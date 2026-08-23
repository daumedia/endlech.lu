# B16 · Transparenzseite `/open` — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Controller mit einer Aktion und einer privaten Methode. Die Rechenarbeit liegt in
`App\Open\`: `OpenStatsService` aggregiert, `CantonResolver` ordnet Städte zu,
`AccessibilityScore` bewertet. Die Darstellung besteht aus einer Seite und vier
Partials, von denen drei reine SVG- bzw. CSS-Grafiken sind — keine Diagramm-Bibliothek.

## Seiten und Routen

| Route | Pfad | Zugang |
|---|---|---|
| `app_open` | `/{_locale}/open` | öffentlich |
| `app_open_redirect` | `/open` (sprachfrei) | Weiterleitung auf `lb` |

Die sprachfreie Weiterleitung ist begründet: *„/open ist die URL, die in Fördermails,
auf Visitenkarten und in Vorträgen steht – sie darf nicht an einer Sprachwahl
scheitern."*

## Komponentenstruktur

```
open/index.html.twig
├── Hero                      Leitzahl: Restaurants
├── Plattform                 open/_metric.html.twig  (Wert · Veränderung · Einordnung)
├── Wirkung                   open/_bar.html.twig     (Anteilsbalken, aria-hidden)
│   └── Kantonstabelle        id="canton-coverage"
├── Punkteverteilung          open/_histogram.html.twig (Säulen, 85 % Höhe)
├── Finanzen                  _bar + „Stand vom"-Hinweis (ab 60 Tagen amber)
├── Verlauf                   open/_sparkline.html.twig (reines SVG)
└── Daten                     Verweise auf B17

src/Open/
├── OpenStatsService   platform() · impact() · finance() · all() · computeAll() · invalidate()
├── CantonResolver     100 Gemeinden in 12 Kantonen + Alias-Tabelle
└── AccessibilityScore forRestaurant() → 0–10
```

## Datenmodell

Nur lesend: `restaurant` (über `findRawForStats()` bzw. entsprechende Aggregate),
`finance_entry`, `metric_snapshot`.

**`CantonResolver`:** alle 100 Gemeinden in 12 Kantonen (Stand nach den Fusionen vom
1. Januar 2024) plus Alias-Tabelle (Stadtteile Luxemburgs, luxemburgische und deutsche
Namen, bekannte Ortschaften).

⚠ **Gemeinde- und Alias-Index sind getrennt.** Beim Zerlegen zusammengesetzter Angaben
(„Rue de la Gare, Strassen") dürfen nur echte Gemeindenamen greifen — läge „gare"
(Stadtteil) im selben Topf, landete der Eintrag in Luxemburg.

**`AccessibilityScore`:** acht gleichgewichtete Merkmale, nicht erfasste zählen als
nicht erfüllt.

**Cache:** eigener Pool `cache.open_stats` (Filesystem, TTL 3600; in `when@test`
`cache.adapter.array`), drei Schlüssel `open_stats.{platform,impact,finance}`.

## Zugriffsregeln

Keine — die Seite ist der Zweck. Es gibt nichts Nutzerspezifisches darauf.

## Missbrauchsschutz

| Aspekt | Vorhanden |
|---|---|
| Rate Limit | keins (FB-02) |
| Cache | eigener Pool, 1 h — begrenzt die Aggregatabfragen |
| Quartalssperre | **strukturell**: gesperrte Werte fehlen im Ergebnis-Array |

## Externe Dienste

Keine. `twig/intl-extra` wurde für dieses Feature ergänzt (`format_number`,
`format_currency`).

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md` — zehn Entscheidungen, davon vier zur
Diagrammgestaltung, alle im Design-System (`docs/design-system.md`) mit Begründung
hinterlegt.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `OpenController::index()`, `open/index.html.twig` |
| AK-02 | `app_open_redirect` in `config/routes.yaml` |
| AK-03 | Hero-Block im Template |
| AK-04 | `'hasTrend' => \count($trend) >= 2` |
| AK-05, AK-06 | `OpenController::deltas()` |
| AK-07 | `OpenStatsService::computeFinance()` — Quartalssperre vor dem Array-Aufbau |
| AK-08 | 60-Tage-Schwelle im Template, `bg-amber-50` |
| AK-09 | `CantonResolver`, unbekannt → unzugeordnet |
| AK-10 | `_bar`, `_histogram` mit `aria-hidden`; `<details>`-Tabellen, `id="canton-coverage"` |
| AK-11, AK-12 | Farbwahl in `_histogram` bzw. `_bar` |
| AK-13 | `print:hidden` in `base.html.twig`, `@media print` in `assets/styles/app.css` |
| AK-14 | `format_number` / `format_currency` |
| AK-15 | `_sparkline.html.twig`, `preserveAspectRatio="none"`, `vector-effect="non-scaling-stroke"` |
| AK-16 | `cache.open_stats`, `TTL = 3600` |
| AK-17 ⚠ | Aggregate **ohne** `isVerified`-Filter | Lücke, FB-01 |
| AK-18 ⚠ | `AccessibilityScore`, nicht erfasst = nicht erfüllt | bewusste Entscheidung mit unerwünschter Folge |
| AK-19, AK-20, AK-21 | Aggregation; keine Zugangsregel |

## Für `sdd-qa` besonders zu prüfen

1. **AK-07** — die Quartalssperre über `/open.json` gegenprüfen. Sie ist die einzige
   Stelle, an der eine Anzeigeregel Datenschutzcharakter hat; wären die Zahlen nur im
   Template verborgen, wären sie abrufbar.
2. **AK-17** — ein unverifiziertes Restaurant anlegen und die Abdeckung vorher/nachher
   vergleichen.
3. **AK-13** — die Seite tatsächlich als PDF speichern. Verlaufsbänder ohne
   Textfarbenkorrektur ergäben weiß auf weiß.
