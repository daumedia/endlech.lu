# 08 · Warteliste für die mobile App — Spezifikation

Status: `approved` · Stand: 2026-09-05 · Entwurf: `design.md` · Plan: `tasks.md` · Bericht: `qa-report.md`

## Zweck

Wer die Plattform lieber als App auf dem Telefon hätte, trägt sich unter `/{locale}/app`
mit seiner E-Mail-Adresse vor und wählt dabei **iOS** oder **Android**. Für iOS läuft
eine offene TestFlight-Beta — der Zugangslink kommt nach der Bestätigung der Adresse per
Mail. Für Android gibt es **nichts**, und genau das steht auch da: Der Eintrag ist dort
eine Vormerkung und zugleich der Bedarfsnachweis, an dem sich später entscheidet, ob
gebaut wird.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B14 · Partner-Warteliste | `approved` | liefert `WaitlistConfirmationService` (Double-Opt-In, Widerruf), `WaitlistEntryInterface`, `WaitlistRequestHelper`, `WaitlistStatus` — die Mechanik wird geteilt, nicht kopiert |
| B22 · Wartelisten-Verwaltung | `approved` | die Einträge erscheinen als **dritte** Quelle in der bestehenden Liste `/admin/warteliste` |
| B24 · Mehrsprachigkeit | `approved` | Seite und beide Mails in de/en/fr/lb |
| 02 · Barrierefreiheit der Plattform | `deployed` | die Zusage WCAG 2.2 AA gilt für dieses Formular ohne Ausnahme |
| 04 · Marketing-Kontakte in Brevo | `deployed` | die optionale Werbe-Einwilligung und ihre Löschkaskade |
| 01 · Betroffenenrechte | `roadmap` | **noch nicht gebaut.** AK-39 und AK-40 sind Zusagen **an** Feature 01; sie werden dort eingelöst, wenn es gebaut wird. Bis dahin bleiben sie offen und stehen hier, damit sie nicht vergessen werden |

## User Stories

- **US-01** · Als iPhone-Besitzer möchte ich die App sofort ausprobieren können, ohne
  auf eine Veröffentlichung im App Store zu warten.
- **US-02** · Als Android-Besitzer möchte ich mich vormerken lassen und dabei **ehrlich
  erfahren**, dass es für mich noch nichts gibt — statt auf eine App zu warten, die
  niemand angefangen hat.
- **US-03** · Als Betreiber der Plattform möchte ich belegen können, wie viele Menschen
  eine App wollen und auf welcher Plattform, bevor ich Entwicklungszeit und laufende
  Kosten binde.
- **US-04** · Als Eingetragener möchte ich mich mit einem Klick wieder austragen können.

## Nicht im Scope

- **Die App selbst.** Weder ein iOS- noch ein Android-Projekt, kein Build, keine
  Store-Einreichung. Dieses Feature ist das Formular und die Liste dahinter — mehr nicht.
- **Push-Benachrichtigungen.** Stehen im PRD unter *Bewusst zurückgestellt* und bleiben
  dort.
- **Store-Abzeichen „Im App Store laden".** Solange keine veröffentlichte App existiert,
  wäre das ein Versprechen ohne Deckung — auch auf der Startseite.
- **Einladungen aus der Verwaltung heraus.** Kein Knopf „einladen", kein „Beta-Link
  erneut senden". Der Zugang läuft über den öffentlichen TestFlight-Link in der Mail;
  für Android gibt es nichts einzuladen.
- **Eine vierte Verwaltungsseite.** Die Einträge gehen in die bestehende Liste → B22.
- **Bewertung, ob gebaut wird.** Die Zahl ist ein Beleg, keine Entscheidung. Die Roadmap
  führt `ios_app` weiterhin unter *Angedacht* → Feature 07.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar.

### Seite und Formular

- **AK-01** · Angenommen, ein Besucher ohne Konto ruft `/{locale}/app` auf, wenn die
  Seite lädt, dann antwortet der Server mit **200** und zeigt das Anmeldeformular — es
  wird **keine** Anmeldung verlangt.
- **AK-02** · Angenommen, ein Besucher ruft die sprachfreie Adresse `/app` auf, wenn die
  Antwort betrachtet wird, dann wird er auf die Fassung in seiner Sprache weitergeleitet
  (Muster `app_open_redirect`, `app_press_redirect`).
