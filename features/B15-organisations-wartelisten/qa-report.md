# B15 · Organisations-Wartelisten — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein niedriger eigener Befund; die schwereren Punkte sind
geerbt und bereits in B14 erfasst.

23 von 23 Kriterien bestanden, 4 von 4 Edge Cases. Die typabhängige Validierung ist der
interessanteste Teil und funktioniert genau wie beschrieben, in beide Richtungen
nachgemessen: Ein untergeschobenes Fremdfeld liefert **422**, derselbe Body ohne dieses
Feld **302**. Das ist die Sorte Prüfung, die man nur mit einer Gegenprobe glauben kann —
ohne sie wäre der 422 auch mit einem ganz anderen Grund erklärbar gewesen.

Die JS-freie Bedienbarkeit steht: Alle drei Feldgruppen liegen im Markup, der Typ ist
über `?type=` und über die Unterseiten vorwählbar, der Selektor bleibt sichtbar. Mit
JavaScript werden die fremden Gruppen ausgeblendet **und** `disabled` gesetzt — im echten
Browser gemessen, inklusive Tab-Reihenfolge und der Ansage in der `aria-live`-Region.

Nächster Aufruf: **`/sdd-erfassen B22`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Seiten und Vorauswahl

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `/de/organisationen` → **200** mit Formular und freier Typwahl |
| AK-02 | ✅ bestanden | `?type=commune` → `commune checked=True`, die anderen beiden `False` — **ohne JavaScript**, direkt im ausgelieferten Markup |
| AK-03 | ✅ bestanden | `/gemeinden`, `/unternehmen`, `/vereine` → je **200**; auf `/gemeinden` sind **3** Typ-Optionen im Markup (Selektor sichtbar), `commune` vorgewählt |
| AK-04 | ✅ bestanden | `/de/organisationen/erfunden` → **404**. Test `testAk04UnbekannterSlugErgibt404` |
| **AK-13** | ✅ bestanden | 31 Sätze über 60 Zeichen auf der Unterseite, **18 davon ausschließlich dort**. Die 13 gemeinsamen stammen alle aus `_integrity.html.twig`, das laut Entwurf auf allen vier Seiten steht. `_section_commune` wird nur in `type.html.twig` eingebunden. Test `testAk13ZielgruppentextStehtNurAufDerUnterseite` |

### Formularverhalten

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-05 / AK-19** | ✅ bestanden | Auf `/gemeinden` liegen **alle** Felder im Markup: `communeName`, `estimatedVenues`, `timeframe`, `sponsorshipInterests`, `collaborationInterests`. `PRE_SET_DATA` baut alle drei Blöcke. Test `testAk05AlleDreiFeldgruppenStehenImMarkup` |
| **AK-06** | ✅ bestanden | Im echten Browser (Brave über CDP) gemessen: nach Wechsel auf `association` sind `communeName`, `estimatedVenues` und `sponsorshipInterests` `sichtbar=false disabled=true`, `collaborationInterests` `sichtbar=true disabled=false`. Die fokussierbaren Felder enthalten **keines** der fremden Gruppen. Die Ansagen: *„Formular auf Unternehmen umgestellt. Die Felder darunter haben sich geändert."* — bei allen drei Wechseln aufgezeichnet |
| **AK-07 / AK-18** | ✅ bestanden | `estimatedVenues=50` bei `type=association` → **422**; derselbe Body ohne das Feld → **302**, Eintrag mit `type=association`, `commune_name=—`, `estimated_venues=—` |
| AK-08 | ✅ bestanden | Folgt aus AK-07: Ohne die Gruppenlogik wäre das Fremdfeld entweder akzeptiert oder still verworfen worden |

