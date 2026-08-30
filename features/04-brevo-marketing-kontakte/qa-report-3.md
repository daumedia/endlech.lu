# 04 · Marketing-Kontakte in Brevo — Testbericht, dritter Durchlauf

Stand: 2026-08-29 · Geprüft gegen `spec.md` vom 2026-08-29 · Vorlauf: `qa-report.md`, `qa-report-2.md`

## Fazit

**Production-ready: nein** — *Stand der Prüfung. BF-91 und BF-92 wurden am 2026-08-30 behoben und gegengeprüft (Vermerke unten); BF-88 und BF-90 bleiben offen. Die vierte Abnahme steht aus (`/sdd-qa 04`).*

**BF-89 ist behoben, und zwar an der Ursache.** `selfConfirmedAt` trennt den eingelösten
Double-Opt-In vom Verwaltungs-Backfill; AK-05 hält jetzt auf allen drei Wegen —
Statuswechsel (fünf hintereinander geprüft), Bestandsimport und Registry. Damit ist das
Kriterium erstmals erfüllt, an dem dieses Feature zweimal gescheitert ist.

**Die Reparatur hat dabei einen neuen Fehler eingeführt.** Seit `confirm()` nicht mehr an
`isConfirmed()` scheitert, läuft es bei einem verwaltungsseitig weitergesetzten Eintrag
tatsächlich durch — und `PartnerWaitlistEntry::confirm()` setzt neben den Zeitstempeln
auch `status = CONFIRMED`. Ein **gewonnener Kunde fällt auf „bestätigt" zurück**, wenn
der Interessent innerhalb der Linkfrist seinen alten Bestätigungslink anklickt. Gemessen:
`converted` → `confirmed`. Das ist **BF-91**, Grad hoch. Es bleibt nicht bei der
Verwaltung: Der Rückfall wandert mit nach Brevo (`FUNNEL_STATUS = confirmed`), und damit
**fällt AK-08 durch** — eine Kampagne, die gewonnene Häuser ausschließt, erreicht sie
wieder. Dazu bekommt das Team erneut eine „Neue Anmeldung"-Meldung für einen
abgeschlossenen Vorgang.

Nächster Schritt: `/sdd-build 04` mit BF-91. Der Fix ist absehbar klein — `confirm()`
darf einen fortgeschrittenen Status nicht überschreiben —, aber er gehört geprüft und
nicht geraten: Es ist die dritte Reparatur an derselben Stelle.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 48 von 48 |
| davon bestanden | **42** *(unverändert — AK-05 kam hinzu, AK-08 fiel weg)* |
| davon durchgefallen | 2 *(AK-08 neu, AK-33 unverändert)* |
| **nicht prüfbar** | 4 *(unverändert)* |
| Edge Cases belegt | 6 von 6 |
| Tests neu in diesem Durchlauf | 1 |
| Gesamtsuite | 675 Tests, **1 rot** — der Nachweis für BF-91 |
| Befunde offen | BF-91 (hoch), BF-88 (mittel), BF-90 (niedrig), BF-92 (niedrig) |

## Stand der Befunde

| ID | Grad | Ergebnis | Gegenprobe |
|---|---|---|---|
| BF-83 → BF-89 | kritisch | ✅ **behoben** | Fünf aufeinanderfolgende Statuswechsel an einem nie bestätigten Eintrag → **0 Kontakte**; Bestandsimport listet ihn nicht; echter Link danach → 1 Kontakt |
| BF-84 / 84b | kritisch | ✅ behoben (QA²) | Zustandsmatrix unverändert grün |
| BF-85 | mittel | ✅ behoben (QA²) | unverändert |
| BF-86 | hoch | ✅ behoben (QA²) | unverändert |
| BF-87 | mittel | ✅ behoben (QA²) | unverändert |
| BF-88 | mittel | ⏸️ offen | AV-Vertrag weiterhin ohne Prüfdatum; hängt an OF-01 |
| BF-90 | niedrig | ⏸️ offen | bewusst zurückgestellt, gehört mit OF-06 entschieden |
| **BF-91** | **hoch** | ❌ **neu** | siehe unten — durch die BF-89-Reparatur eingeführt |

## Neu bewertete Akzeptanzkriterien

