# B15 · Organisations-Wartelisten — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

> **Abgeglichen am 2026-09-12 (BF-130).** Sechs Stellen beschrieben einen überholten
> Stand: AK-14 (zweite Hälfte), AK-15, AK-16 sowie FB-01, FB-03 und FB-06 — dazu FB-04
> (BF-134). **FB-01 und FB-03 standen hier als DSGVO-Lücken** (Art. 7 Abs. 3, Art. 5
> Abs. 1 lit. e), obwohl seit dem 2026-08-29 behoben und ausgeliefert; wer die
> Datenschutzlage anhand dieser Datei beurteilte, kam zu einem falschen Ergebnis.
> **FB-02 ist nicht betroffen und weiterhin offen.**
>
> ⚠ Diese Datei ist die **Rekonstruktion** eines Bestandsfeatures und veraltet mit jeder
> Reparatur, die sie behebt. Drei der sechs Stellen hat erst der `code-reviewer`
> gefunden — der prüfende Agent hatte nur die AK-Tabelle angesehen, nicht den
> Fehlbestand und nicht `design.md`.

## Zweck

Drei Zielgruppen mit je eigener Seite und eigener Bestätigungsmail: **Gemeinden**
(bezahlter Auftrag), **Unternehmen** (Sponsoring) und **Vereine** (Beirat, kein
Geldfluss in beide Richtungen). Übersicht unter `/{locale}/organisationen`, dazu je
eine Unterseite.

Der kommerzielle Unterschied ist der Grund für den Zuschnitt: Ein gemeinsames Formular
mit einem Auswahlfeld hätte drei grundverschiedene Beziehungen zu einem Vorgang
verschmolzen.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B14 | rekonstruiert | teilt Service, Interface, Enum, Templates und Rate-Limiter |

## User Stories

- **US-01** · Als Gemeindevertreter möchte ich eine Erhebung für meine Gemeinde anfragen.
- **US-02** · Als Unternehmen möchte ich Sponsoring-Interessen angeben.
- **US-03** · Als Verein möchte ich Formen der Zusammenarbeit vorschlagen.
- **US-04** · Als Interessent möchte ich das Formular auch **ohne JavaScript**
  ausfüllen können.

## Nicht im Scope

- Partnerprogramm für Restaurants → B14
- Verwaltung → B22

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher ruft `/{locale}/organisationen` auf, wenn die
  Seite lädt, dann sieht er Hero, drei Zielgruppenkarten, den Integritätsblock und ein
  Formular mit **freier** Typwahl.
- **AK-02** · Angenommen, `?type=commune` steht in der Adresse, wenn die Seite lädt,
  dann ist der Formulartyp vorgewählt — **ohne JavaScript**.
- **AK-03** · Angenommen, `/{locale}/organisationen/gemeinden`, `…/unternehmen` oder
  `…/vereine` wird aufgerufen, wenn die Seite lädt, dann erscheint die Zielgruppenseite
  mit vorgewähltem Typ; der Selektor bleibt **sichtbar**, damit ein falsch Gelandeter
  ohne Umweg wechseln kann.
- **AK-04** · Angenommen, ein unbekannter Slug wird aufgerufen, wenn die Anfrage
  durchläuft, dann greift das Routen-Requirement `gemeinden|unternehmen|vereine` und
  die Route wird nicht gefunden.
- **AK-05** · Angenommen, kein JavaScript ist aktiv, wenn das Formular betrachtet wird,
  dann sind **alle drei** Feldgruppen sichtbar und beschriftet.
- **AK-06** · Angenommen, JavaScript ist aktiv, wenn der Typ gewechselt wird, dann
  werden die Felder der nicht gewählten Typen ausgeblendet **und auf `disabled`
  gesetzt** — sie fallen damit aus der Tab-Reihenfolge; der Wechsel wird in einer
  `aria-live`-Region angesagt.
