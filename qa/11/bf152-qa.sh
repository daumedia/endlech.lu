#!/usr/bin/env bash
# QA Feature 11 · Nachprüfung BF-152 an der laufenden Anwendung (Produktionsmodus, symfony server, echte Dateisperre).
#
# Die Reparatur legt die Sperre ohne automatische Freigabe an. Das ist die Stelle, an der sie den Normalbetrieb
# brechen kann: Wird der Platz auf einem Weg nicht freigegeben, bekommt ihn der nächste Aufruf desselben
# PHP-Prozesses nicht mehr, und ab dann wird still nichts gezählt. Deshalb zuerst der gesunde Betrieb, dann der
# Sperrausfall, dann der Hänger — und nach jedem Ausfall die Frage, ob wieder gezählt wird.
#
# Vorbedingungen: Anwendung auf 127.0.0.1:8765 mit APP_UMAMI_UPSTREAM=https://127.0.0.1:39443 und passender
# Schlüsselbindung; im Prüfordner QA liegen eingang.key/.crt, schnell.mjs (antwortet nach 30 ms, schreibt
# schnell.log) und haengt.mjs (antwortet nie).
#
# ⚠ Nie ein nacktes `wait`: Der Eingang läuft als Kindprozess dieses Skripts und endet nicht — `wait` ohne Prozessnummern
# wartet auf ihn und hält den Lauf für immer an. Genau so blieben zwei frühere Läufe von qa/11/haenger.sh stehen.
#
# Aufruf: QA=<Prüfordner> APP=<Projekt> WEBSITE=<Kennung> bash qa/11/bf152-qa.sh
set -u
cd "$APP"
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'
zaehl() { curl -s -o "$QA/rumpf-$2.txt" -w "%{http_code} %{time_total}" -X POST http://127.0.0.1:8765/api/send -H 'Content-Type: application/json' -H "User-Agent: $UA" -d "{\"type\":\"event\",\"payload\":{\"website\":\"$WEBSITE\",\"hostname\":\"endlech.lu\",\"url\":\"$1\"}}"; }
lauscher() { lsof -nP -iTCP:39443 -sTCP:LISTEN -t 2>/dev/null | head -1; }
starte() { ( cd "$QA" && exec node "$@" > /dev/null 2>&1 ) & PID=$!; for i in $(seq 1 40); do [ "$(lauscher)" = "$PID" ] && return 0; sleep 0.25; done; echo "ABBRUCH: $1 lauscht nicht"; exit 1; }
beende() { kill "$1" 2>/dev/null; wait "$1" 2>/dev/null; for i in $(seq 1 40); do [ -z "$(lauscher)" ] && return 0; sleep 0.25; done; echo "ABBRUCH: Port nicht frei"; exit 1; }
leeren() { php bin/console cache:pool:clear cache.usage cache.rate_limiter --env=prod >/dev/null 2>&1; }
angekommen() { local n=0; [ -f "$QA/schnell.log" ] && n=$(grep -c "$1" "$QA/schnell.log"); echo "${n:-0}"; }
unterbrecher() { grep -rl umami_unterbrecher var/share/prod/pools 2>/dev/null | head -1 | grep -q . && echo gesetzt || echo "nicht gesetzt"; }

[ -z "$(lauscher)" ] || { echo "ABBRUCH: Port 39443 belegt"; exit 1; }
rm -f "$QA/schnell.log"; leeren
starte schnell.mjs; GESUND=$PID

echo "── A · Gesunder Eingang: wird der Platz nach jedem Aufruf wieder frei?"
codes=''; for i in $(seq 1 20); do codes="$codes $(zaehl /de/a-folge-$i a$i | cut -d' ' -f1)"; done
echo "A1 · 20 Aufrufe nacheinander: Antworten$codes · angekommen $(angekommen /de/a-folge-) von 20"
pids=(); for i in $(seq 1 6); do zaehl /de/a-gleich-$i g$i > "$QA/g$i.code" & pids+=($!); done; wait "${pids[@]}"
echo "A2 · 6 Aufrufe gleichzeitig: Antworten $(for i in $(seq 1 6); do cut -d' ' -f1 "$QA/g$i.code"; echo; done | sort | uniq -c | tr -s ' ' | tr '\n' ';') · angekommen $(angekommen /de/a-gleich-) von 6"
codes=''; for i in $(seq 1 10); do codes="$codes $(zaehl /de/a-danach-$i d$i | cut -d' ' -f1)"; done
echo "A3 · danach 10 nacheinander: Antworten$codes · angekommen $(angekommen /de/a-danach-) von 10"

SPERRE=$(find /private/var/folders /tmp -maxdepth 4 -name 'sf.umami-weiterleitung*' 2>/dev/null | head -1)
echo "── B · Sperrdatei nicht zu öffnen (BF-152)"
chmod 000 "$SPERRE"
r1=$(zaehl /de/b-defekt-1 b1); r2=$(zaehl /de/b-defekt-2 b2)
echo "B1 · Sperrdatei chmod 000: $r1 (Rumpf $(cat "$QA/rumpf-b1.txt" | head -c 40)) · zweiter Aufruf $r2 · angekommen $(angekommen /de/b-defekt-) · Unterbrecher $(unterbrecher)"
chmod 644 "$SPERRE"
r3=$(zaehl /de/b-unterbrecher b3)
echo "B2 · Rechte zurück, Unterbrecher noch aktiv: $r3 · angekommen $(angekommen /de/b-unterbrecher)"
leeren
r4=$(zaehl /de/b-wieder b4)
echo "B3 · Unterbrecher geleert: $r4 · angekommen $(angekommen /de/b-wieder)"
codes=''; for i in $(seq 1 5); do codes="$codes $(zaehl /de/b-folge-$i bf$i | cut -d' ' -f1)"; done
echo "B4 · danach 5 nacheinander: Antworten$codes · angekommen $(angekommen /de/b-folge-) von 5"

echo "── C · Hängender Eingang: ein Platz, und danach wieder frei"
beende "$GESUND"; starte haengt.mjs; HAENGER=$PID; leeren
rm -rf "$QA/welle" && mkdir -p "$QA/welle"; pids=()
for i in $(seq 1 12); do zaehl /de/c-haengt-$i c$i > "$QA/welle/$i.txt" & pids+=($!); done
sleep 0.3; halter=$(lsof -t "$SPERRE" 2>/dev/null | wc -l | tr -d ' ')
wait "${pids[@]}"
zeiten=$(for i in $(seq 1 12); do cat "$QA/welle/$i.txt"; echo; done)
echo "C1 · 12 gleichzeitig: länger als 1 s: $(echo "$zeiten" | awk '$2 > 1 {n++} END {print n+0}') · Antworten $(echo "$zeiten" | cut -d' ' -f1 | sort | uniq -c | tr -s ' ' | tr '\n' ';') · sortiert: $(echo "$zeiten" | sort -k2 -n | awk '{printf "%.2f ", $2}')s · Sperrdatei nach 0,3 s offen bei $halter Prozess(en)"
# Einordnung: Auf den Eingang wartet genau der Aufruf mit ~2 s. Die übrigen stellen sich lokal hinter drei PHP-FPM-Workern
# an (je ~0,11 s: 100 ms Platzwartezeit plus Aufwand) — die letzten Stufen können über 1 s liegen, ohne auf Umami zu warten.
beende "$HAENGER"; starte schnell.mjs; GESUND=$PID; leeren
codes=''; for i in $(seq 1 5); do codes="$codes $(zaehl /de/c-wieder-$i cw$i | cut -d' ' -f1)"; done
echo "C2 · Eingang wieder gesund, Unterbrecher geleert, 5 nacheinander: Antworten$codes · angekommen $(angekommen /de/c-wieder-) von 5"
beende "$GESUND"; leeren
