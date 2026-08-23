# B21 · Vorschläge prüfen (Admin) — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein Admin sieht eingereichte Vorschläge nach Status gruppiert, öffnet einen im Detail
und entscheidet: genehmigen — dann entsteht daraus ein Restaurant — oder ablehnen, mit
einer Notiz. Dies ist die Moderationsstufe zwischen Nutzerbeitrag und öffentlichem
Eintrag.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B19 | rekonstruiert | Rollenschranke |
| B11 | rekonstruiert | die Vorschläge |

## User Stories

- **US-01** · Als Betreiber möchte ich offene Vorschläge sehen.
- **US-02** · Als Betreiber möchte ich einen Vorschlag mit einem Klick in ein Restaurant
  überführen.
- **US-03** · Als Betreiber möchte ich ablehnen und den Grund festhalten.

## Nicht im Scope

- Einreichen → B11
- Nachbearbeiten des entstandenen Restaurants → B20
- Benachrichtigung des Einreichers — **findet nicht statt**, siehe FB-02

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Admin ruft `/{locale}/admin/vorschlaege` auf, wenn die
  Seite lädt, dann stehen die Vorschläge in drei Gruppen: offen, genehmigt, abgelehnt.
- **AK-02** · Angenommen, ein Admin öffnet einen Vorschlag, wenn die Seite lädt, dann
  sieht er **alle** Felder — auch Ernährung, Zahlung, Sprachen und Kontakt — mit den
  dreiwertigen Antworten als Ja (grün), Nein (rot), Weiß nicht (grau).
- **AK-03** · Angenommen, ein Admin genehmigt einen Vorschlag, wenn das CSRF-Token
  `approve-suggestion-{id}` stimmt, dann entsteht ein neues `Restaurant` mit Name,
  Stadt, Emoji, den zwölf Merkmalen, Sprachen, Kontakt- und Sozialdaten, der Status
  wechselt auf `approved` und es erscheint `flash.suggestion_approved`.
- **AK-04** · Angenommen, der Vorschlag trägt einen Küchentyp als Freitext, wenn er
  genehmigt wird, dann wird er an Kommas zerlegt und jeder Teil über
  `findOrCreateByName()` zugeordnet — bestehende Küchen werden wiederverwendet,
  unbekannte neu angelegt.
- **AK-05** · Angenommen, ein Vorschlag wird genehmigt, wenn das entstandene Restaurant
  betrachtet wird, dann steht `submittedBy` auf dem **Einreicher**, nicht auf dem Admin.
- **AK-06** · Angenommen, ein Vorschlag wird genehmigt, wenn `isVerified` geprüft wird,
  dann ist es **false** — die Genehmigung ist keine Verifizierung.
- **AK-07** · Angenommen, ein Admin lehnt ab, wenn das CSRF-Token
  `reject-suggestion-{id}` stimmt, dann wechselt der Status auf `rejected`, die
  eingegebene Notiz wird gespeichert (leer → `null`) und es erscheint
  `flash.suggestion_rejected`.
- **AK-08** · Angenommen, ein CSRF-Token fehlt oder ist falsch, wenn genehmigt oder
  abgelehnt wird, dann geschieht **nichts** und es erscheint `flash.invalid_csrf`.
- **AK-09** · Angenommen, ein Gast oder `ROLE_USER` ruft eine dieser Routen auf, wenn
  die Anfrage durchläuft, dann greift die Rollenschranke.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-10** ⚠ · Angenommen, ein bereits genehmigter Vorschlag wird **erneut** genehmigt
  (Doppelklick, zweiter Tab, zurück-und-erneut-senden), wenn die Anfrage durchläuft,
  dann entsteht ein **zweites Restaurant** mit denselben Daten.
  *(So verhält sich der Code heute: `AdminSuggestionController::approve()` prüft den
  Status **nicht** — es gibt kein `if ($suggestion->getStatus() !== STATUS_PENDING)`.
  Der Vorgang ist nicht idempotent. Folge: Dubletten im öffentlichen Bestand, die
  niemandem auffallen, weil auch keine Dublettenprüfung existiert (B11/FB-04).)*

- **AK-11** ⚠ · Angenommen, ein Vorschlag enthält „Weiß nicht", wenn er genehmigt wird,
  dann wird daraus **„Nein"**.
  *(Identisch mit B11/AK-12, hier wiederholt, weil die Umwandlung an dieser Stelle
  geschieht. Der Code benennt es und verweist auf das Nachtragen durch den Admin — es
  gibt aber keinen Hinweis in der Oberfläche, welche Felder betroffen waren.)*

