# 05 · Presse-Kit — Aufgabenplan

Status: `tasked` · Stand: 2026-08-30

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation. **Rot heißt anhalten.**

```bash
make fix-check                          # Exit 8 = etwas zu tun, nicht 1
php bin/console cache:warmup            # statt lint:container — siehe unten
php bin/console lint:twig templates/
php bin/phpunit --testsuite Unit
```

⚠ **`lint:container` ist in diesem Projekt vorbestehend rot** (Webauthn-Alias-Altlast) und
taugt nicht als Ebenen-Gate — das ist beim Bau von Feature `04` festgestellt worden.
`cache:warmup` steht an seiner Stelle: Es baut denselben Container und schlägt fehl, wenn
ein Parameter oder ein Dienst nicht auflösbar ist.

⚠ **`doctrine:schema:validate` entfällt.** Dieses Feature legt keine Entität und keine
Tabelle an; ein Schema-Abgleich prüfte hier nichts und würde vorbestehende Abweichungen
melden, die nichts mit dem Feature zu tun haben.

**Es gibt keine Migration.** Ebene 1 ist Konfiguration, Grundstruktur und Material.

---

## Ebene 1 · Fundament — Konfiguration, Grundstruktur, Material

- [x] **T01** · Aufzählungen in `src/Press/`: `BoilerplateLength` (drei Fälle, **jeder mit
      seiner Wortunter- und -obergrenze als Bestandteil des Falls**) und `PressAssetKind`
      (fünf Fälle: Wortbildmarke hell/dunkel, Bildmarke hell/dunkel, Porträt)
      — *Grundlage für T11, T12, T15, T16*
- [x] **T02** · Unveränderliche Wertobjekte in `src/Press/`: `PressAsset` (mit
      `creditKey`, Pflicht beim Porträt), `PressQuote`, `PressRelease`; dazu das Gerüst
      von `PressRegistry` mit leeren Listen — *Grundlage für T11–T13*
- [x] **T03** · Übersetzungsdomain `press` anlegen: vier Dateien
      `translations/press.{lb,de,fr,en}.yaml` mit dem gemeinsamen Gerüst (Seitentitel,
      Kurzbeschreibung, sieben Abschnittsüberschriften, Beschriftungen des Faktenblatts,
      Antwortzeit- und Interviewhinweis); dazu zwei neue Schlüssel in
      `messages.{lb,de,fr,en}.yaml` (Fußzeilen-Link, Teaser auf `/about`) und die Domain
      `press` in `CatalogueCompletenessTest::DOMAINS` — `AK-06, AK-30, AK-43`
- [x] **T04** · Vier Parameter in `config/services.yaml` (`app.operator_name`,
      `app.operator_address`, `app.operator_responsible`, `app.press_email` mit Rückfall
      auf `support@endlech.lu`) plus `PRESS_EMAIL` als dokumentierter Vorgabewert in
      `.env`. ⚠ **Blockiert durch VB-03** — ohne die tatsächlichen Angaben entsteht hier
      ein Platzhalter, der auf zwei Seiten erscheint — `AK-11, AK-15, AK-28`
- [x] **T05** · Sprachfreie Route `app_press_redirect` in `config/routes.yaml` nach dem
      Muster von `app_open_redirect` — `AK-05`
- [x] **T06** · `ext-zip` in `composer.json` unter `require-dev` **und** `zip` in die
      Extension-Liste von `.github/workflows/ci.yml` (Zeile 38). ⚠ Ohne diesen Schritt
      wird T17 auf dem Runner rot — mit einer Meldung über eine unbekannte Klasse, die wie
      ein Codefehler aussieht und keiner ist — *Grundlage für T14, T17*
- [x] **T07** *(2026-08-30 erledigt: `logo.png` mit potrace nachgezeichnet, 0,244 % Abweichung gemessen; vier SVG abgelegt, `make press-kit` gelaufen)* · **Vorbedingung VB-01, keine Programmierarbeit:** die vier Vektormarken als
      `public/presse/endlech-wortbildmarke.svg`, `…-invers.svg`, `endlech-bildmarke.svg`,
      `…-invers.svg` ablegen und committen. Solange sie fehlen, stehen T12, T14 und T17
      still — *Grundlage für T12, T14, T17*
