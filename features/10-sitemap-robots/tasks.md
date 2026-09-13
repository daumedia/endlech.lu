# 10 · Sitemap und robots.txt — Aufgabenplan

Status: `building` · Stand: 2026-09-12

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen. Aufgaben **ohne** `[P]`
laufen danach, der Reihe nach.

Nach jeder Ebene läuft die Verifikation unten. **Rot heißt anhalten.**

## Verifikation — und was davon schon vor diesem Feature rot ist

Das Stack-Profil nennt fünf Befehle. Am 2026-09-12 in diesem Projekt geprüft:

| Befehl | Stand vor Feature 10 | Im Plan |
|---|---|---|
| `make fix-check` | **gibt es nicht** — kein Ziel im Makefile, `php-cs-fixer` nicht installiert | entfällt; Stil an den Nachbardateien ausrichten |
| `php bin/console lint:container` | ⚠ **rot**, genau **ein** Fehler: Alias auf `PublicKeyCredentialSourceRepositoryInterface` (WebAuthn) | Referenzstand: **genau dieser eine Fehler**. Jeder weitere ist rot |
| `php bin/console lint:twig templates/` | grün | ja |
| `php bin/console lint:yaml config/` | grün | ja (statt `schema:validate`, siehe unten) |
| `php bin/console doctrine:schema:validate` | ⚠ **rot** — Mapping und Schema weichen ab (dieselbe Abweichung wie bei der MariaDB-Probe vom 2026-09-12) | entfällt: Feature 10 ändert kein Schema. Die Abweichung ist ein eigener Befund |
| `npm run build` | — | entfällt: Feature 10 ändert nichts unter `assets/` |

**Befehl nach jeder Ebene** (die Test-DB muss aufgesetzt sein, `make test-db-setup`):

```bash
php bin/console lint:yaml config/
php bin/console lint:twig templates/
php bin/console lint:container 2>&1 | grep -c 'Invalid alias definition'   # erwartet: 1
php bin/phpunit                                                           # Ebene 1–2: die genannten Teilläufe genügen
```

## Ebene 1 · Fundament — Konfiguration

- [x] **T01** `[P]` · Zwischenspeicher `cache.sitemap` in `config/packages/cache.yaml`:
      Dateisystem, Lebensdauer **3000 s**; im `when@test`-Block ein Array-Adapter nach dem
      Muster der übrigen Pools. Im Kommentar die Rechnung „50 min + 10 min `max-age` = 60 min"
      — Grundlage für T07 · `AK-08, AK-09, AK-11`
- [x] **T02** `[P]` · Parameter `app.canonical_base_url: 'https://endlech.lu'` in
      `config/services.yaml`, **ohne** Umgebungsvariable. Im Kommentar, warum weder Anfrage
      noch `DEFAULT_URI` (Entwurf, Entscheidung 2) — Grundlage für T05 · `AK-03`
- [x] **T03** `[P]` · Schemadateien unter `tests/Fixtures/Sitemap/`: `sitemap.xsd` von
      sitemaps.org unverändert samt Quellen- und Lizenzvermerk (CC BY-SA), dazu ein kleines
      Schema für das Sprachverweis-Element (`rel`, `hreflang`, `href`), das `sitemap.xsd`
      importiert. ⚠ Neue Ablage — das Projekt hatte bisher keine Fixture-Dateien für Prüfläufe —
      Grundlage für T07, T09 · `AK-01`

## Ebene 2 · Server — Logik

- [x] **T04** `[P]` · Seitenverzeichnis in `src/Seo/`: die 21 festen Seiten als Route plus
      Parameter — die drei Vergleiche **aus dem Vergleichsverzeichnis**, die drei
      Organisationsseiten **aus dem Organisationstyp**, nicht abgeschrieben —, die
      Ausschlussliste mit den 16 Routennamen aus `design.md`. Prüflauf
      `tests/Unit/Seo/SeoRegistryTest.php`: genau 21 feste Seiten, deckungsgleich mit der
      Tabelle in `spec.md`; genau 16 Ausschlüsse; beide Listen disjunkt; keine Route aus
      Verwaltung, Profil oder Schnittstelle — `AK-02, AK-06, AK-16, AK-22`
