# 16 · Roadmap und Ideen in einem Bereich — Testbericht

Stand: 2026-11-07 · Geprüft gegen `spec.md` vom 2026-11-07 · in eigener Session, ohne Bauverlauf

Geprüft am Arbeitsbaum von `feature/16-roadmap-und-ideen` (Feature nicht committet, `HEAD` = Footer-Commit
`951594e`), Entwicklungsserver `http://127.0.0.1:8000` gegen die Dev-Datenbank. Zum Vergleich „vor der
Umstellung“ lief `main` (`536cbad`) in einem Wegwerf-Arbeitsbaum auf Port 8001 gegen **dieselbe**
Datenbank. Browser: Brave headless über playwright-core 1.62.1, axe-core 4.13.0. Belege unter `qa/16/`
(Dateien mit `QA-`-Präfix und alle `.txt` aus dieser Prüfung; `AK-08-ideen-fr-320.png` und
`AK-10-AK-11-roadmap-*.png` stammen aus dem Bau).

## Fazit

**Production-ready: nein**

Das Feature selbst trägt: Fußzeile, drei Reiter, alte Adressen mit 301 in einem Sprung, Mail-Links,
Sitemap, maßgebliche Adressen, Deckel und Datenexport verhalten sich wie spezifiziert, die Ideenliste ist
zeilengleich mit der alten (814 Zeilen, 0 Abweichungen), keine wartende Idee erscheint auf einem Reiter
oder hinter einer alten Adresse (2440 Aufrufe). Blockierend ist **BF-199** (*hoch*): Ein Zustimmen mit
falschem Token auf eine **wartende fremde** Idee leitet auf deren Einzeladresse weiter — der
`Location`-Kopf trägt den aus dem Titel gebildeten Titelteil. Jedes angemeldete Konto, auch ein
unbestätigtes, liest so die Titel der Moderationsschlange mit, ohne Deckel. Der Fehler steckt seit
Feature 06 im Code und ist auf der Produktion erreichbar; Feature 16 übernimmt die Zugriffsregeln
„unter der neuen Adresse“ (Spec H·3) — und damit diese Lücke. Durchgefallen sind außerdem **AK-05**
(die Reiterleiste steht je Seitenart an anderer Höhe, BF-200, hängt an einer Entscheidung) und **AK-11**
(französisch, 375 × 800, erster Besuch: der neue Kopf schiebt „En cours“ unter das Cookie-Banner, BF-202).
AK-28 ist nach der Wertung, die OF-02 der QA überlässt, bestanden. Bei AK-06 ist das Attribut belegt, das die
Ansage „aktuelle Seite“ auslöst; eine Screenreader-Ausgabe wurde nicht abgehört.

Nicht blockierend dazu: BF-201 (vier Texte sagen weiter „Board“), BF-203 (Etikett im Kopf unter 4,5 : 1 — vorbestehendes
Muster, von axe nicht erkannt), BF-204 (README) und BF-205 (zwei Prüfläufe des Baus können nicht rot werden).

