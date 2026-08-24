# B16 · Transparenzseite `/open` — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein mittlerer Befund.

25 von 25 Kriterien bestanden. Das ist die sorgfältigste Seite des Projekts, und sie
hält jeder Gegenprobe stand. Vier Beispiele, die man nur mit einer Messung glauben kann:

| Prüfung | Ergebnis |
|---|---|
| Verlaufsgrafiken bei 0 / 1 / **2** Snapshots | 11 / 11 / **13** SVG — die beiden Linien kommen erst ab zwei dazu |
| Veränderungsangaben ohne Snapshot | **0** — nichts wird gegen einen unbekannten Wert gerechnet |
| Veralterungshinweis bei 23 / **90** Tagen | 0 / **1** `bg-amber-50` |
| Zahlenformat de / **en** / fr | `27,3 %` / **`27.3 %`** / `27,3 %` |

**Der Befund** ist die Leitkennzahl selbst: Ein neu erfasstes Haus ohne Merkmale hebt die
ausgewiesene Abdeckung **und** senkt die Durchschnittspunktzahl. Gemessen:
`restaurants 11 → 12`, `communesCovered 8 → 9`, `averageScore **5,09 → 4,67**`.

Nächster Aufruf: **`/sdd-erfassen B24`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Aufbau und Zahlen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Hero, Plattform, Wirkung, Finanzen, Verlauf, Daten — alle sechs Abschnitte |
| AK-02 | ✅ bestanden | `/open` → **302** nach `/lb/open` |
| **AK-03** | ✅ bestanden | Hero: *„**11** Restaurants auf der Plattform · davon 3 vom Team vor Ort geprüft · 8 Gemeinden in 5 Kantonen"* — eine Leitzahl, der Rest als Beiwerk |
| **AK-04** | ✅ bestanden | 0 Snapshots → **11** SVG · 1 Snapshot → **11** · 2 Snapshots → **13**. Die Verlaufsgrafiken erscheinen genau ab zwei |
| **AK-05** | ✅ bestanden | ohne Snapshot: **0** Veränderungsangaben im Text, kein „seit"/„gegenüber" |
| **AK-06** | ✅ bestanden | mit einem Snapshot für 2026-06: *„seit 2026-06"* — der Bezugsmonat steht dabei |

### Finanzen

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-07** | ✅ bestanden | Einnahmen ab 2026-08-01 (Q3 läuft noch) → *„Einnahmen — Noch nicht veröffentlicht. Wir zeigen Einnahmen erst, wenn ein vollständiges Quartal vorliegt."*; „650" kommt auf der Seite **nicht** vor; `/open.json`: `incomeVisible=False`, `totalIncome=None`, `incomeVisibleFrom=2026-10-01` |
| **AK-08** | ✅ bestanden | frische Daten (23 Tage) → **0** × `bg-amber-50`; auf 90 Tage zurückdatiert → **1** × `bg-amber-50` |

### Darstellung

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-09** | ✅ bestanden | Stadt auf „Absurdistan" gesetzt → `/open.json`: `unassigned: 1`, `communesCovered` unverändert bei 8. Die Kantontabelle sagt: *„Alle zwölf Kantone stehen in der Liste, auch die ohne einen einzigen Eintrag. Die weißen Flecken sind die ehrlichere Hälfte der Aussage."* |
| **AK-10** | ✅ bestanden | **47** × `aria-hidden="true"`, **3** `<details>`, **3** `<table>`, `id="canton-coverage"` vorhanden |
| **AK-11 / AK-12** | ✅ bestanden | Verwendete Farbfamilien in Diagrammen: **nur `cyan` und `purple`**. **Kein `amber`** |
| **AK-13** | ✅ bestanden | **5** × `print:hidden` in `base.html.twig`; im `@media print`-Block: `print-color-adjust: exact`, `background-image: none !important`, und ein Kommentar *„Zugeklappte `<details>` beim Drucken öffnen"* mit der zugehörigen Regel |
| **AK-14** | ✅ bestanden | `de: 27,3 %` · `en: 27.3 %` · `fr: 27,3 %` — die Schreibweise folgt der Sprache |
| **AK-15** | ✅ bestanden | **0** `<circle>` in den SVGs; **2** × `preserveAspectRatio="none"`, **4** × `vector-effect` |
| AK-16 | ✅ bestanden | `cache.open_stats` als eigener Filesystem-Pool mit `default_lifetime: 3600`; in `when@test` auf `cache.adapter.array` |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-19 | ✅ bestanden | **0** E-Mail-Adressen, **0** Telefonnummern im Hauptteil — nur Aggregate |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-17 / AK-18** ⚠ | ✅ bestätigt | ein unverifiziertes Haus ohne Merkmale angelegt: `restaurants 11 → 12`, `communesCovered 8 → 9`, `averageScore` **5,09 → 4,67** → BF-67 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Ohne Snapshots keine Grafiken und keine Deltas (AK-04, AK-05); der leere Restaurantbestand ist in B12/EC-01 geprüft |
| EC-02 | ✅ bestanden | belegt durch AK-06: Der Bezugspunkt ist der Monat des letzten Snapshots |
| EC-03 | ✅ bestanden | `cache.yaml:36–38`: `when@test` → `cache.adapter.array` |
| EC-04 | ✅ bestanden | Die Säulen des Histogramms haben Wertlabels über sich, keine Überläufe im Markup |

