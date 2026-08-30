# 04 · Marketing-Kontakte in Brevo — Testbericht, vierter Durchlauf

Stand: 2026-08-30 · Geprüft gegen `spec.md` vom 2026-08-29 · Vorlauf: `qa-report.md`, `qa-report-2.md`, `qa-report-3.md`

## Fazit

**Production-ready: ja — mit einer Sperre, die vor der Inbetriebnahme fällt.**

Der erste Durchlauf ohne neuen Befund. BF-91 und BF-92 sind behoben und gegengeprüft;
**AK-08 hält wieder, AK-05 weiterhin.** Damit bleibt genau ein durchgefallenes Kriterium
— AK-33, der undatierte AV-Vertrag —, und das ist keine Softwarefrage, sondern eine
Betreiberentscheidung (BF-88, Grad mittel). Nach den Regeln der Kette blockiert das die
Auslieferung nicht.

**Was es blockiert, ist die Inbetriebnahme.** Der Code darf ausgeliefert werden: Ohne
gesetzten Schlüssel ist die Funktion still aus, es geht kein Kontakt hinaus, und die
Einwilligungen sammeln sich im Auftragsbuch. Bevor der erste echte Lauf stattfindet,
müssen **T08** (die fünf Attribute im Brevo-Konto) und **BF-88** (AV-Vertrag, OF-01)
stehen — beides ist in `tasks.md` als Freigabe-Sperre geführt und dort abzuhaken.

Nächster Schritt: `/sdd-deploy 04`.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 48 von 48 |
| davon bestanden | **43** *(zuvor 42)* |
| davon durchgefallen | **1** *(zuvor 2)* |
| **nicht prüfbar** | 4 *(unverändert)* |
| Edge Cases belegt | 6 von 6 |
| Tests neu in diesem Durchlauf | 4 |
| Gesamtsuite | **681 Tests, alle grün** |
| Befunde offen | BF-88 (mittel), BF-90 (niedrig) |

## Stand der Befunde

| ID | Grad | Ergebnis | Gegenprobe |
|---|---|---|---|
| BF-83 → BF-89 | kritisch | ✅ behoben (QA³) | unverändert grün |
| BF-84 / 84b | kritisch | ✅ behoben (QA²) | Zustandsmatrix unverändert grün |
| BF-85, BF-86, BF-87 | mittel–hoch | ✅ behoben (QA²) | unverändert |
| **BF-91** | **hoch** | ✅ **behoben** | Zustandsmatrix über **alle sechs** Status, beide Wartelisten — siehe unten |
| **BF-92** | niedrig | ✅ **behoben** | `docs/data-model.md` deckt sich spaltengenau mit der Datenbank |
| BF-88 | mittel | ⏸️ offen | AV-Vertrag weiterhin ohne Prüfdatum; hängt an OF-01 |
| BF-90 | niedrig | ⏸️ offen | bewusst zurückgestellt, gehört mit OF-06 entschieden |

## Die Zustandsmatrix von `confirm()`

An dieser Methode ist das Feature dreimal gescheitert. Deshalb wurde sie diesmal **nicht
am Einzelfall** geprüft, sondern über alle sechs Ausgangszustände, jeweils bis in das
Brevo-Attribut:

| Ausgangsstatus | Ergebnis | Status danach | Kontakt | `FUNNEL_STATUS` | Team-Meldung |
|---|---|---|---|---|---|
| `pending` | `confirmed` | **`confirmed`** | ja | `confirmed` | **ja** |
| `confirmed` | `confirmed` | `confirmed` | ja | `confirmed` | nein |
| `contacted` | `confirmed` | `contacted` | ja | `contacted` | nein |
| `qualified` | `confirmed` | `qualified` | ja | `qualified` | nein |
| `converted` | `confirmed` | `converted` | ja | `converted` | nein |
| `declined` | `confirmed` | `declined` | ja | `declined` | nein |

Nur aus `pending` heraus wechselt der Status; jeder fortgeschrittene Stand bleibt. Der
Kontakt entsteht in allen Fällen — richtig, denn die Person hat eingewilligt **und**
bestätigt; die Ablehnung betrifft das Partnerprogramm, nicht die Werbung, und
`FUNNEL_STATUS` erlaubt das Filtern. Die Team-Meldung geht genau dann hinaus, wenn der
Vorgang neu ist.

