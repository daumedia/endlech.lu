# 03 · Vergleichsseiten — Systemdesign

Status: `architected` · Stand: 2026-08-28 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Fünf neue öffentliche Seiten, die **nichts speichern**: eine Übersicht und vier
Vergleichsseiten. Es entsteht keine Entität, keine Tabelle, keine Migration — der Inhalt
ist redaktionell und lebt im Quelltext.

Getrennt wird zwischen **Struktur** und **Text**. Die Struktur — welche Merkmalszeile in
welcher Gruppe steht, ob Endlech.lu und der Wettbewerber sie erfüllen, welche Quelle das
belegt — liegt als unveränderliche Datenstruktur im neuen Namensraum `App\Comparison\`.
Die Texte liegen in einer eigenen Übersetzungsdomain `comparison`. So bleibt eine
Merkmalszeile in allen vier Sprachen dieselbe Zeile, und ein Prüflauf kann nachweisen,
dass zu jeder Struktur auch vier Texte existieren.

Die Zahlen über Endlech.lu selbst kommen aus derselben Quelle wie `/open`
(`OpenStatsService::platform()`), die bereits über einen eigenen Zwischenspeicher läuft.
Es wird kein zweiter Rechenweg gebaut — eine Vergleichsseite, die andere Zahlen nennt als
die Transparenzseite, wäre schlimmer als keine.

Angefasst werden drei bestehende Dateien: die App-Hülle (Fußzeilenspalte, zwei neue
Kopfbereich-Blöcke), die Routenkonfiguration (sprachfreier Kurzlink) und zwei Prüfläufe
(Routenliste, Katalogvollständigkeit).

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_comparison_index` | `/{_locale}/vergleich` | Übersicht: alle vier Vergleiche mit Namen und einem Satz | öffentlich |
| `app_comparison_show` | `/{_locale}/vergleich/{slug}` | eine Vergleichsseite; `slug` ∈ `google-maps\|wheelmap\|tripadvisor\|jaccede` | öffentlich |
| `app_comparison_redirect` | `/vergleich` (sprachfrei) | leitet auf `app_comparison_index` in der Sprache des Besuchers | öffentlich |

Der Locale-Präfix entsteht automatisch: `config/routes.yaml` importiert
`src/Controller/` mit `prefix: /{_locale}`. Am Controller ist nichts zu konfigurieren.

Der sprachfreie Kurzlink wird wie `app_open_redirect` in `config/routes.yaml`
eingetragen, nicht als Controller-Methode — dort steht das Muster bereits.

**Kein Ziel nach einer Handlung**, weil es keine Handlung gibt: Die Seiten nehmen nichts
entgegen. Der einzige weiterführende Weg ist der Link auf `app_restaurant_index`, und der
existiert.

**Der Slug ist in allen vier Sprachen derselbe.** Das ist keine Bequemlichkeit, sondern
die Voraussetzung für AK-05: Der Sprachumschalter baut die Zieladresse als
`path(current_route, current_params|merge({'_locale': locale}))`
(`templates/partials/_language_switcher.html.twig:44`). Wäre der Slug übersetzt, stünde
dort ein Wert, den die Zielsprache nicht kennt — der Umschalter und die
`hreflang`-Schleife in `base.html.twig:20–28` würden werfen, und zwar auf **jeder** Seite,
nicht nur hier.

## Komponentenstruktur

