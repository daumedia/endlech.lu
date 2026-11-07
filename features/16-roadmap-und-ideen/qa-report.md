# 16 · Roadmap und Ideen in einem Bereich — Testbericht

Stand: 2026-11-07 · **Zweiter Durchlauf** (Nachprüfung nach dem Fehlerauftrag zu BF-199 bis BF-205) · Geprüft gegen
`spec.md` vom 2026-11-07 (AK-01 bis AK-37, EC-01 bis EC-07, OF-02/OF-03 beantwortet, Decision Log bis #20) · in eigener
Session, ohne Bau- oder Reparaturverlauf · Vorstufe `building`, beide Abschlussberichte am Ende von `tasks.md` als
Suchliste gelesen. Archiv des ersten Durchlaufs (mit den Behebungsvermerken des Fehlerauftrags):
`qa/16/qa-report-erster-durchlauf-2026-11-07.md`.

**Prüfumgebung.** Arbeitsbaum von `feature/16-roadmap-und-ideen` (Feature uncommittet, `HEAD` = Footer-Commit `951594e`),
Entwicklungsserver `http://127.0.0.1:8000` gegen die Dev-Datenbank (MySQL 8, Docker). Zum Vergleich „vor der Umstellung“
`main` (`536cbad`) in einem Wegwerf-Arbeitsbaum unter `/tmp/qa16b-main`, `php -S` auf Port 8001 gegen **dieselbe**
Datenbank (eigener Cache, also eigener Limiter-Speicher). Mailpit auf dem Docker-Port 51087 (nicht 8025), Mails per
`messenger:consume` zugestellt. Browser: Brave 157 headless über playwright-core 1.62.1, axe-core 4.13.0; die
Debug-Toolbar ist bei allen Browsermessungen **blockiert** (`/_wdt`, `/_profiler`) und entfernt — siehe BF-207, warum das
hier zählt. Testdaten in der Dev-DB: zu den Ideen `qa16-0` … `qa16-14` des Baus 30 veröffentlichte (`qa16b-pub-*`, ergibt
45 veröffentlichte, davon 12 geplant), zwei wartende (184 mit Verfasser, 185 ohne), eine Dublette (186 → 94); nach der
Prüfung wieder entfernt (siehe *Testdaten*). Nachweise unter `qa/16/` mit Präfix `QA2-`.

## Fazit

**Production-ready: ja — mit Vorbehalten** (kein offener Befund *kritisch* oder *hoch*; **AK-28 ist durchgefallen**).

Der Fehlerauftrag hat getragen. **Alle sieben Befunde des ersten Durchlaufs sind behoben und am laufenden System
gegengeprüft** — BF-203 im Umfang des Auftrags (Bereichskopf; die übrigen neun Vorlagen mit Glas-Etikett bleiben offen):
**BF-199** (*hoch*) — 4 754 Anfragen über drei Konten, fünf Sprachen, zwölf Parametersätze: Zustimmen mit leerem oder
falschem Token auf eine wartende Idee antwortet **404 ohne `Location`**, für unbestätigtes, bestätigtes und Admin-Konto;
**BF-200** — die Reiterleiste steht auf allen vier Seitenarten auf derselben Höhe (1280 px: 173, 375 px: 165, 320 px: 165
bzw. fr 201 — je Sprache identisch, waagerecht ebenso); **BF-202** — „En cours“ endet bei 375 × 800 mit Banner bei 566 px,
Banner ab 601; **BF-203** — Etikett 6,97–9,18 : 1 an den Pixeln; **BF-201** — kein „Board“ in Katalogen, 25 gerenderten
Seiten und der Freigabe-Mail; **BF-204** README; **BF-205** — beide Prüfläufe werden bei ihrer Gegenprobe rot (selbst
nachgestellt).

**36 von 37 Kriterien bestanden, 1 durchgefallen (AK-28), 0 nicht prüfbar; Randfälle 7 von 7.** Volle Suite **1947 Tests,
0 Fehlschläge**, 40 übersprungen (28 Ausgangsstand, 12 neue Reproduktionen).

Neu, alle nachgestellt:

- **BF-207 (mittel) — AK-28 durchgefallen: Die Einzelseite einer Idee hat keine `h1`.** axe meldet dort
  `page-has-heading-one` in **allen fünf Sprachen, bei 1280 und 375 px** (10 von 50 Läufen); OF-02 nimmt nur die Wortmarke
  aus. Die Seitenstruktur ist seit Feature 06 so (auf `main` derselbe Treffer) — aber AK-28 nennt die Einzelseite
  ausdrücklich. Der erste Durchlauf und die Messung des Fehlerauftrags sahen den Treffer nicht: **Mit geladener
  Debug-Toolbar kann axe die Regel als bestanden werten** (nachgestellt, `QA2-AK-28-einzelseite-ohne-h1.txt`). Die
  Produktion hat keine Toolbar.
- **BF-206 (mittel, vorbestehend, projektweit) — `focus:outline-white` ist überall wirkungslos.** Die globale Regel
  `:focus-visible { outline: 2px solid #0e7490 }` in `assets/styles/app.css` steht außerhalb jeder Kaskadenebene und
  schlägt damit Tailwinds `@layer utilities`. Der Knopf „Idee einreichen“ auf dem Verlaufsband bekommt deshalb einen
  cyan-700-Rahmen mit **1,46–1,65 : 1** gegen das Band statt eines weißen; auf `main` liegt der Hero-Knopf der Startseite bei
  1,07–1,20 : 1. Widerspricht 02/AK-03 („auf jedem Untergrund sichtbar — auch auf den farbigen Verlaufsbändern“) und dem
  Kommentar über der Regel. Ursache per Gegenprobe im Browser belegt (Regel in `@layer base` → Weiß).
- **BF-208 (niedrig, vorbestehend, projektweit) — der Textteil der Mails trägt HTML-Entitäten.** Seit BF-201 steht in der
  Freigabe-Mail „Roadmap **&amp;** Ideen“ im `text/plain`-Teil (lb, de, en, fr); französische Mails trugen schon auf `main`
  `l&#039;avons`, alle `&copy;`. Mails ohne Textvorlage bekommen ihren Textteil per `strip_tags`.
- **BF-209 (niedrig, vorbestehend aus 06; aus dem `code-reviewer`) — die Dubletten-Weiterleitung verrät ein wartendes
  Original.** `show()` leitet eine Dublette auf ihr Original weiter, **bevor** es die Sichtbarkeit prüft, und
  `merge()` nimmt jedes Ziel an. Nach einem handgemachten Verwaltungs-POST (die Auswahl im Formular bietet nur
  veröffentlichte Ideen) bekommt jeder Gast `302 Location: …/199-qa16d-wartendes-original-pfauenauge` — auch über die alte
  Adresse und auf `main`. Dieselbe Fehlerklasse wie BF-199, aber nur über eine Admin-Handlung außerhalb der Oberfläche.
- **BF-210 (niedrig; aus dem `code-reviewer`) — ein Prüflauf des Baus kann nicht rot werden.**
  `RoadmapAreaLocaleTest::testAk37…` liest als „Überschrift der Community-Gruppe“ das erste `h3` der Spalte „Geplant“ —
  ein kuratiertes Vorhaben. Mit „Ideen-Board“ fest im Template blieb die **ganze Suite** grün (1941 Tests, 0 Fehlschläge — vor dem neuen QA-Lauf). Muster BF-205.
  AK-37 selbst ist unabhängig belegt; der neue QA-Lauf schließt die Lücke.

**Vorbehalte, die vor `/sdd-deploy 16` gelesen werden müssen:**

1. **AK-28 wird durchgefallen ausgeliefert**, solange BF-207 offen ist. Die Behebung ist klein (die Idee als Überschrift
   erster Ebene auf der Einzelseite); sie blockiert nach Schweregrad nicht, ist aber eine Zusage dieser Spec.
2. **AK-06, Hälfte „Screenreader sagt ihn als aktuelle Seite an“:** belegt ist das Attribut `aria-current="page"` im
   ausgelieferten Dokument (genau eins je Seite, auch auf der Einzelseite „Ideen“). Eine Screenreader-Ausgabe wurde nicht
   abgehört; Chromes Barrierefreiheitsbaum über CDP und Playwrights ARIA-Abbild führen die Eigenschaft nicht.
3. **AK-09 bestanden, mit BF-206:** Der Fokus ist an allen vier Stationen sichtbar (2 px, durchgehend), am Knopf aber nur
   über den Farbton — 1,5–1,65 : 1 liegt unter den 3 : 1, die WCAG 1.4.11 für Fokusanzeigen verlangt.

**Nächster Aufruf:** `/sdd-deploy 16`. Empfohlen vorher — es blockiert nicht —: `/sdd-build 16` mit BF-207 (eine
Überschrift) und BF-210 (Prüflauf schärfen), danach `/sdd-qa 16` für AK-28. BF-206, BF-208 und BF-209 sind vorbestehend
und projektweit bzw. Feature 06 (Design-System, Mail-Vorlagen, Board-Moderation) und können eigenständig laufen; BF-206
trifft jede Seite mit Verlaufsband, nicht nur diesen Bereich.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 37 von 37 |
| davon bestanden | 36 |
| davon durchgefallen | 1 (AK-28, BF-207) |
| **nicht prüfbar** | 0 (AK-06 mit Vorbehalt 2) |
| Edge Cases belegt | 7 von 7 |
| Nachprüfung früherer Befunde | 7 (BF-199 bis BF-205): 7 behoben — BF-203 im Bereichskopf, Rest des Musters offen |
| Tests neu geschrieben | 23 Fälle in `Qa16NachpruefungTest` (11 Rückfallschutz, 12 Reproduktionen BF-206/-207/-208/-209); eine Zusicherung in `Qa16RoadmapIdeenTest::testAk24…` geschärft |
| Tests grün | 11 von 11 (zwei davon gegengeprüft: rot bei abgeschalteter CSRF-Prüfung bzw. „Ideen-Board“ im Template); Reproduktionen scharf (`QA16_REPRO=1`) **12 von 12 rot**, wie erwartet (`QA2-reproduktionen-scharf.txt`) |
| Volle Suite gelaufen | **1947 Tests, 0 Fehlschläge**, 40 übersprungen (vor den neuen Tests: 1924 / 0 / 28) |
| Regression: berührte Features | 10 (06, 07, 10, 11, 02, 15, 03, 08, B13, B02), davon Hauptweg bestanden 10 |

## Nachprüfung der früheren Befunde

Beide Reproduktionen des ersten Durchlaufs (`Qa16RoadmapIdeenTest::testBf199…`, `::testBf201…`) sind vom Fehlerauftrag
scharf geschaltet worden. Die Änderung am QA-Test (Konto nach dem Kernel-Neustart neu laden) habe ich gelesen: Sie
ändert nur den Aufbau, nicht die Aussage — der Test erreichte die Mailprüfung vorher nie. **Tragbar.** Der Hilfsaufruf
`offen()` ist dort jetzt ungenutzt (kein Befund).

| BF | Befund (kurz) | Ergebnis | Nachweis |
|---|---|---|---|
| BF-199 | Zustimmen mit falschem Token verrät Titel wartender Ideen | ✅ **behoben** | Server: wartende 184/185 × Token leer/falsch × admin@, unverified@, user@ → **12 × 404, kein `Location`**, Titel 0 ×; Gegenprobe veröffentlichte Idee 95 mit falschem Token → 302 auf ihre Seite (Bestandsverhalten), unbekannte 999999 → 404 (`QA2-AK-29-wartende-ideen.txt`). Neu: `Qa16NachpruefungTest::testBf199BestaetigtesFremdesKonto…` (bestätigtes fremdes Konto, drei Token) |
| BF-200 | Reiterleiste springt je Seitenart | ✅ **behoben** | Browser, 4 Seitenarten × 5 Sprachen × 3 Breiten: Oberkante je (Breite, Sprache) identisch, ebenso die x-Lage jedes Reiters; Name, Reiter, Knopf wortgleich (`QA2-browser-messwerte.json` → `ak05`, `QA2-AK-05-*.png`). Neu: `testAk05BandUeberDenReiternIstAufAllenSeitenartenGleich` (Band byte-gleich, 5 Sprachen, auch Folgeseite) |
| BF-201 | „Board“ in Leerzustand, Bestätigung, Mail, Admin-Meldung | ✅ **behoben** | 18 Schlüssel × 5 Sprachen ohne „Board/tableau/quadro/Ideeën“ (`QA2-AK-37-kataloge.txt`); 25 gerenderte Seiten (Roadmap, Liste, Einzelseite, Formular, Bestätigung × 5) ohne Treffer; Freigabe-Mail in Mailpit: Betreff „Deine Idee ist jetzt öffentlich“, Text „unter ‚Roadmap & Ideen‘“ (`QA2-AK-23-mails.txt`) — ⚠ dort BF-208 |
| BF-202 | fr, 375 × 800: „En cours“ unter dem Banner | ✅ **behoben** | Erster Besuch mit Banner, 1280 und 375, alle fünf Sprachen frei; fr/375: Unterkante 566, Banner 601. Breiten 320–412 gegen `main` gemessen: 16 liegt überall 12 px höher als `main`, außer fr/pt bei 320 (dort auf `main` schon verdeckt, H-1) (`QA2-AK-11-in-arbeit-breiten-main-gegen-16.txt`) |
| BF-203 | Glas-Etikett unter 4,5 : 1 | ✅ **im Bereichskopf behoben** | Pixel hinter dem Etikett (Text ausgeblendet), 4 Seitenarten × de/fr/pt × 1280/375/320: schlechtester Wert **6,97 : 1** (12 bzw. 14 px, semibold) (`QA2-browser-messwerte.json` → `etikett`). Übrige neun Vorlagen: offen, nicht Teil des Auftrags |
| BF-204 | README nennt `/community/ideen` | ✅ **behoben** | `README.md:54` nennt `/roadmap/ideen` und die Weiterleitung |
| BF-205 | Zwei Prüfläufe können nicht rot werden | ✅ **behoben** | Selbst gegengeprüft: (1) `BoardLegacyRedirectController::show()` loggt testweise „user@endlech.lu Idee für das Protokoll“ → `testAk30…` **rot** („protokolliert ‚user@endlech.lu‘“); Controller byte-gleich zurück (`cmp`), Testcache geleert, wieder grün. (2) Kopie von `testAk12UnbestaetigtesKonto…` mit falschem Passwort → **rot**; Kopie gelöscht |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | 10 Seiten × 5 Sprachen (Start, Liste, Über uns, drei Reiter, Einzelseite, `/open`, `/sponsoren`, `/legal`): genau **ein** Bereichslink, Text = `footer.roadmap_ideas` der Sprache, Gruppe „Matmaachen/Mitmachen/Participer/Get involved/Participar“, Ziel `/<Sprache>/roadmap` · `QA2-http-messwerte.json` → `ak01_03` (50 von 50) |
| AK-02 | ✅ bestanden | Dieselben 50 Fußzeilen: kein weiterer Link auf `/roadmap`, `/roadmap/ideen`, `/changelog`, `/community/ideen`; die alten Beschriftungen aller Sprachen (aus `HEAD:translations/messages.*`, z. B. „Retours & Idées“, „Registo de alterações“) als Linktext: 0. Fußzeile `main` gegen 16: genau drei Einträge weg, einer neu, dazu der Herstellerhinweis des Footer-Commits (`QA2-Regression-fusszeile-main-gegen-16.txt`) |
| AK-03 | ✅ bestanden | Ein Rückmeldeweg in den Bereich, ohne `target` (50 von 50). Kontaktzeile nicht mitgezählt (OF-03) |
| AK-04 | ✅ bestanden | `curl` anonym, 3 Reiter × 5 Sprachen: 15 × **200**, keine Weiterleitung, kein Feld `_password` |
| AK-05 | ✅ bestanden | siehe BF-200: Name „Roadmap & Ideen“ und Reiter „Roadmap · Ideen · Changelog“ (je Sprache) wortgleich, Reihenfolge stimmt, Oberkante und x-Lage der Reiterleiste je (Breite, Sprache) auf allen vier Seitenarten identisch (60 Messungen) · `QA2-AK-05-1280-de-*.png`, `QA2-AK-05-375-fr-*.png` |
| AK-06 | ✅ bestanden (Vorbehalt 2) | 4 Seitenarten × 5 Sprachen × 3 Breiten: genau ein Reiter mit `aria-current="page"`, auf der Einzelseite „Ideen“. Aktiv: `border-bottom 4px solid` cyan-700, inaktiv: transparenter Rand — beide `font-weight 600` (seit BF-200 gleich fett); Unterschied also Unterstrich **und** Textfarbe (`ak06`). Kontrast des Unterstrichs cyan-700 gegen Weiß 5,36 : 1. Barrierefreiheitsbaum: `QA2-AK-06-barrierefreiheitsbaum.txt` |
| AK-07 | ✅ bestanden | Brave **ohne JavaScript**: 4 Seitenarten × 3 Reiter × 5 Sprachen = 60 Klicks, je **eine** Navigation, Adresse = `href`, `aria-current` danach am Ziel (`ak07`, 60 von 60). Mit JavaScript: Turbo wechselt die Reiter ohne Neuladen, keine Konsolenfehler (`QA2-Regression-konsole-turbo.txt`) |
| AK-08 | ✅ bestanden | 320 px, 4 Seitenarten × 5 Sprachen: `scrollWidth` = `clientWidth` = 320, alle Reiter im Fenster, kleinster Reiter **44 × 44** (Grenzwert genau), keine Beschriftung abgeschnitten; pt bricht die Reiter zweizeilig, fr das Band (`ak08`, `QA2-AK-08-*-320.png`) |
| AK-09 | ✅ bestanden (Vorbehalt 3) | 3 Reiter × 5 Sprachen, Tab vom Seitenanfang: Knopf (10.), Roadmap (11.), Ideen (12.), Changelog (13.), je `outline: solid 2px`; Enter auf jedem öffnet sein Ziel (`ak09`, `QA2-AK-09-fokus-letzter-reiter.png`). Rahmenfarbe nach dem Übergang cyan-700: auf Weiß 5,36 : 1, **am Knopf auf dem Band 1,46–1,65 : 1** → BF-206 |
| AK-10 | ✅ bestanden | 1280 × 800 und 375 × 800, 3 Reiter × 5 Sprachen, mit und ohne Cookie-Banner: 60 von 60, Unterkante des Knopfs höchstens **157 px**, frei (`elementFromPoint` an fünf Punkten). Zusätzlich 320 × 800: 30 von 30 (`ak10`) |
| AK-11 | ✅ bestanden | „In Arbeit“ bei 1280 × 800 (≤ 529 px) und 375 × 800 (≤ 566 px), 5 Sprachen, mit und ohne Banner: 20 von 20 frei (`ak11`, `QA2-AK-11-*-banner.png`). Bei 320 × 800 mit Banner fr/pt verdeckt — nicht Teil von AK-11, auf `main` ebenso (H-1) |
| AK-12 | ✅ bestanden | Browser, **echtes** Anmeldeformular, 5 Sprachen: Gast klickt „Idee einreichen“ → Anmeldung → `/<Sprache>/roadmap/ideen/neu` mit Formular; unverified@ → `/<Sprache>/verify` mit „Bitte bestätige zuerst deine E-Mail-Adresse.“ / „Veuillez d’abord confirmer …“ / „Please confirm …“ / „Confirme primeiro …“; lb per `curl`: „Wgl. bestäteg fir d’éischt deng E-Mail-Adress.“ (`ak12`) |
| AK-13 | ✅ bestanden | 5 Sprachen: drei verschiedene Titel je Sprache (z. B. „Feuille de route – Endlech.lu“, „Idées pour Endlech.lu · Endlech.lu“, „Journal des modifications – Endlech.lu“), je genau **eine** `h1`, keine Sprungstelle in der Überschriftenkette (`ak13`) |
| AK-14 | ✅ bestanden | 5 Sprachen: drei Spalten `in_progress`/`planned`/`considered`, Community-Gruppe mit 10 Ideen, Aktualitätshinweis, „Bewusst nicht gebaut“, **kein** `#more-heading`. Inhalt ab den Spalten gegen `main`: Abweichungen nur in den OF-01-Texten und im Link „2 weitere …“ (`QA2-AK-14-roadmap-alt-gegen-neu.txt`, `QA2-http-messwerte.json` → `ak14_16`) |
| AK-15 | ✅ bestanden | de, fr, pt: alle 30 Ideenverweise der Gruppe → HTTP **200 ohne `Location`**, Pfad `/<Sprache>/roadmap/ideen/<Nummer>-<Titel>` (`ak15`) |
| AK-16 | ✅ bestanden | Dev-DB mit 12 geplanten Ideen: „2 weitere geplante Ideen stehen unter ‚Ideen‘.“ ist ein Link auf `/<Sprache>/roadmap/ideen?status=planned` (5 Sprachen); Ziel: Filter „planned“ ausgewählt, Leitzahl 12 (`ak16_ziel`) |
| AK-17 | ✅ bestanden | `main` `/de/community/ideen` gegen 16 `/de/roadmap/ideen`, angemeldet, gleiche DB, sechs Parametersätze: ab Steuerleiste **0 Abweichungen** (815 Zeilen), einzige Ausnahme der Leerzustand „Noch keine Idee veröffentlicht“ (BF-201, gewollt); Karten, Zustimmungsformulare, Sortierung, fünf Statusfilter, Blätterung, „Umgesetzt“, Leitzahl je gleich (`QA2-AK-17-ideenliste-alt-gegen-neu.txt`) |
| AK-18 | ✅ bestanden | `/<Sprache>/roadmap/ideen/94-qa16-0` → 200, Bereichskopf, „Ideen“ aktiv (5 Sprachen × 3 Breiten, `ak05`/`ak06`) |
| AK-19 | ✅ bestanden | Fünf Einreichungen über `/de/roadmap/ideen/neu` → 302 `/de/roadmap/ideen/eingereicht`, in der DB alle `published_at IS NULL`; Formular und Bestätigung in 5 Sprachen ohne Bereichskopf, Rückweg auf `/<Sprache>/roadmap/ideen` („Zurück zu den Ideen“, „Retour aux idées“ …) (`QA2-AK-19-formular-bestaetigung.txt`, `QA2-AK-34-deckel.txt`) |
| AK-20 | ✅ bestanden | Zustimmen mit gültigem Token (de, pt) → 302 `/<Sprache>/roadmap/ideen/97-qa16-3` (Stimme 0 → 1 → 0); aus der Liste → 302 auf die Idee; Zurückziehen der eigenen wartenden Idee 191 → 302 `/de/roadmap/ideen`, Zeile gelöscht (`QA2-AK-20-folgeseiten.txt`) |
| AK-21 | ✅ bestanden | `curl`, 3 Seitenadressen × 5 Sprachen: 15 × **301**, `Location` `/<gleiche Sprache>/roadmap/ideen[/neu\|/eingereicht]` (`ak21_22`); `Qa16RoadmapIdeenTest::testAk21…` (Sitzungssprache ≠ Pfad) grün |
| AK-22 | ✅ bestanden | `/<Sprache>/community/ideen/94-qa16-0` → 301 `/<Sprache>/roadmap/ideen/94-qa16-0` → 200, 5 Sprachen, **ein** Sprung (`ak21_22`, Kette `[301, 200]`) |
| AK-23 | ✅ bestanden | Freigabe (187) und Ablehnung (188) im Admin, Zustellung in Mailpit: beide Links **nur** `http://127.0.0.1:8000/de/roadmap/ideen/<Nummer>-<Titel>`; Statuswechsel 187 → planned: keine Mail (06 kennt nur die Veröffentlichungsmail) (`QA2-AK-23-mails.txt`). ⚠ Textteil: BF-208 |
| AK-24 | ✅ bestanden | 16 Angriffsadressen: `?next=https://example.com`, `?url=//example.com`, `?_target_path=…`, `?redirect=//evil.example` (auch an `/neu` und Einzeladresse) → Ziel immer `/de/roadmap/ideen…`; `//example.com`, `%2F%2F`, `https:%2F%2F`, `..%2F`, CRLF im Pfad → 404; `%5C%5C`, `@example.com` → 301 auf einen Pfad unter `/de/roadmap/ideen/`; `?_locale=en` ignoriert (`ak24`) |
| AK-25 | ✅ bestanden | `/sitemap.xml`: 160 Einträge, `/<Sprache>/roadmap/ideen` × 5, `/community/ideen` × 0 (auch in Sprachverweisen), `/roadmap` × 5, `/changelog` × 5, gültig gegen `sitemap-mit-sprachverweisen.xsd` (`ak25`). Die Summe der Speicherzeiten hält `SitemapControllerTest` (grün) |
| AK-26 | ✅ bestanden | 5 Sprachen × 5 Parametersätze: canonical `https://endlech.lu/<Sprache>/roadmap/ideen`, sechs Sprachverweise (lb/de/fr/en/pt + x-default); `?page=2` (auch mit Sortierung/Filter) bleibt in canonical und Sprachverweisen, `?page=1` und `?sort=` fallen weg (`ak26`) |
| AK-27 | ✅ bestanden | 4 Seitenarten × 5 Sprachen: Name, Reiter, Knopf in der jeweiligen Sprache (fr „Feuille de route & idées · Feuille de route · Idées · Journal · Proposer une idée“); Suche nach rohen Schlüsseln (`area.`, `footer.`, `roadmap.`, `board.` …) im sichtbaren Text und in `aria-label`/`title`/`placeholder`/`alt`: 0 (`ak27`) |
| AK-28 | ❌ **durchgefallen** | axe-core, 3 Reiter + Einzelseite + Formular × 5 Sprachen × 1280/375, angemeldet: Reiter und Formular **0** Verstöße außer der Wortmarke (1280 px, OF-02); **Einzelseite: `page-has-heading-one` in 10 von 10 Läufen** → BUG-08 / BF-207 (`QA2-AK-28-einzelseite-ohne-h1.txt`) |
| AK-29 | ✅ bestanden | 4 754 Anfragen (Gast, Admin, unbestätigt, Verfasser × 5 Sprachen × 12 Wege × 12 Parametersätze, Weiterleitungen gefolgt): Titel- und Beschreibungswörter der wartenden Ideen **nur** beim Verfasser auf seiner eigenen Idee (AK-33 erlaubt das); sonst nur dort, wo die angefragte Adresse selbst den Titelteil trug (Dev-Fehlerseite spiegelt die Adresse) (`QA2-AK-29-wartende-ideen.txt`) |
| AK-30 | ✅ bestanden | 1 200 Aufrufe (7 Adressen anonym, 5 angemeldet, je 100), `var/log/dev.log` DEBUG, 13 600 neue Zeilen: Anzeigenamen 0, Ideentitel/-beschreibungen aus dem Board 0. `user@endlech.lu` 500 × nur in `security.DEBUG: User was reloaded …` (Kennung des Besuchers selbst, H-2); Titelteil „qa16b-zebrafink…“ 100 × nur als Routenparameter der **angefragten** alten Adresse (`QA2-AK-30-protokoll.txt`) |
| AK-31 | ✅ bestanden | `Qa16RoadmapIdeenTest::testAk31ZaehlaufrufNeuerPfadOhneAbfrageteilUndOhneNeuesFeld` am nachgebildeten Umami-Eingang: `url: /de/roadmap/ideen` ohne Abfrageteil, Feldmenge gleich der alten Adresse (grün in der vollen Suite) |
| AK-32 | ✅ bestanden | Netzwerkmitschnitt, 3 Reiter × 5 Sprachen: je 41 Anfragen, **0** an einen anderen Host (`ak32`) |
| AK-33 | ✅ bestanden | Wartende 184 (Verfasser user@) und 185 (ohne Verfasser) unter `/…/roadmap/ideen/…`, 5 Sprachen, echter und falscher Titelteil, 12 Parametersätze: Gast, Admin, unbestätigt → **404**, Titel im Dokument nur, wo die Adresse ihn selbst trug; Verfasser → 200 (`QA2-AK-29-wartende-ideen.txt`) |
| AK-34 | ✅ bestanden | admin@ über die neue Adresse: Einreichen 1–5 → 302, **6 und 7 → 429** „Zu viele Einreichungen. Bitte versuche es in 60 Minuten erneut.“; auf `main` über die alte Adresse identisch. Zustimmen: 60 umgeschaltet, Nr. 61 → Folgeseite „Zu viele Zustimmungen. Bitte versuche es in 60 Minuten erneut.“ — auf `main` identisch. Alte Schreibwege existieren nicht mehr (POST `/neu` → 405, `/zustimmen` → 404), also kein Weg zu einem frischen Kontingent (`QA2-AK-34-deckel.txt`) · H-3 |
| AK-35 | ✅ bestanden | Dev-DB 45 veröffentlichte, 12 geplant. Profiler: Reiter Ideen `LIMIT 20` (Seite 3 `LIMIT 20 OFFSET 40`), Zählung, „Umgesetzt“ `LIMIT 20`, Stimmen `WHERE id IN (…≤ 21)`; Roadmap (Cache geleert) `LIMIT 10` + Zählung + Stimmen für 10; alte Adressen 0 Abfragen (`QA2-AK-35-abfragen.txt`) |
| AK-36 | ✅ bestanden | `/de/profile/daten` als user@ auf `main` und 16, gleiche DB: 32 Schlüssel (rekursiv) in beiden, keiner nur neu; `boardIdeas` (46) und `boardVotes` wertgleich (`QA2-AK-36-datenexport.txt`) |
| AK-37 | ✅ bestanden | Kataloge der OF-01-Schlüssel in 5 Sprachen ohne „Board/tableau/quadro/Ideeën“; gerenderte Roadmap, Einzelseite und Formular (dazu Liste und Bestätigung) in 5 Sprachen: 0 Treffer (`QA2-AK-37-kataloge.txt`) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `Qa16RoadmapIdeenTest::testEc01OhneFreigegebeneIdee` (Test-DB ohne freigegebene Idee: Leerzustand mit eigenem Weg zum Formular **plus** Knopf im Kopf; Roadmap ohne Community-Gruppe), grün; Leerzustandstext „Noch keine Idee veröffentlicht“ (`testBf201…`, grün) |
| EC-02 | ✅ bestanden | `/de/community/ideen/94-ganz-alter-titel` → 301 → 200 (Idee 94) (`ec02`) |
| EC-03 | ✅ bestanden | Dublette 186 (→ 94): `/de/community/ideen/186-qa16b-dublette` → 301 → 302 → `/de/roadmap/ideen/94-qa16-0` 200 — **zwei** Sprünge, Ziel das Original (`ec03`) |
| EC-04 | ✅ bestanden | `/roadmap` mit `Accept-Language: fr` → `/fr/roadmap`, mit `pt-PT` → `/pt/roadmap`; `/roadmap/ideen`, `/roadmap/ideen/94-qa16-0`, `/community/ideen` ohne Sprache → 404 (`ec04`) |
| EC-05 | ✅ bestanden | 320 px: pt-Reiter zweizeilig, fr-Band zweizeilig, keine Beschriftung abgeschnitten, kein Querscrollen (`ak08`, `QA2-AK-08-*-fr-320.png`, `…-pt-320.png`) |
| EC-06 | ✅ bestanden | `/de/community/ideen?status=planned&page=2` → 301 `/de/roadmap/ideen` (Filter fällt weg — zulässig) (`ec06`) |
| EC-07 | ✅ bestanden | Aktualitätshinweis („Stand:“, „Mise à jour :“, „Last updated:“, „Atualizado em:“) je 1 × auf Roadmap und Changelog, 0 × auf Reiter Ideen und Einzelseite, 5 Sprachen |

## Regression

Volle Testsuite: `php bin/phpunit` → **1947 Tests, 0 Fehlschläge**, 40 übersprungen, 14 299 Zusicherungen
(`QA2-volle-suite.txt`). Vor den neuen QA-Tests 1924 / 0 / 28 — gleich dem Abschlussbericht des Fehlerauftrags. Erster
Durchlauf: 1917 (dazwischen 7 Tests des Fehlerauftrags) — nichts fehlt.

Berührt laut `git diff --name-only main` (Arbeitsbaum gegen `main`, schließt den Footer-Commit ein) außerhalb des
Features: `config/packages/monolog.yaml` (nur `when@test`), `config/packages/twig.yaml`, `config/services.yaml`,
`src/Controller/BoardController.php`, `src/Seo/SeoRegistry.php`, `templates/board/{index,show}`,
`templates/roadmap/{index,changelog}`, `templates/partials/_footer`, `templates/impressum/index`, Kataloge
`messages`/`roadmap`/`changelog` (5 Sprachen), Dokumentation. Dazu *Systemweite Änderungen* beider Abschlussberichte
(Reihenfolge der Prüfungen in `vote()`, Freigabe-Mail in fünf Sprachen, Test-Handler im Monolog).
`doctrine:schema:validate`: Mapping korrekt, die Datenbank weicht ab — an `cuisine`, `ordering_option`, `user`,
`restaurant`, `restaurant_cuisine`, `restaurant_suggestion`, **keine** Board-Tabelle; das Feature ändert keine Entity
und keine Migration (H-5).

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| 06 | Board umgezogen, `vote()` umgebaut, Freigabe-Mail neu | ✅ bestanden | US-01 Einreichen (AK-19), Zustimmen/Zurückziehen (AK-20), Liste zeilengleich (AK-17), Freigabe/Ablehnung im Admin mit Mail (AK-23) |
| 07 | Kopf von Roadmap und Changelog ersetzt, „Weiterlesen“ entfernt | ✅ bestanden | Roadmap-Inhalt gegen `main` (AK-14); Changelog-Einträge de/fr/pt gegen `main`: 246 Zeilen, 0 Abweichungen (`QA2-Regression-changelog-legal.txt`); „In Arbeit“ im ersten Bildschirm (AK-11) |
| 10 | Sitemap, canonical, `SeoRegistry` | ✅ bestanden | AK-25, AK-26; `SeoRouteCoverageTest` grün |
| 11 | Zählpfad | ✅ bestanden | AK-31 |
| 02 | Kopf und Reiter auf vier Seitenarten | ✅ bestanden — mit BF-206 (vorbestehend) | Tastatur (AK-09), 320 px (AK-08), axe (AK-28, BF-207) |
| 15 | Fußzeile | ✅ bestanden | Fußzeile `main` gegen 16: „Sponsor werden →“ und Bereich „Sponsoren“ unverändert (`QA2-Regression-fusszeile-main-gegen-16.txt`) |
| 03, 08 | Fußzeile (Vergleiche, App-Warteliste) | ✅ bestanden | dieselbe Gegenüberstellung: vier Vergleichslinks und „App-Warteliste“ unverändert |
| B13 | Impressum (Footer-Commit) | ✅ bestanden | `/<Sprache>/legal` 5 × 200, Anker `#datenschutz` |
| B02 | Anmeldung über den Knopf | ✅ bestanden | echtes Anmeldeformular, 5 Sprachen (AK-12) |

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR), lesend | bestanden (mit BF-209) | wartende fremde Ideen unter neuer und alter Adresse: Gast/Admin/unbestätigt 404 (AK-33, AK-29, 4 754 Anfragen). ⚠ Nach einem handgemachten Admin-POST, der eine veröffentlichte Idee als Dublette einer wartenden führt, steht deren Titelteil im `Location`-Kopf für jeden Gast (BF-209, vorbestehend) |
| Zugriff auf fremde ID, schreibend | bestanden | Zustimmen auf wartende Idee mit leerem/falschem Token: **404 ohne `Location`** für drei Konten (BF-199 behoben); Zurückziehen fremder wartender Ideen: admin@ und unverified@ **403**, Titel 0 ×, Zeile bleibt; anonymes Zustimmen → Anmeldung; GET auf `/zustimmen` → 405 |
| Zugriffsregeln serverseitig | bestanden | `/de/admin/ideen` als user@ → 403, Freigabe-POST als user@ → 403; Formular und Bestätigung als Gast → Anmeldung, Formular als unbestätigt → `/de/verify`; alte Schreibwege 405/404 (`QA2-Angriff-eingaben-zugriff.txt`) |
| Rate Limit greift | bestanden | Einreichen Nr. 6 → 429, Zustimmen Nr. 61 gedeckelt, beides am Konto und wortgleich zu `main` (AK-34). Alte Adressen ohne Deckel wie entschieden (Decision Log 9): 0 Datenbankabfragen (AK-35). Zustimmen mit falschem Token auf eine **veröffentlichte** Idee ist weiter ungedeckelt — verrät nichts mehr |
| Eingaben | bestanden | Titelteil der alten Adresse: 10 000 Zeichen → 301 (Ziel 200), Emoji und `'; drop table board_idea; --` → 301, kodiert; `<script>` und `../../etc/passwd` (kodiert) → 404; Nummer 0 und 20-stellig → 301, Ziel 404; `?status[]=new`, `?page=abc` → 400 (vorbestehend); `?page=-5`, `?page=99999`, `?sort='…`, `?status=<script>` → 200, nichts unkodiert im Dokument (`QA2-Angriff-eingaben-zugriff.txt`) |
| PII in Logs | bestanden | AK-30; H-2 (Kennung des angemeldeten Besuchers im `security`-Kanal, vorbestehend) |
| PII an externe Dienste | bestanden | Umami: Pfad ohne Abfrage, Feldmenge unverändert (AK-31); keine fremde Ressource (AK-32); Freigabe-Mail: Titel und Link der eigenen Idee, an den Verfasser (AK-23) |
| Geheimnisse im Repository | bestanden | Muster (`sk_live_`, `sk-…`, `xkeysib-`, `PRIVATE KEY`, `api_key=`/`secret=`/`password=` mit Literal, Zugangsdaten in URLs, Sentry-DSN) über `git diff main` und alle neuen Dateien: 0 Treffer außer einer Zeile im alten Bericht, die die Muster aufzählt; das Feature braucht keinen Schlüssel |
| Löschen | trifft nicht zu | Keine Entity, keine Migration, kein Feld; Datenexport unverändert (AK-36) |

