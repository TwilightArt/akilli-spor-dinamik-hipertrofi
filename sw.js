/**
 * ============================================================================
 * Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1)
 * Offline PWA Service Worker (sw.js)
 * ============================================================================
 */

const CACHE_NAME = 'hipertrofi-cache-v4';

const STATIC_ASSETS = [
    '/manifest.json',
    '/assets/css/style.css',
    '/assets/js/api.js',
    '/assets/js/layout.js',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png'
];

// Install: Cache purely static assets
self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

// Activate: Clean up old versions
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
        }).then(() => self.clients.claim())
    );
});

// Fetch: Strategy depending on request type
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // 1. API calls: Network-First with offline JSON fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return new Response(
                        JSON.stringify({
                            success: false,
                            offline: true,
                            error: 'Çevrimdışı moddasınız. İnternet bağlantınızı kontrol edin.'
                        }),
                        {
                            status: 503,
                            headers: { 'Content-Type': 'application/json; charset=UTF-8' }
                        }
                    );
                });
            })
        );
        return;
    }

    // 2. Navigation / HTML requests: Network-Only
    // NEVER cache dynamic HTML pages or personal PHP views containing CSRF tokens and user state
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(
                    '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Çevrimdışı</title></head><body style="background:#09090b;color:#fff;font-family:sans-serif;padding:2rem;text-align:center;"><h2>Çevrimdışı Moddasınız</h2><p>İnternet bağlantınızı kontrol edin.</p></body></html>',
                    { headers: { 'Content-Type': 'text/html; charset=UTF-8' } }
                );
            })
        );
        return;
    }

    // 3. Static assets: Cache-First with Network fallback and revalidation
    const isStaticAsset = /\.(css|js|png|jpg|jpeg|svg|webp|ico|woff|woff2|json)$/i.test(url.pathname);
    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => null);

                return cachedResponse || fetchPromise;
            })
        );
    }
});
