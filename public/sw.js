const CACHE_NAME = 'kdanalytics-v5';
const urlsToCache = [
    '/',
    '/manifest.json'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // 1. Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // 2. Skip requests with schemes other than http: or https: (like chrome-extension://)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

    // 3. Skip dynamic routes that should never be handled by SW cache
    const bypassRoutes = [
        '/research-proposal/preview/',
        '/subscriptions',
        '/wallet',
        '/admin/'
    ];

    if (bypassRoutes.some(path => url.pathname.includes(path))) {
        return; // Let the browser handle it naturally
    }

    // Network First Strategy for other assets
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // If network fetch succeeds, clone and cache the response
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }
                const responseToCache = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseToCache);
                });
                return response;
            })
            .catch(() => {
                // If network fetch fails (offline), try cache
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // CRITICAL FIX: If not in cache, we MUST return a Response object or just throw
                    // Throwing here will let the browser show its own 'Offline' page.
                    throw new Error('Network failed and no cache hit.');
                });
            })
    );
});

self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
