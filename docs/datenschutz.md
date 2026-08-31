# Datenschutz — Verarbeitungen und Auftragsverarbeiter

Stand: 2026-08-29 · angelegt mit Feature `04` (Marketing-Kontakte in Brevo)

Diese Datei ist die **interne** Dokumentation. Die Erklärung für Besucher steht
unter `/legal` (`templates/impressum/index.html.twig`, Abschnitt „Datenschutz").
Beide müssen zusammenpassen: Was hier als Empfänger steht und dort nicht, ist
eine Lücke in der Erklärung — nicht in dieser Datei.

> ⚠ **Offen: die Datenschutzstufe des Projekts (OF-01).** Das PRD legt keine
> fest. Diese Datei geht von **Stufe B** aus (übliche Personendaten, keine
> besonderen Kategorien nach Art. 9 DSGVO) — so, wie `features/04-brevo-marketing-kontakte/spec.md`
> es annimmt. Die Annahme ist **nicht bestätigt**. Sie zu bestätigen oder zu
> korrigieren ist eine Betreiberentscheidung; fällt sie auf eine höhere Stufe,
> ist dieser Abschnitt und der Umfang der Übertragung an Brevo erneut zu prüfen.

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
- [ ] **OF-01 beantwortet** (Datenschutzstufe des Projekts)

Erst danach: `app:marketing:import --commit` bzw. der erste Cron-Lauf mit
gesetztem Schlüssel. **Kein Kontakt geht raus, bevor die Erklärung ihn nennt.**
