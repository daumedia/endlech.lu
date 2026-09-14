#!/usr/bin/env bash
# QA Feature 11 · Angriffsdurchlauf gegen den Zählweg /api/send — selbst gebaute Zählaufrufe, wie sie
# jeder Client schicken kann, der sich nicht an die Tracker-Optionen hält.
#
# Beobachtet wird an drei Stellen: die Antwort der Anwendung, die Mitschrift des Zähl-Eingangs
# (eingang.jsonl: was die Anwendung wirklich weiterreicht) und Umamis Datenbank (was gespeichert wird).
#
# Vorbedingungen: qa/11/umgebung.md. Aufruf: QA=<Ordner mit eingang.jsonl, website-id.txt> bash qa/11/angriff.sh
set -u
B=http://127.0.0.1:8765
W=$(cat "$QA/website-id.txt")
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'
ok=0; nein=0
melde() { if [ "$2" = ja ]; then ok=$((ok+1)); echo "✅ $1 · $3"; else nein=$((nein+1)); echo "❌ $1 · $3"; fi; }
zaehle() { # $1 = JSON-Rumpf, weitere Argumente = zusätzliche curl-Optionen
  local rumpf=$1; shift
  curl -s -o "$QA/antwort.txt" -w '%{http_code}' -X POST "$B/api/send" -H 'Content-Type: application/json' -H "User-Agent: $UA" "$@" -d "$rumpf"
}
eingang_zeilen() { wc -l < "$QA/eingang.jsonl" | tr -d ' '; }
letzter_eingang() { tail -1 "$QA/eingang.jsonl"; }
umami() { docker exec qa11-db psql -U umami -tAF '|' -c "$1"; }
seite() { printf '{"type":"event","payload":{"website":"%s","hostname":"endlech.lu","url":"%s"%s}}' "$W" "$1" "${2:-}"; }

echo "── 5 · Personendaten an Umami: unterschobene Kopfzeilen und Felder"
vor=$(eingang_zeilen)
c=$(zaehle "$(seite '/de/angriff-kopf' ',"referrer":"https://mail.example.lu/inbox/12345?mid=abc","id":"konto-42@example.lu","tag":"konto-42","unbekannt":"x"')" \
  -H 'X-Forwarded-For: 203.0.113.9' -H 'X-Real-IP: 203.0.113.10' -H 'cf-connecting-ip: 203.0.113.11' -H 'cf-ipcountry: US' \
  -H 'x-vercel-ip-country: US' -H 'Cookie: PHPSESSID=abc; umami=1' -H 'Accept-Language: de-LU' -H 'X-Endlech-Client-Ip: 203.0.113.12')
nach=$(eingang_zeilen)
zeile=$(letzter_eingang)
schluessel=$(python3 -c 'import sys,json; d=json.loads(sys.argv[1]); print(",".join(sorted(d["kopf"].keys())))' "$zeile")
ip=$(python3 -c 'import sys,json; print(json.loads(sys.argv[1])["kopf"].get("x-endlech-client-ip",""))' "$zeile")
koerper=$(python3 -c 'import sys,json; print(json.loads(json.loads(sys.argv[1])["rumpf"])["payload"])' "$zeile")
melde AK-04 "$([ "$c" = 200 ] && [ $((nach-vor)) = 1 ] && [ "$schluessel" = "accept,accept-encoding,content-length,content-type,host,user-agent,x-endlech-client-ip" ] && echo ja || echo nein)" \
  "HTTP $c · am Eingang angekommene Kopfzeilen: $schluessel"
melde AK-04 "$([ "$ip" = 127.0.0.1 ] && echo ja || echo nein)" "Besucher-IP am Eingang: $ip (unterschoben waren 203.0.113.9–.12)"
melde AK-21 "$(echo "$koerper" | grep -qE "'id'|'tag'|unbekannt|konto-42" && echo nein || echo ja)" "weitergereichter Payload: $koerper"
sleep 1
ref=$(umami "select coalesce(referrer_domain,'∅'), coalesce(referrer_path,'∅'), coalesce(referrer_query,'∅') from website_event where url_path='/de/angriff-kopf' order by created_at desc limit 1")
melde AK-02 "$([ "$ref" = 'mail.example.lu||' ] || [ "$ref" = 'mail.example.lu|/|' ] || [ "$ref" = 'mail.example.lu|∅|∅' ] || [ "$ref" = 'mail.example.lu|/|∅' ] && echo ja || echo nein)" "Umami speichert Herkunft (Domain|Pfad|Abfrage): $ref"
land=$(umami "select coalesce(s.country,'∅') from website_event e join session s using (session_id) where e.url_path='/de/angriff-kopf' order by e.created_at desc limit 1")
melde AK-04 "$([ "$land" != US ] && echo ja || echo nein)" "Land der Sitzung trotz unterschobenem cf-ipcountry: US → gespeichert: $land"

