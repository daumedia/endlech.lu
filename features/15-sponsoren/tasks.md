# 15 · Sponsoren — Aufgabenplan

Status: `tasked` · Stand: 2026-10-28

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft der Verifikationsbefehl. **Rot heißt anhalten.**
Aufgaben mit Logik beginnen mit einem Test, der rot ist, bevor der Code ihn grün macht.

**Verifikation nach jeder Ebene** (Stack-Profil `symfony-doctrine`, an das Projekt angepasst —
AssetMapper statt Encore, siehe `CLAUDE.md`):

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff     # Stil, Exit ≠ 0 = etwas zu tun
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console doctrine:schema:validate --skip-sync
php bin/phpunit tests/Unit                       # Zahl der Tests ablesen — null ist rot
```

Nach Ebene 3 und vor der Übergabe zusätzlich `make test` (baut die Assets, volle Suite gegen die
Testdatenbank).

## Ebene 1 · Fundament — Daten und Konfiguration

- [x] **T01** · Konfiguration in `config/services.yaml`: Uhr-Dienst `app.sponsor_clock` (Produktion:
      Systemuhr; `when@test`: verstellbare Uhr, Zeitzone `Europe/Luxembourg`), Schnittstelle
      `App\Sponsor\SponsorSource` → `SponsorRegistry`; `when@test` → eine austauschbare Quelle im
      Speicher (`tests/Support/Sponsor/InMemorySponsorSource.php`, öffentlich, Vorgabe **leer**) —
      Grundlage für T04–T10 (Entscheidungen #1, #2)
- [x] **T02** `[P]` · Ablage der Logos: `assets/images/sponsors/` für die Produktion (heute ohne
      Datei, mit kurzem Hinweis in `CLAUDE.md` statt Platzhalterdatei, damit nichts mitausgeliefert
      wird); Testlogos (PNG und WebP, darunter ein sehr breites 8 : 1 und ein fast quadratisches)
      unter `tests/Fixtures/Sponsor/logos/`, die AssetMapper **nur im Testlauf** unter demselben
      logischen Pfad `images/sponsors/` kennt (`asset_mapper.yaml`, `when@test`) — Grundlage für T06,
      T12–T15; `EC-04`
- [x] **T03** `[P]` · Enum `App\Enum\SponsorshipTerm` (`monthly`, `yearly`, `transKey()`, `values()`);
      `OrganisationWaitlistEntry`: Zugriffe „gewählte Laufzeit“ und „alte Werte“, Prüfregel im Typ
      `company` auf höchstens einen Wert aus `SponsorshipTerm`; `SponsorshipInterest` bleibt als
      Lesehilfe für Altdaten (Kommentar). Tests zuerst: Unit-Test der Zugriffe mit alten, neuen und
      gemischten Werten — `AK-37, AK-38, AK-39`

## Ebene 2 · Server — Logik und Validierung

- [x] **T04** · Wertobjekte `App\Sponsor\Sponsor` und `SponsorPeriod` (Felder aus `design.md`, kein
      Feld für Betrag, Laufzeitart oder Person), Schnittstelle `SponsorSource` (`entries()`,
      `removedSlugs()`), Produktionsquelle `SponsorRegistry` mit leerer Liste und Kommentar, wie ein
      Eintrag aussieht — `AK-14, AK-44, AK-45`
- [x] **T05** `[P]` · `SponsorDirectory`: Zustände aktiv · ehemalig · unsichtbar nach dem Kalendertag
      in `Europe/Luxembourg`, Ende einschließlich; Sortierung der aktiven nach Beginn des laufenden
      Zeitraums, Gleichstand nach gefaltetem Namen; ehemalige nach letztem Ende absteigend;
      Footer-Auswahl (erste zwölf aktive); Suche nach Slug (nur sichtbare); „entfernt?“ über
      `removedSlugs()`; „gibt es aktive oder ehemalige?“. Tests zuerst, mit verstellbarer Uhr:
      23:59/00:00 am Endtag, Nacht der Sommerzeitumstellung, Pause und Wiederkehr, 13 aktive,
      gleicher Beginn, nur künftiger Zeitraum, ein Tag Sponsoring — `AK-02, AK-06, AK-15, AK-21,
      AK-23, AK-24, AK-25, EC-01, EC-02, EC-03, EC-07, EC-08, EC-12`
- [x] **T06** `[P]` · `SponsorRules`: Pflichtangaben, Slug-Muster und Eindeutigkeit, Name ≤ 80,
      Zeiträume geordnet und ohne Überlappung (nur der letzte offen), Ende nicht vor Beginn, Zitat
      ≤ 300 Zeichen und nur mit Sprachcode, Website `https://`, Logodatei vorhanden, **Endung und
      Dateisignatur PNG/WebP**, je ≤ 25 KB, die zwölf größten zusammen ≤ 150 KB; jede Verletzung
      als Satz mit Sponsor und Angabe. Prüflauf `SponsorRegistryTest` über die **Produktionsquelle**
      plus Gegenproben mit absichtlich fehlerhaften Einträgen (auch SVG unter `.png`-Namen) —
      `AK-10, AK-19, AK-27, AK-28, EC-05`
