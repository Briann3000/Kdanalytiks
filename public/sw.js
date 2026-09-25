const CACHE_NAME = 'kdanalytiks-v6';
const urlsToCache = [
    '/',
    '/manifest.json'
];

self.addEventListener('install', event => {
    self.skipWaiting();
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
        '/research-proposal/',
        '/subscriptions',
        '/wallet',
        '/admin/',
        '/surveys/',
        '/ai/',
        '/reports/',
        '/socius/',
        '/api/'
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
                try {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache).catch(() => {});
                    }).catch(() => {});
                } catch (e) {}
                return response;
            })
            .catch(() => {
                // If network fetch fails (offline), try cache
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
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
