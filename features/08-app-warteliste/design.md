# 08 · Warteliste für die mobile App — Systemdesign

Status: `architected` · Stand: 2026-09-04 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Eine **dritte Warteliste** neben Partner (B14) und Organisationen (B15), gebaut aus
denselben Bausteinen. Der Besucher öffnet `/{locale}/app`, gibt seine Adresse an, wählt
iOS oder Android und schickt ab. Der bestehende `WaitlistConfirmationService` legt den
Eintrag an, erzeugt den Token und verschickt die Bestätigungsmail — dieselbe Mechanik wie
bei den beiden anderen Listen, einschließlich der 7-Tage-Frist und des Abmeldelinks, die
dort bereits eingebaut sind.

Der einzige echte Zuwachs ist die **zweite Mail nach dem Bestätigungsklick**: Sie trägt
bei iOS den TestFlight-Link und bei Android den Hinweis, dass es noch nichts gibt. Für
diese Mail existiert im Bestand kein Weg — `WaitlistConfirmationService` kennt nur die
Bestätigungsmail an den Interessenten und die interne Meldung ans Team.

Drei Stellen im Bestand müssen mitziehen, sonst wirkt das Feature nur halb: die
Herkunftsauflösung im Marketing-Register (sonst landet die neue Quelle als „Konto"), die
Quellensuche ebendort (sonst greift der Widerruf nicht bis Brevo durch) und die
Zeilennormalisierung der Verwaltungsliste (sonst erscheint kein Eintrag).

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_app_waitlist` | `/{_locale}/app` (GET) | Seite mit Erklärung und Anmeldeformular | öffentlich |
| `app_app_waitlist_submit` | `/{_locale}/app` (POST) | Anmeldung entgegennehmen | öffentlich |
| `app_app_waitlist_confirm` | `/{_locale}/app/confirmation/{token}` (GET) | Token einlösen, zweite Mail auslösen | öffentlich, Token ist der Nachweis |
| `app_app_waitlist_revoke` | `/{_locale}/app/abmelden/{token}` (GET) | Eintrag löschen (Art. 7 Abs. 3 DSGVO) | öffentlich, Token ist der Nachweis |
| `app_app_waitlist_redirect` | `/app` (locale-frei) | Weiterleitung auf die Sprachfassung | öffentlich |

**Token-Bedingung** `[a-f0-9]{64}` an beiden Token-Routen — dieselbe wie bei
`app_partner_confirm`. Ein Token mit falschem Format wird dadurch von der Routing-Schicht
abgewiesen, bevor irgendeine Abfrage läuft (AK-27).

**Die Weiterleitung ist bewusst temporär (302), nicht permanent.** Ein 301 bliebe in
fremden Browsern stehen; das war der teure Teil von BF-100, nicht die Schleife selbst.

⚠ **Unter `public/` darf kein Verzeichnis `app` entstehen.** Auf Apache schickte
`mod_dir` sonst jeden Aufruf von `/app` per 301 auf `/app/`, während Symfonys
Trailing-Slash-Regel zurückschickt — eine Endlosschleife, die lokal unsichtbar bleibt.
`RouteDirectoryCollisionTest` prüft diese Ursache bereits projektweit; die neue Route ist
damit ohne Zutun abgedeckt (AK-58).

**Die Seite nach dem Erfolg** ist keine eigene: Mit Turbo wird das Formular ersetzt, ohne
Turbo führt ein Redirect zurück auf `app_app_waitlist` mit Flash-Meldung. Beides
existiert als Muster (`partner/success.stream.html.twig`, `_waitlist_success.html.twig`).

**Zugriffsregel in `security.yaml`:** Der Pfad fällt unter die bestehende Regel für
`^/[a-z]{2}/` mit `PUBLIC_ACCESS`. Die locale-freie Weiterleitung `/app` fällt unter
**keine** Web-Regel — genau wie `/open`, `/presse` und `/roadmap`. Sie ist ein reiner
`RedirectController` ohne Datenzugriff; das ist zulässig, gehört aber geprüft, weil das
Stack-Profil ausdrücklich davor warnt, dass ein Pfad ohne Locale-Präfix von keiner Regel
gedeckt sein kann.

## Komponentenstruktur

```
Seite /{locale}/app
├── Hero-Band                    Verlauf from-cyan-700 to-purple-800, Leitzahl: keine
│   ├── Überschrift              „Endlech.lu als App"
│   └── Kurztext                 was die App kann, ehrlich: PWA deckt heute schon ab
├── Statusblock                  zwei Karten nebeneinander
│   ├── Karte iOS                „Beta läuft" — Link kommt nach der Bestätigung
│   └── Karte Android            „noch nichts gebaut" — Vormerkung zählt als Bedarf
├── Anmeldeformular              DOM-id `app-waitlist-form` (Turbo-Ziel)
│   ├── Plattformwahl            Segmented Control, zwei Segmente, keine Vorauswahl
│   │   ├── Hinweis iOS          steht am Segment, ohne JavaScript sichtbar
│   │   └── Hinweis Android      dito — trägt die Ehrlichkeitszusage aus US-02
│   ├── Feld E-Mail              via _form_field.html.twig
│   ├── Kästchen Einwilligung    Pflicht, ungemappt (Zeitpunkt wird gespeichert)
│   ├── Kästchen Werbung         freiwillig, ungemappt, nicht vorausgewählt
│   ├── Honeypot-Feld            CSS-versteckt, aria-hidden, tabindex -1
│   └── Absendeschaltfläche      min-h-[48px]
└── Rechtlicher Hinweis          Verweis auf den Datenschutzabschnitt in /legal

Bestätigungsseite /{locale}/app/confirmation/{token}
└── _waitlist_confirmation.html.twig   geteilt mit B14/B15, fünf Zustände

Abmeldeseite /{locale}/app/abmelden/{token}
└── dieselbe Vorlage, Zustand `revoked`
```

**Wiederverwendet, nicht neu gebaut:** `_form_field.html.twig`,
`_tristate_field.html.twig` (als Vorbild für den Segmented Control),
`_waitlist_success.html.twig`, `_waitlist_confirmation.html.twig`,
`_flash_messages.html.twig`, `email/base.html.twig`.

### Die vier Zustände je Bildschirm

| Bildschirm | leer | ladend | Fehler | gefüllt |
|---|---|---|---|---|
| Formular | Normalzustand — nichts ausgefüllt, kein Segment gewählt | Absendeknopf zeigt Ladezustand (Turbo); ohne JavaScript lädt die Seite neu | 422, Meldungen an den Feldern, `autofocus` auf dem ersten Fehler | Erfolgsmeldung ersetzt das Formular |
| Bestätigungsseite | — (immer ein Token vorhanden) | — (eine Abfrage) | 404 unbekannt · 410 abgelaufen | „bestätigt" / „bereits bestätigt" |
| Abmeldeseite | — | — | 404 bei unbekanntem Token | „ausgetragen" |
| Admin-Liste | „noch keine Anmeldungen" (bestehender Leerzustand von B22) | — | — | Zeilen mit Plattform-Abzeichen |

Der **Ladezustand** ist hier fast bedeutungslos: Es gibt keinen KI-Aufruf und keine
fremde Schnittstelle im Anfrageweg. Der Mailversand läuft asynchron über Messenger, der
Besucher wartet also nicht auf ihn.

## Datenmodell

### Neue Tabelle `app_waitlist_entry`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | `integer` PK | ja | |
| `email` | `varchar(180)` | ja | die einzige Kontaktangabe |
| `platform` | `varchar(20)` | ja | `AppPlatform` (`ios` \| `android`), Doctrine-Enum |
| `status` | `varchar(20)` | ja | `WaitlistStatus`, Vorgabe `pending` — **derselbe** Enum wie B14/B15 |
| `confirmationToken` | `varchar(64)` | nein | **unique**, Double-Opt-In und zugleich Abmeldetoken |
| `confirmedAt` | `datetime` | nein | ⚠ zweideutig wie in B14: auch vom Verwaltungs-Statuswechsel gesetzt |
| `selfConfirmedAt` | `datetime` | nein | Zeitpunkt der **Selbst**bestätigung; setzt allein `confirm()` (BF-89) |
| `consentAt` | `datetime` | ja | DSGVO-Nachweis, im Konstruktor gesetzt |
| `marketingConsentAt` | `datetime` | nein | Werbe-Einwilligung; `null` = keine (Feature 04) |
| `betaLinkSentAt` | `datetime` | nein | wann die zweite Mail hinausging; `null` = noch nicht |
| `locale` | `varchar(5)` | ja | Sprache der Anmeldung |
| `source` | `varchar(60)` | nein | UTM-Quelle oder Referrer-Host |
| `createdAt` | `datetime` | ja | **trägt zugleich die 7-Tage-Frist** — keine eigene Ablaufspalte |
| `updatedAt` | `datetime` | ja | per `#[ORM\PreUpdate]`, im Konstruktor vorbelegt |

**Beziehungen: keine.** Der Eintrag hat bewusst **keinen** Fremdschlüssel auf `User` —
auch dann nicht, wenn ein angemeldeter Nutzer ihn anlegt. Begründung unter
*Technische Entscheidungen*, Punkt 5.

**Indizes**

| Index | Spalten | Wofür |
|---|---|---|
| `UNIQ_app_waitlist_token` | `confirmation_token` | Einlösung und Widerruf, ein Treffer je Token |
| `UNIQ_app_waitlist_email` | `email` | erzwingt AK-15/AK-16 **auf Datenbankebene**, nicht nur in der Anwendung |
| `IDX_app_waitlist_status_created` | `(status, created_at)` | Aufräumlauf (AK-47) und die gefilterte Verwaltungsliste |

⚠ **Der Unique-Index auf `email` ist der Unterschied zu B14/B15** — dort gibt es ihn
nicht, und jeder Submit erzeugt dort einen weiteren Eintrag. Hier ist „eine Adresse, ein
Eintrag" ein Akzeptanzkriterium, und eine Prüfung allein im Controller verliert das
Wettrennen zweier gleichzeitiger Absendevorgänge (EC-06).

⚠ **Die Adresse wird kleingeschrieben und beschnitten gespeichert.** `MarketingContact`
sucht mit `mb_strtolower(trim(...))`; stünde hier `Max@Example.LU`, fände die
Löschkaskade den Eintrag nicht — und der Unique-Index griffe bei
`max@example.lu` nicht.

**Was beim Löschen geschieht**

| Auslöser | Folge |
|---|---|
| Abmeldelink (AK-31) | Zeile wird **gelöscht**; vorher Löschauftrag im Marketing-Auftragsbuch |
| Aufräumlauf nach 30 Tagen (AK-47) | Zeile wird gelöscht, sofern `selfConfirmedAt` leer ist |
| Kontolöschung (AK-50) | Zeile wird über Adressgleichheit mitgelöscht — **Feature `01`** |
| Löschen in der Verwaltung | nicht vorgesehen; B22 kennt keine Löschfunktion (offener Fehlbestand dort) |

### Änderungen an bestehenden Bausteinen

Alles Folgende sind **Änderungen**, keine Neuanlagen.

| Wo | Änderung | Warum unverzichtbar |
|---|---|---|
| `App\Enum\MarketingOrigin` | neuer Case `APP` mit `transKey()`, `label()`, `brevoValue()` | ohne ihn fiele die neue Quelle in den `ACCOUNT`-Zweig von `originOf()` — der Code sagt das an Ort und Stelle selbst voraus |
| `MarketingContactRegistry::originOf()` | dritter Zweig für die neue Entity | siehe oben |
| `MarketingContactRegistry::sourcesFor()` | neue Entity in die Klassenliste | **sonst greift AK-32 nicht.** `scheduleRemoval()` prüft über `aktiveQuellen()`, ob eine andere Quelle den Kontakt am Leben hält; eine unbekannte Quelle wird dort weder gefunden noch berücksichtigt (BF-84) |
| `AdminWaitlistController` | dritte Zeilenart `appRow()`, dritter Quellen-Filter, Detailansicht, Statuswechsel | AK-35 |
| `WaitlistConfirmationService` | neue Methode für die **zweite Mail an den Interessenten** | im Bestand gibt es nur `register()` (erste Mail) und `notifyTeam()` (an das Team) |
| `MarketingScheduleProvider` | zweiter Eintrag: täglicher Aufräumlauf | AK-47/AK-49; Begründung unter Entscheidung 7 |
| Fußzeile in `base.html.twig` | Eintrag in **Spalte 4** unter Roadmap/Changelog | Spalte 2 trägt bereits elf Einträge; eine fünfte Spalte bräche `lg:grid-cols-4` |
| Startseite | Hinweisband zwischen „Warum Endlech.lu?" und dem Handlungsaufruf | AK-40 |
| `OpenStatsService::computePlatform()` | drei Zahlen (gesamt, iOS, Android) hinter der Schwelle | AK-37/AK-38 |
| `config/packages/framework.yaml` | Limiter `app_waitlist` (10/Stunde) + `when@test`-Override auf 10000 | AK-44/AK-46 |
| `config/routes.yaml` | locale-freie Weiterleitung `/app` | AK-02 |
| Übersetzungen | neue Schlüssel in `messages.*` und `validators.*`, vier Sprachen | AK-55 |

### Neuer Enum `App\Enum\AppPlatform`

| Case | Wert | Bedeutung |
|---|---|---|
| `IOS` | `ios` | iPhone/iPad — Beta läuft |
| `ANDROID` | `android` | noch nichts gebaut |

Methoden im Stil von `TriState` und `OrganisationType`: `transKey()`, `label()`,
`emoji()`, `badgeClasses()` und **`hasBeta(): bool`**.

⚠ **`hasBeta()` gehört an den Enum, nicht ins Template.** Die Frage „gibt es für diese
Plattform eine Beta" wird an vier Stellen gestellt: im Hinweis am Segment, in der zweiten
Mail, in deren Betreffzeile und in der Verwaltungsliste. Vier `{% if platform == 'ios' %}`
laufen beim ersten Android-Build auseinander — und dann steht auf einer der vier Stellen
noch „noch nichts gebaut".

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Jeder Besucher | nichts aus der Tabelle | einen neuen Eintrag anlegen | öffentliche Route; Rate Limit deckelt die Menge |
| Inhaber eines gültigen Tokens | den eigenen Eintrag (implizit, über die Bestätigungsseite) | den eigenen Eintrag bestätigen oder löschen | Token-Abgleich in der Abfrage; 64 Hex-Zeichen sind nicht erratbar |
| Angemeldeter Nutzer ohne Admin-Rolle | nichts | nichts über die öffentliche Route hinaus | `access_control` auf `^/[a-z]{2}/admin` → `ROLE_ADMIN` |
| `ROLE_ADMIN` | alle Einträge in `/admin/warteliste` | Status ändern | `access_control` **und** `#[IsGranted('ROLE_ADMIN')]` auf `AdminWaitlistController` (bereits vorhanden) |

⚠ **Es gibt keine zweite Schicht.** Das Stack-Profil ist ausdrücklich: ohne RLS ist die
Anwendung die einzige Grenze. Was hier nicht geprüft wird, ist ungeprüft.

⚠ **Kein IDOR-Weg im öffentlichen Teil.** Einträge sind niemals über eine laufende Nummer
erreichbar — es gibt keine Route `/app/{id}`. Der einzige Zugang ist der Token. Die
Verwaltung adressiert dagegen über `{id}`, und dort schützt die Rollenschranke.

⚠ **Die Adresse steht nicht in der Verwaltungsliste.** B22 zeigt sie bewusst nicht; sie
wird nur zum Nachschlagen des Brevo-Sync-Zustands mitgeführt. Die neue Zeilenart hält
sich daran.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `POST /{locale}/app` | 10 je IP und Stunde, `sliding_window` | HTTP **429**, Flash mit Wartezeit, kein Eintrag, keine Mail | `config/packages/framework.yaml`, Limiter `app_waitlist`; im Controller über `ActionLimiter` |
| `GET /{locale}/app` | keins | — | die Seite ist rein lesend und lädt keinen Bestand |
| `GET …/confirmation/{token}` | keins | — | Der Token ist 64 Hex-Zeichen; Raten ist aussichtslos, und ein Deckel träfe den, der seine Mail zweimal öffnet |
| `GET …/abmelden/{token}` | keins | — | dieselbe Begründung; ein Deckel auf dem Widerrufsweg wäre eine Hürde auf der falschen Seite |

**Der Verbrauch steht dort, wo die Handlung stattfindet** — nach Honeypot- und
Formularprüfung, unmittelbar vor dem Anlegen. Fünf Tippfehler dürfen niemanden aussperren
(BF-11). Die Abfrage läuft über `ActionLimiter::isAllowed()`; `consume(0)` ist **keine**
Prüfung.

⚠ **Der `when@test`-Override auf 10000 ist Pflicht.** `LimiterCoverageTest` verlangt ihn
und prüft zugleich, dass jeder konfigurierte Limiter irgendwo verdrahtet ist.

⚠ **Eigenes Kontingent, nicht das von `partner_waitlist`.** Genau das war BF-38: Fünf
Partner-Anmeldungen sperrten das Organisationsformular mit. Hinter einer geteilten
Adresse — Firmen-WLAN, Mobilfunk-NAT — blockierten sich sonst unabhängige Interessenten
gegenseitig (AK-46).

⚠ **`TRUSTED_PROXIES` ist die Voraussetzung dafür, dass dieser Deckel überhaupt wirkt.**
Ohne den Wert liefert `getClientIp()` hinter dem Coolify-Proxy für jeden Besucher
dieselbe Adresse; der erste Angreifer sperrte die gesamte Nutzerschaft aus. Das ist eine
bestehende Betriebszusage, keine Aufgabe dieses Features — aber sie trägt es.

**Honeypot** wie in B14: ein sichtbar benanntes Textfeld, per CSS aus dem Blickfeld,
`aria-hidden="true"`, `tabindex="-1"`, **ohne** Validierungsregel. Ein Treffer liefert
dieselbe Erfolgsantwort, speichert aber nichts (AK-13/AK-14).

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| Brevo — Mailversand (EU) | Bestätigungsmail, zweite Mail nach der Bestätigung | Empfängeradresse, Betreff, HTML-Inhalt mit Bestätigungs-, Beta- und Abmeldelink | nichts zu entfernen — es gibt außer der Adresse keine personenbezogene Angabe im Eintrag |
| Brevo — Kontaktliste (EU) | Werbe-Empfänger, **nur** bei erteilter Einwilligung und **erst** nach der Selbstbestätigung | E-Mail-Adresse, Herkunft (`app`), Einwilligungszeitpunkt, Sprache | **die gewählte Plattform geht nicht mit**; ebenso wenig `source`, `betaLinkSentAt` oder der Token |
| Apple / TestFlight | — | **nichts** | der TestFlight-Link ist ein Link in einer Mail. Die Anwendung ruft Apple nicht auf, und Apple erfährt von diesem Feature nichts. Wer den Link anklickt, tritt selbst in Apples Reichweite — das ist seine Handlung, nicht die der Plattform |

⚠ **Die Plattform gehört nicht nach Brevo.** `record()` nimmt heute `contactName` und
`organisationName` entgegen; naheliegend wäre, die Plattform in eines der beiden Felder
zu schreiben, weil dieses Feature keinen Namen hat und beide Felder leer blieben. Das
wäre ein Attribut, das zu einer Werbeliste wandert, ohne dass jemand es zugesagt hat —
und AK-54 zählt die Übertragung abschließend auf. Beide Felder bleiben leer.

**Datenschutzstufe B** (übliche Personendaten) nach `docs/prd.md`. Keine besonderen
Kategorien: Die Wahl zwischen zwei Betriebssystemen sagt nichts über Gesundheit,
Herkunft oder Überzeugung (AK-42).

## Die zweite Mail — der einzige neue Ablauf

Der Bestand kennt genau zwei Mailwege: `register()` schickt die Bestätigungsmail an den
Interessenten, `notifyTeam()` die interne Meldung. Für die Mail **nach** dem
Bestätigungsklick gibt es keinen.

**Erweitert wird `WaitlistConfirmationService`**, nicht der Controller: Der Weg braucht
`->locale($entry->getLocale())` (BF-10 — der Worker hat keine Anfrage und nähme sonst
`lb`), den Abmeldelink (BF-37) und das Schlucken der Transport-Ausnahme. Alle drei stehen
dort bereits und würden im Controller ein zweites Mal entstehen — und die zweite Fassung
liefe irgendwann auseinander.

**Ablauf beim Bestätigungsklick**

1. Eintrag über den Token suchen — nicht gefunden → **404**.
2. `hasSelfConfirmed()` → „bereits bestätigt", keine zweite Mail erneut (AK-25).
   ⚠ **Nicht `isConfirmed()`.** Das ist auch nach einem Verwaltungs-Statuswechsel wahr;
   darüber lief in B14 eine echte Bestätigung ins Leere (BF-89).
3. Frist geprüft — älter als 7 Tage → **410**, Seite nennt den Grund und verweist zurück
   auf `/{locale}/app` (AK-28).
4. `confirm()` setzt Status und `selfConfirmedAt`, das Marketing-Auftragsbuch wird
   beschrieben (nur bei Einwilligung).
5. **Zweite Mail** an den Interessenten, in seiner Sprache. Inhalt nach `hasBeta()` und
   danach, ob der Link konfiguriert ist. `betaLinkSentAt` wird gesetzt.
6. Scheitert der Versand, bleibt die Bestätigung erfolgreich — der Nutzer merkt nichts
   (dieselbe Regel wie bei `notifyTeam()`).

**Interne Meldung ans Team: keine.** B14 und B15 verschicken eine, weil dort ein Mensch
zurückrufen muss. Hier gibt es nichts zu tun — der Zugang läuft über den Link. Eine Mail
je Vormerkung wäre Lärm, der die beiden Meldungen entwertet, die tatsächlich eine
Handlung verlangen.

**Der TestFlight-Link steht als Umgebungswert** `APP_TESTFLIGHT_URL`, gespiegelt als
Parameter `app.testflight_url` mit leerer Vorgabe in `.env` — dasselbe Muster wie
`MOBILITEIT_API_KEY` und `SENTRY_DSN`: leer heißt lautlos abgeschaltet. Bekannter Wert:
`https://testflight.apple.com/join/Whxmtrsf`. Nicht in den Übersetzungskatalog, sonst
stünde er an vier Stellen (AK-53).

## Der Aufräumlauf

**Zwei Wege zum selben Ergebnis**, nach dem Muster von `StaleIdeaCleaner` (Feature 06):

1. Ein Konsolenbefehl `app:app-waitlist:cleanup`, eingetragen als **zweiter Eintrag im
   bestehenden `marketing`-Zeitplan**, täglich.
2. `sweepOncePerDay()` beim Öffnen von `/admin/warteliste`, über einen Cache-Schlüssel
   auf einen Lauf je Kalendertag gesperrt.

Gelöscht wird, was **`selfConfirmedAt IS NULL`** trägt und älter als 30 Tage ist — nicht
„Status = pending". Ein Eintrag, den ein Admin von Hand weitergesetzt hat, steht sonst
nicht mehr auf `pending` und entginge dem Lauf, obwohl nie jemand bestätigt hat (dieselbe
Zweideutigkeit, die BF-89 zugrunde lag).

⚠ **Kein dritter Zeitplan.** `processOnlyLastMissedRun(true)` hängt am Zeitplan, nicht
am Eintrag — und für einen täglichen Aufräumlauf ist genau dieses Verhalten richtig: Der
Lauf arbeitet einen Bestand ab, keinen Zeitpunkt. Ein dritter Zeitplan bräuchte einen
dritten Transport im Consumer-Befehl, und der steht an **drei** Stellen: im `worker`-Stage
des Dockerfiles, in `CLAUDE.md` und in Coolifys Startbefehl. Die dritte zieht niemand
automatisch nach.

⚠ **Der zweite Weg ist der eigentliche Grund für Weg zwei.** Auf Produktion fehlten schon
zweimal geplante Läufe — `app:metrics:snapshot` hat dadurch nie einen Snapshot
geschrieben. Eine Löschfrist, die von einer Servereinrichtung abhängt, die bereits
zweimal ausblieb, wäre keine (AK-49).

## Kennzahl auf `/open`

`computePlatform()` bekommt drei Werte: `appWaitlistTotal`, `appWaitlistIos`,
`appWaitlistAndroid` — gezählt werden **nur selbst bestätigte** Einträge. Eine Zahl, die
unbestätigte Vormerkungen mitzählt, wäre über das Formular beliebig aufblasbar.

**Die Schwelle wirkt strukturell, nicht kosmetisch:** Unterhalb von 50 stehen die drei
Schlüssel **gar nicht** im Ergebnis-Array. Lägen sie darin und wären nur im Template
verborgen, wären sie über `/open.json` abrufbar — genau die Konstruktion, die bei der
Quartalssperre der Finanzen bewusst vermieden wurde (AK-37/AK-39).

Die Schwelle steht als Konstante am Dienst, nicht im Template und nicht im Prüflauf — ein
Prüflauf mit eigener Zahl prüft gegen sich selbst.

**Kein neues Feld an `MetricSnapshot`.** Der Verlauf braucht die Zahl nicht: Sie ist ein
Bedarfsnachweis für eine einmalige Entscheidung, kein Zeitreihenwert wie Abdeckung oder
Punktzahl. Kommt die Entscheidung, ist die Liste selbst der Beleg. Die vollständige
Momentaufnahme in `payload` nimmt sie ohnehin mit, sobald sie im Ergebnis-Array steht.

⚠ **Cache-Invalidierung:** `platform()` ist eine Stunde gecacht. Die Zahl darf also bis
zu eine Stunde hinterherhinken — das ist bei allen anderen Zahlen dort genauso und
braucht keine Sonderbehandlung. Ein `invalidate()` bei jeder Vormerkung würde den Pool
bei einem verlinkten Beitrag im Sekundentakt leeren.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Eigene Entity `AppWaitlistEntry`, die `WaitlistEntryInterface` erfüllt | Feld `platform` an `PartnerWaitlistEntry` hängen | Die Partner-Warteliste ist eine Anmeldung für ein kostenpflichtiges Programm mit Restaurantname, Ort und Telefon — alle drei hier sinnlos und alle drei `NOT NULL`. Ein gemeinsamer Tisch bräuchte sie nullable, und dann prüft niemand mehr, welche Kombination gültig ist |
| 2 | `getDisplayName()` liefert das Plattform-Label, `getContactName()` einen leeren String | Interface aufweichen oder einen Namen erheben | Das Interface verlangt beides; es aufzuweichen berührte B14, B15, B22 und Feature 04. Ein Namensfeld nur zur Vertragserfüllung wäre erhobene Daten ohne Zweck — das Gegenteil von Datenminimierung. Der leere String ist ehrlich: Es gibt keinen Ansprechpartner |
| 3 | Unique-Index auf `email` | Prüfung allein im Controller | Zwei gleichzeitige Absendevorgänge aus zwei Tabs erzeugten sonst zwei Zeilen (EC-06). Der Index ist die einzige Stelle, an der das Wettrennen entschieden wird |
| 4 | Dublette antwortet **identisch** zum Erfolg | Meldung „steht schon auf der Liste" | Sonst ist die Liste von außen abfragbar: Ein Fremder prüfte, ob eine Adresse eingetragen ist. Dasselbe Muster wie die Anti-Enumeration in `Api\V1\AuthController::register()` |
| 5 | Kein Fremdschlüssel auf `User` | Eintrag am Konto festmachen, wenn jemand angemeldet ist | Die Seite ist öffentlich, die meisten Interessenten haben kein Konto — die Adresse ist der einzige Anker, der immer trägt. Zwei Wege zur selben Liste (mit und ohne Konto) hätten zwei Löschkaskaden, und die zweite vergisst jemand |
| 6 | Zweite Mail über den geteilten Dienst, nicht im Controller | Mail direkt im Controller bauen | `->locale()` (BF-10), Abmeldelink (BF-37) und das Schlucken der Transport-Ausnahme stehen bereits dort. Eine zweite Fassung liefe auseinander |
| 7 | Aufräumlauf im bestehenden `marketing`-Zeitplan | dritter Zeitplan `cleanup` | Ein dritter Transport steht an drei Stellen, von denen eine in Coolify von Hand gepflegt wird. `processOnlyLastMissedRun(true)` ist für diesen Lauf ohnehin richtig |
| 8 | Neuer Case `MarketingOrigin::APP` | die neue Quelle unter `ACCOUNT` führen | `originOf()` sagt den Fall im Bestand selbst voraus. `ACCOUNT` heißt „kein Vertriebskanal" — eine App-Warteliste ist einer, und die Herkunft ist der einzige Weg, eine Kampagne später zuzuschneiden |
| 9 | Zahl auf `/open` erst ab 50, Schlüssel fehlen darunter | Zahl immer zeigen, im Template verbergen | Verborgen im Template wäre sie über `/open.json` abrufbar. Dieselbe Konstruktion wie die Quartalssperre |
| 10 | Kein neues Feld an `MetricSnapshot` | Zeitreihe mitschreiben | Die Zahl trägt eine einmalige Entscheidung, keinen Verlauf. Eine Spalte, die niemand liest, ist eine Migration, die jemand pflegen muss |
| 11 | Keine interne Meldung ans Team | Meldung wie bei B14/B15 | Es gibt nichts zu tun — der Zugang läuft über den Link. Eine Mail je Vormerkung entwertete die beiden Meldungen, die eine Handlung verlangen |
| 12 | Segmented Control als eigenes Partial, nicht `_tristate_field` wiederverwendet | das bestehende Partial parametrisieren | `_tristate_field` ist an drei feste Fälle gebunden und wird von zwölf Pflichtfragen im Vorschlags-Wizard benutzt. Es für zwei Fälle zu verallgemeinern, riskiert B11 für eine Ersparnis von zwanzig Zeilen |
| 13 | Fußzeilen-Eintrag in Spalte 4 | zwölfter Eintrag in Spalte 2 oder fünfte Spalte | Eine fünfte Spalte bräche `lg:grid-cols-4` und vergrößerte die offene Umbruchlücke der Kopfzeile (BF-80). Spalte 4 ist die dünnste — dieselbe Begründung, aus der Feature 07 dort steht |
| 14 | Keine neue Abhängigkeit | — | Alles Nötige ist installiert: `symfony/rate-limiter`, `symfony/ux-turbo` 2.32, `symfony/scheduler` 8.0, `symfony/brevo-mailer`. Für dieses Feature kommt **kein** Paket dazu |

## Aktuelle Dokumentation

Geprüft wurde, ob etwas nachzuschlagen ist. **Ergebnis: nein** — und das ist die
Entscheidung, nicht ihr Fehlen.

| Baustein | Installiert | Warum kein Nachschlagen |
|---|---|---|
| `symfony/framework-bundle`, `symfony/rate-limiter`, `symfony/scheduler` | 8.0.* | alle drei Muster stehen im Projekt in Gebrauch (`ActionLimiter`, beide Zeitplan-Provider) |
| `symfony/ux-turbo` | ^2.32 | Turbo-Stream ist seit B14 in Gebrauch, samt der Falle mit dem Antwortformat im Fehlerfall |
| `doctrine/orm` | ^3.6 | Enum-Mapping und `#[ORM\PreUpdate]` sind mehrfach im Bestand |

*(composer.json gelesen 2026-09-04.)*

Nachzuschlagen wäre etwas, das seit dem letzten Hauptversionssprung anders ist oder das
im Projekt noch nirgends vorkommt. Beides trifft hier auf keinen Baustein zu. Der einzige
externe Bestandteil — der TestFlight-Link — ist eine Zeichenkette in einer Mail und hat
keine Schnittstelle.

## Abdeckung der Akzeptanzkriterien

Aus `features/08-app-warteliste/spec.md` der Reihe nach abgegangen, alle 58.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Route `app_app_waitlist`, `PUBLIC_ACCESS` über die bestehende `^/[a-z]{2}/`-Regel | |
| AK-02 | `app_app_waitlist_redirect` in `config/routes.yaml`, 302 | |
| AK-03 | Formularaufbau: `email`, `platform`, `marketingConsent` (+ Pflicht-`consent`, + Honeypot) | ⚠ Die Spec zählt drei Felder; `consent` und Honeypot sind zusätzlich. Siehe OF-05 |
| AK-04 | `ChoiceType`, `expanded: true`, `multiple: false`, `placeholder: false`, kein `data` | keine Vorauswahl → ungültiger Submit liefert verlässlich 422 |
| AK-05 | Hinweistext am Android-Segment, serverseitig gerendert | ohne JavaScript sichtbar, weil im Markup |
| AK-06 | Hinweistext am iOS-Segment, serverseitig gerendert | |
| AK-07 | `NotBlank` auf `email`, `NotNull` auf `platform`, `IsTrue` auf `consent`; `empty_data: ''` | ⚠ `empty_data` ist Pflicht, sonst 500 statt Meldung |
| AK-08 | `Email`-Constraint auf dem E-Mail-Feld | |
| AK-09 | Controller setzt `consentAt`, `locale`, `source` über `WaitlistRequestHelper::resolveSource()` | Muster aus `PartnerController::submit()` |
| AK-10 | `successResponse()` mit `TurboBundle::STREAM_FORMAT`, Ziel-id `app-waitlist-form` | |
| AK-11 | Redirect-Zweig in `successResponse()`; Bestätigung und Widerruf sind reine GET-Seiten | |
| AK-12 | Fehlerzweig rendert **ohne** `setRequestFormat()` | ⚠ EC-01: Wird dort das Stream-Format gesetzt, rendert Turbo die Meldungen nicht |
| AK-13 | Honeypot-Prüfung **vor** der Formularauswertung, Rückgabe `successResponse(null)` | |
| AK-14 | Feldtyp `TextType` mit CSS-Versteckung; **kein** `Blank`-Constraint | |
| AK-15 | Adressabfrage vor dem Anlegen **plus** Unique-Index; Antwort ist `successResponse()` | Entscheidungen 3 und 4 |
| AK-16 | derselbe Zweig; bei `hasSelfConfirmed()` wird weder Mail noch Feld angefasst | |
| AK-17 | derselbe Zweig; bei abgelaufenem Token neuer Token + erneuter Versand über `register()` | ohne diesen Zweig ist EC-07 eine Sackgasse |
| AK-18 | `WaitlistConfirmationService::register()`, `ABSOLUTE_URL` | Schema kommt aus dem Request → `TRUSTED_PROXIES` trägt es (EC-08) |
| AK-19 | Mailvorlage 1 kennt den Parameter `app.testflight_url` nicht | strukturell, nicht redaktionell |
| AK-20 | Rückgabewert `false` von `register()` → Flash, Eintrag bleibt | |
| AK-21 | `confirm()` setzt Status und `selfConfirmedAt`; Controller stößt die zweite Mail an | |
| AK-22 | Mailvorlage 2, Zweig `platform.hasBeta()` **und** Parameter nicht leer | |
| AK-23 | Mailvorlage 2, Android-Zweig | |
| AK-24 | Mailvorlage 2, Zweig „Parameter leer" | Bestätigung bleibt erfolgreich |
| AK-25 | `hasSelfConfirmed()` → `RESULT_ALREADY`, kein Mailversand | ⚠ nicht `isConfirmed()` (BF-89) |
| AK-26 | `RESULT_INVALID` → HTTP 404, Vorlage `_waitlist_confirmation` | |
| AK-27 | Routenbedingung `[a-f0-9]{64}` | greift vor jeder Abfrage |
| AK-28 | `isExpired()` (7 Tage, `TOKEN_LIFETIME_DAYS`) → HTTP **410** + Verweis auf `/{locale}/app` | Frist ist Bestand; der Rückverweis ist neu |
| AK-29 | `recordWaitlistEntry()` prüft `hasSelfConfirmed()` **und** `hasMarketingConsent()` | Bestand, unverändert |
| AK-30 | `register()` erhält `revokeRoute`; Mailvorlage 2 bekommt denselben Parameter | ⚠ Vorlage 2 ist neu — der Abmeldelink muss dort ausdrücklich mit |
| AK-31 | `WaitlistConfirmationService::revoke()` → `remove()` + `flush()` | Bestand |
| AK-32 | `scheduleRemoval()` **plus** die neue Entity in `sourcesFor()` | ⚠ ohne die Ergänzung wirkungslos (BF-84) |
| AK-33 | Eintrag ist nach dem ersten Aufruf weg → `RESULT_INVALID` → dieselbe Vorlage | ⚠ EC-04: Die Vorlage muss `invalid` auf der Abmelderoute als „bereits ausgetragen" darstellen, nicht als Fehler |
| AK-34 | keine Sperrliste; nach dem Löschen ist die Adresse frei | Unique-Index steht dem nicht entgegen |
| AK-35 | `appRow()` in `AdminWaitlistController`, dritter Quellen-Filter, Plattform als Abzeichen | |
| AK-36 | `access_control` auf `^/[a-z]{2}/admin` + `#[IsGranted('ROLE_ADMIN')]` | Bestand |
| AK-37 | Schwellenprüfung in `computePlatform()`: unterhalb fehlen die Schlüssel im Array | strukturell |
| AK-38 | dieselbe Stelle, drei Zahlen; Kacheln über `open/_metric.html.twig` | |
| AK-39 | folgt aus AK-37/38: `/open.json` liest dasselbe Array; der Datensatz kennt nur Restaurants | keine Änderung an `OpenDataController` nötig |
| AK-40 | Fußzeile Spalte 4 in `base.html.twig`; Hinweisband in `home/index.html.twig` | Entscheidung 13 |
| AK-41 | Feldliste der Tabelle `app_waitlist_entry` | keine IP-Spalte vorgesehen |
| AK-42 | Feldliste; `AppPlatform` hat zwei Cases | |
| AK-43 | keine Protokollausgabe im Anfrageweg; `SecretMaskingProcessor` deckt den Rest | ⚠ als Prüfschritt in `tasks.md`, nicht als Zusicherung im Code |
| AK-44 | Limiter `app_waitlist`, 10/Stunde, `ActionLimiter` | |
| AK-45 | `isAllowed()` vor `consume()`; Verbrauch erst beim Anlegen | BF-11 |
| AK-46 | eigener Limiter-Name in `framework.yaml` | BF-38 |
| AK-47 | Aufräumlauf: `selfConfirmedAt IS NULL` **und** älter als 30 Tage | nicht „Status = pending" |
| AK-48 | dieselbe Bedingung schließt Bestätigte aus | |
| AK-49 | Zwei Wege: Zeitplan-Eintrag **und** `sweepOncePerDay()` beim Öffnen von `/admin/warteliste` | Muster `StaleIdeaCleaner` |
| AK-50 | **Feature `01`** — Löschkaskade über Adressgleichheit | ⚠ Offen bis dahin; siehe OF-06 |
| AK-51 | **Feature `01`** — `AccountDataExporter` um die neue Quelle erweitern | ⚠ dito |
| AK-52 | `marketingConsent` ungemappt, `required: false`, **ohne** `IsTrue`, ohne `data: true` | Koppelungsverbot, Art. 7 Abs. 4 |
| AK-53 | Parameter `app.testflight_url` aus `APP_TESTFLIGHT_URL` | leer = lautlos aus |
| AK-54 | Abschnitt *Externe Dienste*; die Plattform geht ausdrücklich nicht mit | |
| AK-55 | neue Schlüssel in `messages.{de,en,fr,lb}` und `validators.{de,en,fr,lb}` | `CatalogueCompletenessTest` erzwingt die Gleichheit der Schlüsselmengen |
| AK-56 | `_form_field.html.twig`; Segmented Control mit `peer sr-only`, nicht `hidden` | ⚠ `hidden` nähme die Radios aus Tastatur und Screenreader |
| AK-57 | `_form_field.html.twig` setzt `aria-describedby`, `aria-invalid` und `autofocus` serverseitig | ⚠ `'aria-invalid': null` rendert `aria-invalid=""` — nur `false` unterdrückt |
| AK-58 | kein Verzeichnis `public/app`; `RouteDirectoryCollisionTest` prüft projektweit | ohne Zutun abgedeckt |

**Keine Zeile ist leer.** Zwei Kriterien (AK-50, AK-51) werden nicht von diesem Feature
erfüllt, sondern von Feature `01`; das steht in der Spec bereits so und ist hier als
offene Frage vermerkt statt stillschweigend abgehakt.

## Offene Fragen aus dem Entwurf

Zurück in `spec.md` gehören:

- **OF-05** · **Die Spec zählt drei Felder (AK-03), der Entwurf braucht fünf.** `consent`
  (Pflichthäkchen zur Einwilligung) und das Honeypot-Feld sind in B14/B15 selbstverständlich,
  in AK-03 aber nicht mitgezählt — und AK-07 verlangt „je Pflichtfeld eine eigene Meldung",
  was ohne `consent` nur zwei wären. Vorschlag: AK-03 auf „drei **sichtbare Eingabefelder**
  plus Einwilligungshäkchen; das Honeypot-Feld zählt nicht mit" schärfen. — Betreiber
- **OF-06** · **AK-50 und AK-51 hängen an Feature `01`, das auf `roadmap` steht.** Wird `08`
  vor `01` ausgeliefert, sind beide Kriterien ungeprüft in Produktion. Zwei Wege: `01`
  vorziehen, oder beide Kriterien ausdrücklich als „vertagt bis `01`" kennzeichnen, damit
  `sdd-qa` sie nicht als Fehlschlag führt. — Betreiber, vor `/sdd-tasks 08`
- **OF-07** · **Der Aufräumlauf läuft im Zeitplan `marketing`.** Der Name passt fachlich
  nicht; ein Leser sucht ihn dort nicht. Umbenennen kostet einen geänderten Startbefehl an
  drei Stellen (Dockerfile, `CLAUDE.md`, Coolify) und ist deshalb kein Nebenbei-Handgriff.
  Vorschlag: so lassen und den Zeitplan im Klassenkommentar als „wiederkehrende
  Hausarbeit" beschreiben. — Betreiber