```
Übersichtsseite /vergleich
├── Kopfband                     dunkles Verlaufsband, H1, ein Satz zur Einordnung
├── Vergleichsliste
│   └── Vergleichskarte × 4      Wortmarke, ein Satz, Link — Muster der Zielgruppenkarten auf /organisationen
└── Methodikhinweis              wie die Angaben zustande kommen, Link auf /criteria und /open

Vergleichsseite /vergleich/{slug}
├── Kopfband                     H1 „Endlech.lu vs <Wortmarke>", Unterzeile
├── Kurzfazit                    zwei Blöcke „passt, wenn …" + Satz, dass beides nebeneinander geht
├── Merkmalstabelle              vier Gruppen, je Zeile zwei Wertzellen
│   ├── Gruppenzeile             Zwischenüberschrift innerhalb der Tabelle
│   └── Wertzelle                Symbol (aria-hidden) + sr-only-Text + erklärender Halbsatz + Fußnotenzeichen
├── Quellenliste                 nummerierte Fußnoten: Quelle, Adresse, Prüfdatum
├── Gegenposition                „Wann <Wortmarke> die bessere Wahl ist" — mindestens drei Punkte
├── Häufige Fragen               <details>/<summary>, mindestens vier
├── Handlungsaufruf              Link auf die Restaurantsuche
├── Querverweise                 die anderen drei Vergleiche, Wortmarke im Linktext
└── Rechtlicher Fuß              Markenhinweis + Verweis auf den Kontaktweg im Impressum
```

Wiederverwendet, nicht neu gebaut: das Kopfband-Muster der Geschäfts- und Datenseiten,
die Tabellenmechanik aus `templates/partner/index.html.twig:86–135` (Scrollbereich mit
`tabindex="0" role="region"`, `<caption class="sr-only">`, `th scope`, Symbol
`aria-hidden` plus `sr-only`-Text), die Karten- und Schaltflächenketten aus
`docs/design-system.md`.

**Vier Zustände je Bildschirm:**

| Zustand | Übersicht | Vergleichsseite |
|---|---|---|
| leer | tritt nicht auf — vier Vergleiche stehen fest im Quelltext | dito |
| ladend | tritt nicht auf — serverseitig gerendert, kein Nachladen | dito |
| Fehler | unbekannter Slug → 404-Seite der Anwendung | Zwischenspeicher leer → Zahlen werden einmal berechnet, kein sichtbarer Unterschied |
| gefüllt | vier Karten | vollständige Seite |

Der leere Zustand ist hier tatsächlich unmöglich, nicht bloß unwahrscheinlich: Die Anzahl
der Vergleiche ist eine Aufzählung im Quelltext. Der einzige datenabhängige Teil sind die
eigenen Zahlen — bei null erfassten Restaurants steht dort „0" (EC-02).

## Datenmodell

**Es entsteht keine Datenbanktabelle und keine Migration.** Alle vorhandenen Entitäten
bleiben unverändert. Was folgt, ist die Feldebene der unveränderlichen Datenstruktur im
Quelltext — sie ersetzt an dieser Stelle das Schema.

### `App\Comparison\Competitor` (Aufzählung)

| Fall | Slug | Wortmarke | Textschlüssel-Präfix |
|---|---|---|---|
| `GOOGLE_MAPS` | `google-maps` | Google Maps | `google_maps.` |
| `WHEELMAP` | `wheelmap` | Wheelmap | `wheelmap.` |
| `TRIPADVISOR` | `tripadvisor` | TripAdvisor | `tripadvisor.` |
| `JACCEDE` | `jaccede` | Jaccede | `jaccede.` |

Die Wortmarke steht als fester Text im Quelltext, **nicht** im Übersetzungskatalog:
Eigennamen werden nicht übersetzt, und ein übersetzbarer Produktname lädt dazu ein, ihn
in einer Sprachfassung falsch zu schreiben. Dasselbe Muster wie
`WebauthnCredentialRepository::guessDeviceName()`, das „iPhone" und „Mac" fest schreibt.

Auflösung des Slugs über `fromSlug()`, unbekannt → `null`, Muster aus
`App\Enum\OrganisationType`.

### `App\Comparison\Verdict` (Aufzählung)

| Fall | Bedeutung | Darstellung |
|---|---|---|
| `YES` | vorhanden | Häkchen, grün |
| `NO` | nicht vorhanden | Strich, grau |
| `PARTIAL` | eingeschränkt vorhanden | Tilde, bernstein |

