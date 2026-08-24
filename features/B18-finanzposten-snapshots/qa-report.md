# B18 · Finanzposten & Kennzahl-Snapshots — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein mittlerer und ein niedriger Befund.

25 von 25 Kriterien bestanden, 4 von 4 Edge Cases. Eines davon (AK-05) verhält sich
**besser** als die Rekonstruktion behauptet; die Spec ist entsprechend berichtigt.

Das Feature trägt die Zahlen, mit denen sich das Projekt gegenüber Fördergebern
ausweist, und der Datenschutzteil hält: Es gibt kein Feld für Vertragspartner,
Restaurant oder Rechnungsnummer, und veröffentlicht werden nur Summen je Kategorie —
nie Einzelposten. Die Quartalssperre ist strukturell und nicht kosmetisch, an beiden
Enden nachgemessen.

**Der eine Befund, der zählt:** Der Knopf „Snapshot erstellen" überschreibt einen
vorhandenen Snapshot mit den Zahlen von heute. Ich habe `restaurant_count` auf `999`
gesetzt und den Knopf gedrückt — danach stand dort `11`. Damit ist genau die
Eigenschaft verletzt, für die die Entity überhaupt existiert: ein Verlauf, der sich
nicht rückwirkend ändert.

Nächster Aufruf: **`/sdd-erfassen B11`**. Die Erfassung läuft weiter.

## Eine Korrektur an der Rekonstruktion

**AK-05 beschreibt nur die halbe Wahrheit.** Die Spec sagt: *„Angenommen, eine Kategorie
führt keine Stückzahl, wenn sie gewählt wird, dann wird ein zuvor gesetztes `quantity`
geleert."*

Gemessen: `category=hosting` mit `quantity=7` → **HTTP 422**, der Eintrag entsteht gar
nicht. Der Grund steht in `FinanceEntryType.php:110`: ein Callback-Validator mit der
Meldung `finance.quantity_not_allowed`, gebunden an `->atPath('quantity')`.

Das Entity-Verhalten aus der Spec stimmt trotzdem — `setCategory()` räumt `quantity`
weg (`FinanceEntry.php:128–129`). Nur greift die Formularvalidierung vorher.

**Das ist die bessere Lösung**, und sie gehört in die Spec: Ein 422 mit Feldfehler sagt
dem Admin, dass seine Eingabe nicht passt. Ein stilles Leeren hätte ihn glauben lassen,
die Stückzahl sei gespeichert.

## Akzeptanzkriterien im Einzelnen

### Verwaltung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | „Ausgaben gesamt 1.322,70 €", „Einnahmen gesamt 650,00 €", „Letzter Snapshot —", Tabelle mit allen Posten |
| AK-02 | ✅ bestanden | `?type=expense` → 29 Zeilen · `?type=income` → 2 · `?type=unsinn` → 31 (alle). Deckt sich mit der Datenbank: 29 Ausgaben, 2 Einnahmen |
| **AK-03** | ✅ bestanden | Posten über 42,50 € angelegt → `/open.json` zeigt **1322,7 → 1365,2** ohne Cache-Leerung. Die Invalidierung greift sofort |
| AK-04 | ✅ bestanden | `category=hosting` → `type=expense` in der Datenbank. `FinanceEntry.php:123`: `$this->type = $category->type()` |
| **AK-05** | ✅ bestanden | siehe die Korrektur oben — 422 statt stillem Leeren; mit `inclusion_box_materials` geht `quantity=3` durch |
| AK-06 | ✅ bestanden | Eingabe `42.5` → Datenbank `42.50` |
| AK-07 | ✅ bestanden | Betrag positiv, Richtung in `type` |
| AK-08 | ✅ bestanden | `_token=falsch` → Eintrag bleibt (1); gültiges Token → gelöscht (0) |
| AK-21 | ✅ bestanden | `delete-finance-{id}` (Zeile 103) und `metric-snapshot` (Zeile 127) als eigene Token; `new` und `edit` sind Symfony-Formulare |

