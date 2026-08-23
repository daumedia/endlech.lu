# B19 · Admin-Zugang & Dashboard — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

`ROLE_ADMIN` öffnet einen eigenen Bereich unter `/{locale}/admin` mit Seitenleiste,
Kennzahlenübersicht und einem Sprachumschalter, der die Wahl in der Sitzung hält. Alles
Weitere (Restaurants, Vorschläge, Wartelisten, Finanzen) hängt an dieser Rollenschranke.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B02 | rekonstruiert | die Rolle kommt aus der angemeldeten Sitzung |

Umgekehrt hängen an B19: B20, B21, B22, B18.

## User Stories

- **US-01** · Als Betreiber möchte ich auf einen Blick sehen, wie viele Restaurants,
  Nutzer, Bilder und offene Vorschläge es gibt.
- **US-02** · Als Betreiber möchte ich die zuletzt angelegten Restaurants und Nutzer
  sehen.
- **US-03** · Als Betreiber möchte ich die Oberflächensprache umstellen, ohne die URL
  zu wechseln.

## Nicht im Scope

- Die einzelnen Verwaltungsbereiche → B20, B21, B22, B18
- Nutzerverwaltung — **existiert nicht**, siehe FB-01

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Gast ruft `/{locale}/admin` auf, wenn die Anfrage
  durchläuft, dann wird er zur Anmeldung geleitet.
- **AK-02** · Angenommen, ein angemeldeter Nutzer **ohne** `ROLE_ADMIN` ruft
  `/{locale}/admin` auf, wenn die Anfrage durchläuft, dann antwortet der Server mit 403.
- **AK-03** · Angenommen, ein Admin ruft `/{locale}/admin` auf, wenn die Seite lädt,
  dann zeigt sie sieben Kennzahlen: Restaurants gesamt, davon verifiziert, offene
  Vorschläge, Nutzer, Bilder, Restaurants diesen Monat, Nutzer diesen Monat.
- **AK-04** · Angenommen, das Dashboard lädt, wenn die unteren Blöcke betrachtet werden,
  dann stehen dort die je fünf zuletzt angelegten Restaurants und Nutzer.
- **AK-05** · Angenommen, „diesen Monat" wird berechnet, wenn der Bezug geprüft wird,
  dann zählt ab `first day of this month midnight` — nicht die letzten 30 Tage.
- **AK-06** · Angenommen, ein Admin wählt im Kopfband eine Sprache, wenn die Anfrage
  durchläuft, dann wird sie in der Sitzung unter `_locale` gespeichert und er landet
  wieder auf der Seite, von der er kam.
- **AK-07** · Angenommen, ein unbekannter Sprachcode wird an `admin_set_locale`
  übergeben, wenn die Anfrage durchläuft, dann greift bereits das Routen-Requirement
  `lb|de|fr|en` und die Route wird nicht gefunden (404).
- **AK-08** · Angenommen, ein Admin ist im Verwaltungsbereich, wenn eine Seite lädt,
  dann sind Bottom-Navigation, Cookie-Banner und der Cookie-Link der Fußzeile
  ausgeblendet — erkannt am Routennamen-Präfix `admin_`.
- **AK-09** · Angenommen, eine Seitenleisten-Schaltfläche gehört zum aktuellen Bereich,
  wenn die Seite lädt, dann ist sie violett hervorgehoben; erkannt über
  `starts with 'admin_<bereich>'`, beim Dashboard über exakten Vergleich.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-10** ⚠ · Angenommen, ein Admin wählt eine Sprache, wenn er danach eine
  Verwaltungsseite aufruft, dann steht in der URL weiterhin der alte Sprachcode.
  *(So verhält sich der Code heute: `AdminLocaleController` schreibt `_locale` in die
  Sitzung, aber es gibt keinen `LocaleSubscriber`, der den Wert beim nächsten Request
  wieder anwendet — die Routen tragen die Sprache im Pfad. Ob die Umstellung
  tatsächlich wirkt, hängt daran, ob die Weiterleitung an das Referer-Ziel den
  Sprachanteil mitführt. Der Wert in der Sitzung ist ohne Leser toter Zustand.)*

- **AK-11** ⚠ · Angenommen, jemand ruft `admin_set_locale` mit einem `Referer`-Header
  auf, der auf eine fremde Domain zeigt, wenn die Anfrage durchläuft, dann leitet der
  Server dorthin weiter.
  *(So verhält sich der Code heute: `return $this->redirect($referer ?: …)` ohne jede
  Prüfung auf die eigene Herkunft. Folge: Open Redirect. Der Endpunkt verlangt zwar
  `ROLE_ADMIN`, aber ein Admin ist genau das Ziel, für das man eine
  Weiterleitungskette baut.)*

