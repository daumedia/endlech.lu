# CLAUDE.md

Guide for AI assistants working on the endlech.lu codebase.

## Project Overview

**Endlech.lu** is an open platform to find and rate accessible restaurants in Luxembourg. Built with Symfony 8 (PHP 8.4+), Tailwind CSS v4, and Hotwire (Stimulus + Turbo). The platform is live with a restaurant listing page backed by a real database.

The UI language is German/Luxembourgish. The codebase comments (Makefile, templates) use German.

## Tech Stack

- **Backend:** PHP 8.4+, Symfony 8.0.*
- **Database:** MySQL 8.0 (via Docker)
- **ORM:** Doctrine 3.6 with migrations
- **Templates:** Twig
- **CSS:** Tailwind CSS v4.1 via PostCSS
- **JS/TS:** TypeScript, Hotwire (Stimulus 3.x + Turbo 7/8)
- **Build:** Webpack Encore 5.1
- **Testing:** PHPUnit 12.5
- **Email:** Brevo (formerly Sendinblue) via `symfony/brevo-mailer` (production)
- **Dev Mail:** Mailpit (SMTP on port 1025, UI on port 8025)

## Project Structure

```
src/
├── Controller/          # Route controllers (attribute-based routing)
├── DataFixtures/        # Doctrine fixtures (restaurant data + user test data)
├── DTO/                 # Data Transfer Objects (NearbyStop)
├── Entity/              # Doctrine entities (User, Restaurant, RestaurantImage, OrderingOption, Cuisine)
├── Enum/                # PHP Backed Enums (Language, OrderingPlatform)
├── Repository/          # Doctrine repositories (UserRepository, RestaurantRepository, CuisineRepository)
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
- **`tests/Unit/`** (`extends TestCase`, keine DB): Services mit mockbaren Deps (`PublicTransportService` via `MockHttpClient`, `AdminStatsService`), Transformer (`RestaurantTransformer`/`UserTransformer` – echter `AssetUrlBuilder` mit Base-URL, da `final`), Enums, Twig-Extension, `OpeningHoursService`, `ApiExceptionSubscriber`.
- **`tests/Integration/`** (`extends KernelTestCase`, Container + DB, DAMA-isoliert): Repositories (v. a. `RestaurantRepositoryTest` – alle `findPaginated`-Filter und `sort`-Reihenfolgen), `ImageUploadService`/`AvatarUploadService` (Temp-Dir-Isolation via `sys_get_temp_dir`).
- **`tests/Functional/`** (`extends AbstractWebTestCase`/`WebTestCase`, HTTP/Forms/Auth): Web- und Admin-Controller + `/api/v1`. Basisklasse `tests/AbstractWebTestCase.php` (bleibt im `tests/`-Root, Namespace `App\Tests`) mit `loginAs()`, `formWithField()`, `formByAction()`, `csrfTokenFrom()`. Web-Routen tragen den Locale-Prefix → `self::LOCALE` (`/de`) vor jeden Pfad.

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

**Wichtig:** Die `/api/v1/`-Routen sind **locale-frei** (kein `/{_locale}`-Prefix). `config/routes.yaml` importiert `src/Controller/Api/V1/` in einem eigenen Block und `exclude`t es am `controllers`-Loader. Der ältere `CuisineApiController` (`/api/cuisines`) liegt weiterhin UNTER `/{_locale}` (also real `/{_locale}/api/cuisines`).

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
**Controller** (`src/Controller/Api/V1/`): `AuthController` (`login` = json_login-Stub, Rumpf nie erreicht; `register` repliziert den Web-Flow inkl. E-Mail-Verifikation, gibt KEIN Token zurück; **Anti-User-Enumeration**: identische generische 201-Antwort, egal ob die E-Mail existiert – bestehende Adressen erhalten einen Hinweis-Mail statt einer Bestätigung, Passwort wird in beiden Zweigen gehasht (Timing)), `RestaurantApiController` (`index` mit Envelope `{data, meta:{page,limit,total,totalPages,sort}}` + Filter-Mapping auf `RestaurantRepository::findPaginated`; `show`; `images`; `create` mit `submittedBy`=current user, `isVerified=false`), `MeController` (`me`, `submissions` via `findBySubmitter`; `#[IsGranted('IS_AUTHENTICATED_FULLY')]`).
**Transformer** (`src/Api/`): `RestaurantTransformer` (`list()`/`detail()`/`image()`, injiziert `OpeningHoursService` für `isOpenNow` + `nextOpeningTime`, Öffnungszeiten gruppiert nach Tag 1–7) und `UserTransformer` (`profile()`). Bild-/Avatar-URLs sind **absolut** (für native iOS-Clients) via `AssetUrlBuilder` (`src/Api/`): nutzt Scheme+Host des aktuellen Requests, optionaler Env-Override `APP_API_BASE_URL` (Proxy/CDN). Koordinaten im `create`-Endpoint werden auf Dezimalformat + Bereich ±90/±180 geprüft (422 statt DBAL-500). Bewusst explizit statt Serializer-Groups, weil die Entity untypische Getter hat (`acceptsCash()`, `isWheelchairAccessible()`, `hasAccessibleToilet()`), die der ObjectNormalizer nicht zuverlässig erkennt. `password`/Token werden strukturell NIE ausgegeben.
**Security** (`config/packages/security.yaml`): zwei stateless Firewalls VOR `main`: `api_login` (`^/api/v1/auth/login$`, json_login mit `username_path: email`, Lexik success/failure-Handler) und `api` (`^/api/v1`, `jwt: ~`). `access_control`: `auth` + `GET restaurants` = PUBLIC; `me` + `POST restaurants` = IS_AUTHENTICATED_FULLY. Web-Regeln (`^/[a-z]{2}/...`) bleiben kollisionsfrei.
**Fehler/CORS/Rate-Limit** (`src/EventSubscriber/`): `ApiExceptionSubscriber` (nur `^/api/v1`, JSON `{error:{code,message}}`; **anonyme** AccessDenied → 401, sonst 403; übernimmt Header der HTTP-Exception, z. B. `Retry-After`/`WWW-Authenticate`; im Debug-Modus Exception-Detail bei 500). `ApiRateLimitSubscriber` (IP-basiert, Login strenger; bei Limit `TooManyRequestsHttpException` → 429 inkl. `Retry-After`-Sekunden aus `RateLimit::getRetryAfter()`). Limiter in `config/packages/framework.yaml` (`api_anonymous` sliding_window 100/min, `api_login` fixed_window 5/min; in `when@test` auf 10000 gelockert). CORS in `config/packages/nelmio_cors.yaml` nur `^/api/v1/`. `bool $debug`-Bind in `config/services.yaml`.
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
Template: `templates/partials/_nearby_stops.html.twig` — Haltestellen-Karten mit Bus/Tram-Icons, Linien-Badges, Distanz.
Form: `latitude` (NumberType, Range -90/90), `longitude` (NumberType, Range -180/180), `nearbyStopsNote` (TextType, max 1000).
Admin-Fieldset: "Standort & Nahverkehr" in `_form.html.twig`.
Migration: `Version20260322000000`.
Fixtures: Alle 11 Restaurants mit echten Luxemburg-Koordinaten. Brasserie du Grund mit Beispiel-`nearbyStopsNote`.

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