- **AK-03** · Angenommen, das Formular wird betrachtet, wenn die **sichtbaren
  Eingabefelder** gezählt werden, dann sind es genau drei: E-Mail-Adresse (Pflicht),
  Plattformwahl (Pflicht) und die Werbe-Einwilligung (freiwillig) — dazu das
  Einwilligungshäkchen als viertes Pflichtelement. **Kein Namensfeld, kein
  Gerätemodell.** Das Honeypot-Feld zählt nicht mit: Es ist für Menschen nicht
  vorhanden.
  *(Geschärft am 2026-09-04, OF-05. Die ursprüngliche Fassung zählte drei Felder und
  übersah, dass ohne das Einwilligungshäkchen keine nachweisbare Einwilligung nach
  Art. 7 Abs. 1 DSGVO entstünde und AK-07 nur zwei Meldungen verlangen könnte.)*
- **AK-04** · Angenommen, das Formular wird frisch geladen, wenn die Plattformwahl
  betrachtet wird, dann sind genau zwei Optionen wählbar (iOS, Android), **keine ist
  vorausgewählt**, und es lässt sich immer nur eine wählen.
- **AK-05** · Angenommen, ein Besucher betrachtet die Android-Option, wenn er den Text
  daneben liest, dann steht dort, dass es für Android **noch keine App und keine Beta**
  gibt — sichtbar **vor** dem Absenden und **ohne** JavaScript.
- **AK-06** · Angenommen, ein Besucher betrachtet die iOS-Option, wenn er den Text
  daneben liest, dann steht dort, dass eine Beta läuft und der Zugangslink **nach der
  Bestätigung der Adresse** per Mail kommt.
- **AK-07** · Angenommen, das Formular ist leer, wenn es abgeschickt wird, dann antwortet
  der Server mit **422**, es erscheint je Pflichtfeld eine eigene Meldung am Feld, und es
  entsteht **kein** Eintrag und **keine** Mail.
- **AK-08** · Angenommen, `keine-adresse` steht im E-Mail-Feld, wenn abgeschickt wird,
  dann antwortet der Server mit **422** mit einer Meldung am E-Mail-Feld, und es entsteht
  kein Eintrag.
- **AK-09** · Angenommen, das Formular ist gültig ausgefüllt, wenn abgeschickt wird, dann
  entsteht ein Eintrag mit Status `pending`, der gewählten Plattform, dem
  Einwilligungszeitpunkt, der Anfragesprache und der Herkunftsquelle (UTM-Parameter oder
  Referrer-Host).
- **AK-10** · Angenommen, der Browser unterstützt Turbo, wenn erfolgreich abgeschickt
  wird, dann wird **nur das Formular** durch die Erfolgsmeldung ersetzt, die übrige Seite
  bleibt stehen.
- **AK-11** · Angenommen, JavaScript ist im Browser abgeschaltet, wenn Eintragen,
  Bestätigen und Abmelden durchlaufen werden, dann funktionieren **alle drei
  vollständig** — keiner der Schritte verlangt ein Skript.
- **AK-12** · Angenommen, das Formular ist ungültig, wenn abgeschickt wird, dann antwortet
  der Server mit **422** und `text/html` (**nicht** `turbo-stream`), und die Seite wird an
  Ort und Stelle mit den Meldungen neu gerendert.
- **AK-13** · Angenommen, das versteckte Honeypot-Feld ist gefüllt, wenn abgeschickt wird,
  dann ist die Antwort **identisch** zum Erfolgsfall — es wird aber nichts gespeichert und
  nichts versandt.
- **AK-14** · Angenommen, das Honeypot-Feld wird im Markup betrachtet, wenn sein Typ
  geprüft wird, dann ist es **kein** `type="hidden"`, sondern per CSS aus dem Blickfeld
  genommen, mit `aria-hidden="true"` und `tabindex="-1"` — und es trägt **keine**
  Validierungsregel, die dem Bot die Falle verriete.

### Doppelte Eintragung

- **AK-15** · Angenommen, eine Adresse steht bereits auf der Liste, wenn sie ein zweites
  Mal abgeschickt wird, dann sieht der Absender **dieselbe Erfolgsmeldung wie beim ersten
  Mal**, und es entsteht **kein** zweiter Eintrag. Die Antwort verrät weder in Text, noch
  Statuscode, noch Antwortzeit, dass die Adresse bekannt ist.
- **AK-16** · Angenommen, ein Eintrag ist bereits `confirmed`, wenn dieselbe Adresse
  erneut abgeschickt wird, dann geht **keine** weitere Mail hinaus und der bestehende
  Eintrag — Plattform eingeschlossen — bleibt **unverändert**.
- **AK-17** · Angenommen, ein Eintrag ist `pending` und sein Bestätigungslink ist
  abgelaufen, wenn dieselbe Adresse erneut abgeschickt wird, dann geht eine **neue**
  Bestätigungsmail mit **neuem** Token hinaus, ohne dass ein zweiter Eintrag entsteht —
  sonst wäre der Vorgang eine Sackgasse.

