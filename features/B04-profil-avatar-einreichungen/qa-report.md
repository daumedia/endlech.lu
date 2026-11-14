# B04 · Profil, Avatar & eigene Einreichungen — Testbericht

Stand: 2026-11-14 · Geprüft gegen `spec.md` (Rekonstruktion vom 2026-08-23, fortgeschrieben bis 2026-11-14) · in eigener
Session, ohne Bauverlauf · Vorstufe `building` (Fehlerauftrag BF-182, BF-184, BF-186) · Branch
`feature/17-favoriten-und-listen`, uncommittet neben Feature 17 · Suchliste: `build-report.md`

Frühere Durchläufe (2026-08-24) und die Nachträge aus den QA-Runden von Feature 12 sind archiviert unter
`qa/B04/qa-report-bis-2026-11-14-archiv.md`. Nachweise dieses Durchlaufs: `qa/B04/`.

## Fazit

**Production-ready: ja** — mit einem mittleren und drei niedrigen Befunden, keiner davon *hoch* oder *kritisch*.

Die drei Befunde des Auftrags sind behoben, jeder am laufenden Server und mit einer Gegenprobe belegt: Wird die
Reparatur zeilenweise zurückgebaut, wird der Prüflauf rot und der Server antwortet wieder mit HTTP 500. Ein leerer
Name, eine leere, RFC-widrige oder zu lange Adresse ergeben jetzt 422 mit Meldung am Feld (lb, de, fr geprüft). Keine
dieser Eingaben legt eine Vormerkung an, verschickt eine Mail oder verbraucht einen Platz im Deckel: Nach 17
abgewiesenen Eingaben gingen drei gültige Wechsel noch durch. Alle 19 Kriterien sind bestanden. Durchgefallen ist EC-04.

**Neu: BF-226 (*mittel*)**. Das ist der vom Bau als OF-06 gemeldete Fehler, im Browser auf fünf Wegen bestätigt. Nach
jeder Meldung am Profil- **oder** Passwortformular geht das korrigierte Formular mit Turbo an `POST /xx/profile` und
endet mit **405**. Die Korrektur ist dabei verloren. Er ist vorbestehend. Seit der Reparatur erreichen ihn zwei
Eingabeklassen mehr, die vorher sofort mit 500 endeten: der leere Name und die RFC-widrige oder zu lange Adresse. Das
ist ein Mehr an Reichweite, kein Rückschritt: Diese Wege funktionierten vorher gar nicht. Dazu kommen drei niedrige
Befunde:

- **BF-227**: OF-07, am Server nachgestellt. Kontolöschung und Upload aus einer zweiten Sitzung lassen das Bild
  öffentlich liegen.
- **BF-228**: Hinweis des `code-reviewer`, am Server bestätigt. Beim erreichten Adress-Deckel entscheidet das Bild, ob
  eine Namensänderung gespeichert wird.
- **BF-229**: mitgefunden, außerhalb von B04. Ein Prüflauf kippt an der Reihenfolge der Küchen-Etiketten.

**Nächster Aufruf:** `/sdd-deploy B04`. Die Reparatur liegt uncommittet auf dem Branch von Feature 17 und geht mit dessen
Auslieferung live. Bis dahin bestehen BF-182 und BF-184 (HTTP 500) auf der Produktion fort. Vorher oder danach,
blockiert nicht: `/sdd-klaeren B04` über BF-226, BF-227 und BF-228, dann `/sdd-build B04`.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 19 von 19 (AK-01 bis AK-18, AK-12a; AK-19 bis AK-21 gestrichen → Feature 18, nicht geprüft) |
| davon bestanden | 19 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5 — 4 bestanden, **EC-04 durchgefallen** (BF-226) |
| Befunde des Auftrags behoben | 3 von 3 (BF-182, BF-184, BF-186) |
| Tests neu geschrieben | 6 (5 Reproduktionen, übersprungen bis zur Behebung; 1 Gegenprobe) |
| Tests grün | Reproduktionen scharf 5 von 5 rot aus dem erwarteten Grund; Gegenprobe grün |
| Volle Suite gelaufen | 2 320 Tests, 0 Fehlschläge, 43 übersprungen (vor den neuen Tests); 2 326 Tests, 1 Fehlschlag (BF-229, Flackern, 15 von 15 Wiederholungen grün), 48 übersprungen |
| Regression: berührte Features | 5 (01, 12, B03, B02, 17), Hauptweg bestanden 5 |

