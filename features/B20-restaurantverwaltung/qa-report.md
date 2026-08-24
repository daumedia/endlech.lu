# B20 · Restaurantverwaltung (Admin) — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — drei Befunde, keiner blockierend, aber einer davon hätte in
der Rekonstruktion auffallen müssen.

19 von 20 Kriterien bestanden, **AK-03 durchgefallen**: Ein leeres Pflichtfeld endet in
einem **HTTP 500**, nicht in einem 422. Der Grund ist präzise und im ganzen Projekt
einmalig an dieser Stelle:

```
Error: App\Entity\Restaurant::setName(): Argument #1 ($name) must be of type string, null given
```

Das Formular **hat** `NotBlank`-Constraints (`RestaurantType.php:43,51`). Sie kommen nur
nie zum Zug: `handleRequest()` schreibt über den PropertyAccessor in die Entity, **bevor**
validiert wird, und `setName(string $name)` nimmt kein `null`.

Dass es niemandem aufgefallen ist, hat einen Grund: Der Browser erzwingt `required`. Man
kommt normalerweise gar nicht dazu, leer abzuschicken.

Nächster Aufruf: **`/sdd-erfassen B21`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Liste und Zugriff

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | 11 Tabellenzeilen bei 11 Restaurants in der Datenbank |
| AK-10 | ✅ bestanden | `/999999/bearbeiten` → **404** · `/abc/bearbeiten` → **404** |
| AK-11 | ✅ bestanden | `ROLE_USER` → **403** auf Liste und Anlageformular; Gast → **302** |

### Anlegen und Bearbeiten

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-02 | ✅ bestanden | *„Restaurant erfolgreich erstellt."*, DB: `QA Neu is_verified=0` |
| **AK-03** | ❌ **durchgefallen** | leeres `name` und `city` → **HTTP 500** statt 422 → BF-51 |
| AK-04 | ✅ bestanden | Test `testEditTogglesVerification`: nach dem Haken sind `verifiedAt` und `verifiedBy` gesetzt |
| AK-05 | ✅ bestanden | Knopf zurückgenommen → *„Verifizierung für »Umami Corner« aufgehoben."*, DB: `is_verified=0 verifiedAt=NULL verifiedBy=NULL` |
| **AK-06** | ✅ bestanden | Neuer Test `testAk06UnveraenderteVerifizierungBleibtUnangetastet`: Nach dem Verifizieren nur den Namen geändert — `verifiedAt` bleibt identisch. `AdminRestaurantController.php:60` merkt sich `$wasVerified` **vor** `handleRequest()` (EC-02) |

### Verifizieren und Löschen

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-07** | ✅ bestanden | Knopf mit gültigem Token → *„»Umami Corner« als verifiziert markiert."*, DB: `is_verified=1 verifiedAt=2026-08-24 17:15:49 verifiedBy=158` |
| AK-08 | ✅ bestanden | Restaurant gelöscht → Restaurantzeile **0**, Bildzeilen **0** (Kaskade greift) |
| AK-09 | ✅ bestanden | `_token=falsch` → *„Ungültiges CSRF-Token."*, `is_verified` bleibt bei 1 |
| AK-17 | ✅ bestanden | `toggle-verified-{id}` und `delete-restaurant-{id}` als eigene Token; `new`/`edit` als Symfony-Formulare |
| **EC-01** | ✅ bestanden | Das Token für `toggle-verified` ist session-basiert — als gerendertes Hidden-Feld ausgelesen (`551e2c8df5b73f65…`), der stateless `csrf-token`-Platzhalter hätte nicht gereicht |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-15 | ✅ bestanden | Geschäftskontaktdaten plus `verifiedBy` und `submittedBy` |
| AK-16 | ✅ bestanden | `information_schema`: `FK_restaurant_verified_by → SET NULL` |
| AK-18 | ✅ bestanden | **0** Entwurfsfelder in der Entity; „QA Neu" war sofort unter `/de/restaurants?sort=newest` sichtbar |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-12** ⚠ | ✅ bestätigt | **0** Blätter-Links; `AdminRestaurantController.php:32`: `findBy([], ['createdAt' => 'DESC'])` → BF-52 |
| **AK-13** ⚠ | ✅ bestätigt | Bildzeile und Datei angelegt, Restaurant gelöscht → **Datei liegt weiterhin auf der Platte** → BF-53 |
| **AK-14** ⚠ | ✅ bestätigt | Wartelisteneintrag verwies auf Restaurant 303; nach dem Löschen `restaurant_id=NULL`, Meldung nur *„Restaurant erfolgreich gelöscht."* — keine Rückfrage, kein Hinweis |

