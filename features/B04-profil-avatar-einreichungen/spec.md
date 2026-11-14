# B04 · Profil, Avatar & eigene Einreichungen — Spezifikation

Status: `review` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein angemeldeter Nutzer pflegt Name und E-Mail-Adresse, lädt ein Profilbild hoch oder
entfernt es, ändert sein Passwort und sieht, welche Restaurants er eingereicht hat und
ob sie geprüft wurden. Auf derselben Seite verwaltet er seine Passkeys (B03).

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 | rekonstruiert | Konto |
| B02 | rekonstruiert | Anmeldung — `IS_AUTHENTICATED_FULLY` |
| B11 | bestand | die Liste „Meine Einreichungen" zeigt Vorschläge, die zu Restaurants wurden |

## User Stories

- **US-01** · Als Nutzer möchte ich meinen Anzeigenamen ändern.
- **US-02** · Als Nutzer möchte ich ein Profilbild hochladen und wieder entfernen.
- **US-03** · Als Nutzer möchte ich mein Passwort ändern, ohne mich abmelden zu müssen.
- **US-04** · Als Nutzer möchte ich sehen, welche meiner Vorschläge verifiziert wurden.

## Nicht im Scope

- Passkeys → B03 (auf derselben Seite eingebunden, eigenes Feature)
- Konto löschen → **existiert nicht**, siehe FB-01
- Daten exportieren → existiert nicht, siehe FB-02
- Profil über die REST-API → B23 (`/api/v1/me`, nur lesend)

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Gast ruft `/{locale}/profile` auf, wenn die Anfrage
  durchläuft, dann wird er zur Anmeldung geleitet.
- **AK-02** · Angenommen, ein angemeldeter Nutzer ruft `/{locale}/profile` auf, wenn die
  Seite lädt, dann sieht er drei Formulare (Stammdaten, Passwort, Passkeys) und die
  Liste seiner Einreichungen.
- **AK-03** · Angenommen, der Name wird auf einen gültigen Wert geändert, wenn
  abgeschickt wird, dann steht er danach in der Kopfzeile und es erscheint
  `flash.profile_updated`.
- **AK-04** · Angenommen, ein Bild bis 2 MB im Format JPEG, PNG oder WebP wird
  hochgeladen, wenn abgeschickt wird, dann liegt es unter
  `public/uploads/avatars/` und erscheint als Profilbild.
- **AK-05** · Angenommen, die Datei ist größer als 2 MB oder von anderem Typ, wenn
  abgeschickt wird, dann antwortet der Server mit HTTP 422 und der Avatar bleibt
  unverändert.
- **AK-06** · Angenommen, ein Nutzer hatte bereits einen Avatar, wenn er einen neuen
  hochlädt, dann wird die **alte Datei vom Dateisystem gelöscht** — es bleibt kein
  Waisenbild zurück.
- **AK-07** · Angenommen, ein Nutzer löscht seinen Avatar, wenn er bestätigt, dann sind
  Datei und Datenbankeintrag weg und es erscheint `flash.profile_avatar_deleted`.
- **AK-08** · Angenommen, das CSRF-Token beim Avatar-Löschen fehlt oder ist falsch, wenn
  abgeschickt wird, dann bleibt der Avatar bestehen und es erscheint
  `flash.invalid_csrf`.
- **AK-09** · Angenommen, das eingegebene aktuelle Passwort ist falsch, wenn die
  Passwortänderung abgeschickt wird, dann bleibt das Passwort unverändert und es
  erscheint `flash.profile_wrong_password`.
- **AK-10** · Angenommen, das aktuelle Passwort stimmt und das neue ist mindestens 8
  Zeichen lang und wurde zweimal gleich eingegeben, wenn abgeschickt wird, dann gilt ab
  sofort das neue und es erscheint `flash.profile_password_changed`.