## Die drei Befunde des Auftrags

| Befund | behoben | am Server | Gegenprobe |
|---|---|---|---|
| **BF-182** · Name/Adresse leer → 500 | ✅ ja | Name `'   '`, `''`, fehlend, Tab/Zeilenumbruch, NBSP → **422** „Bitte gib deinen Namen ein.“; Adresse `''`, `'   '`, fehlend → **422** „Bitte gib deine E-Mail-Adresse ein.“. DB unverändert, 0 Mails, Sitzung danach 200 | `empty_data` am Namen entfernt → Server **500** „Expected argument of type "string", "null" given at property path "name"“, `Qa12Nachpruefung4Test::testBf182…` und `Bf182Bf184ProfilEingabenTest::testBf182LeererName…` (3/3) rot |
| **BF-184** · RFC-widrig / > 180 Zeichen → 500 | ✅ ja | 17 Eingaben → je **422** mit Meldung, keine Vormerkung, 0 Mails, darunter `../../etc/passwd@example.lu`, `anna..muster@…`, `.anna@…`, `anna.@…`, 181 und 10 010 Zeichen, `<script>…@x.de` (escaped), SQL, Emoji, `anna@[127.0.0.1]`, Zeilenumbruch mit `Bcc:`. **Danach** drei gültige Wechsel (180 Zeichen, `jean-luc@télécom.lu` → Mail an `xn--tlcom-bsab.lu`, Adresse mit Leerzeichen getrimmt) angenommen, der vierte vom Deckel abgewiesen. Damit hat keine abgewiesene Eingabe einen Deckelplatz verbraucht | strikte Prüfung entfernt → Server **500**, aber **ohne** Vormerkung (die Adressprüfung vor dem `flush()` greift); `testBf184…` 4/4 rot. `Length(180)` entfernt → Server **500** `SQLSTATE[22001]`, `testBf184AdresseUeber180…` rot |
| **BF-186** · Entfernen/Kontolöschung neben Upload | ✅ ja | Upload und „Entfernen“ gleichzeitig, 15 Durchgänge (0–300 ms): 0 tote Verweise, 0 Waisen. ⚠ Das Fenster ist am Entwicklungsserver **nicht erreichbar**: Auch ohne Reparatur trafen es 57 Durchgänge nicht (`QA-BF186-server-race-ohne-reparatur.ausgabe.txt`). Am Server ist also nur „kein Rückschritt“ belegt | SQL-`NULL` in `delete()` entfernt → `Qa12…::testBf186…` und `Bf186AvatarEntfernenTest` (1/3) rot; alte Bedingung im `AccountDeleter` → `Bf186…` (Kontolöschung) rot |

Nach jeder Gegenprobe ist die Datei aus einer Sicherung zurückgeschrieben. Die md5-Summen aller vier Dateien sind
identisch mit dem Stand vor der Prüfung (`qa/B04/QA-server-nachweise.ausgabe.txt`). Die drei Reproduktionen von
Feature 12 laufen scharf (`QA12_REPRO=1`) grün; BF-185 (Feature 12, nicht dieser Auftrag) bleibt rot.

## Akzeptanzkriterien im Einzelnen

