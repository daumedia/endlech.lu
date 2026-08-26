# B21 · Vorschläge prüfen (Admin) — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein mittlerer und zwei niedrige Befunde.

18 von 18 Kriterien bestanden, 2 von 2 Edge Cases. Vier davon sind ⚠-Bestätigungen, und
eine davon ist der Grund für diesen Bericht:

> **Zweimal dasselbe Formular abgesendet → zwei Restaurants.** IDs 318 und 319, beide
> mit der Meldung *„»QA Doppelt« wurde genehmigt und als Restaurant hinzugefügt."*
> `approve()` prüft den Status nicht.

Das ist kein konstruierter Fall: Ein Admin mit zwei offenen Tabs oder einem Doppelklick
erzeugt eine Dublette im öffentlichen Bestand — und weil es keine Dublettenprüfung gibt
(B11/FB-04), fällt sie niemandem auf.

Der Rest ist solide: Die Übertragung stimmt in allen Feldern, `submittedBy` steht auf dem
**Einreicher** und nicht auf dem Admin, `isVerified` bleibt `false`, und der Küchen-Freitext
wird korrekt zerlegt — auch der leere Fall erzeugt keinen leeren Eintrag.

Nächster Aufruf: **`/sdd-erfassen B09`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Ansicht und Zugriff

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `/de/admin/vorschlaege` → **200**, Gruppierung nach Status im Repository (`findByStatus(STATUS_PENDING)` u. a., Zeile 28) |
| **AK-02** | ✅ bestanden | Detailseite zeigt Telefon, E-Mail, Website, Instagram, Notiz und Küche. **„Weiß nicht" erscheint genau 4×** — passend zu den vier `unknown`-Feldern des Testvorschlags. Farbklassen: grün 7, rot 5, grau 57 |
| AK-09 | ✅ bestanden | `ROLE_USER` → **403** auf Liste und Detailseite; Gast → **302** |
| AK-15 | ✅ bestanden | `approve-suggestion-{id}` und `reject-suggestion-{id}`, beide mit der ID im Schlüssel |

### Genehmigen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-03 | ✅ bestanden | *„»QA Pruefling« wurde genehmigt und als Restaurant hinzugefügt."*, Vorschlag `status=approved`, Restaurant angelegt |
| **AK-04** | ✅ bestanden | Freitext `"Italienisch, Pizzza"` → zwei Küchen am Restaurant; Küchen-Typen **20 → 21** (nur `Pizzza` war neu, `Italienisch` wurde wiederverwendet) |
| **AK-05** | ✅ bestanden | `submittedBy=162` — der Einreicher, nicht der handelnde Admin |
| AK-06 | ✅ bestanden | `isVerified=0` — Genehmigung ist keine Verifizierung |
| AK-08 | ✅ bestanden | `_token=falsch` → *„Ungültiges CSRF-Token."*, Status bleibt `pending` |

### Ablehnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-07 | ✅ bestanden | *„Vorschlag »QA Ablehnung« wurde abgelehnt."*, `status=rejected`, `adminNote="Bereits im Bestand"`, **kein** Restaurant entstanden |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-14** | ✅ bestanden | Nach der Genehmigung stehen `phone=+352 555` und `email=betrieb@qa.example` am öffentlichen Restaurant — von einem Dritten eingetragen |
| **AK-16** | ✅ bestanden | `admin_note` mit **5000 Zeichen** → gespeichert, alle 5000. Spaltentyp `longtext`, keine Längenprüfung |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-10** ⚠ | ✅ bestätigt | zweimal dasselbe Token → **zwei Restaurants** (318, 319) → BF-54 |
| **AK-11** ⚠ | ✅ bestätigt | Vorschlag `wheelchair=yes toilet=no dogs=unknown` → Restaurant `1 / 0 / 0`. **„nein" und „weiß nicht" sind im Ergebnis nicht mehr unterscheidbar** — das ist BF-49 aus B11, hier an der Stelle, wo es passiert |
| **AK-12** ⚠ | ✅ bestätigt | Notiz „Bereits im Bestand" gespeichert; im Profil des Einreichers **nicht** sichtbar, **0** Routen für eigene Vorschläge, **0** Mails → BF-55 |
| **AK-13** ⚠ | ✅ bestätigt | nach der Genehmigung: `lat=NULL doorWidth=NULL Öffnungszeiten=0` — der Vorschlag erhebt diese Felder gar nicht → BF-56 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| **EC-01** | ✅ bestanden | Vorschlag mit leerem `cuisine` genehmigt → Küchen-Typen **22 → 22**, Küchen am Restaurant **0**. Der leere Teil wird verworfen, kein Eintrag mit leerem Namen |
| EC-02 | ✅ bestanden | belegt durch AK-04: `findOrCreateByName()` fand `Italienisch` wieder und legte nur `Pizzza` neu an |

