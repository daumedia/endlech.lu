# 04 · Marketing-Kontakte in Brevo — Testbericht

Stand: 2026-08-29 · Geprüft gegen `spec.md` vom 2026-08-29 · Branch `feature/04-brevo-marketing-kontakte`

## Fazit

**Production-ready: nein** — *Stand der Prüfung. Die Reparatur lief am selben Tag; BF-83 bis BF-87 sind behoben und gegengeprüft, der Bericht ist entsprechend unten fortgeschrieben. Die erneute Abnahme steht aus (`/sdd-qa 04`).*

Das Feature trägt in seiner Mechanik: Der Payload an Brevo hält die Negativliste ein, der
Webhook ist eng gefasst, die Löschkaskade greift, und kein Anfrage-Ablauf spricht mit
Brevo. Drei Befunde stehen dem Deployment entgegen. **Zwei davon sind kritisch und
betreffen genau die Zusagen, um derentwillen dieses Feature einen so langen
Datenschutzteil hat:** Eine nie per Double-Opt-In bestätigte Adresse kann über einen
gewöhnlichen Verwaltungsvorgang nach Brevo gelangen (BF-83, verletzt AK-05), und der
Widerruf **einer** Quelle löscht den Kontakt einer **anderen**, weiterhin aktiven Quelle
mit derselben Adresse — samt der dort dokumentierten Einwilligung, ohne Fehleranzeige
(BF-84). Dazu kommt ein hoher Befund: Eine fehlgeschlagene Übertragung wird nie wieder
aufgegriffen (BF-86, verletzt AK-19) — ein einzelner 429 von Brevo friert einen Kontakt
dauerhaft ein.

Nächster Schritt: `/sdd-build 04` mit BF-83 bis BF-88.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 48 von 48 |
| davon bestanden | 41 |
| davon durchgefallen | 3 |
| **nicht prüfbar** | 4 |
| Edge Cases belegt | 6 von 6 |
| Tests neu geschrieben | 43 |
| Tests grün | 38 von 43 *(5 sind Befund-Nachweise und absichtlich rot)* |
| Gesamtsuite | 664 Tests, 5 rot — alle fünf sind die Reproduktionen |

## Akzeptanzkriterien im Einzelnen

### Einwilligung erteilen (US-01)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `MarketingConsentTest::testDasHaekchenIstDaUndNichtVorangehakt` — je genau eine Checkbox auf `/de/partner`, `/de/organisationen`, `/de/register` (HTTP 200) |
| AK-02 | ✅ bestanden | derselbe Test: kein `checked`-Attribut auf allen drei Seiten |
| AK-03 | ✅ bestanden | `MarketingConsentTest::testAnmeldungLaeuftOhneHaekchenDurch` (302 + Eintrag angelegt); zusätzlich laufen 14 Registrierungs- und 28 Organisationstests ohne das Feld durch |
| AK-04 | ✅ bestanden | `MarketingConsentTest::testGesetztesHaekchenHaeltDenZeitpunktFest` |
| AK-05 | ❌ durchgefallen | siehe **BF-83** — reproduziert: Statuswechsel an unbestätigtem Eintrag → Zeile `nie-doi@qa.lu / pending / partner` im Auftragsbuch |

### Übertragung nach Brevo (US-02)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-06 | ✅ bestanden | `MarketingSyncServiceTest::testAk06AuftragWirdUebertragen` — `pending` → `synced`, `syncedAt` gesetzt |
| AK-07 | ⚠️ nicht prüfbar | Der Payload trägt **genau fünf** Attribute (abgefangener Rumpf, siehe Sicherheitstabelle). Ob sie in Brevo ankommen, ist **nicht prüfbar**: Die Attribute sind im Konto nicht angelegt (Aufgabe T08 offen), und Brevo verwirft unbekannte Attribute stillschweigend — der Sync meldete Erfolg und überträgt nur die nackte Adresse |
| AK-08 | ✅ bestanden | abgefangener Rumpf enthält `"FUNNEL_STATUS":"contacted"` |
| AK-09 | ✅ bestanden | `AdminWaitlistMarketingTest::testAk09StatuswechselStelltDieUebertragungZurueck` — `synced` → `pending`, `funnelStatus` mitgezogen |
| AK-10 | ⚠️ nicht prüfbar | Der Cron-Eintrag ist in `README.md` dokumentiert, aber nicht eingerichtet (kein Produktivzugang). Die 15-Minuten-Frist lässt sich ohne laufenden Cron nicht messen |

