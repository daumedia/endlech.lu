# 17 · Favoriten und Listen — Spezifikation

Status: `planned` · Stand: 2026-11-13

## Zweck

Wer ein passendes Lokal gefunden hat, muss es heute bei jedem Besuch neu suchen: Endlech.lu
merkt sich nichts, und ein Konto bringt außer dem Einreichen keinen Nutzen (PRD, Zielgruppe 1,
„Offener Punkt“; öffentlich auf `/roadmap` unter „Geplant“ als „Favoriten“).

Nach diesem Feature merkt man ein Lokal mit **einem Tipp aufs Herz**, sammelt Lokale in
**eigenen, benannten Listen** („Mittag in Kirchberg“, „Mit Oma“), hält zu jedem Lokal eine
**private Notiz** fest („Seiteneingang links, Rampe steil“) und findet alles auf einer Seite
**„Gespeichert“** wieder. In der Restaurantsuche lassen sich alle Filter auf die eigenen
Favoriten anwenden. Alles bleibt privat: Nur der Kontoinhaber sieht seine Favoriten, Listen
und Notizen — keine andere Person, nicht die Verwaltung, kein externer Dienst.

## Begriffe

| Begriff | Bedeutung |
|---|---|
| **Favorit** | ein Lokal mit gesetztem Herz. Jedes Konto hat genau eine Favoritenmenge; auf „Gespeichert“ heißt sie „Favoriten“ und lässt sich weder umbenennen noch löschen |
| **Liste** | eine vom Nutzer angelegte, benannte Sammlung von Lokalen. **Unabhängig vom Herz:** Ein Lokal kann in einer Liste stehen, ohne Favorit zu sein, und umgekehrt; es kann in mehreren Listen stehen, in derselben Liste aber nur einmal |
| **Notiz** | privater Freitext zu einem Lokal, höchstens eine je Konto und Lokal. Sie gehört zum Lokal, nicht zu einer Liste, und geht auch ohne Herz und ohne Liste |
| **Gespeichert** | die Seite, auf der Favoriten und Listen zusammenlaufen, samt der Seite „Favoriten“ und einer Seite je Liste |
| **Änderung** | jedes Absenden, das tatsächlich etwas speichert: Herz setzen oder entfernen, Listenzugehörigkeit ändern, Notiz speichern oder leeren, Liste anlegen, umbenennen oder löschen. Zählt für den Deckel (AK-57, AK-58) |

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| 01 · Betroffenenrechte | `deployed` | Datenexport und Kontolöschung müssen Favoriten, Listen und Notizen erfassen (AK-60, AK-61) |
| 02 · Barrierefreiheit der Plattform | `deployed` | Herz, Formulare und die neuen Seiten erfüllen dieselben Regeln wie jede Seite (AK-46 bis AK-51) |
| 10 · Sitemap und robots.txt | `deployed` | Die neuen Seiten sind privat, blättern und müssen dort eingeordnet sein; in der Sitemap stehen sie nie (AK-45) |
| 11 · Nutzungsmessung | `deployed` | Drei neue Ereignisse; Listennamen, Listennummern und Notizen dürfen Umami nie erreichen (AK-64, AK-66, AK-67) |
| 12 · Onboarding | `deployed` | Die Weiche nach der Anmeldung: Eine gemerkte Zielseite gewinnt gegen die Einführung (EC-04) |
| B01 · B02 · B03 · Registrierung, Anmeldung, Passkey | `deployed` | Beide Anmeldewege müssen den Merkwunsch eines Abgemeldeten nachholen (AK-09) |
| B04 · Profil | `deployed` | Verweis auf „Gespeichert“ (AK-44) |
| B05 · Restaurantsuche | `deployed` | Herz auf jeder Karte, neuer Filter „Nur meine Favoriten“ (AK-01, AK-38 bis AK-40) |
| B06 · Restaurant-Detailseite | `deployed` | Herz, Bereich „In Listen“, Notiz (AK-02, AK-21 bis AK-24, AK-32 bis AK-37) |
| B20 · Restaurantverwaltung | `deployed` | Das Löschen eines Lokals räumt Herzen, Listeneinträge und Notizen mit ab (AK-62) |
| B24 · Mehrsprachigkeit | `approved` | Alle Texte in fünf Sprachen (AK-46) |
| B25 · PWA und mobile Navigation | `deployed` | Punkt „Gespeichert“ in der unteren Leiste für Angemeldete, an der Stelle von „Über uns“ (AK-41, AK-42) |

## User Stories

- **US-01** · Als Rollstuhlnutzerin möchte ich ein Lokal, das für mich passt, mit einem Tipp
  merken, damit ich es beim nächsten Mal nicht neu suchen muss.
- **US-02** · Als jemand, der Ausflüge für die Familie plant, möchte ich Lokale in eigenen
  Listen sammeln („Mit Oma“, „Mittag in Kirchberg“), damit ich je Anlass eine kurze Auswahl
  zur Hand habe.
- **US-03** · Als Gast möchte ich mir zu einem Lokal festhalten, was auf seiner Seite nicht
  steht („Seiteneingang nutzen“), damit ich es beim nächsten Besuch weiß — ohne dass es jemand
  anderes liest.
- **US-04** · Als Suchende möchte ich die Filter der Suche auf meine Favoriten anwenden
  („Welches meiner Lokale hat jetzt geöffnet?“).
- **US-05** · Als Besucher ohne Anmeldung möchte ich nach dem Tipp aufs Herz und der Anmeldung
  dort weitermachen, wo ich war, ohne ein zweites Mal tippen zu müssen.
- **US-06** · Als Kontoinhaber möchte ich, dass meine Listen und Notizen in meinem Datenexport
  stehen und mit meinem Konto verschwinden.

## Nicht im Scope

- **Teilen einer Liste** — per Link oder öffentlich. Ein geteilter Listenname wäre Freitext,
  der ungesichtet unter einer `endlech.lu`-Adresse erscheint (Decision Log 2). Eigenes
  Vorhaben, wenn danach gefragt wird.
- **Favoriten, Listen und Notizen in der REST-API `/api/v1`.** Kommen mit der nativen App
  (Roadmap `ios_app`); heute gäbe es keinen Nutzer dafür.
- **Herz auf der Startseite** (Top-6) und auf den Vergleichsseiten. Gemerkt wird in der
  Suche, auf der Detailseite und auf „Gespeichert“.