- **AK-07** · Angenommen, ein Feld eines **fremden** Typs wird untergeschoben (etwa
  `estimatedVenues` bei einem Vereinseintrag), wenn abgeschickt wird, dann antwortet
  der Server mit **422** — es wird nicht stillschweigend ignoriert.
- **AK-08** · Angenommen, ein Typ ist gewählt, wenn die Validierung läuft, dann gilt die
  Validierungsgruppe `['Default', <typ>]`; die fremden Felder tragen dort `IsNull` bzw.
  `Count(max: 0)`.
- **AK-09** · Angenommen, eine Anmeldung ist gültig, wenn sie abgeschickt wird, dann
  bekommt der Interessent die **typspezifische** Bestätigungsmail
  (`email/organisation/{commune|company|association}.html.twig`) mit typspezifischem
  Betreff.
- **AK-10** · Angenommen, kein Typ ist übermittelt, wenn der Versand vorbereitet wird,
  dann fällt der Code auf `OrganisationType::COMMUNE` zurück.
- **AK-11** · Angenommen, das Honeypot-Feld `companyWebsite` ist gefüllt, wenn
  abgeschickt wird, dann ist die Antwort identisch zum Erfolgsfall, ohne Speichern und
  ohne Mail.
- **AK-12** · Angenommen, eine Bestätigung erfolgt, wenn sie durchläuft, dann verhält
  sich alles wie in B14: Status, `confirmedAt`, interne Meldung, drei Zustände auf der
  Bestätigungsseite, 404 bei unbekanntem Token.