## Fehler

### BF-67 · Die Leitkennzahl bestraft Wachstum — mittel

**Betrifft:** AK-17 und AK-18

**Reproduktion:** Ein Restaurant anlegen, über das nichts bekannt ist (alle acht Merkmale
auf `0`, unverifiziert, Stadt Wiltz):

| | vorher | nachher |
|---|---|---|
| `restaurants` | 11 | **12** |
| `communesCovered` | 8 | **9** |
| `averageScore` | **5,09** | **4,67** |

**Zwei Wirkungen in entgegengesetzte Richtungen**, beide auf derselben Seite:

1. **Die Abdeckung steigt.** Ein Eintrag, den niemand geprüft hat, hebt die ausgewiesene
   Gemeindeabdeckung — die Zahl, mit der das Projekt seine Reichweite belegt.
2. **Die Punktzahl fällt.** `AccessibilityScore` (`CRITERIA_COUNT = 8`, `MAX = 10`) zählt
   `array_filter($flags)` — nicht erfasste Merkmale sind `false` und damit „nicht
   erfüllt". Ein Haus ohne Angaben bekommt 0 von 10.

**Warum das mehr ist als eine Definitionsfrage:** `/open` existiert, um gegenüber
Fördergebern zu belegen, dass das Projekt wirkt. Die beiden Leitzahlen sagen dabei
Gegensätzliches — mehr Häuser heißt breitere Abdeckung **und** schlechtere
Barrierefreiheit. Wer die Kurven nebeneinander sieht, liest daraus, dass die Plattform
wächst und die Qualität sinkt. Tatsächlich heißt es nur: Es wurde noch nicht gemessen.

Das steht als Risiko 2 im PRD. Was dieser Bericht hinzufügt, sind die Zahlen und die
Feststellung, dass beide Kennzahlen **auf derselben Seite** in gegenläufige Richtungen
zeigen.

**Verwandt mit BF-49** (B11: „Weiß nicht" wird bei der Genehmigung zu „Nein") — dort
entsteht die Ursache, hier wird sie sichtbar. Eine Reparatur an einer der beiden Stellen
allein reicht nicht: Selbst wenn `Restaurant` dreiwertig würde, müsste `AccessibilityScore`
entscheiden, was mit „unbekannt" geschieht.

**Vorschlag:** Ein Restaurant mit **null** erfassten Merkmalen bekommt **keine Punktzahl**
statt einer Null — und fällt aus dem Durchschnitt heraus. Auf der Seite erscheint es
stattdessen in einer eigenen Zahl: *„3 Häuser noch nicht bewertet"*. Das ist ehrlicher
als beides — es versteckt nichts und rechnet nichts schön.

Nachrangig, aber im selben Zug zu klären: ob `communesCovered` unverifizierte Häuser
mitzählen soll. Dafür spricht, dass die Erfassung eines Betriebs schon eine Leistung ist;
dagegen, dass „Abdeckung" nach geprüfter Abdeckung klingt.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Quartalssperre** | strukturell: die Beträge fehlen im Ergebnis-Array, nicht nur im Template (AK-07, beidseitig geprüft) |
| **Erfundene Veränderungen** | keine ohne Snapshot |
| **Geratene Zuordnungen** | keine — unbekannte Städte werden als `unassigned` ausgewiesen |
| **Personenbezogene Daten** | keine |
| **Barrierefreiheit der Diagramme** | 47 `aria-hidden`, 3 Tabellenentsprechungen |
| **Farbkodierung** | eine Farbe je Serie, kein Bernstein |
| **Testsuite** | 365 Tests, 0 Fehler |

## Ein eigener Messfehler, der hier stehen bleibt

Bei der Prüfung von AK-08 habe ich die Finanzposten auf 90 Tage zurückdatiert, um den
Veralterungshinweis auszulösen. Danach zeigte die Seite **„Einnahmen 650,00 €"** — was
zunächst wie ein durchgefallenes AK-07 aussah.

Es war die Folge meiner eigenen Änderung: Mit dem Rückdatieren war das erste Quartal mit
einer Einnahme (Q2 2026) abgelaufen, und die Sperre **durfte** nicht mehr greifen. Im
Fixture-Zustand (Einnahmen ab 2026-08-01, Q3 läuft) erscheint die Zahl nicht.

Das steht hier, weil es zeigt, wie leicht sich in dieser Prüfung ein falscher Befund
ergibt: Die Sperre ist datumsabhängig, und wer die Daten zum Testen verschiebt,
verschiebt die Sperre mit.

## Neue Tests

Keine. Die vorhandenen Integrationstests (`OpenStatsService`: Abdeckung,
Punkteverteilung, Quartalssperre, Cache-Invalidierung) decken die Rechenwege ab, und
`OpenControllerTest` die Seite.

BF-67 ist keine Regression, sondern eine Definitionsfrage — ein Test darauf würde die
heutige Definition festschreiben, und genau die steht zur Diskussion.

**Suite: 365 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B24`. B16 geht auf `approved`; BF-67 steht in `features/befunde.md`.

BF-67 gehört zusammen mit BF-49 gebaut — sie sind Ursache und Wirkung derselben Sache,
und eine Reparatur an nur einer Stelle löst das Problem nicht.
