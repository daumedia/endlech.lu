# B04 · Profil, Avatar & eigene Einreichungen — Testbericht

Stand: 2026-11-14 · Geprüft gegen `spec.md` (Rekonstruktion vom 2026-08-23, fortgeschrieben bis 2026-11-14) · in eigener
Session, ohne Bauverlauf · Vorstufe `building` (Fehlerauftrag BF-226) · Branch `feature/17-favoriten-und-listen`,
uncommittet neben Feature 17 · Suchliste: `build-report.md`, Abschnitt „Fehlerauftrag BF-226“

Frühere Fassungen archiviert: `qa/B04/qa-report-bis-2026-11-14-archiv.md` (August und Nachträge aus Feature 12),
`qa/B04/qa-report-nachpruefung-bf182-bis-bf186-2026-11-14.md` (Nachprüfung BF-182, BF-184, BF-186; dort wurden BF-226 bis
BF-229 gefunden). Nachweise dieses Durchlaufs: `qa/B04/QA4-*`.

## Fazit

**Production-ready: ja** — offen bleiben drei niedrige Befunde, keiner davon *hoch* oder *kritisch*.

**BF-226 ist behoben.** Nach einer Meldung am Profil- oder Passwortformular geht die Korrektur mit Turbo wieder an die
POST-Route und wird gespeichert. Geprüft im Browser auf allen fünf Wegen des Berichts:

- Name zu kurz
- Name aus Leerzeichen
- RFC-widrige Adresse
- Bild zu groß
- Passwort zu kurz

Jeder Weg ergab **422 → 302** statt 422 → 405. Dazu drei weitere Wege: zwei Meldungen in Folge, und je eine Meldung am
einen Formular mit anschließendem Absenden des anderen. Geprüft in Chromium und WebKit, in fünf Sprachen, bei 1280 und
375 px. Ohne JavaScript ebenso 422 → 302.

Die Gegenprobe: Mit zurückgenommener Reparatur antwortet der Server wieder mit **405** auf `POST /de/profile`, und beide
Prüfläufe werden rot. Danach ist die Datei wiederhergestellt; die Prüfsumme ist gleich. Alle 19 Kriterien und alle
fünf Randfälle sind bestanden, EC-04 wieder eingeschlossen.

**Neu: BF-230 (*niedrig*).** Der Prüflauf, der BF-175 schützen soll, ist nach jedem früheren Lauf seiner Datei blind:
- Mit zurückgebauter Reparatur von BF-175 stand die Übernahmeadresse am Server wirksam am Konto, und die Anmeldung damit
  gelang.
- Alle 862 Controller-Prüfläufe blieben dabei grün.

Das Produkt ist korrekt (am Server nachgestellt). Der Schutz ist durch einen neuen Prüflauf dieses Durchlaufs wieder
wirksam: Er wird mit demselben Rückbau rot. Dieselbe Ursache machte die Reproduktion von BF-228 bei Wiederholung
falsch grün; sie ist berichtigt. BF-228 ist unverändert offen, BF-227 ebenso.

**Nächster Aufruf:** `/sdd-deploy B04`. Die Reparatur geht mit dem Branch `feature/17-favoriten-und-listen` live, also
mit `/sdd-deploy 17`; BF-182 und BF-184 (HTTP 500) und BF-226 (405) bestehen auf der Produktion bis dahin fort. Nicht
blockierend: `/sdd-klaeren B04` über BF-227, BF-228 und BF-230, dann `/sdd-build B04`.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 19 von 19 (AK-01 bis AK-18, AK-12a; AK-19 bis AK-21 an Feature 18 übergeben, nicht geprüft) |
| davon bestanden | 19 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5, alle bestanden (EC-04 wieder bestanden) |
| Befund des Auftrags behoben | 1 von 1 (BF-226) |
| Offene Befunde, Zustand | BF-227 unverändert, BF-228 unverändert |
| Tests neu geschrieben | 1 (Rückfallschutz BF-175, läuft immer); 1 Reproduktion berichtigt (BF-228) |
| Tests grün | neuer Prüflauf 3 von 3 Läufen grün, mit zurückgebautem BF-175 2 von 2 rot |
| Volle Suite gelaufen | dreimal: 2 345 / 2 345 / **2 346 Tests, 0 Fehlschläge, 44 übersprungen** |
| Regression: berührte Features | 5 (B03, B02, 12, 01, 17), Hauptweg bestanden 5 |

