# B11 · Restaurant vorschlagen (Wizard) — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein mittlerer und ein niedriger Befund, ein Kriterium nicht
prüfbar.

18 von 19 Kriterien bestanden, AK-09 nicht prüfbar. Der Assistent ist sorgfältig gebaut:
Die zwölf dreiwertigen Pflichtfragen liefern ihre Fehler **am Feld** (elfmal gemessen,
nicht am Wurzelformular), der Assistent springt auf den Schritt mit dem Fehler, und die
Radios sind `sr-only` statt `hidden` — 36 Stück, kein einziges mit `hidden`.

Und dies ist die **einzige Stelle im Projekt**, an der die E-Mail-Bestätigung etwas
erzwingt. Gemessen: `unverified@endlech.lu` → 302 nach `/de/verify` mit der Meldung
*„Bitte bestätige zuerst deine E-Mail-Adresse, um Restaurants vorschlagen zu können."*

**Der Befund, der zählt:** Wer zwölfmal „Weiß nicht" antwortet, bekommt nach der
Genehmigung zwölfmal „Nein" — und die veröffentlichte Durchschnittspunktzahl fällt
messbar. Ich habe es durchgespielt: `averageScore` sank von **5,09 auf 4,67**.

Nächster Aufruf: **`/sdd-erfassen B20`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Zugang

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Gast → **302** nach `/de/login` |
| **AK-02** | ✅ bestanden | `unverified@endlech.lu` → **302** nach `/de/verify`, Meldung *„Bitte bestätige zuerst deine E-Mail-Adresse, um Restaurants vorschlagen zu können."* |

### Assistent

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-03 | ✅ bestanden | **5** `data-suggestion-wizard-target="step"` im Markup, Fortschrittsleiste vorhanden |
| **AK-04** | ✅ bestanden | Eine von zwölf Fragen beantwortet, abgeschickt → **422**; die Meldung *„Bitte wähle Ja, Nein oder Weiß nicht."* steht **11×** im HTML, jeweils als `<ul><li id="restaurant_suggestion_hasAccessibleToilet_error1">` **innerhalb des `<fieldset>`** — am Feld, nicht am Formular |
| **AK-05** | ✅ bestanden | Die 422-Antwort trägt `data-suggestion-wizard-current-value="2"` — Schritt 2 ist der mit den Barrierefreiheitsfragen |
| AK-06 | ✅ bestanden | **0** Radios mit `checked` im ausgelieferten Formular |
| AK-07 | ✅ bestanden | vollständiger Submit → 302 nach `/de/community/thanks`; DB: `status=pending`, `suggestedBy=156`, `wheelchair=yes` |
| **AK-08** | ✅ bestanden | **36** Radios mit `sr-only` (12 Felder × 3 Optionen), **0** mit `hidden`. Fokus über `peer-focus-visible:ring-2 peer-focus-visible:ring-inset` |
| **AK-09** | ⚠️ **nicht prüfbar** | siehe unten |
| AK-10 | ✅ bestanden | Der offene Vorschlag erscheint **nicht** im Profil; nach der Genehmigung erscheint das Restaurant. Die Liste zeigt `Restaurant`-Datensätze |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-13 | ✅ bestanden | Vorschlag trägt `suggestedBy` (Verweis) und Kontaktdaten des Betriebs: `phone=+352 123`, `email=kontakt@qa.example` — von einem Dritten eingetragen |
| AK-14 | ✅ bestanden | `information_schema.REFERENTIAL_CONSTRAINTS`: `DELETE_RULE = SET NULL` |
| **AK-15** | ✅ bestanden | `_token=voellig-falsch` → **422**, 0 Einträge. **Ohne Referer** (Cross-Origin-Simulation) → **422**, 0 Einträge. `csrf.yaml:5`: `token_id: submit`, stateless |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-11** ⚠ | ✅ bestätigt | **0** Limiter in `CommunityController` → BF-50 |
| **AK-12** ⚠ | ✅ bestätigt | zwölfmal `unknown` eingereicht → nach der Genehmigung `wheelchair=0 toilet=0 dogs=0 vegan=0 cash=0`; `averageScore` **5,09 → 4,67** → BF-49 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| **EC-01** | ✅ bestanden | Die elf Fehler stehen an den Feldern, nicht am Wurzelformular — genau das, was ohne `'error_bubbling' => false` scheitern würde. Das ist der Nachweis, dass die Zeile wirkt |
| EC-02 | ✅ bestanden | keine Vorauswahl (AK-06) **und** verlässlicher 422 bei unvollständigem Submit (AK-04) |
| EC-03 | ✅ bestanden | `unknown` ist in der Datenbank ein eigener Wert (`is_wheelchair_accessible='unknown'`), unterscheidbar von `NULL` |
| EC-04 | ✅ bestanden | `cuisine` ist Freitext im Vorschlag; beim Genehmigen zerlegt `AdminSuggestionController` ihn an Kommas |

