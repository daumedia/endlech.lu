# 07 · Öffentliche Roadmap und Changelog — Spezifikation

Status: `planned` · Stand: 2026-08-30

## Zweck

Ein Besucher kann heute nicht erkennen, ob an Endlech.lu noch gearbeitet wird. Der
Changelog liegt als `CHANGELOG.md` im Repository — 21 Releases, einsprachig deutsch,
mit Sätzen wie „`AdminStatsService` hat ein fünftes Konstruktorargument". Die Roadmap
steht als Tabelle *Belegt offen* in `docs/prd.md`, ebenfalls nur im Repository. Beide
Dokumente sind öffentlich zugänglich und trotzdem unsichtbar: Wer nicht weiß, dass es
ein GitHub-Repository gibt, findet sie nie.

Bis zum 30. August 2026 gab es genau eine öffentlich sichtbare Statusanzeige — das
externe Board `endlech.userjot.com` mit seinen Marken „Planned" und „Pending". Feature
`06` hat es abgelöst und abgeschaltet. Das eigene Board zeigt seither, was die
**Community** sich wünscht; was der **Betreiber** vorhat, zeigt es nicht.

Nach diesem Feature gibt es zwei öffentliche Adressen: `/roadmap` beantwortet „woran
wird gerade gearbeitet und was kommt danach", `/changelog` beantwortet „was hat sich
zuletzt geändert" — beide in allen vier Sprachen, ohne Termine, ohne Codebegriffe.

## Vorbedingungen

Keine Funktion, aber ohne sie ist das Feature nicht auslieferbar. Sie werden vor dem
Bau geklärt, nicht danach.

| | Was fehlt | Warum es blockiert |
|---|---|---|
| **VB-01** | Feature `06` ist auf `dev` abgenommen, aber **noch nicht nach `production` gemerged** | Die Roadmap liest den Board-Bestand und verlinkt auf `app_board_index`. Wird `07` vor `06` ausgeliefert, zeigt die Roadmap eine Spalte ohne Quelle und einen Verweis ins Leere |
| ~~**VB-02**~~ | **erfüllt am 2026-08-30** — die Spaltenzuordnung steht (Decision Log 15, Tabelle in `design.md`) | — |
| ~~**VB-03**~~ | **erfüllt am 2026-08-30** — neun merkbare Releases plus Sammelzeile „Aufbau der Plattform" (OF-01) | — |

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| 06 · Community Feedback Board | `approved`, wartet auf Merge | Liefert die Ideen mit Status `Geplant`, deren Zustimmungszahlen und die Zieladresse der Verweise (AK-12 bis AK-18). Siehe **VB-01** |
| B24 · Mehrsprachigkeit | `approved` | Beide Seiten in vier Sprachfassungen; der Katalogtest ist zugleich das Gate gegen halb übersetzte Einträge (AK-30 bis AK-33) |
| 02 · Barrierefreiheit der Plattform | `deployed` | Gilt für jede neue Seite. Der Prüflauf muss die beiden neuen Adressen kennen (AK-34 bis AK-38) |
| B13 · Statische Inhaltsseiten | `approved` | Liefert das Muster für eine Außenseite ohne Entität, dazu den Fußzeilenplatz |
| B16 · Transparenzseite `/open` | `approved` | Liefert die Veralterungsregel (60 Tage → hervorgehobener Kasten), die hier übernommen wird (AK-27) |
| 03 · Vergleichsseiten · 05 · Presse-Kit | `deployed` | Liefern die Bauart: unveränderliche Strukturen im Code, Texte in eigener Übersetzungsdomain, keine Entität |

## User Stories

- **US-01** · Als Besucherin möchte ich in dreißig Sekunden sehen, woran gerade
  gearbeitet wird, damit ich weiß, ob die Plattform lebt, bevor ich ein Restaurant
  einreiche.
- **US-02** · Als Gast möchte ich nachlesen, was sich seit meinem letzten Besuch
  geändert hat, ohne ein Repository zu öffnen oder Deutsch zu können.
- **US-03** · Als jemand, der eine Idee im Board eingereicht hat, möchte ich sie auf
  der Roadmap wiederfinden, wenn das Team sie eingeplant hat — damit ich sehe, dass
  Zustimmen etwas bewirkt.
- **US-04** · Als Gemeinde oder Fördergeber möchte ich vor einem Gespräch erkennen,
  was als Nächstes kommt, damit ich einschätzen kann, worauf ich mich einlasse.
- **US-05** · Als Betreiber möchte ich sagen können, was ich **nicht** baue und warum,
  damit die immer gleiche Frage nicht immer wieder gestellt wird.
- **US-06** · Als Betreiber möchte ich keine Termine zusagen, damit eine Verzögerung
  keine gebrochene Zusage ist.

## Nicht im Scope

- **Ein maschinenlesbarer Feed** (RSS, Atom oder JSON). Zurückgestellt, bis jemand
  danach fragt — siehe Decision Log 8. Anders als `/open.json`, das für ein konkretes
  Fördergespräch entstand, gibt es hier keinen belegten Bedarf.
