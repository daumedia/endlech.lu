# Konformitäts-Prüfmatrix — Barrierefreiheit der Plattform (Feature 02)

Stand: 2026-08-26 · Norm: **WCAG 2.2 Stufe AA / EN 301 549** · Erklärung nach RAWeb

Dieses Dokument ist der Nachweis zu Feature `02` (AK-55) und die Datenquelle für die
öffentliche Barrierefreiheitserklärung `/accessibility` (AK-43, AK-45). Es hält je
Akzeptanzkriterium fest, ob es **erfüllt**, **umgesetzt (Verifikation ausstehend)**,
**nicht anwendbar** oder **offen** ist, mit Beleg.

## Prüfmethode

| Ebene | Werkzeug | Status |
|---|---|---|
| Strukturell (Skip-Link, Landmarks, ein H1, `lang`) | `tests/Functional/AccessibilityStructureTest` (11 Routen) | ✅ grün |
| Meldeweg (öffentlich, Mail ohne Speicherung, Honeypot, 422) | `tests/Functional/Controller/AccessibilityControllerTest` | ✅ grün |
| Limiter-Verdrahtung | `LimiterCoverageTest` | ✅ grün |
| Übersetzungs-Vollständigkeit (4 Sprachen) | `CatalogueCompletenessTest` | ✅ grün |
| Gesamte Regressionsprüfung | volle PHPUnit-Suite (480 Tests) | ✅ grün |
| Inhaltlich (Kontrast, ARIA, Alt-Texte automatisiert) | `bin/a11y-audit.mjs` (axe-core, WCAG 2.2 AA) über 12 Routen | ✅ **grün (2026-08-26)** — 31 Befunde gefunden und behoben, jetzt 0 |
| Tastaturweg & Screenreader (manuell) | Raster von Hand | ⏳ ausstehend (QA) |

**Gesamtergebnis (vorläufig):** Die strukturellen und funktionalen Kriterien sind durch
automatisierte Tests belegt; die **automatisierte inhaltliche Prüfung (axe-core, WCAG 2.2
AA) ist über alle zwölf geprüften Routen grün** — 31 Befunde wurden gefunden und behoben.
Aus bleibt die **manuelle** Prüfung von Tastaturweg, Screenreader-Ausgaben und
Kontrastmodus. **2026-08-26 hat der Betreiber den Grad „teilweise konform" gesetzt**
(`conformance_level: partial`, `tested_on: 2026-08-26`) mit den bekannten Einschränkungen
in `known_issues` (Bildergalerie-Fokusrückgabe, Tom-Select-Entfernen-Ansage, abschließende
Screenreader-Prüfung). Bewusst „partial", nicht „full": die abschließende
Screenreader-Handprüfung und **UA-01** (juristische Abnahme des Rechtstexts vor dem
Live-Gang) bleiben offen.

## Kriterienmatrix

Legende: ✅ per Test belegt · 🟡 umgesetzt, Verifikation via axe/Hand ausstehend · ➖ nicht anwendbar · ⏳ offen

