# 17 · Favoriten und Listen — Testbericht

Stand: 2026-11-14 · Geprüft gegen `spec.md` vom 2026-11-13 (AK-01 bis AK-70, EC-01 bis EC-16, OF-04 entschieden, Decision
Log bis #25) · in eigener Session, ohne Bauverlauf · Vorstufe `building`, Abschlussbericht am Ende von `tasks.md` als
Suchliste gelesen (nicht als Nachweis).

**Prüfumgebung.** Arbeitsbaum `feature/17-favoriten-und-listen` (alles uncommittet), Entwicklungsserver
`http://127.0.0.1:8000` gegen die Dev-Datenbank `endlech` (MySQL 8 im Container `mikaplus-database-1`), Assets vorher frisch
gebaut (`tailwind:build`, `typescript:build`). Anfragen mit `curl` und Cookie-Dateien je Konto; Browser: **Chromium und
WebKit über Playwright 1.x** (unter `/tmp/qa17/pw`, nicht im Projekt), axe-core 4 mit **blockiertem** `/_wdt` und
`/_profiler` (Muster BF-207). Die eingebaute Vorschau taugte nur für AK-01: Ihr Dokument hat keinen Fokus
(`document.hasFocus() === false`), `:focus`/`:focus-visible` greifen dort nie — Fokusmessungen deshalb in Playwright.
Testkonten in der Dev-DB `qa17a`…`qa17f@example.test` (Passwort wie `user@endlech.lu`, `qa17c` unbestätigt), dazu zeitweise
fünf Kopien eines rollstuhlgerechten Lokals (`QA17 Lokal 1–5`, Nr. 901–905) und ein Wegwerf-Lokal Nr. 999 für AK-62. Nach
der Prüfung entfernt (siehe *Testdaten*). Nachweise unter `qa/17/` mit Präfix `QA-` (die Bilder ohne Präfix stammen aus
dem Selbsttest des Baus und sind hier kein Nachweis).

## Fazit

**Production-ready: ja — mit Vorbehalten** (kein Befund *kritisch* oder *hoch*; **EC-07 ist durchgefallen**).

Das Feature tut, was die Spec verlangt, und hält den Angriffen stand. Herz, Listen, Notiz, Filter und die Seiten unter
„Gespeichert“ funktionieren mit und ohne JavaScript; im Browser springt keine Seite beim Herz (Karte bleibt auf 384 px,
`scrollY` unverändert), der Fokus bleibt am Herz bzw. wandert nach dem Entfernen auf die nächste Karte, die vorige oder den
Leertext. **Fremde Listen sind nicht erreichbar**: acht Lese- und Schreibwege, je mit fremder und mit unbekannter Nummer
(16 Anfragen), antworten identisch mit 404, eine fremde Nummer im Häkchen-Formular ebenso, nichts ändert sich. Der Deckel greift genau bei
der **121.** Änderung — vorher 40 wirkungslose oder abgewiesene Absendungen, die nicht zählten, und ein kombiniertes
Absenden (Häkchen + neue Liste), das einmal zählte —, ein anderes Konto am selben Anschluss speichert weiter. Kontolöschung
und Löschen eines Lokals räumen alle vier Tabellen ab, der Export enthält alles. Zu Umami gehen Listenseiten als
`/de/gespeichert/listen` ohne Titel, Herkunft und Nummer (am tatsächlichen Ausgang des `CollectPayloadNormalizer`); den
Notiztext lässt die echte `RequestIntegration` von sentry/sentry mit dem Produktionswert nicht mehr ins Ereignis (Gegenprobe
mit der Bibliotheksvorgabe: er stünde darin). Nach dem Abmelden zeigt „Zurück“ in Chromium und WebKit — mit Turbo und als
voller Seitenwechsel — weder Listen noch Notizen.

Durchgefallen ist ein Randfall: **BF-211** (*mittel*) — ein Listenname aus 31 bis 60 „İ“ ist als Name gültig, sein
Vergleichsschlüssel aber doppelt so lang; `name_key VARCHAR(60)` läuft über, **HTTP 500** auf allen drei Wegen (EC-07:
„nie mit einem Serverfehler“). Dazu zwei kleine 500er nur über handgemachte Zahlen (BF-212, BF-213, *niedrig*), eine im
neuen Kopfband unsichtbare Fokusanzeige als weitere Stelle des offenen BF-206 (BF-217, *mittel*), und drei vorbestehende
Befunde, die die Prüfung mitgefunden hat (BF-214 Suche, BF-215 untere Leiste, BF-216 Detailseite).

Nicht prüfbar sind drei Kriterien, keines aus technischem Mangel des Baus: **AK-48** (Ansage durch VoiceOver — kein
Screenreader in der Prüfumgebung; Fokus, neuer Name und gefüllter `role="status"`-Bereich sind gemessen), **AK-51**
(Abnahme des Betreibers; der Durchgang des Prüfers bei 375 px gelang ohne Hilfe) und **AK-70** (gilt erst mit dem Release,
T29).

**Nächster Schritt: `/sdd-deploy 17`** — mit T29 im Release-Commit (AK-70) und **Migration vor dem Code** (design.md).
Empfohlen vorher, blockiert nicht: `/sdd-build 17` mit BF-211 (ein 500er, den ein Nutzer mit türkischem Namen ohne
Absicht auslösen kann), BF-212, BF-213 und BF-217.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 67 von 70 |
| davon bestanden | 67 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 3 (AK-48, AK-51, AK-70) |
| Edge Cases belegt | 16 von 16 (15 bestanden, **EC-07 durchgefallen**) |
| Tests neu geschrieben | 17 Fälle in 2 Dateien |
| Tests grün | 13 von 13 ausgeführten; 4 Reproduktionen übersprungen, scharf (`QA17_REPRO=1`) alle 4 rot |
| Volle Suite gelaufen | 2271 Tests, 0 Fehlschläge, 44 übersprungen (vor den neuen Tests: 2254, 0, 40) |
| Regression: berührte Features | 15, davon Hauptweg bestanden 15 |

## Akzeptanzkriterien im Einzelnen

Abgehakt wird nur, was ausgeführt wurde. „Test“ heißt: in der vollen Suite dieses Durchlaufs grün gelaufen und auf seine
Zusicherungen gelesen.

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `curl` mit `Accept: text/vnd.turbo-stream.html` → 200, `replace method="morph" target="favorit-12"`, Knopf „… aus Favoriten entfernen“, `update target="meldung-live"` „Gemerkt: Pizzeria Bella Vista“, DB 1 Zeile. Im Browser (Vorschau, 375 px, `?sort=name`): ein `turbo:submit-start`, Herz vor/nach bei `top` 384 px, `scrollY` 2561 → 2561, Adresse unverändert, keine waagerechte Scrollleiste |
| AK-02 | ✅ bestanden | Detailseite Nr. 18: Herz im Kopf neben dem `h1`, Stream wie AK-01 („Gemerkt: Sushi Zen“), ohne JS 303 → `/de/restaurants/18#favorit-18`; beide Kopfvarianten: `DetailHeartVariantsTest::testAk02HerzNebenDemNamenMitUndOhneTitelbild` |
| AK-03 | ✅ bestanden | Ohne JS 303 auf dieselbe Seite, „Aus Favoriten entfernt: Pizzeria Bella Vista“; DB vorher `fav=1 listen=2,4 notiz=50d455af`, nachher `fav=0 listen=2,4 notiz=50d455af`; nicht mehr auf „Favoriten“ (0 × `karte-12`) |
| AK-04 | ✅ bestanden | Zweite Sitzung desselben Kontos (neue Cookie-Datei): Favorit laut DB (Nr. 14) gefüllt in Suche und „Favoriten“, nicht gemerkte Nr. 12 leer |
| AK-05 | ✅ bestanden | Veraltetes leeres Herz auf schon gemerktes Lokal → bleibt 1 Zeile, Meldung „Gemerkt“; veraltetes gefülltes Herz auf entferntes → bleibt 0, keine Fehlermeldung; **10 gleichzeitige** Tipps auf dasselbe leere Herz → `303 ×10`, genau **1** Zeile |
| AK-06 | ✅ bestanden | **Chromium ohne JavaScript**, 12 rollstuhlgerechte Lokale, `?wheelchair=1&sort=name&page=2`, Herz der **vierten** Karte → `…&page=2#favorit-905`, Herz sichtbar bei 96 px, gefüllt, „Gemerkt: QA17 Lokal 5“ auf der Seite (`qa/17/QA-AK06-ohne-js-seite2-vierte-karte.png`); neu `Qa17PruefungTest::testAk06…` (Hinweis H-1: die Meldung steht oberhalb des Sichtbereichs) |
| AK-07 | ✅ bestanden | Gast auf Suche und Detailseite: 1 Herz je Karte, 0 gefüllt, kein `#in-listen`, kein `#notiz`, kein `<textarea>`, kein „Nur meine Favoriten“ |
| AK-08 | ✅ bestanden | Gast, `?wheelchair=1&page=2`, Herz → 303 `/de/login`; dort „Melde dich an, um dir Café Nordstad zu merken.“; `favorite` unverändert (3 Zeilen gesamt) |
| AK-09 | ✅ bestanden | Passwort: Anmeldung in derselben Sitzung → 302 `/de/restaurants?wheelchair=1&page=2#favorit-17`, DB `8 17`, „Gemerkt: Café Nordstad“, Herz gefüllt, `scroll-to` auf `favorit-17`. Passkey: Zeremonie ohne virtuellen Authenticator nicht ausführbar; beide Hälften (Zielseite aus `onAuthenticationSuccess`, Einlösen über den Ereignisverteiler der Firewall) in `PendingFavoriteLoginTest::testAk09PasskeyWegNimmtDieselbeZielseiteUndMerkt` |
| AK-10 | ✅ bestanden | Falsches Passwort → 302 `/de/login`, Hinweis steht weiter, 0 Zeilen; zweiter Versuch → gemerkt (siehe AK-09) |
| AK-11 | ✅ bestanden | `/de/gespeichert` (Konto A): „Favoriten 3 Lokale“, „Meine Listen: Parallel 0 · Kirchberg mittags 2 · bbbb… 0 · Mittag in Kirchberg 1“ (zuletzt angelegt zuerst), Feld „Neue Liste“, „Anlegen“; `SavedPagesTest::testAk11…` |
| AK-12 | ✅ bestanden | `SavedPagesTest::testAk12FuenfzehnFavoritenStehenZuZwoelfUndDrei` (12 + 3, zuletzt gemerkt zuerst, Notiz unter der Karte, Seite 3 → 404); im Dev-Bestand zu wenig Lokale für 15 |
| AK-13 | ✅ bestanden | Unbestätigtes Konto ohne Daten: beide Leertexte wörtlich, Verweis `/de/restaurants` „Zur Restaurantsuche“ |
| AK-14 | ✅ bestanden | Chromium, Tastatur (Enter) auf „Favoriten“ mit 4 Karten: mittlere entfernt → Fokus auf nächster (`karte-12`); letzte → vorige; erste → nächste; einzige → `#gespeichert-leer` „Noch nichts gemerkt …“; Meldung jeweils im Ansagebereich |
| AK-15 | ✅ bestanden | Gast: `/de/gespeichert`, `/favoriten`, `/listen/4`, `/listen/4/loeschen` → 302 `/de/login`; Anmeldung mit Konto A → 302 `/de/gespeichert/listen/4`; mit Konto B → dieselbe Zielseite, dort 404 |
| AK-16 | ✅ bestanden | „Mittag in Kirchberg“ → 303 `/de/gespeichert`, „Liste „Mittag in Kirchberg“ angelegt.“, zuoberst mit „0 Lokale“ |
| AK-17 | ✅ bestanden | Leer und „    “ → 422, „Bitte gib der Liste einen Namen.“ am Feld, Zahl der Listen unverändert |
| AK-18 | ✅ bestanden | 61 × „a“ → 422 mit Meldung, Eingabe steht im Feld; „  “ + 60 × „b“ + „  “ → gespeichert als `[bbb…]`, 60 Zeichen |
| AK-19 | ✅ bestanden | „Kirchberg“ dann „ kirchberg “ → 422 „Eine Liste mit diesem Namen hast du schon.“, eine Zeile; Konto B legt „Kirchberg“ an → `7 Kirchberg`, `8 Kirchberg` |
| AK-20 | ✅ bestanden | Konto mit 25 Listen: 26. auf „Gespeichert“ → 422 „Du hast bereits 25 Listen. …“; auf der Detailseite („Mit Oma“) ebenso; Zahl bleibt 25 |
| AK-21 | ✅ bestanden | Drei Listen, Lokal in einer: drei Häkchen mit Namen, genau eines `checked`, Feld „Neue Liste“, „Speichern“ |
| AK-22 | ✅ bestanden | Häkchen 2 → 4 umgehängt: 303 `#in-listen`, „Listen gespeichert.“, DB nur Liste 4, Herz unverändert (0), Zahlen auf „Gespeichert“ 1/0/0 |
| AK-23 | ✅ bestanden | „Mit Oma“ auf der Detailseite → Liste mit dem Lokal; Dublette „kirchberg“ + Häkchenänderung → 422, Häkchen unverändert; 61 Zeichen + Häkchenänderung → 422, unverändert |
| AK-24 | ✅ bestanden | Konto ohne Liste: 0 Häkchen, nur `restaurant_lists[newList]` |
| AK-25 | ✅ bestanden | `SavedPagesTest::testAk25ListeMitVierzehnLokalenStehtZuZwoelfUndZwei` (Name als einzige `h1`, 12 + 2, Notiz unter der Karte, Seite 3 → 404); Dev: Listenname als `h1` |
| AK-26 | ✅ bestanden | Ohne JS: 303 `/de/gespeichert/listen/4#karte-18`, „Pizzeria Bella Vista aus „Kirchberg“ entfernt.“, Lokal bleibt in „Mit Oma“, Herz 1, Notiz unverändert; mit Turbo: 200 `text/vnd.turbo-stream.html`, `replace method="morph" target="gespeichert-karten"` + Meldung |
| AK-27 | ✅ bestanden | Leere Liste: „Diese Liste ist noch leer. Lokale legst du auf ihrer Detailseite unter „In Listen“ hinein.“ + `/de/restaurants` |
| AK-28 | ✅ bestanden | „KIRCHBERG“ (eigener Name, andere Schreibweise) → 303, keine Dublette; „Kirchberg mittags“ → überall (Übersicht, `h1`, Häkchen der Detailseite); leer / 61 / „mit oma“ → 422 mit der jeweiligen Meldung, Name unverändert |
| AK-29 | ✅ bestanden | Rückfrage: „Liste „Mit Oma“ mit 4 Lokalen löschen?“ + Erläuterung, „Abbrechen“ (Verweis) und „Löschen“ (POST-Formular), 0 × `onsubmit`/`onclick`, Liste noch da; ohne Skript durchgespielt (`curl`: GET Rückfrage, POST Löschen, AK-30) |
| AK-30 | ✅ bestanden | Abbrechen → Listenseite 200; Löschen → 303 `/de/gespeichert`, „Liste „Mit Oma“ gelöscht.“, Liste und Einträge 0, Favoriten 12/14 und Notizen 12/20 unverändert, alte Adresse 404 |
| AK-31 | ✅ bestanden | 8 gleichzeitige Absendungen mit gesetztem Häkchen → `303 ×8`, genau 1 Eintrag |
| AK-32 | ✅ bestanden | Lokal weder gemerkt noch in Liste: „Deine Notiz“, Feld, „Nur für dich sichtbar.“, „Notiz speichern“ |
| AK-33 | ✅ bestanden | 303 `#notiz`, „Notiz gespeichert.“, nach Neuladen Text im Feld |
| AK-34 | ✅ bestanden | Notiz unter Karte 12 in „Favoriten“, Liste 2 und Liste 4 (`<p … whitespace-pre-line>`) |
| AK-35 | ✅ bestanden | 501 Zeichen → 422, Meldung am Feld, gespeicherte Notiz unverändert, Eingabe (501) steht im Feld; Grenze: 6 × `\r\n` + 494 Zeichen (506 Bytes) → gespeichert mit 500 Zeichen |
| AK-36 | ✅ bestanden | Feld geleert → „Notiz gelöscht.“, 0 Zeilen; unter der Karte auf „Favoriten“ kein Notizblock mehr |
| AK-37 | ✅ bestanden | `<b>fett</b> <script>alert(1)</script>` + Umbruch: in „Favoriten“, zwei Listen und im Feld der Detailseite als `&lt;b&gt;…`, 0 × rohes `<script>alert(1)`, Umbruch als `\n` in `whitespace-pre-line` |
| AK-38 | ✅ bestanden | 5 Favoriten, davon 2 rollstuhlgerecht: `?favorites=1&wheelchair=1` → genau 12 und 18; Sortierung name/rating/newest ordnet um; Blättern wie jeder Filter (`SearchFavoritesTest::testAk38SortierungUndBlaetternBehaltenDenFilter`) |
| AK-39 | ✅ bestanden | Gast: kein Häkchen; `?favorites=1&wheelchair=1` → 200, dieselben 6 Karten wie ohne den Parameter, keine Meldung |
| AK-40 | ✅ bestanden | Konto ohne Favoriten: Leertext wörtlich, „Filter entfernen“ → `/de/restaurants?sort=name&wheelchair=1` |
| AK-41 | ✅ bestanden | Angemeldet: `/de/` · `/de/restaurants` · `/de/gespeichert` · `/de/profile`, `aria-current="page"` auf „Gespeichert“ bei Übersicht, Favoriten, Liste, Rückfrage; Gast: … `/de/about` · `/de/login`; „Über uns“ in der Fußzeile |
| AK-42 | ✅ bestanden | Chromium 320 px, angemeldet, lb/de/fr/en/pt: je 4 Punkte 80 × 60 px, jede Beschriftung einzeilig, nicht abgeschnitten, kein Überlauf (`qa/17/QA-AK42-leiste-320-*.png`) |
| AK-43 | ✅ bestanden | 768/1024/1280 px: Verweis `/de/gespeichert` im Kontobereich, zugänglicher Name „Gespeichert“, kein Überlauf (`QA-AK43-kopfzeile-*.png`); Hinweis H-2 zu 768–1023 px |
| AK-44 | ✅ bestanden | `/de/profile` führt im Inhalt auf `/de/gespeichert` |
| AK-45 | ✅ bestanden | `/sitemap.xml`: 160 `<loc>`, 0 × `gespeichert` |
| AK-46 | ✅ bestanden | 58 neue `messages`- und 5 `validators`-Schlüssel in allen fünf Katalogen; mit Deutsch identisch nur `%list% – Endlech.lu` und lb „Notiz zu %name%“ (korrektes Luxemburgisch). Fünf Seiten × vier Sprachen ohne deutschen Rest; Meldungen fr/pt/en, Rückfrage lb („Lëscht „Kirchberg mittags“ mat 2 Lokaler läschen?“); Listennamen unverändert |
| AK-47 | ✅ bestanden | Barrierefreiheitsbaum (Chromium, `ariaSnapshot`) der Suche: `button "Brasserie du Grund merken"`, `button "Burger & Co. aus Favoriten entfernen"` … — sechs verschiedene Namen; VoiceOver selbst nicht gelaufen |
| AK-48 | ⚠️ nicht prüfbar | Ansage durch VoiceOver: kein Screenreader in der Prüfumgebung. Gemessen (Chromium, Tab + Enter): Fokus bleibt auf `favorit-knopf-21`, Name wechselt auf „Green Bowl aus Favoriten entfernen“, `#meldung-live` (`role="status"`) enthält „Gemerkt: Green Bowl“, Herz bleibt bei 432 px |
| AK-49 | ✅ bestanden | Formen: umrandet (`fill="none"`, Strich) vs. gefüllt (`fill="currentColor"`); Symbol gray-700 bzw. red-600 auf weißem Knopf (≈ 10 : 1 bzw. 4,8 : 1); 44 × 44 px; Tastaturfokus nach dem Übergang `solid 2px rgb(14,116,144) offset 2px` (`QA-AK49-herz-fokus-*.png`) |
| AK-50 | ✅ bestanden | Mit JavaScript (Chromium): leerer Listenname → Fokus `#saved_list_name_name`, 501-Zeichen-Notiz → Fokus `#restaurant_note_text`; Feld `aria-invalid="true"`, `aria-describedby="…_help …_error"`, Meldung im `…_error`; Dublette auf der Detailseite ebenso |
| AK-51 | ⚠️ nicht prüfbar | Abnahme des Betreibers (Decision Log 20). Durchgang des Prüfers, Chromium 375 px, neues Konto: merken (bleibt bei 2561 px), Liste anlegen, Lokal hineinlegen (steht bei `#in-listen`), Notiz (steht bei `#notiz`), Filter — jeder Schritt gelang, keine waagerechte Scrollleiste (`QA-AK51-*.png`); Hinweis H-4 |
| AK-52 | ✅ bestanden | Konto B und Gast auf Suche, Detailseiten 12/20, `?favorites=1`: 0 gefüllte Herzen von A, 0 × „Kirchberg mittags“/„Parallel“, 0 × Notiztext — im Quelltext |
| AK-53 | ✅ bestanden | Konto B auf Liste 4 (Konto A) und Nr. 99999: GET Liste, `?page=2`, Rückfrage → je 404, gleiche Antwort, 0 × Name, 0 × Zahl; `SavedPagesTest::testAk53…` |
| AK-54 | ✅ bestanden | Konto B mit eigenem gültigem Token: Umbenennen, Löschen (mit/ohne Token), Entfernen, Häkchen mit Nr. 4 bzw. 99999 → je 404; Häkchen „eigene + fremde“ → 404, 0 Einträge; Liste 4 danach `Kirchberg mittags`, Einträge 12, 21 |
| AK-55 | ✅ bestanden | Lokal Nr. 999999 an merken, nicht-mehr-merken, listen, notiz, entfernen, Gast-merken → je 404; Zeilen gesamt 49 → 49, keine Liste „NeuX“ |
| AK-56 | ✅ bestanden | Falsches Token (Herz, Löschen, Entfernen, Token einer anderen Liste) und fremde Herkunft (Liste, Umbenennen, Häkchen, Notiz, Herz-Entfernen) → 303 mit „Ungültiges CSRF-Token. Bitte versuche es erneut.“; Prüfsumme über alle Daten des Kontos vorher = nachher |
| AK-57 | ✅ bestanden | Dev-Wert 120/h: Änderungen 1–120 durch, die **121.** → „Zu viele Änderungen. Bitte versuche es in 60 Minuten erneut.“ (Stream) bzw. als Meldung (Liste anlegen), nichts gespeichert; Konto A am selben Anschluss danach 303 + gespeichert |
| AK-58 | ✅ bestanden | Im selben Lauf: 20 × veraltetes Herz, 10 × leerer Name, 5 × leere Notiz ohne Notiz, 5 × unveränderte Häkchen zählten nicht, Häkchen + „Kombi“ zählte einmal — sonst wäre der Deckel vor der 121. gegriffen; neu `Qa17PruefungTest::testAk58…` (am Kontingent gemessen) |
| AK-59 | ✅ bestanden | 23 Verwaltungsseiten (Dashboard, Ideen, Finanzen inkl. Bearbeiten, Restaurants inkl. 11 × Bearbeiten, Vorschläge, Warteliste) als Admin: kein Listenname, keine Notiz, keine Zahl — einzige Treffer der eigene Kopfverweis „Gespeichert“ und das Importmap; `SavedPrivacyLeakTest::testAk59…` |
| AK-60 | ✅ bestanden | Export (`/de/profile/daten`, `endlech.lu account export v1`): `favorites` 2 × (Nr., Name, `savedAt`), `savedLists` 1 × („Mit Oma“, `createdAt`, 3 Lokale je `addedAt`), `notes` 1 × (Lokal, Text mit Umbruch, `updatedAt`) |
| AK-61 | ✅ bestanden | Konto mit fav=2, Liste=1, Einträge=3, Notiz=1 gelöscht → `user` 0, alle vier Tabellen 0, keine verwaisten Einträge |
| AK-62 | ✅ bestanden | Admin löscht Lokal Nr. 999 (gemerkt, in zwei Listen, Notiz): 302; danach 0 Zeilen in `favorite`/`saved_list_entry`/`restaurant_note`; „Gespeichert“ 200, Favoriten 4 → 3, Listen 3 → 2 und 2 → 1; keine Meldung; `messenger_messages` unverändert (keine Mail) |
| AK-63 | ✅ bestanden | Liste mit Notizen → Abmelden → „Zurück“: **Chromium und WebKit**, mit Turbo und als voller Seitenwechsel (`data-turbo=false`, ohne JS) → `/de/login`, 0 × Listenname, 0 × Notiz; zusätzlich nach einer 422-Antwort auf der Detailseite (H-3). Echtes Safari nicht geprüft |
| AK-64 | ✅ bestanden | Tatsächlicher Ausgang des `CollectPayloadNormalizer` (Testkernel): Listenseite `…/listen/4?page=2#karte-12` mit Titel → `url: /de/gespeichert/listen`, kein Titel; Herkunft eigene Listenadresse → entfällt; `herz_gesetzt`/`liste_angelegt` mit untergeschobenen Daten → abgewiesen. Protokoll: Listennamen/Notizen nur in `doctrine.DEBUG` (dev); `when@prod` `dbal.logging: false` (`SavedPrivacyLeakTest::testAk64ProduktionProtokolliertKeinSql`). Mails: einzige Nachricht des Laufs ist die Löschbestätigung (ohne Listen-/Notizinhalt). Brevo: kein Code berührt |
| AK-65 | ✅ bestanden | Neu `Qa17SentryAnfragerumpfTest`: Notiz-POST durch `Sentry\Integration\RequestIntegration` mit dem `when@prod`-Wert → kein `data`, kein „Seiteneingang“; Gegenprobe mit `medium` → Text steht darin |
| AK-66 | ✅ bestanden | Auslöser je Handlung: Liste anlegen (Übersicht und Detailseite) 1 × `liste_angelegt`; Herz setzen (Stream, Folgeseite, nach Anmeldung) 1 × `herz_gesetzt`; Umbenennen, Notiz, Herz entfernen, veraltetes Herz, abgewiesene Liste, falsches Token → keiner; Filter: `filter_angewandt` mit `{"filter":"favorites,wheelchair"}` kommt durch den Normalizer (Technische Entscheidung 10) |
| AK-67 | ✅ bestanden | Siehe AK-64: `/de/…` und `/fr/gespeichert/listen/4/loeschen` → `/xx/gespeichert/listen`; `Feature17UsageEventsTest` prüft dasselbe Muster in `before_send.ts` |
| AK-68 | ✅ bestanden | `/xx/legal`, Abschnitt `#gespeichert` in lb/de/fr/en/pt: was gespeichert wird, nur der Kontoinhaber sieht es, kein weiterer Dienstleister (Fehleranalyse, Mail, Messung), im Export, mit dem Konto gelöscht |
| AK-69 | ✅ bestanden | Unbestätigtes Konto `qa17c`: 5 Herzen (303 ×5, DB 12,13,16,18,19), Liste, Notiz gespeichert; „Gespeichert“ 200 |
| AK-70 | ⚠️ nicht prüfbar | Gilt nach der Auslieferung (T29 im Release-Commit). Stand heute: „Favoriten“ unter „Geplant“, `RoadmapRegistry` Zeile 37 `favourites` — erwartungsgemäß |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Gast merkt Nr. 22, Anmeldung desselben Kontos in **anderer** Sitzung → 0 Zeilen; neu `Qa17PruefungTest::testEc01…` |
| EC-02 | ✅ bestanden | Danach Anmeldung in der Sitzung des Gastes → `/de/restaurants#favorit-22`, 1 Zeile; `PendingFavoriteLoginTest::testEc02…` |
| EC-03 | ✅ bestanden | `PendingFavoriteLoginTest::testEc03GeloeschtesLokalWirdStillVerworfen` |
| EC-04 | ✅ bestanden | `PendingFavoriteLoginTest::testEc04…`; Gegenprobe: Konto mit `onboarding_due` ohne Merkwunsch → 302 `/de/einfuehrung` |
| EC-05 | ✅ bestanden | 8 gleichzeitige „Parallel“ → `303 ×1, 422 ×7`, 1 Liste; 24 Listen + 6 gleichzeitige Anlagen → `303 ×1, 422 ×5`, 25 |
| EC-06 | ✅ bestanden | Zwei Sitzungen, „Gerät 1“ dann „Gerät 2“ → DB und Feld „Gerät 2“, keine Warnung |
| EC-07 | ❌ durchgefallen | 60 × 😀, 60 × Ä, 60 × 日 → gespeichert; 20 × 👨‍👩‍👧 (100) und „Istanbul“ + 53 × İ (61) → 422; **31 × İ und 60 × İ → HTTP 500**, siehe BUG-01 / BF-211 |
| EC-08 | ✅ bestanden | `"   \r\n\r\n  \n "` → „Notiz gelöscht.“, 0 Zeilen |
| EC-09 | ✅ bestanden | „Entfernen“ in gelöschter bzw. unbekannter Liste → 404, nichts entsteht; `SavedListManageTest::testEc09…` |
| EC-10 | ✅ bestanden | `SavedEdgeCasesTest::testEc10FilterAktivHerzEntferntKarteFehltNachDemNeuladen` |
| EC-11 | ✅ bestanden | Chromium mit Service Worker: Cache `endlech-v5` enthält nach Besuch von „Gespeichert“ nur Offline-Seite, Logo, Icons, Manifest; offline `/de/gespeichert/listen/4` → „Offline – Endlech.lu“, kein Listenname, keine Notiz (`QA-EC11-offline-liste.png`) |
| EC-12 | ✅ bestanden | `SavedEdgeCasesTest::testEc12…` |
| EC-13 | ✅ bestanden | `SavedEdgeCasesTest::testEc13…`; Konto `qa17c` sieht seine Daten |
| EC-14 | ✅ bestanden | Sprachumschalter auf `/fr/gespeichert/listen/4` führt auf `/de|en|lb|pt/gespeichert/listen/4`, `h1` „Kirchberg mittags“ unverändert; `SavedEdgeCasesTest::testEc14Ak46…` |
| EC-15 | ✅ bestanden | `SavedCardsTest::testEc15…`, `SavedPagesTest::testEc15…` |
| EC-16 | ✅ bestanden | „Favoriten“/Liste wie die Suche: `abc` 400, `page[]=1` 400, `0`/`-1` 200 (Seite 1), `2` hinter dem Ende 404, `9223372036854775807` → 404 (die Suche selbst: 500, BF-214); `SavedPagesTest::testEc16…`. Das POST-Feld `page` von „Aus Liste entfernen“ → BF-213 |

## Regression

Volle Testsuite: `php -d memory_limit=1G bin/phpunit` → **2271 Tests, 0 Fehlschläge, 44 übersprungen** (40 wie vor
diesem Durchlauf + 4 Reproduktionen). Vor den neuen Tests: 2254, 0, 40 — derselbe Stand wie im Abschlussbericht des Baus;
der letzte Bericht eines anderen Features (16) lief mit 1947.

Berührt (Arbeitsbaum gegen `main`, alles uncommittet, daher `git status` statt `git diff main...HEAD`): außerhalb des
Feature-Codes `config/packages/{security,framework,sentry,doctrine}.yaml`, `templates/base.html.twig`,
`partials/_bottom_nav`, `partials/_flash_messages`, `profile/index`, `restaurant/index`, `restaurant/show`,
`security/login`, `impressum/index`, `src/Controller/{RestaurantController,SecurityController}.php`,
`src/Repository/RestaurantRepository.php`, `src/Account/AccountDataExporter.php`, `src/Seo/SeoRegistry.php`,
`src/Usage/{CollectPayloadNormalizer,UsageEventCatalogue}.php`, `assets/usage/before_send.ts`, `bin/sicherung-pruefen.sh`,
zehn Kataloge, ein bestehender Prüflauf (`SponsorIntegrityTest`, gelesen: setzt nur das je Anfrage neue Herz-Token gleich,
schwächt die Sponsorprüfung nicht).

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B01 Registrierung | Abhängig von; Layout | ✅ bestanden | `/de/register` 200; Registrierungsprüfläufe der Suite grün |
| B02 Anmeldung | `SecurityController::login`, Login-Vorlage | ✅ bestanden | zahlreiche Anmeldungen dieses Laufs (curl, Chromium, WebKit), Fehlversuch → `/de/login` |
| B03 Passkey | gleiche Firewall, `LoginSuccessEvent` | ✅ bestanden | `POST /passkey/login/options` → 200; `SecurityControllerTest` grün |
| B04 Profil | Verweis im Profil | ✅ bestanden | `/de/profile` 200, Export 200, Kontolöschung 302 → Konto weg |
| B05 Restaurantsuche | Karte als Partial, Filter, Repository | ✅ bestanden | Filter, Sortierung, Blättern, 404 hinter dem Ende; `RestaurantRepositoryTest` grün (BF-214 ist älter) |
| B06 Detailseite | Baustein `RestaurantShowView`, Herz, Abschnitt „Für dich“ | ✅ bestanden | Gast und angemeldet 200, Nahverkehr/Öffnungszeiten gerendert |
| B20 Restaurantverwaltung | Löschkaskade | ✅ bestanden | 11 Bearbeitungsseiten 200, Löschen 302 (AK-62) |
| B24 Mehrsprachigkeit | fünf Kataloge | ✅ bestanden | `CatalogueCompletenessTest` grün, Seiten in fünf Sprachen |
| B25 PWA, untere Leiste | Leiste, Service Worker | ✅ bestanden | Leiste angemeldet/abgemeldet (AK-41), `manifest.webmanifest`/`sw.js` 200, SW aktiv (EC-11) |
| 01 Betroffenenrechte | Export, Löschung | ✅ bestanden | AK-60, AK-61 |
| 02 Barrierefreiheit | neue Seiten, Fokus | ✅ bestanden | axe in den neuen Teilen ohne Verstoß (`qa/17/QA-axe-2026-11-14.txt`); BF-217 ist eine weitere Stelle des offenen BF-206 |
| 10 Sitemap | `SeoRegistry` | ✅ bestanden | Sitemap 200 ohne Gespeichert-Adressen; `SeoRouteCoverageTest` grün |
| 11 Nutzungsmessung | Normalizer, `before_send.ts`, Katalog | ✅ bestanden | Normalizer-Ausgänge (AK-64/66/67); `UsageEventCatalogueTest` grün |
| 12 Einführung | Weiche nach der Anmeldung | ✅ bestanden | Konto mit `onboarding_due` → `/de/einfuehrung`; EC-04 |
| 15 Sponsoren | geänderter Prüflauf | ✅ bestanden | `/de/sponsoren` 200, `SponsorIntegrityTest` grün |

Außerdem 200: `/de/{about,legal,roadmap,roadmap/ideen,changelog,open,presse,app,organisationen,partner}`, `/health`,
`/open.json`, `/robots.txt`, `/api/v1/restaurants`.

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | 8 Wege × (fremde Nr. 4, unbekannte Nr. 99999) mit Konto B → je 404, gleiche Antwort; Häkchen mit fremder Nummer → 404 vor dem Token; Liste 4 unverändert (AK-53, AK-54). Nummern jenseits `PHP_INT_MAX` → 500 statt 404 (BF-212) |
| Zugriffsregeln serverseitig | bestanden | `access_control` `^/[a-z]{2}/gespeichert` → Gast 302 Login (4 Seiten); Lokal-Handlungen ohne Konto → Login; Admin sieht nichts (23 Seiten, AK-59); `favorites_of` kommt nur aus der Sitzung (`?favorites=1` als Gast wirkungslos) |
| Rate Limit greift | bestanden | 121. Änderung abgewiesen, am Konto (AK-57); Lesewege bewusst ungedeckelt, blättern zu 12 |
| CSRF | bestanden | neun Varianten, je Meldung und unveränderte Daten (AK-56) |
| Offener Redirect / Kopfzeilen-Injektion über `ziel` | bestanden | `//evil.example/x`, `https://evil.example/`, `/\evil.example`, `/%5Cevil…`, `\r\nX-Injected`, `javascript:`, andere Sprache, `/de/profile`, `/de/admin`, POST-Route → jeweils Detailseite; `%0d%0a` bleibt kodiert; neu `Qa17PruefungTest::testZielIstKeinOffenerRedirect…` (8 Fälle) |
| PII in Logs | bestanden | `var/log/dev.log` ab Prüfbeginn: Listennamen/Notizen nur in `doctrine.DEBUG` (SQL-Parameter, nur dev); Produktion `dbal.logging: false`; DB-Fehler von BF-211 nennt die Werte nicht |
| PII an externe Dienste | bestanden | Umami: tatsächlicher Normalizer-Ausgang ohne Nummer/Titel/Namen (AK-64, AK-67); Sentry: Library-Prüflauf ohne Rumpf (AK-65), Adresse enthält nur Nummern; Brevo: kein Weg; Mail: nur die Löschbestätigung |
| Geheimnisse im Repository | bestanden | geänderte und neue Dateien nach Schlüsselmustern durchsucht: keine Treffer; keine neue `.env`/`.pem` |
| Eingaben | **BF-211, BF-212, BF-213** | Name/Notiz: leer, 1, 10 000 Zeichen (422), Emoji, `<script>` (escaped, auch im `<title>`), `'; drop table --`, `../../etc/passwd`, `%name% {0}` (wörtlich), ungültiges UTF-8 (422), Arrays statt Text (400/422/404), `ziel[]` (400) — kein 500 **außer** „İ“-Namen (BF-211), Listennummern > `PHP_INT_MAX` (BF-212), riesiges POST-`page` (BF-213) |
| Löschen | bestanden | Konto → 0 Zeilen in allen vier Tabellen (AK-61); Lokal → 0 Zeilen (AK-62); Liste → Einträge 0, Herz/Notiz bleiben (AK-30) |

## Fehler

### BUG-01 · Listenname aus „İ“ ergibt HTTP 500 — mittel (BF-211)

**Betrifft:** EC-07 (auch AK-17 bis AK-19, AK-23, AK-28 als Weg)
**Reproduktion:**
1. Angemeldet `/de/gespeichert` öffnen.
2. Bei „Neue Liste“ 31 × „İ“ (U+0130, türkisches großes I mit Punkt) eingeben, „Anlegen“.
3. Ebenso auf einer Detailseite unter „Neue Liste“ oder beim Umbenennen.
**Erwartet:** gespeichert oder 422 mit „Der Name darf höchstens 60 Zeichen lang sein.“ — nie ein Serverfehler (EC-07).
**Tatsächlich:** HTTP 500; `dev.log`: `DriverException … SQLSTATE[22001] … Data too long for column 'name_key'`. Der Name hat 31
Zeichen, `mb_strtolower("İ")` liefert aber zwei Codepunkte („i“ + U+0307) — der Schlüssel hat 62. Ab 31 „İ“ in einem
Namen bis 60 Zeichen (auch gemischt: ein Name mit 59 Zeichen und zwei „İ“ reicht).
**Ort:** `src/Saved/SavedText.php:30` (`nameKey`) gegen `name_key VARCHAR(60)` (`migrations/Version20261113221129.php:38`,
`src/Entity/SavedList.php:44`); geprüft wird nur die Länge des Namens (`SavedListService::pruefeName`, Zeile 185).
**Vorschlag:** Schlüssel mit `mb_convert_case($name, MB_CASE_LOWER_SIMPLE)` bilden (Länge bleibt gleich) oder die Länge des
Schlüssels mitprüfen. Reproduktion: `Qa17PruefungTest::testBf211…` (scharf mit `QA17_REPRO=1`).

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet). `SavedText::nameKey()` bildet den Schlüssel mit
> `mb_convert_case(…, MB_CASE_LOWER_SIMPLE)` — jeder Codepunkt wird zu genau einem, der Schlüssel ist so lang wie der Name.
> Migration und Spaltenbreite unverändert. Folge: „İstanbul“ und „Istanbul“ gelten als derselbe Name. Tests:
> `SavedTextTest::testBf211SchluesselHatFuerJedenCodepunktDieLaengeDesNamens` (alle 1 112 064 Codepunkte; rot: nur
> `U+0130`), `…::testBf211SechzigTuerkischeIErgebenEinenSchluesselVonSechzigZeichen` (rot: 120 statt 60);
> `Qa17PruefungTest::testBf211…` läuft jetzt immer (vorher übersprungen, scharf rot) und ist grün.

