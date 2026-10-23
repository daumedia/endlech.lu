# 12 · Onboarding für neue Nutzer — Testbericht

Stand: 2026-10-23 · **Nachprüfung** nach dem Fehlerauftrag BUG-01 bis BUG-09 · Geprüft gegen `spec.md` vom 2026-10-22
(AK-01 bis AK-68, EC-01 bis EC-10) · in eigener Session, ohne Bau- oder Reparaturverlauf · Vorstufe `building`, beide
Abschlussberichte am Ende von `tasks.md` als Suchliste gelesen · Branch `feature/12-onboarding`, nicht committet.
Der erste Durchlauf vom 2026-10-22 mit den Behebungsvermerken liegt als Archiv unter
`qa/12/qa-report-erster-durchlauf-2026-10-22.md`.

**Prüfumgebung.** Wegwerf-Datenbank `endlech-sdd-testdb` (MySQL 8.0, Port 3309; `endlech` mit frisch geladenen Fixtures,
`endlech_test` für die Suite). Server aus demselben Arbeitsstand: `:8099` Entwicklung (vorgegeben), `:8098` Entwicklung
mit den Upload-Grenzen des Produktionsbildes (`upload_max_filesize=8M`, `post_max_size=10M`), `:8097` Entwicklung mit
`TRUSTED_PROXIES=127.0.0.1` (wechselnde Absenderadresse über `X-Forwarded-For`) und lokalem Brevo-Webhook-Geheimnis,
`:8096` Entwicklung mit vier Arbeitsprozessen (`PHP_CLI_SERVER_WORKERS=4`, für gleichzeitige Anfragen), `:8095` und
`:8093` **Produktionsmodus** (`APP_ENV=prod`, `APP_DEBUG=0`, `:8093` mit vier Arbeitsprozessen). Browser:
Chromium-Vorschau, 1280 × 800 und iPhone SE 375 × 667, Turbo aktiv. Kein Zugang zu Brevo; Mails an `null://null`.
Was an Brevo hinausgeht, ist am HTTP-Client des echten `MarketingSyncService` gegen einen Brevo-Nachbau geprüft, der
Sperren behält wie das Original. Nachweise unter `qa/12/nachpruefung/`.

## Fazit

**Production-ready: nein.**

Der Fehlerauftrag hat getragen: **Alle neun Befunde am Feature sind behoben** und am laufenden Server bzw. mit den
scharf geschalteten Reproduktionen des ersten Durchlaufs gegengeprüft (BF-154, -155, -157, -158, -160 bis -163; BF-159
in der Einführung). Die beiden früher durchgefallenen Kriterien halten: AK-26 bestanden, AK-35 auf der
Anwendungsseite belegt. **55 von 68 Kriterien bestanden, keines durchgefallen, 13 nicht prüfbar, alle 10 Randfälle
belegt.** Kein Befund am Feature ist mehr *hoch* oder *kritisch*.

Blockierend bleiben **die beiden vorbestehenden *hohen* Befunde auf dem Hauptweg dieses Features**, beide unverändert
offen und beide im Browser erneut nachgestellt:

- **BF-156 (B02)** reicht weiter als im ersten Bericht erfasst. Nicht nur „Registrierung → Link → Anmeldung" scheitert
  mit „Ungültiges CSRF-Token.", sondern jede frisch geladene Anmeldeseite in einer Sitzung, in der vorher irgendein
  Formular mit Double-Submit abgeschickt wurde — **auch die Formulare der Einführung**. Nachgestellt: Bild in der
  Einführung hochladen, Startseite neu laden, „Abmelden" → **bleibt lautlos angemeldet** (dev.log: *double-submit info
  was used in a previous request but is now missing*). Kürzester Weg ganz ohne Feature 12: anmelden, Profil öffnen,
  abmelden, Anmeldeseite neu laden → Anmeldung scheitert.
- **BF-164 (B04)** — mit Profilbild lässt sich das Profil nicht speichern (`<form>` im `<form>`). Die Einführung lädt
  genau dazu ein.

Neu gefunden: **zehn Befunde, keiner höher als *mittel*** (BUG-10 bis BUG-19 / BF-165 bis BF-174). Drei *mittlere*
betreffen denselben Weg wie die beiden hohen des ersten Durchlaufs — den **Rückweg einer Abmeldung nach Brevo** — und
haben Gewicht über die Zahl hinaus, weil eine Abmeldung, die Brevo nicht erreicht, Werbung nach einem Widerspruch
bedeutet (Art. 21 Abs. 3 DSGVO):

- **BF-165** — die Aufhebung einer Sperre (`emailBlacklisted: false`) geht nach einer Wieder-Einwilligung bei **jedem**
  späteren Abgleich mit und hebt dabei eine Abmeldung über den Brevo-Link auf, deren Webhook noch unterwegs ist. Genau
  das wollte design.md Entscheidung 6 verhindern.
- **BF-166** — eine Abmeldung, die eintrifft, **während** der Fünf-Minuten-Lauf den Kontakt gerade anlegt, geht
  verloren: Brevo hat danach einen ungesperrten Kontakt (der die Willkommens-Mail auslöst), lokal ist die Zeile gesperrt,
  kein Auftrag offen (AK-46).
- **BF-168** — ein Sperrauftrag, der fünfmal scheitert (z. B. abgelaufener API-Schlüssel), wird nie wieder versucht, und
  für Konto-Kontakte zeigt es keine Verwaltungsseite an.

Dazu sieben *niedrige*, darunter ein vorbestehender in Feature 04 (BF-169) und ein 500er bei gleichzeitigem
Erst-Abonnieren aus zwei Sitzungen (BF-172, im Produktionsmodus nachgestellt).

Nicht prüfbar bleiben 13 Kriterien: acht hängen an der Brevo-Automation (T06, OF-04), drei an Brevos Seite eines bis zur
Schnittstelle belegten Weges (AK-33, AK-35, AK-36), je eines an einer Person mit Stoppuhr (AK-19) und an einem
Screenreader (AK-67).

**Nächster Schritt:** `/sdd-build B02` (BF-156) und `/sdd-build B04` (BF-164, dazu die Profilhälfte von BF-159) —
**vor** `/sdd-deploy 12`. Für dieses Feature `/sdd-build 12` mit BUG-10 bis BUG-19 (BF-165 bis BF-174); BF-165, -166 und
-168 vor der Auslieferung empfohlen, obwohl sie formal nicht blockieren. Vorher `/sdd-klaeren 12` für BF-173 (Deckel
für den Upload, entgegen der Spec?) und die offenen Betreiberpunkte (T06/OF-04, OF-05, OF-03). Danach erneut
`/sdd-qa 12`.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 55 von 68 ausgeführt |
| davon bestanden | 55 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 13 |
| Edge Cases belegt | 10 von 10 |
| Nachprüfung früherer Befunde | 11 (BF-154 bis BF-164): 8 vollständig behoben, BF-159 in der Einführung behoben (Profilhälfte offen, B04), BF-156 und BF-164 offen |
| Tests neu geschrieben | 7 (eine Datei, `Qa12NachpruefungTest`) — 1 Beleg, 6 Reproduktionen |
| Tests grün | 1 von 1 Beleg; 6 von 6 Reproduktionen **rot**, solange der Befund offen ist (übersprungen) |
| Volle Suite gelaufen | **1524 Tests, 0 Fehlschläge**, 17 übersprungen (10 Ausgangsstand, BF-156, 6 neue Reproduktionen) |
| Regression: berührte Features | 11, davon Hauptweg bestanden 9 — B02 und B04 im Browser vorbestehend gestört (BF-156, BF-164) |

