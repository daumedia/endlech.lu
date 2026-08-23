# B20 · Restaurantverwaltung (Admin) — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Klassisches Symfony-CRUD: ein Controller, ein FormType, ein geteiltes Formular-Partial
für Anlegen und Bearbeiten. Die einzige nichttriviale Logik ist die Nachführung von
`verifiedAt` und `verifiedBy` — sie braucht den Zustand **vor** dem Absenden und den
handelnden Nutzer, liegt deshalb im Controller und nicht in der Entity.

## Seiten und Routen

Alle `ROLE_ADMIN`, Präfix `/{_locale}/admin`.

| Route | Pfad | Methode | CSRF |
|---|---|---|---|
| `admin_restaurant_index` | `/restaurants` | GET | — |
| `admin_restaurant_new` | `/restaurants/neu` | GET, POST | Formular |
| `admin_restaurant_edit` | `/restaurants/{id}/bearbeiten` | GET, POST | Formular |
| `admin_restaurant_toggle_verified` | `/restaurants/{id}/verifizieren` | POST | `toggle-verified-{id}` |
| `admin_restaurant_delete` | `/restaurants/{id}/loeschen` | POST | `delete-restaurant-{id}` |

Bild-Routen desselben Controllers → B09.

## Komponentenstruktur

```
admin/restaurant/index.html.twig   Tabelle, je Zeile Verifizieren- und Löschformular
admin/restaurant/new.html.twig  ┐
admin/restaurant/edit.html.twig ┘ beide binden ein:
└── admin/restaurant/_form.html.twig
    └── RestaurantType
        ├── Grunddaten          name · city · emoji · rating
        ├── Küchen              EntityType + tom_select_controller.ts   → B08
        ├── Barrierefreiheit    6 Checkboxen + doorWidthCm · tableSpacingCm
        ├── Zahlung, Ernährung  6 Checkboxen
        ├── Sprachen            spokenLanguages
        ├── Kontakt & Sozial    phone · email · website · 3 URLs
        ├── Standort & Nahverkehr  latitude · longitude · nearbyStopsNote  → B10
        ├── Öffnungszeiten      CollectionType, nach Tag gruppiert        → B07
        ├── Bestellwege         CollectionType (OrderingOptionType)
        └── Verifizierung       isVerified
    (nur im edit): Fotoverwaltung                                         → B09
```

## Datenmodell

Schreibt die Entity `Restaurant` in voller Breite; die Feldreferenz steht in
[`docs/data-model.md`](../../docs/data-model.md#restaurant). Für B20 eigen sind:

| Feld | Typ | Bedeutung |
|---|---|---|
| `is_verified` | TINYINT(1) | Gütesiegel, **kein** Sichtbarkeitsschalter |
| `verified_at` | DATETIME, nullable | wird nur beim Zustandswechsel gesetzt |
| `verified_by_id` | FK → `user`, **SET NULL** | wer geprüft hat |

Kaskaden beim Löschen eines Restaurants:

| Beziehung | Verhalten |
|---|---|
| `restaurant_image` | CASCADE — Zeilen weg, ⚠ **Dateien bleiben** (AK-13) |
| `opening_hour` | CASCADE |
| `ordering_option` | CASCADE |
| `restaurant_cuisine` (JoinTable) | Verknüpfungen weg, `cuisine` bleibt |
| `partner_waitlist_entry.restaurant_id` | SET NULL, stillschweigend (AK-14) |

Migration: `Version20260308100000` (Verifikation), `Version20260820200000` (Maße).

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| Gast, `ROLE_USER` | nichts | `access_control` `^/[a-z]{2}/admin` + `#[IsGranted('ROLE_ADMIN')]` |
| `ROLE_ADMIN` | alles | dieselbe Schranke |

Keine Feingliederung: Jeder Admin darf jeden Datensatz löschen.

## Missbrauchsschutz

| Aspekt | Vorhanden | Fehlt |
|---|---|---|
| CSRF | zwei eigene Token + Formular-CSRF | — |
| ID-Auflösung | `ParamConverter`, `\d+`, 404 | — |
| Löschbestätigung | nur `confirm()` im Browser | serverseitige Rückfrage (FB-03) |
| Rate Limit, Audit-Log, Papierkorb | — | FB-04, FB-05, FB-06 |

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `index()`, `findBy([], ['createdAt' => 'DESC'])` |
| AK-02, AK-03 | `new()`; 422 durch `AbstractController::render()` |
| AK-04, AK-05, AK-06 | `edit()`, Vergleich `$wasVerified` / `$isNowVerified` |
| AK-07 | `toggleVerified()` |
| AK-08 | `delete()` + Doctrine-Kaskaden |
| AK-09 | `isCsrfTokenValid()` in beiden Aktionen |
| AK-10 | `ParamConverter`, `requirements: ['id' => '\d+']` |
| AK-11 | Rollenschranke |
| AK-12 ⚠ | `findBy()` ohne Grenze | Lücke, FB-02 |
| AK-13 ⚠ | **Abwesenheit** eines `PreRemove`-Callbacks; `unlink()` existiert nur in `ImageUploadService::delete()` | Lücke, FB-01 |
| AK-14 ⚠ | FK `ON DELETE SET NULL` ohne Hinweis | Lücke |
| AK-15, AK-16 | Feldbestand; FK `SET NULL` auf `verified_by_id` |
| AK-17 | siehe Routentabelle |
| AK-18 | `findPaginated()` filtert nicht auf `isVerified` — siehe B05 |

## Für `sdd-qa` besonders zu prüfen

1. **AK-13** — ein Restaurant mit Fotos anlegen, löschen und danach
   `public/uploads/restaurants/` zählen. Auf Produktion zusätzlich prüfen, wie viele
   verwaiste Dateien bereits liegen (Dateien ohne passende Zeile in
   `restaurant_image`).
2. **AK-06** — ein verifiziertes Restaurant bearbeiten, ohne den Haken anzufassen, und
   prüfen, ob `verified_at` unverändert bleibt.
3. **FB-03** — Löschen ohne JavaScript auslösen.
