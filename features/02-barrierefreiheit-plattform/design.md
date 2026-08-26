# 02 · Barrierefreiheit der Plattform — Systemdesign

Status: `architected` · Stand: 2026-08-26 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Das Feature hat zwei Naturen. **Zwei Drittel sind Härtung des Bestands:** Skip-Link,
Fokusanzeige, Kontrast, Landmarks, Zielgrößen, Sprachauszeichnung und
Formular-Ansagen werden über die vorhandenen 77 Templates, die E-Mail-Vorlagen und
die Offline-Seite durchgesetzt — größtenteils ohne neuen Code, sondern durch zwei
neue globale CSS-Blöcke und punktuelle Template-Änderungen. **Ein Drittel ist neu:**
eine öffentliche Barrierefreiheitserklärung unter eigener Route und ein Meldeformular,
das eine Barriere per E-Mail an die Kontaktadresse schickt und **nichts speichert**.

Der Nachweis, dass die Zusage hält und hält bleibt, ist selbst Teil des Entwurfs: ein
automatisierter Prüflauf über eine kuratierte Routenliste plus strukturelle Tests, die
rot werden, sobald eine neue Seite gegen eine der Regeln verstößt.

Es kommt **keine neue Bibliothek** in die Anwendung und **keine neue Tabelle** in die
Datenbank.

## Seiten und Routen

| Route | URL (unter `/{_locale}`) | Zweck | Zugang |
|---|---|---|---|
| `app_accessibility` | `/accessibility` (GET) | Barrierefreiheitserklärung + Meldeformular | öffentlich |
| `app_accessibility_report` | `/accessibility` (POST) | Meldung entgegennehmen, versenden, bestätigen | öffentlich |

Der Pfad ist **englisch/technisch** wie `/criteria` (`app_kriterien`) und `/legal`
(`app_impressum`) — beides reine Render-Controller, an denen sich der neue orientiert.
Beide Routen liegen unter dem Locale-Präfix (vier Sprachen, AK-44), geladen über den
regulären `controllers`-Loader in `config/routes.yaml`; **keine** locale-freie Ausnahme
wie bei `api_v1`/`open_data`.