## Der Befund des Auftrags

| Befund | behoben | im Browser (Turbo aktiv) | Gegenprobe |
|---|---|---|---|
| **BF-226** · Korrektur nach Meldung → 405 | ✅ ja | 23 Läufe, alle **422 → 302**, Meldung sichtbar, Eingabe gespeichert. Die Adresse bleibt nach der 422 `/xx/profile`; jedes der sechs Formulare der Seite trägt ein Ziel (`profile` → `/xx/profile/edit`, `change_password` → `/xx/profile/password`, dazu Newsletter, Adresswechsel abbrechen, Bild entfernen, Konto löschen, Abmelden). Keine Konsolenfehler außer der erwarteten 422 | Reparatur zurückgenommen → `testBf226ProfilformularBehaeltNachEinerMeldungSeinZiel`, `…Passwortformular…` **rot** (`null` statt `/de/profile/edit` bzw. `/de/profile/password`); im Browser Name, Passwort, Adresse je **`POST /de/profile → 405`**, nichts gespeichert, ohne JavaScript 302. Wiederhergestellt: md5 `e1595c5f…` wie vor der Prüfung |

Die 23 Läufe im Einzelnen (`qa/B04/QA4-BF226-browser.txt`, Bildschirmfotos `QA4-BF226-*`):

| Browser | Sprache · Breite | JS | Wege |
|---|---|---|---|
| Chromium | de · 1280 | Turbo | die fünf Wege des Berichts; zwei Meldungen in Folge, dann Korrektur; Meldung im Profil, dann Passwort abgeschickt; Meldung im Passwort, dann Profil abgeschickt |
| Chromium | de · 1280 | ohne | die fünf Wege des Berichts (Adresse nach der 422 ist die POST-Route, wie vorher) |
| Chromium | lb · 375 | Turbo | Name zu kurz, RFC-widrige Adresse, Bild zu groß |
| Chromium | pt · 1280, fr · 375, en · 1280 | Turbo | Passwort zu kurz (pt, fr), Name aus Leerzeichen (en) |
| WebKit | de · 1280 / 375 | Turbo | Name zu kurz, Passwort zu kurz, RFC-widrige Adresse, Bild zu groß (375) |

Die Korrektur der RFC-widrigen Adresse ging an eine **gültige neue** Adresse. Damit ist auch der ganze Adressweg nach der
Meldung belegt: Vormerkung gesetzt, Adresse unverändert, Meldung „Fast geschafft …“. Die Korrektur des Passworts ändert
den Hash; die anschließende Anmeldung gelingt mit dem neuen Passwort (AK-10).

## Akzeptanzkriterien im Einzelnen

