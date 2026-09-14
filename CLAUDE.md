# CLAUDE.md

Guide for AI assistants working on the endlech.lu codebase.

## Project Overview

**Endlech.lu** is an open platform to find and rate accessible restaurants in Luxembourg. Symfony 8 (PHP 8.4+), Tailwind CSS v4, Hotwire (Stimulus + Turbo). Live, mit einer datenbankgestützten Restaurantliste.

UI-Sprache ist Deutsch/Luxemburgisch. Code-Kommentare (Makefile, Templates) sind auf Deutsch.

## Ausführliche Dokumentation unter `docs/`

Diese Datei sammelt die **Implementierungs-Fallstricke** — chronologisch nach Issues
gewachsen. Geordnete Referenz in `docs/`:

| Datei | Inhalt |
|---|---|
| `docs/data-model.md` | Feldreferenz aller Entities, Enums, Repositories, ERD, Migrations-Historie |
| `docs/design-system.md` | Farben, Typografie, Komponenten-Klassenketten, Barrierefreiheit, Diagramm-/Druckregeln |
| `docs/prd.md` | Vision, Zielgruppen, Produktprinzipien, Funktionsumfang, Kennzahlen, Geschäftsmodell, Roadmap, Risiken |
| `docs/app-shell.md` | Layout-Hierarchie, Kopf-/Fußzeile, Navigation, Bottom-Nav, Admin-Shell, Druckansicht, Lücken |

**Bei Änderungen am Datenmodell oder an Komponenten-Mustern die passende Datei mitziehen** —
sonst laufen Code und Referenz auseinander. Die `⚠️`-Blöcke unten bleiben die Quelle für
alles, was beim Ändern schiefgeht.

### SDD-Artefakte

Über `/sdd-erfassen` in die SDD-Kette aufgenommen.

| | |
|---|---|
| **Artefaktpfad** | `docs/` — auch in Zeile 3 von `docs/prd.md` |
| **Feature-Inventar** | `features/index.md` — 26 Bestandsfeatures `B01`–`B26`, alle Status `rekonstruiert` |
| **Je Feature** | `features/BNN-slug/spec.md` (Akzeptanzkriterien, Fehlbestand) + `design.md` (Struktur, Zugriffsregeln, AK-Abdeckung) |
| **Projektweite Muster** | `features/fehlbestand-uebersicht.md` — zehn Muster über mehrere Features |
| **Stack-Profil** | `symfony-doctrine` |

⚠️ **Das Datenmodell heißt `data-model.md`, nicht `datenmodell.md`** wie in
`~/.claude/sdd/artefakte.md` vorgesehen. Verlinkt in `docs/README.md`, PRD und dieser
Datei; umbenennen bräche alle drei.

Alle Bestandsfeatures tragen das Präfix `B` — daran ist erkennbar, dass ihre `spec.md`
eine **Rekonstruktion** ist und selbst falsch sein kann, anders als bei einem gegen eine
Spec gebauten Feature. Weg: `bestand` → `/sdd-erfassen BNN` → `rekonstruiert` →
`/sdd-qa BNN`. **Stand 2026-08-23 alle 26 auf `rekonstruiert`** — QA steht aus,
`features/befunde.md` und Auditbericht existieren deshalb noch nicht.

⚠ **Die `spec.md` eines `B`-Features ist eine Rekonstruktion und kann selbst falsch sein.**
Sie beschreibt, was der Code tut, nicht was er tun sollte; Kriterien mit ⚠ markieren
bewusst aufgenommenes fragwürdiges Verhalten. Es läuft **nie** durch `sdd-tasks` und nie
durch den regulären Eingang von `sdd-build` — es ist gebaut.

## Konvention: `ActionLimiter` statt `consume(1)` von Hand

⚠ **`consume(0)` ist keine Prüfung.** `SlidingWindowLimiter` vergleicht
`verfügbar >= angefordert`, und `0 >= 0` gilt auch bei erschöpftem Kontingent (abfragen
mit `consume(0)`, verbrauchen mit `consume(1)` sieht richtig aus, ist es nicht).
Nachgestellt: **acht gültige Anmeldungen liefen durch**, die sechste hätte scheitern
müssen. Maßgeblich ist `getRemainingTokens()`. Deshalb `App\RateLimit\ActionLimiter`:

```php
$limiter = ActionLimiter::for($this->registrationLimiter, $request->getClientIp());
if (!$limiter->isAllowed()) { /* 429 */ }
// … Formular prüfen, Honeypot, alles was fehlschlagen darf …
$limiter->consume();   // erst hier: die Handlung findet statt
```

⚠ **Erst verbrauchen, wenn die Handlung stattfindet** (BF-11). Fünf Tippfehler sperrten
vorher eine Stunde aus, ohne dass ein Konto oder eine Mail entstand — der Deckel soll den
Angreifer treffen, nicht den, der sich vertippt.

⚠ **Eine Ausnahme, im Code:** Der Passwortwechsel verbraucht **vor** der Prüfung. Dort
ist der Fehlversuch der Angriff, nicht ein Tippfehler. Nicht „vereinheitlichen".

`LimiterCoverageTest` prüft, dass jeder konfigurierte Limiter verdrahtet ist und einen
`when@test`-Override hat. Ein Limiter, den niemand ruft, ist kein Schutz.

## Konvention: Jeder Weg, der eine Mail auslöst oder ein Geheimnis prüft, braucht einen Limiter

— und ebenso jeder Weg, der **bei jedem Aufruf den gesamten Bestand lädt**. Egal ob App
oder Browser. Wer einen solchen Weg **neu anlegt oder erweitert, legt den Limiter im
selben Commit an.**

Der Satz steht hier und nicht nur in `features/fehlbestand-uebersicht.md`, weil er dort
beim *Prüfen* gelesen wurde und nicht beim *Bauen* — Folge: BF-30 entstand am selben Tag,
an dem er formuliert wurde.

Fünfmal gefunden: Registrierung (BF-02), Anmeldung (BF-13), Passkey-Challenge (BF-18),
Adressänderung (BF-21), API-Einreichung (BF-30). Gemeinsam: Der **API**-Weg war gedeckelt,
der Browser-Weg nicht — oder eine Reparatur gab einem Weg erstmals Mailversand ohne Deckel.

Limiter in `config/packages/framework.yaml`; **der `when@test`-Override auf 10000 ist
Pflicht**, sonst summieren sich Aufrufe über die Testsuite.

⚠️ **Am Konto zählen, nicht an der IP**, wenn der Angriff eine bestehende Sitzung oder ein
Konto voraussetzt — dort wechselt die IP mühelos, das Konto nicht (siehe `password_change`).

## Konvention: Die Prüfung gehört dorthin, wo der Wert hereinkommt

Viermal derselbe Fehler (BF-27, BF-51, BF-62 zweimal): Ein ungeprüfter Wert fällt in die
nächste Schicht und kommt dort als **HTTP 500** heraus statt als Meldung.

- ⚠ **`'empty_data' => ''` ist Pflicht**, sobald der Entity-Setter striktes `string`
  verlangt. Ohne die Zeile übergibt Symfony `null`, `setName(string)` wirft, und der Nutzer
  bekommt einen Serverfehler statt der daneben konfigurierten `NotBlank`-Meldung.
- ⚠ **Eine Längenprüfung am Endpunkt reicht nicht, wenn daraus ein Slug wird.**
  `AsciiSlugger` macht aus „ß" ein „ss", aus einem japanischen Zeichen bis zu drei
  Buchstaben: 80 × „ß" = 160 Zeichen, 80 × „日" = 239. Der `SQLSTATE[22001]` wandert dann
  von `name` auf `slug`.

## Konvention: Übersetzungsschlüssel werden getestet, nicht gehofft

`tests/Unit/Translation/CatalogueCompletenessTest.php` prüft: (1) alle vier Kataloge tragen
**dieselbe Schlüsselmenge** (`messages` 1084+, `validators` 82+); (2) kein Wert ist leer;
(3) **jeder im Code verwendete Schlüssel ist definiert** — 736 aus `|trans` in Templates,
187 aus `src/Form/` (Constraint-Meldungen, `label`, `help`, `placeholder`).

⚠ **Punkt 3 fand seinen blinden Fleck erst im zweiten Anlauf.** Die erste Fassung prüfte
nur Constraint-Meldungen; zwei neue Feld-Beschriftungen standen in keinem Katalog, Test
blieb grün. Wer den Scanner erweitert, prüft mit einem absichtlich falschen Schlüssel
gegen, ob er rot wird. Manueller Weg: `debug:translation <locale> --only-missing`.

## Tech Stack

- **Backend:** PHP 8.4+, Symfony 8.0.*
- **Database:** MySQL 8.0 (via Docker) — **Production fährt MariaDB** (siehe Deployment)
- **ORM:** Doctrine 3.6 mit Migrations
- **Templates:** Twig
- **CSS:** Tailwind CSS v4.1 via PostCSS
- **JS/TS:** TypeScript, Hotwire (Stimulus 3.x + Turbo 7/8)
- **Build:** Webpack Encore 5.1
- **Scheduling:** `symfony/scheduler` + `dragonmantank/cron-expression` (braucht Messenger-Worker)
- **Testing:** PHPUnit 12.5
- **Email:** Brevo via `symfony/brevo-mailer` (prod); Dev: Mailpit (SMTP 1025, UI 8025)

## Project Structure

```
src/
├── Command/             # Console commands (app:metrics:snapshot)
├── Controller/          # Route controllers (attribute-based routing)
│   └── Open/            # Locale-freie Daten-Endpunkte (/open.json, Datensatz-Downloads)
├── DataFixtures/        # Doctrine fixtures (restaurant + user test data)
├── DTO/                 # Data Transfer Objects (NearbyStop)
├── Entity/              # User, Restaurant, RestaurantImage, OrderingOption, Cuisine, FinanceEntry, MetricSnapshot, WebauthnCredential …
├── Enum/                # Backed Enums (Language, OrderingPlatform, FinanceType, FinanceCategory, Canton, TriState …)
├── Message/ MessageHandler/   # Messenger (CaptureMetricSnapshot)
├── Open/                # Open-Startup-Logik (Stats, Kantonszuordnung, Punktzahl, Snapshots)
├── Repository/          # Doctrine repositories
├── Security/            # PasskeyAuthenticator, WebauthnUserEntityRepository
├── Scheduler/           # Wiederkehrende Aufgaben (#[AsSchedule]) – zwei Zeitpläne
└── Kernel.php

config/
├── packages/            # Bundle-Konfiguration (23 Dateien)
├── routes/  bundles.php  services.yaml (autowire+autoconfigure)  routes.yaml (#[Route]-Autoimport)

templates/               # base.html.twig; admin/, home/, email/, open/ (_metric,_bar,_histogram,_sparkline),
                         # profile/, restaurant/ (index+show), partials/, community/, organisation/, partner/ …

assets/                  # app.ts, controllers/*.ts, controllers.json, stimulus_bootstrap.ts, styles/app.css, usage/
migrations/              # DoctrineMigrations namespace
tests/                   # Unit/, Integration/, Functional/ (+ AbstractWebTestCase.php im Root)
translations/            # de, en, fr, lb
public/                  # Web root; images/platforms/ (SVG), uploads/restaurants/, uploads/avatars/ (gitignored)
```

## Common Commands

```bash
make init       # Full setup: Docker, composer, npm, DB, fixtures
make start/stop/restart
make db         # Doctrine migrations   |  make migration  # generate from entity diff
make fixtures   # reload (destructive)  |  make db-reset   # drop, recreate, migrate, fixtures
make cc         # clear cache           |  make assets     # prod asset build (npm run build)
make fix        # PHP-CS-Fixer          |  make lint       # TS type-check + ESLint
```

**NPM:** `npm run dev | watch | build | dev-server | typecheck (tsc --noEmit) | lint | lint:fix`
**Console:** `php bin/console <cmd>`; nützlich: `debug:router`, `make:entity`, `make:controller`, `make:migration`.

## Testing

```bash
make test               # Test-DB vorbereiten + PHPUnit
make test-db-setup      # nur Test-DB aufsetzen (einmalig)
php bin/phpunit                          # alle (Test-DB muss stehen)
php bin/phpunit --testsuite Unit|Integration|Functional
php bin/phpunit tests/Unit/Service       # einzelnes Verzeichnis
php bin/phpunit --display-all-issues     # volle Notice-/Deprecation-Texte
composer test                            # == make test (CI)
```

Strict mode in `phpunit.dist.xml`: fails on deprecation, notices, warnings. Bootstrap lädt
`.env.test`. Läuft real auf PHP 8.5; Zielversion 8.4+.

**Test-Isolation:** `dama/doctrine-test-bundle` (bundles.php nur `test`, Extension in
`phpunit.dist.xml`) wickelt jeden Test in eine Transaktion mit Rollback. Fixtures **einmal
vor** der Suite (`make test-db-setup`), nicht in `setUp()` → wiederholbar, reihenfolgeunabhängig.

**Mailer im Test:** `messenger.yaml` (`when@test`) routet `SendEmailMessage` auf `sync`
(+ `MAILER_DSN=null://null`), damit `MailerAssertionsTrait` greift.

**Drei Kategorien** — je Ordner + gleichnamige Testsuite, PSR-4 `App\Tests\{Unit,Integration,Functional}\…`
gespiegelt; Schicht-Unterstruktur erhalten (z. B. `tests/Unit/Api/`, `tests/Functional/Controller/Api/V1/`):
- **Unit** (`extends TestCase`, keine DB): mockbare Services (`PublicTransportService` via `MockHttpClient`,
  `AdminStatsService`), Transformer (echter `AssetUrlBuilder`, da `final`), Enums, Twig-Extensions,
  `OpeningHoursService`, `ApiExceptionSubscriber`, `CantonResolver` (Abgleich gegen `Canton::communeCount()`),
  `AccessibilityScore`, `FinanceCategory`.
- **Integration** (`extends KernelTestCase`, Container+DB, DAMA-isoliert): Repositories (v. a.
  `RestaurantRepositoryTest` — alle `findPaginated`-Filter/`sort`), `ImageUploadService`/`AvatarUploadService`
  (Temp-Dir via `sys_get_temp_dir`), `OpenStatsService`, `MetricSnapshotService`, `FinanceEntryRepository`,
  `CaptureMetricSnapshotCommand`.
- **Functional** (`extends AbstractWebTestCase`/`WebTestCase`, HTTP/Forms/Auth): Web-/Admin-Controller + `/api/v1`,
  `OpenControllerTest`, `Open\OpenDataControllerTest`, `AdminFinanceControllerTest`. Basisklasse
  `tests/AbstractWebTestCase.php` (`loginAs()`, `formWithField()`, `formByAction()`, `csrfTokenFrom()`);
  Web-Routen tragen `self::LOCALE` (`/de`).

**PHPUnit-12 (strict):** `#[DataProvider]` statt Docblock; `createStub()` für reine Rückgaben,
`createMock()` nur mit `expects()`. Ungültige Form-Submits → **422**, gültige → 302. Form-CSRF ist
stateless (`token_id: submit`), passt headless via Same-Origin-Referer; Custom-Token-IDs (z. B.
`toggle-verified-…`) sind session-basiert, als Hidden-Felder mitgesendet.

## Architecture & Conventions

**Routing:** PHP-Attribute (`#[Route]`), Auto-Discovery in `config/routes.yaml`.
**Services:** Autowiring + Autoconfiguration in `config/services.yaml`; alles unter `src/` ist Service.

### Routes

| Route name              | URL            | Controller method                   |
|-------------------------|----------------|-------------------------------------|
| `app_home`              | `/`            | `HomeController::index()` (Landing) |
| `app_restaurant_index`  | `/restaurants` | `RestaurantController::index()`     |
| `app_restaurant_show`   | `/restaurants/{id}` | `RestaurantController::show()`  |
| `app_login`             | `/login`       | `SecurityController::login()`       |
| `app_register`          | `/register`    | `RegistrationController::register()`|
| `app_logout`            | `/logout`      | `SecurityController::logout()`      |
| `admin_dashboard`       | `/admin`       | `AdminDashboardController::dashboard()` |
| `admin_restaurant_index`| `/admin/restaurants` | `AdminRestaurantController::index()` |
| `admin_restaurant_new`  | `/admin/restaurants/neu` | `::new()` |
| `admin_restaurant_edit` | `/admin/restaurants/{id}/bearbeiten` | `::edit()` |
| `admin_restaurant_delete`| `/admin/restaurants/{id}/loeschen` | `::delete()` |
| `admin_restaurant_toggle_verified`| `/admin/restaurants/{id}/verifizieren` | `::toggleVerified()` |
| `admin_restaurant_image_upload`| `/admin/restaurants/{id}/fotos` | `::uploadImage()` |
| `admin_restaurant_image_delete`| `/admin/restaurants/{id}/fotos/{imageId}/loeschen` | `::deleteImage()` |
| `admin_restaurant_image_sort`| `/admin/restaurants/{id}/fotos/sortieren` | `::sortImages()` |
| `app_profile`           | `/profile`     | `ProfileController::index()`        |
| `app_profile_edit`      | `/profile/edit` | `::edit()`        |
| `app_profile_password`  | `/profile/password` | `::changePassword()` |
| `app_profile_avatar_delete` | `/profile/avatar/delete` | `::deleteAvatar()` |
| `app_profile_email_cancel` | `/profile/email/abbrechen` (POST) | `::cancelEmailChange()` |
| `app_email_change_confirm` | `/verify/email-change/{token}` | `EmailVerificationController::confirmEmailChange()` |
| `app_passkey_rename`    | `/profile/passkeys/{id}/umbenennen` | `PasskeyController::rename()` |
| `app_passkey_delete`    | `/profile/passkeys/{id}/loeschen` | `::delete()` |
| `webauthn.controller.request.request.login` | `/passkey/login/options` (locale-frei) | Bundle (Login-Challenge) |
| `webauthn.controller.creation.request.add_device` | `/passkey/register/options` (locale-frei) | Bundle (Anlege-Challenge) |
| `webauthn.controller.creation.response.add_device` | `/passkey/register` (locale-frei) | Bundle (speichern) |
| `app_partner` / `_submit` (POST) / `_confirm` | `/partner`, `/partner/confirmation/{token}` | `PartnerController` |
| `app_organisations` / `_type` / `_submit` (POST) / `_confirm` | `/organisationen`, `/organisationen/{slug}`, `/organisationen/confirmation/{token}` | `OrganisationController` (`type` = gemeinden\|unternehmen\|vereine) |
| `admin_waitlist_index`  | `/admin/warteliste` | `AdminWaitlistController::index()` (beide Typen) |
| `admin_waitlist_{partner,organisation}_{show,status}` | `/admin/warteliste/{typ}/{id}[/status]` | `AdminWaitlistController` |
| `admin_waitlist_partner_link`| `/admin/warteliste/partner/{id}/restaurant` | `::linkRestaurant()` |
| `admin_finance_index / _new / _edit / _delete` | `/admin/finanzen[…]` | `AdminFinanceController` |
| `admin_finance_snapshot`| `/admin/finanzen/snapshot` (POST) | `::snapshot()` |
| `app_open`              | `/open`        | `OpenController::index()` (Transparenz) |
| `app_open_redirect`     | `/open` (locale-frei) | Redirect auf `app_open` |
| `app_open_json`         | `/open.json`   | `Open\OpenDataController::stats()` |
| `app_open_dataset_csv` / `_json` | `/open/dataset.csv` \| `.json` | `Open\OpenDataController` |
| `api_cuisine_search / _create` | `/api/cuisines[/search]` | `CuisineApiController` (Admin-only) |
| `api_v1_auth_login / _register` (POST) | `/api/v1/auth/{login,register}` | `Api\V1\AuthController` |
| `api_v1_restaurants_index / _create / _show / _images` | `/api/v1/restaurants[…]` | `Api\V1\RestaurantApiController` |
| `api_v1_me / _me_submissions` (GET) | `/api/v1/me[/submissions]` | `Api\V1\MeController` |
| `app.swagger_ui`      | `/api/docs`    | NelmioApiDoc Swagger-UI |
| `app_sitemap`         | `/sitemap.xml` (locale-frei) | `Seo\SitemapController::sitemap()` (Feature 10) |
| `app_usage_collect`   | `/api/send` (POST, locale-frei) | `Usage\CollectController` (Feature 11, Zählweg zu Umami) |

