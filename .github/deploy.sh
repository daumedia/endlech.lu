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
git fetch --prune origin
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

echo "Deploy abgeschlossen: $(git log --oneline -1)"
