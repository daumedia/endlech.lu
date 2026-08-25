# B05 · Restaurantsuche, Filter & Sortierung — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — zwei niedrige Befunde, keiner blockierend.

24 von 24 Kriterien bestanden. Das ist das gründlichste Filterwerk im Projekt, und es
hält jeder Gegenprobe stand: **Alle elf Ja/Nein-Filter stimmen mit der Datenbank
überein**, die Sprachverknüpfung ist tatsächlich ein UND (de=10, fr=10, **beide=9** —
genau die Zahl aus der Datenbank), die Küchenverknüpfung ein ODER (2 + 1 = 3), und die
Kombination über Filterarten ein UND (wheelchair 7 ∩ vegan 3 = **3**).

Auch die Nachtschicht-Logik ist da, wo sie hingehört: drei Zweige im Repository
(`RestaurantRepository.php:76–80`) plus `distinct()` in Zeile 82 — und ein Haus mit zwei
Zeitfenstern erschien in der Liste genau **einmal**.

Die beiden Befunde sind Randfälle: `?city=%` hebelt den Ortsfilter aus, und `?page=99999`
liefert 200 statt 404.

Nächster Aufruf: **`/sdd-erfassen B06`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Sortierung und Blättern

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | ohne Parameter: `Pizzeria Bella Vista · Sushi Zen · Le Jardin Brasserie · Green Bowl · Wäinhaus am Markt · Burger & Co.` — identisch mit `ORDER BY rating DESC, id ASC LIMIT 6` |
| AK-02 | ✅ bestanden | `?sort=name` → alphabetisch ab `Brasserie du Grund`; `?sort=newest` → `Café Nordstad · Umami Corner · …` |
| AK-03 | ✅ bestanden | `?sort=unsinn` → identische Reihenfolge wie ohne Parameter |
| AK-04 | ✅ bestanden | `?page=2` → die restlichen fünf (7–11 von 11) |
| AK-05 | ✅ bestanden | `?page=0` und `?page=-3` → beide wie Seite 1 |

### Filter

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-06** | ✅ bestanden | alle elf gegen die Datenbank gezählt — siehe Tabelle unten |
| AK-07 | ✅ bestanden | `?city=Strassen` → `Sushi Zen`, identisch mit `LIKE '%Strassen%'`; Teiltreffer `?city=stra` → derselbe Treffer |
| **AK-08** | ✅ bestanden | `cuisine[]=637` → 2 · `cuisine[]=640` → 1 · beide → **3**; DB mit einer der beiden: **3** (ODER bestätigt) |
| **AK-09** | ✅ bestanden | über alle Seiten gezählt: `lang_de=1` → 10 · `lang_fr=1` → 10 · **beide → 9**. DB: de=10, fr=10, **beide=9** (UND bestätigt) |
| **AK-10** | ✅ bestanden | drei Zweige in `RestaurantRepository.php:76–80` — normal, Nachtschicht heute, Nachtschicht von gestern. Unit-Test deckt es ab: *„Freitag (5): 18:00–01:00 … 2026-06-20 00:30 ist Samstag früh – die Freitag-Nachtschicht läuft noch"* |
| **AK-11** | ✅ bestanden | `wheelchair=1` → 7 · `vegan=1` → 3 · zusammen → **3**; DB beides: **3** |
| **AK-12** | ✅ bestanden | Haus mit zwei Zeitfenstern (11:00–14:30 und 18:00–23:00) bei `?open=1` **genau einmal** in der Liste; `distinct()` in Zeile 82 |
| AK-13 | ✅ bestanden | `?city=GibtsNicht` → HTTP **200**, Text *„0 Ergebnisse gefunden"* |
| **AK-14** | ✅ bestanden | **20** Küchen im Selektor (= alle in der Datenbank), alphabetisch ab `Amerikanisch`; **sechs** Sprachen `de, en, fr, lu, other, pt` = alle Fälle des `Language`-Enums |

**AK-06 im Einzelnen** — Seite gegen Datenbank:

| Filter | Treffer | DB |
|---|---|---|
| `verified` | 3 | 3 |
| `wheelchair` | 7 (6 + 1 auf Seite 2) | 7 |
| `toilet` | 4 | 4 |
| `dogs` | 6 | 6 |
| `lighting` | 6 | 6 |
| `changing_table` | 5 | 5 |
| `disabled_parking` | 5 | 5 |
| `vegan` | 3 | 3 |
| `vegetarian` | 7 (6 + 1) | 7 |
| `halal` | 2 | 2 |
| `open` | zeitabhängig | siehe AK-10/AK-12 |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-18 | ✅ bestanden | `/de/restaurants` ohne Anmeldung → **200** |
| **AK-19** | ✅ bestanden | `?city=' OR 1=1 --` → 200, `?sort=name;DROP` → 200, `?cuisine[]=abc` → 200, `?lang_xx=1` → 200; danach **11 Restaurants unverändert** in der Datenbank. Keine 500er, kein Durchgriff |
| AK-20 | ✅ bestanden | **0** E-Mail-Adressen und **0** Telefonnummern in der Liste — Kontaktdaten stehen nur auf der Detailseite |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-15** ⚠ | ✅ bestätigt | 3 verifiziert, **8 nicht** — alle gleichberechtigt in der Liste. `isVerified` kommt im Repository nur als optionaler Filter vor |
| **AK-16** ⚠ | ✅ bestätigt | `?page=99999` → HTTP **200** mit leerer Liste → BF-60 |
| **AK-17** ⚠ | ✅ bestätigt | `?city=%` → 6 Treffer (statt 0), `?city=_` → 6 Treffer; zum Vergleich `?city=Esch` → 1 → BF-59 |

