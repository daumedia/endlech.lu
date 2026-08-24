# B23 · REST-API v1 (iOS-Backend) — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: nein.** Zwei Befunde mit Grad *hoch*.

33 von 35 Kriterien bestanden — und diese Zahl führt in die Irre, wenn man sie allein
liest. Vier der bestandenen Kriterien (AK-21 bis AK-24) sind mit ⚠ als *fragwürdiges
Verhalten* aufgenommen worden: „Bestanden" heißt dort, dass der Code sich genau so
verhält wie rekonstruiert. Genau das ist der Befund.

**Der Kern in einem Satz:** Die API kennt keine Moderation. Wer ein Konto hat — und ein
Konto bekommt jeder, der eine E-Mail-Adresse abrufen kann —, schreibt mit einem
`POST` einen Eintrag in die öffentliche Restaurantliste, in die Filterauswahl der
Website, in die veröffentlichten Kennzahlen der Transparenzseite und in den offenen
Datensatz unter CC BY 4.0. Nachgemessen: Zwei Aufrufe haben `verifiedShare` von
27,3 % auf 23,1 % und `averageScore` von 5,09 auf 4,31 gedrückt.

Dazu zwei durchgefallene Kriterien (AK-02, AK-19): Die Fehlerantworten des
JWT-Bundles brechen den Formatvertrag, den der Rest der API einhält — ausgerechnet
beim häufigsten Fehlerfall eines Mobil-Clients.

Nächster Aufruf: **`/sdd-build B23`** mit BF-24 und BF-25. **Die Erfassung wartet**
— ein Befund mit Grad *hoch* an laufendem Code darf nicht hinter 21 weitere Features
in eine Warteschlange.

## Akzeptanzkriterien im Einzelnen

### Anmeldung und Registrierung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `POST /auth/login` mit gültigen Daten → JWT, `alg: RS256`, Laufzeit **60 Minuten** |
| **AK-02** | ❌ durchgefallen | falsche Daten → **401**, aber Format `{"code":401,"message":…}` statt `{"error":{"code","message"}}`. Siehe BF-26 |
| AK-03 | ✅ bestanden | gültiger Body → **201**, generische Meldung, **kein** Token im Rumpf |
| AK-04 | ✅ bestanden | vorhandene Adresse → **byteweise identische** Antwort. Timing über je 10 Läufe: Median **477 ms** (neu) vs. **470 ms** (vorhanden) — kein auswertbarer Unterschied |
| AK-05 | ✅ bestanden | `{"name":"X","email":"keine-mail","password":"kurz"}` → 422 mit `violations` für alle drei Felder |
| AK-06 | ✅ bestanden | `nicht json` an `register` → 400 `{"error":{"code":400,…}}` |

### Restaurants lesen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-07 | ✅ bestanden | `GET /restaurants` ohne Token → **200** |
| AK-08 | ✅ bestanden | Schlüssel oben: `['data','meta']`; `meta = {page:1, limit:20, total:11, totalPages:1, sort:'rating'}` |
| AK-09 | ✅ bestanden | `?limit=500` → `meta.limit: 50` |
| AK-10 | ✅ bestanden | `?sort=voellig-erfunden` → `meta.sort: rating`, Reihenfolge identisch mit `?sort=rating` |
| AK-16 | ✅ bestanden | `avatarUrl: http://localhost:8000/uploads/avatars/…`, Bild-URL ebenso absolut. Der Host folgt dem `Host`-Header (Proxy-Fall funktioniert) — siehe BF-29 |