### Rückweg: Abmelden und Löschen (US-03)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ✅ bestanden | `BrevoWebhookControllerTest::testAk11AbmeldungSperrtUndLoeschtDieEinwilligung` — `revokedAt` gesetzt **und** `marketingConsentAt` an der Quelle geleert |
| AK-12 | ✅ bestanden | `testAk12NachAbmeldungKeineErneuteEintragung` + `MarketingContactRegistryTest::testAk12UndAk45SperreUndIhreAufhebung` |
| AK-13 | ✅ bestanden | `MarketingLoeschkaskadeTest::testAk13WiderrufHinterlaesstEinenAuftragDerDieQuelleUeberlebt` — Eintrag weg (`COUNT = 0`), Auftrag lebt (`removal_pending`) |
| AK-14 | ✅ bestanden | `MarketingLoeschkaskadeTest::testAk14KontoloeschungStelltDenLoeschauftrag` |
| AK-15 | ✅ bestanden | `AdminWaitlistMarketingTest::testAk15DetailansichtZeigtDenLetztenFehler` — `HTTP 500` sichtbar; ohne Fehler steht die Zeile nicht da |
| AK-16 | ✅ bestanden | `MarketingSyncServiceTest::testGescheiterteLoeschungBehaeltDenAuftrag` — lokale Löschung bleibt, Auftrag bleibt `removal_pending` |

### Ausfall und Nachlauf

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-17 | ✅ bestanden | `MarketingSyncService` wird ausschließlich von `src/Command/MarketingSyncCommand.php` benutzt (`grep -rln`); kein Controller ruft `BrevoContactClient` |
| AK-18 | ✅ bestanden | `AdminWaitlistMarketingTest::testAk26ListeZeigtDenSyncZustand` — alle vier Zustände samt Grund |
| AK-19 | ❌ durchgefallen | siehe **BF-86** — `MarketingSyncServiceTest::testAk19FehlversuchWirdErneutAufgegriffen` ist rot: nach einem 429 greift kein Lauf die Zeile je wieder auf |
| AK-20 | ✅ bestanden | `testEc02UpsertGehtUeberExtIdUndLegtNurBeiBedarfAn` — beim zweiten Lauf nur `PUT`, kein zweites Anlegen |

### Bestandsübertragung (US-05)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-21 | ✅ bestanden | `MarketingImportCommandTest::testAk21TrockenlaufSchreibtNichts`; zusätzlich real: `SELECT COUNT(*) FROM marketing_contact` vor **0**, nach dem Trockenlauf **0** |
| AK-22 | ✅ bestanden | `testAk22CommitUebertraegtDieAngezeigtenEintraege` |
| AK-23 | ✅ bestanden | `testAk23AuswahlregelIstEng` — Konto, unbestätigter und einwilligungsfreier Eintrag bleiben draußen; `--help` zeigt **nur** `--commit`, keine aufweichende Option |
| AK-24 | ⚠️ nicht prüfbar | Textentwurf liegt in `erste-kampagne.md` mit Herkunftssatz und Abmeldelink; die Kampagne ist in Brevo **nicht angelegt** (kein Kontozugang genutzt) |
| AK-25 | ✅ bestanden | `testAk25ZweiterLaufErzeugtKeineDubletten` + Unique-Index `UNIQ_E78FBDB7E7927C74` (`SHOW INDEX`) |

### Sichtbarkeit (US-04)

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-26 | ✅ bestanden | `AdminWaitlistMarketingTest::testAk26ListeZeigtDenSyncZustand` |
| AK-27 | ⚠️ nicht prüfbar | Die lokale Hälfte steht: `testAk27ZaehlungStehtInDerKopfzeile`, gemessen „3 eingewilligt · 1 übertragen". Die **Gegenprobe in Brevo** ist ohne Kontozugang nicht möglich — genau das nennt `design.md` einen QA-Nachweis und keine Automatik |

