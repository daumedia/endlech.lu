# 12 · Onboarding für neue Nutzer — Spezifikation

Status: `tasked` · Stand: 2026-10-22 · freigegeben vom Betreiber am 2026-10-22 · Entwurf: `design.md` · Plan: `tasks.md`

Auf der Seite heißt das Onboarding **„Einführung"**; so heißt es auch in dieser Spec.

## Zweck

Wer heute ein Konto anlegt, bestätigt seine Adresse, meldet sich an und landet auf
derselben Startseite wie ein Gast. Niemand sagt ihm, wofür das Konto da ist, und niemand
erklärt, was „Nicht verfügbar" oder „Verifiziert" in einem Eintrag bedeutet. Nach diesem
Feature führt die erste Anmeldung durch eine kurze Einführung — Willkommen, Einträge
lesen, Profilbild, auf Wunsch der Newsletter — und endet in der Restaurantsuche.

Zwei Dinge kommen dabei mit, weil die Einführung sie voraussetzt: ein **Newsletter-Schalter
im Profil** für jedes Konto (löst `04/OF-02`, das bis heute offen ist), und eine
**Willkommens-Mail** an jeden, der über ein Konto zum ersten Mal einwilligt — gepflegt
in Brevo, wie die Kampagnen aus Feature `04`.

**Was ausdrücklich nicht geschieht:** Die Einführung fragt **nicht** nach Einschränkung,
Hilfsmittel oder Bedarf. Eine solche Angabe wäre eine Gesundheitsangabe nach Art. 9
DSGVO und höbe die Datenschutzstufe B auf (siehe `docs/datenschutz.md`, Einstufung).

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 · Registrierung & E-Mail-Bestätigung | `deployed` | Die Bestätigung ist die Voraussetzung; AK-05 ändert den Rückweg des Bestätigungslinks für bereits Angemeldete |
| B02 · Anmeldung mit Passwort | `deployed` | Die Anmeldung entscheidet, wohin es geht (AK-01, AK-03) |
| B04 · Profil, Avatar & Einreichungen | `deployed` | Regeln fürs Profilbild; Ort des neuen Schalters und des Links „Einführung erneut ansehen" |
| B06 · Restaurant-Detailseite | `deployed` | Ihre Begriffe erklärt der Bildschirm „Einträge lesen" (AK-22 bis AK-24) |
| B11 · Restaurant vorschlagen | `deployed` | Ziel des Knopfs auf der Willkommensseite; sein Rückweg nach der Anmeldung hat Vorrang (AK-03) |
| B19 · Admin-Zugang & Dashboard | `deployed` | trägt die Summen (AK-49) und die Rollenschranke davor |
| B24 · Mehrsprachigkeit | `approved` | fünf Sprachen |
| 01 · Betroffenenrechte | `deployed` | Datenexport und Kontolöschung nehmen die neuen Angaben mit |
| 02 · Barrierefreiheit der Plattform | `deployed` | Tastatur, Screenreader, Zielgrößen |
| 04 · Marketing-Kontakte in Brevo | `deployed` | Einwilligung, Abgleich, Widerruf, Attributliste; `04/OF-02` wird hier gelöst |
| 08 · App-Warteliste | `deployed` | Ziel des App-Hinweises; der TestFlight-Link bleibt in deren zweiter Mail |
| 11 · Nutzungsmessung | `deployed` | Ereignisse ohne Personenbezug; „Profil wird nicht gemessen" gilt weiter |

## User Stories

- **US-01** · Als neuer Nutzer möchte ich nach meiner ersten Anmeldung erfahren, was ich
  mit meinem Konto tun kann, damit ich nicht ratlos auf der Startseite stehe.
- **US-02** · Als neuer Nutzer möchte ich verstehen, was die Angaben in einem Eintrag
  bedeuten — vor allem, dass „Nicht verfügbar" auch „nicht erfasst" heißen kann —, damit
  ich mich vor dem Losfahren auf das Richtige verlasse.
- **US-03** · Als neuer Nutzer möchte ich jeden Schritt auslassen und die Einführung
  jederzeit verlassen können, damit sie mich nicht aufhält.
- **US-04** · Als Nutzer, der sich für ein bestimmtes Ziel angemeldet hat — etwa einen
  Vorschlag —, möchte ich dort ankommen und nicht in einer Einführung.
- **US-05** · Als Nutzer möchte ich den Newsletter im Profil an- und abschalten können,
  ohne vorher Werbung bekommen zu haben.
- **US-06** · Als Betreiber möchte ich jedem neuen Abonnenten aus einem Konto einmal eine
  Willkommens-Mail schicken, deren Text ich in Brevo pflege.
- **US-07** · Als Betreiber möchte ich sehen, wie viele neue Konten die Einführung gesehen
  haben und an welcher Stelle Leute aussteigen — ohne dass sich das einer Person zuordnen
  lässt.

## Nicht im Scope

- **Bedarfsabfrage und vorbelegte Filter** (Rollstuhl, Assistenzhund …) — entschieden:
  nein. Sie wäre eine strukturierte Gesundheitsangabe und machte Stufe C fällig.
- **Passkey als eigener Schritt** — nicht gewählt. Passkeys bleiben im Profil (B03).
- **Das Board (06) in der Einführung** — nicht gewählt.
- **Einführung für Bestandskonten** — nur Konten, die nach der Auslieferung bestätigt
  werden (AK-06, AK-07).
- **Einführung in der iOS-App** — die App hat ihre eigenen Abläufe; hier geht es um die
  Website (`/api/v1` bleibt unverändert).
