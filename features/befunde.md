# Befunde — projektweit

Stand: 2026-08-25 · Quelle: die `qa-report.md` aller geprüften Features

Diese Liste wird von `sdd-qa` fortgeschrieben, nicht von Hand. Sie ist die Grundlage
des Auditberichts, den `/sdd-erfassen abschluss` daraus baut.

**Geprüft bisher:** B01 (17/20), B02 (16/17), B03 (16/20), B04 (23/24 im zweiten
Durchlauf), B23 (34/35 im zweiten Durchlauf), B19 (17/17, davon eines nicht prüfbar),
B14 (28/28), B15 (27/27), B22 (30/30), B17 (25/25) — abgenommen.
B10 (24/24 im zweiten Durchlauf), B18 (29/29), B11 (18/19, eines nicht prüfbar), B20 (19/20), B21 (20/20), B09 (18/18), B05 (24/24), **B06 (23/23 — das erste Feature ohne eigenen Befund)**, B07 (17/17, eines nicht ausgeführt), B08 (16/16) — abgenommen.
B12 (15/15 nach der Reparatur), B13 (14/14), B16 (29/29), B24 (16/16) — abgenommen.

**2026-08-25 · Alle 72 Befunde sind behoben.** Der Durchgang lief über zehn
Blöcke; die Reihenfolge folgte dem Schweregrad, nicht der Feature-Nummer. Dabei
sind vier Muster geschlossen worden, die je mehrere Features betrafen:

| Muster | Vorkommen | Antwort |
|---|---|---|
| M-01 · Ungedeckelte Wege | 7 | `ActionLimiter` plus sechs neue Limiter; `LimiterCoverageTest` prüft die Konvention |
| Fehlende Eingabeprüfung → 500 | 4 | `empty_data`, Längen- und Slug-Kürzung |
| Fehlende Übersetzungen | 2 | Scanner über 736 Template-Schlüssel und 187 Formularangaben |
| Betroffenenrechte | 3 | Feature `01` |

Zwei davon fielen erst beim Reparieren auf: ein zweiter 500er im
Vorschlags-Assistenten (beim Messen des Rate-Limits) und eine Lücke im eigenen
Katalog-Scanner, der Beschriftungen nicht erfasste.

**Alle Reparaturen liegen auf `dev` bzw. `fix/befunde-abarbeiten` und sind noch
nicht auf `production`.**

**Zuordnung von Befunden:** Ein Befund steht bei dem Feature, in dem er **behoben** wird
— nicht bei dem, in dem er gefunden wurde. BF-04 wurde in B01 gefunden und ist seit
2026-08-23 dem Feature `01` zugeordnet; sonst hielte er B01 dauerhaft auf `review`,
obwohl dort nichts mehr zu reparieren ist.

> Nicht zu verwechseln mit `features/fehlbestand-uebersicht.md`. Jene sammelt, was beim
> **Lesen** des Codes auffiel, und ist eine Suchliste. Hier stehen nur Befunde, die in
> einer QA **ausgeführt und belegt** wurden.

## Offen

Keine. Alle vier Befunde der `02`-QA (BF-73/74/75/76) sind über fünf Durchläufe behoben und
verifiziert — siehe unten. Die 72 Rückerfassungs-Befunde ebenfalls.

> Nichts davon liegt auf `production`: Feature `02` auf
> `feature/02-barrierefreiheit-plattform`, die Rückerfassung auf `dev`/`fix/befunde-abarbeiten`.

## Behoben

