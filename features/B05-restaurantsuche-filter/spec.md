# B05 · Restaurantsuche, Filter & Sortierung — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Die Liste unter `/{locale}/restaurants` ist der eigentliche Zweck der Anwendung:
vierzehn kombinierbare Filter, drei Sortierungen, Seitenblättern zu je sechs. Alles
öffentlich, ohne Konto.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B07 | rekonstruiert | Filter „jetzt geöffnet" |
| B08 | rekonstruiert | Filter nach Küchentyp |

## User Stories

- **US-01** · Als Rollstuhlfahrer möchte ich nur Häuser mit stufenlosem Zugang sehen.
- **US-02** · Als Gast mit Assistenzhund möchte ich danach filtern.
- **US-03** · Als Gast möchte ich sehen, was **jetzt** geöffnet hat.
- **US-04** · Als Gast möchte ich mehrere Filter kombinieren.

## Nicht im Scope

- Detailansicht → B06 · Karte oder Umkreissuche — nicht vorhanden
- Volltextsuche über Namen — es gibt nur den Stadtfilter

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher ruft `/{locale}/restaurants` ohne Parameter auf,
  wenn die Seite lädt, dann erscheinen die ersten sechs Häuser nach Bewertung absteigend.
- **AK-02** · Angenommen, `?sort=name` steht in der Adresse, wenn die Seite lädt, dann
  ist die Reihenfolge alphabetisch; `?sort=newest` sortiert nach Anlagedatum absteigend.
- **AK-03** · Angenommen, ein unbekannter Sortierwert steht in der Adresse, wenn die
  Seite lädt, dann wird auf `rating` zurückgefallen — ohne Fehler.
- **AK-04** · Angenommen, `?page=2` steht in der Adresse, wenn die Seite lädt, dann
  erscheinen die Häuser 7 bis 12 und die Blätternavigation zeigt die richtige Seite.
- **AK-05** · Angenommen, `?page=0` oder ein negativer Wert steht in der Adresse, wenn
  die Seite lädt, dann wird auf Seite 1 zurückgefallen.
- **AK-06** · Angenommen, einer der elf Ja/Nein-Filter ist gesetzt (`verified`,
  `wheelchair`, `toilet`, `dogs`, `lighting`, `changing_table`, `disabled_parking`,
  `open`, `vegan`, `vegetarian`, `halal`), wenn die Seite lädt, dann erscheinen nur
  Häuser, die das Merkmal erfüllen.
- **AK-07** · Angenommen, `?city=Strassen` steht in der Adresse, wenn die Seite lädt,
  dann erscheinen Häuser, deren Stadt den Text enthält (LIKE-Suche).
- **AK-08** · Angenommen, `?cuisine[]=1&cuisine[]=2` steht in der Adresse, wenn die
  Seite lädt, dann erscheinen Häuser mit **mindestens einer** dieser Küchen.
- **AK-09** · Angenommen, `?lang_de=1&lang_fr=1` steht in der Adresse, wenn die Seite
  lädt, dann erscheinen nur Häuser, die **beide** Sprachen sprechen (UND-Verknüpfung).
- **AK-10** · Angenommen, `?open=1` ist gesetzt, wenn die Seite lädt, dann berücksichtigt
  die Abfrage auch **Nachtschichten** — ein Haus, das gestern um 22 Uhr geöffnet hat und
  heute um 2 Uhr schließt, gilt um 1 Uhr als geöffnet.
- **AK-11** · Angenommen, mehrere Filter sind gesetzt, wenn die Seite lädt, dann wirken
  sie **kombiniert** (UND über die Filterarten).
- **AK-12** · Angenommen, ein Haus hat mehrere Zeitfenster an einem Tag, wenn `?open=1`
  gesetzt ist, dann erscheint es **einmal** — die Abfrage ist `distinct()`.
- **AK-13** · Angenommen, kein Haus passt, wenn die Seite lädt, dann erscheint eine
  leere Liste mit Hinweis, kein Fehler.
- **AK-14** · Angenommen, die Filterauswahl wird betrachtet, wenn sie geprüft wird, dann
  stehen dort alle Küchentypen alphabetisch und alle Sprachen aus `Language`.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-15** ⚠ · Angenommen, ein Haus ist **nicht verifiziert**, wenn die Liste ohne
  Filter lädt, dann erscheint es gleichberechtigt neben geprüften — kenntlich nur am
  fehlenden Abzeichen.
  *(So verhält sich der Code heute: `findPaginated()` hat keinen Vorfilter auf
  `isVerified`; der Wert steuert nur das Abzeichen und den optionalen Filter
  `?verified=1`. Auf einer Plattform, deren erstes Produktprinzip „Bewertungen sind
  nicht käuflich" lautet, ist die Gleichstellung von geprüften und ungeprüften Angaben
  eine Aussage — verstärkt dadurch, dass jeder angemeldete Nutzer über die API ungeprüft
  anlegen kann (B23/AK-21).)*

