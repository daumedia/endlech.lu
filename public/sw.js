/*
 * Service Worker für die Endlech.lu PWA (Issue #83).
 *
 * Strategie:
 *  - App-Shell (Offline-Seite, Logo, Icons, Manifest) beim Install vorcachen.
 *  - Navigationen: network-first, bei Fehler -> Offline-Fallback.
 *  - Gebaute Assets unter /build/: stale-while-revalidate (Encore-Hashing-sicher).
 *  - Bilder/Icons: cache-first mit Netzwerk-Fallback.
 *  - /api/-Requests und Nicht-GET: nie cachen (immer frische Daten).
 *
 * CACHE_VERSION bei relevanten Änderungen an dieser Datei oder der App-Shell
 * erhöhen, damit veraltete Caches verworfen werden.
 */

const CACHE_VERSION = 'endlech-v1';
const OFFLINE_URL = '/offline.html';

const APP_SHELL = [
    OFFLINE_URL,
    '/images/logo.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Nur GET-Requests behandeln; API-Daten immer frisch lassen.
    if (request.method !== 'GET' || url.pathname.startsWith('/api/')) {
        return;
    }

    // Nur eigene Origin cachen (keine Cross-Origin-Ressourcen).
    if (url.origin !== self.location.origin) {
        return;
    }

    // Navigationen: network-first mit Offline-Fallback.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );
        return;
    }

    // Gebaute Assets: stale-while-revalidate.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(CACHE_VERSION).then((cache) => cache.match(request).then((cached) => {
                const network = fetch(request)
                    .then((response) => {
                        if (response && response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    // Cold-Start offline (noch nichts gecacht): sauber ablehnen
                    // statt zu undefined aufzulösen.
                    .catch(() => cached ?? Promise.reject(new Error('offline')));
                return cached || network;
            })),
        );
        return;
    }

    // Sonstige (Bilder/Icons): cache-first mit Netzwerk-Fallback.
    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((response) => {
            if (response && response.ok && (request.destination === 'image' || url.pathname.startsWith('/icons/'))) {
                const copy = response.clone();
                caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
            }
            return response;
        })),
    );
});
