# 07 · Öffentliche Roadmap und Changelog — Testbericht

Stand: 2026-08-30 · Geprüft gegen `spec.md` vom 2026-08-30 (52 Kriterien, alle offenen Fragen entschieden)
**Zwei Durchläufe** — der zweite steht am Ende des Berichts.

## Fazit (nach dem zweiten Durchlauf)

**Production-ready: ja**

BF-108 ist behoben und gegengeprüft; **49 von 52 Kriterien bestanden, kein neuer Befund**.
Die drei verbliebenen — AK-34, AK-38, AK-44 — fallen sämtlich an **Altlasten der
App-Hülle** (BF-109, BF-110), die jede Seite des Projekts betreffen und deren Reparatur
nicht in dieses Feature gehört. Beide sind *mittel* bzw. *niedrig* und blockieren nach den
Regeln der Kette nicht.

⚠ **Offen und außerhalb dieses Features:** BF-109 und BF-110 (App-Hülle), **BF-111**
(Feature `06`: wartende Idee ohne Verfasser öffentlich lesbar — heute nicht erreichbar,
aber die Prüfung ist richtig aus dem falschen Grund). Nächster Schritt:
**`/sdd-deploy 07`** — ⚠ **erst nach Feature `06`** (VB-01): Die Roadmap liest dessen
Bestand und verlinkt auf `app_board_index`.

---

## Erster Durchlauf (2026-08-30, vormittags)

**Production-ready: nein**

Beide Seiten stehen in vier Sprachen, die Mechanik trägt: Die Community-Spalte zieht
live aus dem Board, eine abgelehnte oder zurückgezogene Idee verschwindet **ohne Deploy
und ohne Handgriff** (an der laufenden Anwendung nachgestellt), die Obergrenze wirkt in
der Abfrage, und der Prüflauf um den fünften Punkt der Release-Checkliste hält beiden
Gegenproben stand. 48 von 52 Kriterien bestanden.

**Blockierend ist BUG-01:** Bei **768 px** Fensterbreite — iPad hochkant, geteiltes
Laptop-Fenster — ist der Titel jeder Community-Karte eine **senkrechte
Buchstabenkolonne**: 12 px breit, 648 px hoch, beim längsten Titel 2352 px. Der Titel
steht im Markup und ist trotzdem nicht lesbar; AK-15 fällt damit durch. Die Ursache ist
eine fehlende Zeile im eigenen Partial — Feature `06` macht es an derselben Stelle
richtig.

Dazu drei Befunde ohne Codeanteil dieses Features: Zwei sind **projektweite Altlasten
der App-Hülle** (BUG-02, BUG-03), die dieses Feature sichtbar gemacht, nicht verursacht
hat; einer liegt in **Feature `06`** (BUG-04, latent).

| | Anzahl (1. Durchlauf) |
|---|---|
| Akzeptanzkriterien geprüft | 52 von 52 |
| davon bestanden | 48 |
| davon durchgefallen | 4 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 9 von 11 |
| Tests neu geschrieben | 5 (in 2 Dateien) |
| Tests grün | 912 von 912 (10 übersprungen, aus anderen Features) |

## Akzeptanzkriterien im Einzelnen

### A · Roadmap finden und lesen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `curl` auf `/lb,/de,/fr,/en` + `/roadmap` → 4 × 200, ohne Anmeldung |
| AK-02 | ✅ bestanden | Fußzeile von `/de/restaurants`: je 1 Verweis auf `/de/roadmap` und `/de/changelog` |
| AK-03 | ✅ bestanden | Browser 1280 × 800: `#stage-in_progress` im ersten Bildschirm, `top < innerHeight` → `true` in allen vier Sprachen · `qa/AK-03-roadmap-1280.png` |
| AK-04 | ✅ bestanden | 3 × `section[aria-labelledby^="stage-"]`, Überschriften „In Arbeit", „Geplant", „Angedacht" — keine vierte |
| AK-05 | ✅ bestanden | 8 kuratierte Einträge, **0** ohne Begründungsabsatz; strukturell erzwungen durch `RoadmapCatalogueTest` (24 Läufe) |
| AK-06 | ✅ bestanden | Regex über den Spaltentext: **0** Datumsangaben, **0** Quartale, **0** Prozentwerte |
| AK-07 | ✅ bestanden | Block „Bewusst nicht gebaut" mit **8** Punkten |
| AK-08 | ✅ bestanden | Block liegt außerhalb jeder `stage-`Sektion (`closest()` → null), eigene Überschrift, abgesetzte Fläche |
| AK-09 | ✅ bestanden | `RoadmapEmptyStatesTest::testJedeLeereSpalteErklaertSich` — jede der drei Spalten trägt > 25 Zeichen Erklärung |
| AK-10 | ✅ bestanden | Block „Weiterlesen" führt auf `/de/changelog` und `/de/community/ideen` |
| AK-11 | ✅ bestanden | POST/PUT/DELETE/PATCH auf beide Seiten → **8 × 405**; HEAD → 200 |

