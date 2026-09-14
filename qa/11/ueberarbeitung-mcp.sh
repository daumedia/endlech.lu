#!/usr/bin/env bash
# QA Feature 11 · Überarbeitung 2026-09-14 — Zugang des Growth-Loops über die Umami-Domain (AK-29, AK-35, AK-39, AK-42).
#
# Gegen die Wegwerf-Instanz aus ueberarbeitung-umgebung.sh, per HTTPS über Traefik:
#   M1  Lese-Benutzer `growth-loop` wie in T33 (Rolle view-only, Team, team-view-only) — was er darf und was nicht
#   M2  mcp-umami als growth-loop: alle fünf Werkzeuge über MCP; steht die Umami-Adresse in einer Antwort?
#   M3  --check bei Zertifikat mit falschem Namen, selbst signiert, falschem Passwort, Zweitfaktor-Pflicht → Exit 3?
#
# Node vertraut der Wegwerf-CA über NODE_EXTRA_CA_CERTS. Aufruf: QA=<Prüfordner> bash qa/11/ueberarbeitung-mcp.sh
set -u
C=$QA/certs; U=https://localhost:39443; M=$HOME/.claude/skills/growth-loop/mcp-umami/server.js
WEBSITE=$(cat "$QA/website-id.txt")
export NODE_EXTRA_CA_CERTS=$C/ca.crt
api() { curl -s --cacert "$C/ca.crt" -o "$QA/api.json" -w '%{http_code}' "$@"; }
feld() { python3 -c "import json,sys;print(json.load(open('$QA/api.json'))$1)"; }
zertifikat() { cp "$C/$1.crt" "$C/aktiv.crt"; cp "$C/$1.key" "$C/aktiv.key"; docker restart qa11u-traefik >/dev/null
  for i in $(seq 1 60); do curl -sk -o /dev/null https://localhost:39443/api/heartbeat && return 0; sleep 0.25; done; }

zertifikat richtig
LESEPASSWORT=$(openssl rand -hex 24)
api -X POST $U/api/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"umami"}' >/dev/null; ADMIN=$(feld "['token']")
echo "── M1 · Lese-Benutzer wie T33"
# ⚠ JSON nie direkt in `echo "…$(curl -d "{…,…}")…"`: Bash zerlegt es dort an den Kommas (erster Lauf: drei
# Aufrufe mit kaputtem Rumpf, 400). Rumpf und Antwortcode deshalb immer über Variablen.
LESER_NAME=growth-loop-qa
json() { python3 -c 'import json,sys; print(json.dumps(dict(zip(sys.argv[1::2], sys.argv[2::2]))))' "$@"; }
erstes_id() { python3 -c "import json;d=json.load(open('$QA/api.json'));d=d[0] if isinstance(d,list) else d;print(d.get('id',''))"; }
R=$(json username $LESER_NAME password "$LESEPASSWORT" role view-only); c=$(api -X POST $U/api/users -H "Authorization: Bearer $ADMIN" -H 'Content-Type: application/json' -d "$R"); LESER=$(erstes_id)
echo "M1 · Benutzer $LESER_NAME (view-only) anlegen: $c"
R=$(json name endlech-qa); c=$(api -X POST $U/api/teams -H "Authorization: Bearer $ADMIN" -H 'Content-Type: application/json' -d "$R"); TEAM=$(erstes_id)
echo "M1 · Team anlegen: $c"
R=$(json teamId "$TEAM"); c=$(api -X POST $U/api/websites/$WEBSITE/transfer -H "Authorization: Bearer $ADMIN" -H 'Content-Type: application/json' -d "$R")
echo "M1 · Website ins Team: $c"
R=$(json userId "$LESER" role team-view-only); c=$(api -X POST $U/api/teams/$TEAM/users -H "Authorization: Bearer $ADMIN" -H 'Content-Type: application/json' -d "$R")
echo "M1 · $LESER_NAME als team-view-only: $c"
R=$(json username $LESER_NAME password "$LESEPASSWORT"); api -X POST $U/api/auth/login -H 'Content-Type: application/json' -d "$R" >/dev/null; LT=$(feld "['token']")
c=$(api "$U/api/websites" -H "Authorization: Bearer $LT"); n=$(python3 -c "import json;d=json.load(open('$QA/api.json'));print(len(d.get('data',d)) if isinstance(d,dict) else len(d))")
echo "M1 · $LESER_NAME listet Websites: $c · Anzahl $n"
c=$(api "$U/api/teams/$TEAM/websites" -H "Authorization: Bearer $LT"); echo "M1 · $LESER_NAME listet Team-Websites: $c · $(python3 -c "import json;d=json.load(open('$QA/api.json'));print(len(d.get('data',[])))") Website(s)"
c=$(api "$U/api/websites/$WEBSITE/stats?startAt=0&endAt=$(date +%s)000" -H "Authorization: Bearer $LT"); echo "M1 · $LESER_NAME liest Statistik: $c"
R=$(json name x domain x.example); c=$(api -X POST $U/api/websites -H "Authorization: Bearer $LT" -H 'Content-Type: application/json' -d "$R"); echo "M1 · $LESER_NAME legt Website an: $c"
R=$(json name umbenannt); c=$(api -X POST $U/api/websites/$WEBSITE -H "Authorization: Bearer $LT" -H 'Content-Type: application/json' -d "$R"); echo "M1 · $LESER_NAME ändert Website: $c"
c=$(api -X DELETE $U/api/websites/$WEBSITE -H "Authorization: Bearer $LT"); echo "M1 · $LESER_NAME löscht Website: $c"
R=$(json username boese password x12345678 role admin); c=$(api -X POST $U/api/users -H "Authorization: Bearer $LT" -H 'Content-Type: application/json' -d "$R"); echo "M1 · $LESER_NAME legt Admin an: $c"
c1=$(api $U/api/websites); c2=$(api "$U/api/websites/$WEBSITE/stats?startAt=0&endAt=1"); c3=$(curl -s --cacert "$C/ca.crt" -o /dev/null -w '%{http_code}' $U/login)
echo "M1 · ohne Anmeldung (AK-26): Websites $c1 · Statistik $c2 · Anmeldeseite $c3"
c=$(api -X POST $U/api/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"umami"}'); echo "M1 · Voreinstellung admin/umami in der Wegwerf-Instanz (Gegenprobe zu AK-28, dort unverändert): $c"

