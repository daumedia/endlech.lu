# Changelog

Alle Änderungen an **Endlech.lu** werden in dieser Datei dokumentiert.

![Version](https://img.shields.io/badge/version-2026.08.09-blue)
![Status](https://img.shields.io/badge/status-beta-green)

## [Unreleased]

### Changed
- **Die Info-Seite heißt jetzt „Über Endlech" statt „Über uns"** – in allen vier Sprachen (`About Endlech`, `À propos d'Endlech`, `Iwwer Endlech`), im Header-Link, im Seitentitel und in der Überschrift. „Über uns" ist ein austauschbarer Standardtitel; der Markenname sagt schon in der Navigation, worum es auf der Seite geht, und taucht damit auch im Browser-Tab und in Suchergebnissen auf. URL und Routenname (`/about`, `app_about`) bleiben unverändert – keine toten Links, kein Redirect nötig.
- **Die mobile Bottom-Navigation behält das kurze Label** und nutzt dafür den neuen Schlüssel `nav.about_short`. Sie hat bei vier Rasterspalten und `text-xs` nur rund 80 px pro Eintrag; „Über Endlech" bräche dort um und ließe die vier Einträge unterschiedlich hoch erscheinen. Der zweite Schlüssel ist deshalb Absicht und **kein Duplikat, das sich zusammenfassen lässt** – Header und Bottom-Nav haben schlicht verschiedene Platzbudgets.

### Added
- **Open-Startup-Seite (`/open`):** Endlech.lu verlangt von Restaurants, ihre Barrierefreiheit offenzulegen – dieselbe Offenheit gilt jetzt für das Projekt selbst. Die Seite zeigt drei Blöcke: **Plattform** (Anzahl Lokale, vom Team geprüfte Einträge, Abdeckung nach Gemeinde und Kanton, Verteilung der Barrierefreiheits-Punktzahlen von 0 bis 10), **Wirkung** (dokumentierte stufenlose Eingänge, barrierefreie WCs, Türbreiten, Tischabstände, gelieferte Inclusion Boxes) und **Finanzen** (Kosten und Einnahmen, ausschließlich nach Kategorie summiert). Plattform- und Wirkungszahlen kommen live aus der Datenbank und liegen eine Stunde in einem eigenen Cache-Pool `cache.open_stats`; die Finanzzahlen pflegt das Team im Admin. `/open` ist zusätzlich sprachfrei erreichbar – das ist die URL, die in Fördermails und Vorträgen steht und nicht an einer Sprachwahl scheitern darf.
- **Einnahmen bleiben bis zum ersten vollständigen Quartal zurückgehalten.** Eine Einnahmenzeile nahe null schreckt potenzielle Partner ab, statt Vertrauen zu schaffen; erst ein abgeschlossenes Kalenderquartal zeigt, ob eine Zahl Signal oder Zufall ist. Die Sperre ist **strukturell und nicht kosmetisch**: Die Beträge stehen gar nicht erst im Ergebnis-Array, sonst wären sie trotz ausgeblendeter Anzeige über `/open.json` abrufbar. Kosten und Wirkung werden von Anfang an veröffentlicht. Auf der Seite steht sichtbar, warum die Spalte leer ist und ab wann sie gefüllt wird.
- **Neue Entity `FinanceEntry` mit Admin-CRUD unter `/admin/finanzen`** – kein Buchhaltungs-Anschluss, ein Eintrag pro Beleg. Enums `FinanceType` (income/expense) und `FinanceCategory` (Hosting, E-Mail-Versand, Apple Developer, Domain, Material Inclusion Box, Mitgliedschaft, öffentliche Förderung, Sponsoring, Spende, je ein Sammelposten). Es gibt **kein Feld für die Richtung**: Sie hängt an der Kategorie und wird von `setCategory()` gesetzt – zwei Felder für dieselbe Aussage wären eine Gelegenheit, sie widersprüchlich zu füllen. Beträge sind immer positiv; ein negativer Wert würde die veröffentlichte Summe doppelt invertieren und wird mit 422 abgelehnt. Felder für Vertragspartner, Restaurant oder Rechnungsnummer existieren bewusst nicht – was nicht erfasst ist, kann nicht versehentlich veröffentlicht werden. Jede Änderung im Admin wirft den Kennzahlen-Cache weg, damit die öffentliche Seite nicht eine Stunde lang den alten Betrag zeigt.
- **Neue Entity `MetricSnapshot` und Befehl `app:metrics:snapshot`** für die Verlaufsanzeige. Ein aus den heutigen Daten zurückgerechneter Verlauf würde sich rückwirkend ändern, sobald jemand einen Eintrag bearbeitet – als Beleg gegenüber einem Ministerium wäre er damit wertlos. Der Snapshot friert deshalb Monatswerte ein: typisierte Spalten für die Grafiken, dazu die vollständige Momentaufnahme als JSON, damit eine spätere Kennzahl nicht rückwirkend fehlt. Ein Unique-Index auf dem Monat macht den Lauf auf Datenbankebene idempotent. `App\Schedule` deklariert den Lauf am Ersten jedes Monats um 03:15 – ⚠️ Symfony Scheduler braucht dafür einen `messenger:consume`-Worker, den Production nicht hat; dort läuft der Befehl **per Cron** (Eintrag im README). Zusätzlich lässt sich ein Snapshot im Admin von Hand auslösen, weil eine ausgefallene Historie sonst unbemerkt bliebe und sich nicht nachträglich erzeugen lässt.
- **Offener Datensatz unter CC BY 4.0:** `/open/dataset.csv` und `/open/dataset.json` liefern die vollständigen Barrierefreiheits-Daten aller Lokale inklusive Punktzahl, Gemeinde und Kanton; `/open.json` spiegelt die Kennzahlen der Seite maschinenlesbar. Alle drei sind **sprachfrei geroutet** (eigener `open_data`-Block in `config/routes.yaml`) – ein `/de/open.json` würde zitierte URLs auf vier Varianten verteilen. E-Mail-Adressen und Telefonnummern sind bewusst **nicht** enthalten: Ein Sammelabzug davon wäre eine Adressliste, kein Barrierefreiheits-Datensatz. Die Antworten sind eine Stunde öffentlich cachebar; dafür nötig war der `NO_AUTO_CACHE_CONTROL`-Marker, weil Symfonys Session-Listener sonst auf `private, must-revalidate` umstellt, sobald irgendwo im Request eine Session angefasst wurde.
- **Gemeinde- und Kantonszuordnung (`App\Open\CantonResolver`):** `Restaurant::$city` ist ein Freitextfeld – dort steht mal die Gemeinde („Strassen"), mal eine Ortschaft darin („Belval"), mal ein Stadtteil („Bonnevoie"), mal die luxemburgische Schreibweise („Lëtzebuerg"). Eine reine `GROUP BY city`-Auswertung zählte diese Fälle als verschiedene Orte und machte jede Abdeckungsquote falsch. Der Resolver kennt alle **100 Gemeinden in 12 Kantonen** (Stand nach den Fusionen vom 1. Januar 2024) samt Alias-Tabelle für Stadtteile und gebräuchliche Schreibweisen. Er rät **nicht**: Ein unbekannter Wert bleibt unzugeordnet und wird auf der Seite als solcher ausgewiesen – eine erfundene Zuordnung wäre auf einer Transparenzseite schlimmer als eine sichtbare Lücke. Gemeinde- und Alias-Index sind getrennt, damit beim Zerlegen zusammengesetzter Angaben („Rue de la Gare, Strassen") nur echte Gemeindenamen greifen.
- **Neue Restaurant-Felder `doorWidthCm` und `tableSpacingCm`** (Maße in Zentimetern, DIN-18040-Schwelle 90 cm), damit „Tür breit genug" nicht Auslegungssache bleibt. Beide sind nullable **ohne Default**: `null` heißt „nicht ausgemessen", nicht „zu schmal" – ein 0-Default hätte jedes nie erfasste Haus als Negativbefund in die veröffentlichte Zahl geschrieben. Die Detailseite zeigt entsprechend drei Zustände (erfüllt, zu eng, nicht ausgemessen). In der iOS-API stehen die Werte in einem eigenen Block `measurements` statt in `accessibility`: Dort ist jeder Wert ein Boolean, und ein `null` in diesem Vertrag wäre ein Kompatibilitätsbruch. Migration `Version20260820200000`.
- **Barrierefreiheits-Punktzahl (`App\Open\AccessibilityScore`)** – acht gleichgewichtete Merkmale, Anteil mal zehn. Eine Gewichtung („Rampe zählt dreifach") wäre fachlich vertretbar, auf einer Transparenzseite aber nicht mehr nachrechenbar. Nicht erfasste Maße zählen als nicht erfüllt: Der Wert misst *dokumentierte* Barrierefreiheit und rundet nicht heimlich zugunsten schlecht gepflegter Einträge.
- **Gestalterischer Feinschliff der Open-Startup-Seite.** Die Seite stand funktional, sprach aber nicht die Sprache der übrigen Außenseiten. Sie hat jetzt ein Hero-Band im Cyan-Purple-Verlauf mit der Zahl der erfassten Restaurants als **Leitzahl** (wer drei Sekunden hinsieht, soll sie mitnehmen – vier gleichrangige Kacheln hinterlassen nichts), abwechselnde Sektionsflächen, Emoji in farbigen Kacheln, `motion-safe:transition`, Fokus-Outlines und 48-px-Tap-Targets wie auf der Partner- und Organisationsseite. Die Kennzahlen zeigen zusätzlich die **Veränderung gegenüber dem zuletzt festgehaltenen Monat** – Bezugspunkt ist bewusst der Snapshot und nicht „vor 30 Tagen", weil nur er ein nachprüfbarer Stand ist; ohne Snapshot erscheint gar keine Veränderung, statt eine gegen einen unbekannten Ausgangswert zu erfinden.
- **Punkteverteilung als Histogramm statt elf gestapelter Querbalken.** Die Punktzahl ist eine geordnete Skala; erst nebeneinander liest man die Form der Verteilung – wo der Gipfel liegt, wo die Ausreißer sitzen – in einem Blick. Die Sektion wurde dabei rund 250 px kürzer. Beschriftet werden nur die höchsten Säulen, und bei mehreren gleich hohen alle: Eine davon herauszugreifen suggerierte, sie sei die höchste.
- **Farbkorrektur mit gemessenem Ergebnis.** Die frühere Ampel in der Verteilung (grün ≥ 8, cyan ≥ 5, bernstein darunter) kodierte die Balkenlänge ein zweites Mal als Farbe und verbrauchte damit den einzigen freien Kanal für Information, die schon dastand; bernstein lag zudem bei **1,49:1 Kontrast** und außerhalb des zulässigen Helligkeitsbands. Jetzt eine Farbe für die Serie, die Position trägt die Ordnung. In der Finanzsektion sind die **Ausgaben von Bernstein auf Cyan** gewechselt: Bernstein ist eine Warnfarbe, und „Hosting" in Warnorange liest sich wie ein Problem – dabei sind Betriebskosten genau das, was die Seite rechtfertigen soll. Einnahmen stehen in Purple; das Paar ist geprüft (ΔE 26,4 normal / 13,6 Deuteranopie, beide über 3:1 gegen Weiß).
- **Verlaufsgrafiken repariert.** `preserveAspectRatio="none"` staucht das Koordinatensystem – die Punkte rendern dadurch als Ellipsen. Sie sind entfallen; der aktuelle Wert steht stattdessen als Zahl über der Grafik, die Linie liegt jetzt bei den spezifizierten 2 px (`vector-effect="non-scaling-stroke"`). Aus den beiden Verlaufskarten sind damit Kennzahl-Kacheln mit Verlaufslinie geworden: Beschriftung, aktueller Wert, Veränderung, Linie.
- **Druckansicht.** Der erste Anwendungsfall der Seite ist ein Fördergespräch, und dafür wird sie als PDF gespeichert. Header, Footer, Bottom-Navigation und Cookie-Banner tragen jetzt `print:hidden` (im Basis-Layout, gilt also für jede Seite), Verlaufsbänder drucken ohne Farbfläche und mit dunklem Text – sonst bliebe weiße Schrift auf weißem Papier –, zugeklappte `<details>` öffnen sich, weil die Tabellenansicht auf Papier die einzige Fassung mit allen Werten ist, und Diagramme werden nicht über eine Seitengrenze zerrissen. Balkenfarben bleiben erhalten (`print-color-adjust: exact`), weil sie hier die Daten sind und nicht Dekoration.
- **Zahlen locale-korrekt.** Anteile und Punktzahlen liefen über eine fest deutsche Formatierung und standen dadurch in der englischen Fassung als „27,3 %" statt „27.3 %". Sie laufen jetzt über `format_number` (`twig/intl-extra`), passend zur bereits verwendeten `format_currency`.
- **Partner-Landing-Page & Warteliste (`/partner`):** Für das kostenpflichtige Partnerprogramm gibt es jetzt eine eigene Seite mit Wartelisten-Anmeldung per **Double-Opt-In**. Preise und Paketumfang stehen bewusst noch nicht fest – die Seite verarbeitet daher **keine Zahlung und legt keinen Account an**, sondern sammelt nur Anmeldungen; wo ein Preis stünde, steht der Hinweis, dass die Warteliste ihn zuerst erfährt. Der Integritätsblock ist ein eigener, farblich abgesetzter Abschnitt statt Kleingedrucktes: Eintrag, Barrierefreiheits-Daten, Score und Verifizierungs-Badge bleiben kostenlos, öffentlich und vollständig unabhängig von jeder Mitgliedschaft – bezahlt werden ausschließlich Beratung, Materialien und Begleitung. Neue Entity `PartnerWaitlistEntry` (Status als Backed Enum `PartnerWaitlistStatus`: pending → confirmed → contacted → converted/declined, optionale Verknüpfung mit einem bestehenden `Restaurant`, `consentAt` als DSGVO-Nachweis, `source` für spätere Attribution), Migration `Version20260820000000`. Die Anmeldung ist per Rate-Limiter auf 5 Versuche je IP und Stunde gedeckelt und durch einen Honeypot geschützt, der bei einem Treffer dieselbe Erfolgsantwort liefert wie eine echte Anmeldung – ein Validierungsfehler hätte dem Bot die Falle verraten. Admin-Bereich unter `/admin/partner-warteliste` mit Status-Filter, Datums-Sortierung, Detailansicht und Restaurant-Verknüpfung.
- **Barrierefreiheit der Partner-Seite (WCAG 2.2 AA als Abnahmekriterium):** Die Seite funktioniert **vollständig ohne JavaScript** – Turbo ist reine Verbesserung. Erster Turbo-Stream im Projekt: Bei aktivem JavaScript wird nur das Formular gegen die Erfolgsmeldung getauscht, ohne JavaScript greift der klassische Redirect samt Flash. Der Fokus springt nach einem Fehlversuch auf das erste fehlerhafte Feld – serverseitig über `autofocus` statt über ein Skript. Neues Partial `templates/partials/_form_field.html.twig` kapselt Label, Widget, Hilfetext und Fehlermeldung samt `aria-describedby`/`aria-invalid` und löst zugleich den bislang zehnfach kopierten Input-Klassenstring ab. Wichtig dabei: In Twig unterdrückt nur `false` ein Attribut – `null` hätte `aria-invalid=""` gerendert, was Screenreader als „ungültig" lesen. Das FAQ nutzt natives `<details>/<summary>` (tastaturbedienbar, ohne JavaScript, meldet seinen Zustand selbst an Screenreader) – ein handgeschriebenes `aria-expanded` steht bewusst **nicht** darin, weil es sich ohne JavaScript nicht aktualisieren ließe und nach dem ersten Klick schlicht falsch wäre. Fokus-Indikatoren sind echte `outline`-Ringe statt `box-shadow` (die im Windows-Kontrastmodus verschwinden), Kontraste nachgerechnet (Feld-Ränder ≥ 3:1, Fließtext ≥ 4,5:1), Pflichtfelder als **Text** gekennzeichnet, `prefers-reduced-motion` respektiert.
- **Organisations-Landing-Page & Warteliste (`/organisationen`):** Zweite Zielgruppe neben dem Partnerprogramm, mit drei kommerziell grundverschiedenen Typen: **Gemeinden** beauftragen eine bezahlte Erhebung ihrer Gastronomie, **Unternehmen** sponsern, **Vereine** sitzen im Beirat – dort fließt in keine Richtung Geld. Der Vereins-Zweig ist bewusst **kein Vertriebskanal**: Weder Seite noch Bestätigungsmail stellen ihn als solchen dar, weil der Beirat über die Barrierefreiheits-Kriterien und deren Gewichtung entscheidet und genau diese Unabhängigkeit sonst hinfällig wäre. Eine Seite statt drei gestapelter Landing Pages: kurzer gemeinsamer Einstieg, drei Anker-Karten, danach die drei Sektionen. Auf der Gemeinde-Sektion steht ausdrücklich, dass die **Erhebung** bezahlt wird und nicht das Ergebnis – Werte werden veröffentlicht, auch wenn sie unbequem ausfallen. Die Sponsoring-Ausschlussregel steht öffentlich auf der Seite (keine Gastronomieketten, keine Umbaufirmen, keine Lieferanten, die wir in Beratungsberichten nennen könnten). Neue Entity `OrganisationWaitlistEntry` mit Enums `OrganisationType`, `OrganisationTimeframe`, `SponsorshipInterest`, `CollaborationInterest`; Migration `Version20260820100000`.
- **Typabhängige Validierung über Validierungsgruppen:** Die typspezifischen Felder sind alle nullable, aber nicht beliebig kombinierbar. Der Formulartyp leitet die Gruppe aus `type` ab, und die jeweils fremden Felder tragen in den anderen Gruppen ein `IsNull`- bzw. `Count(max: 0)`-Constraint. Zusätzlich baut ein `PRE_SUBMIT`-Listener nur die Felder des übermittelten Typs auf – ein manipulierter Request, der einer Gemeinde Sponsoring-Interessen unterschiebt, wird dadurch mit 422 abgelehnt statt still gespeichert. Beim Rendern werden hingegen **alle** Blöcke aufgebaut: Nur so ist die Seite ohne JavaScript benutzbar, denn dann sind alle drei Feldgruppen sichtbar und beschriftet. Der Stimulus-Controller blendet im Browser lediglich aus, was nicht zum gewählten Typ gehört, und sagt den Wechsel in einer Live-Region an. Der Typ-Selektor sind echte Radios in einem `<fieldset>` – damit gibt es Pfeiltasten-Navigation ohne ARIA-Nachbau.
- **Refactor: gemeinsame Wartelisten-Mechanik.** Token-Erzeugung, Versand der Bestätigungsmail, Token-Einlösung und die interne Team-Meldung liegen jetzt in `App\Waitlist\WaitlistConfirmationService`, den sich beide Wartelisten teilen; Erfolgsmeldung und Bestätigungsseite sind geteilte Twig-Partials. Beide Entities implementieren `WaitlistEntryInterface`. Der Partner-Flow verhält sich unverändert – abgesichert durch die 20 bestehenden Tests, die vor und nach dem Umbau grün sind. Der Admin ist zu einer **kombinierten Ansicht** unter `/admin/warteliste` zusammengelegt (Filter nach Quelle, Organisationstyp und Status, Datums-Sortierung), die Status-Enums sind zu einem gemeinsamen `WaitlistStatus` mit dem zusätzlichen Wert `qualified` verschmolzen.
- **Map:** Kartenansicht der Locations. *(geplant)*

---

## [2026.08.09] – Dreiwertige Vorschlags-Antworten & Fehler-Tracking

### Changed
- **Restaurant vorschlagen – „Weiß nicht" ist jetzt eine eigene Antwort:** Barrierefreiheit, Ernährungsoptionen und Zahlungsmethoden waren 12 einfache Checkboxen; ein leeres Häkchen bedeutete dadurch zweierlei zugleich – „gibt es nicht" und „weiß ich nicht". Der alte Hint sagte das offen („Unbekannte Felder einfach frei lassen"), und das Admin-Detail zeigte es als „Nein / unbekannt" an. Für eine Barrierefreiheits-Plattform ist genau dieser Unterschied wesentlich: „kein barrierefreies WC" ist eine belastbare Information, „unbekannt" ist keine. Jede der 12 Fragen ist jetzt eine **Pflichtfrage mit Ja / Nein / Weiß nicht**, dargestellt als Segmented Control (echte Radio-Inputs als `sr-only` statt `hidden`, damit Tastatur- und Screenreader-Bedienung erhalten bleibt; sichtbarer Fokusring, Tap-Targets über 44 px). Der Wizard blockiert „Weiter", solange Fragen im aktuellen Schritt offen sind, markiert sie rot und springt hin – serverseitig sichert ein `NotNull`-Constraint ab (ungültiger Submit → 422). Neuer Enum `App\Enum\TriState`; bewusst nicht `?bool`, weil sich „Weiß nicht" sonst nicht von „noch nicht beantwortet" unterscheiden ließe und die Pflichtvalidierung damit unmöglich wäre. Das Admin-Detail zeigt Ja grün, Nein rot, Weiß nicht grau. Die `Restaurant`-Entity bleibt bei `bool` – beim Freigeben wird „Weiß nicht" als „Nein" übernommen, was Repository-Filter, den `RestaurantTransformer` (Boolean-Vertrag der iOS-API) und alle Restaurant-Templates unangetastet lässt. Migration `Version20260809000000` überführt `TINYINT(1)` nach `VARCHAR(10) NULL` (kein natives `ENUM` wegen MariaDB 10.5 auf Production) und übersetzt Bestandsdaten `1 → yes`, `0 → unknown`.

### Added
- **Fehler-Tracking mit Sentry:** Fehler auf Production waren bislang unsichtbar – Monolog schrieb nur nach `php://stderr`, wo niemand aktiv hinschaut. `sentry/sentry-symfony` meldet jetzt uncaught Exceptions und Monolog-Records ab `WARNING` an ein Sentry-Projekt in der **EU-Region** (Frankfurt). Das Bundle ist in `config/bundles.php` bewusst **nur für `prod`** registriert: lokale Entwicklung und die Test-Suite kennen die Extension gar nicht und können nichts senden. Der DSN kommt aus `SENTRY_DSN` und wird ausschließlich in der `.env.local` auf dem Server gesetzt – nicht im öffentlichen Repo; ein leerer Wert deaktiviert Sentry lautlos (Muster von `MOBILITEIT_API_KEY`). Datenschutzseitig: `send_default_pii: false` (keine IP-Adressen, Cookies, Header oder Nutzerdaten), `zend.exception_ignore_args` bleibt auf dem PHP-Default `On`, damit keine Funktionsargumente wie Passwörter in Stacktraces landen. 404/405/403/429 sind über `ignore_exceptions` gefiltert, sonst hätte Bot-Traffic die Quota geflutet. Sentry-Releases hängen über `release: 'endlech@%app.version%'` am CalVer-Parameter und ziehen bei jedem Release automatisch mit. Datenschutzerklärung um einen Abschnitt „Fehleranalyse (Sentry)" in allen vier Sprachen ergänzt.

---

## [2026.08.06] – Deployment-Automatisierung, Test-Suite & Bugfixes

### Added
- **Deployment über GitHub Actions & SSH:** Der Git-Button des Hosting-Panels ist abgelöst – **ein Merge nach `production` ist jetzt der Deploy**. Ein Runner öffnet eine SSH-Sitzung und lässt den Server sich selbst aktualisieren: `git reset --hard origin/production` + `git clean -fd` (entfernt, was nicht mehr im Repo steht – der Panel-Button kopierte nur und ließ gelöschte Dateien liegen), dann `composer install --no-dev --optimize-autoloader`, Doctrine-Migrationen und `cache:clear`. Zwei neue Dateien: `.github/workflows/cd.yml` (nur die Verbindung, Sparse-Checkout einer einzigen Datei, `concurrency`-Sperre gegen parallele Deploys) und `.github/deploy.sh` (die gesamte Logik, versioniert und mit `bash -n` prüfbar; `set -euo pipefail`, damit eine gescheiterte Migration den Lauf rot macht statt grün). Vorgeschalteter Job `verify-assets` baut `public/build` neu und vergleicht per `git status --porcelain` – da der Build im Repo liegt, fiele ein vergessenes `npm run build` sonst niemandem auf. `git clean` läuft ohne `-x`, alles Gitignorierte überlebt (`.env.local`, `config/jwt/*.pem`, `public/uploads/`, `var/`). Rollback ist ein Revert-Commit auf `production`, inklusive passender Assets aus demselben Commit. Kein Null-Downtime – für dieses Projekt bewusst akzeptiert. Die Waisen-Inventur gegen den Live-Server ist vorab gelaufen: 18 Waisen, ausschließlich echte Altlasten (JS-Dateien von vor der TypeScript-Umstellung, sechs veraltete `public/build/`-Hashes, die alte `tests/`-Gliederung, ein Cloudways-Platzhalter), und alles Schützenswerte – `.env.local`, JWT-Keys, sämtliche Nutzer-Uploads – nachweislich durch `.gitignore` gedeckt.

- **Sortier-Reihenfolge-Tests & Test-Gliederung nach Art:** Reihenfolge-Tests für die Restaurant-Sortierung (`?sort=rating|name|newest`) auf allen drei Ebenen – Repository (bislang fehlender `findPaginated('rating')`-Reihenfolge-Test), funktionaler Web-Controller (tatsächlich gerenderte Reihenfolge der Restaurant-Karten) und JSON-API (`data`-Reihenfolge + `meta.sort`, inkl. Fallback ungültiger Werte auf `rating`). Die Test-Suite ist jetzt nach Test-Art in Ordner gegliedert – `tests/Unit/` (ohne DB), `tests/Integration/` (KernelTestCase + DB) und `tests/Functional/` (WebTestCase) – mit je einer gleichnamigen `phpunit.dist.xml`-Testsuite (`php bin/phpunit --testsuite Unit|Integration|Functional`); `AbstractWebTestCase` bleibt im `tests/`-Root (Namespace `App\Tests`). 154 Tests, 544 Assertions.
- **Umfassende Test-Suite & CI:** Die automatisierte Testabdeckung wurde von 29 auf **146 Tests** (474 Assertions) ausgebaut – Unit-Tests (Services, Transformer, Enums, Twig-Extension), Integrationstests gegen MySQL (alle `RestaurantRepository::findPaginated`-Filter inkl. Nachtschicht- und `JSON_CONTAINS`-Logik, Upload-Services mit Temp-Dir-Isolation) und funktionale WebTestCase-Tests für sämtliche Web-, Admin- und `/api/v1`-Controller (inkl. Auth-Guards, CSRF-Pfade, Mailer-Versand). Test-Isolation über `dama/doctrine-test-bundle` (Transaktion-Rollback pro Test → wiederholbare Läufe). Neue Befehle `make test` / `make test-db-setup` und `composer test`. GitHub-Actions-Workflow (`.github/workflows/ci.yml`) führt PHPUnit (PHP 8.4 + MySQL-8.0-Service, JWT-Keypair) sowie TypeScript-Typecheck und ESLint bei jedem Push/PR aus. Basisklasse `tests/AbstractWebTestCase.php` mit Login-/Formular-/CSRF-Helfern.

### Fixed
- **Sprachen-Filter (`?lang_…`) warf 500er:** Der dokumentierte Sprachfilter auf `/restaurants` (z. B. `?lang_de=1`) nutzt die MySQL-Funktion `JSON_CONTAINS`, die nirgends als DQL-Funktion registriert war → `QueryException`. Neu: `App\Doctrine\JsonContainsFunction`, registriert in `config/packages/doctrine.yaml`.
- **Restaurant-Detailseite warf 500er ohne Nahverkehrs-API-Key:** `app.mobiliteit_api_key` löste über `%env(default::…)%` bei leerem Key zu `null` auf, was den `string`-Typehint von `PublicTransportService` brach (jede Detailseite mit Koordinaten betroffen). Fix: `%env(string:default::MOBILITEIT_API_KEY)%` castet zu `''` → dokumentierte Graceful-Degradation (leerer Key → keine Haltestellen) funktioniert wieder.
- **Admin-Vorschlag-Detailseite warf 500er:** `templates/admin/suggestion/show.html.twig` nutzt den `|u`-Twig-Filter, das Paket `twig/string-extra` war jedoch nicht installiert. Nachinstalliert.
- **Admin – Koordinaten-Präzision:** Breiten- und Längengrad im Restaurant-Formular werden nicht mehr auf 3 Nachkommastellen gerundet angezeigt (z. B. `5.94700000` → `5,947`), sondern mit voller Präzision von 8 Nachkommastellen – passend zu den DB-Spalten `DECIMAL(10,8)`/`DECIMAL(11,8)`. Ursache war der fehlende `scale`-Wert auf den `NumberType`-Feldern (`RestaurantType`); Default des `\NumberFormatter` sind 3 Nachkommastellen. Schützt auch beim Speichern vor Präzisionsverlust.

---

## [2026.06.19] – Mobile App, REST-API & PWA

### Added
- **PWA – Installierbare iPhone-App (Issue #83):** Endlech.lu lässt sich über Safaris „Zum Home-Bildschirm" als Progressive Web App installieren und startet dann im Vollbild ohne Browser-Chrome. Vollständiges Web App Manifest (`public/manifest.webmanifest`, `display: standalone`, `orientation: portrait`, Theme-Farbe Cyan), 11 App-Icon-Größen (57–512 px, inkl. maskable) reproduzierbar aus dem Logo erzeugt (`bin/generate-pwa-icons.sh`, macOS `sips`), alle iOS-spezifischen Meta-Tags (`apple-mobile-web-app-*`, `apple-touch-icon`) und `viewport-fit=cover` für Safe-Area-Insets (Notch/Home-Indicator). Service Worker (`public/sw.js`) mit Offline-Fallback-Seite (`public/offline.html`): Navigationen network-first, gebaute Assets stale-while-revalidate, `/api/` nie gecacht. Neue mobile Bottom-Navigation (`_bottom_nav.html.twig`, nur < 768 px, Tap-Targets ≥ 44 px, Home/Restaurants/Über uns/Profil) ersetzt die auf Mobil ausgeblendete Header-Navigation. Formularfelder erhalten auf kleinen Screens 16 px Schriftgröße gegen iOS-Auto-Zoom. Keine Backend-/DB-Änderung; alle PWA-Dateien locale-frei auf Root-Ebene.
- **REST-API für die iOS-App (Issue #87):** Versionierte, locale-freie REST/JSON-API unter `/api/v1/` als Backend für eine künftige native iOS-App. JWT-Authentifizierung via `lexik/jwt-authentication-bundle`. Endpunkte: `POST /auth/login` (Token), `POST /auth/register` (legt unverifizierten Nutzer an + E-Mail-Verifikation wie im Web), `GET /restaurants` (paginiert + alle Filter aus `findPaginated`: Barrierefreiheit, Ernährung, Küche, „offen jetzt", Stadt, Sprachen), `GET /restaurants/{id}` (volle Details inkl. Öffnungszeiten, Zahlung, Kontakt, Standort, Bestelloptionen), `GET /restaurants/{id}/images`, `GET /me` + `GET /me/submissions` (auth), `POST /restaurants` (auth, setzt `submittedBy`, unverifiziert). Explizite Transformer-Services (`App\Api\RestaurantTransformer`, `UserTransformer`) statt Serializer-Groups – `password`/Token werden strukturell nie ausgegeben. Einheitliche JSON-Fehler (`{error:{code,message}}`, 401 vs. 403 je nach Auth-Status) via `ApiExceptionSubscriber`. CORS (`nelmio/cors-bundle`) und IP-basiertes Rate-Limiting (`symfony/rate-limiter`, Login strenger) nur für `/api/v1`. Auto-generierte Swagger-UI unter `/api/docs` (`nelmio/api-doc-bundle`). 13 WebTestCase-Tests. Bestehende Web-App unverändert (eigener Routing-Import ohne `_locale`-Prefix).
- **Cookie-Consent-Banner (Issue #82):** DSGVO-konformes Banner, das beim ersten Besuch unten erscheint und über die Cookie-Nutzung informiert. „Akzeptieren"/„Ablehnen" speichern die Wahl 365 Tage im Cookie `cookie_consent`; danach erscheint das Banner nicht mehr. Footer-Link „Cookie-Einstellungen" öffnet es erneut. Banner verlinkt auf den Datenschutz-Abschnitt der Rechtliches-Seite (`#datenschutz`). Vollständig barrierefrei (Tastatur, ARIA-Rollen, Kontrast), responsiv und in 4 Sprachen (lb, de, fr, en). Stimulus-Controller `cookie_consent_controller.ts`, Partial `_cookie_banner.html.twig`. Nur auf öffentlichen Seiten (Admin ausgenommen).
- **Öffnungszeiten: Mehrere Zeitslots pro Tag (Issue #81):** Restaurants mit zwei Schichten (z. B. Mittag 12:00–14:30 und Abend 18:00–22:00) werden jetzt korrekt abgebildet. Pro Wochentag sind beliebig viele `OpeningHour`-Einträge möglich; ein Tag ohne Zeitfenster gilt als geschlossen. Admin-Formular gruppiert die Slots nach Tag mit „＋ Zeitfenster hinzufügen"- und Entfernen-Buttons (Stimulus). Detailseite zeigt alle Slots eines Tages als `12:00 – 14:30 · 18:00 – 22:00`. Der „Geöffnet"-Status und die nächste Öffnungszeit berücksichtigen alle Slots (inkl. Nachtschicht-Übertrag). `?open=1`-Filter angepasst. Erster PHPUnit-Test (`OpeningHoursServiceTest`). Migration entfernt UNIQUE-Constraint und `is_closed`-Spalte.

---

## [2026.03.22] – Küchen-Typen, Öffnungszeiten & Nahverkehr

### Added
- **Cuisine Multi-Select (Issue #77):** Küchen-Typ-Auswahl mit Autocomplete und Mehrfachauswahl. Neue `Cuisine` Entity mit ManyToMany-Relation statt einfachem String-Feld. Tom Select Autocomplete im Admin-Formular zum Suchen, Auswählen und Erstellen neuer Küchen-Typen. Neue API-Endpunkte (`/api/cuisines/search`, `/api/cuisines`). Checkbox-Filter in der Restaurant-Sidebar statt Freitext. Orange Cuisine-Badges auf Restaurant-Karten, Detail- und Startseite. 20 vordefinierte Küchen-Typen in Fixtures. Migration mit automatischer Datenmigration vom alten String-Feld.
- **Vorschlags-Wizard (Issue #76):** Multi-Step Wizard mit 5 Schritten für das Restaurant-Vorschlagsformular. 17 neue Felder auf der RestaurantSuggestion-Entity: Zahlung (acceptsCash, acceptsCard, acceptsPayconiq), Ernährung (isVegan, isVegetarian, isHalal), Sprachen (spokenLanguages), Kontakt (phone, email, website) und Social Media (instagramUrl, facebookUrl, tiktokUrl). Step-Indikator-Leiste mit automatischem Sprung zum ersten Fehler-Step. Stimulus-Controller für Step-Navigation. Alle neuen Felder werden bei Genehmigung auf das Restaurant übertragen.
- **Hero-Badges (Issue #74):** Rating & Sprach-Badges im Hero-Bereich der Restaurant-Detailseite. Farbcodiertes Rating-Badge (grün ≥7, amber ≥4, rot <4) und Sprach-Flag-Badges mit Glaseffekt. Neues Partial `_hero_badges.html.twig`, eingebunden in beide Hero-Varianten (Cover-Foto + Emoji-Fallback). Übersetzungen in 4 Sprachen (de, en, fr, lb).
- **Nahverkehr (Issue #65):** Barrierefreie Bus- & Tram-Haltestellen in der Nähe auf der Restaurant-Detailseite. Neue Felder `latitude`, `longitude`, `nearbyStopsNote` auf der Restaurant-Entity. PublicTransportService nutzt HAFAS API (cdt.hafas.de) mit Cache (24h) und Graceful Degradation. Template-Partial mit Haltestellen-Karten (Name, Linien-Badges, Distanz). Admin-Formular: Fieldset "Standort & Nahverkehr" mit Lat/Lng und Nahverkehrs-Hinweis. Übersetzungen in 4 Sprachen (de, en, fr, lb). Alle 11 Fixture-Restaurants mit echten Luxemburg-Koordinaten.
- **Öffnungszeiten (Issue #64):** Strukturierte Öffnungszeiten pro Wochentag mit automatischer Berechnung des Open/Closed-Status. OpeningHour Entity, OpeningHoursService, Admin-Formular mit 7-Tage-Tabelle, Wochenplan auf Detailseite mit hervorgehobenem heutigem Tag, dynamischer Badge auf Karten und Liste. Nachtschichten und Ruhetage werden korrekt behandelt. Manueller isOpen-Boolean entfernt.
- **Behindertenparkplatz (Issue #66):** Neues Barrierefreiheits-Kriterium `hasDisabledParking`. Filter-Checkbox in Sidebar, Badge auf Restaurant-Karten, Kachel auf Detailseite, Icon in Admin-Tabelle, Checkbox im Admin-Formular. Übersetzungen in 4 Sprachen (de, en, fr, lb). 5 Fixture-Restaurants mit Parkplatz.
- **Profil: Eingereichte Restaurants (Issue #63):** Neue Sektion "Meine Einreichungen" auf der Profilseite zeigt vom Nutzer eingereichte Restaurants mit Verifizierungsstatus. Neues `submittedBy`-Feld auf der Restaurant-Entity (ManyToOne User, SET NULL). Bei Genehmigung eines Community-Vorschlags wird der Einreicher automatisch gesetzt. Übersetzungen in 4 Sprachen (de, en, fr, lb).
- **Admin Dashboard Statistiken (Issue #62):** Erweitertes Dashboard mit 7 Stat-Karten (Restaurants, Verifizierte, Offene Vorschläge, Benutzer, Restaurants diesen Monat, Benutzer diesen Monat, Fotos). Tabellen für zuletzt hinzugefügte Restaurants und zuletzt registrierte Benutzer. Neuer `AdminStatsService` für zentralisierte Statistik-Abfragen. Dashboard-Route in eigenen `AdminDashboardController` ausgelagert. Übersetzungen in 4 Sprachen (de, en, fr, lb).
- **Neue Lieferplattformen (Issue #67):** Wolt, Wedely und Goosty als Bestelloptionen. SVG-Logos für Marken-Plattformen auf der Detailseite. Emoji-Fallback für generische Optionen (Telefon, Webseite, Andere).
- **App-Version im Footer:** Versionsnummer wird jetzt im Footer neben dem Copyright angezeigt. Neuer `app.version` Parameter als Twig-Global.

---

## [2026.03.17] – Profil, Cover-Fotos & About-Seite

### Added
- **About-Seite aktualisiert (Issue #56):** Neuer Meilenstein „März 2026 — Erste Live-Version" in der Timeline. Gründer-Foto vorbereitet (Fallback auf Initialen). Übersetzungen in 4 Sprachen aktualisiert.
- **Gründer-Foto:** `public/uploads/team/michael.jpg` wird jetzt im Repository getrackt (gitignore-Ausnahme für statische Team-Assets).
- **Benutzerprofil (Issue #54):** Profilseite für eingeloggte Nutzer zum Anzeigen/Bearbeiten von Name, E-Mail und Profilbild. Passwort-Änderung mit Prüfung des aktuellen Passworts. Avatar-Upload (JPG/PNG/WebP, max. 2 MB) mit Initialen-Fallback. Avatar + Profil-Link in der Navigation. i18n in allen 4 Sprachen (lb, de, fr, en).
- **Titelbild / Cover-Foto (Issue #44):** Das erste Bild eines Restaurants dient automatisch als Cover-Foto. Drag & Drop Sortierung im Admin-Panel (SortableJS). Cover-Foto als Hero-Bild auf Detailseite und Thumbnail in Listenansicht & Homepage.
- **Wickeltisch-Filter (Issue #41):** Neues Barrierefreiheits-Kriterium `hasChangingTable`. Kachel auf Detailseite, Filter-Checkbox in Sidebar, Badge auf Restaurant-Karten.
- **Kontaktdaten & Social Media (Issue #42):** Telefon, E-Mail, Webseite mit direkten Aktions-Links. Instagram, Facebook, TikTok mit Marken-SVG-Icons. Neue Sektion auf Detailseite, neues Fieldset im Admin-Formular.
- **Bestelloptionen (Issue #43):** Plattformen (Uber Eats, Deliveroo, Just Eat, Telefon, Webseite, Andere) pro Restaurant. CTA-Buttons auf Detailseite, dynamische Collection im Admin-Formular.
- **Ernährungsoptionen (Issue #45):** Vegan, Vegetarisch, Halal pro Restaurant. Badges auf Karten, Filter in Sidebar, Sektion auf Detailseite.
- **Gesprochene Sprachen (Issue #40):** Luxemburgisch, Deutsch, Französisch, Englisch, Portugiesisch, Andere. Flaggen-Badges, Sprachfilter (AND-Verknüpfung), Admin-Checkboxen.

### Changed
- **TypeScript-Migration:** Alle JS-Assets auf TypeScript migriert. Webpack Encore `enableTypeScriptLoader()`, ESLint Flat Config, npm-Scripts `typecheck`/`lint`/`lint:fix`, `make lint` Target.
- **Cover-Foto Sortierung:** `Restaurant::$images` OrderBy auf `sortOrder ASC` geändert. `ImageUploadService::reorderAfterDelete()` für konsekutive Sortierung.

### Fixed
- **OrderingOptionType:** Choice-Closures akzeptieren jetzt String-Werte korrekt (Issue #44).

---

## [2026.03.08e] – Restaurant-Fotos

### 🚀 Features
- **Bildergalerie:** Fotos pro Restaurant auf der Detailseite (GLightbox-Lightbox).
- **Thumbnail:** Erstes Foto als Vorschau-Bild auf der Restaurantliste.
- **Admin-Upload:** Mehrere Fotos gleichzeitig hochladen (jpg, png, webp, max. 5 MB).
- **Admin-Löschung:** Einzelne Fotos per Hover-Button entfernen.
- **Alt-Texte:** Barrierefreie Bildbeschreibungen für alle Fotos.

### 🛠 Tech
- Entity `RestaurantImage` (ManyToOne zu Restaurant, CASCADE DELETE).
- `ImageUploadService` – Upload & Löschung (Symfony-nativ, kein VichUploaderBundle).
- GLightbox via npm für Lightbox-Galerie.
- Migration `Version20260308110000`.

---

## [2026.03.08d] – Filterfunktion für Lokale

### 🚀 Features
- **Barrierefreiheits-Filter:** Checkboxen für ♿ Rollstuhlgerecht, 🚻 Barrierefreies WC, 🐕 Assistenzhund, 💡 Helle Beleuchtung.
- **Status-Filter:** „Nur geöffnete Lokale" Checkbox.
- **Ort-Filter:** Freitext-Suche nach Stadt (LIKE).
- **Küchen-Filter:** Freitext-Suche nach Küchentyp (LIKE).
- **Aktive Filter:** Chip-Zeile über Ergebnissen + „Alle zurücksetzen"-Link in der Sidebar.
- **Filter-Persistenz:** Sort- und Pagination-Links behalten alle aktiven Filter bei.

### 🛠 Tech
- **Repository:** `findPaginated()` auf `array $filters` umgestellt (skalierbar, 8 Filter-Keys).
- **Controller:** 8 Query-Parameter ausgelesen und als `$filters`-Array weitergereicht.

---

## [2026.03.08c] – Verifiziertes Lokal
*Blaues Verifikations-Badge für vom Endlech.lu-Team geprüfte Restaurants.*

### 🚀 Features
- **Verifikations-Badge:** Blauer Haken (Cyan-600) für verifizierte Restaurants auf Karte und Detailseite.
- **Tooltip:** „Von Endlech.lu persönlich vor Ort geprüft" via Browser-Tooltip.
- **Filter:** Listenansicht filtert nach „Nur verifizierte Lokale" (?verified=1).
- **Admin:** Verifikations-Checkbox im Bearbeitungsformular mit Auto-Stamping von Datum + Admin-User.
- **Admin:** Quick-Toggle-Button in der Restaurants-Übersicht (verifiziert/unverifiziert).
- **Admin:** Stat-Card „Verifizierte Lokale" im Dashboard.

### 🛠 Tech & Config
- **Entity:** `isVerified`, `verifiedAt`, `verifiedBy` zur `Restaurant`-Entity hinzugefügt.
- **Migration:** `Version20260308100000` – fügt `is_verified`, `verified_at`, `verified_by_id` zur `restaurant`-Tabelle hinzu.
- **Route:** `admin_restaurant_toggle_verified` POST `/admin/restaurants/{id}/verifizieren`.
- **Partial:** `templates/partials/_verified_badge.html.twig` – wiederverwendbares Badge-Template.
- **Fixtures:** 3 Restaurants als verifiziert markiert (Pizzeria Bella Vista, Sushi Zen, Green Bowl).

---

## [2026.03.08b] – Zahlungsmethoden
*Zahlungsmethoden pro Restaurant (Bargeld, Karte, Payconiq).*

### 🚀 Features
- **Zahlungsmethoden:** Drei neue Boolean-Felder in der `Restaurant`-Entity (`acceptsCash`, `acceptsCard`, `acceptsPayconiq`).
- **Detailseite:** Neue Sektion „Zahlungsmethoden" auf `/restaurants/{id}` mit farbigen Badges pro Methode (Grün = akzeptiert, Payconiq in Markenfarbe `#FF4612`).
- **Admin-Formular:** Neue Fieldset „Zahlungsmethoden" mit drei Checkboxen im Restaurant-Bearbeitungsformular.
- **Fixtures:** Alle 11 Fixture-Restaurants mit realistischen Zahlungsmethoden-Daten versehen.

### 🛠 Tech & Config
- **Migration:** `Version20260308000000` – fügt `accepts_cash`, `accepts_card`, `accepts_payconiq` (TINYINT) zur `restaurant`-Tabelle hinzu.

---

## [2026.03.08]
*Brevo Mailer Integration für Transaktions-E-Mails.*

### 🚀 Features
- **Brevo Integration:** `symfony/brevo-mailer` als Produktions-Mail-Provider installiert und konfiguriert.
- **E-Mail-Konfiguration:** Zentraler Absender (`noreply@endlech.lu`) über `mailer.yaml` und Umgebungsvariablen konfigurierbar.
- **Base E-Mail-Template:** Wiederverwendbares Basis-Layout (`email/base.html.twig`) mit Endlech.lu Branding (Gradient-Header, Footer).
- **Fehlerbehandlung:** Try/Catch für `TransportExceptionInterface` in allen E-Mail-sendenden Controllern mit benutzerfreundlichen Flash-Nachrichten.

### 🛠 Tech & Config
- **Dependency:** `symfony/brevo-mailer` v8.0 hinzugefügt.
- **Mailer Config:** Globaler Absender via `envelope.sender` und `headers.From` in `config/packages/mailer.yaml`.
- **Umgebungsvariablen:** `MAILER_SENDER_ADDRESS` und `MAILER_SENDER_NAME` in `.env` für konfigurierbare Absenderadresse.
- **Dev-Umgebung:** `.env.dev` nutzt Mailpit (`smtp://localhost:1025`) für lokales E-Mail-Testing.
- **Templates:** Verification-E-Mail refactored, nutzt jetzt `email/base.html.twig` als Basis-Layout.
- **Controller:** `RegistrationController` und `EmailVerificationController` nutzen globale Absender-Konfiguration statt hardcoded Adressen.

---

## [2026.03.01]
*Admin-Panel für die Verwaltung von Restaurants (CRUD).*

### 🚀 Features
- **Admin-Panel:** Neuer Admin-Bereich unter `/admin` für ROLE_ADMIN Benutzer.
- **Dashboard:** Admin-Dashboard mit Restaurant-Statistiken und Schnellaktionen.
- **Restaurant CRUD:** Restaurants erstellen, bearbeiten und löschen über `/admin/restaurants`.
- **Formular:** `RestaurantType`-Formular mit allen Restaurant-Feldern (Name, Stadt, Küche, Emoji, Bewertung, Status, Barrierefreiheits-Checkboxen, dynamische Hinweise).
- **Barrierefreiheits-Hinweise:** Dynamisches Hinzufügen/Entfernen von Hinweisen im Format `ok:Text` / `warn:Text` via Stimulus-Controller.
- **Navigation:** "Admin"-Link in der Hauptnavigation für Admin-Benutzer.
- **Sicherheit:** `/admin`-Bereich via `access_control` und `#[IsGranted('ROLE_ADMIN')]` geschützt.
- **CSRF-Schutz:** Löschen von Restaurants mit CSRF-Token-Validierung und Bestätigungsdialog.

### 🛠 Tech & Config
- **Controller:** `AdminRestaurantController` mit 5 Aktionen (Dashboard, Index, New, Edit, Delete).
- **Form:** `RestaurantType` mit CollectionType für dynamische accessibilityNotes.
- **Stimulus:** `collection_form_controller.js` für dynamische Formularfelder.
- **Templates:** Admin-Layout mit Sidebar-Navigation (`admin/base.html.twig`), 5 Admin-Templates.
- **Security:** `access_control`-Regel für `/admin`-Pfad in `security.yaml`.

---

## [2026.02.28]
*Startseite als Landing Page neu gestaltet. Detailseite für einzelne Restaurants.*

### 🚀 Features
- **Startseite:** Komplette Neugestaltung als Landing Page mit Hero-Section, „So funktioniert's" (3 Schritte), Top-6 Restaurant-Vorschau, „Warum Endlech.lu?" Wertversprechen und CTA-Banner.
- **Backend:** `RestaurantRepository::findTopRated(int $limit)` für die Top-bewerteten Restaurants.
- **Backend:** `HomeController` zeigt jetzt Top-6 Restaurants statt alle und übergibt Gesamtanzahl ans Template.
- **UI:** Restaurant-Karten auf der Startseite mit Barrierefreiheits-Icons (♿ 🚻 🐕 💡).
- **UI:** Responsive 3-Spalten-Grid (1 Spalte mobil, 2 Tablet, 3 Desktop).
- **CTA:** „Restaurants entdecken" → `/restaurants`, „Mitmachen" / „Restaurant vorschlagen" → `/register`.

### Vorige Änderungen (2026.02.28)
*Detailseite für einzelne Restaurants unter `/restaurants/{id}`.*

### 🚀 Features
- **Backend:** `RestaurantController::show()` mit Route `/restaurants/{id}` (Name: `app_restaurant_show`).
- **Backend:** Automatische 404-Antwort bei nicht existierender Restaurant-ID (Symfony Entity Value Resolver).
- **UI:** Template `restaurant/show.html.twig` mit Emoji-Hero, Status-Badge, Bewertung, Barrierefreiheits-Übersicht (4 Kriterien) und Hinweisen (ok/warn).
- **UI:** Responsive Layout (single-column, max-w-3xl) mit bestehendem Design (Cyan/Purple Gradient).
- **Linking:** "Details ansehen" Links in `restaurant/index.html.twig` und `home/index.html.twig` verlinken jetzt auf die Detailseite.

---

## [2026.02.27]
*Restaurant-Listenansicht unter `/restaurants` mit Pagination und Sortierung.*

### 🚀 Features
- **Backend:** `RestaurantController` mit Route `/restaurants` (Name: `app_restaurant_index`).
- **Backend:** Paginierung via Doctrine `Paginator` (6 Ergebnisse pro Seite).
- **Backend:** Sortierung nach Bewertung (Standard), Name (A–Z) und Neueste via URL-Parameter `?sort=`.
- **UI:** Dediziertes Template `restaurant/index.html.twig` mit Restaurant-Karten, Barrierefreiheits-Icons, Pagination-Navigation und Leer-Zustand.
- **Data:** 3 neue Fixture-Restaurants (Trattoria Roma/Ettelbruck, Green Bowl/Cloche d'Or, Brasserie du Grund/Grund) – jetzt 11 Einträge insgesamt.
- **Nav:** "Restaurants finden" in der Navigation verlinkt jetzt auf `/restaurants`.

### 🛠 Tech & Config
- **Repository:** `RestaurantRepository::findPaginated(string $sort, int $page, int $limit)` hinzugefügt.
- **Data:** `UserFixtures` mit 3 Test-Usern (Admin, verifiziert, unverifiziert) und korrekt gehashten Passwörtern (Symfony PasswordHasher).

---

## [2026.02.25]
*Platform-Launch: Overlay entfernt, echte Datenbank-Anbindung für Restaurant-Karten.*

### 🚀 Features
- **Launch:** "Coming Soon" Overlay entfernt – die Plattform ist jetzt live.
- **Backend:** `Restaurant`-Entity mit Barrierefreiheits-Feldern (Rollstuhl, WC, Assistenzhund, Beleuchtung).
- **Backend:** Doctrine-Migration für die `restaurant`-Tabelle (MySQL 8.0).
- **Data:** 8 Luxemburger Restaurants als initiale Fixtures (Luxembourg-Ville, Esch-Belval, Dudelange, Kirchberg, Grevenmacher, Diekirch, Strassen, Remich).
- **UI:** Dynamische Restaurant-Karten via DB-Abfrage statt hardcoded HTML.
- **UI:** Empty-State bei leerer Restaurantliste.

### 🛠 Tech & Config
- **Dependency:** `doctrine/doctrine-fixtures-bundle` als Dev-Abhängigkeit hinzugefügt.
- **Controller:** `HomeController` injiziert `RestaurantRepository` und übergibt `$restaurants` ans Template.

---

## [2026.01.13]
*Initialer Projektstart und UI-Implementation.*

### 🚀 Features
- **UI:** "Coming Soon" Overlay mit Glassmorphism-Effekt und Animationen implementiert.
- **Layout:** Responsives Grid-Layout mit Sidebar-Filtern und Restaurant-Karten erstellt.
- **Assets:** Logo `images/logo.png` eingebunden.
- **Design:** Modernes Farbschema (Cyan/Purple) definiert.

### 🛠 Tech & Config
- **Core:** Symfony 7 Projektstruktur aufgesetzt.
- **Frontend:** Webpack Encore mit PostCSS und Tailwind CSS konfiguriert.
- **Fix:** Tailwind-Build Prozess repariert (`postcss.config.js` erstellt).
- **Templates:** Base-Layout (`base.html.twig`) mit Navigation und Footer erstellt.

### 📝 Dokumentation
- `README.md` im Mika+ Hub Style erstellt.
- `CHANGELOG.md` mit CalVer-Versionierung initiiert.

---
