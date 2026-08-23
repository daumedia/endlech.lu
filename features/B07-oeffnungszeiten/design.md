# B07 · Öffnungszeiten — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Eine Entität, ein Service, eine Twig-Erweiterung, ein FormType, ein
Stimulus-Controller und ein Partial. Die Fachlogik steckt vollständig in
`OpeningHoursService` — mit einer zweiten, eigenständigen Fassung derselben Regeln in
SQL, im `open`-Zweig von `RestaurantRepository::findPaginated()`.

⚠ Diese Doppelung ist die Stelle, an der das Feature auseinanderlaufen kann: Ändert
sich die Regel, muss sie an **zwei** Orten nachgezogen werden.

## Seiten und Routen

Keine eigenen. Anzeige in B06 und B05, Pflege in B20.

## Komponentenstruktur

```
src/Entity/OpeningHour
src/Service/OpeningHoursService     isOpenNow() · isOpenAt() · getNextOpeningTime()
src/Twig/OpeningHoursExtension      Filter `is_open_now`, Funktion `next_opening_time()`
src/Form/OpeningHourType            dayOfWeek (hidden) · openTime · closeTime
assets/controllers/opening_hours_form_controller.ts
templates/partials/_opening_hours.html.twig     Wochenplan Tag 1–7
templates/admin/restaurant/_form.html.twig      nach Tag gruppiert
```

## Datenmodell

### Tabelle `opening_hour`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT | ja | |
| `day_of_week` | INT (1–7) | ja | 1 = Montag |
| `open_time` | TIME, nullable | nein | |
| `close_time` | TIME, nullable | nein | |
| `restaurant_id` | FK → `restaurant`, **CASCADE DELETE** | ja | |

**Kein** UNIQUE-Constraint auf `(restaurant, day_of_week)`, **kein** `is_closed` — beides
in `Version20260619000000` entfernt.

Collection auf `Restaurant`: `$openingHours` (OneToMany, `cascade`, `orphanRemoval`,
`OrderBy dayOfWeek ASC, openTime ASC`).
Helper: `Restaurant::getOpeningHoursForDay(int $day): OpeningHour[]`.

Migrationen: `Version20260321000000` (Tabelle), `Version20260619000000` (Multi-Slot).

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben |
|---|---|---|
| jeder | Wochenplan auf der Detailseite | — |
| `ROLE_ADMIN` | dito | über `RestaurantType` (B20) |

## Missbrauchsschutz

Keiner nötig — kein eigener Endpunkt. Es fehlt eine Plausibilitätsprüfung beim Anlegen
(FB-03).

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | Abwesenheit von Slots; kein `isClosed`-Feld |
| AK-02 | `partials/_opening_hours.html.twig` |
| AK-03 | `isOpenAt()`, Zweig `$open <= $close` |
| AK-04 | `isOpenAt()`, Zweig `$open > $close`, heutiger Tag |
| AK-05 | `isOpenAt()`, Vortagsschleife |
| AK-06 | `if ($slot->getOpenTime() === null \|\| …) continue;` |
| AK-07 | `if ($this->isOpenAt(...)) return null;` |
| AK-08 | `$nextToday`-Schleife |
| AK-09 | `for ($i = 1; $i <= 6; ++$i)` |
| AK-10 | `RestaurantRepository::findPaginated()`, `open`-Zweig mit `distinct()` |
| AK-11 | `opening_hours_form_controller.ts` |
| AK-12 | `cascade`, `orphanRemoval`, FK CASCADE |
| AK-13 | Konstante `TIMEZONE` im Service, `DateTimeZone` im Repository |
| AK-14 ⚠ | Schleifengrenze `$i <= 6` | Lücke, FB-01 |
| AK-15 ⚠ | fehlende Schließzeitprüfung im Nachtzweig | begründet durch die Vortagsprüfung, aber unkommentiert |
| AK-16, AK-17 | Feldbestand; Pflege nur über B20 |

## Für `sdd-qa` besonders zu prüfen

1. **AK-14** — ein Haus mit genau einem Öffnungstag anlegen und die Detailseite nach
   Ladenschluss aufrufen.
2. **AK-10 gegen AK-03/AK-04** — dasselbe Haus über den Filter `?open=1` und über
   `is_open_now` prüfen. Die beiden Fassungen der Regel müssen dasselbe sagen; die
   Doppelung ist die Hauptgefahr des Features.
3. Der Unit-Test `tests/Unit/Service/OpeningHoursServiceTest.php` deckt Multi-Slot,
   Nachtschicht und nächste Öffnung ab — nicht aber AK-14.
