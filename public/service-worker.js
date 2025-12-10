// DevFlow Pro Service Worker
const CACHE_NAME = 'devflow-pro-v1';

// Install event - minimal caching to prevent errors
self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting());
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - network only (no caching for now)
self.addEventListener('fetch', event => {
    // Just pass through to network, no caching
    return;
});