- **Sprache am Konto speichern** — gibt es nicht; die Einführung läuft in der Sprache der
  Sitzung wie jede andere Seite.
- **Wortlaut der Willkommens-Mail** — gepflegt in Brevo. Die Spec legt fest, wer sie
  bekommt und was sie mindestens enthält (AK-40 bis AK-48), nicht den Text.
- **Weitere Mails** (Erinnerungen, Folgemails) — es gibt genau eine Willkommens-Mail.
- **Bestätigungslink meldet an** — nicht gewählt. B01 bleibt, außer im Sonderfall von
  AK-05.
- **„Nicht verfügbar" auf der Detailseite aufspalten** — gehört zu B06, siehe OF-02.
- **Profilbild öffentlich zeigen** — es erscheint weiter nur im eigenen Profil und in der
  Kopfzeile.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar. „Startseite" meint `/{locale}/`,
„Restaurantsuche" meint `/{locale}/restaurants`.

### Wann die Einführung erscheint (US-01, US-04)

- **AK-01** · Angenommen, ein bestätigtes Konto hat die Einführung noch nie gesehen, wenn
  es sich anmeldet und die Anmeldung heute auf der Startseite enden würde (keine zuvor
  aufgerufene Seite, zu der sie zurückführen soll), dann landet es auf der
  **Willkommensseite** der Einführung statt auf der Startseite.
- **AK-02** · Angenommen, die Willkommensseite wurde diesem Konto einmal angezeigt, wenn
  es sich danach erneut anmeldet, dann landet es auf der Startseite — die Einführung
  erscheint **nie ein zweites Mal von selbst**, gleich ob sie beendet, vorzeitig beendet
  oder mitten im Ablauf verlassen wurde.
- **AK-03** · Angenommen, die erste Anmeldung soll auf eine zuvor aufgerufene Seite
  zurückführen — etwa auf die Erklärseite „Restaurant vorschlagen" (B11/AK-01) —, wenn
  sie gelingt, dann landet der Nutzer **dort** und nicht in der Einführung; die Einführung
  gilt danach als **nicht** gesehen und erscheint bei der nächsten Anmeldung nach AK-01.
- **AK-04** · Angenommen, ein Konto ist noch nicht bestätigt, wenn es sich anmeldet
  (B01/AK-13 erlaubt das), dann erscheint die Einführung nicht, und sie gilt weiter als
  nicht gesehen.
- **AK-05** · Angenommen, jemand ist mit einem noch unbestätigten Konto angemeldet, wenn
  er im selben Browser den gültigen Bestätigungslink **dieses** Kontos öffnet, dann ist
  das Konto bestätigt und er landet direkt auf der Willkommensseite — nicht auf der
  Anmelde- oder der Startseite.
- **AK-06** · Angenommen, ein Konto war am Tag der Auslieferung dieses Features bereits
  bestätigt, wenn es sich danach anmeldet, dann erscheint die Einführung nicht — weder
  bei der ersten noch bei einer späteren Anmeldung.
- **AK-07** · Angenommen, ein Konto war am Tag der Auslieferung angelegt, aber noch nicht
  bestätigt, wenn es danach bestätigt wird und sich anmeldet, dann erscheint die
  Einführung nach AK-01.
- **AK-08** · Angenommen, ein Konto wurde über die iOS-App angelegt und bestätigt, wenn
  es sich zum ersten Mal auf der Website anmeldet, dann erscheint die Einführung nach
  AK-01.
- **AK-09** · Angenommen, ein Konto hat die Einführung gesehen, wenn es im Profil
  „Einführung erneut ansehen" wählt, dann beginnt sie von vorn auf der Willkommensseite.
- **AK-10** · Angenommen, ein Gast ruft eine Seite der Einführung direkt auf, wenn die
  Anfrage durchläuft, dann wird er zur Anmeldung geleitet und sieht keinen Inhalt der
  Einführung.
- **AK-11** · Angenommen, ein angemeldetes, **unbestätigtes** Konto ruft eine Seite der
  Einführung direkt auf, wenn die Anfrage durchläuft, dann landet es auf der Hinweisseite
  zur Bestätigung (`/{locale}/verify`), wie beim Vorschlagen (B11/AK-02).

### Ablauf (US-01, US-03)

- **AK-12** · Angenommen, die Einführung beginnt, wenn auf jedem Bildschirm „Weiter"
  gewählt wird, dann folgen genau diese Bildschirme in dieser Reihenfolge:
  **1 Willkommen · 2 Einträge lesen · 3 Profilbild · 4 Newsletter** — und danach die
  Restaurantsuche.
- **AK-13** · Angenommen, das Konto hat der Werbung bereits zugestimmt (bei der
  Registrierung oder im Profil), wenn die Einführung läuft, dann entfällt der
  Newsletter-Bildschirm, und der letzte Bildschirm ist das Profilbild.
- **AK-14** · Angenommen, ein beliebiger Bildschirm der Einführung, wenn man ihn
  betrachtet, dann steht dort als Text „Schritt N von M", wobei M die tatsächliche Zahl
  der Bildschirme ist (4, oder 3 nach AK-13).
- **AK-15** · Angenommen, auf einem Bildschirm wurde nichts getan, wenn „Weiter" gewählt
  wird, dann erscheint der nächste Bildschirm ohne Fehlermeldung und ohne dass etwas
  gespeichert wird — **kein Schritt ist Pflicht**.
- **AK-16** · Angenommen, Bildschirm 2 oder später, wenn „Zurück" gewählt wird, dann
  erscheint der vorige Bildschirm.