### Snapshots

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-09** | ✅ bestanden | mit gültigem Token: *„Snapshot für 2026-07 geschrieben."*; mit `_token=falsch`: *„Ungültiges CSRF-Token."*, Anzahl **2 → 2** |
| **AK-10** | ✅ bestanden | Befehl ohne Argument am 2026-08-24 → `captured_for=2026-07-01`. Der abgeschlossene Vormonat, nicht der laufende |
| AK-11 | ✅ bestanden | zweiter Lauf ohne `--force` → Warnung, Anzahl bleibt bei **1** |
| AK-12 | ✅ bestanden | `MetricSnapshotService.php:58`: `$this->fill($snapshot, $this->stats->computeAll())` — die ungecachte Variante |
| **AK-13** | ✅ bestanden | `SHOW INDEX`: `UNIQ_metric_snapshot_month` auf `captured_for`. Direkter Einfügeversuch: `ERROR 1062 Duplicate entry '2026-07-01'` |
| AK-14 | ✅ bestanden | typisierte Spalten (`restaurants=11 verified=3 score=5.09 expenses=1322.70 income=650.00`) **und** `payload` mit `['impact','finance','platform']` |
| **AK-15** | ✅ bestanden | Snapshot: `total_income=650.00`. `/open.json`: `incomeVisible: false`, `totalIncome: null`, `income: []`, `incomeVisibleFrom: "2026-10-01"`. Die Zahl steht in der Historie und **nicht** in der öffentlichen Antwort |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-19 | ✅ bestanden | Spalten: `id, entry_date, type, category, amount, quantity, note, created_at, updated_at`. Kein Feld für Vertragspartner, Restaurant oder Rechnungsnummer |
| AK-20 | ✅ bestanden | `/open.json` liefert je Kategorie `{category, label, transKey, emoji, total, entries, share}` — **kein** `note`, **kein** `date`, keine Einzelposten |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-16** ⚠ | ✅ bestätigt | `restaurant_count` von Hand auf **999** gesetzt, Knopf gedrückt → danach **11** → BF-47 |
| **AK-17** ⚠ | ✅ bestätigt | `#[AsSchedule]` in `Schedule.php:22`; `deploy.sh:40` sagt ausdrücklich *„BEWUSST KEIN `pkill -f messenger:consume`… es gibt keinen Worker"*; der echte Auslöser steht in `README.md:230` als Cron-Zeile → BF-48 |
| **AK-18** ⚠ | ✅ bestätigt | **0** Spalten für Bearbeiter oder Version. Kein eigener Befund — das ist B19/FB-02 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Spalte heißt `entry_date`, Typ `date` |
| EC-02 | ✅ bestanden | `IDX_finance_entry_type_date` über `type` und `entry_date` |
| EC-03 | ✅ bestanden | `MetricSnapshotService.php:48`: `->modify('first day of this month')->setTime(0, 0)` |
| **EC-04** | ✅ bestanden | `--month=2025-03` → `captured_for=2025-03-01` mit `restaurant_count=11`, also dem **heutigen** Stand. Dieselbe Eigenschaft wie AK-16, hier bewusst ausgelöst |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **CSRF** | beide Sondertoken greifen; falsches Token ändert nichts (Löschen und Snapshot einzeln geprüft) |
| **Unbekannte Filterwerte** | `?type=unsinn` → volle Liste, keine Exception |
| **Personenbezogene Daten** | keine Spalte dafür vorhanden; nur `note` könnte welche aufnehmen |
| **Was öffentlich wird** | ausschließlich Summen je Kategorie |
| **Quartalssperre** | strukturell — die Beträge stehen nicht im Ergebnis-Array, nicht nur unsichtbar im Template |
| **Testsuite** | 362 Tests, 0 Fehler |

## Fehler

### BF-47 · Der Snapshot-Knopf überschreibt Geschichte — mittel

**Betrifft:** AK-16 · und EC-04 als bewusste Variante

**Reproduktion:**
1. Snapshot für den Vormonat anlegen
2. `restaurant_count` von Hand auf einen erkennbaren Wert setzen (hier `999`)
3. Im Admin „Jetzt festhalten" drücken

**Erwartet:** ein Hinweis, dass für diesen Monat bereits ein Snapshot existiert
**Tatsächlich:**
```
vorher:  restaurant_count=999  captured_for=2026-07-01
Meldung: Snapshot für 2026-07 geschrieben.
nachher: restaurant_count=11   captured_for=2026-07-01
```

**Ort:** `AdminFinanceController.php:133` — `$snapshots->capture(null, true)`. Das `null`
löst auf `defaultMonth()` (Vormonat) auf, das `true` erzwingt das Überschreiben, und
`fill()` schreibt `computeAll()` — also die Zahlen von heute.

**Warum das an dieser Stelle mehr wiegt als anderswo:** Die Entity `MetricSnapshot`
existiert ausschließlich, weil ein zurückgerechneter Verlauf wertlos wäre. Die
Begründung steht in `CLAUDE.md`, und sie ist gut:

> *„Ein aus den heutigen Daten zurückgerechneter Verlauf änderte sich rückwirkend,
> sobald jemand einen Eintrag bearbeitet — als Beleg gegenüber einem Ministerium
> wertlos."*