### Datenschutz und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-28 | ✅ bestanden | abgefangener Rumpf (siehe Sicherheitstabelle) — genau die sechs zugesagten Angaben |
| AK-29 | ✅ bestanden | Negativlistenprüfung am **tatsächlichen** Rumpf: `message`, `phone`, `locality`, `source`, `token` — keiner enthalten |
| AK-30 | ✅ bestanden | `"ORIGIN":"PARTNER"`; `MarketingOrigin` kennt nur Vertriebsrollen |
| AK-31 | ✅ bestanden | `MarketingSyncServiceTest::testAk31FehlertextTraegtKeineAntwortUndKeineAdresse` (Antwort enthielt Adresse **und** Schlüssel, `last_error` = `HTTP 400`); `MarketingImportCommandTest::testAk31AusgabeMaskiertDieAdressen`; in `prod` ist der `doctrine`-Kanal ausgeschlossen (`monolog.yaml`) |
| AK-32 | ✅ bestanden | `curl /de|/en|/fr|/lb/legal` → je 4 Brevo-Treffer, Abschnitt „Newsletter und Kampagnen (Brevo)" mit „dauerhaft und zu Werbezwecken" |
| AK-33 | ❌ durchgefallen | siehe **BF-88** — `docs/datenschutz.md` existiert (5659 Bytes) und nennt den Sitz, aber der AV-Vertrag ist **nicht** festgehalten, sondern als „noch zu prüfen" markiert; das Datum fehlt |
| AK-34 | ✅ bestanden | Die Reihenfolge ist eingehalten: kein Kontakt ist hinausgegangen. `BREVO_API_KEY` leer, `app:marketing:sync` endet mit Exit 1, `marketing_contact` in dev leer |
| AK-35 | ✅ bestanden | `AdminWaitlistMarketingTest::testAk35NutzerOhneRolleSiehtDieSyncAnsichtNicht` → 403; anonym → 302 |
| AK-36 | ✅ bestanden | `app:marketing:import` hat keine Route (`debug:router` zeigt keine); Auswahlregel im Befehl, `--help` ohne aufweichende Option |
| AK-37 | ✅ bestanden | `/de/admin/warteliste/partner/1` und `.../organisation/1` anonym → je 302 |
| AK-38 | ✅ bestanden | `access_control` `^/[a-z]{2}/admin` → ROLE_ADMIN; ein Nutzer sieht nur die Checkbox |
| AK-39 | ✅ bestanden | `testAk39DeckelJeLaufGreift` (5 offen, Deckel 2 → 2 Aufrufe) und `testAk39MindestabstandWirdEingehalten` (3 Kontakte × 120 ms → ≥ 240 ms gemessen); Webhook-Limiter real überrannt: 120 × 401, ab dem **121.** → 429 |
| AK-40 | ✅ bestanden | `git diff config/packages/framework.yaml` enthält keine Änderung an `partner_waitlist`, `organisation_waitlist`, `registration` |
| AK-41 | ✅ bestanden | trifft nicht zu — ausdrücklich geprüft: kein `UploadedFile`, kein Dateifeld in `src/Marketing/`, `src/Controller/Marketing/`, den drei FormTypes |
| AK-42 | ✅ bestanden | trifft nicht zu — kein Kampagnen-Auslöser im Code; `BrevoContactClient` kennt nur `/contacts` |
| AK-43 | ✅ bestanden | siehe AK-13 und AK-14 |
| AK-44 | ✅ bestanden | `MarketingConsentTest::testDatenexportNenntDieEinwilligung` — `marketingConsent` und `marketingConsentAt` im Export |
| AK-45 | ✅ bestanden | `MarketingContactRegistryTest::testAk12UndAk45SperreUndIhreAufhebung` + `MarketingLoeschkaskadeTest::testAk45AdresseIstNachWiderrufWiederFrei` |
| AK-46 | ✅ bestanden | `.env` führt alle drei Werte leer; `git log -S 'xkeysib'` → 0 Treffer; keine Schlüssel im Quelltext |
| AK-47 | ✅ bestanden | `MarketingSyncServiceTest::testAk47OhneSchluesselPassiertNichts` — kein Aufruf, Auftrag unverändert `pending`; real: Exit 1 mit Hinweis |
| AK-48 | ✅ bestanden | Kein Treffer auf `BREVO_`, `brevo_api_key`, `api-key` im HTML von `/de/partner`, `/de/organisationen`, `/de/register`, `/de/legal`, `/de/admin/warteliste`; kein Treffer in `public/build/` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `MarketingContactRegistryTest::testEc01ZweiQuellenEineAdresseErgebenEinenKontakt` — auch bei abweichender Schreibweise `COUNT = 1`. ⚠️ Die **Löschseite** dieses Falls ist fehlerhaft, siehe BF-84 |
| EC-02 | ✅ bestanden | `testEc02AdresswechselFuehrtDieselbeZeileFort` (gleiche `ext_id`) + `testEc02UpsertGehtUeberExtId...` (`PUT …/{id}?identifierType=ext_id`) |
| EC-03 | ✅ bestanden | `testAk05UnbestaetigteQuelleErzeugtKeinenAuftrag` |
| EC-04 | ✅ bestanden | `testAk13UndEc04Loeschauftrag` (unbekannte Adresse folgenlos) + `testEc04BereitsGeloeschterKontaktGiltAlsErledigt` (404 = Erfolg) |
| EC-05 | ✅ bestanden | `emailBlacklisted` in **keinem** der beiden abgefangenen Rümpfe |
| EC-06 | ✅ bestanden | `MarketingConsentTest::testHaekchenUeberlebtEinUngueltigesFormular` — nach 422 ist `checked` gesetzt |

