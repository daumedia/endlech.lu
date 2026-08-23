# B06 · Restaurant-Detailseite — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Die Seite, auf die alles zuläuft: alle erfassten Angaben zu einem Haus — acht
Barrierefreiheitsmerkmale, zwei Maße nach DIN 18040, Öffnungszeiten, Kontakt,
Sozialkonten, Fotogalerie, Bestellwege und Haltestellen in der Nähe.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B07 | rekonstruiert | Wochenplan und „jetzt geöffnet" |
| B08 | rekonstruiert | Küchen-Abzeichen |
| B09 | rekonstruiert | Titelbild und Galerie |
| B10 | rekonstruiert | Haltestellen |

## User Stories

- **US-01** · Als Rollstuhlfahrer möchte ich vor dem Losfahren wissen, ob ich
  hineinkomme.
- **US-02** · Als Gast möchte ich sehen, ob gerade geöffnet ist.
- **US-03** · Als Gast ohne Auto möchte ich die nächste Haltestelle kennen.
- **US-04** · Als Gast möchte ich sehen, ob und wo ich bestellen kann.

## Nicht im Scope

- Bewertungen abgeben — es gibt keine Nutzerbewertungen, nur ein gepflegtes `rating`
- Reservierung, Bestellung — es wird nur verlinkt

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher ruft `/{locale}/restaurants/{id}` auf, wenn die
  Seite lädt, dann sieht er Name, Emoji, Stadt, Bewertung, Küchen und — falls geprüft —
  das Verifikationsabzeichen.
- **AK-02** · Angenommen, die Seite lädt, wenn die Barrierefreiheit betrachtet wird,
  dann stehen dort alle acht Merkmale mit Ja/Nein und die Freitextnotizen.
- **AK-03** · Angenommen, Türbreite oder Tischabstand sind erfasst, wenn sie angezeigt
  werden, dann steht dabei, ob sie die 90 cm nach DIN 18040 erreichen
  (`hasWideDoors()`, `hasWheelchairTableSpacing()`).
- **AK-04** · Angenommen, ein Maß ist **nicht** erfasst, wenn es angezeigt wird, dann
  steht dort „nicht ausgemessen" — nicht „erfüllt nicht". Die Helper liefern `?bool`,
  und `null` heißt genau das.
- **AK-05** · Angenommen, das Haus hat Öffnungszeiten, wenn die Seite lädt, dann
  erscheint der Wochenplan mit hervorgehobenem heutigem Tag und mehreren Zeitfenstern je
  Tag als `12:00 – 14:30 · 18:00 – 22:00`.
- **AK-06** · Angenommen, das Haus ist gerade geöffnet, wenn die Seite lädt, dann sagt
  ein Hinweis das; ist es geschlossen, steht dort die nächste Öffnung.
- **AK-07** · Angenommen, das Haus hat Bilder, wenn die Seite lädt, dann ist das erste
  das Titelbild und die übrigen bilden eine Galerie mit Lightbox.
- **AK-08** · Angenommen, Kontaktdaten sind erfasst, wenn sie angezeigt werden, dann
  sind Telefon, E-Mail und Website verlinkt; Sozialkonten erscheinen als Symbole.
- **AK-09** · Angenommen, Bestellwege sind erfasst, wenn sie angezeigt werden, dann
  erscheint je Plattform das Markenlogo (sechs SVGs) oder ein Emoji für die generischen
  Optionen.
- **AK-10** · Angenommen, das Haus hat Koordinaten, wenn die Seite lädt, dann erscheinen
  bis zu fünf Haltestellen — sofern ein API-Schlüssel hinterlegt ist (B10).
- **AK-11** · Angenommen, eine nicht existierende ID steht im Pfad, wenn die Anfrage
  durchläuft, dann antwortet der Server mit **404** (`ParamConverter`, Requirement
  `\d+`).
- **AK-12** · Angenommen, die Seite lädt, wenn der heutige Tag bestimmt wird, dann
  geschieht das in der Zeitzone **Europe/Luxembourg**, nicht in der Serverzeitzone.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-13** ⚠ · Angenommen, die HAFAS-Schnittstelle antwortet langsam, wenn die
  Detailseite geladen wird, dann wartet der Besucher.
  *(Der Aufruf steht synchron in `RestaurantController::show()` und trägt keine
  Zeitschranke — siehe B10/AK-12. Es trifft ausgerechnet die wichtigste Seite.)*

