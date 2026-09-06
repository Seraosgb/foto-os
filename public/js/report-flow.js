async function compressImage(file, maxDimension = 1920, quality = 0.85) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxDimension) {
                        height = Math.round(height * (maxDimension / width));
                        width = maxDimension;
                    }
                } else {
                    if (height > maxDimension) {
                        width = Math.round(width * (maxDimension / height));
                        height = maxDimension;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name || 'foto.jpg', {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, 'image/jpeg', quality);
            };
            img.onerror = (error) => reject(error);
        };
        reader.onerror = (error) => reject(error);
    });
}

const OfflineStore = {
    dbPromise: null,
    init() {
        if (!this.dbPromise) {
            this.dbPromise = new Promise((resolve, reject) => {
                const req = indexedDB.open('FotoOS_DB', 1);
                req.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains('pending_reports')) {
                        db.createObjectStore('pending_reports', { keyPath: 'client_temp_id' });
                    }
                    if (!db.objectStoreNames.contains('pending_photos')) {
                        db.createObjectStore('pending_photos', { keyPath: 'client_photo_id' });
                    }
                };
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }
        return this.dbPromise;
    },

    async savePendingReport(data) {
        const db = await this.init();
        return new Promise((res, rej) => {
            const tx = db.transaction('pending_reports', 'readwrite');
            tx.objectStore('pending_reports').put(data);
            tx.oncomplete = () => res();
            tx.onerror = () => rej(tx.error);
        });
    },

    async savePendingPhoto(data) {
        const db = await this.init();
        return new Promise((res, rej) => {
            const tx = db.transaction('pending_photos', 'readwrite');
            tx.objectStore('pending_photos').put(data);
            tx.oncomplete = () => res();
            tx.onerror = () => rej(tx.error);
        });
    },

    async getPendingReports() {
        const db = await this.init();
        return new Promise((res) => {
            const tx = db.transaction('pending_reports', 'readonly');
            const req = tx.objectStore('pending_reports').getAll();
            req.onsuccess = () => res(req.result || []);
        });
    },

    async getPendingPhotos() {
        const db = await this.init();
        return new Promise((res) => {
            const tx = db.transaction('pending_photos', 'readonly');
            const req = tx.objectStore('pending_photos').getAll();
            req.onsuccess = () => res(req.result || []);
        });
    },

    async deletePhoto(clientPhotoId) {
        const db = await this.init();
        return new Promise((res, rej) => {
            const tx = db.transaction('pending_photos', 'readwrite');
            tx.objectStore('pending_photos').delete(clientPhotoId);
            tx.oncomplete = () => res();
            tx.onerror = () => rej(tx.error);
        });
    },

    async deleteReport(clientTempId) {
        const db = await this.init();
        return new Promise((res, rej) => {
            const tx = db.transaction('pending_reports', 'readwrite');
            tx.objectStore('pending_reports').delete(clientTempId);
            tx.oncomplete = () => res();
            tx.onerror = () => rej(tx.error);
        });
    }
};

