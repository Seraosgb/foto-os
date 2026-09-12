const CACHE_NAME = 'foto-os-v6';

const ASSETS_TO_CACHE = [
    '/',
    '/manifest.json',
    '/favicon.ico',
    '/js/report-flow.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// --- NOVO: Listener de Background Sync ---
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-os-pendentes') {
        event.waitUntil(processBackgroundSync());
    }
});

// Processamento assíncrono em background (Tenta desovar o IndexedDB para a API)
async function processBackgroundSync() {
    try {
        // Como o Service Worker roda em escopo global, abrimos o IndexedDB manualmente se necessário
        // Ou notificamos os clients abertos para realizarem o flush da fila.
        const clientsList = await clients.matchAll({ includeUncontrolled: true, type: 'window' });
        for (const client of clientsList) {
            client.postMessage({ type: 'TRIGGER_SYNC' });
        }
    } catch (err) {
        console.error('[SW] Erro ao processar background sync:', err);
    }
}

self.addEventListener('fetch', (event) => {
    // 🛡️ TRAVA: Ignora requisições de extensões (chrome-extension://)
    if (!event.request.url.startsWith('http')) {
        return;
    }
    const url = new URL(event.request.url);

    // 1. Não intercepta chamadas de API (tratadas pelo IndexedDB / Axios)
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // 2. Requisição de Navegação (Página HTML)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const cloned = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put('/', cloned));
                    return response;
                })
                .catch(() => caches.match('/'))
        );
        return;
    }

    // 3. Demais Assets (CSS, JS, Imagens, Fontes): Cache com fallback de rede dinâmico
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request).then((networkResponse) => {
                if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                    return networkResponse;
                }

                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseToCache);
                });

                return networkResponse;
            });
        })
    );
});
