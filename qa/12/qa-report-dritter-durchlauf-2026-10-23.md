# 12 · Onboarding für neue Nutzer — Testbericht

Stand: 2026-10-23 · **Zweite Nachprüfung** (dritter Durchlauf) nach dem zweiten Fehlerauftrag (BUG-10 bis BUG-18,
BF-156, BF-164, BF-159-Profilhälfte) · Geprüft gegen `spec.md` vom 2026-10-22 (AK-01 bis AK-68, EC-01 bis EC-10) · in
eigener Session, ohne Bau- oder Reparaturverlauf · Vorstufe `building`, alle drei Abschlussberichte am Ende von
`tasks.md` als Suchliste gelesen · Branch `feature/12-onboarding` (setzt auf `feature/asset-mapper` auf), nicht
committet. Archiv der früheren Durchläufe: `qa/12/qa-report-erster-durchlauf-2026-10-22.md`,
`qa/12/qa-report-zweiter-durchlauf-2026-10-23.md` (mit den Behebungsvermerken des zweiten Fehlerauftrags).

**Prüfumgebung.** Wegwerf-Datenbank `endlech-sdd-testdb` (MySQL 8.0, Port 3309; `endlech` mit frisch geladenen Fixtures,
`endlech_test` für die Suite). Server aus demselben Arbeitsstand, je mit eigenem Router unter `/tmp/endlech-f12/`:
`:8099` Entwicklung (vorgegeben); `:8098` Entwicklung mit den Upload-Grenzen des Produktionsbildes
(`upload_max_filesize=8M`, `post_max_size=10M` — ein noch laufender Server des zweiten Durchlaufs, gleicher Router und
Arbeitsstand; mein eigener Start dort scheiterte an „Address already in use"); `:8097` Entwicklung mit
`TRUSTED_PROXIES=127.0.0.1` (wechselnde Absenderadresse über `X-Forwarded-For`) und lokalem Brevo-Webhook-Geheimnis;
`:8096` Entwicklung mit vier Arbeitsprozessen; `:8095` und `:8093` **Produktionsmodus** (`APP_ENV=prod`, `APP_DEBUG=0`,
Cache vorher frisch gebaut; `:8093` mit vier Arbeitsprozessen). Treiber ohne JavaScript: `qa/12/nachpruefung2/treiber.php`
(Symfony `HttpBrowser`, ein Cookie-Speicher je „Gerät", Weiterleitungen nicht verfolgt, Zustand per SQL), 49 Konten
über die echten Registrierungs- und Bestätigungswege angelegt. Browser: Chromium-Vorschau, 1280 × 800 und iPhone SE,
Turbo aktiv. Kein Zugang zu Brevo; Mails an `null://null`. Was an Brevo hinausgeht, ist am HTTP-Client des echten
`MarketingSyncService` gegen einen Brevo-Nachbau geprüft, der Sperren behält wie das Original. Nachweise unter
`qa/12/nachpruefung2/`.

## Fazit

**Production-ready: nein.**

Der zweite Fehlerauftrag hat getragen. **Alle zehn Befunde am Feature aus dem zweiten Durchlauf sind behoben und
gegengeprüft** (BF-165 bis BF-168, BF-170 bis BF-174), ebenso die drei vorbestehenden in B02/B04, die auf dem Hauptweg
lagen: **BF-156** im echten Browser auf allen drei Wegen, die vorher scheiterten; **BF-164** im Browser mit Bild; **BF-159**
jetzt auch im Profil. Die beiden Wettlauf-Befunde sind diesmal mit **gleichzeitigen Anfragen im Produktionsmodus**
nachgeprüft (BF-172: der Wettlauf fand in vier von fünf Durchgängen statt, MySQL-Fehler 1062, beide Antworten 302;
BF-173: 0 verwaiste Dateien). **55 von 68 Kriterien bestanden, keines durchgefallen, 13 nicht prüfbar, alle 10 Randfälle
belegt.** Volle Suite 1542 Tests, 0 Fehlschläge.

Blockierend ist **ein neuer, vorbestehender *hoher* Befund in B04, der in genau dem Code sitzt, den dieser Branch ändert**:

- **BF-175 (B04, hoch, auf der Produktion)** — ein Adresswechsel wird **ohne Bestätigung** wirksam, wenn im selben
  Absenden ein Profilbild hochgeladen wird und der Deckel `email_change` erreicht ist. `AvatarUploadService::upload()`
  ruft `flush()`, während `handleRequest()` die neue Adresse schon ins Konto geschrieben hat; der Deckel-Zweig in
  `ProfileController::edit()` kehrt zurück, ohne sie zurückzusetzen. Am laufenden Server: Anmeldung mit der alten
  Adresse scheitert danach, mit der neuen gelingt, „Passwort vergessen" schickt den Link an die neue. Das hebt **BF-19**
  auf (gekaperte Sitzung → dauerhafte Übernahme). Auf `main` gleich gebaut; dieser Branch **vergrößert die Reichweite**:
  mit BF-164 behoben lässt sich das Profil auch mit vorhandenem Bild wieder absenden, und die Einführung lädt genau dazu
  ein, eins hochzuladen.

Am eigenen Code dieses Features ist kein Befund *hoch* oder *kritisch*. Neu sind dort fünf *niedrige*, alle nachgestellt:
**BF-177** — die Reparatur von BF-171 zählt „Einführung abgeschlossen" bei **jeder** Rückkehr aus Turbos Zwischenspeicher
erneut (ein Absenden, vier Ereignisse); **BF-179** — umgekehrt geht „Einführung begonnen" bei einem vollständigen
Seitenaufruf verloren, etwa auf dem Weg aus AK-05 (Bestätigungslink im angemeldeten Browser); **BF-178** — eine
Wieder-Einwilligung während oder in derselben Sekunde wie die Übertragung einer Sperre erreicht Brevo nie; **BF-181** —
Abbestellen stellt keinen Sperrauftrag, wenn die Spur einer verlorenen Erstübertragung zurückgesetzt wurde (Restfall von
BF-167); **BF-180** — „Profilbild entfernen" neben einem gleichzeitigen Upload hinterlässt eine Datei ohne Konto (Restfall
von BF-173). Dazu vorbestehend in B04 **BF-176** (*mittel*): der Deckel `email_change` zählt an der gewünschten Adresse
statt am Konto. BF-169 (Feature 04, *niedrig*) bleibt offen.

Nicht prüfbar bleiben 13 Kriterien: acht hängen an der Brevo-Automation (T06 offen, OF-04), drei an Brevos Seite eines
bis zur Schnittstelle belegten Weges (AK-33, AK-35, AK-36), je eines an einer Person mit Stoppuhr (AK-19) und an einem
Screenreader (AK-67).

**Nächster Schritt:** `/sdd-build B04` mit BF-175 (dazu BF-176 und BF-180) — auf diesem Branch, weil die Reparatur dieselben zwei
Dateien trifft (`ProfileController::edit()`, `AvatarUploadService`); BF-175 betrifft laufenden Code und sollte auch
unabhängig von Feature 12 zügig ausgeliefert werden. Für dieses Feature `/sdd-build 12` mit BF-177 bis BF-181 (nicht
blockierend). Danach erneut `/sdd-qa 12`. Offen beim Betreiber (`/sdd-klaeren 12`): T06/OF-04 (Brevo-Automation), OF-03,
OF-05, OF-06 (Deckel für den Upload), dazu H-1.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 55 von 68 ausgeführt |
| davon bestanden | 55 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 13 |
| Edge Cases belegt | 10 von 10 |
| Nachprüfung früherer Befunde | 21 (BF-154 bis BF-174): 20 behoben, BF-169 (Feature 04) offen |
| Tests neu geschrieben | 7 (eine Datei, `Qa12Nachpruefung3Test`) — alle Reproduktionen |
| Tests grün | 7 von 7 Reproduktionen **rot**, solange der Befund offen ist (übersprungen; scharf `QA12_REPRO=1`) |
| Volle Suite gelaufen | **1542 Tests, 0 Fehlschläge**, 19 übersprungen (10 Ausgangsstand, BF-156 mit Begründung, BF-169, 7 neue Reproduktionen) |
| Regression: berührte Features | 11, davon Hauptweg bestanden 11 — B04 mit neuem, vorbestehendem Befund abseits des Hauptwegs (BF-175, BF-176) |

## Nachprüfung der früheren Befunde

| BF | Befund (kurz) | Ergebnis | Nachweis |
|---|---|---|---|
| BF-154 | Wieder-Einwilligung hebt Brevo-Sperre nicht auf | ✅ bleibt behoben | `Qa12OnboardingTest::testBf154…`, `Qa12NachpruefungTest::testAk35…` grün (`reproduktionen-scharf.ausgabe.txt`). Randfall in derselben Sekunde: BF-178 |
| BF-155 | unbestätigtes Konto nimmt fremde Einwilligung zurück | ✅ bleibt behoben | Reproduktion grün; am Server: App-Warteliste mit Werbe-Einwilligung, unbestätigtes Konto derselben Adresse abonniert und bestellt ab → Warteliste unverändert, Kontakt `synced` (`a06-newsletter`) |
| BF-156 | B02: Anmeldung/Abmeldung nach Double-Submit scheitert | ✅ **behoben** | **Browser**, drei Wege: Registrierung → Link → frische Anmeldeseite → Anmeldung → Einführung; Bild hochladen → Startseite frisch → Abmelden wirkt; anmelden → Profil → abmelden → Anmeldeseite frisch → Anmeldung gelingt. dev.log: jede Prüfung „accepted using both origin and double-submit info" (`bf156-browser.ausgabe.txt`) |
| BF-157 | Adresswechsel verwirft Sperrauftrag | ✅ bleibt behoben | Reproduktion grün |
| BF-158 | Turbo-Vorabladen zählt als Anzeige | ✅ bleibt behoben | Reproduktion grün; am Server GET mit `X-Sec-Purpose: prefetch` bzw. `Sec-Purpose: prefetch;prerender` → `shown` NULL |
| BF-159 | Upload über `post_max_size` ohne Meldung | ✅ **behoben (beide Hälften)** | `:8098`, 21,6 MB per curl: Einführung 422 mit „maximal 2 MB" am Feld; Profil 302 mit Fehlermeldung „Das Profilbild darf maximal 2 MB groß sein." (`a04-upload.ausgabe.txt`) |
| BF-160 | drei Wechsel in einer Sekunde | ✅ bleibt behoben | Reproduktion grün |
| BF-161 | „jetzt anmelden" nach AK-05 | ✅ bleibt behoben | 0 Treffer, Seite sagt „bestätigt" |
| BF-162 | CSRF-Fehler in der Einführung ohne Meldung | ✅ bleibt behoben | 422 mit `role="alert"`, Fokus auf der Meldung (Browser) |
| BF-163 | Größenmeldung ohne Einheit | ✅ bleibt behoben | „maximal 2 MB" in Einführung und Profil (H-8 bei 2 000 001 B) |
| BF-164 | B04: Profil mit Bild nicht speicherbar | ✅ **behoben** | Browser, Konto mit Bild: jeder Knopf in seinem Formular, Speichern per Klick und per Enter, Bild bleibt; „Profilbild entfernen" entfernt (`bf164-browser.ausgabe.txt`) |
| BF-165 | Sperr-Aufhebung bei jedem späteren Abgleich | ✅ **behoben** | `testBf165…` (zwei) scharf grün. Kehrseite der neuen Bedingung: BF-178 (b) |
| BF-166 | Abmeldung während des Abgleichs geht verloren | ✅ **behoben** | `testBf166…` scharf grün. Restfenster vom Bau benannt (H-11); Spiegelbild für die Wieder-Einwilligung: BF-178 (a) |
| BF-167 | Abmeldung nach gescheiterter Erstübertragung | ✅ **behoben** | `testBf167…` scharf grün. Restfall über einen zurückgesetzten Zähler: BF-181 |
| BF-168 | Sperrauftrag nach fünf Fehlversuchen aufgegeben | ✅ **behoben** | `testBf168…` scharf grün. Eine Verwaltungsanzeige für Konto-Kontakte gibt es weiter nicht; jeder Fehlschlag steht als `error` im Protokoll (H-12) |
| BF-169 | 04: Adresswechsel auf Wartelisten-Adresse | ❌ **offen** | `testBf169…` scharf **rot** (Kontakt der alten Adresse `synced` statt `removal_pending`) — nicht Teil des Auftrags |
| BF-170 | HEAD vermerkt Anzeige | ✅ **behoben** | `:8099`, fälliges Konto: `HEAD /de/einfuehrung` → 200, `shown` NULL, nächste Anmeldung weiter in die Einführung |
| BF-171 | „abgeschlossen" auch bei abgewiesenem Absenden | ✅ **behoben** | Server: genau ein Auslöser nach gelungenem Absenden, keiner bei Deckel, fremder Herkunft, Neuladen; Browser: abgewiesenes Absenden → kein Ereignis. ⚠ Neue Kehrseite: BF-177 |
| BF-172 | gleichzeitiges Erst-Abo → 500 | ✅ **behoben** | **Produktionsmodus, vier Arbeitsprozesse** (`:8093`), fünf Durchgänge: Wettlauf in vier (MySQL 1062), beide Antworten 302, eine Kontaktzeile, kein Fehler im Protokoll; ebenso `:8096` |
| BF-173 | gleichzeitige Uploads → verwaiste Dateien | ✅ **behoben** | `:8093` und `:8096`, je fünf Durchgänge mit vier gleichzeitigen Uploads: je eine neue Datei, 0 verwaiste (`a10-gleichzeitig-*.ausgabe.txt`). Restfall „Entfernen" neben einem Upload: BF-180 |
| BF-174 | Profiltext verspricht Unbestätigten adressweite Abmeldung | ✅ **behoben** | unbestätigt: „Solange deine Adresse nicht bestätigt ist, gilt Abbestellen nur für dieses Konto; …"; nach der Bestätigung der bisherige Satz |

## Akzeptanzkriterien im Einzelnen

`Treiber` = gegen den laufenden Server mit eigenem Cookie-Speicher je „Gerät", Zustand jeweils per SQL gelesen. Die
Ausgaben stehen in `qa/12/nachpruefung2/` unter dem genannten Dateinamen.

### Wann die Einführung erscheint — `a01-wann.ausgabe.txt`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | Frieda registriert, Link als Gast → `/de/login`, `onboarding_due=1`; erste Anmeldung **302 `/de/einfuehrung`**, „Schritt 1 von 4 Willkommen". Browser: Registrierung → Link → Anmeldung im selben Browser → Einführung, Fokus auf der Überschrift |
| AK-02 | ✅ | Gerät B nach der Anzeige → `/de/`, dritte Anmeldung `/de/`; Mia verlässt auf Schritt 2 → nächste Anmeldung `/de/` |
| AK-03 | ✅ | Gast auf `/de/community/suggest`, Anmeldung → **302 `/de/community/suggest`**, `shown` NULL; nächste Anmeldung → `/de/einfuehrung` |
| AK-04 | ✅ | Anna unbestätigt → `/de/`, `due=0`, `shown` NULL |
| AK-05 | ✅ | Anna angemeldet, eigener Link → **302 `/de/einfuehrung`**, bestätigt, `due=1`; 0 × „jetzt anmelden" |
| AK-06 | ✅ | `user@`, `admin@` (`due=0`) je zweimal → `/de/` |
| AK-07 | ✅ | `unverified@` (Fixture, `due=0`) fordert den Link neu an, öffnet ihn als Gast → `due=1`, erste Anmeldung `/de/einfuehrung` |
| AK-08 | ✅ | `POST /api/v1/auth/register` → 201, Link `/lb/verify/…` → `due=1`, erste Website-Anmeldung `/de/einfuehrung` |
| AK-09 | ✅ | Profillink „Einführung ansehen" (`data-turbo-prefetch="false"`) → 200 „Schritt 1 von 4", `shown` unverändert (H-1) |
| AK-10 | ✅ | Gast: fünf Adressen → **302 `/de/login`**, 366 B ohne Inhalt; POST ebenso; unbekannter Schritt 404 |
| AK-11 | ✅ | Anna unbestätigt: vier GET, POST Newsletter, POST Upload → **302 `/de/verify`**, Konto unverändert |

### Ablauf — `a02-ablauf.ausgabe.txt`, Browser

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-12 | ✅ | „Weiter" führt `/de/einfuehrung` → `eintraege` → `profilbild` → `newsletter`, dort „Restaurants suchen" (Absendeknopf) → 302 `/de/restaurants`; im Browser ebenso per Tastatur |
| AK-13 | ✅ | Dora (Einwilligung bei der Registrierung): „Schritt 1/2/3 von 3", auf `profilbild` „Restaurants suchen"; `/de/einfuehrung/newsletter` → 302 Suche |
| AK-14 | ✅ | „Schritt N von 4" bzw. „von 3" in Überschrift und `<title>`, fünf Sprachen |
| AK-15 | ✅ | vier Seitenwechsel: Kontozeile (ohne `shown`) und Kontaktzahl vorher = nachher; Upload ohne Datei → 302, nichts gespeichert; Newsletter ohne Häkchen → 302, nichts gespeichert |
| AK-16 | ✅ | „Zurück" ab Bildschirm 2 zeigt auf den vorigen Schritt |
| AK-17 | ✅ | „Einführung beenden" auf jedem Bildschirm → `/de/restaurants`; letzter Bildschirm „Restaurants suchen" |
| AK-18 | ✅ | iPhone SE: Kopfzeile 81 px, untere Leiste mit vier Zielen je 90 × 60, kein waagerechtes Scrollen, auf allen vier Bildschirmen (`ak18-68-telefon.ausgabe.txt`, `ak18-newsletter-telefon.png`) |
| AK-19 | ⚠️ | **Abnahmekriterium mit einer Person, die Endlech.lu nicht kennt — keine solche Person verfügbar.** Textumfang unverändert (≈ 270 Wörter) |

### Bildschirm 1 bis 3 — `a02`, `a03-begriffe`, `a04-upload`, `ec02-abbruch`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-20 | ✅ | „Willkommen bei Endlech.lu, Olga Dritte!"; Knöpfe → `/de/community/suggest` und `/de/app` |
| AK-21 | ✅ | sichtbarer Text aller vier Bildschirme in fünf Sprachen auf 19 Wortstämme abgesucht (*bewert, kommentar, favorit, rating, review, avis, évalu, avalia, coment, favori, notez, stern, étoile, estrela, bewäert, …*); einziger Treffer frz. „comment" (= „wie") |
| AK-22 | ✅ | a–c in allen fünf Sprachen; d fehlt (OF-01 offen) |
| AK-23 | ✅ | Katalogwerte „Verifiziertes Lokal", „Nicht verfügbar", „Nicht ausgemessen", „unter 90 cm" und ihre Entsprechungen stehen in fünf Sprachen auf der Einführung **und** auf den ausgelieferten Detailseiten 45, 46, 48, 49, 55 |
| AK-24 | ✅ | „Ein Eintrag zeigt, was wir erfasst haben, nicht was vor Ort gilt." und Entsprechungen |
| AK-25 | ✅ | `:8098`: JPG, PNG, WebP, 1,46 MB und **genau 2 000 000 B** → 302; je dieselbe Datei in Kopfzeile, Bildschirm und Profil; im Browser per Tastatur hochgeladen, „Dein Profilbild ist gespeichert." |
| AK-26 | ✅ | 2 000 001 B und 2,4 MB → 422, Meldung am Feld, `aria-invalid="true"`, `autofocus`, Bild unverändert, „Weiter" vorhanden; Profil wortgleich. 21,6 MB (über `post_max_size`, per curl) → 422 „maximal 2 MB" am Feld |
| AK-27 | ✅ | Text als `text-bild.jpg`, PHP-Code als `shell.jpg`, GIF → 422 „Bitte lade ein Bild im Format JPG, PNG oder WebP hoch.", Bild unverändert |
| AK-28 | ✅ | „Dein Bild erscheint nur in deinem Profil und oben in der Kopfzeile … Andere sehen es nicht." in fünf Sprachen |
| AK-29 | ✅ | fünf Uploads nacheinander: angezeigt das neueste, im Verzeichnis **eine** Datei (0 → 1); gleichzeitig: siehe BF-173 |

