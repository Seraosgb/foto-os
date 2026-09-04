document.addEventListener('alpine:init', () => {
    Alpine.data('reportFlow', () => ({
        step: 1, // 1: Dados OS, 2: Captura de Fotos, 3: Finalização
        loading: false,
        errorMessage: '',
        reportId: null, // UUID retornado pela API

        // Campos do Formulário
        osNumber: '',
        unit: '',
        sectorInput: '',
        sectors: [],
        technicians: '',
        history: '',

        // Fotos
        photos: [],

        addSector() {
            const val = this.sectorInput.trim();
            if (val && !this.sectors.includes(val)) {
                this.sectors.push(val);
                this.sectorInput = '';
            }
        },

        removeSector(index) {
            this.sectors.splice(index, 1);
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
                    const response = await axios.post('/api/v1/reports', payload);
                    this.reportId = response.data.data.id;
                    this.step = 2;
                } else {
                    // Fallback Offline: Gera ID temporário de cliente e enfileira
                    const tempId = 'temp_' + Date.now();
                    this.reportId = tempId;
                    await OfflineStore.savePendingReport({
                        client_temp_id: tempId,
                        ...payload,
                        created_at: new Date().toISOString()
                    });
                    this.step = 2;
                }
            } catch (err) {
                this.errorMessage = err.response?.data?.message || 'Falha ao iniciar relatório.';
            } finally {
                this.loading = false;
            }
        },

        triggerCamera() {
            this.$refs.cameraInput.click();
        },

        async handlePhotoCapture(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.loading = true;
            this.errorMessage = '';

            // Obtenção das coordenadas de GPS
            let lat = null;
            let lng = null;

            try {
                const position = await this.getCurrentLocation();
                lat = position.coords.latitude;
                lng = position.coords.longitude;
            } catch (posError) {
                console.warn('GPS indisponível:', posError);
            }

            if (!lat || !lng) {
                this.errorMessage = 'Acesso à localização é obrigatório para registrar a evidência.';
                this.loading = false;
                event.target.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('photo', file);
            formData.append('latitude', lat);
            formData.append('longitude', lng);
            formData.append('observation', '');

            try {
                if (navigator.onLine && !this.reportId.startsWith('temp_')) {
                    const response = await axios.post(`/api/v1/reports/${this.reportId}/photos`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });

                    this.photos.push({
                        id: response.data.data.id,
                        url: response.data.data.url,
                        address: response.data.data.address
                    });
                } else {
                    // Fallback Offline: Salva Blob da imagem no IndexedDB
                    await OfflineStore.savePendingPhoto({
                        report_client_id: this.reportId,
                        blob: file,
                        latitude: lat,
                        longitude: lng,
                        created_at: new Date().toISOString()
                    });

                    // Preview local usando URL temporária
                    this.photos.push({
                        id: 'local_' + Date.now(),
                        url: URL.createObjectURL(file),
                        address: 'Pendente de sincronização'
                    });
                }
            } catch (err) {
                this.errorMessage = err.response?.data?.message || 'Erro ao enviar foto.';
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
                    timeout: 10000,
                    maximumAge: 0
                });
            });
        },

        async finalize() {
            if (this.photos.length === 0) {
                this.errorMessage = 'Adicione ao menos uma foto para finalizar.';
                return;
            }

            this.loading = true;
            try {
                if (navigator.onLine && !this.reportId.startsWith('temp_')) {
                    const res = await axios.post(`/api/v1/reports/${this.reportId}/finalize`);
                    window.location.href = res.data.data.pdf_url;
                } else {
                    alert('Relatório salvo localmente. Conecte-se à internet para finalizar e gerar o PDF.');
                }
            } catch (err) {
                this.errorMessage = err.response?.data?.message || 'Erro ao finalizar relatório.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
