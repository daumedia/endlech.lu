#!/usr/bin/env bash
# QA Feature 11 · Überarbeitung 2026-09-14 — Weiterleitung an die Umami-Domain, an der laufenden Anwendung.
# Umgebung: qa/11/ueberarbeitung-umgebung.sh (Traefik vor Umami 3.3.1 ohne Einstellungen). Die Anwendung läuft
# im Produktionsmodus mit `php -S` und leitet über Traefiks **HTTP-Eingang** weiter — die Zertifikatsprüfung
# prüft `qa/11/ueberarbeitung-tls.php` gesondert (Grund im Kopf der Umgebung).
#
# ⚠ `-d variables_order=EGPCS` ist Pflicht: Der eingebaute Server legt Umgebungsvariablen sonst nicht in `$_ENV`
# ab, Symfony nimmt die leeren Werte aus `.env`, und jeder Zählaufruf endet als 400 („fremde Website").
# Der erste Lauf am 2026-09-14 lief genau so ins Leere.
#
# ⚠ Nie ein nacktes `wait`; die Anwendung wird über ihre Prozessnummer beendet.
# Aufruf: QA=<Prüfordner> APP=<Projekt> bash qa/11/ueberarbeitung-pruefung.sh
set -u
cd "$APP"
C=$QA/certs; U=http://localhost:39480; WEBSITE=$(cat "$QA/website-id.txt")
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'
LOG=$QA/anwendung.log

starte_app() { # $1 = TRUSTED_PROXIES
  APP_ENV=prod APP_DEBUG=0 APP_SECRET=wegwerf-qa11u \
  DATABASE_URL='mysql://root:root@127.0.0.1:3306/endlech_test?serverVersion=8.0&charset=utf8mb4' \
  APP_UMAMI_WEBSITE_ID=$WEBSITE APP_UMAMI_UPSTREAM=$U TRUSTED_PROXIES="$1" \
    php -d variables_order=EGPCS -S 127.0.0.1:8770 -t public public/index.php >>"$LOG" 2>&1 &
  APP_PID=$!
  for i in $(seq 1 40); do curl -s -o /dev/null http://127.0.0.1:8770/health && return 0; sleep 0.25; done
  echo "ABBRUCH: Anwendung startet nicht"; exit 1
}
stoppe_app() { kill "$APP_PID" 2>/dev/null; wait "$APP_PID" 2>/dev/null; }
leeren() { APP_ENV=prod APP_SECRET=wegwerf-qa11u DATABASE_URL='mysql://root:root@127.0.0.1:3306/endlech_test?serverVersion=8.0' \
  php bin/console cache:pool:clear cache.usage cache.rate_limiter --env=prod >/dev/null 2>&1; }
sende() { # pfad xff extra-json extra-curl…
  local pfad=$1 xff=$2 extra=$3; shift 3
  curl -s -o /dev/null -w '%{http_code}' -X POST http://127.0.0.1:8770/api/send -H 'Content-Type: application/json' \
    -H "User-Agent: $UA" ${xff:+-H "X-Forwarded-For: $xff"} "$@" \
    -d "{\"type\":\"event\",\"payload\":{\"website\":\"$WEBSITE\",\"hostname\":\"endlech.lu\",\"url\":\"https://endlech.lu$pfad?city=Esch&page=2\",\"referrer\":\"https://www.google.com/search?q=barrierefrei\",\"language\":\"de-LU\",\"screen\":\"1440x900\"$extra}}"
}
db() { docker exec qa11u-db psql -U umami -d umami -tAc "$1"; }
zeile() { db "select e.url_path, coalesce(nullif(e.url_query,''),'∅'), coalesce(e.referrer_domain,'∅'), s.country, coalesce(s.region,'∅'), coalesce(s.city,'∅'), s.browser, s.os, left(s.session_id::text,8), to_char(e.created_at,'YYYY') from website_event e join session s on s.session_id=e.session_id where e.url_path = '$1'"; }

