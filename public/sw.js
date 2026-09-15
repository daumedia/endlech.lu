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

// ⚠ Feature 05: Wer eine Datei in `public/presse-kit/` ersetzt, erhöht diese Zahl.
// Bilder werden weiter unten cache-first ausgeliefert — ein wiederkehrender
// Besucher sähe sonst die alte Logo-Vorschau neben dem neuen Presse-Paket (das
// selbst nie gecacht wird, weil es kein `image` ist). AK-17 bräche damit im
// Browser, wo kein Prüflauf hinsieht.
// ⚠ Auf `v2` erhöht am 2026-09-12 (BF-99): Die beiden Wort-Bildmarken unter
// `public/presse-kit/` tragen den Schriftzug jetzt als Pfade in Inter statt als
// `<text>` in der Systemschrift. Ohne diese Zahl sähe ein wiederkehrender Besucher
// die alte Vorschau neben dem neuen Paket — genau der Fall, vor dem der Hinweis
// darüber warnt.
//
// ⚠ Auf `v3` erhöht am 2026-09-12 (BF-140), und hier ist die Erhöhung **Teil der
// Reparatur**: Die neue Positivliste verhindert, dass künftig ein Profilbild in den
// Cache gerät — die bereits gecachten bleiben ohne einen Versionswechsel aber liegen.
// Erst `activate` löscht den alten Cache mitsamt Inhalt.
//
// ⚠ Auf `v4` erhöht am 2026-09-15 (OF-11 aus Feature 05 entschieden): Der Schriftzug
// „Endlech" in beiden Wort-Bildmarken unter `public/presse-kit/` trägt jetzt das Blau
// der Bildmarke (#01b6ed) statt #0891b2 bzw. #22d3ee. Ohne diese Zahl sähe ein
// wiederkehrender Besucher die alte Vorschau neben dem neuen Paket.
const CACHE_VERSION = 'endlech-v4';
const OFFLINE_URL = '/offline.html';

// ⚠ BF-140: **Positivliste, keine Ausschlussliste.** Gecacht wird nur, was hier steht.
// Der frühere Zweig nahm jede Antwort mit `destination === 'image'` auf — und damit auch
// `/uploads/avatars/`, also ein Profilbild. Auf einem geteilten Gerät lag es nach dem
// Abmelden weiter im Cache, bis jemand `CACHE_VERSION` erhöht. AK-19 und AK-20 des
// Bestandsfeatures B25 schliessen genau das aus.
//
// ⚠ Der Grund für die Positivliste liegt in der Zukunft: Eine Ausnahme für
// `/uploads/avatars/` hätte den heutigen Fall behoben und den nächsten
// personenbezogenen Pfad wieder mitgenommen. Dasselbe Prinzip trägt `@source` in
// `assets/styles/app.css`.
//
// `uploads/team/` gehört dazu: Das ist das Gründerporträt für `/about` und das
// Presse-Kit, also veröffentlichtes Material. `uploads/restaurants/` sind die Fotos der
// Häuser — Gemeingut der Plattform.
const CACHEBARE_BILDER = [
    '/icons/',
    '/images/',
    '/uploads/restaurants/',
    '/uploads/team/',
];

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
    //
    // ⚠ BF-141: Das Muster erlaubt **ein** Sprachsegment vor `/api/`. Der Grund ist eine
    // Altlast im Routing: `/api/v1` und `/open` sind locale-frei, der ältere
    // `CuisineApiController` liegt dagegen weiterhin unter `/{_locale}/api/cuisines`
    // (so auch in `CLAUDE.md` vermerkt). Ein `startsWith('/api/')` traf ihn deshalb
    // nicht, und die Anfrage landete im cache-first-Zweig weiter unten. Gemessen:
    // `locale_api_eingegriffen=JA`. Schaden entstand keiner — gecacht wurde die Antwort
    // nie, weil dieser Zweig nur Bilder aufnimmt —, aber die Zusage aus AK-09 galt nur
    // für die Hälfte der API-Wege.
    if (request.method !== 'GET' || /^\/(?:[a-z]{2}\/)?api\//.test(url.pathname)) {
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
            if (response && response.ok && CACHEBARE_BILDER.some((pfad) => url.pathname.startsWith(pfad))) {
                const copy = response.clone();
                caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
            }
            return response;
        })),
    );
});
