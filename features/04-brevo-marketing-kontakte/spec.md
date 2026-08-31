# 04 · Marketing-Kontakte in Brevo — Spezifikation

Status: `planned` · Stand: 2026-08-29 · **Anforderung vor Code**

## Zweck

Wer sich auf einer Warteliste einträgt oder ein Konto anlegt, kann zusätzlich
einwilligen, Neuigkeiten zu erhalten. Diese Adressen stehen danach als Kontakte in
Brevo, nach Sprache und Zielgruppe getrennt, sodass sich eine Kampagne verschicken
lässt, ohne eine Liste von Hand zusammenzustellen.

**Warum jetzt.** Das PRD benennt es als Risiko 4: *„Wer sich im August einträgt und im
Februar noch nichts gehört hat, ist als Interessent verloren."* Die Wartelisten sammeln
seit August 2026 Kontakte für Angebote ohne Preis. Es gibt heute keinen Weg, diesen
Menschen etwas zu sagen, außer jede Adresse einzeln aus der Verwaltung abzuschreiben.

**Was sich dadurch ändert, und zwar grundsätzlich.** Brevo bekommt heute nur die
einzelne Nachricht, die es zustellen soll. Nach diesem Feature bekommt Brevo einen
**Bestand** — Adressen samt Zielgruppe und Vertriebsstatus, dauerhaft gespeichert, zu
einem anderen Zweck als dem Versand. Das ist die erste Weitergabe dieser Art im Projekt
und der Grund, warum der Datenschutzteil hier länger ist als der Funktionsteil.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 · Registrierung | `approved` | die Einwilligungs-Checkbox sitzt im Registrierformular; nur bestätigte Adressen gehen mit |
| B14 · Partner-Warteliste | `approved` | Quelle der Kontakte, Ort der Checkbox |
| B15 · Organisations-Wartelisten | `approved` | dito, plus die Typtrennung (Gemeinde / Unternehmen / Verein) |
| B22 · Wartelisten-Verwaltung | `approved` | trägt die Sync-Ansicht |
| 01 · Betroffenenrechte | `roadmap`, Code live | Widerruf und Kontolöschung müssen den Brevo-Kontakt mitnehmen — ohne dieses Feature gibt es den Widerrufsweg gar nicht |

⚠ **`01` ist der kritische Vorgänger.** Sein Code ist seit v2026.08.29 live, das Inventar
führt ihn aber noch als `roadmap` (siehe `features/index.md`, Zeile 107). Vor dem Bau von
`04` muss geklärt sein, welcher Stand von `01` tatsächlich läuft — sonst wird eine
Löschkaskade an einen Widerrufsweg gehängt, den es nicht gibt.

## User Stories

- **US-01** · Als Interessent möchte ich beim Eintragen entscheiden können, ob ich
  Neuigkeiten bekomme, ohne dass diese Entscheidung an meiner Anmeldung hängt.
- **US-02** · Als Betreiber möchte ich eine Kampagne an eine Zielgruppe in ihrer Sprache
  verschicken können, ohne Adressen von Hand zu sammeln.
- **US-03** · Als Empfänger möchte ich mich mit einem Klick abmelden können und danach
  nichts mehr bekommen — auch nicht nach dem nächsten Abgleich.
- **US-04** · Als Betreiber möchte ich sehen, welche Einträge in Brevo stehen und welche
  nicht, damit eine Lücke nicht erst bei einer Kampagne auffällt.
- **US-05** · Als Betreiber möchte ich den vorhandenen Bestand einmalig übertragen
  können, ohne mich bei einem Filterfehler unwiderruflich zu vertun.

## Nicht im Scope

- **Kampagnen selbst.** Betreff, Inhalt, Versandzeitpunkt und Segmente werden in Brevo
  gepflegt, nicht in Endlech.lu. Dieses Feature liefert die Kontakte, nicht die Mail.