**Nicht `App\Enum\TriState` wiederverwenden.** Dessen dritter Fall heißt `UNKNOWN` und
bedeutet auf dieser Plattform „nicht erhoben" — eine Aussage, die für den Vorschlags-Wizard
zentral ist. `PARTIAL` heißt „erhoben und teilweise erfüllt". Die beiden in einen Topf zu
werfen hieße, die Unterscheidung aufzugeben, für die `TriState` überhaupt gebaut wurde.

### `App\Comparison\ComparisonGroup` (Aufzählung)

`ACCESSIBILITY_DATA` · `PROVENANCE` · `COVERAGE` · `OPENNESS` — in dieser Reihenfolge
gerendert, entspricht den vier Gruppen aus AK-08.

### `App\Comparison\ComparisonRow` (unveränderliches Wertobjekt)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `group` | `ComparisonGroup` | ja | in welcher Gruppe die Zeile steht |
| `labelKey` | `string` | ja | Textschlüssel des Merkmals, Domain `comparison` |
| `own` | `Verdict` | ja | erfüllt Endlech.lu das Merkmal |
| `theirs` | `Verdict` | ja | erfüllt der Wettbewerber es |
| `ownNoteKey` | `string` | ja | erklärender Halbsatz in der eigenen Spalte |
| `theirNoteKey` | `string` | ja | erklärender Halbsatz in der Spalte des Wettbewerbers |
| `sourceRef` | `?int` | nein | Nummer der Fußnote; **Pflicht, sobald `theirs` eine Aussage über den Wettbewerber trifft** |
| `figure` | `?string` | nein | Name einer Kennzahl, die statt eines festen Textes eingesetzt wird (z. B. Anzahl erfasster Lokale) |

`ownNoteKey` und `theirNoteKey` sind **Pflicht**, nicht optional. Genau daran hängt AK-09:
Ohne den Halbsatz stünde in der Zelle nur ein Symbol, und die Tabelle wäre eine Behauptung
statt einer Aussage.

### `App\Comparison\ComparisonSource` (unveränderliches Wertobjekt)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `ref` | `int` | ja | Fußnotennummer, je Vergleichsseite ab 1 |
| `labelKey` | `string` | ja | Bezeichnung der Quelle |
| `url` | `string` | ja | Adresse der Quelle |
| `checkedAt` | `DateTimeImmutable` | ja | Tag der Prüfung — wird als Datum ausgegeben, nicht als Zeitpunkt |

### `App\Comparison\ComparisonPage` (unveränderliches Wertobjekt)

Bündelt alles, was eine Seite braucht: `competitor`, `rows` (Liste), `sources` (Liste),
`advantageKeys` (Liste, mindestens drei), `faqKeys` (Liste, mindestens vier).

### `App\Comparison\ComparisonRegistry` (Dienst)

Die **einzige** Stelle, an der Inhalte stehen. Liefert `page(Competitor): ComparisonPage`
und `all(): ComparisonPage[]`. Kein Zustand, keine Datenbank.

### `App\Comparison\ComparisonFigures` (Dienst)

Übersetzt die Kennzahlen von `OpenStatsService::platform()` in die wenigen Werte, die in
Tabellenzellen eingesetzt werden — erfasste Lokale, davon verifiziert, abgedeckte
Gemeinden. Ruft **ausschließlich** `platform()`, nicht `all()`: Die Finanz- und
Wirkungsdaten werden hier nicht gebraucht und würden sonst bei jedem Seitenaufruf
mitberechnet, wenn ihr Zwischenspeicher gerade leer ist.

**Beziehungen und Löschregeln:** entfallen. Es gibt keinen Fremdschlüssel, keinen
Datensatz, der zu einer Person gehört, und beim Löschen eines Kontos ist an diesem Feature
nichts zu tun.

### Änderungen an bestehenden Dateien

