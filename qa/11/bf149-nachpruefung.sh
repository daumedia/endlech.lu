#!/usr/bin/env bash
# BF-149 · Reproduktion: hängender Zähl-Eingang, sechs gleichzeitige Zählaufrufe, dazu ein Seitenabruf.
# Vorbedingungen: qa/11/umgebung.md, Abschnitte 2 und 3; statt eingang.mjs läuft haengt.mjs (nimmt Verbindungen an,
# antwortet nie). Aufruf: QA=<Prüfordner mit website-id.txt> bash qa/11/bf149-nachpruefung.sh <bezeichnung>
# „Runde 2" stellt den abgelaufenen Unterbrecher nach, indem sie den Pool leert — dieselbe Lage wie 60 s später.
set -u
W=$(cat "$QA/website-id.txt")
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'
rumpf="{\"type\":\"event\",\"payload\":{\"website\":\"$W\",\"hostname\":\"endlech.lu\",\"url\":\"/de/bf149\"}}"
runde() {
  php bin/console cache:pool:clear cache.usage cache.rate_limiter --env=prod >/dev/null 2>&1
  for i in 1 2 3 4 5 6; do
    curl -s -o /dev/null -w "zaehl$i %{http_code} %{time_total}\n" -X POST http://127.0.0.1:8765/api/send -H 'Content-Type: application/json' -H "User-Agent: $UA" -d "$rumpf" &
  done
  sleep 0.3
  curl -s -o /dev/null -w "seite %{http_code} erstesByte %{time_starttransfer}\n" http://127.0.0.1:8765/de/restaurants &
  wait
}
echo "== $1: Runde 1"; runde | sort
echo "== $1: Runde 2 (Unterbrecher abgelaufen, nachgestellt durch Leeren)"; runde | sort
