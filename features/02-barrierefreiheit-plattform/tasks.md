# 02 · Barrierefreiheit der Plattform — Aufgabenplan

Status: `building` · Stand: 2026-08-26

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation. **Rot heißt anhalten.**

> **Bewusste Ebenen-Zuordnung:** Bei diesem Feature ist Barrierefreiheit der Gegenstand,
> nicht der Feinschliff. Die Härtung der Bestandstemplates (Kontrast, Fokus, Struktur,
> Zielgrößen) steht deshalb in **Ebene 4**, bereichsweise gebündelt. Ebene 5 trägt nur
> den automatisierten Nachweis (AK-54/55) und die Randfälle (EC-NN).

## Ebene 1 · Fundament — Daten und Konfiguration

Keine Migration (bewusst keine Tabelle, siehe design.md → Datenmodell).

- [x] **T01** `[P]` · Limiter `accessibility_report` (sliding_window, 5/Stunde, IP) in
      `config/packages/framework.yaml` anlegen **und** `when@test`-Override auf 10000 —
      Grundlage für T07, `AK-52`
- [x] **T02** `[P]` · Erklärungs-Parameter in `config/services.yaml`: Konformitätsgrad,
      Prüfdatum (echtes Datum), Prüfverfahren, Geltungsbereich und strukturierte Liste
      nicht zugänglicher Inhalte (Kriterium · Grund · Datum); als Twig-Global verfügbar
      machen — Grundlage für T06/T10, `AK-43, AK-45, AK-46`
- [x] **T03** `[P]` · Alle neuen Übersetzungsschlüssel für Feature 02 in **allen vier**
      `messages`- und `validators`-Katalogen anlegen (Skip-Link, `footer.accessibility_statement`,
      Domäne `accessibility_statement.*`, Meldeformular-Labels/-Fehler, Wizard-Ansagen,
      Offline-Texte, Bestätigung) — Grundlage für Ebene 4, `AK-44`

**Verifikation:** `php bin/console lint:yaml config/ translations/` · `php bin/console lint:container` · `php bin/console debug:translation de --only-missing`

## Ebene 2 · Server — Logik und Validierung

- [x] **T04** `[P]` · `AccessibilityReportType`: Beschreibung (Pflicht, `NotBlank`,
      `empty_data => ''`, Textarea), E-Mail (optional, `Email`), Honeypot `website`
      (`mapped: false`, **kein** `Blank`-Constraint, `tabindex=-1`) — `AK-49, AK-53, AK-58`
- [x] **T05** `[P]` · `AccessibilityReportMailer`: sendet die Meldung als `TemplatedEmail`
      an `app.contact_email`, `replyTo` nur bei angegebener Adresse, **kein `persist`/
      `flush`**; fängt `TransportExceptionInterface` und loggt ausschließlich Klasse +
      Statuscode, **niemals** Beschreibung oder Melderadresse — `AK-50, AK-56, AK-57`

**Verifikation:** `make fix-check` (Exit 8 = zu tun) · `php bin/console lint:container` · `php bin/phpunit tests/Unit`

## Ebene 3 · Schnittstellen

- [x] **T06** · `AccessibilityController::index` (GET `/accessibility`, öffentlich, **kein**
      `#[IsGranted]`): rendert Erklärung + Formular, berechnet den Veralterungshinweis aus
      Prüfdatum gegen „heute" (≥ 12 Monate); Route bleibt öffentlich, keine
      `access_control`-Kollision — `AK-42, AK-43, AK-46, AK-59`
- [x] **T07** · `AccessibilityController::report` (POST `/accessibility`): `ActionLimiter`
      `isAllowed()` → 429 mit Wartezeit; `handleRequest`; Honeypot-Treffer → dieselbe
      Erfolgsantwort ohne Versand; bei gültig `consume()` + `AccessibilityReportMailer`;
      Turbo-Stream-Erfolg (No-JS-Redirect-Fallback), 422 bei ungültig, Same-Origin-CSRF —
      `AK-48, AK-51, AK-52, AK-53, AK-60`

*(T06 und T07 liegen in derselben Controller-Datei → seriell, kein `[P]`.)*

