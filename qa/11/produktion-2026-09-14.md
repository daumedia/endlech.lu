# Feature 11 · Nachprüfung auf der Produktion (2026-09-14, `v2026.09.14.2`)

Release `v2026.09.14.2`, in Coolify ausgerollt (Anwendung). Umami-Domain, Rechnername und Adresse des zweiten VPS
stehen hier bewusst nicht.

**Stand vor dem Ausrollen:** Fußzeile `v2026.09.14`, `/zaehler.js` 404, `POST /api/send` 404, kein Zählskript, kein
Changelog-Eintrag.

## Weg zu Umami — drei Anläufe

| Anlauf | Befund | Ursache | Behoben |
|---|---|---|---|
| 1 | `POST /api/send` → 202 `{}` in ~0,09 s, auch nach 70 s | `APP_UMAMI_UPSTREAM` im Container **leer** (Prüfung im Terminal der Anwendung: „LEER") | Variable in Coolify korrigiert, neu ausgerollt |
| 2 | 202 `{}` in **2,2 s** | Erster Zählaufruf nach dem Start von Umami überschritt das Zeitlimit von 2 s; danach Unterbrecher. Aus dem Container: Heartbeat über IPv4 **200 in 0,03 s**, Zertifikat gültig; kein IPv6-Eintrag (curl -6 Exit 6) | von selbst — nach Ablauf des Unterbrechers |
| 3 | **200 in 0,15 s**, Rumpf mit Umamis Sitzungs-Token, kein `Set-Cookie` | — | — |

⚠ **Beobachtung:** Der erste Zählaufruf nach einem Neustart von Umami dauert länger als das Zeitlimit der
Weiterleitung (Umami lädt beim ersten `/api/send` seine Städtedatenbank und baut die Datenbankverbindung auf). Folge:
Nach jedem Umami-Neustart werden rund 60 s lang keine Aufrufe gezählt. Die Seiten bleiben unberührt (AK-37). Kein
Befund — das Zeitlimit ist gewollt (BF-149); festgehalten, damit ein 202 direkt nach einem Umami-Neustart nicht für
einen Ausfall gehalten wird.

## Messregel und Zählweg

```
Version v2026.09.14.2 · Zählskript-Tag auf /de/: 1 · Website-Kennung gesetzt: ja
/zaehler.js: 200 text/javascript; charset=utf-8 · gleich wie im Repository: ja
Fremde Hosts in Skript-/Link-Quellen auf /de/: 1 (github.com, Link) · CSP: connect-src 'self'
/de/login 200 · Zählskript 1 · /de/restaurants 200 · 1 · /de/legal 200 · 1
/de/admin 302 · Zählskript 0 · /de/profile 302 · 0 · /de/organisationen/confirmation/<64 Hex> 404 · 0
POST /api/send mit DNT: 204 · falscher hostname: 400 · Verwaltungspfad: 400 · GET: 405 · /api/websites: 404 · /api/auth/login: 404
```

## Texte, Changelog, Roadmap

```
AK-31 · /de/legal: Region/Stadt 1 · monatlich 1 · täglich 0 · anonym 0 · Schalter 1
AK-31 · /en/legal: Region/Stadt 1 · monatlich 1 · täglich 0 · anonym 0 · Schalter 1
AK-31 · /fr/legal: Region/Stadt 1 · monatlich 1 · täglich 0 · anonym 0 · Schalter 1
AK-31 · /lb/legal: Region/Stadt 1 · monatlich 1 · täglich 0 · anonym 0 · Schalter 1
AK-32 · /de/changelog Eintrag: 1 · /fr/changelog: 1 · /de/roadmap „Nutzung messen" unter Angedacht: 0
```

## Deckel und Seiten

```
AK-27 · 305 ungültige Zählaufrufe (hostname www, nichts weitergeleitet): 298 × 400, 7 × 429 · erster 429 beim 299. Aufruf
        (die Prüfaufrufe davor in derselben Stunde zählen mit) · Retry-After: 3390
AK-37 · Seiten trotz erschöpftem Deckel: /de/ 200 in 0,16 s · /de/restaurants 200 in 0,20 s
```

Echte Zählaufrufe dieser Nachprüfung: vier auf `/de/` (erscheinen in Umami als Besuche von einem Prüfrechner).

## Beim Betreiber offen

- **T33** · Konten: Voreinstellung ersetzt, zweiter Faktor nur für den Betreiber, `growth-loop` nur lesend (AK-28, AK-29, AK-41)
- **T34** · Zugangsdatei, `mcp-umami --check` (AK-35)
- **T43** · Trichter-Berichte in Umami — die Website-Kennung steht jetzt in `growth/config.json`
- ~~**T44**~~ · **Vom Betreiber bestätigt am 2026-09-14:** eigener Besuch mit eigenem Land, Schalter in `/legal` wirkt (AK-34, AK-04, AK-40)