Nächster Schritt: **`/sdd-klaeren 16`** (BF-200 Form des Kopfs, BF-201 Reichweite von OF-01, OF-02
bestätigen), danach **`/sdd-build 16`** mit BF-199 (und den freigegebenen übrigen), dann erneut `/sdd-qa 16`.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 37 von 37 |
| davon bestanden | 35 |
| davon durchgefallen | 2 (AK-05, AK-11) |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 7 von 7 |
| Tests neu geschrieben | 11 Fälle in `Qa16RoadmapIdeenTest` (davon 2 Reproduktionen, übersprungen bis zur Behebung) |
| Tests grün | 9 von 9 (Reproduktionen scharf mit `QA16_REPRO=1`: beide rot, wie erwartet) |
| Volle Suite gelaufen | 1917 Tests, 0 Fehlschläge, 30 übersprungen (vor den QA-Tests: 1907, 0, 28) |
| Regression: berührte Features | 8, davon Hauptweg bestanden 8 |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | 8 Seiten × 5 Sprachen (Start, Liste, Über uns, drei Reiter, `/open`, `/sponsoren`): genau **ein** Bereichslink, Gruppe „Mitmachen“/„Matmaachen“/„Participer“/„Get involved“/„Participar“, Ziel `/<Sprache>/roadmap`, ohne `target` · `qa/16/AK-01-03-fusszeile.txt` |
| AK-02 | ✅ bestanden | Dieselben 40 Seiten: kein weiterer Link auf `/roadmap`, `/roadmap/ideen`, `/changelog`, `/community/ideen`; die **alten** Beschriftungen aller fünf Sprachen (aus `HEAD:translations/messages.*.yaml`, z. B. „Retours & Idées“, „Opiniões e ideias“) als Fußzeilenlink: 0 Treffer · Hinweis H-1 |
| AK-03 | ✅ bestanden | Ein Rückmeldeweg in den Bereich, `target` fehlt (`AK-01-03-fusszeile.txt`). Gelesen wie die QA von 06/AK-80 („Feedback-Verweis“) — siehe H-1 |
| AK-04 | ✅ bestanden | `curl` anonym, 3 Reiter × 5 Sprachen: 15 × HTTP 200, keine Weiterleitung, kein Feld `_password` im Dokument |
| AK-05 | ❌ durchgefallen | Name und Reiter wortgleich, Reihenfolge stimmt (4 Seitenarten × 5 Sprachen × 3 Breiten). **Aber nicht an derselben Stelle:** Oberkante der Reiterleiste bei 1280 px (de) Roadmap 353, Ideen 353, Changelog 323, Einzelseite **225** px; bei 375 px (fr) 402 / 432 / 411 / **253** px — siehe BF-200 · `qa/16/AK-05-reiterleiste-position.txt`, `qa/16/QA-AK-05-*.png` |
| AK-06 | ✅ bestanden | Aktiver Reiter `font-weight 700` + `border-bottom 4px solid` cyan-700, inaktive 400 + transparenter Rand (berechnete Stile, 4 Seitenarten × 5 Sprachen × 2 Breiten); genau ein Link mit `aria-current="page"`, auf der Einzelseite „Ideen“ · `qa/16/QA-layout-messwerte.json` (`ak06`). Geprüft ist das Attribut, das die Ansage auslöst; eine Screenreader-Ausgabe wurde nicht abgehört (Chromes CDP-Baum führt `aria-current` nicht als Eigenschaft, `qa/16/AK-06-AK-09-ax-baum-und-enter.txt`) |
| AK-07 | ✅ bestanden | Brave **ohne JavaScript**: 4 Seitenarten × 3 Reiter × 5 Sprachen = 60 Klicks, je **eine** Navigation, Adresse = `href` des Reiters, `aria-current` danach am Ziel · `QA-browser-messwerte.json` (`ak07`, 0 Fehlschläge) |
| AK-08 | ✅ bestanden | 320 px, 4 Seitenarten × 5 Sprachen: `scrollWidth` = `clientWidth` = 320, alle Reiter im Fenster, kleinster Reiter **44 × 44** (Grenzwert genau erreicht), fr/pt zweizeilig ohne Abschneiden · `QA-layout-messwerte.json` (`ak08`), `qa/16/QA-AK-08-*-320.png` |
| AK-09 | ✅ bestanden | 3 Reiter × 5 Sprachen, Tab vom Seitenanfang: Knopf (10.), Roadmap (11.), Ideen (12.), Changelog (13.), je `outline: solid 2px`; Enter auf jedem öffnet sein Ziel (Gast: Knopf → Anmeldung) · `QA-browser-messwerte.json` (`ak09`), `AK-06-AK-09-ax-baum-und-enter.txt`, `qa/16/QA-AK-09-fokus-reiter-ideen.png` |
| AK-10 | ✅ bestanden | 1280 × 800 und 375 × 800, 3 Reiter × 5 Sprachen, mit sichtbarem und weggeklicktem Cookie-Banner: 60 Messungen, Unterkante des Knopfs höchstens **221 px**, nie verdeckt (`elementFromPoint`) · `QA-layout-messwerte.json` (`ak10`), `qa/16/QA-AK-10-*.png` |
| AK-11 | ❌ durchgefallen | 19 von 20 Fällen frei (höchste Unterkante 689 px). **fr, 375 × 800, erster Besuch:** „En cours“ bei 661–689 px liegt **unter dem Cookie-Banner** (ab 601 px), `elementFromPoint` trifft `#cookie-banner-desc`. Auf `main` stand die Überschrift im selben Fall bei 550–578 px, frei — siehe BF-202 · `qa/16/QA-AK-11-fr-375x800-vorher-main.png`, `…-nachher.png` |
| AK-12 | ✅ bestanden | Browser, echtes Anmeldeformular, de und fr: Gast klickt „Idee einreichen“ → Anmeldung → nach dem Absenden `/<Sprache>/roadmap/ideen/neu` mit Formular; unbestätigtes Konto → `/<Sprache>/verify` mit „Bitte bestätige zuerst deine E-Mail-Adresse.“ / „Veuillez d’abord confirmer …“ · `QA-browser-messwerte.json` (`ak12`) |
| AK-13 | ✅ bestanden | 5 Sprachen: drei verschiedene Titel je Sprache (z. B. „Roadmap – Endlech.lu“, „Ideen für Endlech.lu · Endlech.lu“, „Changelog – Endlech.lu“), je **eine** `h1`, keine Sprungstelle in der Überschriftenkette (Toolbar entfernt) · `QA-browser-messwerte.json` (`ak13`) |
| AK-14 | ✅ bestanden | 5 Sprachen: drei Spalten `in_progress`/`planned`/`considered`, Community-Gruppe mit 10 Ideen, „Stand: 28. Oktober 2026“, „Bewusst nicht gebaut“, **kein** „Weiterlesen“ (`more-heading` fehlt) |
| AK-15 | ✅ bestanden | de und fr: alle 10 Ideenverweise der Gruppe → HTTP 200, `num_redirects=0`, Pfad `/<Sprache>/roadmap/ideen/<Nummer>-<Titel>` |
| AK-16 | ✅ bestanden | Dev-DB mit 12 geplanten Ideen: „2 weitere geplante Ideen stehen unter „Ideen“.“ ist ein Link auf `/<Sprache>/roadmap/ideen?status=planned` (5 Sprachen); Ziel: Filter „Geplant“ ausgewählt, Leitzahl 12 |
| AK-17 | ✅ bestanden | Alt (`main`, `/de/community/ideen?sort=votes`) gegen neu (`/de/roadmap/ideen?sort=votes`), angemeldet, gleiche Datenbank: Inhalt ab Steuerleiste **814 Zeilen, 0 Abweichungen** (Pfad und CSRF-Token normalisiert). Zählung je Bestandteil (Leitzahl, Karten, Sortierung, Statusfilter, Blätterung, Zustimmungsformulare, „Umgesetzt“) gleich, auch mit `?sort=newest&status=reviewing&page=2` · `qa/16/AK-17-ideenliste-alt-gegen-neu.txt` |
| AK-18 | ✅ bestanden | `/de/roadmap/ideen/94-qa16-0` → 200, Bereichskopf vorhanden, „Ideen“ aktiv (5 Sprachen, `QA-layout-messwerte.json`) |
| AK-19 | ✅ bestanden | Fünf Einreichungen über `/de/roadmap/ideen/neu` → 302 `/de/roadmap/ideen/eingereicht`; Formular und Bestätigungsseite verweisen auf `/de/roadmap/ideen` („Zurück zu den Ideen“); in der DB `published_at IS NULL` für alle fünf (Ideen 143–147) |
| AK-20 | ✅ bestanden | Mit gültigem Token: Zustimmen → 302 `/de/roadmap/ideen/95-qa16-1` (Stimme 0 → 1); Zurückziehen der eigenen wartenden Idee 144 → 302 `/de/roadmap/ideen`, Zeile gelöscht |
| AK-21 | ✅ bestanden | `curl`, 3 Adressen × 5 Sprachen: 15 × **301**, `Location` `/<gleiche Sprache>/roadmap/ideen[/neu\|/eingereicht]`; Ziel in **einem** Sprung (Liste 200, `/neu` → Anmeldung wie vorher). Auch bei Sitzungssprache fr bleibt `/de/…` deutsch · `Qa16RoadmapIdeenTest::testAk21…` |
| AK-22 | ✅ bestanden | `/<Sprache>/community/ideen/94-qa16-0` → 301 `/<Sprache>/roadmap/ideen/94-qa16-0`, 5 Sprachen; gefolgt: 200 nach `num_redirects=1` (de, fr) |
| AK-23 | ✅ bestanden | Freigabe (Idee 145) und Ablehnung (146) im Admin, Zustellung in Mailpit: beide Mails verweisen in HTML und Text **nur** auf `http://127.0.0.1:8000/de/roadmap/ideen/<Nummer>-<Titel>`. Statuswechsel (new → reviewing → planned) lösen keine Mail aus — 06 kennt nur die Veröffentlichungsmail, der dritte Fall des Kriteriums tritt nicht ein · `qa/16/AK-23-mails.txt` |
| AK-24 | ✅ bestanden | `?next=https://example.com`, `?url=//example.com`, `?_target_path=…`, `?redirect=//evil.example` → Ziel immer `/de/roadmap/ideen…`; `//example.com`, `%2F%2Fexample.com`, `https:%2F%2F…`, `..%2F` im Pfad → 404; `%5C%5C`, `%0d%0a`, `%E2%80%AE` im Titelteil → kodiert im Ziel, keine eingeschleuste Kopfzeile · `Qa16RoadmapIdeenTest::testAk24…` (4 Fälle) |
| AK-25 | ✅ bestanden | `/sitemap.xml`: 160 Einträge, `/<Sprache>/roadmap/ideen` × 5, `/community/ideen` × 0 (auch in Sprachverweisen), `/roadmap` × 5, `/changelog` × 5; gültig gegen `sitemap-mit-sprachverweisen.xsd` · `qa/16/AK-25-sitemap.xml`. Die 60-Minuten-Zusage selbst gilt erst nach dem Ausrollen; die Summe der Speicherzeiten hält `SitemapControllerTest` (grün) |
| AK-26 | ✅ bestanden | 5 Sprachen: canonical `https://endlech.lu/<Sprache>/roadmap/ideen`, Sprachverweise lb/de/fr/en/pt + x-default; `?page=2` (auch mit Sortierung/Filter) bleibt in canonical und Sprachverweisen, `?page=1` fällt weg · `qa/16/AK-26-canonical.txt` |
| AK-27 | ✅ bestanden | 4 Seitenarten × 5 Sprachen: Name, Reiter, Knopf in der jeweiligen Sprache (z. B. pt „Roteiro e ideias · Roteiro · Ideias · Registo de alterações · Enviar ideia“); Suche nach `area.`/`footer.`/`roadmap.`/`board.`/… in Text und Attributen: 0 Treffer (ein Treffer „changelog.html.twig“ stammt aus der eingeblendeten Debug-Toolbar) |
| AK-28 | ✅ bestanden (QA-Wertung nach OF-02) | axe-core über 3 Reiter + Einzelseite + Formular × 5 Sprachen, angemeldet: **375 × 800: 25 × 0 Verstöße**; **1280 × 800: 25 × genau 1** `color-contrast` an der Wortmarke „Endlech.lu“ der App-Hülle (`#01b6ed`, 2,35 : 1, `hidden xl:block`). Derselbe Treffer steht auf `main` (`/de/roadmap`, `/de/about`) — vorbestehend, nicht von 16 eingeführt, und als Markenname von WCAG 1.4.3 ausgenommen. Gewertet wie von OF-02 vorgeschlagen; Bestätigung durch den Betreiber steht aus. ⚠ axe übersieht einen echten Kontrastmangel im Kopf (BF-203) |
| AK-29 | ✅ bestanden | Zwei wartende Ideen (mit und ohne Verfasser). Anonym 1600 Aufrufe (3 Reiter + 5 alte Adressen, darunter die alten Einzeladressen beider wartenden Ideen, × 5 Sprachen × 10 Parametersätze, viermal; Weiterleitungen gefolgt): **0** Treffer auf Titel oder Beschreibung. Angemeldet als Admin, unbestätigtes Konto, Verfasser: 840 Aufrufe, Treffer **nur** beim Verfasser auf seiner eigenen Idee (AK-33 erlaubt das); die 301-Antwort selbst nennt den Titel nicht · `qa/16/AK-29-wartende-ideen.txt`, `Qa16RoadmapIdeenTest::testAk29…` ⚠ Ein anderer Weg verrät den Titelteil: BF-199 |
| AK-30 | ✅ bestanden | Je 100 Aufrufe der 7 Adressen anonym und 5 Adressen angemeldet (1200), danach `var/log/dev.log` (DEBUG-Stufe, 14 021 neue Zeilen): Anzeigename („Test User“, „Test U.“, „Admin User“) 0, Ideentitel/-beschreibungen 0. `user@endlech.lu` 500 × — ausschließlich in `security.DEBUG: User was reloaded from a user provider` (Kennung des angemeldeten Besuchers, nicht aus dem Board, siehe H-3) · `qa/16/AK-30-protokoll.txt` |
| AK-31 | ✅ bestanden | Am nachgebildeten Umami-Eingang: Zählaufruf für `…/de/roadmap/ideen?sort=newest&status=planned&page=2` kommt als `url: /de/roadmap/ideen` an, ohne Abfrageteil; Feldmenge identisch mit dem Aufruf der alten Adresse · `Qa16RoadmapIdeenTest::testAk31…` |
| AK-32 | ✅ bestanden | Netzwerkmitschnitt, 3 Reiter × 5 Sprachen: je 41 Anfragen, **0** an einen anderen Host als `127.0.0.1:8000` · `QA-browser-messwerte.json` (`ak32`) |
| AK-33 | ✅ bestanden | Wartende Idee 140 (Verfasser user@) und 141 (ohne Verfasser) unter `/de/roadmap/ideen/…`: Gast, Admin, unbestätigtes Konto → **404**, Titel und Beschreibung 0 × im Dokument; Verfasser → 200 (auch mit falschem Titelteil) |
| AK-34 | ✅ bestanden | Einreichen über die neue Adresse: Nr. 1–5 → 302, **Nr. 6 → 429** „Zu viele Einreichungen. Bitte versuche es in 60 Minuten erneut.“ — auf `main` über die alte Adresse identisch. Zustimmen: 61 Versuche, 60 umgeschaltet, Folgeseite „Zu viele Zustimmungen. Bitte versuche es in 60 Minuten erneut.“ — auf `main` identisch. Beide Deckel zählen am Konto, nicht am Pfad · `qa/16/AK-34-deckel.txt` · Hinweis H-2 |
| AK-35 | ✅ bestanden | Dev-DB mit 45 freigegebenen Ideen (12 geplant). Profiler: Reiter Ideen `LIMIT 20` (Seite 3: `LIMIT 20 OFFSET 40`), sonst nur `COUNT` und Stimmen `WHERE id IN (…20)`; Roadmap `LIMIT 10` + `COUNT`. Angezeigt: 20 + Abschnitt „Umgesetzt“ bzw. 10 · `qa/16/AK-35-abfragen.txt` |
| AK-36 | ✅ bestanden | `/de/profile/daten` als user@ (mit Ideen und Stimmen) auf `main` und auf 16, gleiche DB: 32 Schlüssel in beiden, keiner nur neu, Board-Abschnitt (`boardIdeas`, `boardVotes`) wertgleich |
| AK-37 | ✅ bestanden | Kataloge der acht OF-01-Schlüssel in 5 Sprachen ohne „Board/tableau/quadro“ (`qa/16/AK-37-kataloge.txt`); gerenderte Roadmap, Einzelseite, Formular in 5 Sprachen: 0 Treffer. ⚠ Außerhalb der in AK-37 genannten Stellen sagt es weiterhin „Board“: BF-201 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Test-DB ohne freigegebene Idee: Reiter Ideen mit Leerzustand und eigenem Weg zum Formular **plus** Knopf im Kopf; Roadmap ohne Community-Gruppe · `Qa16RoadmapIdeenTest::testEc01OhneFreigegebeneIdee` |
| EC-02 | ✅ bestanden | `/de/community/ideen/94-ganz-alter-titel` → 301 → 200, `<title>` der Idee 94 |
| EC-03 | ✅ bestanden | Dublette 142 (→ 94): `/de/community/ideen/142-qa16x-dublette` → 301 → 302 → `/de/roadmap/ideen/94-qa16-0`, **zwei** Sprünge, Ziel das Original |
| EC-04 | ✅ bestanden | `/roadmap` (Accept-Language fr) → 302 `/fr/roadmap`; `/roadmap/ideen`, `/roadmap/ideen/94-qa16-0`, `/community/ideen` ohne Sprache → 404 |
| EC-05 | ✅ bestanden | 320 px: fr und pt brechen in zwei Zeilen um, keine Beschriftung abgeschnitten (`scrollWidth ≤ clientWidth` je Reiter), kein Querscrollen · `qa/16/QA-AK-08-*-fr-320.png`, `…-pt-320.png` |
| EC-06 | ✅ bestanden | `/de/community/ideen?status=planned&page=2` → 301 `/de/roadmap/ideen` (Filter fällt weg — zulässig) |
| EC-07 | ✅ bestanden | „Stand: 28. Oktober 2026“ auf Roadmap und Changelog, nicht auf Reiter Ideen und Einzelseite |

