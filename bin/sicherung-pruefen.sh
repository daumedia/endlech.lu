#!/usr/bin/env bash
#
# BE-03 · Eine Datenbanksicherung tatsächlich einspielen, statt sie anzunehmen.
#
# „Es wird gesichert" ist eine Behauptung. Die Frage, die zählt, lautet: Lässt sich
# die Sicherung einspielen, und ist danach alles da? Seit Feature 08 liegen in dieser
# Datenbank E-Mail-Adressen Dritter mit Einwilligungszeitpunkt — die lassen sich
# nicht rekonstruieren. Bei Restaurantdaten wäre ein Verlust ärgerlich, hier ist er
# endgültig.
#
# ⚠ **Das Skript schreibt NIE in eine bestehende Datenbank.** Eingespielt wird in
# einen eigens gestarteten Wegwerf-Container, der am Ende gelöscht wird — auch wenn
# das Skript abbricht (`trap`). Ein Prüfskript, das die Möglichkeit hat, Produktion
# zu überschreiben, ist ein Risiko und keine Prüfung.
#
# ⚠ **MariaDB, nicht MySQL.** Produktion fährt MariaDB, lokal und in der CI läuft
# MySQL 8.0. Ein Einspielen in MySQL kann an Kollationen scheitern, die mit der
# Sicherung nichts zu tun haben — dann wäre das Ergebnis ein Fehlalarm über eine
# gesunde Sicherung. Der Wegwerf-Container ist deshalb dieselbe Maschine wie oben.
#
# ⚠ **Warum eine Datei und nicht ein Dump über das Netz.** Die Produktionsdatenbank
# ist bei Coolify nicht öffentlich erreichbar, und das soll so bleiben. Der normale
# Weg ist deshalb: Sicherung herunterladen, hier hineingeben. Wer einen Tunnel oder
# eine erreichbare Adresse hat, kann mit `--quelle` zusätzlich abgleichen — dann
# vergleicht das Skript Zeile für Zeile gegen das Original, was die stärkere Aussage
# ist.
#
# Benutzung:
#   bin/sicherung-pruefen.sh --datei sicherung.sql[.gz]
#   bin/sicherung-pruefen.sh --datei sicherung.sql.gz --quelle 'mysql://nutzer:pw@host:3306/endlech'
#   bin/sicherung-pruefen.sh --datei sicherung.sql --version 10.11
#
# Ausgabe: Urteil auf der Konsole und ein Zeugnis unter `qa/sicherungen/`.

set -uo pipefail

DATEI=""
QUELLE=""
MARIA_VERSION="10.5"
BEHALTEN="nein"

while [ $# -gt 0 ]; do
    case "$1" in
        --datei)    DATEI="${2:-}"; shift 2 ;;
        --quelle)   QUELLE="${2:-}"; shift 2 ;;
        --version)  MARIA_VERSION="${2:-}"; shift 2 ;;
        --behalten) BEHALTEN="ja"; shift ;;
        -h|--hilfe|--help)
            sed -n '3,40p' "$0" | sed 's/^# \{0,1\}//'
            exit 0 ;;
        *) echo "Unbekannte Option: $1" >&2; exit 2 ;;
    esac
done

if [ -z "$DATEI" ]; then
    echo "FEHLER: --datei fehlt. Ohne Sicherungsdatei gibt es nichts zu prüfen." >&2
    echo "        Woher die Datei kommt, steht in docs/datenschutz.md unter BE-03." >&2
    exit 2
fi
if [ ! -r "$DATEI" ]; then
    echo "FEHLER: $DATEI ist nicht lesbar." >&2
    exit 2
fi

# Die Tabellen, die es nach dem Einspielen geben MUSS. Die Liste steht hier und
# nicht in der Prüfung selbst: Ein Prüflauf, der seine Erwartung aus dem Prüfling
# ableitet, prüft gegen sich selbst. Wer eine Migration mit neuer Tabelle schreibt,
# ergänzt sie hier — sonst gilt eine Sicherung als vollständig, in der sie fehlt.
ERWARTETE_TABELLEN=(
    app_waitlist_entry board_idea board_vote cache_items cuisine favorite
    finance_entry marketing_contact messenger_messages metric_snapshot
    opening_hour ordering_option organisation_waitlist_entry
    partner_waitlist_entry restaurant restaurant_cuisine restaurant_image
    restaurant_note restaurant_suggestion saved_list saved_list_entry user
    webauthn_credential
)