- **Automatisches Erzeugen der Einträge aus `CHANGELOG.md`.** Verworfen, Decision
  Log 2. Die Datei bleibt die technische Fassung und wird verlinkt.
- **Termine, Quartale oder Fortschrittsbalken** an Roadmap-Einträgen. Decision Log 5.
- **Eine Verwaltungsmaske für Roadmap und Changelog.** Beide Listen ändern sich im
  Takt von Releases, und ein Release ist ohnehin ein Deploy. Eine Entität, eine
  Migration und eine Maske dafür wären Struktur ohne Gewinn.
- **Abstimmen über Roadmap-Einträge.** Das ist das Board (Feature `06`); zwei
  Abstimmungswege für dieselbe Frage teilen die Stimmen — derselbe Fehler, den die
  Abschaltung von `endlech.userjot.com` gerade behoben hat.
- **Benachrichtigung, wenn ein Vorhaben umgesetzt wurde.** Feature `06` verschickt
  bereits eine Mail bei Veröffentlichung einer Idee; eine zweite Meldung aus der
  Roadmap wäre für denselben Nutzer die zweite Mail zur selben Sache.
- **Rückwirkendes Umschreiben von `CHANGELOG.md`.** Die Datei bleibt, wie sie ist,
  einschließlich der alten Buchstabenform `2026.03.08b`.
- **Eine Statusanzeige des Betriebs** („läuft die Seite gerade"). Anderes Thema,
  anderer Auslöser, gehört zu `sdd-betrieb`.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar. Wo eine Zahl steht, ist sie Teil des
Kriteriums.

### A · Roadmap finden und lesen

- **AK-01** · Angenommen, ein Besucher ist nicht angemeldet, wenn er `/de/roadmap`
  aufruft, dann erscheint die Seite mit HTTP 200 und ohne Aufforderung, sich
  anzumelden.
- **AK-02** · Angenommen, ein Besucher steht auf einer beliebigen Seite außerhalb der
  Verwaltung, wenn er die Fußzeile liest, dann findet er dort je einen Verweis auf die
  Roadmap und auf den Changelog, ohne die Adressen zu kennen.
- **AK-03** · Angenommen, die Roadmap ist geöffnet, wenn ein Besucher sie ohne
  Scrollen betrachtet, dann ist ablesbar, **woran gerade gearbeitet wird** — die Spalte
  „In Arbeit" steht im ersten Bildschirm, auf einem Gerät mit 800 px Höhe.
- **AK-04** · Angenommen, die Roadmap ist geöffnet, wenn sie gelesen wird, dann trägt
  sie genau drei Spalten in dieser Reihenfolge: „In Arbeit", „Geplant", „Angedacht" —
  und keine vierte.
- **AK-05** · Angenommen, ein Vorhaben steht in einer der drei Spalten, wenn es
  angezeigt wird, dann trägt es einen Titel und **einen Satz Begründung**, warum es
  vorgesehen ist. Kein Eintrag besteht nur aus einem Titel.
- **AK-06** · Angenommen, die Roadmap ist geöffnet, wenn sie vollständig gelesen wird,
  dann erscheint an **keinem** Eintrag ein Datum, ein Quartal, ein Monat oder eine
  Prozentangabe zum Fortschritt.
- **AK-07** · Angenommen, die Roadmap ist geöffnet, wenn bis zum Ende gelesen wird,
  dann folgt ein eigener Block „Bewusst nicht gebaut" mit mindestens den acht
  zurückgestellten Punkten aus `CLAUDE.md`, jeder mit Begründung.
- **AK-08** · Angenommen, ein Vorhaben steht im Block „Bewusst nicht gebaut", wenn es
  gelesen wird, dann ist ohne Vorwissen erkennbar, dass es **nicht** kommt — es steht
  nicht unter derselben Überschrift wie die drei Spalten.
- **AK-09** · Angenommen, eine Spalte enthält keinen einzigen Eintrag, wenn die
  Roadmap geöffnet wird, dann erscheint dort ein erklärender Satz statt einer leeren
  Fläche — und die Seite bleibt HTTP 200.
- **AK-10** · Angenommen, die Roadmap ist geöffnet, wenn ein Besucher weiterlesen
  will, dann findet er von dort in **einem** Klick den Changelog und das Ideen-Board.
- **AK-11** · Angenommen, jemand ruft `/de/roadmap` mit einer anderen Methode als GET
  oder HEAD auf, dann antwortet die Anwendung mit HTTP 405 und es entsteht kein
  Datensatz. Dasselbe gilt für `/de/changelog`.

### B · Community-Ideen auf der Roadmap

- **AK-12** · Angenommen, im Board stehen vier veröffentlichte Ideen mit Status
  `Geplant`, wenn die Roadmap geöffnet wird, dann erscheinen genau diese vier in der
  Spalte „Geplant".
- **AK-13** · Angenommen, im Board stehen Ideen mit den Status `Neu`, `In Prüfung`,
  `Umgesetzt` und `Abgelehnt`, wenn die Roadmap geöffnet wird, dann erscheint **keine**
  von ihnen.
- **AK-14** · Angenommen, eine Idee wartet noch auf Freigabe (nicht veröffentlicht)
  und trägt bereits den Status `Geplant`, wenn die Roadmap geöffnet wird, dann
  erscheint sie **nicht** — weder Titel noch Zustimmungszahl.
- **AK-15** · Angenommen, eine Community-Idee steht in der Spalte „Geplant", wenn sie
  angezeigt wird, dann sind Titel, Zahl der Zustimmungen und ein Verweis auf die Idee
  im Board sichtbar — und **kein Verfassername**.
- **AK-16** · Angenommen, eine Community-Idee und ein Betreiber-Vorhaben stehen in
  derselben Spalte, wenn beide angezeigt werden, dann ist ohne Klick erkennbar, welche
  aus der Community stammt.
- **AK-17** · Angenommen, zwölf veröffentlichte Ideen tragen den Status `Geplant`,
  wenn die Roadmap geöffnet wird, dann erscheinen **höchstens zehn** — die mit den
  meisten Zustimmungen — und ein Verweis auf das Board für die übrigen. **Kein Aufruf
  lädt alle zwölf.**
- **AK-18** · Angenommen, eine Idee steht in der Spalte „Geplant", wenn ihr Status im
  Board auf `Abgelehnt` gesetzt oder ihre Veröffentlichung zurückgenommen wird, dann
  ist sie beim nächsten Aufruf der Roadmap **verschwunden** — ohne Deploy und ohne
  weiteren Handgriff.
- **AK-52** · Angenommen, in der Spalte „Geplant" stehen Community-Ideen, wenn die
  Gruppe gelesen wird, dann ist **über ihr ausgewiesen, dass die zehn mit den meisten
  Zustimmungen erscheinen** — unabhängig davon, ob gerade mehr als zehn vorliegen.
  *(Nachgetragen am 2026-08-30 aus OF-06.)*

### C · Changelog

- **AK-19** · Angenommen, ein Besucher ist nicht angemeldet, wenn er `/de/changelog`
  aufruft, dann erscheint die Seite mit HTTP 200.
- **AK-20** · Angenommen, ein Release hat einen öffentlichen Eintrag, wenn der
  Changelog geöffnet wird, dann sind Versionsnummer, Datum, ein Titel und ein
  verständlicher Text sichtbar — und der Text enthält **keinen Klassennamen, keinen
  Dateipfad und keinen Migrationsnamen**.
- **AK-21** · Angenommen, ein Release wurde als „still" verzeichnet (rein technisch,
  ohne merkbare Änderung), wenn der Changelog geöffnet wird, dann erscheint es dort
  **nicht**.