**Verifikation:** `php bin/console debug:router | grep accessibility` · `php bin/phpunit --filter Accessibility` · `make fix-check`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt. Neue Keys kommen
ausschließlich aus T03 — eine Oberflächenaufgabe, der ein Key fehlt, **meldet** das,
legt ihn aber nicht selbst an (sonst bräche die `[P]`-Zusage an den Katalogen).

- [x] **T08** `[P]` · `templates/base.html.twig`: Skip-Link „Zum Inhalt springen" als
      erstes fokussierbares Element, `<main id="hauptinhalt" tabindex="-1">` mit
      `scroll-margin` gegen den `sticky`-Header, Landmarks bestätigen; Fußzeile: Link
      „Barrierefreiheit", „Restaurant vorschlagen" ergänzen, toten `href="#"` reparieren,
      Copyright-Graustufe auf ≥ 4,5:1 anheben — `AK-01, AK-05, AK-07, AK-11, AK-31, AK-33, AK-36, AK-42`
- [x] **T09** `[P]` · `assets/styles/app.css`: globaler `:focus-visible`-Fallback (echte
      `outline`, kontraststark, weiß auf Verlaufsgrund), globaler
      `@media (prefers-reduced-motion: reduce)`-Block, Tom-Select-Fokus von `outline:none`
      auf echte `outline` — `AK-03, AK-04, AK-26, AK-27`
- [x] **T10** `[P]` · `templates/accessibility/` (index + `success.stream.html.twig` +
      `_success`-Partial) und `templates/email/accessibility_report.html.twig`: Hero,
      Erklärungsblöcke, Veralterungshinweis, Rückmeldeweg mit Kontaktadresse im Klartext
      und Meldeformular über `_form_field`, Erfolgsziel (`tabindex=-1 autofocus
      role=status` + `aria-live`); Aufklapper melden Zustand — `AK-30, AK-32, AK-43, AK-44, AK-45, AK-47, AK-48, AK-51`
- [x] **T11** `[P]` · Publikumsseiten `templates/{home,restaurant,about}/` härten: genau
      ein `<h1>` + lückenlose Ebenen, Kontrast, Fokus-Sonderfälle, Alt-Texte auf der
      Detailseite, Bildergalerie (Escape/Fokusrückgabe, OF-01), dekoratives `aria-hidden`,
      sprechende Linktexte, 24-px-Zielgrößen, 200 %/320 px/Textabstand, `lang`-Wechsel —
      `AK-06, AK-08, AK-09, AK-10, AK-13, AK-14, AK-15, AK-16, AK-17, AK-25, AK-29, AK-30, AK-33`
- [x] **T12** `[P]` · Außen-/Datenseiten `templates/{partner,organisation,open}/` härten:
      dieselben Regeln plus Diagramm-/UI-Kontrast ≥ 3:1 auf `/open` — `AK-08, AK-10, AK-11, AK-12, AK-13, AK-14, AK-15, AK-16, AK-17, AK-25, AK-29, AK-30, AK-33`
- [x] **T13** `[P]` · `templates/community/vorschlagen` + `suggestion_wizard_controller.ts`:
      Struktur/Kontrast/Zielgrößen, Wizard-Schrittwechsel in `aria-live`-Region ansagen,
      im selben Vorgang erhobene Angaben im späteren Schritt vorbelegen —
      `AK-08, AK-15, AK-23, AK-24, AK-25, AK-30`
- [x] **T14** `[P]` · Formularseiten `templates/{security,registration}/` (+ `RegistrationType`):
      `_form_field` einziehen bzw. ARIA nachrüsten — Label-`for`/`id`, Fehlertext am Feld,
      `autofocus` ins erste Fehlerfeld, Pflicht-Ansage, `autocomplete`-Tokens (Name,
      E-Mail, Telefon) — `AK-18, AK-19, AK-20, AK-21, AK-22`
- [x] **T15** `[P]` · Admin `templates/admin/` + `image_sort_controller.ts` +
      `tom_select_controller.ts`: Tastaturweg für Anlegen/Bearbeiten, Fokus von
      `focus:ring… outline-none` auf echte `outline`, Kontrast, Alt-Text-Pflicht beim
      Bildupload, **Auf/Ab-Knöpfe als Alternative zum Ziehen** (nutzen den bestehenden
      Sortier-Endpunkt), Tom-Select-Vorschläge ansagen und Auswahl bestätigen —
      `AK-04, AK-08, AK-09, AK-39, AK-40, AK-41`
