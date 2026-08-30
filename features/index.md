# Features

Stand: 2026-08-30 · Stack-Profil: `symfony-doctrine` · Artefaktpfad: `docs/`

Stand der Rückerfassung: **alle 26 Features rekonstruiert** (2026-08-23).
Stand der Prüfung: **B01 zweimal geprüft und repariert** → `review` (17/20 Kriterien).
Die Behebungen liegen auf `fix/b01-registrierung-qa` und sind **noch nicht ausgeliefert**.

**2026-08-23 · BF-04 herausgelöst:** Die fehlenden Betroffenenrechte waren B01
zugerechnet, sind aber keine Reparatur an B01, sondern fehlende Funktionen über drei
Features hinweg. Sie laufen jetzt als reguläres Feature `01` durch die volle Kette.
Damit hat B01 nur noch Befunde mit Grad *mittel* — was nach den Regeln der Kette eine
Auslieferung nicht blockiert.

**2026-08-23 · B01 abgenommen** (dritter QA-Durchlauf): 17 von 20 Kriterien, nur noch
zwei Befunde mit Grad *mittel*. Die Reparatur liegt committet auf
`fix/b01-registrierung-qa` und ist **noch nicht ausgeliefert** — für Nutzer ist die
Sackgasse offen, bis das gemerged ist.