### Data Fixtures
- Restaurant fixtures: 11 Luxembourg restaurants (`RestaurantFixtures`); each restaurant has accessibility fields (`isWheelchairAccessible`, `hasAccessibleToilet`, `allowsAssistanceDogs`, `hasBrightLighting`, `hasChangingTable`, `hasDisabledParking`), payment method fields (`acceptsCash`, `acceptsCard`, `acceptsPayconiq`), dietary fields (`isVegan`, `isVegetarian`, `isHalal`), verification fields (`isVerified`, `verifiedAt`, `verifiedBy`), ordering options, contact/social media fields (`phone`, `email`, `website`, `instagramUrl`, `facebookUrl`, `tiktokUrl`), and coordinates (`latitude`, `longitude`). 3 restaurants are verified: Pizzeria Bella Vista, Sushi Zen, Green Bowl. 7 restaurants have ordering options: Pizzeria Bella Vista, Sushi Zen, Green Bowl, Burger & Co., Le Jardin Brasserie, Trattoria Roma. Plattformen inkl. Wolt, Wedely, Goosty. All 11 restaurants have varying contact data (not all fields filled for every restaurant). All 11 restaurants have real Luxembourg coordinates. Brasserie du Grund has a `nearbyStopsNote` example.
- User fixtures: 3 test users (`UserFixtures`) with hashed passwords via Symfony PasswordHasher
  - `admin@endlech.lu` / `admin123` — ROLE_ADMIN, verified
  - `user@endlech.lu` / `user123` — ROLE_USER, verified
  - `unverified@endlech.lu` / `unverified123` — ROLE_USER, unverified
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

