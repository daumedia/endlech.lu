# 05 · Presse-Kit — Testbericht, vierter Durchlauf

Stand: 2026-08-30 · Auftrag: **BF-100** (kritisch, auf Produktion gefunden)
Vorgänger: `qa-report.md` (31/44) · `-2.md` (37/44) · `-3.md` (42/44)

## Fazit

**Production-ready: ja** — unverändert 42 von 44, und BF-100 ist an der Ursache behoben.

**Die Reparatur hat zwei Teile, und der zweite ist der, den man übersieht.** Teil 1: Das
Verzeichnis heißt `public/presse-kit/`, damit greift Apaches `mod_dir` nicht mehr. Teil 2:
**Eine einzige Route matcht beide Formen exakt** (`path: /presse{trailing_slash}`). Ohne
Teil 2 wäre die Schleife nur für neue Besucher gelöst — der Sprung von `mod_dir` war ein
**301**, und den behalten Browser dauerhaft.

⚠ **Was diese Prüfung nicht kann:** Das Verhalten, das den Vorfall ausmachte, entsteht
**nur unter Apache**. Der Symfony-Entwicklungsserver hat kein `mod_dir` — hier war die
Adresse zu keinem Zeitpunkt kaputt, auch nicht vor der Reparatur. Geprüft wurde deshalb
**die Ursache**: dass kein Verzeichnis mehr so heißt wie eine Route, und dass keine der
beiden Adressformen noch eine Trailing-Slash-Weiterleitung auslöst. **Der abschließende
Beleg ist die Nachprüfung auf Produktion**, nicht dieser Bericht.

| | |
|---|---|
| Akzeptanzkriterien | 42 von 44 bestanden (unverändert) |
| durchgefallen | 1 — AK-11, durch Entscheidung (OF-04) |
| nicht prüfbar | 1 — AK-26, keine Pressemitteilung (OF-06) |
| Tests grün | **742 von 742**, 10 übersprungen (alle aus anderen Features) |
| Neue Tests | 1 (`RouteDirectoryCollisionTest`, aus dem Bau) |
| Neue Befunde | **keine** |

## BF-100 · gegengeprüft

| Prüfung | Ergebnis |
|---|---|
| `/presse` | **302** → `/lb/presse` — kein 301 |
| `/presse/` | **302** → `/lb/presse` — kein 301 |
| `/presse` mit Folgen | **1 Weiterleitung**, endet mit 200 (vorher: 50 und Abbruch) |
| Zieladresse | sauber — `ignoreAttributes` hält `trailing_slash` aus der URL heraus |
| Verzeichnis | `public/presse/` existiert nicht mehr; `public/presse-kit/` liefert aus |

**Reichweite der neuen Routenform geprüft** — sie matcht nicht mehr, als sie soll:

| Adresse | Antwort |
|---|---|
| `/presseXYZ` · `/presse/foo` · `/presse.html` · `/PRESSE` · `/presse-kit` | je **404** |
| `/presse//` | 302 → `/lb/presse` (normalisiert, landet richtig) |

**Der Regressionsschutz greift, zweimal unabhängig geprüft:** `RouteDirectoryCollisionTest`
wird rot, sobald `public/presse/` angelegt wird, und nennt die kollidierende Route beim
Namen; ohne das Verzeichnis grün. ⚠ Der Lauf hatte beim Schreiben selbst ein Loch — er
übersprang Pfade mit `{`, also ausgerechnet die neue Form. Das ist im Bau aufgefallen und
behoben; er vergleicht jetzt den statischen Anfang des Segments. **Ein Prüfwerkzeug, das
den Fehler von gestern nicht mehr fände, ist keins.**

## Regression

| Prüfung | Ergebnis |
|---|---|
| Paket am neuen Pfad | 200, 1 097 194 Bytes, `application/zip`, 6 Einträge, kein defekter |
| Alte Adresse `/presse/presse-kit-endlech-lu.zip` | **404** — erwartet, siehe Hinweis |
| Vorschauen, vier Sprachen | 5 Kacheln, **0 gebrochen**, 0 fehlgeschlagene Anfragen |
| Barrierefreiheit | axe-core **0 Verstöße** in lb, de, fr, en · 320 px ohne Querscrollen |
| Struktur | 7 Abschnitte, genau ein `<h1>`, in allen vier Sprachen |
| Pfad-Traversal am neuen Verzeichnis | `/presse-kit/../.env`, `/presse-kit/../config/services.yaml` → je **404** |
| Container gegen Produktivkonfiguration | `cache:warmup --env=prod` OK |

## Hinweise

- **Die alte Download-Adresse ist 404.** `/presse/presse-kit-endlech-lu.zip` war rund eine
  halbe Stunde öffentlich erreichbar. Wer sie in dieser Zeit verlinkt hat, muss nachziehen —
  praktisch dürfte das niemand sein.
- **`/presse-kit/` auf Produktion prüfen.** Lokal ergibt das 404 (der Entwicklungsserver
  liefert keine Verzeichnisse aus). Unter Apache entscheidet `Options -Indexes`, ob dort
  eine Dateiliste steht. Harmlos — die Dateien sind ohnehin öffentlich —, gehört aber in
  die Nachprüfung.
- **`design.md` nennt noch `public/presse/`** an drei Stellen. Als **OF-12** in der Spec
  vermerkt; `sdd-build` ändert den Entwurf nicht.

## Nächster Schritt

`/sdd-deploy 05` als Nachrelease **v2026.08.30.1**. Die Nachprüfung auf Produktion ist
diesmal nicht Formsache, sondern der eigentliche Beleg:

1. `/presse` → **302 direkt** auf eine Sprachfassung, **kein 301**, keine Schleife
2. `/presse/` → dasselbe
3. Paket unter `/presse-kit/presse-kit-endlech-lu.zip` erreichbar und entpackbar
4. `/presse-kit/` — Dateiliste oder nicht
5. Fußzeile zeigt `v2026.08.30.1`