- **AK-11** · Angenommen, ein Nutzer hat Restaurants eingereicht, wenn die Profilseite
  lädt, dann erscheinen sie absteigend nach Anlagedatum mit Name, Stadt, Datum und
  Verifizierungsstatus — und **nur die eigenen**.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-12** · Angenommen, ein Nutzer ändert seine E-Mail-Adresse, wenn abgeschickt
  wird, dann bleibt die bisherige Adresse gültig und die neue wird nur **vorgemerkt**;
  an die neue geht ein Bestätigungslink mit 24-Stunden-Frist, an die bisherige eine
  Warnung. Erst der Klick auf den Link tauscht die Adresse.
  *(**Repariert am 2026-08-24**, BF-19. Vorher wurde die Adresse sofort übernommen und
  `is_verified` blieb auf `true` — der Bestätigungsstatus galt damit für eine nie
  bestätigte Adresse, und wer eine Sitzung kaperte, schrieb das Konto dauerhaft auf
  sich um. Neu: `User::$pendingEmail` mit eigenem Token, `app_email_change_confirm`,
  zwei Mailvorlagen. Der offene Vorgang ist im Profil sichtbar und dort abbrechbar.)*

- **AK-12a** · Angenommen, die vorgemerkte Adresse wurde in der Zwischenzeit von
  jemand anderem registriert oder die Frist ist abgelaufen, wenn der Link angeklickt
  wird, dann wird der Vorgang abgeräumt und eine Meldung gezeigt — kein Serverfehler.
  *(`pending_email` trägt keine Eindeutigkeit, `email` schon; ohne die Prüfung liefe
  der `flush()` in eine Unique-Verletzung.)*

- **AK-13** · Angenommen, ein Nutzer ändert sein Passwort, wenn die Änderung durchläuft,
  dann bleibt **seine eigene** Sitzung bestehen, während **andere Sitzungen desselben
  Kontos und deren `REMEMBERME`-Cookies entwertet** werden.
  *(**Berichtigt am 2026-08-24.** Die ursprüngliche Rekonstruktion behauptete das
  Gegenteil — geschlossen daraus, dass `changePassword()` tatsächlich weder eine
  Session-Invalidierung noch einen Wechsel des Geheimnisses aufruft. Gemessen erledigt
  Symfony beides selbst: Der Sicherheitskontext vergleicht bei jedem Request den
  serialisierten Nutzer aus der Sitzung mit dem frisch geladenen, und die
  `remember_me`-Signatur schließt den Passwort-Hash ein. Nachweis in `qa-report.md`
  sowie im Regressionstest `testEc04PasswortaenderungEntwertetFremdeSitzungen`.)*