# ⚠ Diese vier tragen Daten, die sich nicht wiederherstellen lassen: fremde
# E-Mail-Adressen mit Einwilligungszeitpunkt (DSGVO Art. 7 Abs. 1 — ohne den
# Zeitpunkt ist die Einwilligung nicht mehr belegbar) und die Konten selbst.
# Bei ihnen ist eine Abweichung gegen das Original kein Schönheitsfehler.
UNERSETZLICH=( app_waitlist_entry partner_waitlist_entry organisation_waitlist_entry marketing_contact user )

WURZEL="$(cd "$(dirname "$0")/.." && pwd)"
ARBEIT="$(mktemp -d)"
CONTAINER="endlech-sicherungsprobe-$$"
ZEUGNIS="$WURZEL/qa/sicherungen/$(date +%Y-%m-%d-%H%M)-sicherungsprobe.md"
FEHLER=0

aufraeumen() {
    if [ "$BEHALTEN" = "ja" ]; then
        echo "  (Container $CONTAINER bleibt stehen — mit 'docker rm -f $CONTAINER' entfernen)"
    else
        docker rm -f "$CONTAINER" >/dev/null 2>&1
    fi
    rm -rf "$ARBEIT"
}
trap aufraeumen EXIT

sage()  { printf '%s\n' "$*"; printf '%s\n' "$*" >> "$ARBEIT/protokoll.txt"; }
titel() { printf '\n\033[1m%s\033[0m\n' "$*"; printf '\n## %s\n\n' "$*" >> "$ARBEIT/protokoll.txt"; }
rot()   { FEHLER=$((FEHLER+1)); sage "  ✗ $*"; }
gruen() { sage "  ✓ $*"; }

# ── 0 · Die Datei ansehen, bevor irgendetwas läuft ────────────────────────────
titel "0 · Die Sicherungsdatei"

GROESSE=$(wc -c < "$DATEI" | tr -d ' ')
sage "  Datei:  $DATEI"
sage "  Größe:  $(( GROESSE / 1024 )) kB"
sage "  Stand:  $(date -r "$DATEI" '+%Y-%m-%d %H:%M' 2>/dev/null || echo unbekannt)"

if [ "$GROESSE" -lt 1024 ]; then
    rot "Die Datei ist kleiner als 1 kB. Das ist keine Sicherung, das ist ein leerer Lauf."
fi

# Gepackt oder nicht — beides kommt vor, je nachdem, wer die Sicherung erzeugt hat.
case "$DATEI" in
    *.gz) LESEN="gzip -dc"; if ! gzip -t "$DATEI" 2>/dev/null; then
              rot "Das Gzip-Archiv ist beschädigt (gzip -t schlägt fehl). Weiter geht es nicht."
              exit 1
          fi
          gruen "Gzip-Archiv ist unbeschädigt" ;;
    *)    LESEN="cat" ;;
esac

# ⚠ Der Fall, der sonst als Erfolg durchgeht: ein Dump, der nur die Struktur
# enthält (`--no-data`). Er spielt fehlerfrei ein, und danach ist die Datenbank
# leer. Ohne diese Prüfung wäre das Urteil grün.
if ! $LESEN "$DATEI" | grep -qm1 'INSERT INTO'; then
    rot "Kein einziges INSERT in der Datei — das ist ein Struktur-Dump ohne Daten."
fi

DB_NAME=$($LESEN "$DATEI" | grep -m1 -oE 'USE `[^`]+`' | sed -E 's/USE `//; s/`//')
if [ -z "${DB_NAME:-}" ]; then
    DB_NAME="endlech"
    sage "  Kein USE-Befehl in der Datei — es wird in die Datenbank '$DB_NAME' eingespielt."
else
    gruen "Die Datei benennt ihre Datenbank selbst: $DB_NAME"
fi

