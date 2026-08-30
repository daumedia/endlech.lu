# Befunde — projektweit

Stand: 2026-08-30 · Quelle: die `qa-report.md` aller geprüften Features

Diese Liste wird von `sdd-qa` fortgeschrieben, nicht von Hand. Sie ist die Grundlage
des Auditberichts, den `/sdd-erfassen abschluss` daraus baut.

**Geprüft bisher:** B01 (17/20), B02 (16/17), B03 (16/20), B04 (23/24 im zweiten
Durchlauf), B23 (34/35 im zweiten Durchlauf), B19 (17/17, davon eines nicht prüfbar),
B14 (28/28), B15 (27/27), B22 (30/30), B17 (25/25) — abgenommen.
B10 (24/24 im zweiten Durchlauf), B18 (29/29), B11 (18/19, eines nicht prüfbar), B20 (19/20), B21 (20/20), B09 (18/18), B05 (24/24), **B06 (23/23 — das erste Feature ohne eigenen Befund)**, B07 (17/17, eines nicht ausgeführt), B08 (16/16) — abgenommen.
B12 (15/15 nach der Reparatur), B13 (14/14), B16 (29/29), B24 (16/16) — abgenommen.

**2026-08-29 · Feature `04`, zweiter Durchlauf: 42/48 bestanden.** Fünf der sechs
Befunde sind behoben und gegengeprüft, darunter der schwerste (BF-84). **BF-83 war nur
zur Hälfte behoben** — fortgeführt als BF-89 (kritisch), unabhängig von Messung und
`code-reviewer` bestätigt. **BF-89 wurde am selben Tag behoben**, diesmal an der Ursache:
`selfConfirmedAt` trennt den eingelösten Double-Opt-In vom Verwaltungs-Backfill. Offen
bleiben BF-88 (Betreiberentscheidung) und BF-90 (niedrig).

**2026-08-30 · Feature `04`, vierter Durchlauf: 43/48 — der erste ohne neuen Befund.**
BF-91 und BF-92 behoben, **AK-08 hält wieder**, 681 Tests grün. Geprüft wurde diesmal die
**vollständige Zustandsmatrix** von `confirm()` über alle sechs Ausgangszustände und
**beide** Wartelisten — die ersten drei Durchläufe hatten fast alles nur am Partner-Weg
gemessen. Offen bleiben allein BF-88 und BF-90, beide ohne Softwareanteil. Der Code ist
auslieferbar; die **Inbetriebnahme** hängt an T08 (Brevo-Konto) und BF-88.

**2026-08-29 · Feature `04`, dritter Durchlauf: 43/48 bestanden.** BF-89 ist behoben —
**AK-05 hält erstmals auf allen drei Wegen**. Die Reparatur führte aber **BF-91** ein
(hoch): Der Bestätigungsklick setzt einen fortgeschrittenen Vertriebsstatus zurück, weil
`confirm()` jetzt einen Pfad erreicht, der vorher nie offen war — und der Rückfall wandert
bis nach Brevo, womit **AK-08 durchfällt**. Dritte Runde an derselben Stelle. Dazu BF-92
(niedrig): `docs/data-model.md` führt Feature 04 überhaupt nicht. **Beide wurden am
2026-08-30 behoben** — die vierte Abnahme steht aus.

**2026-08-29 · Feature `04` geprüft (erster Durchlauf): 41/48 bestanden, 3 durchgefallen, 4 nicht prüfbar.**
Nicht abgenommen — zwei kritische Befunde (BF-83, BF-84) und einer hoher (BF-86). Die
vier nicht prüfbaren Kriterien hängen alle am selben Punkt: Das Brevo-Konto ist nicht
eingerichtet (AK-07, AK-10, AK-24, AK-27) — ohne Kontozugang lässt sich weder messen, ob
die Attribute ankommen, noch die Kontaktzahl gegenprüfen.

**2026-08-25 · Alle 72 Befunde sind behoben.** Der Durchgang lief über zehn
Blöcke; die Reihenfolge folgte dem Schweregrad, nicht der Feature-Nummer. Dabei
sind vier Muster geschlossen worden, die je mehrere Features betrafen:

| Muster | Vorkommen | Antwort |
|---|---|---|
| M-01 · Ungedeckelte Wege | 7 | `ActionLimiter` plus sechs neue Limiter; `LimiterCoverageTest` prüft die Konvention |
| Fehlende Eingabeprüfung → 500 | 4 | `empty_data`, Längen- und Slug-Kürzung |
| Fehlende Übersetzungen | 2 | Scanner über 736 Template-Schlüssel und 187 Formularangaben |
| Betroffenenrechte | 3 | Feature `01` |

Zwei davon fielen erst beim Reparieren auf: ein zweiter 500er im
Vorschlags-Assistenten (beim Messen des Rate-Limits) und eine Lücke im eigenen
Katalog-Scanner, der Beschriftungen nicht erfasste.

**Alle Reparaturen liegen auf `dev` bzw. `fix/befunde-abarbeiten` und sind noch
nicht auf `production`.**

**Zuordnung von Befunden:** Ein Befund steht bei dem Feature, in dem er **behoben** wird
— nicht bei dem, in dem er gefunden wurde. BF-04 wurde in B01 gefunden und ist seit
2026-08-23 dem Feature `01` zugeordnet; sonst hielte er B01 dauerhaft auf `review`,
obwohl dort nichts mehr zu reparieren ist.

> Nicht zu verwechseln mit `features/fehlbestand-uebersicht.md`. Jene sammelt, was beim
> **Lesen** des Codes auffiel, und ist eine Suchliste. Hier stehen nur Befunde, die in
> einer QA **ausgeführt und belegt** wurden.

