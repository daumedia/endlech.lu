# 12 · Onboarding für neue Nutzer — Testbericht

Stand: 2026-10-23 · **Dritte Nachprüfung** (vierter Durchlauf) nach dem dritten Fehlerauftrag (BUG-20 bis BUG-26,
BF-175 bis BF-181) · Geprüft gegen `spec.md` vom 2026-10-22 (AK-01 bis AK-68, EC-01 bis EC-10) · in eigener Session,
ohne Bau- oder Reparaturverlauf · Vorstufe `building`, alle vier Abschlussberichte am Ende von `tasks.md` als Suchliste
gelesen · Branch `feature/12-onboarding` (HEAD = `origin/main`, das ganze Feature liegt uncommittet im Arbeitsstand).
Archiv der früheren Durchläufe unter `qa/12/`: `qa-report-erster-durchlauf-2026-10-22.md`,
`qa-report-zweiter-durchlauf-2026-10-23.md`, `qa-report-dritter-durchlauf-2026-10-23.md` (Letzterer mit den
Behebungsvermerken des dritten Fehlerauftrags unter BUG-20 bis BUG-26).

**Prüfumgebung.** Wegwerf-Datenbank `endlech-sdd-testdb` (MySQL 8.0, Port 3309): `endlech` zu Beginn mit frisch geladenen
Fixtures, `endlech_test` für die Suite. Server aus demselben Arbeitsstand: `:8099` Entwicklung; `:8098` Entwicklung mit den
Upload-Grenzen des Produktionsbildes (`8M`/`10M`, vorgegeben); `:8097` Entwicklung mit `TRUSTED_PROXIES=127.0.0.1` und
Webhook-Geheimnis; `:8096` Entwicklung mit vier Arbeitsprozessen; `:8095` und `:8093` **Produktionsmodus** (Cache frisch
gebaut, Attrappen-Schlüssel `xkeysib-qa12vierteattrappe…`; `:8093` mit vier Arbeitsprozessen); `:8094` Entwicklung mit
Website-Kennung und einer Zählskript-**Attrappe**, die wie das echte `zaehler.js` `window.umami` beim Ausführen setzt und
jeden Aufruf an den Server meldet (das echte Skript zählt auf `127.0.0.1` wegen `data-domains` nichts). Eigene Router
unter `/tmp/endlech-f12/vierte/`. Treiber ohne JavaScript: `qa/12/nachpruefung3/treiber.php` (übernommen aus dem dritten
Durchlauf, Konten mit Präfix `…4-`), ein Cookie-Speicher je „Gerät", Zustand per SQL. Browser: Chromium-Vorschau,
1280 × 800 und iPhone SE, Turbo aktiv. Brevo nicht erreichbar, Mails an `null://null`; was an Brevo geht, ist am
HTTP-Client des echten `MarketingSyncService` gegen einen Brevo-Nachbau geprüft — Wettläufe diesmal auch mit **zwei
echten Prozessen** (Abgleich im CLI-Prozess, zweite Anfrage über den laufenden Webserver, `a17`). Nachweise unter
`qa/12/nachpruefung3/`.

## Fazit

**Production-ready: ja — mit drei Vorbehalten, die vor `/sdd-deploy 12` gelesen werden müssen.**

Der dritte Fehlerauftrag hat getragen. **Alle sieben Befunde aus der zweiten Nachprüfung sind behoben und gegengeprüft**,
die meisten auf einem zweiten, unabhängigen Weg: **BF-175** (*hoch*, B04) am laufenden Server — Adresswechsel mit Bild
bei erreichtem Deckel bleibt vorgemerkt, Anmeldung mit der neuen Adresse scheitert, kein Reset-Link dorthin; **BF-176**
am Server (ab dem vierten Wechsel abgewiesen, auch auf jeweils neue Adressen); **BF-177** und **BF-179** im Browser;
**BF-178 (a)** mit zwei echten Prozessen; **BF-180** am Server mit vier Arbeitsprozessen (0 verwaiste Dateien in zehn
Durchgängen, vorher bei 0 ms getroffen); **BF-181** über die scharf geschaltete Reproduktion. Alle früheren Befunde
BF-154 bis BF-174 bleiben behoben, BF-169 (Feature 04) bleibt offen. **55 von 68 Kriterien bestanden, keines
durchgefallen, 13 nicht prüfbar; Randfälle 9 von 10 bestanden, EC-09 durchgefallen** (Messhälfte). Volle Suite
**1550 Tests, 0 Fehlschläge**.

Kein offener Befund ist *hoch* oder *kritisch*. Neu sind sechs, alle nachgestellt, Reproduktionen in
`tests/Functional/Controller/Qa12Nachpruefung4Test.php`:

- **BF-185 (12/04, mittel)** — Scheitert im Abgleich die Übertragung einer Zeile (504/429), während das Konto abbestellt,
  schreibt der Lauf `failed` über den Sperrauftrag; die gesperrte Zeile wird nie wieder abgeholt. Mit zwei echten
  Prozessen nachgestellt: Profil „nicht abonniert", bei Brevo bleibt der Kontakt — unter der **alten** Adresse —
  ungesperrt. Der Restfall, den BF-181 schon benannte und den der dritte Auftrag nicht erreicht hat.
- **BF-182 (B04, mittel, vorbestehend)** — Ein Name aus Leerzeichen im Profil ergibt **HTTP 500** statt der Meldung
  (`'empty_data' => ''` fehlt; ein Browser schickt das ab, `required` gilt mit Leerzeichen als erfüllt).
- **BF-184 (B04, mittel, vorbestehend; aus dem `code-reviewer`)** — Ein Adresswechsel auf eine RFC-widrige oder über
  180 Zeichen lange Adresse ergibt **HTTP 500**; die RFC-widrige bleibt als Vormerkung stehen. Das Profil ist der fünfte
  Adresseingang, den BF-119 nicht nachgezogen hat.
- **BF-183 (12/11, niedrig)** — „Einführung begonnen" geht verloren, wenn man die Einführung über „Einführung ansehen"
  im Profil neu startet (Turbo-Besuch von einer Seite ohne Zählskript). Der Wiederholversuch aus BF-179 greift nur beim
  vollständigen Seitenaufruf. Deshalb **EC-09 durchgefallen**.
- **BF-186 (B04/12, niedrig; aus dem `code-reviewer`)** — „Entfernen" mit einer Entity, die beim Anfragebeginn noch
  kein Bild trug, löscht die inzwischen gespeicherte Datei und lässt den Verweis stehen (kaputtes Bild). Restfall von
  BF-180; dieselbe Lücke lässt bei einer Kontolöschung neben einem Upload eine Bilddatei liegen.
