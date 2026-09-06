const DB_NAME = 'FotoOS_Offline_DB';
const DB_VERSION = 1;

function openOfflineDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            // Store de Relatórios Pendentes
            if (!db.objectStoreNames.contains('pending_reports')) {
                db.createObjectStore('pending_reports', { keyPath: 'client_id' });
            }

            // Store de Fotos Pendentes (armazena o Blob binário)
            if (!db.objectStoreNames.contains('pending_photos')) {
                const photoStore = db.createObjectStore('pending_photos', { keyPath: 'client_photo_id' });
                photoStore.createIndex('report_client_id', 'report_client_id', { unique: false });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

const OfflineStore = {
    async saveReport(reportData) {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_reports', 'readwrite');
            const store = tx.objectStore('pending_reports');
            store.put(reportData);
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    },

    async savePhoto(photoData) {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_photos', 'readwrite');
            const store = tx.objectStore('pending_photos');
            store.put(photoData);
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    },

    async getPendingReports() {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_reports', 'readonly');
            const store = tx.objectStore('pending_reports');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    },

    async getPhotosByReportClientId(reportClientId) {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_photos', 'readonly');
            const store = tx.objectStore('pending_photos');
            const index = store.index('report_client_id');
            const req = index.getAll(reportClientId);
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    },

    async deletePhoto(clientPhotoId) {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_photos', 'readwrite');
            tx.objectStore('pending_photos').delete(clientPhotoId);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    async deleteReport(clientId) {
        const db = await openOfflineDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_reports', 'readwrite');
            tx.objectStore('pending_reports').delete(clientId);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }
};

window.OfflineStore = OfflineStore;