## Nachprüfung der Befunde aus dem ersten Durchlauf

| BF | Befund (kurz) | Ergebnis | Nachweis |
|---|---|---|---|
| BF-154 | Wieder-Einwilligung hebt Brevo-Sperre nicht auf | ✅ behoben | `QA12_REPRO=1 … testBf154…` grün (`qa/12/nachpruefung/reproduktionen-scharf.ausgabe.txt`); neuer Beleg `Qa12NachpruefungTest::testAk35WiederEinwilligungHebtDieSperreAuf`: der PUT trägt `emailBlacklisted:false`, Nachbau entsperrt. ⚠ Neue Kehrseite: BF-165 |
| BF-155 | unbestätigtes Konto nimmt fremde Einwilligung zurück | ✅ behoben | Reproduktion grün; am Server: Warteliste `opfer@example.org` mit Einwilligung + Kontakt `synced`, unbestätigtes Konto derselben Adresse abonniert und bestellt ab → Warteliste-Einwilligung **unverändert**, Kontakt `synced`, `revoked_at` NULL (`ak30-38-newsletter.ausgabe.txt`). Kehrseite: BF-174 |
| BF-156 | B02: Anmeldung nach Double-Submit scheitert | ❌ **offen** | Reproduktion scharf rot; im Browser erneut nachgestellt, Auslöser breiter (siehe oben, `bf156-browser.ausgabe.txt`) |
| BF-157 | Adresswechsel verwirft Sperrauftrag | ✅ behoben | Reproduktion grün; am Server: `blacklist_pending` → Adresswechsel bestätigt → Zeile unter neuer Adresse weiter `blacklist_pending` |
| BF-158 | Turbo-Vorabladen zählt als Anzeige | ✅ behoben | Reproduktion grün; im Browser: Konto nach AK-03 fällig, `mouseenter` auf Kontrolllink → Turbo holt ihn vorab, auf „Einführung ansehen" → keine Anfrage, `onboarding_shown_at` bleibt NULL; Klick → gesetzt. Verwandt: BF-170 (HEAD) |
| BF-159 | Upload über `post_max_size` ohne Meldung | ✅ Einführung behoben · ❌ Profil offen | Server `:8098`: 21,6 MB in der Einführung → 422, „Das Profilbild darf maximal 2 MB groß sein." am Feld, `aria-invalid`, Fokus; im Profil → 422 **ohne Meldung** (gehört zu B04, so im Abschlussbericht angekündigt) |
| BF-160 | drei Wechsel in einer Sekunde | ✅ behoben | Reproduktion grün; am Server vier Wechsel in 110 ms → Konto NULL, Zeile `consent_at = revoked_at` (gesperrt) |
| BF-161 | „Du kannst dich jetzt anmelden" nach AK-05 | ✅ behoben | angemeldet, eigener Link → Willkommensseite, 0 × „jetzt anmelden"; die Seite sagt „Deine Adresse ist bestätigt." |
| BF-162 | CSRF-Fehler in der Einführung ohne Meldung | ✅ behoben | fremde Herkunft → 422 mit `role="alert"`: „Der CSRF-Token ist ungültig. …" (Upload und Newsletter-Schritt). Hinweis H-7 zur Anrede |
| BF-163 | „maximal 2 groß" ohne Einheit | ✅ behoben | 2,06 MB → „maximal 2 MB"; Profil gleich. Hinweis H-8 zum Grenzfall 2 000 001 B |
| BF-164 | B04: Profil mit Bild nicht speicherbar | ❌ **offen** | Browser, Paula mit Bild: „Änderungen speichern" gehört zu keinem Formular (`button.form === null`), „Profilbild entfernen" zum Formular `/de/profile/edit` |

## Akzeptanzkriterien im Einzelnen

`curl` = gegen den laufenden Server mit eigenem Cookie-Speicher je „Gerät", Zustand jeweils per SQL gelesen. Die
Ausgaben stehen in `qa/12/nachpruefung/` unter dem genannten Dateinamen.

### Wann die Einführung erscheint

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | `ak01-11`: Frieda registriert, Link als Gast → `due=1`, erste Anmeldung **302 `/de/einfuehrung`**, „Schritt 1 von 4: Willkommen". Browser (frische Sitzung): Anmeldung → `/de/einfuehrung`. ⚠ In der Sitzung der Registrierung scheitert die Anmeldung an BF-156 |
| AK-02 | ✅ | Frieda nach der Anzeige auf „Gerät B" angemeldet → **302 `/de/`** |
| AK-03 | ✅ | Bert: Gast ruft `/de/community/suggest` auf, Anmeldung → **302 `/de/community/suggest`**, `shown` bleibt NULL; nächste Anmeldung → `/de/einfuehrung`. Browser: Max ebenso. Randfall HEAD: BF-170 |
| AK-04 | ✅ | Anna unbestätigt → `/de/`, `due=0`, `shown=NULL` |
| AK-05 | ✅ | Anna angemeldet, eigener Link → **302 `/de/einfuehrung`**, bestätigt, Merker gesetzt, keine „jetzt anmelden"-Meldung |
| AK-06 | ✅ | `user@`/`admin@` (`due=0`) zweimal → `/de/`. Migration `Version20261022203034`: genau `ADD onboarding_due TINYINT DEFAULT 0 NOT NULL, ADD onboarding_shown_at DATETIME DEFAULT NULL`, kein Datenumbau |
| AK-07 | ✅ | `unverified@` (Altkonto, `due=0`) über den Link bestätigt → `due=1` → erste Anmeldung `/de/einfuehrung` (`ak07-17`) |
| AK-08 | ✅ | `POST /api/v1/auth/register` → 201, Link `/lb/verify/…` → `due=1` → erste Website-Anmeldung **`/de/einfuehrung`** |
| AK-09 | ✅ | Frieda (gesehen): Profillink „Einführung ansehen" (`data-turbo-prefetch="false"`) → 200, „Schritt 1 von 4", `shown` unverändert (H-1 zur Beschriftung) |
| AK-10 | ✅ | Gast: alle Schritte → **302 `/de/login`**, 366 Byte ohne Inhalt; POST ebenso; unbekannter Schritt 404 |
| AK-11 | ✅ | Anna unbestätigt: vier GET und beide POST → **302 `/de/verify`**, Konto unverändert |

