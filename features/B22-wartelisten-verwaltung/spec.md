# B22 · Wartelisten-Verwaltung (Admin) — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein Admin sieht beide Wartelisten in **einer** Tabelle, filtert nach Quelle, Status und
Organisationstyp, öffnet einen Eintrag im Detail, pflegt seinen Status entlang des
Trichters und ordnet einer Partneranmeldung ein bestehendes Restaurant zu.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B19 | rekonstruiert | Rollenschranke und Shell |
| B14, B15 | rekonstruiert | die Einträge |

## User Stories

- **US-01** · Als Betreiber möchte ich alle Anmeldungen an einem Ort sehen.
- **US-02** · Als Betreiber möchte ich nach Quelle, Status und Typ filtern.
- **US-03** · Als Betreiber möchte ich den Status entlang des Vertriebstrichters setzen.
- **US-04** · Als Betreiber möchte ich einer Partneranmeldung das passende Restaurant
  zuordnen.

## Nicht im Scope

- Anlegen von Einträgen — das geschieht öffentlich (B14, B15)
- Löschen — **existiert nicht**, siehe FB-01
- E-Mail-Versand aus der Verwaltung heraus

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Admin ruft `/{locale}/admin/warteliste` auf, wenn die
  Seite lädt, dann stehen Partner- und Organisationseinträge in **einer** Tabelle,
  absteigend nach Anlagedatum.
- **AK-02** · Angenommen, beide Quellen liefern Einträge, wenn die Sortierung geprüft
  wird, dann sind sie **durchmischt** nach Datum — nicht erst alle Partner-, dann alle
  Organisationseinträge.
- **AK-03** · Angenommen, `?source=partner` steht in der Adresse, wenn die Seite lädt,
  dann erscheinen nur Partnereinträge; `?source=organisation` entsprechend.
- **AK-04** · Angenommen, `?type=commune` steht in der Adresse, wenn die Seite lädt,
  dann wird die Quelle **implizit** auf `organisation` gesetzt.
- **AK-05** · Angenommen, `?status=confirmed` steht in der Adresse, wenn die Seite lädt,
  dann erscheinen nur bestätigte Einträge beider Quellen.
- **AK-06** · Angenommen, `?sort=asc` steht in der Adresse, wenn die Seite lädt, dann
  ist die Reihenfolge aufsteigend; jeder andere Wert bedeutet absteigend.
- **AK-07** · Angenommen, ein unbekannter Status oder Typ steht in der Adresse, wenn die
  Seite lädt, dann wird er verworfen und die Liste ungefiltert gezeigt — keine
  Exception.
- **AK-08** · Angenommen, ein Admin öffnet einen Eintrag, wenn die Seite lädt, dann
  sieht er alle erfassten Felder inklusive Freitext, Einwilligungszeitpunkt, Sprache und
  Quelle.
- **AK-09** · Angenommen, ein Admin setzt einen Status, wenn das Formular abgeschickt
  wird, dann wird er gespeichert und es erscheint `flash.waitlist_status_changed` mit
  dem übersetzten Statusnamen.
- **AK-10** · Angenommen, ein Eintrag war noch **nicht bestätigt** und wird auf einen
  Status ungleich `pending` gesetzt, wenn gespeichert wird, dann wird `confirmedAt`
  automatisch auf jetzt gesetzt.
- **AK-11** · Angenommen, das CSRF-Token `waitlist-status-{id}` fehlt oder ist falsch,
  wenn abgeschickt wird, dann ändert sich nichts und es erscheint `flash.invalid_csrf`.
- **AK-12** · Angenommen, ein unbekannter Statuswert wird übermittelt, wenn abgeschickt
  wird, dann ändert sich nichts und es erscheint `flash.waitlist_status_invalid`.
- **AK-13** · Angenommen, ein Admin ordnet einer Partneranmeldung ein Restaurant zu,
  wenn abgeschickt wird, dann steht die Verknüpfung und es erscheint
  `flash.partner_restaurant_linked` mit dem Namen.
- **AK-14** · Angenommen, `restaurant = 0` wird übermittelt, wenn abgeschickt wird, dann
  wird die Verknüpfung **gelöst** und es erscheint `flash.partner_restaurant_unlinked`.
- **AK-15** · Angenommen, eine nicht existierende Restaurant-ID wird übermittelt, wenn
  abgeschickt wird, dann ändert sich nichts und es erscheint
  `flash.partner_restaurant_missing`.
- **AK-16** · Angenommen, ein Gast oder ein Nutzer ohne `ROLE_ADMIN` ruft irgendeine
  Route dieses Bereichs auf, wenn die Anfrage durchläuft, dann greift die Rollenschranke.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-17** ⚠ · Angenommen, die Liste lädt, wenn die Zahl der Einträge wächst, dann
  werden **alle** geladen — es gibt weder Seitenblättern noch eine Obergrenze.
  *(So verhält sich der Code heute: `findFiltered()` liefert vollständige Ergebnisse,
  beide Listen werden im Speicher zusammengeführt und mit `usort()` sortiert. Bei der
  heutigen Größe folgenlos; die Restaurantliste (B20) und die öffentliche Suche (B05)
  blättern dagegen.)*