### Bestätigung und die beiden Mails

- **AK-18** · Angenommen, die Eintragung ist gespeichert, wenn der Versand geprüft wird,
  dann geht eine Bestätigungsmail an die angegebene Adresse, deren Link **absolut** ist
  und mit `https://` beginnt.
- **AK-19** · Angenommen, die erste Mail wird gelesen, wenn nach dem TestFlight-Link
  gesucht wird, dann steht dort **keiner** — nur der Bestätigungslink. (Andernfalls hätte
  der Bestätigungsklick keinen Grund mehr, und wer eine fremde Adresse einträgt, schickte
  dem Fremden den Beta-Zugang.)
- **AK-20** · Angenommen, der Mailversand scheitert, wenn die Antwort betrachtet wird,
  dann **bleibt der Eintrag gespeichert** und es erscheint eine Meldung, dass die Mail
  nicht zugestellt werden konnte.
- **AK-21** · Angenommen, ein gültiger Bestätigungslink wird aufgerufen, wenn die Seite
  lädt, dann wechselt der Status auf `confirmed`, der Bestätigungszeitpunkt wird gesetzt,
  und eine **zweite** Mail geht hinaus.
- **AK-22** · Angenommen, der Eintrag lautet auf **iOS** und der TestFlight-Link ist
  konfiguriert, wenn die zweite Mail gelesen wird, dann enthält sie den TestFlight-Link
  als anklickbaren Knopf.
- **AK-23** · Angenommen, der Eintrag lautet auf **Android**, wenn die zweite Mail gelesen
  wird, dann enthält sie **keinen** TestFlight-Link, sondern den Hinweis, dass wir uns
  melden, sobald es losgeht.
- **AK-24** · Angenommen, der TestFlight-Link ist in der Umgebung **nicht gesetzt** (leer),
  wenn ein iOS-Eintrag bestätigt wird, dann geht dieselbe zweite Mail **ohne**
  Beta-Abschnitt hinaus — kein toter Knopf, keine leere Adresse, kein Fehler, und die
  Bestätigung bleibt erfolgreich.
- **AK-25** · Angenommen, derselbe Bestätigungslink wird ein **zweites Mal** aufgerufen,
  wenn die Seite lädt, dann erscheint „bereits bestätigt" — unterscheidbar von einem
  unbekannten Token — und es geht **keine** zweite Mail erneut hinaus.
- **AK-26** · Angenommen, ein unbekannter Token wird aufgerufen, wenn die Antwort
  betrachtet wird, dann ist der Status **404** und die Seite zeigt „Link ungültig" — ohne
  Ausnahme im Protokoll.
- **AK-27** · Angenommen, ein Token wird mit falschem Format aufgerufen (etwa `abc`), wenn
  die Anfrage durchläuft, dann greift bereits die Routenbedingung und die Route wird gar
  nicht erst gefunden.
