# B23 · REST-API v1 — Abschlussbericht der Reparatur

Stand: 2026-08-24 · Eingang: Fehlerauftrag aus `qa-report.md` (Status `review`)
Auftrag: BF-24, BF-25, BF-26, BF-27, BF-28, BF-29 · Branch `fix/b04-profil-qa`

## Was gebaut wurde

| Befund | Grad | Zustand | Nachweis |
|---|---|---|---|
| BF-24 · API umgeht die Moderation | hoch | **behoben** | siehe unten |
| BF-25 · `register` unter dem schwachen Limit | hoch | **behoben** | `1:201 2:201 3:201 4:201 5:201 6:429 7:429` |
| BF-26 · Formatvertrag der JWT-Antworten | mittel | **behoben** | drei Fälle live, vier Tests |
| BF-27 · zu lange Küchen-Angabe → 500 | niedrig | **behoben** | jetzt 422 mit `violations.cuisines` |
| BF-28 · Klassennamen in 404-Meldungen | niedrig | **behoben** | jetzt „Nicht gefunden." |
| BF-29 · `Host`-Header steuert die URLs | niedrig | **nicht im Code behoben** | siehe *Was offen bleibt* |

### BF-24 — die Reparatur im Einzelnen

`POST /api/v1/restaurants` legt jetzt einen `RestaurantSuggestion` an statt eines
`Restaurant` und antwortet mit **202** statt 201. Das ist derselbe Datensatz, den der
Web-Wizard (B11) erzeugt, und er läuft durch dieselbe Freigabe (B21).

Nachgestellt nach der Reparatur, mit demselben Aufruf wie in der QA:

```
POST /api/v1/restaurants  →  HTTP 202
   öffentliche Website:  0 Treffer
   öffentliche API:      0 Treffer
   CC-BY-Datensatz:      0 Treffer
   /open.json restaurants: 11 → 11
   DB: 11 Restaurants, 20 Küchen-Typen, 1 Vorschläge
   Vorschlag: status=pending cuisine="Pizzza, JETZT BESTELLEN" lat=49.61160000 email=fremd@dritte.lu
   im Admin gelistet: JA
```

Vor der Reparatur stand derselbe Eintrag an allen fünf Stellen, und `/open.json` zeigte
13 statt 11.

**Drei Teile hängen daran:**

1. **`cuisines` ruft kein `findOrCreateByName()` mehr.** Die Namen landen als Freitext im
   Vorschlag (`Pizzza, JETZT BESTELLEN` in der Zeile oben). Damit ist die öffentliche
   Filterauswahl der Website nicht mehr von außen beschreibbar — der Küchen-Zähler blieb
   bei 20, obwohl zwei erfundene Namen im Aufruf standen.
2. **Nicht übermittelte Merkmale sind `TriState::UNKNOWN`, nicht `false`.** Die alte
   Fassung setzte jedes nicht gefragte Barrierefreiheitsmerkmal auf „nein" — eine
   Aussage, die niemand getroffen hatte. Für dieses Projekt ist das der wesentliche
   Unterschied (siehe `TriState` in `CLAUDE.md`).
3. **`RestaurantSuggestion` bekam drei Spalten** (`latitude`, `longitude`,
   `nearbyStopsNote`, Migration `Version20260824160000`). Ohne sie gingen die
   Koordinaten, die die API entgegennimmt und prüft (AK-15), zwischen Eingang und
   Freigabe verloren. `AdminSuggestionController::approve()` überträgt sie mit.

## Annahmen

**Die iOS-App existiert noch nicht — deshalb durfte sich der Antwortvertrag ändern.**

Das war die Vorfrage aus dem QA-Bericht (OF-04) und die einzige, an der die Reparatur
hing: Ein bestehender Client, der ein `Restaurant`-Objekt mit 201 erwartet, bekäme jetzt
eine Eingangsbestätigung mit 202 und bräche.

Vier Belege, alle im Projekt selbst:

