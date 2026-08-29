#!/usr/bin/env bash
#
# Deploy-Skript für endlech.lu – wird von .github/workflows/cd.yml per
#   ssh <user>@<host> 'bash -s' < .github/deploy.sh
# auf dem Production-Server ausgeführt. Läuft nie im Runner.
#
# Voraussetzung (einmaliges Server-Setup, siehe README → Deployment):
# Das Deploy-Verzeichnis ist ein git-Checkout mit SSH-Deploy-Key in .git/.
#
# Lokal prüfbar mit:  bash -n .github/deploy.sh

# Ohne diese Zeile zählt nur der Exit-Code des letzten Befehls: eine
# gescheiterte Migration liefe durch und der Actions-Lauf würde grün.
set -euo pipefail

DEPLOY_DIR="$HOME/public_html"
DEPLOY_BRANCH="production"

# Composers Default-Cache (~/.cache) ist auf diesem Hosting nicht beschreibbar –
# ohne diese Zeile laeuft jeder Deploy komplett ohne Cache ("Proceeding without
# cache") und laedt saemtliche Pakete neu.
export COMPOSER_CACHE_DIR="$HOME/tmp/composer-cache"

cd "$DEPLOY_DIR"

# Deterministisch statt `git pull`: kein Merge, kein Konflikt.
# Das Holen aendert den Arbeitsbaum noch nicht und liegt deshalb VOR der Wartung.
git fetch --prune origin

# --- Wartungsfenster an (ENDLECH-5) --------------------------------------
# Ab `git reset` liegen die neuen PHP-Dateien neben dem kompilierten Container
# des Vorgaenger-Releases. Ruft der alte Container einen geaenderten Konstruktor
# auf, endet JEDE Anfrage in einem 500er – am 29.08.2026 traf es
# `ApiRateLimitSubscriber` (zwei statt drei Argumenten, siehe ENDLECH-5). Der
# gemischte Zustand endet erst mit `cache:clear`, hier rund 35 Sekunden spaeter.
#
# Die Flag-Datei liegt unter var/ und ist damit gitignoriert: `git clean -fd`
# laeuft ohne -x und fasst sie nicht an. `cache:clear` raeumt nur var/cache.
MAINTENANCE_FLAG="$DEPLOY_DIR/var/maintenance"
mkdir -p "$DEPLOY_DIR/var"

# Bei Erfolg faellt die Wartungsseite weg. Bei Abbruch bleibt sie BEWUSST
# stehen: Der Arbeitsbaum ist dann neu, der Container alt oder die Migration
# halb durch – eine 503 mit Retry-After ist fuer Besucher und Suchmaschinen
# besser als der 500er, den dieser Zustand sonst liefert. Das Signal zum
# Eingreifen ist der rote Actions-Lauf; danach von Hand:
#   ssh <user>@<host> 'rm -f ~/public_html/var/maintenance'
release_maintenance() {
    local exit_code=$?

    if [ "$exit_code" -eq 0 ]; then
        rm -f "$MAINTENANCE_FLAG"

        return
    fi

    echo "::error::Deploy mit Code ${exit_code} abgebrochen – die Wartungsseite bleibt stehen. Nach der Reparatur entfernen: rm -f ${MAINTENANCE_FLAG}"
}
trap release_maintenance EXIT

touch "$MAINTENANCE_FLAG"

git reset --hard "origin/${DEPLOY_BRANCH}"

# Entfernt, was nicht mehr im Repo steht (alte Klassen, Templates, Asset-Hashes).
# Ohne -x bleibt alles Gitignorierte unangetastet: .env.local, config/jwt/*.pem,
# public/uploads/{avatars,restaurants}, var/, vendor/, public/bundles/.
git clean -fd


# ⚠ BF-29: Ohne APP_API_BASE_URL baut die REST-API ihre Bild- und Avatar-URLs aus
# Schema und Host des Requests — ein gefälschter `Host:`-Header steuert damit,
# was in der Antwort steht. Kein Abbruch: Die Website funktioniert auch ohne, und
# ein Deploy, der daran scheitert, hilft niemandem. Aber es soll im Protokoll
# stehen, damit es nicht dauerhaft übersehen wird.
if ! grep -qE '^APP_API_BASE_URL=.+' .env.local 2>/dev/null; then
    echo "::warning::APP_API_BASE_URL ist in .env.local nicht gesetzt – die API baut ihre Bild-URLs aus dem Host-Header (BF-29)."
fi

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console cache:clear

# BEWUSST KEIN `pkill -f messenger:consume`:
# Production läuft mit MESSENGER_TRANSPORT_DSN=sync:// (.env.local), es gibt
# also keinen Worker und keine Queue – Nachrichten werden im Request erledigt.
# Auf derselben Maschine läuft aber ein Worker einer FREMDEN Application unter
# einem anderen Systembenutzer; ein pkill-Muster ohne Benutzerfilter wäre dort
# eine Fußangel. Wird der Transport hier je auf doctrine:// umgestellt, gehört
# an diese Stelle: pkill -u "$(id -un)" -f 'messenger:cons[u]me' || true
# (Bracket-Pattern, damit pkill nicht die eigene SSH-Shell trifft.)

# --- Wartungsfenster aus --------------------------------------------------
# Ab hier passen Dateien und kompilierter Container wieder zusammen. Das
# Aufraeumen erledigt der EXIT-Trap (`release_maintenance`) – als einzige
# Stelle, damit auch ein Abbruch weiter oben sauber behandelt wird.

echo "Deploy abgeschlossen: $(git log --oneline -1)"
