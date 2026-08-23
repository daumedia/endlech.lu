# B01 · Registrierung & E-Mail-Bestätigung — Testbericht

Stand: 2026-08-23 · Geprüft gegen `spec.md` vom 2026-08-23 (Rückerfassung)

> **Dieser Bericht hat zwei Durchläufe.** Der erste (unten, „Erster Durchlauf") fand
> 8 Befunde. Danach lief `/sdd-build B01` und behob 6 davon. Der **zweite Durchlauf**
> steht direkt hier darunter und ist der maßgebliche Stand.
Umgebung: lokal, `symfony server` auf `:8000`, MySQL 8.0 in Docker, Mailpit als SMTP-Senke

## Fazit — zweiter Durchlauf (2026-08-23)

**Production-ready: nein**

Die Reparatur hält, was sie zusagt: **AK-05 und AK-15 sind jetzt bestanden**, die
Sackgasse ist geschlossen (abgelaufener Link → neue Mail anfordern → bestätigen läuft
durch), und die Registrierung ist gedrosselt. Von 20 Kriterien bestehen jetzt **17
statt 15**. Alle sechs Behebungen wurden einzeln nachvollzogen, keine davon nur
konfigurativ geglaubt.

**Ein neuer Befund kam durch die Reparatur hinzu** (BF-11, mittel): Der Limiter
verbraucht Kontingent auch bei **ungültigen** Formularen. Fünf Tippfehler sperren einen
Nutzer eine Stunde aus, ohne dass je ein Konto oder eine Mail entstand — belegt. Auf
einer Plattform für Menschen mit Behinderungen wiegt das schwerer als anderswo. Die
Implementierung folgt dabei exakt dem bestehenden `PartnerController`, das Muster ist
also älter als die Reparatur.

Offen bleiben zwei bewusst zurückgestellte Punkte: AK-13 (kein `user_checker` — vom
Betreiber entschieden, jetzt unter *Akzeptiert*) und AK-14/BF-09 (Enumeration, hängt an
einem Passwort-Vergessen-Weg). AK-17 ist weiterhin durchgefallen: In `prod` ist der Weg
geschlossen, in `dev` steht der Token bewusst weiter im Log — und den Laufzeitnachweis
für `prod` konnte ich nicht erbringen.

**Der nächste Schritt ist die Auslieferung.** Die sechs Behebungen liegen auf einem
Branch und wirken für Nutzer erst danach — darunter zwei mit Grad *hoch* an Code, der
gerade läuft.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 20 von 20 |
| davon bestanden | **17** (erster Durchlauf: 15) |
| davon durchgefallen | 3 (AK-13, AK-14, AK-17) |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5 |
| Tests | 317 grün, 0 übersprungen (vorher 315 mit 2 Skips) |
| Neue Befunde | 1 (BF-11, mittel) |

## Akzeptanzkriterien — zweiter Durchlauf

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /de/register` → 200, vier Felder im Markup |
| AK-02 | ✅ bestanden | angemeldet → `302 → /de/` |
| AK-03 | ✅ bestanden | Name „A" → 422 |
| AK-04 | ✅ bestanden | 7 Zeichen → 422; **Grenzwert** 8 → 302 |
| AK-05 | ✅ **jetzt bestanden** | 422; kein roher Schlüssel mehr — „Die Passwörter stimmen nicht überein." (de) und „ne correspondent pas" (fr) |
| AK-06 | ✅ bestanden | 302 → `/de/verify`; DB: `is_verified=0`, Token 64, `$2y$13$`, Ablauf +24 h |
| AK-07 | ✅ bestanden | `pw_prefix = $2y$13$` |
| AK-08 | ✅ bestanden | `/de/profile` → 302 auf Login |
| AK-09 | ✅ bestanden | Link → `302 → /de/login`; DB: `is_verified=1`, Token `NULL` |
| AK-10 | ✅ bestanden | Ablauf 2020 → `302 → /de/verify`, Flash „…abgelaufen. Bitte fordere einen neuen an.", `is_verified` bleibt 0 |
| AK-11 | ✅ bestanden | 64×`a` → `302 → /de/` |
| AK-12 | ✅ bestanden | Mailpit gestoppt: 302, Warnung, Konto **gespeichert** |
| AK-13 | ❌ durchgefallen | unverändert reproduziert: unbestätigtes Konto meldet sich an, `/de/profile` → **200**. **Bewusst so belassen** (Betreiberentscheidung, OF-01) → BF-03, jetzt unter *Akzeptiert* |
| AK-14 | ❌ durchgefallen | Die Meldung folgt jetzt der Sprache („Cette adresse e-mail est déjà utilisée."), **die Auskunft bleibt**: ein Angreifer erfährt weiterhin, ob eine Adresse registriert ist → BF-09 |
| AK-15 | ✅ **jetzt bestanden** | `router:match /de/verify/resend` → `app_verify_resend`; kompletter Rettungsweg durchlaufen: abgelaufen → resend → neue Mail → `is_verified=1` |
| AK-16 | ✅ bestanden | 11 Spalten, unverändert |
| AK-17 | ❌ durchgefallen | Klartextpasswort **0 Treffer** ✓; Bestätigungstoken im `dev`-Log **1 Treffer**. Für `prod` ist `channels: exclusive [deprecation, doctrine]` belegt (`debug:config`), der **Laufzeitnachweis gelang nicht** — siehe Einschränkungen |
| AK-18 | ✅ bestanden | 4 frische Token: alle 64 Zeichen `[a-f0-9]`, verschieden, gemeinsames Präfix 0 |
| AK-19 | ✅ bestanden | zweiter Aufruf → `302 → /de/` |
| AK-20 | ✅ bestanden | Payload: Empfänger, Name, Link. Kein Passwort, kein Hash. Inhalt **in der Sprache der Registrierung** |

## Regressionsprüfung — B04 ist über zwei Wege mitbetroffen

Der Abschlussbericht des Bauvorgangs nannte beide; beide geprüft:

| Weg | Ergebnis | Nachweis |
|---|---|---|
| `form.password_mismatch` in `ChangePasswordType` | ✅ korrekt mitrepariert | Passwortwechsel mit ungleichen Feldern → 422, lesbare Meldung, kein roher Schlüssel |
| `user.email_unique` in `ProfileType` | ✅ korrekt mitrepariert | E-Mail auf `admin@endlech.lu` ändern → 422, „wird bereits verwendet", kein deutscher Klartext |
| Konto unbeschädigt | ✅ | `user@endlech.lu` unverändert in der Datenbank |

## Sicherheitsprüfung — zweiter Durchlauf

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | kein Objektzugriff über ID; fremder Token → 302, keine Daten |
| Zugriffsregeln serverseitig | bestanden | `/de/register` 200/302, `/de/verify` 200, `/de/verify/resend` anonym 302 |
| Rate Limit greift | bestanden | Registrierung: `302 302 302 302 302 429 429`; resend: 5 Aufrufe → **3 Mails**; API unverändert `400×5 429 429` |
| PII in Logs | **BF-06 offen für dev** | Klartextpasswort 0; Token im `dev`-Log 1 |
| PII an externe Dienste | bestanden | Mailpit-Payload geprüft: Empfänger, Name, Link — sonst nichts |
| Geheimnisse im Repository | bestanden | `.env.local` nicht getrackt; **0** neue Secret-Zeilen im Diff gegen `dev` |
| Eingaben | bestanden | XSS und SQL-Einschleusung → 302, Tabelle intakt (18 Konten), Ausgabe escaped |
| Löschen / Betroffenenrechte | **BF-04 offen** | unverändert: 0 Routen für Löschung, Export, Passwort-Reset |

## Neuer Fehler

### BUG-09 · Rate Limit verbraucht Kontingent auch bei ungültigen Formularen — mittel

**Betrifft:** die Reparatur von BUG-03; neu in diesem Durchlauf
**Reproduktion:**
1. Limiter-Speicher leeren
2. Fünfmal `/de/register` mit einem zu kurzen Passwort absenden (jedes Mal 422)
3. Ein sechstes Mal absenden — auch mit gültigen Daten
**Erwartet:** Ein Nutzer, der sich vertippt, bleibt handlungsfähig; gedeckelt wird, was
Konten anlegt und Mails verschickt
**Tatsächlich:** `422 422 422 422 422` → **429**. Angelegte Konten: **0**. Versandte
Mails: **0**. Der Nutzer ist eine Stunde ausgesperrt, ohne je etwas ausgelöst zu haben.
Zweite Beobachtung: Auch ein **Transportfehler** verbraucht Kontingent — dort entsteht
zwar ein Konto, aber keine Mail.
**Ort:** `src/Controller/RegistrationController.php:47-57` — `consume(1)` steht in
`if ($form->isSubmitted())`, also vor `isValid()`.
**Einordnung:** Die Implementierung folgt exakt dem bestehenden
`PartnerController::submit()` (Zeile 53), der ebenfalls vor der Gültigkeitsprüfung
konsumiert. Das Muster ist älter als diese Reparatur und betrifft B14/B15 gleichermaßen.
Auf einer Plattform, die sich ausdrücklich an Menschen mit Behinderungen richtet, wiegt
eine Sperre nach fünf Tippfehlern schwerer als anderswo.
**Vorschlag:** `consume(1)` in den `isValid()`-Zweig verschieben; wenn ein Schutz gegen
reines Formular-Fluten gewünscht ist, dafür ein zweites, großzügigeres Limit.

## Hinweise ohne Fehlerstatus

- **Die resend-Sperre antwortet mit 302, nicht mit 429.** Vom Bauvorgang als Annahme
  gemeldet und hier bestätigt: fünf Aufrufe, alle 302, aber nur drei Mails. Für den
  Nutzer stimmt es (Flash-Meldung), für ein Monitoring ist die Sperre unsichtbar.
- **Die 429-Antwort der Registrierung trägt keinen `Retry-After`-Header.** Der
  `ApiRateLimitSubscriber` setzt ihn für die API; hier fehlt er.
- **`doctrine:schema:validate` bleibt rot** — vier `RENAME INDEX`-Anweisungen aus
  Altlasten, unverändert und nicht von dieser Reparatur verursacht (im Bauvorgang per
  Stash-Vergleich gegen `dev` belegt).
- **Kein automatisierter Test für die Rate Limits.** Vom Bauvorgang gemeldet und hier
  bestätigt: Der Limiter ist per `#[Autowire]` fest im Controller-Service verdrahtet,
  `getContainer()->set()` erreicht ihn nicht. Beide Limits sind nur manuell belegt.

## Einschränkungen dieses Durchlaufs

- **AK-17, `prod`-Teil: Laufzeitnachweis nicht erbracht.** Drei Versuche, den
  `fingers_crossed`-Puffer in einer echten `prod`-Umgebung zu leeren, scheiterten am
  Bootstrap (`test.service_container`, fehlendes `SENTRY_DSN`, zuletzt: der
  `logger`-Service ist in `prod` inlined und nicht abrufbar). Belegt ist damit die
  **Konfiguration** (`debug:config monolog handlers.main --env=prod` →
  `channels: exclusive [deprecation, doctrine]`), nicht das Laufzeitverhalten. Wer es
  abschließend wissen will, braucht einen echten Fehler auf der Produktivumgebung und
  einen Blick ins Hoster-Log.
- Geprüft wurde auf dem Branch `fix/b01-registrierung-qa`, nicht auf `dev` und nicht auf
  der Produktivumgebung.

---

# Erster Durchlauf (2026-08-23, vor der Reparatur)

## Fazit des ersten Durchlaufs

**Production-ready: nein**

Der Hauptweg funktioniert: Registrieren, Konto anlegen, Mail versenden, Link einlösen —
alles belegt und mit Nachweis abgehakt. Vier von fünf durchgefallenen Kriterien betreffen
Wege **daneben**, und genau dort liegen die Fehler, die einen Nutzer aussperren können.

Der schwerste Fund ist eine Sackgasse: Wessen Bestätigungslink abläuft, bekommt die
Aufforderung „Bitte fordere einen neuen an." — und genau dieser Weg ist unerreichbar
(BUG-01). Das Konto ist damit dauerhaft unbestätigt und, weil es kein Passwort-Zurücksetzen
und keinen Löschweg gibt, auch nicht zu retten. Dazu kommt eine ungedrosselte
Registrierung (BUG-03): zwölf Konten und zwölf Mails in Folge, ohne jede Sperre — während
dieselbe Anwendung die API-Anmeldung ab dem sechsten Versuch blockt.

Nächster Schritt: `/sdd-build B01` mit BUG-01 bis BUG-08.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 20 von 20 |
| davon bestanden | 15 |
| davon durchgefallen | 5 |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 5 von 5 |
| Tests neu geschrieben | 10 (2 davon übersprungen bis zur Reparatur) |
| Tests grün | 315 von 315 (2 übersprungen) |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /de/register` → 200; Felder `registration[name\|email\|plainPassword][first\|second]` im Markup |
| AK-02 | ✅ bestanden | angemeldet → `302 → http://localhost:8000/de/`; Test `testAk02AngemeldeterWirdVonDerRegistrierseiteWeggeleitet` |
| AK-03 | ✅ bestanden | Name „A" → **422**, Meldung „Der Name muss mindestens 2 Zeichen lang sein." |
| AK-04 | ✅ bestanden | Passwort 7 Zeichen → **422**; **Grenzwert** 8 Zeichen → **302** |
| AK-05 | ❌ durchgefallen | 422 und kein Mailversand stimmen (Test `testAk05UngleichePasswoerterWerdenAbgewiesen`), aber angezeigt wird der rohe Schlüssel `form.password_mismatch` → **BUG-02** |
| AK-06 | ✅ bestanden | `302 → /de/verify`; DB: `is_verified=0`, `tok_len=64`, `expires_at` = +24 h; Flash „Registrierung erfolgreich! …" |
| AK-07 | ✅ bestanden | DB: `pw_prefix = $2y$13$` (bcrypt), kein Klartext |
| AK-08 | ✅ bestanden | nach der Registrierung `GET /de/profile` → `302 → /de/login` |
| AK-09 | ✅ bestanden | Link aus der Mail → `302 → /de/login`; DB danach `is_verified=1`, Token und Ablauf `NULL`; Flash „…erfolgreich bestätigt!"; Test `testAk09GueltigerTokenVerifiziertUndLeertDenToken` |
| AK-10 | ✅ bestanden | Ablauf auf 2020-01-01 → `302 → /de/verify`, `is_verified` bleibt 0; Test `testAk10AbgelaufenerTokenVerifiziertNicht` |
| AK-11 | ✅ bestanden | Token aus 64 Nullen → `302 → /de/`, Flash „Ungültiger Bestätigungslink."; Test `testAk11UnbekannterTokenLeitetAufDieStartseite` |
| AK-12 | ✅ bestanden | Mailpit gestoppt, synchroner Versand: `302`, Flash „Registrierung erfolgreich, aber die Bestätigungs-E-Mail konnte nicht gesendet werden."; DB: Konto **gespeichert** (`gerda@qa.example`, tok 64) |
| AK-13 | ❌ durchgefallen | reproduziert: unbestätigtes Konto meldet sich an (`302 → /de/`), `GET /de/profile` → **200**. Verhalten wie in der Spec beschrieben, aber es ist eine Lücke → **BUG-05** |
| AK-14 | ❌ durchgefallen | reproduziert: „Diese E-Mail-Adresse ist bereits registriert." — auch auf `/fr/register` **auf Deutsch** → **BUG-06** |
| AK-15 | ❌ durchgefallen | `router:match /de/verify/resend` → `app_verify_email`; Aufruf als angemeldeter, unbestätigter Nutzer → `302 → /de/`, Flash „Ungültiger Bestätigungslink.", **Mailzähler unverändert bei 2** → **BUG-01** |
| AK-16 | ✅ bestanden | Spalten von `user`: `name`, `email`, `password`, `is_verified`, `verification_token`, `verification_token_expires_at`, `created_at` (+ `avatar_filename`, `webauthn_handle` aus B03/B04). Keine besonderen Kategorien nach Art. 9 |
| AK-17 | ❌ durchgefallen | Klartextpasswort: **0 Treffer** in `var/log/dev.log` ✓ — aber der Bestätigungstoken steht drin (1 Treffer, `doctrine.DEBUG: Executing statement: INSERT INTO user …`) → **BUG-04** |
| AK-18 | ✅ bestanden | 5 Token verglichen: alle 64 Zeichen, nur `[a-f0-9]`, alle verschieden, gemeinsames Präfix 0 Zeichen |
| AK-19 | ✅ bestanden | derselbe Link zweimal: erst `302 → /de/login`, dann `302 → /de/`; Test `testAk19EingeloesterTokenGreiftKeinZweitesMal` |
| AK-20 | ✅ bestanden | Mailpit-Payload: `From noreply@endlech.lu`, `To anna@qa.example`, Betreff, Inhalt mit Name und Bestätigungs-URL. Nichts darüber hinaus. ⚠ Sprachfehler siehe BUG-07 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ belegt | direkter Doppel-`INSERT` auf `anna@qa.example` → `Duplicate entry` (UNIQUE greift auf DB-Ebene) |
| EC-02 | ✅ belegt | nicht auslösbar — `resend()` ist unerreichbar (BUG-01). Das **ist** der Nachweis |
| EC-03 | ✅ belegt | `expires_at = NULL` → `302 → /de/verify`, `is_verified` bleibt 0; Test `testEc03TokenOhneAblaufzeitpunktGiltAlsAbgelaufen` |
| EC-04 | ✅ belegt | Passwort 5000 Zeichen → **422**; exakt 4096 → **302** |
| EC-05 | ✅ belegt | über AK-12: Konto bleibt gespeichert, ist aber ohne `resend`-Weg nie bestätigbar |

## Sicherheitsprüfung

Aktiv angegriffen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | B01 adressiert kein Objekt über eine ID. `/verify/{token}` ist tokenbasiert; der Token ist das Geheimnis und wird beim Einlösen geleert (AK-19) |
| Zugriffsregeln serverseitig | bestanden | `/de/register` anonym 200 / angemeldet 302; `/de/verify` 200; `access_control` `^/[a-z]{2}/(register\|verify)` = `PUBLIC_ACCESS` |
| Rate Limit greift | **BUG-03** | 12 Registrierungen in Folge: `302` ×12, **12 Konten**, **12 Mails**. Gegenprobe API: `POST /api/v1/auth/login` → 401,401,401,401,401,**429**,429,429 |
| PII in Logs | **BUG-04** | `grep "GutesPasswort1" var/log/dev.log` → **0** ✓; Bestätigungstoken → **1 Treffer** in einer `doctrine.DEBUG`-Zeile |
| PII an externe Dienste | bestanden | tatsächlicher Payload aus Mailpit geprüft: Empfänger, Anzeigename, Bestätigungs-URL. Kein Passwort, kein Hash, keine weiteren Felder |
| Geheimnisse im Repository | bestanden | `.env.local` nicht getrackt, **0** Commits; `config/jwt/` nicht getrackt. Hinweis: `APP_SECRET` in `.env.dev` ist der dokumentierte dev-Wert — er ist mit dem lokalen `.env.local` identisch (siehe Hinweise) |
| Eingaben | bestanden | `<script>alert(1)</script>` wird gespeichert, aber als `&lt;script&gt;` ausgegeben (Twig-Autoescaping); `'; DROP TABLE user; --` als Literal, Tabelle intakt (27 Konten); Emoji ✓; 10.000 Zeichen → 422 |
| Löschen / Betroffenenrechte | **BUG-08** | `debug:router` → **0** Routen für Kontolöschung, **0** für Datenexport, **0** für Passwort-Zurücksetzen |

## Fehler

### BUG-01 · „Bestätigungsmail erneut senden" ist unerreichbar — hoch

**Betrifft:** AK-15, EC-02, FB-02
**Reproduktion:**
1. `php bin/console router:match /de/verify/resend` → meldet `app_verify_email`
2. Konto registrieren, nicht bestätigen, anmelden
3. `/de/verify` öffnen, „Bestätigungsmail erneut senden" folgen
**Erwartet:** neue Mail, Weiterleitung auf `/de/verify` mit Bestätigung
**Tatsächlich:** `302 → /de/`, Flash „Ungültiger Bestätigungslink.", Mailzähler unverändert
**Ort:** `src/Controller/EmailVerificationController.php:35` (`/verify/{token}`) steht vor
Zeile 59 (`/verify/resend`); Symfony wertet in Deklarationsreihenfolge aus und `{token}`
hat kein Requirement. Der Link in `templates/email_verification/notice.html.twig:32`
führt damit ins Leere.
**Verschärfung:** AK-10 zeigt bei abgelaufenem Token die Meldung „Der Bestätigungslink
ist abgelaufen. **Bitte fordere einen neuen an.**" — der einzige Weg dafür ist dieser.
Zusammen mit dem fehlenden Passwort-Zurücksetzen (FB-05) und dem fehlenden Löschweg
(BUG-08) ist das Konto danach nicht mehr zu retten.
**Vorschlag:** `resend()` vor `verify()` deklarieren **oder** `requirements: ['token' => '[a-f0-9]{64}']`
setzen — und gleichzeitig BUG-03 beheben, sonst öffnet die Reparatur einen ungedrosselten
Mailversandweg.


**BEHOBEN am 2026-08-23** (Branch `fix/b01-registrierung-qa`): `app_verify_email` trägt jetzt
`requirements: ['token' => '[a-f0-9]{64}']`. Nachweis: `router:match /de/verify/resend` →
`app_verify_resend`; der Weg Hinweisseite → Link → neue Mail → Token einlösen wurde
vollständig durchlaufen (`is_verified` danach 1). Regressionstest
`testAk15ErneutSendenIstErreichbar` ist aktiv.

### BUG-03 · Registrierung ohne Rate Limit — hoch

**Betrifft:** FB-01, Sicherheitskatalog Abschnitt 4
**Reproduktion:** `/de/register` zwölfmal in Folge mit verschiedenen Adressen absenden
**Erwartet:** Sperre nach wenigen Versuchen (Katalog: 5 in 15 Minuten)
**Tatsächlich:** 12 × `302`, 12 Konten angelegt, 12 Mails versandt — keine Sperre
**Ort:** `config/packages/framework.yaml` definiert `api_anonymous`, `api_login`,
`partner_waitlist`; keiner greift auf `/{locale}/register`, und
`RegistrationController` bezieht keinen Limiter.
**Folge:** unbegrenzte Konto-Anlage; jede Anlage verbraucht Kontingent der
Brevo-Quota des Betreibers. Über `resend` (nach BUG-01) wäre zusätzlich ein fremdes
Postfach befüllbar.
**Vorschlag:** Limiter analog `partner_waitlist` einführen und im Controller beziehen.


**BEHOBEN am 2026-08-23**: Limiter `registration` (5/Stunde je IP) in `framework.yaml`,
bezogen im Controller nach `handleRequest` und nur für abgeschickte Formulare.
Nachweis: 12 Versuche → 5×302, dann 7×429; genau 5 Konten und 5 Mails; drei GET-Aufrufe
verbrauchten kein Kontingent. Zusätzlich `verify_resend` (3/Stunde), Nachweis im Selbsttest:
5 Aufrufe → 3 Mails.

### BUG-05 · Unbestätigte Konten haben vollen Zugang — hoch

**Betrifft:** AK-13, FB-03
**Reproduktion:** Konto registrieren, **nicht** bestätigen, unter `/de/login` anmelden
**Erwartet:** Anmeldung abgewiesen oder Zugang eingeschränkt
**Tatsächlich:** `302 → /de/`, `GET /de/profile` → **200**. Nur `/de/community/suggest`
leitet auf `/de/verify` um
**Ort:** `config/packages/security.yaml` konfiguriert keinen `user_checker`;
`App\Entity\User` implementiert kein `isEnabled()`. Die einzige Prüfung auf
`isVerified()` außerhalb von B01 steht in `src/Controller/CommunityController.php:29`.
**Folge:** Die E-Mail-Bestätigung ist bis auf den Vorschlags-Wizard folgenlos — eine
nicht existierende Adresse reicht für ein voll nutzbares Konto.
**Vorschlag:** `user_checker` ergänzen. ⚠ **Nicht vor BUG-01**: Solange `resend`
unerreichbar ist, sperrt das alle bestehenden unbestätigten Konten aus. Offene
Entscheidung OF-01 der Spec.


**ZURÜCKGESTELLT am 2026-08-23** — Produktentscheidung des Betreibers, nicht gebaut.
Ein `user_checker` sperrt bestehende unbestätigte Konten im Moment des Deployments aus;
wie viele das auf Produktion sind, ist von hier nicht einsehbar. Die Voraussetzung dafür
ist mit BUG-01 jetzt geschaffen (es gibt wieder einen Weg, eine neue Mail anzufordern).
Bleibt als BF-03 in `features/befunde.md` offen. Entspricht OF-01 der Spec.

### BUG-08 · Betroffenenrechte nicht bedienbar — hoch

**Betrifft:** FB-04, FB-05, FB-06; Sicherheitskatalog Abschnitt 5
**Reproduktion:** `php bin/console debug:router` nach Routen für Kontolöschung,
Datenexport und Passwort-Zurücksetzen durchsuchen
**Erwartet:** je ein Weg (Art. 15 und 17 DSGVO sind Pflicht, kein Ausbauwunsch)
**Tatsächlich:** **0 Treffer** für alle drei
**Ort:** `src/Controller/ProfileController.php` (dort wäre der Ort), keine Entsprechung
in `Api\V1`
**Bemerkenswert:** Die technischen Voraussetzungen sind vollständig vorhanden —
`webauthn_credential` kaskadiert, `restaurant.submitted_by` und
`restaurant_suggestion.suggested_by` stehen auf `SET NULL`,
`AvatarUploadService::delete()` räumt die Datei ab. Es fehlt allein der Auslöser.
**Vorschlag:** eigenes Feature durch die volle Kette; berührt B01, B04 und B19.


**NICHT HIER GEBAUT am 2026-08-23** — Kontolöschung, Datenexport und Passwort-Zurücksetzen
sind fehlende Funktionen, keine Reparaturen. Nach Regel 1 von `sdd-build` gehören sie als
eigenes Feature mit eigener Nummer durch die volle Kette; sie berühren B01, B04 und B19.
Bleibt als BF-04 in `features/befunde.md` offen.

**NACHTRAG 2026-08-23 — aus B01 herausgelöst.** Beim Preflight für die Auslieferung
wurde sichtbar, dass dieser Befund B01 dauerhaft auf `review` hält, obwohl dort nichts
mehr zu reparieren ist: Er ist keine Reparaturaufgabe, sondern eine fehlende Funktion
über B01, B04 und B19 hinweg. Er ist jetzt dem regulären Feature `01` zugeordnet und
läuft durch die volle Kette. **Für die Bewertung von B01 zählt er nicht mehr mit** —
damit sind dort nur noch Befunde mit Grad *mittel* offen.

### BUG-02 · Roher Übersetzungsschlüssel statt Meldung — mittel

**Betrifft:** AK-05
**Reproduktion:** Registrierformular mit zwei verschiedenen Passwörtern absenden
**Erwartet:** „Die Passwörter stimmen nicht überein."
**Tatsächlich:** im Markup steht `form.password_mismatch`
**Ort:** `src/Form/RegistrationType.php:49` — `RepeatedType::invalid_message` wird in
der Domäne **`validators`** übersetzt, der Schlüssel steht aber nur in
`translations/messages.*.yaml`. Geprüft: In **allen vier** `validators.{lb,de,fr,en}.yaml`
fehlt er.
**Betrifft ein zweites Feature:** `src/Form/ChangePasswordType.php:36` verwendet
denselben Schlüssel → B04, Passwortwechsel im Profil.
**Vorschlag:** Schlüssel in die vier `validators.*.yaml` aufnehmen.


**BEHOBEN am 2026-08-23**: `form.password_mismatch` in alle vier `validators.*.yaml`
aufgenommen. Nachweis: `/de/register` und `/fr/register` mit ungleichen Passwörtern —
kein roher Schlüssel mehr, Meldung in der jeweiligen Sprache. Regressionstest
`testAk05MeldungIstUebersetztNichtDerRoheSchluessel` ist aktiv. Wirkt zugleich für B04.

### BUG-04 · Bestätigungstoken im Anwendungsprotokoll — mittel

**Betrifft:** AK-17
**Reproduktion:** registrieren, dann `grep <token> var/log/dev.log`
**Erwartet:** kein Treffer
**Tatsächlich:** 1 Treffer in `doctrine.DEBUG: Executing statement: INSERT INTO user …`
**Ort:** `config/packages/monolog.yaml`. In `dev` schreibt `main` mit `level: debug`.
In **`prod`** ist `main` ein `fingers_crossed` (`action_level: error`, `buffer_size: 50`)
mit `nested: level: debug` auf `php://stderr` — bei einem Fehler im selben Request
werden die gepufferten Doctrine-DEBUG-Zeilen also mit ausgeschrieben. Der
`doctrine`-Channel ist dort nicht ausgeschlossen (`channels: ["!deprecation"]`).
**Nicht betroffen:** Sentry. Der `sentry_logs`-Handler greift ab `WARNING`, Doctrine
loggt auf `DEBUG`; dazu `send_default_pii: false` und `zend.exception_ignore_args = On`.
**Folge:** Der Token ist ein Anmelde-Äquivalent — wer ihn hat, bestätigt das Konto.
In prod nur bei gleichzeitigem Fehler, dann aber im Hoster-Log.
**Vorschlag:** `doctrine`-Channel in `prod` aus `main` ausschließen.


**BEHOBEN am 2026-08-23** (für `prod`): `channels: ["!deprecation", "!doctrine"]` am
`main`-Handler. Nachweis: `debug:config monolog handlers.main --env=prod` zeigt
`channels: type: exclusive, elements: [deprecation, doctrine]`. Der `dev`-Handler bleibt
bewusst unverändert — ein Entwicklungslog ohne SQL wäre für die Fehlersuche wertlos, und
es verlässt den Rechner nicht.

### BUG-06 · Registrierformular verrät bestehende Konten, Meldung nicht übersetzt — mittel

**Betrifft:** AK-14, FB-07
**Reproduktion:** `/fr/register` mit einer bereits vergebenen Adresse absenden
**Erwartet:** entweder generische Antwort (wie in der API) oder wenigstens eine
französische Meldung
**Tatsächlich:** „Diese E-Mail-Adresse ist bereits registriert." — auf Deutsch, in der
französischen Fassung
**Ort:** `src/Entity/User.php:15` — `#[UniqueEntity(message: 'Diese E-Mail-Adresse ist
bereits registriert.')]`, als einzige Validierungsmeldung des Features hartkodiert
statt als Übersetzungsschlüssel
**Zusammenhang:** `src/Controller/Api/V1/AuthController.php` baut für denselben Fall
ausdrücklich Anti-Enumeration auf (identische Antwort, Timing-Ausgleich, Hinweis-Mail).
Dieser Schutz ist wirkungslos, solange dieselbe Auskunft über das Web-Formular frei
abrufbar ist.
**Vorschlag:** Übersetzungsschlüssel einsetzen; die Enumerationsfrage ist OF-02 der Spec.


**TEILWEISE BEHOBEN am 2026-08-23**: Die hartkodierte deutsche Meldung ist ersetzt durch
`user.email_unique` — der Schlüssel war in allen vier `validators.*.yaml` bereits
vorhanden und wurde nur nicht benutzt. Nachweis: `/fr/register` zeigt jetzt
„Cette adresse e-mail est déjà utilisée." Regressionstest
`testAk14MeldungBeiVergebenerAdresseFolgtDerSprache`.
**Offen bleibt die Enumeration selbst** (OF-02 der Spec): Ob das Web-Formular wie die API
generisch antworten soll, ist eine Produktentscheidung — sie kostet die verständliche
Meldung und setzt einen Passwort-Vergessen-Weg voraus, den es nicht gibt (BF-04).

### BUG-07 · Mailinhalt fällt bei asynchronem Versand auf Luxemburgisch zurück — mittel

**Betrifft:** kein AK (Hinweis aus der Prüfung von AK-20)
**Reproduktion:**
1. Über `/fr/register` registrieren, `MESSENGER_TRANSPORT_DSN=doctrine://` (Vorgabe in `.env`)
2. `php bin/console messenger:consume async`
3. Mail ansehen
**Erwartet:** Betreff und Inhalt französisch
**Tatsächlich:** Betreff „Confirmez votre adresse e-mail" ✓, Inhalt „Moien Claire Test!
Merci fir deng Registréierung…" ✗
**Gegenprobe:** mit `MESSENGER_TRANSPORT_DSN=sync://` — Betreff *und* Inhalt französisch
**Ort:** `src/Controller/RegistrationController.php` — der Betreff wird im Controller
über `$this->translator->trans()` aufgelöst, das Twig-Template der `TemplatedEmail` erst
beim Versand. Im Worker fehlt die Request-Locale, es greift `default_locale: lb`.
**Einordnung:** Produktion läuft laut `CLAUDE.md` mit `sync://` — dort tritt es **nicht**
auf. Es tritt in dev auf und würde in prod auftreten, sobald ein Worker eingeführt wird
(für die Monats-Snapshots naheliegend, siehe B18/AK-17).
**Betrifft ebenso:** B14 und B15, die ihre Bestätigungsmails genauso bauen.
**Vorschlag:** `->locale($request->getLocale())` auf der `TemplatedEmail` setzen.


**BEHOBEN am 2026-08-23**: `->locale($request->getLocale())` auf beiden `TemplatedEmail`
in B01. Symfonys `BodyRenderer` wertet `getLocale()` aus und rendert über den
`LocaleSwitcher`. Nachweis: Registrierung über `/fr/` mit
`MESSENGER_TRANSPORT_DSN=doctrine://` und anschließendem Worker-Lauf — Betreff *und*
Inhalt französisch. Regressionstest `testAk20BestaetigungsmailTraegtDieLocaleDerRegistrierung`.
**Nicht mitgeändert:** `WaitlistConfirmationService` (B14/B15) und `Api\V1\AuthController`
(B23) bauen ihre Mails genauso und sind weiterhin betroffen — anderes Feature, eigener
QA-Durchlauf.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/EmailVerificationControllerTest.php` (neu) | 6 (1 übersprungen) | AK-09, AK-10, AK-11, AK-15 (übersprungen bis BUG-01), AK-19, EC-03 |
| `tests/Functional/Controller/RegistrationControllerTest.php` (ergänzt) | 4 (1 übersprungen) | AK-02, AK-05, AK-14 |

Der Bestätigungsweg — die Hälfte dieses Features — war zuvor **ohne jeden Test**.

Die beiden übersprungenen Tests prüfen das **gewünschte** Verhalten und tragen den
Bug-Verweis in `markTestSkipped()`. Nach der Reparatur genügt es, die eine Zeile zu
entfernen; der Test wird dann grün und sichert die Behebung ab.

Vollständige Suite nach der Ergänzung: **315 Tests, 1083 Assertions, 2 übersprungen,
0 Fehler.**

## Hinweise ohne Fehlerstatus

- **`APP_SECRET` lokal identisch mit dem committeten dev-Wert.** `.env.dev` trägt
  `APP_SECRET=dfe5df93…` (laut `CLAUDE.md` bewusst als dev-Wert), und die lokale
  `.env.local` enthält denselben. Für die Prüfumgebung folgenlos; ob auf Produktion ein
  eigener Wert gesetzt ist, war von hier nicht einsehbar. Er signiert `remember_me`
  (B02) und die CSRF-Token. **Offene Frage an den Betreiber.**
- **Die Docker-Konfiguration liefert nicht die Ports, die die Konfiguration erwartet.**
  `compose.override.yaml` setzt `database.ports: ["5432"]` und `mailer.ports: ["1025","8025"]`
  ohne Host-Bindung; damit ist MySQL **nicht** auf 3306 erreichbar und Mailpit landet auf
  Zufallsports — während `.env.local` `127.0.0.1:3306` und `.env.dev`
  `smtp://localhost:1025` erwarten. Für diese Prüfung mit einer eigenen Compose-Datei im
  Scratchpad überbrückt (`!override`, DB auf 3307). Betrifft B01 nicht fachlich, aber
  jeden, der `make start` benutzt.
- **Nicht prüfbar von hier:** ob auf Produktion `MESSENGER_TRANSPORT_DSN=sync://` steht
  (entscheidet über BUG-07) und ob der Webserver fremde `Host`-Header abweist
  (entscheidet über FB-09, Host-Header-Poisoning der Bestätigungs-URL).

## Nächster Schritt

`/sdd-build B01` mit dem Auftrag, **BUG-01 bis BUG-08** zu beheben, danach erneut
`/sdd-qa B01`.

**Reihenfolge ist hier nicht beliebig:**

1. **BUG-03 vor BUG-01** — die Reparatur der Routenkollision öffnet einen
   ungedrosselten Mailversandweg auf ein fremdes Postfach.
2. **BUG-01 vor BUG-05** — ein `user_checker` sperrt alle bestehenden unbestätigten
   Konten aus, solange sie keine neue Mail anfordern können.
3. BUG-02, BUG-04, BUG-06, BUG-07 sind unabhängig und einzeln auslieferbar.
4. BUG-08 ist ein eigenes Vorhaben und gehört durch die volle Kette.