### Geschützte Endpunkte

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ✅ bestanden | `GET /me` ohne Token → **401** `{"error":{"code":401,"message":"Authentifizierung erforderlich."}}` |
| AK-12 | ✅ bestanden | Antwort enthält `id, name, email, avatarUrl, roles, isVerified, createdAt` — `password`, `verificationToken`, `pendingEmail`, `webauthnHandle` kommen **nicht** vor |
| AK-13 | ✅ bestanden | `/me/submissions` → `[178, 179, 182]`; laut Datenbank exakt dieselben drei |
| AK-14 | ✅ bestanden | `POST /restaurants` → 201, DB: `is_verified=0`, `submitted_by=117` |
| AK-15 | ✅ bestanden | `latitude:"999"` → 422 · `longitude:"-500"` → 422 · `latitude:"keine-zahl"` → 422. Kein 500er aus der Datenbankschicht |
| AK-27 | ✅ bestanden | anonym auf `POST /restaurants` → **401**, nicht 403 |

### Drosselung und Fehlerbehandlung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-17 | ✅ bestanden | 105 Anfragen an `GET /restaurants`: **100 × 200, ab der 101. → 429**, `Retry-After` gesetzt |
| AK-18 | ✅ bestanden | Anmeldeversuche: `1:401 2:401 3:401 4:401 5:401 6:429 7:429 8:429`, `Retry-After: 58` |
| **AK-19** | ❌ durchgefallen | Der eigene Subscriber liefert überall das vereinbarte Format — die Antworten des JWT-Bundles nicht: `{"code":401,"message":"Invalid JWT Token"}` bei abgelaufenem oder kaputtem Token. Siehe BF-26 |
| AK-20 | ✅ bestanden | `/api/docs` → 200; `docs.json`: **7 Pfade**, `securitySchemes: ['Bearer']`, **kein** Pfad außerhalb `/api/v1` |
| AK-28 | ✅ bestanden | Unit-Test `testAk28ProduktionZeigtKeinExceptionDetailBei500`: bei `debug=false` nur „Interner Serverfehler.", kein `exception`, kein `detail`, kein `SQLSTATE` im Rumpf; bei `debug=true` beides vorhanden |
| AK-29 | ✅ bestanden | `Origin: http://localhost:3000` → `Access-Control-Allow-Origin` gesetzt; `Origin: https://boeswillig.example` → **kein** Allow-Origin-Header; die Web-Route liefert gar keine CORS-Header |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-25 | ✅ bestanden | siehe AK-12 — die Transformer zählen die Felder einzeln auf |
| AK-26 | ✅ bestanden | `git ls-files config/jwt/` → leer; `.gitignore:37` schließt `/config/jwt/*.pem` aus; keine Schlüsseldatei je im Verlauf |

### Fragwürdiges Verhalten — bestätigt