### Bildschirm 4 und Profil — `a06-newsletter`, `a07-deckel`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ | ein Häkchen, ohne `checked`; im Browser `checked=false` |
| AK-31 | ✅ | Text in fünf Sprachen: Neuigkeiten, einmal Willkommens-Mail, Abbestellen im Profil oder per Link, „Ohne Häkchen geschieht nichts." |
| AK-32 | ✅ | Olga ohne Häkchen → 302 Suche, Einwilligung NULL, kein Kontakt |
| AK-33 | ⚠️ | **Anwendungsseite belegt, Brevo nicht erreichbar.** Hans unter `/fr/`: Einwilligung mit Zeitpunkt, Auftragsbuch `account`, `fr`, `pending`; Rumpf am HTTP-Client (`Qa12OnboardingTest::testAk33Ak40Ak56…`, grün): `email`, `ext_id`, `listIds`, genau die fünf Attribute aus 04/AK-07 |
| AK-34 | ✅ | Gustav: „Nicht abonniert" + „Newsletter abonnieren" → „Abonniert seit 23.10.2026" + „Newsletter abbestellen" |
| AK-35 | ⚠️ | **Anwendungsseite belegt:** Profil unter `/fr/` → Kontakt `fr`, `pending`; Aufhebung einer Sperre im Rumpf (`testAk35…`, grün). Ob Brevo danach beliefert, ist ohne Zugang nicht prüfbar. Randfall BF-178 |
| AK-36 | ⚠️ | **Anwendungsseite belegt:** „Nicht abonniert", Einwilligung NULL; nie übertragene Zeile bleibt gesperrt ohne Auftrag, übertragene → `blacklist_pending`; Sperr-PUT mit nur `{"emailBlacklisted":true}` (`testAk36…`, grün). Brevos Zustellung nicht prüfbar; Randfall BF-181 |
| AK-37 | ✅ | `:8097`: Clara „Abonniert seit …"; Webhook mit falschem Geheimnis → 401, Einwilligung unverändert; `unsubscribed` → 200, Einwilligung NULL, Profil „Nicht abonniert" |
| AK-38 | ✅ | Ulla unbestätigt abonniert: Einwilligung gespeichert, **kein** Kontakt, „Wird wirksam, sobald du deine E-Mail-Adresse bestätigt hast."; Bestätigung unter `/pt/` → Kontakt `account`, `pt`, `pending` |
| AK-39 | ✅ | Konto ohne Einwilligung ändert den Namen → 302, gespeichert; Vorschlagen/Abstimmen ohne Einwilligung in der vollen Suite grün |

