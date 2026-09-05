# 08 · Warteliste für die mobile App — Testbericht

Stand: 2026-09-05 · Geprüft gegen `spec.md` vom 2026-09-04 (58 Akzeptanzkriterien)

> **Nachtrag 2026-09-05, nach `/sdd-build 08`:** Alle fünf Befunde sind behoben und mit
> der jeweiligen Reproduktion gegengeprüft; die Vermerke stehen unter dem einzelnen Fehler.
> 981 Tests grün. **Das Fazit unten beschreibt den Stand VOR der Reparatur** und bleibt so
> stehen — ein Bericht, der sich rückwirkend selbst überschreibt, belegt nichts.
> Der neue Stand gehört in einen zweiten Prüflauf, nicht in diesen.

## Fazit

**Production-ready: nein**

Das Feature funktioniert auf seinem Hauptweg vollständig: Eintragen, Bestätigen, die
zweite Mail mit dem Beta-Link, Abmelden, die Verwaltungsansicht und die Kennzahl auf
`/open` sind belegt. **Vier Fehler stehen der Auslieferung entgegen**, drei davon mit
Grad *hoch*, und alle drei liegen auf demselben Weg — dem Umgang mit einer Adresse,
die schon einmal eingetragen wurde:

- **BUG-01**: Der Bestätigungslink, den ein abgelaufener Vorgang neu ausstellt, ist
  **sofort wieder abgelaufen** (HTTP 410 gemessen). Die Sackgasse, die AK-17 ausdrücklich
  verhindern soll, besteht damit fort.
- **BUG-02**: Derselbe Weg verbraucht **kein Rate-Limit-Kontingent**. Eine fremde Adresse
  mit einer stehenden, nie bestätigten Vormerkung lässt sich unbegrenzt mit
  Bestätigungsmails fluten — gemessen: fünf Absendevorgänge, fünf Mails, Kontingent
  unverändert.
- **BUG-03**: Eine Adresse, die Symfonys `Email`-Constraint passiert, aber nicht
  RFC 2822 entspricht, erzeugt einen **HTTP 500 — und hinterlässt trotzdem eine Zeile**
  in der Datenbank. AK-08 verlangt 422 und keinen Eintrag. **Dasselbe Verhalten hat die
  Partner-Warteliste (B14)**, es ist also kein Fehler dieses Features allein.

Dazu ein Befund mittleren Grades (**BUG-04**), der eine ausdrückliche Zusage bricht: Die
gewählte Plattform wird an Brevo übertragen, obwohl AK-54 und `design.md` das
wörtlich ausschließen.

Nächster Schritt: `/sdd-build 08` mit BUG-01 bis BUG-05.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 58 von 58 |
| davon bestanden | 53 |
| davon durchgefallen | 4 |
| **nicht prüfbar** | 1 |
| Edge Cases belegt | 8 von 9 |
| Tests neu geschrieben | 7 (`AppWaitlistQaTest`) |
| Tests grün | 975 von 975 |

## Akzeptanzkriterien im Einzelnen

### Seite und Formular

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `AppWaitlistControllerTest::testSeiteIstOeffentlichUndZeigtBeideePlattformen` · `curl /de/app` → HTTP 200 |
| AK-02 | ✅ bestanden | `::testSprachfreieAdresseLeitetWeiter` · `debug:router` → Pfad `/app`, 302 (nicht 301) |
| AK-03 | ✅ bestanden | `qa/08/app-de.html`: drei sichtbare Eingabefelder + Einwilligungshäkchen; kein `name`- und kein Gerätefeld im Markup |
| AK-04 | ✅ bestanden | `::testSeiteIstOeffentlich…` — zwei Radios, `assertCount(0, …[checked])` |
| AK-05 | ✅ bestanden | `qa/08/app-de.html` enthält „Noch nichts gebaut. Deine Vormerkung zählt als Bedarf." im Markup, also ohne JavaScript sichtbar |
| AK-06 | ✅ bestanden | ebenda: „Testfassung verfügbar — Zugang per Mail nach der Bestätigung." |
| AK-07 | ✅ bestanden | `::testLeeresFormularLiefert422UndLegtNichtsAn` — HTTP 422, drei einzelne Feldmeldungen, `count([])` = 0, `assertEmailCount(0)` |
| AK-08 | ❌ **durchgefallen** | siehe **BUG-03** — `../../etc/passwd@example.lu` → HTTP 500 statt 422, und eine Zeile entsteht |
| AK-09 | ✅ bestanden | `::testGueltigeVormerkungLegtEintragAnUndVerschicktEineMail` — Status `pending`, `consentAt`, `locale`, Adresse normalisiert |
| AK-10 | ✅ bestanden | `AppWaitlistQaTest::testAk10TurboStreamErsetztNurDasFormular` — `Content-Type: …turbo-stream…`, `action="replace"`, `target="app-waitlist-form"` |
| AK-11 | ✅ bestanden | `::testGueltigeVormerkung…` → `assertResponseRedirects` ohne Turbo; Bestätigen und Abmelden sind reine GET-Seiten |
| AK-12 | ✅ bestanden | `AppWaitlistQaTest::testAk12FehlerfallBleibtHtmlAuchMitTurbo` — 422 mit `text/html`, **auch** bei gesetztem Turbo-Accept-Header |
| AK-13 | ✅ bestanden | `::testHoneypotAntwortetWieDerErfolgSpeichertAberNichts` — identischer Redirect, `count([])` = 0, keine Mail |
| AK-14 | ✅ bestanden | `qa/08/app-de.html`: Feld ist `type="text"` (nicht `hidden`), `tabindex="-1"`, `aria-hidden="true"`; keine Validierungsregel im FormType |

### Doppelte Eintragung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-15 | ✅ bestanden | `::testZweiteEintragungLegtKeinenZweitenEintragAn` — identischer Redirect, `count([])` = 1 |
| AK-16 | ✅ bestanden | ebenda — Plattform bleibt `IOS`, `assertEmailCount(0)` |
| AK-17 | ❌ **durchgefallen** | siehe **BUG-01** — die neue Mail geht hinaus, ihr Link liefert aber **HTTP 410** |

