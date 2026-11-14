# 17 · Favoriten und Listen — Testbericht (zweiter Durchlauf)

Stand: 2026-11-14 · Geprüft gegen `spec.md` vom 2026-11-14 (AK-01 bis AK-70, EC-01 bis EC-16, OF-01 bis OF-05, Decision
Log bis #26) · in eigener Session, ohne Bauverlauf · Vorstufe `building` nach dem Fehlerauftrag (BF-211 bis BF-217, BF-206,
Rest von BF-203). Der *Abschlussbericht · Fehlerauftrag* am Ende von `tasks.md` und die Behebungsvermerke des ersten
Berichts dienten als Suchliste, nicht als Nachweis. Der erste Durchlauf ist archiviert unter
`qa/17/qa-report-erster-durchlauf-2026-11-14.md`.

**Prüfumgebung.** Arbeitsbaum `feature/17-favoriten-und-listen` (alles uncommittet), Entwicklungsserver
`http://127.0.0.1:8000` gegen die Dev-Datenbank (MySQL 8, Container `mikaplus-database-1`); `var/tailwind/app.built.css`
(13:16) jünger als `assets/styles/app.css` (13:02) und enthält `@layer base { :focus-visible … }`, `var/typescript/` jünger als
jede `.ts`-Quelle. Anfragen mit `curl` und Cookie-Dateien je Konto; Browser **Chromium und WebKit über Playwright** (unter
`/tmp/qa17/pw`, nicht im Projekt), axe-core 4 mit **entfernter** Debug-Toolbar (Muster BF-207). Alle Mess- und
Prüfskripte dieses Durchlaufs sind eigene (`/tmp/qa17b/bin/`); die Skripte des Baus unter `/tmp/build17/` wurden nur gelesen.
Testkonten `qa17a`…`qa17i@example.test` (`qa17c` unbestätigt, `qa17i` mit fälliger Einführung), zeitweise fünf Kopien eines
rollstuhlgerechten Lokals (`QA17 Lokal 1–5`), zwei Wegwerf-Lokale (Nr. 1000, 1001, über die Verwaltung gelöscht) und ein
**eingesetztes Titelfoto** für Nr. 22 (synthetisches helles Bild; die Dev-Daten haben keine Titelfotos). Alles nach der Prüfung
entfernt (siehe *Testdaten*). Nachweise unter `qa/17/` mit Präfix `QA2-`.

## Fazit

**Production-ready: ja — mit Vorbehalten** (kein Befund *kritisch* oder *hoch*; **AK-49 ist durchgefallen**, mittel).

**Der Fehlerauftrag hat gehalten.** Alle neun Befunde sind am laufenden System behoben: Listennamen aus „İ“ werden auf allen
drei Wegen gespeichert bzw. mit Meldung abgewiesen (BF-211), Nummern und Seitenzahlen jenseits von `PHP_INT_MAX` ergeben auf
allen geprüften Wegen von 17 und in der Suche 404 bzw. 400 statt 500 (BF-212 bis BF-214), der aktive Leistenpunkt steht in cyan-700 (BF-215), axe findet
auf den elf Detailseiten nur noch Wortmarke und Gehweite (BF-216), und **jede `focus:outline-<farbe>` wirkt** (BF-206/BF-217).
Jede der neun Reparaturen hat einen Prüflauf, der rot wird, wenn man sie zurücknimmt (zehn Mutationen, zehnmal rot).

**BF-206 wirkt projektweit — ohne Rückschritt.** Fokus-Umfrage am Pixel über 47 Seiten (26 Gast, 13 Konto, 8 Verwaltung) bei
1280 und 375 px, **4 491 Elemente**, jedes mit dem heutigen Stand und mit der alten ungeschichteten Regel (per `<style>`
nachgestellt) verglichen: **0 Rückschritte, 68 Rahmen neu sichtbar** — alle `focus:outline-white` auf Verlaufsbändern
(Startseite, `/open`, `/presse`, Vergleiche, Partner, Organisationen, App-Warteliste, Roadmap/Ideen/Changelog, `/about`, die
Kopfbänder von „Gespeichert“). Zusätzlich, weil die Dev-Daten sie nicht zeigen: Cookie-Banner, Kopf-Menü „Für Partner“,
Einführung, Sponsorenkachel (eingesetztes Markup, heller Grund 5,05 : 1, Footer 9,81 statt 3,31 : 1), Titelfoto-Verweis
(5,05 : 1), Lightbox — jeweils gleich oder besser. Alle Flächen auf dunklem Glas (die zwölf **Glas-Etiketten** aus BF-203,
Bereichskopf von 16, Kopfband von 17, Sprach-Abzeichen; de/fr/pt × 1280/375/320 px, 234 Messungen) liegen bei
**5,77–10,88 : 1** (vorher nachgestellt 2,98–6,14), die Sprach-Abzeichen über dem Titelfoto bei 9,15–11,68 : 1.

**Neu und offen.** **BF-218** (*mittel*, AK-49, beantwortet 17/OF-05): Das Herz im Kopf der Detailseite hat bei
Tastaturfokus **keinen sichtbaren Rahmen** — auf dem Verlauf 1,05–1,33 : 1, über dem Titelfoto bis 2,48 : 1, kein Pixel ändert
sich mit 3 : 1. **BF-219** (*mittel*): Ein langer Listenname ohne Leerzeichen sprengt auf der Detailseite den Bereich „In
Listen“ — schon ein 34-stelliges Kompositum erzeugt bei 320 px eine waagerechte Scrollleiste. **BF-220** (*niedrig*): Die
Reparatur von BF-211 kennt das griechische Schluss-Sigma nicht mehr — „ΟΔΟΣ“ und „οδος“ lassen sich als zwei Listen
anlegen. Dazu vorbestehend und mitgefunden: BF-221 (*niedrig*, dieselbe Seitenzahl wie BF-214 an drei weiteren Stellen,
darunter die öffentliche REST-API), BF-222 (*mittel*, B10 — Gehweite 2,48 : 1), BF-223 (*mittel*, B03 — Passkey-Knopf
3,61 : 1), BF-224 (*mittel*, B09 — Schließen-Knopf der Lightbox ohne sichtbaren Fokus), BF-225 (*niedrig*, 15 — ein
Prüflauf kippt an der Sekundengrenze).

Nicht prüfbar bleiben **AK-48** (Ansage durch VoiceOver), **AK-51** (Abnahme des Betreibers) und **AK-70** (erst mit dem
Release, T29).

**Nächster Schritt: `/sdd-deploy 17`** mit T29 im Release-Commit und **Migration vor dem Code**. Empfohlen vorher,
blockiert nicht: `/sdd-build 17` mit BF-218 und BF-219 (beide in Vorlagen dieses Features), dazu BF-220; die vorbestehenden
BF-221 bis BF-225 gehören zu ihren Features. Keiner der neuen Befunde hängt an einer Entscheidung — AK-49 sagt bereits, was
gilt.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 67 von 70 |
| davon bestanden | 66 |
| davon durchgefallen | 1 (AK-49) |
| **nicht prüfbar** | 3 (AK-48, AK-51, AK-70) |
| Edge Cases belegt | 16 von 16, alle bestanden |
| Befunde des Fehlerauftrags nachgeprüft | 9 von 9 behoben (BF-203 Rest, BF-206, BF-211 bis BF-217) |
| Tests neu geschrieben | 11 Fälle in 2 Dateien (4 laufen, 7 Reproduktionen übersprungen) |
| Tests grün | 4 von 4 ausgeführten; scharf (`QA17_REPRO=1`) alle 7 Reproduktionen rot |
| Volle Suite gelaufen | 2 299 Tests, 0 Fehlschläge, 46 übersprungen (vor den neuen Tests: 2 288, **1** Fehlschlag an der Sekundengrenze — BF-225 —, 39) |
| Regression: berührte Features | 21, davon Hauptweg bestanden 21 |

## Akzeptanzkriterien im Einzelnen