- **Transaktionsmails.** Bestätigung, Verifikation und interne Meldung laufen unverändert
  über den bestehenden Versandweg und sind von der Einwilligung unabhängig — sie sind
  Teil des Vorgangs, nicht Werbung.
- **Nachträgliches Zustimmen für bestehende Konten** → siehe OF-02.
- **Ablehnungsgründe und Trichter-Auswertung** (PRD, Hypothese zu `WaitlistStatus`) —
  eigenes Vorhaben.
- **Web-Analytics.** Bleibt ausgeschlossen (PRD, „Nicht im Umfang").

## Akzeptanzkriterien

### Einwilligung erteilen (US-01)

- **AK-01** · Angenommen, ein Besucher öffnet das Partner-, das Organisations- oder das
  Registrierformular, wenn er es betrachtet, dann findet er ein zusätzliches Feld, mit
  dem er Neuigkeiten abonnieren kann.
- **AK-02** · Angenommen, dieses Feld wird betrachtet, wenn sein Ausgangszustand geprüft
  wird, dann ist es **nicht vorangehakt**.
- **AK-03** · Angenommen, das Feld bleibt leer, wenn das Formular abgeschickt wird, dann
  läuft die Anmeldung bzw. die Registrierung **unverändert durch** — die Einwilligung ist
  keine Bedingung für irgendetwas.
- **AK-04** · Angenommen, das Feld ist gesetzt, wenn der Eintrag danach geprüft wird,
  dann ist neben der Zustimmung auch der **Zeitpunkt** festgehalten, getrennt von der
  bestehenden Einwilligung zur Anmeldung.
- **AK-05** · Angenommen, jemand hat der Werbung zugestimmt, aber seine Adresse noch
  nicht bestätigt (Wartelisten-Status `pending` oder Konto unbestätigt), wenn geprüft
  wird, ob er in Brevo steht, dann steht er dort **nicht**.

### Übertragung nach Brevo (US-02)

- **AK-06** · Angenommen, ein Interessent hat zugestimmt und seine Adresse bestätigt,
  wenn danach in Brevo nachgesehen wird, dann existiert dort ein Kontakt mit seiner
  Adresse.
- **AK-07** · Angenommen, ein solcher Kontakt wird in Brevo betrachtet, wenn seine
  Attribute geprüft werden, dann trägt er **genau**: Sprache, Herkunft (Partner,
  Gemeinde, Unternehmen, Verein oder Nutzerkonto), Ansprechpartner, Organisations- bzw.
  Restaurantname und den Vertriebsstatus — und **keine weiteren Felder**.
- **AK-08** · Angenommen, jemand hat einen Vertriebsstatus, der einen abgeschlossenen
  Vorgang bezeichnet, wenn der Kontakt in Brevo betrachtet wird, dann ist dieser Status
  dort erkennbar, sodass sich eine Kampagne für das Partnerprogramm gegen ihn
  ausschließen lässt.
- **AK-09** · Angenommen, ein Vertriebsstatus wird in der Verwaltung geändert, wenn
  danach in Brevo nachgesehen wird, dann ist die Änderung dort angekommen.
- **AK-10** · Angenommen, jemand willigt ein, wenn die Übertragung geprüft wird, dann
  steht der Kontakt **innerhalb von 15 Minuten** in Brevo.

### Rückweg: Abmelden und Löschen (US-03)

- **AK-11** · Angenommen, ein Empfänger klickt in einer Kampagne auf „Abmelden", wenn
  danach der Eintrag in Endlech.lu geprüft wird, dann ist die Werbe-Einwilligung dort
  **zurückgenommen**.
- **AK-12** · Angenommen, jemand hat sich abgemeldet, wenn der nächste Abgleich läuft,
  dann wird er **nicht erneut** als Kontakt angelegt.
- **AK-13** · Angenommen, ein Wartelisten-Interessent widerruft über den Abmeldelink
  seiner Bestätigungsmail (Feature `01`/AK-20), wenn danach geprüft wird, dann ist sein
  Eintrag lokal gelöscht **und** sein Brevo-Kontakt entfernt.
- **AK-14** · Angenommen, ein Nutzer löscht sein Konto (Feature `01`/AK-04), wenn danach
  geprüft wird, dann ist auch sein Brevo-Kontakt entfernt.
- **AK-15** · Angenommen, ein Kontakt konnte in Brevo nicht entfernt werden, wenn der
  Betreiber die Verwaltung öffnet, dann sieht er das — eine gescheiterte Löschung darf
  nicht stillschweigend untergehen.
- **AK-16** · Angenommen, die lokale Löschung ist erfolgt, wenn der Brevo-Aufruf
  scheitert, dann **bleibt die lokale Löschung bestehen** — sie wird nicht zurückgerollt.

### Ausfall und Nachlauf

- **AK-17** · Angenommen, Brevo ist nicht erreichbar, während sich jemand einträgt, wenn
  er die Antwort sieht, dann ist seine Anmeldung **erfolgreich** und er merkt nichts
  davon.
- **AK-18** · Angenommen, eine Übertragung ist gescheitert, wenn die Verwaltung geöffnet
  wird, dann ist der Eintrag als nicht übertragen erkennbar, mit dem Grund des letzten
  Versuchs.
- **AK-19** · Angenommen, eine Übertragung ist gescheitert, wenn ein Nachlauf stattfindet,
  dann wird sie erneut versucht, ohne dass jemand sie von Hand anstoßen muss.
- **AK-20** · Angenommen, dieselbe Übertragung läuft zweimal, wenn danach in Brevo
  nachgesehen wird, dann steht dort trotzdem **ein** Kontakt.

### Bestandsübertragung (US-05)

- **AK-21** · Angenommen, der Betreiber startet die Bestandsübertragung ohne weitere
  Angabe, wenn sie läuft, dann ist es ein **Trockenlauf**: Er sieht, wie viele und welche
  Einträge übertragen würden, und **nichts wird an Brevo geschickt**.
- **AK-22** · Angenommen, der Trockenlauf ist geprüft, wenn die Übertragung ausdrücklich
  bestätigt wird, dann werden genau die angezeigten Einträge übertragen.
- **AK-23** · Angenommen, der Bestand wird übertragen, wenn geprüft wird, wer dabei ist,
  dann sind es **ausschließlich bestätigte Wartelisten-Einträge** — keine unbestätigten
  und keine bestehenden Nutzerkonten.
- **AK-24** · Angenommen, an diesen Kreis geht die erste Kampagne, wenn sie gelesen wird,
  dann nennt sie im ersten Absatz, woher die Adresse stammt, und trägt einen
  Abmeldelink.
- **AK-25** · Angenommen, die Bestandsübertragung wird ein zweites Mal ausgeführt, wenn
  danach in Brevo nachgesehen wird, dann sind keine Dubletten entstanden.

### Sichtbarkeit (US-04)

- **AK-26** · Angenommen, der Betreiber öffnet die Wartelisten-Verwaltung, wenn er eine
  Zeile betrachtet, dann sieht er, ob dieser Eintrag in Brevo steht, seit wann, und was
  beim letzten Versuch geschah.
- **AK-27** · Angenommen, der Betreiber vergleicht die Zahl der einwilligenden Einträge
  mit der Kontaktzahl in Brevo, wenn beide Zahlen betrachtet werden, dann stimmen sie
  überein — und nach einem Widerruf sinken sie zugleich.

### Datenschutz und Missbrauchsschutz

Durchgang durch `~/.claude/sdd/sicherheit.md`, alle sechs Abschnitte. Datenschutzstufe:
**B** (übliche Personendaten) — das PRD legt keine fest, siehe OF-01.

**1 · Personenbezogene Daten**

- **AK-28** · Angenommen, ein Kontakt geht nach Brevo, wenn geprüft wird, welche
  personenbezogenen Daten er trägt, dann sind es: E-Mail-Adresse, Name des
  Ansprechpartners, Organisations- bzw. Restaurantname, Sprache, Herkunft und
  Vertriebsstatus.
- **AK-29** · Angenommen, ein Wartelisten-Eintrag trägt eine **Freitextnachricht**, wenn
  die Übertragung geprüft wird, dann geht dieser Text **nicht** mit. *(Auf einer
  Barrierefreiheitsplattform kann dort alles stehen — auch eine Gesundheitsangabe und
  damit eine besondere Kategorie nach Art. 9 DSGVO.)*
- **AK-30** · Angenommen, das Herkunftsattribut wird in Brevo betrachtet, wenn geprüft
  wird, was es aussagt, dann bezeichnet es **die Rolle im Vertrieb** (Partner, Gemeinde,
  Unternehmen, Verein, Nutzerkonto) und **nicht**, ob jemand selbst von einer
  Behinderung betroffen ist.
- **AK-31** · Angenommen, eine Übertragung läuft, wenn danach die Protokolle betrachtet
  werden, dann steht dort keine vollständige E-Mail-Adresse und kein API-Schlüssel.

**2 · Weitergabe an externe Dienste**

- **AK-32** · Angenommen, jemand liest die Datenschutzerklärung auf `/legal`, wenn er
  nach Empfängern sucht, dann ist Brevo dort als Empfänger **für Werbezwecke** benannt —
  nicht nur als Versanddienstleister.
- **AK-33** · Angenommen, der Auftragsverarbeitungsvertrag mit Brevo wird gesucht, wenn
  nachgesehen wird, dann ist er in `docs/datenschutz.md` festgehalten, mit Sitz des
  Anbieters und Datum. **Diese Datei existiert heute nicht** und wird mit diesem Feature
  angelegt.
- **AK-34** · Angenommen, der erste echte Sync soll laufen, wenn die Vorbedingungen
  geprüft werden, dann stehen AK-32 und AK-33 **vorher** — kein Kontakt geht raus, bevor
  die Erklärung ihn nennt.

**3 · Zugriff**

- **AK-35** · Angenommen, jemand ohne Verwaltungsrolle ruft die Sync-Ansicht auf, wenn
  die Antwort betrachtet wird, dann bekommt er sie nicht zu sehen.
- **AK-36** · Angenommen, die Bestandsübertragung wird von jemandem ohne
  Verwaltungsrolle ausgelöst, wenn geprüft wird, dann läuft sie nicht.
- **AK-37** · Angenommen, jemand ruft die Sync-Ansicht mit einer fremden Kennung auf,
  wenn die Antwort betrachtet wird, dann liefert sie 403 oder 404, nie einen fremden
  Datensatz.
- **AK-38** · *Rollen:* Es gibt nur `ROLE_USER` und `ROLE_ADMIN`. Der Sync und seine
  Ansicht sind ausschließlich `ROLE_ADMIN`; ein Nutzer sieht von diesem Feature nichts
  außer der Checkbox.

**4 · Missbrauch und Kosten**

- **AK-39** · Angenommen, viele Einträge werden zugleich übertragen, wenn der Ablauf
  geprüft wird, dann greift ein Limit auf die Aufrufe an Brevo, sodass ein einzelner Lauf
  weder das Kontingent des Anbieters noch die Zustellrate gefährdet.
- **AK-40** · Angenommen, jemand füllt wiederholt ein Formular mit gesetzter Checkbox
  ab, wenn geprüft wird, dann bremst ihn das **bestehende** Rate-Limit der jeweiligen
  Warteliste bzw. Registrierung — die Checkbox eröffnet keinen ungedeckelten Weg zu einem
  bezahlten Dienst.
- **AK-41** · *Uploads:* trifft nicht zu — dieses Feature nimmt keine Dateien entgegen.
- **AK-42** · *Kosten je Aufruf:* Ein Kontakt in Brevo kostet nichts, der Versand einer
  Kampagne schon. Da Kampagnen nicht aus Endlech.lu heraus ausgelöst werden (siehe *Nicht
  im Scope*), kann dieses Feature keine Versandkosten erzeugen.

**5 · Löschen und Auskunft**

- **AK-43** · Siehe AK-13 und AK-14 — Widerruf und Kontolöschung entfernen den
  Brevo-Kontakt.
- **AK-44** · Angenommen, ein Nutzer fordert seinen Datenexport an (Feature `01`/AK-09),
  wenn die Datei geöffnet wird, dann steht darin, ob er der Werbung zugestimmt hat und
  wann.
- **AK-45** · Angenommen, jemand hat der Werbung zugestimmt und widerruft, wenn danach
  geprüft wird, dann ist die **Adresse wieder frei** — ein späterer erneuter Eintrag
  funktioniert.

**6 · Geheimnisse**

- **AK-46** · Angenommen, der Schlüssel für die Brevo-Kontaktverwaltung wird gesucht,
  wenn nachgesehen wird, dann steht er ausschließlich in der ungetrackten lokalen
  Konfiguration auf dem Server, mit leerem Vorgabewert im Repository.
- **AK-47** · Angenommen, der Schlüssel fehlt oder ist leer, wenn sich jemand einträgt,
  dann ist die **Funktion still aus** und die Anmeldung läuft normal durch — dasselbe
  Muster wie beim Haltestellen-Dienst und bei Sentry.
- **AK-48** · Angenommen, die ausgelieferten Seiten werden betrachtet, wenn nach dem
  Schlüssel gesucht wird, dann kommt er an keiner Stelle beim Browser an.

## Edge Cases

- **EC-01 · Dieselbe Adresse aus zwei Quellen.** Jemand steht auf der Partner-Warteliste
  *und* hat ein Konto, oder auf beiden Wartelisten. In Brevo entsteht daraus **ein**
  Kontakt, nicht zwei — sonst kommt jede Kampagne doppelt an. Welche Herkunft dann gilt,
  entscheidet OF-04.
- **EC-02 · Adresse geändert.** Ein Nutzer ändert seine E-Mail im Profil; wirksam wird
  das erst nach Bestätigung (BF-19). Erst dann zieht der Brevo-Kontakt mit — vorher wäre
  die neue Adresse unbestätigt.
- **EC-03 · Zustimmung ohne je zu bestätigen.** Wer der Werbung zustimmt, aber den
  Double-Opt-In nie abschließt, geht nie nach Brevo (AK-05) und bleibt trotzdem als
  Anmeldung bestehen.
- **EC-04 · Widerruf eines Eintrags, der nie übertragen wurde.** Läuft ins Leere, ohne
  einen Fehler zu erzeugen.
- **EC-05 · Kontakt existiert in Brevo aus einem früheren Handimport.** Der Abgleich
  ergänzt ihn, hebt aber keine dort bestehende Abmeldung auf.
- **EC-06 · Die Checkbox wird gesetzt, das Formular ist aber ungültig.** Nach dem
  erneuten Anzeigen ist die Wahl noch da — sie darf nicht stillschweigend zurückfallen.

## Offene Fragen

- **OF-01** · Welche Datenschutzstufe gilt für das Projekt? Das PRD legt keine fest, und
  `docs/datenschutz.md` existiert nicht. Diese Spec nimmt **B** an. Zu entscheiden, bevor
  AK-33 umgesetzt wird. — Betreiber
- **OF-02** · Wie kann ein bestehendes Konto nachträglich zustimmen? Heute erreicht die
  Checkbox nur den Registriervorgang; wer schon ein Konto hat, hat keinen Weg hinein. Ein
  Schalter im Profil wäre der naheliegende Ort, wurde aber nicht beauftragt. — Betreiber

  ⚠ **Es genügt nicht, das Feld zu setzen.** `MarketingContactRegistry::recordUser()`
  wird an genau einer Stelle gerufen — `EmailVerificationController:74`, beim Bestätigen
  der Adresse nach der Registrierung. Ein bereits verifiziertes Konto holt kein Lauf
  nachträglich ab, auch nicht `app:marketing:import` (das nimmt ausdrücklich keine
  Konten). Wer OF-02 umsetzt, braucht deshalb **beides**: den Schalter *und* einen
  zweiten Aufrufpunkt. Ein Schalter allein wäre eine Einwilligung, die folgenlos bleibt
  — schlimmer als keiner, weil sie dem Nutzer etwas zusagt, das nicht geschieht.

  ⚠ **Der Widerruf hängt mit dran** (Art. 7 Abs. 3): Wer bei der Registrierung angehakt
  hat, kann heute nur über den Abmeldelink einer Kampagne widerrufen — also erst,
  nachdem er Werbung bekommen hat. Derselbe Schalter löst beide Richtungen.

  **Stand 2026-08-30 gemessen: zwei Konten, beide dem Betreiber persönlich bekannt.**
  Damit ist die Frage vorerst gegenstandslos — für zwei Personen ist ein Feature
  unverhältnismäßig, und der direkte Weg ist ohnehin der bessere. Sie wird wieder
  dringlich, sobald Registrierungen von Fremden eingehen; dann ist der gesamte
  Nutzerbestand ohne diesen Schalter für Kampagnen unerreichbar.
- **OF-03** · Bleibt die Öffnungs- und Klickverfolgung in Brevo eingeschaltet? Sie ist
  dort Vorgabe. Das PRD schließt Web-Analytics aus und begründet das mit Datensparsamkeit
  — ob das auch für Kampagnen gilt, ist nicht entschieden. — Betreiber
- **OF-04** · Welche Herkunft gilt bei einer Adresse aus mehreren Quellen (EC-01) — die
  erste, die letzte, oder eine Mehrfachzuordnung? — Betreiber
- **OF-05** · Was geschieht bei einem harten Bounce oder einer Spam-Beschwerde? Bewusst
  nicht als Kriterium aufgenommen; es wird im Betrieb auftreten und braucht dann eine
  Regel. — Betreiber
- **OF-06** · Gibt es eine Löschfrist für Werbe-Kontakte, die jahrelang nicht reagieren?
  Der Bestand kennt bis heute keine Aufräumroutine (B14/FB-02). — Betreiber
- **OF-07** · *(beim Bau am 2026-08-29 gefunden, gehört zu **B15**, nicht zu diesem
  Feature)* **Das Organisations-Formular ist auf den drei Zielgruppenseiten nicht
  absendbar.** `form_start()` in `templates/organisation/_form.html.twig` setzt kein
  `action`; auf `/organisationen/{slug}` postet der Browser damit auf die eigene URL,
  und `app_organisations_type` ist `methods: ['GET']` → **HTTP 405**, nachgemessen. Nur
  die Übersicht funktioniert, weil dort `app_organisations_submit` denselben Pfad als
  POST führt. Für Feature `04` heißt das: Die Einwilligungs-Checkbox ist dort zwar
  sichtbar (AK-01 erfüllt), aber faktisch nicht erteilbar. Die naheliegende Reparatur
  (`'action': path('app_organisations_submit')`) verlöre den UTM-Query-String, den der
  Partner-Weg ausdrücklich schützt — deshalb braucht sie eine eigene Entscheidung und
  wurde hier **nicht** vorgenommen. — Betreiber
- **OF-09** · *(bei der Reparatur von BF-85 offengeblieben)* **Wie soll sich das
  Auftragsbuch bei echter Nebenläufigkeit verhalten?** Der belegte Fall — zwei
  `record()`-Aufrufe in **einem** Vorgang — ist behoben. Zwei **parallele Requests**
  (etwa eine Wartelisten-Bestätigung und eine Konto-Verifikation derselben Adresse im
  selben Moment) lesen weiterhin beide vor dem Commit des anderen und kollidieren am
  Unique-Index; der gemeinsame `flush()` rollt dann **die Bestätigung selbst** mit
  zurück, und der Nutzer sieht eine Fehlerseite statt „Bestätigt". Die Lösung wäre ein
  vom Hauptvorgang entkoppelter `flush()` für den Marketing-Teil — das ändert die
  Transaktionsführung von Feature `01` und braucht eine eigene Entscheidung.
  Eintrittswahrscheinlichkeit gering, Wirkung auf den Betroffenen hoch. — Betreiber
- **OF-08** · *(beim Bau gefunden)* **`ORIGIN` reicht für den Herkunftssatz der ersten
  Kampagne nicht, wenn sie an mehrere Zielgruppen zugleich geht.** „Warteliste für das
  Partnerprogramm" stimmt nur für `PARTNER`. Entweder je Segment eine eigene Kampagne
  oder ein allgemeinerer Satz — ein falscher Herkunftssatz ist schlimmer als ein
  allgemeiner, weil er etwas über die Person behauptet, das nicht stimmt (siehe
  `erste-kampagne.md`). — Betreiber

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Woher kommt die Einwilligung | eigene, nicht vorangehakte Checkbox in allen drei Formularen | `consentAt` der Wartelisten deckt die Kontaktaufnahme **zum Angebot**; ein Newsletter geht darüber hinaus |
| 2 | Einwilligung als Bedingung der Anmeldung | nein | ein Koppelungsverbot-Verstoß wäre der schnellste Weg, die ganze Liste unbrauchbar zu machen |
| 3 | Unbestätigte Adressen übertragen | nein | wer den Double-Opt-In nie abschloss, hat nie belegt, dass die Adresse ihm gehört |
| 4 | Bestand übertragen | ja, nur bestätigte Wartelisten-Einträge, erste Kampagne mit Herkunftshinweis | stützt sich auf `consentAt`; die Herkunftsangabe macht die Grundlage für den Empfänger nachvollziehbar. Konten sind ausgenommen — sie haben nur der Nutzung zugestimmt |
| 5 | Richtung des Abgleichs | beidseitig | eine Abmeldung, von der nur Brevo weiß, wird vom nächsten Lauf überschrieben |
| 6 | Löschkaskade | Widerruf und Kontolöschung entfernen den Brevo-Kontakt | eine Löschung nach Art. 17, die einen Kontakt bei einem Dritten stehen lässt, ist keine — und Feature `01` wäre nur noch halb wirksam |
| 7 | Verhalten bei Ausfall von Brevo | Anmeldung zählt, Übertragung wird nachgeholt | Muster aus B14/AK-04: ein fremder Dienst darf die eigene Anmeldung nicht scheitern lassen |
| 8 | Freitextnachricht übertragen | nein | dort kann eine Gesundheitsangabe stehen; was nicht übertragen wird, kann nicht abfließen |
| 9 | Bestandsübertragung ohne Bestätigung | nein, Trockenlauf zuerst | ein falsch gefilterter Lauf ist nicht zurückzuholen — die Mails sind dann raus |
| 10 | Kampagnen aus Endlech.lu auslösen | nein | Inhalt und Versand bleiben in Brevo; das hält die Kostenseite und die Versandlogik aus dem Produkt heraus |
| 11 | Sichtbarkeit des Sync-Stands | Spalte in der bestehenden Wartelisten-Verwaltung | Sentry zeigt Ausfälle, aber keine Lücken — eine nicht übertragene Adresse erzeugt keinen Fehler |
