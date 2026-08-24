# B12 · Startseite — Testbericht, zweiter Durchlauf

Stand: 2026-08-24 · nach der Reparatur von BF-64
Vorstufe: `building` · Branch `fix/b04-profil-qa` · Commit `f34ea12`

> Der erste Durchlauf steht in `qa-report.md`.

## Fazit

**Production-ready: ja** — kein offener Befund.

15 von 15 Kriterien bestanden. AK-04 ist geschlossen.

Nächster Aufruf: **`/sdd-erfassen B13`**.

## Die reparierte Stelle

| | erster Durchlauf | dieser |
|---|---|---|
| Karten auf der Startseite | **1** | **6** |
| `findTopRated(6)` | 1 Entity | **6** |
| `findTopRated(3)` | 1 Entity | **3** |
| `findTopRated(20)` / `(100)` | 2 / 7 | **11 / 11** (alle) |

Die Reihenfolge stimmt weiterhin: `Pizzeria Bella Vista (9.8) · Sushi Zen (9.4) ·
Le Jardin Brasserie (9.1) · Green Bowl (9) · Wäinhaus am Markt (8.8) · Burger & Co.
(8.5)`.

**N+1 bleibt vermieden** — die Öffnungszeiten sind weiterhin vorgeladen. Das war die
Frage bei dieser Reparatur: `Paginator` mit `$fetchJoinCollection` erhält beides, die
Begrenzung **und** das Vorladen.

## Regression

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ | `GET /` → 302 nach `/lb/` |
| AK-02 | ✅ | „So funktioniert's", „Top bewertete Restaurants", „Warum Endlech.lu?" alle vorhanden |
| AK-03 | ✅ | Zähler „11 Restaurants in Luxemburg" |
| **AK-04** | ✅ | 6 Karten, Reihenfolge deckt sich mit `ORDER BY rating DESC, name ASC LIMIT 6` |
| AK-05 | ✅ | Öffnungszeiten vorgeladen |
| AK-06 | ✅ | ohne Anmeldung 200 |
| AK-07 ⚠ | unverändert bestätigt | ist BF-41, keine eigene Nummer |
| AK-08, AK-09 | ✅ | unverändert |
| EC-01 bis EC-03 | ✅ | unverändert |

## Der Test, der grün war

Die interessanteste Erkenntnis dieses Durchgangs steht im ersten Bericht noch nicht:
**Es gab einen Test**, `testFindTopRatedIsLimitedAndDescending`, und er lief grün, während
die Startseite eine Karte zeigte.

```php
self::assertLessThanOrEqual(6, \count($top));   // bei 1 erfüllt
```

„Höchstens sechs" ist auch bei einem Ergebnis wahr. Der Test prüft jetzt
`assertCount(min(6, $bestand), $top)`, dazu ein zweiter für die Grenzfälle 1, 3, 6 und
über dem Bestand.

**Beide schlagen ohne die Reparatur fehl** — mit `git stash` gegengeprüft:
```
ohne Reparatur:  Tests: 2, Failures: 2
mit Reparatur:   OK (2 tests, 15 assertions)
```

Das ist der Grund, warum eine Zählprüfung `assertCount` heißen sollte und nicht
`assertLessThanOrEqual`. Der Hinweis steht jetzt in `CLAUDE.md`.

**Suite: 365 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B13`. B12 geht auf `approved`; BF-64 wandert in `features/befunde.md`
nach *Behoben*.
