# B12 · Startseite — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Der kleinste Controller des Projekts: eine Methode, zwei Repository-Aufrufe, ein
Template. Die Arbeit steckt in der Vorlage.

## Seiten und Routen

| Route | Pfad | Zugang |
|---|---|---|
| `app_root` | `/` | öffentlich — `RedirectController` → `app_home`, `_locale: lb`, 302 |
| `app_home` | `/{_locale}/` | öffentlich |

## Komponentenstruktur

```
home/index.html.twig
├── Hero                        Leitzahl: totalCount
│   └── partials/_hero_badges.html.twig
├── „So funktioniert's"         drei Schritte
├── Top-6-Raster                je Haus wie in B05
│   ├── partials/_verified_badge.html.twig
│   └── partials/_cuisine_badges.html.twig
├── „Warum Endlech.lu?"
└── Handlungsaufruf             → app_register bzw. community_vorschlagen
```

## Datenmodell

Nur lesend:

| Aufruf | Abfrage |
|---|---|
| `RestaurantRepository::findTopRated(6)` | `leftJoin`+`addSelect` auf `openingHours` und `cuisines`, `ORDER BY rating DESC, name ASC`, `LIMIT 6` |
| `RestaurantRepository::count()` | alle Restaurants |

## Zugriffsregeln

Keine.

## Missbrauchsschutz

Keiner nötig; kein Rate Limit, kein Cache (FB-02).

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `app_root` in `config/routes.yaml` |
| AK-02 | `HomeController::index()`, `home/index.html.twig` |
| AK-03 | `$restaurantRepository->count()` |
| AK-04 | `orderBy('r.rating','DESC')->addOrderBy('r.name','ASC')` |
| AK-05 | `leftJoin` + `addSelect` in `findTopRated()` |
| AK-06 | keine `access_control`-Regel |
| AK-07 ⚠ | `findTopRated()` ohne `isVerified`-Filter | Lücke, FB-01 |
| AK-08 | Template-Inhalt |
| AK-09 | **Abwesenheit** von Cache-Headern | Lücke, FB-02 |

## Für `sdd-qa` besonders zu prüfen

1. **AK-07** — ein ungeprüftes Haus mit hoher Bewertung anlegen und die Startseite
   aufrufen.
2. **AK-01** — prüfen, ob `/` tatsächlich 302 liefert und nicht 301 (ein 301 wäre im
   Browser-Cache dauerhaft festgeschrieben).
