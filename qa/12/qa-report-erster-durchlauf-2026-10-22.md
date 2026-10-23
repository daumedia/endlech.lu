# 12 · Onboarding für neue Nutzer — Testbericht

> Archiv: erster QA-Durchlauf vom 2026-10-22 samt Behebungsvermerken des Fehlerauftrags. Maßgeblich ist
> `features/12-onboarding-neue-nutzer/qa-report.md` (Nachprüfung 2026-10-23).

Stand: 2026-10-22 · Geprüft gegen `spec.md` vom 2026-10-22 (AK-01 bis AK-68, EC-01 bis EC-10) · in eigener Session,
ohne Bauverlauf · Vorstufe `building`, Abschlussbericht am Ende von `tasks.md` als Suchliste gelesen · Branch
`feature/12-onboarding`, nicht committet

**Prüfumgebung.** Wegwerf-Datenbank `endlech-sdd-testdb` (MySQL 8.0, Port 3309; Entwicklung `endlech` mit frisch
geladenen Fixtures, Tests `endlech_test`). Vier lokale Server aus demselben Arbeitsstand: `:8099` Entwicklung (wie
vorgegeben), `:8098` Entwicklung mit den Upload-Grenzen des Produktionsbildes (`upload_max_filesize=8M`,
`post_max_size=10M`, Dockerfile Z. 174 f.), `:8096` Entwicklung mit Umami-Platzhalter, lokalem Brevo-Webhook-Geheimnis
und einem **Fänger** auf `:8097`, der mitschreibt, was die Anwendung an Umami weiterreicht, `:8095` **Produktionsmodus**
(`APP_ENV=prod`, `APP_DEBUG=0`) nur für die Protokolle. Browser: Chromium-Vorschau (1280 × 800, iPhone SE 375 × 667,
320 × 640). Kein Zugang zu Brevo; `BREVO_API_KEY` leer, Mails gehen an `null://null`. Nachweise unter `qa/12/`.

## Fazit

**Production-ready: nein.**

Die Einführung selbst trägt: Weiche nach der Anmeldung, Reihenfolge, Zählung „Schritt N von M", Auslassen,
Zurück, Beenden, gemerkte Zielseite, Bestandskonten, Konten aus der App, Sprachen, Tastatur, Zielgrößen,
Messereignisse ohne Personenbezug, Deckel am Konto (10 durch, 11. abgewiesen — am laufenden Server gezählt),
Datenexport und Kontolöschung. **54 von 68 Kriterien bestanden, alle 10 Randfälle belegt.**

Nicht tragen zwei Dinge am **Rückweg nach Brevo**, beide *hoch*:

- **BUG-01 / BF-154** — Wer abbestellt und später wieder abonniert, bleibt bei Brevo **gesperrt**. Der Entwurf
  (Entscheidung 6) schickt dafür `emailBlacklisted: false`; der Mapper setzt es, `BrevoContactClient::upsert()`
  verwirft es auf dem Aktualisierungsweg. Das Profil sagt „abonniert", Brevo stellt nichts zu. AK-35 durchgefallen.
- **BUG-02 / BF-155** — Ein **unbestätigtes** Konto kann mit „Abbestellen" die Einwilligung des echten Inhabers der
  Adresse auf einer Warteliste löschen und dessen Brevo-Kontakt sperren lassen. Wer eine fremde Adresse registriert,
  braucht dafür zwei Klicks im Profil.

Dazu vier *mittlere* (Adresswechsel verwirft den Sperrauftrag; Turbo lädt „Einführung ansehen" beim Überfahren vor
und vermerkt sie als angezeigt; Bild über 10 MB ergibt 422 ohne Meldung — AK-26 durchgefallen; drei Wechsel in
einer Sekunde machen eine Abmeldung im Auftragsbuch wirkungslos) und drei *niedrige*.

⚠ **Zwei vorbestehende Fehler, beide *hoch*, beide auf der Produktion, beide auf dem Hauptweg dieses Features
gefunden** — nicht durch Feature 12 verursacht, aber sie treffen genau die Menschen, für die es gebaut ist:

- **BF-156 (B02)** — Wer sich im Browser registriert und den Bestätigungslink im selben Browser öffnet, kann sich
  danach **nicht anmelden**: „Ungültiges CSRF-Token.", bei jedem Versuch, bis die Sitzung endet. Im echten Browser
  nachgestellt. Die erste Anmeldung — der Auslöser der Einführung — kommt so gar nicht zustande.
- **BF-164 (B04)** — Sobald ein Konto ein Profilbild hat, tut „Änderungen speichern" im Profil **nichts**, und
  „Profilbild entfernen" schickt das falsche Formular. Ein `<form>` steht im `<form>` (seit Issue #54). Die
  Einführung lädt gerade zum Hochladen ein.

Nicht prüfbar sind 12 Kriterien: acht hängen an der Brevo-Automation, die es hier nicht gibt (T06, OF-04), drei an
Brevos Seite eines sonst bis zur Schnittstelle belegten Weges, je eines an einer Person mit Stoppuhr (AK-19) und an
einem Screenreader (AK-67).

**Nächster Schritt:** `/sdd-build 12` mit BUG-01 bis BUG-09 (BF-154, BF-155, BF-157 bis BF-163), danach erneut
`/sdd-qa 12`. BF-156 und BF-164 über den Fehlerauftrag `/sdd-build B02` bzw. `/sdd-build B04` — **vor** der
Auslieferung von Feature 12, sonst scheitert dessen Hauptweg im Browser an ihnen. Unabhängig davon beim Betreiber:
T06 (Brevo-Automation nach OF-04), Bestätigung von OF-05 (AK-53 neu gefasst).

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 56 von 68 ausgeführt |
| davon bestanden | 54 |
| davon durchgefallen | 2 (AK-26, AK-35) |
| **nicht prüfbar** | 12 |
| Edge Cases belegt | 10 von 10 |
| Tests neu geschrieben | 10 (eine Datei) — 3 Belege, 7 Reproduktionen |
| Tests grün | 3 von 3 Belegen; 7 von 7 Reproduktionen **rot**, solange der Befund offen ist (übersprungen) |
| Volle Suite gelaufen | **1514 Tests, 0 Fehlschläge**, 17 übersprungen (10 wie im Ausgangsstand, 7 Reproduktionen) |
| Regression: berührte Features | 11, davon Hauptweg bestanden 9 — B02 und B04 im Browser vorbestehend gestört (BF-156, BF-164) |

## Akzeptanzkriterien im Einzelnen

