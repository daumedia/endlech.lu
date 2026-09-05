# 08 · Warteliste für die mobile App — Aufgabenplan

Status: `approved` · Stand: 2026-09-05 · Stack-Profil: `symfony-doctrine`

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation. **Rot heißt anhalten** — nicht „das räumen wir
in Ebene 5 auf".

---

## Ebene 1 · Fundament — Daten und Konfiguration

- [x] **T01** `[P]` · Enum `App\Enum\AppPlatform` mit den Cases `IOS` und `ANDROID`,
      dazu `transKey()`, `label()`, `emoji()`, `badgeClasses()` und **`hasBeta()`**.
      Stil wie `TriState` und `OrganisationType`. ⚠ `hasBeta()` gehört hierher und nicht
      ins Template — die Frage wird an vier Stellen gestellt (Segment-Hinweis, zweite
      Mail, deren Betreff, Verwaltungsliste) — *Grundlage für T05, T07, T09*
- [x] **T02** `[P]` · `App\Enum\MarketingOrigin` um den Case `APP` erweitern
      (`transKey()`, `label()`, `brevoValue()`). ⚠ Ohne ihn fällt die neue Quelle in den
      `ACCOUNT`-Zweig von `originOf()` — der Bestand sagt genau das an Ort und Stelle
      voraus — *Grundlage für T08*
- [x] **T03** `[P]` · Limiter `app_waitlist` in `config/packages/framework.yaml`:
      `sliding_window`, 10 je Stunde, **plus `when@test`-Override auf 10000**. ⚠ Eigener
      Zähler, nicht `partner_waitlist` mitbenutzen (BF-38). `LimiterCoverageTest` wird
      rot, solange T15 den Limiter nicht verdrahtet — das ist erwartet und richtig
      — `AK-44, AK-46`
- [x] **T04** `[P]` · Parameter `app.testflight_url` in `config/services.yaml` aus
      `%env(APP_TESTFLIGHT_URL)%`, **leerer Vorgabewert in `.env`** (Muster
      `MOBILITEIT_API_KEY`/`SENTRY_DSN`: leer = lautlos aus). Der echte Wert
      `https://testflight.apple.com/join/Whxmtrsf` gehört in `.env.local` und auf den
      Server, **nicht** ins Repository — `AK-53`
- [x] **T05** · Entity `App\Entity\AppWaitlistEntry` nach der Feldtabelle in `design.md`,
      implementiert `WaitlistEntryInterface`; dazu `AppWaitlistEntryRepository` mit
      `findOneByConfirmationToken()`, `findFiltered()`, `countConfirmedByPlatform()` und
      `deleteStaleUnconfirmed()`. ⚠ `getDisplayName()` liefert das Plattform-Label,
      `getContactName()` einen **leeren String** — es gibt keinen Ansprechpartner, und
      ein Namensfeld nur zur Vertragserfüllung wäre erhobene Daten ohne Zweck.
      ⚠ `setEmail()` normalisiert auf `mb_strtolower(trim(...))`, sonst findet die
      Löschkaskade den Eintrag nicht und der Unique-Index greift nicht
      — `AK-41, AK-42`
- [x] **T06** · Migration `migrations/Version2026…php`: Tabelle `app_waitlist_entry`,
      Unique-Index auf `confirmation_token` **und auf `email`**, Kombi-Index
      `(status, created_at)`. ⚠ **Von Hand schreiben, nicht `migrations:diff`** — der
      Diff schlägt in diesem Projekt regelmäßig Index-Umbenennungen aus Altlasten vor.
      ⚠ MariaDB-tauglich bleiben: kein `CHECK` mit JSON-Funktionen, kein natives `ENUM`
      — `AK-15`, *Grundlage für T05*

**Verifikation Ebene 1**

```bash
php -l src/Enum/AppPlatform.php src/Entity/AppWaitlistEntry.php   # php-cs-fixer ist hier nicht installiert
php bin/console lint:container
php bin/console doctrine:schema:validate
php bin/console debug:config framework rate_limiter | grep -A4 app_waitlist
```

---

## Ebene 2 · Server — Logik und Validierung

