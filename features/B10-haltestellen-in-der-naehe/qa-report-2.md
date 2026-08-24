# B10 · Haltestellen in der Nähe — Testbericht, zweiter Durchlauf

Stand: 2026-08-24 · nach der Reparatur von BF-44, BF-45, BF-46
Vorstufe: `building` · Branch `fix/b04-profil-qa` · Commit `dc1bd96`

> Der erste Durchlauf steht in `qa-report.md` und bleibt dort stehen — samt dem
> datierten Nachtrag, der eine zu weitgehende Angabe darin richtigstellt.

## Fazit

**Production-ready: ja** — kein offener Befund aus dem Auftrag, ein neuer Hinweis.

Alle drei Befunde sind belegt geschlossen. Die beiden durchgefallenen Kriterien des
ersten Durchlaufs (AK-01, AK-15) bestehen jetzt.

Das Feature sagt, was es geprüft hat. Das war der Kern: Auf einer Plattform, deren Zweck
verlässliche Barrierefreiheitsangaben sind, war eine erfundene Barrierefreiheitsaussage
der schwerste Fehler, den ein Text machen konnte.

Nächster Aufruf: **`/sdd-erfassen B18`**. Die Erfassung läuft weiter.

## Was seit dem ersten Durchlauf anders ist

| | erster Durchlauf | dieser |
|---|---|---|
| **AK-01** | ❌ 3 von 11 Restaurants mit Haltestellen | ✅ **8 von 11** |
| **AK-15** | ❌ Schlüssel im Log (30 + 7 Zeilen) | ✅ **22 Zeilen mit `accessId=`, 0 im Klartext** |
| AK-12 ⚠ | keine Zeitvorgabe, >30 s | ✅ `timeout: 3`, `max_duration: 5` |
| Wortlaut | „Keine **barrierefreien** Haltestellen…" | „Keine Haltestelle im Umkreis von 1000 Metern gefunden." |

## Die reparierten Kriterien im Einzelnen

### AK-01 — Haltestellen erscheinen

Alle Restaurants mit Koordinaten, gezählt an den gerenderten Haltestellen-Karten:

```
Pizzeria Bella Vista    4      Café Nordstad          0
Umami Corner            0      Sushi Zen              3
Burger & Co.            1      Wäinhaus am Markt      2
Le Jardin Brasserie     1      Trattoria Roma         0
Steakhaus Moselle       1      Green Bowl             1
                               Brasserie du Grund     2
→ 8 mit, 3 ohne (vorher: 3 zu 8)
```

Die Höchstzahl von 5 wird eingehalten (Maximum: 4).

### AK-15 / BF-45 — der Schlüssel bleibt im Haus

Log geleert, dann elf Detailseiten abgerufen:

| | |
|---|---|
| Zeilen mit `accessId=` | **22** |
| davon im Klartext (`[a-f0-9]{8}`) | **0** |
| Vorkommen des konkreten Schlüssels | **0** |
| Form im Log | `accessId=<maskiert>` |

Beide Wege sind zu: Der Service protokolliert `{class} ({code})` statt der
Exception-Meldung, und `SecretMaskingProcessor` fängt die Zeilen ab, die Symfonys
`http_client`-Kanal selbst schreibt.

### BF-46 — der Wortlaut in allen vier Sprachen

```
de: Keine Haltestelle im Umkreis von 1000 Metern gefunden.
en: No stop found within 1000 metres.
fr: Aucun arrêt trouvé dans un rayon de 1000 mètres.
lb: Keng Haltestell am Ëmkrees vu 1000 Meter fonnt.
```

Der Radius steht in der Meldung — „keine Haltestelle gefunden" ohne Angabe des Umkreises
wäre keine Information. Der Herkunftshinweis darunter: *„Angaben zum Nahverkehr vom
Verkéiersverbond, ohne Aussage zur Barrierefreiheit der Haltestellen."*

Der Admin-Hinweis lautet jetzt: *„GPS-Koordinaten ermöglichen die automatische Suche nach
Haltestellen in der Nähe."* — ohne „barrierefreie".

**Gegenprobe:** `grep` über alle vier Kataloge nach `barrierefrei|barrièrefrä|accessible
stop|arrêt accessible` in Verbindung mit Haltestellen, Nahverkehr oder GPS: **kein
Treffer**. Die einzige verbliebene Erwähnung steht im Herkunftshinweis — als Verneinung.

### AK-12 / BF-44 — die Zeitvorgabe

`PublicTransportService.php:49–50`: `'timeout' => 3`, `'max_duration' => 5`.
Test `testAk12AufrufTraegtEineZeitvorgabe`: OK (3 Assertions, prüft beide Werte und dass
sie unter `default_socket_timeout` liegen).

