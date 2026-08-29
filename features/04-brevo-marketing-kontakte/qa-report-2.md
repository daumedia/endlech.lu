# 04 · Marketing-Kontakte in Brevo — Testbericht, zweiter Durchlauf

Stand: 2026-08-29 · Geprüft gegen `spec.md` vom 2026-08-29 · Vorlauf: `qa-report.md` (erster Durchlauf, sechs Befunde)

## Fazit

**Production-ready: nein** — *Stand der Prüfung. BF-89 wurde am selben Tag behoben und gegengeprüft (Vermerk unten); BF-88 und BF-90 bleiben offen. Die erneute Abnahme steht aus (`/sdd-qa 04`).*

Fünf der sechs Befunde des ersten Durchlaufs sind behoben und gegengeprüft — darunter der
schwerste (BF-84: der Widerruf einer Quelle nahm die andere mit). Die Löschsemantik wurde
über eine Zustandsmatrix in beiden Reihenfolgen geprüft; **kein Kontakt bleibt bei Brevo
stehen, wenn alle Quellen verschwunden sind.**

**Die Reparatur von BF-83 greift jedoch nur eine Runde weit.** Sie hält den
Bestätigungsstand fest, bevor der Backfill läuft — das deckt den *ersten* Statuswechsel.
Beim *zweiten* steht `confirmedAt` bereits, gesetzt vom ersten, und die nie bestätigte
Adresse geht doch hinaus. Ein Vertriebsablauf mit zwei Schritten (Telefonat → Vorprüfung)
ist der Normalfall. Derselbe Weg steht dem Bestandsimport offen. Das ist **BF-89** und
kritisch — es ist AK-05, und AK-05 ist der Grund, warum dieses Feature einen
Double-Opt-In hat.

Nächster Schritt: `/sdd-build 04` mit BF-89. **Die Reihenfolge zuerst zu drehen war
richtig und reicht nicht** — die Ursache ist, dass `confirmedAt` zwei Bedeutungen trägt
(echter Double-Opt-In und Verwaltungs-Backfill) und sie nicht unterscheidet.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 48 von 48 |
| davon bestanden | 42 *(zuvor 41)* |
| davon durchgefallen | 2 *(zuvor 3)* |
| **nicht prüfbar** | 4 *(unverändert)* |
| Edge Cases belegt | 6 von 6 |
| Tests neu in diesem Durchlauf | 7 |
| Gesamtsuite | 671 Tests, **1 rot** — der Nachweis für BF-89 |

## Die Befunde des ersten Durchlaufs

| ID | Grad | Ergebnis | Gegenprobe |
|---|---|---|---|
| BF-83 | kritisch | ⚠️ **nur teilweise behoben** → **BF-89** | Erster Statuswechsel: 0 Kontakte ✅. **Zweiter Statuswechsel: 1 Kontakt** ❌ |
| BF-84 | kritisch | ✅ behoben | Widerruf der Warteliste bei aktivem Konto → Zeile bleibt (`pending/account`), Konto-Einwilligung steht, Wartelisten-Eintrag gelöscht. Zustandsmatrix in beiden Reihenfolgen grün |
| BF-84b | kritisch | ✅ behoben | `contactDeleted` entwertet die Einwilligung nicht mehr; ohne vorhandene Zeile läuft das Echo ins Leere. Beide Fälle im Test |
| BF-85 | mittel | ✅ behoben (belegter Fall) | Zwei `record()`-Aufrufe ohne `flush()` → eine Zeile. Die echte Nebenläufigkeit bleibt bewusst offen (OF-09) |
| BF-86 | hoch | ✅ behoben | Nach vier 429ern holt der fünfte Lauf die Zeile nach (`synced`). **Grenzwert geprüft:** Läufe 1–5 greifen auf, Lauf 6 nicht mehr — Rückzug exakt bei 5 |
| BF-87 | mittel | ✅ behoben | `flush()` im `try/catch`; der neue Protokolleintrag trägt Fehlerklasse und Zahl, keine Adressen |
| BF-88 | mittel | ⏸️ unverändert offen | `docs/datenschutz.md` führt den AV-Vertrag weiterhin als „noch zu prüfen"; hängt an OF-01 |

