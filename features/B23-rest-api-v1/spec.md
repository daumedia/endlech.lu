# B23 · REST-API v1 (iOS-Backend) — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Eine versionierte, sprachfreie JSON-Schnittstelle unter `/api/v1` als Backend einer
nativen iOS-App: anmelden, registrieren, Restaurants suchen und ansehen, eigene
Einreichungen abrufen, neue Restaurants anlegen. Dokumentiert unter `/api/docs`.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 | rekonstruiert | Registrierung teilt sich Entity, Token und Mailvorlage |
| B02 | rekonstruiert | dieselben Konten, andere Firewall |
| B05 | bestand | `index` reicht auf `findPaginated` durch |

## User Stories

- **US-01** · Als App-Nutzer möchte ich mich anmelden und ein Token bekommen.
- **US-02** · Als App-Nutzer möchte ich mich registrieren.
- **US-03** · Als App-Nutzer möchte ich Restaurants mit denselben Filtern durchsuchen
  wie im Web.
- **US-04** · Als App-Nutzer möchte ich mein Profil und meine Einreichungen sehen.
- **US-05** · Als App-Nutzer möchte ich ein neues Restaurant melden.

## Nicht im Scope

- Passkeys → nicht vorhanden, siehe FB-06
- Profil ändern über die API — `/me` ist nur lesend
- Restaurants ändern oder löschen — nur `POST` zum Anlegen
- Wartelisten, Open-Daten, Verwaltung — eigene Wege

## Akzeptanzkriterien

- **AK-01** · Angenommen, gültige Zugangsdaten werden als
  `{"email": …, "password": …}` an `POST /api/v1/auth/login` geschickt, wenn die
  Anfrage durchläuft, dann enthält die Antwort ein JWT.
- **AK-02** · Angenommen, die Zugangsdaten sind falsch, wenn abgeschickt wird, dann
  antwortet der Server mit 401 im Format `{"error": {"code", "message"}}`.
- **AK-03** · Angenommen, `POST /api/v1/auth/register` bekommt einen gültigen Body,
  wenn die Anfrage durchläuft, dann antwortet der Server mit **201** und einer
  generischen Meldung — **kein Token**.
- **AK-04** · Angenommen, die E-Mail-Adresse ist **bereits registriert**, wenn
  registriert wird, dann ist die Antwort **wortgleich** identisch zum Neuanlage-Fall;
  stattdessen geht ein Hinweis an die vorhandene Adresse.
- **AK-05** · Angenommen, ein Feld verletzt die Regeln (Name 2–100, gültige E-Mail,
  Passwort ≥ 8), wenn registriert wird, dann antwortet der Server mit 422 und einem
  `violations`-Objekt je Feld.
- **AK-06** · Angenommen, der Body ist kein gültiges JSON, wenn er ankommt, dann
  antwortet der Server mit 400.
- **AK-07** · Angenommen, `GET /api/v1/restaurants` wird **ohne** Token aufgerufen,
  wenn die Anfrage durchläuft, dann liefert sie Daten — der Endpunkt ist öffentlich.
- **AK-08** · Angenommen, `GET /api/v1/restaurants` wird aufgerufen, wenn die Antwort
  betrachtet wird, dann trägt sie den Umschlag
  `{data: [...], meta: {page, limit, total, totalPages, sort}}`.
- **AK-09** · Angenommen, `limit` wird auf 500 gesetzt, wenn die Anfrage durchläuft,
  dann liefert die Antwort höchstens **50** Einträge und `meta.limit` steht auf 50.
- **AK-10** · Angenommen, `sort` trägt einen unbekannten Wert, wenn die Anfrage
  durchläuft, dann wird auf `rating` zurückgefallen und `meta.sort` sagt das.
- **AK-11** · Angenommen, `GET /api/v1/me` wird **ohne** Token aufgerufen, wenn die
  Anfrage durchläuft, dann antwortet der Server mit **401** (nicht 403).
- **AK-12** · Angenommen, `GET /api/v1/me` wird mit gültigem Token aufgerufen, wenn die
  Antwort betrachtet wird, dann enthält sie **nie** das Feld `password` und keinen
  Bestätigungstoken.
- **AK-13** · Angenommen, `GET /api/v1/me/submissions` wird aufgerufen, wenn die Antwort
  betrachtet wird, dann enthält sie **nur** Restaurants, deren `submittedBy` der
  Token-Inhaber ist.
- **AK-14** · Angenommen, `POST /api/v1/restaurants` wird mit gültigem Token und
  gültigem Body aufgerufen, wenn die Anfrage durchläuft, dann entsteht ein Restaurant
  mit `submittedBy` = Aufrufer und `isVerified = false`, und die Antwort ist 201 mit
  der Detaildarstellung.
- **AK-15** · Angenommen, eine Koordinate liegt außerhalb ±90 bzw. ±180 oder ist keine
  Dezimalzahl, wenn angelegt wird, dann antwortet der Server mit **422** — nicht mit 500
  aus der Datenbankschicht.
