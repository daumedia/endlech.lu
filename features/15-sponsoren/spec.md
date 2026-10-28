# 15 · Sponsoren — Spezifikation

Status: `planned` · Stand: 2026-10-28 · **Überarbeitet 2026-10-28** (Kurzbeschreibung und Profillinks, AK-49 bis AK-55)

> **Überarbeitung nach der Abnahme, vor dem Deploy** (Betreiber, 2026-10-28): Die Sponsorenseite bekommt eine
> **Kurzbeschreibung in fünf Sprachen** und **Links auf die Profile bei Instagram, Facebook und LinkedIn**. Neu sind
> AK-49 bis AK-55, EC-13 und EC-14, Decision Log #29 bis #33. VB-01 ist ergänzt. Kein bestehendes Kriterium hat
> sich geändert. Der Status ging von `approved` auf `planned` zurück; der erste QA-Durchlauf (`qa-report.md`) gilt
> für AK-01 bis AK-48 und bleibt stehen.

## Zweck

Unternehmen und Vereine können Endlech.lu heute „mitfinanzieren“, aber niemand sieht es: Es gibt
keinen Ort, an dem ein Sponsor genannt wird, und die Angebotsseite zählt vier Formate auf, von denen
keines einen Preis oder eine Gegenleistung hat. Nach diesem Feature gibt es **ein** Sponsoring-Paket
(monatlich oder jährlich, Preis auf Anfrage). Jeder aktive Sponsor steht mit seinem Logo im Footer
jeder Seite, und ein Klick darauf führt auf eine eigene Seite: wer er ist, seit wann er sponsert,
ein Satz in eigenen Worten, wofür das Geld verwendet wird und was er dafür **nicht** bekommt.

Die Nennung bleibt eine Nennung. Die öffentliche Zusage *„keine bessere Bewertung, keine bevorzugte
Platzierung, kein Einfluss auf Inhalte“* gilt ohne Ausnahme, und die Startseite verspricht weiterhin
„Keine Werbung, keine bezahlten Einträge“.

## Vorbedingungen, die keine Funktion sind

Sie blockieren **nicht** die Auslieferung — das Feature geht ohne Sponsor live (AK-07, AK-13) —,
aber den **ersten Eintrag**.

| # | Was | Warum es blockiert |
|---|---|---|
| **VB-01** | **Eine Vereinbarung je Sponsor**, die festhält: Recht zur Nutzung des Logos, Freigabe des Zitats im Wortlaut, Beginn und gegebenenfalls Ende, Entfernung auf Wunsch binnen 30 Tagen (AK-43), Bestätigung, dass kein Ausschlussgrund vorliegt, und die Zustimmung, dass Name, Logo und Zitat im öffentlichen Quelltext stehen und in dessen Verlauf auch nach einer Entfernung auffindbar bleiben (OF-01). **Seit der Überarbeitung zusätzlich:** die Kurzbeschreibung in allen fünf Sprachen, jede Fassung vom Sponsor freigegeben, ohne Namen von Personen, ohne Angebote, Preise oder Rabatte (AK-49, AK-51), und die Adressen der Profile, die verlinkt werden sollen (AK-52). | Ohne Nutzungsrecht ist jedes Logo im Footer eine Markenverletzung auf jeder Seite. Die Ausschlussregel prüft kein Prüflauf — nur der Betreiber vor der Aufnahme. |
| **VB-02** | **Das Logo liegt in einer Fassung vor, die auf dunklem Grund lesbar ist**, oder es wird auf eine helle Fläche gesetzt (AK-29). | Der Footer ist dunkel. Ein dunkles Logo darauf ist unsichtbar — die Gegenleistung fiele aus, ohne dass es jemand merkt. |

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B15 · Organisations-Wartelisten | `deployed` | Angebotsseite `/organisationen/unternehmen` und ihr Formular werden umgebaut (AK-33 bis AK-38) |
| B22 · Wartelisten-Verwaltung | `deployed` | zeigt Einträge mit den alten Formaten weiter an (AK-39) |
| B16 · Transparenzseite `/open` | `deployed` | Block Finanzen bekommt den Verweis auf die Sponsoren; die Quartalssperre darf nicht umgangen werden (AK-14, AK-30) |
| 05 · Presse-Kit | `deployed` | Faktenblatt bekommt die Zeile „Sponsoren“ (AK-32) |
| 10 · Sitemap und robots.txt | `deployed` | neue Seiten müssen eingeordnet werden, mit canonical und Sprachverweisen (AK-41) |
| 11 · Nutzungsmessung | `deployed` | Aufrufe der Sponsorenseiten sind die einzige Zahl, die ein Sponsor bekommt (AK-47, AK-48) |
| 02 · Barrierefreiheit der Plattform | `deployed` | gilt für Footer-Leiste und neue Seiten (AK-04) |
| B24 · Mehrsprachigkeit | `approved` | fünf Sprachfassungen; das Zitat bleibt bewusst einsprachig (AK-18) |

## User Stories

- **US-01** · Als Besucherin möchte ich sehen, wer Endlech.lu finanziert, damit ich selbst einschätzen
  kann, ob jemand Einfluss auf die Bewertungen nehmen könnte.
- **US-02** · Als Verantwortlicher eines Unternehmens möchte ich auf einer Seite lesen, was ein
  Sponsoring umfasst und was nicht, damit ich es intern vorschlagen kann, ohne vorher nachzufragen.
- **US-03** · Als Sponsor möchte ich auf jeder Seite genannt werden und einen eigenen Satz sagen
  können, damit unser Engagement nach außen sichtbar ist.
- **US-04** · Als Betreiber möchte ich einen neuen Sponsor in wenigen Minuten eintragen und ein
  vereinbartes Ende im Voraus festlegen, damit ich an keinem Stichtag etwas ausrollen muss.
- **US-05** · Als Journalistin möchte ich im Faktenblatt sehen, ob und wie viele Sponsoren es gibt,
  damit ich die Finanzierung richtig beschreibe.