- [x] **T07** `[P]` · `OrganisationWaitlistType`: Feld `sponsorshipTerm` (zwei Radios, nicht Pflicht,
      ohne leere dritte Antwort) ersetzt `sponsorshipInterests`; Aufbau beim Typwechsel (`PRE_SUBMIT`,
      `PRE_SET_DATA`) wie bisher. Tests zuerst: Funktionstest „monatlich gespeichert“, „leer
      gespeichert“, „altes Format untergeschoben → 422, keine Zeile, keine Mail“ in
      `OrganisationControllerTest` (bestehende Tests mit `sponsorshipInterests` umstellen) —
      `AK-37, AK-38`

## Ebene 3 · Schnittstellen

- [x] **T08** · `SponsorController` mit `app_sponsor_index` (`/sponsoren`) und `app_sponsor_show`
      (`/sponsoren/{slug}`, Requirement aus `design.md`): sichtbarer Sponsor → Seite; entfernter →
      301 auf die Übersicht; unbekannt oder noch nicht begonnen → 404. Tests zuerst
      (`tests/Functional/Controller/SponsorControllerTest.php`) — `AK-22, AK-23, AK-43`
- [x] **T09** `[P]` · Twig-Erweiterung `SponsorExtension` (Muster `ComparisonExtension`):
      Footer-Auswahl, alle aktiven, alle ehemaligen, Zahl der aktiven, „gibt es welche?“, Zeiträume
      eines Sponsors. Unit-Test — `AK-05, AK-06, AK-32`
- [x] **T10** `[P]` · Auffindbarkeit: `SeoRegistry` (feste Seite `app_sponsor_index`, abgeleitete
      Seiten für `app_sponsor_show`, beide Routen indexierbar), `SitemapGenerator` nimmt die
      sichtbaren Sponsoren aus `SponsorDirectory` auf; Zählprüfläufe nachziehen
      (`SeoRegistryTest` 20 → 21, `SitemapGeneratorTest`, `RobotsTxtTest`,
      `Qa10SitemapBestandTest`); neuer Test: aktiver und ehemaliger Sponsor in der Sitemap, künftiger
      und entfernter nicht — `AK-40, AK-41`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt. Ladend entfällt hier überall
(server-gerendert, nichts wird nachgeladen) — das steht so in `design.md` und wird in jeder Aufgabe
nicht neu entschieden.

- [x] **T11** · Katalogschlüssel in **allen fünf Sprachen** (lb, de, fr, en, pt), in einem Zug, damit
      die folgenden Aufgaben nur Templates anfassen: Block `sponsor:` (Übersicht, Seite, Footer,
      „nicht bekommt“, Verwendung, Zeiträume, leere Zustände); `organisation.company.package_*`;
      `organisation.form.sponsorship_term` + `organisation.sponsorship_term.*`;
      `admin.waitlist.legacy_sponsorship`; `open.finance.sponsors_link`; in `press.*.yaml`
      `facts.sponsors`, `facts.sponsors_none`, `facts.sponsors_link`. Entfernen:
      `organisation.company.formats_title`, `format_1…4_*`, `organisation.form.sponsorship_interests`.
      Umformulieren: `organisation.company.intro` („als Sponsor genannt“), `organisation.selector.company`
      (ohne „mit Ihrem Team“) — `AK-11, AK-13, AK-16, AK-30, AK-32, AK-33, AK-34, AK-37`
- [x] **T12** `[P]` · Teilstücke `templates/sponsor/_logo.html.twig` (weiße Kachel, feste Fläche,
      `object-contain`, `loading="lazy"`, `width`/`height`, `alt` = Name), `_logo_row.html.twig`,
      `_not_included.html.twig`, `_periods.html.twig` (Monat und Jahr in der Seitensprache) —
      `AK-02, AK-04, AK-10, AK-29, EC-04`