- [x] **T08** *(2026-08-30 vom Betreiber eingerichtet; MX für endlech.lu: Hostinger + ImprovMX, SPF nennt beide)* · **Vorbedingung VB-02, Konfigurationsaufgabe:** `support@endlech.lu` beim
      Hoster einrichten und mit einer Testmail belegen, dass sie ankommt. Kein Code;
      Nachweis gehört als Beleg in den Testbericht — `AK-29`

## Ebene 2 · Server — Inhalte, Paket und Prüfläufe

- [x] **T09** `[P]` · `App\Press\PressFacts`: liest **ausschließlich**
      `OpenStatsService::platform()` und stellt erfasste Lokale, davon verifiziert und
      abgedeckte Gemeinden bereit. Kein eigener Zwischenspeicher, kein `all()` —
      `AK-12, AK-13, AK-14`
- [x] **T10** `[P]` · `App\Press\PressPackage`: öffentlicher Pfad, `exists`, `sizeBytes`.
      Ein Dateisystemzugriff je Seitenaufruf; er ersetzt eine Zahl im Katalog und trägt
      zugleich den Fehlerzustand — `AK-20`
- [x] **T11** · `PressRegistry`: die drei Beschreibungstexte in allen vier Sprachen,
      Wortzahlen innerhalb der Grenzen aus `BoilerplateLength` — `AK-07, AK-08, AK-10`
- [x] **T12** · `PressRegistry`: die fünf Materialeinträge (vier Marken aus T07, Porträt
      aus `uploads/team/michael.jpg`) mit Bezeichnung, Format und Dunkelkennzeichen; dazu
      die Nutzungsbedingungen als Katalogtexte in vier Sprachen, getrennt nach erlaubt und
      nicht erlaubt — `AK-16, AK-18, AK-21, AK-35`
- [x] **T13** · `PressRegistry`: Kurzvita mit Fotocredit, mindestens zwei freigegebene
      Zitate mit Name und Funktion, die Meldungsliste (leer zulässig).
      ⚠ **Die Angabe zur Behinderung steht in genau einem Katalogschlüssel** (`press.bio`)
      und in keinem weiteren — `AK-23, AK-24, AK-25, AK-26, AK-37`
- [x] **T14** *(Befehl fertig; ⛔ das Paket selbst fehlt, weil VB-01 fehlt — der Befehl bricht bewusst ab)* · Konsolenbefehl `app:press:package` in `src/Command/`: packt die Einträge
      aus `PressRegistry::assets()` plus eine aus dem Übersetzer gerenderte Datei
      `NUTZUNGSBEDINGUNGEN.txt` (vier Sprachabschnitte) zu
      `public/presse/presse-kit-endlech-lu.zip`; dazu ein Makefile-Ziel `press-kit`. Das
      erzeugte Paket wird **committet** — `AK-17, AK-19, AK-22`
- [x] **T15** · Prüflauf `tests/Unit/Press/PressRegistryTest.php`: drei Längen belegt,
      jede der fünf Materialarten genau einmal, mindestens zwei Zitate, Fotocredit am
      Porträt vorhanden — `AK-07, AK-18, AK-24, AK-25`
- [x] **T16** · Prüflauf `tests/Unit/Translation/PressCatalogueTest.php`: jeder in
      `App\Press\` genannte Schlüssel ist in allen vier Katalogen definiert und nicht
      leer; die Wortzahlen der drei Beschreibungstexte liegen je Sprache in den Grenzen
      des Enums; **kein anderer Schlüssel der Domain als `press.bio` enthält die
      Gesundheitsangabe**. Gegenprobe mit einem absichtlich falschen Schlüssel, ob der
      Lauf rot wird — `AK-08, AK-30, AK-31, AK-37`
- [x] **T17** *(Prüflauf fertig; überspringt mit benannter Meldung, solange VB-01 offen ist)* · Prüflauf `tests/Unit/Press/PressPackageTest.php`: öffnet die committete
      Paketdatei, vergleicht ihre Einträge mit `PressRegistry::assets()` plus der
      Bedingungsdatei, prüft den Dateinamen und dass die Bedingungsdatei nicht leer ist —
      `AK-17, AK-18, AK-19, AK-22, EC-04`
- [x] **T18** `[P]` · Prüflauf `tests/Integration/Press/PressFactsTest.php`: dieselben
      Zahlen wie `OpenStatsService::platform()`, und ein zweiter Aufruf rechnet nicht
      erneut — `AK-12, AK-13, AK-14`

⚠ **T11, T12 und T13 sind bewusst nicht parallel.** Alle drei schreiben in dieselbe
`PressRegistry` und in dieselben vier Katalogdateien. Der Zeitfresser ist das Formulieren,
nicht das Eintragen.

⚠ **T11–T13 überschreiten die Drei-Dateien-Grenze** (Registry plus vier Kataloge). Der
Schnitt ist trotzdem richtig: Ein Registry-Eintrag ohne seinen Text in vier Sprachen ist
nicht überprüfbar, und ein Schnitt zwischen Struktur und Text ließe T16 bis zum Ende der
Ebene rot stehen.

## Ebene 3 · Schnittstellen

- [x] **T19** · `src/Controller/PressController.php` mit `index()`, Klassen-Attribut
      `#[Route('/presse')]`, Routenname `app_press_index`. Liest `PressRegistry`,
      `PressFacts`, `PressPackage` und die vier Parameter; **liest keinen
      Query-Parameter** — `AK-02, AK-39`
