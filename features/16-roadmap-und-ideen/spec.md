# 16 · Roadmap und Ideen in einem Bereich — Spezifikation

Status: `planned` · Stand: 2026-11-07

## Zweck

Heute liegen zwei Seiten, die dieselbe Frage aus zwei Richtungen beantworten, an zwei
Orten: Was der **Betreiber** vorhat, steht auf `/roadmap` (Feature `07`), was sich die
**Community** wünscht, auf `/community/ideen` (Feature `06`). In der Fußzeile stehen sie
in verschiedenen Gruppen — „Feedback & Ideen" unter „Mitmachen", „Roadmap" und
„Changelog" unter „Über Endlech" —, und wer eine Idee einreichen will, sieht nicht, ob sie
schon geplant ist, ohne die Seite zu wechseln.

Nach diesem Feature gibt es einen Bereich **„Roadmap & Ideen"** mit einem gemeinsamen
Kopf und drei Reitern: **Roadmap** (`/roadmap`), **Ideen** (`/roadmap/ideen`, bisher
`/community/ideen`) und **Changelog** (`/changelog`). Die Fußzeile führt mit einem
einzigen Eintrag hinein, und der Knopf „Idee einreichen" steht auf jedem Reiter.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| 06 · Community Feedback Board | `deployed` | Liefert den Inhalt des Reiters Ideen samt Freigabe, Zustimmung, Deckeln und Mails. Seine Adressen ziehen um, seine Logik nicht |
| 07 · Öffentliche Roadmap und Changelog | `deployed` | Liefert die Reiter Roadmap und Changelog. Der Block „Weiterlesen" entfällt, alles andere bleibt |
| 10 · Sitemap und robots.txt | `deployed` | Die Sitemap, die maßgeblichen Adressen und die Sprachverweise müssen die neue Adresse kennen und die alte nicht mehr anbieten (AK-25, AK-26) |
| 11 · Nutzungsmessung | `deployed` | Zählt künftig unter dem neuen Pfad; nichts kommt hinzu (AK-31) |
| 02 · Barrierefreiheit der Plattform | `deployed` | Gilt für den gemeinsamen Kopf und die Reiter (AK-06 bis AK-09, AK-28) |
| B24 · Mehrsprachigkeit | `approved` | Bereichsname, Reiter und Knopf in allen fünf Sprachen (AK-27) |

⚠ **Der Branch `feature/feat/footer-herstellerhinweis-mwst` ändert dieselbe Fußzeile**
(Herstellerhinweis). Er wird vor dem Bau gemergt, sonst kollidieren die beiden Änderungen
an derselben Datei.

## User Stories

- **US-01** · Als Besucherin möchte ich an einem Ort sehen, was geplant ist und was sich
  andere wünschen, damit ich vor dem Einreichen erkenne, ob meine Idee schon vorgesehen ist.
- **US-02** · Als jemand mit einer Rückmeldung möchte ich über die Fußzeile mit einem Klick
  dort landen, wo ich sie loswerde, ohne zwischen Seiten zu suchen.
- **US-03** · Als Einreicher möchte ich, dass der Link in einer Mail, die ich vor der
  Umstellung bekommen habe, weiter auf meine Idee führt.
- **US-04** · Als Betreiber möchte ich den Bereich als Ganzes verlinken können (etwa in
  einem Gespräch oder Beitrag), statt zwei Adressen zu nennen.

## Nicht im Scope

- **Inhalte der Roadmap und des Boards.** Vorhaben, Begründungen, Ideen, Status,
  Freigabe, Abstimmung und Mails bleiben, wie sie sind. Die Durchsicht der Roadmap hängt
  weiterhin an der Release-Checkliste (07/OF-03).
- **Die Verwaltung unter `/admin/ideen`.** Adresse und Ablauf bleiben unverändert.
- **Die Adresse des Changelogs.** Er bleibt unter `/changelog` samt sprachfreiem
  Kurzlink und wird nur als dritter Reiter verlinkt (Decision Log 4).
- **„Restaurant vorschlagen" unter `/community/suggest`.** Das ist B11, ein anderer
  Vorgang (ein neues Restaurant, keine Idee zur Plattform), und bleibt unter `/community`.
- **Ideen in die Roadmap-Spalten einsortieren** (Status bestimmt Spalte). Als Form
  verworfen, Decision Log 1.
