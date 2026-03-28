// Service Worker for Kalkan Social - V10 (Skip third-party to fix Google Ads)
const CACHE_NAME = 'kalkansocial-v17';
const STATIC_CACHE = 'kalkansocial-static-v6';
const IMAGE_CACHE = 'kalkansocial-images-v5';

const precacheResources = [
    '/',
    '/logo.jpg',
    '/manifest.json',
    '/icon-192.png',
    '/icon-512.png',
    '/assets/css/main.min.css',
    '/assets/js/theme.js'
];

// Install event - cache essential resources
self.addEventListener('install', event => {
    console.log('[SW] Installing new version...');
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(precacheResources);
        }).then(() => {
            // Force activation without waiting for tabs to close
            return self.skipWaiting();
        })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('[SW] Activating and cleaning old caches...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // Delete any cache that doesn't match current versions
                    if (cacheName !== CACHE_NAME &&
                        cacheName !== STATIC_CACHE &&
                        cacheName !== IMAGE_CACHE) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Take control of all clients immediately
            return self.clients.claim();
        })
    );
});

// Fetch event handling
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // 1. Skip non-GET
    if (event.request.method !== 'GET') return;

    // 2. Skip third-party (analytics, ads, tracking) - prevents "Failed to convert value to 'Response'"
    try {
        const scopeOrigin = new URL(self.registration.scope).origin;
        const isFirstParty = url.origin === scopeOrigin;
        const isCdn = /cdnjs|fonts\.google|jsdelivr|unpkg|cloudflare/.test(url.hostname);
        if (!isFirstParty && !isCdn) return;
    } catch (e) {}

    // 3. Network Only for API/Auth
    if (url.pathname.includes('/api/') ||
        url.pathname.includes('login') ||
        url.pathname.includes('logout')) {
        return;
    }

    // 3. Static Assets (Fonts, CSS, JS) - Cache First
    if (url.hostname.includes('fonts') ||
        url.hostname.includes('cdnjs') ||
        url.pathname.match(/\.(css|js|woff2)$/)) {
        event.respondWith(cacheFirst(STATIC_CACHE, event.request));
        return;
    }

    // 4. Images - Stale-While-Revalidate
    if (url.pathname.match(/\.(jpg|jpeg|png|gif|svg|webp)$/)) {
        event.respondWith(staleWhileRevalidate(IMAGE_CACHE, event.request));
        return;
    }

    // 5. HTML Pages - NETWORK-FIRST so login/logout state updates immediately (PWA fix)
    // Auth-dependent header (Login vs Profile) must be fresh; fallback to cache when offline
    if (event.request.mode === 'navigate' || url.pathname.indexOf('.') === -1 || url.pathname.endsWith('.php')) {
        event.respondWith(networkFirst(CACHE_NAME, event.request));
        return;
    }

    // Fallback
    event.respondWith(networkFirst(CACHE_NAME, event.request));
});

// Strategies
async function cacheFirst(cacheName, request) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;
    return fetch(request).then(resp => {
        cache.put(request, resp.clone());
        return resp;
    });
}

// Helper: Network-First Strategy 
async function networkFirst(cacheName, request) {
    const cache = await caches.open(cacheName);
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (err) {
        // Fallback to cache if network fails (offline)
        return await cache.match(request);
    }
}

// Helper: Stale-While-Revalidate Strategy
async function staleWhileRevalidate(cacheName, request) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    const networkFetch = fetch(request).then(networkResponse => {
        if (networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(() => {
        // Silently fail network if offline
    });

    // Return cached response immediately if available, otherwise wait for network
    return cachedResponse || networkFetch;
}

// Push notification event (Same as before)
self.addEventListener('push', event => {
    let data = {
        title: 'Kalkan Social',
        body: 'Yeni bildiriminiz var',
        url: '/',
        icon: '/icon-192.png'
    };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text() || data.body;
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/icon-192.png',
        badge: '/logo.jpg',
        vibrate: [100, 50, 100],
        data: { url: data.url || '/' }
    };

    event.waitUntil(self.registration.showNotification(data.title, options));
});

// Notification click event
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url || '/'));
});