### BUG-02 · Listennummer jenseits von PHP_INT_MAX ergibt HTTP 500 — niedrig (BF-212)

**Betrifft:** AK-53/AK-55 sinngemäß (unbekannte Nummer → 404)
**Reproduktion:** angemeldet `GET /de/gespeichert/listen/99999999999999999999` (ebenso `/loeschen`, POST `umbenennen`,
`loeschen`, `…/lokale/99999999999999999999/entfernen`).
**Erwartet:** 404 wie jede unbekannte Liste. **Tatsächlich:** 500, `TypeError: SavedController::showList(): Argument #1 ($id)
must be of type int, string given`. In Produktion je Aufruf ein Sentry-Ereignis (`TypeError` wird nicht ignoriert).
**Ort:** `src/Controller/SavedController.php:124, 133, 178, 190, 222` (`requirements: '\d+'` mit `int $id`/`int $restaurant`).
**Vorschlag:** Anforderung auf `\d{1,9}` begrenzen. Reproduktion: `Qa17PruefungTest::testBf212…`.

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet). Alle fünf Routen verlangen
> `SavedController::NUMMER` = `\d{1,10}` für Listen- und Lokalnummer (deckt jede `INT`-Nummer bis 2 147 483 647, weit unter
> `PHP_INT_MAX`; dasselbe Muster prüft schon das Häkchen-Formular). Eine längere Zahl findet keine Route → 404. Tests:
> `SavedEdgeCasesTest::testBf212NummerJenseitsVonPhpIntMaxIstAufAllenFuenfWegen404` (sechs Anfragen, auch die drei POST-Wege;
> rot mit TypeError), `Qa17PruefungTest::testBf212…` läuft immer, grün. Die Herz-, Listen- und Notizwege der Detailseite
> (`RestaurantSavedController`) antworteten schon vorher mit 404 (Entity-Auflösung) — nachgemessen.

