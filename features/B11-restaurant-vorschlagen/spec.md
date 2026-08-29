# B11 · Restaurant vorschlagen (Wizard) — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein angemeldeter, **bestätigter** Nutzer meldet ein Restaurant über einen fünfstufigen
Assistenten. Zwölf Fragen zu Barrierefreiheit, Ernährung und Zahlung sind
**Pflichtfragen mit drei Antworten** — Ja, Nein oder Weiß nicht. Der Vorschlag geht in
die Prüfung (B21), nicht direkt auf die Seite.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 | rekonstruiert | Konto **und** bestätigte Adresse |

## User Stories

- **US-01** · Als Nutzer möchte ich ein Restaurant melden, das mir aufgefallen ist.
- **US-02** · Als Nutzer möchte ich „Weiß nicht" antworten können, ohne dass daraus
  „gibt es nicht" wird.
- **US-03** · Als Nutzer möchte ich den Assistenten in Abschnitten durchlaufen, damit
  mich nicht dreißig Felder auf einmal erschlagen.

## Nicht im Scope

- Genehmigung und Übernahme → B21
- Anlegen über die REST-API → B23 (**umgeht diesen Weg vollständig**, siehe dort)
- Bearbeiten eines eingereichten Vorschlags — nicht vorgesehen

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Gast ruft `/{locale}/community/suggest` auf, wenn die
  Anfrage durchläuft, dann wird er zur Anmeldung geleitet.
- **AK-02** · Angenommen, ein angemeldeter Nutzer ist **nicht bestätigt**, wenn er die
  Seite aufruft, dann wird er mit `flash.suggest_verify_first` auf die Hinweisseite
  geleitet — dies ist die **einzige** Stelle im Projekt, an der die
  E-Mail-Bestätigung etwas erzwingt.
- **AK-03** · Angenommen, ein bestätigter Nutzer ruft die Seite auf, wenn sie lädt, dann
  sieht er den Assistenten mit fünf Schritten und einer Fortschrittsleiste.
- **AK-04** · Angenommen, eine der zwölf dreiwertigen Fragen bleibt unbeantwortet, wenn
  abgeschickt wird, dann antwortet der Server mit **422** und die Meldung
  `suggestion.answer_required` steht **am betroffenen Feld**, nicht am Formular.
- **AK-05** · Angenommen, ein Feld eines späteren Schritts ist fehlerhaft, wenn die
  422-Antwort gerendert wird, dann springt der Assistent auf den Schritt mit dem Fehler.
- **AK-06** · Angenommen, der Assistent wird geöffnet, wenn die dreiwertigen Felder
  betrachtet werden, dann ist **nichts vorausgewählt** — weder Ja noch Nein.
- **AK-07** · Angenommen, alle Pflichtangaben sind gemacht, wenn abgeschickt wird, dann
  entsteht ein `RestaurantSuggestion` mit Status `pending` und `suggestedBy` = aktueller
  Nutzer, und der Nutzer landet auf `/{locale}/community/thanks`.
- **AK-08** · Angenommen, ein dreiwertiges Feld wird bedient, wenn die Bedienung geprüft
  wird, dann sind die Optionen **echte Radios** (`sr-only`, nicht `hidden`), damit
  Tastatur und Screenreader funktionieren; der Fokus ist über
  `peer-focus-visible:ring-inset` sichtbar.
- **AK-09** · Angenommen, JavaScript ist aktiv, wenn ein Schritt verlassen wird, dann
  prüft der Assistent die dreiwertigen Pflichtfragen bereits im Browser.
- **AK-10** · Angenommen, ein Vorschlag wurde eingereicht, wenn der Nutzer sein Profil
  aufruft, dann taucht das Restaurant dort **erst nach der Genehmigung** auf — die
  Liste zeigt `Restaurant`-Datensätze, keine Vorschläge.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-11** ⚠ · Angenommen, ein bestätigter Nutzer schickt beliebig viele Vorschläge ab,
  wenn sie nacheinander eintreffen, dann greift **keine** Sperre.
  *(Kein Rate-Limiter auf `community_vorschlagen`. Gemildert dadurch, dass ein Konto und
  eine bestätigte Adresse nötig sind und jeder Vorschlag eine Prüfung durchläuft — die
  Folge ist Arbeitsaufwand für den Betreiber, nicht öffentlicher Schaden. Zum Vergleich:
  Über `POST /api/v1/restaurants` (B23) entsteht ohne Prüfung ein **öffentlicher**
  Eintrag, ebenfalls ungedrosselt.)*

- **AK-12** ⚠ · Angenommen, ein Nutzer antwortet auf alle zwölf Fragen mit „Weiß nicht",
  wenn der Vorschlag genehmigt wird, dann werden **alle zwölf Merkmale als „Nein"**
  übernommen.
  *(So verhält sich der Code heute: `AdminSuggestionController::approve()` schreibt
  `$suggestion->isWheelchairAccessible()?->isYes() ?? false`. Die Unterscheidung, für
  die das dreiwertige Modell überhaupt eingeführt wurde, geht bei der Übernahme
  verloren — `Restaurant` kennt nur `bool`. Der Kommentar im Code benennt das und
  verweist auf das Nachtragen durch den Admin. Folge für B16: Die Punktzahl bewertet
  „unbekannt" wie „nicht vorhanden", was zum dokumentierten Risiko 2 im PRD beiträgt.)*

### Datenschutz und Missbrauchsschutz

