# B10 · Haltestellen in der Nähe — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Service, ein DTO, ein Partial. `PublicTransportService::findNearbyStops()` wird aus
`RestaurantController::show()` gerufen, wenn das Restaurant Koordinaten hat. Drei
Schutzschichten liegen davor: leerer Schlüssel → sofort leer, Cache → 24 Stunden,
`catch (\Throwable)` → leer plus Protokolleintrag.

Was fehlt, ist die vierte: eine Zeitschranke.

## Seiten und Routen

Keine eigene Route. Aufruf in `RestaurantController::show()` (`app_restaurant_show`),
synchron im Request des Besuchers.

## Komponentenstruktur

```
RestaurantController::show()
└── if ($restaurant->hasCoordinates())
    └── PublicTransportService::findNearbyStops(lat, lng)
        ├── if ($apiKey === '') → []
        ├── cache->get('nearby_stops_'.md5(...), 86400)
        │   └── HttpClient GET cdt.hafas.de/opendata/apiserver/location.nearbystops
        │       └── parseResponse() → extractLines() · determineType()
        └── catch (\Throwable) → logger->error() → []

templates/partials/_nearby_stops.html.twig
├── Haltestellenkarte je NearbyStop
└── zusätzlich: restaurant.nearbyStopsNote
```

## Datenmodell

Kein eigener Speicher. Gelesen werden drei Felder von `Restaurant`:

| Feld | Typ | Bedeutung |
|---|---|---|
| `latitude` | DECIMAL(10,8), nullable | |
| `longitude` | DECIMAL(11,8), nullable | |
| `nearby_stops_note` | TEXT, nullable | Freitext des Betreibers, max. 1000 Zeichen im Formular |

Helper: `Restaurant::hasCoordinates()`. Migration: `Version20260322000000`.

**DTO** `App\DTO\NearbyStop` (`final readonly`): `name`, `distance` (Meter),
`lines` (`string[]`), `type` (`bus`\|`tram`\|`mixed`).

**Cache:** Standard-Pool `cache.app`, Schlüssel
`nearby_stops_<md5(lat|lng auf 4 Stellen gerundet)>`, TTL 86400.

## Zugriffsregeln

Keine — die Angaben sind öffentlich, wie die Detailseite selbst.

## Missbrauchsschutz

| Richtung | Schutz |
|---|---|
| eingehend | keiner nötig; kein eigener Endpunkt |
| ausgehend | Cache (24 h) begrenzt die Abrufzahl bei **Erfolg**; bei Ausfall greift er nicht (FB-02) |
| Zeitschranke | **keine** (FB-01) |

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| HAFAS / Verkéiersverbond (`cdt.hafas.de`) | Haltestellen im Umkreis | Restaurantkoordinaten, Radius 500 m, `maxNo=20`, API-Schlüssel als Query-Parameter | nichts nötig — es gehen keine Besucherdaten hinaus |

Konfiguration: `app.mobiliteit_api_key` (Env `MOBILITEIT_API_KEY`, leer = aus),
`app.mobiliteit_radius` = 500, `app.mobiliteit_max_stops` = 5 — alle in
`config/services.yaml`.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `findNearbyStops()`, `array_slice(…, 0, $this->maxStops)` |
| AK-02 | `partials/_nearby_stops.html.twig` |
| AK-03 | `if ($this->apiKey === '') return [];` |
| AK-04 | `catch (\Throwable)` + `logger->error()` |
| AK-05 | `if ($restaurant->hasCoordinates())` in `RestaurantController::show()` |
| AK-06 | `$item->expiresAfter(86400)` |
| AK-07 | `md5(round((float) $lat, 4) . '_' . round((float) $lng, 4))` |
| AK-08 | `$seen[$name]` in `parseResponse()` |
| AK-09 | `maxNo: 20`, `usort()` nach `distance`, `array_slice(…, 5)` |
| AK-10 | `restaurant.nearbyStopsNote` im Partial |
| AK-11 | `determineType()`, Bitmaske |
| AK-12 ⚠ | **Abwesenheit** einer Zeitschranke | Lücke, FB-01 |
| AK-13 ⚠ | `'accessId' => $this->apiKey` im `query`-Array | Eigenschaft der Schnittstelle |
| AK-14 | Aufbau des `query`-Arrays |
| AK-15 | `logger->error('HAFAS API error: {message}', …)` |
| AK-16 | `config/services.yaml`, `%env(string:default::MOBILITEIT_API_KEY)%` |

## Für `sdd-qa` besonders zu prüfen

1. **AK-12 / FB-01** — mit einem künstlich verzögernden Endpunkt messen, wie lange die
   Detailseite lädt. Der Test liegt bereits vor
   (`tests/Unit/Service/PublicTransportServiceTest.php` nutzt `MockHttpClient`), prüft
   aber nur Erfolg und Ausfall, nicht die Dauer.
2. **FB-02** — Ausfall simulieren und zählen, wie oft die Schnittstelle bei
   wiederholten Seitenaufrufen gerufen wird.
3. **OF-02** — nachsehen, ob auf Produktion überhaupt ein Schlüssel gesetzt ist.