### BUG-03 · Riesige Seitenzahl beim „Aus Liste entfernen“ ergibt HTTP 500 — niedrig (BF-213)

**Betrifft:** EC-16 sinngemäß (Seitenzahl, nie ein Serverfehler)
**Reproduktion:** Listenseite öffnen, im Formular „Aus Liste entfernen“ `page=9223372036854775807` setzen, absenden (eigenes
gültiges Token nötig).
**Erwartet:** wie bei jeder Seite hinter dem Ende (404 oder geklemmt). **Tatsächlich:** 500, `TypeError:
SavedListEntryRepository::findRestaurantIdsPage(): Argument #2 ($offset) must be of type int, float given`; der Eintrag
bleibt bestehen.
**Ort:** `src/Controller/SavedController.php:229` (nur nach unten begrenzt) → `src/Saved/SavedCards.php:194`
(`($seite - 1) * 12` vor jeder Prüfung gegen die letzte Seite, über `position()`).
**Vorschlag:** Seite vor dem Rechnen gegen die letzte Seite klemmen. Reproduktion: `Qa17PruefungTest::testBf213…`.

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet). Neu `SavedCards::clampPage()`: `removeEntry()`
> begrenzt die Seitenzahl vor jedem Rechnen und vor jeder Adresse auf 1 … letzte Seite. Eine Seite hinter dem Ende verhält
> sich damit wie vorher eine knapp dahinter: Eintrag entfernt, 303 auf die letzte Seite mit Anker. Tests:
> `SavedEdgeCasesTest::testBf213RiesigeSeitenzahlBeimEntfernenFuehrtAufDieLetzteSeite` (rot: 500), `Qa17PruefungTest::testBf213…`
> läuft immer, grün.

