# B13 · Statische Inhaltsseiten — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Drei Seiten ohne Datenbankzugriff: `/about` (Mission, Person, Zeitleiste), `/criteria`
(wonach bewertet wird) und `/legal` (Impressum und Datenschutz, mit dem Anker, auf den
der Cookie-Banner verweist).

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| — | — | reine Vorlagen |

## User Stories

- **US-01** · Als Besucher möchte ich wissen, wer hinter der Seite steht.
- **US-02** · Als Restaurantbetreiber möchte ich wissen, wonach bewertet wird.
- **US-03** · Als Besucher möchte ich Impressum und Datenschutzerklärung finden.

## Nicht im Scope

- Cookie-Einstellungen → B26 (verlinkt hierher)
- Pflege der Inhalte — sie stehen fest in den Vorlagen

## Akzeptanzkriterien

- **AK-01** · Angenommen, `/{locale}/about` wird aufgerufen, wenn die Seite lädt, dann
  erscheinen Mission, Person und Zeitleiste.
- **AK-02** · Angenommen, `/{locale}/criteria` wird aufgerufen, wenn die Seite lädt,
  dann steht dort der Kriterienkatalog.
- **AK-03** · Angenommen, `/{locale}/legal` wird aufgerufen, wenn die Seite lädt, dann
  stehen Impressum und Datenschutz auf einer Seite.
- **AK-04** · Angenommen, der Cookie-Banner verlinkt auf den Datenschutz, wenn dem Link
  gefolgt wird, dann springt die Seite zum Abschnitt `#datenschutz`, der
  `scroll-mt-24` trägt — der Text landet also unterhalb der klebenden Kopfzeile.
- **AK-05** · Angenommen, eine der drei Seiten wird aufgerufen, wenn geprüft wird, ob
  eine Anmeldung nötig ist, dann nicht.
- **AK-06** · Angenommen, die Sprache wird gewechselt, wenn die Seite lädt, dann
  erscheinen die Inhalte in der gewählten Sprache — über die Übersetzungskataloge.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-07** ⚠ · Angenommen, `/{locale}/legal` wird betrachtet, wenn geprüft wird, ob die
  Datenschutzerklärung die tatsächliche Verarbeitung abbildet, dann lässt sich das aus
  dem Repository **nicht** feststellen — der Text steht fest in der Vorlage, und es gibt
  kein `docs/datenschutz.md`, gegen das er zu prüfen wäre.
  *(Die Anwendung übermittelt Daten an mindestens drei Empfänger — Brevo (Mailversand,
  B01/B14/B15), Sentry (Fehler, nur `prod`) und HAFAS (Koordinaten, B10) — und speichert
  E-Mail-Adressen Dritter mit Einwilligungszeitpunkt. Ob und wie vollständig das in der
  Erklärung steht, ist eine inhaltliche Frage, die diese Erfassung nicht beantworten
  kann.)*

- **AK-08** ⚠ · Angenommen, `/{locale}/criteria` wird gelesen, wenn nach der Herleitung
  der Punktzahl gesucht wird, dann steht sie dort nicht.
  *(`AccessibilityScore` wertet acht Merkmale gleichgewichtet und zählt nicht Erfasstes
  als nicht erfüllt (B16/AK-18). Die Seite, die genau das erklären sollte, tut es nicht
  — siehe B16/FB-04.)*

### Datenschutz und Missbrauchsschutz

- **AK-09** · Angenommen, die Seiten werden betrachtet, wenn nach personenbezogenen
  Daten gesucht wird, dann enthalten sie die **Impressumsangaben des Betreibers** —
  eine gesetzliche Pflichtangabe.
- **AK-10** · Angenommen, die Seiten werden aufgerufen, wenn geprüft wird, ob sie
  Datenbank oder externe Dienste berühren, dann nicht.

## Edge Cases

- **EC-01** · Alle drei Controller sind vier Zeilen lang und tragen `declare(strict_types=1)`
  — anders als die älteren Controller des Projekts.
- **EC-02** · Die Routen tragen **englische** Pfade (`/about`, `/criteria`, `/legal`),
  die Routennamen dagegen deutsche Bezeichner (`app_kriterien`, `app_impressum`).

## Fehlbestand

- **FB-01 · Kein `docs/datenschutz.md`.** Siehe AK-07. Das Artefakt ist in
  `~/.claude/sdd/artefakte.md` vorgesehen und existiert nicht; damit fehlt das
  PII-Inventar, das Löschkonzept, die Liste der Auftragsverarbeiter und die
  Rechtsgrundlagen.
- **FB-02 · Keine Verzeichnisse der Auftragsverarbeiter.** Für Brevo, Sentry und HAFAS
  ist aus dem Repository nicht ersichtlich, ob AV-Verträge vorliegen.
- **FB-03 · Keine Herleitung der Punktzahl.** Siehe AK-08.
- **FB-04 · Keine Cache-Header** auf Seiten, die sich nur bei einem Deploy ändern.
- **FB-05 · Kein Änderungsstand.** Weder Impressum noch Datenschutz tragen ein Datum
  der letzten Änderung.

## Offene Fragen

- **OF-01** · Wann entsteht `docs/datenschutz.md` (FB-01)? Es ist die Eingabe für
  `/sdd-betrieb` und die Grundlage, um AK-07 überhaupt beantworten zu können. —
  Betreiber
- **OF-02** · Soll der Kriterienkatalog die Punktzahl erklären (AK-08)? — Betreiber
  **Entschieden 2026-08-25:** Ja, umgesetzt (BF-66, 2026-08-25). Der Abschnitt „Wie die Punktzahl entsteht" nennt alle acht Merkmale und den Satz, auf den es ankommt: Nicht Erfasstes zählt wie nicht erfüllt.

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Inhalte in Vorlagen statt in der Datenbank | Vorlagen | sie ändern sich selten und gehören in die Versionsverwaltung |
| 2 | Impressum und Datenschutz auf einer Seite | ja | beides sind Pflichtangaben, die niemand sucht, wenn sie getrennt liegen |
| 3 | Englische Pfade | `/about`, `/criteria`, `/legal` | sprachneutral über alle vier Sprachfassungen |
| 4 | `scroll-mt-24` am Datenschutzanker | ja | ohne das läge die Überschrift hinter der klebenden Kopfzeile |