### Ablauf

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-12 | ✅ | `ak07-17`: Fußleisten Weiter → `eintraege` → `profilbild` → `newsletter` → Absenden → **302 `/de/restaurants`**; im Browser per Tastatur durchlaufen |
| AK-13 | ✅ | Dora (Einwilligung bei der Registrierung): „Schritt 1/2/3 von 3", letzter Schritt Profilbild mit „Restaurants suchen", `/de/einfuehrung/newsletter` → 302 Suche. Clara nach Einwilligung in der Einführung: „Étape 3 sur 3" |
| AK-14 | ✅ | „Schritt N von 4" bzw. „von 3" auf jedem Bildschirm und im `<title>`; fünf Sprachen (`ak20-24-64`) |
| AK-15 | ✅ | fünf Seitenwechsel: Kontozeile und Kontaktzahl vorher = nachher; Upload-Formular ohne Datei → 302, nichts gespeichert; Newsletter-Schritt ohne Häkchen → 302, nichts gespeichert |
| AK-16 | ✅ | „Zurück" ab Bildschirm 2 zeigt jeweils auf den vorigen Schritt |
| AK-17 | ✅ | „Einführung beenden" auf jedem Bildschirm → `/de/restaurants`; letzter Bildschirm „Restaurants suchen" (Absendeknopf bzw. Link) |
| AK-18 | ✅ | 375 px: Kopfzeile sichtbar, untere Leiste mit vier Zielen je 90 × 60 px, kein waagerechtes Scrollen (`ak18-profilbild-telefon.png`) |
| AK-19 | ⚠️ | **Abnahmekriterium mit einer Person, die Endlech.lu nicht kennt — keine solche Person verfügbar.** Umfang unverändert gegenüber dem ersten Durchlauf (≈ 270 Wörter) |