## Neu bewertete Akzeptanzkriterien

| AK | Erster Durchlauf | Jetzt | Nachweis |
|---|---|---|---|
| AK-05 | ❌ (BF-83) | ❌ **weiterhin** | siehe **BF-89** — zweiter Statuswechsel und Bestandsimport |
| AK-19 | ❌ (BF-86) | ✅ **bestanden** | `MarketingSyncServiceTest::testAk19FehlversuchWirdErneutAufgegriffen`; zusätzlich Grenzwert gemessen: Rückzug nach genau 5 Versuchen, danach `isStuck()` |
| AK-33 | ❌ (BF-88) | ❌ **weiterhin** | AV-Vertrag ohne Prüfdatum |

Die übrigen 45 Kriterien wurden über die Regression bestätigt (671 Tests) und in den
sicherheitsrelevanten Punkten erneut aktiv geprüft — siehe Sicherheitstabelle. Ihre
Einzelnachweise stehen unverändert in `qa-report.md`.

## Sicherheitsprüfung (Wiederholung auf dem geänderten Stand)

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| 3 · Rate Limit greift | bestanden | Webhook erneut überrannt: 121. Aufruf → **429**. Grenzwert unverändert |
| 2 · Zugriffsregeln | bestanden | Webhook ohne Token → **401**, mit falschem Token → **401** |
| 5 · PII an externe Dienste | bestanden | Payload erneut abgefangen: genau fünf Attribute, Negativliste (`message`, `phone`, `locality`, `source`, `token`, `emailBlacklisted`) **vollständig eingehalten** |
| 4 · PII in Protokollen | bestanden | Die drei Protokollstellen in `src/Marketing/` tragen `contact_id`, `state`, `attempts`, `reason`, `betroffen` — keine Adresse. Der neue Eintrag aus BF-87 ebenfalls nicht |
| 6 · Geheimnisse | bestanden | `.env` führt alle drei Werte leer; keine echten Schlüssel im Quelltext |
| 8 · Löschen | **bestanden** | Neue Zustandsmatrix: beide Quellen weg (in beiden Reihenfolgen) → `removal_pending`; stehender Löschauftrag überlebt einen zweiten Widerruf; Quellen ohne Einwilligung und unbestätigte Quellen verhindern die Löschung **nicht** |

## Fehler

### BF-89 · Der zweite Statuswechsel trägt eine nie bestätigte Adresse ein — kritisch

**Betrifft:** AK-05, EC-03, Decision Log #3 · **Nachfolger von BF-83**

**Reproduktion:**
1. Partner-Formular absenden, Werbe-Häkchen setzen, Bestätigungsmail **nicht** anklicken
2. Als Admin den Status auf „Kontaktiert" setzen → der Backfill setzt `confirmedAt`;
   die Registry wird korrekt **nicht** gerufen (BF-83-Reparatur greift), 0 Kontakte ✅
3. Denselben Eintrag auf „Qualifiziert" setzen — der übliche zweite Schritt nach einer
   Vorprüfung

**Erwartet:** Weiterhin 0 Kontakte.
**Tatsächlich:** 1 Kontakt im Auftragsbuch. Gemessen:

```
Statuswechsel -> CONTACTED  : confirmed_at=gesetzt | Kontakte im Auftragsbuch=0
Statuswechsel -> QUALIFIED  : confirmed_at=gesetzt | Kontakte im Auftragsbuch=1
```

**Zweiter Weg, dieselbe Ursache:** Der Bestandsimport nimmt einen so „bestätigten"
Eintrag mit. Seine eigene Ausgabe zeigt dabei den Widerspruch:

```
Partner    Admin-bestaetigt   q***@x.lu    Unbestätigt
1 Eintrag/Einträge würden übertragen
```

**Die Kehrseite, ebenfalls gemessen:** Nach dem Backfill läuft der **echte**
Bestätigungslink des Nutzers in „bereits bestätigt" und trägt **nichts** ein. Wer
tatsächlich bestätigt, kommt damit nie nach Brevo.