**2026-08-24 · B02 abgenommen** nach Reparatur: Anmeldung sperrt nach fünf Fehlversuchen,
Abmelden verlangt ein Token. 16 von 17 Kriterien, nur *mittel*/*niedrig* offen.

**2026-08-24 · B03 abgenommen** — der Passkey-Ablauf wurde im echten Browser mit einem
virtuellen WebAuthn-Authenticator (CDP) durchgespielt, inklusive Anmeldung ohne
E-Mail-Eingabe. Ein Befund *mittel* (BF-18), drei Kriterien nicht prüfbar.

**2026-08-26 · Feature `02` aufgenommen und spezifiziert.** Die Zugänglichkeit der Plattform
selbst war nie erfasst — weder „RAWeb" noch „EN 301 549" kam im Projekt vor. Zugesagt wird
WCAG 2.2 AA über den vollen Bestand einschließlich Verwaltung, App-Hülle und Mails.

**2026-08-28 · Feature `03` aufgenommen und spezifiziert.** Vergleichsseiten gegenüber
Google Maps, Wheelmap und TripAdvisor, erreichbar über einen eigenen
Bereich in der Fußzeile. Der Anlass steht seit jeher im PRD („Weder Google Maps noch die
Websites der Häuser beantworten die Fragen, auf die es ankommt“), stand aber nirgends
öffentlich. Die Spec bindet den Ton an die zugesagten Produktprinzipien: jede Aussage über
einen Wettbewerber trägt Quelle und Prüfdatum, und die Abdeckungszeile nennt die eigene,
kleinere Zahl. Der Entwurf steht seit demselben Tag: keine Entität und keine Migration —
Struktur als Aufzählungen unter `App\Comparison\`, Texte in einer eigenen
Übersetzungsdomain `comparison`, Zahlen aus derselben Quelle wie `/open`. Drei der vier
offenen Fragen sind entschieden; **OF-02 (Prüfrhythmus der Wettbewerber-Angaben) bleibt
offen** und ist ein Prozess, kein Entwurf. Der Aufgabenplan brachte zwei Kriterien ans Licht, die es sonst nie gegeben hätte:
OF-01 und OF-03 waren **entschieden, aber ohne AK** — nachgetragen als AK-31 und AK-32.

**Gebaut am selben Tag**, 25 Aufgaben in fünf Ebenen, 590 Tests grün. **Jaccede wurde beim
Bau gestrichen** (OF-05): Die Plattform ist seit dem 2. Juli 2026 nur noch ein statischer
Abzug — Suche und Anmeldung antworten mit 404, die Apps sind aus den Stores. Ein Vergleich
mit einem eingestellten Dienst wäre eine Falschaussage über einen fremden Verein. Damit
nennt AK-03 einen Slug zu viel; die drei übrigen Vergleiche sind vollständig belegt
(54 Merkmalszeilen, 18 Primärquellen mit Prüfdatum).

**2026-08-29 · QA von `03`:** 31 von 32 Kriterien bestanden, alle sieben Randfälle belegt,
der Angriffsdurchlauf ohne Fund. **Blockierend ist BF-77:** Bei 320 px scrollen die drei
Vergleichsseiten waagerecht (`scrollX=212`) — die Merkmalstabelle ist mit ihren
erklärenden Halbsätzen 525 px breit, wo die Bestandstabelle auf `/partner` mit 324 px
noch passt. Dazu zwei mittlere Befunde (BF-78: Gruppen- und Bewertungsnamen fallen durch
jeden Prüflauf; BF-79: zwei gleichnamige Landmarks). Weiter mit `/sdd-build 03`.

**2026-08-29 · Release v2026.08.29 ist live.** Der erste Deploy seit dem 9. August — er
brachte sieben Wochen Arbeit auf einmal: Feature `03`, Feature `02`, Feature `01` und alle
72 Befunde der Rückerfassung. **Zehn Migrationen** liefen mit, darunter fünf neue Tabellen
(`partner_waitlist_entry`, `organisation_waitlist_entry`, `finance_entry`,
`metric_snapshot`, `webauthn_credential`).

Auf Produktion nachgeprüft: alle fünf neuen Adressen antworten mit 200, die Fußzeile führt
den Bereich „Vergleiche", die Abdeckungszeile zeigt **3 Lokale** — die echte Zahl, identisch
mit `/open.json` —, `/de/open`, `/de/partner` und `/de/organisationen` laufen (Beleg dafür,
dass die Migrationen durch sind), unbekannte Slugs ergeben 404 ohne Stacktrace, keine
Fixture-Namen in der Restaurantliste, das Anmelde-Rate-Limit greift („Zu viele
fehlgeschlagene Anmeldeversuche"), 0 Konsolenfehler, keine waagerechte Scrollleiste bei
375 px.

**2026-08-29 · Release v2026.08.29.1 ist live.** Nachtrag desselben Tages, ausgelöst durch
zwei Sentry-Befunde am Rand des vorherigen Deploys: **ENDLECH-5** (das Auslieferungsfenster
lieferte 500er — jetzt Wartungsseite mit 503 und `Retry-After`), **ENDLECH-6** (ein
Passkey-Submit ohne Assertion endete in einer nackten Fehlerseite — betrifft `B03`) und die
Vorbereitung des Messenger-Workers in `deploy.sh`. **Keine Migration**, deshalb keine
Sicherung nötig.

Auf Produktion nachgeprüft: Die Fußzeile zeigt `v2026.08.29.1` — der Beleg, dass der neue
Container läuft und das Wartungsflag abgeräumt ist. `/de/login`, `/de/restaurants` und
`/open.json` antworten mit 200, `open.json` führt die echten 3 Lokale. ENDLECH-6 direkt
belegt: Ein POST auf `/de/login` mit leerem `_assertion` ergibt **302** statt der 400 von
vorher, ebenso mit unbrauchbarem JSON; der Scanner-Fall ohne Felder bleibt korrekt **400**
und ist seit diesem Release aus Sentry ausgenommen. `maintenance.html` wird ausgeliefert,
keine Fixture-Namen in der Restaurantliste. 614 Tests grün, Prod-Container baut fehlerfrei.

Das Rate-Limit wurde **nicht** erneut gegen Produktion gefahren — es ist beim vorherigen
Release belegt, durch Tests abgedeckt, und ein Nachweis hier hätte die eigene Adresse für
eine Stunde gesperrt.

⚠ Offen aus dem Deploy-Protokoll: `APP_API_BASE_URL` steht weiterhin nicht in der
`.env.local` (BF-29) — die API baut ihre Bild-URLs aus dem Host-Header. Der Deploy warnt
bei jedem Lauf.

⚠ **Der Messenger-Worker ist vorbereitet, aber nicht in Betrieb.** `deploy.sh` hält jetzt
`var/worker.lock`, bevor der Arbeitsbaum wechselt; solange keine Sperrdatei existiert,
überspringt es den Block. Die Umstellung von `MESSENGER_TRANSPORT_DSN=sync://` auf die
Queue braucht erst den Cron (README → *Messenger worker*) und darf **nicht** vorher
erfolgen.

⚠ **Buchführung:** 23 Bestandsfeatures stehen auf `approved`, obwohl ihre Reparaturen mit
v2026.08.29 live gingen. Nur `B03` ist hier auf `deployed` gezogen, weil sein Fix Teil
dieses Releases war und auf Produktion belegt wurde. Der Rest gehört in einem eigenen
Durchgang nachgeführt — pauschal umzuschreiben, was nicht einzeln nachgeprüft wurde, wäre
eine Behauptung statt eines Nachweises.

⚠ **Das Inventar ist an einer Stelle überholt:** Feature `01` steht auf `roadmap`, sein
Code ist aber seit diesem Release live (Commit „Feature 01: Betroffenenrechte — die
Sackgasse ist zu"). Das gehört über `/sdd-erfassen` oder eine QA nachgezogen — hier nicht
selbst geändert, weil unklar ist, ob der Umfang der Spec entspricht.

Nächster Schritt: `/sdd-erfassen B25`. Die Reparaturen von B01, B02, B04 und B23 warten auf
`/sdd-deploy`; das neue Feature `01` beginnt mit `/sdd-spec 01`, `02` ist fünfmal geprüft und
abgenommen (`approved`) — alle vier Befunde behoben, 53/60 belegt, weiter mit `/sdd-deploy 02`
(UA-01 und die letzten Testdaten-/JS-/Mail-Prüfungen sind Vorbedingung für den Konformitätsgrad).
⚠ **Reihenfolge:** erst die offenen Reparaturen ausliefern, dann `02` bauen — es fasst breit in
dieselben Templates.

**2026-08-29 · Feature `04` aufgenommen und spezifiziert.** Wartelisten- und
Konto-Adressen sollen als Kontakte nach Brevo, damit sich überhaupt eine Kampagne
verschicken lässt — der Anlass steht als Risiko 4 im PRD („Wer sich im August einträgt
und im Februar noch nichts gehört hat, ist als Interessent verloren"). Brevo ist heute
nur Versandweg für die einzelne Nachricht; danach hält es einen **Bestand** zu einem
anderen Zweck. Deshalb hängt die Spec an drei Vorbedingungen, die keine Funktion sind:
`docs/datenschutz.md` **existiert nicht** und muss mit dem AV-Vertrag angelegt werden,
der Datenschutzabschnitt auf `/legal` nennt Brevo nicht als Werbeempfänger, und die
Löschkaskade aus Feature `01` (Widerruf, Kontolöschung) muss den Brevo-Kontakt
mitnehmen — sonst überlebt eine Adresse bei einem Dritten die Löschung, der sie lokal
zum Opfer fiel. Einwilligung über eine eigene, nicht vorangehakte Checkbox; der
Bestand geht mit, aber nur **bestätigte** Wartelisten-Einträge und mit
Herkunftshinweis in der ersten Kampagne. Sechs offene Fragen, darunter die
Datenschutzstufe des Projekts, die das PRD nie festgelegt hat.

**Entwurf am selben Tag.** Tragender Gedanke: **kein Anfrage-Ablauf spricht mit Brevo.**
Die Einwilligung erzeugt eine Zeile in einem Auftragsbuch (`marketing_contact`), ein
Cron-Befehl trägt sie nach Brevo. Grund ist BF-48 — Produktion läuft mit `sync://` und
ohne Worker, eine „asynchrone" Messenger-Nachricht liefe dort **synchron im Request**
und hinge die Anmeldung an einen fremden Dienst. Die Tabelle hat bewusst **keinen
Fremdschlüssel** auf die Quellen: Der Widerruf löscht den Wartelisten-Eintrag, und ein
Auftrag, der an ihm hängt, verschwände mit ihm — der Kontakt bliebe für immer in Brevo.
48 von 48 Kriterien abgedeckt, vier davon ausdrücklich **nicht durch Code** (die
Datenschutzerklärung, der AV-Vertrag, der Text der ersten Kampagne und die Reihenfolge).

⚠ **`04` hängt an einem ungeklärten Stand von `01`.** Beim Entwurf am Code nachgeprüft:
`PasswordResetController`, `AccountDeleter`, `WaitlistConfirmationService::revoke()` und
die Limiter `password_reset`/`account_export` **existieren** — Feature `01` ist gebaut
und live. Das Inventar führt es dennoch als `roadmap`; die Zeile gehört nachgeführt.
Für `04` heißt das: Die Löschkaskade greift an drei belegten Stellen, nicht an
vermuteten.

**Aufgabenplan am selben Tag.** 39 Aufgaben in fünf Ebenen. Zwei Entscheidungen prägen
die Reihenfolge: Die **Übersetzungsschlüssel stehen in Ebene 1**, nicht im Feinschliff —
`CatalogueCompletenessTest` scannt auch `src/Form/`, ein Formularlabel ohne Katalogeintrag
färbt die Suite drei Ebenen früher rot. Und das **Anlegen der fünf Kontaktattribute im
Brevo-Konto ist eine eigene Aufgabe**, weil Brevo unbekannte Attribute stillschweigend
verwirft: Ohne diesen Schritt meldet der Sync Erfolg und überträgt nur die nackte Adresse.
46 der 48 Kriterien tragen eine Aufgabe; AK-41 (Uploads) und AK-42 (Kosten je Aufruf) sind
Negativkriterien und bleiben ausdrücklich ohne — sie werden in der QA als *Abwesenheit*
nachgewiesen.

**Gebaut am selben Tag, 37 von 39 Aufgaben.** 621 Tests grün. Zwei Aufgaben bleiben
offen, beide brauchen eine Betreiberentscheidung: **T08** (die fünf Attribute im
Brevo-Konto anlegen — ein Eingriff in ein Produktivkonto, nicht ohne Zustimmung) und
**T39** (die Freigabe-Sperre, die erst fällt, wenn der AV-Vertrag geprüft und OF-01
beantwortet ist). Drei Befunde aus dem Bau: Der Entwurf beschrieb `POST /contacts` als
Upsert-Weg — **der kennt kein `identifierType`**, eine Adressänderung hätte dort einen
zweiten Kontakt erzeugt (jetzt zweistufig: `PUT` über `ext_id`, bei 404 anlegen). Die
Annahme im Plan, es gebe keine Asset-Änderung, war falsch — **Tailwind scannt Templates**,
die neue Admin-Spalte brachte `lg:table-cell` mit und verlangte einen Neubau. Und
`lint:container` ist in diesem Projekt **vorbestehend rot** (Webauthn-Alias-Altlast) und
taugt nicht als Ebenen-Gate; `cache:warmup` trat an seine Stelle.

**QA am selben Tag: 41 von 48 bestanden, nicht abgenommen.** Drei Kriterien sind
durchgefallen, vier nicht prüfbar. Die zwei kritischen Befunde treffen genau die Zusagen,
um derentwillen dieses Feature einen so langen Datenschutzteil hat: **BF-83** — ein
gewöhnlicher Verwaltungs-Statuswechsel befördert eine nie per Double-Opt-In bestätigte
Adresse nach Brevo, weil der Block, der `confirmedAt` nachsetzt, vor dem Registry-Aufruf
steht. **BF-84** — bei zwei Quellen mit derselben Adresse löscht der Widerruf *einer* den
Kontakt der *anderen*, und Brevos `contactDeleted`-Echo der eigenen Löschung tilgt
anschließend die Einwilligung an allen Quellen; ein Nachweis nach Art. 7 Abs. 1
verschwindet, den niemand widerrufen hat. Dazu **BF-86** (hoch): Eine fehlgeschlagene
Übertragung wird nie wieder aufgegriffen — `findOpenForSync()` fragt `FAILED` nicht ab,
während der Kommentar im Enum das Gegenteil behauptet.

Die vier nicht prüfbaren Kriterien hängen alle daran, dass das Brevo-Konto nicht
eingerichtet ist (T08). Neu: 43 Tests, davon fünf zunächst absichtlich rot — die
Reproduktionen.

**Reparatur am selben Tag: BF-83 bis BF-87 behoben, 664 Tests grün.** Die beiden
kritischen Befunde hatten dieselbe Bauart: zwei für sich richtige Stellen, deren
Zusammenspiel die Zusage bricht. BF-83 — der `confirmedAt`-Backfill (ein gewolltes
Bestandsmuster) entwertete die Prüfung der Registry (ein korrekter Vertrag); jetzt wird
der Bestätigungsstand **vor** dem Backfill festgehalten. BF-84 — „eine Zeile je Adresse"
löst EC-01 beim Eintragen und kippte beim Austragen; `scheduleRemoval()` kennt nun die
auslösende Quelle und schreibt die Zeile auf eine verbleibende um, statt zu löschen. Dazu
kam das Echo der eigenen Löschung: `contactDeleted` entwertet die Einwilligung an der
Quelle nicht mehr. **Offen bleiben BF-88** (AV-Vertrag, hängt an OF-01) und **OF-09**
(echte Nebenläufigkeit — der belegte Fall ist behoben, zwei parallele Requests
kollidieren weiterhin).

**Zweiter QA-Durchlauf am selben Tag: 42/48, weiterhin nicht production-ready.** Fünf
Reparaturen halten der Gegenprobe stand; die Löschsemantik wurde über eine Zustandsmatrix
in beiden Reihenfolgen geprüft — kein Kontakt bleibt bei Brevo stehen, wenn alle Quellen
weg sind. **BF-83 war jedoch nur zur Hälfte behoben:** Die Reparatur zog die Prüfung vor
den `confirmedAt`-Backfill, damit ist der *erste* Statuswechsel sauber — der *zweite*
findet das nachgesetzte Feld vor und trägt die nie bestätigte Adresse doch ein. Derselbe
Weg steht dem Bestandsimport offen, der den Eintrag dabei selbst als „Unbestätigt"
anzeigt. Fortgeführt als **BF-89** (kritisch), unabhängig durch Messung und
`code-reviewer` bestätigt. Daraus ein neues projektweites Muster: *Wenn ein Feld zwei
Bedeutungen trägt, ist jede Reparatur an der Reihenfolge ein Aufschub.*

**BF-89 am selben Tag behoben — diesmal an der Ursache.** Die Entwurfsfrage wurde zuerst
beantwortet, und die naheliegende Antwort trug nicht: Der `confirmationToken` bleibt nach
einer Bestätigung absichtlich stehen und unterscheidet die beiden Fälle deshalb **nicht**.
Es gab kein Merkmal. Eingeführt wurde **`selfConfirmedAt`** an beiden Wartelisten
(Migration `Version20260829170000`), gesetzt ausschließlich von `confirm()`; der
Verwaltungs-Backfill setzt weiterhin nur `confirmedAt`. Registry, `aktiveQuellen()` und
die Auswahlregel des Bestandsimports fragen jetzt `hasSelfConfirmed()`, der Vorabfilter in
`applyStatus()` ist entfallen. Auch die Kehrseite ist zu: Ein Nutzer, dessen Eintrag der
Admin weitergesetzt hat, kann seinen Bestätigungslink noch einlösen. Die Datenmigration
setzt `self_confirmed_at = confirmed_at` für den Bestand — vertretbar, weil vorher
gemessen **0 Einträge** eine Werbe-Einwilligung tragen. 674 Tests grün.

**Dritter QA-Durchlauf: 43/48 — AK-05 hält erstmals auf allen drei Wegen.** Fünf
aufeinanderfolgende Statuswechsel an einem nie bestätigten Eintrag erzeugen 0 Kontakte,
der Bestandsimport listet ihn nicht, der echte Link trägt ein. Die Migrationsannahme ist
auf Produktion **strukturell** sicher, nicht nur gemessen: Beide Migrationen gehen
zusammen live, und `marketing_consent_at` entsteht erst in der früheren.

**Die Reparatur führte dabei BF-91 ein** (hoch): `WaitlistConfirmationService::confirm()`
stieg bei einem verwaltungsseitig bestätigten Eintrag früher mit „bereits bestätigt" aus.
Das musste weg — es verschluckte echte Bestätigungen —, aber damit wird
`Entity::confirm()` in einer Lage erreicht, für die es nie geschrieben war: Es setzt auch
den **Status**, und ein gewonnener Kunde fällt auf „bestätigt" zurück (gemessen:
`converted` → `confirmed`, Fenster 7 Tage). **Der Rückfall bleibt nicht lokal:** Er
wandert über `recordWaitlistEntry()` bis nach Brevo (`FUNNEL_STATUS = confirmed`), womit
eine Kampagne den gewonnenen Kunden wieder erreicht — **AK-08 fällt durch**. Dazu bekommt
das Team erneut eine „Neue Anmeldung"-Meldung. Daraus das zweite Muster dieses Features:
*Wer eine Abbruchbedingung entfernt, prüft, was dahinter liegt.* Zusätzlich **BF-92**
(niedrig): `docs/data-model.md` führt Feature 04 überhaupt nicht — weder die neue Tabelle
noch eine der vier neuen Spalten.

**2026-08-30 · BF-91 und BF-92 behoben.** `confirm()` setzt den Status **nur noch aus
`PENDING` heraus** — ein fortgeschrittener Vertriebsstand ist die jüngere Information —,
und beide Bestätigungs-Controller merken sich vor dem Aufruf, ob der Vorgang beim Team
überhaupt neu war. Der dritte Effekt (`FUNNEL_STATUS`) löst sich damit von selbst.
Gegenprobe: Eintrag auf `converted` behält seinen Stand, 0 Team-Meldungen,
`FUNNEL_STATUS=converted`, Selbstbestätigung trotzdem festgehalten; der Normalfall
unberührt. `docs/data-model.md` führt jetzt die Entity, alle vier Spalten, beide Enums
und beide Migrationen. **Dabei ein vorbestehender Rückstand sichtbar geworden:** Die
Migrations-Historie listete 26 Einträge bei 34 Dateien — sechs aus Feature `01`/`02`
fehlen. Sie wurden als Lücke vermerkt und **nicht** nachgetragen; fremde Features
gehören gegen den Code geprüft, nicht abgeschrieben. 677 Tests grün.

**2026-08-30 · Vierter QA-Durchlauf: 43/48 und der erste ohne neuen Befund** — damit
`approved`. Geprüft wurde diesmal nicht der Fall, um den es gerade ging, sondern die
**vollständige Zustandsmatrix** von `confirm()`: alle sechs Ausgangszustände, jeweils bis
in das Brevo-Attribut, und **beide** Wartelisten. Dabei fiel eine Lücke der eigenen
Prüfung auf: Die ersten drei Durchläufe hatten fast alles am Partner-Weg gemessen, obwohl
`confirm()` in beiden Entities getrennt steht. Der Organisations-Weg ist jetzt einzeln
belegt und als Test festgehalten. 681 Tests grün.

⚠ **Auslieferbar heißt hier nicht betriebsbereit.** Vier Kriterien (AK-07, AK-10, AK-24,
AK-27) bleiben unprüfbar, solange das Brevo-Konto nicht eingerichtet ist (**T08**), und
**BF-88** (AV-Vertrag, hängt an OF-01) ist offen. Der Code darf raus — ohne Schlüssel ist
die Funktion still aus —, aber die **Freigabe-Sperre T39** hält den ersten echten Lauf
auf, bis beides steht. Nächster Schritt: `/sdd-deploy 04`.

**2026-08-30 · Feature `05` aufgenommen und spezifiziert.** Ein Presse-Kit stand nirgends
im Inventar — wer heute über die Plattform schreiben will, findet keinen freigegebenen
Beschreibungstext, kein brauchbares Logo und keinen Namen eines Verantwortlichen. Die Spec
setzt eine eigene Seite `/presse` an (Verweisblock auf `/about`), mit Boilerplate in drei
Längen, Faktenblatt aus derselben Quelle wie `/open`, einem Bildpaket, Person und Zitaten,
Meldungen und dem Kontakt `support@endlech.lu`.

⚠ **Drei Vorbedingungen sind keine Funktion und blockieren die Auslieferung:** Die
**Vektormarken existieren nicht** (es gibt nur `logo.png` mit 10000 × 7664 px — ein
Rasterlogo taugt im Zeitungsdruck nichts), **`support@endlech.lu` gibt es im Projekt
nicht**, und die **Betreiberangaben müssen erst feststehen**. Letzteres zieht `/legal` mit:
Das Impressum nennt heute nur „Endlech.lu, Luxemburg" und genügt damit weder Art. 11 der
luxemburgischen E-Commerce-Regelung noch dem Presserecht.

Zwei Entscheidungen sind bewusst und nicht umkehrbar, sobald das Material verbreitet ist:
Veröffentlicht werden die **Privatanschrift** des Betreibers und die **Gesundheitsangabe**
aus der Gründervita (SMA2) — beide stehen heute schon öffentlich, werden hier aber gezielt
zur Weiterverbreitung freigegeben. Damit steht **OF-01 aus `docs/datenschutz.md` (die
Datenschutzstufe des Projekts) erneut auf der Tagesordnung**; die bisherige Annahme Stufe B
trägt eine besondere Kategorie nach Art. 9 nicht ohne Weiteres. Nächster Schritt:
`/sdd-architektur 05`.

**Entwurf am selben Tag.** Aufbau wie Feature `03`: Struktur als unveränderliche
Datenstrukturen unter `App\Press\`, Texte in einer eigenen Übersetzungsdomain `press`,
Zahlen aus derselben Quelle wie `/open`. Keine Entität, keine Migration. Zwei
Entscheidungen tragen den Entwurf: Die **Betreiberangaben stehen als Parameter** und
werden von `/presse` und `/legal` aus derselben Stelle gelesen — vier Katalogeinträge
wären vier Stellen, an denen eine Anschrift auseinanderlaufen kann, und der Katalogtest
prüft Vollständigkeit, nicht Gleichheit. Und das **Materialpaket erzeugt ein
Konsolenbefehl** aus derselben Liste, aus der die Seite ihre Vorschauen baut; ein Prüflauf
öffnet die committete Datei und vergleicht sie damit (OF-07 entschieden). 44 von 44
Kriterien abgedeckt, zwei davon ausdrücklich **nicht durch Code** (AK-29: die Presseadresse
nimmt Post an; AK-41: ein Beitrag entsteht ohne Rückfrage).

**Zwei Kriterien kamen beim Entwurf dazu** (AK-43, AK-44): Kurzbeschreibung und kanonische
Adresse. Die Twig-Blöcke dafür stehen seit Feature `03` leer bereit und wären eine Zeile —
aber ohne Kriterium füllt sie niemand, prüft sie niemand, und der nächste Umbau entfernt
sie unbemerkt. Bei einer Seite, deren Zweck über eine Suchmaschine läuft, wäre das die
teuerste Auslassung. Damit sind es **44 Kriterien**, alle abgedeckt.

⚠ **Drei Funde beim Entwerfen, die sonst erst beim Bauen aufgefallen wären:**
**(1)** `ext-zip` fehlt in der CI-Erweiterungsliste (`.github/workflows/ci.yml:38`) — der
Paket-Prüflauf würde dort mit einer Meldung über eine unbekannte Klasse rot, die wie ein
Codefehler aussieht. **(2)** Der Service Worker cacht Bilder cache-first: Eine ersetzte
Logo-Vorschau bliebe im Browser wiederkehrender Besucher alt, während das Paket (das
strukturell nie gecacht wird) bereits neu ist — AK-17 bricht dann dort, wo kein Prüflauf
hinsieht. Regel: Wer eine Datei in `public/presse/` ersetzt, erhöht `CACHE_VERSION`.
**(3)** Das vorhandene Gründerporträt ist 2048 × 1365 px und damit drucktauglich — es
braucht **keine** zweite Datei fürs Presse-Kit, die beim nächsten Bildwechsel
auseinanderfiele.

**Aufgabenplan am selben Tag.** 37 Aufgaben in fünf Ebenen, keine Migration. Drei
Entscheidungen prägen die Reihenfolge: Die **Übersetzungsdomain `press` steht in Ebene 1**
— dieselbe Lehre wie bei Feature `04`, ein Katalogeintrag, der erst beim Feinschliff
entsteht, färbt den Prüflauf drei Ebenen früher rot. Die **funktionalen Läufe stehen am
Ende von Ebene 4**, nicht in Ebene 3: Sie rendern die Seite, und ohne Vorlagen prüften sie
eine Fehlerseite (bei `03` lagen sie in Ebene 3 und standen bis Ebene 4 rot). Und
**`ext-zip` ist eine eigene Aufgabe** (T06), weil die CI die Erweiterung heute nicht
installiert — ohne sie bricht der Paket-Prüflauf auf dem Runner mit einer Meldung ab, die
wie ein Codefehler aussieht.

43 der 44 Kriterien tragen eine Aufgabe. **AK-41 trägt bewusst keine**: „Ein Beitrag
entsteht allein mit `/presse`" prüft das Zusammenspiel aller anderen Kriterien und lässt
sich nicht bauen, nur abnehmen — Handprüfung in der QA, wie AK-29, dessen Nachweis eine
Testmail ist. Nächster Schritt: `/sdd-build 05` — **aber erst, wenn VB-01 bis VB-03
stehen**: ohne Vektormarken entsteht ein leeres Paket, ohne gelesenes Postfach nennt die
Seite eine tote Adresse, ohne Betreiberangaben tragen Impressum und Faktenblatt denselben
Platzhalter.

**2026-08-30 · Befund an der App-Hülle, herausgelöst aus Feature `05`.** Der Selbsttest
des Presse-Kits fand einen Überlauf, den keine bisherige Prüfung finden konnte: **Die
Kopfzeile lässt die Seite zwischen 768 px und 1000 px waagerecht scrollen** — abgemeldet
+36 px bei 768 px, **angemeldet +81 px und bis unter 1000 px hinauf**. Ursache ist der
`md:`-Umbruchpunkt in `base.html.twig`: Ab genau 768 px erscheinen Hauptnavigation
(416 px) und Kontobereich, bevor Platz dafür ist. Betrifft **jede Seite**, nachgemessen
auf `/about`, `/vergleich`, `/open` und `/partner`.

⚠ **Richtigstellung vom selben Tag:** Der Befund war **nicht neu**. Er steht seit der QA
von Feature `02` als **BF-80** in `features/befunde.md` — der Baubericht behauptete das
Gegenteil, und das war falsch. Der Ertrag der Messung bleibt: **BF-80 kannte den
angemeldeten Fall nicht**, und dort ist der Überlauf doppelt so groß und reicht bis unter
1000 px. BF-80 ist ergänzt statt verdoppelt; die vollständige Messreihe steht als
**Bekannte Lücke 7** in `docs/app-shell.md`. Übersehen wurde er zweimal aus demselben
Grund: Die Kriterien von `02`, `03` und `05` nennen 320 px, die QA von `03` zusätzlich
375 px — unterhalb von `md:` ist die Navigation ausgeblendet. **Nicht behoben:** Der Umbruchpunkt von `md:` auf
`lg:` zu ziehen oder die Kopfzeile umbrechen zu lassen ist eine Entwurfsentscheidung an
der App-Hülle und gehörte in kein Feature nebenbei hinein. Der Befund hat heute **kein
Feature-Zuhause** — er trifft die Hülle, die B25 nur für die Bottom-Navigation abdeckt.

**2026-08-30 · QA von `05`: 31 von 44 bestanden, nicht abgenommen.** Der schwerste Befund
entstand erst, als die Prüfung den **Regelfall herstellte**, statt ihn abzuwarten:
**Sobald das Materialpaket existiert, antwortet `/presse` in allen vier Sprachen mit
HTTP 500** (BF-97, kritisch). `_material.html.twig:44` ruft `package.publicPath` — auf
`PressPackage` ist der Pfad eine Klassenkonstante, und Twig löst `object.attr` nie über
eine Konstante auf. Verborgen blieb das, weil die Umgebung kein Paket hat: Der einzige
Lauf, der den Abschnitt anfasst, verzweigt an `PressPackage::exists()` und prüfte nur den
Ersatzzweig. **Der Regelfall des Features lag in keinem einzigen Test.** Daraus ein neues
projektweites Muster: *Ein Ast, der nie ausgeführt wird, ist keine Abdeckung — er sieht
nur so aus.*

Dazu **BF-98** (mittel): Zusammengesetzte Übersetzungsschlüssel fallen durch beide
Prüfläufe — entfernt man einen aus allen vier Katalogen, bleibt die Suite grün und die
Seite zeigt den rohen Schlüssel. Und drei Befunde ohne Codeanteil (BF-93 bis BF-96), die
sämtlich an den drei Vorbedingungen hängen.

Der **Angriffsdurchlauf blieb ohne Fund**: keine fremde Ressource, keine Reflexion von
Eingaben, 405 auf allen Schreibwegen, keine Personendaten in Protokollen, kein Geheimnis
im Quelltext; axe-core meldet in allen vier Sprachfassungen null Verstöße. Zwei neue
Prüfläufe, davon einer absichtlich rot als Befund-Nachweis. **Reihenfolge für die
Reparatur: erst BF-97, dann VB-01** — sonst macht das Ablegen der Marken die Seite kaputt
statt fertig. Nächster Schritt: `/sdd-build 05`.

**2026-08-30 · BF-97 und BF-98 behoben.** Die Reparatur des kritischen Befunds ist eine
Methode: `PressPackage::publicPath()`. Gegen die Reproduktion aus dem Testbericht geprüft —
mit angelegtem Paket antworten alle vier Sprachfassungen mit **200**, der Knopf liest sich
„Presse-Paket herunterladen (ZIP · 244 kB)". **Beim Beheben fiel ein Fehler im
Befund-Nachweis selbst auf:** Sein `tearDown()` rief `parent::tearDown()` nicht, der Kernel
blieb zwischen den Testmethoden gebootet, und die Folgefehler sahen aus wie ein zweiter
Anwendungsfehler. BF-98 ist über vierzehn nachgetragene Schlüssel geschlossen; **beide
Mutationsproben wiederholt** — Entfernen aus allen vier Katalogen färbt den Lauf jetzt rot,
vorher blieb er grün. 741 Tests, kein roter Lauf mehr.

**Nicht repariert, weil ohne Codeanteil:** BF-93 (Betreiberangaben, VB-03), BF-94
(Vektormarken, VB-01), BF-96 (Fotocredit, OF-05). BF-95 wartet auf die Entscheidung zu
OF-09. ⚠ **Reihenfolge bleibt: erst BF-97 ausliefern, dann VB-01** — jetzt erfüllt.

**2026-08-30 · Zweiter QA-Durchlauf von `05`: 37 von 44, kein neuer Befund.** Beide
Reparaturen halten. Der Ertrag ist eine Methode, die aus dem Muster des ersten Durchlaufs
folgt: **den Zustand herstellen, statt auf ihn zu warten.** Vier SVG-Platzhalter abgelegt,
`app:press:package` laufen lassen — damit lief die Materialmechanik zum ersten Mal
vollständig durch: sechs Dateien im Paket, alle vier Sprachabschnitte in der
Bedingungsdatei, fünf Vorschauen ohne eine fehlgeschlagene Anfrage, ein gültiges ZIP über
HTTP, und `PressPackageTest` lief **durch statt zu überspringen** (10 statt 13
übersprungene Tests). **Sechs vormals offene Kriterien sind damit belegt**; die Artefakte
wurden restlos entfernt.

Damit gilt eine ausgesprochene Regel für solche Belege: *Ein Kriterium über die Mechanik
ist bestanden, wenn die Mechanik ausgeführt wurde und hielt; ein Kriterium über den Inhalt
des Materials bleibt offen, bis das Material existiert.* **AK-18 bleibt deshalb offen** —
ob das Paket die Wort-Bildmarke enthält, beantwortet kein grauer Platzhalter.

⚠ **Nächster Schritt ist ausnahmsweise nicht `/sdd-build`.** Von den vier offenen Befunden
hat keiner einen Softwareanteil: Es fehlen die Vektormarken (VB-01), die Betreiberangaben
(VB-03), der Fotocredit (OF-05) und das Postfach (VB-02); BF-95 wartet auf die Entscheidung
zu OF-09. Danach genügt ein kurzer dritter Durchlauf über die sieben betroffenen Kriterien.

**2026-08-30 · VB-01 erfüllt: Die vier Markendateien liegen vor.** Kein Nachbau von Hand —
`public/images/logo.png` wurde mit potrace in zwei Durchgängen nachgezeichnet (Silhouette
über den Alphakanal, Glyphe über die weißen Bildpunkte) und die Abweichung gegen das
Original **gemessen: 0,244 %**, also nur Kantenglättung. Farben aus der Quelldatei
(`#01b6ed` / `#ffffff`), Schriftzug und seine beiden Farbfassungen aus
`templates/base.html.twig`. `make press-kit` erzeugt daraus ein Paket aus sechs Dateien;
die Seite zeigt fünf Vorschauen ohne fehlgeschlagene Anfrage, axe meldet in allen vier
Sprachen null Verstöße, und die Suite meldet **10 statt 13 übersprungene Tests**.

⚠ **Zwei Funde, die erst mit echtem Material sichtbar wurden.** (1) **Die Bilddatei zeigt
ein „ND"-Monogramm** — nicht „Endlech.lu". Dieselbe Datei steckt in der Kopfzeile und über
`bin/generate-pwa-icons.sh` in **allen elf App-Icons**. Auf Nachfrage bestätigt, dass es
die Marke ist; als Beobachtung festgehalten, weil ein Presse-Kit die Marke verbreitet.
(2) **Zwei verschiedene Cyan-Töne** — Fläche `#01b6ed`, Nutzungsbedingungen und Schriftzug
`#0891b2`. Als **OF-11** in der Spec vermerkt, nicht nebenbei vereinheitlicht.

⚠ **Der Schriftzug in den beiden Wort-Bildmarken ist `<text>`, nicht in Pfade
umgewandelt.** Ohne die Schrift auf dem Zielsystem ersetzt der Betrachter sie. Vor der
Freigabe in Illustrator oder Affinity einmal outlinen — steht als Kommentar in beiden
Dateien.

**2026-08-30 · Dritter QA-Durchlauf von `05`: 42 von 44 — abgenommen.** Alle drei
Vorbedingungen sind erfüllt, fünf Kriterien wechselten von offen auf bestanden, und **kein
Prüflauf dieses Features überspringt mehr** (die zehn Übersprungenen der Suite stammen aus
anderen Features). 741 Tests grün, axe null Verstöße in vier Sprachen, Angriffsdurchlauf
ohne Fund — einschließlich der neuen Angriffsfläche: vier SVG von derselben Herkunft, ohne
`<script>`, `onload` oder `foreignObject`.

⚠ **AK-11 bleibt durchgefallen — als Entscheidung, nicht als Mangel.** Es wird keine
Anschrift veröffentlicht (OF-04), damit kann „Betreiber mit vollständiger Anschrift" nicht
bestehen. Das Impressum zitiert § 5 TMG / Art. 11 und erfüllt beides weiterhin nicht;
dieses Feature hat den Zustand sichtbar gemacht, nicht verursacht. **Ob das Kriterium
gestrichen oder zurückgenommen wird, gehört in die Spec und braucht Michaels Zustimmung.**

Offen bleiben **BF-95** (wartet auf OF-09) und neu **BF-99** (mittel): Der Schriftzug der
Wort-Bildmarken ist noch `<text>` statt Pfad — einmal outlinen, dann `make press-kit`
erneut und `CACHE_VERSION` erhöhen. **AK-26** bleibt unprüfbar, solange keine
Pressemitteilung existiert (OF-06). Nächster Schritt: `/sdd-deploy 05`.

**2026-08-30 · Release v2026.08.30 ist live.** Zwei Features auf einmal: `05` (Presse-Kit)
und `04` (Marketing-Kontakte in Brevo), dazu die Fußzeilen-Adresse. **Zwei Migrationen**
liefen mit, beide additiv (`marketing_contact`, `marketing_consent_at`, `self_confirmed_at`).
Der Betreiber hat vorher gesichert; `BREVO_API_KEY` ist auf dem Server leer, damit ist
Feature 04 still aus, bis T08 und BF-88 stehen.

Auf Produktion nachgeprüft: Fußzeile zeigt **v2026.08.30** (Beleg, dass der neue Container
läuft), `/presse` in allen vier Sprachen 200, das Paket lädt (1 097 194 Bytes, gültiges ZIP,
sechs Einträge, kein defekter), alle vier Marken als `image/svg+xml`, das Faktenblatt zeigt
**3 / 3 / 2 — identisch mit `/open.json`**, die Fußzeile nennt `support@endlech.lu`, das
Impressum trägt erstmals einen Namen, die Einwilligungs-Checkbox von Feature 04 rendert
(Beleg, dass die Migrationen durch sind), unbekannte Adressen ergeben 404 ohne Stacktrace,
keine Testdaten in der Restaurantliste.

⚠ **Ein kritischer Befund direkt nach dem Deploy: BF-100.** Der sprachfreie Kurzlink
`/presse` läuft in eine endlose Weiterleitungsschleife — das neue Verzeichnis
`public/presse/` trägt denselben Namen wie die Route, und Apaches `mod_dir` schickt
`/presse` auf `/presse/`, während Symfony zurückschickt. **Lokal unsichtbar**, weil der
Entwicklungsserver kein `mod_dir` hat. Feature `05` steht deshalb wieder auf `review`;
der Rest des Releases ist unberührt. Weiter mit `/sdd-build 05`.

**2026-08-30 · BF-100 behoben.** Zwei Teile, und der zweite ist der, den man übersieht:
Das Verzeichnis heißt jetzt `public/presse-kit/` — **und** eine einzige Route matcht
`/presse` wie `/presse/` exakt (`path: /presse{trailing_slash}`). Ohne den zweiten Teil
hätte die Reparatur die Schleife nur für neue Besucher gelöst: Der Sprung von `mod_dir`
war ein **301**, den Browser dauerhaft behalten. Zwei getrennte Routen genügen dafür
nicht — gemessen: Die zuerst definierte zieht ihre Trailing-Slash-Regel, bevor die zweite
geprüft wird, und je nach Reihenfolge bleibt ein 301 auf der einen oder anderen Form
stehen. Jetzt gehen beide **direkt mit 302** auf die Sprachfassung.

Neu: `RouteDirectoryCollisionTest` prüft **die Ursache statt des Verhaltens** — kein
Verzeichnis unter `public/` darf so heißen wie eine Route. Zweimal gegengeprüft: Legt man
`public/presse/` an, wird der Lauf rot und nennt die Route beim Namen. ⚠ Beim Schreiben
hatte der Lauf selbst ein Loch — er übersprang Pfade mit `{`, also ausgerechnet die neue
Form `/presse{trailing_slash}`. Er vergleicht jetzt den **statischen Anfang** des
Segments.

⚠ Die alte Download-Adresse `/presse/presse-kit-endlech-lu.zip` ist damit **404**. Sie
war eine halbe Stunde lang öffentlich; wer sie in der Zeit verlinkt hat, muss sie
nachziehen. **`design.md` nennt noch `public/presse/`** — als OF-12 in der Spec vermerkt,
`sdd-build` ändert den Entwurf nicht.

**2026-08-30 · Release v2026.08.30.1 ist live — BF-100 behoben.** Nachtrag desselben
Tages, **ohne Migration**. Auf Produktion nachgeprüft, und diesmal war die Nachprüfung
nicht Formsache, sondern der eigentliche Beleg — das Verhalten entsteht nur unter Apache:

- `/presse` → **302, eine einzige Weiterleitung**, endet mit 200 auf `/lb/presse`
  (vorher: 50 Weiterleitungen und Abbruch). `/presse/` ebenso. **Kein 301 mehr auf
  keiner der beiden Formen** — das war der Teil, ohne den die Reparatur nur für neue
  Besucher gewirkt hätte.
- Fußzeile zeigt **v2026.08.30.1**, Paket unter `/presse-kit/…` mit 200 und gültigem ZIP
  (6 Einträge), alte Adresse **404**, `/presse-kit/` **404** (keine Dateiliste).
- Im Browser über alle vier Sprachen: 5 Vorschauen, **0 gebrochen**, 0 fehlgeschlagene
  Anfragen, **axe 0 Verstöße**, 375 px ohne Querscrollen.

⚠ **Nebenbefund, projektweit und vorbestehend:** `/favicon.ico` und
`/apple-touch-icon.png` antworten mit **404**. Der Browser fragt sie beim ersten Besuch
von selbst an — daher ein Konsolenfehler auf jeder Seite, nicht nur hier. Kein Bezug zu
Feature 05; hier vermerkt, weil er bei der Nachprüfung sichtbar wurde.

Damit ist Feature `05` **deployed**. Offen bleiben BF-95 (wartet auf OF-09), BF-99
(Schriftzug outlinen) und OF-11 (zwei Cyan-Töne) — alle drei ohne Codeanteil.

**2026-08-30 · T08 war längst erledigt — das Inventar sagte das Gegenteil.** Beim Versuch,
die Kontaktattribute anzulegen, ergab die Abfrage des Brevo-Kontos: **Alle fünf existieren
bereits** (`CONTACT_NAME`, `ORGANISATION`, `LOCALE`, `ORIGIN`, `FUNNEL_STATUS`, sämtlich
`normal`/`text`, wie `MarketingPayloadMapper` sie erwartet). Angelegt wurden sie beim Bau
von Feature `04`; die Aufgabe war dort auch abgehakt.

⚠ **Der Fehler saß in der Kopfzeile von `04/tasks.md`**, die T08 weiter als offen führte,
während dieselbe Datei ihn zwölf Zeilen tiefer als erledigt auswies. Von dort wanderte die
Falschaussage in dieses Inventar, in vier QA-Berichte und in zwei Antworten an den
Betreiber. Richtiggestellt; die Kopfzeile trägt den Widerspruch jetzt als Warnung.

**Folge:** Die vier Kriterien von `04`, die als *nicht prüfbar* geführt sind (AK-07,
AK-10, AK-24, AK-27), waren es mit der Begründung „das Konto ist nicht eingerichtet". Die
trägt nicht mehr — sie sind prüfbar, sobald ein Schlüssel gesetzt ist. Für die
Inbetriebnahme bleibt allein **BF-88** (AV-Vertrag) offen.

**Zwei Namensräume:** Einträge mit Präfix `B` sind **Bestand** — gebaut, bevor die
SDD-Kette da war, und rückwirkend erfasst. Einträge **ohne** Präfix (`01`, `02`, …)
entstehen durch die Kette und hatten eine Anforderung, bevor Code existierte. An der ID
ist damit ohne Nachschlagen erkennbar, ob die `spec.md` eine Vorgabe oder eine
Rekonstruktion ist. Die ID ändert sich nie, auch wenn die
Bearbeitungsreihenfolge eine andere ist.

Ein Bestandsfeature läuft **nicht** durch `sdd-tasks` und nicht durch den regulären
Eingang von `sdd-build`. Der Weg ist: `bestand` → `/sdd-erfassen BNN` →
`rekonstruiert` → `/sdd-qa BNN`.

## Inventar

| ID | Feature | Prio | Status | Abhängig von | Zuletzt |
|---|---|---|---|---|---|
| 01 | Betroffenenrechte: Konto löschen, Daten exportieren, Passwort zurücksetzen | P0 | roadmap | B01, B04, B19 | 2026-08-23 · aus BF-04 herausgelöst |
| 02 | Barrierefreiheit der Plattform (EN 301 549 / RAWeb) | P0 | **deployed** | B01–B26 | 2026-08-29 · live in v2026.08.29 |
| 03 | Vergleichsseiten (vs. Google Maps, Wheelmap, TripAdvisor) | P1 | **deployed** | B05, B13, B24, B16, 02 | 2026-08-29 · live in v2026.08.29, auf Produktion nachgeprüft |
| 04 | Marketing-Kontakte in Brevo | P1 | **deployed** | B01, B14, B15, B22, 01 | 2026-08-30 · live in v2026.08.30, Migrationen durch, auf Produktion belegt |
| 05 | Presse-Kit | P2 | **deployed** | B13, B16, B24, 02, 03 | 2026-08-30 · live in v2026.08.30.1, auf Produktion nachgeprüft |
| B01 | Registrierung & E-Mail-Bestätigung | P0 | **approved** | — | 2026-08-23 · QA³: 17/20, nur mittlere Befunde offen |
| B02 | Anmeldung mit Passwort | P0 | **approved** | B01 | 2026-08-24 · QA²: 16/17, repariert |
| B03 | Passkey-Anmeldung & -Verwaltung | P0 | **deployed** | B01, B02 | 2026-08-29 · ENDLECH-6 live in v2026.08.29.1, auf Produktion belegt (302 statt 400) |
| B04 | Profil, Avatar & eigene Einreichungen | P0 | **approved** | B01, B11 | 2026-08-24 · QA 2. Durchlauf: 23/24, drei Befunde *mittel* |
| B05 | Restaurantsuche, Filter & Sortierung | P0 | **approved** | B07, B08 | 2026-08-24 · QA: 24/24, zwei Befunde *niedrig* |
| B06 | Restaurant-Detailseite | P0 | **approved** | B07, B08, B09, B10 | 2026-08-24 · QA: 23/23, **kein Befund** |
| B07 | Öffnungszeiten | P1 | **approved** | — | 2026-08-24 · QA: 17/17, ein Befund *niedrig* |
| B08 | Küchen-Typen | P1 | **approved** | — | 2026-08-24 · QA: 16/16, zwei Befunde *niedrig* |
| B09 | Restaurantfotos & Galerie | P1 | **approved** | B20 | 2026-08-24 · QA: 18/18, ein Befund *mittel* |
| B10 | Haltestellen in der Nähe | P2 | **approved** | — | 2026-08-24 · QA 2. Durchlauf: 24/24 |
| B11 | Restaurant vorschlagen (Wizard) | P0 | **approved** | B01 | 2026-08-24 · QA: 18/19, ein Befund *mittel* |
| B12 | Startseite | P1 | **approved** | B05 | 2026-08-24 · QA²: 15/15, BF-64 repariert |
| B13 | Statische Inhaltsseiten | P2 | **approved** | — | 2026-08-24 · QA: 14/14, ein Befund *mittel* |
| B14 | Partner-Warteliste | P0 | **approved** | — | 2026-08-24 · QA: 28/28, ein Befund *mittel* |
| B15 | Organisations-Wartelisten | P0 | **approved** | B14 | 2026-08-24 · QA: 27/27, ein Befund *niedrig* |
| B16 | Transparenzseite `/open` | P1 | **approved** | B18 | 2026-08-24 · QA: 29/29, ein Befund *mittel* |
| B17 | Offener Datensatz & Kennzahl-Endpunkte | P1 | **approved** | B18 | 2026-08-24 · QA: 25/25, drei Befunde *niedrig* |
| B18 | Finanzposten & Kennzahl-Snapshots | P1 | **approved** | B19 | 2026-08-24 · QA: 29/29, ein Befund *mittel* |
| B19 | Admin-Zugang & Dashboard | P0 | **approved** | B02 | 2026-08-24 · QA: 17/17, ein Befund *mittel* |
| B20 | Restaurantverwaltung (Admin) | P0 | **approved** | B19 | 2026-08-24 · QA: 19/20, ein Befund *mittel* |
| B21 | Vorschläge prüfen (Admin) | P0 | **approved** | B19, B11 | 2026-08-24 · QA: 20/20, ein Befund *mittel* |
| B22 | Wartelisten-Verwaltung (Admin) | P1 | **approved** | B19, B14, B15 | 2026-08-24 · QA: 30/30, ein Befund *niedrig* |
| B23 | REST-API v1 (iOS-Backend) | P0 | **approved** | B01, B05 | 2026-08-24 · QA 2. Durchlauf: 34/35, drei Befunde *mittel/niedrig* |
| B24 | Mehrsprachigkeit | P1 | **approved** | — | 2026-08-25 · QA 16/16, BF-68 bis BF-72 behoben |
| B25 | PWA & mobile Navigation | P1 | rekonstruiert | — | 2026-08-23 |
| B26 | Cookie-Banner | P2 | rekonstruiert | — | 2026-08-23 |

## Was jedes Feature umfasst

| ID | Umfang | Wo es lebt |
|---|---|---|
| 02 | Tastatur und Fokus, Wahrnehmbarkeit, Formulare, Zielgrößen, Sprache und Struktur, Mobil und App-Hülle, Verwaltung, Barrierefreiheitserklärung, Rückmeldeweg | projektweit; neu: Erklärungsseite `/barrierefreiheit` samt Meldeformular |
| 03 | Fußzeilenbereich „Vergleiche“, Übersichtsseite, drei Vergleichsseiten mit Kurzfazit, Merkmalstabelle, Gegenposition und häufigen Fragen | neu: `/vergleich` und `/vergleich/{slug}`; berührt Fußzeile und Kopfbereich der App-Hülle |
| 04 | Einwilligungs-Checkbox in drei Formularen, Abgleich der Kontakte in beide Richtungen, Löschkaskade bei Widerruf und Kontolöschung, Bestandsübertragung mit Trockenlauf, Sync-Stand in der Wartelisten-Verwaltung | berührt Partner-, Organisations- und Registrierformular sowie `/admin/warteliste`; neu: `docs/datenschutz.md` und der Werbe-Empfänger im Datenschutzabschnitt von `/legal` |
| 05 | Presseseite mit Boilerplate in drei Längen, Faktenblatt aus den Livezahlen, Bildpaket zum Herunterladen samt Nutzungsbedingungen, Person und Zitate, Meldungen, Pressekontakt | neu: `/presse` und ein Verweisblock auf `/about`; berührt Fußzeile und `/legal` (Betreiberangaben) |
| B01 | Registrierformular, Token 24 h, Bestätigungsmail, erneutes Senden, Hinweisseite | `RegistrationController`, `EmailVerificationController`, `RegistrationType`, `templates/registration/`, `templates/email_verification/`, `email/verification.html.twig` |
| B02 | `form_login`, `remember_me`, Abmelden, Zugriffsregeln der `main`-Firewall | `SecurityController`, `config/packages/security.yaml`, `templates/security/login.html.twig` |
| B03 | WebAuthn-Anmeldung ohne E-Mail-Eingabe, Passkeys anlegen/umbenennen/entfernen | `Security/PasskeyAuthenticator`, `Security/WebauthnUserEntityRepository`, `PasskeyController`, `Entity/WebauthnCredential`, `partials/_passkey_*`, `passkey_ui_controller.ts` |
| B04 | Name, E-Mail, Avatar hoch- und abladen, Passwortwechsel, Liste eigener Einreichungen | `ProfileController`, `ProfileType`, `ChangePasswordType`, `Service/AvatarUploadService`, `templates/profile/`, `partials/_avatar.html.twig` |
| B05 | Liste mit 14 Filtern, 3 Sortierungen, Seitenblättern zu je 6 | `RestaurantController::index`, `RestaurantRepository::findPaginated`, `templates/restaurant/index.html.twig` |
| B06 | Detailseite: Merkmale, Maße, Kontakt, Sozialkonten, Bestellwege, Galerie | `RestaurantController::show`, `templates/restaurant/show.html.twig`, `Entity/OrderingOption`, `Enum/OrderingPlatform` |
| B07 | Mehrere Zeitfenster je Tag, „jetzt geöffnet", nächste Öffnung, Filter `?open=1` | `Entity/OpeningHour`, `Service/OpeningHoursService`, `Twig/OpeningHoursExtension`, `OpeningHourType`, `opening_hours_form_controller.ts`, `partials/_opening_hours.html.twig` |
| B08 | Küchen als eigene Entität, Autocomplete mit Anlegen im Formular, Filter, Abzeichen | `Entity/Cuisine`, `CuisineRepository`, `Api/CuisineApiController`, `tom_select_controller.ts`, `partials/_cuisine_badges.html.twig` |
| B09 | Hochladen, Alt-Text, Sortieren, Löschen, Titelbild, Lightbox | `Entity/RestaurantImage`, `Service/ImageUploadService`, `AdminRestaurantController` (Bild-Routen), `image_sort_controller.ts` |
| B10 | HAFAS-Abfrage, 24 h Cache, stiller Ausfall ohne Schlüssel | `Service/PublicTransportService`, `DTO/NearbyStop`, `partials/_nearby_stops.html.twig` |
| B11 | Fünfstufiger Wizard, 12 dreiwertige Pflichtfragen, Dankeseite | `CommunityController`, `RestaurantSuggestionType`, `Entity/RestaurantSuggestion`, `Enum/TriState`, `suggestion_wizard_controller.ts`, `partials/_tristate_field.html.twig` |
| B12 | Hero, „So funktioniert's", Top-6, „Warum Endlech.lu?", Handlungsaufruf | `HomeController`, `templates/home/index.html.twig`, `partials/_hero_badges.html.twig` |
| B13 | `/about`, `/criteria`, `/legal` inkl. Datenschutzabschnitt | `AboutController`, `KriterienController`, `ImpressumController` und die zugehörigen Templates |
| B14 | Landing-Page, Warteliste, Honeypot, Rate Limit, Double-Opt-In, interne Meldung | `PartnerController`, `PartnerWaitlistType`, `Entity/PartnerWaitlistEntry`, `Waitlist/`, `templates/partner/`, `email/partner/` |
| B15 | Übersicht plus drei Zielgruppenseiten, typabhängige Prüfung, ohne JavaScript bedienbar | `OrganisationController`, `OrganisationWaitlistType`, `Entity/OrganisationWaitlistEntry`, `organisation_type_controller.ts`, `templates/organisation/`, `email/organisation/` |
| B16 | Kennzahlen zu Plattform, Wirkung, Finanzen; Verlauf, Diagramme, Druckansicht | `OpenController`, `Open/OpenStatsService`, `Open/AccessibilityScore`, `Open/CantonResolver`, `templates/open/` |
| B17 | `/open.json`, `/open/dataset.csv`, `/open/dataset.json` unter CC BY 4.0 | `Controller/Open/OpenDataController` |
| B18 | Finanzposten pflegen, Quartalssperre, monatlicher Snapshot per Cron und von Hand | `AdminFinanceController`, `FinanceEntryType`, `Entity/FinanceEntry`, `Entity/MetricSnapshot`, `Open/MetricSnapshotService`, `Command/`, `Schedule.php` |
| B19 | Admin-Shell, Rollenschranke, Kennzahlenübersicht, Sprachumschalter des Admins | `AdminDashboardController`, `AdminLocaleController`, `Service/AdminStatsService`, `templates/admin/base.html.twig` |
| B20 | Vollständiges CRUD, Verifizieren, alle Restaurantfelder inkl. Maßen und Koordinaten | `AdminRestaurantController`, `RestaurantType`, `templates/admin/restaurant/` |
| B21 | Vorschläge prüfen, genehmigen (erzeugt das Restaurant), ablehnen | `AdminSuggestionController`, `templates/admin/suggestion/` |
| B22 | Beide Wartelisten in einer Liste, Status pflegen, Restaurant zuordnen | `AdminWaitlistController`, `templates/admin/waitlist/` |
| B23 | JWT, CORS, Rate Limit, Fehlerformat, Swagger, sechs Endpunkte | `Controller/Api/V1/`, `Api/` (Transformer, `AssetUrlBuilder`), `EventSubscriber/`, `config/packages/{lexik_jwt,nelmio_*}.yaml` |
| B24 | Vier Sprachen, `/{_locale}`-Routing, Umschalter, hreflang, acht Kataloge | `config/packages/translation.yaml`, `config/routes.yaml`, `partials/_language_switcher.html.twig`, `language_switcher_controller.ts`, `translations/` |
| B25 | Manifest, Service Worker, Offline-Rückfall, App-Icons, Bottom-Navigation | `public/manifest.webmanifest`, `public/sw.js`, `public/offline.html`, `public/icons/`, `partials/_bottom_nav.html.twig`, `assets/app.ts` |
| B26 | Banner beim ersten Besuch, Wahl 365 Tage im Cookie, Wiederöffnen aus der Fußzeile | `cookie_consent_controller.ts`, `partials/_cookie_banner.html.twig` |

## Reihenfolge der Rückerfassung

Nach **Risiko**, nicht nach Nummer. Die Rückerfassung ist die Eintrittskarte für
`sdd-qa`, und die QA ist an einem Bestandsprojekt ein Sicherheitsaudit — wer mit der
Darstellung anfängt, auditiert zuletzt, was zuerst brennen kann.

```
B01 → B02 → B03 → B04 → B23 → B19 → B14 → B15 → B22 → B17     Rang 1
    → B10 → B18                                                Rang 2
    → B11 → B09 → B20 → B21 → B08                              Rang 3
    → B05 → B06 → B07 → B12 → B16 → B13 → B24 → B25 → B26      Rang 4
```

### Rang 1 — Personendaten und Zugriffsregeln

| Feature | Warum hier |
|---|---|
| B01 · B02 · B03 | die drei Wege in ein Konto. B03 trägt zusätzlich einen zweiten Authenticator an derselben Firewall und einen Signaturzähler als Klon-Schutz |
| B04 | Änderung eigener Stammdaten plus Datei-Upload in ein öffentlich ausgeliefertes Verzeichnis |
| B23 | zweite, staatenlose Tür zu denselben Konten — eigene Firewalls, eigenes Rate Limit, eigenes Fehlerformat. Ein Fehler hier ist von außen ohne Sitzung erreichbar |
| B19 | die Rollenschranke selbst. Alles darunter verlässt sich darauf |
| B14 · B15 · B22 | E-Mail-Adressen **Dritter** mit Einwilligungszeitpunkt, Double-Opt-In-Token und einer Admin-Ansicht darüber |
| B17 | veröffentlicht aktiv einen Datensatz. Was hier versehentlich mitgeht, ist nicht zurückzuholen |

### Rang 2 — externe Dienste

| Feature | Warum hier |
|---|---|
| B10 | ruft eine fremde Schnittstelle mit Koordinaten aus der Datenbank; Ausfall und Zeitüberschreitung müssen die Seite tragen |
| B18 | zeitgesteuerter Lauf ohne Betrachter. Fällt er aus, fehlt die Historie unbemerkt — und sie lässt sich nicht rückwirkend erzeugen |

### Rang 3 — Nutzereingaben und Uploads

B11 nimmt Eingaben von angemeldeten Nutzern entgegen, B09 und B20 verarbeiten
Dateien und schreiben in den öffentlichen Bestand, B21 überführt Fremdeingaben in
veröffentlichte Datensätze, B08 erlaubt das Anlegen neuer Werte über eine
Schnittstelle.

### Rang 4 — Darstellung und Querschnitt

B05 und B06 sind der eigentliche Zweck der Anwendung und stehen trotzdem hinten: Sie
lesen nur. B24, B25 und B26 sind Querschnitt und werden in jedem der vorherigen
Features ohnehin mitgeprüft.

## Was bewusst kein Feature ist

| Bereich | Warum nicht |
|---|---|
| Fehler-Tracking (Sentry) | Betrieb, nicht Funktion — gehört zu `sdd-betrieb` |
| Deployment (`cd.yml`, `deploy.sh`) | dito |
| Testdaten (`DataFixtures/`) | Werkzeug der Entwicklung |
| Barrierefreiheitsmerkmale **der Restaurants** | kein eigener Ort im Code: sie sind Felder auf `Restaurant` und erscheinen in B05, B06, B11, B20 und B23. Die Bewertungsregel darüber (`AccessibilityScore`) gehört zu B16. **Nicht zu verwechseln mit Feature `02`** — das ist die Zugänglichkeit der Plattform selbst und sehr wohl ein Feature |
| E-Mail-Versand | Infrastruktur, verteilt über B01, B14 und B15 |