- [x] **T07** `[P]` · `App\Form\AppWaitlistType`: `email` (`EmailType`, `empty_data: ''`,
      `NotBlank` + `Email` + `Length(max: 180)`), `platform` (`ChoiceType`,
      `expanded: true`, `multiple: false`, `placeholder: false`, **kein `data`**,
      `NotNull`), `consent` (ungemappt, Pflicht, `IsTrue`), `marketingConsent`
      (ungemappt, `required: false`, **ohne** `IsTrue`, ohne Vorbelegung — Art. 7 Abs. 4)
      und das Honeypot-Feld (`TextType`, ungemappt, **ohne** `Blank`-Constraint).
      ⚠ `empty_data: ''` ist Pflicht, sonst liefert ein leeres Feld einen 500er statt
      der `NotBlank`-Meldung — `AK-03, AK-04, AK-07, AK-08, AK-14, AK-52`
- [x] **T08** `[P]` · `MarketingContactRegistry`: dritter Zweig in `originOf()` (→ `APP`)
      und die neue Entity in die Klassenliste von `sourcesFor()`. ⚠ **Ohne die zweite
      Ergänzung ist AK-32 wirkungslos** — `scheduleRemoval()` fragt über `aktiveQuellen()`
      ab, ob eine andere Quelle den Kontakt hält, und findet eine unbekannte Quelle nicht
      (BF-84). ⚠ Plattform, `source` und Token gehen **nicht** mit nach Brevo
      — `AK-32, AK-54`
- [x] **T09** `[P]` · `WaitlistConfirmationService`: neue Methode für die **zweite Mail an
      den Interessenten** (Vorlage, Betreffschlüssel, Abmelderoute; setzt
      `betaLinkSentAt`). Nutzt `->locale($entry->getLocale())` (BF-10) und schluckt die
      Transport-Ausnahme wie `notifyTeam()` — eine gescheiterte Mail darf die
      Bestätigung nicht kaputt machen. ⚠ Im Dienst, nicht im Controller: Sprache,
      Abmeldelink und Ausnahmebehandlung stehen dort bereits und liefen sonst in einer
      zweiten Fassung auseinander — `AK-21, AK-30`
- [x] **T10** `[P]` · `App\Waitlist\StaleAppWaitlistCleaner` mit `sweep()` und
      `sweepOncePerDay()` (Cache-Schlüssel, ein Lauf je Kalendertag), Muster
      `StaleIdeaCleaner`. ⚠ Gelöscht wird nach **`selfConfirmedAt IS NULL`** und Alter
      > 30 Tage — **nicht** nach `status = pending`: Ein vom Admin weitergesetzter
      Eintrag steht nicht mehr auf `pending` und entginge dem Lauf, obwohl nie jemand
      bestätigt hat (dieselbe Zweideutigkeit wie BF-89) — `AK-47, AK-48`
- [x] **T11** `[P]` · `OpenStatsService::computePlatform()` um `appWaitlistTotal`,
      `appWaitlistIos`, `appWaitlistAndroid` erweitern, gezählt werden **nur selbst
      bestätigte** Einträge. Schwelle 50 als Konstante am Dienst. ⚠ Unterhalb der
      Schwelle fehlen die drei Schlüssel **im Array**, sie werden nicht im Template
      verborgen — sonst wären sie über `/open.json` abrufbar — `AK-37, AK-39`
- [x] **T12** `[P]` · `AccountDeleter::delete()` löscht den App-Wartelisten-Eintrag mit
      gleicher Adresse mit, **vor** `remove($user)` und vor `scheduleRemoval()`;
      `AccountDataExporter::export()` nimmt ihn in den Export auf.
      ⚠ **Bewusste Abweichung vom Bestandsverhalten:** Für Partner- und
      Organisationseinträge bleibt der Eintrag beim Kontolöschen ausdrücklich stehen
      (siehe Kommentar zu BF-84 in `AccountDeleter`). Hier wurde anders entschieden;
      OF-08 stellt die Frage, ob das so bleiben soll — `AK-50, AK-51`
- [x] **T13** · Konsolenbefehl `app:app-waitlist:cleanup` (`src/Command/`), ruft
      `StaleAppWaitlistCleaner::sweep()`, meldet die Zahl. `LockableTrait` mit
      `release()` im `finally` — ein belegtes Schloss liefert `SUCCESS`, nicht `FAILURE`
      (sonst füllt `RunCommandMessage` den `failed`-Transport mit Rauschen)
      — `AK-49`, *braucht T10*
