# Befunde — projektweit

Stand: 2026-08-23 · Quelle: die `qa-report.md` aller geprüften Features

Diese Liste wird von `sdd-qa` fortgeschrieben, nicht von Hand. Sie ist die Grundlage
des Auditberichts, den `/sdd-erfassen abschluss` daraus baut.

**Geprüft bisher:** B01 (17/20), B02 (16/17), B03 (16/20), B04 (23/24 im zweiten
Durchlauf), B23 (34/35 im zweiten Durchlauf), B19 (17/17, davon eines nicht prüfbar),
B14 (28/28), B15 (27/27), B22 (30/30) — alle abgenommen. B23s beide
*hoch*-Befunde sind repariert und nachgemessen; dabei fielen drei neue an, zwei davon
als Folge der Reparatur.
Alle Reparaturen sind **noch nicht auf `production`**.
Offen: B05–B22, B24–B26 (Status `rekonstruiert`).

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
| BF-29 | B23 | Der `Host`-Header steuert die ausgegebenen Bild-URLs. **Bewusst nicht im Code behoben:** `trusted_hosts` hätte bei leerem Wert jeden Host abgewiesen. Der Weg über `APP_API_BASE_URL` ist in `.env` dokumentiert — **Serveraufgabe, gehört auf die Deployment-Liste** | niedrig | `src/Api/AssetUrlBuilder.php`, `.env` | 2026-08-24 |
| BF-40 | B22 | Die Verwaltungsliste skaliert nicht — kein Blättern, keine Obergrenze; die Restaurant-Auswahlliste lädt den **kompletten Kernbestand** (`findBy([], …)`). Blättern ist im Projekt vorhanden (B05, B20) und hier nur nicht angewandt | niedrig | `AdminWaitlistController.php:96`, `PartnerWaitlistEntryRepository::findFiltered()` | 2026-08-24 |
| BF-39 | B15 | Die Typansage für Screenreader sagt „Organisation" statt „Verein" — der Oberbegriff der ganzen Seite. Beim Slug war dieselbe Verwechslung erkannt und behoben (`vereine`, mit Kommentar); beim Label nicht | niedrig | `src/Enum/OrganisationType.php:31` | 2026-08-24 |
| BF-37 | B14 | Die Einwilligung lässt sich nicht widerrufen — 0 Routen, 0 Abmeldelinks, keine Löschfunktion in der Verwaltung. Verstärkt durch die fehlende Löschfrist: `findPendingOlderThan()` existiert, wird aber **nur im Test** aufgerufen — toter Code, der eine Aufräumroutine vortäuscht. Art. 7 Abs. 3 / Art. 5 Abs. 1 lit. e DSGVO | mittel | `src/Repository/PartnerWaitlistEntryRepository.php:31` (ungenutzt) | 2026-08-24 |
| BF-36 | B14 | Der Bestätigungstoken läuft nie ab — anders als `User::generateVerificationToken()` mit 24 Stunden | niedrig | `src/Entity/PartnerWaitlistEntry.php` | 2026-08-24 |
| BF-38 | B14 | Partner- und Organisationsliste teilen sich `limiter.partner_waitlist` — nach fünf Partner-Submits liefert das Organisationsformular 429 | niedrig | `PartnerController.php:33`, `OrganisationController.php:85` | 2026-08-24 |
| BF-33 | B19 | Open Redirect in `admin_set_locale` — `Referer` wird ungeprüft übernommen; `https://boeswillig.example`, `//evil.example/x` und `javascript:alert(1)` alle drei akzeptiert. Zielt auf den einzigen Zugang **ohne zweite Stufe** | mittel | `src/Controller/AdminLocaleController.php:26` | 2026-08-24 |
| BF-34 | B19 | Der Sprachumschalter im Verwaltungsbereich wirkt nicht — `_locale` landet in der Sitzung, aber kein `LocaleSubscriber` liest ihn; die Seite bleibt deutsch. **Beantwortet B19/OF-02** | niedrig | `src/Controller/AdminLocaleController.php` | 2026-08-24 |
| BF-35 | B19 | Keine Drosselung auf Verwaltungsschreibvorgängen — acht Umschaltungen in Folge, alle 302. **Fünfte Wiederholung von M-01** | niedrig | `admin_*`-Routen | 2026-08-24 |
| BF-30 | B23 | Die Moderationsschlange lässt sich fluten — 40 Aufrufe erzeugten 40 Vorschläge, alle 202. **Vierte Wiederholung von M-01**, und die erste, bei der die Konvention schon formuliert war | mittel | `src/EventSubscriber/ApiRateLimitSubscriber.php:54` | 2026-08-24 |
| BF-31 | B23 | Die `id` der 202-Antwort ist eine Vorschlags-ID im Rumpf eines Restaurant-Endpunkts — `GET /restaurants/{id}` liefert bei Überlappung der Zähler ein **fremdes** Restaurant mit 200 | mittel | `Api/V1/RestaurantApiController::create()` | 2026-08-24 |
| BF-32 | B23 | Wer über die API einreicht, sieht seinen Vorschlag nirgends — `/me/submissions` liest nur genehmigte Restaurants | niedrig | `Api/V1/MeController::submissions()` | 2026-08-24 |
| BF-21 | B04 | Adressänderung ohne Rate Limit — zehn Durchläufe erzeugten 20 Mails, davon 10 an ein frei gewähltes fremdes Postfach. **Nebenwirkung der BF-19-Reparatur**: Vorher verschickte dieser Weg gar keine Mail | mittel | `src/Controller/ProfileController.php::edit()` | 2026-08-24 |
| BF-22 | B04 | Ungültiges Stammdatenformular mit geänderter Adresse meldet den Nutzer ab — `handleRequest()` setzt `setEmail()` auch bei fehlgeschlagener Validierung, der veränderte Nutzer wandert in die Sitzung. Bestand vor der Reparatur | mittel | `src/Controller/ProfileController.php::edit()` | 2026-08-24 |
| BF-23 | B01, B04 | Bestätigungstoken im `request`-Kanal (`Matched route`), der in `prod` **nicht** ausgeschlossen ist — **BF-06 war nur halb behoben**. 31 Zeilen für `app_email_change_confirm`, 22 für `app_verify_email` | mittel | `config/packages/monolog.yaml` | 2026-08-24 |
| BF-18 | B03 | Passkey-Challenge-Endpunkte ohne Rate Limit — 10 Anfragen an `/passkey/login/options` alle mit 200 beantwortet | mittel | `config/routes/webauthn.yaml`, `access_control` | 2026-08-24 |
| BF-17 | B02 | Gast auf `/de/logout` bekommt 403 statt einer Weiterleitung — Kehrseite von `enable_csrf` | niedrig | `config/packages/security.yaml` | 2026-08-24 |
| BF-15 | B02 | „Angemeldet bleiben" wirkt für `/profile` nicht (`IS_AUTHENTICATED_FULLY`), die Kopfzeile zeigt den Nutzer trotzdem als angemeldet | mittel | `config/packages/security.yaml`, `templates/base.html.twig` | 2026-08-24 |
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
| BF-16 | B02, B04 | **Rekonstruktion falsch** — B02/EC-04, B04/AK-13 und B04/FB-04 behaupteten, Sitzungen und `remember_me`-Cookies überlebten eine Passwortänderung. **Beide Specs am 2026-08-24 berichtigt**, Regressionstest angelegt | mittel | 2026-08-24 | entfällt (Dokumentation) |
| BF-13 | B02 | Anmeldung ohne Sperre — `login_throttling` mit 5 Versuchen je 15 Minuten ergänzt | **hoch** | 2026-08-24 | **noch nicht** |
| BF-24 | B23 | API umging die Moderation — `create()` legt jetzt einen `RestaurantSuggestion` an und antwortet mit 202; `cuisines` ohne `findOrCreateByName()`; nicht übermittelte Merkmale sind `UNKNOWN` statt `false` | **hoch** | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-25 | B23 | `register` unter dem schwachen Limit — eigener Limiter `api_register` (5/h) | **hoch** | 2026-08-24 | **noch nicht** |
| BF-26 | B23 | Formatvertrag der JWT-Antworten — `ApiAuthenticationFailureSubscriber` für alle vier Fälle des Bundles | mittel | 2026-08-24 | **noch nicht** |
| BF-27 | B23 | Zu lange Küchen-Angabe → 422 statt 500 | niedrig | 2026-08-24 | **noch nicht** |
| BF-28 | B23 | 404-Meldungen ohne interne Klassennamen | niedrig | 2026-08-24 | **noch nicht** |
| BF-14 | B02 | Abmelden ohne CSRF — `enable_csrf` plus POST-Formular in der Kopfzeile | niedrig | 2026-08-24 | **noch nicht** |
| BF-19 | B04 | E-Mail-Änderung ohne erneute Bestätigung — neue Adresse wird nur noch vorgemerkt (`User::$pendingEmail`), Bestätigungslink an die neue und Warnung an die bisherige Adresse | **hoch** | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-20 | B04 | Passwortänderung ohne Rate Limit — Limiter `password_change` (5 je 15 Minuten), gezählt **am Konto** statt an der IP | niedrig | 2026-08-24 | **noch nicht** |

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