### BUG-04 · Suche mit `?page=9223372036854775807` ergibt HTTP 500 — niedrig, vorbestehend in B05 (BF-214)

**Betrifft:** Regression nein (auf `main` gleicher Code); gefunden beim Vergleich für EC-16.
**Reproduktion:** `GET /de/restaurants?page=9223372036854775807` (auch als Gast).
**Erwartet:** 404 wie hinter dem Ende (BF-60). **Tatsächlich:** 500, `TypeError: QueryBuilder::setFirstResult(): Argument #1
must be of type ?int, float given`.
**Ort:** `src/Controller/RestaurantController.php:35` → `src/Repository/RestaurantRepository.php:188`.
**Vorschlag:** Seitenzahl vor der Abfrage nach oben begrenzen. Reproduktion: `Qa17PruefungTest::testBf214…`.

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet). `RestaurantController::index()` antwortet mit 404,
> bevor abgefragt wird, sobald `(page - 1) * LIMIT` keine ganze Zahl mehr wäre (`page > intdiv(PHP_INT_MAX, LIMIT)`); darunter
> greift wie bisher BF-60 nach der Abfrage (nachgemessen: `page=1537228672809129301` und `…302` → 404). Tests:
> `SavedPagesTest::testEc16UngueltigeSeitenzahlVerhaeltSichWieDieSuche` mit neuem Fall „größte ganze Zahl“ (rot: 500 statt
> 404), `Qa17PruefungTest::testBf214…` läuft immer, grün.