| AK | QA² | Jetzt | Nachweis |
|---|---|---|---|
| AK-05 | ❌ (BF-89) | ✅ **bestanden** | `AdminWaitlistMarketingTest::testBf89…`, `MarketingBefundeTest::testBf89BackfillSetztKeineSelbstbestaetigung`, `MarketingImportCommandTest::testBf89ImportNimmtBackfillEintraegeNichtMit`; zusätzlich am laufenden System: fünf Statuswechsel → 0 Kontakte, Import listet ihn nicht |
| AK-08 | ✅ | ❌ **durchgefallen** | siehe **BF-91**, dritter Effekt — `FUNNEL_STATUS` fällt auf `confirmed` zurück, eine Kampagne erreicht den gewonnenen Kunden wieder |
| AK-33 | ❌ (BF-88) | ❌ **weiterhin** | AV-Vertrag ohne Prüfdatum |

Die übrigen Kriterien wurden über die Regression bestätigt (675 Tests) und in den
sicherheitsrelevanten Punkten erneut aktiv geprüft.

## Sicherheitsprüfung (Wiederholung)

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| 2 · Zugriffsregeln | bestanden | Webhook ohne Token → **401**, falscher Token → **401**; `/de/admin/warteliste` anonym → **302** |
| 5 · PII an externe Dienste | bestanden | Payload erneut abgefangen: genau fünf Attribute, Negativliste vollständig eingehalten, `emailBlacklisted` nicht enthalten |
| 6 · Geheimnisse | bestanden | `.env` führt alle drei Werte leer |
| 8 · Löschen | bestanden | Zustandsmatrix aus QA² unverändert grün (6 Tests) |
| Rate Limit, PII in Logs | bestanden (QA²) | Die Reparatur berührt weder Endpunkte noch Protokollstellen |

## Geprüfte Punkte aus dem Reparaturbericht

Der Abschlussbericht nannte drei Annahmen. Alle drei wurden geprüft:

- **Die Datenmigration** (`self_confirmed_at = confirmed_at` für den Bestand) ist auf
  Produktion **strukturell** sicher, nicht nur gemessen: Beide Migrationen dieses
  Features gehen zusammen live (`Version20260829120000` vor `…170000`), und die Spalte
  `marketing_consent_at` entsteht erst in der früheren. Zum Zeitpunkt der Datenmigration
  kann also gar kein Eintrag eine Werbe-Einwilligung tragen. Lokal zusätzlich vorher
  gemessen: 0 in beiden Tabellen.
- **Die Migration ist umkehrbar.** `down` und erneutes `up` ausgeführt, danach
  `doctrine:schema:update --dump-sql` ohne Treffer auf `self_confirmed_at`.
- **Alle Prüfstellen sind konsistent umgestellt.** `grep` über `src/` und `templates/`:
  `hasSelfConfirmed()` in Registry, `aktiveQuellen()`, Import und
  `WaitlistConfirmationService`; `isConfirmed()` nur noch im Backfill selbst, wo es
  richtig ist.

## Fehler

### BF-91 · Der Bestätigungsklick setzt einen fortgeschrittenen Vertriebsstatus zurück — hoch

**Betrifft:** die Integrität von B14/B15 · **eingeführt durch die BF-89-Reparatur**

**Reproduktion:**
1. Partner-Formular absenden, Bestätigungsmail **nicht** anklicken
2. Als Admin den Vertriebsweg gehen: „Kontaktiert" → „Qualifiziert" → **„Gewonnen"**
3. Der Interessent klickt jetzt seinen Bestätigungslink aus der ursprünglichen Mail

**Erwartet:** Der Status bleibt „Gewonnen"; die Selbstbestätigung wird festgehalten.
**Tatsächlich:**

```
Nach dem Vertriebsweg:                      status=converted
Nach dem Klick auf den Bestätigungslink:    status=confirmed
```

**Zeitfenster, gemessen:** Solange der Link gültig ist — 7 Tage ab Anmeldung. Danach
greift `isExpired()` und der Status bleibt unangetastet:

```
 1 Tag  alt -> 'confirmed', Status danach: confirmed   ← Rückfall
 6 Tage alt -> 'confirmed', Status danach: confirmed   ← Rückfall
 8 Tage alt -> 'expired',   Status danach: converted
30 Tage alt -> 'expired',   Status danach: converted
```

Das Fenster ist realistisch: Ein Admin telefoniert am Tag nach der Anmeldung, der
Interessent liest seine Mail zwei Tage später.

**Zweiter Effekt derselben Ursache, ebenfalls gemessen:** Der Controller ruft bei
`RESULT_CONFIRMED` zusätzlich `notifyTeam()`. Das Team bekommt damit erneut eine
Meldung „**Neue Partner-Anmeldung: Schon bearbeitet**" für einen Vorgang, den es längst
abgeschlossen hat (gemessen mit aktiviertem Profiler: 2 Nachrichten im Postausgang).

