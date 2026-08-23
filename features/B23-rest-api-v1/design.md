# B23 · REST-API v1 (iOS-Backend) — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Sechs Endpunkte in drei Controllern, zwei stateless Firewalls, zwei Transformer und
zwei Event-Subscriber. Keine Serializer-Konfiguration, kein API Platform: Jede Antwort
wird in einer Methode Feld für Feld zusammengesetzt.

Das ist der bewusste Kern des Entwurfs — die `Restaurant`-Entity hat Getter, die kein
Normalizer zuverlässig auflöst (`acceptsCash()`, `hasAccessibleToilet()`), und ein
explizit aufgebautes Array kann `password` nicht versehentlich mit ausgeben.

## Seiten und Routen

Alle sprachfrei unter `/api/v1`.

| Route | Methode | Zugang | Zweck |
|---|---|---|---|
| `/auth/login` | POST | öffentlich | JWT gegen E-Mail + Passwort |
| `/auth/register` | POST | öffentlich | Konto anlegen, kein Token zurück |
| `/restaurants` | GET | öffentlich | Liste mit Umschlag und Filtern |
| `/restaurants` | POST | `IS_AUTHENTICATED_FULLY` | anlegen |
| `/restaurants/{id}` | GET | öffentlich | Detail |
| `/restaurants/{id}/images` | GET | öffentlich | Bilder |
| `/me` | GET | `IS_AUTHENTICATED_FULLY` | eigenes Profil |
| `/me/submissions` | GET | `IS_AUTHENTICATED_FULLY` | eigene Einreichungen |
| `/api/docs`, `/api/docs.json` | GET | öffentlich | Swagger UI |

⚠ **Sprachfreiheit ist Konfiguration, nicht Zufall:** `config/routes.yaml` lädt
`src/Controller/Api/V1/` in einem eigenen Block mit `prefix: /api/v1` und schließt das
Verzeichnis am `controllers`-Loader aus. Ohne den `exclude`-Eintrag lägen alle
Endpunkte unter `/{_locale}/api/v1`.

## Komponentenstruktur

```
src/Controller/Api/V1/
├── AuthController        login (Rumpf nie erreicht) · register · zwei private Mailwege
├── RestaurantApiController  index · create · show · images
│                            + extractFilters() · applyOptionalData() · validateCoordinate()
└── MeController          me · submissions

src/Api/
├── RestaurantTransformer  list() · detail() · image()
├── UserTransformer        profile()
└── AssetUrlBuilder        absolute URLs, final → in Tests echt genutzt statt gemockt

src/EventSubscriber/
├── ApiRateLimitSubscriber   KernelEvents::REQUEST, Priorität 20
└── ApiExceptionSubscriber   KernelEvents::EXCEPTION, Priorität 10
```

## Sicherheitsarchitektur

`config/packages/security.yaml` — zwei stateless Firewalls **vor** `main`:

| Firewall | Muster | Mechanik |
|---|---|---|
| `api_login` | `^/api/v1/auth/login$` | `json_login` mit `username_path: email`, Lexik-Handler für Erfolg und Fehler |
| `api` | `^/api/v1` | `jwt: ~` |

`access_control`, reihenfolgeabhängig:

```
^/api/v1/auth                        PUBLIC_ACCESS
^/api/v1/restaurants  (GET)          PUBLIC_ACCESS
^/api/v1/me                          IS_AUTHENTICATED_FULLY
^/api/v1/restaurants  (POST)         IS_AUTHENTICATED_FULLY
```

Schlüsselpaar in `config/jwt/*.pem`, gitignoriert, erzeugt über
`lexik:jwt:generate-keypair`. Env: `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`,
`JWT_PASSPHRASE`.

## Datenmodell

B23 legt **keine eigene Tabelle** an. Geschrieben wird in `restaurant` (über `create`)
und `user` (über `register`), gelesen aus beiden plus `restaurant_image`, `cuisine`,
`opening_hour`.

Der wesentliche Datenvertrag steckt in den Transformern:

| Block | Inhalt | Besonderheit |
|---|---|---|
| `accessibility` | sechs Booleans | jeder Wert ist ein Boolean — nie `null` |
| `measurements` | `doorWidthCm`, `tableSpacingCm` | **eigener Block**, weil `null` hier „nicht ausgemessen" heißt und in `accessibility` ein Kompatibilitätsbruch wäre |
| `dietary`, `payment`, `contact`, `location` | wie benannt | |
| `openingHours` | nach Tag 1–7 gruppiert | Transformer injiziert `OpeningHoursService` für `isOpenNow` und `nextOpeningTime` |
| Bild- und Avatar-URLs | absolut | `AssetUrlBuilder`, Scheme+Host des Requests, Override `APP_API_BASE_URL` |

`password`, `verificationToken` und `webauthnHandle` kommen strukturell nicht vor.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| anonym | alle Restaurants, alle Bilder | — | `access_control` |
| angemeldet (JWT) | zusätzlich eigenes Profil, eigene Einreichungen | Restaurants **anlegen** | `access_control` + `#[IsGranted]` |
| angemeldet | **keine** fremden Einreichungen | — | `findBySubmitter($this->getUser())` — keine ID aus der Anfrage |

Kein Voter. Ein IDOR ist strukturell ausgeschlossen, weil die geschützten Endpunkte
keine fremde Kennung entgegennehmen.