⚠ **Namenskollision vermeiden:** `app_kriterien` (Footer „Kriterien für
Barrierefreiheit") ist die **Restaurant**-Kriterienseite, nicht diese Erklärung. Neue
Übersetzungsschlüssel deshalb unter eigener Domäne `accessibility_statement.*` (nicht
`accessibility.*` — das ist im Bestand die Merkmalsliste der Restaurants) und der neue
Footer-Link unter `footer.accessibility_statement`.

Alle übrigen Kriterien betreffen **bestehende** Seiten und legen keine neue Route an.

## Komponentenstruktur

Erklärungsseite (`templates/accessibility/index.html.twig`, erbt `base.html.twig`):

```
/accessibility
├── Hero-Band (dunkle Stufe from-cyan-700 to-purple-800, eine Leitaussage)
├── Erklärung (aus Konfiguration + Katalogtexten)
│   ├── Konformitätsgrad · Prüfdatum · Prüfverfahren      AK-43
│   ├── Veralterungshinweis (bg-amber-50, ab 12 Monaten)  AK-46
│   ├── Geltungsbereich                                    AK-43
│   ├── Liste nicht zugänglicher Inhalte (Kriterium ·      AK-45
│   │   Grund · geplantes Datum)
│   └── Rechtslage: freiwillige Selbstverpflichtung        AK-47
├── Rückmeldeweg
│   ├── Kontaktadresse im Klartext (mailto)               AK-48
│   └── Meldeformular (partials/_form_field.html.twig)    AK-48/49
│       ├── Beschreibung  (Pflicht, Textarea)
│       ├── E-Mail        (optional)                       AK-49/58
│       ├── Honeypot „website" (sr-only, aria-hidden)      AK-53
│       └── Erfolgsziel #accessibility-report-form         AK-51
│           (tabindex=-1, autofocus, role=status,
│            aria-live=polite) — via Turbo-Stream
└── Beschwerdestelle: keine (Freiwilligkeitshinweis)       AK-43 (Decision #13)
```

Geänderte gemeinsame Bausteine (kein Baum, sondern Eingriffe an einer Stelle mit
projektweiter Wirkung):

- `templates/base.html.twig` — Skip-Link, `<main id>`, Fußzeilen-Link, „vorschlagen"
  in die Fußzeile.
- `assets/styles/app.css` — globaler Fokus-Block, globaler Reduced-Motion-Block,
  Tom-Select-Fokus auf echte `outline`.
- `public/offline.html`, `templates/email/base.html.twig` — Härtung.
- `templates/profile/index.html.twig` — Abmeldeknopf (mobiler Weg).

## Umsetzungsbausteine

Weil das Feature breit streut, sind die Kriterien auf klar benannte Bausteine
gebündelt; die Abdeckungstabelle verweist auf sie.

| # | Baustein | Wo | Art |
|---|---|---|---|
| **BS-1** | Skip-Link + Landmarks | `base.html.twig`: Sprunglink als erstes fokussierbares Element, `<main id="hauptinhalt" tabindex="-1">`; `<header>`/`<footer>` als Landmarks bestätigen | Template |
| **BS-2** | Globaler Fokus-Fallback | `app.css`: `:focus-visible`-Regel (echte `outline`, 2px, offset, kontraststarke Farbe) für alles ohne eigene Utility; **Tom-Select-Override** von `box-shadow`+`outline:none` auf echte `outline` umstellen | CSS |
| **BS-3** | Globaler Reduced-Motion | `app.css`: `@media (prefers-reduced-motion: reduce)` neutralisiert Transitions/Animationen/`scroll-behavior` flächig — der Netzfang unter der `motion-safe:`-Konvention | CSS |
| **BS-4** | Kontrast- & Farbrevision | Bestandstemplates: Textkontrast ≥ 4,5:1 (kleine Schrift) bzw. ≥ 3:1 (groß), UI-/Diagrammkontrast ≥ 3:1; Fokusfarbe auf Verlaufsgrund weiß; „Farbe trägt nie allein" (bereits Konvention) prüfen | Template |
| **BS-5** | Struktur & Sprache | genau ein `<h1>` je Seite, lückenlose Überschriftenebenen; eindeutige `<title>`-Blöcke; `lang`-Wechsel an fremdsprachigen Abschnitten; sprechende Linktexte („hier"/„mehr" ersetzen) | Template |
| **BS-6** | Formular-Härtung | `_form_field` dort einziehen, wo heute manuell verdrahtet (`security/login`, `registration/register`, `admin/**/_form`); Label-Verknüpfung, Fehlertext am Feld, Fokus ins erste Fehlerfeld, Pflicht-Ansage, `autocomplete`-Tokens (Name/E-Mail/Tel.), Vorbelegung wiederholter Angaben, Wizard-Schritt-Ansage | Template/Form |
| **BS-7** | Zielgrößen & Bewegung | Trefferflächen 24 px (Minimum) / 44 px (Admin) / 48 px (öffentliche Hauptaktion); nichts blinkt/bewegt sich länger als 5 s ohne Stopp (via BS-3 abgedeckt) | Template |
| **BS-8** | Mobile Wege | Abmelden-Knopf auf `profile/index` (über Bottom-Nav erreichbar); „Restaurant vorschlagen" in die Fußzeile (B2B-Seiten stehen dort bereits); Sprachwechsel ist seit BF-72 mobil im Header vorhanden | Template |
| **BS-9** | Offline & Mails | `offline.html`: `lang`, eigener `<title>`, Kontrast, sichtbarer Fokus, bedienbarer Button; `email/base.html.twig` + Inhalte: Kontrast (Footer-Grau anheben), sprechende Links, ohne Bilder vollständig | Template |
| **BS-10** | Admin-Härtung | Tastaturweg für Anlegen/Bearbeiten/Einsortieren; **Bildsortierung: Alternative zum Drag&Drop** (Auf/Ab-Knöpfe je Bild, ohne JS bedienbar); Admin-Fokus (`focus:ring… outline-none` → echte `outline`); Tom-Select-Vorschläge werden angesagt und Auswahl bestätigt | Template/Stimulus |
| **BS-11** | Erklärungsseite | `AccessibilityController::index`, `templates/accessibility/`, Katalogtexte in vier Sprachen, Konfiguration für Grad/Datum/Prüfverfahren/nicht-zugängliche-Liste, Veralterungsvergleich gegen „heute" | Controller/Template/Config |
| **BS-12** | Meldeformular | `AccessibilityController::report`, `AccessibilityReportType` (Beschreibung Pflicht, E-Mail optional, Honeypot), Limiter `accessibility_report`, Versand ohne Speicherung, Turbo-Stream-Erfolg + 422-Fehler, Fokus-/Ansage, Log-Sicherheit | Controller/Form/Service |
| **BS-13** | Prüf- & Regressionskette | automatisierter A11y-Lauf über Routenliste + strukturelle Functional-Tests + abgelegte Konformitäts-Prüfmatrix + Limiter-Abdeckung | Test/Doku |

## Datenmodell

**Es entsteht keine Tabelle und keine Entity.** Das ist die tragende Entscheidung des
Feature-Datenschutzes, nicht eine Auslassung.

Die einzige personenbezogene Verarbeitung ist die Meldung. Ihr Inhalt lebt nur für die
Dauer des Requests:

| Feld (flüchtig, nur im Request) | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `description` | Text (mehrzeilig) | ja | die gemeldete Barriere — **besondere Kategorie Art. 9** |
| `email` | Text | nein | Rückmeldeadresse, falls der Melder eine will |
| `website` (Honeypot) | Text | nein | für Menschen unsichtbar; ausgefüllt ⇒ Bot |

Beziehungen: keine. Indizes: keine. Speicherung: keine — der Wert geht in eine E-Mail
und wird danach nicht zurückgehalten (AK-50, AK-56).

**Erklärungsinhalte** (Konformitätsgrad, Prüfdatum, Prüfverfahren, Liste nicht
zugänglicher Inhalte) sind **Konfiguration, keine Nutzerdaten** — sie liegen als
Parameter/Struktur in `config/` (Muster wie `app.contact_email`, `app.version`), die
übersetzbaren Fließtexte in den Katalogen. Das Prüfdatum ist ein echtes Datum, damit
der Controller es gegen „heute" vergleichen und ab zwölf Monaten den Veralterungshinweis
setzen kann (AK-46) — dieselbe Mechanik wie der „Stand vom"-Hinweis der Finanzzahlen
(B18).

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| jeder (auch abgemeldet) | Erklärungsseite | Meldung absenden | `access_control` (öffentlicher Bereich), kein `#[IsGranted]` |

`^/[a-z]{2}/accessibility` beginnt **nicht** mit `admin`, `profile` oder `verify` und
fällt damit unter die bestehende öffentliche Abdeckung der `main`-Firewall — es ist
keine neue `access_control`-Zeile nötig, aber der Entwurf hält fest, dass die Route
öffentlich bleiben **muss** (AK-59) und keine spezifischere Regel davor sie einfängt
(Reihenfolge-Regel des Stack-Profils).

Kein Objektzugriff, kein Voter, kein IDOR-Vektor: Es wird nichts adressierbar abgelegt
(AK-50), also existiert keine fremde ID und keine Rolle, die etwas sehen dürfte oder
nicht (AK-59, Sicherheitskatalog Punkt 3).

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `POST /accessibility` | `accessibility_report`: 5 / Stunde je IP (sliding_window) | Ablehnung, Wartezeit genannt (Flash / 429-Muster) | `config/packages/framework.yaml` |

- **Limiter im selben Commit** wie der Endpunkt (Projektkonvention: jeder Weg, der eine
  Mail auslöst, wird gedeckelt) — angebunden über `#[Autowire(service:
  'limiter.accessibility_report')]` wie `PartnerController`, verbraucht **erst nach**
  bestandener Validierung (`ActionLimiter::consume()`), Prüfung über `isAllowed()`
  (nie `consume(0)`). ⚠ **`when@test`-Override auf 10000** ist Pflicht, sonst färbt der
  sechste Submit die Suite rot. IP-basiert, weil hier keine Sitzung und kein Konto
  vorausgesetzt ist (AK-52).
- **Honeypot** nach dem Wartelisten-Muster: Feld `website`, `mapped: false`, **kein**
  `Blank`-Constraint (ein Validierungsfehler verriete die Falle), per CSS aus dem
  Blickfeld (`sr-only`/absolut positioniert) statt `type="hidden"`, `aria-hidden`,
  `tabindex="-1"`. Treffer ⇒ dieselbe Erfolgsantwort, aber ohne Versand (AK-53).
- **CSRF / fremde Absender**: dieselbe Same-Origin-Absicherung wie jedes Projektformular
  (stateless Token, `token_id: submit`) — AK-60.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| E-Mail-Versender (Brevo prod / Mailpit dev) | Meldung an die Kontaktadresse zustellen | Beschreibung + optional die E-Mail des Melders | nichts hinzugefügt; kein weiterer Empfänger |
| Fehler-Tracking (Sentry, nur prod) | Ausnahme beim Versand melden | **nur** Ausnahmeklasse + Statuscode | Beschreibung und Melder-Adresse dürfen **nicht** in den Record (AK-57) |

- Empfänger ist `app.contact_email` (vorhandener Konfigurationswert, kein Geheimnis).
  Betreff der internen Mail fest Deutsch (Muster `notifyTeam()`), `replyTo` die
  Melder-Adresse nur, wenn angegeben.
- **Reihenfolge Versand vor „fertig", aber ohne Persistenz:** Scheitert der Transport
  (`TransportExceptionInterface`), erfährt es der Melder und behält seinen Text
  (EC-04). Der Fehlerpfad loggt **ausschließlich** Klasse und Statuscode — der
  `SecretMaskingProcessor` maskiert nur bekannte Query-Parameter, **nicht** freien
  Body-Text; die Log-Sicherheit für die Beschreibung ist deshalb eine bewusste
  Controller-/Service-Regel, keine automatische Folge (AK-57).

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Härtung mit vorhandenen Mustern, **keine neue Laufzeit-Bibliothek** | axe-core/Pa11y als App-Abhängigkeit | Ein Prüfwerkzeug gehört in die Test-/CI-Kette, nicht in die ausgelieferte App. Doku-Beschaffung entfällt, da kein neues Bundle. |
| 2 | Meldung **versenden, nicht speichern** (keine Entity) | Entity mit Löschfrist | Der Text ist ein Gesundheitsdatum (Art. 9). Was nicht gespeichert ist, kann nicht abfließen — dieselbe Begründung wie bei den Finanzposten (B18). |
| 3 | **Globaler `:focus-visible`-Fallback** in `app.css` | 57 Bestandsstellen einzeln nachrüsten | Eine Stelle deckt die heute fokuslosen Bereiche (`home/`, `about/`, `community/`, Footer-Links, Browser-Default) ab, ohne 57 Templates anzufassen; die per-Element-Utilities bleiben und gewinnen. |
| 4 | **Globaler Reduced-Motion-Block** | jedes `transition` auf `motion-safe:` umstellen | Über 100 Fundstellen; der `reduce`-Block ist der Netzfang, `motion-safe:` bleibt Konvention für neue Arbeit (AK-26). |
| 5 | Erklärungsinhalte als **Konfiguration + Katalogtexte**, Prüfdatum als Datum | Freitext im Template je Sprache | Vier Sprachen synchron (AK-44), Veralterungsvergleich maschinell möglich (AK-46), Grad/Datum an einer Stelle pflegbar. |
| 6 | Route **öffentlich unter Locale-Präfix**, Pfad `/accessibility` | locale-frei wie `/open` | Die Erklärung ist Fließtext in vier Sprachen, kein zitierter Datenpunkt; der Locale-Präfix ist hier richtig. Englischer Pfad = Konsistenz mit `/criteria`, `/legal`. |
| 7 | Mobiles Abmelden auf die **Profilseite** | fünftes Feld in der Bottom-Nav / Header-Umbau | Die Bottom-Nav hat vier Felder und ist für Ziele, nicht Aktionen; die Profilseite ist über sie erreichbar und der etablierte Ort (AK-34). |
| 8 | „Vorschlagen" in die **Fußzeile** (statt Bottom-Nav-Umbau) | mobiles „Mehr"-Sheet | Erfüllt AK-36 minimalinvasiv; die B2B-Wege stehen bereits in der Fußzeile. Ein Sheet wäre Overengineering für die Kriterien. |
| 9 | Bildsortierung bekommt **Auf/Ab-Knöpfe** neben Drag&Drop | Drag&Drop tastaturfähig machen | WCAG 2.5.7 verlangt eine Nicht-Ziehen-Alternative; Knöpfe funktionieren ohne JS und ohne SortableJS-Umbau (AK-39). |
| 10 | Fremde Widgets (Tom Select, GLightbox) **erst prüfen** | vorab ersetzen | Decision-Log #12 der Spec (OF-01). Der Tom-Select-**CSS-Override** ist dagegen eigener Code und wird sofort auf echte `outline` gebracht (AK-40/41). |
| 11 | Nachweis: **automatisierter A11y-Lauf + strukturelle Tests + Prüfmatrix** | nur manuelle Prüfung | AK-54 verlangt, dass eine neu hinzugefügte, regelverletzende Seite den Prüflauf **fehlschlagen** lässt — das geht nur automatisiert. axe-core über die vorhandene headless-Brave/CDP-Umgebung (keine App-Abhängigkeit). |

## Nachweis und Regression (BS-13, Detail)

- **Automatischer Lauf:** axe-core (WCAG 2.2 AA-Regelsatz) über eine kuratierte
  **Routenliste** — je öffentlicher Seitentyp einmal, dazu je ein Admin-Formular und
  die Offline-Seite. Läuft über die bestehende headless-Brave-CDP-Umgebung; ein Verstoß
  lässt ihn fehlschlagen (AK-54). Er findet maschinell prüfbares Drittel: Kontrast,
  fehlende Alt-Texte, `lang`, doppelte `<title>`, Label-Verknüpfung, `aria-*`-Fehler.
- **Strukturelle Functional-Tests** (`WebTestCase`) für Invarianten, die axe nicht
  abdeckt: Skip-Link als erstes fokussierbares Element vorhanden; `<main id>`; **genau
  ein `<h1>`** je Route; Meldeformular persistiert nichts (kein DB-Schreibzugriff);
  Honeypot-Treffer → Erfolg ohne Mail; 422 bei leerer Beschreibung; `assertEmailCount`
  im Gutfall. Der bestehende `LimiterCoverageTest` erzwingt, dass
  `accessibility_report` verdrahtet ist und einen `when@test`-Override hat.
- **Konformitäts-Prüfmatrix** (`docs/…`, abgelegtes Artefakt): je WCAG-2.2-AA-Kriterium
  „erfüllt / nicht erfüllt / nicht anwendbar" mit Begründung bei „nicht anwendbar"
  (AK-55). Sie ist die Datenquelle für Konformitätsgrad, Prüfdatum und die Liste nicht
  zugänglicher Inhalte auf der Erklärungsseite (AK-43/45).
- **`CatalogueCompletenessTest`:** Alle neuen `|trans`-Schlüssel stehen in **allen vier**
  Katalogen (`messages`/`validators`), sonst wird die Suite rot. Controller-seitige
  `trans()`-Aufrufe erfasst der Scanner nicht — deren Schlüssel deshalb bewusst auch im
  Template referenzieren oder im Test ergänzen.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | BS-1 | Skip-Link als erstes Tab-Ziel, Sprung hinter die Navigation zu `<main id>` |
| AK-02 | BS-1, BS-6, BS-2 | Tastaturweg über alle Bedienpunkte; `<details>`-Menü und Bottom-Nav sind bereits tastaturfähig |
| AK-03 | BS-2 | globaler Fokus-Fallback schließt `home/`, `about/`, `community/` und Verlaufsgründe (weiße Outline) |
| AK-04 | BS-2, BS-10 | echte `outline` statt `box-shadow`-Ring (bleibt im Windows-Kontrastmodus sichtbar); Tom-Select- und Admin-`outline:none` beseitigt |
| AK-05 | BS-1 | `<main>` als Sprungziel ohne Verdeckung; Prüfung gegen `sticky`-Header (`scroll-margin`) |
| AK-06 | BS-10, Decision #10 | Galerie-Fokusfalle/Escape/Rückgabe — Teil der Widget-Prüfung (OF-01); Ergebnis entscheidet Ersatz |
| AK-07 | BS-1, BS-2 | keine Tastaturfalle in Menü/Banner; `<details>`/Cookie-Banner bereits schließbar |
| AK-08 | BS-5 | DOM-Reihenfolge = sichtbare Reihenfolge (kein positives `tabindex`) |
| AK-09 | BS-4, BS-10 | Alt-Text je Bild auf Detailseite; Admin-Upload erzwingt Alt-Text (Pflichtfeld) |
| AK-10 | BS-4 | schmückende Bilder/Emojis `aria-hidden` (bestehende Konvention, Bestand prüfen) |
| AK-11 | BS-4, BS-13 | Textkontrast ≥ 4,5:1 / groß ≥ 3:1; Fundstellen wie Footer-Grau und Mail-Footer angehoben; axe prüft |
| AK-12 | BS-4, BS-13 | Bedien-/Diagrammkontrast ≥ 3:1 |
| AK-13 | BS-4 | 200 % Zoom ohne Verlust — mobile-first-Layout trägt das bereits, Bestand prüfen |
| AK-14 | BS-4 | 320 px ohne Seiten-Querscrollen; nur ausgewiesene Bereiche scrollen waagerecht |
| AK-15 | BS-5, BS-13 | genau ein `<h1>`, keine übersprungene Ebene; Functional-Test erzwingt es |
| AK-16 | BS-4 | Textabstände erhöhbar ohne Abschnitt/Überlauf |
| AK-17 | BS-4 | Graustufen-Erkennbarkeit; „Farbe trägt nie allein" (Vorzeichen/Emoji/Text) — Konvention prüfen |
| AK-18 | BS-6 | Label-`for`/`id`-Verknüpfung überall (auch login/register/admin) |
| AK-19 | BS-6 | Fehlertext am Feld mit Feld + Ursache; `_form_field` liefert das |
| AK-20 | BS-6 | `autofocus` auf erstem Fehlerfeld (serverseitig, JS-frei) |
| AK-21 | BS-6 | Pflicht wird angesagt (`required`/`aria-required`, nicht nur Sternchen) |
| AK-22 | BS-6 | `autocomplete`-Tokens für Name/E-Mail/Telefon |
| AK-23 | BS-6 | wiederholte Angaben vorbelegt/auswählbar (WCAG 3.3.7) — betrifft mehrstufige Wege |
| AK-24 | BS-6, Stimulus | Wizard-Schrittwechsel in `aria-live`-Region angesagt (Muster `organisation_type_controller`) |
| AK-25 | BS-7 | Zielgrößen 24 / 44 / 48 px nach Kontext |
| AK-26 | BS-3 | globaler Reduced-Motion-Block schaltet Bewegung flächig ab |
| AK-27 | BS-3, BS-7 | nichts blinkt/bewegt sich > 5 s ohne Stopp (im Bestand ohnehin nicht vorhanden) |
| AK-28 | Bestand + BS-5 | `<html lang="{{ app.request.locale }}">` bereits gesetzt; je Seite bestätigen |
| AK-29 | BS-5 | `lang`-Attribut an fremdsprachigen Abschnitten |
| AK-30 | BS-5 | eindeutige, beschreibende `<title>`-Blöcke je Seite |
| AK-31 | BS-1 | `<header>`/`<nav>`/`<main>`/`<footer>` als getrennte Landmarks (Bestand + Schärfung) |
| AK-32 | Bestand | `<details>`/Cookie-Banner melden Zustand selbst; neu Hinzugefügtes folgt dem |
| AK-33 | BS-5 | sprechende Linktexte, „hier"/„mehr" ersetzt |
| AK-34 | BS-8 | Abmeldeknopf auf der Profilseite (mobil über Bottom-Nav erreichbar) |
| AK-35 | Bestand (BF-72) | Sprachumschalter ist mobil im Header sichtbar — Regressionstest sichert es |
| AK-36 | BS-8 | „Vorschlagen" in die Fußzeile; B2B-Wege dort bereits vorhanden |
| AK-37 | BS-9 | Offline-Seite: `lang`, `<title>`, Kontrast, Fokus, bedienbarer Button |
| AK-38 | BS-9 | Mail-Kontrast, sprechende Links, ohne Bilder vollständig |
| AK-39 | BS-10, Decision #9 | Auf/Ab-Alternative zur Bildsortierung; Anlegen/Bearbeiten tastaturfähig |
| AK-40 | BS-2, BS-10 | Admin-Feldfokus sichtbar und kontrastmodus-fest (`outline:none` entfernt) |
| AK-41 | BS-10, Decision #10 | Tom-Select-Ansage von Vorschlägen + Auswahlbestätigung (Widget-Prüfung) |
| AK-42 | BS-1/Fußzeile | Footer-Link „Barrierefreiheit" (`footer.accessibility_statement`) auf jeder Seite |
| AK-43 | BS-11 | Erklärung nennt Grad, Datum, Verfahren, Geltungsbereich, nicht-zugängliche Inhalte, Rückmeldeweg; **keine** gesetzliche Beschwerdestelle, dafür Freiwilligkeitshinweis (Decision #13/14) |
| AK-44 | BS-11, Decision #5 | vier Sprachen vollständig aus den Katalogen |
| AK-45 | BS-11, BS-13 | Liste nicht zugänglicher Inhalte (Kriterium · Grund · Datum) aus der Prüfmatrix |
| AK-46 | BS-11 | Veralterungshinweis ab 12 Monaten (Datum-gegen-heute, Muster Finanzzahlen) |
| AK-47 | BS-11 | Rechtslage als freiwillige Selbstverpflichtung; Endtext-Abnahme = UA-01 (Spec) |
| AK-48 | BS-11/12 | Formular **und** Kontaktadresse im Klartext |
| AK-49 | BS-12 | Absenden ohne E-Mail-Adresse geht durch (E-Mail optional) |
| AK-50 | BS-12, Datenmodell | nichts wird gespeichert — kein DB-Schreibzugriff (Functional-Test) |
| AK-51 | BS-12 | Bestätigung, Fokus wandert (`tabindex=-1 autofocus role=status`), `aria-live` sagt an |
| AK-52 | Missbrauchsschutz | Limiter `accessibility_report`, 429 + genannte Wartezeit |
| AK-53 | Missbrauchsschutz | Honeypot ohne `Blank`-Constraint; Treffer ⇒ Erfolg ohne Versand |
| AK-54 | BS-13 | automatischer A11y-Lauf + Struktur-Tests werden bei Verstoß rot |
| AK-55 | BS-13 | Prüfmatrix: je Kriterium erfüllt/nicht/nicht anwendbar (+ Begründung) |
| AK-56 | Datenmodell, Externe Dienste | Beschreibung landet nur im Postfach — nicht DB, nicht Log, nicht Sentry |
| AK-57 | Externe Dienste | Fehlerpfad loggt nur Klasse + Statuscode; kein Body-Text (bewusste Regel, nicht `SecretMaskingProcessor`) |
| AK-58 | BS-12, Datenmodell | Formular verlangt nur die Beschreibung; keine Art/Name/Geburtsdatum |
| AK-59 | Zugriffsregeln | Erklärungsseite öffentlich, keine Anmeldung |
| AK-60 | Missbrauchsschutz | Same-Origin-CSRF wie jedes Projektformular |

Jede Zeile ist belegt. Bleibt in einer späteren Runde eine offen, ist der Entwurf zu
ergänzen, nicht das Kriterium zu streichen.

## Was der Entwurf bewusst offen lässt

- **UA-01** (Spec): Der Endtext der Erklärung wird vor Veröffentlichung juristisch
  abgenommen — der Bau läuft bis dahin, der Go-live nicht.
- **OF-01** (Spec, Decision #10): Ob Tom Select und Bildergalerie bestehen oder ersetzt
  werden, entscheidet das erste Prüfergebnis (AK-06, AK-41). BS-2/BS-10 setzen die
  CSS-/Ansage-Härtung, die unabhängig davon gilt.
