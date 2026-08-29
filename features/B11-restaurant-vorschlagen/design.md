# B11 · Restaurant vorschlagen (Wizard) — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Controller mit zwei Aktionen, ein umfangreicher FormType, eine Entity mit
dreiwertigen Feldern und ein Stimulus-Controller für die Schrittnavigation. Der
Assistent ist **kein** mehrstufiger Server-Ablauf: Alle fünf Schritte stehen im selben
Formular, JavaScript blendet um. Ohne JavaScript sind alle Felder sichtbar und das
Formular funktioniert als eine lange Seite.

## Seiten und Routen

| Route | Pfad | Methode | Zugang |
|---|---|---|---|
| `community_vorschlagen` | `/{_locale}/community/suggest` | GET, POST | `ROLE_USER` **+ bestätigt** |
| `community_danke` | `/{_locale}/community/thanks` | GET | öffentlich |

Die Bestätigungsprüfung ist Controller-Code, keine Firewall-Regel:
`if (!$user->isVerified())` in `CommunityController.php:29`.

## Komponentenstruktur

```
community/vorschlagen.html.twig
├── Schritt-Indikatorleiste
├── RestaurantSuggestionType — fünf Abschnitte
│   ├── 1 Grunddaten          name · city · cuisine (Freitext) · emoji
│   ├── 2 Barrierefreiheit    6 × TriState
│   ├── 3 Ernährung & Zahlung 6 × TriState
│   ├── 4 Kontakt & Sprachen  phone · email · website · Sozialkonten · spokenLanguages
│   └── 5 Notizen             notes
├── partials/_tristate_field.html.twig   Segmented Control, echte Radios (sr-only)
└── suggestion_wizard_controller.ts      Prev/Next/GoTo + clientseitige Pflichtprüfung
```

Fehlererkennung im Template: Es prüft `form[field].vars.errors` je Schritt und springt
zum ersten fehlerhaften — das setzt `'error_bubbling' => false` voraus (EC-01).

## Datenmodell

### Tabelle `restaurant_suggestion`

| Bereich | Felder |
|---|---|
| Grunddaten | `name` VARCHAR(150), `city` VARCHAR(100), `cuisine` VARCHAR(80), `emoji` VARCHAR(10) Vorgabe `🍽️` |
| Barrierefreiheit | 6 × `?TriState` — `is_wheelchair_accessible`, `has_accessible_toilet`, `allows_assistance_dogs`, `has_bright_lighting`, `has_changing_table`, `has_disabled_parking` |
| Zahlung | 3 × `?TriState` — `accepts_cash`, `accepts_card`, `accepts_payconiq` |
| Ernährung | 3 × `?TriState` — `is_vegan`, `is_vegetarian`, `is_halal` |
| Sprachen | `spoken_languages` JSON, Vorgabe `[]` |
| Kontakt | `phone` VARCHAR(30), `email` VARCHAR(180), `website` VARCHAR(500) |
| Sozialkonten | `instagram_url`, `facebook_url`, `tiktok_url` VARCHAR(500) |
| Meta | `notes` TEXT, `status` VARCHAR(20) Vorgabe `pending`, `admin_note` TEXT, `created_at` |
| Herkunft | `suggested_by` FK → `user`, **SET NULL** |

Die zwölf `?TriState`-Spalten sind `VARCHAR(10) NULL` mit
`enumType: TriState::class` — **kein** natives `ENUM`, wegen MariaDB 10.5 auf
Produktion.

Status-Konstanten: `STATUS_PENDING`, `STATUS_APPROVED`, `STATUS_REJECTED`.

Migrationen: `Version20260320000000` (Basis), `Version20260324000000` (weitere Felder),
`Version20260809000000` (`TINYINT(1)` → `VARCHAR(10)`, Datenmigration
`1 → 'yes'`, `0 → 'unknown'`).

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | nichts | nichts | `#[IsGranted('ROLE_USER')]` |
| angemeldet, unbestätigt | nichts | nichts | `if (!$user->isVerified())` im Controller |
| angemeldet, bestätigt | das leere Formular | einen Vorschlag anlegen | dieselbe Methode |
| Nutzer | **nicht** die eigenen Vorschläge | — | es gibt keine Ansicht dafür (FB-02) |
| `ROLE_ADMIN` | alle | genehmigen, ablehnen | B21 |

Der eingereichte Vorschlag ist für seinen Urheber danach unsichtbar.

## Missbrauchsschutz

| Aspekt | Vorhanden | Fehlt |
|---|---|---|
| Zugang | Konto + bestätigte Adresse | — |
| CSRF | Symfony-Formular, stateless `submit` | — |
| Pflichtprüfung | `NotNull` je TriState, `validation` serverseitig | — |
| Rate Limit | — | FB-01 |
| Dubletten | — | FB-04 |

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md` — sieben Entscheidungen, davon vier zum dreiwertigen
Modell.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `#[IsGranted('ROLE_USER')]` |
| AK-02 | `if (!$user->isVerified())` |
| AK-03 | `community/vorschlagen.html.twig`, `suggestion_wizard_controller.ts` |
| AK-04 | `NotNull(message: 'suggestion.answer_required')` + `'error_bubbling' => false` |
| AK-05 | Schritterkennung über `form[field].vars.errors` |
| AK-06 | `placeholder: false` + Entity-Wert `null` |
| AK-07 | `setSuggestedBy()`, `persist`, Redirect auf `community_danke` |
| AK-08 | `partials/_tristate_field.html.twig` |
| AK-09 | `suggestion_wizard_controller.ts` |
| AK-10 | Profil zeigt `Restaurant` über `findBySubmitter()`, nicht Vorschläge |
| AK-11 ⚠ | **Abwesenheit** eines Limiters | Lücke, FB-01 |
| AK-12 ⚠ | `?->isYes() ?? false` in `AdminSuggestionController::approve()` | bewusste Entscheidung mit Informationsverlust |
| AK-13 | Feldbestand der Tabelle |
| AK-14 | FK `ON DELETE SET NULL` |
| AK-15 | Symfony-Formular |

## Für `sdd-qa` besonders zu prüfen

1. **AK-04 / EC-01** — eine der zwölf Fragen leer lassen und prüfen, ob die Meldung am
   Feld erscheint (nicht am Formular) und ob der Assistent zum richtigen Schritt
   springt. Das ist die fehleranfälligste Stelle des Features.
2. **AK-06** — sicherstellen, dass wirklich nichts vorausgewählt ist; sonst wäre die
   Pflichtfrage wirkungslos.
3. **AK-12** — einen Vorschlag mit zwölfmal „Weiß nicht" genehmigen und das entstandene
   Restaurant ansehen.