### B · Community-Ideen auf der Roadmap

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-12 | ✅ bestanden | 12 veröffentlichte `PLANNED`-Ideen angelegt → erscheinen in der Spalte „Geplant" |
| AK-13 | ✅ bestanden | Je eine Idee in `new`/`reviewing`/`done`/`declined` veröffentlicht → **0** davon im Quelltext |
| AK-14 | ✅ bestanden | Idee `QA-WARTEND-GEHEIM-ZZTOP` (PLANNED, nie freigegeben) → **0** Treffer im ausgelieferten HTML |
| **AK-15** | ❌ **durchgefallen** | Zustimmungszahl („14 Zustimmungen") und Board-Verweis (10 ×) belegt — **aber der Titel ist bei 768 px unlesbar**, siehe **BUG-01** · `qa/BUG-01-768px-titelkolonne.png` |
| AK-16 | ✅ bestanden | Alle 10 Karten tragen das Zeichen „Community-Idee"; kuratierte Einträge tragen es nicht |
| AK-17 | ✅ bestanden | 12 geplant → **10** Karten + „2 weitere geplante Ideen stehen im Board." Repository lädt 10 (`findPublishedPlanned(10)`) |
| AK-18 | ✅ bestanden | An der laufenden Anwendung: Ablehnung über ORM → Idee verschwindet (1 → 0 Treffer); Depublizierung ebenso; Zurücksetzen → wieder sichtbar. **Ohne Deploy, ohne Cache-Leeren** |
| AK-52 | ✅ bestanden | „Hier stehen die zehn geplanten Ideen mit den meisten Zustimmungen …" — sichtbar auch bei einer einzigen Idee |

### C · Changelog

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-19 | ✅ bestanden | 4 × 200 auf `/{lb,de,fr,en}/changelog` |
| AK-20 | ✅ bestanden | Regex über die neun Release-Texte: einziger Treffer „TripAdvisor" (Produktname). Kein Klassenname, kein Pfad, kein Migrationsname |
| AK-21 | ✅ bestanden | `2026.08.30.1`, `2026.08.29.1`, `2026.08.06` → **0** Treffer im HTML |
| AK-22 | ✅ bestanden | 10 `<article>` = 9 Releases + Sammelzeile „Aufbau der Plattform"; 2026 offen, `<details>` = 0 (einziges Jahr) |
| AK-23 | ✅ bestanden | `RoadmapFreshnessTest::testDasLaufendeJahrIstOffenDasFruehereZugeklappt` (laufendes Jahr 2027 → 1 `<details>`); kein `aria-expanded`, kein Stimulus-Controller |
| AK-24 | ✅ bestanden | Verweis auf `…/blob/dev/CHANGELOG.md` + Sprachhinweis „Nur auf Deutsch verfügbar." |
| AK-25 | ✅ bestanden | `RoadmapEmptyStatesTest::testDerLeereChangelogErklaertSich` — 0 Artikel, erklärender Text, Repo-Verweis, kein Platzhalter |
| AK-26 | ✅ bestanden | **Zwei** Gegenproben: (1) Release aus der Registry entfernt → rot, nennt `2026.03.17`; (2) neue Version in `CHANGELOG.md` ohne Eintrag → rot, nennt `2026.09.01` |
| AK-51 | ✅ bestanden | Erklärsatz mit 240 Zeichen über dem Verweis: „Im Repository liegt der technische Changelog: jede Auslieferung, auch die rein internen …" |

### D · Aktualität und Ehrlichkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-27 | ✅ bestanden | `RoadmapFreshnessTest`: 61 Tage → `bg-amber-50` + ⚠️ + Tageszahl; Schwelle geprüft — 60 ruhig, 61 hervorgehoben |
| AK-28 | ✅ bestanden | Live: „Stand: 30. August 2026", `text-gray-500`, **nicht** hervorgehoben |
| AK-29 | ✅ bestanden | Alle 8 kuratierten Einträge mit Begründung, alle 10 Community-Einträge mit Zustimmungszahl und Board-Verweis. Kein Eintrag ohne Herkunft |

### E · Mehrsprachigkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ bestanden | 8 × 200; h1 je Sprache: „Roadmap" / „Roadmap" / „Feuille de route" / „Roadmap" |
| AK-31 | ✅ bestanden | Gegenprobe: `release.v2026_08_29` aus `changelog.fr.yaml` entfernt → `RoadmapCatalogueTest` rot, nennt beide Schlüssel |
| AK-32 | ✅ bestanden | Kein `roadmap.`/`changelog.`-Präfix im sichtbaren Text, in keiner der acht Seiten |
| AK-33 | ✅ bestanden | Französische Idee auf Platz 1: `<h4 … lang="fr">Une idée en français avec accent</h4>` |

### F · Barrierefreiheit

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-34** | ❌ **durchgefallen** | axe-core über alle 8 Seiten (ohne Debug-Toolbar): je **1** Verstoß `heading-order`. Siehe **BUG-02** |
| AK-35 | ✅ bestanden | 8 × `scrollWidth = clientWidth = 320`; `maxElementBreite = 320` auch mit 120-Zeichen-Titel und arabischer Schrift · `qa/AK-35-roadmap-320.png` |
| AK-36 | ✅ bestanden | Tab-Durchlauf: Roadmap **12 von 12** fokussierbaren Elementen erreicht, Changelog 1 von 1 — **0** ohne sichtbaren Fokus |
| AK-37 | ✅ bestanden | 3 × `<section aria-labelledby="stage-…">`, jede Beschriftung zeigt auf eine vorhandene `h2` |
| **AK-38** | ❌ **durchgefallen** | Im Inhaltsbereich lückenlos (`RoadmapAccessibilityTest`, beide Seiten). **Seitenweit** springt die Kette von h2 auf h4. Dieselbe Ursache wie AK-34 → **BUG-02** |

### G · Datenschutz und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-39 | ✅ bestanden | Idee mit Verfasser angelegt → weder Anzeigename noch Adresse im HTML; `submittedBy` wird nicht abgefragt |
| AK-40 | ✅ bestanden | Alle vier `changelog.*.yaml` auf „Michael", „Ferreira", „Mukaarts", „@" geprüft → **kein Personenname** |
| AK-41 | ✅ bestanden | 30 Aufrufe → 51 KB neues Protokoll: **0** Board-Daten, **0** Geheimnisbegriffe, **0** E-Mail-Adressen |
| AK-42 | ✅ bestanden | Keine `img`/`script`/`link[stylesheet]`/`iframe` von fremdem Host. Die einzigen fremden URLs sind `<a href>` auf GitHub — geladen wird nichts |
| AK-43 | ✅ bestanden | Wartende Idee: 0 Treffer im ausgelieferten Quelltext; Filter steht in `findPublishedPlanned()`, nicht im Template |
| **AK-44** | ❌ **durchgefallen** | 8 Angriffsmuster (`<script>`, SQL, Pfad-Traversal, 10 000 Zeichen, Nullbyte, Array) → alle **200**, kein Serverfehler, **kein roher `<script>`**. Aber die Eingabe erscheint escaped in den `hreflang`-Verweisen → **BUG-03** |
| AK-45 | ✅ bestanden | 12 geplante Ideen im Bestand → `findPublishedPlanned(10)` liefert **10**, dazu eine Zählabfrage. Keine Abfrage lädt den Bestand |
| AK-46 | ✅ bestanden | **An der laufenden Anwendung:** 13 Stimmen per SQL am ORM vorbei eingefügt → Seite zeigt weiter den alten Stand; nach `cache:pool:clear cache.roadmap` steht die Idee oben |
| AK-47 | ✅ bestanden | `CommunityRoadmapTest` (5 Fälle: Status, Depublizierung, Stimme, Kontolöschung) + Live-Nachweis unter AK-18 |
| AK-48 | ✅ bestanden | `RoadmapAccountDeletionTest` über den echten `AccountDeleter`: Idee bleibt, `submittedBy` wird null, Zustimmungszahl fällt von 1 auf **0** |
| AK-49 | ✅ bestanden | `AccountDataExporter::export()` — kein Schlüssel enthält „roadmap" oder „changelog" |
| AK-50 | ✅ bestanden | Weder im Quelltext des Features noch im ausgelieferten HTML ein Schlüssel, Token oder interner Pfad (0 Treffer) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `RoadmapEmptyStatesTest::testOhneCommunityIdeenKeinLeererBlock` — kein Block, kein Hinweis, keine Auswahlregel |
| EC-02 | ✅ bestanden | Depublizierung über ORM → Idee beim nächsten Aufruf weg (Live-Messung) |
| EC-03 | ✅ bestanden | Zwei Ideen mit je 7 Stimmen: die neuere (20.08.) auf Platz 6, die ältere (01.08.) auf Platz 7; über **drei** Aufrufe identisch |
| EC-04 | ✅ bestanden | Genau 11 geplante → 10 Karten + „**Eine weitere** geplante Idee steht im Board." (Einzahl, nicht „1 weitere") |
| EC-05 | ⚠️ nicht prüfbar | Ein Punkt wandert von „Bewusst nicht gebaut" in eine Spalte — ein redaktioneller Vorgang ohne Auslöser im Code. Die Struktur trägt ihn (`ShelvedItem` ↔ `RoadmapItem` sind getrennte Typen), der Vorgang selbst ist nicht herstellbar |
| EC-06 | ⚠️ nicht prüfbar | Zurückgezogenes Release — ebenfalls redaktionell. Der Prüflauf deckt den Folgezustand ab (jede Version braucht einen Vermerk), nicht den Vorgang |
| EC-07 | ✅ bestanden | `RoadmapFreshnessTest` mit `currentYear` 2026 → 0 `<details>`, 2027 → 1 `<details>` |
| EC-08 | ✅ bestanden | Titel mit **exakt 120 Zeichen** (dem Maximum) ohne Leerzeichen: bricht um (`scrollWidth` = Elementbreite), Seite bleibt bei 320 px überlauffrei. ⚠ **Bei 768 px kollabiert er auf 20 px Breite** — siehe BUG-01 |
| EC-09 | ✅ bestanden | Titel „🚻♿ مقهى بلا حواجز في المدينة" wird dargestellt; `direction` bleibt `ltr` an Element und `body` |
| EC-10 | ✅ bestanden | `/roadmap` und `/changelog` → **302** auf `/lb/…`, **ein** Sprung, kein 301 |
| EC-11 | ✅ bestanden | `CommunityRoadmapTest::testOhneZwischenspeicherWirdGerechnet` + Live: nach `cache:pool:clear` wird korrekt neu berechnet |