**Kein Messenger-Worker:** Production läuft mit `MESSENGER_TRANSPORT_DSN=sync://` (in `.env.local`, überschreibt das `doctrine://` aus `.env`) – E-Mails werden synchron im Request versendet, es gibt weder Queue noch Worker. `deploy.sh` enthält deshalb **kein** `pkill`. Auf derselben Maschine läuft ein `messenger:consume` einer fremden Application unter anderem Systembenutzer; ein pkill-Muster ohne `-u`-Filter wäre dort eine Fußangel.

**Production-DB ist MariaDB 10.5**, lokal und in der CI läuft dagegen MySQL 8.0. Da `deploy.sh` bei jedem Lauf `doctrine:migrations:migrate` ausführt, müssen neue Migrationen gegen MariaDB 10.5 lauffähig sein – MySQL-8-only-Syntax (z. B. `CHECK`-Constraints mit JSON-Funktionen, Window-Functions in DDL) schlägt sonst erst auf Production fehl.

**Konsequenzen für die tägliche Arbeit:**
- **Änderung unter `assets/` → `npm run build` ausführen und `public/build` mitcommitten**, sonst blockt `verify-assets` den Deploy. Der Build ist deterministisch (verifiziert), ein Neubau ohne Quelltextänderung erzeugt keine Diffs.
- **`.nvmrc` ist die gemeinsame Node-Version** für lokale Entwicklung, `ci.yml` und `cd.yml` (beide Workflows nutzen `node-version-file: '.nvmrc'`). Da der committete `public/build` aus der lokalen Node-Version stammt, würde eine abweichende Runner-Version den Vergleich potenziell grundlos rot färben. Wer lokal die Node-Version wechselt, aktualisiert `.nvmrc` mit.
- `git clean -fd` läuft **ohne** `-x`: alles Gitignorierte überlebt (`.env.local`, `config/jwt/*.pem`, `public/uploads/{avatars,restaurants}`, `var/`, `vendor/`, `public/bundles/`). ⚠️ **`public/uploads/team/` ist per `!`-Regel aus `.gitignore` ausgenommen** – Dateien dort, die nicht committet sind, löscht der Deploy.
- Kein Null-Downtime: zwischen `git reset` und dem Ende von `composer install` läuft die App für Sekunden gemischt.
- Rollback = Revert-Commit auf `production`; der nächste Lauf bringt die passenden Assets automatisch mit, weil sie im selben Commit stecken.
- PHPUnit ist **kein** Deploy-Gate (passend zur manuellen CI); zuschaltbar über `needs: [verify-assets, tests]`.

Server-Setup (einmalig) und die Waisen-Inventur vor dem ersten Lauf: siehe README → „🚢 Deployment".

## Fehler-Tracking (Sentry)

`sentry/sentry-symfony` 5.x meldet uncaught Exceptions und Monolog-Records ab `WARNING` an ein Sentry-Projekt in der **EU-Region** (`ingest.de.sentry.io`, Frankfurt).

**Nur `prod`.** `config/bundles.php` registriert `SentryBundle` mit `['prod' => true]` – in dev und test existiert die Extension nicht (`debug:config sentry` schlägt dort bewusst fehl). Damit kann weder lokale Entwicklung noch die Test-Suite Daten senden, und `ci.yml` braucht keine Anpassung. Zum lokalen Testen: `php bin/console sentry:test --env=prod` mit temporärem DSN in `.env.local`.

**DSN.** `SENTRY_DSN` steht leer in `.env` (committed) und wird **ausschließlich in der `.env.local` auf dem Server** gesetzt – das Repo ist öffentlich, ein committeter DSN erlaubte Fremden das Einschleusen von Events. Leerer Wert = Sentry lautlos inaktiv (dasselbe Muster wie `MOBILITEIT_API_KEY`); der leere Default in `.env` verhindert zugleich, dass `%env(SENTRY_DSN)%` den Container-Build sprengt. ⚠️ Der Eintrag muss **vor** dem Merge nach `production` auf dem Server stehen, sonst deployt es grün und Sentry bleibt still.

**`config/packages/sentry.yaml`** (alles unter `when@prod`):
- `release: 'endlech@%app.version%'` – hängt am CalVer-Parameter aus `config/services.yaml` und zieht bei jedem Release automatisch mit (kein fünfter Handgriff in der Release-Checkliste).
- `send_default_pii: false` – keine IP-Adressen, Cookies, Request-Header oder Nutzerdaten.
- `enable_logs: true` – **reicht allein nicht**; der Handler muss zusätzlich in `monolog.yaml` registriert sein (das Sentry-Onboarding-Snippet verschweigt das).
- `ignore_exceptions` filtert 404/405/403/429 – ohne das hätte Bot-Traffic die Quota geflutet. Matching läuft über `is_a($class, $pattern, true)`, greift also auch auf Subklassen und Interfaces.

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