`curl` = gegen den laufenden Entwicklungsserver mit eigenem Cookie-Speicher je „Gerät", Zustand jeweils per SQL aus
der Datenbank gelesen. Browser = Chromium-Vorschau, Turbo aktiv.

### Wann die Einführung erscheint

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | `curl`: frisch registriert, Link als Gast bestätigt (`onboarding_due=1`), erste Anmeldung → **302 `/de/einfuehrung`**; ebenso im Browser (Frida) mit Titel „Schritt 1 von 4: Willkommen". ⚠ Im Browser nur über einen Umweg erreichbar, wenn Registrierung und Bestätigungslink in derselben Sitzung liegen — siehe BF-156 |
| AK-02 | ✅ | Bert: Willkommensseite einmal ausgeliefert (`onboarding_shown_at` gesetzt), Ausstieg auf Bildschirm 1; zweite Anmeldung → **302 `/de/`**. Ebenso Anna nach AK-05 |
| AK-03 | ✅ | Gast ruft `/de/community/suggest` auf, meldet sich an → **302 `/de/community/suggest`**, `onboarding_shown_at` bleibt `NULL`; nächste Anmeldung → `/de/einfuehrung`. Im Browser wiederholt (iOS-Konto) |
| AK-04 | ✅ | Unbestätigte Anna meldet sich an → `/de/`; `onboarding_due=0`, `shown_at=NULL` |
| AK-05 | ✅ | Angemeldet als unbestätigte Anna, eigener Bestätigungslink → **302 `/de/einfuehrung`**, Konto bestätigt, Merker gesetzt. Nebenbefund BUG-07 (falscher Hinweistext) |
| AK-06 | ✅ | Fixture-Konten `user@`/`admin@` (`onboarding_due=0`) → `/de/` bei erster und zweiter Anmeldung. Migration an eigener Datenbank `endlech_qa12` nachgestellt: bis `Version20261014120000` migriert, ein bestätigtes und ein unbestätigtes Altkonto angelegt, dann `Version20261022203034` → beide `onboarding_due=0`, `shown_at=NULL` |
| AK-07 | ✅ | Unbestätigtes Konto mit `onboarding_due=0` (Zustand nach der Migration) über den Link bestätigt → Merker 1 → erste Anmeldung `/de/einfuehrung` (Bert) |
| AK-08 | ✅ | `POST /api/v1/auth/register` → 201, Link `/lb/verify/…` → Merker 1 → erste Website-Anmeldung **`/de/einfuehrung`** |
| AK-09 | ✅ | Bert (gesehen) folgt „Einführung ansehen" im Profil → 200, „Schritt 1 von 4"; `onboarding_shown_at` unverändert. Hinweis: Der Link heißt „Einführung ansehen", nicht „Einführung erneut ansehen" (Hinweis H-1) |
| AK-10 | ✅ | Gast: alle vier Schritte und beide POST-Wege → **302 `/de/login`** bzw. `/fr/login`, Antwort 366 Byte ohne Inhalt der Einführung; unbekannter Schritt → 404 |
| AK-11 | ✅ | Unbestätigt angemeldet: GET `einfuehrung`, `einfuehrung/eintraege` → **302 `/de/verify`**; POST Newsletter und Profilbild ebenfalls → `/de/verify`, nichts gespeichert (`Qa12OnboardingTest::testAk11AbsendewegeVerlangenEineBestaetigteAdresse`) |

### Ablauf

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-12 | ✅ | Fußleisten der vier Bildschirme gelesen: Weiter → `eintraege` → `profilbild` → `newsletter` → Absenden → **302 `/de/restaurants`**; im Browser per Tastatur durchlaufen |
| AK-13 | ✅ | Clara nach Einwilligung in der Einführung und Dora nach Einwilligung bei der Registrierung: „Schritt 1/2/3 von 3", letzter Schritt Profilbild mit „Restaurants suchen"; `/de/einfuehrung/newsletter` → 302 Suche |
| AK-14 | ✅ | „Schritt N von 4" bzw. „von 3" auf jedem Bildschirm, auch im `<title>`; in lb/fr/en/pt übersetzt („Schrëtt 1 vun 4", „Étape 1 sur 4" …) |
| AK-15 | ✅ | Fünf Seitenwechsel über „Weiter"/„Zurück": Kontozeile (`name`, Bild, Einwilligung, Merker, Datum) vorher = nachher, `marketing_contact` 2 → 2, keine Meldung. Upload-Formular ohne Datei → 302, nichts gespeichert |
| AK-16 | ✅ | „Zurück" ab Bildschirm 2, Ziel jeweils der vorige Schritt |
| AK-17 | ✅ | „Einführung beenden" auf allen Bildschirmen → `/de/restaurants`; letzter Bildschirm „Restaurants suchen" (Absendeknopf bzw. Link, wenn das Profilbild letzter ist) |
| AK-18 | ✅ | Kopfzeile auf allen Bildschirmen; bei 375 px untere Leiste sichtbar, vier Ziele je 90 × 60 px (`qa/12/ak18-willkommen-telefon.png`); kein waagerechtes Scrollen bei 375 und 320 px (fr) |
| AK-19 | ⚠️ | **Abnahmekriterium mit einer Person, die Endlech.lu nicht kennt — keine solche Person verfügbar.** Gemessen nur der Umfang: 78 + 98 + 48 + 47 = **271 Wörter**; bei 200 Wörtern/Minute 1,4 min, bei 130 Wörtern/Minute 2,1 min Lesezeit — die Grenze liegt im Bereich |