## Fehler

### BF-51 · Leeres Pflichtfeld endet im Serverfehler — mittel

**Betrifft:** AK-03

**Reproduktion:**
```
POST /de/admin/restaurants/neu
  restaurant[name]=
  restaurant[city]=
```
**Erwartet:** 422 mit Feldfehlern
**Tatsächlich:** **HTTP 500**
```
Error: App\Entity\Restaurant::setName(): Argument #1 ($name) must be of type string, null given
InvalidTypeException: Expected argument of type "string", "null" given at property path "name"
```
Auch der Fall „nur `name` und `city` gefüllt, `emoji` fehlt" → **500**. Ein Name mit
einem Zeichen (`X`) geht dagegen **durch** (302) — nicht leer, also kein TypeError.

**Ort:** `src/Entity/Restaurant.php:182` — `setName(string $name)`, ebenso `setCity()`
(194) und `setEmoji()` (233). Alle drei ohne `?`.

**Warum die vorhandene Validierung nicht greift:** `RestaurantType.php:43,51` trägt
`NotBlank`-Constraints. Sie laufen erst nach `handleRequest()` — und dort schreibt der
PropertyAccessor bereits `null` in einen Setter, der nur `string` annimmt. Der TypeError
fliegt, bevor irgendetwas validiert wurde.

**Warum es bisher nicht auffiel:** `required` steht im Markup an `name`, `city` und
`emoji`. Ein Browser lässt das Formular gar nicht erst abschicken. Erreichbar ist der
Fall über einen Client ohne HTML-Validierung, ein abgeschnittenes Formular oder einen
Zurück-Knopf mit wiederhergestelltem Zustand.

**Warum es trotzdem zählt:** In `prod` sieht der Admin „Interner Serverfehler" ohne
Hinweis, was falsch war — und **jeder 500er erzeugt einen Sentry-Bericht**. Ein Admin,
der zweimal auf einen alten Tab klickt, produziert Fehlerberichte, die aussehen wie ein
echtes Problem.

**Vorschlag:** `'empty_data' => ''` an den drei Feldern in `RestaurantType` — dann
schreibt der PropertyAccessor einen leeren String, `NotBlank` greift, und der Admin
bekommt seinen 422. Die Setter-Signaturen bleiben unangetastet; sie auf `?string` zu
öffnen wäre die schlechtere Lösung, weil dann `null` in der Datenbank landen könnte.

Der Test `testAk03LeeresPflichtfeldEndetImServerfehler` hält den Befund fest und schlägt
fehl, sobald er behoben ist.

### BF-52 · Die Verwaltungsliste blättert nicht — niedrig

**Betrifft:** AK-12

**Nachweis:** **0** Blätter-Links in der Liste; `findBy([], ['createdAt' => 'DESC'])`
ohne Grenze.

Der Kontrast ist der Punkt: Die **öffentliche** Liste (B05) blättert zu sechs, die API
(B23) deckelt bei 50 — und ausgerechnet der Verwaltungsbereich, der als einziger jeden
Datensatz mit Bildern, Status und Aktionsknöpfen rendert, lädt alles.

**Zweites Auftreten desselben Musters** nach BF-40 (Wartelisten-Verwaltung). Dort steht
schon: Blättern ist im Projekt vorhanden und wird in den Verwaltungslisten nicht
angewandt.

**Vorschlag:** `RestaurantRepository::findPaginated()` existiert bereits für die
öffentliche Liste. Dieselbe Mechanik, andere Sortierung.

