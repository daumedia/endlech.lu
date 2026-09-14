#!/usr/bin/env bash
# QA Feature 11 · Überarbeitung 2026-09-14 — Prüfumgebung, die den Produktionsweg nachbildet:
#
#   Anwendung (APP_ENV=prod, php -S)
#     └─ HTTP  ─▶ Traefik v3.5, Eingang web (:39480)      ─┐
#   Weiterleitungsklasse mit cURL-Client + Wegwerf-CA      │
#     └─ HTTPS ─▶ Traefik v3.5, Eingang websecure (:39443) ─┤ Zertifikat für localhost, von dieser CA signiert
#                   ├─ Host(localhost)                     → Umami 3.3.1 OHNE eigene Einstellungen ── Postgres 15
#                   └─ Host(localhost) && /qa-echo         → traefik/whoami (zeigt, was hinter Traefik ankommt)
#
# ⚠ Warum zwei Eingänge: Das cURL dieser PHP-Installation (Homebrew, OpenSSL 3.6) übernimmt weder
#   `curl.cainfo` (auch nicht per ini-Datei) noch `SSL_CERT_FILE`/`CURL_CA_BUNDLE` — nachgestellt am
#   2026-09-14. Der Anwendung lässt sich die Wegwerf-CA ohne Codeänderung nicht unterschieben. Der Weg
#   Anwendung → Proxy → Umami läuft deshalb über HTTP; die Zertifikatsprüfung wird mit der echten Klasse
#   `UmamiForwarder` und einem cURL-Client mit `cafile` ausgeübt (qa/11/ueberarbeitung-tls.php).
#
# Alles Wegwerf-Material im Prüfordner QA (außerhalb des Repositorys): CA, drei Server-Zertifikate
# (richtig · falscher Name · selbst signiert), Traefik-Konfiguration. Kein Wert davon gehört in Coolify.
#
# Aufruf: QA=<Prüfordner> bash qa/11/ueberarbeitung-umgebung.sh   (legt an und startet; gibt WEBSITE aus)
set -euo pipefail
: "${QA:?QA=<Prüfordner> setzen}"
mkdir -p "$QA/certs" "$QA/traefik"
cd "$QA/certs"

# ── Zertifikate ────────────────────────────────────────────────────────────────────────────────────────────
openssl req -x509 -newkey rsa:2048 -nodes -days 2 -keyout ca.key -out ca.crt -subj /CN=qa11u-ca >/dev/null 2>&1
signiere() { # name san
  openssl req -newkey rsa:2048 -nodes -keyout "$1.key" -out "$1.csr" -subj "/CN=$2" >/dev/null 2>&1
  printf 'subjectAltName=DNS:%s\n' "$2" > "$1.ext"
  openssl x509 -req -in "$1.csr" -CA ca.crt -CAkey ca.key -CAcreateserial -days 2 -out "$1.crt" -extfile "$1.ext" >/dev/null 2>&1
}
signiere richtig localhost
signiere falscher-name falsch.example
openssl req -x509 -newkey rsa:2048 -nodes -days 2 -keyout selbst.key -out selbst.crt -subj /CN=localhost \
  -addext subjectAltName=DNS:localhost >/dev/null 2>&1
cp richtig.crt aktiv.crt; cp richtig.key aktiv.key

# ── Traefik ────────────────────────────────────────────────────────────────────────────────────────────────
cat > "$QA/traefik/dynamisch.yml" <<'EOF'
http:
  routers:
    umami:
      rule: Host(`localhost`)
      entryPoints: [websecure]
      service: umami
      tls: {}
    umami-web:
      rule: Host(`localhost`)
      entryPoints: [web]
      service: umami
    echo:
      rule: Host(`localhost`) && PathPrefix(`/qa-echo`)
      entryPoints: [websecure]
      service: echo
      priority: 100
      tls: {}
  services:
    umami:
      loadBalancer:
        servers: [{ url: "http://qa11u-umami:3000" }]
    echo:
      loadBalancer:
        servers: [{ url: "http://qa11u-echo:80" }]
tls:
  stores:
    default:
      defaultCertificate:
        certFile: /certs/aktiv.crt
        keyFile: /certs/aktiv.key
EOF

# ── Container ─────────────────────────────────────────────────────────────────────────────────────────────
docker network create qa11u >/dev/null
docker run -d --name qa11u-db --network qa11u -e POSTGRES_USER=umami -e POSTGRES_DB=umami -e POSTGRES_PASSWORD=wegwerf postgres:15-alpine >/dev/null
for i in $(seq 1 30); do docker exec qa11u-db pg_isready -U umami -d umami >/dev/null 2>&1 && break; sleep 1; done; sleep 2
docker run -d --name qa11u-umami --network qa11u -e DATABASE_URL=postgresql://umami:wegwerf@qa11u-db:5432/umami \
  -e APP_SECRET=wegwerf ghcr.io/umami-software/umami:3.3.1 >/dev/null
docker run -d --name qa11u-echo --network qa11u traefik/whoami >/dev/null
docker run -d --name qa11u-traefik --network qa11u -p 127.0.0.1:39443:443 -p 127.0.0.1:39480:80 \
  -v "$QA/traefik:/etc/traefik/dynamisch:ro" -v "$QA/certs:/certs:ro" traefik:v3.5 \
  --entrypoints.websecure.address=:443 --entrypoints.web.address=:80 --providers.file.directory=/etc/traefik/dynamisch --providers.file.watch=true >/dev/null
for i in $(seq 1 90); do [ "$(curl -s --cacert ca.crt -o /dev/null -w '%{http_code}' https://localhost:39443/api/heartbeat)" = 200 ] && break; sleep 2; done
echo "Umami über Traefik: $(curl -s --cacert ca.crt -o /dev/null -w '%{http_code}' https://localhost:39443/api/heartbeat)"

# ── Website und Benutzer in der Wegwerf-Instanz ────────────────────────────────────────────────────────────
U=https://localhost:39443
T=$(curl -s --cacert ca.crt -X POST $U/api/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"umami"}' | python3 -c 'import sys,json;print(json.load(sys.stdin)["token"])')
curl -s --cacert ca.crt -X POST $U/api/websites -H "Authorization: Bearer $T" -H 'Content-Type: application/json' \
  -d '{"name":"endlech.lu","domain":"endlech.lu"}' | python3 -c 'import sys,json;print(json.load(sys.stdin)["id"])' > "$QA/website-id.txt"
echo "$T" > "$QA/admin-token.txt"
echo "Website angelegt: $(wc -c < "$QA/website-id.txt" | tr -d ' ') Zeichen Kennung"