- **AK-22** · Angenommen, Einträge aus zwei Jahren liegen vor, wenn der Changelog
  geöffnet wird, dann sind die Einträge des laufenden Jahres offen sichtbar und
  frühere Jahre je Jahr zusammengeklappt und aufklappbar.
- **AK-23** · Angenommen, ein früheres Jahr ist zugeklappt, wenn ein Besucher es
  öffnet, dann erscheinen dessen Einträge — **ohne dass die Seite neu geladen wird und
  ohne JavaScript**.
- **AK-24** · Angenommen, der Changelog ist geöffnet, wenn er gelesen wird, dann führt
  ein sichtbarer Verweis zur vollständigen technischen Fassung (`CHANGELOG.md`), und
  es ist angesagt, dass diese nur auf Deutsch vorliegt.
- **AK-25** · Angenommen, es existiert kein einziger öffentlicher Eintrag, wenn der
  Changelog geöffnet wird, dann erscheint ein erklärender leerer Zustand mit Verweis
  auf die technische Fassung — keine leere Seite und keine Fehlerseite.
- **AK-51** · Angenommen, der Changelog ist geöffnet, wenn der Verweis auf
  `CHANGELOG.md` gelesen wird, dann steht **daneben ein Satz, was den Leser dort
  erwartet** — die vollständige technische Fassung aus Entwicklersicht, unkommentiert
  und nur auf Deutsch. *(Nachgetragen am 2026-08-30 aus OF-05.)*
- **AK-26** · Angenommen, in `CHANGELOG.md` steht eine Version, die weder einen
  öffentlichen Eintrag noch einen ausdrücklichen Vermerk „still" trägt, wenn der
  Prüflauf läuft, dann **schlägt er fehl** und nennt die Version beim Namen.
  *(Der Nachweis ist ein absichtlich entfernter Eintrag, der den Lauf rot färbt.)*

### D · Aktualität und Ehrlichkeit

- **AK-27** · Angenommen, der jüngste Changelog-Eintrag ist 61 Tage alt, wenn eine der
  beiden Seiten geöffnet wird, dann steht der Aktualitätshinweis in einem hervorgehobenen
  Kasten statt im grauen Kleingedruckten — dieselbe Regel wie bei den Finanzzahlen auf
  `/open`.