## Regression

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-02 | ✅ | Karten mit Name, Entfernung, Linien und Symbol werden gerendert (4 bei Pizzeria Bella Vista) |
| AK-03 bis AK-11 | ✅ | die acht vorhandenen Unit-Tests laufen unverändert grün |
| AK-04 | ✅ | `testHttpErrorIsLoggedAndReturnsEmpty` **und** `testAk15…EnthaeltDenSchluesselNicht` zusammen: Der Fehler wird weiterhin protokolliert, nur ohne URL |
| AK-09 | ✅ | Höchstzahl 5 eingehalten |
| AK-10 | ✅ | Note erscheint weiterhin über den Haltestellen |
| AK-13/AK-14/AK-16 | ✅ | unverändert |

## Die Frage, die der Build-Bericht selbst gestellt hat

Der Abschlussbericht nennt drei Punkte für diese Prüfung. Alle drei nachgegangen:

### 1 · Maskiert der Processor zu viel?

Sieben Randfälle durchgespielt:

| Eingabe | Ergebnis |
|---|---|
| `Matched route mit sort=rating und page=2` | unverändert ✓ |
| `Doctrine: SELECT * FROM user WHERE token = ?` | unverändert ✓ (Leerzeichen um `=`) |
| `Das Wort Passwort=falsch (deutsches Feld)` | unverändert ✓ |
| `Feld apikey ohne Gleichheitszeichen erwähnt` | unverändert ✓ |
| `URL https://x.de/a?token=abc123&next=/de/profile` | `token=<maskiert>`, **`next=/de/profile` bleibt** ✓ |
| `User password=geheim in einer Formularmeldung` | maskiert ✓ (gewollt) |
| `accessId=abc"und noch Text` | maskiert, Text danach bleibt ✓ |

Im aktuellen Log betrifft die Maskierung **22 von 335 Zeilen (6,6 %)**. Der Processor
greift präzise: nur echte `parameter=wert`-Paare, und nur die sechs benannten Parameter.
**Kein Befund.**

### 2 · Ist 1000 m der richtige Wert?

Die drei Restaurants ohne Treffer, gegen die Schnittstelle mit wachsendem Radius:

| Restaurant | Ort | r=1000 | r=2000 | r=5000 |
|---|---|---|---|---|
| Umami Corner | Esch-Belval | 0 | **3** | 18 |
| Café Nordstad | Diekirch | 0 | 0 | **10** |
| Trattoria Roma | Ettelbruck | 0 | 0 | **1** |

**Der Radius allein löst es nicht.** In Diekirch und Ettelbruck — beides Städte mit
Bahnhof — liefert die Schnittstelle selbst im 2-km-Umkreis nichts. Das deutet auf einen
dünnen Datenbestand außerhalb der Hauptstadt oder auf ungenaue Fixture-Koordinaten hin;
beides ist von hier aus nicht zu unterscheiden.

**Kein Befund gegen ein Kriterium** — AK-01 verlangt „bis zu 5 Haltestellen", nicht
„bei jedem Restaurant". Aber es ist der Grund, warum OF-05 in der Spec stehen bleibt,
und ein Hinweis für die Betreiberfrage: Ein Radius, der in der Hauptstadt passt und im
Norden nicht, ist vielleicht die falsche Stellschraube.

### 3 · Steht der Herkunftshinweis an der richtigen Stelle?

Er erscheint unter jeder Haltestellenliste — bei acht Restaurants also achtmal. Das ist
die Frage, die der Build-Bericht als OF-04 vermerkt hat, und ich kann sie hier nicht
entscheiden: Es ist eine Gestaltungsfrage, keine Messung. **Bleibt Betreiberfrage.**

Was sich sagen lässt: Der Satz ist die Klarstellung, ohne die BF-46 nicht vollständig
behoben wäre. Ihn wegzulassen hieße, wieder eine Aussage im Raum stehen zu lassen, die
niemand geprüft hat.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Schlüssel im Log** | 0 Klartext-Treffer nach 11 Seitenaufrufen |
| **Processor-Überschuss** | 6,6 % der Zeilen betroffen, sieben Randfälle korrekt |
| **Hängender Dienst** | Rückkehr nach 0,3 s (im Build-Selbsttest gemessen) |
| **Besucherdaten nach außen** | unverändert keine (AK-14) |
| **Testsuite** | 362 Tests, 0 Fehler |

## Neue Tests

Keine — der Build-Durchgang hat zwei umgedreht und zwei neu angelegt, und sie decken die
reparierten Punkte ab. Was hier an Prüfungen dazukam (Processor-Randfälle, Radius gegen
die echte Schnittstelle), gehört bewusst nicht in die Suite: Der eine Teil ist im
Unit-Test schon abgedeckt, der andere hängt an einem Fremddienst und wäre beim nächsten
Fahrplanwechsel rot.

## Nächster Schritt

`/sdd-erfassen B18`. B10 geht auf `approved`; BF-44, BF-45 und BF-46 wandern in
`features/befunde.md` nach *Behoben*.

**BF-23 bleibt offen.** Der Processor entschärft ihn — `token=` wird in allen Kanälen
maskiert —, aber ob das dort ausreicht, entscheidet die erneute Prüfung von B04, nicht
dieser Bericht. Der Build-Bericht hat das ausdrücklich so stehen lassen; das ist die
richtige Trennung.
