# B08 · Küchen-Typen — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Eine kleine Entität mit zwei Feldern, eine Admin-Schnittstelle mit zwei Endpunkten und
ein Stimulus-Controller, der Tom Select an das `EntityType`-Feld bindet. Der
interessante Teil ist `findOrCreateByName()` — sie wird von **drei** Stellen gerufen,
von denen nur eine eine Rollenschranke trägt.

## Seiten und Routen

| Route | Pfad | Methode | Zugang |
|---|---|---|---|
| `api_cuisine_search` | `/{_locale}/api/cuisines/search` | GET | `ROLE_ADMIN` |
| `api_cuisine_create` | `/{_locale}/api/cuisines` | POST | `ROLE_ADMIN` |

⚠ Beide liegen **unter** dem Sprachpräfix — `CuisineApiController` ist der ältere
Schnittstellenteil und wurde beim Bau von `/api/v1` nicht mit ausgelagert.

## Komponentenstruktur

```
admin/restaurant/_form.html.twig
└── EntityType 'cuisines'  multiple, by_reference:false
    └── data-controller="tom-select"
        └── tom_select_controller.ts
            ├── remove_button-Plugin
            ├── load  → GET  api_cuisine_search
            └── create → POST api_cuisine_create

partials/_cuisine_badges.html.twig    Anzeige auf Detail- und Listenseite
restaurant/index.html.twig            Filterauswahl ?cuisine[]=
```

**Drei Aufrufstellen von `findOrCreateByName()`:**

| Stelle | Zugang |
|---|---|
| `CuisineApiController::create()` | `ROLE_ADMIN` |
| `AdminSuggestionController::approve()` | `ROLE_ADMIN` |
| `RestaurantApiController::applyOptionalData()` | ⚠ **jeder angemeldete Nutzer** |

## Datenmodell

### Tabelle `cuisine`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT | ja | |
| `name` | VARCHAR(80) **UNIQUE** | ja | Anzeigename, einsprachig |
| `slug` | VARCHAR(100) **UNIQUE** | ja | länger als `name`, damit die Umwandlung Platz hat |

### JoinTable `restaurant_cuisine`

ManyToMany zwischen `restaurant` und `cuisine`, `cascade: persist`. Beim Löschen eines
Restaurants verschwinden nur die Verknüpfungen.

Repository: `findAllSorted()`, `search(string $query, int $limit)`,
`findOrCreateByName(string $name)`.
Helper: `Restaurant::getCuisineNames()` — kommagetrennt.
Migration: `Version20260323000000` — legt beide Tabellen an, migriert die Daten aus der
alten `cuisine`-VARCHAR-Spalte und entfernt sie.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| jeder | Küchen als Abzeichen und Filterauswahl | — | öffentliche Seiten |
| `ROLE_ADMIN` | die Suchschnittstelle | anlegen über `api_cuisine_create` | `#[IsGranted('ROLE_ADMIN')]` an der Klasse |
| **jeder angemeldete Nutzer** | — | **anlegen über `POST /api/v1/restaurants`** | ⚠ keine Schranke, siehe AK-10 |
| niemand | — | ändern, löschen | es gibt keinen Endpunkt (FB-01) |

## Missbrauchsschutz

| Aspekt | Vorhanden | Fehlt |
|---|---|---|
| Rollenschranke | auf `CuisineApiController` | auf dem API-Weg (FB-02) |
| Dublettenschutz | `findOrCreateByName()` + `UNIQUE` | — |
| Längenprüfung | — | FB-03 |
| Rate Limit | `api_anonymous` (100/min) auf dem `/api/v1`-Weg | auf dem Admin-Weg (FB-05) |

## Externe Dienste

Keine. Tom Select liegt als npm-Abhängigkeit lokal.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `CuisineApiController::search()`, `CuisineRepository::search()` |
| AK-02 | `$query !== '' ? search() : findAllSorted()` |
| AK-03 | `create()` → `findOrCreateByName()`, 201 |
| AK-04 | `if ($name === '')` → 400 |
| AK-05 | `findOrCreateByName()` |
| AK-06 | `#[IsGranted('ROLE_ADMIN')]` an der Klasse |
| AK-07 | `partials/_cuisine_badges.html.twig` |
| AK-08 | `RestaurantRepository::findPaginated()`, Filter `cuisine` |
| AK-09 | ManyToMany ohne Kaskade auf `cuisine` |
| AK-10 ⚠ | dritte Aufrufstelle ohne Rollenschranke | Lücke, FB-02 |
| AK-11 ⚠ | **Abwesenheit** einer Längenprüfung vor `VARCHAR(80)` | Lücke, FB-03 |
| AK-12 ⚠ | **Abwesenheit** von Änderungs- und Löschendpunkten | Lücke, FB-01 |
| AK-13 | Feldbestand |
| AK-14 | Lage im `controllers`-Loader (mit `_locale`-Präfix) |
| AK-15 | JSON-Endpunkt ohne CSRF-Prüfung |

## Für `sdd-qa` besonders zu prüfen

1. **AK-10** — als gewöhnlicher Nutzer über `POST /api/v1/restaurants` einen
   Küchentyp mit erfundenem Namen anlegen und danach die Filterauswahl auf
   `/{locale}/restaurants` ansehen.
2. **AK-11** — einen 200 Zeichen langen Namen schicken und den Statuscode prüfen.