- **AK-28** · Angenommen, der jüngste Eintrag ist 10 Tage alt, wenn eine der beiden
  Seiten geöffnet wird, dann ist sein Datum sichtbar und der Hinweis **nicht**
  hervorgehoben.
- **AK-29** · Angenommen, ein Vorhaben steht auf der Roadmap, wenn es gelesen wird,
  dann trägt es entweder eine Begründung des Betreibers oder einen Verweis auf die
  Community-Idee, aus der es stammt. **Es gibt keinen Eintrag ohne Herkunft.**

### E · Mehrsprachigkeit

- **AK-30** · Angenommen, ein Besucher ruft `/lb/roadmap`, `/de/roadmap`,
  `/fr/roadmap` und `/en/roadmap` auf, dann antworten alle vier mit HTTP 200 und
  zeigen den Rahmen in der jeweiligen Sprache. Dasselbe gilt für `/changelog`.
- **AK-31** · Angenommen, ein Changelog-Eintrag ist in nur drei der vier Kataloge
  gepflegt, wenn der Prüflauf läuft, dann **schlägt er fehl** — der Eintrag erreicht
  die Produktion nicht.
- **AK-32** · Angenommen, eine der vier Sprachfassungen wird geöffnet, wenn sie
  vollständig gelesen wird, dann erscheint an keiner Stelle ein roher
  Übersetzungsschlüssel (`changelog.` oder `roadmap.`).
- **AK-33** · Angenommen, eine Community-Idee wurde auf Französisch eingereicht, wenn
  sie auf der deutschen Roadmap erscheint, dann ist ihr Titel als französischer Text
  ausgezeichnet (`lang`-Attribut). ⚠ **Das Board tut das heute nur bei der
  Beschreibung, nicht beim Titel** (`_board_idea_card.html.twig:86`, gefunden beim
  Entwurf am 2026-08-30) — die Roadmap übernimmt das Muster also nicht, sie stellt es
  erstmals her.

### F · Barrierefreiheit

- **AK-34** · Angenommen, eine der beiden Seiten ist geöffnet, wenn ein automatischer
  Prüflauf (axe-core) darüber läuft, dann meldet er **null Verstöße** — in allen vier
  Sprachfassungen.
- **AK-35** · Angenommen, das Browserfenster ist 320 px breit, wenn eine der beiden
  Seiten geöffnet wird, dann lässt sie sich **nicht waagerecht scrollen**; die drei
  Roadmap-Spalten stehen untereinander.
- **AK-36** · Angenommen, ein Besucher bedient die Seite nur mit der Tastatur, wenn er
  durchtabbt, dann sind alle Verweise und alle Jahresabschnitte erreichbar und
  auslösbar, und der Fokus ist an jeder Station sichtbar.
- **AK-37** · Angenommen, ein Screenreader liest die Roadmap, wenn er die Spalten
  erreicht, dann ist jede Spalte als eigener Abschnitt mit Überschrift angesagt — die
  Zugehörigkeit eines Eintrags ergibt sich nicht allein aus seiner Position.
- **AK-38** · Angenommen, eine der beiden Seiten ist geöffnet, wenn ihre Überschriften
  gelesen werden, dann gibt es genau eine `h1`, und die Ebenen sind lückenlos.

### G · Datenschutz und Missbrauchsschutz

Der Katalog aus `~/.claude/sdd/sicherheit.md`, jede Frage einzeln. Was hier nicht als
Kriterium steht, wird später nicht geprüft.

**1 · Personenbezogene Daten.** Beide Seiten erfassen nichts — es gibt kein Formular,
keinen Knopf, der schreibt, und keinen Datensatz. Berührt werden ausschließlich Daten,
die Feature `06` bereits öffentlich zeigt: Ideentitel und Zustimmungszahlen. Besondere
Kategorien nach Art. 9 sind nicht betroffen. Eine eigene Löschfrist entsteht nicht,
weil kein eigener Bestand entsteht.

- **AK-39** · Angenommen, eine Community-Idee erscheint auf der Roadmap, wenn die
  Seite vollständig gelesen wird, dann steht dort **kein Anzeigename, keine
  E-Mail-Adresse und keine Kennung** ihres Verfassers.
- **AK-40** · Angenommen, ein öffentlicher Changelog-Eintrag wird gelesen, dann nennt
  er **keine natürliche Person** — weder als Beitragenden noch als Ideengeber noch in
  einer Danksagung.
- **AK-41** · Angenommen, eine der beiden Seiten wird hundertmal aufgerufen, wenn
  danach das Anwendungsprotokoll gelesen wird, dann steht dort keine personenbezogene
  Angabe aus dem Board.

**2 · Weitergabe an externe Dienste.** *Trifft nicht zu* — keine Daten verlassen die
Anwendung. Der Verweis auf `CHANGELOG.md` ist ein gewöhnlicher Link, den der Besucher
selbst anklickt; ein Feed, der Inhalte an Dritte ausliefert, ist ausdrücklich nicht im
Scope (Decision Log 8). Ein AV-Vertrag ist deshalb nicht berührt.

