# 06 · Community Feedback Board — Spezifikation

Status: `planned` · Stand: 2026-08-30

## Zweck

Heute gibt es keinen Weg, auf dem ein Nutzer der Plattform sagen kann, was ihm fehlt.
Das Meldeformular auf `/barrierefreiheit` (Feature `02`) nimmt Barrieren **der Website**
entgegen und ist einseitig — niemand außer dem Betreiber sieht die Meldung, und der
Melder erfährt nie, was daraus wurde. Der Wizard auf `/community/suggest` (B11) meldet
**neue Restaurants**, nicht Wünsche an das Produkt. Wer sich einen Filter „Ruhiger
Bereich" oder eine Kartenansicht wünscht, hat keine Adresse dafür.

Nach diesem Feature gibt es unter `/community/ideen` ein öffentliches Board für Ideen
**zur Plattform selbst**: Angemeldete reichen einen Vorschlag ein, das Team gibt ihn
frei, alle können ihn lesen, Angemeldete können zustimmen, und jede Idee trägt einen
Status samt öffentlicher Antwort des Teams — auch dann, wenn die Antwort „nein" lautet.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 · Registrierung & E-Mail-Bestätigung | `approved` | Einreichen und Zustimmen setzen ein bestätigtes Konto voraus (AK-10, AK-11) |
| B02 · Anmeldung | `approved` | die Anmeldeschranke vor beiden Handlungen |
| B19 · Admin-Zugang & Dashboard | `approved` | Freigabe, Status und Team-Antwort laufen über die Verwaltung; der Zähler wartender Ideen gehört ins Dashboard (AK-25) |
| B24 · Mehrsprachigkeit | `approved` | vier Sprachfassungen des Rahmens, Sprachkennzeichnung des Beitragstexts (AK-40 bis AK-43) |
| B21 · Vorschläge prüfen | `approved` | liefert das Muster für eine Warteschlange mit Freigabe und Ablehnung |
| 01 · Betroffenenrechte | live seit v2026.08.29 | Kontolöschung und Datenexport müssen das Board mitnehmen (AK-65 bis AK-68). **Der Export wird dafür erweitert** — das ist eine Änderung an ausgeliefertem Code |
| 02 · Barrierefreiheit der Plattform | `deployed` | gilt für jede neue Seite; der Prüflauf muss die neuen Adressen kennen (AK-44 bis AK-49) |

## User Stories

- **US-01** · Als Nutzerin möchte ich einen Verbesserungsvorschlag zur Plattform
  hinterlassen, damit er nicht in einer E-Mail verschwindet, die niemand wiederfindet.
- **US-02** · Als Besucher möchte ich sehen, was andere sich wünschen, damit ich nicht
  zum fünften Mal dieselbe Idee einreiche.
- **US-03** · Als angemeldeter Nutzer möchte ich einer fremden Idee zustimmen können,
  damit das Team erkennt, was vielen wichtig ist — ohne dass ich selbst etwas schreiben
  muss.
- **US-04** · Als Einreicher möchte ich erfahren, was aus meiner Idee geworden ist,
  damit ich nicht monatelang rate.
- **US-05** · Als Betreiber möchte ich vor der Veröffentlichung über jeden Beitrag
  sehen, damit unter dem Namen Endlech.lu nichts steht, das ich nicht verantworte.
- **US-06** · Als Betreiber möchte ich eine Ablehnung öffentlich begründen müssen,
  damit das Prinzip „Lücken werden gezeigt, nicht versteckt" auch für meine eigenen
  Entscheidungen gilt.

## Nicht im Scope

- **Kommentare unter einer Idee.** Ausdrücklich zurückgestellt: Jeder Kommentar
  durchliefe dieselbe Freigabe wie eine Idee und verdoppelte die Moderationslast bei
  einer Person im Betrieb. Gehört in ein Folge-Feature, falls das Board getragen wird.
