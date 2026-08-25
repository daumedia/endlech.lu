# B06 · Restaurant-Detailseite — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — kein eigener neuer Befund.

20 von 20 Kriterien bestanden, 3 von 3 Edge Cases. Das ist das erste Feature dieser
Prüfreihe ohne eigenen Befund, und der Grund ist die Sorgfalt bei den Maßangaben:

| Wert | Anzeige |
|---|---|
| 95 cm | grüner Kasten, „95 cm" |
| 80 cm | „80 cm · **unter 90 cm**" |
| `NULL` | „**Nicht ausgemessen**" |

Genau die Unterscheidung, die dieses Projekt an anderen Stellen verliert (BF-49: „weiß
nicht" wird zu „nein"), ist hier richtig gelöst — und in beide Richtungen nachgemessen.

**Zwei der ⚠-Kriterien haben sich durch die heutigen Reparaturen erledigt:** AK-13 (der
Besucher wartet auf einen hängenden Nahverkehrsdienst) ist mit BF-44 zu, und der
Verschärfer von AK-14 (über die API eingeschleuste Häuser bekommen sofort eine
indexierbare Seite) ist mit BF-24 weg.

Nächster Aufruf: **`/sdd-erfassen B07`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Darstellung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `🍕 Pizzeria Bella Vista · Luxembourg-Ville · Italienisch, Pizza · Geöffnet · Verifiziertes Lokal · ⭐ 9.8/10 · 🇱🇺 🇩🇪 🇫🇷 🇬🇧` |
| AK-02 | ✅ bestanden | alle acht Merkmale im Markup: Stufenloser Eingang, WC, Assistenzhunde, Beleuchtung, Wickeltisch, Behindertenparkplatz, Türbreite, Tischabstand |
| **AK-03** | ✅ bestanden | 95 cm → `bg-green-50 text-green-700`, „95 cm"; **80 cm → „80 cm · unter 90 cm"** |
| **AK-04** | ✅ bestanden | `door_width_cm = NULL` → „**Nicht ausgemessen**" — nicht „erfüllt nicht" |
| **AK-05** | ✅ bestanden | `Montag (Heute) 12:00 – 14:30 · 18:00 – 22:00 · Dienstag 10:00 – 20:00 · Mittwoch Geschlossen` — zwei Fenster mit `·` verbunden, heutiger Tag hervorgehoben |
| **AK-06** | ✅ bestanden | um 18:38 im Fenster 18:00–22:00 → „**Geöffnet**"; Gegenprobe mit Fenster 08:00–10:00 → „**Geschlossen · Öffnet Dienstag um 09:00**" |
| AK-07 | ✅ bestanden | drei Bilder in `sort_order`-Reihenfolge (`qa1, qa2, qa3`), `glightbox` eingebunden, alt-Texte `Erstes, Zweites, Drittes` |
| AK-08 | ✅ bestanden | 2 × `href="tel:`, 2 × `href="mailto:`, 3 × Website-Link, Instagram und Facebook vorhanden |
| AK-09 | ✅ bestanden | `uber-eats.svg` für die Markenplattform; `website` und `phone` als generische Optionen ohne Logo |
| AK-10 | ✅ bestanden | Nahverkehrsblock erscheint mit Haltestellen; Ladezeit **0,54 s** |

### Technik und Zugriff

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ✅ bestanden | `/999999` → **404** · `/abc` → **404** |
| **AK-12** | ✅ bestanden | `RestaurantController.php:77` und `OpeningHoursService.php:9` setzen `Europe/Luxembourg` ausdrücklich. **Die Serverzeitzone ist `UTC`** — der Unterschied ist real, und der Code behandelt ihn |
| AK-17 | ✅ bestanden | ohne Anmeldung → **200** |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-15 | ✅ bestanden | `mailto:hello@burgerandco.lu` steht im Klartext im Quelltext, ohne Verschleierung — bewusste Eigenschaft, die Kontaktdaten sind der Zweck der Seite |
| AK-16 | ✅ bestanden | `submittedBy` auf einen Nutzer gesetzt → weder Name noch Adresse erscheinen auf der Seite |

