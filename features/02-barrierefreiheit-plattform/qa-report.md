# 02 · Barrierefreiheit der Plattform — Testbericht

Stand: 2026-08-26 (fünfter Durchlauf) · Geprüft gegen `spec.md` vom 2026-08-26

## Fünfter Durchlauf — BF-76 behoben, kein Befund mehr offen

- **BF-76 behoben ✅** — alle `focus:outline-none`-Klassen entfernt: `OpeningHourType.php`,
  `_passkey_manage.html.twig` und fünf Profil-Formularfelder (`profile/index.html.twig`,
  fielen beim Fix auf). `AccessibilityInteractionTest` grün (Admin- und Profil-`<main>` ohne
  outline-none); axe-Lauf weiterhin grün; **510 Tests grün**.
- AK-04 und AK-40 damit ✅.

**Kein Befund mehr offen** (BF-73/74/75/76 alle behoben und verifiziert).

## Vierter Durchlauf — interaktive/AX-Vertiefung

Auf Nachfrage: die zuvor als „nicht automatisiert prüfbar" geführten Kriterien doch mit
Werkzeugen geprüft — Accessibility-Tree (was ein Screenreader bekommt), `forced-colors`
(Windows-Kontrastmodus), CSS-Injektion (WCAG 1.4.12 Textabstände), Tastatur-Simulation
und `WebTestCase` mit `loginUser` (eingeloggte Wege). **16 Kriterien so belegt — und zwei
echte Befunde gefunden, die drei Durchläufe nicht sahen.**

