# B01 · Registrierung & E-Mail-Bestätigung — Spezifikation

Status: `approved` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**
> QA vom 2026-08-23: 15 von 20 Kriterien bestanden, 8 Befunde — siehe `qa-report.md`.
> Reparatur vom 2026-08-23: 6 Befunde behoben, erneute Prüfung steht aus.

> Dieses Dokument beschreibt, **was der Code heute tut** — nicht, was er tun sollte.
> Kriterien mit ⚠ beschreiben Verhalten, das fragwürdig aussieht, aber so im Bestand
> steht. Was fehlt, steht unter [Fehlbestand](#fehlbestand) und ist **kein** Kriterium.

## Zweck

Ein Besucher legt sich ein Konto mit Name, E-Mail und Passwort an und bestätigt seine
Adresse über einen Link, der 24 Stunden gilt. Danach steht ihm der Vorschlags-Wizard
offen — der einzige Ort, an dem die Bestätigung tatsächlich verlangt wird.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| — | — | B01 ist die Wurzel der Kontokette |

Umgekehrt hängen an B01: B02 (Anmeldung), B03 (Passkeys), B04 (Profil), B11
(Vorschlag), B23 (API-Registrierung).

## User Stories

- **US-01** · Als Besucher möchte ich mir ein Konto anlegen, damit ich Restaurants
  vorschlagen kann.
- **US-02** · Als neuer Nutzer möchte ich meine E-Mail-Adresse bestätigen, damit das
  Konto als echt gilt.
- **US-03** · Als Nutzer, dessen Bestätigungsmail nicht ankam, möchte ich sie erneut
  anfordern können.

## Nicht im Scope

- Anmeldung selbst → B02
- Registrierung über die REST-API → B23 (eigener Weg, eigenes Verhalten:
  Anti-Enumeration, kein Token in der Antwort)
- Konto per Passkey anlegen → strukturell ausgeschlossen, siehe B03
- Passwort zurücksetzen → existiert nicht, siehe FB-05

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Gast ruft `/{locale}/register` auf, wenn die Seite lädt,
  dann erscheint ein Formular mit Name, E-Mail, Passwort und Passwortwiederholung.
- **AK-02** · Angenommen, ein **angemeldeter** Nutzer ruft `/{locale}/register` auf,
  wenn die Seite lädt, dann wird er auf die Startseite weitergeleitet, ohne das
  Formular zu sehen.
- **AK-03** · Angenommen, der Name ist kürzer als 2 Zeichen, wenn abgeschickt wird,
  dann antwortet der Server mit HTTP 422 und zeigt die Meldung zu `user.name_min`.
- **AK-04** · Angenommen, das Passwort ist kürzer als 8 Zeichen, wenn abgeschickt wird,
  dann antwortet der Server mit HTTP 422 und zeigt die Meldung zu `user.password_min`.
- **AK-05** · Angenommen, die beiden Passwortfelder stimmen nicht überein, wenn
  abgeschickt wird, dann antwortet der Server mit HTTP 422 und zeigt
  `form.password_mismatch`.
- **AK-06** · Angenommen, alle Felder sind gültig, wenn abgeschickt wird, dann wird ein
  Konto mit `isVerified = false` und der Rolle `ROLE_USER` angelegt, eine
  Bestätigungsmail versandt und auf `/{locale}/verify` weitergeleitet, wo die
  Erfolgsmeldung `flash.register_success` steht.
- **AK-07** · Angenommen, ein Konto wurde eben angelegt, wenn man in die Datenbank
  sieht, dann steht dort ein Passwort-Hash, nie das eingegebene Passwort im Klartext.
- **AK-08** · Angenommen, die Registrierung war erfolgreich, wenn die Weiterleitung
  erfolgt, dann ist der Nutzer **nicht** angemeldet.
- **AK-09** · Angenommen, ein gültiger, nicht abgelaufener Bestätigungslink wird
  aufgerufen, wenn die Seite lädt, dann wird `isVerified` auf `true` gesetzt, Token und
  Ablaufzeitpunkt werden geleert und der Nutzer landet auf `/{locale}/login` mit
  `flash.verify_success`.
- **AK-10** · Angenommen, ein Bestätigungslink ist älter als 24 Stunden, wenn er
  aufgerufen wird, dann bleibt das Konto unbestätigt und es erscheint
  `flash.verify_expired` auf `/{locale}/verify`.
- **AK-11** · Angenommen, ein Token existiert nicht (oder wurde bereits eingelöst),
  wenn der Link aufgerufen wird, dann erscheint `flash.verify_invalid_link` und der
  Nutzer landet auf der Startseite.
- **AK-12** · Angenommen, der Mailversand wirft eine `TransportExceptionInterface`,
  wenn registriert wird, dann bleibt das Konto **gespeichert** und es erscheint die
  Warnung `flash.register_email_failed` statt der Erfolgsmeldung.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-13** ⚠ · Angenommen, ein Konto ist **nicht** bestätigt, wenn sich der Nutzer mit
  korrektem Passwort anmeldet, dann gelingt die Anmeldung vollständig — er erreicht
  Profil, Passkey-Verwaltung und alle geschützten Seiten außer dem Vorschlags-Wizard.
  *(So verhält sich der Code heute: `config/packages/security.yaml` konfiguriert keinen
  `user_checker`, und `User` implementiert kein `isEnabled()`. Die einzige Prüfung auf
  `isVerified()` außerhalb dieses Features steht in `CommunityController.php:29`.
  Folge: Die E-Mail-Bestätigung ist praktisch folgenlos.)*

- **AK-14** ⚠ · Angenommen, eine E-Mail-Adresse ist bereits registriert, wenn sie im
  Registrierformular erneut eingegeben wird, dann erscheint „Diese E-Mail-Adresse ist
  bereits registriert."
  *(So verhält sich der Code heute: `#[UniqueEntity]` auf `App\Entity\User:15`. Die
  REST-API tut an dieser Stelle ausdrücklich das Gegenteil — sie antwortet generisch,
  um User-Enumeration zu verhindern (B23). Über den Web-Weg ist dieselbe Information
  frei abfragbar, der Schutz der API damit wirkungslos.)*

- **AK-15** ⚠ · Angenommen, ein Nutzer klickt auf der Hinweisseite `/{locale}/verify`
  auf „Bestätigungsmail erneut senden", wenn der Link geöffnet wird, dann erscheint
  `flash.verify_invalid_link` und er landet auf der Startseite — es wird **keine** Mail
  versandt.
  *(So verhält sich der Code heute: `/verify/{token}` ist in
  `EmailVerificationController.php:35` vor `/verify/resend` (Zeile 59) deklariert und
  fängt die Anfrage mit `token = "resend"` ab. Nachweis:
  `php bin/console router:match /de/verify/resend` → `app_verify_email`. Die Methode
  `resend()` ist damit toter Code; der Link in
  `templates/email_verification/notice.html.twig:32` führt ins Leere.)*

### Datenschutz und Missbrauchsschutz

Katalog: `~/.claude/sdd/sicherheit.md`. Was hier nicht steht, prüft `sdd-qa` nicht.

- **AK-16** · Angenommen, ein Konto wird angelegt, wenn geprüft wird, welche
  personenbezogenen Daten entstehen, dann sind es genau: Name, E-Mail-Adresse,
  Passwort-Hash, Anlagezeitpunkt. Keine besonderen Kategorien nach Art. 9 DSGVO.
- **AK-17** · Angenommen, eine Registrierung läuft durch, wenn die Anwendungs-Logs
  gelesen werden, dann enthalten sie weder das Klartextpasswort noch den
  Bestätigungstoken.
- **AK-18** · Angenommen, ein Bestätigungstoken wird erzeugt, wenn er untersucht wird,
  dann besteht er aus 32 kryptografisch zufälligen Bytes in Hex-Darstellung
  (`bin2hex(random_bytes(32))`, 64 Zeichen) und ist nicht aus Nutzerdaten ableitbar.
- **AK-19** · Angenommen, ein Token wurde eingelöst, wenn derselbe Link erneut
  aufgerufen wird, dann greift er nicht mehr (Token wurde auf `null` gesetzt).
- **AK-20** · Angenommen, die Registrierung wird abgeschickt, wenn die ausgehenden
  Verbindungen betrachtet werden, dann geht genau eine E-Mail an den Versanddienst
  (Produktion: Brevo API) — mit Name, Adresse und Bestätigungs-URL im Inhalt.

## Edge Cases

- **EC-01** · Zwei Registrierungen mit derselben Adresse gleichzeitig → die zweite
  scheitert am `UNIQUE`-Index auf `user.email` (DB-Ebene), nicht nur an der Validierung.
- **EC-02** · Ein zweites `generateVerificationToken()` (über `resend`) überschreibt den
  ersten Token — der zuvor versandte Link wird ungültig. Im Bestand nicht auslösbar,
  siehe AK-15.
- **EC-03** · `verificationTokenExpiresAt = null` → `isVerificationTokenExpired()`
  liefert `true`. Ein bereits bestätigtes Konto kann also nie versehentlich über einen
  Alt-Link erneut verifiziert werden.
- **EC-04** · Passwort länger als 4096 Zeichen → Abweisung durch `Length(max: 4096)`;
  schützt vor DoS über teure Hash-Berechnung.
- **EC-05** · Registrierung mit einer Adresse, die im Formular gültig ist, aber beim
  Versand abgewiesen wird → Konto bleibt bestehen (AK-12), ist aber nie bestätigbar
  und ohne Verwaltungszugriff auch nicht löschbar (siehe FB-04).

## Fehlbestand

Nicht vorhanden, aus dem Code belegt. **Kein Kriterium** — `sdd-qa` prüft nichts davon
als bestanden, sondern nimmt es als Suchliste.

- **FB-01 · Kein Rate Limit auf der Registrierung.** `config/packages/framework.yaml`
  definiert drei Limiter (`api_anonymous`, `api_login`, `partner_waitlist`); keiner
  greift auf `/{locale}/register`, und `RegistrationController` bezieht keinen.
  *Folge:* Unbegrenzt viele Konten pro IP, und mit jedem Konto ein Mailversand über die
  Brevo-Quota des Betreibers. Der Katalog nennt Rate Limits für Registrierung
  ausdrücklich als Pflicht.
- **FB-02 · Kein Rate Limit auf dem erneuten Versand.** `resend()` erzeugt Token und
  Mail ohne Zählung. *Folge:* Wäre die Route erreichbar (siehe AK-15), ließe sich damit
  ein fremdes Postfach zumüllen — die Adresse steht am angemeldeten Konto.
- **FB-03 · Die Bestätigung wird nirgends erzwungen.** Kein `user_checker`, kein
  `isEnabled()`. *Folge:* siehe AK-13 — das Feature hat außer beim Vorschlags-Wizard
  keine Wirkung.
- **FB-04 · Kein Löschweg für das Konto.** Weder `ProfileController` noch die API
  kennen eine Löschfunktion; es gibt keine Route und keine Oberfläche dafür.
  *Folge:* Löschpflicht nach Art. 17 DSGVO ist nur über einen direkten
  Datenbankeingriff erfüllbar. Der Katalog nennt das als Pflicht, nicht als Wunsch.
- **FB-05 · Kein Weg, ein vergessenes Passwort zurückzusetzen.** Keine Route, kein
  Formular, kein Mailtemplate. *Folge:* Wer sein Passwort vergisst und keinen Passkey
  hinterlegt hat, verliert den Zugang endgültig.
- **FB-06 · Kein Datenexport (Auskunftsrecht).** Kein Endpunkt, keine Oberfläche.
- **FB-07 · Die Meldung des `UniqueEntity`-Constraints ist nicht übersetzt.**
  `src/Entity/User.php:15` trägt den deutschen Klartext „Diese E-Mail-Adresse ist
  bereits registriert." statt eines Übersetzungsschlüssels — als einzige
  Validierungsmeldung des Features. *Folge:* In `lb`, `fr` und `en` erscheint deutscher
  Text.
- **FB-09 · Kein `trusted_hosts`, während die Bestätigungs-URL aus dem Request-Host
  gebaut wird.** `RegistrationController` erzeugt den Link mit
  `UrlGeneratorInterface::ABSOLUTE_URL`; Symfony nimmt dafür Schema und Host aus der
  eingehenden Anfrage. `config/packages/framework.yaml` setzt weder `trusted_hosts`
  noch `trusted_proxies`. *Folge:* Wer eine Registrierung mit manipuliertem
  `Host`-Header abschickt, bekommt eine Bestätigungsmail an die Adresse des Opfers,
  deren Link auf seinen eigenen Server zeigt — klickt das Opfer, hat der Angreifer den
  Token. Ob der Angriff durchgeht, hängt zusätzlich vom Webserver ab (ein Apache mit
  gesetztem `ServerName` weist fremde Hosts ab); im Anwendungscode ist nichts
  dagegen vorgesehen. Betrifft ebenso B14/B15, die ihre Bestätigungslinks genauso
  bauen.
- **FB-08 · Keine Prüfung auf Wegwerf-Adressen und keine Passwortqualität über die
  Länge hinaus.** `Length(min: 8)` ist die einzige Anforderung; kein Abgleich gegen
  bekannte Leak-Listen, keine Zeichenklassen.

## Offene Fragen

- **OF-01** · Soll die E-Mail-Bestätigung den Zugang tatsächlich sperren (AK-13)? Ein
  `user_checker` wäre ein Zweizeiler, macht aber alle unbestätigten Bestandskonten
  sofort zugangslos, solange FB-02 und AK-15 nicht behoben sind. — Betreiber
- **OF-02** · Soll der Web-Weg dieselbe Anti-Enumeration bekommen wie die API (AK-14)?
  Das kostet die verständliche Fehlermeldung „bereits registriert" und braucht dann
  zwingend FB-05 (Passwort vergessen) als Ausweg. — Betreiber
  **Entschieden 2026-08-25:** Ja, umgesetzt (BF-09, 2026-08-25). Die Meldung im Formular ist weg; die bestehende Adresse bekommt stattdessen einen Hinweis per Mail. Das Passwort wird in beiden Zweigen gehasht, damit die Antwortzeit nichts verrät (528 gegen 522 ms).

- **OF-03** · ~~Reihenfolge der Reparaturen: AK-15 (Routen-Kollision) ist ein Einzeiler,
  öffnet aber FB-02 (Mailversand ohne Limit). Beide zusammen ausliefern.~~ — **erledigt
  2026-08-23:** beides zusammen behoben, `verify_resend` mit eigenem Limit (3/Stunde).
- **OF-01 · Stand 2026-08-23:** Betreiber hat entschieden, **keinen** globalen
  `user_checker` zu bauen. Der Zugang bleibt wie er ist; die Voraussetzung für eine
  spätere Umstellung ist mit der Reparatur von AK-15 geschaffen. Bleibt als BF-03 offen.
- **OF-02 · Stand 2026-08-23:** Die Meldung ist übersetzt, die Enumeration bleibt.
  Entscheidung offen, Voraussetzung wäre ein Passwort-Vergessen-Weg (BF-04).

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Token-Länge | 32 Zufallsbytes → 64 Hex-Zeichen | passt exakt in `VARCHAR(64)`; vgl. `webauthnHandle`, der aus demselben Grund nur 16 Bytes nutzt |
| 2 | Gültigkeitsdauer | 24 Stunden, im Token-Generator festgeschrieben | nicht konfigurierbar; Grund nicht erkennbar |
| 3 | Verhalten bei Versandfehler | Konto bleibt gespeichert, Warnung statt Erfolg | dieselbe Reihenfolge wie bei den Wartelisten (B14/B15): erst sichern, dann senden |
| 4 | Anmeldung nach Registrierung | findet **nicht** statt | Grund nicht erkennbar; ein `UserAuthenticatorInterface`-Aufruf fehlt schlicht |
| 5 | `plainPassword` als `mapped: false` | ja | verhindert, dass das Klartextpasswort je an der Entity hängt |
