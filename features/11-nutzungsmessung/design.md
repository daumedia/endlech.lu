# 11 · Nutzung messen, ohne zu verfolgen — Systemdesign

Status: `architected` · Stand: 2026-09-13 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Jede öffentliche Seite lädt ein kleines Zählskript von **endlech.lu selbst** (`/zaehler.js`, der
Umami-Tracker in fester Version, als Datei im Repository). Das Skript schickt Seitenaufrufe und
benannte Ereignisse an **`/api/send` auf endlech.lu**. Dort nimmt die Anwendung jeden Zählaufruf an,
**prüft und kürzt ihn nach einer festen Liste** und reicht nur das Erlaubte an Umami auf dem zweiten
VPS weiter — verschlüsselt, an einen Eingang, der ausschließlich den Anwendungsserver hereinlässt.
Der Browser sieht den zweiten VPS nie. Die Umami-Oberfläche ist von außen gar nicht erreichbar; der
Betreiber und der Growth-Loop kommen über einen SSH-Tunnel heran.

Ob gemessen wird, entscheidet sich an drei Stellen, von außen nach innen: im Seitenkopf (kein Skript
auf Verwaltung, Profil und Token-Seiten, kein Skript ohne konfigurierte Website), im Browser (Schalter
in `/legal`, „Do Not Track", „Global Privacy Control", falsche Domain) und in der Weiterleitung
(dieselben Regeln noch einmal — weil jeder Client alles schicken kann).

Quellen, gelesen am 2026-09-13: Umami-Doku *Tracker configuration* und *Environment variables*
(docs.umami.is); Quelltext **Umami v3.3.1** (neueste Veröffentlichung, 2026-08-20):
`src/tracker/index.ts`, `src/app/api/send/route.ts`, `src/lib/detect.ts`, `src/lib/ip.ts`,
`src/lib/crypto.ts`, `src/queries/sql/reports/getFunnel.test.ts`; Symfony HttpClient im Projekt
(`vendor/symfony/http-client`, `CurlHttpClient` und `NativeHttpClient`).

## Seiten und Routen

| Route | Zweck | Zugang |
|---|---|---|
| `GET /zaehler.js` | Statische Datei: Umami-Tracker v3.3.1, unverändert, vom Webserver ohne PHP ausgeliefert | öffentlich |
| `POST /api/send` | **Weiterleitung** eines Zählaufrufs an Umami, nach Prüfung und Kürzung. Sprachfrei, zustandslos, eigener Routenblock | öffentlich, gedeckelt |
| `GET /api/send`, `/api/send/…`, `/api/websites`, `/api/login` … | existieren nicht → 404 bzw. 405 (AK-25) | — |
| `/{_locale}/legal` | **geändert:** Absatz zur Messung und Widerspruchsschalter im Datenschutzabschnitt | öffentlich |
| alle Seiten mit `base.html.twig` | **geändert:** Zählskript im Seitenkopf, wenn die Messregel es erlaubt | — |
| `/{_locale}/restaurants`, `/{_locale}/restaurants/{id}`, Wartelisten-Seiten, Board-Idee, `/presse`, `/open` | **geändert:** Auslöser für die Ereignisse der Trichter | unverändert |
| `/{_locale}/roadmap`, `/{_locale}/changelog` | **geändert:** Eintrag `usage_analytics` entfällt, Changelog-Eintrag bei Auslieferung | öffentlich |

Keine Seite danach, kein neuer Bildschirm. Auf dem zweiten VPS gibt es **keine** öffentlich
erreichbare Seite.

## Komponentenstruktur

### In der Anwendung