**Jetzt objektiv bestanden** (playwright-core + Brave, bzw. WebTestCase):
AK-02/03/07/08 (Fokus-Durchlauf: sichtbarer Fokus, keine Falle, kein positives tabindex),
AK-04 *auf öffentlichen Seiten* (forced-colors), AK-13 (aus dem 320px-Nachweis),
AK-16 (Textabstände — der einzige „Treffer" war der bewusst versteckte Skip-Link),
AK-21 (Pflicht als `required` im DOM→AX-Tree), AK-24 (`aria-live`-Ansageregion, WebTestCase),
AK-29 (`lang`-Auszeichnung), AK-30 (Titel seitenübergreifend eindeutig),
AK-32 (natives `<details>` meldet expanded), AK-34 (Abmelden-Knopf im Profil, WebTestCase).

**Neuer Befund BF-76 (mittel):** `forced-colors` + WebTestCase decken auf, dass zwei
interaktive Felder noch `focus:outline-none` tragen — `src/Form/OpeningHourType.php:16`
(Öffnungszeiten im Admin) und `templates/partials/_passkey_manage.html.twig:61`
(Passkey-Umbenennung). Im Kontrastmodus verschwindet dort der Fokus (AK-04/AK-40). Die
Härtung war auf `templates/` beschränkt und ließ `src/Form/` sowie eingebundene
Profil-Partials aus.

**Weiterhin ⚠️** (echte Grenzen): AK-06/AK-39 (keine Restaurant-Fotos in der dev-DB →
Galerie/Bildsortierung nicht auslösbar), AK-41 (Tom-Select-Ansage: JS-erzeugt, im
Browser-Login nicht erreicht — Code durch T15/code-review belegt), AK-38 (Mail wird nicht
im Browser gerendert), AK-46 (Veralterungshinweis: Logik belegt, Grenzdatum nicht
ausgeführt), **AK-47** (Rechtslage — juristisch, UA-01: das eine echt nicht Automatisierbare).

## Dritter Durchlauf — BF-75 verifiziert, kein Befund mehr offen

- **BF-75 behoben ✅** — playwright 320px über **alle 11 öffentlichen Routen**: overflow
  0px überall (vorher `/de/restaurants` 28px). AK-14 damit vollständig bestanden, kein
  weiterer Reflow-Verstoß. AK-13 (Overflow-Aspekt bei 200 %) ist durch den strengeren
  320px-Nachweis mitgedeckt.
- **Keine neue Regression** — 506 Tests grün, der BF-75-Fix ist reines Layout (`flex-wrap`).
- Kein separater code-review/Angriffsdurchlauf für diese eine CSS-Zeile: die
  substanziellen Reparaturen (BF-74/73) und der Sicherheitsdurchlauf (unten) wurden in
  den vorigen Durchläufen ausgeführt und sind durch den Layout-Fix unberührt.

**Damit ist kein Befund mehr offen** (BF-74, BF-73, BF-75 alle behoben und verifiziert).

## Zweiter Durchlauf — Reparatur verifiziert

Nach `/sdd-build 02` (BF-74, BF-73):

- **BF-74 behoben ✅** — playwright: erster Tab beim Erstbesuch (Banner sichtbar) →
  `#hauptinhalt "Zum Inhalt springen"` (PASS); nutzergetriggerter Fokus (Footer-Klick)
  weiterhin im Banner (PASS, keine Regression). code-reviewer bestätigt.
- **BF-73 behoben ✅** — `AccessibilityReportMailerTest::testMessengerTransportFailureIsAlsoCaught`
  grün; code-reviewer bestätigt den catch per Symfony-Internals als exakt richtig.
- **axe-core (12 Routen) weiterhin grün** — die Reparatur brach nichts.
- **Neu geprüft:** AK-26 (reduced-motion) ✅, AK-01 ✅ — **aber AK-14 ❌ (neuer Befund
  BF-75):** `/de/restaurants` läuft bei 320px um 28px waagerecht über (Sortier-Leiste
  ohne `flex-wrap`).

Damit sind die blockierenden Befunde des ersten Durchlaufs zu; das tiefere Prüfen fand
mit BF-75 einen neuen mittleren Verstoß.

---

Stand erster Durchlauf: Geprüft gegen `spec.md` vom 2026-08-26

⚠ **Hinweis zur Unabhängigkeit:** Bau und QA liefen in derselben Sitzung beim selben
Agenten. Um den blinden Fleck zu mildern, stützt sich dieser Bericht auf **ausgeführte**
Nachweise (PHPUnit, axe-core über Brave, Browser-Tastaturtests via playwright-core,
echte HTTP-Angriffe, unabhängiger `code-reviewer`) statt auf Codelektüre. Der zentrale
Befund BF-74 wurde von Bau, code-review, axe und den Struktur-Tests **nicht** gesehen —
erst der Browser-Tastaturangriff fand ihn.

## Fazit

**Production-ready: ja (der Code) — mit klar benannter Restauflage**

Nach fünf Durchläufen ist **kein Befund mehr offen**: BF-73/74/75/76 sind behoben und
verifiziert. Die interaktive Vertiefung hat den belegten Anteil auf **53 von 60** gehoben —
Fokus-Sichtbarkeit (auch im Kontrastmodus), Tastaturweg, AX-Tree-Ansagen, Struktur, Reflow,
der Meldeweg datenschutzseitig sauber (nichts gespeichert, kein PII im Log, Rate-Limit
greift, Payload nur ans Postfach), 510 Tests grün, axe über zwölf Routen ohne Verstoß. **Es
blockiert kein Befund.** Der Code ist deploybar.

⚠ **Sechs Kriterien bleiben unbelegt** — echte Grenzen, kein Mangel des Codes: AK-06/AK-39
(keine Restaurant-Fotos in der dev-DB → Galerie/Bildsortierung nicht auslösbar), AK-41
(Tom-Select-Ansage JS-erzeugt, im Browser-Login nicht erreicht), AK-38 (Mail nicht im
Browser gerendert), AK-46 (Veralterungshinweis: Logik belegt, Grenzdatum nicht ausgeführt)
und **AK-47** (Rechtslage, UA-01 — juristisch). Die Erklärungsseite behauptet weiterhin
keinen Konformitätsgrad (`tested_on` leer → „Prüfung ausstehend", EC-07); der öffentliche
Konformitäts**anspruch** wird erst nach der manuellen Abnahme und UA-01 aktiviert.

Nächster Schritt: **`/sdd-deploy 02`** — mit dem Vermerk, dass UA-01 und die letzten
Testdaten-/JS-/Mail-Prüfungen vor dem Setzen des Konformitätsgrads erfolgen.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 60 von 60 |
| davon bestanden | 53 (AK-04 + AK-40 nach BF-76-Fix dazu) |
| davon durchgefallen | 0 |
| **nicht prüfbar** (Galerie/Testdaten, Tom-Select-JS, Mail, Veralterung, UA-01) | 6 |
| nicht anwendbar | 1 (AK-23) |
| Edge Cases belegt | 4 von 8 (4 nur manuell prüfbar) |
| Tests neu geschrieben | 3 Dateien + 1 Regressionsfall (BF-73) |
| Tests grün | 506 von 506 (2 vorbestehende Skips) |

## Akzeptanzkriterien im Einzelnen

Legende: ✅ ausgeführt & bestanden · ❌ ausgeführt & durchgefallen · ⚠️ nicht prüfbar · ➖ nicht anwendbar

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | nach Reparatur BF-74 — playwright: erster Tab (Erstbesuch, Banner sichtbar) → Skip-Link (PASS) |
| AK-02 | ⚠️ | vollständiger Tastaturweg über alle Elemente nur manuell; axe ohne ARIA-Fehler |
| AK-03 | ⚠️ | globaler `:focus-visible` (app.css) umgesetzt; Sichtbarkeit auf jedem Untergrund visuell zu prüfen |
| AK-04 | ✅ | forced-colors PASS; alle `outline-none`-Felder auf echte `outline` umgestellt (BF-76 behoben) |
| AK-05 | ✅ | `AccessibilityStructureTest` (`main#hauptinhalt`, `scroll-mt-24`) |
| AK-06 | ⚠️ | **OF-01** — GLightbox Escape/Fokusrückgabe nicht abschließend geprüft |
| AK-07 | ⚠️ | Menü/Banner keine Dauerfalle; manuell (der Banner-Fokusfang ist BF-74) |
| AK-08 | ✅ | kein positives `tabindex` (Struktur-Test/grep); Reihenfolge = DOM |
| AK-09 | ✅ | axe `image-alt` grün; Admin-Alt-Text-Pflicht (T15) |
| AK-10 | ✅ | axe grün; dekoratives `aria-hidden` |
| AK-11 | ✅ | axe `color-contrast` grün (12 Routen) |
| AK-12 | ✅ | axe grün (Diagramme/UI) |
| AK-13 | ⚠️ | 200 % Zoom visuell zu prüfen; mobile-first umgesetzt |
| AK-14 | ✅ | playwright 320px über alle 11 öffentlichen Routen: overflow 0px (BF-75 behoben) |
| AK-15 | ✅ | `AccessibilityStructureTest` (genau ein `<h1>`, 11 Routen) |
| AK-16 | ⚠️ | Textabstands-Erhöhung visuell zu prüfen |
| AK-17 | ✅ | axe `link-in-text-block` grün (Links unterstrichen) |
| AK-18 | ✅ | axe `label`/`select-name` grün; code-review |
| AK-19 | ✅ | `_form_field` (`aria-describedby`), code-review; Registrierung/Wizard |
| AK-20 | ✅ | serverseitiges `autofocus` erstes Fehlerfeld (`_form_field`); code-review |
| AK-21 | ⚠️ | `required`/`aria-required` gesetzt; Screenreader-Ansage manuell |
| AK-22 | ✅ | code-review: `autocomplete`-Tokens in Formularen/`RegistrationType` |
| AK-23 | ➖ | Wizard erhebt jede Angabe genau einmal (T13 dokumentiert) |
| AK-24 | ⚠️ | `aria-live`-Schrittansage umgesetzt; Screenreader-Ausgabe manuell |
| AK-25 | ✅ | axe `target-size` (WCAG 2.2 AA) grün |
| AK-26 | ✅ | playwright `reducedMotion: reduce`: Transitions auf 0,01ms (globaler Block greift) |
| AK-27 | ✅ | `animate-ping` → `motion-safe:` (T11); sonst nichts Blinkendes |
| AK-28 | ✅ | `AccessibilityStructureTest` (`html lang`) |
| AK-29 | ⚠️ | `lang`-Wechsel an Fremdsprachabschnitten manuell |
| AK-30 | ⚠️ | eindeutige `<title>` je Seite — nicht seitenübergreifend abgeglichen |
| AK-31 | ✅ | `AccessibilityStructureTest` (Landmarks) |
| AK-32 | ⚠️ | `<details>` meldet Zustand (Bestand); Screenreader manuell |
| AK-33 | ✅ | axe `link-name` grün; toter Footer-Link behoben |
| AK-34 | ✅ | Abmelden-Karte im Profil (T16); `ProfileControllerTest` grün |
| AK-35 | ✅ | Sprachumschalter mobil (Bestand BF-72) |
| AK-36 | ✅ | „Vorschlagen"/B2B in der Fußzeile (T08, Struktur) |
| AK-37 | ✅ | axe grün auf `offline.html`; `lang`/`title`/Button (T17) |
| AK-38 | ⚠️ | Mail-Kontrast angehoben (T17); Mail wird nicht im Browser gerendert → axe deckt sie nicht |
| AK-39 | ⚠️ | Auf/Ab-Knöpfe + `aria-label` umgesetzt (T15); Tastaturbedienung nicht live geprüft |
| AK-40 | ✅ | `AccessibilityInteractionTest`: Admin-`<main>` ohne `outline-none` (BF-76 behoben) |
| AK-41 | ⚠️ | **OF-01** — Tom-Select-*Entfernen*-Ansage offen |
| AK-42 | ✅ | Footer-Link + Route `app_accessibility` (Struktur) |
| AK-43 | ✅ | `accessibility/index` + Prüfmatrix; keine gesetzliche Beschwerdestelle (Decision #13) |
| AK-44 | ✅ | `CatalogueCompletenessTest` (vier Sprachen deckungsgleich) |
| AK-45 | ✅ | Prüfmatrix `docs/barrierefreiheit-pruefung.md` |
| AK-46 | ⚠️ | Veralterungslogik im Controller; nicht mit Grenzdatum ausgeführt getestet |
| AK-47 | ⚠️ | **UA-01** — freiwillige Selbstverpflichtung formuliert; juristische Abnahme offen |
| AK-48 | ✅ | `AccessibilityControllerTest`; Formular + Kontaktadresse |
| AK-49 | ✅ | `AccessibilityControllerTest` + `AccessibilityReportMailerTest` |
| AK-50 | ✅ | `AccessibilityControllerTest` (kein DB-Schreibzugriff); keine Entity |
| AK-51 | ✅ | playwright: Fokus wandert nach Turbo-Submit auf `role=status` (PASS) |
| AK-52 | ✅ | Live-Angriff: 6. POST → HTTP 429 |
| AK-53 | ✅ | `AccessibilityControllerTest` (Honeypot → Erfolg ohne Mail) |
| AK-54 | ✅ | `AccessibilityStructureTest` + `bin/a11y-audit.mjs` (beide grün) |
| AK-55 | ✅ | Prüfmatrix (dieses Feature) |
| AK-56 | ✅ | `AccessibilityReportMailerTest` (nur contact_email, replyTo); dev.log ohne Meldetext |
| AK-57 | ✅ | `AccessibilityReportMailerTest` (nur Klasse+Code) + dev.log-grep (0 Treffer) |
| AK-58 | ✅ | `AccessibilityReportType` (nur Beschreibung Pflicht); code-review |
| AK-59 | ✅ | `AccessibilityControllerTest` (öffentlich, 200 ohne Login) |
| AK-60 | ✅ | Live: Same-Origin-POST akzeptiert; stateless CSRF (code-review) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ⚠️ | No-JS-Redirect-Fallback im Controller; JS-aus-Bedienung manuell |
| EC-02 | ⚠️ | Kontrastmodus + 400 % nicht automatisiert prüfbar |
| EC-03 | ✅ | Alt-Text-Fallback `?: restaurant.name` (T11); Admin-Pflicht (T15) |
| EC-04 | ⚠️ | Mailer-Transportfehler → false + Meldung (Unit-Test); **aber** anderer Zustellfehler → 500 (BF-73) |
| EC-05 | ✅ | axe grün auf `/open`; Diagramm-Tabellen vorhanden (T12) |
| EC-06 | ⚠️ | Cookie-Banner mit Tastatur — hier zeigte sich BF-74 |
| EC-07 | ✅ | `tested_on` leer → „Prüfung ausstehend" (Controller) |
| EC-08 | ✅ | `CatalogueCompletenessTest` verhindert Schlüssel-als-Text |

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | ✅ nicht anwendbar | Meldung erzeugt keinen adressierbaren Datensatz (keine Entity/ID) |
| Rate Limit greift | ✅ bestanden | Live: 6 POSTs → `1–5: 500` (BF-73), `6: 429` — Deckel greift ab dem 6. |
| PII in Logs | ✅ bestanden | `grep 'QA Rate-Limit Test' var/log/dev.log` → 0 Treffer, auch beim 500 |
| PII an externe Dienste | ✅ bestanden | `AccessibilityReportMailerTest`: nur `To=contact_email`, `replyTo=Melder`, kein weiterer Empfänger |
| Zugriffsregeln serverseitig | ✅ bestanden | `/accessibility` öffentlich (AK-59-Test); keine Rolle/kein Objektzugriff |
| Geheimnisse im Repository | ✅ bestanden | grep der neuen Dateien: kein Secret; kein neues env |
| Eingaben | ⚠️ teilweise | leer → 422 (Test); Meldetext wird nicht auf der Seite reflektiert (kein XSS-Rückgabeweg), Mail-Template auto-escaped; lange/Sonderzeichen-Eingabe nicht live durchgespielt (IP gedrosselt) |
| Löschen | ✅ nicht anwendbar | nichts gespeichert (AK-50) |

## Fehler

### BF-76 · `focus:outline-none` an interaktiven Feldern — mittel — ✅ BEHOBEN (fünfter Durchlauf)

Behoben an drei Stellen (`OpeningHourType.php:16`, `_passkey_manage.html.twig:61`, plus
fünf Feldern in `profile/index.html.twig`, die beim Fix auffielen).

**Betrifft:** AK-04, AK-40 (WCAG 2.4.7 / 1.4.11, Windows-Kontrastmodus)
**Reproduktion:** Browser mit `forced-colors: active` (bzw. `AccessibilityInteractionTest`):
das gerenderte HTML (Admin-Bearbeiten-`<main>` bzw. Profil-`<main>`) enthält `outline-none`.
**Fundstellen:**
- `src/Form/OpeningHourType.php:16` — `$timeAttr` mit `focus:ring-2 … focus:outline-none` (Öffnungszeiten-Felder im Admin-Restaurant-Formular)
- `templates/partials/_passkey_manage.html.twig:61` — Passkey-Umbenennungs-Eingabe im Profil
**Ursache:** Die Härtung (T14–T17) war auf `templates/` beschränkt und ließ `src/Form/`
sowie das eingebundene Profil-Partial `_passkey_manage` aus.
**Vorschlag:** `focus:outline-none` durch echte `focus:outline-2 focus:outline-offset-2
focus:outline-purple-700` ersetzen, wie T15 es im übrigen Admin tat.

### BF-75 · `/de/restaurants` läuft bei 320px waagerecht über — mittel — ✅ BEHOBEN (dritter Durchlauf)

**Betrifft:** AK-14 (WCAG 1.4.10 Reflow)
**Reproduktion:** Browser auf 320px Breite, `/de/restaurants` — `document.scrollWidth − clientWidth = 28px` (waagerechtes Seiten-Scrollen). `/de/` und `/de/accessibility` sind 0px.
**Ursache:** die Sortier-Leiste (`<div class="flex items-center gap-2">` mit den Sortier-Links, `restaurant/index.html.twig`) bricht bei 320px nicht um — rechte Kante bei 348px.
**Vorschlag:** `flex-wrap` an der Leiste (bzw. der Filter-/Sortierzeile) ergänzen.

### BF-74 · Skip-Link beim Erstbesuch nicht erstes Tab-Ziel — hoch — ✅ BEHOBEN (zweiter Durchlauf)

**Betrifft:** AK-01, EC-06
**Reproduktion:**
1. Frischen Browser (kein `cookie_consent`-Cookie) auf `/de/accessibility`
2. Einmal Tab drücken
**Erwartet:** Fokus auf dem sichtbaren Link „Zum Inhalt springen" (`#hauptinhalt`)
**Tatsächlich:** Fokus auf dem Cookie-Banner-Link „Datenschutzerklärung" (`/de/legal#datenschutz`) — belegt per playwright (`activeElement`)
**Ursache:** `assets/controllers/cookie_consent_controller.ts:59` ruft beim `connect()` `this.bannerTarget.focus()`; der Erstbesuch-Banner zieht den Fokus, der erste Tab läuft in den Banner statt zum Skip-Link. Mit gesetztem Consent-Cookie (Banner weg) ist der Skip-Link das erste Tab-Ziel (verifiziert: PASS).
**Vorschlag:** Der Banner darf den initialen Fokus nicht fangen (kein `focus()` beim `connect()`, stattdessen z. B. Ansage per `aria-live`), oder der Skip-Link erhält Vorrang. Entscheidung in `sdd-build`.

### BF-73 · Zustellfehler außerhalb `Mailer\TransportException` → HTTP 500 — niedrig

**Betrifft:** EC-04, AK-51 (Fehlerpfad)
**Reproduktion:** POST einer gültigen Meldung, während der async-Messenger-Transport (dev: `doctrine`) die DB nicht erreicht → `Messenger\Exception\TransportException` (nicht `Mailer\...`) → HTTP 500 statt der freundlichen Fehlermeldung.
**Erwartet:** freundliche Meldung, Text bleibt erhalten (EC-04)
**Tatsächlich:** HTTP 500 (Live beobachtet, Versuche 1–5). Der Meldetext gelangt dabei **nicht** ins Log (AK-57 gewahrt).
**Bewertung:** In prod (`MESSENGER_TRANSPORT_DSN=sync://`) tritt dieser Weg nicht auf; der Mailer spiegelt das approved Waitlist-Muster. Deshalb niedrig.
**Ort:** `src/Service/AccessibilityReportMailer.php:59` (`catch (TransportExceptionInterface)`)
**Vorschlag:** breiter fangen (z. B. zusätzlich `Messenger\Exception\TransportException` bzw. `\Throwable` mit Log ohne PII), damit EC-04 unabhängig vom Transport gilt.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Unit/Service/AccessibilityReportMailerTest.php` | 3 | AK-49, AK-56, AK-57 (Payload/Logging am tatsächlichen Verhalten) |
| `tests/Functional/Controller/AccessibilityControllerTest.php` | 6 | AK-01(Struktur), AK-49/50/51/53/56/59 |
| `tests/Functional/AccessibilityStructureTest.php` | 22 | AK-01/05/15/28/31/54 über 11 Routen |

## Nicht prüfbar — was die Handprüfung leisten muss

Die 26 ⚠️-Kriterien brauchen einen Menschen mit assistiver Technik: echter Screenreader
(Ansagen AK-21/24/32/41), Windows-Kontrastmodus (AK-04/40), visueller Zoom/Reflow
(AK-13/14/16), `prefers-reduced-motion`-Emulation (AK-26), JS-aus (EC-01), plus OF-01
(GLightbox/Tom-Select) und UA-01 (Rechtsabnahme). Der axe-Lauf (`bin/a11y-audit.mjs`)
deckt das maschinelle Drittel und ist reproduzierbar.

## Nächster Schritt

**Fünfter Durchlauf:** BF-76 behoben — `AccessibilityInteractionTest` grün, axe grün,
510 Tests grün, **kein Befund mehr offen**. Nächster Schritt **`/sdd-deploy 02`**. Es
verbleiben sechs unbelegte Kriterien (Testdaten/JS/Mail/Veralterung) und **UA-01** als
Vorbedingung für den Konformitätsgrad — nicht für die Auslieferung des Codes.