: > "$LOG"
echo "Stand: $(date '+%Y-%m-%d %H:%M') · Umami $(docker exec qa11u-umami node -p 'require("./package.json").version') ohne eigene Einstellungen · Traefik $(docker exec qa11u-traefik traefik version 2>/dev/null | awk '/Version/{print $2}')"

echo "── A · Hinter dem Proxy (TRUSTED_PROXIES=127.0.0.1): Land, Sitzung, Kürzung, untergeschobene Werte"
leeren; starte_app 127.0.0.1
UNTER=',"ip":"8.8.8.8","userAgent":"Googlebot/2.1","timestamp":1000000000,"browser":"ie","os":"Windows 95","device":"tv"'
echo "A1 · Besucher LU mit untergeschobenem ip/userAgent/timestamp/browser/os/device und cf-ipcountry: JP → $(sende /de/qa-a-lu 158.64.1.1 "$UNTER" -H 'cf-ipcountry: JP' -H 'X-Endlech-Client-Ip: 8.8.4.4')"
echo "A2 · Besucher FR, derselbe Browser → $(sende /fr/qa-a-fr 90.84.0.1 "")"
echo "A3 · XFF-Kette „8.8.8.8, 158.64.1.1\" (Client fälscht, Proxy hängt die echte Adresse an) → $(sende /de/qa-a-kette '8.8.8.8, 158.64.1.1' "")"
BOT_UA='Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
NORMAL_UA=$UA; UA=$BOT_UA; code=$(sende /de/qa-a-bot 158.64.1.1 ""); UA=$NORMAL_UA
echo "A4 · Googlebot-Kennung (AK-10) → $code"
sleep 1
echo "   Pfad | Abfrage | Herkunft | Land | Region | Stadt | Browser | OS | Sitzung | Jahr"
for p in /de/qa-a-lu /fr/qa-a-fr /de/qa-a-kette /de/qa-a-bot; do printf '   %s\n' "$(zeile $p)"; [ -z "$(zeile $p)" ] && echo "   $p: nicht gespeichert"; done
stoppe_app

echo "── B · Was hinter Traefik ankommt (Echo-Dienst, dieselbe Traefik-Konfiguration)"
curl -s --cacert "$C/ca.crt" -X POST "https://localhost:39443/qa-echo" -H "User-Agent: $UA" -H 'x-umami-cache: abc.def.ghi' -H 'Content-Type: application/json' -d '{"payload":{"ip":"158.64.1.1"}}' \
  | grep -iE '^(User-Agent|X-Umami-Cache|X-Forwarded-For|X-Real-Ip|Content-Type):' | sed 's/^/   /'

echo "── C · Zertifikatsprüfung: siehe qa/11/ueberarbeitung-tls.php"

echo "── D · Ohne vertrauten Proxy (TRUSTED_PROXIES leer) — Gegenprobe zu A"
leeren; starte_app ''
echo "D1 · XFF 158.64.1.1, Verbindung von 127.0.0.1 → $(sende /de/qa-d-ohne-proxy 158.64.1.1 "")"; sleep 1
printf '   %s\n' "$(zeile /de/qa-d-ohne-proxy)"
stoppe_app

echo "── E · Protokoll der Anwendung (stderr aller Läufe, $(wc -l < "$LOG" | tr -d ' ') Zeilen)"
echo "E1 · Umami-Adresse (localhost:39480) im Protokoll: $(grep -c '39480' "$LOG")"
echo "E2 · Besucheradressen (158.64.1.1, 90.84.0.1) im Protokoll: $(grep -cE '158\.64\.1\.1|90\.84\.0\.1' "$LOG")"
echo "E3 · Protokollzeilen der Messung: $(grep -c 'Nutzungsmessung' "$LOG") · Ausnahmeklassen darin: $(grep 'Nutzungsmessung' "$LOG" | grep -oE '[A-Za-z]+\\\\[A-Za-z\\\\]+Exception' | sort -u | tr '\n' ' ')"