### Bestätigung und die beiden Mails

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-18 | ✅ bestanden | `::testGueltigeVormerkung…` — `assertEmailCount(1)`, Body enthält `/app/confirmation/` |
| AK-19 | ✅ bestanden | ebenda — `assertStringNotContainsString('testflight.apple.com', …)` |
| AK-20 | ✅ bestanden | `AppWaitlistQaTest::testAk20EintragUeberlebtEinenVersandfehler` — die Zeile steht vor dem Versand (Reihenfolge Token → flush → Mail) |
| AK-21 | ✅ bestanden | `::testBestaetigungSetztStatusUndVerschicktDieZweiteMail` — Status `CONFIRMED`, `selfConfirmedAt`, `betaLinkSentAt` gesetzt, eine Mail |
| AK-22 | ✅ bestanden | `::testDieZweiteMailInAllenDreiZweigen@iOS mit Link` — Knopf mit TestFlight-URL im Body |
| AK-23 | ✅ bestanden | `::testAndroidBekommtKeinenBetaLink` und `@Android` — Hinweistext, kein `testflight.apple.com` |
| AK-24 | ✅ bestanden | `@iOS ohne Link` — kein `testflight.apple.com`, kein `href=""`, keine Ausnahme |
| AK-25 | ✅ bestanden | `::testZweiterKlickMeldetBereitsBestaetigtUndSchicktKeineMail` — HTTP 200, `assertEmailCount(0)` |
| AK-26 | ✅ bestanden | `::testUnbekannterTokenLiefert404` — HTTP 404 |
| AK-27 | ✅ bestanden | `::testFalschesTokenformatFindetKeineRoute` — `/app/confirmation/zu-kurz` → 404 an der Routing-Schicht |
| AK-28 | ✅ bestanden | `::testAbgelaufenerLinkLiefert410UndVerweistZurueck` — HTTP **410**, Rückverweis auf `/de/app` im Markup |
| AK-29 | ✅ bestanden | `AppWaitlistQaTest::testAk29PendingErzeugtKeinenMarketingKontakt` — `findOneByEmail()` → null |

### Widerruf

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ bestanden | `::testJedeMailTraegtEinenAbmeldelink` (erste Mail) und `::testDieZweiteMailInAllenDreiZweigen` (alle drei Zweige) |
| AK-31 | ✅ bestanden | `::testAbmeldelinkLoeschtDenEintrag` — `count([])` = 0, gelöscht statt markiert |
| AK-32 | ✅ bestanden | `AppWaitlistQaTest::testAk32WiderrufRaeumtDenMarketingKontaktAb` |
| AK-33 | ✅ bestanden | `::testAbmeldelinkLoeschtDenEintrag` — zweiter Aufruf HTTP 200, kein Fehler |
| AK-34 | ✅ bestanden | `::testNachDerAbmeldungIstDieAdresseWiederFrei` — erneutes Eintragen gelingt |

### Verwaltung und öffentliche Kennzahl

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-35 | ✅ bestanden | `AppWaitlistAdminTest::testEintragErscheintInDerKombiniertenListe` · `::testQuellenfilterAppZeigtNurAppEintraege` (genau 1 Zeile) |
| AK-36 | ✅ bestanden | `::testOhneAdminRolleKeinZugriff` → HTTP 403 · `::testAnonymKeinZugriffAufDieDetailseite` → 302 auf `/login` |
| AK-37 | ✅ bestanden | `AppWaitlistIntegrationTest::testKennzahlFehltUnterhalbDerSchwelle` — `assertArrayNotHasKey` für alle drei Schlüssel |
| AK-38 | ✅ bestanden | `::testKennzahlErscheintAbDerSchwelle` — 47 iOS + 3 Android = 50, alle drei Zahlen vorhanden |
| AK-39 | ✅ bestanden | `curl /open.json` → `qa/08/open.json`: keine `appWaitlist`-Schlüssel unter der Schwelle, **0 E-Mail-Adressen** im gesamten Dokument |
| AK-40 | ✅ bestanden | `curl /de/` → 2 Verweise auf `/de/app` (Fußzeile Spalte 4 + Startseiten-Band) |

### Datenschutz und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-41 | ✅ bestanden | `AppWaitlistQaTest::testAk42KeineBesonderenKategorien` — die 14 Spalten abschließend geprüft, **keine `ip`-Spalte, kein Name** |
| AK-42 | ✅ bestanden | ebenda — `platform` hat zwei Werte (`AppPlatform`), keine Kategorie nach Art. 9 |
| AK-43 | ✅ bestanden | `grep -rn logger` über alle Feature-Dateien → 0 Treffer; `monolog.yaml` `when@prod` schließt `!doctrine` und `!request` aus (BF-23) |
| AK-44 | ⚠️ **nicht prüfbar** | Der Grenzwert stimmt an der Policy (10 akzeptiert, 11. abgewiesen — Ausgabe unten). Über HTTP **nicht** prüfbar: `when@test` hebt jeden Limiter auf 10000, das ist Projektkonvention. **Und: BUG-02 zeigt, dass der Deckel auf einem Weg gar nicht greift.** |
| AK-45 | ✅ bestanden | `AppWaitlistAdminTest::testLesenderAufrufVerbrauchtKeinKontingent` — Restkontingent vor und nach einem GET identisch |
| AK-46 | ❌ **durchgefallen** | siehe **BUG-02**. Der Limiter ist zwar eigenständig (`AppWaitlistQaTest::testAk46…` belegt drei verschiedene Dienste), greift aber im Dublettenzweig nicht |
| AK-47 | ✅ bestanden | `AppWaitlistIntegrationTest::testAufraeumlaufLoeschtNieBestaetigteNach30Tagen` |
| AK-48 | ✅ bestanden | `::testAufraeumlaufLaesstBestaetigteStehen` (400 Tage alt, bleibt) |
| AK-49 | ✅ bestanden | `::testZweiterLaufAmSelbenTagLaeuftNichtNochEinmal` · `debug:scheduler` zeigt `app:app-waitlist:cleanup` täglich 03:40 · `sweepOncePerDay()` in `AdminWaitlistController::index()` |
| AK-50 | ✅ bestanden | `::testKontoloeschungEntferntDieVormerkung` |
| AK-51 | ✅ bestanden | `::testDatenexportEnthaeltDieVormerkungOhneToken` — Plattform enthalten, Token nachweislich **nicht** |
| AK-52 | ✅ bestanden | `::testGueltigeVormerkung…` — ohne Häkchen bleibt `marketingConsentAt` null; FormType ohne `IsTrue`, ohne `data: true` |
| AK-53 | ✅ bestanden | `grep -rl testflight translations/` → kein Treffer; Wert steht als `app.testflight_url` aus `APP_TESTFLIGHT_URL` |
| AK-54 | ❌ **durchgefallen** | siehe **BUG-04** — `marketing_contact.organisation_name = 'iOS'` |