### Bestätigung und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-09** | ✅ bestanden | Für `association`: Betreff *„Bestätigen Sie Ihr Interesse am Beirat"*, Text *„…Interesse an einer Mitarbeit im Beirat bekundet"*. Drei Vorlagen vorhanden: `commune.html.twig`, `company.html.twig`, `association.html.twig` |
| AK-10 | ✅ bestanden | `OrganisationController.php:104`: `$type = $entry->getType() ?? OrganisationType::COMMUNE` |
| AK-11 | ✅ bestanden | `companyWebsite` gefüllt → 302, Einträge **1 → 1**, **0 Mails** |
| AK-12 | ✅ bestanden | Bestätigung → 200, `status=confirmed`, `confirmed_at` gesetzt, interne Meldung an `info@endlech.lu`; zweiter Aufruf enthält „bereits"; unbekannter Token → **404** |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-16 | ✅ bestanden | Spalten: `type, organisation_name, contact_name, contact_role, email, phone, website, message, status, confirmation_token, confirmed_at, consent_at, locale, source, commune_name, estimated_venues, timeframe, sponsorship_interests, collaboration_interests, created_at, updated_at` — **keine IP-Adresse** |
| AK-17 | ✅ bestanden | `sponsorship_interests(json)` und `collaboration_interests(json)`; Entity: `private array $sponsorshipInterests = []`, `setSponsorshipInterests()` macht `array_values()`. Inhalt in der DB: `[]` — reine Strings, keine Enum-Cases |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-14** ⚠ | ✅ bestätigt | `OrganisationController.php:85`: `trans('flash.partner_rate_limited')` — der Partner-Schlüssel auf der Organisationsseite. In B14 bereits als **BF-38** erfasst |
| **AK-15** ⚠ | ✅ bestätigt | `findPendingOlderThan()` im `OrganisationWaitlistEntryRepository`: **0 Treffer** (B14 hat wenigstens die ungenutzte Methode). Spalten mit „expires": **0**. Widerrufsrouten: **0**. Alles bereits als BF-36 und BF-37 erfasst |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `OrganisationType.php:55`: `self::ASSOCIATION => 'vereine'`, mit Kommentar *„sonst hieße die Adresse `/organisationen/organisationen`"* |
| EC-02 | ✅ bestanden | Die Checkbox-Gruppen rendern Emoji und Label korrekt (im Markup geprüft, `sponsorshipInterests[]` fünffach vorhanden) |
| EC-03 | ✅ bestanden | `findByType()` nimmt `string`, ruft `OrganisationType::tryFrom()` und gibt bei `null` ein leeres Array zurück — wirft nicht |
| EC-04 | ✅ bestanden | Folgt aus AK-02/AK-03: Die Vorauswahl greift, also finden Model- und Choice-Werte zueinander |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Typabhängige Validierung umgehen** | `estimatedVenues` bei `association` → **422**, nicht still verworfen |
| **Honeypot** | gefüllt → nichts gespeichert, keine Mail |
| **Token raten** | unbekannter 64-Hex-Token → 404 |
| **Rate Limit** | greift — teilt sich aber das Kontingent mit der Partnerliste (BF-38, B14) |
| **Personenbezogene Daten** | keine IP-Adresse, kein User-Agent |
| **Tastaturbedienung** | fremde Felder sind `disabled` und fallen aus der Tab-Reihenfolge — im Browser gemessen |
| **Screenreader-Rückmeldung** | Ansage erfolgt (siehe AK-06), Wortwahl mit einem Vorbehalt → BF-39 |

## Fehler

### BF-39 · Die Typansage sagt „Organisation" statt „Verein" — niedrig

**Betrifft:** AK-06

**Reproduktion:** im Browser den Typ auf „Verein" wechseln, den Inhalt der
`aria-live`-Region mitschreiben:
```
"Formular auf Unternehmen umgestellt. Die Felder darunter haben sich geändert."
"Formular auf Organisation umgestellt. Die Felder darunter haben sich geändert."   ← association
"Formular auf Gemeinde umgestellt. Die Felder darunter haben sich geändert."
```

**Ort:** `src/Enum/OrganisationType.php:31` — `self::ASSOCIATION => 'Organisation'`,
dazu `translations/messages.de.yaml:981` mit demselben Wort.