## Fehler

### BUG-08 / BF-207 · Einzelseite einer Idee ohne Hauptüberschrift — axe meldet `page-has-heading-one` — mittel

**Betrifft:** AK-28 („die Einzelseite einer Idee … null Verstöße — in allen fünf Sprachen“); OF-02 nimmt nur die Wortmarke
aus. Vorbestehend aus Feature 06 (auf `main` derselbe Treffer an `/de/community/ideen/94-qa16-0`).
**Reproduktion:**
1. `/de/roadmap/ideen/94-qa16-0` in einem Browser **ohne Debug-Toolbar** öffnen (Produktion, oder `/_wdt` blockieren).
2. axe-core mit Standard-Regelsatz ausführen.
**Erwartet:** null Verstöße (außer der Wortmarke bei ≥ 1280 px).
**Tatsächlich:** `page-has-heading-one` (Best-Practice-Regel, Auswirkung *moderate*) — in lb, de, fr, en, pt, bei 1280 und
375 px. Die Seite hat keine `h1`; der Titel der Idee steht als `h3` in der Karte (`partials/_board_idea_card.html.twig`),
der Bereichskopf trägt bewusst keine Überschrift (Entwurf, Entscheidung 9; AK-13 verlangt die `h1` nur auf den Reitern).
**Warum es zweimal grün war:** Mit **geladener** Debug-Toolbar kann axe die Regel als bestanden werten — nachgestellt
mit dem Prüfskript des ersten Durchlaufs (1280 × 720, `runOnly: ['page-has-heading-one']` → „passes“, auf 16 und `main`);
in einem zweiten Aufbau (375 px, Standard-Regelsatz) meldete axe den Treffer auch mit Toolbar. Blockiert und entfernt
erscheint er immer. Beide früheren Messungen liefen gegen den Entwicklungsserver mit Toolbar; wie genau sie zu „null“
kamen, ist nicht mehr nachvollziehbar — die Toolbar ist die belegte Störgröße (H-4).
**Ort:** `templates/board/show.html.twig` (Karte ohne Überschriftenebene 1), `templates/partials/_board_idea_card.html.twig`
**Vorschlag:** Auf der Einzelseite den Ideentitel als `h1` ausgeben (Ebene der Karte als Parameter, wie bei
`roadmap/_item.html.twig`); danach axe ohne Toolbar erneut. Wer stattdessen Best-Practice-Regeln aus AK-28 nehmen will,
klärt das über `/sdd-klaeren 16` — die Spec sagt heute „null Verstöße“.
**Prüflauf:** `Qa16NachpruefungTest::testBf207EinzelseiteHatGenauEineHauptueberschrift` (5 Sprachen; übersprungen, scharf
mit `QA16_REPRO=1`: rot).
**Schweregrad mittel, nicht hoch:** Das Kriterium ist auf einer von fünf Seitenarten verfehlt, kein Hauptweg ist gestört,
die Regel ist eine Best-Practice-Regel (keine WCAG-A/AA-Anforderung), und der Zustand läuft seit Feature 06 auf der
Produktion — die Auslieferung von 16 macht ihn nicht schlechter. So hat der erste Durchlauf die verfehlten Teilfälle von
AK-05 und AK-11 gewertet (BF-200, BF-202).