## Sicherheitsprüfung

Aktiv angegriffen, nicht gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| 1 · Zugriff auf fremde ID (IDOR) | bestanden | Admin-Detailrouten anonym: `/de/admin/warteliste/partner/1` → **302**, `.../organisation/1` → **302**; als `ROLE_USER` → **403**. Ein Nutzer besitzt strukturell keine eigene `marketing_contact`-Zeile |
| 2 · Zugriffsregeln serverseitig | bestanden | Kein RLS im Projekt; geprüft wurde die Anwendungsschicht: `^/[a-z]{2}/admin` greift, der sprachfreie Webhook hat eine **eigene** `access_control`-Zeile (ohne sie fiele er durch alle Regeln — BF-18-Muster) |
| 3 · Rate Limit greift | bestanden | Webhook real überrannt: Aufrufe 1–120 → **401**, ab **121 → 429**. Grenzwert exakt. Bestehende Formular-Limiter unverändert |
| 4 · PII in Protokollen | bestanden | Feature-Code loggt nur `contact_id`, `state`, `attempts`, `reason`. Adressen erscheinen in `var/log/dev.log` ausschließlich über Doctrines SQL-Parameter — der `doctrine`-Kanal ist in `prod` ausgeschlossen (`monolog.yaml`, dokumentiert unter BF-23). Kein `api-key`-Treffer in den Logs |
| 5 · PII an externe Dienste | bestanden | **Abgefangener Rumpf** (nicht der Quelltext): `PUT /v3/contacts/10?identifierType=ext_id` → `{"attributes":{"CONTACT_NAME":"Marco Rossi","ORGANISATION":"Pizzeria Bella Vista","LOCALE":"fr","ORIGIN":"PARTNER","FUNNEL_STATUS":"contacted","EMAIL":"chef@bella-vista.lu"},"listIds":[7]}`. Negativliste geprüft: `message`, `phone`, `locality`, `source`, `token`, `emailBlacklisted` — **keiner enthalten** |
| 6 · Geheimnisse im Repository | bestanden | `.env`: `BREVO_API_KEY=`, `BREVO_LIST_ID=`, `BREVO_WEBHOOK_TOKEN=` (alle leer). `git log -p --all -S 'xkeysib'` → 0 Treffer. Kein Brevo-Bezug in `public/build/` |
| 7 · Eingaben | bestanden | Webhook mit SQL-Injection, `<script>`, `email: null`, „kein json", `[]`, fehlenden Feldern: durchweg **200**, kein Serverfehler (`testKaputteRuempfeErzeugenKeinenServerfehler`). Untergeschobene Felder (`origin`, `sync_state`, `attempts`, `id`) wirken **nicht** (`testUntergeschobeneFelderWirkenNicht`) |
| 8 · Löschen | **BF-84** | Kontolöschung und Wartelisten-Widerruf stellen den Löschauftrag korrekt (AK-13/14 grün). Aber: Bei zwei Quellen mit derselben Adresse nimmt der Widerruf **einer** Quelle die andere mit — siehe BF-84 |

