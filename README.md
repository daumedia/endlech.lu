# Endlech.lu

An open platform to find and rate accessible restaurants in Luxembourg. Built for inclusion, community, and simplicity.

![Version](https://img.shields.io/badge/version-v2026.08.30.1-blue)
![Status](https://img.shields.io/badge/status-beta-green)

<div align="center">
  <img src="Element 1.png" alt="Endlech.lu Logo" width="200">
</div>

## 🚀 Project Status

**The first beta version is live.**
The homepage has been redesigned as a landing page with a hero section, "How it works" steps, restaurant preview, and call-to-action areas. A dedicated restaurant listing at `/restaurants` with pagination, sorting, and full filter support (accessibility, open status, city, cuisine) is available. An admin panel at `/admin` allows ROLE_ADMIN users to fully manage (CRUD) restaurants. Transactional emails are powered by Brevo (formerly Sendinblue) with Mailpit for local development. Next up: map view.

## 🎯 Features & Progress

Current development status of the platform.

### 🏗️ Core & Backend
- [x] **Project Setup:** Symfony 8.0 installation & configuration.
- [x] **Frontend Stack:** Tailwind CSS & Webpack Encore integration.
- [x] **Database:** Schema for restaurants & users (MySQL 8.0).
- [x] **Data Seeding:** Initial Luxembourg restaurants via fixtures.
- [x] **User Fixtures:** Test users (admin, verified, unverified) for development & testing.
- [ ] **Authentication:** Login & registration for users.
- [x] **Email:** Brevo mailer integration for transactional emails (verification, password reset).
- [x] **Cookie Consent:** GDPR-compliant cookie banner with accept/decline, 365-day storage, footer re-open link, and translations (LU/DE/FR/EN).
- [x] **REST API (Issue #87):** Versioned JSON API under `/api/v1/` for the native iOS app — JWT auth (login/register), paginated & filterable restaurants, full detail, `/me` + submissions, restaurant submission. CORS, rate limiting, and auto-generated Swagger UI at `/api/docs`.
- [x] **PWA (Issue #83):** Installable as a Progressive Web App on iPhone via Safari's "Add to Home Screen" — full-screen standalone mode, web app manifest, app icons (57–512 px, incl. maskable), iOS meta tags, service worker with offline fallback, safe-area insets, a mobile bottom navigation bar, and 16 px form inputs to prevent iOS zoom. See [PWA / Install on iPhone](#-pwa--install-on-iphone).

### 🔧 Admin Panel
- [x] **Dashboard:** Admin area at `/admin` with statistics and quick actions.
- [x] **Restaurant CRUD:** Create, edit, and delete restaurants (`/admin/restaurants`).
- [x] **Form:** Full form with accessibility checkboxes and dynamic notes.
- [x] **Security:** Access control via `access_control` and `#[IsGranted('ROLE_ADMIN')]`.

### 🍽️ Restaurant Finder
- [x] **Homepage:** Landing page with hero, "How it works", top-6 restaurant preview, value proposition, and CTA.
- [x] **List View:** Dedicated `/restaurants` page with pagination (6/page) and sorting (rating, name, newest).
- [x] **Accessibility Icons:** Display of criteria (wheelchair, toilet, assistance dog, lighting, changing table, disabled parking).
- [x] **Detail Page:** Individual view with address and additional information.
- [x] **Payment Methods:** Display of accepted payment methods (cash, card, Payconiq) per restaurant.
- [x] **Filters:** Filter by accessibility criteria (wheelchair, toilet, dogs, lighting, changing table, disabled parking), open status, city, and cuisine type.
- [x] **About Us:** Updated About page with project timeline and March 2026 launch milestone.
- [x] **Photo Gallery:** Image upload per restaurant with lightbox gallery (GLightbox) on detail page and thumbnail on list view.
- [x] **Spoken Languages:** Display of spoken languages per restaurant with flag badges and filter support.
- [x] **Dietary Options:** Display of dietary options (vegan, vegetarian, halal) per restaurant with filter support.
- [x] **Contact & Social Media:** Phone, email, website, Instagram, Facebook, and TikTok links on restaurant detail pages.

### 👤 User & Community
- [ ] **User Profiles:** Save favorites.
- [ ] **Crowdsourcing:** Form to suggest new restaurants.
- [ ] **Reviews:** Comment system for accessibility feedback.

## 🔮 Roadmap

Ideas for version 2.0 (after the first stable release):

* **Multilingual:** Interface in LU, FR, EN.
* **Map:** Interactive map view (Leaflet/Google Maps).

## 🛠 Tech Stack

* **Backend:** PHP 8.4+, Symfony 8.0
* **Database:** MySQL 8.0 (Doctrine ORM)
* **Frontend:** Twig, Tailwind CSS v4 (via PostCSS)
* **JS:** TypeScript, Stimulus, Turbo (Hotwire)
* **Email:** Brevo (Symfony Mailer) / Mailpit (dev)
* **Assets:** Webpack Encore

## ⚙️ Installation & Setup

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Mukaarts/endlech.lu.git
    cd endlech.lu
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3.  **Start Docker (MySQL):**
    ```bash
    docker compose up -d
    ```

4.  **Environment variables (.env.local):**
    Create a `.env.local` file:
    ```bash
    DATABASE_URL="mysql://root:root@127.0.0.1:3306/endlech?serverVersion=8.0&charset=utf8mb4"
    APP_SECRET=your-secret-here
    # Production only (Brevo):
    # MAILER_DSN=brevo+api://YOUR_API_KEY@default
    # Production only (Sentry error tracking, prod environment only):
    # SENTRY_DSN=https://...@...ingest.de.sentry.io/...
    ```

    > **Never commit the Sentry DSN** – this repository is public. It belongs in
    > the server's `.env.local` only. An empty `SENTRY_DSN` silently disables
    > error tracking, and the bundle is registered for `prod` only, so local
    > development and the test suite never send anything.

5.  **Database & fixtures:**
    ```bash
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ```

6.  **JWT keys for the REST API (Issue #87):**
    The `/api/v1/` API signs tokens with an RSA keypair. Generate it once
    (the keys land in `config/jwt/`, which is gitignored):
    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```
    `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY` and `JWT_PASSPHRASE` are managed in `.env`
    (override the passphrase in `.env.local` for production). Optionally set
    `CORS_ALLOW_ORIGIN` to restrict API origins. Tests use the same keypair, so
    generate it before running `php bin/phpunit`.

7.  **Build assets & start server:**
    ```bash
    npm run build
    symfony server:start
    ```

    Or using `make`:
    ```bash
    make init   # Full setup (Docker, composer, npm, DB, fixtures)
    make start  # Start server + asset watcher
    ```

## 📱 PWA / Install on iPhone

Endlech.lu is a Progressive Web App. On an iPhone, open the site in Safari, tap
**Share → Add to Home Screen**. The app then launches full-screen (no browser
chrome) with its own icon, a mobile bottom navigation bar, and an offline
fallback page when the connection drops.

The PWA assets are plain static files served from the web root (locale-free):
`public/manifest.webmanifest`, `public/sw.js` (service worker), and
`public/offline.html`. The app icons live in `public/icons/` and are checked
into the repo. To regenerate them from `public/images/logo.png` (macOS, uses the
native `sips` tool):

```bash
./bin/generate-pwa-icons.sh
```

> Note: the service worker only takes effect over HTTPS (or `localhost`). After
> changing `public/sw.js`, bump the `CACHE_VERSION` constant so clients pick up
> the new version.

## 🌍 Environments

### 🛠 Development
Active development mode. Changes to templates and CSS are picked up immediately.
Tailwind runs as a PostCSS plugin inside the Encore build, so the asset watcher
is the only process you need:

```bash
npm run watch
```

### 🚀 Production
Optimized for performance and security.

```bash
npm run build
php bin/console cache:clear
```

### 🔑 Passkeys (WebAuthn)

`WEBAUTHN_RP_ID` is the bare domain passkeys are bound to — no scheme, no port,
no path, no IP address. It also covers every subdomain, but never the other way
round, so production must use `endlech.lu` and **not** `www.endlech.lu`.

Set it in the server's `~/public_html/.env.local` **before** merging into
`production`. A wrong value deploys green and only shows up when someone tries
to sign in — the browser rejects the ceremony with a `SecurityError`.

Locally the default `localhost` applies. Browsers treat `localhost` as a secure
context, but the server-side check does not: with an empty `allowed_origins`
list, `CheckAllowedOrigins` insists on HTTPS. `config/packages/webauthn.yaml`
therefore whitelists `http://localhost:8000` in a `when@dev` block — adjust the
port there if `symfony server:start` picks a different one. On production the
list stays empty on purpose, so the spec's own rule applies.

## 🚢 Deployment

A merge into `production` **is** the deploy. GitHub Actions opens an SSH session and
the server updates itself — see `.github/workflows/cd.yml` (the connection) and
`.github/deploy.sh` (everything that actually happens).

```
dev ──merge──▶ production ──push──▶ verify-assets ──▶ deploy (SSH)
```

The `verify-assets` job rebuilds `public/build` and compares it against the
committed one. **`public/build` is checked into the repo** — so whenever you
touch anything under `assets/`, run `npm run build` and commit the result, or
the deploy is blocked.

On the server, `deploy.sh` runs `git reset --hard origin/production` followed by
`git clean -fd`, then `composer install --no-dev`, the Doctrine migrations and
`cache:clear`. `git clean` runs **without** `-x`, so everything gitignored
survives: `.env.local`, `config/jwt/*.pem`, `public/uploads/`, `var/`,
`vendor/`. Production still runs with `MESSENGER_TRANSPORT_DSN=sync://`, so mail
is sent inside the request — see *Messenger worker* below for the prepared switch
to the queue.

**The deploy shows a maintenance page while it runs.** Between `git reset` and
`cache:clear` the new PHP files sit next to the compiled container of the previous
release — if that container calls a constructor that has changed, every request ends
in a 500 (this happened on 2026-08-29, `ApiRateLimitSubscriber` with two instead of
three arguments). So `deploy.sh` touches `var/maintenance` before the reset, and
`public/index.php` checks for that file **before** loading `vendor/autoload_runtime.php`
— it needs neither the container nor the autoloader, both of which may be half-written
at that moment. Visitors get a 503 with `Retry-After` and `public/maintenance.html`
for roughly 35 seconds instead of an error page.

If the deploy fails, **the maintenance page stays up on purpose** — the tree is new
and the container old, which is exactly the broken state. The red Actions run is the
signal; clear it by hand afterwards:

```bash
ssh <user>@<host> 'rm -f ~/public_html/var/maintenance'
```

Rollback: push a revert commit to `production`. The next run restores the previous
state including matching assets, because they live in the same commit.

### Messenger worker

Production still sends mail inside the request (`MESSENGER_TRANSPORT_DSN=sync://`
in `.env.local`). `deploy.sh` is already prepared for the switch to the Doctrine
queue; the switch itself is not done yet.

**Do not just delete that line.** Without a running worker the `.env` default
`doctrine://default?auto_setup=0` takes over, messages pile up in
`messenger_messages` and the app keeps reporting success — nobody receives a
confirmation mail and nothing warns you. The order is: cron first, then deploy,
then drop the line and run `cache:clear --env=prod` (the compiled container holds
the old DSN otherwise).

The table already exists — `Version20260113160019` creates it and its schema
matches what `symfony/doctrine-messenger` expects.

The worker runs as a **cron job, not under supervisor**: with `--time-limit=55` it
replaces itself every minute, so it picks up new code after a deploy on its own and
`deploy.sh` needs no `pkill` (a foreign application's `messenger:consume` runs on
the same machine under a different system user). Add it once:

```
* * * * * /usr/bin/flock -n /home/master/applications/nrzwptqsvx/public_html/var/worker.lock /usr/bin/php /home/master/applications/nrzwptqsvx/public_html/bin/console messenger:consume async --time-limit=55 --memory-limit=128M --env=prod -q
```

`flock -n` keeps the runs from overlapping and is the same lock `deploy.sh` takes
before `git reset`, so no worker touches a half-updated tree. `-q` keeps the log
quiet — real failures go to Monolog and on to Sentry. Do **not** consume the
`failed` transport here; it exists for `messenger:failed:show`/`:retry` by hand.

⚠️ The cron must run as the **same system user as PHP-FPM** (`nrzwptqsvx`), not the
master user the Cloudways panel uses by default — otherwise it fills `var/log` and
`var/cache` with files the web server cannot overwrite. Verify with a throwaway
`* * * * * id > …/var/cron-whoami.txt` before relying on it.

Check afterwards with `php bin/console messenger:stats`: a growing number means
nothing is consuming.

### Monthly metrics snapshot

The `/open` page shows a trend built from stored monthly snapshots, not from
recalculated history. Those snapshots are written by `app:metrics:snapshot`.

`App\Schedule` declares the recurring task (1st of each month, 03:15
Europe/Luxembourg), but Symfony Scheduler needs a running
`messenger:consume scheduler_default` — and production has no worker. **The cron
entry is what actually runs it there.** Add it once in the hosting panel:

```
15 3 1 * * /usr/bin/php ~/public_html/bin/console app:metrics:snapshot --env=prod --no-interaction
```

The command is idempotent: a month that already has a snapshot is left alone
(`--force` overwrites, `--month=YYYY-MM` fills a gap). If the cron is missing or
fails, nothing breaks and nothing warns you — the trend simply stays empty. The
admin page at `/admin/finanzen` shows the month of the latest snapshot and has a
button to capture one by hand, which is the fastest way to notice the gap.

A snapshot can only record what is in the database when it runs. Filling in past
months later gives them today's numbers, not the ones they had.

### Brevo marketing contacts (every 5 minutes)

Feature 04 never calls Brevo from a web request. Consent writes a row into the
order book (`marketing_contact`); `app:marketing:sync` is what carries it over.
Same reason as above — production runs `sync://` with no worker, so an
"asynchronous" message would run inside the request and hang every waitlist
signup on a third party's availability.

```
*/5 * * * * /usr/bin/php ~/public_html/bin/console app:marketing:sync --env=prod --no-interaction
```

⚠ **Run it as the same system user as PHP-FPM** (`nrzwptqsvx`), not the master
login the hosting panel defaults to. A job running as master fills `var/log` and
`var/cache` with files the web server cannot write, and the resulting 500 sends
you looking in the wrong place. Verify with a one-off `id > …/var/cron-whoami.txt`
before relying on the panel.

The five-minute interval is what makes the 15-minute promise in the spec hold,
three times over. The command exits non-zero **only** when `BREVO_API_KEY` is
missing — individual transfer failures are the expected case and get retried on
the next run, so a single 429 does not turn the cron red every five minutes.

⚠ **Before the first real run:** the five contact attributes must exist in the
Brevo account (unknown attributes are silently discarded — the sync would report
success and transfer nothing but the bare address), and both `docs/datenschutz.md`
and the privacy section on `/legal` must name Brevo as a recipient for marketing
purposes. No contact goes out before the declaration mentions it.

Error tracking is active on production only. `SENTRY_DSN` must be present in the
server's `~/public_html/.env.local` **before** the merge — otherwise the deploy
goes green while Sentry stays silently disabled. Verify afterwards over SSH with
`php bin/console sentry:test`.

### One-time server setup

The deploy directory must be a git checkout with its own deploy key. The key
belongs in `.git/` — that directory sits outside the web root, is not part of
the repo, and neither `reset --hard` nor `clean` touches it:

```bash
cd ~/public_html
tar czf /tmp/before-deploy.tar.gz .env.local config/jwt public/uploads  # backup first

git init
ssh-keygen -t ed25519 -C "endlech-deploy" -f .git/deploy_key -N ""
chmod 600 .git/deploy_key
ssh-keyscan github.com > .git/known_hosts
git config core.sshCommand "ssh -i $PWD/.git/deploy_key \
  -o IdentitiesOnly=yes -o UserKnownHostsFile=$PWD/.git/known_hosts"
cat .git/deploy_key.pub   # → GitHub → Settings → Deploy keys (no write access)

git remote add origin git@github.com:daumedia/endlech.lu.git
git fetch origin
git checkout -f -B production origin/production

git clean -nd   # DRY RUN: review this list before the first real deploy
```

That last line is the one not to skip. It shows what `git clean -fd` will delete
on the first deploy — untracked and *not* gitignored. Check upload paths
individually with `git check-ignore -v <path>`; if the command stays silent, the
file is **not** protected.

This inventory was run on 2026-08-06 against the live server: **18 orphans**, all
of them genuine leftovers (pre-TypeScript `assets/*.js`, six stale
`public/build/` hashes, the old `tests/` layout, and a Cloudways placeholder
`index.php` in the project root — harmless, the web root points at `public/`).
Everything that must survive was confirmed protected: `.env.local`,
`config/jwt/*.pem`, and all four user uploads under
`public/uploads/{avatars,restaurants}`.

Three repository secrets are required — `SSH_PRIVATE_KEY`, `APP_USER`,
`APP_HOST` — using a key pair separate from the server's deploy key.

## 📂 Structure

* `/src/Controller` — Page logic (including `AdminRestaurantController`).
* `/src/Entity` — Database models (`User`, `Restaurant`).
* `/src/Form` — Symfony forms (`RegistrationType`, `RestaurantType`).
* `/src/DataFixtures` — Initial test data.
* `/templates` — Twig templates (including `admin/` for the admin panel).
* `/assets` — Stimulus controllers and CSS.
* `/migrations` — Doctrine database migrations.
* `/docs` — In-depth documentation (see below).

## 📚 Documentation

Detailed documentation lives in [`docs/`](docs/). These documents are written in
German, matching `CHANGELOG.md` and the codebase comments.

* [`docs/prd.md`](docs/prd.md) — Product requirements: vision, target groups,
  product principles, feature scope, metrics, business model, roadmap, risks.
* [`docs/data-model.md`](docs/data-model.md) — Complete reference of all Doctrine
  entities, enums, repositories and migrations, including an ER diagram.
* [`docs/design-system.md`](docs/design-system.md) — Colours, typography,
  components, accessibility rules, charts and print styles.

---
*Built with love in Luxembourg.*