### BUG-09 / BF-206 · Globaler Fokus-Fallback übersteuert jede Fokusfarbe — Knopf auf dem Band 1,5–1,65 : 1 — mittel, vorbestehend (projektweit)

**Betrifft:** AK-09 (bestanden, siehe Vorbehalt 3), 02/AK-03 („auf jedem Untergrund sichtbar — auch auf den farbigen
Verlaufsbändern“), WCAG 1.4.11; Design-System „Auf Verlaufsgrund … `focus:outline-white`“.
**Reproduktion:**
1. `/de/roadmap` bei 1280 × 800 öffnen, per Tab auf „Idee einreichen“ (10. Station).
2. 600 ms warten (`motion-safe:transition` blendet `outline-color` über 150 ms ein), `getComputedStyle(knopf).outlineColor`
   lesen und den Rahmen gegen das Band messen.
**Erwartet:** weißer Rahmen (`focus:outline-white` steht am Knopf), ≥ 3 : 1 gegen das Band.
**Tatsächlich:** `rgb(14, 116, 144)` (cyan-700); an den geänderten Pixeln 1,58 : 1 (Median), höchstens 1,65 : 1 bei
1280 px, 1,46/1,63 bei 375 px. Auf `main` ebenso: Board-Knopf im alten Kopfband 1,27/1,32 : 1, Hero-Knopf der Startseite
1,07/1,20 : 1 (`QA2-BF-206-fokusfarbe-ursache.txt`, Bilder `QA2-BF-206-knopf-*.png`).
**Ursache:** `assets/styles/app.css:98` — `:focus-visible { outline: 2px solid #0e7490 }` steht ungeschichtet; Tailwinds
`focus:outline-*` liegt in `@layer utilities`. Ungeschichtete Deklarationen schlagen geschichtete unabhängig von der
Spezifität. Gegenprobe im Browser: dieselbe Regel in `@layer base` → der Knopf bekommt `rgb(255, 255, 255)`. Der
Kommentar über der Regel („… die diesen Fallback durch höhere Spezifität überschreibt“) trifft unter Tailwind v4 nicht zu.
Betrifft jede `focus:outline-<farbe>` im Projekt, sichtbar schwach vor allem auf Verlaufsbändern (zehn Vorlagen).
**Ort:** `assets/styles/app.css:98`
**Vorschlag:** Die Regel in `@layer base { … }` legen und die Bänder neu messen — projektweit, nicht nur hier.
**Prüflauf:** `Qa16NachpruefungTest::testBf206GlobalerFokusFallbackLiegtInEinerKaskadenebene` (übersprungen, scharf: rot;
Gegenprobe mit `@layer base` in `app.css` → grün, Datei danach byte-gleich zurück). Maßgeblich bleibt die Browsermessung.

