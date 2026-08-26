# B10 · Haltestellen in der Nähe — Abschlussbericht der Reparatur

Stand: 2026-08-24 · Eingang: Fehlerauftrag aus `qa-report.md` (Status `review`)
Auftrag: BF-44, BF-45, BF-46 · Branch `fix/b04-profil-qa`

## Was gebaut wurde

| Befund | Grad | Zustand | Nachweis |
|---|---|---|---|
| BF-46 · erfundene Barrierefreiheitsaussage | mittel | **behoben** | 3 von 11 → **8 von 11** Restaurants mit Haltestellen; „barrierefrei" aus dem Block verschwunden |
| BF-44 · kein Timeout | mittel | **behoben** | `'timeout' => 3`, `'max_duration' => 5` |
| BF-45 · Schlüssel im Log | mittel | **behoben** | 2 Zeilen mit `accessId=`, **0 davon im Klartext** |

### BF-46 — zwei getrennte Ursachen, zwei getrennte Reparaturen

**Der Text sagt jetzt, was tatsächlich geprüft wurde.** In allen vier Katalogen:

| | vorher | jetzt |
|---|---|---|
| `nearby_stops.no_stops` | „Keine **barrierefreien** Haltestellen in der Nähe gefunden." | „Keine Haltestelle im Umkreis von %meters% Metern gefunden." |
| `admin…location_hint` | „automatische Suche nach **barrierefreien** Haltestellen" | „automatische Suche nach Haltestellen in der Nähe" |
| `nearby_stops.source` | — | „Angaben zum Nahverkehr vom Verkéiersverbond, ohne Aussage zur Barrierefreiheit der Haltestellen." |

Der Radius steht jetzt im Text — „keine Haltestelle gefunden" ohne Angabe, in welchem
Umkreis gesucht wurde, ist keine Information. Der Controller reicht
`app.mobiliteit_radius` dafür ans Template durch.

**Der Radius steht auf 1000 statt 500.** Selbsttest über alle elf Restaurants gegen die
echte Schnittstelle:

```
Pizzeria Bella Vista   Haltestellen sichtbar     Café Nordstad        keine (Meldung)
Umami Corner           keine (Meldung)           Sushi Zen            Haltestellen sichtbar
Burger & Co.           Haltestellen sichtbar     Wäinhaus am Markt    Haltestellen sichtbar
Le Jardin Brasserie    Haltestellen sichtbar     Trattoria Roma       keine (Meldung)
Steakhaus Moselle      Haltestellen sichtbar     Green Bowl           Haltestellen sichtbar
                                                 Brasserie du Grund   Haltestellen sichtbar
→ 8 mit Haltestellen, 3 ohne (vorher: 3 zu 8)
```

Und: `grep` nach `barrierefrei|barrièrefrä` im gerenderten Haltestellen-Block → **kein
Treffer**.

### BF-44 — eine Zeile, 57 Sekunden

`'timeout' => 3` und `'max_duration' => 5` im Request. Der zweite Wert deckelt auch
Verbindungen, die antworten, aber quälend langsam — `timeout` allein greift nur bei
Untätigkeit.

Der Selbsttest durch den echten Service gegen einen schweigenden Server: **Rückkehr nach
0,3 s** mit leerem Ergebnis, Fehler gefangen. Vorher: nach 30 Sekunden noch keine Antwort.

### BF-45 — zwei Wege, zwei Reparaturen

1. **Der Service reicht die Exception-Meldung nicht mehr durch.** Statt
   `$e->getMessage()` (das die vollständige URL samt `accessId` enthält) protokolliert er
   `{class} ({code})`. Für die Fehlersuche reicht das: Ein 401 ist ein 401.
2. **`App\Monolog\SecretMaskingProcessor`** deckt den zweiten Weg ab — Symfonys eigener
   `http_client`-Kanal, der jede Anfrage samt URL schreibt und den kein Anwendungscode in
   der Hand hat. Er maskiert `accessId`, `token`, `apikey`, `api_key`, `access_token` und
   `password` in Meldung **und** Kontext, rekursiv.

Selbsttest nach geleertem Log und einem echten Seitenaufruf:
```
Zeilen mit accessId= im Log: 2
davon im Klartext:           0
     accessId=<maskiert>
```