# ── 1 · Wegwerf-Maschine starten ─────────────────────────────────────────────
titel "1 · Wegwerf-MariaDB $MARIA_VERSION starten"

if ! docker info >/dev/null 2>&1; then
    rot "Docker antwortet nicht. Ohne Docker kann nichts eingespielt werden."
    exit 1
fi

docker run -d --name "$CONTAINER" \
    -e MARIADB_ROOT_PASSWORD=probe \
    -e MARIADB_DATABASE="$DB_NAME" \
    "mariadb:$MARIA_VERSION" >/dev/null 2>&1 \
    || { rot "Container ließ sich nicht starten (Image mariadb:$MARIA_VERSION vorhanden?)"; exit 1; }

# Kein `sleep 30` auf Hoffnung: warten, bis die Maschine antwortet.
for i in $(seq 1 60); do
    if docker exec "$CONTAINER" mariadb -uroot -pprobe -e 'SELECT 1' >/dev/null 2>&1 \
    || docker exec "$CONTAINER" mysql -uroot -pprobe -e 'SELECT 1' >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

if docker exec "$CONTAINER" mariadb -uroot -pprobe -e 'SELECT 1' >/dev/null 2>&1; then
    KLIENT=mariadb
elif docker exec "$CONTAINER" mysql -uroot -pprobe -e 'SELECT 1' >/dev/null 2>&1; then
    KLIENT=mysql   # MariaDB bis 10.5 liefert den Klienten noch unter dem alten Namen
else
    rot "Die Wegwerf-Maschine antwortet nach 120 Sekunden nicht."
    exit 1
fi
gruen "MariaDB $(docker exec "$CONTAINER" $KLIENT -uroot -pprobe -N -B -e 'SELECT VERSION()' 2>/dev/null) läuft"

# ⚠ Ohne `-i` und mit abgeschnittenem stdin, und das ist kein Schönheitsfehler.
# `docker exec -i` LIEST stdin — steht der Aufruf in einer `while read`-Schleife, frisst
# er die Datei auf, aus der die Schleife liest, und sie endet nach dem ersten Durchgang.
# Beim Bauen genau so gemessen: Der Abgleich gegen die Quelle verglich eine einzige
# Tabelle und meldete danach „alle Zeilenzahlen stimmen überein" — eine Sicherung, in
# der 18 von 19 Tabellen fehlen konnten, wäre grün durchgegangen. `-e` braucht kein
# stdin; das `< /dev/null` ist der Riegel dagegen, dass jemand `-i` wieder ergänzt.
fragen() { docker exec "$CONTAINER" "$KLIENT" -uroot -pprobe -N -B -e "$1" 2>/dev/null < /dev/null; }

# ── 2 · Einspielen ───────────────────────────────────────────────────────────
titel "2 · Die Sicherung einspielen"

$LESEN "$DATEI" | docker exec -i "$CONTAINER" "$KLIENT" -uroot -pprobe \
    --default-character-set=utf8mb4 > "$ARBEIT/einspielen.log" 2>&1
EINSPIEL_CODE=$?

# ⚠ Der Rückgabewert allein reicht nicht: Warnungen über das Passwort auf der
# Kommandozeile stehen auf stderr, und echte Fehler beenden den Lauf nicht immer.
# Gefiltert wird deshalb auf ERROR, nicht auf „irgendetwas auf stderr".
ECHTE_FEHLER=$(grep -i 'ERROR' "$ARBEIT/einspielen.log" | grep -v 'Using a password' || true)

if [ "$EINSPIEL_CODE" -ne 0 ] || [ -n "$ECHTE_FEHLER" ]; then
    rot "Das Einspielen ist fehlgeschlagen (Code $EINSPIEL_CODE):"
    printf '%s\n' "$ECHTE_FEHLER" | head -5 | sed 's/^/      /' | tee -a "$ARBEIT/protokoll.txt"
else
    gruen "Eingespielt, ohne Fehler"
fi

# ── 3 · Ist alles da? ────────────────────────────────────────────────────────
titel "3 · Vollständigkeit der Struktur"