Geprüft am laufenden Server (Port 8000, Entwicklungsmodus) mit eigenen Konten `qab04-*@example.test`. Mails gelesen
aus der Warteschlange `messenger_messages` (Empfänger, Betreff, Vorlage, Link); es lief kein Worker.

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Gast `GET /{lb,de,fr,en,pt}/profile` → **302** auf `/{locale}/login`; ebenso `POST …/profile/edit`, `…/password`, `…/avatar/delete`, `GET …/profile/daten` |
| AK-02 | ✅ bestanden | angemeldet **200**: Formulare `profile` → `/de/profile/edit`, `change_password` → `/de/profile/password`, Abschnitt „Passkeys“ mit sichtbarem Knopf „🔑 Passkey hinzufügen“ (Chromium), „Meine Einreichungen“; `qa/B04/QA-AK02-profil-{1280,375}.png`, kein waagrechter Überstand (375/375) |
| AK-03 | ✅ bestanden | curl: Name → 302, Meldung „Profil erfolgreich aktualisiert.“, Kopfzeile trägt den Namen, keine Mail. Browser (Turbo): dasselbe, ohne Konsolenfehler (`QA-AK03-AK04-hauptweg-1280.png`) |
| AK-04 | ✅ bestanden | JPEG, PNG, WebP und eine PNG mit **genau 2 000 000 Bytes** → 302, Datei unter `/uploads/avatars/…` mit 200 und richtigem `Content-Type`, `<img>` im Profil; im Browser per Dateiauswahl ebenso |
| AK-05 | ✅ bestanden | 2 000 001 Bytes, 3,6 MB, GIF, PHP-Skript als `.png` → je **422**, Avatar in der DB unverändert |
| AK-06 | ✅ bestanden | vier Uploads nacheinander: die drei alten Dateien je **404**, im Verzeichnis nur die neueste |
| AK-07 | ✅ bestanden | gültiges Token → „Profilbild erfolgreich entfernt.“, DB `NULL`, Datei **404**; im Browser mit Bestätigungsdialog ebenso |
| AK-08 | ✅ bestanden | `_token` leer, `falsch`, fehlend → „Ungültiges CSRF-Token. …“, Avatar und Datei bleiben (200) |
| AK-09 | ✅ bestanden | falsches aktuelles Passwort → „Das aktuelle Passwort ist nicht korrekt.“, Hash unverändert |
| AK-10 | ✅ bestanden | Grenzwert: 7 Zeichen → 422 „mindestens 8 Zeichen“, ungleich → 422 „stimmen nicht überein“, **8 Zeichen** → „Passwort erfolgreich geändert.“; neues meldet an, altes nicht |
| AK-11 | ✅ bestanden | Anlagedaten der drei Einreichungen von `user@endlech.lu` vorübergehend gespreizt → Reihenfolge 14, 17, 13 (absteigend), je Name, Stadt, „Eingereicht am …“, „⏳ Ausstehend“ bzw. „Verifiziert“; Daten danach zurückgesetzt |
| AK-12 | ✅ bestanden | Wechsel → `email` unverändert, `pending_email` gesetzt, Token 64 Zeichen, Frist **+24 h**; zwei Mails: Bestätigung (mit Link) an die neue, Warnung (ohne Link) an die alte. Anmeldung mit der vorgemerkten 302 → `/login`, mit der bisherigen ok; Link → Adresse getauscht, Vormerkung leer; danach alte Adresse abgewiesen |
| AK-12a | ✅ bestanden | vorgemerkte Adresse inzwischen vergeben → 302, „Diese E-Mail-Adresse wird inzwischen bereits verwendet.“, Vorgang abgeräumt; Frist zurückdatiert → „Der Bestätigungslink ist abgelaufen …“, abgeräumt; kein 500 |
| AK-13 | ✅ bestanden | zwei Sitzungen + ein reines `REMEMBERME`-Cookie: nach dem Wechsel eigene Sitzung **200**, zweite **302**, `REMEMBERME` allein vorher 200, danach **302** |
| AK-14 | ✅ bestanden | Versuche 1–5 „nicht korrekt“, **6 und 7 gesperrt**; zweite Sitzung desselben Kontos mit richtigem Passwort ebenfalls gesperrt (Hash unverändert); anderes Konto unberührt |
| AK-15 | ✅ bestanden | PHP-Datei als `avatar.png` mit `Content-Type: image/png` → **422**, Avatar unverändert |
| AK-16 | ✅ bestanden | `GET /uploads/avatars/<datei>` ohne Cookie → **200** `image/webp`; Verzeichnisliste 404 |
| AK-17 | ✅ bestanden | `?user=4&id=4&submittedBy=4` → weiterhin nur 14, 17, 13 |
| AK-18 | ✅ bestanden | neun verwendete Passwörter (`QaB04-pass1`, `NeuesPW8`, `rate1` …) in `var/log/dev.log`: je **0 Treffer** |
| AK-19–21 | — | gestrichen 2026-11-14, an Feature `18` übergeben (Auftrag: nicht prüfen) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `admin@endlech.lu` und `ADMIN@ENDLECH.LU` → 422 „Diese E-Mail-Adresse wird bereits verwendet.“, keine Vormerkung. ⚠ Die Spec beschreibt die Meldung noch als „hartkodiert deutsch“; sie ist übersetzt (`/en/` → „This email address is already in use.“) — die Rekonstruktion ist an dieser Stelle veraltet |
| EC-02 | ✅ bestanden | PNG ohne Endung, gesendet als `application/octet-stream` → gespeichert als `….png` |
| EC-03 | ✅ bestanden | fünf Runden mit je drei gleichzeitigen Uploads → je genau eine Datei, Verweis vorhanden |
| **EC-04** | ❌ durchgefallen | 422 kommt, Seite wird gerendert, Passwortfelder leer, Sitzung bleibt (BF-22 bleibt behoben). **Aber das neu gerenderte Formular lässt sich mit JavaScript nicht abschicken:** Die Korrektur geht an `POST /de/profile` → **405**. Siehe BF-226 |
| EC-05 | ✅ bestanden | Datei aus dem Verzeichnis entfernt, Verweis bleibt → Profil 200, Bild 404 (danach zurückgelegt) |

