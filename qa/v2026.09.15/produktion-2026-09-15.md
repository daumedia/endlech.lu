# v2026.09.15 · Deploy-Nachprüfung auf der Produktion (2026-09-15)

Release `v2026.09.15`, `master` auf `b80e82e`, in Coolify ausgerollt (Anwendung, keine Migration, Worker
unberührt). Enthält #133 (Portugiesisch), #134 (Fußzeile), #135 („Konto eröffnen", Markenblau,
Build-Reparatur) und #136 (Vorschlagen für Gäste).

⚠ **Auf der Produktion wurde nichts angelegt.** Der einzige schreibende Aufruf ist ein POST **ohne Daten und
ohne Konto** auf `/de/community/suggest`; er endet vor dem Formular an der Zugriffsprüfung.
⚠ **Das Zählskript war im Browserlauf blockiert** (`zaehler.js`, `/api/send`), damit die Prüfung nicht in
Umami erscheint. Die curl-Abrufe laden es ohnehin nicht.

## Erreichbarkeit und Auslieferung (curl)

| Prüfung | Ergebnis |
|---|---|
| Fußzeile | `v2026.09.15` |
| `/pt/`, `/pt/restaurants`, `/pt/presse`, `/de/community/suggest`, `/de/changelog`, `/sitemap.xml`, `/robots.txt`, `/health` | alle 200 |
| `/pt/` | `lang="pt"`, Überschrift „Encontre restaurantes acessíveis no Luxemburgo" |
| Stylesheet | live `app.827fbd92.css` = `entrypoints.json` auf `main`, **byte-identisch** |
| `sw.js` | `CACHE_VERSION = 'endlech-v4'` |
| Sitemap | 150 Einträge, je Sprache 30 (lb, de, fr, en, pt) |
| `robots.txt` | `Disallow: /pt/admin`, `/pt/profile`, `/pt/api/` |
| Gast-POST `/de/community/suggest` | 302 → `/de/login` |
| `/pt/restaurants/99999999` | 404 |
| Presse-Paket | 200; Nutzungsbedingungen nennen `#01b6ed` fünfmal, Abschnitt `PT —` vorhanden |
| Wort-Bildmarke | Schriftzug `fill="#01b6ed"` |
| Gastseite | „So läuft ein Vorschlag ab" und „Dafür brauchst du ein kostenloses Konto" im HTML |
| Changelog | oben „Endlech.lu gibt es jetzt auch auf Portugiesisch" |

## Im Browser (`produktion-browser.mjs`, Chromium)

Je Seite: HTTP-Status, `lang`, waagerechtes Scrollen, Registrierungsknopf einzeilig, Inter geladen, keine
JS-Ausnahme und kein Konsolenfehler.

```
✅ /pt/ @1280 · 200 · lang=pt · Scroll 0 · Knopf „Criar conta" · zwei Spalten null · Fehler 0
✅ /pt/ @390 · 200 · lang=pt · Scroll 0 · Knopf „Criar conta" · zwei Spalten null · Fehler 0
✅ /de/community/suggest @1280 · 200 · lang=de · Scroll 0 · Knopf „Konto eröffnen" · zwei Spalten true · Fehler 0
✅ /de/community/suggest @390 · 200 · lang=de · Scroll 0 · Knopf „Konto eröffnen" · zwei Spalten false · Fehler 0
✅ /fr/ @1024 · 200 · lang=fr · Scroll 0 · Knopf „S'inscrire" · zwei Spalten null · Fehler 0
✅ /de/about @1440 · 200 · lang=de · Scroll 0 · Knopf „Konto eröffnen" · zwei Spalten null · Fehler 0
   Fußzeile: Kontakt, Entdecken, Mitmachen, Über Endlech, Vergleiche
✅ /lb/restaurants @390 · 200 · lang=lb · Scroll 0 · Knopf „Kont opmaachen" · zwei Spalten null · Fehler 0

7 von 7 bestanden
```

„zwei Spalten" misst nur Seiten mit genau zwei `main section` (die Gastseite): nebeneinander bei 1280 px,
gestapelt bei 390 px. `null` heißt „nicht zutreffend".

## Nicht von außen geprüft

- **Rückkehr nach der Anmeldung** auf die Vorschlagsseite braucht ein Konto auf der Produktion. Belegt durch
  `CommunityControllerTest::testNachDerAnmeldungGehtEsBeimVorschlagWeiter`.
- **Übersetzungsqualität** der Rechtstexte auf Portugiesisch (`legal.*`, `accessibility_statement.*`): vom
  Betreiber gegenzulesen.

## Offen

- **Violett in den Markenfarben:** `material.allowed_3` nennt `#7c3aed`, das „.lu" in Wort-Bildmarke und
  Kopfzeile ist `#9333ea` — dieselbe Frage wie OF-11 aus Feature 05, noch nicht entschieden.
- **Keine Sprachwahl nach Browsersprache:** `/` und die Kurzlinks leiten fest auf `/lb/` (für jede Sprache,
  beim Release nachgemessen). Kein Fehler dieses Releases; eine Weiterleitung nach `Accept-Language` wäre
  eine eigene Entscheidung.