VORHANDEN=$(fragen "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_NAME'" | tr -d '\r')
FEHLENDE=""
for t in "${ERWARTETE_TABELLEN[@]}"; do
    printf '%s\n' "$VORHANDEN" | grep -qx "$t" || FEHLENDE="$FEHLENDE $t"
done
if [ -n "$FEHLENDE" ]; then
    rot "Tabellen fehlen nach dem Einspielen:$FEHLENDE"
else
    gruen "Alle ${#ERWARTETE_TABELLEN[@]} erwarteten Tabellen sind da"
fi

# ⚠ Fremdschlüssel gehören geprüft, nicht bloß Zeilen. Ein Dump mit
# `--no-create-info` oder aus einem Werkzeug, das Beziehungen weglässt, spielt
# Zeilen ein und ist trotzdem keine wiederherstellbare Datenbank: Die Anwendung
# verlässt sich an mehreren Stellen auf ON DELETE CASCADE (Konto löschen, Bilder,
# Öffnungszeiten). Ohne die Schlüssel bliebe beim nächsten Löschvorgang Müll liegen.
FK=$(fragen "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$DB_NAME'")
if [ "${FK:-0}" -lt 10 ]; then
    rot "Nur ${FK:-0} Fremdschlüssel eingespielt — erwartet werden mindestens 10. Die Sicherung trägt die Beziehungen nicht."
else
    gruen "$FK Fremdschlüssel sind mitgekommen"
fi

# Der Schemastand. Eine einspielbare Sicherung eines ÄLTEREN Schemas ist brauchbar,
# braucht danach aber `doctrine:migrations:migrate` — und wer das nicht weiß, sucht
# den Fehler in der Anwendung.
if printf '%s\n' "$VORHANDEN" | grep -qx 'doctrine_migration_versions'; then
    STAND=$(fragen "SELECT MAX(version) FROM $DB_NAME.doctrine_migration_versions")
    LETZTE=$(ls -1 "$WURZEL"/migrations/Version*.php 2>/dev/null | tail -1 | xargs -I{} basename {} .php)
    sage "  Schemastand der Sicherung: ${STAND##*\\\\}"
    sage "  Letzte Migration im Code:  ${LETZTE:-unbekannt}"
    if [ -n "${LETZTE:-}" ] && [ "${STAND##*\\\\}" != "$LETZTE" ]; then
        sage "  ⚠ Die Stände unterscheiden sich — nach dem Einspielen gehört 'doctrine:migrations:migrate' gelaufen."
    else
        gruen "Schemastand der Sicherung entspricht dem Code"
    fi
else
    rot "Tabelle doctrine_migration_versions fehlt — der Schemastand der Sicherung ist unbekannt."
fi

# ── 4 · Zeilenzahlen, mit Schwerpunkt auf dem Unersetzlichen ─────────────────
titel "4 · Zeilenzahlen"

# ⚠ COUNT(*), nicht information_schema.TABLE_ROWS. Letzteres ist bei InnoDB eine
# Schätzung und schwankt um zweistellige Prozentwerte — als Abgleich gegen ein
# Original wäre es wertlos.
ZAEHLUNG="$ARBEIT/zeilen.txt"
: > "$ZAEHLUNG"
for t in "${ERWARTETE_TABELLEN[@]}"; do
    printf '%s\n' "$VORHANDEN" | grep -qx "$t" || continue
    n=$(fragen "SELECT COUNT(*) FROM \`$DB_NAME\`.\`$t\`")
    printf '%s\t%s\n' "$t" "${n:-?}" >> "$ZAEHLUNG"
done

while IFS=$'\t' read -r t n; do
    kennzeichen=""
    for u in "${UNERSETZLICH[@]}"; do [ "$t" = "$u" ] && kennzeichen=" ← unersetzlich"; done
    printf '  %-30s %8s%s\n' "$t" "$n" "$kennzeichen" | tee -a "$ARBEIT/protokoll.txt" >/dev/null
    printf '  %-30s %8s%s\n' "$t" "$n" "$kennzeichen"
done < "$ZAEHLUNG"