Die Parameterliste ist bewusst kurz und ausdrücklich statt heuristisch: Was dort fehlt,
fällt in der nächsten QA auf; was eine Heuristik zu viel maskiert, fällt niemandem auf
und macht Logs unbrauchbar. Der Test
`testBf45ProcessorLaesstGewoehnlicheZeilenUnveraendert` hält das fest.

## Eine Richtigstellung an meinem eigenen QA-Bericht

**Der QA-Bericht behauptet, der falsche Satz stehe „bei 8 von 11 Restaurants" auf der
Seite. Das stimmt nicht.**

Beim Bauen fiel auf, dass der Satz eine zweite Bedingung hat:

```twig
{% if nearbyStops is not empty or restaurant.nearbyStopsNote %}   ← äußere Bedingung
    …
    {% elseif restaurant.hasCoordinates %}                        ← hier steht der Satz
```

Ohne Haltestellen **und** ohne `nearbyStopsNote` wird der ganze Block übersprungen. Im
Fixture-Bestand trägt genau ein Restaurant eine Note — und das hatte Haltestellen. **Der
falsche Satz war dort also nie zu sehen.** Ich hatte die Note für den AK-10-Test selbst
gesetzt und dann die Meldung gesehen.

Der Befund bleibt gültig, seine Reichweite war kleiner:
- Der Text stand in allen vier Katalogen und wäre erschienen, sobald ein Betreiber eine
  Note bei einem Restaurant ohne Haltestellen hinterlegt.
- Der **Admin-Hinweis** war dagegen immer sichtbar — das ist der Teil, der real gewirkt
  hat.
- Der Radius war real zu klein.

Die Richtigstellung steht als datierter Nachtrag **im QA-Bericht selbst**, nicht nur
hier. Ein Prüfdokument stillschweigend anzugleichen wäre der schlechtere Weg: Dann wüsste
niemand mehr, dass die Zahl einmal falsch war.

## Annahmen

**1000 Meter als neuer Radius** — die Spec fragt in OF-01 nach dem Timeout, nicht nach
dem Radius; einen begründeten Wert gibt es nirgends. 1000 m entsprechen etwa zwölf
Minuten Fußweg und liegen damit im Bereich dessen, was jemand tatsächlich geht. In der
Stichprobe kippt der Wert das Verhältnis von 3:8 auf 8:3. Wäre 2000 m gewünscht, ist es
eine Zahl in `config/services.yaml:16`.

**3 Sekunden Timeout** — OF-01 nennt drei Sekunden als „großzügig und deutlich unter
jeder Geduldsschwelle". Übernommen.

## Was offen bleibt

- **FB-02 (keine negative Zwischenspeicherung)** ist nicht im Auftrag und bleibt. Die
  Wirkung ist durch BF-44 aber stark gedämpft: Fällt die Schnittstelle aus, kostet jeder
  Seitenaufruf jetzt 3 statt 60 Sekunden. Ob sich ein Circuit Breaker danach noch lohnt,
  ist eine eigene Abwägung — als offener Punkt in `spec.md` vermerkt.
- **AK-13 (Schlüssel als Query-Parameter)** bleibt: So sieht HAFAS die Übergabe vor. Das
  Protokollproblem daran ist behoben, der Schlüssel geht aber weiterhin durch fremde
  Zugriffsprotokolle. Bei der Rotation zu bedenken.
- **Der Schlüssel in `.env.local` ist als kompromittiert zu behandeln** — er stand vor
  der Reparatur im lokalen Log. Das ist keine Codeänderung, sondern eine Handlung des
  Betreibers.
- **OF-02 (Schlüssel auf Produktion?)** bleibt offen. Nach dieser Reparatur ist die
  Antwort allerdings weniger heikel: Mit Schlüssel zeigt die Seite jetzt richtige
  Angaben, ohne bleibt der Block leer.

## Was über das Feature hinaus verändert wurde

| Änderung | Warum |
|---|---|
| `src/Monolog/SecretMaskingProcessor.php` (neu) | Reparatur des zweiten BF-45-Wegs. **Wirkt projektweit** — siehe unten |
| `translations/messages.{de,en,fr,lb}.yaml` | drei Schlüssel je Sprache |
| `src/Controller/RestaurantController.php` | reicht den Radius ans Template |
| `CLAUDE.md`, `CHANGELOG.md` | Projektkonvention |

