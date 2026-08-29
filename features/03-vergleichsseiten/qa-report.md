# 03 · Vergleichsseiten — Testbericht

Stand: 2026-08-29 · **Zweiter Durchlauf** · Geprüft gegen `spec.md` vom 2026-08-28

## Fazit

**Production-ready: ja**

Die drei Befunde des ersten Durchlaufs sind behoben, und **keine ihrer Reproduktionen
greift noch**. Alle 32 Akzeptanzkriterien sind bestanden, alle sieben Randfälle belegt,
der Angriffsdurchlauf fand nichts. Die Reparatur von BF-77 hat die Seite nicht nur
schmal-tauglich gemacht, sondern auch lesbar: Statt einer Tabelle mit zerrissenen Wörtern
steht unter `md:` je Merkmal eine Karte.

Drei Dinge gehören trotzdem ins Fazit, weil sie sonst niemand liest:

- **BF-80 blockiert dieses Feature nicht, ist aber projektweit.** Bei 768 px scrollen
  **alle** Seiten des Projekts um 51 px waagerecht — Startseite, `/about`, `/open`,
  `/restaurants`, `/criteria`, `/legal` genauso wie die Vergleichsseiten. Ursache ist die
  Kopfzeile, nicht dieses Feature; mit ausgeblendetem `<header>` sind es 0 px. Der Befund
  gehört zu Feature `02` (Status `approved`).
- **BF-81 war eine Testlücke und ist geschlossen.** Der Fußnoten-Prüflauf sah nur die
  Tabelle. Nachgestellt: Die Kartendarstellung verlor alle 18 Fußnotenlinks, und alle 606
  Tests blieben grün. Auf schmalen Anzeigen hätten die Aussagen über den Wettbewerber
  unbelegt dagestanden. Der Test ist erweitert, die Gegenprobe schlägt jetzt an.
- **EC-05 hat eine theoretische Kante** (BF-82, niedrig): Ein Anbietername von 57 Zeichen
  **ohne Leerzeichen** sprengt die Karte bei 320 px. Bis 30 Zeichen — auch als ein einziges
  Wort — ist alles sauber; die drei realen Wortmarken sind 8 bis 11 Zeichen lang.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 32 von 32 |
| davon bestanden | **32** |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 7 von 7 |
| Tests neu geschrieben | 1 Fall (zweiter Durchlauf), 4 im ersten |
| Tests grün | **610 von 610** |

## Behobene Befunde des ersten Durchlaufs

Behoben ist ein Fehler, wenn die Reproduktion aus dem Bericht nicht mehr greift.

| Befund | Reproduktion im zweiten Durchlauf | Ergebnis |
|---|---|---|
| **BF-77** · Seite scrollt bei 320 px | `scrollTo(500,0)` bei 320/360/375/414/640/1024/1280 px auf allen drei Seiten | `scrollX=0` überall ✅ — auch auf der luxemburgischen und französischen Fassung |
| **BF-78** · Schlüssel fallen durch jeden Prüflauf | `group.coverage` **und** `verdict.partial` je aus allen vier Katalogen entfernt | beide Male rot: „1 Schlüssel fehlen in comparison.lb.yaml" ✅ |
| **BF-79** · zwei gleichnamige Landmarks | `<nav>`-Namen in allen vier Sprachen ausgelesen | de „Sie sind hier / Weitere Vergleiche", lb „Dir sidd hei / Weider Verglachër", fr, en — überall verschieden ✅ |

## Die Reparatur selbst geprüft

Die Doppeldarstellung ist der Eingriff mit dem größten Folgerisiko. Vier Prüfungen dazu:

| Frage | Ergebnis |
|---|---|
| Doppelte HTML-IDs durch den zweifachen Inhalt? | **nein** — 21 IDs je Seite, 0 doppelt |
| Laufen Tabelle und Karten inhaltlich auseinander? | **nein** — je Seite identische Merkmale (18/19/17), identische Halbsatzzahl (36/38/34), identische Fußnotenmenge |
| Liest ein Screenreader alles doppelt? | **nein** — bei 320 px stehen **0** `table`-Knoten im Accessibility-Tree, bei 1280 px genau **1**. Die versteckte Darstellung ist vollständig draußen |
| Bricht die Überschriftenhierarchie? | **nein** — `[1,2,2,2,3,3,3,3,3,2,2,3,3,3,3,2,2]`, ein `<h1>`, keine übersprungene Ebene |

