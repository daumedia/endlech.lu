# CLAUDE.md

Guide for AI assistants working on the endlech.lu codebase.

## Project Overview

**Endlech.lu** is an open platform to find and rate accessible restaurants in Luxembourg. Built with Symfony 8 (PHP 8.4+), Tailwind CSS v4, and Hotwire (Stimulus + Turbo). The platform is live with a restaurant listing page backed by a real database.

The UI language is German/Luxembourgish. The codebase comments (Makefile, templates) use German.

## Ausführliche Dokumentation unter `docs/`

Diese Datei sammelt die **Implementierungs-Fallstricke** — sie ist chronologisch
nach Issues gewachsen. Wer eine geordnete Referenz braucht, findet sie in `docs/`:

| Datei | Inhalt |
|---|---|
| `docs/data-model.md` | vollständige Feldreferenz aller Entities, Enums und Repositories, ERD, Migrations-Historie |
| `docs/design-system.md` | Farben, Typografie, kanonische Komponenten-Klassenketten, Barrierefreiheits-Regeln, Diagramm- und Druckregeln |
| `docs/prd.md` | Vision, Zielgruppen, Produktprinzipien, Funktionsumfang, Kennzahlen, Geschäftsmodell, Roadmap, Risiken |
| `docs/app-shell.md` | Layout-Hierarchie, Kopf-/Fußzeile, Navigation, Bottom-Nav, Admin-Shell, Druckansicht, bekannte Lücken |

**Bei Änderungen am Datenmodell oder an den Komponenten-Mustern die passende
Datei mitziehen** — sonst laufen Code und Referenz auseinander. Die `⚠️`-Blöcke
weiter unten bleiben die Quelle für alles, was beim Ändern schiefgehen kann.

### SDD-Artefakte

Dieses Projekt ist über `/sdd-erfassen` in die SDD-Kette aufgenommen worden.

| | |
|---|---|
| **Artefaktpfad** | `docs/` — steht auch in Zeile 3 von `docs/prd.md` |
| **Feature-Inventar** | `features/index.md` — 26 Bestandsfeatures mit IDs `B01`–`B26`, alle Status `rekonstruiert` |
| **Je Feature** | `features/BNN-slug/spec.md` (Akzeptanzkriterien, Fehlbestand) und `design.md` (Struktur, Zugriffsregeln, AK-Abdeckung) |
| **Projektweite Muster** | `features/fehlbestand-uebersicht.md` — zehn Muster, die mehrere Features gleichzeitig betreffen |
| **Stack-Profil** | `symfony-doctrine` |

⚠️ **Das Datenmodell heißt hier `data-model.md`, nicht `datenmodell.md`** wie in
`~/.claude/sdd/artefakte.md` vorgesehen. Der Name ist gewachsen und in `docs/README.md`,
im PRD und in dieser Datei verlinkt; umbenennen bräche alle drei Stellen.

Alle Bestandsfeatures tragen das Präfix `B`. Daran ist ohne Nachschlagen erkennbar,
dass ihre `spec.md` eine **Rekonstruktion** ist und selbst falsch sein kann — anders
als bei einem Feature, das gegen eine Spezifikation gebaut wurde. Wer das verwechselt,
sucht einen Fehler an der falschen Stelle.

Der Weg für ein Bestandsfeature ist `bestand` → `/sdd-erfassen BNN` → `rekonstruiert`
→ `/sdd-qa BNN`. **Stand 2026-08-23 sind alle 26 auf `rekonstruiert`** — die QA steht
noch aus, `features/befunde.md` und der Auditbericht existieren deshalb noch nicht.

⚠ **Die `spec.md` eines `B`-Features ist eine Rekonstruktion und kann selbst falsch
sein.** Sie beschreibt, was der Code tut, nicht was er tun sollte; Kriterien mit ⚠
markieren fragwürdiges Verhalten, das bewusst als Kriterium aufgenommen wurde. Wer
sie wie eine Vorgabe liest, sucht Fehler an der falschen Stelle. Es läuft **nie** durch `sdd-tasks` und nie durch den regulären
Eingang von `sdd-build` — es ist gebaut.

## Konvention: `ActionLimiter` statt `consume(1)` von Hand

⚠ **`consume(0)` ist keine Prüfung.** Der naheliegende Umbau — abfragen mit
`consume(0)`, verbrauchen mit `consume(1)` — sieht richtig aus und ist es nicht:
`SlidingWindowLimiter` vergleicht `verfügbar >= angefordert`, und `0 >= 0` gilt auch
bei erschöpftem Kontingent. Nachgestellt: **acht gültige Anmeldungen liefen durch**, wo
die sechste hätte scheitern müssen — der Deckel war weg, nicht repariert. Maßgeblich ist
`getRemainingTokens()`.

Deshalb gibt es `App\RateLimit\ActionLimiter`:

```php
$limiter = ActionLimiter::for($this->registrationLimiter, $request->getClientIp());
if (!$limiter->isAllowed()) { /* 429 */ }
// … Formular prüfen, Honeypot, alles was fehlschlagen darf …
$limiter->consume();   // erst hier: die Handlung findet statt
```

⚠ **Erst verbrauchen, wenn die Handlung stattfindet** (BF-11). Fünf Tippfehler sperrten
vorher eine Stunde lang aus, ohne dass ein Konto oder eine Mail entstanden wäre. Der
Deckel soll den Angreifer treffen, nicht den, der sich vertippt.

⚠ **Eine Ausnahme, und sie steht im Code:** Der Passwortwechsel verbraucht **vor** der
Prüfung. Dort ist der Fehlversuch kein Tippfehler, sondern der Angriff — genau ihn soll
der Deckel zählen. Nicht „vereinheitlichen".

`LimiterCoverageTest` prüft, dass jeder konfigurierte Limiter irgendwo verdrahtet ist
und einen `when@test`-Override hat. Ein Limiter, den niemand ruft, ist eine Zeile
Konfiguration und kein Schutz.

## Konvention: Jeder Weg, der eine Mail auslöst oder ein Geheimnis prüft, braucht einen Limiter

— und ebenso jeder Weg, der **bei jedem Aufruf den gesamten Bestand lädt**.

Unabhängig davon, ob eine App oder ein Browser ihn geht. Wer einen solchen Weg **neu
anlegt oder einen bestehenden darum erweitert, legt den Limiter im selben Commit an.**

Der Satz steht hier und nicht nur in `features/fehlbestand-uebersicht.md`, weil er dort
beim *Prüfen* gelesen wurde und nicht beim *Bauen* — mit dem Ergebnis, dass BF-30 am
selben Tag entstand, an dem er formuliert wurde.

Fünfmal gefunden, jedes Mal an einer anderen Stelle: Registrierung (BF-02), Anmeldung
(BF-13), Passkey-Challenge (BF-18), Adressänderung (BF-21), API-Einreichung (BF-30).
Gemeinsam ist allen, dass der **API**-Weg gedeckelt war und der Browser-Weg nicht — oder
dass eine Reparatur einem Weg erstmals einen Mailversand gab, ohne den Deckel mitzunehmen.

Vorhandene Limiter stehen in `config/packages/framework.yaml`; **der `when@test`-Override
auf 10000 ist Pflicht**, sonst summieren sich die Aufrufe über die Testsuite.

⚠️ **Am Konto zählen, nicht an der IP**, wenn der Angriff eine bestehende Sitzung oder
ein bestehendes Konto voraussetzt — dort wechselt die IP mühelos, das Konto nicht
(siehe `password_change`).

## Konvention: Die Prüfung gehört dorthin, wo der Wert hereinkommt

Viermal derselbe Fehler, jedes Mal an einer anderen Stelle (BF-27, BF-51, BF-62 zweimal):
Ein Wert, den niemand geprüft hat, fällt in die nächste Schicht und kommt dort als
**HTTP 500** heraus statt als Meldung.

- ⚠ **`'empty_data' => ''` ist Pflicht**, sobald der Setter der Entity ein striktes
  `string` verlangt. Ohne die Zeile übergibt Symfony `null`, `setName(string)` wirft, und
  der Nutzer bekommt einen Serverfehler statt der `NotBlank`-Meldung, die direkt daneben
  konfiguriert ist.
- ⚠ **Eine Längenprüfung am Endpunkt reicht nicht, wenn daraus ein Slug wird.**
  `AsciiSlugger` macht aus „ß" ein „ss", aus einem japanischen Zeichen bis zu drei
  Buchstaben: 80 × „ß" ergeben 160 Zeichen, 80 × „日" ergeben 239. Der `SQLSTATE[22001]`
  wandert dann von `name` auf `slug`.

## Konvention: Übersetzungsschlüssel werden getestet, nicht gehofft

`tests/Unit/Translation/CatalogueCompletenessTest.php` prüft dreierlei:

1. Alle vier Kataloge tragen **dieselbe Schlüsselmenge** (`messages` 1084+, `validators` 82+).
2. Kein Wert ist leer.
3. **Jeder im Code verwendete Schlüssel ist definiert** — 736 aus `|trans` in Templates,
   187 aus `src/Form/` (Constraint-Meldungen, `label`, `help`, `placeholder`).

⚠ **Punkt 3 fand seinen eigenen blinden Fleck erst im zweiten Anlauf.** Die erste Fassung
prüfte nur Constraint-Meldungen; zwei neue Formularfelder trugen Beschriftungen, die in
keinem Katalog standen, und der Test blieb grün. Wer den Scanner erweitert, prüft mit
einem absichtlich falschen Schlüssel gegen, ob er rot wird.

`debug:translation <locale> --only-missing` ist der manuelle Weg dazu.

## Tech Stack

- **Backend:** PHP 8.4+, Symfony 8.0.*
- **Database:** MySQL 8.0 (via Docker)
- **ORM:** Doctrine 3.6 with migrations
- **Templates:** Twig
- **CSS:** Tailwind CSS v4.1 via PostCSS
- **JS/TS:** TypeScript, Hotwire (Stimulus 3.x + Turbo 7/8)
- **Build:** Webpack Encore 5.1
- **Scheduling:** `symfony/scheduler` + `dragonmantank/cron-expression` (braucht einen Messenger-Worker – siehe Open-Startup-Abschnitt)
- **Testing:** PHPUnit 12.5
- **Email:** Brevo (formerly Sendinblue) via `symfony/brevo-mailer` (production)
- **Dev Mail:** Mailpit (SMTP on port 1025, UI on port 8025)

## Project Structure

```
src/
├── Command/             # Console commands (app:metrics:snapshot)
├── Controller/          # Route controllers (attribute-based routing)
│   └── Open/            # Locale-freie Daten-Endpunkte (/open.json, Datensatz-Downloads)
├── DataFixtures/        # Doctrine fixtures (restaurant data + user test data)
├── DTO/                 # Data Transfer Objects (NearbyStop)
├── Entity/              # Doctrine entities (User, Restaurant, RestaurantImage, OrderingOption, Cuisine, FinanceEntry, MetricSnapshot, WebauthnCredential)
├── Enum/                # PHP Backed Enums (Language, OrderingPlatform, FinanceType, FinanceCategory, Canton)
├── Message/             # Messenger-Nachrichten (CaptureMetricSnapshot)
├── MessageHandler/      # Messenger-Handler
├── Open/                # Open-Startup-Logik (Stats, Kantonszuordnung, Punktzahl, Snapshots)
├── Repository/          # Doctrine repositories (UserRepository, RestaurantRepository, CuisineRepository, WebauthnCredentialRepository)
├── Security/            # PasskeyAuthenticator, WebauthnUserEntityRepository
├── Schedule.php         # Wiederkehrende Aufgaben (#[AsSchedule])
└── Kernel.php           # Symfony kernel

config/
├── packages/            # Bundle-specific configuration (23 files)
├── routes/              # Route configurations
├── bundles.php          # Bundle registration
├── services.yaml        # Service container (autowire + autoconfigure)
└── routes.yaml          # Auto-imports controllers with #[Route] attributes

templates/
├── base.html.twig       # Base layout (header, nav, footer)
├── admin/
│   ├── base.html.twig   # Admin layout (sidebar nav, extends base)
│   ├── dashboard.html.twig # Admin dashboard with stats
│   └── restaurant/
│       ├── index.html.twig  # Restaurant table listing (CRUD overview)
│       ├── new.html.twig    # Create restaurant form
│       ├── edit.html.twig   # Edit restaurant form
│       └── _form.html.twig  # Shared form partial (new + edit)
├── home/
│   └── index.html.twig  # Landing page (Hero, "So funktioniert's", Top-6 Restaurants, "Warum Endlech.lu?", CTA)
├── email/
│   ├── base.html.twig       # Base email layout (header, footer, branding)
│   └── verification.html.twig # Email verification template (extends base)
├── open/
│   ├── index.html.twig      # /open – Transparenzseite (Hero, Plattform, Wirkung, Finanzen, Verlauf, Daten)
│   ├── _metric.html.twig    # Kennzahl-Kachel (Wert, Veränderung, Einordnung)
│   ├── _bar.html.twig       # Anteilsbalken (aria-hidden, Zahl trägt die Aussage)
│   ├── _histogram.html.twig # Punkteverteilung als Säulen
│   └── _sparkline.html.twig # Verlaufslinie als reines SVG
├── profile/
│   └── index.html.twig  # /profile – user profile page (edit info, avatar, password)
└── restaurant/
    ├── index.html.twig  # /restaurants – paginated & sortable restaurant list
    └── show.html.twig   # /restaurants/{id} – restaurant detail view (incl. contact & social media)

assets/
├── app.ts               # Main TS entry point
├── controllers/         # Stimulus controllers (.ts)
├── controllers.json     # Stimulus controller registry
├── stimulus_bootstrap.ts
└── styles/
    └── app.css          # Tailwind import (@import "tailwindcss")

migrations/              # Doctrine migrations (DoctrineMigrations namespace)
tests/                   # PHPUnit tests, nach Art gegliedert: Unit/, Integration/, Functional/
translations/            # i18n files (de, en, fr, lb)
public/                  # Web root (index.php front controller)
public/images/platforms/    # SVG logos for delivery platforms (Uber Eats, Deliveroo, etc.)
public/uploads/restaurants/ # Uploaded restaurant images (gitignored except .gitkeep)
public/uploads/avatars/    # Uploaded user avatars (gitignored except .gitkeep)
```

## Common Commands

All development commands are available via `make`:

```bash
make init              # Full setup: Docker, composer, npm, DB, fixtures
make start             # Start Docker + Symfony server + asset watcher
make stop              # Stop Symfony server and Docker
make restart           # Restart everything

make db                # Run Doctrine migrations
make migration         # Generate new migration from entity changes
make fixtures          # Reload test fixtures (destructive)
make db-reset          # Drop DB, recreate, migrate, load fixtures

make cc                # Clear Symfony cache
make assets            # Production asset build (npm run build)
make fix               # Run PHP-CS-Fixer
make lint              # TypeScript type-check + ESLint
```

### NPM Scripts

```bash
npm run dev            # Development build
npm run watch          # Watch mode (continuous rebuild)
npm run build          # Production build (minified, hashed)
npm run dev-server     # Webpack dev server with HMR
npm run typecheck      # TypeScript type-check (tsc --noEmit)
npm run lint           # ESLint check
npm run lint:fix       # ESLint auto-fix
```

### Direct Symfony Console

```bash
php bin/console <command>           # Run any Symfony command
php bin/console debug:router        # List all routes
php bin/console make:entity         # Generate a new Doctrine entity
php bin/console make:controller     # Generate a new controller
php bin/console make:migration      # Generate migration from entity diff
```

## Testing

```bash
make test                           # Test-DB vorbereiten (create/migrate/fixtures) + PHPUnit
make test-db-setup                  # Nur Test-DB aufsetzen (einmalig nötig)
php bin/phpunit                     # Alle Tests (Test-DB muss bereits aufgesetzt sein)
php bin/phpunit --testsuite Unit    # Nur Unit-Tests (keine DB, schnell)
php bin/phpunit --testsuite Integration  # Nur Integration-Tests (KernelTestCase + DB)
php bin/phpunit --testsuite Functional   # Nur Functional-Tests (WebTestCase, HTTP)
php bin/phpunit tests/Unit/Service  # Einzelnes Verzeichnis
php bin/phpunit --display-all-issues  # Volle Notice-/Deprecation-Texte
composer test                       # Äquivalent zu `make test` (CI-tauglich)
```