## Regression

Volle Testsuite: `php bin/phpunit` → **1917 Tests, 0 Fehlschläge**, 30 übersprungen (11 598 Zusicherungen).
Vor den QA-Tests: 1907 / 0 / 28. Letzter Bericht eines anderen Features (15, Nachprüfung): 1816 — die Zahl ist
gestiegen, nichts fehlt.

Berührt laut `git diff --name-only main...HEAD` (der vorgeschaltete Footer-Commit: `config/packages/twig.yaml`,
`config/services.yaml`, `templates/impressum/index.html.twig`, `templates/partials/_footer.html.twig`,
`translations/messages.*`) und Arbeitsbaum (`src/Controller/BoardController.php`, `src/Seo/SeoRegistry.php`,
Fußzeile, Board- und Roadmap-Vorlagen, drei Katalogdomänen) sowie *Systemweite Änderungen* in `tasks.md`:

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| 06 | Board umgezogen, Präfix des Controllers | ✅ bestanden | US-01 Einreichen: 5 × 302 → Bestätigung, wartend (AK-19); Zustimmen/Zurückziehen (AK-20); Liste zeilengleich (AK-17) |
| 07 | Kopf von Roadmap und Changelog ersetzt, „Weiterlesen“ entfernt | ✅ bestanden | US-01 „woran gerade gearbeitet wird“: Spalten, Gruppe, Aktualität (AK-14); bei 1280 px „In Arbeit“ ≤ 565 px (AK-11) |
| 10 | Sitemap, canonical, `SeoRegistry` | ✅ bestanden | Sitemap schemagültig, 385 Restaurantadressen, neue Board-Adresse (AK-25/26) |
| 11 | Zählpfad | ✅ bestanden | Weiterleitung an Umami nachgebildet (AK-31) |
| 02 | Kopf und Reiter auf vier Seitenarten | ✅ bestanden | axe (AK-28), Tastatur (AK-09), 320 px (AK-08) — mit BF-203 |
| 15 | Fußzeile | ✅ bestanden | Bereich „Sponsoren“ mit „Sponsor werden →“ auf allen 40 Fußzeilen (`AK-01-03-fusszeile.txt`) |
| B13 | Impressum (Footer-Commit) | ✅ bestanden | `/<Sprache>/legal` 5 × 200, Anker `#datenschutz`, Umsatzsteuer-Angabe, Herstellerhinweis |
| B02 | Anmeldung über den Knopf | ✅ bestanden | echtes Anmeldeformular de/fr (AK-12) |

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR), lesend | bestanden | wartende fremde Idee unter neuer und alter Adresse: Gast/Admin/unbestätigt 404 ohne Titel (AK-33, AK-29) |
| Zugriff auf fremde ID, schreibend | **BF-199** | Zurückziehen fremder Idee 147 mit dem Token des Verfassers: Admin **403**, Gast → Anmeldung, Idee bleibt. Zustimmen auf wartende fremde Idee mit falschem/leerem Token: **302 auf `/de/roadmap/ideen/140-qa16x-zebrafink-geheimtitel`** (unbestätigtes Konto, auch auf `main`) · `qa/16/BF-199-zustimmen-verraet-titel.txt` |
| Zugriffsregeln serverseitig | bestanden | `/de/admin/ideen` als user@ → 403; Formular als Gast → Anmeldung, als unbestätigt → Hinweis (AK-12); POST an alte Schreibadressen: Zustimmen/Zurückziehen 404, `/neu` 405 (H-4) |
| Rate Limit greift | bestanden (mit BF-199) | Einreichen Nr. 6 → 429, Zustimmen Nr. 61 gedeckelt (AK-34). Alte Adressen ohne Deckel wie entschieden (Decision Log 9): ohne Datenbankabfrage (Profiler: 0 Abfragen auf `board_idea`). ⚠ 70 Zustimmungsversuche mit falschem Token → 70 × 302, kein Deckel (BF-199) |
| Eingaben | bestanden | Titelteil der alten Adresse: 10 000 Zeichen → 301 (neue Adresse 200), Emoji, `'; drop table …`, `<script>` (404), CRLF, Rückstrich, Unicode-Umkehr → kodiert, keine Kopfzeile eingeschleust; Nummer 0 und 20-stellig → 404; `?status[]=new`, `?page=abc` → 400 (vorbestehend) |
| PII in Logs | bestanden | AK-30; Hinweis H-3 (Kennung des angemeldeten Besuchers im `security`-Kanal, vorbestehend) |
| PII an externe Dienste | bestanden | Umami: Pfad ohne Abfrage, Feldmenge unverändert (AK-31); keine fremde Ressource (AK-32); Freigabe-Mail: nur Titel und Link (Mailpit, `AK-23-mails.txt`) |
| Geheimnisse im Repository | bestanden | Muster (`sk_live_`, `sk-…`, `api_key=`, `secret=`, Passwortliterale, `PRIVATE KEY`, `xkeysib-`, Zugangsdaten in URLs) im Diff und in den neuen Dateien: 0 Treffer; das Feature braucht keinen Schlüssel |
| Löschen | trifft nicht zu | Keine Entität, keine Migration, kein Feld im Diff; Datenexport unverändert (AK-36) |