## Nicht prüfbar

### AK-09 · Vorabprüfung im Browser

Der Assistent soll die dreiwertigen Pflichtfragen bereits im Browser prüfen, bevor ein
Schritt verlassen wird. **Nicht gemessen** — die Browseranmeldung schlug fehl: Der Klick
auf den Submit-Knopf löste keinen POST aus (Playwright/Brave über CDP, mit
ausgeblendeter Debug-Toolbar; drei Anläufe, auch mit `force: true`).

Die naheliegende Ursache ist die im Projekt dokumentierte Formularfalle: Das
Passkey-Formular steht **zuerst im Markup**, und der `AuthenticationController` des
npm-Pakets ruft `form.checkValidity()`. Mein Selektor
(`form:has(input[name="_password"]) button[type="submit"]`) sollte das umgehen — tat es
aber nicht. Bei B15 funktionierte derselbe Aufbau ohne Anmeldung; hier braucht der
Wizard eine.

**Was ich stattdessen habe — und was ausdrücklich nicht als Nachweis zählt:** Im
ausgelieferten HTML steht `data-suggestion-wizard-incomplete-message-value="Bitte
beantworte alle Fragen in diesem Schritt."`, und
`suggestion_wizard_controller.ts:66–90` liest `[data-tristate]`-Gruppen, prüft
`input[type="radio"]:checked`, setzt die Meldung und fokussiert die erste fehlende
Gruppe. Das sieht vollständig aus — **aber „sieht vollständig aus" ist kein Nachweis**,
und ich trage es deshalb hier ein und nicht in der Tabelle oben.

Die serverseitige Prüfung greift unabhängig davon (AK-04, gemessen). Ein Ausfall von
AK-09 kostet Bequemlichkeit, nicht Datenqualität.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Zugang ohne Bestätigung** | 302 nach `/de/verify` — die einzige Stelle im Projekt, wo das greift |
| **CSRF** | falsches Token → 422, ohne Referer → 422; in beiden Fällen **0** Einträge |
| **Rate Limit** | keins → BF-50 |
| **Fremde Kontaktdaten** | werden angenommen (AK-13), laufen aber über die Admin-Freigabe |
| **Fehlerzuordnung** | 11 Feldfehler statt eines Sammelfehlers — im Ergebnis geprüft, nicht am Code |
| **Testsuite** | 362 Tests, 0 Fehler |

## Fehler

### BF-49 · „Weiß nicht" wird bei der Genehmigung zu „Nein" — mittel

**Betrifft:** AK-12

**Reproduktion:**
1. Vorschlag mit **allen zwölf** Fragen auf „Weiß nicht" einreichen
2. Im Admin genehmigen
3. Das entstandene Restaurant ansehen

**Erwartet:** ein Weg, „unbekannt" zu erhalten — oder wenigstens keine Verfälschung der
veröffentlichten Zahlen
**Tatsächlich:**
```
Vorschlag:   wheelchair=unknown  toilet=unknown  vegan=unknown
Restaurant:  wheelchair=0        toilet=0        dogs=0  vegan=0  cash=0
averageScore auf /open.json:  5,09 → 4,67
```

**Ort:** `AdminSuggestionController.php:65–67` und die neun folgenden Zeilen:
`$suggestion->isWheelchairAccessible()?->isYes() ?? false`

**Warum das mehr ist als ein Modellierungsdetail:** Das dreiwertige Modell wurde
eingeführt, weil *„eine nicht angehakte Checkbox zweierlei zugleich bedeutete — »gibt es
nicht« und »weiß ich nicht«"* (`CLAUDE.md`). Genau diese Unterscheidung geht bei der
Übernahme verloren — und zwar in die ungünstige Richtung: Aus „ich weiß es nicht" wird
eine **Aussage über das Restaurant**, die niemand getroffen hat.

Die Folge ist messbar und öffentlich. Die Punktzahl auf `/open` bewertet acht Merkmale;
ein Restaurant, über das nichts bekannt ist, bekommt 0 von 10 — als wäre es nachweislich
nicht barrierefrei. Das ist Risiko 2 aus dem PRD, hier mit Zahlen belegt.

