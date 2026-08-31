# Datenschutz — Verarbeitungen und Auftragsverarbeiter

Stand: 2026-08-29 · angelegt mit Feature `04` (Marketing-Kontakte in Brevo)

Diese Datei ist die **interne** Dokumentation. Die Erklärung für Besucher steht
unter `/legal` (`templates/impressum/index.html.twig`, Abschnitt „Datenschutz").
Beide müssen zusammenpassen: Was hier als Empfänger steht und dort nicht, ist
eine Lücke in der Erklärung — nicht in dieser Datei.

> ✅ **Datenschutzstufe: B — bestätigt am 2026-08-30** (Feature `06`, OF-04).
> Übliche Personendaten, keine besonderen Kategorien nach Art. 9 DSGVO als
> *erhobene* Daten.
>
> **Begründung:** Die Plattform erhebt Daten über **Restaurants**, nicht über
> Gesundheit. Ein Konto führt Name, E-Mail-Adresse und Avatar. Eine Angabe nach
> Art. 9 kann ausschließlich **unaufgefordert im Freitext** erscheinen — in der
> Nachricht einer Wartelisten-Anmeldung, im Meldeformular auf
> `/barrierefreiheit` oder seit Feature `06` in einer Board-Idee. Für genau
> diesen Fall gelten die Maßnahmen unten.
>
> ⚠ **Wer ein Feld ergänzt, das eine Gesundheitsangabe strukturiert erfasst —
> ein Ankreuzfeld „Rollstuhl", eine Auswahl „Art der Einschränkung" —, hebt
> diese Einstufung auf.** Dann ist Stufe C fällig: Verarbeitungsverzeichnis
> nach Art. 30, Datenminimierung als Entwurfsprinzip, Verschlüsselung auf
> Feldebene prüfen, Folgenabschätzung erwägen.

---

## Auftragsverarbeiter

### Brevo (Sendinblue SAS)

| | |
|---|---|
| **Anbieter** | Brevo SA (vormals Sendinblue), Frankreich |
| **Sitz** | EU — keine Drittlandsübermittlung nach Kapitel V DSGVO |
| **Zwecke** | (1) Versand von Transaktionsmails, (2) **seit Feature 04:** Führung eines Kontaktbestands für Werbe-Kampagnen |
| **Rechtsgrundlage** | Zweck 1: Art. 6 Abs. 1 lit. b (Vertrag/vorvertraglich) · Zweck 2: **Art. 6 Abs. 1 lit. a — Einwilligung** |
| **AV-Vertrag** | ⚠ **noch zu prüfen und zu datieren** — siehe unten |
| **Datum der Prüfung** | ⚠ offen |

⚠ **Der zweite Zweck ist neu und ändert die Art der Weitergabe grundlegend.**
Bis Feature 04 bekam Brevo nur die einzelne Nachricht, die es zustellen sollte.
Seither bekommt es einen **Bestand** — Adressen samt Zielgruppe und
Vertriebsstatus, dauerhaft gespeichert, zu einem anderen Zweck als dem Versand.
Das ist die erste Weitergabe dieser Art im Projekt.

**Übermittelte Daten** (abschließend, `App\Marketing\MarketingPayloadMapper`):

| Feld | Inhalt |
|---|---|
| `email` | E-Mail-Adresse |
| `ext_id` | interne Datensatz-Kennung |
| `CONTACT_NAME` | Name des Ansprechpartners |
| `ORGANISATION` | Restaurant- bzw. Organisationsname |
| `LOCALE` | Sprache |
| `ORIGIN` | Rolle im Vertrieb: Partner, Gemeinde, Unternehmen, Verein, Nutzerkonto |
| `FUNNEL_STATUS` | Vertriebsstatus |

**Ausdrücklich nicht übermittelt:**

- ⚠ **die Freitextnachricht** aus beiden Wartelisten. Auf einer
  Barrierefreiheitsplattform kann dort eine Gesundheitsangabe stehen und damit
  eine besondere Kategorie nach Art. 9 DSGVO. Das Auftragsbuch führt das Feld
  gar nicht erst — was nicht erfasst ist, kann nicht abfließen.
- Telefonnummer, Ort, Herkunftsquelle (`source`/UTM), jede IP-Adresse, jeder
  Token.

⚠ **`ORIGIN` bezeichnet die Rolle im Vertrieb, nicht die Person.** Es sagt
nicht, ob jemand selbst von einer Behinderung betroffen ist. Wer dieses Attribut
je um einen Wert erweitert, prüft diesen Satz zuerst.

**Einwilligung, Widerruf, Löschung:**

- Die Einwilligung ist eine eigene, **nicht vorangehakte** Checkbox in den drei
  Formularen (Partner-Warteliste, Organisations-Wartelisten, Registrierung). Sie
  ist **keine Bedingung** für die Anmeldung (Koppelungsverbot, Art. 7 Abs. 4).
- Nachgewiesen wird sie über den gespeicherten **Zeitpunkt**
  (`marketing_consent_at`) an der jeweiligen Quelle — Art. 7 Abs. 1 verlangt,
  die Einwilligung nachweisen zu können.
- Übertragen wird erst nach **bestätigter Adresse** (Double-Opt-In bzw.
  E-Mail-Verifikation).
- Der Widerruf über den Abmeldelink einer Kampagne erreicht uns über einen
  Webhook und sperrt die Adresse lokal; die Einwilligung an der Quelle wird
  gelöscht.
- Kontolöschung und Wartelisten-Widerruf entfernen den Brevo-Kontakt mit
  (Art. 17). Der Löschauftrag überlebt die Löschung seiner Quelle — dafür hat
  `marketing_contact` bewusst keinen Fremdschlüssel.
- Der Datenexport eines Kontos (Art. 20) gibt aus, ob und wann eingewilligt
  wurde.

⚠ **Offen (OF-06): eine Löschfrist für Werbe-Kontakte, die jahrelang nicht
reagieren, gibt es nicht.** Zeilen mit gesetztem `revoked_at` bleiben als Sperre
unbegrenzt stehen, damit der nächste Abgleich die Adresse nicht erneut einträgt.
Das ist derselbe fehlende Aufräumschritt wie bei den Wartelisten (B14/FB-02).

⚠ **Offen (OF-03): Öffnungs- und Klickverfolgung** ist in Brevo standardmäßig
eingeschaltet. Das PRD schließt Web-Analytics aus und begründet das mit
Datensparsamkeit; ob das auch für Kampagnen gilt, ist nicht entschieden.

### Community Feedback Board (Feature 06, seit 2026-08-30)

| | |
|---|---|
| **Zweck** | Öffentliches Sammeln und Beantworten von Ideen **zur Plattform** |
| **Rechtsgrundlage** | Art. 6 Abs. 1 lit. f — berechtigtes Interesse an der Weiterentwicklung; die Veröffentlichung geschieht auf Veranlassung der betroffenen Person selbst |
| **Empfänger** | keine. Der Beitragstext verlässt das System an **niemanden** außer den Verfasser selbst |
| **Speicherort** | eigene Datenbank (`board_idea`, `board_vote`) |

**Verarbeitete Daten**

| Feld | Inhalt |
|---|---|
| `title`, `description` | Freitext des Verfassers — **öffentlich sichtbar nach Freigabe** |
| `submitted_by_id` | Verweis auf das Konto; `NULL` nach dessen Löschung |
| `locale` | Sprache der Einreichung |
| `board_vote` | welches Konto welcher Idee zugestimmt hat — **nicht öffentlich**, nur die Summe erscheint |

⚠ **Der Beitragstext ist der erste öffentlich veröffentlichte Freitext des
Projekts.** Auf einer Barrierefreiheitsplattform steht darin mit hoher
Wahrscheinlichkeit eine Gesundheitsangabe des Verfassers („Ich bin auf einen
Rollstuhl angewiesen und wünsche mir …"). Vermeiden lässt sich das nicht — der
Text *ist* das Produkt. Eingegrenzt wird er dreifach:

- **Hinweis vor dem Absenden** (AK-16): Das Formular sagt ausdrücklich, dass der
  Text öffentlich wird und keine Gesundheits- oder Kontaktangaben enthalten soll.
- **Freigabe vor Veröffentlichung** (AK-71): Kein Beitrag wird ohne Sichtung
  öffentlich. Ein Text, der zu viel preisgibt, lässt sich vorher abfangen.
- **Kein Abfluss** (AK-53, AK-54): Der Text geht an keinen Auftragsverarbeiter.
  Die eine Mail an den Verfasser führt **Titel und Link, nicht den Volltext**;
  Fehlerberichte an Sentry enthalten keine Beitragstexte
  (`zend.exception_ignore_args=On`).

**Löschung und Auskunft**

- **Kontolöschung:** Wartende Einreichungen werden **mitgelöscht**.
  Veröffentlichte Ideen bleiben stehen, ihr Verfasserbezug wird auf `NULL`
  gesetzt — andere haben zugestimmt und das Team hat öffentlich geantwortet.
  Der Anzeigename wird bei jeder Anzeige aus dem Konto abgeleitet und
  verschwindet damit von selbst; es gibt **kein** eingefrorenes Namensfeld.
  Abgegebene Zustimmungen verschwinden vollständig, die Zahl sinkt entsprechend.
- **Löschfrist:** Nie freigegebene Einreichungen werden nach **zwölf Monaten**
  gelöscht (`app:board:cleanup`, zusätzlich täglich beim Öffnen der
  Moderationsschlange). Für veröffentlichte Ideen gibt es keine Frist — sie sind
  Teil einer öffentlichen Zusage.
- **Auskunft:** Der Datenexport eines Kontos führt seine eingereichten Ideen samt
  Status **und** die Ideen, denen es zugestimmt hat.

### Weitere Verarbeiter

| Dienst | Zweck | Sitz | Bemerkung |
|---|---|---|---|
| Cloudways (Hosting) | Betrieb der Anwendung und Datenbank | — | ⚠ Vertrag und Sitz hier noch nachzutragen |
| Sentry | Fehler-Tracking | EU (`ingest.de.sentry.io`, Frankfurt) | `send_default_pii: false` — keine IP-Adressen, Cookies, Request-Header oder Nutzerdaten. `zend.exception_ignore_args` bleibt auf `On`, damit keine Funktionsargumente (und damit keine Passwörter) in Stacktraces landen |
| Mobilité (HAFAS) | Haltestellen in der Nähe | Luxemburg | Es gehen **Koordinaten eines Restaurants** hin, keine Nutzerdaten |

---

## Was vor dem ersten echten Brevo-Lauf stehen muss

Das ist AK-34 aus Feature 04 und keine Nacharbeit:

- [ ] **AV-Vertrag mit Brevo geprüft und hier mit Datum eingetragen**
- [ ] **`/legal` nennt Brevo als Empfänger für Werbezwecke** — nicht nur als
      Versanddienstleister
- [x] ~~**OF-01 beantwortet** (Datenschutzstufe des Projekts)~~ — **am 2026-08-30
      auf Stufe B festgelegt**, siehe oben

Erst danach: `app:marketing:import --commit` bzw. der erste Cron-Lauf mit
gesetztem Schlüssel. **Kein Kontakt geht raus, bevor die Erklärung ihn nennt.**
