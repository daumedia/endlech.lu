# Befunde — projektweit

Stand: 2026-08-23 · Quelle: die `qa-report.md` aller geprüften Features

Diese Liste wird von `sdd-qa` fortgeschrieben, nicht von Hand. Sie ist die Grundlage
des Auditberichts, den `/sdd-erfassen abschluss` daraus baut.

**Geprüft bisher:** B01 (17/20), B02 (16/17) und B03 (16/20) — alle **abgenommen**.
Beide Reparaturen sind **noch nicht auf `production`**.
Offen: B02–B26 (Status `rekonstruiert`).

**Zuordnung von Befunden:** Ein Befund steht bei dem Feature, in dem er **behoben** wird
— nicht bei dem, in dem er gefunden wurde. BF-04 wurde in B01 gefunden und ist seit
2026-08-23 dem Feature `01` zugeordnet; sonst hielte er B01 dauerhaft auf `review`,
obwohl dort nichts mehr zu reparieren ist.

> Nicht zu verwechseln mit `features/fehlbestand-uebersicht.md`. Jene sammelt, was beim
> **Lesen** des Codes auffiel, und ist eine Suchliste. Hier stehen nur Befunde, die in
> einer QA **ausgeführt und belegt** wurden.

## Offen

| ID | Feature | Befund | Grad | Fundstelle | Seit |
|---|---|---|---|---|---|
| BF-04 | **01** | Betroffenenrechte nicht bedienbar — keine Kontolöschung, kein Datenexport, kein Passwort-Zurücksetzen. **2026-08-23 aus B01 herausgelöst:** keine Reparaturaufgabe, sondern fehlende Funktionen über B01, B04 und B19 hinweg — läuft als reguläres Feature `01` durch die volle Kette | hoch | `src/Controller/ProfileController.php` (fehlend) | 2026-08-23 |
| BF-18 | B03 | Passkey-Challenge-Endpunkte ohne Rate Limit — 10 Anfragen an `/passkey/login/options` alle mit 200 beantwortet | mittel | `config/routes/webauthn.yaml`, `access_control` | 2026-08-24 |
| BF-17 | B02 | Gast auf `/de/logout` bekommt 403 statt einer Weiterleitung — Kehrseite von `enable_csrf` | niedrig | `config/packages/security.yaml` | 2026-08-24 |
| BF-15 | B02 | „Angemeldet bleiben" wirkt für `/profile` nicht (`IS_AUTHENTICATED_FULLY`), die Kopfzeile zeigt den Nutzer trotzdem als angemeldet | mittel | `config/packages/security.yaml`, `templates/base.html.twig` | 2026-08-24 |
| BF-16 | B02, B04 | **Rekonstruktion falsch:** B02/EC-04, B04/AK-13 und B04/FB-04 behaupten, Sitzungen und `remember_me`-Cookies überlebten eine Passwortänderung. Gemessen: Symfony entwertet beide. Kein Codefehler — eine falsche Spec, gegen die sonst geprüft würde | mittel | `features/B02-…/spec.md`, `features/B04-…/spec.md` | 2026-08-24 |
| BF-09 | B01 | Registrierformular verrät bestehende Konten (Enumeration). Die Meldung ist seit 2026-08-23 übersetzt, die Auskunft bleibt. Produktentscheidung OF-02 — setzt einen Passwort-Vergessen-Weg voraus (BF-04) | mittel | `src/Entity/User.php:15` | 2026-08-23 |
| BF-11 | B01, B14, B15 | Rate Limit verbraucht Kontingent auch bei **ungültigen** Formularen — 5 Tippfehler sperren eine Stunde aus, ohne dass ein Konto oder eine Mail entsteht | mittel | `src/Controller/RegistrationController.php:47`, `src/Controller/PartnerController.php:53` | 2026-08-23 |
| BF-10 | B14, B15, B23 | Mail-Locale bei asynchronem Versand — dieselbe Ursache wie BF-08, dort behoben. `WaitlistConfirmationService` und `Api\V1\AuthController` bauen ihre Mails unverändert | mittel | `src/Waitlist/WaitlistConfirmationService.php`, `src/Controller/Api/V1/AuthController.php` | 2026-08-23 |

## Behoben

| ID | Feature | Befund | Grad | Behoben am | Ausgeliefert |
|---|---|---|---|---|---|
| BF-01 | B01 | „Bestätigungsmail erneut senden" war unerreichbar — Routen-Requirement ergänzt | hoch | 2026-08-23 | **noch nicht** — Branch `fix/b01-registrierung-qa` |
| BF-02 | B01 | Registrierung ohne Rate Limit — Limiter `registration` (5/h) und `verify_resend` (3/h) | hoch | 2026-08-23 | **noch nicht** |
| BF-05 | B01, B04 | Roher Übersetzungsschlüssel — `form.password_mismatch` in allen vier `validators.*.yaml` | mittel | 2026-08-23 | **noch nicht** |
| BF-06 | B01 | Bestätigungstoken im Log — `doctrine`-Channel in `prod` ausgeschlossen. **Nur der prod-Weg**; der dev-Teil ist bewusst offen, siehe BF-12 | mittel | 2026-08-23 | **noch nicht** |
| BF-07 | B01 | Hartkodierte deutsche Meldung — `user.email_unique` statt Klartext (Enumeration bleibt offen, siehe BF-09) | mittel | 2026-08-23 | **noch nicht** |
| BF-08 | B01 | Mail-Locale bei asynchronem Versand — `->locale()` gesetzt (**nur B01**, B14/B15/B23 weiterhin betroffen) | mittel | 2026-08-23 | **noch nicht** |
| BF-13 | B02 | Anmeldung ohne Sperre — `login_throttling` mit 5 Versuchen je 15 Minuten ergänzt | **hoch** | 2026-08-24 | **noch nicht** |
| BF-14 | B02 | Abmelden ohne CSRF — `enable_csrf` plus POST-Formular in der Kopfzeile | niedrig | 2026-08-24 | **noch nicht** |

