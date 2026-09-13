# QA Feature 11 · Prüfumgebung

Stand: 2026-09-13 · Grundlage für alle Skripte in `qa/11/`

Die Prüfung lief **nicht gegen die echte Umami-Instanz**, sondern gegen einen lokalen Nachbau mit
derselben Umami-Version und derselben Umgebung, die `design.md` für den zweiten VPS vorsieht. Alles
hier ist Wegwerf-Material: selbst erzeugte Schlüssel, eine lokale Datenbank, eine lokale Website-Kennung.
**Kein Wert aus dieser Datei gehört in Coolify oder auf den zweiten VPS**, und kein Wert von dort gehört
hierher.

⚠ Was dieser Nachbau **nicht** zeigt: Land (die Besucheradresse ist `127.0.0.1`, eine Geo-Zuordnung
entsteht nicht), Standort des Servers, Firewall und SSH-Zugang des zweiten VPS, Anmeldeschutz und
Benutzerrechte in Umami. Die dazugehörigen Kriterien stehen im Bericht unter *nicht prüfbar*.

## Aufbau

```
Chromium (CDP 9333) ── http://endlech.lu:8765 ──▶ Anwendung, APP_ENV=prod (symfony server)
                                                     │  POST /api/send (geprüft, gekürzt)
                                                     ▼
                                   https://127.0.0.1:39443  Zähl-Eingang (eingang.mjs)
                                   selbst signiert, Schlüsselbindung, schreibt eingang.jsonl
                                                     │  nur POST /api/send
                                                     ▼
                                   http://127.0.0.1:39300  Umami 3.3.1 ── Postgres 15
```

`endlech.lu` zeigt im Browser per `--host-resolver-rules` auf `127.0.0.1`. Der Host muss so heißen:
Der Tracker zählt wegen `data-domains` nur dort — mit `localhost` wäre jedes „kein Zählaufruf" wertlos.

## 1 · Umami und Datenbank

```bash
docker network create qa11
docker run -d --name qa11-db --network qa11 -e POSTGRES_USER=umami -e POSTGRES_DB=umami \
  -e POSTGRES_PASSWORD=<wegwerf> postgres:15-alpine
docker run -d --name qa11-umami --network qa11 -p 127.0.0.1:39300:3000 \
  -e DATABASE_URL=postgresql://umami:<wegwerf>@qa11-db:5432/umami -e APP_SECRET=<wegwerf> \
  -e CLIENT_IP_HEADER=x-endlech-client-ip -e SKIP_LOCATION_HEADERS=1 -e SALT_ROTATION=day \
  -e DISABLE_TELEMETRY=1 -e DISABLE_UPDATES=1 ghcr.io/umami-software/umami:3.3.1
```

Danach über die Oberfläche auf `127.0.0.1:39300` das Passwort ändern, eine Website `endlech.lu`
anlegen und ihre Kennung in `website-id.txt` im Prüfordner ablegen.

## 2 · Zähl-Eingang

Im Prüfordner (außerhalb des Repositorys): Schlüssel und Zertifikat erzeugen, Schlüsselbindung ausrechnen,
Eingang starten.

```bash
openssl req -x509 -newkey rsa:2048 -nodes -keyout eingang.key -out eingang.crt -days 2 -subj /CN=qa11
openssl x509 -in eingang.crt -pubkey -noout | openssl pkey -pubin -outform der \
  | openssl dgst -sha256 -binary | base64 > pin.txt
node eingang.mjs eingang.key eingang.crt 39443
```

`eingang.mjs` ist ein TLS-Server, der jede Anfrage mit Kopfzeilen und Rumpf nach `eingang.jsonl` schreibt
und nur `POST /api/send` an Umami weitergibt (alles andere 404). Die Mitschrift ist der Nachweis dafür,
was die Anwendung **tatsächlich** überträgt — nicht, was ihr Quelltext verspricht. Für den Hänger-Fall
(`ladezeit.sh`) ersetzt ihn `haengt.mjs`: nimmt Verbindungen an, antwortet nie.

## 3 · Anwendung im Produktionsmodus

```bash
export APP_ENV=prod APP_DEBUG=0 APP_SECRET=<wegwerf>
export DATABASE_URL='mysql://root:root@127.0.0.1:3306/endlech_test?serverVersion=8.0&charset=utf8mb4'
export APP_UMAMI_WEBSITE_ID=$(cat website-id.txt)
export APP_UMAMI_UPSTREAM=https://127.0.0.1:39443
export APP_UMAMI_UPSTREAM_PIN=$(cat pin.txt)
php bin/console cache:clear --env=prod
symfony server:start --port=8765 --no-tls -d
```