### BF-53 · Bilddateien überleben das Löschen — niedrig

**Betrifft:** AK-13

**Reproduktion:**
1. `RestaurantImage`-Zeile und Datei `public/uploads/restaurants/qa-verwaist.png` anlegen
2. Restaurant im Admin löschen

**Erwartet:** Datei verschwindet mit
**Tatsächlich:**
```
Restaurant in der DB: 0 · Bildzeilen: 0
Datei auf der Platte: NOCH DA — verwaist
```

**Ort:** `AdminRestaurantController::delete()` ruft `$entityManager->remove($restaurant)`.
Die Kaskade räumt die Datenbankzeilen ab, aber `ImageUploadService::delete()` — die
einzige Stelle mit `unlink()` — wird nicht durchlaufen.

**Folge:** Dateien, die niemand mehr zuordnen kann, und die **jeden Deploy überleben**
(`git clean -fd` läuft ohne `-x`, `public/uploads/restaurants` ist gitignoriert). Sie
sind weiterhin über ihre URL abrufbar — wer den Dateinamen kennt, sieht das Bild eines
gelöschten Restaurants.

**Vorschlag:** Vor dem `remove()` über `$restaurant->getImages()` laufen und
`ImageUploadService::delete()` je Bild aufrufen. Alternativ ein `postRemove`-Callback an
`RestaurantImage` — das wäre robuster, weil es auch andere Löschwege erfasst.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Rollenschranke** | `ROLE_USER` 403, Gast 302 auf beiden geprüften Routen |
| **CSRF** | falsches Token bei `verifizieren` → keine Änderung; session-basiertes Token korrekt gebunden |
| **Fremde IDs** | 404 bei nicht existierend und bei falschem Format |
| **Kaskaden** | Bildzeilen verschwinden mit dem Restaurant; Wartelisten-Verweis wird auf `NULL` gesetzt |
| **Dateisystem** | **verwaiste Dateien** → BF-53 |
| **Fehlerbehandlung** | **500 statt 422** bei leerem Pflichtfeld → BF-51 |
| **Testsuite** | 364 Tests, 0 Fehler |

## Hinweise ohne Fehlerstatus

- **AK-14 (Wartelisten-Verweis ohne Hinweis)** ist bestätigt, bekommt aber keine eigene
  Nummer: `SET NULL` ist die richtige Kaskade — der Eintrag soll erhalten bleiben. Was
  fehlt, ist der Hinweis an den Admin („Dieses Restaurant ist mit einer
  Partneranmeldung verknüpft"). Das ist eine Verbesserung, kein Fehler, und steht als
  offener Punkt in `spec.md`.
- **Ein einbuchstabiger Restaurantname geht durch** (`name=X` → 302). Die Spec verlangt
  im Admin keine Mindestlänge — im Vorschlags-Wizard und in der API gibt es sie
  (2–150 Zeichen). Kein Befund, aber eine Ungleichheit, die auffällt.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Zwei in `tests/Functional/Controller/AdminRestaurantControllerTest.php`:

- `testAk03LeeresPflichtfeldEndetImServerfehler` — hält BF-51 fest, inklusive der
  genauen Meldung. Er erwartet `InvalidTypeException` (der PropertyAccessor verpackt den
  `TypeError`), was beim ersten Anlauf auffiel und den Befund noch präziser macht.
- `testAk06UnveraenderteVerifizierungBleibtUnangetastet` — der Fall, den die vorhandenen
  elf Tests nicht abdeckten: Speichern **ohne** Änderung am Haken darf das Prüfdatum
  nicht verschieben. Ohne diesen Test wäre eine Regression an `$wasVerified` unbemerkt
  geblieben.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B21`. B20 geht auf `approved`; BF-51, BF-52 und BF-53 stehen in
`features/befunde.md`.

BF-51 ist der Kandidat für den nächsten Reparaturdurchgang — drei `'empty_data' => ''`,
und ein 500er wird zu dem 422, den die Constraints ohnehin schon vorbereitet haben.