### Willkommens-Mail (Plattformeinstellung T06)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-40 | ⚠️ | **Keine Automation, kein Postfach** (T06 offen, OF-04). Anwendungsseite: `LOCALE` folgt der Anfrage (`fr` bei Hans und Gustav, `pt` bei Ulla) |
| AK-41 | ⚠️ | braucht die Mail; der Weg ab dem Webhook ist unter AK-37 belegt |
| AK-42 | ✅ | ohne Einwilligung entsteht weder Auftrag noch Kontakt (Olga, Gustav vor dem Abo); was nie bei Brevo ankommt, bekommt von dort keine Mail |
| AK-43 | ⚠️ | Bedingung `ORIGIN = ACCOUNT` in Brevo. Anwendungsseite: Wartelisten-Kontakt trägt `app` (`opfer3-…`) |
| AK-44 | ⚠️ | „Nur einmal" ist eine Einstellung der Automation. Anwendungsseite: Abbestellen sperrt statt zu löschen |
| AK-45 | ⚠️ | Einstellung der Automation |
| AK-46 | ⚠️ | Anwendungsseite: nie übertragene Zeile bleibt lokal gesperrt (Gustav), übertragene bekommt den Sperrauftrag; der Wettlauf aus BF-166 ist behoben. Restfall BF-181 |
| AK-47 | ⚠️ | Inhalt der Mail in Brevo |
| AK-48 | ⚠️ | Inhalt der Mail in Brevo; Attributliste der Anwendung unverändert (AK-56) |