- [x] **T05** `[P]` · Adressbildner in `src/Seo/`: absolute Adresse aus
      `app.canonical_base_url` plus Routenpfad; vier Sprachfassungen plus Vorgabe `lb`, der
      Eintrag selbst eingeschlossen; von den Abfrageparametern bleibt nur `page` als ganze Zahl
      ab 2. Parameter über `#[Autowire]` im Konstruktor, **nicht** über `services.yaml` (sonst
      teilt die Aufgabe eine Datei mit T02). Prüflauf `tests/Integration/Seo/SeoUrlBuilderTest.php`
      mit den **vier Beispielzeilen aus AK-13** als Datenfälle, dazu eine Anfrage über
      `www.endlech.lu` und über `http`, die trotzdem `https://endlech.lu` ergibt —
      `AK-03, AK-05, AK-13, AK-14`
- [x] **T06** `[P]` · Leseabfrage im Restaurant-Repository: alle Restaurantnummern aufsteigend,
      nur die Nummer, ohne Beziehungen, **ohne natives SQL** (MariaDB, Stack-Profil § 6).
      Prüflauf ergänzt in `tests/Integration/Repository/RestaurantRepositoryTest.php`: Anzahl
      gleich Fixture-Bestand, aufsteigend, nur ganze Zahlen; leerer Bestand ergibt eine leere
      Liste — `AK-02, AK-08, AK-09, AK-10`
- [x] **T07** · Sitemap-Erzeuger in `src/Seo/` und Vorlage `templates/seo/sitemap.xml.twig`:
      Einträge aus T04 und T06, Adressen aus T05, gerendert in eine Zeichenkette, gespeichert in
      `cache.sitemap`. **Kein `try`/`catch` um die Abfrage** (Entwurf, Entscheidung 7). Prüflauf
      `tests/Integration/Seo/SitemapGeneratorTest.php`: 128 Einträge mit den Fixtures; nach
      Löschen aller Restaurants 84; eine absichtlich scheiternde Abfrage wirft **und hinterlässt
      einen leeren Speicher**; kein `lastmod`, `priority`, `changefreq`; kein `@`, kein
      Token-Muster; jeder Eintrag mit vier Sprachverweisen und `x-default`; Schema-Prüfung mit
      T03 — `AK-01, AK-02, AK-05, AK-07, AK-10, AK-11, AK-22`
- [x] **T08** · Twig-Erweiterung in `src/Twig/`: liefert für die aktuelle Anfrage den
      canonical-Verweis (nur für Seiten aus dem Verzeichnis, sonst nichts) und die
      Sprachverweise, beides aus T05. Prüflauf `tests/Integration/Twig/SeoExtensionTest.php`:
      Verzeichnisseite → Adresse; Anmeldeseite → kein canonical; Restaurantliste mit
      `?page=2&sort=name` → Seitenzahl in canonical **und** Sprachverweisen —
      `AK-12, AK-13, AK-14`

**Verifikation Ebene 2:** `lint:container` (Referenzstand), `lint:twig`, dann
`php bin/phpunit tests/Unit/Seo tests/Integration/Seo tests/Integration/Twig tests/Integration/Repository/RestaurantRepositoryTest.php`.

## Ebene 3 · Schnittstellen

- [x] **T09** `[P]` · Sitemap-Endpunkt `src/Controller/Seo/`: `GET /sitemap.xml`, zustandslos,
      `Content-Type: application/xml; charset=utf-8`, `Cache-Control: public, max-age=600`
      samt Kennzeichnung gegen die automatische Cache-Kopfzeile (Muster des offenen
      Datensatzes). Eigener Block `seo` in `config/routes.yaml` und Eintrag in der
      `exclude`-Liste des `controllers`-Loaders. Prüflauf `tests/Functional/Seo/SitemapControllerTest.php`:
      200 und Kopfzeilen; Schema-Prüfung; **jede Adresse der Sitemap als Pfad aufgerufen → 200
      ohne Weiterleitung**; Abruf mit Host `www.endlech.lu` → alle Adressen auf
      `https://endlech.lu`; `/de/sitemap.xml` → 404; Speicherdauer aus T01 plus `max-age` ≤ 3600 s —
      `AK-01, AK-03, AK-04, AK-08, AK-09`, `EC-06`
