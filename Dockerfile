# syntax=docker/dockerfile:1
#
# Endlech.lu – Produktions-Image für Coolify.
#
# Drei Stages, damit im Endbild weder Composer noch Node noch die Quellen der
# Build-Werkzeuge liegen:
#   vendor  – PHP-Abhängigkeiten (--no-dev)
#   assets  – Webpack Encore → public/build
#   runtime – FrankenPHP + App
#
# Kein Worker-Mode in dieser Fassung: FrankenPHP fährt im klassischen
# Request-pro-Prozess-Modell. Das ist langsamer, verzeiht aber Zustandslecks,
# die ein Worker gnadenlos aufdeckt.

ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1
ARG NODE_VERSION=26

# ---------------------------------------------------------------------------
# base – gemeinsame PHP-Grundlage für vendor- und runtime-Stage
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS base

# Nur die Erweiterungen, die dieses Projekt wirklich braucht:
#   intl      – symfony/intl + twig/intl-extra (format_number/format_currency
#               auf /open; ohne sie stünde in der englischen Fassung „27,3 %“)
#   pdo_mysql – MySQL 8 (Produktion Coolify) bzw. MariaDB
#   opcache   – Pflicht in Produktion, sonst wird jede Datei je Request geparst
#
# Bewusst NICHT dabei: gd (das Projekt bearbeitet keine Bilder – die
# Typprüfung in ImageUploadService läuft über finfo, das ist im Image drin) und
# zip (steht in require-dev, nur `app:press:package` und der Prüflauf brauchen
# es – Composer bekommt es im vendor-Stage separat). ctype, iconv, mbstring,
# openssl, sodium, tokenizer, xml, curl und
# fileinfo bringt das offizielle PHP-Image bereits mit.
RUN install-php-extensions \
        intl \
        pdo_mysql \
        opcache

WORKDIR /app

# ---------------------------------------------------------------------------
# vendor – Composer-Abhängigkeiten
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Nur hier, nicht im runtime-Stage: Composer entpackt die dist-Archive damit.
# Zur Laufzeit braucht die Anwendung zip nicht (ext-zip steht in require-dev).
RUN install-php-extensions zip

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# Erst nur die Manifeste kopieren: Solange sich composer.json/lock nicht ändern,
# bleibt dieser Layer im Cache – auch wenn jede andere Datei angefasst wurde.
COPY composer.json composer.lock symfony.lock ./

# --no-scripts ist hier Pflicht, nicht Geschmackssache: `auto-scripts` in
# composer.json ruft `importmap:install`, aber symfony/asset-mapper ist in
# diesem Projekt gar nicht installiert (importmap.php ist eine Altlast aus dem
# Skeleton). Ausserdem gibt es zu diesem Zeitpunkt weder src/ noch config/.
# --no-autoloader, weil der Klassenmap erst nach dem Kopieren des Codes
# entstehen kann.
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress

COPY . .

RUN composer dump-autoload --no-dev --classmap-authoritative

# ---------------------------------------------------------------------------
# assets – Webpack Encore (kein AssetMapper in diesem Projekt)
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./

# ⚠ Reihenfolge ist Pflicht: package.json führt
# "@symfony/ux-turbo": "file:vendor/symfony/ux-turbo/assets" – eine
# Datei-Abhängigkeit in den Composer-Bestand hinein. Ohne vendor/ scheitert
# `npm ci` am toten Symlink. Derselbe Fallstrick steht in CLAUDE.md für den
# `verify-assets`-Job der GitHub-Action.
COPY --from=vendor /app/vendor/symfony/ux-turbo ./vendor/symfony/ux-turbo

RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

# assets/, templates/ und src/ müssen vollständig vorliegen: assets/styles/app.css
# deklariert `@source "../../templates"`, `"../../assets"` und `"../../src"` –
# Tailwind v4 findet die verwendeten Klassen sonst nicht und liefert ein
# nahezu leeres Stylesheet aus, ohne dass der Build fehlschlägt.
COPY . .

RUN npm run build

# ---------------------------------------------------------------------------
# runtime – das Bild, das in Coolify läuft
# ---------------------------------------------------------------------------
FROM base AS runtime

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=":80"

# php.ini-production: display_errors=Off, kürzere Fehlerausgabe.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY <<'INI' /usr/local/etc/php/conf.d/zz-app.ini
; Produktionswerte für Symfony. `validate_timestamps=0` ist zulässig, weil ein
; Deploy hier immer ein neuer Container ist – es gibt keinen Fall, in dem sich
; eine PHP-Datei im laufenden Betrieb ändert.
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0

; Uploads: ImageUploadService lässt 4 MB je Bild durch; die PHP-Grenze liegt
; bewusst darüber, damit die Anwendung die Ablehnung ausspricht und nicht der
; Interpreter mit einem abgeschnittenen Request.
upload_max_filesize = 8M
post_max_size = 10M
memory_limit = 256M

; Zeitzone der Anwendung (OpeningHoursService rechnet in Europe/Luxembourg).
date.timezone = Europe/Luxembourg

; Sitzungen liegen im Container, nicht in /tmp des Hosts.
session.cookie_httponly = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1
INI

COPY --from=vendor  /app                   /app
COPY --from=assets  /app/public/build      /app/public/build

# var/ liegt nicht im Repo (gitignored) und muss deshalb hier entstehen.
# www-data ist der Benutzer, unter dem FrankenPHP die PHP-Threads fährt.
RUN set -eux; \
    mkdir -p var/cache var/log public/uploads/avatars public/uploads/restaurants; \
    chown -R www-data:www-data var public/uploads; \
    chmod -R 775 var public/uploads

# Container aufwärmen: `assets:install` legt public/bundles/ an (die Swagger-UI
# unter /api/docs lädt von dort), `cache:warmup` kompiliert den Container, damit
# der erste echte Besucher nicht darauf wartet.
#
# Die Platzhalterwerte gelten NUR für diesen Build-Schritt. Symfony hält
# %env(...)% im kompilierten Container als Platzhalter und löst ihn erst zur
# Laufzeit auf – die echten Werte kommen aus Coolify. DATABASE_URL steht hier,
# weil .env sie nicht führt (sie lag bisher nur in .env.local) und der
# Kernel-Boot sie sonst nicht auflösen kann.
RUN set -eux; \
    APP_SECRET=build-only \
    DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4" \
    php bin/console assets:install public --no-interaction; \
    APP_SECRET=build-only \
    DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4" \
    php bin/console cache:warmup --no-interaction; \
    chown -R www-data:www-data var

# Ohne curl im Image – die Prüfung läuft über den PHP-Interpreter, der ohnehin
# da ist. Ein Fehlerstatus lässt file_get_contents false zurückgeben, das ist
# das Signal. start-period deckt den Kaltstart ab.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD ["php", "-r", "$r = @file_get_contents('http://127.0.0.1/health'); exit(false !== $r && str_contains($r, 'ok') ? 0 : 1);"]

EXPOSE 80

# Nutzerdaten und Caddys Zertifikatsspeicher gehören auf Volumes, nicht ins
# Image. In Coolify als Persistent Storage anlegen.
VOLUME ["/app/public/uploads", "/data", "/config"]