### BUG-10 / BF-208 · Textteil der Freigabe-Mail mit HTML-Entitäten („Roadmap &amp; Ideen“) — niedrig, vorbestehend (projektweit)

**Betrifft:** Text aus dem Fehlerauftrag zu BF-201; kein Kriterium (AK-23 verlangt den Link, der stimmt).
**Reproduktion:** Eine wartende Idee (Sprache de) im Admin freigeben, Mail zustellen, den `text/plain`-Teil im
MIME-Quelltext lesen.
**Tatsächlich:** „Deine Idee steht jetzt unter „Roadmap &amp; Ideen““, dazu `&copy; 2026 Endlech.lu`. In fr steht
„Feuille de route &amp; idées“ und — schon auf `main`, Freigabe und Darstellung mit dem Code von `main` —
„l&#039;avons publiée“, „Voir l&#039;idée“. pt hat kein „&“, aber `&copy;`.
**Ursache:** `TemplatedEmail` ohne `textTemplate`; Symfony bildet den Textteil mit `DefaultHtmlToTextConverter`
(`strip_tags`, ohne `html_entity_decode`). Gilt für jede Mail des Projekts ohne eigene Textvorlage.
**Ort:** `src/Board/BoardNotifier.php:62` (ohne `textTemplate`), Mail-Grundlayout `templates/email/base.html.twig` (`&copy;`)
**Vorschlag:** Einen eigenen `HtmlToTextConverterInterface`-Dienst mit `html_entity_decode` registrieren (wirkt für alle
Mails) oder Textvorlagen ergänzen.
**Prüflauf:** `Qa16NachpruefungTest::testBf208TextteilDerFreigabeMailOhneHtmlEntitaeten` (5 Sprachen; übersprungen,
scharf: rot).