- [x] **T10** `[P]` · Deckel `sitemap` **konfigurieren und verdrahten in einem Zug**: in
      `config/packages/framework.yaml` gleitendes Fenster, 60 je Stunde, mit `when@test`-Override
      auf 10000; im Routen-Deckel-Abonnent ein vierter Zweig für `/sitemap.xml`, **eigenes
      Kontingent**, nicht das des Datensatzes. ⚠ Nicht aufteilen: `LimiterCoverageTest` wird rot,
      sobald ein Deckel konfiguriert, aber nicht verdrahtet ist. Prüflauf
      `tests/Unit/EventSubscriber/RouteRateLimitSubscriberTest.php` nach dem Muster von
      `ActionLimiterTest` (`InMemoryStorage`): 60 Anfragen frei, die 61. wirft 429 mit Wartezeit ≥ 1 s;
      ein Abruf von `/open/dataset.json` verbraucht **nichts** vom Sitemap-Kontingent —
      `AK-23`, `EC-04`
- [x] **T11** `[P]` · Antwort-Abonnent in `src/EventSubscriber/`: setzt `X-Robots-Tag: noindex`
      auf jede Antwort einer Route aus der Ausschlussliste — auch auf Weiterleitungen und
      Fehlerantworten. Überschreibt keine vorhandene Kopfzeile (Muster
      `SecurityHeadersSubscriber`). Prüflauf `tests/Functional/Seo/RobotsHeaderTest.php`: alle 16
      Wege, die zwei reinen Weiterleitungen eingeschlossen, tragen die Kopfzeile; **keine** Seite
      aus dem Verzeichnis trägt sie, und keine enthält `noindex` im Quelltext —
      `AK-15, AK-16`
- [x] **T12** `[P]` · `public/robots.txt`: Sitemap-Zeile mit absoluter Adresse; Sperren für
      `/api/` und je Sprache `/{s}/admin`, `/{s}/profile`, `/{s}/api/` — **ausgeschrieben, ohne
      `*` und `$`**. Prüflauf `tests/Integration/Seo/RobotsTxtTest.php` mit einer reinen
      Präfixauswertung (längste Regel gewinnt): Sperren greifen in allen vier Sprachen; jede
      Verzeichnisseite, `/open.json`, `/open/dataset.csv` und `/open/dataset.json` erlaubt; die
      16 Ausschlusswege mit Beispielparametern **erlaubt**; keine Sperre der ganzen Seite; kein
      Platzhalter in der Datei. ⚠ Kein Verzeichnis `public/sitemap*` anlegen (BF-100) —
      `AK-17, AK-18, AK-19, AK-20, AK-21`

**Verifikation Ebene 3:** alle vier Befehle, `php bin/phpunit` vollständig. Dazu einmal von
Hand gegen den Entwicklungsserver: `curl -sI /robots.txt`, `curl -s /sitemap.xml | head`,
`curl -sI /de/login | grep -i x-robots`.

## Ebene 4 · Oberfläche — Seitenkopf

Vier Zustände je Seite: **trifft nicht zu** — dieses Feature legt keine Seite an. Die
Zustände der Sitemap (gefüllt, leer, Fehler, gedeckelt) liegen in T07, T09 und T10.

- [x] **T13** · Seitenkopf in `templates/base.html.twig`: der `canonical`-Block bekommt als
      Vorgabe den Verweis aus T08; die Sprachverweise kommen aus T08 statt aus der Anfrage. Den
      veralteten Kommentar „Standardmäßig leer — bislang füllt sie nur der Vergleichsbereich"
      berichtigen. Prüflauf `tests/Functional/Seo/SeoHeadTest.php`: **jede** Seite aus der
      Sitemap nennt als canonical genau ihre Sitemap-Adresse; die vier Beispielzeilen aus AK-13;
      die Sprachverweise jeder Sitemap-Seite decken sich mit ihrem Sitemap-Eintrag; eine
      Seitenzahl jenseits der letzten Seite nennt sich selbst —
      `AK-12, AK-13, AK-14`, `EC-05`