## Fehler

### BUG-01 / BF-199 · Zustimmen mit falschem Token verrät den Titel wartender Ideen — hoch

**Betrifft:** Spec H·3 („eine wartende Idee sieht nur ihr Verfasser … unter der neuen Adresse übernommen“),
Ziel von AK-29/AK-33; vorbestehend aus Feature 06, auf der Produktion erreichbar
**Reproduktion:**
1. Als beliebiges Konto anmelden — ein **unbestätigtes** genügt (`unverified@endlech.lu`).
2. `POST /de/roadmap/ideen/140/zustimmen` mit `_token=x` (Idee 140 wartet, Verfasser ist ein anderer).
3. Nummern hochzählen; jede wartende Idee antwortet mit 302.
**Erwartet:** 404, wie bei gültigem Token und wie die Einzelseite (`GET …/140-…` → 404).
**Tatsächlich:** `302 Location: /de/roadmap/ideen/140-qa16x-zebrafink-geheimtitel` — der Titelteil wird aus dem
Titel gebildet. Nicht existierende Nummer → 404, also auch Existenz aufzählbar. 70 Versuche hintereinander:
70 × 302, kein Deckel. Auf `main` identisch (`/de/community/ideen/140/zustimmen`).
**Ort:** `src/Controller/BoardController.php:209` — die CSRF-Prüfung steht vor der Sichtbarkeitsprüfung
(`:217`), ihr Fehlerzweig ruft `zurueckZurIdee()` mit `slug` (`:281`); der Deckel wird erst nach `:217` gefragt.
**Vorschlag:** Sichtbarkeit (`isPublished()`) vor der CSRF-Prüfung prüfen — dasselbe Muster wie „Besitzprüfung vor
CSRF“ beim Zurückziehen und im `PasskeyController`.
**Prüflauf:** `Qa16RoadmapIdeenTest::testBf199ZustimmenVerraetWartendeIdeeNicht` (übersprungen, scharf mit
`QA16_REPRO=1`: rot).