- **AK-16** · Angenommen, Bild- oder Avatar-URLs stehen in einer Antwort, wenn sie
  betrachtet werden, dann sind sie **absolut** (Schema und Host), damit ein nativer
  Client sie ohne Basis-URL laden kann.
- **AK-17** · Angenommen, mehr als 100 Anfragen pro Minute kommen von einer IP, wenn die
  nächste eintrifft, dann antwortet der Server mit 429 und einem `Retry-After`-Header
  in Sekunden.
- **AK-18** · Angenommen, mehr als 5 Anmeldeversuche pro Minute kommen von einer IP,
  wenn der nächste eintrifft, dann antwortet der Server mit 429.
- **AK-19** · Angenommen, irgendeine Exception tritt unterhalb `/api/v1` auf, wenn sie
  den Nutzer erreicht, dann ist die Antwort JSON im Format
  `{"error": {"code", "message"}}` — nie eine HTML-Fehlerseite.
- **AK-20** · Angenommen, `/api/docs` wird aufgerufen, wenn die Seite lädt, dann zeigt
  Swagger UI alle `/api/v1`-Endpunkte mit Bearer-Sicherheitsschema.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-21** ⚠ · Angenommen, ein beliebiger angemeldeter Nutzer legt über
  `POST /api/v1/restaurants` einen Eintrag an, wenn danach die **öffentliche Website**
  `/{locale}/restaurants` aufgerufen wird, dann erscheint der Eintrag dort sofort — ohne
  jede Prüfung durch einen Admin.
  *(So verhält sich der Code heute: `RestaurantRepository::findPaginated()` filtert
  **nicht** auf `isVerified`; der Wert steuert nur ein Abzeichen und den optionalen
  Filter `?verified=1`. Der Web-Weg für dieselbe Absicht (B11) legt dagegen eine
  `RestaurantSuggestion` an, die ein Admin genehmigen muss (B21). Folge: Die
  API umgeht die Moderation vollständig — bei 100 Anfragen pro Minute je IP sind das
  6.000 öffentliche Einträge pro Stunde.)*

- **AK-22** ⚠ · Angenommen, jemand ruft `POST /api/v1/auth/register` wiederholt mit der
  **fremden** E-Mail-Adresse eines bestehenden Kontos auf, wenn die Anfragen
  durchlaufen, dann erhält diese Adresse pro Anfrage eine Hinweis-Mail — bis zu 100 pro
  Minute.
  *(So verhält sich der Code heute: `ApiRateLimitSubscriber` wählt den strengen
  `api_login`-Limiter nur für `^/api/v1/auth/login`; `register` fällt unter
  `api_anonymous` mit 100/Minute. Die Anti-Enumeration ist damit erkauft mit einem
  Mail-Versandvektor gegen Dritte.)*

- **AK-23** ⚠ · Angenommen, jemand registriert sich mit einer bereits vorhandenen
  Adresse, wenn er die Hinweis-Mail liest, dann steht dort: „Falls du dein Passwort
  vergessen hast, kannst du es über die Anmeldeseite zurücksetzen."
  *(So verhält sich der Code heute: `AuthController::sendAccountExistsHint()`. Ein
  Passwort-Zurücksetzen existiert nicht — weder Route noch Formular, siehe B01/FB-05.
  Die Mail verspricht eine Funktion, die es nicht gibt.)*

- **AK-24** ⚠ · Angenommen, ein Restaurant wird über die API mit `contact.email` und
  `contact.phone` angelegt, wenn der Eintrag danach öffentlich abgerufen wird, dann
  stehen dort Kontaktdaten, die ein beliebiger Nutzer über einen Dritten eingetragen hat.
  *(Keine Prüfung, keine Zustimmung, keine Herkunftskennzeichnung. Bemerkenswert im
  Kontrast dazu: Der offene Datensatz (B17) lässt genau diese Felder bewusst weg.)*

### Datenschutz und Missbrauchsschutz

- **AK-25** · Angenommen, irgendeine API-Antwort wird betrachtet, wenn nach `password`
  gesucht wird, dann kommt das Feld strukturell nicht vor — die Transformer listen die
  auszugebenden Felder einzeln auf.
- **AK-26** · Angenommen, ein JWT wird ausgestellt, wenn es geprüft wird, dann ist es
  mit dem privaten Schlüssel aus `config/jwt/` signiert, der **nicht** im Repository
  liegt.
- **AK-27** · Angenommen, eine anonyme Anfrage trifft auf eine geschützte Route, wenn
  sie abgewiesen wird, dann ist der Status **401** und nicht 403 — 403 bliebe für den
  Fall „angemeldet, aber Rolle fehlt".
- **AK-28** · Angenommen, ein 500er tritt in Produktion auf, wenn die Antwort betrachtet
  wird, dann enthält sie nur „Interner Serverfehler." ohne Exception-Klasse oder
  Stacktrace; im Debug-Modus zusätzlich das Detail.
- **AK-29** · Angenommen, CORS greift, wenn geprüft wird wo, dann ausschließlich für
  `^/api/v1/` — die Web-Seiten sind nicht freigegeben.