Geprüft am laufenden Server (Port 8000, Entwicklungsmodus) mit eigenen Konten `qab04c-*@example.test`. Mails wurden aus
der Warteschlange `messenger_messages` gelesen; es lief kein Worker. Nachweise: `qa/B04/QA4-server-nachweise.ausgabe.txt`
(curl) und `QA4-hauptweg.ausgabe.txt` (Browser).

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Gast `GET /{lb,de,fr,en,pt}/profile` → **302** auf `/{locale}/login`; ebenso `POST …/profile/edit`, `…/password`, `…/avatar/delete`, `…/loeschen`, `…/newsletter`, `GET …/profile/daten` |
| AK-02 | ✅ bestanden | angemeldet **200**, `profile` → `/de/profile/edit`, `change_password` → `/de/profile/password`, Abschnitt „Passkeys“ mit sichtbarem Knopf (Chromium, Turbo aktiv), „Meine Einreichungen“. `QA4-hauptweg-AK02-profil-{1280,375}.png`; bei 375 px `scrollWidth` 375/375 |
| AK-03 | ✅ bestanden | curl: 302, „Profil erfolgreich aktualisiert.“, Name viermal im Seitenkopf und Profil, 0 Mails. Browser: dasselbe, Kopfzeile trägt den neuen Namen (`QA4-hauptweg-AK03-AK04-1280.png`) |
| AK-04 | ✅ bestanden | JPEG, PNG, WebP und PNG mit **genau 2 000 000 Bytes** → 302, Datei unter `/uploads/avatars/…` mit 200 und `image/jpeg` / `image/png` / `image/webp`, `<img>` im Profil; im Browser per Dateiauswahl ebenso |
| AK-05 | ✅ bestanden | 2 000 001 Bytes → 422 „… maximal 2000000 bytes …“; 2,6 MB → 422 „… 2 MB“; GIF → 422 „Bitte lade ein Bild im Format JPG, PNG oder WebP hoch.“; Avatar je unverändert |
| AK-06 | ✅ bestanden | vier Uploads nacheinander, danach EC-02: das jeweils vorige Bild **404**, im Verzeichnis genau eine Datei; ebenso nach drei Runden gleichzeitiger Uploads (EC-03) |
| AK-07 | ✅ bestanden | curl mit gültigem Token: „Profilbild erfolgreich entfernt.“, DB `NULL`, Datei **404**; im Browser mit Bestätigungsdialog ebenso |
| AK-08 | ✅ bestanden | `_token` leer, `falsch`, fehlend → je „Ungültiges CSRF-Token. Bitte versuche es erneut.“, Verweis und Datei bleiben (200) |
| AK-09 | ✅ bestanden | falsches aktuelles Passwort → „Das aktuelle Passwort ist nicht korrekt.“, Hash unverändert |
| AK-10 | ✅ bestanden | Grenzwert: 7 Zeichen → 422 „mindestens 8 Zeichen“, ungleich → 422 „stimmen nicht überein“, **8 Zeichen** → „Passwort erfolgreich geändert.“; Anmeldung mit dem neuen 302 → `/de/`, mit dem alten → `/de/login` |
| AK-11 | ✅ bestanden | Anlagedaten von 13, 14, 17 vorübergehend gespreizt → Reihenfolge Burger & Co. (12.11.), Café Nordstad (11.11.), Umami Corner (10.11.), je mit Stadt, „Eingereicht am …“ und „⏳ Ausstehend“. Daten danach zurückgesetzt (`09:31:41`) |
| AK-12 | ✅ bestanden | Wechsel → `email` unverändert, `pending_email` gesetzt, Token 64 Zeichen, Frist **1 440 min**. Zwei Mails: die Bestätigung mit Link an die neue Adresse, die Warnung (`email_change_notice`) ohne Link an die alte. Anmeldung mit der vorgemerkten Adresse → `/login`, mit der bisherigen → `/de/`. Link → Adresse getauscht, Vormerkung leer, Weiterleitung auf `/login` (gewollt, `EmailVerificationController:177`). Danach scheitert die alte Adresse, die neue meldet an |
| AK-12a | ✅ bestanden | vorgemerkte Adresse inzwischen vergeben → „Diese E-Mail-Adresse wird inzwischen bereits verwendet.“, Vorgang abgeräumt; Frist zurückdatiert → „Der Bestätigungslink ist abgelaufen …“, abgeräumt; kein 500 |
| AK-13 | ✅ bestanden | zwei Sitzungen und ein reines `REMEMBERME`-Cookie, vorher je 200. Nach dem Wechsel in Sitzung 1: eigene Sitzung **200**, zweite **302** `/de/login`, `REMEMBERME` allein **302** |
| AK-14 | ✅ bestanden | Versuche 1–5 „nicht korrekt“, **6 und 7 gesperrt**; zweite Sitzung desselben Kontos mit **richtigem** Passwort ebenfalls gesperrt, Hash unverändert; anderes Konto unberührt |
| AK-15 | ✅ bestanden | PHP-Skript als `skript.png`, gesendet als `image/png` → **422** „Bitte lade ein Bild im Format JPG, PNG oder WebP hoch.“, Avatar unverändert |
| AK-16 | ✅ bestanden | `GET /uploads/avatars/<datei>` ohne Cookie → **200** `image/png`; Verzeichnisliste → 404 |
| AK-17 | ✅ bestanden | `/de/profile?user=4&id=4&submittedBy=4` als `user@endlech.lu` → Bella Vista 0, Sushi Zen 0, Umami Corner 1 |
| AK-18 | ✅ bestanden | zwölf verwendete Passwörter (`falsch1`, `Abc12345`, `QaSitzung8`, `rate1` … `rate7`, `Richtig88`, `QaB04neu8`, `kurz7ab`, `user123`) in `var/log/dev.log`: je **0 Treffer** |
| AK-19–21 | — | an Feature `18` übergeben (Auftrag: nicht prüfen) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `admin@endlech.lu` und `ADMIN@ENDLECH.LU` → 422 „Diese E-Mail-Adresse wird bereits verwendet.“, keine Vormerkung. ⚠ Die Spec nennt die Meldung noch „hartkodiert deutsch“; sie ist übersetzt (unverändert seit dem letzten Bericht) |
| EC-02 | ✅ bestanden | PNG ohne Endung als `application/octet-stream` → gespeichert als `….png` |
| EC-03 | ✅ bestanden | drei Runden mit je drei gleichzeitigen Uploads → je genau eine Datei, Verweis 200 |
| **EC-04** | ✅ bestanden | 422 mit erneut gerenderter Seite; Passwortfelder ohne `value` nach Meldung am Profil- **und** am Passwortformular; Sitzung bleibt (200). **Die Korrektur gelingt mit JavaScript** (BF-226, siehe oben) |
| EC-05 | ✅ bestanden | Datei entfernt, Verweis bleibt → Profil 200, Bild 404 (danach zurückgelegt) |