- **Ein Eintrag in der Kopfnavigation oder der mobilen Leiste unten.** Der Bereich wird
  über die Fußzeile erreicht, wie Roadmap und Board heute.
- **Ein sprachfreier Kurzlink `/roadmap/ideen`.** `/community/ideen` hatte auch keinen;
  wer ohne Sprache kommt, landet über `/roadmap` im Bereich (EC-04).
- **Die Nutzungszahlen der alten Adresse in Umami zusammenführen.** Ab der Umstellung
  zählt der neue Pfad, die alten Zahlen bleiben unter der alten Adresse stehen
  (Decision Log 11).
- **Die Texte der Startseite zu „Bewertungen"** (07/OF-08). Eigenes Vorhaben.

## Verhältnis zu den Zusagen aus 06 und 07

Diese Kriterien der beiden Vorgänger werden durch dieses Feature **abgelöst**. Beim Bau
werden sie in `features/06-…/spec.md` und `features/07-…/spec.md` als abgelöst markiert
(durchgestrichen, mit Verweis hierher), nicht gelöscht.

| Alt | Sagte | Abgelöst durch |
|---|---|---|
| 06/AK-01 | Board unter `/de/community/ideen` | AK-04, AK-17 |
| 06/AK-02 | Fußzeile verweist auf das Board | AK-01, AK-03 |
| 06/AK-80 | genau ein Rückmeldeweg in der Fußzeile, auf `/community/ideen` | AK-03 |
| 07/AK-02 | Fußzeile verweist je einmal auf Roadmap und Changelog | AK-01, AK-02 |
| 07/AK-10 | von der Roadmap in einem Klick zu Changelog und Board | AK-07 (Reiter) |
| 07/AK-17 | „ein Verweis auf das Board für die übrigen" | AK-16 |

**Alle übrigen Kriterien aus 06 und 07 gelten unverändert**, unter den neuen Adressen.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar. Wo eine Zahl steht, ist sie Teil des
Kriteriums. Die fünf Sprachen sind `lb`, `de`, `fr`, `en`, `pt`.

### A · Den Bereich finden

- **AK-01** · Angenommen, ein Besucher steht auf einer beliebigen Seite außerhalb der
  Verwaltung, wenn er die Fußzeile liest, dann findet er in der Gruppe „Mitmachen" genau
  einen Eintrag „Roadmap & Ideen", und ein Klick darauf öffnet den Reiter **Roadmap** des
  Bereichs in seiner Sprache (deutsch: `/de/roadmap`).
- **AK-02** · Angenommen, die Fußzeile wird vollständig gelesen, dann steht dort **kein**
  Eintrag „Roadmap", „Changelog" oder „Feedback & Ideen" mehr und kein zweiter Verweis in
  den Bereich — in keiner Gruppe.
- **AK-03** · Angenommen, ein Besucher sucht in der Fußzeile nach einem Weg für
  Rückmeldungen, dann findet er **genau einen**, er führt in den Bereich „Roadmap & Ideen",
  und er öffnet sich im selben Browserfenster, nicht in einem neuen.
- **AK-04** · Angenommen, ein Besucher ist nicht angemeldet, wenn er `/de/roadmap`,
  `/de/roadmap/ideen` oder `/de/changelog` aufruft, dann antwortet jede der drei mit
  HTTP 200 und ohne Aufforderung, sich anzumelden.

### B · Gemeinsamer Kopf und Reiter

- **AK-05** · Angenommen, einer der drei Reiter oder die Einzelseite einer Idee ist
  geöffnet, wenn der Kopf gelesen wird, dann steht dort der Name „Roadmap & Ideen" und
  darunter drei Reiter in dieser Reihenfolge: „Roadmap", „Ideen", „Changelog" — auf allen
  vier Seitenarten wortgleich und an derselben Stelle.
- **AK-06** · Angenommen, ein Reiter ist geöffnet, wenn der Kopf betrachtet wird, dann ist
  der aktive Reiter **nicht allein durch Farbe** hervorgehoben (etwa durch Unterstrich oder
  Rahmen), und ein Screenreader sagt ihn als aktuelle Seite an. Auf der Einzelseite einer
  Idee gilt „Ideen" als aktiv.