**Ort:** `src/Controller/AdminWaitlistController.php` (`applyStatus()`),
`src/Marketing/MarketingContactRegistry.php` (`recordWaitlistEntry()` prüft
`isConfirmed()`), `src/Command/MarketingImportCommand.php` (Auswahlregel).

**Ursache:** `PartnerWaitlistEntry::$confirmedAt` trägt zwei Bedeutungen — den
eingelösten Double-Opt-In und den Verwaltungs-Backfill — und unterscheidet sie nicht.
Die BF-83-Reparatur umgeht den *Zeitpunkt* des Backfills; sie kann die *Zweideutigkeit*
nicht auflösen.

**Nachweis:** `AdminWaitlistMarketingTest::testBf89AuchDerZweiteStatuswechselErzeugtKeinenKontakt` (rot)

**Unabhängig bestätigt:** Der `code-reviewer`-Durchlauf dieser Prüfung kam ohne Kenntnis
der Messung zum selben Ergebnis, mit derselben Reproduktion und derselben Ursachenanalyse
(`applyStatus()` Zeile 252 gegen den Backfill darunter; `isConfirmed()` ist bloß
`null !== $confirmedAt`). Er weist zusätzlich darauf hin, dass der naheliegende Reflex
„dann die Registry eben nie zweimal rufen" **nicht** trägt: Bei einem *echt* bestätigten
Kontakt soll sie bei jedem Statuswechsel laufen (AK-09).

**Vorschlag:** Die beiden Bedeutungen trennen, statt die Reihenfolge weiter zu
verschieben — etwa über den noch vorhandenen `confirmationToken` (ein eingelöster
Double-Opt-In hinterlässt einen anderen Zustand als ein Backfill) oder ein eigenes Feld.
Das ist eine Entwurfsfrage und berührt B14/B15; sie gehört vor die nächste Reparatur.

> **Behoben 2026-08-29.** Die Entwurfsfrage wurde zuerst beantwortet: Der
> `confirmationToken` taugt **nicht** zur Unterscheidung — er bleibt in beiden Fällen
> stehen (Projektkonvention, damit ein zweiter Klick von einem unbekannten Token
> unterscheidbar bleibt). Es gab schlicht kein Merkmal.
>
> Eingeführt wurde deshalb **`selfConfirmedAt`** an beiden Wartelisten-Entities
> (Migration `Version20260829170000`): den Zeitpunkt, zu dem der Interessent **selbst**
> bestätigt hat. Gesetzt wird es **ausschließlich** von `confirm()`, also vom
> eingelösten Bestätigungslink; der Verwaltungs-Backfill setzt weiterhin nur
> `confirmedAt`. Alle Stellen, die eine belegte Adresse voraussetzen, fragen jetzt
> `hasSelfConfirmed()`: `MarketingContactRegistry::recordWaitlistEntry()`,
> `aktiveQuellen()` und die Auswahlregel von `app:marketing:import`. Der Vorabfilter in
> `applyStatus()` (die erste, halbe Reparatur) ist damit **entfallen** — die Registry
> entscheidet selbst.
>
> **Auch die Kehrseite ist behoben:** `WaitlistConfirmationService::confirm()` prüft für
> „bereits bestätigt" jetzt `hasSelfConfirmed()`. Ein Nutzer, dessen Eintrag der Admin
> zwischenzeitlich weitergesetzt hat, kann seinen Bestätigungslink also noch einlösen.
>
> **Gegenprobe am laufenden System:** vier aufeinanderfolgende Statuswechsel an einem nie
> bestätigten Eintrag → **0 Kontakte**; der echte Link danach → **1 Kontakt**;
> Statuswechsel an einem echt bestätigten Eintrag → `sync_state=pending` (AK-09
> unverändert); der Import listet `NUR-BACKFILL` nicht und `ECHTER-DOI` schon; ein
> Doppelklick bleibt bei einem Kontakt. 674 Tests grün.
>
> ⚠ **Eine Annahme zum Bestand steckt in der Migration:** Sie setzt
> `self_confirmed_at = confirmed_at` für alle bereits bestätigten Einträge. Darunter
> können welche sein, deren `confirmed_at` aus einem Statuswechsel stammt. Vertretbar,
> weil **kein einziger Eintrag heute eine Werbe-Einwilligung trägt** — vor der Migration
> gemessen: 0 in beiden Tabellen. Die Unterscheidung wirkt ab jetzt.