| Beleg | Fundstelle |
|---|---|
| „belegt, dass die iOS-App bereits Geld kostet, **bevor sie existiert**" | `docs/prd.md:418` |
| App unter „**Belegt offen**" geführt, nicht unter „umgesetzt" | `docs/prd.md:462` |
| „als Backend für eine **künftige** native iOS-App" | `CHANGELOG.md:100` |
| 0 Swift-/Xcode-Dateien im Repository | `git ls-files` |
| `CORS_ALLOW_ORIGIN` steht auf `localhost` — kein Produktionsclient | `.env:74` |

Wäre die Annahme falsch, ist die Rücknahme klein: 201 statt 202 und die
`RestaurantTransformer::detail()`-Antwort auf dem Vorschlag statt der
Eingangsbestätigung. Der Moderationsschutz selbst bliebe davon unberührt.

**Zweite Annahme, kleiner:** Der Vorschlag übernimmt `notes` aus dem Feld `notes` des
Bodys. Das stand in keinem Kriterium — die API nahm es vorher nicht entgegen, weil
`Restaurant` kein solches Feld hat. Es einfach zu verwerfen wäre stiller
Informationsverlust gewesen.

## Was offen bleibt

**BF-29 ist nicht im Code behoben.** Der erste Versuch war
`framework.trusted_hosts: '%env(csv:TRUSTED_HOSTS)%'` — der Container-Build bricht damit
ab (`Invalid type for path "framework.trusted_hosts.0"`, erwartet Strings, bekommt ein
Array), und ein leerer Env-Wert ergäbe ein Muster, das **jeden** Host abweist. Für einen
Befund mit Grad *niedrig* ist das Risiko, Produktion unerreichbar zu machen, zu hoch.

Der Weg ist stattdessen dokumentiert: `.env` erklärt jetzt an `APP_API_BASE_URL`, dass
der Wert auf Produktion gesetzt gehört (`https://endlech.lu`) — dann nimmt
`AssetUrlBuilder` den Request-Host gar nicht erst. Das ist der Override, der genau dafür
gebaut wurde.

**Das ist eine Serveraufgabe, keine Codeänderung, und sie ist damit nicht erledigt.**

**Nicht angefasst, obwohl im QA-Bericht erwähnt:**
- Die API antwortet ohne `Accept-Language` **luxemburgisch** (`default_locale: lb`).
  Das zu ändern hieße, an der Voreinstellung der gesamten Website zu drehen — weit
  außerhalb dieses Auftrags. Als Hinweis in `CLAUDE.md` festgehalten.
- Die Hinweis-Mail verspricht ein Passwort-Zurücksetzen, das es nicht gibt (AK-23). Das
  ist BF-04 und läuft als Feature `01`.
- Unbestätigte Konten kommen durch (FB-04) — nicht API-spezifisch, steht als B01/FB-03.
- JWT ohne Refresh und ohne Widerruf (FB-03) — eine Produktentscheidung, kein Fehler.

## Was über das Feature hinaus verändert wurde

| Änderung | Warum |
|---|---|
| `RestaurantSuggestion` + 3 Spalten, Migration `Version20260824160000` | ohne sie verlöre die Reparatur die Koordinaten, die die API prüft |
| `AdminSuggestionController::approve()` + 3 Zeilen | überträgt dieselben Felder bei der Freigabe |
| `docs/data-model.md` | neue Spalten samt Begründung, warum ein Vorschlag Koordinaten führt, die der Wizard nicht abfragt |
| `translations/messages.{de,en,fr,lb}.yaml` | neuer `api:`-Block (4 Schlüssel × 4 Sprachen) |
| `.env` | Hinweis an `APP_API_BASE_URL` (BF-29) |
| `CLAUDE.md`, `CHANGELOG.md` | Projektkonvention bei Änderungen am Datenmodell und an Mustern |

Der Admin-Controller ist der einzige Eingriff außerhalb von `src/Controller/Api/V1/` und
`src/EventSubscriber/`. Er war unvermeidlich: Wer Felder anlegt, die nur die Freigabe
weiterreichen kann, muss die Freigabe mitziehen.

