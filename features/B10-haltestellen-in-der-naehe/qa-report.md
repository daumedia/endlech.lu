# B10 · Haltestellen in der Nähe — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: nein** — nicht wegen eines Sicherheitsproblems, sondern weil das
Feature **falsche Aussagen macht**.

18 von 20 Kriterien bestanden, zwei durchgefallen (AK-01, AK-15). Dazu drei Befunde mit
Grad *mittel*, von denen der erste an dieser Stelle schwerer wiegt als sonst irgendwo:

> Auf `/de/restaurants/{id}` steht *„Keine barrierefreien Haltestellen in der Nähe
> gefunden."* — bei **8 von 11** Restaurants. Die Abfrage kennt **kein einziges**
> Barrierefreiheitsmerkmal; sie fragt nach Haltestellen im Umkreis von 500 Metern und
> bekommt keine.

Ein Mensch im Rollstuhl liest dort, dass er nicht hinkommt. Tatsächlich steht da nur:
Der Radius ist zu klein. Auf einer Plattform, deren einziger Zweck verlässliche
Barrierefreiheitsangaben sind, ist eine erfundene Barrierefreiheitsaussage der
schwerste Fehler, den ich in dieser Prüfreihe gefunden habe — obwohl technisch nichts
kaputt ist.

Nächster Aufruf: **`/sdd-build B10`** mit BF-46. **Die Erfassung wartet.**

## Die Messung, um die es geht

Der lokale `.env.local` trägt einen gültigen Schlüssel; das Feature ist also aktiv und
live prüfbar. Gegen die echte Schnittstelle, alle elf Fixture-Restaurants, beim
konfigurierten Radius von 500 Metern:

```
Pizzeria Bella Vista           0        Sushi Zen                      2
Umami Corner                   0        Wäinhaus am Markt              0
Burger & Co.                   1        Trattoria Roma                 0
Le Jardin Brasserie            0        Green Bowl                     0
Steakhaus Moselle              0        Brasserie du Grund             2
Café Nordstad                  0
→ 3 von 11 Restaurants zeigen überhaupt Haltestellen
```

Dieselben Koordinaten mit größerem Radius:

| Koordinaten | r=500 | r=1000 | r=2000 | r=3000 |
|---|---|---|---|---|
| 49.6116 / 6.1319 (Luxemburg-Stadt) | **0** | — | **7** | — |
| 49.6293 / 6.1594 | — | **1** | — | — |
| 49.5000 / 5.9500 | — | — | — | **18** |

Die Schnittstelle funktioniert einwandfrei. Der Radius ist das Problem.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-01** | ❌ durchgefallen | „bis zu 5 Haltestellen, aufsteigend nach Entfernung" — bei 8 von 11 Restaurants erscheinen **null**, obwohl Koordinaten und Schlüssel vorhanden sind. Siehe die Messung oben → BF-46 |
| AK-02 | ✅ bestanden | Die drei Restaurants mit Treffern zeigen Name, Entfernung, Linien-Abzeichen und Symbol; die Verarbeitung deckt der vorhandene Test `testParsesDeduplicatesSortsAndLimits` ab |
| AK-03 | ✅ bestanden | `testEmptyApiKeyReturnsEmptyWithoutHttpCall` — bei leerem Schlüssel kein HTTP-Aufruf; die Seite lädt in 0,62 s ohne Fehlermeldung |
| AK-04 | ✅ bestanden | `testHttpErrorIsLoggedAndReturnsEmpty` — Fehler wird protokolliert, Block bleibt leer, Seite lädt |
| AK-05 | ✅ bestanden | Restaurant ohne Koordinaten: Seite lädt in **0,04 s** statt 0,49 s — `RestaurantController.php:75` prüft `hasCoordinates()` vorher |
| AK-06 | ✅ bestanden | `testCachesResultForIdenticalCoordinates`; `$item->expiresAfter(86400)` im Service |
| AK-07 | ✅ bestanden | `md5(round((float) $lat, 4).'_'.round((float) $lng, 4))` — vier Nachkommastellen ≈ 11 m |
| AK-08 | ✅ bestanden | `testParsesDeduplicatesSortsAndLimits` |
| AK-09 | ✅ bestanden | `maxNo: 20` im Request, `testRespectsMaxStopsLimit` für die 5 |
| AK-10 | ✅ bestanden | Hinweis `QA-Hinweis: Bus 16 hält direkt vor der Tür.` erschien auf der Detailseite |
| AK-11 | ✅ bestanden | Bit-Auswertung im vorhandenen Parser-Test abgedeckt |
| **AK-12** ⚠ | ✅ bestätigt | real gemessen → BF-44 |
| **AK-13** ⚠ | ✅ bestätigt | `accessId` als Query-Parameter — die von HAFAS vorgesehene Übergabe |
| AK-14 | ✅ bestanden | Übertragen werden `accessId`, `originCoordLat`, `originCoordLong`, `r`, `maxNo`, `format`. **Keine** Besucher-IP, kein User-Agent — die Anfrage läuft Server zu Server |
| **AK-15** | ❌ durchgefallen | Der Schlüssel steht sehr wohl im eigenen Log → BF-45 |
| AK-16 | ✅ bestanden | `.env` trägt einen leeren Wert; `.env.local` ist nicht unter git-Verwaltung (0 Treffer in `git ls-files`) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | leerer Schlüssel = Normalzustand in `.env`; früher `return []` ohne HTTP-Aufruf |
| EC-02 | ✅ bestanden | Der Service bekommt `cache.app`, nicht `cache.open_stats` |
| EC-03 | ✅ bestanden | `toArray()` wirft bei Nicht-JSON, der `catch (\Throwable)` fängt es |
| EC-04 | ✅ bestanden | `maxNo: 20` fest, `radius`/`maxStops` über `config/services.yaml:16–17` |

