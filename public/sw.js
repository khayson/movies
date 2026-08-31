const CACHE = 'streamvault-shell-v1';
const SHELL = ['/', '/manifest.webmanifest', '/favicon.ico', '/favicon.svg', '/apple-touch-icon.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)),
        )).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Never cache video/stream or API traffic.
    if (
        url.pathname.startsWith('/watch')
        || url.pathname.startsWith('/api')
        || url.pathname.startsWith('/livewire')
        || url.pathname.startsWith('/cron')
    ) {
        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok && request.destination === 'document') {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            })
            .catch(() => caches.match(request).then((cached) => cached || caches.match('/'))),
    );
});