## Regression

**Volle Testsuite:** `php bin/phpunit`, dreimal:

| Lauf | Wann | Tests | Fehlschläge | übersprungen |
|---|---|---|---|---|
| 1 | vor der Prüfung | 2 345 | 0 | 44 |
| 2 | nach der Berichtigung der BF-228-Reproduktion | 2 345 | 0 | 44 |
| 3 | mit dem neuen Rückfallschutz BF-175 | **2 346** | **0** | 44 |

Die Zahl ist gleich der letzten Prüfung von Feature 17 auf diesem Branch (2 345), plus der neue Fall. Die bekannten
Wackler BF-225 und BF-229 traten in keinem der drei Läufe auf.

Scharf (`QAB04_REPRO=1`): BF-227 2/2 rot, BF-228 1/1 rot. Beide BF-226-Fälle laufen jetzt immer und sind grün, die
Gegenprobe ist 405.

**Berührt** laut `git diff` (uncommittet, dem Auftrag gehörig):
- `src/Controller/ProfileController.php`: zwei `createForm`-Aufrufe
- `tests/Functional/Controller/QaB04NachpruefungTest.php`: zwei `offen()` entfernt

Der übrige uncommittete Stand gehört zu Feature 17 und ist dort abgenommen. Keine Konfiguration, Migration oder
Übersetzung geändert. Berührt sind damit die Features, deren Formulare auf der Profilseite stehen:

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| B03 Passkeys | Abschnitt der Profilseite, eigene Formulare | ✅ bestanden | Im Browser mit virtuellem Authenticator (CDP): „Passkey hinzufügen“ (`/passkey/register` 201), Umbenennen über das Formular (302, Name in der DB), Abmelden, **Anmeldung mit Passkey** (302, `last_used_at` gesetzt), Entfernen mit Bestätigungsdialog (302, Zeile weg). Fremder Passkey → 403 |
| B02 Anmeldung | Profil hinter Anmeldung | ✅ bestanden | Alle Anmeldungen der Prüfung (über 40 Konten, Browser und curl) 302 auf `/xx/`; Passkey-Anmeldung; Remember-me (AK-13) |
| 12 Onboarding | Newsletter-Schalter im Profil | ✅ bestanden | Im Browser an: „Du hast den Newsletter abonniert.“, `marketing_consent_at` gesetzt, Kontaktzeile angelegt. Aus: „… abbestellt.“, Einwilligung `NULL`, Kontakt `blacklist_pending`. Einführung: Bild zu groß → 422, Korrektur → 302 (siehe *Hinweise*) |
| 01 Betroffenenrechte | Kontolöschung und Export auf der Profilseite | ✅ bestanden | Konto mit Bild, Passkey und Newsletter. Falsches Passwort → Meldung, Zeile bleibt. Richtiges Passwort (Bestätigungsdialog): 302 `/de/`, „Dein Konto wurde gelöscht.“, Zeile weg, Passkeys, Favoriten, Listen und Notizen 0, Bild 404, Abschiedsmail in der Warteschlange. Danach: Profil → `/login`, Anmeldung scheitert. Export `/de/profile/daten` → 200 JSON, `no-store`, `attachment` |
| 17 Favoriten und Listen | Karte „Gespeichert“ im Profil | ✅ bestanden | Karte auf jeder Profilseite sichtbar (Bildschirmfotos); volle Suite ohne Fehlschlag in 17 |