- [x] **T20** · Prüflauf: der Router kennt **keine** Route auf
      `/presse/presse-kit-endlech-lu.zip` — das Paket wird vom Webserver ausgeliefert, es
      gibt nichts zu deckeln, weil nichts gerechnet wird — `AK-40`

⚠ **Keine `access_control`-Zeile.** Die Seite ist öffentlich, weil keine Regel auf sie
passt — wie `/about`, `/partner`, `/open` und `/vergleich`. Eine eigene Zeile für dieses
eine Feature ließe die übrigen öffentlichen Seiten so aussehen, als seien sie anders
behandelt. Steht als Entscheidung im Entwurf; hier keine Aufgabe.

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände. Hier treten **leer** (keine Meldung, T29) und **Fehler**
(Paketdatei fehlt, T27) wirklich auf; **ladend** tritt nicht auf, weil serverseitig
gerendert und nichts nachgeladen wird. Das ist im Entwurf begründet und keine Auslassung.

- [x] **T21** `[P]` · `templates/base.html.twig`: elfter Eintrag in Fußzeilenspalte 2,
      „Presse" → `app_press_index` — `AK-01`
- [x] **T22** `[P]` · `templates/about/index.html.twig`: Abschnitt `#presse-teaser` am
      Ende, drei Zeilen und ein Link auf `/presse` — `AK-03`
- [x] **T23** *(Schlüssel `legal.info_text` bewusst behalten — er trägt den Zustand vor VB-03)* `[P]` · `templates/impressum/index.html.twig`: Betreiberangaben aus den
      Parametern statt aus `legal.info_text`; den Schlüssel in allen vier Katalogen
      entfernen (Template und Katalog **in derselben Aufgabe**, sonst steht das Impressum
      dazwischen leer) — `AK-15`
- [x] **T24** `[P]` · `templates/press/index.html.twig`: Dokumentkopf füllt die
      vorhandenen Blöcke `meta_description` und `canonical`; Kopfband mit **einer** H1 als
      `<section>` (Druckregel), Sprungmarken; die sieben Abschnitte als `<section>` mit
      stabiler `id` (`boilerplate`, `fakten`, `material`, `person`, `zitate`, `meldungen`,
      `kontakt`) — `AK-06, AK-34, AK-42, AK-43, AK-44`
- [x] **T25** `[P]` · `templates/press/_boilerplate.html.twig`: drei Textkarten mit
      Längenangabe, Text offen im Markup — **kein `<details>`, kein Skript, kein
      Kopier-Knopf** — `AK-07, AK-09`
- [x] **T26** `[P]` · `templates/press/_facts.html.twig`: Faktenblatt als
      Beschreibungsliste (**keine Tabelle**, siehe Entwurf), drei Livezahlen über
      `format_number`, darunter die Stammdaten aus den Parametern und den festen Angaben
      — `AK-11, AK-33, AK-36`
- [x] **T27** `[P]` · `templates/press/_material.html.twig`: fünf Vorschaukacheln
      (dunkler Grund bei den Inversvarianten, Alternativtext aus der Bezeichnung),
      Download-Knopf mit Format und Größe im Linktext, Nutzungsbedingungen als zwei
      Listen; **fehlt die Paketdatei, tritt der Verweis auf den Pressekontakt an die
      Stelle des Knopfes** — `AK-16, AK-20, AK-21, AK-35, EC-04`
- [x] **T28** `[P]` · `templates/press/_person.html.twig` und `_quotes.html.twig`:
      Porträt mit Fotocredit und Nutzungshinweis, Kurzvita; Zitatkarten mit Name, Funktion
      und Freigabehinweis — `AK-23, AK-24, AK-25`