- [x] **T14** · Die fünf vorlageneigenen `canonical`-Blöcke entfernen
      (`comparison/index`, `comparison/show`, `press/index`, `roadmap/index`,
      `roadmap/changelog`) und die fünf Prüfläufe nachziehen, die canonical- oder Sprachverweise
      prüfen (`ComparisonControllerTest`, `PressControllerTest`, `RoadmapControllerTest`,
      `RoadmapFreshnessTest`, `QueryParameterReflexionTest`). ⚠ **Beim Bau als sechster aufgefallen:** `PressEdgeCaseTest` — er nennt weder canonical noch hreflang und fiel deshalb durch die Suche beim Planen; er nahm die Verweise über den Host der Anfrage aus. Die Adressen lauten danach auch im
      Test auf `https://endlech.lu` — das ist gewollt. ⚠ Mehr als drei Dateien, aber **eine**
      Sorge und mechanisch; nach T13, weil die Seiten sonst zwischendurch ohne canonical dastehen —
      `AK-12`

**Verifikation Ebene 4:** alle vier Befehle, `php bin/phpunit` vollständig.

## Ebene 5 · Feinschliff

- [x] **T15** `[P]` · Randfall EC-01 als Prüflauf in `SitemapGeneratorTest`: Sitemap einmal
      erzeugen, danach die Abfrage scheitern lassen → die gespeicherte, **vollständige** Fassung
      wird ausgeliefert, kein Fehler — `EC-01`
- [x] **T16** `[P]` · Unterlagen nachziehen: in `CLAUDE.md` ein Abschnitt „Sitemap, robots.txt
      und maßgebliche Adressen" mit den Fallstricken aus dem Entwurf (feste Basisadresse, kein
      Auffangen von Datenbankfehlern, 50 + 10 Minuten, kein `noindex` hinter einer
      robots.txt-Sperre, eine neue öffentliche Seite gehört ins Verzeichnis, kein Verzeichnis
      `public/sitemap*`); in `docs/app-shell.md` den Seitenkopf (canonical, Sprachverweise,
      Ausschluss-Kopfzeile) — Projektkonvention, siehe Abdeckung

**Verifikation Ebene 5:** alle vier Befehle, `php bin/phpunit` vollständig.

## Plattform — nach dem Deploy, durch den Betreiber

Diese beiden Aufgaben erzeugen keinen Code und hängen am ausgelieferten Stand. Sie stehen
trotzdem hier, weil ein Kriterium ohne zugeordnete Aufgabe nie erledigt wird.

- [ ] **T17** · Search Console, `sc-domain:endlech.lu` → Sitemaps → `https://endlech.lu/sitemap.xml`
      einreichen; Screenshot mit Status „Erfolgreich" und der Zahl der erkannten Seiten nach
      `qa-report.md` — `AK-24`
- [ ] **T18** · Search Console → Einstellungen → robots.txt-Bericht; Screenshot: abgerufen, ohne
      Fehler und ohne Warnung — `AK-25`

## Abdeckung

Abgegangen aus `spec.md` (AK-01 bis AK-25, EC-01 bis EC-06) und aus diesem Plan (T01 bis T18).

| AK | Aufgaben |
|---|---|
| AK-01 | T03, T07, T09 |
| AK-02 | T04, T06, T07 |
| AK-03 | T02, T05, T09 |
| AK-04 | T09 |
| AK-05 | T05, T07 |
| AK-06 | T04 |
| AK-07 | T07 |
| AK-08 | T01, T06, T09 |
| AK-09 | T01, T06, T09 |
| AK-10 | T06, T07 |
| AK-11 | T01, T07 |
| AK-12 | T08, T13, T14 |
| AK-13 | T05, T08, T13 |
| AK-14 | T05, T08, T13 |
| AK-15 | T11 |
| AK-16 | T04, T11 |
| AK-17 | T12 |
| AK-18 | T12 |
| AK-19 | T12 |
| AK-20 | T12 |
| AK-21 | T12 |
| AK-22 | T04, T07 |
| AK-23 | T10 |
| AK-24 | T17 |
| AK-25 | T18 |