### Datenschutz und Missbrauchsschutz

- **AK-12** · Angenommen, das Dashboard lädt, wenn geprüft wird, welche
  personenbezogenen Daten sichtbar werden, dann sind es Name, E-Mail-Adresse und
  Anmeldezeitpunkt der fünf zuletzt registrierten Nutzer.
- **AK-13** · Angenommen, jemand ohne `ROLE_ADMIN` versucht irgendeine
  `admin_*`-Route, wenn die Anfrage durchläuft, dann greift die Schranke **zweifach**:
  `access_control` auf `^/[a-z]{2}/admin` und `#[IsGranted('ROLE_ADMIN')]` an jeder
  Controller-Klasse.
- **AK-14** · Angenommen, `roles` eines Nutzers wird betrachtet, wenn geprüft wird, wie
  `ROLE_ADMIN` vergeben wird, dann geschieht das **ausschließlich** über einen direkten
  Datenbankeingriff oder die Fixtures — es gibt keine Oberfläche dafür.

## Edge Cases

- **EC-01** · Die Admin-Shell erbt Kopf- und Fußzeile der öffentlichen Seite, weil sie
  nur `{% block body %}` füllt. Drei Stellen entschärfen die Kollision einzeln
  (Bottom-Nav, Cookie-Banner, Cookie-Link).
- **EC-02** · `admin_set_locale` ohne `Referer` → Rückfall auf `admin_dashboard`.
- **EC-03** · Die Seitenleiste ist `sticky top-24`, rechnet also mit der 80 px hohen
  Kopfzeile plus Abstand. Ändert sich `h-20` in `base.html.twig`, verrutscht sie.

## Fehlbestand

- **FB-01 · Keine Nutzerverwaltung.** Kein Weg, Rollen zu vergeben, Konten zu sperren
  oder zu löschen. *Folge:* Ein zweiter Admin lässt sich nur per SQL anlegen; ein
  missbräuchliches Konto nur per SQL stilllegen. Bei einem Alleinbetrieb tragbar, aber
  es ist eine bewusste Entscheidung, die nirgends festgehalten ist.
- **FB-02 · Kein Audit-Log.** Keine Aufzeichnung, wer wann was verifiziert, genehmigt,
  gelöscht oder im Status geändert hat. Einzige Ausnahme: `Restaurant.verifiedBy`.
  *Folge:* Bei einem Fehler ist nicht rekonstruierbar, wer ihn ausgelöst hat.
- **FB-03 · Keine zweite Stufe für den Verwaltungszugang.** Siehe B02/FB-03. Ein
  erratenes Admin-Passwort öffnet alles — und B02 hat kein Rate Limit.
- **FB-04 · Open Redirect in `admin_set_locale`.** Siehe AK-11.
- **FB-05 · Kein Rate Limit auf Verwaltungsschreibvorgängen.** Alle Admin-POSTs sind
  CSRF-geschützt, aber ungedrosselt.
- **FB-06 · Die Kennzahlen sind ungecacht.** Jeder Dashboard-Aufruf löst acht
  Zählabfragen aus. Bei der aktuellen Größe folgenlos, aber es gibt bereits einen
  Cache-Pool für genau diesen Zweck (`cache.open_stats`, B16).

## Offene Fragen

- **OF-01** · Soll es eine Nutzerverwaltung geben (FB-01)? Ohne sie ist auch das
  Löschen eines Kontos auf Nutzerwunsch (B04/FB-01) nicht durchführbar. — Betreiber
- **OF-02** · Wirkt der Admin-Sprachumschalter tatsächlich (AK-10)? Ohne Leser ist der
  Sitzungswert wirkungslos. — im Browser nachzuprüfen

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Eigenes Admin-Layout | nein, erbt `base.html.twig` | spart ein Layout; der Preis sind drei Einzelausnahmen |
| 2 | Kennzahlen in einem Service | `AdminStatsService` | hält den Controller schlank; die Methoden sind reine Durchreichen |
| 3 | Rollenschranke doppelt | `access_control` + `#[IsGranted]` | Gürtel und Hosenträger; die Attribute wirken auch, wenn das Pfadmuster einmal nicht greift |
| 4 | Admin-Leitfarbe Violett | statt Cyan | macht den Bereichswechsel sichtbar |
| 5 | Sprache in der Sitzung statt in der URL | Sitzung | Grund nicht erkennbar — siehe AK-10 |