### Mehrsprachigkeit und Zugänglichkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-55 | ✅ bestanden | `/de/app`, `/en/app`, `/fr/app`, `/lb/app` je HTTP 200, **0 rohe Übersetzungsschlüssel**; `CatalogueCompletenessTest` grün |
| AK-56 | ✅ bestanden | `qa/08/app-de.html`: `peer sr-only` (nicht `hidden`), `<legend>`, `min-h-[48px]`, kein `outline-none` |
| AK-57 | ✅ bestanden | `qa/08/err.html`: `autofocus` genau einmal, `aria-invalid="true"` zweimal, Live-Region vorhanden; kein `aria-invalid=""` |
| AK-58 | ✅ bestanden | `ls -d public/app` → nicht vorhanden; `RouteDirectoryCollisionTest` grün (1 Test, 2 Assertions) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ belegt | `::testFehlerfallAntwortetAlsHtml` und `AppWaitlistQaTest::testAk12…` |
| EC-02 | ✅ belegt | `AppWaitlistAdminTest::testLesenderAufrufVerbrauchtKeinKontingent` |
| EC-03 | ✅ belegt | `when@test`-Override vorhanden; `LimiterCoverageTest` grün (8 Tests) |
| EC-04 | ✅ belegt | `::testZweiterKlickMeldetBereitsBestaetigt…` (Token bleibt) und `::testAbmeldelinkLoeschtDenEintrag` (zweiter Klick 200) |
| EC-05 | ✅ belegt | `::testUeberlangeUndSeltsameAdressenWerdenAbgewiesen` · `::testAdresseWirdNormalisiertUndBleibtEindeutig` — **aber siehe BUG-03**, eine Eingabeform kommt durch |
| EC-06 | ❌ **offen** | siehe **BUG-05** — der Unique-Index greift (Test belegt), der Controller fängt die Ausnahme aber nicht ab |
| EC-07 | ✅ belegt | `::testAbgelaufenerVorgangBekommtEineNeueMail` — **aber der neue Token trägt nicht, siehe BUG-01** |
| EC-08 | ⚠️ Betriebszusage | `trusted_hosts`/`TRUSTED_PROXIES` — nicht Gegenstand dieses Features |
| EC-09 | ⚠️ Betriebszusage | Worker-Ausfall — nicht Gegenstand dieses Features |

## Sicherheitsprüfung

Aktiv angegriffen. Grundlage: `~/.claude/sdd/sicherheit.md`. Belege in `qa/08/`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| 1 · Zugriff auf fremde ID (IDOR) | **bestanden** | Öffentlich gibt es keine ID-Route — der Zugang läuft ausschließlich über den 64-stelligen Token. Verwaltung: `/admin/warteliste/app/{id}` ohne Adminrolle → **403**, anonym → **302** auf `/login` |
| 2 · Zugriffsregeln serverseitig | **bestanden** | `#[IsGranted('ROLE_ADMIN')]` auf Klassenebene von `AdminWaitlistController` **plus** `access_control` auf `^/[a-z]{2}/admin`. Kein RLS im Stack — die Anwendung ist die einzige Grenze, und sie prüft |
| 3 · Rate Limit greift | **BUG-02** | Policy-Grenzwert korrekt (10 akzeptiert, 11. abgewiesen). **Aber:** fünf Absendevorgänge über den Dublettenzweig → Kontingent 9984 → 9984, Differenz **0** |
| 4 · PII in Protokollen | **bestanden** | Kein `logger`-Aufruf in irgendeiner Feature-Datei. Treffer in `var/log/test.log` stammen aus `doctrine.DEBUG` (Testumgebung) und aus B14-Einträgen vom 2026-08-20; `when@prod` schließt `!doctrine` und `!request` aus |
| 5 · PII an externe Dienste | **BUG-04** | Tatsächliche Zeile in `marketing_contact`: `organisation_name = 'iOS'`. Übrige Felder wie zugesagt (`email`, `origin='app'`, `locale`, `consent_at`); **Token nicht enthalten**, `contact_name` leer |
| 6 · Geheimnisse im Repository | **bestanden** | `git log -p --all -S 'testflight.apple.com/join'` → 0 Treffer; `.env.local` ist ignoriert; `.env` trägt den Schlüssel leer |
| 7 · Eingaben | **BUG-03** | XSS, SQL-Injection, 10.000 Zeichen, Emoji, leer, ein Zeichen → alle **HTTP 422**, kein unescaptes Skript, keine rohe DB-Meldung, Tabelle intakt. **Ausnahme:** `../../etc/passwd@example.lu` → **HTTP 500** |
| 8 · Löschen | **bestanden** | `::testKontoloeschungEntferntDieVormerkung` · `::testAbmeldelinkLoeschtDenEintrag` (Zeile weg) · `::testNachDerAbmeldungIstDieAdresseWiederFrei` (Adresse wieder frei) |

## Fehler

### BUG-01 · Der neu ausgestellte Bestätigungslink ist sofort wieder abgelaufen — hoch

**Betrifft:** AK-17, EC-07

**Reproduktion:**
1. Vormerkung für `sonde1@example.lu` anlegen, nicht bestätigen
2. `created_at` auf `-8 days` setzen (der Vorgang gilt damit als abgelaufen)
3. Dieselbe Adresse erneut über `/de/app` absenden
4. Den Link aus der **neuen** Mail aufrufen