**Wichtig:** `/api/v1/` ist **locale-frei** (eigener `api_v1`-Block in `routes.yaml` + `exclude` am
`controllers`-Loader). Ebenso `src/Controller/Open/` (Block `open_data`) — die `exclude`-**Liste** hat
deshalb mehrere Einträge. Die HTML-Seite `/open` liegt unter `/{_locale}`; `app_open_redirect` leitet
sprachfrei dorthin. Der ältere `CuisineApiController` liegt weiterhin UNTER `/{_locale}`.

`/restaurants` Query-Parameter (alle kombinierbar, via `RestaurantRepository::findPaginated(sort, page,
limit, filters)`): `sort=rating|name|newest`; `page=N` (6/Seite, Doctrine `Paginator`); Filter
`verified`, `wheelchair`, `toilet`, `dogs`, `lighting`, `changing_table`, `disabled_parking`, `open`
(aktuell geöffnet), `city=` (LIKE), `cuisine[]=` (ManyToMany JOIN), `lang_de/fr/…` (AND), `vegan`,
`vegetarian`, `halal`.

## Entity: Cuisine (Issue #77)
Felder: id, name (VARCHAR 80 unique), slug (VARCHAR 100 unique). `__toString()` → name (für EntityType).
`CuisineRepository`: `findAllSorted()`, `search(query, limit)`, `findOrCreateByName(name)`. Restaurant hat
`$cuisines` (ManyToMany, cascade persist, JoinTable `restaurant_cuisine`); Helper `getCuisineNames(): string`.
API: `CuisineApiController` (`GET /api/cuisines/search?q=…`, `POST /api/cuisines`, Admin-only). Form: `EntityType`
+ Tom Select (`tom_select_controller.ts`, `remove_button`-Plugin, Load/Create). Fixtures: 20 Küchen-Typen.
Migration `Version20260323000000` (erstellt Tabellen, migriert Daten, entfernt `cuisine` VARCHAR).

## Entity: RestaurantImage
Felder: id, filename (VARCHAR 255), altText (nullable), restaurant (ManyToOne CASCADE DELETE), uploadedAt,
sortOrder (INT default 0). Restaurant `$images` (OneToMany, cascade persist+remove, orphanRemoval, OrderBy
sortOrder ASC); Helper `getCoverImage()`, `getGalleryImages()`. `ImageUploadService` — Upload nach
`public/uploads/restaurants/`, Löschung inkl. Dateisystem, `reorderAfterDelete()`.

## Entity: User — Avatar (Issue #54)
Feld `avatarFilename` (VARCHAR 255 nullable). Helper `getAvatarUrl(): ?string`. `AvatarUploadService` →
`public/uploads/avatars/`. Forms `ProfileType`, `ChangePasswordType`. `ProfileController` (4 Routen).
Templates `profile/index.html.twig`, `partials/_avatar.html.twig`. Migration `Version20260317000000`.

## Restaurant: submittedBy (Issue #63)
Feld `submittedBy` (ManyToOne User, nullable, SET NULL). `RestaurantRepository::findBySubmitter(User)` →
sortiert `createdAt DESC`. Profil-Sektion „Meine Einreichungen". `AdminSuggestionController::approve()` setzt
`submittedBy` aus `suggestion.suggestedBy`. Migration `Version20260319000000`. Fixtures: Admin → 3 verifiziert,
User → 3 unverifiziert, Rest → null.