⚠ **Dritter Effekt — und er verletzt ein Kriterium dieses Features.** Nach dem
Statusrückfall läuft `recordWaitlistEntry()` mit dem nun falschen Stand: Der Kontakt geht
mit `FUNNEL_STATUS = confirmed` nach Brevo, obwohl der Vorgang gewonnen war. Eine
Kampagne fürs Partnerprogramm, die `converted` ausschließt, **erreicht ihn wieder** —
genau das schließt AK-08 aus. Gemessen am Payload:

```
Nach „gewonnen":                     FUNNEL_STATUS=converted
Nach dem Klick (echter DOI):         FUNNEL_STATUS=converted   ← abgefangen
Nach dem Klick (nach Backfill):      FUNNEL_STATUS=confirmed   ← Rückfall
```

Damit ist BF-91 nicht nur eine Regression an B14/B15, sondern lässt **AK-08 durchfallen**.

**Ort:** `src/Entity/PartnerWaitlistEntry.php` und
`src/Entity/OrganisationWaitlistEntry.php` — `confirm()` setzt neben `confirmedAt` und
`selfConfirmedAt` auch `status = WaitlistStatus::CONFIRMED`. Vor der BF-89-Reparatur
wurde diese Methode in dieser Lage nie erreicht: `WaitlistConfirmationService::confirm()`
stieg vorher mit `RESULT_ALREADY` aus. Die Folgewirkung sitzt in
`src/Controller/PartnerController.php` und `OrganisationController.php` (`notifyTeam()`
im `RESULT_CONFIRMED`-Zweig).

**Nachweis:** `MarketingBefundeTest::testBf91BestaetigungSetztDenVertriebsstatusNichtZurueck` (rot)

**Unabhängig bestätigt:** Der `code-reviewer`-Durchlauf kam über reine Codeanalyse zum
selben Fund, mit derselben Reproduktion — und wies zusätzlich auf den dritten Effekt hin
(`FUNNEL_STATUS`), den ich daraufhin gemessen habe. Er hält ausdrücklich fest, dass die
Zeile `$this->status = WaitlistStatus::CONFIRMED` unverändert alt ist und **durch die
Reparatur erstmals erreichbar** wurde. Alle übrigen Aspekte der Reparatur (Migration,
Vollständigkeit der Umstellung, Reihenfolge gegenüber `isExpired()`) hat er als
unauffällig geprüft.

**Vorschlag:** `confirm()` soll den Status nur dann auf `CONFIRMED` setzen, wenn er noch
`PENDING` ist — ein fortgeschrittener Stand ist die jüngere Information. Die
Zeitstempel (`confirmedAt`, `selfConfirmedAt`) sollen weiterhin gesetzt werden: Die
Selbstbestätigung ist eingetreten und gehört festgehalten. Für den zweiten Effekt ist
zu entscheiden, ob `notifyTeam()` überhaupt laufen soll, wenn der Vorgang beim Team
bereits bekannt war. ⚠ Beides berührt B14/B15 und gehört dort gegengeprüft.

> **Behoben 2026-08-30.** `confirm()` setzt den Status in beiden Entities **nur noch aus
> `PENDING` heraus**; die Zeitstempel werden weiterhin unbedingt gesetzt. Für den zweiten
> Effekt merken sich beide Bestätigungs-Controller **vor** dem Aufruf, ob der Vorgang
> überhaupt neu war (`$warNeu`), und rufen `notifyTeam()` nur dann — ohne neuen
> Rückgabewert und ohne Template-Änderung. Der dritte Effekt (`FUNNEL_STATUS`) löst sich
> damit von selbst: Wenn der Status nicht zurückfällt, geht auch nichts Falsches nach
> Brevo.
>
> **Gegenprobe am laufenden System**, ein Eintrag auf `converted` mit gültigem Link:
> Status bleibt `converted` · 0 Team-Meldungen · `FUNNEL_STATUS = converted` ·
> `self_confirmed_at` ist gesetzt · der Kontakt entsteht (AK-05). Der **Normalfall** ist
> unberührt: Status → `confirmed`, genau 1 Team-Meldung
> (`MarketingConsentTest::testNormaleBestaetigungBenachrichtigtDasTeamWeiterhin`).
> 677 Tests grün.
>
> ⚠ **Eine Korrektur an diesem Bericht:** Die Angabe „2 Nachrichten im Postausgang" oben
> stammt aus dem Profiler-Collector, der Envelope und Nachricht getrennt zählt.
> `assertEmailCount()` zählt **eine** Mail. Am Befund ändert das nichts (0 gegenüber ≥1),
> an der Zahl schon.