PHPUnit is configured in `phpunit.dist.xml` with strict mode: fails on deprecation, notices, and warnings. Bootstrap loads `.env.test` variables. Läuft real auf PHP 8.5; Zielversion ist 8.4+.

**Test-Isolation:** `dama/doctrine-test-bundle` (in `config/bundles.php` nur `test`, Extension in `phpunit.dist.xml`) wickelt jeden Test in eine Transaktion mit Rollback. Fixtures werden **einmal vor** der Suite geladen (`make test-db-setup`), nicht in `setUp()`. Tests dürfen frei persistieren/flushen, ohne den Fixture-Stand zu verändern → wiederholbare, reihenfolgeunabhängige Läufe.

**Mailer im Test:** `config/packages/messenger.yaml` (`when@test`) routet `SendEmailMessage` auf `sync` (+ `MAILER_DSN=null://null` in `.env.test`), damit `MailerAssertionsTrait` (`assertEmailCount`, `getMailerMessage`) greift.

**Drei Test-Kategorien** — je ein eigener Ordner + gleichnamige `phpunit.dist.xml`-Testsuite, PSR-4-Namespace `App\Tests\{Unit,Integration,Functional}\…` gespiegelt zum Pfad. Unter jeder Kategorie bleibt die Schicht-Unterstruktur erhalten (z. B. `tests/Unit/Api/`, `tests/Integration/Repository/`, `tests/Functional/Controller/Api/V1/`):
- **`tests/Unit/`** (`extends TestCase`, keine DB): Services mit mockbaren Deps (`PublicTransportService` via `MockHttpClient`, `AdminStatsService`), Transformer (`RestaurantTransformer`/`UserTransformer` – echter `AssetUrlBuilder` mit Base-URL, da `final`), Enums, Twig-Extension, `OpeningHoursService`, `ApiExceptionSubscriber`, `CantonResolver` (Gemeinde-/Kantonszuordnung inkl. Abgleich der Gemeindezahlen gegen `Canton::communeCount()`), `AccessibilityScore`, `FinanceCategory`.
- **`tests/Integration/`** (`extends KernelTestCase`, Container + DB, DAMA-isoliert): Repositories (v. a. `RestaurantRepositoryTest` – alle `findPaginated`-Filter und `sort`-Reihenfolgen), `ImageUploadService`/`AvatarUploadService` (Temp-Dir-Isolation via `sys_get_temp_dir`), `OpenStatsService` (Abdeckung, Punkteverteilung, Quartalssperre, Cache-Invalidierung), `MetricSnapshotService` (Idempotenz, `--force`, Payload), `FinanceEntryRepository`, `CaptureMetricSnapshotCommand` (`tests/Integration/Command/`).
- **`tests/Functional/`** (`extends AbstractWebTestCase`/`WebTestCase`, HTTP/Forms/Auth): Web- und Admin-Controller + `/api/v1`. Dazu `OpenControllerTest`, `Open\OpenDataControllerTest` (locale-freie Endpunkte, CC-BY-Header, keine Kontaktdaten im Datensatz, öffentlicher Cache) und `AdminFinanceControllerTest`. Basisklasse `tests/AbstractWebTestCase.php` (bleibt im `tests/`-Root, Namespace `App\Tests`) mit `loginAs()`, `formWithField()`, `formByAction()`, `csrfTokenFrom()`. Web-Routen tragen den Locale-Prefix → `self::LOCALE` (`/de`) vor jeden Pfad.

**PHPUnit-12-Konventionen (strict):** `#[DataProvider]`-Attribut statt Docblock-`@dataProvider`; `createStub()` für reine Rückgabe-Doubles, `createMock()` nur mit `expects()` (sonst Notice). Ungültige Formular-Submits liefern HTTP **422**, gültige einen 302-Redirect. Formular-CSRF ist stateless (`token_id: submit`) und passt im headless-Test via Same-Origin-Referer; Custom-Token-IDs (z. B. `toggle-verified-…`) sind session-basiert und werden als gerenderte Hidden-Felder mitgesendet.

## Architecture & Conventions

### Routing
Routes are defined using PHP attributes (`#[Route]`) on controller methods. Auto-discovery is enabled in `config/routes.yaml` - no manual route registration needed.

### Services
Autowiring and autoconfiguration are enabled by default in `config/services.yaml`. All classes under `src/` are automatically registered as services.

### Routes

| Route name              | URL            | Controller method                   |
|-------------------------|----------------|-------------------------------------|
| `app_home`              | `/`            | `HomeController::index()` (Landing Page) |
| `app_restaurant_index`  | `/restaurants` | `RestaurantController::index()`     |
| `app_restaurant_show`   | `/restaurants/{id}` | `RestaurantController::show()`      |
| `app_login`             | `/login`       | `SecurityController::login()`       |
| `app_register`          | `/register`    | `RegistrationController::register()`|
| `app_logout`            | `/logout`      | `SecurityController::logout()`      |
| `admin_dashboard`       | `/admin`       | `AdminDashboardController::dashboard()` |
| `admin_restaurant_index`| `/admin/restaurants` | `AdminRestaurantController::index()` |
| `admin_restaurant_new`  | `/admin/restaurants/neu` | `AdminRestaurantController::new()` |
| `admin_restaurant_edit` | `/admin/restaurants/{id}/bearbeiten` | `AdminRestaurantController::edit()` |
| `admin_restaurant_delete`| `/admin/restaurants/{id}/loeschen` | `AdminRestaurantController::delete()` |
| `admin_restaurant_toggle_verified`| `/admin/restaurants/{id}/verifizieren` | `AdminRestaurantController::toggleVerified()` |
| `admin_restaurant_image_upload`| `/admin/restaurants/{id}/fotos` | `AdminRestaurantController::uploadImage()` |
| `admin_restaurant_image_delete`| `/admin/restaurants/{id}/fotos/{imageId}/loeschen` | `AdminRestaurantController::deleteImage()` |
| `admin_restaurant_image_sort`| `/admin/restaurants/{id}/fotos/sortieren` | `AdminRestaurantController::sortImages()` |
| `app_profile`           | `/profile`     | `ProfileController::index()`        |
| `app_profile_edit`      | `/profile/edit` | `ProfileController::edit()`        |
| `app_profile_password`  | `/profile/password` | `ProfileController::changePassword()` |
| `app_profile_avatar_delete` | `/profile/avatar/delete` | `ProfileController::deleteAvatar()` |
| `app_profile_email_cancel` | `/profile/email/abbrechen` (POST) | `ProfileController::cancelEmailChange()` |
| `app_email_change_confirm` | `/verify/email-change/{token}` | `EmailVerificationController::confirmEmailChange()` |
| `app_passkey_rename`    | `/profile/passkeys/{id}/umbenennen` | `PasskeyController::rename()` |
| `app_passkey_delete`    | `/profile/passkeys/{id}/loeschen` | `PasskeyController::delete()` |
| `webauthn.controller.request.request.login` | `/passkey/login/options` (locale-frei) | Bundle-Controller (Challenge zum Anmelden) |
| `webauthn.controller.creation.request.add_device` | `/passkey/register/options` (locale-frei) | Bundle-Controller (Challenge zum Anlegen) |
| `webauthn.controller.creation.response.add_device` | `/passkey/register` (locale-frei) | Bundle-Controller (Passkey speichern) |
| `app_partner`           | `/partner`     | `PartnerController::index()` (Landing Page) |
| `app_partner_submit`    | `/partner` (POST) | `PartnerController::submit()`    |
| `app_partner_confirm`   | `/partner/confirmation/{token}` | `PartnerController::confirm()` |
| `app_organisations`     | `/organisationen` | `OrganisationController::index()` (Übersicht) |
| `app_organisations_type`| `/organisationen/{slug}` | `OrganisationController::type()` (gemeinden\|unternehmen\|vereine) |
| `app_organisations_submit`| `/organisationen` (POST) | `OrganisationController::submit()` |
| `app_organisations_confirm`| `/organisationen/confirmation/{token}` | `OrganisationController::confirm()` |
| `admin_waitlist_index`  | `/admin/warteliste` | `AdminWaitlistController::index()` (beide Typen) |
| `admin_waitlist_partner_show`| `/admin/warteliste/partner/{id}` | `AdminWaitlistController::showPartner()` |
| `admin_waitlist_organisation_show`| `/admin/warteliste/organisation/{id}` | `AdminWaitlistController::showOrganisation()` |
| `admin_waitlist_partner_status`| `/admin/warteliste/partner/{id}/status` | `AdminWaitlistController::changePartnerStatus()` |
| `admin_waitlist_organisation_status`| `/admin/warteliste/organisation/{id}/status` | `AdminWaitlistController::changeOrganisationStatus()` |
| `admin_waitlist_partner_link`| `/admin/warteliste/partner/{id}/restaurant` | `AdminWaitlistController::linkRestaurant()` |
| `admin_finance_index`   | `/admin/finanzen` | `AdminFinanceController::index()` |
| `admin_finance_new`     | `/admin/finanzen/neu` | `AdminFinanceController::new()` |
| `admin_finance_edit`    | `/admin/finanzen/{id}/bearbeiten` | `AdminFinanceController::edit()` |
| `admin_finance_delete`  | `/admin/finanzen/{id}/loeschen` | `AdminFinanceController::delete()` |
| `admin_finance_snapshot`| `/admin/finanzen/snapshot` (POST) | `AdminFinanceController::snapshot()` |
| `app_open`              | `/open`        | `OpenController::index()` (Transparenzseite) |
| `app_open_redirect`     | `/open` (locale-frei) | Redirect auf `app_open` |
| `app_open_json`         | `/open.json`   | `Open\OpenDataController::stats()` |
| `app_open_dataset_csv`  | `/open/dataset.csv` | `Open\OpenDataController::datasetCsv()` |
| `app_open_dataset_json` | `/open/dataset.json` | `Open\OpenDataController::datasetJson()` |
| `api_cuisine_search`  | `/api/cuisines/search` | `CuisineApiController::search()` |
| `api_cuisine_create`  | `/api/cuisines`        | `CuisineApiController::create()` |
| `api_v1_auth_login`   | `/api/v1/auth/login` (POST) | `Api\V1\AuthController::login()` (json_login) |
| `api_v1_auth_register`| `/api/v1/auth/register` (POST) | `Api\V1\AuthController::register()` |
| `api_v1_restaurants_index` | `/api/v1/restaurants` (GET) | `Api\V1\RestaurantApiController::index()` |
| `api_v1_restaurants_create`| `/api/v1/restaurants` (POST) | `Api\V1\RestaurantApiController::create()` |
| `api_v1_restaurants_show`  | `/api/v1/restaurants/{id}` (GET) | `Api\V1\RestaurantApiController::show()` |
| `api_v1_restaurants_images`| `/api/v1/restaurants/{id}/images` (GET) | `Api\V1\RestaurantApiController::images()` |
| `api_v1_me`           | `/api/v1/me` (GET) | `Api\V1\MeController::me()` |
| `api_v1_me_submissions`| `/api/v1/me/submissions` (GET) | `Api\V1\MeController::submissions()` |
| `app.swagger_ui`      | `/api/docs`    | NelmioApiDoc Swagger-UI |

**Wichtig:** Die `/api/v1/`-Routen sind **locale-frei** (kein `/{_locale}`-Prefix). `config/routes.yaml` importiert `src/Controller/Api/V1/` in einem eigenen Block und `exclude`t es am `controllers`-Loader. Genauso ist `src/Controller/Open/` locale-frei importiert (Block `open_data`) – `exclude` am `controllers`-Loader ist deshalb eine **Liste** mit zwei Einträgen. Die HTML-Seite `/open` liegt dagegen unter `/{_locale}`; die sprachfreie Route `app_open_redirect` leitet auf sie um. Der ältere `CuisineApiController` (`/api/cuisines`) liegt weiterhin UNTER `/{_locale}` (also real `/{_locale}/api/cuisines`).

`/restaurants` accepts query params:
- `?sort=rating` (default) – sorted by rating DESC
- `?sort=name` – sorted A–Z
- `?sort=newest` – sorted by `createdAt` DESC
- `?page=N` – page number (6 items per page, uses Doctrine `Paginator`)
- `?verified=1` – filter to verified restaurants only
- `?wheelchair=1` – filter to wheelchair-accessible restaurants
- `?toilet=1` – filter to restaurants with accessible toilet
- `?dogs=1` – filter to restaurants that allow assistance dogs
- `?lighting=1` – filter to restaurants with bright lighting
- `?changing_table=1` – filter to restaurants with a baby changing table
- `?disabled_parking=1` – filter to restaurants with disabled parking
- `?open=1` – filter to currently open restaurants
- `?city=Strassen` – filter by city name (LIKE search)
- `?cuisine[]=1&cuisine[]=2` – filter by cuisine IDs (ManyToMany JOIN)
- `?lang_de=1&lang_fr=1` – filter by spoken languages (AND: restaurant speaks all selected)
- `?vegan=1` – filter to restaurants with vegan options
- `?vegetarian=1` – filter to restaurants with vegetarian options
- `?halal=1` – filter to restaurants with halal options

All filter params are combinable. `RestaurantRepository::findPaginated(string $sort, int $page, int $limit, array $filters)` handles all filtering.

## Entity: Cuisine (Issue #77)
Felder: id (int, PK), name (VARCHAR 80, unique), slug (VARCHAR 100, unique).
`__toString()` → `$this->name` (nötig für Symfony EntityType).
Repository: `CuisineRepository` — `findAllSorted()`, `search(string $query, int $limit)`, `findOrCreateByName(string $name)`.
Relation: Restaurant hat `$cuisines` (ManyToMany, cascade persist, JoinTable `restaurant_cuisine`).
Helper auf Restaurant: `getCuisineNames(): string` — kommagetrennte Namen.
API: `CuisineApiController` — `GET /api/cuisines/search?q=…` (JSON), `POST /api/cuisines` (erstellt neue Cuisine). Beide Admin-only.
Form: `EntityType` mit Tom Select Stimulus-Controller für Autocomplete + Inline-Create.
Stimulus: `tom_select_controller.ts` — Tom Select mit `remove_button`-Plugin, Load + Create Callbacks.
Fixtures: `CuisineFixtures` — 20 vordefinierte Küchen-Typen.
Migration: `Version20260323000000` — erstellt `cuisine` + `restaurant_cuisine` Tabellen, migriert Daten, entfernt `cuisine` VARCHAR-Spalte.

## Entity: RestaurantImage
Felder: id, filename (VARCHAR 255), altText (VARCHAR 255 nullable), restaurant (ManyToOne Restaurant, CASCADE DELETE), uploadedAt (DateTimeImmutable), sortOrder (INT, default 0).
Collection auf Restaurant: `$images` (OneToMany, cascade persist+remove, orphanRemoval, OrderBy sortOrder ASC).
Helper auf Restaurant: `getCoverImage(): ?RestaurantImage` (erstes Bild), `getGalleryImages(): Collection` (alle außer Cover).
Service: `ImageUploadService` – Upload nach `public/uploads/restaurants/`, Löschung inkl. Dateisystem, `reorderAfterDelete()` für konsekutive Sortierung.

## Entity: User — Avatar (Issue #54)
Zusätzliches Feld: `avatarFilename` (VARCHAR 255 nullable).
Helper: `getAvatarUrl(): ?string` — gibt `/uploads/avatars/{filename}` zurück oder `null`.
Service: `AvatarUploadService` — Upload nach `public/uploads/avatars/`, Löschung inkl. Dateisystem.
Form: `ProfileType` (Name, E-Mail, Avatar-Upload), `ChangePasswordType` (aktuelles + neues PW).
Controller: `ProfileController` — 4 Routen (`app_profile`, `app_profile_edit`, `app_profile_password`, `app_profile_avatar_delete`).
Template: `templates/profile/index.html.twig`, `templates/partials/_avatar.html.twig`.
Migration: `Version20260317000000`.

