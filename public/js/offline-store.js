const DB_NAME = 'FotoOS_DB';
const DB_VERSION = 1;

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            // Fila de relatórios pendentes de envio
            if (!db.objectStoreNames.contains('pending_reports')) {
                db.createObjectStore('pending_reports', { keyPath: 'client_temp_id' });
            }
            // Fila de fotos vinculadas
            if (!db.objectStoreNames.contains('pending_photos')) {
                db.createObjectStore('pending_photos', { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

window.OfflineStore = {
    async savePendingReport(reportData) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_reports', 'readwrite');
            const store = tx.objectStore('pending_reports');
            store.put(reportData);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    async savePendingPhoto(photoData) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('pending_photos', 'readwrite');
            const store = tx.objectStore('pending_photos');
            store.put(photoData);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }
};