- **„In Listen“ auf der Karte in der Suche.** Nur auf der Detailseite (Decision Log 6).
- **Filter nach einer eigenen Liste** in der Suche. Nur „Nur meine Favoriten“ (Decision Log 12).
- **Selbst sortieren** innerhalb einer Liste.
- **Hinweis oder Mail, wenn sich ein gemerktes Lokal ändert** oder gelöscht wird.
- **Zahlen über das Merken:** „N Personen haben das gemerkt“, Ranglisten, Kennzahlen im
  Admin-Dashboard oder auf `/open`.
- **Offline-Zugriff** auf „Gespeichert“ in der PWA und eine **Druckansicht** einer Liste.
- **Hinweis auf das Herz in der Einführung** (Feature 12). Dort verspricht bisher bewusst
  keiner der Bildschirme Favoriten — ob sich das ändert, ist OF-01.
- **Ein Abschnitt für Notizen zu Lokalen, die weder gemerkt noch in einer Liste sind** — OF-02.

## Akzeptanzkriterien

Meldungstexte in Anführungszeichen sind die deutsche Fassung; die übrigen vier Sprachen
tragen dieselbe Aussage (AK-46). `<Name>` steht für den Namen des Lokals, `<Liste>` für den
Namen einer Liste.

### A · Merken mit dem Herz

- **AK-01** · Angenommen, eine angemeldete Nutzerin sieht in der Restaurantsuche eine Karte
  mit leerem Herz, wenn sie auf das Herz tippt, dann ist es danach gefüllt, die Meldung
  „Gemerkt: <Name>“ erscheint, und die Seite bleibt an derselben Stelle stehen — sie springt
  nicht an den Anfang, Filter, Sortierung und Seitenzahl bleiben erhalten.
- **AK-02** · Angenommen, sie öffnet die Detailseite eines Lokals, wenn sie dort auf das Herz
  beim Namen des Lokals tippt, dann gilt dasselbe wie in AK-01.
- **AK-03** · Angenommen, ein Herz ist gefüllt, wenn sie darauf tippt, dann ist es danach
  leer, „Aus Favoriten entfernt: <Name>“ erscheint, und das Lokal steht nicht mehr unter
  „Favoriten“. Seine Einträge in eigenen Listen und seine Notiz bleiben unverändert.
- **AK-04** · Angenommen, sie hat ein Lokal gemerkt, wenn sie danach die Suche, die
  Detailseite oder „Gespeichert“ neu lädt oder sich auf einem anderen Gerät mit demselben
  Konto anmeldet, dann ist das Herz dort ebenfalls gefüllt.
- **AK-05** · Angenommen, sie hat ein Lokal auf dem Telefon gemerkt, und auf dem Laptop ist
  noch die ältere Seite mit leerem Herz offen, wenn sie dort auf das Herz tippt, dann bleibt
  das Lokal gemerkt — es wird nicht entfernt — und steht danach genau einmal unter
  „Favoriten“. Ebenso bleibt ein schon entferntes Lokal beim Tipp auf ein veraltetes gefülltes
  Herz entfernt, ohne Fehlermeldung, und zwei schnelle Tipps auf dasselbe leere Herz ergeben
  genau einen Eintrag.
- **AK-06** · Angenommen, JavaScript ist abgeschaltet, wenn sie in der Suche auf Seite 2 mit
  gesetztem Filter auf das Herz der vierten Karte tippt, dann lädt die Seite neu, zeigt die
  Meldung aus AK-01 und steht wieder bei derselben Karte — auf Seite 2, mit demselben Filter.

### B · Merken ohne Anmeldung

- **AK-07** · Angenommen, ein abgemeldeter Besucher betrachtet eine Karte in der Suche oder
  eine Detailseite, wenn die Seite erscheint, dann trägt das Lokal ein leeres Herz, aber es
  gibt weder den Bereich „In Listen“ noch ein Notizfeld.
- **AK-08** · Angenommen, er ist auf Seite 2 der Suche mit dem Filter „Rollstuhlgerecht“,
  wenn er auf das Herz einer Karte tippt, dann landet er auf der Anmeldeseite, über dem
  Formular steht „Melde dich an, um dir <Name> zu merken.“, und bis hierher ist nichts
  gespeichert.
- **AK-09** · Angenommen, er meldet sich dort an — mit Passwort oder mit Passkey —, wenn die
  Anmeldung gelingt, dann landet er wieder auf der Seite, von der er kam (in der Suche auf
  derselben Seite mit denselben Filtern bei derselben Karte, sonst auf der Detailseite), das
  Herz ist gefüllt, und „Gemerkt: <Name>“ erscheint. Ein zweiter Tipp ist nicht nötig.
- **AK-10** · Angenommen, der erste Anmeldeversuch scheitert am falschen Passwort, wenn der
  zweite in derselben Sitzung gelingt, dann wird das Merken trotzdem nachgeholt wie in AK-09.

### C · Seite „Gespeichert“

- **AK-11** · Angenommen, eine angemeldete Nutzerin öffnet „Gespeichert“, wenn die Seite
  erscheint, dann steht oben „Favoriten“ mit der Zahl der gemerkten Lokale, darunter
  „Meine Listen“ mit jeder eigenen Liste samt der Zahl ihrer Lokale — zuletzt angelegte
  zuerst — und ein Feld „Neue Liste“ mit dem Knopf „Anlegen“.
- **AK-12** · Angenommen, sie hat 15 Lokale gemerkt, wenn sie „Favoriten“ öffnet, dann stehen
  die Lokale als dieselben Karten wie in der Suche, zuletzt gemerkt zuerst, 12 auf der ersten
  Seite und 3 nach „Weiter“. Hat sie zu einem Lokal eine Notiz, steht diese unter seiner Karte.
- **AK-13** · Angenommen, sie hat noch nichts gemerkt und keine Liste angelegt, wenn sie
  „Gespeichert“ öffnet, dann steht bei „Favoriten“ „Noch nichts gemerkt. Tippe in der Suche
  auf das Herz, um ein Lokal hier zu sammeln.“ mit einem Verweis auf die Restaurantsuche, und
  bei „Meine Listen“ „Noch keine eigene Liste. Lege eine an, zum Beispiel für einen Anlass
  oder einen Ort.“
- **AK-14** · Angenommen, sie ist auf „Favoriten“, wenn sie bei einer Karte auf das gefüllte
  Herz tippt, dann verschwindet die Karte, „Aus Favoriten entfernt: <Name>“ erscheint, und mit
  JavaScript steht der Tastaturfokus danach auf der nächsten Karte — bei der letzten auf der
  vorigen, ist keine mehr übrig, auf dem Leertext aus AK-13.
- **AK-15** · Angenommen, ein abgemeldeter Besucher ruft die Adresse von „Gespeichert“,
  „Favoriten“ oder einer Liste auf, wenn die Seite antwortet, dann landet er auf der
  Anmeldeseite und nach erfolgreicher Anmeldung mit dem Konto, dem die Seite gehört, auf der
  aufgerufenen Seite.