- [x] **T13** `[P]` · Footer: Bereich „Sponsoren“ in `partials/_footer.html.twig` mit drei Zuständen
      (nie einer · nur ehemalige · aktive, ab 13 die ältesten zwölf), Links „Alle Sponsoren“ und
      „Sponsor werden →“; Funktionstest `tests/Functional/Sponsor/FooterSponsorsTest.php` (Startseite,
      Restaurantliste, Detailseite, `/open`, Impressum, fünf Sprachen; Tastatur und Name als
      Linktext) — `AK-01, AK-03, AK-04, AK-05, AK-06, AK-07`
- [x] **T14** `[P]` · Übersicht `templates/sponsor/index.html.twig`: Paket, „bekommt“, „nicht
      bekommt“, Ausschlussregel, Verwendung mit Link auf `/open`, aktive (alle), „Bisher unterstützt
      von“, Handlungsaufruf; Zustände leer · nur ehemalige · gefüllt. Funktionstest
      `tests/Functional/Sponsor/SponsorIndexTest.php` — `AK-11, AK-12, AK-13, AK-15`
- [x] **T15** `[P]` · Sponsorenseite `templates/sponsor/show.html.twig`: Kopf, Zeiträume, Zitat mit
      `lang` und Zeichnung, Verwendung, „nicht bekommt“, Website-Link nur aktiv (`rel="sponsored
      noopener"`, kein `target`), Handlungsaufruf; Zustände aktiv · ehemalig · ohne Zitat · ohne
      Website. Funktionstest `tests/Functional/Sponsor/SponsorShowTest.php` — `AK-16, AK-17, AK-18,
      AK-19, AK-20, AK-21`
- [x] **T16** `[P]` · Unternehmensseite: Paketblock statt Formatraster in
      `organisation/_section_company.html.twig`, Logoreihe über der Formularkarte in
      `organisation/type.html.twig` (nur Typ Unternehmen, nur mit aktivem Sponsor), Feld
      „Interesse an einem Sponsoring“ in `organisation/_form.html.twig`. Funktionstest
      `tests/Functional/Sponsor/CompanyPageTest.php` (Paket, Reihe an/aus, Ausschlussregel und
      „Auch ohne Geld“ unverändert, Formular ohne JavaScript) — `AK-31, AK-33, AK-35, AK-36, AK-37`
- [x] **T17** `[P]` · Verwaltung und interne Meldung: Laufzeit als Abzeichen und alte Werte mit
      Vermerk „frühere Angabe“ in `admin/waitlist/organisation_show.html.twig`; Laufzeit übersetzt
      (Deutsch) und alte Werte mit Vermerk in `email/organisation/internal_notification.html.twig`.
      Funktionstest in `tests/Functional/Sponsor/WaitlistLegacyTest.php` (Eintrag mit altem Format
      öffnen → keine Fehlerseite, Werte lesbar) — `AK-38, AK-39`
- [x] **T18** `[P]` · Weitere Orte: Link „Wer uns finanziert →“ im Block Finanzen von
      `open/index.html.twig` (außerhalb der Sperrbedingung), Zeile „Sponsoren“ in
      `press/_facts.html.twig`. Funktionstest `tests/Functional/Sponsor/SponsorMentionsTest.php`
      (auch während der Quartalssperre; ohne Sponsor „Derzeit keine“) — `AK-30, AK-32`

## Ebene 5 · Feinschliff

- [x] **T19** · Prüfläufe über das ganze Feature in `tests/Functional/Sponsor/SponsorIntegrityTest.php`:
      Hauptinhalt von Restaurantliste (Sortierungen, Filter), Detailseite, Startseite und Datensatz
      mit und ohne Sponsoren gleich; kein Sponsor im `<main>` der Detailseite; kein Eurobetrag,
      kein „monatlich“/„jährlich“ bei einem einzelnen Sponsor (Footer, Seiten, Faktenblatt, fünf
      Sprachen); alte Formatnamen auf keiner öffentlichen Seite; keine Datei von fremder Adresse;
      kein Messauslöser am Website-Link; Sponsorenpfad passiert `CollectPayloadNormalizer` —
      `AK-08, AK-09, AK-14, AK-34, AK-42, AK-46, AK-47`
