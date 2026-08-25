# B04 · Profil, Avatar & Einreichungen — Testbericht

Stand: 2026-08-24 · zweiter Durchlauf, nach der Reparatur von BF-19 und BF-20
Vorstufe: `review` · Branch `fix/b04-profil-qa` · Commit `c3a2592`

## Fazit

**Production-ready: ja** — mit drei mittleren Befunden, die keinen Hauptweg blockieren.

18 von 19 Akzeptanzkriterien bestanden, eines durchgefallen (EC-04). Die beiden
Befunde des ersten Durchlaufs sind belegt behoben: Eine geänderte E-Mail-Adresse wird
nur noch vorgemerkt, und die Passwortänderung greift ab dem sechsten Versuch nicht mehr.
Beides an der Grenze nachgemessen, nicht nur am Erfolgsweg.

Drei neue Befunde, alle *mittel*:

- **BF-21** — die Adressänderung selbst hat keinen Deckel. Zehn Durchläufe erzeugten
  zwanzig Mails, davon zehn an ein frei gewähltes fremdes Postfach. Der Befund ist eine
  **Nebenwirkung der Reparatur**: Vorher verschickte dieser Weg gar keine Mail.
- **BF-22** — ein ungültiges Stammdatenformular mit geänderter Adresse meldet den Nutzer
  ab. Bestand schon vorher; die Reparatur sitzt genau an dieser Stelle und hätte ihn
  mit einer Zeile miterledigt.