| ID | Feature | Befund | Grad | Behoben am | Ausgeliefert |
|---|---|---|---|---|---|
| BF-74 | 02 | Cookie-Banner fing beim `connect()` den Fokus; `focus()` nur noch in `reopen()` (nutzergetriggert) — Skip-Link ist wieder erstes Tab-Ziel (playwright PASS) | hoch | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-73 | 02 | Mailer fing nur `Mailer\TransportException`; catch um `Messenger\TransportException` erweitert (EC-04), Regressionstest ergänzt | niedrig | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-75 | 02 | Sortier-Leiste auf `/de/restaurants` mit `flex-wrap` versehen; kein 320px-Overflow mehr über alle öffentlichen Routen (AK-14) | mittel | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-76 | 02 | `focus:outline-none` durch echte `outline` ersetzt — `OpeningHourType`, `_passkey_manage`, 5 Profil-Felder; Fokus im Kontrastmodus sichtbar (AK-04/AK-40). Gefunden durch forced-colors + `AccessibilityInteractionTest` | mittel | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-04 | **01** | Feature `01` gebaut — Konto löschen, Daten exportieren, Passwort zurücksetzen, Einwilligung widerrufen | hoch | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-09 | B01 | Anti-Enumeration im Registrierformular; `UniqueEntity` in Gruppe `strict`, Hash in beiden Zweigen (528 vs. 522 ms) | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-10 | B14, B15, B23 | `->locale()` in `WaitlistConfirmationService` und `Api\V1\AuthController` | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-11 | B01, B14, B15 | `ActionLimiter` — Kontingent wird erst verbraucht, wenn die Handlung stattfindet | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-15 | B02 | `IS_AUTHENTICATED_REMEMBERED` für `/profile` | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-17 | B02 | `GuestLogoutSubscriber` — Weiterleitung statt 403, CSRF-Schutz unverändert | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-18 | B03 | Limiter `passkey_challenge` (30 je 15 min) über `RouteRateLimitSubscriber` | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-21 | B04 | Limiter `email_change` (3/h), am Konto gezählt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-22 | B04 | Adresse wird auch im Fehlerfall zurückgesetzt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-23 | B01, B04 | `!request` aus dem `fingers_crossed`-Puffer | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-29 | B23 | In `.env` erklärt, warum `trusted_hosts` nicht aus der Umgebung geht; `deploy.sh` warnt bei fehlendem `APP_API_BASE_URL` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-30 | B23 | Limiter `suggestion_submit` (10/h), am Konto | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-31 | B23 | `id` → `submissionId` in der 202-Antwort | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-32 | B23 | `/me/submissions` zeigt Vorschläge mit `state` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-33 | B19 | Referer ist nur noch Wegweiser; der Router baut das Ziel | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-34 | B19 | Sprachwechsel im Admin schreibt den Pfad um statt die Sitzung | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-35 | B19 | Limiter `admin_write` (120 je 5 min), GET zählt nicht | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-36 | B14 | Bestätigungslinks verfallen nach sieben Tagen, 410 statt 404 | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-37 | B14 | Abmeldelink in jeder Wartelisten-Mail; der Widerruf löscht | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-38 | B14 | Eigener Limiter `organisation_waitlist` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-39 | B15 | „Verein" statt „Organisation", im Enum und in vier Katalogen | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-40 | B22 | Auswahlliste auf 50 begrenzt, serverseitige Suche | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-41 | B17 | `fieldNotes` in den Datensatz-Metadaten erklären `verified`, `assessed`, `accessibilityScore` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-42 | B17 | Limiter `open_dataset` (60/h) mit `Retry-After` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-43 | B17 | CSV-Werte mit führendem `= + - @` bekommen ein Apostroph (OWASP) | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-47 | B18 | Überschreiben ist ein eigener Knopf mit Rückfrage | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-48 | B18 | `src/Schedule.php` sagt selbst, dass es auf Production nicht feuert | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-49 | B11 | `assessedFeatures` hält fest, wonach jemand gesehen hat — `UNKNOWN` zählt nicht | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-50 | B11 | Limiter `suggestion_submit`, geteilt mit dem API-Weg | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-51 | B20 | `empty_data => ''` an sechs Pflichtfeldern — 422 statt 500 | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-52 | B20 | 25 je Seite plus Suche über Name und Stadt | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-53 | B20 | `RestaurantImageFileListener` auf `postRemove`, dazu `app:uploads:prune` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-54 | B21 | Genehmigen und Ablehnen prüfen den Status — dreimal abgeschickt ergibt ein Restaurant | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-55 | B21 | Ablehnungsmail an den Einreicher, in der Sprache der Einreichung | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-56 | B21 | Türbreite und Tischabstand im Assistenten; wandern bei der Genehmigung mit | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-57 | B09 | MIME-Whitelist im `ImageUploadService`; Endung folgt dem echten Typ | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-58 | B09 | 4-MB-Grenze plus verständliche Meldung bei `post_max_size` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-59 | B05 | LIKE-Platzhalter maskiert (`ESCAPE '!'`) | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-60 | B05 | Seite jenseits des Endes → 404 | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-61 | B07 | Folgetagsschleife läuft bis 7 statt 6 | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-62 | B08 | Längenprüfung am Endpunkt UND Slug-Kürzung im Repository (`ß` → 160 Zeichen) | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-63 | B08 | `DELETE /api/cuisines/{id}` für ungenutzte Typen, 409 mit `usedBy` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-65 | B13 | Brevo, Verkéiersverbond, Betroffenenrechte, Speicherdauer, neuer Einleitungssatz | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-66 | B13 | Abschnitt „Wie die Punktzahl entsteht" mit allen acht Merkmalen | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-67 | B16 | Häuser ohne Erhebung bekommen `null` statt 0 und fallen aus dem Durchschnitt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-68 | B24 | `_locale` aus dem Query gefiltert, Sprache zuletzt gemergt | hoch | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-69 | B24 | Elf Schlüssel ergänzt, drei Templates auf vorhandene umgestellt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-70 | B24 | `lang="de"` und Herkunftshinweis auf den Freitexten | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-71 | B24 | `keydown.esc` schließt den Umschalter und gibt den Fokus zurück | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-72 | B24 | Umschalter auch auf Mobil sichtbar | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
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
| BF-64 | B12 | Startseite zeigte ein Restaurant statt sechs — `findTopRated()` nutzt jetzt `Paginator` mit `$fetchJoinCollection`. Der vorhandene Test prüfte `assertLessThanOrEqual` und war grün; er prüft jetzt `assertCount` | mittel | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-46 | B10 | Erfundene Barrierefreiheitsaussage — Texte sagen jetzt, was tatsächlich geprüft wurde, samt Radius und Herkunftshinweis; Radius 500 → 1000, damit 8 statt 3 von 11 Restaurants Haltestellen zeigen | mittel | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-44 | B10 | Kein Timeout — `'timeout' => 3`, `'max_duration' => 5`; Rückkehr nach 0,3 s statt >30 s | mittel | 2026-08-24 | **noch nicht** |
| BF-45 | B10 | API-Schlüssel im Log — Service protokolliert Klasse und Code statt der URL; `SecretMaskingProcessor` deckt Symfonys `http_client`-Kanal ab. 22 Zeilen mit `accessId=`, **0 im Klartext** | mittel | 2026-08-24 | **noch nicht** |
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