## Regression

**Volle Testsuite:** `php bin/phpunit`.

- **Vor den neuen Tests:** 2 320 Tests, 16 814 Assertions, 0 Fehlschläge, 43 übersprungen, wie im Abschlussbericht.
  Die 43 Übersprungenen sind Reproduktionen offener Befunde: Qa16 11, Qa15 11, `ComparisonEdgeCaseTest` 7, Qa17 7,
  Qa12 3 (davon BF-185 in `Qa12Nachpruefung4Test`) und vier einzelne.
- **Mit den neuen Tests:** 2 326 Tests, 1 Fehlschlag, 48 übersprungen. Der Fehlschlag ist
  `SponsorIntegrityTest::testAk08…@Liste nach Name`: zwei Abrufe von `/de/restaurants?sort=name` unterschieden sich nur
  in der Reihenfolge zweier Küchen-Etiketten. Die Datei lief danach dreimal grün, der Fall allein zwölfmal grün. Das ist
  BF-229, außerhalb von B04, nicht BF-225.

**Berührt laut `git diff --name-only main` und *Systemweite Änderungen*.** Der Branch trägt zusätzlich das abgenommene
Feature 17. Dem Auftrag gehören:

- `src/Form/ProfileType.php`, `src/Controller/ProfileController.php` (`edit()`), `src/Service/AvatarUploadService.php`
- `src/Account/AccountDeleter.php`
- `translations/validators.{lb,de,fr,en,pt}.yaml` (`user.email_max`)
- `CLAUDE.md`
- `tests/Functional/Controller/Qa12Nachpruefung4Test.php`: drei `offen()` entfernt, sonst unverändert, per Diff geprüft

| Feature | Warum berührt | Hauptweg | Nachweis |
|---|---|---|---|
| 01 Betroffenenrechte | `AccountDeleter` ruft `delete()` jetzt immer | ✅ bestanden | Konto mit Bild: falsches Passwort → Meldung; richtiges → 302 `/de/`, Zeile weg, Bild 404, Sitzung → `/login`, Anmeldung scheitert, Abschiedsmail in der Warteschlange. Export `/de/profile/daten` → 200 JSON, `no-store`. `AccountDeletionTest`, `Bf136KontoloeschungLimiterTest` grün |
| 12 Onboarding | teilt `AvatarUploadService`, Reproduktionsdatei geändert | ✅ bestanden | Bild in der Einführung hochgeladen → 302, Datei 200; Newsletter-Schalter im Profil an/aus mit Meldung, Doppel-Abo ohne Wechsel; `ProfileNewsletterTest`, `Qa12Nachpruefung3Test` (scharf) grün |
| B03 Passkeys | eingebunden auf derselben Seite | ✅ bestanden | `POST /passkey/register/options` angemeldet 200 mit Challenge, als Gast 302; `PasskeyControllerTest` grün |
| B02 Anmeldung | Profil hinter `IS_AUTHENTICATED_REMEMBERED` | ✅ bestanden | alle Anmeldungen der Prüfung (sechs Konten, Browser und curl) 302 auf `/de/`; Remember-me (AK-13) |
| 17 Favoriten und Listen | geteilte Hunks in `validators.*.yaml`, `CLAUDE.md`; Karte „Gespeichert“ im Profil | ✅ bestanden | `CatalogueCompletenessTest` grün (fünf Kataloge gleich), Karte im Profil sichtbar, Suite ohne Fehlschlag in 17 |