## Sicherheitsprüfung

Aktiv angegriffen, nicht gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | **bestanden für Feature 07** — BUG-04 für Feature 06 | Feature 07 hat keine ID-tragende Route. Über den Board-Verweis geprüft: eine wartende Idee **ohne Verfasser** liefert `200` statt `404` (BUG-04) |
| Nicht freigegebene Daten sichtbar | bestanden | Wartende `PLANNED`-Idee → 0 Treffer im ausgelieferten HTML der Roadmap |
| Rate Limit greift | **entfällt bewusst** (Decision Log 7) | 50 Aufrufe in 2 417 ms (Ø 48 ms), danach 200. Der Deckel steht in der Abfrage: 12 im Bestand → 10 geladen |
| PII in Logs | bestanden | 30 Aufrufe → 51 KB Protokoll, 0 Board-Daten, 0 E-Mail-Adressen, 0 Geheimnisbegriffe |
| PII an externe Dienste | bestanden — **kein Dienst** | Keine ausgehende Anfrage; die einzigen fremden URLs sind anklickbare GitHub-Links |
| Zugriffsregeln serverseitig | bestanden | Filter in den Abfragekriterien (`publishedAt IS NOT NULL`, `duplicateOf IS NULL`, `status = PLANNED`), nicht im Template; 405 auf allen Schreibmethoden |
| Geheimnisse im Repository | bestanden | 0 Treffer auf `sk_live_`, `APP_SECRET`, `DATABASE_URL`, `BREVO_API` im Feature-Code und im HTML |
| Eingaben | bestanden mit Einschränkung | 8 Muster (XSS, SQL, Traversal, 10 000 Zeichen, Nullbyte, Array, negative und überlange Zahl) → alle 200, kein roher `<script>`, kein Serverfehler. Spiegelung in `hreflang` → BUG-03 |