Zusätzlich geprüft: Ein **leeres** Webhook-Geheimnis lehnt jede Anfrage ab (`testLeeresGeheimnisLehntAllesAb`) — „still aus" heißt beim Empfangen, dass nichts hereinkommt. Der Vergleich läuft über `hash_equals()`.

## Fehler

### BF-83 · Verwaltungs-Statuswechsel befördert eine nie bestätigte Adresse nach Brevo — kritisch

**Betrifft:** AK-05, EC-03, Decision Log #3
**Reproduktion:**
1. Partner-Formular absenden, Werbe-Häkchen setzen, Bestätigungsmail **nicht** anklicken
   (`confirmedAt = null`, `marketingConsentAt` gesetzt)
2. Als Admin `/de/admin/warteliste/partner/{id}` öffnen
3. Status auf „Kontaktiert" setzen — der übliche Vorgang nach einem Telefonat
**Erwartet:** Keine Zeile im Auftragsbuch. Wer den Double-Opt-In nie abschloss, hat nie
belegt, dass die Adresse ihm gehört.
**Tatsächlich:** `SELECT … FROM marketing_contact` liefert `nie-doi@qa.lu / pending / partner`.
Der nächste Sync-Lauf trägt die Adresse bei Brevo ein.
**Ort:** `src/Controller/AdminWaitlistController.php` — der Block, der `confirmedAt`
nachsetzt, steht **vor** `recordWaitlistEntry()`; `MarketingContactRegistry::recordWaitlistEntry()`
prüft `isConfirmed()`, was nach dem Nachsetzen `true` ist.
**Nachweis:** `MarketingBefundeTest::testBf83…` (rot)
**Vorschlag:** Vor dem Nachsetzen merken, ob der Eintrag bereits bestätigt **war**, und die
Registry nur dann rufen.

> **Behoben 2026-08-29.** `applyStatus()` hält den Bestätigungsstand in
> `$warBereitsBestaetigt` fest, **bevor** der Backfill läuft, und ruft die Registry nur
> dann. Der Bestandsvorgang selbst (telefonisch geführte Kontakte weitersetzen) läuft
> unverändert. Gegenprobe über die echte Route: 0 Kontakte im Auftragsbuch, Status
> trotzdem `contacted`. Regression: `AdminWaitlistMarketingTest::testBf83…` (grün).

### BF-84 · Widerruf einer Quelle löscht den Kontakt einer anderen, aktiven Quelle — kritisch

**Betrifft:** EC-01, AK-13, AK-44
**Reproduktion (rein sequenziell):**
1. Dieselbe Adresse auf der Partner-Warteliste **und** als Nutzerkonto eintragen, beide
   mit Werbe-Einwilligung, beide bestätigt (das ist EC-01 und im Alltag plausibel: ein
   Restaurantbesitzer mit persönlichem Konto)