| EC | Aufgaben |
|---|---|
| EC-01 | T15 |
| EC-02 | keine — beschreibt hingenommenes Verhalten der Detailseite (404 für ein gelöschtes Restaurant) innerhalb der Frist aus AK-09 |
| EC-03 | keine — die Spec nimmt das Aufteilen ausdrücklich aus dem Umfang |
| EC-04 | T10 |
| EC-05 | T13 |
| EC-06 | T09 |

**AK ohne Aufgabe:** keine.
**Aufgabe ohne AK:** T16 (Unterlagen) — zulässig: `CLAUDE.md` verlangt, Code und Referenz
gemeinsam zu ändern. T01, T02 und T03 tragen „Grundlage für …" und zusätzlich die Kriterien, die
ohne sie nicht erfüllbar wären.

## Parallelisierung

| Ebene | Gleichzeitig | Dateien je Aufgabe | Warum gefahrlos |
|---|---|---|---|
| 1 | T01, T02, T03 | T01 `config/packages/cache.yaml` · T02 `config/services.yaml` · T03 `tests/Fixtures/Sitemap/*.xsd` | drei verschiedene Dateien, keine liest die andere |
| 2 | T04, T05, T06 | T04 `src/Seo/`-Verzeichnisklassen, `tests/Unit/Seo/SeoRegistryTest.php` · T05 `src/Seo/`-Adressbildner, `tests/Integration/Seo/SeoUrlBuilderTest.php` · T06 `src/Repository/RestaurantRepository.php`, `tests/Integration/Repository/RestaurantRepositoryTest.php` | verschiedene Dateien; T05 liest seinen Parameter über `#[Autowire]`, nicht über `services.yaml`. Keine Registrierung nötig (Autowiring) |
| 2 | — | T07, T08 **der Reihe nach danach** | beide brauchen T04 und T05; T07 zusätzlich T06 |
| 3 | T09, T10, T11, T12 | T09 `src/Controller/Seo/`, `config/routes.yaml`, `tests/Functional/Seo/SitemapControllerTest.php` · T10 `config/packages/framework.yaml`, `src/EventSubscriber/RouteRateLimitSubscriber.php`, `tests/Unit/EventSubscriber/RouteRateLimitSubscriberTest.php` · T11 neuer Abonnent in `src/EventSubscriber/`, `tests/Functional/Seo/RobotsHeaderTest.php` · T12 `public/robots.txt`, `tests/Integration/Seo/RobotsTxtTest.php` | keine gemeinsame Datei. T11 und T12 brauchen nur das Verzeichnis aus Ebene 2, nicht den Endpunkt aus T09. ⚠ T09 und T11 legen Dateien im selben **Ordner** `tests/Functional/Seo/` an — verschiedene Dateien, kein Konflikt |
| 4 | — | T13, dann T14 | T14 entfernt, was T13 ersetzt |
| 5 | T15, T16 | T15 `tests/Integration/Seo/SitemapGeneratorTest.php` · T16 `CLAUDE.md`, `docs/app-shell.md` | keine gemeinsame Datei; der Kommentar in `base.html.twig` ist schon in T13 erledigt |

## Vor dem Bauen

- [ ] Feature-Branch: `git checkout -b feature/10-sitemap-robots` — `spec.md`, `design.md`,
      `tasks.md` und die Zeile in `features/index.md` sind noch nicht committet und gehen mit
- [ ] Test-DB aufgesetzt: `make test-db-setup`
- [ ] Referenzstand festgehalten: `lint:container` meldet **genau einen** Fehler (WebAuthn-Alias),
      `doctrine:schema:validate` ist rot. Beides war vor Feature 10 so — kein Anhaltegrund, aber
      auch nichts, das wachsen darf
- [ ] Keine Schlüssel nötig — Feature 10 braucht weder Geheimnis noch Umgebungsvariable
- [ ] Für T17/T18: Zugang zur Search Console für `sc-domain:endlech.lu` (Betreiber)

---

## Abschlussbericht (`sdd-build`, 2026-09-12)

Branch `feature/10-sitemap-robots`, **nicht committet**. Status bleibt `building`; abnehmen darf
nur `/sdd-qa 10`.

### 1 · Umgesetzt