- **AK-18** ⚠ · Angenommen, ein Admin öffnet eine Partneranmeldung, wenn die Auswahlliste
  für die Restaurantzuordnung aufgebaut wird, dann werden **alle** Restaurants der
  Datenbank geladen (`findBy([], ['name' => 'ASC'])`).
  *(Dieselbe Einordnung wie AK-17 — heute unkritisch, wächst aber mit dem Kernbestand
  der Anwendung mit.)*

- **AK-19** ⚠ · Angenommen, ein Admin ändert einen Status, wenn später gefragt wird, wer
  das war, dann lässt sich das nicht beantworten — es wird nur `updatedAt`
  fortgeschrieben, kein Bearbeiter.
  *(Kein Audit-Log, siehe B19/FB-02. Bei einem Alleinbetrieb tragbar, bei einer
  Erweiterung des Teams nicht.)*

### Datenschutz und Missbrauchsschutz

- **AK-20** · Angenommen, ein Admin ruft die Liste auf, wenn geprüft wird, welche
  personenbezogenen Daten sichtbar werden, dann sind es alle Felder beider
  Wartelisten-Entities — Name, Rolle, E-Mail, Telefon, Freitext.
- **AK-21** · Angenommen, ein Statuswechsel oder eine Zuordnung wird abgeschickt, wenn
  der Schutz geprüft wird, dann trägt **jeder** schreibende Endpunkt ein eigenes
  CSRF-Token mit der ID des Eintrags (`waitlist-status-{id}`, `waitlist-link-{id}`).
- **AK-22** · Angenommen, eine ID aus dem Pfad wird aufgelöst, wenn geprüft wird wie,
  dann über den `ParamConverter` mit Requirement `\d+`; ein nicht existierender Eintrag
  ergibt 404.

## Edge Cases

- **EC-01** · Ein gesetzter Organisationstyp überschreibt eine widersprüchliche
  Quellenangabe (`?source=partner&type=commune` → nur Organisationen).
- **EC-02** · Die Zeilen werden auf ein gemeinsames Array-Format normalisiert
  (`partnerRow()` / `organisationRow()`), damit das Template keine Fallunterscheidung
  über Entity-Klassen braucht.
- **EC-03** · `WaitlistStatus` führt **sechs** Werte: `pending`, `confirmed`,
  `contacted`, **`qualified`**, `converted`, `declined`. `qualified` sitzt zwischen
  Kontakt und Abschluss, weil bei Gemeinden und Unternehmen regelmäßig eine Vorprüfung
  dazwischenliegt.
- **EC-04** · `applyStatus()` arbeitet gegen `WaitlistEntryInterface` und funktioniert
  deshalb für beide Entities unverändert.

## Fehlbestand

- **FB-01 · Kein Löschweg.** Kein `remove()` im gesamten Controller. *Folge:* Weder auf
  Widerruf einer Einwilligung (B14/AK-22) noch auf Löschverlangen kann reagiert werden,
  ohne die Datenbank direkt anzufassen. Das ist die Verwaltungsseite derselben Lücke.
- **FB-02 · Kein Export.** Kein CSV, keine Zwischenablage — ausgerechnet für den
  Datenbestand, mit dem man arbeiten würde.
- **FB-03 · Keine Suche.** Nur Filter über feste Werte; nach einer E-Mail-Adresse oder
  einem Namen lässt sich nicht suchen.
- **FB-04 · Kein Audit-Log.** Siehe AK-19.
- **FB-05 · Kein Seitenblättern.** Siehe AK-17.
- **FB-06 · Keine Notiz je Eintrag aus der Verwaltung heraus.** `PartnerWaitlistEntry`
  hat kein `adminNote`-Feld — anders als `RestaurantSuggestion` (B21), wo es das gibt.

## Offene Fragen

- **OF-01** · Löschen: soll es harte Löschung oder Anonymisierung sein? Bei einem
  bestätigten Interessenten mit Einwilligungsnachweis ist das nicht dasselbe. —
  Betreiber
- **OF-02** · Braucht die Verwaltung eine Notizfunktion (FB-06)? — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Eine Liste oder zwei | eine, normalisiert | ein Betreiber denkt in „Anfragen", nicht in Entity-Klassen |
| 2 | Normalisierung im Controller | statt im Template | das Template bleibt frei von Klassenprüfungen |
| 3 | Erneutes Sortieren nach dem Zusammenführen | `usort()` | sonst stünden erst alle Partner-, dann alle Organisationseinträge |
| 4 | Statuslogik gegen das Interface | `applyStatus(WaitlistEntryInterface …)` | eine Methode für beide Typen |
| 5 | `confirmedAt` bei manuellem Weitersetzen nachtragen | ja | sonst fehlte der Bestätigungszeitpunkt bei Einträgen, die der Admin von Hand weiterschiebt |
| 6 | CSRF-Token je Eintrag statt global | `waitlist-status-{id}` | ein Token aus einer Ansicht taugt nicht für einen anderen Eintrag |
