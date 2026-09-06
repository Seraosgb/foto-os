const CACHE_NAME = 'foto-os-v2';

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

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // 1. Não intercepta chamadas de API (elas são tratadas pelo IndexedDB no report-flow.js)
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // 2. Requisição de Navegação (Página HTML)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Atualiza o cache da página principal com a versão mais recente
                    const cloned = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put('/', cloned));
                    return response;
                })
                .catch(() => caches.match('/'))
        );
        return;
    }

    // 3. Demais Assets (CSS, JS do Vite, Imagens, Fontes): Cache com fallback de rede dinâmico
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
