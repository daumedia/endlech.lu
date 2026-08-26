# B17 · Offener Datensatz & Kennzahl-Endpunkte — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — zwei niedrige Befunde.

21 von 21 Kriterien bestanden, 4 von 4 Edge Cases. Der Datenschutzteil ist der beste im
Projekt: In der Datenbank stehen **7 E-Mail-Adressen und 9 Telefonnummern**, und in
keiner der drei Ausgaben taucht davon eine einzige auf — auch nicht die konkrete
Adresse `info@bellavista.lu`, gegen die ich einzeln geprüft habe. Das ist keine
Nebenwirkung, sondern eine Entscheidung, die im Code sichtbar bleibt: 21 Spalten,
alle einzeln aufgezählt.

**Der schwerste Befund dieser Spec hat sich heute erledigt.** AK-13 nennt die
Verkettung „ungeprüfter Eintrag über die API → offizieller CC-BY-Datensatz" die
*„gewichtigste Verkettung, die diese Erfassung zutage gefördert hat"*. Sie ist seit der
BF-24-Reparatur von heute Mittag unterbrochen — in der B23-QA nachgemessen: Ein über die
API eingereichter Eintrag liefert **0 Treffer** in `dataset.csv`. Was im Datensatz steht,
hat einen Admin gesehen.

Nächster Aufruf: **`/sdd-erfassen B10`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Kennzahlen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `/open.json` liefert `platform.restaurants: 11`, `verifiedShare: 27.3`, `averageScore: 5.09`; dieselben Werte stehen auf `/de/open` |
| **AK-02** | ✅ bestanden | Mit 27 Snapshots in der Datenbank liefert `trend` genau **24** Monate (`2024-08` bis `2026-07`) — die Obergrenze greift. **12 Felder** je Monat: `accessibleRestrooms, averageAccessibilityScore, cantonsCovered, communesCovered, inclusionBoxesDelivered, month, restaurants, stepFreeEntrances, totalExpenses, verified, wheelchairTableSpacing, wideDoors` |
| AK-03 | ✅ bestanden | `/open.json` → `licence=CC-BY-4.0` · `/open/dataset.json` → dito · CSV → Header `X-Licence: CC-BY-4.0` |
| AK-17 | ✅ bestanden | `generatedAt: 2026-08-24T16:35:28+00:00` — DATE_ATOM per Regex geprüft |

### Datensatz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-04 | ✅ bestanden | `Content-Type: text/csv; charset=utf-8`, `Content-Disposition: attachment; filename="endlech-accessibility-dataset.csv"` |
| **AK-05** | ✅ bestanden | Erste drei Bytes: `69 64 2c` = `id,` — **kein BOM**. Test `testAk05CsvBeginntOhneBom` |
| **AK-06** | ✅ bestanden | In der DB: 7 E-Mails, 9 Telefonnummern. Im CSV: **0** Treffer für `@`, `+352`, `tel:`; die konkrete Adresse `info@bellavista.lu` in CSV, `dataset.json` und `open.json` je **0** |
| AK-07 | ✅ bestanden | Genau **21** Spalten, erste heißt `id`, `email` und `phone` kommen nicht vor. Test `testAk07DatensatzFuehrtEinundzwanzigSpalten` |
| AK-08 | ✅ bestanden | Liste: `Fast Food\|Burger` · Boolean: nur `true`/`false` · `null`: leerer String (`website`, `verifiedAt`, `doorWidthCm` in Zeile 1) |
| **AK-09** | ✅ bestanden | `Luxembourg-Grund → Luxembourg/Luxembourg`, `Cloche d'Or → Luxembourg/Luxembourg` (beides Stadtteile), `Dudelange → Dudelange/Esch-sur-Alzette`, `Diekirch → Diekirch/Diekirch`. **0** unzugeordnete Zeilen im Bestand |
| AK-15 / AK-16 | ✅ bestanden | Nur Geschäftsdaten; kein Zugriffsschutz — die Veröffentlichung ist der Zweck |