### Verwaltung, Messung, Datenschutz — `a08`, `a09`, `ak50-messung-browser`

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-49 | ✅ | `/de/admin`: Kachel „Einführung 14 / 26 — 26 Konten mit Einführung · 14 davon angezeigt", SQL 26 / 14, keine Links und kein `@` in der Kachel; als Nutzer 403 |
| AK-50 | ✅ | Browser, `window.umami` durch einen Rekorder ersetzt: Willkommensseite → `einfuehrung_begonnen`; Absenden auf dem letzten Schritt → `einfuehrung_abgeschlossen` (über den Flash-Auslöser der Folgeseite); Link „Restaurants suchen" auf dem letzten Schritt (3 von 3) → `einfuehrung_abgeschlossen`; „Einführung beenden" auf Schritt 2 → `einfuehrung_beendet {"schritt":"2"}`; „Weiter", Upload, Häkchen: nichts. ⚠ Mehrfachzählung über Turbos Zwischenspeicher: BF-177; verloren bei vollständigem Seitenaufruf (Weg aus AK-05): BF-179 |
| AK-51 | ✅ | dieselben Aufrufe tragen nur Name bzw. `schritt`; Filter am Zählweg `Feature12UsageEventsTest` grün |
| AK-52 | ✅ | Profil: 0 Elemente `usage-event`, kein Zählskript; zwei Schalter-Wechsel → kein Ereignis |
| AK-53 | ✅ | Migration `Version20261022203034`: genau `ADD onboarding_due TINYINT DEFAULT 0 NOT NULL, ADD onboarding_shown_at DATETIME DEFAULT NULL`; `schema:update --dump-sql` 12 Altlast-Anweisungen, keine zu `onboarding_*` oder `marketing_contact` (Neufassung OF-05 vom Betreiber unbestätigt) |
| AK-54 | ✅ | Eingabefelder je Bildschirm (fünf Sprachen): Willkommen/Einträge keine, Profilbild `file`, Newsletter `checkbox` |
| AK-55 | ✅ | Produktionsmodus `:8095`: Konto „Rosa Geheimname", Upload „Rosa Privat Geheimname.jpg", Fehlupload „Rosa Zu Gross Geheimname.jpg", Abo, drei Wechsel, fremde Herkunft, 404 → Protokollausschnitt (72 Zeilen) **0 Treffer** für Adresse, Name, Dateinamen, `@example.org`; keine Zeile ab `warning` (`a09-protokoll.ausgabe.txt`) |
| AK-56 | ✅ | Rumpf an Brevo (AK-33) ohne Angaben zur Einführung, zum Bild oder zum Weg der Einwilligung (`testAk33Ak40Ak56…`) |
| AK-57 | ✅ | Anna schickt `user=<Admin-ID>`, `email=admin@…` (auch im Formularnamensraum) an Newsletter-Schritt, Upload und Profilschalter: nur Annas Zeile ändert sich, Admin vorher = nachher |
| AK-58 | ✅ | fremde Herkunft: Upload und Newsletter-Schritt → 422 mit Meldung; Profilschalter mit falschem Token → Flash „Ungültiges CSRF-Token. …"; Konto vorher = nachher |
| AK-59 | ✅ | `:8097`, jede Anfrage mit eigener `X-Forwarded-For`: Wechsel 1–10 durch, **11. und 12. → „Zu viele Änderungen. Bitte versuch es in 60 Minuten erneut."**, Stand unverändert; Einführungsschritt danach ebenso abgewiesen (ohne Messauslöser). Dass `:8097` die Kopfzeile als Absender nimmt, zeigt der Deckel `registration` (5/Std je IP): über `:8097` liefen in diesem Durchlauf weit über fünf Registrierungen (`a07-deckel.ausgabe.txt`) |
| AK-60 | ✅ | drei „Abbestellen" ohne Abo vorweg, danach trotzdem zehn Wechsel; EC-08 ebenso |
| AK-61 | ✅ | Ines (Einführung gesehen, Abo, Bild, Kontakt `synced`) löscht ihr Konto: Zeile weg, Bilddatei weg (`test -e`), Kontakt `removal_pending`, Anmeldung scheitert |
| AK-62 | ✅ | Export Dora: `onboardingDue: true`, `onboardingShownAt`, `marketingConsent: true`, `marketingConsentAt` = Datenbank |
| AK-63 | ✅ | Produktionsmodus mit untergeschobener Attrappe `xkeysib-qa12dritteattrappe…`: vier Bildschirme und Profil → 0 × Attrappe, 0 × `xkeysib-` |