### Bildschirm 1 bis 3

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-20 | ✅ | „Willkommen bei Endlech.lu, Frieda Neu!"; Knöpfe → `/de/community/suggest` und `/de/app`; Anzeige ist beim Ausliefern vermerkt |
| AK-21 | ✅ | sichtbarer Text aller vier Bildschirme in fünf Sprachen (`ak20-24-64`) auf *bewert, kommentar, favorit, rating, review, avis, évalu, avalia, coment, favori, notez, stern, étoile, estrela* abgesucht: einziger Treffer frz. „comment" (= „wie") |
| AK-22 | ✅ | a–c in allen fünf Sprachen gelesen; d fehlt (OF-01 offen) |
| AK-23 | ✅ | `ak23-begriffe`: „Verifiziertes Lokal", „Nicht verfügbar", „Nicht ausgemessen", „unter 90 cm" (und Entsprechungen) stehen in allen fünf Sprachen wortgleich auf Einführung und Detailseiten 34, 35, 37. Das kurze Abzeichen („Vérifié") steht nur auf den Listenkarten, nicht auf der Detailseite |
| AK-24 | ✅ | „Ein Eintrag zeigt, was wir erfasst haben, nicht was vor Ort gilt." und Entsprechungen |
| AK-25 | ✅ | `ak25-29-upload`: JPG, PNG, WebP, 1,46 MB und **genau 2 000 000 B** → 302; dieselbe Datei in Kopfzeile, Bildschirm und Profil; im Browser per Tastatur abgeschickt, „Dein Profilbild ist gespeichert." |
| AK-26 | ✅ | 2 000 001 B, 2,06 MB → 422, Meldung am Feld wie im Profil, `aria-invalid`, Fokus aufs Feld, Bild unverändert, „Weiter" vorhanden. Mit Produktionsgrenzen (`:8098`): 2,4 MB und **21,6 MB (über `post_max_size`)** → 422 mit „maximal 2 MB" am Feld |
| AK-27 | ✅ | Text als `bild.jpg`, PHP-Code als `shell.jpg`, GIF → 422 „Bitte lade ein Bild im Format JPG, PNG oder WebP hoch.", Bild unverändert |
| AK-28 | ✅ | „Dein Bild erscheint nur in deinem Profil und oben in der Kopfzeile … Andere sehen es nicht." in fünf Sprachen |
| AK-29 | ✅ | fünf Uploads nacheinander: angezeigt wird das neueste, im Verzeichnis bleibt **eine** Datei. Gleichzeitig: BF-173 |

### Bildschirm 4 und Profil

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ | `<input type="checkbox" … value="1" />` ohne `checked`; im Browser `checked=false` |
| AK-31 | ✅ | Text in fünf Sprachen: Neuigkeiten, einmal Willkommens-Mail, Abbestellen im Profil oder per Link, ohne Häkchen geschieht nichts |
| AK-32 | ✅ | Frieda ohne Häkchen → 302 Suche, Einwilligung NULL, 0 Kontaktzeilen |
| AK-33 | ⚠️ | **Anwendungsseite belegt, Brevo nicht erreichbar.** Clara unter `/fr/`: Einwilligung mit Zeitpunkt, Auftragsbuch `account`, `fr`, `pending`. Rumpf am HTTP-Client: `email`, `ext_id`, `listIds:[7]`, genau die fünf Attribute aus 04/AK-07, `LOCALE: fr`, `ORIGIN: ACCOUNT` (`Qa12OnboardingTest::testAk33Ak40Ak56…`, grün) |
| AK-34 | ✅ | Gustav: „Nicht abonniert" + „Newsletter abonnieren" → „Abonniert seit 23.10.2026" + „Newsletter abbestellen" |
| AK-35 | ⚠️ | **Anwendungsseite belegt** (vorher ❌, BF-154): Erst-Abo im Profil wie AK-33; nach Abbestellen und neuem Abo trägt der PUT `emailBlacklisted:false`, der Nachbau entsperrt (`Qa12NachpruefungTest::testAk35…`, grün). Ob Brevo den Kontakt danach beliefert, ist ohne Zugang nicht prüfbar |
| AK-36 | ⚠️ | **Anwendungsseite belegt:** „Nicht abonniert", Einwilligung NULL, nach übertragenem Kontakt `blacklist_pending`; der Lauf schickt `PUT …?identifierType=ext_id` mit **nur** `{"emailBlacklisted":true}` (`testAk36…`, grün). Ob Brevo danach nichts mehr zustellt, ist nicht prüfbar. Drei Randfälle, in denen die Sperre Brevo nie erreicht: BF-166, BF-167, BF-168 |
| AK-37 | ✅ | Server `:8097` mit lokalem Geheimnis: Profil Clara „Abonniert seit …", Webhook `unsubscribed` → 200, Profil **„Nicht abonniert"**, Einwilligung NULL; falsches Geheimnis → 401 |
| AK-38 | ✅ | `unverified@` abonniert: Einwilligung gespeichert, **0** Kontakte, „Wird wirksam, sobald du deine E-Mail-Adresse bestätigt hast."; Bestätigung unter `/pt/` → Kontakt `account`, `pt`, `pending` |
| AK-39 | ✅ | Anna ohne Einwilligung ändert ihren Namen → 302, gespeichert; Vorschlag/Zustimmung mit einwilligungslosen Konten in der vollen Suite grün |

### Willkommens-Mail (Plattformeinstellung T06)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-40 | ⚠️ | **Keine Automation, kein Postfach** (T06 offen, OF-04). Anwendungsseite: `LOCALE` folgt der Anfrage (`fr` bei Clara, `pt` bei `unverified@`) |
| AK-41 | ⚠️ | Braucht die Mail. Der Weg ab dem Webhook ist unter AK-37 belegt. ⚠ BF-165 kann eine solche Abmeldung bei Brevo rückgängig machen, solange der Webhook unterwegs ist |
| AK-42 | ✅ | ohne Einwilligung entsteht weder Auftrag noch Kontakt (Frieda, Paula vor dem Häkchen); was nie bei Brevo ankommt, bekommt von dort keine Mail |
| AK-43 | ⚠️ | Bedingung `ORIGIN = ACCOUNT` in Brevo. Anwendungsseite: Wartelisten-Kontakte tragen `app` (Wanda, Regression) |
| AK-44 | ⚠️ | „Nur einmal" ist eine Einstellung der Automation. Anwendungsseite: Abbestellen sperrt statt zu löschen |
| AK-45 | ⚠️ | Einstellung der Automation |
| AK-46 | ⚠️ | Anwendungsseite: nie übertragene Zeile bleibt lokal gesperrt (Gustav), übertragene bekommt den Sperrauftrag. ⚠ Fällt die Abmeldung in den laufenden Abgleich, entsteht bei Brevo ein ungesperrter Kontakt — genau der Fall dieses Kriteriums (BF-166) |
| AK-47 | ⚠️ | Inhalt der Mail in Brevo |
| AK-48 | ⚠️ | Inhalt der Mail in Brevo; Attributliste der Anwendung unverändert (AK-56) |

### Verwaltung, Messung, Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-49 | ✅ | `/de/admin`: Kachel „Einführung 5 / 11 — 11 Konten mit Einführung · 5 davon angezeigt", SQL 11 / 5, keine Namen, Adressen oder Verweise in der Kachel; als Nutzer 403 |
| AK-50 | ✅ | Browser, `window.umami.track` umhüllt: Willkommensseite → `einfuehrung_begonnen` (ohne Daten); Absenden auf dem letzten Schritt → `einfuehrung_abgeschlossen`; „Einführung beenden" auf Schritt 2 → `einfuehrung_beendet {"schritt":"2"}`; „Weiter", Upload, Häkchen: kein Ereignis. Ungenauigkeit: BF-171 |
| AK-51 | ✅ | dieselben Aufrufe tragen nur Name bzw. `schritt`; Filter am Zählweg: `Feature12UsageEventsTest` (12 Fälle, u. a. Adresse als Schritt, `abonniert`, Konto, Zusatzschlüssel → abgewiesen) grün |
| AK-52 | ✅ | Profil: 0 Auslöser `usage-event`, kein Zählskript; der Schalter löst kein Ereignis aus |
| AK-53 | ✅ | Migration fügt genau `onboarding_due`, `onboarding_shown_at` hinzu; `schema:update --dump-sql` ohne Rest zu diesen Spalten (Altlast 12 Anweisungen, keine dieses Features). (Neufassung OF-05 vom Betreiber unbestätigt) |
| AK-54 | ✅ | fokussierbare Elemente je Bildschirm im Browser: Willkommen/Einträge nur Links, Profilbild Datei + Knopf, Newsletter Häkchen |
| AK-55 | ✅ | voller Durchlauf im **Produktionsmodus** (`:8095`, Konto „Rosa Geheimname", Upload „Rosa Privat Geheimname.jpg", Fehlupload, Abo, Abbestellen, Abo, fremde Herkunft, 404): stderr **0 Treffer** für Name, Dateiname und E-Mail-Adressen |
| AK-56 | ✅ | Rumpf an Brevo (AK-33) ohne `onboarding`, `einfuehrung`, `avatar`, `uploads`, `shown`, Weg der Einwilligung |
| AK-57 | ✅ | Anna schickt `user=<Admin-ID>`, `onboarding_newsletter[user]`, `email=admin@…` an Newsletter-Schritt, Upload und Profilschalter: nur Annas Zeile ändert sich, Admin vorher = nachher |
| AK-58 | ✅ | fremde Herkunft: Newsletter-Schritt und Upload → 422 mit Meldung, Profilschalter mit falschem Token → 302 „Ungültiges CSRF-Token. Bitte versuche es erneut."; Kontozeile unverändert |
| AK-59 | ✅ | `:8097`, Produktionswert, jede Anfrage von einer **anderen Absenderadresse** (`10.0.0.1` … `10.0.0.12`): Wechsel 1–10 durch, **11. und 12. → „Zu viele Änderungen. Bitte versuch es in 60 Minuten erneut."**, Stand unverändert; Einführungsschritt danach ebenfalls abgewiesen (`ak59-60-deckel`) |
| AK-60 | ✅ | drei „Abbestellen" ohne Abo vorweg, danach trotzdem zehn Wechsel möglich; EC-08 (Theo) ebenso |
| AK-61 | ✅ | Ines (Einführung gesehen, Abo, Bild, übertragener Kontakt) löscht ihr Konto: Zeile weg, **Bilddatei weg**, Kontakt `removal_pending`, Anmeldung scheitert |
| AK-62 | ✅ | Export Dora: `onboardingDue: true`, `onboardingShownAt`, `marketingConsent: true`, `marketingConsentAt` = Datenbank |
| AK-63 | ✅ | Produktionsmodus mit untergeschobenem Schlüssel `xkeysib-qa12nachpruefungattrappe…`: vier Bildschirme und Profil → **0** Treffer |

### Sprachen und Zugänglichkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-64 | ✅ | alle vier Bildschirme in lb, de, fr, en, pt gelesen (`ak20-24-64`) — kein deutscher Text außerhalb von de; pt ohne „você" |
| AK-65 | ✅ | Browser auf `/de/einfuehrung/profilbild`: Umschalter verweist auf `/{lb,fr,en,pt}/einfuehrung/profilbild`; Klick auf FR → „Étape 3 sur 3 : Photo de profil" |
| AK-66 | ✅ | Browser, nur Tastatur, von der Willkommensseite bis zur Suche: Tab-Reihenfolge = sichtbare Reihenfolge, Upload über Enter auf „Bild hochladen" (Datei per Skript gesetzt — den Dateidialog bedient die Vorschau nicht), Leertaste aufs Häkchen, Enter auf „Restaurants suchen". Fokusrahmen nach Ende der Übergangsanimation gemessen: 2 px `rgb(14,116,144)` auf Weiter/Beenden, 2 px Purpur auf den Kartenknöpfen, `:focus-visible` wahr, `document.hasFocus()` wahr |
| AK-67 | ⚠️ | **Kein Screenreader in der Umgebung.** Belegt ist die Mechanik: nach jedem Seitenwechsel liegt der Fokus auf `#einfuehrung-titel` („Schritt N von M" + Titel); nach abgewiesenem Upload auf dem Feld, `aria-invalid="true"`, `aria-describedby` = Hilfe + Fehler; bei Formularfehler der Wurzel auf der `role="alert"`-Meldung |
| AK-68 | ✅ | 375 px: alle Knöpfe und Links ≥ 44 × 44 (kleinster „Weiter" 99 × 48, „Einführung beenden" 162 × 44, „Bild hochladen" 145 × 44). Häkchen 20 × 20 mit Label ≥ 44 px hoch (H-2) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | Frieda: Anzeige auf Gerät A, Anmeldung auf Gerät B → `/de/` |
| EC-02 | ✅ | `:8098`: Upload mit Kopf 1,46 MB, nach 40 kB Verbindung geschlossen (`qa/12/abbruch.php`) → Bild unverändert, Bildschirm danach 200 mit „Weiter" |
| EC-03 | ✅ | alle Abos dieses Durchlaufs ohne erreichbares Brevo: Erfolgsmeldung, Zeile `pending`; Wiederholung nach Fehlschlag `Feature12SperreSyncTest` grün, bei `failed` auch in `testBf167…` sichtbar |
| EC-04 | ✅ | `user@` angemeldet öffnet Janas Link → `/de/login`, `user@` bleibt angemeldet (Profil 200), Merker 0; Janas erste Anmeldung → `/de/einfuehrung` |
| EC-05 | ✅ | abgelaufener Link → 302 `/de/verify`, Konto unbestätigt, Merker 0 |
| EC-06 | ✅ | Lena mit „Angemeldet bleiben" (fällig), Sitzungs-Cookie verworfen: `/de/restaurants`, `/de/`, `/de/profile` → 200, `shown` bleibt NULL |
| EC-07 | ✅ | Max: bestätigt, nie angemeldet, Passwort zurückgesetzt → erste Anmeldung mit neuem Passwort → `/de/einfuehrung` |
| EC-08 | ✅ | Theo: Tab 1 hat den Schritt offen, Tab 2 abonniert, Tab 1 setzt das Häkchen → Einwilligung unverändert, 1 Kontakt; danach Wechsel 2–10 durch, 11. gesperrt |
| EC-09 | ✅ | Neustart über das Profil: `shown` und Kachelwert (5) unverändert; Ereignis „begonnen" erneut (Browser) |
| EC-10 | ✅ | Otto löscht sich in Sitzung B; Sitzung A: nächster Schritt und Newsletter-Absenden → 302 `/de/login`, keine Zeile, kein Kontakt |

## Regression

Volle Testsuite: `php bin/phpunit` → **1524 Tests, 8948 Assertions, 0 Fehlschläge, 17 übersprungen** (37 s,
`qa/12/nachpruefung/volle-suite.ausgabe.txt`). Vor dieser Prüfung: 1517 Tests, 0 Fehlschläge, 11 übersprungen — die
sieben neuen sind `Qa12NachpruefungTest`. Zahl gegenüber dem ersten Bericht (1514) gestiegen, nichts fehlt.
`lint:twig` grün (133 Dateien); `lint:container` scheitert wie bisher am WebAuthn-Alias (vorbestehend, keine Datei
dieses Features); `schema:update --dump-sql` ohne Rest zu den neuen Spalten.

Berührt (Arbeitsstand gegen `HEAD`; `git diff --name-only main...HEAD` zeigt nur den AssetMapper-Umbau, auf dem der Branch
aufsetzt): wie im ersten Bericht, dazu aus dem Fehlerauftrag `BrevoContactClient::upsert()` und
`MarketingContactRegistry::changeEmail()` (Feature 04, B04) und `validators.*.yaml` (Profilmeldung, B04).

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B01 | Bestätigungslink setzt Merker, neue Weiterleitung | ✅ | Registrierung → Link → `/de/login`, Konto bestätigt (Frieda, Bert, Jana …) |
| B02 | Weiche nach der Anmeldung | ✅ `curl`, ❌ Browser (vorbestehend) | `user@` → `/de/`. **Im Browser BF-156, Auslöser breiter als erfasst** (`bf156-browser.ausgabe.txt`) |
| B03 | Passkey-Weg durch dieselbe Weiche | ✅ (Suite) | `SecurityControllerTest`, `PasskeyControllerTest` grün |
| B04 | Profil, Avatar-Regel, Validierungstext, Adresswechsel im Auftragsbuch | ✅ ohne Bild, ❌ mit Bild (vorbestehend) | Name ändern (AK-39), Upload im Profil, Adresswechsel bestätigt (BF-157-Gegenprobe). **BF-164 unverändert**, BF-159-Profilhälfte offen |
| B11 | Ziel der Willkommensseite, gemerkter Rückweg | ✅ | `/de/community/suggest` 200; AK-03 |
| B19 | Kachel | ✅ | `/de/admin` 200 / 403 |
| 01 | Export, Löschung | ✅ | AK-61, AK-62 |
| 04 | Auftragsbuch, Mapper, Client, Lauf | ✅ | Wanda: App-Warteliste mit Werbe-Einwilligung → Bestätigung → Kontakt `app`, `de`, `pending`; Webhook (AK-37); 51 Marketing-Tests grün. Vorbestehend: BF-169 |
| 08 | Ziel der Willkommensseite | ✅ | `/de/app` 200, Eintragung + Bestätigung |
| 10 | `SeoRegistry` | ✅ | `/sitemap.xml` 200, keine Einführungsseite darin |
| 11 | drei neue Ereignisse | ✅ | AK-50 bis AK-52 |

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | fremde Konto-ID in drei Formularen ändert nichts (AK-57); der Weg ohne Kennung aus BF-155 ist zu (am Server nachgestellt) |
| Rate Limit greift | bestanden; ⚠ Upload | `newsletter_toggle`: 10 durch, 11. abgewiesen, am **Konto** — zwölf verschiedene Absenderadressen (AK-59). Upload ohne Deckel wie spezifiziert; die Annahme dahinter („jeder Upload ersetzt die alte Datei") trägt bei gleichzeitigen Anfragen nicht (BF-173) |
| PII in Logs | bestanden | Produktionsmodus: 0 Treffer nach vollem Durchlauf (AK-55); auch der 500er aus BF-172 schreibt die Adresse nicht nach stderr (Kanal `request` ausgeschlossen) — die Ausnahme trägt sie aber im Text und ginge so an Sentry (hier nicht aktiv) |
| PII an externe Dienste | bestanden | Brevo-Rumpf am HTTP-Client (AK-33/56); Umami-Aufrufe im Browser (AK-50/51) nur Name und `schritt` |
| Zugriffsregeln serverseitig | bestanden | Gast → Anmeldung (AK-10); unbestätigt → `/verify` auch für POST (AK-11); `/de/admin` als Nutzer 403 |
| CSRF | bestanden | fremde Herkunft → 422 mit Meldung / Fehlerflash, nichts geändert (AK-58). Kehrseite der CSRF-Strategie: BF-156 |
| Eingaben | bestanden | Upload: falsche Endung, PHP-Code als `.jpg`, GIF, 2 000 001 B, 2,4 MB, 21,6 MB, abgebrochene Verbindung — nie 500, nie gespeichert, immer eine Meldung. Gleichzeitiges Erst-Abo → 500 (BF-172) |
| Löschen | bestanden | Konto, Merker, Datum, Einwilligung, Bilddatei weg, Brevo-Löschauftrag (AK-61) |
| Geheimnisse im Repository | bestanden | `git log --all -S 'xkeysib-'` und Arbeitsstand: nur Platzhalter (`xkeysib-…` in einer Doku-Zeile, Attrappe `xkeysib-f12-pruefschluessel` im Test); untergeschobener Schlüssel erscheint auf keiner Seite (AK-63) |

## Fehler

Die Nummern setzen den ersten Bericht fort (BUG-01 bis BUG-09 sind behoben, siehe oben).

### BUG-10 · Die Aufhebung einer Sperre geht bei jedem späteren Abgleich wieder mit — mittel (BF-165)

**Betrifft:** design.md Entscheidung 6, 04/EC-05; mittelbar AK-41, AK-36
**Reproduktion** (`QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12NachpruefungTest.php --filter Bf165`):
1. Konto abonniert, Lauf legt den Kontakt an; Konto bestellt ab, Lauf sperrt; Konto abonniert erneut, Lauf entsperrt
   (`emailBlacklisted:false`, richtig — BF-154).
2. Die Person klickt den Abmeldelink in einer Brevo-Mail; Brevo sperrt, der Webhook ist noch unterwegs oder scheitert.
3. Im selben Fenster ein Anlass zum Abgleich: bestätigter Adresswechsel (B04), ebenso ein Statuswechsel einer
   Warteliste derselben Adresse in der Verwaltung.
4. Nächster Lauf.
**Erwartet:** Der PUT trägt kein `emailBlacklisted` — eine Abmeldung, die nur Brevo kennt, bleibt unberührt.
**Tatsächlich:** Der PUT trägt erneut `emailBlacklisted:false`, Brevo entsperrt. Kommt der Webhook danach an, ist die
Zeile lokal gesperrt, bei Brevo aber offen, und niemand überträgt die Sperre noch einmal (`testBf165Verspaeteter…`):
Werbung nach Widerspruch.
**Ort:** `src/Marketing/MarketingPayloadMapper.php:159–163` — `revokedAt !== null && consentAt > revokedAt` bleibt nach
der einmal übertragenen Aufhebung für immer wahr (`revokedAt` bleibt als Historie stehen).
**Vorschlag:** Nur senden, solange die Aufhebung noch nicht übertragen ist (etwa `syncedAt === null || syncedAt <
consentAt`).

**Behoben 2026-10-23** (`/sdd-build 12`, zweiter Fehlerauftrag): Der Mapper schickt `emailBlacklisted:false` nur noch, solange die Aufhebung nicht übertragen ist (`syncedAt` fehlt oder liegt vor der Einwilligung). Tests: `Qa12NachpruefungTest::testBf165SpaetererAbgleich…` und `::testBf165VerspaeteterWebhook…` (scharf, vorher rot).

### BUG-11 · Eine Abmeldung während des laufenden Abgleichs erreicht Brevo nie — mittel (BF-166)

**Betrifft:** AK-46, AK-36
**Reproduktion** (`--filter Bf166`): Konto abonniert (Zeile `pending`, `synced_at` leer). Der Lauf lädt die Zeile und
legt den Kontakt bei Brevo an; **in dieser Zeit** bestellt das Konto in einer zweiten Anfrage ab. Weil `synced_at` noch
leer ist, stellt `revokeForAccount()` keinen Sperrauftrag, sondern setzt nur `revoked_at`. Danach schreibt der Lauf
`synced`. Die zweite Anfrage ist im Test als das nachgebildet, was sie in die Datenbank schreibt — an der Unit of Work
des Laufs vorbei, wie es ein zweiter PHP-Prozess täte.
**Erwartet:** Spätestens der nächste Lauf sperrt den Kontakt bei Brevo.
**Tatsächlich:** Brevo hat einen frisch angelegten, ungesperrten Kontakt (Einstieg der Willkommens-Mail), lokal ist die
Zeile gesperrt, kein Auftrag offen — auch nach zwei weiteren Läufen. Profil „Nicht abonniert".
**Ort:** `src/Marketing/MarketingSyncService.php:54` (lädt am Anfang) und `:109` (ein `flush()` am Ende, BF-87);
`src/Marketing/MarketingContactRegistry.php:514` (Sperrauftrag nur bei gesetztem `synced_at`). Fenster: Dauer des
Laufs bis zum `flush()`, bei vielen offenen Zeilen Sekunden.
**Vorschlag:** Vor `markSynced()` den Zustand frisch lesen bzw. bedingt schreiben (`WHERE sync_state = erwartet`), oder
beim Abbestellen jede schon einmal versuchte Zeile mit Sperrauftrag versehen (siehe BUG-12).

**Behoben 2026-10-23**: Nach dem Schreiben eines Laufs sieht `MarketingSyncService` nach, ob eine eben übertragene Zeile inzwischen gesperrt ist, und stellt dann den Sperrauftrag (bedingt auf `sync_state = 'synced'`). Restfenster: eine Abmeldung, die ihren Stand liest, bevor der Lauf schreibt, und erst nach dessen Nachsehen schreibt — Millisekunden, nicht die Dauer des Laufs. Test: `Qa12NachpruefungTest::testBf166…`.

### BUG-12 · Nach einer gescheiterten Erstübertragung stellt Abbestellen keinen Sperrauftrag — niedrig (BF-167)

**Betrifft:** AK-36 (Randfall)
**Reproduktion** (`--filter Bf167`): Konto abonniert; Brevo legt den Kontakt an, die Antwort scheitert (Zeitüberschreitung,
im Test 504) → Zeile `failed`, `synced_at` leer. Vor dem Nachlauf bestellt das Konto ab.
**Erwartet:** Der bei Brevo angelegte Kontakt wird gesperrt.
**Tatsächlich:** kein Sperrauftrag; die gesperrte `failed`-Zeile holt `findOpenForSync()` nicht mehr ab, Brevo behält
einen ungesperrten Kontakt.
**Ort:** `MarketingContactRegistry.php:514` — `synced_at` dient als „gibt es bei Brevo", bedeutet aber nur „letzte
Übertragung bestätigt". `blacklist()` behandelt 404 ohnehin als Erfolg.
**Vorschlag:** Sperrauftrag für jede Zeile, die schon einmal versucht wurde (`attempts > 0` oder `failed`).

**Behoben 2026-10-23**: `revokeForAccount()` stellt den Sperrauftrag auch bei `attempts > 0` und `failed`; ein Löschauftrag wird nie in eine Sperre umgewandelt. Test: `Qa12NachpruefungTest::testBf167…`.

### BUG-13 · Ein Sperrauftrag wird nach fünf Fehlversuchen aufgegeben, ohne Anzeige — mittel (BF-168)

**Betrifft:** AK-36; im ersten Bericht als Hinweis H-4 geführt, hier nachgestellt und hochgestuft
**Reproduktion** (`--filter Bf168`): abonniert, übertragen, abbestellt (`blacklist_pending`); fünf Läufe mit 401 (etwa
abgelaufener Schlüssel) → `attempts = 5`; Brevo wieder erreichbar, nächster Lauf.
**Erwartet:** Die Sperre erreicht Brevo — `blacklist_pending` soll Fehlversuche überleben (CLAUDE.md, Feature 12:
„ohne das wäre der Auftrag nach dem ersten 429 weg").
**Tatsächlich:** `findOpenForSync()` lässt die Zeile ab `MAX_ATTEMPTS` dauerhaft draußen; kein Weg setzt `attempts`
zurück. Für Konto-Kontakte gibt es keine Verwaltungsansicht (nur Wartelisten zeigen den Abgleich). Ein Ausfall von
gut 25 Minuten genügt.
**Ort:** `src/Repository/MarketingContactRepository.php:56`, `src/Entity/MarketingContact.php:348–362`.
**Vorschlag:** Sperr- und Löschaufträge von der Versuchsgrenze ausnehmen (mit Abstand) oder ihr Aufgeben melden.

**Behoben 2026-10-23**: `findOpenForSync()` nimmt `blacklist_pending` ohne Versuchsgrenze (je Lauf ein Aufruf). Eine Verwaltungsanzeige für Konto-Kontakte ist **nicht** gebaut. Test: `Qa12NachpruefungTest::testBf168…`.

### BUG-14 · Eine HEAD-Anfrage vermerkt die Einführung als angezeigt — niedrig (BF-170)

**Betrifft:** AK-02, AK-03 (Kehrseite: „gilt als nicht gesehen")
**Reproduktion:** Lena fällig (`shown=NULL`), `curl -I -b <Sitzung> /de/einfuehrung` → 200, danach `shown` gesetzt,
nächste Anmeldung → `/de/` (`ak49-62-ec.ausgabe.txt`).
**Ort:** `src/Controller/OnboardingController.php:77–80` — `methods: ['GET']` nimmt HEAD mit, geprüft wird nur der
Vorabladen-Kopf. **Vorschlag:** nur bei `GET` vermerken.

**Behoben 2026-10-23**: Vermerkt nur bei `GET`. Test: `Feature12Nachpruefung2BugfixTest::testBf170…` (vorher rot).

### BUG-15 · „Einführung abgeschlossen" wird auch gezählt, wenn das Absenden abgewiesen wird — niedrig (BF-171)

**Betrifft:** AK-50, US-07 (Aussagekraft der Messung)
**Reproduktion** (Browser): Kurt mit erschöpftem Kontingent, Newsletter-Schritt, Häkchen, „Restaurants suchen" →
bleibt auf „Schritt 4 von 4" mit „Zu viele Änderungen …"; `umami.track` wurde mit `einfuehrung_abgeschlossen`
gerufen. Ebenso bei einem CSRF-Fehler.
**Ort:** `templates/onboarding/newsletter.html.twig:32–34` (Auslöser am Klick). **Vorschlag:** nach der erfolgreichen
Weiterleitung zählen (z. B. Merker in der Sitzung, Auslöser auf der Zielseite) oder den Klick nur auf gültigem Weg.

**Behoben 2026-10-23**: Kein Auslöser mehr am Knopf; der Controller setzt nach gelungenem Absenden eine Flash-Meldung der Art `usage_event`, die das Flash-Partial als unsichtbaren Messauslöser rendert. Test: `Feature12Nachpruefung2BugfixTest::testBf171…` (vorher rot).

### BUG-16 · Gleichzeitiges Erst-Abonnieren aus zwei Sitzungen ergibt HTTP 500 — niedrig (BF-172)

**Betrifft:** AK-34/AK-35, Muster BF-121
**Reproduktion:** Server mit vier Arbeitsprozessen (`:8096` dev, `:8093` Produktionsmodus), dasselbe Konto in zwei
Sitzungen, beide „Newsletter abonnieren" gleichzeitig → eine 302, die andere **500**
(`SQLSTATE[23000] … Duplicate entry '<Adresse>' for key 'marketing_contact.UNIQ_…'`). Stand danach konsistent (eine
Zeile). Dieselbe Sitzung in zwei Tabs ist durch die Sitzungssperre geschützt.
**Ort:** `src/Account/NewsletterSubscription.php:35–47` → `MarketingContactRegistry::record()` ohne Fang der
Unique-Verletzung. **Vorschlag:** wie BF-121 fangen und als „schon abonniert" behandeln.

**Behoben 2026-10-23**: `NewsletterSubscription::subscribe()` fängt die Unique-Verletzung (Muster BF-121) und meldet „nichts geändert". Test: `Bf172GleichzeitigesAboTest` (vorher rot, mit einer Zeile, die eine „andere Anfrage" unmittelbar vor dem Schreiben anlegt). Mit vier Arbeitsprozessen nicht nachgestellt.

### BUG-17 · Gleichzeitige Uploads hinterlassen verwaiste Bilddateien — niedrig (BF-173)

**Betrifft:** Missbrauchsschutz-Abschnitt der Spec („Bild-Upload: kein eigener Deckel"), AK-29; vorbestehend im Profil (B04)
**Reproduktion:** `:8096`, ein Konto in vier Sitzungen, vier Uploads à 1,46 MB gleichzeitig → alle 302; im Verzeichnis
ein Bild mehr, auf das kein Konto verweist. Wiederholbar → Plattenfüllung mit einem bestätigten Konto.
**Ort:** `src/Service/AvatarUploadService.php` (`upload()` liest den alten Namen, speichert, löscht — ohne Sperre).
**Vorschlag:** Entscheidung beim Betreiber, ob ein Deckel kommt (`/sdd-klaeren 12`); technisch sonst Sperre je Konto
oder Aufräumen verwaister Dateien. Die Begründung der Spec gegen einen Deckel setzte voraus, dass jeder Upload die alte
Datei ersetzt — das gilt nur nacheinander.

**Behoben 2026-10-23** (technisch, ohne Deckel): `AvatarUploadService` arbeitet unter einer Sperre je Konto und räumt neben dem Namen der Entity den frisch aus der Datenbank gelesenen weg. Wirkt in Einführung und Profil. Test: `Bf173AvatarVerwaistTest` (vorher rot). Ob der Upload **zusätzlich** einen Deckel bekommt, bleibt beim Betreiber (Spec OF-06).

### BUG-18 · Der Profiltext verspricht unbestätigten Konten eine adressweite Abmeldung — niedrig (BF-174)

**Betrifft:** AK-36, Kehrseite der Reparatur von BF-155
**Reproduktion:** unbestätigtes Konto mit derselben Adresse wie ein Wartelisten-Kontakt mit Einwilligung (am Server
nachgestellt): Profil sagt „Abbestellen gilt für alle Neuigkeiten an diese Adresse, auch zu einer Warteliste.";
nach Abbestellen „Nicht abonniert" — die Warteliste bleibt eingewilligt, Brevo beliefert die Adresse weiter.
**Ort:** `templates/profile/_newsletter_manage.html.twig:13` (`newsletter.text`, alle fünf Kataloge). **Vorschlag:**
für unbestätigte Konten einen eigenen Satz („gilt für dieses Konto; für die ganze Adresse erst nach der Bestätigung
oder über den Link in einer Mail").

**Behoben 2026-10-23**: Unbestätigte Konten sehen `newsletter.text_unverified` (fünf Sprachen). Test: `Feature12Nachpruefung2BugfixTest::testBf174…` (vorher rot).

### Vorbestehend, beim Prüfen dieses Features gefunden (nicht durch Feature 12 verursacht)

**BUG-19 / BF-169 · Feature 04 · niedrig — Ein Adresswechsel auf eine Adresse, die schon als Wartelisten-Kontakt
im Auftragsbuch steht, lässt den Kontakt der alten Adresse bei Brevo stehen.** `changeEmail()` ruft im Kollisionszweig
`scheduleRemoval(alt)` (`MarketingContactRegistry.php:245`); `sourcesFor(alt)` findet das Konto noch über die
Datenbank (der `flush()` folgt erst danach in `EmailVerificationController::confirmEmailChange()`), hält es für eine
aktive Quelle der alten Adresse und schreibt über `recordUser()` die Zeile der **neuen** fort (Herkunft danach
„Konto"). Die alte bleibt `synced` und wird weiter bespielt. `--filter Bf169`.

**BF-156 · B02 · hoch — weiter offen, Reichweite größer als erfasst** (`bf156-browser.ausgabe.txt`). Ursache wie im
ersten Bericht: `csrf_protection_controller.ts` ist `stimulusFetch: 'lazy'` (auch auf `main`, Z. 94); Seiten ohne ein
Element mit `data-controller="csrf-protection"` laden ihn nicht, ihr `_csrf_token` geht ohne Double-Submit hinaus;
Symfony merkt sich in der Sitzung, dass Double-Submit benutzt wurde, und **vererbt den Merker beim Abmelden in die neue
Sitzung** (`csrf-token = 259`). Nachgestellt:
- Registrierung → Link → Anmeldung: „Ungültiges CSRF-Token.", zweimal.
- Bild in der Einführung hochgeladen, Startseite frisch geladen, „Abmelden" → **bleibt angemeldet**, keine Meldung.
- Bestandskonto: Anmelden, Profil öffnen, Abmelden, Anmeldeseite frisch laden → „Ungültiges CSRF-Token."

**Behoben 2026-10-23** (Fehlerauftrag B02, nicht ausgeliefert): `csrf_protection_controller.ts` wird sofort geladen statt `lazy`; die drei handgeschriebenen Token-Felder (Anmeldung, Abmeldung in Kopfzeile und Profil) tragen `data-controller="csrf-protection"`. Der Server bleibt unverändert (der Merker ist Schutz gegen das Zurückfallen auf die Herkunftsprüfung). Im Browser alle drei Wege nachgestellt — Anmelden, Profil, Abmelden, Anmeldeseite frisch, Anmelden → angemeldet; Abmelden von der frisch geladenen Startseite → abgemeldet; dev.log: dreimal „accepted using both origin and double-submit info". Tests: `Bf156CsrfControllerTest` (Controller in `eagerControllers`, Felder binden ihn; vorher rot). `Qa12OnboardingTest::testBf156…` bleibt **übersprungen, mit Begründung**: Er schickt die Anmeldung ohne Double-Submit und bildet damit den alten Browser nach — das lehnt Symfony zu Recht ab.

**BF-164 · B04 · hoch — weiter offen.** Browser, Konto mit Bild: `button.form === null` für „Änderungen speichern",
„Profilbild entfernen" gehört zum Profilformular.

**Behoben 2026-10-23** (Fehlerauftrag B04, nicht ausgeliefert): Das Löschformular steht hinter dem Profilformular, der Knopf hängt sich über `form="profil-bild-entfernen"` daran. Im Browser: „Änderungen speichern" gehört zu `/de/profile/edit`, „Profilbild entfernen" zu `/de/profile/avatar/delete`, Speichern mit Bild → „Profil erfolgreich aktualisiert." Tests: `Bf164ProfilFormularTest` (Struktur am rohen Quelltext, vorher rot). Profilhälfte von BF-159 ebenso behoben (`::testBf159…`, Meldung „2 MB").

## Hinweise (keine Befunde)

- **H-1** · AK-09 nennt den Link „Einführung erneut ansehen", gebaut ist „Einführung ansehen" — die Spec sollte nachziehen.
- **H-2** · Das Newsletter-Häkchen misst 20 × 20 px; sein Label (≥ 44 px hoch) schaltet es mit.
- **H-3** · Der Schlüssel des Limiters ist die Adresse; Symfonys Lock-Kanal schreibt ihn auf DEBUG (in Produktion nur im
  Fehlerfall derselben Anfrage).
- **H-5** · „Einführung begonnen" zählt bei „Zurück" auf Bildschirm 1 und bei Turbos Vorschau aus dem Cache erneut.
- **H-6** · „Abonniert seit" zeigt das Datum in UTC und in jeder Sprache als `d.m.Y`.
- **H-7** · Die CSRF-Meldung in der Einführung ist Symfonys Vorgabetext in Sie-Form („Versuchen Sie bitte …"), die
  Einführung duzt.
- **H-8** · Genau über der Grenze (2 000 001 B) lautet die Meldung „maximal 2000000 bytes groß" — Symfony weicht auf
  Bytes aus, wenn beide Werte gerundet gleich wären; „bytes" bleibt englisch.
- **H-9** · Eine Konto-Einwilligung schreibt einen bestehenden Wartelisten-Kontakt auf `ORIGIN=ACCOUNT` um und leert
  `ORGANISATION`/`FUNNEL_STATUS` bei Brevo (letzter Schreiber gewinnt) — gehört zu OF-03 / 04/OF-04, über den
  Profilschalter jetzt jederzeit erreichbar.
- **H-10** · Nicht prüfbar ohne Brevo-Konto: ob `PUT …?identifierType=ext_id` für einen per Upsert über die Adresse
  gefundenen Altkontakt 404 liefert (dann „gelänge" die Sperre stillschweigend), und ob Brevo eine API-Sperre per
  Webhook als `unsubscribed` zurückmeldet (dann löschte ein verspätetes Echo eine zwischenzeitliche Wieder-Einwilligung).
- **Code-Review:** Der `code-reviewer` lieferte sechs im Code belegte, vier vermutete und drei schon bekannte bzw.
  vorbestehende Punkte; keinen Rückfall in den Reparaturen BF-154 bis BF-163. Am System bzw. per Test
  bestätigt und aufgenommen: BF-165 (dort Fund 4, unabhängig vom eigenen Fund), BF-166 (1), BF-167 (2), BF-168 (3),
  BF-170 (6), BF-171 (5), BF-172 (8, im Produktionsmodus nachgestellt), BF-173 (13b), BF-169 (13a). Als Hinweis:
  H-9 (10), H-10 (7, 9). Bestätigt ohne neuen Befund: BF-156, BF-164.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/Qa12NachpruefungTest.php` | `testAk35WiederEinwilligungHebtDieSperreAuf` (grün) | AK-35, BF-154 — Rumpf am HTTP-Client |
| dieselbe Datei | `testBf165SpaetererAbgleich…`, `testBf165VerspaeteterWebhook…`, `testBf166…`, `testBf167…`, `testBf168…`, `testBf169…` | Reproduktionen; prüfen das richtige Verhalten, waren rot (`qa/12/nachpruefung/neue-befunde-scharf.ausgabe.txt`), überspringen sich bis zur Behebung. Scharf: `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12NachpruefungTest.php` |

Die Reproduktionen aus `Qa12OnboardingTest` (erster Durchlauf) laufen scharf alle grün außer `testBf156…`.

## Nächster Schritt

1. **`/sdd-build B02`** (BF-156) und **`/sdd-build B04`** (BF-164, BF-159 Profilhälfte) — blockieren die Auslieferung
   von Feature 12, weil sie auf dessen Hauptweg liegen.
2. **`/sdd-klaeren 12`** — BF-173 (Deckel für den Upload, entgegen der Spec?), dazu T06/OF-04 (Brevo-Automation),
   OF-05 (AK-53 neu gefasst), OF-03 (Herkunftskonflikt, siehe H-9); OF-01 und OF-02 bleiben offen.
3. **`/sdd-build 12`** mit BUG-10 bis BUG-18 (BF-165 bis BF-168, BF-170 bis BF-174); BF-169 über den Fehlerauftrag von
   Feature 04. Je Befund den Aufruf `$this->offen('BF-…')` in `Qa12NachpruefungTest` entfernen.
4. Danach erneut **`/sdd-qa 12`**. AK-19 braucht eine Person mit Stoppuhr, AK-40 bis AK-48 die eingerichtete Automation.