Abgehakt wird nur, was in diesem Durchlauf ausgeführt wurde. „Test“ heißt: in der vollen Suite dieses Durchlaufs grün
gelaufen. Konten: `a` … `i` = `qa17a` … `qa17i@example.test`.

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Konto d, `?sort=name&wheelchair=1`, Herz Nr. 12 mit `Accept: text/vnd.turbo-stream.html` → 200, `replace method="morph" target="favorit-12"`, Knopf „Pizzeria Bella Vista aus Favoriten entfernen“, `update target="meldung-live"` „Gemerkt: Pizzeria Bella Vista“, DB 1 Zeile; ohne JS → 303 `/de/restaurants?sort=name&wheelchair=1#favorit-14`, Meldung „Gemerkt: Burger & Co.“. Im Browser (Chromium 375 px, Tastatur) bleibt das Herz bei `top` 384 px (AK-48) |
| AK-02 | ✅ bestanden | Detailseite Nr. 18: Herz im Kopf direkt nach dem `h1`, Stream wie AK-01 („Gemerkt: Sushi Zen“); ohne JS Nr. 20 → 303 `/de/restaurants/20#favorit-20`; `DetailHeartVariantsTest` |
| AK-03 | ✅ bestanden | Nr. 12 mit Liste und Notiz: → 303 `#favorit-12`, „Aus Favoriten entfernt: Pizzeria Bella Vista“; DB vorher `fav=1 liste=1 notiz=be35…`, nachher `fav=0 liste=1 notiz=be35…`; 0 × `karte-12` auf „Favoriten“ |
| AK-04 | ✅ bestanden | Zweite Sitzung desselben Kontos: „Favoriten“ zeigt 20, 18, 14 (zuletzt gemerkt zuerst); Suche Seite 2 und Detailseite 18 mit gefülltem Herz („… aus Favoriten entfernen“) |
| AK-05 | ✅ bestanden | Veraltetes leeres Herz auf gemerktes Nr. 18 → 303, bleibt 1 Zeile, „Gemerkt: Sushi Zen“; veraltetes gefülltes Herz auf entferntes Nr. 12 → 303, 0 Zeilen, keine Fehlermeldung; **10 gleichzeitige** Tipps auf dasselbe leere Herz → `303 ×10`, genau 1 Zeile |
| AK-06 | ✅ bestanden | **Chromium ohne JavaScript**, 12 rollstuhlgerechte Lokale, `?wheelchair=1&sort=name&page=2`, Herz der vierten Karte (Nr. 1006) → `…&page=2#favorit-1006`, Herz bei 96 px, „QA17 Lokal 5 aus Favoriten entfernen“, Meldung „Gemerkt: QA17 Lokal 5“ (`qa/17/QA2-AK06-ohne-js-seite2-vierte-karte.png`); `Qa17PruefungTest::testAk06…` |
| AK-07 | ✅ bestanden | Gast, Suche: 6 Herzen, 0 gefüllt, kein `#in-listen`, kein `#notiz`, kein `<textarea>`, kein „Nur meine Favoriten“; Detailseite 12: 1 Herz, Rest ebenso 0 |
| AK-08 | ✅ bestanden | Gast `?wheelchair=1&page=2`, Herz Nr. 17 → 303 `/de/login`, dort „Melde dich an, um dir Café Nordstad zu merken.“, `favorite` gesamt 10 → 10 |
| AK-09 | ✅ bestanden | Passwort in derselben Sitzung → 302 `/de/restaurants?wheelchair=1&page=2#favorit-17`, DB Nr. 17, „Gemerkt: Café Nordstad“, Herz gefüllt, `data-scroll-to-id-value="favorit-17"`. Passkey: Zeremonie ohne virtuellen Authenticator nicht ausführbar; `PendingFavoriteLoginTest::testAk09PasskeyWegNimmtDieselbeZielseiteUndMerkt` grün |
| AK-10 | ✅ bestanden | Falsches Passwort → 302 `/de/login`, Hinweis steht weiter, 0 Zeilen; danach richtiges → nachgeholt (AK-09) |
| AK-11 | ✅ bestanden | `/de/gespeichert` Konto a: „Favoriten 4 Lokale“, „Meine Listen: Kirchberg 2 · οδος 0 · ΟΔΟΣ 0 · İstanbul 0 · aaa…İİ 0 · İ×60 0 · İ×31 0“ (zuletzt angelegt zuerst), Feld `saved_list_name[name]`, „Anlegen“ |
| AK-12 | ✅ bestanden | **Live mit 15 Favoriten** (Konto g): Seite 1 12 Karten (1005 … 15, zuletzt gemerkt zuerst), „Weiter“ → `?page=2` mit 3 (14, 13, 12), Seite 3 → 404; `SavedPagesTest::testAk12…` |
| AK-13 | ✅ bestanden | Konto g ohne Daten: beide Leertexte wörtlich, Verweis `/de/restaurants` |
| AK-14 | ✅ bestanden | Chromium, Tastatur (Enter) auf „Favoriten“ mit 4 Karten (20, 18, 16, 14): mittlere (18) → Fokus auf der nächsten (`karte-16`, `:focus-visible`); letzte (14) → vorige (16); erste (20) → nächste (16); einzige → `#gespeichert-leer`; Meldung jeweils im Ansagebereich |
| AK-15 | ✅ bestanden | Gast: `/de/gespeichert`, `/favoriten`, `/listen/53`, `/listen/53/loeschen` → 302 `/de/login`; Anmeldung mit Konto a → 302 auf die zuletzt aufgerufene (`/listen/53` bzw. `/favoriten`); mit Konto b → dieselbe Zielseite, dort 404 |
| AK-16 | ✅ bestanden | „Mittag in Kirchberg“ → 303, „Liste „Mittag in Kirchberg“ angelegt.“, zuoberst „0 Lokale“ |
| AK-17 | ✅ bestanden | Leer und fünf Leerzeichen → 422 „Bitte gib der Liste einen Namen.“, Zahl der Listen bleibt 1 |
| AK-18 | ✅ bestanden | 61 × „a“ → 422 „Der Name darf höchstens 60 Zeichen lang sein.“, Eingabe im Feld; „  “ + 60 × „b“ + „  “ → gespeichert ohne Ränder, 60 Zeichen |
| AK-19 | ✅ bestanden | „Kirchberg“, dann „ kirchberg “ → 422 „Eine Liste mit diesem Namen hast du schon.“; Konto e legt „Kirchberg“ an (je eine Zeile für Konten 13, 17, 19). ⚠ Griechisches Schluss-Sigma siehe BF-220 |
| AK-20 | ✅ bestanden | 25 Listen: 26. auf „Gespeichert“ → 422 „Du hast bereits 25 Listen. Lösche eine, um eine neue anzulegen.“, auf der Detailseite („Mit Oma“) ebenso; Zahl bleibt 25 |
| AK-21 | ✅ bestanden | Konto e, Lokal 13 in „Kirchberg“: 3 Häkchen (Dritte, Parallel, Kirchberg), genau 1 gesetzt, Feld „Neue Liste“, „Speichern“ |
| AK-22 | ✅ bestanden | Häkchen von „Kirchberg“ auf „Parallel“ umgehängt → 303 `#in-listen`, „Listen gespeichert.“, DB nur Liste 84, Herz 0 → 0; „Gespeichert“: Dritte 0, Parallel 1, Kirchberg 0 |
| AK-23 | ✅ bestanden | „Mit Oma“ auf der Detailseite → Liste mit Lokal 13; Dublette „kirchberg“ + Häkchenänderung → 422, Häkchen unverändert; 61 Zeichen + Häkchenänderung → 422, unverändert |
| AK-24 | ✅ bestanden | Konto ohne Liste: 0 Häkchen, nur `restaurant_lists[newList]` |
| AK-25 | ✅ bestanden | **Live mit 14 Lokalen** (Konto g): Listenname einzige `h1`, Seite 1 12 Karten (zuletzt hinzugefügt zuerst), Seite 2 2 (13, 12) mit „Deine Notiz“ unter Karte 12, Seite 3 → 404; `SavedPagesTest::testAk25…` |
| AK-26 | ✅ bestanden | Ohne JS → 303 `…/listen/60#gespeichert-leer`, „Sushi Zen aus „Kirchberg“ entfernt.“, Lokal bleibt in „Mit Oma“, Herz 1, Notiz unverändert; mit Turbo → 200 `text/vnd.turbo-stream.html`, `replace method="morph" target="gespeichert-karten"` + Meldung |
| AK-27 | ✅ bestanden | Leere Liste: Leertext wörtlich, Verweis `/de/restaurants` |
| AK-28 | ✅ bestanden | „KIRCHBERG“ (eigener Name) → 303, keine Dublette; „Kirchberg mittags“ → Übersicht, `h1`, Häkchen der Detailseite; leer / 61 / „parallel“ → 422 mit der jeweiligen Meldung, Name unverändert |
| AK-29 | ✅ bestanden | Rückfrage „Liste „Mit Oma“ mit 4 Lokalen löschen? Die Lokale selbst, deine Favoriten und deine Notizen bleiben.“, „Abbrechen“ (Verweis) und „Löschen“ (POST-Formular), 0 × `onsubmit`/`onclick`, Liste noch da; ohne Skript durchgespielt |
| AK-30 | ✅ bestanden | Abbrechen → Listenseite 200; Löschen → 303 `/de/gespeichert`, „Liste „Mit Oma“ gelöscht.“, Liste und Einträge 0, Favoriten und Notizen des Kontos unverändert (Vergleich vorher/nachher), alte Adresse 404 |
| AK-31 | ✅ bestanden | 8 gleichzeitige Absendungen mit gesetztem Häkchen → `303 ×8`, genau 1 Eintrag |
| AK-32 | ✅ bestanden | Lokal 16 weder gemerkt noch in Liste (Konto ohne Listen): „Deine Notiz“, Feld, „Nur für dich sichtbar.“, „Notiz speichern“ |
| AK-33 | ✅ bestanden | → 303 `#notiz`, „Notiz gespeichert.“, nach Neuladen „Seiteneingang links, Rampe steil“ im Feld |
| AK-34 | ✅ bestanden | Lokal 14 als Favorit und in zwei Listen: Notiz unter der Karte in „Favoriten“, „Dritte“ und „Parallel“ |
| AK-35 | ✅ bestanden | 501 Zeichen → 422, Meldung am Feld, gespeicherte Notiz unverändert, Eingabe im Feld; Grenze: 247 + 6 × `\r\n` + 247 (506 Byte roh) → gespeichert mit 500 Zeichen, 248 + … + 247 → 422 |
| AK-36 | ✅ bestanden | Feld geleert → „Notiz gelöscht.“, 0 Zeilen, kein Notizblock unter der Karte auf „Favoriten“ |
| AK-37 | ✅ bestanden | `<b>fett</b> <script>alert(1)</script>` + Umbruch auf Detailseite, „Favoriten“ und Listenseite: 0 × rohes `<script>alert(1)`, 0 × `<b>fett`, als `&lt;b&gt;…` mit erhaltenem Umbruch |
| AK-38 | ✅ bestanden | Konto d, 5 Favoriten (14, 16, 18, 20, 22), davon rollstuhlgerecht laut DB 14, 18, 20: `?favorites=1&wheelchair=1` → genau 14, 18, 20; Sortierung `rating` ordnet um (18, 14, 20); Seite 2 → 404 wie jeder Filter; `SearchFavoritesTest::testAk38…` |
| AK-39 | ✅ bestanden | Gast: kein Häkchen; `?favorites=1&wheelchair=1` → 200, dieselben Karten wie ohne Parameter (12 18 15 21 14 20), keine Meldung |
| AK-40 | ✅ bestanden | Konto ohne Favoriten: „Du hast noch keine Favoriten. Tippe auf das Herz eines Lokals, um es hier zu finden.“, „Filter entfernen“ → `/de/restaurants?sort=name&wheelchair=1` |
| AK-41 | ✅ bestanden | Angemeldet: `/de/` · `/de/restaurants` · `/de/gespeichert` · `/de/profile`, `aria-current="page"` auf „Gespeichert“ bei Übersicht, Favoriten, Liste, Rückfrage (jetzt `text-cyan-700`); Gast: … `/de/about` · `/de/login`; „Über uns“ in der Fußzeile |
| AK-42 | ✅ bestanden | Chromium 320 px, angemeldet, lb/de/fr/en/pt: je 4 Punkte 80 × 60 px, jede Beschriftung einzeilig, nicht abgeschnitten, kein Überlauf (`qa/17/QA2-AK42-leiste-320-*.png`) |
| AK-43 | ✅ bestanden | 768/1024/1280 px: Verweis `/de/gespeichert` im Kontobereich, Name „Gespeichert“, 28 × 44 bzw. 116 × 44 px, kein Überlauf (`QA2-AK43-kopfzeile-*.png`); Hinweis H-2 des ersten Berichts gilt weiter |
| AK-44 | ✅ bestanden | `/de/profile` führt im Inhalt auf `/de/gespeichert` |
| AK-45 | ✅ bestanden | `/sitemap.xml`: 160 `<loc>`, 0 × `gespeichert` |
| AK-46 | ✅ bestanden | `debug:translation lb/fr/en/pt --only-missing` → 0; `CatalogueCompletenessTest` grün; Übersicht in lb/fr/en/pt ohne deutschen Rest, Listenname unverändert; Meldung fr „Liste « Liste FR » créée.“, Fehler pt „Dê um nome à lista.“, Rückfrage lb „Lëscht „Liste FR“ läschen?“ |
| AK-47 | ✅ bestanden | Zugängliche Namen der Suche (`sr-only`): „Brasserie du Grund aus Favoriten entfernen“, „Burger & Co. aus Favoriten entfernen“, „Café Nordstad merken“, „Green Bowl merken“ … — sechs verschiedene; VoiceOver selbst nicht gelaufen |
| AK-48 | ⚠️ nicht prüfbar | Ansage durch VoiceOver: kein Screenreader in der Prüfumgebung. Gemessen (Chromium, Enter auf dem Herz): Fokus bleibt auf `favorit-knopf-22` (`:focus-visible`), Name wechselt auf „Brasserie du Grund aus Favoriten entfernen“, `#meldung-live` (`role="status"`) „Gemerkt: Brasserie du Grund“, Herz bleibt bei `top` 384 px; zurück ebenso |
| AK-49 | ❌ durchgefallen | Formen (umrandet/gefüllt), Kontrast des Symbols (gray-700 bzw. red-600 auf Weiß) und 44 × 44 px bestanden; Fokusrahmen **in der Karte** sichtbar (`QA2-AK49-karte-herz-fokus-*.png`). **Im Kopf der Detailseite nicht:** siehe BUG-01 / BF-218 |
| AK-50 | ✅ bestanden | Chromium mit JS: leerer Listenname → Fokus `#saved_list_name_name`; 501-Zeichen-Notiz → Fokus `#restaurant_note_text` (Eingabe 501 erhalten); Dublette unter „Neue Liste“ der Detailseite → Fokus `#restaurant_lists_newList`; Meldung im `…_error`, `aria-describedby="…_help …_error"` |
| AK-51 | ⚠️ nicht prüfbar | Abnahme des Betreibers (Decision Log 20). Teile vom Prüfer gemessen: keine waagerechte Scrollleiste auf 21 Seiten bei 375 und 320 px — **außer** der Detailseite mit einem langen Listennamen ohne Leerzeichen (BF-219) |
| AK-52 | ✅ bestanden | Konto b und Gast auf Suche, Detailseiten 14/18, `?favorites=1`: 0 gefüllte Herzen von e, 0 × Listennamen, 0 × Notiztext (Quelltext) |
| AK-53 | ✅ bestanden | Konto b auf Liste 60 (Konto e) und Nr. 99999: Liste, `?page=2`, Rückfrage → je 404, gleiche Fehlerseite, 0 × Name; `SavedPagesTest::testAk53…` |
| AK-54 | ✅ bestanden | Konto b mit eigenem gültigem Token auf Liste 60: Umbenennen, Löschen (mit/ohne Token), Entfernen, Häkchen nur fremd, eigene + fremd, 99999 → je 404; Liste 60 vorher = nachher (`Kirchberg mittags|15`), b ohne neue Einträge |
| AK-55 | ✅ bestanden | Lokal 999999 an merken, nicht-mehr-merken, listen, notiz, entfernen, Gast-merken → je 404; Zeilen gesamt 74 → 74, keine Liste „NeuX“ |
| AK-56 | ✅ bestanden | Falsches Token (Herz, Löschen) und fremde Herkunft (Liste, Notiz, Umbenennen, Häkchen) → 303 mit „Ungültiges CSRF-Token. Bitte versuche es erneut.“; Prüfsumme über alle Daten von b vorher = nachher (`e5ffeb74…`), 0 × „Evil“ |
| AK-57 | ✅ bestanden | Frisches Konto h: die **121.** Änderung → „Zu viele Änderungen. Bitte versuche es in 60 Minuten erneut.“ (Stream), Herz-Zustand unverändert; Liste anlegen danach → Meldung, nicht gespeichert; Konto b am selben Anschluss → 303, gespeichert |
| AK-58 | ✅ bestanden | Im selben Lauf vorher 20 × veraltetes Herz, 10 × leerer Name, 5 × leere Notiz ohne Notiz, 5 × unveränderte Häkchen — und ein Absenden „Häkchen + neue Liste“: Der Deckel griff genau bei der 121. gezählten Änderung, also zählten die 40 nicht und das kombinierte einmal; `Qa17PruefungTest::testAk58…` |
| AK-59 | ✅ bestanden | 22 Verwaltungsseiten (Dashboard, Restaurants inkl. 11 × Bearbeiten und Neu, Vorschläge, Warteliste, Finanzen inkl. 3 × Bearbeiten, Ideen): kein Listenname, keine Notiz, keine Zahl — Treffer nur der eigene Kopfverweis „Gespeichert“, der Importmap-Eintrag und das Finanzfeld „Notiz“; `SavedPrivacyLeakTest::testAk59…` |
| AK-60 | ✅ bestanden | Export `/de/profile/daten` (`endlech.lu account export v1`): `favorites` 2 × (Nr., Name, `savedAt`), `savedLists` 1 × („XSS-Liste“, `createdAt`, 3 Lokale je `addedAt`), `notes` 1 × (Lokal, Text mit Umbruch, `updatedAt`) |
| AK-61 | ✅ bestanden | Konto f (fav 2, Liste 1, Einträge 3, Notiz 1) über das Profil gelöscht → `user` 0, alle vier Tabellen 0, keine verwaisten Einträge, Anmeldung scheitert, zweite Sitzung → Login |
| AK-62 | ✅ bestanden | Verwaltung löscht Nr. 1000 (gemerkt, in zwei Listen, Notiz) → 302; 0 Zeilen in `favorite`/`saved_list_entry`/`restaurant_note`; „Gespeichert“ 200, Favoriten 5 → 4, Dritte 2 → 1, Parallel 3 → 2, keine Meldung; `messenger_messages` unverändert |
| AK-63 | ✅ bestanden | Liste mit Notiz → Abmelden (Formular) → zweimal „Zurück“: **Chromium und WebKit**, mit JS und ohne → jeweils `/de/login`, 0 × Listenname, 0 × Notiz. Echtes Safari nicht geprüft |
| AK-64 | ✅ bestanden | Auslöser im HTML: „liste_angelegt“ bzw. „herz_gesetzt“ ohne Daten-Werte; Listenpfad in `before_send.ts` auf `/{xx}/gespeichert/listen` gekürzt; Protokoll: Listennamen/Notizen ab Prüfbeginn nur in `doctrine.DEBUG` (dev), Produktion `dbal.logging: false` (`SavedPrivacyLeakTest`); Mail: einzige Nachricht des Laufs die Löschbestätigung an f, ohne Listen-/Notizinhalt; Brevo: kein Weg. Normalizer-Ausgang: `Feature17UsageEventsTest` (26 Fälle), `CollectControllerTest` grün |
| AK-65 | ✅ bestanden | `Qa17SentryAnfragerumpfTest` (echte `RequestIntegration`, Produktionswert, Gegenprobe) grün |
| AK-66 | ✅ bestanden | Liste anlegen → genau ein Auslöser `liste_angelegt`; Herz → `herz_gesetzt`, nach Neuladen keiner; Umbenennen, Notiz → keiner; Filter → `filter_angewandt`; `Feature17UsageEventsTest` |
| AK-67 | ✅ bestanden | `before_send.ts` Zeile 27/48 und `Feature17UsageEventsTest`; Pfade ohne Nummer und Titel |
| AK-68 | ✅ bestanden | `/xx/legal#gespeichert` in lb/de/fr/en/pt vorhanden; de gelesen: was gespeichert wird, nur der Kontoinhaber, kein weiterer Dienstleister (Fehleranalyse, Mail, Messung), im Export, mit dem Konto gelöscht |
| AK-69 | ✅ bestanden | Unbestätigtes Konto c: 3 Herzen (303 ×3, DB 12, 13, 16), Liste, Notiz gespeichert; „Gespeichert“ 200 |
| AK-70 | ⚠️ nicht prüfbar | Gilt nach der Auslieferung (T29 im Release-Commit) |

