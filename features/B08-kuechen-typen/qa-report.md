# B08 · Küchen-Typen — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — zwei niedrige Befunde.

16 von 16 Kriterien bestanden. Die Schnittstelle verhält sich sauber: Suche liefert
gefiltert und alphabetisch, `findOrCreateByName()` erzeugt keine Dublette (zweimal
derselbe Name → **dieselbe ID 717**), leerer Name → 400, und die Rollenschranke greift
(Nutzer 403, Gast 302).

**Der schwerste Befund dieser Spec hat sich heute erledigt.** AK-10 beschreibt, dass
*jeder angemeldete Nutzer* über `POST /api/v1/restaurants` einen Küchentyp in die
öffentliche Filterauswahl schreiben kann — die Rollenschranke schütze nur einen von zwei
Wegen. Gemessen: **Küchen 21 → 21**, der eingeschickte Name „Voellig Neue Kueche" steht
nicht in der Datenbank. Das ist die BF-24-Reparatur von heute Mittag.

Nächster Aufruf: **`/sdd-erfassen B12`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Schnittstelle

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `?q=ital` → `[{"id":697,"name":"Italienisch"}]` |
| AK-02 | ✅ bestanden | `?q=` → **20** Einträge, alphabetisch (`Amerikanisch, Asiatisch, Burger, Café & Bistro, Chinesisch, …`); Datenbank: 20 |
| AK-03 | ✅ bestanden | `{"name":"QA Küche"}` → **201**, `{"id":717,"name":"QA Küche"}` |
| AK-04 | ✅ bestanden | `{"name":""}` → **400**, `{"error":"Name is required"}` |
| **AK-05** | ✅ bestanden | derselbe Name erneut → **dieselbe ID 717**, Küchenzahl 20 → 21 (nur eine neue) |
| **AK-06** | ✅ bestanden | `POST /de/api/cuisines`: Nutzer → **403**, Gast → **302**; **0** Einträge angelegt. `GET .../search`: Nutzer 403, Gast 302 |

### Verwendung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-07 | ✅ bestanden | Restaurant mit zwei Küchen → auf der Detailseite `Italienisch, Pizza` |
| AK-08 | ✅ bestanden | in B05/AK-08 gemessen: `cuisine[]=637` → 2, `cuisine[]=640` → 1, beide → **3** (ODER, deckt sich mit der Datenbank) |
| **AK-09** | ✅ bestanden | Restaurant gelöscht → Küchen **21 → 21**, Verknüpfungszeilen **2 → 0**. Die Typen bleiben |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-13 | ✅ bestanden | Spalten: `id, name, slug` — keine personenbezogenen Daten |
| AK-14 | ✅ bestanden | `/{_locale}/api/cuisines/search` — die Route liegt **unter** dem Sprachpräfix, anders als `/api/v1`. Das ist die dokumentierte Eigenheit aus `CLAUDE.md` |

### Fragwürdiges Verhalten

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-10** ⚠ | ✅ **erledigt** | `POST /api/v1/restaurants` mit `cuisines: ["Voellig Neue Kueche"]` → HTTP 202, **Küchen 21 → 21**, Name nicht in der Datenbank. `RestaurantApiController.php:256` trägt jetzt den Kommentar *„Bewusst KEIN `findOrCreateByName()` mehr"* — seit BF-24 |
| **AK-11** ⚠ | ✅ bestätigt | 100 Zeichen → `SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column`, **HTTP 500** → BF-62 |
| **AK-12** ⚠ | ✅ bestätigt | `debug:router` findet genau **zwei** Routen: `api_cuisine_search` (GET) und `api_cuisine_create` (POST). Kein Löschweg → BF-63 |

## Fehler

### BF-62 · Zu langer Küchenname endet im 500er — niedrig

**Betrifft:** AK-11

**Reproduktion:** `POST /de/api/cuisines` mit einem Namen aus 100 Zeichen
**Tatsächlich:** **HTTP 500**
```
An exception occurred while executing a query: SQLSTATE[22001]:
String data, right truncated: 1406 Data too long for column 'name'
```

Die Spalte ist `VARCHAR(80)`, eine Längenprüfung findet nicht statt.

**Dasselbe Muster wie BF-27** (B23, zu lange Küchen-Angabe über die API) und **BF-51**
(B20, leeres Pflichtfeld) — jedes Mal endet eine fehlende Eingabeprüfung in einem 500er
statt einer Meldung. BF-27 ist am 2026-08-24 behoben worden; dieser Weg blieb offen,
weil er über einen anderen Controller läuft.