- [x] **T16** `[P]` · `templates/profile/index.html.twig`: Abmeldeknopf (CSRF-Formular wie
      im Header), damit Abmelden auf dem Telefon über die Bottom-Nav → Profil erreichbar ist
      — `AK-34`
- [x] **T17** `[P]` · `templates/email/base.html.twig` und `public/offline.html` härten:
      Mail-Footer-Kontrast anheben, sprechende Links, ohne geladene Bilder vollständig;
      Offline-Seite mit `lang`, eigenem `<title>`, Kontrast, sichtbarem Fokus, bedienbarem
      Button — `AK-11, AK-37, AK-38`

**Verifikation:** `php bin/console lint:twig templates/` · `npm run typecheck && npm run lint` · `npm run build` (danach `public/build` mitcommitten) · `php bin/phpunit --testsuite Functional`

## Ebene 5 · Feinschliff — Nachweis und Randfälle

- [x] **T18** `[P]` · Automatischer A11y-Prüflauf: axe-core (WCAG-2.2-AA-Regeln) über eine
      **Ausgeführt und grün:** `bin/a11y-audit.mjs` (playwright-core + axe-core, Brave) über 12
      Routen — von 31 Verstößen auf 0. Symfony-Debug-Toolbar (dev-only) ausgeschlossen. Die
      manuelle Tastatur-/Screenreader-Prüfung bleibt der QA vorbehalten.
      kuratierte Routenliste (je öffentlicher Seitentyp, ein Admin-Formular, die
      Offline-Seite) über die vorhandene headless-Brave/CDP-Umgebung; ein Verstoß lässt
      ihn fehlschlagen. Bestätigt zugleich die Bestands-Kriterien Fokusreihenfolge,
      Aufklapper-Zustand, Sprachwechsel auf Mobil, `lang` am Wurzelelement, gesamter
      Tastaturweg — `AK-02, AK-08, AK-28, AK-32, AK-35, AK-54`
- [x] **T19** `[P]` · Strukturelle Functional-Tests (`WebTestCase`): Skip-Link als erstes
      Fokusziel, `<main id>` vorhanden, genau **ein** `<h1>` je Route, Meldeformular
      persistiert nichts, Honeypot-Treffer → Erfolg ohne Mail, 422 bei leerer Beschreibung,
      `assertEmailCount` im Gutfall; `LimiterCoverageTest` deckt `accessibility_report` —
      `AK-01, AK-15, AK-50, AK-53`
- [x] **T20** `[P]` · Konformitäts-Prüfmatrix als Doku-Artefakt (`docs/`): je
      WCAG-2.2-AA-Kriterium „erfüllt / nicht erfüllt / nicht anwendbar" mit Begründung bei
      „nicht anwendbar"; Quelle für Konformitätsgrad, Prüfdatum und die Liste nicht
      zugänglicher Inhalte auf der Erklärungsseite — `AK-45, AK-55`
- [x] **T21** · Randfälle EC-01–EC-08 durchgehen und absichern: JS aus (Erklärung/Formular
      bedienbar), Kontrastmodus + 400 %, Bestandsfoto ohne Alt-Text (dokumentierte
      Anzeige), Versand scheitert (Text bleibt erhalten), Screenreader auf `/open`
      (Grafik-Tabellen), Cookie-Banner mit Tastatur, Erklärung vor erster Prüfung,
      lb-Katalog-Lücke (kein Schlüssel als Text) — `EC-01, EC-02, EC-03, EC-04, EC-05, EC-06, EC-07, EC-08`