**Warum das mehr ist als Wortklauberei:** „Organisation" ist auf dieser Seite der
**Oberbegriff für alle drei Typen** — die Seite heißt `/organisationen`, die Entity heißt
`OrganisationWaitlistEntry`, das Formular heißt `organisation_waitlist`. Ein
Screenreader-Nutzer hört „Formular auf Organisation umgestellt" und kann daraus nicht
schließen, welcher der drei Typen jetzt gilt.

Dieselbe Verwechslung war beim Slug bereits erkannt und behoben: `OrganisationType::slug()`
gibt für `ASSOCIATION` bewusst `vereine` zurück, **mit Kommentar im Code** — *„sonst hieße
die Adresse `/organisationen/organisationen`"*. Die Begründung gilt für das Label
wortgleich, wurde dort aber nicht angewandt. Die sichtbare Karte heißt konsequenterweise
„🤲 Für Vereine".

**Vorschlag:** `self::ASSOCIATION => 'Verein'` und den Übersetzungsschlüssel
`organisation.type.association` entsprechend. Zu prüfen ist, wo das Label sonst noch
erscheint — im Verwaltungsbereich (B22) trägt es dieselbe Bedeutung.

## Hinweise ohne Fehlerstatus

- **Vier Fehlbestände sind geerbt und bereits erfasst:** FB-01 (kein Widerruf) → BF-37,
  FB-03 (kein Tokenablauf) → BF-36, FB-05/FB-06 (geteilter Limiter und Meldungsschlüssel)
  → BF-38, FB-04 (`trusted_hosts`) → BF-29. Sie bekommen hier **keine** eigenen Nummern,
  sonst steht dieselbe Sache viermal im Register und wirkt schwerer, als sie ist.
- **FB-02 ist hier schlimmer als in B14.** Dort gibt es wenigstens ein ungenutztes
  `findPendingOlderThan()`. Im `OrganisationWaitlistEntryRepository` fehlt selbst das —
  es gibt nichts, woran eine Aufräumroutine anknüpfen könnte. Zählt zu BF-37.
- **Zwei `aria-live`-Regionen im Formular** — das ist kein Fehler: Die erste
  (`_form.html.twig:40`) ist die Fehlerzusammenfassung, die zweite (Zeile 91,
  `data-organisation-type-target="announcer"`) die Typansage. Ich habe beim ersten Anlauf
  die falsche gemessen und daraus fälschlich geschlossen, die Ansage funktioniere nicht.
  Die Korrektur steht hier, weil ein zweiter Prüfer sonst denselben Weg geht.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Drei in `tests/Functional/Controller/OrganisationControllerTest.php`:
`testAk13ZielgruppentextStehtNurAufDerUnterseite`,
`testAk04UnbekannterSlugErgibt404`,
`testAk05AlleDreiFeldgruppenStehenImMarkup`.

Der erste ist der wertvollste: Er hält die Entscheidung fest, dass Zielgruppentexte nicht
doppelt im Netz stehen — eine Regel, die man beim nächsten Umbau der Übersichtsseite
mühelos verletzt, ohne dass irgendetwas kaputtgeht.

**AK-06 ist bewusst kein PHPUnit-Test.** Die Prüfung braucht einen echten Browser
(`disabled`-Zustand, Tab-Reihenfolge, `aria-live`-Inhalt nach einem Ereignis). Das Skript
liegt unter `scratchpad/e2e/live2.mjs`; die Ausgabe steht oben im Bericht.