**Behoben 2026-11-07 (Fehlerauftrag):** Sichtbarkeitsprüfung vor der CSRF-Prüfung in `BoardController::vote()`. Test: `Qa16RoadmapIdeenTest::testBf199ZustimmenVerraetWartendeIdeeNicht` (vorher rot, jetzt grün und nicht mehr übersprungen).

### BUG-02 / BF-200 · Reiterleiste steht je Seitenart an anderer Stelle — mittel

**Betrifft:** AK-05 („auf allen vier Seitenarten wortgleich und an derselben Stelle“)
**Reproduktion:** `/de/roadmap`, `/de/roadmap/ideen`, `/de/changelog`, `/de/roadmap/ideen/94-qa16-0` bei 1280 × 800
nacheinander öffnen und die Oberkante von `nav[data-area-tabs]` messen (oder hinsehen: die Leiste springt).
**Erwartet:** gleiche Höhe auf allen vier Seitenarten.
**Tatsächlich:** 353 / 353 / 323 / **225** px; bei 375 px (fr) 402 / 432 / 411 / **253** px; bei 320 px bis 260 px
Unterschied. Zusätzlich verschieben sich die Reiter waagerecht um ~3 px, weil der fette aktive Reiter breiter ist.
**Ort:** `templates/roadmap/_area_header.html.twig:44` — `h1` und Einleitung stehen **im** Kopfband über der
Reiterleiste (`:52`); ihre Länge je Seite (und Sprache) bestimmt die Höhe. So im Entwurf vorgesehen
(`design.md`, Komponentenstruktur).
**Vorschlag:** Entscheidung nötig — Reiterleiste direkt unter Name und Knopf, Überschrift und Einleitung darunter;
oder AK-05 auf „unter dem Namen, in derselben Reihenfolge“ neu fassen. → `/sdd-klaeren 16`