⚠ **Was fehlt, ist keine Zugriffsregel, sondern eine Freigaberegel:** Angelegte
Restaurants sind ohne Prüfung öffentlich (AK-21). Das ist kein Autorisierungsfehler —
der Nutzer *darf* anlegen — sondern eine fehlende Moderationsstufe.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten | Wo konfiguriert |
|---|---|---|---|
| `^/api/v1/auth/login` | 5/Minute je IP, `fixed_window` | 429 + `Retry-After` | `limiter.api_login` |
| alles übrige unter `^/api/v1` | 100/Minute je IP, `sliding_window` | dito | `limiter.api_anonymous` |
| `create` | nur das obige | — | ⚠ zu schwach, siehe FB-01 |
| `register` | nur das obige | — | ⚠ Mailversand, siehe FB-02 |

`ApiRateLimitSubscriber` hängt an `KernelEvents::REQUEST` mit Priorität 20 — also vor
der Firewall, das Limit greift auch für nicht authentifizierte Anfragen. Der
`Retry-After`-Wert kommt aus `RateLimit::getRetryAfter()` und wird auf mindestens
1 Sekunde angehoben.

In `when@test` sind alle drei Limiter auf 10.000 gesetzt, sonst würde die Suite ab dem
sechsten Login rot.

CORS: `config/packages/nelmio_cors.yaml`, ausschließlich `^/api/v1/`.

## Fehlerbehandlung

`ApiExceptionSubscriber`, Priorität 10, greift nur bei Pfaden unter `/api/v1`:

| Ausgangslage | Status | Meldung |
|---|---|---|
| `HttpExceptionInterface` | dessen Status | dessen Meldung, Header werden übernommen (`Retry-After`, `WWW-Authenticate`) |
| `AccessDeniedException`, **anonym** | 401 | „Authentifizierung erforderlich." |
| `AccessDeniedException`, angemeldet | 403 | „Zugriff verweigert." |
| `AuthenticationException` | 401 | wie oben |
| alles übrige | 500 | „Interner Serverfehler." — im Debug-Modus mit Detail |

## Externe Dienste

| Dienst | Wofür | Was geht hin |
|---|---|---|
| Brevo | Bestätigungs- und Hinweis-Mails aus `register` | Adresse, Name, Bestätigungs-URL mit Token |
| Sentry (nur `prod`) | Exceptions unterhalb `/api/v1` | sieht sie vor der JSON-Umwandlung (Priorität 128 vs. 10) |

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`. Zusätzlich:

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 8 | `MAX_LIMIT = 50` als Konstante | ungebunden | begrenzt teure Antworten (AK-09) |
| 9 | Unbekanntes `sort` → Rückfall statt Fehler | 422 | nachsichtiges Verhalten für Clients; `meta.sort` macht es sichtbar |
| 10 | `AssetUrlBuilder` ist `final` | Interface | in Tests wird die echte Klasse mit Basis-URL benutzt statt eines Doubles |
| 11 | Koordinatenprüfung im Controller | Constraint auf der Entity | fängt den DBAL-500 ab, bevor er entsteht (AK-15) |
| 12 | Validierung von Hand | Symfony Validator | **Grund nicht erkennbar** — führt zu doppelten Regeln, siehe FB-09 |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02 | Firewall `api_login`, Lexik success/failure-Handler |
| AK-03 | `AuthController::register()`, 201 mit generischer Meldung |
| AK-04 | derselbe Zweig, `sendAccountExistsHint()`, Hash in beiden Zweigen |
| AK-05, AK-06 | `$violations`-Aufbau bzw. `json_decode`-Prüfung |
| AK-07 | `access_control` `GET /api/v1/restaurants` = `PUBLIC_ACCESS` |
| AK-08 | `index()`, `meta`-Block |
| AK-09 | `min(self::MAX_LIMIT, …)` |
| AK-10 | `in_array($sort, self::SORTS, true)` |
| AK-11 | `ApiExceptionSubscriber`, anonym → 401 |
| AK-12, AK-25 | `UserTransformer::profile()` — explizite Feldliste |
| AK-13 | `MeController::submissions()` → `findBySubmitter()` |
| AK-14 | `create()`, `setSubmittedBy()`, `setIsVerified(false)` |
| AK-15 | `validateCoordinate()` |
| AK-16 | `AssetUrlBuilder` |
| AK-17, AK-18 | `ApiRateLimitSubscriber` |
| AK-19 | `ApiExceptionSubscriber` |
| AK-20 | `config/packages/nelmio_api_doc.yaml`, `path_patterns: ^/api/v1` |
| AK-21 ⚠ | `findPaginated()` **ohne** `isVerified`-Vorfilter | Lücke, FB-01 |
| AK-22 ⚠ | Limiter-Wahl im Subscriber (`str_starts_with … /auth/login`) | Lücke, FB-02 |
| AK-23 ⚠ | Text in `sendAccountExistsHint()` | Lücke, verweist auf B01/FB-05 |
| AK-24 ⚠ | `applyOptionalData()`, Block `contact` | Lücke |
| AK-26 | `config/packages/lexik_jwt_authentication.yaml`, `config/jwt/` gitignoriert |
| AK-27 | `ApiExceptionSubscriber`, `$this->security->getUser() === null` |
| AK-28 | derselbe, `$this->debug` |
| AK-29 | `nelmio_cors.yaml` |

## Für `sdd-qa` besonders zu prüfen

1. **AK-21** — ein Restaurant über die API anlegen und danach `/{locale}/restaurants`
   aufrufen. Der Befund entscheidet sich in einem einzigen Durchlauf.
2. **AK-22** — `register` mit einer fremden Adresse in Schleife; zählen, wie viele Mails
   ankommen.
3. **AK-23** — die Hinweis-Mail lesen und den versprochenen Weg suchen.
4. **FB-07** — `CORS_ALLOW_ORIGIN` auf Produktion nachsehen.