- [x] **T20** · Barrierefreiheit und Randfälle: neue Seiten in die Prüfläufe aus Feature 02 aufnehmen
      (Überschriftenkette, Landmarken, Namen), Footer mit 12 Logos bei 320 px ohne waagerechtes
      Scrollen, sehr breites Logo, Name mit „Ëmil & Fils S.à r.l.“, Seiten ohne JavaScript; im
      Browser: Logos bei 390 px auf Startseite und Restaurantliste erst beim Scrollen geladen —
      `AK-04, AK-10, EC-04, EC-05, EC-06, EC-09, EC-11`
- [x] **T21** · Was außerhalb des Codes mitzieht (Tabelle in `design.md`): `CLAUDE.md` (Abschnitt
      Feature 15 mit Anleitung „Sponsor eintragen · beenden · entfernen“ und den Fallstricken),
      `docs/prd.md`, `docs/data-model.md`, `docs/datenschutz.md` (Verarbeitung „Nennung von
      Sponsoren“, OF-03), `docs/app-shell.md`, Vermerke in `features/10-sitemap-robots/spec.md` und
      `features/B15-organisations-wartelisten/spec.md` — `AK-26, AK-43`
- [ ] **T22** · Nach dem Deploy, beim Betreiber: in Umami die Aufrufe einer Sponsorenseite für einen
      Monat über alle Sprachen ablesen (erst möglich, wenn es einen Sponsor gibt) — `AK-48`

## Abdeckung

Abgegangen an `spec.md`, Kriterium für Kriterium.

| AK | Aufgaben |
|---|---|
| AK-01 | T13 |
| AK-02 | T05, T12 |
| AK-03 | T13 |
| AK-04 | T12, T13, T20 |
| AK-05 | T09, T13 |
| AK-06 | T05, T09, T13 |
| AK-07 | T13 |
| AK-08 | T19 |
| AK-09 | T19 |
| AK-10 | T06, T12, T20 |
| AK-11 | T11, T14 |
| AK-12 | T14 |
| AK-13 | T11, T14 |
| AK-14 | T04, T19 |
| AK-15 | T05, T14 |
| AK-16 | T11, T15 |
| AK-17 | T15 |
| AK-18 | T15 |
| AK-19 | T06, T15 |
| AK-20 | T15 |
| AK-21 | T05, T15 |
| AK-22 | T08 |
| AK-23 | T05, T08 |
| AK-24 | T05 |
| AK-25 | T05 |
| AK-26 | T21 (Anleitung), Struktur aus T04, T10, T11 |
| AK-27 | T06 |
| AK-28 | T06 |
| AK-29 | T12 |
| AK-30 | T11, T18 |
| AK-31 | T16 |
| AK-32 | T09, T11, T18 |
| AK-33 | T11, T16 |
| AK-34 | T11, T19 |
| AK-35 | T16 |
| AK-36 | T16 |
| AK-37 | T03, T07, T11, T16 |
| AK-38 | T03, T07, T17 |
| AK-39 | T03, T17 |
| AK-40 | T10 |
| AK-41 | T10 |
| AK-42 | T19 |
| AK-43 | T08, T21 |
| AK-44 | T04 |
| AK-45 | T04 |
| AK-46 | T19 |
| AK-47 | T19 |
| AK-48 | T22 |