# Der Einwilligungszeitpunkt ist der Beleg (DSGVO Art. 7 Abs. 1): eine Adresse ohne
# ihn ist nach dem Einspielen unbrauchbar und müsste gelöscht werden.
#
# ⚠ Geprüft wird die SPALTENDEFINITION, nicht nur der Inhalt. Beim Bauen gemessen:
# `consent_at` ist im Schema `NOT NULL` — eine Zählung von NULL-Werten kann auf einer
# gesunden Datenbank also nie anschlagen und wäre ein Prüfschritt, der strukturell
# immer grün ist. Aussagekräftig ist, ob die Sicherung die Spalte samt ihrer
# NOT-NULL-Bedingung mitgebracht hat: Ein Dump, der die Bedingung verliert, spielt
# ein und lässt danach Zeilen ohne Beleg zu. Die Inhaltsprüfung bleibt daneben
# stehen — für den Fall, dass die Bedingung schon weg war, als die Sicherung entstand.
for t in app_waitlist_entry partner_waitlist_entry organisation_waitlist_entry; do
    printf '%s\n' "$VORHANDEN" | grep -qx "$t" || continue
    art=$(fragen "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$DB_NAME' AND TABLE_NAME='$t' AND COLUMN_NAME='consent_at'")
    gesamt=$(fragen "SELECT COUNT(*) FROM \`$DB_NAME\`.\`$t\`")
    if [ -z "${art:-}" ]; then
        rot "$t: die Spalte consent_at fehlt — der Einwilligungszeitpunkt ist nicht mitgekommen."
        continue
    fi
    if [ "$art" != "NO" ]; then
        rot "$t: consent_at ist nach dem Einspielen NULL-fähig — die Sicherung hat die NOT-NULL-Bedingung verloren."
    fi
    ohne=$(fragen "SELECT COUNT(*) FROM \`$DB_NAME\`.\`$t\` WHERE consent_at IS NULL")
    if [ "${ohne:-0}" -gt 0 ]; then
        rot "$t: $ohne von $gesamt Zeilen ohne consent_at — die Einwilligung ist nicht mehr belegbar."
    elif [ "$art" = "NO" ]; then
        gruen "$t: consent_at ist NOT NULL, $gesamt Zeile(n) belegt"
    fi
done