**Behoben 2026-11-07 (Fehlerauftrag, entschieden in `/sdd-klaeren 16`: Kopf umbauen, AK-05 bleibt):** Band enthält nur Etikett und Knopf, `h1` und Einleitung stehen unter der Reiterleiste; alle Reiter `font-semibold`. Tests: `RoadmapAreaTest::testBf200UeberDerReiterleisteStehtUeberallDasselbe` (4 Seitenarten), `::testBf200AlleReiterGleichFett`. Browser: Oberkante der Leiste je Seitenart gleich — 1280 px 173, 375 px 165, 320 px 165 (fr 201) in allen fünf Sprachen (`qa/16/FA-messwerte.json`).

### BUG-03 / BF-202 · Französisch, 375 × 800, erster Besuch: „En cours“ unter dem Cookie-Banner — mittel

**Betrifft:** AK-11 („der zusätzliche Kopf verdrängt sie nicht (07/AK-03 bleibt erfüllt)“)
**Reproduktion:** Neues Browserprofil, 375 × 800, `/fr/roadmap` öffnen, Cookie-Banner nicht wegklicken.
**Erwartet:** Überschrift „En cours“ sichtbar.
**Tatsächlich:** Überschrift bei 661–689 px, Banner ab 601 px — verdeckt. Auf `main` im selben Fall 550–578 px,
frei. Ursache: die Reiterleiste bricht französisch zweizeilig um (+44 px), dazu Knopfzeile und längere
Einleitung. Die übrigen vier Sprachen liegen 19–39 px über dem Banner.
**Ort:** `templates/roadmap/_area_header.html.twig` (Kopfhöhe), Banner `templates/partials/_cookie_banner.html.twig:6`
**Vorschlag:** Kopf niedriger oder Reiterleiste einzeilig; das Banner gehört zum gemessenen ersten Bildschirm.
**Belege:** `qa/16/QA-AK-11-fr-375x800-vorher-main.png`, `qa/16/QA-AK-11-fr-375x800-nachher.png`

**Behoben 2026-11-07 (Fehlerauftrag):** einzeiliges Band, kompakter Knopf auf schmalen Schirmen, Reiter fr „Journal“. Test: `RoadmapAreaLocaleTest::testBf202FranzoesischerReiterIstKurz`. Browser, erster Besuch mit Banner: 375 × 800 fr „En cours“ endet bei 566 px, Banner ab 601 px; alle fünf Sprachen bei 1280 und 375 frei (`qa/16/FA-erster-bildschirm-mit-banner.json`). ⚠ Bei 320 × 800 (nicht Teil von AK-11) verdeckt das Banner fr/pt weiterhin 51/58 px.

### BUG-04 / BF-201 · OF-01 nicht durchgezogen: vier Texte nennen den Ort weiter „Board“ — niedrig

**Betrifft:** OF-01/Decision Log 16 („Wo ein Text auf den Ort verweist, heißt er ‚Ideen‘“); kein durchgefallenes
Kriterium — AK-37 zählt Roadmap, Einzelseite und Formular auf, diese Stellen liegen daneben.
**Reproduktion:** `/de/roadmap/ideen/eingereicht` (angemeldet) lesen; Reiter Ideen ohne freigegebene Idee; Mail
nach einer Freigabe lesen.
**Tatsächlich:** Leerzustand „Noch keine Idee auf dem Board“ (`board.empty_title`), Bestätigung „Sobald sie auf
dem Board steht, bekommst du eine E-Mail.“ (`board.thanks_text`), Freigabe-Mail „Deine Idee steht jetzt auf dem
Board“ / „… öffentlich auf dem Community-Board …“ (`email.board_published_*`), Admin-Meldung
`flash.board_published` — in allen fünf Sprachen („tableau“, „quadro“, „Board“).
**Ort:** `translations/messages.de.yaml:704`, `:728`, `:752`, `:839–842` und die vier Schwesterkataloge
**Vorschlag:** Reichweite von OF-01 entscheiden (`/sdd-klaeren 16`), dann die Texte nachziehen.
**Prüflauf:** `Qa16RoadmapIdeenTest::testBf201…` (übersprungen, scharf: rot am Leerzustand).

**Behoben 2026-11-07 (Fehlerauftrag, Reichweite entschieden in `/sdd-klaeren 16`):** sechs Schlüssel in fünf Sprachen (`board.empty_title`, `board.thanks_text`, `flash.board_published`, `email.board_published_subject/_greeting/_text`). Tests: `Qa16RoadmapIdeenTest::testBf201…` (scharf, nach Reparatur des Testaufbaus — er gab nach dem Kernel-Neustart ein losgelöstes Konto weiter) und `RoadmapAreaLocaleTest::testAk37OrtHeisstIdeen` (Schlüsselliste erweitert). Gegenprobe: alter Mailbetreff → rot.

