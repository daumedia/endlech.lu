# 05 · Presse-Kit — Spezifikation

Status: `planned` · Stand: 2026-08-30

## Zweck

Wer heute über Endlech.lu schreiben will — eine Lokalzeitung, ein Gemeindeblatt, ein
Fördergeber, ein Blog —, findet kein Material: keinen freigegebenen Beschreibungstext,
kein Logo in brauchbarer Form, keine belastbaren Zahlen an einer Stelle, keinen Namen
eines Verantwortlichen. Das Impressum nennt „Endlech.lu, Luxemburg" und sonst nichts.

Nach diesem Feature gibt es unter `/presse` eine Seite, von der aus ein Beitrag ohne
eine einzige Rückfrage entstehen kann: Beschreibungstexte in drei Längen zum Kopieren,
ein Faktenblatt mit den Livezahlen der Transparenzseite, ein Bildpaket zum Herunterladen,
Porträt und Kurzvita des Gründers, freigegebene Zitate, datierte Meldungen und ein
Pressekontakt mit zugesagter Antwortzeit.

## Vorbedingungen, die keine Funktion sind

Drei Punkte hängen nicht am Bau, sondern an einer Entscheidung oder an Arbeit außerhalb
des Quelltexts. **Ohne sie ist das Feature nicht auslieferbar** — sie stehen deshalb hier
und nicht unter „Offene Fragen".

| # | Was | Warum es blockiert |
|---|---|---|
| **VB-01** | **Die Marken existieren als Vektordatei.** Wort-Bildmarke hell, Wort-Bildmarke dunkel, Bildmarke, Bildmarke dunkel. | Heute gibt es ausschließlich `public/images/logo.png` (10000 × 7664 px). Ein Presse-Kit, das ein Rasterbild als Logo ausgibt, liefert Zeitungsdruck eine Datei, die er nicht verwenden kann. Das ist Gestaltungsarbeit, keine Programmierarbeit. |
| **VB-02** | **`support@endlech.lu` nimmt Post an und wird gelesen.** | Die Adresse kommt im Projekt bisher nirgends vor — Fußzeile und Impressum führen `info@endlech.lu`. Eine Presseadresse, die ins Leere läuft, ist schlimmer als keine. |
| **VB-03** | **Die Betreiberangaben stehen fest und sind zur Veröffentlichung freigegeben** — Name, ladungsfähige Anschrift, presserechtlich Verantwortlicher. | AK-11 und AK-15 verlangen sie auf zwei Seiten gleichlautend. Siehe dazu den Datenschutzteil und OF-04. |

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B13 · Statische Inhaltsseiten | `approved` | liefert das Muster für eine Inhaltsseite ohne Formular; `/legal` wird von AK-15 mitgezogen |
| B16 · Transparenzseite `/open` | `approved` | Quelle jeder Zahl im Faktenblatt (AK-12 bis AK-14) |
| B24 · Mehrsprachigkeit | `approved` | vier Sprachfassungen, Sprachverweise, Katalogvollständigkeit (AK-30, AK-31, AK-44) |
| B12 · Startseite / B05 · Restaurantsuche | `approved` | Ziel des Handlungsaufrufs am Seitenende |
| 02 · Barrierefreiheit der Plattform | `deployed` | gilt für jede neue Seite; der Prüflauf muss die neue Adresse kennen (AK-32) |
| 03 · Vergleichsseiten | `deployed` | dasselbe Muster: redaktionelle Inhalte im Quelltext, Kennzahlen live, eigener Fußzeilenbereich |

## User Stories

- **US-01** · Als Journalistin einer luxemburgischen Zeitung möchte ich einen freigegebenen
  Beschreibungstext in der Länge kopieren können, die in meinen Beitrag passt, damit ich
  nicht selbst formuliere, was die Plattform tut — und dabei etwas Falsches schreibe.
- **US-02** · Als Redakteur möchte ich das Logo in einer Form herunterladen, die im Druck
  scharf bleibt, damit der Beitrag nicht ohne Bild erscheint.
- **US-03** · Als Journalistin möchte ich wissen, wer hinter der Plattform steht und wie ich
  diese Person erreiche, damit ich die presserechtlich nötigen Angaben habe und ein Zitat
  einholen kann.
- **US-04** · Als Mitarbeiterin einer Gemeinde möchte ich aktuelle, belegte Zahlen zur
  Abdeckung zitieren können, damit eine Vorlage für den Gemeinderat nachprüfbar bleibt.
- **US-05** · Als Fördergeber möchte ich Gründungsverlauf, Status und Finanzierungsmodell an
  einer Stelle sehen, damit ich das Projekt einordnen kann, ohne sechs Seiten zu lesen.
- **US-06** · Als Betreiber möchte ich, dass die Zahlen im Presse-Kit aus derselben Quelle
  stammen wie `/open`, damit nie eine veraltete Zahl in einem Artikel landet, die ich nicht
  mehr einfangen kann.