- **BF-187 (12/04, niedrig; aus dem `code-reviewer`)** — Drei Kommentare beschreiben noch die Regel, die BF-181
  abgeschafft hat („eine nie übertragene Zeile bleibt einfach gesperrt" — direkt über dem Code, der das Gegenteil tut).

**Die drei Vorbehalte:**

1. **Die Willkommens-Mail gibt es noch nicht.** T06 (Brevo-Automation, OF-04) ist offen; acht Kriterien (AK-40, AK-41,
   AK-43 bis AK-48) sind deshalb nicht prüfbar. Der Newsletter-Bildschirm und der Profiltext **versprechen** aber
   „darunter einmal eine Willkommens-Mail" (AK-31). Wird ausgeliefert, bevor T06 steht, ist dieser Satz falsch.
2. **Das Abnahmekriterium des Betreibers (AK-19, „unter zwei Minuten") ist nicht ausgeführt** — es braucht eine Person,
   die Endlech.lu nicht kennt. Ebenso offen: Brevos Seite von AK-33/35/36 (kein Kontozugang) und AK-67 (kein
   Screenreader; die Mechanik ist belegt).
3. **Mittlere Befunde bleiben offen**, keiner blockiert: BF-185 betrifft Werbung nach Widerspruch (selten, aber
   dauerhaft und ohne Alarm), BF-182/BF-184 sind Serverfehler im eigenen Profil. BF-175 (*hoch*) läuft **auf der
   Produktion weiter**, bis dieser Branch ausgerollt ist — das spricht für eine zügige Auslieferung.

**Nächster Aufruf:** `/sdd-klaeren 12` (T06/OF-04 vor der Auslieferung, dazu OF-03, OF-05, OF-06, H-1), danach
`/sdd-deploy 12`. Die offenen Befunde über `/sdd-build 12` (BF-183, BF-185, BF-187) und `/sdd-build B04` (BF-182,
BF-184, BF-186) — vor oder nach der Auslieferung, sie blockieren nicht. BF-169 über Feature 04.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 55 von 68 ausgeführt |
| davon bestanden | 55 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 13 |
| Edge Cases | 10 von 10 ausgeführt: 9 bestanden, **EC-09 durchgefallen** (BF-183) |
| Nachprüfung früherer Befunde | 28 (BF-154 bis BF-181): 27 behoben, BF-169 (Feature 04) offen |
| Tests neu geschrieben | 5 (eine Datei, `Qa12Nachpruefung4Test`): 4 Reproduktionen, 1 Gegenprobe |
| Tests grün | Gegenprobe grün; 4 von 4 Reproduktionen **rot**, solange der Befund offen ist (übersprungen; scharf `QA12_REPRO=1`) |
| Volle Suite gelaufen | **1550 Tests, 0 Fehlschläge**, 16 übersprungen (10 Ausgangsstand, BF-156 mit Begründung, BF-169, 4 neue Reproduktionen) |
| Regression: berührte Features | 11, davon Hauptweg bestanden 11 — B04 mit drei neuen, vorbestehenden Befunden abseits des Hauptwegs |

## Nachprüfung der früheren Befunde

Alle QA-Reproduktionen der drei früheren Durchläufe scharf geschaltet: `QA12_REPRO=1 php bin/phpunit` über
`Qa12OnboardingTest`, `Qa12NachpruefungTest`, `Qa12Nachpruefung3Test` → **24 Tests, 22 grün, BF-169 rot, BF-156
übersprungen** (mit Begründung, im Browser geprüft) (`reproduktionen-scharf.ausgabe.txt`).

**Zu den Änderungen des dritten Fehlerauftrags an den QA-Prüfungen** (die Vermerke unter BUG-23/BUG-26 und der dritte
Abschlussbericht nennen sie). Ich habe sie gegen einen Werkzeug-Abbild-Stand von vor dem Auftrag
(`refs/t3/checkpoints/…/turn/3`) verglichen; den Urstand von `Qa12Nachpruefung3Test` gibt es nicht mehr, nur die
damalige Fehlerausgabe (`qa/12/nachpruefung2/neue-befunde-scharf.ausgabe.txt`). Bewertung: **alle drei tragbar.**
- `Qa12Nachpruefung3Test::testBf178…InDerselbenSekunde…` prüft jetzt erst, dass die Vorbedingung „dieselbe Sekunde" nicht
  mehr eintritt, und dann — unverändert — dass die Aufhebung hinausgeht und Brevo entsperrt ist. Die Endaussage, um die
  es ging, ist geblieben.
- `Qa12OnboardingTest::testBf160…`: „nicht in der offenen Liste" stand stellvertretend für „wird nicht als Abonnent
  übertragen"; seit BF-181 bekommt jede Zeile einen Sperrauftrag. Die neue Prüfung erlaubt die Zeile nur als
  `blacklist_pending`. Dass dabei nichts angelegt wird, belegt `Feature12SperreSyncTest::testBf181Nie…` (genau ein
  `PUT {"emailBlacklisted":true}`, kein `POST`) — und am Server: Gustavs nie übertragene Zeile geht auf
  `blacklist_pending` (`a06`). AK-46 bleibt dadurch gewahrt, solange Brevos `PUT` nichts anlegt (H-10).
- `Feature12RegistryTest::testAk46…` (Bau-Test) folgt derselben Regeländerung.

| BF | Befund (kurz) | Ergebnis | Nachweis |
|---|---|---|---|
| BF-154 | Wieder-Einwilligung hebt Brevo-Sperre nicht auf | ✅ bleibt behoben | `testBf154…`, `testAk35…` scharf grün; `a17`: Aufhebung nach Wettlauf übertragen |
| BF-155 | unbestätigtes Konto nimmt fremde Einwilligung | ✅ bleibt behoben | scharf grün; Server: App-Warteliste mit Werbe-Einwilligung, unbestätigtes Fremdkonto abonniert + bestellt ab → Warteliste unverändert, Kontakt `app`/`synced` (`a06`) |
| BF-156 | B02: Anmeldung/Abmeldung nach Double-Submit | ✅ bleibt behoben | **Browser**, drei Wege (`bf156-bf164-browser.ausgabe.txt`), dev.log: „accepted using both origin and double-submit info" |
| BF-157 | Adresswechsel verwirft Sperrauftrag | ✅ bleibt behoben | scharf grün |
| BF-158 | Vorabladen zählt als Anzeige | ✅ bleibt behoben | scharf grün; Server: `X-Sec-Purpose: prefetch`, `Sec-Purpose: prefetch;prerender` → `shown` NULL (`a01`) |
| BF-159 | Upload über `post_max_size` ohne Meldung | ✅ bleibt behoben | `:8098`, 21,6 MB per curl: Einführung 422 „maximal 2 MB" am Feld; Profil 302 + Flash „Das Profilbild darf maximal 2 MB groß sein." (`a04b-curl.ausgabe.txt`) |
| BF-160 | drei Wechsel in einer Sekunde | ✅ bleibt behoben | scharf grün (Prüfung angepasst, s. o.) |
| BF-161 | „jetzt anmelden" nach AK-05 | ✅ bleibt behoben | 0 Treffer, „bestätigt" (`a01`) |
| BF-162 | CSRF-Fehler ohne Meldung | ✅ bleibt behoben | fremde Herkunft → 422 „Der CSRF-Token ist ungültig. …" (`a06`, `a08`) |
| BF-163 | Größenmeldung ohne Einheit | ✅ bleibt behoben | „maximal 2 MB" in Einführung und Profil (H-8 bei 2 000 001 B) |
| BF-164 | B04: Profil mit Bild nicht speicherbar | ✅ bleibt behoben | Browser: Klick und Enter speichern, Bild bleibt, keine verschachtelten Formulare |
| BF-165 | Sperr-Aufhebung bei jedem späteren Abgleich | ✅ bleibt behoben | zwei Reproduktionen scharf grün |
| BF-166 | Abmeldung während des Abgleichs | ✅ bleibt behoben | scharf grün. Restfall mit gescheiterter Übertragung: **BF-185** |
| BF-167 | Abmeldung nach gescheiterter Erstübertragung | ✅ bleibt behoben | scharf grün |
| BF-168 | Sperrauftrag nach fünf Fehlversuchen aufgegeben | ✅ bleibt behoben | scharf grün |
| BF-169 | 04: Adresswechsel auf Wartelisten-Adresse | ❌ **offen** | scharf **rot** (`synced` statt `removal_pending`) — nicht Teil des Auftrags |
| BF-170 | HEAD vermerkt Anzeige | ✅ bleibt behoben | `HEAD /de/einfuehrung` → 200, `shown` NULL, nächste Anmeldung weiter in die Einführung (`a01`) |
| BF-171 | „abgeschlossen" bei abgewiesenem Absenden | ✅ bleibt behoben | Server: ein Auslöser nach gelungenem Absenden, keiner nach Neuladen, fremder Herkunft (`a06`) oder Deckel (`a07`) |
| BF-172 | gleichzeitiges Erst-Abo → 500 | ✅ bleibt behoben | `:8096` und **`:8093` Produktionsmodus**, je fünf Durchgänge: Wettlauf (MySQL 1062) in 5 bzw. 3, je 2 × 302, eine Kontaktzeile, 0 × 500 (`a10-*`) |
| BF-173 | gleichzeitige Uploads → verwaiste Dateien | ✅ bleibt behoben | `:8096`, `:8093`: je fünf Durchgänge mit vier Uploads, je 1 neue Datei, 0 verwaist |
| BF-174 | Profiltext für Unbestätigte | ✅ bleibt behoben | Ulla unbestätigt: „Solange deine Adresse nicht bestätigt ist, …", danach der bisherige Satz (`a06`) |
| BF-175 | B04: Adresswechsel ohne Bestätigung über Bild-Upload | ✅ **behoben** | **Server**: drei Wechsel, vierter mit Bild → Flash „Zu viele Adressänderungen …", `email` unverändert, `pending_email` gesetzt; Anmeldung mit der neuen Adresse → `/de/login`, mit der alten gelingt; „Passwort vergessen" für die neue: kein Token (`a05-adresswechsel-mit-bild.ausgabe.txt`). Reproduktion scharf grün |
| BF-176 | B04: Deckel an der gewünschten Adresse | ✅ **behoben** | **Server**: acht Wechsel auf acht neue Adressen → 1–3 „Fast geschafft …", 4–8 „Zu viele Adressänderungen …" (`a05b`). Reproduktion scharf grün |
| BF-177 | „abgeschlossen" bei jeder Rückkehr aus dem Zwischenspeicher | ✅ **behoben** | **Browser**: abonniert → ein Ereignis; zweimal Restaurant öffnen + `history.back()` → kein weiteres (`ak50-messung-browser.ausgabe.txt`). Reproduktion grün |
| BF-178 | Wieder-Einwilligung während/zeitgleich mit der Sperrübertragung | ✅ **behoben** | (a) **zwei echte Prozesse**: während des Sperr-PUT im Profil wieder abonniert → nach dem Lauf `pending`, `synced_at` NULL, nächster Lauf entsperrt (`a17-wettlauf-abgleich.ausgabe.txt`); (a) und (b) scharf grün |
| BF-179 | „begonnen" geht bei vollem Seitenaufruf verloren | ✅ **behoben** | **Browser**: voller Aufruf `/de/einfuehrung` → `einfuehrung_begonnen` beim Server angekommen. ⚠ Restfall Turbo-Besuch aus dem Profil: **BF-183** |
| BF-180 | Entfernen neben einem Upload | ✅ **behoben** | **Server, vier Arbeitsprozesse**, zehn Durchgänge mit 0–250 ms Abstand: 0 verwaiste Dateien (`a13`). Reproduktion grün. Restfall mit leerem Entity-Namen: **BF-186** |
| BF-181 | Abbestellen ohne Sperrauftrag nach zurückgesetzter Spur | ✅ **behoben** | Reproduktion scharf grün; Server: jede abbestellte Zeile `blacklist_pending` (`a06`, `a07`). Restfall „`failed` überschreibt": **BF-185** |

## Akzeptanzkriterien im Einzelnen

`Treiber` = gegen den laufenden Server mit eigenem Cookie-Speicher je „Gerät", Zustand jeweils per SQL gelesen.
Ausgaben in `qa/12/nachpruefung3/` unter dem genannten Namen.

### Wann die Einführung erscheint — `a01-wann.ausgabe.txt`, `a01b-ak07.ausgabe.txt`, Browser

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | Frieda registriert, Link als Gast → `/de/login`, `onboarding_due=1`; erste Anmeldung **302 `/de/einfuehrung`**, „Schritt 1 von 4 Willkommen". **Browser**: Registrierung → Link → frisch geladene Anmeldeseite → Anmeldung → `/de/einfuehrung`, Fokus auf der Überschrift |
| AK-02 | ✅ | Gerät B nach der Anzeige → `/de/`, dritte Anmeldung `/de/`; Mia verlässt auf Schritt 2 → nächste Anmeldung `/de/` |
| AK-03 | ✅ | Gast auf `/de/community/suggest`, Anmeldung → **302 `/de/community/suggest`**, `shown` NULL; nächste Anmeldung → `/de/einfuehrung` |
| AK-04 | ✅ | Anna unbestätigt → `/de/`, `due=0`, `shown` NULL |
| AK-05 | ✅ | Anna angemeldet, eigener Link → **302 `/de/einfuehrung`**, bestätigt, `due=1`, „bestätigt" ja, „jetzt anmelden" 0 |
| AK-06 | ✅ | `user@`, `admin@` (`due=0`) je zweimal → `/de/` |
| AK-07 | ✅ | `unverified@` (Fixture, `due=0`): Link neu angefordert, als Gast geöffnet → `is_verified=1`, `due=1`; erste Anmeldung `/de/einfuehrung` |
| AK-08 | ✅ | `POST /api/v1/auth/register` → 201, Link `/lb/verify/…` → `due=1`, erste Website-Anmeldung `/de/einfuehrung` |
| AK-09 | ✅ | Profillink „Einführung ansehen" (`data-turbo-prefetch="false"`) → 200 „Schritt 1 von 4", `shown` unverändert (H-1) |
| AK-10 | ✅ | Gast: fünf Adressen → **302 `/de/login`**, 366 B ohne Inhalt; POST ebenso; unbekannter Schritt 404 |
| AK-11 | ✅ | Anna unbestätigt: vier GET, POST Newsletter, POST Upload → **302 `/de/verify`**, Konto unverändert |

### Ablauf — `a02-ablauf.ausgabe.txt`, Browser

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-12 | ✅ | „Weiter" führt `/de/einfuehrung` → `eintraege` → `profilbild` → `newsletter`, dort „Restaurants suchen" (Absendeknopf) → 302 `/de/restaurants`; im Browser ebenso |
| AK-13 | ✅ | Dora (Einwilligung bei der Registrierung): „Schritt 1/2/3 von 3", auf `profilbild` „Restaurants suchen", `/de/einfuehrung/newsletter` → 302 Suche; im Browser nach dem Abo ebenso („Schritt 2 von 3") |
| AK-14 | ✅ | „Schritt N von 4" bzw. „von 3" in Überschrift und `<title>`, fünf Sprachen |
| AK-15 | ✅ | vier Seitenwechsel: Kontozeile (ohne `shown`) und Kontaktzahl vorher = nachher; Upload ohne Datei → 302, nichts gespeichert; Newsletter ohne Häkchen → 302, nichts gespeichert |
| AK-16 | ✅ | „Zurück" ab Bildschirm 2 zeigt auf den vorigen Schritt (Fußleisten in `a02`; im Browser angeklickt) |
| AK-17 | ✅ | „Einführung beenden" auf jedem Bildschirm → `/de/restaurants`; letzter Bildschirm „Restaurants suchen" |
| AK-18 | ✅ | iPhone SE: Kopfzeile 81 px, untere Leiste mit vier Zielen je 90 × 60, kein waagerechtes Scrollen, auf allen vier Bildschirmen (`ak18-68-telefon.ausgabe.txt`, `ak18-profilbild-telefon.png`) |
| AK-19 | ⚠️ | **Abnahmekriterium mit einer Person, die Endlech.lu nicht kennt — keine solche Person verfügbar.** |

### Bildschirm 1 bis 3 — `a02`, `a03-begriffe`, `a04-upload`, `a04b-curl`, `ec02-abbruch`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-20 | ✅ | „Willkommen bei Endlech.lu, Olga Vierte!"; Knöpfe → `/de/community/suggest` und `/de/app` |
| AK-21 | ✅ | sichtbarer Text aller vier Bildschirme in fünf Sprachen auf die Wortstämme abgesucht (*bewert, kommentar, favorit, rating, review, avis, évalu, avalia, coment, …*); einziger Treffer frz. „comment" (= „wie") |
| AK-22 | ✅ | a–c in allen fünf Sprachen; (a) „Wann, steht auf der Restaurantseite" — die Detailseite eines verifizierten Lokals sagt „Geprüft am 15.01.2026."; d fehlt (OF-01 offen) |
| AK-23 | ✅ | „Verifiziertes Lokal", „Nicht verfügbar", „Nicht ausgemessen", „unter 90 cm" und ihre Entsprechungen stehen in fünf Sprachen auf der Einführung **und** auf den ausgelieferten Detailseiten 56, 57, 59, 60, 66 (20 × „ja") |
| AK-24 | ✅ | „Ein Eintrag zeigt, was wir erfasst haben, nicht was vor Ort gilt." und Entsprechungen |
| AK-25 | ✅ | `:8098`: JPG, PNG, WebP, 1,46 MB und **genau 2 000 000 B** → 302; je dieselbe Datei in Kopfzeile, Bildschirm und Profil; im Browser hochgeladen, „Dein Profilbild ist gespeichert." |
| AK-26 | ✅ | 2 000 001 B und 2,4 MB → 422, Meldung am Feld, `aria-invalid="true"`, `autofocus`, Bild unverändert, „Weiter" vorhanden; Profil mit derselben Meldung (curl). 21,6 MB (curl) → 422 „maximal 2 MB" |
| AK-27 | ✅ | Text als `text-bild.jpg`, PHP-Code als `shell.jpg`, GIF → 422 „Bitte lade ein Bild im Format JPG, PNG oder WebP hoch.", Bild unverändert; im Browser ebenso |
| AK-28 | ✅ | „Dein Bild erscheint nur in deinem Profil und oben in der Kopfzeile, solange du angemeldet bist. Andere sehen es nicht." in fünf Sprachen |
| AK-29 | ✅ | fünf Uploads nacheinander: angezeigt das neueste, im Verzeichnis eine Datei mehr als vorher (1 → 2, das eine Bild des Kontos); im Browser ersetzt ein zweiter Upload das erste |

### Bildschirm 4 und Profil — `a06-newsletter`, `a07-deckel`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ | ein Häkchen, ohne `checked`; im Browser `checked=false`, kein Attribut |
| AK-31 | ✅ | Text in fünf Sprachen: Neuigkeiten, einmal Willkommens-Mail, Abbestellen im Profil oder per Link, „Ohne Häkchen geschieht nichts." (⚠ Vorbehalt 1 im Fazit) |
| AK-32 | ✅ | Olga ohne Häkchen → 302 Suche, Einwilligung NULL, kein Kontakt |
| AK-33 | ⚠️ | **Anwendungsseite belegt, Brevo nicht erreichbar.** Hans unter `/fr/`: Einwilligung mit Zeitpunkt, Auftragsbuch `account`, `fr`, `pending`; Rumpf am HTTP-Client (`Qa12OnboardingTest::testAk33Ak40Ak56…`, grün): genau die Attribute aus 04/AK-07 |
| AK-34 | ✅ | Gustav: „Nicht abonniert" + „Newsletter abonnieren" → „Abonniert seit 23.10.2026" + „Newsletter abbestellen"; im Browser ebenso |
| AK-35 | ⚠️ | **Anwendungsseite belegt:** Profil unter `/fr/` → Kontakt `fr`, `pending`; Aufhebung einer Sperre im Rumpf (`testAk35…`, grün; `a17`). Ob Brevo danach beliefert, ist ohne Zugang nicht prüfbar |
| AK-36 | ⚠️ | **Anwendungsseite belegt:** „Nicht abonniert", Einwilligung NULL; Zeile → `blacklist_pending` (auch nie übertragen, seit BF-181); Sperr-PUT mit nur `{"emailBlacklisted":true}` (`testAk36…`). Brevos Zustellung nicht prüfbar; Randfall **BF-185** |
| AK-37 | ✅ | `:8097`: Clara „Abonniert seit …"; Webhook mit falschem Geheimnis → 401, unverändert; `unsubscribed` → 200, Einwilligung NULL, Profil „Nicht abonniert" |
| AK-38 | ✅ | Ulla unbestätigt abonniert: Einwilligung gespeichert, **kein** Kontakt, Hinweis auf die Bestätigung; Bestätigung unter `/pt/` → Kontakt `account`, `pt`, `pending` |
| AK-39 | ✅ | Konto ohne Einwilligung ändert den Namen → 302, gespeichert; Vorschlagen/Abstimmen ohne Einwilligung in der vollen Suite grün |

### Willkommens-Mail (Plattformeinstellung T06)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-40 | ⚠️ | **Keine Automation, kein Postfach** (T06 offen, OF-04). Anwendungsseite: `LOCALE` folgt der Anfrage (`fr` bei Hans und Gustav, `pt` bei Ulla) |
| AK-41 | ⚠️ | braucht die Mail; der Weg ab dem Webhook ist unter AK-37 belegt |
| AK-42 | ✅ | ohne Einwilligung entsteht weder Auftrag noch Kontakt (Olga, Gustav vor dem Abo); was nie bei Brevo ankommt, bekommt von dort keine Mail |
| AK-43 | ⚠️ | Bedingung `ORIGIN = ACCOUNT` in Brevo. Anwendungsseite: Wartelisten-Kontakt trägt `app` (`opfer4-…`) |
| AK-44 | ⚠️ | „Nur einmal" ist eine Einstellung der Automation. Anwendungsseite: Abbestellen sperrt statt zu löschen |
| AK-45 | ⚠️ | Einstellung der Automation |
| AK-46 | ⚠️ | Anwendungsseite: abbestellte Zeile bekommt den Sperrauftrag, eine nie übertragene erzeugt dabei nur einen `PUT` ohne Anlegen (`testBf181Nie…`); ob Brevos `PUT` wirklich nichts anlegt: H-10 |
| AK-47 | ⚠️ | Inhalt der Mail in Brevo |
| AK-48 | ⚠️ | Inhalt der Mail in Brevo; Attributliste der Anwendung unverändert (AK-56) |

### Verwaltung, Messung, Datenschutz — `a08`, `a08b`, `a09`, `ak50-messung-browser`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-49 | ✅ | `/de/admin`: Kachel „Einführung 10 / 21 — 21 Konten mit Einführung · 10 davon angezeigt", SQL 21 / 10, keine Links und kein `@` in der Kachel; als Nutzer 403 |
| AK-50 | ✅ | Browser mit Zählskript-Attrappe, beim Server mitgeschrieben: Willkommensseite (voll geladen und über Turbo nach der Anmeldung) → `einfuehrung_begonnen`; Absenden auf dem letzten Schritt → `einfuehrung_abgeschlossen`; „Einführung beenden" auf Schritt 2 → `einfuehrung_beendet {"schritt":"2"}`; „Weiter", Upload, Häkchen: nichts. ⚠ Neustart aus dem Profil zählt nicht: **BF-183** |
| AK-51 | ✅ | dieselben Aufrufe tragen nur Name bzw. `schritt`; Filter am Zählweg `Feature12UsageEventsTest` grün |
| AK-52 | ✅ | Profil: kein Zählskript, 0 Auslöser; im Browser abbestellt → kein Aufruf beim Server |
| AK-53 | ✅ | Migration `Version20261022203034`: genau `ADD onboarding_due TINYINT DEFAULT 0 NOT NULL, ADD onboarding_shown_at DATETIME DEFAULT NULL`; `schema:update --dump-sql` 12 Altlast-Anweisungen, keine zu `onboarding_*`/`marketing_contact` (Neufassung OF-05 vom Betreiber unbestätigt) |
| AK-54 | ✅ | Eingabefelder je Bildschirm (fünf Sprachen): Willkommen/Einträge keine, Profilbild `file`, Newsletter `checkbox` |
| AK-55 | ✅ | Produktionsmodus `:8095`: Konto „Rosa Geheimname", Upload „Rosa Privat Geheimname.jpg", Fehlupload „Rosa Zu Gross Geheimname.jpg", Abo, drei Wechsel, fremde Herkunft, 404 → Protokollausschnitt (72 Zeilen, nur Deprecations auf `info`) **0 Treffer** für Adresse, Name, Dateinamen, `@example.org`; keine Zeile ab `warning` (`a09-produktion.ausgabe.txt`). Gegenprobe mit einem Serverfehler im angemeldeten Zustand (BF-182, `a09b`): ebenfalls 0 Treffer |
| AK-56 | ✅ | Rumpf an Brevo ohne Angaben zur Einführung, zum Bild oder zum Weg der Einwilligung (`testAk33Ak40Ak56…`) |
| AK-57 | ✅ | Anna schickt Admin-Kennung und -Adresse an Newsletter-Schritt, Upload und Profilschalter: nur Annas Zeile ändert sich, Admin vorher = nachher |
| AK-58 | ✅ | fremde Herkunft: Upload und Newsletter-Schritt → 422 mit Meldung; Profilschalter mit falschem Token → Flash „Ungültiges CSRF-Token. …"; Konto vorher = nachher |
| AK-59 | ✅ | `:8097`, jede Anfrage mit eigener `X-Forwarded-For`: Wechsel 1–10 durch, **11. und 12. → „Zu viele Änderungen. Bitte versuch es in 60 Minuten erneut."**, Stand unverändert; Einführungsschritt danach ebenso abgewiesen (ohne Messauslöser). Dass `:8097` die Kopfzeile als Absender nimmt, zeigt der Deckel `registration` (5/Std je IP): über `:8097` liefen in diesem Durchlauf über 50 Registrierungen (`a07-deckel.ausgabe.txt`; 59 Konten mit Präfix `…4-` angelegt, 57 bestätigt) |
| AK-60 | ✅ | drei „Abbestellen" ohne Abo vorweg, danach trotzdem zehn Wechsel; EC-08 ebenso |
| AK-61 | ✅ | Ines (Einführung gesehen, Abo, Bild, Kontakt `synced`) löscht ihr Konto: Zeile weg, Kontakt `removal_pending`, Anmeldung scheitert. Bilddatei: Gegenprobe `a08b` mit `clearstatcache()` und `test -e` → **weg** (die Zeile „Datei vorhanden JA" in `a08` ist ein Artefakt des Treibers — PHPs Stat-Cache —, das Verzeichnis enthält die Datei nicht mehr) |
| AK-62 | ✅ | Export Dora: `onboardingDue: true`, `onboardingShownAt`, `marketingConsent: true`, `marketingConsentAt` = Datenbank |
| AK-63 | ✅ | Produktionsmodus mit untergeschobener Attrappe `xkeysib-qa12vierteattrappe…`: vier Bildschirme und Profil → 0 × Attrappe, 0 × `xkeysib-`, 0 × `api-key` |

### Sprachen und Zugänglichkeit — `ak66-67-tastatur.ausgabe.txt`, `lint-uebersetzung.ausgabe.txt`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-64 | ✅ | alle Bildschirme in lb, de, fr, en, pt gelesen; kein deutscher Satz (≥ 4 Wörter) wörtlich in einer anderen Sprache; pt ohne „você"; `debug:translation --only-missing` in fünf Sprachen leer |
| AK-65 | ✅ | Browser auf `/de/einfuehrung/newsletter`: Umschalter verweist auf `/{lb,fr,en,pt}/einfuehrung/newsletter`; FR → „Étape 4 sur 4 Newsletter", `lang="fr"` |
| AK-66 | ✅ | Browser (`document.hasFocus()` wahr), kein positives `tabindex`: Newsletter-Schritt Tab-Folge Überschrift → Häkchen → Zurück → Restaurants suchen → Einführung beenden, je Fokusrahmen 2 px, `:focus-visible` wahr; Häkchen per Leertaste, Absenden per Klick; Profilbild-Schritt: Upload per Enter auf „Bild hochladen" (Datei per Skript gesetzt — den Dateidialog bedient die Vorschau nicht) |
| AK-67 | ⚠️ | **Kein Screenreader in der Umgebung.** Belegt ist die Mechanik: nach jedem Turbo-Seitenwechsel liegt der Fokus auf `#einfuehrung-titel` („Schritt N von M" + Titel); nach abgewiesenem Upload auf dem Feld (`aria-invalid="true"`, `aria-describedby` = Hilfe + Fehler) |
| AK-68 | ✅ | iPhone SE: alle Knöpfe und Links ≥ 44 × 44 (kleinster „Weiter" 99 × 48, „Einführung beenden" 162 × 44, „Bild hochladen" 145 × 44); Häkchen 20 × 20 mit Label 212 × 68 (H-2) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | Frieda: Anzeige auf Gerät A, Anmeldung auf Gerät B → `/de/` |
| EC-02 | ✅ | `:8098`: Kopf 1,46 MB, nach 40 000 B Verbindung geschlossen (`qa/12/abbruch.php`) → Bild unverändert, Dateien 17 → 17, Bildschirm danach 200 mit „Weiter" (`ec02-abbruch.ausgabe.txt`) |
| EC-03 | ✅ | alle Abos dieses Durchlaufs ohne erreichbares Brevo: Erfolgsmeldung, Zeile `pending`; Wiederholung nach Fehlschlag `Feature12SperreSyncTest` grün |
| EC-04 | ✅ | `user@` angemeldet öffnet Janas Link → `/de/login`, `user@` bleibt angemeldet (Profil 200), Merker 0; Janas erste Anmeldung → `/de/einfuehrung` |
| EC-05 | ✅ | abgelaufener Link → 302 `/de/verify`, Konto unbestätigt, Merker 0 |
| EC-06 | ✅ | Rosi „angemeldet bleiben", nur das REMEMBERME-Cookie: `/de/restaurants`, `/de/`, `/de/profile` → 200, `shown` bleibt NULL |
| EC-07 | ✅ | Max: bestätigt, nie angemeldet, Passwort zurückgesetzt → erste Anmeldung mit neuem Passwort → `/de/einfuehrung` |
| EC-08 | ✅ | Theo: Tab 1 hat den Schritt offen, Tab 2 abonniert im Profil, Tab 1 setzt das Häkchen → Einwilligung unverändert, eine Kontaktzeile |
| EC-09 | ❌ | Verwaltungshälfte bestanden: Neustart über das Profil, `shown` und Kachel (10 / 21) unverändert (`a08`). **Messhälfte durchgefallen:** Profil (ohne Zählskript, Absicht) → „Einführung ansehen" (Turbo) → Willkommensseite, Auslöser nach 105 ms im DOM, `window.umami` erst nach ~200 ms, **kein** `einfuehrung_begonnen` in 3 s (`ak50-messung-browser.ausgabe.txt`, Punkt 4) → BUG-28 / BF-183 |
| EC-10 | ✅ | Otto löscht sich in Sitzung B; Sitzung A: nächster Schritt und Newsletter-Absenden → 302 `/de/login`, kein Kontakt, kein Konto |

## Regression

Volle Testsuite: `php bin/phpunit` → zu Beginn **1545 Tests, 9033 Assertions, 0 Fehlschläge, 12 übersprungen**
(`volle-suite.ausgabe.txt`), am Ende mit der neuen QA-Datei **1550 Tests, 9039 Assertions, 0 Fehlschläge,
16 übersprungen** (43 s, `volle-suite-ende.ausgabe.txt`). Gegenüber dem dritten Bericht (1542) gestiegen, nichts fehlt
(die sieben Reproduktionen von damals laufen jetzt scharf statt übersprungen). `npm run typecheck` und `npm run lint` ohne
Befund, `lint:twig` grün (133 Dateien), `lint:yaml` grün (63 Dateien) (`lint-uebersetzung.ausgabe.txt`).

Berührt: `HEAD` ist `origin/main`, das Feature liegt vollständig im Arbeitsstand (`git diff --name-only origin/main...HEAD`
→ 0 Dateien; das lokale `main` steht hinter `origin/main`). Gegenüber dem dritten Bericht kamen aus dem dritten
Fehlerauftrag `ProfileController::edit()` (B04), `AvatarUploadService::delete()` (B04, 01), `usage_event_controller.ts`
(Feature 11, **jeder** `on-connect`-Auslöser im Projekt), `_flash_messages.html.twig` und drei Klassen von Feature 04 hinzu.

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B01 | Bestätigungslink setzt Merker, neue Weiterleitung | ✅ | Browser: Registrierung → Link → Anmeldeseite „bestätigt"; Treiber: 59 Konten registriert, 57 bestätigt |
| B02 | Weiche nach der Anmeldung, CSRF-Controller sofort geladen | ✅ | Browser, drei Wege (BF-156); Treiber `user@` → `/de/` |
| B03 | Passkey-Weg durch dieselbe Weiche | ✅ | `POST /passkey/login/options` 200 (`a11`); `SecurityControllerTest`, `PasskeyControllerTest` grün |
| B04 | Profil, Bildregel, Adresswechsel, Bild entfernen, Löschformular | ✅ Hauptweg · ⚠ BF-182, BF-184, BF-186 | Browser: Name speichern mit Bild (Klick, Enter); Upload im Profil; Adresswechsel vorgemerkt (`a05`, `a05b`, Gegenprobe `Qa12Nachpruefung4Test`). Neu vorbestehend: BF-182, BF-184 (*mittel*), BF-186 (*niedrig*) |
| B11 | Ziel der Willkommensseite, gemerkter Rückweg | ✅ | `/de/community/suggest` 200 (Gast und angemeldet); AK-03 |
| B19 | Kachel | ✅ | `/de/admin`, `/de/admin/warteliste`, `/de/admin/restaurants` 200; als Nutzer 403 |
| 01 | Export, Löschung (geht durch `AvatarUploadService::delete()`) | ✅ | AK-61 (Bild weg, `a08b`), AK-62 |
| 04 | Auftragsbuch, Mapper, Client, Lauf | ✅ · ⚠ BF-185 | App-Warteliste mit Werbe-Einwilligung → Kontakt `app`; Webhook (AK-37); Marketing-Tests grün. Vorbestehend offen: BF-169 |
| 08 | Ziel der Willkommensseite | ✅ | Browser `:8094`: `/de/app` eingetragen, Erfolgsmeldung per Turbo-Stream, **ein** `warteliste_eingetragen {"liste":"app"}` (berührt durch den Wiederholversuch aus BF-179) |
| 10 | `SeoRegistry` | ✅ | `/sitemap.xml` 200, 155 Einträge, keine Einführungsseite |
| 11 | drei Ereignisse, Flash-Auslöser, Wiederholversuch beim `load` | ✅ · ⚠ BF-183 | AK-50 bis AK-52; Wartelisten-Ereignis (Zeile 08) |

`a11-regression.ausgabe.txt`: Startseite, Liste, Vorschlagen, `/de/app`, `/open.json`, `/api/v1/restaurants`, `/health` je
200 (die Detailseite 45 aus dem alten Skript ist nach dem Neuladen der Fixtures 404; die aktuellen 56–66 → 200, `a03`).

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | fremde Konto-ID und Adresse in drei Formularen ändern nichts (AK-57); unbestätigtes Konto erreicht die Einwilligung des Adressinhabers nicht (BF-155) |
| Rate Limit greift | bestanden | `newsletter_toggle`: 10 durch, 11. abgewiesen, am **Konto** bei wechselnder Absenderadresse (AK-59); `email_change` jetzt am Konto: 3 durch, 4.–8. abgewiesen, auch auf neue Adressen (BF-176) |
| PII in Logs | bestanden | Produktionsmodus: 0 Treffer nach vollem Durchlauf und nach einem Serverfehler im angemeldeten Zustand (AK-55, `a09b`) |
| PII an externe Dienste | bestanden | Brevo-Rumpf am HTTP-Client (AK-33/56); Sperrauftrag nur `{"emailBlacklisted":true}` an die `ext_id`; Zählaufrufe im Browser nur Name und `schritt` (AK-50/51) |
| Zugriffsregeln serverseitig | bestanden | Gast → Anmeldung (AK-10); unbestätigt → `/verify` auch für POST (AK-11); `/de/admin` als Nutzer 403; PUT/GET auf POST-Wege 405 (`a12`) |
| CSRF | bestanden | fremde Herkunft → 422 mit Meldung bzw. Fehlerflash, nichts geändert (AK-58); im Browser jede zustandslose Prüfung mit Herkunft **und** Double-Submit (BF-156) |
| Konto-Übernahme | bestanden | BF-175 am Server behoben: kein Weg, die Adresse ohne Bestätigungslink zu wechseln (mit Bild, mit erreichtem Deckel, Groß-/Kleinschreibung) |
| Eingaben | **BF-182, BF-184** (B04, vorbestehend) | Einführung: Upload mit falscher Endung, PHP-Code als `.jpg`, GIF, 2 000 001 B, 2,4 MB, 21,6 MB, abgebrochene Verbindung, Dateiname mit `../`, 200 × „ä", HTML — nie 500, stets unter erzeugtem Namen; Newsletter-Schritt mit leerem, langem, SQL-, Listen- und Zeichenkettenwert → nie 500; Profilschalter mit Listenwerten → 400 (`a12`). **Profil:** Name aus Leerzeichen → **500** (`a15`); fehlendes Namensfeld → 500 (`a04b-curl`); leere Adresse → 500 (`a15b`); Adresse `../../etc/passwd@example.lu` bzw. 181 Zeichen → **500** (`a16`) |
| Wettläufe | **BF-185**, **BF-186** | gleichzeitiges Abonnieren, Hochladen, Entfernen neben Hochladen: behoben (`a10`, `a13`); gescheiterter Abgleich neben Abbestellen mit zwei echten Prozessen: Sperre geht verloren (`a17`) |
| Löschen | bestanden | Konto, Merker, Datum, Einwilligung, Bilddatei weg, Brevo-Löschauftrag (AK-61, `a08b`) |
| Geheimnisse im Repository | bestanden | `git log --all -S 'xkeysib-'` trifft nur lokale Werkzeug-Abbilder (`refs/t3/checkpoints/*`, nicht auf `origin`); im Arbeitsstand nur Platzhalter und Attrappen (`geheimnisse-schema.ausgabe.txt`); Attrappe auf keiner Seite (AK-63) |

## Fehler

Die Nummern setzen die früheren Berichte fort (BUG-01 bis BUG-18 und BUG-20 bis BUG-26 sind behoben, BUG-19/BF-169 offen;
Einzelheiten und Behebungsvermerke im Archiv `qa/12/qa-report-dritter-durchlauf-2026-10-23.md`). Reproduktionen in
`tests/Functional/Controller/Qa12Nachpruefung4Test.php`, scharf: `QA12_REPRO=1 php bin/phpunit
tests/Functional/Controller/Qa12Nachpruefung4Test.php` (vier rot, `neue-befunde-scharf.ausgabe.txt`).

### BUG-27 · Profil: Name aus Leerzeichen ergibt HTTP 500 — mittel (BF-182, vorbestehend B04)

**Betrifft:** B04 (Stammdaten), Konvention „Die Prüfung gehört dorthin, wo der Wert hereinkommt"; berührt durch diesen
Branch (`ProfileController::edit()`), Ursache nicht von ihm
**Reproduktion** (`qa/12/nachpruefung3/a15-profil-leerer-name.php`, Server `:8099`; Test `--filter Bf182`):
1. Bestätigtes Konto, angemeldet, `/de/profile`.
2. Im Feld „Name" nur drei Leerzeichen eintragen (der Browser schickt ab — `required` ist mit Leerzeichen erfüllt),
   „Änderungen speichern".
**Erwartet:** 422 mit der Meldung aus `user.name_blank`.
**Tatsächlich:** **HTTP 500** „Expected argument of type "string", "null" given at property path "name"." Ebenso mit
leerem Feld (per Skript) und ohne das Feld im Rumpf. Name bleibt unverändert. Das Feld „E-Mail-Adresse" verhält sich
gleich (leer oder Leerzeichen → 500 an `email`, `a15b-profil-leere-adresse.ausgabe.txt`); dort hält der Browser
(`type="email"` kürzt Leerzeichen, `required`) es ab, erreichbar ist es nur mit einer selbst gebauten Anfrage.
**Ort:** `src/Form/ProfileType.php`, Feld `name` — `TextType` kürzt auf „" und gibt `null` an `User::setName(string)`.
**Vorschlag:** `'empty_data' => ''` an beiden Feldern (wie BF-51 in B20).

### BUG-28 · „Einführung begonnen" fehlt beim Neustart aus dem Profil — niedrig (BF-183)

**Betrifft:** EC-09 („zählt in Umami wieder als begonnen"), AK-50, US-07; Restfall von BF-179
**Reproduktion** (Browser `:8094` mit Zählskript-Attrappe, `ak50-messung-browser.ausgabe.txt` Punkt 4):
1. Angemeldet `/de/profile` voll laden — das Profil trägt bewusst kein Zählskript (Feature 11).
2. „Einführung ansehen" klicken (Turbo).
**Erwartet:** ein `einfuehrung_begonnen`.
**Tatsächlich:** keins. Der Auslöser steht nach 105 ms im DOM, `window.umami` gibt es erst nach ~200 ms — Turbo fügt das
Zählskript aus dem neuen `<head>` erst nach dem Rendern ein. Der Wiederholversuch aus BF-179 greift nur bei
`document.readyState !== 'complete'`, bei einem Turbo-Besuch ist der Zustand längst `complete`. Gleiches gilt für jeden
Turbo-Besuch von einer Seite ohne Zählskript (Profil, Verwaltung) auf eine Seite mit Auslöser.
**Ort:** `assets/controllers/usage_event_controller.ts#send`.
**Vorschlag:** fehlt `window.umami`, auf das `load`-Ereignis des Zählskript-Elements warten (oder kurz pollen mit
Obergrenze), nicht auf das `load` des Dokuments.

### BUG-29 · Profil: Adresswechsel auf RFC-widrige oder zu lange Adresse ergibt HTTP 500 — mittel (BF-184, vorbestehend B04)

**Betrifft:** B04 (BF-19-Weg), Muster BF-119 („alle vier Wege prüfen strikt" — das Profil ist der fünfte); Hinweis des
`code-reviewer`, am Server nachgestellt
**Reproduktion** (`a16-profil-adresse-rfc.php`, Server `:8099`; Test `--filter Bf184`):
1. Bestätigtes Konto, angemeldet, `/de/profile`.
2. Neue Adresse `../../etc/passwd@example.lu` (oder `vera4.123.@example.org`), „Änderungen speichern".
3. Zweiter Fall: eine Adresse mit 181 Zeichen.
**Erwartet:** 422 mit Meldung am Feld, nichts vorgemerkt.
**Tatsächlich:** (2) **HTTP 500** „Email … does not comply with addr-spec of RFC 2822"; `pending_email` bleibt auf der
abgewiesenen Adresse stehen, ein Platz im Deckel ist verbraucht, keine Mail geht hinaus. (3) **HTTP 500**
`SQLSTATE[22001] … Data too long` (Spalte `pending_email` VARCHAR(180)). Das Profil lässt den Vorgang danach abbrechen.
**Ort:** `src/Form/ProfileType.php` (`new Email(...)` im HTML5-Modus, kein `Length(max: 180)`);
`ProfileController::sendeAdressbestaetigung()` baut die Adresse (`->to()`) vor dem `try`, nach dem `flush()`.
**Vorschlag:** `Email(mode: Email::VALIDATION_MODE_STRICT)` und `Length(max: 180)` wie in den vier anderen Wegen; die
Adresse vor dem `flush()` konstruieren (Muster BF-119, zweite Hälfte).

### BUG-30 · Ein gescheiterter Abgleich überschreibt ein gleichzeitiges Abbestellen — mittel (BF-185)

**Betrifft:** AK-36, AK-46; Restfall von BF-181 („… oder ein gleichzeitiges Abbestellen von einem `failed` des Laufs
überschrieben wird"), Spiegelbild von BF-166 für den Fehlschlag; Hinweis des `code-reviewer`, nachgestellt
**Reproduktion** mit **zwei echten Prozessen** (`a17-wettlauf-abgleich.php`: Abgleich im CLI-Prozess gegen einen
Brevo-Nachbau, zweite Anfrage über den laufenden Webserver `:8099`; als Test `--filter Bf185`):
1. Konto abonniert, Lauf 1 legt den Kontakt bei Brevo an (`synced`).
2. Bestätigter Adresswechsel → Zeile `pending` (Neuübertragung nötig).
3. Lauf 2 überträgt; Brevo antwortet 504. **Während** der Antwort bestellt das Konto im Profil ab → Zeile
   `blacklist_pending`, `revoked_at` gesetzt, Profil „nicht abonniert".
4. Läufe 3 und 4.
**Erwartet:** Der Sperrauftrag bleibt und erreicht Brevo.
**Tatsächlich:** Nach Lauf 2 steht die Zeile auf `failed`, `attempts = 1`, gesperrt (`consent_at < revoked_at`); Läufe 3
und 4 schicken **keinen** Aufruf für sie. Bei Brevo bleibt der Kontakt ungesperrt — unter der **alten** Adresse, weil auch
der Adresswechsel nie ankam. Kein Alarm: Die Zeile erscheint in keiner Verwaltungsansicht.
**Ort:** `src/Marketing/MarketingSyncService.php::run()` (`flush()` am Ende schreibt `sync_state = 'failed'` über den
gleichzeitig gestellten Auftrag; der Nachtrag `abmeldungenWaehrendDesLaufsNachtragen()` sieht nur gelungene Zeilen),
`MarketingContact::markFailed()`, `MarketingContactRepository::findOpenForSync()` (lässt gesperrte `failed`-Zeilen aus).
**Vorschlag:** den Nachtrag auch für gescheiterte Zeilen laufen lassen (gesperrt + `failed` → `blacklist_pending`) oder
die Zustände des Laufs bedingt schreiben (`UPDATE … WHERE sync_state = :erwartet`). Siehe Muster „Rückweg einer
Abmeldung" in `befunde.md`: Die Invariante gilt auch nach einem Fehlschlag.

### BUG-31 · „Entfernen" mit leerem Entity-Namen lässt einen toten Verweis stehen — niedrig (BF-186, B04 / 12)

**Betrifft:** Restfall von BF-180/BF-173 (Missbrauchsschutz-Abschnitt der Spec: „jeder Upload ersetzt"), 01 (Löschung);
Hinweis des `code-reviewer`, nachgestellt
**Reproduktion** (Test `--filter Bf186`): Konto ohne Bild geladen; eine andere Anfrage speichert inzwischen ein Bild
(neue Datei, neuer Name in der Datenbank); „Profilbild entfernen" mit der beim Anfragebeginn geladenen Entity.
**Erwartet:** Datei weg **und** Verweis `NULL` — oder beides bleibt.
**Tatsächlich:** Die Datei wird gelöscht, `avatar_filename` zeigt weiter auf sie (Kopfzeile und Profil: kaputtes Bild).
`setAvatarFilename(null)` ist für Doctrine keine Änderung, der `flush()` schreibt nichts. Dieselbe Lücke in
`AccountDeleter::delete()` (prüft nur den Entity-Namen): Kontolöschung neben einem Upload lässt die neue Bilddatei liegen
(im Code nachvollzogen; `app:uploads:prune` räumt sie nur von Hand weg).
**Ort:** `src/Service/AvatarUploadService.php::delete()`, `src/Account/AccountDeleter.php:64`.
**Vorschlag:** den Verweis per `UPDATE user SET avatar_filename = NULL WHERE id = ?` setzen und die Entity angleichen;
im `AccountDeleter` den Namen aus der Datenbank mitprüfen bzw. immer `delete()` rufen.

### BUG-32 · Kommentare beschreiben die Regel, die BF-181 abgeschafft hat — niedrig (BF-187)

**Betrifft:** Wartbarkeit der Zustandsmaschine aus Feature 04/12; Hinweis des `code-reviewer`, im Code bestätigt
**Fundstellen:** `src/Marketing/MarketingContactRegistry.php`, Docblock von `revokeForAccount()`: „Deshalb bekommt eine
schon übertragene Zeile den Auftrag …" und „Eine nie übertragene Zeile bleibt einfach gesperrt: … `findOpenForSync()`
lässt gesperrte Zeilen ohne Auftrag draußen (AK-46)" — der Code darunter stellt den Auftrag für jede Zeile;
`src/Enum/MarketingSyncState.php` (`BLACKLIST_PENDING`: „… und der Kontakt schon einmal übertragen war");
`src/Marketing/MarketingSyncService.php`, Docblock von `abmeldungenWaehrendDesLaufsNachtragen()`: „Eine Abmeldung, die
erst NACH diesem Schreiben kommt, sieht `synced_at` und stellt den Auftrag selbst". `CLAUDE.md` ist dagegen richtig
nachgezogen (der Hinweis des Reviewers dazu war **nicht** zu bestätigen).
**Warum ein Befund:** Der Docblock steht direkt über dem reparierten Code und sagt das Gegenteil; wer ihm folgt, baut
BF-181 zurück — genau das Muster, vor dem `befunde.md` warnt.
**Vorschlag:** die drei Stellen auf „Sperrauftrag für jede bestehende Zeile außer Löschauftrag" ziehen.

### Weiter offen

**BUG-19 / BF-169 · Feature 04 · niedrig** — Adresswechsel auf eine Adresse, die schon als Wartelisten-Kontakt im
Auftragsbuch steht, lässt den Kontakt der alten Adresse stehen. Reproduktion scharf weiter rot.

## Hinweise (keine Befunde)

- **H-1** · AK-09 nennt den Link „Einführung erneut ansehen", gebaut ist „Einführung ansehen" — die Spec sollte nachziehen.
- **H-2** · Das Newsletter-Häkchen misst 20 × 20 px; sein Label (212 × 68) schaltet es mit.
- **H-3** · Der Schlüssel der Limiter ist die Adresse; Symfonys Lock-Kanal schreibt ihn auf DEBUG.
- **H-5** · „Einführung begonnen" zählt bei „Zurück" auf Bildschirm 1 erneut (im Browser bestätigt: zwei Ereignisse). Ob
  ein erneutes Anzeigen der Willkommensseite „begonnen" ist, sagt die Spec nicht; die Aussteigerzahl verzerrt es leicht.
- **H-6** · „Abonniert seit" zeigt das Datum in jeder Sprache als `d.m.Y`.
- **H-7** · Die CSRF-Meldung in der Einführung ist Symfonys Vorgabetext in Sie-Form, die Einführung duzt.
- **H-8** · Genau über der Grenze (2 000 001 B) lautet die Meldung „maximal 2000000 bytes groß" (Einführung und Profil).
- **H-9** · Eine Konto-Einwilligung schreibt einen bestehenden Wartelisten-Kontakt auf `ORIGIN=ACCOUNT` um (OF-03 / 04/OF-04).
- **H-10** · Ohne Brevo-Konto nicht prüfbar: ob `PUT …?identifierType=ext_id` für einen nie angelegten Kontakt wirklich nur
  404 liefert und nichts anlegt (seit BF-181 trägt AK-46 darauf), ob er einen nur per Adresse vorhandenen Altkontakt
  (Handimport, ohne `ext_id`) verfehlt (der Reviewer vermutet: dann bliebe dieser ungesperrt), und ob Brevo eine
  API-Sperre per Webhook als `unsubscribed` zurückmeldet.
- **H-11** · BF-166/BF-178 haben ein Restfenster (Anfrage liest vor dem Schreiben des Laufs und schreibt nach dessen
  Nachsehen), vom Bau selbst benannt; nicht nachgestellt.
- **H-12** · Sperraufträge werden ohne Grenze wiederholt; eine Verwaltungsanzeige für Konto-Kontakte gibt es nicht.
- **H-13** · Der Treiber (`HttpBrowser`) schickt Multipart-Rümpfe ohne `Content-Length`; dann greift
  `AvatarConstraints::bodyWasDiscarded()` nicht (200 ohne Meldung). Browser senden die Länge mit — per curl nachgeprüft.
- **H-14** · Ein abgeschickter Newsletter-Schritt mit beliebigem Wert im Feld `consent` gilt als Häkchen (`CheckboxType`).
- **H-15** · `blockByEmail()` (Webhook) hat keine Klammer wie BF-160 — nicht nachgestellt.
- **H-16** · *Vorbestehend:* `PruneOrphanUploadsCommandTest` löscht beim Suite-Lauf lokal hochgeladene Dateien der
  Entwicklungsumgebung.
- **H-17** · Ist der Deckel `email_change` erreicht, geht eine gleichzeitige Namensänderung ohne Hinweis verloren — außer
  es wird zugleich ein Bild hochgeladen, dann speichert der Upload-Dienst sie mit; die Meldung nennt nur den Deckel, das
  gespeicherte Bild nicht (`a05`, im Code nachvollzogen, vorbestehend B04).
- **H-18** · `consentMomentFor()` legt eine Wieder-Einwilligung bis zu einer Sekunde **hinter** die Uhr, wenn Sperre oder
  Übertragung in derselben Sekunde lagen; im Datenexport (AK-62) steht dann ein um eine Sekunde späterer Zeitpunkt.
- **H-19** · Im Produktionsmodus erscheinen ungefangene Ausnahmen nicht auf `stderr` (`request`-Kanal ist aus `main`
  ausgenommen, BF-23); sie erreichen nur Sentry. Für AK-55 günstig, für die Fehlersuche ohne Sentry nicht.

## Code-Review

Der `code-reviewer` las den Arbeitsstand (Schwerpunkt: die Änderungen des dritten Fehlerauftrags, dazu der übrige Code
des Features) ohne meine Ergebnisse als Vorgabe; er konnte nichts ausführen. Er meldete zwei „wichtige", drei mittlere
bis niedrige Punkte und fünf veraltete Kommentare. Verifiziert:
- **bestätigt und aufgenommen:** Fund 1 → **BF-184** (am Server: 500 bei RFC-widriger und bei 181 Zeichen langer
  Adresse); Fund 2 → **BF-185** (mit zwei echten Prozessen nachgestellt); Fund 3 → **BF-186** (Test, deterministisch);
  Kommentare → **BF-187** (drei von fünf Stellen bestätigt).
- **als Hinweis:** Fund 4 → H-17 (Deckel-Zweig verliert die Namensänderung); Fund 5 → H-10 (404 beim Sperren eines nur
  per Adresse vorhandenen Kontakts, „vermutet", ohne Brevo nicht prüfbar).
- **nicht bestätigt:** `CLAUDE.md` nenne noch die alte Bedingung aus BF-167 — der Abschnitt „Einführung für neue Konten"
  sagt richtig „für **jede** bestehende Zeile" (BF-181); der Kommentar zu `changeEmail()` (Fund unter „Kommentare") gehört
  zu BF-169 und ist dort schon erfasst.
- **als richtig bestätigt** (vom Reviewer, von mir an den genannten Stellen nachgelesen und durch die Läufe gedeckt):
  kein `flush()` zwischen `handleRequest()` und dem Zurücksetzen der Adresse (BF-175), Deckelschlüssel vor
  `handleRequest()` (BF-176), SQL beider Nachträge, Mapper-Bedingung, `data-turbo-temporary`, Reihenfolge Modul →
  Zählskript, `OnboardingRedirectSubscriber`, Migration.

Dazu ein eigener Fund außerhalb des Reviews: **BF-182** (beim Gegenprüfen der Profilmeldungen per curl, `a04b`), und
**BF-183** (beim Prüfen von EC-09 im Browser). Der dritte Bericht hatte EC-09 mit einem von Hand gesetzten
`window.umami` geprüft — das verdeckte genau diesen Fall.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/Qa12Nachpruefung4Test.php` | `testBf182…`, `testBf184…`, `testBf185…`, `testBf186…` | Reproduktionen; prüfen das richtige Verhalten, waren rot (`neue-befunde-scharf.ausgabe.txt`), überspringen sich bis zur Behebung. Scharf: `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12Nachpruefung4Test.php` |
| dieselbe | `testGegenprobeGueltigeEingabenImProfilLaufenWieBisher` | läuft immer: Name speichern und Adresse vormerken — damit eine Reparatur von BF-182/BF-184 den Hauptweg von B04 nicht mitnimmt |

BF-183 hat keinen Servertest (Zeitverhalten von Turbo und Zählskript im Browser); Nachweis im Browser.
Prüfskripte gegen den laufenden Server (keine Projekttests): `qa/12/nachpruefung3/treiber.php`, `a01` … `a17`,
`cookies.php`.

## Nächster Schritt

1. **`/sdd-klaeren 12`** — vor der Auslieferung **T06/OF-04** (Brevo-Automation; sonst verspricht der Newsletter-Text eine
   Willkommens-Mail, die nicht kommt), dazu OF-03, OF-05, OF-06 (Upload-Deckel), H-1, H-5.
2. **`/sdd-deploy 12`** — kein blockierender Befund. BF-175 (*hoch*) ist auf der Produktion offen, bis dieser Branch
   ausgerollt ist.
3. Nicht blockierend: **`/sdd-build 12`** mit BUG-28, BUG-30, BUG-32 (BF-183, BF-185, BF-187) und **`/sdd-build B04`** mit
   BUG-27, BUG-29, BUG-31 (BF-182, BF-184, BF-186) — je Befund den Aufruf `$this->offen('BF-…')` in
   `Qa12Nachpruefung4Test` entfernen und sonst nichts an der Prüfung ändern. BF-169 über Feature 04.
4. AK-19 braucht eine Person mit Stoppuhr, AK-40 bis AK-48 die eingerichtete Automation — beides vor oder nach der
   Auslieferung nachholen und hier eintragen.