echo "── 7 · Eingaben: Verstöße werden abgewiesen, nichts erreicht den Eingang"
pruefe_abweisung() { # $1 Beschreibung, $2 erwarteter Code, $3 Rumpf, weitere curl-Optionen
  local text=$1 soll=$2 rumpf=$3; shift 3
  local vor nach c
  vor=$(eingang_zeilen); c=$(zaehle "$rumpf" "$@"); nach=$(eingang_zeilen)
  melde "${AK_NR}" "$([ "$c" = "$soll" ] && [ "$vor" = "$nach" ] && echo ja || echo nein)" "$text → HTTP $c, am Eingang: $((nach-vor))"
}
AK_NR=AK-06; pruefe_abweisung 'Verwaltungspfad' 400 "$(seite '/de/admin/restaurants')"
AK_NR=AK-06; pruefe_abweisung 'Profilpfad' 400 "$(seite 'https://endlech.lu/lb/profile/edit')"
AK_NR=AK-07; pruefe_abweisung 'Token-Link' 400 "$(seite "/de/verify/$(printf 'ab%.0s' $(seq 1 32))")"
AK_NR=AK-09; pruefe_abweisung 'www.endlech.lu' 400 "$(printf '{"type":"event","payload":{"website":"%s","hostname":"www.endlech.lu","url":"/de/"}}' "$W")"
AK_NR=AK-09; pruefe_abweisung 'fremde Website-Kennung' 400 "$(printf '{"type":"event","payload":{"website":"11111111-1111-4111-8111-111111111111","hostname":"endlech.lu","url":"/de/"}}')"
AK_NR=AK-17; pruefe_abweisung 'E-Mail in Ereignisdaten' 400 "$(seite '/de/app' ',"name":"warteliste_eingetragen","data":{"liste":"app","email":"a@b.lu"}')"
AK_NR=AK-13; pruefe_abweisung 'Ortstext im Filter' 400 "$(seite '/de/restaurants' ',"name":"filter_angewandt","data":{"filter":"Esch-sur-Alzette"}')"
AK_NR=AK-12; pruefe_abweisung 'Telefonnummer als Kontaktweg' 400 "$(seite '/de/restaurants/1' ',"name":"kontaktweg_genutzt","data":{"art":"+352 123456"}')"
AK_NR=AK-21; pruefe_abweisung 'Typ identify' 400 "$(printf '{"type":"identify","payload":{"website":"%s","hostname":"endlech.lu","url":"/de/","id":"konto-42"}}' "$W")"
AK_NR=AK-03; pruefe_abweisung 'kaputtes JSON' 400 '{kein json'
AK_NR=AK-03; pruefe_abweisung 'Schachtelung tiefer als 16' 400 "$(python3 -c 'print("[" * 40 + "]" * 40)')"
AK_NR=AK-03; pruefe_abweisung 'Rumpf über 8 KB' 413 "$(seite '/de/' ",\"title\":\"$(head -c 9000 < /dev/zero | tr '\0' 'x')\"")"
AK_NR=AK-22; pruefe_abweisung 'Sec-GPC: 1' 204 "$(seite '/de/gpc')" -H 'Sec-GPC: 1'
AK_NR=AK-22; pruefe_abweisung 'DNT: 1' 204 "$(seite '/de/dnt')" -H 'DNT: 1'
c=$(zaehle "$(seite '/de/angriff-%3Cscript%3Ealert(1)%3C%2Fscript%3E' ',"title":"<script>alert(1)</script>"')")
melde AK-03 "$([ "$c" = 200 ] && echo ja || echo nein)" "Markup in Titel und Pfad: HTTP $c — wird als Text weitergereicht (Umami speichert Text, keine Ausgabe in der Anwendung)"