Diese vier Kriterien sind **bestanden**, weil der Code sich verhält wie rekonstruiert.
Was gemessen wurde, ist trotzdem der schwerste Teil dieses Berichts.

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-21** | ✅ bestätigt | siehe die Kette unten — **BF-24** |
| **AK-22** | ✅ bestätigt | `admin@endlech.lu` erhielt in wenigen Sekunden **11 Hinweis-Mails**, anonym, ohne Sperre — **BF-25** |
| **AK-23** | ✅ bestätigt | Mailtext wörtlich: *„Falls du dein Passwort vergessen hast, kannst du es über die Anmeldeseite zurücksetzen."* — `debug:router` findet **0** passende Routen |
| **AK-24** | ✅ bestätigt | `contact.email = vorstand@fremde-firma.lu` angelegt → steht auf `/de/restaurants/189` öffentlich; im offenen Datensatz bewusst **nicht** (B17 lässt die Felder weg) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `AuthController.php:37` wirft `LogicException`; erreichbar ist die Methode nicht, `json_login` fängt vorher ab |
| EC-02 | ✅ bestanden | `GET /restaurants` öffentlich (200), `GET /me` geschützt (401) — die Reihenfolge in `access_control` trägt |
| EC-03 | ✅ bestanden | `/api/v1/restaurants` → 200, `/de/api/v1/restaurants` → **404** |
| EC-04 | ✅ bestanden | nicht übermittelte Merkmale stehen alle auf `false` in der Antwort |
| EC-05 | ✅ bestanden | Prioritäten belegt: Sentry-`ErrorListener` 128, `ApiExceptionSubscriber` 10 |
| EC-06 | ✅ bestätigt | `cuisines: ["Italienissch","Pizzza"]` → beide dauerhaft in `cuisine` (id 341, 342) **und in der öffentlichen Filterauswahl der Website**. Teil von BF-24 |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Fremder Zugriff** | `/me/submissions` liest über `findBySubmitter($this->getUser())` — keine ID aus der Anfrage. Kein Schreib- oder Löschweg für fremde Datensätze vorhanden (es gibt gar keinen, siehe FB-05) |
| **Rate Limits überrannt** | beide Limits greifen exakt an der Grenze (AK-17, AK-18). **`register` fällt unter das schwache** → BF-25 |
| **Limit über `X-Forwarded-For` umgehen** | `10.9.9.1/2/3` → alle **429**. `trusted_proxies` ist nicht gesetzt, der Header wird ignoriert. FB-08 gilt damit nur für einen Angreifer mit echten IPs |
| **XSS über die Filterauswahl** | `cuisines: ["<script>alert(1)</script>"]` → in der Datenbank roh, auf der Seite **maskiert** dargestellt. **Kein XSS** |
| **Spam über die Filterauswahl** | `"JETZT BEI UNS BESTELLEN 0900-123456"` steht danach in der öffentlichen Filterliste — **ja**, Teil von BF-24 |
| **Massenanlage** | 50 Küchen-Typen in **einer** Anfrage → 201. Bei 100 Anfragen/Minute sind das 5.000 Einträge je Minute |
| **Längengrenze** | Küchen-Typ mit 200 Zeichen (Spalte ist `VARCHAR(80)`) → **HTTP 500** statt 422 → BF-27 |
| **Personendaten in Antworten** | `password` und Token strukturell nicht vorhanden (AK-12) |
| **Geheimnisse** | keine `.pem`/`.key` unter git-Verwaltung, keine im Verlauf |
| **Unbestätigte Konten** | `unverified@endlech.lu` (`is_verified=0`) bekommt ein **Token** und erreicht `/me` mit 200. Derselbe Nutzer erreicht auch `/de/profile` mit 200 — die Lücke ist **nicht API-spezifisch**, sondern B01/FB-03 |

## Fehler

### BF-24 · Die API umgeht die Moderation vollständig — hoch

**Betrifft:** AK-21, AK-24, EC-06 · **FB-01 der Spec, dort als „gewichtigster Befund" geführt**

**Reproduktion:**
1. `POST /api/v1/auth/login` als beliebiger Nutzer → Token
2. `POST /api/v1/restaurants` mit `{"name":"QA Testlokal","city":"Strassen","emoji":"🧪"}`

**Erwartet:** ein Eintrag, der auf Freigabe wartet — so wie beim Web-Weg (B11 legt eine
`RestaurantSuggestion` an, die ein Admin genehmigen muss)
**Tatsächlich:** sofort öffentlich, gemessen an fünf Stellen:

| Stelle | Ergebnis |
|---|---|
| `/de/restaurants?sort=newest` | gelistet |
| `/de/restaurants/188` ohne Anmeldung | **200** |
| `GET /api/v1/restaurants?sort=newest` | an erster Stelle |
| `/open/dataset.csv` (CC BY 4.0) | **1 Treffer** |
| `/open.json` nach Cache-Ablauf | `restaurants: 11 → 13`, `verifiedShare: 27,3 % → 23,1 %`, `averageScore: 5,09 → 4,31` |

Die Startseite blieb frei, aber nur zufällig: Sie zeigt die sechs bestbewerteten, und
`rating` ist bei einem neuen Eintrag `null`.

**Drei Verstärker, alle gemessen:**
- **Kontaktdaten Dritter** (AK-24): `vorstand@fremde-firma.lu` steht nach dem Aufruf
  öffentlich auf der Detailseite. Keine Prüfung, keine Zustimmung, keine
  Herkunftskennzeichnung.