- **AK-17** · Angenommen, ein beliebiger Bildschirm, wenn „Einführung beenden" gewählt
  wird, dann landet man in der Restaurantsuche. Auf dem letzten Bildschirm heißt der
  Knopf „Weiter" stattdessen **„Restaurants suchen"** und führt ebenfalls dorthin.
- **AK-18** · Angenommen, ein Bildschirm der Einführung ist offen, wenn man Kopfzeile und
  — auf dem Telefon — die untere Navigationsleiste betrachtet, dann sind beide vorhanden
  und benutzbar; wer darüber wegnavigiert, hat die Einführung gesehen (AK-02).
- **AK-19** · Angenommen, eine Person, die Endlech.lu nicht kennt, meldet sich mit einem
  neuen Testkonto an, wenn sie die Einführung von der Willkommensseite bis zur
  Restaurantsuche durchläuft, dabei jeden Text liest und kein Bild hochlädt, dann ist sie
  in **unter zwei Minuten** in der Suche. *(Abnahmekriterium des Betreibers.)*

### Bildschirm 1 · Willkommen

- **AK-20** · Angenommen, die Willkommensseite ist offen, wenn man sie liest, dann begrüßt
  sie mit dem Namen des Kontos und nennt zwei Wege mit je einem Knopf:
  **Restaurant vorschlagen** (führt zum Vorschlags-Assistenten, B11) und
  **App-Warteliste** (führt zu `/{locale}/app`, Feature 08). Wer einen der beiden wählt,
  verlässt die Einführung; sie gilt als gesehen.
- **AK-21** · Angenommen, alle Texte der Einführung, wenn nach Versprechen gesucht wird,
  dann verspricht **keiner** Bewertungen, Kommentare oder Favoriten und fordert nirgends
  zum „Bewerten" auf — beides gibt es nicht (PRD, Risiko 1).

### Bildschirm 2 · Einträge lesen (US-02)

- **AK-22** · Angenommen, der Bildschirm „Einträge lesen" ist offen, wenn man ihn liest,
  dann erklärt er genau diese Punkte:
  (a) das Abzeichen **„Verifiziert"** heißt: von Endlech.lu persönlich vor Ort geprüft,
  mit Datum der Prüfung;
  (b) **„Nicht verfügbar"** bei einem Merkmal heißt: nicht vorhanden **oder** nicht
  erfasst — die Seite unterscheidet das nicht;
  (c) **Türbreite und Tischabstand** stehen in Zentimetern, „unter 90 cm" heißt unter dem
  Maß der DIN 18040, „Nicht ausgemessen" heißt: niemand hat gemessen;
  (d) die **Zahl auf der Restaurantkarte** — nur, wenn OF-01 entschieden ist; bis dahin
  fehlt der Punkt.