- [x] **T29** `[P]` · `templates/press/_releases.html.twig`: Meldungen mit Datum über
      `format_date`, neueste zuerst; **ohne Meldung** steht der Abschnitt trotzdem da, mit
      Hinweis und Verweis auf den Pressekontakt — `AK-26, AK-27`
- [x] **T30** `[P]` · `templates/press/_contact.html.twig`: Mailto auf
      `app.press_email`, zugesagte Antwortzeit, Hinweis auf Interviewanfragen —
      `AK-28`
- [x] **T31** *(OperatorDetailsTest überspringt die Gleichheitsprüfung, solange VB-03 offen ist)* · Funktionale Prüfläufe, **nach** den Vorlagen:
      `tests/Functional/Controller/PressControllerTest.php` (200 in vier Sprachen,
      sprachfreier Kurzlink, Sprachwechsel bleibt auf `/presse`, eigener Fenstertitel,
      sieben Abschnitts-`id`s je Sprache, leerer Meldungszustand, Kurzbeschreibung und
      kanonische Adresse, Zusatzparameter ohne Wirkung, geschlossene Angabenmenge) und
      `tests/Functional/Controller/OperatorDetailsTest.php` (Name, Anschrift und
      Verantwortlicher auf `/presse` und `/legal` zeichengenau gleich) —
      `AK-02, AK-04, AK-05, AK-06, AK-15, AK-27, AK-36, AK-39, AK-42, AK-43, AK-44`

## Ebene 5 · Feinschliff

- [x] **T32** · Barrierefreiheit: `/presse` in `AccessibilityStructureTest::publicRoutes()`
      eintragen; Überschriftenhierarchie prüfen (eine H1, sieben H2, Karten H3);
      `motion-safe:` an jedem Übergang, `min-h-[48px]` an jeder Aktion; bei 320 px keine
      waagerechte Scrollleiste der Seite — `AK-32, AK-33, AK-34`
- [x] **T33** · Prüflauf gegen fremde Ressourcen: die gerenderte Seite enthält kein
      `<img>`, kein `<script src>`, kein `<link href>` und kein `url(...)` mit fremdem
      Host — `AK-38`
- [x] **T34** *(EC-06 und EC-10 nicht prüfbar, solange keine Meldung existiert — OF-06)* · Randfälle durchspielen: ohne JavaScript (EC-01), null erfasste
      Restaurants (EC-02), leerer Zwischenspeicher beim ersten Aufruf (EC-03), fehlende
      Paketdatei (EC-04), Druckansicht — Kopfband als `<section>`, sonst weiß auf weiß
      (EC-05), langer Meldungstitel (EC-06), 320 px (EC-07), Vorschau ohne Paketeintrag
      (EC-08), Zitat zurückgezogen (EC-09), Datumsformat auf Luxemburgisch (EC-10) —
      `EC-01 bis EC-10`
- [x] **T35** · Die Service-Worker-Regel festhalten: Kommentar an der Materialliste **und**
      Zeile in `CLAUDE.md` — wer eine Datei in `public/presse/` ersetzt, erhöht
      `CACHE_VERSION` in `public/sw.js`. Ohne das zeigt ein wiederkehrender Besucher die
      alte Vorschau neben dem neuen Paket — *schützt AK-17, kein eigenes AK*
- [x] **T36** · Dokumentation nachziehen: `docs/app-shell.md` (Fußzeilenspalte 2 trägt elf
      Einträge statt zehn) und `docs/data-model.md` (ein Satz: Feature 05 bringt keine
      Entität — damit niemand eine fehlende Tabelle sucht) — *Dokumentation, kein AK*
- [x] **T37** *(gebaut; der Commit selbst liegt außerhalb von sdd-build)* · `npm run build` ausführen und `public/build` mitcommitten. **Pflicht auch
      bei reinen Twig-Änderungen** — Tailwind liest seine Klassen aus den Vorlagen, und
      `verify-assets` blockt sonst den Deploy — *Deploy-Voraussetzung, kein AK*

