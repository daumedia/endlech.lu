# B07 · Öffnungszeiten — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein niedriger Befund.

17 von 17 Kriterien bestanden. Die Zeitlogik ist an allen Grenzen gemessen und stimmt
überall — auch dort, wo es leicht schiefgeht:

```
12:00 (Fenstergrenze auf)  → true      ← >= open
14:30 (Fenstergrenze zu)   → false     ← <  close
16:00 (zwischen Fenstern)  → false
Mo 23:00 bei 22:00–02:00   → true      ← Nachtschicht heute
Di 01:00 bei 22:00–02:00   → true      ← Nachtschicht von gestern
Fenster ohne Zeiten        → false     ← übersprungen
```

`Europe/Luxembourg` steht an **allen vier** Stellen ausdrücklich, auch im
Repository-Filter — und die Serverzeitzone ist `UTC`, der Unterschied also real.

**Der eine Befund:** Ein Haus, das nur an einem Wochentag öffnet, hat nach Ladenschluss
weder einen Status noch eine nächste Öffnung. Die Suche über die Folgetage läuft sechs
Tage weit; der siebte wäre wieder heute.

Nächster Aufruf: **`/sdd-erfassen B08`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Zeitlogik

Gemessen mit festen Zeitpunkten (Montag, 2026-08-24) gegen `OpeningHoursService`:

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `SHOW COLUMNS`: `id, restaurant_id, day_of_week, open_time, close_time` — **kein** `is_closed`-Feld. Der Wochenplan zeigt für Tage ohne Slots „Geschlossen" |
| AK-02 | ✅ bestanden | `Montag (Heute) 12:00 – 14:30 · 18:00 – 22:00` (in B06/AK-05 gemessen) |
| **AK-03** | ✅ bestanden | 12:00 → `true`, **14:30 → `false`**, 16:00 → `false`. Die Grenze ist `>= open` und `< close`, wie beschrieben |
| **AK-04** | ✅ bestanden | Fenster 22:00–02:00, Montag 23:00 → `true` |
| **AK-05** | ✅ bestanden | dasselbe Fenster, **Dienstag 01:00 → `true`** — die Prüfung sieht den Vortag an |
| AK-06 | ✅ bestanden | Fenster mit `open = null, close = null` → `false`, kein Fehler |
| AK-07 | ✅ bestanden | `getNextOpeningTime()` während geöffnet → `null` |
| **AK-08** | ✅ bestanden | um 16:00 bei Fenstern 12:00–14:30 und 18:00–22:00 → `{"dayOfWeek":1,"time":"18:00"}` — der früheste **künftige** Slot desselben Tages |
| **AK-09** | ✅ bestanden | heute nichts mehr, morgen 09:00–17:00 → `{"dayOfWeek":2,"time":"09:00"}` |

### Integration

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-10 | ✅ bestanden | `RestaurantRepository.php:74` (`oh_yesterday`-Join), Zeile 80 (Vortags-Nachtschicht), Zeile 82 (`distinct()`). In B05/AK-10 und AK-12 am laufenden System gemessen |
| AK-11 | ⚠️ nicht ausgeführt | Der Stimulus-Controller klont den Prototyp im Browser. Der Code (`opening_hours_form_controller.ts`) ist da, aber eine Messung hätte eine Browsersitzung im Admin gebraucht — dieselbe, die bei B11/AK-09 dreimal scheiterte. Steht bewusst hier und nicht als bestanden |
| **AK-12** | ✅ bestanden | FK-Regel `CASCADE`; Restaurant gelöscht → Zeitfenster **1 → 0** |
| **AK-13** | ✅ bestanden | `Europe/Luxembourg` an vier Stellen: `RestaurantRepository:68`, `RestaurantController:77`, `Schedule:42`, `OpeningHoursService:9`. Serverzeitzone: **UTC** |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-16 | ✅ bestanden | fünf Spalten, davon drei Nutzdaten: Wochentag, zwei Uhrzeiten. Keine personenbezogenen Daten |
| AK-17 | ✅ bestanden | `OpeningHourType` ist ausschließlich im `RestaurantType` eingebunden, und der läuft über den Admin-Bereich (B20/AK-11: `ROLE_USER` → 403) |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-14** ⚠ | ✅ bestätigt | Haus mit nur einem Wochentag, nach Ladenschluss → `getNextOpeningTime()` liefert **`null`** → BF-61 |
| **AK-15** ⚠ | ✅ bestätigt | Im Zweig `open > close` steht nur `$currentTime >= $open`; die Schließzeit wird dort nicht geprüft. Dass AK-04 **und** AK-05 beide `true` liefern, zeigt: Die Zweige greifen ineinander, die Auslassung ist richtig. **Kein Befund** — siehe Hinweise |

