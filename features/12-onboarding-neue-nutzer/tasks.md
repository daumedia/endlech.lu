# 12 · Onboarding für neue Nutzer — Aufgabenplan

Status: `building` · Stand: 2026-10-22

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft der Verifikationsbefehl aus dem Stack-Profil. **Rot heißt anhalten.**
Aufgaben mit Logik beginnen mit einem Test, der rot ist, bevor der Code ihn grün macht.

**Verifikation nach jeder Ebene** (aus `symfony-doctrine.md`, an das Projekt angepasst —
gebaut wird seit 2026-10-22 über AssetMapper, nicht `npm run build`):

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff     # make fix-check gibt es hier nicht
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console doctrine:schema:validate
php bin/phpunit tests/Unit                       # Zahl der Tests ablesen — null ist rot
```

Volle Suite (`php bin/phpunit`, braucht Test-DB und gebaute Assets) nach Ebene 3 und vor
der Übergabe.

## Ebene 1 · Fundament — Daten und Konfiguration

- [x] **T01** · `App\Entity\User`: Felder `onboardingDue` (bool, Vorgabe `false`) und
      `onboardingShownAt` (`?DateTimeImmutable`) nach `design.md`; Methoden „vorgesehen
      setzen", „angezeigt vermerken" (schreibt nur, wenn leer) und „ist fällig" (bestätigt
      **und** vorgesehen **und** nicht angezeigt). Unit-Test für die Fälligkeitsregel und
      dafür, dass ein zweites Vermerken das erste Datum nicht überschreibt
      — `AK-01, AK-02, AK-04, AK-06, AK-07, AK-53`
- [x] **T02** · Migration `migrations/Version20261022203034.php`: zwei `ADD COLUMN` an
      `user`, Vorgabe `0` bzw. `NULL`, kein Datenumbau. Erzeugt über `make migration`,
      **gelesen, bevor sie läuft** — `AK-06, AK-53`, Grundlage für T17–T19
- [x] **T03** `[P]` · `App\Enum\MarketingSyncState`: fünfter Fall `BLACKLIST_PENDING`
      (`blacklist_pending`) mit `label()`, `badgeClasses()`; zählt bei „wartet auf Brevo"
      mit — `AK-36`
- [x] **T04** · ~~Ebene 1~~ → **mit T19/T20 in Ebene 3.** Limiter `newsletter_toggle` in
      `config/packages/framework.yaml`: `sliding_window`, 10 je Stunde, Kommentar „am Konto,
      nicht an der IP"; `when@test` 10000 — `AK-59`
      *Im Bau verschoben:* `LimiterCoverageTest` wird rot, sobald ein Limiter konfiguriert,
      aber nicht verdrahtet ist — und genau das wäre er zwischen Ebene 1 und 3. `CLAUDE.md`
      verlangt ohnehin, den Limiter im selben Schritt anzulegen wie den Weg, den er deckelt.
- [x] **T05** `[P]` · `config/packages/security.yaml`: Zeile
      `^/[a-z]{2}/einfuehrung` → `IS_AUTHENTICATED_REMEMBERED` neben der Profilregel, mit
      Begründung — `AK-10`
- [x] **T06** · **Plattform, Betreiber:** im Brevo-Konto prüfen, ob der Tarif die
      Automation trägt (OF-04), dann Automation „Willkommen · Konto" nach der Tabelle in
      `design.md` einrichten: Einstieg Liste `BREVO_LIST_ID`, Bedingung `ORIGIN = ACCOUNT`,
      „nur einmal", Verzweigung nach `LOCALE` in fünf Mails mit Abmeldelink, ohne
      Bestandseinbezug; Inhalt nach AK-47/AK-48. Nachweis: Bildschirmfoto der Automation
      und Testempfang. **Blockiert den Bau nicht**, wohl aber die Prüfung von AK-40 ff.
      — `AK-40, AK-41, AK-42, AK-43, AK-44, AK-45, AK-47, AK-48`
      *Stand 2026-10-23:* Kostenloser Tarif (300 Mails/Tag), Automationen im Menü vorhanden — ob Einstieg, Bedingung und „nur einmal“ gehen (OF-04), zeigt erst das Anlegen. Alle fünf Vorlagen in
      Brevo angelegt (#2–#6); Automation #1 „Willkommen · Konto“ vollständig gebaut, **inaktiv**
      (Bild `qa/12/t06/`). Testversand aller fünf Mails zugestellt (15:58), Empfang vom Betreiber bestätigt; eingeschaltet vor dem
      Ausrollen von `v2026.10.23`, im Betrieb mit einem echten Konto nachgeprüft — `willkommens-mail/README.md`.
- [x] **T07** `[P]` · Alle neuen Texte in `translations/messages.{lb,de,fr,en,pt}.yaml`
      (Block `onboarding:`, `profile.newsletter_*`, `flash.onboarding_*`,
      `flash.newsletter_*`, `admin.dashboard.onboarding_*`,
      `marketing_sync_state.blacklist_pending`); Einschränkungsmeldungen, falls neu, in
      `validators.*`. Knapp gehalten (AK-19); keine Bewertungen, Kommentare, Favoriten
      (AK-21); pt in höflicher Form ohne „você"
      — `AK-19, AK-21, AK-22, AK-24, AK-28, AK-31, AK-64`, Grundlage für Ebene 3 und 4

**Verifikation Ebene 1** + `php bin/console doctrine:migrations:migrate` gegen die
lokale DB, `make test-db-setup` für die Test-DB.

## Ebene 2 · Server — Logik und Validierung

- [x] **T08** `[P]` · `MarketingContactRegistry`: (a) `recordUser()` nimmt die Sprache als
      Argument; ohne Argument bleibt die gespeicherte Sprache der Zeile, sonst `de`;
      (b) neue Methode „Konto bestellt ab": Einwilligung an **allen** Quellen der Adresse
      leeren, Zeile sperren (`revokedAt`), und nur wenn sie schon übertragen war
      (`syncedAt`), Zustand `BLACKLIST_PENDING`. Integrationstest je Zweig
      (nie übertragen / übertragen / keine Zeile)
      — `AK-33, AK-36, AK-40, AK-44, AK-46`
- [x] **T09** `[P]` · `MarketingPayloadMapper`: `emailBlacklisted: false` **nur**, wenn die
      Zeile eine Sperre trägt und die Einwilligung jünger ist; Attributliste sonst
      unverändert. Unit-Test: Feld fehlt im Normalfall, steht bei Wieder-Einwilligung,
      keine weiteren Attribute (Entscheidung 6)
      — `AK-35, AK-44, AK-56`
- [x] **T10** `[P]` · Sperre übertragen: `BrevoContactClient` bekommt „sperren"
      (`PUT …?identifierType=ext_id` mit `emailBlacklisted: true`);
      `MarketingSyncService` führt `BLACKLIST_PENDING` aus und markiert übertragen;
      `MarketingContactRepository::findOpenForSync()` nimmt diesen Zustand **trotz Sperre**
      mit; `MarketingContact::markFailed()` hält ihn bei einem Fehlschlag fest wie
      `REMOVAL_PENDING` (im Bau ergänzt — sonst ginge der Auftrag nach dem ersten 429 verloren). Tests mit `MockHttpClient` (Rumpf, Fehlerfall → `FAILED`, Wiederholung)
      — `AK-36, AK-46`
- [x] **T11** · Dienst `App\Account\NewsletterSubscription` (nach T08): „abonnieren"
      (Einwilligung mit Zeitpunkt, Auftragsbuch mit Sprache der Anfrage) und
      „abbestellen"; beide liefern, **ob sich etwas geändert hat** (ohne Wechsel kein
      Kontingent, AK-60). Integrationstest inkl. unbestätigtem Konto (AK-38)
      — `AK-32, AK-33, AK-35, AK-36, AK-38, AK-39, AK-42, AK-60`
- [x] **T12** `[P]` · `App\Onboarding\OnboardingFlow`: Schrittfolge je Konto (Newsletter
      entfällt bei bestehender Einwilligung), Nummer N und Zahl M, vorheriger/nächster
      Schritt, letzter Schritt. Unit-Test für beide Längen
      — `AK-12, AK-13, AK-14, AK-16`
- [x] **T13** `[P]` · Formulare `App\Form\OnboardingAvatarType` (dieselben
      Einschränkungen und Meldungsschlüssel wie das Avatar-Feld in `ProfileType`: 2 MB,
      JPG/PNG/WebP am Inhalt) und `App\Form\OnboardingNewsletterType` (ein
      `CheckboxType`, nicht Pflicht, ohne Vorbelegung, `empty_data` passend). Unit-Test
      der Formulare — `AK-25, AK-26, AK-27, AK-30, AK-58`
- [x] **T14** `[P]` · `AdminStatsService`: Zahl der vorgesehenen Konten und Zahl der
      davon angezeigten (zwei `COUNT`-Abfragen, keine Liste). Unit-/Integrationstest
      — `AK-49`
- [x] **T15** `[P]` · `App\Account\AccountDataExporter`: Abschnitt Einführung
      (vorgesehen, angezeigt am) und Newsletter-Stand mit Zeitpunkt; Integrationstest für
      Export **und** Kontolöschung (beide Spalten weg, Brevo-Löschauftrag gestellt)
      — `AK-61, AK-62`
- [x] **T16** `[P]` · `App\Usage\UsageEventCatalogue` (+ Normalisierer-Regel): drei
      Ereignisse `einfuehrung_begonnen`, `einfuehrung_abgeschlossen` (beide ohne Daten),
      `einfuehrung_beendet` mit `schritt` ∈ `1`–`4`; alles andere wird verworfen.
      Unit-Test inkl. Angriffswerte (Adresse als `schritt`, Zusatzschlüssel)
      — `AK-50, AK-51, AK-52`

**Verifikation Ebene 2.**

## Ebene 3 · Schnittstellen

- [x] **T17** `[P]` · `App\EventSubscriber\OnboardingRedirectSubscriber` auf die
      erfolgreiche Anmeldung der Firewall `main`: ersetzt **nur** eine Weiterleitung, deren
      Pfad der Startseite entspricht, und nur bei fälligem Konto (Entscheidung 1).
      Funktionstests: erste Anmeldung → Willkommen; zweite → Startseite; mit gemerkter
      Zielseite (B11) → Zielseite und weiter fällig; unbestätigt → Startseite;
      Bestandskonto → Startseite; „angemeldet bleiben" → keine Umlenkung
      — `AK-01, AK-02, AK-03, AK-04, AK-06`, `EC-06`
- [x] **T18** `[P]` · `EmailVerificationController::verify()`: Merker setzen,
      `recordUser()` mit Sprache der Anfrage; ist dasselbe Konto angemeldet → Willkommen
      statt Anmeldeseite. Funktionstests inkl. anderes Konto angemeldet (EC-04), Konto aus
      `/api/v1/auth/register` (AK-08), vor dem Merker angelegtes unbestätigtes Konto (AK-07)
      — `AK-05, AK-07, AK-08, AK-40`, `EC-04, EC-05`
- [x] **T19** `[P]` · `App\Controller\OnboardingController`: `GET einfuehrung/{schritt}`
      (Vorgabe `willkommen`; unbestätigt → `/verify`; erstes Ausliefern vermerkt;
      `newsletter` bei bestehender Einwilligung → Suche), `POST einfuehrung/profilbild`
      (422 bei Fehler, sonst zurück mit Meldung), `POST einfuehrung/newsletter` (Limiter
      `newsletter_toggle` am Konto, Verbrauch nur bei Wechsel, danach Suche).
      Funktionstests je Kriterium
      — `AK-02, AK-09, AK-10, AK-11, AK-12, AK-13, AK-25, AK-26, AK-27, AK-29, AK-32,
      AK-33, AK-57, AK-58, AK-59, AK-60`
- [x] **T20** `[P]` · `ProfileController`: `POST profile/newsletter` mit Token
      `profile-newsletter`, Limiter am Konto, gemeinsamer Dienst; `index()` reicht den
      Stand durch. Funktionstests: beide Richtungen, unbestätigt, Deckel beim elften
      Wechsel (auch von anderer IP), fremdes Token, fremde Kennung im Rumpf
      — `AK-34, AK-35, AK-36, AK-37, AK-38, AK-57, AK-58, AK-59, AK-60`
- [x] **T21** `[P]` · `App\Seo\SeoRegistry`: `app_onboarding` in die Liste „bewusst weder
      angeboten noch ausgeschlossen" mit Begründung (nur nach Anmeldung erreichbar).
      `SeoRouteCoverageTest` wird sonst rot — Projektregel (Feature 10), kein AK

**Verifikation Ebene 3** + volle Suite.

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt (Tabelle in `design.md`).

- [x] **T22** · Rahmen `templates/onboarding/_rahmen.html.twig`: Schrittkopf mit
      fokussierter Überschrift und „Schritt N von M" (auch im Seitentitel), Fußleiste mit
      „Zurück", „Weiter"/„Restaurants suchen", „Einführung beenden" (Messauslöser mit
      Schrittnummer), alle Ziele ≥ 44 px — `AK-14, AK-15, AK-16, AK-17, AK-18, AK-50,
      AK-67, AK-68`
- [x] **T23** `[P]` · `templates/onboarding/willkommen.html.twig` (Gruß, zwei Karten,
      Messauslöser „begonnen" beim Erscheinen) und `eintraege.html.twig` (Punkte a–c mit
      **denselben** Katalogschlüsseln wie die Detailseite, Abzeichen über
      `_verified_badge`, Satz aus AK-24; Punkt d fehlt bis OF-01)
      — `AK-19, AK-20, AK-22, AK-23, AK-24, AK-50, AK-54`
- [x] **T24** `[P]` · `templates/onboarding/profilbild.html.twig` (vorhandenes Bild,
      Upload über `_form_field`, Fehler am Feld, Hinweis zur Sichtbarkeit; als letzter
      Schritt „Restaurants suchen" als Link mit Messauslöser) und `newsletter.html.twig`
      (leeres Häkchen, Erklärung, Absendeknopf „Restaurants suchen" mit Messauslöser)
      — `AK-17, AK-19, AK-25, AK-26, AK-28, AK-29, AK-30, AK-31, AK-50, AK-54`
- [x] **T25** `[P]` · Profil: `templates/profile/_newsletter_manage.html.twig` (Stand mit
      Datum, Knopf in **eigenem** Formular außerhalb des Profilformulars, Hinweis bei
      unbestätigter Adresse, Satz, dass Abbestellen für alle Neuigkeiten an diese Adresse
      gilt) und Link „Einführung ansehen" in `templates/profile/index.html.twig`
      — `AK-09, AK-34, AK-37, AK-38`
- [x] **T26** `[P]` · Kachel „Einführung" in `templates/admin/dashboard.html.twig`, zwei
      Zahlen, keine Namen; `AdminDashboardController` reicht sie durch — `AK-49`

**Verifikation Ebene 4** + `php bin/console tailwind:build` und
`php bin/console typescript:build`, dann die volle Suite.

## Ebene 5 · Feinschliff

- [x] **T27** · Barrierefreiheit im Browser: nur Tastatur von Willkommen bis Suche mit
      Upload und Häkchen; Fokus auf der Überschrift jedes Bildschirms; Fehler beim Upload
      wird vorgelesen; Zielgrößen gemessen; Sprachwechsel mitten im Ablauf bleibt auf dem
      Schritt — `AK-65, AK-66, AK-67, AK-68`
- [x] **T28** · Randfälle nachstellen, wo möglich als Test: zwei Geräte, Abbruch beim
      Upload, Brevo nicht erreichbar, Bestätigung mit anderem angemeldetem Konto,
      abgelaufener Link, „angemeldet bleiben", Passwort-Reset vor erster Anmeldung,
      zweiter Tab, Neustart im Profil, Konto im anderen Tab gelöscht
      — `EC-01, EC-02, EC-03, EC-04, EC-05, EC-06, EC-07, EC-08, EC-09, EC-10`
- [x] **T29** · Protokolle nach einem vollen Durchlauf (Upload, Abo, Abbestellen) lesen:
      keine Adresse, kein Name, kein Originaldateiname; Quelltext von Einführung und
      Profil ohne Brevo-Schlüssel — `AK-55, AK-63`
- [x] **T30** · Prüfläufe für die Texte: Wortstämme für Bewertung/Kommentar/Favorit je
      Sprache über alle `onboarding.*`-Schlüssel (Muster
      `testKeineSeiteBietetNochEinenBeiratAn`); Gleichheit der Begriffe mit der
      Detailseite; Katalogvollständigkeit — `AK-21, AK-23, AK-64`
- [x] **T31** · Dokumentation mitziehen: `docs/data-model.md` (zwei Spalten, neuer
      Sync-Zustand, Migrations-Historie), `docs/datenschutz.md` (Willkommens-Mail,
      Abbestellen sperrt statt löscht, zwei Kontoangaben), `docs/app-shell.md` (Weiche
      nach der Anmeldung), `CLAUDE.md` (Abschnitt mit den Fallstricken dieses Features)
      — Projektregel, kein AK

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T01, T17 |
| AK-02 | T01, T17, T19 |
| AK-03 | T17 |
| AK-04 | T01, T17 |
| AK-05 | T18 |
| AK-06 | T01, T02, T17 |
| AK-07 | T01, T18 |
| AK-08 | T18 |
| AK-09 | T19, T25 |
| AK-10 | T05, T19 |
| AK-11 | T19 |
| AK-12 | T12, T19 |
| AK-13 | T12, T19 |
| AK-14 | T12, T22 |
| AK-15 | T22 |
| AK-16 | T12, T22 |
| AK-17 | T22, T24 |
| AK-18 | T22 |
| AK-19 | T07, T23, T24 — Nachweis mit Stoppuhr in `sdd-qa` |
| AK-20 | T23 |
| AK-21 | T07, T30 |
| AK-22 | T07, T23 |
| AK-23 | T23, T30 |
| AK-24 | T07, T23 |
| AK-25 | T13, T19, T24 |
| AK-26 | T13, T19, T24 |
| AK-27 | T13, T19 |
| AK-28 | T07, T24 |
| AK-29 | T19, T24 |
| AK-30 | T13, T24 |
| AK-31 | T07, T24 |
| AK-32 | T11, T19 |
| AK-33 | T08, T11, T19 |
| AK-34 | T20, T25 |
| AK-35 | T09, T11, T20 |
| AK-36 | T03, T08, T10, T11, T20 |
| AK-37 | T20, T25 (bestehender Webhook) |
| AK-38 | T11, T20, T25 |
| AK-39 | T11 |
| AK-40 | T06, T08, T18 |
| AK-41 | T06 |
| AK-42 | T06, T11 |
| AK-43 | T06 |
| AK-44 | T06, T08, T09 |
| AK-45 | T06 |
| AK-46 | T08, T10 |
| AK-47 | T06 |
| AK-48 | T06 |
| AK-49 | T14, T26 |
| AK-50 | T16, T22, T23, T24 |
| AK-51 | T16 |
| AK-52 | T16 |
| AK-53 | T01, T02 |
| AK-54 | T23, T24 |
| AK-55 | T29 |
| AK-56 | T09 |
| AK-57 | T19, T20 |
| AK-58 | T13, T19, T20 |
| AK-59 | T04, T19, T20 |
| AK-60 | T11, T19, T20 |
| AK-61 | T15 |
| AK-62 | T15 |
| AK-63 | T29 |
| AK-64 | T07, T30 |
| AK-65 | T27 |
| AK-66 | T27 |
| AK-67 | T22, T27 |
| AK-68 | T22, T27 |

**AK ohne Aufgabe:** keine — alle 68 geprüft.
**Aufgabe ohne AK:** T21 (`SeoRegistry`, Projektregel aus Feature 10 — ohne sie wird
`SeoRouteCoverageTest` rot), T31 (Dokumentation, Projektregel aus `CLAUDE.md`) —
beide zulässig. T28 verweist auf `EC-01` bis `EC-10`.

## Parallelisierung

| Ebene | Gleichzeitig | Dateien je Aufgabe — überschneidungsfrei |
|---|---|---|
| 1 | T03, T04, T05, T07 | T03 `src/Enum/MarketingSyncState.php` · T04 `config/packages/framework.yaml` · T05 `config/packages/security.yaml` · T07 `translations/*.yaml`. T01 → T02 seriell (Migration aus dem Entity-Stand); T06 ist Betreiberarbeit |
| 2 | T08, T09, T10, T12, T13, T14, T15, T16 | T08 `MarketingContactRegistry.php` + eigener Test · T09 `MarketingPayloadMapper.php` + Test · T10 `BrevoContactClient.php`, `MarketingSyncService.php`, `MarketingContactRepository.php`, `MarketingContact.php` + Tests · T12 `src/Onboarding/` · T13 zwei neue Formulare · T14 `AdminStatsService.php` · T15 `AccountDataExporter.php` + Test · T16 `UsageEventCatalogue.php`, `CollectPayloadNormalizer.php` + Tests. **T11 nach T08** (ruft dessen neue Methode) |
| 3 | T17, T18, T19, T20, T21 | T17 neuer Subscriber · T18 `EmailVerificationController.php` · T19 neuer `OnboardingController.php` · T20 `ProfileController.php` · T21 `SeoRegistry.php`; jede mit eigener Testdatei. Texte liegen seit T07 vor, keine Aufgabe fasst die Kataloge an |
| 4 | T23, T24, T25, T26 | nach T22 (Rahmen): T23 zwei Templates · T24 zwei Templates · T25 Profil-Templates · T26 `admin/dashboard.html.twig` + `AdminDashboardController.php` |
| 5 | seriell | Prüfungen im Browser und am Protokoll greifen auf denselben Stand |

## Vor dem Bauen

- [x] Feature-Branch: `feature/12-onboarding` (abgezweigt von `feature/asset-mapper`, das
      zwei Commits vor `main` liegt und den AssetMapper-Umbau trägt)
- [ ] Docker läuft (MySQL 8.0, Mailpit), `make test-db-setup` nach T02
- [ ] Gebaute Assets für Funktionstests (`tailwind:build`, `typescript:build`)
- [ ] Brevo: Tarif geprüft (OF-04) — nur für T06, nicht für den Bau

## Abschlussbericht · 2026-10-22

### 1 · Umgesetzt

Nach der ersten Anmeldung eines bestätigten Kontos führt die Anwendung durch vier Bildschirme
(Willkommen, Einträge lesen, Profilbild, Newsletter) und endet in der Restaurantsuche; eine
gemerkte Zielseite (B11) hat Vorrang, Bestandskonten sehen nichts. Das Profil hat einen
Newsletter-Schalter in beide Richtungen und den Link „Einführung ansehen"; die Verwaltung zeigt
zwei Summen. Abbestellen sperrt den Kontakt in Brevo (neuer Sync-Zustand), erneutes Einwilligen
hebt die Sperre auf, und die Sprache eines Konto-Kontakts kommt aus der Anfrage. Drei
Messereignisse ohne Personenbezug. **30 von 31 Aufgaben erledigt**, offen T06 (Betreiber).
Abgedeckt durch Code und Test: alle Kriterien außer den unten genannten.

### 2 · Offene Akzeptanzkriterien

- **AK-40 bis AK-45, AK-47, AK-48** (Willkommens-Mail) — **nicht in der Anwendung lösbar**, T06
  beim Betreiber: Automation in Brevo nach der Tabelle in `design.md` einrichten, vorher den
  Tarif prüfen (OF-04). Die Anwendung liefert, was die Automation braucht — Kontakt mit
  `ORIGIN=ACCOUNT`, `LOCALE` aus der Anfrage, Sperre statt Löschung —, belegt in
  `Feature12RegistryTest`, `Feature12SperreSyncTest`, `OnboardingVerificationTest::testAk40…`.
  Kein Testempfang möglich: Ohne Zugang zum Brevo-Konto gibt es hier keine Automation.
- **AK-46** — Anwendungsseite belegt (nie übertragen → nie übertragen; übertragen → Sperre),
  der Teil „Brevo stellt einem gesperrten Kontakt nichts zu" ist Brevos Verhalten und erst mit
  T06 prüfbar.
- **AK-19** (unter zwei Minuten) — nicht gemessen, nur abgeschätzt: rund 290 Wörter auf vier
  Bildschirmen (80/100/51/≈60), bei 200 Wörtern je Minute ≈ 1½ Minuten Lesezeit. Braucht die
  Stoppuhr mit einer Person in `sdd-qa`.
- **AK-66, AK-67, Teil Fokusrahmen** — im Browser nicht messbar: Das Vorschaufenster dieser
  Umgebung hat keinen Fokus (`document.hasFocus() === false`), `:focus` greift dort nie. Belegt
  ist: Tab-Reihenfolge folgt der sichtbaren (Vorschlagen → App → Weiter, auf jedem Bildschirm
  geprüft), der ganze Ablauf geht nur mit Tab/Leertaste/Enter bis in die Suche, Turbo fokussiert
  bei jedem Seitenwechsel die Überschrift (`activeElement` = `einfuehrung-titel`), die
  Fokus-Klassen sind dieselben wie im übrigen Bestand, und die globale `:focus-visible`-Regel
  (2 px Cyan) ist geladen. Ob der Rahmen sichtbar ist, muss jemand an einem Gerät mit Fokus sehen.

### 3 · Getroffene Annahmen

- **AK-53 neu gefasst** (OF-05 in der Spec): Ein Ja/Nein-Merker zusätzlich zum Datum, sonst
  ließen sich Bestandskonten nicht unterscheiden. Der Betreiber hat dem nicht ausdrücklich
  zugestimmt — die neue Fassung gilt bis zu einem Widerspruch.
- **OF-03 mit Vorgabe beantwortet** (Entwurf, Entscheidung 8): Wer schon als
  Wartelisten-Kontakt in Brevo steht, bekommt keine Willkommens-Mail. Folge des Einstiegs
  „Kontakt zur Liste hinzugefügt", nicht eigens gebaut.
- **OF-01 offen** → der Bildschirm „Einträge lesen" erklärt die Zahl auf der Restaurantkarte
  nicht (AK-22 d).
- **Abbestellen gilt für die Adresse**, nicht nur fürs Konto (Entwurf, Entscheidung 5) — der
  Profiltext sagt es.
- **Ein Formular der Einführung, das ohne Datei abgeschickt wird**, ist kein Fehler; es geschieht
  nichts (AK-15 sinngemäß).
- **Unbekannte Felder in den Formularen der Einführung werden übergangen**
  (`allow_extra_fields`), damit AK-57 wörtlich gilt: Mit `user=42` im Rumpf ändert sich das
  eigene Konto, sonst nichts.
- **Der Vorgabeschritt steht nicht in der Adresse**: `/de/einfuehrung` ist die Willkommensseite.
- **Reihenfolge der Bildschirme** und **„Zurück"-Knopf** (AK-16) sind Vorschläge der Spec, die
  der Betreiber mit der Freigabe angenommen hat.

### 4 · Systemweite Änderungen

- **Feature 04 (Brevo) verhält sich an drei Stellen anders:** `recordUser()` schreibt die
  Sprache der Anfrage statt fest `de`; der Mapper schickt nach bekannter Sperre und jüngerer
  Einwilligung `emailBlacklisted: false` (vorher nie — auch eine Wieder-Einwilligung über eine
  Warteliste nach einer Abmeldung blieb bei Brevo gesperrt); neuer Sync-Zustand
  `blacklist_pending`, `findOpenForSync()` holt ihn trotz Sperre, `markFailed()` hält ihn fest.
  Die bestehenden 51 Marketing-Tests bleiben grün.
- **`ProfileType`**: Die Avatar-Regel steht jetzt in `App\Form\AvatarConstraints` (gemeinsam mit
  der Einführung), Verhalten unverändert.
- **`EmailVerificationController::verify()`**: setzt den Merker; leitet ein bereits mit
  demselben Konto angemeldetes Gegenüber in die Einführung statt auf die Anmeldeseite.
- **`config/packages/security.yaml`**: neue Zeile `^/[a-z]{2}/einfuehrung`.
- **`config/packages/framework.yaml`**: neuer Limiter `newsletter_toggle` (+ `when@test`).
- **`SeoRegistry`**: `app_onboarding` in `UNLISTED_ROUTES`.
- **`UsageEventCatalogue`**: drei neue Ereignisse. `/legal` bleibt wahr („Ereignisse wie …",
  „Profil wird nicht gemessen").
- **Profilseite** und **Verwaltungsübersicht**: je ein neuer Abschnitt bzw. eine Kachel.
- **Kataloge**: zwei neue Blöcke `onboarding`, `newsletter` und
  `marketing_sync_state.blacklist_pending` in allen fünf Sprachen.
- **Migration** `Version20261022203034` (zwei Spalten an `user`). ⚠ Post-Deployment-Command in
  Coolify muss sie ausführen.
- **Doku**: `docs/data-model.md`, `docs/datenschutz.md`, `docs/app-shell.md`, `CLAUDE.md`.
- **Im Bau gefundener Vorbestand, nicht angefasst:** `php bin/console lint:container` scheitert
  an einem Alias im WebAuthn-Bundle (`WebauthnCredentialRepository` implementiert
  `PublicKeyCredentialSourceRepositoryInterface` nicht) — Dateien dieses Features sind nicht
  beteiligt. `vendor/bin/php-cs-fixer` existiert nicht (nicht in `composer.json`), `make fix`
  läuft in diesem Stand also gar nicht; der Stil wurde von Hand gehalten. `schema:validate`
  meldet die bekannte Altlast (12 Anweisungen), keine zu diesem Feature.
- **Lokale Umgebung:** Port 3306 belegt die Datenbank eines anderen Projekts. Gebaut und
  getestet gegen einen Wegwerf-Container `endlech-sdd-testdb` (MySQL 8.0, Port 3309) mit
  `DATABASE_URL` aus der Umgebung; für den Browsertest ein Router-Skript unter `/tmp`.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 119 (17 Dateien) |
| davon rot gesehen, dann grün | 70 — 54 vor der Umsetzung rot, 16 über Gegenprobe (Fehler eingebaut, rot gesehen, zurückgebaut) |
| gelaufen in der letzten Verifikation | 1504, alle grün, 10 übersprungen (wie im Ausgangsstand 1385/10) |

**Nicht rot gesehen** (49), mit Grund:

- **Halten Bestandsverhalten fest und waren deshalb sofort grün:** `Feature12RegistryTest::
  testOhneSpracheUndOhneZeileBleibtEsBeiDe`, `Feature12PayloadMapperTest::testEc05…`,
  `::testNochGesperrt…`, `OnboardingDataRightsTest::testAk61…` (bestehende Löschkaskade),
  `OnboardingRedirectTest` AK-02, AK-03, AK-04, AK-06, EC-06 (sie würden rot, wenn die Weiche zu
  viel umlenkte — eine Gegenprobe dafür steht aus), `OnboardingVerificationTest::testEc05…`.
- **Nach der Umsetzung geschrieben, Gegenprobe nur stichprobenweise:** in
  `OnboardingControllerTest` 19 von 23, `ProfileNewsletterTest` 8 von 11 (die Gegenprobe traf
  dort je die tragenden Schutzregeln: Bestätigung, Anzeigevermerk, Deckel, CSRF, Verbrauch),
  `AdminOnboardingTileTest`, `OnboardingEdgeCaseTest` (5).
- **Aufgaben mit Logik ohne eigenen Test:** keine. Ohne Test geblieben sind T02 (Migration —
  belegt über die Spaltenliste in der DB und `schema:update --dump-sql` ohne Rest) und T31 (Doku).
- **EC-02** (Abbruch beim Upload) und **EC-03** (Brevo nicht erreichbar) ohne Test: EC-02 lässt
  sich im Testclient nicht abbrechen; EC-03 ist strukturell — abonnieren ruft Brevo nie, nur der
  Fünf-Minuten-Lauf, dessen Fehlerverhalten Feature 04 belegt.

## Abschlussbericht · 2026-10-23 · Fehlerauftrag BUG-01 bis BUG-09

### 1 · Umgesetzt

Alle neun Befunde der QA vom 2026-10-22 am Feature behoben (BF-154, BF-155, BF-157 bis
BF-163), je mit Vermerk unter dem Fehler in `qa-report.md`. Die beiden hohen: Wieder-Einwilligung
hebt die Brevo-Sperre jetzt tatsächlich auf (der PUT trägt `emailBlacklisted: false`), und ein
unbestätigtes Konto kann keine fremde Einwilligung mehr zurücknehmen.

### 2 · Offene Akzeptanzkriterien

- **BF-159, Profilhälfte** — nicht behoben, gehört zu B04 (`ProfileController::edit()` zeigt bei
  überschrittener `post_max_size` weiter keine Meldung). In der Einführung behoben (AK-26).
- **BF-156 (B02)** und **BF-164 (B04)** — vorbestehend, nicht Teil dieses Auftrags. BF-156 habe ich
  beim Nachprüfen im Browser selbst ausgelöst: In der Sitzung, in der vorher ein Formular mit
  Double-Submit abgeschickt wurde, meldete die Anmeldung „Ungültiges CSRF-Token.". Die
  Reproduktion `Qa12OnboardingTest::testBf156…` bleibt übersprungen.
- Unverändert offen aus dem ersten Bericht: T06 (Brevo-Automation), AK-19 (Stoppuhr), Fokusrahmen
  im Browser nicht messbar.

### 3 · Getroffene Annahmen

- **BUG-04 doppelt gelöst:** Prefetch-Kopf erkennen **und** Profillink vom Vorabladen ausnehmen.
  Nur den Kopf zu erkennen hätte AK-02 gebrochen — Turbo zeigt beim Klick die vorab geholte
  Antwort, die echte Anzeige käme nie beim Server an.
- **BUG-05 als Feldfehler mit 422**, nicht wie BF-58 als Weiterleitung mit Flash — so verlangt es
  die Reproduktion der QA, und AK-26 spricht von „derselben Meldung am Feld".
- **BUG-07:** im angemeldeten Zweig gar keine Meldung statt eines neuen Textes; die
  Willkommensseite sagt es bereits.

### 4 · Systemweite Änderungen

- `BrevoContactClient::upsert()` reicht `emailBlacklisted` weiter (Feature 04).
- `MarketingContactRegistry::changeEmail()` lässt Sperr- **und Löschaufträge** stehen — Letzteres
  behebt den vorbestehenden Teil von BF-157 mit (`removal_pending` ging beim Adresswechsel ebenso
  verloren).
- `translations/validators.*.yaml`: `profile.avatar_max_size` mit Einheit — wirkt auch im Profil
  (B04).
- `tests/Functional/Controller/Qa12OnboardingTest.php` (Datei der QA): die Zeile
  `$this->offen('BF-…')` bei den sechs behobenen Reproduktionen entfernt, wie der Kopfkommentar
  der Datei es vorsieht. `BF-156` bleibt übersprungen.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 3 (`Feature12BugfixTest`) |
| davon rot gesehen, dann grün | 3, dazu die 6 Reproduktionen der QA (mit `QA12_REPRO=1` rot, nach der Behebung grün) |
| gelaufen in der letzten Verifikation | 1517, alle grün, 11 übersprungen (10 Ausgangsstand + BF-156) |

Von Hand am laufenden Server wiederholt: BF-158 (`curl` mit und ohne Prefetch-Kopf), BF-159 und
BF-163 (27 MB gegen `post_max_size=8M` → 422 mit „2 MB"), BF-162 (fremdes Token → 422 mit
Meldung). BF-154, BF-155, BF-157, BF-160 nur über die Tests der QA, die den echten
`MarketingSyncService` gegen einen Brevo-Nachbau laufen lassen.

## Abschlussbericht · 2026-10-23 · Zweiter Fehlerauftrag (BUG-10 bis BUG-18, BF-156, BF-164)

### 1 · Umgesetzt

Alle Befunde der Nachprüfung am Feature behoben (BF-165 bis BF-168, BF-170 bis BF-174) und die
beiden blockierenden vorbestehenden: **BF-156** (B02 — Anmeldung und Abmeldung nach Double-Submit)
und **BF-164** (B04 — Profil mit Bild nicht speicherbar), dazu die Profilhälfte von BF-159. Je
Befund ein Vermerk in `qa-report.md`; B02 und B04 tragen einen Nachtrag in ihrem eigenen Bericht.

### 2 · Offene Akzeptanzkriterien

- **BF-169** (Feature 04, *niedrig*) — nicht Teil dieses Auftrags; Reproduktion bleibt übersprungen.
- **BUG-13, Hälfte „Anzeige"** — Sperraufträge werden jetzt ohne Grenze wiederholt; eine
  Verwaltungsansicht für Konto-Kontakte gibt es weiterhin nicht.
- **BUG-11, Restfenster** — eine Abmeldung, die ihren Stand liest, bevor der Lauf schreibt, und erst
  nach dessen Nachsehen schreibt, bliebe unentdeckt. Millisekunden statt der ganzen Laufdauer; ganz
  schließen ließe es sich nur mit optimistischer Sperre (Versionsspalte) — nicht im Entwurf.
- Unverändert: T06/OF-04, AK-19 (Stoppuhr), AK-67 (Screenreader), OF-01, OF-03, OF-05, neu OF-06.

### 3 · Getroffene Annahmen

- **Fehlerauftrag an B02 und B04 ohne deren `review`-Status.** Die QA von Feature 12 hat die Befunde
  dort eingetragen, den Status bewusst auf `deployed` gelassen und `/sdd-build B02`/`B04` als nächsten
  Schritt genannt; Vorbild ist BF-151 an B15. Gebaut auf demselben Branch, weil die Befunde den
  Hauptweg dieses Features blockieren.
- **BF-156 im Browser behoben, nicht am Server.** Der Double-Submit-Merker in der Sitzung ist Symfonys
  Schutz gegen das Zurückfallen auf die Herkunftsprüfung. `Qa12OnboardingTest::testBf156…` bleibt
  übersprungen (mit Begründung im Test) — er schickt die Anmeldung ohne Double-Submit und bildet damit
  den alten Browser nach.
- **BUG-13: ohne Abstand wiederholt** (je Lauf ein Aufruf), weil die Reproduktion der QA die Sperre im
  nächsten Lauf verlangt; ein Abstand hätte sie rot gelassen.
- **BUG-15: neue Flash-Art `usage_event`** im gemeinsamen Flash-Partial statt einer Abfrage auf der
  Restaurantliste.
- **BUG-17 ohne Deckel**, technisch über Sperre und frisches Lesen; die Deckelfrage ist OF-06.
- **BF-173-Test:** Seine Erwartung wurde nach der ersten Behebung verschärft („übrig bleibt nur das
  neue Bild" statt „die Datei der anderen Anfrage ist weg"), weil die endgültige Behebung beide alten
  Namen wegräumt. Gegen den alten Code ist auch die verschärfte Erwartung rot.

### 4 · Systemweite Änderungen

- `assets/controllers/csrf_protection_controller.ts` **sofort geladen** — wirkt auf jedes Formular mit
  `_csrf_token` im ganzen Projekt (B02).
- `templates/security/login.html.twig`, `templates/base.html.twig`, `templates/profile/index.html.twig`:
  `data-controller="csrf-protection"` an den Token-Feldern; Profil: Löschformular außerhalb (B04).
- `templates/partials/_flash_messages.html.twig`: Flash-Art `usage_event` wird als Messauslöser
  gerendert, nicht als Meldung.
- `AvatarUploadService`: optionale `LockFactory`, Sperre je Konto, alter Name auch aus der Datenbank (B04).
- `AvatarConstraints`: `tooLargeMessage()`, `bodyWasDiscarded()`; `ProfileController::edit()` nutzt sie.
- Feature 04: `MarketingPayloadMapper` (Bedingung an `syncedAt`), `MarketingSyncService` (Nachtrag nach
  dem Lauf), `MarketingContactRepository::findOpenForSync()` (Sperraufträge ohne Grenze),
  `MarketingContactRegistry::revokeForAccount()` (Auftrag bei jedem Versuch, nie über Löschauftrag).
- Kataloge: `newsletter.text_unverified` in fünf Sprachen.
- `CLAUDE.md`: zwei neue Fallstricke (CSRF-Controller, Flash-Art `usage_event`) und Ergänzungen in
  Feature 12. Spec: OF-06.
- QA-Testdateien: `$this->offen(…)` bei BF-165 bis BF-168 entfernt; `testBf156…` mit neuer Begründung.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 11 — `Bf156CsrfControllerTest` (3), `Bf164ProfilFormularTest` (3), `Bf173AvatarVerwaistTest` (1), `Bf172GleichzeitigesAboTest` (1), `Feature12Nachpruefung2BugfixTest` (3) |
| davon rot gesehen, dann grün | 11 (der BF-164-Strukturtest erst nach Korrektur: er las zunächst den geparsten DOM, in dem die Verschachtelung schon aufgelöst war) — dazu die 5 Reproduktionen der QA zu BF-165 bis BF-168 |
| gelaufen in der letzten Verifikation | 1535, alle grün, 12 übersprungen (10 Ausgangsstand, BF-156 mit Begründung, BF-169) |

Im Browser nachgeprüft: BF-156 (Anmelden → Profil → Abmelden → Anmeldeseite frisch → Anmelden;
Abmelden von der frisch geladenen Startseite; dev.log „both origin and double-submit"), BF-164
(Knöpfe gehören zu den richtigen Formularen, Speichern mit Bild gelingt). Nicht nachgestellt: BF-172
mit mehreren Arbeitsprozessen, BF-173 mit echten parallelen Uploads.

## Abschlussbericht · 2026-10-23 · Dritter Fehlerauftrag (BUG-20 bis BUG-26)

### 1 · Umgesetzt

Alle sieben Befunde der zweiten Nachprüfung behoben: **BF-175** (*hoch*, vorbestehend in B04 und auf
der Produktion — Adresswechsel ohne Bestätigung über einen Bild-Upload), **BF-176** (*mittel*, B04 —
Deckel an der gewünschten Adresse), **BF-180** (B04/12 — Entfernen neben einem Upload) sowie am
Feature **BF-177** (Messung über Turbos Zwischenspeicher), **BF-178** (Wieder-Einwilligung während
bzw. in derselben Sekunde wie die Sperrübertragung), **BF-179** („begonnen" bei vollem Seitenaufruf)
und **BF-181** (Abbestellen ohne Sperrauftrag nach zurückgesetzter Spur). Je Befund ein Vermerk in
`qa-report.md`, Status in `befunde.md`; B04 trägt einen Nachtrag in seinem Bericht.

### 2 · Offene Akzeptanzkriterien

- **BF-169** (Feature 04, *niedrig*) — nicht Teil dieses Auftrags; Reproduktion bleibt übersprungen.
- **BUG-11, Restfenster** (aus dem zweiten Auftrag) gilt ebenso für den neuen Nachtrag aus BF-178:
  eine Einwilligung, die ihren Stand liest, bevor der Lauf schreibt, und erst nach dessen Nachsehen
  schreibt. Millisekunden; ganz schließen ließe es sich nur mit einer Versionsspalte.
- Unverändert: T06/OF-04, AK-19 (Stoppuhr), AK-67 (Screenreader), OF-01, OF-03, OF-05, OF-06.

### 3 · Getroffene Annahmen

- **BF-175: zurücksetzen statt den Upload-Dienst umzubauen.** Der Vorschlag der QA nannte beides; das
  Zurücksetzen direkt nach der Validierung genügt, weil danach keine fremde Änderung mehr in der Unit
  of Work steht. `AvatarUploadService` schreibt weiter selbst (Einführung und Profil rufen ihn so).
- **BF-176: Schlüssel ist die bestätigte Adresse**, nicht die Kontokennung — Projektkonvention wie bei
  `password_change`, und ein `'konto-'.$id` hätte einen neuen Schlüsselraum eingeführt.
- **BF-178: Mapper bleibt bei `<`.** Mit `<=` schickte ein Abgleich in derselben Sekunde wie die
  übertragene Aufhebung sie erneut mit (BF-165 im Sekundenfall). Stattdessen legt `consentMomentFor()`
  die Einwilligung hinter `max(revoked_at, synced_at)`. Die zweite QA-Reproduktion setzte genau diese
  Sekunde voraus und hätte mit `markTestIncomplete` geendet; sie prüft jetzt, dass sie nicht eintritt.
- **BF-181: Sperrauftrag für jede bestehende Zeile.** Damit kippt eine frühere Zusicherung des Baus
  („nie übertragen → kein Auftrag"); zwei Tests sind mit Begründung angepasst (`Feature12RegistryTest`,
  letzte Prüfung von `Qa12OnboardingTest::testBf160…`). Nach einem Adresswechsel trägt die Sperre nur
  die `ext_id`; bei Brevo bleibt die alte Adresse gesperrt stehen, die neue entsteht dort nicht.
- **BF-179: ein Wiederholversuch beim `load`-Ereignis**, nicht mehrere. Fehlt das Zählskript nach dem
  Laden noch, ist es blockiert oder widersprochen — dann soll nicht gezählt werden.

### 4 · Systemweite Änderungen

- `ProfileController::edit()` (B04): Reihenfolge Validierung → Adresse zurücksetzen → Upload → Deckel →
  Vormerkung; Deckelschlüssel bestätigte Adresse.
- `AvatarUploadService::delete()` (B04): unter der Sperre je Konto, Name auch frisch aus der Datenbank.
- `assets/controllers/usage_event_controller.ts` (Feature 11): Wiederholversuch beim `load` — wirkt
  auf jeden `on-connect`-Auslöser im Projekt, auch die Erfolgsmeldungen der Wartelisten.
- `templates/partials/_flash_messages.html.twig`: `data-turbo-temporary` am `usage_event`-Element.
- Feature 04: `MarketingSyncService` (zweiter Nachtrag nach dem Lauf), `MarketingContactRegistry`
  (`revokeForAccount()` ohne „vielleicht bei Brevo", `consentMomentFor()` hinter `synced_at`).
- `CLAUDE.md`: Fallstricke zu BF-175, BF-178, BF-181 und `data-turbo-temporary` ergänzt.
- QA-Testdateien: `$this->offen(…)` bei BF-175 bis BF-181 entfernt; zwei Prüfungen angepasst (siehe 3).

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 3 — `Feature12SperreSyncTest::testBf181NieUebertragenerKontaktErzeugtNurDenSperraufruf`, `::testBf178WiedereinwilligungWaehrendDesLaufsBleibtOffen`, `::testBf178GewoehnlicheSperreBleibtUebertragen` |
| davon rot gesehen, dann grün | 1 (`testBf178Wiedereinwilligung…`, Gegenprobe mit abgeschaltetem Nachtrag); die beiden anderen sind Schutzprüfungen (AK-46, Gegenprobe) und gegen den alten Code ebenso grün — dazu die 7 Reproduktionen der QA zu BF-175 bis BF-181, alle vorher scharf rot |
| gelaufen in der letzten Verifikation | 1545, alle grün, 12 übersprungen (10 Ausgangsstand, BF-156 mit Begründung, BF-169) |

`npm run typecheck` und `npm run lint` ohne Befund. Im Browser nachgeprüft (Tracker-Attrappe wie in
der QA): voller Aufruf der Willkommensseite zählt „begonnen" einmal, „Zurück" aus dem Zwischenspeicher
zählt nicht erneut (`qa/12/fehlerauftrag3/bf179-bf177-browser.ausgabe.txt`). Nicht nachgestellt:
BF-178 (a) und BF-180 mit echten gleichzeitigen Anfragen — beide sind als das nachgebildet, was die
andere Anfrage in der Datenbank hinterlässt.