## Fehler

### BF-59 · `%` und `_` hebeln den Ortsfilter aus — niedrig

**Betrifft:** AK-17

**Reproduktion:**

| Adresse | Treffer |
|---|---|
| `?city=Esch` | 1 |
| `?city=%` | **6** (die volle erste Seite) |
| `?city=_` | **6** |

Der Wert geht als `'%'.$wert.'%'` in eine LIKE-Bedingung, ohne dass `%` und `_` maskiert
werden. **Der Parameter ist gebunden** — eine SQL-Injection ist damit ausgeschlossen, und
das habe ich mit vier Angriffsvarianten gegengeprüft (AK-19). Aushebeln lässt sich nur
die *Filterwirkung*.

**Warum das trotzdem zählt und warum nur *niedrig*:** Schaden entsteht keiner — wer alle
Restaurants sehen will, ruft die Liste ohne Filter auf. Was auffällt, ist die
Inkonsequenz: Jeder andere Filter dieses Features prüft seine Eingabe (`sort` gegen eine
feste Liste, `cuisine` über `intval`, `lang` gegen `Language::cases()`), nur der
Freitext nicht.

**Vorschlag:** `addcslashes($wert, '%_\\')` vor dem Einsetzen. Eine Zeile, und der
Filter tut, was er verspricht.

### BF-60 · Seiten jenseits des Bestands liefern 200 — niedrig

**Betrifft:** AK-16

**Reproduktion:** `?page=99999` → **HTTP 200** mit leerer Liste.

**Erwartet:** 404 für eine Seite, die es nicht gibt
**Folge:** Für Suchmaschinen sind das beliebig viele indexierbare Leerseiten. Bei einer
Plattform, deren Sichtbarkeit über die Suche läuft, ist das kein Detail — aber auch kein
Fehler mit Betroffenen.

**Vorschlag:** Ist `page > totalPages` und der Bestand nicht leer, dann 404. Alternativ
ein `<meta name="robots" content="noindex">` auf leeren Ergebnisseiten — das ist der
kleinere Eingriff und löst das eigentliche Problem.

## Hinweise ohne Fehlerstatus

- **AK-15 (unverifizierte gleichberechtigt) bekommt keine eigene Nummer.** Es ist
  dieselbe Frage wie BF-41 aus B17, dort ausführlich behandelt und als Produktfrage
  eingestuft: Bei 8 von 11 unverifizierten Häusern wäre eine gefilterte Liste mit drei
  Einträgen die schlechtere Antwort. **Und der Verschärfer ist seit heute weg:** Die
  Spec verweist auf B23/AK-21 („jeder angemeldete Nutzer kann über die API ungeprüft
  anlegen") — das ist seit BF-24 nicht mehr möglich. Was in der Liste steht, hat ein
  Admin gesehen.
- **`?open=1` ist zeitabhängig** und deshalb in der Tabelle oben ohne feste Zahl. Zum
  Messzeitpunkt (18:34 Uhr) erschienen 5 Häuser, darunter das Testhaus im Fenster
  18:00–23:00.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **SQL-Injection** | vier Varianten (`' OR 1=1 --`, `sort=name;DROP`, `cuisine[]=abc`, `lang_xx=1`) → alle 200, Datenbank unverändert |
| **Filterwirkung aushebeln** | über `%` und `_` **möglich** → BF-59 |
| **Ungültige Seitenzahlen** | `0` und `-3` fallen auf Seite 1 zurück; `99999` → 200 statt 404 → BF-60 |
| **Personenbezogene Daten** | keine in der Liste |
| **Anmeldung** | nicht erforderlich — bewusst, das ist der öffentliche Kern |
| **Testsuite** | 364 Tests, 0 Fehler |

## Neue Tests

Keine. `RestaurantRepositoryTest` deckt laut `CLAUDE.md` „alle `findPaginated`-Filter und
`sort`-Reihenfolgen" ab, und die Messungen oben bestätigen das Verhalten gegen die
Datenbank. Die beiden Befunde sind Randfälle:

- **BF-59** ließe sich testen, aber der Test hielte das unerwünschte Verhalten fest.
- **BF-60** ist eine Statuscode-Entscheidung, die mit der Reparatur kommt.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B06`. B05 geht auf `approved`; BF-59 und BF-60 stehen in
`features/befunde.md`.

Beide sind Einzeiler. BF-59 ist der lohnendere: Ein Filter, der bei einem bestimmten
Zeichen etwas anderes tut als angekündigt, ist die Sorte Verhalten, die man erst bemerkt,
wenn sich jemand darüber wundert.
