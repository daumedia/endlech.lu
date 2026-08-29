# Changelog

Alle Änderungen an **Endlech.lu** werden in dieser Datei dokumentiert.

![Version](https://img.shields.io/badge/version-2026.08.29-blue)
![Status](https://img.shields.io/badge/status-beta-green)

## [Unreleased]

### Der Deploy zeigt eine Wartungsseite statt 500er (2026-08-29)

Der Deploy von v2026.08.29 hat einen Besucher in einen Serverfehler laufen lassen
(Sentry `ENDLECH-5`, 10:48:14 UTC — mitten im Lauf von 10:47:41 bis 10:48:46).

**Die Ursache ist kein Code-Fehler, sondern die Reihenfolge im Deploy.** Ab
`git reset --hard` liegen die neuen PHP-Dateien neben dem kompilierten Container des
Vorgänger-Releases. Der rief `new ApiRateLimitSubscriber($anonymous, $login)` mit zwei
Argumenten auf, während die Datei auf der Platte seit BF-25 drei verlangt
(`api_register`). Weil die Klasse an `kernel.request` hängt, traf das **jede** Route,
nicht nur `/api/v1` — und beim Rendern der Fehlerseite noch einmal.

Der Beleg steht im Event selbst: `release: endlech@2026.08.09`. Die Angabe kommt aus
`%app.version%` und damit aus dem Container — der war noch der alte, während Sentry
bereits die neue Datei zeigte.

`deploy.sh` legt jetzt vor dem Reset `var/maintenance` an und entfernt die Datei im
`EXIT`-Trap nach `cache:clear`. `public/index.php` prüft sie **vor**
`vendor/autoload_runtime.php` — die Prüfung darf weder Container noch Autoloader
brauchen, weil genau die in diesem Moment unvollständig sein können. Besucher sehen
für rund 35 Sekunden eine 503 mit `Retry-After` und `public/maintenance.html`.

**Bei einem gescheiterten Deploy bleibt die Wartungsseite bewusst stehen.** Der
Arbeitsbaum ist dann neu, der Container alt oder die Migration halb durch — eine 503
ist dort besser als der 500er, den dieser Zustand sonst liefert. Das Signal zum
Eingreifen ist der rote Actions-Lauf.

Die Flag-Datei liegt unter `var/`, weil `git clean -fd` ohne `-x` läuft und
Gitignoriertes unangetastet lässt. Unter `public/` wäre sie nach dem `clean` weg.

## [2026.08.29] – Vergleichsseiten, Barrierefreiheit und die Rückerfassung

### Vergleichsseiten: Endlech.lu neben Google Maps, Wheelmap und TripAdvisor (2026-08-29)

Ein Fußzeilenbereich „Vergleiche", eine Übersichtsseite unter `/vergleich` und drei
Vergleichsseiten in allen vier Sprachen. 54 Merkmalszeilen, 18 Primärquellen — jede
mit Adresse und Prüfdatum.

Der Ton folgt den öffentlich zugesagten Produktprinzipien statt der Werbewirkung:

- **Die Abdeckungszeile nennt die eigene, kleinere Zahl.** „11 Lokale" steht dort gegen
  „250 Millionen Orte weltweit", live aus derselben Quelle wie `/open`.
- **Jede Seite räumt ein, worin der andere besser ist** — ein Prüflauf erzwingt das. Bei
  Wheelmap steht in der Gruppe „Offenheit" dreimal Ja gegen Ja; die Seite empfiehlt
  Wheelmap ausdrücklich.
- **Was sich nicht belegen ließ, steht nicht in der Tabelle.** Deshalb tragen die drei
  Seiten nicht dieselbe Zeilenmenge.

Zwei Falschbehauptungen hat die Recherche verhindert: „TripAdvisor hat keinen
Barrierefreiheitsfilter" (es gibt ihn — er erscheint nur dort in der Filtervorschau, wo
das Merkmal örtlich häufig ist) und **Jaccede als lebender Wettbewerber**. Die
französische Plattform ist seit dem 2. Juli 2026 nur noch ein statischer Abzug: Suche,
Anmeldung und das Anlegen von Orten antworten mit 404, beide Apps sind aus den Stores.
Der Vergleich wurde gestrichen. Die Lehre steht als Warnung im Quelltext — HTTP 200 und
sichtbarer Inhalt sind kein Betriebsnachweis.

Kein Datenmodell, keine Migration: Die Struktur liegt als Aufzählungen und Wertobjekte
in `src/Comparison/`, die Texte in der neuen Übersetzungsdomain `comparison`.

**Bei 320 px ersetzt eine Kartenliste die Tabelle.** Mit erklärenden Halbsätzen in
beiden Wertspalten wird die Tabelle 525 px breit, und die Seite scrollte waagerecht. Der
naheliegende erzwungene Umbruch wurde verworfen — er zerlegt Wörter mitten im Wort. Je
Breite ist genau eine Darstellung im Accessibility-Tree.

### Alle 72 Befunde der SDD-Rückerfassung sind behoben (2026-08-25)

Zehn Blöcke, nach Schweregrad statt nach Feature-Nummer. Was dabei zählt, ist
nicht die Zahl, sondern dass vier **Muster** geschlossen wurden — jedes mit einer
Vorkehrung dagegen, dass es wiederkommt.

**Sicherheit**
- **Der Sprachumschalter führte auf fremde Seiten.** `?_locale=//fremd.example/de`
  erzeugte `href="///fremd.example/…"`, und der Browser navigierte auf den fremden
  Host — ein Open Redirect von der echten Domain aus, auf jeder öffentlichen Seite.
  Andere Werte desselben Parameters kippten zehn von zehn Seiten in einen 500er,
  und `sentry.yaml` filtert keine 500er. (BF-68)
- `admin_set_locale` übernahm den Referer ungeprüft als Weiterleitungsziel — mit
  ausgerechnet dem Zugang, der ohne zweite Stufe auskommt. (BF-33)
- Eine hochgeladene `.html` wurde als `text/html` ausgeliefert und lief damit im
  Ursprung der Anwendung; dasselbe galt für eine `.svg` mit `<script>`. (BF-57)
- Der Bestätigungstoken stand im `request`-Kanal des Produktionslogs: 31 Zeilen für
  `app_email_change_confirm`. Wer das Hoster-Log lesen konnte, konnte eine
  E-Mail-Änderung fremd bestätigen. (BF-23)
- Das Registrierformular verriet, wer hier ein Konto hat. Auf einer
  Barrierefreiheitsplattform erfährt man damit nicht, dass jemand hier isst,
  sondern dass jemand nach barrierefreien Lokalen sucht. (BF-09)
- Ein Restaurantname mit führendem `=` wurde von Excel beim Öffnen des
  CC-BY-Datensatzes ausgeführt. (BF-43)

**Betroffenenrechte — Feature `01`**
- Konto löschen (Art. 17), Daten als JSON mitnehmen (Art. 20), Passwort
  zurücksetzen, Einwilligung widerrufen (Art. 7 Abs. 3). Nichts davon existierte.
- Die Sackgasse war real: Seit die E-Mail-Änderung eine Bestätigung verlangt, war
  ein vergessenes Passwort der Verlust des Kontos.
- Restaurants bleiben bei einer Kontolöschung bestehen — eine Angabe darüber, ob
  ein Lokal eine Rampe hat, gehört den Menschen, die sie brauchen.

**Drosselung — sieben ungedeckelte Wege**
- Passkey-Challenge, Adressänderung, API- und Web-Vorschläge, Verwaltungsvorgänge,
  Organisations-Warteliste, offene Datenendpunkte.
- `ActionLimiter` verbraucht Kontingent erst, wenn die Handlung stattfindet: Fünf
  Tippfehler sperrten vorher eine Stunde lang aus. **Der naheliegende Umbau war
  falsch** — `consume(0)` prüft nicht, acht gültige Anmeldungen liefen durch.

**Datenqualität**
- Ein Haus ohne jede Erhebung senkte die veröffentlichte Durchschnittspunktzahl
  (5,09 → 4,67) und hob zugleich die Gemeindeabdeckung (8 → 9). Zwei Leitzahlen auf
  derselben Seite in gegenläufige Richtungen. Solche Häuser bekommen jetzt keine
  Punktzahl, sondern erscheinen als eigene Zahl. (BF-67, BF-49)
- Zweimal genehmigen erzeugte zwei Restaurants; der Snapshot-Knopf überschrieb
  Geschichte ohne Rückfrage. (BF-54, BF-47)
- Bilddateien überlebten das Löschen ihres Restaurants — fünf Waisen aus Februar
  und Juni lagen noch im Verzeichnis, öffentlich abrufbar. (BF-53)

**Verständlichkeit**
- Ein leeres Pflichtfeld endete in einem 500er, ein zu langer Küchenname ebenfalls
  — und die naheliegende Längenprüfung reichte nicht: `AsciiSlugger` macht aus 80 ×
  „ß" 160 Zeichen. (BF-51, BF-62)
- Elf Übersetzungsschlüssel standen als roher Text auf der Seite. (BF-69)
- Die Datenschutzerklärung nannte einen von drei Empfängern — Brevo, das jede
  gespeicherte E-Mail-Adresse empfängt, fehlte. (BF-65)
- Die Kriterienseite erklärte die Punktzahl nicht, während `/open` sie
  veröffentlicht. (BF-66)
- Die Ablehnungsnotiz erreichte den Einreicher nie. (BF-55)

**Bedienbarkeit**
- Der Sprachumschalter war auf Mobil unerreichbar — auf genau dem Gerät, für das
  diese Anwendung als PWA gebaut ist. (BF-72, BF-71)
- „Angemeldet bleiben" hielt für alles außer dem Profil. (BF-15)
- Verwaltungslisten luden den gesamten Bestand. (BF-40, BF-52)

### Hinzugefügt
- `App\RateLimit\ActionLimiter` und sechs neue Limiter
- `app:uploads:prune` — findet hochgeladene Dateien ohne Datenbankzeile
- `CatalogueCompletenessTest` — prüft 923 verwendete Schlüssel gegen vier Kataloge
- `LimiterCoverageTest` — prüft die Limiter-Konvention aus `CLAUDE.md`
- Vier Migrationen: `restaurant_suggestion.locale`, die beiden Maßspalten,
  `user.password_reset_token`, `restaurant.assessed_features`

### Geändert
- `POST /api/v1/restaurants` antwortet mit `submissionId` statt `id` (BF-31)
- `/api/v1/me/submissions` zeigt auch wartende Vorschläge, mit `state` (BF-32)
- Der offene Datensatz führt 22 statt 21 Spalten und erklärt sie in `fieldNotes`

**Testsuite: 474 Tests** (vorher 365).

---


### Security
- **Die Anmeldung sperrt nach fünf Fehlversuchen.** Bis dahin nahm sie beliebig viele entgegen – nachgestellt mit zwanzig Versuchen gegen das Admin-Konto: alle angenommen, danach griff das richtige Passwort sofort. Dieselben Zugangsdaten gegen `/api/v1/auth/login` waren längst ab dem sechsten Versuch mit 429 abgewiesen worden. Geschützt war also der Weg, den eine App nimmt, nicht der, den ein Browser nimmt – und dahinter steht ein Verwaltungszugang an genau einem Konto, ohne zweite Stufe und ohne Benachrichtigung bei fremder Anmeldung. Jetzt `login_throttling` mit fünf Versuchen je Kombination aus IP und Benutzername in 15 Minuten. Ein anderes Konto von derselben Adresse bleibt unberührt; in `when@test` ist der Wert bewusst ausgehebelt, weil sich Fehlversuche sonst über die Suite summieren.
- **Das Abmelden verlangt einen POST mit CSRF-Token.** Vorher genügte ein `<img src="/de/logout">` auf einer fremden Seite, um einen angemeldeten Besucher abzumelden. Der Schaden war gering, aber es war kein Schutz. Der Abmeldelink in der Kopfzeile ist deshalb jetzt ein Formular statt eines `<a href>`.
- **Die Registrierung ist gedrosselt.** Bis dahin liessen sich beliebig viele Konten in Folge anlegen – nachgestellt: zwölf Versuche, zwölf Konten, zwölf Bestätigungsmails, keine Sperre. Jede Anlage verbraucht Kontingent der Brevo-Quota, die Rechnung des Angreifers zahlt also der Betreiber. Neuer Limiter `registration` (5 je IP und Stunde) und `verify_resend` (3), Muster wie bei der Partner-Warteliste. Bemerkenswert an dem Fund: Die **API**-Anmeldung war längst limitiert (5/Minute, ab dem sechsten Versuch 429) – ausgerechnet der Weg, den ein Browser nimmt, war es nicht.
- **Die Haltestellen-Anzeige behauptet keine Barrierefreiheit mehr, die sie nie geprüft hat.** Auf der Restaurant-Detailseite stand „Keine barrierefreien Haltestellen in der Nähe gefunden" und im Admin-Formular „automatische Suche nach barrierefreien Haltestellen" – die HAFAS-Abfrage kennt jedoch **kein einziges** Barrierefreiheitsmerkmal, sie fragt nach Haltestellen im Umkreis. Für eine Plattform, deren Zweck verlässliche Barrierefreiheitsangaben sind, ist eine erfundene Barrierefreiheitsaussage der schwerste Fehler, den ein Text machen kann: Wer im Rollstuhl sitzt und das liest, plant nicht hin. Die Texte sagen jetzt in allen vier Sprachen, was tatsächlich geprüft wurde („Keine Haltestelle im Umkreis von 1000 Metern gefunden"), und der Block nennt seine Quelle samt dem Hinweis, dass sie nichts über die Barrierefreiheit der Haltestellen sagt.
- **Der Suchradius steht auf 1000 statt 500 Metern.** Bei 500 m lieferte die Schnittstelle für 8 von 11 Restaurants null Haltestellen – an denselben Koordinaten sind es bei 2000 m sieben; die Schnittstelle funktionierte also einwandfrei, der Radius war zu klein. Nach der Umstellung zeigen 8 von 11 Restaurants Haltestellen. 1000 m entsprechen etwa zwölf Minuten Fußweg.

- **Ein hängender Nahverkehrs-Dienst blockiert die Detailseite nicht mehr.** Der Aufruf trug keine Zeitvorgabe, also griff der PHP-Standard `default_socket_timeout` – gemessen 60 Sekunden. Nachgestellt gegen einen Server, der schweigt: ohne Vorgabe nach 30 Sekunden noch immer keine Antwort, mit `'timeout' => 3` Abbruch nach exakt 3,0 Sekunden. Der `catch` fing bisher den Ausfall, nicht die Verzögerung.
- **Der API-Schlüssel des Nahverkehrs steht nicht mehr im eigenen Protokoll.** HAFAS sieht die Übergabe als Query-Parameter `accessId` vor, und Symfonys Exception-Meldung enthält die vollständige URL – der Service reichte sie unverändert ins Log weiter. In `var/log/dev.log` standen 30 Zeilen mit dem Schlüssel im Klartext (`http_client`-Kanal) und 7 weitere aus dem Anwendungscode. Der Service protokolliert jetzt Klasse und Statuscode; für den zweiten Weg, den kein Anwendungscode in der Hand hat, gibt es `SecretMaskingProcessor`. Er maskiert `accessId`, `token`, `apikey` und Verwandte in allen Kanälen und nimmt damit auch den Bestätigungstoken aus den `Matched route`-Zeilen mit.

- **Die REST-API umgeht die Moderation nicht mehr.** `POST /api/v1/restaurants` legte bisher sofort ein öffentliches Restaurant an – nachgestellt: Der Eintrag stand augenblicklich in der Restaurantliste, auf einer Detailseite, in der öffentlichen API-Liste und im Datensatz unter CC BY 4.0. Zwei Aufrufe drückten die auf `/open` veröffentlichte Verifizierungsquote von 27,3 auf 23,1 Prozent und die Durchschnittspunktzahl von 5,09 auf 4,31. Ein Datensatz, den jeder mit einem Konto beschreiben kann, ist als Beleg gegenüber Fördergebern wertlos – und man sieht ihm das nicht an; die Snapshot-Historie friert den verfälschten Stand zusätzlich dauerhaft ein. Der Endpunkt legt jetzt einen **Vorschlag** an, denselben, den auch der Web-Wizard erzeugt, und antwortet mit **202** statt 201: Die Anfrage ist angenommen, die Ressource entsteht mit der Freigabe. Der Antwortvertrag durfte sich ändern, weil es die iOS-App noch nicht gibt (`docs/prd.md`: „belegt, dass die iOS-App bereits Geld kostet, bevor sie existiert").
- **Die Küchen-Auswahl der Website lässt sich nicht mehr von außen beschreiben.** `cuisines` rief bisher `findOrCreateByName()` – jeder Tippfehler legte dauerhaft einen neuen Typ an, und der erschien im öffentlichen Filter. Nachgestellt mit „Pizzza" und „JETZT BEI UNS BESTELLEN 0900-123456", 50 Stück in einer einzigen Anfrage. Die Namen sind jetzt Freitext am Vorschlag; welcher echte Typ gemeint ist, entscheidet der Admin bei der Freigabe. Über 80 Zeichen antwortet der Server mit 422 statt mit einem 500er aus der Datenbankschicht – jeder davon erzeugte in Produktion einen Sentry-Bericht.
- **Die API-Registrierung ist gedrosselt.** Sie fiel unter das anonyme Limit von 100 Anfragen je Minute – nachgestellt: elf Hinweis-Mails an eine **fremde** Adresse in wenigen Sekunden, anonym, ohne Konto. Das ist ein Mail-Versender auf beliebige Postfächer, gedeckt von der eigenen Absenderdomäne. Bitter daran: Die Anti-Enumeration ist sauber gebaut (wortgleiche Antwort, kein Timing-Unterschied) – und sie ist der Grund, warum überhaupt eine Mail an eine fremde Adresse geht. Ohne Deckel hat der Schutz den Vektor erst geschaffen. Neuer Limiter `api_register` mit 5 je Stunde, denselben Werten wie der Web-Weg seit gestern.
- **Nicht beantwortete Merkmale gelten in der API nicht mehr als „nein".** Ein nicht übermitteltes Barrierefreiheitsmerkmal wurde auf `false` gesetzt – daraus wurde eine Aussage, die niemand getroffen hatte. Der Vorschlag kennt „ja", „nein" und „weiß nicht"; die API ordnet jetzt entsprechend zu.

- **Eine geänderte E-Mail-Adresse wird erst nach Bestätigung wirksam.** Bisher wechselte sie im selben Request – nachgestellt: Adresse auf eine fremde umgeschrieben, 302, und in der Datenbank stand die neue Adresse mit `is_verified = 1` und ohne Token. Der Bestätigungsstatus galt damit für eine Adresse, die nie bestätigt wurde. Wer eine Sitzung kaperte, schrieb das Konto in einem Schritt dauerhaft auf sich um, und der rechtmässige Inhaber hatte **keinen Rückweg** – ein Passwort-Zurücksetzen gibt es im Projekt bis heute nicht. Jetzt wandert die neue Adresse in `pending_email` mit eigenem Token und 24-Stunden-Frist; erst der Klick auf den Bestätigungslink tauscht sie. Es gehen **zwei** Mails raus, und die wichtigere ist die an die **bisherige** Adresse: Wer übernimmt, sitzt im neuen Postfach und liest die Bestätigung ohnehin mit – nur die Warnung erreicht den Inhaber. Der offene Vorgang steht sichtbar im Profil und lässt sich dort abbrechen.
- **Die Passwortänderung im Profil ist gedrosselt.** Acht Versuche mit falschem aktuellem Passwort wurden zuvor alle angenommen. Neuer Limiter `password_change`, fünf Versuche je 15 Minuten – gezählt **am Konto**, nicht an der IP: Der Angriff setzt eine gekaperte Sitzung voraus, und dort wechselt die IP mühelos, das Konto nicht.
- **Der Bestätigungstoken landet auf Production nicht mehr im Fehlerprotokoll.** In `prod` schreibt der `fingers_crossed`-Handler bei jedem Fehler seinen gesamten Puffer nach `php://stderr` – darunter die `doctrine`-DEBUG-Zeilen mit allen gebundenen Parametern, also auch Token und Passwort-Hashes. Der Kanal ist jetzt ausgeschlossen (`channels: ["!deprecation", "!doctrine"]`). Der Preis ist, dass im Fehlerfall die SQL-Historie fehlt; das wiegt leichter als ein Anmelde-Äquivalent im Hoster-Log. Der `dev`-Handler bleibt bewusst unverändert – ein Entwicklungslog ohne SQL wäre für die Fehlersuche wertlos, und es verlässt den Rechner nicht.

### Fixed
- **Die Startseite zeigt wieder sechs Restaurants statt einem.** `findTopRated(6)` lieferte genau **ein** Haus – nachgemessen: `(20)` ergab 2, `(100)` ergab 7. Die Ursache ist ein bekanntes Doctrine-Muster: Die beiden `addSelect()`-Joins holen Öffnungszeiten und Küchen mit (gegen N+1), und dadurch erzeugt jedes Restaurant so viele SQL-Zeilen, wie es Kombinationen aus beidem hat – beim bestbewerteten Haus 14. `setMaxResults()` begrenzt aber die Zeilen, nicht die Entities, und das `LIMIT 6` war innerhalb des ersten Datensatzes verbraucht. Behoben mit `new Paginator($qb->getQuery(), true)`, wie es `findPaginated()` acht Zeilen tiefer schon immer tat. Aufgefallen war es niemandem, weil ein Raster mit einer Karte wie eine Gestaltungsentscheidung aussieht – und weil der vorhandene Test `assertLessThanOrEqual(6, …)` prüfte, was auch bei einem Ergebnis erfüllt ist. Er prüft jetzt `assertCount`.
- **Die Fehlerantworten des JWT-Bundles folgen dem Format der übrigen API.** Bei falschem Passwort und bei abgelaufenem Token kam ein flaches `{"code":401,"message":…}` statt `{"error":{"code","message"}}` – ausgerechnet in den beiden häufigsten Fehlerfällen eines Mobil-Clients. Ein Client, der einheitlich `error.code` liest, bekam dort `undefined`. Ursache: Das Bundle wirft keine Exception, sondern schreibt die Antwort selbst; `ApiExceptionSubscriber` kam nie zum Zug. Neuer `ApiAuthenticationFailureSubscriber` für die vier Fälle des Bundles, Meldungen übersetzt statt englisch.
- **404-Antworten der API verraten keine internen Klassennamen mehr.** Dort stand wörtlich `"App\Entity\Restaurant" object not found by "Symfony\Bridge\Doctrine\ArgumentResolver\EntityValueResolver".` – und zwar nicht als Debug-Zugabe, sondern auch in Produktion. Das nennt ORM, Entity und Framework-Aufbau, und einem Client ist damit nicht geholfen. Jetzt „Nicht gefunden."; die Meldungen anderer Ausnahmen bleiben erhalten, weil sie meist aus eigenem Code stammen.
- **Der Asset-Build hängt nicht mehr davon ab, worüber jemand schreibt.** Tailwind v4 scannt ohne `source(none)` das gesamte Projekt und kann eine als Prosa zitierte Klassenkette nicht von einer verwendeten unterscheiden. `assets/styles/app.css` schloss bisher nur `public/` aus; damit veränderte jede Dokumentation den CSS-Hash und blockte `verify-assets` im Deploy, ohne dass eine Zeile Oberfläche angefasst wurde. Bestand seit dem Merge von `docs/` am 21. August unbemerkt. Einzelne Verzeichnisse auszuschließen greift zu kurz – auch CHANGELOG, README und CLAUDE.md nennen Klassennamen. Stattdessen jetzt eine **Positivliste**: `source(none)` plus die drei Orte, an denen Klassen wirklich entstehen – `templates/` (Twig), `assets/` (Stimulus setzt sie zur Laufzeit über `classList`) und `src/` (FormTypes tragen sie im `attr`-Array, etwa `OpeningHourType`). Das CSS schrumpft dadurch um 1.321 Bytes; geprüft wurde, dass alle sechs entfallenen Utilities in keiner echten Quelle vorkommen. Nebeneffekt mit Wert: Über die Oberfläche lässt sich ab jetzt schreiben, ohne den Build zu verändern.
- **„Bestätigungsmail erneut senden" funktioniert wieder.** Die Route `/verify/resend` war seit ihrer Einführung unerreichbar: `/verify/{token}` steht in derselben Klasse davor und fängt ohne Requirement jeden Ein-Segment-Pfad ab – auch den mit `token = "resend"`. Wer auf den Link klickte, bekam „Ungültiger Bestätigungslink." und landete auf der Startseite. Das war eine **Sackgasse**: Bei abgelaufenem Token lautet die Meldung „Bitte fordere einen neuen an", und genau dieser Weg war der kaputte – ohne Passwort-Zurücksetzen und ohne Kontolöschung blieb das Konto unrettbar. Behoben durch `requirements: ['token' => '[a-f0-9]{64}']`, was unabhängig von der Deklarationsreihenfolge wirkt. Nachweis: `php bin/console router:match /de/verify/resend`.
- **Bei ungleichen Passwörtern erscheint eine Meldung statt eines rohen Übersetzungsschlüssels.** Im Formular stand wörtlich `form.password_mismatch`. Ursache: `RepeatedType::invalid_message` wird in der Domäne **`validators`** aufgelöst, der Schlüssel lag aber nur in `messages.*.yaml`. Jetzt in allen vier `validators.*.yaml`. Betraf zwei Stellen – Registrierung **und** Passwortwechsel im Profil.
- **Die Meldung bei bereits vergebener E-Mail-Adresse folgt der Sprache.** `#[UniqueEntity]` trug deutschen Klartext direkt in der Entity, der damit auch auf der französischen, englischen und luxemburgischen Fassung erschien. Ersetzt durch `user.email_unique` – der Schlüssel lag in allen vier Dateien bereits fertig übersetzt und wurde nur nicht benutzt.
- **Bestätigungsmails behalten ihre Sprache auch bei asynchronem Versand.** Wer sich über `/fr/` registrierte, bekam einen französischen Betreff und einen luxemburgischen Inhalt: Der Betreff wird im Controller übersetzt, das Twig-Template dagegen erst beim Versand – im Messenger-Worker also ohne Request-Sprache, wo `default_locale` greift. Behoben mit `->locale($request->getLocale())`; Symfonys `BodyRenderer` wertet das aus und rendert über den `LocaleSwitcher`. Auf Production trat es nicht auf (dort läuft `sync://`), es hätte aber gekippt, sobald ein Worker eingeführt wird – wie er für die Monats-Snapshots vorgesehen ist.

### Added
- **Der Bestätigungsweg hat Tests.** `EmailVerificationControllerTest` deckt gültigen, abgelaufenen, unbekannten und bereits eingelösten Token ab sowie den Fall ohne Ablaufzeitpunkt – dieser Teil des Registrierungsablaufs war zuvor vollständig ungetestet. Dazu vier Fälle in `RegistrationControllerTest`.
- **`docs/` und `features/`: das Projekt ist für die SDD-Kette erfasst.** Neu ist `docs/app-shell.md` (Layout-Hierarchie, Navigation, Admin-Shell, Druckansicht) neben den vorhandenen Referenzen. Unter `features/` liegen 26 Bestandsfeatures mit rückwirkend geschriebener `spec.md` und `design.md`, dazu `fehlbestand-uebersicht.md` mit zehn Mustern, die mehrere Features gleichzeitig betreffen. ⚠️ Die Spezifikationen der `B`-Features sind **Rekonstruktionen** – sie beschreiben, was der Code tut, nicht was er tun sollte; Kriterien mit ⚠ markieren fragwürdiges Verhalten, das bewusst als Kriterium aufgenommen wurde.

### Changed
- **Die Info-Seite heißt jetzt „Über Endlech" statt „Über uns"** – in allen vier Sprachen (`About Endlech`, `À propos d'Endlech`, `Iwwer Endlech`), im Header-Link, im Seitentitel und in der Überschrift. „Über uns" ist ein austauschbarer Standardtitel; der Markenname sagt schon in der Navigation, worum es auf der Seite geht, und taucht damit auch im Browser-Tab und in Suchergebnissen auf. URL und Routenname (`/about`, `app_about`) bleiben unverändert – keine toten Links, kein Redirect nötig.
- **Die mobile Bottom-Navigation behält das kurze Label** und nutzt dafür den neuen Schlüssel `nav.about_short`. Sie hat bei vier Rasterspalten und `text-xs` nur rund 80 px pro Eintrag; „Über Endlech" bräche dort um und ließe die vier Einträge unterschiedlich hoch erscheinen. Der zweite Schlüssel ist deshalb Absicht und **kein Duplikat, das sich zusammenfassen lässt** – Header und Bottom-Nav haben schlicht verschiedene Platzbudgets.

### Added
- **Anmeldung mit Passkey (WebAuthn).** Endlech.lu richtet sich an Menschen mit Behinderungen – und verlangte bislang genau eine Sache, die für viele davon die grösste Hürde ist: ein Passwort abzutippen. Wer will, hinterlegt jetzt im Profil einen Passkey und meldet sich danach mit Face ID, Touch ID oder der Geräte-PIN an. Auf der Login-Seite steht dafür **ein Knopf, der keine E-Mail-Eingabe verlangt**: Der Browser zeigt die passenden Konten selbst an (`resident_key: required`, leere `allowCredentials`). Das Passwort bleibt vollwertig daneben bestehen – dieses Feature nimmt niemandem etwas weg und sperrt niemanden aus. Neue Entity `WebauthnCredential`, neues Feld `User::$webauthnHandle`, Migration `Version20260821000000`, Bundle `web-auth/webauthn-symfony-bundle` ^5.3.5.
- **Der Passkey-Login läuft als gewöhnlicher Formular-Login, nicht als JSON-Schnittstelle.** Das Bundle bietet dafür einen fertigen `webauthn:`-Schlüssel für die Firewall; der ist für Version 6.0 abgekündigt und nimmt die Assertion ausschliesslich als JSON-Body entgegen. Stattdessen ein eigener `App\Security\PasskeyAuthenticator`, der sie aus einem Formularfeld `_assertion` liest. Damit läuft der Passkey durch dieselbe Mechanik wie das Passwort – gleicher `check_path`, gleiche Weiterleitung, gleiches „Angemeldet bleiben" – statt einen zweiten, andersartigen Anmeldeweg danebenzustellen. Welcher der beiden greift, entscheidet allein das gefüllte Feld (Priorität 0 gegenüber −30 bei `form_login`). ⚠️ Zwei Authenticator in einer Firewall verlangen ein explizites `entry_point` – hier `form_login`, weil nur das den Weg zur Anmeldeseite kennt.
- **Passkey-Verwaltung im Profil, ohne JavaScript-Zwang.** Anlegen braucht zwingend ein Skript – ohne `navigator.credentials` gibt es keinen Passkey. Umbenennen und Entfernen sind dagegen gewöhnliche Formulare: Wer einen Schlüssel loswerden will, soll nicht davon abhängen, ob JavaScript geladen hat. Der Anzeigename kommt beim Anlegen vom Server aus dem User-Agent („iPhone", „Mac", „Android") und lässt sich danach ändern; bewusst Produktnamen statt Übersetzungsschlüssel, weil der Name einmal festgeschrieben wird und sonst für immer die Sprache jenes einen Moments trüge. Die Besitzprüfung steht **vor** der CSRF-Prüfung: Wer nicht Eigentümer ist, hat dort unabhängig von jedem Token nichts verloren, und die Antwort sagt das dann auch (403) statt einer Weiterleitung, die nach einem abgelaufenen Formular aussieht.
- **`User::$webauthnHandle` statt der Datenbank-ID als WebAuthn-Kennung.** Der Handle liegt dauerhaft auf dem Gerät des Nutzers und wandert bei jeder Anmeldung mit; eine fortlaufende Zahl gäbe dort die Nutzerzahl preis und verknüpfte ein fremdverwahrtes Datum fest mit der internen Identität. Er entsteht erst beim ersten Passkey (nullable, keine Datenmigration für Bestandskonten) und misst 32 Zeichen aus 16 Zufallsbytes – ⚠️ **nicht** 32 Bytes wie `generateVerificationToken()`: `PublicKeyCredentialUserEntity` lässt für die Kennung höchstens 64 Byte zu, ein Hex-Wert aus 32 Bytes läge exakt auf der Grenze.
- **Neue Konten lassen sich weiterhin nur mit E-Mail und Passwort anlegen.** `App\Security\WebauthnUserEntityRepository` implementiert bewusst **nicht** `CanRegisterUserEntity`/`CanGenerateUserEntity`; ohne diese Schnittstellen lehnt das Bundle jeden Versuch strukturell ab, über einen Passkey ein Konto zu erzeugen. Das ist verlässlicher als eine Konfigurationszeile, die man beim nächsten Umbau übersieht. Die E-Mail-Verifikation bleibt damit der einzige Weg ins System.
- **Bundle-Konfiguration bewusst schmal gehalten**, weil `phpunit.dist.xml` bei der ersten Deprecation die ganze Suite scheitern lässt. Nicht gesetzt sind deshalb `rp.name` (seit 5.3.0 abgekündigt), `rp.icon` (seit 5.1.0 ohne Wirkung), `secured_rp_ids` (seit 5.2.0) und `options_storage` je Firewall (seit 5.2.0). `allowed_origins` bleibt auf Production ebenfalls leer – nicht wegen einer Deprecation, sondern weil dann der Weg der Spezifikation greift (HTTPS plus Abgleich gegen die rp id); eine gefüllte Liste erzwingt exakten Abgleich inklusive Port und normalisiert Einträge ohne Schema still auf `https://…:443`. Für die lokale Entwicklung erlaubt ein `when@dev`-Block `http://localhost:8000` – sonst bräuchte jede lokale Anmeldung eine eigene Zertifizierungsstelle im System-Schlüsselbund.
- **Das Bundle bringt sein Doctrine-Mapping selbst mit.** `WebauthnCredential` erbt von `Webauthn\CredentialRecord`, dessen Felder über eine mapped-superclass des Bundles und fünf automatisch registrierte DBAL-Typen (`base64`, `aaguid`, `trust_path` …) abgebildet werden. Der zunächst erwogene Weg – eigene Entity mit selbst serialisierter JSON-Spalte – hätte denselben Zweck erfüllt, aber eine Serialisierung von Hand hinzugefügt, die hier niemand braucht. ⚠️ Die geerbten Spalten landen als LONGTEXT; die bei jeder Anmeldung durchsuchte Kennung ist deshalb nur mit Längenangabe indizierbar (`public_key_credential_id(100)`). ⚠️ Und weil Doctrine gebundene Parameter anhand des Feld-Mappings selbst kodiert, muss `findOneByCredentialId()` die **rohe** Kennung übergeben – eine Kodierung von Hand käme doppelt an und fände nie etwas.
- **`saveCredentialRecord()` legt an oder schreibt fort.** Das Bundle ruft die Methode bei *jeder* Anmeldung, weil der Signaturzähler mitwandert und der Klon-Schutz daran hängt. Ein reines Anlegen erzeugte Duplikate und liefe der Prüfung zuwider. Ein Datensatz, dessen Handle zu keinem Konto gehört, wird gar nicht erst gespeichert: Er wäre weder benutzbar noch im Profil zu entfernen.
- **Frontend über `@web-auth/webauthn-stimulus`** (npm, 5.3.5) statt Eigenbau – das Paket bringt base64url-Behandlung und die WebAuthn-Fehlerklassen mit. ⚠️ Es gehört **nicht** in `assets/controllers.json`: Das StimulusBundle löst jeden Eintrag dort gegen ein gleichnamiges Composer-Paket auf, das es nur auf npm gibt. Registriert wird es daher in `assets/stimulus_bootstrap.ts`. Daneben ein schlanker eigener Controller `passkey_ui_controller.ts` für das, was das Fremdpaket nicht liefern kann: übersetzte Meldungen aus dessen Events, einen Ladezustand, und einen Knopf, der überhaupt erst erscheint, wenn der Browser Passkeys beherrscht. Ein Abbruch durch den Nutzer (`ERROR_CEREMONY_ABORTED`) erzeugt bewusst **keine** Meldung – das ist eine Entscheidung, kein Fehler.
- **Der Passkey-Knopf hat ein eigenes `<form>`, und zwar aus einem harten Grund:** Der `AuthenticationController` ruft vor dem Start `form.checkValidity()`. Im Passwort-Formular sind E-Mail und Passwort `required`, ein Klick liefe dort gegen die Browser-Validierung und käme nie bis zum Authenticator. Das Passkey-Formular steht **zuerst im Markup** – die Tab-Reihenfolge muss der sichtbaren folgen, ein Umsortieren per CSS wäre für dieses Projekt das falsche Werkzeug. Der bestehende `SecurityControllerTest` greift deshalb nicht mehr `filter('form')`, sondern die vorhandene Hilfsmethode `formWithField()`.
- **Open-Startup-Seite (`/open`):** Endlech.lu verlangt von Restaurants, ihre Barrierefreiheit offenzulegen – dieselbe Offenheit gilt jetzt für das Projekt selbst. Die Seite zeigt drei Blöcke: **Plattform** (Anzahl Lokale, vom Team geprüfte Einträge, Abdeckung nach Gemeinde und Kanton, Verteilung der Barrierefreiheits-Punktzahlen von 0 bis 10), **Wirkung** (dokumentierte stufenlose Eingänge, barrierefreie WCs, Türbreiten, Tischabstände, gelieferte Inclusion Boxes) und **Finanzen** (Kosten und Einnahmen, ausschließlich nach Kategorie summiert). Plattform- und Wirkungszahlen kommen live aus der Datenbank und liegen eine Stunde in einem eigenen Cache-Pool `cache.open_stats`; die Finanzzahlen pflegt das Team im Admin. `/open` ist zusätzlich sprachfrei erreichbar – das ist die URL, die in Fördermails und Vorträgen steht und nicht an einer Sprachwahl scheitern darf.
- **Einnahmen bleiben bis zum ersten vollständigen Quartal zurückgehalten.** Eine Einnahmenzeile nahe null schreckt potenzielle Partner ab, statt Vertrauen zu schaffen; erst ein abgeschlossenes Kalenderquartal zeigt, ob eine Zahl Signal oder Zufall ist. Die Sperre ist **strukturell und nicht kosmetisch**: Die Beträge stehen gar nicht erst im Ergebnis-Array, sonst wären sie trotz ausgeblendeter Anzeige über `/open.json` abrufbar. Kosten und Wirkung werden von Anfang an veröffentlicht. Auf der Seite steht sichtbar, warum die Spalte leer ist und ab wann sie gefüllt wird.
- **Neue Entity `FinanceEntry` mit Admin-CRUD unter `/admin/finanzen`** – kein Buchhaltungs-Anschluss, ein Eintrag pro Beleg. Enums `FinanceType` (income/expense) und `FinanceCategory` (Hosting, E-Mail-Versand, Apple Developer, Domain, Material Inclusion Box, Mitgliedschaft, öffentliche Förderung, Sponsoring, Spende, je ein Sammelposten). Es gibt **kein Feld für die Richtung**: Sie hängt an der Kategorie und wird von `setCategory()` gesetzt – zwei Felder für dieselbe Aussage wären eine Gelegenheit, sie widersprüchlich zu füllen. Beträge sind immer positiv; ein negativer Wert würde die veröffentlichte Summe doppelt invertieren und wird mit 422 abgelehnt. Felder für Vertragspartner, Restaurant oder Rechnungsnummer existieren bewusst nicht – was nicht erfasst ist, kann nicht versehentlich veröffentlicht werden. Jede Änderung im Admin wirft den Kennzahlen-Cache weg, damit die öffentliche Seite nicht eine Stunde lang den alten Betrag zeigt.
- **Neue Entity `MetricSnapshot` und Befehl `app:metrics:snapshot`** für die Verlaufsanzeige. Ein aus den heutigen Daten zurückgerechneter Verlauf würde sich rückwirkend ändern, sobald jemand einen Eintrag bearbeitet – als Beleg gegenüber einem Ministerium wäre er damit wertlos. Der Snapshot friert deshalb Monatswerte ein: typisierte Spalten für die Grafiken, dazu die vollständige Momentaufnahme als JSON, damit eine spätere Kennzahl nicht rückwirkend fehlt. Ein Unique-Index auf dem Monat macht den Lauf auf Datenbankebene idempotent. `App\Schedule` deklariert den Lauf am Ersten jedes Monats um 03:15 – ⚠️ Symfony Scheduler braucht dafür einen `messenger:consume`-Worker, den Production nicht hat; dort läuft der Befehl **per Cron** (Eintrag im README). Zusätzlich lässt sich ein Snapshot im Admin von Hand auslösen, weil eine ausgefallene Historie sonst unbemerkt bliebe und sich nicht nachträglich erzeugen lässt.
- **Offener Datensatz unter CC BY 4.0:** `/open/dataset.csv` und `/open/dataset.json` liefern die vollständigen Barrierefreiheits-Daten aller Lokale inklusive Punktzahl, Gemeinde und Kanton; `/open.json` spiegelt die Kennzahlen der Seite maschinenlesbar. Alle drei sind **sprachfrei geroutet** (eigener `open_data`-Block in `config/routes.yaml`) – ein `/de/open.json` würde zitierte URLs auf vier Varianten verteilen. E-Mail-Adressen und Telefonnummern sind bewusst **nicht** enthalten: Ein Sammelabzug davon wäre eine Adressliste, kein Barrierefreiheits-Datensatz. Die Antworten sind eine Stunde öffentlich cachebar; dafür nötig war der `NO_AUTO_CACHE_CONTROL`-Marker, weil Symfonys Session-Listener sonst auf `private, must-revalidate` umstellt, sobald irgendwo im Request eine Session angefasst wurde.
- **Gemeinde- und Kantonszuordnung (`App\Open\CantonResolver`):** `Restaurant::$city` ist ein Freitextfeld – dort steht mal die Gemeinde („Strassen"), mal eine Ortschaft darin („Belval"), mal ein Stadtteil („Bonnevoie"), mal die luxemburgische Schreibweise („Lëtzebuerg"). Eine reine `GROUP BY city`-Auswertung zählte diese Fälle als verschiedene Orte und machte jede Abdeckungsquote falsch. Der Resolver kennt alle **100 Gemeinden in 12 Kantonen** (Stand nach den Fusionen vom 1. Januar 2024) samt Alias-Tabelle für Stadtteile und gebräuchliche Schreibweisen. Er rät **nicht**: Ein unbekannter Wert bleibt unzugeordnet und wird auf der Seite als solcher ausgewiesen – eine erfundene Zuordnung wäre auf einer Transparenzseite schlimmer als eine sichtbare Lücke. Gemeinde- und Alias-Index sind getrennt, damit beim Zerlegen zusammengesetzter Angaben („Rue de la Gare, Strassen") nur echte Gemeindenamen greifen.
- **Neue Restaurant-Felder `doorWidthCm` und `tableSpacingCm`** (Maße in Zentimetern, DIN-18040-Schwelle 90 cm), damit „Tür breit genug" nicht Auslegungssache bleibt. Beide sind nullable **ohne Default**: `null` heißt „nicht ausgemessen", nicht „zu schmal" – ein 0-Default hätte jedes nie erfasste Haus als Negativbefund in die veröffentlichte Zahl geschrieben. Die Detailseite zeigt entsprechend drei Zustände (erfüllt, zu eng, nicht ausgemessen). In der iOS-API stehen die Werte in einem eigenen Block `measurements` statt in `accessibility`: Dort ist jeder Wert ein Boolean, und ein `null` in diesem Vertrag wäre ein Kompatibilitätsbruch. Migration `Version20260820200000`.
- **Barrierefreiheits-Punktzahl (`App\Open\AccessibilityScore`)** – acht gleichgewichtete Merkmale, Anteil mal zehn. Eine Gewichtung („Rampe zählt dreifach") wäre fachlich vertretbar, auf einer Transparenzseite aber nicht mehr nachrechenbar. Nicht erfasste Maße zählen als nicht erfüllt: Der Wert misst *dokumentierte* Barrierefreiheit und rundet nicht heimlich zugunsten schlecht gepflegter Einträge.
- **Gestalterischer Feinschliff der Open-Startup-Seite.** Die Seite stand funktional, sprach aber nicht die Sprache der übrigen Außenseiten. Sie hat jetzt ein Hero-Band im Cyan-Purple-Verlauf mit der Zahl der erfassten Restaurants als **Leitzahl** (wer drei Sekunden hinsieht, soll sie mitnehmen – vier gleichrangige Kacheln hinterlassen nichts), abwechselnde Sektionsflächen, Emoji in farbigen Kacheln, `motion-safe:transition`, Fokus-Outlines und 48-px-Tap-Targets wie auf der Partner- und Organisationsseite. Die Kennzahlen zeigen zusätzlich die **Veränderung gegenüber dem zuletzt festgehaltenen Monat** – Bezugspunkt ist bewusst der Snapshot und nicht „vor 30 Tagen", weil nur er ein nachprüfbarer Stand ist; ohne Snapshot erscheint gar keine Veränderung, statt eine gegen einen unbekannten Ausgangswert zu erfinden.
- **Punkteverteilung als Histogramm statt elf gestapelter Querbalken.** Die Punktzahl ist eine geordnete Skala; erst nebeneinander liest man die Form der Verteilung – wo der Gipfel liegt, wo die Ausreißer sitzen – in einem Blick. Die Sektion wurde dabei rund 250 px kürzer. Beschriftet werden nur die höchsten Säulen, und bei mehreren gleich hohen alle: Eine davon herauszugreifen suggerierte, sie sei die höchste.
- **Farbkorrektur mit gemessenem Ergebnis.** Die frühere Ampel in der Verteilung (grün ≥ 8, cyan ≥ 5, bernstein darunter) kodierte die Balkenlänge ein zweites Mal als Farbe und verbrauchte damit den einzigen freien Kanal für Information, die schon dastand; bernstein lag zudem bei **1,49:1 Kontrast** und außerhalb des zulässigen Helligkeitsbands. Jetzt eine Farbe für die Serie, die Position trägt die Ordnung. In der Finanzsektion sind die **Ausgaben von Bernstein auf Cyan** gewechselt: Bernstein ist eine Warnfarbe, und „Hosting" in Warnorange liest sich wie ein Problem – dabei sind Betriebskosten genau das, was die Seite rechtfertigen soll. Einnahmen stehen in Purple; das Paar ist geprüft (ΔE 26,4 normal / 13,6 Deuteranopie, beide über 3:1 gegen Weiß).
- **Verlaufsgrafiken repariert.** `preserveAspectRatio="none"` staucht das Koordinatensystem – die Punkte rendern dadurch als Ellipsen. Sie sind entfallen; der aktuelle Wert steht stattdessen als Zahl über der Grafik, die Linie liegt jetzt bei den spezifizierten 2 px (`vector-effect="non-scaling-stroke"`). Aus den beiden Verlaufskarten sind damit Kennzahl-Kacheln mit Verlaufslinie geworden: Beschriftung, aktueller Wert, Veränderung, Linie.
- **Druckansicht.** Der erste Anwendungsfall der Seite ist ein Fördergespräch, und dafür wird sie als PDF gespeichert. Header, Footer, Bottom-Navigation und Cookie-Banner tragen jetzt `print:hidden` (im Basis-Layout, gilt also für jede Seite), Verlaufsbänder drucken ohne Farbfläche und mit dunklem Text – sonst bliebe weiße Schrift auf weißem Papier –, zugeklappte `<details>` öffnen sich, weil die Tabellenansicht auf Papier die einzige Fassung mit allen Werten ist, und Diagramme werden nicht über eine Seitengrenze zerrissen. Balkenfarben bleiben erhalten (`print-color-adjust: exact`), weil sie hier die Daten sind und nicht Dekoration.
- **Zahlen locale-korrekt.** Anteile und Punktzahlen liefen über eine fest deutsche Formatierung und standen dadurch in der englischen Fassung als „27,3 %" statt „27.3 %". Sie laufen jetzt über `format_number` (`twig/intl-extra`), passend zur bereits verwendeten `format_currency`.
- **Partner-Landing-Page & Warteliste (`/partner`):** Für das kostenpflichtige Partnerprogramm gibt es jetzt eine eigene Seite mit Wartelisten-Anmeldung per **Double-Opt-In**. Preise und Paketumfang stehen bewusst noch nicht fest – die Seite verarbeitet daher **keine Zahlung und legt keinen Account an**, sondern sammelt nur Anmeldungen; wo ein Preis stünde, steht der Hinweis, dass die Warteliste ihn zuerst erfährt. Der Integritätsblock ist ein eigener, farblich abgesetzter Abschnitt statt Kleingedrucktes: Eintrag, Barrierefreiheits-Daten, Score und Verifizierungs-Badge bleiben kostenlos, öffentlich und vollständig unabhängig von jeder Mitgliedschaft – bezahlt werden ausschließlich Beratung, Materialien und Begleitung. Neue Entity `PartnerWaitlistEntry` (Status als Backed Enum `PartnerWaitlistStatus`: pending → confirmed → contacted → converted/declined, optionale Verknüpfung mit einem bestehenden `Restaurant`, `consentAt` als DSGVO-Nachweis, `source` für spätere Attribution), Migration `Version20260820000000`. Die Anmeldung ist per Rate-Limiter auf 5 Versuche je IP und Stunde gedeckelt und durch einen Honeypot geschützt, der bei einem Treffer dieselbe Erfolgsantwort liefert wie eine echte Anmeldung – ein Validierungsfehler hätte dem Bot die Falle verraten. Admin-Bereich unter `/admin/partner-warteliste` mit Status-Filter, Datums-Sortierung, Detailansicht und Restaurant-Verknüpfung.
- **Barrierefreiheit der Partner-Seite (WCAG 2.2 AA als Abnahmekriterium):** Die Seite funktioniert **vollständig ohne JavaScript** – Turbo ist reine Verbesserung. Erster Turbo-Stream im Projekt: Bei aktivem JavaScript wird nur das Formular gegen die Erfolgsmeldung getauscht, ohne JavaScript greift der klassische Redirect samt Flash. Der Fokus springt nach einem Fehlversuch auf das erste fehlerhafte Feld – serverseitig über `autofocus` statt über ein Skript. Neues Partial `templates/partials/_form_field.html.twig` kapselt Label, Widget, Hilfetext und Fehlermeldung samt `aria-describedby`/`aria-invalid` und löst zugleich den bislang zehnfach kopierten Input-Klassenstring ab. Wichtig dabei: In Twig unterdrückt nur `false` ein Attribut – `null` hätte `aria-invalid=""` gerendert, was Screenreader als „ungültig" lesen. Das FAQ nutzt natives `<details>/<summary>` (tastaturbedienbar, ohne JavaScript, meldet seinen Zustand selbst an Screenreader) – ein handgeschriebenes `aria-expanded` steht bewusst **nicht** darin, weil es sich ohne JavaScript nicht aktualisieren ließe und nach dem ersten Klick schlicht falsch wäre. Fokus-Indikatoren sind echte `outline`-Ringe statt `box-shadow` (die im Windows-Kontrastmodus verschwinden), Kontraste nachgerechnet (Feld-Ränder ≥ 3:1, Fließtext ≥ 4,5:1), Pflichtfelder als **Text** gekennzeichnet, `prefers-reduced-motion` respektiert.
- **Organisations-Landing-Page & Warteliste (`/organisationen`):** Zweite Zielgruppe neben dem Partnerprogramm, mit drei kommerziell grundverschiedenen Typen: **Gemeinden** beauftragen eine bezahlte Erhebung ihrer Gastronomie, **Unternehmen** sponsern, **Vereine** sitzen im Beirat – dort fließt in keine Richtung Geld. Der Vereins-Zweig ist bewusst **kein Vertriebskanal**: Weder Seite noch Bestätigungsmail stellen ihn als solchen dar, weil der Beirat über die Barrierefreiheits-Kriterien und deren Gewichtung entscheidet und genau diese Unabhängigkeit sonst hinfällig wäre. Eine Seite statt drei gestapelter Landing Pages: kurzer gemeinsamer Einstieg, drei Anker-Karten, danach die drei Sektionen. Auf der Gemeinde-Sektion steht ausdrücklich, dass die **Erhebung** bezahlt wird und nicht das Ergebnis – Werte werden veröffentlicht, auch wenn sie unbequem ausfallen. Die Sponsoring-Ausschlussregel steht öffentlich auf der Seite (keine Gastronomieketten, keine Umbaufirmen, keine Lieferanten, die wir in Beratungsberichten nennen könnten). Neue Entity `OrganisationWaitlistEntry` mit Enums `OrganisationType`, `OrganisationTimeframe`, `SponsorshipInterest`, `CollaborationInterest`; Migration `Version20260820100000`.
- **Typabhängige Validierung über Validierungsgruppen:** Die typspezifischen Felder sind alle nullable, aber nicht beliebig kombinierbar. Der Formulartyp leitet die Gruppe aus `type` ab, und die jeweils fremden Felder tragen in den anderen Gruppen ein `IsNull`- bzw. `Count(max: 0)`-Constraint. Zusätzlich baut ein `PRE_SUBMIT`-Listener nur die Felder des übermittelten Typs auf – ein manipulierter Request, der einer Gemeinde Sponsoring-Interessen unterschiebt, wird dadurch mit 422 abgelehnt statt still gespeichert. Beim Rendern werden hingegen **alle** Blöcke aufgebaut: Nur so ist die Seite ohne JavaScript benutzbar, denn dann sind alle drei Feldgruppen sichtbar und beschriftet. Der Stimulus-Controller blendet im Browser lediglich aus, was nicht zum gewählten Typ gehört, und sagt den Wechsel in einer Live-Region an. Der Typ-Selektor sind echte Radios in einem `<fieldset>` – damit gibt es Pfeiltasten-Navigation ohne ARIA-Nachbau.
- **Refactor: gemeinsame Wartelisten-Mechanik.** Token-Erzeugung, Versand der Bestätigungsmail, Token-Einlösung und die interne Team-Meldung liegen jetzt in `App\Waitlist\WaitlistConfirmationService`, den sich beide Wartelisten teilen; Erfolgsmeldung und Bestätigungsseite sind geteilte Twig-Partials. Beide Entities implementieren `WaitlistEntryInterface`. Der Partner-Flow verhält sich unverändert – abgesichert durch die 20 bestehenden Tests, die vor und nach dem Umbau grün sind. Der Admin ist zu einer **kombinierten Ansicht** unter `/admin/warteliste` zusammengelegt (Filter nach Quelle, Organisationstyp und Status, Datums-Sortierung), die Status-Enums sind zu einem gemeinsamen `WaitlistStatus` mit dem zusätzlichen Wert `qualified` verschmolzen.
- **Verzeichnis `docs/` mit Datenmodell-, Design-System- und PRD-Referenz.** Das Projektwissen lag bisher ausschließlich in `CLAUDE.md` (83 KB Arbeitsanweisung für KI-Assistenten, chronologisch nach Issues gewachsen), im `CHANGELOG` und im Code selbst. Wer wissen wollte, welche Spalten `restaurant` hat, musste sie aus fünf verstreuten Abschnitten zusammensuchen; ein Produktdokument gab es gar nicht, obwohl auf `/partner` und `/organisationen` bereits Wartelisten für drei kommerzielle Zielgruppen laufen. Drei neue Dateien: **`docs/data-model.md`** (alle zwölf Entities mit Spalten, Typen, Constraints und Relationen, elf Enums, zwölf Repositories, ERD als Mermaid, Migrations-Historie), **`docs/design-system.md`** (Farbrollen, Typografie-Skala, kanonische Komponenten-Klassenketten zum Kopieren, Barrierefreiheits-Regeln, Diagramm- und Druckregeln) und **`docs/prd.md`** (Vision, Zielgruppen, Produktprinzipien, Funktionsumfang, Kennzahlen, Geschäftsmodell, Roadmap, Risiken). Alle Angaben sind gegen das laufende Schema geprüft, nicht aus dem Gedächtnis geschrieben. **Keine Änderung an Code, Templates oder Assets** – reine Dokumentation.
- **Das Design-System benennt erstmals einen Kanon, wo zwei Generationen nebeneinander laufen.** Die Erfassung förderte vier Doppelungen zutage: Fokusgestaltung (57× die alte Kette `focus:ring-2 focus:ring-purple-500 focus:outline-none` gegen 38× die neue `focus:outline-2 focus:outline-offset-2`), Buttons (purple-600 ohne Mindesthöhe gegen cyan-700 mit `min-h-[48px]` und `motion-safe:`), zwei Hero-Verlaufsstufen und ein Rest v3-Syntax `bg-gradient-to-*`. Jeweils die neuere, barrierefreiere Variante ist als Kanon markiert, die ältere als „Bestand, nicht nachbauen" – mit einer Verzeichnistabelle, damit eine spätere Angleichung planbar ist, ohne dass jemand erneut zählen muss. Dabei fielen zwei offene Punkte auf, die vorher niemand benannt hatte: Es gibt **keinen Skip-Link**, und `home/`, `about/` und `community/` haben **gar keine Fokusgestaltung**.
- **Das PRD trennt Belegtes von Abgeleitetem sichtbar.** Alles ohne Kennzeichnung stammt aus Code, Templates, Übersetzungen, README oder CHANGELOG; eigene Ableitungen stehen unter „▸ Vorschlag" bzw. „▸ Hypothese". Diese Trennung ist nicht dekorativ: Für Angebote, deren Preise laut eigener Seite ausdrücklich noch nicht feststehen, würde ein Dokument mit erfundenen Zahlen genau die Glaubwürdigkeit beschädigen, die das Produkt zu seinem Kernversprechen gemacht hat. Der Risikoteil benennt entsprechend auch die unbequemste Lücke: Startseite und Kriterienseite werben mit „Bewerten" und „echten Bewertungen von echten Besuchern" – tatsächlich ist `Restaurant::$rating` ein redaktionelles Zahlenfeld im Admin-Formular, eine Review-Entity existiert nicht.
- **Map:** Kartenansicht der Locations. *(geplant)*

---

## [2026.08.09] – Dreiwertige Vorschlags-Antworten & Fehler-Tracking

### Changed
- **Restaurant vorschlagen – „Weiß nicht" ist jetzt eine eigene Antwort:** Barrierefreiheit, Ernährungsoptionen und Zahlungsmethoden waren 12 einfache Checkboxen; ein leeres Häkchen bedeutete dadurch zweierlei zugleich – „gibt es nicht" und „weiß ich nicht". Der alte Hint sagte das offen („Unbekannte Felder einfach frei lassen"), und das Admin-Detail zeigte es als „Nein / unbekannt" an. Für eine Barrierefreiheits-Plattform ist genau dieser Unterschied wesentlich: „kein barrierefreies WC" ist eine belastbare Information, „unbekannt" ist keine. Jede der 12 Fragen ist jetzt eine **Pflichtfrage mit Ja / Nein / Weiß nicht**, dargestellt als Segmented Control (echte Radio-Inputs als `sr-only` statt `hidden`, damit Tastatur- und Screenreader-Bedienung erhalten bleibt; sichtbarer Fokusring, Tap-Targets über 44 px). Der Wizard blockiert „Weiter", solange Fragen im aktuellen Schritt offen sind, markiert sie rot und springt hin – serverseitig sichert ein `NotNull`-Constraint ab (ungültiger Submit → 422). Neuer Enum `App\Enum\TriState`; bewusst nicht `?bool`, weil sich „Weiß nicht" sonst nicht von „noch nicht beantwortet" unterscheiden ließe und die Pflichtvalidierung damit unmöglich wäre. Das Admin-Detail zeigt Ja grün, Nein rot, Weiß nicht grau. Die `Restaurant`-Entity bleibt bei `bool` – beim Freigeben wird „Weiß nicht" als „Nein" übernommen, was Repository-Filter, den `RestaurantTransformer` (Boolean-Vertrag der iOS-API) und alle Restaurant-Templates unangetastet lässt. Migration `Version20260809000000` überführt `TINYINT(1)` nach `VARCHAR(10) NULL` (kein natives `ENUM` wegen MariaDB 10.5 auf Production) und übersetzt Bestandsdaten `1 → yes`, `0 → unknown`.

### Added
- **Fehler-Tracking mit Sentry:** Fehler auf Production waren bislang unsichtbar – Monolog schrieb nur nach `php://stderr`, wo niemand aktiv hinschaut. `sentry/sentry-symfony` meldet jetzt uncaught Exceptions und Monolog-Records ab `WARNING` an ein Sentry-Projekt in der **EU-Region** (Frankfurt). Das Bundle ist in `config/bundles.php` bewusst **nur für `prod`** registriert: lokale Entwicklung und die Test-Suite kennen die Extension gar nicht und können nichts senden. Der DSN kommt aus `SENTRY_DSN` und wird ausschließlich in der `.env.local` auf dem Server gesetzt – nicht im öffentlichen Repo; ein leerer Wert deaktiviert Sentry lautlos (Muster von `MOBILITEIT_API_KEY`). Datenschutzseitig: `send_default_pii: false` (keine IP-Adressen, Cookies, Header oder Nutzerdaten), `zend.exception_ignore_args` bleibt auf dem PHP-Default `On`, damit keine Funktionsargumente wie Passwörter in Stacktraces landen. 404/405/403/429 sind über `ignore_exceptions` gefiltert, sonst hätte Bot-Traffic die Quota geflutet. Sentry-Releases hängen über `release: 'endlech@%app.version%'` am CalVer-Parameter und ziehen bei jedem Release automatisch mit. Datenschutzerklärung um einen Abschnitt „Fehleranalyse (Sentry)" in allen vier Sprachen ergänzt.

---

## [2026.08.06] – Deployment-Automatisierung, Test-Suite & Bugfixes

### Added
- **Deployment über GitHub Actions & SSH:** Der Git-Button des Hosting-Panels ist abgelöst – **ein Merge nach `production` ist jetzt der Deploy**. Ein Runner öffnet eine SSH-Sitzung und lässt den Server sich selbst aktualisieren: `git reset --hard origin/production` + `git clean -fd` (entfernt, was nicht mehr im Repo steht – der Panel-Button kopierte nur und ließ gelöschte Dateien liegen), dann `composer install --no-dev --optimize-autoloader`, Doctrine-Migrationen und `cache:clear`. Zwei neue Dateien: `.github/workflows/cd.yml` (nur die Verbindung, Sparse-Checkout einer einzigen Datei, `concurrency`-Sperre gegen parallele Deploys) und `.github/deploy.sh` (die gesamte Logik, versioniert und mit `bash -n` prüfbar; `set -euo pipefail`, damit eine gescheiterte Migration den Lauf rot macht statt grün). Vorgeschalteter Job `verify-assets` baut `public/build` neu und vergleicht per `git status --porcelain` – da der Build im Repo liegt, fiele ein vergessenes `npm run build` sonst niemandem auf. `git clean` läuft ohne `-x`, alles Gitignorierte überlebt (`.env.local`, `config/jwt/*.pem`, `public/uploads/`, `var/`). Rollback ist ein Revert-Commit auf `production`, inklusive passender Assets aus demselben Commit. Kein Null-Downtime – für dieses Projekt bewusst akzeptiert. Die Waisen-Inventur gegen den Live-Server ist vorab gelaufen: 18 Waisen, ausschließlich echte Altlasten (JS-Dateien von vor der TypeScript-Umstellung, sechs veraltete `public/build/`-Hashes, die alte `tests/`-Gliederung, ein Cloudways-Platzhalter), und alles Schützenswerte – `.env.local`, JWT-Keys, sämtliche Nutzer-Uploads – nachweislich durch `.gitignore` gedeckt.

- **Sortier-Reihenfolge-Tests & Test-Gliederung nach Art:** Reihenfolge-Tests für die Restaurant-Sortierung (`?sort=rating|name|newest`) auf allen drei Ebenen – Repository (bislang fehlender `findPaginated('rating')`-Reihenfolge-Test), funktionaler Web-Controller (tatsächlich gerenderte Reihenfolge der Restaurant-Karten) und JSON-API (`data`-Reihenfolge + `meta.sort`, inkl. Fallback ungültiger Werte auf `rating`). Die Test-Suite ist jetzt nach Test-Art in Ordner gegliedert – `tests/Unit/` (ohne DB), `tests/Integration/` (KernelTestCase + DB) und `tests/Functional/` (WebTestCase) – mit je einer gleichnamigen `phpunit.dist.xml`-Testsuite (`php bin/phpunit --testsuite Unit|Integration|Functional`); `AbstractWebTestCase` bleibt im `tests/`-Root (Namespace `App\Tests`). 154 Tests, 544 Assertions.
- **Umfassende Test-Suite & CI:** Die automatisierte Testabdeckung wurde von 29 auf **146 Tests** (474 Assertions) ausgebaut – Unit-Tests (Services, Transformer, Enums, Twig-Extension), Integrationstests gegen MySQL (alle `RestaurantRepository::findPaginated`-Filter inkl. Nachtschicht- und `JSON_CONTAINS`-Logik, Upload-Services mit Temp-Dir-Isolation) und funktionale WebTestCase-Tests für sämtliche Web-, Admin- und `/api/v1`-Controller (inkl. Auth-Guards, CSRF-Pfade, Mailer-Versand). Test-Isolation über `dama/doctrine-test-bundle` (Transaktion-Rollback pro Test → wiederholbare Läufe). Neue Befehle `make test` / `make test-db-setup` und `composer test`. GitHub-Actions-Workflow (`.github/workflows/ci.yml`) führt PHPUnit (PHP 8.4 + MySQL-8.0-Service, JWT-Keypair) sowie TypeScript-Typecheck und ESLint bei jedem Push/PR aus. Basisklasse `tests/AbstractWebTestCase.php` mit Login-/Formular-/CSRF-Helfern.

### Fixed
- **Sprachen-Filter (`?lang_…`) warf 500er:** Der dokumentierte Sprachfilter auf `/restaurants` (z. B. `?lang_de=1`) nutzt die MySQL-Funktion `JSON_CONTAINS`, die nirgends als DQL-Funktion registriert war → `QueryException`. Neu: `App\Doctrine\JsonContainsFunction`, registriert in `config/packages/doctrine.yaml`.
- **Restaurant-Detailseite warf 500er ohne Nahverkehrs-API-Key:** `app.mobiliteit_api_key` löste über `%env(default::…)%` bei leerem Key zu `null` auf, was den `string`-Typehint von `PublicTransportService` brach (jede Detailseite mit Koordinaten betroffen). Fix: `%env(string:default::MOBILITEIT_API_KEY)%` castet zu `''` → dokumentierte Graceful-Degradation (leerer Key → keine Haltestellen) funktioniert wieder.
- **Admin-Vorschlag-Detailseite warf 500er:** `templates/admin/suggestion/show.html.twig` nutzt den `|u`-Twig-Filter, das Paket `twig/string-extra` war jedoch nicht installiert. Nachinstalliert.
- **Admin – Koordinaten-Präzision:** Breiten- und Längengrad im Restaurant-Formular werden nicht mehr auf 3 Nachkommastellen gerundet angezeigt (z. B. `5.94700000` → `5,947`), sondern mit voller Präzision von 8 Nachkommastellen – passend zu den DB-Spalten `DECIMAL(10,8)`/`DECIMAL(11,8)`. Ursache war der fehlende `scale`-Wert auf den `NumberType`-Feldern (`RestaurantType`); Default des `\NumberFormatter` sind 3 Nachkommastellen. Schützt auch beim Speichern vor Präzisionsverlust.

---

## [2026.06.19] – Mobile App, REST-API & PWA

### Added
- **PWA – Installierbare iPhone-App (Issue #83):** Endlech.lu lässt sich über Safaris „Zum Home-Bildschirm" als Progressive Web App installieren und startet dann im Vollbild ohne Browser-Chrome. Vollständiges Web App Manifest (`public/manifest.webmanifest`, `display: standalone`, `orientation: portrait`, Theme-Farbe Cyan), 11 App-Icon-Größen (57–512 px, inkl. maskable) reproduzierbar aus dem Logo erzeugt (`bin/generate-pwa-icons.sh`, macOS `sips`), alle iOS-spezifischen Meta-Tags (`apple-mobile-web-app-*`, `apple-touch-icon`) und `viewport-fit=cover` für Safe-Area-Insets (Notch/Home-Indicator). Service Worker (`public/sw.js`) mit Offline-Fallback-Seite (`public/offline.html`): Navigationen network-first, gebaute Assets stale-while-revalidate, `/api/` nie gecacht. Neue mobile Bottom-Navigation (`_bottom_nav.html.twig`, nur < 768 px, Tap-Targets ≥ 44 px, Home/Restaurants/Über uns/Profil) ersetzt die auf Mobil ausgeblendete Header-Navigation. Formularfelder erhalten auf kleinen Screens 16 px Schriftgröße gegen iOS-Auto-Zoom. Keine Backend-/DB-Änderung; alle PWA-Dateien locale-frei auf Root-Ebene.
- **REST-API für die iOS-App (Issue #87):** Versionierte, locale-freie REST/JSON-API unter `/api/v1/` als Backend für eine künftige native iOS-App. JWT-Authentifizierung via `lexik/jwt-authentication-bundle`. Endpunkte: `POST /auth/login` (Token), `POST /auth/register` (legt unverifizierten Nutzer an + E-Mail-Verifikation wie im Web), `GET /restaurants` (paginiert + alle Filter aus `findPaginated`: Barrierefreiheit, Ernährung, Küche, „offen jetzt", Stadt, Sprachen), `GET /restaurants/{id}` (volle Details inkl. Öffnungszeiten, Zahlung, Kontakt, Standort, Bestelloptionen), `GET /restaurants/{id}/images`, `GET /me` + `GET /me/submissions` (auth), `POST /restaurants` (auth, setzt `submittedBy`, unverifiziert). Explizite Transformer-Services (`App\Api\RestaurantTransformer`, `UserTransformer`) statt Serializer-Groups – `password`/Token werden strukturell nie ausgegeben. Einheitliche JSON-Fehler (`{error:{code,message}}`, 401 vs. 403 je nach Auth-Status) via `ApiExceptionSubscriber`. CORS (`nelmio/cors-bundle`) und IP-basiertes Rate-Limiting (`symfony/rate-limiter`, Login strenger) nur für `/api/v1`. Auto-generierte Swagger-UI unter `/api/docs` (`nelmio/api-doc-bundle`). 13 WebTestCase-Tests. Bestehende Web-App unverändert (eigener Routing-Import ohne `_locale`-Prefix).
- **Cookie-Consent-Banner (Issue #82):** DSGVO-konformes Banner, das beim ersten Besuch unten erscheint und über die Cookie-Nutzung informiert. „Akzeptieren"/„Ablehnen" speichern die Wahl 365 Tage im Cookie `cookie_consent`; danach erscheint das Banner nicht mehr. Footer-Link „Cookie-Einstellungen" öffnet es erneut. Banner verlinkt auf den Datenschutz-Abschnitt der Rechtliches-Seite (`#datenschutz`). Vollständig barrierefrei (Tastatur, ARIA-Rollen, Kontrast), responsiv und in 4 Sprachen (lb, de, fr, en). Stimulus-Controller `cookie_consent_controller.ts`, Partial `_cookie_banner.html.twig`. Nur auf öffentlichen Seiten (Admin ausgenommen).
- **Öffnungszeiten: Mehrere Zeitslots pro Tag (Issue #81):** Restaurants mit zwei Schichten (z. B. Mittag 12:00–14:30 und Abend 18:00–22:00) werden jetzt korrekt abgebildet. Pro Wochentag sind beliebig viele `OpeningHour`-Einträge möglich; ein Tag ohne Zeitfenster gilt als geschlossen. Admin-Formular gruppiert die Slots nach Tag mit „＋ Zeitfenster hinzufügen"- und Entfernen-Buttons (Stimulus). Detailseite zeigt alle Slots eines Tages als `12:00 – 14:30 · 18:00 – 22:00`. Der „Geöffnet"-Status und die nächste Öffnungszeit berücksichtigen alle Slots (inkl. Nachtschicht-Übertrag). `?open=1`-Filter angepasst. Erster PHPUnit-Test (`OpeningHoursServiceTest`). Migration entfernt UNIQUE-Constraint und `is_closed`-Spalte.

---

## [2026.03.22] – Küchen-Typen, Öffnungszeiten & Nahverkehr

### Added
- **Cuisine Multi-Select (Issue #77):** Küchen-Typ-Auswahl mit Autocomplete und Mehrfachauswahl. Neue `Cuisine` Entity mit ManyToMany-Relation statt einfachem String-Feld. Tom Select Autocomplete im Admin-Formular zum Suchen, Auswählen und Erstellen neuer Küchen-Typen. Neue API-Endpunkte (`/api/cuisines/search`, `/api/cuisines`). Checkbox-Filter in der Restaurant-Sidebar statt Freitext. Orange Cuisine-Badges auf Restaurant-Karten, Detail- und Startseite. 20 vordefinierte Küchen-Typen in Fixtures. Migration mit automatischer Datenmigration vom alten String-Feld.
- **Vorschlags-Wizard (Issue #76):** Multi-Step Wizard mit 5 Schritten für das Restaurant-Vorschlagsformular. 17 neue Felder auf der RestaurantSuggestion-Entity: Zahlung (acceptsCash, acceptsCard, acceptsPayconiq), Ernährung (isVegan, isVegetarian, isHalal), Sprachen (spokenLanguages), Kontakt (phone, email, website) und Social Media (instagramUrl, facebookUrl, tiktokUrl). Step-Indikator-Leiste mit automatischem Sprung zum ersten Fehler-Step. Stimulus-Controller für Step-Navigation. Alle neuen Felder werden bei Genehmigung auf das Restaurant übertragen.
- **Hero-Badges (Issue #74):** Rating & Sprach-Badges im Hero-Bereich der Restaurant-Detailseite. Farbcodiertes Rating-Badge (grün ≥7, amber ≥4, rot <4) und Sprach-Flag-Badges mit Glaseffekt. Neues Partial `_hero_badges.html.twig`, eingebunden in beide Hero-Varianten (Cover-Foto + Emoji-Fallback). Übersetzungen in 4 Sprachen (de, en, fr, lb).
- **Nahverkehr (Issue #65):** Barrierefreie Bus- & Tram-Haltestellen in der Nähe auf der Restaurant-Detailseite. Neue Felder `latitude`, `longitude`, `nearbyStopsNote` auf der Restaurant-Entity. PublicTransportService nutzt HAFAS API (cdt.hafas.de) mit Cache (24h) und Graceful Degradation. Template-Partial mit Haltestellen-Karten (Name, Linien-Badges, Distanz). Admin-Formular: Fieldset "Standort & Nahverkehr" mit Lat/Lng und Nahverkehrs-Hinweis. Übersetzungen in 4 Sprachen (de, en, fr, lb). Alle 11 Fixture-Restaurants mit echten Luxemburg-Koordinaten.
- **Öffnungszeiten (Issue #64):** Strukturierte Öffnungszeiten pro Wochentag mit automatischer Berechnung des Open/Closed-Status. OpeningHour Entity, OpeningHoursService, Admin-Formular mit 7-Tage-Tabelle, Wochenplan auf Detailseite mit hervorgehobenem heutigem Tag, dynamischer Badge auf Karten und Liste. Nachtschichten und Ruhetage werden korrekt behandelt. Manueller isOpen-Boolean entfernt.
- **Behindertenparkplatz (Issue #66):** Neues Barrierefreiheits-Kriterium `hasDisabledParking`. Filter-Checkbox in Sidebar, Badge auf Restaurant-Karten, Kachel auf Detailseite, Icon in Admin-Tabelle, Checkbox im Admin-Formular. Übersetzungen in 4 Sprachen (de, en, fr, lb). 5 Fixture-Restaurants mit Parkplatz.
- **Profil: Eingereichte Restaurants (Issue #63):** Neue Sektion "Meine Einreichungen" auf der Profilseite zeigt vom Nutzer eingereichte Restaurants mit Verifizierungsstatus. Neues `submittedBy`-Feld auf der Restaurant-Entity (ManyToOne User, SET NULL). Bei Genehmigung eines Community-Vorschlags wird der Einreicher automatisch gesetzt. Übersetzungen in 4 Sprachen (de, en, fr, lb).
- **Admin Dashboard Statistiken (Issue #62):** Erweitertes Dashboard mit 7 Stat-Karten (Restaurants, Verifizierte, Offene Vorschläge, Benutzer, Restaurants diesen Monat, Benutzer diesen Monat, Fotos). Tabellen für zuletzt hinzugefügte Restaurants und zuletzt registrierte Benutzer. Neuer `AdminStatsService` für zentralisierte Statistik-Abfragen. Dashboard-Route in eigenen `AdminDashboardController` ausgelagert. Übersetzungen in 4 Sprachen (de, en, fr, lb).
- **Neue Lieferplattformen (Issue #67):** Wolt, Wedely und Goosty als Bestelloptionen. SVG-Logos für Marken-Plattformen auf der Detailseite. Emoji-Fallback für generische Optionen (Telefon, Webseite, Andere).
- **App-Version im Footer:** Versionsnummer wird jetzt im Footer neben dem Copyright angezeigt. Neuer `app.version` Parameter als Twig-Global.

---

## [2026.03.17] – Profil, Cover-Fotos & About-Seite

### Added
- **About-Seite aktualisiert (Issue #56):** Neuer Meilenstein „März 2026 — Erste Live-Version" in der Timeline. Gründer-Foto vorbereitet (Fallback auf Initialen). Übersetzungen in 4 Sprachen aktualisiert.
- **Gründer-Foto:** `public/uploads/team/michael.jpg` wird jetzt im Repository getrackt (gitignore-Ausnahme für statische Team-Assets).
- **Benutzerprofil (Issue #54):** Profilseite für eingeloggte Nutzer zum Anzeigen/Bearbeiten von Name, E-Mail und Profilbild. Passwort-Änderung mit Prüfung des aktuellen Passworts. Avatar-Upload (JPG/PNG/WebP, max. 2 MB) mit Initialen-Fallback. Avatar + Profil-Link in der Navigation. i18n in allen 4 Sprachen (lb, de, fr, en).
- **Titelbild / Cover-Foto (Issue #44):** Das erste Bild eines Restaurants dient automatisch als Cover-Foto. Drag & Drop Sortierung im Admin-Panel (SortableJS). Cover-Foto als Hero-Bild auf Detailseite und Thumbnail in Listenansicht & Homepage.
- **Wickeltisch-Filter (Issue #41):** Neues Barrierefreiheits-Kriterium `hasChangingTable`. Kachel auf Detailseite, Filter-Checkbox in Sidebar, Badge auf Restaurant-Karten.
- **Kontaktdaten & Social Media (Issue #42):** Telefon, E-Mail, Webseite mit direkten Aktions-Links. Instagram, Facebook, TikTok mit Marken-SVG-Icons. Neue Sektion auf Detailseite, neues Fieldset im Admin-Formular.
- **Bestelloptionen (Issue #43):** Plattformen (Uber Eats, Deliveroo, Just Eat, Telefon, Webseite, Andere) pro Restaurant. CTA-Buttons auf Detailseite, dynamische Collection im Admin-Formular.
- **Ernährungsoptionen (Issue #45):** Vegan, Vegetarisch, Halal pro Restaurant. Badges auf Karten, Filter in Sidebar, Sektion auf Detailseite.
- **Gesprochene Sprachen (Issue #40):** Luxemburgisch, Deutsch, Französisch, Englisch, Portugiesisch, Andere. Flaggen-Badges, Sprachfilter (AND-Verknüpfung), Admin-Checkboxen.

### Changed
- **TypeScript-Migration:** Alle JS-Assets auf TypeScript migriert. Webpack Encore `enableTypeScriptLoader()`, ESLint Flat Config, npm-Scripts `typecheck`/`lint`/`lint:fix`, `make lint` Target.
- **Cover-Foto Sortierung:** `Restaurant::$images` OrderBy auf `sortOrder ASC` geändert. `ImageUploadService::reorderAfterDelete()` für konsekutive Sortierung.

### Fixed
- **OrderingOptionType:** Choice-Closures akzeptieren jetzt String-Werte korrekt (Issue #44).

---

## [2026.03.08e] – Restaurant-Fotos

### 🚀 Features
- **Bildergalerie:** Fotos pro Restaurant auf der Detailseite (GLightbox-Lightbox).
- **Thumbnail:** Erstes Foto als Vorschau-Bild auf der Restaurantliste.
- **Admin-Upload:** Mehrere Fotos gleichzeitig hochladen (jpg, png, webp, max. 5 MB).
- **Admin-Löschung:** Einzelne Fotos per Hover-Button entfernen.
- **Alt-Texte:** Barrierefreie Bildbeschreibungen für alle Fotos.

### 🛠 Tech
- Entity `RestaurantImage` (ManyToOne zu Restaurant, CASCADE DELETE).
- `ImageUploadService` – Upload & Löschung (Symfony-nativ, kein VichUploaderBundle).
- GLightbox via npm für Lightbox-Galerie.
- Migration `Version20260308110000`.

---

## [2026.03.08d] – Filterfunktion für Lokale

### 🚀 Features
- **Barrierefreiheits-Filter:** Checkboxen für ♿ Rollstuhlgerecht, 🚻 Barrierefreies WC, 🐕 Assistenzhund, 💡 Helle Beleuchtung.
- **Status-Filter:** „Nur geöffnete Lokale" Checkbox.
- **Ort-Filter:** Freitext-Suche nach Stadt (LIKE).
- **Küchen-Filter:** Freitext-Suche nach Küchentyp (LIKE).
- **Aktive Filter:** Chip-Zeile über Ergebnissen + „Alle zurücksetzen"-Link in der Sidebar.
- **Filter-Persistenz:** Sort- und Pagination-Links behalten alle aktiven Filter bei.

### 🛠 Tech
- **Repository:** `findPaginated()` auf `array $filters` umgestellt (skalierbar, 8 Filter-Keys).
- **Controller:** 8 Query-Parameter ausgelesen und als `$filters`-Array weitergereicht.

---

## [2026.03.08c] – Verifiziertes Lokal
*Blaues Verifikations-Badge für vom Endlech.lu-Team geprüfte Restaurants.*

### 🚀 Features
- **Verifikations-Badge:** Blauer Haken (Cyan-600) für verifizierte Restaurants auf Karte und Detailseite.
- **Tooltip:** „Von Endlech.lu persönlich vor Ort geprüft" via Browser-Tooltip.
- **Filter:** Listenansicht filtert nach „Nur verifizierte Lokale" (?verified=1).
- **Admin:** Verifikations-Checkbox im Bearbeitungsformular mit Auto-Stamping von Datum + Admin-User.
- **Admin:** Quick-Toggle-Button in der Restaurants-Übersicht (verifiziert/unverifiziert).
- **Admin:** Stat-Card „Verifizierte Lokale" im Dashboard.

### 🛠 Tech & Config
- **Entity:** `isVerified`, `verifiedAt`, `verifiedBy` zur `Restaurant`-Entity hinzugefügt.
- **Migration:** `Version20260308100000` – fügt `is_verified`, `verified_at`, `verified_by_id` zur `restaurant`-Tabelle hinzu.
- **Route:** `admin_restaurant_toggle_verified` POST `/admin/restaurants/{id}/verifizieren`.
- **Partial:** `templates/partials/_verified_badge.html.twig` – wiederverwendbares Badge-Template.
- **Fixtures:** 3 Restaurants als verifiziert markiert (Pizzeria Bella Vista, Sushi Zen, Green Bowl).

---

## [2026.03.08b] – Zahlungsmethoden
*Zahlungsmethoden pro Restaurant (Bargeld, Karte, Payconiq).*

### 🚀 Features
- **Zahlungsmethoden:** Drei neue Boolean-Felder in der `Restaurant`-Entity (`acceptsCash`, `acceptsCard`, `acceptsPayconiq`).
- **Detailseite:** Neue Sektion „Zahlungsmethoden" auf `/restaurants/{id}` mit farbigen Badges pro Methode (Grün = akzeptiert, Payconiq in Markenfarbe `#FF4612`).
- **Admin-Formular:** Neue Fieldset „Zahlungsmethoden" mit drei Checkboxen im Restaurant-Bearbeitungsformular.
- **Fixtures:** Alle 11 Fixture-Restaurants mit realistischen Zahlungsmethoden-Daten versehen.

### 🛠 Tech & Config
- **Migration:** `Version20260308000000` – fügt `accepts_cash`, `accepts_card`, `accepts_payconiq` (TINYINT) zur `restaurant`-Tabelle hinzu.

---

## [2026.03.08]
*Brevo Mailer Integration für Transaktions-E-Mails.*

### 🚀 Features
- **Brevo Integration:** `symfony/brevo-mailer` als Produktions-Mail-Provider installiert und konfiguriert.
- **E-Mail-Konfiguration:** Zentraler Absender (`noreply@endlech.lu`) über `mailer.yaml` und Umgebungsvariablen konfigurierbar.
- **Base E-Mail-Template:** Wiederverwendbares Basis-Layout (`email/base.html.twig`) mit Endlech.lu Branding (Gradient-Header, Footer).
- **Fehlerbehandlung:** Try/Catch für `TransportExceptionInterface` in allen E-Mail-sendenden Controllern mit benutzerfreundlichen Flash-Nachrichten.

### 🛠 Tech & Config
- **Dependency:** `symfony/brevo-mailer` v8.0 hinzugefügt.
- **Mailer Config:** Globaler Absender via `envelope.sender` und `headers.From` in `config/packages/mailer.yaml`.
- **Umgebungsvariablen:** `MAILER_SENDER_ADDRESS` und `MAILER_SENDER_NAME` in `.env` für konfigurierbare Absenderadresse.
- **Dev-Umgebung:** `.env.dev` nutzt Mailpit (`smtp://localhost:1025`) für lokales E-Mail-Testing.
- **Templates:** Verification-E-Mail refactored, nutzt jetzt `email/base.html.twig` als Basis-Layout.
- **Controller:** `RegistrationController` und `EmailVerificationController` nutzen globale Absender-Konfiguration statt hardcoded Adressen.

---

## [2026.03.01]
*Admin-Panel für die Verwaltung von Restaurants (CRUD).*

### 🚀 Features
- **Admin-Panel:** Neuer Admin-Bereich unter `/admin` für ROLE_ADMIN Benutzer.
- **Dashboard:** Admin-Dashboard mit Restaurant-Statistiken und Schnellaktionen.
- **Restaurant CRUD:** Restaurants erstellen, bearbeiten und löschen über `/admin/restaurants`.
- **Formular:** `RestaurantType`-Formular mit allen Restaurant-Feldern (Name, Stadt, Küche, Emoji, Bewertung, Status, Barrierefreiheits-Checkboxen, dynamische Hinweise).
- **Barrierefreiheits-Hinweise:** Dynamisches Hinzufügen/Entfernen von Hinweisen im Format `ok:Text` / `warn:Text` via Stimulus-Controller.
- **Navigation:** "Admin"-Link in der Hauptnavigation für Admin-Benutzer.
- **Sicherheit:** `/admin`-Bereich via `access_control` und `#[IsGranted('ROLE_ADMIN')]` geschützt.
- **CSRF-Schutz:** Löschen von Restaurants mit CSRF-Token-Validierung und Bestätigungsdialog.

### 🛠 Tech & Config
- **Controller:** `AdminRestaurantController` mit 5 Aktionen (Dashboard, Index, New, Edit, Delete).
- **Form:** `RestaurantType` mit CollectionType für dynamische accessibilityNotes.
- **Stimulus:** `collection_form_controller.js` für dynamische Formularfelder.
- **Templates:** Admin-Layout mit Sidebar-Navigation (`admin/base.html.twig`), 5 Admin-Templates.
- **Security:** `access_control`-Regel für `/admin`-Pfad in `security.yaml`.

---

## [2026.02.28]
*Startseite als Landing Page neu gestaltet. Detailseite für einzelne Restaurants.*

### 🚀 Features
- **Startseite:** Komplette Neugestaltung als Landing Page mit Hero-Section, „So funktioniert's" (3 Schritte), Top-6 Restaurant-Vorschau, „Warum Endlech.lu?" Wertversprechen und CTA-Banner.
- **Backend:** `RestaurantRepository::findTopRated(int $limit)` für die Top-bewerteten Restaurants.
- **Backend:** `HomeController` zeigt jetzt Top-6 Restaurants statt alle und übergibt Gesamtanzahl ans Template.
- **UI:** Restaurant-Karten auf der Startseite mit Barrierefreiheits-Icons (♿ 🚻 🐕 💡).
- **UI:** Responsive 3-Spalten-Grid (1 Spalte mobil, 2 Tablet, 3 Desktop).
- **CTA:** „Restaurants entdecken" → `/restaurants`, „Mitmachen" / „Restaurant vorschlagen" → `/register`.

### Vorige Änderungen (2026.02.28)
*Detailseite für einzelne Restaurants unter `/restaurants/{id}`.*

### 🚀 Features
- **Backend:** `RestaurantController::show()` mit Route `/restaurants/{id}` (Name: `app_restaurant_show`).
- **Backend:** Automatische 404-Antwort bei nicht existierender Restaurant-ID (Symfony Entity Value Resolver).
- **UI:** Template `restaurant/show.html.twig` mit Emoji-Hero, Status-Badge, Bewertung, Barrierefreiheits-Übersicht (4 Kriterien) und Hinweisen (ok/warn).
- **UI:** Responsive Layout (single-column, max-w-3xl) mit bestehendem Design (Cyan/Purple Gradient).
- **Linking:** "Details ansehen" Links in `restaurant/index.html.twig` und `home/index.html.twig` verlinken jetzt auf die Detailseite.

---

## [2026.02.27]
*Restaurant-Listenansicht unter `/restaurants` mit Pagination und Sortierung.*

### 🚀 Features
- **Backend:** `RestaurantController` mit Route `/restaurants` (Name: `app_restaurant_index`).
- **Backend:** Paginierung via Doctrine `Paginator` (6 Ergebnisse pro Seite).
- **Backend:** Sortierung nach Bewertung (Standard), Name (A–Z) und Neueste via URL-Parameter `?sort=`.
- **UI:** Dediziertes Template `restaurant/index.html.twig` mit Restaurant-Karten, Barrierefreiheits-Icons, Pagination-Navigation und Leer-Zustand.
- **Data:** 3 neue Fixture-Restaurants (Trattoria Roma/Ettelbruck, Green Bowl/Cloche d'Or, Brasserie du Grund/Grund) – jetzt 11 Einträge insgesamt.
- **Nav:** "Restaurants finden" in der Navigation verlinkt jetzt auf `/restaurants`.

### 🛠 Tech & Config
- **Repository:** `RestaurantRepository::findPaginated(string $sort, int $page, int $limit)` hinzugefügt.
- **Data:** `UserFixtures` mit 3 Test-Usern (Admin, verifiziert, unverifiziert) und korrekt gehashten Passwörtern (Symfony PasswordHasher).

---

## [2026.02.25]
*Platform-Launch: Overlay entfernt, echte Datenbank-Anbindung für Restaurant-Karten.*

### 🚀 Features
- **Launch:** "Coming Soon" Overlay entfernt – die Plattform ist jetzt live.
- **Backend:** `Restaurant`-Entity mit Barrierefreiheits-Feldern (Rollstuhl, WC, Assistenzhund, Beleuchtung).
- **Backend:** Doctrine-Migration für die `restaurant`-Tabelle (MySQL 8.0).
- **Data:** 8 Luxemburger Restaurants als initiale Fixtures (Luxembourg-Ville, Esch-Belval, Dudelange, Kirchberg, Grevenmacher, Diekirch, Strassen, Remich).
- **UI:** Dynamische Restaurant-Karten via DB-Abfrage statt hardcoded HTML.
- **UI:** Empty-State bei leerer Restaurantliste.

### 🛠 Tech & Config
- **Dependency:** `doctrine/doctrine-fixtures-bundle` als Dev-Abhängigkeit hinzugefügt.
- **Controller:** `HomeController` injiziert `RestaurantRepository` und übergibt `$restaurants` ans Template.

---

## [2026.01.13]
*Initialer Projektstart und UI-Implementation.*

### 🚀 Features
- **UI:** "Coming Soon" Overlay mit Glassmorphism-Effekt und Animationen implementiert.
- **Layout:** Responsives Grid-Layout mit Sidebar-Filtern und Restaurant-Karten erstellt.
- **Assets:** Logo `images/logo.png` eingebunden.
- **Design:** Modernes Farbschema (Cyan/Purple) definiert.

### 🛠 Tech & Config
- **Core:** Symfony 7 Projektstruktur aufgesetzt.
- **Frontend:** Webpack Encore mit PostCSS und Tailwind CSS konfiguriert.
- **Fix:** Tailwind-Build Prozess repariert (`postcss.config.js` erstellt).
- **Templates:** Base-Layout (`base.html.twig`) mit Navigation und Footer erstellt.

### 📝 Dokumentation
- `README.md` im Mika+ Hub Style erstellt.
- `CHANGELOG.md` mit CalVer-Versionierung initiiert.

---