- **AK-23** · Angenommen, der Bildschirm „Einträge lesen" und eine Detailseite in
  derselben Sprache, wenn die erklärten Begriffe verglichen werden, dann sind sie
  **wortgleich** mit denen der Detailseite („Verifiziert", „Nicht verfügbar", „Nicht
  ausgemessen").
- **AK-24** · Angenommen, der Bildschirm „Einträge lesen" ist offen, wenn man ihn liest,
  dann steht dort sinngemäß, dass ein Eintrag zeigt, **was erfasst ist, nicht was vor Ort
  gilt** — derselbe Grundsatz wie unter „Wie die Punktzahl entsteht" auf `/criteria`.

### Bildschirm 3 · Profilbild

- **AK-25** · Angenommen, eine JPG-, PNG- oder WebP-Datei bis 2 MB wird gewählt, wenn sie
  hochgeladen wird, dann steht das Bild sofort in der Kopfzeile und auf diesem Bildschirm,
  und im Profil ist es dasselbe Bild.
- **AK-26** · Angenommen, eine Datei über 2 MB oder eines anderen Typs wird gewählt, wenn
  sie hochgeladen wird, dann erscheint am Feld dieselbe Meldung wie im Profil, der
  Bildschirm bleibt stehen, **nichts wird gespeichert**, und „Weiter" bleibt möglich.
- **AK-27** · Angenommen, eine Textdatei wurde in `bild.jpg` umbenannt, wenn sie
  hochgeladen wird, dann wird sie wie ein falscher Dateityp abgewiesen (AK-26).
- **AK-28** · Angenommen, der Bildschirm ist offen, wenn sein Text gelesen wird, dann steht
  dort, wo das Bild erscheint (eigenes Profil, Kopfzeile), und **nirgends**, dass andere es
  sehen.
- **AK-29** · Angenommen, das Konto hat schon ein Profilbild (etwa nach AK-09), wenn der
  Bildschirm erscheint, dann zeigt er das vorhandene Bild, und ein neues Hochladen ersetzt
  es.

### Bildschirm 4 · Newsletter

- **AK-30** · Angenommen, der Newsletter-Bildschirm erscheint, wenn man das Häkchen
  betrachtet, dann ist es **nicht vorangehakt** (wie `04/AK-02`).
- **AK-31** · Angenommen, der Newsletter-Bildschirm ist offen, wenn sein Text gelesen wird,
  dann steht dort: was man bekommt (Neuigkeiten von Endlech.lu, darunter einmal eine
  Willkommens-Mail), dass man jederzeit im Profil oder über den Link in jeder Mail
  abbestellen kann, und dass ohne Häkchen nichts geschieht.
- **AK-32** · Angenommen, das Häkchen bleibt leer, wenn „Restaurants suchen" gewählt wird,
  dann ist danach **keine** Einwilligung gespeichert und kein Kontakt in Brevo entstanden.
- **AK-33** · Angenommen, das Häkchen ist gesetzt, wenn „Restaurants suchen" gewählt wird,
  dann ist die Einwilligung mit Zeitpunkt am Konto gespeichert, und **innerhalb von 15
  Minuten** steht in Brevo ein Kontakt mit dieser Adresse, Herkunft „Nutzerkonto" und
  genau den Attributen aus `04/AK-07`.

### Newsletter-Schalter im Profil (US-05)

- **AK-34** · Angenommen, ein angemeldetes Konto öffnet sein Profil, wenn es den Abschnitt
  zum Newsletter betrachtet, dann sieht es den aktuellen Stand — „abonniert seit
  <Datum>" oder „nicht abonniert" — und einen Schalter, der ihn umkehrt.
- **AK-35** · Angenommen, der Stand ist „nicht abonniert", wenn der Schalter betätigt wird,
  dann gilt dasselbe wie in AK-33.
- **AK-36** · Angenommen, der Stand ist „abonniert", wenn der Schalter betätigt wird, dann
  zeigt das Profil „nicht abonniert", die Einwilligung ist am Konto zurückgenommen, und
  **innerhalb von 15 Minuten** bekommt diese Adresse aus Brevo keine Kampagne und keine
  Willkommens-Mail mehr.
- **AK-37** · Angenommen, jemand hat über den Abmeldelink einer Brevo-Mail abbestellt
  (`04/AK-11`), wenn er danach — spätestens 15 Minuten später — sein Profil öffnet, dann
  steht dort „nicht abonniert".
- **AK-38** · Angenommen, ein **unbestätigtes** Konto schaltet den Newsletter ein, wenn
  danach geprüft wird, dann ist die Einwilligung gespeichert, aber **kein** Kontakt in
  Brevo (`04/AK-05`); das Profil sagt, dass sie mit der Bestätigung der Adresse wirksam
  wird. Nach der Bestätigung gilt AK-33.
- **AK-39** · Angenommen, der Newsletter ist aus, wenn das Konto irgendetwas anderes tut
  (vorschlagen, abstimmen, Profil ändern), dann gelingt das unverändert — die Einwilligung
  ist für nichts eine Bedingung.

### Willkommens-Mail (US-06)

*Plattformeinstellung in Brevo — als Konfigurationsaufgabe in `tasks.md`, Nachweis in
`qa-report.md` als Bildschirmfoto der Automation und Testempfang an ein echtes Postfach.*

- **AK-40** · Angenommen, ein Konto willigt über Registrierung, Einführung oder Profil zum
  ersten Mal ein und ist bestätigt, wenn danach das Postfach geprüft wird, dann liegt
  **innerhalb von 30 Minuten** die Willkommens-Mail dort, in der Sprache des Kontakts
  (eine der fünf).
- **AK-41** · Angenommen, die Willkommens-Mail ist angekommen, wenn ihr Abmeldelink
  geklickt wird, dann ist die Einwilligung zurückgenommen (`04/AK-11`) und das Profil
  zeigt „nicht abonniert" (AK-37).
- **AK-42** · Angenommen, ein Konto hat nicht eingewilligt, wenn es bestätigt wird und die
  Einführung durchläuft, dann bekommt es **keine** Willkommens-Mail.
- **AK-43** · Angenommen, eine Adresse steht nur als Kontakt einer Warteliste (Partner,
  Organisation, App) in Brevo, wenn sie dort neu angelegt wird, dann bekommt sie
  **keine** Willkommens-Mail für Konten.
- **AK-44** · Angenommen, ein Konto hat die Willkommens-Mail bekommen, abbestellt und
  später erneut eingewilligt, wenn danach das Postfach geprüft wird, dann ist **keine
  zweite** Willkommens-Mail gekommen — höchstens eine je Adresse.
- **AK-45** · Angenommen, ein Kontakt stand bereits in Brevo, bevor die Automation
  eingerichtet wurde, wenn sie eingeschaltet wird, dann bekommt er keine Willkommens-Mail.
- **AK-46** · Angenommen, jemand willigt ein und schaltet im Profil wieder ab, bevor die
  Willkommens-Mail verschickt ist, wenn danach das Postfach geprüft wird, dann ist keine
  gekommen.
- **AK-47** · Angenommen, die Willkommens-Mail wird gelesen, wenn ihr Inhalt geprüft wird,
  dann nennt sie mindestens die Restaurantsuche, das Vorschlagen und die App-Warteliste,
  verweist für die App auf die **Warteliste** `/{locale}/app` und **nicht** direkt auf
  TestFlight (der Link gehört in die zweite Mail von Feature 08), und für sie gilt AK-21.
- **AK-48** · Angenommen, die Willkommens-Mail wird gelesen, wenn nach persönlichen Angaben
  gesucht wird, dann steht darin höchstens der Name zur Anrede — keine Angaben, die nicht
  in `04/AK-07` stehen.

### Verwaltung und Messung (US-07)

- **AK-49** · Angenommen, ein Admin öffnet `/{locale}/admin`, wenn er die Kennzahlen
  betrachtet, dann stehen dort zwei Zahlen: Konten, die seit der Auslieferung bestätigt
  wurden, und davon die, denen die Einführung angezeigt wurde — **ohne Namen, ohne Liste,
  ohne Verweis auf einzelne Konten**.
- **AK-50** · Angenommen, die Einführung wird durchlaufen, wenn die an Umami übermittelten
  Ereignisse geprüft werden, dann gibt es genau drei Arten: **Einführung begonnen**
  (Willkommensseite angezeigt), **Einführung abgeschlossen** (über „Restaurants suchen"
  auf dem letzten Bildschirm) und **Einführung vorzeitig beendet** (über „Einführung
  beenden", mit der Nummer des Bildschirms).
- **AK-51** · Angenommen, ein Konto durchläuft die Einführung, lädt ein Bild hoch und
  abonniert den Newsletter, wenn die übermittelten Ereignisse geprüft werden, dann steht
  darin **weder** Konto, Name, E-Mail-Adresse **noch**, ob abonniert oder ein Bild
  hochgeladen wurde — nur Ereignisart und Bildschirmnummer.
- **AK-52** · Angenommen, der Newsletter-Schalter im Profil wird benutzt, wenn die
  übermittelten Ereignisse geprüft werden, dann ist **keins** entstanden — die Zusage
  „Verwaltung und Profil werden nicht gemessen" auf `/legal` bleibt wahr.

### Datenschutz und Missbrauchsschutz

Der Katalog `~/.claude/sdd/sicherheit.md` ist vollständig durchgegangen. Was nicht
zutrifft, steht als solches darunter.

**1 · Personenbezogene Daten**

- **AK-53** · Angenommen, ein Konto hat die Einführung durchlaufen, wenn geprüft wird, was
  dieses Feature am Konto speichert, dann sind es **genau zwei Angaben**: ob die Einführung
  für dieses Konto vorgesehen ist (Ja/Nein, gesetzt beim Bestätigen der Adresse) und wann
  sie zum ersten Mal angezeigt wurde. Keine Schritte, keine Klicks, keine Verweildauer.
  Profilbild und Einwilligung liegen in den Feldern, die es schon gibt.
  *Geändert 2026-10-22 im Entwurf (OF-05). Bisher: „genau ein Datum — wann die Einführung
  angezeigt wurde".*
- **AK-54** · Angenommen, alle Bildschirme der Einführung, wenn nach Eingabefeldern gesucht
  wird, dann gibt es außer dem Bild-Upload und dem Newsletter-Häkchen **keine** —
  insbesondere keine Frage nach Einschränkung, Hilfsmittel oder Bedarf. Die
  Datenschutzstufe B bleibt bestehen.
- **AK-55** · Angenommen, ein Konto durchläuft die Einführung, lädt ein Bild hoch und
  schaltet den Newsletter um, wenn danach die Protokolle gelesen werden, dann steht dort
  weder E-Mail-Adresse noch Name noch der ursprüngliche Dateiname des Bildes.

**2 · Weitergabe an externe Dienste**

- **AK-56** · Angenommen, ein Konto willigt über die Einführung oder das Profil ein, wenn
  der Kontakt in Brevo betrachtet wird, dann trägt er **nur** die Attribute aus
  `04/AK-07` — weder das Datum der Einführung noch das Profilbild noch, auf welchem Weg
  eingewilligt wurde.
- Brevo ist bereits Auftragsverarbeiter (`docs/datenschutz.md`); dieses Feature fügt
  **keinen neuen Dienst** hinzu. Die Willkommens-Mail ist Werbung an Einwilligende — dieselbe
  Rechtsgrundlage wie die Kampagnen aus Feature 04 (Art. 6 Abs. 1 lit. a).
- Umami: siehe AK-51 und AK-52.
- Training durch einen Dienst: **trifft nicht zu**, weil kein KI-Dienst beteiligt ist.

**3 · Zugriff**

- **AK-57** · Angenommen, Konto A ist angemeldet, wenn es den Newsletter-Schalter, den
  Newsletter-Schritt oder den Bild-Upload benutzt und dabei eine Kennung von Konto B
  mitschickt, dann ändert sich **nur Konto A**. Die Seiten der Einführung tragen keine
  Kontokennung in der Adresse; einen fremden Datensatz gibt es hier nicht aufzurufen.
- **AK-58** · Angenommen, eine fremde Website schickt ein Formular an den
  Newsletter-Schalter, den Newsletter-Schritt oder den Bild-Upload, wenn die Anfrage
  eintrifft, dann wird sie abgewiesen und **nichts** ändert sich.
- Rollen: Admins sehen nur die Summen aus AK-49; die Rollenschranke davor ist B19.

**4 · Missbrauch und Kosten**

- **AK-59** · Angenommen, ein Konto hat den Newsletter in einer Stunde **zehnmal**
  umgeschaltet (Profil und Einführung zusammengezählt), wenn es ein **elftes** Mal
  umschaltet, dann bleibt der Stand unverändert, es erscheint „Zu viele Änderungen, bitte
  in N Minuten erneut", und nichts geht an Brevo. Gezählt wird **am Konto**: Derselbe
  Versuch von einer anderen IP scheitert ebenso.
- **AK-60** · Angenommen, ein Konto schickt den Schalter oder den Schritt ab, ohne den
  Stand zu ändern (etwa „abonnieren", obwohl schon abonniert), wenn danach das Kontingent
  geprüft wird, dann ist **nichts verbraucht** — gezählt wird ein Wechsel, nicht ein
  Absenden (BF-11).
- Bild-Upload: **kein eigener Deckel**, weil er weder eine Mail auslöst noch ein Geheimnis
  prüft noch den Bestand lädt (Projektkonvention); Größe und Typ wie im Profil (AK-25 bis
  AK-27). Der Plattenplatz bleibt je Konto auf eine Datei begrenzt, weil jeder Upload das alte
  Bild ersetzt — auch bei gleichzeitigen Uploads (Sperre je Konto, BF-173). *Bestätigt
  2026-10-23 nach `OF-06`.*
- Kosten: Jede Willkommens-Mail verbraucht Brevo-Kontingent. Begrenzt durch „höchstens
  eine je Adresse" (AK-44) und den Deckel des Schalters (AK-59).

**5 · Löschen und Auskunft**

- **AK-61** · Angenommen, ein Konto hat die Einführung gesehen und den Newsletter
  abonniert, wenn es sich löscht (`01/AK-04`), dann sind danach das Datum der Einführung,
  die Einwilligung, das Profilbild und der Brevo-Kontakt (`04/AK-14`) weg.
- **AK-62** · Angenommen, ein Konto fordert seinen Datenexport an (Feature 01), wenn es ihn
  öffnet, dann stehen darin das Datum der Einführung und der Newsletter-Stand mit
  Zeitpunkt.
- E-Mail-Adresse danach wieder frei: unverändert durch Feature 01 geregelt; dieses Feature
  ändert daran nichts.

**6 · Geheimnisse**

- **AK-63** · Angenommen, die Seiten der Einführung und das Profil werden im Browser
  geöffnet, wenn ihr Quelltext nach einem Brevo-Schlüssel durchsucht wird, dann ist keiner
  enthalten.
- Neue Schlüssel: **trifft nicht zu**, weil die Automation in der Oberfläche von Brevo
  eingerichtet wird und der bestehende Schlüssel auf dem Server bleibt.

### Mehrsprachigkeit und Zugänglichkeit

*Nicht aus der Abnahmefrage, sondern aus Projektregeln: `CatalogueCompletenessTest`
verlangt vollständige Kataloge, Feature 02 die Bedienbarkeit für alle Seiten.*

- **AK-64** · Angenommen, die Einführung wird nacheinander in lb, de, fr, en und pt
  geöffnet, wenn ihre Texte gelesen werden, dann ist jeder Text in der jeweiligen Sprache
  — **kein** deutscher Rückfall.
- **AK-65** · Angenommen, die Sprache wird mitten in der Einführung umgeschaltet, wenn die
  Seite neu erscheint, dann steht man auf demselben Bildschirm in der neuen Sprache.
- **AK-66** · Angenommen, die Einführung wird nur mit der Tastatur bedient, wenn man von
  der Willkommensseite bis zur Suche geht und dabei ein Bild hochlädt und den Newsletter
  anhakt, dann ist jedes Bedienelement in sichtbarer Reihenfolge erreichbar und der Fokus
  jederzeit sichtbar.
- **AK-67** · Angenommen, ein Screenreader ist aktiv, wenn ein neuer Bildschirm erscheint,
  dann wird zuerst seine Überschrift samt „Schritt N von M" vorgelesen; eine Fehlermeldung
  beim Bild-Upload wird am Feld vorgelesen.
- **AK-68** · Angenommen, die Einführung wird auf einem Telefon geöffnet, wenn die Knöpfe
  ausgemessen werden, dann ist jeder mindestens 44 × 44 px groß.

## Edge Cases

- **EC-01** · Zwei Geräte: Die Einführung erscheint auf Gerät A, danach meldet sich
  dasselbe Konto auf Gerät B an → auf B erscheint sie nicht mehr (gesehen ab der Anzeige
  der Willkommensseite, AK-02).
- **EC-02** · Die Verbindung bricht mitten im Bild-Upload ab → nichts gespeichert, der
  Bildschirm erscheint erneut, „Weiter" ist möglich.
- **EC-03** · Brevo ist nicht erreichbar, während jemand abonniert → die Einwilligung ist
  gespeichert, die Übertragung wird nachgeholt (`04/AK-17` bis `AK-19`); der Nutzer sieht
  keinen Fehler, die Willkommens-Mail kommt später.
- **EC-04** · Der Bestätigungslink wird geöffnet, während ein **anderes** Konto angemeldet
  ist → das andere Konto bleibt angemeldet und bekommt keine Einführung; das bestätigte
  Konto bekommt sie bei seiner ersten Anmeldung (AK-01).
- **EC-05** · Abgelaufener Bestätigungslink → wie B01 (Hinweisseite, erneut anfordern),
  keine Einführung.
- **EC-06** · Die Sitzung ist abgelaufen und „Angemeldet bleiben" meldet beim Aufruf einer
  Seite automatisch wieder an → die aufgerufene Seite erscheint, nicht die Einführung
  (AK-03: es gibt ein Ziel); sie bleibt offen für die nächste Anmeldung über die
  Anmeldeseite.
- **EC-07** · Ein Konto, das nie angemeldet war, setzt sein Passwort zurück (Feature 01)
  und meldet sich danach an → AK-01 gilt.
- **EC-08** · Im zweiten Tab wurde schon abonniert, im ersten steht der Newsletter-Schritt
  noch offen, und das Häkchen wird gesetzt → es ändert sich nichts, kein Kontingent wird
  verbraucht (AK-60), keine zweite Mail (AK-44).
- **EC-09** · Neustart über „Einführung erneut ansehen" → zählt in Umami wieder als
  „begonnen"; die Summe in der Verwaltung (AK-49) ändert sich nicht, weil sie Konten
  zählt, nicht Durchläufe.
- **EC-10** · Das Konto wird in einem anderen Tab gelöscht, während die Einführung offen
  ist → der nächste Schritt führt zur Anmeldeseite, nichts wird gespeichert.

## Offene Fragen

- **OF-01** · **Was bedeutet die Zahl auf der Restaurantkarte, und wie erklärt die
  Einführung sie?** Die Liste zeigt `rating` (0–10, redaktionell gepflegt, laut
  `docs/data-model.md`). Das ist **nicht** die Punktzahl von `/open` aus acht Merkmalen,
  liegt aber im selben Wertebereich — und öffentlich erklärt ist sie nirgends. Bis zur
  Antwort lässt AK-22 (d) sie aus: lieber keine Erklärung als eine falsche. — Betreiber,
  vor `/sdd-architektur 12`.
  ✔ **Beantwortet 2026-10-23 (Betreiber): durch die berechnete Punktzahl ersetzen.** Die Karte soll künftig die
  Punktzahl aus acht Merkmalen zeigen (`AccessibilityScore`, wie auf `/open`, „nicht gemessen heißt nicht erfüllt“)
  statt der von Hand gepflegten Zahl `rating`. Das wird **Feature 13** (`features/index.md`, `roadmap`). AK-22 (d)
  bleibt bis dahin ausgelassen; die Einführung erklärt die Zahl erst, wenn Feature 13 sie ersetzt hat.
- **OF-02** · *(Fund für B06, hier nicht zu lösen)* Die Detailseite zeigt „Nicht
  verfügbar" für „gibt es nicht" **und** für „nicht erfasst" — `Restaurant` führt `bool`,
  und „Weiß nicht" wird bei der Freigabe zu „Nein". Die Einführung sagt das ehrlich
  (AK-22 b), behebt es aber nicht. Soll das als eigenes Feature in `features/index.md`?
  — Betreiber.
  ✔ **Beantwortet 2026-10-23 (Betreiber): ja, eigenes Feature 14** (`features/index.md`, `roadmap`). Die Einführung
  behält ihren ehrlichen Hinweis (AK-22 b), bis Feature 14 die Detailseite aufspaltet; dann zieht AK-22 (b) mit.
- **OF-03** · **Herkunftskonflikt** (`04/OF-04`): Steht eine Adresse schon als
  Wartelisten-Kontakt in Brevo und willigt dann über ein Konto ein, ist offen, welche
  Herkunft gilt — und damit, ob AK-40 oder AK-43 greift. — Betreiber, zusammen mit
  `04/OF-04`.
  *Vorgabe aus dem Entwurf (Entscheidung 8), bis zur Antwort:* Wer schon als
  Wartelisten-Kontakt in Brevo steht, bekommt **keine** Willkommens-Mail.
  ✔ **Beantwortet 2026-10-23 (Betreiber): so lassen.** Wer schon als Wartelisten-Kontakt in Brevo steht, bekommt
  beim späteren Abonnieren über ein Konto **keine** Willkommens-Mail — er kennt Endlech.lu aus der Wartelisten-Mail.
  Technisch: Die Automation startet nur beim Neuzugang zur Liste; im Auftragsbuch gewinnt die zuletzt eingetragene
  Herkunft (`MarketingContactRegistry::record()`). AK-40 und AK-43 unverändert. `04/OF-04` (welche Herkunft gilt)
  ist damit im Code beantwortet („die letzte“), dort aber noch nicht eingetragen — gehört in eine Runde zu Feature 04.
- **OF-04** · **Erlaubt der Brevo-Tarif eine Automation** mit Bedingung auf Herkunft und
  Sprache und der Regel „einmal je Kontakt"? Falls nicht, wäre die verworfene Variante
  (Mail aus Endlech.lu, eigener Abmeldeweg) neu zu entscheiden — mit Folgen für
  AK-40 bis AK-48. — Betreiber, im Brevo-Konto nachsehen, vor `/sdd-tasks 12`.
  ✔ **Beantwortet 2026-10-23 (festgestellt beim Einrichten von T06, Testempfang vom Betreiber bestätigt): ja.**
  Der kostenlose Tarif trägt Einstieg über die Liste, Bedingung auf `ORIGIN` und `LOCALE` und „jeder Kontakt nur
  einmal“ (Wiedereintritt aus). Nur mehrere Zweige je Verzweigung kosten extra; die Sprachweiche ist deshalb eine
  Kette aus Ja/Nein-Verzweigungen. Die verworfene Variante (Mail aus Endlech.lu) bleibt verworfen; AK-40 bis AK-48
  unverändert. Aufbau in `willkommens-mail/README.md`, Nachweise in `qa/12/t06/`.
- **OF-05** · *(beim Entwurf gefunden)* **AK-53 verlangte „genau ein Datum" — der Entwurf
  braucht zusätzlich einen Ja/Nein-Merker.** Ohne ihn lassen sich Bestandskonten (AK-06)
  von Konten, die erst nach der Auslieferung bestätigt werden (AK-07), nicht unterscheiden:
  Das Konto kennt keinen Bestätigungszeitpunkt, ein Stichtag am Anlagedatum verfehlt AK-07,
  und ein für Bestandskonten eingetragenes Datum behauptete im Datenexport, sie hätten die
  Einführung gesehen. Der Merker sagt nichts über Verhalten aus, nur ob die Einführung
  vorgesehen ist. AK-53 ist entsprechend neu gefasst (Vermerk am Kriterium). — Betreiber;
  bis zu einem Widerspruch gilt die neue Fassung.
  ✔ **Beantwortet 2026-10-23 (Betreiber): die neue Fassung von AK-53 gilt** — zwei Angaben, Merker „vorgesehen“ und
  Datum der ersten Anzeige. AK-53 bleibt, wie es steht; so ist es gebaut und geprüft.
- **OF-06** · *(aus der QA-Nachprüfung, BF-173)* **Bekommt der Bild-Upload doch einen Deckel?**
  Die Spec verneint es mit der Begründung, jeder Upload ersetze das alte Bild. Das galt nur
  nacheinander; gleichzeitige Uploads ließen Dateien ohne Konto liegen. Seit 2026-10-23 räumt der
  Upload-Dienst unter einer Sperre je Konto auf, die Begründung trägt damit wieder. Offen bleibt, ob
  ein Deckel trotzdem gewollt ist (Plattenplatz bei absichtlich vielen Uploads nacheinander). —
  Betreiber, `/sdd-klaeren 12`.
  ✔ **Beantwortet 2026-10-23 (Betreiber): kein Deckel.** Je Konto liegt immer höchstens eine Datei (≤ 2 MB) auf der
  Platte — jeder Upload ersetzt das alte Bild, auch bei gleichzeitigen Uploads (Sperre je Konto seit BF-173). Ein
  Deckel schützte nur noch Bandbreite und träfe, wer mehrere Fotos ausprobiert. Missbrauchsschutz-Abschnitt
  unverändert, Begründung dort ergänzt.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Form | Willkommensseite + geführte Schritte + Willkommens-Mail; keine Checkliste im Profil | Seite und Schritte bilden **einen** Ablauf; die Mail erreicht auch, wer die Einführung überspringt |
| 2 | Auslöser | erste Anmeldung nach der Bestätigung | Unbestätigte Adressen sind nicht belegt; der Bestätigungslink bleibt ein Bestätigungs-, kein Anmeldeweg |
| 3 | Ziel am Ende | Restaurantsuche | Der Kern der Plattform; das Konto ist Mittel, nicht Zweck |
| 4 | Bedarfsabfrage | nein | wäre eine Gesundheitsangabe nach Art. 9 → Stufe C |
| 5 | Schritte | Einträge lesen, Profilbild, Newsletter; kein Passkey | Passkey bleibt im Profil; die Reihenfolge ist ein Vorschlag des Entwurfs: der Newsletter zuletzt, damit er nicht als Eintrittspreis wirkt |
| 6 | Abbruch | einmal gezeigt heißt erledigt; im Profil erneut aufrufbar | kein Drängeln |
| 7 | Mail-Inhalt | mit Neuigkeiten → nur an Einwilligende | Werbung braucht eine Einwilligung |
| 8 | Bestandskonten | keine Einführung | wer sich auskennt, braucht keine |
| 9 | Mail-Quelle | Brevo-Automation | passt zu Feature 04 („Kampagnen leben in Brevo"); Abmeldelink von dort |
| 10 | Widerruf | Schalter im Profil, beide Richtungen | Art. 7 Abs. 3 — so einfach wie die Einwilligung; löst `04/OF-02` mit |
| 11 | Willkommensseite | Vorschlagen und App-Warteliste | Board und Passkey nicht gewählt |
| 12 | Ziel vor Einführung | Ziel zuerst, Einführung später | B11 sagt den Rückweg zu; wer mit einer Absicht kommt, wird nicht umgeleitet |
| 13 | Gespeicherter Zustand | nur ein Datum | Datenminimierung; reicht für „kommt nicht wieder" |
| 14 | Deckel Schalter | 10 Wechsel je Stunde, am Konto | der Schalter löst über Brevo Mails aus; Angriff setzt ein Konto voraus, die IP wechselt mühelos |
| 15 | Empfänger der Mail | jedes Konto, einmal je Adresse | braucht kein neues Brevo-Feld; `04/AK-07` bleibt unverändert |
| 16 | Sichtbarkeit in der Verwaltung | nur Summen | keine personenbezogene Auswertung |
| 17 | Messung | Umami-Ereignisse ohne Personenbezug | zeigt, wo Leute aussteigen, ohne Konto- oder Schrittdaten |
| 18 | Rahmen | normale Kopfzeile bleibt | keine Sackgasse |
| 19 | Angemeldet beim Bestätigen | Einführung startet sofort | sonst kann es mit „Angemeldet bleiben" Wochen dauern |
| 20 | Abnahme | unter zwei Minuten; Mail kommt in Brevo an | Betreiberwahl. „Ohne JavaScript" nicht gewählt; Sprachen und Bedienbarkeit trotzdem als Kriterien, weil Projektregeln sie verlangen |
| 21 | 30 Minuten für die Willkommens-Mail (AK-40) | Vorschlag des Entwurfs | 15 Minuten Abgleich (`04/AK-10`) plus Vorlauf der Automation |
| 22 | Brevo-Tarif (`OF-04`) | Automation im kostenlosen Tarif, Ja/Nein-Kette statt Mehrfachzweig | beim Einrichten festgestellt (2026-10-23); kein Grund, die Mail aus der Anwendung zu schicken |
| 23 | Gespeicherter Zustand (`OF-05`) | Merker „vorgesehen“ + Datum der ersten Anzeige (AK-53 neu gefasst bestätigt) | löst Nr. 13 ab: ohne Merker keine Unterscheidung von Bestandskonten, ein Datum für sie wäre im Export falsch |
| 24 | Herkunftskonflikt (`OF-03`) | Wartelisten-Kontakt, der später über ein Konto abonniert, bekommt keine Willkommens-Mail | kennt die Plattform schon; keine zweite Begrüßung, kein zweiter Einstieg in Brevo |
| 25 | Deckel Bild-Upload (`OF-06`) | kein Deckel | Platte je Konto auf eine Datei begrenzt (Ersetzen + Sperre je Konto); ein Deckel träfe nur Ausprobierende |
| 26 | Zahl auf der Karte (`OF-01`) | Hand-Zahl `rating` durch berechnete Punktzahl ersetzen → Feature 13 | passt zum PRD (Grundsatz 3); eine Hand-Zahl ohne Kriterien ließe sich nicht ehrlich erklären |
| 27 | „Nicht verfügbar“ aufspalten (`OF-02`) | eigenes Feature 14 | „nicht vorhanden“ ≠ „nicht erfasst“ ist auf dieser Plattform wesentlich; berührt Filter, iOS-API und Punktzahl |
