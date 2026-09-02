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

# ⚠ `wget` allein für Coolifys Healthcheck. Coolify benutzt den HEALTHCHECK aus
# diesem Dockerfile NICHT — es setzt beim Ausrollen einen eigenen und ruft darin
# `wget` auf. Fehlt das Programm, scheitert die Prüfung zehnmal mit
# „/bin/sh: 1: wget: not found", der frische Container gilt als krank und Coolify
# rollt auf den alten zurück. Gemessen am 2026-09-02: Der Container lief dabei
# einwandfrei und meldete im Log „[OK] Consuming messages from transports …" —
# verworfen wurde er trotzdem.
#
# `curl` liegt bereits im Basisimage, hilft hier aber nicht: Coolifys Vorgabe
# fragt nach `wget`. Ein Paket von rund einem Megabyte gegen einen Deploy, der
# ohne erkennbaren Fehler zurückrollt.
RUN apt-get update \
    && apt-get install -y --no-install-recommends wget \
    && rm -rf /var/lib/apt/lists/*

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

# Die Prüfung läuft über den PHP-Interpreter, der ohnehin da ist; ein
# Fehlerstatus lässt file_get_contents false zurückgeben, das ist das Signal.
# start-period deckt den Kaltstart ab.
#
# ⚠ Das gilt für `docker run` und Compose. **Coolify benutzt diese Zeile nicht** —
# es setzt beim Ausrollen einen eigenen Healthcheck auf `GET /health` und ruft
# darin `wget` auf (siehe oben).
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD ["php", "-r", "$r = @file_get_contents('http://127.0.0.1/health'); exit(false !== $r && str_contains($r, 'ok') ? 0 : 1);"]

EXPOSE 80

# Nutzerdaten und Caddys Zertifikatsspeicher gehören auf Volumes, nicht ins
# Image. In Coolify als Persistent Storage anlegen.
VOLUME ["/app/public/uploads", "/data", "/config"]

# ---------------------------------------------------------------------------
# worker – derselbe Code, derselbe Container, anderer Prozess
# ---------------------------------------------------------------------------
# In Coolify eine zweite Ressource aus demselben Dockerfile, über „Docker build
# stage target: worker". Erbt alles von `runtime` — Code, Erweiterungen,
# php.ini, Rechte — und tauscht nur den Startbefehl.
#
# ⚠ **Ohne diesen Prozess ist der Ausfall lautlos.** Weder eine Bestätigungsmail
# noch der Monats-Snapshot noch der Brevo-Abgleich findet statt, während die
# Anwendung weiter „erfolgreich" meldet und sich die Nachrichten in
# `messenger_messages` stapeln. Der Zustand gehört gemessen, nicht angenommen:
# `messenger:stats` und `messenger:failed:show`.
FROM runtime AS worker

# ⚠ `pcntl` fehlt im FrankenPHP-Image — nachgesehen, nicht vermutet: `php -m`
# listet dort `posix`, aber kein `pcntl`. Für den Webserver ist das richtig, für
# einen Worker nicht: Ohne die Erweiterung meldet Symfonys `SignalRegistry` keine
# Unterstützung, und `messenger:consume` kann SIGTERM nicht abfangen.
#
# Die Folge trifft genau den häufigsten Fall, den Neustart beim Ausrollen: Docker
# schickt SIGTERM, der Worker ignoriert es und wird nach der Schonfrist hart
# beendet — mitten in einer Nachricht. Beim Doctrine-Transport bleibt die dann mit
# gesetztem `delivered_at` liegen und kommt erst nach `redeliver_timeout` (Vorgabe
# eine Stunde) zurück. Eine Bestätigungsmail ginge also eine Stunde zu spät hinaus
# oder ein zweites Mal, falls der Versand schon durch war.
#
# Deshalb steht die Erweiterung hier und nicht im `base`-Stage: Der Webserver
# braucht sie nie, und in einem Request-Kontext ist sie unerwünscht.
RUN install-php-extensions pcntl

# ⚠ Der geerbte HEALTHCHECK prüft `http://127.0.0.1/health` und muss hier
# zwangsläufig scheitern — der Worker startet keinen Webserver. Er wird deshalb
# überschrieben, aber **nicht** mit `HEALTHCHECK NONE`:
#
# ⚠⚠ **`HEALTHCHECK NONE` bringt Coolify zum Abbruch.** Ein Container ohne
# Healthcheck hat kein `.State.Health` — und Coolify fragt genau das ab, sobald
# seine eigene Prüfung abgeschaltet ist und es auf die des Bildes zurückfällt.
# Gemessen am 2026-09-02: „Custom healthcheck found in Dockerfile" gefolgt von
# „template parsing error: map has no entry for key \"Health\"" und einem
# fehlgeschlagenen Deploy. Die Prüfung muss also **existieren** und passen.
#
# Geprüft wird die Kommandozeile von PID 1 — im Container ist das der Consumer
# selbst. Kein zusätzliches Paket nötig (`pgrep` säße in `procps`, das nicht im
# Bild liegt), und der Healthcheck-Prozess selbst kann sich nicht versehentlich
# selbst finden, weil ausschließlich `/proc/1` gelesen wird.
#
# ⚠ Das ist eine Lebendigkeitsprüfung, keine Fortschrittsprüfung: Sie sagt „der
# Consumer läuft", nicht „er arbeitet Nachrichten ab". Wer Letzteres wissen will,
# fragt `messenger:stats` — ein Rückstau in `async` ist das Signal, und der lässt
# sich von innen nicht sinnvoll in einen Exit-Code gießen.
HEALTHCHECK --interval=60s --timeout=5s --start-period=15s --retries=3 \
    CMD ["php", "-r", "exit(str_contains((string) @file_get_contents('/proc/1/cmdline'), 'messenger') ? 0 : 1);"]

# ⚠ `SERVER_NAME`, `EXPOSE 80` und der Caddy-Verlauf aus `runtime` bleiben stehen,
# sind hier aber wirkungslos: Sie liest allein FrankenPHP, und das startet nie.
# Der Entrypoint des Basisimage stellt nur dann `frankenphp run` voran, wenn das
# erste Argument mit `-` beginnt (nachgesehen in `/usr/local/bin/docker-php-entrypoint`)
# — bei `php` als erstem Wort läuft schlicht `exec php …`. In Coolify bekommt
# diese Ressource deshalb **keine Domain und keinen Port**.
#
# Die drei Transporte sind die tatsächlichen Namen aus
# `config/packages/messenger.yaml`, gegengeprüft mit `messenger:consume` gegen
# einen ungültigen Namen: „Valid receivers are: async, failed, scheduler_metrics,
# scheduler_marketing." Der `failed`-Transport gehört ausdrücklich NICHT dazu —
# er ist die Ablage für endgültig Gescheitertes und wird von Hand mit
# `messenger:failed:show` und `:retry` bearbeitet. Wer ihn hier mitkonsumiert,
# schickt jede aufgegebene Nachricht sofort wieder in dieselbe Schleife.
#
# ⚠ **`--time-limit=3600` beendet den Prozess nach einer Stunde — mit Absicht, und
# es setzt eine Neustart-Regel voraus.** Der Worker löst sich damit selbst ab und
# läuft nach jedem Ausrollen von allein mit neuem Code und frischem Container an;
# zugleich verfällt jeder Speicher, den ein langlebiger PHP-Prozess ansammelt.
# Steht die Ressource in Coolify aber auf „no restart", ist der Worker nach einer
# Stunde weg und kommt nicht zurück — und genau dieser Ausfall ist der lautlose.
CMD ["php", "bin/console", "messenger:consume", \
     "async", "scheduler_metrics", "scheduler_marketing", \
     "--time-limit=3600", "--memory-limit=256M", "--env=prod"]