- **Die Filterauswahl ist von außen beschreibbar** (EC-06): `cuisines` ruft
  `findOrCreateByName()`. „JETZT BEI UNS BESTELLEN 0900-123456" stand danach in der
  öffentlichen Filterliste. Maskiert, also kein XSS — aber sichtbar.
- **50 Küchen-Typen je Anfrage**, 100 Anfragen je Minute: 5.000 Einträge in einer
  Auswahlliste, die jeder Besucher sieht.

**Ort:** `src/Controller/Api/V1/RestaurantApiController::create()` legt ein `Restaurant`
an; `RestaurantRepository::findPaginated()` filtert **nicht** auf `isVerified` — der Wert
steuert nur ein Abzeichen und den optionalen Filter `?verified=1`.

**Warum das hier schwerer wiegt als anderswo:** Endlech.lu veröffentlicht seine Zahlen
als Beleg gegenüber Fördergebern und stellt einen Datensatz unter CC BY 4.0 bereit. Ein
Datensatz, dessen Inhalt jeder mit einem Konto beschreiben kann, ist als Beleg wertlos —
und man sieht ihm das nicht an. Die Snapshot-Historie (B18) friert den verfälschten Stand
zusätzlich dauerhaft ein.

**Vorschlag:** `create()` eine `RestaurantSuggestion` anlegen lassen statt eines
`Restaurant` — dieselbe Mechanik wie der Web-Weg, der Admin-Freigabeweg steht bereits
(B21). Das ändert den Antwortvertrag der App (kein `Restaurant`-Objekt mehr, sondern eine
Eingangsbestätigung); ob es dafür schon einen Client gibt, ist OF-04 und **die
Vorfrage**. Falls die App noch nicht existiert, ist das folgenlos machbar. Falls doch,
ist die kleinere Fassung: `findPaginated()` auf `isVerified` filtern und den Datensatz
ebenso — dann bleibt der Eintrag bestehen, aber unsichtbar bis zur Freigabe.

Für `cuisines` gilt unabhängig davon: nur bestehende Typen zulassen. `findOrCreateByName()`
gehört in den Admin-Bereich, nicht in einen öffentlich erreichbaren Endpunkt.

### BF-25 · `register` fällt unter das schwache Limit — hoch

**Betrifft:** AK-22 · FB-02 der Spec

**Reproduktion:**
```bash
for i in $(seq 1 11); do
  curl -sX POST http://localhost:8000/api/v1/auth/register \
    -H 'Content-Type: application/json' \
    -d '{"name":"A B","email":"admin@endlech.lu","password":"geheim12345"}'
done
```
**Erwartet:** eine Sperre nach wenigen Versuchen
**Tatsächlich:** **11 Mails** an `admin@endlech.lu` in wenigen Sekunden, alle Antworten
201. Ohne Anmeldung, ohne Konto, gegen eine **frei wählbare fremde Adresse**.

**Ort:** `src/EventSubscriber/ApiRateLimitSubscriber.php` — der strenge `api_login`-Limiter
greift nur für `^/api/v1/auth/login`; `register` fällt unter `api_anonymous` (100/Minute).

**Rechnung:** 100 Mails je Minute und IP an eine beliebige Adresse. Das ist ein
Mail-Versender auf fremde Postfächer, gedeckt von der Absenderdomäne von Endlech.lu —
mit der Brevo-Quota und der Zustellreputation als Preis.

**Der bittere Teil:** Die Anti-Enumeration von AK-04 ist sauber gebaut — wortgleiche
Antwort, kein Timing-Unterschied. Sie ist der Grund, warum überhaupt eine Mail an eine
fremde Adresse geht. Ohne Deckel hat der Schutz den Vektor erst geschaffen.