## Fehler

### BUG-01 · Community-Titel bei 768 px als senkrechte Buchstabenkolonne — hoch

**Betrifft:** AK-15 (mittelbar EC-08)

**Reproduktion:**
1. Mindestens eine veröffentlichte Idee mit Status `Geplant` anlegen
2. `/de/roadmap` bei **768 px** Fensterbreite öffnen (iPad hochkant, geteiltes Laptop-Fenster)
3. Die erste Karte unter „Aus dem Ideen-Board" ansehen

**Erwartet:** Der Titel ist lesbar, wie bei den kuratierten Einträgen daneben.

**Tatsächlich:** Der Titel steht als **ein Buchstabe pro Zeile**. Gemessen an
„Une idée en français avec accent": **12 px breit, 648 px hoch**. Beim
120-Zeichen-Titel: **20 px breit, 2352 px hoch**. Die kuratierten Einträge derselben
Spalte sind an derselben Stelle 155 px breit.

Messreihe über die Umbruchpunkte (Breite der `h4`-Titel):

| Fenster | Spalte | Community-Titel | kuratierte Titel |
|---|---|---|---|
| 320 px | 288 px | 64 px | 214 px |
| 375 px | 343 px | 119 px | 269 px |
| **768 px** | **229 px** | **12–20 px** | **155 px** |
| 1280 px | 400 px | 176 px | 326 px |