## Fehler

### BF-46 · Das Feature behauptet Barrierefreiheit, die es nie geprüft hat — mittel

**Betrifft:** AK-01

**Reproduktion:**
1. `.env.local` mit gültigem `MOBILITEIT_API_KEY` (lokal vorhanden)
2. `/de/restaurants/{id}` für ein Restaurant mit Koordinaten aufrufen

**Erwartet:** bis zu fünf Haltestellen
**Tatsächlich:** bei 8 von 11 Restaurants steht dort
*„Keine barrierefreien Haltestellen in der Nähe gefunden."*

**Zwei Ursachen, die zusammenkommen:**

1. **Der Radius ist zu klein.** `app.mobiliteit_radius: 500` liefert für Luxemburg-Stadt
   null Treffer, `r=2000` an denselben Koordinaten sieben. Der Wert ist nirgends
   begründet.
2. **Der Meldungstext behauptet etwas, das der Code nirgends prüft.** `grep` nach
   `accessib|barrier|wheelchair` in `PublicTransportService.php` und `NearbyStop.php`:
   **keine einzige Stelle**. Die Abfrage kennt Name, Entfernung, Linien und
   Verkehrsmittel — kein Barrierefreiheitsmerkmal.

Der Text steht so in allen vier Sprachen (`messages.{de,en,fr,lb}.yaml:157`):
```
de: "Keine barrierefreien Haltestellen in der Nähe gefunden."
en: "No accessible stops found nearby."
fr: "Aucun arrêt accessible trouvé à proximité."
lb: "Keng barrierefrä Haltestellen an der Géigend fonnt."
```
Und dieselbe Behauptung im Admin-Formular (`messages.de.yaml:485`): *„GPS-Koordinaten
ermöglichen die automatische Suche nach barrierefreien Haltestellen in der Nähe."*

**Warum das an dieser Stelle schwerer wiegt als anderswo:** Endlech.lu existiert, damit
Menschen mit Behinderung sich auf Angaben verlassen können. Wer im Rollstuhl sitzt und
liest „Keine barrierefreien Haltestellen in der Nähe", plant nicht hin. Die Aussage ist
frei erfunden — richtig wäre „im Umkreis von 500 m keine Haltestelle gefunden", was eine
ganz andere Information ist. **Eine erfundene Barrierefreiheitsaussage ist auf dieser
Plattform kein Textfehler, sondern ein Feature-Fehler.**

**Vorschlag, in dieser Reihenfolge:**
1. Den Text in allen vier Sprachen auf das ändern, was tatsächlich geprüft wurde:
   *„Keine Haltestelle im Umkreis von %radius% Metern gefunden."* Dasselbe für den
   Admin-Hinweis. Das ist eine halbe Stunde und behebt den schwersten Teil sofort.
2. Den Radius erhöhen — 1000 m wären ein Fußweg von etwa 12 Minuten und lieferten in der
   Stichprobe deutlich mehr. Der Wert steht in `config/services.yaml:16`.
3. Falls Barrierefreiheit tatsächlich gemeint war: HAFAS liefert das nicht in dieser
   Abfrage. Dann ist es ein neues Feature, keine Reparatur.

### BF-44 · Kein Timeout auf dem Fremdaufruf — mittel