**Suite: 352 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B22`. B15 geht auf `approved`; BF-39 steht in `features/befunde.md`.

---

# Zweiter Durchlauf — 2026-09-11

Stand: 2026-09-11 · Vorstufe: `building` · Branch `fix/bf-119-email-validierung`

## Fazit

**Production-ready: ja** — BF-119 ist auch auf dem Organisationsweg behoben und am
laufenden Server belegt. Offen bleiben **ein mittlerer und ein niedriger** Befund,
keiner blockierend.

15 von 19 Kriterien bestanden, **1 nicht mehr zutreffend**, 3 nicht prüfbar.

Anlass war die BF-119-Reparatur. Gemessen an `/de/organisationen`:
`../../etc/passwd@example.lu` → **422 statt 500**, Bestand **5 → 5**. Die typabhängige
Validierung hält unter Beschuss: Ein untergeschobenes Fremdfeld (`estimatedVenues` bei
`type=association`) ergibt **422**, ein erfundener Wert in der JSON-Interessenliste
(`<script>alert(1)</script>`) ebenfalls **422** und **keine Zeile**.

⚠ **Dieser Durchlauf prüft die Arbeit desselben Agenten, der sie gebaut hat** — wie bei
B14. Gegengesteuert mit dem `code-reviewer` und damit, dass jeder Nachweis am laufenden
Server entstand.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `curl /de/organisationen` → **200**; `testLandingPageRendersAllThreeSections` |
| AK-02 | ✅ bestanden | `testTypeCanBePreselectedViaQuery` |
| AK-03 | ✅ bestanden | `testTypePageRendersWithPreselectedType` — drei Datensätze (Gemeinden, Unternehmen, Vereine) |
| AK-04 | ✅ bestanden | `testUnknownTypeSlugReturns404`, `testAk04UnbekannterSlugErgibt404` |
| **AK-05** | ✅ bestanden | Markup ohne JavaScript geholt: `communeName` 1×, `estimatedVenues` 1×, `sponsorshipInterests` 6×, `collaborationInterests` 5× — **alle drei Blöcke stehen da** |
| AK-06 | ⚠️ nicht prüfbar | Setzt einen Browser mit aktivem Stimulus voraus; mit `curl` nicht beobachtbar. Für den Nachweis bräuchte es einen echten Browser (wie bei B03 mit CDP) |
| **AK-07** | ✅ bestanden | Am Server: `estimatedVenues=42` bei `type=association` → **422**, nicht still ignoriert; `testCrossTypeFieldsAreRejected` |
| AK-08 | ✅ bestanden | `testOwnTypeFieldsAreAccepted` (eigener Typ akzeptiert) gegen AK-07 (fremder abgelehnt) |
| AK-09 | ✅ bestanden | `testCommuneSubmissionStoresTypeSpecificFields`, `…CompanySubmissionStoresSponsorshipInterests`, `…AssociationSubmissionStoresCollaborationInterests` |
| **AK-10** | ⚠️ nicht prüfbar | Der Fallback `getType() ?? COMMUNE` (`OrganisationController:127`) sitzt **hinter** der Pflichtvalidierung: Ohne `type` antwortet das Formular mit 422 (`testMissingTypeIsRejected`), der Fallback wird nie erreicht. Über HTTP nicht auslösbar — Verteidigung in der Tiefe, kein beobachtbares Verhalten |
| AK-11 | ✅ bestanden | `testHoneypotIsSilentlyDiscarded` |
| AK-12 | ✅ bestanden | `testConfirmationActivatesEntryAndNotifiesTeam`, `testUnknownTokenReturns404` |
| AK-13 | ✅ bestanden | `testAk13ZielgruppentextStehtNurAufDerUnterseite` |
| **AK-14** ⚠ | ✅ bestanden | `OrganisationController:93` nutzt tatsächlich `flash.partner_rate_limited`. ⚠ **Die Einschätzung der Spec trifft aber nicht zu** — siehe BF-129 |
| **AK-15** ⚠ | ❌ trifft nicht mehr zu | Die Spec sagt „kein Ablauf, kein Widerrufsweg". Beides existiert: `TOKEN_LIFETIME_DAYS = 7` und Route `app_organisations_revoke` (`/{_locale}/organisationen/abmelden/{token}`) — BF-36/BF-37, live seit `v2026.08.29` |
| **AK-16** | ⚠️ nicht prüfbar | `SHOW COLUMNS`: Die aufgezählten Felder sind alle da — die Liste ist aber **nicht mehr vollständig**. `marketing_consent_at` und `self_confirmed_at` kamen über Feature 04 und BF-89 dazu. Ein abschließend aufzählendes Kriterium lässt sich gegen einen erweiterten Bestand nicht mit „bestanden" beantworten (wie B14/AK-17) |
| AK-17 | ✅ bestanden | `information_schema`: `sponsorship_interests` und `collaboration_interests` sind beide `json` |
| AK-18 | ✅ bestanden | belegt durch AK-07: Nur die Felder des übermittelten Typs werden aufgebaut, ein fremdes ergibt 422 |
| AK-19 | ✅ bestanden | belegt durch AK-05: Alle drei Blöcke stehen ohne JavaScript im Markup |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| **Eigener** Rate-Limit-Zähler | ✅ greift | 5 × 302, dann 3 × **429** — eigenes Kontingent, nicht das der Partner (BF-38) |
| Fremdes Typfeld untergeschoben | ✅ 422 | `estimatedVenues` bei `type=association` |
| Erfundener Wert in der JSON-Liste | ✅ 422 | `sponsorshipInterests[]=<script>alert(1)</script>` → keine Zeile in der Datenbank |
| Unbekannter Token | ✅ 404 | `testUnknownTokenReturns404` |
| BF-119 am laufenden Server | ✅ behoben | 422 statt 500, Bestand **5 → 5** |
| Bestätigungstoken im Log | ✅ kein Befund | `doctrine`-Kanal, in `prod` per `!doctrine` ausgeschlossen (BF-06/BF-12) |

## Fehler

### BF-129 · Der Organisationsweg meldet über den Partner-Schlüssel — niedrig

**Betrifft:** AK-14

**Reproduktion:** `grep -n rate_limited src/Controller/OrganisationController.php`
→ Zeile 93: `$this->translator->trans('flash.partner_rate_limited')`

⚠ **Der Schaden ist deutlich kleiner, als die Spec annimmt.** AK-14 nennt das Ergebnis
„eine Partner-Meldung auf der Organisationsseite" und stellt es als klärungsbedürftig
heraus. Der hinterlegte **Text** ist aber in allen vier Sprachen neutral:

> „Sie haben in kurzer Zeit mehrere Anmeldungen abgeschickt. Bitte versuchen Sie es in
> einer Stunde erneut."

Kein Wort von „Partner". **Ein Besucher der Organisationsseite liest nichts Falsches** —
irreführend ist allein der Schlüsselname im Quelltext. Damit ist AK-14 technisch erfüllt
und die daran geknüpfte Sorge gegenstandslos.

⚠ Die zweite Hälfte von AK-14 („zugleich teilen sich beide denselben Limiter-Service")
ist **überholt**: Am Server nachgemessen greift ein eigenes Kontingent (BF-38).

**Vorschlag:** Schlüssel in `flash.waitlist_rate_limited` umbenennen (drei Aufrufer, vier
Kataloge) — oder AK-14 schließen und die Sorge streichen. Beides ist vertretbar; die
Entscheidung gehört zum Betreiber, nicht in diesen Bericht.

---

### BF-130 · Spec und Entwurf beschreiben an sechs Stellen einen überholten Stand — mittel

**Betrifft:** AK-14, AK-15, AK-16, FB-01, FB-03, FB-06 sowie `design.md`

⚠ **Drei der sechs Stellen hat der prüfende Agent übersehen** — sie kamen vom
`code-reviewer` und sind hier einzeln nachgemessen. Geprüft worden waren nur die
Akzeptanzkriterien; der **Fehlbestand-Abschnitt** und `design.md` blieben unangesehen.
Das ist die eigentliche Lehre dieses Durchlaufs: Eine Drift-Prüfung, die nur die
AK-Tabelle liest, findet die Hälfte nicht.

| Stelle | Behauptet | Nachgemessen |
|---|---|---|
| AK-14 | „eine Partner-Meldung" auf der Organisationsseite; geteilter Limiter | Text ist neutral; eigenes Kontingent (BF-129, BF-38) |
| AK-15 | „kein Ablauf des Bestätigungstokens und kein Widerrufsweg" | `TOKEN_LIFETIME_DAYS = 7`; Route `app_organisations_revoke` (BF-36, BF-37) |
| AK-16 | Liste der erfassten Daten ist abschließend | zwei Spalten mehr: `marketing_consent_at`, `self_confirmed_at` |
| **FB-01** (`spec.md:122`) | „Kein Widerrufsweg" | `OrganisationController:223-234` → `app_organisations_revoke` |
| **FB-03** (`spec.md:125`) | „Kein Ablauf des Bestätigungstokens" | `RESULT_EXPIRED` → HTTP 410 (`OrganisationController:175-181`) |
| **FB-06** (`spec.md:128`) | „Kein eigenes Kontingent" | am Server gemessen: eigener Zähler, 429 ab dem sechsten |
| **`design.md:75`** | `limiter.partner_waitlist` — ⚠ **geteilt mit B14** | `#[Autowire(service: 'limiter.organisation_waitlist')]` |