**Vorschlag:** `register` in dieselbe strenge Klasse wie `login` (5/Minute) — ein
Einzeiler im Subscriber, in der Spec bereits als OF-02 vorgemerkt. Besser noch: einen
eigenen Limiter mit engerem Fenster (z. B. 5/Stunde je IP, analog dem Web-Weg, wo
`registration` seit BF-02 genau so eingestellt ist). **Der Web-Weg ist seit gestern
gedeckelt, der API-Weg nicht — dieselbe Handlung, zwei Maßstäbe.**

### BF-26 · Die Fehlerantworten des JWT-Bundles brechen den Formatvertrag — mittel

**Betrifft:** AK-02, AK-19

**Reproduktion:**
```
$ curl -sX POST /api/v1/auth/login -d '{"email":"…","password":"falsch"}'
{"code":401,"message":"Ongëlteg Zouganksdaten."}

$ curl -s /api/v1/me -H 'Authorization: Bearer kein.gueltiges.token'
{"code":401,"message":"Invalid JWT Token"}
```
**Erwartet:** `{"error": {"code": …, "message": …}}` wie überall sonst
**Tatsächlich:** flaches `{code, message}` ohne `error`-Umschlag

**Ort:** `config/packages/security.yaml:23–24` — `lexik_jwt_authentication.handler.authentication_failure`
antwortet selbst und wirft keine Exception, an der `ApiExceptionSubscriber` ansetzen könnte.

**Warum das zählt:** Es trifft die beiden häufigsten Fehlerfälle eines Mobil-Clients —
falsches Passwort und abgelaufenes Token. Ein Client, der einheitlich `error.code` liest,
bekommt dort `undefined` und zeigt im Zweifel gar keine Meldung. Der Vertrag steht in
AK-19 und gilt für „irgendeine Exception unterhalb `/api/v1`".