## Restaurant: submittedBy (Issue #63)
Feld: `submittedBy` (ManyToOne User, nullable, SET NULL) — der Nutzer, der das Restaurant eingereicht hat.
Getter/Setter: `getSubmittedBy()`, `setSubmittedBy()`.
Repository: `RestaurantRepository::findBySubmitter(User $user): Restaurant[]` — alle Restaurants eines Einreichers, sortiert nach `createdAt DESC`.
Profil-Template: Sektion "Meine Einreichungen" in `templates/profile/index.html.twig` — zeigt Emoji, Name (Link), Stadt, Datum, Verifizierungsstatus.
Suggestion-Approval: `AdminSuggestionController::approve()` setzt `submittedBy` automatisch aus `suggestion.suggestedBy`.
Migration: `Version20260319000000`.
Fixtures: Admin → 3 verifizierte, User → 3 unverifizierte, Rest → null.

## Entity: OpeningHour (Issue #64, erweitert in #81)
Felder: id, dayOfWeek (INT 1-7), openTime (TIME nullable), closeTime (TIME nullable), restaurant (ManyToOne CASCADE DELETE).
**Mehrere Zeitslots pro Tag** (Issue #81): kein UNIQUE-Constraint mehr; ein Tag kann beliebig viele `OpeningHour`-Einträge haben (z. B. Mittag + Abend). **Geschlossener Tag = keine Slots** (Feld `isClosed` entfernt).
Collection auf Restaurant: `$openingHours` (OneToMany, cascade, orphanRemoval, OrderBy dayOfWeek ASC, openTime ASC).
Helper auf Restaurant: `getOpeningHoursForDay(int $day): OpeningHour[]` — alle Slots eines Tages.
Service: `OpeningHoursService` — `isOpenNow()`, `isOpenAt()` (iteriert über alle Slots), `getNextOpeningTime(Restaurant, ?\DateTimeInterface $now = null)` (frühester künftiger Slot; optionaler `$now` für Tests). Zeitzone: Europe/Luxembourg.
Twig Extension: `OpeningHoursExtension` — Filter `restaurant|is_open_now`, Funktion `next_opening_time(restaurant)`.
Form: `OpeningHourType` (dayOfWeek hidden, openTime/closeTime) als CollectionType (`allow_add`/`allow_delete`/`prototype`) in `RestaurantType`.
Stimulus: `opening_hours_form_controller.ts` — pro Tag „＋ Zeitfenster hinzufügen" / einzelne Slots entfernen (Prototype-Klonen, gemeinsamer Index, setzt `dayOfWeek` des neuen Slots).
Template: `templates/partials/_opening_hours.html.twig` — Wochenplan (Tag 1–7), mehrere Slots als `12:00 – 14:30 · 18:00 – 22:00`, hervorgehobener heutiger Tag.
Admin-Template: `templates/admin/restaurant/_form.html.twig` — Öffnungszeiten nach Tag gruppiert.
Filter: `?open=1` nutzt SQL JOIN mit TIME-Vergleich (inkl. Nachtschicht-Übertrag), `distinct()` gegen Duplikate bei mehreren Slots.
Test: `tests/Unit/Service/OpeningHoursServiceTest.php` — Multi-Slot-, Nachtschicht- und Next-Opening-Logik.
Migrationen: `Version20260321000000` (erstellt Tabelle), `Version20260619000000` (entfernt UNIQUE-Constraint + `is_closed`-Spalte für Multi-Slot).

## REST-API für die iOS-App (Issue #87)
Versionierte, **locale-freie** REST/JSON-API unter `/api/v1/` als Backend für eine native iOS-App. Bestehende Web-App unverändert. Ansatz: **Plain Controller + explizite Transformer** (kein API Platform, keine Serializer-Groups).
**Bundles:** `lexik/jwt-authentication-bundle` (JWT), `nelmio/cors-bundle` (CORS), `nelmio/api-doc-bundle` (Swagger), `symfony/rate-limiter`. JWT-Keypair in `config/jwt/*.pem` (gitignored) via `php bin/console lexik:jwt:generate-keypair`; env: `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE`, `CORS_ALLOW_ORIGIN`.
**Routing:** `config/routes.yaml` — eigener `api_v1`-Block (prefix `/api/v1`, kein `_locale`) + `exclude: '../src/Controller/Api/V1/'` am `controllers`-Loader (sonst landete die API unter `/{_locale}/api/v1`).
⚠️ **`POST /api/v1/restaurants` legt einen `RestaurantSuggestion` an, kein `Restaurant`** —
und antwortet mit **202**, nicht 201 (QA B23, BF-24). Vorher entstand hier sofort ein
öffentlicher Eintrag: Er stand augenblicklich in der Restaurantliste, auf einer
Detailseite, in den veröffentlichten Kennzahlen von `/open` und im Datensatz unter
CC BY 4.0 — ohne dass jemand ihn gesehen hatte. Gemessen: zwei Aufrufe drückten
`verifiedShare` von 27,3 auf 23,1 %. Der Web-Weg (B11) läuft seit jeher über einen
Vorschlag mit Admin-Freigabe (B21); die API umging genau das.

⚠️ **`cuisines` ruft NICHT mehr `findOrCreateByName()`.** Die Namen landen als Freitext
in `RestaurantSuggestion::$cuisine` (max. 80 Zeichen, sonst 422 statt eines 500ers aus
der Datenbankschicht). Vorher schrieb jeder Aufruf dauerhaft in die **öffentliche
Filterauswahl der Website** — gemessen wurden dort „Pizzza" und „JETZT BEI UNS
BESTELLEN 0900-123456", 50 Stück je Anfrage. Welcher echte Küchen-Typ gemeint ist,
entscheidet der Admin bei der Freigabe.

⚠️ **Nicht übermittelte Merkmale sind `TriState::UNKNOWN`, nicht `false`.** Der Vorschlag
unterscheidet „nein" von „weiß nicht"; die alte Fassung machte aus jedem nicht gefragten
Merkmal ein „nein", das niemand behauptet hatte.

**`ApiAuthenticationFailureSubscriber`** bringt die Antworten des JWT-Bundles auf dieselbe
Form wie alle anderen (`{error:{code,message}}`). Das Bundle wirft keine Exception,
sondern schreibt die Antwort selbst — `ApiExceptionSubscriber` kommt dort nicht zum Zug.
Betroffen waren die beiden häufigsten Fälle eines Mobil-Clients: falsches Passwort und
abgelaufenes Token (BF-26).

⚠️ **Ohne `Accept-Language` antwortet die API luxemburgisch** (`translation.yaml`:
`default_locale: lb`). Bewusst nicht geändert — das wäre ein Eingriff in die gesamte
Website.

**Controller** (`src/Controller/Api/V1/`): `AuthController` (`login` = json_login-Stub, Rumpf nie erreicht; `register` repliziert den Web-Flow inkl. E-Mail-Verifikation, gibt KEIN Token zurück; **Anti-User-Enumeration**: identische generische 201-Antwort, egal ob die E-Mail existiert – bestehende Adressen erhalten einen Hinweis-Mail statt einer Bestätigung, Passwort wird in beiden Zweigen gehasht (Timing)), `RestaurantApiController` (`index` mit Envelope `{data, meta:{page,limit,total,totalPages,sort}}` + Filter-Mapping auf `RestaurantRepository::findPaginated`; `show`; `images`; `create` mit `submittedBy`=current user, `isVerified=false`), `MeController` (`me`, `submissions` via `findBySubmitter`; `#[IsGranted('IS_AUTHENTICATED_FULLY')]`).
**Transformer** (`src/Api/`): `RestaurantTransformer` (`list()`/`detail()`/`image()`, injiziert `OpeningHoursService` für `isOpenNow` + `nextOpeningTime`, Öffnungszeiten gruppiert nach Tag 1–7) und `UserTransformer` (`profile()`). Bild-/Avatar-URLs sind **absolut** (für native iOS-Clients) via `AssetUrlBuilder` (`src/Api/`): nutzt Scheme+Host des aktuellen Requests, optionaler Env-Override `APP_API_BASE_URL` (Proxy/CDN). Koordinaten im `create`-Endpoint werden auf Dezimalformat + Bereich ±90/±180 geprüft (422 statt DBAL-500). Bewusst explizit statt Serializer-Groups, weil die Entity untypische Getter hat (`acceptsCash()`, `isWheelchairAccessible()`, `hasAccessibleToilet()`), die der ObjectNormalizer nicht zuverlässig erkennt. `password`/Token werden strukturell NIE ausgegeben.
**Security** (`config/packages/security.yaml`): zwei stateless Firewalls VOR `main`: `api_login` (`^/api/v1/auth/login$`, json_login mit `username_path: email`, Lexik success/failure-Handler) und `api` (`^/api/v1`, `jwt: ~`). `access_control`: `auth` + `GET restaurants` = PUBLIC; `me` + `POST restaurants` = IS_AUTHENTICATED_FULLY. Web-Regeln (`^/[a-z]{2}/...`) bleiben kollisionsfrei.
**Fehler/CORS/Rate-Limit** (`src/EventSubscriber/`): `ApiExceptionSubscriber` (nur `^/api/v1`, JSON `{error:{code,message}}`; **anonyme** AccessDenied → 401, sonst 403; übernimmt Header der HTTP-Exception, z. B. `Retry-After`/`WWW-Authenticate`; im Debug-Modus Exception-Detail bei 500). `ApiRateLimitSubscriber` (IP-basiert, Login **und Registrierung** strenger — letztere seit BF-25 unter `api_register` mit 5/Stunde statt 100/Minute; ohne den Deckel waren das bis zu 100 Mails je Minute an eine frei wählbare **fremde** Adresse, weil die Anti-Enumeration bewusst auch an bestehende Adressen schreibt; bei Limit `TooManyRequestsHttpException` → 429 inkl. `Retry-After`-Sekunden aus `RateLimit::getRetryAfter()`). Limiter in `config/packages/framework.yaml` (`api_anonymous` sliding_window 100/min, `api_login` fixed_window 5/min, `api_register` sliding_window 5/h; in `when@test` alle auf 10000 gelockert). CORS in `config/packages/nelmio_cors.yaml` nur `^/api/v1/`. `bool $debug`-Bind in `config/services.yaml`.
**Swagger:** `config/packages/nelmio_api_doc.yaml` (`areas.default.path_patterns: ^/api/v1`, Bearer-securityScheme), Routen `app.swagger_ui` (`/api/docs`) + `app.swagger` (`/api/docs.json`) in `config/routes/nelmio_api_doc.yaml`. OA-Tags + `#[Security(name:'Bearer')]` auf geschützten Endpunkten.
**Tests** (`tests/Functional/Controller/Api/V1/`): `RestaurantApiControllerTest`, `AuthControllerTest`, `MeControllerTest` (WebTestCase, inkl. `password`-Regression und `sort`-Reihenfolge/`meta.sort`). Token im Test via `JWTTokenManagerInterface::create()`. **Test-DB:** `DATABASE_URL` musste in `.env.test` ergänzt werden (`.env.local` wird im Test-Env nicht geladen). `when@test` in `messenger.yaml` routet `async` → `in-memory://` (kein `messenger_messages`-Table, E-Mails nicht real versendet).

## PWA – Installierbare iPhone-App (Issue #83)
Endlech.lu ist als Progressive Web App über Safaris „Zum Home-Bildschirm" installierbar (Vollbild, App-Icon, Offline-Fallback). **Kein** separates Swift-Projekt; alle Templates werden weiterverwendet. Reiner Frontend-/Static-File-Ansatz — keine Entity/Migration/Backend-Logik.
**Locale-frei:** Alle PWA-Dateien liegen als statische Dateien auf Root-Ebene in `public/` (kein `/{_locale}`-Prefix, kein Controller). Der Service-Worker-Scope `/` erfordert das.
**Dateien:**
- `public/manifest.webmanifest` — `name`/`short_name`, `start_url`/`scope` `/`, `display: standalone`, `orientation: portrait`, `theme_color #0891b2` (cyan-600), `background_color #ffffff`, Icons 192/512 (`any`) + 512 (`maskable`). Eingebunden via `<link rel="manifest">` in `base.html.twig`.
- `public/icons/icon-{57,60,72,76,114,120,144,152,180,192,512}.png` — generiert mit `bin/generate-pwa-icons.sh` (macOS `sips`): Logo wird zuerst quadratisch weiß gepaddet (`--padToHeightWidth`), dann skaliert (sonst Verzerrung, da `logo.png` 10000×7664). `icon-512.png` dient zugleich als maskable. **Eingecheckt** (nicht von `.gitignore` erfasst).
- `public/sw.js` — Vanilla Service Worker, Scope `/`. `CACHE_VERSION`-Konstante (bei Änderungen erhöhen). install: App-Shell (`offline.html`, Logo, Icons, Manifest) vorcachen + `skipWaiting()`. activate: alte Caches löschen + `clients.claim()`. fetch (nur GET, eigene Origin, **nie** `/api/`): Navigationen network-first → `offline.html`-Fallback; `/build/`-Assets stale-while-revalidate (Encore-Hashing-sicher); Bilder/Icons cache-first.
- `public/offline.html` — eigenständige HTML (Inline-CSS, da offline kein Server/Encore-Asset erreichbar), Endlech.lu-Branding, Reload-Button. Vom SW vorgecacht.
- `templates/partials/_bottom_nav.html.twig` — mobile Bottom-Navigation (`fixed bottom-0 md:hidden`, Safe-Area unten via `pb-[env(safe-area-inset-bottom)]`, Tap-Targets ≥ 44 px). 4 Items: Home, Restaurants, Über uns, Profil (bzw. Login für Gäste), aktiver Zustand über Route-Vergleich. Eingebunden in `base.html.twig` analog Cookie-Banner (**nicht** auf `admin_*`-Routen). `<main>` hat `pb-16 md:pb-0`, damit Inhalt nicht hinter der Nav liegt.
**`base.html.twig`:** `viewport-fit=cover` im Viewport; iOS-Meta-Tags (`apple-mobile-web-app-capable`, `…-status-bar-style: black-translucent`, `…-title: Endlech.lu`, `mobile-web-app-capable`); `apple-touch-icon` (180) + Legacy-Größen per Twig-Loop; `<meta name="theme-color" content="#0891b2">`.
**`assets/app.ts`:** registriert `/sw.js` (Scope `/`) beim `load`-Event (Fehler werden geschluckt; kein Workbox/keine neue Abhängigkeit).
**`assets/styles/app.css`:** `@media (max-width: 767px)` setzt `input/select/textarea` auf `font-size: 16px` (verhindert iOS-Auto-Zoom).
**Übersetzungen:** neue Keys `nav.home`, `nav.restaurants` in `messages.{de,en,fr,lb}.yaml` (für die Bottom-Nav; `nav.about`/`nav.profile`/`nav.login` wiederverwendet).
**Bewusst nicht enthalten (Folge-Issues):** Apple-Splash-Screens, Pull-to-Refresh, Swipe-Gesten, Push-Notification-Scaffold, vollständiger 7-Seiten-Mobile-Audit.

## Passkey-Login (WebAuthn)

Anmeldung per Face ID, Touch ID oder Geräte-PIN – **zusätzlich** zum Passwort, das unverändert bestehen bleibt. Auf `/login` steht ein Knopf, der **keine E-Mail-Eingabe verlangt**: Der Browser zeigt die passenden Konten selbst an. Bundle: `web-auth/webauthn-symfony-bundle` ^5.3.5 (Flex-Recipe greift nicht – liegt nur in `recipes-contrib` und nur in Version 3.0; `bundles.php`, `config/packages/webauthn.yaml` und `config/routes/webauthn.yaml` sind von Hand angelegt, wie bei Sentry).

**Anmeldung als Formular-Login, nicht als JSON-Schnittstelle.** `App\Security\PasskeyAuthenticator` erbt von `Webauthn\Bundle\Security\Authentication\WebauthnAuthenticator` (das ist ein `AbstractLoginFormAuthenticator`) und liest die Assertion aus dem Feld `_assertion`. Der `webauthn:`-Firewall-Schlüssel des Bundles wird bewusst **nicht** benutzt: Er ist für 6.0 abgekündigt und verlangt zwingend `Content-Type: application/json`. Über das Formular läuft der Passkey dagegen durch dieselbe Mechanik wie das Passwort – gleicher `check_path` (`app_login`), gleiche Weiterleitung, gleiches `remember_me`.

⚠️ **`entry_point: form_login` ist Pflicht**, sobald eine Firewall zwei Authenticator führt – sonst bricht der Container-Build mit `RegisterEntryPointPass`. Nur `form_login` kennt den `login_path`.

⚠️ **`supports()` prüft mit `has('_assertion')`, nicht auf einen gefüllten Wert** (ENDLECH-6).
Das Passkey-Formular führt kein `_username`. Prüft man auf einen **gefüllten** Wert, fällt
ein Submit mit leerer Assertion an den `FormLoginAuthenticator` durch — und der wirft dort
`BadRequestHttpException: The key "_username" must be a string, "NULL" given.` Statt der
Meldung „Passkey-Anmeldung fehlgeschlagen" sah der Nutzer eine nackte Fehlerseite. Mit
`has()` beansprucht der Passkey-Weg jeden Submit aus seinem Formular; eine unbrauchbare
Assertion scheitert regulär und wird zur Flash-Nachricht. Der Passwort-Weg ist unberührt,
sein Formular sendet kein `_assertion`. Abgesichert durch
`SecurityControllerTest::testEndlech6…` mit vier Assertion-Formen.

⚠️ **Der Passkey-Knopf hat ein eigenes `<form>`.** Der `AuthenticationController` aus dem npm-Paket ruft vor dem Start `form.checkValidity()`; im Passwort-Formular sind beide Felder `required`, ein Klick liefe dort gegen die Browser-Validierung. Das Passkey-Formular steht **zuerst im Markup**, weil die Tab-Reihenfolge der sichtbaren folgen muss. Deshalb nutzt `SecurityControllerTest` `formWithField()` statt `filter('form')` – wer dort auf `filter('form')` zurückfällt, greift das Passkey-Formular und bekommt „Unreachable field \"_username\"".

**Entity `WebauthnCredential`** erbt von `Webauthn\CredentialRecord`. Das Bundle registriert dafür selbst eine mapped-superclass (`WebauthnBundle::registerMappings()`) und trägt fünf DBAL-Typen (`base64`, `aaguid`, `trust_path`, …) über `WebauthnExtension::prepend()` ein – für die geerbten Felder braucht es also **keine** ORM-Attribute und keine Konfigurationszeile. Eigene Felder: `id`, `user` (ManyToOne, `ON DELETE CASCADE`), `name`, `createdAt`, `lastUsedAt`.

⚠️ **Die geerbten Spalten sind LONGTEXT** (der Typ `base64` deklariert sich als CLOB). Die bei jeder Anmeldung durchsuchte `public_key_credential_id` ist deshalb nur mit Längenangabe indizierbar – im Mapping als `#[ORM\Index(..., options: ['lengths' => [100]])]`.

⚠️ **`findOneByCredentialId()` übergibt die ROHE Kennung**, nicht `base64_encode(...)`. Doctrine kodiert gebundene Parameter anhand des Feld-Mappings selbst; eine Kodierung von Hand käme doppelt an und fände nie etwas — der Login schlüge mit „The credential ID is invalid" fehl. (Das mitgelieferte `DoctrineCredentialSourceRepository` kodiert vor, baut die Abfrage aber über einen QueryBuilder ohne Feldbezug.)

⚠️ **`saveCredentialRecord()` läuft bei JEDER Anmeldung**, nicht nur beim Anlegen: Der Signaturzähler wandert mit und ist der Klon-Schutz. Ein reines `persist()` erzeugte Duplikate. Beim Anmelden ist der übergebene Datensatz bereits die Entity (er kam aus `findOneByCredentialId()`), beim Anlegen ein frischer `PublicKeyCredentialSource` → `WebauthnCredential::fromRecord()`.

**`User::$webauthnHandle`** (VARCHAR 64, nullable, unique) statt der Datenbank-ID: Der Handle liegt dauerhaft auf dem Gerät des Nutzers. Erzeugt bei Bedarf in `WebauthnUserEntityRepository::findOneByUsername()`.
⚠️ **`bin2hex(random_bytes(16))`, nicht 32 wie bei `generateVerificationToken()`** – `PublicKeyCredentialUserEntity` erzwingt `strlen($id) <= 64`.

**Keine Kontoerstellung per Passkey:** `WebauthnUserEntityRepository` implementiert bewusst **nicht** `CanRegisterUserEntity`/`CanGenerateUserEntity`. Ohne diese Schnittstellen lehnt das Bundle es strukturell ab – verlässlicher als eine Konfigurationszeile.

**Konfiguration bewusst schmal** (`phpunit.dist.xml` hat `failOnDeprecation="true"`, jede abgekündigte Option färbt die Suite rot). Nicht gesetzt: `rp.name` (seit 5.3.0), `rp.icon` (seit 5.1.0), `secured_rp_ids` (seit 5.2.0), `options_storage` je Firewall (seit 5.2.0). Nicht benutzt: `DoctrineCredentialSourceRepository` (seit 5.2.0), `PublicKeyCredentialSourceRepositoryInterface`/`CanSaveCredentialSource` (seit 5.3) – stattdessen `CredentialRecordRepositoryInterface` + `CanSaveCredentialRecord`.

⚠️ **`allowed_origins` bleibt auf Production leer.** Ist die Liste gefüllt, gilt nur noch exakter Origin-Abgleich inklusive Port, und Einträge ohne Schema werden still auf `https://…:443` normalisiert. Leer greift der Weg der Spezifikation (HTTPS-Zwang plus Abgleich gegen die rp id). **Lokal ist ein `when@dev`-Block nötig** (`http://localhost:8000`), weil `CheckAllowedOrigins` serverseitig HTTPS verlangt – Browser behandeln `localhost` als sicher, diese Prüfung nicht. Port anpassen, wenn `symfony server:start` ausweicht.

**Frontend:** `@web-auth/webauthn-stimulus` (npm 5.3.5) + `@simplewebauthn/browser`.
⚠️ **Nicht in `assets/controllers.json` eintragen** – das StimulusBundle löst jeden Eintrag dort gegen ein Composer-Paket auf und bricht mit „Could not find package". Registriert wird in `assets/stimulus_bootstrap.ts` unter den eigenen Bezeichnern `passkey-auth` und `passkey-register`.
Daneben `assets/controllers/passkey_ui_controller.ts`: Feature-Detection (Knopf erscheint nur bei vorhandenem `window.PublicKeyCredential`), Ladezustand und übersetzte Meldungen aus den Events des Fremdpakets. `ERROR_CEREMONY_ABORTED` erzeugt bewusst **keine** Meldung – Abbruch ist eine Entscheidung, kein Fehler.
⚠️ `submitViaForm` schickt per `form.submit()` ab, was das submit-Ereignis überspringt – `generateCsrfToken()` aus `csrf_protection_controller.ts` läuft dabei nicht. Unkritisch, weil der Authenticator kein CSRF-Badge setzt und eine Assertion an Herkunft und Challenge gebunden ist.

**Verwaltung im Profil** (`PasskeyController`, `templates/partials/_passkey_manage.html.twig`): Umbenennen und Entfernen sind gewöhnliche Formulare und funktionieren ohne JavaScript; nur das Anlegen braucht zwingend eins. Der Anzeigename kommt beim Anlegen aus dem User-Agent (`WebauthnCredentialRepository::guessDeviceName()` → „iPhone", „Mac", „Android"; Produktnamen statt Übersetzungsschlüssel, weil der Wert einmal festgeschrieben wird).
⚠️ **Die Besitzprüfung steht VOR der CSRF-Prüfung.** Wer nicht Eigentümer ist, hat dort unabhängig vom Token nichts verloren; die Antwort ist 403 statt einer Weiterleitung. Am Schutz ändert das nichts – ein Angriff über eine fremde Seite zielt auf eine ID des Opfers und scheitert danach am Token.

**Tests:** `tests/Integration/Repository/WebauthnCredentialRepositoryTest.php` (Anlegen vs. Fortschreiben, base64-Kodierung in beide Richtungen, verwaister Handle) und `tests/Functional/Controller/PasskeyControllerTest.php` (Options-Endpunkte, Umbenennen/Löschen über die gerenderten Formulare, fremder Passkey → 403). Die Assertion selbst ist mit PHPUnit nicht testbar – dafür braucht es einen virtuellen Authenticator im Browser (Chrome DevTools Protocol, `WebAuthn.addVirtualAuthenticator`).

**Migration:** `Version20260821000000` – `webauthn_credential` (FK auf `` `user` `` mit CASCADE, Präfix-Index) und `user.webauthn_handle`.

**Übersetzungen:** Block `passkey:` in `messages.{de,en,fr,lb}.yaml`, dazu `flash.passkey_*`.

**Bewusst nicht enthalten:** Conditional UI / Autofill (`conditionalUi: true` kann das Paket), Passkey-Registrierung neuer Konten, Passkeys in `/api/v1`, Attestation-Prüfung (`attestation_conveyance` bleibt `none` – ein Attestation-Zwang sperrte Authenticator aus, ohne dass jemand die Herstellerdaten auswertet).

## E-Mail-Änderung mit Bestätigung (QA B04, BF-19)

Eine im Profil eingegebene neue Adresse wird **vorgemerkt, nicht übernommen**. Vorher
wechselte `User::$email` im selben Request und `is_verified` blieb auf `true` — der
Bestätigungsstatus galt damit für eine nie bestätigte Adresse, und wer eine Sitzung
kaperte, schrieb das Konto dauerhaft auf sich um. Einen Rückweg gäbe es nicht: Ein
Passwort-Zurücksetzen existiert im Projekt bis heute nicht (Feature `01`).

**Felder:** `pendingEmail`, `pendingEmailToken`, `pendingEmailTokenExpiresAt`
(Migration `Version20260824120000`, reine `ADD COLUMN` — MariaDB-10.5-tauglich).
Methoden auf `User`: `requestEmailChange()`, `confirmEmailChange()`,
`clearPendingEmail()`, `isPendingEmailTokenExpired()`.

⚠️ **`ProfileController::edit()` merkt sich die bisherige Adresse VOR `handleRequest()`
und setzt sie danach zurück.** `ProfileType` ist an die Entity gebunden; nach
`handleRequest()` steht dort bereits der eingegebene Wert. Genau der soll nicht wirksam
werden — die Validierung muss ihn aber sehen, sonst prüfte `UniqueEntity` auf dem alten
Wert und eine bereits vergebene Adresse ginge durch.

⚠️ **Zwei Mails, und die wichtigere geht an die ALTE Adresse.** Wer ein Konto übernehmen
will, sitzt im neuen Postfach und liest die Bestätigung ohnehin mit; nur die Warnung an
die bisherige Adresse erreicht den rechtmäßigen Inhaber. Vorlagen:
`email/email_change.html.twig` (Knopf) und `email/email_change_notice.html.twig`
(Warnung, kein Knopf).

⚠️ **`pending_email` hat keinen Unique-Index** — siehe `docs/data-model.md`. Beim
Einlösen prüft der Controller gegen `email` und räumt den Vorgang ab; ohne das liefe
der `flush()` in eine Unique-Verletzung und der Nutzer sähe einen 500er.

**Die Bestätigungsroute liegt unter `/verify/…`, nicht unter `/profile/…`** — dort
greift `IsGranted('IS_AUTHENTICATED_FULLY')` auf Klassenebene. Der Token *ist* der
Nachweis (Zugriff auf das neue Postfach); eine Anmeldepflicht machte den Klick aus dem
Postfach heraus unbenutzbar, ohne etwas zu sichern. `access_control` deckt
`^/[a-z]{2}/verify` bereits als `PUBLIC_ACCESS` ab. Zwei Pfadsegmente, deshalb kein
Konflikt mit `/verify/{token}`.

⚠️ **Der Hinweis auf den offenen Vorgang steht AUSSERHALB des Profilformulars.** Ein
`<form>` im `<form>` ist ungültiges HTML — der Browser verwirft das innere, und der
Abbrechen-Knopf wäre wirkungslos. Im Test fiel das als „Kein Formular mit action
/profile/email/abbrechen" auf.

**Limiter `password_change`** (5 je 15 Minuten, BF-20) zählt **am Konto**, nicht an der
IP: Der Angriff ist das Raten des aktuellen Passworts aus einer gekaperten Sitzung
heraus, und dort wechselt die IP mühelos.

## Presse-Kit (`/presse`, Feature 05)

Öffentliche Presseseite: Beschreibungstexte in drei Längen, Faktenblatt mit den
Livezahlen von `/open`, Materialpaket zum Herunterladen, Gründerporträt mit Kurzvita,
freigegebene Zitate, Meldungen und Pressekontakt. **Keine Entity, keine Migration** —
Struktur als unveränderliche Wertobjekte unter `App\Press\`, Texte in der eigenen
Übersetzungsdomain `press`. Aufbau wie Feature 03.

⚠️ **Wer eine Datei in `public/presse-kit/` ersetzt, erhöht `CACHE_VERSION` in
`public/sw.js`.** Der Service Worker liefert Bilder cache-first aus; ein
wiederkehrender Besucher sähe sonst die alte Logo-Vorschau neben dem neuen Paket. Das
Paket selbst wird nie gecacht (es ist kein `image` und liegt nicht unter `/build/`) —
genau deshalb laufen die beiden auseinander, und **kein Prüflauf sieht es**, weil es
im Browser passiert.

⚠️ **Das Paket ist eine committete Datei, erzeugt von `app:press:package`**
(`make press-kit`), nicht zur Laufzeit gepackt. Es liegt unter `public/presse-kit/` und
wird vom Webserver direkt ausgeliefert — der Front-Controller sieht es nie, deshalb
gibt es hier nichts zu deckeln. `PressPackageTest` öffnet die Datei und vergleicht
ihren Inhalt mit `PressRegistry::assets()`; wer eine Datei austauscht und den Befehl
nicht neu laufen lässt, bekommt einen roten Prüflauf statt eines veralteten Downloads.

⚠️ **`ext-zip` steht in `require-dev` und in der CI-Extension-Liste.** Die Anwendung
selbst braucht die Erweiterung nie — nur der Befehl und der Prüflauf. Fehlt sie in
`.github/workflows/ci.yml`, bricht der Lauf mit einer Meldung über eine unbekannte
Klasse ab, die wie ein Codefehler aussieht.

⚠️ **Das Verzeichnis heißt `presse-kit`, nicht `presse` — und ein Verzeichnis unter
`public/` darf generell nicht so heißen wie eine Route.** Auf Apache schickt `mod_dir`
jeden Aufruf von `/presse` per **301** auf `/presse/`, sobald ein gleichnamiges
Verzeichnis existiert; Symfonys Trailing-Slash-Regel schickt zurück — eine endlose
Schleife auf genau der Adresse, die sprachfrei erreichbar sein sollte (BF-100). **Lokal
unsichtbar**, weil der Entwicklungsserver kein `mod_dir` hat: Drei QA-Durchläufe und ein
grüner CI-Lauf haben es nicht gesehen. `RouteDirectoryCollisionTest` prüft seither die
Ursache statt des Verhaltens. Dazu gibt es `/presse/` **mit** Schrägstrich als eigene
Weiterleitung — der 301er von `mod_dir` steht in den Browsern derer, die die kaputte
Adresse einmal geöffnet haben, und ohne diese Route drehten sie sich weiter im Kreis.

⚠️ **Betreiberangaben stehen als Parameter, nicht im Übersetzungskatalog**
(`app.operator_name`, `app.operator_address`, `app.operator_responsible`, dazu
`app.press_email`). Sie erscheinen auf **zwei** Seiten — Faktenblatt und Impressum —
und vier Katalogeinträge wären vier Stellen, an denen eine Anschrift auseinanderläuft;
`CatalogueCompletenessTest` prüft Vollständigkeit, nicht Gleichheit. Als Twig-Globals
eingebunden, weil zwei Controller sie brauchen und der dritte, der sie vergisst, eine
Seite mit halben Angaben ausliefern würde.

⚠️ **Die Angabe zur Behinderung steht in genau einem Katalogschlüssel** (`person.bio`).
`PressCatalogueTest` prüft, dass kein anderer Schlüssel der Domain sie enthält — damit
ist ihr Widerruf eine Textstelle und keine Suche über vier Kataloge. Sie im Boilerplate
zu wiederholen ist naheliegend (es ist das stärkste Argument des Textes) und macht
genau diese Zusage kaputt.

⚠️ **Die Wortgrenzen der Beschreibungstexte stehen im Enum `BoilerplateLength`,
nicht im Prüflauf** — ein Prüflauf mit eigenen Zahlen prüft gegen sich selbst.
Gezählt wird **je Sprache**: Französisch braucht regelmäßig 15–20 % mehr Wörter als
Deutsch, und ein deutscher Text, der mit 28 Wörtern gerade passt, sprengt die Grenze
in der Übersetzung.

## Cookie-Consent-Banner (Issue #82)
DSGVO-Banner, das beim ersten Besuch unten erscheint und die Wahl (`accepted`/`declined`) 365 Tage im Cookie `cookie_consent` speichert. Keine Entity/Migration/Backend-Änderung — rein clientseitig.
Stimulus: `assets/controllers/cookie_consent_controller.ts` — `connect()` zeigt das Banner, wenn kein `cookie_consent`-Cookie existiert; `accept()`/`decline()` setzen den Cookie (`path=/; max-age=365d; samesite=lax`, `secure` nur bei HTTPS — Muster aus `csrf_protection_controller.ts`) und blenden aus. Values: `cookieName` (default `cookie_consent`), `lifetime` (default 365). Cross-Element-Kommunikation idiomatisch über Fenster-Event: der Footer-Link ist eine eigene Controller-Instanz (`<li data-controller="cookie-consent">`), sein `openSettings()` stößt `this.dispatch('open')` an; die Banner-Instanz fängt es über den `@window`-Descriptor (`data-action="cookie-consent:open@window->cookie-consent#reopen"`) ab und zeigt das Banner (`reopen()`). Beide `reopen()`/`connect()` sind via `hasBannerTarget` abgesichert.
Template: `templates/partials/_cookie_banner.html.twig` — `role="dialog"`, `aria-modal="false"`, fixiertes Banner (`fixed bottom-0`), barrierefrei (Tastatur/ARIA/Kontrast), responsiv. Verlinkt auf `path('app_impressum') ~ '#datenschutz'`.
Einbindung: `templates/base.html.twig` — Banner-Include **und** Footer-Link „Cookie-Einstellungen" nur außerhalb Admin: `{% if not (app.request.attributes.get('_route') starts with 'admin_') %}` (Footer wird auch auf Admin-Seiten gerendert).
Datenschutz-Anker: `templates/impressum/index.html.twig` — Datenschutz-`<section id="datenschutz" class="... scroll-mt-24">`.
Übersetzungen: `cookie`-Block (`title`, `description`, `accept`, `decline`, `privacy_link`, `settings`) in `messages.{de,en,fr,lb}.yaml`.

## Nearby Stops / Public Transport (Issue #65)
Felder auf Restaurant: `latitude` (DECIMAL 10,8 nullable), `longitude` (DECIMAL 11,8 nullable), `nearbyStopsNote` (TEXT nullable).
Helper: `hasCoordinates(): bool` — prüft ob lat+lng gesetzt.
DTO: `App\DTO\NearbyStop` (readonly) — name, distance (Meter), lines (string[]), type (bus/tram/mixed).
Service: `App\Service\PublicTransportService` — `findNearbyStops(string $lat, string $lng): NearbyStop[]`. Nutzt HAFAS API (`cdt.hafas.de`), Cache 24h, Graceful Degradation (leerer API-Key → `[]`). Parameter: `app.mobiliteit_api_key`, `app.mobiliteit_radius` (500), `app.mobiliteit_max_stops` (5).
Env: `MOBILITEIT_API_KEY` in `.env` (leer = deaktiviert).

⚠️ **Der Block heißt „Nahverkehr", nicht „barrierefreie Haltestellen".** Die
HAFAS-Abfrage kennt **kein** Barrierefreiheitsmerkmal — `grep` nach
`accessib|barrier|wheelchair` in `PublicTransportService` und `NearbyStop` findet
nichts. Bis 2026-08-24 stand auf der Detailseite trotzdem „Keine barrierefreien
Haltestellen in der Nähe gefunden" und im Admin-Formular „automatische Suche nach
barrierefreien Haltestellen" (QA B10, BF-46). Auf dieser Plattform ist eine erfundene
Barrierefreiheitsaussage der schwerste Fehler, den ein Text machen kann. Die Texte
sagen jetzt, was tatsächlich geprüft wurde, und der Block trägt einen Herkunftshinweis.

⚠️ **Radius 1000, nicht 500.** Bei 500 m lieferte die Schnittstelle für **8 von 11**
Restaurants null Haltestellen; an denselben Koordinaten sind es bei 2000 m sieben. Nach
der Umstellung: 8 von 11 mit Treffern.

⚠️ **`'timeout' => 3` ist Pflicht.** Ohne eigene Vorgabe greift `default_socket_timeout`
— gemessen 60 s, so lange wartete der Besucher der Detailseite bei hängender
Schnittstelle. Der `catch (\Throwable)` fängt den **Ausfall**, nicht die **Verzögerung**.

⚠️ **Die Exception-Meldung nicht ins Log durchreichen.** Sie enthält die vollständige
URL samt `accessId` — HAFAS sieht die Übergabe des Schlüssels als Query-Parameter vor.
Protokolliert werden Klasse und Statuscode. Den zweiten Weg (Symfonys eigener
`http_client`-Kanal, der in `prod` **nicht** ausgeschlossen ist) deckt
`App\Monolog\SecretMaskingProcessor` ab.
Template: `templates/partials/_nearby_stops.html.twig` — Haltestellen-Karten mit Bus/Tram-Icons, Linien-Badges, Distanz.
Form: `latitude` (NumberType, Range -90/90), `longitude` (NumberType, Range -180/180), `nearbyStopsNote` (TextType, max 1000).
Admin-Fieldset: "Standort & Nahverkehr" in `_form.html.twig`.
Migration: `Version20260322000000`.
Fixtures: Alle 11 Restaurants mit echten Luxemburg-Koordinaten. Brasserie du Grund mit Beispiel-`nearbyStopsNote`.

## ⚠️ `setMaxResults()` mit `addSelect()`-Joins braucht `Paginator`

Ein `leftJoin` mit `addSelect` holt eine Collection mit (gegen N+1) — und vervielfacht
dabei die SQL-Zeilen je Entity. `setMaxResults()` begrenzt aber die **Zeilen**, nicht
die Objekte. `RestaurantRepository::findTopRated(6)` lieferte dadurch **ein** Restaurant
statt sechs: Das bestbewertete Haus brachte allein 14 Zeilen mit (7 Öffnungszeiten × 2
Küchen), und das `LIMIT 6` war innerhalb des ersten Datensatzes verbraucht (QA B12,
BF-64).

Gemessen vor der Reparatur: `findTopRated(6)` → 1, `(20)` → 2, `(100)` → 7.

**Die Lösung ist `new Paginator($qb->getQuery(), true)`** — der zweite Parameter
`$fetchJoinCollection` ist genau dafür da. `findPaginated()` im selben Repository macht
es seit jeher so; nur `findTopRated()` tat es nicht.

⚠️ **Ein Test mit `assertLessThanOrEqual($limit, count(...))` fängt das nicht.** Genau so
stand er in `RestaurantRepositoryTest` und war grün, während die Startseite eine Karte
zeigte. Bei einer Begrenzung gehört `assertCount(min($limit, $bestand), …)` geprüft.

## Entity: OrderingOption (Issue #43)
Felder: id (int, PK), platform (VARCHAR 20 – Werte aus `App\Enum\OrderingPlatform`), url (VARCHAR 500), restaurant (ManyToOne Restaurant, CASCADE DELETE).
Collection auf Restaurant: `$orderingOptions` (OneToMany, cascade persist+remove, orphanRemoval).
Enum: `App\Enum\OrderingPlatform` – Cases: `uber_eats`, `deliveroo`, `just_eat`, `wolt`, `wedely`, `goosty`, `phone`, `website`, `other`. Helper: `label()`, `emoji()`, `actionLabel()`, `logoPath()` (gibt Pfad zu SVG-Logo zurück oder `null` für generische Optionen).
SVG-Logos: `public/images/platforms/` – 6 SVG-Dateien für Marken-Plattformen (uber-eats, deliveroo, just-eat, wolt, wedely, goosty).
Form: `OrderingOptionType` als CollectionType-Entry in `RestaurantType` (`by_reference: false`).
Migration: `Version20260314200000`.

## Entity: RestaurantSuggestion
Felder: id, suggestedBy (ManyToOne User nullable SET NULL), name (VARCHAR 150), city (VARCHAR 100), cuisine (VARCHAR 80), emoji (VARCHAR 10, default '🍽️').
Barrierefreiheit (6 × `?TriState`): isWheelchairAccessible, hasAccessibleToilet, allowsAssistanceDogs, hasBrightLighting, hasChangingTable, hasDisabledParking.
Zahlung (3 × `?TriState`): acceptsCash, acceptsCard, acceptsPayconiq.
Ernährung (3 × `?TriState`): isVegan, isVegetarian, isHalal.
**Dreiwertig statt bool (Ja / Nein / Weiß nicht):** siehe „Dreiwertige Antworten" unten.
Sprachen: spokenLanguages (JSON, default []) — Werte aus `App\Enum\Language`.
Kontakt: phone (VARCHAR 30 nullable), email (VARCHAR 180 nullable), website (VARCHAR 500 nullable).
Social Media: instagramUrl (VARCHAR 500 nullable), facebookUrl (VARCHAR 500 nullable), tiktokUrl (VARCHAR 500 nullable).
Meta: notes (TEXT nullable), status (VARCHAR 20, default 'pending'), adminNote (TEXT nullable), createdAt (DateTimeImmutable).
Status-Konstanten: STATUS_PENDING, STATUS_APPROVED, STATUS_REJECTED.
Form: `RestaurantSuggestionType` — Multi-Step Wizard mit 5 Steps (Grunddaten, Barrierefreiheit, Ernährung & Zahlung, Kontakt & Sprachen, Notizen).
Stimulus: `suggestion_wizard_controller.ts` — Step-Navigation mit Prev/Next/GoTo, CSS-Klassen-Toggle, plus clientseitige Pflichtprüfung der Tri-State-Fragen.
Template: `templates/community/vorschlagen.html.twig` — 5-Step Wizard mit Step-Indikator-Leiste, Fehler-Erkennung für automatischen Step-Sprung.
Admin: `AdminSuggestionController` — CRUD + approve (überträgt alle Felder auf neues Restaurant) + reject.
Admin-Template: `templates/admin/suggestion/show.html.twig` — zeigt alle Felder inkl. Ernährung, Zahlung, Sprachen, Kontakt.
Routen: `admin_suggestion_index`, `admin_suggestion_show`, `admin_suggestion_approve`, `admin_suggestion_reject`.
Community-Route: `/community/suggest` (CommunityController).
Migrationen: `Version20260320000000` (Basis), `Version20260324000000` (neue Felder), `Version20260809000000` (bool → Tri-State).

## Dreiwertige Antworten im Vorschlags-Wizard (Ja / Nein / Weiß nicht)
Eine nicht angehakte Checkbox bedeutete früher zweierlei zugleich – „gibt es nicht" und „weiß ich nicht" (der alte Hint sagte „Unbekannte Felder einfach frei lassen", das Admin-UI zeigte `accessibility.no_unknown` = „Nein / unbekannt"). Für eine Barrierefreiheits-Plattform ist der Unterschied wesentlich, deshalb sind die 12 Fragen zu Barrierefreiheit, Ernährung und Zahlung jetzt **Pflichtfragen mit drei Antworten**.

**Enum `App\Enum\TriState`** (`YES`/`NO`/`UNKNOWN`, backed string): `transKey()`, `label()`, `emoji()`, `isYes()`. Stil wie `Language`/`OrderingPlatform`.

**Warum Enum und nicht `?bool`:** Mit `?bool` wäre „Weiß nicht" = `null` – ununterscheidbar von „noch nicht beantwortet". Genau diese Unterscheidung braucht die Pflichtvalidierung. Deshalb: Property `?TriState` (null = unbeantwortet) + `NotNull`-Constraint. Doctrine mappt via `#[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]`.

**Getternamen bleiben** (`isWheelchairAccessible(): ?TriState`, `acceptsCash(): ?TriState`, …) – Symfony PropertyAccess und Twig lösen die Properties über genau diese Namen auf.

**Form** (`RestaurantSuggestionType::addTriState()`): `ChoiceType` mit `expanded: true`, `multiple: false`, `placeholder: false`, `NotNull(message: 'suggestion.answer_required')`.
⚠️ **`'error_bubbling' => false` ist Pflicht** – ein expanded `ChoiceType` ist compound, und dort ist `error_bubbling` per Default `true`. Ohne die Zeile landen alle 12 Fehler am Root-Formular; `form_errors(feld)` bliebe leer und die Step-Erkennung im Template (prüft `form[field].vars.errors`) würde nie greifen.
Keine Vorauswahl entsteht aus `placeholder: false` + Entity-Wert `null` – ein ungültiger Submit liefert dadurch verlässlich 422.

**Rendering:** `templates/partials/_tristate_field.html.twig` (Segmented Control; echte Radios als `sr-only` statt `hidden`, damit Tastatur/Screenreader funktionieren, Fokus über `peer-focus-visible:ring-inset`) und `templates/partials/_tristate_value.html.twig` (Admin-Anzeige: Ja grün, Nein rot, Weiß nicht grau).

**Approve:** `Restaurant` bleibt bewusst bei `bool` – „Weiß nicht" wird als „Nein" übernommen (`$suggestion->isWheelchairAccessible()?->isYes() ?? false`). Ein Durchziehen bis `Restaurant` hätte Repository-Filter, `RestaurantTransformer` (Boolean-Vertrag der iOS-API), 5 Templates und die Fixtures berührt.

**Migration `Version20260809000000`:** `TINYINT(1)` → `VARCHAR(10) NULL` (kein natives `ENUM`, wegen MariaDB 10.5 auf Production), dann Datenmigration `1 → 'yes'`, `0 → 'unknown'` – nicht `'no'`, weil ein leeres Häkchen unter dem alten Hint „unbekannt" bedeutete.

**Übersetzungen:** Block `tristate:` in `messages.{de,en,fr,lb}.yaml` mit **gequoteten** Keys (`"yes"`, `"no"`, `"unknown"`), `community.suggest.step_incomplete`, sowie `suggestion.answer_required` in `validators.{de,en,fr,lb}.yaml`.

## Entity: PartnerWaitlistEntry (Partnerprogramm-Warteliste)
Wartelisten-Anmeldung für das kostenpflichtige Partnerprogramm. Preise und Paketumfang stehen noch nicht fest – die Seite verarbeitet deshalb **keine Zahlung** und legt **keinen Account** an.

Felder: id, restaurantName (VARCHAR 180), contactName (VARCHAR 120), email (VARCHAR 180), phone (VARCHAR 40 nullable), locality (VARCHAR 120), restaurant (ManyToOne Restaurant nullable, SET NULL), message (TEXT nullable), status (VARCHAR 20, enumType), confirmationToken (VARCHAR 64 nullable UNIQUE), confirmedAt (nullable), consentAt (NOT NULL), locale (VARCHAR 5), source (VARCHAR 60 nullable), createdAt, updatedAt.
Enum: `App\Enum\PartnerWaitlistStatus` – `pending`/`confirmed`/`contacted`/`converted`/`declined`, mit `transKey()`, `label()`, `emoji()`, `badgeClasses()` (Muster wie `TriState`).
Repository: `findPendingOlderThan()`, `findFiltered()`, `countByStatus()`, `findOneByConfirmationToken()`.
Migration: `Version20260820000000` – inkl. Kombi-Index `(status, created_at)`, der **auch im Entity-Mapping** deklariert ist (sonst meldet `doctrine:schema:validate` eine Abweichung).

**`updatedAt` per `#[ORM\PreUpdate]`:** Das Projekt kannte bisher keine Lifecycle-Callbacks. Der Wert wird an drei Admin-Stellen geändert; ihn dort von Hand zu pflegen wäre fehleranfällig. Im Konstruktor initialisiert, da `PreUpdate` beim ersten `persist()` nicht feuert.

**Token bleibt nach der Bestätigung stehen** (anders als `User::verificationToken`, der genullt wird): Nur so lässt sich ein zweiter Klick auf denselben Link („bereits bestätigt") von einem unbekannten Token („Link ungültig") unterscheiden. `confirm()` rendert drei Zustände in einer Vorlage und wirft nie eine Exception.

**Honeypot ohne `Blank`-Constraint:** Ein Validierungsfehler würde dem Bot verraten, welches Feld die Falle ist. Der Controller prüft das Feld und liefert bei einem Treffer dieselbe Erfolgsantwort wie sonst – nur ohne zu speichern und ohne Mail. Das Feld ist bewusst **kein** `type="hidden"` (das füllen Bots zuverlässig), sondern per CSS aus dem Blickfeld genommen, mit `aria-hidden="true"` + `tabindex="-1"`.

**Rate-Limiter `partner_waitlist`** (`config/packages/framework.yaml`): 5 Versuche je IP und Stunde, im Controller via `#[Autowire(service: 'limiter.partner_waitlist')]`. ⚠️ Der `when@test`-Override (Limit 10000) ist Pflicht, sonst wird die Test-Suite ab dem sechsten Submit rot.

**Erster Turbo-Stream im Projekt.** `TurboBundle` war bereits registriert, aber ungenutzt. Erfolgsfall: `TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()` → `setRequestFormat()` → `partner/success.stream.html.twig` ersetzt per `action="replace" target="partner-waitlist-form"` nur das Formular. Kein `<turbo-frame>` – „replace" adressiert eine gewöhnliche DOM-id. Der **Fehlerfall braucht keinen Stream**: `AbstractController::render()` setzt für ein submitted-invalides Formular selbst 422 (`vendor/symfony/framework-bundle/Controller/AbstractController.php:473`), und Turbo rendert 4xx-HTML an Ort und Stelle. Auf diesem Pfad darf `setRequestFormat()` **nicht** aufgerufen werden – die Antwort muss `text/html` bleiben.

**`app.contact_email`** (`config/services.yaml`, env `CONTACT_EMAIL`): Empfänger der internen Meldung, wenn eine Anmeldung bestätigt wird. Fallback-Parameter statt leerem Default, weil eine leere Empfängeradresse beim Versand werfen würde. Die interne Mail ist fest auf Deutsch (`trans(..., null, 'de')`), unabhängig von der Sprache des Bestätigenden; die Bestätigungsmail an den Interessenten geht im Submit-Request raus und erbt dadurch automatisch dessen Locale.

## Entity: OrganisationWaitlistEntry (Gemeinden, Unternehmen, Vereine)
Zweite Warteliste neben dem Partnerprogramm, unter `/organisationen`. Drei Typen (`App\Enum\OrganisationType`), die kommerziell grundverschieden sind: `commune` = bezahlter Auftrag, `company` = Sponsoring, `association` = **kein Vertriebskanal** (Beirat, kein Geldfluss in beide Richtungen).

Gemeinsame Felder: type, organisationName, contactName, contactRole, email, phone, website, message, status, confirmationToken, confirmedAt, consentAt, locale, source, createdAt, updatedAt.
Typspezifisch (alle nullable): `communeName`, `estimatedVenues`, `timeframe` (nur commune) · `sponsorshipInterests` JSON (nur company) · `collaborationInterests` JSON (nur association).
Weitere Enums: `OrganisationTimeframe`, `SponsorshipInterest`, `CollaborationInterest`.

**Seitenstruktur:** `/organisationen` ist die Übersicht (Hero, drei Karten, Integritätsblock, Formular mit freier Typwahl). Jede Zielgruppe hat zusätzlich eine **eigene Seite** unter `/organisationen/{slug}` (`OrganisationType::slug()` → `gemeinden`, `unternehmen`, `vereine`; für ASSOCIATION bewusst „vereine", sonst hieße es `/organisationen/organisationen`). Die Inhalte liegen in `templates/organisation/_section_{type}.html.twig` und werden nur dort eingebunden – die Übersicht zeigt bewusst nur Teaser, damit derselbe Text nicht doppelt im Netz steht. Auf den Unterseiten ist der Formulartyp vorgewählt, der Selektor bleibt aber sichtbar (wer falsch gelandet ist, wechselt ohne Umweg). `_integrity.html.twig` steht auf allen vier Seiten.
Repository: `findByType(string $type, ?string $status = null)` (nimmt bewusst Strings aus Query-Parametern und verwirft unbekannte Werte, statt zu werfen), `findFiltered()`, `countByStatus()`, `countByType()`.
Migration: `Version20260820100000`.

**Typabhängige Validierung – zwei Schichten:**
1. `validation_groups` im FormType leitet die Gruppe aus `$type` ab (`['Default', 'commune']` usw.). Die jeweils fremden Felder tragen in den anderen Gruppen `IsNull` bzw. `Count(max: 0)`.
2. `PRE_SUBMIT` baut nur die Felder des **übermittelten** Typs auf. Ein untergeschobenes Fremdfeld ist damit ein unerlaubtes Zusatzfeld → **422**, nicht stilles Ignorieren.

⚠️ **`PRE_SET_DATA` baut dagegen ALLE Blöcke auf.** Das ist die Voraussetzung für die JS-freie Bedienung: Ohne JavaScript sind alle drei Feldgruppen sichtbar und beschriftet, man füllt die passende aus. Wer das auf den aktuellen Typ einschränkt, macht die Seite ohne JavaScript unbenutzbar.

**Choices sind reine Strings, keine Enum-Cases.** Die JSON-Spalten speichern `string[]`; würde man Enum-Cases als `choices` übergeben, fänden Model- und Choice-Werte nicht zueinander (nichts wäre vorausgewählt) und es bräuchte einen Transformer. Der Array-Schlüssel ist der Übersetzungsschlüssel und wird als Label übersetzt (`OrganisationWaitlistType::enumChoices()`).

⚠️ **Bei `expanded: true` ist `choice.vars.data` der Checked-Zustand (bool), nicht der Enum-Case.** Für Emoji/Label im Template deshalb eine Map `value → Case` aus den übergebenen `types` bauen (siehe `organisation/_form.html.twig`).

**Stimulus `organisation_type_controller.ts`** blendet die Blöcke um und setzt `disabled` auf den Feldern der nicht gewählten Typen (nimmt sie aus der Tab-Reihenfolge). Der Wechsel wird in einer `aria-live`-Region angesagt.

## Geteilte Wartelisten-Mechanik (`src/Waitlist/`)
`WaitlistConfirmationService` kapselt Double-Opt-In für **beide** Wartelisten: `register()` (Token → flush → absolute URL → Mail), `confirm()` (liefert `RESULT_CONFIRMED|ALREADY|INVALID`), `notifyTeam()`. Die Reihenfolge Token → flush → Mail ist wesentlich: Scheitert der Transport, ist die Anmeldung trotzdem gespeichert.
`WaitlistEntryInterface` ist der gemeinsame Vertrag beider Entities – Grundlage für den Service und die kombinierte Admin-Liste. `WaitlistRequestHelper::resolveSource()` liest UTM-Quelle bzw. Referrer-Host.
Geteilte Templates: `templates/partials/_waitlist_success.html.twig`, `_waitlist_confirmation.html.twig`.
Gemeinsames `App\Enum\WaitlistStatus` (pending, confirmed, contacted, **qualified**, converted, declined) – `qualified` sitzt zwischen Kontakt und Abschluss, weil bei Gemeinden und Unternehmen regelmäßig eine Vorprüfung dazwischenliegt.

**Admin: `/admin/warteliste` zeigt beide Typen kombiniert.** Der Controller normalisiert Partner- und Organisationseinträge zu einheitlichen Zeilen, damit das Template keine Entity-Fallunterscheidung braucht; nach dem Zusammenführen wird erneut sortiert, sonst stünden erst alle Partner- und danach alle Organisationseinträge. Ein gesetzter Organisationstyp impliziert die Quelle „Organisation".

## Barrierefreies Formular-Partial (`templates/partials/_form_field.html.twig`)
Kapselt Label, Pflicht-/Optional-Hinweis, Widget, Hilfetext und Fehlermeldung samt `aria-describedby` und `aria-invalid`. Löst zugleich den Input-Klassenstring ab, der in `templates/community/vorschlagen.html.twig` **zehnmal** wortgleich steht.

⚠️ **In `attr` unterdrückt nur `false` ein Attribut, nicht `null`.** `'aria-invalid': null` rendert `aria-invalid=""` – Screenreader lesen das als „ungültig". Siehe `form_div_layout.html.twig`, Block `attributes`.

Bewusst ein Include und **kein** registriertes Form-Theme: Ein Theme würde global auf Wizard, Admin und Profil durchschlagen (Regressionsrisiko ohne Nutzen für diese Seite).

Der Fehlercontainer existiert auch im Gutfall (leer), damit `aria-describedby` nie ins Leere zeigt. Fokus ist ein echtes `outline` statt eines `box-shadow`-Rings – Ringe verschwinden im Windows-Kontrastmodus. Deshalb steht dort auch nirgends `outline-none`.

**Fokus ohne JavaScript:** Das erste fehlerhafte Feld bekommt serverseitig `autofocus`. Der Browser fokussiert es beim Rendern der 422-Antwort nativ; Turbo tut nach einem Render dasselbe.

**FAQ ohne `aria-expanded`:** `<details>/<summary>` meldet seinen Zustand selbst an Screenreader. Ein handgeschriebenes `aria-expanded` ließe sich ohne JavaScript nicht aktualisieren und wäre nach dem ersten Klick falsch.

## Open-Startup-Seite (`/open`)

Öffentliche Transparenzseite mit drei Blöcken: **Plattform** (live aus der DB), **Wirkung** (live) und **Finanzen** (manuell im Admin gepflegt). Dazu maschinenlesbare Endpunkte und ein offener Datensatz unter CC BY 4.0.

**Namensraum `App\Open\`** (nicht `App\Service\`) – der Bereich hat genug eigene Begriffe (Punktzahl, Gemeindezuordnung, Snapshot), um zusammenzubleiben. Nicht verwechseln mit `App\Controller\Open\` (locale-freie Daten-Endpunkte).

- `OpenStatsService` — `platform()`, `impact()`, `finance()`, `all()` (gecacht) und `computeAll()` (ungecacht, für den Snapshot). **Alle Rückgaben sind reine Arrays aus Skalaren**: Dieselbe Struktur geht durch Cache, Twig, `/open.json` und den Snapshot – Enums oder Entities darin würden je nach Weg anders behandelt und die vier Ausgaben auseinanderlaufen lassen. `invalidate()` wirft den Cache weg (ruft der Admin nach jeder Finanzänderung auf).
- `CantonResolver` — Freitext aus `Restaurant::$city` → Gemeinde + Kanton. Alle **100 Gemeinden in 12 Kantonen** (Stand nach den Fusionen vom 1. Januar 2024) plus Alias-Tabelle (Stadtteile der Stadt Luxemburg, luxemburgische/deutsche Namen, bekannte Ortschaften). ⚠️ **Gemeinde- und Alias-Index sind getrennt**: Beim Zerlegen zusammengesetzter Angaben („Rue de la Gare, Strassen") dürfen nur echte Gemeindenamen greifen – läge „gare" (Stadtteil) im selben Topf, landete der Eintrag in Luxemburg. Ein unbekannter Wert wird **nicht geraten**, sondern als unzugeordnet ausgewiesen.
- `AccessibilityScore` — 0–10 aus acht gleichgewichteten Merkmalen. Nicht erfasste Maße zählen als nicht erfüllt: Der Wert misst *dokumentierte* Barrierefreiheit.
- `MetricSnapshotService` — `capture(?month, force)`, idempotent, `defaultMonth()` = abgeschlossener **Vormonat** (der Lauf am Ersten hält den Endstand des Vormonats fest; würde er den laufenden Monat schreiben, endete jeder Verlauf mit einem künstlichen Einbruch).

**Entity `FinanceEntry`:** `date` (Spalte `entry_date` – `date` ist in MySQL reserviert), `type` (enum, redundant zu `category->type()`, aber indiziert für die SQL-Aggregation), `category` (enum), `amount` (DECIMAL 10,2, **immer positiv** – die Richtung steckt in `type`), `quantity` (nur Inclusion Boxes), `note`, `createdAt`, `updatedAt` (`#[ORM\PreUpdate]`).
⚠️ **Es gibt keinen `setType()`.** `setCategory()` setzt die Richtung mit und räumt `quantity` weg, wenn die Kategorie keine Menge führt. Eine Ausgabe unter einer Einnahmekategorie wäre in der veröffentlichten Summe nicht mehr als Fehler erkennbar.
⚠️ **`setAmount()` normalisiert auf zwei Nachkommastellen.** `MoneyType` liefert `"42.5"`, die Datenbank `"42.50"` – ohne Normalisierung hinge die Schreibweise davon ab, ob die Entity zwischendurch neu geladen wurde.
Kein Feld für Vertragspartner, Restaurant oder Rechnungsnummer: Was nicht erfasst ist, kann nicht versehentlich veröffentlicht werden.

**Quartalssperre für Einnahmen:** Sichtbar ab dem Tag nach Ablauf des Kalenderquartals, in dem der erste Einnahmeposten liegt. Die Sperre ist **strukturell, nicht kosmetisch** – die Beträge stehen gar nicht erst im Ergebnis-Array von `computeFinance()`. Lägen sie darin und wären nur im Template verborgen, wären sie über `/open.json` abrufbar. Der **Snapshot speichert die Summe trotzdem** (direkt aus dem Repository), sonst stünde für die Anfangsmonate dauerhaft eine 0 in der Historie.

**Entity `MetricSnapshot`:** `capturedFor` (DATE, **unique** → Idempotenz auf DB-Ebene), typisierte Spalten für die Verlaufsgrafiken plus `payload` (JSON) mit der vollständigen Momentaufnahme. Grund für die Entity: Ein aus den heutigen Daten zurückgerechneter Verlauf änderte sich rückwirkend, sobald jemand einen Eintrag bearbeitet – als Beleg gegenüber einem Ministerium wertlos.

**Zeitplan vs. Cron:** `src/Schedule.php` (`#[AsSchedule]`, `RecurringMessage::cron('15 3 1 * *', …, Europe/Luxembourg)`) → `App\Message\CaptureMetricSnapshot` → `CaptureMetricSnapshotHandler`. ⚠️ **Symfony Scheduler braucht einen Worker auf dem Transport `scheduler_default`; der Cron auf Production konsumiert nur `async`** – dort feuert der Zeitplan nicht. Der reale Auslöser ist der **Cron-Eintrag auf `app:metrics:snapshot`** (README → Deployment). Der Befehl unterstützt `--month=YYYY-MM` und `--force`. Zusätzlich gibt es im Admin einen Knopf (`admin_finance_snapshot`), weil eine ausgefallene Historie sonst unbemerkt bliebe und sich nicht rückwirkend erzeugen lässt.

**Cache:** eigener Pool `cache.open_stats` in `config/packages/cache.yaml` (Filesystem, TTL 3600; in `when@test` `cache.adapter.array`). Ein eigener Pool statt `cache.app`, damit `clear()` nach einer Admin-Änderung nicht den halben Anwendungscache mitnimmt.

**Daten-Endpunkte** (`src/Controller/Open/OpenDataController.php`, locale-frei): `/open.json` (Kennzahlen + Verlauf), `/open/dataset.csv`, `/open/dataset.json`. Der Datensatz enthält **keine** E-Mail-Adressen und Telefonnummern – ein Sammelabzug davon wäre eine Adressliste, kein Barrierefreiheits-Datensatz. Kein UTF-8-BOM im CSV (es landete im ersten Spaltennamen jedes gewöhnlichen Parsers).
⚠️ **`AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER` ist Pflicht**, sonst überschreibt Symfonys Session-Listener `public, max-age=3600` mit `private, must-revalidate`, sobald irgendwo im Request eine Session angefasst wurde.

**Templates:** `templates/open/index.html.twig` plus `_metric`, `_bar`, `_histogram`, `_sparkline`.

Aufbau wie die übrigen Außenseiten (Partner, Organisationen): Hero-Band im Verlauf `from-cyan-700 to-purple-800`, danach Sektionsbänder mit wechselnder Fläche (weiß / `bg-gray-50`), Emoji in `bg-cyan-50`-Kacheln, `motion-safe:transition`, `focus:outline-2` und `min-h-[48px]` auf allen Aktionen. Die Zahl der Restaurants ist die **Leitzahl im Hero** – genau eine pro Seite.

**Diagramm-Regeln, die hier bewusst gelten:**
- **Eine Farbe je Serie.** Die frühere Ampel in der Punkteverteilung (grün/cyan/bernstein nach Punktzahl) kodierte die Balkenlänge ein zweites Mal als Farbe. Die Position trägt die Ordnung; die Farbe hätte nichts hinzugefügt und bernstein lag bei 1,49:1 Kontrast.
- **Ausgaben in Cyan, Einnahmen in Purple** – die beiden Marken-Hues, geprüft (ΔE 26,4 normal / 13,6 Deuteranopie, beide > 3:1 gegen Weiß). ⚠️ **Kein Bernstein für Ausgaben:** Das ist eine Warnfarbe und ließe Betriebskosten wie ein Problem aussehen.
- **Balken:** 4 px runde Datenkante, eckig an der Grundlinie; die Spur ist eine hellere Stufe derselben Farbe, nicht neutrales Grau. Balken sind `aria-hidden` – die Zahl daneben trägt die Aussage.
- **Histogramm** (`_histogram`): Säulen, keine gestapelten Querbalken – die Punktzahl ist eine geordnete Skala und nur nebeneinander liest man die Verteilungsform. Die Säulen reichen auf **85 %** statt 100 %; die oberen 15 % sind der Streifen fürs Wertlabel, sonst liefe Label plus Säule oben aus dem Container. Beschriftet werden alle Säulen mit dem Höchstwert.
- **Verlaufslinie** (`_sparkline`): reines SVG ohne Diagramm-Bibliothek. ⚠️ **Keine `<circle>`-Punkte:** `preserveAspectRatio="none"` streckt das Koordinatensystem (richtig für einen Zeitverlauf) und macht aus Kreisen Ellipsen. Der aktuelle Wert steht deshalb als Zahl über der Grafik. Strichstärke 2 px über `vector-effect="non-scaling-stroke"`.
- Jede Grafik hat eine Tabellen-Entsprechung (`<details>` bzw. die Kanton-Tabelle mit `id="canton-coverage"`).

**Zahlen** laufen über `format_number`/`format_currency` (`twig/intl-extra`, für dieses Feature ergänzt), nicht über `number_format` mit fester deutscher Notation – sonst stünde in der englischen Fassung „27,3 %".

**Deltas** liefert `OpenController::deltas()` gegen `MetricSnapshotRepository::findLatest()`. Bezugspunkt ist der Snapshot, nicht „vor 30 Tagen": Nur er ist ein nachprüfbarer Stand. Ohne Snapshot gibt es **keine** Deltas – eine Veränderung gegen einen unbekannten Ausgangswert wäre erfunden.

**Veralterung** der Finanzdaten: Ab 60 Tagen wechselt der „Stand vom"-Hinweis von grauem Kleingedruckten in einen `bg-amber-50`-Kasten. Ein Dashboard, dem man das Alter nicht ansieht, richtet mehr Schaden an als gar keines.

**Druckansicht:** `print:hidden` auf Header, Footer, Bottom-Nav und Cookie-Banner in `templates/base.html.twig` (gilt für alle Seiten, angelegt für den PDF-Export vor Fördergesprächen); der `@media print`-Block in `assets/styles/app.css` nimmt den Verlaufsbändern die Fläche **samt Textfarbe der Nachfahren** (sonst weiß auf weiß), klappt `<details>` auf und verhindert Seitenumbrüche in Diagrammen. `print-color-adjust: exact`, weil die Balkenfarben hier Daten sind.

**Migration:** `Version20260820200000` — `finance_entry`, `metric_snapshot`, `restaurant.door_width_cm`, `restaurant.table_spacing_cm`.

**Restaurant-Maße:** `doorWidthCm`/`tableSpacingCm` (`?int`), Konstanten `Restaurant::MIN_DOOR_WIDTH_CM`/`MIN_TABLE_SPACING_CM` (90, DIN 18040). Helper `hasWideDoors()`/`hasWheelchairTableSpacing()` geben `?bool` zurück – `null` heißt „nicht ausgemessen". In der iOS-API stehen sie im eigenen Block `measurements`, **nicht** in `accessibility`: Dort ist jeder Wert ein Boolean, ein `null` wäre ein Kompatibilitätsbruch.

### Data Fixtures
- Restaurant fixtures: 11 Luxembourg restaurants (`RestaurantFixtures`); each restaurant has accessibility fields (`isWheelchairAccessible`, `hasAccessibleToilet`, `allowsAssistanceDogs`, `hasBrightLighting`, `hasChangingTable`, `hasDisabledParking`), payment method fields (`acceptsCash`, `acceptsCard`, `acceptsPayconiq`), dietary fields (`isVegan`, `isVegetarian`, `isHalal`), verification fields (`isVerified`, `verifiedAt`, `verifiedBy`), ordering options, contact/social media fields (`phone`, `email`, `website`, `instagramUrl`, `facebookUrl`, `tiktokUrl`), and coordinates (`latitude`, `longitude`). 3 restaurants are verified: Pizzeria Bella Vista, Sushi Zen, Green Bowl. 7 restaurants have ordering options: Pizzeria Bella Vista, Sushi Zen, Green Bowl, Burger & Co., Le Jardin Brasserie, Trattoria Roma. Plattformen inkl. Wolt, Wedely, Goosty. All 11 restaurants have varying contact data (not all fields filled for every restaurant). All 11 restaurants have real Luxembourg coordinates. Brasserie du Grund has a `nearbyStopsNote` example.
- User fixtures: 3 test users (`UserFixtures`) with hashed passwords via Symfony PasswordHasher
  - `admin@endlech.lu` / `admin123` — ROLE_ADMIN, verified
  - `user@endlech.lu` / `user123` — ROLE_USER, verified
  - `unverified@endlech.lu` / `unverified123` — ROLE_USER, unverified
- Finance fixtures: `FinanceEntryFixtures` — zwölf Monate laufende Kosten, Domain, Apple Developer, zwei Inclusion-Box-Materiallieferungen mit Stückzahl, dazu zwei Einnahmen **im laufenden Quartal**. Letzteres mit Absicht: So greift die Quartalssperre lokal und man sieht, wie die Seite ohne Einnahmenblock aussieht.
- Restaurant fixtures tragen zusätzlich `doorWidthCm`/`tableSpacingCm`; vier der elf Häuser haben bewusst **kein** Maß (deckt den Fall „nicht ausgemessen" ab), zwei liegen dokumentiert unter 90 cm.
- Fixture references available: `UserFixtures::REFERENCE_ADMIN`, `REFERENCE_USER`, `REFERENCE_UNVERIFIED`

### Database
- MySQL 8.0 via Docker Compose (`compose.yaml`) on port 3306
- Migrations namespace: `DoctrineMigrations` (not `App\Migrations`)
- Migration path: `migrations/` directory
- Connection string format: `mysql://root:root@127.0.0.1:3306/endlech?serverVersion=8.0&charset=utf8mb4`
- Set `DATABASE_URL` in `.env.local`

### Frontend
- Entry point: `assets/app.ts` (compiled by Webpack Encore to `public/build/`)
- Stimulus controllers go in `assets/controllers/` as `.ts` files and are auto-discovered
- TypeScript: `tsconfig.json` with `strict: true`, `ES2020` target, `noEmit: true` (type-checking only)
- Webpack Encore uses `enableTypeScriptLoader()` with `transpileOnly: true` (ts-loader)
- ESLint: Flat config (`eslint.config.mjs`) with `typescript-eslint`
- Tailwind CSS v4 uses PostCSS plugin (`postcss.config.mjs`)
- Templates use Tailwind utility classes throughout
- CSRF protection uses double-submit cookie pattern (see `csrf_protection_controller.ts`)

### Webpack Encore Configuration
- Output: `public/build/`
- Features: PostCSS, Stimulus bridge, TypeScript (ts-loader), code splitting, source maps (dev), filename hashing (prod)
- Config: `webpack.config.js`

## Code Style

- **PHP:** 4-space indentation, PSR-4 autoloading, enforced by PHP-CS-Fixer (`make fix`)
- **YAML:** 2-space indentation
- **TypeScript/JS/CSS:** 4-space indentation
- **Line endings:** LF
- **Encoding:** UTF-8
- **Trailing whitespace:** trimmed (except `.md` files)

See `.editorconfig` for full formatting rules.

## Docker Services

Defined in `compose.yaml` and `compose.override.yaml`:

| Service   | Image              | Ports          | Purpose              |
|-----------|--------------------|----------------|----------------------|
| database  | mysql:8.0          | 3306           | MySQL database       |
| mailer    | axllent/mailpit    | 1025, 8025     | Dev email (SMTP+UI)  |

## Environment Files

| File            | Purpose                                    |
|-----------------|--------------------------------------------|
| `.env`          | Default config (committed, non-secret)     |
| `.env.dev`      | Dev-specific overrides                     |
| `.env.test`     | Test environment (`APP_ENV=test`)          |
| `.env.local`    | Local overrides (gitignored, secrets here) |

`APP_SECRET` must be set in `.env.local` for production. The `.env.dev` file provides a dev-only secret.

### Email / Mailer
- **Production:** Brevo API via `symfony/brevo-mailer` – set `MAILER_DSN=brevo+api://YOUR_API_KEY@default` in `.env.local`
- **Development:** Mailpit via `smtp://localhost:1025` (configured in `.env.dev`), UI at `http://localhost:8025`
- **Default:** `null://null` (emails discarded) in `.env`
- **Sender:** Configured globally via `MAILER_SENDER_ADDRESS` and `MAILER_SENDER_NAME` env vars, applied in `config/packages/mailer.yaml`
- **Async:** Emails routed to async Doctrine transport via Messenger (see `config/packages/messenger.yaml`)
- **Templates:** All emails extend `email/base.html.twig` for consistent Endlech.lu branding
- **Error handling:** Controllers catch `TransportExceptionInterface` and show user-friendly flash messages

## Versioning

The project uses **CalVer** (Calendar Versioning): `vYYYY.MM.DD` (e.g., `v2026.01.13`). See `CHANGELOG.md`.

**Mehrere Releases am selben Tag zählen mit einem Punkt hoch:** `v2026.08.29`,
`v2026.08.29.1`, `v2026.08.29.2`. Die frühere Buchstabenform (`2026.03.08b` bis `e`)
ist **Historie und bleibt unangetastet** – sie wird nicht rückwirkend umgeschrieben,
aber auch nicht fortgeführt. Die Punktform sortiert sich richtig (nach `z` wäre bei
Buchstaben Schluss, und `b` liest sich nicht als „der erste Nachtrag"). Der erste
Release eines Tages trägt **kein** Suffix.

**Bei jedem Release müssen vier Stellen mitgezogen werden** – sie liegen auseinander und wurden schon mehrfach vergessen (das README-Badge stand zwei Releases lang auf einer alten Version, der Footer eines):

1. `CHANGELOG.md` – `[Unreleased]`-Abschnitt zu `[YYYY.MM.DD] – Titel` schließen **und** das Version-Badge in Zeile 5.
2. `README.md` – Version-Badge (Format mit `v`-Präfix).
3. `config/services.yaml` – Parameter `app.version`; wird über `twig.yaml` als `app_version` global gesetzt und in `templates/base.html.twig` im Footer gerendert. **Das ist die einzige Stelle, die Besucher sehen.**
4. Git-Tag `vYYYY.MM.DD` auf dem Release-Commit in `dev`, danach GitHub-Release (`gh release create`).

Konvention: Release-Commit direkt auf `dev` mit dem Titel `Release vYYYY.MM.DD – Titel`, Tag darauf, anschließend Merge `dev` → `production` (= Deploy).

## CI

GitHub-Actions-Workflow `.github/workflows/ci.yml` (Trigger: **nur** `workflow_dispatch` – die automatischen Push-/PR-Trigger sind bewusst abgeschaltet, Lauf per „Run workflow" bzw. `gh workflow run ci.yml`):
- **Job `tests`** – PHP 8.4 (`shivammathur/setup-php`, Extensions inkl. `pdo_mysql`, `gd`, `intl`), MySQL-8.0-Service, Composer-Install (gecacht, `--no-scripts`), JWT-Keypair (`lexik:jwt:generate-keypair --env=test --skip-if-exists`), Test-DB (create/migrate/fixtures), dann `php bin/phpunit`.
- **Job `frontend`** – Node 20, `npm ci`, `npm run typecheck` (`tsc --noEmit`), `npm run lint` (ESLint).

Das `.github/`-Verzeichnis enthält außerdem Issue-Templates (Bug Reports, Feature Requests, Tasks).

## Deployment (CD)

**Ein Merge nach `production` ist der Deploy.** Kein Deployer, keine atomic releases – der Runner öffnet eine SSH-Sitzung, der Server aktualisiert sich selbst. Zwei Dateien im Repo, drei Secrets (`SSH_PRIVATE_KEY`, `APP_USER`, `APP_HOST`), Ziel ist Cloudways (`~/public_html`).

**`.github/workflows/cd.yml`** – Trigger `push` auf `production` + `workflow_dispatch`; `concurrency: deploy-production` (zwei parallele Deploys würden sich den Arbeitsbaum umschreiben). Zwei Jobs:
- **`verify-assets`** – baut `public/build` neu und vergleicht mit dem committeten Stand. **Reihenfolge ist Pflicht: `composer install` VOR `npm ci`/`npm run build`**, weil `node_modules/@symfony/ux-turbo` ein Symlink nach `vendor/symfony/ux-turbo/assets` ist (`file:`-Dependency in `package.json`) – ohne `vendor/` scheitert Webpack am toten Link. Verglichen wird mit `git status --porcelain public/build`, **nicht** `git diff --exit-code`: bei aktivem `cleanupOutputBeforeBuild()` erscheint ein geänderter Hash als *untracked* Datei, die `git diff` nie meldet.
- **`deploy`** (`needs: verify-assets`) – Sparse-Checkout nur von `.github/deploy.sh`, `webfactory/ssh-agent`, dann `ssh … 'bash -s' < .github/deploy.sh`.

**`.github/deploy.sh`** – die gesamte Logik, versioniert und lokal mit `bash -n` prüfbar. `set -euo pipefail` (ohne die Zeile zählt nur der Exit-Code des letzten Befehls – eine gescheiterte Migration liefe durch und der Lauf würde grün), dann `git fetch` + `git reset --hard origin/production` + `git clean -fd`, `composer install --no-dev --optimize-autoloader`, `doctrine:migrations:migrate`, `cache:clear`.

**Production-Umgebung (verifiziert am 2026-08-06):** Cloudways, SSH-Login `endlech` → Systembenutzer `nrzwptqsvx` (Application-User, dem auch `public_html` gehört – der richtige, nicht der Master-Login). Deploy-Pfad `$HOME/public_html`, Webroot zeigt auf dessen `public/`. PHP 8.4.22, Composer 2.10.1, git 2.30.2. `~/.ssh/` gehört **root** – deshalb liegt der Deploy-Key in `.git/deploy_key` (dort greift auch `git clean` nicht hin) und `known_hosts` wird per `ssh-keyscan` daneben geschrieben; der CI-Key des Runners liegt in `~/.openssh/authorized_keys` (Cloudways-Pfad, gehört dem App-User). `COMPOSER_CACHE_DIR` wird im Skript auf `$HOME/tmp/composer-cache` gesetzt, weil Composers Default `~/.cache` hier nicht beschreibbar ist – sonst läuft jeder Deploy cache-los.

### Messenger-Worker (Umstellung von `sync://` auf die Queue)

**Stand 2026-08-30: die Queue ist in Betrieb.** In der `.env.local` auf dem Server
steht **kein** `MESSENGER_TRANSPORT_DSN` — damit greift der Vorgabewert
`doctrine://default?auto_setup=0` aus `.env`, und der Worker-Cron konsumiert ihn
jede Minute. Der frühere Hinweis „Umstellung steht noch aus" war überholt.

⚠️ **Der Ausfall ist hier lautlos.** Läuft der Worker nicht oder scheitert er beim
Start, stapeln sich die Nachrichten in `messenger_messages`, während die App weiter
„erfolgreich" meldet – niemand bekommt mehr eine Bestätigungsmail (Registrierung,
Double-Opt-In beider Wartelisten, E-Mail-Wechsel), und es fällt erst bei einer
Beschwerde auf. **Der Zustand gehört gemessen, nicht angenommen:**

```bash
php bin/console messenger:stats --env=prod        # Rückstau in async
php bin/console messenger:failed:show --env=prod  # nach 3 Versuchen aufgegeben
```

Eine dreistellige Zahl in `async` heißt: der Worker läuft nicht.
`messenger:failed:retry` schickt Liegengebliebenes nach.

⚠️ **Wer je wieder `sync://` setzt, nimmt der Sperre unten die Wirkung** und schaltet
Retry und `failed`-Transport ab. Beim Zurückstellen gilt dieselbe Reihenfolge wie bei
der Umstellung — und immer `cache:clear --env=prod`, sonst hält der kompilierte
Container den alten DSN.

Die Tabelle ist bereits da: `Version20260113160019` legt `messenger_messages` an,
und ihr Schema deckt sich mit dem, was `symfony/doctrine-messenger` erwartet
(Kombi-Index `(queue_name, available_at, delivered_at, id)`). `auto_setup=0` ist
deshalb unkritisch.

**Der Worker läuft als Cron, nicht unter Supervisor** – jede Minute, mit
`--time-limit=55`. Das ist hier die bessere Wahl, weil sich der Worker damit selbst
ablöst und nach jedem Deploy von allein mit neuem Code und frischem Container
startet. Ein `pkill` im Deploy entfällt dadurch, und das ist ein Gewinn: Auf
derselben Maschine läuft ein `messenger:consume` einer **fremden** Application unter
anderem Systembenutzer, ein Muster ohne `-u`-Filter wäre dort eine Fußangel.

⚠️ **Der Cron muss unter demselben Systembenutzer laufen wie PHP-FPM**
(`nrzwptqsvx`, nicht der Master-Login, unter dem das Cloudways-Panel seine Cron-Jobs
anlegt). Ein Worker als Master schreibt `var/log` und `var/cache` mit fremdem
Eigentümer voll, bis der Webserver dort auf „Permission denied" läuft — ein 500er,
dessen Ursache man an der falschen Stelle sucht. Vor dem Einrichten prüfen, als wer
das Panel den Job startet:

```
* * * * * id > /home/master/applications/nrzwptqsvx/public_html/var/cron-whoami.txt 2>&1
```

Steht dort `master`, gehört der Job stattdessen per `crontab -e` in die SSH-Sitzung
des App-Users. Der bestehende `app:metrics:snapshot`-Cron hat dasselbe Thema, fällt
dort aber kaum auf, weil er einmal im Monat läuft.

Die **Sperrdatei** ist davon bewusst unabhängig: `deploy.sh` öffnet sie lesend
(`exec 9<`), weil `flock` unabhängig vom Zugriffsmodus sperrt. Ein abweichender
Eigentümer blockiert damit nicht den Deploy.

**Die Sperre in `deploy.sh`:** Das Skript nimmt sich `var/worker.lock` per `flock`,
bevor `git reset` läuft, und hält den Deskriptor bis zum Ende. Ein laufender Worker
wird abgewartet (bis 90 s), jeder Cron-Start während des Deploys springt per
`flock -n` ab. Ohne das trifft der Worker mitten im `git reset` auf halb neue
Dateien – derselbe gemischte Zustand wie bei ENDLECH-5, nur im Hintergrund und ohne
Wartungsseite davor. Die Datei liegt aus demselben Grund unter `var/` wie das
Wartungsflag: gitignoriert, `git clean -fd` fasst sie nicht an.

**Was die Queue mitbringt:** Retry (`max_retries: 3`) und den `failed`-Transport,
also `messenger:failed:show`/`:retry` statt endgültig verlorener Mails. Und
Sichtbarkeit – ein im Worker gescheiterter Versand landet als `error` im Monolog-Kanal
`messenger` und damit über `sentry_logs` in Sentry. Bei `sync://` fingen zwölf
`catch (TransportExceptionInterface)`-Blöcke in acht Dateien den Fehler ab, **ohne ihn
zu loggen**: Der Nutzer sah eine Warnung, der Betreiber erfuhr nichts.

⚠️ **Diese zwölf Blöcke sind seit der Umstellung toter Code** – ein Dispatch-Fehler ist
eine Messenger-Exception, keine Mailer-`TransportExceptionInterface`. Sie schaden
nicht, täuschen aber eine Absicherung vor, die an dieser Stelle nichts mehr auffängt;
wer dort einen Fehlerfall prüfen will, prüft ihn im Worker. Aufräumen ist ein eigener
Auftrag, kein Nebenbei-Handgriff.

**Production-DB ist MariaDB 10.5**, lokal und in der CI läuft dagegen MySQL 8.0. Da `deploy.sh` bei jedem Lauf `doctrine:migrations:migrate` ausführt, müssen neue Migrationen gegen MariaDB 10.5 lauffähig sein – MySQL-8-only-Syntax (z. B. `CHECK`-Constraints mit JSON-Funktionen, Window-Functions in DDL) schlägt sonst erst auf Production fehl.

**Konsequenzen für die tägliche Arbeit:**
- **Änderung unter `assets/` → `npm run build` ausführen und `public/build` mitcommitten**, sonst blockt `verify-assets` den Deploy. Der Build ist deterministisch (verifiziert), ein Neubau ohne Quelltextänderung erzeugt keine Diffs.
- **`.nvmrc` ist die gemeinsame Node-Version** für lokale Entwicklung, `ci.yml` und `cd.yml` (beide Workflows nutzen `node-version-file: '.nvmrc'`). Da der committete `public/build` aus der lokalen Node-Version stammt, würde eine abweichende Runner-Version den Vergleich potenziell grundlos rot färben. Wer lokal die Node-Version wechselt, aktualisiert `.nvmrc` mit.
- `git clean -fd` läuft **ohne** `-x`: alles Gitignorierte überlebt (`.env.local`, `config/jwt/*.pem`, `public/uploads/{avatars,restaurants}`, `var/`, `vendor/`, `public/bundles/`). ⚠️ **`public/uploads/team/` ist per `!`-Regel aus `.gitignore` ausgenommen** – Dateien dort, die nicht committet sind, löscht der Deploy.
- Kein Null-Downtime, aber auch keine 500er: Zwischen `git reset` und `cache:clear` läuft die App gemischt (neue Dateien, alter Container) — **dieses Fenster deckt seit ENDLECH-5 eine Wartungsseite ab**, siehe unten.
- Rollback = Revert-Commit auf `production`; der nächste Lauf bringt die passenden Assets automatisch mit, weil sie im selben Commit stecken.
- PHPUnit ist **kein** Deploy-Gate (passend zur manuellen CI); zuschaltbar über `needs: [verify-assets, tests]`.

Server-Setup (einmalig) und die Waisen-Inventur vor dem ersten Lauf: siehe README → „🚢 Deployment".

### Wartungsfenster während des Deploys (ENDLECH-5)

⚠️ **Ab `git reset` liegen neue PHP-Dateien neben dem kompilierten Container des
Vorgänger-Releases.** Ruft der alte Container einen geänderten Konstruktor auf, endet
**jede** Anfrage in einem 500er — nicht nur die betroffene Route, wenn die Klasse an
`kernel.request` hängt. Am 29.08.2026 traf es `ApiRateLimitSubscriber`: Der Container
von v2026.08.09 übergab zwei Argumente, die neue Datei verlangte seit BF-25 drei
(`api_register`). Der gemischte Zustand endet erst mit `cache:clear`, gemessen rund
35 Sekunden später.

Der Beleg im Sentry-Event ist das Feld `release`. Es kommt aus `%app.version%` und
damit **aus dem kompilierten Container** — steht dort eine ältere Version als die im
Repo, ist genau dieses Fenster die Ursache und nicht der Code.

**Deshalb:**
- `deploy.sh` legt `var/maintenance` an, **bevor** `git reset` läuft, und entfernt die
  Datei im `EXIT`-Trap. `git fetch` steht davor — es ändert den Arbeitsbaum noch nicht.
- ⚠️ **Die Flag-Datei liegt unter `var/`, weil das gitignoriert ist.** `git clean -fd`
  läuft ohne `-x` und fasst sie deshalb nicht an; `cache:clear` räumt nur `var/cache`.
  Unter `public/` läge sie im Repo-Bereich und wäre nach dem `clean` weg.
- ⚠️ **Die Prüfung in `public/index.php` steht VOR `require vendor/autoload_runtime.php`.**
  Sie darf weder Container noch Autoloader brauchen — genau die können während des
  Deploys unvollständig sein. Antwort: 503 + `Retry-After` + `Cache-Control: no-store`
  (sonst hält der Varnish des Hostings die Wartungsseite über den Deploy hinaus fest).
- ⚠️ **Bei einem Abbruch bleibt die Wartungsseite bewusst stehen.** Der Arbeitsbaum ist
  dann neu, der Container alt oder die Migration halb durch — eine 503 ist dort besser
  als der 500er, den dieser Zustand liefert. Das Signal ist der rote Actions-Lauf
  (`::error::`-Annotation); danach von Hand
  `ssh <user>@<host> 'rm -f ~/public_html/var/maintenance'`.
- `public/maintenance.html` ist eigenständiges HTML mit Inline-CSS wie `offline.html` —
  Encore-Assets sind an dieser Stelle nicht verlässlich erreichbar.

Der Service Worker braucht dafür **nichts**: Navigationen laufen network-first ohne
Cache-Schreiben, alle anderen Wege cachen nur bei `response.ok`. Eine 503 landet
strukturell in keinem Cache.

## Fehler-Tracking (Sentry)

`sentry/sentry-symfony` 5.x meldet uncaught Exceptions und Monolog-Records ab `WARNING` an ein Sentry-Projekt in der **EU-Region** (`ingest.de.sentry.io`, Frankfurt).

**Nur `prod`.** `config/bundles.php` registriert `SentryBundle` mit `['prod' => true]` – in dev und test existiert die Extension nicht (`debug:config sentry` schlägt dort bewusst fehl). Damit kann weder lokale Entwicklung noch die Test-Suite Daten senden, und `ci.yml` braucht keine Anpassung. Zum lokalen Testen: `php bin/console sentry:test --env=prod` mit temporärem DSN in `.env.local`.

**DSN.** `SENTRY_DSN` steht leer in `.env` (committed) und wird **ausschließlich in der `.env.local` auf dem Server** gesetzt – das Repo ist öffentlich, ein committeter DSN erlaubte Fremden das Einschleusen von Events. Leerer Wert = Sentry lautlos inaktiv (dasselbe Muster wie `MOBILITEIT_API_KEY`); der leere Default in `.env` verhindert zugleich, dass `%env(SENTRY_DSN)%` den Container-Build sprengt. ⚠️ Der Eintrag muss **vor** dem Merge nach `production` auf dem Server stehen, sonst deployt es grün und Sentry bleibt still.

**`config/packages/sentry.yaml`** (alles unter `when@prod`):
- `release: 'endlech@%app.version%'` – hängt am CalVer-Parameter aus `config/services.yaml` und zieht bei jedem Release automatisch mit (kein fünfter Handgriff in der Release-Checkliste).
- `send_default_pii: false` – keine IP-Adressen, Cookies, Request-Header oder Nutzerdaten.
- `enable_logs: true` – **reicht allein nicht**; der Handler muss zusätzlich in `monolog.yaml` registriert sein (das Sentry-Onboarding-Snippet verschweigt das).
- `ignore_exceptions` filtert 404/405/403/429 **und seit ENDLECH-6 auch 400** (`BadRequestHttpException`) – ohne das hätte Bot-Traffic die Quota geflutet. Der 400er kam von einem Scanner, der `/login` per POST ohne Felder anpokte; Symfonys `FormLoginAuthenticator` wirft dort korrekt, aber das ist ein kaputter Client und kein Anwendungsfehler. ⚠️ **Vor dem Aufnehmen einer Exception hier prüfen, ob das Projekt sie selbst wirft** – `BadRequestHttpException` tut es nirgends (die eigenen 400er sind `JsonResponse`-Rückgaben in Admin- und API-Controllern und erreichen Sentry nie). Matching läuft über `is_a($class, $pattern, true)`, greift also auch auf Subklassen und Interfaces.

**Monolog.** `config/packages/monolog.yaml` hat im `when@prod`-Block den Handler `sentry_logs` (`type: service`, `id: Sentry\SentryBundle\Monolog\LogsHandler`) neben `main`/`console`/`deprecation`. Der Service wird in `sentry.yaml` mit `Monolog\Level::Warning` definiert (Monolog 3 – nicht die deprecatete Konstante `Monolog\Logger::WARNING`). Bewusst `LogsHandler` (schickt Sentry-*Logs*) statt `Sentry\Monolog\Handler` (schickt *Issues*) – deshalb bleibt `register_error_listener` aktiv, ohne dass Exceptions doppelt gemeldet werden.

**Kein Eingriff am `ApiExceptionSubscriber` nötig:** Sentrys `ErrorListener` hängt mit Priorität **128** an `kernel.exception`, unser Subscriber mit **10**. Sentry sieht `/api/v1`-Exceptions also, bevor `setResponse()` sie in JSON verwandelt.

**`zend.exception_ignore_args` bleibt auf dem PHP-Default `On`** – entgegen der Sentry-Empfehlung. `Off` würde Funktionsargumente in Stacktraces schreiben, also potenziell Passwörter aus `AuthController`. Das passt nicht zu `send_default_pii: false`.

**Flex-Recipe.** Liegt nur in `recipes-contrib`; wegen `extra.symfony.allow-contrib: false` wird es übersprungen (auch mit `SYMFONY_ALLOW_CONTRIB=1`). Bundle-Eintrag, `sentry.yaml` und der `.env`-Block sind daher von Hand angelegt.

## Key Files Reference

| File                  | Purpose                                    |
|-----------------------|--------------------------------------------|
| `composer.json`       | PHP dependencies and autoloading           |
| `package.json`        | NPM dev dependencies and build scripts     |
| `webpack.config.js`   | Webpack Encore build configuration         |
| `postcss.config.mjs`  | PostCSS with Tailwind CSS plugin           |
| `phpunit.dist.xml`    | PHPUnit test configuration                 |
| `compose.yaml`        | Docker services (MySQL 8.0, Mailpit)       |
| `Makefile`            | Development workflow commands               |
| `tsconfig.json`       | TypeScript compiler configuration          |
| `eslint.config.mjs`   | ESLint flat config (TypeScript rules)      |
| `.nvmrc`              | Node-Version für lokal + beide Workflows   |
| `.github/deploy.sh`   | Deploy-Logik (läuft per SSH auf dem Server)|
| `importmap.php`       | Symfony AssetMapper module mapping         |
| `.editorconfig`       | Editor formatting rules                    |
| `docs/`               | Datenmodell-, Design-System- und PRD-Referenz |