### BUG-05 / BF-203 · Etikett „Roadmap & Ideen“ unter 4,5 : 1 — mittel, vorbestehend (Muster)

**Betrifft:** Barrierefreiheit (Feature 02, WCAG 1.4.3); kein Kriterium von 16 — AK-28 misst axe, und axe kann den
Verlauf nicht auswerten („incomplete: background gradient“).
**Reproduktion:** Pixel des Etikett-Hintergrunds im Bildschirmfoto bei 1280 px auslesen, Kontrast zu Weiß rechnen.
**Tatsächlich:** 14 px, `font-semibold`, weiß auf `bg-white/20` über dem Verlauf: **3,67–3,79 : 1** (gefordert 4,5 : 1).
Das alte Etikett „Was wir bauen“ lag bei 3,86–3,98 : 1; im neuen Kopf steht es bei x = 16 px am hellen Ende des
Verlaufs und jetzt auf vier Seitenarten. Dieselbe Komponente „Glas“ (`docs/design-system.md:406`) steht in zehn
Vorlagen (Startseite, `/open`, `/presse`, Vergleiche, Partner, Organisationen, Sponsoren, App-Warteliste …).
**Ort:** `templates/roadmap/_area_header.html.twig:37`; Komponente im Design-System
**Vorschlag:** Glas-Etikett mit dunklerem Grund (`bg-black/25` o. ä.) oder größerer/fetterer Schrift neu messen —
projektweit, nicht nur hier.

**Für den Bereichskopf behoben 2026-11-07 (Fehlerauftrag):** Etikett auf `bg-black/20`, an den Pixeln gemessen ≥ 7,4 : 1 (1280 und 375 px, de/fr). Test: `RoadmapAreaTest::testBf203EtikettAufDunklemGlas`. Die übrigen neun Vorlagen mit Glas-Etikett bleiben offen (nicht Teil von 16).

### BUG-06 / BF-204 · README nennt noch `/community/ideen` — niedrig

**Ort:** `README.md:54` („Public board at `/community/ideen` …“). Das öffentliche Repository beschreibt die alte
Adresse; `CLAUDE.md`, `docs/` und `CHANGELOG.md` sind nachgezogen.

**Behoben 2026-11-07 (Fehlerauftrag):** `README.md:54` nennt `/roadmap/ideen` und die Weiterleitung.

### BUG-07 / BF-205 · Zwei Prüfläufe des Baus können nicht rot werden — niedrig

**Betrifft:** Absicherung von AK-30 und AK-12 (beide Kriterien sind von der QA unabhängig belegt, siehe oben)
**Reproduktion:**
1. `BoardLegacyRedirectTest::testAk30KeinePersonenangabeImProtokoll` liest `var/log/test.log`. `when@test` schreibt
   dorthin über `fingers_crossed` mit `action_level: error` (`config/packages/monolog.yaml:21–30`) — bei 301-Antworten
   nie. Gegenprobe: dem Weiterleitungs-Controller testweise
   `$logger->info('… user@endlech.lu Idee für das Protokoll')` gegeben → Test **grün**, `test.log` 0 Treffer
   (Controller danach byte-gleich zurückgesetzt).
2. `RoadmapAreaTest::testAk12UnbestaetigtesKontoBekommtDenHinweis` prüft nur, dass Pfad und Formularfeld fehlen.
   Gegenprobe: Kopie mit **falschem Passwort** → landet auf `/de/login`, Test **grün** (Kopie danach gelöscht).
**Erwartet:** Ein Prüflauf, der den beschriebenen Fehler sieht.
**Ort:** `tests/Functional/Controller/BoardLegacyRedirectTest.php:125–151`, `tests/Functional/Controller/RoadmapAreaTest.php:122–140`
**Vorschlag:** (1) `Monolog\Handler\TestHandler` auf Stufe `debug` statt der Datei; (2) Ziel `app_verify_notice` und
den Text von `flash.board_verify_first` prüfen.

**Behoben 2026-11-07 (Fehlerauftrag):** (1) Speicher-Handler `pruefung` (`type: test`, `debug`) in `when@test` von `monolog.yaml`; `testAk30…` liest ihn nach jeder Anfrage, leert ihn nach dem Aufbau und verlangt 301. Gegenprobe Protokollzeile mit E-Mail im Controller → rot. (2) `testAk12UnbestaetigtesKonto…` verlangt `/de/verify` und die Meldung „Bitte bestätige zuerst deine E-Mail-Adresse.“. Gegenprobe falsches Passwort → rot.

## Code-Review

`code-reviewer`-Agent über die geänderten Dateien, mit `spec.md`, `design.md` und `CLAUDE.md`. Seine Funde, jeder
nachgeprüft:

| Fund des Reviewers | Nachprüfung | Ergebnis |
|---|---|---|
| AK-30-Prüflauf liest eine Datei, die bei 301 leer bleibt | Mutation mit Protokollzeile, Test grün | bestätigt → BF-205 |
| AK-12-Prüflauf (unbestätigt) besteht auch ohne Anmeldung | Kopie mit falschem Passwort, Test grün | bestätigt → BF-205 |
| „Board“ in Leerzustand, Bestätigung, Mail, Admin-Meldung | gerendert und in Mailpit gelesen | bestätigt → BF-201 (von der QA unabhängig gefunden) |
| Einzelseite ohne `h1`; axe müsste `page-has-heading-one` melden | `h3` als erste Überschrift auf 16 **und** `main`; axe wertet die Regel dort als bestanden (Werkzeuggrenze) | vorbestehend, kein Kriterium von 16 → H-6. Der erste Vergleich in `testAk13KetteLueckenlosMitIdeen` (`1 <= 1`) ist immer wahr; die `h1` je Reiter sichert `testAk13EigeneTitelUndGenauEineH1` — kein Befund |
| `README.md:54` nennt `/community/ideen` | gelesen | bestätigt → BF-204 |
| Etikett unter 4,5 : 1 (rechnerisch ~3,6 : 1) | Pixel gemessen: 3,67–3,79 : 1 | bestätigt → BF-203 (von der QA unabhängig gefunden) |
| Luxemburgisch „geplangten Iddi“ (`roadmap.lb.yaml:33`) eventuell ungrammatisch | nicht prüfbar ohne Muttersprachler | verworfen als Befund, offen für den Betreiber |