---

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T21 |
| AK-02 | T19, T31 |
| AK-03 | T22 |
| AK-04 | T31 |
| AK-05 | T05, T31 |
| AK-06 | T03, T24, T31 |
| AK-07 | T11, T15, T25 |
| AK-08 | T11, T16 |
| AK-09 | T25 |
| AK-10 | T11, T16 |
| AK-11 | T04, T26 |
| AK-12 | T09, T18 |
| AK-13 | T09, T18 |
| AK-14 | T09, T18 |
| AK-15 | T04, T23, T31 |
| AK-16 | T12, T27 |
| AK-17 | T14, T17 |
| AK-18 | T12, T15, T17 |
| AK-19 | T14, T17 |
| AK-20 | T10, T27 |
| AK-21 | T12, T27 |
| AK-22 | T14, T17 |
| AK-23 | T13, T28 |
| AK-24 | T13, T15, T28 |
| AK-25 | T13, T15, T28 |
| AK-26 | T13, T29 |
| AK-27 | T29, T31 |
| AK-28 | T04, T30 |
| AK-29 | T08 |
| AK-30 | T03, T16 |
| AK-31 | T16 |
| AK-32 | T32 |
| AK-33 | T26, T32 |
| AK-34 | T24, T32 |
| AK-35 | T12, T27 |
| AK-36 | T26, T31 |
| AK-37 | T13, T16 |
| AK-38 | T33 |
| AK-39 | T19, T31 |
| AK-40 | T20 |
| AK-41 | **keine Aufgabe** — siehe unten |
| AK-42 | T24, T31 |
| AK-43 | T03, T24, T31 |
| AK-44 | T24, T31 |

**AK ohne Aufgabe:** **AK-41** — und das ist eine Entscheidung, kein Versehen. Das
Kriterium lautet: Ein Beitrag entsteht allein mit `/presse`, ohne eine Mail zu schreiben.
Es prüft das Zusammenspiel aller anderen Kriterien und lässt sich nicht bauen, sondern nur
abnehmen. Es steht deshalb in `design.md` unter *Prüfläufe* als **Handprüfung in
`sdd-qa`** — mit derselben Behandlung wie AK-29, dessen Nachweis eine Testmail ist.
Alle übrigen 43 Kriterien sind einzeln durchgegangen und haben mindestens eine Aufgabe.

**Aufgabe ohne AK:** T01, T02 (Grundlage für T11–T13), T06 (Grundlage für T14 und T17),
T07 (Material aus VB-01, Grundlage für T12, T14, T17), T34 (trägt Randfälle statt
Kriterien, siehe die Zeile darunter), T35 (Konvention, die AK-17 im Browser schützt),
T36 (Dokumentation), T37 (Deploy-Voraussetzung). Alle acht zulässig — gegengezählt: Die
Abdeckungstabelle nennt 29 der 37 Aufgaben, diese Liste die übrigen acht.

**Edge Cases:** EC-01 bis EC-10 in T34, EC-04 zusätzlich in T17 und T27. Keiner ohne
Aufgabe.

## Parallelisierung

**Ebene 2:** T09, T10 und T18 laufen gleichzeitig.

| Aufgabe | Dateien |
|---|---|
| T09 | `src/Press/PressFacts.php` |
| T10 | `src/Press/PressPackage.php` |
| T18 | `tests/Integration/Press/PressFactsTest.php` |

T18 prüft, was T09 baut — es läuft trotzdem gefahrlos daneben, weil beide getrennte
Dateien schreiben; rot ist der Lauf erst bei der Verifikation **nach** der Ebene, und
dort muss T09 fertig sein.

**Ebene 4:** T21 bis T30 laufen gleichzeitig — zehn verschiedene Dateien.

| Aufgabe | Datei |
|---|---|
| T21 | `templates/base.html.twig` |
| T22 | `templates/about/index.html.twig` |
| T23 | `templates/impressum/index.html.twig` + `translations/messages.*.yaml` |
| T24 | `templates/press/index.html.twig` |
| T25 | `templates/press/_boilerplate.html.twig` |
| T26 | `templates/press/_facts.html.twig` |
| T27 | `templates/press/_material.html.twig` |
| T28 | `templates/press/_person.html.twig`, `_quotes.html.twig` |
| T29 | `templates/press/_releases.html.twig` |
| T30 | `templates/press/_contact.html.twig` |

T24 bindet die Bausteine aus T25–T30 ein, die es während der Ebene noch nicht gibt. Das
ist zulässig: `lint:twig` läuft **nach** der Ebene, nicht während. T23 ist die einzige
Aufgabe der Ebene, die eine Katalogdatei anfasst — alle anderen Schlüssel stehen seit T03
bereit. Genau deshalb liegt T03 in Ebene 1.

**Ausdrücklich nicht parallel:**