## Edge Cases

- **EC-01** · `AuthController::login()` wird nie ausgeführt; `json_login` fängt vorher
  ab. Die Methode wirft `LogicException` als Absicherung.
- **EC-02** · Die Reihenfolge in `access_control` ist bedeutungstragend: `auth` und
  `GET restaurants` stehen vor `me` und `POST restaurants`.
- **EC-03** · `/api/v1`-Routen sind sprachfrei, weil `config/routes.yaml` sie in einem
  eigenen Block lädt und am `controllers`-Loader ausschließt — sonst lägen sie unter
  `/{_locale}/api/v1`.
- **EC-04** · `applyOptionalData()` setzt jedes nicht übermittelte Merkmal auf `false`.
  Bei `POST` unproblematisch; ein späteres `PATCH` mit derselben Methode würde stillschweigend
  Merkmale löschen.
- **EC-05** · Sentry hängt mit Priorität 128 an `kernel.exception`, der
  `ApiExceptionSubscriber` mit 10 — Sentry sieht die Exception also, bevor sie zu JSON
  wird.
- **EC-06** · `cuisines` im Anlage-Body ruft `findOrCreateByName()`: Ein Tippfehler legt
  dauerhaft einen neuen Küchen-Typ an (siehe B08).

## Fehlbestand

- **FB-01 · Keine Moderation für über die API angelegte Restaurants.** Siehe AK-21. Der
  gewichtigste Befund des Features.
- **FB-02 · Registrierung fällt unter das schwache Limit.** Siehe AK-22.
- **FB-03 · Kein Ablaufmanagement für Token.** Kein Refresh-Token, kein Widerruf. Ein
  ausgestelltes JWT gilt bis zum Ablauf; ein kompromittiertes lässt sich nicht
  zurückziehen.
- **FB-04 · Keine Prüfung auf `isVerified` beim API-Login.** Dieselbe Lücke wie B01/FB-03,
  hier über die zweite Firewall.
- **FB-05 · Kein Lösch- oder Änderungsweg.** Wer über die App ein Restaurant falsch
  anlegt, kann es nicht korrigieren.
- **FB-06 · Keine Passkey-Unterstützung.** Auf iOS ausgerechnet.
- **FB-07 · `CORS_ALLOW_ORIGIN` ist nicht dokumentiert.** Ein zu weiter Wert (`*`)
  öffnete die API für jede Herkunft; welcher Wert auf Produktion gesetzt ist, geht aus
  dem Repository nicht hervor.
- **FB-08 · Kein Rate Limit je **Konto**, nur je IP.** Ein Angreifer mit wechselnden
  IPs umgeht AK-17 und AK-18 vollständig.
- **FB-09 · Validierung von Hand statt über den Validator.** `register` und `create`
  prüfen mit `mb_strlen` und `filter_var` — die Regeln stehen damit zweimal im Projekt
  (hier und in den FormTypes) und können auseinanderlaufen. Bereits sichtbar: Der
  Web-Weg begrenzt das Passwort auf 4096 Zeichen, die API nicht.

## Offene Fragen

- **OF-01** · Soll die API dieselbe Moderation durchlaufen wie der Web-Weg (AK-21)?
  Naheliegend wäre, `create` eine `RestaurantSuggestion` anlegen zu lassen statt eines
  `Restaurant`. Das ändert allerdings den Antwortvertrag der App. — Betreiber
- **OF-02** · Soll `register` unter den strengen Limiter (AK-22)? Ein Einzeiler im
  Subscriber. — Betreiber
- **OF-03** · Was steht auf Produktion in `CORS_ALLOW_ORIGIN`? — Betreiber
- **OF-04** · Gibt es die iOS-App überhaupt schon? Falls nein, ließen sich AK-21 und
  AK-22 folgenlos schließen. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | API Platform oder Plain Controller | Plain Controller + explizite Transformer | die Entity hat untypische Getter (`acceptsCash()`, `isWheelchairAccessible()`), die der ObjectNormalizer nicht zuverlässig erkennt |
| 2 | Serializer-Groups | keine | dieselbe Begründung; zusätzlich kann `password` strukturell nicht durchrutschen |
| 3 | Registrierung gibt kein Token | so | Anmeldung erst nach Bestätigung — wie im Web |
| 4 | Anti-Enumeration bei `register` | identische Antwort, Hash in beiden Zweigen | auch die Antwortzeit soll nichts verraten. ⚠ Der Web-Weg tut das Gegenteil (B01/AK-14) |
| 5 | Absolute Bild-URLs | `AssetUrlBuilder`, optional `APP_API_BASE_URL` | native Clients haben keine Basis-URL |
| 6 | Maße in eigenem Block `measurements` | nicht in `accessibility` | dort ist jeder Wert ein Boolean; ein `null` wäre ein Kompatibilitätsbruch |
| 7 | Anonym → 401 statt 403 | so | 403 bleibt für „angemeldet, Rolle fehlt" |