## Akzeptanzkriterien im Einzelnen

Alle 32 in der Reihenfolge der Spec ausgeführt. Die Nachweise des ersten Durchlaufs
gelten fort, wo das Markup unverändert blieb; alles vom Umbau Betroffene wurde neu geprüft.

| AK | Ergebnis | Nachweis (zweiter Durchlauf) |
|---|---|---|
| AK-01 | ✅ | Fußzeile auf `/de/`: Überschrift, drei Slug-Links, „Alle Vergleiche" |
| AK-02 | ✅ | `/de/vergleich`: drei Wortmarken im `<main>`, genau 3 Karten-Links dort (6 im Dokument — die Fußzeile trägt dieselben) |
| AK-03 | ✅ | `google-maps 200`, `wheelmap 200`, `tripadvisor 200` |
| AK-04 | ✅ | `foobar 404`, `jaccede 404`, `Google-Maps 404` |
| AK-05 | ✅ | `/de/vergleich/wheelmap` verlinkt `/fr/vergleich/wheelmap` |
| AK-06 | ✅ | je Seite 2 Querverweise mit Wortmarke im Linktext |
| AK-07 | ✅ | je Seite 2 Kurzfazit-Blöcke plus Koexistenz-Satz |
| AK-08 | ✅ | je Seite 4 `scope="colgroup"` **und** 4 `<h3 id="gruppe-…">` — beide Darstellungen vollständig |
| AK-09 | ✅ | 108 Tabellenzellen + 108 Kartenwerte über drei Seiten, **keine** ohne Symbol, Ansage und Halbsatz |
| AK-10 | ✅ | je Seite 4 Vorteile des Wettbewerbers |
| AK-11 | ✅ | je Seite 4 `<details>` im `<main>` plus Link auf `/restaurants` |
| AK-12 | ✅ | je Seite 6 Fußnoten mit Adresse und Prüfdatum, keine ins Leere; **beide** Darstellungen belegt (neuer Prüflauf) |
| AK-13 | ✅ | „11 Lokale, ausschließlich Luxemburg" gegen „250 Millionen Orte weltweit" |
| AK-14 | ✅ | Markenhinweis auf allen drei Seiten |
| AK-15 | ✅ | je Seite 0 Bilder von fremden Servern |
| AK-16 | ✅ | `/open` 11, Vergleichsseite 11; `ComparisonFiguresTest` (4 Fälle grün) |
| AK-17 | ✅ | `ComparisonFiguresTest::testEinNeuesRestaurantErhoehtDieZahl` |
| AK-18 | ✅ | `ComparisonFiguresTest::testDerZweiteAufrufLiestAusDemZwischenspeicher` |
| AK-19 | ✅ | vier paarweise verschiedene `<title>` |
| AK-20 | ✅ | `/fr/vergleich/tripadvisor` trägt eine französische `meta description` |
| AK-21 | ✅ | `canonical` auf die eigene Adresse, 5 `hreflang`-Einträge |
| AK-22 | ✅ | `AccessibilityStructureTest` mit 4 neuen Adressen, 30 Tests grün |
| AK-23 | ✅ | 216 Bewertungen über drei Seiten, alle mit „Ja/Nein/Teilweise" als `sr-only` |
| AK-24 | ✅ | Scrollbereich mit `tabindex="0" role="region" aria-label`, `<caption class="sr-only">` |
| **AK-25** | ✅ **behoben** | 320 px: `scrollX=0` auf allen drei Seiten und in allen vier Sprachen; ebenso 360/375/414/640/1024/1280 px |
| AK-26 | ✅ | 6 × `motion-safe:transition`, **0** nacktes `transition` (tokenweise gezählt) |
| AK-27 | ✅ | ein `<h1>`, Folge ohne Sprung — auch mit beiden Darstellungen im Markup |
| AK-28 | ✅ | Gegenprobe mit `group.coverage` **und** `verdict.partial` aus allen vier Katalogen: beide Male rot |
| AK-29 | ✅ | `/lb/vergleich/wheelmap`: keine deutsche Wendung |
| AK-30 | ✅ | je Seite 23 Ressourcen, 0 von fremden Servern |
| AK-31 | ✅ | `/vergleich` → 302 auf `/lb/vergleich` |
| AK-32 | ✅ | Korrekturhinweis mit Link auf `/legal` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | `ComparisonControllerTest::testAbweichendeSchreibweiseErgibt404` |
| EC-02 | ✅ | `ComparisonFiguresTest::testKeinBestandErgibtNullUndKeinenFehler` |
| EC-03 | ✅ | `ComparisonEdgeCaseTest::testKeinJavaScriptNoetig` |
| EC-04 | ✅ | `ComparisonEdgeCaseTest::testDasKopfbandIstEinSectionElement` |
| EC-05 | ✅ | Browser bei 320 px: „Google Maps" (11 Z.), „Google Business Profile" (23 Z.), „Mobiliteit.lu Barrierefreiheit" (30 Z.) und ein 30-Zeichen-Wort ohne Leerzeichen → alle `scrollX=0`. ⚠ Ein 57-Zeichen-Wort ohne Leerzeichen ergibt 104 — siehe BF-82 |
| EC-06 | ✅ | `ComparisonEdgeCaseTest::testErsterAufrufOhneZwischenspeicher` |
| EC-07 | ✅ | eingetreten und behandelt (Jaccede, OF-05) |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | `%2e%2e%2fadmin` 404, `../../etc/passwd` 404, `%00` 404, 10.000-Zeichen-Slug 404 |
| Rate Limit greift | *trifft nicht zu* — belegt | 20 Aufrufe in Folge: 20 × 200, keine 5xx. Vollbestand-Schutz über den Zwischenspeicher (AK-18) |
| Schreibwege | bestanden | `POST`/`PUT`/`DELETE`/`PATCH` → alle 405 |
| PII in Logs | bestanden | 0 E-Mail-Adressen in den Vergleichs-Zeilen von `var/log/dev.log` |
| PII an externe Dienste | bestanden | 0 fremde Ressourcen je Seite; die IP des Besuchers erreicht keinen Dritten |
| Zugriffsregeln serverseitig | bestanden mit Anmerkung | öffentlich, **weil keine `access_control`-Regel passt** — wie im gesamten Bestand |
| Geheimnisse im Repository | bestanden | 0 Treffer in `src/Comparison/`, der Twig-Erweiterung und den Vorlagen |
| Eingaben | bestanden | eingeschleustes `<script>` erscheint **0×** roh im HTML |
| Löschen und Auskunft | *trifft nicht zu* | es entstehen keine personenbezogenen Daten |

