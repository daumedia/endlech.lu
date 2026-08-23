# B18 · Finanzposten & Kennzahl-Snapshots — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Zwei getrennte Dinge in einem Feature, weil sie denselben Verwaltungsbereich teilen:
gewöhnliches CRUD über `FinanceEntry` und ein idempotenter Schreibvorgang, der den
gesamten Kennzahlenstand als `MetricSnapshot` einfriert.

Verbunden sind sie über `OpenStatsService`: Jede schreibende Finanzaktion ruft
`invalidate()`, und der Snapshot ruft `computeAll()` — die ungecachte Variante.

## Seiten und Routen

Alle `ROLE_ADMIN`, Präfix `/{_locale}/admin/finanzen`.

| Route | Pfad | Methode | CSRF |
|---|---|---|---|
| `admin_finance_index` | `` | GET | — |
| `admin_finance_new` | `/neu` | GET, POST | Formular |
| `admin_finance_edit` | `/{id}/bearbeiten` | GET, POST | Formular |
| `admin_finance_delete` | `/{id}/loeschen` | POST | `delete-finance-{id}` |
| `admin_finance_snapshot` | `/snapshot` | POST | `metric-snapshot` |

Dazu der Konsolenbefehl `app:metrics:snapshot [--month=YYYY-MM] [--force]`.

## Komponentenstruktur

```
admin/finance/index.html.twig     Liste · Summen · Stand · Snapshot-Knopf
├── admin/finance/_form.html.twig  geteilt von new und edit
├── admin/finance/new.html.twig
└── admin/finance/edit.html.twig

src/Open/MetricSnapshotService     defaultMonth() · capture() · fill()
src/Command/…CaptureMetricSnapshotCommand
src/Schedule.php                   #[AsSchedule], cron '15 3 1 * *' Europe/Luxembourg
src/Message/CaptureMetricSnapshot  + MessageHandler
```

⚠ Der Weg über `Schedule` → `Message` → `Handler` ist vollständig gebaut, feuert auf
Produktion aber nicht (AK-17). Der reale Auslöser ist der Cron auf den Befehl.

## Datenmodell

### Tabelle `finance_entry`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT | ja | |
| `entry_date` | DATE | ja | Spaltenname, weil `date` reserviert ist |
| `type` | Enum `FinanceType` | ja | redundant zu `category->type()`, **indiziert** für die Aggregation |
| `category` | Enum `FinanceCategory` | ja | bestimmt die Richtung |
| `amount` | DECIMAL(10,2) | ja | **immer positiv** |
| `quantity` | INT | nein | nur für Kategorien, die eine Menge führen (Inclusion Boxes) |
| `note` | TEXT | nein | |
| `created_at`, `updated_at` | DATETIME | ja | `updated_at` über `#[ORM\PreUpdate]` |

### Tabelle `metric_snapshot`

| Feld | Typ | Bedeutung |
|---|---|---|
| `captured_for` | DATE **UNIQUE** | Monatserster — die Idempotenz liegt hier |
| typisierte Spalten | INT / DECIMAL | `restaurant_count`, `verified_count`, `communes_covered`, `cantons_covered`, `average_accessibility_score`, `step_free_entrances`, `accessible_restrooms`, `wide_doors`, `wheelchair_table_spacing`, `inclusion_boxes_delivered`, `total_expenses` |
| `payload` | JSON | vollständige Momentaufnahme |

Migration: `Version20260820200000` (beide Tabellen plus `restaurant.door_width_cm` und
`restaurant.table_spacing_cm`).

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| Gast, `ROLE_USER` | nichts | `access_control` + `#[IsGranted('ROLE_ADMIN')]` |
| `ROLE_ADMIN` | Posten anlegen, ändern, löschen; Snapshot auslösen | dieselbe Schranke |
| Konsole (Cron) | Snapshot schreiben | kein Sicherheitskontext — wer die Konsole hat, hat den Server |

## Missbrauchsschutz

| Endpunkt | Schutz | Fehlt |
|---|---|---|
| CRUD | Rollenschranke + CSRF | Rate Limit, Audit-Log |
| Snapshot | Rollenschranke + CSRF `metric-snapshot` | Schutz gegen Überschreiben (FB-01) |
| Befehl | Dateisystemzugriff | Überwachung (FB-04) |

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md` — neun Entscheidungen, alle im Quelltext begründet.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `index()`, `findForAdmin()`, `sumByType()`, `findLastUpdatedAt()`, `findLatest()` |
| AK-02 | `FinanceType::tryFrom()` |
| AK-03 | `$this->stats->invalidate()` in `new`, `edit`, `delete` |
| AK-04, AK-05 | `FinanceEntry::setCategory()` |
| AK-06 | `FinanceEntry::setAmount()` |
| AK-07 | Konvention, durchgesetzt über das Fehlen eines Vorzeichenwegs |
| AK-08 | `isCsrfTokenValid('delete-finance-' . $id, …)` |
| AK-09 | `snapshot()`, `isCsrfTokenValid('metric-snapshot', …)` |
| AK-10 | `MetricSnapshotService::defaultMonth()` |
| AK-11 | `if ($existing && !$force)` |
| AK-12 | `$this->stats->computeAll()` in `capture()` |
| AK-13 | `UNIQUE` auf `captured_for` |
| AK-14 | `fill()`, typisierte Setter + `payload` |
| AK-15 | Summe direkt aus `FinanceEntryRepository`, nicht aus dem gesperrten Array |
| AK-16 ⚠ | `capture(null, true)` im Admin-Knopf | Lücke, FB-01 |
| AK-17 ⚠ | `src/Schedule.php` ohne Worker auf Produktion | Lücke, FB-03 |
| AK-18 ⚠ | **Abwesenheit** eines Audit-Logs | Lücke, FB-02 |
| AK-19, AK-20 | Feldbestand; Aggregation in `computeFinance()` |
| AK-21 | siehe Routentabelle |

## Für `sdd-qa` besonders zu prüfen

1. **AK-16** — einen Snapshot schreiben, einen Finanzposten ändern, den Knopf erneut
   drücken und den gespeicherten Wert vergleichen. Der Befund entscheidet, ob der
   Verlauf als Beleg taugt.
2. **AK-17 / OF-02** — auf Produktion nachsehen, ob der Cron eingerichtet ist. Fehlt er,
   ist jeder vergangene Monat bereits unwiederbringlich verloren.
3. Die Tests decken Idempotenz und `--force` bereits ab
   (`tests/Integration/Open/MetricSnapshotServiceTest.php`) — nicht aber, was `force`
   inhaltlich mit einem alten Monat anstellt.
