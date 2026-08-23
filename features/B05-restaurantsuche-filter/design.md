# B05 · Restaurantsuche, Filter & Sortierung — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Controller, der Query-Parameter in ein Filterarray übersetzt, und eine
Repository-Methode, die daraus einen QueryBuilder baut. `findPaginated()` ist die
meistgenutzte Methode des Projekts — sie bedient die Webliste **und** die REST-API
(B23) und ist damit die Stelle, an der beide Wege dieselben Regeln teilen.

## Seiten und Routen

| Route | Pfad | Zugang |
|---|---|---|
| `app_restaurant_index` | `/{_locale}/restaurants` | öffentlich |

## Komponentenstruktur

```
restaurant/index.html.twig
├── Filterleiste            14 Filter als GET-Formular
│   ├── 11 Ja/Nein-Filter
│   ├── city (Text)
│   ├── cuisine[]  ← allCuisines aus CuisineRepository::findAllSorted()  → B08
│   └── lang_*     ← Language::cases()
├── Sortierumschalter       rating · name · newest
├── Kartenraster            je Haus: Emoji · Name · Stadt · Bewertung
│   ├── partials/_verified_badge.html.twig
│   ├── partials/_cuisine_badges.html.twig    → B08
│   └── restaurant|is_open_now                → B07
└── Blätternavigation       currentPage / lastPage
```

## Datenmodell

Nur lesend. `RestaurantRepository::findPaginated(string $sort, int $page, int $limit, array $filters): Paginator`

| Filterschlüssel | Bedingung |
|---|---|
| `verified`, `wheelchair`, `toilet`, `dogs`, `lighting`, `changing_table`, `disabled_parking`, `vegan`, `vegetarian`, `halal` | `r.<feld> = true` |
| `city` | `r.city LIKE :city` mit `'%'.$wert.'%'` |
| `cuisine` | `innerJoin r.cuisines` + `IN (:cuisineIds)` |
| `lang` | UND-Verknüpfung über `spokenLanguages` |
| `open` | zwei `leftJoin` auf `openingHours` (heute, gestern) mit TIME-Vergleich inkl. Nachtschicht-Übertrag, `distinct()` |

Sortierungen: `rating DESC` (Vorgabe), `name ASC`, `createdAt DESC`.

`leftJoin` + `addSelect` auf `openingHours` und `cuisines` verhindern N+1.

## Zugriffsregeln

Keine. Die Liste zeigt **alle** Restaurants, geprüft wie ungeprüft (AK-15).

## Missbrauchsschutz

| Aspekt | Vorhanden |
|---|---|
| Parameterbindung | alle Werte gebunden; `cuisine` über `intval`, `lang` gegen das Enum, `sort` gegen eine Liste |
| Seitengröße | fest auf 6, vom Client nicht steuerbar (anders als in der API) |
| Rate Limit, Cache | keins (FB-07) |

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02 | `findPaginated()`, `orderBy` je `sort` |
| AK-03 | `in_array($sort, ['rating','name','newest'], true)` |
| AK-04, AK-05 | `max(1, getInt('page', 1))`, `Paginator` |
| AK-06 | elf `andWhere`-Zweige |
| AK-07 | `LIKE :city` |
| AK-08 | `innerJoin` + `IN` |
| AK-09 | UND-Verknüpfung im `lang`-Zweig |
| AK-10, AK-12 | `open`-Zweig mit `oh_today`/`oh_yesterday` und `distinct()` |
| AK-11 | alle Zweige sind `andWhere` |
| AK-13 | leeres Ergebnis, Hinweis im Template |
| AK-14 | `CuisineRepository::findAllSorted()`, `Language::cases()` |
| AK-15 ⚠ | **Abwesenheit** eines `isVerified`-Vorfilters | Lücke, FB-01 |
| AK-16 ⚠ | `Paginator` liefert leer, kein 404 | Lücke, FB-04 |
| AK-17 ⚠ | `'%'.$wert.'%'` ohne Maskierung | Lücke, FB-03 |
| AK-18 | keine `access_control`-Regel |
| AK-19 | Parameterbindung und Vorprüfungen |
| AK-20 | Feldbestand |

## Für `sdd-qa` besonders zu prüfen

1. **AK-10** — die Nachtschicht-Logik ist die komplexeste Abfrage des Projekts. Ein
   Integrationstest existiert (`tests/Integration/Repository/RestaurantRepositoryTest.php`)
   und deckt alle Filter ab; die Zeitgrenzen sind trotzdem einen manuellen Blick wert.
2. **AK-09** — prüfen, ob der Sprachfilter wirklich UND-verknüpft ist; der Unterschied
   zum Küchenfilter ist für Besucher unsichtbar.
3. **AK-17** — `?city=%` aufrufen.