- **AK-42** · Angenommen, eine der beiden Seiten wird geladen, wenn die Netzwerkanfragen
  betrachtet werden, dann geht **keine** an einen fremden Host — kein eingebettetes
  Bild, keine Schrift, kein Skript von außerhalb.

**3 · Zugriff.** Beide Seiten sind rein lesend und für alle offen; es gibt keine
Rollen, keine Detailseiten mit fremder Kennung und damit keinen IDOR-Weg. Die einzige
Zugriffsregel ist die Sichtbarkeitsgrenze des Boards, und sie wird nicht neu
entschieden, sondern übernommen.

- **AK-43** · Angenommen, das Board enthält eine nie freigegebene Idee, wenn die
  Roadmap in irgendeiner Sprachfassung und mit beliebigen Parametern aufgerufen wird,
  dann erscheint deren Titel an **keiner** Stelle des ausgelieferten Quelltextes.
- **AK-44** · Angenommen, jemand hängt an `/de/roadmap` beliebige Parameter an, wenn
  die Seite antwortet, dann erscheint keine Eingabe von ihm in der Antwort und die
  Seite bleibt HTTP 200 oder antwortet mit 404 — nie mit einem Serverfehler.

**4 · Missbrauch und Kosten.** Kein Aufruf kostet Geld, es gibt keinen Upload, keinen
Mailversand und keine Anmeldung. Ein Rate Limit wird bewusst **nicht** gesetzt: Beide
Seiten sind rein lesend, und ein Deckel sperrte hier Besucher aus statt Angreifer
(Decision Log 7). Die Konvention aus `CLAUDE.md` — jeder Weg, der bei jedem Aufruf den
gesamten Bestand lädt, braucht einen Deckel — wird stattdessen an der Ursache erfüllt:
Der Bestand wird nie vollständig geladen.

- **AK-45** · Angenommen, das Board enthält 200 veröffentlichte Ideen mit Status
  `Geplant`, wenn die Roadmap geöffnet wird, dann fragt sie höchstens die zehn
  benötigten ab — **keine Abfrage liefert alle 200**.
- **AK-46** · Angenommen, die Roadmap wurde einmal geöffnet, wenn sie unmittelbar
  danach erneut geöffnet wird, dann entsteht **keine erneute Datenbankabfrage** an das
  Board; die Antwort kommt aus dem Zwischenspeicher.
- **AK-47** · Angenommen, eine Idee wechselt im Board ihren Status, wenn die Roadmap
  danach geöffnet wird, dann zeigt sie den neuen Stand — der Zwischenspeicher wird bei
  der Änderung verworfen und nicht erst nach Ablauf einer Frist.

**5 · Löschen und Auskunft.** Das Feature legt keine Daten an, also gibt es nichts zu
löschen und nichts zu exportieren; die Betroffenenrechte aus Feature `01` bleiben
unverändert und brauchen **keine** Erweiterung. Der einzige Berührungspunkt ist die
Kontolöschung eines Ideenverfassers.

- **AK-48** · Angenommen, eine Community-Idee steht in der Spalte „Geplant" und ihr
  Verfasser löscht sein Konto, wenn die Roadmap danach geöffnet wird, dann steht die
  Idee unverändert dort — ohne Verfasserbezug, wie in Feature `06` zugesagt — und die
  Seite antwortet mit HTTP 200 statt mit einem Fehler.
- **AK-49** · Angenommen, ein Nutzer fordert seinen Datenexport an, wenn die
  Ausgabedatei gelesen wird, dann enthält sie **keinen** zusätzlichen Abschnitt aus
  diesem Feature — es speichert nichts über ihn.

**6 · Geheimnisse.** *Trifft nicht zu* — das Feature braucht keinen Schlüssel, keinen
Zugangsdatensatz und keine Umgebungsvariable mit einem echten Wert.

- **AK-50** · Angenommen, der ausgelieferte Quelltext beider Seiten wird gelesen, dann
  enthält er keinen Schlüssel, keinen Token und keinen internen Pfad.

## Edge Cases

- **EC-01** · Das Board enthält keine einzige veröffentlichte Idee mit Status
  `Geplant` → Die Spalte zeigt ausschließlich die Betreiber-Vorhaben; es erscheint
  kein leerer Community-Block und kein Hinweis auf ein Versäumnis.
- **EC-02** · Eine Idee wird depubliziert, während ein Besucher die Roadmap geöffnet
  hat → Beim nächsten Aufruf ist sie weg; ein Klick auf den noch offenen Verweis führt
  zu 404, nicht zum Beitrag.
- **EC-03** · Zwei geplante Ideen haben dieselbe Zahl an Zustimmungen → Die neuere
  steht oben; die Reihenfolge ist bei gleichem Aufruf stabil und wechselt nicht
  zwischen zwei Ladevorgängen.
- **EC-04** · Genau elf Ideen tragen `Geplant` → Zehn erscheinen, der Hinweis auf die
  übrigen nennt die Zahl **1**, nicht „weitere".