- **BF-23** — der Bestätigungstoken steht auch im `request`-Kanal („Matched route"), und
  der ist in `prod` **nicht** ausgeschlossen. Damit war **BF-06 nur halb behoben**, und
  zwar für B01 genauso wie für B04.

Nächster Aufruf: `/sdd-qa B23` (Rang 1 der Risikoreihenfolge; B05 steht in Rang 4).
Die Erfassung läuft weiter — kein Befund ist
*hoch* oder *kritisch*. Die drei neuen stehen in `features/befunde.md`.

## Was seit dem ersten Durchlauf anders ist

| | erster Durchlauf (2026-08-24, vormittags) | dieser |
|---|---|---|
| AK-12 E-Mail-Änderung | ❌ sofort wirksam, `is_verified` blieb 1 | ✅ vorgemerkt, zwei Mails, 24-Stunden-Frist |
| AK-14 Passwort-Deckel | ❌ acht Versuche, alle angenommen | ✅ fünf erlaubt, der sechste gesperrt |
| AK-12a (neu) | — | ✅ abgelaufen und fremdvergeben beide ohne Serverfehler |
| EC-04 | ✅ (nur der 422 geprüft) | ❌ der 422 kommt, aber die Sitzung stirbt danach |

EC-04 war im ersten Durchlauf zu flach geprüft: Nur der Antwortcode wurde angesehen,
nicht der Zustand danach. Das ist keine Regression durch die Reparatur — nachgewiesen
am Vorgängerstand (`git show HEAD~1`), der an dieser Stelle identisch ist.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Gast auf `/de/profile` → **302** nach `/de/login` |
| AK-02 | ✅ bestanden | angemeldet → **200** |
| AK-03 | ✅ bestanden | Name auf „Geänderter Name" → 302, DB trägt ihn, Meldung „Profil erfolgreich aktualisiert."; **keine** Mail ausgelöst (Regressionsrisiko des `edit()`-Umbaus, eigens geprüft) |
| AK-04 | ✅ bestanden | PNG hochgeladen → `avatar_filename = 6a8c62e0e0c3a3.04020943.png`, Datei über `/uploads/avatars/…` mit **200** abrufbar |
| AK-05 | ✅ bestanden | 3-MB-Datei → **422** |
| AK-06 | ✅ bestanden | zweiter Upload: `…04020943.png` → `…00432173.png`; die alte Datei liefert **404** |
| AK-07 | ✅ bestanden | Löschen mit gültigem Token → „Profilbild erfolgreich entfernt.", DB `NULL`, Datei **404** |
| AK-08 | ✅ bestanden | `_token=falsch` → „Ungültiges CSRF-Token.", Avatar bleibt in der DB stehen |
| AK-09 | ✅ bestanden | falsches aktuelles Passwort → „Das aktuelle Passwort ist nicht korrekt." |
| AK-10 | ✅ bestanden | Wechsel gelingt; neues Passwort meldet an (200), altes wird abgewiesen (302) |
| AK-11 | ✅ bestanden | Profil verlinkt `167, 168, 171` — exakt die drei mit `submitted_by_id = 114` |
| **AK-12** | ✅ bestanden | Adresse auf `angreifer@qa.example` → 302; DB: `email` **unverändert**, `pending_email` gesetzt, Token 64 Zeichen, Frist `2026-08-25 15:29:37`. Zwei Mails (Warnung an die alte, Bestätigung an die neue). Anmeldung mit der vorgemerkten Adresse → **302**, mit der bisherigen → **200** |
| **AK-12a** | ✅ bestanden | Adresse in der Zwischenzeit fremd vergeben → **302**, kein 500, Vorgang abgeräumt. Frist zurückdatiert → **302**, Vorgang abgeräumt |
| AK-13 | ✅ bestanden | zwei Sitzungen, in s1 gewechselt: s1 → **200**, s2 → **302** |
| **AK-14** | ✅ bestanden | Zähler frisch: Versuche 1–5 „Passwort nicht korrekt", **6 und 7 gesperrt**. Kontoweit (andere Sitzung desselben Kontos ebenfalls gesperrt), aber **nicht** kontoübergreifend (Admin-Konto unberührt) |
| AK-15 | ✅ bestanden | PHP-Datei als `.png` mit gefälschtem `Content-Type` → **422**, Avatar in der DB unverändert |
| AK-16 | ✅ bestanden | `GET /uploads/avatars/<datei>` → **200** ohne Anmeldung (bewusste Eigenschaft, kein Fehler) |
| AK-17 | ✅ bestanden | `?user=113&id=113` ändert nichts — weiterhin `167, 168, 171` |
| AK-18 | ✅ bestanden | `DrittesPW123`, `GanzNeuesPW1`, `raten1` → je **0 Treffer** in `var/log/` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Änderung auf `admin@endlech.lu` → **422**, DB unverändert, kein Vorgang angelegt |
| EC-02 | ✅ bestanden | Datei ohne Endung, MIME `image/png` → gespeichert als `…96004464.png`, abrufbar |
| EC-03 | ✅ bestanden | zwei parallele Uploads → genau eine Datei im Bestand, keine Kollision |
| **EC-04** | ❌ durchgefallen | 422 kommt und die Seite wird gerendert — **danach ist die Sitzung tot**: der nächste Aufruf von `/de/profile` liefert 302 nach `/de/login`. Siehe BF-22 |
| EC-05 | ✅ bestanden | Datei aus dem Dateisystem entfernt, DB-Eintrag bleibt → Profilseite **200**, kein Fehler |

## Sicherheitsprüfung

Durchlauf nach `references/angriff.md`, auf die durch die Reparatur neu entstandene
Fläche zugespitzt.

| Prüfung | Ergebnis |
|---|---|
| **Fremder Zugriff** | `?user=113` ändert die Einreichungsliste nicht — `findBySubmitter($this->getUser())` nimmt keine ID aus der Anfrage |
| **Token raten** | 64 Hex-Zeichen aus `random_bytes(32)`. `aaaa…` (64×) → 302 auf die Startseite mit „Ungültiger Bestätigungslink."; zu kurz → **404**, die Route greift gar nicht |
| **Pfadwechsel** | `/verify/email-change/../../admin` → 302, kein Durchgriff |
| **Eingaben** | `<script>alert(1)</script>@x.de`, `a@b.de'; DROP TABLE user; --`, 300 Zeichen, `nur-text` — DB in allen vier Fällen unverändert, kein Serverfehler, keine rohe Datenbankmeldung |
| **Rate Limits** | Passwortänderung gedeckelt (siehe AK-14). **Adressänderung nicht** → BF-21 |
| **Personendaten in Protokollen** | Passwörter: 0 Treffer. **Token: 31 Zeilen im `request`-Kanal, 147 im `doctrine`-Kanal** → BF-23 |
| **Geheimnisse** | keine im Feature-Umfang berührt |

### Zwei Prüfungen, die nichts ergaben — und warum sie trotzdem hier stehen

**Nur die Schreibweise ändern.** `USER@endlech.lu` statt `user@endlech.lu` läuft am
Bestätigungsweg vorbei und wird sofort übernommen (`strcasecmp` sieht keinen
Unterschied). Folgenlos, weil dasselbe Postfach dahintersteht und die Sitzung bestehen
bleibt (gemessen: 200). Kein Befund, aber der Grund, warum `strcasecmp` und nicht `!==`
dort richtig ist: Mit `!==` löste jede Groß-/Kleinschreibung einen vollen
Bestätigungsvorgang samt zwei Mails aus.

**Konto über die vorgemerkte Adresse übernehmen.** Der Versuch, sich mit
`angreifer@qa.example` anzumelden, während sie nur vorgemerkt ist, endet mit 302. Das
ist die Kehrseite, an der die Reparatur steht oder fällt — ohne sie wäre die Vormerkung
Kosmetik.

## Fehler

### BF-21 · Adressänderung ohne Rate Limit — mittel

**Betrifft:** AK-12 (Nebenwirkung der Reparatur, kein Kriterium)

**Reproduktion:**
1. Als `user@endlech.lu` anmelden
2. Zehnmal hintereinander `POST /de/profile/edit` mit `profile[email]=opfer@qa.example`

**Erwartet:** eine Sperre nach wenigen Versuchen
**Tatsächlich:** zehnmal 302, **20 Mails** in Mailpit — zehn an `opfer@qa.example`, zehn
an die eigene Adresse. Keine Sperre.

**Ort:** `src/Controller/ProfileController.php::edit()` — kein Limiter

**Warum das zählt:** Der Empfänger ist frei wählbar und muss dem Konto nicht gehören.
Damit ist der Weg ein Mail-Versender auf fremde Adressen, gedeckt von der Absenderdomäne
von Endlech.lu — das trifft die Brevo-Quota und die Zustellreputation. Der Bruder-Befund
BF-02 (Registrierung) lief als *hoch*, weil er **ohne Konto** ausnutzbar war; hier
braucht es ein bestätigtes Konto, deshalb *mittel*.

**Vorschlag:** Limiter wie `verify_resend` (3 je Stunde), gezählt am Konto. Der Vorgang
ist selten und bewusst; ein enger Deckel stört niemanden. Zusätzlich erwägen, den
laufenden Vorgang zu entwerten, statt bei jedem Aufruf einen neuen anzulegen.

**Drittes Auftreten desselben Musters** (M-01 in `fehlbestand-uebersicht.md`): Der
API-Weg ist gedeckelt, der Browser-Weg nicht. Nach BF-02 (Registrierung), BF-13
(Anmeldung) und BF-18 (Passkey-Challenge) ist das kein Einzelfall mehr, sondern eine
fehlende Konvention: **Jeder Weg, der eine Mail auslöst oder ein Geheimnis prüft,
braucht einen Limiter — unabhängig davon, ob eine App oder ein Browser ihn geht.**

### BF-22 · Ungültiges Stammdatenformular meldet den Nutzer ab — mittel

**Betrifft:** EC-04

**Reproduktion:**
1. Als `user@endlech.lu` anmelden, `/de/profile` → 200
2. `POST /de/profile/edit` mit `profile[email]=kaputt` (oder einer vergebenen Adresse)
3. Antwort: **422**, Seite wird gerendert
4. `/de/profile` erneut aufrufen

**Erwartet:** 200 — der Nutzer korrigiert seine Eingabe
**Tatsächlich:** **302 nach `/de/login`**. Wortlos abgemeldet, Eingaben verloren.

**Gegenprobe zur Eingrenzung:** Ein ungültiges Formular **ohne** Adressbezug
(`profile[name]=X`, zu kurz) → 422 und danach **200**. Es liegt also an der Adresse,
nicht am ungültigen Formular an sich.

**Ort:** `src/Controller/ProfileController.php::edit()` — `handleRequest()` setzt
`$user->setEmail()` auch dann, wenn die Validierung anschließend scheitert. Der
veränderte Nutzer wandert in die Sitzung; beim nächsten Request passt der Anmeldename
nicht mehr zum Konto und Symfony meldet ab.

**Bestand vor der Reparatur** — nachgewiesen an `git show HEAD~1:src/Controller/ProfileController.php`:
dort steht `handleRequest()` ohne jeden Reset. Die Reparatur hat den Fall nicht
verursacht, aber auch nicht mitgenommen, obwohl die dafür nötige Variable
(`$bisherigeAdresse`) inzwischen zwei Zeilen darüber steht.

**Vorschlag:** Im ungültigen Zweig `$user->setEmail($bisherigeAdresse)` zurücksetzen —
dieselbe Zeile, die im gültigen Zweig schon steht. Nebenwirkung mit Wert: Der
eingegebene Wert bleibt trotzdem im Feld stehen, weil das Formular ihn aus dem
Submit-Zustand rendert, nicht aus der Entity.

### BF-23 · Bestätigungstoken im `request`-Kanal — mittel

**Betrifft:** AK-18 (Kriterium bestanden, weil es nur Passwörter nennt), B01 gleichermaßen

**Reproduktion:**
```
$ grep -n "verify/email-change" var/log/dev.log | tail -1
request.INFO: Matched route "app_email_change_confirm".
  {"route":"app_email_change_confirm","route_parameters":{…,"token":"d831e667…"}}
```
Kanalverteilung der Zeilen mit einem 64-Hex-Token in `var/log/dev.log`:
`doctrine: 147 Zeilen`, **`request: 31 Zeilen`**. Für `app_verify_email` (B01) sind es
22 Treffer im selben Kanal.

**Erwartet:** Kein gültiger Token in einem Protokoll, das auf Production geschrieben wird
**Tatsächlich:** `config/packages/monolog.yaml` schließt im `prod`-Block
`["!deprecation", "!doctrine"]` aus — `request` läuft durch. Der `fingers_crossed`-Handler
schreibt bei jedem Fehler ab WARNING seinen gesamten Puffer nach `php://stderr`, samt der
`Matched route`-Zeile mit dem Token darin.

**Damit war BF-06 nur halb behoben.** Dort wurde der `doctrine`-Kanal geschlossen — der
Weg über die gebundenen Abfrageparameter. Der zweite Weg, die Route selbst, blieb offen
und betrifft beide Token-Routen.

**Ort:** `config/packages/monolog.yaml` (prod-Handler `main`)

**Vorschlag:** Entweder den `request`-Kanal in `prod` mit ausschließen — dann fehlt im
Fehlerfall die Route, was die Fehlersuche spürbar verschlechtert — oder einen Processor,
der `route_parameters.token` maskiert. Die zweite Fassung ist die bessere: Sie trifft
genau das Feld und lässt den Rest stehen. Zu prüfen ist zusätzlich das
**Zugriffsprotokoll des Webservers**, das die vollständige URL unabhängig von Monolog
mitschreibt; dort hilft nur, den Token nicht im Pfad zu führen.

## Hinweise ohne Fehlerstatus

- **Der Deckel auf der Passwortänderung zählt auch erfolgreiche Wechsel.** Nach fünf
  legitimen Änderungen in 15 Minuten ist gesperrt. Vertretbar — wer sein Passwort
  fünfmal in einer Viertelstunde wechselt, hat ein anderes Problem —, aber es steht
  nirgends.
- **Die Bestätigung eines Adresswechsels setzt `is_verified` nicht auf `1`.** Wer sein
  Konto nie bestätigt hat und dann die Adresse wechselt, hat den Zugriff auf das neue
  Postfach damit bewiesen, gilt aber weiter als unbestätigt. Kein Fehler nach der Spec,
  aber eine Frage an den Betreiber (als OF-05 aufgenommen).
- **Fehlerzweige des Bestätigungslinks leiten auf `/de/profile`.** Für einen Gast heißt
  das: Weiterleitung auf die Anmeldung. Die Flash-Meldung überlebt (gemessen: Endstation
  `/de/` mit 200), aber der Weg ist ein Umweg.
- **`code-reviewer`-Agent nicht eingesetzt.** Die Sitzungsvorgaben untersagen den Aufruf
  von Subagenten ohne ausdrückliche Anforderung. Die Codequalitätsprüfung erfolgte
  manuell; die drei Befunde stammen aus dem Angriffsdurchlauf, nicht aus der Lektüre.

## Regressionstests

Sieben neue Tests in `tests/Functional/Controller/ProfileControllerTest.php`:
`testEmailAenderungWirdNurVorgemerkt`, `testEmailAenderungBenachrichtigtBeideAdressen`,
`testBestaetigungUebernimmtDieNeueAdresse`,
`testAbgelaufenerBestaetigungslinkWechseltNicht`,
`testBereitsVergebeneAdresseFuehrtNichtZumFehler`,
`testOffenerVorgangLaesstSichAbbrechen`, `testNamensaenderungLoestKeinenAdresswechselAus`.

Gesamtsuite: **330 Tests, 1137 Assertions, 1 übersprungen, 0 Fehler.**

Der Deckel selbst ist nicht als Test abgebildet: In `when@test` steht das Limit auf
10000, sonst summierten sich die Versuche über die Suite. Nachweis ist die
Reproduktion oben — dieselbe Regelung wie bei BF-02 und BF-13.

## Nächster Schritt

`/sdd-qa B23`. B04 geht auf `approved`; ausgeliefert ist noch nichts — die
Reparaturen von B01, B02 und B04 liegen zusammen auf `dev` und warten auf `/sdd-deploy`.