## Tests

**Neu, 9 Stück:**

`RestaurantApiControllerTest`: `testAk21CreateLegtEinenVorschlagAnKeinRestaurant`,
`testAk21VorschlagErscheintNichtInDerOeffentlichenListe`,
`testAk21CuisinesLegenKeineOeffentlichenKuechenTypenAn`,
`testBf27ZuLangeKuechenAngabeLiefert422`,
`testNichtUebermittelteMerkmaleWerdenNichtAlsNeinGespeichert`

`AuthControllerTest`: `testBf26FalschesPasswortAntwortetImVereinbartenFormat`,
`testBf26UngueltigesTokenAntwortetImVereinbartenFormat`,
`testBf26AbgelaufenesTokenAntwortetImVereinbartenFormat`,
`testBf25RegistrierungHaengtAmEigenenLimiter`

**Umgeschrieben:** `testHttpExceptionMeldungWirdAuchInProduktionDurchgereicht` hieß so,
weil er BF-28 festhielt, bis es behoben ist. Er schlug bei der ersten Ausführung nach der
Reparatur fehl — wie vorgesehen — und heißt jetzt
`testAk28VerraetKeineInternenKlassennamenBei404`. Dazu neu:
`testMeldungAndererHttpExceptionsBleibtErhalten`, damit die Reparatur nicht zu weit greift.

**Zwei Kriterien sind bewusst nicht als Test abgebildet:**
- Der Grenzwert des `api_register`-Limiters. In `when@test` steht das Limit auf 10000,
  sonst summierten sich die Aufrufe über die Suite — dieselbe Regelung wie bei allen
  anderen Limitern des Projekts. Der Test prüft stattdessen, dass der Endpunkt am
  richtigen Limiter hängt; der Grenzwert steht als Reproduktion oben.
- Das abgelaufene Token wird über `createFromPayload($user, ['exp' => time() - 60])`
  erzeugt — ohne den privaten Schlüssel ginge das nicht, mit ihm ist es ein Einzeiler.

**Suite:** 342 Tests, 1173 Assertions, 1 übersprungen, **0 Fehler**
(vorher 333; +9 neue).

## Verifikation

| Schritt | Ergebnis |
|---|---|
| `php -l` auf jede geänderte Datei | fehlerfrei |
| `bin/console lint:yaml translations config` | 41 Dateien gültig |
| `bin/console cache:clear` | fehlerfrei, Container baut |
| `doctrine:migrations:migrate` (dev + test) | beide auf `Version20260824160000` |
| `doctrine:schema:validate` | meldet nur das bekannte Index-Rauschen aus Altlasten; keine der neuen Spalten steht in der Abweichung |
| `php bin/phpunit` | 342 grün |
| Selbsttest aller sechs Befunde live | fünf behoben, BF-29 offen (siehe oben) |
| Testdaten zurückgesetzt | 3 Nutzer, 11 Restaurants, 20 Küchen-Typen, 0 Vorschläge |

## Übergabe

Status bleibt `building`. Nächster Aufruf: **`/sdd-qa B23`**.

Worauf die erneute Prüfung besonders schauen sollte:

1. **Ob die Reparatur zu weit greift.** `findOrCreateByName()` ist aus dem
   API-Pfad verschwunden — der Admin-Pfad (`AdminSuggestionController::approve()`) und
   `CuisineApiController` nutzen es weiter. Beides ist Absicht, beides ungeprüft.
2. **`/me/submissions`.** Es liest `findBySubmitter()`, also genehmigte Einträge. Über
   die API angelegte Vorschläge tauchen dort bis zur Freigabe **nicht** auf — wer etwas
   einreicht, sieht es zunächst nirgends. Das ist kein Befund gegen ein Kriterium, aber
   es ist eine Lücke im Erlebnis, und sie ist neu.
3. **Der 202-Rumpf** (`{status, id, message}`) ist nirgends in der Spec beschrieben. Er
   sollte dort landen, bevor jemand einen Client dagegen baut.