### BF-92 · `docs/data-model.md` führt Feature 04 nicht — niedrig

**Betrifft:** keine Zusage der Spec; die Konvention aus `CLAUDE.md`
**Gemessen:** Die Datei nennt **keinen** der neuen Bestandteile — `MarketingContact`,
`marketing_contact`, `marketingConsentAt`, `self_confirmed_at`: je 0 Treffer.
**Warum das zählt:** `CLAUDE.md` schreibt vor: „Bei Änderungen am Datenmodell oder an den
Komponenten-Mustern die passende Datei mitziehen — sonst laufen Code und Referenz
auseinander." `docs/data-model.md` ist die vollständige Feldreferenz des Projekts; eine
neue Tabelle plus vier neue Spalten fehlen darin.
**Einordnung:** Blockiert nicht. Aufgefallen im `code-reviewer`-Durchlauf, von mir
nachgemessen. Betrifft das Feature insgesamt, nicht die Reparatur.

> **Behoben 2026-08-30.** `docs/data-model.md` führt jetzt die Entity `MarketingContact`
> mit vollständiger Feldreferenz, Indizes und den beiden ⚠-Begründungen (kein
> Fremdschlüssel, kein Freitextfeld), dazu `marketingConsentAt` an allen drei Quellen,
> `selfConfirmedAt` an beiden Wartelisten, die Enums `MarketingOrigin` und
> `MarketingSyncState` sowie beide Migrationen in der Historie.
>
> ⚠ **Dabei ist ein vorbestehender Rückstand sichtbar geworden:** Die
> Migrations-Historie führte 26 Einträge bei 34 Dateien — **sechs Migrationen vom 24./25.
> August aus Feature `01` und `02` fehlen**. Sie wurden **nicht** nachgetragen (fremde
> Features, eigener Auftrag), sondern in der Datei ausdrücklich als Lücke vermerkt.

## Hinweise (keine Befunde)

- **Zweite Verhaltensänderung derselben Umstellung, harmlos:** Ein verwaltungsseitig
  bestätigter Eintrag mit **abgelaufenem** Link meldet jetzt „Link abgelaufen" statt
  „bereits bestätigt" (gemessen, siehe Tabelle oben). Beide Meldungen sind für den Leser
  plausibel; keine Zusage ist berührt.
- **`templates/admin/waitlist/_timestamps.html.twig` zeigt weiterhin `confirmedAt`** als
  „Bestätigt am". Der Admin sieht damit nicht, ob ein echter Double-Opt-In dahintersteht
  oder sein eigener Backfill. Die Spec verlangt es nicht — aber es ist genau die
  Unterscheidung, die dieses Feature zweimal Geld gekostet hat, und sie ist jetzt in den
  Daten vorhanden.
- Unverändert: OF-07 (405 auf den Organisations-Unterseiten, ein B15-Fehler), das
  vorbestehend rote `lint:container`, das fehlende `make fix-check`.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `MarketingBefundeTest::testBf91…` | 1 | **BF-91** — rot, bis die Ursache behoben ist |

Aus dem Reparaturvorgang kamen zusätzlich drei Regressionen hinzu, die hier bestätigt
wurden: `testBf89BackfillSetztKeineSelbstbestaetigung`,
`testBf89EchterLinkBleibtNachEinemBackfillEinloesbar`,
`MarketingImportCommandTest::testBf89ImportNimmtBackfillEintraegeNichtMit`.

## Nächster Schritt

`/sdd-build 04` mit **BF-91**. **BF-88** braucht die Betreiberentscheidung zu OF-01,
**BF-90** gehört mit OF-06 zusammen entschieden.

⚠ **Das ist die dritte Reparatur an derselben Stelle.** Die ersten beiden Male hat je
eine Reparatur die nächste Lücke erzeugt — erst durch Verschieben der Reihenfolge
(BF-83 → BF-89), jetzt durch das Öffnen eines Pfades, der vorher nie erreicht wurde
(BF-89 → BF-91). Vor der nächsten Änderung an `confirm()` gehört geprüft, **was diese
Methode sonst noch anfasst** und wer sich darauf verlässt.
