# B18 · Finanzposten & Kennzahl-Snapshots — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Der Betreiber pflegt Einnahmen und Ausgaben von Hand unter `/{locale}/admin/finanzen`;
diese Zahlen speisen den Finanzblock der Transparenzseite. Monatlich friert ein Lauf
den vollständigen Kennzahlenstand in einem `MetricSnapshot` ein — das ist die einzige
Quelle für jeden Verlauf.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B19 | rekonstruiert | Rollenschranke und Shell |

Umgekehrt hängen B16 und B17 daran.

## User Stories

- **US-01** · Als Betreiber möchte ich Ausgaben und Einnahmen erfassen.
- **US-02** · Als Betreiber möchte ich sehen, wann zuletzt gepflegt wurde.
- **US-03** · Als Betreiber möchte ich einen Monatswert von Hand nachholen, wenn der
  Cron ausgefallen ist.
- **US-04** · Als Fördergeber möchte ich einen Verlauf sehen, der sich nicht
  rückwirkend ändert.

## Nicht im Scope

- Anzeige der Zahlen → B16, B17
- Buchhaltung, Belege, Rechnungen — bewusst nicht erfasst

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Admin ruft `/{locale}/admin/finanzen` auf, wenn die Seite
  lädt, dann sieht er alle Posten, die Summen je Richtung, den Zeitpunkt der letzten
  Pflege und den zuletzt geschriebenen Snapshot.
- **AK-02** · Angenommen, `?type=expense` steht in der Adresse, wenn die Seite lädt,
  dann erscheinen nur Ausgaben; ein unbekannter Wert wird verworfen.
- **AK-03** · Angenommen, ein Posten wird angelegt oder geändert, wenn gespeichert wird,
  dann **wird der Kennzahlen-Cache verworfen** und die öffentliche Seite zeigt sofort
  den neuen Stand.
- **AK-04** · Angenommen, eine Kategorie wird gewählt, wenn der Posten gespeichert wird,
  dann folgt die Richtung (`type`) automatisch aus der Kategorie — es gibt **keinen**
  `setType()`.
- **AK-05** · Angenommen, eine Kategorie führt keine Stückzahl, wenn sie gewählt wird,
  dann wird ein zuvor gesetztes `quantity` geleert.
- **AK-06** · Angenommen, ein Betrag wird eingegeben, wenn er gespeichert ist, dann
  steht er mit **zwei** Nachkommastellen in der Datenbank — unabhängig davon, ob das
  Formular `42.5` oder `42.50` lieferte.
- **AK-07** · Angenommen, ein Betrag wird betrachtet, wenn sein Vorzeichen geprüft wird,
  dann ist er **immer positiv** — die Richtung steckt in `type`.
- **AK-08** · Angenommen, ein Posten wird gelöscht, wenn das CSRF-Token
  `delete-finance-{id}` fehlt oder falsch ist, dann bleibt er bestehen.
- **AK-09** · Angenommen, ein Admin drückt „Snapshot erstellen", wenn das CSRF-Token
  `metric-snapshot` stimmt, dann entsteht ein Snapshot und es erscheint der festgehaltene
  Monat in der Erfolgsmeldung.
- **AK-10** · Angenommen, der Befehl `app:metrics:snapshot` läuft ohne Argument, wenn er
  durchläuft, dann hält er den **abgeschlossenen Vormonat** fest — nicht den laufenden.
- **AK-11** · Angenommen, für einen Monat existiert bereits ein Snapshot, wenn der
  Befehl ohne `--force` erneut läuft, dann wird **nichts** geschrieben und die Rückgabe
  meldet `created: false`.
- **AK-12** · Angenommen, ein Snapshot wird geschrieben, wenn die Quelle geprüft wird,
  dann kommen die Zahlen aus `computeAll()` — **ungecacht**, damit kein bis zu eine
  Stunde alter Zwischenstand eingefroren wird.
- **AK-13** · Angenommen, zwei Snapshots für denselben Monat werden versucht, wenn die
  Datenbank betrachtet wird, dann verhindert der **UNIQUE**-Index auf `captured_for`
  einen zweiten Eintrag.
- **AK-14** · Angenommen, ein Snapshot wird betrachtet, wenn sein Inhalt geprüft wird,
  dann trägt er typisierte Spalten für die Verlaufsgrafiken **und** ein `payload`-JSON
  mit der vollständigen Momentaufnahme.
