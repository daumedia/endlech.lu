# Features

Stand: 2026-09-04 · Stack-Profil: `symfony-doctrine` · Artefaktpfad: `docs/`

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

**2026-08-30 · Zwei von drei Cron-Einträgen fehlen auf Produktion.** Aufgefallen beim
Versuch, Feature 04 in Betrieb zu nehmen. Der Server führt **nur** den Messenger-Worker;
das README verlangt drei.

⚠ **`app:metrics:snapshot` (monatlich) fehlt — und das ist nicht nachholbar.** Auf
Produktion gemessen: `/open.json` liefert `trend: leer`, **es wurde nie ein Snapshot
geschrieben**. `/open` zeigt deshalb keine Veränderungen, und die Historie lässt sich
nicht rückwirkend erzeugen — ein aus heutigen Daten zurückgerechneter Verlauf änderte
sich, sobald jemand einen Eintrag bearbeitet, und wäre als Beleg gegenüber einem
Ministerium wertlos. Jeder Monat ohne diesen Cron ist ein dauerhaft verlorener Messpunkt.
Genau davor warnt `docs/`: „eine ausgefallene Historie bliebe unbemerkt".

⚠ **`app:marketing:sync` (alle 5 Minuten) fehlt** — damit überträgt Feature 04 **nie**,
gleich ob Schlüssel und Listen-ID stehen. **T27 war als erledigt abgehakt**; die Aufgabe
ist jetzt richtiggestellt. Zusammen mit dem umgekehrten Fall bei T08 (erledigt, aber als
offen geführt) ist das ein Muster: **Was in `tasks.md` als erledigt steht, ist ein
Vermerk, kein Nachweis** — bei Aufgaben, die außerhalb des Quelltexts stattfinden,
gehört der Zustand gemessen und nicht abgehakt.

**2026-08-30 · Feature `06` aufgenommen und spezifiziert.** Ein Community Feedback Board
stand nirgends — weder im Inventar noch auf der Roadmap. Der Anlass ist eine Lücke, die
sich erst beim Nachzählen der Rückmeldewege zeigt: Es gibt zwei, und **beide sind
einseitig**. Das Meldeformular auf `/barrierefreiheit` (`02`) nimmt Barrieren der Website
entgegen, ohne dass der Melder je erfährt, was daraus wurde; der Wizard `/community/suggest`
(B11) meldet neue Restaurants. Wer sich etwas am *Produkt* wünscht, hat keine Adresse dafür.

