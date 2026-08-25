# B07 · Öffnungszeiten — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein Haus kann pro Wochentag **beliebig viele** Zeitfenster haben (Mittag und Abend), und
die Anwendung beantwortet daraus zwei Fragen: Ist jetzt geöffnet? Und wenn nicht, wann
wieder? Nachtschichten über Mitternacht werden mitgerechnet.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B20 | rekonstruiert | Pflege im Restaurantformular |

## User Stories

- **US-01** · Als Gast möchte ich sehen, ob jetzt geöffnet ist.
- **US-02** · Als Gast möchte ich wissen, wann wieder geöffnet wird.
- **US-03** · Als Admin möchte ich Mittag und Abend getrennt eintragen.

## Nicht im Scope

- Feiertage, Betriebsferien, Ausnahmen — nicht vorgesehen
- Zeitzonen außer `Europe/Luxembourg`

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Tag hat **keine** Zeitfenster, wenn er betrachtet wird,
  dann gilt er als geschlossen — es gibt kein eigenes „geschlossen"-Feld.
- **AK-02** · Angenommen, ein Tag hat zwei Fenster, wenn der Wochenplan angezeigt wird,
  dann stehen beide als `12:00 – 14:30 · 18:00 – 22:00`.
- **AK-03** · Angenommen, die aktuelle Zeit liegt in einem Fenster, wenn `is_open_now`
  ausgewertet wird, dann ist das Ergebnis `true` — Grenze: `>= open` und `< close`.
- **AK-04** · Angenommen, ein Fenster geht über Mitternacht (`open > close`, etwa
  22:00–02:00), wenn nach 22:00 gefragt wird, dann gilt es als geöffnet.
- **AK-05** · Angenommen, dasselbe Fenster wird um 01:00 des Folgetages geprüft, wenn
  ausgewertet wird, dann gilt es weiterhin als geöffnet — die Prüfung sieht auch den
  **Vortag** an.
- **AK-06** · Angenommen, ein Fenster hat keine Öffnungs- oder Schließzeit, wenn es
  ausgewertet wird, dann wird es übersprungen.
- **AK-07** · Angenommen, ein Haus ist geöffnet, wenn `getNextOpeningTime()` gerufen
  wird, dann liefert es `null`.
- **AK-08** · Angenommen, ein Haus ist geschlossen und öffnet heute noch, wenn
  `getNextOpeningTime()` gerufen wird, dann liefert es den **frühesten künftigen** Slot
  des heutigen Tages.
- **AK-09** · Angenommen, heute öffnet nichts mehr, wenn `getNextOpeningTime()` gerufen
  wird, dann wird der erste der **nächsten sechs** Tage mit Fenstern geliefert, jeweils
  dessen frühester Slot.
- **AK-10** · Angenommen, `?open=1` steht in der Restaurantliste, wenn sie lädt, dann
  greift dieselbe Nachtschicht-Logik in SQL — mit `distinct()`, damit ein Haus mit
  mehreren Fenstern einmal erscheint.
- **AK-11** · Angenommen, ein Admin bearbeitet ein Restaurant, wenn er einem Tag ein
  Fenster hinzufügt, dann klont der Stimulus-Controller den Prototyp, vergibt einen
  gemeinsamen Index und setzt den `dayOfWeek` des neuen Slots.
- **AK-12** · Angenommen, ein Restaurant wird gelöscht, wenn danach nachgesehen wird,
  dann sind seine Zeitfenster mit gelöscht (`cascade`, `orphanRemoval`, FK CASCADE).
