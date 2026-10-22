.PHONY: help init start stop restart db migration fixtures db-reset cc assets tailwind-binary fix lint test test-db-setup press-kit sicherung-pruefen

# Standard-Hilfe: Zeigt alle Befehle an, wenn du nur "make" tippst
help: ## Zeigt diese Hilfe an
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# --- 🚀 Projekt Start & Setup ---

init: ## Projekt initialisieren (Docker, Dependencies, DB & Fixtures)
	@echo "🐳 Starte Docker Container..."
	docker compose up -d
	@echo "⏳ Warte 10 Sekunden auf Datenbank-Initialisierung..."
	@sleep 10
	@echo "📦 Installiere PHP Dependencies..."
	composer install
	@echo "📦 Installiere JS-Prüfwerkzeuge (tsc, ESLint)..."
	npm install
	@echo "🎨 Baue CSS und TypeScript..."
	$(MAKE) --no-print-directory assets
	@echo "🗄  Richte Datenbank ein..."
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
	@echo "🌱 Lade Testdaten (Fixtures)..."
	php bin/console doctrine:fixtures:load --no-interaction
	@echo "✅ Setup fertig! Starte den Server mit 'make start'"

start: ## Startet Docker und den Symfony Server (samt Tailwind- und TypeScript-Watcher aus .symfony.local.yaml)
	docker compose up -d
	symfony server:start -d

stop: ## Stoppt alles (Symfony Server & Docker)
	symfony server:stop
	docker compose stop

restart: stop start ## Startet alles neu

# --- 🛠 Entwicklung & Datenbank ---

db: ## Führt Datenbank-Migrationen aus
	php bin/console doctrine:migrations:migrate --no-interaction

migration: ## Erstellt eine neue Migration basierend auf Änderungen an Entities
	php bin/console make:migration

fixtures: ## Lädt die Testdaten neu (Löscht alte Daten!)
	php bin/console doctrine:fixtures:load --no-interaction

db-reset: ## ⚠️ ACHTUNG: Löscht DB, migriert neu & lädt Fixtures (Alles frisch!)
	-php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console doctrine:fixtures:load --no-interaction

# --- 🧪 Tests ---

test-db-setup: ## Test-Datenbank anlegen, migrieren & Fixtures laden
	php bin/console doctrine:database:create --env=test --if-not-exists
	php bin/console doctrine:migrations:migrate --env=test --no-interaction
	php bin/console doctrine:fixtures:load --env=test --no-interaction

test: test-db-setup assets ## Bereitet Test-DB und Assets vor und führt PHPUnit aus
	php bin/phpunit

# --- 🧹 Tools & Assets ---

cc: ## Cache leeren (Wichtig nach Config-Änderungen)
	php bin/console cache:clear

press-kit: ## Packt das Presse-Paket neu (public/presse/*.zip – gehört in den Commit)
	php bin/console app:press:package

sicherung-pruefen: ## BE-03: Spielt eine Sicherung in einen Wegwerf-Container ein und prüft sie (DATEI=... nötig)
ifndef DATEI
	$(error DATEI fehlt. Aufruf: make sicherung-pruefen DATEI=~/Downloads/endlech.sql.gz)
endif
	bin/sicherung-pruefen.sh --datei "$(DATEI)"

assets: tailwind-binary ## Baut CSS (var/tailwind) und TypeScript (var/typescript) einmal – Produktion baut das Image selbst
	php bin/console tailwind:build
	php bin/console typescript:build

# ⚠ macOS: Das offizielle Tailwind-Programm (v4.1.18) trägt eine Signatur, die macOS als
# ungültig verwirft — es wird beim Start mit Signal 9 beendet („The process has been signaled
# with signal 9"), ohne weitere Meldung. Die Datei ist echt (Prüfsumme gegen sha256sums.txt
# des Releases verglichen, 2026-10-22); neu signiert läuft sie. Unter Linux (CI, Image) nichts zu tun.
# Der erste `tailwind:build` lädt die Datei herunter und scheitert, deshalb `|| true`.
tailwind-binary: ## macOS: Tailwind-Programm laden und neu signieren (sonst Abbruch mit Signal 9)
ifeq ($(shell uname),Darwin)
	@ls var/tailwind/*/tailwindcss-macos-* >/dev/null 2>&1 || php bin/console tailwind:build >/dev/null 2>&1 || true
	@for f in var/tailwind/*/tailwindcss-macos-*; do codesign --verify "$$f" 2>/dev/null || codesign --force --sign - "$$f"; done
endif

fix: ## Repariert Code-Styles (CS-Fixer)
	vendor/bin/php-cs-fixer fix

lint: ## TypeScript & ESLint prüfen
	npm run typecheck
	npm run lint