### D · Eigene Listen

- **AK-16** · Angenommen, sie gibt auf „Gespeichert“ bei „Neue Liste“ „Mittag in Kirchberg“
  ein, wenn sie „Anlegen“ wählt, dann steht die Liste zuoberst unter „Meine Listen“ mit
  0 Lokalen, und „Liste „Mittag in Kirchberg“ angelegt.“ erscheint.
- **AK-17** · Angenommen, sie lässt das Feld leer oder gibt nur Leerzeichen ein, wenn sie
  „Anlegen“ wählt, dann steht am Feld „Bitte gib der Liste einen Namen.“, und keine Liste
  entsteht.
- **AK-18** · Angenommen, sie gibt einen Namen aus 61 Buchstaben ein, wenn sie „Anlegen“
  wählt, dann steht am Feld „Der Name darf höchstens 60 Zeichen lang sein.“, keine Liste
  entsteht, und der eingegebene Name steht weiter im Feld. Leerzeichen am Anfang und Ende
  zählen nicht mit und werden nicht gespeichert.
- **AK-19** · Angenommen, sie hat eine Liste „Kirchberg“, wenn sie eine neue Liste
  „ kirchberg “ anlegen will, dann steht am Feld „Eine Liste mit diesem Namen hast du schon.“,
  und keine zweite Liste entsteht. Ein anderes Konto kann eine eigene Liste „Kirchberg“ anlegen.
- **AK-20** · Angenommen, sie hat 25 Listen, wenn sie eine 26. anlegen will — auf „Gespeichert“
  oder auf einer Detailseite —, dann erscheint „Du hast bereits 25 Listen. Lösche eine, um eine
  neue anzulegen.“, und keine Liste entsteht.
- **AK-21** · Angenommen, sie hat drei Listen und das Lokal steht in einer davon, wenn sie auf
  der Detailseite den Bereich „In Listen“ betrachtet, dann stehen dort drei Häkchen mit den
  Listennamen — das eine gesetzt —, ein Feld „Neue Liste“ und ein Knopf „Speichern“.
- **AK-22** · Angenommen, sie setzt dort ein zweites Häkchen und entfernt das erste, wenn sie
  „Speichern“ wählt, dann steht das Lokal danach in der zweiten Liste und nicht mehr in der
  ersten, „Listen gespeichert.“ erscheint, die Zahlen auf „Gespeichert“ stimmen, und das Herz
  bleibt, wie es war.
- **AK-23** · Angenommen, sie gibt auf der Detailseite bei „Neue Liste“ „Mit Oma“ ein, wenn
  sie „Speichern“ wählt, dann entsteht die Liste und enthält dieses Lokal bereits. Die
  Prüfungen aus AK-17 bis AK-20 gelten auch hier; scheitert der Name an einer davon, wird
  auch keine Häkchenänderung aus demselben Absenden übernommen.
- **AK-24** · Angenommen, sie hat noch keine Liste, wenn sie auf der Detailseite den Bereich
  „In Listen“ betrachtet, dann steht dort statt der Häkchen nur das Feld „Neue Liste“.
- **AK-25** · Angenommen, sie öffnet eine Liste mit 14 Lokalen, wenn die Seite erscheint, dann
  ist der Listenname die Hauptüberschrift, die Lokale stehen als Karten, zuletzt hinzugefügt
  zuerst, 12 auf der ersten Seite und 2 nach „Weiter“; Notizen stehen unter den Karten wie in
  AK-12.
- **AK-26** · Angenommen, sie wählt auf einer Listenseite bei einer Karte „Aus Liste
  entfernen“, wenn die Seite antwortet, dann ist die Karte weg, „<Name> aus „<Liste>“
  entfernt.“ erscheint, und das Lokal bleibt in seinen anderen Listen, unter „Favoriten“ und
  mit seiner Notiz.
- **AK-27** · Angenommen, eine Liste ist leer, wenn sie geöffnet wird, dann steht dort „Diese
  Liste ist noch leer. Lokale legst du auf ihrer Detailseite unter „In Listen“ hinein.“ mit
  einem Verweis auf die Restaurantsuche.
- **AK-28** · Angenommen, sie benennt „Kirchberg“ in „Kirchberg mittags“ um, wenn sie
  speichert, dann trägt die Liste überall den neuen Namen — auf „Gespeichert“, als Überschrift
  ihrer Seite und bei den Häkchen der Detailseiten. Für den neuen Namen gelten AK-17 bis
  AK-19; den bisherigen Namen der Liste selbst noch einmal zu speichern, ist keine Dublette.
- **AK-29** · Angenommen, sie wählt bei einer Liste mit 4 Lokalen „Liste löschen“, wenn die
  Rückfrage erscheint, dann steht dort „Liste „<Liste>“ mit 4 Lokalen löschen? Die Lokale
  selbst, deine Favoriten und deine Notizen bleiben.“ mit den Knöpfen „Löschen“ und
  „Abbrechen“, und bis hierher ist nichts gelöscht. Die Rückfrage funktioniert ohne JavaScript.
- **AK-30** · Angenommen, die Rückfrage steht, wenn sie „Abbrechen“ wählt, dann ist sie wieder
  auf der Listenseite und die Liste unverändert; wählt sie „Löschen“, ist die Liste weg,
  „Liste „<Liste>“ gelöscht.“ erscheint auf „Gespeichert“, und Herz und Notiz der vier Lokale
  sind unverändert.
- **AK-31** · Angenommen, ein Lokal steht in „Kirchberg“, wenn das Formular „In Listen“ mit
  gesetztem Häkchen ein zweites Mal abgeschickt wird (zweiter Tab, Doppelklick), dann steht
  das Lokal danach genau einmal in „Kirchberg“.

### E · Notiz

- **AK-32** · Angenommen, eine angemeldete Nutzerin öffnet eine Detailseite, wenn sie den
  Bereich „Deine Notiz“ betrachtet, dann steht dort ein Textfeld mit dem Hinweis „Nur für dich
  sichtbar.“ und ein Knopf „Notiz speichern“ — auch wenn das Lokal weder gemerkt noch in einer
  Liste ist.
- **AK-33** · Angenommen, sie schreibt „Seiteneingang links, Rampe steil“ und wählt „Notiz
  speichern“, wenn die Seite antwortet, dann erscheint „Notiz gespeichert.“, und nach dem
  Neuladen steht der Text im Feld.
- **AK-34** · Angenommen, das Lokal steht unter „Favoriten“ und in zwei Listen, wenn sie diese
  drei Ansichten öffnet, dann steht die Notiz in allen drei unter der Karte des Lokals.
