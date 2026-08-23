# B24 · Mehrsprachigkeit — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Die gesamte Website liegt in vier Sprachen vor — Luxemburgisch (Vorgabe), Deutsch,
Französisch, Englisch —, jede unter eigenem Pfadpräfix, mit Umschalter, hreflang-Angaben
und acht Übersetzungskatalogen.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| — | — | Querschnitt, greift überall |

## User Stories

- **US-01** · Als frankophoner Besucher möchte ich die Seite auf Französisch lesen.
- **US-02** · Als Besucher möchte ich die Sprache wechseln, ohne die Seite zu verlieren.
- **US-03** · Als Suchmaschine möchte ich die Sprachvarianten einer Seite erkennen.

## Nicht im Scope

- Übersetzung der Inhalte aus der Datenbank (Restaurantnamen, Küchentypen, Notizen) —
  sie sind einsprachig gespeichert
- Sprachwahl im Verwaltungsbereich → B19 (eigener Umschalter über die Sitzung)

## Akzeptanzkriterien

- **AK-01** · Angenommen, eine Web-Route wird aufgerufen, wenn ihr Pfad betrachtet wird,
  dann trägt sie das Präfix `/{_locale}` mit dem Requirement `lb|de|fr|en`.
- **AK-02** · Angenommen, kein Sprachcode steht im Pfad, wenn die Route aufgelöst wird,
  dann greift die Vorgabe `lb`.
- **AK-03** · Angenommen, ein Besucher wählt im Umschalter eine Sprache, wenn er dem
  Link folgt, dann bleibt er auf **derselben Seite** — Routenname, Routenparameter und
  Query-Parameter werden übernommen.
- **AK-04** · Angenommen, der Umschalter ist geöffnet, wenn er betrachtet wird, dann ist
  die aktuelle Sprache nicht anklickbar, sondern hervorgehoben.
- **AK-05** · Angenommen, der Umschalter wird per Tastatur bedient, wenn er geöffnet
  wird, dann wechselt `aria-expanded` von `false` auf `true` und der Pfeil dreht sich.
- **AK-06** · Angenommen, eine Seite lädt, wenn der Dokumentkopf betrachtet wird, dann
  steht dort für jede der vier Sprachen ein `<link rel="alternate" hreflang="…">` plus
  `x-default` auf `lb`.
- **AK-07** · Angenommen, `app_root` wird aufgelöst, wenn die hreflang-Schleife läuft,
  dann wird sie **übersprungen** — die Route hat keine Sprachvariante.
- **AK-08** · Angenommen, eine Seite lädt, wenn das Wurzelelement betrachtet wird, dann
  trägt `<html lang="…">` die aktuelle Sprache.
- **AK-09** · Angenommen, ein Übersetzungsschlüssel fehlt in einem Katalog, wenn die
  Seite lädt, dann erscheint der Schlüssel selbst — Symfony-Vorgabe, kein Fehler.
- **AK-10** · Angenommen, eine Zahl oder ein Betrag wird auf `/open` angezeigt, wenn die
  Sprache gewechselt wird, dann folgt die Schreibweise der Sprache (`format_number`,
  `format_currency` aus `twig/intl-extra`).
- **AK-11** · Angenommen, Validierungsmeldungen erscheinen, wenn sie geprüft werden,
  dann kommen sie aus `validators.{lb,de,fr,en}.yaml`.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-12** ⚠ · Angenommen, die aufgerufene Adresse enthält einen Query-Parameter
  namens `_locale`, wenn der Sprachumschalter gerendert wird, dann **überschreibt er
  die gewählte Sprache** — der Umschalter wechselt nicht.
  *(So verhält sich der Code heute:
  `path(current_route, current_params|merge({'_locale': locale})|merge(query_params))`
  in `partials/_language_switcher.html.twig`. Der `merge` der Query-Parameter steht
  **hinter** dem der Sprache und gewinnt damit. Folge: eine über die Adresse erzwingbare
  Fehlfunktion des Umschalters; harmlos, aber leicht zu beheben, indem die Reihenfolge
  getauscht wird.)*

