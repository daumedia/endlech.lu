# B10 · Haltestellen in der Nähe — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Auf der Restaurant-Detailseite erscheinen die nächstgelegenen Bus- und Tramhaltestellen
mit Entfernung und Linien, abgefragt aus der HAFAS-Schnittstelle des Verkéiersverbond.
Für Menschen, die auf den öffentlichen Nahverkehr angewiesen sind, ist das eine
Kernangabe — nicht Beiwerk.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B06 | bestand | wird nur auf der Detailseite angezeigt |

## User Stories

- **US-01** · Als Gast ohne Auto möchte ich sehen, welche Haltestelle am nächsten liegt.
- **US-02** · Als Betreiber möchte ich eine eigene Notiz zur Anfahrt hinterlegen können.

## Nicht im Scope

- Fahrplanauskunft, Abfahrtszeiten, Umsteigeverbindungen
- Karte oder Routenführung
- Haltestellen in der Liste (B05) oder in der API (B23)

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Restaurant hat Koordinaten und ein API-Schlüssel ist
  hinterlegt, wenn die Detailseite lädt, dann erscheinen bis zu **5** Haltestellen,
  aufsteigend nach Entfernung.
- **AK-02** · Angenommen, Haltestellen werden angezeigt, wenn eine Karte betrachtet
  wird, dann stehen dort Name, Entfernung in Metern, die Linien als Abzeichen und ein
  Symbol für Bus, Tram oder beides.
- **AK-03** · Angenommen, **kein** API-Schlüssel ist hinterlegt (`MOBILITEIT_API_KEY`
  leer), wenn die Detailseite lädt, dann bleibt der Block leer und die Seite
  funktioniert unverändert — es erscheint keine Fehlermeldung.
- **AK-04** · Angenommen, die HAFAS-Schnittstelle antwortet mit einem Fehler oder gar
  nicht, wenn die Detailseite lädt, dann bleibt der Block leer, der Fehler wird
  protokolliert und die Seite lädt normal.
- **AK-05** · Angenommen, ein Restaurant hat **keine** Koordinaten, wenn die
  Detailseite lädt, dann wird die Schnittstelle gar nicht erst gerufen.
- **AK-06** · Angenommen, dieselben Koordinaten werden erneut abgefragt, wenn die
  zweite Anfrage kommt, dann wird die Antwort **24 Stunden** aus dem Cache bedient.
- **AK-07** · Angenommen, zwei Restaurants liegen weniger als etwa 11 Meter
  auseinander, wenn beide abgefragt werden, dann teilen sie sich den Cache-Eintrag —
  der Schlüssel rundet die Koordinaten auf vier Nachkommastellen.
- **AK-08** · Angenommen, die Schnittstelle liefert dieselbe Haltestelle mehrfach, wenn
  die Antwort verarbeitet wird, dann erscheint sie **einmal** — die Entdopplung läuft
  über den Namen.
- **AK-09** · Angenommen, mehr als 5 Haltestellen liegen im Umkreis, wenn die Antwort
  verarbeitet wird, dann werden 20 abgefragt, nach Entfernung sortiert und die
  nächsten 5 behalten.
- **AK-10** · Angenommen, ein Betreiber hat eine `nearbyStopsNote` hinterlegt, wenn die
  Detailseite lädt, dann erscheint sie zusätzlich zu den Haltestellen.
- **AK-11** · Angenommen, das `products`-Bitfeld der Antwort wird ausgewertet, wenn der
  Typ bestimmt wird, dann gilt: Bit 4 = Tram, Bit 32 oder 64 = Bus, beides = `mixed`,
  sonst `bus`.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-12** ⚠ · Angenommen, die HAFAS-Schnittstelle antwortet **langsam** (statt gar
  nicht), wenn die Detailseite geladen wird, dann wartet der Besucher — bis der
  PHP-Standard `default_socket_timeout` greift.
  *(So verhält sich der Code heute: `PublicTransportService` übergibt weder `timeout`
  noch `max_duration` an `HttpClientInterface::request()`, und
  `config/packages/framework.yaml` konfiguriert keine `http_client.default_options`.
  Folge: Ein hängender Drittdienst blockiert die wichtigste Seite der Anwendung. Der
  `catch (\Throwable)` fängt den Ausfall, nicht die Verzögerung.)*

- **AK-13** ⚠ · Angenommen, der API-Schlüssel wird übertragen, wenn geprüft wird wie,
  dann steht er als Query-Parameter `accessId` in der URL.
  *(So verhält sich der Code heute — es ist die von HAFAS vorgesehene Übergabe. Folge:
  Der Schlüssel landet in den Zugriffsprotokollen des Betreibers der Schnittstelle und
  in jedem dazwischenliegenden Proxy. Bei der Rotation zu bedenken.)*