**Verifikation:** `php bin/phpunit` (gesamte Suite) · axe-Lauf grün · `php bin/console debug:translation lb --only-missing` (und de/fr/en)

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T08, T19 |
| AK-02 | T18 (Bereiche T11–T15 stellen sicher) |
| AK-03 | T09 |
| AK-04 | T09, T15 |
| AK-05 | T08 |
| AK-06 | T11 (OF-01) |
| AK-07 | T08 |
| AK-08 | T11, T12, T13, T15, T18 |
| AK-09 | T11, T15 |
| AK-10 | T11, T12 |
| AK-11 | T08, T11, T12, T17 |
| AK-12 | T12 |
| AK-13 | T11, T12 |
| AK-14 | T11, T12 |
| AK-15 | T11, T12, T13, T19 |
| AK-16 | T11, T12 |
| AK-17 | T11, T12 |
| AK-18 | T14 |
| AK-19 | T14 |
| AK-20 | T14 |
| AK-21 | T14 |
| AK-22 | T14 |
| AK-23 | T13 |
| AK-24 | T13 |
| AK-25 | T11, T12, T13 |
| AK-26 | T09 |
| AK-27 | T09 |
| AK-28 | T18 (Bestand, bestätigt) |
| AK-29 | T11, T12 |
| AK-30 | T10, T11, T12, T13 |
| AK-31 | T08 |
| AK-32 | T10, T18 |
| AK-33 | T08, T11, T12 |
| AK-34 | T16 |
| AK-35 | T18 (Bestand BF-72, bestätigt) |
| AK-36 | T08 |
| AK-37 | T17 |
| AK-38 | T17 |
| AK-39 | T15 |
| AK-40 | T09, T15 |
| AK-41 | T15 |
| AK-42 | T06, T08 |
| AK-43 | T02, T06, T10 |
| AK-44 | T03, T10 |
| AK-45 | T02, T10, T20 |
| AK-46 | T02, T06 |
| AK-47 | T10 |
| AK-48 | T07, T10 |
| AK-49 | T04 |
| AK-50 | T05, T19 |
| AK-51 | T07, T10 |
| AK-52 | T01, T07 |
| AK-53 | T04, T07, T19 |
| AK-54 | T18 |
| AK-55 | T20 |
| AK-56 | T05 |
| AK-57 | T05 |
| AK-58 | T04 |
| AK-59 | T06 |
| AK-60 | T07 |

**AK ohne Aufgabe:** keine
**Aufgabe ohne AK:** T01 (Limiter, Grundlage für T07), T02 (Parameter, Grundlage für T06/T10), T03 (Übersetzungen, Grundlage für Ebene 4) — alle drei zulässig als Grundlage. T21 verweist auf EC-01–EC-08 statt AK (Randfälle, planmäßig).

## Parallelisierung

- **Ebene 1:** T01, T02, T03 gleichzeitig — `framework.yaml`, `services.yaml`,
  `translations/*.yaml` sind getrennte Dateien.
- **Ebene 2:** T04, T05 gleichzeitig — `src/Form/AccessibilityReportType.php` vs.
  `src/Service/AccessibilityReportMailer.php`.
- **Ebene 3:** keine — T06 und T07 teilen sich `AccessibilityController.php`.
- **Ebene 4:** T08–T17 gleichzeitig. Jede berührt genau einen eigenen Ordner bzw. eine
  eigene Datei; neue Katalog-Schlüssel liegen bereits aus T03 vor, die Aufgaben lesen sie
  nur. `templates/email/accessibility_report.html.twig` (T10) und
  `templates/email/base.html.twig` (T17) sind verschiedene Dateien — T10 erbt von base,
  ändert sie nicht.
- **Ebene 5:** T18, T19, T20 gleichzeitig — axe-Skript/Routenliste, `WebTestCase`-Datei,
  `docs/`-Artefakt sind getrennt. T21 läuft **seriell** (berührt diffus mehrere Templates).

## Vor dem Bauen

- [ ] Feature-Branch: `git checkout -b feature/02-barrierefreiheit-plattform`
- [ ] ⚠ **Reihenfolge (index.md):** erst die offenen Reparaturen B01, B02, B04, B23
      ausliefern (`/sdd-deploy`), **dann** `02` bauen — es fasst breit in dieselben
      Templates; paralleler Umbau erzeugt zwei Umbauten in denselben Dateien.
- [ ] Keine neuen Secrets nötig — Empfänger ist der vorhandene `app.contact_email`.
- [ ] headless-Brave/CDP-Umgebung für den axe-Lauf (T18) vorhanden (Projektgedächtnis).
- [ ] Nach Template-/CSS-Änderungen `npm run build` und `public/build` mitcommitten,
      sonst blockt `verify-assets` den Deploy.
- [ ] Offen bleibt **UA-01** (Spec): Erklärungstext vor Go-live juristisch abnehmen — der
      Bau läuft bis dahin, die Veröffentlichung nicht.