| Datei | Änderung |
|---|---|
| `templates/base.html.twig` | Fußzeile `md:grid-cols-3` → `md:grid-cols-4`, vierte Spalte „Vergleiche"; im Kopfbereich zwei neue, standardmäßig leere Blöcke für Kurzbeschreibung und kanonische Adresse |
| `config/routes.yaml` | Block `app_comparison_redirect` nach dem Muster von `app_open_redirect` |
| `translations/comparison.{lb,de,fr,en}.yaml` | **neu** — eigene Domain, keine Konfiguration nötig |
| `translations/messages.{lb,de,fr,en}.yaml` | vier neue Schlüssel für die Fußzeilenspalte (Überschrift, „Alle Vergleiche") |
| `tests/Functional/AccessibilityStructureTest.php` | fünf Adressen in `publicRoutes()` |
| `tests/Unit/Translation/CatalogueCompletenessTest.php` | Domain `comparison` in die geprüfte Liste |
| `docs/app-shell.md` | Fußzeilenabschnitt: vier Spalten statt drei. ⚠ Der Abschnitt ist bereits überholt (er nennt sieben Linkeinträge, es sind zehn) — beim Nachziehen mit korrigieren |

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| jeder, auch nicht angemeldet | alle fünf Seiten vollständig | nichts | — es gibt keinen Schreibweg |
| Redaktion | dieselben Seiten | Inhalte über einen Commit im Quelltext | Schreibrecht am Repository, Auslieferung über den Deploy-Branch |

**Die Seiten sind öffentlich, weil keine `access_control`-Regel auf sie passt — nicht,
weil eine Regel sie freigibt.** In `config/packages/security.yaml` gibt es keinen
Catch-all; gedeckt sind nur `admin`, `profile`, `register`, `login`, `verify` und die
beiden Passwort-Pfade. Ein Pfad ohne passende Regel ist in Symfony offen. Das ist hier
gewollt und entspricht dem Bestand — `/about`, `/partner`, `/organisationen` und `/open`
stehen genauso da. Es wird **keine** eigene `PUBLIC_ACCESS`-Zeile ergänzt: Eine einzelne
explizite Regel für ein einzelnes Feature erweckte den Eindruck, die übrigen öffentlichen
Seiten seien anders behandelt.

Ein unbekannter Slug ist keine Zugriffsfrage, sondern eine Existenzfrage: Der
Regex im Route-Requirement lässt nur die vier bekannten Werte durch, und der Controller
prüft zusätzlich über `Competitor::fromSlug()` (Muster aus `OrganisationController:58–64`).
Beides zusammen ergibt 404 — der Regex fängt den Normalfall, die Prüfung im Controller
den Fall, dass jemand den Regex später erweitert und das Enum vergisst.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `/vergleich`, `/vergleich/{slug}` | **kein Zähler** | — | — |

Begründung, weil die Projektkonvention drei Fälle kennt:

- **Löst eine Mail aus?** Nein. Kein Formular, kein Versand.
- **Prüft ein Geheimnis?** Nein. Kein Token, kein Passwort, keine Anmeldung.
- **Lädt bei jedem Aufruf den gesamten Bestand?** Das wäre der Fall, und genau hier greift
  der Schutz — nicht als Rate Limit, sondern strukturell: Die Kennzahlen kommen aus dem
  bestehenden Zwischenspeicher `cache.open_stats` (Dateisystem, eine Stunde). Ein Aufruf
  ohne gefüllten Speicher berechnet einmal; die folgenden lesen. Ein Rate Limit wäre hier
  das falsche Werkzeug: Es würde echte Besucher aussperren, ohne die Last zu senken.

Der Zwischenspeicher wird **nicht** von diesem Feature geleert. `OpenStatsService::invalidate()`
ruft heute die Finanzverwaltung nach jeder Änderung; daran ändert sich nichts.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| — | — | — | — |

**Die Tabelle ist absichtlich leer.** Kein Dienst wird beim Aufruf kontaktiert: keine
fremden Logos, keine eingebetteten Schriften von einem fremden Server, keine Karten, keine
Analysewerkzeuge. Die Adressen der Quellen stehen als Text in der Fußnotenliste; ob sie
verlinkt werden, ändert daran nichts — ein Link wird erst beim Klick geladen.

Das ist die technische Entsprechung von AK-30: Die IP-Adresse des Besuchers erreicht
keinen Dritten. Ein einziges von `google.com` nachgeladenes Logo hätte das gekippt, ohne
dass es jemandem aufgefallen wäre.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Struktur in `App\Comparison\`, Texte in eigener Domain `comparison` | alles als Block `vergleich:` in `messages.*.yaml` | die vier `messages`-Kataloge tragen bereits 1238 Schlüssel; rund 600 weitere Zeilen machen sie unübersichtlich. Wichtiger: Eine datengetriebene Tabelle ruft ihre Schlüssel zwangsläufig dynamisch auf, und der Scanner in `CatalogueCompletenessTest` erfasst nur Literale — die Struktur in PHP macht einen eigenen, vollständigen Prüflauf überhaupt erst möglich |
| 2 | **ein** Template für alle vier Vergleichsseiten, datengetrieben | vier eigenständige Templates | die Tabellenmechanik samt Screenreader-Ausgabe stünde sonst viermal da; eine Korrektur an der Barrierefreiheit müsste viermal nachgezogen werden, und beim vierten Mal wird sie vergessen |
| 3 | eigenes `Verdict` mit `PARTIAL` statt `TriState` | `TriState` wiederverwenden | `UNKNOWN` heißt „nicht erhoben", `PARTIAL` heißt „erhoben, teilweise erfüllt". Diese Unterscheidung ist der Grund, warum `TriState` existiert; sie hier aufzugeben wäre ein Rückschritt an der falschen Stelle |
| 4 | Wortmarken als fester Text im Quelltext | im Übersetzungskatalog | Eigennamen werden nicht übersetzt; ein übersetzbarer Produktname lädt zu einer falschen Schreibweise in einer Sprachfassung ein, die niemand bemerkt |
| 5 | Slug in allen vier Sprachen gleich | je Sprache übersetzte Slugs | der Sprachumschalter und die `hreflang`-Schleife bauen ihre Adressen aus `current_params`; ein Slug, den die Zielsprache nicht kennt, ließe beide werfen — und zwar auf jeder Seite der Website, nicht nur hier |
| 6 | Kennzahlen aus `OpenStatsService::platform()` | eigene Abfrage im neuen Dienst | zwei Rechenwege ergeben früher oder später zwei verschiedene Zahlen für dieselbe Aussage; die Transparenzseite ist die veröffentlichte Fassung, und sie gilt |
| 7 | nur `platform()`, nicht `all()` | `all()` der Bequemlichkeit halber | `all()` berechnet zusätzlich Wirkung und Finanzen. Bei leerem Zwischenspeicher zahlt der Besucher einer Vergleichsseite dafür, ohne dass eine der Zahlen dort erscheint |
| 8 | zwei leere Blöcke im Kopfbereich, nur hier gefüllt | Kurzbeschreibung für alle Seiten nachrüsten | das wäre ein zweites Feature; die Blöcke sind rückwärtskompatibel und ändern an keiner bestehenden Seite etwas |
| 9 | Routenname `app_comparison_*`, Pfad `/vergleich` | durchgängig deutsch oder durchgängig englisch | das Projekt ist an dieser Stelle uneinheitlich (`app_kriterien` → `/criteria`, `app_organisations` → `/organisationen`); der Routenname folgt hier dem PHP-Namensraum, der Pfad der Sichtbarkeit nach außen |
| 10 | keine eigene `access_control`-Zeile | `^/[a-z]{2}/vergleich` als `PUBLIC_ACCESS` | eine explizite Regel für genau dieses Feature ließe die übrigen öffentlichen Seiten so aussehen, als seien sie anders behandelt. Der Befund gehört benannt, nicht einzeln repariert |
| 11 | Fußnoten je Quelle mit eigenem Datum | ein Prüfdatum je Seite | wer eine Zeile nachprüft, datiert sonst implizit alle anderen mit — und die veröffentlichte Aussage wäre frischer, als die Prüfung war |

## Abdeckung der Akzeptanzkriterien

Aus `spec.md` der Reihe nach durchgegangen, nicht aus dem Gedächtnis.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | vierte Fußzeilenspalte in `base.html.twig`, vier Links aus `Competitor::cases()` plus „Alle Vergleiche" | Fußzeile wird auf allen Routen gerendert, auch im Admin — Bestand, hier nicht geändert |
| AK-02 | `app_comparison_index` + `comparison/index.html.twig`, Karten aus `ComparisonRegistry::all()` | |
| AK-03 | `app_comparison_show`, Regex-Requirement mit den vier Slugs | |
| AK-04 | Regex-Requirement **und** `Competitor::fromSlug()` → `createNotFoundException()` | doppelt, siehe Zugriffsregeln |
| AK-05 | Slug sprachunabhängig (Entscheidung 5); Sprachumschalter unverändert | |
| AK-06 | Baustein Querverweise, Wortmarke im Linktext | erfüllt zugleich AK-33 aus Feature 02 („kein ‚hier'") |
| AK-07 | Baustein Kurzfazit, zwei Textschlüssel je Vergleich plus gemeinsamer Satz | |
| AK-08 | `ComparisonGroup` mit genau vier Fällen, Reihenfolge im Template festgelegt | Prüflauf: jede Gruppe trägt mindestens eine Zeile |
| AK-09 | `ComparisonRow.ownNoteKey` und `theirNoteKey` sind Pflichtfelder | Prüflauf: kein leerer Halbsatz |
| AK-10 | `ComparisonPage.advantageKeys`, mindestens drei | Prüflauf über die Anzahl |
| AK-11 | `ComparisonPage.faqKeys`, mindestens vier; Handlungsaufruf auf `app_restaurant_index` | Prüflauf über die Anzahl |
| AK-12 | `ComparisonSource` mit `url` und `checkedAt`; `sourceRef` an jeder Zeile mit Aussage über den Wettbewerber | Prüflauf: keine Zeile mit `theirs`-Aussage ohne auflösbare Fußnote |
| AK-13 | Zeile in Gruppe `COVERAGE` mit `figure` = erfasste Lokale; die Zahl wird eingesetzt, nicht bewertet | die Gruppe steht fest im Enum und lässt sich nicht stillschweigend weglassen |
| AK-14 | Baustein Rechtlicher Fuß, ein Textschlüssel je Sprache | |
| AK-15 | keine Bildquelle im Template; Tabelle in der Sprachdomain enthält keine Adressen zu Bildern | Prüflauf: die gerenderte Seite enthält kein `<img>` mit fremder Herkunft |
| AK-16 | `ComparisonFigures` liest `OpenStatsService::platform()` — dieselbe Quelle wie `/open` | Entscheidung 6 |
| AK-17 | derselbe Zwischenspeicher, Laufzeit eine Stunde; keine feste Zahl im Katalog | |
| AK-18 | `cache.open_stats` (Dateisystem, TTL 3600); nur `platform()` | Entscheidung 7 |
| AK-19 | eigener Titelschlüssel je Seite in der Domain `comparison` | Prüflauf: fünf paarweise verschiedene Titel |
| AK-20 | neuer Block im Kopfbereich, gefüllt aus der Domain `comparison` | Entscheidung 8 |
| AK-21 | zweiter neuer Block im Kopfbereich; `hreflang` bleibt unverändert und greift automatisch, weil der Slug sprachunabhängig ist | |
| AK-22 | fünf Adressen in `AccessibilityStructureTest::publicRoutes()` | prüft eine H1, Skip-Link, `main#hauptinhalt`, `html lang` |
| AK-23 | Wertzelle nach dem Muster `partner/index.html.twig:123–127`: Symbol `aria-hidden`, Aussage als `sr-only` | |
| AK-24 | Scrollbereich `tabindex="0" role="region" aria-label` mit eigenem `focus:outline-2` | Muster aus demselben Template |
| AK-25 | `overflow-x-auto` nur am Tabellenbereich, Seitenbreite über `container mx-auto px-4` | |
| AK-26 | ausnahmslos `motion-safe:transition` an Karten, Links, Schaltflächen | Regel aus `docs/design-system.md` |
| AK-27 | eine H1 im Kopfband, Sektionen als H2, Karten und Fragen als H3 | von AK-22 mitgeprüft |
| AK-28 | Domain `comparison` in `CatalogueCompletenessTest`; **zusätzlich** ein Prüflauf, der jeden in `App\Comparison\` genannten Schlüssel in allen vier Katalogen sucht | der bestehende Scanner erfasst nur literale Schlüssel in Templates und liefe hier ins Leere — das ist die Falle, die dieses AK sonst wirkungslos machte |
| AK-29 | folgt aus AK-28: Die Fallback-Kette `['de','en']` gilt auch für eigene Domains, also verhindert nur ein vollständiger Katalog den stillen Rückfall | *(symfony.com/doc/current/translation.html, gelesen 2026-08-28)* |
| AK-30 | keine externe Ressource im Template (Entscheidung 4, Abschnitt Externe Dienste) | Prüflauf: gerenderte Seite enthält keine Verweise auf fremde Hosts in `src`/`href` von Ressourcen |
| AK-31 | Route `app_comparison_redirect` in `config/routes.yaml`, Muster `app_open_redirect` | am 2026-08-28 aus OF-01 in die Spec ergänzt |
| AK-32 | Baustein *Rechtlicher Fuß* verlinkt den Kontaktweg im Impressum | am 2026-08-28 aus OF-03 in die Spec ergänzt |

Keine Zeile leer. AK-31 und AK-32 wurden beim Aufgabenplan nachgetragen: Beide Entscheidungen
standen bereits im Entwurf (OF-01, OF-03), hatten aber kein Kriterium — und was kein Kriterium
hat, prüft `sdd-qa` nicht.

Die vier Punkte des Sicherheitskatalogs, die in der Spec als *trifft
nicht zu* geführt sind (personenbezogene Daten, Löschen und Auskunft, Geheimnisse,
Rate Limit als Zähler), sind in den Abschnitten Datenmodell, Zugriffsregeln und
Missbrauchsschutz jeweils mit ihrer Begründung wiederholt — nicht weggelassen.

## Offene Fragen aus der Spec

| # | Stand |
|---|---|
| OF-01 | **entschieden:** `/vergleich` ist auch sprachfrei erreichbar, wie `/open`. Route `app_comparison_redirect` |
| OF-02 | **offen** — der Prüfrhythmus ist ein Prozess, kein Entwurf. Der Entwurf macht ihn nachprüfbar, indem jede Quelle ihr eigenes Datum trägt; wer wann prüft, entscheidet Michael |
| OF-03 | **entschieden:** kein eigener Meldeweg. Der Baustein *Rechtlicher Fuß* verweist auf den Kontaktweg im Impressum, der ohnehin in jeder Fußzeile steht |
| OF-04 | **entschieden:** Slug `tripadvisor`, auf TripAdvisor zugeschnitten. Andere Bewertungsportale werden im Text erwähnt, ohne dass eine Aussage über sie belegt werden müsste |

## Was dieser Entwurf ausdrücklich nicht vorsieht

- **Keine Entität, keine Migration, kein Admin-Formular.** Wer später Pflege im Admin
  will, baut ein neues Feature — nicht einen Nachtrag hier.
- **Keine strukturierten Daten (JSON-LD) für die häufigen Fragen.** Naheliegend, aber die
  Plattform hat heute nirgends welche; ein erster Fall gehört in dasselbe Feature wie
  `sitemap.xml` und `robots.txt`.
- **Keine Kurzbeschreibung für die übrigen Seiten.** Nur der Platz entsteht.
- **Keine Änderung an `OpenStatsService`.** Der neue Dienst liest, er erweitert nicht.