### BF-90 · Verwaiste Zeile nach `contactDeleted` — niedrig

**Betrifft:** keine Zusage direkt; verwandt mit OF-06 (Aufbewahrung)
**Reproduktion:** Zwei Quellen mit derselben Adresse, Zeile `synced`. `contactDeleted`
vom Webhook sperrt die Zeile, lässt die Einwilligung aber stehen (so gewollt seit
BF-84b). Danach widerruft eine Quelle.
**Tatsächlich:** `scheduleRemoval()` findet die verbliebene Quelle als aktiv, `record()`
verweigert wegen der Sperre und gibt `null` zurück — die Zeile bleibt auf `synced`,
obwohl der Kontakt bei Brevo bereits gelöscht ist. Der Sync-Lauf greift sie nicht mehr auf.
**Einordnung:** **Kein Datenabfluss** — bei Brevo steht nichts mehr. Es bleibt ein
lokaler Zustand, der nicht mehr stimmt, und eine Zeile, die niemand aufräumt.
**Vorschlag:** Mit OF-06 zusammen entscheiden; eine Aufräumroutine für gesperrte und
verwaiste Zeilen fehlt dem Projekt ohnehin (dasselbe Thema wie B14/FB-02).

## Hinweise (keine Befunde)

- ⚠️ **Nicht ausgeführt, aus dem Code gefolgert:** Nach einem gefangenen `flush()`-Fehler
  (BF-87-Reparatur) meldet `MarketingSyncService::run()` die **gesamte** Menge als
  `failed`, und der Konsolenbefehl gibt sie als „Fehlgeschlagen: N" aus. Tatsächlich
  waren die Aufrufe bei Brevo zu diesem Zeitpunkt schon erfolgreich — nur die lokale
  Fortschreibung scheiterte. Der Betreiber liest also eine Zahl, die etwas anderes
  bedeutet, als sie sagt. Sachlich unkritisch (Idempotenz, der nächste Lauf holt nach);
  eine Formulierungsfrage. Aus dem `code-reviewer`-Durchlauf, von mir **nicht**
  reproduziert — ein `flush()`-Fehler ließ sich nicht verlässlich erzwingen.
- **Geprüft und unauffällig:** Die Objektidentität in `aktiveQuellen()` trägt in allen
  drei Aufrufwegen (Test `testAusloesendeQuelleWirdAuchNachNeuladenErkannt`); die
  Merkliste `$vorgemerkt` verwirft nach einem `clear()` korrekt und wächst beim heutigen
  Bestand nicht bedenklich.
- Die Hinweise des ersten Durchlaufs gelten unverändert: der 405er auf den
  Organisations-Unterseiten (OF-07, ein B15-Fehler), das vorbestehend rote
  `lint:container`, das fehlende `make fix-check`.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Integration/Marketing/MarketingLoeschmatrixTest.php` | 6 | Löschsemantik bei geteilter Adresse in beiden Reihenfolgen; stehender Löschauftrag; Quellen ohne Einwilligung und unbestätigte Quellen; Objektidentität der auslösenden Quelle |
| `AdminWaitlistMarketingTest::testBf89…` | 1 | **BF-89** — rot, bis die Ursache behoben ist |

## Nächster Schritt

`/sdd-build 04` mit **BF-89**. **BF-88** braucht weiterhin die Betreiberentscheidung zu
OF-01, **BF-90** gehört mit OF-06 zusammen entschieden.

Vor der Reparatur von BF-89 ist eine Entwurfsfrage zu klären, keine Codezeile: Wie
unterscheidet das Projekt einen eingelösten Double-Opt-In von einem
Verwaltungs-Backfill? Solange beide dasselbe Feld setzen, verschiebt jede Reparatur den
Fehler nur um eine Runde.