## Fehler

### BF-54 · Genehmigen ist nicht idempotent — mittel

**Betrifft:** AK-10

**Reproduktion (das reale Szenario: zwei offene Tabs):**
1. Vorschlag im Admin öffnen, CSRF-Token merken
2. Genehmigen
3. **Denselben** Request ein zweites Mal absenden

```
Absenden 1: „QA Doppelt" wurde genehmigt und als Restaurant hinzugefügt.  → Restaurants: 1
Absenden 2: „QA Doppelt" wurde genehmigt und als Restaurant hinzugefügt.  → Restaurants: 2
IDs: 318, 319 · Vorschlagsstatus: approved
```

**Erwartet:** beim zweiten Mal ein Hinweis, dass der Vorschlag bereits bearbeitet ist
**Tatsächlich:** ein zweites Restaurant, dieselbe Erfolgsmeldung

**Ort:** `AdminSuggestionController::approve()` — `grep` nach `getStatus()` in der Methode:
**kein Treffer**. Es fehlt schlicht ein `if ($suggestion->getStatus() !== STATUS_PENDING)`.

**Was den Fall abmildert und was nicht:** Auf der Detailseite verschwindet das
Genehmigen-Formular nach der Genehmigung — wer die Seite neu lädt, kann nicht noch einmal
drücken. Das schützt aber nur den, der die Seite neu lädt. Ein zweiter Tab, ein
Doppelklick oder ein Zurück-und-erneut-senden trägt sein Token weiter, und das Token ist
gültig, weil es an die ID gebunden ist und nicht an den Status.

**Warum es zählt:** Die Dublette landet sofort im öffentlichen Bestand — in der
Restaurantliste, im CC-BY-Datensatz und in den Kennzahlen von `/open` (zwei Einträge
statt einem verschiebt `averageScore` und `verifiedShare`). Und es gibt keine
Dublettenprüfung, die sie später auffangen würde.

**Vorschlag:** Am Anfang von `approve()` und `reject()`:
```php
if ($suggestion->getStatus() !== RestaurantSuggestion::STATUS_PENDING) {
    $this->addFlash('info', $this->translator->trans('flash.suggestion_already_handled'));

    return $this->redirectToRoute('admin_suggestion_index');
}
```
Vier Zeilen, und der Vorgang wird idempotent. Dasselbe gehört an `reject()`, aus
demselben Grund — dort ist der Schaden zwar geringer (kein zweiter Datensatz), aber der
Status würde stillschweigend von `approved` auf `rejected` kippen.

### BF-55 · Die Ablehnungsnotiz erreicht ihren Adressaten nie — niedrig

**Betrifft:** AK-12

**Nachweis:** Notiz „Bereits im Bestand" gespeichert (`admin_note` in der Datenbank).
Danach:
- im Profil des Einreichers: **nicht** sichtbar
- `debug:router` nach Routen für eigene Vorschläge: **0**
- Mails an den Einreicher: **0**

Ein Feld, dessen einziger denkbarer Zweck die Rückmeldung an den Einreicher ist, wird
erfasst und nur dem Admin gezeigt. Wer einen Vorschlag einreicht, erfährt nie, was daraus
wurde — weder dass er abgelehnt wurde noch warum.

**Zusammen mit B11/AK-10** (offene Vorschläge tauchen im Profil nicht auf) heißt das: Der
Einreicher sieht seinen Vorschlag **zu keinem Zeitpunkt** wieder. Er verschwindet beim
Absenden und taucht entweder als Restaurant auf oder gar nicht.