- [x] **T14** · Zweiter Eintrag im `MarketingScheduleProvider`: `RecurringMessage::cron`
      täglich mit Zeitzone `Europe/Luxembourg`. ⚠ **Kein dritter Zeitplan** — der
      bräuchte einen dritten Transport im Consumer-Befehl, und der steht an drei Stellen
      (Dockerfile `worker`-Stage, `CLAUDE.md`, Coolify), von denen eine niemand
      automatisch nachzieht. ⚠ Die Zeitzone gehört auch bei einem Tageslauf hin, sonst
      zieht der Ausdruck UTC aus dem Container — `AK-49`, *braucht T13*

**Verifikation Ebene 2**

```bash
php bin/console lint:container
php bin/console debug:scheduler                      # zeigt beide Einträge des Zeitplans „marketing"
php bin/phpunit --testsuite Unit
php bin/phpunit tests/Integration/
```

---

## Ebene 3 · Schnittstellen

- [x] **T15** · `AppWaitlistController::index()` und `submit()` unter `#[Route('/app')]`.
      Reihenfolge im `submit()`: `handleRequest` → Limiter **abfragen**
      (`ActionLimiter::isAllowed()`, ⚠ nicht `consume(0)` — das ist keine Prüfung, BF-11)
      → Honeypot → Formularprüfung → **Dublettenzweig** → `consume()` → anlegen →
      `register()`. Der Dublettenzweig: bestehende Adresse mit `hasSelfConfirmed()` →
      identische Erfolgsantwort, nichts angefasst; bestehende Adresse `pending` mit
      abgelaufenem Token → neuer Token und erneuter Versand, **kein** zweiter Eintrag.
      ⚠ Erfolgsfall setzt `setRequestFormat(TURBO_STREAM)`, der Fehlerfall **darf das
      nicht** — die Antwort muss `text/html` bleiben, sonst rendert Turbo die 422 nicht
      — `AK-01, AK-09, AK-10, AK-11, AK-12, AK-13, AK-15, AK-16, AK-17, AK-18, AK-20, AK-44, AK-45`
- [x] **T16** · `AppWaitlistController::confirm()` und `revoke()`.
      `confirm()`: Token suchen → `confirm()` des Dienstes → bei `RESULT_CONFIRMED` die
      **zweite Mail** aus T09 anstoßen. Antwortcodes: `RESULT_INVALID` → **404**,
      `RESULT_EXPIRED` → **410** (nicht 404 — „gab es, ist weg" statt „gab es nie").
      `revoke()` → `revoke()` des Dienstes, `RESULT_INVALID` → 404.
      ⚠ **Keine interne Meldung ans Team** — anders als B14/B15: Hier gibt es nichts zu
      tun, der Zugang läuft über den Link. Eine Mail je Vormerkung entwertete die beiden
      Meldungen, die tatsächlich eine Handlung verlangen
      — `AK-21, AK-25, AK-26, AK-27, AK-28, AK-29, AK-31, AK-33, AK-34`
- [x] **T17** `[P]` · Locale-freie Weiterleitung `/app` in `config/routes.yaml` über
      `RedirectController`, **`permanent: false`**. ⚠ Ein 301 bliebe in fremden Browsern
      stehen — das war der teure Teil von BF-100, nicht die Schleife selbst — `AK-02`
- [x] **T18** `[P]` · `AdminWaitlistController`: `appRow()` als dritte Zeilenart, dritter
      Wert für den Quellen-Filter, `showApp()` und `changeAppStatus()` analog zu den
      bestehenden Methoden. Dazu der Aufruf von `sweepOncePerDay()` in `index()`.
      ⚠ Die Zeile führt die Adresse **nur zum Nachschlagen des Sync-Zustands** mit und
      zeigt sie nicht an — so hält es der Bestand — `AK-35, AK-36, AK-49`

**Verifikation Ebene 3**

```bash
php bin/console debug:router | grep -E 'app_app_waitlist|admin_waitlist'
php bin/console lint:container
php bin/phpunit tests/Functional/
```

---

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt.

- [x] **T19** · `templates/partials/_platform_field.html.twig` — Segmented Control mit
      zwei Segmenten, Hinweistext je Segment, serverseitig gerendert.
      ⚠ **`peer sr-only`, nicht `hidden`** — ein `hidden`-Input ist für Tastatur und
      Screenreader nicht vorhanden; der Fokus wird über `peer-focus-visible` am
      sichtbaren Segment dargestellt. ⚠ **Eigenes Partial, nicht `_tristate_field`
      verallgemeinern** — das ist an drei feste Fälle gebunden und trägt zwölf
      Pflichtfragen in B11 — `AK-04, AK-05, AK-06, AK-56`, *Grundlage für T20*