## Befunde des Fehlerauftrags — Nachprüfung

| Befund | Behoben? | Nachweis am laufenden System | Prüflauf rot ohne Reparatur? |
|---|---|---|---|
| BF-211 · Listenname aus „İ“ → 500 | **ja** | Übersicht: 31 × İ, 60 × İ, 57 × a + İİ → 303; „Istanbul“ + 53 × İ (61) → 422 mit Meldung; Detailseite 60 × İ und 31 × İ → 303, 61 → 422; Umbenennen auf Ä × 60, İ × 60, 日 × 60, 😀 × 60 → 303, 👨‍👩‍👧 × 20 → 422; DB: `char_length(name_key) = char_length(name)` | ja — `mb_strtolower` zurück: `SavedTextTest` 2 rot, `Qa17PruefungTest::testBf211…` rot. ⚠ Folge: BF-220 |
| BF-212 · Listennummer > `PHP_INT_MAX` → 500 | **ja** | GET Liste/Rückfrage, POST Umbenennen/Löschen/Entfernen mit 20 Ziffern, 10 Ziffern, 11 Ziffern, `00000000047`, `2147483648` → je 404; Herz-, Listen-, Notizwege der Detailseite mit 20 Ziffern → 404 | ja — `\d+` zurück: `SavedEdgeCasesTest::testBf212…` rot |
| BF-213 · riesiges `page` beim Entfernen → 500 | **ja** | `page=9223372036854775807` → 303 `#gespeichert-leer`, Eintrag entfernt; als Stream 200; `page=99999999999999999999` → 400; `page=-5` → 303; `page[]` → 400 | ja — Klemmen entfernt: `testBf213…` rot |
| BF-214 · Suche `?page=PHP_INT_MAX` → 500 | **ja** | Gast und Konto: `9223372036854775807`, `…129302`, `…129301`, `…129300` → 404, `99999999999999999999` → 400, `abc` 400, `-1`/`0` 200 | ja — Grenze entfernt: `SavedPagesTest::testEc16…` (größte ganze Zahl) rot. ⚠ Dasselbe Muster an drei weiteren Stellen: BF-221 |
| BF-215 · aktiver Leistenpunkt 3,61 : 1 | **ja** | Aktiv `oklch(0.52 0.105 223.128)` (cyan-700) in fünf Sprachen; axe `color-contrast` auf 46 Gast- und 16 Kontoseiten (375/1280 px): 0 Treffer an der Leiste | ja — `text-cyan-600` zurück: `PwaTest::testAk11Ak12…` rot |
| BF-216 · kleine Schrift Detailseite | **ja** | axe auf allen elf Detailseiten × 375/1280 px: nur noch Wortmarke (16/OF-02) und Gehweite der Haltestellen (BF-222, andere Datei) | ja — eine `text-green-600` bzw. `text-purple-500` zurück: `KontrastKlassenTest::testBf216…` rot |
| BF-206 · Fokus-Fallback ungeschichtet | **ja** | Fokus-Umfrage 4 491 Elemente, 0 Rückschritte, 68 neu sichtbar (`qa/17/QA2-fokus-umfrage.txt`); Kaskade im gebauten CSS geprüft | ja — Regel ungeschichtet: `Qa16NachpruefungTest::testBf206…` rot (prüft den Text von `app.css`, nicht die Kaskade) |
| BF-217 · Rückverweis im Kopfband | **ja** | „Favoriten“ und Listen 51/53 bei 1280 und 375 px: vorher (nachgestellt) 0 Pixel mit 3 : 1, jetzt sichtbar (Teil der 68) | mit BF-206 |
| BF-203 (Rest) · Glas-Etikett | **ja** | Die zwölf Etiketten der neun Vorlagen (Startseite, `/open` ×2, `/presse`, `/vergleich`, `/partner` ×2, `/organisationen` samt Integritätsblock auf drei Seiten, `/sponsoren`, `/app`), dazu Bereichskopf 16, Kopfband 17 und Sprach-Abzeichen — de/fr/pt × 1280/375/320 px, 234 Messungen: **5,77–10,88 : 1**, vorher nachgestellt 2,98–6,14; Sprach-Abzeichen ohne Titelfoto 6,52–8,92, über dem eingesetzten Titelfoto 9,15–11,68 (`qa/17/QA2-glas-etiketten.txt`) | ja — ein Etikett auf `bg-white/20`: `KontrastKlassenTest::testBf203…` rot |