### BUG-11 / BF-209 · Dubletten-Weiterleitung verrät den Titelteil eines wartenden Originals — niedrig, vorbestehend (06)

**Betrifft:** Spec H·3 („eine wartende Idee sieht nur ihr Verfasser“), Ziel von AK-29/AK-33; aus dem `code-reviewer`,
am Server nachgestellt. Vorbestehend aus Feature 06 (auf `main` identisch).
**Reproduktion:**
1. Wartende Idee O (199, „QA16D Wartendes Original Pfauenauge“) und veröffentlichte Idee D (200) anlegen.
2. Als Admin `POST /de/admin/ideen/200/dublette` mit gültigem Token und `target=199` — von Hand: Die Auswahl im Formular
   bietet O nicht an (`findPublishedForAdmin()`), der Controller nimmt das Ziel trotzdem (302, `duplicate_of_id = 199`).
3. Als Gast `/de/roadmap/ideen/200-qa16d-veroeffentlichte-dublette` oder `/fr/roadmap/ideen/200-x` aufrufen.
**Erwartet:** Kein Titelteil der wartenden Idee in der Antwort (404 oder Weiterleitung ohne Titelteil).
**Tatsächlich:** `302 Location: …/de/roadmap/ideen/199-qa16d-wartendes-original-pfauenauge` (fr ebenso). Über die alte
Adresse `/de/community/ideen/200-x` nach dem 301 dasselbe; auf `main`
`302 Location: …/de/community/ideen/199-qa16d-wartendes-original-pfauenauge`.
**Ort:** `src/Controller/BoardController.php:167` (Weiterleitung vor der Sichtbarkeitsprüfung `:188`),
`src/Controller/AdminBoardController.php:147` und `src/Board/BoardModerator.php:154` (kein `isPublished()` am Ziel)
**Vorschlag:** In `show()` die Sichtbarkeit zuerst prüfen und nur auf ein veröffentlichtes Original weiterleiten; in
`merge()` ein unveröffentlichtes Ziel abweisen.
**Prüflauf:** `Qa16NachpruefungTest::testBf209DubletteVerraetWartendesOriginalNicht` (übersprungen, scharf: rot).

