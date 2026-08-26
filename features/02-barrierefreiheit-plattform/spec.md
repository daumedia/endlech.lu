# 02 · Barrierefreiheit der Plattform — Spezifikation

Status: `planned` · Stand: 2026-08-26 · **Anforderung vor Code**

## Zweck

Endlech.lu misst, wie zugänglich Restaurants sind. Nach diesem Feature ist die
Plattform selbst nachweislich zugänglich — bedienbar mit Tastatur, Screenreader,
Vergrößerung und Kontrastmodus — und sagt öffentlich, wie weit sie dabei ist.

**Warum das kein Nebenfeature ist:** Produktprinzip 4 lautet „Derselbe Maßstab
gilt für uns". Für die eigene Website gab es diesen Maßstab bisher nirgends. Eine
Plattform, die Betriebe auffordert, ihre Zugänglichkeit offenzulegen, und deren
eigenes Anmeldeformular im Kontrastmodus keinen sichtbaren Fokus hat, beschädigt
genau das Kapital, von dem sie lebt.

## Normrahmen

| Grundlage | Was daraus gilt |
|---|---|
| **EN 301 549** (europäische Norm für IKT-Barrierefreiheit) | Kapitel 9 „Web" — deckungsgleich mit den WCAG-Erfolgskriterien der Stufen A und AA |
| **WCAG 2.2, Stufe AA** | der geprüfte Kriterienkatalog. Obermenge von 2.1 AA, bringt Zielgrößen und „Fokus nicht verdeckt" mit |
| **RAWeb** (luxemburgischer Referenzrahmen) | Form und Pflichtinhalte der Barrierefreiheitserklärung |