**Der Zuschnitt war die eigentliche Arbeit.** Drei Lesarten lagen nebeneinander und sind
drei verschiedene Produkte: Ideen zur Plattform, Bewertungen einzelner Lokale, Korrekturen
an Einträgen. Gewählt ist die erste; die zweite bleibt der eigene, im PRD belegte
Roadmap-Punkt („Ein Kernversprechen ist nicht eingelöst"), die dritte endet in einer
Datenänderung statt in einer Meinung und hängt an B20/B21.

**Eine Entscheidung wurde im Interview zurückgenommen**, und sie ist die folgenreichste:
Zustimmen sollte zunächst ohne Konto gehen. Jede Umsetzung davon verarbeitet entweder eine
IP-Adresse oder ist über ein privates Fenster trivial zu umgehen. Mit Kontozwang ist die
Zahl belastbar **und** das Feature kommt ganz ohne IP-Verarbeitung aus — es ist damit das
erste Feature seit `04`, das den Datenschutzteil nicht ausweitet, sondern verengt.

⚠ **Der Ideentext ist der erste öffentlich veröffentlichte Freitext des Projekts.** Auf
einer Barrierefreiheitsplattform steht darin mit hoher Wahrscheinlichkeit eine
Gesundheitsangabe des Verfassers — dieselbe Erwägung, die in `04` dazu führte, die
Freitextnachricht der Wartelisten von der Brevo-Übertragung auszunehmen. Hier lässt sie
sich nicht vermeiden, der Text *ist* das Produkt. Deshalb wird sie benannt (AK-16) und
eingegrenzt (AK-53, AK-54). **OF-04 macht damit die seit `04` offene Frage nach der
Datenschutzstufe dringlich** — sie ist nicht neu, aber sie kostet hier zum ersten Mal etwas.

**Zwei Zusagen gelten gegen den Betreiber selbst:** Eine Ablehnung ist ohne öffentliche
Begründung technisch nicht auslösbar (AK-27), und abgelehnte Ideen bleiben stehen (AK-28).
Ohne diese beiden gilt Produktprinzip 2 („Lücken werden gezeigt, nicht versteckt") nur so
lange, wie es gelegen kommt.

⚠ **Das Feature ändert ausgelieferten Code.** Kontolöschung und Datenexport aus Feature
`01` müssen das Board mitnehmen (AK-65 bis AK-68) — das ist keine Ergänzung an Neuem,
sondern ein Eingriff in Live-Code und gehört im Aufgabenplan als solcher geführt.

**2026-08-30 · Alle fünf offenen Fragen von `06` entschieden**, noch am Tag der Spec. Jede
hat ein eigenes Kriterium bekommen (AK-72 bis AK-78) — eine entschiedene Frage ohne
Kriterium wird später nicht geprüft, genau der Fehler, der in `03` erst beim Aufgabenplan
auffiel. Damit steht die Spec auf **78 Kriterien**.

- **OF-01 → fünf Werktage, öffentlich zugesagt** (AK-72). Bewusst nicht die zwei Werktage
  des Pressekontakts (`05`/OF-03): Ein Board ist weniger dringend als eine Presseanfrage,
  und eine Zusage, die im Urlaub bricht, ist schlechter als eine großzügige. Die interne
  Meldung bleibt weg.
- **OF-02 → zwölf Monate Höchstdauer, Hervorhebung ab 30 Tagen** (AK-73, AK-74). Die
  Hervorhebung behebt die Ursache, die Frist erfüllt Art. 5 Abs. 1 lit. e. ⚠ **Das löst
  das Muster nur hier:** Bei den Wartelisten (B14/FB-02) und den Werbe-Kontakten
  (`04`/OF-06) fehlt der Aufräumschritt weiter.
- **OF-03 → eigener Abschnitt „Schon umgesetzt" unter der Liste** (AK-75).
- **OF-04 → Stufe B, bestätigt** (AK-78). Die seit `04` offene Frage ist damit beantwortet:
  Die Plattform **erhebt** keine Gesundheitsdaten, sie erhebt Daten über Restaurants. Eine
  Art.-9-Angabe kann nur unaufgefordert im Freitext erscheinen, und dafür sind AK-16, AK-52
  und AK-54 gebaut. ⚠ **Die Bestätigung steht noch nicht in `docs/datenschutz.md`** — dort
  läuft Stufe B weiter als Annahme. `sdd-spec` schreibt keine Datei außerhalb des
  Feature-Ordners; AK-78 fordert die Fortschreibung ein, `sdd-tasks` braucht eine Aufgabe
  dafür.
- **OF-05 → Zurückziehen ja, bis zur Freigabe** (AK-76, AK-77).

⚠ **Eine Entscheidung hat eine neue Frage erzeugt: OF-06.** AK-74 braucht einen
wiederkehrenden Lauf — und auf Produktion fehlen heute **zwei von drei** Cron-Einträgen.
Ein dritter, der ebenfalls nicht eingerichtet wird, wäre kein Zufall mehr. Der Entwurf soll
deshalb prüfen, ob der Aufräumlauf **ohne** eigenen Cron auskommt.

**2026-08-30 · Systementwurf für `06` steht** — 78 von 78 Kriterien abgedeckt, keine leere
Zeile. Zwei neue Tabellen (`board_idea`, `board_vote`), ein Enum, zwei Controller, fünf
Dienste, ein Aufräumbefehl. **Keine neue Bibliothek, kein neuer externer Dienst** — alles
benutzt Muster, die schon stehen: Moderationsschlange von B21, Limiter am Konto von B11,
Fallenfeld von B14, Formularfeld von `02`, Blätterung von B05.

⚠ **Der Entwurf hat gefunden, was die Spec übersah: Es gibt das Board schon — extern.**
Die Fußzeile führt seit Langem „Feedback & Ideen" auf `endlech.userjot.com`, sortiert nach
Stimmen. Das ist dieses Feature, bei einem Dritten betrieben. AK-02 kollidiert damit direkt;
der Entwurf **ersetzt** den Verweis, statt einen zwölften Eintrag danebenzustellen. Was mit
den dort liegenden Ideen und Stimmen geschieht, ist keine Entwurfsfrage → **OF-07, vor
`sdd-tasks` zu klären.** Der Fund spricht im Übrigen für das Feature: Der Bedarf war real
genug, dass bereits ein fremdes Werkzeug im Einsatz ist.

**Drei Entwurfsentscheidungen, die man auch anders treffen könnte:**

- **Sichtbarkeit hängt an `published_at`, nicht an einem sechsten Status.** Die fünf Status
  aus AK-31 beschreiben eine *öffentliche* Idee; „wartet auf Freigabe" ist eine andere
  Achse. Vermischt hätte ein Statuswechsel eine veröffentlichte Idee vom Netz nehmen können.
- **Zustimmungen werden gezählt, nicht mitgeführt.** Ein Zählerfeld liefe genau bei AK-66
  auseinander — die Kaskade beim Kontolöschen läuft in der Datenbank, am Anwendungscode
  vorbei. Der Ausweg wäre ein Abgleichsbefehl gewesen: ein dritter geplanter Lauf in einem
  Projekt, dem zwei von drei fehlen. Ab etwa 2000 Ideen neu bewerten.
- **Der Aufräumlauf aus AK-74 hängt nicht an einem neuen Cron** (OF-06), sondern läuft als
  Befehl *und* faul beim Öffnen der Warteschlange, höchstens einmal je Tag.

⚠ **OF-08 dazugekommen:** AK-72 sagt fünf Werktage zu, AK-73 hebt erst nach 30 Tagen hervor
— fünf Wochen, in denen die Zusage bricht, ohne dass es auffällt. Vorschlag: zehn Werktage.

⚠ **Das Feature fasst ausgelieferten Code an:** `AccountDataExporter` (AK-67),
`AccountDeleter` (EC-09 — wartende Ideen müssen vor dem Konto weg, sonst bleibt eine
herrenlose Einreichung stehen), `AdminStatsService`, das Dashboard und die Fußzeile. Dazu
`docs/datenschutz.md` als eigene Aufgabe (AK-78).

**2026-08-30 · OF-06 bis OF-08 entschieden — `06` hat keine offene Frage mehr.** Die Spec
steht auf **82 Kriterien**, der Entwurf deckt alle 82 ab.

**OF-07 wurde nachgesehen, nicht vermutet.** Ein Abruf von `endlech.userjot.com` zeigte:
**sieben Einträge, alle vom Betreiber selbst, alle mit null Stimmen** (Presskit, iOS app,
Android App, Google Login, Apple Login, Chat widget, AI filter). **Es gibt keinen fremden
Nutzerbestand** — nichts zuzuordnen, nichts zu retten. Damit war aus einer Migrationsfrage
eine einfache Entscheidung geworden:

- **Das Board startet leer.** Die sieben sind Roadmap-Notizen, keine Community-Beiträge;
  siebenmal derselbe Name hätte auf einem Board, das nach den Wünschen der Nutzer fragt,
  das Gegenteil belegt.
- **Die Titel wandern in die PRD-Roadmap** (AK-82). „Chat-Widget" und „KI-Filter" stehen im
  PRD bis heute nirgends — die Überführung schließt eine echte Lücke.
- **Der externe Dienst wird nach dem Deploy abgeschaltet** (AK-81). Dass „Presskit" dort
  noch auf *In Progress* steht, während `05` seit `v2026.08.30.1` live ist, belegt, dass er
  nicht gepflegt wird. Ein nicht mehr verlinktes Board bliebe über Suchmaschinen auffindbar
  und sammelte Beiträge, die niemand liest — genau die Sackgasse, gegen die `06` gebaut wird.

**OF-08 → zweistufig:** Hinweis ab drei Werktagen (AK-73, neu gefasst), deutliche Warnung ab
fünf (AK-79). Die erste Stufe warnt, *bevor* die Zusage aus AK-72 bricht, die zweite genau
dann. Werktag heißt Montag bis Freitag, **ohne Feiertagsrechnung** — eine Feiertagstabelle
für Luxemburg wäre eigene Mechanik ohne Gegenwert.

**OF-06 → kein neuer Cron.** Der Aufräumlauf gibt es als Befehl `app:board:cleanup` *und* er
wird faul beim Öffnen der Warteschlange angestoßen, höchstens einmal je Tag. Eine Zusage,
die von einer Servereinrichtung abhängt, die im Projekt schon dreimal ausblieb, ist keine.

⚠ **Drei Aufgaben liegen außerhalb des Quelltexts** und fallen sonst zwischen die Stühle wie
VB-03 in `05`: AK-78 (`docs/datenschutz.md`), AK-82 (`docs/prd.md`) und **AK-81 — die
Abschaltung bei userjot.** Letztere ist der einzige Schritt, den kein Prüflauf sehen kann;
sie gehört zusätzlich in die Nachverifikation nach dem Deploy.

**2026-08-30 · Aufgabenplan für `06` steht** — 36 Aufgaben in fünf Ebenen, 82 von 82
Kriterien und 12 von 12 Randfällen zugeordnet, in beide Richtungen geprüft.

**Ein Fund beim Schneiden bestimmt die Parallelisierung.** Sechs Template-Aufgaben in
Ebene 4 sehen unabhängig aus — sie berühren sechs verschiedene Dateien. Sie brauchen aber
alle Übersetzungsschlüssel, und die liegen in denselben acht Katalogdateien. Parallel
ausgeführt wäre das exakt der Fehler „zwei Komponenten, beide ergänzen dieselbe
Index-Datei". Die Kataloge bekommen deshalb eine **eigene Aufgabe davor** (T23); erst
dadurch ist die `[P]`-Zusage für T25–T30 haltbar.

**Vier parallele Gruppen**, jede mit ausgeschriebener Dateiliste: Ebene 1 → T01+T02;
Ebene 2 → T05–T09 (fünf getrennte Dienste); Ebene 3 → T16–T18 (Formular, zwei Controller);
Ebene 4 → T25–T30 (sechs Templates); Ebene 5 → T33–T35 (drei Dokumente). Alles andere
läuft seriell, meist weil es sich eine Datei teilt.

⚠ **EC-05 sitzt bewusst in Ebene 2, nicht in Ebene 5.** Dass zwei Admin-Fenster dieselbe
Idee nicht zweimal veröffentlichen, ist eine Zustandsprüfung im `BoardModerator` und kein
Feinschliff. Als Aufgabe am Ende wäre sie das erste, was bei Zeitdruck gestrichen wird —
genau so entstand BF-54 bei den Restaurantvorschlägen.

**AK-69 und AK-70 tragen bewusst keine Aufgabe:** Abnahmekriterien über das Ganze, erfüllt
durch T01–T30 bzw. durch den Verifikationsblock nach jeder Ebene. Eine Sammelaufgabe
„testen" wäre die erste, die bei Zeitdruck fällt.

⚠ **Drei Aufgaben liegen außerhalb des Quelltexts:** T33 (`docs/datenschutz.md`),
T34 (`docs/prd.md`) und **T36 — die Abschaltung bei userjot**, der einzige Schritt, den
kein Prüflauf sehen kann.

**2026-08-30 · Feature `06` gebaut.** 35 von 36 Aufgaben, **791 Tests grün** (3340
Zusicherungen; Ausgangslage 742). 49 neue Tests, keine neue Abhängigkeit, keine Änderung
unter `assets/`.

⚠ **Eine Aufgabe ist NICHT erledigt: T36 / AK-81** — die Abschaltung von
`endlech.userjot.com` geschieht beim Anbieter, außerhalb des Repositorys. Der
Fußzeilenverweis zeigt bereits aufs eigene Board; solange userjot aber Einreichungen
annimmt, laufen Beiträge dorthin, die niemand liest. **Vor dem Deploy zu erledigen.**

**Dreimal dieselbe Planabweichung — und sie gehört in den nächsten Aufgabenplan:**
Konfiguration und Katalogeinträge gehören in dieselbe Ebene wie das Artefakt, das sie
benutzt. T02 (Limiter) musste von Ebene 1 nach Ebene 3, weil `LimiterCoverageTest` einen
Limiter ohne Aufrufer als Fehler wertet — die Suite wäre sonst zwei Ebenen lang rot
gewesen. Die Mail-Schlüssel mussten zu T07, die Formularschlüssel vor Ebene 3.

**Zwei Fehler fand erst der Prüflauf, nicht die Überlegung:**

- **Ein roher Übersetzungsschlüssel stand auf der Seite.** `BoardIdeaStatus::transKey()`
  lieferte `board.status.declined`, die Kataloge trugen `board.status_declined`.
  ⚠ **`CatalogueCompletenessTest` sah das nicht** — der Schlüssel entsteht in PHP, nicht
  als Literal im Template. Das ist ein blinder Fleck des Prüflaufs, der über Feature `06`
  hinaus gilt. Dafür gibt es jetzt `BoardLocaleTest`, der die gerenderte Seite in allen
  vier Sprachen auf rohe Schlüssel prüft.
- **Die Ablehnungsbegründung fehlte in der Listenansicht.** AK-28 verlangt sie „im
  Board"; ohne den Fund hätte dort ein „Abgelehnt" ohne jedes Warum gestanden.

⚠ **Eine Abweichung vom Entwurf:** Der Slug ist **nicht** unique. Der vorgesehene
Unique-Index hätte bei zwei gleichnamigen Ideen einen Serverfehler erzeugt, und gleiche
Titel sind auf einem Wunschboard der Normalfall — die Adresse `/{id}-{slug}` ist durch
die Kennung eindeutig.

⚠ **Systemweit wirksam:** `AdminStatsService` hat ein fünftes Konstruktorargument (jeder
Aufrufer muss mitziehen), die Fußzeile zeigt auf **jeder** Seite aufs neue Board, und
**`docs/datenschutz.md` führt die Datenschutzstufe jetzt als bestätigt (B) statt als
Annahme** — das gilt projektweit, nicht nur für dieses Feature.

⚠ **Bekannte rote Stelle, nicht von diesem Feature:** `lint:container` schlägt mit einem
Webauthn-Alias-Fehler fehl. Mit `git stash` gegengeprüft — der Fehler besteht auch ohne
`06` (Vorbestand aus B03). `make fix-check` gibt es im Makefile nicht, `php-cs-fixer` ist
nicht installiert; die Stilprüfung des Stack-Profils konnte nicht laufen.

**2026-08-30 · QA von `06`: 77 von 82 Kriterien belegt, kein kritischer Fund.** Der
Angriffsdurchlauf lief vollständig — alle acht Prüfungen ausgeführt, nicht gelesen.
806 Tests grün (3418 Zusicherungen), 15 neue.

**Was der Angriff belegt hat:** Kein IDOR (fremdes Konto, sogar das Admin-Konto, bekommt
auf eine wartende Idee **404**, nicht 403). Die Rollenschranke hält auch gegen einen
direkt gepokten POST — `published_at` blieb `NULL`. Der Einreichdeckel greift **exakt am
Grenzwert**: fünf durch, der sechste 429, `COUNT` = 5; und er hängt am Konto, nicht an
der IP (Konto B kam von derselben IP durch). Fünf ungültige Submits verbrauchen ihn
nicht. Elf Eingabeformen — 10.000 Zeichen, SQL, Skript, Pfadwechsel, Nullbyte, 120 × „日"
— erzeugen **keinen einzigen HTTP 500**.

**Der wichtigste Nachweis ist der Mailkörper, nicht der Quelltext:** `To` enthält
ausschließlich den Verfasser, `Cc`/`Bcc` sind leer, der Titel geht mit — und die
simulierte Gesundheitsangabe, die Telefonnummer und die Fremdadresse stehen **nicht**
darin. Damit ist die zentrale Zusage aus dem Datenschutzteil am tatsächlichen Payload
belegt.

⚠ **AK-49 konnte belegt werden, obwohl der Bau es offenließ:** Alle fünf Wege — Board,
Sortierung, Filter, Einreichen, Zustimmen — laufen per curl **ohne jedes JavaScript**;
die Zustimmung landete real in der Datenbank.

**Drei Befunde, alle unterhalb der Blockierschwelle:**

- **BF-101** (*mittel*) · Die Deckel-Meldung nennt **keine Wartezeit**, obwohl AK-59 sie
  verlangt. `ActionLimiter::retryAfter()` wird im Projekt an vier anderen Stellen genau
  dafür benutzt — hier nicht.
- **BF-102** (*niedrig*) · Beim Zustimmen erscheint „Zu viele **Einreichungen**" —
  ein Schlüssel für zwei Wege.
- **BF-103** (*mittel*) · ⚠ **`endlech.userjot.com` nimmt weiterhin Einreichungen
  entgegen.** Keine Softwarefrage, aber die Betriebs-Sperre: Das externe Board ist zwar
  unverlinkt, über Suchmaschinen und Lesezeichen aber auffindbar — Beiträge landen dort,
  wo niemand sie liest.

**Zwei neue Muster in `befunde.md`**, beide über dieses Feature hinaus gültig:

1. **Ein Prüflauf, der Schlüssel nur als Literal sucht, sieht die zusammengesetzten
   nicht.** `CatalogueCompletenessTest` scannt Templates und `src/Form/`; ein in PHP
   gebauter Schlüssel (`'board.status_' . $value`) kommt in keinem Topf vor. Die Suite
   blieb grün, während der rohe Schlüsselname auf der Seite stand.
2. **Konfiguration und Katalogeinträge gehören in dieselbe Ebene wie das Artefakt, das
   sie benutzt** — dreimal in einem Bau aufgetreten.

**Nicht prüfbar (3):** AK-46 (320 px), AK-47 (Tastatur) und AK-69 (Volldurchlauf von
Hand) brauchen einen Browser; im Projekt liefe das über Brave + CDP. Ebenso drei
Randfälle zur Gleichzeitigkeit und die Druckansicht.

**2026-08-30 · BF-101 und BF-102 behoben.** 810 Tests grün (3438 Zusicherungen), vier
neue. Beide Reproduktionen aus dem Testbericht greifen nicht mehr — live gegen den
laufenden Server belegt: „Zu viele Einreichungen. Bitte versuche es in **45 Minuten**
erneut." und „Zu viele **Zustimmungen**. Bitte versuche es in **49 Minuten** erneut."

⚠ **Eine Einschränkung beim Regressionsschutz, die benannt gehört:** Der Deckel lässt
sich im Test-Kernel **nicht auslösen** — `KernelBrowser` startet den Kernel bei jedem
Request neu, und der Limiter-Zustand überlebt das dort nicht (außerhalb des Tests wirkt
das Erschöpfen nachweislich: 10000 → 0). Der neue Prüflauf deckt deshalb ab, was
tatsächlich falsch war: dass beide Schlüssel existieren, die Wartezeit tragen und
**verschieden** sind — in vier Sprachen. Das Auslösen selbst bleibt live belegt.

⚠ **BF-103 bleibt offen und wandert bewusst HINTER den Deploy** (Betreiberentscheidung
2026-08-30). Der Testbericht riet zunächst zum Gegenteil — das war falsch herum gedacht:
Solange `/community/ideen` nicht live ist, ist `endlech.userjot.com` der **einzige**
Rückmeldeweg, und ihn vorher zu schließen erzeugte ein Fenster ganz ohne Weg. Die
Reihenfolge ist **Deploy → userjot schließen**; der Schritt gehört in die
Nachverifikation von `/sdd-deploy 06`.

**2026-08-30 · Zweiter QA-Durchlauf von `06`: 79 von 82 belegt — aber zwei neue Befunde
mit Grad *hoch*.** BF-101 und BF-102 sind nachgeprüft und behoben („in **59 Minuten**
erneut" bzw. „Zu viele **Zustimmungen** … in **60 Minuten**").

**Der Ertrag dieses Durchlaufs liegt woanders:** AK-46, AK-47 und AK-69 standen im ersten
Bericht als *nicht prüfbar* — jetzt sind sie **im Browser gemessen** (Brave über CDP).
Zwei davon bestehen, eines fällt durch:

- **AK-46 bestanden.** Bei 320 px: Board, Einzelansicht, Formular und Dankeseite je
  Überhang = 0 px. Der Weg dorthin war lehrreich: Drei Messungen zeigten 9703 px Überhang
  und ein Logo von 10000 px — bis sich herausstellte, dass `php -S` mit Router-Skript CSS
  mit `Content-type: text/html` ausliefert und der Browser das Stylesheet nicht anwendet.
  **Ein Messartefakt, das wie ein schwerer Layoutfehler aussah.**
- **AK-47 durchgefallen → BF-104** (*hoch*): Die Titel-Verweise auf dem Board sind **18 px
  hoch** und der einzige Weg in die Einzelansicht. AK-47 verlangt 44 × 44, WCAG 2.2 AA
  mindestens 24 × 24. Der Fokus ist dagegen überall sichtbar (8/8, 2/2, 4/4).
- **AK-69 bestanden**, im Browser durchgespielt bis zur Freigabe.

⚠ **BF-105 (*hoch*) ist der Befund, der am ehesten teuer geworden wäre:** Der committete
`public/build` ist veralteter Stand — `line-clamp-3` fehlt, obwohl nur Feature 06 die
Klasse benutzt. `verify-assets` hätte den Deploy blockiert. **Ursache ist ein Denkfehler
im Aufgabenplan**, der ungeprüft in den Abschlussbericht wanderte: „`npm run build`
entfällt, das Feature kommt ohne Änderung unter `assets/` aus." Tailwind v4 scannt hier
aber `templates/` (`@source "../../templates"`). Die Projektregel in `CLAUDE.md` gehört
auf „Änderung unter `assets/` **oder `templates/`**" erweitert — sonst wiederholt sich
das beim nächsten Feature. Als Muster in `befunde.md` festgehalten.

**2026-08-30 · BF-104 und BF-105 behoben.** 815 Tests grün (3450 Zusicherungen), fünf
neue. Beide im Browser bzw. am gebauten Asset nachgeprüft.

- **BF-104** · Der Titel-Verweis trägt jetzt `min-h-[44px] flex items-center`. Nachgemessen
  mit Brave/CDP: **192 × 44** bei 390 px, **140 × 48** bei 320 px, **null** zu kleine Ziele
  in `main`, Überhang weiterhin 0 px, Klick und Zustimmen funktionieren.
  ⚠ **Der elegantere Stretched Link wurde versucht und verworfen.** Er war korrekt gesetzt
  (`content: ""`, `inset: 0px`, `article` auf `relative`), blieb im Stapel aber unter den
  Geschwisterelementen: `elementFromPoint` traf `p` und `svg` statt des Verweises. Eine
  Mindesthöhe am Verweis selbst hängt nicht von der Stapelreihenfolge ab — und ist im
  Markup prüfbar, statt nur im Browser messbar.
- **BF-105** · Gebaut. **Determinismus belegt:** Ein dritter Lauf erzeugte identische
  Prüfsummen für `app.*.css` und `manifest.json` — `verify-assets` bleibt grün. Zu
  committen: `app.b236d552.css` (neu), `app.f5e6e5d8.css` (entfällt), `entrypoints.json`,
  `manifest.json`.

**Der wichtigere Ertrag ist ein Prüflauf, den es vorher nicht gab:** `BuiltAssetsTest`
hält vier charakteristische Regeln gegen das gebaute CSS. Damit meldet sich der Fall
„Template geändert, Bau vergessen" **im normalen Lauf** statt erst an `verify-assets` im
Deploy. Gegengeprüft mit einer umbenannten Regel — der Prüflauf wird rot.

⚠ **Nicht geändert, obwohl es naheliegt:** Der Satz in `CLAUDE.md` („Änderung unter
`assets/` → `npm run build`") gehört auf `templates/` erweitert. Das ist eine projektweite
Regeländerung und kein Teil eines Fehlerauftrags — als Muster in `befunde.md` festgehalten,
zu entscheiden außerhalb dieses Features.

⚠ **BF-103** (userjot) bleibt wie entschieden hinter dem Deploy.

**2026-08-30 · Dritter QA-Durchlauf von `06`.** BF-104 und BF-105 sind behoben und
nachgeprüft — aber **die Reparatur von BF-104 hält den Grenzfällen nicht stand**, die
vorher nicht in den Prüfdaten waren. Der Durchlauf lief bewusst mit einem kurzen Titel,
einem sehr langen und einem aus einem einzigen Wort.

- **BF-106** (*mittel*) · Ein Titel aus **80 × „W" ohne Leerzeichen** — innerhalb der
  erlaubten 120 Zeichen — sprengt das Board: **1089 px Überhang bei 320 px**. Isoliert
  belegt: Karte im DOM entfernt → 0 px. ⚠ **Das ist BF-82 zum zweiten Mal.** Dort kam der
  Text aus einer gepflegten Anbieterliste und blieb *niedrig*; hier kommt er aus einem
  Formular, und jeder angemeldete Nutzer kann es auslösen. Beim zweiten Auftreten ist die
  Einzelreparatur die falsche Antwort — es fehlt eine Regel im Design-System, dass jedes
  Element mit Nutzertext eine Umbruchregel trägt. **Die nächsten drei Roadmap-Punkte
  (Bewertungen, Kommentare, Korrekturhinweise) zeigen alle Freitext.**
- **BF-107** (*mittel*) · Die Reparatur setzt mit `min-h-[44px]` nur die **Höhe**. Ein
  kurzer Titel ergibt **36 × 44** — die Breite bleibt unter 44. ⚠ **`BoardTargetSizeTest`
  fängt das nicht:** Er prüft, dass die Klasse im Markup steht, und das tut sie. Ein
  Prüflauf, der Klassen liest, sieht keine gerenderte Breite — als Muster festgehalten.

**Was hält:** BF-105 sauber behoben (`BuiltAssetsTest` grün, Bau deterministisch), die
Zugriffsregeln unverändert dicht (wartende Idee für Gast **404**, Verwaltung **302**),
815 Tests grün. **EC-10 erstmals belegt:** In der Druckansicht sind Kopf-, Fußzeile und
Bottom-Navigation auf `display: none`, der Inhalt bleibt — damit 10 von 12 Randfällen.

**2026-08-30 · BF-106 und BF-107 behoben.** 816 Tests grün (3457 Zusicherungen). Beide
mit denselben Grenzfällen nachgemessen, an denen sie aufgefallen waren.

- **BF-106** · `wrap-anywhere` an **acht Stellen über vier Templates** — Titel,
  Beschreibung, Team-Antwort und Anzeigename in der Karte, die Antwort auf der
  Einzelansicht sowie Titel, Beschreibung und Anzeigename in beiden Verwaltungsansichten.
  Nachgemessen: **0 px Überhang bei 320 und 390 px**, auch wenn Titel, Beschreibung *und*
  Team-Antwort aus je 80 × „W" bestehen.
- **BF-107** · **Zwei Anläufe.** `w-full` am Verweis reichte nicht — es bezieht sich auf
  die Überschrift, und die schrumpft im Flex-Container auf ihre Textbreite („Kurz" blieb
  36 × 44). `flex-1` an der Überschrift deckte prompt den nächsten Fall auf: Bei 320 px
  teilen sich Titel und Statusabzeichen eine Zeile, der Titel schrumpfte auf **37 px**.
  Erst `basis-full min-w-0 sm:basis-auto sm:flex-1` löst beides. Endstand: **null zu kleine
  Ziele bei 320 und 390 px.**

⚠ **Nur der eigene Fall ist behoben.** `BF-82` in Feature 03 bleibt offen, und die
Design-System-Regel für Nutzertext ist **nicht** gezogen — beides gehört nicht in einen
Fehlerauftrag für Feature 06, sondern ist eine eigene Entscheidung. Das Muster steht in
`befunde.md`.

**Der Prüflauf ist mitgewachsen:** `BoardTargetSizeTest` prüfte bisher nur `min-h-[44px]`
— genau daran ist BF-107 vorbeigekommen. Er prüft jetzt alle vier zusammenwirkenden
Klassen plus die Umbruchregel, und seine Grenze („ein Markup-Test sieht keine gerenderte
Breite") steht als Warnung im Klassenkommentar. Gegengeprüft: Ohne `w-full` wird er rot.

**2026-08-30 · Vierter QA-Durchlauf: kein Befund an `06` — abgenommen.** 81 von 82
Kriterien belegt, 816 Tests grün. Alle vier Reparaturen halten, auch gegen die
Grenzfälle, an denen sie zuvor gescheitert waren.

Dieser Durchlauf prüfte gezielt, was die drei vorherigen ausgelassen hatten: **Breiten
zwischen den bisherigen Messpunkten** (375, 640, 768, 1024, 1280), die **Blätterung mit
echtem Bestand** (28 Ideen → 20 + 8, „Seite 1 von 2") und die **Verwaltung auf Mobil**.
Ergebnis am Board: bei sieben Breiten **0 px Überhang und 0 zu kleine Ziele**, mit
80-Zeichen-Wörtern in Titel, Beschreibung und Team-Antwort.

**Zwei Auffälligkeiten wurden geprüft und als Vorbestand belegt statt als Befund gebucht:**

- **Bei 768 px scrollen alle Seiten um 96 px** — Startseite, `/restaurants`, `/about`
  **und** das Board, jeweils mit Ursache im `header`, nie in `main`. Das ist **BF-80**,
  seit Feature `02` offen. Ohne die Gegenprobe auf drei Bestandsseiten wäre daraus ein
  falscher Befund gegen `06` geworden.
- **34 zu kleine Ziele in der Verwaltung** stammen ausnahmslos aus der **Admin-Shell**
  (Sprachumschalter 54 × 28, Navigationseinträge 309 × 36) — null aus der Ideenkarte.
  Gegenprobe auf `/de/admin/vorschlaege`: dieselbe Lage. ⚠ **Kein Befund gebucht**, weil
  AK-47 „Board und Formular" nennt und die QA keine Anforderungen erfindet — als Hinweis
  für **B19** im Bericht vermerkt.

⚠ **Zwei Punkte für die Nachverifikation nach dem Deploy:** **BF-103** (userjot
schließen, bewusst danach) und **`public/build` mitcommitten** — vier Dateien, ohne die
`verify-assets` blockiert.

**2026-08-30 · `v2026.08.30.2` liegt auf `dev` — der Deploy ist vorbereitet, nicht
ausgeführt.** Preflight vollständig durchlaufen, Feature-Commit und Release-Commit
gesetzt, Tag `v2026.08.30.2` vergeben, alles nach `origin` gepusht. 816 Tests grün auf
`dev`. **Der Merge nach `production` bleibt beim Betreiber** (so entschieden) — er ist
der Moment, in dem sich der Live-Server ändert.

**Preflight-Ergebnis:** Feature `06` auf `approved` mit „production-ready: ja", keine
Geheimnisse im Repository, `cache:clear --env=prod` fehlerfrei, `public/build` aktuell und
deterministisch (BF-105 bleibt behoben). **Alle vier Release-Stellen gezogen** — CHANGELOG
samt Badge in Zeile 5, README-Badge, `app.version` und der Tag; im Container bestätigt:
`app_version: 2026.08.30.2`.

**Die Migration ist additiv:** `Version20260830120000` legt ausschließlich zwei neue
Tabellen an — kein `DROP`, kein `ALTER` an bestehenden Tabellen, `down()` vorhanden. Kein
Datenverlustrisiko. **Die Sicherung der Produktivdatenbank macht der Betreiber vor dem
Merge** (so entschieden), obwohl das Risiko gering ist.

⚠ **Zwei Punkte für die Nachverifikation:**

1. **BF-103 — `endlech.userjot.com` schließen**, unmittelbar **nach** dem Deploy. Vorher
   wäre es der einzige Rückmeldeweg.
2. **Der Deploy schreibt zwei neue Tabellen und lässt den Worker weiterlaufen.** Die eine
   Mail des Boards geht über die Queue; `messenger:stats` nach dem Deploy zeigt, ob sie
   durchläuft.

Nächster Schritt: **Merge `dev` → `production`** durch den Betreiber, danach die
Live-Nachprüfung.

**Zwei Namensräume:** Einträge mit Präfix `B` sind **Bestand** — gebaut, bevor die
SDD-Kette da war, und rückwirkend erfasst. Einträge **ohne** Präfix (`01`, `02`, …)
entstehen durch die Kette und hatten eine Anforderung, bevor Code existierte. An der ID
ist damit ohne Nachschlagen erkennbar, ob die `spec.md` eine Vorgabe oder eine
Rekonstruktion ist. Die ID ändert sich nie, auch wenn die
Bearbeitungsreihenfolge eine andere ist.

Ein Bestandsfeature läuft **nicht** durch `sdd-tasks` und nicht durch den regulären
Eingang von `sdd-build`. Der Weg ist: `bestand` → `/sdd-erfassen BNN` →
`rekonstruiert` → `/sdd-qa BNN`.

**2026-08-30 · Feature `07` aufgenommen und spezifiziert.** Ein Besucher kann heute nicht
erkennen, ob an der Plattform noch gearbeitet wird: Der Changelog liegt als `CHANGELOG.md`
im Repository (21 Releases, einsprachig deutsch, mit Konstruktorargumenten im Text), die
Roadmap als Tabelle in `docs/prd.md`. Bis zum 30. August gab es genau **eine** öffentlich
sichtbare Statusanzeige — das externe Board `endlech.userjot.com`, das Feature `06` gerade
abgeschaltet hat. Das eigene Board zeigt seither, was die Community will; was der Betreiber
vorhat, zeigt es nicht.

Die Spec setzt `/roadmap` und `/changelog` an: drei Status-Spalten (In Arbeit · Geplant ·
Angedacht), **keine Termine**, ein eigener Block „Bewusst nicht gebaut“ mit den acht
zurückgestellten Punkten aus `CLAUDE.md`, und ein Changelog als redaktionelle Kurzfassung
in vier Sprachen — `CHANGELOG.md` bleibt die technische Fassung und wird verlinkt. Bauart
wie `03` und `05`: keine Entität, keine Migration, eigene Übersetzungsdomain.

⚠ **Zwei Entscheidungen aus dem Interview tragen das Feature.** Community-Ideen mit Status
`Geplant` werden **live abgefragt statt kopiert** — eine zurückgezogene Idee kann sonst auf
der Roadmap stehen bleiben, bis es jemand merkt. Und der Changelog zeigt **nur, was ein
Besucher merkt**; die übrigen Releases tragen einen ausdrücklichen Vermerk „still“. Genau
dieser Vermerk macht die Vollständigkeit erst prüfbar (AK-26): Ohne ihn könnte ein Prüflauf
nicht zwischen „bewusst still“ und „vergessen“ unterscheiden, und die Absicherung des neuen
fünften Punkts der Release-Checkliste wäre wertlos.

⚠ **Kein Rate Limit — bewusst, mit Begründung.** Beide Seiten sind rein lesend; ein Deckel
wäre die erste öffentliche Leseseite der Plattform, die Besucher aussperrt. Die Konvention
aus `CLAUDE.md` (jeder Weg, der den gesamten Bestand lädt, braucht einen Deckel) wird
stattdessen an der Ursache erfüllt: Zwischenspeicher plus harte Obergrenze von zehn Ideen,
belegt durch AK-45 bis AK-47.

⚠ **Drei Vorbedingungen blockieren die Auslieferung, keine davon ist Code:** Feature `06`
ist abgenommen, aber **noch nicht nach `production` gemerged** (VB-01) — die Roadmap liest
seinen Bestand und verlinkt auf ihn. Der Betreiber hat noch nicht festgelegt, welches der
sieben PRD-Vorhaben in welcher Spalte steht (VB-02) — ohne das erfindet der Bau eine
Priorisierung und veröffentlicht sie als Zusage. Und es steht nicht fest, welche der 21
Altreleases rückwirkend einen Eintrag bekommen (VB-03).

⚠ **OF-02 ist die unangenehme Frage und gehört Michael:** Kommt **Bewertungen und
Kommentare** auf die Roadmap? Das PRD führt es als Risiko 1 — die Startseite wirbt seit
jeher mit „echten Bewertungen“, die es nicht gibt. Auf der Roadmap wäre dieses Versprechen
zum ersten Mal öffentlich als *noch nicht eingelöst* markiert; weggelassen, bleibt die Lücke
unerwähnt. Sechs offene Fragen insgesamt, 50 Kriterien. Nächster Schritt:
`/sdd-architektur 07`.

**Entwurf am selben Tag.** Bauart wie `03` und `05`: keine Entität, keine Migration,
Struktur als unveränderliche Wertobjekte unter `App\Roadmap\`, Texte in **zwei** eigenen
Domains (`roadmap`, `changelog`) mit **einem** gemeinsamen Katalogtest. Zwei Seiten, ein
Controller, zwei sprachfreie Kurzlinks. 50 von 50 Kriterien abgedeckt, zwei davon
ausdrücklich **nicht durch Code** (AK-20: kein Codebegriff im Text; AK-40: keine
Personennamen) — beide sind redaktionelle Zusagen an einen Text, den kein Prüflauf
beurteilen kann, und stehen als solche vermerkt, damit die QA sie nicht für abgesichert
hält.

**Zwei Entscheidungen tragen den Entwurf.** Der Zwischenspeicher wird von einem
**Doctrine-Entity-Listener** verworfen statt von Aufrufen in `BoardModerator` und
`BoardVoteService` — er fasst Feature `06` nicht an und deckt jeden künftigen Schreibweg
mit ab. Und der Begründungstext ist **Bestandteil des Wertobjekts**: Ein Roadmap-Eintrag
ohne `…reason`-Schlüssel existiert strukturell nicht, weil der Katalogtest ihn in vier
Sprachen verlangt. AK-05 und AK-29 sind damit erzwungen statt erbeten.

⚠ **Vier Funde beim Entwerfen, die sonst erst beim Bauen aufgefallen wären.**
**(1)** Der **Ideentitel im Board trägt keine Sprachauszeichnung** — Zeile 110 von
`_board_idea_card.html.twig` zeichnet die Beschreibung aus, Zeile 86 den Titel nicht. Die
Roadmap zeigt genau den Titel; AK-33 stellt die Auszeichnung dort **erstmals her**, statt
ein Muster zu übernehmen. Die Spec behauptete das Gegenteil und ist korrigiert; im Board
selbst bleibt die Lücke offen und gehört zu `06`. **(2)** `findPublishedPaginated()`
schließt `Umgesetzt` aus und blättert zu zwanzigst — wer sie wiederverwendete, würfe die
Hälfte weg; es braucht eine eigene Abfrage. **(3)** Die **Stimmen-Kaskade beim Kontolöschen
läuft an Doctrine vorbei** (so im Changelog von `06` beschrieben): Ein Listener auf
`BoardVote` allein sähe sie nicht, deshalb hängt er zusätzlich am gelöschten Konto, und die
Lebensdauer von einer Stunde bleibt als zweites Netz — daraus **OF-07**. **(4)** Die
**Fußzeilenspalte 2 ist voll** (elf Einträge); die zwei neuen Verweise gehen in Spalte 4,
weil eine fünfte Spalte das `lg:grid-cols-4`-Raster bräche und die App-Hülle mit BF-80
bereits eine offene Umbruchlücke hat.

⚠ **Kein Rate Limit — als Entscheidung vermerkt, nicht als Lücke.** Die Konvention aus
`CLAUDE.md` wird an der Ursache erfüllt: Die Begrenzung auf zehn Ideen steht **in der
Abfrage**, damit lädt kein Aufruf je den Bestand. Ein Limiter wäre die erste öffentliche
Leseseite der Plattform, die Besucher aussperrt.

⚠ **Kein Verzeichnis `public/roadmap` und kein `public/changelog`** — sonst wiederholt sich
BF-100 auf zwei neuen Adressen. `RouteDirectoryCollisionTest` deckt beide ohne Zutun ab,
weil er die Ursache prüft und nicht das Verhalten. Nächster Schritt: `/sdd-tasks 07`.

**Aufgabenplan am selben Tag.** 26 Aufgaben in fünf Ebenen, keine Migration. 49 der 50
Kriterien tragen eine Aufgabe, alle elf Randfälle ebenfalls. Drei Entscheidungen prägen
die Reihenfolge: Die **beiden Übersetzungsdomains stehen in Ebene 1** — dieselbe Lehre wie
bei `04` und `05`, ein Katalogeintrag, der erst im Feinschliff entsteht, färbt den Prüflauf
drei Ebenen früher rot. Die **beiden Prüfläufe stehen in Ebene 2 neben den Registries**,
nicht am Ende: Sie lesen nur und schlagen bei leeren Listen zunächst fehl — genau das ist
der Beleg, dass sie prüfen. Und **`npm run build` ist eine eigene Aufgabe** (T26), weil
Tailwind die Templates scannt; bei Feature `04` erzwang eine einzige neue Klasse einen
Neubau, den der Plan nicht vorgesehen hatte.

⚠ **AK-40 trägt bewusst keine Aufgabe.** „Der Changelog nennt keine natürliche Person"
lässt sich nicht bauen, nur abnehmen — ein Name unterscheidet sich für eine Maschine nicht
von einem Produktnamen. Der Nachweis ist eine Handprüfung in der QA. Eine Alibi-Aufgabe
dafür wäre schlimmer als keine: Sie sähe später aus wie eine Absicherung, die es nicht
gibt. Dasselbe gilt zur Hälfte für **AK-20** — T18 baut die Seite, ob der Text die Zusage
hält, liest ebenfalls die QA.

⚠ **Zwei Aufgaben stehen still, bis der Betreiber entschieden hat:** **T07** (welches
Vorhaben in welcher Spalte — VB-02, samt OF-02 zu den Bewertungen) und **T08** (welche der
21 Altreleases öffentlich werden — VB-03). Beides ist keine Programmierarbeit, und ohne die
Antworten entstünde eine erfundene Priorisierung beziehungsweise ein Changelog mit einem
einzigen Eintrag. **VB-01 blockiert den Bau dagegen nicht**, nur die Auslieferung: `06`
liegt abgenommen auf `dev`, darauf lässt sich bauen — ausgeliefert werden darf `07` erst
danach. Nächster Schritt: `/sdd-build 07`.

**2026-08-30 · Gebaut am selben Tag, alle 26 Aufgaben.** 907 Tests grün (vorher 741 mit
Feature 06), davon 63 neu. Beide Seiten stehen in vier Sprachen; im Browser gemessen:
**8 von 8 Aufrufen ohne Querscrollen bei 320 px**, „In Arbeit" im ersten Bildschirm,
0 Konsolenfehler.

**Vier Funde, die erst der Bau zutage brachte.** **(1)** Eine frisch eingeplante Idee hat
**null** Zustimmungen — mein Pluralmuster kannte nur `{1}` und `]1,Inf[`, und die Seite
antwortete mit **HTTP 500**. Genau der Regelfall. Behoben, `{0}`-Zweig in allen vier
Katalogen. **(2)** Der **Zwischenspeicher ist über HTTP nicht testbar**: Der Testclient
bootet den Kernel bei jedem Request neu, und selbst mit `disableReboot()` leert Symfonys
`services_resetter` den Array-Adapter zwischen zwei Requests. Drei Läufe, die den Listener
zu belegen schienen, belegten nichts — sie sind umgeschrieben, der echte Nachweis steht
jetzt als Integrationstest. **(3)** Der `hreflang`-Block der App-Hülle **spiegelt die
Abfragezeichenfolge** auf jeder Seite (OF-09). **(4)** Die **Fußzeile überschreibt ihre
Spalten mit `<h4>`**, wodurch die Überschriftenkette jeder Seite von h2 auf h4 springt
(OF-10) — der einzige axe-Verstoß, der nach Abzug der Debug-Toolbar übrig bleibt.

⚠ **Vier Kriterien bleiben offen, keines davon aus eigenem Verschulden.** **AK-34**
(axe null Verstöße) und **AK-38** (lückenlose Ebenen seitenweit) scheitern beide an
OF-10; im Inhaltsbereich ist die Kette lückenlos. **AK-44** scheitert an OF-09 — escaped,
also kein Sicherheitsproblem, aber eine Eingabe erscheint in der Antwort. **AK-20** und
**AK-40** sind redaktionelle Zusagen an einen Text und werden in der QA gelesen.
Nachgemessen an `/presse`, `/open`, `/about`, `/vergleich` und `/community/ideen`: OF-09
und OF-10 treffen **jede** Seite des Projekts.

⚠ **Eine bewusste Abweichung vom Entwurf:** `ReleaseVisibility` ist **dreiwertig**
(`SHOWN`/`SUMMARISED`/`SILENT`) statt des vorgesehenen `public: bool`. Der Bool trägt die
Sammelzeile aus OF-01 nicht — er müsste zwei Bedeutungen tragen, und genau daran hing
BF-89. Erst die Dreiteilung macht `ChangelogCompletenessTest` aussagekräftig: „bewusst
still" ist von „vergessen" unterscheidbar.

**Die Release-Checkliste hat jetzt fünf Punkte** — der neue ist der einzige, den ein
Prüflauf erzwingt. Beide neuen Läufe sind gegengeprüft: Ein entferntes Release färbt rot
und nennt die Version, ein aus **allen vier** Katalogen entfernter Schlüssel ebenfalls —
während `CatalogueCompletenessTest` dabei grün bleibt. Das ist der Beleg, warum es den
zweiten Lauf braucht (BF-98).

Nächster Schritt: `/sdd-qa 07`.

**2026-08-30 · QA von `07`: 48 von 52 bestanden, nicht abgenommen.** Alle elf Randfälle
geprüft (neun belegt, zwei redaktionell nicht herstellbar), Angriffsdurchlauf über alle
acht Punkte. Der Zustand wurde **hergestellt statt abgewartet** — zwölf geplante Ideen mit
gestaffelten Stimmen, eine nie freigegebene, je eine in den vier anderen Status; danach
restlos entfernt.

**Was der Prüflauf belegt, was kein Test konnte:** Eine über den ORM abgelehnte Idee
verschwindet an der **laufenden Anwendung** ohne Deploy und ohne Cache-Leeren (1 → 0
Treffer), eine depublizierte ebenso. Umgekehrt: 13 per SQL **am ORM vorbei** eingefügte
Stimmen bleiben unsichtbar, bis `cache:pool:clear cache.roadmap` läuft — damit sind
AK-46 und AK-47 erstmals unter Produktionsbedingungen belegt, nicht nur im Test.

⚠ **Blockierend ist BF-108, und er sitzt genau dort, wo niemand gemessen hat.** Bei
**768 px** ist der Titel jeder Community-Karte eine **senkrechte Buchstabenkolonne** —
12 px breit, 648 px hoch; beim längsten Titel 2352 px. Bei 320 px (64 px) und 1280 px
(176 px) ist nichts zu sehen; der Fehler lebt in der Mitte. **Es ist BF-107 zum zweiten
Mal**: dieselbe Bauart (Titel neben `shrink-0`-Abzeichen im Flex-Container), die Feature
`06` in zwei Anläufen gelöst hat — Feature `07` baute die Karte neu und begann von vorn.
Daraus zwei neue projektweite Muster: *die Klassenkette gehört ins Design-System*, und
*768 px gehört als dritte Messbreite in jedes Darstellungskriterium* (auch BF-80 wurde
zweimal genau dort übersehen).

**Drei Befunde ohne Codeanteil dieses Features:** **BF-109** (Fußzeile überschreibt mit
`<h4>` → `heading-order` auf jeder Seite, blockiert AK-34 und AK-38) und **BF-110**
(`hreflang` spiegelt die Abfragezeichenfolge, blockiert AK-44) sind **projektweite
Altlasten der App-Hülle** — nachgemessen auf `/presse`, `/open`, `/about`, `/vergleich`
und `/community/ideen`. **BF-111** gehört zu Feature `06`: Eine wartende Idee **ohne
Verfasser** ist öffentlich lesbar (`null !== null` ist `false`), heute nicht erreichbar,
aber die Prüfung ist richtig aus dem falschen Grund — und der vorhandene Test bemerkt es
nicht, weil er nur den Fall *mit* Verfasser kennt.

912 Tests grün, fünf davon neu aus der QA. Nächster Schritt: `/sdd-build 07` mit BF-108.

**2026-08-30 · BF-108 behoben.** `flex-wrap` am Container, `basis-full min-w-0
lg:basis-auto lg:flex-1` am Titel. ⚠ **Der Umbruchpunkt ist `lg:`, nicht `sm:` wie in
Feature `06`** — dort füllt die Karte die Seitenbreite, hier steht sie in einer von drei
Spalten: ab `md:` misst die Spalte 229 px, und mit `sm:` wäre genau der gemessene Fall
stehen geblieben. Das Muster zu übernehmen hätte hier nicht gereicht; es musste
verstanden werden.

Gegen die Reproduktion aus dem Testbericht gemessen: **214 / 269 / 155 / 326 px** bei
320 / 375 / 768 / 1280 px — gleichauf mit den kuratierten Einträgen daneben. Die Höhe des
120-Zeichen-Titels fällt von **2352 px auf 168 px**, die gemessene Karte von 12 × 648 auf
155 × 48 px. 320 px bleibt überlauffrei, axe unverändert.

**Der neue Prüflauf fängt das Muster, nicht den Einzelfall:** `RoadmapCardLayoutTest`
verlangt, dass **keine** Überschrift in einem Flex-Container neben einem
`shrink-0`-Element steht, ohne selbst schrumpfen zu dürfen — geprüft über **beide**
Kartenvorlagen (`/roadmap` und `/community/ideen`). Damit fällt eine dritte Karte dieser
Bauart auf, bevor sie in die QA kommt. Zwei Gegenproben: Klassenkette entfernt → zwei
Fehlschläge, `flex-wrap` entfernt → einer, wiederhergestellt → grün.

**Nicht angefasst, weil außerhalb des Auftrags:** BF-109 und BF-110 (App-Hülle, jede
Seite des Projekts) und BF-111 (Feature `06`). 915 Tests grün. Nächster Schritt:
`/sdd-qa 07` — der dritte Messpunkt 768 px gehört in die Reihe.

**2026-08-30 · Zweiter QA-Durchlauf: 49 von 52, kein neuer Befund — abgenommen.** Geprüft
wurde nicht die Reparatur allein, sondern **ihre Umgebung**, nach der Lehre, die BF-108
selbst geliefert hat: **36 Messpunkte von 320 bis 1440 px** statt vier. Ergebnis: Der
Community-Titel ist auf der **gesamten Strecke** exakt so breit wie die kuratierten
Einträge daneben (Verhältnis 1,00), bei 768 px 155 statt 12 px.

⚠ **Ein Messwert sah nach einem Befund aus und war keiner.** Ab 1024 px fiel das Minimum
über alle Titel auf 91 px — es betrifft ausschließlich den Titel „Kurz" (91 × 24 px, eine
Zeile, lesbar). Ab `lg:` greift `lg:flex-1`, und ein kurzer Titel *soll* schmal sein.
**Die selbst gewählte `lg:`-Annahme des Bauberichts trägt**, an vier Punkten um den
Umbruch herum nachgeprüft.

**Die Gegenprobe zum neuen Prüflauf wurde unabhängig geführt** — mit einem anderen
Eingriff als der Bau: Klassen aus `_board_idea_card.html.twig` (**Feature 06**) entfernt,
Roadmap unberührt → Lauf rot, nennt `/de/community/ideen`. Der Musterlauf fängt also
tatsächlich beide Kartenvorlagen. Daraus ein Hinweis ohne Befundcharakter: Er liegt in
der Testdatei von `07`, prüft aber eine **projektweite** Regel — bei nächster Gelegenheit
gehört er an eine neutrale Stelle, sonst sucht jemand den Fehler am falschen Feature.

⚠ **Das Querscrollen bei 768–832 px ist BF-80, nicht dieses Feature.** Mit ausgeblendetem
`<header>` 0 px Überhang, und auf `/presse`, `/open`, `/about` und `/community/ideen`
identisch (36 → 28 → 20 → 12 → 4 → 0). Erstmals über den vollen Bereich vermessen.

**Drei Kriterien bleiben durchgefallen — alle drei ohne Codeanteil dieses Features:**
AK-34 und AK-38 an BF-109 (Fußzeile mit `<h4>`), AK-44 an BF-110 (`hreflang` spiegelt).
Beide sind *mittel* bzw. *niedrig* und blockieren nach den Regeln der Kette nicht; ihre
Reparatur ist je eine Zeile in `base.html.twig` und verändert **jede** Seite.

915 Tests grün, Prüfdaten restlos entfernt (`SELECT COUNT(*) FROM board_idea` → 0).
Nächster Schritt: **`/sdd-deploy 07`** — ⚠ **erst nach Feature `06`** (VB-01).

**2026-08-30 · Deploy angehalten, BF-112 gefunden und behoben.** Der Preflight lief
vollständig durch; Feature `07` ist committet und nach `dev` gemergt, die
Release-Vorbereitung für **v2026.08.30.3** liegt bereit. **Nichts ging nach
`production`.**

⚠ **Der neue Prüflauf hat beim ersten scharfen Einsatz funktioniert.** Sobald
`2026.08.30.3` in `CHANGELOG.md` stand, wurde `ChangelogCompletenessTest` rot und nannte
die Version — bis Registry-Eintrag und vier Übersetzungen standen. Genau wofür der fünfte
Punkt der Release-Checkliste gebaut wurde.

⚠ **Dabei fiel BF-112 auf, und der Fund gehört dem Release, nicht der QA.** Beide
QA-Durchläufe waren grün, weil beide gegen einen **eingefrorenen** Bestand liefen:
`testChangelogZeigtNeunReleasesUndDieSammelzeile` prüfte mit `assertCount(10, …)` gegen
eine feste Zahl und wäre bei **jedem** Release rot geworden. Ein Prüflauf, der bei jedem
korrekten Vorgang anschlägt, wird nach dem dritten Mal ignoriert — dann fehlt die
Absicherung, für die er gebaut wurde.

**Behoben:** Die Zahl wird aus `ChangelogRegistry` **abgeleitet statt genannt**. Der Lauf
prüft damit mehr als vorher — nicht eine Momentaufnahme, sondern die Kopplung zwischen
Registry und Seite. Zwei Gegenproben: ein weiteres Release → grün, ein vom Template
verschluckter Eintrag → rot. 915 Tests grün.

**Daraus eine Lehre für künftige Prüfläufe:** *Ein Lauf, der eine Liste zählt, gehört
einmal mit einem zusätzlichen Eintrag ausgeführt.* Dieselbe Familie wie die Lehre aus
Feature `05` — den Zustand herstellen, statt auf ihn zu warten —, hier auf den Release
angewandt.

Nächster Schritt: `/sdd-qa 07` über die betroffenen Kriterien (AK-20, AK-21, AK-22),
danach `/sdd-deploy` erneut. ⚠ Die Release-Vorbereitung liegt **uncommittet auf `dev`**
und wartet dort.

**2026-08-30 · Dritter QA-Durchlauf: BF-112 gegengeprüft — und dabei zweimal dieselbe
Bauart daneben gefunden.** AK-20, AK-21 und AK-22 sind belegt (11 Artikel = 10 Releases
plus Sammelzeile, kein stilles Release sichtbar, im Artikeltext einzig „TripAdvisor" als
Großschreibung — ein Produktname). Die Gegenprobe lief **mit einem anderen Eingriff als
der Bau**: alle `SUMMARISED` auf `SILENT`, Sammelzeile fällt weg → grün. Die Ableitung
ist nicht auf den Regelfall geraten.

⚠ **Die Reparatur hat die Stelle behoben, nicht die Lehre angewandt.** Zwei weitere
Prüfläufe tragen denselben Fehler, beide **nachgestellt**: **BF-113** —
`assertCount(8, …)` für die zurückgestellten Punkte wird rot, sobald ein neunter
dazukommt (und OF-03/OF-04 sehen genau das als Regelbetrieb vor). **BF-114** — die
Jahres-Prüfungen nehmen an, dass es nur **ein** Jahr gibt; mit einem Release vom
15.01.2027 werden **zwei** Läufe rot. Das tritt **sicher** ein, nicht nur möglicherweise.

**Daraus ein projektweites Muster** (in `befunde.md`): *Feste Zahlen in Prüfläufen, die
vom wachsenden Bestand abhängen.* Dreimal dieselbe Bauart in einem Feature. Alle drei
sind grün, solange sich nichts ändert — und werden rot, sobald der Regelbetrieb genau
das tut, wofür das Feature gebaut wurde. **Regel: Wer eine Liste zählt, leitet die
erwartete Zahl aus der Quelle ab und führt den Lauf einmal mit einem zusätzlichen Eintrag
aus.** Es ist dasselbe Versäumnis wie bei BF-107 → BF-108: eine Stelle repariert, die
Nachbarschaft nicht mitgenommen.

⚠ **Randnotiz zur Prüfung selbst:** Eine Probe lief zunächst gegen eine **gestoppte
Datenbank** und meldete „actual size 0" — was wie ein Befund aussah und keiner war
(`Connection refused`). Nach dem Start des Containers wiederholt. Festgehalten, weil ein
Prüfbericht auch sagen muss, wo er sich selbst korrigiert hat.

**Abgenommen mit Empfehlung:** Der höchste Grad ist *mittel*, keiner der Befunde trifft
eine Funktion für Besucher — nach den Regeln blockiert das nicht. **BF-114 sollte
trotzdem vor dem Deploy fallen**, weil er garantiert eintritt und dann zwei Läufe rot
sind, ohne dass jemand einen Fehler gemacht hat. 915 Tests grün.

Nächster Schritt: **`/sdd-build 07`** mit BF-113 und BF-114 (empfohlen) **oder**
`/sdd-deploy` — beide Befunde sind erfasst. ⚠ In jedem Fall erst Feature `06`.

**2026-08-30 · BF-113 und BF-114 behoben — dieselbe Regel, drei Stellen.** Alle nach dem
Muster von BF-112: **Die erwartete Zahl wird aus der Quelle abgeleitet, nicht genannt.**
BF-113 zieht sie aus `RoadmapRegistry::shelved()`; BF-114 leitet die Zahl der
zugeklappten Jahre ab (jedes außer dem laufenden) und verlangt die Sammelzeile nur noch
in **ihrem eigenen** Jahr.

⚠ **Eine dritte Stelle kam beim Beheben dazu, die der Testbericht nicht benannt hatte:**
Der Datenlieferant von `testDasLaufendeJahrIstOffenDasFruehereZugeklappt` trug selbst
feste Jahreszahlen (`['2026', 0]`, `['2027', 1]`). Er liefert jetzt zwei Lagen, die es
immer gibt — das jüngste Jahr **mit** Einträgen und eines **ohne**.

**Beide Reproduktionen aus dem Bericht sind grün**, und beide Läufe haben ihre Prüfkraft
behalten: Ein vom Template verschluckter zurückgestellter Punkt macht den einen rot („Die
Seite zeigt nicht die 8 …"), ein fälschlich zugeklapptes laufendes Jahr den anderen („…
müssen 0 von 1 Jahren zugeklappt sein"). 915 Tests grün.

Nächster Schritt: `/sdd-qa 07`, danach `/sdd-deploy`. ⚠ Erst Feature `06`; die
Release-Vorbereitung für `v2026.08.30.3` liegt weiterhin uncommittet auf `dev`.

**2026-08-30 · Vierter QA-Durchlauf: BF-113 und BF-114 halten — und eine
Vollbetriebs-Probe fördert BF-115 zutage.** Statt 25 feste Zahlen zu lesen, wurde **der
Bestand wachsen gelassen**: zwei Releases in einem neuen Jahr, ein neunter
zurückgestellter Punkt, ein neuntes kuratiertes Vorhaben. Beide Reparaturen halten, beide
Läufe haben ihre Prüfkraft behalten.

⚠ **Der eine Fehlschlag war kein Zahlenmuster mehr, sondern ein inhaltlicher Fehler.**
**BF-115:** Ein **zugeklapptes Jahr trägt keine Überschrift**. Das laufende bekommt
`<h2 id="year-…">`, ein früheres nur ein `<summary>` — für einen Screenreader keine
Gliederung. Am ausgelieferten HTML gemessen: `h1 „Changelog"` → `<summary> „2027"` →
`h3 „Probe A"`, also ein Sprung von h1 auf h3 (WCAG 1.3.1).

**Heute unsichtbar, tritt sicher ein:** Solange die Registry ein einziges Jahr führt, gibt
es kein `<details>`. Mit dem ersten Release im Januar 2027 rutscht 2026 hinein und
verliert seine Überschrift — auf einer Seite, die dann längst live ist. ⚠ **Anders als
BF-109 gehört dieser Befund dem Feature**, nicht der Hülle: AK-38 fällt jetzt aus **zwei
unabhängigen** Gründen durch.

**Daraus das zweite Muster dieses Features:** *Ein Zustand, den der Kalender erst später
herstellt, wird heute nicht geprüft.* Drei QA-Durchläufe gegen den eingefrorenen Stand
haben BF-114 und BF-115 nicht gefunden — beide sitzen im selben Zweig, dem zugeklappten
Jahr.

**Neuer Prüflauf `RoadmapYearHeadingTest`**, der die Seite mit einem **frei wählbaren
laufenden Jahr** rendert und damit den Zustand herstellt, den der Kalender erst 2027
liefert. Zwei seiner fünf Fälle sind **absichtlich rot**, bis BF-115 fällt. Beim
Schreiben zweimal nachgeschärft: Der erste Wurf maß die ganze Seite (und schlug an BF-109
an), der zweite hielt die `h3` der Artikel für die Jahresüberschrift.

⚠ **Production-ready: nein — aber nicht wegen des Schweregrads.** BF-115 ist *mittel* und
würde nach den Regeln nicht blockieren; der absichtlich rote Prüflauf lässt jedoch den
Deploy-Preflight an seinem Punkt „Verifikationsbefehl grün" scheitern. Das ist gewollt:
Ein Befund, der garantiert eintritt, soll nicht als Fußnote mitfahren.

Nächster Schritt: **`/sdd-build 07`** mit BF-115 — eine Zeile im Template
(`<summary><h2>…</h2></summary>`), danach ist der Lauf grün.

**2026-08-30 · BF-115 behoben.** `<summary><h2 class="inline">…</h2></summary>` — HTML
erlaubt im `<summary>` Heading-Content, `inline` hält die Überschrift neben dem
Aufklapp-Dreieck (gemessen: 21 px hoch in einem 60 px hohen `<summary>`, kein Umbruch).
Mit **hergestellter Reproduktion** am ausgelieferten HTML nachgemessen: Die Kette lautet
`h1 → h2 „2027" → h3 → h2 „2026" → h3 …` — **keine Sprünge**. Gegenprobe: Überschrift
entfernt → beide Läufe rot. **920 Tests grün**, vorher zwei absichtlich rot.

⚠ **Ein Messfehler von mir, der fast zu einer Falschmeldung geführt hätte.** Der erste
Messbefehl suchte `<h[1-6]>` **und** `<summary>` in einem Ausdruck — dabei greift der
`<summary>`-Zweig zuerst und verschluckt die darin liegende `h2`. Das Ergebnis sah aus
wie ein unveränderter Sprung, während der PHPUnit-Lauf mit echtem DOM-Parser zur selben
Zeit grün war. Erst der Vergleich beider brachte es ans Licht. **Ein Regex ist kein
Parser** — festgehalten, weil derselbe Fehler jede HTML-Messung dieses Projekts treffen
kann.

**Neu offen als OF-11** (nicht behoben, wäre eine neue Anforderung): Der
Aktualitätshinweis unterscheidet **nicht zwischen Vergangenheit und Zukunft** —
`date().diff()` liefert den Betrag. Ein Eintragsdatum, das versehentlich in der Zukunft
liegt, erzeugt „Zuletzt aktualisiert am 15. Januar 2027 — seither sind 136 Tage
vergangen". Kein Kriterium deckt den Fall ab.

Nächster Schritt: `/sdd-qa 07`.

**2026-08-31 · Fünfter QA-Durchlauf: 50 von 52, kein neuer Befund — abgenommen.** Die
Vollbetriebs-Probe wurde weitergetrieben: **drei Jahre** in der Registry (2026, 2027,
2028), eines mit einem stillen Release, nur 2026 mit Sammelzeile. Beide zugeklappten
Jahre tragen ihre `h2`, die Kette ist lückenlos, 920 Tests grün.

**AK-38 ist damit bestanden** — es fiel zuvor aus **zwei** Gründen durch, der
feature-eigene (BF-115) ist behoben. Übrig bleiben AK-34 und AK-44, beide ohne
Codeanteil in diesem Feature: `heading-order` an der Fußzeile (BF-109) und die
`hreflang`-Spiegelung (BF-110), beide in `base.html.twig` und auf jeder Seite.

⚠ **Der methodische Fund dieses Durchlaufs: axe hätte BF-115 nie gefunden.** Mit
zurückgenommener Reparatur gemessen — bei **zugeklappten** Jahren meldet axe nur den
Fußzeilen-Verstoß, der Sprung von h1 auf h3 bleibt unsichtbar; erst **aufgeklappt** sieht
axe ihn. **Ein zugeklapptes `<details>` verbirgt seinen Inhalt vor der Prüfung** — das
gilt für jeden axe-Lauf des Projekts, auch die von `02`, `03` und `05`. Projektweit
nachgesehen (`/open`, `/vergleich`, `/criteria`): **kein verborgener Befund**. Die
Beobachtung bleibt gültig und gehört in künftige Prüfungen: *Ein axe-Lauf sollte
aufklappbare Abschnitte einmal geöffnet messen.*

**Fünf Durchläufe, vier davon mit je einem Fund** — BF-108 (768 px), BF-112/113/114
(feste Zahlen), BF-115 (zugeklapptes Jahr). Jeder wurde sichtbar, weil der Durchlauf
einen Zustand herstellte, den der vorherige nicht angefasst hatte. Der fünfte fand
nichts mehr.

Nächster Schritt: **`/sdd-deploy`** — die Release-Vorbereitung für `v2026.08.30.3` liegt
fertig auf `dev`. ⚠ Erst Feature `06`, dann `07` (VB-01).

**2026-08-31 · Deploy von `v2026.08.31` gescheitert und zurückgerollt — BF-116.** Der
Preflight lief vollständig durch (920 Tests, Prod-Container grün, Assets deterministisch);
dabei fiel auf, dass die gestern vorbereitete Version **falsch datiert** war
(`2026.08.30.3` bei einer Auslieferung am 31.) — korrigiert auf **v2026.08.31** über alle
fünf Stellen der Checkliste.

⚠ **Auf Produktion brach `cache:clear` ab:** *„The target-entity `App\Entity\self` cannot
be found in `App\Entity\BoardIdea#duplicateOf`."* Ursache ist `#[ORM\ManyToOne]` **ohne
`targetEntity`** bei einem Property vom Typ `?self` — Doctrine leitet das Ziel aus dem Typ
ab, und **PHP 8.4** (Produktion) löst `self` dort nicht zur Klasse auf. **Lokal läuft PHP
8.5.2**, dort greift die Auflösung; `cache:warmup --env=prod` und
`doctrine:schema:validate` waren grün.

**Die Wartungsseite blieb wie vorgesehen stehen** (ENDLECH-5), die Seite antwortete mit
503. **Rollback per Revert-Commit**, Lauf grün, Seite wieder online — `/`,
`/de/restaurants` und `/de/open` mit 200, Fußzeile zeigt **v2026.08.30.1**, die neuen
Adressen erwartungsgemäß 404.

⚠ **Die Datenbank blieb unberührt.** Der Abbruch kam im `composer install`-Post-Script,
also **vor** `doctrine:migrations:migrate` — im Protokoll null Migrations-Zeilen. Der
Rollback war deshalb gefahrlos, und die Sicherung wurde nicht gebraucht.

**Daraus das dritte Muster der Sorte „lokal ≠ Produktion":** Nach `mod_dir` (BF-100) und
MySQL 8 gegen MariaDB 10.5 ist es jetzt die **PHP-Version**. Alle drei sind vor dem Deploy
unsichtbar und von keinem Prüflauf erfasst. *Was aus der Laufzeitumgebung kommt —
Webserver, Datenbank, Sprachversion —, ist lokal nicht geprüft, sondern nur nicht
aufgefallen.*

**Feature `06` steht damit auf `review`** (kritischer Befund an ausgeliefertem Code-Stand),
**Feature `07` bleibt `approved`** und ist auslieferbar, kommt aber nicht raus, solange
`06` blockiert (VB-01). Nächster Schritt: **`/sdd-build 06`** mit BF-116 — die Reparatur
ist `targetEntity: self::class`.

**2026-08-31 · BF-116 behoben.** `#[ORM\ManyToOne(targetEntity: self::class)]` —
`self::class` wird zur Übersetzungszeit aufgelöst und ist von der Sprachversion
unabhängig. Doctrine meldet lokal jetzt wie zuvor `App\Entity\BoardIdea`;
`cache:clear --env=prod` bleibt grün. 922 Tests.

⚠ **Der Fehler ist lokal nicht reproduzierbar, und das prägt den Nachweis.** PHP 8.4
steht auf diesem Rechner nicht zur Verfügung (nur 8.3 als Symlink auf 8.5 und 8.5
selbst), und auf 8.5 löst Doctrine `?self` korrekt auf. **Jede verhaltensbasierte
Prüfung bliebe hier blind.** Der Nachweis ist deshalb ein **statischer** Prüflauf:
`MappingSelfTargetTest` verlangt, dass keine Assoziation mit dem Property-Typ `self`
ohne explizites `targetEntity` steht.

**Projektweit geprüft: genau eine Stelle war betroffen.** Fünf weitere Assoziationen
haben kein `targetEntity`, aber auch keinen `self`-Typ — dort löst Doctrine den
Klassennamen problemlos auf.

⚠ **Beim Schreiben des Prüflaufs fiel derselbe Fehler zweimal an:** Das erste Suchmuster
verlangte Klammern hinter dem Attribut und übersah `#[ORM\ManyToOne]` — **ausgerechnet
die Schreibweise, an der BF-116 hing**. Der Lauf prüft deshalb zuerst sich selbst: Er
verlangt, mindestens ein Attribut *ohne* Klammern gefunden zu haben. Zwei Gegenproben:
`targetEntity` entfernt → rot; **eine neue Entity mit demselben Muster** → rot.

**Der endgültige Beleg ist der nächste Deploy** — lokal lässt sich nur zeigen, dass die
Ursache strukturell beseitigt ist.

⚠ **Nebenbefund, vorbestehend:** `doctrine:schema:validate` meldet „schema is not in sync"
— mit **und ohne** meine Änderung identisch. Es ist das dokumentierte Diff-Rauschen
(Index-Umbenennungen an `cuisine`, `ordering_option`, `restaurant`); `board_idea` kommt
darin nicht vor, und „The mapping files are correct" steht daneben.

Nächster Schritt: `/sdd-qa 06`.

**2026-08-31 · QA von `06` nach BF-116: kein neuer Befund — abgenommen.** Der Baubericht
meldete den Fehler als **lokal nicht reproduzierbar** und stützte sich auf einen
statischen Prüflauf. Diese Prüfung hat die Reproduktion **nachgeholt** — mit einem
einzigen Befehl:

```
docker run --rm -v "$PWD":/app -w /app php:8.4-cli php <skript>
```

**Ursache belegt:** `ReflectionProperty::getType()->getName()` liefert unter **8.4.25
`self`**, unter **8.5.2 `App\Entity\BoardIdea`**. **Reparatur belegt, in beide
Richtungen:** Doctrines `AttributeDriver` löst unter 8.4 **ohne** die Angabe zu
`App\Entity\self` auf — Klasse existiert nicht, **wortgleich mit der Produktionsmeldung**
—, **mit** der Angabe zu `App\Entity\BoardIdea`. Kein Symfony-Kernel, keine Extensions,
keine Datenbank nötig.

**Daraus ein projektweites Muster, das mehr wert ist als der Befund selbst:** *Bevor ein
Befund als „lokal nicht reproduzierbar" abgelegt wird, wird die Laufzeitumgebung im
Container nachgestellt.* BF-116 galt als „nur auf Produktion prüfbar" — bis ein
`docker run` in unter einer Minute das Gegenteil zeigte. Für Apache und `mod_dir`
(BF-100) wäre es `php:8.4-apache`.

Der neue Prüflauf wurde mit einer **anderen** Gegenprobe als der Bau geprüft (eine
`OneToOne`-Assoziation mit `self`-Typ) → rot. Er fängt also nicht nur `ManyToOne` und
nicht nur den bekannten Fall. Regression grün: Dublettenzusammenführung, beide
Board-Seiten mit 200, `cache:clear --env=prod` OK, **922 Tests**.

⚠ **Offen bleibt BF-111** (*mittel*, blockiert nicht): Die wartende Idee ohne Verfasser
ist weiterhin öffentlich lesbar; die Zeile steht unverändert in
`BoardController.php:169`.

Nächster Schritt: **`/sdd-deploy`** — beide Features zusammen, `06` vor `07` (VB-01).

**2026-08-31 · Release v2026.08.31 ist live — Features `06` und `07` zusammen.** Der
dritte Anlauf nach dem gescheiterten Deploy vom selben Tag. **Eine Migration** lief mit
(`Version20260830120000`, additiv: `board_idea`, `board_vote`).

⚠ **Der Preflight hatte diesmal einen Schritt mehr — und genau den, der gefehlt hatte:**
das **Mapping gegen PHP 8.4** im Container (`docker run --rm -v "$PWD":/app -w /app
php:8.4-cli`). 15 Entities geprüft, jede Assoziation zeigt auf eine existierende Klasse.
Dieser Schritt hätte BF-116 gefangen, bevor die Seite offline ging.

Auf Produktion nachgeprüft:

- Fußzeile zeigt **v2026.08.31** — der Beleg, dass der neue Container läuft
- **Alle acht neuen Adressen mit 200**: `/{lb,de,fr,en}/roadmap` und `…/changelog`
- `/de/community/ideen` und `…/neu` mit 200 — der Beleg, dass die Migration durch ist
- Kurzlinks `/roadmap` und `/changelog`: **302, ein Sprung**, kein 301 (die Lehre aus BF-100)
- Roadmap inhaltlich wie in VB-02 festgelegt: *In Arbeit* — Öffentliche Roadmap und
  Changelog · *Geplant* — Bewertungen und Kommentare, Kartenansicht, Favoriten ·
  *Angedacht* — Native iOS-App, Chat-Fenster, KI-gestützte Suche, Android-App mit Google-
  und Apple-Anmeldung · **8 Punkte** unter „Bewusst nicht gebaut"
- **Kein Datum an keinem Eintrag** (AK-06) — auf der ausgelieferten Seite gemessen
- Changelog: 11 Einträge, der erste „Roadmap und Changelog · 2026.08.31"; **kein stilles
  Release sichtbar**
- **EC-01 erstmals im Echtbetrieb belegt**: Das Board ist auf Produktion leer, und die
  Spalte „Geplant" zeigt nur die kuratierten Vorhaben — kein leerer Community-Block
- Fußzeile führt beide Seiten, keine Prüfdaten in der Anwendung

⚠ **Der Tag `v2026.08.31` wurde verschoben** (von `a057482` auf `783ae3f`). Das Release
unter der ursprünglichen Marke wurde **nie ausgeliefert** — der Deploy scheiterte an
BF-116 —, und ein zusätzliches `v2026.08.31.1` für einen Fehler, den nie jemand gesehen
hat, hätte die Historie mit einem Phantom belastet.

**Offen, ohne den Betrieb zu beeinträchtigen:** **BF-109** und **BF-110** (Altlasten der
App-Hülle, jede Seite), **BF-111** (wartende Idee ohne Verfasser, Feature `06`),
**BF-103** (das externe Board `endlech.userjot.com` nimmt weiter Einreichungen entgegen —
laut Betreiberentscheidung **nach** dem Deploy abzuschalten, jetzt fällig) und **OF-11**
(Aktualitätshinweis bei einem Datum in der Zukunft).

**2026-08-30 · Roadmap-Pflege im Admin: erwogen, bewusst vertagt — keine Spec.** Der
Wunsch, die drei Spalten und den Block „Bewusst nicht gebaut" in der Verwaltung zu
pflegen, kehrt eine **ausdrücklich begründete Entwurfsentscheidung** von `07` um
(Decision Log 4: „Kuratierte Vorhaben stehen im Code", Alternative *Entität mit
Verwaltungsmaske* verworfen). Betroffen wären 16 Einträge und **128 Texte in vier
Sprachen**, die heute im Katalog liegen und von `RoadmapCatalogueTest` erzwungen werden.

**Entscheidung: erst `07` ausliefern, dann anhand der Erfahrung entscheiden.** Ob die
Pflege im Code wirklich stört, zeigt sich nach ein paar Releases — und beantwortet die
Frage mit Beobachtung statt mit Vermutung. Es wurde **keine Spec geschrieben und kein
Feature angelegt**; `07` bleibt unverändert `approved`.

**Der Zuschnitt steht aber schon fest, falls es gebaut wird** — und er ist der schmale:

| Frage | Entscheidung |
|---|---|
| Umfang | **Nur verschieben und Reihenfolge.** Kein Texteingabefeld im Admin |
| Texte | **bleiben im Katalog.** Keine Migration, kein Datenverlust |
| Changelog | **bleibt im Code.** AK-26 verlöre sonst seine Grundlage — ein Prüflauf kann eine Datenbank nicht gegen `CHANGELOG.md` halten |

⚠ **Das ist der entscheidende Zuschnitt, nicht eine Detailfrage.** Solange nur die
*Zuordnung* verwaltet wird und die Texte im Katalog bleiben, bleiben beide Zusagen
strukturell erhalten: die vier Sprachen je Eintrag und die Begründungspflicht (AK-05,
AK-29). Bei voller Pflege im Admin fielen beide weg — ein Eintrag ohne Begründung und
eine Roadmap, die auf drei von vier Sprachfassungen unvollständig ist, wären erstmals
möglich. **Wer das Thema später aufgreift, beginnt hier und nicht bei der Maske.**

**2026-08-30 · Alle sieben offenen Fragen von `07` entschieden — das Feature ist
baubereit.** VB-02 und VB-03 sind damit erfüllt, T07 und T08 stehen nicht mehr still.
Die Spaltenzuordnung folgt dem PRD-Abschnitt „Vorschlag: Reihenfolge" (Bewertungen ·
Karte · Favoriten unter *Geplant*, iOS · Chat-Widget · KI-Filter · Android unter
*Angedacht*), „In Arbeit" trägt das Feature selbst. Der Changelog startet mit **neun**
öffentlichen Releases plus einer Sammelzeile „Aufbau der Plattform"; alles Übrige trägt
den Vermerk *still*. Der Zwischenspeicher hält **3600 s** — der Listener deckt die
Änderungen ab, die Dauer ist nur das Netz.

**Zwei Entscheidungen haben je ein Kriterium nachgetragen** — damit sind es **52**:
**AK-51** (ein Satz neben dem Repo-Verweis, was den Leser dort erwartet, OF-05) und
**AK-52** (die Auswahlregel der Community-Ideen steht **immer** über der Gruppe, auch
bei weniger als zehn, OF-06). Beide sind in `design.md` und `tasks.md` zugeordnet; die
Abdeckung ist maschinell gegengeprüft und lückenlos.

⚠ **OF-02 ist entschieden und hat einen neuen offenen Punkt hinterlassen.**
„Bewertungen und Kommentare" steht auf der Roadmap, aber **ohne Bezug auf das
Werbeversprechen** der Startseite. Damit bleibt Risiko 1 aus dem PRD unberührt: Die
Startseite wirbt weiter mit „Bewerten" und „Echte Bewertungen von echten Besuchern",
und das Produkt kann es nicht. Festgehalten als **OF-08** in `spec.md`. Es gehört in
ein eigenes Vorhaben — entweder die Funktion bauen oder die Texte anpassen —, **nicht
in Feature `07`**; hier ist bewusst keine Feature-Zeile dafür angelegt worden, weil das
eine neue Anforderung wäre und Michaels Entscheidung braucht.

**2026-09-05 · Feature `08` abgenommen** (dritter QA-Durchlauf): 58 von 58 Kriterien,
**kein offener Befund**. BF-122 ist behoben und über vier Sonden gegengeprüft — darunter
der vollständige Missbrauchsweg über HTTP und zehn Erneuerungsrunden. Keine der sechs
Reparaturen ist zurückgekommen; 985 Tests grün. AK-44 bleibt *nicht prüfbar* (der
Test-Override hebt jeden Limiter auf 10000) und gehört in die Nachprüfung auf Produktion.

**2026-09-05 · Status von `08` auf `review` zurückgesetzt.** `approved` beschrieb den
Stand nicht mehr: Sobald ein Befund zur Behebung ansteht, ist das Feature nicht
abgenommen, unabhängig von seinem Grad. Die Korrektur gehört zur Entscheidung im
Deploy-Preflight und hätte dort schon erfolgen müssen — nachgeholt beim nächsten
`/sdd-build`, weil dessen Eingang sonst formal verschlossen gewesen wäre.

**2026-09-05 · Auslieferung von `08` angehalten — Preflight.** Der Betreiber hat
entschieden, **BF-122 vor dem Release zu beheben**. Formal blockiert der Befund nicht
(Grad *mittel*), die Entscheidung geht darüber hinaus. Es wurde nichts committet, kein
Release erzeugt und nichts ausgeliefert; der Stand liegt unverändert auf
`feature/08-app-warteliste`.

Zwei Preflight-Punkte waren zum Zeitpunkt des Abbruchs ohnehin offen: das
Arbeitsverzeichnis (53 Dateien nicht committet) und der Versionsstand — alle fünf
Release-Stellen zeigen noch auf `2026.09.02`. Beide gehören in den Release-Durchgang
**nach** der Reparatur, nicht davor: Der Code ändert sich noch.

Bestätigt und damit kein Hindernis mehr: Der Post-Deployment-Command für die Migrationen
steht in Coolify, und `APP_TESTFLIGHT_URL` ist dort hinterlegt. Die Freigabe, den Merge
nach `master` beim nächsten Anlauf vollständig durchzuziehen, liegt vor.

**2026-09-05 · Feature `08` abgenommen** (zweiter QA-Durchlauf): 57 von 58 Kriterien,
alle fünf Befunde des ersten Laufs behoben und gegen ihre Reproduktion geprüft. **Vier der
fünf Reparaturen sind nebenwirkungsfrei; eine hat BF-122 erzeugt** — `renewConfirmationWindow()`
setzt `createdAt` zurück, und daran hängt außer der Token-Frist auch die 30-Tage-Aufbewahrung.
Grad *mittel*, blockiert nicht, gehört aber vor dem Release entschieden.

**2026-09-05 · Feature `08` geprüft — nicht abgenommen** (erster Durchlauf). 53 von 58 Kriterien bestanden,
vier durchgefallen, eines nicht prüfbar. Drei Befunde mit Grad *hoch*, und alle drei liegen
auf demselben Weg: dem Umgang mit einer Adresse, die schon einmal eingetragen wurde.
**BF-119 betrifft nicht nur `08`** — die Gegenprobe am Partner-Formular zeigt dieselbe
Ausnahme; alle vier Formulare des Projekts prüfen E-Mail-Adressen großzügiger, als der
Mailversand sie akzeptiert.

**2026-09-04 · Feature `08` aufgenommen und spezifiziert.** Dritte Warteliste neben Partner
(B14) und Organisationen (B15), diesmal für die mobile App. Sie teilt deren Mechanik
(`WaitlistConfirmationService`, `WaitlistEntryInterface`, Abmeldeweg aus BF-37) und behebt
im selben Zug zwei Fehlbestände, die dort offen sind: der Bestätigungstoken läuft ab
(FB-03) und nie bestätigte Einträge werden nach 30 Tagen tatsächlich gelöscht (FB-02) —
AK-49 verlangt ausdrücklich den *Aufruf* des Aufräumlaufs, nicht bloß seine Existenz.

## Inventar

| ID | Feature | Prio | Status | Abhängig von | Zuletzt |
|---|---|---|---|---|---|
| 01 | Betroffenenrechte: Konto löschen, Daten exportieren, Passwort zurücksetzen | P0 | roadmap | B01, B04, B19 | 2026-08-23 · aus BF-04 herausgelöst |
| 02 | Barrierefreiheit der Plattform (EN 301 549 / RAWeb) | P0 | **deployed** | B01–B26 | 2026-08-29 · live in v2026.08.29 |
| 03 | Vergleichsseiten (vs. Google Maps, Wheelmap, TripAdvisor) | P1 | **deployed** | B05, B13, B24, B16, 02 | 2026-08-29 · live in v2026.08.29, auf Produktion nachgeprüft |
| 04 | Marketing-Kontakte in Brevo | P1 | **deployed** | B01, B14, B15, B22, 01 | 2026-08-30 · live in v2026.08.30, Migrationen durch, auf Produktion belegt |
| 05 | Presse-Kit | P2 | **deployed** | B13, B16, B24, 02, 03 | 2026-08-30 · live in v2026.08.30.1, auf Produktion nachgeprüft |
| 06 | Community Feedback Board | P1 | **deployed** | B01, B02, B19, B21, B24, 01, 02 | 2026-08-31 · live in v2026.08.31, auf Produktion nachgeprüft |
| 07 | Öffentliche Roadmap und Changelog | P2 | **deployed** | 06, B13, B16, B24, 02, 03, 05 | 2026-08-31 · live in v2026.08.31, auf Produktion nachgeprüft |
| 08 | Warteliste für die mobile App (iOS-Beta / Android) | P1 | **approved** | B14, B22, B24, 02, 04 | 2026-09-05 · QA³: 58/58, **kein offener Befund** |
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
| 06 | Öffentliches Ideen-Board zur Plattform: Einreichen mit Konto, Freigabe vor Veröffentlichung, Zustimmung, fünf Status mit erzwungener Ablehnungsbegründung, Dublettenzusammenführung, eine Mail bei Veröffentlichung | neu: `/community/ideen` und die Warteschlange in der Verwaltung; berührt Fußzeile, Admin-Dashboard sowie Kontolöschung und Datenexport aus Feature `01` |
| 07 | Zwei öffentliche Seiten: Roadmap in drei Status-Spalten samt Block „Bewusst nicht gebaut“, Changelog als redaktionelle Kurzfassung je Release nach Jahren gruppiert; Community-Ideen mit Status `Geplant` werden live eingezogen | neu: `/roadmap` und `/changelog`; berührt Fußzeile, `docs/prd.md` (Roadmap-Tabelle) und die Release-Checkliste in `CLAUDE.md` |
| 08 | Öffentliches Formular mit Plattformwahl (genau eine), Double-Opt-In, TestFlight-Link in der **zweiten** Mail, Abmeldelink, Aufräumlauf nach 30 Tagen, Kennzahl auf `/open` ab 50 Vormerkungen | neu: `/{_locale}/app` samt sprachfreier Weiterleitung; berührt Fußzeile, Startseite, `/admin/warteliste` (dritte Quelle), `/open` und die Löschkaskade aus Feature `01` |
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
