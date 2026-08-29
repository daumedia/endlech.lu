# 03 · Vergleichsseiten — Aufgabenplan

Status: `tasked` · Stand: 2026-08-28

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation aus dem Stack-Profil. **Rot heißt anhalten.**

```bash
make fix-check                          # Exit 8 = etwas zu tun, nicht 1
php bin/console lint:container
php bin/console lint:twig templates/
php bin/phpunit --testsuite Unit
```

**Es gibt keine Migration.** Dieses Feature legt keine Tabelle und kein Feld an; Ebene 1
ist deshalb Konfiguration und Grundstruktur, nicht Schema.

## Ebene 1 · Fundament — Konfiguration und Grundstruktur

- [x] **T01** · Aufzählungen `Competitor` (vier Fälle mit `slug()`, `fromSlug()`,
      Wortmarke als fester Text), `Verdict` (`YES`/`NO`/`PARTIAL`) und `ComparisonGroup`
      (vier Fälle) in `src/Comparison/` — *Grundlage für T03, T06–T09*
- [x] **T02** · Unveränderliche Wertobjekte `ComparisonRow`, `ComparisonSource`,
      `ComparisonPage` in `src/Comparison/`; `ownNoteKey` und `theirNoteKey` sind
      **Pflichtfelder**, `sourceRef` ist Pflicht, sobald `theirs` eine Aussage über den
      Wettbewerber trifft — *Grundlage für T06–T09*
- [x] **T03** · Übersetzungsdomain `comparison` anlegen: vier Dateien
      `translations/comparison.{lb,de,fr,en}.yaml` mit dem **gemeinsamen** Gerüst
      (Seitentitel je Seite, Kurzbeschreibungen, Tabellenkopf, Gruppennamen, die drei
      Verdict-Ansagen, Markenhinweis, Kontaktverweis) und die vier Fußzeilenschlüssel in
      `messages.{lb,de,fr,en}.yaml`; Domain `comparison` in die geprüfte Liste von
      `CatalogueCompletenessTest` eintragen — `AK-19, AK-20, AK-28`
- [x] **T04** · Sprachfreie Route `app_comparison_redirect` in `config/routes.yaml` nach
      dem Muster von `app_open_redirect` — `AK-31`

## Ebene 2 · Server — Logik und Inhalte

- [x] **T05** `[P]` · `ComparisonFigures` liest **ausschließlich**
      `OpenStatsService::platform()` und stellt erfasste Lokale, davon verifiziert und
      abgedeckte Gemeinden bereit; kein eigener Zwischenspeicher, kein `all()` —
      `AK-16, AK-17, AK-18`
- [x] **T06** · `ComparisonRegistry`: Vergleich **Google Maps** — Merkmalszeilen in allen
      vier Gruppen, mindestens drei Vorteile des Wettbewerbers, mindestens vier häufige
      Fragen, jede Aussage über Google Maps mit Quelle und Prüfdatum; dazu die vier
      Sprachfassungen der Texte — `AK-07, AK-08, AK-09, AK-10, AK-11, AK-12, AK-13`
- [x] **T07** · dasselbe für **Wheelmap** —
      `AK-07, AK-08, AK-09, AK-10, AK-11, AK-12, AK-13`
- [x] **T08** · dasselbe für **TripAdvisor** —
      `AK-07, AK-08, AK-09, AK-10, AK-11, AK-12, AK-13`
- [x] **T09** · dasselbe für **Jaccede** —
      `AK-07, AK-08, AK-09, AK-10, AK-11, AK-12, AK-13`
- [x] **T10** · Prüflauf `tests/Unit/Comparison/ComparisonRegistryTest.php`: je Vergleich
      alle vier Gruppen belegt, kein leerer Halbsatz, mindestens drei Vorteile, mindestens
      vier Fragen, jede Zeile mit Aussage über den Wettbewerber hat eine **auflösbare**
      Fußnote, jede Quelle hat ein Datum, die Abdeckungszeile ist vorhanden —
      `AK-08, AK-09, AK-10, AK-11, AK-12, AK-13`
- [x] **T11** · Prüflauf `tests/Unit/Translation/ComparisonCatalogueTest.php`: jeder in
      `App\Comparison\` genannte Textschlüssel ist in **allen vier** Katalogen definiert
      und nicht leer. Gegenprobe mit einem absichtlich falschen Schlüssel, ob der Lauf rot
      wird — `AK-28, AK-29`

⚠ **T06–T09 sind bewusst nicht parallel.** Sie schreiben alle in dieselbe
`ComparisonRegistry` und in dieselben vier Katalogdateien. Der Zeitfresser ist ohnehin die
Recherche, nicht das Eintragen.

⚠ **T06–T09 überschreiten die Drei-Dateien-Grenze** (Registry plus vier Kataloge). Der
Schnitt ist trotzdem richtig: Eine Merkmalszeile ohne ihren Text in vier Sprachen ist
nicht überprüfbar, und ein Schnitt zwischen Struktur und Text ließe T11 bis zum Ende der
Ebene rot stehen.

## Ebene 3 · Schnittstellen

- [x] **T12** · `src/Controller/ComparisonController.php` mit `index()` und `show()`;
      Klassen-Attribut `#[Route('/vergleich')]`, Slug-Requirement als Regex mit den vier
      bekannten Werten, zusätzlich `Competitor::fromSlug()` → `createNotFoundException()`
      — `AK-02, AK-03, AK-04`
