# B12 · Startseite — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: nein** — ein Befund mit Grad *mittel* an der prominentesten Seite der
Anwendung.

11 von 12 Kriterien bestanden. **AK-04 ist durchgefallen, und zwar nicht in der
Reihenfolge, sondern in der Anzahl:**

> Der Abschnitt „Top bewertete Restaurants" zeigt **ein** Restaurant. Nicht sechs.

Gemessen direkt gegen das Repository:
```
findTopRated(6)   → 1 Entity
findTopRated(20)  → 2 Entities
findTopRated(100) → 7 Entities
```

Die Ursache ist ein bekanntes Doctrine-Muster: `setMaxResults()` zusammen mit
`addSelect()` auf zwei Collections begrenzt die **SQL-Zeilen**, nicht die Entities. Das
bestbewertete Restaurant allein erzeugt **14 Zeilen** — das `LIMIT 6` verbraucht sich
innerhalb des ersten Datensatzes.

Und die Reparatur steht **acht Zeilen tiefer im selben File**: `findPaginated()` löst
genau dasselbe Problem mit `Paginator`. Gegenprobe mit demselben QueryBuilder: **6
Entities**.

Nächster Aufruf: **`/sdd-build B12`** mit BF-64. Die Erfassung wartet nicht — der Befund
ist *mittel*, kein *hoch*.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /` → **302**, `Location: http://localhost:8000/lb/` — nicht permanent |
| AK-02 | ✅ bestanden | Hero („11 Restaurants in Luxemburg"), „So funktioniert's", „Top bewertete Restaurants", „Warum Endlech.lu?", CTA „Mitmachen" — alle Abschnitte da. Der Kartenblock ist allerdings fast leer, siehe AK-04 |
| **AK-03** | ✅ bestanden | Hero zeigt „**11** Restaurants in Luxemburg"; Datenbank: 11 gesamt, 3 verifiziert. Es ist die Gesamtzahl |
| **AK-04** | ❌ **durchgefallen** | **1 statt 6** Restaurants → BF-64. Die Sortierung selbst stimmt: Nach Gleichstand-Erzwingung (Sushi Zen und Green Bowl auf 9.9) stand `Green Bowl` oben — alphabetisch vor `Sushi Zen`, wie AK-04 es verlangt |
| AK-05 | ✅ bestanden | `RestaurantRepository.php:27–30`: `leftJoin('r.openingHours')` + `addSelect('oh')`, `leftJoin('r.cuisines')` + `addSelect('c')`. **Kein N+1 — und genau das ist die Ursache von BF-64** |
| AK-06 | ✅ bestanden | ohne Anmeldung → **200** |
| AK-08 | ✅ bestanden | **0** E-Mail-Adressen, **0** Telefonnummern im Hauptteil |
| **AK-09** | ✅ bestätigt | `Cache-Control: max-age=0, must-revalidate, private`; **0** Cache-Aufrufe im `HomeController` — zwei Abfragen bei jedem Aufruf |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-07** ⚠ | ✅ bestätigt | Fall erzwungen: unverifiziertes Haus (`Umami Corner`) auf Bewertung 10.0 gesetzt → steht an **erster Stelle** der Startseite, **ohne** Verifiziert-Abzeichen. `findTopRated()` filtert nicht auf `isVerified` (0 Treffer im Code) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Restauranttabelle geleert → HTTP **200**, **0** Detailseiten-Links, Seite bleibt gültig |
| EC-02 | ✅ bestanden | `debug:router app_root`: `_controller: Symfony\Bundle\FrameworkBundle\Controller\RedirectController()` — kein eigener Controller |
| EC-03 | ✅ bestanden | `base.html.twig:23`: `{% if _current_route and _current_route != 'app_root' %}` |

## Fehler

### BF-64 · Die Startseite zeigt ein Restaurant statt sechs — mittel

**Betrifft:** AK-04

**Reproduktion:** `/de/` aufrufen und die Links im Abschnitt „Top bewertete Restaurants"
zählen:
```
Links im Block: ['/de/restaurants/399', '/de/restaurants']
```
Ein Detaillink, dazu der Link zur vollständigen Liste.

Direkt gegen das Repository:

| Aufruf | Ergebnis |
|---|---|
| `findTopRated(6)` | **1** Entity |
| `findTopRated(20)` | 2 Entities |
| `findTopRated(100)` | 7 Entities |
| SQL-Zeilen für das erste Restaurant allein | **14** |
| derselbe QueryBuilder mit `Paginator` | **6** Entities |

**Ort:** `RestaurantRepository::findTopRated()` (Zeilen 24–34).

**Die Ursache:** `setMaxResults(6)` wirkt auf das SQL-Ergebnis, nicht auf die Zahl der
Entities. Durch die beiden `leftJoin` + `addSelect` auf `openingHours` und `cuisines`
erzeugt jedes Restaurant so viele Zeilen wie es Kombinationen aus Öffnungszeiten und
Küchen hat — beim bestbewerteten Haus sind das 14. Doctrine hydratisiert daraus ein
Objekt, und das `LIMIT` ist verbraucht.

Die Zahlenreihe oben zeigt das Muster deutlich: Je größer das Limit, desto mehr Entities
— aber nie so viele, wie angefordert.

**Warum das niemandem aufgefallen ist:** Die Seite sieht nicht kaputt aus. Ein Raster
mit einer Karte wirkt wie eine Gestaltungsentscheidung, nicht wie ein Fehler. Und mit
leerer Datenbank oder wenigen Öffnungszeiten je Haus funktioniert es — die Fixtures
haben inzwischen sieben Zeitfenster pro Restaurant.

**Warum es zählt:** Das ist die erste Seite, die jeder Besucher sieht, und der einzige
Ort, an dem die Plattform ihre Restaurants zeigt, bevor jemand auf „Restaurants
entdecken" klickt. Sechs Karten sind ein Angebot; eine Karte ist ein Versehen.

**Vorschlag:** Denselben Weg wie `findPaginated()` acht Zeilen tiefer:
```php
return iterator_to_array(new Paginator($qb->getQuery(), true));
```
Der zweite Parameter (`$fetchJoinCollection`) ist genau dafür da. Nachgemessen: **6
Entities**, N+1 bleibt vermieden.

Alternativ die `addSelect()` streichen und N+1 in Kauf nehmen — bei sechs Datensätzen
wären das zwölf zusätzliche Abfragen. Der `Paginator` ist die bessere Antwort, weil er
beides erhält.

## Hinweise ohne Fehlerstatus

- **AK-07 (unverifiziertes Haus ganz oben)** bekommt keine eigene Nummer — es ist BF-41
  aus B17, dieselbe Produktfrage. Die Messung ist hier trotzdem festgehalten, weil die
  Startseite die prominenteste Stelle dafür ist: Das Haus stand oben **ohne** Abzeichen,
  ein Besucher sieht also, dass es ungeprüft ist. Das ist die ehrlichere Darstellung, als
  es wegzulassen.
  **Und der Verschärfer aus der Spec ist weg:** Sie verweist auf B23/AK-21 („bei einem
  über die API angelegten Haus steht das `rating` auf dem Vorgabewert") — seit BF-24
  entsteht über die API kein Restaurant mehr.
- **AK-09 (keine Zwischenspeicherung)** ist bestätigt, aber kein Befund: Zwei Abfragen je
  Aufruf sind bei diesem Bestand folgenlos, und der Pool dafür existiert
  (`cache.open_stats`). Erwähnt, weil die Spec danach fragt.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Anmeldung** | nicht nötig — 200 für Gäste |
| **Personenbezogene Daten** | keine im Hauptteil |
| **Leere Datenbank** | 200, kein Fehler |
| **Weiterleitung** | 302 (nicht 301) auf die Vorgabesprache |
| **Testsuite** | 364 Tests, 0 Fehler |

## Neue Tests

Keine — **und das ist hier eine Lücke, die ich benenne.** BF-64 wäre mit einem Test
sofort aufgefallen:

```php
self::assertCount(6, $repository->findTopRated(6));
```

Eine Zeile. Dass sie fehlt, ist der Grund, warum die Startseite seit einer unbestimmten
Zeit ein Restaurant zeigt. Ich lege den Test **nicht** an, weil er das aktuelle
Verhalten festhalten würde statt das gewünschte — er gehört in denselben Durchgang wie
die Reparatur, dann in der richtigen Richtung.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-build B12` mit BF-64. Die Reparatur ist eine Zeile, der Test dazu eine zweite, und
beides steht als Muster acht Zeilen tiefer im selben File.