## Nicht im Scope

- **Screenshots der Anwendung als Bildmaterial** — in Runde 1 abgewählt. Sie müssten
  testdatenfrei aufgenommen und bei jeder Oberflächenänderung erneuert werden. Ein
  Nachtrag an dieser Spec, kein neues Feature.
- **Ein Presseverteiler zum Eintragen** — E-Mail-Adressen von Journalisten wären ein
  weiterer Einwilligungs- und Löschweg. Das gehört zu Feature `04`, nicht hierher.
- **Ein Admin-Bereich für Meldungen und Material** — die Inhalte stehen im Quelltext,
  eine Änderung ist ein Commit (Decision Log #6). Es entsteht keine Entität und keine
  Migration.
- **Mediathek mit Videos, Podcast-Material, Interviewaufzeichnungen** — nichts davon
  existiert.
- **Übersetzte URL-Pfade je Sprache** — der Pfad `/presse` ist in allen vier Sprachen
  derselbe, wie `/organisationen/gemeinden` und `/vergleich`.
- **`sitemap.xml` und `robots.txt`** — hat die Plattform bis heute nicht; unverändert
  offen aus Feature `03`.
- **Kurzbeschreibung, kanonische Adresse und Vorschaubild für die übrigen Seiten** —
  AK-43 und AK-44 gelten nur für `/presse`. Die rund zwanzig anderen Seiten bleiben
  unverändert, und ein Vorschaubild für geteilte Links (Open Graph) entsteht hier nicht.
- **Eine Pressestelle als Prozess** — wer wann antwortet, ist Betrieb. Diese Spec verlangt
  nur, dass eine Antwortzeit *zugesagt* und die Adresse erreichbar ist (VB-02).

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar.

### Auffindbarkeit

- **AK-01** · Angenommen, ein Besucher ist auf einer beliebigen öffentlichen Seite, wenn er
  zur Fußzeile scrollt, dann findet er dort einen Link mit dem Wort „Presse", der auf
  `/presse` führt.
- **AK-02** · Angenommen, ein Besucher öffnet `/de/presse`, wenn die Seite lädt, dann
  antwortet die Anwendung mit HTTP 200 — dasselbe gilt für `/lb/presse`, `/fr/presse` und
  `/en/presse`.
- **AK-03** · Angenommen, ein Besucher liest `/de/about` bis zum Ende, wenn er den letzten
  Abschnitt erreicht, dann findet er einen Verweisblock „Für Presse und Medien" mit einem
  Link auf `/de/presse`.
- **AK-04** · Angenommen, ein Besucher steht auf `/de/presse`, wenn er im Sprachumschalter
  Französisch wählt, dann steht er auf `/fr/presse` — nicht auf der Startseite und nicht
  auf einer Fehlerseite.
- **AK-05** · Angenommen, jemand ruft `endlech.lu/presse` ohne Sprachkürzel auf, wenn die
  Anfrage verarbeitet wird, dann landet er auf der Presseseite in einer der vier Sprachen —
  nicht auf einer Fehlerseite. *(Dieselbe Begründung wie bei `/open`: Die Adresse steht in
  Mails und auf Visitenkarten und darf nicht an einer Sprachwahl scheitern.)*
- **AK-06** · Angenommen, `/de/presse` ist geöffnet, wenn man den Fenstertitel mit denen der
  übrigen Seiten vergleicht, dann trägt sie einen eigenen — keine zwei sind gleich.

### Beschreibungstexte (Boilerplate)

- **AK-07** · Angenommen, `/de/presse` ist geöffnet, wenn man den Abschnitt „Über
  Endlech.lu" liest, dann stehen dort **drei** Beschreibungstexte, jeder mit seiner Länge
  beschriftet: kurz, mittel, lang.
- **AK-08** · Angenommen, die drei Texte sind sichtbar, wenn man ihre Wörter zählt, dann hat
  der kurze 20 bis 30, der mittlere 50 bis 70 und der lange 95 bis 125 Wörter.
- **AK-09** · Angenommen, ein Besucher hat JavaScript abgeschaltet, wenn er einen der drei
  Texte mit der Maus markiert, dann lässt er sich vollständig markieren und kopieren — kein
  Text steckt hinter einem Aufklappelement, das ein Skript öffnen müsste.
- **AK-10** · Angenommen, `/fr/presse` ist geöffnet, wenn man die drei Texte liest, dann
  stehen sie auf Französisch — nicht auf Deutsch und nicht in einer Mischform.

### Faktenblatt

- **AK-11** · Angenommen, `/de/presse` ist geöffnet, wenn man das Faktenblatt liest, dann
  nennt es mindestens: Name der Plattform, Betreiber mit vollständiger Anschrift,
  presserechtlich Verantwortlichen, Land, Gründungsverlauf, aktuellen Status, Lizenz des
  Quelltexts, Lizenz des offenen Datensatzes, verfügbare Sprachen und den Pressekontakt.
- **AK-12** · Angenommen, `/open.json` weist an einem Tag 47 erfasste Lokale aus, wenn man am
  selben Tag die entsprechende Zeile im Faktenblatt liest, dann steht dort ebenfalls 47.
  Dasselbe gilt für die Zahl der verifizierten Lokale und die Gemeindeabdeckung.
- **AK-13** · Angenommen, ein Restaurant wird neu angelegt, wenn `/presse` nach Ablauf des
  Kennzahlen-Zwischenspeichers erneut geladen wird, dann zeigt das Faktenblatt die erhöhte
  Zahl, ohne dass jemand eine Datei geändert hat.
- **AK-14** · Angenommen, `/presse` wird zehnmal hintereinander aufgerufen, wenn man die
  Datenbankabfragen mitzählt, dann werden die Kennzahlen **nicht** bei jedem Aufruf über den
  gesamten Bestand neu berechnet.
- **AK-15** · Angenommen, `/de/presse` und `/de/legal` sind beide geöffnet, wenn man die
  Betreiberangaben nebeneinanderlegt, dann sind Name, Anschrift und Verantwortlicher
  wortgleich — es gibt keine zwei Fassungen derselben Angabe.

### Bildmaterial und Paket

- **AK-16** · Angenommen, `/de/presse` ist geöffnet, wenn man den Abschnitt „Bildmaterial"
  liest, dann sieht man **jede** im Paket enthaltene Datei als Vorschau, mit ihrem
  Dateinamen und ihrem Format daneben.
- **AK-17** · Angenommen, ein Besucher lädt das Paket herunter und entpackt es, wenn er
  seinen Inhalt mit den Vorschauen auf der Seite vergleicht, dann enthält es genau diese
  Dateien — keine mehr, keine weniger, keine ältere Fassung.
- **AK-18** · Angenommen, das Paket ist entpackt, wenn man seinen Inhalt durchgeht, dann
  enthält es mindestens: Wort-Bildmarke für hellen Grund, Wort-Bildmarke für dunklen Grund,
  Bildmarke, Bildmarke für dunklen Grund — alle vier als Vektordatei — sowie das
  Gründerporträt und eine Textdatei mit den Nutzungsbedingungen.
- **AK-19** · Angenommen, ein Besucher klickt den Download, wenn die Datei ankommt, dann
  trägt sie einen sprechenden Namen mit „endlech" darin und lässt sich mit den Bordmitteln
  des Betriebssystems entpacken.
- **AK-20** · Angenommen, der Downloadlink ist sichtbar, wenn man **nur** seinen Linktext
  liest, dann nennt er Dateiformat und ungefähre Größe — ein Screenreader-Nutzer erfährt
  vor dem Klick, was ihn erwartet.
- **AK-21** · Angenommen, `/de/presse` ist geöffnet, wenn man den Abschnitt zu den
  Nutzungsbedingungen liest, dann steht dort: Das Material darf für redaktionelle
  Berichterstattung über Endlech.lu honorarfrei verwendet werden; das Logo darf dabei nicht
  verzerrt, gedreht, umgefärbt oder mit Effekten versehen werden, und die Schreibweise
  lautet „Endlech.lu".
- **AK-22** · Angenommen, jemand hat nur das Paket und nicht die Seite, wenn er es entpackt,
  dann findet er dieselben Nutzungsbedingungen darin — sie sind nicht allein auf der
  Webseite verfügbar.

### Person, Zitate und Meldungen

- **AK-23** · Angenommen, `/de/presse` ist geöffnet, wenn man den Abschnitt zur Person liest,
  dann findet man Porträt, Namen, Funktion und eine Kurzvita, die die persönliche Motivation
  benennt.
- **AK-24** · Angenommen, das Porträt ist sichtbar, wenn man den Bereich darunter liest, dann
  steht dort, wer das Foto gemacht hat und unter welchen Bedingungen es verwendet werden darf.
- **AK-25** · Angenommen, `/de/presse` ist geöffnet, wenn man den Zitat-Abschnitt liest, dann
  stehen dort mindestens **zwei** freigegebene Zitate, jedes mit Namen und Funktion der
  zitierten Person und dem ausdrücklichen Hinweis, dass sie ohne Rückfrage verwendet werden
  dürfen.
- **AK-26** · Angenommen, mindestens eine Pressemitteilung liegt vor, wenn man den Abschnitt
  „Meldungen" liest, dann stehen sie mit Datum, Titel und Text untereinander, die neueste
  zuerst, und das Datum ist in der Sprache der Seite geschrieben.
- **AK-27** · Angenommen, es liegt **keine** Pressemitteilung vor, wenn `/de/presse` geladen
  wird, dann steht der Abschnitt trotzdem da und sagt, dass bislang keine Meldung
  veröffentlicht wurde — mit einem Verweis auf den Pressekontakt. Die Seite zeigt weder eine
  leere Liste noch einen Fehler.

### Kontakt

- **AK-28** · Angenommen, `/de/presse` ist geöffnet, wenn man den Kontaktabschnitt liest, dann
  steht dort `support@endlech.lu` als anklickbare Adresse, eine zugesagte Antwortzeit und der
  Hinweis, dass Interviewanfragen möglich sind.
- **AK-29** · Angenommen, jemand schreibt an die auf der Seite genannte Presseadresse, wenn
  die Nachricht abgeschickt ist, dann kommt sie in einem Postfach an, das gelesen wird — sie
  läuft nicht in eine Unzustellbarkeitsmeldung.

### Suchmaschinen

Am 2026-08-30 beim Entwurf ergänzt: Die beiden Twig-Blöcke dafür entstanden mit Feature
`03` und stehen leer bereit. Ohne Kriterium hätte sie niemand gefüllt und niemand geprüft
— bei einer Seite, die ihren Zweck vor allem über eine Suchmaschine erfüllt, wäre das die
teuerste Auslassung des Features.

- **AK-43** · Angenommen, `/de/presse` ist geöffnet, wenn man den Kopfbereich des
  Dokuments liest, dann steht dort eine geschriebene Kurzbeschreibung in der Sprache der
  Seite — kein aus dem Fließtext abgeschnittener Anfang.
- **AK-44** · Angenommen, `/fr/presse` ist geöffnet, wenn man den Kopfbereich liest, dann
  verweist die kanonische Adresse auf genau diese Seite und die vier Sprachverweise auf
  die Presseseite in den vier Sprachen.

### Mehrsprachigkeit

- **AK-30** · Angenommen, ein Übersetzungsschlüssel des Presse-Kits fehlt in einer der vier
  Sprachen, wenn der Prüflauf des Projekts läuft, dann schlägt er fehl.
- **AK-31** · Angenommen, `/lb/presse` wird geöffnet, wenn man die Seite von oben bis unten
  liest, dann steht darin kein Text, der aus einer anderen Sprache eingesprungen ist.

### Barrierefreiheit

Feature `02` gilt unverändert weiter. Die folgenden Kriterien sind die Stellen, an denen
eine Materialseite typischerweise dagegen verstößt.

- **AK-32** · Angenommen, `/presse` ist gebaut, wenn der Barrierefreiheits-Prüflauf des
  Projekts läuft, dann prüft er die Adresse mit — sie steht in seiner Routenliste.
- **AK-33** · Angenommen, das Fenster ist 320 px breit, wenn `/de/presse` geladen wird, dann
  entsteht **keine** waagerechte Scrollleiste für die Seite.
- **AK-34** · Angenommen, `/de/presse` ist geöffnet, wenn man ihre Überschriften von oben nach
  unten liest, dann gibt es genau eine erste Ebene und keine übersprungene Ebene.
- **AK-35** · Angenommen, ein Screenreader erreicht eine Logo-Vorschau, wenn er sie vorliest,
  dann hört der Nutzer, um welche Variante es sich handelt — nicht „Bild" und nicht den
  Dateinamen allein.

### Datenschutz und Missbrauchsschutz

Der Katalog aus `~/.claude/sdd/sicherheit.md` ist vollständig durchgegangen. Was nicht
zutrifft, steht hier mit Begründung — nicht weggelassen.

**1 · Personenbezogene Daten.** Das Feature *erhebt* keine, aber es **veröffentlicht**
welche, und zwar dauerhaft und an prominenter Stelle: Name, Porträt, Kurzvita und
ladungsfähige Anschrift einer identifizierten natürlichen Person. Die Kurzvita nennt
zudem SMA2 — eine Gesundheitsangabe und damit eine besondere Kategorie nach Art. 9 DSGVO.

Beides ist bewusst entschieden (Decision Log #9 und #10). Die Angaben stehen heute schon
öffentlich auf `/about` beziehungsweise sind für das Impressum ohnehin verlangt; die
Rechtsgrundlage ist die Offenlegung durch die betroffene Person selbst (Art. 9 Abs. 2
lit. e). Was dieses Feature ändert, ist die **Absicht**: Das Material wird gezielt zur
Weiterverbreitung freigegeben. Was einmal in Artikeln steht, ist nicht zurückzuholen —
für die Diagnose so wenig wie für die Wohnadresse. Die Entscheidung ist deshalb im
Decision Log festgehalten, und OF-04 hält den Rückweg offen.

- **AK-36** · Angenommen, `/de/presse` ist geöffnet, wenn man die Seite und das Paket nach
  Angaben zur Person durchsucht, dann finden sich ausschließlich die in AK-11, AK-23 und
  AK-24 genannten — keine Telefonnummer, kein Geburtsdatum, keine weitere Adresse.
- **AK-37** · Angenommen, die Kurzvita ist veröffentlicht, wenn die betroffene Person die
  Angabe zur Behinderung zurückziehen will, dann genügt dafür eine Änderung an einer
  einzigen Textstelle — die Angabe ist nicht über Faktenblatt, Boilerplate und Paket
  verstreut.

**2 · Weitergabe an externe Dienste** — es gibt keine. Die Seite bindet nichts von fremden
Servern ein und ruft keine fremde Schnittstelle auf.

- **AK-38** · Angenommen, ein Besucher öffnet `/de/presse`, wenn man die ausgehenden
  Verbindungen des Browsers beobachtet, dann wird **kein** fremder Server kontaktiert — die
  IP-Adresse des Besuchers erreicht keinen Dritten.

**3 · Zugriff** — die Seite ist öffentlich, ohne Anmeldung, ohne Rollen. Es gibt keinen
Datensatz mit Eigentümer, damit auch keinen Fall „fremde Kennung im Aufruf". Nichts auf der
Seite lässt sich durch einen Aufruf ändern.

- **AK-39** · Angenommen, jemand ruft `/de/presse` mit beliebigen Zusatzparametern in der
  Adresse auf, wenn die Anfrage verarbeitet wird, dann ändert sich die ausgelieferte Seite
  nicht und es entsteht kein Fehler.

**4 · Missbrauch und Kosten** — kein Rate Limit im Sinne eines Zählers: Die Seite löst keine
E-Mail aus, prüft kein Geheimnis und kostet pro Aufruf nichts. Der dritte Fall der
Projektkonvention (ein Weg, der bei jedem Aufruf den gesamten Bestand lädt) ist über den
vorhandenen Kennzahlen-Zwischenspeicher gelöst und wird mit AK-14 nachgewiesen. Es gibt
keinen Upload; das Paket ist eine feste Datei und wird nicht zur Laufzeit erzeugt.

- **AK-40** · Angenommen, das Paket wird hundertmal hintereinander heruntergeladen, wenn man
  die Serverlast dabei beobachtet, dann entsteht dabei kein Rechenaufwand über das Ausliefern
  einer Datei hinaus — es wird nichts gepackt und nichts berechnet.

**5 · Löschen und Auskunft** — *trifft für Besucher nicht zu.* Es entstehen keine Daten, die
zu einem Nutzerkonto gehören; bei einer Kontolöschung ist an diesem Feature nichts zu tun.
Für die veröffentlichten Angaben zur Person gilt AK-37.

**6 · Geheimnisse** — *trifft nicht zu.* Das Feature braucht keinen Schlüssel und keine
Zugangsdaten. Die Presseadresse aus VB-02 ist eine Postfach-Einrichtung beim Hoster, kein
Anwendungsgeheimnis.

### Abnahme

Aus Runde 4 des Interviews ohne Einzelauswahl übernommen — die vier Punkte sind der
Maßstab, an dem das Feature als fertig gilt. Zwei davon sind bereits oben abgedeckt
(Zahlengleichheit → AK-12, Materialgleichheit → AK-17) und werden hier nicht wiederholt;
zwei Nummern für dieselbe Prüfung machen den Testbericht doppeldeutig.

- **AK-41** · Angenommen, jemand soll einen Beitrag über Endlech.lu schreiben, wenn er
  ausschließlich `/presse` benutzt, dann findet er dort Beschreibungstext, aktuelle Zahlen,
  Bildmaterial, Betreiberangaben und einen Kontakt — er muss für den Beitrag keine Mail
  schreiben.
- **AK-42** · Angenommen, alle vier Sprachfassungen sind gebaut, wenn man jede von oben bis
  unten liest, dann ist jede vollständig — jeder der in AK-07 bis AK-28 genannten Abschnitte
  existiert in jeder Sprache.

## Edge Cases

- **EC-01** · Seite ohne JavaScript → vollständig lesbar und bedienbar, Beschreibungstexte
  eingeschlossen (deckt AK-09)
- **EC-02** · Kein einziges Restaurant in der Datenbank → das Faktenblatt zeigt „0", keine
  Division durch null, kein leeres Feld
- **EC-03** · Der Kennzahlen-Zwischenspeicher ist leer (erster Aufruf nach dem Ausliefern) →
  die Seite lädt, die Zahlen werden einmal berechnet, kein Fehler
- **EC-04** · Die Paketdatei fehlt auf dem Server (nicht mitgeliefert, versehentlich
  entfernt) → der Downloadlink führt nicht in eine nackte Fehlerseite; der Fall fällt vor der
  Auslieferung auf, nicht dem ersten Journalisten
- **EC-05** · Druck oder PDF-Export → das Farbband des Kopfbereichs bleibt lesbar, kein weißer
  Text auf weißem Grund; die Beschreibungstexte sind vollständig gedruckt
- **EC-06** · Sehr langer Meldungstitel → bricht um, statt die Spaltenbreite zu sprengen
- **EC-07** · Fenster 320 px breit → keine waagerechte Scrollleiste, Logo-Vorschauen
  untereinander statt nebeneinander
- **EC-08** · Eine Logo-Variante fehlt im Paket, steht aber als Vorschau auf der Seite → die
  Abweichung ist prüfbar (AK-17) und nicht dem Auge überlassen
- **EC-09** · Ein Zitat wird zurückgezogen → die Seite bleibt gültig, auch wenn nur noch zwei
  Zitate übrig sind; unter zwei greift AK-25 und der Bau schlägt fehl
- **EC-10** · Datumsformat auf Luxemburgisch → das Datum einer Meldung erscheint in
  luxemburgischer Schreibweise, nicht in deutscher (deckt AK-26 in der `lb`-Fassung)

## Offene Fragen

- **OF-01** · **Die Datenschutzstufe des Projekts ist weiterhin nicht festgelegt.**
  `docs/datenschutz.md` nimmt Stufe B an und benennt die Annahme als unbestätigt.
  Dieses Feature verschiebt die Frage: Mit der bewussten Veröffentlichung einer
  Gesundheitsangabe zu Werbe- und Pressezwecken liegt eine besondere Kategorie nach Art. 9
  vor — auch wenn die betroffene Person sie selbst offenlegt. Entscheidet Michael; die
  Antwort gehört in `docs/datenschutz.md`, nicht hierher.
- **OF-02** · Wer erstellt die vier Vektormarken aus VB-01, und bis wann? Ohne sie ist das
  Feature nicht auslieferbar. Entscheidet Michael.
- **OF-03** · Welche Antwortzeit wird in AK-28 zugesagt? Die Referenzseite verspricht
  „Antwort in der Regel am selben Werktag". Für einen Alleinbetrieb (PRD, Risiko 3) ist das
  eine Zusage, die im Urlaub bricht. Vorschlag: „innerhalb von zwei Werktagen". Entscheidet
  Michael.
- **OF-04** · ~~Privatanschrift oder c/o?~~ — **entschieden am 2026-08-30: gar keine
  Anschrift.** Weder Privatadresse noch c/o; erreichbar ist der Betreiber über den
  Pressekontakt. Die Zeile „Anschrift" entfällt im Faktenblatt, statt eine Mailadresse
  unter dieser Überschrift zu führen.

  ⚠ **Folge, ausdrücklich in Kauf genommen: AK-11 kann damit nicht bestehen.** Das
  Kriterium verlangt „Betreiber mit vollständiger Anschrift", und das Impressum zitiert
  selbst „§ 5 TMG / Art. 11 Loi sur le commerce électronique" — beide verlangen eine
  geografische Anschrift. Eine spätere c/o- oder Postfachadresse erfüllt das Kriterium
  ohne Codeänderung: Die Zeile erscheint wieder, sobald der Parameter gefüllt ist.
- **OF-05** · ~~Wer hat das Gründerporträt aufgenommen?~~ — **entschieden am 2026-08-30:**
  Michael selbst. Der Credit lautet „Foto: Michael (Selbstporträt), Endlech.lu" und steht in
  allen vier Katalogen. ⚠ Der **vollständige Name** hängt weiterhin an VB-03; wer die
  Betreiberangaben füllt, zieht diesen Schlüssel mit.
- **OF-06** · Gibt es einen Anlass für eine erste Pressemitteilung (Veröffentlichung des
  Presse-Kits, Start der Wartelisten, eine erste Gemeinde-Erhebung)? Solange nicht, greift
  AK-27 — das ist zulässig, aber eine Seite, die Kompetenz zeigen soll, beginnt dann mit
  einer Leerstelle.
- **OF-07** · Wer erneuert das Paket, wenn sich eine Datei darin ändert? AK-17 macht die
  Abweichung prüfbar, aber nicht unmöglich. Vorschlag: Der Prüflauf vergleicht Paketinhalt
  und Verzeichnis, damit der Fall beim Bauen auffällt und nicht beim Journalisten.
  Entscheidet `sdd-architektur`.

- **OF-08** · ~~Bei 768 px scrollt die Website waagerecht~~ — **am 2026-08-30 erfasst
  und aus diesem Feature herausgelöst.** Der Befund liegt jetzt als *Bekannte Lücke 7*
  in `docs/app-shell.md`, mit vollständiger Messreihe: abgemeldet 768–849 px (+36 px bei
  768), **angemeldet 768–999 px (+81 px bei 768)**. Ursache ist der `md:`-Umbruchpunkt
  der Kopfzeile in `base.html.twig`, nicht diese Seite — nachgemessen auf `/about`,
  `/vergleich`, `/open` und `/partner`. ⚠ **Der Befund war nicht neu** — er steht seit
  der QA von Feature `02` als **BF-80** in `features/befunde.md`; die Messung hat ihn
  verschärft (angemeldet doppelt so groß, Band bis unter 1000 px) und ist dort
  ergänzt. Für Feature 05 ist nichts mehr offen.
- **OF-09** · **Solange VB-01 offen ist, laufen vier Bildanfragen in HTTP 404.** Die
  Vorschaukacheln zeigen dann das Bruchbild-Symbol des Browsers. Gemessen am
  2026-08-30: genau die vier SVG-Adressen, das Porträt lädt. Der Entwurf sieht einen
  Fehlerzustand nur für die **Paketdatei** vor, nicht für einzelne Vorschauen. Soll
  eine fehlende Datei die Kachel ausblenden (ein Dateisystemzugriff je Bild), oder
  bleibt es dabei, dass VB-01 ohnehin vor der Auslieferung erfüllt sein muss?
  Bewusst nicht nebenbei entschieden — es wäre Verhalten ohne Kriterium.

- **OF-10** · **Ein Paket unter 512 Byte wird als „0 kB" ausgewiesen.** Beim Beheben von
  BUG-05 am 2026-08-30 beobachtet: Die Größe entsteht aus `(sizeBytes / 1024)|round`, und
  ein 166 Byte großes Testpaket ergibt „ZIP · 0 kB". Mit realem Material tritt der Fall
  nicht auf (gemessen: 244 kB → „ZIP · 244 kB"), und AK-20 verlangt nur eine ungefähre
  Größe. Nicht nebenbei geändert — soll die Anzeige eine Untergrenze bekommen, oder bleibt
  es dabei? Entscheidet Michael.

- **OF-11** · **Zwei verschiedene Cyan-Töne im Presse-Kit.** Beim Erzeugen der Marken am
  2026-08-30 sichtbar geworden: Die Fläche der Bildmarke ist `#01b6ed` (aus
  `public/images/logo.png` ausgelesen), die Nutzungsbedingungen nennen als Markenfarbe
  dagegen `#0891b2` (cyan-600 aus dem Design-System), und der Schriftzug „Endlech" in der
  Wort-Bildmarke nimmt ebenfalls `#0891b2` — beide Werte stammen belegt aus dem Projekt,
  passen aber nicht zueinander. Ein Presse-Kit, das eine Markenfarbe nennt und eine andere
  ausliefert, fällt jedem Gestalter auf. Welcher Ton gilt? Entscheidet Michael; die
  Änderung ist ein Wert an zwei Stellen.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Aufnahme in die Kette | neues Feature `05`, Prio P2 | Ein Presse-Kit stand nirgends im Inventar. Ein Nachtrag an `B13` wäre nach den Regeln der Kette falsch — erweiterte Bestandsfeatures bekommen eine eigene Nummer |
| 2 | Ort | eigene Seite `/presse` plus Verweisblock auf `/about` | Die Referenzseite legt Presse als Anker unter `/company`, hat aber keine eigene Über-Seite. Hier gibt es sie — und eine zitierbare Adresse ist für Presse mehr wert als ein Anker |
| 3 | Umfang | Boilerplate, Faktenblatt, Bildpaket, Person, Zitate, Meldungen | die sechs Bausteine, nach denen eine Redaktion tatsächlich fragt |
| 4 | Logo-Format | Vektormarken werden erstellt, bevor ausgeliefert wird (VB-01) | Ein Rasterlogo aus einer 10000-px-Datei ist im Druck unbrauchbar. Lieber ein blockiertes Feature als ein Presse-Kit, dessen Hauptdatei nichts taugt |
| 5 | Auslieferung des Materials | ein Paket, keine Einzeldownloads | Entscheidung des Betreibers. Folge: Was drin ist, muss vorher sichtbar sein — deshalb AK-16, und gegen das Veralten AK-17 |
| 6 | Pflege der Meldungen | redaktionell im Quelltext | wie Feature `03`: keine Entität, keine Migration, kein Admin für Inhalte, die sich wenige Male im Jahr ändern |
| 7 | Nutzungsrechte | honorarfrei für redaktionelle Berichterstattung, Logo unverändert | Der Zweck eines Presse-Kits ist, ohne Rückfrage arbeiten zu können. CC BY 4.0 wie beim Datensatz wäre für eine Marke zu weit — es erlaubte ausdrücklich die Bearbeitung des Logos |
| 8 | Markenregeln | **doch aufgenommen** (AK-21, AK-22), obwohl in Runde 1 abgewählt | Die gewählte Freigabe *ist* eine Bedingung („Logo unverändert"). Ohne die Regeln stünde ein Logo-Download ohne jede Auflage im Netz. Sie stehen deshalb als Teil der Freigabe da, nicht als eigener Gestaltungsabschnitt |
| 9 | Betreiberangaben | vollständig, mit Privatanschrift | Journalisten fragen als Erstes danach, und `/legal` genügt heute weder Art. 11 der luxemburgischen E-Commerce-Regelung noch dem Presserecht. Folge: `/legal` wird mitgezogen (AK-15), und die Wohnadresse ist dauerhaft öffentlich — siehe OF-04 |
| 10 | Behinderung in der Vita | bewusst Teil des Materials | Sie ist der Grund, warum diese Plattform glaubwürdig ist („aus eigener Erfahrung"), und steht bereits auf `/about`. Folge: Sie wird gezielt zur Verbreitung freigegeben — AK-37 hält den Widerruf an einer Stelle zusammen |
| 11 | Pressekontakt | `support@endlech.lu` | eigene Adresse statt der allgemeinen `info@`. Folge: VB-02 — sie existiert im Projekt bisher nicht |
| 12 | Sprachen | alle vier | alles andere bräche mit B24; `lb` ist die Vorgabesprache, eine Seite ohne sie wäre für Besucher ohne Sprachwahl leer |
| 13 | Leerer Zustand der Meldungen | Hinweis statt Ausblenden | Ein ausgeblendeter Abschnitt verschweigt, dass es hier künftig etwas gibt. Der Hinweis führt zum Pressekontakt und passt zu Produktprinzip 2 („Lücken werden gezeigt") |
| 14 | Zahlen im Faktenblatt | live aus derselben Quelle wie `/open` | Eine feste Zahl im Text veraltet still — und eine veraltete Zahl in einem Artikel ist nicht einzufangen. „Derselbe Maßstab gilt für uns" verlangt dieselbe Quelle wie die Transparenzseite |
| 15 | Paketerzeugung | fertige Datei im Repo | kein Rechenaufwand pro Aufruf, kein Limiter nötig (AK-40). Der Preis ist das mögliche Veralten — dagegen AK-17 und OF-07 |
| 17 | Kurzbeschreibung und kanonische Adresse | aufgenommen als AK-43 und AK-44 | am 2026-08-30 beim Entwurf entschieden. Die Blöcke existieren seit Feature `03` und wären eine Zeile — aber was kein Kriterium hat, prüft `sdd-qa` nicht und der nächste Umbau entfernt es unbemerkt. Eine Presseseite wird über eine Suchmaschine gefunden oder gar nicht |
| 16 | Abnahmekriterien | alle vier Vorschläge übernommen | in Runde 4 ohne Einzelauswahl geblieben; sie widersprechen einander nicht und decken je einen anderen Fehlerfall |

---

## Hinweise für `/sdd-architektur 05`

Kein Teil der Kriterien — Fundstellen aus dem Bestand, damit sie beim Entwurf nicht neu
gesucht werden müssen.

| Stelle | Was daran zu beachten ist |
|---|---|
| `templates/base.html.twig`, Fußzeile Spalte 2 | trägt bereits zehn Einträge; ein elfter für „Presse" ist die naheliegende Stelle. `docs/app-shell.md` führt die Liste namentlich auf und ist zweimal ausgelaufen — mitziehen |
| `src/Comparison/ComparisonRegistry.php` | das Muster für redaktionelle Inhalte als Aufzählung im Quelltext, samt Twig-Erweiterung für die Fußzeile (Feature 03) |
| `src/Open/OpenStatsService.php` | liefert `restaurants`, `verified`, `verifiedShare`, `communesCovered`, `communeCoverage` — Quelle für AK-12 bis AK-14, inklusive des Zwischenspeichers `cache.open_stats` |
| `templates/impressum/index.html.twig` + `translations/messages.*.yaml`, Block `legal` | `info_text` lautet heute `"Endlech.lu\nLuxemburg"`. AK-15 verlangt, dass hier und auf `/presse` dasselbe steht — die Änderung berührt B13 |
| `public/uploads/team/michael.jpg` | das Porträt liegt bereits da. ⚠ `public/uploads/team/` ist per `!`-Regel aus `.gitignore` ausgenommen — was dort nicht committet ist, löscht der Deploy (`git clean -fd`) |
| `public/images/logo.png` | 10000 × 7664 px, nicht quadratisch. `bin/generate-pwa-icons.sh` zeigt, wie im Projekt bisher skaliert wird (`sips`, vorher quadratisch padden) |
| `tests/Functional/AccessibilityStructureTest.php` | Routenliste — `/presse` eintragen, sonst greift AK-32 nicht |
| `tests/Unit/Translation/CatalogueCompletenessTest.php` | der Scanner erfasst nur *literale* Schlüssel mit Punkt; dynamisch zusammengesetzte fallen durchs Netz und machten AK-30 wirkungslos |
| `config/routes.yaml` | Muster für eine sprachfreie Weiterleitung (`app_open_redirect`) — Vorlage für AK-05 |
| `docs/design-system.md` | dunkles Kopfband `from-cyan-700 to-purple-800` für Außenseiten, als `<section>` wegen EC-05; jede Aktion `min-h-[48px]`, `motion-safe:transition`, `focus:outline-2` |

**Die Texte selbst sind nicht Teil dieser Spec.** Boilerplate, Zitate und Kurzvita sind
redaktionelle Arbeit und entstehen beim Bauen. Festgelegt sind hier ihre Zahl, ihre Länge
(AK-08), ihre Sprachen und die Pflicht, dass sie freigegeben sind — nicht ihr Wortlaut.