- [x] **T20** `[P]` · Seite `templates/app_waitlist/index.html.twig`: Hero-Band, zwei
      Statuskarten (iOS „Beta läuft" / Android „noch nichts gebaut"), Formular mit der
      DOM-id `app-waitlist-form`, alle vier Zustände. Dazu
      `app_waitlist/success.stream.html.twig` (Turbo-Ziel) und die Einbindung von
      `_waitlist_success.html.twig`. Aufbau wie die übrigen Außenseiten: Verlauf
      `from-cyan-700 to-purple-800`, `motion-safe:transition`, `focus:outline-2`,
      `min-h-[48px]` auf allen Aktionen — `AK-01, AK-10, AK-12`
- [x] **T21** `[P]` · `templates/app_waitlist/confirmation.html.twig` — bindet
      `_waitlist_confirmation.html.twig` ein und ergänzt zwei app-eigene Fälle: beim
      abgelaufenen Link der **Rückverweis auf `/{locale}/app`** (sonst ist EC-07 eine
      Sackgasse), und auf der Abmelderoute wird `invalid` als „bereits ausgetragen"
      dargestellt, nicht als Fehler. ⚠ Die geteilte Vorlage selbst **nicht** ändern —
      sie trägt B14 und B15 — `AK-26, AK-28, AK-33`
- [x] **T22** `[P]` · Zwei Mailvorlagen unter `templates/email/app/`, beide erweitern
      `email/base.html.twig`: (1) Bestätigungsmail — Bestätigungslink, Abmeldelink,
      **kein TestFlight-Link**; (2) Mail nach der Bestätigung — drei Zweige über
      `platform.hasBeta()` und „Parameter leer": Beta-Knopf / Android-Hinweis /
      ohne Beta-Abschnitt. Der Abmeldelink steht in **beiden**
      — `AK-19, AK-22, AK-23, AK-24, AK-30`
- [x] **T23** `[P]` · Fußzeilen-Eintrag in `templates/base.html.twig`, **Spalte 4** unter
      Roadmap/Changelog; dazu ein Hinweisband in `templates/home/index.html.twig`
      zwischen „Warum Endlech.lu?" und dem Handlungsaufruf. ⚠ Nicht Spalte 2 (trägt
      bereits elf Einträge) und keine fünfte Spalte (bräche `lg:grid-cols-4` und
      vergrößerte die offene Umbruchlücke BF-80). ⚠ `docs/app-shell.md` mitziehen — der
      Abschnitt ist schon zweimal auseinandergelaufen — `AK-40`
- [x] **T24** `[P]` · Kennzahl-Kacheln auf `templates/open/index.html.twig` über
      `open/_metric.html.twig`, sichtbar nur wenn die Schlüssel im Array stehen
      (T11 entscheidet das, nicht das Template) — `AK-38`
- [x] **T25** `[P]` · Verwaltungs-Templates: dritte Zeilenart und Quellen-Filter in
      `templates/admin/waitlist/index.html.twig`, Detailseite
      `templates/admin/waitlist/show_app.html.twig`. Plattform als Abzeichen über
      `AppPlatform::badgeClasses()` — `AK-35`

**Verifikation Ebene 4**

```bash
php bin/console lint:twig templates/
npm run build          # ⚠ auch bei reinen Twig-Änderungen: Tailwind scannt templates/ als @source
php bin/phpunit tests/Functional/
```

---

## Ebene 5 · Feinschliff

- [x] **T26** · Alle neuen Schlüssel in `messages.{de,en,fr,lb}.yaml` und
      `validators.{de,en,fr,lb}.yaml` — Seite, Feldbeschriftungen, Hilfetexte, beide
      Mailbetreffe und -texte, Bestätigungs- und Abmeldeseite, Flash-Meldungen,
      `AppPlatform`- und `MarketingOrigin::APP`-Labels. ⚠ `CatalogueCompletenessTest`
      erzwingt **identische Schlüsselmengen** in allen vier Katalogen und prüft, dass
      jeder im Code verwendete Schlüssel definiert ist — `AK-55`
