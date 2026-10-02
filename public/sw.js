/* Prumo PWA — service worker.
 * Estratégia segura p/ app autenticado com CRUD:
 * - Navegações: network-first, cai p/ cache e depois /offline.
 * - Estáticos (css/js/fontes/img, mesmo domínio): cache-first + atualização em fundo.
 * - Nunca intercepta POST/API fora de GET nem outros domínios.
 * Bump CACHE ao mudar este arquivo.
 */
const CACHE = 'finfamilia-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.add(OFFLINE_URL)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Navegação entre páginas: tenta rede, senão cache, senão página offline.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((cache) => cache.put(req, copy));
                    return res;
                })
                .catch(() => caches.match(req).then((hit) => hit || caches.match(OFFLINE_URL)))
        );
        return;
    }

    // Estáticos: serve do cache e atualiza em fundo.
    if (['style', 'script', 'font', 'image'].includes(req.destination)) {
        event.respondWith(
            caches.match(req).then((hit) => {
                const network = fetch(req).then((res) => {
                    if (res.ok) {
                        const copy = res.clone();
                        caches.open(CACHE).then((cache) => cache.put(req, copy));
                    }
                    return res;
                }).catch(() => hit);
                return hit || network;
            })
        );
    }
});
