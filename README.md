# Endlech.lu

An open platform to find and rate accessible restaurants in Luxembourg. Built for inclusion, community, and simplicity.

![Version](https://img.shields.io/badge/version-v2026.09.02-blue)
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

Set it as an environment variable on the Coolify app resource **before** merging
into `master`. A wrong value deploys green and only shows up when someone tries
to sign in — the browser rejects the ceremony with a `SecurityError`.

Locally the default `localhost` applies. Browsers treat `localhost` as a secure
context, but the server-side check does not: with an empty `allowed_origins`
list, `CheckAllowedOrigins` insists on HTTPS. `config/packages/webauthn.yaml`
therefore whitelists `http://localhost:8000` in a `when@dev` block — adjust the
port there if `symfony server:start` picks a different one. On production the
list stays empty on purpose, so the spec's own rule applies.

## 🚢 Deployment

A merge into `master` **is** the deploy. Coolify watches that branch, builds the
image from the `Dockerfile` in this repo and swaps the container.

```
main ──merge──▶ master ──▶ Coolify builds --target runtime ──▶ container swap
                          └──▶ Coolify builds --target worker  ──▶ container swap
```

Two resources out of the same Dockerfile: the app (`--target runtime`) and the
messenger worker (`--target worker`). See *Container image* below for what each
one needs.

**No maintenance page any more, and none needed.** The old SSH deploy overwrote
files in place, which left new PHP code sitting next to the previous release's
compiled container for roughly 35 seconds — every request a 500 (it happened on
2026-08-29). A container swap has no such window: the old container serves until
the new one is up. `public/index.php` still checks for `var/maintenance` before
loading the autoloader, but nothing creates that file automatically — it is a
**manual switch** now:

```bash
docker exec <container> touch var/maintenance   # 503 + Retry-After
docker exec <container> rm -f var/maintenance
```

**Migrations do not run themselves.** Set `php bin/console doctrine:migrations:migrate -n`
as a post-deployment command in Coolify, or run it by hand after a deploy that
carries one. This is the single biggest difference to the old setup, where
`deploy.sh` always ran them.

Rollback: push a revert commit to `master`, or redeploy the previous build from
Coolify's deployment list.

⚠️ **`public/build` is still committed, but nothing verifies it any more.** The
old `verify-assets` job rebuilt it and blocked the deploy on a mismatch; that
workflow is gone with Cloudways. It no longer matters for production — the image
builds its assets from source in the `assets` stage and `.dockerignore` excludes
the committed copy. It does still matter for anyone running the app without
Docker, so keep committing it after changes under `assets/`.

### Container image (Coolify)

`Dockerfile` and `.dockerignore` in the repo root build a production image on
`dunglas/frankenphp:1-php8.4`. Since 2026-09-02 this is **the** deployment path —
the SSH deploy to Cloudways is gone, along with `.github/workflows/cd.yml` and
`.github/deploy.sh`.

```bash
docker build -t endlech .
docker run -p 8080:80 -e APP_SECRET=… -e DATABASE_URL=… endlech
```

Three stages: Composer dependencies, Encore assets, slim runtime. `/health` returns
`{"status":"ok"}` and is what the `HEALTHCHECK` polls — no curl in the image, the
check runs through `php -r`.

**Environment variables that must be set** (everything else has a default in `.env`):

| Variable | Value |
|---|---|
| `APP_SECRET` | `openssl rand -hex 32` |
| `DATABASE_URL` | `mysql://user:pass@host:3306/endlech?serverVersion=8.0&charset=utf8mb4` |
| `TRUSTED_PROXIES` | `private_ranges` — see below |
| `DEFAULT_URI` | `https://endlech.lu`, or every mail links to `http://localhost` |
| `MAILER_DSN` | `brevo+api://KEY@default` |
| `WEBAUTHN_RP_ID` | `endlech.lu` — a wrong value only surfaces at the first passkey login |
| `CORS_ALLOW_ORIGIN` | `^https://(www\.)?endlech\.lu$` |

⚠️ **`TRUSTED_PROXIES` is not optional behind a proxy.** Without it
`Request::getClientIp()` is the proxy's address for *every* visitor, so all IP-based
limiters share a single bucket: the first attacker locks out everyone else and walks
past it themselves by switching proxies. Symfony also fails to see the scheme behind
TLS termination and generates `http://` URLs.

**The worker runs from the same Dockerfile.** Build it with
`--target worker` (in Coolify: *Docker build stage target* = `worker`) — same code,
same extensions, different command:

```bash
docker build --target worker -t endlech-worker .
```

It adds `pcntl` (missing from the FrankenPHP image) so `messenger:consume` can catch
SIGTERM: measured with `docker stop`, the worker stage exits in 0 s logging
`Received signal 15`, while the runtime stage dies with exit code 137 — SIGKILL, in
the middle of whatever it was handling. It also sets `HEALTHCHECK NONE`, because the
inherited check probes an HTTP server this container never starts.

⚠️ `--time-limit=3600` makes the worker replace itself every hour, which is how it
picks up new code after a deploy — but it needs a **restart policy**, or it is simply
gone after an hour, silently.

**Two things the image still does not solve:**

1. **JWT keys.** `config/jwt/*.pem` are gitignored and deliberately not in the image.
   Mount a volume at `/app/config/jwt` and run `lexik:jwt:generate-keypair` once, or
   every `/api/v1/auth/login` fails.
2. **Migrations and uploads.** Run `doctrine:migrations:migrate -n` as a post-deployment
   command, and mount `/app/public/uploads` — otherwise every restaurant photo is gone
   after the next deploy.

### Messenger worker

Mail is queued, not sent inside the request (`MESSENGER_TRANSPORT_DSN` defaults to
`doctrine://default?auto_setup=0`). Something has to consume that queue, and in
Coolify that is a **second resource from the same Dockerfile** — build stage target
`worker`, no domain, no port, restart policy `unless-stopped`.

⚠️ **The failure is silent.** Without a running consumer, messages pile up in
`messenger_messages` while the app keeps reporting success: nobody gets a
confirmation mail (registration, both waitlist double-opt-ins, email change), no
monthly snapshot is written, no Brevo sync runs. Nothing warns you. Measure it,
don't assume it:

```bash
php bin/console messenger:stats --env=prod        # backlog in async
php bin/console messenger:failed:show --env=prod  # gave up after 3 tries
```

A three-digit number in `async` means the worker is not running.
`messenger:failed:retry` sends the leftovers back through.

The worker's `--time-limit=3600` makes it exit every hour on purpose: it replaces
itself, picks up new code, and drops whatever memory a long-lived PHP process
accumulates. ⚠️ That only works with a **restart policy** — on "no restart" the
worker is simply gone after an hour, and that is exactly the silent failure above.

⚠️ **The `failed` transport is deliberately not consumed.** It exists for
`messenger:failed:show` / `:retry` by hand; consuming it would send every
given-up message straight back into the same loop.

⚠️ **App and worker need the same `APP_SECRET`.** They are two resources with two
separate variable lists in Coolify, so two different values are one slip away.
`RunCommandMessage` is signed on serialization with `kernel.secret`, and with a
mismatch `messenger:failed:show` aborts with `Invalid signature` — the one command
you reach for when something already went wrong.

The queue table already exists: `Version20260113160019` creates `messenger_messages`
and its schema matches what `symfony/doctrine-messenger` expects, which is why
`auto_setup=0` is harmless.

### Scheduled tasks

Both recurring jobs run through Symfony's Scheduler (`src/Scheduler/`), not through
system cron. One consumer drives them together with the mail queue:

```bash
php bin/console messenger:consume async scheduler_metrics scheduler_marketing \
    --time-limit=3600 --memory-limit=192M --env=prod
```

| Schedule | Cron | Catch-up after downtime |
|---|---|---|
| `metrics` | `15 3 1 * *` | **every** missed run — covers a deploy landing on 03:15 of the 1st |
| `marketing` | `*/5 * * * *` | **one** run only — three days down would otherwise mean 864 |

The checkpoint that makes catch-up possible lives in the **database** (pool
`cache.scheduler`, table `cache_items`), not under `var/cache`: a filesystem pool
does not survive `cache:clear`, which runs on every deploy. Inspect it with
`php bin/console debug:scheduler`.

⚠️ Both commands remain callable by hand — `app:metrics:snapshot --month=2026-08`
and `app:marketing:sync --limit=50` are unchanged. They hold a lock while running, so
a manual catch-up cannot collide with the scheduled one.

### Monthly metrics snapshot

The `/open` page shows a trend built from stored monthly snapshots, not from
recalculated history. Those snapshots are written by `app:metrics:snapshot`.

`App\Scheduler\MetricsScheduleProvider` declares the recurring task (1st of each
month, 03:15 Europe/Luxembourg). **The consumer above is what runs it** — there is
no cron entry for this any more. A run missed because the worker was down at 03:15
is delivered as soon as it comes back.

⚠️ Catch-up delivers the *trigger*, it does not reconstruct *past months*:
`capture()` always takes the month before the moment it runs, and an existing month
is left alone. If the consumer is down across a month boundary, that month stays
empty — and filling it later would only stamp today's numbers with an old date.

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
It never runs inside a request, so no signup hangs on a third party's availability.
`App\Scheduler\MarketingScheduleProvider` schedules it every five minutes and the
consumer above executes it — through `RunCommandMessage`, i.e. the exact same path a
manual call takes. **No cron entry for this any more either.**

Unlike the monthly snapshot, this one catches up **a single run** after downtime:
the command drains an order book rather than a point in time, so the first run
already does the whole backlog, and 863 further runs would only hammer Brevo.

⚠ **If you still run the messenger worker from cron, run it as the same system user
as PHP-FPM** (`nrzwptqsvx`), not the master
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

Error tracking is active on production only. `SENTRY_DSN` must be set on **both**
Coolify resources before the merge — otherwise the deploy goes green while Sentry
stays silently disabled, and errors in the worker are exactly the ones you cannot
see any other way. Verify afterwards with
`docker exec <container> php bin/console sentry:test --env=prod`.

### One-time setup in Coolify

Two resources, both **Build Pack: Dockerfile**, Dockerfile location `/Dockerfile`,
base directory `/`, branch `master`:

⚠️ **Set the stage target explicitly on both.** Docker builds the *last* stage when
none is given. The file ends with `app` (an alias for `runtime`) precisely so that an
empty field yields the application — but relying on that leaves one rename away from
serving a worker on your domain.

| | App | Worker |
|---|---|---|
| Build stage target | `runtime` (or empty) | **`worker`** — never leave empty |
| Domain | `endlech.lu` | *(none)* |
| Port | `80` | *(none)* |
| Restart policy | `unless-stopped` | `unless-stopped` |
| Healthcheck | `/health` (Coolify's own, uses `wget`) | **switch Coolify's off** — the image brings its own |

**Environment variables.** The app needs `APP_SECRET`, `DATABASE_URL`,
`TRUSTED_PROXIES=private_ranges`, `DEFAULT_URI=https://endlech.lu`, `MAILER_DSN`,
`WEBAUTHN_RP_ID=endlech.lu`, `CORS_ALLOW_ORIGIN`, plus `SENTRY_DSN`,
`MOBILITEIT_API_KEY`, `BREVO_*` and `CONTACT_EMAIL`. The worker needs the same set
minus `TRUSTED_PROXIES`, `WEBAUTHN_RP_ID` and `CORS_ALLOW_ORIGIN` — there is no
request there.

⚠️ **Switch the healthcheck off on the worker resource.** Coolify does **not** use
the `HEALTHCHECK` from the Dockerfile — it installs its own, a `wget` against
`GET /health`. The worker serves no HTTP, so that check fails ten times, Coolify
declares the fresh container unhealthy and rolls back to the old one. Measured on
2026-09-02: the container was working fine and had already logged
`[OK] Consuming messages from transports "async, scheduler_metrics, scheduler_marketing"`
when it was discarded.

⚠️ **Do not answer that with `HEALTHCHECK NONE`.** Once Coolify's own check is off it
falls back to the image's and queries `docker inspect '{{json .State.Health.Status}}'`
— a container with `NONE` has no `.State.Health` at all, and the deploy dies with
`map has no entry for key "Health"`. The worker stage therefore ships its own check:
it reads `/proc/1/cmdline` and verifies PID 1 is still the consumer. No extra package,
and the probe cannot match itself because only `/proc/1` is read.

⚠️ **`APP_SECRET` must be byte-identical in both**, see *Messenger worker* above.

⚠️ **`TRUSTED_PROXIES` is not optional behind Coolify's proxy.** Without it
`Request::getClientIp()` returns the proxy's address for *every* visitor, so all
IP-based limiters share one bucket: the first attacker locks out everyone else and
walks past it themselves by switching proxies. Symfony also fails to see HTTPS, so
every generated URL is `http://` — in mails, in redirects, and in the login form,
where the browser then blocks the submit as mixed content and Turbo reports a bare
`TypeError: Failed to fetch`. Check it from outside with
`curl -sI https://endlech.lu/ | grep -i location`: an `http://` location means the
value is missing.

⚠️ **`DEFAULT_URI` matters more in the worker than in the app.** There is no request
to derive a host from, so every link in every mail it sends comes from this value.

**Persistent storage.** `/app/public/uploads` on the app (restaurant photos and
avatars are gone after the next deploy otherwise) and `/app/config/jwt` — mount it,
then run `php bin/console lexik:jwt:generate-keypair` once inside the container, or
every `/api/v1/auth/login` fails. The keys are gitignored and deliberately not in
the image.

**Migrations.** `php bin/console doctrine:migrations:migrate -n` as a
post-deployment command. Nothing runs them for you.

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