Nicht gefunden hat der Reviewer BF-199, BF-200 und BF-202 — alle drei zeigen sich erst am laufenden System.

## Hinweise (keine Befunde)

- **H-1 · AK-03 und die Kontaktzeile.** Unter „Helfen Sie uns, besser zu werden.“ stehen `support@endlech.lu`
  (`mailto:`) und „Open Source auf GitHub“ (`target="_blank"`). Wörtlich gelesen sind auch das Wege für
  Rückmeldungen. Gewertet wie die QA von 06/AK-80 („Feedback-Verweis“); die Spec sollte sagen, ob die Kontaktzeile
  mitgemeint ist.
- **H-2 · Deckel überleben kein Ausrollen.** Die Limiter speichern in `cache.rate_limiter` (Dateisystem unter
  `var/cache`, `debug:container`), das Image führt `var/` nicht als Volume (`Dockerfile:229`). Jeder
  Containertausch beginnt also mit leeren Zählern — auch beim Ausrollen dieses Features. Vom Pfad unabhängig (AK-34
  ist so gelesen bestanden), projektweit, auf der Produktion nicht ausgeführt.
- **H-3 · `security`-Kanal im Produktionspuffer.** Bei jedem angemeldeten Aufruf schreibt Symfony
  `User was reloaded from a user provider {"username":"<E-Mail>"}` (DEBUG). `when@prod` schließt `doctrine` und
  `request` aus dem `fingers_crossed`-Puffer aus, `security` nicht; bei einem Fehler in einer angemeldeten Anfrage
  landete die Adresse im Hoster-Log. Vorbestehend (B02), auf der Produktion nicht ausgeführt.
- **H-4 · POST an alte Schreibadressen.** Ein vor der Umstellung geöffnetes Einreichformular endet nach dem
  Ausrollen mit **405** (der Text ist weg), Zustimmen/Zurückziehen mit 404. Nicht zugesagt (Decision Log 12).
- **H-5 · Schrägstrich am Ende.** `/de/community/ideen/` braucht zwei Sprünge (Symfonys Schrägstrich-Regel, dann
  301). Nicht zugesagt.
- **H-6 · Einzelseite ohne `h1`.** Vorher wie nachher (`h3` als einzige Überschrift); AK-13 gilt nur für die
  Reiter, der Kopf bringt dort bewusst keine.
- **H-7 · Verifikation des Baus.** `lint:container` scheitert vorbestehend (auch auf `main`) am Webauthn-Alias;
  `vendor/bin/php-cs-fixer` fehlt. `lint:twig`/`lint:yaml` grün.
- **H-8 · Umgebung.** Mailpit lief auf Port 51087 (Docker-Zufallsport), nicht auf 8025; außerhalb des Symfony-CLI
  kennt `bin/console` den Port nicht (zwei Mails gingen in `failed` und wurden mit `MAILER_DSN` erneut zugestellt).

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/Qa16RoadmapIdeenTest.php` | `testAk31ZaehlaufrufNeuerPfadOhneAbfrageteilUndOhneNeuesFeld` | AK-31 |
| | `testAk21AlteAdresseBehaeltIhreSpracheGegenDieSitzung` | AK-21 (Sitzungssprache ≠ Pfad) — nie rot gesehen: `LocaleSubscriber` schreibt die Pfadsprache vor dem Controller in die Sitzung, eine Mutation des Controllers auf die Sitzungssprache bleibt deshalb grün; Rückfallschutz |
| | `testAk24TitelteilBleibtImZielKodiert` (4 Fälle) | AK-24 (CRLF, Rückstriche, Unicode-Umkehr, `..`) |
| | `testAk29WartendeIdeeAuchMitParameternUndFremdemKontoUnsichtbar` | AK-29 (5 Sprachen × 6 Pfade × 4 Parametersätze × Gast/Admin/unbestätigt) |
| | `testEc01OhneFreigegebeneIdee` | EC-01, 07/EC-01 |
| | `testBf199ZustimmenVerraetWartendeIdeeNicht` | BF-199 — Reproduktion, übersprungen |
| | `testBf201OrtHeisstAuchInLeerzustandBestaetigungUndMailIdeen` | BF-201 — Reproduktion, übersprungen |

Browsermessungen (AK-05 bis -13, -27, -28, -32, BF-202, BF-203) laufen nicht in PHPUnit; die Skripte lagen unter
`/tmp/qa16/`, die Messwerte stehen in `qa/16/QA-*-messwerte.json`.

## Testdaten

In der Dev-Datenbank angelegt und nach der Prüfung wieder entfernt: Ideen `qa16x-*` (30 veröffentlichte, zwei
wartende, eine Dublette) und `QA16Y …` (fünf Einreichungen). Die Ideen `qa16-0` … `qa16-14` des Baus bleiben.
Zwei Mails in Mailpit. Wegwerf-Arbeitsbaum `main` unter `/tmp/qa16-main` entfernt.

## Nächster Schritt

1. **`/sdd-klaeren 16`** — BF-200 (Form des Kopfs oder AK-05 neu fassen), BF-201 (Reichweite von OF-01), OF-02
   (Wertung von AK-28 bestätigen), H-1.
2. **`/sdd-build 16`** mit BF-199 (blockierend; sitzt in `BoardController::vote()`, den dieser Branch berührt),
   BF-202, BF-204, BF-205 und den in Schritt 1 freigegebenen; BF-203 ist projektweit (Design-System) und kann
   eigenständig laufen.
3. Danach erneut **`/sdd-qa 16`**.