- **AK-13** ⚠ · Angenommen, ein Besucher benutzt ein Telefon, wenn er die Sprache
  wechseln will, dann findet er den Umschalter **nicht** — er steht ausschließlich in
  `<div class="hidden md:block">` der Kopfzeile.
  *(Siehe `docs/app-shell.md#bekannte-lücken`, Punkt 2. Vier gepflegte Kataloge, von
  denen auf Mobil nur der über die Adresse erreichbare zugänglich ist.)*

- **AK-14** ⚠ · Angenommen, `/` wird aufgerufen, wenn die Sprache bestimmt wird, dann
  ist es immer `lb` — der `Accept-Language`-Header des Browsers wird **nicht**
  ausgewertet.
  *(Siehe B12/FB-03. In einem dreisprachigen Land mit hohem Anteil an Zugezogenen ist
  das eine spürbare Entscheidung.)*

### Datenschutz und Missbrauchsschutz

- **AK-15** · Angenommen, die Sprache wird gewählt, wenn geprüft wird, wo sie gespeichert
  wird, dann **im Pfad** — es wird kein Cookie gesetzt und nichts serverseitig
  gespeichert. (Ausnahme: der Verwaltungsbereich, B19/AK-06.)
- **AK-16** · Angenommen, der Sprachcode kommt aus der Adresse, wenn er verarbeitet
  wird, dann prüft ihn das Routen-Requirement `lb|de|fr|en` — ein anderer Wert ergibt
  404.

## Edge Cases

- **EC-01** · Die REST-API (`/api/v1`) und die Datenendpunkte (`/open.json`,
  `/open/dataset.*`) sind bewusst **sprachfrei**; `config/routes.yaml` schließt beide
  Verzeichnisse am `controllers`-Loader aus.
- **EC-02** · `CuisineApiController` liegt weiterhin **unter** dem Sprachpräfix — der
  ältere Schnittstellenteil wurde nicht mit ausgelagert (B08/AK-14).
- **EC-03** · Die Passkey-Endpunkte des Bundles sind ebenfalls sprachfrei und stehen
  deshalb in `access_control` vor den Web-Regeln.
- **EC-04** · Küchennamen (B08) und Restaurantangaben werden einsprachig gespeichert und
  in allen vier Fassungen unverändert angezeigt.

## Fehlbestand

- **FB-01 · Kein Sprachumschalter auf Mobil.** Siehe AK-13.
- **FB-02 · Keine Auswertung von `Accept-Language`.** Siehe AK-14.
- **FB-03 · Merge-Reihenfolge im Umschalter.** Siehe AK-12.
- **FB-04 · Keine Prüfung auf Vollständigkeit der Kataloge.** Ein fehlender Schlüssel
  fällt erst auf, wenn ihn jemand sieht; `debug:translation` läuft in keinem Workflow.
- **FB-05 · Inhalte aus der Datenbank sind nicht übersetzbar.** Siehe EC-04 — für
  Küchentypen („Italienisch" / „Italien" / „Italian") wäre es naheliegend.
- **FB-06 · Die hartkodierte deutsche Meldung des `UniqueEntity`-Constraints**
  (B01/FB-07) erscheint in allen vier Sprachfassungen auf Deutsch.

## Offene Fragen

- **OF-01** · Wo soll der Sprachumschalter auf Mobil hin (FB-01)? Die Bottom-Navigation
  hat vier Felder und keinen Platz; ein fünftes wäre eng. — Betreiber
- **OF-02** · Sollen Küchentypen übersetzbar werden (FB-05)? Das änderte die
  Entity-Struktur und beträfe B08, B05, B06, B17 und B23. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Sprache im Pfad statt im Cookie | Pfad | jede Sprachfassung ist eigenständig verlinkbar und indexierbar |
| 2 | Vorgabe `lb` | Luxemburgisch | Landessprache; deckt sich mit `default_locale` |
| 3 | API und Datenendpunkte sprachfrei | ja | die Daten sind in jeder Sprache dieselben; zitierte URLs sollen eindeutig bleiben |
| 4 | Zahlen über `format_number` | statt `number_format` | sonst stünde in der englischen Fassung „27,3 %" |
| 5 | Umschalter als `<div>` mit Stimulus | statt `<details>` wie die Hauptnavigation | Grund nicht erkennbar — die Navigation nutzt bewusst `<details>`, weil es ohne JavaScript funktioniert; der Sprachumschalter tut es nicht |
