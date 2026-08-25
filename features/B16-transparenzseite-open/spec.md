# B16 · Transparenzseite `/open` — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Eine öffentliche Seite, die dieselbe Offenlegung auf das Projekt anwendet, die es von
Restaurants verlangt: Plattformzahlen, Wirkung und Finanzen, jeweils mit Verlauf,
Einordnung und Druckansicht. Sie ist zugleich die Antwort auf die Frage, die vor jedem
Fördergespräch gestellt wird.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B18 | rekonstruiert | Finanzposten und Snapshots |

## User Stories

- **US-01** · Als Besucher möchte ich sehen, wie weit die Abdeckung Luxemburgs ist.
- **US-02** · Als Fördergeber möchte ich Einnahmen und Ausgaben nachvollziehen.
- **US-03** · Als Fördergeber möchte ich die Seite als PDF speichern.
- **US-04** · Als Besucher möchte ich erkennen, wie alt die Zahlen sind.

## Nicht im Scope

- Maschinenlesbare Ausgabe → B17
- Pflege der Zahlen → B18

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher ruft `/{locale}/open` auf, wenn die Seite lädt,
  dann sieht er Hero, Plattform, Wirkung, Finanzen, Verlauf und Daten.
- **AK-02** · Angenommen, jemand ruft `/open` **ohne** Sprache auf, wenn die Anfrage
  durchläuft, dann leitet `app_open_redirect` auf die luxemburgische Fassung.
- **AK-03** · Angenommen, die Startzahl im Hero wird betrachtet, wenn sie geprüft wird,
  dann ist es die Zahl der Restaurants — **genau eine Leitzahl pro Seite**.
- **AK-04** · Angenommen, weniger als **zwei** Snapshots existieren, wenn die Seite
  lädt, dann werden die Verlaufsgrafiken **nicht** gezeigt — eine Linie aus einem Punkt
  wäre zwangsläufig waagerecht und sagte nichts aus.
- **AK-05** · Angenommen, **kein** Snapshot existiert, wenn die Seite lädt, dann gibt es
  **keine** Veränderungsangaben — eine Veränderung gegen einen unbekannten Ausgangswert
  wäre erfunden.
- **AK-06** · Angenommen, ein Snapshot existiert, wenn eine Veränderung angezeigt wird,
  dann bezieht sie sich auf den **Monat dieses Snapshots**, nicht auf „vor 30 Tagen",
  und der Bezugsmonat steht dabei.
- **AK-07** · Angenommen, das erste Kalenderquartal mit einer Einnahme ist noch nicht
  abgelaufen, wenn die Seite lädt, dann erscheinen **keine** Einnahmezahlen — und sie
  stehen auch nicht im Ergebnis-Array, sind also über `/open.json` nicht abrufbar.
- **AK-08** · Angenommen, die Finanzdaten sind älter als **60 Tage**, wenn die Seite
  lädt, dann wechselt der „Stand vom"-Hinweis von grauem Kleingedruckten in einen
  `bg-amber-50`-Kasten.
- **AK-09** · Angenommen, ein Restaurant hat eine Stadt, die keiner bekannten Gemeinde
  entspricht, wenn die Abdeckung berechnet wird, dann wird es als **unzugeordnet**
  ausgewiesen — es wird **nicht geraten**.
- **AK-10** · Angenommen, ein Diagramm wird betrachtet, wenn seine Barrierefreiheit
  geprüft wird, dann sind die Balken `aria-hidden` und die Zahl daneben trägt die
  Aussage; zu jeder Grafik gibt es eine Tabellenentsprechung.
- **AK-11** · Angenommen, die Punkteverteilung wird betrachtet, wenn die Farben geprüft
  werden, dann hat die Serie **eine** Farbe — die Position trägt die Ordnung, nicht die
  Färbung.
- **AK-12** · Angenommen, Einnahmen und Ausgaben werden dargestellt, wenn die Farben
  geprüft werden, dann sind Ausgaben cyan und Einnahmen violett — **kein Bernstein**,
  das ist eine Warnfarbe und ließe Betriebskosten wie ein Problem aussehen.
- **AK-13** · Angenommen, die Seite wird gedruckt oder als PDF gespeichert, wenn das
  Ergebnis betrachtet wird, dann fehlen Kopfzeile, Fußzeile, Bottom-Navigation und
  Cookie-Banner; `<details>` sind aufgeklappt, Diagramme werden nicht umbrochen und die
  Balkenfarben bleiben erhalten (`print-color-adjust: exact`).
- **AK-14** · Angenommen, eine Zahl oder ein Betrag wird angezeigt, wenn die Sprache
  gewechselt wird, dann folgt die Schreibweise der Sprache — über `format_number` und
  `format_currency`, nicht über eine feste deutsche Notation.
- **AK-15** · Angenommen, die Verlaufslinie wird betrachtet, wenn nach Punktmarkierungen
  gesucht wird, dann gibt es **keine** — `preserveAspectRatio="none"` streckt das
  Koordinatensystem und machte aus Kreisen Ellipsen; der aktuelle Wert steht als Zahl
  über der Grafik.
- **AK-16** · Angenommen, die Kennzahlen werden abgerufen, wenn die Zwischenspeicherung
  geprüft wird, dann liegen sie im eigenen Pool `cache.open_stats` mit einer Stunde
  Laufzeit.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-17** ⚠ · Angenommen, die Abdeckung wird berechnet, wenn ein unverifiziertes
  Restaurant in der Datenbank steht, dann zählt es mit.
  *(Dieselbe Eigenschaft wie B17/AK-13 und B23/AK-21: Die Kennzahlen der
  Transparenzseite unterscheiden nicht zwischen geprüften und ungeprüften Einträgen —
  nur die Zahl „davon verifiziert" tut es. Über die API eingeschleuste Einträge heben
  damit die ausgewiesene Abdeckung.)*

