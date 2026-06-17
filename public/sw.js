const CACHE_NAME = 'helpdesk-v1';
const STATIC_ASSETS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
];

// Cache CDN assets on install
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Cache-first for CDN static assets
    if (STATIC_ASSETS.includes(request.url)) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(res => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                return res;
            }))
        );
        return;
    }

    // Network-first for app routes (graceful offline fallback)
    if (url.origin === self.location.origin && request.method === 'GET' && request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match('/dashboard').then(r => r || new Response(
                    '<html><body style="font-family:sans-serif;text-align:center;padding:3rem"><h2>Helpdesk IT</h2><p>Tidak ada koneksi internet. Silakan periksa jaringan Anda.</p></body></html>',
                    { headers: { 'Content-Type': 'text/html' } }
                ))
            )
        );
        return;
    }

    // Default: network only
    event.respondWith(fetch(request));
});