- **AK-13** · Angenommen, ein Vorschlag entsteht, wenn nach personenbezogenen Daten
  gesucht wird, dann sind es: der **einreichende Nutzer** (Verweis) sowie Kontaktdaten
  des vorgeschlagenen Betriebs (Telefon, E-Mail, Website, Sozialkonten) — von einem
  Dritten eingetragen, ohne dessen Zustimmung.
- **AK-14** · Angenommen, der einreichende Nutzer wird gelöscht, wenn der Vorschlag
  betrachtet wird, dann bleibt er bestehen mit `suggestedBy = NULL`
  (`ON DELETE SET NULL`).
- **AK-15** · Angenommen, der Assistent wird abgeschickt, wenn der CSRF-Schutz geprüft
  wird, dann greift der stateless `submit`-Token des Symfony-Formularsystems.

## Edge Cases

- **EC-01** · `'error_bubbling' => false` ist bei den dreiwertigen Feldern **Pflicht**:
  Ein expanded `ChoiceType` ist compound, dort ist `error_bubbling` per Vorgabe `true`.
  Ohne die Zeile landeten alle zwölf Fehler am Wurzelformular, `form_errors(feld)`
  bliebe leer und die Schritterkennung im Template griffe nie.
- **EC-02** · Keine Vorauswahl entsteht aus `placeholder: false` **und** Entity-Wert
  `null` — ein ungültiger Submit liefert dadurch verlässlich 422.
- **EC-03** · `?TriState` statt `?bool`: Mit `?bool` wäre „Weiß nicht" = `null` und
  ununterscheidbar von „noch nicht beantwortet" — genau die Unterscheidung, die die
  Pflichtvalidierung braucht.
- **EC-04** · Der Küchentyp ist im Vorschlag ein **Freitextfeld** (`cuisine`,
  VARCHAR 80), nicht die Entity aus B08; beim Genehmigen wird er an Kommas zerlegt.

## Fehlbestand

- **FB-01 · Kein Rate Limit.** Siehe AK-11.
- **FB-02 · Kein Weg, einen eingereichten Vorschlag zu sehen oder zurückzuziehen.** Der
  Nutzer erfährt nichts über den Verlauf; erst nach Genehmigung erscheint ein Eintrag
  im Profil. Bei Ablehnung erfährt er es **gar nicht** — `adminNote` wird erfasst, aber
  niemandem gezeigt.
- **FB-03 · Keine Benachrichtigung bei Genehmigung oder Ablehnung.** Kein Mailversand
  in `AdminSuggestionController`.
- **FB-04 · Keine Dublettenprüfung.** Dasselbe Restaurant kann beliebig oft
  vorgeschlagen werden; erst der Admin bemerkt es.
- **FB-05 · Keine Prüfung der Kontaktdaten Dritter.** Siehe AK-13.
- **FB-06 · Kein Zwischenspeichern des Assistenten.** Wer auf Schritt 5 den Browser
  schließt, beginnt von vorn.

## Offene Fragen

- **OF-01** · Soll „Weiß nicht" bis zum `Restaurant` durchgereicht werden (AK-12)? Der
  Verzicht ist im Code begründet — es hätte Repository-Filter, den
  `RestaurantTransformer` (Boolean-Vertrag der iOS-API), fünf Templates und die Fixtures
  berührt. Für die Aussagekraft der Punktzahl (B16) wäre es dennoch der richtige
  Schritt. — Betreiber
  **Entschieden 2026-08-25:** Anders gelöst (BF-49, 2026-08-25). `Restaurant` bleibt zweiwertig — eine neue Spalte `assessedFeatures` hält fest, wonach jemand gesehen hat. Damit ist „weiß nicht" von „nein" unterscheidbar, ohne Repository-Filter, Transformer, Templates und Fixtures anzufassen.

- **OF-02** · Soll der Einreicher über den Ausgang informiert werden (FB-02/FB-03)?
  `adminNote` wird bereits erfasst und wäre der Text dafür. — Betreiber
  **Entschieden 2026-08-25:** Ja, umgesetzt (BF-55, 2026-08-25). Die Ablehnungsmail trägt `adminNote` und geht in der Sprache raus, in der eingereicht wurde.

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Zweiwertig oder dreiwertig | dreiwertig, Pflicht | eine nicht angehakte Checkbox bedeutete zweierlei zugleich — „gibt es nicht" und „weiß ich nicht"; für eine Barrierefreiheitsplattform ist der Unterschied wesentlich |
| 2 | Enum statt `?bool` | `TriState` | mit `?bool` wäre „Weiß nicht" nicht von „unbeantwortet" unterscheidbar |
| 3 | Getternamen unverändert | `isWheelchairAccessible(): ?TriState` | Symfony PropertyAccess und Twig lösen die Properties über genau diese Namen auf |
| 4 | Übernahme als „Nein" | `?->isYes() ?? false` | ein Durchziehen bis `Restaurant` hätte fünf weitere Stellen berührt |
| 5 | Radios `sr-only` statt `hidden` | `sr-only` | `hidden` nähme sie aus dem Zugänglichkeitsbaum |
| 6 | Assistent statt einer langen Seite | fünf Schritte | dreißig Felder auf einmal schrecken ab |
| 7 | Migration `1 → 'yes'`, `0 → 'unknown'` | nicht `'no'` | ein leeres Häkchen bedeutete unter dem alten Hinweistext „unbekannt" |
