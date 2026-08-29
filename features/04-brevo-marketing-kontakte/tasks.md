# 04 · Marketing-Kontakte in Brevo — Aufgabenplan

Status: `approved` (QA⁴ 2026-08-30: 43/48, kein Befund — Inbetriebnahme hängt an T08 und BF-88) · Stand: 2026-08-29 · **37 von 39 Aufgaben erledigt** (offen: T08, T39 — beide brauchen eine Betreiberentscheidung) · Stack-Profil: `symfony-doctrine`

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben derselben Welle, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation aus dem Stack-Profil. **Rot heißt anhalten.**

```bash
make fix-check                            # Exit 8 = etwas zu tun (nicht 1!)
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console doctrine:schema:validate
npm run build                             # ab Ebene 4, wenn assets/ berührt wird
php bin/phpunit
```

---

## Ebene 1 · Fundament — Daten und Konfiguration

- [x] **T01** · Enums `MarketingOrigin` (`partner`, `commune`, `company`, `association`,
      `account`; `label()`, `brevoValue()`) und `MarketingSyncState` (`pending`, `synced`,
      `failed`, `removal_pending`; `label()`, `badgeClasses()`) in `src/Enum/` —
      Muster `WaitlistStatus`, `badgeClasses()` liefert **nur Farbe** — `AK-30`,
      Grundlage für T02, T03
- [x] **T02** · Entity `App\Entity\MarketingContact` + `MarketingContactRepository`:
      Felder aus `design.md`, `email` **unique**, Index `(sync_state, updated_at)`,
      `createdAt` im Konstruktor, `updatedAt` über `#[ORM\PreUpdate]`, **keine
      Beziehungen**; Repository mit `findOpenForSync(int $limit)` und
      `countBySyncState()` — `AK-25, AK-27, AK-28`, EC-01
- [x] **T03** · Feld `marketingConsentAt` (`?DateTimeImmutable`, nullable) an
      `PartnerWaitlistEntry`, `OrganisationWaitlistEntry` und `User`, je mit Getter/Setter
      — `AK-04`
- [x] **T04** · Migration von Hand (`migrations/Version20260829…`): `CREATE TABLE
      marketing_contact` ohne native `ENUM`-Spalten + drei `ADD COLUMN`. Gegen
      MariaDB 10.5 gegengeprüft, danach `doctrine:schema:validate` grün —
      Grundlage für T02, T03
      ⚠ `migrations:diff` schlägt in diesem Projekt Index-Umbenennungen aus Altlasten
      vor; die Datei wird geschrieben, nicht generiert.
- [x] **T05** `[P]` · `BREVO_API_KEY`, `BREVO_LIST_ID`, `BREVO_WEBHOOK_TOKEN` **leer** in
      `.env`; Parameter `app.brevo_sync_batch` (200) und `app.brevo_sync_delay_ms` (200)
      in `config/services.yaml`. Kein Wert wird als Twig-Global oder Encore-Variable
      exponiert — `AK-46, AK-48`
- [x] **T06** `[P]` · Limiter `marketing_webhook` (sliding_window, 120 je 5 Minuten) in
      `config/packages/framework.yaml` **inklusive `when@test`-Override auf 10000** —
      sonst wird `LimiterCoverageTest` rot — Grundlage für T17