Mitgeprüft, weil der Fehlerauftrag sie angepasst hat: **Titelfoto-Verweis** (`focus:outline-cyan-700`) über dem eingesetzten
Foto 4,54–5,05 : 1 (`QA2-titelfoto-fokus-22-*.png`; Gegenprobe Weiß: Prüflauf rot); **Sponsorenkachel** (eingesetztes Markup
der Vorlage, die Dev-Daten haben keinen Sponsor): Unternehmensseite mit `hellerGrund` 5,05 : 1, ohne den Parameter wäre sie
1,73 : 1 (Gegenprobe; `CompanyPageTest` wird ohne ihn rot), Footer 9,81 statt vorher 3,31 : 1.

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Gast merkt Nr. 22, Anmeldung desselben Kontos in **anderer** Sitzung → 302 `/de/`, 0 Zeilen; `Qa17PruefungTest::testEc01…` |
| EC-02 | ✅ bestanden | Danach Anmeldung in der Sitzung des Gastes → 302 `/de/restaurants/22#favorit-22`, 1 Zeile |
| EC-03 | ✅ bestanden | Gast merkt Wegwerf-Lokal Nr. 1001, Verwaltung löscht es, Anmeldung → 302 auf `…/1001#favorit-1001`, dort 404, 0 Favoriten, keine Meldung |
| EC-04 | ✅ bestanden | Konto i (`onboarding_due=1`) über das Herz angemeldet → 302 `/de/restaurants/21#favorit-21`, Einführung bleibt fällig; nächste Anmeldung ohne Merkwunsch → 302 `/de/einfuehrung` |
| EC-05 | ✅ bestanden | 8 gleichzeitige „Parallel“ → `303 ×1, 422 ×7`, 1 Liste; 24 Listen + 6 gleichzeitige Anlagen → `303 ×1, 422 ×5`, 25 |
| EC-06 | ✅ bestanden | Zwei Sitzungen, „Gerät 1“ dann „Gerät 2“ → DB und Feld „Gerät 2“, keine Warnung |
| EC-07 | ✅ bestanden | 60 × Ä, 60 × 日, 60 × 😀, 60 × İ → gespeichert; 20 × 👨‍👩‍👧 und 61 Zeichen → 422 — kein Serverfehler auf drei Wegen (vorher 500, BF-211). ⚠ Darstellung langer Namen: BF-219 |
| EC-08 | ✅ bestanden | `"   \r\n\r\n  \n "` → „Notiz gelöscht.“, 0 Zeilen |
| EC-09 | ✅ bestanden | Liste auf Gerät 1 gelöscht, Gerät 2 entfernt dort ein Lokal → 404, Zahl der Listen 4 → 4, keine Einträge |
| EC-10 | ✅ bestanden | Filter aktiv, Herz entfernt → Stream ersetzt nur das Herz („… merken“), Karte bleibt; neu geladen → Karte fehlt; `SavedEdgeCasesTest::testEc10…` |
| EC-11 | ✅ bestanden | Chromium mit Service Worker: Cache `endlech-v5` enthält nach Besuch der Liste nur Offline-Seite, Logo, Icons, Manifest und `/assets/…`; offline `/de/gespeichert/listen/56` → „Offline – Endlech.lu“, kein Listenname, keine Notiz (`QA2-EC11-offline-liste.png`) |
| EC-12 | ✅ bestanden | Nr. 18 (gemerkt) Verifizierung entzogen → bleibt auf „Favoriten“, Abzeichen „persönlich vor Ort geprüft“ fehlt; zurückgesetzt → wieder da |
| EC-13 | ✅ bestanden | Unbestätigtes Konto c sieht seine Favoriten (16, 13, 12); `SavedEdgeCasesTest::testEc13…` |
| EC-14 | ✅ bestanden | Sprachumschalter auf `/fr/gespeichert/listen/60` führt auf de/en/lb/pt derselben Nummer, `h1` „Kirchberg mittags“ unverändert |
| EC-15 | ✅ bestanden | Herz auf der Listenseite (Nr. 14) → 303 `…/listen/85#favorit-14`, Favorit 1 → 0, in der Liste 1 → 1 |
| EC-16 | ✅ bestanden | „Favoriten“/Liste wie die Suche: `abc` 400, `page[]` 400, `0`/`-1` 200, `2` hinter dem Ende 404, `PHP_INT_MAX` 404 (die Suche jetzt ebenfalls 404) |