Der Code benennt es selbst:
```php
// Restaurant kennt nur ja/nein: "Weiß nicht" wird als "nein" übernommen,
// der Admin kann es im Restaurant-Formular nachtragen.
```
Das Nachtragen ist die Lösung — aber nichts erinnert daran, und die Punktzahl ist bis
dahin veröffentlicht.

**Vorschlag, in aufsteigender Größe:**
1. **Sofortmaßnahme:** Bei der Genehmigung eine Warnung anzeigen, wenn Merkmale auf
   „Weiß nicht" stehen („5 von 12 Angaben sind unbekannt und werden als »nein«
   übernommen"). Der Admin entscheidet dann bewusst.
2. **Punktzahl:** `AccessibilityScore` unterscheidet „nicht erfüllt" nicht von „nicht
   erfasst". Ein Restaurant mit null Angaben sollte keine 0 haben, sondern **keine
   Punktzahl** — und aus dem Durchschnitt herausfallen. Das berührt B16 und B17.
3. **Vollständig:** `Restaurant` auf `TriState` umstellen. `CLAUDE.md` nennt die Kosten
   (Repository-Filter, `RestaurantTransformer`, fünf Templates, Fixtures) und den Grund
   dagegen (Boolean-Vertrag der iOS-API). Nach BF-24 ist die API-Frage neu zu bewerten.

### BF-50 · Kein Rate Limit auf dem Wizard — niedrig

**Betrifft:** AK-11

**Nachweis:** `grep -c limiter src/Controller/CommunityController.php` → **0**

Ein bestätigter Nutzer kann beliebig viele Vorschläge einreichen. Gemildert dadurch,
dass ein Konto **und** eine bestätigte Adresse nötig sind — die Registrierung ist seit
BF-02 gedeckelt (5/Stunde) — und dass jeder Vorschlag durch die Freigabe läuft.

**Die Folge ist Arbeit für den Betreiber, nicht öffentlicher Schaden.** Deshalb
*niedrig* und nicht *mittel*: Der Unterschied zu BF-30 (API-Einreichung) ist die
Bestätigungspflicht davor.

**Siebte Wiederholung von M-01.** Die Konvention in `CLAUDE.md` erfasst diesen Fall:
Der Weg löst zwar keine Mail aus, aber er schreibt in eine Warteschlange, die ein
Mensch abarbeiten muss.

**Vorschlag:** Ein Limiter am Konto, großzügig (etwa 10/Stunde) — mehr reicht kein
echter Nutzer ein, und ein Angreifer legt damit die Moderation nicht still.

## Hinweise ohne Fehlerstatus

- **AK-02 ist bemerkenswert, weil es die Ausnahme ist.** Dies ist die einzige Stelle im
  Projekt, an der `isVerified` etwas erzwingt. Überall sonst — Anmeldung, API, Profil —
  kommt ein unbestätigtes Konto durch (B01/FB-03, in B23 erneut gemessen). Dass es hier
  greift, ist richtig; dass es sonst nirgends greift, bleibt der offene Punkt.
- **Fremde Kontaktdaten** (AK-13) laufen über die Admin-Freigabe und sind damit besser
  gesichert als über die API vor BF-24. Kein eigener Befund.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Keine. Die vorhandene Abdeckung des Wizards (Formularvalidierung, Tri-State-Pflicht) ist
in `tests/` vorhanden, und die beiden Befunde sind Verhaltensfragen:

- **BF-49** ließe sich testen, aber der Test hielte das unerwünschte Verhalten fest und
  wäre nach der Reparatur falsch herum. Sinnvoll wird er mit der Reparatur.
- **BF-50** ist in `when@test` ohnehin ausgehebelt.
- **AK-09** ist der Fall, für den ein Test etwas gebracht hätte — und genau der ließ sich
  nicht ausführen.

**Suite: 362 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B20`. B11 geht auf `approved`; BF-49 und BF-50 stehen in
`features/befunde.md`.

BF-49 ist der interessanteste offene Punkt dieser ganzen Prüfreihe: Er ist kein Fehler
im engeren Sinn — der Code tut, was der Kommentar sagt — sondern eine Entscheidung, die
in einer veröffentlichten Kennzahl ankommt. Die Sofortmaßnahme (Warnung bei der
Genehmigung) ist klein; die richtige Lösung berührt drei Features.