- **AK-14** · Angenommen, jemand probiert das aktuelle Passwort im Änderungsformular
  durch, wenn der sechste Versuch innerhalb von 15 Minuten kommt, dann wird er
  abgewiesen — gezählt wird **am Konto**, nicht an der IP.
  *(**Repariert am 2026-08-24**, BF-20. Der Angriff setzt eine gekaperte Sitzung
  voraus; dort wechselt die IP mühelos, das Konto nicht. Limiter `password_change`,
  5 Versuche je 15 Minuten. Nachgestellt: Versuche 1–5 melden „Passwort nicht
  korrekt", der sechste die Sperre.)*

### Datenschutz und Missbrauchsschutz

- **AK-15** · Angenommen, ein Avatar wird hochgeladen, wenn die Prüfung betrachtet
  wird, dann greift `File(maxSize: '2M', mimeTypes: [image/jpeg, image/png, image/webp])`
  — Symfony prüft den **tatsächlichen** MIME-Typ über `fileinfo`, nicht die Endung.
- **AK-16** · Angenommen, ein Avatar liegt im Dateisystem, wenn sein Pfad betrachtet
  wird, dann liegt er unter `public/uploads/avatars/` und ist damit **öffentlich
  abrufbar** — wer die URL kennt, sieht das Bild ohne Anmeldung.
- **AK-17** · Angenommen, ein Nutzer ruft die Profilseite auf, wenn die Einreichungen
  geladen werden, dann filtert die Abfrage auf `submittedBy = :user` — eine fremde ID
  lässt sich nicht unterschieben, weil keine ID aus der Anfrage gelesen wird.
- **AK-18** · Angenommen, die Passwortänderung läuft, wenn die Logs gelesen werden,
  dann steht dort weder das alte noch das neue Passwort im Klartext.
- ~~**AK-19** *(aus OF-03, neu 2026-11-14, Betreiber)* · Angenommen, ein angemeldetes Konto lädt ein Profilbild
  hoch, wenn die Datei gespeichert wird, dann ist sie neu kodiert, höchstens 512 × 512 px groß (Vorschlag) und
  enthält keine Metadaten (EXIF, GPS) mehr. Bereits gespeicherte Profilbilder sind einmalig ebenso umgewandelt.~~ *gestrichen 2026-11-14 (Betreiber): an Feature `18` (Kontoschutz im Profil) übergeben — die Erweiterung eines Bestandsfeatures bekommt eine eigene Nummer.*
- ~~**AK-20** *(aus OF-04, neu 2026-11-14, Betreiber)* · Angenommen, ein angemeldetes Konto ändert sein Passwort im
  Profil, wenn der Wechsel gelingt, dann geht eine Mail an die hinterlegte Adresse: Das Passwort wurde geändert, mit
  Zeitpunkt und dem Weg „Passwort vergessen“, falls es nicht der Inhaber war. Die Mail enthält kein Passwort. Bei
  einem gescheiterten Versuch geht keine Mail.~~ *gestrichen 2026-11-14 (Betreiber): an Feature `18` (Kontoschutz im Profil) übergeben — die Erweiterung eines Bestandsfeatures bekommt eine eigene Nummer.*
- ~~**AK-21** *(aus OF-05, neu 2026-11-14, Betreiber)* · Angenommen, ein unbestätigtes Konto bestätigt einen
  Adresswechsel über den Link in der Mail, wenn der Wechsel wirksam wird, dann gilt das Konto als bestätigt — mit
  denselben Folgen wie die Erstbestätigung (Einführung beim nächsten Anmelden, Brevo-Abgleich). Ein bereits
  bestätigtes Konto bleibt bestätigt, die Einführung kommt nicht erneut.~~ *gestrichen 2026-11-14 (Betreiber): an Feature `18` (Kontoschutz im Profil) übergeben — die Erweiterung eines Bestandsfeatures bekommt eine eigene Nummer.*

## Edge Cases

- **EC-01** · E-Mail-Änderung auf eine bereits vergebene Adresse → `#[UniqueEntity]`
  greift, aber mit der hartkodierten deutschen Meldung (B01/FB-07).
- **EC-02** · Upload mit einer Datei ohne erkennbare Endung → `guessExtension()` liefert
  aus dem MIME-Typ; da nur drei Typen zugelassen sind, entsteht immer `jpg`, `png` oder
  `webp`.
- **EC-03** · Zwei Uploads in derselben Mikrosekunde → `uniqid('', true)` enthält
  zusätzlich einen Zufallsanteil; Kollision praktisch ausgeschlossen.
- **EC-04** · Ungültiges Stammdatenformular → 422 mit erneut gerendeter Seite; der
  **Passwortteil** wird dabei neu und leer aufgebaut, eingegebene Passwörter gehen
  verloren. Beabsichtigt.
- **EC-05** · Datei im Dateisystem gelöscht, Datenbankeintrag bleibt → `getAvatarUrl()`
  liefert weiter einen Pfad, das Bild lädt nicht. Kein Abgleich vorhanden.

## Fehlbestand

- **FB-01 · Kein Löschweg für das Konto.** Keine Route, kein Knopf, keine Methode.
  *Folge:* Art. 17 DSGVO nur über direkten Datenbankeingriff erfüllbar. Der
  Sicherheitskatalog nennt das als **Pflicht**. (Identisch mit B01/FB-04, hier
  wiederholt, weil das Profil der Ort wäre.)
- **FB-02 · Kein Datenexport.** Auskunftsrecht nach Art. 15 DSGVO nicht erfüllbar.
- ~~**FB-03 · Keine erneute Bestätigung bei E-Mail-Änderung.**~~ **Behoben am
  2026-08-24** (BF-19) — siehe AK-12.
- ~~**FB-04 · Keine Sitzungsinvalidierung bei Passwortänderung.**~~ **Entfällt —
  2026-08-24 widerlegt.** Symfony entwertet fremde Sitzungen und `remember_me`-Cookies
  von sich aus; siehe AK-13. Der Eintrag bleibt durchgestrichen stehen, damit
  nachvollziehbar ist, dass hier einmal eine Lücke vermutet wurde, die es nicht gibt.
- **FB-05 · Keine Benachrichtigung an die alte Adresse**, wenn das **Passwort**
  geändert wird. Für die E-Mail-Änderung ist der Punkt seit 2026-08-24 erledigt (die
  Warnmail an die bisherige Adresse ist dort der eigentliche Schutz — wer übernimmt,
  liest die Bestätigung im neuen Postfach ohnehin mit); beim Passwortwechsel fehlt sie
  weiterhin.
  *Seit 2026-11-14 an Feature `18` übergeben (OF-04).*
- **FB-06 · Avatare werden nicht verkleinert oder neu kodiert.** Die hochgeladene Datei
  wird unverändert abgelegt (im Bestand liegt eine mit 1,1 MB). *Folge:* Metadaten wie
  GPS-Koordinaten aus einem Handyfoto bleiben in der öffentlich abrufbaren Datei
  erhalten.
  *Seit 2026-11-14 an Feature `18` übergeben (OF-03).*
- ~~**FB-07 · Kein Rate Limit auf der Passwortänderung.**~~ **Behoben am 2026-08-24**
  (BF-20) — siehe AK-14.
- **FB-08 · Dateiname ist nicht kryptografisch zufällig.** `uniqid('', true)` ist
  zeitbasiert. Für öffentliche Avatare unkritisch, wäre bei nicht-öffentlichen Dateien
  aber ein Aufzählungspfad.

## Offene Fragen

- **OF-01** · Wann kommt der Kontolöschweg (FB-01)? Er berührt `Restaurant.submittedBy`
  (SET NULL), `WebauthnCredential` (CASCADE), `RestaurantSuggestion.suggestedBy`
  (SET NULL) und die Avatardatei — die Kaskaden sind bereits alle gesetzt, es fehlt nur
  der Auslöser. — Betreiber
- ~~**OF-02** · Soll eine E-Mail-Änderung erneut bestätigt werden (AK-12)?~~
  **Beantwortet am 2026-08-24 durch die Reparatur:** ja. Die Frage war ohnehin keine
  echte Wahl — ohne Bestätigung ist die Adresse ein Übernahmehebel, und ein
  Passwort-Zurücksetzen als Rückweg gibt es nicht (siehe Feature `01`).
  **Entschieden 2026-08-25:** Beantwortet durch Feature `01` (2026-08-25). Die Restaurants bleiben, `submittedBy` wird `NULL` — eine Barrierefreiheitsangabe ist eine Sachangabe und gehört den Menschen, die sie brauchen.

- **OF-04** · Soll auch die **Passwortänderung** eine Benachrichtigung an die
  hinterlegte Adresse auslösen (FB-05)? Die Mechanik dafür steht seit 2026-08-24
  bereit. — Betreiber
  ✔ **Beantwortet 2026-11-14 (Betreiber): ja.** Nach einem gelungenen Wechsel geht eine Mail an die hinterlegte
  Adresse, mit Hinweis auf „Passwort vergessen“ — umgesetzt in Feature `18`. Der Deckel `password_change` deckt den Weg bereits.
- **OF-05** · Soll die Bestätigung eines Adresswechsels `is_verified` auf `1` setzen?
  Wer sein Konto nie bestätigt hat und dann die Adresse wechselt, hat den Zugriff auf
  das neue Postfach damit bewiesen, gilt aber weiter als unbestätigt. — Betreiber
  ✔ **Beantwortet 2026-11-14 (Betreiber): ja, wie die Erstbestätigung** — umgesetzt in Feature `18` — Status, Einführung beim nächsten
  Anmelden (`user.onboarding_due`, Feature 12) und Brevo-Abgleich wie in `app_verify_email`. Berührt 12 und 04;
  der Bau prüft dort die Reihenfolge, auf die `EmailVerificationController` schon achtet („NACH setIsVerified(true)“).
- **OF-03** · Sollen Avatare beim Upload neu kodiert werden (FB-06)? Ohne
  Bildbibliothek im Projekt wäre das eine neue Abhängigkeit. — Betreiber
  ✔ **Beantwortet 2026-11-14 (Betreiber): ja, einschließlich des Bestands.** Neue Uploads werden verkleinert und
  ohne Metadaten neu kodiert, vorhandene Avatare wandelt ein einmaliger Befehl um — umgesetzt in Feature `18`. Dafür kommt die
  PHP-Erweiterung GD ins Laufzeit-Image (`Dockerfile`), keine Composer-Abhängigkeit. 512 px ist ein Vorschlag.
- **OF-06** *(neu 2026-11-14, `/sdd-build B04`, außerhalb des Auftrags gefunden)* · **Nach einer Meldung am
  Profilformular scheitert das zweite Absenden mit HTTP 405.** `ProfileController::edit()` rendert die Seite bei
  ungültigem Formular (422) mit einem `ProfileType` **ohne** `action`; Turbo lässt dabei die Adresse `/xx/profile`
  stehen, der Browser schickt die Korrektur an `POST /xx/profile` (nur GET) → 405, die Korrektur geht verloren. Im
  Browser gemessen mit Name „a“ (Meldung, dann „Test User“): auf `main` **und** nach dem Bau gleich, also vorbestehend
  — betrifft jeden 422-Weg des Profils (zu kurzer Name, vergebene Adresse, falsches Bildformat) und seit BF-182/BF-184
  auch leeren Namen und RFC-widrige Adresse, die vorher als 500 endeten. Im Code gleich, nicht gemessen: das
  Passwortformular nach einer Meldung in `changePassword()`. Muster BF-151 („Wer ein Formular auf mehreren Seiten
  rendert, gibt ihm ein Ziel“). Nicht behoben, weil nicht im Auftrag. Belege: `/tmp/b04vorlage/protokoll.txt`
  (Nachtrag), `selbsttest-*-zweites-absenden-1280-de.png`. — Betreiber
  → **QA 2026-11-14: als BF-226 erfasst** (*mittel*, EC-04 durchgefallen). Im Browser auf fünf Wegen bestätigt, auch am
  Passwortformular (405). Ein Code-Befund, keine offene Frage: Nächster Schritt über `/sdd-klaeren B04`.
- **OF-07** *(neu 2026-11-14, `/sdd-build B04`)* · **Restfenster der Kontolöschung neben einem Upload.**
  `AccountDeleter` räumt das Bild jetzt immer über `AvatarUploadService::delete()` ab (unter der Sperre, frisch
  gelesener Name, BF-186). Ein Upload, der die Sperre erst **nach** diesem Schritt bekommt und vor oder nach dem
  Entfernen der Zeile fertig wird, hinterlässt weiterhin eine Datei ohne Konto — `UPDATE` auf eine gelöschte Zeile
  trifft nichts und meldet keinen Fehler. Schließen ließe es sich nur, wenn der Upload unter der Sperre prüft, ob das
  Konto noch besteht; das ändert den Upload-Weg und stand nicht im Auftrag. Nicht nachgestellt, nur im Code
  nachvollzogen (zwei Tabs: Konto löschen und Bild hochladen in derselben Sekunde). — Betreiber
  → **QA 2026-11-14: als BF-227 erfasst** (*niedrig*). Am Server mit zwei Sitzungen nachgestellt: 5 von 31 Durchgängen
  ließen ein öffentlich abrufbares Bild zurück. Zwei Tabs eines Browsers teilen die Sitzung und trafen nie.

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Drei Formulare auf einer Seite | ja, mit eigenen `action`-Zielen | jede Änderung ist ein eigener POST-Endpunkt; die Anzeigeroute bleibt GET-only |
| 2 | Avatar-Feld `mapped: false` | ja | der Dateiname entsteht erst im Service |
| 3 | Alte Datei beim Upload löschen | ja | verhindert Waisen; die einzige Aufräumlogik im Projekt neben `ImageUploadService` |
| 4 | Upload im Web-Root | `public/uploads/avatars` | direkte Auslieferung ohne Controller — schnell, aber öffentlich (AK-16) |
| 5 | CSRF beim Avatar-Löschen von Hand | `isCsrfTokenValid('delete-avatar', …)` | es ist kein Symfony-Formular, sondern ein nackter POST |
| 6 | Passwortänderung ohne Abmeldung | so | Grund nicht erkennbar — siehe FB-04 |
| 7 | Avatare neu kodieren (OF-03) | ja, mit Bestand (2026-11-14, Betreiber) | ein Handyfoto trägt GPS-Koordinaten in eine öffentlich abrufbare Datei; GD statt neuer Bibliothek → Feature `18` |
| 8 | Mail nach Passwortwechsel (OF-04) | ja (2026-11-14, Betreiber) | ein Wechsel aus einer gekaperten Sitzung bliebe sonst unbemerkt; Mechanik und Deckel sind da → Feature `18` |
| 9 | Adresswechsel bestätigt das Konto (OF-05) | ja, wie die Erstbestätigung (2026-11-14, Betreiber) | eine Bedeutung von „bestätigt“; der Klick beweist den Zugriff auf das Postfach → Feature `18` |
