# B25 · PWA & mobile Navigation — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Reiner Frontend- und Static-File-Ansatz: keine Entity, keine Migration, keine
Backend-Logik. Vier statische Dateien in `public/`, einige Meta-Tags in der Shell, ein
Partial und drei Zeilen in `assets/app.ts`.

## Seiten und Routen

Keine. Alle PWA-Dateien liegen als **statische Dateien** auf Wurzelebene — ohne
Sprachpräfix und ohne Controller, weil der Service-Worker-Scope `/` das verlangt.

| Datei | Zweck |
|---|---|
| `public/manifest.webmanifest` | Name, Scope, Anzeigemodus, Icons |
| `public/sw.js` | Service Worker, Scope `/` |
| `public/offline.html` | eigenständige Seite mit Inline-CSS |
| `public/icons/icon-{57…512}.png` | elf Größen, eingecheckt |

## Komponentenstruktur

```
templates/base.html.twig
├── viewport-fit=cover
├── Manifest · theme-color · iOS-Meta-Tags · apple-touch-icon (9 Größen)
├── <main class="pb-16 md:pb-0 print:pb-0">
└── partials/_bottom_nav.html.twig    nur wenn Route nicht mit 'admin_' beginnt

assets/app.ts            navigator.serviceWorker.register('/sw.js', {scope: '/'}) beim load
assets/styles/app.css    @media (max-width: 767px) → input/select/textarea 16px

public/sw.js
├── install   App-Shell vorcachen + skipWaiting()
├── activate  fremde Caches löschen + clients.claim()
└── fetch     Weichen in dieser Reihenfolge:
    ├── kein GET oder /api/  → nicht eingreifen
    ├── fremde Herkunft      → nicht eingreifen
    ├── mode === 'navigate'  → network-first, Fallback offline.html
    ├── /build/              → stale-while-revalidate
    └── sonst                → cache-first (nur Bilder und /icons/ werden abgelegt)
```

## Datenmodell

Keines.

## Zugriffsregeln

Keine. ⚠ Der Service Worker cacht ausschließlich Dateien der eigenen Herkunft und nie
`/api/`-Antworten — damit liegen keine nutzerspezifischen Inhalte im Cache (AK-19).

## Missbrauchsschutz

Nicht anwendbar. Relevanter ist die Cache-Hygiene: `CACHE_VERSION` ist der einzige
Hebel, um veraltete Dateien loszuwerden (FB-02).

## Externe Dienste

Keine — bewusst kein Workbox, keine CDN-Ressourcen.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | Kopfbereich in `base.html.twig`, Twig-Schleife über neun Größen |
| AK-02 | `public/manifest.webmanifest` |
| AK-03 | `assets/app.ts` |
| AK-04, AK-05 | `install`- und `activate`-Handler in `sw.js` |
| AK-06 | `fetch`-Handler, Zweig `mode === 'navigate'` |
| AK-07 | Zweig `/build/` |
| AK-08 | `.catch(() => cached ?? Promise.reject(new Error('offline')))` |
| AK-09 | `request.method !== 'GET' \|\| url.pathname.startsWith('/api/')` |
| AK-10 | `url.origin !== self.location.origin` |
| AK-11, AK-12 | `partials/_bottom_nav.html.twig` |
| AK-13 | `{% if not (…_route) starts with 'admin_' %}` in `base.html.twig` |
| AK-14 | `@media (max-width: 767px)` in `assets/styles/app.css` |
| AK-15 | `<main class="pb-16 md:pb-0">` |
| AK-16 ⚠ | `const CACHE_VERSION = 'endlech-v1'`, von Hand gepflegt | Lücke, FB-02 |
| AK-17 ⚠ | Bottom-Navigation mit vier Feldern; kein Burger-Menü | Lücke, FB-01 |
| AK-18 ⚠ | Navigationszweig ohne Cache-Rückfall | Lücke, FB-03 |
| AK-19, AK-20 | Cache-Weichen im `fetch`-Handler |

## Für `sdd-qa` besonders zu prüfen

1. **AK-17** — die Anwendung auf einem iPhone installieren, anmelden und versuchen,
   sich wieder abzumelden. Das ist der Befund, der den erklärten Zweck des Features
   berührt.
2. **AK-16** — `sw.js` ändern, ohne `CACHE_VERSION` zu erhöhen, und mit einem zuvor
   installierten Client prüfen, was ausgeliefert wird.
3. **AK-09** — im installierten Zustand einen `/api/v1`-Aufruf beobachten und
   sicherstellen, dass keine Antwort im Cache landet.
4. ⚠ **Vor jedem Test:** Änderungen unter `assets/` verlangen `npm run build` und einen
   Commit von `public/build` — sonst blockt der Job `verify-assets` den Deploy.