- **AK-35** · Angenommen, eine Notiz ist gespeichert, wenn sie einen Text aus 501 Zeichen
  speichern will, dann steht am Feld „Die Notiz darf höchstens 500 Zeichen lang sein.“, die
  bisherige Notiz bleibt unverändert gespeichert, und der eingegebene Text steht weiter im
  Feld, damit nichts verloren geht.
- **AK-36** · Angenommen, eine Notiz ist gespeichert, wenn sie das Feld leert und speichert,
  dann ist die Notiz entfernt, „Notiz gelöscht.“ erscheint, und unter den Karten auf
  „Gespeichert“ steht nichts mehr.
- **AK-37** · Angenommen, die Notiz lautet `<b>fett</b> <script>alert(1)</script>` und enthält
  einen Zeilenumbruch, wenn sie auf der Detailseite und auf „Gespeichert“ angezeigt wird, dann
  erscheint genau dieser Text als Text — nichts wird fett, kein Skript läuft —, und der
  Zeilenumbruch bleibt erhalten.

### F · Filter „Nur meine Favoriten“

- **AK-38** · Angenommen, eine angemeldete Nutzerin hat 5 Lokale gemerkt, davon 2
  rollstuhlgerecht, wenn sie in der Suche „Nur meine Favoriten“ und „Rollstuhlgerecht“ wählt,
  dann zeigt die Liste genau diese 2; Sortierung und Blättern funktionieren wie bei jedem
  anderen Filter.
- **AK-39** · Angenommen, ein Besucher ist abgemeldet, wenn er die Suche öffnet, dann gibt es
  das Häkchen „Nur meine Favoriten“ nicht; ruft er eine Suchadresse mit diesem Filter auf (etwa
  aus einem weitergegebenen Link), sieht er die gewöhnliche Liste ohne Fehlermeldung.
- **AK-40** · Angenommen, sie hat noch nichts gemerkt, wenn sie den Filter wählt, dann steht
  statt der Karten „Du hast noch keine Favoriten. Tippe auf das Herz eines Lokals, um es hier
  zu finden.“ mit einem Verweis „Filter entfernen“, der die übrigen Filter erhält.

### G · Navigation und Auffindbarkeit

- **AK-41** · Angenommen, eine angemeldete Nutzerin ist auf einem Telefon (Breite unter
  768 px), wenn sie die untere Leiste betrachtet, dann hat sie vier Punkte in der Reihenfolge
  Start · Restaurants · Gespeichert · Profil — „Gespeichert“ steht an der Stelle von
  „Über uns“ —, und „Gespeichert“ ist auf „Gespeichert“, „Favoriten“, jeder Listenseite und
  jeder Rückfrage als aktuelle Seite markiert. „Über uns“ bleibt über die Fußzeile erreichbar.
  Abgemeldet ist die Leiste unverändert (Start · Restaurants · Über uns · Anmelden).
  *Neu gefasst 2026-11-13 nach OF-04. Vorher: fünf Punkte, Start · Restaurants · Gespeichert ·
  Über uns · Profil.*
- **AK-42** · Angenommen, eine angemeldete Nutzerin sieht die Leiste, wenn sie bei 320 px Breite
  in jeder der fünf Sprachen betrachtet wird, dann ist jede Beschriftung vollständig lesbar und
  einzeilig, nichts überlappt oder ist abgeschnitten, und jeder Punkt ist mindestens
  44 × 44 px groß. *Neu gefasst 2026-11-13 nach OF-04. Vorher: „Angenommen, die Leiste hat
  fünf Punkte …“.*
- **AK-43** · Angenommen, eine angemeldete Nutzerin ist auf einem Bildschirm ab 768 px Breite,
  wenn sie die Kopfzeile betrachtet, dann steht im Kontobereich neben Profilbild und Name ein
  Verweis „Gespeichert“.
- **AK-44** · Angenommen, sie öffnet ihr Profil, wenn die Seite erscheint, dann führt dort ein
  Verweis auf „Gespeichert“.
- **AK-45** · Angenommen, die Sitemap wird abgerufen, wenn sie durchsucht wird, dann steht
  keine Adresse von „Gespeichert“, „Favoriten“, einer Liste oder einer Rückfrage darin.

### H · Sprache und Barrierefreiheit

- **AK-46** · Angenommen, die Seiten dieses Features werden in lb, de, fr, en und pt
  aufgerufen, wenn sie betrachtet werden, dann sind alle Beschriftungen, Meldungen, Leertexte
  und Rückfragen in der jeweiligen Sprache; Listennamen und Notizen erscheinen unverändert, so
  wie sie eingegeben wurden.
- **AK-47** · Angenommen, ein Screenreader (VoiceOver) liest ein Herz vor, wenn er es erreicht,
  dann nennt er Handlung und Lokal — „<Name> merken“ bei leerem, „<Name> aus Favoriten
  entfernen“ bei gefülltem Herz —, sodass sich die Herzen von sechs Karten unterscheiden lassen.
- **AK-48** · Angenommen, VoiceOver ist eingeschaltet, wenn sie (mit JavaScript) ein Herz
  antippt, dann wird „Gemerkt: <Name>“ angesagt, und der Fokus bleibt auf dem Herz, das nun
  „<Name> aus Favoriten entfernen“ heißt.
- **AK-49** · Angenommen, ein Herz wird betrachtet, wenn man gefüllt und leer vergleicht, dann
  unterscheiden sich beide in der Form, nicht nur in der Farbe; das Herz hat gegen seinen
  Hintergrund in beiden Zuständen mindestens 3 : 1 Kontrast, eine Zielfläche von mindestens
  44 × 44 px und bei Tastaturbedienung einen sichtbaren Fokusrahmen.
- **AK-50** · Angenommen, ein Listenname oder eine Notiz scheitert an einer Prüfung, wenn die
  Seite antwortet, dann steht die Meldung am Feld, ein Screenreader liest sie beim Feld vor,
  und der Fokus steht im Feld.
- **AK-51** · *(Abnahme des Betreibers)* Angenommen, ein Telefon mit 375 px Breite, wenn der
  Betreiber ohne Hilfe nacheinander in der Suche ein Lokal merkt, eine Liste anlegt, ein Lokal
  auf seiner Detailseite hineinlegt, eine Notiz schreibt und den Filter „Nur meine Favoriten“
  benutzt, dann gelingt jeder Schritt, keine Seite springt dabei an den Anfang, und keine Seite
  hat eine waagerechte Scrollleiste.

### I · Datenschutz und Missbrauchsschutz