- **AK-14** ⚠ · Angenommen, ein Haus ist nicht verifiziert, wenn die Detailseite
  aufgerufen wird, dann ist sie **öffentlich abrufbar** wie jede andere.
  *(Konsequent zu B05/AK-15 — hier festgehalten, weil die Detailseite die Kontaktdaten
  im Klartext zeigt, die im offenen Datensatz bewusst fehlen (B17/AK-06). Ein über die
  API eingeschleustes Haus (B23/AK-21) hat damit sofort eine vollwertige, indexierbare
  Seite.)*

### Datenschutz und Missbrauchsschutz

- **AK-15** · Angenommen, die Seite lädt, wenn nach personenbezogenen Daten gesucht
  wird, dann sind es die Geschäftskontaktdaten: Telefon, E-Mail, Website, Sozialkonten
  — im Klartext und ohne Schutz vor automatisiertem Auslesen.
- **AK-16** · Angenommen, die Seite lädt, wenn geprüft wird, ob der Einreicher genannt
  wird, dann nicht — `submittedBy` erscheint nur im Profil des Einreichers (B04).
- **AK-17** · Angenommen, die Seite wird aufgerufen, wenn geprüft wird, ob eine
  Anmeldung nötig ist, dann nicht.

## Edge Cases

- **EC-01** · Ein Haus ohne Bilder, ohne Öffnungszeiten, ohne Kontakt und ohne
  Koordinaten rendert eine gültige, wenn auch magere Seite.
- **EC-02** · `hasContactInfo()` entscheidet, ob der Kontaktblock überhaupt erscheint.
- **EC-03** · Der heutige Tag wird als `int` (1–7) ans Template gegeben und dort mit
  `dayOfWeek` verglichen.

## Fehlbestand

- **FB-01 · Keine Zeitschranke für den Haltestellenabruf.** Siehe AK-13 und B10/FB-01.
- **FB-02 · Kein Schutz der Kontaktdaten vor automatisiertem Auslesen.** Siehe AK-15.
  Der offene Datensatz lässt sie bewusst weg — auf der Seite selbst stehen sie
  ungeschützt.
- **FB-03 · Keine strukturierten Daten (schema.org).** Für eine Seite, deren Zweck das
  Auffinden ist, wäre `Restaurant` mit `accessibilityFeature` naheliegend.
- **FB-04 · Keine Angabe, wann die Daten zuletzt geprüft wurden.** `verifiedAt` ist
  erfasst; ob es angezeigt wird, entscheidet das Template — eine Alterung wie bei den
  Finanzdaten (B16/AK-08) gibt es hier nicht.
- **FB-05 · Kein Meldeweg für falsche Angaben.** Ein Besucher, der feststellt, dass die
  Rampe nicht existiert, hat keinen Knopf dafür.

## Offene Fragen

- **OF-01** · Soll es einen Korrekturmeldeweg geben (FB-05)? Auf einer
  Barrierefreiheitsplattform ist eine falsche Ja-Angabe schädlicher als eine fehlende. —
  Betreiber
- **OF-02** · Soll `verifiedAt` mit Alterungshinweis angezeigt werden (FB-04)? —
  Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Maße als eigenes Feld statt Ja/Nein | `doorWidthCm`, `tableSpacingCm` als `?int` | eine Zahl ist überprüfbar, ein Häkchen nicht |
| 2 | `null` heißt „nicht ausgemessen" | `?bool` aus den Helpern | die Unterscheidung zu „erfüllt nicht" ist der Kern der Angabe |
| 3 | Konstanten `MIN_DOOR_WIDTH_CM` = `MIN_TABLE_SPACING_CM` = 90 | DIN 18040 | eine benannte Norm statt einer Hausmeinung |
| 4 | Haltestellen synchron im Request | statt asynchron | einfacher; der Preis ist AK-13 |
| 5 | Zeitzone fest `Europe/Luxembourg` | statt Serverzeit | die Anwendung bedient genau ein Land |