printf '{"url":"%s","benutzer":"%s","passwort":"%s"}' "$U" "$LESER_NAME" "$LESEPASSWORT" > "$QA/zugang.json"; chmod 600 "$QA/zugang.json"

echo "── M2 · mcp-umami als growth-loop, alle Werkzeuge über MCP"
node - "$M" "$QA/zugang.json" "$WEBSITE" > "$QA/mcp-antworten.txt" <<'EOF'
const { spawn } = require('node:child_process');
const [server, zugang, website] = process.argv.slice(2);
const p = spawn('node', [server], { env: { ...process.env, UMAMI_ZUGANG: zugang } });
let puffer = ''; const antworten = {};
p.stdout.on('data', (d) => { puffer += d; for (const z of puffer.split('\n').slice(0, -1)) { try { const j = JSON.parse(z); if (j.id) antworten[j.id] = j; } catch {} } puffer = puffer.split('\n').pop(); });
const send = (o) => p.stdin.write(JSON.stringify(o) + '\n');
const tools = [
  ['umami_websites', {}],
  ['umami_stats', { websiteId: website }],
  ['umami_metrics', { websiteId: website, type: 'path' }],
  ['umami_funnel', { websiteId: website, schritte: ['*/restaurants', 'filter_angewandt'] }],
  ['umami_conversion_nach_quelle', { websiteId: website, schritte: ['*/restaurants', 'kontaktweg_genutzt'] }],
];
send({ jsonrpc: '2.0', id: 1, method: 'initialize', params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'qa11', version: '1' } } });
setTimeout(() => { send({ jsonrpc: '2.0', method: 'notifications/initialized' }); tools.forEach(([name, args], i) => send({ jsonrpc: '2.0', id: 10 + i, method: 'tools/call', params: { name, arguments: args } })); }, 600);
setTimeout(() => { p.kill(); tools.forEach(([name], i) => { const a = antworten[10 + i]; const text = a ? JSON.stringify(a.result ?? a.error) : 'KEINE ANTWORT'; console.log(`${name}\t${a?.result?.isError ? 'Fehler' : 'ok'}\t${text.length} Zeichen\t${text.slice(0, 160).replace(/\s+/g, ' ')}`); }); }, 12000);
EOF
cut -f1-3 "$QA/mcp-antworten.txt" | sed 's/^/   /'
echo "M2 · Umami-Adresse (localhost:39443) in den Antworten: $(grep -c 'localhost:39443' "$QA/mcp-antworten.txt") · Passwort darin: $(grep -c "$LESEPASSWORT" "$QA/mcp-antworten.txt")"
UMAMI_ZUGANG=$QA/zugang.json perl -e 'alarm 60; exec @ARGV' node "$M" --check > "$QA/check-ok.txt" 2>&1; echo "M2 · --check gesund: Exit $? · Adresse darin: $(grep -c '39443' "$QA/check-ok.txt") · $(grep -E '✓' "$QA/check-ok.txt")"

echo "── M3 · --check bei Fehlern (Exit 3 erwartet, nie die Adresse)"
pruefe() { UMAMI_ZUGANG=$1 perl -e 'alarm 60; exec @ARGV' node "$M" --check > "$QA/check.txt" 2>&1; code=$?
  echo "M3 · $2: Exit $code · Adresse darin: $(grep -c '39443' "$QA/check.txt") · $(grep -E 'meldet ab' -A1 "$QA/check.txt" | tail -1 | cut -c1-120)"; }
zertifikat falscher-name; pruefe "$QA/zugang.json" "Zertifikat für falschen Namen"
zertifikat selbst;        pruefe "$QA/zugang.json" "selbst signiertes Zertifikat"
zertifikat richtig
printf '{"url":"%s","benutzer":"%s","passwort":"falsch"}' "$U" "$LESER_NAME" > "$QA/zugang-falsch.json"; pruefe "$QA/zugang-falsch.json" "falsches Passwort"
docker stop qa11u-umami >/dev/null; pruefe "$QA/zugang.json" "Umami angehalten (Proxy antwortet)"; docker start qa11u-umami >/dev/null