## Fehler

### BUG-01 · Vergleichsseiten scrollen bei 320 px waagerecht — hoch

**Betrifft:** AK-25

**Reproduktion:**
1. `symfony server:start --no-tls --port=8901`
2. Browser mit Fensterbreite 320 px auf `http://127.0.0.1:8901/de/vergleich/google-maps`
3. In der Konsole: `window.scrollTo(500, 0); window.scrollX`

**Erwartet:** `0` — die Seite scrollt nicht waagerecht, nur die Tabelle in ihrem eigenen Bereich.

**Tatsächlich:** `212`. Ebenso `wheelmap` → `193`, `tripadvisor` → `211`.
`document.documentElement.scrollWidth` = 517 bei `clientWidth` = 305.

**Ort:** `templates/comparison/_table.html.twig:1–3` in Verbindung mit den langen
Halbsätzen aus `translations/comparison.*.yaml`.

**Eingegrenzt:**
- Die Übersicht `/de/vergleich` ist sauber (`scrollX=0`) — nur die Seiten mit Tabelle sind betroffen.
- Gegenprobe: `table { display: none }` → `scrollX=0`, `scrollWidth=305`. Die Tabelle ist die Ursache.
- Das übernommene Bestandsmuster ist **nicht** schuld: `/de/partner` nutzt denselben
  Scrollcontainer und scrollt nicht (`scrollX=0`). Dort ist die Tabelle 324 px breit,
  hier 525 px — der Unterschied sind die erklärenden Halbsätze in beiden Wertspalten,
  die es auf `/partner` nicht gibt (dort stehen nur Häkchen).