- **AK-07** · Angenommen, ein Reiter ist geöffnet, wenn auf einen anderen Reiter geklickt
  wird, dann öffnet sich dessen Seite mit **einem** Klick, die Adresse in der Adresszeile
  wechselt auf die des Reiters, und das gilt auch bei **abgeschaltetem JavaScript**.
- **AK-08** · Angenommen, das Browserfenster ist 320 px breit, wenn einer der drei Reiter
  in einer der fünf Sprachen geöffnet wird, dann lässt sich die Seite **nicht waagerecht
  scrollen**, alle drei Reiter sind sichtbar, und jeder Reiter ist eine Tippfläche von
  mindestens 44 × 44 px.
- **AK-09** · Angenommen, ein Besucher bedient den Bereich nur mit der Tastatur, wenn er
  vom Seitenanfang durchtabbt, dann erreicht er die drei Reiter und den Knopf „Idee
  einreichen", kann jedes mit Enter auslösen, und der Fokus ist an jeder Station sichtbar.
- **AK-10** · Angenommen, einer der drei Reiter ist geöffnet, wenn der erste Bildschirm
  bei 1280 × 800 px und bei 375 × 800 px betrachtet wird, dann ist der Knopf „Idee
  einreichen" **ohne Scrollen** sichtbar — auf allen drei Reitern.
- **AK-11** · Angenommen, der Reiter Roadmap ist bei 1280 × 800 px und bei 375 × 800 px
  geöffnet, wenn der erste Bildschirm betrachtet wird, dann ist die Überschrift der Spalte
  „In Arbeit" **ohne Scrollen** sichtbar — der zusätzliche Kopf verdrängt sie nicht
  (07/AK-03 bleibt erfüllt).
- **AK-12** · Angenommen, ein nicht angemeldeter Besucher klickt im Kopf auf „Idee
  einreichen", wenn er sich danach anmeldet, dann landet er **auf dem Einreichformular**,
  ohne den Weg erneut suchen zu müssen. Ist sein Konto nicht bestätigt, erscheint wie
  bisher der Hinweis, die E-Mail-Adresse zu bestätigen.
- **AK-13** · Angenommen, die drei Reiter werden nacheinander geöffnet, wenn die Titel im
  Browser-Tab verglichen werden, dann trägt jeder Reiter einen **eigenen** Titel; und auf
  jeder der drei Seiten gibt es genau eine Hauptüberschrift (`h1`), die
  Überschriftenebenen sind lückenlos.

### C · Reiter Roadmap

- **AK-14** · Angenommen, der Reiter Roadmap ist geöffnet, wenn er bis zum Ende gelesen
  wird, dann zeigt er die drei Spalten „In Arbeit", „Geplant", „Angedacht", die Gruppe der
  höchstens zehn geplanten Community-Ideen, den Aktualitätshinweis und den Block „Bewusst
  nicht gebaut" wie vor der Umstellung — und **keinen** Abschnitt „Weiterlesen" mehr.
- **AK-15** · Angenommen, eine Community-Idee steht in der Spalte „Geplant", wenn ihr
  Verweis angeklickt wird, dann öffnet sich ihre Einzelseite unter
  `/de/roadmap/ideen/<Nummer>-<Titel>` **direkt**, ohne Umweg über eine Weiterleitung.