**Betrifft:** AK-12 · FB-01 der Spec (*„die wirksamste Einzelmaßnahme des Features"*)

**Gemessen** gegen einen Server, der 60 Sekunden schweigt:

| Aufruf | Ergebnis |
|---|---|
| wie im Code (ohne Zeitvorgabe) | **nach 30 s keine Antwort**, Messung abgebrochen |
| mit `'timeout' => 3` | Abbruch nach **3,0 s**: „Idle timeout reached" |

`PHP default_socket_timeout: 60` — so lange wartet der Besucher im schlimmsten Fall auf
der Restaurant-Detailseite, der meistbesuchten Seite der Anwendung. Der
`catch (\Throwable)` fängt den **Ausfall**, nicht die **Verzögerung**.

`config/packages/framework.yaml` enthält **0** Treffer für `http_client` — es gibt auch
keine projektweite Voreinstellung.

**Vorschlag:** `'timeout' => 3` im Request-Aufruf. Eine Zeile, 57 Sekunden Unterschied.
Der Test `testAk12AufrufTraegtKeineZeitvorgabe` hält den Befund fest und schlägt fehl,
sobald die Zeile steht.

### BF-45 · Der API-Schlüssel steht im eigenen Log — mittel

**Betrifft:** AK-15 (das Kriterium behauptet das Gegenteil)

**Nachweis** aus `var/log/dev.log`:
```
app.ERROR: HAFAS API error: HTTP/2 401  returned for
  "https://cdt.hafas.de/opendata/apiserver/location.nearbystops?accessId=…&originCoordLat=…"
```
Kanalverteilung der Zeilen mit `accessId=`: **`http_client`: 30 Zeilen, `app`: 7 Zeilen.**

Der Service loggt `$e->getMessage()` — und die Meldung von Symfonys HttpClient **enthält
die vollständige URL**. Die Spec nimmt an, die Meldung sei kurz; gemessen ist sie es
nicht.

Der zweite Weg ist der `http_client`-Kanal, den Symfony selbst bedient. **In `prod` ist
er nicht ausgeschlossen** — `monolog.yaml:46` filtert `["!deprecation", "!doctrine"]`.
Bei jedem Fehler ab WARNING schreibt der `fingers_crossed`-Handler seinen Puffer nach
`php://stderr`, samt Schlüssel.

**Das ist BF-23 in dritter Ausprägung** (nach `Matched route` mit Token und den
doctrine-Parametern). Der Befund dort lautete: Ein Processor, der bekannte Geheimnisse
maskiert, ist die tragfähigere Lösung als kanalweises Ausschließen.

**Vorschlag:** Beim Loggen die URL entfernen statt sie durchzureichen — etwa nur
`$e->getCode()` und den Statuscode. Plus der Processor aus BF-23, der `accessId=…` und
`token=…` in allen Kanälen maskiert. Der Test
`testAk15FehlerprotokollEnthaeltDenSchluessel` hält den Zustand fest.

**Praktische Folge unabhängig von der Reparatur:** Der Schlüssel in `.env.local` steht
im lokalen Log und ist damit als kompromittiert zu behandeln, sobald das Log das Gerät
verlässt. Bei der nächsten Rotation mitdenken.

## Hinweise ohne Fehlerstatus

- **FB-02 (keine negative Zwischenspeicherung)** ist im Code belegt: Der
  `catch (\Throwable)` liegt **außerhalb** von `cache->get()`, es wird also nur der
  Erfolgsfall zwischengespeichert. Fällt die Schnittstelle aus, wird sie bei jedem
  Seitenaufruf erneut gerufen — mit BF-44 zusammen heißt das: jeder Aufruf 60 Sekunden.
  Kein eigener Befund, weil die Reparatur von BF-44 den Schaden auf 3 s je Aufruf
  begrenzt; danach lohnt eine getrennte Betrachtung.
- **AK-13 (Schlüssel als Query-Parameter)** ist bestätigt, aber die von HAFAS vorgesehene
  Übergabe — kein Fehler unserer Seite. Er verschärft allerdings BF-45: Was in der URL
  steht, landet in jedem Log auf dem Weg.
- **OF-02 ist teilweise beantwortet:** Lokal ist ein Schlüssel hinterlegt und gültig.
  Ob auf Produktion einer steht, bleibt offen — und wäre nach BF-46 auch keine gute
  Nachricht: Mit Schlüssel zeigt die Seite die falsche Aussage, ohne bleibt der Block
  leer.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Zwei in `tests/Unit/Service/PublicTransportServiceTest.php`:
`testAk12AufrufTraegtKeineZeitvorgabe` (prüft, dass der Service keine eigene Zeitvorgabe
setzt und deshalb `default_socket_timeout` greift) und
`testAk15FehlerprotokollEnthaeltDenSchluessel`.

Beide halten einen Befund fest und schlagen fehl, sobald er behoben ist — dasselbe
Verfahren wie bei BF-28 in B23, wo es funktioniert hat.

BF-46 ist bewusst **kein** Test: Er hängt an einer Aussage über die Wirklichkeit
(„liefert der Radius Treffer?"), die gegen einen Fremddienst läuft. Ein Test darauf wäre
beim nächsten Fahrplanwechsel rot, ohne dass sich im Code etwas geändert hätte.

**Suite: 360 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-build B10` mit BF-46 als erstem — die Textänderung ist klein, und sie behebt den
Teil, der Menschen in die Irre führt. BF-44 (eine Zeile) und BF-45 gehören in denselben
Durchgang.

Die Erfassung von B18 und den folgenden Features wartet, bis B10 repariert und erneut
geprüft ist.