⚠ **Eine Lücke in meiner eigenen Prüfung, nachgeholt:** Die ersten drei Durchläufe haben
fast alles am **Partner**-Weg gemessen. `confirm()` steht in beiden Entities getrennt —
eine Reparatur, die nur eine trifft, wäre nicht aufgefallen. Der Organisations-Weg ist
jetzt einzeln geprüft (Statuserhalt, Selbstbestätigung, `FUNNEL_STATUS`, `ORIGIN=COMMUNE`,
BF-89 über zwei Statuswechsel, keine Team-Meldung) und als Test festgehalten.

## Neu bewertete Akzeptanzkriterien

| AK | QA³ | Jetzt | Nachweis |
|---|---|---|---|
| AK-05 | ✅ | ✅ **weiterhin** | Fünf Statuswechsel ohne Double-Opt-In → 0 Kontakte; Bestandsimport listet ihn nicht. Am Organisations-Weg gegengeprüft: zwei Statuswechsel → 0 Kontakte |
| AK-08 | ❌ (BF-91) | ✅ **bestanden** | Zustandsmatrix oben; zusätzlich am laufenden System: Statuswechsel eines echt bestätigten Eintrags → `FUNNEL_STATUS` folgt (`contacted` → `qualified` → `converted`), `sync_state` je `pending` |
| AK-09 | ✅ | ✅ **weiterhin** | derselbe Nachweis |
| AK-33 | ❌ (BF-88) | ❌ **weiterhin** | AV-Vertrag ohne Prüfdatum |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| 1 · Fremder Zugriff (IDOR) | bestanden | Admin-Detailrouten anonym → 302; als `ROLE_USER` → 403 |
| 2 · Zugriffsregeln | bestanden | Webhook ohne Token → **401**, falscher Token → **401** |
| 3 · Rate Limit | bestanden | Erneut überrannt: Aufruf 120 → 401, **121 → 429**. Grenzwert exakt |
| 4 · PII in Protokollen | bestanden | Drei Protokollstellen im Feature, **keine** mit Adresse (`grep`); `doctrine`-Kanal in `prod` ausgeschlossen |
| 5 · PII an externe Dienste | bestanden | Payload erneut abgefangen: genau fünf Attribute, Negativliste (`message`, `phone`, `locality`, `source`, `token`, `emailBlacklisted`) vollständig eingehalten |
| 6 · Geheimnisse | bestanden | `.env` führt alle drei Werte leer; kein Brevo-Bezug in `public/build/` |
| 7 · Eingaben | bestanden | Bestätigungsendpunkt mit 64-Zeichen-Phantomtoken, zu kurzem Token, Pfad-Traversal (`..%2F..%2Fetc%2Fpasswd`): durchweg **404**, kein Serverfehler. Beide Wartelisten |
| 8 · Löschen | bestanden | Zustandsmatrix aus QA² unverändert grün |

## Geprüfte Punkte aus dem Reparaturbericht

- **Unterdrückt die neue Bedingung zu viel?** Nein — die Matrix oben deckt alle sechs
  Zustände ab. Der Normalfall (`pending`) setzt den Status und meldet dem Team; alles
  andere lässt beides in Ruhe.
- **`docs/data-model.md` gegen den Code**: Die dokumentierten Spalten von
  `marketing_contact` decken sich **spaltengenau** mit `information_schema` (15 zu 15),
  ebenso die beiden Indizes (`UNIQ_E78FBDB7E7927C74`, `IDX_marketing_contact_state_updated`).
- **Der vermerkte Rückstand stimmt**: 34 Migrationsdateien, 28 Einträge in der Historie,
  sechs fehlende aus Feature `01`/`02` — in der Datei als Lücke benannt und bewusst nicht
  gefüllt.

## Fehler

**Keine neuen.** Das ist der erste Durchlauf an diesem Feature ohne Fund — und er kam
zustande, weil diesmal die vollständige Zustandsmatrix geprüft wurde statt des Falls, um
den es gerade ging.