- **AK-16** · Angenommen, mehr als zehn veröffentlichte Ideen tragen den Status
  `Geplant`, wenn der Hinweis auf die übrigen gelesen wird („3 weitere geplante Ideen …"),
  dann führt er mit einem Klick auf den Reiter Ideen, **gefiltert auf „Geplant"**.

### D · Reiter Ideen

- **AK-17** · Angenommen, freigegebene Ideen liegen vor, wenn `/de/roadmap/ideen`
  geöffnet wird, dann zeigt der Reiter dieselben Bestandteile wie bisher
  `/de/community/ideen`: die Zahl der Ideen, die Liste mit Titel, Verfasser,
  Zustimmungen und Status, Sortierung, Statusfilter, Blätterung, Zustimmungsknöpfe und den
  Abschnitt „Umgesetzt". **Keine Funktion fällt weg.**
- **AK-18** · Angenommen, eine Idee ist freigegeben, wenn ihre Einzelseite geöffnet wird,
  dann liegt sie unter `/de/roadmap/ideen/<Nummer>-<Titel>`, und darüber steht der
  gemeinsame Kopf aus AK-05.
- **AK-19** · Angenommen, ein angemeldeter, bestätigter Nutzer reicht eine Idee ein, wenn
  er das Formular öffnet und absendet, dann liegt das Formular unter
  `/de/roadmap/ideen/neu`, die Bestätigungsseite danach unter `/de/roadmap/ideen/…`, beide
  führen mit einem Verweis zurück zum Reiter Ideen, und die Idee wartet wie bisher auf
  Freigabe.
- **AK-20** · Angenommen, ein angemeldeter Nutzer stimmt einer Idee zu oder zieht seine
  wartende Idee zurück, wenn er danach auf der Folgeseite landet, dann liegt diese unter
  `/…/roadmap/ideen` — **nie** unter einer alten Adresse.

### E · Alte Adressen

- **AK-21** · Angenommen, jemand ruft `/<Sprache>/community/ideen`,
  `/<Sprache>/community/ideen/neu` oder `/<Sprache>/community/ideen/eingereicht` auf,
  dann wird er mit einer **dauerhaften Weiterleitung (HTTP 301)** in **einem** Sprung auf
  die entsprechende Adresse unter `/<Sprache>/roadmap/ideen` geführt — in allen fünf
  Sprachen jeweils in dieselbe Sprache.
- **AK-22** · Angenommen, eine Mail zu einer Idee wurde vor der Umstellung verschickt und
  enthält `/<Sprache>/community/ideen/<Nummer>-<Titel>`, wenn der Empfänger den Link nach
  der Umstellung anklickt, dann landet er mit HTTP 301 in **einem** Sprung auf der
  Einzelseite **derselben** Idee unter `/<Sprache>/roadmap/ideen/<Nummer>-<Titel>`, in
  derselben Sprache.
- **AK-23** · Angenommen, eine Idee wird nach der Umstellung freigegeben, abgelehnt oder
  ändert ihren Status, wenn die Mail an ihren Verfasser gelesen wird, dann zeigt der Link
  darin **direkt** auf `/…/roadmap/ideen/…`, nicht auf die alte Adresse.
- **AK-24** · Angenommen, jemand hängt an eine alte Adresse eine fremde Adresse an
  (etwa `?next=https://example.com`, `?url=//example.com` oder als Pfadbestandteil), wenn
  die Weiterleitung antwortet, dann führt sie **ausschließlich** auf die feste neue
  Adresse unter endlech.lu — nie auf die angehängte.

### F · Auffindbarkeit

- **AK-25** · Angenommen, das Ausrollen liegt 60 Minuten zurück, wenn die Sitemap
  abgerufen wird, dann nennt sie `/<Sprache>/roadmap/ideen` in allen fünf Sprachen,
  **keine** Adresse unter `/community/ideen` mehr, und `/roadmap` und `/changelog` stehen
  weiterhin darin.
- **AK-26** · Angenommen, der Reiter Ideen ist geöffnet, wenn der Kopf des Dokuments
  gelesen wird, dann zeigt der maßgebliche Verweis (canonical) auf
  `https://endlech.lu/<Sprache>/roadmap/ideen`, die Sprachverweise nennen die fünf
  Fassungen dieser Adresse, und auf Folgeseiten bleibt die Seitenzahl darin erhalten — wie
  bisher bei `/community/ideen`.

### G · Sprache und Barrierefreiheit

- **AK-27** · Angenommen, einer der drei Reiter wird in einer der fünf Sprachen geöffnet,
  wenn er vollständig gelesen wird, dann sind Bereichsname, Reiter und der Knopf „Idee
  einreichen" in dieser Sprache beschriftet, und an keiner Stelle erscheint ein roher
  Übersetzungsschlüssel.
- **AK-28** · Angenommen, einer der drei Reiter, die Einzelseite einer Idee oder das
  Einreichformular ist geöffnet, wenn ein automatischer Prüflauf (axe-core) darüber läuft,
  dann meldet er **null Verstöße** — in allen fünf Sprachen.
- **AK-37** *(aus OF-01)* · Angenommen, der Reiter Roadmap, die Einzelseite einer Idee
  oder das Einreichformular ist in einer der fünf Sprachen geöffnet, wenn alle Texte
  gelesen werden, die auf den Ort der Ideen verweisen (Leertexte der Spalten, Überschrift
  der Community-Gruppe, Verweis an jeder Community-Idee, Rückweg), dann heißt er dort
  „Ideen" in der jeweiligen Sprache — **nirgends** „Ideen-Board" oder „Board".

### H · Datenschutz und Missbrauchsschutz

Der Katalog aus `~/.claude/sdd/sicherheit.md`, jede Frage einzeln. Was hier nicht als
Kriterium steht, wird später nicht geprüft.

**1 · Personenbezogene Daten.** Das Feature erfasst nichts und legt keinen Datensatz an.
Es zeigt ausschließlich, was Feature `06` bereits öffentlich zeigt — Ideentitel,
Beschreibungen, Anzeigenamen der Verfasser, Zustimmungszahlen —, nur unter anderer
Adresse. Besondere Kategorien nach Art. 9 werden nicht erhoben; dass sie unaufgefordert im
Freitext einer Idee stehen können, regelt `06` (Hinweis vor dem Absenden, Freigabe), und
daran ändert sich nichts. Eine eigene Löschfrist entsteht nicht.

- **AK-29** · Angenommen, eine Idee wartet noch auf Freigabe, wenn einer der drei Reiter
  oder eine der alten Adressen in irgendeiner Sprache und mit beliebigen Parametern
  aufgerufen wird, dann erscheint ihr Titel an **keiner** Stelle des ausgelieferten
  Quelltexts.
- **AK-30** · Angenommen, die drei Reiter und die alten Adressen werden je hundertmal
  aufgerufen, wenn danach das Anwendungsprotokoll gelesen wird, dann steht dort **keine**
  personenbezogene Angabe aus dem Board — kein Anzeigename, keine E-Mail-Adresse, kein
  Ideentext.

**2 · Weitergabe an externe Dienste.** Einziger Empfänger bleibt die selbst betriebene
Nutzungsmessung aus Feature `11`; sie bekommt den neuen Pfad statt des alten, sonst
nichts. Kein neuer Dienst, kein neuer AV-Vertrag.

- **AK-31** · Angenommen, der Reiter Ideen wird mit Sortierung und Filter aufgerufen, wenn
  der Zählaufruf an die Nutzungsmessung betrachtet wird, dann enthält er den Pfad
  `/<Sprache>/roadmap/ideen` **ohne** Abfrageteil und kein Feld, das er vor der Umstellung
  nicht enthielt.
- **AK-32** · Angenommen, einer der drei Reiter wird geladen, wenn die Netzwerkanfragen
  betrachtet werden, dann geht **keine** an einen fremden Host — kein Bild, keine Schrift,
  kein Skript von außerhalb.

**3 · Zugriff.** Die Reiter sind lesend und für alle offen. Die Zugriffsregeln des Boards
— eine wartende Idee sieht nur ihr Verfasser, Zustimmen und Einreichen nur mit bestätigtem
Konto, Zurückziehen nur der Verfasser — werden nicht neu entschieden, sondern unter der
neuen Adresse übernommen. Weiterleitungen haben nur feste Ziele (AK-24). Es gibt keine
Rollen über die bestehenden hinaus.

- **AK-33** · Angenommen, eine Idee wartet auf Freigabe, wenn ein anderer als ihr
  Verfasser — angemeldet oder nicht — ihre Einzeladresse unter `/…/roadmap/ideen/…`
  aufruft, dann antwortet die Seite mit **HTTP 404**, und die Fehlerseite nennt den Titel
  nicht.

**4 · Missbrauch und Kosten.** Kein Aufruf kostet Geld, es gibt keinen Upload und keinen
neuen Mailversand. **Kein neuer Limiter**: Die Reiter sind lesend, die Weiterleitungen
laden keinen Bestand und lösen keine Mail aus (Decision Log 9). Die bestehenden Deckel für
Einreichen und Zustimmen zählen weiter **am Konto** und ziehen mit um.

- **AK-34** · Angenommen, ein Konto hat das Kontingent zum Einreichen oder zum Zustimmen
  ausgeschöpft, wenn es über die neue Adresse erneut einreicht oder zustimmt, dann greift
  derselbe Deckel mit derselben Meldung samt Wartezeit wie vor der Umstellung — der Umzug
  eröffnet **kein frisches Kontingent**.
- **AK-35** · Angenommen, 45 freigegebene Ideen liegen vor, darunter 12 mit Status
  `Geplant`, wenn der Reiter Ideen und der Reiter Roadmap geöffnet werden, dann zeigt der
  Reiter Ideen höchstens 20 je Seite und der Reiter Roadmap höchstens zehn — **kein Aufruf
  lädt alle 45**.

**5 · Löschen und Auskunft.** Das Feature speichert nichts über einen Nutzer.
Kontolöschung und Datenexport aus Feature `01` bleiben unverändert; was `06` beim Löschen
mit Ideen und Zustimmungen tut, gilt weiter.

- **AK-36** · Angenommen, ein Nutzer mit eingereichten Ideen fordert seinen Datenexport an,
  wenn die Ausgabedatei gelesen wird, dann enthält sie denselben Abschnitt zum Board wie
  vor der Umstellung und **keinen** zusätzlichen.

**6 · Geheimnisse.** *Trifft nicht zu*, weil das Feature keinen Schlüssel, kein Token und
keine Umgebungsvariable braucht.

## Edge Cases

- **EC-01** · Es gibt keine einzige freigegebene Idee → Der Reiter Ideen zeigt den leeren
  Zustand aus `06` mit Weg zum Einreichformular; der Knopf im Kopf steht zusätzlich da. Der
  Reiter Roadmap zeigt keine leere Community-Gruppe (07/EC-01).
- **EC-02** · Der Titel in einer alten Mail-Adresse passt nicht mehr zum heutigen Titel
  der Idee → Maßgeblich ist die Nummer; der Empfänger landet trotzdem auf der Idee.
- **EC-03** · Eine alte Mail-Adresse zeigt auf eine Idee, die inzwischen als Dublette
  zusammengeführt wurde → Erst die Weiterleitung auf die neue Adresse, dann wie bisher auf
  das Original — höchstens **zwei** Sprünge, und das Ziel ist das Original.
- **EC-04** · Jemand ruft `/roadmap` ohne Sprache auf → Er landet wie bisher in seiner
  Sprache auf dem Reiter Roadmap. `/roadmap/ideen` ohne Sprache ist keine Adresse (wie
  bisher `/community/ideen`) und antwortet mit 404.
- **EC-05** · Die längste Beschriftung der Reiter in Französisch oder Portugiesisch passt
  bei 320 px nicht in eine Zeile → Die Reiter brechen um; die Seite scrollt nicht
  waagerecht, keine Beschriftung wird abgeschnitten.
- **EC-06** · Ein Lesezeichen enthält `/de/community/ideen?status=planned&page=2` → Der
  Besucher landet auf dem Reiter Ideen. Ob Filter und Seite erhalten bleiben, sagt diese
  Spec nicht zu (Decision Log 12).
- **EC-07** · Der Reiter Ideen trägt **keinen** Aktualitätshinweis — der bezieht sich auf
  den Changelog und steht wie bisher auf Roadmap und Changelog.

## Offene Fragen

Keine offene mehr — OF-02 und OF-03 sind beantwortet (unten jeweils der Vermerk).

- **OF-02** *(beim Bau am 2026-11-07 gefunden, T08)* · **axe meldet auf jeder geprüften Seite
  genau einen Verstoß `color-contrast` — an der Wortmarke „Endlech.lu" im Seitenkopf**
  (`base.html.twig`, `text-brand` = `#01b6ed` auf Weiß, 2,35 : 1; nur ab 1280 px sichtbar,
  `hidden xl:block`). Er steht ebenso auf `/de/about` und `/de/restaurants`, also in der
  App-Hülle, nicht in diesem Feature; die Farbe ist die am 2026-09-15 entschiedene Markenfarbe
  (Feature 05, OF-11). **WCAG 2.1 SC 1.4.3 nimmt Logotypen ausdrücklich aus** („Text that is
  part of a logo or brand name has no contrast requirement"), EN 301 549 übernimmt das. Bis auf
  diesen Treffer meldet axe auf allen 25 Seiten (drei Reiter, Einzelseite, Formular × fünf
  Sprachen) **null** Verstöße. Vorschlag des Baus: AK-28 gilt als erfüllt, wenn außer dem
  Logotyp-Treffer der App-Hülle nichts gemeldet wird; die Wortmarke selbst ist keine Frage
  dieses Features. — **Michael bestätigt oder verwirft; bis dahin entscheidet die QA, wie sie
  AK-28 wertet.**
  ✔ **Beantwortet 2026-11-07 (Claude unter dem Sitzungsziel „Feature 16 approved“, ohne Rückfrage — Michael kann umentscheiden): AK-28 ist erfüllt, wenn axe außer dem
  Logotyp-Treffer der App-Hülle nichts meldet.** Die QA hat AK-28 am selben Tag so gewertet und den
  Treffer auf `main` nachgemessen. Die Wortmarke bleibt eine Frage der App-Hülle und der Markenfarbe,
  nicht dieses Features.

- **OF-03** *(aus der QA vom 2026-11-07, Hinweis H-1)* · Zählt die Kontaktzeile der Fußzeile
  (`mailto:support@endlech.lu`, „Open Source auf GitHub“) als „Weg für Rückmeldungen“ im Sinne von
  AK-03? Wörtlich gelesen ja, dann wären es drei.
  ✔ **Beantwortet 2026-11-07 (Claude unter dem Sitzungsziel „Feature 16 approved“, ohne Rückfrage — Michael kann umentscheiden): Nein.** AK-03 meint den Weg, eine Idee oder Rückmeldung **zur
  Plattform** öffentlich zu hinterlegen — so hat auch die QA von 06/AK-80 gewertet. Die Kontaktzeile ist
  Kontakt, kein Rückmeldeweg; sie bleibt, wie sie ist.


## Entschiedene Fragen

- **OF-01 · entschieden am 2026-11-07: Wo ein Text auf den Ort verweist, heißt er
  „Ideen".** Betroffen sind die Leertexte der Roadmap-Spalten, die Überschrift der
  Community-Gruppe, der Verweis an jeder Community-Idee und der Rückweg von Einzelseite und
  Formular — in allen fünf Sprachen. **Unverändert bleiben** der historische
  Changelog-Eintrag „Ideen-Board" (er beschreibt ein Release von damals) und die
  Überschrift des Boards („Ideen für Endlech.lu"). Als **AK-37** nachgetragen.
  *Entschieden von Claude unter dem Sitzungsziel „Feature 16 approved", ohne Rückfrage —
  Michael kann umentscheiden; es ist eine reine Textänderung.*

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Form des Zusammenführens | Ein Bereich mit gemeinsamem Kopf und drei Reitern, jeder eine eigene Seite. Verworfen: eine einzige lange Seite; Ideen nach Status in die Roadmap-Spalten | Geringster Eingriff: Beide Seiten behalten ihre Logik, die Roadmap liest weiter keine Parameter (07/AK-44), das Board blättert und filtert wie bisher. Jeder Reiter bleibt teilbar und funktioniert ohne JavaScript |
| 2 | Eigenes Feature oder Änderung an 06/07? | Eigenes Feature `16` | Es löst Zusagen aus **beiden** Specs ab (Tabelle oben). Als Nachtrag an einem der beiden stünde die Hälfte am falschen Ort |
| 3 | Name des Bereichs | „Roadmap & Ideen" | Nennt beide Hälften; wer in der Fußzeile bisher „Feedback & Ideen" suchte, findet „Ideen" wieder |
| 4 | Adresse des Changelogs | Bleibt `/changelog` | Keine Weiterleitung, kein Sitemap-Umbau, der Kurzlink bleibt gültig. Ein Reiter kann auf jede Adresse zeigen |
| 5 | Adresse des Boards | `/roadmap/ideen`, alte Adressen mit 301 | Der Pfad zeigt die Zugehörigkeit zum Bereich; die alte Adresse steht in verschickten Mails und in Suchmaschinen und muss weiter funktionieren |
| 6 | Fußzeile | Ein Eintrag „Roadmap & Ideen" unter „Mitmachen", landet auf dem Reiter Roadmap; „Roadmap" und „Changelog" verschwinden aus „Über Endlech" | Ein Bereich, ein Eingang. Der Changelog ist danach nur über seinen Reiter erreichbar — bewusst |
| 7 | Ort von „Idee einreichen" | Im gemeinsamen Kopf, auf allen drei Reitern | Der Fußzeileneintrag ist der einzige Rückmeldeweg (06/AK-80) und landet auf der Roadmap. Ohne den Knopf dort müsste, wer Feedback geben will, erst den Reiter finden |
| 8 | Bestehende Querverweise der Roadmap | Die Gruppe der zehn geplanten Ideen bleibt, der Block „Weiterlesen" entfällt | Die Gruppe zeigt, dass Zustimmen etwas bewirkt (07/US-03). „Weiterlesen" wiederholte nur, was die Reiter jetzt anbieten |
| 9 | Neuer Limiter? | Nein | Reiter sind lesend, Weiterleitungen laden keinen Bestand und lösen keine Mail aus — die Konvention aus `CLAUDE.md` greift nicht. Ein Deckel ohne Zweck wäre ein weiterer Limiter, den `LimiterCoverageTest` verlangt und der nichts schützt |
| 10 | 301 oder 302 | 301 | Die Umstellung ist dauerhaft; nur eine dauerhafte Weiterleitung lässt die neue Adresse die alte im Suchindex ablösen |
| 11 | Nutzungszahlen | Kein Zusammenführen der Historie | Umami zählt nach Pfad. Ab der Umstellung zählt der neue, die alten Zahlen bleiben unter der alten Adresse lesbar. Ein Umschreiben der Historie wäre ein Eingriff in die Messung ohne Gewinn |
| 12 | Welche Randfälle werden Kriterien? | Nur Links aus verschickten Mails (AK-22). **Nicht** zugesagt: Erhalt von Filter und Seite bei der Weiterleitung, Zustimmen aus einem vor der Umstellung geöffneten Tab, Verhalten einer unbekannten Idee unter alter Adresse | Betreiberentscheidung 2026-11-07. Die Mail-Links sind der einzige Weg, auf dem eine alte Adresse dauerhaft bei Nutzern liegt; die übrigen Fälle betreffen Lesezeichen und ein Zeitfenster von Minuten. Dass keine wartende Idee über eine alte Adresse sichtbar wird, sichert AK-29 unabhängig davon |
| 13 | Abnahmesignal des Betreibers | „Ein Klick, drei Reiter": aus der Fußzeile in den Bereich, alle Reiter erreichbar, aktiver erkennbar, ohne JavaScript und bei 320 px | Betreiberentscheidung 2026-11-07 — abgebildet in AK-01, AK-06, AK-07, AK-08 |
| 14 | Kopf auf Formular und Bestätigungsseite? | Nein — dort ein Verweis zurück zum Reiter Ideen (AK-19). Der Kopf steht auf den drei Reitern und der Einzelseite einer Idee | Vorschlag dieser Spec. Ein Knopf „Idee einreichen" über dem Einreichformular führte auf sich selbst, Reiter lenkten vom Ausfüllen ab |
| 16 | Begriff „Ideen-Board" (OF-01) | Wo ein Text auf den Ort verweist: „Ideen"; Changelog-Historie und Board-Überschrift bleiben | Der Reiter heißt „Ideen"; ein Text, der auf ein „Board" verweist, das es als eigenen Ort nicht mehr gibt, schickt den Leser auf die Suche. Ein historischer Release-Titel beschreibt den damaligen Stand und wird nicht umgeschrieben |
| 17 | AK-28 und die Wortmarke (OF-02) | Logotyp-Treffer der App-Hülle zählt nicht | WCAG 1.4.3 nimmt Logotypen aus; der Treffer steht auf jeder Seite, auch vor diesem Feature |
| 18 | Kontaktzeile als Rückmeldeweg? (OF-03) | Nein | AK-03 meint den öffentlichen Weg für Ideen; die Kontaktzeile ist Kontakt — Wertung wie bei 06/AK-80 |
| 19 | AK-05: Kopf umbauen oder Kriterium lockern? (BF-200) | Kopf umbauen: Reiterleiste direkt unter Name und Knopf, Überschrift und Einleitung darunter; alle Reiter gleich fett | Die Leiste ist das Navigationselement des Bereichs und soll beim Wechseln stillstehen; ein gelockertes Kriterium hätte das Springen festgeschrieben. Entschieden von Claude unter dem Sitzungsziel |
| 20 | Reichweite von OF-01 (BF-201) | Auch Leerzustand, Bestätigungsseite, Freigabe-Mail und Verwaltungsmeldung sagen „Ideen“ | Die Regel aus OF-01 lautet „wo ein Text auf den Ort verweist“ — diese vier tun es; AK-37 hatte sie nur nicht aufgezählt. Entschieden von Claude unter dem Sitzungsziel |
| 15 | Verwaltung | Bleibt unter `/admin/ideen` | Die Verwaltung hat eigene Navigation; ihre Adresse kennt nur das Team, und nichts daran wird besser durch einen Umzug |