echo "── 3 · Zugriffsregeln: nur der Zählweg führt zu Umami"
for p in 'GET /api/send' 'GET /api/send/login' 'POST /api/auth/login' 'GET /api/websites' 'GET /api/heartbeat' 'GET /script.js' 'GET /login'; do
  m=${p%% *}; pf=${p#* }
  c=$(curl -s -o /dev/null -w '%{http_code}' -X "$m" "$B$pf")
  melde AK-25 "$([ "$c" = 404 ] || { [ "$pf" = /api/send ] && [ "$c" = 405 ]; } && echo ja || echo nein)" "$p → HTTP $c"
done

echo "── 6 · Schlüsselbindung: Eingang mit fremdem Zertifikat"
pkill -f 'node eingang.mjs' 2>/dev/null; wait 2>/dev/null; sleep 1
( cd "$QA" && node eingang.mjs fremd.key fremd.crt 39443 > eingang-fremd.log 2>&1 & ) ; sleep 1
docker exec -i qa11-db true
vor=$(eingang_zeilen); c1=$(zaehle "$(seite '/de/pin-probe-1')"); c2=$(zaehle "$(seite '/de/pin-probe-2')"); nach=$(eingang_zeilen)
melde AK-24 "$([ "$c1" = 202 ] && [ "$c2" = 202 ] && [ "$vor" = "$nach" ] && echo ja || echo nein)" \
  "fremdes Zertifikat: HTTP $c1 / $c2, beim Eingang angekommen: $((nach-vor)) (TLS-Handschlag abgebrochen, Unterbrecher greift)"
pkill -f 'node eingang.mjs' 2>/dev/null; wait 2>/dev/null; sleep 1
( cd "$QA" && node eingang.mjs eingang.key eingang.crt 39443 > eingang.log 2>&1 & ) ; sleep 1
(cd "$APP" && php bin/console cache:pool:clear cache.usage --env=prod >/dev/null 2>&1)
c=$(zaehle "$(seite '/de/pin-probe-3')")
melde AK-24 "$([ "$c" = 200 ] && echo ja || echo nein)" "richtiges Zertifikat, Unterbrecher geleert: HTTP $c"

echo "── 4 · Personendaten und Zieladresse im Protokoll der Anwendung"
LOG=$(ls -t ~/.symfony5/log/*.log | head -1)
anw=$(grep -v '"source":"server"' "$LOG")
melde AK-30 "$(echo "$anw" | grep -qE '39443|umami\.test|203\.0\.113|mail\.example|konto-42|X-Endlech' && echo nein || echo ja)" \
  "Anwendungsprotokoll: Zieladresse, unterschobene IPs, Herkunft, Kennung → $(echo "$anw" | grep -cE '39443|203\.0\.113|mail\.example|konto-42') Treffer"
# Hinweis, kein Prüfpunkt: `prod` schreibt nach stderr erst ab `error` (fingers_crossed). Die Warnung der
# Weiterleitung („Umami nicht erreichbar", nur Ausnahmeklasse) erreicht deshalb Sentry-Logs, nicht dieses Protokoll.
echo "ℹ️  Warnungen der Weiterleitung im Serverprotokoll: $(echo "$anw" | grep -c 'Nutzungsmessung:') (erwartet 0 — action_level: error)"

echo "── 3 · Deckel: 300 je Stunde, danach funktioniert die Seite weiter"
(cd "$APP" && php bin/console cache:pool:clear cache.rate_limiter --env=prod >/dev/null 2>&1)
erster=''; for i in $(seq 1 301); do c=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$B/api/send" -H 'Content-Type: application/json' -d '{}'); if [ "$c" = 429 ]; then erster=$i; break; fi; done
seite_nach=$(curl -s -o /dev/null -w '%{http_code}' "$B/de/restaurants")
keks=$(curl -s -D - -o /dev/null -X POST "$B/api/send" -H 'Content-Type: application/json' -d '{}' | grep -ci '^set-cookie')
melde AK-27 "$([ "$erster" = 301 ] && [ "$seite_nach" = 200 ] && [ "$keks" = 0 ] && echo ja || echo nein)" "erster 429 bei Aufruf $erster · /de/restaurants danach: $seite_nach · Set-Cookie auf der 429: $keks"
(cd "$APP" && php bin/console cache:pool:clear cache.rate_limiter --env=prod >/dev/null 2>&1)

echo "── 6 · Geheimnisse im Repository"
# ⚠ Der Rechnername des ANWENDUNGS-VPS steht seit Feature 08 bewusst in den Unterlagen und ist hier ausgenommen.
# Gesucht wird, was den zweiten VPS verriete: eine Schlüsselbindung, ein gesetzter Zähl-Eingang, ein anderer
# Hostinger-Rechnername, die Adresse aus dieser Prüfumgebung.
APP_HOST=$(grep -ohE 'srv[0-9]+\.hstgr\.cloud' "$APP/CLAUDE.md" | head -1)
treffer=$(cd "$APP" && git ls-files -co --exclude-standard | grep -vE '^(vendor|var|node_modules|public/build|qa/11)/' \
  | xargs grep -hoE "sha256//[A-Za-z0-9+/]{20,}|APP_UMAMI_UPSTREAM=['\"]?[a-z]+://[^'\" ]+|39443|srv[0-9]+\.hstgr\.cloud" 2>/dev/null \
  | grep -vF "$APP_HOST" | sort -u | sed -E 's/[A-Za-z0-9+/]{12,}/<maskiert>/g' | tr '\n' ' ')
melde AK-24 "$([ -z "$treffer" ] && echo ja || echo nein)" "Schlüsselbindung, gesetzter Zähl-Eingang oder anderer Rechnername im Repository: ${treffer:-keine}"

echo
echo "$ok bestanden, $nein durchgefallen"