2. Nur die Partner-Warteliste über ihren Abmeldelink widerrufen
**Erwartet:** Der Brevo-Kontakt bleibt — das Konto hat nicht widerrufen.
**Tatsächlich:** Die geteilte Zeile geht auf `removal_pending`; der nächste Lauf löscht den
Kontakt bei Brevo und räumt die Zeile ab. Das Konto meldet weiterhin
`hasMarketingConsent() = true`, seine Einwilligung ist aber wirkungslos — ohne
Fehleranzeige. Gemessen: Zeile `removal_pending`, Konto-Einwilligung `true`.
**Verstärker (eigenständig belegt):** Brevo löst `contactDeleted` auch bei einer Löschung
über die API aus — also durch unseren eigenen Aufruf. Das Ereignis steht in
`BLOCKING_EVENTS` und führt zu `blockByEmail()`, das `marketingConsentAt` an **allen**
Quellen leert. Gemessen: Konto-Einwilligung vor dem Echo `true`, danach `false`. Damit
verschwindet ein Nachweis nach Art. 7 Abs. 1 DSGVO, den niemand widerrufen hat.
**Ort:** `src/Marketing/MarketingContactRegistry.php` — `scheduleRemoval()` und
`blockByEmail()` kennen die auslösende Quelle nicht und wirken auf die geteilte Zeile.
**Nachweis:** `MarketingBefundeTest::testBf84…` und `::testBf84b…` (beide rot)
**Vorschlag:** Vor dem Löschauftrag prüfen, ob unter derselben Adresse eine andere Quelle
mit gültiger Einwilligung steht (`sourcesFor()` liefert die Abfrage bereits) — dann nur
die auslösende Quelle austragen. Für `contactDeleted` klären, ob es als Auslöser taugt,
wenn die Löschung selbst verursacht war.

> **Behoben 2026-08-29.** `scheduleRemoval()` nimmt jetzt die **auslösende Quelle**
> entgegen und zählt über `aktiveQuellen()`, ob eine andere mit gültiger Einwilligung
> übrig bleibt. Wenn ja, wird die Zeile auf jene Quelle umgeschrieben (Herkunft, Namen,
> Vertriebsstatus neu abgeleitet, Zustand `pending`) statt gelöscht. Die beiden Aufrufer
> geben ihre Quelle mit; `changeEmail()` bewusst nicht — dort trägt der Nutzer schon die
> neue Adresse.
>
> **BF-84b** doppelt abgesichert: `blockByEmail()` steigt aus, wenn **keine Zeile** mehr
> existiert (dann war die Löschung unsere eigene), und `contactDeleted` steht nicht mehr
> in den Ereignissen, die die Einwilligung an der Quelle entwerten — eine gelöschte
> Karteikarte bei Brevo sagt nichts über den Willen des Menschen aus. Gegenprobe über
> den echten Widerrufslink: Zeile bleibt (`pending/account`), Konto-Einwilligung steht,
> Wartelisten-Eintrag ist gelöscht. Regression: `MarketingBefundeTest::testBf84…`,
> `::testBf84b…` (grün).

### BF-86 · Eine fehlgeschlagene Übertragung wird nie wieder aufgegriffen — hoch

**Betrifft:** AK-19
**Reproduktion:**
1. Einen Auftrag anlegen, den Sync-Lauf gegen eine 429-Antwort laufen lassen
2. Den Lauf erneut starten, diesmal mit erfolgreicher Antwort
**Erwartet:** Der zweite Lauf holt die Zeile nach — „ohne dass jemand sie von Hand
anstoßen muss".
**Tatsächlich:** Der zweite Lauf überträgt **nichts** (`synced = 0`). `markFailed()` setzt
`syncState = FAILED`, aber `findOpenForSync()` fragt nur `PENDING` und `REMOVAL_PENDING`
ab. Ein einzelner 429 friert den Kontakt dauerhaft ein.
**Ort:** `src/Repository/MarketingContactRepository.php` (`findOpenForSync`) gegen
`src/Entity/MarketingContact.php` (`markFailed`). ⚠️ Der Kommentar in
`src/Enum/MarketingSyncState.php` behauptet das Gegenteil: „Der Sync-Lauf greift eine
fehlgeschlagene Zeile über ihren Versuchszähler wieder auf, nicht über den Zustand." Das
tut er nicht.
**Nachweis:** `MarketingSyncServiceTest::testAk19FehlversuchWirdErneutAufgegriffen` (rot)
**Vorschlag:** `FAILED` in die Zustandsliste von `findOpenForSync()` aufnehmen — der
Rückzug greift ohnehin über `attempts < MAX_ATTEMPTS`.

> **Behoben 2026-08-29.** `FAILED` steht in der Zustandsliste; der Rückzug bleibt allein
> beim Versuchszähler. Der irreführende Kommentar in `MarketingSyncState::isOpen()` ist
> korrigiert und trägt jetzt den Hinweis, dass beide Stellen zusammen geändert werden
> müssen. Regression: `MarketingSyncServiceTest::testAk19…` (grün).