## Regression

Volle Testsuite je Verzeichnis (`php -d memory_limit=2G bin/phpunit --testsuite Unit|Integration|Functional`):

- **Vor den neuen Tests: 2 288 Tests, 1 Fehlschlag, 39 übersprungen** — wie im Abschlussbericht des Baus (2 288, 39). Der
  Fehlschlag: `SponsorIntegrityTest::testAk08…@Datensatz` — die beiden verglichenen `/open/dataset.json`-Antworten
  unterscheiden sich nur in `generatedAt` (`12:27:29` gegen `12:27:30`). Fünf Wiederholungen grün → BF-225.
- **Mit den neuen Tests: 2 299 Tests, 0 Fehlschläge, 46 übersprungen** (39 wie vorher + 7 neue Reproduktionen).
- `lint:twig` (151 Dateien) und `lint:yaml` (63) ohne Befund, `npm run typecheck` und `npm run lint` ohne Befund;
  `doctrine:schema:validate` rot wie vor dem Fehlerauftrag, der Unterschied betrifft keine der vier Tabellen von 17.

Berührt (Arbeitsbaum gegen `main`, alles uncommittet, daher `git status` statt `git diff main...HEAD`): Der Fehlerauftrag
änderte außerhalb des Feature-Codes `assets/styles/app.css` (Kaskade, projektweit), zwölf Vorlagen fremder Seiten (Startseite,
`/open`, `/presse`, Vergleiche, Partner, Organisationen ×3, Sponsoren ×3, App-Warteliste), `partials/_bottom_nav`,
`partials/_hero_badges`, `partials/_opening_hours`, `restaurant/show`, `RestaurantController` und Prüfläufe von 15, 16 und B25.

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| 02 Barrierefreiheit | Fokus-Kaskade projektweit, Kontraste | ✅ bestanden | Fokus-Umfrage 0 Rückschritte; axe 62 Seitenläufe: nur Wortmarke (16/OF-02) und die vorbestehenden BF-222/BF-223; `HeadingOrderTest` grün |
| 03 Vergleichsseiten | Glas-Etikett, Fokusfarben | ✅ bestanden | `/de/vergleich`, `/de/vergleich/wheelmap` 200; `ComparisonControllerTest` grün |
| 05 Presse-Kit | Glas-Etikett, Fokusfarben | ✅ bestanden | `/de/presse` 200, Paket `/presse-kit/presse-kit-endlech-lu.zip` 200 `application/zip`; `PressControllerTest` grün |
| 08 App-Warteliste | Glas-Etikett | ✅ bestanden | `/de/app` 200; `AppWaitlistControllerTest` (24) grün |
| 10 Sitemap | `SeoRegistry` | ✅ bestanden | `/sitemap.xml` 200 ohne Gespeichert-Adressen, `/robots.txt` 200; `SeoRouteCoverageTest` grün |
| 11 Nutzungsmessung | Normalizer, `before_send.ts` | ✅ bestanden | Auslöser im HTML (AK-66); `CollectControllerTest`, `UsageEventCatalogueTest` grün |
| 12 Einführung | Weiche nach der Anmeldung | ✅ bestanden | EC-04: `/de/einfuehrung` bei fälliger Einführung |
| 15 Sponsoren | Fokusfarbe der Kachel, Glas-Etikett | ✅ bestanden | `/de/sponsoren` 200; Kachel am Pixel (oben); `CompanyPageTest` (15) grün |
| 16 Roadmap und Ideen | Kopfband, Fokus | ✅ bestanden | `/de/roadmap`, `/de/roadmap/ideen`, `/de/changelog` 200; `RoadmapAreaTest` (30) grün |
| 01 Betroffenenrechte | Export, Löschung | ✅ bestanden | AK-60, AK-61 |
| B01 Registrierung | Abhängig von | ✅ bestanden | `/de/register` 200; `RegistrationControllerTest` grün |
| B02 Anmeldung | Weiche, Login-Vorlage | ✅ bestanden | rund 40 Anmeldungen dieses Laufs (curl, Chromium, WebKit), Fehlversuch → `/de/login` |
| B03 Passkey | gleiche Firewall | ✅ bestanden | `POST /passkey/login/options` → 200; `SecurityControllerTest` grün |
| B04 Profil | Verweis, Export, Löschung | ✅ bestanden | `/de/profile` 200, Export 200, Löschung 302 |
| B05 Restaurantsuche | Seitenzahl (BF-214), Karte, Filter | ✅ bestanden | Filter (wheelchair+toilet+rating, open, city, cuisine) 200, Seite 2 200, hinter dem Ende 404; `RestaurantRepositoryTest` grün |
| B06 Detailseite | Kontrastklassen, Titelfoto, Herz | ✅ bestanden | elf Detailseiten 200 (Gast und Konto), Öffnungszeiten gerendert, mit Titelfoto 200 und Lightbox öffnet per Tastatur |
| B12 Startseite | Glas-Etikett, Fokus | ✅ bestanden | `/de/` 200, sechs Lokale verlinkt; `HomeControllerTest` grün |
| B14 Partner-Warteliste | Glas-Etikett ×2 | ✅ bestanden | `/de/partner` 200; `PartnerControllerTest` (14) grün |
| B15 Organisationen | Glas-Etikett, Integritätsblock, Sponsorenreihe | ✅ bestanden | Übersicht und beide Zielgruppenseiten 200, `/vereine` 301; `OrganisationControllerTest` (31), `Qa11ZielgruppenFormularTest` (Absenden von jeder Seite) grün |
| B16 Transparenzseite | Glas-Etikett ×2, Fokus | ✅ bestanden | `/de/open` 200 („11 Restaurants auf der Plattform“), `/open.json`, `/open/dataset.csv` 200; `OpenControllerTest` grün |
| B25 PWA, untere Leiste | Farbe des aktiven Punkts | ✅ bestanden | AK-41/42; `manifest.webmanifest`, `sw.js` 200, SW aktiv (EC-11) |

