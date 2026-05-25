/**
 * Crono — service worker del kiosko (PWA).
 *
 * Estrategia: network-first para navegación (siempre intenta la versión fresca del
 * kiosko), con fallback al shell cacheado si no hay red — así la tablet abre /marcar
 * aunque arranque sin conexión, y la cola offline (IndexedDB) se encarga del resto.
 * NO cachea /api/marcar (los marcajes los maneja la cola, no el SW).
 */
const CACHE = 'crono-kiosko-v1';
const SHELL = ['/marcar', '/js/crono-offline.js'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    const url = new URL(req.url);

    // Nunca interceptar el API de marcaje ni POSTs (la cola offline los maneja).
    if (req.method !== 'GET' || url.pathname.startsWith('/api/')) return;

    // Solo manejamos el scope del kiosko.
    if (!url.pathname.startsWith('/marcar') && !url.pathname.startsWith('/js/')) return;

    e.respondWith(
        fetch(req)
            .then((resp) => {
                // refrescar cache del shell con la última versión buena
                const copia = resp.clone();
                caches.open(CACHE).then((c) => c.put(req, copia)).catch(() => {});
                return resp;
            })
            .catch(() => caches.match(req).then((c) => c || caches.match('/marcar')))
    );
});