### BUG-12 / BF-210 · Prüflauf zur Überschrift der Community-Gruppe kann nicht rot werden — niedrig

**Betrifft:** Absicherung von AK-37 (das Kriterium selbst ist unabhängig belegt); aus dem `code-reviewer`, nachgestellt.
Muster BF-205.
**Reproduktion:** In `templates/roadmap/index.html.twig` die Gruppenüberschrift testweise fest auf „Aus dem
Ideen-Board / Board / tableau / quadro / Ideeën-Board“ setzen, Testcache leeren, `php bin/phpunit`.
**Erwartet:** `RoadmapAreaLocaleTest::testAk37OrtHeisstIdeen` wird rot.
**Tatsächlich:** **1941 Tests, 0 Fehlschläge** — die ganze Suite bleibt grün. Der Test liest `$gruppe->filter('h3')->text()`,
also das erste `h3` der Spalte „Geplant“: ein kuratiertes Vorhaben. Nur die Katalogschleife davor fängt etwas — aber nur
Änderungen im Katalog, nicht im Template. (Template danach byte-gleich zurück, `cmp`.)
**Ort:** `tests/Functional/Controller/RoadmapAreaLocaleTest.php:125`
**Vorschlag:** Die Gruppe selbst greifen (`section[aria-labelledby="stage-planned"] div.border-t > h3`) — wie
`Qa16NachpruefungTest::testAk37GruppenueberschriftUndVerweiseOhneBoard`, der bei derselben Gegenprobe in allen fünf
Sprachen rot wird.