⚠ **Der Fehlbestand wiegt schwerer als die Kriterien.** FB-01 und FB-03 sind als
DSGVO-Lücken erfasst (Art. 7 Abs. 3, Art. 5 Abs. 1 lit. e) — sie stehen dort als *offene
Mängel*, obwohl sie seit dem 2026-08-29 behoben und ausgeliefert sind. Wer die
Datenschutzlage dieses Projekts anhand der Spec beurteilt, kommt zu einem falschen
Ergebnis. Deshalb **mittel** und nicht *niedrig*.

⚠ **Weiterhin zutreffend und ausdrücklich nicht betroffen:** FB-02 („keine Löschfrist,
keine Aufräumroutine") — `OrganisationWaitlistEntryRepository` hat bis heute kein
Gegenstück zu `deleteStaleUnconfirmed()` aus Feature 08. Das ist ein echter, offener
Fehlbestand und keine Drift.

⚠ **Zweite Ausprägung desselben Musters** — bei B14 als BF-126 erfasst, dort mit fünf
Stellen. Die Ursache ist in beiden Fällen dieselbe: Die Rekonstruktion stammt vom
2026-08-24, seither sind BF-36/37/38 behoben und mit `v2026.08.29` ausgeliefert, und
Feature 04 hat Spalten ergänzt. **Die Reparaturen wurden gebucht, die Spezifikationen
nicht.**

Dass es hier zum zweiten Mal auftritt, macht es zu einem Projektmuster und nicht zu einem
Einzelfall — entsprechend in `features/befunde.md` unter *Muster* aufgenommen.

**Vorschlag:** AK-15 in ein erfülltes Kriterium überführen (Verweis auf BF-36/37), AK-16
um die zwei Spalten ergänzen, AK-14 nach BF-129 entscheiden. Zuständig ist eine
Spec-Fortschreibung, **nicht dieser Skill**.

## Was der `code-reviewer` beigetragen hat

Er hat die Kernmechanik unabhängig geprüft und **keinen funktionalen Fehler** gefunden —
Validierung, JSON-Felder und JS-Freiheit bezeichnet er als solide und durch echte
Funktionstests abgesichert. Zwei Beiträge gehen darüber hinaus:

- **Eine zweite Schicht, die der Angriff allein nicht gezeigt hätte:** Die JSON-Listen
  sind nicht nur über `ChoiceType` abgesichert, sondern zusätzlich an der Entity mit
  `#[Assert\All([new Assert\Choice(callback: [SponsorshipInterest::class, 'values'])])]`
  und Validierungsgruppe (`OrganisationWaitlistEntry.php:126, :134`). Dazu per Grep
  belegt: Es gibt **keinen zweiten Schreibpfad** — die Setter werden im gesamten
  `src/`-Baum nur vom Formular gerufen.
- **Drei Drift-Stellen, die dieser Durchlauf übersehen hatte** (FB-01, FB-03, FB-06 und
  `design.md:75`) — eingearbeitet in BF-130, jede einzeln nachgemessen.

Er bestätigte außerdem **BF-125** am aktuellen Code und wies zutreffend darauf hin, dass
es kein neuer Fund ist.

## Neue Prüfläufe dieses Durchlaufs

Keine. Die Absicherung von BF-119 entstand beim Bauen; dieser Durchlauf hat sie am
laufenden Server gegengeprüft. Der einzige Testbedarf, der sich zeigt, ist BF-125 —
dort erfasst, nicht hier.

## Nächster Schritt

**`/sdd-qa B01`** — das dritte und letzte Feature auf `building`. Es ist eigenständiger
als B14 und B15: eigener Controller, eigener Mailversand, Anti-Enumeration mit
Timing-Angleich. Danach können alle drei zusammen ausgeliefert werden.

⚠ Bis dahin bleibt BF-119 auf Produktion aktiv — auf **allen drei** Wegen.

---

# Nachtrag aus QA Feature 11 — 2026-09-13

Stand: 2026-09-13 · Vorstufe: `deployed` → **`review`** · kein eigener Durchlauf von B15, sondern ein Fund beim
Prüfen der Nutzungsmessung (Feature 11, AK-15), der B15 betrifft.

## Fazit

**Production-ready: nein** — ein hoher Befund, der **auf der Produktion aktiv ist**.

## Fehler

### BF-151 · Eintragen von den Zielgruppenseiten endet in einer 405-Fehlerseite — hoch

**Betrifft:** AK-03 (Zielgruppenseite mit vorgewähltem Typ — die Seite erscheint, ihr Formular funktioniert
nicht) und AK-09 (eine gültige Anmeldung bekommt die typspezifische Mail — von hier aus entsteht keine).
**Reproduktion:**
1. `/de/organisationen/gemeinden`, `…/unternehmen` oder `…/vereine` im Browser öffnen, Organisation, Kontakt,
   E-Mail und Einwilligung ausfüllen, absenden (`qa/11/wartelisten-browser.mjs`, Ausgabe daneben).
2. `tests/Functional/Controller/Qa11ZielgruppenFormularTest.php` ohne `markTestSkipped`: drei Fälle rot mit
   „Failed asserting that 405 is not identical to 405".
**Erwartet:** wie auf `/de/organisationen` — Weiterleitung, Eintrag gespeichert, Bestätigungsmail.
**Tatsächlich:** `POST /de/organisationen/<slug>` → **405**, sichtbar „Oops! An Error Occurred — The server returned
a "405 Method Not Allowed"", **0** Einträge, die eingegebenen Daten sind weg. `form_start()` setzt kein `action`,
der Browser schickt an die aktuelle Adresse, und `app_organisations_type` kennt nur GET; `app_organisations_submit`
liegt unter `/organisationen`.
**Produktion:** nur lesend geprüft — `GET https://endlech.lu/de/organisationen/gemeinden` (200) rendert dasselbe
Formular ohne `action`; `master` trägt dieselben Routen. Kein POST an die Produktion. Sentry meldet es nicht,
405 steht in `ignore_exceptions`. ⚠ Seit Feature 10 stehen die drei Seiten in der Sitemap — der Weg, auf dem
Gemeinden aus der Suche heraus ankommen, ist genau der kaputte.
**Warum drei Durchläufe es nicht sahen:** Alle Absende-Tests in `OrganisationControllerTest` holen das Formular
von der Übersicht, wo die aktuelle Adresse zufällig die POST-Route ist; die Zielgruppenseiten werden nur per GET
geprüft (Überschrift, vorgewählter Typ, Teaser). Der erste Durchlauf dieses Berichts prüfte AK-03 als bestanden —
richtig für das, was dort steht, und blind für das Formular darunter.
**Ort:** `templates/organisation/_form.html.twig:33`, `src/Controller/OrganisationController.php` (`type()`).
**Vorschlag:** Das Formular ausdrücklich an `app_organisations_submit` richten; der Prüflauf holt es von jeder
Zielgruppenseite (die Reproduktion tut das bereits).

## Nächster Schritt

**`/sdd-build B15 BF-151 beheben`**, danach `/sdd-qa B15` und Auslieferung. Laut Prüfregel für Bestandsfeatures
geht das vor dem nächsten Feature.
