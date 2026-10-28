# 15 · Sponsoren — Testbericht

Stand: 2026-10-28 · Geprüft gegen `spec.md` vom 2026-10-28 (mit den Klarstellungen zu OF-01 bis OF-03) · in eigener
Session, ohne Bauverlauf · Stand des Baus: uncommittet auf `feature/15-sponsoren` (HEAD = `main`, `ef55a94`)

**Wie geprüft wurde.** Funktionstests laufen gegen `endlech_test`. Für alles Sichtbare lief der Entwicklungsserver
(`symfony server:start --no-tls --port=8000`) gegen eine **eigens angelegte Wegwerf-Datenbank `endlech_qa15`**
(Migrationen + Fixtures, über `DATABASE_URL` in der Umgebung des Servers; eine Datenbank `endlech` gibt es lokal nicht,
`endlech_test` blieb unberührt). In `SponsorRegistry` standen dafür vorübergehend 17 Probe-Einträge mit Logos aus GD
(Abschrift `qa/15/probe-registry.php.txt`): 13 aktive (davon drei mit demselben Beginn, einer mit Pause und Wiederkehr,
einer mit „Ëmil & Fils S.à r.l.“, ein sehr breites 8 : 1-Logo, ein dunkles Logo auf transparentem Grund), zwei
ehemalige, zwei künftige, ein entfernter Slug. Browser: Vorschau-Chrome (1280 × 900, iPhone 12 Pro 390 × 844, 320 und
660 px). Die Produktionsverdrahtung (`app.sponsor_clock` → `clock`, echte Registry) wurde mit einem Kernel `prod`/
`debug=false` und eigenem Cache-Verzeichnis unter `/tmp` gerendert, `var/cache/prod` blieb unberührt. Nachher:
Registry byte-gleich zurück (SHA-256 `642605ee…`), Logo-Verzeichnis leer, Server gestoppt, Entwicklungs-Cache geleert,
die in dieser Session entstandenen Zeilen in `var/log/dev.log` und `var/log/test.log` wieder abgeschnitten. **Die
Wegwerf-Datenbank `endlech_qa15` steht noch** — das Löschen hat die Projekt-Leitplanke zu Recht dem Menschen
vorbehalten (Befehl unter „Nächster Schritt“).

> **Nachprüfung der Überarbeitung (AK-49 bis AK-55, EC-13, EC-14) am Ende dieses Berichts** — 6 von 7 neuen
> Kriterien bestanden (AK-51 durchgefallen), EC-13 durchgefallen, EC-14 bestanden; neu BF-196 (*mittel*), BF-197 und BF-198 (*niedrig*); Production-ready weiterhin ja, mit
> Vorbehalten.

## Fazit

**Production-ready: ja — mit Vorbehalten, die vor `/sdd-deploy 15` gelesen werden müssen.**

Der Kern trägt. Footer, Übersicht, Sponsorenseiten, Faktenblatt, Unternehmensseite, `/open` und Sitemap lesen dieselbe
Auswahl, in allen fünf Sprachen, auf allen 235 Seiten der Sitemap; aktiv, ehemalig und unsichtbar wechseln nach
Luxemburger Kalendertag, auch mit einer Serveruhr in UTC und in der Nacht der Herbstumstellung. Restaurantliste,
Detailseiten, Startseite, Datensatz und API sind mit zwölf aktiven Sponsoren und ohne Sponsor **byte-gleich** bis auf
den Footer (47 Abrufe). Kein Betrag, keine Laufzeitart bei einem Sponsor, kein altes Format auf einer öffentlichen
Seite, keine Datei von fremder Adresse, kein Zählaufruf am Website-Link, die Zielseite erfährt nur den Ursprung.
Das Formular nimmt nur „monatlich“, „jährlich“ oder nichts; alles andere endet in 422 ohne Zeile und ohne Mail. Der
Prüflauf über die Produktionsquelle fängt 19 absichtlich falsche Angaben und nennt jede mit Sponsor und Angabe.

Kein Befund ist *kritisch* oder *hoch*. Acht neue, alle nachgestellt:

- **BF-188 (mittel)** — Ein Slug unter `removedSlugs()`, dessen Eintrag noch steht, erscheint weiter in Footer,
  Übersicht, Sitemap, Faktenblatt-Zahl und Logoreihe; nur die eigene Seite leitet um — auf die Übersicht, die ihn wieder
  nennt. Der Entwurf sagt „ein entfernter Sponsor ist weder aktiv noch ehemalig“, `SponsorDirectory` filtert ihn nicht.
  Es fängt allein `SponsorRegistryTest` — und PHPUnit ist kein Deploy-Gate, die Anleitung „Entfernen“ nennt den
  Prüflauf nicht.
