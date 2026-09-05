// Função Turbo para comprimir a imagem no dispositivo antes do upload
async function compressImage(file, maxDimension = 1920, quality = 0.9) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                // Redimensionamento proporcional mantendo a proporção (máx 1920px)
                if (width > height) {
                    if (width > maxDimension) {
                        height *= maxDimension / width;
                        width = maxDimension;
                    }
                } else {
                    if (height > maxDimension) {
                        width *= maxDimension / height;
                        height = maxDimension;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name, {
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
            const rawFile = event.target.files[0];
            if (!rawFile) return;

            this.loading = true;
            this.errorMessage = '';

            // Compressão prévia no cliente
            let fileToSend = rawFile;
            try {
                fileToSend = await compressImage(rawFile, 1920, 0.9);
                console.log(`Original: ${(rawFile.size / 1024 / 1024).toFixed(2)} MB`);
                console.log(`Comprimida: ${(fileToSend.size / 1024 / 1024).toFixed(2)} MB`);
            } catch (error) {
                console.warn('Falha na compressão, enviando arquivo bruto como fallback', error);
            }

            // Coleta de coordenadas
            let lat = null;
            let lng = null;

            try {
                const position = await this.getCurrentLocation();
                lat = position.coords.latitude;
                lng = position.coords.longitude;
            } catch (posError) {
                console.warn('GPS indisponível ou negado:', posError);
                lat = -22.7641;
                lng = -43.3994;
                alert('Aviso: Não foi possível obter o GPS real. Coordenadas de contingência ativadas.');
            }

            if (!lat || !lng) {
                this.errorMessage = 'Acesso à localização é obrigatório para registrar a evidência.';
                this.loading = false;
                event.target.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('photo', fileToSend);
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
                        address: response.data.data.address,
                        observation: ''
                    });
                } else {
                    await OfflineStore.savePendingPhoto({
                        report_client_id: this.reportId,
                        blob: fileToSend,
                        latitude: lat,
                        longitude: lng,
                        created_at: new Date().toISOString()
                    });

                    this.photos.push({
                        id: 'local_' + Date.now(),
                        url: URL.createObjectURL(fileToSend),
                        address: 'Pendente de sincronização',
                        observation: ''
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
                    reject(new Error('Geolocalização não suportada no navegador.'));
                }
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });
        },

        async updatePhotoObservation(photoId, observation) {
            if (photoId.startsWith('local_')) {
                return;
            }

            try {
                await axios.patch(`/api/v1/photos/${photoId}`, { observation });
            } catch (err) {
                console.warn('Falha ao salvar observação da foto via patch:', err);
            }
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
                    const pdfUrl = res.data.data.pdf_url;

                    if (navigator.share) {
                        navigator.share({
                            title: `Relatório OS ${this.osNumber}`,
                            text: `Relatório fotográfico consolidado da OS ${this.osNumber}`,
                            url: pdfUrl
                        }).catch(() => {
                            window.location.href = pdfUrl;
                        });
                    } else {
                        window.location.href = pdfUrl;
                    }
                } else {
                    alert('Relatório salvo localmente. Conecte-se à internet para sincronizar e gerar o PDF.');
                }
            } catch (err) {
                this.errorMessage = err.response?.data?.message || 'Erro ao finalizar relatório.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
