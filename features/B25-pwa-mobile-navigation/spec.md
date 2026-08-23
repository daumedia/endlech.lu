# B25 · PWA & mobile Navigation — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Endlech.lu lässt sich über Safaris „Zum Home-Bildschirm" als App installieren: Vollbild,
eigenes Symbol, Service Worker mit Offline-Rückfall und eine feste Navigationsleiste am
unteren Rand. Kein separates Swift-Projekt — dieselben Vorlagen wie im Web.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| — | — | Querschnitt |

## User Stories

- **US-01** · Als iPhone-Nutzer möchte ich die Seite als App auf dem Startbildschirm
  haben.
- **US-02** · Als Nutzer möchte ich bei fehlender Verbindung eine verständliche Seite
  sehen.
- **US-03** · Als Nutzer möchte ich auf dem Telefon mit einer Hand navigieren können.

## Nicht im Scope

Ausdrücklich als Folge-Issues zurückgestellt: Apple-Splash-Screens, Pull-to-Refresh,
Wischgesten, Push-Benachrichtigungen, vollständiger Sieben-Seiten-Mobil-Audit.

## Akzeptanzkriterien

- **AK-01** · Angenommen, eine Seite lädt, wenn der Dokumentkopf betrachtet wird, dann
  stehen dort `<link rel="manifest">`, `theme-color: #0891b2`, die iOS-Meta-Tags und
  `apple-touch-icon` in neun Größen.
- **AK-02** · Angenommen, das Manifest wird gelesen, wenn es geprüft wird, dann trägt es
  `start_url` und `scope` `/`, `display: standalone`, `orientation: portrait` und Icons
  in 192, 512 (`any`) sowie 512 (`maskable`).
- **AK-03** · Angenommen, die Seite wird geladen, wenn das `load`-Ereignis feuert, dann
  registriert `assets/app.ts` `/sw.js` mit Scope `/`; Fehler werden geschluckt.
- **AK-04** · Angenommen, der Service Worker installiert, wenn er durchläuft, dann
  cacht er die App-Shell (`offline.html`, Logo, zwei Icons, Manifest) und ruft
  `skipWaiting()`.
- **AK-05** · Angenommen, eine neue `CACHE_VERSION` ist gesetzt, wenn der Worker
  aktiviert, dann werden alle Caches mit abweichendem Namen gelöscht und
  `clients.claim()` gerufen.
- **AK-06** · Angenommen, eine Navigation schlägt fehl (offline), wenn der Worker
  eingreift, dann liefert er `offline.html` aus.
- **AK-07** · Angenommen, ein Asset unter `/build/` wird angefragt, wenn der Worker
  eingreift, dann antwortet er aus dem Cache und aktualisiert im Hintergrund
  (stale-while-revalidate).
- **AK-08** · Angenommen, nichts ist gecacht und die Verbindung fehlt, wenn ein
  `/build/`-Asset angefragt wird, dann lehnt der Worker **sauber ab**, statt zu
  `undefined` aufzulösen.
- **AK-09** · Angenommen, eine Anfrage ist kein GET **oder** ihr Pfad beginnt mit
  `/api/`, wenn der Worker sie sieht, dann greift er **nicht** ein — API-Daten bleiben
  immer frisch. (`startsWith('/api/')` deckt auch `/api/v1/` ab.)
- **AK-10** · Angenommen, eine Anfrage geht an eine fremde Herkunft, wenn der Worker sie
  sieht, dann greift er nicht ein.
- **AK-11** · Angenommen, die Seite wird auf einem Telefon betrachtet, wenn sie lädt,
  dann erscheint unten eine feste Navigationsleiste mit vier Feldern (Start,
  Restaurants, Über uns, Profil bzw. Anmelden), Tap-Targets ab 44 px und
  `pb-[env(safe-area-inset-bottom)]`.
- **AK-12** · Angenommen, ein Feld gehört zur aktuellen Seite, wenn die Leiste
  gerendert wird, dann trägt es `text-cyan-600` **und** `aria-current="page"`.
- **AK-13** · Angenommen, die Route beginnt mit `admin_`, wenn die Seite lädt, dann
  erscheint die Leiste **nicht**.
- **AK-14** · Angenommen, ein Formularfeld wird auf einem Telefon fokussiert, wenn es
  betrachtet wird, dann ist die Schriftgröße 16 px — das verhindert iOS' automatisches
  Hineinzoomen (`@media (max-width: 767px)` in `assets/styles/app.css`).
- **AK-15** · Angenommen, `<main>` wird betrachtet, wenn seine Abstände geprüft werden,
  dann trägt es `pb-16 md:pb-0`, damit der Inhalt nicht hinter der Leiste liegt.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-16** ⚠ · Angenommen, `CACHE_VERSION` wird bei einer Änderung an `sw.js` oder der
  App-Shell **nicht** erhöht, wenn ein wiederkehrender Nutzer die Seite öffnet, dann
  bekommt er die alte App-Shell.
  *(So verhält sich der Code heute: `const CACHE_VERSION = 'endlech-v1';` ist von Hand
  zu pflegen — ein Kommentar im Kopf der Datei weist darauf hin. Es gibt keinen
  Build-Schritt, der den Wert aus `app.version` oder einem Hash ableitet. Der Wert steht
  seit der Einführung auf `v1`.)*