- [x] **T27** · Barrierefreiheit durchgehen: Tab-Reihenfolge folgt der sichtbaren
      Anordnung, Fokus überall sichtbar (`outline`, kein `ring`), Fehlermeldungen über
      `aria-describedby` verbunden, `autofocus` serverseitig auf dem ersten Fehler.
      ⚠ In `attr` unterdrückt nur `false` ein Attribut, **nicht `null`** —
      `'aria-invalid': null` rendert `aria-invalid=""`, und Screenreader lesen das als
      „ungültig" — `AK-56, AK-57`
- [x] **T28** · Randfälle nachstellen: Adresse > 180 Zeichen, Emoji, führende
      Leerzeichen (EC-05); zwei gleichzeitige Absendevorgänge derselben Adresse (EC-06,
      der Unique-Index muss greifen); Token zwischen Tag 7 und Tag 30 (EC-07); Antwort
      formatkorrekt im Erfolgs- und Fehlerfall (EC-01); GET verbraucht kein Kontingent
      (EC-02) — `EC-01, EC-02, EC-05, EC-06, EC-07`
- [x] **T29** · Nachweisen, dass weder E-Mail-Adresse noch Token in den Protokollen
      landen — im Erfolgs- **und** im Fehlerfall. Gegenprobe mit einem erzwungenen
      Versandfehler. `SecretMaskingProcessor` deckt den `http_client`-Kanal ab, dieser
      Weg läuft nicht darüber — `AK-43`
- [x] **T30** · Gegenprobe zu BF-100: Es darf **kein Verzeichnis `public/app`** geben.
      `RouteDirectoryCollisionTest` prüft das projektweit — nachsehen, dass er die neue
      Route tatsächlich erfasst, statt es anzunehmen — `AK-58`
- [x] **T31** · `docs/data-model.md` (Entity, Enum, Repository, Migrations-Historie),
      `docs/app-shell.md` (Fußzeile) und `CLAUDE.md` mitziehen; `CHANGELOG.md` unter
      `[Unreleased]` ergänzen — *Grundlage für den Release; ohne AK, siehe unten*

**Verifikation Ebene 5**

```bash
php bin/console debug:translation de --only-missing
php bin/console debug:translation lb --only-missing
php bin/console lint:twig templates/
npm run build
php bin/phpunit                 # vollständig, inkl. LimiterCoverageTest und CatalogueCompletenessTest
```

---

## Abdeckung

| AK | Aufgaben | AK | Aufgaben |
|---|---|---|---|
| AK-01 | T15, T20 | AK-30 | T09, T22 |
| AK-02 | T17 | AK-31 | T16 |
| AK-03 | T07 | AK-32 | T08 |
| AK-04 | T07, T19 | AK-33 | T16, T21 |
| AK-05 | T19 | AK-34 | T16 |
| AK-06 | T19 | AK-35 | T18, T25 |
| AK-07 | T07 | AK-36 | T18 |
| AK-08 | T07 | AK-37 | T11 |
| AK-09 | T15 | AK-38 | T11, T24 |
| AK-10 | T15, T20 | AK-39 | T11 |
| AK-11 | T15 | AK-40 | T23 |
| AK-12 | T15, T20 | AK-41 | T05 |
| AK-13 | T15 | AK-42 | T01, T05 |
| AK-14 | T07 | AK-43 | T29 |
| AK-15 | T06, T15 | AK-44 | T03, T15 |
| AK-16 | T15 | AK-45 | T15 |
| AK-17 | T15 | AK-46 | T03 |
| AK-18 | T15 | AK-47 | T10 |
| AK-19 | T22 | AK-48 | T10 |
| AK-20 | T15 | AK-49 | T13, T14, T18 |
| AK-21 | T09, T16 | AK-50 | T12 |
| AK-22 | T22 | AK-51 | T12 |
| AK-23 | T22 | AK-52 | T07 |
| AK-24 | T22 | AK-53 | T04 |
| AK-25 | T16 | AK-54 | T08 |
| AK-26 | T16, T21 | AK-55 | T26 |
| AK-27 | T16 | AK-56 | T19, T27 |
| AK-28 | T16, T21 | AK-57 | T27 |
| AK-29 | T16 | AK-58 | T30 |

**AK ohne Aufgabe:** keine. Alle 58 Kriterien aus `spec.md` sind zugeordnet.