```
Seitenkopf (base.html.twig)
└── Zählskript-Einbindung        nur wenn die Messregel „ja" sagt; Attribute: Website-Kennung,
                                 Domain endlech.lu, ohne Abfrage und Anker, „Do Not Track"
                                 beachten, Vor-Versand-Prüfung (GPC)

Messregel (Twig-Funktion + kleiner Dienst)
├── Website-Kennung gesetzt?      leer (lokal, Test, CI) → nie messen
├── Verwaltung?                   Route beginnt mit `admin_`
├── Profil?                       Pfad `/{Sprache}/profile…`
└── Token-Seite?                  die Route trägt einen Parameter `token` — erfasst jede heutige
                                  und künftige Token-Route, ohne Liste

Vor-Versand-Prüfung (im App-Bundle)   verwirft jeden Zählaufruf bei „Global Privacy Control"

Ereignis-Auslöser (Stimulus-Controller)
├── beim Erscheinen               feuert ein Ereignis, sobald das Element erscheint
│                                 (Wartelisten-Erfolg)
├── beim Klick                    feuert ohne zu warten und ohne die Navigation anzuhalten
│                                 (Kontaktwege, Presse-Kit, Datensatz)
├── beim Absenden                 Filterformular: sammelt nur Filternamen, nie den Ortstext;
│                                 Board: nur wenn die Idee noch nicht unterstützt war
└── ohne Zählskript               tut nichts, wirft nichts

Widerspruchsschalter (/legal, Stimulus-Controller)
├── Zustand                       „an" / „aus" aus dem Browserspeicher (`umami.disabled`)
├── Umschalten                    schreibt bzw. entfernt den Eintrag; kein Cookie
└── ohne JavaScript               Schalter bleibt verborgen, Hinweis „ohne JavaScript wird nicht
                                  gemessen" (EC-02)

Weiterleitung /api/send (Controller + Dienst)
├── Vorab                         Deckel (Subscriber), `DNT: 1` oder `Sec-GPC: 1` → 204, nichts
│                                 weitergeleitet
├── Prüfen und kürzen             nach dem Ereigniskatalog (unten); Verstoß → 400, nichts
│                                 weitergeleitet
├── Weiterreichen                 eigener HTTP-Client ohne Protokollierung, Zeitlimit 2 s,
│                                 Gesamtdauer 3 s, Schlüsselbindung an den Umami-Eingang
├── Unterbrecher                  nach einem Fehlschlag 60 s lang nichts weiterleiten → 202
└── Antwort                       Umamis Antwort unverändert (enthält nur das Sitzungs-Token
                                  für den nächsten Aufruf) oder `{}`

Ereigniskatalog (eine Klasse, einzige Quelle)
└── erlaubte Ereignisnamen, je Ereignis erlaubte Datenfelder und Werte
```

### Ereigniskatalog

| Ereignis | Ausgelöst | Datenfelder (einzige erlaubte) | Trichter |
|---|---|---|---|
| `filter_angewandt` | Absenden des Filterformulars der Restaurantliste | `filter`: kommagetrennte Liste aus den **festen** Filterschlüsseln (`verified`, `wheelchair`, `toilet`, `dogs`, `lighting`, `changing_table`, `disabled_parking`, `open`, `vegan`, `vegetarian`, `halal`, `lang_*`, dazu `kueche` und `ort` **als Merker ohne Wert**) | Suche, Schritt 2 |
| `kontaktweg_genutzt` | Klick auf einen Kontaktweg der Detailseite | `art`: `website`, `telefon`, `email`, `instagram`, `facebook`, `tiktok`, `bestellweg`; bei Bestellweg zusätzlich `plattform` aus `OrderingPlatform` | Suche, Schritt 4 |
| `warteliste_eingetragen` | Erscheinen der Erfolgsmeldung (gemeinsames Erfolgs-Partial, das nur bei Erfolg gerendert wird) | `liste`: `partner`, `organisation`, `app` | Wartelisten, Schritt 2 |
| `zustimmung_gegeben` | Absenden des Zustimm-Formulars einer noch nicht unterstützten Idee | keine | — |
| `presse_kit_geladen` | Klick auf den Download des Presse-Kits | keine | — |
| `datensatz_geladen` | Klick auf CSV- oder JSON-Download auf `/open` | `format`: `csv`, `json` | — |

Seitenaufrufe brauchen keinen Eintrag; sie laufen mit dem Pfad.

### Trichter (in Umami als Bericht und in `growth/config.json`)

| Trichter | Schritte (Pfade mit `*`, sonst Ereignis) | Fenster |
|---|---|---|
| **Suche** | `/*/restaurants` → `filter_angewandt` → `/*/restaurants/*` → `kontaktweg_genutzt` | 60 min |
| **Warteliste App** | `/*/app` → `warteliste_eingetragen` | 60 min |
| **Warteliste Partner** | `/*/partner` → `warteliste_eingetragen` | 60 min |
| **Warteliste Organisationen** | `/*/organisationen*` → `warteliste_eingetragen` | 60 min |

Umami übersetzt `*` in jedem Schritt in ein `LIKE`-Muster, auch mitten im Pfad
(`getFunnel.test.ts`: `'*/thanks'` → `'%/thanks'`). Die Sprachen laufen damit ohne Zusatz zusammen
(AK-14); getrennt je Sprache genügt ein Schritt mit festem Präfix (AK-05).

⚠ Die Wartelisten-Trichter trennen sich über den ersten Schritt, nicht über `liste`: Umamis Trichter
filtert Ereignisse zwar nach Datenfeldern, der Growth-Loop kennt aber nur Pfade und Ereignisnamen.

