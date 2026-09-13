# Feature 10 · Nachprüfung auf der Produktion (2026-09-13)

Release `v2026.09.13`, `master` auf `bf0def0`, in Coolify ausgerollt (Anwendung). Fußzeile zeigt
`v2026.09.13`. Stand **vor** dem Ausrollen, gemessen: `/robots.txt` 404, `/sitemap.xml` 404,
`/de/about` ohne canonical-Verweis, Fußzeile `v2026.09.12.3`.

Werkzeuge unverändert aus `qa/10/`, über einen Aufruf mit HTTPS gegen `https://endlech.lu`.
Bestand laut `/open.json`: **3 Restaurants** (`platform.restaurants`) — unabhängig von der Sitemap.

## AK-01 · Schema der ausgelieferten Datei

```
AK-01 Schema (mit Sprachverweisen): gültig
Gegenprobe sitemap.xsd allein: ungültig (erwartet)
Kopfzeilen: HTTP/2 200 · content-type: application/xml; charset=utf-8 · cache-control: max-age=600, public
```

## Crawler-Prüfung (`qa/10/http-pruefung.py https://endlech.lu 3`)

```
✅ EC-06  1. Abruf nach leerem Speicher mit Host www.endlech.lu → 200; www im XML: False; folgender gewöhnlicher Abruf identisch: True
✅ AK-01  GET /sitemap.xml → 200, Content-Type application/xml; charset=utf-8 (Schema separat mit PHP)
✅ AK-02  96 <url>-Einträge, erwartet (21 + 3) × 4 = 96
✅ AK-02  feste Einträge deckungsgleich mit der Spec-Tabelle: 84 von 84
✅ AK-07  kein lastmod, priority, changefreq im ausgelieferten XML
✅ AK-03  96 Adressen, davon mit falschem Anfang oder '?': 0
✅ AK-05  Einträge ohne vollständige, sich selbst einschließende Sprachverweise: 0
✅ AK-06  Adressen aus ausgeschlossenen Bereichen: 0
✅ AK-22  96 Adressen (loc + hreflang), mit '@' oder Token-Muster: 0
✅ AK-04  96 Adressen einzeln abgerufen, ohne Weiterleitung zu folgen · nicht 200: 0 []
✅ AK-12  canonical ≠ Sitemap-Adresse: 0 []
✅ AK-14  hreflang der Seite ≠ Sitemap-Eintrag: 0 []
✅ AK-16  Sitemap-Seiten mit X-Robots-Tag oder noindex-Meta: 0
✅ AK-13  /de/restaurants?sort=name → 200, canonical ['https://endlech.lu/de/restaurants']
✅ AK-13  /de/restaurants?wheelchair=1&city=Esch → 200, canonical ['https://endlech.lu/de/restaurants']
❌ AK-13  /de/restaurants?page=2&sort=name → 404, canonical []
✅ AK-13  /de/restaurants?page=1 → 200, canonical ['https://endlech.lu/de/restaurants']
✅ AK-13  /de/restaurants?page=0 → 200, canonical ['https://endlech.lu/de/restaurants']
✅ AK-13  /de/restaurants?page=abc → 400, canonical [] (Spec-Zeile: https://endlech.lu/de/restaurants · OF-06)
✅ AK-15  16 Wege × 4 Sprachen = 64 Abrufe · Statuscodes {200: 20, 302: 20, 404: 24} · ohne 'X-Robots-Tag: noindex': 0 []
✅ AK-17  GET /robots.txt → 200, text/plain; charset=utf-8, Sitemap-Zeile vorhanden, ausgeliefert = Repository: True
✅ AK-18  22 Pfade aus Verwaltung/Profil/Schnittstelle, davon erlaubt: 0
✅ AK-19  99 Pfade (alle Sitemap-Adressen + offene Daten), davon gesperrt: 0
✅ AK-20  64 Ausschlusswege, davon gesperrt: 0
✅ AK-21  Regeln: 13, keine Sperre der ganzen Seite

24 von 25 Prüfungen bestanden
```

⚠ **AK-13, Zeile `?page=2&sort=name` → 404:** In Produktion gibt es bei drei Restaurants und sechs je
Seite **keine Seite 2**; die Liste weist Seitenzahlen jenseits der letzten Seite ab (BF-148). Keine
Abweichung der Anwendung, sondern nicht belegbar mit diesem Bestand — lokal mit 11 Restaurants
bestanden (`qa/10/http-pruefung.ausgabe.txt`). ⚠ EC-06: Der Speicher war beim ersten Abruf mit
`www` nicht nachweislich leer (ein `curl` kam davor); belegt ist, dass weder dieser noch der folgende
Abruf `www` enthält und beide identisch sind.

## BF-147 (`qa/10/bf147-nachpruefung.py https://endlech.lu`)

```
Sitemap: 96 Einträge
Abrufe: 288 Sitemap-Seiten mit Abfrage (264 nicht blätternd, 12 blätternd mit Seite), 20 Ausschlusswege
Beobachtung · /de/restaurants?page=3 → 404, canonical []
Beobachtung · /de/restaurants?page=99 → 404, canonical []
Beobachtung · /de/community/ideen?page=2 → 200, canonical ['https://endlech.lu/de/community/ideen?page=2']
Beobachtung · /de/community/ideen?page=99 → 200, canonical ['https://endlech.lu/de/community/ideen?page=99']

✅ BF-147: keine Abweichung
```

## AK-23 · Deckel

Vor der Schleife **6** Sitemap-Abrufe von dieser Adresse (1 × `curl`, 2 × abgebrochener erster Lauf,
2 × Crawler-Prüfung, 1 × BF-147-Nachprüfung), alle 200.

```
erster 429 bei Abruf Nr. 61 (insgesamt, davon 6 vor dieser Schleife)
retry-after: 3308
Statuscodes der Schleife: 54 × 200, 1 × 429 — kein Set-Cookie auf der 429
/open/dataset.json danach: 200
/de/about danach: 200
```

## Übrige Nachprüfung

```
GET /de/login → 200 · Formularfelder _username, _password, _assertion: 3 · X-Robots-Tag: noindex · canonical: 0
/de/about → X-Robots-Tag: keine
/health → 200
Testdaten (Fixture-Namen Bella Vista, Sushi Zen, Green Bowl, Burger & Co, Le Jardin, Trattoria Roma,
Brasserie du Grund, QA10, admin@/user@endlech.lu) in Restaurantliste und Sitemap: 0
```

Die Anmeldung selbst ist nicht durchgeführt — sie braucht ein echtes Konto; geprüft ist, dass die
Seite mit beiden Anmeldewegen ausgeliefert wird. Das Feature berührt die Anmeldung nicht.

**Nicht prüfbar bis zur Search Console (Betreiber):** AK-24, AK-25 — T17, T18.