**Aufgabe ohne AK:** vier, alle zulässig —
`T01` (Enum, Grundlage für T05/T07/T09), `T02` (Enum-Case, Grundlage für T08),
`T06` (Migration, Grundlage für T05 — trägt zusätzlich AK-15 über den Unique-Index),
`T31` (Dokumentation und Changelog, Vorbedingung des Releases).

**Randfälle:** EC-01, EC-02, EC-05, EC-06 und EC-07 liegen bei T28; EC-03 (Test-Override)
bei T03; EC-04 (Token bleibt stehen) folgt aus dem Bestandsverhalten des Dienstes und
wird bei T16 mitgeprüft; EC-08 (`trusted_hosts`) und EC-09 (Worker-Ausfall) sind
**Betriebszusagen, keine Aufgaben dieses Features** — sie stehen in der Spec, damit bei
einem Fehlschlag in Produktion die richtige Stelle gesucht wird.

---

## Parallelisierung

| Ebene | Gleichzeitig | Berührte Dateien — nachweislich überschneidungsfrei |
|---|---|---|
| 1 | T01, T02, T03, T04 | `src/Enum/AppPlatform.php` · `src/Enum/MarketingOrigin.php` · `config/packages/framework.yaml` · `config/services.yaml` + `.env` |
| 1 | danach seriell: T05 → T06 | T05 braucht den Enum aus T01; T06 wird aus der Entity von T05 abgeleitet |
| 2 | T07, T08, T09, T10, T11, T12 | `src/Form/AppWaitlistType.php` · `src/Marketing/MarketingContactRegistry.php` · `src/Waitlist/WaitlistConfirmationService.php` · `src/Waitlist/StaleAppWaitlistCleaner.php` · `src/Open/OpenStatsService.php` · `src/Account/{AccountDeleter,AccountDataExporter}.php` |
| 2 | danach seriell: T13 → T14 | T13 ruft den Dienst aus T10; T14 trägt den Befehl aus T13 ein |
| 3 | T17, T18 | `config/routes.yaml` · `src/Controller/AdminWaitlistController.php` |
| 3 | seriell: T15 → T16 | **dieselbe Datei** `src/Controller/AppWaitlistController.php` — ausdrücklich **kein** `[P]` |
| 4 | T19 zuerst, danach T20–T25 | T20 bindet das Partial aus T19 ein. Danach: `app_waitlist/index+success` · `app_waitlist/confirmation` · `email/app/*` · `base.html.twig`+`home/index.html.twig` · `open/index.html.twig` · `admin/waitlist/*` |
| 5 | keine | T26 fasst alle acht Katalogdateien an, T27–T30 prüfen quer über den Bestand — parallel liefe hier nur Arbeit doppelt |

⚠ **T12 und T08 sehen verwandt aus und sind es nicht.** T08 ändert das Marketing-Register
(`sourcesFor`, `originOf`), T12 die beiden Account-Klassen. Keine gemeinsame Datei — aber
T12 setzt fachlich auf T08 auf, weil `scheduleRemoval()` die neue Quelle sonst nicht
findet. Beide dürfen gleichzeitig laufen; der **Test** zu T12 wird erst grün, wenn T08
durch ist. Bei einem roten Lauf zuerst dort nachsehen.

---

## Vor dem Bauen

- [x] Feature-Branch: `git checkout -b feature/08-app-warteliste`
- [x] `APP_TESTFLIGHT_URL=https://testflight.apple.com/join/Whxmtrsf` in `.env.local`
      hinterlegen — **nicht** in `.env` committen
- [x] Docker läuft, Test-DB steht: `make start` und einmalig `make test-db-setup`
- [~] Klären, ob OF-05 (Feldzählung in AK-03) und OF-08 (Abweichung beim Kontolöschen)
      vor dem Bau entschieden werden sollen — beide betreffen T07 bzw. T12
- [x] `php bin/phpunit` läuft vor der ersten Änderung grün — 926 Tests, belegt — sonst ist später nicht
      unterscheidbar, was dieses Feature kaputtgemacht hat

⚠ **`make fix` steht im Stack-Profil, ist hier aber nicht benutzbar** —
`vendor/bin/php-cs-fixer` fehlt in dieser Installation. Ersatz ist `php -l` je geänderter
Datei; der Stil folgt dem Nachbarcode.
