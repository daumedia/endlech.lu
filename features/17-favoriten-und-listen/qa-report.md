# 17 · Favoriten und Listen — Testbericht (dritter Durchlauf)

Stand: 2026-11-14 · Geprüft gegen `spec.md` vom 2026-11-14 (AK-01 bis AK-70, EC-01 bis EC-16, Decision Log bis #26) · in
eigener Session, ohne Bauverlauf · Vorstufe `building` nach dem zweiten Fehlerauftrag (BF-218, BF-219, BF-220). Der
*Abschlussbericht · Zweiter Fehlerauftrag* am Ende von `tasks.md` und die Behebungsvermerke des zweiten Berichts dienten als
Suchliste, nicht als Nachweis. Frühere Fassungen: `qa/17/qa-report-erster-durchlauf-2026-11-14.md`,
`qa/17/qa-report-zweiter-durchlauf-2026-11-14.md`.

**Prüfumgebung.** Arbeitsbaum `feature/17-favoriten-und-listen` (Reparaturen uncommittet), Entwicklungsserver
`http://127.0.0.1:8000` gegen die Dev-Datenbank (MySQL 8, Container `mikaplus-database-1`). Tailwind-Watcher lief,
`var/tailwind/app.built.css` (17:22:31) so alt wie die zuletzt geänderte Vorlage und mit `.focus\:-outline-offset-4`,
`.focus\:outline-gray-900`, `.wrap-anywhere`. Anfragen mit `curl` und Cookie-Dateien je Konto; Browser **Chromium und WebKit
über Playwright** (`/tmp/qa17/pw`), axe-core 4 mit entfernter Debug-Leiste. Alle Mess- und Prüfskripte sind eigene
(`/tmp/qa17c/bin/`); Skripte und Rohdaten des Baus (`/tmp/build17b/`) wurden nicht benutzt. Testkonten
`qa17ca`, `qa17cb`, `qa17cd` … `qa17ch@example.test`. **Drei echte Titelfotos** (Unsplash, 1600 px, über die Verwaltung
hochgeladen — derselbe Weg wie im Betrieb): *hell* Salatschale auf hellem Grund an Lokal 12 (mittlere Leuchtdichte 0,57),
*mittel* gedeckter Tisch an Lokal 14 (0,28), *dunkel* Restaurant innen an Lokal 18 (0,14). Alles nach der Prüfung entfernt
(siehe *Testdaten*). Nachweise unter `qa/17/` mit Präfix `QA3-`.

## Fazit

**Production-ready: ja** — kein Befund *kritisch* oder *hoch*, kein durchgefallenes Kriterium.

**Der zweite Fehlerauftrag hat gehalten, alle drei Befunde sind behoben.** Das Herz hat jetzt **überall** einen sichtbaren
Fokusrahmen: am Pixel gemessen auf dem Verlauf, über einem hellen, einem mittleren und einem dunklen echten Titelfoto, im Kopf
der Detailseite und in der Karte auf Suche, „Favoriten“ und Listenseite, bei 1280 und 375 px, in Chromium und WebKit —
**56 Messungen, dazu 12 nach dem Umschalten per Turbo-Stream: schwächster Strahl 13,05 : 1, jeweils alle 360 Strahlen ≥ 3 : 1**. Mit den Klassen von vorher (Gegenprobe am
laufenden Server) lag der Kopf bei 1,00–1,27 : 1 und die Karte über dem mittleren Foto bei 1,05–1,12 : 1 — **die Karte hatte
den Befund tatsächlich**, sobald ein Lokal ein Titelfoto trägt; der Bau hatte das richtig erkannt und mitbehoben. Lange
Listennamen sprengen „In Listen“ nicht mehr (`scrollWidth` 534 → 320 bzw. 375 px, beide Browser). „ΟΔΟΣ“ und „οδος“ sind
wieder eine Dublette, auf allen drei Wegen und auch gleichzeitig abgeschickt.

**BF-220 und die Eigenschaft aus BF-211.** Über alle 1 112 064 Codepunkte ist der Schlüssel nie länger als der Name; auch
Zeichen, die NFC in zwei oder drei Codepunkte zerlegt, werden normalisiert gezählt und sauber mit 422 abgewiesen. Die
**Nebenwirkung ist unbedenklich**: Die Faltung ändert den Schlüssel bei 194 Codepunkten, neu gleich werden nur Formvarianten
eines Buchstabens (ſ/s, µ/μ, ς/σ, ϐ/β, ϑ/θ, ϕ/φ, ϖ/π, ϰ/κ, ϱ/ρ, ϵ/ε, ẛ/ṡ, ﬅ/ﬆ, neun altkyrillische Varianten, die
kombinierende Ypogegrammeni; Cherokee wechselt nur die Schreibweise des Schlüssels). Das ist Unicodes Definition von
Gleichheit ohne Groß- und Kleinschreibung und genau die Paare, die als Häkchen nicht unterscheidbar wären — der Grund von
Decision Log 11. Verschieden bleiben Akzente („Café“/„Cafe“), das punktlose „ı“, „ß“/„ss“ und Ligatur/Buchstabenfolge.

**Jede Reparatur hat einen Prüflauf, der ohne sie rot wird** (drei Rücknahmen, je rot gesehen, wiederhergestellt und per
Prüfsumme bestätigt), und jede Rücknahme zeigte den Fehler auch am laufenden Server wieder.

Nicht prüfbar bleiben **AK-48** (Ansage durch VoiceOver), **AK-51** (Abnahme des Betreibers) und **AK-70** (erst mit dem
Release, T29). Keine neuen Befunde; drei Hinweise ohne Befundcharakter (H-1 bis H-3).

**Nächster Schritt: `/sdd-deploy 17`** mit T29 im Release-Commit und **Migration vor dem Code**. Der uncommittete
Fehlerauftrag von B04 (BF-226) liegt auf demselben Branch und wartet auf **`/sdd-qa B04`** — er geht mit diesem Release live;
der Hauptweg des Profils ist hier nachgeprüft (Regression), die Abnahme von B04 nicht.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 67 von 70 |
| davon bestanden | 67 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 3 (AK-48, AK-51, AK-70) |
| Edge Cases belegt | 16 von 16, alle bestanden |
| Befunde des Fehlerauftrags nachgeprüft | 3 von 3 behoben (BF-218, BF-219, BF-220) |
| Tests neu geschrieben | 15 Fälle in 2 Dateien |
| Tests grün | 15 von 15; mit zurückgenommener BF-220-Reparatur 7 rot (die übrigen 8 prüfen, was gleich bleiben muss) |
| Volle Suite gelaufen | **2 345 Tests, 0 Fehlschläge, 44 übersprungen** (vor den neuen Tests 2 330 / 0 / 44 — wie der Abschlussbericht) |
| Regression: berührte Features | 15, davon Hauptweg bestanden 15 |

## Befunde des Fehlerauftrags — Nachprüfung

| Befund | Behoben? | Nachweis am laufenden System | Gegenprobe (Reparatur zurückgenommen) |
|---|---|---|---|
| BF-218 · Herz ohne sichtbaren Fokusrahmen (AK-49) | **ja** | 14 Lagen × 1280/375 px × Chromium/WebKit = 56 Messungen: Kopf auf dem Verlauf (Lokal 13, 20), Kopf über hellem/mittlerem/dunklem Titelfoto (12, 14, 18), Karte in der Suche mit drei Fotos und ohne Foto, Karte auf „Favoriten“ und auf einer Listenseite mit Fotos. Schwächster von 360 Strahlen **13,05–17,75 : 1**, je 360/360 Strahlen ≥ 3 : 1, Fläche ≥ 3 : 1 241–256 px²; `outline: solid 2px oklch(0.21 0.034 264.665)`, `outline-offset: -4px`, `:focus-visible` (`qa/17/QA3-BF218-messung.txt`, Ausschnitte `QA3-BF218-jetzt-*.png`). Nach dem Turbo-Stream (Enter, Karte und Kopf mit/ohne Foto, beide Browser): Fokus bleibt, Rahmen 17,75 : 1. Erzwungene Farben (Kontrastmodus): Rahmen sichtbar, 234 px² ≥ 3 : 1 (`QA3-AK49-kontrastmodus-*.png`) | Klassen zurück auf `focus:outline-offset-2 focus:outline-cyan-700`: `DetailHeartVariantsTest::testBf218…` und `Qa17NachpruefungTest::testBf218…` **rot**; am Server Kopf auf dem Verlauf **1,00–1,12 : 1, 0/360 Strahlen**, Kopf über Foto 1,02–1,27 : 1, Karte über dem mittleren Foto 1,05–1,12 : 1, über dem dunklen 1,07–1,85 : 1; nur Karte ohne Foto und über dem hellen Foto ≥ 3 : 1 (`QA3-BF218-mutiert-*.png`). Wiederhergestellt, Prüfsumme `5e097a03…` = vorher |
| BF-219 · langer Listenname sprengt „In Listen“ | **ja** | Listen „Wochenendausflugsrestaurantauswahl“ (34) und ein 60-stelliger Name ohne Leerzeichen, Detailseiten 12 und 22: `scrollWidth` **320 bei 320 px, 375 bei 375 px**, rechte Kante der Beschriftungen 279 bzw. 334 px — Chromium und WebKit (`QA3-BF219-messung.txt`, `QA3-BF219-jetzt-*.png`). Häkchen per Klick auf die Beschriftung umschaltbar, Speichern → „Listen gespeichert.“, Bereich nicht am Seitenanfang | Klassen zurück: `RestaurantListsAndNoteTest::testBf219…` **rot**; am Server `scrollWidth` **534** bei 320 und 375 px, beide Browser (`QA3-BF219-vorher-mutiert-*.png`). Wiederhergestellt, Prüfsumme `d7a2e4f2…` = vorher |
| BF-220 · Schluss-Sigma umgeht die Dublettenprüfung | **ja** | „ΟΔΟΣ“ 303, „οδος“/„Οδοσ“/„ΟΔΟΣ “ je **422** „Eine Liste mit diesem Namen hast du schon.“; auf der Detailseite „κιρχβεργ“ neben „ΚΙΡΧΒΕΡΓ“ 422, Häkchen unverändert; Umbenennen auf „κιρχβεργ“ 422; 8 Schreibweisen gleichzeitig → 303 ×1, 422 ×7, eine Zeile (`QA3-BF220-server.txt`) | `nameKey()` nur einfache Kleinschreibung: `SavedTextTest`, `SavedListServiceTest`, `Qa17NachpruefungTextTest` je `testBf220…` **rot**; am Server „ΟΔΟΣ“ 303 und „οδος“ 303 → zwei Listen. Wiederhergestellt, Prüfsumme `37013eb1…` = vorher |

### Bewertung der Nebenwirkung von BF-220

Ausgezählt mit `SavedText` selbst über alle 1 112 064 Codepunkte (PHP 8.5.12, `qa/17/QA3-BF220-codepunkte.txt`):

- **Eigenschaft aus BF-211 hält:** 0 Codepunkte mit einem Schlüssel, der länger ist als der normalisierte Name. Zeichen, die
  NFC zerlegt (U+0344, U+0F73, U+FB2C, U+1D160, U+2ADC), werden **nach** der Zerlegung gezählt: 60 davon → 422 „Der Name darf
  höchstens 60 Zeichen lang sein.“, 20 × U+FB2C (= 60 Codepunkte) → 303 mit Name und Schlüssel je 60. Kein 500 im Protokoll.
- **Neu gleich** (Schlüssel ändert sich bei 194 Codepunkten): 25 Formvarianten (ſ, µ, ς, ϐ ϑ ϕ ϖ ϰ ϱ ϵ, ẛ, ﬅ, ᲀ–ᲈ, U+0345)
  werden auf ihren Grundbuchstaben gefaltet; bei den 169 Cherokee-Zeichen waren Groß und Klein schon vorher gleich, nur der
  Schlüssel steht jetzt in Großbuchstaben. Am Server: „Paſta“ 303 → „Pasta“/„PASTA“ 422; „5 µg“ 303 → „5 μg“/„5 ΜG“ 422.
- **Verschieden bleibt:** „Ὀδός“ neben „ΟΔΟΣ“ (Akzent), „ıstanbul“ neben „İstanbul“ (punktloses ı), „Straße“/„Strasse“,
  „ﬆ“/„st“.
- **Urteil: unbedenklich, kein Befund.** Es ist Unicodes einfache Faltung — die Standarddefinition von „ohne Unterschied der
  Groß- und Kleinschreibung“ (Decision Log 11) —, sie macht nur Namen gleich, die als Häkchen nicht unterscheidbar wären (der
  Grund von DL 11), und sie nimmt niemandem eine Liste: Wer „5 µg“ hat, bekommt bei „5 μg“ die gewohnte Meldung. Keine
  Datenmigration nötig — 17 ist nicht ausgeliefert; alle 19 Zeilen der Dev-Datenbank tragen `name_key = nameKey(name)`.

## Akzeptanzkriterien im Einzelnen

„Live“ = in diesem Durchlauf am laufenden Server ausgeführt; „Suite“ = in der vollen Suite dieses Durchlaufs grün gelaufen
(`qa/17` und `/tmp/qa17c/out/suite2-junit.xml`). Code, den dieser Fehlerauftrag nicht berührt, ist seit der zweiten QA
unverändert (`git diff` dort: nur `SavedText.php`, `_favorite_button`, `_saved_panel`, dazu `ProfileController` aus B04).

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Live (Chromium/WebKit, Tastatur, Turbo): Herz Nr. 15 in der Suche → Name „Le Jardin Brasserie aus Favoriten entfernen“, Ansage „Gemerkt: Le Jardin Brasserie“, Adresse unverändert, Herz bleibt bei `top` 378 px; Suite `FavoriteHeartTest::testAk01…`, `SearchFavoritesTest` |
| AK-02 | ✅ bestanden | Live: Kopf Nr. 13 (Verlauf) und Nr. 12 (Titelfoto) per Enter gemerkt/entfernt, Ansage, Fokus bleibt; Suite `DetailHeartVariantsTest::testAk02…`, `FavoriteHeartTest::testAk02…`. Hinweis H-1 |
| AK-03 | ✅ bestanden | Live: Nr. 12 (in Liste „Fotos“) entfernt → „Aus Favoriten entfernt: Pizzeria Bella Vista“, Listeneintrag bleibt (Liste 115 weiter 4); Suite `FavoriteHeartTest::testAk03…`, `FavoriteServiceTest::testAk03…` |
| AK-04 | ✅ bestanden | Suite `FavoriteServiceTest::testAk04…`, `SavedStateTest::testAk04…` (3) |
| AK-05 | ✅ bestanden | Suite `FavoriteHeartTest::testAk05…`, `FavoriteServiceTest` (4) |
| AK-06 | ✅ bestanden | Suite `Qa17PruefungTest::testAk06OhneJavaScriptSeite2…`, `PendingFavoriteTest` (15) |
| AK-07 | ✅ bestanden | Live: Gast-Suche 6 Herzen, alle „… merken“; Suite `RestaurantListsAndNoteTest::testAk07…`, `SearchFavoritesTest::testAk07…` |
| AK-08 | ✅ bestanden | Suite `FavoriteHeartTest::testAk08…`, `PendingFavoriteLoginTest::testAk08…` (4) |
| AK-09 | ✅ bestanden | Suite `PendingFavoriteLoginTest::testAk09…` inkl. Passkey-Weg (17) |
| AK-10 | ✅ bestanden | Suite `PendingFavoriteLoginTest::testAk10…` |
| AK-11 | ✅ bestanden | Live: „Gespeichert“ zeigt „Mittag in Kirchberg 0 Lokale“ zuoberst; Suite `SavedPagesTest::testAk11…` |
| AK-12 | ✅ bestanden | Suite `SavedPagesTest::testAk12FuenfzehnFavoriten…`, `SavedCardsTest::testAk12…` |
| AK-13 | ✅ bestanden | Suite `SavedPagesTest::testAk13…` (2) |
| AK-14 | ✅ bestanden | Suite `SavedCardsTest::testAk14…` (6) |
| AK-15 | ✅ bestanden | Suite `SavedPagesTest::testAk15…` (3) |
| AK-16 | ✅ bestanden | Live: „Mittag in Kirchberg“ → 303, „Liste „Mittag in Kirchberg“ angelegt.“, zuoberst mit „0 Lokale“; Suite `SavedListManageTest::testAk16…` |
| AK-17 | ✅ bestanden | Live: leer und fünf Leerzeichen → 422 „Bitte gib der Liste einen Namen.“; im Browser Fokus `#saved_list_name_name` (Chromium/WebKit) |
| AK-18 | ✅ bestanden | Live: 61 × „a“ → 422 „Der Name darf höchstens 60 Zeichen lang sein.“, Eingabe im Feld; „  “ + 60 × „b“ + „  “ → gespeichert ohne Ränder, 60 Zeichen; NFC-zerlegende Zeichen siehe BF-220 |
| AK-19 | ✅ bestanden | Live: „Kirchberg“, dann „ kirchberg “ → 422 „Eine Liste mit diesem Namen hast du schon.“; anderes Konto legt „Kirchberg“ an (zwei Zeilen, Konten 83 und 84); Schreibvarianten siehe BF-220 |
| AK-20 | ✅ bestanden | Live: 25 Listen, 26. auf „Gespeichert“ und als „Mit Oma“ auf der Detailseite → je 422 „Du hast bereits 25 Listen. Lösche eine, um eine neue anzulegen.“, bleibt 25 |
| AK-21 | ✅ bestanden | Live: Konto b, Nr. 13 in „Kirchberg“: drei Häkchen (122, 121, 119), genau 119 gesetzt, Feld „Neue Liste“, „Speichern“ |
| AK-22 | ✅ bestanden | Live: auf „Parallel“ umgehängt → 303 `#in-listen`, „Listen gespeichert.“, DB nur „Parallel“, Herz 0 → 0, „Gespeichert“: Parallel 1, Dritte 0, Kirchberg 0. Im Browser per Klick auf die Beschriftung, beide Engines |
| AK-23 | ✅ bestanden | Live: „Mit Oma“ → Liste enthält Nr. 13; „kirchberg“, „KIRCHBERG“, „κιρχβεργ“ (Dublette) und 61 Zeichen je mit Häkchenänderung → 422, Einträge vorher = nachher (`Dritte, ΚΙΡΧΒΕΡΓ`) |
| AK-24 | ✅ bestanden | Live: Konto ohne Liste → 0 Häkchen, nur `restaurant_lists[newList]` |
| AK-25 | ✅ bestanden | Suite `SavedPagesTest::testAk25…` |
| AK-26 | ✅ bestanden | Suite `SavedCardsTest::testAk26…` (7) |
| AK-27 | ✅ bestanden | Suite `SavedPagesTest::testAk27…` |
| AK-28 | ✅ bestanden | Live: „Kirchberg“ → „KIRCHBERG“ (eigener Name) 303, → „Kirchberg mittags“ 303 (`h1` der Listenseite), → „parallel“ und „κιρχβεργ“ 422, leer 422; Name danach „Kirchberg mittags“ |
| AK-29 | ✅ bestanden | Suite `SavedListManageTest::testAk29…` |
| AK-30 | ✅ bestanden | Suite `SavedCascadeTest::testAk30…`, `SavedListManageTest::testAk30…` (4) |
| AK-31 | ✅ bestanden | Suite `RestaurantListsAndNoteTest::testAk31…`, `SavedListServiceTest::testAk31…` |
| AK-32 | ✅ bestanden | Suite `RestaurantListsAndNoteTest::testAk32Ak33…`, `SavedFormTypesTest::testAk32…` |
| AK-33 | ✅ bestanden | Live: Notiz gespeichert → 303 `#notiz`; Suite `RestaurantNoteServiceTest::testAk33…` |
| AK-34 | ✅ bestanden | Suite `SavedCardsTest::testAk34…`, `SavedStateTest::testAk34…` (3) |
| AK-35 | ✅ bestanden | Suite `RestaurantListsAndNoteTest::testAk35…`, `RestaurantNoteServiceTest` (10) |
| AK-36 | ✅ bestanden | Suite `RestaurantListsAndNoteTest::testAk36…` (5) |
| AK-37 | ✅ bestanden | Suite `RestaurantListsAndNoteTest::testAk37NotizMitHtmlBleibtText` |
| AK-38 | ✅ bestanden | Live: `?favorites=1` 200 (Gast und Konto); Suite `SearchFavoritesTest::testAk38…` (3) |
| AK-39 | ✅ bestanden | Suite `SearchFavoritesTest::testAk39…` |
| AK-40 | ✅ bestanden | Suite `SearchFavoritesTest::testAk40…` (2) |
| AK-41 | ✅ bestanden | Live: angemeldet „Gespeichert“ in der Leiste, `aria-current` auf `/xx/gespeichert`; Suite `SavedAppShellTest::testAk41…` (3) |
| AK-42 | ✅ bestanden | Live, Chromium 320 px, angemeldet, lb/de/fr/en/pt: je 4 Punkte 80 × 60 px, jede Beschriftung einzeilig, kein Überlauf, `scrollWidth` 320 |
| AK-43 | ✅ bestanden | Suite `SavedAppShellTest::testAk43…` (2) |
| AK-44 | ✅ bestanden | Live: `/de/profile` 200, 3 Verweise auf `/de/gespeichert`; Suite `SavedAppShellTest::testAk44…` |
| AK-45 | ✅ bestanden | Live: `/sitemap.xml` 200, 0 × „gespeichert“; Suite `SavedPagesTest::testAk45…` |
| AK-46 | ✅ bestanden | Live: Leiste in fünf Sprachen übersetzt (AK-42); Suite `SavedEdgeCasesTest::testEc14Ak46…` (5 Sprachen), `CatalogueCompletenessTest` |
| AK-47 | ✅ bestanden | Live: zugängliche Namen der sechs Karten der Suche „Pizzeria Bella Vista merken | Sushi Zen merken | Le Jardin Brasserie merken | Green Bowl merken | Wäinhaus am Markt merken | Burger & Co. merken“, nach dem Merken „… aus Favoriten entfernen“; VoiceOver selbst nicht gelaufen |
| AK-48 | ⚠️ nicht prüfbar | Ansage durch VoiceOver: kein Screenreader in der Prüfumgebung. Gemessen in Chromium **und WebKit** (Enter auf dem Herz, Karte und Kopf, mit und ohne Titelfoto, hin und zurück): Fokus bleibt auf `favorit-knopf-NN` (`:focus-visible`), Name wechselt, `#meldung-live` (`role="status"`) „Gemerkt: …“ bzw. „Aus Favoriten entfernt: …“, Rahmen sichtbar (17,75 : 1) — `QA3-BF218-messung.txt`, Abschnitt „nach dem Turbo-Stream“ |
| AK-49 | ✅ bestanden | Form: gefüllt (`fill`) gegen Umriss (`fill: none`); Symbol red-600 auf Weiß **4,77 : 1**, gray-700 **10,30 : 1**; Knopf **44 × 44 px** überall; **Fokusrahmen sichtbar in jeder Lage** — siehe BF-218 oben (56 + 12 Messungen ≥ 13,05 : 1, Kontrastmodus sichtbar) |
| AK-50 | ✅ bestanden | Live, Chromium und WebKit: Dublette „DRITTE“ bzw. „κιρχβεργ“ unter „Neue Liste“ → HTTP 422, Fokus `#restaurant_lists_newList`, Meldung im `…_error`, `aria-invalid="true"`, `aria-describedby="…_help …_error"`, Eingabe bleibt; leerer Name auf „Gespeichert“ → Fokus `#saved_list_name_name` |
| AK-51 | ⚠️ nicht prüfbar | Abnahme des Betreibers (Decision Log 20). Vom Prüfer gemessen: Detailseite mit langen Listennamen ohne waagerechte Scrollleiste (BF-219 behoben), „In Listen“ nach dem Speichern nicht am Seitenanfang (`top` 181 px); Herz-Klick ohne Sprung an den Anfang (H-1 beachten) |
| AK-52 | ✅ bestanden | Suite `RestaurantListsAndNoteTest::testAk52…`, `SavedStateTest::testAk52…` (5) |
| AK-53 | ✅ bestanden | Live: Konto b auf Liste 115 (Konto a): Liste, `?page=2`, Rückfrage → je 404 „Not Found“, Listenname nicht im Dokument, wie Nr. 99999; Suite `SavedPagesTest::testAk53…` |
| AK-54 | ✅ bestanden | Live: Konto b auf Liste 115: Umbenennen, Löschen, Entfernen (mit gültigem Token von a, mit leerem Token), Häkchen auf fremde Liste → je 404; Liste „Fotos“ 4 Einträge vorher = nachher |
| AK-55 | ✅ bestanden | Suite `FavoriteHeartTest::testAk55…`, `SavedListManageTest::testAk55…` |
| AK-56 | ✅ bestanden | Live: falsches Token am Herz → 303 + „Ungültiges CSRF-Token. Bitte versuche es erneut.“; fremde Herkunft an Liste und „Neue Liste“ → 303 ohne Wirkung; Bestand von a (Favoriten/Listen/Einträge/Notizen 4/11/4/0) vorher = nachher, 0 × „Evil“ |
| AK-57 | ✅ bestanden | Live, frisches Konto: 120 Änderungen (60 × merken + entfernen) je 303; die **121.** → „Zu viele Änderungen. Bitte versuche es in 60 Minuten erneut.“ (Stream), Herz unverändert; Liste anlegen danach → Meldung, keine Liste; Konto b am selben Anschluss → 303 |
| AK-58 | ✅ bestanden | Suite `Qa17PruefungTest::testAk58…` |
| AK-59 | ✅ bestanden | Suite `SavedPrivacyLeakTest::testAk59…` |
| AK-60 | ✅ bestanden | Live: `/de/profile/daten` 200 JSON, `favorites` 4, `savedLists` 11 (u. a. „Fotos“ mit 4 Lokalen), `notes`; Suite `SavedExportTest` (2) |
| AK-61 | ✅ bestanden | Live: Konto `qa17cd` (1 Favorit, 25 Listen, 1 Eintrag, 1 Notiz) über das Profil gelöscht → 302; `user`, `favorite`, `saved_list`, `restaurant_note` je 0, keine verwaisten Einträge; Anmeldung danach → `/de/login` |
| AK-62 | ✅ bestanden | Suite `SavedCascadeTest::testAk62…` |
| AK-63 | ✅ bestanden | Suite `PrivatePageCacheTest` (13); echtes Safari weiter nicht geprüft |
| AK-64 | ✅ bestanden | Live: `var/log/dev.log` ab Prüfbeginn (19 765 Zeilen): fünf Listennamen/Notiztexte des Laufs nur in `doctrine.DEBUG`; Suite `SavedPrivacyLeakTest`, `Feature17UsageEventsTest::testAk64…` |
| AK-65 | ✅ bestanden | Suite `Qa17SentryAnfragerumpfTest` (3, echte `RequestIntegration`) |
| AK-66 | ✅ bestanden | Suite `Feature17UsageEventsTest::testAk66…` (10) |
| AK-67 | ✅ bestanden | Suite `Feature17UsageEventsTest::testAk67…` (12) |
| AK-68 | ✅ bestanden | Suite `SavedPrivacyStatementTest::testAk68…` (5 Sprachen) |
| AK-69 | ✅ bestanden | Suite `FavoriteHeartTest::testAk69…`, `RestaurantListsAndNoteTest::testAk69…` (4) |
| AK-70 | ⚠️ nicht prüfbar | Gilt nach der Auslieferung (T29 im Release-Commit); `RoadmapRegistry` führt `favourites` noch unter „Geplant“ |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Suite `Qa17PruefungTest::testEc01…` |
| EC-02 | ✅ bestanden | Suite `PendingFavoriteLoginTest::testEc02…` |
| EC-03 | ✅ bestanden | Suite `PendingFavoriteLoginTest::testEc03…` |
| EC-04 | ✅ bestanden | Suite `PendingFavoriteLoginTest::testEc04…` |
| EC-05 | ✅ bestanden | Live: 8 Schreibweisen von „ΟΔΟΣ“ gleichzeitig → 303 ×1, 422 ×7, eine Zeile; 6 Schreibweisen von „Paſta“ → 303 ×1, 422 ×5; 24 Listen + 6 gleichzeitige Anlagen → 303 ×1, 422 ×5, 25 Listen; kein 500 |
| EC-06 | ✅ bestanden | Suite `RestaurantNoteServiceTest::testEc06…` |
| EC-07 | ✅ bestanden | Live: 60 × U+0344/U+FB2C/U+1D160/U+0F73/U+2ADC → 422 mit Meldung aus AK-18; 20 × U+FB2C → gespeichert (60/60); kein Serverfehler; Darstellung langer Namen bei 320 px ohne Überlauf (BF-219). Neu: `Qa17Nachpruefung3Test::testEc07…`, `Qa17Nachpruefung3TextTest::testEc07…` |
| EC-08 | ✅ bestanden | Suite `RestaurantNoteServiceTest::testEc08…` (4) |
| EC-09 | ✅ bestanden | Suite `SavedListManageTest::testEc09…` |
| EC-10 | ✅ bestanden | Suite `SavedEdgeCasesTest::testEc10…` |
| EC-11 | ✅ bestanden | Live, Chromium mit Service Worker: Cache `endlech-v5` nach Besuch der Liste 45 Einträge, 0 mit „gespeichert“ (Offline-Seite, Logo, Icons, Manifest, `/assets/…`); offline `/de/gespeichert/listen/173` → „Offline – Endlech.lu“, Listenname und Notiz nicht im Dokument |
| EC-12 | ✅ bestanden | Suite `SavedEdgeCasesTest::testEc12…` |
| EC-13 | ✅ bestanden | Suite `SavedEdgeCasesTest::testEc13…` |
| EC-14 | ✅ bestanden | Suite `SavedEdgeCasesTest::testEc14Ak46…` (5) |
| EC-15 | ✅ bestanden | Suite `SavedCardsTest::testEc15…`, `SavedPagesTest::testEc15…` |
| EC-16 | ✅ bestanden | Live: Suche `?page=3` 404; Suite `SavedPagesTest::testEc16…` (5) |

## Regression

Volle Testsuite `php -d memory_limit=2G bin/phpunit`:

- **Vor den neuen Tests: 2 330 Tests, 0 Fehlschläge, 44 übersprungen** (1:22 min) — wie im Abschlussbericht des Baus. Die 44
  übersprungenen sind die bekannten Reproduktionen anderer Features (u. a. `Qa16NachpruefungTest` 11, `Qa15NachpruefungTest` 9,
  `ComparisonEdgeCaseTest` 7, `Qa17NachpruefungTest` 5 — BF-221 ×3, BF-222, BF-223); die beiden bekannten Wackler BF-225 und
  BF-229 liefen grün.
- **Mit den neuen Tests: 2 345 Tests, 0 Fehlschläge, 44 übersprungen** (1:15 min).
- `lint:twig` (151 Dateien) und `lint:yaml` (63) ohne Befund, `npm run typecheck` und `npm run lint` ohne Befund.
- axe (WCAG 2.0–2.2 A/AA) auf Suche, Detailseiten 12/13/18 (mit und ohne Titelfoto), „Gespeichert“, „Favoriten“, Listenseite
  bei 375 und 1280 px: bei 375 px **keine** Verstöße, bei 1280 px nur die Wortmarke im Seitenkopf (16/OF-02, bekannt;
  `qa/17/QA3-axe.txt`).
- Protokoll des Laufs: 0 × `CRITICAL`; 18 × 404 und 2 × 405 aus den eigenen Angriffs- und Grenzwertanfragen.

Berührt (Arbeitsbaum gegen den letzten Commit, alles uncommittet, daher `git diff --name-only` statt `main...HEAD`): außerhalb
der Tests `src/Saved/SavedText.php`, `templates/partials/_favorite_button.html.twig` (eingebunden in
`_restaurant_card` → Suche und `saved/_cards` → „Favoriten“/Listen, `restaurant/show` → Kopf der Detailseite,
`saved/favorite.stream`), `templates/restaurant/_saved_panel.html.twig` (Detailseite) und `src/Controller/ProfileController.php`
(Fehlerauftrag B04, BF-226). Dazu die Features aus *Abhängig von* in `features/index.md`.

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| 02 Barrierefreiheit | Fokusrahmen des Herzens | ✅ bestanden | 56 + 12 Pixelmessungen ≥ 13,05 : 1, Kontrastmodus sichtbar; axe ohne neuen Verstoß |
| B05 Restaurantsuche | Karte mit Herz | ✅ bestanden | Gast und Konto: Liste, Filter (Rollstuhl+WC+Bewertung, geöffnet, Ort, Favoriten), Seite 2 → 200, Seite 3 → 404; 6 Herzen je Seite mit neuer Klasse; `RestaurantRepositoryTest` grün |
| B06 Detailseite | Kopf mit Herz, „In Listen“ | ✅ bestanden | elf Detailseiten 200 (Gast und Konto); mit Titelfoto 200, Bild 200 `image/jpeg`, Lightbox-Verweise vorhanden |
| B04 Profil | `ProfileController` (BF-226), Verweis, Export, Löschung | ✅ bestanden | `/de/profile` 200; Name ändern → 302 und gespeichert; leerer Name → 422, Formular behält `action="/de/profile/edit"`; Export 200; Löschung 302. **Keine Abnahme von B04** — dafür `/sdd-qa B04` |
| 01 Betroffenenrechte | Export, Löschung | ✅ bestanden | AK-60, AK-61 |
| B01 Registrierung | Abhängig von | ✅ bestanden | `/de/register` 200; `RegistrationControllerTest` grün |
| B02 Anmeldung | Abhängig von | ✅ bestanden | rund 40 Anmeldungen dieses Laufs (curl, Chromium, WebKit); gelöschtes Konto → `/de/login` |
| B03 Passkey | gleiche Firewall | ✅ bestanden | `POST /passkey/login/options` → 200; `SecurityControllerTest` grün |
| B12 Startseite | gemeinsame Hülle | ✅ bestanden | `/de/` 200; `HomeControllerTest` grün |
| B20 Restaurantverwaltung | Titelfotos | ✅ bestanden | Hochladen von drei Fotos über `/de/admin/restaurants/{id}/fotos` → 302, Löschen über `…/fotos/{n}/loeschen` → 302, Zeile und Datei weg |
| B24 Mehrsprachigkeit | Beschriftungen | ✅ bestanden | Leiste in fünf Sprachen (AK-42); `CatalogueCompletenessTest`, `SavedEdgeCasesTest::testEc14Ak46…` grün |
| B25 PWA, untere Leiste | Abhängig von | ✅ bestanden | AK-42; `manifest.webmanifest`, `sw.js` 200; Service Worker aktiv (EC-11) |
| 10 Sitemap | `SeoRegistry` | ✅ bestanden | `/sitemap.xml` 200 ohne Gespeichert-Adressen, `/robots.txt` 200; `SeoRouteCoverageTest` grün |
| 11 Nutzungsmessung | Auslöser am Herz | ✅ bestanden | `Feature17UsageEventsTest` (26), `CollectControllerTest` grün |
| 12 Einführung | Weiche nach der Anmeldung | ✅ bestanden | `PendingFavoriteLoginTest::testEc04…` grün |

Außerdem 200: `/de/open`, `/open.json`, `/health`, `/de/roadmap`, `/de/roadmap/ideen`, `/api/v1/restaurants`.

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Konto b auf Liste 115 von a: 3 Lesewege + 5 Schreibwege (Umbenennen, Löschen, Entfernen mit gültigem Token von a, Entfernen ohne Token, Häkchen auf fremde Liste) → je 404; Liste „Fotos|4“ vorher = nachher, b ohne neuen Eintrag |
| Zugriffsregeln serverseitig | bestanden | Fremde und unbekannte Liste antworten gleich („Not Found“, kein Name); gelöschtes Konto kommt nicht mehr hinein |
| Rate Limit greift | bestanden | 121. Änderung am Konto abgewiesen (Stream-Meldung, Zustand unverändert), auch für das Anlegen einer Liste; anderes Konto am selben Anschluss frei |
| CSRF | bestanden | falsches Token am Herz, fremde Herkunft an „Liste anlegen“ und „Neue Liste“ → keine Wirkung, Bestand vorher = nachher |
| PII in Logs | bestanden | `dev.log` ab Prüfbeginn: Listennamen und Notiz des Laufs ausschließlich in `doctrine.DEBUG` (dev); Produktion `dbal.logging: false` (`SavedPrivacyLeakTest`) |
| PII an externe Dienste | bestanden | Dieser Fehlerauftrag berührt keinen Weg nach außen; Umami-Normalizer und Sentry-Rumpf in der Suite grün (`Feature17UsageEventsTest`, `CollectControllerTest`, `Qa17SentryAnfragerumpfTest`); einzige Mail des Laufs die Löschbestätigung an `qa17cd` (aus der Warteschlange entfernt) |
| Geheimnisse im Repository | bestanden | uncommitteter Diff (20 Dateien) nach Brevo-, Sentry-, Push-, privaten Schlüsselmustern durchsucht: 0 Treffer; keine `.env`/`.pem` im Status |
| Eingaben | bestanden | Listennamen: leer, Leerzeichen, 61 Zeichen, 60 + Ränder, Schluss-Sigma in fünf Schreibweisen, ſ, µ, İ/ı, Akzente, NFC-zerlegende Zeichen × 20/60, Dubletten gleichzeitig — kein 500, 0 × `CRITICAL` |
| Löschen | bestanden | Konto mit 25 Listen, Favorit, Eintrag und Notiz → 0 Zeilen in allen vier Tabellen, keine verwaisten Einträge |

## Fehler

Keine. Kein neuer Befund in diesem Durchlauf.

## Hinweise (keine Befunde)

- **H-1 · Das Herz im Kopf der Detailseite rutscht beim ersten Umschalten um 60 px nach unten** — nur wenn die Seite ganz oben
  steht (Chromium und WebKit, Nr. 13 bei `scrollY` 0: `top` 249 → 309 px; bei `scrollY` 100: 0 px, die Bildlaufverankerung
  gleicht aus). Grund: Die sichtbare Meldung des Streams erscheint in `#meldung-live` über dem Inhalt. Fokus und Ansage stimmen,
  kein Sprung an den Anfang (AK-51), seit dem ersten Bau so. Wer zweimal schnell tippt, trifft beim zweiten Mal nicht mehr das
  Herz — eine Frage an den Betreiber für die Abnahme (AK-51), kein Kriterium verletzt.
- **H-2 · Safari erreicht Knöpfe per Tab nur mit Wahltaste** (Vorgabe von macOS, ohne „Mit Tab jedes Objekt hervorheben“).
  Die WebKit-Messungen liefen mit ⌥-Tab; mit Tab allein sprang der Fokus vom Herz in das Feld „Neue Liste“. Für den
  VoiceOver-Durchgang zu AK-48 und AK-63 in echtem Safari zu beachten.
- **H-3 · Die Fläche des inneren Rahmens** (241–256 px² bei einem Knopf von 44 px) liegt knapp unter dem Maß, das das
  AAA-Kriterium 2.4.13 für einen 2-px-Umriss des ganzen Knopfs ansetzen würde (≈ 276 px²). AA (2.4.7) ist erfüllt, AAA ist
  nicht Ziel des Projekts.
- **`code-reviewer`:** keine Funde in den drei Änderungen. Sein Vorbehalt (bestehende Zeilen mit altem Schlüssel nach einer
  ausgelieferten Migration) trifft nicht zu: 17 ist nicht ausgeliefert, alle 19 Zeilen der Dev-Datenbank tragen bereits den
  neuen Schlüssel.
- Hinweise H-5 (422-Antworten mit `private, must-revalidate`) und H-6 (AK-43 bei 768–1023 px nur das Symbol) des zweiten
  Berichts gelten weiter. Der erste Lauf des eigenen Panel-Skripts maß AK-50 zu früh (vor dem Ende des Absendens); der
  wiederholte Lauf mit Warten auf die Antwort ist der Nachweis oben.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Unit/Saved/Qa17Nachpruefung3TextTest.php` | 11 | BF-220: was die Faltung gleich macht (ſ/s, µ/μ, ϐ/β, Schluss-Sigma, Cherokee) und was verschieden bleibt (Akzent, ı, Ligatur, ß/ss, griechischer Akzent); EC-07: Schlüssel so lang wie der normalisierte Name bei NFC-zerlegenden Zeichen |
| `tests/Functional/Saved/Qa17Nachpruefung3Test.php` | 4 | BF-220 über HTTP auf allen drei Wegen (Detailseite mit Häkchen unverändert, Umbenennen, Anlegen mit ſ); EC-07 mit U+FB2C × 21 (422) und × 20 (303, 60/60) auf beiden Anlegewegen |

Ausgeführt: 15 grün. Mit zurückgenommener BF-220-Reparatur 7 rot (alle `testBf220…` außer Cherokee, das vorher schon gleich
war), die übrigen grün — wiederhergestellt, Prüfsumme gleich.

Browser- und Messskripte (nicht im Projekt, `/tmp/qa17c/bin/`): `bf218.js` (Pixelmessung, 14 Lagen × 2 Breiten × 2 Engines),
`ak48.js` (Stream, Fokus, Rahmen), `ak49-rest.js` (Symbolkontrast, Größe, Kontrastmodus), `bf219.js`, `panel.js`/`ak50.js`,
`axe.js`, `regress.js`/`ak42b.js` (AK-42, EC-11), `sprung*.js` (H-1), `bf220-codepunkte.php`, `lib.sh`.

## Testdaten

In der Dev-Datenbank angelegt und nach der Prüfung entfernt: Konten `qa17ca`, `qa17cb`, `qa17cd` (über die Anwendung gelöscht,
AK-61), `qa17ce` … `qa17ch@example.test` (Kaskade nahm Favoriten, Listen, Einträge und Notizen mit), die drei Titelfotos (über
die Verwaltung gelöscht — Zeilen in `restaurant_image` und Dateien unter `public/uploads/restaurants/`), die Löschbestätigung an
`qa17cd` in `messenger_messages`. Die Fotos wurden vor dem zweiten Suite-Lauf entfernt (die Suite leert das Verzeichnis). Die
Daten an `user@endlech.lu` sind unverändert (Abgleich `user`, `favorite`, `saved_list_entry`, `restaurant_note`, `restaurant`
vorher/nachher identisch; Liste „Mittag in Kirchberg“ unberührt).

## Nächster Schritt

**`/sdd-deploy 17`** — mit T29 im Release-Commit (`RoadmapRegistry` ohne `favourites`, `ChangelogRegistry` + fünf
`changelog.*.yaml`; erst dann ist AK-70 prüfbar) und **Migration vor dem Code** (`Version20261113221129`, rein ergänzend; Weg
„einmaliger Pre-Deployment-Befehl“ aus `CLAUDE.md`). Vorher **`/sdd-qa B04`** für den uncommitteten Fehlerauftrag BF-226, der
auf demselben Branch mit ausgeliefert wird. Beim Betreiber: AK-51 abnehmen (dabei H-1 ansehen), AK-48 mit VoiceOver und AK-63
in echtem Safari (H-2), in Sentry nach alten Ereignissen mit Anfragerumpf sehen. Die vorbestehenden BF-221 bis BF-225 gehören
zu ihren Features und stehen in `features/befunde.md`.