Außerdem 200: `/de/{about,criteria,legal,community/suggest}`, `/health`, `/api/v1/restaurants`, API-Anmeldung, Verwaltung
(22 Seiten). Keine `request.CRITICAL` aus dem Code von 17 im Protokoll des Laufs; die zwei vorhandenen gehören zu BF-221.

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Konto b auf Liste 60 (Konto e) und 99999: 3 Lesewege + 7 Schreibwege → je 404, gleiche Fehlerseite, Liste unverändert (AK-53, AK-54); Nummern bis 20 Ziffern → 404 (BF-212 behoben) |
| Zugriffsregeln serverseitig | bestanden | `^/[a-z]{2}/gespeichert` → Gast 302 Login (4 Seiten); Verwaltung sieht nichts (22 Seiten, AK-59); `?favorites=1` als Gast wirkungslos (AK-39) |
| Rate Limit greift | bestanden | 121. Änderung abgewiesen, am Konto, anderes Konto am selben Anschluss frei (AK-57); 40 Fehlversuche zählen nicht (AK-58) |
| CSRF | bestanden | 6 Varianten (falsches Token, fremde Herkunft), Prüfsumme vorher = nachher (AK-56) |
| PII in Logs | bestanden | `var/log/dev.log` ab Prüfbeginn (27 700 Zeilen): neun Listennamen/Notiztexte des Laufs ausschließlich in `doctrine.DEBUG` (dev); keine Zeile außerhalb; Produktion `dbal.logging: false` |
| PII an externe Dienste | bestanden | Umami: Auslöser ohne Daten, Listenpfad gekürzt (AK-64, AK-66, AK-67, Prüfläufe am Normalizer); Sentry: `Qa17SentryAnfragerumpfTest`; Brevo: kein Weg; Mail: nur die Löschbestätigung |
| Geheimnisse im Repository | bestanden | 150 geänderte/neue Dateien nach Schlüsselmustern (Brevo, Sentry-DSN, Push-Pfad, Umami, private Schlüssel) durchsucht: 0 Treffer; keine neue `.env`/`.pem` im Status |
| Eingaben | bestanden, **BF-220** | Name: leer, Leerzeichen, 61, 60 + Ränder, Ä/日/😀/İ × 60, Familien-Emoji, Dublette über Groß-/Kleinschreibung — kein 500; Notiz: 501, 500 mit `\r\n`, `<script>`, nur Leerraum; Seitenzahlen und Nummern bis 20 Ziffern, Arrays → 400/404, nie 500. Griechisches Schluss-Sigma umgeht die Dublettenprüfung (BF-220) |
| Löschen | bestanden | Konto → 0 Zeilen in vier Tabellen (AK-61); Lokal → 0 Zeilen (AK-62); Liste → Einträge 0, Herz/Notiz bleiben (AK-30) |
| Nebenbefund außerhalb von 17 | **BF-221** | `GET /api/v1/restaurants?page=9223372036854775807` (öffentlich, ohne Anmeldung) → 500 `TypeError`; ebenso `/de/roadmap/ideen` und die Verwaltungsliste — in Produktion je Aufruf ein Sentry-Ereignis |

## Fehler

### BUG-01 · Herz im Kopf der Detailseite ohne sichtbaren Fokusrahmen — mittel (BF-218)

**Betrifft:** AK-49 („… bei Tastaturbedienung einen sichtbaren Fokusrahmen“), AK-02; beantwortet 17/OF-05.
**Reproduktion:**
1. Chromium, 1280 oder 375 px, `/de/restaurants/12` (ohne Titelfoto) bzw. ein Lokal mit Titelfoto.
2. Per Tastatur auf das Herz neben dem Namen, 700 ms warten.
3. Bildausschnitt mit und ohne Fokus vergleichen.
**Erwartet:** ein Rahmen, der sich vom Grund abhebt (WCAG 2.4.7; 1.4.11 verlangt 3 : 1).
**Tatsächlich:** `solid 2px` cyan-700 (`focus:outline-cyan-700`) mit 2 px Abstand — der Rahmen liegt **auf dem Verlauf**
`from-cyan-600 to-purple-700` bzw. über dem Titelfoto unter `from-black/70`. Größte Änderung eines Pixels: **1,05 : 1**
(1280 px) und **1,27 : 1** (375 px) ohne Titelfoto, **1,56 : 1** und **2,48 : 1** mit eingesetztem Titelfoto; kein einziges
Pixel ändert sich mit 3 : 1 (`qa/17/QA2-OF05-detail-*-herz-fokus-*.png`, Messwerte `QA2-herz-fokus.json`). Die Fokus-Umfrage
findet dieselbe Stelle auf `/de/restaurants/12`, `13`, `22` für Gast und Konto (`QA2-fokus-umfrage.txt`, „schwach“). Das
Herz in den Karten der Suche ist nicht betroffen (weißer Grund, sichtbar).
**Ort:** `templates/partials/_favorite_button.html.twig:35` (Klassen des Knopfs, für Karte und Kopf gleich), eingebunden im
Kopf von `templates/restaurant/show.html.twig:25` (mit Titelfoto) und `:57` (Verlaufsband).
**Vorschlag:** Fokusfarbe je Lage — im Kopf der Detailseite Weiß (auf cyan-600 ≥ 3,6 : 1, auf purple-700 und auf dem
abgedunkelten Foto deutlich mehr), in der Karte cyan-700; danach am Pixel messen. Reproduktion:
`Qa17NachpruefungTest::testBf218…` (prüft nur, dass die gemessene Klasse dort nicht zurückkommt).
**Warum mittel, nicht hoch:** Das Herz bleibt per Tastatur bedienbar, Fokus und Ansage stimmen (AK-48); es fehlt die
sichtbare Anzeige an einer von zwei Stellen — dieselbe Klasse wie BF-206/BF-217 (beide *mittel*). Kein Rückschritt des
Fehlerauftrags: vorher und nachher gleich (beide Zustände 0 Pixel mit 3 : 1). Die erste QA hatte AK-49 nur in der Karte gemessen.