- **BF-190 (mittel, Entscheidung)** — **AK-10 durchgefallen** auf kurzen Seiten: Liegt der Footer innerhalb von Chromes
  Vorlaufzone (gemessen: 761 px unter dem Sichtbereich), lädt Chrome alle zwölf Logos, ohne dass gescrollt wird. Auf
  Startseite (5400 px) und Restaurantliste (4602 px) und auch auf den Sponsorenseiten (1418 px) lädt keins. Der Entwurf
  hat das vorhergesagt (Entscheidung #6), lösbar nur mit JavaScript-Nachladen, das EC-09 bräche — gehört in
  `/sdd-klaeren 15`.
- BF-189 (niedrig) — Die Logodatei eines entfernten Sponsors bleibt mit 200 ausgeliefert, wenn sie liegen bleibt; kein
  Prüflauf merkt es (AK-43 verlangt 404).
- BF-191 bis BF-195 (niedrig) — deutsche Anführungszeichen um das Zitat auf allen Sprachfassungen; ungleich schrumpfende
  Logokacheln auf der Übersicht (102–123 statt 144 px); „Sie sponsern Endlech.lu bereits“ liest sich als Anrede; der
  tragende ⚠-Kommentar zu `app.usage.umami_client` (Feature 11) steht seit dem Bau über dem Sponsorenblock; die interne
  Meldung führt für jede neue Anfrage eine leere Zeile „Sponsoring (Frühere Angabe (vor dem Paket))“.

**Die Vorbehalte:** AK-10 braucht eine Entscheidung (BF-190). AK-26 (zehn Minuten einschließlich Ausrollen), AK-47 (in
Umami gezählt) und AK-48 (Umami-Abfrage) sind aus der lokalen Umgebung nicht prüfbar. BF-188 betrifft die Zusage
„Entfernung auf Wunsch binnen 30 Tagen“ — wer vor der Reparatur einen Sponsor entfernt, löscht Eintrag **und** Logo und
lässt `php bin/phpunit tests/Unit/Sponsor` laufen.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 45 von 48 |
| davon bestanden | 44 |
| davon durchgefallen | 1 (AK-10) |
| **nicht prüfbar** | 3 (AK-26, AK-47, AK-48) |
| Edge Cases belegt | 12 von 12 |
| Tests neu geschrieben | 23 in drei Dateien (davon 3 Reproduktionen, übersprungen solange offen) |
| Tests grün | 20 von 20 (die 3 Reproduktionen scharf geschaltet: 3 rot, wie erwartet) |
| Volle Suite gelaufen | **1753 Tests, 0 Fehlschläge**, 10 162 Zusicherungen, 19 übersprungen (16 wie vor dem Bau, 3 Reproduktionen) |
| Regression: berührte Features | 9, davon Hauptweg bestanden 9 |

## Akzeptanzkriterien im Einzelnen

„Server“ heißt: Entwicklungsserver gegen `endlech_qa15` mit den Probe-Einträgen, Uhr = Systemuhr (2026-10-28).

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Server: alle **235** Seiten der Sitemap (21 feste + 11 Restaurants + 15 Sponsoren, × 5 Sprachen — darin Startseite, Liste, alle Detailseiten, `/open`, `/legal`) tragen den Bereich mit Überschrift in Seitensprache (Sponsoren · Sponsors · Patrocinadores) und **dieselben 12 Logos in derselben Reihenfolge** (235/235). Dazu `FooterSponsorsTest::testAk01…` (9 Fälle) |
| AK-02 | ✅ bestanden | Server: Alpha, Ëmil & Fils, Sponsor 03 (alle Beginn 2026-01-01) alphabetisch vorn, dann nach Beginn; alle Kacheln **144 × 56 px** (390 und 320 px gemessen); 8 : 1- und Fast-quadrat-Logo unverzerrt in derselben Fläche, `qa/15/AK-02-AK-29-footer-12-logos-390px.png`; `SponsorDirectoryTest::testAk02…` |
| AK-03 | ✅ bestanden | Browser: Klick auf das Logo „Ëmil & Fils“ im Footer von `/fr/` → `/fr/sponsoren/emil-fils`, gleicher Tab, `<html lang="fr">`; kein `target` am Link; `FooterSponsorsTest::testAk03…` |
| AK-04 | ✅ bestanden | DOM: jeder Logo-Link enthält nur das Bild, `alt` = Name (12/12, „Ëmil &amp; Fils S.à r.l.“ einfach maskiert) — der zugängliche Name des Links. Browser: Tab vom letzten Link davor („Alle Vergleiche“) landet auf dem ersten Logo; Regel `focus:outline-2 focus:outline-offset-2 focus:outline-cyan-400` im ausgelieferten Stylesheet gefunden (das Vorschaufenster hat keinen Systemfokus, `:focus` greift dort nicht — der Rahmen selbst ist deshalb nicht im Bild). Kachel 144 × 56 px bei 390 **und** 320 px |
| AK-05 | ✅ bestanden | Server: mit aktiven und ehemaligen beide Links (`/de/sponsoren`, `/de/organisationen/unternehmen#anfrage`); `FooterSponsorsTest::testAk05NurEhemaligeZeigenAlleSponsorenOhneLogos` für den Fall „nur ehemalige“ |
| AK-06 | ✅ bestanden | Server: 13 aktive → Footer zeigt 12, der dreizehnte (Sponsor 12, Beginn 2026-10-01) steht nur auf `/sponsoren`; `FooterSponsorsTest::testAk06…` |
| AK-07 | ✅ bestanden | Server, Registry leer, fünf Sprachen: Bereich enthält nur „Sponsor werden →“ (bzw. „Sponsor ginn →“, „Devenir sponsor →“ …), 0 Bilder, kein Platzhaltertext |
| AK-08 | ✅ bestanden | Server: **47 Abrufe** (Startseite de/fr, Liste in drei Sortierungen, Seite 2, zwölf Filterkombinationen, drei Küchenfilter, alle 11 Detailseiten, `/api/v1/restaurants` Liste + 11 Details, `/open.json`, `/open/dataset.csv`, `.json`) mit 12 aktiven Sponsoren und mit leerer Registry verglichen: **47 von 47 identisch** nach Entfernen des Footer-Bereichs |
| AK-09 | ✅ bestanden | Server: `<main>` aller 11 Detailseiten ohne Sponsorname, -link oder -logo; dazu der AK-08-Vergleich |
| AK-10 | ❌ durchgefallen | Gewicht ✅: im Browser nach dem Scrollen 12 Logos, zusammen 102 502 Bytes, das größte 12 673; Prüflauf schlägt bei 151 KB an (`qa/15/AK-10-prueflauf-zwoelf-logos-151kb.txt`). Laden ✅ auf Startseite und Restaurantliste bei 390 px mit kaltem Cache: 0 von 12 geladen. **❌ auf kurzen Seiten:** `/de/organisationen/confirmation/…` (Footer 761 px unter dem Sichtbereich) lädt alle 12 ohne Scrollen — siehe BUG-03 (BF-190) |
| AK-11 | ✅ bestanden | Server, fünf Sprachen: Paket (monatlich oder jährlich, Preis auf Anfrage), „bekommt“ (Footer, eigene Seite), vier Punkte „nicht bekommt“, Ausschlussregel, Verwendung mit Link `/xx/open#finance-heading`, „Sponsor werden →“; `SponsorIndexTest` |
| AK-12 | ✅ bestanden | Server: alle 13 aktiven (auch über zwölf) mit Logo, Name, „Sponsor seit <Monat Jahr>“ in Seitensprache, Reihenfolge wie im Footer, je verlinkt |
| AK-13 | ✅ bestanden | Server, Registry leer, fünf Sprachen: „Endlech.lu hat derzeit keine Sponsoren.“ (lb/fr/en/pt entsprechend), keine `<ul>` im Abschnitt, kein Abschnitt „Bisher unterstützt von“, darunter die Inhalte aus AK-11 |
| AK-14 | ✅ bestanden | Server, fünf Sprachen, Footer, Übersicht, sechs Sponsorenseiten, Unternehmensseite, Faktenblatt: kein Eurobetrag; „monatlich/jährlich“ (und Entsprechungen) nur im allgemeinen Paketsatz und in den Formularantworten, nie bei einem Sponsor. Neu: `Qa15SponsorSeitenTest::testAk14…` (Übersichtskarten und Logoreihe, fünf Sprachen — gegengeprüft: rot mit „(monatlich)“ in der Karte); `SponsorIntegrityTest::testAk14…` |
| AK-15 | ✅ bestanden | Server: „Bisher unterstützt von“ mit Ehemals GmbH (Ende 2026-10-27) vor Früh SA (Ende 2025-06-30), Zeiträume, verlinkt, unter den aktiven fehlend |
| AK-16 | ✅ bestanden | Server, fünf Sprachen: Logo, Name als `h1`, „Sponsor seit Januar 2026“ bzw. „Sponsor Januar 2024 – Dezember 2024 und seit Juni 2026“, Zitat, Verwendung mit Link `/open`, „Was <Name> dafür nicht bekommt“ mit vier Punkten, Website-Link, „Sponsor werden →“, „Alle Sponsoren“ |
| AK-17 | ✅ bestanden | Server: `href="https://example.com/alpha?x=1"` — genau der Eintrag samt Abfrage, nichts angehängt; `rel="sponsored noopener"`; Attribute nur `href`, `rel`, `class` |
| AK-18 | ✅ bestanden | Server, fünf Sprachen: `<blockquote lang="fr">` mit unverändertem Zitat, `<html lang>` = Seitensprache. Neu: `Qa15SponsorSeitenTest::testAk18…` (fünf Fälle; gegengeprüft: rot ohne `lang`). Anführungszeichen: BUG-04 |
| AK-19 | ✅ bestanden | Server: `figure` / `blockquote` / `figcaption` „— Alpha S.à r.l.“; Prüflauf: 301 Zeichen rot, 300 Zeichen grün (`qa/15/AK-27-AK-28-…txt`) |
| AK-20 | ✅ bestanden | Server: Seite „Ëmil & Fils“ (ohne Zitat und Website) ohne Zitatblock und ohne Website-Link; `SponsorShowTest::testAk20…` |
| AK-21 | ✅ bestanden | Server: Ehemals GmbH (Website eingetragen) → 200, „Ehemaliger Sponsor · Januar 2025 – Oktober 2026“, **kein** Website-Link; nicht im Footer, nicht unter den aktiven |
| AK-22 | ✅ bestanden | Server: unbekannt, `ALPHA-SARL`, `alpha_sarl`, `alpha-sarl-`, 10 000 Zeichen, `..%2F..%2Fetc%2Fpasswd`, `<script>`, SQL, Emoji → je **404**; Produktionskernel: `/de/sponsoren/gibt-es-nicht` → 404 über dieselbe `NotFoundHttpException` wie jede andere 404 |
| AK-23 | ✅ bestanden | Server: zwei künftige Sponsoren (Beginn morgen bzw. 2026-11-01) fehlen in Footer, Übersicht, Faktenblatt (Zahl 13), Logoreihe, Sitemap; Adresse **404**. Ab dem Beginn: `SponsorDirectoryTest::testAk23…` (Uhr auf 00:00 des Starttags), keine Zwischenspeicherung außer der Sitemap (≤ 60 Min., `SitemapControllerTest`) |
| AK-24 | ✅ bestanden | Server: Ehemals GmbH, Ende gestern, steht heute unter „Bisher unterstützt von“, nicht im Footer. `SponsorDirectoryTest::testAk24…` (23:59 / 00:00 / 00:30 Luxemburg); neu `Qa15SponsorTest::testAk24Ec02…` mit **Serveruhr in UTC** (21:59:59/22:00 UTC im Sommer, 22:59:59/23:00 UTC im Winter) |
| AK-25 | ✅ bestanden | Server: „Pause & Wiederkehr SA“ (2024, dann seit 2026-06-15) zwischen Sponsor 08 (06-01) und 09 (07-01); Seite nennt beide Zeiträume in Reihenfolge |
| AK-26 | ⚠️ nicht prüfbar | Das Ausrollen in Coolify ist lokal nicht messbar. Belegt ist der Rest: ein neuer Eintrag plus Logodatei, sonst nichts angefasst — Footer-Regel, Übersicht, Faktenblatt (14 aktive), eigene Seite in fünf Sprachen sofort da, `tests/Unit/Sponsor` grün (44 Tests); kein Katalog, keine Sitemap, kein Prüflauf geändert |
| AK-27 | ✅ bestanden | Prüflauf über die Produktionsquelle mit 21 absichtlich falschen Einträgen: **19 Verstöße, jeder mit Sponsor und Angabe** — fehlender Name, Logo, Beginn, leerer Beginn, Ende vor Beginn, Überlappung, doppelte Adresse, Zitat 301 Zeichen, Zitat ohne Sprache, Logodatei fehlt, SVG als PNG, 30 KB, Großbuchstaben, `.svg`, `http://`, `javascript:`, Name 81 Zeichen, Slug zugleich entfernt, 30. Februar; der 300-Zeichen-Eintrag bleibt gültig (`qa/15/AK-27-AK-28-prueflauf-fehlerhafte-eintraege.txt`) |
| AK-28 | ✅ bestanden | Prüflauf weist echte SVG und SVG mit `.png`-Namen ab (siehe AK-27). Browser: Logo direkt aufgerufen → `document.contentType = image/webp` bzw. `Content-Type: image/png`, 0 Skripte, 0 weitere Ladevorgänge |
| AK-29 | ✅ bestanden | Screenshot `qa/15/AK-02-AK-29-…png`: das schwarze „ALPHA“-Logo auf transparentem Grund steht lesbar auf der weißen Kachel |
| AK-30 | ✅ bestanden | Server, fünf Sprachen: „Wer uns finanziert →“ (`/xx/sponsoren`) im Block Finanzen, während die Quartalssperre greift („Einnahmen — Noch nicht veröffentlicht“); kein Sponsorname im Finanzblock |
| AK-31 | ✅ bestanden | Server, fünf Sprachen: Logoreihe über dem Formular = Footer-Auswahl, gleiche Reihenfolge, verlinkt; Registry leer → keine Reihe |
| AK-32 | ✅ bestanden | Server: „13 aktive Sponsoren · Alle Sponsoren“ (fünf Sprachen), leer „Derzeit keine · Alle Sponsoren“ (lb „De Moment keng“, fr „Aucun actuellement“, en „None at present“, pt „Nenhum de momento“) |
| AK-33 | ✅ bestanden | Server, fünf Sprachen: ein Paket, monatlich oder jährlich, Preis auf Anfrage, Gegenleistung Footer + eigene Seite, Link `/xx/sponsoren` |
| AK-34 | ✅ bestanden | Server: alle 235 Sitemap-Seiten gegen die Wortlaute der vier Formate, sechs alten Interessen, des alten Formularetiketts und des alten Auswahlsatzes **aus den Katalogen von `main`** durchsucht — 0 Treffer; „wird als Sponsor genannt“ (de); `SponsorIntegrityTest::testAk34…` |
| AK-35 | ✅ bestanden | Kataloge: `no_money_*`, `collaboration.*` in fünf Sprachen gegenüber `main` unverändert (0 Abweichungen); Template-Diff ersetzt nur das Formatraster |
| AK-36 | ✅ bestanden | Kataloge: `exclusion_*` in fünf Sprachen unverändert; Server: Regel steht auf der Seite |
| AK-37 | ✅ bestanden | Server: genau zwei Radios (`monthly`, `yearly`), keins vorgewählt, keine dritte Antwort; ohne JavaScript (curl) abgeschickt: „monatlich“ → 302, `["monthly"]`; leer → 302, `[]` |
| AK-38 | ✅ bestanden | Server: `inclusion_boxes`, Liste `[monthly, yearly]`, altes Feld `sponsorshipInterests[]` → je **422**, 0 Zeilen, Mail-Warteschlange unverändert (2 vor, 2 nach). Gültig → interne Meldung „Sponsoring: Ja, monatlich“ / „Ja, jährlich“ (aus der Warteschlange gerendert), Verwaltung zeigt „Ja, monatlich“ |
| AK-39 | ✅ bestanden | Browser als Admin: Eintrag mit `["inclusion_boxes","employee_engagement","other"]` → 200, „Inclusion-Boxen finanzieren · Frühere Angabe (vor dem Paket)“ usw.; Liste 200; Zeile danach unverändert |
| AK-40 | ✅ bestanden | Server: `/de/sponsoren` und `/de/sponsoren/emil-fils` mit `canonical` auf `https://endlech.lu/…` und sechs `alternate` (fünf Sprachen + `x-default`) |
| AK-41 | ✅ bestanden | Server: Sitemap **235 = (21 + 11 + 15) × 5** Einträge, 15 sichtbare Sponsoren, künftige und entfernte fehlen; gegen `sitemap-mit-sprachverweisen.xsd` gültig (`xmllint`); nach dem Entfernen zweier Sponsoren 225. Frist: `SitemapControllerTest` (3000 + 600 ≤ 3600) |
| AK-42 | ✅ bestanden | Server: auf allen 235 Seiten keine geladene Ressource (`src`, `stylesheet`, `preload`, `icon`, `url()`) von fremder Adresse. Browser: Website-Link auf einen zweiten Ursprung umgelenkt und angeklickt → `document.referrer` dort = `http://127.0.0.1:8000/` (nur Ursprung, ohne Pfad und Abfrage), `window.opener` null; `Referrer-Policy: strict-origin-when-cross-origin` |
| AK-43 | ✅ bestanden | Server, nach der Anleitung (Eintrag und Logo gelöscht, Slug in `removedSlugs()`): `/de/…/alpha-sarl` und `/fr/…` → **301** auf `/xx/sponsoren`; alte Logoadressen → **404**; Name, Logo, Zitat auf Startseite, Liste, Übersicht (de/fr), Faktenblatt (12 statt 13), Unternehmensseite und Sitemap: 0 Treffer. Teilweises Entfernen: BUG-01, BUG-02 |
| AK-44 | ✅ bestanden | `Sponsor` hat kein Feld für eine Person (`SponsorRegistryTest::testAk14Ak44…`); Zitat gezeichnet „— Alpha S.à r.l.“; keine Kontaktperson auf Übersicht, Seiten, Footer |
| AK-45 | ✅ bestanden | Server: Prüfsumme über alle Wartelisten-Zeilen vor und nach dem Entfernen zweier Sponsoren gleich (`31ec63c6…`); umgekehrt Abmeldelink einer Anfrage aufgerufen (Zeile weg, wie B15) → alle Sponsoren unverändert genannt |
| AK-46 | ✅ bestanden | Browser: Zählskript aktiv (Seitenaufruf ging an `/api/send`), Klick auf „Website von Alpha S.à r.l.“ → in 2 s **kein** Aufruf von `fetch`/`sendBeacon`/Ressource |
| AK-47 | ⚠️ nicht prüfbar | Umami ist aus der lokalen Umgebung nicht erreichbar. Belegt: der Seitenaufruf im Browser trägt `url: …/de/sponsoren/alpha-sarl`, keinen Personenbezug (Firmenname im Titel); `SponsorIntegrityTest::testAk47…` (Pfad passiert `CollectPayloadNormalizer`) |
| AK-48 | ⚠️ nicht prüfbar | Plattformeinstellung; T22 beim Betreiber nach dem ersten Sponsor |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | `SponsorDirectoryTest::testEc01EinTagSponsoring`; Prüflauf meldet nur Ende **vor** Beginn (AK-27) |
| EC-02 | ✅ | Frühjahr: `SponsorDirectoryTest::testEc02…`; **Herbst** neu: `Qa15SponsorTest::testAk24Ec02…` (31.10.2027, 21:59:59/22:00 UTC, doppelte Stunde 02:30, Ende 22:59:59/23:00 UTC) |
| EC-03 | ✅ | Server: drei Sponsoren mit Beginn 2026-01-01 in allen fünf Sprachen gleich geordnet (Alpha, Ëmil, Sponsor 03) |
| EC-04 | ✅ | Screenshot: 8 : 1-WebP und fast quadratisches PNG in derselben 144 × 56-Kachel, unverzerrt, nichts ragt heraus |
| EC-05 | ✅ | „Ëmil & Fils S.à r.l.“ richtig in `h1`, `<title>` (`&amp;` einfach), `alt`, Karte, „Was … dafür nicht bekommt“; Adresse nur `[a-z0-9-]` (Route-Requirement, Prüflauf) |
| EC-06 | ✅ | Logo mit neuem Inhalt → neue Adresse: `alpha-ONxhlAP.png` → `alpha-YzbAmxV.png` → `alpha-8zgdKmG.png`; Navigationen laufen im Service Worker network-first |
| EC-07 | ✅ | Server: der dreizehnte weder im Footer noch in der Logoreihe, nur auf `/sponsoren` |
| EC-08 | ✅ | Server: Ehemals GmbH entfernt → fehlt auch unter „Bisher unterstützt von“ |
| EC-09 | ✅ | Alle Prüfungen am Server liefen mit curl (ohne JavaScript): Footer, Übersicht, Seiten vollständig; Formular ohne JavaScript abgeschickt (AK-37) |
| EC-10 | ✅ | Siehe AK-45, zweite Hälfte |
| EC-11 | ✅ | Siehe AK-21 |
| EC-12 | ✅ | Ende zählt mit (`SponsorDirectoryTest::testAk24…`, neu mit UTC-Uhr); Jahrespaket ohne Ende läuft weiter (offener Zeitraum aktiv) |

## Regression

Volle Testsuite: `php bin/phpunit` → **1753 Tests, 0 Fehlschläge**, 10 162 Zusicherungen, 19 übersprungen
(`qa/15/volle-suite.ausgabe.txt`). Vor den neuen QA-Tests: 1730, 0 Fehlschläge, 16 übersprungen — gleich der Zahl im
Abschlussbericht. Der letzte Bericht eines anderen Features (12) lief mit 1550; die Zahl ist gewachsen, nicht
geschrumpft.

Berührt laut `git diff --name-only` (HEAD = `main`, alles uncommittet) außerhalb von `features/15-sponsoren/` und
`src/Sponsor/`: `partials/_footer.html.twig` (jede Seite), `organisation/{_form,_section_company,type}.html.twig`,
`OrganisationWaitlistEntry`, `OrganisationWaitlistType`, `admin/waitlist/organisation_show.html.twig`,
`email/organisation/internal_notification.html.twig`, `open/index.html.twig`, `press/_facts.html.twig`,
`SeoRegistry`, `SitemapGenerator`, `services.yaml`, `asset_mapper.yaml`, zehn Kataloge, sechs Seo- und zwei
Barrierefreiheits-Prüfläufe.

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B15 | Formular, Angebotsseite, Entity, interne Meldung | ✅ | Server: Anfrage Unternehmen → 302 → Bestätigungslink 200 → interne Meldung in der Warteschlange; Deckel greift bei der sechsten (429). US-01 (Gemeinde): `OrganisationControllerTest::testCommuneSubmissionStoresTypeSpecificFields`, `::testConfirmationActivatesEntryAndNotifiesTeam` grün |
| B22 | Verwaltungsansicht der Organisationen | ✅ | Browser als Admin: Liste 200, Einträge mit neuer und alter Angabe 200 |
| B16 | Block Finanzen auf `/open` | ✅ | Server: `/xx/open` 200 in fünf Sprachen, Finanzblock mit Quartalssperre unverändert |
| 05 | Faktenblatt | ✅ | Server: `/xx/presse` 200 in fünf Sprachen; `PressPackageTest`, `PressCatalogueTest` grün |
| 10 | `SeoRegistry`, `SitemapGenerator`, Zählprüfläufe | ✅ | Server: Sitemap 200, gegen das Schema gültig, Deckel greift beim 61. Abruf (429); `SeoRouteCoverageTest`, `RobotsTxtTest`, `Qa10SitemapBestandTest` grün |
| 02 | Footer auf jeder Seite, neue Seiten | ✅ | `HeadingOrderTest`, `AccessibilityStructureTest`, `FooterStructureTest`, `SponsorAccessibilityTest` grün; 320 px ohne waagerechtes Scrollen |
| 11 | neue Seiten werden gezählt | ✅ | Browser: Seitenaufruf der Sponsorenseite geht an `/api/send` mit eigenem Pfad; `CollectControllerTest`, `UsageEventCatalogueTest` grün |
| B13 / B24 | Footer auf jeder Inhaltsseite, fünf Kataloge | ✅ | 235 Seiten 200; `CatalogueCompletenessTest` grün |
| B05 / B06 | Footer unter Liste und Detailseite | ✅ | AK-08: 47 Abrufe identisch |

Weitere Prüfungen: `lint:twig` (139 Dateien) und `lint:yaml` (63 Dateien) grün; `doctrine:schema:validate --skip-sync`
Mapping korrekt — der Abgleich mit der Testdatenbank zeigt Abweichungen (u. a. `cache_items`, Indexnamen), keine davon
an `organisation_waitlist_entry`, also vorbestehend. `lint:container` scheitert an der Webauthn-Aliasdefinition
(`WebauthnCredentialRepository`) — keine Webauthn-Datei im Diff, vorbestehend, wie im Abschlussbericht.

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Kein Datensatz eines Nutzers im Feature. Künftiger Sponsor (dessen Seite das Template rendern könnte) → 404, entfernter → 301, neun manipulierte Adressen → 404; Wartelisten-Detail anonym → 302 auf `/de/login` |
| Rate Limit greift | bestanden | Formular: gültige Anfragen 4 und 5 → 302, **6 → 429**, 7 → 429; ungültige (422) verbrauchen nichts. Sitemap: **429 beim 61. Abruf**. `/de/sponsoren` 100 × 200 — bewusst ohne Deckel (keine Mail, kein Geheimnis, keine Datenbank, Bestand nicht von außen vergrößerbar; Entwurf, Missbrauchsschutz) |
| PII in Logs | bestanden | Kein Logger im Feature-Code (`src/Sponsor`, Controller, Erweiterung). Die Adressen der Probe-Anfragen stehen in `dev.log` nur über `doctrine.DEBUG` (SQL-Parameter, nur Entwicklung, vorbestehend wie in B15) |
| PII an externe Dienste | bestanden | Umami — tatsächlicher Payload im Browser: `{"website":…,"screen":"1920x1080","language":"en-US","title":"Alpha S.à r.l. · Sponsoren · Endlech.lu","hostname":"127.0.0.1","url":"http://127.0.0.1:8000/de/sponsoren/alpha-sarl","referrer":""}`. Brevo — tatsächlicher Payload eines Eintrags mit Laufzeit: `{"email":…,"ext_id":"503","updateEnabled":true,"attributes":{"CONTACT_NAME":"Probe","ORGANISATION":…,"LOCALE":"de","ORIGIN":"COMPANY","FUNNEL_STATUS":"confirmed"}}` — keine Laufzeit (`Qa15BrevoLaufzeitTest`, gegengeprüft) |
| Zugriffsregeln serverseitig | bestanden | Sichtbarkeit entscheidet `SponsorDirectory` vor dem Rendern; Produktionskernel mit echter Registry und Systemuhr: Seiten 200, unbekannt 404. `access_control` unverändert |
| Geheimnisse im Repository | bestanden | Keine Schlüssel nötig, keine im Diff. **Der Paketpreis steht in keiner Datei der Arbeitskopie.** Hinweis H-2 zu lokalen Checkpoint-Refs |
| Eingaben | bestanden | Slug: 10 000 Zeichen, Pfadsprung, `<script>`, SQL, Emoji → 404; `POST /de/sponsoren` → 405. Formular: drei untergeschobene Werte → 422. Zitat mit `<b>…</b> &` → als Text ausgegeben. Prüflauf weist `javascript:`- und `http:`-Websites ab |
| Löschen | bestanden | Sponsoren hängen an keinem Konto. Entfernen auf Wunsch nach Anleitung vollständig (AK-43); Lücken beim teilweisen Entfernen: BUG-01, BUG-02 |

## Fehler

### BUG-01 · Entfernter Slug mit stehengebliebenem Eintrag bleibt überall genannt — mittel (BF-188)

**Betrifft:** AK-43, EC-08 (Randfall: unvollständiges Entfernen)
**Reproduktion:**
1. In `SponsorRegistry` einen aktiven Eintrag stehen lassen und seinen Slug zusätzlich in `removedSlugs()` eintragen
   (am Server: `alpha-sarl`).
2. `/de/sponsoren/alpha-sarl` aufrufen → 301 auf `/de/sponsoren`.
3. Footer der Startseite, `/de/sponsoren`, `/de/organisationen/unternehmen`, `/sitemap.xml`, `/de/presse` ansehen.
**Erwartet:** Der Sponsor fehlt überall („Ein entfernter Sponsor ist weder aktiv noch ehemalig“, `design.md`; „die
Entfernung gewinnt“, EC-08).
**Tatsächlich:** Footer und Logoreihe zeigen das Logo, die Übersicht die Karte, die Sitemap führt ihn in allen fünf Sprachen
(35 Fundstellen samt Sprachverweisen), das Faktenblatt zählt ihn mit (13 statt 12). Der Klick auf das Logo führt per 301 auf die Übersicht, die ihn wieder nennt. Rot wird nur
`SponsorRegistryTest` — PHPUnit ist kein Deploy-Gate (`CLAUDE.md`), die CI läuft nur von Hand, und die Anleitung
„Entfernen“ in `CLAUDE.md` nennt den Prüflauf nicht (bei „Eintragen“ steht er).
Reproduktionen: `Qa15SponsorTest::testBf188…`, `Qa15SponsorSeitenTest::testBf188…` (scharf mit `QA15_REPRO=1`: rot).
**Ort:** `src/Sponsor/SponsorDirectory.php:44`, `:70`, `:104` — `active()`, `former()`, `visible()` lesen
`entries()` ohne `removedSlugs()`; das liest nur `isRemoved()` (`:132`) für den Controller.
**Vorschlag:** Die Einträge einmal um die entfernten Slugs bereinigen und alle Abfragen daraus lesen; die Anleitung
„Entfernen“ um den Prüflauf ergänzen.

### BUG-02 · Liegengebliebene Logodatei eines entfernten Sponsors bleibt ausgeliefert — niedrig (BF-189)

**Betrifft:** AK-43 („seine Logodateien antworten mit 404“)
**Reproduktion:** Eintrag löschen, Slug in `removedSlugs()`, die Logodatei aber unter `assets/images/sponsors/`
liegen lassen. `php bin/phpunit tests/Unit/Sponsor/SponsorRegistryTest.php` → grün; die bisherige Adresse
`/assets/images/sponsors/alpha-YzbAmxV.png` → **200** (gleicher Inhalt, gleiche Prüfsumme, gleiche Adresse).
Reproduktion: `Qa15SponsorTest::testBf189…` (scharf: rot).
**Erwartet:** Ein Prüflauf meldet eine Logodatei ohne Eintrag.
**Tatsächlich:** Nichts meldet sie; AssetMapper veröffentlicht jede Datei im Verzeichnis.
**Ort:** `src/Sponsor/SponsorRules.php:34` — prüft nur „Datei fehlt“, nicht „Datei ohne Eintrag“.
**Vorschlag:** Im Prüflauf über die Produktionsquelle jede Datei des Logo-Verzeichnisses einem Eintrag zuordnen.

### BUG-03 · AK-10: Auf kurzen Seiten laden die Footer-Logos ohne Scrollen — mittel, Entscheidung (BF-190)

**Betrifft:** AK-10, erste Hälfte
**Reproduktion:** Zwölf aktive Sponsoren mit noch nie geladenen Logos (neue Prüfsummen), Chrome 390 × 844 (iPhone 12
Pro, `4g`), `/de/organisationen/confirmation/0000…0000` öffnen, nicht scrollen; `performance.getEntriesByType('resource')`.
**Erwartet:** „noch keine Logodatei geladen“ — für „eine Seite“.
**Tatsächlich:** Der Footer liegt 761 px unter dem Sichtbereich, innerhalb von Chromes Vorlaufzone für `loading="lazy"`;
**alle 12 Logos werden geladen**. Startseite (5400 px), Restaurantliste (4602 px), Sponsorenseiten (1418 px) und `/de/app`
(2640 px): 0 von 12. Das Gewicht bleibt in allen Fällen unter 150 KB.
⚠ **Berichtigung zum Abschlussbericht:** Dort lud Chrome die Logos einer Sponsorenseite bei 1716 px Abstand „sofort“.
Mit kaltem Cache wurde das hier nicht beobachtet (1418 px: 0 von 12). Bilder, die schon im Speicher des Browsers
liegen, zeigt Chrome sofort an, ohne Netzabruf — so sah der Selbsttest wohl seine eigenen, vorher geladenen Logos.
**Ort:** `templates/sponsor/_logo.html.twig:33` (`loading="lazy"`); `design.md`, Entscheidung #6, hat den Fall
vorhergesagt und die Messung auf Startseite und Restaurantliste beschränkt — die Spec nicht.
**Vorschlag:** Keine Reparatur ohne Entscheidung: AK-10 auf die gemessenen Seiten fassen oder hinnehmen; JavaScript-
Nachladen bräche EC-09.

### BUG-04 · Das Zitat steht auf jeder Sprachfassung in deutschen Anführungszeichen — niedrig (BF-191)

**Betrifft:** AK-18 (Hinweis, das Kriterium selbst ist erfüllt)
**Reproduktion:** `/fr/sponsoren/alpha-sarl` (Zitat französisch) und `/en/…` ansehen.
**Erwartet:** Ein unverändertes Zitat, keine Typografie einer dritten Sprache.
**Tatsächlich:** `<p>„Parce que … “</p>` — „ “ auf allen fünf Fassungen, auch um ein französisches Zitat auf `/fr/`.
**Ort:** `templates/sponsor/show.html.twig:53`
**Vorschlag:** Die Anführungszeichen weglassen (`blockquote` trägt die Bedeutung) oder per CSS `quotes` aus `lang`.

### BUG-05 · Logokacheln auf der Übersicht schrumpfen ungleich — niedrig (BF-192)

**Betrifft:** AK-12 (Darstellung), Muster „Ein Element, das schrumpfen darf …“ (BF-107, BF-108)
**Reproduktion:** `/de/sponsoren` mit 13 aktiven bei 320 px und 660 px; Breiten der Kacheln in `#aktive-sponsoren`.
**Erwartet:** gleiche Fläche wie im Footer (144 × 56).
**Tatsächlich:** 102–117 px bei 320 px, 107–123 px bei 660 px, je nach Länge von Name und „Sponsor seit …“
(`qa/15/BF-192-uebersicht-kacheln-660px.png`). Verzerrt wird nichts (`object-contain`); der Footer ist nicht betroffen.
**Ort:** `templates/sponsor/_logo.html.twig:42` (Zweig ohne Link, Kachel ohne `shrink-0`), eingesetzt in
`templates/sponsor/index.html.twig:47` (`flex items-center gap-4`).
**Vorschlag:** `shrink-0` an die Kachel.

### BUG-06 · „Sie sponsern Endlech.lu bereits“ liest sich als Anrede an den Leser — niedrig (BF-193)

**Betrifft:** AK-31 (Text)
**Reproduktion:** `/de/organisationen/unternehmen` mit aktivem Sponsor: Überschrift über der Logoreihe.
**Erwartet:** Dritte Person, wie lb („Si sponseren …“), fr („Ils sponsorisent …“), en („They already sponsor …“), pt.
**Tatsächlich:** Die Seite siezt durchgehend („Was Sie bekommen“); „Sie sponsern Endlech.lu bereits“ spricht damit den
Interessenten an, der gerade noch nicht sponsert.
**Ort:** `translations/messages.de.yaml:1286` (`organisation.company.sponsors_row_title`), festgeschrieben in
`tests/Functional/Sponsor/CompanyPageTest.php`.
**Vorschlag:** etwa „Diese Unternehmen sponsern Endlech.lu bereits“.

### BUG-07 · Der tragende Kommentar zum Umami-Client steht über dem Sponsorenblock — niedrig (BF-194)

**Betrifft:** Feature 11 (Dokumentation im Code), kein Kriterium
**Reproduktion:** `config/services.yaml:193–214` lesen.
**Erwartet:** „⚠ `autoconfigure: false` ist der Punkt …“ direkt über `app.usage.umami_client`.
**Tatsächlich:** Der Sponsorenblock (`SponsorSource`, `app.sponsor_clock`) wurde zwischen Kommentar und Dienst gesetzt;
der Kommentar endet ohne Leerzeile in „# Sponsoren (Feature 15) …“, der Umami-Dienst steht ohne Kopf. Wer den Dienst
anfasst, sieht die Begründung nicht mehr — sie schützt die Adresse des Überwachungs-VPS (Feature 11, AK-24/AK-42).
**Ort:** `config/services.yaml:193`–`209`
**Vorschlag:** Den Sponsorenblock über den Umami-Kommentar oder hinter den Dienst setzen.

### BUG-08 · Interne Meldung mit leerer Zeile „Sponsoring (Frühere Angabe (vor dem Paket))“ — niedrig (BF-195)

**Betrifft:** AK-38/AK-39 (Darstellung)
**Reproduktion:** Unternehmensanfrage mit „monatlich“ abschicken und bestätigen; die Mail an das Team rendern.
**Erwartet:** Laufzeit; frühere Angaben nur, wenn es welche gibt.
**Tatsächlich:** „Sponsoring: Ja, monatlich · Sponsoring (Frühere Angabe (vor dem Paket)): —“ — die zweite Zeile steht in
jeder neuen Meldung und ist dort immer leer (Altzeilen sind längst gemeldet), mit doppelter Klammer.
**Ort:** `templates/email/organisation/internal_notification.html.twig:42`
**Vorschlag:** Die Zeile nur mit Altwerten ausgeben.

## Hinweise (kein Befund)

- **H-1 · Latente Doppelmaskierung.** `show.html.twig:26/37/39` reicht die Ausgabe von `include('_periods')` als
  `%periods%` in `|trans`; das Ergebnis wird erneut maskiert. Heute ohne Wirkung (kein `'`, `&`, `<` in Monatsnamen und
  `sponsor.period.*`, fünf Sprachen geprüft), aber ein Apostroph im Verbindungswort erschiene als `&#039;`.
- **H-2 · Der Paketpreis steht in zwei lokalen Checkpoint-Refs des Werkzeugs T3** (`refs/t3/checkpoints/…`, Spec und
  Index vor dem Bau). Nicht auf GitHub (`git ls-remote origin`: 0 Refs unter `refs/t3`). Wer je `git push --mirror` oder
  `refs/*` pusht, veröffentlicht ihn (Decision Log #7).
- **H-3 · Drei Prüfläufe des Baus sind schwächer als ihr Kriterium** (Muster BF-64/BF-117):
  `SitemapSponsorTest::testAk41KuenftigeUndEntfernteFehlen` prüft einen entfernten Slug ohne Eintrag (wahr unabhängig
  vom Code, BF-188 bliebe grün); `SponsorAccessibilityTest::testAk04JedesLogoHatAlt` läuft mit `each()` ohne
  Mindestzahl; `SponsorShowTest::testAk18…` fällt bei fehlendem `lang` auf `'de'` zurück und prüft nur `/de/`. AK-18
  und AK-14 sind mit `Qa15SponsorSeitenTest` nachgezogen.
- **H-4** · Der Footer-Bereich erscheint auch im Verwaltungsbereich (laut Abschlussbericht gewollt, unschädlich).
- **H-5** · Französisch „Site web de Alpha S.à r.l.“ (Elision fehlt bei Vokal), Portugiesisch „janeiro 2026“ statt
  „janeiro de 2026“ — Kosmetik, namensabhängig.
- **H-6** · `transliterator_transliterate()` braucht `ext-intl`; `composer.json` verlangt sie nicht, Dockerfile und CI
  installieren sie. Ohne sie bräche die Sortierung bei gleichem Beginn.
- **H-7** · `vendor/bin/php-cs-fixer` fehlt, `lint:container` scheitert vorbestehend (Webauthn) — beides nicht vom
  Feature.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Unit/Sponsor/Qa15SponsorTest.php` | 9 Datenfälle Tageswechsel mit Serveruhr in UTC (Sommer, Winter, Herbstumstellung), 2 Reproduktionen | AK-24, EC-02, EC-12; BF-188, BF-189 (übersprungen, solange offen) |
| `tests/Functional/Sponsor/Qa15SponsorSeitenTest.php` | AK-18 in fünf Sprachen, AK-14 Übersicht + Logoreihe in fünf Sprachen, 1 Reproduktion | AK-14, AK-18; BF-188 (übersprungen) |
| `tests/Integration/Sponsor/Qa15BrevoLaufzeitTest.php` | 1 (beide Laufzeiten) | Katalogfrage 2: Laufzeit geht nicht nach Brevo, am tatsächlichen Payload |

Gegenproben: AK-14/AK-18 rot mit „(monatlich)“ in der Karte bzw. ohne `lang` am Zitat (10 von 10), Brevo rot mit einem
Attribut, das im Payload steht. Scharf: `QA15_REPRO=1 php bin/phpunit tests/Unit/Sponsor/Qa15SponsorTest.php
tests/Functional/Sponsor/Qa15SponsorSeitenTest.php` → 3 rot (`qa/15/reproduktionen-scharf.ausgabe.txt`). Wer einen
Befund behebt, entfernt den Aufruf `offen(…)` bzw. das `markTestSkipped` — und ändert sonst nichts an der Prüfung.

## Nächster Schritt

1. **Wegwerf-Datenbank löschen** (die Leitplanke lässt das nur den Menschen tun):
   `! DATABASE_URL='mysql://root:root@127.0.0.1:3306/endlech_qa15?serverVersion=8.0&charset=utf8mb4' php bin/console doctrine:database:drop --force --env=dev`
2. **`/sdd-klaeren 15`** — BF-190 (AK-10 auf die gemessenen Seiten fassen oder hinnehmen) und die Frage, ob BF-188 vor
   dem ersten Eintrag behoben sein muss.
3. **`/sdd-deploy 15`** — kein blockierender Befund. Bis BF-188 behoben ist: beim Entfernen Eintrag **und** Logodatei
   löschen und `php bin/phpunit tests/Unit/Sponsor` laufen lassen.
4. Nicht blockierend: **`/sdd-build 15`** mit BUG-01 bis BUG-08 (BF-188 bis BF-195), danach `/sdd-qa 15`.
5. AK-26 (gestoppte Zeit beim ersten echten Sponsor einschließlich Ausrollen), AK-47 und AK-48 (T22) nach der
   Auslieferung nachholen und hier eintragen.

---

# Nachprüfung · Überarbeitung vom 2026-10-28 (AK-49 bis AK-55, EC-13, EC-14)

Stand: 2026-10-28 · Geprüft gegen `spec.md` in der Überarbeitung vom 2026-10-28 (Decision Log #29–#33), den Nachtrag in
`design.md` und `tasks.md` (T23–T28, Abschlussbericht-Nachtrag) · in eigener Session, ohne Bauverlauf · Stand des Baus:
weiterhin uncommittet auf `feature/15-sponsoren`. Der erste Abschnitt oben gilt für AK-01 bis AK-48 und bleibt stehen.

**Wie geprüft wurde.** Der Entwicklungsserver lief bereits (`symfony server:start -d --no-tls --port=8000`, gegen die
Wegwerf-Datenbank `endlech_qa15` aus dem ersten Durchlauf) und wurde weder neu gestartet noch umkonfiguriert. In
`SponsorRegistry` standen vorübergehend **sechs Probe-Einträge zusätzlich** zum lokalen Beispiel Wonschstär (Abschrift
`qa/15/N-probe-registry.php.txt`): „Ëmil & Fils "Q" S.à r.l.“ (Website, Zitat, drei Profile, Beschreibung mit
`<script>`, `<b>`, `&amp;`, Anführungszeichen, `{{ 7*7 }}`, `%name%` und Zeilenumbrüchen, eine Instagram-Adresse mit `"`),
„Nur Profil SA“ (nur LinkedIn), „Ohne Alles ASBL“ (weder Website noch Profil noch Beschreibung), „Grenze GmbH“ (genau
500 Zeichen je Sprache), „Ehemals Profil AG“ (beendet, mit Website, drei Profilen, Beschreibung, Zitat), „Künftig SA“
(Beginn 2026-11-15, mit Beschreibung und Profil); dazu drei Logos als Kopien der Testlogos. Für den Prüflauf kamen
danach 14 absichtlich falsche Einträge hinzu. Die Sitemap des Entwicklungsservers antwortete **429** (Deckel 60/Std. vom
ersten Durchlauf verbraucht); sie und vier Sponsorenseiten wurden deshalb mit der **Produktionsverdrahtung** gerendert
(Kernel `prod`, `debug=false`, Cache unter `/tmp`, andere Client-Adresse). Browser: Vorschau-Chrome 1280 × 800 und
390 × 844. Gegenproben der neuen Funktionstests durch vorübergehende Änderungen an `show.html.twig`. **Nachher:**
Registry byte-gleich zum Ausgangszustand (SHA-256 `1dfee822…`, das markierte Beispiel Wonschstär unverändert), im
Logo-Verzeichnis nur `wonschstaer.webp` (`55cca141…`), `show.html.twig` byte-gleich (`a68e4198…`), Server läuft wie
vorgefunden, die Zeilen dieser Session in `var/log/dev.log` und `var/log/test.log` abgeschnitten.

## Fazit (Nachprüfung)

**Production-ready: ja — mit Vorbehalten. Kein Befund ist *kritisch* oder *hoch*.**

Die Überarbeitung trägt im Hauptweg. Die Sponsorenseite zeigt unter „Über <Name>“ die Fassung der Seitensprache, in
allen fünf Sprachen. 500 Zeichen erscheinen vollständig, und ohne Beschreibung fehlt der Abschnitt ganz. Je Profil steht
ein Textlink mit dem Plattformnamen, angesagt als „<Plattform> von <Name>“. Er trägt `rel="sponsored noopener"`, hat kein
Ziel-Fenster und ist 48 px hoch. Bei Ehemaligen fallen alle Links nach außen weg, die Beschreibung bleibt. Nichts wird
von Instagram, Facebook oder LinkedIn geladen. Ein Klick auf ein Profil löst keinen Zählaufruf aus, auch bei echter
Navigation nicht. Auszeichnung in der Beschreibung bleibt Text. 17 Tarnadressen für Profile weist der Prüflauf ab, ebenso
die Rückstrich-Parserdifferenz. Restaurantseiten, Startseite und Liste sind mit und ohne die neuen Einträge byte-gleich
bis auf den Footer (29 Seiten).

Drei neue Befunde, alle nachgestellt:

- **BF-196 (mittel)** — **AK-51 durchgefallen:** Ein ausgeschriebener Eurobetrag passiert den Prüflauf („500 Euro“,
  „500 euros“, „50EUR“, „50 EURO“, „50 eur“). Die Regel kennt nur `€` und `EUR` mit Wortgrenze, Groß-/Kleinschreibung
  zählt. Laufzeitwörter („monatlich“, „jährlich“, AK-14) prüft sie gar nicht. Die Beschreibung ist das erste
  Freitextfeld auf der Sponsorenseite. Sie ist die einzige Stelle, an der ein Betrag in eine Sponsorenseite gelangen
  kann. Dann stünde der Paketpreis öffentlich, den Decision Log #7 bewusst zurückhält.
- **BF-197 (niedrig)** — Ein `null` in Beschreibung oder Profil („kommt noch“) bricht den Prüflauf mit `TypeError` ab.
  Er nennt dann weder Sponsor noch Sprache und verschluckt alle übrigen Verstöße. Auf der Seite fehlt der Abschnitt in
  dieser Sprache lautlos.
- **BF-198 (niedrig)** — **EC-13 durchgefallen, zweite Hälfte:** Ein Zeilenumbruch wird `<br>` im selben `<p>`, kein
  Absatz. Der Entwurf widerspricht sich (Zeile 309 „→ Absatz“, Zeile 320 „`nl2br`“). Der Prüflauf des Baus prüft „bleibt
  ein Umbruch“.

**Die Vorbehalte:** Alle des ersten Durchlaufs gelten weiter: BF-190 braucht eine Entscheidung, AK-26, AK-47 und AK-48
sind nicht prüfbar, BF-188 betrifft das Entfernen. **Neu:** BF-196 sollte behoben sein, bevor der erste Sponsor mit
Kurzbeschreibung eingetragen wird. Bis dahin liest der Betreiber jede Fassung selbst auf Beträge und Laufzeitwörter
gegen. **Und das lokale Beispiel „Fondatioun Wonschstär“ muss vor dem Commit heraus** (H-8): Kein Prüflauf merkt es, und
committet stünde ein Sponsor ohne Vereinbarung im Footer jeder Seite und in der Sitemap.

| | Anzahl |
|---|---|
| Neue Akzeptanzkriterien geprüft | 7 von 7 (AK-49 bis AK-55) |
| davon bestanden | 6 |
| davon durchgefallen | 1 (AK-51) |
| **nicht prüfbar** | 0 |
| Neue Edge Cases | 2 von 2 ausgeführt: EC-14 bestanden, **EC-13 durchgefallen** (zweite Hälfte) |
| Bisherige Kriterien erneut ausgeführt (von der Überarbeitung berührt) | 16, alle bestanden |
| Tests neu geschrieben | 42 in zwei Dateien (davon 9 Reproduktionen, übersprungen, solange offen) |
| Tests grün | 33 von 33; die 9 Reproduktionen scharf geschaltet: 9 rot, wie erwartet |
| Volle Suite gelaufen | **1816 Tests, 0 Fehlschläge**, 10 398 Zusicherungen, 28 übersprungen (vor den neuen QA-Tests: 1774 / 19, gleich dem Abschlussbericht-Nachtrag) |
| Regression: berührte Features | 4 (B24, 10, 11, 02) + Feature 15 selbst, Hauptweg bestanden 4 |

## Neue Akzeptanzkriterien im Einzelnen

„Server“ heißt: Entwicklungsserver mit den Probe-Einträgen, Uhr = Systemuhr (2026-10-28). „Prod-Kernel“: Produktionsverdrahtung, siehe oben.

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-49 | ✅ bestanden | Server, fünf Sprachen, Wonschstär und Ëmil & Fils: `h2` „Iwwer / Über / À propos de / About / Sobre <Name>“, darunter genau die Fassung der Seitensprache. Jede Fassung trägt eine Sprachmarke (`LB-EMIL` …) und steht nur auf ihrer eigenen Seite. „Grenze GmbH“ zeigt genau 500 Zeichen je Sprache vollständig (500 von 500, letztes Zeichen erhalten), auch im Prod-Kernel. Tests: `SponsorShowTest::testAk49…` (5 Sprachen) und neu `Qa15NachpruefungSeitenTest::testAk49BeschreibungVon500ZeichenStehtVollstaendig` (5; Gegenprobe: auf 499 Zeichen gekürzt → 5 rot). Screenshot `qa/15/N-AK-49-EC-13-beschreibung-sonderzeichen-390px.png` |
| AK-50 | ✅ bestanden | Server, fünf Sprachen: „Nur Profil SA“ und „Ohne Alles ASBL“ haben weder Überschrift „Über …“ noch einen leeren Kasten (erste `h2` ist „Wofür das Geld verwendet wird“). `SponsorShowTest::testAk50…` |
| AK-51 | ❌ durchgefallen | Prüflauf über die Produktionsquelle (`SponsorRegistryTest`) mit falschen Einträgen. Gemeldet, je mit Sponsor, Sprache und Angabe: fehlendes `pt`, leeres `fr`, 501 Zeichen in `de`, „50 €“ in `en`. **Ungemeldet:** „500 Euro“ (`lb`), „500 euros“ (`fr`), „50EUR“ (`de`) → BUG-09 (BF-196). `'pt' => null` → `TypeError` statt Meldung, ein weiterer Fehler danach fehlt → BUG-10 (BF-197). 500 Zeichen grün, 501 rot in jeder Sprache (neu `Qa15NachpruefungTest::testAk51Grenze…`). Ausgabe `qa/15/N-AK-51-AK-53-prueflauf-fehlerhafte-eintraege.txt` |
| AK-52 | ✅ bestanden | Server, fünf Sprachen: je Profil ein Link mit Text „Instagram“, „Facebook“ oder „LinkedIn“, in der Reihenfolge des Enums. Eingetragen war `linkedin, instagram, facebook`, ausgegeben wird Instagram, Facebook, LinkedIn. `href` ist genau der Eintrag, `rel="sponsored noopener"`, kein `target`, die Attribute sind nur `href`, `rel`, `aria-label`, `class`. Browser: zugänglicher Name „Instagram von Ëmil & Fils "Q" S.à r.l.“ (lb „vun“, fr/pt „de“, en „<Name> on Instagram“, siehe H-12). Bei 390 px sind die Links 48 px hoch, ohne waagerechtes Scrollen. Wonschstär hat kein LinkedIn, also auch keinen LinkedIn-Link. Tests: `SponsorShowTest::testAk52…` und neu `testAk52NameUndAdresseMitSonderzeichen` (Gegenprobe `\|raw` am `aria-label` → rot). Screenshot `qa/15/N-AK-52-profillinks-390px.png` |
| AK-53 | ✅ bestanden | Prüflauf: `http://`, `instagram.com.evil.example`, Facebook-Profil auf `linkedin.com` und `tiktok` → je gemeldet mit Sponsor und Plattform. Angriffsdurchlauf: 17 Tarnadressen abgewiesen — Benutzerteil vor fremdem Host, `?@`, `#@`, Rückstrich vor `@` und im Host, Tabulator, Leerzeichen, `%2e`, Plattform als Pfad oder Präfix, Name ohne Punkt, kyrillisches а, Punycode, `fb.me`, `lnkd.in`, `javascript:`, `//` (neu `Qa15NachpruefungTest::testAk53Tarnadresse…`). Übliche Schreibweisen (`WWW.INSTAGRAM.COM`, `m.facebook.com`, `lu.linkedin.com`) bleiben gültig. Weiterleiter der Plattformen selbst: H-9 |
| AK-54 | ✅ bestanden | Server, fünf Sprachen: „Ehemals Profil AG“ (Website, drei Profile, Beschreibung) zeigt die Beschreibung, 0 Links nach außen, keine Linkzeile. Prod-Kernel `/fr/…`: 200, keine Adresse von Website oder Plattform im Dokument. Neu `testAk54EhemaligBehaeltBeschreibungVerliertAlleLinks` (5; Gegenprobe „Linkzeile auch bei Ehemaligen“ → 5 rot) |
| AK-55 | ✅ bestanden | Browser `/de/sponsoren/emil-fils` (drei Profile, nach Scrollen bis zum Footer): 46 Ressourcen, 0 von fremdem Ursprung, 0 von `instagram`, `facebook`, `fbcdn`, `linkedin`, `licdn`, kein `iframe`, kein `svg` in `<main>`. Server: auf 190 Sitemap-Seiten keine Ressource von einer Plattform. **Klick:** Zählskript mit den Attributen aus `base.html.twig` eingehängt. Positivprobe: Der Seitenaufruf ging an `/api/send`. Danach drei Klicks auf die Profillinks bei unterdrückter Navigation: 0 weitere Aufrufe (`fetch`, `sendBeacon`, XHR). Zweiter Durchgang mit **echter Navigation** auf einen zweiten Ursprung: Netzwerkmitschnitt und Serverprotokoll zeigen den Seitenaufruf (21:25:56 UTC) und die Navigation (21:26:07), dazwischen und danach kein `/api/send`. `data-performance` ist nicht gesetzt, also kein Versand bei `pagehide`. Die Zielseite erfährt `document.referrer` = `http://127.0.0.1:8000/` (nur Ursprung), `opener` ist `null`. Tests: `SponsorIntegrityTest::testAk55…` und neu `testAk55DreiPlattformenNurAlsLinkziel` (Gegenproben: Plattformsymbol von dort → rot, `data-action` am Link → rot) |

## Neue Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-13 | ❌ durchgefallen (zweite Hälfte) | **Erste Hälfte bestanden:** In fünf Sprachen stehen `<script>alert("…")</script>`, `<b>…</b>`, `&amp;`, „…“, '…', "…", `{{ 7*7 }}` und `%name%` als Text da. In `<main>` gibt es 0 `<b>` und 0 `<script>`, im Quelltext steht `&lt;script&gt;` (Screenshot). **Zweite Hälfte nicht:** „ein Zeilenumbruch bleibt ein Absatz“ — `nl2br` setzt `<br />` in dasselbe `<p>`. Ein einzelner Umbruch erscheint als neue Zeile ohne Absatzabstand, und ein Screenreader liest einen einzigen Absatz → BUG-11 (BF-198) |
| EC-14 | ✅ bestanden | Server, fünf Sprachen: „Nur Profil SA“ (LinkedIn, keine Website) hat eine Linkzeile mit genau einem Link. „Ohne Alles ASBL“ hat keine Linkzeile; das einzige `<ul>` in `<main>` ist „Was … nicht bekommt“. Neu `testEc14OhneWebsiteUndOhneProfilKeineLinkzeile` (Gegenprobe „Linkzeile immer“ → rot) |

## Regression

Volle Testsuite: `php bin/phpunit` → **1816 Tests, 0 Fehlschläge**, 10 398 Zusicherungen, 28 übersprungen
(`qa/15/N-volle-suite.ausgabe.txt`). Vor den neuen QA-Tests liefen 1774 Tests mit 0 Fehlschlägen und 19 übersprungenen,
wie im Abschlussbericht-Nachtrag. Der erste Durchlauf lief mit 1753, die Zahl ist gewachsen. Dazu `lint:twig
templates/sponsor` (6 Dateien) und `lint:yaml translations` (30 Dateien) grün.

**Umfang der Überarbeitung.** `git diff` trennt sie nicht vom ersten Bau, alles ist uncommittet. Bestimmt wurde sie über
die Dateien, die nach dem ersten Durchlauf geändert wurden (`find -newermt '2026-10-28 21:56'`):
`src/Sponsor/{Sponsor,SponsorPlatform (neu),SponsorRules,SponsorRegistry}.php`, `templates/sponsor/show.html.twig`, fünf
`messages.*.yaml` (zwei Schlüssel), Tests (`SponsorRulesTest`, `SponsorRegistryTest`, `SponsorShowTest`,
`SponsorIntegrityTest`, `TestSponsors`, `Qa15SponsorTest` nur um das neue Argument erweitert; die Reproduktionen BF-188
und BF-189 bleiben scharf rot), `CLAUDE.md`, `docs/data-model.md`, `docs/datenschutz.md` und das Beispiel-Logo. Außerhalb
von Feature 15 liegen nur die Kataloge und die Dokumentation.

**Von der Überarbeitung berührte bisherige Kriterien, erneut ausgeführt:**

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | Server: Auf allen 190 Sitemap-Seiten (fünf Sprachen) trägt der Footer dieselben fünf Logos in derselben Reihenfolge, nach Beginn geordnet |
| AK-08 / AK-09 | ✅ | 29 Seiten (fünf Startseiten, Liste de/fr, 22 Detailseiten) mit Probe (fünf aktive, ein ehemaliger) und im Ausgangszustand (ein aktiver) verglichen: 29 von 29 byte-gleich nach Entfernen des Footer-Bereichs und der Debug-Leiste. Keine Beschreibung und kein Profil außerhalb der eigenen Sponsorenseite (190 Seiten durchsucht) |
| AK-12 / AK-15 | ✅ | Übersicht: fünf aktive, „Ehemals Profil AG · Januar 2025 – September 2026“ unter „Bisher unterstützt von“; dort weder Beschreibung noch Profil |
| AK-14 | ✅ | Sponsorenseiten in fünf Sprachen mit Beschreibungen: kein Betrag, keine Laufzeit (Probe-Inhalte). Die Absicherung für echte Einträge ist zu schwach → BF-196 |
| AK-16 / AK-17 / AK-20 / AK-21 | ✅ | Ëmil & Fils: alle Teile aus AK-16; Website-`href` genau `https://example.com/emil?x=1`, `rel="sponsored noopener"`. Ohne Website kein Website-Link, Ehemalige ohne Website-Link — die Bedingung der Linkzeile wurde geändert (Website **oder** Profil) |
| AK-18 / AK-19 | ✅ | Zitat weiter `blockquote lang="fr"`, gezeichnet „— Ëmil & Fils "Q" S.à r.l.“ (`Qa15SponsorSeitenTest` grün) |
| AK-22 | ✅ | `/de/sponsoren/gibt-es-nicht` → 404 |
| AK-23 | ✅ | „Künftig SA“ (mit Beschreibung und Profil) → 404 in fünf Sprachen, auch im Prod-Kernel; nicht in Footer, Übersicht, Sitemap |
| AK-26 (Teil) | ✅ / ⚠️ | Sechs neue Sponsoren mit Beschreibung in fünf Sprachen waren ohne Katalogänderung in allen fünf Sprachen live. Die Ausrolldauer ist weiterhin nicht prüfbar |
| AK-40 / AK-41 | ✅ | Prod-Kernel: Sitemap mit **190 = (21 + 11 + 6) × 5** Einträgen, „Künftig SA“ fehlt, gegen `sitemap-mit-sprachverweisen.xsd` gültig |
| AK-42 / AK-46 | ✅ | siehe AK-55; Website-Link ohne `data-*`, kein Zählaufruf |
| EC-09 | ✅ | alle Serverprüfungen mit curl, also ohne JavaScript |

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B24 | zwei neue Schlüssel in fünf Katalogen | ✅ | `CatalogueCompletenessTest` grün (volle Suite); Überschrift und Linkname in fünf Sprachen gerendert |
| 10 | Sponsorenseiten in der Sitemap, jetzt mit neuen Daten | ✅ | Prod-Kernel: 190 Einträge, Schema gültig; `SitemapSponsorTest`, `SeoRouteCoverageTest` grün |
| 11 | Seitenaufruf der Sponsorenseite, kein Klickereignis | ✅ | Payload im Browser: `url` `/de/sponsoren/emil-fils`, `title`, `screen`, `language`, kein Ereignis beim Klick; `CollectControllerTest` grün |
| 02 | neue Überschrift und Linkzeile | ✅ | Überschriftenfolge `h1` → `h2` „Über …“ → `h2` …; Links 48 px; 390 px ohne waagerechtes Scrollen (`scrollWidth` = `clientWidth`); `HeadingOrderTest`, `SponsorAccessibilityTest` grün |

Die übrigen Features aus dem ersten Durchlauf (B15, B22, B16, 05, B13, B05/B06) berührt die Überarbeitung mit keiner
Datei. Ihre Prüfläufe sind in der vollen Suite grün, B05/B06 zusätzlich über den AK-08-Vergleich. Ihre Hauptwege wurden
nicht erneut von Hand gegangen; es gelten die Ergebnisse des ersten Abschnitts.

**Abschlussbericht-Nachtrag gegengeprüft:** „6 von 6 Aufgaben“, die Testzahlen (10 Regelfälle, 10 Seitentests,
1 Integritätstest), „1774, 19 übersprungen“ und „fb.me, lnkd.in werden abgewiesen“ stimmen. **Nicht** stimmt „Offene
Akzeptanzkriterien: keine aus der Überarbeitung“: AK-51 (Eurobetrag) und EC-13 sind nicht erfüllt. Die Weiterleiter der
Plattformen selbst passieren (H-9).

## Sicherheitsprüfung (Nachprüfung)

Angriffsdurchlauf für die neuen Eingaben, Kurzbeschreibung und Profiladressen. Sie kommen nur vom Betreiber und
vom Sponsor über die Vereinbarung, nicht von Besuchern. Geprüft wurde trotzdem, was eine fehlerhafte oder
untergeschobene Angabe anrichtet.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Beschreibung: Auszeichnung, Skript, Vorlagensyntax | bestanden | `<script>`, `<b>`, `&amp;`, drei Arten Anführungszeichen, `{{ 7*7 }}` und `%name%` in fünf Sprachen als Text; 0 ausgeführte Elemente; kein `\|raw`, `nl2br` maskiert vorher |
| Profiladresse: Ausbruch aus dem Attribut | bestanden | `https://www.instagram.com/emil"onmouseover="alert(1)` besteht den Prüflauf (gültige URL) und wird zu `&quot;`. Der Browser sieht einen Link mit den Attributen `href`, `rel`, `aria-label`, `class` und dem Ziel `https://www.instagram.com/emil%22onmouseover=%22alert(1)` |
| Profiladresse: getarnter fremder Server | bestanden | 17 Tarnadressen abgewiesen. Die Parserdifferenz `https://evil.example\@www.instagram.com/` (PHP liest den Host `www.instagram.com`, ein Browser `evil.example`) fängt `FILTER_VALIDATE_URL` ab |
| Profiladresse: Weiterleiter der Plattform | Hinweis H-9 | `l.facebook.com/l.php?u=…`, `l.instagram.com/?u=…`, `www.linkedin.com/redir/redirect?url=…` werden angenommen |
| Name im `aria-label` | bestanden | `Ëmil & Fils "Q" S.à r.l.` → `aria-label="Instagram von Ëmil &amp; Fils &quot;Q&quot; S.à r.l."` |
| Fremde Ressourcen, Einbettungen | bestanden | siehe AK-55 |
| PII an externe Dienste | bestanden | Umami: nur der Seitenaufruf, tatsächlicher Payload `{"website":…,"screen":"1920x1080","language":"en-US","title":"Ëmil & Fils \"Q\" S.à r.l. · Sponsoren · Endlech.lu","hostname":"127.0.0.1","url":"…/de/sponsoren/emil-fils","referrer":""}`, kein Ereignis beim Klick. Die Plattform erfährt beim Klick nur den Ursprung |
| PII in Logs | bestanden | kein Logger in `src/Sponsor`, Controller oder Erweiterung; die Überarbeitung schreibt nichts |
| Zugriff (IDOR) | bestanden | künftiger Sponsor mit Beschreibung und Profil → 404 (fünf Sprachen, Prod-Kernel); die Beschreibung steht nirgends außer auf der eigenen Seite |
| Rate Limit | unverändert | keine neue Route, keine Mail, kein Geheimnis; der Sitemap-Deckel greift weiter (429 am Entwicklungsserver) |
| Prüfung erst im Prüflauf | Hinweis H-10 | eine ungeprüfte Profiladresse (`javascript:` …) landet zur Laufzeit im `href` — wie die Website, so entworfen |

## Fehler (Nachprüfung)

### BUG-09 · Ein ausgeschriebener Eurobetrag in der Kurzbeschreibung passiert den Prüflauf — mittel (BF-196)

**Betrifft:** AK-51 („nennt einen Eurobetrag → ein Prüflauf schlägt fehl“), mittelbar AK-14 und Decision Log #7
**Reproduktion:**
1. In `SponsorRegistry` einen Eintrag mit vollständiger Beschreibung anlegen und eine Fassung auf
   `'lb' => 'Mir bezuelen 500 Euro am Joer.'` setzen (ebenso `'fr' => 'Nous versons 500 euros par an.'` oder
   `'de' => 'Ab 50EUR im Monat.'`).
2. `php bin/phpunit tests/Unit/Sponsor/SponsorRegistryTest.php`
**Erwartet:** rot, mit Sponsor, Sprache und Angabe.
**Tatsächlich:** grün. Gemeldet werden nur `€` und `EUR` als eigenes Wort. Ungemeldet bleiben „Euro“, „euros“, „EURO“,
„eur“ und „50EUR“, also die übliche Schreibweise im Fließtext aller fünf Sprachen. Laufzeitwörter („monatlich“,
„jährlich“, „par an“), die AK-14 auf einer Sponsorenseite ausschließt, prüft die Regel gar nicht. Seit der Überarbeitung
ist die Beschreibung das einzige Freitextfeld der Seite. `CLAUDE.md` nennt „kein Eurobetrag“ als geprüfte Regel. Der
Satz „`Sponsor` hat strukturell kein Feld für Betrag“ trägt nicht mehr, denn die Beschreibung kann alles enthalten.
Reproduktion: `Qa15NachpruefungTest::testBf196…` (8 Fälle, scharf mit `QA15_REPRO=1`: rot).
**Ort:** `src/Sponsor/SponsorRules.php:185` (`/€|\bEUR\b/u`); Entwurf `design.md:342` („kein `€`/`EUR`“).
`SponsorRulesTest.php:113–114` prüft genau die zwei Formen, die die Regel kennt, nicht das Kriterium.
`SponsorIntegrityTest.php:101` hat im selben Feature eine schärfere Definition (`/i`, Dezimalbetrag, Laufzeitwörter in
fünf Sprachen), prüft aber nur die Testsponsoren.
**Vorschlag:** In `SponsorRules` dieselbe Definition wie im Integritätstest verwenden, ergänzt um `euro(s)?` ohne
Wortgrenze vor der Zahl und Groß-/Kleinschreibung. Im Test die ausgeschriebenen Formen je Sprache prüfen.

### BUG-10 · `null` in Beschreibung oder Profil bricht den Prüflauf ohne Nennung ab — niedrig (BF-197)

**Betrifft:** AK-51 („nennt Sponsor, Sprache und Angabe“), AK-53 („nennt Sponsor und Plattform“)
**Reproduktion:** Eintrag mit `description: [… 'pt' => null]` oder `profiles: ['instagram' => null]`, dazu ein zweiter
Eintrag ohne `pt`; `php bin/phpunit tests/Unit/Sponsor/SponsorRegistryTest.php`.
**Erwartet:** zwei Meldungen mit Sponsor und Sprache bzw. Plattform.
**Tatsächlich:** `TypeError: trim(): Argument #1 ($string) must be of type string, null given` (bzw.
`str_starts_with()`); der zweite Eintrag wird nicht mehr gemeldet. Zur Laufzeit fehlen der Abschnitt auf `/pt/` und der
Instagram-Link lautlos. Der Prüflauf ist immerhin rot, die Zusage hält, nur die Meldung nicht — deshalb niedrig.
Reproduktion: `Qa15NachpruefungTest::testBf197…` (scharf: Fehler).
**Ort:** `src/Sponsor/SponsorRules.php:179` (`trim($text)`), `:209` (`str_starts_with($url, …)`) unter `strict_types`.
**Vorschlag:** Nicht-Zeichenketten wie „fehlt“ bzw. „keine https://-Adresse“ melden, bevor gekürzt oder verglichen wird.

### BUG-11 · Ein Zeilenumbruch in der Kurzbeschreibung wird kein Absatz — niedrig (BF-198)

**Betrifft:** EC-13, zweite Hälfte („ein Zeilenumbruch bleibt ein Absatz“)
**Reproduktion:** Beschreibung `"Erste Zeile\nZweite Zeile\n\nDritter Absatz"`, Sponsorenseite aufrufen.
**Erwartet:** je Absatz ein `<p>` (oder, falls nur die Leerzeile einen Absatz meinen soll, das so in der Spec).
**Tatsächlich:** ein `<p>` mit `Erste Zeile<br />Zweite Zeile<br /><br />Dritter Absatz`. „Zweite Zeile“ steht ohne
Absatzabstand direkt darunter (Screenshot `qa/15/N-AK-49-EC-13-…png`), und ein Screenreader springt nicht von Absatz zu
Absatz. Der Entwurf widerspricht sich (`design.md:309` „Zeilenumbruch → Absatz“, `:320` „danach `nl2br`“). Der Prüflauf
`SponsorShowTest::testEc13…` heißt im Docblock „ein Zeilenumbruch bleibt ein **Umbruch**“ und prüft `<br>`.
**Ort:** `templates/sponsor/show.html.twig:58`
**Vorschlag:** Die Beschreibung an Leerzeilen in Absätze teilen (je `<p>`, darin `nl2br`). Andernfalls EC-13 in der Spec
neu fassen — dann über `/sdd-klaeren 15`.

## Hinweise (Nachprüfung, kein Befund)

- **H-8 · Das lokale Beispiel „Fondatioun Wonschstär“ muss vor dem Commit heraus**, samt
  `assets/images/sponsors/wonschstaer.webp`, so wie im Eintrag selbst und im Abschlussbericht vermerkt. Kein Prüflauf
  merkt es: `SponsorRegistryTest` ist mit ihm grün, und Footer jeder Seite, Übersicht, Faktenblatt („Ein aktiver Sponsor“)
  und Sitemap (fünf Sprachen) führen es. Committet und ausgerollt stünde ein Sponsor ohne Vereinbarung (VB-01) mit einer
  nicht freigegebenen Beschreibung öffentlich da, und er bliebe im öffentlichen Verlauf. Das ist kein Befund am Feature.
- **H-9 · Die Host-Regel lässt die Weiterleiter der Plattformen durch.** `l.facebook.com/l.php?u=…`,
  `l.instagram.com/?u=…`, `www.linkedin.com/redir/redirect?url=…` und jeder Pfad auf der Plattform (auch die Startseite)
  gelten als Profil. Wie weit das zu einem fremden Ziel trägt, hängt von den Zwischenseiten der Plattformen ab. AK-53
  verlangt nur den Host, deshalb kein Befund. Ebenso wenig unterscheidet die Regel LinkedIn-Personenprofile (`/in/…`) von
  Firmenseiten (`/company/…`). `docs/datenschutz.md` sagt „Kontaktpersonen erscheinen nicht“; ein Personenprofil als
  „LinkedIn von <Firma>“ wäre genau das (AK-44). Abhilfe wäre ein Pfadmuster je Plattform.
- **H-10 · Zur Laufzeit prüft niemand die Adressen.** `profileLinks()` gibt jede eingetragene Adresse aus, `http://`
  und `javascript:` eingeschlossen. Schutz ist allein `SponsorRegistryTest`, und PHPUnit ist kein Deploy-Gate (wie bei
  der Website und bei BF-188). `SponsorPlatform::owns()` könnte dort still filtern, ohne der Regel „nicht zur Laufzeit
  werfen“ zu widersprechen.
- **H-11 · Prüfläufe der Überarbeitung, die schwächer sind als ihr Kriterium** (Muster BF-64/BF-117/H-3):
  `SponsorRulesTest::testVerstossWirdMitSponsorUndAngabeGemeldet` sucht die Angabe ohne Backticks. „en“ steckt auch in
  „n**en**nt ei**n**en“, „it“ in „Webs**it**e“, die Sprache in der Meldung ist für diese Fälle also nicht belegt (Gegenprobe
  per `str_contains`). `SponsorIntegrityTest::testAk14…` läuft gegen die Testsponsoren und ist grün, egal was
  `SponsorRules` prüft. `testAk50…` und `testEc14…` prüfen nur „kein `a[rel~=sponsored]`“, eine leere Linkzeile fiele
  nicht auf. `testAk52…` prüft nur `/de/` und ohne Facebook. EC-14, AK-52, AK-54 und AK-55 sind mit
  `Qa15NachpruefungSeitenTest` nachgezogen.
- **H-12 · Sprachfassungen des Linknamens.** Englisch sagt „<Name> on Instagram“ statt „<Plattform> von <Name>“. WCAG
  2.5.3 ist erfüllt, weil der sichtbare Text im Namen steckt; nur die Reihenfolge weicht ab, und geprüft wird nur Deutsch.
  Französisch und Portugiesisch elidieren nicht: „À propos de Ëmil …“, „Instagram de Ëmil …“, wie H-5.
- **H-13** · Eine Fassung nur aus geschützten Leerzeichen (`U+00A0`) gilt nicht als leer (`trim()`), die Überschrift
  „Über …“ stünde über unsichtbarem Text. Konstruiert, nur der Vollständigkeit halber.
- **H-14 · Dokumentation.** Die Anleitung „Sponsor eintragen“ in `CLAUDE.md` nennt die Inhaltsregeln aus VB-01 nicht:
  keine Personennamen, keine Angebote, Preise oder Rabatte. Sie prüft nur der Betreiber. „Eine Sprache hinzufügen“
  erwähnt die Sponsorenbeschreibungen nicht; eine sechste Sprache macht `SponsorRegistryTest` für jeden Sponsor mit
  Beschreibung rot, bis eine freigegebene Fassung vorliegt. Das ist gewollt, steht dort aber nicht. Der Docblock in
  `SponsorRegistry` zeigt das Beispiel noch ohne `description` und `profiles`.
- **H-15** · Die Profillinks tragen anders als der Website-Link kein „↗“. Reine Kosmetik.

## Neue Tests (Nachprüfung)

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Unit/Sponsor/Qa15NachpruefungTest.php` | 17 Tarnadressen, übliche Schreibweisen, Grenze 500/501 je Sprache, „nur Zeilenumbrüche = leer“; 8 + 1 Reproduktionen | AK-51, AK-53; BF-196, BF-197 (übersprungen, solange offen) |
| `tests/Functional/Sponsor/Qa15NachpruefungSeitenTest.php` | AK-49 an der Grenze (5 Sprachen), AK-52 mit `&`/`"`, AK-54 (5 Sprachen), EC-14 zweite Hälfte, AK-55 mit drei Plattformen | AK-49, AK-52, AK-54, AK-55, EC-14 |

Gegenproben an `show.html.twig`, je vorübergehend und danach byte-gleich zurückgesetzt. Rot wurden: Linkzeile auch bei
Ehemaligen (5 rot), Beschreibung auf 499 Zeichen gekürzt (5), `|raw` am `aria-label` (1), Linkzeile immer (1),
Plattformsymbol von dort (1), `data-action` am Profillink (1). Grün blieb nur „Profile auch bei Ehemaligen“ bei
weiterhin geschützter Linkzeile, also doppelt abgesichert, kein Loch. Scharf: `QA15_REPRO=1 php bin/phpunit
tests/Unit/Sponsor/Qa15NachpruefungTest.php` → 8 Fehlschläge, 1 Fehler (`qa/15/N-reproduktionen-scharf.ausgabe.txt`).
Wer einen Befund behebt, entfernt den Aufruf `self::offen(…)` und ändert sonst nichts an der Prüfung.

## Nächster Schritt (Nachprüfung)

1. **Vor dem Commit:** das lokale Beispiel Wonschstär samt Logo entfernen (H-8, Betreiber).
2. **`/sdd-klaeren 15`** — BF-190 (AK-10, unverändert offen). Dazu BF-198, falls „Absatz“ in EC-13 nur die Leerzeile
   meinen soll.
3. **`/sdd-deploy 15`** — kein blockierender Befund. **BF-196 vor dem ersten Sponsor mit Kurzbeschreibung beheben**, sonst
   jede Fassung von Hand auf Beträge („Euro“, „euros“) und Laufzeitwörter gegenlesen.
4. Nicht blockierend: **`/sdd-build 15`** mit BUG-01 bis BUG-11 (BF-188 bis BF-198), danach `/sdd-qa 15`.
5. Unverändert: AK-26, AK-47, AK-48 nach der Auslieferung nachholen. Die Wegwerf-Datenbank `endlech_qa15` steht weiter
   (Löschbefehl im ersten Abschnitt).