- **AK-13** · Angenommen, irgendeine Zeitberechnung läuft, wenn die Zeitzone geprüft
  wird, dann ist es `Europe/Luxembourg` — an **jeder** Stelle, auch im
  Repository-Filter.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-14** ⚠ · Angenommen, ein Haus öffnet **nur** an einem Wochentag und dieser Tag
  ist heute und bereits vorbei, wenn `getNextOpeningTime()` gerufen wird, dann liefert
  es **`null`** — obwohl in sieben Tagen wieder geöffnet wird.
  *(So verhält sich der Code heute: Die Folgetagsschleife läuft `for ($i = 1; $i <= 6;
  ++$i)`, deckt also die nächsten sechs Tage ab. Der siebte wäre wieder der heutige, und
  dessen bereits vergangene Fenster wurden zuvor verworfen. Folge: Auf der Detailseite
  steht bei einem solchen Haus weder „geöffnet" noch eine nächste Öffnung.)*

- **AK-15** ⚠ · Angenommen, ein Fenster geht über Mitternacht, wenn `isOpenAt()` den
  **heutigen** Tag prüft, dann genügt `$currentTime >= $open` — die Schließzeit wird
  dabei **nicht** geprüft.
  *(So verhält sich der Code heute: Im Zweig `$open > $close` steht nur
  `if ($currentTime >= $open) return true;`. Das ist korrekt, solange die Schließzeit
  am Folgetag liegt — dieser Fall wird von der Vortagsprüfung abgedeckt. Die Zweige
  greifen also ineinander; die Auslassung ist Absicht, aber ohne Kommentar schwer als
  solche zu erkennen.)*

### Datenschutz und Missbrauchsschutz

- **AK-16** · Angenommen, ein Zeitfenster wird betrachtet, wenn nach personenbezogenen
  Daten gesucht wird, dann enthält es **keine** — Wochentag und zwei Uhrzeiten.
- **AK-17** · Angenommen, Zeitfenster werden gepflegt, wenn geprüft wird, wer das darf,
  dann nur `ROLE_ADMIN` über das Restaurantformular (B20).

## Edge Cases

- **EC-01** · Seit Issue #81 gibt es **keinen** UNIQUE-Constraint auf
  `(restaurant, dayOfWeek)` mehr und **kein** Feld `isClosed` — beides wurde in
  `Version20260619000000` entfernt.
- **EC-02** · Uhrzeitvergleiche laufen als String im Format `H:i:s` — lexikografisch
  gleichbedeutend mit chronologisch, solange das Format eingehalten wird.
- **EC-03** · `getNextOpeningTime()` nimmt einen optionalen `$now` — eingeführt, damit
  der Unit-Test feste Zeitpunkte prüfen kann.
- **EC-04** · Die Sortierung `dayOfWeek ASC, openTime ASC` steht im Mapping, nicht in
  der Abfrage.

## Fehlbestand

- **FB-01 · Nächste Öffnung findet den siebten Tag nicht.** Siehe AK-14.
- **FB-02 · Keine Feiertage und keine Ausnahmen.** Ein Haus, das am 25. Dezember
  geschlossen hat, zeigt trotzdem „geöffnet".
- **FB-03 · Keine Plausibilitätsprüfung beim Anlegen.** Überlappende oder identische
  Fenster am selben Tag werden nicht bemängelt.
- **FB-04 · Öffnungszeiten fehlen im Vorschlags-Wizard** (B11) und werden beim
  Genehmigen daher nie übernommen (B21/AK-13).
- **FB-05 · Öffnungszeiten fehlen im offenen Datensatz** (B17) — obwohl sie für eine
  Nachnutzung naheliegend wären.

## Offene Fragen

- **OF-01** · Soll die Folgetagsschleife auf sieben Tage erweitert werden (AK-14)? Die
  Änderung ist ein Zeichen; sie braucht aber einen Test, der den Fall abdeckt. —
  Betreiber
  **Entschieden 2026-08-25:** Ja, umgesetzt (BF-61, 2026-08-25). Die Schleife läuft bis 7; zwei Tests decken den Fall ab, einer davon mit Gegenprobe.

- **OF-02** · Sollen Feiertage erfasst werden (FB-02)? In Luxemburg sind das elf feste
  Tage plus Ostermontag und Pfingstmontag. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Mehrere Fenster je Tag | ja, seit Issue #81 | Mittag und Abend sind der Normalfall in der Gastronomie |
| 2 | „Geschlossen" ohne eigenes Feld | keine Slots = geschlossen | ein `isClosed` neben leeren Slots wäre ein zweiter, widersprechbarer Zustand |
| 3 | Nachtschicht über zwei Zweige | heute + Vortag | ein Fenster 22:00–02:00 gehört fachlich zum Vortag |
| 4 | Zeitzone fest verdrahtet | `Europe/Luxembourg` | die Anwendung bedient genau ein Land |
| 5 | `$now` als optionaler Parameter | ja | ohne ihn wäre `getNextOpeningTime()` nicht testbar |
| 6 | Filterlogik in SQL statt in PHP | Repository | sonst müssten alle Häuser geladen und gefiltert werden |
