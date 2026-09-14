# 11 · Nutzung messen, ohne zu verfolgen — Systemdesign

Status: `architected` · Stand: 2026-09-14 (Überarbeitung) · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

> **Überarbeitung vom 2026-09-14** nach der geänderten Spezifikation (Decision Log #23–#28): Ziel der
> Weiterleitung ist die **vorhandene Umami-Instanz über ihre eigene Domain**, nicht mehr ein eigener
> Zähl-Eingang mit Schlüsselbindung, Firewall, Länderdatei und SSH-Tunnel. Geändert gegenüber dem
> gebauten Stand: Besucheradresse im Zählaufruf statt in einer eigenen Kopfzeile (Entscheidung 18),
> gewöhnliche Zertifikatsprüfung statt Schlüsselbindung (5, 19), Umamis Vorgaben für Ort und
> Sitzungswechsel (8, 9), Growth-Loop über die Domain (16), Betriebsschritte neu. Alles Übrige ist
> gebaut, geprüft und bleibt.

## Überblick

Jede öffentliche Seite lädt ein kleines Zählskript von **endlech.lu selbst** (`/zaehler.js`, der
Umami-Tracker in fester Version, als Datei im Repository). Das Skript schickt Seitenaufrufe und
benannte Ereignisse an **`/api/send` auf endlech.lu**. Dort nimmt die Anwendung jeden Zählaufruf an,
**prüft und kürzt ihn nach einer festen Liste**, setzt die Adresse des Besuchers ein und reicht nur das
Erlaubte per HTTPS an die **Umami-Domain** weiter. Der Browser sieht die Umami-Domain nie.

Die Umami-Instanz läuft auf dem zweiten VPS aus dem Docker-Katalog des Hosters, hinter dessen Proxy
(Traefik) mit einem gewöhnlichen Zertifikat. Oberfläche und Zählschnittstelle sind über die Domain
öffentlich; die Oberfläche schützt die Anmeldung (Betreiber mit zweitem Faktor, Growth-Loop nur lesend).

Ob gemessen wird, entscheidet sich an drei Stellen, von außen nach innen: im Seitenkopf (kein Skript
auf Verwaltung, Profil und Token-Seiten, kein Skript ohne konfigurierte Website), im Browser (Schalter
in `/legal`, „Do Not Track", „Global Privacy Control", falsche Domain) und in der Weiterleitung
(dieselben Regeln noch einmal — weil jeder Client alles schicken kann).

Quellen, gelesen am 2026-09-13: Umami-Doku *Tracker configuration* und *Environment variables*
(docs.umami.is); Quelltext **Umami v3.3.1**: `src/tracker/index.ts`, `src/app/api/send/route.ts`,
`src/lib/detect.ts`, `src/lib/ip.ts`, `src/lib/crypto.ts`, `src/queries/sql/reports/getFunnel.test.ts`;
Symfony HttpClient im Projekt. **Nachgesehen am 2026-09-14 im Image `umami:3.3.1`:** Schema der
Zählschnittstelle (Feld `ip` im Zählaufruf erlaubt), Reihenfolge der Adress-Kopfzeilen, Ortsermittlung
(mit `ip` im Zählaufruf werden Orts-Kopfzeilen übergangen), Telemetrie (Bild im Dashboard), Anmeldung
(kein Deckel für Fehlversuche gefunden). **Nachgestellt am 2026-09-14** an einer Wegwerf-Instanz 3.3.1,
Ergebnis unter Entscheidung 18.

## Seiten und Routen

| Route | Zweck | Zugang |
|---|---|---|
| `GET /zaehler.js` | Statische Datei: Umami-Tracker v3.3.1, unverändert, vom Webserver ohne PHP ausgeliefert | öffentlich |
| `POST /api/send` | **Weiterleitung** eines Zählaufrufs an Umami, nach Prüfung und Kürzung. Sprachfrei, zustandslos, eigener Routenblock | öffentlich, gedeckelt |
| `GET /api/send`, `/api/send/…`, `/api/websites`, `/api/login` … | existieren nicht → 404 bzw. 405 (AK-25) | — |
| `/{_locale}/legal` | **geändert:** Absatz zur Messung und Widerspruchsschalter im Datenschutzabschnitt; seit der Überarbeitung mit Region, Stadt und monatlichem Sitzungswechsel | öffentlich |
| alle Seiten mit `base.html.twig` | **geändert:** Zählskript im Seitenkopf, wenn die Messregel es erlaubt | — |
| `/{_locale}/restaurants`, `/{_locale}/restaurants/{id}`, Wartelisten-Seiten, Board-Idee, `/presse`, `/open` | **geändert:** Auslöser für die Ereignisse der Trichter | unverändert |
| `/{_locale}/roadmap`, `/{_locale}/changelog` | **geändert:** Eintrag `usage_analytics` entfällt, Changelog-Eintrag bei Auslieferung | öffentlich |

Keine Seite danach, kein neuer Bildschirm in der Anwendung. Außerhalb der Anwendung: Umamis eigene
Oberfläche unter der Umami-Domain (Anmeldung, Dashboard) — nicht Teil dieses Repositorys.

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
├── Adresse einsetzen             GEÄNDERT: die Adresse des Besuchers (Anfrage, hinter dem Proxy der
│                                 Anwendung) als Feld `ip` in den gekürzten Zählaufruf; ein vom
│                                 Client mitgeschicktes `ip` hat die Kürzung vorher schon entfernt
├── Weiterreichen                 eigener HTTP-Client ohne Protokollierung, Zeitlimit 2 s,
│                                 Gesamtdauer 3 s, höchstens eine Weiterleitung zur Zeit;
│                                 GEÄNDERT: gewöhnliche Zertifikatsprüfung, keine Schlüsselbindung
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
| **Suche** | `*/restaurants` → `filter_angewandt` → `*/restaurants/*` → `kontaktweg_genutzt` | 60 min |
| **Warteliste App** | `*/app` → `warteliste_eingetragen` | 60 min |
| **Warteliste Partner** | `*/partner` → `warteliste_eingetragen` | 60 min |
| **Warteliste Organisationen** | `*/organisationen*` → `warteliste_eingetragen` | 60 min |

**Umami ersetzt `*` nur am Anfang oder am Ende eines Schritts** durch `%` (`getFunnel.ts`); ein Stern
mitten im Pfad bleibt ein wörtliches Zeichen und zählt null (BF-150, gegen eine echte Instanz gemessen).
Mit führendem Stern laufen die Sprachen ohne Zusatz zusammen (AK-14); getrennt je Sprache genügt ein
Schritt mit festem Präfix (AK-05). *Berichtigt am 2026-09-14 nach OF-07 — hier stand bis dahin die
mittige Form `/*/restaurants` mit der falschen Begründung „auch mitten im Pfad".*

⚠ Die Wartelisten-Trichter trennen sich über den ersten Schritt, nicht über `liste`: Umamis Trichter
filtert Ereignisse zwar nach Datenfeldern, der Growth-Loop kennt aber nur Pfade und Ereignisnamen.

⚠ **Der Growth-Loop wertet genau eine Kette aus** (`conversion_events`). Dort steht der Suchtrichter;
die drei Wartelisten-Ketten stehen unter einem eigenen Schlüssel `weitere_trichter` in
`growth/config.json` und werden als Umami-Berichte angelegt. Loop 2 kann sie auf Nachfrage mit
demselben Werkzeug abfragen (`umami_funnel`).

### Auf dem zweiten VPS (Betrieb, außerhalb des Repositorys)

```
Zweiter VPS (Hostinger, Deutschland, neben Uptime Kuma) — seit 2026-09-14 nicht weiter verändert
└── Katalog-Projekt „Umami" (Docker Manager des Hosters)
    ├── Umami 3.3.1                Image fest auf 3.3.1; Port nur 127.0.0.1 (keine Veröffentlichung
    │                              auf allen Adressen); erreichbar über die Umami-Domain
    ├── PostgreSQL                 im Verbund, Volume mit Konten und Websites
    └── Route im Proxy (Traefik)   Umami-Domain → Umami, gewöhnliches Zertifikat
```

Umami-Einstellungen: **Vorgaben der Instanz, keine eigene Umgebung** (Spec, Decision Log #23). Was das
bedeutet, nachgesehen im Image 3.3.1:

| Einstellung | Stand | Folge |
|---|---|---|
| `CLIENT_IP_HEADER` | nicht gesetzt | Ohne Feld `ip` nähme Umami die Adresse aus den Kopfzeilen des Proxys — die Adresse des Anwendungs-VPS für jeden Besucher. Die Weiterleitung setzt deshalb `ip` (Entscheidung 18) |
| `SKIP_LOCATION_HEADERS` | nicht gesetzt | Unerheblich für Aufrufe über endlech.lu: Mit `ip` im Zählaufruf übergeht Umami Orts-Kopfzeilen. Direkt an die Domain gesendete Aufrufe könnten ein Land unterschieben — hingenommen (EC-08) |
| `GEOLITE_DB_PATH` | nicht gesetzt | Umamis Städtedatenbank: **Land, Region und Stadt** (Spec, Decision Log #25) |
| `SALT_ROTATION` | nicht gesetzt | Vorgabe `month`: die Sitzungskennung wechselt **monatlich** (Entscheidung 9) |
| `DISABLE_TELEMETRY`, `DISABLE_UPDATES` | nicht gesetzt | Das **Dashboard** lädt im Browser des Betreibers ein Telemetrie-Bild des Herstellers (Versionsnummer) und fragt nach Updates. Kein Besucherdatum geht dabei an den Hersteller |
| `DISABLE_BOT_CHECK` | nicht gesetzt | Bekannte Bots bleiben ungezählt (`isbot`, AK-10) |

### Zugang des Growth-Loops (außerhalb des Repositorys)

```
~/.config/umami/zugang.json       URL = Umami-Domain, Benutzer growth-loop, langes Zufallspasswort;
                                  kein Tunnel-Block — nie im Repo, chmod 600
mcp-umami (Skill growth-loop)     meldet sich per HTTPS an der Domain an; Domain und Passwort
                                  erscheinen in keiner Ausgabe; nicht erreichbar oder Anmeldung
                                  gescheitert → „Loop 2 meldet ab", kein Abbruch des Laufs
```

⚠ **Diese Änderung liegt im globalen Skill `~/.claude/skills/growth-loop/`**, nicht im Projekt. Heute
gibt `mcp-umami` die URL an vier Stellen aus (Websites-Liste, `--check`, Fehlermeldung bei 404 und bei
nicht erreichbarem Rechner) und meldet ohne Tunnel nicht ab — beides verletzt AK-39 und AK-42. Der
Aufgabenplan führt das als eigene Aufgabe; geprüft über AK-39 und AK-42. Der bisherige Tunnel-Weg darf
für andere Projekte bestehen bleiben.

### Zustände

| Bildschirm | leer | ladend | Fehler | gefüllt |
|---|---|---|---|---|
| Jede Seite | ohne Website-Kennung: kein Skript | Skript lädt `defer`, blockiert nichts | Skript oder Weiterleitung nicht erreichbar: nichts sichtbar (AK-37) | Zählaufruf geht im Hintergrund |
| Schalter in `/legal` | ohne JavaScript: verborgen, Hinweistext | — | Browserspeicher gesperrt: Schalter zeigt „nicht verfügbar", Messung bleibt wie eingestellt | „Messung an" / „Messung aus", mit `aria-pressed` |
| Umami (Betreiber) | vor dem ersten Aufruf: keine Daten | — | Umami-Domain nicht erreichbar oder Zertifikat ungültig: Weiterleitung setzt den Unterbrecher, Seiten unberührt | Seitenaufrufe, Ereignisse, Trichter |

## Datenmodell

**Keine Änderung am Datenmodell der Anwendung.** Keine Entity, keine Migration.

Was Umami speichert (PostgreSQL auf dem zweiten VPS, Schema von Umami, nicht von uns), nach der
Kürzung durch die Weiterleitung:

| Umami-Datensatz | Felder mit Inhalt | Leer, weil die Weiterleitung sie entfernt oder Umami sie nicht bestimmen kann |
|---|---|---|
| Sitzung | Kennung (**monatlich** wechselnd), Browser, Betriebssystem, Gerät, Bildschirmgröße, Sprache, **Land, Region, Stadt** | `distinctId` (Feld `id` entfernt) |
| Seitenaufruf / Ereignis | Pfad, Seitentitel, Herkunftsdomain, Ereignisname, Zeitpunkt | Abfrage (`url_query`), Herkunftspfad und -abfrage, `tag`, UTM- und Klick-Kennungen (stehen nur in der Abfrage) |
| Ereignisdaten | nur die Felder aus dem Ereigniskatalog | alles andere wird vorher abgewiesen |

**Die IP-Adresse wird nicht gespeichert** — Umami nutzt sie beim Eingang für den Ort und die
Sitzungskennung (`route.ts`: `uuid(sourceId, ip, userAgent, sessionSalt)`) und verwirft sie. Genau
deshalb muss es die **richtige** Adresse sein (AK-04, AK-40). Aufbewahrung: unbegrenzt (Spec, Decision
Log #12). Löschregel: Es gibt keinen Bezug zu einem Konto, also nichts, was bei einer Kontolöschung
mitgehen müsste.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Besucher | — | Zählaufrufe über `/api/send`, im Rahmen des Ereigniskatalogs | Weiterleitung (Prüfung, Deckel) |
| Anwendungsserver | — | `POST /api/send` an der Umami-Domain | nichts Eigenes — die Zählschnittstelle ist öffentlich (EC-08) |
| Betreiber | alles in Umami | Einstellungen, Websites, Benutzer | Umami-Konto mit eigenem Namen und Passwort (Voreinstellung ersetzt), **zweiter Faktor für diesen Benutzer** (AK-28, AK-41) |
| Growth-Loop | die Website endlech.lu | nichts | Umami-Rolle „view-only" plus Team-Rolle „team-view-only"; langes Zufallspasswort, ohne zweiten Faktor (AK-29) |
| Jeder andere im Netz | Anmeldeseite, sonst nichts | Zählaufrufe direkt an die Domain (hingenommen, EC-08) | Umamis Anmeldung (AK-26); Umami-Port nur auf 127.0.0.1 |

⚠ **Den zweiten Faktor für den Betreiber-Benutzer einschalten — nicht global und nicht für das Team
erzwingen.** Umami 3.3.1 kennt beides (Einstellungen „2FA global" und je Team); erzwungen scheitert der
Growth-Loop an jeder Anmeldung, und Loop 2 meldet dauerhaft ab.

⚠ **Die Weiterleitung ist die einzige Grenze für den Inhalt.** Tracker-Optionen (ohne Abfrage, ohne
Anker, Domain, „Do Not Track") helfen nur ehrlichen Browsern; ein selbst gebauter Zählaufruf umgeht
sie alle. Deshalb wiederholt die Weiterleitung jede Regel, die den Inhalt betrifft — nach der
Projektkonvention „Die Prüfung gehört dorthin, wo der Wert hereinkommt". Für den Weg direkt an die
Umami-Domain gilt keine dieser Regeln; die Spec nimmt das hin (Decision Log #28).

### Was die Weiterleitung prüft und kürzt

| Teil des Zählaufrufs | Regel | Bei Verstoß |
|---|---|---|
| Methode, Größe | nur `POST`, Rumpf höchstens 8 KB, JSON | 405 / 413 / 400 |
| `type` | nur `event` (Seitenaufruf und Ereignis); `identify` und `performance` abgewiesen | 400 |
| `website` | exakt die konfigurierte Kennung | 400 |
| `hostname` | exakt `endlech.lu` (nicht `www.`) | 400 (AK-09) |
| `url` | nur Pfad; Abfrage und Anker werden entfernt; Pfad unter `/{Sprache}/admin`, `/{Sprache}/profile` oder mit Token-Muster (64 Hex-Zeichen) | Pfad gekürzt bzw. 400 (AK-03, AK-06, AK-07) |
| `referrer` | fremde Herkunft → nur `https://<domain>/`; eigene Herkunft → entfällt | gekürzt (AK-02) |
| `title` | höchstens 200 Zeichen | gekürzt |
| `name`, `data` | Ereignisname und Datenfelder nur aus dem Ereigniskatalog | 400 (AK-12, AK-13, AK-17) |
| `id`, `tag` | entfernt | — (AK-21) |
| `ip`, `userAgent`, `timestamp`, `browser`, `os`, `device` **vom Client** | entfernt — Umami 3.3.1 nimmt diese Felder im Zählaufruf an; ein Client könnte sonst Adresse, Zeitpunkt oder Gerät unterschieben | — |
| `ip` **von der Weiterleitung** | NEU: Adresse der Anfrage (hinter dem Proxy der Anwendung, `TRUSTED_PROXIES`), nur wenn sie eine gültige IP ist; sonst entfällt das Feld | — (AK-04, AK-40) |
| `screen`, `language` | Format geprüft (`1234x567`, Sprachkürzel) | entfällt |
| Kopfzeilen an Umami | **nur** `Content-Type`, `User-Agent` (≤ 512 Zeichen) und `x-umami-cache` (≤ 2 KB); keine Cookies, kein `Accept-Language`, kein `X-Forwarded-For`, keine Orts-Kopfzeilen. GEÄNDERT: die eigene IP-Kopfzeile entfällt | — |
| `DNT: 1` oder `Sec-GPC: 1` an der Anfrage | nichts weiterleiten | 204 (AK-22, Rückhalt) |

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `POST /api/send` | **300 je Stunde je Adresse**, gleitendes Fenster, eigenes Kontingent `usage_collect` | 429, nichts weitergeleitet; der Tracker verwirft die Antwort still, die Seite merkt nichts (AK-27) | `config/packages/framework.yaml` (+ `when@test` 10000), Zweig im `RouteRateLimitSubscriber` — derselbe Ort wie Sitemap und Datensatz |
| `POST /api/send` | 8 KB Rumpf, Katalogprüfung | 413 / 400 | Weiterleitung |
| Weiterleitung → Umami | Zeitlimit 2 s, Gesamtdauer 3 s; höchstens eine zur Zeit; Unterbrecher 60 s nach einem Fehlschlag | 202 `{}` ohne Wartezeit | Weiterleitungsdienst; Unterbrecher-Merker im Cache-Pool der Anwendung |
| Zählschnittstelle an der Umami-Domain | **keins** | hingenommen (EC-08); Last trifft den zweiten VPS (OF-08) | — |
| Umami-Anmeldung | **kein Deckel für Fehlversuche** in Umami 3.3.1 gefunden | Betreiber: zweiter Faktor macht ein erratenes Passwort wertlos (AK-41); Growth-Loop: langes Zufallspasswort, nur lesend (AK-29) | Umami-Konten (Betriebsschritt 3); Begrenzung am Proxy des VPS: OF-08 |

⚠ `TRUSTED_PROXIES` ist in Produktion gesetzt (am 2026-09-13 über die `https://`-Weiterleitung
nachgeprüft); ohne es teilten sich alle Besucher einen Deckel — **und seit der Überarbeitung auch eine
Adresse in Umami**: Das Feld `ip` bekäme dann für jeden Besucher die Adresse des Proxys (AK-04, AK-40).

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| **Umami 3.3.1**, selbst betrieben auf dem zweiten VPS (Hostinger, Deutschland, bestehender Auftragsverarbeitungsvertrag), erreicht über die Umami-Domain per HTTPS | Seitenaufrufe, Herkunft, Ereignisse, Trichter | Pfad **ohne** Abfrage, Seitentitel, Herkunftsdomain, Bildschirmgröße, Sprache, Browserkennung, Ereignisname mit Katalogfeldern; die Besucher-IP als Feld `ip`, nur für Ort und Sitzungskennung | Abfrage und Anker, Herkunftspfad und -abfrage, `id`, `tag`, vom Client gesetzte `ip`/`userAgent`/`timestamp`/`browser`/`os`/`device`, alle Cookies, alle übrigen Kopfzeilen (`Accept-Language`, `X-Forwarded-For`, `cf-*` …); ganze Zählaufrufe von Verwaltung, Profil, Token-Seiten, mit `DNT`/`GPC`, von `www.` |

Kein weiterer Dienst. Das Zählskript liegt im Repository; kein CDN. Das Telemetrie-Bild des Herstellers
lädt nur das Dashboard im Browser des Betreibers, nicht die Anwendung und nicht die Besucherseiten.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **Weiterleitung in der Anwendung** (Controller, `/api/send`) | Weiterleitung im Proxy von Coolify; Skript direkt von der Umami-Domain | Spec, Decision Log #24: Deckel und Kürzung bleiben, die Umami-Domain steht nie im Seitenquelltext, Werbeblocker greifen seltener. Eine Proxy-Regel in Coolify wäre eine von Hand gepflegte Einstellung, die kein Prüflauf misst und die niemand kürzen kann |
| 2 | **Tracker als Datei im Repository** (`public/zaehler.js`, v3.3.1, MIT) | vom Umami-Server durchreichen; npm-Paket in das App-Bundle | Lädt ohne PHP und ohne zweiten VPS (AK-37). Im App-Bundle ginge `document.currentScript` verloren, aus dem der Tracker seine Einstellungen liest. ⚠ Wer das Image auf dem VPS aktualisiert, ersetzt die Datei mit; `TrackerFileTest` vergleicht die Version |
| 3 | **Eigene Ereignis-Auslöser** statt `data-umami-event`-Attributen | Umamis Klick-Attribute | Der Tracker hält bei Links ohne `target="_blank"` die Navigation an, bis der Zählaufruf fertig ist (`index.ts`, `onClick`). Bei `tel:` und `mailto:` wartete der Besucher auf die Messung |
| 4 | **Prüfen und kürzen in der Weiterleitung**, zusätzlich zu den Tracker-Optionen | nur Tracker-Optionen | Jeder Client kann einen Zählaufruf selbst bauen; die Zusagen AK-03, AK-12, AK-17, AK-21 wären sonst Bitten |
| 5 | GEÄNDERT: **Gewöhnliche Zertifikatsprüfung** gegen die Umami-Domain | Schlüsselbindung (`pin-sha256`) wie bisher; Prüfung abschalten | Die Domain trägt ein öffentliches Zertifikat des Proxys, das sich bei jeder Erneuerung ändern kann — eine Schlüsselbindung bräche dann still, und die Messung stünde ohne Fehlermeldung. Kein anderer Aufruf der Anwendung schaltet die Prüfung ab (nachgesehen in `src/` und `config/`), und die Nahverkehrs-Schnittstelle läuft in Produktion über geprüftes HTTPS — das Zertifikatsbündel im Image trägt also. Die Prüfung abzuschalten schickte Besucheradressen an jeden, der sich dazwischen stellt |
| 6 | **Eigener HTTP-Client ohne Protokollierung** | der Standard-Client (`http_client`) | Der Kanal `http_client` ist in `prod` nicht ausgeschlossen, und `fingers_crossed` schreibt bei einer Warnung den ganzen Puffer — samt Umami-Domain. Geloggt wird bei Fehlschlag nur die Ausnahmeklasse. Grund seit der Überarbeitung: AK-42 (der Weg vom Projekt zum VPS steht in keinem Protokoll) |
| 7 | **Synchron mit 2 s Zeitlimit, einem Platz und 60-s-Unterbrecher** | über den Messenger asynchron | Asynchron füllte jeder Seitenaufruf die Tabelle `messenger_messages`, und Umamis Sitzungs-Token käme nie beim Browser an. Unterbrecher und Platz begrenzen die Last bei ausgefallener Domain auf einen hängenden Aufruf je Minute (BF-149, BF-152) |
| 8 | GEÄNDERT: **Umamis Städtedatenbank** (Vorgabe der Instanz) | eigene Länderdatei; IP nicht weitergeben | Spec, Decision Log #23 und #25: keine Änderung am VPS. Ohne IP gäbe es keinen Ort und nur eine Sitzung für alle (Entscheidung 18) |
| 9 | GEÄNDERT: **Sitzungskennung wechselt monatlich** (Umamis Vorgabe `month`) | `SALT_ROTATION=day` per Umgebungszeile | Betreiberentscheidung vom 2026-09-14: keine Änderung am VPS. Aufrufe eines Browsers von derselben Adresse sind damit bis zu einem Monat verknüpfbar; Trichter mit 60-min-Fenster sind unberührt. `/legal` nennt „wechselt monatlich" (AK-31) — bis dahin stand dort „täglich" |
| 10 | **Widerspruch über `umami.disabled` im Browserspeicher** | eigener Merker plus Vor-Versand-Prüfung | Der Tracker prüft den Schlüssel vor **jedem** Versand selbst (`trackingDisabled()`); ein eigener Merker wäre eine zweite Stelle, die auseinanderlaufen kann |
| 11 | **GPC in der Vor-Versand-Prüfung, DNT per Tracker-Option; beides zusätzlich in der Weiterleitung** | nur Weiterleitung; Skript bei DNT/GPC gar nicht ausliefern | AK-22 verlangt, dass **kein** Zählaufruf den Browser verlässt — das geht nur im Browser. Die Weiterleitung ist der Rückhalt für Clients, die die Option ignorieren |
| 12 | **Pfade `/zaehler.js` und `/api/send`** | beliebige andere | Liegt das Skript in der Wurzel, schickt der Tracker ohne weitere Einstellung an `/api/send`. `/api/` nimmt der Service Worker grundsätzlich aus, die robots.txt sperrt es bereits. Kein Verzeichnis `public/api` — BF-100 |
| 13 | **Token-Seiten über den Routenparameter `token` erkennen** | Liste aus `SeoRegistry::EXCLUDED_ROUTES` | Die SEO-Liste enthält Anmeldung und Registrierung, die hier gemessen werden sollen (Spec, Decision Log #5). Der Parameter erfasst jede künftige Token-Route ohne Pflege |
| 14 | **Zustimmung beim Absenden, nur wenn noch nicht unterstützt** | nur nach erfolgreicher Speicherung | Die Zustimmung ist ein Umschalter und leitet ohne Rückmeldung zurück. Ein Erfolgsmerker bräuchte eine Änderung am Board-Controller für ein reines Messereignis |
| 15 | **Wartelisten-Erfolg über das gemeinsame Erfolgs-Partial** | am Absenden-Knopf | Das Partial wird nur bei Erfolg gerendert; ein Klick auf „Absenden" zählte auch fehlerhafte Versuche (AK-16) |
| 16 | GEÄNDERT: **Growth-Loop meldet sich über die Umami-Domain an; `mcp-umami` verschweigt die Domain und meldet bei jedem Verbindungs- oder Anmeldefehler ab** | Tunnel wie bisher; Domain in den Ausgaben zulassen | Spec, Decision Log #26: kein Tunnel. Die Antworten des Werkzeugs landen in `growth/laeufe/` im Repository — eine Domain darin wäre der dokumentierte Weg zum VPS (AK-42). „Meldet ab" statt einer Fehlermeldung mit Adresse hält Loop 1 am Laufen (AK-39) |
| 17 | **Zweiter Trichterschlüssel `weitere_trichter`** in `growth/config.json` | Wartelisten-Trichter nur in Umami | Die Spec verlangt beide Schrittfolgen in der Konfiguration (AK-35); der Loop wertet weiterhin eine Kette aus |
| 18 | NEU: **Besucheradresse als Feld `ip` im Zählaufruf** | eigene Kopfzeile wie bisher (`X-Endlech-Client-Ip`); Kopfzeile `True-Client-Ip` | Die eigene Kopfzeile liest Umami nur mit `CLIENT_IP_HEADER` — eine Änderung am VPS. **Nachgestellt am 2026-09-14** an einer Wegwerf-Instanz 3.3.1, mit festen Proxy-Kopfzeilen einer Adresse aus dem 179er-Bereich wie beim Anwendungs-VPS: ohne `ip` landeten zwei verschiedene Besucher gemeinsam in **Argentinien** und in **einer** Sitzung; mit `ip` bekam jeder sein Land (LU mit Region und Stadt, FR) und eine eigene Sitzung, und ein untergeschobenes `cf-ipcountry: JP` blieb wirkungslos. `True-Client-Ip` wirkte ebenfalls, prüft aber Orts-Kopfzeilen weiter mit und hängt daran, dass der Proxy die Kopfzeile unverändert lässt |
| 19 | NEU: **`APP_UMAMI_UPSTREAM_PIN` entfällt** samt Parameter | leer stehen lassen | Eine Variable ohne Wirkung wird irgendwann in Coolify gesetzt und für Schutz gehalten. Die Weiterleitung braucht nur noch Website-Kennung und Umami-Domain; ohne Domain leitet sie wie bisher nichts weiter |

## Unterlagen, die mitziehen

| Datei | Änderung | AK |
|---|---|---|
| `translations/messages.{de,en,fr,lb}.yaml` | Absatz zur Messung: **Land, Region und Stadt**; Sitzungskennung **wechselt monatlich** | AK-31 |
| `templates/impressum/index.html.twig` | unverändert, sofern der Absatz nur aus dem Katalog kommt | AK-23, AK-31 |
| `src/Roadmap/RoadmapRegistry.php`, `translations/roadmap.*.yaml` | Eintrag `usage_analytics` entfernt (gebaut) | AK-32 |
| `src/Roadmap/ChangelogRegistry.php`, `translations/changelog.*.yaml` | beim Release, das die Messung scharfschaltet: `SHOWN` mit Text in vier Sprachen, gleiche Formulierung wie AK-31 | AK-32 |
| `docs/prd.md` | gebaut; kein weiterer Änderungsbedarf | AK-33 |
| `docs/datenschutz.md` | Eintrag „Nutzungsmessung": Umami über eigene Domain, Region und Stadt, monatliche Sitzung, kein Tunnel, Standort über DNS auffindbar, Telemetrie nur im Dashboard | AK-31 |
| `.env`, `config/services.yaml` | `APP_UMAMI_UPSTREAM_PIN` und Parameter entfernen; Kommentar zu `APP_UMAMI_UPSTREAM` („Umami-Domain, `https://…`") | — |
| `CLAUDE.md` | Abschnitt Feature 11: Schlüsselbindung und eigene IP-Kopfzeile durch Zertifikatsprüfung und Feld `ip` ersetzen; Warnung „ohne `ip` landen alle Besucher in einer Sitzung"; Tunnel entfernt | — |
| `growth/config.json` | `umami.website_id` nach Betriebsschritt 3 | AK-35 |
| `~/.claude/skills/growth-loop/mcp-umami/` (global) | Domain in keiner Ausgabe; „meldet ab" bei Verbindungs- und Anmeldefehlern auch ohne Tunnel; README und `references/einrichtung.md`: Zugang über die Domain | AK-39, AK-42 |

## Betriebsschritte vor dem Deploy (Betreiber)

Ersetzen die Schritte 1–6 vom 2026-09-13. Schon erledigt am 2026-09-14: Umami-Port nur auf 127.0.0.1,
Image fest auf 3.3.1.

1. **Standort** (OF-02): Deutschland laut Betreiber — nur das Ergebnis in `docs/datenschutz.md`.
2. **Route wieder einschalten:** Im Katalog-Projekt die Proxy-Labels für die Umami-Domain zurück; der
   Port bleibt `127.0.0.1`. Nachweis: Die Domain antwortet per HTTPS mit gültigem Zertifikat, und die
   Anmeldeseite erscheint; Adresse des VPS mit dem Umami-Port antwortet nicht (AK-26).
3. **Konten:** Voreinstellung `admin`/`umami` ersetzen, **zweiter Faktor für diesen Benutzer** (nicht
   global, nicht fürs Team); Website `endlech.lu` anlegen, falls nicht vorhanden, und einem Team
   zuordnen; Benutzer `growth-loop` mit Rolle view-only, im Team team-view-only, ohne zweiten Faktor,
   langes Zufallspasswort. Nachweise: AK-28, AK-29, AK-41.
4. **Zugangsdatei** `~/.config/umami/zugang.json` mit Umami-Domain, `growth-loop` und Passwort, `chmod
   600`, ohne Tunnel-Block. Nachweis: `mcp-umami --check` listet die Website, ohne die Domain auszugeben
   (AK-35, AK-42).
5. **Coolify, Anwendung** (nicht Worker): `APP_UMAMI_WEBSITE_ID` und `APP_UMAMI_UPSTREAM` =
   `https://<Umami-Domain>`. `APP_UMAMI_UPSTREAM_PIN` nicht setzen, eine vorhandene entfernen. Ohne
   Website-Kennung bleibt die Messung lautlos aus — ein Deploy vor diesem Schritt ist gefahrlos.

⚠ **`APP_UMAMI_UPSTREAM` nie ins Repository, in kein Protokoll, in keine Fehlermeldung.** Die Domain
steht zwar im öffentlichen DNS; dokumentiert wäre aber der Weg von endlech.lu zum VPS der Überwachung
(AK-24, AK-42).

## Abdeckung der Akzeptanzkriterien

Abgegangen aus `features/11-nutzungsmessung/spec.md` (Stand 2026-09-14), in der Reihenfolge der Datei —
also AK-40 nach AK-05, AK-41 nach AK-28, AK-42 nach AK-30.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Zählskript im Seitenkopf, Weiterleitung an die Umami-Domain, Umami | Nachweis in Umami binnen 5 min; setzt Betriebsschritte 2 und 5 voraus |
| AK-02 | Weiterleitung: Herkunft auf Domain gekürzt | Tracker behält Herkunftspfade — nur die Weiterleitung kürzt |
| AK-03 | Tracker-Option „ohne Abfrage" + Weiterleitung entfernt Abfrage | doppelt, Entscheidung 4 |
| AK-04 | Weiterleitung setzt `ip` aus der Anfrage (Entscheidung 18), `TRUSTED_PROXIES`; Umamis Städtedatenbank | geändert; Nachweis mit Besuchen aus zwei Ländern |
| AK-05 | Pfade mit Sprachpräfix; Umami-Filter mit festem Präfix bzw. führendem `*` | Trichter-Tabelle |
| AK-40 | Feld `ip` je Besucher geht in Umamis Sitzungskennung ein | neu; Entscheidung 18, nachgestellt |
| AK-06 | Messregel (Verwaltung, Profil) + Weiterleitung weist diese Pfade ab | |
| AK-07 | Messregel (Routenparameter `token`) + Weiterleitung weist Token-Muster ab | Entscheidung 13 |
| AK-08 | Messregel erlaubt Anmeldung, Registrierung, Formulare | |
| AK-09 | Website-Kennung leer außerhalb Produktion; Tracker-Option Domain `endlech.lu`; Weiterleitung prüft `hostname` | dreifach |
| AK-10 | Umami-Bot-Erkennung (`isbot`), `DISABLE_BOT_CHECK` nicht gesetzt; Weiterleitung reicht die Browserkennung durch | Vorgabe der Instanz |
| AK-11 | Ereignis-Auslöser Filter und Kontaktweg; Suchtrichter in Umami | Trichter-Tabelle |
| AK-12 | Ereigniskatalog `kontaktweg_genutzt` (`art`, `plattform`); Auslöser sendet nie `href` | Entscheidung 3 |
| AK-13 | Auslöser Filterformular sammelt nur feste Schlüssel; `ort` nur als Merker; Katalogprüfung | |
| AK-14 | Umami-Trichter mit führendem `*` | Trichter-Tabelle, BF-150 |
| AK-15 | Auslöser beim Erscheinen im Erfolgs-Partial mit `liste`; Wartelisten-Trichter je erster Seite | Entscheidung 15; OF-05 |
| AK-16 | Erfolgs-Partial wird bei Fehlern nicht gerendert | Entscheidung 15 |
| AK-17 | Katalog erlaubt bei `warteliste_eingetragen` nur `liste`; Weiterleitung weist andere Felder ab | |
| AK-18 | Auslöser beim Absenden (noch nicht unterstützt); kein Kontofeld erlaubt | Entscheidung 14 |
| AK-19 | Auslöser beim Klick auf Presse-Kit und Datensatz; `format` im Katalog | |
| AK-20 | Tracker setzt keine Cookies; Schalter nutzt Browserspeicher; Weiterleitung setzt keine | zustandslose Route |
| AK-21 | Weiterleitung entfernt `id`; Katalog kennt kein Kontofeld; Messregel unterscheidet nicht nach Anmeldung | |
| AK-22 | Tracker-Option „Do Not Track"; Vor-Versand-Prüfung für GPC; Weiterleitung als Rückhalt | Entscheidung 11 |
| AK-23 | Widerspruchsschalter mit `umami.disabled` | Entscheidung 10 |
| AK-24 | Skript und Zählweg auf endlech.lu; CSP bleibt `'self'`; Umami-Domain nur in der Umgebung der Anwendung | Prüflauf gegen versehentliche Fremddomain in der CSP |
| AK-25 | nur zwei Wege (`/zaehler.js`, `POST /api/send`); keine Durchreichung anderer Pfade | Entscheidung 1 |
| AK-26 | Umamis Anmeldung schützt Oberfläche und Schnittstelle; Umami-Port nur auf 127.0.0.1 | geändert; Betriebsschritt 2 |
| AK-27 | Limiter `usage_collect` 300/h im `RouteRateLimitSubscriber` — für den Weg über endlech.lu | `LimiterCoverageTest`; EC-08 |
| AK-28 | Voreinstellung ersetzt | Betriebsschritt 3 |
| AK-41 | Zweiter Faktor für den Betreiber-Benutzer | neu; Betriebsschritt 3 |
| AK-29 | Rollen „view-only" und „team-view-only" | Betriebsschritt 3 |
| AK-30 | Eigener HTTP-Client ohne Protokollierung; Weiterleitung loggt nur Ausnahmeklasse | Entscheidung 6 |
| AK-42 | Umami-Domain nur in der Umgebung von Coolify; Client ohne Logger; Passwort nur unter `~/.config/umami/`; `mcp-umami` ohne Domain in den Ausgaben | neu; Entscheidungen 6 und 16, globaler Skill |
| AK-31 | Texte in `messages.*.yaml` und `/legal`; `docs/datenschutz.md` | Region, Stadt, monatlich (Entscheidungen 8, 9) |
| AK-32 | `RoadmapRegistry` ohne `usage_analytics`; `ChangelogRegistry` `SHOWN` beim Scharfschalten | Release-Aufgabe |
| AK-33 | `docs/prd.md` | gebaut |
| AK-34 | Gesamtweg; Schalter | Abnahme durch den Betreiber |
| AK-35 | `growth/config.json` mit Website-Kennung, `conversion_events`, `weitere_trichter`; `mcp-umami --check` über die Domain | Betriebsschritt 4; Entscheidung 17 |
| AK-36 | Pfad der Detailseite enthält die Restaurant-Nummer; Sitzung ohne Kontobezug | |
| AK-37 | Skript statisch und `defer`; Zählaufrufe asynchron; Platz, Zeitlimit und Unterbrecher; eigene Auslöser halten keine Navigation an | Entscheidungen 2, 3, 7 |
| AK-38 | — | **entfallen** (Spec, Decision Log #26) |
| AK-39 | `mcp-umami` meldet bei Verbindungs- und Anmeldefehlern ab, ohne Domain oder Passwort | geändert; Entscheidung 16, globaler Skill |

**AK ohne Stelle im Entwurf: keine.** Acht Kriterien (AK-01 teilweise, AK-26, AK-28, AK-29, AK-35,
AK-41 und die Umami-Seite von AK-04 und AK-40) hängen an Betriebsschritten außerhalb des Repositorys;
AK-39 und AK-42 zusätzlich am globalen Skill. Sie brauchen im Aufgabenplan eigene Aufgaben mit Nachweis.

**Aufgaben ohne AK aus der Überarbeitung:** das Entfernen von `APP_UMAMI_UPSTREAM_PIN` (Entscheidung 19)
ist Grundlage für Entscheidung 5, kein eigenes Kriterium.