`/sitemap.xml` liefert (21 + n) × 4 Einträge mit je vier Sprachverweisen und Vorgabe `lb`,
gültig gegen `sitemap.xsd`, 50 Minuten zwischengespeichert, gedeckelt auf 60 je Stunde je Adresse.
`public/robots.txt` sperrt Verwaltung, Profil und Schnittstelle in allen Sprachen und nennt die
Sitemap. Jede Sitemap-Seite trägt einen canonical-Verweis auf genau ihre Sitemap-Adresse, die
Sprachverweise kommen aus demselben Adressbildner, alle auf `https://endlech.lu`. Die 16
Ausschlusswege tragen `X-Robots-Tag: noindex`. **16 von 18 Aufgaben erledigt** — T17 und T18 sind
Plattformaufgaben nach dem Deploy. Suite: **1187 Tests grün** (Referenz vor dem Bau 1118),
`lint:container` weiterhin genau der eine bekannte Fehler.

Gegenproben gefahren, jede Absicherung wurde rot, wenn man sie entfernt: Abdeckungs-Prüflauf
(Route aus dem Verzeichnis genommen), `try`/`catch` im Erzeuger (AK-11), `<lastmod>` in der
Vorlage (AK-07), Deckel-Zweig entfernt (AK-23), Ausschluss stillgelegt (AK-15), robots.txt mit
Sperre der Anmeldeseite und `Disallow: /` (AK-19–21), fremdes Stylesheet auf Vergleichs- und
Presseseite (die zwei gelockerten Prüfläufe), alte Bedingung im `LocaleSubscriber`.

Gegen einen laufenden Server, **Produktionsmodus**: `robots.txt` 200 `text/plain`; Sitemap 200
`application/xml`, 128 Einträge, Schema gültig, `max-age=600, public`; genau **60× 200, dann 429**
mit `Retry-After`; `/open/dataset.json` zugleich 200; `/de/sitemap.xml` 404.

### 2 · Offene Akzeptanzkriterien

- **AK-24, AK-25** — Plattformnachweise in der Search Console. Erst nach dem Deploy möglich
  (T17, T18), durch den Betreiber.
- **AK-13, Beispielzeile `?page=abc`** — so nicht beobachtbar: Die Restaurantliste antwortet darauf
  schon heute mit **400** (B05), eine Seite mit Verweis entsteht nie. Die Abbildungsregel selbst ist
  im Adressbildner geprüft. Als **OF-06** in `spec.md`; der Prüflauf hält das tatsächliche Verhalten
  fest (400, kein Verweis).
- **AK-17 „als Klartext ausgeliefert"** — im Prüflauf nicht erreichbar, weil der Webserver die
  statische Datei liefert, nicht die Anwendung. Belegt nur durch den Serverabruf oben; auf Produktion
  in der QA nachzumessen.

### 3 · Getroffene Annahmen

- **Parallele Aufgaben ohne Subagenten gebaut.** `[P]` erlaubt Parallelität, verlangt sie nicht; die
  Aufgaben waren klein und hingen an Konventionen dieses Projekts, die ein Subagent erst hätte lesen
  müssen. Kein Einfluss auf das Ergebnis.
- **Abdeckungs-Prüflauf in T04 aufgenommen.** Der Entwurf verlangt ihn (Entscheidung 3), der
  Aufgabenplan hatte ihn keiner Aufgabe zugeordnet — eine Lücke im Plan, keine neue Anforderung.
  Dafür bekam das Seitenverzeichnis eine dritte Liste „bewusst weder angeboten noch ausgeschlossen".
- **`/{s}/verify` und `/{s}/verify/resend`** stehen in dieser dritten Liste und damit weder in der
  Sitemap noch unter Ausschluss. Sie sind Sackgassen wie die Dankeseiten, aber nicht in AK-15
  genannt — **OF-05** in `spec.md`.
- **`app_logout`** steht ebenfalls in der dritten Liste (reine Weiterleitung).
- **Schemadateien unter `tests/Fixtures/Sitemap/`** — eine neue Ablage; das Projekt hatte keine.
  `sitemap.xsd` unverändert von sitemaps.org mit Lizenzvermerk (CC BY-SA).