## Sicherheitsprüfung

Durchlauf nach `references/angriff.md`, zugespitzt auf die Fläche der Reparatur. Ausgeführt, nicht gelesen.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Profil liest keine ID: `?user=4…` ohne Wirkung (AK-17); Massenzuweisung `profile[roles][]=ROLE_ADMIN`, `profile[avatarFilename]=../../../.env`, `profile[id]=4` → 422, Rollen und Bild unverändert; fremder Passkey → 403 (`PasskeyControllerTest`) |
| Rate Limit greift | bestanden | `password_change`: sechster Versuch gesperrt, kontoweit (AK-14). `email_change`: vierter Wechsel gesperrt, auch mit Bild ohne Übernahme der Adresse (BF-175/176 bleiben behoben); **abgewiesene Eingaben verbrauchen nichts** (17 Abweisungen, danach 3 Wechsel). `account_delete` per Prüflauf |
| PII in Logs | bestanden | Passwörter 0 Treffer (AK-18). Der Bestätigungstoken steht im Entwicklungsprotokoll im Kanal `request` (2×) und `doctrine` (5×); in `prod` sind beide ausgeschlossen (`monolog.yaml`, BF-23) |
| PII an externe Dienste | bestanden | Mails nur an die vom Nutzer genannte und die bestätigte Adresse (Warteschlange gelesen); IDN-Adresse als Punycode. Brevo nur über den Newsletter-Schalter (Feature 12, unverändert) |
| Zugriffsregeln serverseitig | bestanden | alle Profilrouten als Gast → `/login` (AK-01); Passkey-Optionen als Gast 302 |
| Eingaben | bestanden | Name: leer, Leerzeichen, Tab, NBSP → 422. Adresse: 17 Angriffseingaben → 422 (BF-184). Upload: falsche Endung, Übergröße, PHP mit Bild-MIME → 422 (AK-05, AK-15). Kein 500, nichts roh ausgegeben |
| Löschen | **BF-227** | Kontolöschung räumt Zeile, Bild, Sitzung (Feature 01). Mit einem Upload aus einer zweiten Sitzung im selben Augenblick bleibt das Bild öffentlich liegen: 5 von 31 Durchgängen |
| Geheimnisse im Repository | bestanden | Auftrag berührt keine Schlüssel; keine neue Konfiguration |

## Fehler

### BF-226 · Nach einer Meldung im Profil scheitert die Korrektur mit HTTP 405 — mittel

**Betrifft:** EC-04 (bisher OF-06 in `spec.md`). Vorbestehend; dieselbe Zeile steht auf `main`
(`createForm(ProfileType::class, $user)` ohne `action`), und der Bau hat den Fehler dort im Browser gemessen.

**Reproduktion (Chromium, Turbo aktiv):**
1. Anmelden, `/de/profile` öffnen.
2. Name „a“ eingeben, „Änderungen speichern“: `POST /de/profile/edit` → 422 mit Meldung. Die Adresse im Browser bleibt
   `/de/profile`, das neu gerenderte `<form name="profile">` hat **kein** `action`.
3. Name zu „QA Carl Korrigiert“ ändern, erneut speichern.

**Erwartet:** 302, Name gespeichert.
**Tatsächlich:** `POST /de/profile` → **405**. Die Seite kommt mit dem alten Namen zurück, ohne Meldung; die Korrektur
ist verloren. Gemessen auf fünf Wegen, alle 405:
- Name zu kurz
- Name aus Leerzeichen (BF-182)
- RFC-widrige Adresse (BF-184)
- zu großes Bild
- **Passwortformular** (neues Passwort zu kurz, `changePassword()` baut `ChangePasswordType` ohne `action`)

