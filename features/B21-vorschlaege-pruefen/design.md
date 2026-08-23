# B21 · Vorschläge prüfen (Admin) — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Controller mit vier Aktionen. Die tragende Methode ist `approve()`: Sie baut aus
einem `RestaurantSuggestion` ein `Restaurant`, Feld für Feld, und wandelt dabei die
dreiwertigen Antworten in Booleans um. Beides — der explizite Feldtransfer und die
Umwandlung — ist der Kern des Features und zugleich seine Schwachstelle: Der Transfer
ist unvollständig (AK-13) und die Umwandlung verliert Information (AK-11).

## Seiten und Routen

Alle `ROLE_ADMIN`, Präfix `/{_locale}/admin/vorschlaege`.

| Route | Pfad | Methode | CSRF |
|---|---|---|---|
| `admin_suggestion_index` | `` | GET | — |
| `admin_suggestion_show` | `/{id}` | GET | — |
| `admin_suggestion_approve` | `/{id}/genehmigen` | POST | `approve-suggestion-{id}` |
| `admin_suggestion_reject` | `/{id}/ablehnen` | POST | `reject-suggestion-{id}` |

## Komponentenstruktur

```
admin/suggestion/index.html.twig    drei Gruppen: pending · approved · rejected
admin/suggestion/show.html.twig     alle Felder
└── partials/_tristate_value.html.twig   Ja grün · Nein rot · Weiß nicht grau
    + Genehmigen-Formular (CSRF)
    + Ablehnen-Formular mit Textfeld admin_note (CSRF)
```

## Datenmodell

Liest `restaurant_suggestion` (B11), schreibt dort `status` und `admin_note`, und legt
in `restaurant` (B20) einen neuen Datensatz an — dazu ggf. Zeilen in `cuisine` und
`restaurant_cuisine` (B08).

**Feldtransfer in `approve()`:**

| Übernommen | Nicht übernommen |
|---|---|
| `name`, `city`, `emoji` | `notes` (Freitext des Einreichers) |
| Küchen (Freitext → Entitäten) | Öffnungszeiten (nicht erhoben) |
| 6 Barrierefreiheit, 3 Zahlung, 3 Ernährung — je `?->isYes() ?? false` | Koordinaten (nicht erhoben) |
| `spokenLanguages` | Maße `doorWidthCm`, `tableSpacingCm` (nicht erhoben) |
| `phone`, `email`, `website`, 3 Sozialkonten | `rating` |
| `submittedBy` ← `suggestedBy` | `isVerified` (bleibt `false`) |

`RestaurantSuggestionRepository::findByStatus()` und `countPending()` (letzteres für
das Dashboard, B19).

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| Gast, `ROLE_USER` | nichts | `access_control` + `#[IsGranted('ROLE_ADMIN')]` |
| `ROLE_ADMIN` | alle Vorschläge sehen, genehmigen, ablehnen | dieselbe Schranke |
| Einreicher | **nicht** seinen eigenen Vorschlag | es gibt keine Ansicht (B11/FB-02) |

## Missbrauchsschutz

| Aspekt | Vorhanden | Fehlt |
|---|---|---|
| CSRF | zwei eigene Token | — |
| ID-Auflösung | `ParamConverter`, `\d+` | — |
| **Zustandsprüfung** | — | `STATUS_PENDING`-Prüfung (FB-01) |
| Eingabeprüfung `admin_note` | — | Längenbegrenzung (AK-16) |

## Externe Dienste

Keine — insbesondere **kein** Mailversand (FB-02).

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `index()`, `findByStatus()` × 3 |
| AK-02 | `show()`, `partials/_tristate_value.html.twig` |
| AK-03 | `approve()`, Feldtransfer |
| AK-04 | `explode(',')` + `CuisineRepository::findOrCreateByName()` |
| AK-05 | `setSubmittedBy($suggestion->getSuggestedBy())` |
| AK-06 | `isVerified` wird nicht gesetzt, Vorgabe `false` |
| AK-07 | `reject()`, `setAdminNote(… ?: null)` |
| AK-08 | `isCsrfTokenValid()` in beiden Aktionen |
| AK-09 | Rollenschranke |
| AK-10 ⚠ | **Abwesenheit** einer Statusprüfung | Lücke, FB-01 |
| AK-11 ⚠ | `?->isYes() ?? false`, zwölfmal | bewusste Entscheidung mit Informationsverlust |
| AK-12 ⚠ | `adminNote` ohne Empfänger | Lücke, FB-02 |
| AK-13 ⚠ | Feldtransfer ohne Öffnungszeiten, Koordinaten, Maße | Lücke |
| AK-14 | übernommene Kontaktfelder |
| AK-15 | siehe Routentabelle |
| AK-16 | `$request->request->getString('admin_note')` ohne Prüfung |

## Für `sdd-qa` besonders zu prüfen

1. **AK-10** — denselben Vorschlag zweimal genehmigen (zweiter Tab genügt) und die Zahl
   der Restaurants zählen.
2. **AK-11** — einen Vorschlag mit „Weiß nicht" genehmigen und prüfen, ob dem Admin
   irgendwo angezeigt wird, welche Felder nachzutragen sind.
3. **AK-04** — einen Küchentyp mit Tippfehler genehmigen und die Tabelle `cuisine`
   danach ansehen.