- **EC-05** · Ein Punkt aus „Bewusst nicht gebaut" wird doch gebaut → Er wandert in
  eine der drei Spalten und verschwindet aus dem Block; der Changelog-Eintrag zur
  Umsetzung erwähnt, dass die Haltung sich geändert hat.
- **EC-06** · Ein Release wird zurückgezogen (Revert) → Sein öffentlicher Eintrag wird
  entfernt und der Vorgang im nächsten Eintrag benannt; der Prüflauf aus AK-26 bleibt
  grün, weil auch die zurückgezogene Version einen Vermerk trägt.
- **EC-07** · Jahreswechsel → Das neue Jahr ist ab dem ersten Eintrag offen, das
  vorherige klappt zu; die Seite bleibt ohne Handgriff korrekt.
- **EC-08** · Ein Ideentitel ist 120 Zeichen lang (das Maximum aus Feature `06`) →
  Die Kachel bricht um, statt die Spalte zu verbreitern; bei 320 px entsteht kein
  Querscrollen.
- **EC-09** · Ein Ideentitel besteht aus Emoji oder arabischer Schrift → Er wird
  dargestellt, wie er im Board steht; die Leserichtung des umgebenden Textes bleibt
  unverändert.
- **EC-10** · Ein Besucher öffnet `/roadmap` ohne Sprachpräfix → Er landet per
  Weiterleitung auf der Sprachfassung, in **einem** Sprung und ohne 301 — die Lehre
  aus BF-100.
- **EC-11** · Der Zwischenspeicher ist leer oder nicht verfügbar → Die Seite wird
  gerendert, indem sie das Board direkt abfragt; kein Fehler, nur langsamer.

## Entschiedene Fragen

Alle sieben am **2026-08-30** von Michael entschieden. Sie bleiben mit ihrer
ursprünglichen Nummer stehen — `design.md` und `tasks.md` verweisen darauf.

- **OF-01 · entschieden: neun merkbare Releases plus eine Sammelzeile.** Öffentlich
  werden die neun mit sichtbarer Wirkung für Gäste (Community-Board, Presse-Kit,
  Vergleichsseiten, Vorschlags-Wizard, PWA und App-Anbindung, Küchen · Öffnungszeiten ·
  Nahverkehr, Profil · Fotos, Filter, Fotogalerie); davor steht eine Sammelzeile
  „Aufbau der Plattform" für Januar bis März 2026. Alle übrigen tragen den Vermerk
  **still**. Damit ist **VB-03 erfüllt**.
- **OF-02 · entschieden: Bewertungen und Kommentare stehen auf der Roadmap, ohne Bezug
  auf das Werbeversprechen.** Der Eintrag trägt eine sachliche Begründung wie jeder
  andere. ⚠ **Das Risiko 1 aus dem PRD bleibt damit offen** — die Startseite wirbt
  weiter mit „echten Bewertungen", die es nicht gibt, und die Roadmap benennt diesen
  Widerspruch nicht. Das ist eine bewusste Entscheidung, kein Versehen: Sie gehört in
  ein eigenes Vorhaben (Texte der Startseite), nicht in dieses Feature.
- **OF-03 · entschieden: bei jedem Release mitprüfen.** Die Durchsicht der Roadmap
  hängt sich an den fünften Punkt der Release-Checkliste, der ohnehin abgearbeitet
  wird. Kein eigener Termin — ein Release ist genau der Moment, in dem sich etwas
  verändert hat.
- **OF-04 · entschieden: Rückstufung nach „Angedacht".** Ein Vorhaben, das zwölf
  Monate ohne Fortschritt in „Geplant" steht, wandert zurück, mit einem Satz dazu.
  Eine Zusage, die faktisch keine mehr ist, wird nicht konserviert.
- **OF-05 · entschieden: Erklärsatz ergänzen.** Der Hinweis aus AK-24 genügt nicht;
  daneben steht ein Satz, was den Leser im Repository erwartet. Als **AK-51**
  nachgetragen.
- **OF-06 · entschieden: die Auswahlregel wird immer ausgewiesen.** Über der
  Community-Gruppe steht, dass die zehn mit den meisten Zustimmungen erscheinen — auch
  dann, wenn gerade weniger vorliegen. Das ist zugleich die Aufforderung, im Board
  zuzustimmen. Als **AK-52** nachgetragen.
- **OF-07** *(aus `design.md`)* · **entschieden: Lebensdauer 3600 Sekunden.** Der
  Entity-Listener deckt Statuswechsel, Stimmen und Kontolöschungen bereits ab; die
  Dauer ist nur das Netz für Wege, die niemand vorhergesehen hat. Fünf Minuten hätten
  zwölfmal so viele Abfragen gekostet, um ein Fenster zu verkürzen, das der Listener
  in aller Regel gar nicht erst entstehen lässt.

### Was dadurch neu offen ist