- **AK-13** · Angenommen, `/{locale}/organisationen` und eine Zielgruppenseite werden
  verglichen, wenn die Texte betrachtet werden, dann steht der ausführliche Inhalt
  **nur** auf der Unterseite — die Übersicht zeigt Teaser, damit derselbe Text nicht
  doppelt im Netz steht.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-14** ⚠ · Angenommen, das Rate-Limit greift auf der Organisationsseite, wenn die
  Meldung gelesen wird, dann steht dort `flash.partner_rate_limited` — eine
  Partner-Meldung auf der Organisationsseite.
  *(So verhält sich der Code heute: `OrganisationController:93` verwendet denselben
  Übersetzungsschlüssel wie `PartnerController`. Gilt weiterhin, offen als BF-129 —
  ⚠ der Schaden ist kleiner als hier angenommen: Der hinterlegte **Text** ist in allen
  vier Sprachen neutral („Sie haben in kurzer Zeit mehrere Anmeldungen abgeschickt"),
  ein Besucher liest also nichts Falsches; irreführend ist allein der Schlüsselname.)*
  ⚠ **Die zweite Hälfte ist überholt (BF-130):** ~~Zugleich teilen sich beide denselben
  Limiter-Service, siehe B14/AK-23.~~ Seit BF-38 (live mit `v2026.08.29`) hat jede
  Warteliste ihr eigenes Kontingent; am 2026-09-12 über Verhalten belegt.

- **AK-15** ~~⚠ · Wie B14/AK-21 und B14/AK-22: **kein Ablauf des Bestätigungstokens** und
  **kein Widerrufsweg**. Beides liegt im geteilten Service bzw. in der Entity und gilt
  hier gleichermaßen.~~ — **überholt, beides behoben (BF-36/BF-37), live seit
  `v2026.08.29`.**
  **Heute gilt:** Der Token verfällt sieben Tage nach `createdAt` (abgelaufener Link →
  HTTP 410), und jede Mail trägt den Abmeldelink (`app_organisations_revoke`). Damit
  ist Art. 7 Abs. 3 DSGVO erfüllt — die alte Fassung dieser Zeile führte die Lücke
  weiter, obwohl sie zwei Wochen zuvor geschlossen worden war.

### Datenschutz und Missbrauchsschutz

- **AK-16** · Angenommen, ein Eintrag entsteht, wenn geprüft wird, welche
  personenbezogenen Daten er trägt, dann sind es: Organisationsname, Ansprechpartner,
  **Funktion im Haus**, E-Mail, Telefon, Website, Freitext, Einwilligungszeitpunkt,
  Sprache, Quelle — dazu typabhängig Gemeindename, geschätzte Zahl der Betriebe,
  Zeitrahmen bzw. Interessenlisten.
  ⚠ **Zwei Felder fehlten in der Aufzählung (BF-130, wie B14/AK-17):**
  `marketingConsentAt` (eigene Einwilligung für den Brevo-Verteiler, Feature 04) und
  `selfConfirmedAt` (Zeitpunkt der Selbstbestätigung — er unterscheidet den
  Doppel-Opt-in vom Weitersetzen durch die Verwaltung, BF-89). Eine Auskunft nach der
  alten Liste wäre lückenhaft gewesen.
- **AK-17** · Angenommen, die Interessenlisten werden betrachtet, wenn ihr Typ geprüft
  wird, dann sind es **JSON-Spalten mit Strings**, nicht mit Enum-Cases.
- **AK-18** · Angenommen, `PRE_SUBMIT` läuft, wenn geprüft wird, welche Felder aufgebaut
  werden, dann nur die des **übermittelten** Typs — das ist die Grundlage von AK-07.
- **AK-19** · Angenommen, `PRE_SET_DATA` läuft, wenn geprüft wird, welche Felder
  aufgebaut werden, dann **alle drei Blöcke** — das ist die Grundlage von AK-05.

## Edge Cases

- **EC-01** · `OrganisationType::slug()` gibt für `ASSOCIATION` bewusst `vereine` zurück
  — sonst hieße die Adresse `/organisationen/organisationen`.
- **EC-02** · Bei `expanded: true` ist `choice.vars.data` der **Checked-Zustand (bool)**,
  nicht der Enum-Case. Für Emoji und Label im Template wird deshalb eine Map
  `value → Case` aus den übergebenen `types` gebaut.
- **EC-03** · `findByType()` im Repository nimmt bewusst **Strings** aus
  Query-Parametern entgegen und verwirft unbekannte Werte, statt zu werfen.
- **EC-04** · Die Choices sind reine Strings: Mit Enum-Cases fänden Model- und
  Choice-Werte nicht zueinander, nichts wäre vorausgewählt und es bräuchte einen
  Transformer.

## Fehlbestand

Alles aus B14 gilt hier gleichermaßen — der Unterbau ist derselbe:

- ~~**FB-01 · Kein Widerrufsweg** (B14/FB-01).~~ — **erledigt** (BF-37, live seit
  `v2026.08.29`). ⚠ In der alten Fassung stand er als **DSGVO-Lücke nach Art. 7 Abs. 3**
  — wer die Datenschutzlage anhand dieser Datei beurteilte, kam zu einem falschen
  Ergebnis.
- **FB-02 · Keine Löschfrist, keine Aufräumroutine.** `OrganisationWaitlistEntryRepository`
  hat nicht einmal ein `findPendingOlderThan()`-Gegenstück. ⚠ **Am 2026-09-12
  nachgeprüft und weiterhin offen** (die Methodenliste des Repositories kennt es nach
  wie vor nicht); der Aufräumlauf aus Feature `08` greift nur auf die App-Warteliste.
  Ausdrücklich **nicht** von BF-130 betroffen.
- ~~**FB-03 · Kein Ablauf des Bestätigungstokens** (B14/FB-03).~~ — **erledigt**
  (BF-36, live seit `v2026.08.29`): sieben Tage, gemessen an `createdAt`. ⚠ Stand hier
  ebenfalls als DSGVO-Lücke (Art. 5 Abs. 1 lit. e).
- ~~**FB-04 · Kein `trusted_hosts`** (B01/FB-09).~~ — **erledigt am 2026-09-12**
  (BF-134): feste Liste in `framework.trusted_hosts`, Angriff antwortet mit HTTP 400.
- **FB-05 · Eigener Übersetzungsschlüssel für das Rate-Limit fehlt.** Siehe AK-14.
  ⚠ Weiterhin offen (BF-129, *niedrig*) — der angezeigte Text ist neutral, falsch ist
  nur der Schlüsselname.
- ~~**FB-06 · Kein eigenes Kontingent.** Siehe B14/AK-23.~~ — **erledigt** (BF-38, live
  seit `v2026.08.29`): `limiter.organisation_waitlist`.

## Offene Fragen

- **OF-BF151a** · **Nach einem Eingabefehler auf einer Zielgruppenseite erscheint die Übersicht** (beim Beheben
  von BF-151 am 2026-09-13 aufgefallen). Seit das Formular ausdrücklich an `app_organisations_submit` schickt,
  kommt die Eintragung von `/organisationen/vereine` an; fehlt ein Pflichtfeld, antwortet `submit()` mit
  `renderLandingPage()` — **422 unter `/organisationen`**, Fehlermeldungen und vorgewählter Typ bleiben erhalten,
  der ausführliche Zielgruppentext aber nicht (gemessen: Überschrift wechselt von „Für Organisationen und Vereine"
  zur Übersicht). Vorher endete derselbe Weg in einer 405-Fehlerseite, es ist also eine Verbesserung, keine
  Verschlechterung. Offen ist, ob der Fehlerfall die Zielgruppenseite erneut rendern soll — das wäre neues
  Verhalten und steht nicht im Auftrag. — Betreiber.
- **OF-BF119a** · Fünf weitere `new Email(...)` stehen weiterhin im HTML5-Default —
  `ProfileType`, `PasswordResetRequestType`, `AccessibilityReportType`,
  `RestaurantSuggestionType`, `RestaurantType`. Die ersten drei lösen einen Mailversand
  an **genau diese** Adresse aus und haben damit dieselbe Lücke wie BF-119; die beiden
  letzten speichern die Adresse nur. Beim Bauen von BF-119 gemessen, **bewusst nicht
  mitrepariert**: Der Auftrag nannte drei Formulare, und was nicht im Auftrag steht,
  wird nicht gebaut. — Betreiber

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

- **OF-01** · Sollen die drei Typen getrennte Kontingente bekommen? Eine Gemeinde und
  ein Unternehmen hinter derselben Verwaltungs-IP blockieren sich heute gegenseitig. —
  Betreiber
- **OF-02** · Vereine bringen ausdrücklich kein Geld. Soll ihre Anmeldung trotzdem
  denselben Trichter durchlaufen (`WaitlistStatus` bis `converted`)? — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Ein Formular oder drei | eines mit Typwahl, drei Feldblöcken | die Typen sind kommerziell verschieden, der Vorgang ist derselbe |
| 2 | `PRE_SET_DATA` baut alle Blöcke | ja | Voraussetzung der JavaScript-freien Bedienung (AK-05) |
| 3 | `PRE_SUBMIT` baut nur den gewählten | ja | ein untergeschobenes Fremdfeld ist damit ein unerlaubtes Zusatzfeld → 422 |
| 4 | Validierung über Gruppen | `validation_groups` aus `$type` | zweite Schicht neben `PRE_SUBMIT` |
| 5 | Choices als Strings | ja | die JSON-Spalten speichern `string[]`; Enum-Cases bräuchten einen Transformer |
| 6 | Eigene Seite je Zielgruppe | ja | verhindert doppelten Text im Netz und erlaubt zielgruppengenaue Ansprache |
| 7 | Slug `vereine` für `ASSOCIATION` | ja | sonst `/organisationen/organisationen` |
| 8 | Eigene Bestätigungsmail je Typ | drei Vorlagen | die Beziehung ist je Typ eine andere |