### Bildschirm 1 bis 3

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-20 | ✅ | „Willkommen bei Endlech.lu, Bert Neu!"; Knöpfe → `/de/community/suggest` (200) und `/de/app` (200); Anzeige vermerkt |
| AK-21 | ✅ | Alle Texte der vier Bildschirme in fünf Sprachen (28 Seiten) auf *bewert, kommentar, favorit, rating, review, avis, évalu, avalia, coment, favori, notez, stern, étoile, estrela* abgesucht: einziger Treffer frz. „comment" (= „wie") |
| AK-22 | ✅ | Punkte a–c in allen fünf Sprachen gelesen; d fehlt (OF-01 offen) |
| AK-23 | ✅ | `qa/12/ak23-begriffe.php` gleicht die Katalogwerte mit dem gerenderten Text ab: „Verifiziertes Lokal", „Nicht verfügbar", „Nicht ausgemessen", „unter 90 cm" stehen in **allen fünf Sprachen** wortgleich auf Einführung und Detailseiten (Restaurants 12, 13, 15) |
| AK-24 | ✅ | „Ein Eintrag zeigt, was wir erfasst haben, nicht was vor Ort gilt." (und Entsprechungen in lb/fr/en/pt) |
| AK-25 | ✅ | JPG, PNG, WebP und 1,92 MB → 302, Bild in Kopfzeile, auf dem Bildschirm und im Profil mit **derselben Datei**; im Browser per Tastatur abgeschickt, Flash „Dein Profilbild ist gespeichert." |
| AK-26 | ❌ | 2,5 MB → **422**, Meldung am Feld (wie im Profil), nichts gespeichert, Weiter vorhanden. **27 MB (über `post_max_size`) → 422 ohne jede Meldung** — siehe BUG-05. Die Größenmeldung selbst ist unvollständig („maximal 2 groß") — BUG-09 |
| AK-27 | ✅ | `bild.jpg` mit Text → 422 „Bitte lade ein Bild im Format JPG, PNG oder WebP hoch."; ebenso PHP-Code als `shell.jpg` und GIF; Bild unverändert |
| AK-28 | ✅ | „Dein Bild erscheint nur in deinem Profil und oben in der Kopfzeile … Andere sehen es nicht." in fünf Sprachen; Gegenprobe im Code: Avatare erscheinen nur in Kopfzeile, Profil, Einführung, `/api/v1/me`, Export |
| AK-29 | ✅ | Vier Uploads hintereinander: angezeigt wird jeweils das neueste, im Verzeichnis bleibt **eine** Datei (alte gelöscht) |

### Bildschirm 4 und Profil

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ | `<input type="checkbox" … name="onboarding_newsletter[consent]" value="1">` ohne `checked`; im Browser `checked=false` |
| AK-31 | ✅ | Text in fünf Sprachen gelesen: Neuigkeiten, einmal Willkommens-Mail, Abbestellen im Profil oder über den Link in jeder Mail, ohne Häkchen geschieht nichts |
| AK-32 | ✅ | Ohne Häkchen → 302 Suche; `marketing_consent_at` NULL, **0** Zeilen in `marketing_contact` — es gibt nichts, was der Abgleich übertragen könnte |
| AK-33 | ⚠️ | **Anwendungsseite belegt, Brevo nicht erreichbar.** Einwilligung mit Zeitpunkt am Konto, Auftragsbuch `origin=account`, `locale=de`, `pending`; Zeitplan `*/5` (`debug:scheduler`). Der echte `MarketingSyncService` schickt an einen Brevo-Nachbau genau `email`, `ext_id`, `listIds:[7]` und die fünf Attribute `CONTACT_NAME, FUNNEL_STATUS, LOCALE, ORGANISATION, ORIGIN=ACCOUNT` (`testAk33Ak40Ak56…`). Ob der Kontakt in Brevo steht, ist ohne Zugang nicht prüfbar |
| AK-34 | ✅ | Profil: „Nicht abonniert" + „Newsletter abonnieren"; danach „Abonniert seit 22.10.2026" + „Newsletter abbestellen" |
| AK-35 | ❌ | Erstes Abonnieren im Profil wie AK-33. **Nach einem Abbestellen** hebt erneutes Abonnieren die Sperre bei Brevo nicht auf — BUG-01 |
| AK-36 | ⚠️ | **Anwendungsseite belegt.** „Nicht abonniert", Einwilligung NULL, Zeile `blacklist_pending`; der nächste Lauf schickt `PUT …?identifierType=ext_id` mit **nur** `{"emailBlacklisted":true}` (`testAk36AbbestellenSchicktNurDieSperre`). Dass Brevo danach nichts mehr zustellt, ist ohne Zugang nicht prüfbar. Zwei Randfälle, in denen die Sperre Brevo nie erreicht: BUG-03, BUG-06 |
| AK-37 | ✅ | Webhook am laufenden Server mit lokalem Geheimnis: `{"event":"unsubscribed","email":"clara.neu@…"}` → 200; Profil vorher „Abonniert seit …", danach **„Nicht abonniert"**. Falsches Geheimnis → 401 |
| AK-38 | ✅ | `unverified@` abonniert: Einwilligung gespeichert, **0** Kontakte, Hinweis „Wird wirksam, sobald du deine E-Mail-Adresse bestätigt hast."; nach Bestätigung über `/pt/verify/…` Kontakt `account`, `pt`, `pending` |
| AK-39 | ✅ | Anna ohne Einwilligung ändert ihren Namen → 302, gespeichert; Vorschlag und Zustimmung mit einwilligungslosem Fixture-Konto in der vollen Suite grün (`CommunityControllerTest::testVerifiedUserCanSubmitSuggestion`, `BoardControllerTest::testZustimmenIstUmschaltbarUndZaehltEinmal`) |

### Willkommens-Mail (Plattformeinstellung T06)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-40 | ⚠️ | **Keine Automation, kein Postfach** (T06 offen, OF-04). Anwendungsseite: `LOCALE` folgt der Anfrage (fr-Registrierung → `fr`, pt-Bestätigung → `pt`, Einwilligung unter `/fr/…` → Rumpf `LOCALE: fr`) |
| AK-41 | ⚠️ | Braucht die Mail. Der Weg ab dem Webhook ist unter AK-37 belegt |
| AK-42 | ✅ | Ohne Einwilligung entsteht weder Auftrag noch Kontakt (AK-32); eine Adresse, die nie bei Brevo ankommt, kann von dort keine Mail bekommen |
| AK-43 | ⚠️ | Hängt an der Bedingung `ORIGIN = ACCOUNT` in Brevo. Anwendungsseite: Wartelisten-Kontakte tragen `origin=app/partner/…` |
| AK-44 | ⚠️ | „Nur einmal" ist eine Einstellung der Automation. Anwendungsseite: Abbestellen sperrt statt zu löschen (AK-36) |
| AK-45 | ⚠️ | Einstellung der Automation |
| AK-46 | ⚠️ | Anwendungsseite: nie übertragene Zeile bleibt lokal gesperrt, übertragene bekommt den Sperrauftrag. Ob Brevo eine bereits geplante Mail an einen gesperrten Kontakt zurückhält, ist nicht prüfbar. Randfall BUG-06 |
| AK-47 | ⚠️ | Inhalt der Mail in Brevo |
| AK-48 | ⚠️ | Inhalt der Mail in Brevo; Attributliste der Anwendung unverändert (AK-56) |

### Verwaltung, Messung, Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-49 | ✅ | `/de/admin` als Admin: Kachel „Einführung 4 / 6 — 6 Konten mit Einführung · 4 davon angezeigt", SQL `sum(onboarding_due)=6`, `…shown…=4`; keine Namen in der Kachel. Als Nutzer `/de/admin` → 403 |
| AK-50 | ✅ | Browser, `window.umami.track` umhüllt: Willkommensseite → `einfuehrung_begonnen` (ohne Daten); „Einführung beenden" auf Schritt 2 → `einfuehrung_beendet {"schritt":"2"}`; Absenden auf Schritt 4 → `einfuehrung_abgeschlossen`. „Weiter", Upload, Häkchen: kein Ereignis |
| AK-51 | ✅ | Was die Anwendung an Umami weiterreicht, am Fänger mitgeschrieben (`qa/12/umami-weiterleitung.jsonl`): nur Name, bei „beendet" `schritt`. Abgewiesen mit 400: `schritt` = Adresse, Zusatzschlüssel `email`, `abonniert`, `schritt=5`, Profilpfad; Abfragezeichenfolge entfernt |
| AK-52 | ✅ | Profil: kein Zählskript im HTML; Abbestellen im Browser → **0** Ereignisse; `/api/send` mit Profilpfad → 400 |
| AK-53 | ✅ | Migration fügt genau `onboarding_due` und `onboarding_shown_at` hinzu; `schema:update --dump-sql` ohne Rest zu diesen Spalten; der Controller schreibt nichts in die Sitzung außer Flash-Meldungen. (Neufassung OF-05 vom Betreiber noch unbestätigt) |
| AK-54 | ✅ | Fokussierbare Elemente je Bildschirm im Browser gezählt: Willkommen/Einträge nur Links, Profilbild Datei + Knopf, Newsletter Häkchen; sonst nur CSRF-Felder |
| AK-55 | ✅ | Voller Durchlauf im **Produktionsmodus** (Upload „Mein Name Anna Neu privat.jpg", Abo, Abbestellen, Abo): 0 Treffer für Adresse, Name, Dateiname (`qa/12/prod-protokoll.ausgabe.txt`). Im DEBUG-Protokoll der Entwicklung stehen Adressen in Rahmenkanälen — Hinweis H-3 |
| AK-56 | ✅ | Rumpf an Brevo (siehe AK-33) ohne `onboarding`, `einfuehrung`, `avatar`, `uploads`, Weg der Einwilligung |
| AK-57 | ✅ | Anna schickt `user=<Admin-ID>`, `onboarding_newsletter[user]`, `email=admin@…` an Newsletter-Schritt und Upload: nur Annas Zeile ändert sich, Admin vorher = nachher. ⚠ Eine andere Art, fremde Daten zu ändern, ohne Kennung: BUG-02 |
| AK-58 | ✅ | Fremde Herkunft bzw. ohne Herkunft: Newsletter-Schritt 422, Upload 422, Profilschalter ohne gültiges Token 302 mit Fehlermeldung; Kontozeile unverändert. (Ohne Meldung in der Einführung: BUG-08) |
| AK-59 | ✅ | Am laufenden Server mit dem Produktionswert: Wechsel 1–10 durch, **11. und 12. → „Zu viele Änderungen. Bitte versuch es in 60 Minuten erneut."**, Stand unverändert; Newsletter-Schritt danach ebenfalls abgewiesen (gemeinsames Kontingent) — `qa/12/ak59-deckel.ausgabe.txt`. Andere IP: `ProfileNewsletterTest::testAk59ElfterWechselScheitertAuchVonAndererAdresse` (Schlüssel = Konto) |
| AK-60 | ✅ | Abo im Profil, danach Häkchen in der Einführung ohne Wechsel: Einwilligung unverändert; anschließend Wechsel 2–10 durch, 11. gesperrt — der Leerlauf hat nichts verbraucht |
| AK-61 | ✅ | Ines (Einführung gesehen, Abo, Bild, übertragener Kontakt) löscht ihr Konto: Zeile weg, **Bilddatei weg**, Kontakt `removal_pending`; Anmeldung danach scheitert |
| AK-62 | ✅ | `/de/profile/daten` → JSON mit `onboardingDue: true`, `onboardingShownAt`, `marketingConsent`, `marketingConsentAt` (= Datenbank) |
| AK-63 | ✅ | Produktionsmodus mit untergeschobenem Schlüssel `xkeysib-qa12attrappe…`: alle vier Bildschirme und das Profil → **0** Treffer |

### Sprachen und Zugänglichkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-64 | ✅ | Alle vier Bildschirme in lb, fr, en, pt gelesen — kein deutscher Text. 51 neue Schlüssel in allen fünf Katalogen; gleich wie de nur „Newsletter" und drei luxemburgisch identische Wörter; pt ohne „você" |
| AK-65 | ✅ | Sprachumschalter auf `/de/einfuehrung/profilbild` verweist auf `/{lb,fr,en,pt}/einfuehrung/profilbild`, auf `/de/einfuehrung` auf `/{…}/einfuehrung`; `/fr/einfuehrung/newsletter` → „Étape 4 sur 4" |
| AK-66 | ✅ | Browser, nur Tastatur: von der Willkommensseite bis zur Suche (Tab/Enter, Leertaste fürs Häkchen, Upload über Enter auf „Bild hochladen"); Tab-Reihenfolge = sichtbare Reihenfolge auf jedem Bildschirm; **jedes** Bedienelement zeigt beim Fokus einen 2-px-Rahmen (`outline: solid 2px`, `:focus-visible`, gemessen bei `document.hasFocus() === true`). Den Dateidialog des Betriebssystems bedient die Vorschau nicht — die Datei wurde gesetzt, abgeschickt per Tastatur |
| AK-67 | ⚠️ | **Kein Screenreader in der Umgebung.** Belegt ist die Mechanik: nach jedem Seitenwechsel liegt der Fokus auf `#einfuehrung-titel` („Schritt N von M" + Titel); nach einem abgewiesenen Upload liegt er auf dem Feld, `aria-invalid="true"`, `aria-describedby` nennt Hilfe und Fehler |
| AK-68 | ✅ | 375 px: alle Knöpfe und Links ≥ 44 × 44 (kleinster 99 × 48 „Weiter", 162 × 44 „Einführung beenden"). Das Häkchen selbst misst 20 × 20, sein Label 212 × 68 — Hinweis H-2 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | Bert: Anzeige auf „Gerät A" (eigener Cookie-Speicher), Anmeldung auf „Gerät B" → `/de/` |
| EC-02 | ✅ | Upload mit `Content-Length` 1,9 MB, nach 40 kB Verbindung geschlossen (`qa/12/abbruch.php`, Server `:8098`): Bild unverändert, Bildschirm danach 200 |
| EC-03 | ✅ | Alle Abos dieses Durchlaufs liefen ohne erreichbares Brevo: Flash „Du hast den Newsletter abonniert.", Zeile `pending`; Wiederholung nach 429: `Feature12SperreSyncTest::testAk36FehlschlagBehaeltDenAuftrag` |
| EC-04 | ✅ | `user@` angemeldet, Janas Link geöffnet → `/de/login` → `/de/`, `user@` bleibt angemeldet, sein Merker 0; Janas erste Anmeldung → `/de/einfuehrung` |
| EC-05 | ✅ | Abgelaufener Link → 302 `/de/verify`, Konto unbestätigt, Merker 0 |
| EC-06 | ✅ | Kurt meldet sich mit „Angemeldet bleiben" an (fällig), Sitzungs-Cookie verworfen, nur `REMEMBERME`: `/de/restaurants` 200, `/de/` 200, `/de/profile` 200 — keine Umlenkung, `shown_at` bleibt `NULL` |
| EC-07 | ✅ | Lena: bestätigt, nie angemeldet, Passwort zurückgesetzt → erste Anmeldung mit neuem Passwort → `/de/einfuehrung` |
| EC-08 | ✅ | siehe AK-60 |
| EC-09 | ✅ | Neustart über das Profil: Ereignis `einfuehrung_begonnen` erneut im Browser, `onboarding_shown_at` und Kachel unverändert |
| EC-10 | ✅ | Kurt löscht sein Konto in Sitzung B; Sitzung A: nächster Schritt → 302 `/de/login`, Newsletter absenden → 302 `/de/login`; keine Zeile, kein Kontakt |

## Regression

Volle Testsuite: `php bin/phpunit` → **1514 Tests, 8911 Assertions, 0 Fehlschläge, 17 übersprungen** (36 s).
Ausgangsstand vor diesem Bericht: 1504 Tests, 0 Fehlschläge, 10 übersprungen — die zehn neuen sind die Prüfläufe
dieses Berichts. Zusätzlich: `lint:twig` grün; `lint:container` scheitert wie im Abschlussbericht beschrieben am
WebAuthn-Alias (vorbestehend, keine Datei dieses Features); `schema:update --dump-sql` ohne Rest zu den neuen Spalten.

Berührt (Arbeitsstand gegen `HEAD`; `git diff --name-only main...HEAD` zeigt nur den AssetMapper-Umbau, auf dem der
Branch aufsetzt): `EmailVerificationController` (B01), Anmelde-Weiche (B02), `ProfileController`, `ProfileType`,
Profil-Vorlage (B04), Verwaltungsübersicht (B19), `AccountDataExporter` (01), Brevo-Abgleich in fünf Klassen (04),
`SeoRegistry` (10), `UsageEventCatalogue` (11), `security.yaml`, `framework.yaml`.

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B01 | Bestätigungslink setzt Merker, neue Weiterleitung | ✅ | Registrierung → Link → `/de/login`, Konto bestätigt (`curl` und Browser) |
| B02 | Weiche nach der Anmeldung | ✅ mit `curl`, ⚠ im Browser vorbestehend gestört | Anmeldung `user@` → `/de/`. **Im Browser nach einer Registrierung in derselben Sitzung „Ungültiges CSRF-Token" — BF-156, vorbestehend (Produktion gleich ausgezeichnet)** |
| B03 | Passkey-Weg läuft durch dieselbe Weiche | ✅ (Suite) | `SecurityControllerTest`, `PasskeyControllerTest` grün; Weiche vergleicht nur den Pfad |
| B04 | Profil, Avatar-Regel ausgelagert | ✅ ohne Bild, ⚠ mit Bild vorbestehend gestört | Name ändern, Bild im Profil hochladen → 302; 2,5 MB im Profil dieselbe Meldung wie in der Einführung. **Mit Bild: „Änderungen speichern" ohne Wirkung — BF-164, vorbestehend seit 2026-03-17** |
| B11 | Ziel der Willkommensseite, Rückweg nach Anmeldung | ✅ | `/de/community/suggest` 200; gemerkter Rückweg (AK-03) |
| B19 | Kachel in der Übersicht | ✅ | `/de/admin` 200 als Admin, 403 als Nutzer |
| 01 | Export und Löschung | ✅ | Export (AK-62), Löschung (AK-61) |
| 04 | Auftragsbuch, Mapper, Client, Lauf | ✅ | App-Warteliste mit Werbe-Einwilligung → Bestätigung → Kontakt `app`, `de`, `pending`; Webhook (AK-37); alle Marketing-Tests der Suite grün. ⚠ BF-154 betrifft auch 04/AK-45 (siehe BUG-01) |
| 08 | Ziel der Willkommensseite | ✅ | `/de/app` 200, Eintragung mit Bestätigung |
| 10 | `SeoRegistry` | ✅ | `/sitemap.xml` 200, keine Einführungsseite darin; `SeoRouteCoverageTest` grün |
| 11 | drei neue Ereignisse | ✅ | Weiterleitung an den Fänger (AK-51); Profil weiter ungemessen |

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden für Kennungen; **BUG-02** ohne Kennung | Fremde Konto-ID in Formularen ändert nichts (AK-57). Aber: unbestätigtes Konto mit fremder Adresse löscht die Warteliste-Einwilligung des Inhabers und stellt einen Sperrauftrag (`testBf155…`, am laufenden Server nachgestellt: `app_waitlist_entry.marketing_consent_at` → NULL, Kontakt `blacklist_pending`) |
| Rate Limit greift | bestanden | `newsletter_toggle`: 10 durch, 11. abgewiesen, am Konto (AK-59); Upload ohne Deckel wie spezifiziert, jeder Upload ersetzt die alte Datei (kein Zuwachs auf der Platte) |
| PII in Logs | bestanden (Produktionsmodus) | 0 Treffer nach vollem Durchlauf; Hinweis H-3 zum Schlüssel des Limiters |
| PII an externe Dienste | bestanden | Brevo-Rumpf am HTTP-Client (AK-33/56); Umami-Rumpf am Fänger (AK-51) — `ip` ist die Besucheradresse nach Feature 11, Entscheidung dort |
| Zugriffsregeln serverseitig | bestanden | Gast → Anmeldung (AK-10); unbestätigt → `/verify` auch für POST (AK-11); `/de/admin` als Nutzer 403 |
| CSRF | bestanden, Meldung fehlt | Fremde Herkunft → 422/Fehlerflash, nichts geändert (AK-58); in der Einführung ohne sichtbare Meldung (BUG-08) |
| Eingaben | bestanden | Name `<script>alert(1)</script><b>X</b>` erscheint im Gruß maskiert (`&lt;script&gt;…`, 0 Treffer für das rohe Tag); Upload: falsche Endung, PHP-Code als `.jpg`, GIF, 2,5 MB, 27 MB, abgebrochene Verbindung — nie 500, nie gespeichert (Meldung fehlt bei 27 MB: BUG-05) |
| Löschen | bestanden | Konto, Merker, Datum, Einwilligung, Bilddatei weg, Brevo-Löschauftrag gestellt (AK-61) |
| Geheimnisse im Repository | bestanden | Arbeitsstand und Verlauf (`git log -S 'xkeysib-'`) ohne echten Schlüssel; einzige Fundstelle ist die Attrappe `xkeysib-f12-pruefschluessel` in `OnboardingControllerTest`. Untergeschobener Schlüssel erscheint in keiner Seite (AK-63) |

## Fehler

### BUG-01 · Wieder-Einwilligung hebt die Sperre bei Brevo nicht auf — hoch (BF-154)

**Betrifft:** AK-35; design.md Entscheidung 6; mittelbar 04/AK-45
**Reproduktion:**
1. Konto abonniert im Profil, der Lauf überträgt den Kontakt.
2. Konto bestellt ab → `blacklist_pending` → der Lauf schickt `PUT /contacts/{id}` `{"emailBlacklisted":true}`.
3. Konto abonniert erneut (in einer neuen Sekunde). Der Mapper baut `emailBlacklisted: false` in den Rumpf.
4. Der Lauf schickt `PUT /contacts/{id}?identifierType=ext_id` mit **nur** `attributes` und `listIds`; Brevo 204,
   Zeile `synced`.
**Erwartet:** Der PUT trägt `emailBlacklisted: false`, der Kontakt ist bei Brevo wieder zustellbar.
**Tatsächlich:** Das Feld fehlt im PUT; der Kontakt bleibt gesperrt. Profil „Abonniert seit …", Brevo stellt weder
Kampagne noch Willkommens-Mail zu, keine Fehlermeldung. `QA12_REPRO=1 php bin/phpunit --filter testBf154` →
`Der PUT muss die Sperre aufheben` (`qa/12/reproduktionen.ausgabe.txt`).
**Ort:** `src/Marketing/BrevoContactClient.php:128–137` (`$update` übernimmt nur `attributes`, `listIds`); die
Prüfläufe des Baus prüfen nur den Mapper (`Feature12PayloadMapperTest`) und den Sperr-PUT.
**Vorschlag:** `emailBlacklisted` aus dem Rumpf in `$update` übernehmen und den PUT-Rumpf am `MockHttpClient` prüfen.

**Behoben 2026-10-23** (`/sdd-build 12`): `BrevoContactClient::upsert()` übernimmt `emailBlacklisted` auf den Aktualisierungsweg, sofern der Mapper es gesetzt hat. Test: `Qa12OnboardingTest::testBf154WiederEinwilligungHebtDieSperreBeiBrevoAuf` (scharf, vorher rot, jetzt grün).

### BUG-02 · Ein unbestätigtes Konto nimmt die Einwilligung des Adressinhabers zurück — hoch (BF-155)

**Betrifft:** Sicherheit (Zugriff), Umfeld AK-57; design.md Entscheidung 5
**Reproduktion** (am laufenden Server):
1. `opfer@example.org` steht bestätigt mit Werbe-Einwilligung auf der App-Warteliste, Kontakt `synced`.
2. Jemand registriert ein Konto mit `opfer@example.org` (Adresse nie bestätigt) und meldet sich an (B01/AK-13).
3. Profil → „Newsletter abonnieren" (wirkt nur am eigenen Konto), dann „Newsletter abbestellen".
**Erwartet:** Nur die eigene Einwilligung des unbestätigten Kontos fällt; Warteliste und Kontakt bleiben.
**Tatsächlich:** `app_waitlist_entry.marketing_consent_at` → **NULL** (Einwilligungsnachweis nach Art. 7 Abs. 1
gelöscht), Kontakt `blacklist_pending` → der nächste Lauf sperrt den Inhaber bei Brevo.
`testBf155UnbestaetigtesKontoNimmtFremdeEinwilligungNicht`.
**Ort:** `src/Marketing/MarketingContactRegistry.php:463–487` (`revokeForAccount()` ohne Prüfung von `isVerified()`),
erreichbar über `ProfileController::newsletter()` für unbestätigte Konten (AK-38).
**Vorschlag:** Bei unbestätigtem Konto nur dessen eigene Einwilligung leeren — Quellen und Kontakt der Adresse nur
anfassen, wenn die Adresse belegt ist. (Derselbe Weg kann einen `removal_pending`-Auftrag in eine Sperre umwandeln.)

**Behoben 2026-10-23**: `MarketingContactRegistry::revokeForAccount()` nimmt bei einem unbestätigten Konto nur dessen eigene Einwilligung zurück; Quellen und Kontakt der Adresse bleiben unberührt. Test: `Qa12OnboardingTest::testBf155UnbestaetigtesKontoNimmtFremdeEinwilligungNicht`.

### BUG-03 · Ein Adresswechsel verwirft den Sperrauftrag — mittel (BF-157)

**Betrifft:** AK-36 (Randfall)
**Reproduktion:** abonnieren → Lauf → abbestellen (`blacklist_pending`) → vor dem nächsten Lauf eine
Adressänderung bestätigen (`/verify/email-change/…`) → Lauf.
**Erwartet:** Die Sperre erreicht Brevo.
**Tatsächlich:** `changeEmail()` setzt den Zustand auf `pending`; die Zeile ist gesperrt, `findOpenForSync()` holt sie
nicht mehr ab, Brevo bekommt nie `emailBlacklisted:true` — der Kontakt bekommt weiter Kampagnen.
`testBf157AdresswechselLaesstDenSperrauftragStehen`. Dasselbe gilt vorbestehend für `removal_pending`.
**Ort:** `src/Marketing/MarketingContactRegistry.php:211` `changeEmail()` (beide Zweige setzen `PENDING`).
**Vorschlag:** `blacklist_pending` und `removal_pending` beim Adresswechsel stehen lassen.

**Behoben 2026-10-23**: `changeEmail()` lässt `blacklist_pending` und `removal_pending` stehen (beide Zweige, `zustandNachAdresswechsel()`). Test: `Qa12OnboardingTest::testBf157AdresswechselLaesstDenSperrauftragStehen`.

### BUG-04 · Turbo lädt „Einführung ansehen" beim Überfahren vor — und das zählt als Anzeige — mittel (BF-158)

**Betrifft:** AK-02, AK-03 (Kehrseite), AK-49 (Zählung „angezeigt")
**Reproduktion** (Browser): Konto nach AK-03 fällig (auf der Vorschlagsseite gelandet), Profil öffnen, Maus über den
Link „Einführung ansehen" → Turbo holt `/de/einfuehrung` mit `X-Sec-Purpose: prefetch` →
`onboarding_shown_at = 21:23:35`, ohne dass die Seite je gezeigt wurde. Nächste Anmeldung → Startseite.
**Erwartet:** Ein Vorabladen ist keine Anzeige.
**Ort:** `OnboardingController::show()` vermerkt bei jedem GET; kein `data-turbo-prefetch="false"` im Projekt.
**Vorschlag:** Vorabladen erkennen (`X-Sec-Purpose`) oder den Link vom Vorabladen ausnehmen.
`testBf158VorabladenVermerktKeineAnzeige`.

**Behoben 2026-10-23**: `OnboardingController::show()` vermerkt nichts bei `X-Sec-Purpose`/`Sec-Purpose`/`Purpose: prefetch`; der Profillink trägt zusätzlich `data-turbo-prefetch="false"` (sonst käme beim echten Klick die vorab geholte Antwort und die Anzeige nie beim Server an). Test: `Qa12OnboardingTest::testBf158VorabladenVermerktKeineAnzeige`; von Hand per `curl`: mit Kopf `onboarding_shown_at` NULL, ohne gesetzt.

### BUG-05 · Ein Bild über `post_max_size` ergibt 422 ohne jede Meldung — mittel (BF-159)

**Betrifft:** AK-26 (auch Profil, B04); Muster BF-58
**Reproduktion:** Server mit den Grenzen des Produktionsbildes (`post_max_size=10M`), 27-MB-PNG in der Einführung
hochladen → 422, Feldfehler leer, keine Meldung auf der Seite; im Profil identisch.
**Erwartet:** Dieselbe Meldung am Feld wie bei 2,5 MB.
**Tatsächlich:** PHP verwirft den Rumpf, Symfony hängt den Fehler an das Formular, die Vorlagen zeigen nur Feldfehler.
Telefonfotos über 10 MB sind keine Seltenheit.
**Ort:** `templates/onboarding/profilbild.html.twig`, `templates/profile/index.html.twig`; gelöst ist der Fall in
`AdminRestaurantController.php:157` (BF-58).
**Vorschlag:** Wie BF-58 behandeln. `testBf159UebergrosserUploadZeigtEineMeldung`.

**Behoben 2026-10-23** (Einführung): Wie BF-58 erkannt (leerer Rumpf, `CONTENT_LENGTH` > 0), die Meldung aus `profile.avatar_max_size` steht am Feld, 422. Test: `Qa12OnboardingTest::testBf159UebergrosserUploadZeigtEineMeldung`; von Hand mit 27 MB gegen `post_max_size=8M`: 422, „Das Profilbild darf maximal 2 MB groß sein." ⚠ **Die Profilhälfte ist nicht behoben** — sie gehört zu B04 (`ProfileController::edit()`), nicht in diesen Auftrag.

### BUG-06 · Drei Wechsel in einer Sekunde machen eine Abmeldung im Auftragsbuch wirkungslos — mittel (BF-160)

**Betrifft:** AK-36, AK-46
**Reproduktion:** im Profil abbestellen, abonnieren, abbestellen innerhalb derselben Sekunde (am Server beim Zählen
des Deckels zufällig entstanden: Hanna, Wechsel 8–10).
**Erwartet:** Zeile gesperrt.
**Tatsächlich:** `consentMomentFor()` legt die Einwilligung eine Sekunde **hinter** die vorige Sperre; die folgende
Abmeldung derselben Sekunde liegt davor → `consent_at 21:26:39 > revoked_at 21:26:38`, `pending`, am Konto NULL.
Der nächste Lauf trüge die Adresse bei Brevo als Abonnent ein. `testBf160SchnellesUmschaltenEndetGesperrt`
(zeitabhängig).
**Ort:** `MarketingContactRegistry::consentMomentFor()` / `revokeForAccount()`.
**Vorschlag:** Beim Abbestellen `revoked_at` mindestens auf `consent_at` setzen bzw. die Reihenfolge nicht über
Sekunden, sondern über den Zustand entscheiden.

**Behoben 2026-10-23**: `revokeForAccount()` setzt `revoked_at` mindestens auf `consent_at`. Test: `Qa12OnboardingTest::testBf160SchnellesUmschaltenEndetGesperrt`.

### BUG-07 · Nach AK-05 steht auf der Willkommensseite „Du kannst dich jetzt anmelden." — niedrig (BF-161)

**Betrifft:** AK-05
**Reproduktion:** angemeldet und unbestätigt den eigenen Link öffnen → Willkommensseite mit Flash „Deine
E-Mail-Adresse wurde erfolgreich bestätigt! Du kannst dich jetzt anmelden."
**Ort:** `EmailVerificationController.php:90` (Flash vor der Weiche). **Vorschlag:** eigener Text für den
angemeldeten Zweig.

**Behoben 2026-10-23**: Im angemeldeten Zweig keine Meldung „Du kannst dich jetzt anmelden"; die Willkommensseite sagt selbst, dass die Adresse bestätigt ist. Test: `Feature12BugfixTest::testBf161…` (vorher rot).

### BUG-08 · CSRF-Fehler in der Einführung: 422 ohne Meldung, ohne Fokus — niedrig (BF-162)

**Betrifft:** AK-58, AK-67
**Reproduktion:** Newsletter-Schritt mit fremder Herkunft absenden → 422, derselbe Bildschirm, keine Meldung; die
Überschrift verliert `autofocus`, kein Feld bekommt ihn.
**Ort:** `templates/onboarding/*` rendern keine Formularfehler der Wurzel. **Vorschlag:** `form_errors()` der Wurzel
anzeigen.

**Behoben 2026-10-23**: Der Rahmen zeigt Formularfehler der Wurzel in `role="alert"` mit `autofocus`. Test: `Feature12BugfixTest::testBf162…` (vorher rot); von Hand: 422 mit „Der CSRF-Token ist ungültig. …" in der Meldung.

### BUG-09 · Größenmeldung ohne Einheit: „Das Profilbild darf maximal 2 groß sein." — niedrig (BF-163)

**Betrifft:** AK-26 (Text; Profil gleich, B04)
**Reproduktion:** 2,5-MB-Bild hochladen. Ebenso „ne doit pas dépasser 2.", „must not exceed 2." …
**Ort:** `translations/validators.*.yaml:39` nutzt `{{ limit }}` ohne `{{ suffix }}`. **Vorschlag:** `{{ suffix }}`
ergänzen.

**Behoben 2026-10-23**: `{{ suffix }}` in `validators.*.yaml` (fünf Sprachen) — wirkt auch im Profil. Test: `Feature12BugfixTest::testBf163…` (vorher rot).

### Vorbestehend, beim Prüfen dieses Features gefunden (nicht durch Feature 12 verursacht)

**BF-156 · B02 · hoch — Nach einem Formular mit Double-Submit scheitert die Anmeldung in derselben Sitzung.**
Die Registrierung schickt ihr Token per Double-Submit (`data-controller="csrf-protection"`); Symfony merkt sich das
in der Sitzung und verlangt es danach für jede zustandslose Prüfung. Die Anmeldeseite fordert den (lazy geladenen)
Controller nicht an — eine frisch geladene Anmeldeseite, etwa nach dem Bestätigungslink, schickt nur die Herkunft.
Im Browser: Registrierung → Link → Anmeldung → **„Ungültiges CSRF-Token."**, auch beim zweiten Versuch;
`dev.log`: *„double-submit info was used in a previous request but is now missing"*
(`qa/12/b02-csrf-browser.ausgabe.txt`). Produktion per GET gelesen: dieselbe Auszeichnung, Controller im
Encore-Bündel ebenfalls lazy; auf der Produktion nicht nachgestellt (kein Konto angelegt). Ort:
`templates/security/login.html.twig:50`, ebenso Abmeldeformulare (`base.html.twig:254`, `profile/index.html.twig:212`).
Reproduktion `testBf156AnmeldungNachDoubleSubmitInDerselbenSitzung`.

**BF-164 · B04 · hoch — Mit Profilbild lässt sich das Profil im Browser nicht speichern.**
`templates/profile/index.html.twig:108–113` setzt das Formular „Profilbild entfernen" **in** das Profilformular
(84–126). Der Browser verwirft das innere `<form>`, das innere `</form>` schließt das äußere. Gemessen im Browser
(Anna mit Bild): „Änderungen speichern" und `profile[_token]` gehören zu keinem Formular — Klick ohne Anfrage, Name
unverändert; „Profilbild entfernen" schickt das Profilformular an `/de/profile/edit` (ohne Rückfrage), das Bild
bleibt. Seit `d132140` (2026-03-17) auf `main`. Ohne Bild unauffällig, deshalb grün in jedem Prüflauf ohne Browser.

## Hinweise (keine Befunde)

- **H-1** · AK-09 nennt den Link „Einführung erneut ansehen", gebaut ist „Einführung ansehen" (design.md). Sachlich
  richtiger, weil auch Bestandskonten ihn sehen — die Spec sollte nachziehen.
- **H-2** · Das Newsletter-Häkchen misst 20 × 20 px; das zugehörige Label (212 × 68) schaltet es mit. AK-68 spricht
  von Knöpfen; WCAG 2.5.8 ist über das Label erfüllt.
- **H-3** · Der Schlüssel des Limiters ist die Adresse (`newsletter_toggle-<Adresse>`); Symfonys Lock-Kanal schreibt ihn
  auf DEBUG. In Produktion nur bei einem Fehler in derselben Anfrage (fingers_crossed, `lock` nicht ausgeschlossen) —
  wie die vorbestehende Zeile `security.DEBUG User was reloaded … <Adresse>`.
- **H-4** · Ein `blacklist_pending`-Auftrag, der fünfmal scheitert, wird nicht mehr versucht (`MAX_ATTEMPTS`), und nichts
  meldet es — wie bei Löschaufträgen aus Feature 04, nun aber für einen Widerruf.
- **H-5** · „Einführung begonnen" zählt auch bei „Zurück" auf Bildschirm 1 und bei Turbos Vorschau aus dem Cache erneut.
- **H-6** · „Abonniert seit" zeigt das Datum in UTC und in jeder Sprache als `d.m.Y`.
- **Code-Review:** Der `code-reviewer` lieferte sechs Funde und sieben unbestätigte Punkte. Bestätigt und als Befund
  aufgenommen: BUG-01, -02, -03, -04, -07, -08, und aus den unbestätigten BUG-05 und BUG-06 (beide am Server
  nachgestellt). Nicht nachstellbar: gleichzeitiges doppeltes Abonnieren → Unique-Verletzung (`php -S` arbeitet
  Anfragen nacheinander ab; drei parallele Anfragen ergaben eine Zeile); Wettlauf zwischen Lauf und Anfrage;
  `syncedAt` leer nach einem Zeitüberschreitungs-Erfolg — theoretisch, nicht belegt, deshalb kein Befund.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/Qa12OnboardingTest.php` | `testAk33Ak40Ak56RumpfAnBrevoNachEinwilligungInDerEinfuehrung`, `testAk36AbbestellenSchicktNurDieSperre`, `testAk11AbsendewegeVerlangenEineBestaetigteAdresse` (grün) | AK-33, AK-36, AK-40, AK-56, AK-11 — Rumpf am HTTP-Client eines Brevo-Nachbaus, der Sperren behält |
| dieselbe Datei | `testBf154…`, `testBf155…`, `testBf156…`, `testBf157…`, `testBf158…`, `testBf159…`, `testBf160…` | Reproduktionen; prüfen das richtige Verhalten, waren rot, überspringen sich bis zur Behebung. Scharf: `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12OnboardingTest.php` |

## Nächster Schritt

`/sdd-build 12` mit dem Auftrag, BUG-01 bis BUG-09 (BF-154, BF-155, BF-157 bis BF-163) zu beheben; je Befund die
Zeile `self::markTestSkipped` bzw. den `offen()`-Aufruf der Reproduktion entfernen. Danach erneut `/sdd-qa 12`.

Vorher oder im selben Zug: `/sdd-build B02` (BF-156) und `/sdd-build B04` (BF-164) — beide laufen auf der Produktion,
und beide stehen auf dem Hauptweg dieses Features (erste Anmeldung nach der Registrierung; Profil nach dem Hochladen
in der Einführung).

Beim Betreiber, unabhängig vom Code: T06 (Brevo-Automation, OF-04) — erst danach sind AK-40 bis AK-48 prüfbar;
Bestätigung von OF-05 (AK-53 neu gefasst); OF-01, OF-02, OF-03 bleiben offen. AK-19 braucht eine Person mit Stoppuhr.