| AK | Thema | Status | Beleg / Fundort |
|---|---|---|---|
| AK-01 | Skip-Link erstes Fokusziel | ✅ | `AccessibilityStructureTest`, `base.html.twig` |
| AK-02 | Vollständiger Tastaturweg | 🟡 | Bestandshärtung T11–T15; axe/Hand ausstehend |
| AK-03 | Fokus auf jedem Untergrund | 🟡 | globaler `:focus-visible` (`app.css`) + `outline-white` auf Verlauf |
| AK-04 | Fokus im Kontrastmodus | 🟡 | echte `outline` statt Ring (`app.css`, Admin T15); Windows-Kontrastmodus-Test von Hand ausstehend |
| AK-05 | Fokus nicht verdeckt | ✅ | `<main scroll-mt-24>`, `AccessibilityStructureTest` |
| AK-06 | Galerie Escape/Fokusrückgabe | ⏳ | GLightbox-Default `keyboardNavigation`; Fokusrückgabe unbestätigt → **OF-01** |
| AK-07 | Keine Tastaturfalle | 🟡 | `<details>`-Menü/Banner bestandskonform; Hand ausstehend |
| AK-08 | Fokusreihenfolge = sichtbar | 🟡 | kein positives `tabindex` (Subagenten bestätigt); Hand ausstehend |
| AK-09 | Alt-Text je Bild + Upload-Pflicht | ✅/🟡 | Admin-Alt-Text jetzt `required` (T15); Anzeige-Fallbacks (T11) |
| AK-10 | Deko nicht vorgelesen | 🟡 | `aria-hidden` an Emojis/SVGs (T11/T12) |
| AK-11 | Textkontrast ≥ 4,5:1 | ✅ | axe grün; Badges 500→700, Trenner/Links/`mailto` angehoben |
| AK-12 | UI-/Diagrammkontrast ≥ 3:1 | ✅ | axe grün über `/open` und Formulare |
| AK-13 | 200 % Zoom | 🟡 | mobile-first-Layout; Hand ausstehend |
| AK-14 | 320 px kein Querscroll | 🟡 | Scroll-Container `/open` jetzt tastaturbedienbar; Hand ausstehend |
| AK-15 | Genau ein H1 / Ebenen | ✅ | `AccessibilityStructureTest` (11 Routen) |
| AK-16 | Textabstände | 🟡 | keine festen Höhen an Textblöcken; Hand ausstehend |
| AK-17 | Farbe trägt nie allein | ✅/🟡 | Fließtext-Links jetzt unterstrichen (axe `link-in-text-block` grün); Graustufen-Handprüfung ausstehend |
| AK-18 | Label ↔ Feld | ✅ | `_form_field` + Login (T14); Küchen-Filter `aria-label` (axe `select-name` grün) |
| AK-19 | Fehlertext am Feld | 🟡 | `_form_field` `aria-describedby`; Login-Sammelfehler gebunden (T14) |
| AK-20 | Fokus ins erste Fehlerfeld | 🟡 | serverseitiges `autofocus` (`_form_field`, Registrierung T14) |
| AK-21 | Pflicht angesagt | 🟡 | `required`/`aria-required` (T14) |
| AK-22 | `autocomplete`-Zweck | ✅ | `RegistrationType`/Formulare (T14 bestätigt) |
| AK-23 | Redundante Eingabe | ➖ | Wizard erhebt jede Angabe genau einmal (T13 dokumentiert) |
| AK-24 | Wizard-Schritt angesagt | 🟡 | `aria-live` + `community.suggest.step_announce` (T13); Screenreader-Test ausstehend |
| AK-25 | Zielgrößen 24/44/48 | 🟡 | `min-h-[48/44px]` in gehärteten Bereichen |
| AK-26 | Bewegung reduzieren | 🟡 | globaler `prefers-reduced-motion: reduce` (`app.css`) |
| AK-27 | Nichts blinkt > 5 s | 🟡 | `animate-ping` → `motion-safe:` (T11); sonst nichts dergleichen |
| AK-28 | `lang` am Wurzelelement | ✅ | `AccessibilityStructureTest` |
| AK-29 | `lang`-Wechsel Fremdsprache | 🟡 | Bestandshärtung; Hand ausstehend |
| AK-30 | Eindeutige Fenstertitel | 🟡 | `<title>`-Blöcke je Seite; Vollabgleich ausstehend |
| AK-31 | Getrennte Landmarks | ✅ | `AccessibilityStructureTest` (`main#hauptinhalt`) |
| AK-33 | Sprechende Linktexte | 🟡 | toter Footer-Link behoben (T08); Bestandshärtung |
| AK-34 | Mobiler Abmeldeweg | ✅/🟡 | Abmelden-Karte im Profil (T16); über Bottom-Nav erreichbar |
| AK-35 | Mobiler Sprachwechsel | ✅ | Bestand seit BF-72 (Sprachumschalter mobil im Header) |
| AK-36 | Jede Funktion auf Mobil | 🟡 | „Vorschlagen" + B2B in der Fußzeile (T08) |
| AK-37 | Offline-Seite konform | 🟡 | `offline.html`: lang/title/Kontrast/Fokus/Button (T17) |
| AK-38 | Mails konform | 🟡 | Footer-Kontrast angehoben, ohne Bilder verständlich (T17) |
| AK-39 | Bildsortierung ohne Ziehen | ✅/🟡 | Auf/Ab-Knöpfe über bestehenden Endpunkt (T15) |
| AK-40 | Admin-Fokus im Kontrastmodus | 🟡 | 39 Ring→Outline (T15); Kontrastmodus-Test von Hand ausstehend |
| AK-41 | Tom-Select-Ansage | 🟡 | ARIA von Haus aus + `aria-live` für Auswahl (T15); **Entfernen** nicht angesagt → OF-01 |
| AK-42 | Footer-Link zur Erklärung | ✅ | `base.html.twig` (T08), Route registriert |
| AK-43 | Erklärung Pflichtinhalte | ✅ | `accessibility/index.html.twig` (T10) + diese Matrix; keine gesetzliche Beschwerdestelle (Decision #13) |
| AK-44 | Erklärung in vier Sprachen | ✅ | `accessibility_statement.*` in 4 Katalogen (`CatalogueCompletenessTest`) |
| AK-45 | Liste nicht zugänglicher Inhalte | ✅ | siehe Abschnitt „Bekannte Einschränkungen" unten |
| AK-46 | Veralterungshinweis ≥ 12 Monate | ✅ | Controller-Logik (T06), Datum-gegen-heute |
| AK-47 | Rechtslage ohne falsche Pflicht | 🟡 | freiwillige Selbstverpflichtung (T10); **Endtext-Rechtsabnahme = UA-01** |
| AK-48 | Formular + Kontaktadresse | ✅ | `accessibility/index` (T10) |
| AK-49 | Absenden ohne E-Mail | ✅ | `AccessibilityControllerTest` |
| AK-50 | Nichts gespeichert | ✅ | keine Entity; `AccessibilityControllerTest` |
| AK-51 | Bestätigung + Fokus + Ansage | ✅/🟡 | Redirect+Flash getestet; Turbo-Fokus (`_success`) Screenreader-Test ausstehend |
| AK-52 | Rate-Limit mit Wartezeit | ✅ | `LimiterCoverageTest`, `framework.yaml` |
| AK-53 | Honeypot lautlos | ✅ | `AccessibilityControllerTest` |
| AK-54 | Prüflauf schlägt bei Verstoß fehl | ✅ | `AccessibilityStructureTest` (strukturell) + `bin/a11y-audit.mjs` (inhaltlich) — beide grün |
| AK-55 | Prüfmatrix je Kriterium | ✅ | dieses Dokument |
| AK-56 | Meldetext nur ins Postfach | ✅ | `AccessibilityReportMailer` (kein persist/Log), `AccessibilityControllerTest` |
| AK-57 | Kein PII im Log bei Fehler | ✅ | `AccessibilityReportMailer` (nur Klasse+Code) |
| AK-58 | Nur Beschreibung verlangt | ✅ | `AccessibilityReportType` (kein Name/Art) |
| AK-59 | Erklärung öffentlich | ✅ | `AccessibilityControllerTest` |
| AK-60 | CSRF/fremde Absender | ✅ | Formular-CSRF (Symfony), `AccessibilityControllerTest` |

## Bekannte Einschränkungen (Quelle für `app.accessibility.known_issues`, AK-45)

1. **Bildergalerie (GLightbox), OF-01** — Escape schließt (Bibliotheks-Default), aber die
   Fokusrückgabe zum auslösenden Bild ist nicht bestätigt. Behebung: `keyboardNavigation`
   und Fokusrückgabe in `assets/app.ts` prüfen/setzen, oder Widget ersetzen.
2. **Küchen-Auswahlfeld (Tom Select), OF-01** — Vorschläge und die getroffene Auswahl
   werden angesagt; das **Entfernen** eines Eintrags wird noch nicht verbal gemeldet.
3. **`/open`-Verlauf/Kanton-Tabellen** — jetzt per Tastatur scrollbar (`tabindex`/`role`);
   der CC-BY-Link öffnet ohne „neues Fenster"-Hinweis (nur beratend, kein AA-Verstoß).
4. **Manuelle Prüfung** — der automatisierte axe-Vollabgleich ist grün; der Screenreader-
   und Tastatur-Durchlauf von Hand steht noch aus (siehe Prüfmethode).

## Edge Cases (EC-01 – EC-08, Feature-Spec)

| EC | Fall | Status |
|---|---|---|
| EC-01 | JavaScript aus → Erklärung/Formular bedienbar | 🟡 Formular ist reines POST (No-JS-Redirect-Fallback im Controller); Hand ausstehend |
| EC-02 | Kontrastmodus + 400 % gleichzeitig | ⏳ Handprüfung ausstehend |
| EC-03 | Bestandsfoto ohne Alt-Text | 🟡 Anzeige-Fallback `?: restaurant.name` (T11); Admin verlangt künftig Alt-Text (T15) |
| EC-04 | Versand scheitert → Text bleibt | ✅ Mailer gibt `false`, Controller zeigt Fehler + rendert Formular neu |
| EC-05 | Screenreader auf Kennzahlenseite | 🟡 Diagramme `aria-hidden`, Tabellen-Entsprechung vorhanden (T12); Screenreader-Test ausstehend |
| EC-06 | Cookie-Banner mit Tastatur | 🟡 Bestand `role=dialog`; Hand ausstehend |
| EC-07 | Erklärung vor erster Prüfung | ✅ `tested_on` leer → „Prüfung ausstehend" (Controller T06) |
| EC-08 | lb-Katalog-Lücke | ✅ `CatalogueCompletenessTest` verhindert Schlüssel-als-Text |

## Nächste Schritte bis zum Konformitätsnachweis

1. ✅ erledigt: `bin/a11y-audit.mjs` ausgeführt, 31 Befunde behoben, Lauf grün über 12
   Routen. Bei jeder späteren Änderung erneut ausführen (Regressionsschutz).
2. Tastatur- und Screenreader-Durchlauf von Hand (die 🟡-Zeilen).
3. OF-01 entscheiden (GLightbox/Tom-Select bestehen oder ersetzen).
4. UA-01: Erklärungstext juristisch abnehmen lassen.
5. Erst dann `app.accessibility.conformance_level` + `tested_on` setzen und
   `known_issues` aus den dann noch offenen Punkten füllen.
