# B14 · Partner-Warteliste — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

> **Abgeglichen am 2026-09-12 (BF-126).** Fünf Stellen beschrieben einen überholten
> Stand: AK-21 („keine Ablauffrist"), AK-22 („kein Widerrufsweg") und AK-23 („geteiltes
> Kontingent") sind seit `v2026.08.29` erledigt (BF-36/37/38), AK-17 zählte die
> erfassten Daten abschliessend auf und übersah drei Felder, AK-04 ist auf Produktion
> nicht mehr erfüllbar (BF-124). Dazu FB-01, FB-03 und FB-04 (BF-134).
> **Wer die alte Fassung las, hielt drei behobene DSGVO-Mängel für offen** — und eine
> Auskunft nach AK-17 wäre lückenhaft gewesen.
>
> ⚠ Diese Datei ist die **Rekonstruktion** eines Bestandsfeatures: Sie beschreibt, was
> der Code tut, nicht was er tun sollte — und sie veraltet mit jeder Reparatur, die sie
> behebt.

## Zweck

Restaurantbetreiber tragen sich auf `/{locale}/partner` für das kostenpflichtige
Partnerprogramm ein und bestätigen ihre Adresse per Double-Opt-In. Preise und
Paketumfang stehen ausdrücklich **noch nicht fest** — die Seite verarbeitet keine
Zahlung und legt kein Konto an.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| — | — | öffentlich, kein Konto nötig |

Teilt sich `WaitlistConfirmationService`, `WaitlistEntryInterface`,
`WaitlistRequestHelper`, `WaitlistStatus` und den Rate-Limiter mit B15.

## User Stories

- **US-01** · Als Restaurantbetreiber möchte ich mich unverbindlich vormerken lassen.
- **US-02** · Als Interessent möchte ich eine Bestätigungsmail bekommen, damit klar ist,
  dass die Anmeldung angekommen ist.
- **US-03** · Als Betreiber der Plattform möchte ich benachrichtigt werden, sobald
  jemand bestätigt hat.

## Nicht im Scope

- Zahlung, Vertragsabschluss, Kontoanlage — bewusst nicht
- Organisationen → B15
- Verwaltung der Einträge → B22

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher ruft `/{locale}/partner` auf, wenn die Seite
  lädt, dann sieht er die Landing-Page mit dem Anmeldeformular.
- **AK-02** · Angenommen, das Formular ist vollständig und gültig, wenn abgeschickt
  wird, dann entsteht ein Eintrag mit Status `pending`, `consentAt` = jetzt,
  `locale` = Anfragesprache und `source` aus UTM-Parameter oder Referrer-Host.
- **AK-03** · Angenommen, die Anmeldung ist gespeichert, wenn der Versand geprüft wird,
  dann geht eine Bestätigungsmail mit absolutem Link an die angegebene Adresse.
- **AK-04** · Angenommen, der Mailversand scheitert, wenn die Antwort betrachtet wird,
  dann **bleibt der Eintrag gespeichert** und es erscheint `flash.partner_email_failed`.
  ⚠ **Die zweite Hälfte ist auf Produktion seit dem 2026-09-02 nicht mehr erfüllbar
  (BF-124).** Der Versand läuft über den `async`-Transport: `send()` legt die Nachricht
  in `messenger_messages` und wirft keine `TransportExceptionInterface` mehr,
  `register()` gibt immer `true` zurück, und `if (!$sent)` wird nicht erreicht.
  Nachgestellt mit gestopptem Mailpit: **HTTP 302 ohne Warnung**, Eintrag entsteht
  (5 → 6), Nachricht in der Warteschlange. ⚠ **Der erste Prüflauf hat AK-04 zu Recht
  als bestanden gebucht** — damals lief der Mailer synchron; die Regression ist still
  eingetreten, weil die Umstellung an einer anderen Stelle beschlossen wurde. Was
  bleibt: Der Eintrag geht nicht verloren (erste Hälfte, weiterhin erfüllt), und der
  Rückstau wird gemessen (`app:messenger:watch`, täglich 07:20). Den Interessenten
  kann niemand mehr warnen — zum Zeitpunkt der Antwort ist die Zustellung nicht
  passiert.
- **AK-05** · Angenommen, der Browser unterstützt Turbo, wenn erfolgreich abgeschickt
  wird, dann wird nur das Formular durch die Erfolgsmeldung ersetzt (`turbo-stream`,
  `action="replace"`, Ziel `partner-waitlist-form`).
- **AK-06** · Angenommen, Turbo ist nicht aktiv, wenn erfolgreich abgeschickt wird, dann
  greift ein klassischer Redirect mit Flash-Meldung — die Seite funktioniert **ohne
  JavaScript**.
- **AK-07** · Angenommen, das Formular ist ungültig, wenn abgeschickt wird, dann
  antwortet der Server mit **422** und HTML (nicht `turbo-stream`), und Turbo rendert
  die Seite an Ort und Stelle neu.
- **AK-08** · Angenommen, das versteckte Honeypot-Feld `website` ist gefüllt, wenn
  abgeschickt wird, dann ist die Antwort **identisch** zum Erfolgsfall — es wird aber
  nichts gespeichert und nichts versandt.
- **AK-09** · Angenommen, von einer IP kommen mehr als 5 Absendeversuche in einer
  Stunde, wenn der nächste eintrifft, dann antwortet der Server mit **429** und
  `flash.partner_rate_limited`.
- **AK-10** · Angenommen, die Seite wird nur **gelesen** (GET), wenn das Kontingent
  geprüft wird, dann wurde nichts verbraucht — gedeckelt wird das Absenden.
- **AK-11** · Angenommen, ein gültiger Bestätigungslink wird aufgerufen, wenn die Seite
  lädt, dann wechselt der Status auf `confirmed`, `confirmedAt` wird gesetzt, und das
  Team erhält eine interne Meldung.
- **AK-12** · Angenommen, derselbe Link wird ein **zweites Mal** aufgerufen, wenn die
  Seite lädt, dann erscheint „bereits bestätigt" — unterscheidbar von einem unbekannten
  Token.
- **AK-13** · Angenommen, ein unbekannter Token wird aufgerufen, wenn die Antwort
  betrachtet wird, dann ist der Status **404** und die Seite zeigt „Link ungültig" —
  ohne Exception.
- **AK-14** · Angenommen, ein Token wird mit falschem Format aufgerufen, wenn die
  Anfrage durchläuft, dann greift bereits das Routen-Requirement `[a-f0-9]{64}` und die
  Route wird nicht gefunden.
- **AK-15** · Angenommen, die interne Meldung geht ans Team, wenn sie gelesen wird, dann
  ist sie **fest auf Deutsch**, unabhängig von der Sprache des Bestätigenden, und trägt
  die Adresse des Interessenten als `Reply-To`.
- **AK-16** · Angenommen, die interne Meldung ist nicht zustellbar, wenn der Nutzer die
  Seite sieht, dann merkt er nichts davon — die Bestätigung bleibt erfolgreich.

### Datenschutz und Missbrauchsschutz

- **AK-17** · Angenommen, ein Eintrag entsteht, wenn geprüft wird, welche
  personenbezogenen Daten er trägt, dann sind es: Restaurantname, Ansprechpartner,
  E-Mail, Telefon (optional), Ort, Freitextnachricht, Einwilligungszeitpunkt, Sprache
  und Herkunftsquelle. **Keine IP-Adresse.**
  ⚠ **Die Aufzählung war unvollständig (BF-126).** Seit dem 2026-08-29 kommen drei
  Felder hinzu, und eine Auskunft nach dieser Liste wäre lückenhaft gewesen:
  `marketingConsentAt` (eigene Einwilligung für den Brevo-Verteiler, Feature 04),
  `selfConfirmedAt` (Zeitpunkt der Selbstbestätigung — er unterscheidet den
  Doppel-Opt-in vom Weitersetzen durch die Verwaltung, BF-89) und `restaurant`
  (Verknüpfung mit einem bestehenden Eintrag, von der Verwaltung gesetzt, B22).
  **Keine IP-Adresse** — das gilt weiterhin.
- **AK-18** · Angenommen, das Honeypot-Feld wird betrachtet, wenn nach einem
  `Blank`-Constraint gesucht wird, dann gibt es keines — ein Validierungsfehler würde dem
  Bot verraten, welches Feld die Falle ist.
- **AK-19** · Angenommen, das Honeypot-Feld wird im Markup betrachtet, wenn sein Typ
  geprüft wird, dann ist es **kein** `type="hidden"` (das füllen Bots zuverlässig),
  sondern per CSS aus dem Blickfeld genommen, mit `aria-hidden="true"` und
  `tabindex="-1"`.
- **AK-20** · Angenommen, `consentAt` wird betrachtet, wenn geprüft wird wofür, dann
  belegt es den Zeitpunkt der Einwilligung — die Rechtsgrundlage der späteren
  Kontaktaufnahme.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-21** ~~⚠ · Angenommen, ein Bestätigungslink wird nach beliebig langer Zeit
  aufgerufen, wenn die Anfrage durchläuft, dann greift er weiterhin — es gibt **keine
  Ablauffrist**.~~ — **überholt, behoben durch BF-36, live seit `v2026.08.29`.**
  **Heute gilt:** Der Token verfällt sieben Tage nach `createdAt`; ein abgelaufener
  Link antwortet mit HTTP 410. Sieben Tage statt 24 Stunden, weil eine
  Wartelisten-Anmeldung kein Anmeldevorgang ist — wer sie am Freitagabend abschickt,
  liest die Mail vielleicht erst am Montag.
  *(So verhält sich der Code heute: `PartnerWaitlistEntry::generateConfirmationToken()`
  setzt keinen Ablaufzeitpunkt, anders als `User::generateVerificationToken()` mit
  24 Stunden. Folge: Ein Token, der einmal in einem fremden Postfach oder Log landet,
  bleibt dauerhaft einlösbar.)*

- **AK-22** ~~⚠ · Angenommen, ein Interessent möchte sich wieder austragen, wenn er einen
  Weg dafür sucht, dann gibt es keinen — weder einen Abmeldelink in der Mail noch eine
  Selbstbedienungsseite, und auch die Verwaltung (B22) kennt keine Löschfunktion.~~
  — **überholt, behoben durch BF-37, live seit `v2026.08.29`.**
  **Heute gilt:** Jede Mail trägt den Rückweg (`app_partner_revoke`, Token im Link);
  der Widerruf ist damit so einfach wie die Erteilung und erfüllt Art. 7 Abs. 3 DSGVO.
  ⚠ Wer den Link aus einer Mailvorlage entfernt, nimmt genau diese Pflicht mit.

- **AK-23** ~~⚠ · Angenommen, jemand hat sich auf der **Organisations**-Warteliste
  eingetragen, wenn er danach das Partnerformular abschickt, dann zählt beides auf
  dasselbe Kontingent.~~ — **überholt, behoben durch BF-38, live seit `v2026.08.29`.**
  **Heute gilt:** Jede Warteliste führt ihr eigenes Kontingent
  (`limiter.partner_waitlist`, `limiter.organisation_waitlist`, `limiter.app_waitlist`);
  ein ausgeschöpfter Partnerweg sperrt den Organisationsweg nicht mit. Am 2026-09-12
  über Verhalten belegt: Partnerweg 429, Organisationsweg weiterhin 302
  (`PartnerControllerTest::testAk23WartelistenFuehrenGetrennteKontingente`).
  ⚠ **Der frühere Prüflauf dazu bewies das Gegenteil des tatsächlichen Verhaltens**, weil
  er den Quelltext als Zeichenkette durchsuchte und nur noch einen Kommentar traf
  (BF-125).
  ⚠ **Nicht behoben ist der Meldungsschlüssel:** Die Organisationsseite zeigt bei
  Überschreitung weiterhin `flash.partner_rate_limited` — der hinterlegte Text ist in
  allen vier Sprachen neutral, irreführend ist allein der Schlüsselname (BF-129, offen,
  *niedrig*).

## Edge Cases

- **EC-01** · `updatedAt` wird über `#[ORM\PreUpdate]` gepflegt — der erste
  Lifecycle-Callback im Projekt. Im Konstruktor initialisiert, weil `PreUpdate` beim
  ersten `persist()` nicht feuert.
- **EC-02** · Der Token bleibt nach der Bestätigung **stehen** (anders als
  `User::verificationToken`, der genullt wird) — nur so ist AK-12 von AK-13
  unterscheidbar.
- **EC-03** · Der Erfolgsfall setzt `setRequestFormat(TURBO_STREAM)`, der Fehlerfall
  **darf das nicht** — die Antwort muss `text/html` bleiben, sonst rendert Turbo die
  422-Antwort nicht.
- **EC-04** · Das Rate-Limit wird **nach** `handleRequest()` geprüft, damit ein reiner
  Seitenaufruf kein Kontingent verbraucht.
- **EC-05** · Der `when@test`-Override auf 10.000 ist Pflicht, sonst wird die Suite ab
  dem sechsten Submit rot.

## Fehlbestand

- ~~**FB-01 · Kein Widerrufsweg.** Siehe AK-22. DSGVO-Pflicht, nicht Komfort.~~ —
  **erledigt** (BF-37, live seit `v2026.08.29`): Abmeldelink in jeder Mail.
- **FB-02 · Keine Löschfrist und keine Aufräumroutine.** ⚠ **Am 2026-09-12 nachgeprüft
  und weiterhin offen** — der Aufräumlauf aus Feature `08`
  (`StaleAppWaitlistCleaner`) greift ausschliesslich auf die **App**-Warteliste;
  `findPendingOlderThan()` wird im Produktivcode nach wie vor nirgends gerufen.
  `PartnerWaitlistEntryRepository::findPendingOlderThan()` existiert und ist offenkundig
  dafür gedacht — sie wird aber **nirgends im Produktivcode aufgerufen**, nur in
  `tests/Integration/Repository/PartnerWaitlistEntryRepositoryTest.php:29`. *Folge:*
  Nie bestätigte Anmeldungen bleiben unbefristet gespeichert; toter Code täuscht eine
  Aufräumlogik vor, die es nicht gibt.
- ~~**FB-03 · Kein Ablauf des Bestätigungstokens.** Siehe AK-21.~~ — **erledigt**
  (BF-36, live seit `v2026.08.29`): sieben Tage, gemessen an `createdAt`.
- ~~**FB-04 · Kein `trusted_hosts`, während der Bestätigungslink aus dem Request-Host
  gebaut wird.** `WaitlistConfirmationService::register()` nutzt `ABSOLUTE_URL`. Siehe
  B01/FB-09 — hier mit demselben Angriffsweg.~~ — **erledigt am 2026-09-12 (BF-134).**
  Feste Liste in `framework.trusted_hosts`; der Angriff antwortet jetzt mit HTTP 400
  statt 302, kein Eintrag, keine Mail.
- **FB-05 · Keine Auskunftsfunktion.** Wer wissen will, welche Daten über ihn
  gespeichert sind, hat keinen Weg dorthin.
- ~~**FB-06 · Der Freitext `message` wird nicht begrenzt geprüft.**~~ **Entfällt —
  2026-08-24 widerlegt.** `PartnerWaitlistType.php:77` trägt
  `Length(max: 2000)`; gemessen: 20.000 Zeichen → **422**. Alle fünf Textfelder haben
  Längengrenzen (180/120/180/40/120/2000). Der Eintrag bleibt durchgestrichen stehen,
  damit nachvollziehbar ist, dass hier einmal eine Lücke vermutet wurde, die es nicht
  gibt. In der Verwaltungsansicht wird der Text zudem maskiert — kein XSS.

## Offene Fragen

- **OF-BF119a** · Fünf weitere `new Email(...)` stehen weiterhin im HTML5-Default —
  `ProfileType`, `PasswordResetRequestType`, `AccessibilityReportType`,
  `RestaurantSuggestionType`, `RestaurantType`. Die ersten drei lösen einen Mailversand
  an **genau diese** Adresse aus und haben damit dieselbe Lücke wie BF-119; die beiden
  letzten speichern die Adresse nur. Beim Bauen von BF-119 gemessen, **bewusst nicht
  mitrepariert**: Der Auftrag nannte drei Formulare, und was nicht im Auftrag steht,
  wird nicht gebaut. — Betreiber
  *Teilstand 2026-11-14 (Betreiber, über BF-184):* `ProfileType` wird mit `/sdd-build B04` auf die strikte Prüfung gebracht. Offen bleiben `PasswordResetRequestType`, `AccessibilityReportType`, `RestaurantSuggestionType` und `RestaurantType`.

- **OF-BF119b** · `WaitlistConfirmationService::notifyRequester()` (die zweite Mail)
  konstruiert die Adresse erst beim `->to()`. Ein Reihenfolgeproblem ist das nicht — die
  Methode schreibt nichts in die Datenbank —, aber ein **Altbestand** mit einer
  RFC-widrigen Adresse aus der Zeit vor dieser Reparatur erzeugt dort weiterhin einen
  500er. Ob solche Zeilen auf Produktion liegen, ist von hier aus nicht prüfbar. — Betreiber

- **OF-BF119c** · Web- und API-Weg prüfen unterschiedlich: `Api\V1\AuthController::register()`
  nutzt `filter_var(..., FILTER_VALIDATE_EMAIL)`. Gemessen weist das alle drei
  BF-119-Adressen ab — der API-Weg war also nie betroffen —, lehnt aber auch
  `jean-luc@télécom.lu` ab, das der Web-Weg seit dieser Reparatur **annimmt**. Dieselbe
  Adresse führt damit je nach Tür zu einem anderen Ergebnis. — Betreiber

- **OF-01** · Wie soll der Widerruf aussehen (AK-22)? Ein signierter Abmeldelink in
  jeder Mail wäre der übliche Weg und deckte zugleich FB-05 teilweise ab. — Betreiber
  **Entschieden 2026-08-25:** Ja, genau so (BF-37, 2026-08-25). Der Abmeldelink steht in jeder Wartelisten-Mail und löscht den Eintrag — ein Widerruf, nach dem der Datensatz bleibt, ist keiner.

- **OF-02** · Soll `findPendingOlderThan()` an einen Cron gehängt werden (FB-02)? Ein
  Befehl dafür existiert nicht; `app:metrics:snapshot` zeigt das Muster. — Betreiber
- **OF-03** · Sollen beide Wartelisten getrennte Kontingente bekommen (AK-23)?
  **Gemessen am 2026-08-24 (BF-38):** Nach fünf Partner-Submits liefert das
  Organisationsformular 429. Die Frage ist damit nicht mehr, *ob* es passiert, sondern
  ob es so bleiben soll. — Betreiber
  **Entschieden 2026-08-25:** Ja, umgesetzt (BF-38, 2026-08-25): eigener Limiter `organisation_waitlist`.

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Zahlung auf der Seite | keine | Preise stehen nicht fest; ein PRD mit erfundenen Zahlen beschädigte die Glaubwürdigkeit, die das Produkt zu seinem Kernversprechen gemacht hat |
| 2 | Honeypot ohne `Blank`-Constraint | so | ein Validierungsfehler verriete dem Bot die Falle |
| 3 | Honeypot nicht `type="hidden"` | CSS + `aria-hidden` + `tabindex="-1"` | versteckte Felder füllen Bots zuverlässig |
| 4 | Reihenfolge Token → flush → Mail | so | scheitert der Transport, ist die Anmeldung trotzdem gesichert |
| 5 | Token bleibt nach Bestätigung stehen | so | unterscheidet „bereits bestätigt" von „Link ungültig" |
| 6 | Interne Meldung fest auf Deutsch | `trans(…, null, 'de')` | das Team liest Deutsch, unabhängig von der Sprache des Absenders |
| 7 | Erster Turbo-Stream im Projekt | `action="replace"` auf eine DOM-id | kein `<turbo-frame>` nötig |
| 8 | `app.contact_email` als Parameter mit Fallback | statt leerem Default | eine leere Empfängeradresse würfe beim Versand |
