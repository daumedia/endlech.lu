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

# --- Worker anhalten ------------------------------------------------------
# Der Messenger-Worker läuft als Cron-Job (jede Minute, `--time-limit=55`) und
# nimmt sich dieselbe Sperre per `flock -n`. Solange dieses Skript den
# Deskriptor hält, springt jeder Cron-Start ab; ein bereits laufender Worker
# wird abgewartet. Ohne das trifft er mitten in `git reset` auf halb neue
# Dateien – derselbe gemischte Zustand wie bei ENDLECH-5, nur im Hintergrund
# und ohne Wartungsseite davor.
#
# Kein `pkill`: Auf derselben Maschine läuft ein Worker einer FREMDEN
# Application unter einem anderen Systembenutzer, ein Muster ohne
# Benutzerfilter wäre dort eine Fußangel. Das Zeitlimit macht das Töten
# ohnehin überflüssig – der Worker löst sich jede Minute selbst ab und startet
# dabei mit dem neuen Code und frischem Container.
#
# ⚠ Geöffnet wird LESEND (`9<`), nicht schreibend. `flock` sperrt unabhängig vom
# Zugriffsmodus, und Cloudways legt Cron-Jobs im Panel unter dem Master-Benutzer
# an, während dieser Deploy als Application-User läuft – die Sperrdatei gehört
# dann dem anderen von beiden. Mit `9>` scheiterte der Deploy an genau dem.
#
# Das löst nur die Sperre. Läuft der Worker unter einem anderen Benutzer,
# schreibt er weiterhin var/log und var/cache mit fremdem Eigentümer voll, bis
# PHP-FPM dort auf Permission denied läuft. Beide gehören unter denselben
# Benutzer – siehe CLAUDE.md → Messenger-Worker.
WORKER_LOCK="$DEPLOY_DIR/var/worker.lock"

# Solange der Transport auf sync:// steht, hat noch nie ein Worker gelaufen und
# die Datei existiert nicht – dann gibt es auch nichts abzuwarten. Der Zweig
# greift von selbst, sobald der Cron eingerichtet ist.
if [ -r "$WORKER_LOCK" ]; then
    exec 9<"$WORKER_LOCK"

    # 90s: Zeitlimit des Workers (55s) plus Anlauf und Puffer. Der Deskriptor
    # bleibt bis zum Skriptende offen; das OS gibt die Sperre danach von selbst
    # frei, und der naechste Minutentakt startet den Worker neu.
    if ! flock -w 90 9; then
        echo "::error::Worker-Sperre nach 90s nicht erhalten – haengt ein messenger:consume ohne Zeitlimit?"
        exit 1
    fi
else
    echo "Keine Worker-Sperre gefunden (${WORKER_LOCK}) – Transport vermutlich noch sync://."
fi

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

# --- Wartungsfenster aus --------------------------------------------------
# Ab hier passen Dateien und kompilierter Container wieder zusammen. Das
# Aufraeumen erledigt der EXIT-Trap (`release_maintenance`) – als einzige
# Stelle, damit auch ein Abbruch weiter oben sauber behandelt wird.

echo "Deploy abgeschlossen: $(git log --oneline -1)"