### BUG-05 · Aktiver Punkt der unteren Leiste 3,61 : 1 — mittel, vorbestehend in B25 (BF-215)

**Betrifft:** 02 (WCAG 1.4.3); seit diesem Feature auch der Punkt „Gespeichert“ auf allen Seiten des Bereichs.
**Reproduktion:** 375 px, beliebige Seite; axe `color-contrast` am Element mit `aria-current="page"` der unteren Leiste.
**Erwartet:** ≥ 4,5 : 1 (12 px). **Tatsächlich:** `text-cyan-600` `#0092b8` auf Weiß, **3,61 : 1** — auf jeder Seite, auf
`main` gleich.
**Ort:** `templates/partials/_bottom_nav.html.twig:26`.
**Vorschlag:** `text-cyan-700` (5,4 : 1). Nachweis: `qa/17/QA-axe-2026-11-14.txt`.

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet). Aktiver Punkt `text-cyan-700`; Hover der übrigen
> bleibt `cyan-600` (nicht im Befund). axe `color-contrast` bei 375 px auf 13 Gast- und 5 Kontoseiten: 0 Verstöße an der
> Leiste (vorher 1 je Seite). Test: `PwaTest::testAk11Ak12…` verlangt jetzt ` text-cyan-700` und verbietet ` text-cyan-600`
> (rot vor der Änderung). Ein Prüflauf misst keine Farbe — die Messung ist der axe-Lauf.