### Fragwürdiges Verhalten

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-13** ⚠ | ✅ **erledigt** | `PublicTransportService.php:49–50`: `'timeout' => 3`, `'max_duration' => 5` — seit der BF-44-Reparatur von heute. Der Besucher wartet höchstens drei Sekunden |
| **AK-14** ⚠ | ✅ bestätigt | Unverifiziertes Haus: HTTP **200**, kein Abzeichen, **2 E-Mail- und 2 Telefonlinks im Klartext**, kein `noindex`. Siehe unten |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| **EC-01** | ✅ bestanden | Haus ohne Bilder, Öffnungszeiten, Kontakt und Koordinaten → **200 in 0,04 s**, Seite rendert: „QA Leer · Nirgendwo · Geschlossen · Keine Öffnungszeiten angegeben · Barrierefreiheit: Nicht verfügbar …" |
| EC-02 | ✅ bestanden | Beim leeren Haus fehlt der Kontaktblock ganz — `hasContactInfo()` entscheidet |
| EC-03 | ✅ bestanden | belegt durch AK-05: „Montag **(Heute)**" wird über den `int`-Vergleich gesetzt |

## Was sich seit der Rekonstruktion geändert hat

**AK-13 ist erledigt.** Die Spec schreibt: *„Der Aufruf steht synchron in
`RestaurantController::show()` und trägt keine Zeitschranke — es trifft ausgerechnet die
wichtigste Seite."* Seit BF-44 (heute) steht dort `'timeout' => 3`. Gemessen wurde das in
B10 gegen einen schweigenden Server: Rückkehr nach 0,3 s statt nach über 30.

**AK-14 bleibt bestätigt, aber der Verschärfer ist weg.** Die Spec begründet die
Einstufung so: *„Ein über die API eingeschleustes Haus (B23/AK-21) hat damit sofort eine
vollwertige, indexierbare Seite."* Seit BF-24 kommt über die API nichts mehr ungeprüft in
die Datenbank — was eine Detailseite hat, hat ein Admin gesehen.

Was bleibt, ist die Beobachtung selbst: Eine Detailseite zeigt Kontaktdaten im Klartext,
die der offene Datensatz (B17/AK-06) bewusst weglässt. **Das ist kein Widerspruch,
sondern eine bewusste Trennung:** Eine einzelne Seite mit Telefonnummer ist eine
Auskunft, ein Sammelabzug aller Telefonnummern wäre eine Adressliste. Genau so steht es
in `CLAUDE.md`. Deshalb bekommt AK-14 **keine Befundnummer** — es ist die Eigenschaft
zweier Features, die zusammen richtig entschieden sind.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Fremde IDs** | 404 bei nicht existierend und bei falschem Format |
| **Anmeldung** | nicht nötig — die Seite ist der öffentliche Zweck |
| **Einreicher** | wird nicht genannt (AK-16) |
| **Kontaktdaten** | im Klartext, ohne Verschleierung — bewusst, siehe oben |
| **Zeitzone** | `Europe/Luxembourg` gegen Server-`UTC` — explizit gesetzt, nicht geerbt |
| **Fremddienst** | Zeitschranke seit BF-44 |
| **Leerer Datensatz** | rendert in 0,04 s ohne Fehler |
| **Testsuite** | 364 Tests, 0 Fehler |

## Hinweise ohne Fehlerstatus

- **AK-15 (Kontaktdaten im Klartext)** ist kein Befund: Die Seite existiert, damit
  jemand das Restaurant erreicht. Eine Verschleierung per JavaScript machte sie für
  Screenreader schlechter — auf dieser Plattform der falsche Preis für einen
  Spam-Schutz, der ohnehin nur gegen einfache Sammler wirkt.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Keine. Alle Kriterien ließen sich am laufenden System messen, und die Befundlage gibt
nichts her, was festzuhalten wäre. Die Maßanzeige (AK-03/AK-04) wäre der Kandidat für
einen Test — sie ist die Stelle, an der eine Regression am meisten anrichten würde. Sie
ist über `Restaurant::hasWideDoors()` bereits abgedeckt, und ein Template-Test wäre hier
Aufwand ohne zusätzliche Sicherheit.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B07`. B06 geht auf `approved` — **ohne Eintrag in `features/befunde.md`**,
weil es nichts einzutragen gibt. Das ist bisher einmalig in dieser Reihe.