Ohne JavaScript gelingt die Korrektur (302), weil die Adresse dann `/de/profile/edit` ist. Nachweise:
`qa/B04/QA-OF06-*.png`, `QA-OF06-browser.json`, Reproduktion
`QaB04NachpruefungTest::testBf226ProfilformularBehaeltNachEinerMeldungSeinZiel`, `…PasswortformularBehaelt…` (scharf
rot: `null` statt `/de/profile/edit` bzw. `/de/profile/password`); Gegenprobe `testGegenprobeProfilseiteNimmtKeinPostAn`
(405).

**Reichweite:** jede Meldung am Profil- oder Passwortformular. Der häufigste Auslöser dürfte ein Handyfoto über 2 MB
sein. Seit der Reparatur von BF-182 und BF-184 kommen der leere Name und die RFC-widrige oder zu lange Adresse hinzu;
sie endeten vorher schon beim ersten Absenden mit 500. Mehr Reichweite also, aber kein Rückschritt. In der Produktion
nicht gemessen, was nach der 405 erscheint; im Entwicklungsmodus lädt Turbo die Profilseite neu (Screenshot `…-2-korrektur.png`).

**Ort:** `src/Controller/ProfileController.php:115` (`edit()`) und `:329` (`changePassword()`).

**Vorschlag:** `'action' => $this->generateUrl('app_profile_edit')` bzw. `('app_profile_password')` in beiden
`createForm`-Aufrufen. Muster BF-151; die Turbo-Mechanik „Adresse bleibt stehen“ ist seit BF-153 bekannt.

### BF-227 · Kontolöschung neben einem Upload lässt das Bild öffentlich liegen — niedrig

**Betrifft:** Löschen (Feature 01, Art. 17), bisher OF-07 in `spec.md`. Der Fehler ist vorbestehend; die Reparatur von
BF-186 hat das Fenster verkleinert, aber nicht geschlossen.

**Reproduktion (am Server, `qa/B04/QA-OF07-kontoloeschung-neben-upload.ausgabe.txt`):**
1. Ein Konto in **zwei Sitzungen** anmelden, also zwei Geräte. Zwei Tabs eines Browsers teilen die Sitzung und wurden
   in 18 Durchgängen nie getroffen.
2. In Sitzung 1 „Konto löschen“ mit richtigem Passwort abschicken.
3. 365–385 ms später in Sitzung 2 ein Profilbild hochladen.

**Erwartet:** Nach der Löschung liegt keine Bilddatei des Kontos mehr im Verzeichnis.
**Tatsächlich:** In 5 von 31 Durchgängen meldet der Upload Erfolg (302 → `/de/profile`), die Zeile ist weg, die Datei
bleibt liegen und ist unter `/uploads/avatars/<name>` ohne Anmeldung abrufbar (**200**). `app:uploads:prune` listet sie
(„5 Datei(en) ohne Zeile“), läuft aber nur von Hand.

**Ort:** `src/Account/AccountDeleter.php:69` gibt die Sperre mit `delete()` frei, bevor `em->remove($user)` die Zeile
löscht. `src/Service/AvatarUploadService.php:22-60` prüft unter der Sperre nicht, ob das Konto noch besteht. Reproduktion
`tests/Integration/Service/QaB04KontoloeschungNebenUploadTest.php` (zwei Fälle: Upload zwischen Bildentfernung und
Zeilenlöschung, Upload danach). Die zweite Sitzung läuft dort über das echte `upload()` mit Sperre; beide Fälle sind
scharf rot.

**Warum niedrig:** Es braucht den Kontoinhaber selbst, zwei Geräte und ein Fenster von etwa 20 ms. Der Dateiname ist
nirgends verlinkt.

**Vorschlag:** Die Sperre im `AccountDeleter` bis nach dem Löschen der Zeile halten, und `upload()` prüft unter der
Sperre, ob die Zeile noch existiert. Andernfalls verwirft es die Datei.

### BF-228 · Beim erreichten Adress-Deckel hängt die Namensänderung am Bild — niedrig

**Betrifft:** AK-03 und AK-12 im Zusammenspiel. Der Fehler ist vorbestehend, seit der Reparatur von BF-175
(`v2026.10.23`). Hinweis des `code-reviewer`, am Server bestätigt.

