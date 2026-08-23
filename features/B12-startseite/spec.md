# B12 · Startseite — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Der Einstieg: Hero mit Anzahl erfasster Häuser, eine Erklärung in drei Schritten, die
sechs bestbewerteten Restaurants, ein „Warum Endlech.lu?"-Block und ein
Handlungsaufruf.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B05 | rekonstruiert | die Top-6 kommen aus demselben Repository |

## User Stories

- **US-01** · Als neuer Besucher möchte ich in einem Satz verstehen, worum es geht.
- **US-02** · Als Besucher möchte ich sofort ein paar Häuser sehen.

## Nicht im Scope

- Suche und Filter → B05 · Statische Informationsseiten → B13

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher ruft `/` **ohne** Sprache auf, wenn die Anfrage
  durchläuft, dann leitet `app_root` auf `app_home` mit `_locale: lb` weiter
  (nicht permanent).
- **AK-02** · Angenommen, `/{locale}/` wird aufgerufen, wenn die Seite lädt, dann
  erscheinen Hero, „So funktioniert's", Top-6, „Warum Endlech.lu?" und der
  Handlungsaufruf.
- **AK-03** · Angenommen, die Startseite lädt, wenn die Anzahl geprüft wird, dann steht
  dort die Gesamtzahl aller Restaurants (`count()`), nicht die der geprüften.
- **AK-04** · Angenommen, die Top-6 werden geladen, wenn ihre Reihenfolge geprüft wird,
  dann sind sie nach Bewertung absteigend, bei Gleichstand nach Name aufsteigend
  sortiert.
- **AK-05** · Angenommen, die Top-6 werden geladen, wenn die Abfrage betrachtet wird,
  dann sind Öffnungszeiten und Küchen mitgeladen (`addSelect`) — kein N+1.
- **AK-06** · Angenommen, ein Besucher ist nicht angemeldet, wenn die Seite lädt, dann
  ist sie vollständig sichtbar — kein Konto nötig.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-07** ⚠ · Angenommen, ein ungeprüftes Haus hat die höchste Bewertung, wenn die
  Startseite lädt, dann steht es ganz oben.
  *(`findTopRated()` filtert nicht auf `isVerified`. Dieselbe Eigenschaft wie
  B05/AK-15, hier aber an der prominentesten Stelle der Anwendung. Das `rating` wird
  ausschließlich von Admins gepflegt — es gibt keine Nutzerbewertungen —, sodass der
  Wert bei einem über die API angelegten Haus (B23/AK-21) auf dem Vorgabewert steht.)*

### Datenschutz und Missbrauchsschutz

- **AK-08** · Angenommen, die Startseite lädt, wenn nach personenbezogenen Daten
  gesucht wird, dann enthält sie **keine**.
- **AK-09** · Angenommen, die Startseite lädt, wenn geprüft wird, ob Zwischenspeicherung
  greift, dann **keine** — zwei Abfragen bei jedem Aufruf.

## Edge Cases

- **EC-01** · Leere Datenbank → Zähler 0, leeres Raster, Seite bleibt gültig.
- **EC-02** · `app_root` ist eine `RedirectController`-Route aus `config/routes.yaml`,
  kein Controller im Projekt.
- **EC-03** · Die hreflang-Schleife in `base.html.twig` überspringt `app_root` — die
  Route hat keine Sprachvariante.

## Fehlbestand

- **FB-01 · Kein Vorfilter auf geprüfte Häuser.** Siehe AK-07.
- **FB-02 · Keine Zwischenspeicherung.** Siehe AK-09. Die Zahl ändert sich selten; ein
  Cache-Header oder der vorhandene Pool (B16) läge nahe.
- **FB-03 · Keine Spracherkennung.** `/` leitet immer auf `lb`, unabhängig vom
  `Accept-Language`-Header des Besuchers.
- **FB-04 · Kein `noindex` für die Weiterleitung**, und die Weiterleitung ist
  `permanent: false` (302) — für eine dauerhafte Sprachwahl wäre das diskutabel.

## Offene Fragen

- **OF-01** · Soll `/` den `Accept-Language`-Header auswerten (FB-03)? Bei vier
  gepflegten Sprachen und einem mehrsprachigen Land wäre es naheliegend. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Sechs Häuser im Raster | `findTopRated(6)` | dieselbe Zahl wie eine Seite der Liste (B05) |
| 2 | Sortierung mit Namens-Tiebreaker | `rating DESC, name ASC` | stabile Reihenfolge bei gleicher Bewertung |
| 3 | Weiterleitung statt Sprachwahlseite | `RedirectController` | ein Zwischenschritt kostet jeden Besucher einen Klick |
| 4 | Vorgabesprache `lb` | Luxemburgisch | Landessprache; deckt sich mit `default_locale` |