- [x] **T13** · Funktionaler Prüflauf
      `tests/Functional/Controller/ComparisonControllerTest.php`: 200 für alle vier Slugs,
      404 für einen unbekannten, sprachfreier Kurzlink leitet auf die Übersicht um,
      Sprachwechsel auf `/de/vergleich/wheelmap` führt auf `/fr/vergleich/wheelmap`, die
      fünf Fenstertitel sind paarweise verschieden —
      `AK-03, AK-04, AK-05, AK-19, AK-31`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt. Hier treten leer und
ladend nachweislich nicht auf (die Zahl der Vergleiche steht im Quelltext, es wird nichts
nachgeladen) — das ist in `design.md` begründet und keine Auslassung.

- [x] **T14** · `templates/base.html.twig`: zwei neue, standardmäßig **leere** Blöcke im
      Kopfbereich für Kurzbeschreibung und kanonische Adresse. Keine bestehende Seite
      ändert sich dadurch — `AK-20, AK-21`
- [x] **T15** · `templates/base.html.twig`: Fußzeile von `md:grid-cols-3` auf
      `md:grid-cols-4`, vierte Spalte „Vergleiche" mit vier Links aus
      `Competitor::cases()` und „Alle Vergleiche" — `AK-01`
- [x] **T16** `[P]` · `templates/comparison/index.html.twig`: Kopfband, vier
      Vergleichskarten, Methodikhinweis mit Verweis auf `/criteria` und `/open` — `AK-02`
- [x] **T17** `[P]` · `templates/comparison/show.html.twig`: Kopfband mit einer H1,
      Kurzfazit, Gegenposition, häufige Fragen als `<details>`, Handlungsaufruf auf die
      Restaurantsuche, rechtlicher Fuß mit Markenhinweis und Verweis auf den Kontaktweg im
      Impressum — `AK-07, AK-10, AK-11, AK-14, AK-32`
- [x] **T18** `[P]` · `templates/comparison/_table.html.twig` und `_verdict.html.twig`:
      Merkmalstabelle nach dem Muster `partner/index.html.twig:86–135` — Scrollbereich
      `tabindex="0" role="region" aria-label` mit eigenem `focus:outline-2`,
      `<caption class="sr-only">`, `th scope`, je Zelle Symbol `aria-hidden` plus
      `sr-only`-Ansage plus erklärender Halbsatz — `AK-08, AK-09, AK-23, AK-24`
- [x] **T19** `[P]` · `templates/comparison/_sources.html.twig`: nummerierte Fußnoten mit
      Quelle, Adresse und Prüfdatum über `format_date`, nicht über feste Notation —
      `AK-12`
- [x] **T20** `[P]` · `templates/comparison/_cross_links.html.twig`: die anderen drei
      Vergleiche, Wortmarke im Linktext, kein „hier" und kein „mehr" — `AK-06`

## Ebene 5 · Feinschliff

- [x] **T21** · Barrierefreiheit: die fünf Adressen in
      `AccessibilityStructureTest::publicRoutes()` eintragen; Überschriftenhierarchie
      prüfen (eine H1 im Kopfband, Sektionen H2, Karten und Fragen H3); `motion-safe:` an
      jedem Übergang, `min-h-[48px]` an jeder Aktion; bei 320 px keine waagerechte
      Scrollleiste der Seite — `AK-22, AK-25, AK-26, AK-27`
- [x] **T22** · Prüflauf gegen fremde Ressourcen: die gerenderten Seiten enthalten kein
      `<img>`, kein `<script src>`, kein `<link href>` und kein `url(...)` mit fremdem
      Host — `AK-15, AK-30`
- [x] **T23** · Randfälle durchspielen: Slug in abweichender Schreibweise (EC-01), null
      erfasste Restaurants (EC-02), Seite ohne JavaScript (EC-03), Druckansicht — Kopfband
      als `<section>`, sonst weiß auf weiß (EC-04), langer Wettbewerbername in der
      Spaltenüberschrift (EC-05), leerer Zwischenspeicher beim ersten Aufruf (EC-06),
      Wettbewerber stellt Dienst ein (EC-07) —
      `EC-01, EC-02, EC-03, EC-04, EC-05, EC-06, EC-07`
- [x] **T24** · `docs/app-shell.md`, Abschnitt Fußzeile: vier Spalten statt drei.
      ⚠ Der Abschnitt ist bereits überholt — er nennt sieben Linkeinträge (es sind zehn)
      und führt unter „bekannte Lücken" einen toten Link, der längst behoben ist. Beim
      Nachziehen mitkorrigieren — *Dokumentation, kein AK*