- **AK-17** ⚠ · Angenommen, ein Nutzer ist auf einem Telefon angemeldet, wenn er sich
  abmelden oder die Sprache wechseln will, dann findet er keinen Weg dafür.
  *(Die Bottom-Navigation ersetzt die auf kleinen Bildschirmen ausgeblendete
  Kopfnavigation — aber nur teilweise: Abmelden, Sprachwahl, „Restaurant vorschlagen"
  und das Menü „Mitmachen" haben dort kein Gegenstück. Vollständig beschrieben in
  `docs/app-shell.md#bekannte-lücken`, Punkte 1–3. Für ein Feature, dessen erklärter
  Zweck die Bedienbarkeit auf dem Telefon ist, ist das der zentrale Befund.)*

- **AK-18** ⚠ · Angenommen, ein Nutzer ist offline und ruft eine Seite auf, die er
  vorher besucht hat, wenn der Worker eingreift, dann bekommt er trotzdem
  `offline.html`.
  *(Navigationen laufen network-first **ohne** Cache-Rückfall auf zuvor besuchte
  Seiten — nur die Offline-Seite ist hinterlegt. Das ist eine vertretbare Entscheidung
  für eine Anwendung mit häufig wechselnden Daten, macht die „App" aber offline
  praktisch leer.)*

### Datenschutz und Missbrauchsschutz

- **AK-19** · Angenommen, der Service Worker läuft, wenn geprüft wird, was er speichert,
  dann sind es statische Dateien der eigenen Herkunft — keine Nutzerdaten, keine
  API-Antworten (AK-09).
- **AK-20** · Angenommen, ein Nutzer meldet sich auf einem geteilten Gerät ab, wenn
  geprüft wird, ob Reste im Cache bleiben, dann liegen dort nur App-Shell und Assets —
  keine personenbezogenen Inhalte.

## Edge Cases

- **EC-01** · Alle PWA-Dateien liegen als statische Dateien in `public/` auf
  Wurzelebene, **ohne** Sprachpräfix und ohne Controller — der Scope `/` verlangt das.
- **EC-02** · Die Icons wurden mit `bin/generate-pwa-icons.sh` (macOS `sips`) erzeugt:
  erst quadratisch weiß padden, dann skalieren — sonst verzerrt, weil `logo.png`
  10000×7664 ist.
- **EC-03** · `icon-512.png` dient zugleich als `maskable`.
- **EC-04** · `viewport-fit=cover` im Viewport ist die Voraussetzung dafür, dass
  `env(safe-area-inset-bottom)` überhaupt greift.

## Fehlbestand

- **FB-01 · Unvollständiger Navigationsersatz auf Mobil.** Siehe AK-17 — der
  gewichtigste Befund.
- **FB-02 · `CACHE_VERSION` wird von Hand gepflegt.** Siehe AK-16.
- **FB-03 · Kein Offline-Zugriff auf besuchte Seiten.** Siehe AK-18.
- **FB-04 · Kein Hinweis auf eine neue Version.** `skipWaiting()` plus
  `clients.claim()` tauschen den Worker sofort; ein Nutzer mit offener Seite bekommt
  gemischte Assets, ohne es zu erfahren.
- **FB-05 · Keine Prüfung, ob der Service Worker registriert wurde.** Fehler werden
  geschluckt; es gibt keine Rückmeldung und kein Protokoll.
- **FB-06 · Keine Apple-Splash-Screens.** Ausdrücklich zurückgestellt — hier als
  Bestandslücke festgehalten, nicht als Versäumnis.

## Offene Fragen

- **OF-01** · Wie soll der Navigationsersatz auf Mobil aussehen (AK-17)? Vier Felder
  sind belegt; ein Menüfeld statt „Über uns" könnte Abmelden, Sprache, Vorschlagen und
  Mitmachen aufnehmen. — Betreiber
- **OF-02** · Soll `CACHE_VERSION` aus `app.version` abgeleitet werden (FB-02)? Der
  Parameter wird bei jedem Release ohnehin gepflegt. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | PWA statt nativer App | PWA | dieselben Vorlagen, kein zweites Projekt |
| 2 | Vanilla Service Worker | statt Workbox | keine neue Abhängigkeit |
| 3 | `/api/` nie cachen | ja | API-Daten sollen immer frisch sein |
| 4 | `/build/` stale-while-revalidate | ja | Encore-Hashing-sicher: neue Dateinamen holen sich neue Antworten |
| 5 | `offline.html` mit Inline-CSS | eigenständig | offline ist weder Server noch Encore-Asset erreichbar |
| 6 | Bottom-Navigation nicht im Admin | Routennamen-Präfix | derselbe Weg wie beim Cookie-Banner |
| 7 | 16 px Schriftgröße für Eingabefelder | ja | verhindert iOS-Auto-Zoom |