Die Fragen dazu stehen in `~/.claude/sdd/sicherheit.md`; die Einordnung je Abschnitt steht
unter *Sicherheitskatalog* weiter unten.

- **AK-52** · Angenommen, Konto A hat ein Lokal gemerkt, eine Liste „Mit Oma“ angelegt und
  eine Notiz zum Lokal geschrieben, wenn Konto B oder ein abgemeldeter Besucher dieselbe
  Suchseite und dieselbe Detailseite öffnet, dann ist dort kein Herz gefüllt, und weder der
  Listenname noch die Notiz steht irgendwo auf der Seite, auch nicht im Seitenquelltext.
- **AK-53** · Angenommen, Konto B kennt die Adresse einer Liste von Konto A, wenn B sie
  aufruft, dann antwortet die Seite mit „Nicht gefunden“ (404) — ohne Listennamen, ohne
  Anzahl und genauso wie bei einer Liste, die es nicht gibt.
- **AK-54** · Angenommen, Konto B schickt ein Formular zum Umbenennen, Löschen, Befüllen oder
  Entfernen ab, in dem die Nummer einer Liste von Konto A steht, wenn die Seite antwortet,
  dann antwortet sie mit 404, und an der Liste von A ändert sich nichts.
- **AK-55** · Angenommen, ein Formular dieses Features nennt eine Lokalnummer, die es nicht
  gibt, wenn es abgeschickt wird, dann antwortet die Seite mit 404, und nichts entsteht.
- **AK-56** · Angenommen, ein Formular dieses Features kommt ohne gültiges Sicherheitstoken an
  (etwa von einer fremden Seite abgeschickt), wenn die Seite antwortet, dann erscheint
  „Ungültiges CSRF-Token. Bitte versuche es erneut.“, und nichts ändert sich.
- **AK-57** · Angenommen, ein Konto hat in der letzten Stunde 120 Änderungen gespeichert, wenn
  es eine 121. versucht, dann erscheint „Zu viele Änderungen. Bitte versuche es in <N> Minuten
  erneut.“, und nichts wird gespeichert. Ein anderes Konto am selben Internetanschluss kann
  weiter speichern.
- **AK-58** · Angenommen, ein Konto schickt Formulare ab, die an einer Prüfung scheitern oder
  nichts ändern (Herz schon gesetzt, Häkchen unverändert), wenn danach gezählt wird, dann
  haben sie nicht gegen den Deckel gezählt; ein Absenden auf der Detailseite, das Häkchen und
  eine neue Liste zugleich ändert, zählt einmal.
- **AK-59** · Angenommen, ein Konto mit Verwaltungsrechten ist angemeldet, wenn es alle Seiten
  unter `/admin` durchgeht, dann zeigt keine davon Favoriten, Listen, Listennamen, Notizen
  oder deren Anzahl — weder je Konto noch zusammengezählt.
- **AK-60** · Angenommen, ein Konto hat 2 Favoriten, 1 Liste mit 3 Lokalen und 1 Notiz, wenn es
  seinen Datenexport herunterlädt, dann enthält dieser die beiden Favoriten (Lokal und
  Zeitpunkt), die Liste (Name, angelegt am, die drei Lokale je mit Zeitpunkt) und die Notiz
  (Lokal, Text, zuletzt geändert).
- **AK-61** · Angenommen, dieses Konto wird gelöscht, wenn danach in der Datenbank nach seinen
  Favoriten, Listen, Listeneinträgen und Notizen gesucht wird, dann ist keine Zeile mehr übrig.
- **AK-62** · Angenommen, die Verwaltung löscht ein Lokal, das ein Konto gemerkt, in zwei
  Listen gelegt und mit einer Notiz versehen hat, wenn das Konto danach „Gespeichert“ öffnet,
  dann fehlt das Lokal überall, die Zahlen stimmen, es erscheint keine Fehlermeldung, es geht
  keine Mail hinaus, und in der Datenbank zeigt keine Zeile dieses Features mehr auf das Lokal.
- **AK-63** · Angenommen, Konto A hatte „Gespeichert“ samt Notizen geöffnet und hat sich
  abgemeldet, wenn danach jemand im selben Browser auf „Zurück“ tippt, dann erscheinen weder
  Listen noch Notizen von A.
- **AK-64** · Angenommen, Konto A legt eine Liste an und schreibt eine Notiz, wenn die
  Zählaufrufe an Umami, die dabei verschickten Mails und die Protokollzeilen dieser Vorgänge
  betrachtet werden, dann steht darin kein Listenname, keine Listennummer und kein Notiztext;
  Brevo erhält aus diesem Feature nichts.
- **AK-65** · Angenommen, beim Speichern einer Notiz tritt ein Serverfehler auf, wenn der
  zugehörige Fehlerbericht in Sentry betrachtet wird, dann steht der Notiztext nicht darin.
- **AK-66** · Angenommen, eine Nutzerin setzt ein Herz, legt eine Liste an und wählt den Filter
  „Nur meine Favoriten“, wenn die Zählaufrufe an Umami betrachtet werden, dann gibt es je genau
  ein Ereignis „Herz gesetzt“, „Liste angelegt“ und „Favoriten-Filter benutzt“ — ohne Lokal,
  Listennamen oder Nummer. Herz entfernen, Notiz speichern, umbenennen, löschen und aus einer
  Liste entfernen erzeugen kein Ereignis, ebenso wenig ein abgewiesenes Absenden (Prüfung,
  Deckel, Token).
- **AK-67** · Angenommen, sie öffnet eine eigene Liste, wenn der Seitenaufruf in Umami
  betrachtet wird, dann steht dort ein Pfad ohne Listennummer und ohne Listennamen.
- **AK-68** · Angenommen, der Datenschutzabschnitt auf `/legal` wird in einer der fünf Sprachen
  gelesen, wenn man nach Favoriten sucht, dann nennt er Favoriten, Listen und Notizen: was
  gespeichert wird, dass nur der Kontoinhaber es sieht, dass es an keinen Dienstleister geht,
  dass es im Datenexport steht und mit dem Konto gelöscht wird.
- **AK-69** · Angenommen, ein Konto mit noch unbestätigter E-Mail-Adresse ist angemeldet, wenn
  es ein Herz setzt, eine Liste anlegt oder eine Notiz schreibt, dann gelingt das wie bei einem
  bestätigten Konto.

### J · Veröffentlichung

- **AK-70** · Angenommen, das Feature ist ausgeliefert, wenn `/roadmap` in einer der fünf
  Sprachen geöffnet wird, dann steht „Favoriten“ nicht mehr unter „Geplant“, und `/changelog`
  nennt die Neuerung im Eintrag des Releases.

## Sicherheitskatalog

