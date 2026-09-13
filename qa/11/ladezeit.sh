#!/usr/bin/env bash
# QA Feature 11 · AK-37 — stellt die Bedingungen her und ruft qa/11/ladezeit.mjs.
# Vorbedingungen: qa/11/umgebung.md. Aufruf: QA=<Prüfordner mit eingang.mjs> APP=<Projekt> bash qa/11/ladezeit.sh
set -u
cd "$APP"
eingang_start() { ( cd "$QA" && node eingang.mjs eingang.key eingang.crt 39443 > eingang.log 2>&1 & ); sleep 1; }
eingang_stop() { pkill -f 'node eingang.mjs' 2>/dev/null; pkill -f 'node haengt.mjs' 2>/dev/null; sleep 1; }
unterbrecher_leeren() { php bin/console cache:pool:clear cache.usage cache.rate_limiter --env=prod >/dev/null 2>&1; }

cat > "$QA/haengt.mjs" <<'EOF'
// Zähl-Eingang, der Verbindungen annimmt und nie antwortet — der VPS, der Pakete schluckt.
import https from 'node:https';
import { readFileSync } from 'node:fs';
https.createServer({ key: readFileSync('eingang.key'), cert: readFileSync('eingang.crt') }, () => {}).listen(39443, '127.0.0.1');
EOF

echo "== normal"; eingang_stop; eingang_start; unterbrecher_leeren
CDP=http://127.0.0.1:9333 node qa/11/ladezeit.mjs normal

echo "== Umami angehalten"; docker stop qa11-umami >/dev/null; unterbrecher_leeren
CDP=http://127.0.0.1:9333 node qa/11/ladezeit.mjs umami-angehalten --funktionen
docker start qa11-umami >/dev/null

echo "== Eingang abgeschaltet"; eingang_stop; unterbrecher_leeren
CDP=http://127.0.0.1:9333 node qa/11/ladezeit.mjs eingang-aus --funktionen

echo "== Eingang hängt (nimmt an, antwortet nie)"; ( cd "$QA" && node haengt.mjs > /dev/null 2>&1 & ); sleep 1; unterbrecher_leeren
CDP=http://127.0.0.1:9333 node qa/11/ladezeit.mjs eingang-haengt --funktionen
eingang_stop

echo "== Skript blockiert (Werbeblocker)"; eingang_start; unterbrecher_leeren
CDP=http://127.0.0.1:9333 node qa/11/ladezeit.mjs skript-blockiert --blockiert --funktionen
unterbrecher_leeren