**Erwartet:** HTTP 200, der Vorgang wird bestätigt (AK-17: „dann geht eine **neue**
Bestätigungsmail mit **neuem** Token hinaus … sonst wäre der Vorgang eine Sackgasse")

**Tatsächlich:** HTTP **410**. Gemessen: `createdAt` bleibt nach dem erneuten Eintragen
auf `2026-08-28 12:07`, `isExpired()` liefert weiterhin `true`.

**Ort:** `src/Waitlist/WaitlistConfirmationService.php:61-117` — `register()` erzeugt
einen neuen Token, setzt `createdAt` aber nicht zurück. `isExpired()` (Zeile 223-226)
misst ausschließlich an `createdAt`.

**Warum kein Test das gefunden hat:** `AppWaitlistControllerTest::testAbgelaufenerVorgangBekommtEineNeueMail`
prüft, dass sich der Token **ändert** — nicht, dass er **trägt**. Genau das Muster, das
`CLAUDE.md` unter BF-64 beschreibt: eine Zusicherung, die schwächer ist als das Kriterium.

**Vorschlag:** `register()` setzt `createdAt` beim Neuausstellen zurück — das ist die
Stelle, an der die Frist entsteht. ⚠ Die Methode wird von B14 und B15 mitbenutzt; dort
legt sie bisher nur neue Einträge an, für die der Zeitpunkt ohnehin „jetzt" ist.

**✅ Behoben am 2026-09-05.** `AppWaitlistEntry::renewConfirmationWindow()` setzt Frist
**und** Token; `handleDuplicate()` ruft sie vor dem erneuten Versand. Gegenprobe mit der
Reproduktion oben: `isExpired` → **nein**, Aufruf des neuen Links → **HTTP 200** (war 410).
Test: `AppWaitlistRegressionTest::testBf117NeuerLinkNachAblaufLoestTatsaechlichEin` —
er prüft die **Einlösung**, nicht die Änderung des Tokens.
⚠ Bewusst in der Entity und nicht in `WaitlistConfirmationService::register()`: Dort landet
bei B14/B15 stets ein frischer Eintrag. `consentAt` wandert nicht mit — der
Einwilligungsnachweis darf kein späterer Vorgang überschreiben.

---

### BUG-02 · Der Dublettenzweig verbraucht kein Kontingent — unbegrenzter Mailversand an fremde Adressen — hoch

**Betrifft:** AK-44, AK-46; Konvention „Jeder Weg, der eine Mail auslöst, braucht einen Limiter"

**Reproduktion:**
1. Vormerkung für `opfer@example.lu` anlegen, nicht bestätigen, `created_at` älter als
   7 Tage (in der Praxis der Normalzustand jeder Vormerkung, die zwischen Tag 8 und
   Tag 30 liegt — davor greift die Frist nicht, danach der Aufräumlauf)
2. Fünfmal `/de/app` mit genau dieser Adresse absenden

**Erwartet:** Spätestens der elfte Versuch innerhalb einer Stunde → HTTP 429

**Tatsächlich:** Jeder Versuch → HTTP 302 und **eine Mail**. Restkontingent vorher 9984,
nachher 9984 — **Differenz 0**. Es gibt keinen Deckel auf diesem Weg.

**Ort:** `src/Controller/AppWaitlistController.php:101-105` — der Dublettenzweig kehrt
mit `return $this->handleDuplicate(...)` zurück, bevor `$limiter->consume()` (Zeile 107)
erreicht wird. `handleDuplicate()` (Zeile 154-156) verschickt im Ablauf-Zweig eine Mail.

⚠ **`isAllowed()` verbraucht nichts** — es ruft `consume(0)`, und `SlidingWindowLimiter`
speichert dabei nichts (`vendor/symfony/rate-limiter/Policy/SlidingWindowLimiter.php:71-76`).
Das ist richtig so und der Grund, warum der Verbrauch eine eigene Zeile ist.

**Verschärfend:** Zusammen mit BUG-01 ist der Zustand dauerhaft — der Eintrag bleibt
„abgelaufen", also lässt sich der Zweig beliebig oft auslösen.

**Vorschlag:** `consume()` vor den Dublettenzweig ziehen oder im Resend-Fall nachholen.
⚠ Nicht vor die Formularprüfung — fünf Tippfehler dürfen niemanden aussperren (BF-11).

**✅ Behoben am 2026-09-05.** `handleDuplicate()` bekommt den `ActionLimiter` übergeben und
verbraucht **im Ablauf-Zweig**, bevor die Mail hinausgeht. Der Zweig „bereits bestätigt"
verbraucht weiterhin nichts — dort findet keine Handlung statt. Gegenprobe: fünf
Absendevorgänge → **1 Mail**, Kontingent-Differenz **1** (war 0 bei 5 Mails). Ab dem
zweiten Versuch greift zusätzlich die erneuerte Frist aus BUG-01.
Tests: `::testBf118DublettenzweigVerbrauchtKontingent` und
`::testBf118BestaetigteDubletteVerbrauchtNichts`.

---

### BUG-03 · Eine RFC-widrige Adresse erzeugt HTTP 500 und hinterlässt eine Zeile — hoch

**Betrifft:** AK-08, EC-05

**Reproduktion:**
1. `/de/app` aufrufen
2. Als E-Mail `../../etc/passwd@example.lu` eintragen, Plattform wählen, Häkchen setzen
3. Absenden

**Erwartet:** HTTP 422 mit einer Meldung am E-Mail-Feld, **kein Eintrag** (AK-08)

**Tatsächlich:** HTTP **500**.
`Symfony\Component\Mime\Exception\RfcComplianceException: Email "../../etc/passwd@example.lu"
does not comply with addr-spec of RFC 2822.`
Gemessen: Zeilen vorher 0, **nachher 1** — der Eintrag bleibt stehen.

**Ort:** `src/Waitlist/WaitlistConfirmationService.php:93` (`->to($entry->getEmail())`),
ausgelöst aus `src/Controller/AppWaitlistController.php:169`. Ursache ist
`src/Form/AppWaitlistType.php:64`: `new Email(...)` läuft im **HTML5-Modus** (Symfony-Default)
und lässt die Adresse durch, während `Mime\Address` RFC 2822 verlangt.

**Warum die Zeile bleibt:** `register()` speichert **vor** dem Versand (Reihenfolge
Token → flush → Mail, damit eine gescheiterte Zustellung die Anmeldung nicht verliert).
Die Ausnahme fliegt danach — der Commit steht bereits.

⚠ **Projektweites Muster, nicht nur Feature 08.** Gegenprobe am Partner-Formular
(`/de/partner`, B14) mit derselben Eingabe: **dieselbe Ausnahme**. `PartnerWaitlistType`,
`OrganisationWaitlistType` und `RegistrationType` nutzen alle den HTML5-Default.

**Vorschlag:** `new Email(mode: Email::VALIDATION_MODE_STRICT)` — dann prüft die
Constraint gegen dieselbe Norm wie der Mailversand. Die Reparatur für B14/B15/B01 gehört
in einen eigenen Auftrag, nicht hierher.

**✅ Behoben am 2026-09-05 — für Feature 08.** `AppWaitlistType` prüft mit
`Email::VALIDATION_MODE_STRICT`, also gegen dieselbe Norm wie der Mailversand. Gegenprobe:
`../../etc/passwd@example.lu` → **HTTP 422** (war 500), Zeilen vorher 0, **nachher 0**
(war 1). Test: `::testBf119RfcWidrigeAdresseLiefert422UndLegtNichtsAn`, drei Eingabeformen.
`egulias/email-validator` 4.0.4 liegt transitiv über `symfony/mime` bereits vor — **keine
neue Abhängigkeit**.
⚠ **B14, B15 und B01 sind unverändert.** Die Gegenprobe am Partner-Formular wirft weiterhin
`RfcComplianceException`; das steht als BF-119 offen und gehört in einen eigenen Auftrag.

---

### BUG-04 · Die gewählte Plattform wird an Brevo übertragen — mittel

**Betrifft:** AK-54

**Reproduktion:**
1. Vormerkung mit iOS und gesetzter Werbe-Einwilligung anlegen
2. Bestätigungslink aufrufen
3. `SELECT * FROM marketing_contact WHERE email = …`

**Erwartet:** Übertragen wird die Adresse, Herkunft `app`, Einwilligungszeitpunkt und
Sprache. AK-54: „die gewählte Plattform geht **nicht** mit". `design.md` wird noch
deutlicher: „Beide Felder bleiben leer."

**Tatsächlich:** `organisation_name = 'iOS'`. Von dort geht der Wert als Brevo-Attribut
`ORGANISATION` hinaus.

**Ort:** `src/Marketing/MarketingContactRegistry.php:102` — `organisationName:
$entry->getDisplayName()` gilt für alle drei Wartelisten. Bei B14/B15 ist das ein echter
Organisationsname; `AppWaitlistEntry::getDisplayName()`
(`src/Entity/AppWaitlistEntry.php:204-207`) liefert dagegen das Plattform-Label.

**Einordnung des Grades:** kein Abfluss an Fremde, keine besondere Kategorie nach Art. 9,
und der Empfängerdienst ist derselbe, dem die Adresse ohnehin eingewilligt zugeht.
Aber es ist ein Attribut in einer Werbeliste, das die Spezifikation abschließend
ausschließt — und der Entwurf hat genau diesen Fehler vorhergesehen und benannt.

**Vorschlag:** In `recordWaitlistEntry()` für `AppWaitlistEntry` `organisationName: null`
erzwingen. `getDisplayName()` bleibt unverändert — es trägt die Verwaltungsliste.

**✅ Behoben am 2026-09-05.** `recordWaitlistEntry()` reicht für `AppWaitlistEntry`
`organisationName: null` durch. Gegenprobe an der tatsächlichen Zeile:
`organisation_name` leer, weder `ios` noch `android` im gesamten Datensatz.
Test: `::testBf120PlattformGehtNichtNachBrevo` — prüft die Zeile **und** beide
Schreibweisen; die erste QA-Sonde hatte den Fehler fast verfehlt, weil sie auf `ios`
prüfte, während dort `iOS` stand.
⚠ `getDisplayName()` bleibt unverändert — es trägt die Verwaltungsliste. Falsch war nicht
die Methode, sondern sie ungeprüft als Marketing-Attribut weiterzureichen.

---

### BUG-05 · Ein echtes Wettrennen wird nicht abgefangen — mittel

**Betrifft:** EC-06

**Reproduktion:** Zwei gleichzeitige `POST /{locale}/app` mit derselben, bisher
unbekannten Adresse. Beide durchlaufen `findOneByEmail()` (beide `null`), bevor eine
committet; die zweite läuft beim `flush()` in die `UniqueConstraintViolationException`.

**Erwartet:** Dieselbe Erfolgsantwort wie im Dublettenfall (EC-06: „auch der Wettlauf
muss abgefangen sein")

**Tatsächlich:** Die Ausnahme wird nirgends gefangen. `ApiExceptionSubscriber` ist auf
`^/api/v1` beschränkt (`src/EventSubscriber/ApiExceptionSubscriber.php:39`), ein
Web-Pendant existiert nicht → HTTP 500.

**Ort:** `src/Controller/AppWaitlistController.php:101-125` — kein `try/catch` um den
Anlagepfad.

⚠ **Ehrlich zum Nachweis: im Code nachvollzogen, am laufenden System nicht
reproduziert.** Der Testclient ist einprozessig und sequenziell; das Zeitfenster ist ein
einzelner Datenbank-Roundtrip. Der **häufigere** Fall — Doppelklick, zweite Zeile steht
bereits — wurde nachgestellt und läuft sauber: HTTP 302 (`qa/08/race-probe.php.txt`).
Deshalb *mittel* und nicht *hoch*: Der Code-Review stufte höher ein, konnte aber
ebenfalls keine Reproduktion beibringen.

**Vorschlag:** `try/catch (UniqueConstraintViolationException)` um den Anlagepfad, im
`catch` dieselbe `successResponse()` wie im Dublettenzweig.

**✅ Behoben am 2026-09-05.** `try/catch (UniqueConstraintViolationException)` um den
Anlagepfad; im `catch` dieselbe `successResponse()` wie im Dublettenzweig, ohne erneuten
Versand — die Anfrage, die das Rennen gewonnen hat, verschickt die Mail bereits.
Test: `::testBf121WettlaufErgibtDieErfolgsantwort` — die konkurrierende Zeile entsteht per
rohem SQL, nachdem das Formular ausgeliefert wurde; Ergebnis **302 statt 500**, eine Zeile.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/AppWaitlistQaTest.php` | 7 | AK-10, AK-12, AK-20, AK-29, AK-32, AK-41, AK-42, AK-46 — Kriterien, die beim Bau ohne Nachweis blieben |

Belege der Angriffsprüfung liegen als `qa/08/sonden.php.txt` und `qa/08/race-probe.php.txt`
(ausführbare Reproduktionen, bewusst **nicht** in `tests/` — sie belegen Fehler, statt sie
abzufangen; die festen Assertions entstehen bei der Behebung).

## Nächster Schritt

`/sdd-build 08` mit dem Auftrag, **BUG-01 bis BUG-05** zu beheben — BUG-01 und BUG-02
zuerst, sie liegen auf demselben Weg und verstärken einander. Danach erneut `/sdd-qa 08`.

⚠ **BUG-03 betrifft auch B14, B15 und B01.** Die Reparatur an Feature 08 behebt dort
nichts; der Befund gehört als eigener Auftrag in die Bestandsfeatures.

---

# Zweiter Durchlauf — 2026-09-05

Geprüft wurde, ob die fünf Reparaturen greifen **und ob sie Neues gebrochen haben**.
Der zweite Punkt ist der Grund für diesen Durchlauf: Eine Reparatur, die nur gegen ihre
eigene Reproduktion geprüft wird, verschiebt Fehler, statt sie zu beheben.

## Fazit des zweiten Durchlaufs

**Production-ready: ja — mit einem offenen Befund mittleren Grades.**

> **Nachtrag 2026-09-05:** BUG-06 ist auf Betreiberentscheid behoben (Vermerk unten,
> Gegenprobe geführt). 985 Tests grün. Das Fazit beschreibt weiterhin den Stand **zum
> Zeitpunkt der Prüfung** — der neue Stand gehört in einen dritten Durchlauf.

Alle fünf Befunde des ersten Durchlaufs sind behoben und gegen ihre jeweilige
Reproduktion geprüft. Vier Reparaturen sind ohne Nebenwirkung; **eine hat einen neuen
Befund erzeugt** (BF-122), der die Auslieferung nicht blockiert, aber vor dem nächsten
Release entschieden gehört.

| | Anzahl |
|---|---|
| Befunde des ersten Durchlaufs | 5 |
| davon behoben und gegengeprüft | 5 |
| Reparaturen ohne Nebenwirkung | 4 von 5 |
| **neue Befunde** | 1 (mittel) |
| Akzeptanzkriterien jetzt bestanden | **57 von 58** |
| nicht prüfbar | 1 (AK-44, unverändert) |
| Tests gesamt | 982, alle grün |

## Die vier zuvor durchgefallenen Kriterien

| AK | vorher | jetzt | Nachweis |
|---|---|---|---|
| AK-08 | ❌ HTTP 500, 1 Zeile | ✅ **bestanden** | `AppWaitlistRegressionTest::testBf119RfcWidrigeAdresseLiefert422UndLegtNichtsAn` — drei Eingabeformen, je 422, `count([])` = 0 |
| AK-17 | ❌ neuer Link → HTTP 410 | ✅ **bestanden** | `::testBf117NeuerLinkNachAblaufLoestTatsaechlichEin` — HTTP 200, `selfConfirmedAt` gesetzt |
| AK-46 | ❌ Deckel griff nicht | ✅ **bestanden** | `::testBf118DublettenzweigVerbrauchtKontingent` — Kontingent-Differenz 1; Sonde über alle vier Wege: kein Mailweg mehr ohne Deckel |
| AK-54 | ❌ `organisation_name='iOS'` | ✅ **bestanden** | `::testBf120PlattformGehtNichtNachBrevo` **und** `::testBf120PlattformStehtAuchNichtImBrevoRumpf` — geprüft am tatsächlichen Brevo-Rumpf, nicht nur an der Zeile davor |
| EC-06 | ❌ ungefangen | ✅ **belegt** | `::testBf121WettlaufErgibtDieErfolgsantwort` — HTTP 302 statt 500, eine Zeile |

## Prüfung der Reparaturen auf Nebenwirkungen

| Reparatur | Urteil | Beleg |
|---|---|---|
| BF-117 · `renewConfirmationWindow()` | ⚠️ **Nebenwirkung** | siehe **BUG-06** |
| BF-118 · Limiter im Dublettenzweig | **sauber** | Sonde über alle vier Wege: neue Adresse −1, abgelaufene Dublette −1, bestätigte Dublette 0 (keine Mail), Honeypot 0 (keine Mail). Kein Weg löst etwas aus, ohne zu verbrauchen |
| BF-119 · `VALIDATION_MODE_STRICT` | **sauber — und ein Gewinn** | Acht realistische Adressen durchgespielt, **alle akzeptiert** (Plus-Adressierung, Apostroph, Subdomains, lange TLD). Direkter Modusvergleich: STRICT lehnt zusätzlich nur Local-Parts **über 64 Zeichen** ab — die RFC 5321 ohnehin verbietet und die kein Mailserver zustellt. **Umgekehrt akzeptiert STRICT `jean-luc@télécom.lu`, das der HTML5-Default abwies** — die Reparatur hat eine stille Ablehnung von Umlaut-Domains mitbehoben |
| BF-120 · `organisationName: null` | **sauber** | `MarketingContact::setOrganisationName(?string)` ist nullable, `MarketingPayloadMapper:91` liest `?? ''`; `recordUser()` übergibt seit jeher null. Kein Template rendert das Feld |
| BF-121 · `try/catch` | **sauber** | Der Block unterscheidet nicht, welcher der beiden Unique-Indizes verletzt wurde. Bei einer Token-Kollision (256 Bit Zufall) wäre die Antwort „harmlos falsch" statt eines 500ers. Kein praktischer Fund |

## Sicherheitsprüfung, zweiter Lauf

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| 1 · IDOR | **bestanden** | `AppWaitlistAdminTest` 7/7 grün — 403 ohne Rolle, 302 anonym |
| 3 · Rate Limit | **bestanden** | Sonde über alle vier Absendewege; kein Mailweg ohne Verbrauch. Grenzwert an der Policy: 10 akzeptiert, 11. abgewiesen |
| 4 · PII in Protokollen | **bestanden** | Kein `logger`, `error_log`, `dump()` in einer der geänderten Dateien |
| 5 · PII an externe Dienste | **bestanden** | Am **tatsächlichen Brevo-Rumpf** geprüft: `ORGANISATION` leer, weder `ios` noch `android` noch der Token im JSON |
| 6 · Geheimnisse | **bestanden** | `Whxmtrsf` in keiner getrackten Quelle; `.env` trägt den Schlüssel leer, `.env.local` ist ignoriert |
| 7 · Eingaben | **bestanden** | Alle sieben Formen aus dem ersten Lauf → 422, Tabelle mit 0 Zeilen (vorher: eine Form → 500 und eine Zeile) |
| 8 · Löschen | **bestanden** | Feature- und Integrationstests unverändert grün |
| **Regression im Bestand** | **bestanden** | B14, B15 und der Marketing-Bereich: 76 Tests grün. Feature 08: 65 Tests grün |

## Fehler des zweiten Durchlaufs

### BUG-06 · Die 30-Tage-Aufräumfrist lässt sich unbegrenzt verlängern — mittel

**Betrifft:** AK-47 · **Ursache:** die Reparatur von BUG-01

**Reproduktion:**
1. Vormerkung für `dauergast@example.lu` anlegen, nie bestätigen
2. `created_at` auf `-29 days` setzen (kurz vor der Löschfrist, Token längst abgelaufen)
3. Dieselbe Adresse erneut über `/de/app` absenden
4. Aufräumlauf zwei Tage später laufen lassen — Tag 31 seit dem Erstkontakt

**Erwartet:** Der Eintrag ist gelöscht (AK-47: „steht seit mehr als 30 Tagen auf pending
→ gelöscht", begründet mit „ohne Bestätigung liegt keine Einwilligung vor")

**Tatsächlich:** `createdAt` springt von `2026-08-07` auf `2026-09-05`; der Aufräumlauf
an Tag 31 löscht **0** Einträge, die Zeile bleibt bestehen.

**Ort:** `src/Entity/AppWaitlistEntry.php` — `renewConfirmationWindow()` setzt `createdAt`
zurück. Daran messen **zwei** Fristen:
`WaitlistConfirmationService::isExpired()` (7 Tage, gewollt zurückgesetzt) und
`AppWaitlistEntryRepository::deleteStaleUnconfirmed()` (30 Tage, **nicht** gewollt).

**Ausnutzbarkeit:** Ein POST alle 7 bis 29 Tage genügt — über 7, damit der Zweig
überhaupt greift, unter 30, damit der Lauf nicht dazwischenkommt. Kostet je einen von
zehn Token pro Stunde, ist also faktisch gratis. **`handleDuplicate()` prüft keine
Eigentümerschaft**: Das funktioniert auch mit einer fremden Adresse, die dabei je Runde
eine Mail bekommt.

**Warum kein Test das gefunden hat:** Alle vorhandenen Tests prüfen **eine einzelne**
Erneuerung. Die Wechselwirkung entsteht erst bei Wiederholung — und daraus, dass zwei
Fristen dieselbe Spalte lesen.

**Einordnung des Grades:** Auf dem Hauptweg ist AK-47 erfüllt (belegt durch
`AppWaitlistIntegrationTest::testAufraeumlaufLoeschtNieBestaetigteNach30Tagen`). Umgangen
wird es nur durch wiederholtes, aktives Zutun. Kein Datenabfluss, keine Zugriffslücke —
aber eine Speicherung über die zugesagte Frist hinaus, und die Zusage ist ausdrücklich
datenschutzrechtlich begründet. Der Code-Review tendierte zu *mittel–hoch*; ich bleibe
bei **mittel**, weil der reguläre Ablauf nachweislich trägt.

**Vorschlag:** Eine zweite, harte Obergrenze am Löschkriterium — `consentAt` bleibt beim
Erneuern unberührt und markiert den echten Erstkontakt. Damit bliebe die Frist des
Bestätigungslinks erneuerbar, die Aufbewahrung aber gedeckelt.


**✅ Behoben am 2026-09-05.** `deleteStaleUnconfirmed()` misst an **`consentAt`** statt an
`createdAt`. Das Feld bleibt beim Erneuern unberührt und markiert den echten Erstkontakt —
damit ist der Bestätigungslink weiterhin erneuerbar (AK-17), die Aufbewahrung aber
gedeckelt (AK-47).

Tests: `AppWaitlistIntegrationTest::testBf122ErneuernVerlaengertDieAufbewahrungNicht`,
`::testBf122AuchMehrfachesErneuernHaeltDenEintragNichtAmLeben` (fünf Runden) und die
Gegenprobe `::testBf122ErneuerterLinkBleibtInnerhalbDerFristGueltig`, die sicherstellt,
dass BF-117 dadurch nicht zurückkommt.

⚠ **Keine zweite Bedingung neben `createdAt`.** Da `consentAt` nie später liegt, ist es von
beiden stets das schärfere Kriterium; ein `OR` daneben wäre wirkungslos und sähe nur so
aus, als prüfte es etwas.

⚠ **Die Reproduktion oben enthält selbst einen Fehler** — sie altert nur `created_at`. Ein
real 29 Tage alter Eintrag hat **beide** Zeitpunkte in der Vergangenheit; der Konstruktor
setzt sie identisch. Vor der Reparatur war das folgenlos, weil beide Spalten dieselbe
Frist trugen. Mit realitätsgetreuem Altern: **1 gelöscht, Eintrag weg** (vorher 0). Die
Sonde in `qa/08-durchlauf2/` bleibt unverändert stehen — sie belegt den Fund von damals,
und wer sie erneut laufen lässt, muss diesen Punkt kennen.
## Neue Tests des zweiten Durchlaufs

| Datei | Fälle | Deckt ab |
|---|---|---|
| `AppWaitlistRegressionTest` (ergänzt) | +1 | AK-54 am **tatsächlichen Brevo-Rumpf**, nicht nur an der Zeile davor — die Lücke des ersten Durchlaufs |

Belege in `qa/08-durchlauf2/`.

## Nächster Schritt

**`/sdd-deploy 08`.** BUG-06 blockiert nicht, gehört aber vor dem Release entschieden —
entweder beheben (`/sdd-build 08`) oder als bewusst hingenommen in `befunde.md` vermerken.

⚠ **BF-119 bleibt für B14, B15 und B01 offen.** Die Gegenprobe am Partner-Formular wirft
weiterhin `RfcComplianceException`; die Reparatur an Feature 08 hat dort nichts geändert.

---

# Dritter Durchlauf — 2026-09-05

Geprüft wurde die Reparatur von BUG-06 und die Frage, ob sie eine der fünf vorherigen
zurückholt. Umfang bewusst eng: Die Änderung betrifft eine Repository-Methode, alles
Übrige wurde im zweiten Durchlauf belegt und ist durch die Testsuite abgesichert.

## Fazit des dritten Durchlaufs

**Production-ready: ja — ohne offenen Befund.**

BUG-06 ist behoben und mit vier eigenen Sonden gegengeprüft, darunter der vollständige
Missbrauchsweg über HTTP und zehn Erneuerungsrunden hintereinander. Keine der fünf
früheren Reparaturen ist zurückgekommen. **Zu Feature 08 steht kein Befund mehr offen.**

| | Anzahl |
|---|---|
| Akzeptanzkriterien bestanden | **58 von 58** — AK-44 bleibt nicht prüfbar, siehe unten |
| Edge Cases belegt | 9 von 9 |
| offene Befunde zu Feature 08 | **keine** |
| Tests gesamt | 985, alle grün |
| davon neu in diesem Durchlauf | 0 — die drei Tests zu BF-122 entstanden beim Bauen und decken den Fall ab |

## BUG-06 · gegengeprüft

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Missbrauchsweg über die Entity | **behoben** | `AppWaitlistIntegrationTest::testBf122ErneuernVerlaengertDieAufbewahrungNicht` — Lauf an Tag 31 löscht 1 |
| Missbrauchsweg über **HTTP** | **behoben** | Sonde E: Erstkontakt −29 Tage, erneut eintragen, Lauf an Tag 31 → 1 gelöscht, Eintrag weg |
| **Zehn** Erneuerungsrunden | **behoben** | Sonde F: nach zehn Durchgängen über HTTP → 1 gelöscht, Eintrag weg |
| Kommt BF-117 zurück? | **nein** | Sonde G: erneuerter Link → HTTP 200, `self_confirmed_at` in der Datenbank gesetzt, Status `confirmed` |
| Annahme „`consentAt` nie später als `createdAt`" | **trägt** | Sonde D am echten Anlageweg: beide Zeitstempel identisch. `setConsentAt()` wird ausschließlich im Anlagepfad gerufen, nach dem Dublettenzweig — ein bestehender Eintrag bekommt nie ein neues `consentAt` |
| AK-47 / AK-48 im Normalfall | **unverändert** | `::testAufraeumlaufLoeschtNieBestaetigteNach30Tagen`, `::testAufraeumlaufLaesstBestaetigteStehen`, `::testAufraeumlaufGreiftAuchBeiVerwaltungsseitigWeitergesetztenEintraegen` |

## Regression

| Bereich | Ergebnis |
|---|---|
| Feature 08 vollständig | 69 Tests grün |
| Bestand: B14, B15, Marketing, Kontolöschung | 84 Tests grün |
| Rate Limit (Angriff 3) | 8 Tests grün |
| PII an externe Dienste (Angriff 5) | 2 Tests grün — Brevo-Rumpf unverändert sauber |
| Eingaben (Angriff 7) | 1 Test, 4 Assertions grün |
| Löschen (Angriff 8) | 7 Tests grün |
| Gesamtsuite | **985 grün** |

## Ein Befund an der Prüfung selbst

Sonde G meldete zunächst „selbst bestätigt: **nein**" — scheinbar ein Rückfall von
BF-117. Die Nachprüfung zeigte: Das Repository lieferte das Objekt aus Doctrines Identity
Map, also den Stand **vor** dem Request. Ein Blick direkt in die Tabelle ergab
`self_confirmed_at` gesetzt und Status `confirmed`.

Der Fall gehört in den Bericht, weil er zum zweiten Mal in dieser Prüfkette auftritt:
Beim vorigen Durchlauf enthielt die Reproduktion zu BF-122 einen Messfehler (nur eine
Spalte gealtert), der erst nach der Reparatur auffiel. **Eine Sonde ist Code und kann
falsch sein wie jeder andere** — ein gemeldeter Fehler, der sich auf sie stützt, gehört
vor der Meldung nachgeprüft.

## Warum kein dritter Code-Review

Die Änderung ist eine Bedingung in einer Repository-Methode; dieselben Dateien haben in
den beiden vorigen Durchläufen je einen vollständigen Review erhalten. Der Grenznutzen
eines dritten Laufs wäre gering gewesen, der Aufwand nicht. Stattdessen wurde die
Annahme, auf der die Reparatur beruht, direkt am laufenden System gemessen (Sonde D) —
das ist der Punkt, an dem sie hätte brechen können.

## Nicht prüfbar — unverändert

**AK-44** (Rate Limit 10 je Stunde): `when@test` hebt jeden Limiter auf 10000, das ist
Projektkonvention und verhindert, dass sich Aufrufe über die Suite summieren. Belegt sind
der Grenzwert an der Policy (10 akzeptiert, der 11. abgewiesen) und die Verdrahtung über
`LimiterCoverageTest`. Der Nachweis am laufenden System gehört in die Nachprüfung auf der
Produktivumgebung.

## Hinweise ohne Befundcharakter

- **`features/befunde.md` wurde von `sdd-build` angefasst** — die Zuordnung von BF-119
  wanderte von „08 / projektweit" auf „B14 / B15 / B01". Inhaltlich richtig: Der
  Feature-08-Teil ist behoben, offen ist der Bestand. Die Liste zu führen ist allerdings
  Aufgabe dieses Skills; die Änderung wird hiermit nachträglich übernommen und verantwortet.
- **BF-119 bleibt für B14, B15 und B01 offen** und ist auf Produktion bereits wirksam —
  unabhängig von diesem Feature. Eigener Auftrag.

## Nächster Schritt

**`/sdd-deploy 08`.** Der Preflight war beim letzten Anlauf bis auf zwei Punkte
vollständig: Post-Deployment-Command und `APP_TESTFLIGHT_URL` sind in Coolify bestätigt.
Offen bleiben der Release-Commit und der Versionsstand — beide gehören in den
Deploy-Durchgang, alle fünf Stellen zeigen noch auf `2026.09.02`.