### Technische Eigenschaften

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-10 | ✅ bestanden | Alle drei Endpunkte: `Cache-Control: max-age=3600, public` |
| **AK-11** | ✅ bestanden | Mit angemeldeter Admin-Sitzung (Session garantiert angefasst) bleiben **alle drei** auf `max-age=3600, public` — keine Herabstufung auf `private`. `OpenDataController.php:178` setzt `NO_AUTO_CACHE_CONTROL_HEADER`. Test `testAk11CacheHeaderBleibtOeffentlichTrotzSession` |
| AK-12 | ✅ bestanden | `/open.json` **200**, `/de/open.json` **404**; `/open/dataset.csv` **200**, `/de/open/dataset.csv` **404**; die HTML-Seite `/de/open` bleibt **200** |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-13** ⚠ | ✅ bestätigt | 11 Zeilen, davon **3 verifiziert und 8 nicht** — alle im Datensatz. `findAllForExport()` filtert nicht → BF-41, **aber neu bewertet**, siehe unten |
| **AK-14** ⚠ | ✅ bestätigt | 12 Abrufe von `dataset.csv` in Folge: **zwölfmal 200**, kein Limit. **0** Limiter im `Open`-Controller → BF-42 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `OpenDataController.php:99`: `array_keys($this->row($first ?? new Restaurant(), $cantons))` — die Kopfzeile entsteht aus einem leeren Objekt, wenn kein Datensatz da ist |
| EC-02 | ✅ bestanden | Die Endpunkte liegen unter `src/Controller/Open/`, nicht unter `/api/v1` — `ApiExceptionSubscriber` greift dort nicht (in der B23-QA am Pfadpräfix belegt) |
| EC-03 | ✅ bestanden | `fputcsv($handle, $columns, ',', '"', '')` — der leere Escape-Parameter steht in Zeile 100 und 103 |
| EC-04 | ✅ bestanden | `config/routes.yaml:30–32`: `exclude` ist eine Liste mit `'../src/Controller/Api/V1/'` **und** `'../src/Controller/Open/'` |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Personenbezogene Daten** | keine Kontaktdaten in irgendeiner der drei Ausgaben (siehe AK-06) |
| **Zurückgehaltene Einnahmen** | die Quartalssperre greift strukturell — geprüft durch den vorhandenen Test `testWithheldIncomeIsAbsentFromTheJson` |
| **Rate Limit** | keins → BF-42 |
| **CSV-Struktur zerstören** | Restaurantname auf `=cmd\|.\\x22 "Test", Semi;kolon` gesetzt: Die Zeile behält **21 Spalten**, Anführungszeichen und Kommas werden korrekt maskiert. Die CSV-Struktur ist nicht zu brechen |
| **Formelinjektion** | derselbe Name steht **unverändert mit führendem `=`** im Datensatz → BF-43 |
| **Sprachfreiheit** | `/de/…`-Varianten aller Datenendpunkte → 404 |
| **Testsuite** | 358 Tests, 0 Fehler |

## Fehler

### BF-41 · Unverifizierte Einträge stehen im veröffentlichten Datensatz — niedrig

**Betrifft:** AK-13

**Nachweis:** 11 Zeilen im Datensatz, davon **8 mit `isVerified=false`**.
`RestaurantRepository::findAllForExport()` filtert nicht.

**Die Bewertung hat sich heute geändert — und das gehört hier hin.** Die Spec führt
diesen Punkt als *„die gewichtigste Verkettung, die diese Erfassung zutage gefördert
hat"*: Über `POST /api/v1/restaurants` konnte jeder angemeldete Nutzer ungeprüft einen
Eintrag anlegen, und der landete ohne Zwischenschritt im CC-BY-Datensatz.

**Diese Verkettung ist seit heute Mittag unterbrochen** (BF-24, Commit `e61c253`). In der
B23-QA nachgemessen: Ein über die API eingereichter Eintrag liefert **0 Treffer** in
`dataset.csv`, `dataset.json`, der Restaurantliste und den Kennzahlen. Alle drei Wege in
die Restauranttabelle — Admin, genehmigte Suggestion, API — laufen jetzt über eine
Sichtung.

**Was übrig bleibt, ist eine Produktfrage, kein Sicherheitsproblem:** Soll ein
admin-erfasster, aber nicht als „verifiziert" markierter Betrieb im veröffentlichten
Datensatz stehen? Dafür spricht, dass `isVerified` in der Zeile steht und jeder Nutzer
selbst filtern kann; dass 8 von 11 Einträgen nicht verifiziert sind, macht die
Alternative auch wenig attraktiv — ein Datensatz mit drei Zeilen belegt nichts.

**Vorschlag:** So lassen, aber **in der Dokumentation des Datensatzes benennen**. Wer die
Datei bekommt, sollte ohne Nachfragen wissen, was `isVerified` bedeutet und dass die
Mehrheit der Zeilen es nicht ist. Eine `README`-Spalte im ZIP oder ein Feld
`verificationNote` im JSON-Kopf.