### BUG-06 · Kleine Schrift der Detailseite unter 4,5 : 1 — mittel, vorbestehend in B06 (BF-216)

**Betrifft:** 02 (WCAG 1.4.3), Detailseite.
**Reproduktion:** `/de/restaurants/12`, axe `color-contrast`: 13 Knoten.
**Tatsächlich:** Merkmalskacheln `text-xs text-green-600` `#00a63e` auf `#f0fdf4` **3,07 : 1**; „(heute)“ in den
Öffnungszeiten `text-xs text-purple-500` `#ad46ff` auf `#faf5ff` **3,83 : 1** (im Abschlussbericht des Baus als
Verifizierungs-Abzeichen bezeichnet — es ist die Öffnungszeit).
**Ort:** `templates/restaurant/show.html.twig:118, 127 …` (13 × `text-green-600`), `templates/partials/_opening_hours.html.twig:13`.
**Vorschlag:** `text-green-800` bzw. `text-purple-700`.

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet), in den beiden genannten Dateien vollständig:
> Merkmalskacheln `text-green-800` (13 Stellen), Maß unter dem Mindestwert `text-amber-800` (vorher `amber-600` auf `amber-50`,
> 3,08 : 1 — dieselbe Zeile, auf `/de/restaurants/13`, `16`, `19`), „(heute)“ `text-purple-700`, „Keine Öffnungszeiten
> angegeben“ `text-gray-600` (vorher `gray-400` auf Weiß, 2,6 : 1, Lokale 13, 17, 22). axe über alle elf Detailseiten bei
> 375/1280 px: **220 → 21 Knoten**; übrig sind die Wortmarke (16/OF-02) und die Gehweite in `_nearby_stops.html.twig`
> (`text-gray-400` auf `gray-50`, 2,48 : 1, 10 Knoten) — andere Datei, nicht im Befund, nicht geändert. Test:
> `KontrastKlassenTest::testBf216KleineSchriftDerDetailseiteOhneZuHelleFarben` (alle Lokale der Fixtures; rot vorher).