**Unabhängig bestätigt:** Der `code-reviewer`-Durchlauf hat dieselben sechs Zustände
einzeln durchgespielt, dazu die Randfälle „`$entry` ist null", „Token ungültig",
„`RESULT_ALREADY`" und „Admin setzt den Status auf `pending` zurück" — und meldet
**keinen Fund über der Meldeschwelle**. Er hebt zwei Punkte hervor, die ich bestätigen
kann: Der Guard in `confirm()` und die `$warNeu`-Momentaufnahme im Controller lesen
**denselben** Status, es gibt also keinen Zwischenzustand, in dem beide auseinanderlaufen;
und `notifyTeam()` ist zusätzlich durch `state === RESULT_CONFIRMED` abgesichert, was
`RESULT_INVALID`, `RESULT_EXPIRED` und `RESULT_ALREADY` sicher ausschließt. Auch die
Angaben in `docs/data-model.md` hat er gegen Entity und Migration abgeglichen.

*Ein Hinweis von ihm habe ich geprüft und verworfen:* Die Assertion-Meldung
`assertEmailCount(1, null, 'Der normale Ablauf benachrichtigt das Team nicht mehr')`
liest sich isoliert widersprüchlich. Sie erscheint aber nur **im Fehlerfall**, und dann
ist die Aussage zutreffend — so beschreiben Assertion-Meldungen per Konvention, was
schiefging. Keine Änderung.

Weiterhin offen, beide **ohne** Softwareanteil:

- **BF-88** (mittel) — der AV-Vertrag mit Brevo ist in `docs/datenschutz.md` als „noch zu
  prüfen" vermerkt; das Prüfdatum fehlt. Hängt an **OF-01**, der Datenschutzstufe, die das
  PRD nie festgelegt hat. Betreiberentscheidung.
- **BF-90** (niedrig) — eine verwaiste Zeile nach `contactDeleted`. Kein Datenabfluss;
  gehört mit **OF-06** (Aufbewahrungsfrist) zusammen entschieden.

## Nicht prüfbar — und was daran hängt

Vier Kriterien bleiben unprüfbar, alle aus demselben Grund: **Das Brevo-Konto ist nicht
eingerichtet** (Aufgabe T08, ein Eingriff in ein Produktivkonto).

| AK | Was fehlt |
|---|---|
| AK-07 | Ob die fünf Attribute ankommen. Brevo verwirft unbekannte **stillschweigend** — ein Sync ohne T08 meldet Erfolg und überträgt nur die nackte Adresse |
| AK-10 | Die 15-Minuten-Frist; der Cron ist dokumentiert, aber nicht eingerichtet |
| AK-24 | Die erste Kampagne; der Text liegt als Entwurf vor, die Kampagne ist nicht angelegt |
| AK-27 | Die Gegenprobe der Kontaktzahl in Brevo |

Das ist kein Mangel am Code, aber es heißt: **Nach der Auslieferung ist das Feature noch
nicht abgenommen im Sinne von „es tut, was es soll".** Der Nachweis dafür kann erst am
eingerichteten Konto entstehen.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Integration/Waitlist/BestaetigungStatusMatrixTest.php` | 4 | Die vollständige Matrix: Statuserhalt über alle sechs Zustände, Selbstbestätigung, AK-08 bis ins Brevo-Attribut, und derselbe Fall für den **Organisations**-Weg |

Aus dem Reparaturvorgang bestätigt: `MarketingConsentTest::testBf91SpaeteBestaetigungBenachrichtigtDasTeamNicht`
und `::testNormaleBestaetigungBenachrichtigtDasTeamWeiterhin`.

## Nächster Schritt

**`/sdd-deploy 04`.**

Vor der **Inbetriebnahme** — nicht vor der Auslieferung — ist die Freigabe-Sperre aus
`tasks.md` (T39) abzuarbeiten:

1. **BF-88 / OF-01** — Datenschutzstufe entscheiden, AV-Vertrag prüfen und datieren
2. **T08** — die fünf Attribute, die Liste und der Webhook im Brevo-Konto
3. erst dann `app:marketing:import --commit` bzw. der erste Cron-Lauf mit echtem Schlüssel

**BF-90** und die offenen Fragen **OF-01 bis OF-09** bleiben zur Entscheidung beim
Betreiber.