Jede Frage aus `~/.claude/sdd/sicherheit.md`, mit der Antwort für dieses Feature.

| # | Frage | Antwort |
|---|---|---|
| 1 | Welche personenbezogenen Daten? | Welche Lokale ein Konto gemerkt hat, seine Listen samt Namen und Zugehörigkeiten, seine Notizen, jeweils mit Zeitpunkt |
| 1 | Besondere Kategorien? | **Nicht strukturiert erhoben.** Notiz und Listenname sind Freitext, in dem eine Gesundheitsangabe *unaufgefordert* stehen kann („für Papas Rollstuhl zu eng“) — genau der Fall, für den `docs/datenschutz.md` Stufe B vorsieht. Kein Feld fragt nach Bedarf oder Einschränkung. Die Stufe bleibt **B** (Decision Log 21) |
| 1 | Wo und wie lange? | Eigene Datenbank. Keine eigene Frist: Ein Favorit lebt bis zum Entfernen, eine Liste bis zum Löschen, eine Notiz bis zum Leeren — jeweils höchstens so lange wie Konto (AK-61) und Lokal (AK-62) |
| 1 | Landen sie in Logs? | Nein — AK-64, AK-65 |
| 2 | Externe Dienste? | Keiner bekommt Inhalte. Umami erhält drei Ereignisse ohne Lokal, Namen oder Nummer (AK-66) und Pfade ohne Listennummer (AK-67); Brevo nichts; Sentry keinen Notiztext (AK-65) |
| 2 | AV-Vertrag, Training? | Trifft nicht zu, weil kein neuer Auftragsverarbeiter und kein KI-Dienst angebunden wird |
| 3 | Wer sieht, ändert, löscht? | Ausschließlich der Kontoinhaber (AK-52 bis AK-55). Die Verwaltung sieht nichts, auch keine Zahlen (AK-59). Erzwungen auf dem Server bei jedem Aufruf, nicht durch Ausblenden |
| 3 | Fremde ID? | 404 ohne Inhalt, gleich wie für eine nicht vorhandene (AK-53, AK-54) |
| 3 | Rollen? | Keine neue. `ROLE_ADMIN` erhält keine zusätzlichen Rechte (AK-59) |
| 4 | Rate Limit? | 120 Änderungen je Stunde und Konto, am Konto statt an der IP (AK-57, AK-58). Lesen ist ungedeckelt: Kein Weg lädt den gesamten Bestand — „Gespeichert“ und Listen blättern zu 12 (AK-12, AK-25), die Detailseite zeigt höchstens 25 Listen (AK-20) |
| 4 | Obergrenzen? | 25 Listen je Konto (AK-20); ein Lokal je Liste höchstens einmal (AK-31) |
| 4 | Kosten, Mail, Upload? | Trifft nicht zu, weil das Feature weder Geld kostet noch eine Mail auslöst, ein Geheimnis prüft oder Dateien annimmt |
| 5 | Kontolöschung? | Alles fällt mit (AK-61). Es bleibt nichts zurück, es gibt keine Aufbewahrungspflicht |
| 5 | Export? | AK-60 |
| 5 | E-Mail-Adresse danach frei? | Unverändert aus Feature 01; dieses Feature hält keine Adresse fest |
| 6 | Geheimnisse? | Trifft nicht zu, weil kein Schlüssel und kein externer Dienst hinzukommt |
| — | Gemeinsam genutztes Gerät | Nach der Abmeldung keine Listen oder Notizen über „Zurück“ (AK-63); offline zeigt die PWA keine gespeicherte Fassung (EC-11) |

## Edge Cases

- **EC-01** · Ein Abgemeldeter tippt aufs Herz und registriert sich, statt sich anzumelden →
  der Merkwunsch verfällt, sobald die Sitzung endet — der übliche Fall, weil der
  Bestätigungslink aus dem Postfach meist in einem anderen Browser aufgeht. Nach Bestätigung
  und erster Anmeldung ist dann nichts gemerkt, ohne Fehlermeldung (Decision Log 5). Meldet er
  sich in **derselben** Sitzung an, gilt EC-02. *Präzisiert 2026-11-13 im Entwurf: Die erste
  Fassung widersprach EC-02 für diesen Fall.*
- **EC-02** · Ein Abgemeldeter tippt aufs Herz, verlässt die Anmeldeseite und meldet sich
  später in derselben Sitzung über die Kopfzeile an → das Merken wird nachgeholt, und er
  landet auf der Seite des Herzens. Endet die Sitzung vorher, verfällt der Wunsch.
- **EC-03** · Das Lokal wird gelöscht, während der Abgemeldete sich anmeldet → nichts wird
  gespeichert, es erscheint keine Meldung zum Merken. Kam er von der Detailseite dieses
  Lokals, sieht er deren gewöhnliche „Nicht gefunden“-Seite.
- **EC-04** · Die erste Anmeldung eines neuen Kontos kommt über das Herz → die Seite des Herzens
  gewinnt gegen die Einführung (Feature 12, dort AK-03). Die Einführung bleibt fällig und
  erscheint bei der nächsten Anmeldung, die auf die Startseite führt.
- **EC-05** · Zwei Tabs legen gleichzeitig eine Liste mit demselben Namen an → es entsteht genau
  eine, der zweite Tab bekommt die Meldung aus AK-19. Bei 24 Listen und zwei gleichzeitigen
  Anlagen entstehen höchstens 25.
- **EC-06** · Zwei Geräte speichern kurz nacheinander verschiedene Notizen zum selben Lokal →
  es gilt die zuletzt gespeicherte, ohne Warnung.
- **EC-07** · Ein Listenname aus 60 Emoji, Umlauten oder Zeichen anderer Schriften → er wird
  gespeichert oder mit der Meldung aus AK-18 abgewiesen, **nie** mit einem Serverfehler.
- **EC-08** · Eine Notiz nur aus Leerzeichen oder Zeilenumbrüchen → wird behandelt wie ein
  geleertes Feld (AK-36).
- **EC-09** · Eine Liste wird auf einem Gerät gelöscht, während ein anderes ihre Seite offen hat
  und dort ein Lokal entfernt → „Nicht gefunden“ (404) wie in AK-53; nichts entsteht neu.
- **EC-10** · Der Filter „Nur meine Favoriten“ ist aktiv, und auf einer Karte wird das Herz
  entfernt → die Karte darf bis zum nächsten Laden der Seite mit leerem Herz stehen bleiben;
  danach fehlt sie.
- **EC-11** · Kein Netz in der installierten App → „Gespeichert“ zeigt die Offline-Seite, keine
  zwischengespeicherte Fassung der Listen oder Notizen.