- `min-width: 0` an `<main>`, am Scrollcontainer und `display: block` am `<body>` ändern
  jeweils nichts.

**Vorschlag:** Die Merkmalstabelle braucht unter `md:` ein anderes Layout — etwa je
Merkmal eine Karte mit den beiden Werten untereinander statt drei Spalten nebeneinander.
Ein `table-layout: fixed` mit Umbruch in den Zellen wäre der kleinere Eingriff, macht die
Halbsätze aber sehr schmal.

**✅ Behoben am 2026-08-29.** Der kleinere Eingriff wurde ausprobiert und verworfen:
`overflow-wrap: anywhere` beseitigt das Scrollen (`scrollX=0`), zerlegt aber Wörter mitten
im Wort — „Stuf-enlo-ser Eing-ang" bei 64 px Spaltenbreite. Gebaut wurde deshalb
`templates/comparison/_cards.html.twig`: je Merkmal eine Karte mit beiden Werten
untereinander, `md:hidden`; die Tabelle trägt jetzt `hidden md:block`. Der gemeinsame
Inhalt einer Bewertung liegt in `_verdict_body.html.twig`, damit die
Screenreader-Mechanik nur einmal im Projekt steht.
**Nachweis:** 320 px → `scrollX=0` auf allen drei Vergleichsseiten sowie auf der
luxemburgischen und der französischen Fassung; 375 px, 1280 px ebenfalls 0.
**Regressionsschutz:** `ComparisonEdgeCaseTest::testZweiDarstellungenDieSichAusschliessen`
und `::testAuchDieKartenSagenDieBewertungAn`. Gegenprobe ausgeführt: Ohne `hidden md:block`
schlägt der Lauf mit „Die Tabelle ist auf schmalen Anzeigen nicht ausgeblendet" fehl.

### BUG-02 · Gruppen- und Bewertungsnamen fallen durch jeden Prüflauf — mittel

**Betrifft:** AK-28 (Kriterium selbst ist erfüllt; die zugesagte Absicherung hat eine Lücke)

**Reproduktion:**
1. `group.coverage` aus **allen vier** `translations/comparison.*.yaml` entfernen
2. `php bin/phpunit`
3. `GET /de/vergleich/google-maps`

**Erwartet:** Ein Prüflauf schlägt fehl.

**Tatsächlich:** Alle 594 Tests bleiben grün. Auf der Seite steht in der dritten
Gruppenzeile der rohe Schlüssel **`group.coverage`**.

**Ort:** `tests/Unit/Translation/ComparisonCatalogueTest.php:89–119`
(`schluesselAusDerRegistry()`) sammelt Schlüssel aus `ComparisonPage`, `ComparisonRow`
und `ComparisonSource`, aber **nicht** aus `ComparisonGroup::transKey()`
(`src/Comparison/ComparisonGroup.php:22`) und `Verdict::transKey()`
(`src/Comparison/Verdict.php:27`). Der Regex-Scanner in `CatalogueCompletenessTest:119`
erfasst nur Literale und sieht `{{ group.transKey|trans }}` nicht.

**Fehlt der Schlüssel nur in einer Sprache**, greift der Domain-Vergleich und der Lauf
wird rot — geprüft und belegt (AK-28). Die Lücke betrifft den Fall, dass eine neue
Gruppe oder ein neuer Bewertungsfall hinzukommt und die Übersetzung überall vergessen
wird. Das ist der BF-69-Fehlertyp: roher Schlüssel vor einem Besucher.

**Vorschlag:** In `schluesselAusDerRegistry()` zusätzlich über `ComparisonGroup::cases()`
und `Verdict::cases()` laufen und deren `transKey()` einsammeln.

**✅ Behoben am 2026-08-29.** Genau so umgesetzt.
**Nachweis:** Die Reproduktion greift nicht mehr — `group.coverage` aus allen vier
Katalogen entfernt, der Lauf schlägt jetzt mit „1 Schlüssel fehlen in comparison.lb.yaml"
fehl. Nach Wiederherstellung grün.

### BUG-03 · Zwei Landmarks auf jeder Vergleichsseite heißen gleich — mittel

