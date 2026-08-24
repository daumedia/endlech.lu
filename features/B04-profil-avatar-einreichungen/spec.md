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

- **AK-12** ⚠ · Angenommen, ein Nutzer ändert seine E-Mail-Adresse auf eine beliebige
  andere, wenn abgeschickt wird, dann wird sie sofort übernommen und `is_verified`
  bleibt auf `true` — es geht **keine** Bestätigungsmail an die neue Adresse.
  *(So verhält sich der Code heute: `ProfileType` führt `email` als reguläres Feld,
  `ProfileController::edit()` ruft nur `flush()`. Folge: Der Bestätigungsstatus gilt
  für eine Adresse, die nie bestätigt wurde. Wer ein Konto übernimmt, kann die Adresse
  auf seine eigene umschreiben und ist damit dauerhaft drin.)*

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

- **AK-14** ⚠ · Angenommen, jemand kennt eine gültige Sitzung, wenn er das aktuelle
  Passwort im Änderungsformular durchprobiert, dann greift **keine** Sperre.
  *(Kein Rate-Limiter auf `app_profile_password`; `isPasswordValid()` wird beliebig oft
  gerufen. Weniger gewichtig als B02/AK-11, weil eine Sitzung Voraussetzung ist.)*

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
- **FB-03 · Keine erneute Bestätigung bei E-Mail-Änderung.** Siehe AK-12.
- ~~**FB-04 · Keine Sitzungsinvalidierung bei Passwortänderung.**~~ **Entfällt —
  2026-08-24 widerlegt.** Symfony entwertet fremde Sitzungen und `remember_me`-Cookies
  von sich aus; siehe AK-13. Der Eintrag bleibt durchgestrichen stehen, damit
  nachvollziehbar ist, dass hier einmal eine Lücke vermutet wurde, die es nicht gibt.
- **FB-05 · Keine Benachrichtigung an die alte Adresse**, wenn E-Mail oder Passwort
  geändert werden. Der übliche Schutz gegen stille Kontoübernahme fehlt.
- **FB-06 · Avatare werden nicht verkleinert oder neu kodiert.** Die hochgeladene Datei
  wird unverändert abgelegt (im Bestand liegt eine mit 1,1 MB). *Folge:* Metadaten wie
  GPS-Koordinaten aus einem Handyfoto bleiben in der öffentlich abrufbaren Datei
  erhalten.
- **FB-07 · Kein Rate Limit auf der Passwortänderung.** Siehe AK-14.
- **FB-08 · Dateiname ist nicht kryptografisch zufällig.** `uniqid('', true)` ist
  zeitbasiert. Für öffentliche Avatare unkritisch, wäre bei nicht-öffentlichen Dateien
  aber ein Aufzählungspfad.

## Offene Fragen

- **OF-01** · Wann kommt der Kontolöschweg (FB-01)? Er berührt `Restaurant.submittedBy`
  (SET NULL), `WebauthnCredential` (CASCADE), `RestaurantSuggestion.suggestedBy`
  (SET NULL) und die Avatardatei — die Kaskaden sind bereits alle gesetzt, es fehlt nur
  der Auslöser. — Betreiber
- **OF-02** · Soll eine E-Mail-Änderung erneut bestätigt werden (AK-12)? — Betreiber
- **OF-03** · Sollen Avatare beim Upload neu kodiert werden (FB-06)? Ohne
  Bildbibliothek im Projekt wäre das eine neue Abhängigkeit. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Drei Formulare auf einer Seite | ja, mit eigenen `action`-Zielen | jede Änderung ist ein eigener POST-Endpunkt; die Anzeigeroute bleibt GET-only |
| 2 | Avatar-Feld `mapped: false` | ja | der Dateiname entsteht erst im Service |
| 3 | Alte Datei beim Upload löschen | ja | verhindert Waisen; die einzige Aufräumlogik im Projekt neben `ImageUploadService` |
| 4 | Upload im Web-Root | `public/uploads/avatars` | direkte Auslieferung ohne Controller — schnell, aber öffentlich (AK-16) |
| 5 | CSRF beim Avatar-Löschen von Hand | `isCsrfTokenValid('delete-avatar', …)` | es ist kein Symfony-Formular, sondern ein nackter POST |
| 6 | Passwortänderung ohne Abmeldung | so | Grund nicht erkennbar — siehe FB-04 |