- **AK-28** · Angenommen, ein Bestätigungslink ist **älter als 7 Tage**, wenn er
  aufgerufen wird, dann wird er abgewiesen, die Seite nennt den Grund („Link abgelaufen")
  und verweist auf `/{locale}/app`, wo eine neue Eintragung möglich ist — der Nutzer
  steckt nicht fest.
- **AK-29** · Angenommen, ein Eintrag ist noch `pending`, wenn geprüft wird, ob ein
  Marketing-Kontakt in Brevo entstanden ist, dann ist **keiner** entstanden — erst die
  selbst eingelöste Bestätigung erzeugt ihn.

### Widerruf

- **AK-30** · Angenommen, eine der beiden Mails wird gelesen, wenn nach einem Abmeldeweg
  gesucht wird, dann trägt **jede** von ihnen einen Abmeldelink.
- **AK-31** · Angenommen, ein Abmeldelink wird aufgerufen, wenn danach der Bestand geprüft
  wird, dann ist der Eintrag **gelöscht** — nicht auf einen Status gesetzt — und die Seite
  bestätigt das.
- **AK-32** · Angenommen, ein Eintrag wurde abgemeldet und hatte eine Werbe-Einwilligung,
  wenn danach der Marketing-Kontakt geprüft wird, dann ist auch dieser entfernt.
- **AK-33** · Angenommen, derselbe Abmeldelink wird ein zweites Mal aufgerufen, wenn die
  Seite lädt, dann erscheint dieselbe Bestätigungsseite ohne Fehler und ohne Ausnahme.
- **AK-34** · Angenommen, eine Adresse hat sich abgemeldet, wenn sie sich erneut einträgt,
  dann gelingt das wie beim ersten Mal — die Abmeldung sperrt nicht dauerhaft aus.

### Verwaltung und öffentliche Kennzahl

- **AK-35** · Angenommen, ein Eintrag existiert, wenn ein Admin `/admin/warteliste`
  öffnet, dann steht er dort in derselben Liste wie Partner- und Organisationseinträge,
  ist als App-Warteliste erkennbar, und die gewählte Plattform ist ohne Aufklappen
  ablesbar.
- **AK-36** · Angenommen, ein angemeldeter Nutzer **ohne** Admin-Rolle ruft
  `/admin/warteliste` auf, wenn die Antwort betrachtet wird, dann ist sie **403** und
  enthält keinen einzigen Eintrag.
- **AK-37** · Angenommen, es liegen **weniger als 50** Vormerkungen vor, wenn `/open`
  geöffnet wird, dann erscheint dort **gar keine** Zahl zur App-Warteliste — auch keine
  Null und kein „unter 50".
- **AK-38** · Angenommen, es liegen **50 oder mehr** Vormerkungen vor, wenn `/open`
  geöffnet wird, dann erscheint die Gesamtzahl sowie die Aufteilung auf iOS und Android.
- **AK-39** · Angenommen, die Schwelle ist erreicht, wenn `/open.json`, `/open/dataset.csv`
  und `/open/dataset.json` abgerufen werden, dann enthält **keine** dieser Antworten eine
  E-Mail-Adresse aus dieser Warteliste — nur Zahlen.
- **AK-40** · Angenommen, ein Besucher sucht die Seite, wenn er die Fußzeile betrachtet,
  dann findet er dort einen Verweis auf `/app`; zusätzlich weist ein Band auf der
  Startseite darauf hin.

### Datenschutz und Missbrauchsschutz

Der Katalog `~/.claude/sdd/sicherheit.md` ist vollständig durchgegangen. Was nicht
zutrifft, steht als solches darunter.

- **AK-41** · Angenommen, ein Eintrag entsteht, wenn geprüft wird, welche
  personenbezogenen Daten er trägt, dann sind es genau: E-Mail-Adresse, gewählte
  Plattform, Einwilligungszeitpunkt, Zeitpunkt der Werbe-Einwilligung (sofern erteilt),
  Sprache und Herkunftsquelle. **Keine IP-Adresse, kein Name, keine Gerätedaten.**
- **AK-42** · Angenommen, jemand betrachtet die erfassten Felder, wenn nach besonderen
  Kategorien nach Art. 9 DSGVO gesucht wird, dann ist keine dabei — die Auswahl zwischen
  zwei Betriebssystemen sagt nichts über Gesundheit, Herkunft oder Überzeugung.
- **AK-43** · Angenommen, eine Eintragung läuft durch, wenn danach die Protokolle gelesen
  werden, dann steht dort **keine** E-Mail-Adresse und **kein** Bestätigungstoken — weder
  im Erfolgs- noch im Fehlerfall.
- **AK-44** · Angenommen, von einer IP kommen **10** Absendeversuche in einer Stunde, wenn
  der **elfte** eintrifft, dann antwortet der Server mit **429** und einer Meldung, die
  sagt, wann es wieder geht — und es entsteht weder Eintrag noch Mail.
- **AK-45** · Angenommen, die Seite wird nur **gelesen** (GET), wenn danach das Kontingent
  geprüft wird, dann ist nichts verbraucht — gedeckelt wird das Absenden, nicht der Besuch.
- **AK-46** · Angenommen, von einer IP wurden bereits fünf Partner- oder
  Organisationsanmeldungen abgeschickt, wenn dieselbe IP das App-Formular absendet, dann
  gelingt das — die App-Warteliste hat ein **eigenes** Kontingent (BF-38 wiederholt sich
  hier nicht).
- **AK-47** · Angenommen, ein Eintrag steht seit **mehr als 30 Tagen** auf `pending`, wenn
  der Aufräumlauf durchläuft, dann ist er danach gelöscht — ohne Bestätigung gibt es keine
  Einwilligung und damit keine Rechtsgrundlage, ihn zu behalten.
- **AK-48** · Angenommen, ein Eintrag ist `confirmed`, wenn derselbe Aufräumlauf
  durchläuft, dann bleibt er unberührt, unabhängig von seinem Alter.
- **AK-49** · Angenommen, der Aufräumlauf ist eingerichtet, wenn nachgesehen wird, ob er
  tatsächlich aufgerufen wird, dann läuft er wiederkehrend von selbst — nicht nur als
  Methode, die es gibt und die niemand ruft (der Fehlbestand FB-02 aus B14).
- **AK-50** · Angenommen, ein Nutzer löscht sein Konto und dieselbe Adresse steht auf der
  App-Warteliste, wenn danach der Bestand geprüft wird, dann ist auch dieser Eintrag
  gelöscht. *(Wird in Feature `01` eingelöst; Zusage an dessen Löschkaskade.)*
- **AK-51** · Angenommen, ein Nutzer fordert seine Daten an und dieselbe Adresse steht auf
  der App-Warteliste, wenn er den Export öffnet, dann ist der Eintrag darin enthalten.
  *(Ebenfalls Feature `01`.)*
- **AK-52** · Angenommen, die Werbe-Einwilligung wird betrachtet, wenn ihr Zustand geprüft
  wird, dann ist sie **freiwillig und nicht vorausgewählt**, und ohne Häkchen entsteht kein
  Marketing-Kontakt. Die Mails zur Beta selbst hängen **nicht** an ihr — sie sind der Zweck
  der Eintragung.
- **AK-53** · Angenommen, der TestFlight-Link wird gesucht, wenn nachgesehen wird, wo er
  steht, dann steht er als **Umgebungswert**, nicht in einer der vier Sprachdateien und
  nicht im Quelltext — ein Wechsel des Links kostet keinen Deploy und keine vier
  Übersetzungen.
- **AK-54** · Angenommen, jemand fragt, welche externen Dienste Daten aus diesem Feature
  bekommen, wenn nachgesehen wird, dann sind es genau zwei: der Mailversand (Brevo, EU) für
  beide Mails und — **nur bei erteilter Werbe-Einwilligung und erst nach eingelöster
  Bestätigung** — die Kontaktliste in Brevo. Übertragen wird die E-Mail-Adresse; die
  gewählte Plattform geht **nicht** mit. Apple erhält aus diesem Feature **nichts**: Der
  TestFlight-Link ist ein Link, kein Aufruf.

**Trifft nicht zu:**
- *Uploads* — das Formular nimmt keine Dateien entgegen.
- *Kosten je Aufruf über den Mailversand hinaus* — es wird kein KI-Dienst und kein
  bezahlter Fremdaufruf angestoßen. Der Mailversand ist der einzige Kostenpunkt und
  deshalb der Grund für AK-44.
- *Rollen jenseits von Admin* — es gibt keine Zwischenrolle; öffentlich eintragen,
  Admin lesen, mehr nicht.
- *Zugriff auf fremde Datensätze über eine ID* — Einträge sind nie über eine laufende
  Nummer erreichbar, sondern ausschließlich über den 64-stelligen Token. Damit gibt es
  keine IDOR-Fläche im öffentlichen Teil; für die Verwaltung greift AK-36.
- *Geheimnisse zum Client* — dieses Feature führt keinen Schlüssel, den ein Browser sähe.
  Der TestFlight-Link ist öffentlich und zum Teilen bestimmt.

### Mehrsprachigkeit und Zugänglichkeit

- **AK-55** · Angenommen, die Seite und beide Mails werden betrachtet, wenn die vier
  Sprachen durchgegangen werden, dann liegt jeder Text in de, en, fr und lb vor — Seite,
  Feldbeschriftungen, Hilfetexte, Fehlermeldungen, beide Mailvorlagen, die
  Bestätigungsseiten und die Abmeldeseite.
- **AK-56** · Angenommen, das Formular wird ausschließlich mit der Tastatur bedient, wenn
  vom ersten Feld bis zum Absendeknopf getabbt wird, dann ist jedes Bedienelement
  erreichbar, die Reihenfolge folgt der sichtbaren Anordnung, und der Fokus ist überall
  sichtbar.
- **AK-57** · Angenommen, das Formular wurde ungültig abgeschickt, wenn ein Screenreader
  das erste fehlerhafte Feld erreicht, dann wird die zugehörige Fehlermeldung vorgelesen
  und das Feld ist als fehlerhaft ausgezeichnet — und der Fokus steht **ohne JavaScript**
  bereits dort.
- **AK-58** · Angenommen, jemand legt unter `public/` ein Verzeichnis mit dem Namen einer
  Route an, wenn der Prüflauf durchläuft, dann wird er rot — es gibt insbesondere **kein**
  Verzeichnis `public/app` (BF-100: auf Apache schickt `mod_dir` sonst `/app` in eine
  Weiterleitungsschleife, und lokal sieht das niemand).

## Edge Cases

- **EC-01** · Erfolgsfall und Fehlerfall antworten in **verschiedenen Formaten**: der
  Erfolg als Turbo-Stream, der Fehler als `text/html` mit 422. Wird beim Fehler das
  Stream-Format gesetzt, rendert Turbo die Meldungen nicht (EC-03 aus B14).
- **EC-02** · Das Rate-Limit wird **nach** der Formularverarbeitung geprüft, damit ein
  reiner Seitenaufruf kein Kontingent verbraucht (AK-45).
- **EC-03** · Der Test-Override des Limiters ist Pflicht, sonst wird die Suite ab dem
  elften Submit rot.
- **EC-04** · Der Bestätigungstoken bleibt nach dem Einlösen **stehen**, sonst ist
  „bereits bestätigt" (AK-25) nicht von „Link unbekannt" (AK-26) unterscheidbar. Er ist
  zugleich der Abmeldetoken (AK-31) — nach der Abmeldung ist der Eintrag weg, weshalb ein
  zweiter Aufruf über AK-33 abgefangen wird und **nicht** über AK-26.
- **EC-05** · Eine Adresse mit über 180 Zeichen, Emoji oder führenden Leerzeichen wird
  abgewiesen, bevor sie die Datenbank erreicht (Konvention „Die Prüfung gehört dorthin, wo
  der Wert hereinkommt").
- **EC-06** · Zwei Absendevorgänge derselben Adresse **gleichzeitig** aus zwei Tabs dürfen
  nicht zwei Einträge erzeugen; über AK-15 hinaus muss auch der Wettlauf abgefangen sein.
- **EC-07** · Ein Eintrag, dessen Token nach 7 Tagen abläuft, und der Aufräumlauf nach 30
  Tagen greifen unabhängig voneinander: Zwischen Tag 7 und Tag 30 existiert der Eintrag
  noch, ist aber nicht mehr einlösbar. AK-17 ist genau für dieses Fenster da.
- **EC-08** · Der Bestätigungslink wird aus dem Host der Anfrage gebaut. Ohne
  `trusted_hosts` ist das derselbe Angriffsweg wie FB-04 in B14 — er trifft dieses Feature
  gleichermaßen und ist dort noch offen.
- **EC-09** · Fällt der Messenger-Worker aus, entsteht der Eintrag, aber keine Mail geht
  hinaus — lautlos (siehe `CLAUDE.md`, „Messenger-Worker"). Der Nutzer sieht die
  Erfolgsmeldung und wartet vergeblich. Das ist kein Fehler dieses Features, aber der
  wahrscheinlichste Grund dafür, dass AK-18 in Produktion scheitert.

## Offene Fragen

**Alle offenen Fragen sind am 2026-09-04 vom Betreiber entschieden worden.** Die
Einträge bleiben mit ihrer ursprünglichen Fragestellung stehen — was einmal offen
war, ist der Grund dafür, dass es heute so und nicht anders ist.

- **OF-01** · Was passiert mit den Einträgen, **wenn** eine App erscheint?
  **Entschieden 2026-09-04: Eine Mail an alle Bestätigten, und die Liste bleibt
  danach bestehen.** Begründung: Nach iOS kommt Android, nach dem ersten Build
  ein größeres Update — die Liste ist nicht mit einer einzigen Veröffentlichung
  erschöpft.
  ⚠ **Damit läuft für bestätigte Einträge dauerhaft keine Frist.** Der 30-Tage-Lauf
  greift nur bei nie bestätigten (AK-47/AK-48), und das bleibt so. Wer aussteigen
  will, nimmt den Abmeldelink, der in jeder Mail steht (AK-30) — er ist ab jetzt
  nicht mehr nur Komfort, sondern der einzige Weg hinaus.
  *Kein neues Akzeptanzkriterium:* Der Versand ist eine künftige Handlung beim
  App-Start, kein Verhalten der heutigen Software. Er gehört in die Aufgabe, die
  die Veröffentlichung begleitet.

- **OF-02** · Soll die Schwelle von 50 (AK-37) auch dann gelten, wenn eine der
  beiden Plattformen sie allein erreicht? **Entschieden 2026-09-04: Maßgeblich ist
  die Gesamtzahl.** Nimmt sie die Schwelle, werden beide Zahlen gezeigt — auch eine
  kleine. Umgesetzt in `OpenStatsService::appWaitlistCounts()`; die Summe wird
  bewusst aus `ios + android` gebildet und nicht über `array_sum()`, damit sie zu
  der daneben angezeigten Aufteilung passt. Belegt durch
  `AppWaitlistIntegrationTest::testKennzahlErscheintAbDerSchwelle()`.

- **OF-03** · Wo genau steht das Hinweisband auf der Startseite (AK-40)?
  **Entschieden 2026-09-04: zwischen „Warum Endlech.lu?" und dem Handlungsaufruf,
  und es verdrängt nichts.** `bg-gray-50` mit Rahmen oben und unten — die Sektion
  darüber ist weiß, die darunter der Verlauf, es stoßen also keine zwei gleichen
  Flächen aneinander. Cyan statt Purple, damit der Handlungsaufruf darunter der
  lautere bleibt. Ohne Store-Abzeichen (steht unter *Nicht im Scope*).

- **OF-04** · Der öffentliche TestFlight-Link fasst höchstens 10.000 Tester; ist er
  voll oder abgelaufen, führt er ins Leere, und die Anwendung kann das nicht
  erkennen — Apple bietet keine Abfrage, und ein toter Link antwortet nicht anders
  als ein lebender. **Entschieden 2026-09-04: Ein Satz in die zweite Mail** —
  „Der Link funktioniert nicht mehr? … schreib uns kurz." (`email.app_beta_link_dead`,
  vier Sprachen).
  ⚠ **Nur im Zweig mit Link.** Ohne Link gibt es keinen toten Link, und der Satz
  wäre dort eine Warnung vor etwas, das gar nicht angeboten wurde.
  *Weiterhin kein Akzeptanzkriterium für die Platzgrenze selbst* — sie ist von außen
  nicht prüfbar. Der Empfänger ist der Einzige, der den Fall sieht, und damit der
  einzige Weg, auf dem der Betreiber je davon erfährt. Belegt durch
  `testDieZweiteMailInAllenDreiZweigen()`.

- **OF-05** · **AK-03 zählte drei Felder, gebaut sind fünf.**
  **Entschieden 2026-09-04: AK-03 wird geschärft** (siehe dort) — drei sichtbare
  Eingabefelder plus Einwilligungshäkchen; das Honeypot-Feld zählt nicht mit. Beide
  Zusätze sind nicht verhandelbar: Ohne das Häkchen gäbe es keine nachweisbare
  Einwilligung (Art. 7 Abs. 1 DSGVO), ohne den Honeypot kein AK-13/AK-14.

- **OF-06** · ~~**AK-50 und AK-51 hängen an Feature `01`, das auf `roadmap` steht.**~~
  **Erledigt — 2026-09-04 widerlegt.** `src/Account/AccountDeleter.php` und
  `src/Account/AccountDataExporter.php` sind gebaut und in Gebrauch; beide Kriterien
  sind ohne Feature `01` umsetzbar und wurden als Aufgabe **T12** umgesetzt. Der
  Eintrag bleibt stehen, damit nachvollziehbar ist, dass hier eine Abhängigkeit
  vermutet wurde, die es nicht gibt. ⚠ Der Status `roadmap` von Feature `01` in
  `features/index.md` beschreibt den Stand damit nicht mehr vollständig.

- **OF-07** · **Der Aufräumlauf läuft im Zeitplan `marketing`**, dessen Name fachlich
  nicht passt. **Entschieden 2026-09-04: bleibt so.** Ein eigener Zeitplan bräuchte
  einen dritten Transport im `messenger:consume`-Befehl, und der steht an drei
  Stellen — `worker`-Stage des Dockerfiles, `CLAUDE.md` und Coolifys Startbefehl.
  Die dritte zieht niemand automatisch nach, und ihr Ausfall ist lautlos. Der
  Klassenkommentar des Providers benennt den Widerspruch ausdrücklich, damit ein
  Leser den Eintrag dort findet.

- **OF-08** · **AK-50 weicht bewusst vom Bestandsverhalten ab.** `AccountDeleter`
  lässt Partner- und Organisationseinträge beim Kontolöschen stehen (BF-84: eine
  eigenständige Wartelisten-Einwilligung hängt nicht am Konto); die App-Vormerkung
  wird mitgelöscht. **Entschieden 2026-09-04: Es bleibt bei der Abweichung, B14/B15
  werden nicht nachgezogen.**
  Begründung: Hinter einer Partner- oder Organisationsanmeldung steht ein Betrieb
  oder eine Verwaltung mit eigenem Vorgang und einem angekündigten Rückruf — sie
  überlebt das Privatkonto der Person, die sie abgeschickt hat. Die App-Vormerkung
  ist eine reine Adresse ohne eigenen Vorgang; wer sein Konto löscht, will keine
  Mails mehr, und es gibt nichts, was ohne ihn weiterliefe.
  ⚠ **Drei Wartelisten verhalten sich damit in derselben Lage unterschiedlich.** Der
  Unterschied ist begründet, aber er ist einer — und er steht im Code an der Stelle,
  an der er entsteht (`AccountDeleter::delete()`). Wer B14/B15 später angleichen
  will, tut das dort und nicht hier.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Beta-Zugang nach der Eintragung | öffentlicher TestFlight-Link per Mail | der Link existiert und ist offen; ein Einladeweg von Hand wäre ein Knopf ohne Aufgabe |
| 2 | In welcher Mail steht der Link | in der **zweiten**, nach dem Bestätigungsklick | sonst hat der Bestätigungsklick keinen Grund, die Liste bliebe auf `pending` — und eine an eine fremde Adresse ausgelöste Mail trüge den Beta-Zugang |
| 3 | Android ohne Beta | Auswahl möglich, Hinweis sichtbar davor | die Vormerkung ist der Bedarfsnachweis; eine deaktivierte Schaltfläche kostet genau ihn |
| 4 | Eine Plattform oder mehrere | genau eine | die Zahl „so viele wollen Android" bleibt eindeutig, und jede Mail hat genau einen Text |
| 5 | Erfasste Felder | E-Mail, Plattform, Einwilligung | ein Name bringt hier keinen Nutzen, den die Adresse nicht hat; jedes Pflichtfeld mehr kostet Eintragungen |
| 6 | Link nicht konfiguriert | Mail ohne Beta-Abschnitt | ein toter Knopf ist schlechter als kein Knopf; die Bestätigung darf daran nicht scheitern |
| 7 | Werbe-Einwilligung | getrennt und freiwillig | Beta-Mails sind der Zweck der Eintragung, Neuigkeiten sind ein zweiter Zweck — Art. 7 DSGVO trennt die beiden |
| 8 | Zweite Eintragung derselben Adresse | identische Antwort, kein zweiter Eintrag | dasselbe Muster wie die Anti-Enumeration in der API-Registrierung: die Liste darf von außen nicht abfragbar sein |
| 9 | Rate Limit | eigener Deckel, 10 je IP und Stunde | eigener statt geteilter Deckel wegen BF-38; 10 statt 5, weil geteilte Netze (Firmen-WLAN, Mobilfunk-NAT) sonst unschuldig anschlagen |
| 10 | Nie bestätigte Einträge | nach 30 Tagen automatisch gelöscht | ohne Bestätigung liegt keine Einwilligung vor; FB-02 aus B14 wird hier nicht wiederholt — und AK-49 verlangt ausdrücklich den *Aufruf*, nicht bloß die Methode |
| 11 | Widerruf | signierter Abmeldelink in jeder Mail, löscht | der Weg existiert bereits (`WaitlistConfirmationService::revoke()`, BF-37) und wird geteilt; ein Widerruf, nach dem der Datensatz bleibt, ist keiner |
| 12 | Token-Ablauf | 7 Tage | FB-03 aus B14 wird nicht wiederholt; 7 statt 24 h, weil hier niemand aktiv wartet — anders als bei der Registrierung |
| 13 | Kontolöschung | Eintrag über Adressgleichheit mitlöschen | die Warteliste kennt keinen Nutzerbezug, die Adresse ist der einzige Anker; wer sein Konto löscht, will keine Mails mehr |
| 14 | Öffentliche Kennzahl | erst ab 50 Vormerkungen | eine niedrige Zahl in den ersten Wochen wirkt gegen das Vorhaben; die Schwelle folgt dem Muster der Quartalssperre bei den Finanzen |
| 15 | Verwaltung | dritte Quelle in `/admin/warteliste` | B22 normalisiert die Zeilen bereits über `WaitlistEntryInterface`; eine vierte Verwaltungsseite wäre Fläche ohne Zugewinn |
| 16 | Ort der Seite | eigene Seite `/{locale}/app` plus sprachfreie Weiterleitung | eine Adresse, die man teilt und bewirbt, braucht einen eigenen Pfad — Muster von `/presse` und `/roadmap` |
| 17 | Verlinkung | Fußzeile plus Band auf der Startseite | eine Warteliste ohne Sichtbarkeit bekommt keine Füllung; die Bottom-Navigation hat nur vier Plätze und keiner davon ist entbehrlich |
| 18 | Was beim App-Start passiert (OF-01) | Mail an alle Bestätigten, Liste bleibt | nach iOS kommt Android, nach dem ersten Build ein Update — die Liste ist mit einer Veröffentlichung nicht erschöpft. Preis: für Bestätigte läuft keine Frist, der Abmeldelink ist der einzige Weg hinaus |
| 19 | Toter TestFlight-Link (OF-04) | ein Satz in der zweiten Mail, nur im Zweig mit Link | der Empfänger ist der Einzige, der den Fall sieht — Apple bietet keine Abfrage, und ein voller Link antwortet nicht anders als ein offener |
| 20 | Abweichung beim Kontolöschen (OF-08) | bleibt bei `08`, B14/B15 unangetastet | hinter einer Partneranmeldung steht ein Vorgang mit angekündigtem Rückruf, der das Privatkonto überlebt; hinter einer App-Vormerkung steht nur eine Adresse |
| 21 | Zeitplanname `marketing` (OF-07) | bleibt | ein dritter Transport steht an drei Orten, einer davon von Hand in Coolify — und sein Ausfall ist lautlos |