**Zwei Nebenbefunde derselben Stelle:**
- Ohne `Accept-Language` antwortet die API **luxemburgisch** („Ongëlteg Zouganksdaten"),
  weil `translation.yaml` `default_locale: lb` führt. Mit `Accept-Language: de` kommt
  Deutsch. Für einen Client, der den Header nicht setzt, ist Luxemburgisch die
  Voreinstellung — vertretbar für Luxemburg, aber es steht nirgends.
- `Invalid JWT Token` ist gar nicht übersetzt.

**Vorschlag:** Einen eigenen `AuthenticationFailureHandler` registrieren, der dieselbe
Rumpfstruktur baut wie der Subscriber. Etwa 15 Zeilen, keine neue Abhängigkeit.

### BF-27 · Zu langer Küchen-Typ endet im 500er — niedrig

**Reproduktion:** `POST /api/v1/restaurants` mit `cuisines: ["A"×200]` (Spalte ist
`VARCHAR(80)`) → **HTTP 500**

**Erwartet:** 422 mit `violations`, wie AK-15 es für Koordinaten leistet
**Tatsächlich:** die DBAL-Exception schlägt durch

**Ort:** `RestaurantApiController::create()` — die Längen werden von Hand geprüft
(FB-09), für `cuisines` fehlt die Prüfung ganz.

**Warum es nicht nur Kosmetik ist:** Jeder 500er erzeugt in Produktion einen
Sentry-Fehlerbericht. Ein Aufrufer, der das in einer Schleife auslöst, verbrennt die
Sentry-Quota — und die Meldungen sehen aus wie ein echtes Problem.

### BF-28 · Interne Klassennamen in 404-Meldungen, auch in Produktion — niedrig

**Reproduktion:** `GET /api/v1/restaurants/999999` →
```json
{"error":{"code":404,"message":"\"App\\Entity\\Restaurant\" object not found by \"Symfony\\Bridge\\Doctrine\\ArgumentResolver\\EntityValueResolver\"."}}
```
**Ort:** `ApiExceptionSubscriber.php:46–48` übernimmt bei `HttpExceptionInterface` die
Meldung unverändert. Das ist **kein** Debug-Zusatz: Der Test
`testHttpExceptionMeldungWirdAuchInProduktionDurchgereicht` belegt es mit `debug=false`.

Verrät ORM, Framework-Version und Entity-Namen. Und für einen Client ist die Meldung
unbrauchbar — sie lässt sich niemandem anzeigen.

**Vorschlag:** Für 404 eine eigene Meldung setzen statt der durchgereichten. Die
Header-Übernahme (`Retry-After`, `WWW-Authenticate`) bleibt davon unberührt.

### BF-29 · Der `Host`-Header steuert die ausgegebenen URLs — niedrig

**Reproduktion:**
```
$ curl -H "Host: api.endlech.lu" http://localhost:8000/api/v1/restaurants/1/images
{"data":[{"url":"http://api.endlech.lu/uploads/restaurants/qa-bild.png",…}]}
```
`AssetUrlBuilder` nimmt Schema und Host aus dem Request; `trusted_hosts` ist nirgends
gesetzt. Auf Cloudways mit festem vhost ist die Ausnutzbarkeit gering — steht später ein
Cache oder CDN davor, ist es ein Vergiftungsweg.

**Vorschlag:** `APP_API_BASE_URL` auf Produktion setzen (der Override existiert bereits
und ist genau dafür gebaut) oder `framework.trusted_hosts` füllen.

## Hinweise ohne Fehlerstatus

- **Die Hinweis-Mail verspricht ein Passwort-Zurücksetzen, das es nicht gibt** (AK-23).
  Wörtlich: *„Falls du dein Passwort vergessen hast, kannst du es über die Anmeldeseite
  zurücksetzen."* `debug:router` findet null passende Routen. Kein eigener Befund — das
  ist BF-04, seit 2026-08-23 als Feature `01` in der Warteschlange. Der Satz gehört
  entfernt, solange die Funktion fehlt: Er schickt jemanden, der ausgesperrt ist, auf
  eine Suche, die nicht endet.
- **Unbestätigte Konten kommen durch** — über die API wie über den Browser. Nicht
  API-spezifisch, deshalb kein eigener Befund; steht als B01/FB-03.
- **JWT-Laufzeit 60 Minuten, kein Widerruf** (FB-03 der Spec). Ohne Refresh-Token heißt
  das: Die App fragt stündlich neu nach dem Passwort, oder sie speichert es. Beides ist
  eine Entscheidung, die getroffen werden sollte, bevor die App gebaut wird.
- **`code-reviewer`-Agent nicht eingesetzt** — die Sitzungsvorgaben untersagen den Aufruf
  von Subagenten ohne ausdrückliche Anforderung. Alle Befunde stammen aus dem
  Angriffsdurchlauf.

## Neue Tests

Drei Unit-Tests in `tests/Unit/EventSubscriber/ApiExceptionSubscriberTest.php`:
`testAk28ProduktionZeigtKeinExceptionDetailBei500`,
`testAk28DebugModusZeigtDasDetail`,
`testHttpExceptionMeldungWirdAuchInProduktionDurchgereicht`.

Der dritte ist bewusst ein Test **auf** das unerwünschte Verhalten: Er hält BF-28 fest,
bis es behoben ist, und schlägt dann fehl — das ist die Erinnerung.

Gesamtsuite: **333 Tests, 1147 Assertions, 1 übersprungen, 0 Fehler.**

Die beiden *hoch*-Befunde sind nicht als Test abgebildet: Ein Test, der prüft, dass
ungeprüfte Einträge öffentlich erscheinen, hielte den Fehler fest statt ihn zu melden.
Er entsteht mit der Reparatur, dann in umgekehrter Richtung.

## Nächster Schritt

`/sdd-build B23` mit BF-24 und BF-25. **Vorfrage an den Betreiber (OF-04): Gibt es die
iOS-App schon?** Bei Nein lassen sich beide Befunde folgenlos schließen — bei Ja ändert
BF-24 den Antwortvertrag, und das will vorher entschieden sein.

Die Erfassung von B19 und den folgenden Features wartet, bis B23 repariert und erneut
geprüft ist.