### BF-85 · `record()` ist ohne zwischenzeitliches `flush()` nicht kollisionsfrei — mittel

**Betrifft:** EC-01 (Datenintegrität)
**Reproduktion:** Zwei `MarketingContactRegistry::record()`-Aufrufe für dieselbe Adresse
ohne `flush()` dazwischen, danach `flush()`.
**Erwartet:** Eine Zeile.
**Tatsächlich:** `UniqueConstraintViolationException: Duplicate entry … for key
'marketing_contact.UNIQ_E78FBDB7E7927C74'`. `findOneByEmail()` sieht die vorgemerkte,
noch nicht geschriebene Zeile nicht.
**Heutige Wirkung:** Kein Anwendungspfad trifft das — jeder Aufrufer flusht sofort. Aber
`MarketingImportCommand` musste deshalb bereits von Hand entdoppeln, und dieselbe Mechanik
greift bei echter Nebenläufigkeit (zwei Bestätigungen derselben Adresse in
überlappenden Requests). Dann scheitert der gemeinsame `flush()` und rollt **die
Bestätigung selbst** mit zurück — der Nutzer sähe eine Fehlerseite statt „Bestätigt".
**Ort:** `src/Marketing/MarketingContactRegistry.php` (`record()`)
**Nachweis:** `MarketingBefundeTest::testBf85…` (rot, wirft die Unique-Verletzung)
**Vorschlag:** Kollision abfangen und als Aktualisierung behandeln, oder den
Marketing-Teil in einem eigenen, vom Hauptvorgang entkoppelten `flush()` führen.

> **Behoben 2026-08-29 — der belegte Fall.** Die Registry führt eine eigene Merkliste
> der in diesem Vorgang angelegten Zeilen; `finde()` sucht erst dort, dann in der
> Datenbank, und verwirft einen Eintrag, den der EntityManager nach einem `clear()`
> nicht mehr führt. Zwei `record()`-Aufrufe ohne `flush()` ergeben jetzt eine Zeile.
> Regression: `MarketingBefundeTest::testBf85…` (grün).
>
> ⚠ **Die echte Nebenläufigkeit bleibt offen.** Zwei *parallele Requests* lesen beide
> vor dem jeweils anderen Commit und kollidieren weiterhin am Unique-Index. Der Fix
> dafür wäre ein entkoppelter `flush()` und damit ein Eingriff in die
> Transaktionsführung von Feature `01` — das geht über diesen Auftrag hinaus und ist
> als **OF-09** in `spec.md` vermerkt.

### BF-87 · Sync-Lauf committet bis zu 200 Zeilen in einer Transaktion — mittel

**Betrifft:** AK-19 (Nachlauf)
**Ort:** `src/Marketing/MarketingSyncService.php` — `flush()` steht außerhalb der Schleife.
**Wirkung:** Scheitert dieser eine `flush()` (Lock-Timeout, Deadlock), gehen die
Zustandsänderungen aller bereits erfolgreich übertragenen Zeilen verloren. Wegen der
Idempotenz von `upsert`/`delete` kein Korrektheitsproblem, aber unnötige Zusatzlast auf
Brevo und eine Verzögerung, bis `lastError` in der Verwaltung sichtbar wird.
**Nicht reproduziert** — der Auslöser (Flush-Fehler bei reinen UPDATE/DELETE-by-PK) ist
selten; der Befund stammt aus dem Code-Review und ist an der Zeile festgemacht.
**Vorschlag:** Den finalen `flush()` in einen `try/catch` nehmen und den Lauf mit klarer
Meldung beenden, statt mit einer unbehandelten Ausnahme.

> **Behoben 2026-08-29.** Der `flush()` liegt in einem `try/catch`; ein Fehlschlag
> protokolliert Fehlerklasse und Zahl der betroffenen Zeilen (keine Adressen) und meldet
> sie im Ergebnis als fehlgeschlagen, statt den Cron-Lauf mit einer unbehandelten
> Ausnahme abzubrechen. Die Bündelung selbst bleibt — sie ist wegen der Idempotenz
> unkritisch, und ein `flush()` je Kontakt kostete 200 Round-Trips.