**Regression der früheren B04-Reparaturen**, am Server:

| Reparatur | Ergebnis | Beleg |
|---|---|---|
| BF-175 · Adresse ohne Bestätigung bei Deckel + Bild | ✅ hält | Deckel erreicht, neue Adresse und Bild in einem Absenden → „Zu viele Adressänderungen …“. `email` unverändert, kein Konto unter der Übernahmeadresse, Anmeldung damit → `/login`. ⚠ Der automatische Schutz war blind (BF-230), ist jetzt durch `QaB04NachpruefungTest::testBf175…` wirksam |
| BF-176 · Deckel an der bestätigten Adresse | ✅ hält | drei Wechsel auf drei **verschiedene** Wunschadressen angenommen (6 Mails), der vierte und fünfte gesperrt |
| BF-182 · leerer Name → 500 | ✅ hält | Name `'   '`, `''`, Tab, NBSP → je 422 „Bitte gib deinen Namen ein.“ |
| BF-184 · RFC-widrig / > 180 → 500 | ✅ hält | 8 Angriffseingaben → 422, keine Vormerkung, 0 Mails, `<script>` nicht roh im HTML. Grenzwert: 181 Zeichen → 422 „höchstens 180“, **180 Zeichen** → 302, Vormerkung mit Länge 180, 2 Mails |
| BF-186 · Entfernen neben Upload | ✅ hält | Entfernen in curl und Browser sauber (DB `NULL`, Datei 404); `Bf186AvatarEntfernenTest` in der Suite grün |
| BF-19, BF-20, BF-22 | ✅ halten | AK-12, AK-14, EC-04 |

## Sicherheitsprüfung

Durchlauf nach `references/angriff.md`, zugespitzt auf die Fläche der Reparatur und die Profilseite. Ausgeführt, nicht
gelesen.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Profil liest keine ID (AK-17). Massenzuweisung `profile[roles][]=ROLE_ADMIN`, `profile[avatarFilename]=../../../.env`, `profile[id]=4`, `profile[isVerified]=0` → je 422, Rollen, Bild, ID und Status unverändert. Passkey von Konto A als Konto B umbenennen/entfernen → **403**, unbekannte Nummer 404, Passkey von A unverändert |
| Rate Limit greift | bestanden | `password_change`: sechster Versuch gesperrt, kontoweit, auch mit richtigem Passwort (AK-14). `email_change`: vierter Wechsel gesperrt, an der bestätigten Adresse (BF-176), auch mit Bild ohne Übernahme (BF-175); abgewiesene Eingaben verbrauchen nichts (BF-184-Lauf, danach Wechsel angenommen) |
| PII in Logs | bestanden | zwölf Passwörter 0 Treffer in `var/log/dev.log` (AK-18) |
| PII an externe Dienste | bestanden | Mails nur an die eingegebene neue und die bestätigte Adresse (Warteschlange gelesen); abgewiesene Adressen erzeugen keine Mail. Brevo nur über den Newsletter-Schalter (Zeile `marketing_contact`, ohne Worker nicht übertragen) |
| Zugriffsregeln serverseitig | bestanden | alle Profilrouten als Gast → `/login` (AK-01); `POST /passkey/register/options` als Gast → 302 `/lb/login`; `POST /de/profile` → **405** (Gegenprobe) |
| Eingaben | bestanden | Name und Adresse wie unter BF-182/BF-184; Upload: Übergröße, GIF, PHP mit Bild-MIME → 422 (AK-05, AK-15). Kein 500, nichts roh ausgegeben |
| Löschen | BF-227 (unverändert) | Kontolöschung räumt Zeile, Bild, Passkeys, Sitzung (Feature 01, oben). Das Restfenster neben einem Upload aus zweiter Sitzung besteht fort: `QaB04KontoloeschungNebenUploadTest` scharf 2/2 rot |
| Geheimnisse im Repository | bestanden | Diff des Auftrags enthält keine Schlüssel, keine Konfiguration |

## Fehler

### BF-230 · Der Prüflauf gegen BF-175 ist nach jedem früheren Lauf blind — niedrig