**Vorschlag:** Eine Mail bei der Ablehnung, die die Notiz enthält — die Mechanik dafür
steht im Projekt (`WaitlistConfirmationService` zeigt das Muster). Alternativ ein
Abschnitt „Meine Vorschläge" im Profil mit Status und Notiz; das deckt zugleich
B11/AK-10 und B23/BF-32 ab. **Drei Befunde, ein Bauvorgang.**

### BF-56 · Genehmigte Vorschläge starten mit niedriger Punktzahl — niedrig

**Betrifft:** AK-13

**Nachweis:** Nach der Genehmigung: `lat=NULL`, `doorWidthCm=NULL`,
Öffnungszeiten **0**.

Der Vorschlags-Assistent erhebt diese Felder gar nicht. Für Koordinaten und
Öffnungszeiten ist das nachvollziehbar — sie sind mühsam einzugeben. Für die
**Türbreite und den Tischabstand** ist es unglücklich: Sie sind zwei von zehn Punkten in
`AccessibilityScore`, und ein so entstandenes Restaurant startet damit bei höchstens 8
von 10, ohne dass jemand nachgemessen hat.

**Zusammen mit BF-49** („Weiß nicht" → „Nein") heißt das: Ein über die Community
eingereichtes Restaurant kann eine Punktzahl bekommen, die deutlich unter dem liegt, was
über es bekannt ist — und diese Zahl steht auf `/open`.

**Kein eigener Reparaturvorschlag**, weil die Frage vor der Umsetzung steht: Sollen die
Maße in den Assistenten (dann wird er länger) oder soll die Punktzahl „nicht erfasst"
von „nicht erfüllt" unterscheiden (dann berührt es B16 und B17)? Die zweite Fassung ist
die bessere und deckt BF-49 mit ab.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Rollenschranke** | `ROLE_USER` 403, Gast 302 auf beiden Routen |
| **CSRF** | falsches Token bei `genehmigen` → keine Änderung; beide Endpunkte mit ID-gebundenem Token |
| **Idempotenz** | **nicht gegeben** → BF-54 |
| **Fremde Kontaktdaten** | werden veröffentlicht (AK-14) — das ist die bewusste Eigenschaft des Features, mit Admin-Sichtung davor |
| **Ungeprüfte Eingabe** | `admin_note` ohne Längenprüfung, 5000 Zeichen gespeichert. Nur intern sichtbar, `longtext` — kein Befund |
| **Küchen-Injektion** | über diesen Weg möglich (`findOrCreateByName`), aber **mit Admin-Sichtung** — anders als über die API vor BF-24 |
| **Testsuite** | 364 Tests, 0 Fehler |

## Hinweise ohne Fehlerstatus

- **AK-11 bekommt keine eigene Nummer** — das ist BF-49 aus B11. Hier ist nur der Ort,
  an dem die Umwandlung geschieht. Was mir dabei aufgefallen ist und im B11-Bericht noch
  nicht steht: **`no` und `unknown` werden im Ergebnis identisch** (`toilet=0`,
  `dogs=0`), obwohl der Vorschlag sie unterscheidet. Wer das Restaurant später ansieht,
  kann nicht mehr erkennen, welche Angabe geprüft und welche nur nicht bekannt war.
- **`admin_note` ohne Längenprüfung** (AK-16) ist kein Befund: Das Feld ist `longtext`,
  nur der Admin schreibt hinein, und es wird nirgends öffentlich. Erwähnt, weil die Spec
  danach fragt.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Keine. Die Befunde sind Verhaltensfragen, und für BF-54 gilt dasselbe wie bei BF-47:
Ein Test, der festhält, dass zweimaliges Genehmigen zwei Restaurants erzeugt, wäre nach
der Reparatur falsch herum. Er entsteht mit der Reparatur, dann in der richtigen
Richtung.

Die vorhandene Abdeckung (`AdminSuggestionControllerTest`) deckt AK-03, AK-07, AK-08 und
AK-09 ab.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B09`. B21 geht auf `approved`; BF-54, BF-55 und BF-56 stehen in
`features/befunde.md`.

BF-54 ist der Kandidat für den nächsten Reparaturdurchgang: vier Zeilen, und eine
Dublette im öffentlichen Bestand wird strukturell unmöglich.