### Datenschutz und Missbrauchsschutz

- **AK-14** · Angenommen, eine Abfrage geht an `cdt.hafas.de`, wenn geprüft wird, was
  übertragen wird, dann sind es: Koordinaten des **Restaurants**, Radius, Höchstzahl und
  der API-Schlüssel. **Keine** Daten des Besuchers — die Anfrage läuft Server zu Server,
  die IP des Besuchers geht nicht mit.
- **AK-15** · Angenommen, ein Fehler wird protokolliert, wenn der Eintrag gelesen wird,
  dann enthält er die Fehlermeldung, nicht die vollständige URL — der Schlüssel landet
  also nicht im eigenen Log.
- **AK-16** · Angenommen, der API-Schlüssel wird gesucht, wenn das Repository
  durchsucht wird, dann steht in `.env` nur ein leerer Wert; der echte gehört in die
  ungetrackte `.env.local`.

## Edge Cases

- **EC-01** · Leerer Schlüssel ist der **Normalzustand** in Entwicklung und Test — der
  frühe `return []` macht das Feature dort lautlos abwesend.
- **EC-02** · `$this->cache` ist der Standard-Pool `cache.app`, nicht der eigene
  `cache.open_stats` (B16). Ein `cache:clear` trifft damit auch die Haltestellen.
- **EC-03** · Die Antwort wird über `toArray()` gelesen; ein nicht-JSON-Körper wirft und
  landet im `catch`.
- **EC-04** · `maxNo: 20` ist fest verdrahtet, `maxStops` (5) konfigurierbar über
  `app.mobiliteit_max_stops`. Der Radius (`app.mobiliteit_radius`) steht auf 500 Metern.

## Fehlbestand

- **FB-01 · Kein Timeout.** Siehe AK-12. Die wirksamste Einzelmaßnahme des Features:
  eine Zeile `'timeout' => 3` im Request-Aufruf.
- **FB-02 · Kein Circuit Breaker und keine negative Zwischenspeicherung.** Fällt die
  Schnittstelle aus, wird sie bei **jedem** Seitenaufruf erneut gerufen — der Cache
  speichert nur Erfolge, weil der `catch` außerhalb von `cache->get()` liegt.
- **FB-03 · Kein Rate Limit gegen die eigene Nutzung.** Wie viele Abrufe der
  Verkéiersverbond zulässt, geht aus dem Code nicht hervor; ohne negative
  Zwischenspeicherung (FB-02) skaliert der Verbrauch mit den Seitenaufrufen.
- **FB-04 · Kein Hinweis auf die Herkunft der Daten in der Oberfläche.** Die Angaben
  stammen von einem Dritten; weder Quelle noch Abrufzeitpunkt sind für den Besucher
  sichtbar.
- **FB-05 · Kein Auftragsverarbeitungsvertrag dokumentiert.** Es gehen zwar keine
  Besucherdaten hinaus (AK-14), aber der Dienst ist eine externe Abhängigkeit und
  gehört nach `docs/datenschutz.md`, das noch nicht existiert.

## Offene Fragen

- **OF-01** · Welcher Timeout ist angemessen (FB-01)? Drei Sekunden wären großzügig
  und lägen deutlich unter jeder Geduldsschwelle. — Betreiber
- **OF-02** · Ist ein Schlüssel auf Produktion hinterlegt? Ohne ihn ist das Feature
  unsichtbar, und niemand würde es bemerken. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Verhalten ohne Schlüssel | leeres Array, kein Fehler | dasselbe Muster wie Sentry: leerer Wert = lautlos inaktiv |
| 2 | Cache-Dauer | 24 Stunden | Haltestellen ändern sich selten |
| 3 | Cache-Schlüssel gerundet | 4 Nachkommastellen | ~11 Meter; benachbarte Restaurants teilen sich den Eintrag |
| 4 | Entdopplung über den Namen | statt über eine Kennung | HAFAS liefert dieselbe Haltestelle unter mehreren Kennungen |
| 5 | Eigenes DTO statt Array | `NearbyStop` (readonly) | typsicher im Template |
| 6 | 20 abfragen, 5 zeigen | so | die Sortierung nach Entfernung braucht mehr Kandidaten als angezeigt werden |
| 7 | `catch (\Throwable)` statt gezielter Ausnahmen | breit | jeder Fehler des Dritten soll die Seite tragen — der Preis ist, dass auch eigene Fehler verschluckt werden |