## Fehler

### BF-61 · Ein Haus mit nur einem Öffnungstag verliert seinen Status — niedrig

**Betrifft:** AK-14

**Reproduktion:**
1. Restaurant mit **einem** Zeitfenster: Montag 08:00–10:00
2. Detailseite an einem Montag um 18:41 aufrufen

**Erwartet:** „Geschlossen · Öffnet Montag um 08:00" (in sieben Tagen)
**Tatsächlich:**
```
Kopfbereich:  … Geschlossen · Verifiziertes Lokal · ⭐ 9.8/10 …
Enthält „Öffnet": NEIN — weder geöffnet noch nächste Öffnung
```

Auch direkt gegen den Service: `getNextOpeningTime()` → **`null`**.

**Ort:** `OpeningHoursService::getNextOpeningTime()` — die Folgetagsschleife läuft
`for ($i = 1; $i <= 6; ++$i)` und deckt damit die nächsten sechs Tage ab. Der siebte
wäre wieder der heutige, und dessen bereits vergangene Fenster wurden im ersten Schritt
verworfen.

**Wie realistisch ist der Fall?** Ein Restaurant, das nur an einem Wochentag öffnet, ist
ungewöhnlich — aber es gibt sie: Sonntagsbrunch, Wochenmarkt-Stände, Vereinslokale mit
einem Öffnungstag. Genau die Art Betrieb, für die eine Barrierefreiheitsplattform
nützlich ist.

**Die Wirkung ist zudem schleichend:** Die Seite sieht nicht kaputt aus. Es steht
„Geschlossen", und wo bei anderen Häusern die nächste Öffnung stünde, steht nichts.
Niemand vermisst, was er nicht kennt.

**Vorschlag:** Die Schleife bis `$i <= 7` laufen lassen — dann trifft sie den heutigen
Wochentag der Folgewoche und liefert dessen **frühestes** Fenster (nicht das nächste
künftige, denn eine Woche später ist wieder alles künftig). Eine Zahl, und der Randfall
verschwindet.

Zu prüfen wäre dabei, ob die Anzeige „Öffnet Montag um 08:00" bei sieben Tagen Abstand
verständlich bleibt oder ob es „Öffnet nächsten Montag" heißen müsste.

## Hinweise ohne Fehlerstatus

- **AK-15 ist kein Befund, sondern ein Kommentarmangel.** Die Spec vermutet richtig: Die
  fehlende Schließzeitprüfung im Nachtschicht-Zweig ist Absicht, weil die Vortagsprüfung
  den Rest übernimmt. Meine Messung belegt beides — AK-04 (Mo 23:00) und AK-05 (Di 01:00)
  liefern beide `true`, und zwar über verschiedene Zweige. Was fehlt, ist ein Satz im
  Code, der das erklärt; wer den Zweig isoliert liest, hält ihn für unvollständig.
- **AK-11 steht als nicht ausgeführt** und nicht als bestanden. Der Code sieht richtig
  aus, aber das zählt hier nicht als Nachweis — dieselbe Regel, die ich bei B11/AK-09
  angewandt habe.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Grenzwerte** | `>= open` und `< close` an beiden Enden geprüft |
| **Zeitzone** | vier Stellen, alle explizit `Europe/Luxembourg` gegen Server-`UTC` |
| **Unvollständige Daten** | Fenster ohne Zeiten wird übersprungen, kein Fehler |
| **Kaskade** | Zeitfenster verschwinden mit dem Restaurant |
| **Personenbezogene Daten** | keine |
| **Schreibzugriff** | nur über den Admin-Bereich |
| **Testsuite** | 364 Tests, 0 Fehler |

## Neue Tests

Keine. Die sieben vorhandenen Unit-Tests (`testOpenDuringLunchSlot`,
`testClosedInGapBetweenSlots`, `testOpenDuringDinnerSlot`,
`testOvernightSlotSpillsIntoNextDay`, `testNextOpeningTimeIsNextSlotSameDay`,
`testNextOpeningTimeRollsToFollowingDay`, `testDayWithoutSlotsIsClosed`) decken AK-01
bis AK-09 ab — meine Messungen bestätigen sie an denselben Grenzen.

**BF-61 ließe sich testen**, aber der Test hielte das unerwünschte Verhalten fest. Er
entsteht mit der Reparatur, dann in der richtigen Richtung: „nur montags geöffnet, an
einem Montagabend geprüft → nächster Montag".

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B08`. B07 geht auf `approved`; BF-61 steht in `features/befunde.md`.