**2026-08-30 · Feature `05` geprüft (erster Durchlauf): 32/44 bestanden, 5 durchgefallen,
7 nicht prüfbar.** Nicht abgenommen — zwei Befunde mit Grad *hoch*. **Alle zwölf offenen
Kriterien hängen an den drei Vorbedingungen der Spec** (Vektormarken, Postfach,
Betreiberangaben); nur einer der vier Befunde hat überhaupt einen Codeanteil (BF-95). Der
Angriffsdurchlauf blieb ohne Fund, axe-core meldet in allen vier Sprachfassungen null
Verstöße.

⚠ **Der 768-px-Überlauf war bereits als BF-80 erfasst.** Er wurde beim Selbsttest von `05`
erneut gefunden und dort zunächst als neuer Befund geführt — falsch. Die Messung ist
trotzdem ein Gewinn: Sie zeigt, dass der Fehler **angemeldet doppelt so groß ist und bis
unter 1000 px reicht**. BF-80 ist entsprechend ergänzt statt verdoppelt.

**2026-08-30 · Feature `05`, zweiter Durchlauf: 37/44, kein neuer Befund.** Beide
Reparaturen halten der Gegenprobe stand. Der Ertrag liegt in der Methode: Statt auf VB-01
zu warten, wurde der **vollständige Zustand hergestellt** (SVG-Platzhalter,
`app:press:package`) — damit lief die gesamte Materialmechanik zum ersten Mal durch und
**sechs vormals offene Kriterien sind belegt**. In diesem Zustand meldete die Suite 10
statt 13 übersprungene Tests. Alle Prüf-Artefakte wurden wieder entfernt. Offen bleiben
BF-93 bis BF-96 — **keiner davon hat einen Softwareanteil**.

**2026-08-30 · Feature `05`, dritter Durchlauf: 42/44 — production-ready.** Die drei
Vorbedingungen sind erfüllt; **fünf Kriterien wechselten von offen auf bestanden**, und
**kein Prüflauf dieses Features überspringt mehr**. BF-93, BF-94 und BF-96 sind behoben.
Neu ist allein **BF-99** (mittel, kein Codeanteil): Der Schriftzug der Wort-Bildmarken ist
noch nicht in Pfade umgewandelt.

⚠ **AK-11 bleibt durchgefallen, und das ist eine Entscheidung** (OF-04): Es wird keine
Anschrift veröffentlicht. Das Impressum zitiert weiterhin § 5 TMG / Art. 11 Loi sur le
commerce électronique und erfüllt beides nicht. Das Feature hat den Zustand sichtbar
gemacht, nicht verursacht — vorher stand dort gar nichts außer dem Namen der Plattform.
Ob AK-11 gestrichen oder zurückgenommen wird, entscheidet Michael in der Spec.

Der Angriffsdurchlauf blieb zum dritten Mal ohne Fund, diesmal einschließlich der neuen
Angriffsfläche: **vier SVG-Dateien von derselben Herkunft** — kein `<script>`, kein
`onload`, kein `foreignObject`, kein nachgeladenes Bild.

## Offen