# ── 5 · Abgleich gegen das Original, wenn eines erreichbar ist ───────────────
if [ -n "$QUELLE" ]; then
    titel "5 · Abgleich gegen die Quelle"

    # mysql://nutzer:pw@host:port/datenbank
    ohne_schema="${QUELLE#mysql://}"; ohne_schema="${ohne_schema#mysql2://}"
    anmeldung="${ohne_schema%%@*}"; rest="${ohne_schema#*@}"
    q_user="${anmeldung%%:*}"; q_pass="${anmeldung#*:}"
    q_hostport="${rest%%/*}"; q_db="${rest#*/}"; q_db="${q_db%%\?*}"
    q_host="${q_hostport%%:*}"; q_port="${q_hostport#*:}"
    [ "$q_port" = "$q_host" ] && q_port=3306

    # Eine Adresse auf diesem Rechner ist aus dem Container heraus nicht dieselbe.
    case "$q_host" in
        127.0.0.1|localhost|::1) q_host="host.docker.internal" ;;
    esac

    # ⚠ Das Passwort geht über eine Datei mit 600, nicht über die Kommandozeile.
    # Dort stünde es in der Prozessliste jedes Nutzers auf diesem Rechner.
    printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' \
        "$q_host" "$q_port" "$q_user" "$q_pass" > "$ARBEIT/quelle.cnf"
    chmod 600 "$ARBEIT/quelle.cnf"

    # ⚠ Gefragt wird AUS dem Wegwerf-Container heraus, nicht über einen zweiten
    # `docker run --network host`. Host-Networking verhält sich auf macOS anders als
    # unter Linux; der Abgleich wäre dort still leer geblieben und hätte wie „Quelle
    # nicht erreichbar" ausgesehen. Der Container hängt am Bridge-Netz und erreicht
    # den Rechner unter `host.docker.internal` — deshalb die Umschrift unten.
    docker cp "$ARBEIT/quelle.cnf" "$CONTAINER:/q.cnf" >/dev/null 2>&1
    docker exec "$CONTAINER" chmod 600 /q.cnf >/dev/null 2>&1

    quelle_fragen() {
        docker exec "$CONTAINER" "$KLIENT" --defaults-extra-file=/q.cnf -N -B -e "$1" 2>/dev/null < /dev/null
    }

    if [ -z "$(quelle_fragen 'SELECT 1')" ]; then
        sage "  ⚠ Die Quelle ist nicht erreichbar — der Abgleich entfällt."
        sage "    Das ist der Normalfall: Die Produktionsdatenbank ist bei Coolify nicht"
        sage "    öffentlich erreichbar, und das soll sie bleiben. Ohne Abgleich bleibt das"
        sage "    Urteil aus Schritt 3 und 4 gültig — es sagt nur nichts über Zeilen, die"
        sage "    schon in der Sicherung fehlten."
    else
        abweichungen=0
        verglichen=0
        # ⚠ Die Schleife liest über Dateikennung 3, nicht über stdin. Damit kann kein
        # Befehl im Rumpf die Vorlage mehr auffressen (siehe `fragen()` weiter oben).
        while IFS=$'\t' read -r t n <&3; do
            q=$(quelle_fragen "SELECT COUNT(*) FROM \`$q_db\`.\`$t\`")
            if [ -z "${q:-}" ]; then
                # Nicht überspringen: Ein stiller Übersprung ist von Übereinstimmung
                # nicht zu unterscheiden, und genau das ist der Fehler, den dieses
                # Skript finden soll.
                rot "$t: in der Quelle nicht abfragbar — der Abgleich für diese Tabelle fehlt."
                continue
            fi
            verglichen=$((verglichen+1))
            if [ "$q" != "$n" ]; then
                abweichungen=$((abweichungen+1))
                unersetzlich=""
                for u in "${UNERSETZLICH[@]}"; do [ "$t" = "$u" ] && unersetzlich=" (unersetzlich!)"; done
                rot "$t: Quelle $q, Sicherung $n$unersetzlich"
            fi
        done 3< "$ZAEHLUNG"
        # Die Zahl gehört in die Ausgabe. „Alle stimmen überein" ist ohne sie eine
        # Aussage, die auch bei einer einzigen verglichenen Tabelle richtig klingt.
        if [ "$abweichungen" -eq 0 ]; then
            gruen "Alle Zeilenzahlen stimmen mit der Quelle überein ($verglichen Tabellen verglichen)"
        else
            sage "  ($verglichen von $(wc -l < "$ZAEHLUNG" | tr -d ' ') Tabellen verglichen)"
        fi
    fi
fi

# ── 6 · Urteil und Zeugnis ───────────────────────────────────────────────────
titel "6 · Urteil"

if [ "$FEHLER" -eq 0 ]; then
    URTEIL="**Die Sicherung ist einspielbar und vollständig.**"
    sage "  $URTEIL"
else
    URTEIL="**Die Sicherung ist NICHT brauchbar — $FEHLER Befund(e).**"
    sage "  $URTEIL"
fi

mkdir -p "$(dirname "$ZEUGNIS")"
{
    echo "# Sicherungsprobe $(date '+%Y-%m-%d %H:%M')"
    echo
    echo "BE-03. Erzeugt von \`bin/sicherung-pruefen.sh\` — nicht von Hand geschrieben."
    echo
    echo "| | |"
    echo "|---|---|"
    echo "| **Urteil** | $URTEIL |"
    echo "| Geprüfte Datei | \`$DATEI\` ($(( GROESSE / 1024 )) kB) |"
    echo "| Eingespielt in | MariaDB $MARIA_VERSION, Wegwerf-Container |"
    echo "| Abgleich gegen Quelle | $([ -n "$QUELLE" ] && echo 'versucht' || echo 'nein — nur die Datei geprüft') |"
    echo
    echo '```'
    cat "$ARBEIT/protokoll.txt"
    echo '```'
} > "$ZEUGNIS"

sage ""
sage "  Zeugnis: ${ZEUGNIS#$WURZEL/}"

[ "$FEHLER" -eq 0 ] || exit 1