> **Behoben 2026-11-14** (`sdd-build`, zweiter Fehlerauftrag, nicht committet). **Abweichend vom Vorschlag** keine Fokusfarbe
> je Lage, sondern der Rahmen **innen auf der weißen Fläche des Knopfs**: `focus:outline-2 focus:-outline-offset-4
> focus:outline-gray-900` in `partials/_favorite_button.html.twig`, für Karte und Kopf gleich. Grund, gemessen (Kandidaten per
> eingesetztem Stil, Chromium): Weiß außen hält auf dem Verlauf (4,6–5,1 : 1), über einem weißen Titelfoto aber nur 2,16 bzw.
> 1,59 : 1; und **die Karte hat denselben Befund**, sobald ein Lokal ein Titelfoto trägt — cyan-700 außen über einem Foto
> mittlerer Helligkeit (#808080) **1,3 : 1**, 0 Pixel mit 3 : 1 (die Dev-Daten haben keine Fotos, daher bisher unsichtbar).
> Innen ändert sich Weiß zu gray-900 unabhängig vom Grund. Zudem rendert der Turbo-Stream den Knopf neu, ohne die Lage zu
> kennen — eine Farbe je Lage hätte nach dem ersten Klick wieder die Kartenfarbe gezeigt.
> **Messung nachher** (echte Klassen, Tastaturfokus, 700 ms; Schwächster von 360 Strahlen aus der Knopfmitte = größte
> Pixeländerung entlang des Strahls): Kopf ohne Titelfoto (Lokal 12) **13,43 / 13,62 : 1** (1280 / 375 px, vorher 1,01 /
> 1,10); Kopf und Karte mit eingesetztem Titelfoto (Lokal 22, je weiß, #c8c8c8, #808080, schwarz) **13,43–13,79 : 1**
> (vorher Kopf weiß 1,65 / 2,11, Karte #808080 1,30); alle 360 Winkel ≥ 3 : 1 in jedem Fall; WebKit **13,05 : 1** überall.
> Nach dem Herz-Stream (angemeldet, Enter) bleibt der Fokus auf dem Knopf, Rahmen innen (AK-48). Tests:
> `DetailHeartVariantsTest::testBf218FokusrahmenLiegtInJederLageInnenAufDerWeissenFlaeche` (Kopf mit/ohne Foto, Karte; rot:
> fehlende Klasse), `Qa17NachpruefungTest::testBf218…` scharf (rot vorher, jetzt immer grün). Ein Prüflauf misst keine
> Farbe — der Nachweis ist die Messung (`/tmp/build17b/out/bf218-nachher.txt`, außerhalb des Projekts).

### BUG-02 · Langer Listenname sprengt „In Listen“ auf der Detailseite — mittel (BF-219)

**Betrifft:** WCAG 1.4.10 (Umbruch bei 320 CSS-px, entspricht 400 % Zoom bei 1280 px); berührt AK-21, AK-51, EC-07.
**Reproduktion:**
1. Angemeldet eine Liste „Wochenendausflugsrestaurantauswahl“ (34 Zeichen) anlegen.
2. Eine beliebige Detailseite bei 320 px öffnen.
**Erwartet:** Der Name bricht um, keine waagerechte Scrollleiste (auf „Gespeichert“, der Listenseite und der Rückfrage tut er
das — dort 320 px Breite bei 60 × „a“).
**Tatsächlich:** `scrollWidth` **367 px bei 320 px**; mit einem 59-stelligen Namen 593 px bei 375 px. Das `<label>` steht in
einem Flex-Element (`li.flex`), `overflow-wrap: break-word` verkleinert dort die Mindestbreite nicht
(`qa/17/QA2-in-listen-langer-name-320.png`, `-375.png`).
**Ort:** `templates/restaurant/_saved_panel.html.twig:20-22` (`li.flex items-center gap-3` mit `form_label(… 'text-base text-gray-900')`).
**Vorschlag:** Label mit `min-w-0` und `[overflow-wrap:anywhere]` (oder `break-words` + `min-w-0`), dann bei 320 px messen.

> **Behoben 2026-11-14** (`sdd-build`, zweiter Fehlerauftrag, nicht committet). Beschriftung `min-w-0 wrap-anywhere`, Kästchen
> `shrink-0` (dieselbe Kombination wie der Listenname auf „Gespeichert“). Gemessen am laufenden Server mit den Listen
> „Wochenendausflugsrestaurantauswahl“ (34) und einem 60-stelligen Namen ohne Leerzeichen: Detailseiten 12 und 22
> `scrollWidth` **583 → 320 px** bei 320 px und **583 → 375 px** bei 375 px. **Alle Stellen mit Listennamen durchgesehen:**
> „Gespeichert“, Listenseite, Rückfrage (je 320 / 375 px, vorher und nachher ohne Überlauf), Titel (kein Layout), Meldungen
> „Liste … angelegt / umbenannt in … / gelöscht“ und „… aus … entfernt“ (Blockelement, `break-word` am `body` greift —
> nachgemessen: Umbenennen auf denselben 60-stelligen Namen bei 320 px, `scrollWidth` 320). Nur „In Listen“ war betroffen.
> Test: `RestaurantListsAndNoteTest::testBf219LangerListennameBrichtInDerBeschriftungUm` (rot: `min-w-0` fehlte) — hält die
> Klassen fest; der Nachweis ist die Messung.

### BUG-03 · Griechisches Schluss-Sigma umgeht die Dublettenprüfung — niedrig (BF-220)

**Betrifft:** AK-19 sinngemäß, Decision Log 11 („ohne Unterschied von Groß- und Kleinschreibung“); Folge der Reparatur von BF-211.
**Reproduktion:** Liste „ΟΔΟΣ“ anlegen (303), dann „οδος“ (303) — zwei Listen, die sich nur in der Schreibweise unterscheiden.
**Erwartet:** zweite → 422 „Eine Liste mit diesem Namen hast du schon.“ — so vor dem Fehlerauftrag (`mb_strtolower` bildet für
beide `οδος`).
**Tatsächlich:** `MB_CASE_LOWER_SIMPLE` kennt den Kontext nicht: `ΟΔΟΣ` → `οδοσ`, `οδος` → `οδος`; DB: zwei Zeilen mit
verschiedenem `name_key`. Die Prüfläufe des Baus prüfen nur die Länge.
**Ort:** `src/Saved/SavedText.php:36` (`nameKey()`).
**Vorschlag:** `MB_CASE_FOLD_SIMPLE` (bildet ς und σ gleich ab, bleibt 1 : 1 lang) oder Schluss-Sigma nach dem Kleinschreiben
auf σ setzen; Reproduktion `Qa17NachpruefungTextTest::testBf220…`. Gefunden zugleich vom `code-reviewer` und live bestätigt.

> **Behoben 2026-11-14** (`sdd-build`, zweiter Fehlerauftrag, nicht committet). `SavedText::nameKey()` = einfache
> Kleinschreibung, **danach** einfache Faltung (`MB_CASE_FOLD_SIMPLE`). Die Faltung allein reicht nicht: „İ“ hat keine einfache
> Faltung und bliebe „İ“ — „İstanbul“ und „Istanbul“ wären wieder verschieden (Entscheidung aus BF-211). Beide Abbildungen sind
> 1 : 1 je Codepunkt; `SavedTextTest::testBf211SchluesselHatFuerJedenCodepunktDieLaengeDesNamens` (alle 1 112 064 Codepunkte)
> bleibt grün. Am laufenden Server ohne JavaScript: „ΟΔΟΣ“ **303**, „οδος“ **422** „Eine Liste mit diesem Namen hast du
> schon.“, „Οδοσ“ **422**. Nebenwirkung der Faltung: auch „ſ“/„s“ und „µ“ (Mikro)/„μ“ gelten als gleich. Keine Datenmigration:
> Feature 17 ist nicht ausgeliefert; die eine Liste der Dev-Datenbank (ASCII) hat denselben Schlüssel wie vorher. Tests:
> `SavedTextTest::testBf220SigmaInJederFormErgibtDenselbenSchluessel`, `SavedListServiceTest::testBf220GriechischesSchlussSigmaIstEineDublette`
> (beide rot vorher: `οδοσ` ≠ `οδος` bzw. kein `NAME_DUPLICATE`), `Qa17NachpruefungTextTest::testBf220…` scharf.

### BUG-04 · Riesige Seitenzahl ergibt an drei weiteren Stellen HTTP 500 — niedrig, vorbestehend (BF-221)

**Betrifft:** 06/16 (Ideen), B23 (REST-API), B20 (Verwaltung); dasselbe Muster wie BF-214.
**Reproduktion:** `GET /de/roadmap/ideen?page=9223372036854775807` (Gast), `GET /api/v1/restaurants?page=9223372036854775807`
(ohne Anmeldung), `GET /de/admin/restaurants?page=9223372036854775807` (Admin).
**Erwartet:** 404 bzw. 4xx. **Tatsächlich:** 500, `TypeError: QueryBuilder::setFirstResult(): … float given` (Protokoll
12:30:19; die API antwortet `{"error":{"code":500,…}}`). Gleicher Code auf `main`.
**Ort:** `src/Controller/BoardController.php:59` → `src/Repository/BoardIdeaRepository.php:73`;
`src/Controller/Api/V1/RestaurantApiController.php:59` → `RestaurantRepository.php:188`;
`src/Controller/AdminRestaurantController.php:35` → `RestaurantRepository.php:76`.
**Vorschlag:** dieselbe Grenze wie BF-214, besser einmal zentral. Reproduktion `Qa17NachpruefungTest::testBf221…` (3 Fälle).

### BUG-05 · Gehweite der Haltestellen 2,48 : 1 — mittel, vorbestehend in B10 (BF-222)

**Reproduktion:** axe `color-contrast` auf `/de/restaurants/15`, `19`, `21` u. a. (375/1280 px): `text-xs text-gray-400` `#99a1af`
auf `bg-gray-50` **2,48 : 1** (`qa/17/QA2-axe.txt`). Der Abschlussbericht des Baus nannte die Stelle, sie stand in keinem Befund.
**Ort:** `templates/partials/_nearby_stops.html.twig:41`. **Vorschlag:** `text-gray-600`. Reproduktion `Qa17NachpruefungTest::testBf222…`.

### BUG-06 · Knopf „Passkey hinzufügen“ 3,61 : 1 — mittel, vorbestehend in B03 (BF-223)

**Reproduktion:** axe auf `/de/profile` (375 und 1280 px): Weiß auf `bg-cyan-600` `#0092b8` **3,61 : 1** bei 16 px fett (keine
große Schrift, verlangt 4,5 : 1). **Ort:** `templates/partials/_passkey_manage.html.twig:98`. **Vorschlag:** `bg-cyan-700`
(Hover `cyan-800`). Reproduktion `Qa17NachpruefungTest::testBf223…`.

### BUG-07 · Schließen-Knopf der Lightbox ohne sichtbaren Fokus — mittel, vorbestehend in B09 (BF-224)

**Reproduktion:** Detailseite mit Titelfoto, per Tastatur auf das Foto, Enter, Tab auf „Close“. **Tatsächlich:** GLightbox
setzt `.gbtn.focused { outline: 2px solid #0f3d81 }` auf das fast schwarze Overlay — größte Pixeländerung **1,48 : 1**, kein
Pixel mit 3 : 1; mit der alten Kaskade identisch (kein Rückschritt von BF-206). **Ort:** `assets/vendor/glightbox/dist/css/glightbox.css:487`
(Fremd-CSS). **Vorschlag:** in `app.css` überschreiben (z. B. Weiß); am Pixel messen. Nur mit Titelfoto/Galerie erreichbar,
die Dev-Daten haben keines — gemessen mit eingesetztem Foto.

### BUG-08 · Prüflauf kippt an der Sekundengrenze — niedrig, vorbestehend in 15 (BF-225)

**Reproduktion:** volle Suite; `SponsorIntegrityTest::testAk08SponsorenAendernNichtsAnRestaurants@Datensatz` vergleicht zwei
Antworten von `/open/dataset.json` wörtlich — liegt eine Sekundengrenze dazwischen, unterscheidet sich `generatedAt`
(`12:27:29` gegen `12:27:30`), der Lauf ist rot. Fünf Wiederholungen grün. **Ort:** `tests/Functional/Sponsor/SponsorIntegrityTest.php:35-37`
(JSON-Zweig ohne Gleichsetzung). **Vorschlag:** `generatedAt` vor dem Vergleich gleichsetzen, wie es der HTML-Zweig mit dem
Herz-Token tut.

## Hinweise (keine Befunde)

- **H-1 · Tom Select hat einen sichtbaren Fokusrahmen.** Der Abschlussbericht des Baus meldet für die Küchen-Auswahl 1,52 : 1.
  Gemessen am `.ts-control` (per Tab und programmatisch): `solid 2px` purple-700, **6,98 : 1**, ≥ 3 : 1 rundum
  (`QA2-tomselect-*.png`). Die niedrige Zahl entsteht, wenn man nur den Ausschnitt um das innere `<input>` vergleicht — der
  Rahmen liegt am äußeren Element. Das ursprüngliche `<select>` hat `tabindex="-1"`.
- **H-2 · Die Fokus-Umfrage meldet zwei weitere Elemente als „schwach“, beide Messgrenzen:** der Tabellenbereich der
  Vergleichsseiten (höher als das Fenster, der Rahmen liegt teils außerhalb des Ausschnitts; cyan-700 auf Weiß 5,05 : 1) und
  die „×“ der Tom-Select-Felder in der Verwaltung bei 375 px (teilweise beschnitten, vorher gleich).
- **H-3 · `code-reviewer`:** Funde 1 (BF-221) und 2 (BF-220) bestätigt und nachgestellt. Fund 3 (unbeschichtetes
  `outline: none` an `.glightbox-container` schlägt jetzt den Fallback) ohne messbare Wirkung: Nach dem Öffnen liegt der Fokus
  nicht auf dem Container, sondern auf dem Foto-Verweis bzw. „Close“; jetzt und mit alter Kaskade identisch. Fund 4
  (`clampPage()` vor der Token-Prüfung) ist eine zusätzliche Zählabfrage nach der Besitzprüfung, ohne Wirkung.
- **H-4 · `Qa16NachpruefungTest::testBf206…` prüft den Text von `app.css`, nicht die Kaskade** — er hält die Schicht fest,
  nicht die Wirkung. Die Wirkung ist hier am Pixel belegt.
- **H-5 · Hinweis H-3 des ersten Berichts gilt weiter:** Die 422-Antworten von „Notiz speichern“ und „In Listen“ tragen
  `private, must-revalidate` statt `no-store` (gemessen); ohne Wirkung nachgestellt im ersten Durchlauf.
- **H-6 · AK-43 bei 768–1023 px** zeigt weiterhin nur das Herz-Symbol (28 × 44 px), wie im ersten Bericht.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Saved/Qa17NachpruefungTest.php` | 6 (alle übersprungen, scharf rot) | Reproduktionen BF-218 (Klasse des Herzens im Kopf), BF-221 (Ideen, API, Verwaltung), BF-222, BF-223 |
| `tests/Unit/Saved/Qa17NachpruefungTextTest.php` | 5 (4 laufen, 1 übersprungen) | AK-19 Gleichheit trotz Schreibweise (3 Fälle, auch „İstanbul“ = „Istanbul“ als Entscheidung des Baus), EC-07 Schlüssel nie länger als der Name (İ, ΟΔΟΣ, ß, ẞ, ǅ, 😀, 日本), Reproduktion BF-220 |

Ausgeführt: normal 11 Fälle, 4 grün, 7 übersprungen; `QA17_REPRO=1` → 7 rot (Failures, keine Errors). Die
Mutationsprüfung der Prüfläufe des Baus (zehn Rücknahmen, jede wieder hergestellt und per Prüfsumme verglichen) steht oben
unter *Befunde des Fehlerauftrags*.

Browser- und Messskripte (nicht im Projekt, unter `/tmp/qa17b/bin`): `fokus.js` (Umfrage jetzt/vorher), `glas.js`/`glas2.js`
(Etiketten), `axe.js`, `browser.js` (Herz, Tom Select, AK-06, AK-14, AK-42/43, AK-48, AK-50, AK-63, EC-11), `sponsor.js`,
`extra-fokus.js`, `lightbox.js`, `ueberlauf*.js`, `mutation.sh`, `ak59.sh`, `hauptweg.sh`.

## Testdaten

In der Dev-Datenbank angelegt und nach der Prüfung entfernt: Konten `qa17a`…`qa17i@example.test` (Kaskade nahm Favoriten,
Listen, Einträge und Notizen mit; `qa17f` in AK-61 über die Anwendung gelöscht), Lokale `QA17 Lokal 1–5` (Nr. 1002–1006;
Nr. 1000/1001 in AK-62/EC-03 über die Verwaltung gelöscht), das eingesetzte Titelfoto (Zeile in `restaurant_image` und
`public/uploads/restaurants/qa17b-titelfoto.jpg`), die Löschbestätigung an `qa17f` in `messenger_messages`. Nr. 18 kurz
unverifiziert (EC-12), zurückgesetzt. Die Daten des Baus an `user@endlech.lu` sind unverändert (Abgleich aller Zeilen der vier
Tabellen vor/nach).

## Nächster Schritt

**`/sdd-deploy 17`** — mit T29 im Release-Commit (`RoadmapRegistry` ohne `favourites`, `ChangelogRegistry` + fünf
`changelog.*.yaml`; erst dann ist AK-70 prüfbar) und **Migration vor dem Code** (`Version20261113221129`, rein ergänzend; Weg
„einmaliger Pre-Deployment-Befehl“ aus `CLAUDE.md`). Vor dem Ausrollen beim Betreiber: AK-51 abnehmen, in Sentry nach alten
Ereignissen mit Anfragerumpf sehen.

Empfohlen vorher, blockiert nicht: **`/sdd-build 17`** mit BF-218 (AK-49) und BF-219, dazu BF-220 — alle drei in Dateien
dieses Features; danach `/sdd-qa 17`. Die vorbestehenden BF-221 bis BF-225 gehören zu 06/16, B20, B23, B10, B03, B09 und 15
und stehen in `features/befunde.md`. Offen für den Betreiber: **AK-48** mit VoiceOver, **AK-63** in echtem Safari.