**Reproduktion:** Deckel `email_change` erreicht (drei Wechsel in der Stunde). Dann in **einem** Absenden einen neuen
Namen und eine neue Adresse schicken:
- **ohne Bild:** Meldung „Zu viele Adressänderungen …“, der **Name ist nicht gespeichert**.
- **mit Bild:** dieselbe Meldung, aber **Name und Bild sind gespeichert**.

In beiden Fällen nennt die Meldung nur die Adresse.

**Erwartet:** Dieselbe Eingabe hat beim Namen dasselbe Ergebnis, mit oder ohne Bild.
**Tatsächlich:** Der `flush()` des Upload-Dienstes nimmt den Namen mit. Der Deckel-Zweig kehrt ohne eigenen `flush()`
zurück (`qa/B04/QA-server-nachweise.ausgabe.txt`).

**Ort:** `src/Controller/ProfileController.php:140-156`. Reproduktion
`QaB04NachpruefungTest::testBf228NamensaenderungBeimErreichtenDeckelHaengtNichtAmBild`; sie prüft die Gleichheit, nicht
eine Richtung, und ist scharf rot.

**Vorschlag:** Den Deckel vor dem Upload prüfen, also vor allem, was schreibt. Dann den Namen und das Bild entweder
speichern und die Meldung entsprechend fassen, oder beides verwerfen.

### BF-229 · Prüflauf kippt an der Reihenfolge der Küchen-Etiketten — niedrig (mitgefunden, außerhalb von B04)

**Betrifft:** Feature 15 (`SponsorIntegrityTest`) und B05, Restaurantliste.

**Reproduktion:** Die volle Suite vom 2026-11-14 (zweiter Lauf) war rot in
`SponsorIntegrityTest::testAk08SponsorenAendernNichtsAnRestaurants@Liste nach Name`. Zwei Abrufe von
`/de/restaurants?sort=name` unterschieden sich nur in der Reihenfolge zweier Etiketten:
- „Vegetarisch“/„Vegan“ bei „Cloche d'Or“
- „Französisch“/„Mediterran“ bei „Kirchberg“

Die Datei lief danach dreimal grün, der Fall allein zwölfmal; sechs Abrufe am Server waren gleich.

**Ort:** `src/Entity/Restaurant.php:34-36`. `$cuisines` ist ohne `#[ORM\OrderBy]` gemappt, die Etiketten folgen der
Zeilenreihenfolge der Abfrage. Nicht BF-225 (dort `generatedAt` im JSON-Zweig).

**Vorschlag:** `#[ORM\OrderBy(['name' => 'ASC'])]` an `$cuisines`. Dann stehen die Etiketten auf jeder Seite gleich, und
der Prüflauf vergleicht Gleiches.

## Hinweise ohne Fehlerstatus

- **„Das Profilbild darf maximal 2000000 bytes groß sein.“** Diese Meldung erscheint bei Dateien von 2 000 001 bis etwa
  2 005 000 Bytes. Symfony wählt die Einheit so, dass sich Grenze und Größe sichtbar unterscheiden. Eine Datei knapp
  darüber zeigt „2000 kB“, alles ab etwa 2,05 MB „2 MB“. Das ist kosmetisch und gilt nur in diesem schmalen Band; „bytes“
  bleibt dabei auf Englisch.
- **Zwei Meldungen bei leerem Namen:** „Bitte gib deinen Namen ein.“ und „mindestens 2 Zeichen“. Der Bau hat das so
  gelassen (`Length(min: 2)` prüft auch die leere Zeichenkette).
- **Unbekannte Zusatzfelder** werden mit 422 abgewiesen, ohne sichtbare Meldung (kein `form_errors` am Wurzelformular).
  Das erreicht nur eine gebaute Anfrage, kein Browser.
- **Ein Bestätigungslink, den ein Gast anklickt,** tauscht die Adresse und leitet dann auf `/login` (Hinweis aus dem
  August, unverändert).
- **axe** (Profil, 1280/375 px, ohne Symfony-Toolbar) findet nur Bekanntes:
  - die Wortmarke (16/OF-02)
  - „Passkey hinzufügen“ mit 3,61 : 1 (BF-223, B03)

  `qa/B04/QA-AK02-axe.json`.