### BF-88 · Der AV-Vertrag mit Brevo ist nicht festgehalten — mittel

**Betrifft:** AK-33, AK-34
**Erwartet:** In `docs/datenschutz.md` steht der Auftragsverarbeitungsvertrag mit Sitz des
Anbieters **und Datum**.
**Tatsächlich:** Die Datei existiert und nennt den Sitz (Brevo SA, Frankreich), führt den
AV-Vertrag aber als „⚠ noch zu prüfen und zu datieren"; das Prüfdatum fehlt. Zusätzlich
offen: **OF-01** (die Datenschutzstufe, die das PRD nie festgelegt hat).
**Einordnung:** Kein Softwarefehler, sondern eine offene organisatorische Aufgabe. Sie war
im Abschlussbericht des Baus als T36/T39 gemeldet und blockiert den ersten echten Lauf
ohnehin über die Freigabe-Sperre. Sie steht hier, weil AK-33 ein Kriterium ist und ein
Bericht, der es stillschweigend übergeht, den Stand beschönigt.
**Vorschlag:** Betreiberentscheidung zu OF-01, dann AV-Vertrag prüfen und datieren.

## Hinweise (keine Befunde dieses Features)

- ⚠️ **Das Organisations-Formular ist auf den drei Zielgruppenseiten nicht absendbar.**
  Gemessen: `POST /de/organisationen/{gemeinden|unternehmen|vereine}` → je **405**, weil
  `form_start()` kein `action` setzt und `app_organisations_type` nur GET führt. Die
  Übersicht funktioniert (422 bei unvollständigen Daten). Das ist ein **vorbestehender
  B15-Fehler**, steht als **OF-07** in der Spec — aber er macht die neue
  Einwilligungs-Checkbox dort faktisch unerreichbar. AK-01 gilt trotzdem als bestanden:
  Das Feld ist vorhanden und korrekt gerendert.
- `lint:container` ist projektweit vorbestehend rot (Webauthn-Alias-Altlast) und taugt
  nicht als Gate; `make fix-check` existiert nicht, PHP-CS-Fixer ist nicht installiert.
  Der Code-Style ist damit ungeprüft.
- Im Payload trägt der Aktualisierungsweg ein sechstes Attribut `EMAIL`. Das ist die
  Adresse selbst und im Entwurf ausdrücklich so vorgesehen (EC-02) — kein Zusatzmerkmal
  im Sinne von AK-07.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Integration/Marketing/MarketingContactRegistryTest.php` | 9 | AK-05, AK-12, AK-13, AK-19, AK-45, EC-01, EC-02, EC-04 |
| `tests/Integration/Marketing/MarketingSyncServiceTest.php` | 10 | AK-06, AK-13, AK-19, AK-20, AK-31, AK-39, AK-47, EC-02, EC-04 |
| `tests/Integration/Marketing/BrevoWebhookControllerTest.php` | 7 | AK-11, AK-12, Anti-Enumeration, Eingabefestigkeit, leeres Geheimnis |
| `tests/Integration/Marketing/MarketingLoeschkaskadeTest.php` | 3 | AK-13, AK-14, AK-45 |
| `tests/Integration/Command/MarketingImportCommandTest.php` | 5 | AK-21, AK-22, AK-23, AK-25, AK-31 |
| `tests/Functional/Controller/AdminWaitlistMarketingTest.php` | 5 | AK-09, AK-15, AK-18, AK-26, AK-27, AK-35, AK-38 |
| `tests/Integration/Marketing/MarketingBefundeTest.php` | 4 | **Reproduktionen** zu BF-83, BF-84, BF-84b, BF-85 — absichtlich rot |

## Nächster Schritt

`/sdd-build 04` mit dem Auftrag, **BF-83, BF-84, BF-86** (deployment-blockierend) sowie
**BF-85, BF-87** zu beheben. **BF-88** braucht zuerst eine Betreiberentscheidung zu OF-01.
Danach erneut `/sdd-qa 04`.

Die fünf roten Tests sind die Abnahmebedingung: Sie werden grün, wenn die Ursachen behoben
sind — nicht, wenn die Erwartung angepasst wird.