| ID | Feature | Befund | Grad | Fundstelle | Status |
|---|---|---|---|---|---|
| BF-80 | 02 / projektweit | Bei 768 px scrollen **alle** Seiten um 51 px waagerecht — Startseite, `/about`, `/open`, `/restaurants`, `/criteria`, `/legal` und die Vergleichsseiten. Mit ausgeblendetem `<header>` sind es 0 px; bei genau 768 px greift die Desktop-Navigation, der Platz reicht nicht, `flex-wrap: nowrap` verhindert den Umbruch. **2026-08-30 bei der QA von `05` nachgemessen und verschärft:** abgemeldet +36 px bei 768 px und ab 850 px weg — **angemeldet aber +81 px, und das Band reicht bis unter 1000 px** (850 px → +40, 900 px → +15). Bei 768 px messen Logo (123) + Navigation (416) + Kontobereich (250 bzw. 295 angemeldet) zusammen 789 bzw. 833 px, während der Inhaltsbereich nach `px-4` nur 736 px fasst. Vollständige Messreihe: `docs/app-shell.md`, Bekannte Lücke 7 | mittel | `templates/base.html.twig` — `div.flex items-center gap-4` | offen |
| BF-82 | 03 | Ein Anbietername von 57 Zeichen **ohne Leerzeichen** sprengt bei 320 px die Kartendarstellung (`scrollX=104`). Bis 30 Zeichen sauber; die realen Wortmarken sind 8–11 Zeichen | niedrig | `templates/comparison/_cards.html.twig` — `<dt>` ohne `overflow-wrap` | offen |
| BF-90 | 04 | Nach einem `contactDeleted` bleibt bei zwei Quellen eine Zeile auf `synced` stehen, obwohl der Kontakt bei Brevo gelöscht ist: `record()` verweigert wegen der Sperre, `scheduleRemoval()` gibt `null` zurück. **Kein Datenabfluss** — bei Brevo steht nichts mehr —, aber ein lokaler Zustand, der nicht mehr stimmt, und eine Zeile, die niemand aufräumt | niedrig | `src/Marketing/MarketingContactRegistry.php` — `scheduleRemoval()` | offen |
| BF-88 | 04 | Der AV-Vertrag mit Brevo ist in `docs/datenschutz.md` **nicht festgehalten**, sondern als „noch zu prüfen" markiert; das Prüfdatum fehlt (AK-33). Hängt an **OF-01**, der nie festgelegten Datenschutzstufe des Projekts. Keine Softwarefrage — aber die Freigabe-Sperre für den ersten echten Lauf | mittel | `docs/datenschutz.md` | offen |
| ~~BF-93~~ | 05 | **behoben 2026-08-30 (QA³)** · Betreiberangaben fehlten auf `/presse` **und** `/legal`: Das Faktenblatt zeigt dreimal „Wird derzeit ergänzt", das Impressum unverändert „Endlech.lu / Luxemburg". AK-11 durchgefallen, AK-15 nicht prüfbar. **Kein Codeanteil** — die Mechanik (ein Parameter, zwei Seiten) steht und ist geprüft, die Werte fehlen (VB-03) | hoch | `config/services.yaml:40–42` | **behoben** — Michael Ferreira als Betreiber und Verantwortlicher; `OperatorDetailsTest` läuft statt zu überspringen. ⚠ Die **Anschrift bleibt bewusst leer** (OF-04), damit kann AK-11 nicht bestehen |
| ~~BF-94~~ | 05 | **behoben 2026-08-30 (QA³)** · Kein Bildmaterial und kein Paket: vier von fünf Vorschauen laufen in HTTP 404, kein Downloadlink. AK-16 und AK-20 durchgefallen, AK-17/18/19/22 nicht prüfbar. **Kein Codeanteil** — `app:press:package` verweigert bewusst ein halbes Paket (Exit 1), drei Prüfläufe überspringen mit benannter Begründung (VB-01) | hoch | `public/presse/` · `src/Press/PressRegistry.php::assets()` | **behoben** — vier Marken aus `logo.png` nachgezeichnet (0,244 % Abweichung), `make press-kit` gelaufen, Paket mit sechs Dateien |
| BF-95 | 05 | Eine fehlende Vorschaudatei erzeugt ein Bruchbild statt eines Ersatzes — anders als beim Paket, wo der Kontaktweg an die Stelle des Knopfes tritt. Der Entwurf sieht einen Fehlerzustand nur für `PressPackage` vor. Braucht **erst ein Kriterium** (OF-09), dann Code | mittel | `templates/press/_material.html.twig:18–22` | offen |
| ~~BF-96~~ | 05 | **behoben 2026-08-30 (QA³)** · Der Fotocredit nannte keinen Urheber („Bildnachweis wird ergänzt"). Die Nutzungsbedingung steht, die Urheberangabe fehlt — ein Presse-Kit, das ein Foto ohne Urheberangabe ausgibt, verursacht das Problem beim Abdruckenden (AK-24, OF-05) | mittel | `translations/press.*.yaml` → `person.photo_credit` | **behoben** — Fotocredit nennt den Urheber, in vier Sprachen |
| BF-99 | 05 | Der Schriftzug der beiden Wort-Bildmarken liegt als `<text>` vor, nicht als Pfad. Ohne die Schrift auf dem Zielsystem ersetzt der Betrachter sie — bei einer Wortmarke ist die Schrift die Marke. Die Bildmarke selbst ist sauber vektorisiert. Kein Codeanteil: einmal in Illustrator oder Affinity outlinen, dann `make press-kit` erneut | mittel | `public/presse/endlech-wortbildmarke.svg`, `…-invers.svg` | offen |
| ~~BF-97~~ | 05 | **behoben 2026-08-30** · **Mit vorhandenem Materialpaket antwortet `/presse` in allen vier Sprachen mit HTTP 500.** `_material.html.twig:44` ruft `package.publicPath`; auf `PressPackage` ist der Pfad eine **Klassenkonstante**, und Twig löst `object.attr` nie über eine Konstante auf. Der Fehler liegt im **Regelfall** des Features und blieb verborgen, weil die Umgebung kein Paket hat — der einzige Lauf, der den Abschnitt anfasst, prüfte nur den Ersatzzweig | **kritisch** | `templates/press/_material.html.twig:44` gegen `src/Press/PressPackage.php:22` | **behoben, gegengeprüft 2026-08-30 (QA²)** — `PressPackage::publicPath()`; mit angelegtem Paket 200 in allen vier Sprachen, `PressPackageTest` läuft erstmals durch statt zu überspringen. **Noch nicht ausgeliefert** |
| ~~BF-98~~ | 05 | **behoben 2026-08-30** · Zusammengesetzte Übersetzungsschlüssel (`'material.allowed_' ~ i`, die sechs `facts.*_value`) fallen durch **beide** Netze: `CatalogueCompletenessTest` erfasst nur Literale, `PressCatalogueTest` nur die von `PressRegistry` genannten. Entfernt man einen aus allen vier Katalogen, bleibt die Suite grün und die Seite zeigt den rohen Schlüssel. Heute ist nichts kaputt — die Absicherung fehlt. Derselbe blinde Fleck wie BF-56 | mittel | `tests/Unit/Translation/PressCatalogueTest.php:129–154` | **behoben, gegengeprüft 2026-08-30 (QA²)** — vierzehn Schlüssel als `ZUSAMMENGESETZTE_SCHLUESSEL`; beide Mutationsproben unabhängig wiederholt, beide werden rot, nach dem Wiederherstellen grün. **Noch nicht ausgeliefert** |

Die drei Befunde des ersten `03`-Durchlaufs (BF-77/78/79), BF-81 sowie die vier der
`02`-QA und die 72 Rückerfassungs-Befunde sind behoben — siehe unten.

> Nichts davon liegt auf `production`: Feature `02` auf
> `feature/02-barrierefreiheit-plattform`, Feature `03` auf `feature/03-vergleichsseiten`,
> die Rückerfassung auf `dev`/`fix/befunde-abarbeiten`.

## Behoben

### Feature 04 · Marketing-Kontakte in Brevo (QA 2026-08-29, behoben am selben Tag)

| ID | Befund | Grad | Behebung | Gegenprobe |
|---|---|---|---|---|
| BF-83 | Ein Verwaltungs-Statuswechsel befördert eine nie per Double-Opt-In bestätigte Adresse nach Brevo — der `confirmedAt`-Backfill stand vor dem Registry-Aufruf | **kritisch** | `applyStatus()` hält den Bestätigungsstand fest, **bevor** der Backfill läuft, und ruft die Registry nur dann | ⚠️ **Nur der erste Statuswechsel.** Der zweite liest ein `confirmedAt`, das der erste gesetzt hat — der Fehler tritt eine Runde später erneut auf. Fortgeführt als **BF-89** (QA², 2026-08-29) |
| BF-84 | Der Widerruf einer Quelle löscht den Kontakt einer anderen, aktiven Quelle mit derselben Adresse. Verstärker: Brevos `contactDeleted`-Echo der **eigenen** Löschung tilgte `marketing_consent_at` an allen Quellen | **kritisch** | `scheduleRemoval()` nimmt die auslösende Quelle entgegen und prüft über `aktiveQuellen()`, ob eine andere übrig bleibt — dann wird die Zeile auf jene umgeschrieben statt gelöscht. `blockByEmail()` steigt ohne vorhandene Zeile aus, und `contactDeleted` entwertet die Einwilligung an der Quelle nicht mehr | Über den echten Widerrufslink: Zeile bleibt (`pending/account`), Konto-Einwilligung steht, Wartelisten-Eintrag gelöscht. `MarketingBefundeTest::testBf84…`, `::testBf84b…` grün |
| BF-85 | `record()` war ohne zwischenzeitliches `flush()` nicht kollisionsfrei — `findOneByEmail()` sieht die vorgemerkte Zeile nicht | mittel | Die Registry führt eine Merkliste der in diesem Vorgang angelegten Zeilen; `finde()` sucht erst dort und verwirft Einträge, die der EntityManager nach `clear()` nicht mehr führt | `MarketingBefundeTest::testBf85…` grün. ⚠ Die echte Nebenläufigkeit (zwei parallele Requests) bleibt offen — siehe OF-09 |
| BF-86 | Eine fehlgeschlagene Übertragung wurde nie wieder aufgegriffen; `findOpenForSync()` fragte `FAILED` nicht ab, während der Enum-Kommentar das Gegenteil behauptete | hoch | `FAILED` steht in der Zustandsliste, der Rückzug bleibt allein beim Versuchszähler. Der Kommentar ist korrigiert und verweist auf die zweite Stelle | `MarketingSyncServiceTest::testAk19…` grün: nach 429 überträgt der zweite Lauf |
| BF-87 | Der Sync-Lauf committete bis zu 200 Zeilen ungeschützt in einer Transaktion | mittel | `flush()` in `try/catch`; ein Fehlschlag protokolliert Fehlerklasse und Zahl der betroffenen Zeilen (keine Adressen) und meldet sie im Ergebnis, statt den Cron-Lauf abzubrechen. Die Bündelung bleibt — wegen der Idempotenz unkritisch | 664 Tests grün |

| BF-89 | Der **zweite** Statuswechsel trug eine nie bestätigte Adresse ein — die BF-83-Reparatur griff nur eine Runde weit. Zweiter Weg: der Bestandsimport. Kehrseite: Nach dem Backfill lief der echte Bestätigungslink ins Leere | **kritisch** | Neues Feld **`selfConfirmedAt`** an beiden Wartelisten (Migration `Version20260829170000`), gesetzt **ausschließlich** von `confirm()`. Registry, `aktiveQuellen()` und die Import-Auswahl fragen `hasSelfConfirmed()`; `WaitlistConfirmationService::confirm()` prüft es für „bereits bestätigt". Der Vorabfilter in `applyStatus()` ist entfallen. ⚠ Der `confirmationToken` taugte **nicht** zur Unterscheidung — er bleibt in beiden Fällen stehen | Vier aufeinanderfolgende Statuswechsel → 0 Kontakte; echter Link danach → 1 Kontakt; AK-09 unverändert (`pending`); Import listet `NUR-BACKFILL` nicht, `ECHTER-DOI` schon; Doppelklick → 1 Kontakt. 674 Tests grün |

| BF-91 | Der Bestätigungsklick setzte einen fortgeschrittenen Vertriebsstatus zurück (`converted` → `confirmed`); der Rückfall wanderte über das Auftragsbuch bis in `FUNNEL_STATUS` bei Brevo (**AK-08**), und das Team bekam erneut eine „Neue Anmeldung"-Meldung | hoch | `confirm()` setzt den Status **nur noch aus `PENDING` heraus**, Zeitstempel weiterhin unbedingt. Beide Bestätigungs-Controller merken vor dem Aufruf, ob der Vorgang neu war (`$warNeu`), und rufen `notifyTeam()` nur dann | Eintrag auf `converted`: Status bleibt, 0 Team-Meldungen, `FUNNEL_STATUS=converted`, Selbstbestätigung festgehalten, Kontakt entsteht. Normalfall unberührt: `confirmed` + 1 Meldung. 677 Tests grün |
| BF-92 | `docs/data-model.md` führte keinen Bestandteil von Feature 04 | niedrig | Entity `MarketingContact` mit Feldreferenz, Indizes und den beiden ⚠-Begründungen; `marketingConsentAt` an allen drei Quellen, `selfConfirmedAt` an beiden Wartelisten, beide Enums, beide Migrationen | Alle neun Bestandteile in der Datei nachgewiesen. ⚠ Dabei fiel ein **vorbestehender Rückstand** auf: sechs Migrationen aus Feature 01/02 fehlen in der Historie — als Lücke vermerkt, nicht gefüllt |

⚠ **BF-88 und BF-90 bleiben offen** — beide stehen oben unter *Offen* und brauchen eine
Betreiberentscheidung. BF-88 (AV-Vertrag ohne Datum) hängt an OF-01; BF-90 (verwaiste
Zeile nach `contactDeleted`, niedrig) gehört mit OF-06 zusammen entschieden.


### Feature 03 · Vergleichsseiten (QA 2026-08-29, behoben am selben Tag)

| ID | Befund | Grad | Behebung | Gegenprobe |
|---|---|---|---|---|
| BF-77 | Die drei Vergleichsseiten scrollten bei 320 px waagerecht (`scrollX=212`); die Merkmalstabelle ist mit ihren erklärenden Halbsätzen 525 px breit | hoch | Kartendarstellung `_cards.html.twig` unter `md:`, Tabelle auf `hidden md:block`; gemeinsamer Inhalt in `_verdict_body.html.twig`. ⚠ Der naheliegende `overflow-wrap: anywhere` wurde ausprobiert und **verworfen** — er beseitigt das Scrollen und zerlegt dabei Wörter mitten im Wort | `scrollX=0` bei 320/375/1280 px auf allen drei Seiten und in allen Sprachen; zwei neue Prüfläufe, Rückbau schlägt fehl |
| BF-78 | `ComparisonGroup::transKey()` und `Verdict::transKey()` fielen durch jeden Prüflauf — der Schlüssel fehlte in allen vier Katalogen, 594 Tests blieben grün, der rohe Schlüssel stand auf der Seite | mittel | `schluesselAusDerRegistry()` läuft zusätzlich über `ComparisonGroup::cases()` und `Verdict::cases()` | Reproduktion greift nicht mehr: Lauf schlägt mit „1 Schlüssel fehlen in comparison.lb.yaml" fehl |
| BF-79 | Zwei `<nav>`-Landmarks je Vergleichsseite hießen beide „Weitere Vergleiche" | mittel | eigener Schlüssel `breadcrumb.label` in vier Sprachen | Landmarks heißen jetzt „Sie sind hier" / „Weitere Vergleiche"; neuer Prüflauf, Rückbau schlägt fehl |
| BF-81 | Der Fußnoten-Prüflauf filterte auf `table a[href^="#quelle-"]` und sah nur die Tabelle. Nachgestellt: Die Kartendarstellung verlor alle 18 Fußnotenlinks, **alle 606 Tests blieben grün** — auf schmalen Anzeigen hätten die Aussagen über den Wettbewerber unbelegt dagestanden | mittel | in der QA vom 2026-08-29 selbst geschlossen: neuer Prüflauf `testBeideDarstellungenBelegenIhreAussagen`, bestehender Test filtert nicht mehr auf `table` | Rückbau schlägt jetzt mit „Die Kartendarstellung belegt keine einzige Aussage" fehl |


| ID | Feature | Befund | Grad | Behoben am | Ausgeliefert |
|---|---|---|---|---|---|
| BF-74 | 02 | Cookie-Banner fing beim `connect()` den Fokus; `focus()` nur noch in `reopen()` (nutzergetriggert) — Skip-Link ist wieder erstes Tab-Ziel (playwright PASS) | hoch | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-73 | 02 | Mailer fing nur `Mailer\TransportException`; catch um `Messenger\TransportException` erweitert (EC-04), Regressionstest ergänzt | niedrig | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-75 | 02 | Sortier-Leiste auf `/de/restaurants` mit `flex-wrap` versehen; kein 320px-Overflow mehr über alle öffentlichen Routen (AK-14) | mittel | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-76 | 02 | `focus:outline-none` durch echte `outline` ersetzt — `OpeningHourType`, `_passkey_manage`, 5 Profil-Felder; Fokus im Kontrastmodus sichtbar (AK-04/AK-40). Gefunden durch forced-colors + `AccessibilityInteractionTest` | mittel | 2026-08-26 | **noch nicht** — `feature/02-barrierefreiheit-plattform` |
| BF-04 | **01** | Feature `01` gebaut — Konto löschen, Daten exportieren, Passwort zurücksetzen, Einwilligung widerrufen | hoch | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-09 | B01 | Anti-Enumeration im Registrierformular; `UniqueEntity` in Gruppe `strict`, Hash in beiden Zweigen (528 vs. 522 ms) | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-10 | B14, B15, B23 | `->locale()` in `WaitlistConfirmationService` und `Api\V1\AuthController` | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-11 | B01, B14, B15 | `ActionLimiter` — Kontingent wird erst verbraucht, wenn die Handlung stattfindet | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-15 | B02 | `IS_AUTHENTICATED_REMEMBERED` für `/profile` | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-17 | B02 | `GuestLogoutSubscriber` — Weiterleitung statt 403, CSRF-Schutz unverändert | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-18 | B03 | Limiter `passkey_challenge` (30 je 15 min) über `RouteRateLimitSubscriber` | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-21 | B04 | Limiter `email_change` (3/h), am Konto gezählt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-22 | B04 | Adresse wird auch im Fehlerfall zurückgesetzt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-23 | B01, B04 | `!request` aus dem `fingers_crossed`-Puffer | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-29 | B23 | In `.env` erklärt, warum `trusted_hosts` nicht aus der Umgebung geht; `deploy.sh` warnt bei fehlendem `APP_API_BASE_URL` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-30 | B23 | Limiter `suggestion_submit` (10/h), am Konto | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-31 | B23 | `id` → `submissionId` in der 202-Antwort | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-32 | B23 | `/me/submissions` zeigt Vorschläge mit `state` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-33 | B19 | Referer ist nur noch Wegweiser; der Router baut das Ziel | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-34 | B19 | Sprachwechsel im Admin schreibt den Pfad um statt die Sitzung | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-35 | B19 | Limiter `admin_write` (120 je 5 min), GET zählt nicht | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-36 | B14 | Bestätigungslinks verfallen nach sieben Tagen, 410 statt 404 | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-37 | B14 | Abmeldelink in jeder Wartelisten-Mail; der Widerruf löscht | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-38 | B14 | Eigener Limiter `organisation_waitlist` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-39 | B15 | „Verein" statt „Organisation", im Enum und in vier Katalogen | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-40 | B22 | Auswahlliste auf 50 begrenzt, serverseitige Suche | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-41 | B17 | `fieldNotes` in den Datensatz-Metadaten erklären `verified`, `assessed`, `accessibilityScore` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-42 | B17 | Limiter `open_dataset` (60/h) mit `Retry-After` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-43 | B17 | CSV-Werte mit führendem `= + - @` bekommen ein Apostroph (OWASP) | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-47 | B18 | Überschreiben ist ein eigener Knopf mit Rückfrage | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-48 | B18 | `src/Schedule.php` sagt selbst, dass es auf Production nicht feuert | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-49 | B11 | `assessedFeatures` hält fest, wonach jemand gesehen hat — `UNKNOWN` zählt nicht | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-50 | B11 | Limiter `suggestion_submit`, geteilt mit dem API-Weg | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-51 | B20 | `empty_data => ''` an sechs Pflichtfeldern — 422 statt 500 | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-52 | B20 | 25 je Seite plus Suche über Name und Stadt | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-53 | B20 | `RestaurantImageFileListener` auf `postRemove`, dazu `app:uploads:prune` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-54 | B21 | Genehmigen und Ablehnen prüfen den Status — dreimal abgeschickt ergibt ein Restaurant | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-55 | B21 | Ablehnungsmail an den Einreicher, in der Sprache der Einreichung | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-56 | B21 | Türbreite und Tischabstand im Assistenten; wandern bei der Genehmigung mit | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-57 | B09 | MIME-Whitelist im `ImageUploadService`; Endung folgt dem echten Typ | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-58 | B09 | 4-MB-Grenze plus verständliche Meldung bei `post_max_size` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-59 | B05 | LIKE-Platzhalter maskiert (`ESCAPE '!'`) | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-60 | B05 | Seite jenseits des Endes → 404 | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-61 | B07 | Folgetagsschleife läuft bis 7 statt 6 | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-62 | B08 | Längenprüfung am Endpunkt UND Slug-Kürzung im Repository (`ß` → 160 Zeichen) | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-63 | B08 | `DELETE /api/cuisines/{id}` für ungenutzte Typen, 409 mit `usedBy` | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-65 | B13 | Brevo, Verkéiersverbond, Betroffenenrechte, Speicherdauer, neuer Einleitungssatz | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-66 | B13 | Abschnitt „Wie die Punktzahl entsteht" mit allen acht Merkmalen | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-67 | B16 | Häuser ohne Erhebung bekommen `null` statt 0 und fallen aus dem Durchschnitt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-68 | B24 | `_locale` aus dem Query gefiltert, Sprache zuletzt gemergt | hoch | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-69 | B24 | Elf Schlüssel ergänzt, drei Templates auf vorhandene umgestellt | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-70 | B24 | `lang="de"` und Herkunftshinweis auf den Freitexten | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-71 | B24 | `keydown.esc` schließt den Umschalter und gibt den Fokus zurück | niedrig | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-72 | B24 | Umschalter auch auf Mobil sichtbar | mittel | 2026-08-25 | **noch nicht** — Branch `fix/befunde-abarbeiten` |
| BF-01 | B01 | „Bestätigungsmail erneut senden" war unerreichbar — Routen-Requirement ergänzt | hoch | 2026-08-23 | **noch nicht** — Branch `fix/b01-registrierung-qa` |
| BF-02 | B01 | Registrierung ohne Rate Limit — Limiter `registration` (5/h) und `verify_resend` (3/h) | hoch | 2026-08-23 | **noch nicht** |
| BF-05 | B01, B04 | Roher Übersetzungsschlüssel — `form.password_mismatch` in allen vier `validators.*.yaml` | mittel | 2026-08-23 | **noch nicht** |
| BF-06 | B01 | Bestätigungstoken im Log — `doctrine`-Channel in `prod` ausgeschlossen. **Nur der prod-Weg**; der dev-Teil ist bewusst offen, siehe BF-12 | mittel | 2026-08-23 | **noch nicht** |
| BF-07 | B01 | Hartkodierte deutsche Meldung — `user.email_unique` statt Klartext (Enumeration bleibt offen, siehe BF-09) | mittel | 2026-08-23 | **noch nicht** |
| BF-08 | B01 | Mail-Locale bei asynchronem Versand — `->locale()` gesetzt (**nur B01**, B14/B15/B23 weiterhin betroffen) | mittel | 2026-08-23 | **noch nicht** |
| BF-16 | B02, B04 | **Rekonstruktion falsch** — B02/EC-04, B04/AK-13 und B04/FB-04 behaupteten, Sitzungen und `remember_me`-Cookies überlebten eine Passwortänderung. **Beide Specs am 2026-08-24 berichtigt**, Regressionstest angelegt | mittel | 2026-08-24 | entfällt (Dokumentation) |
| BF-13 | B02 | Anmeldung ohne Sperre — `login_throttling` mit 5 Versuchen je 15 Minuten ergänzt | **hoch** | 2026-08-24 | **noch nicht** |
| BF-24 | B23 | API umging die Moderation — `create()` legt jetzt einen `RestaurantSuggestion` an und antwortet mit 202; `cuisines` ohne `findOrCreateByName()`; nicht übermittelte Merkmale sind `UNKNOWN` statt `false` | **hoch** | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-25 | B23 | `register` unter dem schwachen Limit — eigener Limiter `api_register` (5/h) | **hoch** | 2026-08-24 | **noch nicht** |
| BF-26 | B23 | Formatvertrag der JWT-Antworten — `ApiAuthenticationFailureSubscriber` für alle vier Fälle des Bundles | mittel | 2026-08-24 | **noch nicht** |
| BF-27 | B23 | Zu lange Küchen-Angabe → 422 statt 500 | niedrig | 2026-08-24 | **noch nicht** |
| BF-28 | B23 | 404-Meldungen ohne interne Klassennamen | niedrig | 2026-08-24 | **noch nicht** |
| BF-64 | B12 | Startseite zeigte ein Restaurant statt sechs — `findTopRated()` nutzt jetzt `Paginator` mit `$fetchJoinCollection`. Der vorhandene Test prüfte `assertLessThanOrEqual` und war grün; er prüft jetzt `assertCount` | mittel | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-46 | B10 | Erfundene Barrierefreiheitsaussage — Texte sagen jetzt, was tatsächlich geprüft wurde, samt Radius und Herkunftshinweis; Radius 500 → 1000, damit 8 statt 3 von 11 Restaurants Haltestellen zeigen | mittel | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-44 | B10 | Kein Timeout — `'timeout' => 3`, `'max_duration' => 5`; Rückkehr nach 0,3 s statt >30 s | mittel | 2026-08-24 | **noch nicht** |
| BF-45 | B10 | API-Schlüssel im Log — Service protokolliert Klasse und Code statt der URL; `SecretMaskingProcessor` deckt Symfonys `http_client`-Kanal ab. 22 Zeilen mit `accessId=`, **0 im Klartext** | mittel | 2026-08-24 | **noch nicht** |
| BF-14 | B02 | Abmelden ohne CSRF — `enable_csrf` plus POST-Formular in der Kopfzeile | niedrig | 2026-08-24 | **noch nicht** |
| BF-19 | B04 | E-Mail-Änderung ohne erneute Bestätigung — neue Adresse wird nur noch vorgemerkt (`User::$pendingEmail`), Bestätigungslink an die neue und Warnung an die bisherige Adresse | **hoch** | 2026-08-24 | **noch nicht** — Branch `fix/b04-profil-qa` |
| BF-20 | B04 | Passwortänderung ohne Rate Limit — Limiter `password_change` (5 je 15 Minuten), gezählt **am Konto** statt an der IP | niedrig | 2026-08-24 | **noch nicht** |

## Akzeptiert

Bewusst nicht behoben. Ohne Begründung und Datum ist ein Befund nicht akzeptiert,
sondern vergessen.

| ID | Feature | Befund | Grad | Begründung | Beschlossen am |
|---|---|---|---|---|---|
| BF-12 | B01 | Bestätigungstoken steht weiterhin im `dev`-Log (`doctrine.DEBUG`) | niedrig | Ein Entwicklungslog ohne SQL-Historie wäre für die Fehlersuche wertlos, und es verlässt den Rechner nicht. Der Weg, auf dem der Token in `prod` ins Hoster-Log geriet, ist mit BF-06 geschlossen. Deshalb bleibt AK-17 formal durchgefallen, ohne dass ein Befund offen ist | 2026-08-23 |
| BF-03 | B01 | Unbestätigte Konten haben vollen Zugang — kein `user_checker` | hoch | Ein `user_checker` sperrt bestehende unbestätigte Konten im Moment des Deployments aus; wie viele das auf Produktion sind, ist nicht einsehbar. Betreiberentscheidung gegen einen globalen Zwang; die Voraussetzung für eine spätere Umstellung ist mit der Reparatur von BF-01 geschaffen. Dokumentiert als OF-01 der Spec | 2026-08-23 |

## Muster

**Ein Ast, der nie ausgeführt wird, ist keine Abdeckung — er sieht nur so aus.**
*(2026-08-30, Feature 05, BF-97)* Ein Prüflauf verzweigte an einer Vorbedingung
(`PressPackage::exists()`) und prüfte in der vorhandenen Umgebung ausschließlich den
Ersatzzweig. Der Regelfall des Features lag damit in keinem einzigen Test, und ein
Fehler darin — die Seite antwortet mit 500 — wurde erst sichtbar, als die QA den Zustand
**herstellte** statt ihn abzuwarten. Wo eine Vorbedingung offen ist, wird der Zustand
dahinter simuliert; sonst wächst die grüne Suite genau um die Stellen, die niemand
gesehen hat.



Was in mehr als einem Feature auftritt — der Grund, warum diese Liste existiert.

- **Eine Reparatur öffnet einen Pfad, den vorher eine Abbruchbedingung verdeckte
  (BF-89 → BF-91).** Dritte Runde an derselben Stelle, und diesmal war die Ursache eine
  andere: `WaitlistConfirmationService::confirm()` stieg bei einem verwaltungsseitig
  bestätigten Eintrag früher mit `RESULT_ALREADY` aus. Die Reparatur nahm diese
  Abbruchbedingung weg — richtigerweise, denn sie verschluckte echte Bestätigungen —, und
  damit wurde `Entity::confirm()` in einer Lage erreicht, für die es nie geschrieben war:
  Es setzt auch den **Status**, und ein gewonnener Kunde fiel auf „bestätigt" zurück.
  **Folgerung: Wer eine Abbruchbedingung entfernt, prüft, was dahinter liegt.** Der
  entfernte `return` war die einzige Stelle, die einen ganzen Codepfad unerreichbar hielt
  — sein Wegfall ist kein kleinerer Eingriff als eine neue Verzweigung.
- **Eine Reparatur an der Reihenfolge verschiebt einen Fehler, der aus einer
  Zweideutigkeit stammt (BF-83 → BF-89).** `confirmedAt` bedeutet zweierlei — eingelöster
  Double-Opt-In und Verwaltungs-Backfill. Die erste Reparatur zog die Prüfung **vor** den
  Backfill; damit war der erste Statuswechsel sauber und der zweite wieder falsch, weil
  er das nachgesetzte Feld vorfindet. **Folgerung: Wenn ein Feld zwei Bedeutungen trägt,
  ist jede Reparatur an der Reihenfolge ein Aufschub.** Die Frage vor der nächsten
  Reparatur lautet nicht „wann prüfen wir", sondern „woran unterscheiden wir die beiden
  Fälle". Verwandt mit dem Muster darunter: Auch hier stimmte der Kommentar — er
  beschrieb nur den Fall, den der Autor vor Augen hatte.
- **Ein Kommentar, der eine Zusage macht, die der Code nicht hält (BF-46 behoben, BF-86
  offen).** Zweimal derselbe Mechanismus in einem Projekt, das bewusst dicht und
  begründend kommentiert: Bei BF-46 sagte der **Nutzertext** „barrierefreie Haltestellen",
  während die Schnittstelle kein solches Merkmal kennt. Bei BF-86 sagt der
  **Codekommentar**, der Sync-Lauf greife fehlgeschlagene Zeilen „über ihren
  Versuchszähler wieder auf, nicht über den Zustand" — die Abfrage tut genau das nicht.
  Beide Male hat der Kommentar die Prüfung *ersetzt* statt sie zu leiten: Wer ihn liest,
  hält die Sache für geklärt und sieht nicht mehr nach. **Folgerung für künftige QA: Ein
  ⚠-Kommentar, der ein Verhalten zusichert, ist ein Prüfauftrag, kein Nachweis.**
- **Ein geteilter Schlüssel löst einen Fall auf der Schreibseite und bricht ihn auf der
  Löschseite (BF-84, offen).** `marketing_contact` hat bewusst *eine Zeile je Adresse* —
  das löst EC-01 („eine Adresse, ein Kontakt") beim Eintragen sauber und auf
  Datenbankebene. Beim **Austragen** kippt dieselbe Entscheidung: Der Löschauftrag kennt
  die auslösende Quelle nicht und nimmt die anderen mit. Bisher einmalig; die Zeile steht
  hier, weil dieselbe Frage bei jedem künftigen quellenübergreifenden Schlüssel wieder
  auftaucht — die Entwurfsprüfung sollte zu jedem geteilten Schlüssel ausdrücklich fragen,
  was beim **Entfernen** einer von mehreren Quellen geschieht.

- **Fehlende Eingabeprüfung endet im 500er (BF-27 behoben, BF-51, BF-62, BF-68 offen).**
  Viermal dasselbe: Ein Wert, den niemand geprüft hat, fällt in die nächste Schicht und
  kommt dort als Serverfehler heraus — zu lange Küchen-Angabe über die API (BF-27), leeres
  Pflichtfeld (BF-51), zu langer Küchenname (BF-62), Sprachcode aus dem Query (BF-68).
  Der vierte Fall ist der schwerste, weil er **ohne Anmeldung und ohne POST** auslösbar ist
  und jede öffentliche Seite trifft. Gemeinsam ist allen, dass die Prüfung an der Stelle
  fehlt, an der der Wert **hereinkommt** — nicht dort, wo er verbraucht wird.
- **Eine Rekonstruktion kann auch richtig sein und trotzdem in die Irre führen (BF-68).**
  B24/AK-12 beschreibt die Merge-Reihenfolge im Sprachumschalter **korrekt** — und nennt
  sie „harmlos, aber leicht zu beheben". Gemessen ist sie ein Open Redirect plus ein
  auslösbarer 500er auf zehn Seiten. Das Verhalten war richtig erfasst, der Schaden nicht.
  Ergänzung zu BF-16: Eine `spec.md` eines Bestandsfeatures ist nicht nur dort angreifbar,
  wo sie das Verhalten falsch beschreibt, sondern auch dort, wo sie es bewertet.
- **Fehlende Rate Limits im Browser-Weg (BF-02 behoben, BF-13 offen).** Die Anwendung
  drosselt die API (`api_login`: 5/min, zweimal belegt: ab dem sechsten Versuch 429), aber
  kaum einen Web-Endpunkt. Für die Registrierung ist es behoben, für die **Anmeldung**
  nicht — dort wurden 20 Fehlversuche gegen das Admin-Konto alle angenommen. Offen bleiben
  nach der Rückerfassung Passwortwechsel, Passkey-Challenge, Vorschläge und
  Datensatz-Download (M-01 in `fehlbestand-uebersicht.md`). Das Muster hat sich damit
  bestätigt: Geschützt ist der Weg, den eine App nimmt; ungeschützt der, den ein Browser
  nimmt. Mit BF-18 kommt ein dritter Fall dazu: die sprachfreien Passkey-Endpunkte fallen
  weder unter die Web- noch unter die API-Limiter.
- **Eine Rekonstruktion kann falsch sein (BF-16).** B02/EC-04 und zwei Stellen in B04
  behaupteten, eine Passwortänderung lasse fremde Sitzungen unberührt — geschlossen aus
  dem Projektcode, der tatsächlich nichts dergleichen tut. Gemessen erledigt Symfony es
  selbst. Bei jedem weiteren Feature gilt: Was das Framework leistet, steht nicht im
  Projektcode und lässt sich nur durch Ausführen feststellen.
- **Ein Übersetzungsschlüssel, zwei Features (BF-05, behoben).** Derselbe Griff in die
  falsche Übersetzungsdomäne betraf Registrierung und Passwortwechsel. Die Reparatur wirkt
  für beide; bei der QA von B04 ist nur noch zu bestätigen.
- **Locale geht bei asynchroner Verarbeitung verloren (BF-08 behoben, BF-10 offen).**
  Betrifft alle vier Mailwege des Projekts. In B01 mit `->locale()` behoben — derselbe
  Einzeiler steht für B14, B15 und B23 noch aus. Auf Produktion derzeit unwirksam, weil dort
  synchron versandt wird; kippt, sobald ein Messenger-Worker eingeführt wird. Genau das ist
  für die Monats-Snapshots vorgesehen (B18/AK-17).
- **Kontingent für ungültige Eingaben (BF-11).** Beide Rate-Limiter des Projekts —
  der bestehende in `PartnerController` und der neue in `RegistrationController` —
  konsumieren vor der Gültigkeitsprüfung. Wer sich vertippt, verbraucht dasselbe
  Kontingent wie ein Angreifer. Das ist keine Nachlässigkeit der Reparatur, sondern
  ein Muster, das sie übernommen hat; bei der QA von B14/B15 mitzuprüfen.
- **Betroffenenrechte (BF-04, jetzt Feature `01`).** Kein Löschweg, kein Export, kein
  Widerruf. Die Rückerfassung nennt dieselbe Lücke für die Wartelisten (B14, B15, B22).
  Es ist die einzige Befundgruppe mit rechtlicher Frist — und die technischen
  Voraussetzungen (Kaskaden, `SET NULL`, Dateilöschung) sind bereits vollständig da.
  **Seit 2026-08-23 als eigenes Feature geführt**, weil ein Befund, der drei Features
  betrifft und keine Reparatur ist, sonst eines davon dauerhaft blockiert.

## Reihenfolgeabhängigkeiten

Nicht jeder Befund lässt sich einzeln beheben:

- **BF-02 vor BF-01** — die Reparatur der Routenkollision öffnet sonst einen
  ungedrosselten Mailversand auf ein fremdes Postfach.
- **BF-01 vor BF-03** — ein `user_checker` sperrt alle bestehenden unbestätigten Konten
  aus, solange sie keine neue Bestätigungsmail anfordern können.
