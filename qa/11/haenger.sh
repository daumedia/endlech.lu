#!/usr/bin/env bash
# QA Feature 11 · BF-149 unabhängig nachgeprüft — Übergang gesund → hängend und echter Ablauf des Unterbrechers.
#
# Anders als die Reproduktion im Bericht wird der Unterbrecher NICHT von Hand geleert: Phase 1 beginnt mit einem
# gesunden Eingang (ein erfolgreicher Zählaufruf), dann hängt der Eingang, und zwölf Zählaufrufe kommen gleichzeitig.
# Phase 2 wartet, bis der Unterbrecher nach 60 s von selbst abläuft, und wiederholt das. Phase 3 prüft, dass nach
# einem wieder gesunden Eingang erneut gezählt wird. In den Wellen laufen drei Seitenabrufe mit.
#
# ⚠ Zwei frühere Läufe dieses Skripts am 2026-09-13 waren wertlos: `pkill -f 'node eingang.mjs'` beendete den
# Eingang nicht, der Hänger konnte den Port nicht belegen, und „Phase 1" lief gegen einen gesunden Eingang. Deshalb
# jetzt feste Prozessnummern, und vor jeder Welle wird geprüft, WER auf dem Port lauscht. Und: ein laufendes
# Bash-Skript nicht bearbeiten — bash liest es zeilenweise nach.
#
# Vorbedingungen: qa/11/umgebung.md. Aufruf: QA=<Prüfordner> APP=<Projekt> bash qa/11/haenger.sh
set -u
cd "$APP"
W=$(cat "$QA/website-id.txt")
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'
SPERRE=$(find /private/var/folders /tmp -maxdepth 4 -name 'sf.umami-weiterleitung*' 2>/dev/null | head -1)
rumpf() { printf '{"type":"event","payload":{"website":"%s","hostname":"endlech.lu","url":"%s"}}' "$W" "$1"; }
zaehl() { curl -s -o /dev/null -w "%{http_code} %{time_total}" -X POST http://127.0.0.1:8765/api/send -H 'Content-Type: application/json' -H "User-Agent: $UA" -d "$(rumpf "$1")"; }
lauscher() { lsof -nP -iTCP:39443 -sTCP:LISTEN -t 2>/dev/null | head -1; }
starte() { # $1 = Skript, Ergebnis: PID in $PID
  ( cd "$QA" && exec node "$@" > /dev/null 2>&1 ) & PID=$!
  for i in $(seq 1 40); do [ "$(lauscher)" = "$PID" ] && return 0; sleep 0.25; done
  echo "ABBRUCH: $1 lauscht nicht (Port gehört $(lauscher))"; exit 1
}
beende() { kill "$1" 2>/dev/null; wait "$1" 2>/dev/null; for i in $(seq 1 40); do [ -z "$(lauscher)" ] && return 0; sleep 0.25; done; echo "ABBRUCH: Port nicht frei"; exit 1; }
welle() {
  # ⚠ Jeder Aufruf schreibt in eine EIGENE Datei. Zwölf Hintergrundprozesse, die an dieselbe Datei anhängen,
  # verschmolzen im zweiten Lauf ihre Zeilen („Codes 5 ;", „kürzester s") — und ließen den Übergang aussehen,
  # als warte niemand. Von Hand mit getrennten Dateien nachgemessen: genau einer wartet 2,09 s.
  rm -rf "$QA/welle" && mkdir -p "$QA/welle"; pids=()
  for i in $(seq 1 12); do zaehl "/de/haenger-$1-$i" > "$QA/welle/$i.txt" & pids+=($!); done
  sleep 0.3
  halter=$(lsof -t "$SPERRE" 2>/dev/null | wc -l | tr -d ' ')
  for i in 1 2 3; do curl -s -o /dev/null -w "%{time_starttransfer}" http://127.0.0.1:8765/de/restaurants > "$QA/welle/seite$i.txt" & pids+=($!); done
  wait "${pids[@]}"
  zeiten=$(for i in $(seq 1 12); do cat "$QA/welle/$i.txt"; echo; done)
  lang=$(echo "$zeiten" | awk '$2 > 1 {n++} END {print n+0}')
  echo "$1: 12 Zählaufrufe, länger als 1 s: $lang (sortiert: $(echo "$zeiten" | sort -k2 -n | awk '{printf "%s %.2fs; ", $1, $2}')) · Prozesse mit offener Sperrdatei nach 0,3 s: $halter · Seiten bis zum ersten Byte: $(for i in 1 2 3; do printf '%ss ' "$(cat "$QA/welle/seite$i.txt")"; done)"
}

[ -z "$(lauscher)" ] || { echo "ABBRUCH: Port 39443 ist schon belegt"; exit 1; }
php bin/console cache:pool:clear cache.usage cache.rate_limiter --env=prod >/dev/null 2>&1
starte eingang.mjs eingang.key eingang.crt 39443; EINGANG=$PID
echo "gesund (Eingang PID lauscht): $(zaehl /de/haenger-gesund)"
beende "$EINGANG"
starte haengt.mjs; HAENGER=$PID
curl -sk -o /dev/null --max-time 1 https://127.0.0.1:39443/api/send; echo "Hänger lauscht und schweigt: curl-Exit $? (28 = Zeitüberschreitung)"
welle "Phase 1 · Übergang gesund → hängend"
echo "Phase 1b · direkt danach: $(zaehl /de/haenger-danach)"
sleep 62
welle "Phase 2 · Unterbrecher nach 62 s von selbst abgelaufen"
beende "$HAENGER"
starte eingang.mjs eingang.key eingang.crt 39443; EINGANG=$PID
sleep 62
echo "Phase 3 · Eingang wieder gesund, Unterbrecher abgelaufen: $(zaehl /de/haenger-wieder)"
beende "$EINGANG"