- **Die Vergleichs-Slugs kommen aus dem Enum `Competitor`**, nicht aus `ComparisonRegistry` — das
  Verzeichnis selbst baut auf demselben Enum auf, und so muss das Seitenverzeichnis keine
  Seiteninhalte erzeugen.

### 4 · Systemweite Änderungen

- **`templates/base.html.twig`** — canonical-Verweis und Sprachverweise auf **jeder** Seite kommen
  jetzt aus dem Adressbildner, mit Host `https://endlech.lu` statt dem Host der Anfrage. Betrifft
  alle öffentlichen Seiten.
- **Fünf Vorlagen** verlieren ihren eigenen canonical-Block (Vergleichsübersicht, Vergleichsseite,
  Presse, Roadmap, Changelog).
- **`src/EventSubscriber/LocaleSubscriber.php`** — steigt jetzt auch aus, wenn die **Hauptanfrage**
  zustandslos ist. Beim Selbsttest gemessen: Die 429 des Sitemap-Deckels (und jede 5xx) setzte ein
  `PHPSESSID`-Cookie, weil Symfony die Fehlerseite in einer Unteranfrage ohne `_stateless` rendert;
  im Debug-Modus wurde daraus eine 500, in Produktion eine Warnung je gedeckeltem Abruf in Sentry.
  Nach der Behebung: 429 ohne Cookie. Wirkt auch für `/health`. Gewöhnliche HTML-Seiten sind
  unberührt — ihre Fehlerseiten lesen die Sprache weiter aus der Sitzung (per Gegenprobe belegt).
- **`config/packages/framework.yaml`, `when@test`: `disallow_search_engine_index: false`.** Symfony
  setzt `X-Robots-Tag: noindex` im Debug-Modus auf jede Antwort; ohne die Zeile wäre der Prüflauf für
  AK-15 auch ohne den neuen Abonnenten grün gewesen. Wirkt auf den gesamten Testbetrieb. **Lokal im
  Entwicklungsbetrieb trägt weiterhin jede Seite `noindex`** — dort nicht aussagekräftig.
- **Neuer Deckel `sitemap`** in `framework.yaml` samt Test-Override; vierter Zweig im
  `RouteRateLimitSubscriber`.
- **Neuer Parameter `app.canonical_base_url`**, neuer Pool `cache.sitemap`, neuer Routenblock `seo`
  in `config/routes.yaml` samt `exclude`-Eintrag.
- **Zwei bestehende Prüfläufe gelockert**, beide mit Gegenprobe: `ComparisonControllerTest` und
  `PressEdgeCaseTest` nehmen canonical- und Sprachverweise über ihre `rel`-Art aus der
  „fremde Ressource"-Prüfung aus. `PressEdgeCaseTest` stand **nicht** im Plan — er nennt weder
  canonical noch hreflang und fiel deshalb durch die Suche beim Planen.
- **`docs/app-shell.md`** — Dokumentkopf neu beschrieben; die Aussage „hreflang spiegelt die
  Abfragezeichenfolge" als überholt markiert.
- **`CLAUDE.md`** — neuer Abschnitt, Routentabelle, Key-Files-Tabelle.

### Nebenbefunde, nicht Teil von Feature 10

- `lint:container` ist seit vor diesem Bau rot (WebAuthn-Alias), `doctrine:schema:validate` ebenfalls.
- Auf diesem Rechner belegt der Container eines anderen Projekts Port 3306; die Entwicklungs-DB
  `endlech` ist darüber nicht erreichbar. Der Serverabruf lief deshalb gegen `endlech_test`.

## Fehlerauftrag BF-147 (`sdd-build`, 2026-09-13)