**Beleg:** `qa/BUG-01-768px-titelkolonne.png`

**Ort:** `templates/roadmap/_item.html.twig` — die Community-Variante setzt Titel und
Herkunftszeichen in `<div class="flex items-start justify-between gap-3">`. Das Zeichen
trägt `shrink-0`, der Titel **weder `min-w-0` noch `flex-1`**. Sobald die Spalte schmal
wird, gewinnt das Zeichen den ganzen Platz.

**Vorschlag:** Dieselben Klassen wie in `templates/partials/_board_idea_card.html.twig:86`
— dort löst Feature `06` genau diesen Fall bereits mit
`basis-full min-w-0 sm:basis-auto sm:flex-1`.

**✅ Behoben am 2026-08-30.** `flex-wrap` am Container, `basis-full min-w-0 lg:basis-auto
lg:flex-1` am Titel. ⚠ **Der Umbruchpunkt ist `lg:`, nicht `sm:` wie in Feature `06`:**
Dort steht die Karte über die volle Seitenbreite, hier in einer von drei Spalten — ab
`md:` misst die Spalte nur 229 px, Titel und Zeichen passen erst ab `lg:` nebeneinander.
Mit `sm:` wäre genau der gemessene Fall stehen geblieben.

Gegen die Reproduktion aus diesem Bericht gemessen — die Titel sind jetzt so breit wie
die kuratierten Einträge daneben:

| Fenster | vorher | nachher | kuratierte (Referenz) |
|---|---|---|---|
| 320 px | 64 px | **214 px** | 214 px |
| 375 px | 119 px | **269 px** | 269 px |
| **768 px** | **12 px** | **155 px** | 155 px |
| 1280 px | 176 px | **326 px** | 326 px |

Der 120-Zeichen-Titel braucht statt **2352 px** noch **168 px** Höhe; die gemessene Karte
ist 155 × 48 px statt 12 × 648 px. 320 px bleibt überlauffrei (`scrollWidth = 320` auf
allen acht Aufrufen), axe unverändert bei einem Verstoß (BUG-02).
**Beleg:** `qa/BF-108-behoben-768px.png`

**Neuer Prüflauf:** `tests/Functional/Controller/RoadmapCardLayoutTest.php` — drei Fälle,
darunter einer, der **das Muster statt des Einzelfalls** prüft: Keine Überschrift darf in
einem Flex-Container neben einem `shrink-0`-Element stehen, ohne selbst `min-w-0` oder
`basis-full` zu tragen. Geprüft über **beide** Kartenvorlagen (`/roadmap` und
`/community/ideen`), damit eine dritte Karte dieser Bauart auffällt, bevor sie in die QA
kommt. Zwei Gegenproben: Klassenkette entfernt → 2 Fehlschläge, `flex-wrap` entfernt →
1 Fehlschlag, wiederhergestellt → grün.