- [x] **T25** · `npm run build` ausführen und `public/build` mitcommitten. **Pflicht auch
      bei reinen Twig-Änderungen**, weil Tailwind seine Klassen aus den Vorlagen liest;
      ohne das blockt `verify-assets` den Deploy — *Deploy-Voraussetzung, kein AK*

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T15 |
| AK-02 | T12, T16 |
| AK-03 | T12, T13 |
| AK-04 | T12, T13 |
| AK-05 | T13 |
| AK-06 | T20 |
| AK-07 | T06–T09, T17 |
| AK-08 | T06–T09, T10, T18 |
| AK-09 | T06–T09, T10, T18 |
| AK-10 | T06–T09, T10, T17 |
| AK-11 | T06–T09, T10, T17 |
| AK-12 | T06–T09, T10, T19 |
| AK-13 | T06–T09, T10 |
| AK-14 | T17 |
| AK-15 | T22 |
| AK-16 | T05 |
| AK-17 | T05 |
| AK-18 | T05 |
| AK-19 | T03, T13 |
| AK-20 | T03, T14 |
| AK-21 | T14 |
| AK-22 | T21 |
| AK-23 | T18 |
| AK-24 | T18 |
| AK-25 | T21 |
| AK-26 | T21 |
| AK-27 | T21 |
| AK-28 | T03, T11 |
| AK-29 | T11 |
| AK-30 | T22 |
| AK-31 | T04, T13 |
| AK-32 | T17 |

**AK ohne Aufgabe:** keine — alle 32 aus `spec.md` einzeln durchgegangen.

**Aufgabe ohne AK:** T01 und T02 (Grundlage für T03 und T06–T09), T24 (Dokumentation
nachziehen), T25 (Deploy-Voraussetzung). Alle vier zulässig.

**Edge Cases:** EC-01 bis EC-07 in T23. Keiner ohne Aufgabe.

## Parallelisierung

**Ebene 2:** T05 läuft gleichzeitig zu T06 — T05 fasst nur
`src/Comparison/ComparisonFigures.php` an, T06 die Registry und die Kataloge.

**Ebene 4:** T16, T17, T18, T19, T20 laufen gleichzeitig. Fünf verschiedene Dateien:

| Aufgabe | Datei |
|---|---|
| T16 | `templates/comparison/index.html.twig` |
| T17 | `templates/comparison/show.html.twig` |
| T18 | `templates/comparison/_table.html.twig`, `_verdict.html.twig` |
| T19 | `templates/comparison/_sources.html.twig` |
| T20 | `templates/comparison/_cross_links.html.twig` |

T17 bindet die Bausteine aus T18–T20 ein, die es während der Ebene noch nicht gibt. Das
ist zulässig: `lint:twig` läuft **nach** der Ebene, nicht während. Keine Aufgabe fasst
eine Katalogdatei an — die Schlüssel stehen bereits aus T03 und T06–T09 bereit. Genau
deshalb liegt T03 in Ebene 1 und nicht bei den Vorlagen.

**Ausdrücklich nicht parallel:**

| Aufgaben | Warum |
|---|---|
| T14 und T15 | beide ändern `templates/base.html.twig` |
| T06–T09 | alle vier schreiben in `ComparisonRegistry` und dieselben vier Katalogdateien |
| T10, T11 nach T06–T09 | ein Prüflauf gegen unvollständige Inhalte ist zwangsläufig rot |
| T01, T02, T03 | T02 nutzt die Aufzählungen aus T01, T03 benennt Schlüssel, die T01 vorgibt |

## Vor dem Bauen

- [x] Feature-Branch: `git checkout -b feature/03-vergleichsseiten`
- [x] Keine Schlüssel nötig — das Feature ruft keinen externen Dienst
- [x] Keine Migration nötig — es entsteht keine Tabelle
- [x] Testdatenbank steht: `make test-db-setup` (einmalig)
- [x] Docker läuft (`make start`), sonst scheitern die funktionalen Prüfläufe an der
      Datenbankverbindung
- [ ] **OF-02 ist weiterhin offen** — in welchem Rhythmus die Wettbewerber-Angaben
      nachgeprüft werden. Blockiert das Bauen nicht: Die Prüfdaten der ersten Recherche
      werden in T06–T09 gesetzt. Blockiert aber die Zusage, dass sie aktuell bleiben

## Recherchehinweis für T06–T09

Jede Aussage über einen Wettbewerber braucht eine Quelle mit Datum (AK-12), und T10 macht
daraus einen Prüflauf: eine Zeile ohne auflösbare Fußnote lässt ihn fehlschlagen. Zulässig
sind **Primärquellen** — Hilfeseiten und Dokumentation des Anbieters selbst. Ein
Blogbeitrag über Google Maps ist keine Quelle über Google Maps.

Wo sich eine Aussage nicht belegen lässt, gehört die Zeile **nicht** in die Tabelle. Eine
unbelegte Behauptung über ein fremdes Unternehmen ist der teuerste Fehler, den dieses
Feature machen kann — teurer als eine Tabelle mit drei Zeilen weniger.