### Behoben aus dem ersten Durchlauf

BUG-01 bis BUG-07 (BF-199 bis BF-205) — siehe *Nachprüfung der früheren Befunde*; die vollständigen Beschreibungen samt
Behebungsvermerken stehen im Archiv `qa/16/qa-report-erster-durchlauf-2026-11-07.md`.

## Code-Review

`code-reviewer`-Agent über alle Dateien des Features (Arbeitsbaum gegen `HEAD` plus neue Dateien), mit `spec.md`,
`design.md`, beiden Abschlussberichten und `CLAUDE.md`; Schwerpunkt auf dem, was der Fehlerauftrag geändert hat. Er hat
nur gelesen. Seine Funde, jeder nachgeprüft:

| Fund des Reviewers | Nachprüfung | Ergebnis |
|---|---|---|
| Fokusrahmen am Knopf cyan-700 statt weiß (ungeschichtete globale Regel) | von der QA vorher unabhängig gefunden und im Browser gemessen, Ursache per Gegenprobe | bestätigt → BF-206 |
| Textteil der Freigabe-Mail mit `&amp;` (Folge von BF-201), fr `&#039;` vorbestehend | MIME-Quelltext aus Mailpit, 16 und `main` | bestätigt → BF-208 (von der QA unabhängig gefunden) |
| Einzelseite ohne `h1`, axe `page-has-heading-one` | axe ohne Toolbar, 10 von 10 Läufen | bestätigt → BF-207 (von der QA unabhängig gefunden) |
| Kein Lauf belegt, dass `vote()` einen falschen Token abweist (nach dem Umsortieren) | `grep` über alle Board-/Roadmap-Tests: keiner; Verhalten am Server stimmt (302 + Meldung, keine Stimme) | bestätigt als **Testlücke**, kein Fehlverhalten → in `testBf199Bestaetigtes…` geschlossen (Stimmenzahl und Meldung); Gegenprobe mit `if (false && …)` an der CSRF-Zeile: **rot** („▲ 1 Zugestimmt“), Controller danach byte-gleich zurück |
| `testAk37…` prüft die Gruppenüberschrift nicht | Mutation im Template, volle Suite grün | bestätigt → BF-210 |
| Dubletten-Weiterleitung vor der Sichtbarkeitsprüfung, `merge()` ohne Prüfung des Ziels | am Server mit handgemachtem Admin-POST nachgestellt, auf `main` ebenso | bestätigt → BF-209 |
| `Qa16RoadmapIdeenTest::testAk24…`: `headers->has('x')` kann nie anschlagen | gelesen: die eingeschleuste Kopfzeile hieße `Set-Cookie` | bestätigt — QA-eigener Test, Zusicherung auf den Wert von `Set-Cookie` geschärft; kein Befund |
| `testAk13KetteLueckenlosMitIdeen`: erster Vergleich immer wahr | schon im ersten Durchlauf so gewertet | kein Befund (die Kette sichert `testAk13EigeneTitelUndGenauEineH1`) |
| `withdraw()` 403 gegen `vote()` 404 verrät die Existenz einer wartenden Nummer | am Server: 403 bzw. 404, Titel 0 × | kein Befund — Nummern sind fortlaufend, die Existenz ist ohnehin ablesbar; kein Inhalt |
| `aria-current="page"` auf der Einzelseite statt `true` | AK-06 verlangt „Ideen“ als aktuelle Seite | verworfen — so spezifiziert |
| Luxemburgisch „Nach eng geplangten Iddi“ (`roadmap.lb.yaml:33`) | nicht prüfbar ohne Muttersprache | verworfen als Befund, offen für den Betreiber (wie im ersten Durchlauf) |