**Der Processor nimmt BF-23 mit.** Jener Befund (aus B04) beschreibt den
Bestätigungstoken in `request.INFO: Matched route`-Zeilen — dieselbe Mechanik, anderer
Kanal. Der Processor maskiert `token=` in allen Kanälen und entschärft damit auch das.

**Das ist ein Grenzfall zu Regel 1** („was nicht im Auftrag steht, wird nicht gebaut").
Ich habe ihn so entschieden: Der Processor ist die Reparatur für BF-45, und dass er
nebenbei auf BF-23 wirkt, ist eine Eigenschaft derselben Zeilen — keine zusätzliche
Funktion. Ihn künstlich auf `accessId` zu beschränken wäre schlechter Code gewesen.
**BF-23 gilt damit nicht als geschlossen**; ob der Processor dort ausreicht, entscheidet
die QA zu B04, nicht dieser Bericht.

Zweiter Grenzfall: Der Schlüssel `nearby_stops.source` nennt auch die **Herkunft** der
Daten — das ist FB-04 der Spec und stand nicht im Auftrag. Der Satz ist entstanden, weil
BF-46 eine Klarstellung verlangt („ohne Aussage zur Barrierefreiheit"), und die braucht
einen Träger. Dass dabei die Quelle genannt wird, ist eine Zeile mehr im selben Satz.

## Tests

**Zwei umgedreht:** `testAk12AufrufTraegtKeineZeitvorgabe` →
`testAk12AufrufTraegtEineZeitvorgabe`, `testAk15FehlerprotokollEnthaeltDenSchluessel` →
`…EnthaeltDenSchluesselNicht`. Beide schlugen bei der ersten Ausführung nach der
Reparatur fehl — wie vorgesehen.

**Zwei neu:** `testBf45ProcessorMaskiertDenSchluesselInFremdenLogzeilen` (prüft auch,
dass der Rest der URL lesbar bleibt) und
`testBf45ProcessorLaesstGewoehnlicheZeilenUnveraendert`.

**BF-46 ist bewusst kein Test.** Er hängt an einer Aussage über die Wirklichkeit —
„liefert der Radius Treffer?" — gegen einen Fremddienst. Ein Test darauf wäre beim
nächsten Fahrplanwechsel rot, ohne dass sich im Code etwas geändert hätte. Der Nachweis
ist der Selbsttest oben.

**Suite:** 362 Tests, 1236 Assertions, 1 übersprungen, **0 Fehler** (vorher 360).

## Verifikation

| Schritt | Ergebnis |
|---|---|
| `php -l` auf jede geänderte Datei | fehlerfrei |
| `bin/console lint:yaml translations config` | gültig |
| `bin/console lint:twig` | gültig |
| `bin/console cache:clear` | Container baut, Processor als `monolog.processor` getaggt |
| Selbsttest BF-46 | 8 von 11 mit Haltestellen, „barrierefrei" verschwunden, Herkunftshinweis sichtbar |
| Selbsttest BF-44 | Rückkehr nach 0,3 s statt >30 s |
| Selbsttest BF-45 | 0 Klartext-Schlüssel im Log |
| `php bin/phpunit` | 362 grün |

## Übergabe

Status bleibt `building`. Nächster Aufruf: **`/sdd-qa B10`**.

Worauf die erneute Prüfung schauen sollte:

1. **Ob der Processor zu viel maskiert.** Er greift in **allen** Kanälen. Ein Log, in dem
   `password=` in einer harmlosen Zeile steht, verliert dort Information. Der Test deckt
   den Normalfall ab, nicht den Randfall.
2. **Ob 1000 m der richtige Wert ist.** Drei Restaurants zeigen weiterhin nichts. Ob das
   an ihrer Lage liegt oder der Radius immer noch zu klein ist, habe ich nicht geprüft.
3. **Ob der Satz „ohne Aussage zur Barrierefreiheit" an der richtigen Stelle steht.** Er
   erscheint jetzt unter jeder Haltestellenliste. Auf einer Seite, die sonst nur
   Barrierefreiheitsangaben macht, ist das eine ungewöhnliche Einschränkung — vielleicht
   eine, die besser einmal prominent als zwölfmal kleingedruckt stünde.