⚠ **Die Zusage ist freiwillig, und das muss sie auch sagen.** RAWeb bindet
öffentliche Stellen; für Dienstleistungen privater Anbieter kennt der europäische
Rechtsrahmen eine Kleinstunternehmens-Ausnahme, unter die ein Alleinbetrieb
fällt. Die Erklärung darf deshalb keine Rechtspflicht behaupten, die nicht
besteht; sie tritt als **freiwillige Selbstverpflichtung** auf (Decision Log #14).
Der eigentliche Anlass ist ohnehin ein anderer: Eine Gemeinde, die eine Erhebung
beauftragt, ist selbst eine öffentliche Stelle und fragt nach diesem Nachweis.

## Geltungsbereich

| Enthalten | Warum |
|---|---|
| alle öffentlichen Seiten | was Gäste und prüfende Gemeinden sehen |
| Verwaltungsbereich `/admin` | 37 der 57 Fundstellen ohne belastbare Fokusgestaltung liegen dort; sobald Beirat oder Gemeindepersonal mitarbeiten, ist es ein Arbeitsplatz |
| PWA: Ersatzseite bei fehlender Verbindung, Bottom-Navigation | eigenständiges HTML außerhalb jeder Projektkonvention; dazu die auf dem Telefon fehlenden Wege |
| E-Mail-Vorlagen | von WCAG nicht erfasst, aber jeder Kontoweg führt durch eine Mail |

**Ausgenommen mit Begründung:** die Swagger-Oberfläche unter `/api/docs` — eine
mitgelieferte Entwicklerwerkzeug-Oberfläche ohne eigenen Code (Decision Log #12,
endgültig).

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01–B26 | approved | das Feature fasst in ihre Templates; ein noch wandernder Bestand wäre kein Prüfgegenstand |
| Auslieferung der offenen Reparaturen (B01, B02, B04, B23) | ausstehend | sie liegen committet, aber nicht live. Wer parallel dieselben Templates umbaut, erzeugt zwei Umbauten in denselben Dateien |

## User Stories

- **US-01** · Als Mensch, der eine Maus nicht bedienen kann, möchte ich die gesamte
  Plattform mit der Tastatur benutzen und jederzeit sehen, wo ich bin.
- **US-02** · Als Mensch mit Sehbehinderung möchte ich Inhalte vergrößern, mit
  erhöhtem Kontrast lesen und mit einem Screenreader erfassen können.
- **US-03** · Als Mensch mit motorischer Einschränkung möchte ich Schaltflächen
  sicher treffen und Formulare ohne Fallen ausfüllen können.
- **US-04** · Als Mensch, der die Plattform nur auf dem Telefon nutzt, möchte ich
  dieselben Funktionen erreichen wie am Rechner.
- **US-05** · Als Gemeinde möchte ich vor der Beauftragung nachlesen können, wie
  zugänglich die Plattform ist und woran das geprüft wurde.
- **US-06** · Als Betroffener möchte ich eine Barriere melden können, ohne dafür
  meine Behinderung offenlegen oder ein Konto anlegen zu müssen.

## Nicht im Scope

- **Barrierefreiheitsmerkmale der Restaurants** — das ist der Gegenstand von B05,
  B06, B11, B20 und B23. Hier geht es ausschließlich um die Plattform.
- **Dark Mode** — im PRD ausdrücklich ausgeschlossen.
- **Stufe AAA und Leichte Sprache** — nicht Teil der Zusage.
- **Test mit Betroffenen als Abnahmebedingung** — läuft über den Beirat (B15) und
  hat eine eigene Terminlage; eine Abnahme darf nicht an fremden Kalendern hängen.
- **Native iOS-App** — existiert nicht; Kapitel 11 der Norm greift erst dann.
- **Konformitätsgrad als Kennzahl auf `/open`** — dort bedeutet „Barrierefreiheit"
  die der Restaurants; eine zweite Bedeutung in derselben Liste wäre verwechselbar.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar. Werkzeugnamen stehen im Decision
Log, nie in einem Kriterium — geprüft wird das Ergebnis, nicht der Weg dorthin.

### A · Tastatur und Fokus (US-01)

- **AK-01** · Angenommen, eine beliebige Seite ist geladen, wenn die Tabulatortaste
  einmal gedrückt wird, dann erscheint als erstes ein sichtbarer Link „Zum Inhalt
  springen", der den Fokus hinter die Navigation setzt.
- **AK-02** · Angenommen, jemand bedient nur die Tastatur, wenn er eine Seite von
  oben bis unten durchläuft, dann erreicht und aktiviert er jede Schaltfläche, jeden
  Link und jedes Feld — einschließlich Sprachumschalter, Filter, Bildergalerie,
  Wizard-Schritte und Cookie-Banner.
- **AK-03** · Angenommen, ein Element hat den Fokus, wenn hingesehen wird, dann ist
  die Fokusanzeige auf **jedem** Untergrund sichtbar — auch auf den farbigen
  Verlaufsbändern und auf Startseite, Über-Seite und Vorschlagsseite, wo es heute
  gar keine gibt.
- **AK-04** · Angenommen, der Kontrastmodus des Betriebssystems ist eingeschaltet,
  wenn ein Element den Fokus erhält, dann bleibt die Fokusanzeige sichtbar.
- **AK-05** · Angenommen, eine lange Seite wird durchlaufen, wenn der Fokus ein
  Element im oberen Bereich erreicht, dann wird es **nicht** vom feststehenden
  Kopfbereich verdeckt.
- **AK-06** · Angenommen, die Bildergalerie ist geöffnet, wenn nur die Tastatur
  benutzt wird, dann schließt Escape sie, der Fokus bleibt bis dahin innerhalb, und
  danach steht er wieder auf dem Bild, das sie geöffnet hat.
- **AK-07** · Angenommen, ein Menü oder Banner ist offen, wenn weitergetabt wird,
  dann gibt es keine Stelle, an der der Fokus hängenbleibt.
- **AK-08** · Angenommen, eine Seite wird durchlaufen, wenn die Reihenfolge
  beobachtet wird, dann folgt sie der sichtbaren Anordnung.

### B · Wahrnehmbarkeit (US-02)

- **AK-09** · Angenommen, ein Restaurantfoto ist hochgeladen, wenn die Detailseite
  betrachtet wird, dann hat jedes Bild einen Alternativtext — und beim Hochladen
  lässt sich keins ohne einen speichern.
- **AK-10** · Angenommen, ein Bild oder Symbol ist rein schmückend, wenn ein
  Screenreader die Seite liest, dann wird es nicht vorgelesen.
- **AK-11** · Angenommen, ein beliebiger Text steht auf der Seite, wenn sein
  Kontrast gemessen wird, dann liegt er bei mindestens 4,5:1 — bei großer Schrift
  bei mindestens 3:1.
- **AK-12** · Angenommen, ein Bedienelement oder ein Diagrammbestandteil trägt
  Information, wenn sein Kontrast gegen die Umgebung gemessen wird, dann liegt er
  bei mindestens 3:1.
- **AK-13** · Angenommen, der Browser wird auf 200 % vergrößert, wenn die Seite
  betrachtet wird, dann geht kein Inhalt und keine Funktion verloren.
- **AK-14** · Angenommen, das Fenster ist 320 px breit, wenn gescrollt wird, dann
  gibt es kein waagerechtes Scrollen der Seite — ausgenommen Elemente, die
  ausdrücklich in einem eigenen Bereich waagerecht scrollen.
- **AK-15** · Angenommen, eine Seite wird nach Überschriften durchsucht, wenn die
  Ebenen gelesen werden, dann trägt sie genau eine erste Ebene und überspringt keine.
- **AK-16** · Angenommen, jemand erhöht Zeilen-, Wort- und Zeichenabstand, wenn er
  die Seite betrachtet, dann wird kein Text abgeschnitten und keine Schaltfläche
  unlesbar.
- **AK-17** · Angenommen, eine Aussage ist farbig hervorgehoben, wenn die Seite in
  Graustufen betrachtet wird, dann bleibt sie erkennbar — jede farbcodierte Aussage
  trägt zusätzlich Text, Vorzeichen oder Symbol.

### C · Formulare (US-03)

- **AK-18** · Angenommen, ein Formularfeld wird betrachtet, wenn seine Beschriftung
  angeklickt wird, dann springt der Fokus in das Feld.
- **AK-19** · Angenommen, ein Formular wird fehlerhaft abgesendet, wenn die Antwort
  erscheint, dann steht der Fehler als Text am betroffenen Feld und benennt Feld
  und Ursache — eine rote Umrandung allein genügt nicht.
- **AK-20** · Angenommen, ein Formular wird fehlerhaft abgesendet, wenn die Antwort
  erscheint, dann steht der Fokus im ersten fehlerhaften Feld.
- **AK-21** · Angenommen, ein Feld ist Pflicht, wenn ein Screenreader es erreicht,
  dann sagt er das an — ein Sternchen allein genügt nicht.
- **AK-22** · Angenommen, ein Feld erwartet Name, E-Mail-Adresse oder Telefonnummer,
  wenn der Browser es ausfüllen soll, dann kennt er den Zweck des Feldes.
- **AK-23** · Angenommen, eine Angabe wurde im selben Vorgang schon gemacht, wenn
  ein späterer Schritt sie erneut verlangt, dann ist sie vorbelegt oder auswählbar.
- **AK-24** · Angenommen, im fünfstufigen Vorschlags-Wizard wird der Schritt
  gewechselt, wenn ein Screenreader benutzt wird, dann werden der neue Schritt und
  die Position in der Abfolge angesagt.

### D · Zielgrößen und Bewegung (US-03)

- **AK-25** · Angenommen, ein Bedienelement wird ausgemessen, wenn daneben keine
  gleichwertige größere Alternative steht, dann ist seine Trefferfläche mindestens
  24 × 24 px — bei öffentlichen Hauptaktionen 48 px, im Verwaltungsbereich 44 px.
- **AK-26** · Angenommen, im Betriebssystem ist „Bewegung reduzieren" eingeschaltet,
  wenn eine Seite geladen und bedient wird, dann laufen keine Übergänge,
  Verschiebungen oder Animationen.
- **AK-27** · Angenommen, etwas bewegt sich oder blinkt länger als fünf Sekunden,
  wenn die Seite betrachtet wird, dann lässt es sich anhalten — oder es gibt nichts
  dergleichen.

### E · Sprache und Struktur (US-02)

- **AK-28** · Angenommen, eine Seite ist in einer der vier Sprachen geladen, wenn
  die Sprachauszeichnung geprüft wird, dann entspricht sie der Sprache des Inhalts.
- **AK-29** · Angenommen, ein Abschnitt steht in einer anderen Sprache als die
  Seite, wenn ein Screenreader ihn liest, dann wechselt er die Aussprache.
- **AK-30** · Angenommen, mehrere Seiten werden nacheinander geöffnet, wenn ihre
  Fenstertitel verglichen werden, dann beschreibt jeder seinen Inhalt, und keine
  zwei verschiedenen Seiten tragen denselben.
- **AK-31** · Angenommen, ein Screenreader listet die Bereiche einer Seite auf, wenn
  die Liste gelesen wird, dann findet er Kopfbereich, Navigation, Hauptinhalt und
  Fußbereich getrennt.
- **AK-32** · Angenommen, ein aufklappbares Element wird geöffnet und geschlossen,
  wenn ein Screenreader mitliest, dann meldet es seinen Zustand.
- **AK-33** · Angenommen, ein Link wird ohne seine Umgebung gelesen, wenn nur sein
  Text zählt, dann ist erkennbar, wohin er führt — kein „hier", kein „mehr".

### F · Auf dem Telefon und in der installierten App (US-04)

- **AK-34** · Angenommen, jemand ist auf einem Telefon angemeldet, wenn er sich
  abmelden will, dann findet er dafür einen Weg. **Heute gibt es keinen.**
- **AK-35** · Angenommen, jemand ist auf einem Telefon, wenn er die Sprache wechseln
  will, dann findet er dafür einen Weg.
- **AK-36** · Angenommen, eine Funktion ist am Rechner erreichbar, wenn dieselbe
  Anwendung auf einem Telefon geöffnet wird, dann ist sie auch dort erreichbar.
- **AK-37** · Angenommen, das Gerät ist ohne Verbindung und die installierte App
  wird geöffnet, wenn die Ersatzseite erscheint, dann erfüllt sie dieselben Regeln
  wie jede andere Seite: Sprachauszeichnung, eigener Fenstertitel, Kontrast,
  sichtbarer Fokus, bedienbare Schaltfläche.
- **AK-38** · Angenommen, eine Bestätigungs- oder Hinweismail wird geöffnet, wenn
  sie betrachtet wird, dann erfüllen Text und Schaltfläche die Kontrastvorgabe,
  jeder Link ist aus sich heraus verständlich, und der Inhalt bleibt vollständig,
  wenn der Mailprogramm keine Bilder lädt.

### G · Verwaltungsbereich (US-01, US-03)

- **AK-39** · Angenommen, der Verwaltungsbereich wird nur mit der Tastatur bedient,
  wenn ein Restaurant angelegt, bearbeitet und ein Foto einsortiert wird, dann ist
  jeder Schritt ohne Maus möglich — die Bildsortierung hat eine Alternative zum
  Ziehen und Ablegen.
- **AK-40** · Angenommen, ein Feld im Verwaltungsbereich erhält den Fokus, wenn
  hingesehen wird, dann ist die Anzeige sichtbar und bleibt es im Kontrastmodus.
- **AK-41** · Angenommen, das Küchen-Auswahlfeld wird mit der Tastatur bedient, wenn
  getippt und ausgewählt wird, dann werden die Vorschläge angesagt und die getroffene
  Auswahl bestätigt.

### H · Barrierefreiheitserklärung (US-05)

- **AK-42** · Angenommen, ein Besucher ist auf einer beliebigen Seite, wenn er in
  die Fußzeile schaut, dann findet er einen Link „Barrierefreiheit" zur Erklärung.
- **AK-43** · Angenommen, die Erklärung wird gelesen, wenn ihr Inhalt geprüft wird,
  dann nennt sie: Konformitätsgrad, Datum der Prüfung, angewandtes Prüfverfahren,
  Geltungsbereich, die nicht zugänglichen Inhalte mit Begründung und den
  Rückmeldeweg. Eine gesetzliche Beschwerdestelle nennt sie **nicht** — für einen
  privaten Kleinstanbieter ist keine zuständig; stattdessen steht dort, dass die
  Zusage freiwillig ist (Decision Log #13, #14).
- **AK-44** · Angenommen, die Erklärung wird in einer der vier Sprachen aufgerufen,
  wenn sie gelesen wird, dann steht sie vollständig in dieser Sprache.
- **AK-45** · Angenommen, ein Inhalt ist nicht zugänglich, wenn die Erklärung ihn
  aufführt, dann nennt sie das betroffene Kriterium, den Grund und — soweit
  vorgesehen — ein Datum für die Behebung.
- **AK-46** · Angenommen, die Erklärung nennt einen Konformitätsgrad, wenn das
  Prüfdatum älter als zwölf Monate ist, dann weist die Seite sichtbar darauf hin,
  dass der Stand veraltet ist (Decision Log #15).
- **AK-47** · Angenommen, die Erklärung beschreibt die Rechtslage, wenn sie gelesen
  wird, dann behauptet sie keine Verpflichtung, der die Plattform nicht unterliegt.

### I · Rückmeldeweg (US-06)

- **AK-48** · Angenommen, jemand stößt auf eine Barriere, wenn er die
  Erklärungsseite öffnet, dann findet er dort ein Formular zum Melden **und** eine
  Kontaktadresse im Klartext.
- **AK-49** · Angenommen, das Meldeformular wird ohne E-Mail-Adresse abgesendet,
  wenn nur die Beschreibung ausgefüllt ist, dann geht die Meldung durch.
- **AK-50** · Angenommen, eine Meldung wurde abgesendet, wenn danach in der
  Datenbank nachgesehen wird, dann ist dort **nichts** davon gespeichert.
- **AK-51** · Angenommen, eine Meldung wurde abgesendet, wenn die Antwort erscheint,
  dann steht eine Bestätigung da, der Fokus springt darauf, und ein Screenreader
  sagt sie an.
- **AK-52** · Angenommen, das Formular wird wiederholt abgesendet, wenn eine
  Obergrenze erreicht ist, dann wird abgelehnt und eine Wartezeit genannt.
- **AK-53** · Angenommen, ein automatisiertes Skript füllt alle Felder aus, wenn es
  dabei das für Menschen unsichtbare Feld mitausfüllt, dann sieht es dieselbe
  Erfolgsmeldung wie sonst, es wird aber nichts versendet.

### J · Nachweis und Regression

- **AK-54** · Angenommen, jemand fügt eine Seite oder ein Formular hinzu, das gegen
  eine der Regeln dieser Spec verstößt, wenn der Prüflauf des Projekts läuft, dann
  schlägt er fehl.
- **AK-55** · Angenommen, ein Prüfdurchgang ist abgeschlossen, wenn sein Ergebnis
  abgelegt wird, dann ist je Kriterium erkennbar, ob es erfüllt, nicht erfüllt oder
  nicht anwendbar ist — und bei „nicht anwendbar" steht die Begründung dabei.

### Datenschutz und Missbrauchsschutz

Der vollständige Katalog steht weiter unten; diese fünf sind seine prüfbaren
Kriterien.

- **AK-56** · Angenommen, jemand beschreibt in seiner Meldung seine Behinderung,
  wenn geprüft wird, wo dieser Text landet, dann ausschließlich im Postfach der
  Kontaktadresse — nicht in der Datenbank, nicht in einem Protokoll, nicht beim
  Fehler-Tracking.
- **AK-57** · Angenommen, der Versand einer Meldung scheitert, wenn danach ins
  Protokoll gesehen wird, dann steht dort weder die Beschreibung noch die Adresse
  des Melders.
- **AK-58** · Angenommen, das Formular wird betrachtet, wenn geprüft wird, was es
  verlangt, dann verlangt es nur die Beschreibung — keinen Namen, kein
  Geburtsdatum, keine Auswahl der Art der Behinderung.
- **AK-59** · Angenommen, die Erklärungsseite wird ohne Anmeldung aufgerufen, wenn
  sie geladen wird, dann ist sie öffentlich erreichbar.
- **AK-60** · Angenommen, das Meldeformular wird abgesendet, wenn der Ursprung der
  Anfrage geprüft wird, dann greift derselbe Schutz gegen fremde Absender wie bei
  jedem anderen Formular des Projekts.

## Der Sicherheitskatalog, Punkt für Punkt

Nach `~/.claude/sdd/sicherheit.md`. Was nicht zutrifft, steht mit Begründung da.

**1 · Personenbezogene Daten.** Betroffen ist ausschließlich das Meldeformular:
Freitext und optional eine E-Mail-Adresse. ⚠ **Der Freitext ist eine besondere
Kategorie nach Art. 9** — wer eine Barriere meldet, beschreibt fast zwangsläufig
seine Behinderung. Für diesen einen Datenstrom gilt Stufe C, obwohl das Projekt
sonst auf B liegt. Deshalb wird nichts gespeichert (AK-50); es gibt keine
Löschfrist, weil es nichts zu löschen gibt. In Protokolle darf nichts davon
(AK-57). Alle übrigen Kriterien dieser Spec ändern Darstellung und Bedienung, nicht
die Datenhaltung.

**2 · Weitergabe an externe Dienste.** Der Meldetext geht an den E-Mail-Versender,
über den das Projekt ohnehin alle Mails schickt — ein weiterer Empfänger entsteht
nicht. ⚠ **Das Fehler-Tracking ist hier der Risikoweg:** Wirft der Versand eine
Ausnahme, darf deren Meldung den Text nicht mittragen (AK-57). Der Vorgang gehört
in `docs/datenschutz.md`, sobald diese Datei existiert (heute fehlt sie, B13/FB-01).

**3 · Zugriff.** Die Erklärungsseite ist öffentlich (AK-59) — hinter einer
Anmeldung wäre sie sinnlos. Es entsteht kein Datensatz, also gibt es weder eine
fremde ID noch eine Rolle, die etwas sehen dürfte oder nicht. Ein IDOR ist
strukturell ausgeschlossen, weil nichts adressierbar abgelegt wird.

**4 · Missbrauch und Kosten.** Das Formular löst Mailversand aus und braucht
deshalb nach der Projektkonvention einen Limiter **im selben Commit** (AK-52), mit
Test-Override — sonst summieren sich die Aufrufe über die Testsuite. Dazu das
Honeypot-Muster ohne Validierungsfehler (AK-53), wie bei den Wartelisten. Uploads
gibt es nicht. Kosten je Aufruf: der Versand einer E-Mail.

**5 · Löschen und Auskunft.** Trifft nicht zu, weil kein Datensatz entsteht. Die
Rechte an bestehenden Konten sind Gegenstand von Feature `01`.

**6 · Geheimnisse.** Trifft nicht zu — das Feature braucht keinen neuen Schlüssel.
Die Empfängeradresse ist ein bereits vorhandener Konfigurationswert, kein Geheimnis.

## Edge Cases

- **EC-01** · JavaScript ist abgeschaltet → Erklärung und Meldeformular bleiben
  vollständig bedienbar, wie die Organisationsformulare es vormachen.
- **EC-02** · Kontrastmodus und 400 % Vergrößerung gleichzeitig → Bedienung bleibt
  möglich, Fokus sichtbar.
- **EC-03** · Ein Restaurantfoto aus dem Bestand hat keinen Alternativtext → es ist
  entschieden und dokumentiert, was bis zum Nachtragen angezeigt wird; ein leeres
  `alt` an einem informationstragenden Bild ist keine Lösung.
- **EC-04** · Der Versand einer Meldung scheitert → der Melder erfährt es und
  verliert seinen eingegebenen Text nicht.
- **EC-05** · Ein Screenreader liest die Kennzahlenseite → jede Grafik hat ihre
  Tabellenentsprechung, die Balken selbst schweigen.
- **EC-06** · Jemand mit Tastatur landet auf einer Seite mit Cookie-Banner → das
  Banner ist erreichbar und verdeckt danach keinen Inhalt dauerhaft.
- **EC-07** · Die Erklärung wird aufgerufen, bevor je eine Prüfung stattfand → sie
  sagt genau das, statt einen Grad zu behaupten.
- **EC-08** · Eine Seite wird in luxemburgischer Vorgabesprache aufgerufen, deren
  Katalog eine Zeile nicht kennt → es erscheint kein Übersetzungsschlüssel als Text;
  dafür sorgt der bestehende Katalogtest.

## Offene Fragen

Alle vier am 2026-08-26 vom Betreiber entschieden (Decision Log #12–#15). Es bleibt
**eine Umsetzungsauflage**, keine offene Frage:

- **UA-01** · Der Endtext der Barrierefreiheitserklärung wird vor der
  Veröffentlichung von jemandem mit luxemburgischer Rechtskenntnis abgenommen. Diese
  Spec legt die Linie fest — freiwillige Selbstverpflichtung, keine behauptete
  Pflicht (AK-47), keine gesetzliche Beschwerdestelle (AK-43) —, ersetzt die Abnahme
  aber nicht. Der Bau darf bis dahin laufen; die Seite geht **nicht** ohne diese
  Abnahme live.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Welcher Normstand | WCAG 2.2 AA, Erklärung nach RAWeb | Obermenge von 2.1 AA; das Projekt zielt an zwei Stellen bereits darauf, und RAWeb ist damit automatisch mit erfüllt |
| 2 | Geltungsbereich | alles, inklusive Verwaltung, App-Hülle und Mails | Ein Ausschluss hätte begründet werden müssen — und die meisten Fundstellen liegen ausgerechnet in der Verwaltung |
| 3 | Wo steht die Erklärung | eigene Seite, vier Sprachen, aus der Fußzeile | Einzeln verlinkbar; genau das fordert eine Gemeinde in der Ausschreibung an |
| 4 | Wie wird geprüft | automatisierter Lauf plus Raster von Hand | Werkzeuge finden etwa ein Drittel; Tastaturweg und Ansagen findet nur ein Mensch |
| 5 | Abnahmeziel | vollständig konform für den eigenen Code | Sonst hinge die Abnahme an Code, der dem Projekt nicht gehört |
| 6 | Fremde Widgets | erst prüfen, dann entscheiden | Funktionierenden Code auf Verdacht zu ersetzen kostet mehr, als die Prüfung kostet |
| 7 | Rückmeldeweg | Formular, Adresse und Beschwerdestelle | Wer kein Mailprogramm eingerichtet hat, braucht das Formular; wer keins ausfüllen mag, die Adresse |
| 8 | Was mit der Meldung geschieht | versenden, nicht speichern | Der Text ist ein Gesundheitsdatum. Was nicht gespeichert ist, kann nicht abfließen — dieselbe Begründung wie bei den Finanzposten |
| 9 | Kontaktangabe | E-Mail freiwillig | Wer seine Behinderung offenlegen muss, um eine Barriere zu melden, meldet sie seltener |
| 10 | Test mit Betroffenen | nicht als Abnahmekriterium | Der stärkste Nachweis, aber er hängt an fremden Terminen; Ort dafür ist der Beirat |
| 11 | Kennzahl auf der Transparenzseite | nein | Dort bedeutet „Barrierefreiheit" die der Restaurants; zwei Bedeutungen in einer Kennzahlenliste sind eine Fehlerquelle |
| 12 | Fremde Widgets, Vorentscheidung (OF-01) | Swagger jetzt endgültig aus dem Geltungsbereich; Küchen-Auswahlfeld und Bildergalerie durchlaufen den Prüflauf, Ersatz nur bei Durchfallen | Swagger ist mitgeliefertes Entwicklerwerkzeug ohne eigenen Code; die beiden anderen tragen echte Funktion und werden nicht auf Verdacht ersetzt (vgl. #6) |
| 13 | Beschwerdestelle (OF-02) | keine gesetzliche Stelle nennen | Als Kleinstanbieter unterliegt die Plattform der Richtlinie für öffentliche Stellen nicht; eine dort zuständige Stelle passt nicht und wäre eine falsche Zuständigkeitsbehauptung. Nur der eigene Rückmeldeweg plus Freiwilligkeitshinweis (AK-43) |
| 14 | Rechtslage im Text (OF-03) | freiwillige Selbstverpflichtung; Endtext juristisch abnehmen (UA-01) | AK-47 verbietet die Behauptung einer Pflicht, die nicht besteht; die Linie steht, die Endabnahme durch jemanden mit luxemburgischer Rechtskenntnis bleibt Auflage |
| 15 | Erneuerungsrhythmus (OF-04) | jährlich, danach sichtbarer Veralterungshinweis | Üblicher Rhythmus für Barrierefreiheitserklärungen; dasselbe Veralterungsmuster wie bei den Finanzzahlen (AK-46) |