| EC | Aufgaben |
|---|---|
| EC-01, EC-02, EC-03, EC-07, EC-08, EC-12 | T05 |
| EC-04 | T02, T12, T20 |
| EC-05 | T06, T20 |
| EC-06 | T20 (Entscheidung #3 in `design.md`: Prüfsumme im Dateinamen) |
| EC-09 | T20 |
| EC-10 | strukturell durch T04 (kein Bezug zur Warteliste), Prüfung in T19 mit AK-45 |
| EC-11 | T15, T20 |

**AK ohne Aufgabe:** keine.
**Aufgabe ohne AK:** T01 (Konfiguration, Grundlage für T04–T10), T02 (Ablage der Logos, Grundlage
für T06 und T12–T15) — zulässig.

## Parallelisierung

- **Ebene 1:** T02 und T03 gleichzeitig nach T01 — T02 berührt `assets/images/sponsors/`,
  `tests/Fixtures/Sponsor/logos/`, `config/packages/asset_mapper.yaml`; T03 berührt
  `src/Enum/SponsorshipTerm.php`, `src/Entity/OrganisationWaitlistEntry.php` und eigene Unit-Tests.
  T01 läuft zuerst und allein (`config/services.yaml`, `tests/Support/`).
- **Ebene 2:** T04 läuft zuerst und allein (die Wertobjekte brauchen T05 und T06). Danach T05, T06 und
  T07 gleichzeitig — T05 `src/Sponsor/SponsorDirectory.php` + Test, T06 `src/Sponsor/SponsorRules.php`
  + `SponsorRegistryTest`, T07 `src/Form/OrganisationWaitlistType.php` +
  `OrganisationControllerTest`. Keine gemeinsame Datei.
- **Ebene 3:** T08 zuerst (die Routen braucht T10 für `SeoRegistry`), danach T09 und T10 gleichzeitig —
  T09 `src/Twig/SponsorExtension.php` + Test, T10 `src/Seo/*` + Seo-Tests.
- **Ebene 4:** T11 läuft zuerst und allein — **es ist die einzige Aufgabe, die Kataloge anfasst**,
  genau damit die übrigen parallel laufen können. Danach T12 bis T18 gleichzeitig; jede berührt nur
  ihre Templates und ihre eigene Testdatei (`tests/Functional/Sponsor/<Name>Test.php`). T13 und T16
  binden die Teilstücke aus T12 ein, ändern sie aber nicht — T12 wird deshalb **vor** T13–T16
  fertig, die übrigen (T17, T18) laufen schon daneben.
- **Ebene 5:** seriell (T19 und T20 lesen dieselben Seiten, T21 beschreibt das Ergebnis).

## Vor dem Bauen

- [ ] Feature-Branch: `git checkout -b feature/15-sponsoren` (der Stand von `main` mit Spec, Entwurf
      und Plan geht mit, uncommittet)
- [ ] Keine Schlüssel nötig (Spec, Katalogfrage 6)
- [ ] Testdatenbank steht (`make test-db-setup`), Assets gebaut (`make assets`), sonst scheitert jeder
      Funktionstest an `importmap()`
- [ ] Keine Migration — `doctrine:schema:validate` muss nach T03 weiterhin ohne Abweichung sein

## Abschlussbericht · 2026-10-28

### 1 · Umgesetzt

Sponsoren stehen als Einträge im Quelltext (`App\Sponsor\SponsorRegistry`, heute leer) hinter der Schnittstelle
`SponsorSource`. `SponsorDirectory` rechnet bei jeder Anfrage aus Zeiträumen und dem Tag in Luxemburg, wer aktiv,
ehemalig oder noch unsichtbar ist. Der Footer jeder Seite trägt den Bereich „Sponsoren“ (bis zu zwölf Logos auf
weißer Kachel, „Alle Sponsoren“, „Sponsor werden →“; ohne Sponsor nur der letzte Link). Neu sind `/sponsoren`
(Paket, was es gibt und was nicht, Ausschlussregel, Verwendung, aktive, „Bisher unterstützt von“) und
`/sponsoren/{slug}` (Zeiträume, Zitat in Originalsprache mit `lang`, `rel="sponsored noopener"`, ehemalige ohne
Website-Link; entfernt → 301, unsichtbar/unbekannt → 404). `/open` verlinkt im Block Finanzen, das Faktenblatt
nennt die Zahl der aktiven. Die Unternehmensseite nennt nur noch das Paket, die Logoreihe steht über dem
Formular, und das Formular fragt nur noch „monatlich“ oder „jährlich“ (Liste → 422). Die Verwaltung und die
interne Meldung zeigen Laufzeit und alte Formate als „frühere Angabe“. `/sponsoren` ist die 21. feste Seite der
Sitemap, die Sponsorenseiten werden abgeleitet. **21 von 22 Aufgaben erledigt** (T22 beim Betreiber nach dem
Deploy). Alle 48 Kriterien sind durch Code oder Ablauf abgedeckt; zwei hängen am Betrieb (AK-26 Ausrolldauer,
AK-48 Umami).

### 2 · Offene Akzeptanzkriterien

- **AK-48** (Aufrufe einer Sponsorenseite in Umami ablesen): nicht prüfbar, bevor es einen Sponsor und einen Deploy
  gibt. Plattformschritt T22. Belegt ist nur die Vorbedingung: Der Pfad `/de/sponsoren/acme` passiert
  `CollectPayloadNormalizer` unverändert (`SponsorIntegrityTest::testAk47…`).
- **AK-26** (in zehn Minuten live): lokal bis „bereit zum Ausrollen“ nachgestellt. Ein Probeeintrag mit zwei
  Logos war samt `tests/Unit/Sponsor` in unter drei Minuten fertig, und Footer, Übersicht und Seite zeigten ihn.
  Die Ausrolldauer in Coolify (laut `CLAUDE.md` rund drei Minuten) ist nicht gemessen.
- **AK-10, erste Hälfte** („auf dem Telefon nicht gescrollt, noch keine Logodatei geladen“): **nur eingeschränkt
  erfüllt.** Die Logos tragen `loading="lazy"`, feste Maße, und `SponsorRules` begrenzt das Gewicht (je ≤ 25 KB,
  zwölf zusammen ≤ 150 KB). Im Browser gemessen (Chrome, 375 px, `/de/sponsoren/probe-acme`, Footer bei 1716 px)
  lud Chrome die Footer-Logos trotzdem sofort: Seine Vorlaufzone für verzögertes Laden ist größer als der Abstand
  (Entscheidung #6 sagt das voraus). Startseite und Restaurantliste ließen sich lokal nicht messen (siehe
  Annahmen: Entwicklungsdatenbank fehlt). Vorschlag für die QA: an diesen beiden Seiten auf der Produktion oder mit
  Testdatenbank messen. Fällt der Footer dort in die Vorlaufzone, wird das Kriterium zur offenen Frage (schärfer
  nur mit JavaScript-Nachladen, das EC-09 bräche).

### 3 · Getroffene Annahmen

- **Der Paketpreis steht in keiner Datei.** Er stand nach Spec und Index in Decision Log #2, in der Index-Zeile und
  kurz im PRD. Ich habe ihn entfernt, weil das Repository öffentlich ist und Decision Log #7 ihn bewusst nicht
  veröffentlicht. Das ist keine Änderung an einer Anforderung, an Decision Log #2 steht ein Vermerk.
- **Testsponsoren statt echter Liste:** Alle Funktionstests laufen gegen `InMemorySponsorSource` und eine Uhr auf
  dem 2027-06-15. Die echte Liste prüft allein `SponsorRegistryTest`.
- **`symfony/clock` ist keine direkte Abhängigkeit** in `composer.json`, kommt aber über Framework und Scheduler
  mit. `SponsorDirectory` benutzt die PSR-Schnittstelle, im Test `MockClock`. Ein Eintrag in `composer.json` wäre
  sauberer, steht aber nicht im Entwurf.
- **Vorgezogen aus T11 und T16**, damit Ebene 2 grün blieb: die drei Katalogschlüssel und der Feldteil von
  `organisation/_form.html.twig` entstanden schon in T07.
- **Selbsttest ohne Entwicklungsdatenbank:** Auf dem lokalen MySQL gibt es keine Datenbank `endlech`, deshalb
  liefert die Startseite lokal 500. Ich habe keine angelegt. Sichtgeprüft wurden die Seiten ohne Datenbankzugriff
  (Übersicht, Sponsorenseite, Unternehmensseite) mit zwei vorübergehend eingetragenen Probe-Sponsoren. Eintrag und
  Logos sind wieder entfernt, `SponsorRegistry` ist leer.
- Die Testdatenbank war veraltet (Tabelle `organisation_waitlist_entry` fehlte) und wurde mit `make test-db-setup`
  migriert und neu befüllt.

### 4 · Systemweite Änderungen

- **Footer** (`partials/_footer.html.twig`): neuer Bereich auf **jeder** Seite, auch im Verwaltungsbereich.
- **Seitenverzeichnis/Sitemap:** 21 statt 20 feste Seiten (`SeoRegistry`, `derivedRoutes()` ersetzt die Sonderstellung
  von `RESTAURANT_ROUTE` in drei Prüfläufen). Zählprüfläufe angepasst: `SeoRegistryTest`, `SitemapGeneratorTest`
  (155 → 160), `SitemapControllerTest`, `RobotsTxtTest`, `Qa10SitemapBestandTest`. `SitemapGenerator` hat ein
  neues Konstruktor-Argument.
- **`config/services.yaml`:** `SponsorSource`-Alias, `app.sponsor_clock`, im Test zwei öffentliche Dienste.
  **`asset_mapper.yaml`:** im Test zusätzlicher Pfad `tests/Fixtures/Sponsor/logos/` unter `images/sponsors`.
- **Organisations-Warteliste (B15):** Formularfeld `sponsorshipInterests` → `sponsorshipTerm`; die Entity prüft im
  Typ `company` nur noch höchstens einen Wert aus `SponsorshipTerm`. Bestehende Tests in
  `OrganisationControllerTest` umgestellt.
- **Kataloge** (je fünf `messages.*`, `press.*`): neuer Block `sponsor:`; entfernt sind
  `organisation.company.formats_title`, `format_1…4_*` und `organisation.form.sponsorship_interests`. Umformuliert
  sind `organisation.company.intro` und `organisation.selector.company`. `organisation.sponsorship.*` bleibt für
  Altdaten.
- **Barrierefreiheits-Prüfläufe** (Feature 02): `/sponsoren` in `AccessibilityStructureTest`, `/sponsoren` und
  `/organisationen/unternehmen` in `HeadingOrderTest`.
- **Dokumentation:** `CLAUDE.md` (Abschnitt Feature 15, Routentabelle), `docs/prd.md`, `docs/data-model.md`,
  `docs/datenschutz.md` (neue Verarbeitung), `docs/app-shell.md`, Vermerke in `features/10-…/spec.md` und
  `features/B15-…/spec.md`.
- **Gefunden, nicht behoben (nicht im Auftrag):** `lint:container` scheitert auch auf `main` an der Webauthn-
  Aliasdefinition (`PublicKeyCredentialSourceRepositoryInterface`). `vendor/bin/php-cs-fixer` ist nicht
  installiert, obwohl `make fix` ihn ruft. Stil deshalb nur von Hand am Nachbarcode ausgerichtet.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben (einschließlich Datenfälle) | 170 in neuen Dateien, dazu 5 neue in `OrganisationControllerTest` |
| davon rot gesehen, dann grün | alle mit Logik (Verzeichnis 17, Regeln 24, Erweiterung 4, Entity 3, Formular 3, Controller per Gegenprobe, Sitemap 3, Footer, Übersicht, Seite, Unternehmensseite, Verwaltung, Verweise) |
| gelaufen in der letzten Verifikation | 1730, alle grün, 16 übersprungen (wie vor dem Bau) |

**Nicht vor der Umsetzung rot gesehen, stattdessen per Gegenprobe belegt:**
- `SponsorIntegrityTest` (Wächtertests AK-08, AK-14, AK-34, AK-42, AK-46): je eine Gegenprobe (Sponsor im Inhalt
  der Liste, Betrag auf der Seite, altes Format im Katalog, fremdes Bild im Footer, Messattribut am Link), alle
  rot, danach zurückgesetzt.
- `SponsorShowTest::testAk20…` und `testAk21…`: mit entfernter Bedingung rot.
- `OrganisationSponsorshipTermTest::testAk37LeerHeisstKeinSponsoring`: war mit dem Stummel grün, mit gebrochenem
  Leeren rot.
- `SponsorControllerTest`: mit umgangener Sichtbarkeit wird der Test für den künftigen Sponsor rot.
- AK-38-Ablehnungstests: waren vor dem neuen Feld grün (das Feld fehlte), danach grün, weil die Auswahlprüfung
  scheitert.
- Aus Prüfläufen von Feature 02 übernommen: `SponsorAccessibilityTest` (Überschriften, alt) und
  `testAk04KeinVerschachtelterUndKeinNamenloserLink`. Letzterer war rot gegen den Fehler, den der Selbsttest fand
  (`link|default(true)` im Logo-Teilstück), und ist nach der Reparatur grün.

## Nachtrag · Überarbeitung 2026-10-28 — Aufgaben T23 bis T28

Grundlage: Spec-Überarbeitung (AK-49 bis AK-55, EC-13, EC-14) und Nachtrag in `design.md`. Verifikation wie oben.

### Ebene 1/2 · Daten und Regeln

- [x] **T23** · Enum `App\Sponsor\SponsorPlatform` (`label()`, `hosts()`), Felder `description` und `profiles` an
      `Sponsor`; die Feldliste in `SponsorRegistryTest::testAk14Ak44…` zieht mit — `AK-49, AK-52`
- [x] **T24** · `SponsorRules`: Beschreibung (genau die Sprachen aus dem Argument, 1–500 Zeichen, kein Eurobetrag),
      Profile (`https://`, Host der Plattform); `SponsorRegistryTest` liest die Sprachen aus `translation.yaml`.
      Tests zuerst (Gegenproben in `SponsorRulesTest`) — `AK-51, AK-53`

### Ebene 4 · Oberfläche

- [x] **T25** · Katalogschlüssel in fünf Sprachen: `sponsor.show.about_title`, `sponsor.show.profile_label` —
      `AK-49, AK-52`
- [x] **T26** · `sponsor/show.html.twig`: Abschnitt „Über <Name>“, Linkzeile mit Profilen (nur aktiv, auch ohne
      Website); `TestSponsors::acme()` bekommt Beschreibung und Profile. Tests zuerst in `SponsorShowTest` —
      `AK-49, AK-50, AK-52, AK-54, EC-13, EC-14`

### Ebene 5 · Feinschliff

- [x] **T27** · `SponsorIntegrityTest`: AK-42/AK-46 mit Profillinks, kein Plattform-Host in geladenen Dateien;
      AK-14 mit Beschreibung — `AK-55`
- [x] **T28** · Doku: `CLAUDE.md` (Anleitung um Beschreibung und Profile), `docs/data-model.md`,
      `docs/datenschutz.md` (Daten: Beschreibung, Profile; Klick auf ein Profil) — `AK-26`

**AK ohne Aufgabe (Nachtrag):** keine. **Aufgabe ohne AK:** keine.
**Parallelisierung:** keine — T23 → T24 → T25 → T26 → T27 → T28, jede baut auf der vorigen auf.

## Abschlussbericht · 2026-10-28 · Nachtrag Überarbeitung (T23–T28)

### 1 · Umgesetzt

Die Sponsorenseite zeigt eine Kurzbeschreibung in der Seitensprache unter „Über <Name>“ und Links auf die Profile
bei Instagram, Facebook und LinkedIn. Die Links stehen in der Zeile neben der Website, sind als bezahlt
gekennzeichnet, tragen „<Plattform> von <Name>“ als Namen und fehlen bei ehemaligen Sponsoren. Neu sind
`SponsorPlatform` (erlaubte Hosts) und die Felder `description` und `profiles`. `SponsorRules` prüft beides und
bekommt die Sprachen aus `translation.yaml`. **6 von 6 Aufgaben erledigt**, AK-49 bis AK-55, EC-13 und EC-14
sind abgedeckt.

### 2 · Offene Akzeptanzkriterien

Keine aus der Überarbeitung. Die offenen Punkte aus dem ersten Bau und der ersten QA (AK-10/BF-190, AK-26, AK-47,
AK-48, BF-188, BF-189, BF-191 bis BF-195) sind unverändert und nicht Teil dieses Auftrags.

### 3 · Getroffene Annahmen

- **AK-54 (vorgeschlagen, vom Betreiber freigegeben):** Die Beschreibung bleibt bei ehemaligen Sponsoren stehen,
  die Profillinks fallen weg.
- **Erlaubte Hosts:** der Host selbst oder eine Unterdomain davon (`www.instagram.com`, `lu.linkedin.com`). Kurz-
  oder Weiterleitungsadressen (`fb.me`, `instagr.am`, `lnkd.in`) werden abgewiesen.
- **⚠ Lokales Beispiel in `SponsorRegistry`:** Die Fondatioun Wonschstär steht zur Ansicht mit Logo
  (`assets/images/sponsors/wonschstaer.webp`), Beschreibung und Profilen in der Registry. Der Eintrag ist markiert,
  es gibt keine Vereinbarung, die Beschreibung ist sinngemäß aus wonschstaer.lu übernommen. **Entfernt am
  2026-10-28** (Eintrag und Logo), `SponsorRegistry` ist wieder leer.

### 4 · Systemweite Änderungen

- `SponsorRules::violations()` hat ein viertes Pflichtargument (Sprachen). Nachgezogen sind die drei Aufrufer,
  darunter `Qa15SponsorTest` aus der QA.
- Kataloge: `sponsor.show.about_title`, `sponsor.show.profile_label` in fünf Sprachen.
- `TestSponsors::acme()` trägt jetzt Beschreibung und zwei Profile. Die übrigen Sponsor-Tests sind damit grün.
- Doku: `CLAUDE.md`, `docs/data-model.md`, `docs/datenschutz.md`.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu (einschließlich Datenfälle) | 10 Regelfälle, 10 Seitentests (5 Sprachen + 5), 1 Integritätstest |
| davon rot gesehen, dann grün | 10 + 9 + 1. Der Feldlisten-Test war rot, bevor die Felder kamen |
| gelaufen in der letzten Verifikation | 1774, alle grün, 19 übersprungen (16 + 3 Reproduktionen der QA) |

Sofort grün und per Gegenprobe belegt: `testAk50OhneBeschreibungKeinAbschnitt`. Rot, wenn der Abschnitt immer
erscheint.
