# B17 · Offener Datensatz & Kennzahl-Endpunkte — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Drei sprachfreie Endpunkte geben die Daten der Plattform maschinenlesbar heraus:
`/open.json` (Kennzahlen und 24-Monats-Verlauf), `/open/dataset.csv` und
`/open/dataset.json` (der vollständige Barrierefreiheits-Datensatz unter CC BY 4.0).

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B18 | bestand | der Verlauf kommt aus `MetricSnapshot` |
| B16 | bestand | dieselbe Quelle wie die gerenderte Seite |

## User Stories

- **US-01** · Als Journalist möchte ich die Zahlen abrufen, ohne die Seite zu lesen.
- **US-02** · Als Forscher möchte ich den vollständigen Datensatz herunterladen.
- **US-03** · Als Fördergeber möchte ich den Verlauf über zwei Jahre sehen.

## Nicht im Scope

- Die gerenderte Transparenzseite → B16
- Pflege der Finanzdaten und Snapshots → B18
- Schreibender Zugriff — alle drei Endpunkte sind `GET`

## Akzeptanzkriterien

- **AK-01** · Angenommen, `/open.json` wird aufgerufen, wenn die Antwort betrachtet
  wird, dann enthält sie dieselben Kennzahlen wie die Seite `/{locale}/open` — aus
  derselben Quelle (`OpenStatsService::all()`), damit beide nicht auseinanderlaufen.
- **AK-02** · Angenommen, `/open.json` wird aufgerufen, wenn der Block `trend`
  betrachtet wird, dann stehen dort bis zu **24** Monatswerte aus `MetricSnapshot` mit
  zwölf Feldern je Monat.
- **AK-03** · Angenommen, eine der drei Antworten wird betrachtet, wenn nach der Lizenz
  gesucht wird, dann steht `CC-BY-4.0` samt URL darin — im CSV zusätzlich als Header
  `X-Licence`.
- **AK-04** · Angenommen, `/open/dataset.csv` wird aufgerufen, wenn die Antwort
  betrachtet wird, dann trägt sie `Content-Type: text/csv; charset=utf-8` und
  `Content-Disposition: attachment; filename="endlech-accessibility-dataset.csv"`.
- **AK-05** · Angenommen, die CSV-Datei wird in einem gewöhnlichen Parser geöffnet, wenn
  die erste Spalte gelesen wird, dann heißt sie `id` — **ohne** vorangestelltes
  UTF-8-BOM.
- **AK-06** · Angenommen, eine Zeile des Datensatzes wird betrachtet, wenn nach
  Kontaktdaten gesucht wird, dann enthält sie **weder E-Mail-Adresse noch
  Telefonnummer** — obwohl beide auf jeder Detailseite stehen.
- **AK-07** · Angenommen, eine Zeile wird betrachtet, wenn ihre Felder gezählt werden,
  dann sind es 21: Kennung, Name, Stadt, Gemeinde, Kanton, Koordinaten, Küchen,
  Website, Verifikationsstatus und -datum, Punktzahl und die acht
  Barrierefreiheitsangaben plus zwei Maße und das Anlagedatum.
- **AK-08** · Angenommen, ein Wert ist `null`, ein Boolean oder eine Liste, wenn er ins
  CSV geschrieben wird, dann erscheint er als leerer String, als `true`/`false` bzw.
  mit `|` verbunden.
- **AK-09** · Angenommen, die Stadt eines Restaurants ist bekannt, wenn die Zeile gebaut
  wird, dann stehen dort zusätzlich die aufgelöste **Gemeinde** und der **Kanton**;
  bei unbekanntem Wert bleiben beide leer — es wird **nicht geraten**.
- **AK-10** · Angenommen, eine der drei Antworten wird betrachtet, wenn die Cache-Header
  gelesen werden, dann stehen dort `public` und `max-age=3600`.
- **AK-11** · Angenommen, im selben Request wurde irgendwo eine Session angefasst, wenn
  die Cache-Header gelesen werden, dann stehen sie **trotzdem** auf `public` — der
  Marker `NO_AUTO_CACHE_CONTROL_HEADER` verhindert, dass Symfonys Session-Listener sie
  auf `private, must-revalidate` überschreibt.
- **AK-12** · Angenommen, einer der Endpunkte wird mit Sprachpräfix aufgerufen
  (`/de/open.json`), wenn die Anfrage durchläuft, dann wird die Route **nicht** gefunden
  — sie sind bewusst sprachfrei.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-13** ⚠ · Angenommen, ein Restaurant ist **nicht verifiziert**, wenn der Datensatz
  abgerufen wird, dann steht es trotzdem darin — kenntlich nur am Feld `isVerified`.
  *(So verhält sich der Code heute: `RestaurantRepository::findAllForExport()` filtert
  nicht. In Verbindung mit B23/AK-21 — jeder angemeldete Nutzer kann über die API
  ungeprüft Einträge anlegen — bedeutet das: Ein eingeschleuster Eintrag landet ohne
  Zwischenschritt im offiziellen, unter CC BY 4.0 veröffentlichten Datensatz der
  Plattform. Das ist die gewichtigste Verkettung, die diese Erfassung zutage
  gefördert hat.)*