function registerReportFlow() {
    if (typeof Alpine === 'undefined') return;

    Alpine.data('reportFlow', () => ({
        step: 1,
        loading: false,
        errorMessage: '',
        successMessage: '',
        reportId: null,
        pdfUrl: null,
        isFinalized: false,

        // Status de Rede e Sincronização
        isOnline: navigator.onLine,
        isSyncing: false,
        syncProgress: 0,
        syncTotal: 0,
        syncStatusText: '',

        // Taxonomias Dinâmicas & Autocomplete
        availableUnits: [],
        unitSuggestions: [],
        sectorSuggestions: [],
        showUnitDropdown: false,
        showSectorDropdown: false,

        // Campos do Formulário
        osNumber: '',
        unit: '',
        sectorInput: '',
        sectors: [],
        technicians: '',
        history: '',
        photos: [],

        debounceSearchTimer: null,

        async init() {
            await this.loadTaxonomies();

            this.$watch('unit', () => this.filterUnits());
            this.$watch('sectorInput', () => this.filterSectors());

            window.addEventListener('online', () => {
                this.isOnline = true;
                this.syncPendingData();
            });

            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.errorMessage = 'Sem conexão à internet. Operando em modo offline.';
            });

            // Se carregar já conectado, verifica se existem relatórios pendentes
            if (this.isOnline) {
                this.syncPendingData();
            }
        },

        async loadTaxonomies() {
            try {
                const res = await axios.get('/api/taxonomies/units');
                this.availableUnits = res.data || [];
            } catch (e) {
                console.warn('Modo offline: Taxonomias indisponíveis na rede.', e);
            }
        },

        filterUnits() {
            const query = (this.unit || '').trim().toLowerCase();
            if (!query) {
                this.unitSuggestions = this.availableUnits.slice(0, 6);
                return;
            }
            this.unitSuggestions = this.availableUnits
                .filter(u => u.name.toLowerCase().includes(query))
                .slice(0, 6);
        },

        selectUnit(item) {
            this.unit = item.name;
            this.showUnitDropdown = false;
            this.filterSectors();
        },

        filterSectors() {
            const query = (this.sectorInput || '').trim().toLowerCase();
            const currentUnitObj = this.availableUnits.find(
                u => u.name.trim().toLowerCase() === (this.unit || '').trim().toLowerCase()
            );

            const baseSectors = currentUnitObj && currentUnitObj.sectors ? currentUnitObj.sectors : [];

            if (!query) {
                this.sectorSuggestions = baseSectors
                    .filter(s => !this.sectors.includes(s.name))
                    .slice(0, 6);
                return;
            }

            this.sectorSuggestions = baseSectors
                .filter(s => s.name.toLowerCase().includes(query) && !this.sectors.includes(s.name))
                .slice(0, 6);
        },

        onOsInput() {
            clearTimeout(this.debounceSearchTimer);
            const os = (this.osNumber || '').trim();

            if (!os || os.length < 2) {
                this.successMessage = '';
                this.isFinalized = false;
                this.errorMessage = '';
                return;
            }

            this.debounceSearchTimer = setTimeout(() => {
                this.searchOs();
            }, 600);
        },

        async searchOs() {
            const os = this.osNumber.trim();
            if (!os || os.length < 2 || !navigator.onLine) return;

            try {
                const response = await axios.get('/api/reports/search', { params: { os_number: os } });
                if (response.data.found) {
                    const r = response.data.data;
                    this.reportId = r.id;
                    this.unit = r.unit;
                    this.sectors = r.sectors || [];
                    this.technicians = r.technicians || '';
                    this.history = r.history || '';
                    this.photos = r.photos || [];

                    if (r.status_slug === 'finalizado') {
                        this.isFinalized = true;
                        this.successMessage = '';
                        this.errorMessage = 'Esta OS já foi finalizada. Clique abaixo se desejar reabri-la.';
                    } else {
                        this.isFinalized = false;
                        this.errorMessage = '';
                        this.successMessage = `OS encontrada! Rascunho recuperado com ${this.photos.length} foto(s).`;
                    }
                    this.filterSectors();
                } else {
                    this.successMessage = '';
                    this.isFinalized = false;
                    this.errorMessage = '';
                }
            } catch (err) {
                console.warn('Erro ao pesquisar OS:', err);
            }
        },

        async reopenReport() {
            if (!this.reportId || !navigator.onLine) return;
            this.loading = true;
            this.errorMessage = '';
            try {
                await axios.post(`/api/reports/${this.reportId}/reopen`);
                this.isFinalized = false;
                this.successMessage = 'OS reaberta com sucesso! Você pode editar os dados e capturar mais fotos.';
            } catch (err) {
                this.errorMessage = err.response?.data?.error || 'Erro ao reabrir OS.';
            } finally {
                this.loading = false;
            }
        },

        addSector(sectorName = null) {
            const val = (sectorName || this.sectorInput || '').trim();
            if (val && !this.sectors.includes(val)) {
                this.sectors.push(val);
                this.sectorInput = '';
                this.showSectorDropdown = false;
                this.filterSectors();
            }
        },

        removeSector(index) {
            this.sectors.splice(index, 1);
            this.filterSectors();
        },

        async startReport() {
            if (!this.osNumber || !this.unit || this.sectors.length === 0) {
                this.errorMessage = 'Preencha OS, Unidade e ao menos um Setor.';
                return;
            }

            this.loading = true;
            this.errorMessage = '';

            const payload = {
                os_number: this.osNumber,
                unit: this.unit,
                sectors: this.sectors,
                technicians: this.technicians,
                history: this.history
            };

            try {
                if (navigator.onLine) {
                    const response = await axios.post('/api/reports', payload);
                    this.reportId = response.data.data.id;
                    this.step = 2;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    const tempId = this.reportId || ('temp_' + Date.now());
                    this.reportId = tempId;
                    await OfflineStore.savePendingReport({
                        client_temp_id: tempId,
                        ...payload,
                        created_at: new Date().toISOString()
                    });
                    this.step = 2;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch (err) {
                // Se falhar na rede, faz fallback para armazenamento offline
                const tempId = this.reportId || ('temp_' + Date.now());
                this.reportId = tempId;
                await OfflineStore.savePendingReport({
                    client_temp_id: tempId,
                    ...payload,
                    created_at: new Date().toISOString()
                });
                this.step = 2;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } finally {
                this.loading = false;
            }
        },

        triggerCamera() {
            this.$refs.cameraInput.click();
        },

        async handlePhotoCapture(event) {
            const rawFile = event.target.files[0];
            if (!rawFile) return;

            this.loading = true;
            this.errorMessage = '';

            let fileToSend = rawFile;
            try {
                fileToSend = await compressImage(rawFile, 1920, 0.85);
            } catch (error) {
                console.warn('Falha na compressão client-side:', error);
            }

            let lat = null;
            let lng = null;

            try {
                const position = await this.getCurrentLocation();
                lat = position.coords.latitude;
                lng = position.coords.longitude;
            } catch (posError) {
                console.warn('GPS indisponível:', posError);
                lat = -22.7641;
                lng = -43.3994;
            }

            const formData = new FormData();
            formData.append('photo', fileToSend);
            formData.append('latitude', lat);
            formData.append('longitude', lng);
            formData.append('observation', '');

            const isTemporary = !this.reportId || String(this.reportId).startsWith('temp_');

            try {
                if (navigator.onLine && !isTemporary) {
                    const response = await axios.post(`/api/reports/${this.reportId}/photos`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });

                    this.photos.push({
                        id: response.data.data.id,
                        url: response.data.data.url,
                        observation: ''
                    });
                } else {
                    const clientPhotoId = 'local_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
                    await OfflineStore.savePendingPhoto({
                        client_photo_id: clientPhotoId,
                        report_client_id: this.reportId,
                        blob: fileToSend,
                        latitude: lat,
                        longitude: lng,
                        created_at: new Date().toISOString()
                    });

                    this.photos.push({
                        id: clientPhotoId,
                        url: URL.createObjectURL(fileToSend),
                        observation: ''
                    });
                }
            } catch (err) {
                // Fallback de contingência local em erro de transmissão
                const clientPhotoId = 'local_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
                await OfflineStore.savePendingPhoto({
                    client_photo_id: clientPhotoId,
                    report_client_id: this.reportId,
                    blob: fileToSend,
                    latitude: lat,
                    longitude: lng,
                    created_at: new Date().toISOString()
                });

                this.photos.push({
                    id: clientPhotoId,
                    url: URL.createObjectURL(fileToSend),
                    observation: ''
                });
            } finally {
                this.loading = false;
                event.target.value = '';
            }
        },

        getCurrentLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocalização não suportada.'));
                }
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 8000,
                    maximumAge: 0
                });
            });
        },

        async updatePhotoObservation(photoId, observation) {
            if (String(photoId).startsWith('local_')) return;
            try {
                await axios.patch(`/api/photos/${photoId}`, { observation });
            } catch (err) {
                console.warn('Falha ao atualizar observação:', err);
            }
        },

        movePhoto(index, direction) {
            const targetIndex = index + direction;
            if (targetIndex < 0 || targetIndex >= this.photos.length) return;

            const item = this.photos.splice(index, 1)[0];
            this.photos.splice(targetIndex, 0, item);

            const isTemporary = !this.reportId || String(this.reportId).startsWith('temp_');
            if (navigator.onLine && !isTemporary) {
                const order = this.photos.map(p => p.id);
                axios.patch(`/api/reports/${this.reportId}/photos/reorder`, { order });
            }
        },

        async syncPendingData() {
            if (this.isSyncing || !navigator.onLine) return;

            const pendingReports = await OfflineStore.getPendingReports();
            if (pendingReports.length === 0) return;

            this.isSyncing = true;
            this.syncTotal = pendingReports.length;
            this.syncProgress = 0;

            for (let i = 0; i < pendingReports.length; i++) {
                const rep = pendingReports[i];
                this.syncStatusText = `Sincronizando OS ${rep.os_number} (${i + 1}/${this.syncTotal})...`;

                try {
                    const res = await axios.post('/api/reports', {
                        os_number: rep.os_number,
                        unit: rep.unit,
                        sectors: rep.sectors,
                        technicians: rep.technicians,
                        history: rep.history
                    });

                    const realReportId = res.data.data.id;
                    if (this.reportId === rep.client_temp_id) {
                        this.reportId = realReportId;
                    }

                    const pendingPhotos = await OfflineStore.getPendingPhotos();
                    const photosToSync = pendingPhotos.filter(p => p.report_client_id === rep.client_temp_id);

                    for (let j = 0; j < photosToSync.length; j++) {
                        const photo = photosToSync[j];
                        this.syncStatusText = `Enviando foto ${j + 1}/${photosToSync.length} da OS ${rep.os_number}...`;

                        const formData = new FormData();
                        formData.append('photo', photo.blob, 'foto.jpg');
                        formData.append('latitude', photo.latitude);
                        formData.append('longitude', photo.longitude);
                        formData.append('observation', photo.observation || '');

                        await axios.post(`/api/reports/${realReportId}/photos`, formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        });

                        await OfflineStore.deletePhoto(photo.client_photo_id);
                    }

                    if (rep.is_finalized) {
                        await axios.post(`/api/reports/${realReportId}/finalize`);
                    }

                    await OfflineStore.deleteReport(rep.client_temp_id);
                } catch (err) {
                    console.error('Falha na sincronização do relatório:', err);
                }

                this.syncProgress = Math.round(((i + 1) / this.syncTotal) * 100);
            }

            this.isSyncing = false;
            this.syncStatusText = '';
            this.successMessage = 'Dados sincronizados com sucesso com o servidor!';
            setTimeout(() => { this.successMessage = ''; }, 4000);
        },

        async finalize() {
            if (this.photos.length === 0) {
                this.errorMessage = 'Adicione ao menos uma foto para finalizar.';
                return;
            }

            this.loading = true;
            const isTemporary = !this.reportId || String(this.reportId).startsWith('temp_');

            try {
                if (navigator.onLine && !isTemporary) {
                    const res = await axios.post(`/api/reports/${this.reportId}/finalize`);
                    this.pdfUrl = res.data.data.pdf_url;
                    this.step = 3;
                } else {
                    if (this.reportId) {
                        const pendingReports = await OfflineStore.getPendingReports();
                        const currentRep = pendingReports.find(r => r.client_temp_id === this.reportId);
                        if (currentRep) {
                            currentRep.is_finalized = true;
                            await OfflineStore.savePendingReport(currentRep);
                        }
                    }
                    alert('Relatório gravado offline com sucesso! Ele será enviado e o PDF gerado assim que o dispositivo recuperar o sinal de internet.');
                    this.resetFlow();
                }
            } catch (err) {
                this.errorMessage = err.response?.data?.message || 'Erro ao finalizar relatório.';
            } finally {
                this.loading = false;
            }
        },

        shareWhatsapp() {
            if (!this.pdfUrl) return;
            const text = encodeURIComponent(
                `*RELATÓRIO DE SERVIÇO CONCLUÍDO*\n` +
                `*OS:* ${this.osNumber}\n` +
                `*Unidade:* ${this.unit}\n` +
                `*Setores:* ${this.sectors.join(', ')}\n\n` +
                `Acesse o documento digital:\n${this.pdfUrl}`
            );
            window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
        },

        shareReport() {
            if (navigator.share && this.pdfUrl) {
                navigator.share({
                    title: `Relatório OS ${this.osNumber}`,
                    text: `Relatório de serviço OS ${this.osNumber} - ${this.unit}`,
                    url: this.pdfUrl
                }).catch(() => {});
            }
        },

        resetFlow() {
            this.step = 1;
            this.loading = false;
            this.errorMessage = '';
            this.successMessage = '';
            this.reportId = null;
            this.pdfUrl = null;
            this.isFinalized = false;
            this.osNumber = '';
            this.unit = '';
            this.sectorInput = '';
            this.sectors = [];
            this.technicians = '';
            this.history = '';
            this.photos = [];
        }
    }));
}

if (window.Alpine) {
    registerReportFlow();
} else {
    document.addEventListener('alpine:init', registerReportFlow);
}
