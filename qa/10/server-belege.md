# QA Feature 10 · Belege vom laufenden Server (2026-09-13)

Umgebung: `symfony server:start` im **Produktionsmodus** (`APP_ENV=prod`, `APP_DEBUG=0`, keine
Debug-Leiste) gegen die Datenbank `endlech_test` mit 11 Restaurants. Warum nicht der
Entwicklungsmodus: Dort setzt Symfony `X-Robots-Tag: noindex` auf jede Antwort — AK-15 und
AK-16 wären dort nicht unterscheidbar.

## AK-01 · Schema der ausgelieferten Datei

```
Abruf: 200 application/xml; charset=utf-8
AK-01 · ausgelieferte Sitemap, Schema: gültig
      Gegenprobe gegen sitemap.xsd allein: ungültig, wie erwartet
```

## AK-08 / AK-09 · Frist und Bestandsänderung

Lebensdauer der gespeicherten Fassung, an der Datei gemessen (der Dateispeicher setzt die
Änderungszeit auf den Ablaufzeitpunkt):

```
Abruf um 00:06:30 · Ablaufzeitpunkt der gespeicherten Fassung 00:56:30 · Abstand 3000 s (Soll 3000)
Cache-Control: max-age=600, public
```

Restaurant per SQL angelegt und gelöscht; „nach Ablauf" = Pool `cache.sitemap` geleert:

```
neues Restaurant: Nr. 750
AK-08 · sofort danach (gespeicherte Fassung):   0 Einträge für Nr. 750
AK-08 · nach Ablauf der gespeicherten Fassung: 4 Einträge (Soll 4)
AK-09 · sofort danach (gespeicherte Fassung):   4 Einträge
AK-09 · nach Ablauf der gespeicherten Fassung: 0 Einträge (Soll 0)
Aufgeräumt, Bestand wieder: 11
```

## AK-11 / EC-01 · Echter Datenbankausfall

Server neu gestartet mit `DATABASE_URL` auf eine nicht vorhandene Datenbank.

```
AK-11 · Datenbank weg, keine gespeicherte Fassung
  Abruf 1 → HTTP 500 · <url>-Einträge im Rumpf: 0 · beginnt mit <?xml: 0
  Abruf 2 → HTTP 500 · <url>-Einträge im Rumpf: 0 · beginnt mit <?xml: 0
  gespeicherte Fassung danach vorhanden: 0
  /health zugleich: 200 (Prozess lebt, Datenbank nicht)

EC-01 · Datenbank weg, gespeicherte Fassung vorhanden
  mit Datenbank: 128 Einträge gespeichert
  ohne Datenbank → HTTP 200 · 128 Einträge · identisch mit der gespeicherten: ja
  eine gewöhnliche Seite ohne Datenbank: /de/restaurants → 500
```

## Angriffe

```
Eingaben an /sitemap.xml
  GET   /sitemap.xml?x=%3Cscript%3Ealert(1)%3C/script%3E  → 200 · Rumpf identisch
  GET   /sitemap.xml?page=2                               → 200 · Rumpf identisch
  POST  /sitemap.xml                                      → 405
  HEAD  /sitemap.xml                                      → 200
  GET   /sitemap.xml/                                     → 301
  GET   /sitemap.XML                                      → 404
  GET   /lb/sitemap.xml                                   → 404
  GET   /sitemap.xml%00.txt                               → 404
  GET   /sitemap.xml mit Host: evil.example               → 400

Eingaben an den canonical-Verweis
  /de/restaurants?page=99999999999999999999  → 400 · canonical: —
  /de/restaurants?page=2%0A                  → 200 · canonical: https://endlech.lu/de/restaurants
  /de/restaurants?page%5B%5D=2               → 400 · canonical: —
  /de/restaurants?page=+2                    → 200 · canonical: https://endlech.lu/de/restaurants
  /de/restaurants?page=02                    → 400 · canonical: —
  /de/about?_locale=//evil.example           → 200 · canonical: https://endlech.lu/de/about · hreflang fr: https://endlech.lu/fr/about

BF-147 · Seitenzahl auf Seiten, die nicht blättern
  /de/about?page=2               → 200 · canonical https://endlech.lu/de/about?page=2
  /de/restaurants/1?page=7       → 200 · canonical https://endlech.lu/de/restaurants/1?page=7
  /lb/vergleich/wheelmap?page=3  → 200 · canonical https://endlech.lu/lb/vergleich/wheelmap?page=3
  /de/presse?page=12             → 200 · canonical https://endlech.lu/de/presse?page=12
  /de/community/ideen?page=99    → 200 · canonical https://endlech.lu/de/community/ideen?page=99   (leere Seite)
  Inhaltlicher Unterschied /de/about ↔ /de/about?page=2 (ohne Kopfverweise): 6 Zeilen, nur die
  Links des Sprachumschalters, der die Abfrage absichtlich weiterträgt (BF-68).

BF-148 · EC-05
  /de/restaurants?page=3   → 404 · canonical: —
  /de/restaurants?page=99  → 404 · canonical: —

robots-Sperre ist keine Zugriffsregel
  /de/admin → 302 · Location …/de/login · /de/profile → 302 · /api/v1/me ohne Token → 401
```