- **US-06** · Als ehemaliger Sponsor möchte ich verlangen können, nicht mehr genannt zu werden.

## Nicht im Scope

- **Online-Buchung und Zahlung** — Vertrag und Rechnung laufen von Hand, der Eingang wie bisher als
  Finanzposten der Kategorie Sponsoring (Decision Log #6). Ein Zahlungsanbieter wäre ein eigenes
  Feature mit eigenem Auftragsverarbeiter.
- **Eine Verwaltungsoberfläche für Sponsoren** — die Einträge stehen im Quelltext (Decision Log #5).
  Es gibt keine Upload-Strecke und kein Formular.
- **Stufen, Größenklassen, hervorgehobene Sponsoren** (Decision Log #3).
- **Beträge und Preise auf irgendeiner öffentlichen Seite** (Decision Log #4, #7).
- **Projektbezogene Sponsoring-Formate** (Inclusion-Boxen finanzieren, eine Gemeinde mitfinanzieren,
  Übersetzungen, Workshops) und **„Mitarbeitende als Testpersonen“** — entfallen ersatzlos
  (Decision Log #2, #8). Die **Inclusion-Box als Leistung für Restaurants** (B14, `/partner`) bleibt
  unberührt.
- **Die Zusammenarbeit ohne Geld** — bleibt, wie sie ist (AK-35).
- **Sponsoren in der iOS-API, in E-Mails, in `/open.json` und im offenen Datensatz.** Ein Nachtrag
  an dieser Spec, falls gewünscht.
- **Klickzählung auf den Link zur Website des Sponsors und automatische Berichte** (Decision Log #17).
  Gilt seit der Überarbeitung ebenso für die Profillinks (AK-55).
- **Weitere Angaben zum Sponsor:** Sitz oder Gemeinde und öffentlicher Kontakt (E-Mail, Telefon) wurden in der
  Überarbeitung abgewählt (Decision Log #29). Weitere Plattformen (TikTok, YouTube, X) sind ein Nachtrag.
- **Eingebettete Beiträge, Feeds oder Symbole der Plattformen** — sie luden Inhalte von deren Servern (AK-42, AK-55).
- **Die Gemeinde-Erhebung** (B15, Typ Gemeinde) — unverändert.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar. „Aktiv“ heißt: Heute liegt in einem vereinbarten
Zeitraum des Sponsors, gerechnet nach Luxemburger Zeit. „Ehemalig“ heißt: Mindestens ein Zeitraum
liegt vollständig in der Vergangenheit, und keiner läuft gerade.

### Footer-Leiste auf jeder Seite

- **AK-01** · Angenommen, mindestens ein Sponsor ist aktiv, wenn eine beliebige öffentliche Seite in
  einer der fünf Sprachen aufgerufen wird — darunter Startseite, Restaurantliste, eine
  Restaurant-Detailseite, `/open` und das Impressum —, dann steht im Footer ein eigener Bereich mit
  der Überschrift „Sponsoren“ (in der Seitensprache) und darin das Logo jedes aktiven Sponsors,
  höchstens aber zwölf (AK-06).
- **AK-02** · Angenommen, mehrere Sponsoren sind aktiv, wenn die Logos im Footer verglichen werden,
  dann nehmen alle eine gleich große Fläche ein, keins ist verzerrt, und die Reihenfolge folgt dem
  Beginn des **laufenden** Zeitraums, der älteste zuerst; bei gleichem Beginn alphabetisch nach Name.
- **AK-03** · Angenommen, ein Logo im Footer wird angeklickt oder per Tastatur aktiviert, wenn die
  nächste Seite lädt, dann ist es die Sponsorenseite dieses Sponsors auf endlech.lu in derselben
  Sprache — nicht die Website des Sponsors und nicht in einem neuen Tab.
- **AK-04** · Angenommen, ein Screenreader liest den Footer, wenn er ein Logo erreicht, dann sagt er
  den Namen des Sponsors an (nicht „Logo“, nicht den Dateinamen). Jedes Logo ist per Tab erreichbar,
  hat einen sichtbaren Fokusrahmen und unter 768 px Breite eine Tippfläche von mindestens 44 × 44 px.
- **AK-05** · Angenommen, es gibt mindestens einen aktiven oder ehemaligen Sponsor, wenn der Footer
  betrachtet wird, dann stehen im Bereich „Sponsoren“ die Links „Alle Sponsoren“ (auf `/sponsoren`)
  und „Sponsor werden →“ (auf `/organisationen/unternehmen`).
- **AK-06** · Angenommen, 13 Sponsoren sind aktiv, wenn der Footer betrachtet wird, dann zeigt er
  genau zwölf Logos — die zwölf mit dem ältesten laufenden Beginn —, und der dreizehnte steht auf
  `/sponsoren`, nicht im Footer.
- **AK-07** · Angenommen, kein Sponsor ist aktiv und es gab nie einen (der Stand bei Auslieferung),
  wenn eine Seite aufgerufen wird, dann zeigt der Bereich nur den Link „Sponsor werden →“ — keine
  leere Fläche, kein Platzhalterlogo, kein Text wie „Hier könnte Ihr Logo stehen“.
- **AK-08** · Angenommen, ein Sponsor wird eingetragen oder entfernt, wenn Restaurantliste (jede
  Sortierung, jeder Filter), Restaurant-Detailseiten, Startseite und der offene Datensatz vorher und
  nachher verglichen werden, dann sind sie bis auf die Footer-Leiste identisch: dieselben Einträge,
  dieselbe Reihenfolge, dieselben Werte.
- **AK-09** · Angenommen, eine Restaurant-Detailseite wird aufgerufen, wenn alles außer dem Footer
  betrachtet wird, dann kommt dort weder ein Sponsorlogo noch ein Sponsorname vor.
- **AK-10** · Angenommen, zwölf Sponsoren sind aktiv, wenn eine Seite auf einem Telefon geöffnet und
  nicht gescrollt wird, dann ist noch keine Logodatei geladen; die Logos im Footer wiegen zusammen
  höchstens 150 KB, keins mehr als 25 KB.

### Übersicht `/sponsoren`

- **AK-11** · Angenommen, `/{locale}/sponsoren` wird in einer der fünf Sprachen aufgerufen, wenn die
  Seite lädt, dann steht dort: was Sponsoring ist (ein Paket, monatlich oder jährlich, Preis auf
  Anfrage); was ein Sponsor bekommt (Nennung im Footer jeder Seite, eine eigene Seite); was er
  **nicht** bekommt (keine bessere Bewertung, keine Platzierung, keinen Einfluss auf Einträge, keinen
  Zugriff auf einzelne Bewertungen); die Ausschlussregel (keine Gastronomiebetriebe, die selbst auf
  Endlech.lu bewertet werden); ein Satz, wofür das Geld verwendet wird, mit Link auf den Block
  Finanzen in `/open`; und „Sponsor werden →“.
- **AK-12** · Angenommen, Sponsoren sind aktiv, wenn die Übersicht betrachtet wird, dann listet sie
  **alle** aktiven — auch über zwölf — mit Logo, Name und „Sponsor seit <Monat Jahr>“, in derselben
  Reihenfolge wie der Footer, jeder verlinkt auf seine Sponsorenseite.
- **AK-13** · Angenommen, es gibt weder aktive noch ehemalige Sponsoren, wenn die Übersicht aufgerufen
  wird, dann steht dort „Endlech.lu hat derzeit keine Sponsoren.“ (in der Seitensprache), darunter
  dieselben Inhalte wie in AK-11 — kein leerer Listenrahmen.
- **AK-14** · Angenommen, Footer, Übersicht, jede Sponsorenseite, `/organisationen/unternehmen` und
  das Faktenblatt werden in allen fünf Sprachen durchsucht, wenn man nach einem Eurobetrag, einem
  Preis oder nach „monatlich“/„jährlich“ **bei einem einzelnen Sponsor** sucht, dann findet man
  nichts davon. (Die Laufzeitart eines Sponsors verriete seinen Betrag.)
- **AK-15** · Angenommen, es gibt ehemalige Sponsoren, wenn die Übersicht betrachtet wird, dann stehen
  sie in einem eigenen Abschnitt „Bisher unterstützt von“ mit Name und Zeitraum, das jüngste Ende
  zuerst, jeder verlinkt auf seine Seite; im Abschnitt der aktiven fehlen sie.

### Sponsorenseite `/sponsoren/<name>`

- **AK-16** · Angenommen, die Seite eines aktiven Sponsors wird aufgerufen, wenn sie lädt, dann zeigt
  sie: Logo, Name, alle Zeiträume („Sponsor seit März 2027“ bzw. „Sponsor 2026–2027 und seit März
  2029“), das Zitat des Sponsors, falls vereinbart, den Satz zur Verwendung des Geldes mit Link auf
  `/open`, einen Block „Was <Name> dafür nicht bekommt“ mit denselben Punkten wie AK-11, den Link
  „Website von <Name>“, falls vereinbart, „Sponsor werden →“ und einen Link zurück zur Übersicht.
- **AK-17** · Angenommen, der Link „Website von <Name>“ wird untersucht, wenn man das Linkziel und
  seine Art liest, dann führt er genau auf die vereinbarte Adresse, ohne von Endlech.lu angehängte
  Zusätze, und ist als bezahlter Link gekennzeichnet (`rel` enthält `sponsored`) und gegen Zugriff
  der Zielseite auf das Fenster geschützt (`rel` enthält `noopener`).
- **AK-18** · Angenommen, das Zitat eines Sponsors ist französisch, wenn seine Seite auf `/de/`
  aufgerufen wird, dann steht das Zitat unverändert auf Französisch, ein Screenreader liest es mit
  französischer Aussprache, und alles andere auf der Seite ist Deutsch. Dasselbe gilt in allen fünf
  Sprachfassungen.
- **AK-19** · Angenommen, ein Zitat steht auf der Seite, wenn man darunter liest, dann ist es als Zitat
  erkennbar und mit „— <Firmenname>“ gezeichnet; es ist höchstens 300 Zeichen lang.
- **AK-20** · Angenommen, für einen Sponsor ist kein Zitat oder keine Website vereinbart, wenn seine
  Seite aufgerufen wird, dann fehlt der jeweilige Teil ganz — kein leerer Zitatkasten, kein Link ins
  Leere.
- **AK-21** · Angenommen, ein Sponsor ist ehemalig, wenn seine Seite aufgerufen wird, dann ist sie
  erreichbar, trägt „Ehemaliger Sponsor“ mit den Zeiträumen und zeigt **keinen** Link mehr auf seine
  Website; im Footer und unter den aktiven Sponsoren fehlt er.
- **AK-22** · Angenommen, eine Adresse `/sponsoren/<unbekannt>` wird aufgerufen, wenn die Antwort kommt,
  dann ist es die gewöhnliche 404-Seite — nie die Seite eines anderen Sponsors.

### Mehr über den Sponsor (Überarbeitung 2026-10-28)

- **AK-49** · Angenommen, für einen Sponsor ist eine Kurzbeschreibung vereinbart, wenn seine Seite in einer der
  fünf Sprachen aufgerufen wird, dann steht unter der Überschrift „Über <Name>“ (in der Seitensprache) die
  Beschreibung **in dieser Sprache** — auf `/fr/` die französische, auf `/lb/` die luxemburgische —, höchstens
  500 Zeichen lang.
- **AK-50** · Angenommen, für einen Sponsor ist keine Kurzbeschreibung vereinbart, wenn seine Seite aufgerufen
  wird, dann fehlt der Abschnitt „Über <Name>“ ganz — kein leerer Kasten, keine Überschrift ohne Text.
- **AK-51** · Angenommen, bei einem Eintrag ist eine Kurzbeschreibung vereinbart, aber eine der fünf Sprachen
  fehlt, ist leer, ist länger als 500 Zeichen oder nennt einen Eurobetrag, wenn die Prüfläufe laufen, dann schlägt
  einer fehl und nennt Sponsor, Sprache und Angabe.
- **AK-52** · Angenommen, für einen aktiven Sponsor sind Profile bei Instagram, Facebook oder LinkedIn vereinbart,
  wenn seine Seite aufgerufen wird, dann steht je Profil ein Link, der den Namen der Plattform trägt und für einen
  Screenreader als „<Plattform> von <Name>“ angesagt wird, genau auf die vereinbarte Adresse führt und — wie der
  Website-Link — `rel` mit `sponsored` und `noopener` trägt. Ohne vereinbarte Profile fehlt die Zeile ganz.
- **AK-53** · Angenommen, ein Profil wird mit einer Adresse eingetragen, die nicht mit `https://` beginnt oder nicht
  zur genannten Plattform gehört (etwa ein „Instagram“-Profil auf einem anderen Server), wenn die Prüfläufe laufen,
  dann schlägt einer fehl und nennt Sponsor und Plattform.
- **AK-54** · Angenommen, ein Sponsor ist ehemalig, wenn seine Seite aufgerufen wird, dann steht seine
  Kurzbeschreibung weiter da, die Profillinks fehlen aber — wie der Website-Link (AK-21).
- **AK-55** · Angenommen, eine Sponsorenseite mit Profillinks wird aufgerufen, wenn man die geladenen Dateien und
  die Netzwerkaufrufe ansieht, dann kommt nichts von Instagram, Facebook oder LinkedIn (kein eingebetteter
  Beitrag, kein Symbol von dort), und beim Klick auf einen Profillink geht kein Zählaufruf an die
  Nutzungsmessung hinaus.

### Zeiträume und Datum

- **AK-23** · Angenommen, ein Sponsor ist eingetragen, dessen erster Zeitraum erst morgen beginnt, wenn
  heute Footer, Übersicht, Faktenblatt, `/organisationen/unternehmen`, die Sitemap und seine Adresse
  aufgerufen werden, dann erscheint er nirgends und seine Adresse antwortet mit 404. Ab dem Beginn
  erscheint er spätestens eine Stunde nach Tagesbeginn überall, **ohne erneutes Ausrollen**.
- **AK-24** · Angenommen, der laufende Zeitraum eines Sponsors endet am 31. März, wenn am 1. April
  spätestens um 01:00 Uhr Luxemburger Zeit eine Seite aufgerufen wird, dann steht er nicht mehr im
  Footer und nicht unter den aktiven Sponsoren, sondern unter „Bisher unterstützt von“ — ohne dass
  seit dem Eintragen des Enddatums ausgerollt wurde. Am 31. März um 23:59 Uhr steht er noch im Footer.
- **AK-25** · Angenommen, ein Sponsor hatte eine Pause und sponsert wieder, wenn Footer und Übersicht
  betrachtet werden, dann gilt für die Reihenfolge der Beginn des **laufenden** Zeitraums, und seine
  Seite nennt alle Zeiträume in zeitlicher Reihenfolge.

### Eintragen durch den Betreiber

- **AK-26** · Angenommen, die Vereinbarung liegt vor (VB-01), wenn der Betreiber den Sponsor einträgt —
  Name, Logodateien, Beginn, gegebenenfalls Ende, Zitat mit Sprache und Website —, dann ist der
  Sponsor nach höchstens 10 Minuten einschließlich Ausrollen in Footer, Übersicht, Faktenblatt,
  `/organisationen/unternehmen` und mit eigener Seite in allen fünf Sprachen live, ohne dass außer
  dem Eintrag und den Logodateien eine weitere Stelle angefasst werden musste — keine
  Übersetzungsdatei, keine Sitemap, keine Liste in einem Prüflauf.
- **AK-27** · Angenommen, bei einem Eintrag fehlt eine Pflichtangabe (Name, Logo, Beginn) oder sie ist
  widersprüchlich (Ende vor Beginn, zwei sich überlappende Zeiträume, zwei Sponsoren mit derselben
  Adresse, Zitat über 300 Zeichen oder ohne Sprachangabe, Logodatei nicht vorhanden), wenn die
  Prüfläufe laufen, dann schlägt einer fehl und nennt Sponsor und Angabe.
- **AK-28** · Angenommen, eine Logodatei eines Sponsors wird direkt im Browser aufgerufen, wenn sie
  lädt, dann führt sie kein Skript aus und lädt nichts von einem fremden Server.
- **AK-29** · Angenommen, die Footer-Leiste wird betrachtet, wenn man jedes Logo ansieht, dann ist
  keins dunkel auf dunklem Grund: Jedes liegt in einer hellen Fassung vor oder steht auf einer hellen
  Fläche.

### Weitere Orte

- **AK-30** · Angenommen, `/open` wird in einer der fünf Sprachen aufgerufen, wenn der Block Finanzen
  betrachtet wird, dann steht dort ein Link „Wer uns finanziert →“ auf `/sponsoren` — auch während
  der Quartalssperre für Einnahmen —, und kein Betrag ist einem Sponsor zugeordnet.
- **AK-31** · Angenommen, mindestens ein Sponsor ist aktiv, wenn `/organisationen/unternehmen`
  aufgerufen wird, dann stehen über dem Formular dieselben Logos wie im Footer (gleiche Auswahl,
  gleiche Reihenfolge, verlinkt auf die Sponsorenseiten); ohne aktiven Sponsor fehlt die Reihe ganz.
- **AK-32** · Angenommen, das Faktenblatt unter `/presse` wird betrachtet, wenn man die Zeile
  „Sponsoren“ liest, dann nennt sie die Zahl der aktiven Sponsoren und verlinkt auf `/sponsoren`; ohne
  aktiven Sponsor steht dort „Derzeit keine“ (in der Seitensprache), mit demselben Link.

### Angebotsseite `/organisationen/unternehmen`

- **AK-33** · Angenommen, `/{locale}/organisationen/unternehmen` wird in einer der fünf Sprachen
  aufgerufen, wenn der Teil zum Sponsoring gelesen wird, dann beschreibt er genau ein Paket: monatlich
  oder jährlich, Preis auf Anfrage, Gegenleistung Nennung im Footer jeder Seite und eigene
  Sponsorenseite, dazu ein Link auf `/sponsoren`.
- **AK-34** · Angenommen, alle öffentlichen Seiten in allen fünf Sprachen werden durchsucht, wenn man
  nach den bisherigen Sponsoring-Formaten sucht („Inclusion-Boxen finanzieren“, „Mitarbeitende als
  Testpersonen“, „Eine Gemeinde mitfinanzieren“, „Übersetzungen und Workshops“ bzw. deren
  Übersetzungen), dann findet man sie nirgends als Angebot an Unternehmen; auch die Übersicht
  `/organisationen` bietet keine Unterstützung „mit Ihrem Team“ mehr an. Der Satz zur Gegenleistung
  lautet im Deutschen „wird als Sponsor genannt“, nicht mehr „als Unterstützer“.
- **AK-35** · Angenommen, die Seite wird gelesen, wenn man den Teil „Auch ohne Geld“ betrachtet, dann
  ist er unverändert: aggregierte Daten, gemeinsame Kommunikation, Weiterempfehlung, „Etwas anderes“.
- **AK-36** · Angenommen, die Seite wird gelesen, wenn man die Ausschlussregel sucht, dann steht sie
  unverändert da: keine Gastronomieketten und Betriebe, die selbst auf Endlech.lu bewertet werden.
- **AK-37** · Angenommen, ein Unternehmen füllt das Formular aus, wenn es die Frage zum Sponsoring
  beantwortet, dann gibt es genau zwei Antworten, „monatlich“ und „jährlich“, von denen höchstens eine
  wählbar ist, und die Frage darf leer bleiben (dann geht es nur um Zusammenarbeit ohne Geld). Das
  Formular bleibt ohne JavaScript bedienbar.
- **AK-38** · Angenommen, eine Anfrage mit untergeschobenem anderem Wert (etwa einem der alten Formate)
  wird abgeschickt, wenn der Server antwortet, dann mit 422, und es wird nichts gespeichert und keine
  Mail verschickt. Bei einer gültigen Anfrage nennen die interne Meldung ans Team und die
  Verwaltungsansicht die gewählte Laufzeit.
- **AK-39** · Angenommen, ein Wartelisten-Eintrag trägt noch alte Formate, wenn er in der Verwaltung
  geöffnet wird, dann sind sie lesbar, als „frühere Angabe“ gekennzeichnet, und es entsteht keine
  Fehlerseite; nichts davon wird gelöscht.

### Auffindbarkeit

- **AK-40** · Angenommen, `/sponsoren` oder eine Sponsorenseite wird aufgerufen, wenn man den Kopf der
  Seite liest, dann trägt sie wie jede öffentliche Seite eine maßgebliche Adresse und Verweise auf
  alle fünf Sprachfassungen.
- **AK-41** · Angenommen, die Sitemap wird abgerufen, wenn man ihre Einträge liest, dann stehen dort
  `/sponsoren` und die Seiten aller aktiven und ehemaligen Sponsoren in allen fünf Sprachen; Sponsoren
  mit künftigem Beginn (AK-23) und entfernte (AK-43) fehlen. Ein neuer Sponsor erscheint dort
  innerhalb der Frist aus Feature 10 (60 Minuten).

### Datenschutz und Missbrauchsschutz

- **AK-42** · Angenommen, Footer, Übersicht oder eine Sponsorenseite werden aufgerufen, wenn man die
  geladenen Dateien ansieht, dann kommt alles von endlech.lu — kein Logo, keine Schrift, kein Skript
  von einer Adresse des Sponsors. Erst ein Klick auf „Website von <Name>“ verlässt endlech.lu, und die
  Zielseite erfährt dabei höchstens, dass der Besucher von endlech.lu kommt, nicht von welcher Seite.
- **AK-43** · Angenommen, ein aktiver oder ehemaliger Sponsor verlangt, nicht mehr genannt zu werden,
  wenn höchstens 30 Tage vergangen sind, dann kommen Name, Logo und Zitat auf keiner Seite mehr vor
  (Footer, Übersicht, Faktenblatt, Unternehmensseite, Sitemap), seine Logodateien antworten mit 404,
  und seine frühere Adresse leitet dauerhaft (301) auf `/sponsoren`. *(Ablauf beim Betreiber — der
  Nachweis ist der Zustand der Seite am 30. Tag.)* *(Klargestellt 2026-10-28 nach `OF-01`: Das
  Kriterium gilt für die Website. Im Verlauf des öffentlichen Quelltexts bleibt der Eintrag stehen; der
  Sponsor hat dem in der Vereinbarung zugestimmt, VB-01. Prüfbarer Inhalt unverändert.)*
- **AK-44** · Angenommen, eine Sponsorenseite, die Übersicht oder der Footer werden gelesen, wenn man
  nach Personen sucht, dann steht dort kein Name einer natürlichen Person als Urheber des Zitats und
  keine Kontaktperson; gezeichnet ist mit dem Firmennamen.
- **AK-45** · Angenommen, die Verwaltung der Organisations-Warteliste wird geöffnet, wenn ein Sponsor
  eingetragen, beendet oder entfernt wurde, dann hat sich an den Wartelisten-Einträgen nichts geändert;
  umgekehrt entfernt der Abmeldelink einer Warteliste keinen Sponsor (EC-10).

### Messung

- **AK-46** · Angenommen, ein Besucher klickt auf „Website von <Name>“, wenn man die Netzwerkaufrufe
  ansieht, dann geht dabei kein Zählaufruf an die Nutzungsmessung hinaus.
- **AK-47** · Angenommen, eine Sponsorenseite wird aufgerufen, wenn man die Nutzungsmessung danach
  befragt, dann ist der Aufruf dort wie bei jeder anderen öffentlichen Seite gezählt — mit einer
  Adresse, die nur diesem Sponsor gehört und keine Personendaten trägt.
- **AK-48** · Angenommen, ein Sponsor fragt nach Zahlen, wenn der Betreiber die Nutzungsmessung für
  einen Kalendermonat öffnet, dann kann er die Aufrufe der Seite dieses Sponsors über alle fünf
  Sprachen ablesen. *(Plattformeinstellung — Nachweis als Abfrage in Umami, nicht als Code.)*

### Katalogfragen, die nicht zutreffen

Aus `~/.claude/sdd/sicherheit.md`, einzeln durchgegangen:

| Abschnitt | Frage | Trifft nicht zu, weil … |
|---|---|---|
| 1 | Besondere Kategorien | Sponsoren sind Unternehmen und Vereine; kein Merkmal einer Person wird erfasst. Der Firmenname eines Einzelunternehmens kann ein Personenname sein — er erscheint mit Einwilligung aus der Vereinbarung (VB-01) und verschwindet auf Wunsch (AK-43). Siehe OF-03. |
| 1 | Personendaten in Logs | Keine neue Eingabe, kein neuer Fehlerweg mit Inhalt. Das Formular (AK-37) protokolliert wie bisher (B15). |
| 2 | Externe Dienste | Kein Dienst bekommt neue Daten. Umami zählt die neuen Seiten wie alle anderen (Feature 11), Brevo bekommt aus der Warteliste dieselben Felder wie bisher — die Laufzeit geht **nicht** mit. Die Profillinks der Überarbeitung verlinken nur auf die Plattformen und laden nichts von dort (AK-55); was die Plattform nach dem Klick erfasst, liegt außerhalb von Endlech.lu. |
| 3 | Zugriff je Datensatz | Alles ist öffentlich; ändern kann nur, wer in das Repository schreiben darf. Es gibt keine Rolle und keinen Verwaltungsweg. Fremde oder unbekannte Adresse: AK-22. |
| 4 | Rate Limit | Die neuen Seiten lösen keine Mail aus und prüfen kein Geheimnis. Das Formular behält seinen Deckel aus B15. ⚠ **Sollte der Entwurf die Sponsoren bei jedem Seitenaufruf aus einem wachsenden Bestand laden, gilt die Projektkonvention: Deckel im selben Commit.** |
| 4 | Kosten je Aufruf | Keiner — kein bezahlter Dienst wird gerufen. Was jede Seite mehr wiegt, begrenzt AK-10. |
| 4 | Uploads | Keine. Die Logodateien legt der Betreiber ab; was sie nicht dürfen, steht in AK-28. |
| 5 | Kontolöschung, Export | Sponsoren haben kein Konto, und kein Konto hängt an einem Sponsor. Entfernung auf Wunsch: AK-43. |
| 6 | Geheimnisse | Das Feature braucht keinen Schlüssel. |

## Edge Cases

- **EC-01** · Beginn und Ende am selben Tag → ein Tag Sponsoring, gültig; Ende vor Beginn → AK-27.
- **EC-02** · Der Tageswechsel zählt nach Luxemburger Zeit, auch in der Nacht der
  Sommerzeitumstellung (AK-23, AK-24).
- **EC-03** · Zwei Sponsoren mit demselben Beginn → alphabetisch nach Name, gleich in allen Sprachen.
- **EC-04** · Sehr breites (8 : 1) oder fast quadratisches Logo → passt in dieselbe Fläche wie alle
  anderen, unverzerrt, ohne aus der Reihe zu ragen (AK-02).
- **EC-05** · Name mit Akzenten, Umlauten oder „&“ (etwa „Ëmil & Fils S.à r.l.“) → überall richtig
  dargestellt; die Adresse der Sponsorenseite besteht nur aus Kleinbuchstaben, Ziffern und
  Bindestrichen.
- **EC-06** · Ein Sponsor tauscht sein Logo → ein Besucher, der die Seite schon kannte, sieht beim
  ersten Aufruf nach dem Ausrollen das neue Logo, nicht das zwischengespeicherte alte.
- **EC-07** · Ein 13. Sponsor kommt hinzu → er steht auf `/sponsoren`, nicht im Footer und nicht in
  der Reihe auf `/organisationen/unternehmen` (AK-06, AK-31), bis ein älterer endet.
- **EC-08** · Ein Sponsor endet und verlangt am selben Tag die Entfernung → die Entfernung gewinnt, er
  erscheint auch nicht unter „Bisher unterstützt von“.
- **EC-09** · JavaScript ist abgeschaltet → Footer-Leiste, Übersicht und Sponsorenseiten sind
  vollständig sichtbar und bedienbar.
- **EC-10** · Der Sponsor steht zugleich in der Organisations-Warteliste und widerruft dort seine
  Einwilligung → der Wartelisten-Eintrag folgt B15, der Sponsor bleibt genannt (andere Grundlage:
  die Vereinbarung). Will er auch dort weg, gilt AK-43.
- **EC-11** · Die Website eines ehemaligen Sponsors existiert nicht mehr oder gehört inzwischen
  jemand anderem → irrelevant, weil ehemalige Sponsorenseiten keinen Link mehr tragen (AK-21).
- **EC-12** · Ein Sponsor mit Monatspaket kündigt am 10. Mai → Enddatum 31. Mai; er steht bis
  einschließlich 31. Mai im Footer und ist ab dem 1. Juni ehemalig (AK-24). Ein Jahrespaket ohne
  Kündigung läuft weiter, ohne dass etwas eingetragen wird; mit Kündigung ist das Enddatum der
  letzte Tag des laufenden Vertragsjahres. *(Ergänzt 2026-10-28 nach `OF-02`.)*
- **EC-13** · Eine Kurzbeschreibung mit Sonderzeichen, Anführungszeichen oder einem Zeilenumbruch → steht als Text
  da, nichts davon wird als Auszeichnung ausgeführt; ein Zeilenumbruch bleibt ein Absatz. *(Überarbeitung)*
- **EC-14** · Ein Sponsor hat Profillinks, aber keine Website → die Profillinks stehen trotzdem da; hat er weder
  noch, fehlt der ganze Block mit Links. *(Überarbeitung)*

## Offene Fragen

- **OF-01** · **Der Quelltext ist öffentlich.** Ein Sponsor, der nach AK-43 entfernt wurde, bleibt im
  Verlauf des öffentlichen Repositorys auffindbar (Name, Logo, Zitat). Genügt ein Hinweis darauf in
  der Vereinbarung (VB-01), oder müssen Sponsorendaten außerhalb des öffentlichen Repositorys
  liegen? Letzteres änderte die Entscheidung „Pflege im Code“ (Decision Log #5). — Betreiber, **vor
  dem ersten Sponsoreintrag**; beeinflusst `/sdd-architektur 15`.
  ✔ **Beantwortet 2026-10-28 (Betreiber): Ein Hinweis in der Vereinbarung reicht.** Der Sponsor
  stimmt zu, dass Name, Logo und Zitat im öffentlichen Quelltext stehen und dort im Verlauf
  bleiben; AK-43 gilt für die Website (Vermerk dort), VB-01 nennt die Zustimmung ausdrücklich. Die
  Pflege im Quelltext (Decision Log #5) bleibt, der Entwurf kann darauf aufbauen.
- **OF-02** · Mindestlaufzeit und Kündigungsfrist des Monatspakets. Kein Kriterium hängt daran, aber
  das Enddatum (AK-24) muss bei einer Kündigung bekannt sein, um es im Voraus einzutragen. —
  Betreiber, vor dem ersten Vertrag.
  ✔ **Beantwortet 2026-10-28 (Betreiber): Monatspaket jederzeit zum Monatsende kündbar; Jahrespaket
  läuft sein Vertragsjahr und verlängert sich ohne Kündigung um ein weiteres Jahr** (Fristen als
  Vorschlag vorgelegt und angenommen). Enddatum ist der letzte bezahlte Tag; neuer Randfall EC-12.
  Die Fristen gehören in die Vereinbarung (VB-01), auf der Website stehen sie nicht (AK-14).
- **OF-03** · Gehört die Veröffentlichung der Sponsorennamen als eigene Verarbeitung in
  `docs/datenschutz.md` (Firmenname eines Einzelunternehmens = Personenname, Rechtsgrundlage
  Vertrag)? Empfehlung: ja, ein kurzer Eintrag. — Betreiber, spätestens bei `/sdd-betrieb` vor dem
  Deploy.
  ✔ **Beantwortet 2026-10-28 (Betreiber): Ja, ein kurzer Eintrag.** Zweck, Daten (Name, Logo, Zitat,
  Zeiträume), Rechtsgrundlage Vertrag, Entfernung auf Wunsch binnen 30 Tagen (AK-43), Hinweis auf
  den öffentlichen Quelltext-Verlauf (OF-01). Wird eine Aufgabe im Aufgabenplan; `/sdd-betrieb`
  prüft ihn vor dem Deploy.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Begriff | **„Sponsoren“**, nicht „Unterstützer“ oder „Förderer“ (2026-10-28) | gebräuchlich und ehrlich; der Satz auf der Angebotsseite zieht mit (AK-34) |
| 2 | Modell | **ein festes Paket, monatlich oder jährlich**, Preis vom Betreiber festgelegt; ersetzt alle bisherigen Formate (2026-10-28). *(Der Betrag stand bis zum Bau hier und wurde entfernt: Das Repository ist öffentlich, und Decision Log #7 hält den Preis bewusst aus der Öffentlichkeit.)* | ein Angebot mit Preis statt vier ohne |
| 3 | Stufen | **keine**, alle Logos gleich groß (2026-10-28) | „Sie bekommen nichts, was andere nicht bekommen“ gilt auch unter Sponsoren |
| 4 | Beträge auf der Sponsorenseite | **keine**, nur ein allgemeiner Satz zur Verwendung mit Link auf `/open` (2026-10-28) | bei gleichem Preis für alle sagt ein Betrag nichts über den Sponsor, nur über unsere Einnahmen |
| 5 | Pflege | **im Quelltext**, wie Presse-Kit und Roadmap; keine Verwaltungsoberfläche (2026-10-28) | wenige Sponsoren, jede Nennung ist ohnehin ein Vertragsschritt; kein Upload, keine Migration — siehe aber OF-01 |
| 6 | Zahlung | **Rechnung von Hand**, Eingang als Finanzposten Sponsoring (2026-10-28) | ein Zahlungsanbieter wäre ein eigenes Feature samt Auftragsverarbeiter |
| 7 | Preis öffentlich | **nur auf Anfrage** (2026-10-28) | öffentlicher Preis × Zahl der Logos = ableitbare Einnahmen; die Quartalssperre (PRD, Prinzip 5) bliebe nur auf dem Papier. Restrisiko hingenommen: Wer anfragt, kann rechnen |
| 8 | Team-Format | **„Mitarbeitende als Testpersonen“ entfällt** (2026-10-28) | das Paket ersetzt alles; Mithilfe ohne Geld läuft weiter über „Auch ohne Geld“ |
| 9 | Footer | **auf allen Seiten**, auch unter Restaurantseiten (2026-10-28) | der Footer ist klar vom Inhalt getrennt; im Inhalt der Detailseite erscheint nichts (AK-09) |
| 10 | Ende eines Sponsorings | **bleibt als „ehemals“** auf der Übersicht, Seite erreichbar (2026-10-28) | wer verschwindet, sieht nach Verstecken aus |
| 11 | Eigener Text | **kurzes Zitat**, freigegeben, ohne Werbeaussagen, höchstens 300 Zeichen (2026-10-28) | Gegenwert für den Sponsor, ohne dass die Seite zur Anzeige wird |
| 12 | Zuschnitt | **Umbau von `/organisationen/unternehmen` gehört in 15** (2026-10-28) | sonst böte die Seite noch Formate an, während der Footer schon das Paket zeigt |
| 13 | Weitere Orte | **`/open` (Finanzen), `/organisationen/unternehmen`, Faktenblatt** (2026-10-28) | Herkunft des Geldes gehört zu „Derselbe Maßstab gilt für uns“ (Prinzip 4) |
| 14 | Leerer Zustand | **nur „Sponsor werden →“**, Auslieferung sofort, ohne Sponsor (2026-10-28) | kein Platzhalter, kein Warten auf den ersten Vertrag; die Übersicht sagt offen „derzeit keine“ (Prinzip 2) |
| 15 | Viele Sponsoren | **höchstens zwölf im Footer**, die mit dem ältesten laufenden Beginn; alle auf `/sponsoren` (2026-10-28) | eine Leiste mit dreißig Logos wird zur Wand |
| 15a | Link auf die Übersicht | **immer**, sobald es einen aktiven oder ehemaligen Sponsor gibt — nicht erst ab dem 13. (beim Schreiben ergänzt, bestätigt 2026-10-28) | sonst wäre `/sponsoren` mit den Ehemaligen aus dem Footer nicht erreichbar |
| 16 | Zitat gezeichnet von | **nur dem Firmennamen** (2026-10-28) | kein Personenname, keine Einwilligung einer Person, nichts zu löschen, wenn sie die Firma verlässt |
| 17 | Sprache des Zitats | **Originalsprache auf allen fünf Fassungen**, mit Sprachangabe (2026-10-28) | niemand legt dem Sponsor Wörter in den Mund |
| 18 | Pause und Wiederkehr | **alle Zeiträume** auf der Seite; Reihenfolge nach dem laufenden Beginn (2026-10-28) | vollständig und ehrlich |
| 19 | Alte Formate in der Warteliste | **bleiben lesbar**, als „frühere Angabe“ (2026-10-28) | im Gespräch weiß man noch, was der Interessent wollte |
| 20 | Entfernung auf Wunsch | **binnen 30 Tagen vollständig**, Adresse leitet auf `/sponsoren` (2026-10-28) | bei Einzelunternehmen ist der Firmenname oft ein Personenname |
| 21 | Zahlen für Sponsoren | **Seitenaufrufe auf Anfrage** aus Umami, keine Klickmessung (2026-10-28) | keine neue Messung; Sponsoring ist Nennung, kein Reichweitenprodukt |
| 22 | Suchmaschinen | **indexiert, auch ehemalige** (2026-10-28) | die Seiten sind ein öffentlicher Beleg; der bezahlte Link trägt `sponsored` |
| 23 | Website-Link bei Ehemaligen | **entfällt** (beim Schreiben ergänzt, bestätigt 2026-10-28) | er ist nicht mehr bezahlt, und eine verfallene Domain landet sonst Jahre später bei einem Fremden |
| 24 | Abnahme | **„Erster Sponsor in 10 Minuten live“** (2026-10-28) | AK-26 |
| 24a | Gewicht der Footer-Leiste | **Logos laden erst in Sichtweite, zusammen ≤ 150 KB, je ≤ 25 KB** (beim Schreiben ergänzt, bestätigt 2026-10-28) | die Leiste steht auf jeder Seite, auch am Telefon (AK-10) |
| 25 | Ausschlussprüfung | **von Hand vor der Aufnahme**, Regel öffentlich auf `/sponsoren` und der Angebotsseite | ob ein Sponsor einen bewerteten Betrieb führt, erkennt kein Namensvergleich verlässlich |
| 26 | `OF-01` · öffentliches Repository vs. Entfernung auf Wunsch | **Hinweis in der Vereinbarung reicht**; AK-43 gilt für die Website, der Quelltext-Verlauf bleibt (2026-10-28, Betreiber) | Firmen wollen genannt werden, und Archive wie Suchmaschinen hielten den Namen ohnehin fest — ein Umschreiben des Verlaufs entfernte ihn nicht verlässlich |
| 27 | `OF-02` · Laufzeit und Kündigung | **Monatspaket zum Monatsende kündbar, Jahrespaket verlängert sich ohne Kündigung um ein Jahr** (Vorschlag, angenommen 2026-10-28, Betreiber) | das Enddatum steht mit der Kündigung fest und lässt sich sofort eintragen (EC-12) |
| 28 | `OF-03` · Eintrag in `docs/datenschutz.md` | **ja, kurzer Eintrag**, als Aufgabe im Bau (2026-10-28, Betreiber) | ein Einzelunternehmen trägt oft einen Personennamen; die Veröffentlichung braucht eine benannte Grundlage |
| 29 | Mehr über den Sponsor (Überarbeitung) | **Kurzbeschreibung und Profillinks**; Sitz/Gemeinde und öffentlicher Kontakt abgewählt (2026-10-28, Betreiber) | erweitert #11: Das Zitat allein ließ die Seite dünn; Kontaktangaben lägen am nächsten an Werbung |
| 30 | Sprache der Kurzbeschreibung | **fünf Sprachen**, übersetzt von Endlech.lu, jede Fassung vom Sponsor freigegeben (2026-10-28, Betreiber) | lesbar für alle Besucher — anders als das Zitat (#17), das in seiner Sprache bleibt |
| 31 | Weg in die Kette | **Überarbeitung von 15 vor dem Deploy**, nicht als eigenes Feature (2026-10-28, Betreiber) | ausgeliefert wird das Feature einmal vollständig |
| 32 | Ehemalige und Profillinks | **Beschreibung bleibt, Profillinks entfallen** (beim Schreiben vorgeschlagen, 2026-10-28) | dieselbe Begründung wie #23: nicht mehr bezahlt, und ein Profil kann den Inhaber wechseln |
| 33 | Welche Plattformen | **Instagram, Facebook, LinkedIn** (2026-10-28, Betreiber, mit der Auswahl angeboten) | die drei, die Vereine und Unternehmen in Luxemburg tragen; weitere sind ein Nachtrag |