### BUG-07 · Rückverweis im Kopfband: Fokusrahmen unsichtbar — mittel (BF-217)

**Betrifft:** 02 (WCAG 2.4.7, 1.4.11); neue Vorlage dieses Features, weitere Stelle des offenen **BF-206**.
**Reproduktion:** Chromium 375 px, `/de/gespeichert/listen/<eigene>` (ebenso „Favoriten“), per Tab auf „← Gespeichert“ im
Verlaufsband, 600 ms warten (Übergang).
**Erwartet:** sichtbarer Rahmen (Vorlage setzt `focus:outline-white`). **Tatsächlich:** `solid 2px rgb(14,116,144)` — der
globale Fallback schlägt die Utility (BF-206); am Pixel **1,00 : 1** links, **1,16 : 1** oben gegen das Band
(`qa/17/QA-kopfband-rueckverweis-fokus.png`).
**Ort:** `templates/saved/_kopf.html.twig:25`, Ursache `assets/styles/app.css:98`.
**Vorschlag:** mit BF-206 beheben (globale Regel in `@layer base`); bis dahin hier wirkungslos.

> **Behoben 2026-11-14 mit BF-206** (`sdd-build`, Fehlerauftrag, nicht committet), an der Ursache: Die globale
> `:focus-visible`-Regel steht in `assets/styles/app.css` jetzt in `@layer base`; `_kopf.html.twig` unverändert. Am Pixel
> (Chromium, Tastaturfokus, Bild ohne/mit Fokus): Rückverweis **1,13 → 6,03 : 1** (1280 px) und **1,37 → 7,36 : 1** (375 px),
> „Favoriten“ und Listenseite. Fokus-Umfrage über 41 Seiten (Gast, Konto, Verwaltung) × 1280/375 px, 3 708 Elemente: **0
> Rückschritte, 56 Elemente neu sichtbar** (alle `focus:outline-white` auf Bändern), zwei Folgestellen ohne Dev-Daten per
> eingesetztem Markup gefunden und angepasst (Titelfoto, Sponsorenreihe — siehe Abschlussbericht in `tasks.md`). Test:
> `Qa16NachpruefungTest::testBf206GlobalerFokusFallbackLiegtInEinerKaskadenebene` läuft immer (vorher übersprungen, scharf
> rot), grün.