- **EC-01 in der Spec ist veraltet:** Die Meldung bei vergebener Adresse ist übersetzt. Die Spec ist eine Rekonstruktion
  und gehört bei Gelegenheit berichtigt.
- **Die strikte Adressprüfung im Formular und die `new Address()` im Controller** prüfen dieselbe Norm. Die Gegenprobe
  zeigt, dass die zweite nicht doppelt ist: Ohne die strikte Prüfung endet die RFC-widrige Adresse mit 500, aber
  **ohne** Vormerkung.
- **Nicht nachgestellt:** Ob die Einführung (Feature 12) dieselbe 405 wie BF-226 hat, ist geprüft, und sie hat sie
  nicht. Die POST-Route `…/einfuehrung/profilbild` liegt auf derselben Adresse wie die Bildschirmseite. Das
  Bildformular hat auf der 422-Seite ebenfalls kein `action`, landet aber zufällig richtig. Sobald sich eine der beiden
  Adressen ändert, entsteht BF-226 dort neu.

## `code-reviewer`

Der Agent hat die geänderten Dateien gelesen. Verifiziert und übernommen ist ein Fund: **BF-228**, am Server
nachgestellt und mit Reproduktion belegt.

- Sein Hinweis auf das Restfenster der Kontolöschung deckt sich mit BF-227. Am Server ist er bestätigt.
- **Verworfen ist seine Aussage zu BF-151:** Er meinte, das Formular auf der 422-Seite gehe an `/profile/edit`. Im
  Browser stimmt das nur ohne JavaScript; mit Turbo bleibt die Adresse `/profile` stehen (BF-226).
- Sein Hinweis, das SQL-`UPDATE` in `delete()` laufe an der Unit of Work vorbei, ist kein Fehler. Zurzeit ist dort
  keine fremde Änderung offen, weil `edit()` die Adresse vor dem Upload zurücksetzt. Er bleibt als Hinweis stehen.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/QaB04NachpruefungTest.php` | 4 (3 Reproduktionen, übersprungen bis zur Behebung; 1 Gegenprobe immer) | BF-226 Profil- und Passwortformular, Gegenprobe 405 auf `POST /profile`, BF-228 |
| `tests/Integration/Service/QaB04KontoloeschungNebenUploadTest.php` | 2 Reproduktionen, übersprungen | BF-227: Upload zwischen Bildentfernung und Zeilenlöschung bzw. danach, über das echte `upload()` mit Sperre |

Scharf: `QAB04_REPRO=1 php bin/phpunit tests/Functional/Controller/QaB04NachpruefungTest.php tests/Integration/Service/QaB04KontoloeschungNebenUploadTest.php`.
Ergebnis: 5 Reproduktionen rot aus dem erwarteten Grund, Gegenprobe grün. Wer einen Befund behebt, entfernt den Aufruf
`offen(…)` und ändert sonst nichts an der Prüfung.

## Testdaten

Verwendet und wieder entfernt wurden:
- sechs eigene Konten per SQL und 53 Wegwerfkonten für den Löschlauf und seine Zeitmessung (alle gelöscht)
- 67 Nachrichten in `messenger_messages`
- eine Zeile in `marketing_contact`
- alle Bilddateien, darunter die fünf Waisen aus BF-227

Die Anlagedaten der Restaurants 13, 14 und 17 sind zurückgesetzt. Die Tabelle `user` ist zeichengleich mit dem Stand vor
der Prüfung (`diff` leer). Die drei Fixture-Konten sind unverändert; im Verzeichnis `public/uploads/avatars/` liegt nur
`.gitkeep`.

## Nächster Schritt

`/sdd-deploy B04`. Die Reparatur von BF-182, BF-184 und BF-186 geht mit dem Branch `feature/17-favoriten-und-listen`
live, also mit `/sdd-deploy 17`. Bis dahin liefert die Produktion bei leerem Namen und bei RFC-widriger oder zu langer
Adresse weiterhin HTTP 500.

Danach, nicht blockierend: `/sdd-klaeren B04` über BF-226 (*mittel*), BF-227 und BF-228 (*niedrig*), dann `/sdd-build
B04` und erneut `/sdd-qa B04`. BF-229 gehört zu Feature 15 bzw. B05.
