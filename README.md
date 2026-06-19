# Endlech.lu

An open platform to find and rate accessible restaurants in Luxembourg. Built for inclusion, community, and simplicity.

![Version](https://img.shields.io/badge/version-v2026.03.14f-blue)
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
    ```

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

## 🌍 Environments

### 🛠 Development
Active development mode. Changes to templates and CSS are picked up immediately.

```bash
# Terminal 1: Tailwind
php bin/console tailwind:build --watch

# Terminal 2: JS/Encore
npm run watch
```

### 🚀 Production
Optimized for performance and security.

```bash
php bin/console tailwind:build --minify
npm run build
php bin/console cache:clear
```

## 📂 Structure

* `/src/Controller` — Page logic (including `AdminRestaurantController`).
* `/src/Entity` — Database models (`User`, `Restaurant`).
* `/src/Form` — Symfony forms (`RegistrationType`, `RestaurantType`).
* `/src/DataFixtures` — Initial test data.
* `/templates` — Twig templates (including `admin/` for the admin panel).
* `/assets` — Stimulus controllers and CSS.
* `/migrations` — Doctrine database migrations.

---
*Built with love in Luxembourg.*