| Aufgaben | Warum |
|---|---|
| T11, T12, T13 | alle drei schreiben in `PressRegistry` und dieselben vier Katalogdateien |
| T14 nach T07 und T12 | ein Paket aus einer Liste, deren Dateien noch fehlen, ist entweder leer oder bricht ab |
| T15, T16, T17 nach T11–T14 | Prüfläufe gegen unvollständige Inhalte sind zwangsläufig rot |
| T31 nach T21–T30 | die funktionalen Läufe rendern die Seite; ohne Vorlagen prüfen sie eine Fehlerseite |
| T01, T02, T03 | T02 nutzt die Aufzählungen aus T01, T03 benennt Schlüssel, die T01 und T02 vorgeben |

## Vor dem Bauen

- [x] Feature-Branch: `git checkout -b feature/05-presse-kit`
- [x] Keine Migration nötig — es entsteht keine Tabelle
- [x] Keine Schlüssel nötig — das Feature ruft keinen externen Dienst
- [x] Testdatenbank steht: `make test-db-setup` (einmalig)
- [x] Docker läuft (`make start`), sonst scheitern die funktionalen Prüfläufe an der
      Datenbankverbindung
- [x] `php -m | grep zip` lokal vorhanden (geprüft am 2026-08-30: PHP 8.5.2 mit `zip`)

**Drei Vorbedingungen blockieren, und sie blockieren verschiedene Stellen:**

| # | Blockiert | Ohne sie passiert |
|---|---|---|
| **VB-01** · vier Vektormarken | T07 → T12, T14, T17 | das Paket entsteht leer, drei Prüfläufe bleiben rot |
| **VB-02** · `support@endlech.lu` | T08 (AK-29) | die Seite nennt eine Adresse, die niemand liest — der schlechteste aller Zustände |
| **VB-03** · Betreiberangaben | T04 → T23, T26, T31 | Impressum und Faktenblatt tragen einen Platzhalter, und AK-15 ist formal erfüllt, während beide Seiten dasselbe Falsche sagen |

**Zwei offene Fragen blockieren einzelne Aufgaben, nicht den Bau:**

- **OF-03** (zugesagte Antwortzeit) → T30. Vorschlag aus der Spec: „innerhalb von zwei
  Werktagen". Bis zur Entscheidung ist der Katalogschlüssel der Platzhalter.
- **OF-05** (Fotocredit) → T13. `creditKey` ist beim Porträt Pflicht; T15 schlägt ohne ihn
  fehl. Das ist gewollt — ein Bild ohne Credit ist nicht freigabefähig.

## Hinweis für T11 bis T13

Die Texte sind der eigentliche Inhalt dieses Features, und sie sind redaktionelle Arbeit.
Was der Plan festlegt, sind Zahl, Länge und Sprachen — nicht der Wortlaut.

⚠ **Beim Boilerplate zählt die Wortzahl je Sprache, nicht nur auf Deutsch.** Französisch
braucht regelmäßig 15–20 % mehr Wörter als Deutsch; ein Text, der auf Deutsch mit 28
Wörtern in der Grenze liegt, sprengt sie auf Französisch. T16 prüft alle vier — wer nur
eine Sprache formuliert und übersetzen lässt, läuft dort auf.

⚠ **Die Angabe zur Behinderung gehört in genau einen Schlüssel.** Sie im Boilerplate zu
wiederholen wäre naheliegend — es ist das stärkste Argument des Textes — und macht AK-37
unmöglich: Der Widerruf wäre dann keine Textstelle mehr, sondern drei.


---

## Stand nach `/sdd-build 05` (2026-08-30)

**35 von 37 Aufgaben erledigt.** Offen bleiben **T07** (die vier Vektormarken, VB-01)
und **T08** (das Postfach `support@endlech.lu`, VB-02) — beide entstehen außerhalb des
Quelltexts und waren als blockierend angekündigt.

Verifikation nach jeder Ebene: `cache:warmup`, `lint:twig`, `lint:yaml`, PHPUnit.
**735 Tests, 3147 Zusicherungen, grün** — 13 übersprungen, davon vier aus diesem Feature
(drei Paket-Läufe wegen VB-01, ein Gleichheitslauf wegen VB-03) und neun vorbestehend.

⚠ `make fix-check` existiert in diesem Projekt nicht (das Makefile kennt nur `fix`, und
PHP-CS-Fixer ist nicht installiert). An seiner Stelle liefen `php -l` je Datei und
`cache:warmup` als Container-Gate — Letzteres statt `lint:container`, das hier
vorbestehend rot ist.