⚠ **Der Growth-Loop wertet genau eine Kette aus** (`conversion_events`). Dort steht der Suchtrichter;
die drei Wartelisten-Ketten stehen unter einem eigenen Schlüssel `weitere_trichter` in
`growth/config.json` und werden als Umami-Berichte angelegt. Loop 2 kann sie auf Nachfrage mit
demselben Werkzeug abfragen (`umami_funnel`).

### Auf dem zweiten VPS (Betrieb, außerhalb des Repositorys)

```
Zweiter VPS (Hostinger, Deutschland, neben Uptime Kuma)
├── Umami v3.3.x                  Container, lauscht nur auf 127.0.0.1
│   └── PostgreSQL                Container, nur im internen Netz des Verbunds
├── Zähl-Eingang                  Reverse Proxy auf eigenem Port, TLS mit selbst signiertem
│                                 Zertifikat, lässt nur `POST /api/send` durch
├── Firewall                      Port des Zähl-Eingangs nur für die Adresse des Anwendungs-VPS
├── Tunnel-Benutzer               eigener Linux-Benutzer ohne Befehlszeile; sein Schlüssel darf nur
│                                 zu 127.0.0.1:<Umami-Port> weiterleiten
└── Länderdatenbank               MMDB-Datei nur mit Ländern (Lizenz prüft der Betrieb)
```

Umami-Einstellungen (Umgebung):

| Variable | Wert | Wofür |
|---|---|---|
| `CLIENT_IP_HEADER` | eigene Kopfzeile der Weiterleitung | Umami nimmt die Besucheradresse **nur** aus ihr — alle Standard-Kopfzeilen entfernt die Weiterleitung |
| `SKIP_LOCATION_HEADERS` | gesetzt | Umami ignoriert Orts-Kopfzeilen (`cf-ipcountry` u. a.), die ein Client sonst unterschieben könnte |
| `GEOLITE_DB_PATH` | Länderdatenbank | Region und Stadt bleiben leer (`detect.ts` liest `subdivisions`/`city` nur, wenn vorhanden) |
| `SALT_ROTATION` | `day` | Die Sitzungskennung wechselt täglich statt monatlich (Entscheidung 9) |
| `DISABLE_TELEMETRY`, `DISABLE_UPDATES` | gesetzt | Umami ruft von sich aus nirgends an |
| `DISABLE_BOT_CHECK` | **nicht** gesetzt | Bekannte Bots bleiben ungezählt (`isbot`, AK-10) |
| `APP_SECRET` | eigener, zufälliger Wert | Grundlage der Sitzungskennung; nicht das `APP_SECRET` der Anwendung |

### Zugang des Growth-Loops (außerhalb des Repositorys)

```
~/.config/umami/zugang.json       URL auf den lokalen Tunnel-Port, Benutzer growth-loop,
                                  Tunnel-Block (Ziel, Benutzer, Schlüsselpfad) — nie im Repo
~/.config/umami/tunnel_ed25519    eigener Schlüssel nur für den Tunnel
mcp-umami (Skill growth-loop)     öffnet den Tunnel beim ersten Aufruf, schließt ihn beim Ende;
                                  ohne Tunnel → „Loop 2 meldet ab", kein Abbruch des Laufs
```

⚠ Diese Änderung liegt im **globalen** Skill `~/.claude/skills/growth-loop/`, nicht im Projekt. Sie
wird im Aufgabenplan als eigene Aufgabe geführt und in der QA über AK-39 geprüft.

### Zustände

| Bildschirm | leer | ladend | Fehler | gefüllt |
|---|---|---|---|---|
| Jede Seite | ohne Website-Kennung: kein Skript | Skript lädt `defer`, blockiert nichts | Skript oder Weiterleitung nicht erreichbar: nichts sichtbar (AK-37) | Zählaufruf geht im Hintergrund |
| Schalter in `/legal` | ohne JavaScript: verborgen, Hinweistext | — | Browserspeicher gesperrt: Schalter zeigt „nicht verfügbar", Messung bleibt wie eingestellt | „Messung an" / „Messung aus", mit `aria-pressed` |
| Umami (Betreiber) | vor dem ersten Aufruf: keine Daten | — | Tunnel zu: nicht erreichbar | Seitenaufrufe, Ereignisse, Trichter |

## Datenmodell

**Keine Änderung am Datenmodell der Anwendung.** Keine Entity, keine Migration.

Was Umami speichert (PostgreSQL auf dem zweiten VPS, Schema von Umami, nicht von uns), nach der
Kürzung durch die Weiterleitung:

| Umami-Datensatz | Felder mit Inhalt | Leer, weil die Weiterleitung sie entfernt oder Umami sie nicht bestimmen kann |
|---|---|---|
| Sitzung | Kennung (täglich wechselnd), Browser, Betriebssystem, Gerät, Bildschirmgröße, Sprache, **Land** | Region, Stadt (Länderdatenbank); `distinctId` (Feld `id` entfernt) |
| Seitenaufruf / Ereignis | Pfad, Seitentitel, Herkunftsdomain, Ereignisname, Zeitpunkt | Abfrage (`url_query`), Herkunftspfad und -abfrage, `tag`, UTM- und Klick-Kennungen (stehen nur in der Abfrage) |
| Ereignisdaten | nur die Felder aus dem Ereigniskatalog | alles andere wird vorher abgewiesen |

**Die IP-Adresse wird nicht gespeichert** — Umami nutzt sie beim Eingang für Land und
Sitzungskennung (`route.ts`: `uuid(sourceId, ip, userAgent, sessionSalt)`) und verwirft sie.
Aufbewahrung: unbegrenzt (Spec, Decision Log #12). Löschregel: Es gibt keinen Bezug zu einem Konto,
also nichts, was bei einer Kontolöschung mitgehen müsste.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Besucher | — | Zählaufrufe über `/api/send`, im Rahmen des Ereigniskatalogs | Weiterleitung (Prüfung, Deckel); Umami ist für ihn nicht erreichbar |
| Anwendungsserver | — | `POST /api/send` am Zähl-Eingang | Firewall (nur seine Adresse), Reverse Proxy (nur dieser Pfad), Schlüsselbindung im Client |
| Betreiber | alles in Umami | Einstellungen, Websites, Benutzer | Umami-Konto mit eigenem Namen und Passwort (Voreinstellung ersetzt), 2FA; Zugang nur per SSH-Tunnel |
| Growth-Loop | die Website endlech.lu | nichts | Umami-Rolle „view-only" plus Team-Rolle „team-view-only"; Tunnel-Schlüssel ohne Befehlszeile, nur Weiterleitung zu Umami |
| Jeder andere im Netz | nichts | nichts | Umami lauscht nur auf 127.0.0.1; Zähl-Eingang per Firewall gesperrt |

⚠ **Die Weiterleitung ist die einzige Grenze für den Inhalt.** Tracker-Optionen (ohne Abfrage, ohne
Anker, Domain, „Do Not Track") helfen nur ehrlichen Browsern; ein selbst gebauter Zählaufruf umgeht
sie alle. Deshalb wiederholt die Weiterleitung jede Regel, die den Inhalt betrifft — nach der
Projektkonvention „Die Prüfung gehört dorthin, wo der Wert hereinkommt".

### Was die Weiterleitung prüft und kürzt

| Teil des Zählaufrufs | Regel | Bei Verstoß |
|---|---|---|
| Methode, Größe | nur `POST`, Rumpf höchstens 8 KB, JSON | 405 / 413 / 400 |
| `type` | nur `event` (Seitenaufruf und Ereignis); `identify` abgewiesen | 400 |
| `website` | exakt die konfigurierte Kennung | 400 |
| `hostname` | exakt `endlech.lu` (nicht `www.`) | 400 (AK-09) |
| `url` | nur Pfad; Abfrage und Anker werden entfernt; Pfad unter `/{Sprache}/admin`, `/{Sprache}/profile` oder mit Token-Muster (64 Hex-Zeichen) | Pfad gekürzt bzw. 400 (AK-03, AK-06, AK-07) |
| `referrer` | fremde Herkunft → nur `https://<domain>/`; eigene Herkunft → entfällt | gekürzt (AK-02) |
| `title` | höchstens 200 Zeichen | gekürzt |
| `name`, `data` | Ereignisname und Datenfelder nur aus dem Ereigniskatalog | 400 (AK-12, AK-13, AK-17) |
| `id`, `tag` | entfernt | — (AK-21) |
| `screen`, `language` | Format geprüft (`1234x567`, Sprachkürzel) | entfällt |
| Kopfzeilen an Umami | **nur** `Content-Type`, `User-Agent` (≤ 512 Zeichen), `x-umami-cache` (≤ 2 KB) und die eigene IP-Kopfzeile mit `Request::getClientIp()`; keine Cookies, kein `Accept-Language`, kein `X-Forwarded-For`, keine Orts-Kopfzeilen | — |
| `DNT: 1` oder `Sec-GPC: 1` an der Anfrage | nichts weiterleiten | 204 (AK-22, Rückhalt) |

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `POST /api/send` | **300 je Stunde je Adresse**, gleitendes Fenster, eigenes Kontingent `usage_collect` | 429, nichts weitergeleitet; der Tracker verwirft die Antwort still, die Seite merkt nichts (AK-27) | `config/packages/framework.yaml` (+ `when@test` 10000), Zweig im `RouteRateLimitSubscriber` — derselbe Ort wie Sitemap und Datensatz |
| `POST /api/send` | 8 KB Rumpf, Katalogprüfung | 413 / 400 | Weiterleitung |
| Weiterleitung → Umami | Zeitlimit 2 s, Gesamtdauer 3 s; Unterbrecher 60 s nach einem Fehlschlag | 202 `{}` ohne Wartezeit | Weiterleitungsdienst; Unterbrecher-Merker im Cache-Pool der Anwendung |
| Zähl-Eingang auf dem zweiten VPS | nur Adresse des Anwendungs-VPS, nur `POST /api/send` | Verbindung abgewiesen / 404 | Firewall und Reverse Proxy auf dem VPS (Betrieb) |
| Umami-Anmeldung | nicht öffentlich erreichbar | — | Umami nur auf 127.0.0.1, Zugang per Tunnel (AK-26) |
| Tunnel-Schlüssel | nur Weiterleitung zu `127.0.0.1:<Umami-Port>` | Befehlszeile und andere Ziele abgewiesen | `authorized_keys` des Tunnel-Benutzers mit Einschränkungen (AK-38) |

⚠ `TRUSTED_PROXIES` ist in Produktion gesetzt (am 2026-09-13 über die `https://`-Weiterleitung
nachgeprüft); ohne es teilten sich alle Besucher einen Deckel.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| **Umami v3.3.x**, selbst betrieben auf dem zweiten VPS (Hostinger, Deutschland, bestehender Auftragsverarbeitungsvertrag) | Seitenaufrufe, Herkunft, Ereignisse, Trichter | Pfad **ohne** Abfrage, Seitentitel, Herkunftsdomain, Bildschirmgröße, Sprache, Browserkennung, Ereignisname mit Katalogfeldern; die Besucher-IP in einer eigenen Kopfzeile, nur für Land und Sitzungskennung | Abfrage und Anker, Herkunftspfad und -abfrage, `id`, `tag`, alle Cookies, alle übrigen Kopfzeilen (`Accept-Language`, `X-Forwarded-For`, `cf-*` …); ganze Zählaufrufe von Verwaltung, Profil, Token-Seiten, mit `DNT`/`GPC`, von `www.` |

Kein weiterer Dienst. Das Zählskript liegt im Repository; kein CDN.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **Weiterleitung in der Anwendung** (Controller, `/api/send`) | Weiterleitung im Proxy von Coolify; eigene Subdomain | Die Spec verlangt, dass der VPS verborgen bleibt (Decision Log #3). Eine Proxy-Regel in Coolify wäre eine von Hand gepflegte Einstellung, die kein Prüflauf misst und die niemand kürzen kann — dasselbe Argument, das die Sicherheits-Kopfzeilen nach PHP gebracht hat |
| 2 | **Tracker als Datei im Repository** (`public/zaehler.js`, v3.3.1, MIT) | vom Umami-Server durchreichen; npm-Paket in das App-Bundle | Lädt ohne PHP und ohne zweiten VPS (AK-37). Im App-Bundle ginge `document.currentScript` verloren, aus dem der Tracker seine Einstellungen liest. ⚠ Wer Umami aktualisiert, ersetzt die Datei mit — der Aufgabenplan führt einen Prüflauf, der die Version im Dateikopf mit der dokumentierten vergleicht |
| 3 | **Eigene Ereignis-Auslöser** statt `data-umami-event`-Attributen | Umamis Klick-Attribute | Der Tracker hält bei Links ohne `target="_blank"` die Navigation an, bis der Zählaufruf fertig ist (`index.ts`, `onClick`). Bei `tel:` und `mailto:` wartete der Besucher auf die Messung — und bei langsamer Weiterleitung spürbar |
| 4 | **Prüfen und kürzen in der Weiterleitung**, zusätzlich zu den Tracker-Optionen | nur Tracker-Optionen | Jeder Client kann einen Zählaufruf selbst bauen; die Zusagen AK-03, AK-12, AK-17, AK-21 wären sonst Bitten |
| 5 | **TLS mit selbst signiertem Zertifikat und Schlüsselbindung (`pin-sha256`)**, Firewall auf die Adresse des Anwendungs-VPS | WireGuard zwischen den VPS; öffentliches Zertifikat | Ein öffentliches Zertifikat stünde in den Zertifikatslogs (Spec, Decision Log #11). WireGuard bräuchte Netzwerkkonfiguration am Coolify-Container. `CurlHttpClient` unterstützt `pin-sha256` (`CURLOPT_PINNEDPUBLICKEY`), der native Client nicht — `curl` liegt im Basisimage (`Dockerfile`, Kommentar zu den mitgelieferten Erweiterungen); der Bau prüft, dass der Container den cURL-Client wählt |
| 6 | **Eigener HTTP-Client ohne Protokollierung** | der Standard-Client (`http_client`) | Der Kanal `http_client` ist in `prod` nicht ausgeschlossen, und `fingers_crossed` schreibt bei einer Warnung den ganzen Puffer — samt Adresse des zweiten VPS. Dieselbe Lehre wie beim Puls (BE-01); geloggt wird bei Fehlschlag nur die Ausnahmeklasse |
| 7 | **Synchron mit 2 s Zeitlimit und 60-s-Unterbrecher** | über den Messenger asynchron | Asynchron füllte jeder Seitenaufruf die Tabelle `messenger_messages`, und Umamis Sitzungs-Token käme nie beim Browser an. Der Unterbrecher begrenzt die Last bei ausgefallenem VPS auf einen hängenden Aufruf je Minute |
| 8 | **Länderdatenbank** statt Stadtdatenbank | IP nicht weitergeben; volle Datenbank | Ohne IP gäbe es kein Land und nur eine Sitzung für alle. Mit Stadtdatenbank müsste man Region und Stadt nachträglich löschen. Die Länderdatenbank erfüllt AK-04 an der Quelle |
| 9 | **`SALT_ROTATION=day`** | Umamis Vorgabe `month` | Kürzere Verknüpfbarkeit einer Sitzung. Trichter mit 60-min-Fenster sind unberührt; wiederkehrende Besucher über Tage hinweg sind nicht erkennbar — für die Fragen der Spec nicht nötig. `/legal` nennt „wechselt täglich" (AK-31) |
| 10 | **Widerspruch über `umami.disabled` im Browserspeicher** | eigener Merker plus Vor-Versand-Prüfung | Der Tracker prüft den Schlüssel vor **jedem** Versand selbst (`trackingDisabled()`); ein eigener Merker wäre eine zweite Stelle, die auseinanderlaufen kann |
| 11 | **GPC in der Vor-Versand-Prüfung, DNT per Tracker-Option; beides zusätzlich in der Weiterleitung** | nur Weiterleitung; Skript bei DNT/GPC gar nicht ausliefern | AK-22 verlangt, dass **kein** Zählaufruf den Browser verlässt — das geht nur im Browser. Das Skript serverseitig wegzulassen machte das HTML von Kopfzeilen abhängig. Die Weiterleitung ist der Rückhalt für Clients, die die Option ignorieren |
| 12 | **Pfade `/zaehler.js` und `/api/send`** | beliebige andere | Liegt das Skript in der Wurzel, schickt der Tracker ohne weitere Einstellung an `/api/send` (`index.ts`: Host aus dem Skriptpfad, sofern der Platzhalter `__COLLECT_API_HOST__` beim Umami-Build leer ersetzt wurde — der Bau prüft das am ausgelieferten Skript und setzt sonst `data-host-url`). `/api/` nimmt der Service Worker grundsätzlich aus (`public/sw.js`, Zeile 94), die robots.txt sperrt es bereits. Kein Verzeichnis `public/api` — BF-100 |
| 13 | **Token-Seiten über den Routenparameter `token` erkennen** | Liste aus `SeoRegistry::EXCLUDED_ROUTES` | Die SEO-Liste enthält Anmeldung und Registrierung, die hier gemessen werden sollen (Spec, Decision Log #5). Der Parameter erfasst jede künftige Token-Route ohne Pflege |
| 14 | **Zustimmung beim Absenden, nur wenn noch nicht unterstützt** | nur nach erfolgreicher Speicherung | Die Zustimmung ist ein Umschalter (`BoardVoteService::toggle`) und leitet ohne Rückmeldung zurück. Ein Erfolgsmerker bräuchte eine Änderung am Board-Controller für ein reines Messereignis. Gezählt würde auch ein Versuch, der am Deckel oder am CSRF-Token scheitert — beides selten, im Bericht benannt |
| 15 | **Wartelisten-Erfolg über das gemeinsame Erfolgs-Partial** | am Absenden-Knopf | Das Partial wird nur bei Erfolg gerendert (Turbo-Stream); ein Klick auf „Absenden" zählte auch fehlerhafte Versuche (AK-16) |
| 16 | **Tunnel im MCP-Server des Growth-Loops, bei Bedarf** | Tunnel dauerhaft offen; Tunnel im Laufprotokoll des Skills | Ein dauerhafter Tunnel ist ein offener Weg ohne Anlass. Im MCP-Server liegt der Zugang an einer Stelle, und ein Fehlschlag wird zu „Loop 2 meldet ab" (AK-39) |
| 17 | **Zweiter Trichterschlüssel `weitere_trichter`** in `growth/config.json` | Wartelisten-Trichter nur in Umami | Die Spec verlangt beide Schrittfolgen in der Konfiguration (AK-35); der Loop wertet weiterhin eine Kette aus |

## Unterlagen, die mitziehen

| Datei | Änderung | AK |
|---|---|---|
| `translations/messages.{de,en,fr,lb}.yaml` | Absatz zur Messung im Datenschutzabschnitt, Schaltertexte | AK-23, AK-31 |
| `templates/impressum/index.html.twig` | Absatz und Schalter im Abschnitt `#datenschutz` | AK-23, AK-31 |
| `src/Roadmap/RoadmapRegistry.php`, `translations/roadmap.*.yaml` | Eintrag `usage_analytics` entfernen | AK-32 |
| `src/Roadmap/ChangelogRegistry.php`, `translations/changelog.*.yaml` | beim Release: `SHOWN` mit Text in vier Sprachen | AK-32 |
| `docs/prd.md` | „Es gibt kein Web-Analytics" und Zeile „Web-Analytics" in „Bewusst nicht gebaut" ersetzen | AK-33 |
| `docs/datenschutz.md` | neuer Verarbeitungseintrag „Nutzungsmessung (Umami, selbst betrieben)"; BE-02 geschlossen; Standort ohne Adresse | AK-31 |
| `CLAUDE.md` | Abschnitt zu Feature 11 mit den Fallstricken (Datei ersetzen bei Umami-Update, kein `public/api`, eigener Client ohne Log) | — |
| `growth/config.json` | `umami.website_id`, `conversion_events` (Suche), `weitere_trichter` | AK-35 |

## Betriebsschritte vor dem Bau (Betreiber)

1. **Standort des zweiten VPS bestimmen** (OF-02): Land aus der Adresse, **nur das Ergebnis** notieren.
   Ergebnis „DE" in `docs/datenschutz.md`, ohne Rechnername und Adresse.
2. Umami v3.3.x mit PostgreSQL aufsetzen, nur auf 127.0.0.1, mit den Umgebungswerten oben.
3. Zähl-Eingang: Reverse Proxy mit selbst signiertem Zertifikat, nur `POST /api/send`, Firewall auf die
   Adresse des Anwendungs-VPS. Den öffentlichen Schlüssel als `pin-sha256` bestimmen.
4. Umami-Konto: Voreinstellung `admin`/`umami` ersetzen, 2FA; Website `endlech.lu` anlegen, Team
   anlegen; Benutzer `growth-loop` (view-only, team-view-only, ohne 2FA).
5. Tunnel-Benutzer ohne Befehlszeile, Schlüssel mit Einschränkung auf die Weiterleitung zu Umami.
6. In Coolify auf der **Anwendung** (nicht dem Worker): `APP_UMAMI_WEBSITE_ID`, `APP_UMAMI_UPSTREAM`
   (Adresse und Port des Zähl-Eingangs), `APP_UMAMI_UPSTREAM_PIN`. Ohne Website-Kennung bleibt die
   Messung lautlos aus — ein Deploy vor Schritt 6 ist gefahrlos.

⚠ **`APP_UMAMI_UPSTREAM` verrät den Standort der Überwachung.** Nie ins Repository, in kein Protokoll,
in keine Fehlermeldung — dieselbe Behandlung wie `APP_UPTIME_PUSH_URL`.

## Abdeckung der Akzeptanzkriterien

Abgegangen aus `features/11-nutzungsmessung/spec.md`, AK-01 bis AK-39, in der Reihenfolge der Datei.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Zählskript im Seitenkopf, Weiterleitung, Umami | Nachweis in Umami binnen 5 min |
| AK-02 | Weiterleitung: Herkunft auf Domain gekürzt | Tracker behält Herkunftspfade — nur die Weiterleitung kürzt |
| AK-03 | Tracker-Option „ohne Abfrage" + Weiterleitung entfernt Abfrage | doppelt, Entscheidung 4 |
| AK-04 | Länderdatenbank, `SKIP_LOCATION_HEADERS`, keine Orts-Kopfzeilen weitergereicht | Entscheidung 8; Betriebsschritt 2 |
| AK-05 | Pfade mit Sprachpräfix; Umami-Filter mit festem Präfix bzw. `*` | Trichter-Tabelle |
| AK-06 | Messregel (Verwaltung, Profil) + Weiterleitung weist diese Pfade ab | |
| AK-07 | Messregel (Routenparameter `token`) + Weiterleitung weist Token-Muster ab | Entscheidung 13 |
| AK-08 | Messregel erlaubt Anmeldung, Registrierung, Formulare | |
| AK-09 | Website-Kennung leer außerhalb Produktion; Tracker-Option Domain `endlech.lu`; Weiterleitung prüft `hostname` | dreifach |
| AK-10 | Umami-Bot-Erkennung (`isbot`), `DISABLE_BOT_CHECK` nicht gesetzt; Weiterleitung reicht die Browserkennung durch | Plattformeinstellung |
| AK-11 | Ereignis-Auslöser Filter und Kontaktweg; Suchtrichter in Umami | Trichter-Tabelle |
| AK-12 | Ereigniskatalog `kontaktweg_genutzt` (`art`, `plattform`); Auslöser sendet nie `href` | Entscheidung 3 |
| AK-13 | Auslöser Filterformular sammelt nur feste Schlüssel; `ort` nur als Merker; Katalogprüfung | |
| AK-14 | Umami-Trichter mit `*` mitten im Pfad | `getFunnel.test.ts` |
| AK-15 | Auslöser beim Erscheinen im Erfolgs-Partial mit `liste`; Wartelisten-Trichter je erster Seite | Entscheidung 15 |
| AK-16 | Erfolgs-Partial wird bei Fehlern nicht gerendert | Entscheidung 15 |
| AK-17 | Katalog erlaubt bei `warteliste_eingetragen` nur `liste`; Weiterleitung weist andere Felder ab | |
| AK-18 | Auslöser beim Absenden (noch nicht unterstützt); kein Kontofeld erlaubt | Entscheidung 14, Einschränkung benannt |
| AK-19 | Auslöser beim Klick auf Presse-Kit und Datensatz; `format` im Katalog | |
| AK-20 | Tracker setzt keine Cookies; Schalter nutzt Browserspeicher; Weiterleitung setzt keine | zustandslose Route, keine Sitzung |
| AK-21 | Weiterleitung entfernt `id`; Katalog kennt kein Kontofeld; Messregel unterscheidet nicht nach Anmeldung | |
| AK-22 | Tracker-Option „Do Not Track"; Vor-Versand-Prüfung für GPC; Weiterleitung als Rückhalt | Entscheidung 11 |
| AK-23 | Widerspruchsschalter mit `umami.disabled` | Entscheidung 10 |
| AK-24 | Skript und Zählweg auf endlech.lu; CSP bleibt `'self'` und unverändert | Prüflauf gegen versehentliche Fremddomain in der CSP |
| AK-25 | nur zwei Wege (`/zaehler.js`, `POST /api/send`); keine Durchreichung anderer Pfade | Entscheidung 1 |
| AK-26 | Umami nur auf 127.0.0.1; Zähl-Eingang per Firewall nur für den Anwendungs-VPS | Betriebsschritte 2–3 |
| AK-27 | Limiter `usage_collect` 300/h im `RouteRateLimitSubscriber` | `LimiterCoverageTest` erzwingt Verdrahtung und Test-Override |
| AK-28 | Voreinstellung ersetzt | Betriebsschritt 4; Nachweis in der QA |
| AK-29 | Rollen „view-only" und „team-view-only" | Betriebsschritt 4 |
| AK-30 | Eigener HTTP-Client ohne Protokollierung; Weiterleitung loggt nur Ausnahmeklasse; zustandslose Route ohne `request`-Kanal | Entscheidung 6 |
| AK-31 | Texte in `messages.*.yaml` und `/legal`; `docs/datenschutz.md` | Inhalte aus dieser Datei: Datenmodell-Tabelle, Entscheidung 9 |
| AK-32 | `RoadmapRegistry` ohne `usage_analytics`; `ChangelogRegistry` `SHOWN` | Release-Aufgabe |
| AK-33 | `docs/prd.md` | |
| AK-34 | Gesamtweg; Schalter | Abnahme durch den Betreiber |
| AK-35 | `growth/config.json` mit Website-Kennung, `conversion_events`, `weitere_trichter`; `mcp-umami --check` über den Tunnel | Entscheidung 17 |
| AK-36 | Pfad der Detailseite enthält die Restaurant-Nummer; Sitzung ohne Kontobezug | |
| AK-37 | Skript statisch und `defer`; Zählaufrufe asynchron; Unterbrecher 60 s; eigene Auslöser halten keine Navigation an | Entscheidungen 2, 3, 7 |
| AK-38 | Tunnel-Benutzer ohne Befehlszeile, Schlüssel nur zur Umami-Weiterleitung | Betriebsschritt 5 |
| AK-39 | MCP-Server öffnet den Tunnel bei Bedarf, meldet sonst ab | Entscheidung 16, globaler Skill |

**AK ohne Stelle im Entwurf: keine.** Neun Kriterien (AK-04, AK-10, AK-26, AK-28, AK-29, AK-35, AK-38,
AK-39 und teilweise AK-31) hängen an Betriebsschritten außerhalb des Repositorys; sie brauchen im
Aufgabenplan eigene Aufgaben mit Nachweis, sonst werden sie nie eingestellt.