### BF-42 · Kein Rate Limit auf den Datenendpunkten — niedrig

**Betrifft:** AK-14

**Reproduktion:** 12 Abrufe von `/open/dataset.csv` in Folge → `200 200 200 200 200 200
200 200 200 200 200 200`. **0** Limiter im `Open`-Controller.

Jeder Abruf lädt alle Restaurants samt Küchen-Relation und baut die CSV im Speicher. Der
`public, max-age=3600`-Header nimmt einem vorgelagerten Cache die Arbeit ab — **ob auf
Cloudways einer davorsteht, geht aus dem Repository nicht hervor** (das ist OF-01 der
Spec und weiterhin offen).

**Sechste Wiederholung von M-01.** Die Konvention steht seit heute in `CLAUDE.md`; dieser
Endpunkt fällt allerdings nicht unter ihren Wortlaut („löst eine Mail aus oder prüft ein
Geheimnis") — er tut beides nicht, er ist nur teuer. Das ist eine Lücke in meiner eigenen
Formulierung, und sie gehört erweitert: **auch Endpunkte, die bei jedem Aufruf den
gesamten Bestand laden.**

**Vorschlag:** Ein großzügiger Limiter (etwa 60/Stunde je IP) — großzügig genug, dass
Forschende und Behörden nichts merken, eng genug, dass niemand den Endpunkt als
Lastwerkzeug benutzt. Zusätzlich klären, ob ein CDN davorsteht; wenn ja, ist der Limiter
zweitrangig.

### BF-43 · Formelinjektion im Datensatz möglich — niedrig

**Reproduktion:** Restaurantname auf `=cmd|.\\x22 "Test", Semi;kolon` gesetzt, dann
`/open/dataset.csv` abgerufen:
```
Name im CSV: '=cmd|.\\x22 "Test", Semi;kolon'
Spaltenzahl der Zeile: 21 ✓
Formel-Präfix (=) steht unverändert: True
```

**Die CSV-Struktur hält** — Anführungszeichen und Kommas werden korrekt maskiert, die
Zeile behält ihre 21 Spalten. Was nicht passiert: Ein führendes `=`, `+`, `-` oder `@`
wird nicht entschärft. Excel und LibreOffice interpretieren solche Zellen als **Formel**;
`=HYPERLINK("http://…","Klicken")` oder ein `cmd|`-Konstrukt sind die bekannten Varianten.

**Warum das trotzdem *niedrig* ist:** Der Name muss durch die Moderation. Nach der
BF-24-Reparatur gibt es keinen Weg mehr, ihn ohne Admin-Sichtung in die Tabelle zu
bekommen.

**Warum es trotzdem im Register steht:** Die Hürde ist ein Mensch, der eine Liste
durchklickt — und `=HYPERLINK(...)` sieht in einem Formularfeld nicht nach einem Angriff
aus. Die Zielgruppe des Datensatzes sind Ministerien und Forschende, also genau die
Leute, die ihn in Excel öffnen. Für ein Projekt, dessen Wert an der Glaubwürdigkeit
seiner Zahlen hängt, ist das ein schlechter Ort für ein Restrisiko.

**Vorschlag:** Zellen, die mit `=`, `+`, `-` oder `@` beginnen, beim Schreiben ein
führendes `'` voranstellen — der übliche Weg, eine Zeile in der Anzeige unverändert und
in der Auswertung harmlos zu halten. Betrifft `name`, `city` und `website`.

## Hinweise ohne Fehlerstatus

- **OF-01 der Spec bleibt offen:** Ob auf Cloudways ein Reverse Proxy oder CDN vor der
  Anwendung steht, ist aus dem Repository nicht zu beantworten und entscheidet, wie
  schwer BF-42 wiegt. Betreiberfrage.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Drei in `tests/Functional/Controller/Open/OpenDataControllerTest.php`:
`testAk05CsvBeginntOhneBom`, `testAk07DatensatzFuehrtEinundzwanzigSpalten`,
`testAk11CacheHeaderBleibtOeffentlichTrotzSession`.

Der zweite prüft die Spaltenzahl **und** dass `email` und `phone` nicht darunter sind.
Das ist bewusst redundant zum vorhandenen `testDatasetContainsNoContactDetails`: Jener
sucht nach Werten, dieser nach Spaltennamen. Ein Feld, das versehentlich hinzukommt und
in den Fixtures zufällig leer ist, fällt nur dem zweiten auf.

**Suite: 358 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B10`. B17 geht auf `approved`; die drei Befunde stehen in
`features/befunde.md`.