- **AK-15** · Angenommen, Einnahmen unterliegen der Quartalssperre, wenn ein Snapshot
  geschrieben wird, dann wird die Summe **trotzdem** gespeichert — direkt aus dem
  Repository, nicht aus dem gesperrten Ergebnis-Array.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-16** ⚠ · Angenommen, ein Admin drückt „Snapshot erstellen", wenn für den Vormonat
  bereits ein Snapshot existiert, dann wird dieser **überschrieben** — mit den Zahlen von
  **heute**.
  *(So verhält sich der Code heute: `AdminFinanceController::snapshot()` ruft
  `capture(null, true)`; `null` löst auf `defaultMonth()` = Vormonat auf, `true`
  erzwingt das Überschreiben, und `fill()` schreibt `computeAll()` — den aktuellen
  Stand. Folge: Ein zweiter Klick im Folgemonat ersetzt einen historischen Wert durch
  einen jüngeren. Damit ist genau die Eigenschaft verletzt, für die die Entity
  überhaupt existiert: „Ein aus den heutigen Daten zurückgerechneter Verlauf änderte
  sich rückwirkend, sobald jemand einen Eintrag bearbeitet — als Beleg gegenüber einem
  Ministerium wertlos.")*

- **AK-17** ⚠ · Angenommen, der Zeitplan in `src/Schedule.php` ist konfiguriert, wenn
  auf Produktion geprüft wird, ob er feuert, dann tut er es **nicht**.
  *(So verhält sich der Bestand: Symfony Scheduler braucht
  `messenger:consume scheduler_default`; Produktion läuft mit
  `MESSENGER_TRANSPORT_DSN=sync://` und ohne Worker. Der reale Auslöser ist ein
  Cron-Eintrag auf `app:metrics:snapshot`, dokumentiert im README. Der Zeitplan im Code
  ist damit eine Attrappe, die einen falschen Eindruck von Verlässlichkeit erzeugt.)*

- **AK-18** ⚠ · Angenommen, ein Finanzposten wird geändert oder gelöscht, wenn später
  gefragt wird, wer das war und was vorher dastand, dann lässt sich beides nicht
  beantworten.
  *(Kein Audit-Log, keine Versionierung. Für Zahlen, die öffentlich als Beleg dienen,
  wiegt das schwerer als in den übrigen Verwaltungsbereichen.)*

### Datenschutz und Missbrauchsschutz

- **AK-19** · Angenommen, ein Finanzposten wird betrachtet, wenn nach personenbezogenen
  Daten gesucht wird, dann enthält er **keine** — es gibt kein Feld für Vertragspartner,
  Restaurant oder Rechnungsnummer. Nur der Freitext `note` könnte welche aufnehmen.
- **AK-20** · Angenommen, ein Posten wird angelegt, wenn geprüft wird, was davon
  öffentlich wird, dann sind es die **aggregierten Summen je Kategorie** auf `/open` und
  in `/open.json` — nicht die Einzelposten.
- **AK-21** · Angenommen, alle schreibenden Endpunkte werden geprüft, wenn nach CSRF
  gesucht wird, dann tragen `delete` und `snapshot` eigene Token; `new` und `edit` sind
  Symfony-Formulare mit eingebautem Schutz.

## Edge Cases

- **EC-01** · Die Spalte heißt `entry_date`, nicht `date` — `date` ist in MySQL
  reserviert.
- **EC-02** · `type` ist redundant zu `category->type()`, aber indiziert — für die
  SQL-Aggregation.
- **EC-03** · `capture()` normalisiert jeden übergebenen Monat auf
  `first day of this month, 00:00`.
- **EC-04** · Ein `--month=YYYY-MM` in der Vergangenheit schreibt **heutige** Zahlen in
  einen alten Monat — dieselbe Eigenschaft wie AK-16, nur bewusst ausgelöst.

## Fehlbestand

- **FB-01 · Der Snapshot-Knopf ist nicht gegen Überschreiben gesichert.** Siehe AK-16.
  Weder Rückfrage noch Hinweis, dass ein vorhandener Wert ersetzt wird.
- **FB-02 · Kein Audit-Log für Finanzdaten.** Siehe AK-18.
- **FB-03 · Der Zeitplan im Code feuert nicht.** Siehe AK-17. Es fehlt ein Hinweis im
  Code selbst; die Einschränkung steht nur in `CLAUDE.md` und im README.
- **FB-04 · Keine Überwachung des Cron-Laufs.** Fällt er aus, fällt es erst auf, wenn
  jemand die Verlaufsgrafik betrachtet — und dann ist der Monat unwiederbringlich
  verloren (die heutigen Zahlen wären nicht der damalige Stand).
- **FB-05 · Kein Vier-Augen-Prinzip und keine Freigabestufe** für Zahlen, die
  veröffentlicht werden.
- **FB-06 · Der Freitext `note` wird ungeprüft übernommen** und könnte
  personenbezogene Angaben aufnehmen, die über die Aggregation zwar nicht öffentlich
  werden — aber gespeichert bleiben.

## Offene Fragen

- **OF-01** · Soll der Snapshot-Knopf `force: false` verwenden und einen vorhandenen
  Monat unangetastet lassen (AK-16)? Dann bräuchte es einen zweiten, ausdrücklich
  benannten Weg zum Überschreiben. — Betreiber
- **OF-02** · Ist der Cron-Eintrag auf Produktion tatsächlich eingerichtet (AK-17)? —
  Betreiber, vor dem nächsten Monatswechsel
- **OF-03** · Soll `src/Schedule.php` bleiben, obwohl es nicht feuert? Ein Kommentar im
  Code wäre das Mindeste. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Snapshot-Entity statt Rückrechnung | Entity | ein zurückgerechneter Verlauf änderte sich rückwirkend und wäre als Beleg wertlos |
| 2 | Vormonat statt laufender Monat | Vormonat | sonst endete jeder Verlauf mit einem künstlichen Einbruch |
| 3 | Idempotenz auf DB-Ebene | `UNIQUE` auf `captured_for` | zwei Auslöser (Scheduler, Cron) dürfen nicht doppelt schreiben |
| 4 | Kein `setType()` | Richtung folgt der Kategorie | eine Ausgabe unter einer Einnahmekategorie wäre in der veröffentlichten Summe nicht mehr als Fehler erkennbar |
| 5 | Beträge immer positiv | ja | die Richtung steckt in `type` |
| 6 | `setAmount()` normalisiert auf zwei Stellen | ja | `MoneyType` liefert `"42.5"`, die DB `"42.50"` |
| 7 | Keine Felder für Vertragspartner | bewusst | „was nicht erfasst ist, kann nicht versehentlich veröffentlicht werden" |
| 8 | Knopf im Admin zusätzlich zum Cron | ja | eine ausgefallene Historie bliebe sonst unbemerkt und ließe sich nicht rückwirkend erzeugen |
| 9 | Snapshot rechnet ungecacht | `computeAll()` | ein eingefrorener Zwischenstand wäre kein definierter Stand |