Ein Knopf, der einen historischen Wert durch einen jüngeren ersetzt, hebelt genau das
aus — und er tut es **ohne Rückfrage und mit einer Erfolgsmeldung**. Der Befehl selbst
verhält sich richtig: Ohne `--force` schreibt er nichts (AK-11 bestanden). Nur der
Knopf setzt `force` fest auf `true`.

Der Weg dahin ist gewöhnlich: Ein Admin pflegt am 3. des Monats eine Rechnung nach,
sieht den Knopf und drückt ihn. Dass er damit den Stand des Vormonats auf heute
umschreibt, sagt ihm nichts.

**Vorschlag:** `capture(null, false)` — und wenn ein Snapshot existiert, eine Meldung
statt eines stillen Überschreibens („Für 2026-07 existiert bereits ein Snapshot vom
…"). Wer wirklich überschreiben will, hat den Befehl mit `--force`. Das ist ein
bewusster Griff zur Konsole, kein Knopf im Vorbeigehen.

Zu klären wäre außerdem, ob der Knopf überhaupt den **Vormonat** meinen sollte. Wer im
August auf „Jetzt festhalten" drückt, erwartet vermutlich einen Snapshot für August —
nicht für Juli.

### BF-48 · Der Zeitplan im Code feuert nicht — niedrig

**Betrifft:** AK-17

**Nachweis:**
- `src/Schedule.php:22` trägt `#[AsSchedule]`, Zeile 39 `RecurringMessage::cron(…)`
- `.github/deploy.sh:40–42`: *„BEWUSST KEIN `pkill -f messenger:consume`: Production
  läuft mit `MESSENGER_TRANSPORT_DSN=sync://` (.env.local), es gibt also keinen Worker
  und keine Queue"*
- `README.md:230` nennt den echten Auslöser:
  `15 3 1 * * /usr/bin/php ~/public_html/bin/console app:metrics:snapshot --env=prod`

Der Zeitplan ist also eine Attrappe. **Funktional ist nichts kaputt** — der Cron macht
die Arbeit, und beides ist im Projekt dokumentiert.

**Warum es trotzdem im Register steht:** Wer `src/Schedule.php` liest, hält die
Snapshot-Historie für automatisch abgesichert. Sie ist es, aber durch etwas völlig
anderes, das an einer Stelle steht, die man beim Lesen des Codes nicht sieht. Fällt der
Cron-Eintrag bei einem Serverumzug weg, sucht die Fehlersuche zuerst am falschen Ort —
und die Historie bricht still ab, weil sie sich nicht nachträglich erzeugen lässt.

**Vorschlag:** Entweder einen Kommentar in `Schedule.php`, der auf den Cron verweist
(zwei Zeilen, kein Risiko), oder die Datei entfernen. Die erste Fassung ist besser: Der
Zeitplan wäre sofort wirksam, sobald jemand einen Worker aufsetzt.

## Hinweise ohne Fehlerstatus

- **AK-18 (kein Bearbeiter, keine Versionierung)** bekommt keine eigene Nummer — das ist
  B19/FB-02. Hier fällt es besonders auf, weil die Zahlen öffentlich als Beleg dienen:
  Wer einen Betrag ändert, verändert eine veröffentlichte Aussage, und niemand kann
  später sagen, wer es war und was vorher dastand.
- **Der Freitext `note` ist das einzige Feld, das personenbezogene Daten aufnehmen
  könnte** (AK-19). Er wird nicht veröffentlicht (AK-20), aber es gibt auch keinen
  Hinweis im Formular, dort keine Namen einzutragen.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Keine. Die vorhandenen Integrationstests (`MetricSnapshotService`: Idempotenz, `--force`,
Payload; `FinanceEntryRepository`; `CaptureMetricSnapshotCommand`) decken AK-10 bis AK-15
ab, und die neuen Befunde sind beide Verhaltensfragen, keine Regressionen:

- **BF-47** ließe sich testen — aber der Test würde festhalten, dass der Knopf
  überschreibt, und wäre nach der Reparatur falsch herum. Sinnvoll wird er erst mit der
  Reparatur, dann in der richtigen Richtung.
- **BF-48** ist eine Aussage über die Produktionsumgebung, nicht über den Code.

**Suite: 362 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B11`. B18 geht auf `approved`; BF-47 und BF-48 stehen in
`features/befunde.md`.

BF-47 ist der Kandidat für den nächsten Reparaturdurchgang: ein Parameter, und er
schützt die einzige Zahlenreihe im Projekt, die sich nicht wiederherstellen lässt.