## Akzeptiert

Bewusst nicht behoben. Ohne Begründung und Datum ist ein Befund nicht akzeptiert,
sondern vergessen.

| ID | Feature | Befund | Grad | Begründung | Beschlossen am |
|---|---|---|---|---|---|
| BF-12 | B01 | Bestätigungstoken steht weiterhin im `dev`-Log (`doctrine.DEBUG`) | niedrig | Ein Entwicklungslog ohne SQL-Historie wäre für die Fehlersuche wertlos, und es verlässt den Rechner nicht. Der Weg, auf dem der Token in `prod` ins Hoster-Log geriet, ist mit BF-06 geschlossen. Deshalb bleibt AK-17 formal durchgefallen, ohne dass ein Befund offen ist | 2026-08-23 |
| BF-03 | B01 | Unbestätigte Konten haben vollen Zugang — kein `user_checker` | hoch | Ein `user_checker` sperrt bestehende unbestätigte Konten im Moment des Deployments aus; wie viele das auf Produktion sind, ist nicht einsehbar. Betreiberentscheidung gegen einen globalen Zwang; die Voraussetzung für eine spätere Umstellung ist mit der Reparatur von BF-01 geschaffen. Dokumentiert als OF-01 der Spec | 2026-08-23 |

## Muster

Was in mehr als einem Feature auftritt — der Grund, warum diese Liste existiert.

- **Fehlende Rate Limits im Browser-Weg (BF-02 behoben, BF-13 offen).** Die Anwendung
  drosselt die API (`api_login`: 5/min, zweimal belegt: ab dem sechsten Versuch 429), aber
  kaum einen Web-Endpunkt. Für die Registrierung ist es behoben, für die **Anmeldung**
  nicht — dort wurden 20 Fehlversuche gegen das Admin-Konto alle angenommen. Offen bleiben
  nach der Rückerfassung Passwortwechsel, Passkey-Challenge, Vorschläge und
  Datensatz-Download (M-01 in `fehlbestand-uebersicht.md`). Das Muster hat sich damit
  bestätigt: Geschützt ist der Weg, den eine App nimmt; ungeschützt der, den ein Browser
  nimmt. Mit BF-18 kommt ein dritter Fall dazu: die sprachfreien Passkey-Endpunkte fallen
  weder unter die Web- noch unter die API-Limiter.
- **Eine Rekonstruktion kann falsch sein (BF-16).** B02/EC-04 und zwei Stellen in B04
  behaupteten, eine Passwortänderung lasse fremde Sitzungen unberührt — geschlossen aus
  dem Projektcode, der tatsächlich nichts dergleichen tut. Gemessen erledigt Symfony es
  selbst. Bei jedem weiteren Feature gilt: Was das Framework leistet, steht nicht im
  Projektcode und lässt sich nur durch Ausführen feststellen.
- **Ein Übersetzungsschlüssel, zwei Features (BF-05, behoben).** Derselbe Griff in die
  falsche Übersetzungsdomäne betraf Registrierung und Passwortwechsel. Die Reparatur wirkt
  für beide; bei der QA von B04 ist nur noch zu bestätigen.
- **Locale geht bei asynchroner Verarbeitung verloren (BF-08 behoben, BF-10 offen).**
  Betrifft alle vier Mailwege des Projekts. In B01 mit `->locale()` behoben — derselbe
  Einzeiler steht für B14, B15 und B23 noch aus. Auf Produktion derzeit unwirksam, weil dort
  synchron versandt wird; kippt, sobald ein Messenger-Worker eingeführt wird. Genau das ist
  für die Monats-Snapshots vorgesehen (B18/AK-17).
- **Kontingent für ungültige Eingaben (BF-11).** Beide Rate-Limiter des Projekts —
  der bestehende in `PartnerController` und der neue in `RegistrationController` —
  konsumieren vor der Gültigkeitsprüfung. Wer sich vertippt, verbraucht dasselbe
  Kontingent wie ein Angreifer. Das ist keine Nachlässigkeit der Reparatur, sondern
  ein Muster, das sie übernommen hat; bei der QA von B14/B15 mitzuprüfen.
- **Betroffenenrechte (BF-04, jetzt Feature `01`).** Kein Löschweg, kein Export, kein
  Widerruf. Die Rückerfassung nennt dieselbe Lücke für die Wartelisten (B14, B15, B22).
  Es ist die einzige Befundgruppe mit rechtlicher Frist — und die technischen
  Voraussetzungen (Kaskaden, `SET NULL`, Dateilöschung) sind bereits vollständig da.
  **Seit 2026-08-23 als eigenes Feature geführt**, weil ein Befund, der drei Features
  betrifft und keine Reparatur ist, sonst eines davon dauerhaft blockiert.

## Reihenfolgeabhängigkeiten

Nicht jeder Befund lässt sich einzeln beheben:

- **BF-02 vor BF-01** — die Reparatur der Routenkollision öffnet sonst einen
  ungedrosselten Mailversand auf ein fremdes Postfach.
- **BF-01 vor BF-03** — ein `user_checker` sperrt alle bestehenden unbestätigten Konten
  aus, solange sie keine neue Bestätigungsmail anfordern können.
