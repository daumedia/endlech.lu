# B26 · Cookie-Banner — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Stimulus-Controller, ein Partial, zwei Einbindungsstellen in der Shell. Keine
Entity, keine Migration, keine Backend-Änderung — die Entscheidung lebt ausschließlich
in einem Cookie und wird von niemandem gelesen.

## Seiten und Routen

Keine eigenen. Eingebunden auf **allen** öffentlichen Seiten über
`templates/base.html.twig`, unterdrückt auf `admin_*`-Routen.

## Komponentenstruktur

```
templates/base.html.twig
├── Fußzeile: <li data-controller="cookie-consent">
│   └── <button data-action="cookie-consent#openSettings">    → dispatch('open')
└── partials/_cookie_banner.html.twig
    └── data-controller="cookie-consent"
        data-action="cookie-consent:open@window->cookie-consent#reopen"
        ├── role="dialog" aria-modal="false"
        ├── Link → path('app_impressum') ~ '#datenschutz'     → B13
        ├── Knopf annehmen   → accept()
        └── Knopf ablehnen   → decline()

assets/controllers/cookie_consent_controller.ts
├── values: cookieName (Vorgabe 'cookie_consent'), lifetime (Vorgabe 365)
├── connect()      zeigt das Banner, wenn kein Cookie existiert
├── accept() / decline()   Cookie setzen, Banner ausblenden
└── reopen()       Banner erneut zeigen
```

Beide Einstiegspunkte sind über `hasBannerTarget` abgesichert, weil die
Fußzeilen-Instanz kein Banner-Ziel besitzt.

## Datenmodell

Keines. Zustand:

| Cookie | Werte | Eigenschaften |
|---|---|---|
| `cookie_consent` | `accepted` \| `declined` | `path=/`, `max-age` = 365 Tage, `samesite=lax`, `secure` nur bei HTTPS |

## Zugriffsregeln

Keine.

## Missbrauchsschutz

Nicht anwendbar. Bemerkenswert ist die Gegenrichtung: Es gibt **nichts**, was der Banner
zu unterbinden hätte — die Anwendung lädt keine Fremdressourcen (AK-11).

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02 | `connect()` mit Cookie-Prüfung |
| AK-03 | `accept()` / `decline()` |
| AK-04 | `openSettings()` → `dispatch('open')` → `reopen()` über den `@window`-Deskriptor |
| AK-05 | `partials/_cookie_banner.html.twig` |
| AK-06 | `{% if not (…_route) starts with 'admin_' %}` an **beiden** Einbindungen |
| AK-07, AK-08 ⚠ | **Abwesenheit** jeder Auswertung von `cookie_consent` | Lücke, FB-01 |
| AK-09 ⚠ | Cookie ohne Zeitstempel und Fassungsnummer | Lücke, FB-02 |
| AK-10 | Cookie-Inhalt |
| AK-11 | keine externen Ressourcen in `base.html.twig` |

## Für `sdd-qa` besonders zu prüfen

1. **AK-08** — ablehnen und danach prüfen, welche Cookies gesetzt sind. Der Befund
   entscheidet, ob der Banner eine Funktion hat oder nur eine Geste ist.
2. **AK-11** — den Netzwerk-Mitschnitt einer beliebigen Seite auf Fremdhosts prüfen.
   Fände sich dort etwas, kippte die Einordnung von FB-01 von „folgenlos" zu „relevant".
3. **FB-05** — mit der Tastatur prüfen, wie viele Tabulatorschritte bis zum Banner
   nötig sind.