⚠ **Das ist BF-107 zum zweiten Mal.** Feature `06` hatte denselben Fehler an derselben
Art von Stelle — Titel neben einem `shrink-0`-Abzeichen im selben Flex-Container — und
brauchte dafür **zwei Anläufe**: `w-full` reichte nicht („die Überschrift schrumpft im
Flex-Container"), `flex-1` deckte den nächsten Fall auf (37 px neben dem Statusabzeichen),
Endstand `basis-full min-w-0 sm:basis-auto sm:flex-1`. Feature `07` hat die Karte neu
gebaut und die Lehre nicht mitgenommen. **Zweites Auftreten = fehlende projektweite
Regel**, siehe `features/befunde.md` → Muster.

⚠ **Warum der Bau es nicht sah:** Gemessen wurde bei 320 px und 1280 px — an beiden
Enden ist es unauffällig. Der Fehler sitzt genau dazwischen, an dem Umbruchpunkt, ab dem
die drei Spalten nebeneinander stehen. Dieselbe Lücke wie bei **BF-80** (Kopfzeile,
768–1000 px), die zweimal übersehen wurde, weil alle Kriterien 320 px und 375 px nennen.
**Empfehlung für die Spec: 768 px als dritte Messbreite aufnehmen.**

### BUG-02 · Überschriftenkette springt seitenweit von h2 auf h4 — mittel

**Betrifft:** AK-34, AK-38 · entspricht **OF-10** aus `spec.md`

**Reproduktion:**
1. `/de/roadmap` öffnen, Debug-Toolbar entfernen
2. axe-core laufen lassen — oder die Überschriften der ganzen Seite auslesen

**Erwartet:** null Verstöße, lückenlose Ebenen.

**Tatsächlich:** `heading-order` (moderate), 1 ×, an
`<h4 class="text-white font-bold …">Links</h4>`. Auf allen acht Seitenaufrufen
(2 Seiten × 4 Sprachen) identisch.

**Ort:** `templates/base.html.twig` — die Fußzeile überschreibt ihre vier Spalten mit
`<h4>`; die letzte Inhaltsüberschrift ist eine `h2`.

⚠ **Projektweit und vorbestehend**, nicht von diesem Feature verursacht: nachgemessen
auf `/de/presse` (`…,2,4,4,4`), `/de/open`, `/de/about`, `/de/vergleich` und
`/de/community/ideen` — überall dasselbe. Innerhalb von `<main>` ist die Kette auf
beiden neuen Seiten lückenlos.

**Vorschlag:** `h4` → `h2` in der Fußzeile. Eine Zeile — die aber **jede** Seite des
Projekts verändert und deshalb zu Feature `02` oder einem eigenen Auftrag an die
App-Hülle gehört, nicht in dieses Feature.

### BUG-03 · `hreflang`-Verweise spiegeln die Abfragezeichenfolge — niedrig

**Betrifft:** AK-44 · entspricht **OF-09** aus `spec.md`

**Reproduktion:** `curl -sk 'https://…/de/roadmap?stage=secret'` → im Kopfbereich steht
`<link rel="alternate" hreflang="lb" href="/lb/roadmap?stage=secret">`

**Erwartet:** Keine Eingabe des Aufrufers in der Antwort.

**Tatsächlich:** Die Abfragezeichenfolge steht in vier Alternativ-Verweisen.
**Kein Sicherheitsproblem** — die Ausgabe ist escaped, `<script>alert(1)</script>` bleibt
wirkungslos (nachgemessen: 0 rohe Treffer).

**Ort:** `templates/base.html.twig:43` — `url(_current_route, _current_params|merge(…))`

⚠ **Projektweit und vorbestehend:** `/presse`, `/open`, `/about`, `/restaurants`
spiegeln ebenso. Gehört zur App-Hülle bzw. zu Feature `B24`.

### BUG-04 · Wartende Idee ohne Verfasser ist öffentlich lesbar — mittel

**Betrifft:** kein Kriterium von Feature 07 — **Fund an Feature `06`**

**Reproduktion:**
1. Eine `BoardIdea` ohne `submittedBy` anlegen, `publishedAt` = null
2. Als **Gast** `/de/community/ideen/{id}-{slug}` aufrufen

**Erwartet:** 404 (so verlangt es AK-18/AK-56 von Feature `06`).

**Tatsächlich:** **HTTP 200**, Titel und Beschreibung vollständig sichtbar — der Titel
steht in `<title>` und im Seitenkopf. Nachgestellt am 2026-08-30.

**Ort:** `src/Controller/BoardController.php:169`
```php
if (!$idea->isPublished() && $this->getUser() !== $idea->getSubmittedBy()) {
```
Bei einem Gast ist `getUser()` gleich `null`, bei einer verfasserlosen Idee
`getSubmittedBy()` ebenfalls — `null !== null` ist **false**, die Sperre greift nicht.

**Warum trotzdem nur *mittel*:** Über den Anwendungsweg entsteht der Zustand heute
nicht. `AccountDeleter::delete()` ruft `deleteUnpublishedBy($user)` **vor**
`remove($user)`; die wartenden Ideen verschwinden also, bevor der Fremdschlüssel
(`SET NULL`, in der Datenbank geprüft) greifen könnte. Die Prüfung ist damit richtig aus
dem falschen Grund: Sobald ein zweiter Weg entsteht — Bestandsimport, Anlage durch die
Verwaltung, ein Eingriff in die Datenbank —, ist die Idee öffentlich, und der
vorhandene Test bemerkt es nicht (er prüft nur den Fall **mit** Verfasser).

**Vorschlag:** `if (!$idea->isPublished() && (null === $this->getUser() || $this->getUser() !== $idea->getSubmittedBy()))`
— und ein Testfall für die verfasserlose wartende Idee.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/RoadmapEmptyStatesTest.php` | 3 | AK-09, AK-25, EC-01 — Zustände, die der Regelbetrieb nicht hergibt |
| `tests/Integration/Roadmap/RoadmapAccountDeletionTest.php` | 2 | AK-48, AK-49 über den echten `AccountDeleter` |

## Nächster Schritt

`/sdd-build 07` mit dem Auftrag, **BUG-01** zu beheben (eine Zeile in
`templates/roadmap/_item.html.twig`, Muster aus `_board_idea_card.html.twig:86`), danach
erneut `/sdd-qa 07` — der dritte Messpunkt **768 px** gehört dabei in die Reihe.

**BUG-02 und BUG-03 blockieren nicht** und gehören nicht in dieses Feature: Beide sind
Altlasten der App-Hülle und in `features/befunde.md` vermerkt. **BUG-04 gehört zu
Feature `06`** und wartet dort auf eine Entscheidung — er blockiert die Auslieferung von
`07` nicht, betrifft aber Code, der bereits auf `dev` liegt.

---

# Zweiter Durchlauf (2026-08-30, nachmittags)

Geprüft wurde nicht die Reparatur allein, sondern **ihre Umgebung** — nach der Lehre, die
BF-108 selbst geliefert hat: *Gemessen wird an den Enden, kaputt ist die Mitte.*

| | Anzahl (2. Durchlauf) |
|---|---|
| Kriterien neu bewertet | 4 (AK-15, AK-34, AK-38, EC-08) |
| **Gesamtstand** | **49 von 52 bestanden**, 3 durchgefallen, 0 nicht prüfbar |
| Edge Cases belegt | 9 von 11 (unverändert) |
| neue Befunde | **keiner** |
| Tests grün | 915 von 915 (10 übersprungen, aus anderen Features) |

## Was neu bewertet wurde

| AK | vorher | jetzt | Nachweis |
|---|---|---|---|
| **AK-15** | ❌ | ✅ **bestanden** | Feine Messreihe **320–1440 px in 32-px-Schritten**: Der Community-Titel ist auf der **gesamten** Strecke exakt so breit wie die kuratierten Einträge daneben (Verhältnis 1,00 von 320 bis 992 px). Bei 768 px: **155 px statt 12 px**. Beleg: `qa/BF-108-behoben-768px.png` |
| **EC-08** | ✅ mit Einschränkung | ✅ **ohne Einschränkung** | 120-Zeichen-Titel ohne Leerzeichen: bei 768 px **155 × 168 px** statt 20 × 2352 px; bei 1024 px 241 × 120 px |
| AK-34 | ❌ | ❌ **unverändert** | axe über alle 8 Seiten: weiterhin genau **1** Verstoß, `heading-order` — BF-109, App-Hülle |
| AK-38 | ❌ | ❌ **unverändert** | dieselbe Ursache |
| AK-44 | ❌ | ❌ **unverändert** | BF-110, App-Hülle |

## Die feine Messreihe

Der eigentliche Prüfschritt dieses Durchlaufs: **36 Messpunkte** statt vier.

| Breite | Spalte | Community-Titel | kuratiert | Verhältnis |
|---|---|---|---|---|
| 320 px | 288 px | 214 px | 214 px | 1,00 |
| 512 px | 480 px | 406 px | 406 px | 1,00 |
| 704 px | 608 px | 534 px | 534 px | 1,00 |
| **768–992 px** | 229 px | **155 px** | 155 px | **1,00** |
| 1024–1248 px | 315 px | 241 px | 241 px | 1,00 |
| 1280–1440 px | 400 px | 326 px | 326 px | 1,00 |

⚠ **Ein Messwert sah zunächst nach einem Befund aus und war keiner.** Das Minimum über
alle Titel fiel ab 1024 px auf 91 px (Verhältnis 0,38). Nachgesehen: Es betrifft
ausschließlich den Titel **„Kurz"** — 91 × 24 px, eine Zeile, vollständig lesbar. Ab
`lg:` greift `lg:flex-1`, und ein kurzer Titel *soll* schmal sein; das Zeichen steht dann
daneben statt darunter. Alle übrigen Titel wachsen dort von 155 auf 241 px. **Die
`lg:`-Annahme des Bauberichts trägt** — geprüft an 1008, 1024, 1152 und 1280 px.

## Gegenproben zum neuen Prüflauf

Der Baubericht behauptet, `RoadmapCardLayoutTest` fange **das Muster über beide
Kartenvorlagen**. Unabhängig nachgestellt — mit einem anderen Eingriff als der Bau:

1. `basis-full min-w-0 sm:basis-auto sm:flex-1` aus **`_board_idea_card.html.twig`
   (Feature 06)** entfernt, Roadmap unberührt → Lauf **rot**, nennt
   `/de/community/ideen: „Titel für die Musterprüfung"`
2. Wiederhergestellt → grün

Der Lauf würde also auch eine Regression an Feature `06` fangen. Das ist der Ertrag aus
„zweites Auftreten" — und zugleich der Hinweis unten.

## Angriffsdurchlauf

Vollständig wiederholt; unverändert gegenüber dem ersten Durchlauf, mit einer Ergänzung:

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Querscrollen über den ganzen Bereich | bestanden für Feature 07 | 36 Messpunkte 320–1440 px: kein Überhang aus dem Inhalt. **Bei 768–832 px scrollt die Seite um 36 → 4 px** — mit ausgeblendetem `<header>` **0 px**, und auf `/presse`, `/open`, `/about`, `/community/ideen` **identisch**. Das ist **BF-80**, nicht dieses Feature |
| axe, vier Sprachen, beide Seiten | 1 Verstoß (BF-109) | unverändert |
| Tastatur | bestanden | 7 von 7 fokussierbaren Elementen erreicht, 0 ohne sichtbaren Fokus |
| Konsole | bestanden | 0 Fehler auf allen 8 Aufrufen |
| Prüfdaten | restlos entfernt | 5 angelegt, 5 entfernt, `SELECT COUNT(*) FROM board_idea` → **0** |

## Hinweis (kein Befund)

**Der Musterlauf liegt in der Testdatei von Feature `07`, prüft aber auch Feature `06`.**
Belegt durch die Gegenprobe oben: Wer `_board_idea_card.html.twig` umbaut, bekommt einen
roten Lauf in `RoadmapCardLayoutTest` — und sucht den Fehler zunächst am falschen Feature.
Der Baubericht meldet das als systemweite Änderung; hier steht es als Beobachtung mit
einem Vorschlag: Bei nächster Gelegenheit gehört der Musterlauf an eine neutrale Stelle
(etwa `tests/Functional/CardLayoutTest.php`), weil er eine **projektweite** Regel prüft
und kein Feature-Kriterium. **Kein durchgefallenes Kriterium** — die Spec kennt diese
Anforderung nicht.

## Nächster Schritt

`/sdd-deploy 07`.

⚠ **Reihenfolge:** Feature `06` muss **vor** `07` auf `production` sein (VB-01) — die
Roadmap liest dessen Bestand und verlinkt auf `app_board_index`. Wird `07` zuerst
ausgeliefert, zeigt die Spalte „Geplant" nur die kuratierten Vorhaben und die Fußzeile
verweist ins Leere.

⚠ **Was mit ausgeliefert wird, ohne behoben zu sein:** BF-109 und BF-110 (App-Hülle,
betreffen jede Seite und sind seit BF-80 bzw. B24 vorbestehend) sowie BF-111 (Feature
`06`, liegt auf `dev`). Alle drei stehen in `features/befunde.md`.