**Betrifft:** Regression von BF-175 (*hoch*, Adresswechsel ohne Bestätigung). Der Prüflauf
`Qa12Nachpruefung3Test::testBf175AdresswechselMitBildUndErreichtemDeckelWirdNichtWirksam` stammt aus der QA von Feature 12
und ist vorbestehend. Das Produkt ist korrekt.

**Reproduktion:**
1. `php bin/phpunit tests/Functional/Controller/Qa12Nachpruefung3Test.php` einmal laufen lassen.
2. Die Reparatur von BF-175 zurückbauen: in `ProfileController::edit()` die Zeile `$user->setEmail($bisherigeAdresse);`
   vor dem Upload entfernen und hinter die Deckel-Abfrage setzen (Stand vor `v2026.10.23`).
3. Den Prüflauf erneut starten.

**Erwartet:** rot. Mit diesem Rückbau steht am Server die Übernahmeadresse wirksam am Konto. Nachgestellt: drei Wechsel,
dann Übernahmeadresse und Bild → `email=qab04c-mut175-uebernahme@…`, Anmeldung damit → `/de/`.
**Tatsächlich:** grün, ebenso alle **862** Prüfläufe unter `tests/Functional/Controller`. Der Prüflauf erschöpft den
Deckel mit `consume(10000)`, ohne ihn vorher zu leeren:

- Der Deckel liegt im Testlauf in einem dateibasierten Speicher und überlebt den Lauf.
- `testBf176…` in derselben Datei verbraucht danach einen Platz an derselben Adresse.
- Beim nächsten Lauf (`remaining=9999`) weist der Speicher die 10 000 ab und verbraucht nichts.
- Der Deckel ist dann nicht erreicht, der Zweig wird nie betreten.

Mit vorgeschaltetem `reset()` wird derselbe Prüflauf gegen den Rückbau rot.

**Ort:** `tests/Functional/Controller/Qa12Nachpruefung3Test.php:259-261`.

**Dieselbe Ursache** machte die Reproduktion von BF-228 (`QaB04NachpruefungTest::testBf228…`) bei Wiederholung falsch
grün. Gemessen waren 2 929 Restplätze, `consume(10000)` → `accepted=false`, und die Adresse wurde vorgemerkt statt
gesperrt. Diese Reproduktion gehört der QA von B04 und ist **in diesem Durchlauf berichtigt** (`reset()` davor); sie ist
seither zweimal hintereinander rot. `RoadmapIdeenAdresseTest:192` kannte die Falle schon.

**Warum niedrig:** BF-175 ist am Server behoben (oben). Der neue Prüflauf
`QaB04NachpruefungTest::testBf175AdresseBleibtBeimErreichtenDeckelMitBildUnbestaetigt` leert den Deckel. Er prüft die
Vorbedingung mit (Deckel-Meldung erreicht) und ist mit demselben Rückbau zweimal rot, wiederhergestellt grün. Offen bleibt
ein Prüflauf, der Schutz vortäuscht.

**Vorschlag:** In `testBf175…` vor dem `consume(10000)` den Deckel je Schlüssel mit `reset()` leeren, wie es `testBf176…`
in derselben Datei tut. Dazu als Regel: Ein Prüflauf, der einen Deckel erschöpft, leert ihn vorher **und** prüft, dass
der Deckel-Zweig erreicht wurde.

### Offen, unverändert, nicht Teil des Auftrags

- **BF-227** (*niedrig*) · Kontolöschung neben einem Upload aus zweiter Sitzung lässt das Bild öffentlich liegen. Scharf
  2/2 rot, Code unverändert (`AccountDeleter.php`, `AvatarUploadService.php` nicht im Diff).
- **BF-228** (*niedrig*) · Beim erreichten Adress-Deckel hängt die Namensänderung am Bild. Am Server erneut nachgestellt:
  - mit Bild: „QA C 176 Neuer Name“ und Bild gespeichert;
  - ohne Bild: „QA C 176 Zweiter Name“ verworfen;
  - die Meldung nennt beide Male nur die Adresse.

  Die Reproduktion ist jetzt verlässlich rot (siehe BF-230).

## Hinweise ohne Fehlerstatus