Der Reviewer bestätigt außerdem als in Ordnung: die Reihenfolge in `vote()` und `withdraw()`, den
Weiterleitungs-Controller (keine offene Weiterleitung, keine Abfrage), die vier `UNLISTED_ROUTES`, die Kataloge und das
Escaping von „&“ im HTML.

## Hinweise (keine Befunde)

- **H-1 · 320 × 800 mit Cookie-Banner, fr und pt:** „En cours“/„Em curso“ endet bei 652/659 px, das Banner beginnt bei
  601 px. Nicht Teil von AK-11 (1280 und 375). Auf `main` im selben Fall ebenfalls verdeckt (628 px) — vorbestehend. Ab
  360 px Breite in allen Sprachen frei und 12 px höher als auf `main`.
- **H-2 · `security`-Kanal im Produktionspuffer** (aus dem ersten Durchlauf, unverändert): Bei jedem angemeldeten Aufruf
  schreibt Symfony `User was reloaded from a user provider {"username":"<E-Mail>"}` (DEBUG); `when@prod` schließt den
  Kanal aus `fingers_crossed` nicht aus. Vorbestehend (B02).
- **H-3 · Deckel überleben kein Ausrollen** (aus dem ersten Durchlauf): Die Limiter speichern in `cache.rate_limiter`
  unter `var/cache`, das Image führt `var/` nicht als Volume. Jeder Containertausch beginnt mit leeren Zählern — auch
  dieser. Vom Pfad unabhängig; AK-34 ist so gelesen bestanden.
- **H-4 · Prüfwerkzeuge:** Chromes Barrierefreiheitsbaum (CDP) und Playwrights ARIA-Abbild führen `aria-current` nicht —
  ein Screenreader-Lauf (VoiceOver, NVDA) schlösse Vorbehalt 2. Und: **axe-Läufe gegen den Entwicklungsserver müssen die
  Debug-Toolbar blockieren**, nicht nur nach dem Laden entfernen — sie lädt nach und verändert das Ergebnis (BF-207).
- **H-5 · `lint:container` und Schema:** `lint:container` scheitert vorbestehend am Webauthn-Alias (auch auf `main`);
  `doctrine:schema:validate` meldet Abweichungen an sechs Nicht-Board-Tabellen (Indexnamen, `TINYINT`, `DATETIME`) — das
  Feature ändert weder Entity noch Migration. `lint:twig` (140 Dateien), `lint:yaml` (63) und `npm run typecheck` grün;
  `vendor/bin/php-cs-fixer` fehlt im Projekt.
- **H-6 · Schrägstrich am Ende und alte Schreibwege** (aus dem ersten Durchlauf, unverändert): `/de/community/ideen/`
  braucht zwei Sprünge; ein vor dem Ausrollen geöffnetes Formular endet mit 405. Nicht zugesagt (Decision Log 12).
- **H-7 · Seitenkopf auf grauem Grund:** Unter der Reiterleiste stehen `h1` und Einleitung auf dem Seitenhintergrund
  (`bg-gray-50`), der Inhalt darunter auf Weiß — sichtbar als Kante bei 320 px (`QA2-AK-08-roadmap-fr-320.png`). Kosmetik,
  kein Kriterium.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/Qa16NachpruefungTest.php` | `testBf199BestaetigtesFremdesKontoErfaehrtNichtsUeberWartendeIdee` | BF-199 — bestätigtes fremdes Konto, drei Token; Kehrseite: veröffentlichte Idee mit falschem Token → Meldung, **keine Stimme** (rot bei abgeschalteter CSRF-Prüfung) |
| | `testAk05BandUeberDenReiternIstAufAllenSeitenartenGleich` (5 Sprachen) | AK-05 / BF-200 — Band byte-gleich auf vier Seitenarten und einer Folgeseite, Leiste direkt darunter |
| | `testAk37GruppenueberschriftUndVerweiseOhneBoard` (5 Sprachen) | AK-37 / BF-210 — Gruppenüberschrift, zehn Verweise, „N weitere“ (rot mit „Ideen-Board“ im Template) |
| | `testBf207EinzelseiteHatGenauEineHauptueberschrift` (5 Sprachen) | BF-207 / AK-28 — Reproduktion, übersprungen |
| | `testBf206GlobalerFokusFallbackLiegtInEinerKaskadenebene` | BF-206 — Reproduktion, übersprungen (Gegenprobe mit `@layer base`: grün) |
| | `testBf208TextteilDerFreigabeMailOhneHtmlEntitaeten` (5 Sprachen) | BF-208 — Reproduktion, übersprungen |
| | `testBf209DubletteVerraetWartendesOriginalNicht` | BF-209 — Reproduktion, übersprungen |
| `tests/Functional/Controller/Qa16RoadmapIdeenTest.php` (erster Durchlauf) | `testAk24TitelteilBleibtImZielKodiert` | Zusicherung „keine eingeschleuste Kopfzeile“ prüft jetzt den Wert von `Set-Cookie` statt einer Kopfzeile `x` |

Browsermessungen (AK-05 bis -13, -27, -28, -32, Etikett-Kontrast, Fokusfarbe) laufen nicht in PHPUnit; die Skripte lagen
unter `/tmp/qa16b/`, die Messwerte stehen in `qa/16/QA2-browser-messwerte.json`, die HTTP-Messungen in
`qa/16/QA2-http-messwerte.json`.

## Testdaten

In der Dev-Datenbank angelegt und nach der Prüfung wieder entfernt: 46 Ideen (153–200: `qa16b-*`, `QA16C …`, `QA16D …` —
darunter die fünf Deckelproben je Server, zwei französische Freigaben über `main` und das Dubletten-Paar aus BF-209). Alle
Stimmen der Prüfung sind zurückgenommen (`board_vote` leer wie vorher). Die Ideen `qa16-0` … `qa16-14` des Baus bleiben
(15 veröffentlicht, 12 geplant). Danach geleert: `cache.roadmap` und `cache.rate_limiter` der Entwicklungsumgebung (die
Deckel von admin@ waren erschöpft). Vier Mails dieses Durchlaufs aus Mailpit gelöscht; die zwei des ersten Durchlaufs
bleiben. Messenger-Warteschlange leer. Wegwerf-Arbeitsbaum `/tmp/qa16b-main` und sein Server entfernt. Der
Entwicklungsserver auf Port 8000 läuft weiter.

Gegenproben am Code (alle danach byte-gleich wiederhergestellt, per `cmp` geprüft, Testcache geleert):
`BoardLegacyRedirectController.php` (Protokollzeile, BF-205), `BoardController.php` (CSRF-Prüfung abgeschaltet),
`templates/roadmap/index.html.twig` („Ideen-Board“ fest, BF-210), `assets/styles/app.css` (Regel in `@layer base`, BF-206).

## Nächster Schritt

1. **`/sdd-deploy 16`** — production-ready mit den drei Vorbehalten oben.
2. Empfohlen vorher, blockiert nicht: **`/sdd-build 16`** mit BF-207 (eine `h1` auf der Einzelseite) und BF-210 (Prüflauf
   schärfen), danach `/sdd-qa 16` für AK-28. Soll AK-28 Best-Practice-Regeln nicht umfassen, vorher `/sdd-klaeren 16`.
3. Eigenständig, vorbestehend: BF-206 (Fokus-Fallback in eine Kaskadenebene, alle Verlaufsbänder neu messen — Feature 02 /
   Design-System), BF-208 (Textteil der Mails), BF-209 (Dubletten-Weiterleitung und `merge()` — Feature 06), BF-203
   (übrige neun Glas-Etiketten).