**Warum es trotz `ROLE_ADMIN` zählt:** Der Endpunkt wird vom Tom-Select-Feld im
Admin-Formular gerufen. Ein Admin, der einen langen Namen einträgt, bekommt keine
Meldung, sondern einen Serverfehler — und in `prod` einen Sentry-Bericht.

**Vorschlag:** `mb_strlen($name) > 80` prüfen und 422 mit Meldung liefern. Dieselben
drei Zeilen, die in `RestaurantApiController` seit BF-27 stehen.

### BF-63 · Die Küchenliste kann nur wachsen — niedrig

**Betrifft:** AK-12

**Nachweis:** Genau zwei Routen im gesamten Projekt:
```
api_cuisine_search    GET   /{_locale}/api/cuisines/search
api_cuisine_create    POST  /{_locale}/api/cuisines
```
Kein Löschweg, weder als Oberfläche noch als Endpunkt.

**Die Wirkung ist seit heute kleiner geworden, aber nicht weg.** Vor BF-24 konnte jeder
angemeldete Nutzer die Liste über die API beschreiben — in der B23-QA landeten dort
„Pizzza", „Sushiii" und „JETZT BEI UNS BESTELLEN 0900-123456". Dieser Weg ist zu. Was
bleibt:

- ein Admin, der sich vertippt (`api_cuisine_create`)
- ein genehmigter Vorschlag mit Tippfehler im Küchen-Freitext (B21/AK-04 —
  `findOrCreateByName()` läuft dort weiterhin, mit Admin-Sichtung davor)

Beides sind menschliche Fehler, und beide bleiben **dauerhaft in der öffentlichen
Filterauswahl** der Restaurantliste stehen.

**Vorschlag:** Ein Löschweg im Admin, der die Verknüpfungen mitzählt („wird von 3
Restaurants verwendet") und bei 0 Verwendungen ohne Rückfrage löscht. Alternativ eine
Bereinigungsroutine, die Typen ohne Verknüpfung nach einer Frist entfernt — dieselbe
Mechanik, die für die Wartelisten fehlt (BF-37).

## Was sich seit der Rekonstruktion geändert hat

**AK-10 war der Grund, warum dieses Feature in Rang 3 der Risikoreihenfolge stand.** Die
Spec formuliert es so: *„Die Rollenschranke auf `CuisineApiController` schützt damit nur
den einen von zwei Wegen. Folge: Die Küchenliste ist über die öffentlich zugängliche API
von jedem Konto beschreibbar."*

Gemessen am 2026-08-24:

| | vor BF-24 (in der B23-QA) | jetzt |
|---|---|---|
| 50 Namen über `POST /api/v1/restaurants` | 50 neue Küchen | **0 neue** |
| „JETZT BEI UNS BESTELLEN 0900-123456" | stand im öffentlichen Filter | nicht angelegt |

Der Weg über den Vorschlags-Assistenten (B21) besteht weiter — dort ist er richtig, weil
ein Admin ihn sieht, bevor er wirkt.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Rollenschranke** | beide Routen: Nutzer 403, Gast 302; **0** Einträge durch Unbefugte |
| **Dublettenschutz** | derselbe Name → dieselbe ID, keine zweite Zeile |
| **Zweiter Schreibweg (API)** | seit BF-24 zu — gemessen |
| **Längenprüfung** | fehlt → 500 statt 422 → BF-62 |
| **Löschweg** | keiner → BF-63 |
| **Personenbezogene Daten** | keine (`id, name, slug`) |
| **Kaskade** | Küchen überleben das Löschen eines Restaurants, Verknüpfungen verschwinden |
| **Testsuite** | 364 Tests, 0 Fehler |

## Neue Tests

Keine. Die Befunde sind beide Verhaltensfragen:

- **BF-62** ließe sich testen, aber der Test hielte den 500er fest und wäre nach der
  Reparatur falsch herum — dieselbe Lage wie bei BF-51.
- **BF-63** ist die Abwesenheit einer Funktion; ein Test darauf wäre ein Test auf eine
  Routenliste, und den gibt es bereits in anderer Form (`testAk14KeineRouteVergibtRollen`
  aus B19 zeigt das Muster).

Die vorhandene Abdeckung deckt `findOrCreateByName()` über `CuisineRepositoryTest` ab.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B12`. B08 geht auf `approved`; BF-62 und BF-63 stehen in
`features/befunde.md`.