- **EC-12** · Ein gemerktes Lokal verliert die Verifizierung oder ein Merkmal → es bleibt
  gemerkt, die Karte zeigt den aktuellen Stand, kein Hinweis.
- **EC-13** · Ein Konto bleibt unbestätigt → seine Favoriten, Listen und Notizen bleiben so lange
  wie das Konto. Einen Aufräumlauf für unbestätigte Konten gibt es nicht (OF-03).
- **EC-14** · Sprachwechsel auf einer Listenseite → dieselbe Liste in der anderen Sprache, der
  Listenname bleibt unverändert.
- **EC-15** · Das Herz auf einer Karte innerhalb einer eigenen Listenseite → wirkt auf die
  Favoriten, nicht auf die Liste; die Karte bleibt in der Liste.
- **EC-16** · Eine Seitenzahl hinter dem Ende oder eine ungültige Seitenzahl auf „Favoriten“
  oder einer Listenseite → verhält sich wie in der Restaurantsuche, nie ein Serverfehler.

## Offene Fragen

- **OF-01** · Soll die Einführung (Feature 12, Bildschirm „Einträge lesen“) auf das Herz
  hinweisen? Bisher verspricht dort bewusst kein Bildschirm Favoriten, weil es sie nicht gab.
  — Betreiber, nach der Auslieferung; blockiert den Bau nicht, berührt Feature 12.
  ⏳ **Zurückgestellt 2026-11-14 (Betreiber) — Auslöser: vier Wochen nach der Auslieferung von 17 (Vorschlag)** wird
  das Ereignis `herz_gesetzt` in Umami angesehen. Wird das Herz kaum genutzt, ist ein Hinweis in der Einführung der
  Hebel; dann neue Zeile im Index (nicht im Fehlerauftrag von 17).
- **OF-02** · Eine Notiz zu einem Lokal, das weder gemerkt noch in einer Liste ist, findet man
  nur auf dessen Detailseite wieder. Braucht „Gespeichert“ einen Abschnitt „Mit Notiz“? —
  Betreiber, nach den ersten Wochen im Betrieb; blockiert den Bau nicht.
  ⏳ **Zurückgestellt 2026-11-14 (Betreiber) — Auslöser: sechs Wochen nach der Auslieferung von 17 (Vorschlag)** wird
  gezählt, wie viele Notizen zu Lokalen ohne Herz und ohne Liste gehören (Abfrage über `restaurant_note`,
  `favorite` und `saved_list_entry`, ohne Inhalte zu lesen). Gibt es solche Notizen, kommt der Abschnitt „Mit Notiz“.
- **OF-03** · Unbestätigte Konten werden nie aufgeräumt (B01), ihre Notizen liegen damit
  unbefristet. Gehört zu B01, nicht hierher; notiert, weil dieses Feature dort erstmals
  Freitext ablegt. — Betreiber, eigener Auftrag an B01.
  ✖ **Beantwortet 2026-11-14 (Betreiber): kein Aufräumen.** „Unbestätigt“ heißt nach BF-03 (akzeptiert) nicht
  „verlassen“ — ein Aufräumlauf löschte genutzte Konten samt Listen und Notizen. Jedes Konto kann sich selbst löschen
  und seine Daten exportieren (Feature 01); der Export enthält Favoriten, Listen und Notizen.
- **OF-04** · AK-41 verlangte fünf Punkte in der unteren Leiste, AK-42 dabei einzeilige,
  vollständig lesbare Beschriftungen bei 320 px. Beides zugleich geht nicht: Fünf Punkte lassen
  je 64 px, und schon heute stehen dort Beschriftungen mit zwölf Zeichen (lb „Restauranten“,
  pt „Restaurantes“, „O meu perfil“), in 12-px-Inter rund 74 px breit. Aufgefallen beim
  Entwurf. — Betreiber.
  ✔ **Entschieden 2026-11-13, Betreiber:** Für Angemeldete ersetzt „Gespeichert“ den Punkt
  „Über uns“; die Leiste bleibt bei vier Punkten. AK-41 und AK-42 neu gefasst, Decision Log 25.