- **Fehlende Eingabeprüfung endet im 500er (BF-27 behoben, BF-51, BF-62, BF-68 offen).**
  Viermal dasselbe: Ein Wert, den niemand geprüft hat, fällt in die nächste Schicht und
  kommt dort als Serverfehler heraus — zu lange Küchen-Angabe über die API (BF-27), leeres
  Pflichtfeld (BF-51), zu langer Küchenname (BF-62), Sprachcode aus dem Query (BF-68).
  Der vierte Fall ist der schwerste, weil er **ohne Anmeldung und ohne POST** auslösbar ist
  und jede öffentliche Seite trifft. Gemeinsam ist allen, dass die Prüfung an der Stelle
  fehlt, an der der Wert **hereinkommt** — nicht dort, wo er verbraucht wird.
- **Eine Rekonstruktion kann auch richtig sein und trotzdem in die Irre führen (BF-68).**
  B24/AK-12 beschreibt die Merge-Reihenfolge im Sprachumschalter **korrekt** — und nennt
  sie „harmlos, aber leicht zu beheben". Gemessen ist sie ein Open Redirect plus ein
  auslösbarer 500er auf zehn Seiten. Das Verhalten war richtig erfasst, der Schaden nicht.
  Ergänzung zu BF-16: Eine `spec.md` eines Bestandsfeatures ist nicht nur dort angreifbar,
  wo sie das Verhalten falsch beschreibt, sondern auch dort, wo sie es bewertet.
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