Datenbank ist die Test-Datenbank samt Fixtures (`make test-db-setup`). Die Skripte räumen ihre Einträge
(`board_idea`, `board_vote`, `app_waitlist_entry`, `messenger_messages`) selbst wieder ab.

## 4 · Browser

```bash
# für browser-pruefung.mjs, ladezeit.mjs
chrome-headless-shell --headless --remote-debugging-port=9333 --user-data-dir=<prüfordner>/profil \
  "--host-resolver-rules=MAP endlech.lu 127.0.0.1, MAP www.endlech.lu 127.0.0.1" --no-first-run about:blank

# nur für offline.mjs — Service Worker brauchen einen sicheren Kontext
"Google Chrome for Testing" --headless=new --remote-debugging-port=9334 --user-data-dir=<prüfordner>/profil-offline \
  "--host-resolver-rules=MAP endlech.lu 127.0.0.1, MAP www.endlech.lu 127.0.0.1" \
  --unsafely-treat-insecure-origin-as-secure=http://endlech.lu:8765 --no-first-run about:blank
```

⚠ **Umami verwirft `HeadlessChrome` als Bot** (`isbot`). Die Skripte setzen deshalb eine gewöhnliche
Browserkennung; ohne das zählt Umami nichts, und jede Prüfung „ist in Umami sichtbar" schlägt fehl, ohne
dass an der Anwendung etwas falsch wäre.

⚠ **`chrome-headless-shell` ignoriert `--unsafely-treat-insecure-origin-as-secure`** — gemessen:
`isSecureContext` blieb `false`, der Service Worker registrierte sich nicht, und der Offline-Lauf zeigte
die Fehlerseite des Browsers statt `offline.html`.

⚠ **Die Offline-Emulation der Seite trennt den Service Worker nicht vom Netz.** Er lud die Detailseite
am Emulator vorbei, und es sah aus, als würde offline gezählt. `offline.mjs` setzt die Emulation deshalb
zusätzlich am Service-Worker-Ziel.

⚠ **`SALT_ROTATION=day` legt alle Aufrufe desselben Browsers am selben Tag in eine Sitzung.** Wer Umamis
Tabellen nach „der neuesten Sitzung" abfragt, bekommt eine ältere zurück. Die Skripte fragen über die
Ereignisse.

## 5 · Trichter über die Auswertungsschnittstelle

Nach `browser-pruefung.mjs` mit einem Anmelde-Token der lokalen Instanz:

```
POST http://127.0.0.1:39300/api/reports/funnel
{"websiteId": "<lokal>", "type": "funnel",
 "filters": {"startAt": <ms>, "endAt": <ms>, "timezone": "Europe/Luxembourg"},
 "parameters": {"startDate": "…", "endDate": "…", "window": 60,
   "steps": [{"type": "path", "value": "/*/app"}, {"type": "event", "value": "warteliste_eingetragen"}]}}
```

Ergebnis in `trichter.ausgabe.txt`. Die Schrittfolgen kommen aus `growth/config.json`; zum Vergleich
dieselben Schritte mit `*` nur am Anfang.

## Skripte und Ausgaben

| Skript | Prüft | Ausgabe |
|---|---|---|
| `browser-pruefung.mjs` | Hauptwege im Browser, Umami-Tabellen danach | `browser-pruefung.ausgabe.txt` |
| `angriff.sh` | selbst gebaute Zählaufrufe, Deckel, Schlüsselbindung, Protokoll, Repository | `angriff.ausgabe.txt` |
| `ladezeit.sh` + `ladezeit.mjs` | AK-37 unter fünf Bedingungen, Funktionen | `ladezeit.ausgabe.txt` |
| `offline.mjs` | EC-06 | `offline.ausgabe.txt` |
| `legal.py` | AK-31 in vier Sprachen | `legal.ausgabe.txt` |
| — (Schnittstelle, siehe 5) | AK-11, AK-14, AK-15 | `trichter.ausgabe.txt` |

## Abbau

```bash
symfony server:stop
rm -rf var/cache/prod
docker rm -f qa11-umami qa11-db && docker network rm qa11
pkill -f 'remote-debugging-port=933[34]'; pkill -f 'node (eingang|haengt).mjs'
```