Eingang: Fehlerauftrag aus `qa-report.md`. Das Feature stand auf `approved`; der Betreiber hat
entschieden, BF-147 vor dem Deploy zu beheben, und den Status dafür zurücknehmen lassen. Branch
`fix/bf-147-seitenzahl` von `main` (nach Merge von #125).

### 1 · Behoben

- **BF-147** — Die Seitenzahl bleibt nur noch auf Seiten, die blättern.
  `SeoRegistry::PAGINATED_ROUTES` (`app_restaurant_index`, `app_board_index`) mit
  `isPaginatedRoute()`; `SeoExtension` reicht die Abfrage nur für diese Routen an den Adressbildner
  weiter. `SeoUrlBuilder` bleibt unverändert, sein Docblock nennt jetzt, wer entscheidet.
  - Reproduktion `Qa10SitemapBestandTest::testBf147SeitenzahlNurAufSeitenDieBlaettern`: **vor** der
    Reparatur ohne Überspringen ausgeführt → rot (`…/de/about?page=2`), danach grün.
  - Neu: `SeoExtensionTest::testSeiteOhneBlaetternVerliertDieSeitenzahl` (Über-uns, Detailseite,
    Sprachverweise einer ausgeschlossenen Seite) und `::testBoardUebersichtBehaeltIhreSeitenzahl`.
  - Neu: `SeoRouteCoverageTest::testBlaetterndeSeitenStimmenMitDenControllernUeberein` — gleicht die
    Liste mit den Controller-Methoden angebotener Routen ab, die `page` aus der Abfrage lesen.
  - **Gegenproben gefahren:** Reparatur in `SeoExtension` entfernt → beide BF-147-Läufe rot; Board aus
    der Liste genommen → Abgleich und Board-Lauf rot. Danach wiederhergestellt.

### 2 · Nicht behoben

- **BF-148** — liegt in `spec.md` (EC-05), die `sdd-build` außerhalb der offenen Fragen nicht ändert.
  Als **OF-08** an den Betreiber übergeben.
- **Board-Übersicht jenseits der letzten Seite** (`?page=99` → leere Seite, 200, nennt sich selbst):
  Verhalten des Boards, nicht dieses Features — Teil von **OF-07**.

### 3 · Getroffene Annahmen

- **Die Board-Übersicht behält ihre Seitenzahl.** AK-13 nennt nur die Restaurantliste, und
  `design.md` Entscheidung 11 nimmt an, keine andere Seite blättere. Das Board liest aber `page`, und
  der Grund der Regel (Google: Folgeseiten nicht auf Seite 1 kanonisieren) gilt dort genauso. So
  verhielt es sich auch vor der Reparatur, und der Vorschlag im QA-Bericht nennt es ausdrücklich.
  Festgehalten als **OF-07**; entscheidet der Betreiber anders, ist es ein Eintrag weniger in der Liste.

### 4 · Systemweite Änderungen

- `CLAUDE.md` und `docs/app-shell.md`: Die Seitenzahl-Regel gilt nur auf blätternden Seiten, mit
  Verweis auf die Liste und den Abgleich.
- `features/befunde.md`: Status von BF-147 („behoben auf Branch, QA ausstehend") und BF-148 („als
  OF-08 übergeben"). Verschoben nach *Behoben* wird erst mit der Auslieferung.
- `features/index.md`: Status `building`.

### 5 · Verifikation

| Befehl | Ergebnis |
|---|---|
| `php bin/phpunit` | **1194 Tests grün**, 10 übersprungen (vorher 1191/11: +3 neue, Reproduktion nicht mehr übersprungen) |
| `php bin/console lint:twig templates/` | OK, 127 Dateien |
| `php bin/console doctrine:schema:validate` | Mapping OK; Datenbankteil wie vor dem Bau |
| `php bin/console lint:container` | rot wie vor dem Bau (WebAuthn-Alias), am gestashten Stand gegengeprüft |
| `make fix-check` | ⚠ **nicht ausführbar** — das Ziel gibt es im Makefile nicht, und `vendor/bin/php-cs-fixer` ist nicht installiert (`make fix` verweist ebenfalls ins Leere). Ersatzweise `php -l` auf allen sechs geänderten Dateien: fehlerfrei |

⚠ **Die Testdatenbank war beim Start nicht erreichbar**: beide MySQL-Container waren beendet
(Neustart von Docker). `mika-database-1`, der auf diesem Rechner Port 3306 hält und `endlech_test`
trägt, wurde wieder gestartet. Die 26 zunächst roten Läufe (13 Fehler mit `Connection refused`,
13 Fehlschläge) waren nach dem Start ohne weitere Änderung grün.

Übergabe: `/sdd-qa 10`.
