# B17 · Offener Datensatz & Kennzahl-Endpunkte — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Controller mit drei Aktionen und zwei privaten Hilfsmethoden. `row()` definiert das
Datensatzformat an genau einer Stelle und wird von beiden Export-Aktionen benutzt —
CSV und JSON können deshalb nicht auseinanderlaufen. `cached()` setzt die Cache-Header
inklusive des Markers, der Symfonys Session-Listener davon abhält, sie zu überschreiben.

## Seiten und Routen

Alle sprachfrei, alle `GET`, alle öffentlich.

| Route | Pfad | Antwort |
|---|---|---|
| `app_open_json` | `/open.json` | Kennzahlen + 24-Monats-Verlauf + Lizenz |
| `app_open_dataset_json` | `/open/dataset.json` | Umschlag + `data[]` |
| `app_open_dataset_csv` | `/open/dataset.csv` | CSV als Download |

Sprachfreiheit über den `open_data`-Block in `config/routes.yaml`; das Verzeichnis
`src/Controller/Open/` ist am `controllers`-Loader ausgeschlossen.

## Komponentenstruktur

Kein Template — die drei Aktionen erzeugen ihre Antworten direkt.

```
OpenDataController
├── stats()        OpenStatsService::all() + MetricSnapshotRepository::findTrend(24)
├── datasetJson()  RestaurantRepository::findAllForExport() → row()
├── datasetCsv()   dieselbe Quelle → php://temp → fputcsv
├── row()          das Datensatzformat, 21 Spalten
└── cached()       public, max-age, NO_AUTO_CACHE_CONTROL_HEADER
```

## Datenmodell

Nur lesend. Quellen:

| Quelle | Wofür |
|---|---|
| `OpenStatsService::all()` | Plattform-, Wirkungs- und Finanzkennzahlen (gecacht, TTL 3600) |
| `MetricSnapshotRepository::findTrend(24)` | Verlauf |
| `RestaurantRepository::findAllForExport()` | alle Restaurants mit Küchen, `name ASC` |
| `CantonResolver` | Stadt → Gemeinde, Stadt → Kanton |
| `AccessibilityScore::forRestaurant()` | 0–10 aus acht Merkmalen |

**Ausgeschlossene Felder** (bewusst): `email`, `phone`, `instagramUrl`, `facebookUrl`,
`tiktokUrl`, `nearbyStopsNote`, `accessibilityNotes`, `submittedBy`, Bilder,
Öffnungszeiten, Bestellwege.

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| jeder, auch anonym | alle drei Endpunkte lesen | keine `access_control`-Regel deckt `^/open` — es gibt keine, also gilt kein Zwang |
| niemand | schreiben | nur `GET` deklariert |

## Missbrauchsschutz

| Endpunkt | Limit | Milderung |
|---|---|---|
| alle drei | **keins** | `public, max-age=3600` — wirkt nur mit vorgelagertem Cache |

## Externe Dienste

Keine. Der Datenfluss geht nach **außen**, nicht hinaus zu einem Dienstleister — das ist
der Zweck des Features und zugleich der Grund, warum die Feldauswahl in `row()` die
wichtigste Entscheidungsstelle ist.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`. Ergänzend:

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 7 | `row()` für beide Formate | getrennte Aufbauten | CSV und JSON können nicht divergieren |
| 8 | Kopfzeile aus `array_keys(row(new Restaurant()))` | feste Spaltenliste | die Spalten folgen automatisch dem Format; funktioniert auch bei leerer Datenbank |
| 9 | `php://temp` statt `StreamedResponse` | Streaming | einfacher; der Preis ist Speicher bzw. eine temporäre Datei je Aufruf |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `stats()` → `OpenStatsService::all()` |
| AK-02 | `MetricSnapshotRepository::findTrend(24)` |
| AK-03 | Konstanten `LICENCE`, `LICENCE_URL`; Header `X-Licence` im CSV |
| AK-04 | Header in `datasetCsv()` |
| AK-05 | kein BOM geschrieben |
| AK-06 | Feldauswahl in `row()` |
| AK-07 | dieselbe |
| AK-08 | `match`-Ausdruck in `datasetCsv()` |
| AK-09 | `CantonResolver::resolveCommune()` / `resolveCanton()` |
| AK-10, AK-11 | `cached()` |
| AK-12 | `open_data`-Block ohne `_locale` |
| AK-13 ⚠ | `findAllForExport()` ohne Filter | Lücke, FB-01 |
| AK-14 ⚠ | **Abwesenheit** eines Limiters | Lücke, FB-02 |
| AK-15, AK-16 | Feldauswahl; keine Zugangsregel |
| AK-17 | `(new \DateTimeImmutable())->format(\DATE_ATOM)` |

## Für `sdd-qa` besonders zu prüfen

1. **AK-13 in Verbindung mit B23/AK-21** — ein Restaurant über die API anlegen und
   danach `/open/dataset.csv` abrufen. Steht es drin, ist die Kette bestätigt: von der
   ungeprüften API-Eingabe bis in den offiziellen offenen Datensatz.
2. **AK-06** — den vollständigen Datensatz nach `@` und Ziffernfolgen durchsuchen.
3. **AK-11** — den Endpunkt in einer Sitzung aufrufen (angemeldet) und die Cache-Header
   prüfen.
