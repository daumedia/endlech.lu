# 11 · Nutzung messen, ohne zu verfolgen — Aufgabenplan

Status: `deployed` · Stand: 2026-09-14 (Überarbeitung, live in `v2026.09.14.2`)

> **Zwei Pläne in dieser Datei.** Oben der Plan für die **Überarbeitung vom 2026-09-14** (T32–T44):
> Weiterleitung an die vorhandene Umami-Instanz über ihre eigene Domain (Spec, Decision Log #23–#28;
> Entwurf, Entscheidungen 5, 8, 9, 16, 18, 19). Darunter der **ursprüngliche Plan vom 2026-09-13**
> (T01–T31) mit seinen Abschlussberichten — gebaut, geprüft, als Historie stehen gelassen. Überholte
> Aufgaben sind dort durchgestrichen und nennen ihren Ersatz.

# Überarbeitung vom 2026-09-14 — Umami über seine eigene Domain

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den anderen
`[P]`-Aufgaben, darf parallel an einen Subagenten gehen. **Rot heißt anhalten.**

## Verifikation

Referenzstand unverändert gegenüber dem ursprünglichen Plan (unten, „Verifikation"): `lint:container`
meldet **genau einen** Fehler (WebAuthn-Alias), `doctrine:schema:validate` entfällt (kein Schema),
`php-cs-fixer` gibt es nicht. Zuletzt gemessen am 2026-09-14 auf `main`: **1331 Tests grün**, 10
übersprungen.

```bash
php bin/console lint:yaml config/
php bin/console lint:twig templates/
php bin/console lint:container 2>&1 | grep -c 'Invalid alias definition'   # erwartet: 1
php bin/phpunit tests/Unit/Usage tests/Functional/Usage                    # Ebenen 2–3
php bin/phpunit                                                           # nach Ebene 5 vollständig
```

`npm run typecheck/lint/build` entfallen: Die Überarbeitung ändert nichts unter `assets/`.

## Betrieb — Umami-Instanz auf dem zweiten VPS (Betreiber, außerhalb des Repositorys)

Schon erledigt am 2026-09-14: Umami-Port nur auf `127.0.0.1`, Image fest auf `3.3.1`, Daten erhalten.

⚠ Umami-Domain, Rechnername und Adresse des VPS erscheinen in **keinem** Nachweis, keiner Datei und
keinem Chat. Nachweise tragen nur das Ergebnis.

- [x] **T32** · *Erledigt 2026-09-14: aus dem Anwendungs-Container Heartbeat 200 über IPv4, Zertifikat gültig.* · Proxy-Route der Umami-Domain wieder einschalten: im Katalog-Projekt die Traefik-Labels
      zurück, der Port bleibt `127.0.0.1`, das Image bleibt `3.3.1`. Nachweis ohne Adresse: Die Domain
      antwortet per HTTPS mit **gültigem** Zertifikat und zeigt die Anmeldeseite; die Schnittstelle
      liefert ohne Anmeldung keine Websites; die Adresse des VPS mit dem Umami-Port antwortet nicht ·
      `AK-26` — Grundlage für T34, T42
- [ ] **T33** · Umami-Konten: Voreinstellung `admin`/`umami` ersetzen (eigener Name, starkes Passwort),
      **zweiter Faktor für diesen Benutzer — nicht global, nicht für das Team erzwingen**; Website
      `endlech.lu` anlegen, falls nicht vorhanden, und einem Team zuordnen; Benutzer `growth-loop` mit
      Rolle view-only, im Team team-view-only, ohne zweiten Faktor, langes Zufallspasswort. Nachweis:
      `admin`/`umami` scheitert; Betreiber mit Passwort, aber ohne zweiten Faktor, scheitert; `growth-loop`
      sieht die Website und kann nichts ändern · `AK-28, AK-29, AK-41` — Grundlage für T34
- [ ] **T34** · Zugangsdatei `~/.config/umami/zugang.json`: `url` = Umami-Domain, `benutzer` =
      `growth-loop`, `passwort`; **kein** Tunnel-Block; `chmod 600`. Nachweis: `mcp-umami --check` listet
      die Website mit ihrer Kennung, und in der Ausgabe steht die Domain nicht (nach T41) ·
      `AK-35, AK-42` — nach T32, T33, T41

## Ebene 1 · Fundament — Konfiguration

**Keine eigene Aufgabe.** Kein Schema, keine neue Variable. Das Entfernen von `APP_UMAMI_UPSTREAM_PIN`
hängt am Konstruktor der Weiterleitung — getrennt davon wäre der Container nach dieser Ebene rot (der
Dienst verlangte einen Parameter, den es nicht mehr gibt). Es steht deshalb in T36.

## Ebene 2 · Server — Logik

- [x] **T35** `[P]` · Kürzung in `src/Usage/CollectPayloadNormalizer.php`: ausdrücklich festhalten und
      prüfen, dass vom Client mitgeschickte `ip`, `userAgent`, `timestamp`, `browser`, `os` und `device`
      **nicht** durchkommen (Umami 3.3.1 nimmt diese Felder im Zählaufruf an) und dass `type`
      `performance` abgewiesen wird wie `identify`. Prüflauf in
      `tests/Unit/Usage/CollectPayloadNormalizerTest.php` mit je einem Fall; Gegenprobe: ein
      durchgelassenes `ip` färbt ihn rot · `AK-04, AK-40, AK-21`
- [x] **T36** `[P]` · Weiterleitung `src/Usage/UmamiForwarder.php` auf die Umami-Domain umstellen
      (Entwurf, Entscheidungen 5, 18, 19):
      Besucheradresse als Feld **`ip` im `payload`**, nur wenn sie eine gültige IP ist, sonst ohne Feld;
      Kopfzeile `X-Endlech-Client-Ip` samt Konstante entfällt, die Kopfzeilen-Positivliste ist danach
      `Content-Type`, `User-Agent`, `x-umami-cache`;
      **gewöhnliche Zertifikatsprüfung** — `verify_peer`/`verify_host` nicht mehr abschalten, kein
      `peer_fingerprint`; der Warnzweig „ohne Schlüsselbindung" entfällt, ohne Umami-Domain wird wie
      bisher nichts weitergeleitet;
      `APP_UMAMI_UPSTREAM_PIN` aus `.env` und `.env.test`, Parameter `app.umami_upstream_pin` aus
      `config/services.yaml` entfernen; Kommentare zu `APP_UMAMI_UPSTREAM` („Umami-Domain, `https://…`,
      nie in Repository oder Protokoll") und zum eigenen Client (ohne Logger bleibt Pflicht, der Grund
      „nur cURL versteht pin-sha256" entfällt).
      Unverändert und weiter geprüft: ein Platz, Zeitlimit, Unterbrecher, nur die Ausnahmeklasse im
      Protokoll. Prüfläufe: `tests/Unit/Usage/UmamiForwarderTest.php` (`ip` im Rumpf, ungültige Adresse
      ohne Feld, keine Adress-Kopfzeile, keine abgeschaltete Zertifikatsprüfung, ohne Ziel nichts;
      Pin-Fälle ersetzt), Konstruktor in `tests/Unit/Usage/Qa11SperrAusfallTest.php`. Gegenprobe:
      Adresse wieder als Kopfzeile statt Feld → rot · `AK-04, AK-40, AK-42, AK-37`

**Verifikation Ebene 2:** `lint:yaml`, `lint:container` (genau 1), `php bin/phpunit tests/Unit/Usage`.

## Ebene 3 · Schnittstellen

- [x] **T37** · Zählweg über den Controller (`POST /api/send`, Code unverändert): Der Prüflauf
      `tests/Functional/Usage/CollectControllerTest.php` belegt, dass die Adresse **des Besuchers** als
      `payload.ip` bei Umami ankommt — auch hinter einem vertrauten Proxy (`TRUSTED_PROXIES` im Prüflauf
      gesetzt, Anfrage mit `X-Forwarded-For`), nicht die Adresse des Proxys — und dass keine
      Adress-Kopfzeile mitgeht; `APP_UMAMI_UPSTREAM_PIN` verschwindet aus dem Aufbau des Prüflaufs.
      Gegenprobe ohne `TRUSTED_PROXIES`: dann steht die Proxy-Adresse im Feld, der Fall zeigt es ·
      `AK-04, AK-40`

**Verifikation Ebene 3:** `php bin/phpunit tests/Unit/Usage tests/Functional/Usage`.

## Ebene 4 · Oberfläche

**Keine Aufgabe.** Seitenkopf, Auslöser und Widerspruchsschalter bleiben, wie sie gebaut und geprüft
sind; die Überarbeitung ändert nur, wohin und wie weitergeleitet wird.

## Ebene 5 · Feinschliff und Unterlagen

- [x] **T38** `[P]` · Datenschutztext in `/legal`, vier Sprachen in `translations/messages.*.yaml`
      (`usage_text`): „das Land" → **Land, Region und Stadt**; Sitzungskennung „wechselt täglich" →
      **„wechselt monatlich"** (gezählt im Messabsatz: je eine Stelle für den Ort — „das Land", „the
      country", „le pays", „d’Land" — und je eine für den Wechsel — „täglich", „daily", „chaque jour",
      „all Dag"). Alles andere am Text bleibt, er beschreibt weiterhin nur Nachstellbares.
      Erwartung in `tests/Functional/Usage/OptOutSwitchTest.php` („täglich wechselt") mitziehen ·
      `AK-31`
- [x] **T39** `[P]` · `docs/datenschutz.md`, Eintrag „Nutzungsmessung": Umami über eine eigene Domain
      (ohne sie zu nennen), **Region und Stadt** statt Länderdatenbank, Sitzung **monatlich**, kein
      Tunnel, Zugriff Betreiber mit zweitem Faktor und Growth-Loop nur lesend über die Domain;
      Übertragung per HTTPS mit Zertifikatsprüfung statt Schlüsselbindung; Standort über DNS und
      Zertifikatslogs auffindbar, nur nicht aus endlech.lu ableitbar; Telemetrie-Bild nur im Dashboard;
      Direktaufrufe an die Domain hingenommen (EC-08, OF-08). Standort Deutschland laut Betreiber
      bleibt · `AK-31`
- [x] **T40** `[P]` · `CLAUDE.md`, Abschnitt „Nutzungsmessung": ⚠-Block „**ohne `ip` im Zählaufruf
      ein Land und eine Sitzung für alle**" mit der Messung vom 2026-09-14 (Argentinien, eine Sitzung);
      Schlüsselbindung und eigene IP-Kopfzeile durch Zertifikatsprüfung und Feld `ip` ersetzen; vom
      Client gesetzte `ip`/`userAgent`/… entfernt die Kürzung; `TRUSTED_PROXIES` bestimmt jetzt auch die
      Adresse in Umami; zweiter Faktor nur für den Betreiber-Benutzer, nie global; kein Tunnel mehr ·
      `AK-04, AK-40, AK-41`

**Verifikation Ebene 5:** alle Befehle oben, `php bin/phpunit` vollständig.

## Außerhalb des Repositorys — Skill `growth-loop`

- [x] **T41** · `~/.claude/skills/growth-loop/mcp-umami/server.js`: Die Umami-URL erscheint in **keiner**
      Ausgabe — betroffen heute: Feld `zugang` der Websites-Liste, Zeile „API" in `--check`, die Meldung
      bei 404 und die bei nicht erreichbarem Rechner; `e.message` aus dem Netz nur ausgeben, wenn sie keine
      Adresse enthält. Ohne Tunnel-Block gilt: nicht auflösbar, abgewiesen, Zeitüberschreitung,
      Zertifikatsfehler oder gescheiterte Anmeldung → „**Loop 2 meldet ab**", `--check` mit Exit-Code 3.
      Der Tunnel-Weg bleibt für andere Projekte. `README.md` und `references/einrichtung.md`: Zugang über
      eine eigene Domain, Lese-Benutzer ohne zweiten Faktor, zweiten Faktor nie global erzwingen.
      Nachweis: `--check` gegen eine nicht auflösbare Domain → Exit 3, und `grep` der Ausgabe nach dem
      Domainnamen findet nichts; dasselbe mit falschem Passwort · `AK-39, AK-42`

## Plattform — vor und nach dem Deploy (Betreiber mit Claude)

- [x] **T42** · *Erledigt 2026-09-14: beide Variablen im Container gesetzt (erster Anlauf `APP_UMAMI_UPSTREAM` leer, korrigiert); Weiterleitung auf der Produktion 200.* · Coolify, **Anwendung** (nicht Worker): `APP_UMAMI_WEBSITE_ID` und `APP_UMAMI_UPSTREAM`
      = `https://<Umami-Domain>`; `APP_UMAMI_UPSTREAM_PIN` nicht setzen, eine vorhandene entfernen. Ohne
      Website-Kennung bleibt die Messung aus — ein Deploy vor diesem Schritt ist gefahrlos · `AK-01` —
      Grundlage für T43, T44
- [ ] **T43** · In Umami die vier Trichter als Berichte anlegen, **Schritte aus `growth/config.json`**
      (führender Stern); die Website-Kennung nach `growth/config.json`; `mcp-umami --check` listet die
      Website ohne die Domain auszugeben · `AK-14, AK-35`
- [x] **T44** · *Vom Betreiber bestätigt am 2026-09-14 („T44 ok“): eigener Besuch in Umami mit eigenem Land, Schalter in `/legal` wirkt.* · Abnahme auf der Produktion: eigener Besuch ohne DNT binnen 5 min in Umami, **mit dem
      eigenen Land** (nicht dem Land des Anwendungsservers); zweiter Besuch von einer anderen Adresse mit
      demselben Browser ergibt einen zweiten Besucher; nach Ausschalten in `/legal` kein weiterer Aufruf;
      `/changelog` trägt beim Release, das die Messung scharfschaltet, den Eintrag aus
      `changelog-text.md` (`SHOWN`) · `AK-34, AK-04, AK-40, AK-32`

## Abdeckung

Abgegangen aus `spec.md` (Stand 2026-09-14, AK-01 bis AK-42, EC-01 bis EC-08) und aus beiden Plänen.
Gebaute Aufgaben des ursprünglichen Plans (T06–T28) zählen weiter, soweit die Überarbeitung sie nicht
berührt; überholte (T01–T05, T29–T31) sind ersetzt.

| AK | Aufgaben |
|---|---|
| AK-01 | T13, T17, **T36, T42** |
| AK-02 | T12 |
| AK-03 | T12, T17 |
| AK-04 | T13, **T35, T36, T37, T40, T44** — vorher T02 (Länderdatenbank), überholt |
| AK-05 | T17 |
| AK-40 | **T35, T36, T37, T40, T44** — neu |
| AK-06 | T11, T12, T17 |
| AK-07 | T11, T12, T17 |
| AK-08 | T11, T17 |
| AK-09 | T06, T11, T12, T17 |
| AK-10 | T13 — Vorgabe der Instanz (`DISABLE_BOT_CHECK` nicht gesetzt), in T32 nicht verändert |
| AK-11 | T18, T19 |
| AK-12 | T10, T12, T19 |
| AK-13 | T10, T12, T18, T19 |
| AK-14 | T09, T19, **T43** |
| AK-15 | T18, T20 |
| AK-16 | T20 |
| AK-17 | T10, T12, T20 |
| AK-18 | T18, T21 |
| AK-19 | T10, T21 |
| AK-20 | T14, T22 |
| AK-21 | T10, T12, **T35** |
| AK-22 | T14, T17 |
| AK-23 | T22 |
| AK-24 | T07, T16, T17 — Umami-Domain nur in der Umgebung (T42); vorher T03, überholt |
| AK-25 | T14 |
| AK-26 | **T32** — vorher T02, T03, überholt |
| AK-27 | T15 |
| AK-28 | **T33** |
| AK-41 | **T33** — neu |
| AK-29 | **T33** |
| AK-30 | T13, **T36** |
| AK-42 | **T34, T36, T41** — neu |
| AK-31 | T23, T26, **T38, T39** |
| AK-32 | T24, T27, **T44** |
| AK-33 | T25 |
| AK-34 | **T44** |
| AK-35 | T09, T28, **T34, T43** |
| AK-36 | T12, T17 |
| AK-37 | T07, T08, T13, T17, T18, **T36** |
| AK-38 | — **entfallen** (Spec, Decision Log #26); vorher T05 |
| AK-39 | T28, **T41** |

| EC | Aufgaben |
|---|---|
| EC-01 | T13, T17, T18, **T36** (als AK-37 geprüft) |
| EC-02 | T22, T23 |
| EC-03 | T17, T19 |
| EC-04 | T11 |
| EC-05 | T15 |
| EC-06 | T18 |
| EC-07 | T23 |
| EC-08 | **keine Aufgabe** — hingenommen (Spec, Decision Log #28); in T39 beschrieben, Betriebsfolge OF-08 bei `/sdd-betrieb` |

**AK ohne Aufgabe:** keine (AK-38 entfallen).
**Aufgabe ohne AK:** keine. T32 trägt zusätzlich „Grundlage für T34, T42", T33 „Grundlage für T34",
T42 „Grundlage für T43, T44". Das Entfernen von `APP_UMAMI_UPSTREAM_PIN` ist Teil von T36 und kein
eigenes Kriterium (Entwurf, Entscheidung 19).

## Parallelisierung

| Ebene | Gleichzeitig | Dateien je Aufgabe | Warum gefahrlos |
|---|---|---|---|
| Betrieb | T32, T33 | nur auf dem zweiten VPS bzw. in der Umami-Oberfläche | kein Repository; T34 danach |
| 2 | T35, T36 | T35 `src/Usage/CollectPayloadNormalizer.php`, `tests/Unit/Usage/CollectPayloadNormalizerTest.php` · T36 `src/Usage/UmamiForwarder.php`, `tests/Unit/Usage/UmamiForwarderTest.php`, `tests/Unit/Usage/Qa11SperrAusfallTest.php`, `config/services.yaml`, `.env`, `.env.test` | keine gemeinsame Datei; T36 setzt `ip` nach der Kürzung ein, unabhängig davon, wie T35 sie prüft |
| 3 | — | T37 allein | eine Datei |
| 5 | T38, T39, T40 | T38 `translations/messages.{de,en,fr,lb}.yaml`, `tests/Functional/Usage/OptOutSwitchTest.php` · T39 `docs/datenschutz.md` · T40 `CLAUDE.md` | keine gemeinsame Datei |
| extern | T41 | `~/.claude/skills/growth-loop/mcp-umami/` | außerhalb des Repositorys, jederzeit; vor T34 |

## Vor dem Bauen

- [ ] Branch von `main` anlegen: `git checkout -b feature/11-umami-domain` — Spec, Entwurf und dieser Plan
      sind noch nicht committet und gehen mit
- [ ] Test-DB aufgesetzt: `make test-db-setup` (Test-DB in `mika-database-1`, vorher starten)
- [ ] Referenzstand: `lint:container` genau ein Fehler; 1331 Tests grün
- [ ] **Keine echten Werte lokal nötig.** Der Bau arbeitet gegen `MockHttpClient`; Umami-Domain und
      Website-Kennung setzt nur der Betreiber in Coolify (T42)
- [ ] ⚠ `v2026.09.14.1` liegt mit der alten Fassung auf `master` und wird nicht ausgerollt. Das nächste
      Release enthält sie samt dieser Überarbeitung; `CHANGELOG.md` beschreibt dort den geänderten Weg
- [ ] Vor der QA: T32–T34 und T41 abgeschlossen, sonst sind AK-26, AK-28, AK-29, AK-35, AK-39, AK-41 und
      AK-42 nicht prüfbar


## Abschlussbericht Überarbeitung (`sdd-build`, 2026-09-14)

Branch `feature/11-umami-domain` von `main`, nicht committet. Gebaut: T35–T41. Nicht gebaut, weil beim
Betreiber: T32–T34, T42–T44.

### 1 · Umgesetzt

Die Weiterleitung spricht die Umami-Domain wie einen gewöhnlichen HTTPS-Dienst an und gibt die
Besucheradresse als Feld `ip` im Zählaufruf mit.

- **T35** · `CollectPayloadNormalizer`: Kommentar hält fest, dass `ip`, `userAgent`, `timestamp`, `browser`,
  `os`, `device` vom Client nie übernommen werden (der Code tat es schon nicht); zwei neue Fälle, darunter
  `type: performance`. Gegenprobe (durchgelassenes `ip`) rot.
- **T36** · `UmamiForwarder`: Adresse als `payload.ip` (nur gültige IP, auch IPv6), Kopfzeile
  `X-Endlech-Client-Ip` und Konstante entfernt; gewöhnliche Zertifikatsprüfung, kein `peer_fingerprint`; der
  Warnzweig „ohne Schlüsselbindung" entfällt. `APP_UMAMI_UPSTREAM_PIN` aus `.env`, `.env.test` und
  `config/services.yaml` entfernt, Kommentare angepasst. Zwei Gegenproben rot: Adresse als Kopfzeile;
  `verify_peer: false`.
- **T37** · `CollectControllerTest`: `payload.ip` statt Kopfzeile; neu hinter vertrautem Proxy
  (`private_ranges`, `X-Forwarded-For`) die Adresse des Besuchers, im selben Lauf die Gegenprobe ohne
  `TRUSTED_PROXIES` (Proxy-Adresse); neu untergeschobenes `ip` im Rumpf wird ersetzt. `Request`-Proxys werden
  im `tearDown` zurückgesetzt — in Zufallsreihenfolge mit Nachbartests grün.
- **T38** · `usage_text` in vier Sprachen: Land, Region und Stadt; Sitzungskennung monatlich.
  `OptOutSwitchTest` erwartet die neuen Angaben und prüft, dass „täglich" nicht mehr im Abschnitt steht.
- **T39** · `docs/datenschutz.md`: Eintrag „Nutzungsmessung" neu gefasst (Domain, Region/Stadt, monatlich,
  kein Tunnel, Zertifikatsprüfung, Telemetrie nur im Dashboard, bekannte Grenze EC-08), Übersichtszeile und
  BE-02-Stand nachgezogen.
- **T40** · `CLAUDE.md`: vier ⚠-Blöcke (Feld `ip` mit der Messung, `TRUSTED_PROXIES`, Zertifikatsprüfung,
  2FA nie global), Einleitung und Upstream-Warnung angepasst, Image-Pin 3.3.1 vermerkt.
- **T41** · `~/.claude/skills/growth-loop/`: `mcp-umami` nennt die URL selbst gehostet in keiner Ausgabe
  (Werkzeugantwort, `--check`, jede Fehlermeldung über eine gemeinsame Schwärzung); nicht erreichbar,
  Zertifikat, Zeitlimit, falsches Passwort, 2FA, HTTP 404 oder 5xx → „Loop 2 meldet ab", `--check` Exit 3,
  auch ohne Tunnel. README, `references/einrichtung.md`, `references/cro-loop.md` nachgezogen. Sicherung
  der alten Fassungen im Scratchpad der Sitzung.

**Verifikation:** `lint:yaml` und `lint:twig` grün, `lint:container` genau der eine bekannte Fehler,
`php bin/phpunit` **1336 Tests grün**, 10 übersprungen (vorher 1331; die fünf neuen Fälle aus T35–T37).
Produktions-Cache baut ohne den entfernten Parameter.

**Selbsttest Ende zu Ende** gegen eine Wegwerf-Instanz Umami 3.3.1 **ohne jede Einstellung** (so wie die
Katalog-Instanz): Anwendung im Produktionsmodus, vertrauter Proxy, zwei Zählaufrufe mit demselben Browser
über `X-Forwarded-For` → Umami speichert **LU (Mamer)** und **FR** in **zwei** Sitzungen, Abfrage `?city=Esch`
entfernt; das mitgeschickte `ip: 8.8.8.8` und `cf-ipcountry: JP` blieben wirkungslos. `mcp-umami --check`
gegen dieselbe Instanz: richtiges Passwort Exit 0 mit Website-Liste, falsches Passwort Exit 3, nicht
auflösbare Domain Exit 3, Antwort 404 und 501 je Exit 3 — in keinem Fall die Adresse in der Ausgabe.

### 2 · Offene Akzeptanzkriterien

- **AK-26, AK-28, AK-29, AK-41** — hängen an T32 und T33 (Route, Konten, 2FA) auf dem echten VPS.
- **AK-35** — T34 und T43; lokal nur gegen die Wegwerf-Instanz belegt.
- **AK-01, AK-04, AK-34, AK-40** auf der Produktion — T42 und T44. Lokal belegt (Selbsttest), aber über den
  echten Proxy des Hosters ungeprüft: dass er `User-Agent` und `x-umami-cache` unverändert durchreicht.
- **AK-32** — der `SHOWN`-Eintrag entsteht erst im Release, das die Messung scharfschaltet.

### 3 · Getroffene Annahmen

- **Der Proxy des Hosters (Traefik) reicht `User-Agent` und `x-umami-cache` unverändert weiter** und
  verändert den JSON-Rumpf nicht. Traefiks Vorgabe tut das; die konkrete Route ist ungesehen.
- **`TRUSTED_PROXIES=private_ranges` in Coolify deckt die Adresse ab, von der die Anfrage bei FrankenPHP
  ankommt** — am 2026-09-13 über die `https://`-Weiterleitung nachgeprüft, für die Besucheradresse in Umami
  nicht eigens.
- **Zeitlimit 20 s in `mcp-umami`** je Anfrage — der Plan nennt „Zeitüberschreitung", aber keine Zahl.
  Gewählt so, dass eine langsame Instanz nicht als Ausfall zählt, ein hängender Server den Lauf aber nicht
  minutenlang aufhält.
- **HTTP 404 und jeder 5xx gelten in `mcp-umami` als „nicht erreichbar"** (Route im Proxy aus, Instanz
  kaputt). Die erste Fassung kannte nur 404, 502, 503, 504; die Probe mit einer 501-Antwort zeigte die Lücke.

### 4 · Systemweite Änderungen

- **Globaler Skill `growth-loop`** (außerhalb des Repositorys): betrifft jedes Projekt, das `mcp-umami`
  selbst gehostet nutzt — dort steht die URL nun ebenfalls in keiner Ausgabe, und Verbindungsfehler melden
  ab, statt eine Fehlermeldung mit Adresse zu liefern. Umami Cloud und der Tunnel-Weg sind unverändert; der
  Tunnel-Weg ist nicht erneut gefahren.
- **`APP_UMAMI_UPSTREAM_PIN` existiert nicht mehr.** Steht sie in Coolify, ist sie wirkungslos — T42 entfernt
  sie.
- **Nicht angefasst, obwohl veraltet:** `CHANGELOG.md` beschreibt unter `[2026.09.14.1]` noch die
  Schlüsselbindung — das ist der Text eines Releases, das so auf `master` liegt; das nächste Release
  beschreibt den geänderten Weg. `qa/11/umgebung.md` beschreibt die Prüfumgebung der ersten QA (eigener
  Eingang, Schlüsselbindung) und bleibt als deren Zeugnis stehen.
- ⚠ **Eine eigene Probe war zuerst wertlos:** Die erste Runde `mcp-umami --check` lief über `timeout`, das es
  unter macOS nicht gibt (Exit 127) — „0 Treffer der Adresse" stammte aus leeren Ausgaben. Mit `perl -e alarm`
  wiederholt; die Ergebnisse oben sind die der Wiederholung.

---

# Ursprünglicher Plan vom 2026-09-13 (gebaut, Historie)

Status damals: `building`

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen. Aufgaben **ohne** `[P]`
laufen danach, der Reihe nach.

Nach jeder Ebene läuft die Verifikation unten. **Rot heißt anhalten.**

### Verifikation — und was davon schon vor diesem Feature rot ist

Referenzstand wie bei Feature 10, am 2026-09-13 erneut geprüft:

| Befehl | Stand vor Feature 11 | Im Plan |
|---|---|---|
| `make fix-check` | **gibt es nicht**, `php-cs-fixer` nicht installiert | entfällt; `php -l` auf geänderten PHP-Dateien, Stil an den Nachbardateien |
| `php bin/console lint:container` | ⚠ rot, **genau ein** Fehler (WebAuthn-Alias) | Referenzstand: genau dieser eine. Jeder weitere ist rot |
| `php bin/console lint:twig templates/` | grün | ja |
| `php bin/console lint:yaml config/` | grün | ja |
| `php bin/console doctrine:schema:validate` | ⚠ rot (bekannte Abweichung) | entfällt: Feature 11 ändert kein Schema |
| `npm run typecheck`, `npm run lint`, `npm run build` | grün | **ja, ab Ebene 4** — Feature 11 ändert `assets/` |

**Befehl nach jeder Ebene** (Test-DB aufgesetzt, `make test-db-setup`):

```bash
php bin/console lint:yaml config/
php bin/console lint:twig templates/
php bin/console lint:container 2>&1 | grep -c 'Invalid alias definition'   # erwartet: 1
php bin/phpunit                                                           # Ebene 1–3: die genannten Teilläufe genügen
npm run typecheck && npm run lint && npm run build                        # ab Ebene 4
```

### Betrieb — zweiter VPS (Betreiber, außerhalb des Repositorys)

Diese Aufgaben erzeugen keinen Code. Sie **blockieren den Bau nicht** — die Anwendung wird gegen
einen nachgebildeten Umami gebaut und geprüft —, müssen aber **vor der QA** abgeschlossen sein: Neun
Kriterien lassen sich nur an der echten Instanz belegen. Jede Aufgabe nennt ihren Nachweis.

⚠ Rechnername und Adresse des zweiten VPS erscheinen in **keinem** Nachweis, keiner Datei und keinem
Chat. Nachweise tragen nur das Ergebnis.

- [~] ~~**T01**~~ · **Überholt am 2026-09-14** (Standort steht in T39, laut Betreiber Deutschland). Vorher: Standort des zweiten VPS bestimmen: Land aus der Adresse, nur das Ergebnis notieren.
      Nachweis: „DE" mit Datum, eingetragen in T26 · `AK-31`
- [~] ~~**T02**~~ · **Überholt am 2026-09-14** — die Katalog-Instanz bleibt mit ihren Vorgaben, Ersatz T32. Vorher: Umami **v3.3.x** mit PostgreSQL als Container aufsetzen, Umami lauscht **nur auf
      127.0.0.1**. Umgebung laut `design.md`: `CLIENT_IP_HEADER` = Kopfzeile aus T13,
      `SKIP_LOCATION_HEADERS`, `GEOLITE_DB_PATH` auf eine **Länderdatenbank** (MMDB, Lizenz notieren),
      `SALT_ROTATION=day`, `DISABLE_TELEMETRY`, `DISABLE_UPDATES`, eigenes `APP_SECRET`,
      `DISABLE_BOT_CHECK` **nicht** gesetzt. Nachweis: Versionsangabe der Oberfläche, Liste der
      gesetzten Variablennamen ohne Werte · `AK-04, AK-10, AK-26` — Grundlage für T03, T04
- [~] ~~**T03**~~ · **Überholt am 2026-09-14** — kein eigener Zähl-Eingang, Ersatz T32 und T36. Vorher: Zähl-Eingang: Reverse Proxy auf eigenem Port mit **selbst signiertem** Zertifikat,
      lässt nur `POST /api/send` zu Umami durch; Firewall öffnet den Port **nur** für die Adresse des
      Anwendungs-VPS. Den öffentlichen Schlüssel als `pin-sha256` bestimmen (für T31). Nachweis vom
      Laptop aus: Port nicht erreichbar; vom Anwendungs-VPS aus: `POST /api/send` antwortet,
      `GET /` und `/api/websites` → 404 · `AK-24, AK-26`
- [~] ~~**T04**~~ · **Überholt am 2026-09-14** — Ersatz T33. Vorher: Umami-Konten: Voreinstellung `admin`/`umami` ersetzen (anderer Name, starkes
      Passwort, 2FA); Website `endlech.lu` anlegen und einem Team zuordnen; Benutzer `growth-loop`
      mit Rolle **view-only**, im Team **team-view-only**, ohne 2FA. Nachweis: Anmeldeversuch mit
      `admin`/`umami` scheitert; `growth-loop` sieht die Website, kann keine Einstellung ändern ·
      `AK-28, AK-29, AK-35`
- [~] ~~**T05**~~ · **Überholt am 2026-09-14** — kein Tunnel, AK-38 entfallen. Vorher: Tunnel-Benutzer ohne Befehlszeile; sein Schlüssel ist in `authorized_keys`
      eingeschränkt auf die Weiterleitung zu `127.0.0.1:<Umami-Port>` (keine Shell, kein anderes
      Ziel, keine Agent- und X11-Weiterleitung). Schlüssel nur unter `~/.config/umami/`. Nachweis:
      `ssh` mit Befehl → abgewiesen; Weiterleitung zu einem anderen Port → abgewiesen; Weiterleitung
      zu Umami → Oberfläche erreichbar · `AK-38`

### Ebene 1 · Fundament — Konfiguration und Dateien

- [x] **T06** `[P]` · Umgebung: in `.env` leer `APP_UMAMI_WEBSITE_ID`, `APP_UMAMI_UPSTREAM`,
      `APP_UMAMI_UPSTREAM_PIN`; in `config/services.yaml` die Parameter mit leerem Vorgabewert
      (`env(string:default::…)`-Muster wie `app.uptime_push_url`) und je ein Kommentar: leer heißt
      lautlos aus; `APP_UMAMI_UPSTREAM` nie in Repo oder Protokoll. In `.env.test` eine
      **Platzhalter-Kennung** für die Website, `APP_UMAMI_UPSTREAM` bleibt leer — Grundlage für T11,
      T13, T17 · `AK-09`
- [x] **T07** `[P]` · Zählskript `public/zaehler.js`: Tracker aus dem Container-Image der in T02
      eingesetzten Umami-Version, **inhaltlich unverändert**, mit vorangestellter Kommentarzeile
      (Version, Herkunft, MIT-Lizenz). Am Skript prüfen und im Kommentar festhalten: Zählweg ist
      `/api/send`, der Host-Platzhalter ist leer ersetzt (sonst `data-host-url` in T17). Prüflauf
      `tests/Unit/Usage/TrackerFileTest.php`: Versionszeile vorhanden und gleich der in `CLAUDE.md`
      dokumentierten; das Skript nennt keinen fremden Host; `RouteDirectoryCollisionTest` bleibt grün
      · `AK-24, AK-37`
- [x] **T08** `[P]` · Cache-Pool `cache.usage` in `config/packages/cache.yaml` für den Merker des
      Unterbrechers (Dateisystem; `when@test` Array-Adapter wie die übrigen Pools) — Grundlage für
      T13 · `AK-37`
- [x] **T09** `[P]` · `growth/config.json`: `umami.conversion_events` mit dem Suchtrichter
      (`/*/restaurants`, `filter_angewandt`, `/*/restaurants/*`, `kontaktweg_genutzt`), neuer
      Schlüssel `weitere_trichter` mit den drei Wartelisten-Ketten laut `design.md`;
      `website_id` bleibt `null` bis T29 · `AK-14, AK-35`
      ⚠ **Berichtigt am 2026-09-13 (BF-150):** Die Schrittfolge oben ist die ursprüngliche und zählt in
      Umami 3.3.1 null — ein Stern wirkt nur am Anfang oder Ende. `growth/config.json` trägt jetzt
      `*/restaurants`, `*/restaurants/*`, `*/app`, `*/partner`, `*/organisationen*`; die Tabelle in
      `design.md` steht bis zur Entscheidung über OF-07 noch in der alten Form.

**Verifikation Ebene 1:** `lint:yaml`, `lint:container` (1), `php bin/phpunit tests/Unit/Usage
tests/Functional/RouteDirectoryCollisionTest.php`.

### Ebene 2 · Server — Logik

- [x] **T10** `[P]` · Ereigniskatalog in `src/Usage/`: die sechs Ereignisnamen, je Ereignis die
      erlaubten Datenfelder und Werte aus `design.md` (Filterschlüssel, `kueche`/`ort` nur als Merker,
      Kontaktweg-Arten, Plattformen aus `OrderingPlatform`, Wartelisten, Datensatzformate). Prüflauf
      `tests/Unit/Usage/UsageEventCatalogueTest.php`: jeder Filterschlüssel kommt als Feldname im
      Filterformular von `templates/restaurant/index.html.twig` vor (gegen die Datei geprüft, nicht
      abgeschrieben); jede Plattform aus dem Enum ist erlaubt; kein Feld heißt `email`, `name`,
      `phone`, `user`, `id`, `city` · `AK-12, AK-13, AK-17, AK-19, AK-21`
- [x] **T11** `[P]` · Messregel in `src/Usage/` plus Twig-Funktion in `src/Twig/`: „messen?" ist nein
      bei leerer Website-Kennung, bei Routen `admin_*`, bei Pfaden `/{Sprache}/profile…` und bei jeder
      Route mit Parameter `token`; sonst ja. Prüflauf `tests/Integration/Twig/UsageExtensionTest.php`
      mit **allen** Routen aus dem Router, die `token` tragen (nicht abgeschrieben), plus Anmeldung,
      Registrierung, Vorschlagsformular (ja), Verwaltung und Profil (nein), leere Kennung (nein) ·
      `AK-06, AK-07, AK-08, AK-09`
- [x] **T12** · Prüfen und kürzen (reiner Dienst in `src/Usage/`, nach T10): `type` nur `event`;
      `website` gleich Kennung; `hostname` exakt `endlech.lu`; `url` nur Pfad, Pfade unter
      Verwaltung/Profil und mit 64-Hex-Token abgewiesen; `referrer` fremd → `https://<domain>/`,
      eigen → entfällt; `title` ≤ 200; `name`/`data` nur aus dem Katalog; `id`, `tag` entfernt;
      `screen`/`language` formatgeprüft. Prüflauf `tests/Unit/Usage/CollectPayloadNormalizerTest.php`
      als Datentabelle, **je Regel ein Gutfall und ein Verstoß**, darunter: `?city=Esch` verschwindet
      samt Wert; `www.endlech.lu` abgewiesen; `/de/verify/<64 hex>` abgewiesen; `data.email` abgewiesen
      · `AK-02, AK-03, AK-06, AK-07, AK-09, AK-12, AK-13, AK-17, AK-21, AK-36`
- [x] **T13** · Weiterleitungsdienst in `src/Usage/` (nach T06, T08): eigener HTTP-Client **ohne**
      Logger, cURL-Implementierung, Zeitlimit 2 s, Gesamtdauer 3 s, `verify_peer`/`verify_host` aus,
      `peer_fingerprint` mit `pin-sha256` aus `APP_UMAMI_UPSTREAM_PIN`. Kopfzeilen an Umami **nur**
      `Content-Type`, `User-Agent` (≤ 512), `x-umami-cache` (≤ 2 KB) und die eigene Kopfzeile mit der
      Besucher-IP (Name als Konstante, gleich `CLIENT_IP_HEADER` aus T02). Fehlschlag → 60 s
      Unterbrecher im Pool aus T08, Protokoll nur mit Ausnahmeklasse. Leeres `APP_UMAMI_UPSTREAM` →
      nichts senden. Prüflauf `tests/Unit/Usage/UmamiForwarderTest.php` mit `MockHttpClient`: gesendete
      Kopfzeilen **exakt** diese (kein `Cookie`, `Accept-Language`, `X-Forwarded-For`, `cf-*`);
      Unterbrecher greift nach einem Fehlschlag und löst nach Ablauf; das Protokoll enthält weder
      Upstream-Adresse noch IP (Gegenprobe: mit `getMessage()` wird der Lauf rot); ⚠ der erzeugte
      Client ist der cURL-Client, sonst wäre `pin-sha256` wirkungslos ·
      `AK-01, AK-04, AK-10, AK-30, AK-37`

**Verifikation Ebene 2:** alle YAML/Twig/Container-Befehle, `php bin/phpunit tests/Unit/Usage
tests/Integration/Twig`.

### Ebene 3 · Schnittstellen

- [x] **T14** `[P]` · `POST /api/send`: Controller in `src/Controller/Usage/`, `stateless: true`;
      eigener Routenblock `usage` in `config/routes.yaml` samt Eintrag in der `exclude`-Liste; Firewall
      `usage` (`^/api/send$`, `security: false`) und `PUBLIC_ACCESS`-Zeile in
      `config/packages/security.yaml` vor den Web-Regeln. Ablauf: `DNT: 1` oder `Sec-GPC: 1` → 204
      ohne Weiterleitung; Rumpf > 8 KB → 413; Prüfverstoß → 400; sonst weiterleiten, Umamis Antwort
      durchreichen, bei Unterbrecher/Fehlschlag 202 `{}`. Prüflauf
      `tests/Functional/Usage/CollectControllerTest.php` (Weiterleitungsdienst mit `MockHttpClient` im
      Testcontainer): kein `Set-Cookie` auf keiner Antwort; `GET /api/send` → 405; `/api/send/login`,
      `/api/websites`, `/api/login` → 404; DNT und GPC → 204 und **null** Weiterleitungen; fremde
      Website-Kennung → 400 · `AK-20, AK-22, AK-25`
- [x] **T15** `[P]` · Deckel `usage_collect`: gleitendes Fenster **300 je Stunde**, in
      `config/packages/framework.yaml` samt `when@test`-Override 10000, verdrahtet als Zweig `/api/send`
      im `RouteRateLimitSubscriber` (Schlüssel: Client-IP). ⚠ Konfiguration und Verdrahtung in
      **einer** Aufgabe — sonst ist `LimiterCoverageTest` zwischendurch rot. Prüflauf in
      `tests/Unit/EventSubscriber/RouteRateLimitSubscriberTest.php`: der 301. Aufruf wirft 429, der
      Datensatz-Deckel bleibt unberührt · `AK-27`
- [x] **T16** `[P]` · Rückhalt für die CSP: `tests/Unit/EventSubscriber/SecurityHeadersSubscriberTest.php`
      prüft, dass `script-src` und `connect-src` weiterhin **nur** `'self'` enthalten — die Richtlinie
      selbst bleibt unverändert · `AK-24`

**Verifikation Ebene 3:** alle Befehle, `php bin/phpunit tests/Unit tests/Functional/Usage`.

### Ebene 4 · Oberfläche

Die Messung hat keinen eigenen Bildschirm. Die vier Zustände betreffen den Schalter (T22) und das
Verhalten jeder Seite bei fehlender oder ausgefallener Messung (T17, T18) — sie stehen dort.

- [x] **T17** · Seitenkopf: in `templates/base.html.twig` das Zählskript (`defer`, **nach** den
      Encore-Tags) mit Website-Kennung, Domain `endlech.lu`, ohne Abfrage und Anker, „Do Not Track"
      beachten und Vor-Versand-Prüfung — nur wenn die Messregel aus T11 „ja" sagt. Vor-Versand-Prüfung
      als globale Funktion im App-Bundle (`assets/`): verwirft bei `navigator.globalPrivacyControl`.
      Prüflauf `tests/Functional/Usage/TrackerTagTest.php`: Skript auf `/de/restaurants`, `/de/login`;
      **nicht** auf `/de/admin`, `/de/profile`, einer Token-Seite; kein fremder Host im Dokument ·
      `AK-01, AK-03, AK-05, AK-06, AK-07, AK-08, AK-09, AK-22, AK-24, AK-36, AK-37`
- [x] **T18** `[P]` · Ereignis-Auslöser `assets/controllers/usage_event_controller.ts`: Modi „beim
      Erscheinen", „beim Klick", „beim Absenden"; ruft `umami.track` **ohne zu warten** und ohne
      Navigation anzuhalten; tut ohne `window.umami` nichts und wirft nie. Für das Filterformular:
      sammelt nur Filterschlüssel mit gesetztem Wert, `ort`/`kueche` nur als Merker, **nie** den
      Ortstext. `npm run typecheck` und `npm run lint` grün · `AK-11, AK-13, AK-15, AK-18, AK-37`
- [x] **T19** `[P]` · Suche verdrahten: Filterformular in `templates/restaurant/index.html.twig`
      („beim Absenden", `filter_angewandt`) und alle Kontaktwege samt Bestellwegen in
      `templates/restaurant/show.html.twig` („beim Klick", `kontaktweg_genutzt` mit `art` und ggf.
      `plattform`). Prüflauf `tests/Functional/Usage/SuchtrichterHooksTest.php`: jeder Kontaktweg trägt
      den Auslöser; **kein** Datenattribut enthält eine Telefonnummer, E-Mail-Adresse oder URL ·
      `AK-11, AK-12, AK-13, AK-14`
- [x] **T20** `[P]` · Wartelisten verdrahten: `templates/partials/_waitlist_success.html.twig` („beim
      Erscheinen", `warteliste_eingetragen`, neuer Parameter `liste`), dazu die drei Aufrufer
      `templates/partner/_success.html.twig`, `templates/organisation/_success.html.twig`,
      `templates/app_waitlist/success.stream.html.twig`. Prüflauf
      `tests/Functional/Usage/WartelistenHooksTest.php`: erfolgreiche Anmeldung (Turbo-Stream) enthält
      den Auslöser mit richtiger `liste`; eine fehlerhafte Absendung (422) enthält ihn **nicht** ·
      `AK-15, AK-16, AK-17`
- [x] **T21** `[P]` · Engagement verdrahten: Zustimm-Formular in
      `templates/partials/_board_idea_card.html.twig` („beim Absenden", `zustimmung_gegeben`, **nur
      wenn `hasVoted` falsch**), Presse-Kit-Link in `templates/press/_material.html.twig`,
      Datensatz-Links in `templates/open/index.html.twig` (`format`). Prüflauf
      `tests/Functional/Usage/EngagementHooksTest.php`: Auslöser am nicht unterstützten Formular, nicht
      am unterstützten; Presse-Kit und beide Formate · `AK-18, AK-19`
- [x] **T22** · Widerspruchsschalter in `/legal`: Stimulus-Controller
      `assets/controllers/usage_opt_out_controller.ts` mit `umami.disabled` im Browserspeicher, Knopf
      mit `aria-pressed`; im Abschnitt `#datenschutz` von `templates/impressum/index.html.twig`, ohne
      JavaScript verborgen mit Hinweis; bei gesperrtem Browserspeicher „nicht verfügbar"; Schaltertexte
      in `translations/messages.{de,en,fr,lb}.yaml`. **Kein Cookie.** Prüflauf
      `tests/Functional/Usage/OptOutSwitchTest.php`: Schalter-Markup mit `hidden` und `aria-pressed`,
      `CatalogueCompletenessTest` grün · `AK-20, AK-23`

**Verifikation Ebene 4:** alle Befehle, `php bin/phpunit` vollständig, `npm run typecheck`,
`npm run lint`, `npm run build` — `public/build` mitcommitten (Konvention in `CLAUDE.md`).

### Ebene 5 · Feinschliff und Unterlagen

- [x] **T23** · Datenschutztext in `/legal`, vier Sprachen in `translations/messages.*.yaml`:
      Dienst (Umami, selbst betrieben), Anbieter und Land des Servers (aus T01), was erfasst wird,
      **die Sitzungskennung offen und „wechselt täglich"**, was nicht (Cookies, gespeicherte
      IP-Adresse, Konto, Formularinhalte), unbegrenzte Aufbewahrung mit Begründung, DNT/GPC, Schalter,
      ohne JavaScript keine Messung, geleerter Browserspeicher hebt den Widerspruch auf. **Weder
      „anonym" noch „keine Kennung".** `CatalogueCompletenessTest` grün · `AK-31` · `EC-02, EC-07`
- [x] **T24** `[P]` · Roadmap: Eintrag `usage_analytics` aus `src/Roadmap/RoadmapRegistry.php` und
      den vier `translations/roadmap.*.yaml` entfernen; `RoadmapCatalogueTest` grün · `AK-32`
- [x] **T25** `[P]` · `docs/prd.md`: „Es gibt kein Web-Analytics" und die Zeile „Web-Analytics" unter
      „Bewusst nicht gebaut" durch die cookielose Messung mit ihrer Bedingung ersetzen; Besucherzahlen
      bleiben **keine** Zielkennzahl · `AK-33`
- [x] **T26** `[P]` · `docs/datenschutz.md`: neuer Verarbeitungseintrag „Nutzungsmessung (Umami,
      selbst betrieben)" mit Datenmodell-Tabelle aus `design.md`, Land aus T01 **ohne** Adresse; BE-02
      als erledigt; `CLAUDE.md`: Abschnitt Feature 11 (Datei ersetzen bei Umami-Update samt Version,
      kein `public/api`, eigener Client ohne Logger, `APP_UMAMI_UPSTREAM` nie loggen, Katalog als
      einzige Quelle) und Eintrag in der Routentabelle · `AK-31`
- [x] **T27** `[P]` · Changelog-Text für die Auslieferung in vier Sprachen vorbereiten, in derselben
      vorsichtigen Formulierung wie T23, mit Verweis auf `/legal`: als Entwurf in
      `features/11-nutzungsmessung/changelog-text.md`. Eintrag in `ChangelogRegistry` (`SHOWN`) und
      `translations/changelog.*.yaml` erst im Release-Commit, weil die Version erst dort feststeht ·
      `AK-32`

**Verifikation Ebene 5:** alle Befehle, `php bin/phpunit` vollständig.

### Außerhalb des Repositorys — Skill `growth-loop`

- [x] **T28** · `~/.claude/skills/growth-loop/mcp-umami/`: Zugangsdatei kennt einen Tunnel-Block
      (Ziel, Benutzer, Schlüsselpfad); der Server öffnet den Tunnel beim ersten Aufruf und schließt
      ihn beim Beenden; ist er nicht herstellbar, antwortet jedes Werkzeug mit „Loop 2 meldet ab" statt
      eines Absturzes. `README.md`, `references/einrichtung.md` und `references/cro-loop.md` ziehen
      mit, `weitere_trichter` beschrieben. Nachweis: `--check` mit geschlossenem Tunnel öffnet ihn
      selbst; mit gesperrtem Port „meldet ab" · `AK-39, AK-35`

### Plattform — nach dem Deploy (Betreiber mit Claude)

- [~] ~~**T29**~~ · **Überholt am 2026-09-14** — Ersatz T43. Vorher: In Umami die vier Trichter aus `design.md` als Berichte anlegen; die Website-Kennung
      nach `growth/config.json`; `mcp-umami --check` über den Tunnel listet die Website · `AK-14, AK-35`
      ⚠ **Schritte aus `growth/config.json` nehmen, nicht aus `design.md`** (BF-150, OF-07): Die Tabelle im
      Entwurf schreibt `/*/restaurants` und zählt in Umami null.
- [~] ~~**T30**~~ · **Überholt am 2026-09-14** — Ersatz T33 und T44. Vorher: Abnahme: eigener Besuch ohne DNT binnen 5 min in Umami; nach Ausschalten in `/legal`
      kein weiterer; `admin`/`umami` scheitert; `growth-loop` kann nichts ändern · `AK-28, AK-29, AK-34`
- [~] ~~**T31**~~ · **Überholt am 2026-09-14** — Ersatz T42 (ohne Schlüsselbindung). Vorher: Vor dem Deploy in Coolify auf der **Anwendung** (nicht dem Worker):
      `APP_UMAMI_WEBSITE_ID`, `APP_UMAMI_UPSTREAM`, `APP_UMAMI_UPSTREAM_PIN` — gesetzt vom Betreiber.
      Ohne Kennung bleibt die Messung aus; ein Deploy vor diesem Schritt ist gefahrlos — Grundlage für
      T29, T30 · `AK-01`

### Abdeckung

Abgegangen aus `spec.md` (AK-01 bis AK-39, EC-01 bis EC-07) und aus diesem Plan (T01 bis T31).

| AK | Aufgaben |
|---|---|
| AK-01 | T13, T17, T31 |
| AK-02 | T12 |
| AK-03 | T12, T17 |
| AK-04 | T02, T13 |
| AK-05 | T17 |
| AK-06 | T11, T12, T17 |
| AK-07 | T11, T12, T17 |
| AK-08 | T11, T17 |
| AK-09 | T06, T11, T12, T17 |
| AK-10 | T02, T13 |
| AK-11 | T18, T19 |
| AK-12 | T10, T12, T19 |
| AK-13 | T10, T12, T18, T19 |
| AK-14 | T09, T19, T29 |
| AK-15 | T18, T20 |
| AK-16 | T20 |
| AK-17 | T10, T12, T20 |
| AK-18 | T18, T21 |
| AK-19 | T10, T21 |
| AK-20 | T14, T22 |
| AK-21 | T10, T12 |
| AK-22 | T14, T17 |
| AK-23 | T22 |
| AK-24 | T03, T07, T16, T17 |
| AK-25 | T14 |
| AK-26 | T02, T03 |
| AK-27 | T15 |
| AK-28 | T04, T30 |
| AK-29 | T04, T30 |
| AK-30 | T13 |
| AK-31 | T01, T23, T26 |
| AK-32 | T24, T27 |
| AK-33 | T25 |
| AK-34 | T30 |
| AK-35 | T04, T09, T28, T29 |
| AK-36 | T12, T17 |
| AK-37 | T07, T08, T13, T17, T18 |
| AK-38 | T05 |
| AK-39 | T28 |

| EC | Aufgaben |
|---|---|
| EC-01 | T13, T17, T18 (als AK-37 geprüft) |
| EC-02 | T22, T23 |
| EC-03 | T17, T19 — kein eigener Code: zwei Seitenaufrufe entstehen von selbst, der Trichter läuft über `*` |
| EC-04 | T11 — die 404-Seite erweitert `base.html.twig` nicht; nichts zu bauen, im Prüflauf von T11 als Fall enthalten |
| EC-05 | T15 — der Deckel zählt je Adresse; hingenommenes Verhalten |
| EC-06 | T18 — der Auslöser wirft ohne Netz nichts; der Tracker verschluckt Fehlschläge selbst |
| EC-07 | T23 |

**AK ohne Aufgabe:** keine.
**Aufgabe ohne AK:** keine. T08 trägt „Grundlage für T13" und zusätzlich AK-37; T06 trägt „Grundlage
für …" und AK-09; T31 trägt „Grundlage für T29, T30" und AK-01.

### Parallelisierung

| Ebene | Gleichzeitig | Dateien je Aufgabe | Warum gefahrlos |
|---|---|---|---|
| Betrieb | T01–T05 | nur auf dem zweiten VPS und unter `~/.config/umami/` | kein Repository; unabhängig vom Bau |
| 1 | T06, T07, T08, T09 | T06 `.env`, `.env.test`, `config/services.yaml` · T07 `public/zaehler.js`, `tests/Unit/Usage/TrackerFileTest.php` · T08 `config/packages/cache.yaml` · T09 `growth/config.json` | vier verschiedene Dateien |
| 2 | T10, T11 | T10 Katalog in `src/Usage/`, `tests/Unit/Usage/UsageEventCatalogueTest.php` · T11 Messregel in `src/Usage/`, Twig-Funktion in `src/Twig/`, `tests/Integration/Twig/UsageExtensionTest.php` | verschiedene Dateien; Autowiring, keine Registrierung |
| 2 | — | T12 nach T10, dann T13 | T12 liest den Katalog; T13 braucht T06 und T08 |
| 3 | T14, T15, T16 | T14 `src/Controller/Usage/`, `config/routes.yaml`, `config/packages/security.yaml`, `tests/Functional/Usage/CollectControllerTest.php` · T15 `config/packages/framework.yaml`, `src/EventSubscriber/RouteRateLimitSubscriber.php`, `tests/Unit/EventSubscriber/RouteRateLimitSubscriberTest.php` · T16 `tests/Unit/EventSubscriber/SecurityHeadersSubscriberTest.php` | keine gemeinsame Datei |
| 4 | — | T17 **zuerst** | legt fest, dass und wie das Skript auf den Seiten liegt; T19–T21 prüfen darauf aufbauend |
| 4 | T18, T19, T20, T21 | T18 `assets/controllers/usage_event_controller.ts` · T19 `templates/restaurant/index.html.twig`, `templates/restaurant/show.html.twig`, eigener Prüflauf · T20 `_waitlist_success`-Partial und seine drei Aufrufer, eigener Prüflauf · T21 `_board_idea_card`, `press/_material`, `open/index`, eigener Prüflauf | keine gemeinsame Datei; T19–T21 setzen nur Attribute mit dem Controller-Namen aus T18 |
| 4 | — | T22 danach | schreibt in `translations/messages.*.yaml` — dieselben Dateien wie T23 |
| 5 | T24, T25, T26, T27 | T24 `RoadmapRegistry.php`, `roadmap.*.yaml` · T25 `docs/prd.md` · T26 `docs/datenschutz.md`, `CLAUDE.md` · T27 `features/11-nutzungsmessung/changelog-text.md` | keine gemeinsame Datei |
| 5 | — | T23 zuerst oder danach, nicht gleichzeitig mit T22 | `messages.*.yaml` |
| extern | T28 | `~/.claude/skills/growth-loop/` | außerhalb des Repositorys, jederzeit |

### Vor dem Bauen

- [ ] Feature-Branch: `git checkout -b feature/11-nutzungsmessung` — `spec.md`, `design.md`,
      `tasks.md`, `growth/` und die Zeilen in `features/index.md` sind noch nicht committet und gehen mit
- [ ] Test-DB aufgesetzt: `make test-db-setup` (auf diesem Rechner läuft die Test-DB in
      `mika-database-1`, Port 3306 — vorher starten)
- [ ] Referenzstand festgehalten: `lint:container` meldet genau einen Fehler, `schema:validate` rot —
      beides vor Feature 11 so
- [ ] Für T07: das Container-Image der Umami-Version aus T02 ist lokal ladbar (die Datei wird daraus
      entnommen, nicht von Hand geschrieben)
- [ ] **Keine echten Schlüssel lokal nötig.** `APP_UMAMI_UPSTREAM` und `APP_UMAMI_UPSTREAM_PIN` setzt
      nur der Betreiber in Coolify (T31); der Bau arbeitet gegen `MockHttpClient`
- [ ] Vor der QA: T01–T05 abgeschlossen, sonst sind AK-04, AK-10, AK-24, AK-26, AK-28, AK-29, AK-35,
      AK-38 und AK-39 nicht prüfbar

### Abschlussbericht (`sdd-build`, 2026-09-13)

#### 1 · Umgesetzt

Die Nutzungsmessung ist in der Anwendung vollständig gebaut. Jede Seite außer Verwaltung, Profil und
Token-Seiten lädt `/zaehler.js` (Umami-Tracker 3.3.1, aus dem Container-Image entnommen) mit Domain-,
Abfrage-, Anker-, DNT- und Vor-Versand-Schutz. Zählaufrufe gehen an `POST /api/send`; dort prüft
`CollectPayloadNormalizer` jede Inhaltsregel und den Ereigniskatalog, `UmamiForwarder` reicht nur das
Gekürzte weiter — über einen eigenen cURL-Client ohne Logger, mit Schlüsselbindung, 2 s Zeitlimit und
60-s-Unterbrecher. Deckel 300 je Stunde. Auslöser für Suchtrichter, Wartelisten, Zustimmung,
Presse-Kit und Datensatz; Widerspruchsschalter und Datenschutztext in `/legal` in vier Sprachen;
Roadmap-Eintrag entfernt; PRD, `docs/datenschutz.md`, `CLAUDE.md` und der Skill `growth-loop`
(Tunnel bei Bedarf) nachgezogen.

**Erledigt: 23 von 31 Aufgaben** (T06–T28). **Offen: T01–T05, T29–T31** — Betriebsschritte des
Betreibers auf dem zweiten VPS und rund um den Deploy; sie blockieren den Bau nicht, die QA schon.

Testsuite: **1317 Tests grün**, 10 übersprungen (wie vor dem Bau). Neu: 120 Tests in
`tests/Unit/Usage/`, `tests/Integration/Twig/UsageExtensionTest.php`, `tests/Functional/Usage/`, dazu
Erweiterungen in `RouteRateLimitSubscriberTest` und `SecurityHeadersSubscriberTest`.

**Gegenproben gefahren** (jeweils Code kurz geändert, Lauf rot, byte-gleich wiederhergestellt):
`getMessage()` statt Ausnahmeklasse im Protokoll → `UmamiForwarderTest` rot; `halal` aus dem Katalog →
Abgleich mit dem Filterformular rot; Token-Regel entfernt → beide Token-Prüfläufe rot; andere
Stimulus-Anbindung auf `/presse` → `PressEdgeCaseTest` weiter rot.

**Selbsttest am laufenden Server** (Produktionsmodus gegen `endlech_test`): Skript auf der
Restaurantliste, nicht auf der Token-Seite; `/zaehler.js` vom Webserver byte-gleich; `/api/send` → 202
ohne Zähl-Eingang, 204 mit DNT, 400 bei Verstoß, kein `Set-Cookie`; erster 429 beim **301.** Aufruf;
im Anwendungsprotokoll 0 Einträge mit Umami-Bezug oder IP. Tunnel des Skills: meldet bei nicht
herstellbarem Tunnel ab (Exit 3, kein Hostname in der Ausgabe, kein ssh-Prozess übrig), nutzt einen
schon offenen Port.

#### 2 · Offene Akzeptanzkriterien

- **An der echten Instanz, nach T01–T05:** AK-04 (nur Land), AK-10 (Bots), AK-24 (Eingang nur für den
  Anwendungs-VPS), AK-26 (keine Oberfläche von außen), AK-28, AK-29 (Konten), AK-35 (Website-Kennung,
  `--check`), AK-38 (Tunnel-Schlüssel), AK-39 (Tunnelaufbau gegen einen echten Server — lokal nur die
  beiden Fehlerwege geprüft). Grund: Die Instanz existiert noch nicht.
- **Nur im Browser prüfbar, nicht gefahren:** das Verhalten des Trackers bei Turbo-Navigation (AK-06,
  AK-07 auf dem Weg nach `/de/admin`), der Schalter beim Umschalten (AK-23), das Auslösen der Ereignisse
  (AK-11, AK-15, AK-18, AK-19), GPC (AK-22), die CSP-Konsole (AK-24), die Verzögerung bei angehaltener
  Messung (AK-37). Mit PHPUnit ist nur das Markup prüfbar, auf dem der JavaScript-Code arbeitet; ein
  Browser-Werkzeug steht in diesem Projekt nicht zur Verfügung.
- **AK-01, AK-34** (eigener Besuch in Umami) — erst nach dem Deploy.
- **AK-15 nur näherungsweise** — Dubletten und Honeypot-Treffer zählen mit, weil sie absichtlich dieselbe
  Erfolgsmeldung sehen. Als **OF-05** in der Spec.

#### 3 · Getroffene Annahmen

- **Vor-Versand-Prüfung auch mit Pfadregeln, nicht nur GPC.** Der Entwurf sah dort nur GPC vor. Turbo
  Drive lässt einen geladenen Tracker beim Navigieren weiterlaufen, und Turbo ruft `pushState` vor dem
  Rendern — AK-06/AK-07 wären im Browser sonst verletzt. Dieselben Regeln wie in der Weiterleitung.
- **`TrackerFileTest` vergleicht die Version mit dem Parameter `app.umami_tracker_version`**, nicht mit
  `CLAUDE.md` wie im Plan. Der Parameter existiert, weil das Skript ihn als `?v=` trägt (neue Fassung
  erreicht den Browser); `CLAUDE.md` nennt den Parameter statt einer zweiten Versionsangabe.
- **Weiterleitung ohne Schlüsselbindung sendet nichts** (Warnung im Protokoll). Nicht im Plan; ohne Pin
  ginge die Besucheradresse über eine ungeprüfte TLS-Verbindung.
- **Antwort bei Nicht-Weiterleitung einheitlich 202 `{}`** — ob Zähl-Eingang fehlt, Unterbrecher greift
  oder Umami ausfällt, ist von außen nicht unterscheidbar.
- **Zustandswort am Schalter mit `aria-hidden`**, den Zustand meldet `aria-pressed`; der Name eines
  Umschaltknopfs soll sich nicht ändern.
- **Rechtsgrundlage im `/legal`-Text** nach dem Muster der Sentry- und Hostinger-Abschnitte gesetzt
  (lit. f) — als **OF-06** zur Prüfung in `/sdd-betrieb`.
- **Satz in `legal.retention_text` ergänzt** (Messdaten ohne zeitliche Grenze) — sonst widerspräche der
  Abschnitt „Aufbewahrung" dem neuen Absatz.
- **T22 und T23 zusammen gebaut** — beide schreiben in dieselben vier Übersetzungsdateien, und der
  Katalogtest verlangt jeden verwendeten Schlüssel.

#### 4 · Systemweite Änderungen

- **`templates/base.html.twig`** — Zählskript im Seitenkopf aller Seiten mit Messregel.
- **`assets/app.ts`** — importiert die Vor-Versand-Prüfung (globale Funktion
  `window.endlechNutzungVorVersand`); `public/build` neu gebaut und mitzucommitten.
- **`config/routes.yaml`** — sechster sprachfreier Block `usage`, Eintrag in der `exclude`-Liste.
- **`config/packages/security.yaml`** — Firewall `usage` und `PUBLIC_ACCESS`-Zeile für `^/api/send$`.
- **`config/packages/framework.yaml`** — Limiter `usage_collect` samt Test-Override (jetzt 23).
- **`config/packages/cache.yaml`** — Pool `cache.usage`.
- **`config/services.yaml`** — vier Parameter, Dienst `app.usage.umami_client` (cURL, ohne
  Autokonfiguration) und der erste `when@test`-Block dieser Datei (MockHttpClient).
- **`.env`, `.env.test`** — drei Variablen; `.env.test` mit Platzhalter-Kennung.
- **`src/EventSubscriber/RouteRateLimitSubscriber.php`** — neuer Konstruktor-Parameter und Zweig
  `/api/send`.
- **Vorlagen außerhalb des Features:** `restaurant/index`, `restaurant/show`,
  `partials/_waitlist_success` (neuer optionaler Parameter `messliste`) samt drei Aufrufern,
  `partials/_board_idea_card`, `press/_material`, `open/index`, `impressum/index`.
- **Übersetzungen** — `messages.*` (Abschnitt Nutzungsmessung, Schalter, Satz in `retention_text`),
  `roadmap.*` (Eintrag `usage_analytics` entfernt).
- **`src/Roadmap/RoadmapRegistry.php`** — Eintrag `usage_analytics` entfernt.
- **Ein bestehender Prüflauf gelockert:** `PressEdgeCaseTest::testDieSeiteBrauchtKeinJavaScript` nimmt
  genau den Messauslöser am Presse-Kit aus; Gegenprobe gefahren.
- **`docs/prd.md`** — „Es gibt kein Web-Analytics" ersetzt; „Bewusst nicht gebaut" jetzt
  „Besucherverfolgung" und „Besucherzahlen als Zielkennzahl".
- **`docs/datenschutz.md`** — neuer Abschnitt „Nutzungsmessung", BE-02 aktualisiert, Limiter-Zahl.
- **`CLAUDE.md`** — Abschnitt Feature 11, Routentabelle, Key-Files.
- **Außerhalb des Repositorys:** `~/.claude/skills/growth-loop/mcp-umami/server.js` (Tunnel bei Bedarf),
  `mcp-umami/README.md` (Tunnel-Block; Platzhalter in Trichterschritten an beliebiger Stelle — vorher
  hieß es „am Anfang oder Ende"), `references/einrichtung.md`, `references/cro-loop.md`
  (`weitere_trichter`, Abmelden bei Tunnelfehler). Sicherung der vorherigen Fassungen im Scratchpad
  dieser Sitzung.

#### Nebenbefunde, nicht Teil von Feature 11

- **Beim Vergleich der Build-Warnung ist mir ein Handgriff misslungen:** Ein `git stash` mit
  anschließendem Build ließ sich nicht sauber zurückholen. Gelöst ohne Verlust (Build-Ausgaben
  zurückgesetzt, Stash zurückgeholt, neu gebaut, Manifest geprüft). Ergebnis des Vergleichs: Die
  Node-Warnung `DEP0205` (`module.register()`) erscheint auch beim Stand vor Feature 11.
- Unverändert rot wie vor dem Bau: `lint:container` (genau ein Fehler, WebAuthn-Alias),
  `doctrine:schema:validate`. `make fix-check` gibt es weiterhin nicht.

### Abschlussbericht Fehlerauftrag BF-149 und BF-150 (`sdd-build`, 2026-09-13)

Eingang: Fehlerauftrag aus `qa-report.md` (Status `review` → `building`). Reihenfolge nach Schwere:
BF-150 (hoch) und BF-149 (mittel); gebaut wurde BF-149 zuerst, weil seine Reproduktion die laufende
Umgebung brauchte. Nichts committet.

#### 1 · Umgesetzt

- **BF-150** · `growth/config.json` zählt: fünf Pfadschritte mit führendem Stern. Gegen eine frische
  Umami-3.3.1-Instanz mit zwei Besuchern (Deutsch, Französisch) nachgeprüft: Suche 2 · 2 · 2 · 2, App 1 · 1,
  Partner 1 · 1, Organisationen 2 · 2; die alte Schreibweise auf denselben Daten weiterhin 0
  (`qa/11/bf150-nachpruefung.py`). Die QA-Reproduktion `Qa11TrichterMusterTest` läuft scharf, neu
  `tests/Integration/Usage/GrowthTrichterTest.php` (3 Tests) mit drei roten Gegenproben. AK-14 und AK-15
  sind damit erfüllbar; AK-35 bleibt an T29 (Website-Kennung).
- **BF-149** · Höchstens eine Weiterleitung zur Zeit (`UmamiForwarder`, Sperre `umami-weiterleitung`,
  100 ms Wartezeit auf den Platz, zweiter Blick auf den Unterbrecher nach dem Warten). Reproduktion
  vorher/nachher auf derselben Umgebung: sechs Zählaufrufe je 2,05–3,88 s und Restaurantliste 1,8 s →
  einer 2,06 s, fünf 0,17–0,40 s, Restaurantliste 0,06 s (`qa/11/bf149-nachpruefung.sh`). Drei neue Fälle
  in `UmamiForwarderTest`, zwei rote Gegenproben.
- Verifikation: `php bin/phpunit` 1324 Tests, 7284 Zusicherungen, 10 übersprungen (vorher 1318/11);
  `lint:yaml config` grün; `lint:container` unverändert mit dem einen WebAuthn-Alias-Fehler.
  ⚠ **Berichtigt am 2026-09-13:** Hier stand „`--env=prod` grün“. Das war der Exit-Code von `tail` am Ende der
  Pipeline, nicht der von `lint:container` — im Produktionsmodus zeigt es denselben einen Fehler.

#### 2 · Offene Akzeptanzkriterien

- **Keine neuen.** Die zehn Instanz-Kriterien aus dem QA-Bericht (AK-04, 26, 28, 29, 31, 32, 34, 35, 38,
  39) bleiben an T01–T05 und T29–T31 gebunden.
- **OF-07** (neu in `spec.md`): Die Trichtertabelle und ihre Begründung in `design.md` sind falsch;
  `sdd-build` darf den Entwurf nicht ändern. Bis zur Entscheidung widerspricht der Entwurf der
  Konfiguration — wer T29 („die vier Trichter aus `design.md` als Berichte anlegen") wörtlich ausführt,
  legt Berichte an, die null zählen.

#### 3 · Getroffene Annahmen

- **Verlust bei Gleichzeitigkeit ist hinnehmbar.** Die Sperre tauscht „kein Seitenaufbau wartet" gegen
  gelegentlich verlorene Zählaufrufe: Gegen einen gesunden Eingang (30 ms) kamen von sechs exakt
  gleichzeitigen Aufrufen 6, 5 und 5 an; bei 50 ms Abstand 20 von 20. Die Zahl 100 ms ist gewählt, nicht
  vorgegeben — lang genug für eine normale Umami-Antwort, kurz genug, dass fünf Wartende keinen
  PHP-Prozess nennenswert festhalten. Die echte Antwortzeit des zweiten VPS ist unbekannt (T01–T05).
- **`flock` trennt die Threads von FrankenPHP.** Belegt ist es lokal mit mehreren PHP-Prozessen des
  Symfony-Servers, nicht im FrankenPHP-Container. `FlockStore` öffnet die Sperrdatei je Sperre neu, und
  `flock` gilt je geöffneter Datei — nach Linux-Semantik trennt das auch Threads eines Prozesses. Die
  QA sollte die Reproduktion gegen das Container-Image fahren, wenn das billig ist.
- Die Sperre nutzt die vorhandene `LockFactory` (`LOCK_DSN=flock`, `symfony/lock` steht in
  `composer.json`) — **keine neue Abhängigkeit**.

#### 4 · Systemweite Änderungen

- **`CLAUDE.md`**, Abschnitt Feature 11: zwei neue Warnblöcke (ein Platz, Stern-Regel).
- **`features/befunde.md`**: BF-149 und BF-150 auf „behoben, noch nicht ausgeliefert".
- **`features/11-nutzungsmessung/spec.md`**: OF-07 im Abschnitt *Offene Fragen*. **`tasks.md`** T09:
  Berichtigungsvermerk. **`qa-report.md`**: Behebung unter beiden Befunden.
- **Außerhalb des Repositorys:** `~/.claude/skills/growth-loop/mcp-umami/README.md` (Stern-Regel mit
  Tabelle; ⚠ die Fassung **vor** T28 sagte richtig „am Anfang oder Ende" — T28 hatte eine korrekte Aussage
  überschrieben) und `references/einrichtung.md` (Pfade beginnen mit `/` **oder** `*`, Stern nur an den
  Rändern). `server.js` stimmte schon. Sicherung der README vor dieser Änderung im Scratchpad
  (`skill-sicherung/README-vor-bf150.md`).
- **Neue Nachweisdateien** unter `qa/11/`: `bf149-nachpruefung.sh` samt Ausgabe,
  `bf150-nachpruefung.py` samt Ausgabe.
- `vendor/bin/php-cs-fixer` ist nicht installiert (`make fix` scheitert) — der Codestil der geänderten
  PHP-Dateien ist deshalb nicht maschinell geprüft.

### Abschlussbericht Fehlerauftrag BF-152 (`sdd-build`, 2026-09-13)

Eingang: Fehlerauftrag aus `qa-report.md` (Nachprüfung). Feature 11 stand auf `approved`; auf Entscheidung des
Betreibers über `review` auf `building` zurückgenommen. Vorher Feature 11 committet (`32e3ee5`, vom Betreiber
erlaubt), die Reparatur selbst ist nicht committet. Parallel BF-151 (B15) auf eigenem Branch, siehe dort.

#### 1 · Umgesetzt

Eine Sperre, die scheitert statt „belegt" zu melden, ergibt jetzt 202 `{}` statt 500: Belegen im `try` von
`forward()` mit Klassenprotokoll und Unterbrecher, Freigeben in `platzFreigeben()` mit eigenem `catch`, Sperre ohne
automatische Freigabe. Reproduktion aus dem Bericht scharf und grün; am laufenden Server `chmod 000` → 202. Zwei neue
Fälle in `UmamiForwarderTest`, vier Gegenproben rot. Suite 1330 Tests grün, 13 übersprungen.

#### 2 · Offene Akzeptanzkriterien

Keine neuen. Die zehn Instanz-Kriterien bleiben an T01–T05; AK-15 bleibt teilweise, bis BF-151 in B15 ausgeliefert
ist.

#### 3 · Getroffene Annahmen

- **Eine defekte Sperre setzt den Unterbrecher.** Die Warnung kommt damit höchstens einmal je Minute, und 60 s lang
  wird nichts gezählt — auch wenn die Sperre sich sofort wieder erholt. Die Alternative (bei jedem Aufruf neu
  versuchen) hätte bei vollem Temp-Verzeichnis je Zählaufruf eine Warnung an Sentry-Logs geschickt. Ist die Ursache
  ein volles Dateisystem, scheitert womöglich auch das Speichern des Unterbrechers; dann gibt es eine Warnung je
  Aufruf, aber keinen 500er (PSR-6 `save()` meldet `false`, statt zu werfen — nicht nachgestellt).
- **Ohne automatische Freigabe bleibt keine Sperre hängen:** Die Dateisperre endet, wenn der Prozess die Datei
  schließt; in FrankenPHP also mit dem Ende der Anfrage. Belegt ist das nur für den Normalfall (explizites
  `release()`), nicht für einen gescheiterten.

#### 4 · Systemweite Änderungen

- **`CLAUDE.md`**, Abschnitt Feature 11: Warnblock zur scheiternden Sperre.
- **`features/befunde.md`**, **`features/index.md`**, **`qa-report.md`**: Behebung eingetragen.
- **`tasks.md`**: Berichtigung einer falschen Angabe im Bericht zu BF-149 (`lint:container --env=prod`).
- **Neue Nachweisdatei** `qa/11/sperre-ausfall-nachpruefung.ausgabe.txt`.
- ⚠ **`features/befunde.md` und `features/index.md` sind auch auf `fix/bf-151-zielgruppen-formular` geändert**
  (BF-151-Zeile, Status B15). Wer beide Branches nach `main` bringt, löst dort einen Konflikt in denselben Tabellen.