- **AK-16** ⚠ · Angenommen, `?page=99999` steht in der Adresse, wenn die Seite lädt,
  dann erscheint eine leere Liste mit Status **200**.
  *(Kein 404 für Seiten jenseits des Bestands. Für Suchmaschinen bedeutet das beliebig
  viele indexierbare Leerseiten.)*

- **AK-17** ⚠ · Angenommen, `?city=%` steht in der Adresse, wenn die Seite lädt, dann
  passt der Filter auf **alle** Häuser.
  *(Der Wert wird als `'%'.$wert.'%'` in eine LIKE-Bedingung gesetzt, ohne dass `%` und
  `_` maskiert werden. Der Parameter ist gebunden — eine SQL-Injection ist damit
  ausgeschlossen —, aber die Filterwirkung lässt sich aushebeln.)*

### Datenschutz und Missbrauchsschutz

- **AK-18** · Angenommen, die Liste wird aufgerufen, wenn geprüft wird, ob eine
  Anmeldung nötig ist, dann nicht — sie ist der öffentliche Kern der Anwendung.
- **AK-19** · Angenommen, ein Filterwert kommt aus der Adresse, wenn er in die Abfrage
  geht, dann als **gebundener Parameter**; `cuisine` wird zusätzlich über `intval`
  geführt, `lang` gegen `Language::cases()` geprüft, `sort` gegen eine feste Liste.
- **AK-20** · Angenommen, die Liste zeigt Häuser, wenn nach personenbezogenen Daten
  gesucht wird, dann sind es Geschäftsangaben — Name, Stadt, Bewertung, Merkmale.

## Edge Cases

- **EC-01** · `findPaginated()` verbindet `openingHours` und `cuisines` mit `addSelect`
  — ohne das entstünde bei sechs Häusern ein N+1-Problem.
- **EC-02** · Bei `?open=1` kommen zwei zusätzliche Verbindungen hinzu (`oh_today`,
  `oh_yesterday`) plus `distinct()`.
- **EC-03** · Der Sprachfilter ist **UND**-verknüpft, der Küchenfilter **ODER** — das
  ist ein bewusster Unterschied, aber für einen Besucher nicht erkennbar.
- **EC-04** · `Paginator` liefert `count()` als Gesamtzahl; `lastPage` wird daraus
  errechnet.

## Fehlbestand

- **FB-01 · Kein Vorfilter auf geprüfte Einträge.** Siehe AK-15.
- **FB-02 · Keine Namenssuche.** Wer ein bestimmtes Haus sucht, kann nur über die Stadt
  filtern oder blättern.
- **FB-03 · Keine Maskierung der LIKE-Platzhalter.** Siehe AK-17.
- **FB-04 · Kein 404 jenseits der letzten Seite.** Siehe AK-16.
- **FB-05 · Keine Filterung nach den Maßen** (`doorWidthCm`, `tableSpacingCm`), obwohl
  sie erfasst und auf der Detailseite gezeigt werden — für Rollstuhlfahrer die
  belastbarste Angabe überhaupt.
- **FB-06 · Kein Umkreis- oder Kartenfilter**, obwohl alle Häuser Koordinaten tragen.
- **FB-07 · Keine Cache-Header.** Jede Anfrage löst die volle Abfrage aus.

## Offene Fragen

- **OF-01** · Sollen ungeprüfte Häuser gekennzeichnet oder herausgefiltert werden
  (AK-15)? Hängt an derselben Entscheidung wie B16/OF-02, B17/OF-01 und B23/OF-01. —
  Betreiber
- **OF-02** · Warum sind die Maße nicht filterbar (FB-05)? — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Sechs Häuser je Seite | `LIMIT = 6` | passt zum dreispaltigen Raster |
| 2 | Filter als Query-Parameter | statt POST | Ergebnisse bleiben verlinkbar und teilbar |
| 3 | Sprachen UND, Küchen ODER | so | eine Sprache muss das Haus wirklich sprechen; ein Küchentyp ist eine Auswahl |
| 4 | Unbekannte Werte still verwerfen | statt 400 | die Seite bleibt für zerlegte Links bedienbar |
| 5 | Alle Filterlogik in `findPaginated()` | statt im Controller | dieselbe Methode bedient Web und API (B23) |
