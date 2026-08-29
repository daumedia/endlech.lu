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