- **AK-18** ⚠ · Angenommen, die durchschnittliche Punktzahl wird betrachtet, wenn ein
  neues, noch unvollständig erfasstes Restaurant hinzukommt, dann **sinkt** sie.
  *(So verhält sich der Code heute: `AccessibilityScore` wertet acht Merkmale
  gleichgewichtet, nicht erfasste zählen als nicht erfüllt — „der Wert misst
  *dokumentierte* Barrierefreiheit". Das ist bewusst so, hat aber die Folge, dass die
  Leitkennzahl der Seite Wachstum bestraft. Im PRD steht das bereits als Risiko 2.)*

### Datenschutz und Missbrauchsschutz

- **AK-19** · Angenommen, die Seite wird betrachtet, wenn nach personenbezogenen Daten
  gesucht wird, dann enthält sie **keine** — nur Aggregate.
- **AK-20** · Angenommen, die Seite wird aufgerufen, wenn geprüft wird, ob eine
  Anmeldung nötig ist, dann nicht.
- **AK-21** · Angenommen, Einzelposten der Finanzen werden gesucht, wenn die Seite
  gelesen wird, dann sind sie nicht darauf — nur Summen je Kategorie.

## Edge Cases

- **EC-01** · Leere Datenbank → alle Zahlen 0, keine Grafiken, keine Deltas.
- **EC-02** · `end($trend)` liefert den **letzten** Eintrag der Trendliste als
  Bezugspunkt der Deltas.
- **EC-03** · Der Cache-Pool ist in `when@test` auf `cache.adapter.array` gesetzt,
  sonst überlebten Werte zwischen Tests.
- **EC-04** · Die Säulen des Histogramms reichen auf **85 %** statt 100 % — die oberen
  15 % sind der Streifen fürs Wertlabel, sonst liefe es aus dem Container.

## Fehlbestand

- **FB-01 · Kein Hinweis, dass ungeprüfte Einträge mitzählen.** Siehe AK-17.
- **FB-02 · Kein Rate Limit und keine Cache-Header auf der HTML-Seite.** Die
  Datenendpunkte (B17) setzen `public, max-age`; die gerenderte Seite nicht — obwohl
  sie dieselben Aggregatabfragen auslöst (gemildert durch `cache.open_stats`).
- **FB-03 · Keine Angabe, wann die Plattformzahlen zuletzt berechnet wurden.** Der
  „Stand vom"-Hinweis betrifft nur die **Finanzdaten**; für Plattform und Wirkung fehlt
  eine Altersangabe, obwohl sie bis zu eine Stunde alt sein können.
- **FB-04 · Keine Methodenbeschreibung zur Punktzahl auf der Seite selbst.** Wie die
  0–10 zustande kommen, steht in `docs/` und im Code — für einen Fördergeber, der die
  Seite als Beleg liest, wäre es dort nötig.

## Offene Fragen

- **OF-01** · Soll die Punktzahl nicht erfasste Merkmale weiterhin als nicht erfüllt
  werten (AK-18)? Das PRD führt es als Risiko; eine Alternative wäre, den Nenner auf
  die erfassten Merkmale zu begrenzen und die Erfassungsquote separat auszuweisen. —
  Betreiber
  **Entschieden 2026-08-25:** Teils (BF-67, 2026-08-25). Innerhalb eines bewerteten Hauses zählt nicht Erfasstes weiterhin als nicht erfüllt. Ein Haus, über das GAR NICHTS erhoben wurde, bekommt aber keine Punktzahl mehr, sondern `null` — und erscheint als eigene Zahl „noch nicht bewertet".

- **OF-02** · Sollen die Kennzahlen auf verifizierte Einträge beschränkt werden
  (AK-17)? Hängt an derselben Entscheidung wie B17/OF-01 und B23/OF-01. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Eigener Namensraum `App\Open\` | statt `App\Service\` | der Bereich hat genug eigene Begriffe, um zusammenzubleiben |
| 2 | Rückgaben als reine Skalar-Arrays | keine Enums, keine Entities | dieselbe Struktur geht durch Cache, Twig, `/open.json` und Snapshot — Objekte darin ließen die vier Ausgaben auseinanderlaufen |
| 3 | Eigener Cache-Pool | `cache.open_stats` | ein `clear()` nach einer Admin-Änderung soll nicht den halben Anwendungscache mitnehmen |
| 4 | Quartalssperre **strukturell** | Zahlen fehlen im Array, nicht nur im Template | lägen sie darin und wären nur verborgen, wären sie über `/open.json` abrufbar |
| 5 | Deltas nur gegen einen Snapshot | ja | nur er ist ein nachprüfbarer Stand |
| 6 | Grafiken erst ab zwei Punkten | ja | ein Punkt ist kein Verlauf |
| 7 | Eine Farbe je Serie | ja | die frühere Ampel kodierte die Balkenlänge ein zweites Mal; Bernstein lag bei 1,49:1 Kontrast |
| 8 | Ausgaben cyan, Einnahmen violett | die Marken-Hues | geprüft: ΔE 26,4 normal / 13,6 Deuteranopie, beide > 3:1 gegen Weiß |
| 9 | Sparkline als reines SVG | ohne Bibliothek | keine neue Abhängigkeit; der Preis sind fehlende Punktmarkierungen (AK-15) |
| 10 | Unbekannte Gemeinden nicht raten | ausweisen | ein geratener Kanton wäre eine erfundene Zahl auf einer Transparenzseite |