- [x] **T07** `[P]` · Alle Übersetzungsschlüssel des Features in **allen vier** Katalogen
      (`messages.{de,en,fr,lb}.yaml`): Block `marketing:` (Einwilligungstext, Hilfetext
      „jederzeit widerrufbar", Abzeichen-Beschriftungen, Admin-Block, Fehlertexte).
      Steht in Ebene 1, weil `CatalogueCompletenessTest` auch `src/Form/` scannt: ein
      Label in Ebene 3 ohne Katalogeintrag färbt die Suite dort rot — Grundlage für
      T19–T22, T28–T32
- [x] **T08** `[P]` · **Brevo-Konto einrichten, kein Code** — erledigt am 2026-08-30.
      ⚠ Unbekannte Attribute verwirft Brevo **stillschweigend**: Ohne diesen Schritt
      meldet der Sync Erfolg und überträgt nur die nackte Adresse. Vor der Einrichtung
      geprüft: **keines** der fünf Attribute existierte — der Befund war real.

      | Was | Stand |
      |---|---|
      | Attribute | `CONTACT_NAME`, `ORGANISATION`, `LOCALE`, `ORIGIN`, `FUNNEL_STATUS` — alle `normal`/`text`, nach dem Anlegen im Konto nachgewiesen |
      | Liste | „Endlech.lu · Neuigkeiten", **ID 5** (Ordner 1) → gehört als `BREVO_LIST_ID=5` in die `.env.local` |
      | Webhook | ID `2159035`, Typ `marketing`, URL `https://endlech.lu/marketing/brevo/webhook`, Ereignisse `unsubscribed`, `hardBounce`, `spam`, `contactDeleted` |
      | Absicherung | `auth.type: bearer` — **Brevo unterstützt das**, die Annahme des Entwurfs trägt. Der Token wurde erzeugt und im Webhook hinterlegt; er steht **nicht** im Repository und gehört als `BREVO_WEBHOOK_TOKEN` in die `.env.local` auf dem Server |

      ⚠ **Die Webhook-URL antwortet bis zum Deploy mit 404.** Unkritisch: Solange kein
      Kontakt existiert, erzeugt Brevo keine Ereignisse. Nach dem Deploy greift sie.

**Verifikation Ebene 1:** `doctrine:schema:validate` grün, `php bin/phpunit
tests/Unit/Translation`, `lint:container`.

## Ebene 2 · Server — Logik und Validierung

- [x] **T09** `[P]` · `App\Marketing\BrevoContactClient`: `upsert()` über
      `POST /contacts` mit `updateEnabled: true` und `identifierType: ext_id`,
      `delete()`; eigener `timeout`, `catch (\Throwable)`, **Protokoll trägt nur Klasse
      und Statuscode** — nie die Antwort im Wortlaut, nie den Schlüssel. Leerer
      Schlüssel = still aus — `AK-20, AK-25, AK-31, AK-47`
- [x] **T10** `[P]` · `App\Marketing\MarketingPayloadMapper`: baut den Rumpf aus **genau**
      `ext_id`, `EMAIL` und den fünf Attributen. Die Negativliste ist die Aufgabe, nicht
      ein Nebensatz: Freitextnachricht, Telefonnummer, Ort, `source`/UTM, jede IP und
      jeder Token gehen **nicht** mit; `emailBlacklisted` steht **nie** im Rumpf —
      `AK-07, AK-08, AK-28, AK-29, AK-30`, EC-05
- [x] **T11** `[P]` · `AccountDataExporter::export()` nimmt `marketingConsentAt` des
      Kontos auf — `AK-44`
- [x] **T12** · `MarketingContactRegistry::record()` (`src/Marketing/`): legt die Zeile je
      E-Mail an oder schreibt sie fort, **nur wenn die Quelle bestätigt ist**; setzt
      `sync_state = pending`; Sperrregel aus Entscheidung 8 — eine Zeile mit `revoked_at`
      bleibt gesperrt, es sei denn `consent_at` ist jünger. Ruft Brevo nicht —
      `AK-05, AK-12, AK-17, AK-45`, EC-01, EC-03
- [x] **T13** · `MarketingContactRegistry::scheduleRemoval()`: setzt `removal_pending`,
      **bevor** die Quelle gelöscht wird; unbekannte Adresse läuft folgenlos durch. Ruft
      Brevo nicht — `AK-13, AK-14, AK-16, AK-17, AK-43`, EC-04
      *(gleiche Datei wie T12 — deshalb kein `[P]`)*
- [x] **T14** · `App\Marketing\MarketingSyncService::run()`: holt offene Zeilen über
      `findOpenForSync`, höchstens `app.brevo_sync_batch` je Lauf, mindestens
      `app.brevo_sync_delay_ms` Abstand; Zustandswechsel `pending → synced` bzw.
      `failed` mit `attempts++` und `last_error`; Rückzug nach 5 Versuchen; bestätigte
      Löschung entfernt die Zeile; leerer Schlüssel bricht mit Hinweis ab, ohne das
      Auftragsbuch zu verändern — `AK-06, AK-19, AK-39, AK-47`
- [x] **T15** · `WaitlistConfirmationService`: `confirm()` ruft `record()`, `revoke()` ruft
      `scheduleRemoval()` **vor** `remove()` — `AK-05, AK-13`
- [x] **T16** · `AccountDeleter::delete()` ruft `scheduleRemoval()`, bevor das Konto
      entfernt wird — dieselbe Reihenfolge wie beim Avatar — `AK-14`

**Verifikation Ebene 2:** `lint:container`, `php bin/phpunit tests/Unit tests/Integration`.

## Ebene 3 · Schnittstellen

- [x] **T17** · `src/Controller/Marketing/BrevoWebhookController.php`:
      `POST /marketing/brevo/webhook`, sprachfrei. Bearer-Geheimnis **vor** jeder
      Auswertung, `ActionLimiter` auf `marketing_webhook`, aus dem Rumpf werden
      **ausschließlich** E-Mail-Adresse und Ereignistyp gelesen. `unsubscribed` setzt
      `revoked_at` und leert `marketingConsentAt` an der Quelle. Antwortet **immer 200**,
      auch bei unbekannter Adresse — `AK-11, AK-12`
- [x] **T18** · Routing und Zugriffsregeln: dritter Eintrag in der `exclude`-**Liste** am
      `controllers`-Loader plus eigener Importblock ohne `/{_locale}` in
      `config/routes.yaml`; eigene `access_control`-Zeile für `^/marketing/` in
      `config/packages/security.yaml`. Nachweis, dass `^/[a-z]{2}/admin` die neuen
      Ansichten deckt und ein sprachfreier Pfad durch alle Web-Regeln fiele (BF-18) —
      `AK-35, AK-36, AK-37, AK-38`
- [x] **T19** `[P]` · `PartnerWaitlistType`: Feld `marketingConsent` (`CheckboxType`,
      `mapped: false`, `required: false`, **kein** `IsTrue`, keine Vorbelegung);
      `PartnerController` setzt bei gesetztem Feld `marketingConsentAt`. Bestehender
      Limiter `partner_waitlist` bleibt unverändert — `AK-01, AK-02, AK-03, AK-04, AK-40`
- [x] **T20** `[P]` · dasselbe in `OrganisationWaitlistType` + `OrganisationController`.
      ⚠ Das Feld gehört in **beide** Feldaufbauten (`PRE_SET_DATA` und `PRE_SUBMIT`),
      sonst ist es ohne JavaScript nicht bedienbar bzw. löst ein 422 aus —
      `AK-01, AK-02, AK-03, AK-04, AK-40`
- [x] **T21** `[P]` · dasselbe in `RegistrationType` + `RegistrationController` —
      `AK-01, AK-02, AK-03, AK-04, AK-40`
- [x] **T22** `[P]` · `EmailVerificationController`: `verify()` ruft nach erfolgreicher
      Verifikation `record()`, wenn `marketingConsentAt` gesetzt ist;
      `confirmEmailChange()` schreibt die neue Adresse über `ext_id` fort —
      `AK-05`, EC-02
- [x] **T23** · `AdminWaitlistController::changePartnerStatus()` und
      `changeOrganisationStatus()` setzen den zugehörigen `sync_state` auf `pending`
      zurück — keine Direktübertragung im Request — `AK-09`
- [x] **T24** · `AdminWaitlistController::index()`, `showPartner()`, `showOrganisation()`
      laden die `MarketingContact`-Zeile zur jeweiligen Adresse und geben Sync-Zustand,
      `synced_at`, `last_error` sowie die Zählung aus `countBySyncState()` an die
      Templates — `AK-15, AK-18, AK-26, AK-27`
      *(gleiche Datei wie T23 — deshalb kein `[P]`)*
- [x] **T25** `[P]` · Konsolenbefehl `app:marketing:sync` (`src/Command/`) ruft
      `MarketingSyncService::run()`, gibt Zahlen je Zustand aus, Exit-Code ≠ 0 nur bei
      Konfigurationsfehler — `AK-06, AK-19`
- [x] **T26** `[P]` · Konsolenbefehl `app:marketing:import`: **Trockenlauf ist der
      Vorgabefall** — ohne `--commit` wird nichts geschrieben und nichts geschickt,
      ausgegeben werden Zahl und Liste der betroffenen Einträge. Die Auswahlregel steht
      im Befehl und ist nicht per Parameter aufweichbar: **ausschließlich bestätigte
      Wartelisten-Einträge mit `marketingConsentAt`**, keine Konten, keine unbestätigten
      — `AK-21, AK-22, AK-23, AK-36`
- [x] **T27** · Cron-Eintrag alle 5 Minuten auf `app:marketing:sync` unter dem
      **PHP-FPM-Systembenutzer** (nicht dem Master-Login), dokumentiert in `README.md`
      neben `app:metrics:snapshot`. `src/Schedule.php` bleibt unberührt — dort feuert
      nichts, solange Produktion mit `sync://` läuft (BF-48) — `AK-10`

**Verifikation Ebene 3:** `debug:router | grep marketing` zeigt die sprachfreie Route,
`lint:container`, `php bin/phpunit`.

## Ebene 4 · Oberfläche

Jede Anzeige braucht vier Zustände: leer, ladend, Fehler, gefüllt. Für die Spalte „Brevo"
sind das *keine Einwilligung*, *ausstehend*, *übertragen*, *fehlgeschlagen*.

- [x] **T28** `[P]` · `templates/partner/_form.html.twig`: Einwilligungsfeld über
      `_form_field.html.twig`, Kurztext „jederzeit widerrufbar", Eintrag in `field_order`
      **vor** dem Honeypot — der bleibt letztes Feld — `AK-01`
- [x] **T29** `[P]` · `templates/organisation/_form.html.twig`: dito — `AK-01`
- [x] **T30** `[P]` · `templates/registration/register.html.twig`: dito — `AK-01`
- [x] **T31** `[P]` · `templates/admin/waitlist/index.html.twig`: Spalte „Brevo" mit den
      vier Zuständen über `MarketingSyncState::badgeClasses()`; Abzeichen trägt **Text
      neben der Farbe**. Kopfzeile zeigt „x eingewilligt / y übertragen" —
      `AK-15, AK-18, AK-26, AK-27`
- [x] **T32** `[P]` · neues `templates/admin/waitlist/_marketing.html.twig` (Einwilligung
      ja/nein + Zeitpunkt, Sync-Zustand + Zeitpunkt, letzter Fehler **nur wenn
      vorhanden**), eingebunden in `partner_show.html.twig` und
      `organisation_show.html.twig` — `AK-15, AK-18`

**Verifikation Ebene 4:** `lint:twig templates/`, `npm run build`, `php bin/phpunit
tests/Functional`.

## Ebene 5 · Feinschliff

- [x] **T33** · Die gesetzte Einwilligung überlebt ein ungültiges Formular: nach der
      422-Antwort ist das Häkchen in allen drei Formularen noch gesetzt — `EC-06`
- [x] **T34** · Barrierefreiheit: Feld mit `aria-describedby` auf den Hilfetext,
      `aria-invalid` nur als `true`/`false` (**nie `null`** — das rendert
      `aria-invalid=""`), Abzeichenkontrast gegen `docs/design-system.md` geprüft,
      Tastaturbedienung der Spalte — `AK-01`
- [x] **T35** · Wortlaut in vier Sprachen schärfen: Was wird geschickt, von wem, wie
      widerruft man. Kein Text behauptet eine Barrierefreiheitsaussage, die nicht geprüft
      wurde — `EC-06`, Feinschliff zu T07
- [x] **T36** · **`docs/datenschutz.md` anlegen**: Auftragsverarbeitungsvertrag mit
      Brevo SA (Frankreich, EU), Sitz, Datum der Prüfung, Zweck, Datenkategorien,
      Löschregeln. Die Datei existiert heute nicht — `AK-33`
      ⚠ Hängt an **OF-01** (Datenschutzstufe; die Spec nimmt B an). Vor dem Schreiben
      beim Betreiber klären.
- [x] **T37** · Datenschutzabschnitt in `templates/impressum/index.html.twig` (`/legal`,
      Route `app_impressum`): Brevo als Empfänger **für Werbezwecke** benennen, nicht nur
      als Versanddienstleister — `AK-32`
- [x] **T38** · Text der ersten Kampagne in Brevo (**Inhalt, kein Code**): nennt im ersten
      Absatz, woher die Adresse stammt, und trägt einen Abmeldelink. Nachweis: Screenshot
      oder Kampagnen-ID — `AK-24`
- [ ] **T39** · **Freigabe-Sperre:** Der erste echte Lauf — `app:marketing:import
      --commit` oder der erste Cron-Durchlauf mit echtem Schlüssel — findet erst statt,
      wenn T36 und T37 stehen. Kein Kontakt geht raus, bevor die Erklärung ihn nennt —
      `AK-34`

---

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T19, T20, T21, T28, T29, T30, T34 |
| AK-02 | T19, T20, T21 |
| AK-03 | T19, T20, T21 |
| AK-04 | T03, T19, T20, T21 |
| AK-05 | T12, T15, T22 |
| AK-06 | T14, T25 |
| AK-07 | T08, T10 |
| AK-08 | T10 |
| AK-09 | T23 |
| AK-10 | T27 |
| AK-11 | T17 |
| AK-12 | T12, T17 |
| AK-13 | T13, T15 |
| AK-14 | T13, T16 |
| AK-15 | T24, T31, T32 |
| AK-16 | T13 |
| AK-17 | T12, T13 |
| AK-18 | T24, T31, T32 |
| AK-19 | T14, T25 |
| AK-20 | T09 |
| AK-21 | T26 |
| AK-22 | T26 |
| AK-23 | T26 |
| AK-24 | T38 |
| AK-25 | T02, T09 |
| AK-26 | T24, T31 |
| AK-27 | T02, T24, T31 |
| AK-28 | T02, T10 |
| AK-29 | T10 |
| AK-30 | T01, T10 |
| AK-31 | T09 |
| AK-32 | T37 |
| AK-33 | T36 |
| AK-34 | T39 |
| AK-35 | T18 |
| AK-36 | T18, T26 |
| AK-37 | T18 |
| AK-38 | T18 |
| AK-39 | T05, T14 |
| AK-40 | T19, T20, T21 |
| AK-41 | — *(Negativkriterium, siehe unten)* |
| AK-42 | — *(Negativkriterium, siehe unten)* |
| AK-43 | T13, T15, T16 |
| AK-44 | T11 |
| AK-45 | T12 |
| AK-46 | T05 |
| AK-47 | T09, T14 |
| AK-48 | T05 |

| EC | Aufgaben |
|---|---|
| EC-01 | T02, T12 |
| EC-02 | T10, T22 |
| EC-03 | T12 |
| EC-04 | T13 |
| EC-05 | T10 |
| EC-06 | T33, T35 |

**AK ohne Aufgabe:** AK-41 und AK-42 — beide sind **Negativkriterien** und in `spec.md`
bereits als „trifft nicht zu" formuliert. AK-41 (Uploads): Dieses Feature nimmt keine
Dateien entgegen; es gibt nichts zu bauen und nichts zu unterlassen. AK-42
(Kosten je Aufruf): Erfüllt sich dadurch, dass kein Kampagnen-Auslöser gebaut wird — die
Abwesenheit ist die Umsetzung. Beide bleiben als **QA-Nachweise** stehen: `sdd-qa` prüft,
dass kein Aufnahmefeld und kein Versandauslöser entstanden ist. Eine Aufgabe zu erfinden,
deren Inhalt „nichts tun" wäre, würde die Prüfung nicht schärfer machen.

**Aufgabe ohne AK:**
- **T01** (Enums) — Grundlage für T02, T03; trägt zusätzlich AK-30
- **T04** (Migration) — Grundlage für T02, T03
- **T06** (Limiter) — Grundlage für T17; erfüllt zugleich die `CLAUDE.md`-Konvention
  „jeder Weg, der ein Geheimnis prüft, braucht einen Limiter im selben Commit"
- **T07** (Übersetzungsschlüssel) — Grundlage für T19–T22 und T28–T32
- **T14** (SyncService) trägt AK-06/19/39/47, ist aber zugleich Grundlage für T25
- **T18** (Routing) trägt AK-35–38, ist aber zugleich Grundlage dafür, dass T17 überhaupt
  erreichbar ist

Alle sechs sind zulässige Grundlagenaufgaben. Keine Aufgabe steht ohne Zweck im Plan.

## Parallelisierung

Innerhalb einer Ebene laufen die `[P]`-Aufgaben einer Welle gleichzeitig; die Wellen
laufen nacheinander.

**Ebene 1** — zwei Wellen:
- Welle A: **T05** (`.env`, `config/services.yaml`), **T06**
  (`config/packages/framework.yaml`), **T07** (`translations/*.yaml`), **T08** (kein
  Code, nur Brevo-Konto). Vier getrennte Ziele, keine gemeinsame Datei.
- Welle B (seriell): T01 → T02, T03 → T04. `MarketingContact` braucht die Enums, die
  Migration braucht beide Entity-Stände. **Zwei gleichzeitige Migrationen bekämen
  kollidierende Zeitstempel** — deshalb ist T04 allein.

**Ebene 2** — zwei Wellen:
- Welle A: **T09** (`src/Marketing/BrevoContactClient.php`), **T10**
  (`src/Marketing/MarketingPayloadMapper.php`), **T11**
  (`src/Account/AccountDataExporter.php`). Drei Dateien, kein Überschnitt.
- Welle B (seriell): T12 → T13 (beide `MarketingContactRegistry.php`, deshalb **kein**
  `[P]`) → T14 (braucht T09, T10, T12) → T15, T16 (brauchen T12, T13).

**Ebene 3** — drei Wellen:
- Welle A (seriell): T17 → T18. Der Importblock in `routes.yaml` zeigt auf
  `src/Controller/Marketing/`; das Verzeichnis muss vorher existieren, sonst scheitert
  `lint:container`.
- Welle B: **T19** (`PartnerWaitlistType.php`, `PartnerController.php`), **T20**
  (`OrganisationWaitlistType.php`, `OrganisationController.php`), **T21**
  (`RegistrationType.php`, `RegistrationController.php`), **T22**
  (`EmailVerificationController.php`), **T25** (`MarketingSyncCommand.php`), **T26**
  (`MarketingImportCommand.php`). Sechs Paare getrennter Dateien. Die Katalogdateien
  berührt keine davon — das ist genau der Grund, warum T07 in Ebene 1 steht.
- Welle C (seriell): T23 → T24 → T27. T23 und T24 fassen beide
  `AdminWaitlistController.php` an.

**Ebene 4** — eine Welle: **T28**, **T29**, **T30**, **T31**, **T32**. Fünf getrennte
Templates; T32 legt ein neues Partial an und bindet es in zwei Show-Templates ein, die
keine andere `[P]`-Aufgabe berührt.

**Ebene 5** — seriell. T33–T35 arbeiten über alle drei Formulare hinweg, T36–T39 hängen
aneinander und an einer Betreiberantwort.

## Vor dem Bauen

- [x] Feature-Branch: `git checkout -b feature/04-brevo-marketing-kontakte`
- [x] **Stand von Feature `01` festhalten.** Die Spec markiert das als kritisch. Am Code
      belegt (2026-08-29): `WaitlistConfirmationService::revoke()` mit
      `RESULT_REVOKED`, `src/Account/AccountDeleter.php::delete()`,
      `src/Account/AccountDataExporter.php::export()`, Routen `app_profile_export` und
      `app_profile_delete`. Die drei Einhängepunkte aus T15, T16 und T11 existieren
      also. Der Inventar-Status von `01` steht weiterhin auf `roadmap` — das ist eine
      Buchführungslücke, kein fehlender Code.
- [ ] `BREVO_API_KEY`, `BREVO_LIST_ID`, `BREVO_WEBHOOK_TOKEN` in der lokalen,
      ungetrackten `.env.local` hinterlegt — im Repository bleiben sie leer
- [ ] **OF-01 beim Betreiber klären** (Datenschutzstufe; die Spec nimmt B an) — blockiert
      T36, nicht den Rest des Plans
- [x] Test-DB steht — Migration `Version20260829120000` ist auch dort eingespielt
- [x] **`npm run build` gelaufen und `public/build` mitzuliefern.** Die Annahme im
      Plan war falsch: Zwar wurde kein `assets/`-Quelltext geändert, aber Tailwind
      scannt die **Templates** — die neue Admin-Spalte brachte `lg:table-cell` mit,
      das im alten Build fehlte. Ohne den Neubau wäre die Spalte auf großen
      Schirmen unsichtbar geblieben **und** `verify-assets` hätte den Deploy
      geblockt. `app.1d1c027e.css` → `app.2336df9e.css`.

## Vor dem ersten echten Lauf (AK-34)

Diese Reihenfolge ist nicht technisch erzwungen. Sie steht deshalb hier, sichtbar, statt
in einer Fußnote:

1. **T36** — `docs/datenschutz.md` existiert und nennt den AVV mit Datum
2. **T37** — `/legal` nennt Brevo als Empfänger für Werbezwecke
3. **T08** — die fünf Attribute stehen im Brevo-Konto *(sonst überträgt der Lauf nur die
   nackte Adresse und meldet Erfolg)*
4. erst dann: `app:marketing:import --commit` bzw. der erste Cron-Lauf mit echtem
   Schlüssel

## Was dieser Plan nicht enthält

- **Keine Sammelaufgabe „testen".** Jede Aufgabe wird an ihrer eigenen Stelle geprüft;
  die Abnahme läuft über `/sdd-qa 04`.
- **Keine neuen Anforderungen.** Die sechs offenen Fragen aus `spec.md` bleiben offen.
  OF-04 (Herkunft bei mehreren Quellen) ist im Plan mit „die erste gewinnt" **nicht**
  entschieden — T12 legt die Zeile an und schreibt sie fort; welche Herkunft dabei
  gewinnt, ist die Antwort auf OF-04 und ändert das Datenmodell, wenn sie „mehrere"
  lautet.
- **Keine Zeitschätzung.** Die Reihenfolge folgt Abhängigkeiten, nicht Dauer.