- **AK-14** ⚠ · Angenommen, `/open/dataset.csv` wird aufgerufen, wenn die Serverlast
  betrachtet wird, dann werden **alle** Restaurants samt Küchen geladen und im Speicher
  zu CSV gebaut — bei jedem Aufruf, den kein vorgelagerter Cache abfängt.
  *(Kein Rate Limit auf den Datenendpunkten; der `public, max-age`-Header wirkt nur,
  wenn ein Reverse Proxy oder CDN davorsteht. Ob das auf Cloudways der Fall ist, geht
  aus dem Repository nicht hervor.)*

### Datenschutz und Missbrauchsschutz

- **AK-15** · Angenommen, der Datensatz wird veröffentlicht, wenn geprüft wird, ob er
  personenbezogene Daten enthält, dann sind es **Geschäftsdaten** — Name, Adresse,
  Koordinaten und Website von Gastronomiebetrieben. Bei inhabergeführten Betrieben kann
  der Betriebsname eine natürliche Person benennen; Kontaktdaten sind ausgeschlossen
  (AK-06).
- **AK-16** · Angenommen, jemand ruft den Datensatz ab, wenn geprüft wird, ob eine
  Anmeldung nötig ist, dann nicht — die Veröffentlichung ist der Zweck.
- **AK-17** · Angenommen, `generatedAt` wird betrachtet, wenn es geprüft wird, dann
  trägt es den Abrufzeitpunkt im Format `DATE_ATOM`.

## Edge Cases

- **EC-01** · Leere Datenbank → die CSV-Kopfzeile entsteht aus
  `array_keys($this->row(new Restaurant(), …))`, die Spalten stehen also auch ohne
  Datensätze.
- **EC-02** · `fopen('php://temp')` schlägt fehl → `RuntimeException`; wird als 500
  ausgeliefert (die Endpunkte liegen **nicht** unter `/api/v1`, greifen also nicht in
  `ApiExceptionSubscriber`).
- **EC-03** · `fputcsv(..., ',', '"', '')` — der leere Escape-Parameter ist seit PHP 8.1
  nötig, um den Backslash nicht als Escape zu behandeln.
- **EC-04** · Die Sprachfreiheit kommt aus dem `open_data`-Block in
  `config/routes.yaml` **und** dem `exclude`-Eintrag am `controllers`-Loader. Der
  `exclude` ist deshalb eine **Liste** mit zwei Einträgen (`Api/V1/` und `Open/`).

## Fehlbestand

- **FB-01 · Kein Filter auf geprüfte Einträge.** Siehe AK-13.
- **FB-02 · Kein Rate Limit.** Siehe AK-14.
- **FB-03 · Keine Versionierung des Datensatzformats.** Wird eine Spalte umbenannt,
  brechen alle Abnehmer stillschweigend. `/open.json` und die Datensätze tragen keine
  Formatversion — anders als die REST-API, die `/v1` im Pfad führt.
- **FB-04 · Kein `Last-Modified` und kein `ETag`.** Ein Abnehmer kann nicht bedingt
  abrufen; jede Anfrage überträgt den vollen Datensatz.
- **FB-05 · Keine Datenschutzerklärung zur Veröffentlichung.** Dass Betriebsdaten unter
  CC BY 4.0 weitergegeben werden, steht in `docs/` und im Code, aber nicht als Hinweis
  gegenüber den Betrieben selbst.

## Offene Fragen

- **OF-01** · Soll der Datensatz auf verifizierte Einträge beschränkt werden (AK-13)?
  Das reduzierte ihn stark (3 von 11 in den Fixtures), machte ihn aber belastbar.
  Alternative: den API-Weg moderieren (B23/OF-01) und hier alles lassen. — Betreiber
- **OF-02** · Steht auf Cloudways ein Cache vor der Anwendung (AK-14)? — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Sprachpräfix | keines | die Zahlen sind in jeder Sprache dieselben; ein Präfix zwänge jeden Abrufer zur Wahl und ließe zitierte URLs in vier Varianten kursieren |
| 2 | Kontaktdaten im Datensatz | ausgeschlossen | „ein Sammelabzug davon ist eine Adressliste, kein Barrierefreiheits-Datensatz" |
| 3 | UTF-8-BOM | keines | es landete im ersten Spaltennamen jedes gewöhnlichen Parsers |
| 4 | Cache-Header | `public, max-age=3600` + `NO_AUTO_CACHE_CONTROL_HEADER` | ohne den Marker überschreibt der Session-Listener alles |
| 5 | Kennzahlen aus derselben Quelle wie die Seite | `OpenStatsService::all()` | Seite und JSON können nicht auseinanderlaufen |
| 6 | Lizenz | CC BY 4.0 | Nachnutzung mit Namensnennung |
