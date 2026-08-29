# B06 · Restaurant-Detailseite — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Eine Controller-Methode, ein umfangreiches Template und sechs Partials. Der Controller
tut wenig: Er löst das Restaurant über den `ParamConverter` auf, bestimmt den heutigen
Wochentag in luxemburgischer Zeit und ruft — falls Koordinaten vorliegen — die
Haltestellen ab. Alles Weitere ist Darstellung.

## Seiten und Routen

| Route | Pfad | Zugang |
|---|---|---|
| `app_restaurant_show` | `/{_locale}/restaurants/{id}` | öffentlich, `id` = `\d+` |

## Komponentenstruktur

```
restaurant/show.html.twig
├── Kopf                    Emoji · Name · Stadt · Bewertung
│   ├── partials/_verified_badge.html.twig   (size 'lg')
│   └── partials/_cuisine_badges.html.twig                  → B08
├── Titelbild + Galerie     getCoverImage() / getGalleryImages(), GLightbox → B09
├── Barrierefreiheit        8 Merkmale + accessibilityNotes
│   └── Maße                doorWidthCm · tableSpacingCm gegen 90 cm
├── partials/_opening_hours.html.twig                        → B07
│   └── restaurant|is_open_now · next_opening_time(restaurant)
├── Kontakt                 nur wenn hasContactInfo()
├── Bestellwege             OrderingPlatform::logoPath() bzw. emoji()
└── partials/_nearby_stops.html.twig                         → B10
```

## Datenmodell

Nur lesend, die vollständige `Restaurant`-Entity samt Beziehungen. Für B06 eigen:

| Feld | Typ | Bedeutung |
|---|---|---|
| `door_width_cm` | INT, nullable | `null` = nicht ausgemessen |
| `table_spacing_cm` | INT, nullable | dito |
| `accessibility_notes` | JSON | Freitextnotizen |

Konstanten `Restaurant::MIN_DOOR_WIDTH_CM` / `MIN_TABLE_SPACING_CM` = 90 (DIN 18040).
Helper `hasWideDoors()`, `hasWheelchairTableSpacing()` — beide `?bool`.
Helper `hasContactInfo()`, `hasCoordinates()`.

Migration: `Version20260820200000` (Maße).

## Zugriffsregeln

Keine. Jede Detailseite ist öffentlich, unabhängig vom Verifikationsstatus (AK-14).

## Missbrauchsschutz

| Aspekt | Vorhanden | Fehlt |
|---|---|---|
| ID-Auflösung | `ParamConverter`, `\d+`, 404 | — |
| Zeitschranke Drittdienst | — | FB-01 |
| Schutz der Kontaktdaten | — | FB-02 |

## Externe Dienste

HAFAS über `PublicTransportService` → siehe B10.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `show()`, `restaurant/show.html.twig`, `_verified_badge`, `_cuisine_badges` |
| AK-02 | Merkmalsblock + `accessibilityNotes` |
| AK-03 | `hasWideDoors()`, `hasWheelchairTableSpacing()` gegen die Konstanten |
| AK-04 | dieselben Helper liefern `?bool`; `null` wird im Template eigens behandelt |
| AK-05 | `partials/_opening_hours.html.twig`, `todayDayOfWeek` |
| AK-06 | `OpeningHoursExtension`: `is_open_now`, `next_opening_time()` |
| AK-07 | `getCoverImage()`, `getGalleryImages()`, GLightbox |
| AK-08 | `hasContactInfo()`, Kontaktblock |
| AK-09 | `OrderingPlatform::logoPath()` / `emoji()`, `public/images/platforms/` |
| AK-10 | `if ($restaurant->hasCoordinates())` → `findNearbyStops()` |
| AK-11 | `ParamConverter`, `requirements: ['id' => '\d+']` |
| AK-12 | `new \DateTimeImmutable('now', new \DateTimeZone('Europe/Luxembourg'))` |
| AK-13 ⚠ | synchroner Aufruf ohne Zeitschranke | Lücke, FB-01 |
| AK-14 ⚠ | keine Prüfung auf `isVerified` | Lücke |
| AK-15, AK-16, AK-17 | Template-Inhalt; keine Zugangsregel |

## Für `sdd-qa` besonders zu prüfen

1. **AK-04** — ein Haus ohne erfasste Maße aufrufen und den angezeigten Text prüfen.
   Die Unterscheidung „nicht ausgemessen" vs. „erfüllt nicht" ist der fachliche Kern des
   Features; vier der elf Fixtures decken den Fall ab.
2. **AK-13** — Ladezeit bei gestörter HAFAS-Schnittstelle messen.
3. **AK-06** — ein Haus zu einer Zeit aufrufen, in der es geschlossen ist, und die
   angezeigte nächste Öffnung gegen den Wochenplan prüfen (siehe B07/AK-09).