- **Bewertungen und Kommentare zu einzelnen Restaurants.** Das ist der eigene
  Roadmap-Punkt aus `docs/prd.md` („Ein Kernversprechen ist nicht eingelöst") und
  betrifft `Restaurant`, nicht die Plattform. Zwei verschiedene Produkte.
- **Korrekturhinweise zu bestehenden Restaurantdaten** („hier stimmt etwas nicht").
  Eigenes Feature — das Ergebnis ist eine Datenänderung, keine Meinungsäußerung, und
  es hängt an B20/B21 statt an diesem Board.
- **Dateianhänge, Bilder, Screenshots** an einer Idee. Ein öffentlich ausgeliefertes
  Upload-Verzeichnis mit ungeprüften Fremddateien ist eine eigene Risikofläche (AK-63).
- **Zustimmen ohne Konto.** In der ersten Interviewrunde vorgesehen, in der zweiten
  verworfen — siehe Decision Log 3.
- **Eine eigene Moderatorenrolle.** Das Projekt hat genau ein Admin-Konto (B19/FB-01);
  eine zweite Rolle wäre Struktur ohne Träger.
- **Benachrichtigung bei jedem Statuswechsel.** Bewusst nicht gewählt (Decision Log 8).

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar.

### A · Board finden und lesen

- **AK-01** · Angenommen, ein Besucher ist nicht angemeldet, wenn er
  `/de/community/ideen` aufruft, dann erscheint das Board mit HTTP 200 und ohne
  Aufforderung, sich anzumelden.
- **AK-02** · Angenommen, ein Besucher steht auf einer beliebigen Seite außerhalb der
  Verwaltung, wenn er die Fußzeile liest, dann findet er dort einen Verweis auf das
  Board, ohne die Adresse zu kennen.
- **AK-03** · Angenommen, es liegen drei freigegebene und zwei wartende Ideen vor, wenn
  das Board geöffnet wird, dann erscheinen genau die drei freigegebenen und keine der
  wartenden.
- **AK-04** · Angenommen, eine freigegebene Idee liegt vor, wenn sie im Board erscheint,
  dann sind Titel, Anzeigename des Verfassers, Einreichdatum, Zahl der Zustimmungen und
  der Status **als Text** sichtbar.
- **AK-05** · Angenommen, Idee A hat 12 Zustimmungen und Idee B hat 3, wenn das Board
  ohne Sortierparameter geöffnet wird, dann steht A über B; bei gleicher Zahl steht die
  neuere oben. Eine Umschaltung auf „neueste zuerst" ist vorhanden und wirkt.
- **AK-06** · Angenommen, Ideen mit vier verschiedenen Status liegen vor, wenn nach
  `geplant` gefiltert wird, dann erscheinen ausschließlich Ideen mit diesem Status, und
  die aktive Filterung ist an der Seite ablesbar.
- **AK-07** · Angenommen, 45 freigegebene Ideen liegen vor, wenn das Board geöffnet
  wird, dann erscheinen höchstens 20 und eine Blätterung; **kein Aufruf lädt alle 45**.
- **AK-08** · Angenommen, es gibt keine einzige freigegebene Idee, wenn das Board
  geöffnet wird, dann erscheint ein erklärender leerer Zustand mit einem Weg zum
  Einreichformular — keine leere Liste und keine Fehlerseite.
- **AK-09** · Angenommen, eine freigegebene Idee liegt vor, wenn ihre Einzeladresse
  geöffnet wird, dann erscheinen der vollständige Beschreibungstext, der Status und —
  falls vorhanden — die Antwort des Teams.

### B · Idee einreichen

- **AK-10** · Angenommen, ein Besucher ist nicht angemeldet, wenn er das
  Einreichformular aufruft, dann wird er zur Anmeldung geführt und **es entsteht kein
  Datensatz**.
- **AK-11** · Angenommen, ein angemeldetes Konto hat seine E-Mail-Adresse nicht
  bestätigt, wenn es eine Idee absenden will, dann erscheint ein Hinweis auf die
  ausstehende Bestätigung und **es entsteht kein Datensatz**.
- **AK-12** · Angenommen, das Formular ist leer, wenn es abgesendet wird, dann erscheint
  **je Pflichtfeld eine eigene Meldung** am Feld, die Antwort ist HTTP 422, und es
  entsteht kein Datensatz.
- **AK-13** · Angenommen, der Titel ist 121 Zeichen lang, wenn abgesendet wird, dann
  erscheint eine Meldung zur Länge — **kein Serverfehler**.
- **AK-14** · Angenommen, der Beschreibungstext ist 2001 Zeichen lang, wenn abgesendet
  wird, dann erscheint eine Meldung zur Länge und es entsteht kein Datensatz.
- **AK-15** · Angenommen, ein bestätigtes Konto füllt das Formular gültig aus, wenn es
  absendet, dann erscheint eine Bestätigungsseite mit dem Hinweis, dass die Idee auf
  Freigabe wartet, der Datensatz steht auf `wartet`, und **die Idee erscheint nicht im
  Board**.
- **AK-16** · Angenommen, das Einreichformular ist geöffnet, wenn es gelesen wird, dann
  steht **vor** dem Absendeknopf, dass Titel und Beschreibung nach Freigabe öffentlich
  sichtbar werden und dass dort keine Gesundheits- oder Kontaktangaben stehen sollen.
- **AK-17** · Angenommen, das versteckte Fallenfeld ist gefüllt, wenn abgesendet wird,
  dann erscheint **dieselbe** Bestätigungsseite wie im Gutfall, aber es entsteht kein
  Datensatz und es wird keine Mail verschickt.
- **AK-18** · Angenommen, eine Idee wartet auf Freigabe, wenn ihr Verfasser ihre
  Einzeladresse aufruft, dann sieht er sie mit dem Hinweis „wartet auf Freigabe"; ruft
  ein anderes Konto oder ein Gast dieselbe Adresse auf, erscheint **404**.

### C · Zustimmen

- **AK-19** · Angenommen, ein angemeldetes Konto hat einer Idee mit 7 Zustimmungen noch
  nicht zugestimmt, wenn es zustimmt, dann steht dort 8 und der Knopf zeigt den Zustand
  „zugestimmt".
- **AK-20** · Angenommen, dasselbe Konto hat bereits zugestimmt, wenn es die Handlung
  ein zweites Mal auslöst, dann bleibt die Zahl unverändert bei 8.
- **AK-21** · Angenommen, ein Konto hat zugestimmt, wenn es die Zustimmung zurücknimmt,
  dann steht die Zahl wieder auf 7 und der Knopf zeigt den Ausgangszustand.
- **AK-22** · Angenommen, ein Gast liest das Board, wenn er zustimmen will, dann wird er
  zur Anmeldung geführt und die Zahl bleibt unverändert.
- **AK-23** · Angenommen, JavaScript ist im Browser abgeschaltet, wenn ein angemeldetes
  Konto zustimmt, dann wirkt die Zustimmung trotzdem und die Seite zeigt den neuen Stand.

### D · Freigabe und Moderation

- **AK-24** · Angenommen, fünf Ideen warten, wenn ein Admin die Warteschlange öffnet,
  dann stehen alle fünf dort, älteste zuerst, mit vollständigem Text.
- **AK-25** · Angenommen, drei Ideen warten auf Freigabe, wenn ein Admin das Dashboard
  öffnet, dann steht dort die Zahl **3** als eigener Posten — so, wie es die wartenden
  Restaurantvorschläge bereits tun.
- **AK-26** · Angenommen, eine Idee wartet, wenn ein Admin sie freigibt, dann erscheint
  sie unmittelbar danach im öffentlichen Board.
- **AK-27** · Angenommen, ein Admin will eine Idee ablehnen und hat **keine** Begründung
  eingetragen, wenn er die Ablehnung auslöst, dann wird sie **nicht** ausgeführt und es
  erscheint eine Meldung, dass eine öffentliche Begründung erforderlich ist.
- **AK-28** · Angenommen, ein Admin lehnt mit Begründung ab, wenn danach das Board
  geöffnet wird, dann steht die Idee dort mit dem Status `abgelehnt` **und** der
  Begründung — sie verschwindet nicht.
- **AK-29** · Angenommen, ein angemeldetes Konto ohne Adminrechte ruft eine
  Verwaltungsadresse des Boards auf, wenn die Antwort kommt, dann ist sie **403**; ein
  nicht angemeldeter Aufruf führt zur Anmeldung. In keinem Fall erscheint der Inhalt.
- **AK-30** · Angenommen, eine wartende Einreichung ist erkennbar Spam, wenn ein Admin
  sie endgültig löscht, dann ist sie aus der Warteschlange verschwunden, war nie
  öffentlich, und es wurde keine Mail verschickt.

### E · Status und Antwort des Teams

- **AK-31** · Angenommen, eine Idee ist öffentlich, wenn ihr Status betrachtet wird,
  dann trägt sie genau einen aus: `neu`, `in Prüfung`, `geplant`, `umgesetzt`,
  `abgelehnt` — jeweils als lesbares Wort, nicht nur als Farbe oder Zeichen.
- **AK-32** · Angenommen, ein Admin hinterlegt eine Antwort des Teams, wenn die Idee
  geöffnet wird, dann steht die Antwort dort und ist als Antwort des Teams
  gekennzeichnet, unterscheidbar vom Text des Einreichers.
- **AK-33** · Angenommen, eine Idee wechselt von `neu` auf `geplant`, wenn der Wechsel
  gespeichert wird, dann ist der neue Status öffentlich sichtbar und **es wird keine
  weitere E-Mail verschickt**.

### F · Dubletten

- **AK-34** · Angenommen, Idee B ist eine Dublette von Idee A (A hat 5, B hat 2
  Zustimmungen), wenn ein Admin B als Dublette von A markiert, dann hat A danach die
  Zustimmungen beider — Konten, die für **beide** gestimmt hatten, zählen **einmal**.
- **AK-35** · Angenommen, B wurde als Dublette zusammengeführt, wenn die Einzeladresse
  von B aufgerufen wird, dann führt sie auf A; B erscheint nicht mehr in der Liste.
- **AK-36** · Angenommen, B war noch nicht freigegeben, als sie zusammengeführt wurde,
  wenn der Verfasser von B benachrichtigt wird, dann zeigt der Link in seiner Mail auf
  **A**, nicht auf eine Adresse ohne Inhalt.

### G · Benachrichtigung

- **AK-37** · Angenommen, eine Idee wird zum ersten Mal öffentlich sichtbar — gleich mit
  welchem Status, `abgelehnt` eingeschlossen —, wenn das geschieht, dann erhält ihr
  Verfasser **genau eine** E-Mail mit Titel und Link, in der Sprache, in der er die Idee
  eingereicht hat.
- **AK-38** · Angenommen, eine Idee ist bereits öffentlich, wenn sich Status oder
  Antwort des Teams später ändern, dann wird **keine** weitere E-Mail verschickt.
- **AK-39** · Angenommen, der Mailversand scheitert, wenn die Freigabe ausgelöst wurde,
  dann bleibt die Idee trotzdem freigegeben und öffentlich — die Zustellung darf die
  Veröffentlichung nicht rückgängig machen.

### H · Mehrsprachigkeit

- **AK-40** · Angenommen, das Board wird unter `/de`, `/en`, `/fr` und `/lb` geöffnet,
  wenn die Seite geladen ist, dann sind Überschriften, Status, Knöpfe und Hilfetexte in
  der jeweiligen Sprache — kein sichtbarer Übersetzungsschlüssel.
- **AK-41** · Angenommen, eine Idee wurde auf Luxemburgisch eingereicht, wenn das Board
  unter `/fr` geöffnet wird, dann erscheint ihr Text **unverändert** und ist als
  luxemburgischer Beitrag gekennzeichnet.
- **AK-42** · Angenommen, eine Idee wird unter `/fr` eingereicht, wenn sie gespeichert
  wird, dann ist `fr` als ihre Sprache festgehalten und bestimmt später die Sprache der
  Mail aus AK-37.
- **AK-43** · Angenommen, das Feature bringt neue Übersetzungsschlüssel mit, wenn der
  Katalogprüflauf läuft, dann sind alle vier Kataloge vollständig und kein Wert ist leer.

### I · Barrierefreiheit

Feature `02` ist ausgeliefert; seine Zusage gilt für jede neue Seite.

- **AK-44** · Angenommen, ein Konto hat zugestimmt, wenn der Zustand des Knopfes
  ermittelt wird, dann ist er **nicht allein an der Farbe** erkennbar, sondern zusätzlich
  an Text oder Beschriftung, und ein Screenreader liest den Zustand vor.
- **AK-45** · Angenommen, eine Idee trägt einen Status, wenn das Abzeichen betrachtet
  wird, dann ist der Status auch ohne Farbwahrnehmung lesbar.
- **AK-46** · Angenommen, das Fenster ist 320 px breit, wenn Board, Einzelansicht und
  Formular geöffnet werden, dann entsteht auf **keiner** der drei Seiten eine waagerechte
  Bildlaufleiste.
- **AK-47** · Angenommen, nur die Tastatur wird benutzt, wenn Board und Formular bedient
  werden, dann sind alle Bedienelemente erreichbar, haben einen sichtbaren Fokus und
  messen mindestens 44 × 44 px.
- **AK-48** · Angenommen, das Formular wird fehlerhaft abgesendet, wenn die 422-Antwort
  erscheint, dann ist jede Meldung ihrem Feld zugeordnet, die Felder sind als fehlerhaft
  ausgezeichnet, und der Fokus steht **ohne JavaScript** im ersten fehlerhaften Feld.
- **AK-49** · Angenommen, JavaScript ist abgeschaltet, wenn Board, Filter, Sortierung,
  Einreichen und Zustimmen benutzt werden, dann funktionieren alle fünf.

### Datenschutz und Missbrauchsschutz

Nach `~/.claude/sdd/sicherheit.md`, Abschnitt für Abschnitt.

**1 · Personenbezogene Daten.** Betroffen sind der Verfasserbezug (Konto), der
Anzeigename und **der Freitext**. Der Freitext ist der kritische Teil: Auf einer
Barrierefreiheitsplattform schreibt jemand mit hoher Wahrscheinlichkeit „Ich bin auf
einen Rollstuhl angewiesen und wünsche mir …" — das ist eine Gesundheitsangabe und damit
eine besondere Kategorie nach Art. 9 DSGVO. Dieselbe Erwägung führte in `04` dazu, die
Freitextnachricht der Wartelisten von der Brevo-Übertragung auszunehmen. Hier lässt sie
sich nicht vermeiden (der Text **ist** das Produkt), also wird sie benannt (AK-16) und
eingegrenzt (AK-53, AK-54).

- **AK-50** · Angenommen, eine Idee ist öffentlich, wenn die Seite und ihr Quelltext
  gelesen werden, dann erscheinen dort **weder die E-Mail-Adresse noch der vollständige
  Name** des Verfassers.
- **AK-51** · Angenommen, das Konto führt den Namen „Anna Katharina Berg", wenn seine
  Idee öffentlich erscheint, dann steht dort „Anna B."; führt es nur „Anna", steht dort
  „Anna"; führt es **keinen** Namen, steht dort die übersetzte Bezeichnung für einen
  Beitrag ohne Namen.
- **AK-52** · Angenommen, eine Idee wird eingereicht, wenn danach die Anwendungslogs
  gelesen werden, dann steht dort **weder der Beitragstext noch eine E-Mail-Adresse**.

**2 · Weitergabe an externe Dienste.** Es kommt kein neuer Dienst hinzu. Berührt sind
Brevo (Versand der einen Mail aus AK-37, Zweck 1 nach `docs/datenschutz.md`) und Sentry.

- **AK-53** · Angenommen, eine Idee wird eingereicht und freigegeben, wenn danach der
  Marketing-Kontaktbestand aus Feature `04` geprüft wird, dann ist durch das Board
  **kein** Kontakt hinzugekommen und keine Einwilligung entstanden.
- **AK-54** · Angenommen, eine Idee enthält eine Gesundheitsangabe, wenn der Versand
  geprüft wird, dann geht der Text an **keinen anderen Empfänger als den Verfasser
  selbst** — insbesondere an keine Sammel- oder Betreiberadresse.
- **AK-55** · Angenommen, beim Speichern einer Idee tritt ein Fehler auf, wenn der
  Fehlerbericht betrachtet wird, dann enthält er **keinen Beitragstext und keine
  Funktionsargumente**.

**3 · Zugriff.** Lesen: öffentlich, aber nur Freigegebenes. Einreichen und Zustimmen:
bestätigtes Konto. Freigeben, Status, Antwort, Zusammenführen, Löschen: `ROLE_ADMIN`.
Eine eigene Moderatorenrolle gibt es nicht. Erzwungen wird das serverseitig, nicht durch
ausgeblendete Schaltflächen.

- **AK-56** · Angenommen, Konto X hat eine wartende Idee, wenn Konto Y deren Adresse
  errät und aufruft, dann erscheint **404** — nicht der Inhalt und nicht 403 mit Titel.
- **AK-57** · Angenommen, ein Konto ohne Adminrechte kennt die Adresse zum Freigeben,
  wenn es sie mit gültigem Token aufruft, dann ist die Antwort **403** und der Status der
  Idee bleibt unverändert.
- **AK-58** · Angenommen, ein angemeldetes Konto sendet eine Zustimmung mit der
  Kennung eines fremden Kontos, wenn sie verarbeitet wird, dann wird sie dem **eigenen**
  Konto zugerechnet oder abgewiesen — nie dem fremden.

**4 · Missbrauch und Kosten.** Beide schreibenden Wege setzen ein Konto voraus, der
Deckel zählt deshalb **am Konto** und nicht an der IP — dort wechselt der Angreifer
mühelos, das Konto nicht (Projektkonvention, siehe `password_change`).

- **AK-59** · Angenommen, ein Konto hat in der letzten Stunde fünf Ideen eingereicht,
  wenn es eine sechste absendet, dann erscheint eine Meldung mit der Wartezeit und **es
  entsteht kein Datensatz**.
- **AK-60** · Angenommen, ein Konto hat in der letzten Stunde 60 Zustimmungen abgegeben,
  wenn es eine weitere abgibt, dann wird sie abgewiesen und keine Zahl verändert sich.
- **AK-61** · Angenommen, dasselbe Konto wechselt die IP-Adresse, wenn es danach die
  sechste Idee einreicht, dann greift der Deckel **trotzdem**.
- **AK-62** · Angenommen, ein Konto sendet fünfmal ein **ungültiges** Formular ab, wenn
  es danach eine gültige Idee einreicht, dann wird sie angenommen — der Deckel zählt
  stattgefundene Einreichungen, nicht Tippfehler.
- **AK-63** · Angenommen, das Einreichformular wird untersucht, wenn nach einem
  Dateifeld gesucht wird, dann gibt es keins, und ein untergeschobener Datei-Upload wird
  abgewiesen.
- **AK-64** · Angenommen, ein Beitragstext enthält `https://beispiel.tld`, wenn die Idee
  öffentlich erscheint, dann steht die Zeichenfolge als **Text** dort und **nicht** als
  anklickbarer Verweis.

**5 · Löschen und Auskunft.** Maßgeblich ist der Präzedenzfall aus Feature `01`
(AK-05 dort): Restaurants bleiben, `submittedBy` wird `NULL` — „die Person verschwindet,
die Auskunft bleibt". Für eine Idee gilt dasselbe, weil andere für sie gestimmt haben und
das Team öffentlich geantwortet hat. Für eine **Zustimmung** gilt es nicht: Sie ist eine
persönliche Handlung ohne eigenen Aussagewert.

- **AK-65** · Angenommen, ein Konto mit einer öffentlichen Idee wird gelöscht, wenn
  danach das Board geöffnet wird, dann steht die Idee samt Team-Antwort weiter dort, aber
  ohne Anzeigenamen — und in der Datenbank ist kein Verfasserbezug mehr gesetzt.
- **AK-66** · Angenommen, dieses Konto hatte drei Zustimmungen abgegeben, wenn es
  gelöscht wird, dann sinken die drei Zählstände jeweils um eins und keine Zeile verweist
  mehr auf das Konto.
- **AK-67** · Angenommen, ein Konto fordert seinen Datenexport an (Feature `01`), wenn
  die Datei geöffnet wird, dann enthält sie seine eingereichten Ideen samt Status **und**
  die Ideen, denen es zugestimmt hat.
- **AK-68** · Angenommen, die Löschung ist erfolgt, wenn Board, Einzelansicht, Export
  und Verwaltung durchsucht werden, dann führt von keiner Idee ein Weg zurück auf Name
  oder E-Mail-Adresse der gelöschten Person.

**6 · Geheimnisse.** *Trifft nicht zu, weil* das Feature keinen neuen Schlüssel und
keinen neuen Dienst braucht. Es nutzt den vorhandenen Mailversand; nichts geht zum
Client, was nicht ohnehin öffentlich ist.

### Abnahme

- **AK-69** · Angenommen, ein frisches bestätigtes Konto liegt vor, wenn der volle
  Durchlauf gegangen wird — einreichen, Freigabe im Admin, Mail beim Verfasser, Idee im
  Board, Zustimmung durch ein zweites Konto, Statuswechsel auf `geplant`, Antwort des
  Teams —, dann ist jeder Schritt ohne Umweg über die Datenbank sichtbar.
- **AK-70** · Angenommen, alle Änderungen liegen vor, wenn `php bin/phpunit` läuft, dann
  ist der Lauf grün, einschließlich Katalogvollständigkeit und Limiter-Abdeckung.
- **AK-71** · Angenommen, das Board läuft, wenn zu einem beliebigen Zeitpunkt geprüft
  wird, dann war **kein** Beitrag jemals ohne Freigabe öffentlich abrufbar — weder über
  das Board, noch über eine Einzeladresse, noch über eine Sortierung oder einen Filter.

### L · Nachgetragen aus den entschiedenen offenen Fragen (2026-08-30)

Alle fünf offenen Fragen wurden am 2026-08-30 entschieden. Jede Entscheidung bekommt hier
ein Kriterium — eine entschiedene Frage ohne Kriterium wird später nicht geprüft. Genau
das passierte in Feature `03`, wo OF-01 und OF-03 entschieden, aber ohne AK waren und erst
beim Aufgabenplan als AK-31/AK-32 auffielen.

- **AK-72** *(aus OF-01)* · Angenommen, ein Nutzer öffnet das Einreichformular oder die
  Seite, die nach dem Absenden erscheint, wenn er sie liest, dann steht **an beiden
  Stellen** die Zusage, dass eine Idee in der Regel innerhalb von **fünf Werktagen**
  geprüft und freigegeben wird.
- **AK-73** *(aus OF-02, am 2026-08-30 durch OF-08 geschärft)* · Angenommen, eine
  Einreichung wartet seit **drei Werktagen** auf Freigabe, wenn ein Admin die
  Warteschlange öffnet, dann ist sie dort **als bald fällig gekennzeichnet** — nicht bloß
  nach Datum einsortiert. *(Werktag = Montag bis Freitag. Feiertage werden **nicht**
  gerechnet; eine Feiertagstabelle für Luxemburg wäre eigene Mechanik ohne Gegenwert an
  dieser Stelle.)*
- **AK-74** *(aus OF-02)* · Angenommen, eine Einreichung liegt seit zwölf Monaten und
  einem Tag vor und wurde nie freigegeben, wenn danach Warteschlange und Datenbank geprüft
  werden, dann ist sie **gelöscht**.
- **AK-75** *(aus OF-03)* · Angenommen, fünf Ideen sind offen und zwei stehen auf
  `umgesetzt`, wenn das Board ohne Filter geöffnet wird, dann stehen die fünf in der
  Hauptliste und die zwei in einem eigenen, überschriebenen Abschnitt **darunter** —
  sichtbar, aber nicht in der Hauptliste und nicht in deren Sortierung nach AK-05.
- **AK-76** *(aus OF-05)* · Angenommen, eine eigene Idee wartet auf Freigabe, wenn ihr
  Verfasser sie zurückzieht, dann verschwindet sie aus der Warteschlange, ist in der
  Datenbank nicht mehr vorhanden, und es wird keine Mail verschickt.
- **AK-77** *(aus OF-05)* · Angenommen, eine eigene Idee ist bereits öffentlich, wenn ihr
  Verfasser sie zurückziehen will, dann gibt es dafür **keinen Weg** in der Oberfläche,
  und ein von Hand nachgebauter Aufruf wird abgewiesen, ohne die Idee zu verändern.
- **AK-78** *(aus OF-04)* · Angenommen, das Feature ist ausgeliefert, wenn
  `docs/datenschutz.md` gelesen wird, dann führt es Stufe B als **bestätigt** — nicht als
  Annahme —, mit Begründung und Datum, und nennt das Board als eigene Verarbeitung samt
  Rechtsgrundlage und Löschfrist aus AK-74.
  *(Dokumentationskriterium, kein Code. Nachweis in `qa-report.md` als Zitat der Stelle —
  so, wie eine Plattformeinstellung als Screenshot nachgewiesen wird.)*

### M · Nachgetragen aus OF-07 und OF-08 (2026-08-30, aus dem Systementwurf)

- **AK-79** *(aus OF-08)* · Angenommen, eine Einreichung wartet seit **fünf Werktagen**
  auf Freigabe — die in AK-72 zugesagte Frist ist damit erreicht —, wenn ein Admin die
  Warteschlange öffnet, dann ist sie dort **deutlicher hervorgehoben als eine bald
  fällige** aus AK-73, und die beiden Stufen sind ohne Farbwahrnehmung unterscheidbar.
- **AK-80** *(aus OF-07)* · Angenommen, ein Besucher liest die Fußzeile, wenn er nach
  einem Weg für Rückmeldungen sucht, dann findet er dort **genau einen** und dieser führt
  auf `/community/ideen` — kein zweiter Verweis auf ein fremdes Board, und keiner mit
  `target="_blank"`.
- **AK-81** *(aus OF-07)* · Angenommen, das Feature ist ausgeliefert, wenn
  `endlech.userjot.com` aufgerufen wird, dann nimmt der Dienst **keine neuen Einreichungen
  mehr entgegen**.
  *(Betriebskriterium beim Anbieter, kein Code. Nachweis in `qa-report.md` als Abruf.)*
- **AK-82** *(aus OF-07)* · Angenommen, die sieben Einträge des externen Boards werden
  nicht übernommen, wenn danach `docs/prd.md` gelesen wird, dann stehen die dort noch
  nicht erfassten Vorhaben in der Roadmap — namentlich **„Chat-Widget"** und
  **„KI-Filter"**, die im PRD bis heute nirgends vorkommen.
  *(Dokumentationskriterium, kein Code.)*

## Edge Cases

- **EC-01** · Das Konto führt keinen Namen (`name` ist leer) → Anzeige als Beitrag ohne
  Namen, kein leerer Punkt und kein „ ." (AK-51).
- **EC-02** · Der Name besteht aus einem einzigen sehr langen Wort ohne Leerzeichen →
  wird auf 30 Zeichen gekürzt, die Zeile bricht nicht aus dem Kasten.
- **EC-03** · Titel aus 120 × „ß" oder 120 × „日" → Wird aus dem Titel eine Adresse
  gebildet, greift die Ausdehnung durch den Slugger (aus „ß" wird „ss", aus einem
  japanischen Zeichen bis zu drei Buchstaben). Die Prüfung am Eingang allein reicht nicht;
  es darf **kein** Datenbankfehler entstehen (Projektkonvention „Die Prüfung gehört
  dorthin, wo der Wert hereinkommt").
- **EC-04** · Ein Konto stimmt zu und löscht sich im selben Moment → die Zählung bleibt
  widerspruchsfrei, kein verwaister Verweis.
- **EC-05** · Zwei Admin-Fenster geben dieselbe wartende Idee gleichzeitig frei → sie
  wird einmal öffentlich und der Verfasser bekommt **eine** Mail, nicht zwei.
- **EC-06** · Eine Idee wird als Dublette zusammengeführt, während ein Nutzer ihr gerade
  zustimmt → die Stimme landet am Original oder wird verworfen, in keinem Fall an einer
  Idee, die niemand mehr sieht.
- **EC-07** · Der Mailversand liegt still (Worker steht) → die Freigabe wirkt trotzdem
  (AK-39); die Nachricht bleibt in der Warteschlange und wird nachgeholt.
- **EC-08** · Der Beitragstext enthält HTML oder ein `<script>` → erscheint als Text,
  wird nie ausgeführt.
- **EC-09** · Ein Konto reicht eine Idee ein und wird gelöscht, bevor die Freigabe
  erfolgt → die wartende Idee verschwindet mit dem Konto; es geht keine Mail an eine
  gelöschte Adresse.
- **EC-10** · Das Board wird gedruckt (die Druckansicht gilt projektweit) → Kopf, Fuß,
  Bottom-Navigation und Cookie-Banner fehlen, die Zustimmungszahlen bleiben lesbar.
- **EC-11** · Der Verfasser zieht seine Idee zurück, während ein Admin sie im anderen
  Fenster gerade freigibt → eines von beidem gewinnt, aber die Idee steht danach **nicht**
  öffentlich mit einem Verfasser, der sie zurückgezogen hat, und es geht keine Mail zu
  einer Idee, die es nicht mehr gibt (AK-76, AK-37).
- **EC-12** · Eine Einreichung erreicht die Zwölf-Monats-Grenze aus AK-74 an einem Tag, an
  dem der Aufräumlauf nicht läuft → sie wird beim nächsten Lauf gelöscht; die Frist ist
  eine Höchstdauer, keine Löschung auf die Minute.

## Offene Fragen

**Alle acht am 2026-08-30 entschieden** — fünf beim Schreiben der Spec, drei beim
Systementwurf desselben Tages. Die Nummern bleiben stehen, damit Verweise aus
`design.md`, `tasks.md` und `qa-report.md` gültig bleiben. Jede Entscheidung hat ein
Kriterium in Abschnitt L bekommen.

- **OF-01** · ~~Gilt eine zugesagte Bearbeitungszeit, und steht sie öffentlich am
  Formular?~~ → **Entschieden: ja, fünf Werktage, öffentlich an beiden Stellen (AK-72).**
  Bewusst nicht die zwei Werktage des Pressekontakts (`05`/OF-03): Ein Board ist weniger
  dringend als eine Presseanfrage, und eine großzügige Zusage ist besser als eine, die im
  Urlaub bricht. Die interne Meldung bleibt damit ausdrücklich weg — Decision Log 8 steht.
- **OF-02** · ~~Löschfrist für nie freigegebene Einreichungen?~~ → **Entschieden: zwölf
  Monate Höchstdauer (AK-74), Hervorhebung in der Warteschlange ab 30 Tagen (AK-73).**
  Damit ist das dreimal aufgetretene Muster hier zum ersten Mal aufgelöst statt vertagt.
  ⚠ **Bei B14/FB-02 und `04`/OF-06 bleibt es offen** — dieselbe Frage, andere Tabelle;
  dass sie hier beantwortet ist, beantwortet sie dort nicht.
- **OF-03** · ~~Was geschieht mit Ideen auf `umgesetzt`?~~ → **Entschieden: eigener
  Abschnitt „Schon umgesetzt" unter der Hauptliste (AK-75).** Der Beleg dafür, dass
  zugehört wird, ist der eigentliche Grund, ein Board zu betreiben — er gehört sichtbar,
  aber nicht in die Liste der offenen Wünsche.
- **OF-04** · ~~Datenschutzstufe des Projekts?~~ → **Entschieden: Stufe B, bestätigt
  (AK-78).** Begründung: Die Plattform **erhebt** keine Gesundheitsdaten — sie erhebt
  Daten über Restaurants; Konten führen Name, E-Mail und Avatar. Eine Angabe nach Art. 9
  kann ausschließlich unaufgefordert im Freitext erscheinen, und dafür greifen AK-16
  (Hinweis vor dem Absenden), AK-52 (nicht ins Log) und AK-54 (an keinen Dritten).
  ⚠ **Die Bestätigung gehört nach `docs/datenschutz.md`** — dort steht sie bis heute als
  unbestätigte Annahme. Das ist der Grund für AK-78; `sdd-spec` schreibt keine Datei
  außerhalb des Feature-Ordners.
- **OF-05** · ~~Darf ein Einreicher seine wartende Idee zurückziehen?~~ → **Entschieden:
  ja, bis zur Freigabe (AK-76); danach nicht mehr (AK-77).** Nach der Veröffentlichung
  haben andere zugestimmt und das Team hat geantwortet — dieselbe Erwägung wie bei der
  Kontolöschung (Decision Log 11).

**Neu offen aus diesen Entscheidungen:**

- **OF-06** · ~~Wer führt den Aufräumlauf aus AK-74 aus?~~ → **Entschieden im
  Systementwurf (Technische Entscheidung 6): er hängt an keinem neuen Cron.** Der Lauf
  gibt es als Befehl `app:board:cleanup` **und** er wird faul beim Öffnen der
  Warteschlange angestoßen, höchstens einmal je Tag. Grund: Auf Produktion fehlen zwei von
  drei Cron-Einträgen; ein dritter, der von einer Einrichtung auf dem Server abhängt,
  fehlte mit hoher Wahrscheinlichkeit auch. Der Befehl bleibt für den Tag, an dem der Cron
  steht.

**Aus dem Systementwurf vom 2026-08-30 (`design.md`) — beide am selben Tag entschieden:**

- **OF-07** · ~~Es gibt bereits ein Ideen-Board — extern. Was geschieht mit dem Bestand?~~
  → **Entschieden, nachdem nachgesehen wurde.** Auf `endlech.userjot.com` liegen **sieben
  Einträge, alle vom Betreiber selbst, alle mit null Stimmen** (Presskit, iOS app, Android
  App, Google Login, Apple Login, Chat widget, AI filter; abgerufen 2026-08-30). **Es gibt
  keinen fremden Nutzerbestand** — keine Verfasser zuzuordnen, keine Stimmen zu retten.
  Damit:
  - **Das Board startet leer** (AK-08). Die sieben sind Roadmap-Notizen des Betreibers,
    keine Community-Beiträge; auf einem Board, das die Frage „was wünschen sich die
    Nutzer" beantworten soll, hätten sie nur den Namen des Betreibers siebenmal gezeigt.
  - **Die Titel wandern in die PRD-Roadmap** (AK-82). „Chat-Widget" und „KI-Filter" stehen
    im PRD bis heute nirgends — die Überführung schließt eine echte Lücke.
  - **Der Fußzeilenverweis wird ersetzt, nicht ergänzt** (AK-02, geschärft durch AK-80).
  - **Der externe Dienst wird nach dem Deploy abgeschaltet** (AK-81). Ein nicht mehr
    verlinktes Board bleibt über Suchmaschinen und Lesezeichen auffindbar und sammelt
    Beiträge, die niemand liest — genau die Sackgasse, die dieses Feature beheben soll.
    Dass „Presskit" dort noch auf *In Progress* steht, während Feature `05` seit
    `v2026.08.30.1` live ist, belegt, dass es ohnehin nicht gepflegt wird.
- **OF-08** · ~~Ab wann gilt eine Einreichung als überfällig?~~ → **Entschieden:
  zweistufig — Hinweis ab drei Werktagen (AK-73), deutliche Warnung ab fünf (AK-79).**
  Die erste Stufe warnt, *bevor* die Zusage aus AK-72 bricht, die zweite genau dann. Die
  30 Tage der ursprünglichen Fassung hätten fünf Wochen offen gelassen, in denen die
  Zusage gebrochen ist, ohne dass es irgendwo auffällt. Muster ist der „Stand vom"-Hinweis
  auf `/open`, der ab 60 Tagen von grauem Kleingedruckten in einen amber-Kasten wechselt.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Gegenstand des Boards | Ideen zur **Plattform** | Bewertungen zu Lokalen sind ein eigener, im PRD belegter Roadmap-Punkt mit eigener Mechanik; Korrekturhinweise zu Einträgen enden in einer Datenänderung statt in einer Meinung. Drei Produkte, nicht eins |
| 2 | Kommentare unter einer Idee | nein, zurückgestellt | Jeder Kommentar durchliefe dieselbe Freigabe. Bei einer Person im Betrieb verdoppelt das die Moderationslast, ohne die Kernfrage („was wünschen sich viele") besser zu beantworten als eine Zustimmungszahl |
| 3 | Zustimmen ohne Konto | **verworfen** (Korrektur der ersten Runde) | Die erste Runde sah offenes Zustimmen vor. Jede Umsetzung davon verarbeitet entweder eine IP-Adresse oder ist über ein privates Fenster trivial zu umgehen. Mit Kontozwang ist die Zahl belastbar **und** das Feature kommt ganz ohne IP-Verarbeitung aus — das war den Verlust an Bequemlichkeit wert |
| 4 | Sichtbarkeit vor Freigabe | keine | Alleinbetrieb: Zwischen einem beleidigenden Beitrag und seiner Entdeckung lägen sonst Stunden, in denen er unter dem Namen der Plattform steht |
| 5 | Anzeige des Verfassers | Vorname + Initial | Ein Board braucht ein Gesicht, aber der Name wurde bei der Registrierung nicht zur Veröffentlichung erhoben. „Anna B." ist der Mittelweg, und die Regel steht als prüfbares Kriterium (AK-51) statt als Absicht |
| 6 | Abgelehnte Ideen | bleiben sichtbar, Begründung ist **Pflicht** | Produktprinzip 2 („Lücken werden gezeigt, nicht versteckt") gilt sonst nur, solange es dem Betreiber gelegen kommt. Die Pflicht ist erzwungen (AK-27), nicht erhofft |
| 7 | Dubletten | Admin führt zusammen, Stimmen addieren sich | Ein Board ohne Zusammenführung zerfällt in Varianten derselben Idee, und die Zustimmungszahl — die einzige Zahl, auf die es hier ankommt — verliert ihre Aussage |
| 8 | E-Mails | genau eine, bei der Veröffentlichung | Statusmails wären die meisten Mails des Features und jedes Mal ein Zustellrisiko. Die eine Mail beantwortet die Frage, die den Einreicher am meisten beschäftigt: „ist es angekommen und sichtbar" |
| 9 | Ablehnung ohne eigene Mail | ja, die Veröffentlichungsmail deckt sie ab | Eine abgelehnte Idee **wird** veröffentlicht (Entscheidung 6). Damit ist die Mail aus AK-37 auch ihre Benachrichtigung, und der Verfasser liest die Begründung an derselben Stelle wie alle anderen |
| 10 | Sprache der Beiträge | Originalsprache, gekennzeichnet | Vier getrennte Sprachboards hätten bei der heutigen Nutzerzahl drei leere Zustände. Die Kennzeichnung ist zugleich die technisch korrekte Auszeichnung fremdsprachiger Abschnitte und damit ein Barrierefreiheitsgewinn |
| 11 | Kontolöschung: Ideen | bleiben, Verfasserbezug entfällt | Präzedenzfall aus Feature `01` (AK-05). Andere haben zugestimmt, das Team hat öffentlich geantwortet — ein Verschwinden risse Lücken in eine öffentliche Zusage |
| 12 | Kontolöschung: Zustimmungen | verschwinden, Zahl sinkt | Anders als die Idee hat eine Stimme keinen Aussagewert ohne die Person. Preis ist eine Zahl, die sinken kann; das ist der ehrlichere Zustand |
| 13 | Verweise im Beitragstext | nicht anklickbar | Ein moderiertes, öffentliches Board mit anklickbaren Fremdverweisen ist ein Ziel für Verweis-Spam. Als Text bleibt die Angabe nachvollziehbar, ohne den Anreiz zu setzen |
| 14 | Adresse | `/community/ideen` | Deutschsprachiger Pfad wie `/organisationen`, `/vergleich`, `/presse`. Unter `/community`, weil dort bereits der Vorschlags-Wizard liegt und beide Wege dieselbe Zielgruppe haben |
| 15 | Bearbeitungszeit (OF-01) | fünf Werktage, öffentlich zugesagt | Der Einreicher braucht einen Anhaltspunkt, sonst ist das Board dieselbe Sackgasse wie der bestehende Rückmeldeweg. Fünf statt zwei Werktage, weil eine Zusage im Alleinbetrieb halten muss — auch im Urlaub |
| 16 | Löschfrist wartender Einreichungen (OF-02) | zwölf Monate, Hervorhebung ab 30 Tagen | Die Hervorhebung behebt die Ursache (Vergessen), die Frist erfüllt Art. 5 Abs. 1 lit. e. Zwölf statt drei Monate, weil ohne Statusmail eine gelöschte Idee lautlos verschwindet — das darf nicht dem Normalfall passieren |
| 17 | Umgesetzte Ideen (OF-03) | eigener Abschnitt unter der Liste | Ein Filter wäre einen Klick entfernt und würde nie gesehen. Der Beleg, dass zugehört wird, ist die Wirkung des Boards; er gehört auf dieselbe Seite, nur nicht in dieselbe Liste |
| 18 | Zurückziehen (OF-05) | ja, bis zur Freigabe | Bis zur Veröffentlichung gehört der Text allein dem Verfasser. Danach nicht mehr — dann hängen Zustimmungen Dritter und eine öffentliche Team-Antwort daran (wie Decision Log 11) |
| 19 | Datenschutzstufe (OF-04) | **Stufe B, bestätigt** | Es werden keine Gesundheitsdaten erhoben; sie können nur unaufgefordert im Freitext erscheinen, und genau dafür sind AK-16, AK-52 und AK-54 gebaut. Stufe C hätte Verarbeitungsverzeichnis, Feldverschlüsselung und Folgenabschätzung nach sich gezogen — Aufwand ohne zusätzlichen Schutz an der einzigen Stelle, an der das Risiko sitzt |
| 20 | Aufräumlauf (OF-06) | kein neuer Cron: Befehl **plus** fauler Aufruf beim Öffnen der Warteschlange | Auf Produktion fehlen zwei von drei Cron-Einträgen — `app:metrics:snapshot` hat dadurch nie einen Snapshot geschrieben, und das ist nicht nachholbar. Eine Zusage, die von einer Servereinrichtung abhängt, die dreimal ausblieb, ist keine |
| 21 | Bestand auf userjot (OF-07) | nicht übernehmen; Board startet leer, Titel in die PRD-Roadmap | Nachgesehen statt vermutet: sieben Einträge, alle vom Betreiber, alle null Stimmen. Es gab keinen Community-Bestand zu retten — und siebenmal derselbe Name auf einem Board, das nach den Wünschen der Nutzer fragt, hätte das Gegenteil belegt |
| 22 | Externer Dienst (OF-07) | nach dem Deploy abschalten | Ein nicht verlinktes Board bleibt über Suchmaschinen auffindbar und sammelt Beiträge, die niemand liest — dieselbe Sackgasse, gegen die dieses Feature gebaut wird. Dass „Presskit" dort auf *In Progress* steht, während `05` live ist, zeigt, dass es nicht gepflegt wird |
| 23 | Überfälligkeit (OF-08) | zweistufig: drei und fünf Werktage | Eine einzelne Schwelle kann entweder warnen oder den Bruch melden, nicht beides. Feiertage werden bewusst nicht gerechnet — eine Feiertagstabelle für Luxemburg wäre eigene Mechanik ohne Gegenwert |

## Hinweise für `/sdd-architektur 06`

Keine Entwurfsentscheidungen — nur Fallstricke aus dem Bestand, die dort sonst erst nach
dem Bau auffallen:

1. **`ActionLimiter` benutzen, nicht `consume(1)` von Hand** — und den `when@test`-Override
   auf 10000 nicht vergessen, sonst färben AK-59/AK-60 die restliche Suite rot.
   `LimiterCoverageTest` prüft beides.
2. **`AccountDataExporter` und die Kontolöschung aus Feature `01` müssen erweitert
   werden** (AK-65 bis AK-67). Das ist eine Änderung an ausgeliefertem Code, nicht an
   neuem — sie gehört mit einem eigenen Prüflauf abgesichert.
3. **`AdminStatsService` hat bereits `countPending()` für Restaurantvorschläge** — AK-25
   folgt demselben Muster und braucht keine neue Mechanik.
4. **Kein Verzeichnis `public/community` anlegen.** Ein Verzeichnis, das so heißt wie
   eine Route, erzeugt auf Apache eine 301-Schleife (BF-100). Heute existiert keins;
   `RouteDirectoryCollisionTest` hält das fest.
5. **`'empty_data' => ''` bei jedem Feld, dessen Setter ein striktes `string` verlangt** —
   sonst wird aus AK-12 ein 500er statt einer Meldung.
6. **Der Anzeigename aus AK-51 wird aus einem einzigen `name`-Feld abgeleitet**
   (`User::$name` ist `?string`, es gibt keinen getrennten Vornamen). EC-01 und EC-02
   sind deshalb keine Randfälle, sondern der Normalfall.
7. **Bei Änderungen an `assets/` gehört `npm run build` und der committete
   `public/build` dazu**, sonst blockt `verify-assets` den Deploy.
8. **AK-74 braucht einen wiederkehrenden Lauf** (Löschung nie freigegebener Einreichungen
   nach zwölf Monaten). ⚠ **Auf Produktion fehlen heute zwei von drei Cron-Einträgen** —
   `app:metrics:snapshot` hat dadurch nie einen Snapshot geschrieben, und die Historie ist
   nicht nachholbar. Ein Aufräumlauf, der nie eingerichtet wird, ist stiller als das: Er
   fällt nur bei einer Prüfung auf. Der Entwurf sollte deshalb prüfen, ob der Lauf **ohne**
   eigenen Cron auskommt — etwa angehängt an einen bestehenden Befehl oder ausgelöst beim
   Öffnen der Warteschlange. Siehe OF-06.
9. **AK-78 ist Dokumentationsarbeit an `docs/datenschutz.md`, nicht Programmierarbeit** —
   dort steht Stufe B bis heute als unbestätigte Annahme. `sdd-spec` darf die Datei nicht
   anfassen; `sdd-tasks` gehört eine eigene Aufgabe dafür, sonst fällt es zwischen die
   Stühle. Dieselbe Vorbedingungslage wie VB-03 in Feature `05`.
10. **AK-77 ist ein Zugriffskriterium, kein Oberflächenkriterium.** „Es gibt keinen Knopf"
   ist keine Regel — der Weg muss serverseitig abgewiesen werden, sonst steht er jedem
   offen, der ihn von Hand nachbaut.