### Rest von BF-203 (Glas-Etikett, mit diesem Fehlerauftrag freigegeben)

> **Behoben 2026-11-14** (`sdd-build`, Fehlerauftrag, nicht committet). Neun Vorlagen mit zwölf Etiketten auf `bg-black/20`
> (Startseite, `/open` ×2, `/presse`, `/vergleich`, `/partner` ×2, `/organisationen`, Integritätsblock, `/sponsoren`, `/app`).
> Am Pixel (Weiß gegen jeden Pixel des Etikettengrunds, de/fr/pt × 1280/375/320 px, 108 Messungen): **vorher 2,95–4,21 : 1,
> jetzt 5,89–10,71 : 1**. `_hero_badges.html.twig` gemessen: Weiß auf `bg-white/20` 3,19–4,47 : 1 → mitgezogen auf
> `bg-black/20`, jetzt 6,48–8,87 : 1 (351 Messungen, Verlaufs-Kopf; Fassung mit Titelfoto nicht in den Dev-Daten).
> `docs/design-system.md` Komponente „Glas“ nachgezogen. Test: `KontrastKlassenTest::testBf203GlasEtikettLiegtAufDunklemGlas`
> (neun Seiten, rot vorher).

## Hinweise (keine Befunde)

- **H-1 · AK-06 ohne JavaScript:** Die Meldung „Gemerkt“ steht oben auf der Seite, der Sichtbereich bei der Karte — gesehen
  wird das gefüllte Herz, die Meldung erst beim Hochscrollen. Die Spec verlangt beides, nicht beides im Blick.
- **H-2 · AK-43 bei 768–1023 px:** Der Verweis zeigt nur das Herz-Symbol, „Gespeichert“ ist `sr-only lg:not-sr-only` (28 × 44
  px) — wie der Name neben dem Profilbild, der dort ebenfalls fehlt. Ab 1024 px steht der Text.
- **H-3 · Prüfer-Agent:** Eine abgewiesene Notiz/Häkchenänderung rendert die Detailseite unter `app_restaurant_note_save` bzw.
  `…_lists_save` — dort fehlen `no-store` und `turbo-cache-control` (`PrivatePages` kennt die Routen nicht). Nachgestellt in
  Chromium und WebKit (422 → Wegnavigieren → Abmelden → zweimal „Zurück“): nichts erscheint (Abmelden als Formular leert Turbos
  Speicher, der Verlaufseintrag ist die GET-Seite mit `no-store`). Ohne gemessene Wirkung; die zwei Routen in `PrivatePages`
  wären trotzdem die sichere Seite.
- **H-4 · AK-51 Schritt 2 und 5:** Nach „Anlegen“ steht „Gespeichert“ oben (Meldung und neue Liste sind dort), nach dem Filter
  die Suche oben — wie jeder Filter. Bei vielen Listen liegt das Feld unter dem ersten Bildschirm; der Betreiber entscheidet in
  seiner Abnahme, ob das „an den Anfang springen“ ist.
- **H-5 · Nullbyte:** Ein Listenname `a\0b` wird angenommen und gespeichert (keine Wirkung gemessen, Browser zeigen U+FFFD).
- **H-6 · Eingebaute Vorschau:** Ihr Dokument hat keinen Fokus; Fokusmessungen dort sind ungültig (alle Fokusangaben dieses
  Berichts aus Playwright). Ein `preview_click` löste dort einmal Merken **und** Entfernen aus — mit `button.click()` genau ein
  Absenden; als Werkzeugeffekt gewertet, nicht als Befund.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Saved/Qa17PruefungTest.php` | 15 (13 grün, 4 übersprungen) | AK-06 ohne JS (Seite 2, Filter, vierte Karte), AK-58 am Kontingent (abgewiesen/wirkungslos zählt nicht, kombiniert einmal), `ziel` ohne offenen Redirect und ohne Kopfzeilen-Injektion (8 Fälle), EC-01; Reproduktionen BF-211 bis BF-214 (scharf mit `QA17_REPRO=1`: 4 × rot) |
| `tests/Unit/Saved/Qa17SentryAnfragerumpfTest.php` | 2 | AK-65 an der echten `RequestIntegration` mit dem Produktionswert, Gegenprobe mit `medium` |

Browser-Prüfskripte (nicht im Projekt, unter `/tmp/qa17/bin`): `browser1.js` (Fokus, ohne JS, „Zurück“, Leiste, Kopfzeile,
Durchgang, offline), `zurueck_ohnejs.js`, `ak14.js`, `fokusregel.js`, `kopfband.js`, `axe.js`.

## Testdaten

In der Dev-Datenbank angelegt und nach der Prüfung entfernt: Konten `qa17a`…`qa17f@example.test` (die Kaskade nahm ihre
Favoriten, Listen und Notizen mit; `qa17e` wurde in AK-61 über die Anwendung gelöscht), Lokale Nr. 901–905 (Nr. 999 in
AK-62 über die Verwaltung gelöscht), die Löschbestätigung an `qa17e@example.test` in `messenger_messages`. Unverändert
geblieben sind die Daten des Baus an `user@endlech.lu`. Die eingebaute Vorschau war als `user@endlech.lu` angemeldet und ist
jetzt abgemeldet.

## Nächster Schritt

**`/sdd-deploy 17`** — mit T29 im Release-Commit (`RoadmapRegistry` ohne `favourites`, `ChangelogRegistry` + fünf
`changelog.*.yaml`; erst dann ist AK-70 prüfbar) und **Migration vor dem Code** (`Version20261113221129`, rein ergänzend; Weg
„einmaliger Pre-Deployment-Befehl“ aus `CLAUDE.md`). Vor dem Ausrollen beim Betreiber: AK-51 abnehmen, in Sentry nachsehen,
ob Ereignisse mit Anfragerumpf liegen (Abschlussbericht des Baus).

Empfohlen vorher, blockiert nicht: **`/sdd-build 17`** mit BF-211, BF-212, BF-213 und BF-217 (BF-217 zusammen mit BF-206),
danach `/sdd-qa 17`. BF-214 (B05), BF-215 (B25), BF-216 (B06) sind eigenständig.

Offen für den Betreiber: **AK-48** mit VoiceOver, **AK-63** in echtem Safari (WebKit-Engine bestanden), OF-01 bis OF-03 der
Spec (blockieren nicht).