## Entity: OpeningHour (Issue #64, erweitert in #81)
Felder: id, dayOfWeek (INT 1-7), openTime/closeTime (TIME nullable), restaurant (ManyToOne CASCADE DELETE).
**Mehrere Zeitslots pro Tag** (#81): kein UNIQUE mehr; **geschlossener Tag = keine Slots** (`isClosed` entfernt).
Restaurant `$openingHours` (OneToMany, cascade, orphanRemoval, OrderBy dayOfWeek ASC, openTime ASC); Helper
`getOpeningHoursForDay(day)`. `OpeningHoursService`: `isOpenNow()`, `isOpenAt()` (alle Slots), `getNextOpeningTime(Restaurant, ?$now)`
(Zeitzone Europe/Luxembourg). Twig `OpeningHoursExtension` (`|is_open_now`, `next_opening_time()`). Form
`OpeningHourType` als CollectionType (`allow_add`/`allow_delete`/`prototype`) in `RestaurantType`; Stimulus
`opening_hours_form_controller.ts` (Prototype-Klonen, gemeinsamer Index, setzt `dayOfWeek`). Templates
`partials/_opening_hours.html.twig`, `admin/restaurant/_form.html.twig`. Filter `?open=1`: SQL JOIN mit
TIME-Vergleich (inkl. Nachtschicht), `distinct()` gegen Slot-Duplikate. Test
`tests/Unit/Service/OpeningHoursServiceTest.php`. Migrationen `Version20260321000000` (Tabelle),
`Version20260619000000` (UNIQUE + `is_closed` entfernt).

## REST-API für die iOS-App (Issue #87)
Versionierte, **locale-freie** REST/JSON-API unter `/api/v1/` als iOS-Backend. Ansatz: **Plain Controller +
explizite Transformer** (kein API Platform, keine Serializer-Groups).
**Bundles:** `lexik/jwt-authentication-bundle`, `nelmio/cors-bundle`, `nelmio/api-doc-bundle`,
`symfony/rate-limiter`. JWT-Keypair in `config/jwt/*.pem` (gitignored) via `lexik:jwt:generate-keypair`; env
`JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE`, `CORS_ALLOW_ORIGIN`.
**Routing:** eigener `api_v1`-Block (prefix `/api/v1`, kein `_locale`) + `exclude` am `controllers`-Loader.

⚠️ **`POST /api/v1/restaurants` legt einen `RestaurantSuggestion` an, kein `Restaurant`** — Antwort **202**,
nicht 201 (QA B23, BF-24). Vorher entstand sofort ein öffentlicher Eintrag (Liste, Detailseite, `/open`-Kennzahlen,
CC-BY-Datensatz), ungesehen; zwei Aufrufe drückten `verifiedShare` von 27,3 auf 23,1 %. Der Web-Weg (B11) läuft
seit jeher über Vorschlag + Admin-Freigabe (B21); die API umging das.

⚠️ **`cuisines` ruft NICHT mehr `findOrCreateByName()`.** Namen landen als Freitext in
`RestaurantSuggestion::$cuisine` (max. 80 Zeichen, sonst 422 statt 500). Vorher schrieb jeder Aufruf dauerhaft in
die **öffentliche Filterauswahl** — gemessen „Pizzza" und „JETZT BEI UNS BESTELLEN 0900-123456", 50 je Anfrage.
Der Küchen-Typ wird bei der Freigabe entschieden.

⚠️ **Nicht übermittelte Merkmale sind `TriState::UNKNOWN`, nicht `false`.** Der Vorschlag unterscheidet „nein"
von „weiß nicht"; die alte Fassung machte aus jedem nicht gefragten Merkmal ein „nein".

**`ApiAuthenticationFailureSubscriber`** bringt JWT-Bundle-Antworten auf `{error:{code,message}}`. Das Bundle
schreibt die Antwort selbst, `ApiExceptionSubscriber` greift dort nicht — betroffen waren falsches Passwort und
abgelaufenes Token (BF-26).

⚠️ **Ohne `Accept-Language` antwortet die API luxemburgisch** (`translation.yaml`: `default_locale: lb`). Bewusst
nicht geändert (wäre ein Eingriff in die ganze Website).

**Controller** (`src/Controller/Api/V1/`): `AuthController` (`login` = json_login-Stub, Rumpf nie erreicht;
`register` repliziert den Web-Flow inkl. E-Mail-Verifikation, gibt KEIN Token; **Anti-Enumeration**: identische
generische 201 egal ob E-Mail existiert — bestehende Adressen bekommen Hinweis-Mail statt Bestätigung, Passwort
in beiden Zweigen gehasht (Timing)); `RestaurantApiController` (`index` Envelope `{data, meta:{page,limit,total,
totalPages,sort}}` + Filter-Mapping; `show`; `images`; `create` mit `submittedBy`=current, `isVerified=false`);
`MeController` (`me`, `submissions` via `findBySubmitter`; `#[IsGranted('IS_AUTHENTICATED_FULLY')]`).
**Transformer** (`src/Api/`): `RestaurantTransformer` (`list/detail/image`, injiziert `OpeningHoursService`,
Öffnungszeiten nach Tag 1–7) + `UserTransformer` (`profile`). Bild-/Avatar-URLs **absolut** via `AssetUrlBuilder`
(Scheme+Host des Requests, optional `APP_API_BASE_URL` für Proxy/CDN). Koordinaten im `create` auf Dezimal +
±90/±180 geprüft (422 statt DBAL-500). Explizit statt Serializer-Groups wegen untypischer Getter (`acceptsCash()`,
`isWheelchairAccessible()`, `hasAccessibleToilet()`). `password`/Token werden strukturell NIE ausgegeben.
**Security** (`security.yaml`): zwei stateless Firewalls VOR `main`: `api_login` (`^/api/v1/auth/login$`,
json_login `username_path: email`, Lexik-Handler) + `api` (`^/api/v1`, `jwt: ~`). `access_control`: `auth` +
`GET restaurants` = PUBLIC; `me` + `POST restaurants` = IS_AUTHENTICATED_FULLY.
**Fehler/CORS/Rate-Limit** (`src/EventSubscriber/`): `ApiExceptionSubscriber` (nur `^/api/v1`; **anonyme**
AccessDenied → 401, sonst 403; übernimmt HTTP-Exception-Header, z. B. `Retry-After`/`WWW-Authenticate`; im Debug
Exception-Detail bei 500). `ApiRateLimitSubscriber` (IP-basiert; Registrierung seit BF-25 unter `api_register`
mit 5/Stunde statt 100/Min — ohne den Deckel bis zu 100 Mails/Min an eine frei wählbare **fremde** Adresse, weil
Anti-Enumeration auch an bestehende Adressen schreibt; 429 inkl. `Retry-After` aus `RateLimit::getRetryAfter()`).
Limiter in `framework.yaml` (`api_anonymous` sliding 100/min, `api_login` fixed 5/min, `api_register` sliding
5/h; `when@test` alle 10000). CORS in `nelmio_cors.yaml` nur `^/api/v1/`. `bool $debug`-Bind in `services.yaml`.
**Swagger:** `nelmio_api_doc.yaml` (`path_patterns: ^/api/v1`, Bearer), Routen `app.swagger_ui`/`app.swagger`.
**Tests** (`tests/Functional/Controller/Api/V1/`): inkl. `password`-Regression und `sort`/`meta.sort`. Token via
`JWTTokenManagerInterface::create()`. `DATABASE_URL` musste in `.env.test` ergänzt werden (`.env.local` lädt im
Test nicht); `when@test` routet `async` → `in-memory://`.

## PWA – Installierbare iPhone-App (Issue #83)
Über Safaris „Zum Home-Bildschirm" installierbar (Vollbild, App-Icon, Offline-Fallback). **Kein** Swift-Projekt;
reiner Frontend/Static-File-Ansatz, keine Entity/Migration/Backend. **Locale-frei:** alle PWA-Dateien statisch in
`public/` (Service-Worker-Scope `/` erfordert das).
**Dateien:**
- `public/manifest.webmanifest` — name/short_name, start_url/scope `/`, `display: standalone`, portrait,
  `theme_color #0891b2` (cyan-600), `background_color #ffffff`, Icons 192/512 (`any`) + 512 (`maskable`). Via
  `<link rel="manifest">` in `base.html.twig`.
- `public/icons/icon-{57,60,72,76,114,120,144,152,180,192,512}.png` — via `bin/generate-pwa-icons.sh` (macOS
  `sips`): Logo erst quadratisch weiß gepaddet (`--padToHeightWidth`), dann skaliert (sonst Verzerrung, `logo.png`
  ist 10000×7664). 512 dient auch als maskable. **Eingecheckt.**
- `public/sw.js` — Vanilla SW, Scope `/`, `CACHE_VERSION`-Konstante (bei Änderungen erhöhen). install: App-Shell
  vorcachen + `skipWaiting()`. activate: alte Caches löschen + `clients.claim()`. fetch (nur GET, eigene Origin,
  **nie** `/api/`): Navigationen network-first → `offline.html`; `/build/`-Assets stale-while-revalidate; Bilder/
  Icons cache-first.
- `public/offline.html` — eigenständig (Inline-CSS), vom SW vorgecacht.
- `templates/partials/_bottom_nav.html.twig` — mobile Bottom-Nav (`fixed bottom-0 md:hidden`, Safe-Area
  `pb-[env(safe-area-inset-bottom)]`, Tap-Targets ≥ 44 px), 4 Items, aktiver Zustand über Route-Vergleich. In
  `base.html.twig` **nicht** auf `admin_*`; `<main>` hat `pb-16 md:pb-0`.
**`base.html.twig`:** `viewport-fit=cover`; iOS-Meta (`apple-mobile-web-app-capable`, `…-status-bar-style:
black-translucent`, `…-title: Endlech.lu`, `mobile-web-app-capable`); `apple-touch-icon` (180) + Legacy per Loop;
`theme-color #0891b2`. **`assets/app.ts`:** registriert `/sw.js` beim `load` (Fehler geschluckt, kein Workbox).
**`app.css`:** `@media (max-width:767px)` setzt Inputs auf `font-size:16px` (kein iOS-Auto-Zoom). Neue Keys
`nav.home`, `nav.restaurants`. **Bewusst nicht enthalten:** Splash-Screens, Pull-to-Refresh, Swipe, Push-Scaffold,
7-Seiten-Mobile-Audit.

## Passkey-Login (WebAuthn)

Face ID / Touch ID / Geräte-PIN **zusätzlich** zum Passwort. Auf `/login` ein Knopf **ohne E-Mail-Eingabe**
(Browser zeigt Konten selbst). Bundle `web-auth/webauthn-symfony-bundle` ^5.3.5 (Flex-Recipe greift nicht — nur
`recipes-contrib`, Version 3.0; `bundles.php`, `config/packages/webauthn.yaml`, `config/routes/webauthn.yaml` von
Hand, wie bei Sentry).

**Anmeldung als Formular-Login, nicht JSON.** `App\Security\PasskeyAuthenticator` erbt von
`WebauthnAuthenticator` (ein `AbstractLoginFormAuthenticator`), liest die Assertion aus `_assertion`. Der
`webauthn:`-Firewall-Schlüssel wird bewusst **nicht** benutzt (für 6.0 abgekündigt, verlangt `application/json`).
Über das Formular läuft der Passkey durch dieselbe Mechanik wie das Passwort (gleicher `check_path` `app_login`,
gleiche Weiterleitung, gleiches `remember_me`).

⚠️ **`entry_point: form_login` ist Pflicht**, sobald eine Firewall zwei Authenticator führt — sonst bricht der
Container-Build mit `RegisterEntryPointPass`. Nur `form_login` kennt den `login_path`.

⚠️ **`supports()` prüft mit `has('_assertion')`, nicht auf einen gefüllten Wert** (ENDLECH-6). Das Passkey-Formular
führt kein `_username`; bei Prüfung auf gefüllten Wert fällt ein Submit mit leerer Assertion an den
`FormLoginAuthenticator` und wirft `BadRequestHttpException: The key "_username" must be a string, "NULL" given` —
nackte Fehlerseite statt „Passkey-Anmeldung fehlgeschlagen". Mit `has()` beansprucht der Passkey-Weg jeden Submit
aus seinem Formular; unbrauchbare Assertion → Flash-Nachricht. Passwort-Weg unberührt (sendet kein `_assertion`).
Abgesichert durch `SecurityControllerTest::testEndlech6…` (vier Assertion-Formen).

⚠️ **Der Passkey-Knopf hat ein eigenes `<form>`.** Der `AuthenticationController` (npm) ruft vorher
`form.checkValidity()`; im Passwort-Formular sind beide Felder `required`. Das Passkey-Formular steht **zuerst im
Markup** (Tab-Reihenfolge folgt der sichtbaren). Deshalb nutzt `SecurityControllerTest` `formWithField()` statt
`filter('form')` — sonst „Unreachable field \"_username\"".

**Entity `WebauthnCredential`** erbt von `Webauthn\CredentialRecord`. Das Bundle registriert die mapped-superclass
selbst (`registerMappings()`) und trägt fünf DBAL-Typen (`base64`, `aaguid`, `trust_path`, …) über `prepend()` ein
— geerbte Felder brauchen **keine** ORM-Attribute. Eigene Felder: id, user (ManyToOne, `ON DELETE CASCADE`), name,
createdAt, lastUsedAt.

⚠️ **Die geerbten Spalten sind LONGTEXT** (Typ `base64` = CLOB). `public_key_credential_id` ist deshalb nur mit
Längenangabe indizierbar: `#[ORM\Index(..., options: ['lengths' => [100]])]`.

⚠️ **`findOneByCredentialId()` übergibt die ROHE Kennung**, nicht `base64_encode(...)`. Doctrine kodiert
Parameter anhand des Feld-Mappings selbst; Handkodierung käme doppelt an („The credential ID is invalid"). Das
mitgelieferte `DoctrineCredentialSourceRepository` kodiert vor, baut aber über einen QueryBuilder ohne Feldbezug.

⚠️ **`saveCredentialRecord()` läuft bei JEDER Anmeldung**, nicht nur beim Anlegen: Der Signaturzähler ist der
Klon-Schutz. Reines `persist()` erzeugte Duplikate. Beim Anmelden ist der Datensatz bereits die Entity (aus
`findOneByCredentialId()`), beim Anlegen ein frischer `PublicKeyCredentialSource` → `WebauthnCredential::fromRecord()`.

**`User::$webauthnHandle`** (VARCHAR 64, nullable, unique) statt DB-ID (liegt dauerhaft auf dem Gerät). Erzeugt in
`WebauthnUserEntityRepository::findOneByUsername()`.
⚠️ **`bin2hex(random_bytes(16))`, nicht 32** — `PublicKeyCredentialUserEntity` erzwingt `strlen($id) <= 64`.

**Keine Kontoerstellung per Passkey:** `WebauthnUserEntityRepository` implementiert bewusst **nicht**
`CanRegisterUserEntity`/`CanGenerateUserEntity`; ohne diese Schnittstellen lehnt das Bundle es strukturell ab.

**Konfiguration bewusst schmal** (`failOnDeprecation="true"` färbt jede abgekündigte Option rot). Nicht gesetzt:
`rp.name` (seit 5.3.0), `rp.icon` (5.1.0), `secured_rp_ids` (5.2.0), `options_storage` je Firewall (5.2.0). Nicht
benutzt: `DoctrineCredentialSourceRepository` (5.2.0), `PublicKeyCredentialSourceRepositoryInterface`/
`CanSaveCredentialSource` (5.3) — stattdessen `CredentialRecordRepositoryInterface` + `CanSaveCredentialRecord`.

⚠️ **`allowed_origins` bleibt auf Production leer.** Gefüllt gilt exakter Origin-Abgleich inkl. Port, Einträge ohne
Schema werden still auf `https://…:443` normalisiert. Leer greift der Spec-Weg (HTTPS-Zwang + rp-id-Abgleich).
**Lokal ist ein `when@dev`-Block nötig** (`http://localhost:8000`), weil `CheckAllowedOrigins` serverseitig HTTPS
verlangt. Port anpassen, wenn `symfony server:start` ausweicht.

**Frontend:** `@web-auth/webauthn-stimulus` (5.3.5) + `@simplewebauthn/browser`.
⚠️ **Nicht in `assets/controllers.json` eintragen** — StimulusBundle löst Einträge dort gegen ein Composer-Paket
auf und bricht mit „Could not find package". Registriert in `stimulus_bootstrap.ts` als `passkey-auth`/
`passkey-register`. `passkey_ui_controller.ts`: Feature-Detection (Knopf nur bei `window.PublicKeyCredential`),
Ladezustand, übersetzte Meldungen; `ERROR_CEREMONY_ABORTED` erzeugt bewusst **keine** Meldung (Abbruch ist eine
Entscheidung). ⚠️ `submitViaForm` nutzt `form.submit()`, überspringt das submit-Ereignis → `generateCsrfToken()`
läuft nicht; unkritisch (Authenticator setzt kein CSRF-Badge, Assertion an Herkunft+Challenge gebunden).

**Verwaltung im Profil** (`PasskeyController`, `partials/_passkey_manage.html.twig`): Umbenennen/Entfernen sind
gewöhnliche Formulare (ohne JS); nur Anlegen braucht JS. Anzeigename aus User-Agent
(`WebauthnCredentialRepository::guessDeviceName()` → „iPhone"/„Mac"/„Android"; Produktnamen statt Übersetzungs-
schlüssel, da einmal festgeschrieben).
⚠️ **Besitzprüfung VOR CSRF-Prüfung.** Wer nicht Eigentümer ist, hat unabhängig vom Token nichts verloren → 403.
Am Schutz ändert das nichts (Fremd-Angriff scheitert danach am Token).

**Tests:** `WebauthnCredentialRepositoryTest` (Anlegen vs. Fortschreiben, base64 beidseitig, verwaister Handle),
`PasskeyControllerTest` (Options, Umbenennen/Löschen über Formulare, fremder Passkey → 403). Die Assertion selbst
ist mit PHPUnit nicht testbar (braucht virtuellen Authenticator via CDP `WebAuthn.addVirtualAuthenticator`).
**Migration** `Version20260821000000`. **Übersetzungen:** Block `passkey:` + `flash.passkey_*`.
**Bewusst nicht enthalten:** Conditional UI/Autofill, Passkey-Registrierung neuer Konten, Passkeys in `/api/v1`,
Attestation-Prüfung (`attestation_conveyance` bleibt `none`).

## E-Mail-Änderung mit Bestätigung (QA B04, BF-19)

Neue Adresse wird **vorgemerkt, nicht übernommen**. Vorher wechselte `User::$email` im selben Request und
`is_verified` blieb `true` — der Status galt für eine nie bestätigte Adresse, und ein Sitzungsdieb schrieb das
Konto dauerhaft auf sich um. Rückweg gäbe es nicht (kein Passwort-Reset im Projekt, Feature `01`).

**Felder:** `pendingEmail`, `pendingEmailToken`, `pendingEmailTokenExpiresAt` (Migration `Version20260824120000`,
reine `ADD COLUMN`, MariaDB-10.5-tauglich). `User`-Methoden: `requestEmailChange()`, `confirmEmailChange()`,
`clearPendingEmail()`, `isPendingEmailTokenExpired()`.

⚠️ **`ProfileController::edit()` merkt sich die bisherige Adresse VOR `handleRequest()` und setzt sie danach
zurück.** `ProfileType` ist an die Entity gebunden; nach `handleRequest()` steht dort der Eingabewert. Der soll
nicht wirksam werden, die Validierung muss ihn aber sehen (sonst prüfte `UniqueEntity` auf dem alten Wert).

⚠️ **Zwei Mails, die wichtigere geht an die ALTE Adresse.** Ein Konto-Übernehmer sitzt im neuen Postfach; nur die
Warnung an die alte Adresse erreicht den rechtmäßigen Inhaber. Vorlagen `email/email_change.html.twig` (Knopf),
`email/email_change_notice.html.twig` (Warnung, kein Knopf).

⚠️ **`pending_email` hat keinen Unique-Index** (siehe `docs/data-model.md`). Beim Einlösen prüft der Controller
gegen `email` und räumt den Vorgang ab; ohne das liefe der `flush()` in eine Unique-Verletzung → 500.

**Bestätigungsroute unter `/verify/…`, nicht `/profile/…`** (dort greift `IsGranted('IS_AUTHENTICATED_FULLY')`
auf Klassenebene). Der Token *ist* der Nachweis; eine Anmeldepflicht machte den Klick aus dem Postfach unbenutzbar.
`access_control` deckt `^/[a-z]{2}/verify` als `PUBLIC_ACCESS`; zwei Pfadsegmente → kein Konflikt mit `/verify/{token}`.

⚠️ **Der Hinweis auf den offenen Vorgang steht AUSSERHALB des Profilformulars.** `<form>` im `<form>` ist ungültig
— der Browser verwirft das innere, der Abbrechen-Knopf wäre wirkungslos.

**Limiter `password_change`** (5 je 15 Min, BF-20) zählt **am Konto**, nicht an der IP (Angriff = Passwortraten
aus gekaperter Sitzung, IP wechselt mühelos).

## Presse-Kit (`/presse`, Feature 05)

Öffentliche Presseseite: Beschreibungstexte in drei Längen, Faktenblatt mit `/open`-Livezahlen, Materialpaket,
Gründerporträt, freigegebene Zitate, Meldungen, Kontakt. **Keine Entity/Migration** — Wertobjekte unter
`App\Press\`, Texte in Domain `press`. Aufbau wie Feature 03.

⚠️ **Wer eine Datei in `public/presse-kit/` ersetzt, erhöht `CACHE_VERSION` in `public/sw.js`.** Der SW liefert
Bilder cache-first; ein wiederkehrender Besucher sähe sonst die alte Logo-Vorschau. Das Paket selbst wird nie
gecacht (kein `image`, nicht `/build/`) — genau deshalb laufen beide auseinander, und **kein Prüflauf sieht es**.

⚠️ **Das Paket ist eine committete Datei, erzeugt von `app:press:package`** (`make press-kit`), nicht zur Laufzeit
gepackt. Liegt unter `public/presse-kit/`, direkt vom Webserver ausgeliefert (nichts zu deckeln). `PressPackageTest`
vergleicht Inhalt mit `PressRegistry::assets()` → roter Lauf, wenn jemand tauscht ohne den Befehl neu zu laufen.

⚠️ **`ext-zip` steht in `require-dev` und in der CI-Extension-Liste.** Die Anwendung braucht sie nie, nur Befehl +
Prüflauf. Fehlt sie in `.github/workflows/ci.yml`, bricht der Lauf mit unbekannter Klasse ab (sieht wie Codefehler aus).

⚠️ **Verzeichnis heißt `presse-kit`, nicht `presse` — ein Verzeichnis unter `public/` darf generell nicht heißen
wie eine Route.** Auf Apache schickt `mod_dir` `/presse` per **301** auf `/presse/`, Symfonys Trailing-Slash-Regel
zurück — Endlosschleife (BF-100). **Lokal unsichtbar** (kein `mod_dir`): drei QA-Läufe + ein grüner CI-Lauf sahen
es nicht. `RouteDirectoryCollisionTest` prüft seither die Ursache. Dazu `/presse/` **mit** Schrägstrich als eigene
Weiterleitung (der 301er steckt in Browsern, die die kaputte Adresse einmal öffneten).

⚠️ **Betreiberangaben als Parameter, nicht im Katalog** (`app.operator_name`, `app.operator_address`,
`app.operator_responsible`, `app.press_email`). Sie erscheinen auf **zwei** Seiten (Faktenblatt + Impressum); vier
Katalogeinträge wären vier Stellen, an denen eine Anschrift auseinanderläuft (`CatalogueCompletenessTest` prüft
Vollständigkeit, nicht Gleichheit). Als Twig-Globals, weil zwei Controller sie brauchen.

⚠️ **Die Angabe zur Behinderung steht in genau einem Katalogschlüssel** (`person.bio`). `PressCatalogueTest` prüft,
dass kein anderer Schlüssel sie enthält → ihr Widerruf ist eine Textstelle. Sie im Boilerplate zu wiederholen ist
naheliegend und macht genau diese Zusage kaputt.

⚠️ **Wortgrenzen der Beschreibungstexte stehen im Enum `BoilerplateLength`, nicht im Prüflauf** (sonst prüft er
gegen sich selbst). Gezählt **je Sprache**: Französisch braucht 15–20 % mehr Wörter, ein deutscher 28-Wort-Text
sprengt die Grenze in der Übersetzung.

## Warteliste für die mobile App (`/app`, Feature 08)

Dritte Warteliste neben Partnern (B14) und Organisationen (B15), unter `/{_locale}/app` + sprachfreier Weiterleitung
`/app`. Erfasst **nur** E-Mail + Plattform (`AppPlatform`: `ios` | `android`). Teilt `WaitlistConfirmationService`,
`WaitlistEntryInterface`, `WaitlistRequestHelper`, `WaitlistStatus`.

⚠️ **Der TestFlight-Link steht in der ZWEITEN Mail, nicht in der Bestätigungsmail.** In der ersten hätte der
Bestätigungsklick keinen Grund (Liste bliebe auf `pending`, weder Marketing-Kontakt noch belegte Einwilligung);
zudem bekäme, wer eine fremde Adresse einträgt, den Beta-Zugang des Fremden.

⚠️ **Betreff und Rumpf der zweiten Mail hängen an DERSELBEN Bedingung** (`hasBeta() && '' !== $testflightUrl`).
Allein aus `hasBeta()` abgeleitet (erster Anlauf, falsch): Bei iOS mit leerem `app.testflight_url` ginge „Deine
Beta ist da" hinaus, während der Rumpf korrekt keinen Beta-Abschnitt trägt (Regelfall lokal + Test).

⚠️ **`hasBeta()` gehört an `AppPlatform`, nicht ins Template.** Die Frage steht an vier Stellen (Hinweis, zweite
Mail, deren Betreff, Verwaltungsliste); verstreute `platform == 'ios'`-Abfragen laufen beim ersten Android-Build
auseinander.

⚠️ **Unique-Index auf `email` — anders als B14/B15** (dort jeder Submit eine Zeile). Hier „eine Adresse, ein
Eintrag"; eine Prüfung allein im Controller verliert das Wettrennen zweier Tabs. Antwort auf eine Dublette
**identisch** zum Erfolg (Anti-Enumeration) — Ausnahme: ist der bestehende Eintrag `pending` und Token abgelaufen,
geht eine **neue** Mail mit neuem Token raus (sonst wäre der Vorgang eine Sackgasse).

⚠️ **Der Aufräumlauf hängt an ZWEI Wegen**, nicht einem Cron: Eintrag im Zeitplan `marketing` (täglich 03:40)
**und** `sweepOncePerDay()` beim Öffnen von `/admin/warteliste`. Grund ist der Bestand (auf Produktion fehlten
schon zweimal geplante Läufe). Gelöscht wird nach **`selfConfirmedAt IS NULL`**, nicht `status = pending` (BF-89:
ein weitergesetzter Eintrag entginge sonst dem Lauf).

⚠️ **Zeitplan heißt `marketing` und trägt mehrere Aufgaben.** Ein weiterer Zeitplan bräuchte einen weiteren
Transport im `messenger:consume`-Befehl (steht an drei Stellen: `worker`-Stage des Dockerfiles, diese Datei,
Coolifys Startbefehl; die dritte zieht niemand automatisch nach). `processOnlyLastMissedRun(true)` passt für alle
(Bestand abarbeiten, kein Zeitpunkt).

⚠️ **Drei Stellen im Bestand mussten mitziehen**, sonst wirkt das Feature lautlos halb: `MarketingOrigin::APP` +
`originOf()` (sonst fiele die Quelle in `ACCOUNT`), die Klassenliste in `MarketingContactRegistry::sourcesFor()`
(**ohne sie greift der Widerruf nicht bis Brevo**, BF-84), Zeilennormalisierung in `AdminWaitlistController`.

⚠️ **Der Quellen-Filter der Verwaltungsliste arbeitete mit Negationen** — bei zwei Werten äquivalent, bei dreien
nicht: `?source=app` lieferte weiterhin Partner- + Organisationszeilen. Umgestellt auf einmalige Normalisierung +
positiven Vergleich; alle drei Zeilenarten führen **dieselbe Schlüsselmenge** (`platform => null` bei den anderen),
weil `strict_variables: true` aus einem fehlenden Schlüssel einen Laufzeitfehler macht.

⚠️ **Kontolöschung nimmt die App-Vormerkung mit — abweichend von B14/B15** (dort bleiben Einträge stehen, BF-84:
eigenständige Einwilligung hängt nicht am Konto). Für die App-Warteliste am 2026-09-04 anders entschieden; drei
Wartelisten verhalten sich damit unterschiedlich, **OF-08** in `features/08-app-warteliste/spec.md` hält die Frage
offen. Das `flush()` direkt nach dem `remove()` ist Pflicht: `scheduleRemoval()` sucht die Quellen per Abfrage, und
Doctrine liefert eine bloß vorgemerkte Löschung noch aus dem Identity Map.

⚠️ **Die Kennzahl auf `/open` erscheint erst ab 50 selbst bestätigten Vormerkungen — strukturell.** Unterhalb
fehlen die drei Schlüssel im Ergebnis-Array (nur im Template verborgen wären sie über `/open.json` abrufbar, wie
die Quartalssperre der Finanzen). Schwelle `OpenStatsService::APP_WAITLIST_MIN`, nicht im Template/Prüflauf.

⚠️ **Ein neuer Token braucht eine neue Frist** (BF-117). `isExpired()` misst an `createdAt`, keine eigene
Ablaufspalte — wer nur `generateConfirmationToken()` ruft, verschickt einen sofort abgelaufenen Link (gemessen HTTP
410). Dafür `AppWaitlistEntry::renewConfirmationWindow()`. `consentAt` wandert **nicht** mit (neu ist die Frist,
nicht die Einwilligung).

⚠️ **Jeder `return` zwischen `isAllowed()` und `consume()` ist ein ungedeckelter Weg** (BF-118, Kehrseite von
BF-11). Der Dublettenzweig kehrte vor dem Verbrauch zurück und verschickte trotzdem eine Mail — fünf Absendungen
auf eine fremde Adresse bei unverändertem Kontingent. Unsichtbar, weil der Limiter konfiguriert, verdrahtet und von
`LimiterCoverageTest` bestätigt ist; er greift nur auf einem Weg nicht.

⚠️ **E-Mail-Prüfung mit `Email::VALIDATION_MODE_STRICT`** (BF-119). Der HTML5-Default lässt Adressen durch, die
`Mime\Address` (RFC 2822) ablehnt — und `register()` speichert **vor** dem Versand, bei einem 500er blieb eine Zeile
stehen. ⚠️ **Seit 2026-09-11 prüfen alle vier Wege strikt** (`PartnerWaitlistType` B14, `OrganisationWaitlistType`
B15, `RegistrationType` B01 nachgezogen). ⚠ **Noch nicht ausgeliefert:** auf Produktion erzeugt eine RFC-widrige
Adresse dort weiterhin einen 500er + bleibende Zeile, bis Branch `fix/bf-119-email-validierung` gemerged ist.

⚠️ **Die zweite Hälfte der Reparatur steht im Service, nicht im Formular.** In
`WaitlistConfirmationService::register()` und `RegistrationController` wandert die Adresskonstruktion **vor** den
`flush()` (trennt „Ist das eine Adresse?" davor von „kam die Mail an?" dahinter). **Reihenfolge Token → flush → Mail
bleibt** — wer sie umdreht, nimmt die Eigenschaft mit, für die sie gewählt wurde.
⚠️ **Über das Formular nicht mehr prüfbar** (strikte Constraints halten widrige Adressen ab); Nachweis in
`tests/Integration/Waitlist/Bf119RegisterReihenfolgeTest.php`, ruft den Service direkt.

⚠️ **Kein Verzeichnis `public/app` anlegen** — sonst BF-100 auf neuer Adresse. `RouteDirectoryCollisionTest` prüft
projektweit.

**Keine interne Meldung ans Team** — anders als B14/B15 (dort muss ein Mensch zurückrufen; hier läuft der Zugang
über den Link). Eine Mail je Vormerkung entwertete die beiden Meldungen, die eine Handlung verlangen.

⚠️ **Bestätigte Vormerkungen haben keine Frist** (OF-01, 2026-09-04). Der 30-Tage-Lauf greift nur bei nie
bestätigten. Entschieden: die Liste überlebt eine Veröffentlichung (nach iOS Android, nach dem ersten Build ein
Update). Folge: **Der Abmeldelink ist der einzige Weg hinaus** und tragende Funktion — wer ihn aus einer Vorlage
entfernt, nimmt Art. 7 Abs. 3 DSGVO mit.

⚠️ **Der Hinweis auf einen toten TestFlight-Link steht NUR im Zweig mit Link** (`email.app_beta_link_dead`, OF-04).
Ohne Link gibt es keinen toten Link. Er existiert, weil ein voller/abgelaufener Link von außen **nicht erkennbar**
ist (Apple bietet keine Abfrage, ein voller Link antwortet wie ein offener) — nur der Empfänger sieht den Fall.

**Konfiguration:** `APP_TESTFLIGHT_URL` (leer in `.env`, echt nur `.env.local`/Server) → `app.testflight_url`;
leer = lautlos aus (zweite Mail ohne Beta-Abschnitt). Limiter `app_waitlist` (10/Std je IP, `when@test` 10000) —
**eigenes Kontingent**, nicht `partner_waitlist` mitbenutzt (BF-38); großzügiger, weil Privatpersonen vom Telefon
kommen und Mobilfunk-NAT viele auf dieselbe Adresse legt. **Migration** `Version20260904120000`.

## Cookie-Consent-Banner (Issue #82)
DSGVO-Banner beim ersten Besuch, speichert `accepted`/`declined` 365 Tage im Cookie `cookie_consent`. Keine Entity/
Migration/Backend — rein clientseitig.
Stimulus `cookie_consent_controller.ts`: `connect()` zeigt das Banner ohne Cookie; `accept()`/`decline()` setzen
den Cookie (`path=/; max-age=365d; samesite=lax`, `secure` nur bei HTTPS — Muster aus `csrf_protection_controller.ts`).
Values `cookieName`, `lifetime`. Cross-Element via Fenster-Event: Footer-Link ist eigene Instanz
(`<li data-controller="cookie-consent">`), `openSettings()` → `this.dispatch('open')`; Banner fängt über
`cookie-consent:open@window->cookie-consent#reopen`. Beide `reopen()`/`connect()` via `hasBannerTarget` abgesichert.
Template `partials/_cookie_banner.html.twig` (`role="dialog"`, `aria-modal="false"`, `fixed bottom-0`, barrierefrei),
verlinkt auf `path('app_impressum') ~ '#datenschutz'`. Einbindung in `base.html.twig` nur außerhalb Admin
(`_route` not starts with `admin_`). Datenschutz-Anker `<section id="datenschutz" class="… scroll-mt-24">`.
Übersetzungen: `cookie`-Block.

## Nearby Stops / Public Transport (Issue #65)
Restaurant-Felder: latitude (DECIMAL 10,8 nullable), longitude (DECIMAL 11,8 nullable), nearbyStopsNote (TEXT).
Helper `hasCoordinates()`. DTO `App\DTO\NearbyStop` (readonly): name, distance (m), lines (string[]), type
(bus/tram/mixed). `App\Service\PublicTransportService::findNearbyStops(lat, lng)` — HAFAS API (`cdt.hafas.de`),
Cache 24h, Graceful Degradation (leerer Key → `[]`). Parameter `app.mobiliteit_api_key`, `…_radius`, `…_max_stops`
(5); env `MOBILITEIT_API_KEY` (leer = aus).

⚠️ **Der Block heißt „Nahverkehr", nicht „barrierefreie Haltestellen".** HAFAS kennt **kein**
Barrierefreiheitsmerkmal (`grep accessib|barrier|wheelchair` in Service/DTO findet nichts). Bis 2026-08-24 stand
trotzdem „Keine barrierefreien Haltestellen … gefunden" bzw. „automatische Suche nach barrierefreien Haltestellen"
(QA B10, BF-46). Eine erfundene Barrierefreiheitsaussage ist auf dieser Plattform der schwerste Textfehler; die
Texte sagen jetzt, was geprüft wurde, + Herkunftshinweis.

⚠️ **Radius 1000, nicht 500.** Bei 500 m: 8 von 11 Restaurants ohne Haltestelle; bei 2000 m sieben mit. Nach der
Umstellung 8 von 11 mit Treffern.

⚠️ **`'timeout' => 3` ist Pflicht.** Ohne Vorgabe greift `default_socket_timeout` (gemessen 60 s Wartezeit auf der
Detailseite). Der `catch (\Throwable)` fängt den **Ausfall**, nicht die **Verzögerung**.

⚠️ **Exception-Meldung nicht ins Log durchreichen.** Sie enthält die volle URL samt `accessId` (HAFAS übergibt den
Schlüssel als Query-Param). Geloggt werden Klasse + Statuscode. Den zweiten Weg (Symfonys `http_client`-Kanal, in
`prod` nicht ausgeschlossen) deckt `App\Monolog\SecretMaskingProcessor` ab.
Template `partials/_nearby_stops.html.twig`. Form: latitude/longitude (NumberType, Range ±90/±180), nearbyStopsNote
(max 1000). Admin-Fieldset „Standort & Nahverkehr". Migration `Version20260322000000`. Fixtures: alle 11 mit echten
Koordinaten, Brasserie du Grund mit Beispiel-Note.

## ⚠️ `setMaxResults()` mit `addSelect()`-Joins braucht `Paginator`

Ein `leftJoin` mit `addSelect` (gegen N+1) vervielfacht die SQL-Zeilen je Entity; `setMaxResults()` begrenzt
**Zeilen**, nicht Objekte. `RestaurantRepository::findTopRated(6)` lieferte dadurch **ein** Restaurant statt sechs
(das bestbewertete Haus brachte allein 14 Zeilen = 7 Öffnungszeiten × 2 Küchen mit, `LIMIT 6` war im ersten
Datensatz verbraucht; QA B12, BF-64). Gemessen: `findTopRated(6)` → 1, `(20)` → 2, `(100)` → 7.

**Lösung: `new Paginator($qb->getQuery(), true)`** — der zweite Parameter `$fetchJoinCollection` ist genau dafür da.
`findPaginated()` macht es seit jeher so; nur `findTopRated()` nicht.

⚠️ **Ein Test mit `assertLessThanOrEqual($limit, count(...))` fängt das nicht** (war grün, während die Startseite
eine Karte zeigte). Bei Begrenzung gehört `assertCount(min($limit, $bestand), …)` geprüft.

## Entity: OrderingOption (Issue #43)
Felder: id, platform (VARCHAR 20 aus `App\Enum\OrderingPlatform`), url (VARCHAR 500), restaurant (ManyToOne
CASCADE DELETE). Restaurant `$orderingOptions` (OneToMany, cascade persist+remove, orphanRemoval). Enum-Cases:
`uber_eats`, `deliveroo`, `just_eat`, `wolt`, `wedely`, `goosty`, `phone`, `website`, `other`; Helper `label()`,
`emoji()`, `actionLabel()`, `logoPath()` (SVG oder null). SVG-Logos `public/images/platforms/` (6 Marken). Form
`OrderingOptionType` als CollectionType in `RestaurantType` (`by_reference: false`). Migration `Version20260314200000`.

## Entity: RestaurantSuggestion
Felder: id, suggestedBy (ManyToOne User nullable SET NULL), name (VARCHAR 150), city (100), cuisine (80), emoji
(VARCHAR 10, default '🍽️'). Barrierefreiheit (6 × `?TriState`): isWheelchairAccessible, hasAccessibleToilet,
allowsAssistanceDogs, hasBrightLighting, hasChangingTable, hasDisabledParking. Zahlung (3 × `?TriState`):
acceptsCash, acceptsCard, acceptsPayconiq. Ernährung (3 × `?TriState`): isVegan, isVegetarian, isHalal.
**Dreiwertig statt bool** — siehe unten. Sprachen: spokenLanguages (JSON, aus `App\Enum\Language`). Kontakt: phone
(30), email (180), website (500). Social: instagramUrl/facebookUrl/tiktokUrl (500). Meta: notes, status (VARCHAR
20, default 'pending'), adminNote, createdAt. Status-Konstanten STATUS_PENDING/APPROVED/REJECTED.
Form `RestaurantSuggestionType` — 5-Step-Wizard (Grunddaten, Barrierefreiheit, Ernährung & Zahlung, Kontakt &
Sprachen, Notizen); Stimulus `suggestion_wizard_controller.ts` (Prev/Next/GoTo + clientseitige Tri-State-Pflicht).
Template `community/vorschlagen.html.twig`. Admin `AdminSuggestionController` (CRUD + approve überträgt alle Felder
+ reject), Template `admin/suggestion/show.html.twig`. Routen `admin_suggestion_{index,show,approve,reject}`,
Community `/community/suggest`. Migrationen `Version20260320000000` (Basis), `Version20260324000000` (neue Felder), `Version20260809000000` (bool → Tri-State).

## Dreiwertige Antworten im Vorschlags-Wizard (Ja / Nein / Weiß nicht)
Eine nicht angehakte Checkbox bedeutete früher zweierlei — „gibt es nicht" und „weiß nicht" (alter Hint „frei
lassen", Admin `accessibility.no_unknown` = „Nein / unbekannt"). Für eine Barrierefreiheits-Plattform wesentlich,
deshalb sind die 12 Fragen jetzt **Pflichtfragen mit drei Antworten**.

**Enum `App\Enum\TriState`** (`YES`/`NO`/`UNKNOWN`, backed string): `transKey()`, `label()`, `emoji()`, `isYes()`.
**Warum Enum, nicht `?bool`:** Mit `?bool` wäre „Weiß nicht" = `null`, ununterscheidbar von „unbeantwortet" — genau
diese Unterscheidung braucht die Pflichtvalidierung. Also Property `?TriState` (null = unbeantwortet) + `NotNull`;
Doctrine `#[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]`. **Getternamen bleiben**
(`isWheelchairAccessible(): ?TriState`, `acceptsCash(): ?TriState`, …).
**Form** (`addTriState()`): `ChoiceType` `expanded: true`, `multiple: false`, `placeholder: false`, `NotNull
(message: 'suggestion.answer_required')`.
⚠️ **`'error_bubbling' => false` ist Pflicht** — ein expanded `ChoiceType` ist compound, `error_bubbling` dort
default `true`. Ohne die Zeile landen alle 12 Fehler am Root; `form_errors(feld)` bliebe leer und die Step-Erkennung
(prüft `form[field].vars.errors`) griffe nie. Keine Vorauswahl aus `placeholder: false` + Wert `null` → ungültiger
Submit liefert verlässlich 422.
**Rendering:** `partials/_tristate_field.html.twig` (Segmented Control; echte Radios als `sr-only`, Fokus
`peer-focus-visible:ring-inset`) + `_tristate_value.html.twig` (Admin: Ja grün, Nein rot, Weiß nicht grau).
**Approve:** `Restaurant` bleibt bei `bool` — „Weiß nicht" wird „Nein" (`?->isYes() ?? false`). Ein Durchziehen
bis `Restaurant` hätte Repository-Filter, `RestaurantTransformer` (Boolean-Vertrag der iOS-API), 5 Templates und
Fixtures berührt.
**Migration `Version20260809000000`:** `TINYINT(1)` → `VARCHAR(10) NULL` (kein natives ENUM wegen MariaDB 10.5),
Daten `1 → 'yes'`, `0 → 'unknown'` (nicht `'no'`, weil leeres Häkchen unter dem alten Hint „unbekannt" hieß).
**Übersetzungen:** Block `tristate:` mit **gequoteten** Keys (`"yes"`/`"no"`/`"unknown"`),
`community.suggest.step_incomplete`, `suggestion.answer_required` in `validators.*`.

## Entity: PartnerWaitlistEntry (Partnerprogramm-Warteliste)
Anmeldung für das kostenpflichtige Partnerprogramm; Preise offen → **keine Zahlung, kein Account**.
Felder: id, restaurantName (180), contactName (120), email (180), phone (40 nullable), locality (120), restaurant
(ManyToOne nullable SET NULL), message, status (enumType), confirmationToken (64 nullable UNIQUE), confirmedAt,
consentAt (NOT NULL), locale (5), source (60 nullable), createdAt, updatedAt. Enum `PartnerWaitlistStatus`
(`pending`/`confirmed`/`contacted`/`converted`/`declined`, mit `transKey/label/emoji/badgeClasses`). Repository
`findPendingOlderThan/findFiltered/countByStatus/findOneByConfirmationToken`. Migration `Version20260820000000`
inkl. Kombi-Index `(status, created_at)`, **auch im Entity-Mapping** deklariert (sonst meldet
`doctrine:schema:validate` eine Abweichung).

**`updatedAt` per `#[ORM\PreUpdate]`** (erstes Lifecycle-Callback im Projekt); im Konstruktor initialisiert, da
`PreUpdate` beim ersten `persist()` nicht feuert.
**Token bleibt nach der Bestätigung stehen** (anders als `User::verificationToken`): nur so lässt sich zweiter Klick
(„bereits bestätigt") von unbekanntem Token („ungültig") unterscheiden. `confirm()` rendert drei Zustände, wirft nie.
**Honeypot ohne `Blank`-Constraint** (ein Fehler verriete dem Bot die Falle): Controller prüft das Feld, liefert bei
Treffer dieselbe Erfolgsantwort ohne Speichern/Mail. Kein `type="hidden"` (füllen Bots), sondern per CSS aus dem
Blick, mit `aria-hidden="true"` + `tabindex="-1"`.
**Rate-Limiter `partner_waitlist`** (5/IP/Std, `#[Autowire(service: 'limiter.partner_waitlist')]`). ⚠️ `when@test`-
Override 10000 Pflicht.
**Erster Turbo-Stream im Projekt.** Erfolgsfall: `TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()` →
`setRequestFormat()` → `partner/success.stream.html.twig` ersetzt per `action="replace" target="partner-waitlist-
form"` nur das Formular (kein `<turbo-frame>`). **Fehlerfall braucht keinen Stream**: `AbstractController::render()`
setzt für submitted-invalid selbst 422 (`AbstractController.php:473`), Turbo rendert 4xx-HTML an Ort und Stelle —
`setRequestFormat()` dort **nicht** aufrufen (Antwort muss `text/html` bleiben).
**`app.contact_email`** (env `CONTACT_EMAIL`): Empfänger der internen Meldung bei Bestätigung. Fallback-Parameter
statt leerem Default (leere Empfängeradresse würfe). Interne Mail fest auf Deutsch (`trans(…, null, 'de')`); die
Bestätigungsmail geht im Submit-Request raus und erbt dessen Locale.

## Entity: OrganisationWaitlistEntry (Gemeinden, Unternehmen, Vereine)
Zweite Warteliste unter `/organisationen`. Drei Typen (`App\Enum\OrganisationType`), kommerziell verschieden:
`commune` = bezahlter Auftrag, `company` = Sponsoring, `association` = **kein Vertriebskanal** (Beirat).
Gemeinsame Felder: type, organisationName, contactName, contactRole, email, phone, website, message, status,
confirmationToken, confirmedAt, consentAt, locale, source, createdAt, updatedAt. Typspezifisch (nullable):
`communeName`, `estimatedVenues`, `timeframe` (commune) · `sponsorshipInterests` JSON (company) ·
`collaborationInterests` JSON (association). Enums `OrganisationTimeframe`, `SponsorshipInterest`,
`CollaborationInterest`.

**Seitenstruktur:** `/organisationen` = Übersicht (Hero, drei Karten, Integritätsblock, Formular mit freier
Typwahl). Jede Zielgruppe hat eine **eigene Seite** unter `/organisationen/{slug}` (`OrganisationType::slug()` →
`gemeinden`, `unternehmen`, `vereine`; für ASSOCIATION bewusst „vereine"). Inhalte in
`organisation/_section_{type}.html.twig`, nur dort eingebunden (Übersicht zeigt nur Teaser, kein Doppeltext). Auf
Unterseiten Formulartyp vorgewählt, Selektor bleibt sichtbar. `_integrity.html.twig` auf allen vier Seiten.

⚠️ **Das Formular-Partial `organisation/_form.html.twig` trägt sein Ziel ausdrücklich** (`action` →
`app_organisations_submit`, BF-151). Es hängt in Übersicht **und** auf drei Zielgruppenseiten; ohne `action` schickt
der Browser an die aktuelle Adresse, und nur die Übersicht ist zugleich POST-Route. Bis 2026-09-13 endete deshalb
jede Eintragung von `/organisationen/{gemeinden,unternehmen,vereine}` in einer **405-Fehlerseite** — auf Produktion,
Sentry sah es nicht (405 ignoriert), kein Test merkte es (jeder Absende-Test holte das Formular von der Übersicht).
**Wer ein Partial auf mehreren Seiten einbindet, gibt ihm ein Ziel und schickt es im Prüflauf von jeder Seite ab**
(`Qa11ZielgruppenFormularTest`, `OrganisationControllerTest::testBf151…`).
Repository `findByType(type, ?status)` (nimmt bewusst Strings aus Query-Params, verwirft Unbekanntes statt zu
werfen), `findFiltered/countByStatus/countByType`. Migration `Version20260820100000`.

**Typabhängige Validierung — zwei Schichten:** (1) `validation_groups` leitet die Gruppe aus `$type` ab
(`['Default','commune']` usw.), fremde Felder tragen in anderen Gruppen `IsNull`/`Count(max:0)`; (2) `PRE_SUBMIT`
baut nur die Felder des **übermittelten** Typs auf → untergeschobenes Fremdfeld = **422**, nicht stilles Ignorieren.
⚠️ **`PRE_SET_DATA` baut dagegen ALLE Blöcke auf** — Voraussetzung für JS-freie Bedienung (ohne JS alle drei
Feldgruppen sichtbar). Auf den aktuellen Typ eingeschränkt wäre die Seite ohne JS unbenutzbar.
**Choices sind reine Strings, keine Enum-Cases.** Die JSON-Spalten speichern `string[]`; Enum-Cases als `choices`
fänden Model-/Choice-Werte nicht zueinander und bräuchten einen Transformer. Array-Schlüssel = Übersetzungsschlüssel
(`enumChoices()`).
⚠️ **Bei `expanded: true` ist `choice.vars.data` der Checked-Zustand (bool), nicht der Enum-Case.** Für Emoji/Label
im Template Map `value → Case` aus `types` bauen (siehe `organisation/_form.html.twig`).
**Stimulus `organisation_type_controller.ts`** blendet Blöcke um und setzt `disabled` auf nicht gewählten Typen
(nimmt sie aus der Tab-Reihenfolge); Wechsel in `aria-live` angesagt.

## Geteilte Wartelisten-Mechanik (`src/Waitlist/`)
`WaitlistConfirmationService` kapselt Double-Opt-In beider Wartelisten: `register()` (Token → flush → absolute URL →
Mail), `confirm()` (`RESULT_CONFIRMED|ALREADY|INVALID`), `notifyTeam()`. Reihenfolge Token → flush → Mail ist
wesentlich (scheitert der Transport, ist die Anmeldung trotzdem gespeichert). `WaitlistEntryInterface` = gemeinsamer
Vertrag; `WaitlistRequestHelper::resolveSource()` liest UTM/Referrer-Host. Geteilte Templates
`partials/_waitlist_success.html.twig`, `_waitlist_confirmation.html.twig`. Gemeinsames `App\Enum\WaitlistStatus`
(pending, confirmed, contacted, **qualified**, converted, declined) — `qualified` zwischen Kontakt und Abschluss
(Gemeinden/Unternehmen haben oft eine Vorprüfung).
**Admin `/admin/warteliste` zeigt beide Typen kombiniert:** Controller normalisiert beide zu einheitlichen Zeilen
(Template braucht keine Entity-Fallunterscheidung); nach dem Merge erneut sortiert (sonst erst alle Partner-, dann
alle Organisationseinträge). Gesetzter Organisationstyp impliziert Quelle „Organisation".

## Barrierefreies Formular-Partial (`templates/partials/_form_field.html.twig`)
Kapselt Label, Pflicht-/Optional-Hinweis, Widget, Hilfetext, Fehlermeldung samt `aria-describedby`/`aria-invalid`.
Löst den Input-Klassenstring ab, der in `community/vorschlagen.html.twig` **zehnmal** wortgleich stand.
⚠️ **In `attr` unterdrückt nur `false` ein Attribut, nicht `null`.** `'aria-invalid': null` rendert
`aria-invalid=""` — Screenreader lesen „ungültig" (siehe `form_div_layout.html.twig`, Block `attributes`).
Bewusst ein Include, **kein** Form-Theme (ein Theme schlüge global auf Wizard/Admin/Profil durch).
Der Fehlercontainer existiert auch im Gutfall leer (`aria-describedby` zeigt nie ins Leere). Fokus ist echtes
`outline` statt `box-shadow`-Ring (Ringe verschwinden im Windows-Kontrastmodus; deshalb nirgends `outline-none`).
**Fokus ohne JS:** erstes fehlerhaftes Feld bekommt serverseitig `autofocus` (Browser + Turbo fokussieren beim
422-Render nativ). **FAQ ohne `aria-expanded`:** `<details>/<summary>` meldet seinen Zustand selbst; ein
handgeschriebenes `aria-expanded` ließe sich ohne JS nicht aktualisieren.

## Open-Startup-Seite (`/open`)

Öffentliche Transparenzseite, drei Blöcke: **Plattform** (live), **Wirkung** (live), **Finanzen** (manuell im
Admin). Dazu maschinenlesbare Endpunkte + offener Datensatz unter CC BY 4.0.
**Namensraum `App\Open\`** (nicht `App\Service\`; eigene Begriffe: Punktzahl, Gemeindezuordnung, Snapshot). Nicht
verwechseln mit `App\Controller\Open\`.
- `OpenStatsService` — `platform()`, `impact()`, `finance()`, `all()` (gecacht), `computeAll()` (ungecacht, für
  Snapshot). **Alle Rückgaben sind reine Arrays aus Skalaren** (dieselbe Struktur durch Cache, Twig, `/open.json`,
  Snapshot; Enums/Entities liefen auseinander). `invalidate()` wirft den Cache weg (nach jeder Finanzänderung).
- `CantonResolver` — `Restaurant::$city` → Gemeinde + Kanton. Alle **100 Gemeinden in 12 Kantonen** (nach den
  Fusionen 1.1.2024) + Alias-Tabelle. ⚠️ **Gemeinde- und Alias-Index getrennt**: beim Zerlegen („Rue de la Gare,
  Strassen") dürfen nur echte Gemeindenamen greifen (läge „gare" im selben Topf, landete der Eintrag in Luxemburg).
  Unbekannter Wert wird **nicht geraten**, sondern als unzugeordnet ausgewiesen.
- `AccessibilityScore` — 0–10 aus acht gleichgewichteten Merkmalen; nicht erfasst zählt als nicht erfüllt (misst
  *dokumentierte* Barrierefreiheit).
- `MetricSnapshotService` — `capture(?month, force)`, idempotent, `defaultMonth()` = abgeschlossener **Vormonat**
  (der Erste hält den Endstand fest; würde er den laufenden Monat schreiben, endete jeder Verlauf mit künstlichem
  Einbruch).

**Entity `FinanceEntry`:** `date` (Spalte `entry_date` — `date` in MySQL reserviert), `type` (enum, redundant zu
`category->type()`, aber indiziert für Aggregation), `category` (enum), `amount` (DECIMAL 10,2, **immer positiv** —
Richtung steckt in `type`), `quantity` (nur Inclusion Boxes), `note`, `createdAt`, `updatedAt`.
⚠️ **Es gibt keinen `setType()`.** `setCategory()` setzt die Richtung mit und räumt `quantity` weg, wenn die
Kategorie keine Menge führt (eine Ausgabe unter einer Einnahmekategorie wäre in der Summe nicht mehr als Fehler
erkennbar). ⚠️ **`setAmount()` normalisiert auf zwei Nachkommastellen** (`MoneyType` liefert `"42.5"`, DB `"42.50"`).
Kein Feld für Vertragspartner/Restaurant/Rechnungsnummer (was nicht erfasst ist, kann nicht veröffentlicht werden).

**Quartalssperre für Einnahmen:** sichtbar ab dem Tag nach Ablauf des Quartals, in dem der erste Einnahmeposten
liegt. **Strukturell, nicht kosmetisch** — die Beträge stehen gar nicht im Ergebnis-Array von `computeFinance()`
(sonst über `/open.json` abrufbar). Der **Snapshot speichert die Summe trotzdem** (direkt aus dem Repository), sonst
stünde für die Anfangsmonate eine 0 in der Historie.

**Entity `MetricSnapshot`:** `capturedFor` (DATE, **unique** → Idempotenz auf DB-Ebene), typisierte Spalten für die
Verlaufsgrafiken + `payload` (JSON, vollständige Momentaufnahme). Grund: ein zurückgerechneter Verlauf änderte sich
rückwirkend bei jeder Bearbeitung — als Beleg gegenüber einem Ministerium wertlos.

**Zeitplan:** `src/Scheduler/MetricsScheduleProvider.php` (`#[AsSchedule('metrics')]`, `RecurringMessage::cron('15
3 1 * *', …, Europe/Luxembourg)`) → `CaptureMetricSnapshot` → `CaptureMetricSnapshotHandler`. Consumer auf
`scheduler_metrics`; der frühere Cron auf `app:metrics:snapshot` ist seit 2026-09-02 abgelöst. Verpasste
Monatsläufe werden einzeln nachgeholt; Befehl weiterhin mit `--month=YYYY-MM`/`--force`. Admin-Knopf
`admin_finance_snapshot`, weil eine ausgefallene Historie sonst unbemerkt bliebe.
**Cache:** eigener Pool `cache.open_stats` (Filesystem, TTL 3600; `when@test` array) statt `cache.app`, damit
`clear()` nicht den halben Anwendungscache mitnimmt.
**Daten-Endpunkte** (`src/Controller/Open/OpenDataController.php`, locale-frei): `/open.json`, `/open/dataset.csv`,
`…json`. Der Datensatz enthält **keine** E-Mail/Telefon (wäre eine Adressliste, kein Barrierefreiheits-Datensatz).
Kein UTF-8-BOM im CSV (landete im ersten Spaltennamen jedes Parsers).
⚠️ **`AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER` ist Pflicht**, sonst überschreibt Symfonys
Session-Listener `public, max-age=3600` mit `private, must-revalidate`, sobald eine Session angefasst wurde.
**Templates:** `open/index.html.twig` + `_metric`, `_bar`, `_histogram`, `_sparkline`. Aufbau wie die Außenseiten:
Hero-Verlauf `from-cyan-700 to-purple-800`, Sektionsbänder weiß/`bg-gray-50`, Emoji in `bg-cyan-50`,
`motion-safe:transition`, `focus:outline-2`, `min-h-[48px]`. Die Zahl der Restaurants ist die **Leitzahl im Hero**.

**Diagramm-Regeln:**
- **Eine Farbe je Serie.** Die frühere Ampel (grün/cyan/bernstein) kodierte die Balkenlänge doppelt; Position trägt
  die Ordnung, bernstein lag bei 1,49:1 Kontrast.
- **Ausgaben Cyan, Einnahmen Purple** (Marken-Hues, ΔE 26,4 normal / 13,6 Deuteranopie, beide > 3:1 gegen Weiß).
  ⚠️ **Kein Bernstein für Ausgaben** (Warnfarbe, ließe Betriebskosten wie ein Problem aussehen).
- **Balken:** 4 px runde Datenkante, eckig an der Grundlinie; Spur = hellere Stufe derselben Farbe. Balken
  `aria-hidden` (die Zahl daneben trägt die Aussage).
- **Histogramm** (`_histogram`): Säulen, keine gestapelten Querbalken (Verteilungsform liest man nur nebeneinander).
  Säulen auf **85 %** (obere 15 % = Streifen fürs Wertlabel); alle mit Höchstwert beschriftet.
- **Verlaufslinie** (`_sparkline`): reines SVG, keine Bibliothek. ⚠️ **Keine `<circle>`-Punkte** —
  `preserveAspectRatio="none"` streckt das System und macht Kreise zu Ellipsen; aktueller Wert steht als Zahl
  darüber. Strichstärke 2 px über `vector-effect="non-scaling-stroke"`.
- Jede Grafik hat eine Tabellen-Entsprechung (`<details>` bzw. Kanton-Tabelle `id="canton-coverage"`).

**Zahlen** über `format_number`/`format_currency` (`twig/intl-extra`), nicht `number_format` (sonst „27,3 %" in der
englischen Fassung). **Deltas** liefert `OpenController::deltas()` gegen `MetricSnapshotRepository::findLatest()` —
Bezug ist der Snapshot, nicht „vor 30 Tagen"; ohne Snapshot **keine** Deltas. **Veralterung:** ab 60 Tagen wechselt
der „Stand vom"-Hinweis von grauem Kleingedruckten in einen `bg-amber-50`-Kasten.
**Druckansicht:** `print:hidden` auf Header/Footer/Bottom-Nav/Cookie-Banner in `base.html.twig`; der `@media print`-
Block in `app.css` nimmt den Verlaufsbändern die Fläche **samt Textfarbe der Nachfahren** (sonst weiß auf weiß),
klappt `<details>` auf, verhindert Umbrüche in Diagrammen. `print-color-adjust: exact` (Balkenfarben sind Daten).
**Migration** `Version20260820200000` (`finance_entry`, `metric_snapshot`, `restaurant.door_width_cm`,
`table_spacing_cm`).
**Restaurant-Maße:** `doorWidthCm`/`tableSpacingCm` (`?int`), Konstanten `MIN_DOOR_WIDTH_CM`/`MIN_TABLE_SPACING_CM`
(90, DIN 18040). Helper `hasWideDoors()`/`hasWheelchairTableSpacing()` → `?bool` (`null` = nicht ausgemessen). In
der iOS-API im eigenen Block `measurements`, **nicht** in `accessibility` (dort ist jeder Wert Boolean, `null` wäre
ein Kompatibilitätsbruch).

### Data Fixtures
- **Restaurants:** 11 Luxembourg (`RestaurantFixtures`) mit allen Accessibility-, Payment-, Dietary-,
  Verification-, Kontakt-/Social-Feldern, Ordering-Options und Koordinaten. 3 verifiziert (Pizzeria Bella Vista,
  Sushi Zen, Green Bowl); 7 mit Ordering-Options (+ Burger & Co., Le Jardin Brasserie, Trattoria Roma; Plattformen
  inkl. Wolt/Wedely/Goosty). Kontaktdaten variieren. Alle 11 mit echten Koordinaten; Brasserie du Grund mit
  `nearbyStopsNote`. Zusätzlich `doorWidthCm`/`tableSpacingCm`: vier ohne Maß, zwei dokumentiert unter 90 cm.
- **User:** `admin@endlech.lu`/`admin123` (ROLE_ADMIN, verified), `user@endlech.lu`/`user123` (ROLE_USER, verified),
  `unverified@endlech.lu`/`unverified123` (ROLE_USER, unverified). References `UserFixtures::REFERENCE_ADMIN/USER/UNVERIFIED`.
- **Finance:** `FinanceEntryFixtures` — zwölf Monate laufende Kosten, Domain, Apple Developer, zwei Inclusion-Box-
  Lieferungen mit Stückzahl, dazu zwei Einnahmen **im laufenden Quartal** (Absicht: so greift die Quartalssperre lokal).

### Database
- MySQL 8.0 via Docker Compose (`compose.yaml`) Port 3306.
- Migrations-Namespace `DoctrineMigrations` (nicht `App\Migrations`), Pfad `migrations/`.
- `DATABASE_URL` in `.env.local`: `mysql://root:root@127.0.0.1:3306/endlech?serverVersion=8.0&charset=utf8mb4`.

### Frontend
- Entry `assets/app.ts` (Encore → `public/build/`). Stimulus-Controller in `assets/controllers/*.ts` (auto-discovered).
- TypeScript `tsconfig.json` (`strict`, ES2020, `noEmit`); Encore `enableTypeScriptLoader()` `transpileOnly`. ESLint
  Flat-Config (`eslint.config.mjs`). Tailwind v4 via PostCSS (`postcss.config.mjs`). CSRF = double-submit cookie
  (`csrf_protection_controller.ts`).
- **Encore:** Output `public/build/`; PostCSS, Stimulus bridge, ts-loader, code splitting, source maps (dev), Hashing
  (prod). Config `webpack.config.js`.

## Code Style
PHP 4-space (PSR-4, `make fix`), YAML 2-space, TS/JS/CSS 4-space, LF, UTF-8, Trailing-Whitespace getrimmt (außer
`.md`). Siehe `.editorconfig`.

## Docker Services
`compose.yaml` + `compose.override.yaml`: `database` (mysql:8.0, 3306), `mailer` (axllent/mailpit, 1025 SMTP + 8025 UI).

## Environment Files
| Datei | Zweck |
|---|---|
| `.env` | Default (committed, non-secret) |
| `.env.dev` | Dev-Overrides (dev-only Secret) |
| `.env.test` | Test (`APP_ENV=test`) |
| `.env.local` | Lokale Overrides (gitignored, Secrets); `APP_SECRET` für Production hier |

### Email / Mailer
- **Prod:** Brevo API (`MAILER_DSN=brevo+api://KEY@default` in `.env.local`).
- **Dev:** Mailpit (`smtp://localhost:1025`, UI `:8025`). **Default:** `null://null` (verworfen) in `.env`.
- Sender global via `MAILER_SENDER_ADDRESS`/`_NAME` (`mailer.yaml`). Async via Messenger (Doctrine-Transport). Alle
  Mails extend `email/base.html.twig`. Controller fangen `TransportExceptionInterface` und zeigen Flash.

## Öffentliche Roadmap und Changelog (`/roadmap`, `/changelog`, Feature 07)

Zwei Leseseiten: `/roadmap` in drei Spalten (In Arbeit · Geplant · Angedacht) + Block „Bewusst nicht gebaut";
`/changelog` je Release ein Text, nach Jahren. **Keine Entity/Migration** — Wertobjekte unter `App\Roadmap\`, Domains
`roadmap` und `changelog`. Aufbau wie Feature 03/05.

⚠ **An keinem Roadmap-Eintrag steht ein Datum** — strukturell: `RoadmapItem` hat kein Datumsfeld. Ein gerissener
Termin kostet mehr Glaubwürdigkeit, als eine Zahl einbringt.
⚠ **Der Begründungssatz gehört zum Wertobjekt.** `RoadmapItem::reasonKey()` existiert immer, `RoadmapCatalogueTest`
verlangt ihn in vier Sprachen (AK-05, AK-29 erzwungen).
⚠ **`ReleaseVisibility` ist dreiwertig** (`SHOWN`/`SUMMARISED`/`SILENT`), nicht `bool`. Der Entwurf sah `public:
bool` vor; das trägt die Sammelzeile für die Aufbauphase nicht — dasselbe Zwei-Bedeutungen-Muster wie BF-89.
⚠ **Community-Ideen werden live abgefragt, nicht kopiert** (eine zurückgezogene Idee bliebe sonst stehen). Höchstens
zehn, nach Zustimmungen — **die Grenze steht in der Abfrage**, nicht der Darstellung (kein Aufruf lädt den Bestand).
Deshalb bewusst **kein Rate Limit** (ein Deckel auf einer Leseseite träfe Besucher).
⚠ **`RoadmapCacheListener` hängt auch an `User::postRemove`.** Beim Kontolöschen fallen die Stimmen über die
FK-Kaskade **in der DB** weg; Doctrine feuert kein `BoardVote`-Ereignis. Ohne diesen Fall stünde bis zu eine Stunde
eine zu hohe Zahl.
⚠ **Der Cache ist über HTTP nicht testbar** (Testclient bootet neu, `services_resetter` leert den Array-Adapter
zwischen Requests). Nachweis in `tests/Integration/Roadmap/CommunityRoadmapTest.php`.
⚠ **Kein Verzeichnis `public/roadmap`/`public/changelog`** — sonst BF-100. `RouteDirectoryCollisionTest` prüft
projektweit.
⚠ **Pluralformen brauchen den Fall `{0}`** (frische Idee hat null Zustimmungen; ohne `{0}` wirft Symfony → 500).

## Versioning

**CalVer** `vYYYY.MM.DD` (siehe `CHANGELOG.md`). **Mehrere Releases am selben Tag mit Punkt:** `v2026.08.29`,
`.1`, `.2`. Erster Release eines Tages **ohne** Suffix. Die frühere Buchstabenform (`2026.03.08b` bis `e`) bleibt
**Historie**, wird nicht fortgeführt (die Punktform sortiert richtig).

**Bei jedem Release fünf Stellen mitziehen** (liegen auseinander, mehrfach vergessen — README-Badge stand zwei
Releases falsch, der Footer eines):
1. `CHANGELOG.md` — `[Unreleased]` zu `[YYYY.MM.DD] – Titel` schließen **und** Badge in Zeile 5.
2. `README.md` — Version-Badge (`v`-Präfix).
3. `config/services.yaml` — Parameter `app.version` (via `twig.yaml` global `app_version`, im Footer gerendert). **Die
   einzige Stelle, die Besucher sehen.**
4. Git-Tag `vYYYY.MM.DD` auf dem Release-Commit in `main`, dann `gh release create`.
5. **`App\Roadmap\ChangelogRegistry::notes()`** — neue Version eintragen, entweder `ReleaseVisibility::SHOWN` **und**
   Text in allen vier `changelog.*.yaml`, oder `SILENT` (wenn ein Gast es nicht merkt).

⚠ **Punkt 5 ist der einzige, den ein Prüflauf erzwingt.** `ChangelogCompletenessTest` liest die `## [version]`-
Überschriften aus `CHANGELOG.md` und färbt rot, sobald eine Version weder öffentlichen Eintrag noch Vermerk trägt.
Dafür ist `ReleaseVisibility` dreiwertig (sonst wären „bewusst still" und „vergessen" ununterscheidbar).
⚠ **Im selben Handgriff die Roadmap durchsehen** (Feature 07, OF-03). Dabei: **Ein Vorhaben, das zwölf Monate ohne
Fortschritt in „Geplant" steht, wandert nach „Angedacht" zurück** (OF-04).

Konvention: Release-Commit direkt auf `main` (`Release vYYYY.MM.DD – Titel`), Tag darauf, dann Merge `main` →
`master` (= Deploy).
⚠ **`master` ist der Produktionszweig, `main` der Entwicklungszweig** — umgekehrt zur verbreiteten Lesart. Bis
2026-09-02 hießen sie `dev` und `production`.

## CI

`.github/workflows/ci.yml` (Trigger **nur** `workflow_dispatch`; Push/PR bewusst aus, Lauf per „Run workflow" bzw.
`gh workflow run ci.yml`):
- **`tests`** — PHP 8.4 (`setup-php`, Extensions inkl. `pdo_mysql`, `gd`, `intl`), MySQL-8.0-Service, Composer
  (gecacht, `--no-scripts`), JWT-Keypair, Test-DB, dann `php bin/phpunit`.
- **`frontend`** — Node 20, `npm ci`, `npm run typecheck`, `npm run lint`.

`.github/` enthält außerdem Issue-Templates.

## Sicherungen prüfen (`bin/sicherung-pruefen.sh`, BE-03)

`make sicherung-pruefen DATEI=…` spielt eine DB-Sicherung in einen **Wegwerf-Container** ein und urteilt (Rückgabe
0/1, Zeugnis unter `qa/sicherungen/`).
⚠ **MariaDB, nicht das lokale MySQL 8.** Production fährt MariaDB; Einspielen in MySQL kann an Kollationen scheitern
→ Fehlalarm über eine gesunde Sicherung.
⚠ **Die Liste erwarteter Tabellen steht im Skript.** Wer eine Migration mit neuer Tabelle schreibt, ergänzt sie dort
(sonst gilt eine Sicherung ohne die neue Tabelle als vollständig — eine Prüfung, die ihre Erwartung aus dem Prüfling
ableitet, prüft gegen sich selbst).
⚠ **`docker exec` dort ohne `-i`, mit `< /dev/null`.** `docker exec -i` **liest stdin** — in einer `while read`-
Schleife frisst es die Datei auf, aus der die Schleife liest. Beim Bauen gemessen: der Abgleich verglich **eine
einzige** Tabelle und meldete „alle Zeilenzahlen stimmen überein" (18 von 19 Tabellen konnten fehlen, grün). Die
Schleife liest seither über Dateikennung 3, die Ausgabe nennt die Zahl verglichener Tabellen.
⚠ **Am 2026-09-12 erstmals belegt: Alle Migrationen laufen auf MariaDB 10.5 durch** (bis dahin eine Annahme).

## Puls an den externen Wächter (`app:worker:pulse`, BE-01)

Überwachung seit 2026-09-12 mit **Uptime Kuma auf einem zweiten VPS** — `/health` + ein **Push-Monitor** für den
Messenger-Consumer. ⚠️ **`/open.json` wird bewusst nicht überwacht** (2026-09-12): `/health` fragt die DB nicht ab,
ein DB-Ausfall bleibt in Kuma grün und fällt nur über Sentry auf. Volle Konfiguration + Checkliste in
`docs/datenschutz.md` unter BE-01; hier nur, was beim Ändern schiefgeht.

⚠️ **Der Consumer ruft nach draußen, statt befragt zu werden — das ist der Punkt.** Ein abfragender Wächter kann
einen stehenden Prozess nicht von einem gesunden unterscheiden (der Worker serviert kein HTTP). `app:worker:pulse`
läuft alle fünf Minuten im Zeitplan `marketing` und ruft eine Push-Adresse; **das Ausbleiben des Rufs ist die
Meldung.** Damit ist der lautlose Worker-Ausfall erstmals von außen sichtbar.

⚠️⚠️ **`APP_UPTIME_PUSH_URL` gehört auf die WORKER-Ressource in Coolify, nicht die Anwendung** (zwei Ressourcen,
zwei Variablenlisten — wie `APP_SECRET`). Nur bei der Anwendung: Puls läuft **nie**, Dauer-Alarm über einen gesunden
Worker; ein grundlos weckender Wächter wird abgeschaltet — Lücke dann schlimmer offen als vorher.
⚠️ **Der Befehl gibt NIE `FAILURE` zurück.** Der Zeitplan ruft ihn über `RunCommandMessage`; ein Fehlschlag würfe
eine Ausnahme, der `failed`-Transport füllte sich mit 288 Nachrichten/Tag (wie das belegte Schloss in
`MarketingSyncCommand`). Ein unerreichbarer Wächter ist ohnehin kein App-Fehler (er alarmiert selbst).
⚠️ **`'timeout' => 5` ist Pflicht** (wie `PublicTransportService`). Ohne Vorgabe griffe `default_socket_timeout`
(60 s), ein hängender Wächter hielte den Consumer eine Minute alle fünf Minuten auf. Der `catch (\Throwable)` fängt
den **Ausfall**, nicht die **Verzögerung**.
⚠️ **Die Push-Adresse nie in ein Protokoll/Fehlermeldung geben.** Wer sie hat, schaltet den Alarm **aus** (kann
dauerhaft „alles ok" melden). Symfonys Transport-Ausnahmen führen die volle URL im **Text**; geloggt werden nur
Rechnername + Ausnahme**klasse**. `WorkerPulseCommandTest` hält das fest (mit `getMessage()` statt `$fehler::class`
wird der Lauf rot).
⚠️ **`SecretMaskingProcessor` maskiert seit 2026-09-12 auch pfadgetragene Geheimnisse.** Kumas Token steht im
**Pfad** (`/api/push/<token>`), nicht als Query — die Parameterliste griff dort nicht, und die `=`-Abkürzung in
`maskiere()` hätte eine Adresse ohne Query durchgelaufen. Pfad-Maskierung läuft deshalb **vor** dieser Abkürzung.
Nötig, weil `monolog.yaml` den `http_client`-Kanal in `prod` nicht ausschließt und `fingers_crossed` bei jeder
Warnung seinen Puffer nach `php://stderr` schreibt (zweiter Weg aus BF-45).
⚠️ **Kein `LockableTrait`**, abweichend von den beiden übrigen Zeitplan-Befehlen (dort verhindert es eine doppelte
*Handlung* mit Nebenwirkungen; zwei Pulse sind dasselbe Signal, eine Sperre wäre nur ein weiterer Ausfallweg).
⚠️ **Ein Netzproblem zwischen den Servern sieht aus wie ein toter Worker.** Die Warnung (`Puls an den externen
Wächter nicht zustellbar`) ist der einzige Unterschied — erscheint sie, war der Worker am Leben, nur der Weg versperrt.

## Sitemap, robots.txt und maßgebliche Adressen (Feature 10)

`/sitemap.xml` (Route, sprachfrei), `public/robots.txt` (statisch), canonical- + Sprachverweise im Kopf,
`X-Robots-Tag: noindex` auf Ausschlusswegen. Spec/Entwurf/Plan unter `features/10-sitemap-robots/`.

⚠️ **`App\Seo\SeoRegistry` ist die EINZIGE Quelle** — drei Klassen: angeboten (21 feste Seiten + Restaurant-
Detailseiten), ausgeschlossen (16 Wege), bewusst keins von beidem (z. B. Board-Ideen). **Wer eine öffentliche Seite
anlegt, ordnet sie dort ein** — `SeoRouteCoverageTest` wird rot, solange eine öffentliche GET-Route unterm
Sprachpräfix in keiner Klasse steht. `SeoRegistryTest` hält die 21 ausgeschrieben fest.
⚠️ **Alle Adressen fest auf `https://endlech.lu`** (`app.canonical_base_url`) — nicht aus der Anfrage
(`www.endlech.lu` liefert 200, gemessen 2026-09-12 OF-03; gecachte Sitemap hielte den Host des ersten Abrufers),
nicht aus `DEFAULT_URI` (steht in `.env` auf `http://localhost`; eine vergessene Coolify-Variable kündigte Google
`localhost` an). **Auch im Test.**
⚠️ **Folge für „keine fremde Ressource"-Prüfläufe:** canonical-/Sprachverweise sind absolut, zeigen nicht auf den
Testhost, laden nichts — ausgenommen über ihre `rel`-Art, **nicht** über den Host (`ComparisonControllerTest`,
`PressEdgeCaseTest`). Über den Host ausnehmen → rot.
⚠️⚠️ **`SitemapGenerator` fängt keinen DB-Fehler ab — das ist die tragende Eigenschaft (AK-11).** Der „robuste"
`try`/`catch` (nur feste Seiten bei scheiternder Abfrage) liefert Google eine Sitemap ohne Restaurantseiten (liest
sich als „gibt es nicht mehr"). Eine ungefangene Ausnahme wird zur 5xx, `CacheInterface::get()` speichert dann
nichts. Gegenprobe: mit `try`/`catch` wird `SitemapGeneratorTest` rot.
⚠️ **50 Minuten Speicher + `max-age=600` — die Summe ist die Zusage.** Server-Speicher (`cache.sitemap`, 3000 s) +
HTTP-Speicher addieren sich; AK-08/AK-09 versprechen „binnen 60 Minuten". `SitemapControllerTest` liest den
**Produktionswert** aus `cache.yaml` und prüft `3000 + 600 ≤ 3600`.
⚠️ **robots.txt ist eine statische Datei, keine Route** (eine 5xx darauf lässt Google zwölf Stunden nicht crawlen).
Der Symfony-Testclient erreicht sie nicht — `RobotsTxtTest` liest die Datei und wertet die Regeln aus.
⚠️ **Keine Platzhalter (`*`, `$`) in der robots.txt** (ohne sie ist die Auswertung eine reine Präfixprüfung, die
`RobotsTxtTest` nachbildet). Sperren je Sprache ausgeschrieben.
⚠️⚠️ **Die Ausschlusswege NIE in der robots.txt sperren.** Ihr `noindex` steht in einer Kopfzeile, die eine
Suchmaschine nur liest, wenn sie die Seite abrufen darf. Gesperrt könnte die Adresse ohne Inhalt in den Ergebnissen
erscheinen — das Gegenteil des Gewollten.
⚠️ **Ausschluss als Kopfzeile `X-Robots-Tag`, nicht als Meta-Element** (OF-04). Zwei Wege (E-Mail-/Adresswechsel-
Bestätigung) rendern nie eine Seite, sie leiten nur weiter.
⚠️ **Symfony setzt `X-Robots-Tag: noindex` im Debug-Modus auf JEDE Antwort** (`framework.disallow_search_engine_
index`, Vorgabe `%kernel.debug%`). `when@test` setzt deshalb `disallow_search_engine_index: false` — ohne die Zeile
wäre der Prüflauf für die 16 Ausschlusswege auch ohne `SeoRobotsHeaderSubscriber` grün. **Lokal trägt deshalb jede
Seite `noindex`** (nicht aussagekräftig); in Produktion auf gewöhnlichen Seiten nachweislich nicht vorhanden.
⚠️ **Die Schema-Prüfung braucht zwei Dateien** (`tests/Fixtures/Sitemap/`): `sitemap.xsd` prüft fremde Elemente
streng (gegen sie allein fällt jede Sitemap mit Sprachverweisen durch); geprüft wird gegen
`sitemap-mit-sprachverweisen.xsd`.
⚠️ **Von den Query-Parametern überlebt nur `page` als ganze Zahl ab 2** — für canonical UND Sprachverweise (Google:
Folgeseiten nicht auf Seite 1 kanonisieren). `?page=abc` → 400 vorher, keine Seite mit Verweis (OF-06).
⚠️⚠️ **Und nur auf Seiten, die blättern** — `SeoRegistry::PAGINATED_ROUTES` (Restaurantliste, Board-Übersicht). Bis
BF-147 galt die Regel für jede Seite: `/de/about?page=2` nannte sich selbst maßgeblich, jeder Seitenparameter erzeugte
eine selbstkanonisierende Dublette (Prüfläufe deckten nur die Restaurantliste ab, grün). **Wer eine blätternde Seite
anlegt, trägt sie dort ein** — `SeoRouteCoverageTest` gleicht die Liste mit den Controller-Methoden ab, die `page`
aus der Abfrage lesen (in beide Richtungen rot).
**Deckel `sitemap`** (60/Std/Adresse) im `RouteRateLimitSubscriber`, eigenes Kontingent (greift vor dem Controller,
auch bei gespeicherter Fassung).
⚠️ **Kein Verzeichnis `public/sitemap` und keine Datei `public/sitemap.xml`** (verdeckte die Route/friere die
Sitemap ein; Verzeichnis → BF-100).
⚠️ **Route `app_sitemap` in eigenem Block `seo`** in `routes.yaml` + `exclude`-Eintrag (Muster wie `Open/`,
`Health/`, `Marketing/`).

## Nutzungsmessung (Feature 11, Umami selbst betrieben)

Cookielose Messung mit **Umami auf dem zweiten VPS**, seit 2026-09-14 die Instanz aus dem Docker-Katalog des
Hosters, über **ihre eigene Domain**. Der Browser sieht diese Domain nie: Zählskript `public/zaehler.js`,
Zählaufrufe an `POST /api/send` auf endlech.lu, die App reicht sie **geprüft und gekürzt** per HTTPS weiter.
Spec/Entwurf/Plan unter `features/11-nutzungsmessung/`, Verarbeitungseintrag in `docs/datenschutz.md`.

⚠️⚠️ **`APP_UMAMI_UPSTREAM` ist die Umami-Domain und führt zum Überwachungs-VPS.** Nie ins Repo, kein Protokoll,
keine Fehlermeldung (wie `APP_UPTIME_PUSH_URL`); dokumentiert wäre der Weg dorthin (AK-24, AK-42). Deshalb nutzt
`UmamiForwarder` den Dienst `app.usage.umami_client` mit **`autoconfigure: false`** — mit Autokonfiguration bekäme
der Client über `LoggerAwareInterface` den Logger und der Kanal `http_client` schriebe die Adresse ins Protokoll.
Geloggt wird nur die Ausnahme**klasse**; `UmamiForwarderTest` mit `getMessage()` gegengeprüft rot.
⚠️⚠️ **Die Besucheradresse geht als Feld `ip` im Zählaufruf mit — ohne sie gibt es ein Land und eine Sitzung für
alle.** Umami läuft mit Vorgaben hinter dem Hoster-Proxy; ohne `ip` nimmt es die Adresse aus dessen Kopfzeilen (die
des Anwendungs-VPS). Nachgestellt 2026-09-14 (Umami 3.3.1): zwei Besucher landeten gemeinsam in **Argentinien**
(179er-Bereich des VPS) und in **einer** Sitzung. Eine eigene Kopfzeile liest Umami nur mit `CLIENT_IP_HEADER` (am
VPS nicht vorhanden). Vom Client im Rumpf gesetzte `ip`/`userAgent`/`timestamp`/`browser`/`os`/`device` entfernt
`CollectPayloadNormalizer` (Umami bevorzugt sie sonst).
⚠️ **`TRUSTED_PROXIES` bestimmt seither auch, was in Umami steht.** Fehlt der Wert, bekommt `ip` für jeden die
Proxy-Adresse (wie beim geteilten Rate-Limit-Deckel, nur in den Zahlen).
`CollectControllerTest::testHinterDemProxyZaehltDieAdresseDesBesuchers` hält beide Fälle fest.
⚠️ **Gewöhnliche Zertifikatsprüfung, keine Schlüsselbindung** (seit 2026-09-14; das Proxy-Zertifikat wechselt bei
Erneuerung, eine Bindung bräche still). `verify_peer` nie abschalten (sonst ginge die Besucheradresse an jeden
Zwischenmann). `APP_UMAMI_UPSTREAM_PIN` gibt es nicht mehr.
⚠️ **Zweiten Faktor in Umami nur für den Betreiber-Benutzer, nie global/Team.** Erzwungen scheitert die Anmeldung
des Growth-Loops (Lese-Benutzer ohne 2FA), Loop 2 meldet dauerhaft ab. SSH-Tunnel gibt es seit 2026-09-14 nicht mehr.
⚠️ **Die Weiterleitung ist die Grenze, nicht die Tracker-Optionen.** `data-exclude-search`/`data-domains`/
`data-do-not-track` gelten nur für ehrliche Browser. `CollectPayloadNormalizer` wiederholt jede Inhaltsregel (Pfad
ohne Abfrage, Herkunft nur als Domain, keine Verwaltungs-/Profil-/Token-Pfade, nur `endlech.lu`, `id`/`tag`
entfernt) und lässt Ereignisse nur aus `UsageEventCatalogue` durch. **Wer ein Ereignis ergänzt, trägt es dort ein**
und prüft, ob sein Wert eine Person beschreiben kann. `UsageEventCatalogueTest` gleicht die Filterschlüssel gegen
das Filterformular ab.
⚠️ **Die Pfadregeln stehen ein zweites Mal in `assets/usage/before_send.ts` (Absicht).** Turbo Drive tauscht beim
Navigieren nur den Inhalt: ein einmal geladener Tracker bliebe auf dem Weg nach `/de/admin` aktiv und zählt 300 ms
nach jedem `pushState`. Turbo ruft `pushState` schon zu Beginn — eine Markierung im neuen Inhalt käme zu spät. Die
Adresse steht dagegen im Zählaufruf selbst.
⚠️ **Eigene Auslöser (`usage_event_controller.ts`) statt `data-umami-event`.** Umamis Klick-Attribute halten bei
Links ohne `target="_blank"` die Navigation an, bis der Zählaufruf fertig ist — bei `tel:`/`mailto:` wartete der
Besucher.
⚠️ **Wer Umami aktualisiert, ersetzt `public/zaehler.js` und `app.umami_tracker_version` mit.** Das Image steht im
Docker Manager fest auf `3.3.1` (Katalog setzte `latest`, ein Neustart hätte die Version still gewechselt).
`zaehler.js` aus dem Container-Image (`/app/public/script.js`), inhaltlich unverändert; `TrackerFileTest` prüft
Version, Zählweg, `umami.disabled`, `before-send`, `do-not-track` …
⚠️ **Kein Verzeichnis `public/api`** (BF-100). Der Zählweg ist eine Route in eigenem Block `usage` in `routes.yaml`,
Firewall `usage` (`security: false`), `stateless: true` (wie `/health`).
**Deckel `usage_collect`:** 300/Std/Adresse im `RouteRateLimitSubscriber`. **Unterbrecher:** nach einem Fehlschlag
60 s keine Weiterleitung (Pool `cache.usage`), damit ein toter VPS keine PHP-Prozesse festhält. `.env.test` trägt
eine Platzhalter-Kennung (Skript im Test gerendert), Umami-Domain bleibt leer.
⚠️⚠️ **Höchstens eine Weiterleitung zur Zeit** (Sperre `umami-weiterleitung`, BF-149). Der Unterbrecher greift erst
**nach** einem Fehlschlag; bis dahin wartete jede gleichzeitige Anfrage bis zum Zeitlimit — gemessen gegen einen nie
antwortenden Eingang: sechs Zählaufrufe je 2,1–3,7 s, die Restaurantliste daneben 1,8 s TTFB (PHP-Prozesse belegt).
Wer den Platz nicht binnen 100 ms bekommt, wird **nicht gezählt**. Preis gemessen: bei gesundem Eingang gehen von
sechs exakt gleichzeitigen 0–1 verloren, bei 50 ms Abstand keiner. **Wer die Sperre für „Durchsatz" entfernt, holt
BF-149 zurück** (ein Zeitlimit begrenzt die Wartezeit eines Aufrufs, nicht die Summe gleichzeitiger).
⚠️ **Die Sperre selbst kann scheitern — dann wie ein Ausfall behandelt** (BF-152). `Lock::acquire()` fängt nur den
Konflikt ab; ein Speicher, der die Sperrdatei nicht öffnen kann, wirft weiter (ungefangen: 500 + Sentry je Aufruf).
Belegen im selben Muster wie die Weiterleitung (Klasse loggen, Unterbrecher setzen); die Sperre entsteht **ohne
automatische Freigabe** (scheitert `release()`, versucht es der Destruktor beim Verlassen von `forward()` erneut).
⚠️ **Trichterschritte in `growth/config.json`: `*` nur am Anfang oder Ende** (BF-150). Umami 3.3.1 ersetzt diese
Sterne durch `%`; ein Stern in der Mitte bleibt wörtlich. `/*/restaurants` zählte **null**, `*/restaurants` trifft
jede Sprache. `GrowthTrichterTest` bildet Umamis Abgleich nach (die Tabelle in `design.md` steht bis zur
Entscheidung über OF-07 noch falsch).
⚠️ **`PressEdgeCaseTest` nimmt den Messauslöser am Presse-Kit ausdrücklich aus** (die Seite braucht kein JS, der
Link ist ein Download). Jede andere Stimulus-Anbindung im Hauptbereich bleibt rot.

## Deployment (CD)

**Ein Merge nach `master` ist die Voraussetzung, nicht der Deploy selbst.** Coolify **beobachtet den Zweig nicht** —
das Ausrollen wird in der Oberfläche von Hand angestoßen; danach baut es aus dem `Dockerfile` und tauscht den
Container. Zwei Ressourcen aus derselben Datei: Anwendung (`--target runtime`) und Worker (`--target worker`).
⚠️ **Bis 2026-09-05 stand hier „Coolify beobachtet den Zweig".** Falsch — fiel beim Ausliefern von Feature 08 auf
(zehn Minuten passierte nichts, sah aus wie ein fehlgeschlagener Build).
**Nach dem Merge: in Coolify das Ausrollen starten** (Container-Tausch ~3 Min; die Versionsangabe im Footer zeigt,
ob durch).
⚠️ **Seit 2026-09-02 kein SSH-Deploy mehr.** `.github/workflows/cd.yml` und `.github/deploy.sh` entfernt, Cloudways
abgelöst. Ältere Protokolle (`deploy.sh`, `verify-assets`, `~/public_html`) beschreiben den Stand bis dahin.

| entfallen | Ersatz |
|---|---|
| `verify-assets` (public/build vs. Quellen) | `assets`-Stage baut sie im Image; `.dockerignore` schließt den committeten Stand aus |
| `deploy.sh` führte Migrationen aus | **Post-Deployment-Command in Coolify** — sonst läuft keine Migration |
| Wartungsseite gegen das ENDLECH-5-Fenster | Entfällt (Container-Tausch hat kein Fenster) |
| Cron für `messenger:consume` | Die Worker-Ressource |

⚠️ **Die Migration tut hier am ehesten weh.** Vorher automatisch, jetzt eine Zeile im Coolify-Feld, die jemand
gesetzt haben muss. Ein Deploy mit neuer Entity ohne Migration meldet grün und wirft danach bei jeder betroffenen
Seite 500.
**Wo:** VPS bei **Hostinger** (`srv1947421.hstgr.cloud`, AS47583), Standort **Deutschland** (2026-09-05: 19 ms,
Düsseldorf). ⚠ Die IP liegt aus historischen Gründen in einem **LACNIC**-Bereich (179.x); wer nur `whois` fragt,
landet bei Brasilien und hält es für einen Drittlandtransfer. Ist keiner.
⚠️ **Coolify ist die Software, nicht der Anbieter.** Auftragsverarbeiter im Verzeichnis ist deshalb **Hostinger**
(Einzelheiten in `docs/datenschutz.md`).
**Production-DB:** MariaDB — neue Migrationen bleiben frei von MySQL-8-only-Syntax (`CHECK` mit JSON-Funktionen,
Window-Functions in DDL). Lokal/CI läuft MySQL 8.0, der Unterschied fällt sonst erst auf Production auf.
**Rollback:** Revert-Commit auf `master`, oder in Coolify den vorherigen Build erneut ausrollen.

### Wartungsseite: jetzt ein Handschalter
`public/index.php` prüft auf `var/maintenance` **vor** `vendor/autoload_runtime.php` und liefert dann 503 mit
`Retry-After` + `public/maintenance.html`. Von Hand:
```bash
docker exec <container> touch var/maintenance
docker exec <container> rm -f var/maintenance
```
⚠️ **Die Prüfung darf weder Container noch Autoloader brauchen** (steht deshalb vor dem `require`) — billig
(`file_exists`) und der einzige Weg, die Seite ohne Deploy stillzulegen.

### Messenger-Worker
Zweite Coolify-Ressource aus demselben Dockerfile (`--target worker`), keine Domain/Port, Restart `unless-stopped`.
Fallstricke unter „Container-Image".
⚠️ **Der Ausfall ist lautlos.** Läuft der Worker nicht, stapeln sich Nachrichten in `messenger_messages`, während
die App „erfolgreich" meldet — keine Bestätigungsmails (Registrierung, Double-Opt-In, E-Mail-Wechsel), kein Snapshot,
kein Brevo-Abgleich. Fällt erst bei einer Beschwerde auf. **Gemessen, nicht angenommen:**
```bash
php bin/console messenger:stats --env=prod        # Rückstau in async
php bin/console messenger:failed:show --env=prod  # nach 3 Versuchen aufgegeben
```
Dreistellige Zahl in `async` = Worker läuft nicht. `messenger:failed:retry` schickt Liegengebliebenes nach.
**Seit 2026-09-05 misst das `app:messenger:watch`** (statt eines Menschen): prüft den Rückstau, meldet per Mail an
`app.contact_email`, täglich 07:20 aus dem Zeitplan `marketing`; Schwelle 25 unbearbeitete oder eine >30 Min in
Zustellung. `--dry-run` prüft ohne Versand.
⚠️ **Der Befehl versendet über `TransportInterface`, nicht `MailerInterface`.** Letzterer schöbe jede Mail über den
Messenger (`SendEmailMessage: async`) — die Warnung läge in genau der Queue, vor der sie warnt. Gemessen: mit
`MailerInterface` stieg der Stand von 30 auf **31**, die 31. war die Warnung.
`MessengerWatchCommandTest::testWarnungLandetNichtInDerWarteschlange` hält das fest.
⚠️ **Diese Überwachung erkennt keinen vollständigen Stillstand** (läuft im selben Consumer). Sie sieht einen
**Rückstau** und Altlast nach Neustart; den Totalausfall sieht nur `app:worker:pulse` (Prüfung von außen, seit
2026-09-12). Puls = „der Consumer läuft", Watch = „er kommt nach".
⚠️ **Wer je wieder `sync://` setzt**, nimmt der Queue Retry + `failed`-Transport (die einzige Sichtbarkeit für
gescheiterten Versand). Bei `sync://` fingen zwölf `catch (TransportExceptionInterface)`-Blöcke in acht Dateien den
Fehler ab, **ohne zu loggen**.
⚠️ **Diese zwölf Blöcke sind seit der Umstellung toter Code** (ein Dispatch-Fehler ist eine Messenger-Exception,
keine Mailer-`TransportExceptionInterface`). Sie täuschen eine Absicherung vor; Aufräumen ist ein eigener Auftrag.
Die Tabelle ist da: `Version20260113160019` legt `messenger_messages` an, Schema deckt sich mit
`symfony/doctrine-messenger` (`auto_setup=0` unkritisch).
**Tägliche Arbeit:** Änderung unter `assets/` → `npm run build` + `public/build` mitcommitten (für Production egal,
das Image baut selbst; für Betrieb ohne Docker nötig). `.nvmrc` ist die gemeinsame Node-Version für lokal + `ci.yml`;
das Dockerfile führt sie als `ARG NODE_VERSION` (bei Wechsel beide Stellen). Neue Migration → Post-Deployment-Command
in Coolify prüfen. PHPUnit ist **kein** Deploy-Gate.

## Zeitpläne statt System-Cron (`src/Scheduler/`)

Seit 2026-09-02 laufen beide wiederkehrenden Aufgaben über Symfonys Scheduler (Container-Hosting hat keinen Cron,
zwei Auslöser für dasselbe = Doppelläufe).

| Zeitplan | Takt | Nachricht | Nachholen |
|---|---|---|---|
| `metrics` | `15 3 1 * *` | `CaptureMetricSnapshot` | **ja**, jeder verpasste Termin¹ |
| `marketing` | `*/5 * * * *` | `RunCommandMessage('app:marketing:sync')` | **nein**, genau ein Durchgang |
| `marketing` | `40 3 * * *` | `RunCommandMessage('app:app-waitlist:cleanup')` | **nein** (Feature 08) |
| `marketing` | `20 7 * * *` | `RunCommandMessage('app:messenger:watch')` | **nein** (Rückstau-Meldung) |
| `marketing` | `*/5 * * * *` | `RunCommandMessage('app:worker:pulse')` | **nein** (BE-01, Puls nach außen) |

⚠️ **Der Zeitplan `marketing` trägt vier Aufgaben, nicht eine** (die Tabelle war bis 2026-09-12 zwei Features
hinterher). Alle im **selben** Zeitplan, weil `processOnlyLastMissedRun()` am Zeitplan hängt, nicht am Eintrag — und
weil ein weiterer Zeitplan einen weiteren Transport im `messenger:consume`-Befehl kostete (an drei Orten, einer in
Coolify von Hand).

¹ ⚠️ **Nachholen heißt „der Termin wird zugestellt", nicht „der damalige Monatswert kommt zurück".**
`capture()` nimmt ohne Argument immer den Vormonat *relativ zum Laufzeitpunkt*, ein vorhandener Monat bleibt ohne
`--force` unangetastet. Gedeckt: ein Deploy/Neustart genau um 03:15 am Ersten (Lauf kommt Minuten später, richtiger
Monat). Steht der Consumer über einen Monatswechsel hinaus, bleibt die Lücke; drei nachgeholte Termine schreiben
dreimal denselben Monat, zwei verpuffen. Absicht: ein nachträglich gefüllter Monat trüge heutige Zahlen unter altem
Datum.

Antrieb ist ein einziger Consumer:
```bash
php bin/console messenger:consume async scheduler_metrics scheduler_marketing \
    --time-limit=3600 --memory-limit=192M --env=prod
```
⚠️ **`getSchedule()` MUSS das Schedule-Objekt zwischenspeichern** (`$this->schedule ??= …`). Mehrfach gerufen
(Transport, Generator, `debug:scheduler`); ohne Caching entsteht je Aufruf ein **neues** Sperrobjekt, das zweite
`acquire()` scheitert am `flock`, `MessageGenerator::getMessages()` bricht in Zeile 52 ab und liefert schweigend
nichts. Gemessen 2026-09-02: Ein Consumer lief 220 s über einen fälligen Fünf-Minuten-Takt und verarbeitete null —
`debug:scheduler` sah dabei richtig aus. **Keine Fehlermeldung dazu.**
⚠️ **Zwei Zeitpläne, weil `processOnlyLastMissedRun()` am Zeitplan hängt, nicht am Eintrag** (`MessageGenerator`
fragt `$this->schedule->shouldProcessOnlyLastMissedRun()`). Zwei Aufgaben mit gegensätzlichem Nachholbedarf passen
nicht in denselben; Preis sind zwei Transporte im Consumer-Befehl.
⚠️ **`catchUp()` gibt es in symfony/scheduler 8.0 nicht.** `Schedule` kennt `stateful()`,
`processOnlyLastMissedRun()`, `lock()`. Nachholen ist das **Standardverhalten** eines Zeitplans mit Zustand; man
schaltet es mit `processOnlyLastMissedRun(true)` ab. Beide Provider setzen das Flag ausdrücklich (erkennbar, ob
gewollt oder vergessen).
⚠️ **Der Merkposten liegt in der Datenbank, nicht unter `var/cache`.** Pool `cache.scheduler`
(`cache.adapter.doctrine_dbal`, Tabelle `cache_items`, Migration `Version20260902200000` — trägt `CREATE TABLE IF
NOT EXISTS`, weil der Adapter dieselbe Tabelle beim ersten Schreibzugriff selbst anlegt; sonst scheitert die
Migration bei JEDEM Deploy an „already exists"; 2026-09-02 eingetreten, aufgeräumt mit `doctrine:migrations:version
'…Version20260902200000' --add`). Ein Dateisystem-Pool überlebt `cache:clear` nicht (läuft bei **jedem** Deploy) —
ein leerer Merkposten heißt „letzter Lauf: jetzt", ein Monatslauf im Deploy-Fenster wäre verloren, ohne Fehlermeldung.
Nachgeprüft: `SELECT item_id FROM cache_items` zeigt `scheduler_checkpoint_metrics`/`_marketing`.
⚠️ **Im `when@test`-Block gehört `provider: ~` neben den Array-Adapter.** Pools werden verschmolzen; ohne die Zeile
bleibt `provider: doctrine.dbal.default_connection` stehen und landet als erstes Konstruktor-Argument im
`ArrayAdapter` („Argument #1 ($defaultLifetime) must be of type int, Connection given") — gemessen als 456
fehlgeschlagene Prüfläufe.
⚠️ **Beide Zeitpläne brauchen einen EIGENEN Sperrnamen** (`scheduler-metrics`, `scheduler-marketing`); geteilt
blockierte der eine den anderen — lautlos.
**Sperre auch auf Befehlsebene:** `CaptureMetricSnapshotCommand` + `MarketingSyncCommand` tragen `LockableTrait`
(Zeitplan-Lock gegen doppelte *Erzeugung*, dieser gegen doppelte *Ausführung*). ⚠️ Das `release()` im `finally` ist
Pflicht (`CaptureMetricSnapshotCommandTest` ruft `execute()` zweimal, sonst „A lock is already in place"). Ein
belegtes Schloss liefert bewusst `SUCCESS` (bei `FAILURE` würfe `RunCommandMessage` eine Ausnahme, `failed`-Transport
füllte sich im Fünf-Minuten-Takt).
**Beide Befehle bleiben von Hand aufrufbar** (`--month`, `--force`, `--limit`).
**Was das belegt:** `tests/Unit/Scheduler/CatchUpBehaviourTest.php` stellt Ausfälle mit `MockClock` nach — drei
Monate Ausfall = drei Monatsläufe, drei Tage = ein Brevo-Lauf, Gegenprobe ohne Flag = zwölf.

## Container-Image (`Dockerfile`, Coolify)

Seit 2026-09-02 **der** Auslieferungsweg: Coolify baut zwei Ressourcen, Anwendung (`--target runtime`) und Worker
(`--target worker`). Basis `dunglas/frankenphp:1-php8.4`, drei Stufen: `vendor` (Composer), `assets` (Encore),
`runtime`. **Kein FrankenPHP-Worker-Modus** (Request-pro-Prozess ist langsamer, verzeiht aber Zustandslecks).

⚠️ **PHP 8.4, nicht 8.3** (`composer.json` verlangt `>=8.4`).
⚠️ **`zip` gehört in den `vendor`-Stage, nicht die Laufzeit** (ohne Erweiterung bricht `composer install` mit „The
zip extension and unzip/7z commands are both missing"; die App braucht sie nie, `ext-zip` ist `require-dev`, nur
`app:press:package`).
⚠️ **`vendor/symfony/ux-turbo` muss VOR `npm ci` im Node-Stage liegen** — `"@symfony/ux-turbo":
"file:vendor/symfony/ux-turbo/assets"` in `package.json` ist eine Datei-Abhängigkeit in den Composer-Bestand (sonst
toter Symlink).
⚠️ **`assets/`, `templates/` und `src/` müssen im Node-Stage vollständig vorliegen** (`app.css` deklariert sie als
`@source`-Positivliste). Fehlt eines, liefert Tailwind ein fast leeres Stylesheet **ohne** Build-Fehler; Nachweis ist
der Hash (byte-identisch zum committeten Stand, blockt `verify-assets` nicht).
⚠️ **`public/build` steht in der `.dockerignore`** (der Node-Stage baut es neu, `COPY` löscht nichts — läge der Stand
im Kontext, blieben alte Hash-Dateien für immer im Bild).
⚠️ **`--no-scripts` beim `composer install` ist Pflicht** (`auto-scripts` rufen `importmap:install`, aber
`symfony/asset-mapper` ist nicht installiert — `importmap.php` ist Skeleton-Altlast, das Projekt fährt Encore).
`assets:install public` + `cache:warmup` laufen als eigene Schritte (ersteres, weil Swagger-UI aus
`public/bundles/` lädt).
⚠️ **Der Warmup braucht Platzhalter für `APP_SECRET` und `DATABASE_URL`** (letztere steht **nicht** in `.env`, nur
`.env.local`; ohne sie bootet der Kernel im Build nicht). Unkritisch (Symfony hält `%env(...)%` als Platzhalter, löst
zur Laufzeit).
⚠️ **`app` MUSS das letzte Stage bleiben** (reiner Alias auf `runtime`; Docker baut ohne `--target` das **letzte**).
Solange `worker` am Ende stand, lieferte `docker build .` und ein leeres Coolify-Target den Consumer statt der App:
Container kerngesund, meldete `healthy`, servierte kein HTTP, Proxy antwortete **502 Bad Gateway** (2026-09-02, ~1 h
Ausfall). Neues Stage **nicht** dahinter hängen.

### Worker-Stage
`FROM runtime AS worker` am Ende — in Coolify zweite Ressource über „Docker build stage target: worker". Erbt Code,
Erweiterungen, php.ini, Rechte; getauscht wird nur der Startbefehl:
```
php bin/console messenger:consume async scheduler_metrics scheduler_marketing \
    --time-limit=3600 --memory-limit=256M --env=prod
```
⚠️ **`pcntl` fehlt im FrankenPHP-Image** (nachgesehen: `php -m` listet `posix`, kein `pcntl`). Ohne es meldet
`SignalRegistry` keine Unterstützung, `messenger:consume` fängt **kein SIGTERM** ab. Gemessen 2026-09-02 mit `docker
stop`: mit `pcntl` beendet in 0 s („Received signal 15 → Stopping worker"), ohne es Exit-Code 137 (SIGKILL mitten in
einer Nachricht) — beim Doctrine-Transport bleibt sie mit `delivered_at` liegen, kommt erst nach `redeliver_timeout`
(1 h) zurück. Deshalb `install-php-extensions pcntl` im **worker**-Stage (Webserver braucht sie nie).
⚠️ **Coolify liest den `HEALTHCHECK` des Bildes NICHT** — es setzt beim Ausrollen einen eigenen auf `GET /health` mit
`wget`. Zwei Folgen (beide 2026-09-02):
1. **`wget` muss im Bild liegen** (`curl` ist da, `wget` nicht; ohne scheitert die Prüfung zehnmal mit „wget: not
   found", Rollback). Deshalb `apt-get install wget` im `runtime`-Stage.
2. **Auf der Worker-Ressource Coolifys eigenen Healthcheck abschalten** (er prüft HTTP, der Container serviert keines
   — der verworfene Container hatte bereits „[OK] Consuming messages …" geschrieben).

⚠️⚠️ **Und dann NICHT `HEALTHCHECK NONE` setzen.** Ist Coolifys Prüfung aus, fällt es auf die des Bildes zurück und
fragt `docker inspect '{{json .State.Health.Status}}'`; ein Container mit `NONE` hat kein `.State.Health`, der Deploy
bricht ab mit „template parsing error: map has no entry for key \"Health\"" (2026-09-02, direkt nach dem ersten Fix).
Der worker-Stage setzt einen **eigenen** Healthcheck: liest `/proc/1/cmdline` und prüft, ob PID 1 der Consumer ist
(kein Zusatzpaket; der Prüfprozess findet sich nicht selbst, nur `/proc/1`). Nachgestellt: `healthy` nach 10 s, mit
fremdem PID 1 Exit-Code 1.
⚠️ Das ist eine **Lebendigkeits**-, keine Fortschrittsprüfung („der Consumer läuft", nicht „er arbeitet ab" — das
beantwortet `messenger:stats`).
⚠️ **`--time-limit=3600` setzt eine Neustart-Regel voraus** (der Worker löst sich selbst ab — neuer Code, kein
Speicher; steht die Ressource auf „no restart", ist er nach einer Stunde weg — der lautlose Ausfall).
⚠️ **Der `failed`-Transport gehört NICHT in den Befehl** (Ablage für endgültig Gescheitertes, von Hand über
`messenger:failed:show`/`:retry`; mitkonsumiert schickt jede aufgegebene Nachricht sofort wieder in die Schleife).
⚠️ **App und Worker MÜSSEN dasselbe `APP_SECRET` tragen** (in Coolify zwei Variablenlisten; der Bruch ist lautlos).
`RunCommandMessage` wird beim Serialisieren **signiert** (`console.messenger.execute_command_handler` mit `['sign' =>
true]`, `MessengerPass::$signedMessageTypes`), Schlüssel ist `kernel.secret`. Gemessen: eine mit abweichendem Secret
in den `failed`-Transport geschriebene Nachricht war nicht mehr lesbar — `messenger:failed:show` brach mit „Invalid
signature for message \"RunCommandMessage\"" ab.
**Zum Logging nichts zu tun:** `when@prod` in `monolog.yaml` schreibt bereits nach `php://stderr` (JSON), die Datei
unter `var/log` gilt nur für `dev`/`test`. Start meldet `[OK] Consuming …`, ein gescheiterter Lauf `CRITICAL` im
Kanal `messenger` in `docker logs`, dazwischen Ruhe (`fingers_crossed` erst ab `error`). Kein zweiter stderr-Handler.
`SERVER_NAME`/`EXPOSE 80` erbt das Stage, beide wirkungslos (liest nur FrankenPHP, das startet nie; der Basisimage-
Entrypoint stellt nur bei führendem `-` ein `frankenphp run` voran, bei `php` läuft `exec php …`). Worker-Ressource
in Coolify **ohne Domain/Port**.
**Was das Bild nicht löst:** JWT-Schlüssel (`config/jwt/*.pem` gitignored, Volume auf `/app/config/jwt` +
`lexik:jwt:generate-keypair`), Migrationen, Upload-Volume.

## Sicherheits-Kopfzeilen (`SecurityHeadersSubscriber`)

Am 2026-09-11 gemessen: Produktion lieferte **keine einzige** Sicherheits-Kopfzeile (BF-123). Das Basisimage bringt
sie nicht mit, eine eigene Caddy-Konfiguration gibt es nicht.

⚠️ **In PHP gesetzt, nicht im Webserver.** Der naheliegende Ort (Caddyfile) erreichte auch `/build/`, aber: die
Standard-Caddyfile müsste ersetzt werden und ein Fehler nimmt die Seite offline (BF-116 genau so passiert);
`CADDY_SERVER_EXTRA_DIRECTIVES` wäre eine weitere Coolify-Variable (siehe `TRUSTED_PROXIES`); und **ein Prüflauf kann
keine Caddyfile messen**, diesen Subscriber schon. Praktisch trägt: jeder Besucher lädt zuerst ein Dokument, HSTS
gilt danach für die ganze Herkunft.
⚠️ **CSP geht als `Content-Security-Policy-Report-Only`, Absicht.** Eine zu enge scharfe Richtlinie nähme der Seite
das JavaScript (Passkey, Wizard, Turbo tot), sähe nach Frontend-Fehler aus (wie fehlendes `TRUSTED_PROXIES`). Bildet
den **gemessenen** Bestand ab: keine Inline-`<script>`, aber Inline-`style=` in 23 Dateien, deshalb `'unsafe-inline'`
nur für Stile. **Wer sie scharf schaltet**, tauscht den Kopfzeilennamen — nachdem er in der Konsole über mehrere
Seiten geprüft hat, dass kein Verstoß mehr gemeldet wird. `SecurityHeadersSubscriberTest` hält fest, dass es nicht
versehentlich passiert.
⚠️ **Kein `preload`, kein `includeSubDomains` beim HSTS** (Zusage über Namen, die es nicht gibt; Preload-Liste ist
kaum zu verlassen). HSTS **nur über HTTPS, nie im Debug** (lokal zwingt es den Browser dauerhaft auf
`https://localhost`, bis man die Herkunft aus `chrome://net-internals/#hsts` löscht).
⚠️ **Vorhandene Kopfzeilen werden nicht überschrieben.**
`expose_php = Off` steht zusätzlich im Dockerfile (`conf.d/zz-endlech.ini`) — `X-Powered-By` nannte die Patch-Version.
Doppelt abgesichert (die php.ini-Zeile sieht niemand, der ohne das Image startet).

## Route `/health`

Sprachfreie Lebendigkeitsprüfung für Docker/Coolify/LB. Eigener Loader-Block in `routes.yaml` + `exclude` (wie
`Api/V1/`, `Open/`, `Marketing/`).
⚠️ **Ohne den eigenen Block hinge sie unter `/{_locale}`** und `/health` wäre ein 302er auf `/lb/health` (jeder
Orchestrator ohne Redirect-Folge hielte den Container für krank).
⚠️ **Bewusst ohne DB-Abfrage** („läuft der PHP-Prozess", nicht „ist alles gesund"; hinge sie an der DB, nähme ein
kurzer Ausfall den Container mit, und der Neustart hülfe nichts).
⚠️ **`stateless: true` und Firewall `health` mit `security: false` sind kein Schmuck.** Der `LocaleSubscriber` fasst
sonst bei jedem Request die Sitzung an — bei 30-Sekunden-Takt ~100 000 Dateien/Jahr. Der Subscriber steigt seither
bei jeder Route mit `_stateless` aus, **bevor** er `getSession()` ruft.

## Konvention: `TRUSTED_PROXIES` hinter jedem Reverse Proxy

`framework.trusted_proxies` liest `%env(TRUSTED_PROXIES)%`, Vorgabe leer (kein Proxy vertraut). **In Coolify gehört
`private_ranges` hinein** (die App steht hinter dem Hoster-Proxy). Nur ohne Proxy bleibt der Wert leer.

⚠️ **Ohne den Wert teilen sich hinter einem Proxy ALLE Besucher einen Rate-Limit-Deckel** (`getClientIp()` liefert
die Proxy-Adresse, für jeden dieselbe) — betroffen ist jeder IP-basierte Limiter (Registrierung BF-02, Anmeldung
BF-13, Passkey BF-18, Adressänderung BF-21, API BF-30, beide Wartelisten). Der erste Angreifer sperrt alle aus und
kommt selbst mit Proxy-Wechsel daran vorbei. Zusätzlich erkennt Symfony ohne die Zeile hinter TLS-Terminierung das
Schema nicht — URLs stünden auf `http://`, in jeder Mail. Am Konto zählende Limiter (`password_change`) sind
unberührt (zweiter Grund für die Unterscheidung).
⚠️ **Die Symptome zeigen nicht auf die Ursache.** 2026-09-02 gemessen, nachdem die Variable beim Umzug fehlte:
```
Mixed Content: … loaded over HTTPS, but requested an insecure resource 'http://endlech.lu/de/login'.
TypeError: Failed to fetch   (turbo)
```
Browser blockt, Turbo meldet Netzwerkfehler, der Anmeldeknopf tut nichts — sieht nach JS-Problem aus, ist keines.
Nachweisbar: `curl -sI https://endlech.lu/ | grep -i location` (http:// = Wert fehlt). Nachgestellt mit demselben
Bild + `X-Forwarded-Proto: https`: leer → `http://…`, `private_ranges` → `https://…`.
**Kein Repo-Fix möglich:** Ein Default `private_ranges` in `.env` wäre für jeden ohne Proxy falsch (übernähme
Client-Adressen aus gefälschten `X-Forwarded-For`-Headern). Der Wert gehört in die Umgebung.

## Fehler-Tracking (Sentry)

`sentry/sentry-symfony` 5.x meldet uncaught Exceptions + Monolog ab `WARNING` an ein Sentry-Projekt in der
**EU-Region** (`ingest.de.sentry.io`, Frankfurt).

**Nur `prod`.** `bundles.php` registriert `SentryBundle` mit `['prod' => true]` — in dev/test existiert die Extension
nicht (`debug:config sentry` schlägt dort bewusst fehl); weder lokale Entwicklung noch Test senden Daten, `ci.yml`
braucht nichts. Lokaltest: `sentry:test --env=prod` mit temporärem DSN in `.env.local`.
**DSN.** `SENTRY_DSN` leer in `.env` (committed), **nur `.env.local` auf dem Server** (Repo öffentlich, committeter
DSN erlaubte Fremd-Events). Leer = lautlos inaktiv (wie `MOBILITEIT_API_KEY`); leerer Default verhindert Container-
Build-Bruch. ⚠️ Muss **vor** dem Merge nach `master` auf dem Server stehen, sonst deployt es grün und Sentry bleibt still.
**`config/packages/sentry.yaml`** (alles `when@prod`):
- `release: 'endlech@%app.version%'` (hängt am CalVer-Parameter, zieht bei jedem Release mit).
- `send_default_pii: false` (keine IPs, Cookies, Header, Nutzerdaten).
- `enable_logs: true` — **reicht allein nicht**; der Handler muss zusätzlich in `monolog.yaml` registriert sein.
- `ignore_exceptions` filtert 404/405/403/429 **und seit ENDLECH-6 auch 400** (`BadRequestHttpException`) — der 400er
  kam von einem Scanner (`/login` POST ohne Felder), `FormLoginAuthenticator` wirft dort korrekt. ⚠️ **Vor dem
  Aufnehmen einer Exception prüfen, ob das Projekt sie selbst wirft** — `BadRequestHttpException` tut es nirgends (die
  eigenen 400er sind `JsonResponse`-Rückgaben). Matching über `is_a($class, $pattern, true)` (auch Subklassen/Interfaces).
**Monolog.** `when@prod` hat den Handler `sentry_logs` (`type: service`, `id: Sentry\SentryBundle\Monolog\LogsHandler`)
neben `main`/`console`/`deprecation`. Service in `sentry.yaml` mit `Monolog\Level::Warning` (Monolog 3, nicht die
deprecatete `Monolog\Logger::WARNING`). Bewusst `LogsHandler` (Sentry-*Logs*) statt `Sentry\Monolog\Handler`
(*Issues*) — deshalb bleibt `register_error_listener` aktiv, ohne dass Exceptions doppelt gemeldet werden.
**Kein Eingriff am `ApiExceptionSubscriber` nötig:** Sentrys `ErrorListener` hängt mit Priorität **128** an
`kernel.exception`, unser Subscriber mit **10** — Sentry sieht `/api/v1`-Exceptions vor `setResponse()`.
**`zend.exception_ignore_args` bleibt auf `On`** (entgegen Sentry-Empfehlung; `Off` schriebe Funktionsargumente in
Stacktraces, potenziell Passwörter aus `AuthController` — passt nicht zu `send_default_pii: false`).
**Flex-Recipe** nur in `recipes-contrib`, wegen `extra.symfony.allow-contrib: false` übersprungen — Bundle-Eintrag,
`sentry.yaml` und `.env`-Block von Hand.

## Key Files Reference

| File | Purpose |
|---|---|
| `composer.json` / `package.json` | PHP- / NPM-Abhängigkeiten und Skripte |
| `webpack.config.js` / `postcss.config.mjs` | Encore-Build / PostCSS + Tailwind |
| `phpunit.dist.xml` | PHPUnit-Konfiguration |
| `compose.yaml` | Docker (MySQL 8.0, Mailpit) |
| `Makefile` | Development-Befehle |
| `tsconfig.json` / `eslint.config.mjs` | TypeScript / ESLint |
| `.nvmrc` | Node-Version für lokal + beide Workflows |
| `Dockerfile` | Produktions-Image, Stages `runtime` und `worker` |
| `bin/sicherung-pruefen.sh` | BE-03: Sicherung in Wegwerf-Container einspielen und urteilen |
| `src/Command/WorkerPulseCommand.php` | BE-01: Puls an Uptime Kuma; sein Ausbleiben ist die Meldung |
| `src/Seo/SeoRegistry.php` | Feature 10: einzige Quelle für Sitemap, canonical, Ausschluss |
| `public/robots.txt` | Feature 10: statisch, ohne Platzhalter; Ausschlusswege nie sperren |
| `src/Usage/UsageEventCatalogue.php` | Feature 11: einzige Ereignisliste; was fehlt, erreicht Umami nicht |
| `public/zaehler.js` | Feature 11: Umami-Tracker in fester Version — bei Umami-Update mit ersetzen |
| `importmap.php` | Symfony AssetMapper (Skeleton-Altlast) |
| `.editorconfig` | Editor-Formatierungsregeln |
| `docs/` | Datenmodell-, Design-System- und PRD-Referenz |