**Betrifft:** AK-27 nicht direkt; berührt Feature `02`, AK-31 (Landmarks getrennt auffindbar)

**Reproduktion:** `GET /de/vergleich/google-maps`, `<nav>`-Bereiche im Hauptinhalt auslesen.

**Erwartet:** Zwei unterscheidbare Namen.

**Tatsächlich:** Beide heißen **„Weitere Vergleiche"** —
1. der Brotkrümelpfad oben (Inhalt: „Endlech.lu im Vergleich › Google Maps"),
2. die tatsächlichen Querverweise unten (Inhalt: „vs. Wheelmap · vs. TripAdvisor").

Wer per Landmark navigiert, hört denselben Namen zweimal, und der erste führt zu etwas
anderem, als er ankündigt.

**Ort:** `templates/comparison/show.html.twig:28` verwendet `cross.title` als
`aria-label`; derselbe Schlüssel ist in `templates/comparison/_cross_links.html.twig:5`
die sichtbare Überschrift.

**Vorschlag:** Eigener Schlüssel `breadcrumb.label` (etwa „Sie sind hier") in den vier
Katalogen; `cross.title` bleibt der Querverweisliste vorbehalten.

**✅ Behoben am 2026-08-29.** `breadcrumb.label` in vier Sprachen angelegt
(„Sie sind hier" / „You are here" / „Vous êtes ici" / „Dir sidd hei").
**Nachweis:** Die beiden Landmarks heißen jetzt „Sie sind hier" und „Weitere Vergleiche".
**Regressionsschutz:** `ComparisonEdgeCaseTest::testLandmarksTragenVerschiedeneNamen`.
Gegenprobe ausgeführt: Mit `cross.title` schlägt der Lauf mit „Zwei Navigationsbereiche
heißen gleich" fehl.

### BF-80 · Alle Seiten des Projekts scrollen bei 768 px waagerecht — mittel

**Betrifft:** kein AK dieses Features. Berührt Feature `02` (Barrierefreiheit der
Plattform, Status `approved`).

**Reproduktion:** Browser mit 768 px Breite auf `http://127.0.0.1:8901/de/`, dann
`window.scrollTo(500,0); window.scrollX`.

**Erwartet:** `0`. **Tatsächlich:** `51` — auf `/de/`, `/de/about`, `/de/open`,
`/de/restaurants`, `/de/criteria`, `/de/legal` und den Vergleichsseiten gleichermaßen.

**Zugeordnet:** Mit ausgeblendeter Merkmalstabelle bleiben es 51 px; mit ausgeblendetem
`<header>` sind es **0**. Feature 03 trägt nichts bei.

**Ort:** `templates/base.html.twig` — `div.flex items-center gap-4` in der Kopfzeile,
Breite 250 px, rechte Kante bei 804 px statt 753. Bei genau 768 px greift die
Desktop-Navigation (`md:`), der Platz reicht aber nicht, und `flex-wrap: nowrap`
verhindert den Umbruch.

**Vorschlag:** Den Umschaltpunkt der Kopfzeile auf `lg:` heben oder der rechten Gruppe
`flex-wrap: wrap` erlauben. Gehört in einen eigenen Durchgang für Feature `02`.

### BF-81 · Fußnoten der Kartendarstellung waren ungeprüft — mittel · **geschlossen**

**Betrifft:** AK-12 (Kriterium selbst war und ist erfüllt; der Regressionsschutz war halb)

**Reproduktion:** In `templates/comparison/_cards.html.twig` `source: row.sourceRef` auf
`source: null` setzen, dann `php bin/phpunit`.

**Erwartet:** Ein Prüflauf schlägt fehl. **Tatsächlich (vorher):** Alle 606 Tests blieben
grün, während die Kartendarstellung alle 18 Fußnotenlinks verlor. Auf schmalen Anzeigen
hätten die Aussagen über den Wettbewerber unbelegt dagestanden.

**Ort:** `tests/Functional/Controller/ComparisonEdgeCaseTest.php` — der Selektor lautete
`table a[href^="#quelle-"]` und sah nur die Tabelle.

**Behandlung:** In dieser QA geschlossen (Tests zu schreiben ist die eine Ausnahme dieses
Skills). Neuer Prüflauf `testBeideDarstellungenBelegenIhreAussagen` vergleicht die
Fußnotenmengen beider Darstellungen; der bestehende Test filtert nicht mehr auf `table`.
**Gegenprobe:** Der Rückbau schlägt jetzt mit „Die Kartendarstellung belegt keine einzige
Aussage" fehl.

### BF-82 · Sehr langes Wort ohne Leerzeichen sprengt die Karte — niedrig

**Betrifft:** EC-05 (bei realistischen Namen erfüllt)

**Reproduktion:** Bei 320 px den Anbieternamen in der Kartendarstellung durch ein Wort
von 57 Zeichen **ohne Leerzeichen** ersetzen.

**Erwartet:** `scrollX=0`. **Tatsächlich:** `104`.

**Einordnung:** Bis 30 Zeichen — auch als einziges Wort ohne Leerzeichen — bleibt alles
bei 0. Die drei realen Wortmarken sind 8 bis 11 Zeichen lang, ein realistischer
Kandidat wie „Google Business Profile" (23) oder „Mobiliteit.lu Barrierefreiheit" (30)
ebenfalls sauber. Der Fall tritt erst bei einem konstruierten Namen ein.

**Ort:** `templates/comparison/_cards.html.twig` — die `<dt>`-Elemente haben kein
`overflow-wrap`.

**Vorschlag:** `break-words` an den `<dt>` der Kartendarstellung. Einzeiler; lohnt sich,
wenn ohnehin jemand die Datei anfasst.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Integration/Comparison/ComparisonFiguresTest.php` | 4 | AK-16 (gleiche Quelle wie `/open`), AK-17 (Zahl folgt der Datenlage), AK-18 (zweiter Aufruf rechnet nicht neu), EC-02 (kein Bestand ergibt 0) |
| `tests/Functional/Controller/ComparisonEdgeCaseTest.php` | 1 | AK-12 in **beiden** Darstellungen (BF-81) |

## Hinweise ohne Befundcharakter

- **Der Asset-Stand war zwischenzeitlich inkonsistent.** `public/build/entrypoints.json`
  verwies auf `/build/app.css` und `/build/runtime.js` — Dev-Build-Namen, die es nicht
  gab; die Seite lud ohne Styling. Ein erneutes `npm run build` stellte es her. Ursache
  vermutlich ein stiller Fehlschlag beim Bau (`npm run build > /dev/null 2>&1`
  verschluckt den Exit-Code). **Vor dem Ausliefern gehört der Bau ohne Ausgabe-Umleitung
  ausgeführt und `git status public/build` geprüft.**
- **Der PHP-Built-in-Server taugt nicht für Darstellungsprüfungen.** Er liefert CSS mit
  `Content-Type: text/html`, der Browser verwirft das Stylesheet, und jede Messung von
  Breiten ist wertlos. Eine erste Messung von AK-25 lief genau in diese Falle
  (`scrollWidth` 10008 statt 517). Für Browser-Prüfungen `symfony server:start` nehmen.
- **Die Testsuite meldet einen fehlenden Datenbankserver als 264 Fehler**, nicht als
  Verbindungsfehler. Betrifft das ganze Projekt, nicht dieses Feature.

## Nächster Schritt

**`/sdd-deploy 03`.** Kein Befund an diesem Feature blockiert.

Mitzunehmen ist:

- **BF-80** gehört zu Feature `02` und wartet auf einen eigenen Durchgang. Es blockiert
  `03` nicht, ist aber auf jeder Seite des Projekts sichtbar.
- **BF-82** (niedrig) lohnt einen Einzeiler, wenn jemand `_cards.html.twig` ohnehin anfasst.
- **OF-02 bleibt offen:** In welchem Rhythmus die 18 Quellen nachgeprüft werden. Alle
  Prüfdaten stehen auf dem 28. August 2026 und altern gemeinsam. Ohne festen Termin ist
  die Zusage aus AK-12 nur für den Tag der Veröffentlichung wahr.
- **Vor dem Ausliefern:** `npm run build` **ohne** Ausgabe-Umleitung ausführen und
  `git status public/build` prüfen (Hinweis aus dem ersten Durchlauf).