- **OF-05** · Das Herz auf der Detailseite **ohne Titelfoto** steht im Verlaufsband
  (`from-cyan-600 to-purple-700`) und trägt `focus:outline-cyan-700` — der Fokusrahmen liegt auf
  dem Verlauf und maß am Pixel **1,06–1,33 : 1** (Chromium, 1280 und 375 px, `/de/restaurants/12`,
  Tastaturfokus). Vor und nach der Reparatur von BF-206 gleich, also kein Rückschritt des
  Fehlerauftrags, sondern Bestand dieses Features (AK-48 prüfte Fokus und Ansage, nicht die Farbe
  auf dem Band). Gefunden in der Fokus-Umfrage des Fehlerauftrags vom 2026-11-14, nicht im Auftrag,
  deshalb nicht behoben. Die Fassung **mit** Titelfoto (Herz über `from-black/70`) ist nicht
  gemessen — die Dev-Daten haben keine Titelfotos. Naheliegend wäre eine Fokusfarbe je Lage
  (Weiß im Band) — Betreiber bzw. nächste QA.
  ↪ **Bewertet in der zweiten QA vom 2026-11-14:** keine offene Frage, sondern **AK-49 durchgefallen** — AK-49 verlangt
  den sichtbaren Fokusrahmen für jedes Herz. Gemessen auch mit eingesetztem Titelfoto: größte Pixeländerung 1,56 bzw.
  2,48 : 1, also ebenfalls unsichtbar. Befund **BF-218** (*mittel*) in `features/befunde.md`, Behebung über `/sdd-build 17`.
  ↪ **Nachgeprüft in der dritten QA vom 2026-11-14: behoben.** Rahmen innen auf der weißen Fläche des Knopfs; mit drei echten
  Titelfotos und ohne Foto, im Kopf und in der Karte, Chromium und WebKit: schwächster Strahl 13,05 : 1 (`qa-report.md`).

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Wie hängen Herz und Listen zusammen? | Herz + eigene Listen, unabhängig voneinander | Der häufigste Weg bleibt ein einziger Tipp, ohne Dialog und ohne JavaScript; die Listenauswahl bei jedem Merken (wie bei Airbnb) kostete jedes Mal einen Schritt |
| 2 | Wer sieht eine Liste? | Nur der Inhaber; Teilen ist ein eigenes Vorhaben | Ein geteilter Listenname wäre ungesichteter Freitext unter einer `endlech.lu`-Adresse — das Board braucht genau dafür eine Freigabe vor der Veröffentlichung |
| 3 | Was gehört über Merken und Listen hinaus dazu? | Private Notiz und Filter „Nur meine Favoriten“; API und Teilen nicht | Die API hätte heute keinen Nutzer, die native App steht noch auf der Roadmap |
| 4 | Wo setzt man das Herz, wo findet man es wieder? | Herz auf Karte und Detailseite; eigene Seite „Gespeichert“, Punkt der unteren Leiste (seit Decision Log 25 anstelle von „Über uns“), Verweis in Kopfzeile und Profil | Merken dort, wo gesucht wird; wiederfinden mit einem Tipp, nicht über das Profil |
| 5 | Was passiert beim Herz ohne Anmeldung? | Anmelden, dann wird das Merken nachgeholt | Der häufigste Einstieg eines neuen Kontos kostet keinen zweiten Tipp; der Verlust über den Registrierweg (EC-01) ist bewusst hingenommen |
| 6 | Wo legt man ein Lokal in eine Liste? | Nur auf der Detailseite | Die Karten in der Suche bleiben schlank, kein Aufklappmenü je Karte |
| 7 | Worüber geht die Notiz? | Eine Notiz je Lokal | „Seiteneingang nutzen“ gehört zum Ort, nicht zur Liste; zwei Notizen zum selben Ort laufen auseinander |
| 8 | Wie wird das Löschen einer Liste abgesichert? | Rückfrageseite | Funktioniert ohne JavaScript; ein `onsubmit`-Dialog verstößt gegen `script-src-attr` und liefe nach dem Scharfschalten der CSP ohne Rückfrage durch (CLAUDE.md, Sicherheits-Kopfzeilen) |
| 9 | Wie viele Listen, wie lange Namen? | 25 Listen, Name 1–60 Zeichen | Reicht für jeden ernsthaften Gebrauch; 60 Zeichen passen bei 375 px auf eine Zeile |
| 10 | Wie lang darf die Notiz sein? | 500 Zeichen | Ein paar Sätze; je länger der Freitext, desto wahrscheinlicher Gesundheitsangaben |
| 11 | Zwei gleichnamige Listen? | Nein, ohne Unterschied von Groß- und Kleinschreibung | Zwei gleich lautende Häkchen wären nicht unterscheidbar, auch nicht für einen Screenreader |
| 12 | Worauf wirkt der Filter? | Nur auf das Herz | Ein weiteres Auswahlfeld in einem schon vollen Filterbereich; eigene Listen öffnet man auf „Gespeichert“ |
| 13 | Welche Konten dürfen? | Jedes angemeldete, auch unbestätigte | Alles ist privat und wirkt auf niemanden sonst — anders als die Zustimmung im Board, die eine öffentliche Zahl bewegt; Missbrauch begrenzen Obergrenze und Deckel |
| 14 | Wie hoch ist der Deckel? | 120 Änderungen je Stunde, am Konto | Echtes Stöbern stößt nie daran; am Konto, weil der Weg eine Sitzung voraussetzt (Konvention `password_change`); verbraucht wird erst, wenn die Änderung stattfindet (BF-11) |
| 15 | Darf die Verwaltung etwas sehen? | Nein, auch keine Summen | Notizen enthalten vermutlich Gesundheitsangaben; was die Oberfläche nicht zeigt, liest niemand versehentlich |
| 16 | Wird gemessen? | Drei Ereignisse ohne Lokal und Inhalt | Zeigt, ob das Feature benutzt wird, ohne zu verraten, wer was gemerkt hat; die Notiz wird gar nicht gezählt |
| 17 | Reihenfolge auf „Gespeichert“? | Zuletzt hinzugefügt zuerst | Das gerade Gemerkte steht oben, keine Bedienung nötig |
| 18 | Lokal wird gelöscht? | Verschwindet still aus Herz, Listen und Notiz | Einfach und ohne Reste; ein Hinweis verlangte, den Namen nach dem Löschen aufzubewahren |
| 19 | Herz als Umschalter oder mit Absicht? | Mit Absicht: Das Herz sendet „merken“ oder „entfernen“ *(von Claude entschieden)* | Ein Umschalter auf einer veralteten Seite entfernte ein gemerktes Lokal (AK-05) |
| 20 | Woran wird abgenommen? | Telefon-Durchgang ohne Hilfe (AK-51) | Betreiberwahl. Die Kriterien zu Screenreader, Bedienung ohne JavaScript, Export und Löschung bleiben trotzdem: Feature 02, die Projektkonvention und der Sicherheitskatalog verlangen sie — sie sind nur nicht Teil der eigenen Abnahme |
| 21 | Ändert die Notiz die Datenschutzstufe? | Nein, Stufe B bleibt | Unaufgeforderter, privater Freitext, wie in `docs/datenschutz.md` vorgesehen; kein Feld erfasst eine Gesundheitsangabe strukturiert. Die Verarbeitung bekommt dort einen eigenen Abschnitt |
| 22 | Kein Hinweis „bitte keine Gesundheitsangaben“ am Notizfeld? | Kein solcher Hinweis, nur „Nur für dich sichtbar.“ *(von Claude entschieden)* | Anders als beim Board wird die Notiz nicht veröffentlicht, und ihr Zweck ist genau so eine Angabe; der Hinweis sagt, was gilt |
| 23 | Wie viele Karten je Seite? | 12 *(von Claude entschieden)* | Die Suche zeigt 6; eine eigene Liste wird öfter ganz durchgesehen. Zahl nötig, damit AK-12 und AK-25 prüfbar sind |
| 24 | Reihenfolge der unteren Leiste? | ~~Start · Restaurants · Gespeichert · Über uns · Profil~~ *(von Claude entschieden; abgelöst durch 25)* | „Gespeichert“ neben „Restaurants“, weil beides Lokale zeigt; das Profil bleibt rechts außen wie bisher |
| 25 | Fünf Punkte passen bei 320 px nicht (OF-04) — was gilt? | Angemeldet: Start · Restaurants · Gespeichert · Profil; „Gespeichert“ ersetzt „Über uns“ | Vier Punkte behalten 80 px und einzeilige Beschriftungen in allen fünf Sprachen; „Über uns“ ist Information ohne Handlung und bleibt über die Fußzeile erreichbar. Zweizeilige Beschriftungen mit getrennten Wörtern („Restau-rantes“) verworfen |
| 26 | Einführung, Abschnitt „Mit Notiz“, Aufräumen unbestätigter Konten (OF-01 bis OF-03) | OF-01 und OF-02 ⏳ mit Messung nach vier bzw. sechs Wochen (Vorschlag), OF-03 ✖ (2026-11-14, Betreiber) | entscheiden mit einer Zahl aus dem Betrieb statt einer Vermutung; unbestätigte Konten sind laut BF-03 genutzte Konten |