- **OF-08** · Die Texte der Startseite („Bewerten", „Echte Bewertungen von echten
  Besuchern") behaupten weiterhin eine Funktion, die es nicht gibt — OF-02 hat das
  bewusst nicht aufgelöst. Entweder wird die Funktion gebaut oder der Text angepasst;
  beides ist ein eigenes Vorhaben und **gehört als Zeile ins Feature-Inventar**, nicht
  in dieses Feature. — **Michael entscheidet, wann.**
- **OF-09** *(beim Bau am 2026-08-30 gefunden)* · **AK-44 ist nur teilweise
  erfüllt, und die Ursache liegt in der App-Hülle.** Der `hreflang`-Block in
  `base.html.twig` (Feature B24) übernimmt die Abfragezeichenfolge in die
  Alternativ-Verweise: `/de/roadmap?stage=secret` liefert
  `<link rel="alternate" href="/lb/roadmap?stage=secret">`. Damit erscheint eine
  Eingabe des Aufrufers in der Antwort, obwohl das Kriterium das ausschließt.
  **Kein Sicherheitsproblem** — die Ausgabe ist escaped, `<script>` bleibt wirkungslos,
  nachgemessen. Aber **projektweit**: nachgestellt auf `/presse`, `/open`, `/about` und
  `/restaurants`, alle vier spiegeln ebenso. Eine Reparatur gehört an die Hülle und
  betrifft jede Seite; dieses Feature hat sie sichtbar gemacht, nicht verursacht.
  — **Michael entscheidet, ob das ein eigener Befund an B24 wird.**
- **OF-10** *(beim Bau am 2026-08-30 gefunden)* · **AK-38 ist im Inhaltsbereich
  erfüllt, seitenweit nicht — auch das eine Altlast der App-Hülle.** Die Fußzeile
  überschreibt ihre vier Spalten mit `<h4>`; die letzte Inhaltsüberschrift ist eine
  `h2`, also springt die Überschriftenkette jeder Seite von h2 auf h4. Ein
  Screenreader meldet damit eine Ebene, zu der es keine übergeordnete gibt (WCAG
  1.3.1). **Projektweit**: nachgemessen an `/presse` (`…,2,4,4,4`), `/open`,
  `/about` und `/vergleich`. Innerhalb von `<main>` ist die Kette auf beiden neuen
  Seiten lückenlos. Die Reparatur wäre eine Zeile in `base.html.twig` (`h4` → `h2`
  in der Fußzeile), betrifft aber **jede** Seite und gehört deshalb zu Feature `02`
  oder in einen eigenen Auftrag an die Hülle. — **Michael entscheidet, wohin der
  Befund gehört.**
- **OF-11** *(beim Beheben von BF-115 am 2026-08-30 aufgefallen)* · **Der
  Aktualitätshinweis unterscheidet nicht zwischen Vergangenheit und Zukunft.**
  `date().diff()` liefert die Tagesdifferenz als Betrag; ein Eintragsdatum, das
  versehentlich in der Zukunft liegt (Tippfehler beim Release: `2027` statt `2026`),
  erzeugt die Meldung „Zuletzt aktualisiert am 15. Januar 2027 — seither sind 136 Tage
  vergangen. Diese Seite ist möglicherweise nicht mehr aktuell." — an der laufenden
  Anwendung gemessen. Die Seite behauptet damit, veraltet zu sein, obwohl das Datum noch
  nicht erreicht ist. **Kein Kriterium deckt den Fall ab** (AK-27 und AK-28 kennen nur
  die Vergangenheit), und er entsteht nur durch einen Eingabefehler. — **Michael
  entscheidet, ob das ein Kriterium wert ist.**

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Roadmap, Changelog oder beides? | Beides, als zwei Seiten mit gemeinsamer Bauart unter `/roadmap` und `/changelog` | Beide beantworten dieselbe Frage aus zwei Richtungen; zwei Adressen bleiben teilbar, ein gemeinsamer Rahmen hält den Pflegeaufwand bei einem |
| 2 | Woher kommen die Changelog-Texte? | Redaktionelle Kurzfassung je Release in einer eigenen Übersetzungsdomain, vier Sprachen; `CHANGELOG.md` bleibt die technische Fassung und wird verlinkt | Die Datei ist einsprachig deutsch und nennt Konstruktorargumente — für einen Gast unlesbar. Automatisches Rendern hätte beides in die Öffentlichkeit getragen |
| 3 | Welche Releases erscheinen? | Nur die, die ein Besucher merkt; die übrigen tragen einen ausdrücklichen Vermerk „still" | Ein Changelog, in dem die Hälfte der Einträge „Worker-Sperre im Deploy" heißt, wird nicht gelesen. Der Vermerk hält die Vollständigkeit prüfbar (AK-26) |
| 4 | Roadmap-Quelle | Kuratierte Vorhaben aus dem PRD **plus** Board-Ideen mit Status `Geplant`, live abgefragt | Eine Adresse beantwortet die Frage vollständig, und das Board bleibt der Weg, etwas hinzuzufügen. Eine Kopie im Code hätte eine zurückgezogene Idee überleben lassen |
| 5 | Termine? | Keine — weder Datum noch Quartal noch Fortschritt | Ein gerissener Termin kostet mehr Glaubwürdigkeit, als eine Zahl einbringt. Bei einer Person im Betrieb ist jede Zeitzusage eine Wette |
| 6 | Zurückgestelltes zeigen? | Ja, eigener Block mit Begründung | Produktprinzip 2 („Lücken werden gezeigt, nicht versteckt") gilt auch für die eigenen Entscheidungen — dieselbe Lehre wie die erzwungene Ablehnungsbegründung in Feature `06` |
| 7 | Deckel gegen Missbrauch | Zwischenspeicher plus harte Obergrenze von zehn Ideen, **kein** Rate Limit | Beide Seiten sind rein lesend; ein Limiter wäre die erste öffentliche Leseseite der Plattform, die Besucher aussperrt. Die Konvention aus `CLAUDE.md` wird an der Ursache erfüllt: Der Bestand wird nie ganz geladen |
| 8 | Maschinenlesbare Fassung | Vorerst keine | Ein Feed ist eine zweite Ausgabe mit eigenem Prüflauf und eigener Angriffsfläche. `/open.json` entstand für ein konkretes Fördergespräch; hier gibt es keinen belegten Bedarf |
| 9 | Namen im Changelog | Keine natürlichen Personen | Ein veröffentlichter Eintrag lässt sich nicht zurücknehmen; eine Zustimmung, die es heute nicht gibt, wäre die Voraussetzung |
| 10 | Verfassername auf der Roadmap | Nein — Titel, Zustimmungen und Verweis genügen | Der Anzeigename steht im Board, wo der Verfasser ihn hingestellt hat. Ihn auf eine Betreiberseite zu ziehen ist ein neuer Zusammenhang, dem niemand zugestimmt hat |
| 11 | Halb übersetzter Eintrag | Erreicht die Produktion nicht; der Katalogtest färbt rot | Ein deutscher Absatz auf der französischen Seite verstößt gegen die Sprachauszeichnung aus Feature `02` und lässt die Sprachfassungen auseinanderlaufen |
| 12 | Wachstum des Changelogs | Nach Jahren gruppiert, frühere Jahre zugeklappt, ohne JavaScript aufklappbar | Eine Adresse bleibt teilbar und auffindbar; das `<details>`-Muster ist auf `/open` erprobt und funktioniert ohne Skript |
| 13 | Wann entsteht ein Eintrag? | Als fünfter Punkt der Release-Checkliste, abgesichert durch einen Prüflauf | Die vier bestehenden Punkte wurden laut `CLAUDE.md` bereits zweimal vergessen (Badge, Fußzeile). Ein fünfter ohne Absicherung wäre der dritte Fall |
| 14 | Alter der Seite anzeigen? | Ja, mit hervorgehobenem Hinweis ab 60 Tagen | Übernommen von `/open`: Ein Fahrplan, dem man das Alter nicht ansieht, richtet mehr Schaden an als gar keiner |
| 15 | Spaltenzuordnung der sieben PRD-Vorhaben (VB-02) | Nach der Reihenfolge aus dem PRD; „In Arbeit" trägt, was gerade wirklich gebaut wird | Der Abschnitt „Vorschlag: Reihenfolge" ist bereits begründet und öffentlich nachlesbar. Eine leere erste Spalte wäre der schwächste erste Bildschirm, den die Seite haben kann |
| 16 | Altreleases (OF-01) | Neun merkbare plus eine Sammelzeile für die Aufbauphase | Alle 21 hätten 84 Texte gekostet, von denen die Hälfte einem Gast nichts sagt; nur die letzten drei hätten den Eindruck erweckt, die Plattform sei drei Wochen alt |
| 17 | Bewertungen auf der Roadmap (OF-02) | Ja, mit sachlicher Begründung, **ohne** Bezug auf das Werbeversprechen | Der Widerspruch zwischen Startseite und Produkt ist real, gehört aber in ein eigenes Vorhaben — als OF-08 festgehalten, statt ihn hier halb aufzulösen |
| 18 | Prüfrhythmus (OF-03) | An die Release-Checkliste gehängt | Ein eigener Termin braucht jemanden, der ihn erzwingt; die Checkliste wird ohnehin abgearbeitet |
| 19 | Liegenbleiber (OF-04) | Rückstufung nach zwölf Monaten | Eine Zusage, die niemand mehr einlöst, macht jede andere Zeile der Seite unglaubwürdig |
| 20 | Repo-Verweis (OF-05) | Erklärsatz daneben, als AK-51 | Wer auf `CHANGELOG.md` klickt, ohne Entwickler zu sein, landet sonst unvermittelt in Migrationsnamen |
| 21 | Auswahlregel ausweisen (OF-06) | Immer, als AK-52 | Der Satz erklärt nicht nur die Auswahl, er ist die Aufforderung zuzustimmen — und muss deshalb auch dann stehen, wenn gerade alle Ideen passen |