### Sprachen und Zugänglichkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-64 | ✅ | alle Bildschirme in lb, de, fr, en, pt gelesen; kein deutscher Satz (≥ 4 Wörter) wörtlich in einer anderen Sprache; pt ohne „você"; `debug:translation --only-missing` in fünf Sprachen leer |
| AK-65 | ✅ | Browser auf `/de/einfuehrung/newsletter`: Umschalter verweist auf `/{lb,fr,en,pt}/einfuehrung/newsletter`; FR → „Étape 4 sur 4 Newsletter", `lang="fr"` |
| AK-66 | ✅ | Browser (`document.hasFocus()` wahr), nur Tastatur von der Willkommensseite bis zur Suche: Tab-Reihenfolge = DOM-Reihenfolge, kein positives `tabindex`; Upload über Enter auf „Bild hochladen" (Datei per Skript gesetzt — den Dateidialog bedient die Vorschau nicht), Leertaste aufs Häkchen, Enter auf „Restaurants suchen". Fokusrahmen 2 px `rgb(14,116,144)` bzw. Purpur, `:focus-visible` wahr (während der 150-ms-Übergangsanimation kurz weiß auf dem Hauptknopf) (`ak66-tastatur.ausgabe.txt`) |
| AK-67 | ⚠️ | **Kein Screenreader in der Umgebung.** Belegt ist die Mechanik: nach jedem Turbo-Seitenwechsel liegt der Fokus auf `#einfuehrung-titel` („Schritt N von M" + Titel); nach abgewiesenem Upload auf dem Feld (`aria-invalid`, `aria-describedby` = Hilfe + Fehler); bei CSRF-Fehler auf der `role="alert"`-Meldung |
| AK-68 | ✅ | iPhone SE: alle Knöpfe und Links ≥ 44 × 44 (kleinster „Weiter" 99 × 48, „Einführung beenden" 162 × 44, „Bild hochladen" 145 × 44); Häkchen 20 × 20 mit Label 212 × 68 (H-2) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | Frieda: Anzeige auf Gerät A, Anmeldung auf Gerät B → `/de/` |
| EC-02 | ✅ | `:8098`: Kopf 1,46 MB, nach 40 000 B Verbindung geschlossen (`qa/12/abbruch.php`) → Bild unverändert, Dateien 1 → 1, Bildschirm danach 200 mit „Weiter" |
| EC-03 | ✅ | alle Abos dieses Durchlaufs ohne erreichbares Brevo: Erfolgsmeldung, Zeile `pending`; Wiederholung nach Fehlschlag `Feature12SperreSyncTest` grün |
| EC-04 | ✅ | `user@` angemeldet öffnet Janas Link → `/de/login`, `user@` bleibt angemeldet (Profil 200), Merker 0; Janas erste Anmeldung → `/de/einfuehrung` |
| EC-05 | ✅ | abgelaufener Link → 302 `/de/verify`, Konto unbestätigt, Merker 0 |
| EC-06 | ✅ | Rosi „angemeldet bleiben", nur das REMEMBERME-Cookie: `/de/restaurants`, `/de/`, `/de/profile` → 200, `shown` bleibt NULL |
| EC-07 | ✅ | Max: bestätigt, nie angemeldet, Passwort zurückgesetzt → erste Anmeldung mit neuem Passwort → `/de/einfuehrung` |
| EC-08 | ✅ | Theo: Tab 1 hat den Schritt offen, Tab 2 abonniert im Profil, Tab 1 setzt das Häkchen → Einwilligung unverändert, eine Kontaktzeile |
| EC-09 | ✅ | Neustart über das Profil: `shown` und Kachelwert (14 / 26) unverändert; im Browser „begonnen" erneut |
| EC-10 | ✅ | Otto löscht sich in Sitzung B; Sitzung A: nächster Schritt und Newsletter-Absenden → 302 `/de/login`, kein Kontakt, kein Konto |

## Regression

Volle Testsuite: `php bin/phpunit` → **1542 Tests, 9006 Assertions, 0 Fehlschläge, 19 übersprungen** (38 s,
`volle-suite-ende.ausgabe.txt`). Zu Beginn dieser Prüfung: 1535 Tests, 0 Fehlschläge, 12 übersprungen
(`volle-suite.ausgabe.txt`) — die sieben neuen sind `Qa12Nachpruefung3Test`. Gegenüber dem zweiten Bericht (1524) gestiegen,
nichts fehlt. `npm run typecheck` und `npm run lint` grün (`frontend.ausgabe.txt`), `lint:twig` grün (133 Dateien),
`lint:yaml` grün, `lint:container` scheitert wie bisher am WebAuthn-Alias (vorbestehend, keine Datei dieses Features).

Berührt (Arbeitsstand gegen `HEAD`; `git diff --name-only main...HEAD` zeigt nur den AssetMapper-Umbau, auf dem der
Branch aufsetzt): wie im zweiten Bericht, dazu aus dem zweiten Fehlerauftrag `csrf_protection_controller.ts`
(**projektweit**, jedes Formular mit zustandslosem Token), `login.html.twig`, `base.html.twig`, `profile/index.html.twig`,
`_flash_messages.html.twig`, `AvatarUploadService`, `AvatarConstraints`, `ProfileController::edit()` und vier Klassen von
Feature 04.

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B01 | Bestätigungslink setzt Merker, neue Weiterleitung | ✅ | Browser: Registrierung → Link → Anmeldeseite „bestätigt"; Treiber: 49 Konten registriert und bestätigt |
| B02 | Weiche nach der Anmeldung, CSRF-Controller sofort geladen | ✅ | Browser, drei Wege (BF-156); Treiber `user@` → `/de/` |
| B03 | Passkey-Weg durch dieselbe Weiche | ✅ | `POST /passkey/login/options` 200; `SecurityControllerTest`, `PasskeyControllerTest` grün |
| B04 | Profil, Bildregel, Adresswechsel, Löschformular | ✅ Hauptweg · ⚠ BF-175, BF-176 | Browser: Name speichern mit Bild, Enter, Bild entfernen; Upload im Profil; Adresswechsel vorgemerkt (Eva, Ida). Neu vorbestehend: BF-175 (*hoch*), BF-176 |
| B11 | Ziel der Willkommensseite, gemerkter Rückweg | ✅ | `/de/community/suggest` 200 (Gast und angemeldet); AK-03 |
| B19 | Kachel | ✅ | `/de/admin`, `/de/admin/warteliste`, `/de/admin/restaurants` 200; als Nutzer 403 |
| 01 | Export, Löschung | ✅ | AK-61, AK-62 |
| 04 | Auftragsbuch, Mapper, Client, Lauf | ✅ | App-Warteliste mit Werbe-Einwilligung → Bestätigung → Kontakt `app`, `de`; Webhook (AK-37); Marketing-Tests grün. Vorbestehend: BF-169 |
| 08 | Ziel der Willkommensseite | ✅ | `/de/app` 200, Eintragung + Bestätigung |
| 10 | `SeoRegistry` | ✅ | `/sitemap.xml` 200, 155 Einträge, keine Einführungsseite |
| 11 | drei Ereignisse, Flash-Auslöser | ✅ | AK-50 bis AK-52; BF-177, BF-179 |

`a11-regression.ausgabe.txt`: Startseite, Liste, Detailseite, `/open.json`, `/api/v1/restaurants`, `/health` je 200.

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | fremde Konto-ID und Adresse in drei Formularen ändern nichts (AK-57); unbestätigtes Konto erreicht die Einwilligung des Adressinhabers nicht (BF-155) |
| Rate Limit greift | bestanden (Feature 12); **BF-176** (B04) | `newsletter_toggle`: 10 durch, 11. abgewiesen, am **Konto** bei wechselnder Absenderadresse (AK-59). Vorbestehend: `email_change` zählt an der gewünschten Adresse — acht Wechsel auf acht Adressen angenommen |
| PII in Logs | bestanden | Produktionsmodus: 0 Treffer nach vollem Durchlauf (AK-55); der jetzt gefangene Wettlauf (BF-172) schreibt nichts |
| PII an externe Dienste | bestanden | Brevo-Rumpf am HTTP-Client (AK-33/56); Umami-Aufrufe im Browser nur Name und `schritt` (AK-50/51) |
| Zugriffsregeln serverseitig | bestanden | Gast → Anmeldung (AK-10); unbestätigt → `/verify` auch für POST (AK-11); `/de/admin` als Nutzer 403; PUT/GET auf POST-Wege 405 |
| CSRF | bestanden | fremde Herkunft → 422 mit Meldung bzw. Fehlerflash, nichts geändert (AK-58); im Browser jede zustandslose Prüfung mit Herkunft **und** Double-Submit (BF-156) |
| Konto-Übernahme | **BF-175** (B04, vorbestehend) | Adresswechsel ohne Bestätigung über Bild-Upload bei erreichtem Deckel (`a05-adresswechsel-mit-bild.ausgabe.txt`) |
| Eingaben | bestanden | Upload: falsche Endung, PHP-Code als `.jpg`, GIF, 2 000 001 B, 2,4 MB, 21,6 MB, abgebrochene Verbindung, Dateiname mit `../`, 200 × „ä", HTML — nie 500, gespeichert stets unter erzeugtem Namen. Newsletter-Schritt mit leerem, langem, SQL-, Listen- und Zeichenkettenwert → nie 500; Profilschalter mit Listenwerten → 400 (`a12-eingaben.ausgabe.txt`) |
| Löschen | bestanden | Konto, Merker, Datum, Einwilligung, Bilddatei weg, Brevo-Löschauftrag (AK-61) |
| Geheimnisse im Repository | bestanden | `git log --all -S 'xkeysib-'` und Arbeitsstand: nur Platzhalter und Attrappen (`xkeysib-…`, `xkeysib-f12-pruefschluessel`, `xkeysib-qa12…attrappe`); die Treffer in `refs/t3/checkpoints/*` sind lokale Werkzeug-Abbilder, nicht auf `origin` (`geheimnisse.ausgabe.txt`); Attrappe erscheint auf keiner Seite (AK-63) |

## Fehler

Die Nummern setzen die früheren Berichte fort (BUG-01 bis BUG-18 sind behoben, BUG-19/BF-169 offen; Einzelheiten im
Archiv `qa/12/qa-report-zweiter-durchlauf-2026-10-23.md`).

### BUG-20 · Adresswechsel ohne Bestätigung über einen Bild-Upload bei erreichtem Deckel — hoch (BF-175, vorbestehend B04)

**Betrifft:** B04 (BF-19 „E-Mail-Änderung nur mit Bestätigung"); berührt durch diesen Branch (`ProfileController::edit()`,
`AvatarUploadService`, BF-164)
**Reproduktion** (`qa/12/nachpruefung2/a05-adresswechsel-mit-bild.php`, Server `:8099`; als Test
`QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12Nachpruefung3Test.php --filter Bf175`):
1. Bestätigtes Konto, angemeldet (eigene oder gekaperte Sitzung).
2. Im Profil dreimal die Adresse auf `uebernahme@…` ändern (je „Fast geschafft: … Bestätigungslink …", `email` bleibt).
3. Ein viertes Mal dieselbe Adresse eintragen **und** ein Profilbild wählen, „Änderungen speichern".
**Erwartet:** Flash „Zu viele Adressänderungen …", `email` unverändert (BF-19).
**Tatsächlich:** Flash erscheint, aber `user.email` steht in der Datenbank auf der **nie bestätigten** Adresse. Anmeldung
mit der bisherigen Adresse → zurück auf `/de/login`, mit der neuen → angemeldet; „Passwort vergessen" für die neue
Adresse erzeugt einen Reset-Link an sie. Das Konto bleibt `is_verified = 1`.
**Ort:** `src/Controller/ProfileController.php::edit()` — `$avatarService->upload()` steht vor der Adresslogik, der Zweig
`if (!$limiter->isAllowed()) { …; return …; }` setzt `$user->setEmail($bisherigeAdresse)` nicht;
`src/Service/AvatarUploadService.php::ersetzen()` ruft `$this->em->flush()` und schreibt damit die ganze Unit of Work samt
eingegebener Adresse. Auch im Normalfall steht die unbestätigte Adresse kurz in der Datenbank (zwischen den beiden
`flush()`).
**Vorschlag:** Die Adresse sofort nach `handleRequest()`/Validierung zurücksetzen (vor jedem Dienst, der schreibt), und der
Upload-Dienst schreibt nicht selbst (Muster `MarketingContactRegistry`: „Kein `flush()` — die Aufrufer stehen mitten in
einem eigenen Vorgang").

**Behoben 2026-10-23** (`/sdd-build 12`, dritter Fehlerauftrag; nicht ausgeliefert — **die Produktion trägt den Fehler weiter**): `ProfileController::edit()` setzt die Adresse unmittelbar nach der Validierung zurück, **vor** dem Upload und vor jedem anderen Schreiben; die gewünschte Adresse lebt nur noch in einer Variablen. Der Upload-Dienst schreibt weiter selbst — er findet aber keine fremde Änderung mehr in der Unit of Work. Test: `Qa12Nachpruefung3Test::testBf175…` (scharf, vorher rot), `ProfileControllerTest` unverändert grün.

### BUG-21 · Der Deckel `email_change` zählt an der gewünschten Adresse — mittel (BF-176, vorbestehend B04)

**Betrifft:** B04 (BF-21), Konvention „Am Konto zählen"
**Reproduktion** (`a05b-deckel-schluessel.php`; Test `--filter Bf176`): angemeldet acht Adresswechsel auf acht verschiedene
Adressen → alle acht „Fast geschafft …" (Grenze 3/Std).
**Erwartet:** ab dem vierten „Zu viele Adressänderungen …".
**Tatsächlich:** angenommen; je Absenden zwei Mails (Bestätigung an die neue, Warnung an die bisherige Adresse).
**Ort:** `ProfileController::edit()` — `ActionLimiter::for($this->emailChangeLimiter, $user->getUserIdentifier())` steht nach
`handleRequest()`; die Entity trägt da schon die Eingabe.
**Vorschlag:** am Konto zählen (`$bisherigeAdresse` oder die Kontokennung).

**Behoben 2026-10-23**: Schlüssel des Deckels ist die **bestätigte** Adresse (`$bisherigeAdresse`), gelesen vor `handleRequest()` — am Konto, nicht an der Eingabe (Konvention „Am Konto zählen“). Test: `testBf176…` (scharf, vorher rot).

### BUG-22 · „Einführung abgeschlossen" zählt bei jeder Rückkehr aus Turbos Zwischenspeicher — niedrig (BF-177)

**Betrifft:** AK-50, US-07 (Aussagekraft der Messung); Kehrseite der Reparatur von BF-171
**Reproduktion** (Browser, `ak50-messung-browser.ausgabe.txt`; Test `--filter Bf177`): Newsletter-Schritt absenden → Liste
mit einem Ereignis; ein Restaurant öffnen, „Zurück" → zweites Ereignis; zweimal wiederholt → vier Ereignisse für ein
Absenden. Später auf Schritt 2 „Einführung beenden" → `einfuehrung_beendet` **und** `einfuehrung_abgeschlossen` (Turbo
zeigt die Liste als Vorschau aus dem Zwischenspeicher, der Auslöser steht noch darin).
**Ort:** `templates/partials/_flash_messages.html.twig` — das `usage_event`-Element (`on-connect`) wird mit der Seite
zwischengespeichert. **Vorschlag:** `data-turbo-temporary` am Element (Turbo entfernt es vor dem Zwischenspeichern); H-5
(„begonnen") hat dieselbe Ursache.

**Behoben 2026-10-23**: `data-turbo-temporary` am `usage_event`-Element im Flash-Partial; „begonnen“ (H-5) hat ein eigenes, ebenso vorübergehendes Element auf der Willkommensseite. Test: `testBf177…` (scharf, vorher rot); im Browser: weiter per Turbo und „Zurück“ → weiterhin ein Ereignis (`qa/12/fehlerauftrag3/bf179-bf177-browser.ausgabe.txt`).

### BUG-23 · Wieder-Einwilligung während oder in derselben Sekunde wie die Sperrübertragung erreicht Brevo nie — niedrig (BF-178)

**Betrifft:** AK-35 (Randfall); Spiegelbild von BF-166, Sekunden-Muster von BF-160
**Reproduktion** (`--filter Bf178`, zwei Fälle):
(a) Konto abonniert, übertragen, abbestellt (`blacklist_pending`). Der Lauf lädt die Zeile und schickt die Sperre; **während**
des Aufrufs abonniert das Konto erneut (`record()` schreibt jüngere Einwilligung und `pending`). Der Lauf schreibt danach
`synced` darüber. (b) Ohne Gleichzeitigkeit: Der Lauf überträgt die Sperre in Sekunde S, das Konto abonniert noch in S;
nach dem Neuladen gilt `syncedAt == consentAt`.
**Erwartet:** Der nächste Lauf hebt die Sperre auf (`emailBlacklisted:false`, BF-154).
**Tatsächlich:** (a) Zeile `synced`, kein Auftrag offen; (b) der PUT trägt kein `emailBlacklisted`. Profil „Abonniert seit …",
Brevo gesperrt — die Einwilligung kommt nie an.
**Ort:** `src/Marketing/MarketingSyncService.php` (ein `flush()` je Lauf; der Nachtrag aus BF-166 sieht nur Abmeldungen),
`src/Marketing/MarketingPayloadMapper.php` (`$syncedAt < $consentAt` auf Sekunden).
**Vorschlag:** nach dem Lauf auch übertragene Zeilen mit inzwischen jüngerer Einwilligung wieder auf `pending` setzen
(bedingt geschrieben); im Mapper `<=` statt `<` oder ein ausdrücklicher Merker „Aufhebung übertragen".

**Behoben 2026-10-23**, beide Fälle: (a) Nach dem Schreiben eines Laufs sieht `MarketingSyncService` bei eben gesperrten Zeilen nach, ob inzwischen wieder eingewilligt ist (`consent_at > revoked_at`), und setzt sie dann auf `pending` mit leerem `synced_at` (bedingt auf `sync_state = 'synced'`, Spiegelbild des Nachtrags aus BF-166). (b) `consentMomentFor()` legt die neue Einwilligung hinter `max(revoked_at, synced_at)`; der Mapper bleibt bei `<` (mit `<=` käme BF-165 im Sekundenfall zurück). ⚠ Im zweiten Reproduktionstest ist die Vorbedingung „dieselbe Sekunde“ seither unmöglich; statt `markTestIncomplete` prüft er jetzt, dass sie nicht eintritt. Tests: `testBf178…` (beide scharf, vorher rot), `Feature12SperreSyncTest::testBf178…` (zwei neue).

### BUG-24 · „Einführung begonnen" geht bei einem vollständigen Seitenaufruf verloren — niedrig (BF-179)

**Betrifft:** AK-50 („Einführung begonnen = Willkommensseite angezeigt"), US-07; Hinweis des `code-reviewer`, nachgestellt
**Reproduktion** (`bf179-volles-laden.ausgabe.txt`): Server mit Website-Kennung, `/zaehler.js` durch eine Attrappe ersetzt, die
`window.umami` setzt und jeden Aufruf mitschreibt. Unbestätigtes Konto angemeldet, eigenen Bestätigungslink im selben Browser
öffnen (AK-05) → Willkommensseite, Auslöser im DOM, Zählskript ausgeführt — **kein** `einfuehrung_begonnen`. Ebenso bei jedem
direkten Aufruf oder Neuladen. Über Turbo (Anmeldung, Profillink) kommt es an.
**Ort:** `templates/base.html.twig` — das App-Modul (`importmap()`) steht vor dem `defer`-Zählskript; Stimulus verbindet im
Mikrotask nach dem Modul, `usage_event_controller.ts#send` findet `window.umami` nicht und kehrt still zurück.
**Vorschlag:** im Controller auf das Zählskript warten (etwa einmal bei `load` erneut versuchen), wenn `window.umami` fehlt.

**Behoben 2026-10-23**: Fehlt `window.umami`, versucht `usage_event_controller.ts#send` es **einmal** beim `load`-Ereignis erneut (nur solange `document.readyState !== 'complete'`; fehlt das Skript dann noch, wird nicht gezählt). Browser mit derselben Attrappe: voller Aufruf von `/de/einfuehrung` → `[["einfuehrung_begonnen", null, 87.7]]`, vorher `[]` (`qa/12/fehlerauftrag3/bf179-bf177-browser.ausgabe.txt`). Kein Servertest möglich.

### BUG-25 · „Profilbild entfernen" neben einem Upload hinterlässt eine Datei ohne Konto — niedrig (BF-180, B04 / 12)

**Betrifft:** Restfall von BF-173 (Missbrauchsschutz-Abschnitt der Spec: „jeder Upload ersetzt"); Hinweis des `code-reviewer`
**Reproduktion** (`a13-avatar-loeschen-gleichzeitig.ausgabe.txt`, Server mit vier Arbeitsprozessen; Test `--filter Bf180`):
Konto mit Bild, Upload und „Profilbild entfernen" aus zwei Sitzungen gleichzeitig → Konto `NULL`, die neue Datei bleibt ohne
Verweis liegen (bei 0 ms Abstand getroffen).
**Ort:** `src/Service/AvatarUploadService.php::delete()` — ohne die Sperre aus BF-173, Name aus der beim Anfragebeginn geladenen
Entity. **Vorschlag:** dieselbe Sperre, Name frisch aus der Datenbank.

**Behoben 2026-10-23**: `AvatarUploadService::delete()` läuft unter derselben Sperre je Konto wie `upload()` und löscht den Namen aus der Entity **und** den frisch aus der Datenbank gelesenen. Test: `testBf180…` (scharf, vorher rot), `AvatarUploadServiceTest` unverändert grün.

### BUG-26 · Abbestellen stellt keinen Sperrauftrag, wenn die Spur einer verlorenen Erstübertragung zurückgesetzt ist — niedrig (BF-181)

**Betrifft:** AK-36, AK-46 (Randfall); Restfall von BF-167; Hinweis des `code-reviewer`, nachgestellt
**Reproduktion** (`--filter Bf181`): abonniert; Brevo legt den Kontakt an, die Antwort geht verloren (`failed`, `attempts = 1`,
`synced_at` leer); ein bestätigter Adresswechsel setzt die Zeile auf `pending`, `attempts = 0`; danach — vor dem nächsten Lauf —
abbestellen. **Erwartet:** Sperrauftrag. **Tatsächlich:** keiner, die gesperrte `pending`-Zeile bleibt liegen, Brevo behält einen
ungesperrten Kontakt. Dieselbe Lage (im Code nachvollzogen, nicht nachgestellt) nach einem gescheiterten `flush()` am Ende eines
Laufs und wenn ein gleichzeitiges Abbestellen von einem `failed` des Laufs überschrieben wird.
**Ort:** `src/Marketing/MarketingContactRegistry.php::revokeForAccount()` (`$vielleichtBeiBrevo` aus `synced_at`/`attempts`/
Zustand); `record()` und `changeEmail()` setzen `attempts` zurück. **Vorschlag:** bei jedem Abbestellen einer bestehenden Zeile
den Sperrauftrag stellen (außer bei `REMOVAL_PENDING`) — 404 gilt ohnehin als Erfolg.

**Behoben 2026-10-23**: `revokeForAccount()` stellt für **jede** bestehende Zeile den Sperrauftrag (außer `REMOVAL_PENDING`); die Ableitung „vielleicht bei Brevo“ entfällt. AK-46 bleibt: Der Auftrag ist ein `PUT {emailBlacklisted:true}` auf die `ext_id`, eine 404 gilt als Erfolg, angelegt wird nichts (`Feature12SperreSyncTest::testBf181NieUebertragenerKontaktErzeugtNurDenSperraufruf`). ⚠ Zwei Tests nahmen das Gegenteil an und sind mit Begründung angepasst: `Feature12RegistryTest::testAk46…` (eigener) und die letzte Prüfung von `Qa12OnboardingTest::testBf160…` („nicht offen“ → „offen nur als Sperrauftrag“). Test: `testBf181…` (scharf, vorher rot).

### Weiter offen

**BUG-19 / BF-169 · Feature 04 · niedrig** — Adresswechsel auf eine Adresse, die schon als Wartelisten-Kontakt im
Auftragsbuch steht, lässt den Kontakt der alten Adresse stehen. Reproduktion scharf weiter rot.

## Hinweise (keine Befunde)

- **H-1** · AK-09 nennt den Link „Einführung erneut ansehen", gebaut ist „Einführung ansehen" — die Spec sollte nachziehen.
- **H-2** · Das Newsletter-Häkchen misst 20 × 20 px; sein Label (212 × 68) schaltet es mit.
- **H-3** · Der Schlüssel des Limiters ist die Adresse; Symfonys Lock-Kanal schreibt ihn auf DEBUG.
- **H-5** · „Einführung begonnen" zählt bei „Zurück" auf Bildschirm 1 und bei Turbos Vorschau erneut (Ursache wie BF-177).
- **H-6** · „Abonniert seit" zeigt das Datum in jeder Sprache als `d.m.Y`.
- **H-7** · Die CSRF-Meldung in der Einführung ist Symfonys Vorgabetext in Sie-Form („Versuchen Sie bitte …"), die Einführung duzt.
- **H-8** · Genau über der Grenze (2 000 001 B) lautet die Meldung „maximal 2000000 bytes groß" (Einführung und Profil).
- **H-9** · Eine Konto-Einwilligung schreibt einen bestehenden Wartelisten-Kontakt auf `ORIGIN=ACCOUNT` um (OF-03 / 04/OF-04).
- **H-10** · Ohne Brevo-Konto nicht prüfbar: ob `PUT …?identifierType=ext_id` für einen per Upsert über die Adresse gefundenen
  Altkontakt 404 liefert, und ob Brevo eine API-Sperre per Webhook als `unsubscribed` zurückmeldet.
- **H-11** · BF-166 hat ein Restfenster (Abmeldung liest vor dem Schreiben des Laufs und schreibt nach dessen Nachsehen),
  vom Bau selbst benannt; nicht nachgestellt.
- **H-12** · BF-168: Sperraufträge werden ohne Grenze wiederholt; eine Verwaltungsanzeige für Konto-Kontakte gibt es nicht,
  jeder Fehlschlag steht als `error` im Protokoll (in Produktion bei Sentry). Löschaufträge (Feature 04) behalten die Grenze
  von fünf Versuchen.
- **H-13** · Der Treiber (`HttpBrowser`) schickt Multipart-Rümpfe ohne `Content-Length`; dann greift
  `AvatarConstraints::bodyWasDiscarded()` nicht (200 ohne Meldung). Browser senden die Länge immer mit — mit curl
  nachgeprüft, kein Befund.
- **H-15** · `blockByEmail()` (Webhook) hat keine Klammer wie BF-160: Kommt die Abmeldung innerhalb der Sekunde Vorsprung, die
  `consentMomentFor()` einer Wieder-Einwilligung gibt, bleibt `consentAt > revokedAt` — nicht nachgestellt.
- **H-16** · *Vorbestehend, nicht Feature 12:* `tests/Integration/Command/PruneOrphanUploadsCommandTest` führt
  `app:uploads:prune --force` gegen das echte `public/uploads` mit der Test-Datenbank aus und löscht dabei jede lokal
  hochgeladene Datei der Entwicklungsumgebung (nachgestellt mit einer Markierungsdatei: nach `tests/Integration/Command/`
  weg). Für die Produktion folgenlos, lokal verwirrend — Bilder der Entwicklungs-DB zeigen danach ins Leere.
- **H-14** · Ein abgeschickter Newsletter-Schritt mit **beliebigem** Wert im Feld `consent` (auch leer oder „nein") gilt als
  Häkchen — Symfonys `CheckboxType`. Ein Browser schickt ein leeres Häkchen gar nicht; kein Befund.

## Code-Review

Der `code-reviewer` las die Änderungen des zweiten Fehlerauftrags (ohne meine Ergebnisse als Vorgabe) und meldete vier im
Code belegte, einen vermuteten und drei kleinere Punkte. Verifiziert:
- **bestätigt, schon gefunden:** BF-175 und BF-176 (Fund 1), BF-178 (Fund 3), BF-177 (Fund 4; die „begonnen"-Variante ist H-5).
- **bestätigt und aufgenommen:** BF-181 (Fund 2, Weg über den zurückgesetzten Zähler im Test nachgestellt), BF-179 (Fund 5 —
  „vermutet", im Browser nachgestellt), BF-180 (Fund 6, am Server mit vier Arbeitsprozessen nachgestellt).
- **nicht bestätigt:** Ein Adresswechsel nur in Groß-/Kleinschreibung melde den Nutzer ab — am Server: gespeichert, Profil danach
  200, Anmeldung mit kleiner Adresse gelingt (`a14-gross-klein.ausgabe.txt`; MySQL vergleicht ohne Groß-/Kleinschreibung).
- **als Hinweis:** H-12 (Sperraufträge ohne Grenze erzeugen bei ungültigem Schlüssel je Lauf und Zeile zwei Fehlerzeilen),
  H-15 (Webhook innerhalb der Sekunde Vorsprung aus `consentMomentFor()`).
- **als richtig bestätigt:** sofort geladener CSRF-Controller (Stimulus lädt den Zeichenketten-Export nicht als Controller),
  Klammerung in `findOpenForSync()`, 404 als Erfolg bei Sperre/Löschung, kein `flush()` nach dem geschlossenen EntityManager,
  Flash-Art `usage_event` nur aus der Konstante, CSRF auf allen neuen POST-Wegen, `LOCK_DSN=flock` (die Sperre aus BF-173 ist echt).

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/Qa12Nachpruefung3Test.php` | `testBf175…`, `testBf176…`, `testBf177…`, `testBf178…` (zwei), `testBf180…`, `testBf181…` | Reproduktionen; prüfen das richtige Verhalten, waren rot (`neue-befunde-scharf.ausgabe.txt`), überspringen sich bis zur Behebung. Scharf: `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12Nachpruefung3Test.php` |

Prüfskripte gegen den laufenden Server (keine Projekttests): `qa/12/nachpruefung2/treiber.php`, `a01`…`a12`.

## Nächster Schritt

1. **`/sdd-build B04`** mit BF-175 (*hoch*, auf der Produktion), BF-176 und BF-180 — auf diesem Branch, weil die Reparatur dieselben
   Dateien trifft; blockiert `/sdd-deploy 12`. BF-175 sollte unabhängig davon zügig ausgeliefert werden.
2. **`/sdd-build 12`** mit BUG-22 bis BUG-26 (BF-177 bis BF-181, nicht blockierend). Je Befund den Aufruf `$this->offen('BF-…')` in
   `Qa12Nachpruefung3Test` entfernen. BF-169 über den Fehlerauftrag von Feature 04.
3. **`/sdd-klaeren 12`** — T06/OF-04 (Brevo-Automation), OF-03, OF-05, OF-06 (Upload-Deckel), H-1.
4. Danach erneut **`/sdd-qa 12`**. AK-19 braucht eine Person mit Stoppuhr, AK-40 bis AK-48 die eingerichtete Automation.