- **`code-reviewer`**: bestätigt, dass alle `createForm`-Aufrufe im `ProfileController` und alle Formulare der Seite ein
  Ziel tragen, ohne Nebenwirkung auf Validierung, CSRF, Upload und Adresswechsel. **Verworfen** ist seine Meldung, die
  Einführung (Feature 12) habe dieselbe 405 bei `…/einfuehrung/profilbild` und `…/newsletter`. Die POST-Routen
  `app_onboarding_avatar` und `app_onboarding_newsletter` liegen auf denselben Adressen wie die Bildschirmseiten. Im
  Browser nachgestellt: Bild zu groß → `POST /de/einfuehrung/profilbild` 422, Formular ohne `action`, Korrektur → **302**,
  Bild gespeichert. Das Risiko „sobald sich eine Adresse ändert“ steht schon im Muster zu BF-151/BF-226.
- **WebKit** meldet auf der Profilseite je Aufruf drei Verstöße gegen die Content-Security-Policy im Modus *Report-Only*
  („Refused to execute a script …“), Chromium keinen. Ohne Wirkung, nicht weiter eingegrenzt. `CLAUDE.md` („Sicherheits-
  Kopfzeilen“) nennt die `onsubmit=`-Bestätigungsdialoge, die auch diese Seite trägt, als bekannten Verstoß vor dem
  Scharfschalten.
- **axe** (Profil, 1280 px) findet nur Bekanntes:
  - die Wortmarke mit 2,35 : 1 (16/OF-02)
  - „Passkey hinzufügen“ (BF-223, B03)
  - die Symfony-Werkzeugleiste
- Unverändert aus dem letzten Bericht:
  - „2000000 bytes“ im schmalen Band knapp über 2 MB
  - zwei Meldungen bei leerem Namen
  - EC-01 in der Spec veraltet

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/QaB04NachpruefungTest.php` | 1 neu: `testBf175AdresseBleibtBeimErreichtenDeckelMitBildUnbestaetigt` (läuft immer) | Rückfallschutz BF-175 mit geleertem Deckel und geprüfter Vorbedingung; mit zurückgebautem BF-175 rot |
| dieselbe Datei | 1 berichtigt: `testBf228…` (Reproduktion, übersprungen bis zur Behebung) | `reset()` vor `consume(10000)` — vorher bei Wiederholung falsch grün |

Scharf: `QAB04_REPRO=1 php bin/phpunit tests/Functional/Controller/QaB04NachpruefungTest.php tests/Integration/Service/QaB04KontoloeschungNebenUploadTest.php`
→ 6 Tests, 3 Fehlschläge (BF-227 ×2, BF-228), die übrigen grün (`qa/B04/QA4-server-nachweise.ausgabe.txt`).

## Testdaten

Verwendet und wieder entfernt:
- 17 zuletzt bestehende Wegwerfkonten `qab04c-*` (dazu die zwischendurch gelöschten der Browserläufe)
- zwei Passkeys
- eine Zeile `marketing_contact`
- alle Mails der Warteschlange
- alle hochgeladenen Bilder

Die Anlagedaten der Restaurants 13, 14 und 17 sind auf `2026-11-14 09:31:41` zurückgesetzt. Die Tabelle `user` ist
zeichengleich mit dem Stand vor der Prüfung (`diff` leer). Die Zeilenzahlen von 14 Tabellen sind gleich. Die drei
Fixture-Konten sind unverändert, `public/uploads/avatars/` trägt nur `.gitkeep`. Jede zurückgenommene Reparatur ist
wiederhergestellt: Die md5 von `ProfileController.php`, `ProfileType.php`, `ChangePasswordType.php` und
`profile/index.html.twig` ist gleich dem Stand vor der Prüfung.

## Nächster Schritt

`/sdd-deploy B04` — mit `/sdd-deploy 17`, weil die Reparaturen uncommittet bzw. auf dem Branch
`feature/17-favoriten-und-listen` liegen. Bis dahin liefert die Produktion bei leerem Namen und RFC-widriger oder zu
langer Adresse HTTP 500 (BF-182, BF-184), nach jeder Meldung im Profil 405 (BF-226).

Danach, nicht blockierend: `/sdd-klaeren B04` über BF-227, BF-228 (*niedrig*) und BF-230 (*niedrig*, Prüflauf), dann
`/sdd-build B04` und erneut `/sdd-qa B04`.