- **AK-12** ⚠ · Angenommen, ein Vorschlag wird abgelehnt und eine Notiz hinterlegt, wenn
  der Einreicher nachsieht, dann sieht er sie **nie** — es gibt keine Ansicht und keine
  Nachricht.
  *(`adminNote` wird erfasst und nur in der Verwaltung angezeigt. Ein Feld, dessen
  einziger denkbarer Zweck die Rückmeldung ist, erreicht seinen Adressaten nicht.)*

- **AK-13** ⚠ · Angenommen, ein Vorschlag wird genehmigt, wenn das entstandene
  Restaurant betrachtet wird, dann fehlen **Öffnungszeiten, Koordinaten und Maße** —
  der Vorschlag erhebt sie gar nicht erst.
  *(Folge für B16: Ein so entstandenes Restaurant startet mit einer niedrigen
  Punktzahl, weil nicht erfasste Merkmale als nicht erfüllt zählen.)*

### Datenschutz und Missbrauchsschutz

- **AK-14** · Angenommen, ein Vorschlag wird genehmigt, wenn geprüft wird, welche Daten
  öffentlich werden, dann sind es alle übernommenen Felder — **einschließlich
  Telefonnummer und E-Mail-Adresse des Betriebs**, die ein Dritter eingetragen hat.
- **AK-15** · Angenommen, beide schreibenden Endpunkte werden geprüft, wenn nach CSRF
  gesucht wird, dann tragen beide ein eigenes Token mit der ID des Vorschlags.
- **AK-16** · Angenommen, `admin_note` wird übermittelt, wenn geprüft wird, was damit
  geschieht, dann wird es ungeprüft übernommen (`$request->request->getString()`), ohne
  Längenbegrenzung.

## Edge Cases

- **EC-01** · Der Küchen-Freitext `""` erzeugt nach `explode(',')` einen leeren Teil,
  der übersprungen wird.
- **EC-02** · `findOrCreateByName()` legt bei einem Tippfehler dauerhaft eine neue
  Küche an (siehe B08).
- **EC-03** · Der Statuswechsel und die Restaurant-Anlage laufen in **einem** `flush()`
  — sie sind damit atomar.
- **EC-04** · Abgelehnte Vorschläge bleiben in der Datenbank; sie werden nie gelöscht.

## Fehlbestand

- **FB-01 · Keine Idempotenz beim Genehmigen.** Siehe AK-10. Der wichtigste Befund des
  Features und ein Einzeiler.
- **FB-02 · Keine Rückmeldung an den Einreicher.** Siehe AK-12; betrifft Genehmigung
  wie Ablehnung.
- **FB-03 · Keine Möglichkeit, vor der Genehmigung zu korrigieren.** Der Admin
  übernimmt die Daten unbesehen und bearbeitet danach in B20 nach — oder eben nicht.
- **FB-04 · Kein Audit-Log.** (B19/FB-02)
- **FB-05 · Keine Löschfrist für abgelehnte Vorschläge.** Siehe EC-04; sie enthalten
  einen Verweis auf den einreichenden Nutzer.
- **FB-06 · Kein Rate Limit.** (B19/FB-05)

## Offene Fragen

- **OF-01** · Soll `approve()` auf `STATUS_PENDING` prüfen (AK-10)? — Betreiber
- **OF-02** · Soll der Einreicher benachrichtigt werden (AK-12)? Die Mailinfrastruktur
  steht bereits (B01, B14). — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Genehmigen erzeugt ein neues Restaurant | statt den Vorschlag umzuwandeln | der Vorschlag bleibt als Beleg erhalten |
| 2 | `submittedBy` = Einreicher | nicht der Admin | der Beitrag soll dem zugerechnet werden, der ihn geleistet hat |
| 3 | Genehmigung ≠ Verifizierung | `isVerified` bleibt `false` | das Gütesiegel setzt eine eigene Prüfung voraus (B20) |
| 4 | „Weiß nicht" → „Nein" | ja | `Restaurant` kennt nur `bool`; ein Durchziehen hätte fünf weitere Stellen berührt |
| 5 | Küchen per Freitext, beim Genehmigen aufgelöst | so | der Einreicher soll nicht aus einer Liste wählen müssen |
| 6 | Drei Statusgruppen auf einer Seite | statt Filter | die Zahl der Vorschläge ist überschaubar |
