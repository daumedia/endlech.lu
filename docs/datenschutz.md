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

## Verantwortlicher

| | |
|---|---|
| **Verantwortlicher** (Art. 4 Nr. 7 DSGVO) | **DAUMEDIA S.A.R.L.-S** |
| **Sitz** | 13, rue de la Fontaine, L-3768 Tétange, Luxemburg |
| **Handelsregister** | RCS Luxembourg **B311765** (Gründung 27.07.2026, Eintragung 02.09.2026) |
| **Handelsermächtigung** | 10199120/0, erteilt vom Ministère de l’Économie |
| **Vertreten durch** | Michael Ferreira Rodrigues, Geschäftsführer |
| **Kontakt** | `info@endlech.lu` |

⚠ **Wechsel des Verantwortlichen am 2026-09-05.** Bis dahin verarbeitete Michael
Ferreira die Daten als **Privatperson**; seit der Eintragung der Gesellschaft ist sie
es. Das ist kein Formalium: Betroffenenrechte richten sich ab jetzt gegen die
S.à r.l.-S, und sie haftet für die Verarbeitung.

⚠ **Für die bereits erhobenen Daten ändert sich die Rechtsgrundlage nicht** — die
Einwilligungen gelten dem Angebot „Endlech.lu", das unverändert weiterbetrieben wird.
Ob der Übergang eine Information der Betroffenen erfordert, ist eine Frage, die
fachlicher Rat beantworten sollte; sie steht als **DS-05** unten.

⚠ **Keine MwSt-Nummer.** Die Gründungsurkunde nennt keine. Sobald sie vergeben ist,
gehört sie in `app.operator_vat` — das Impressum blendet die Zeile dann von selbst ein.

## Auftragsverarbeiter

### Brevo (Sendinblue SAS)

| | |
|---|---|
| **Anbieter** | Brevo SA (vormals Sendinblue), 7 rue de Madrid, 75008 Paris, Frankreich · Datenschutzbeauftragter: `dpo@brevo.com` |
| **Sitz** | Frankreich (EU) |
| **Drittlandsübermittlung** | ⚠ **ja — über Unterauftragsverarbeiter.** Siehe die Liste unten. Abgesichert über EU-US Data Privacy Framework und Standardvertragsklauseln |
| **Zwecke** | (1) Versand von Transaktionsmails, (2) **seit Feature 04:** Führung eines Kontaktbestands für Werbe-Kampagnen |
| **Rechtsgrundlage** | Zweck 1: Art. 6 Abs. 1 lit. b (Vertrag/vorvertraglich) · Zweck 2: **Art. 6 Abs. 1 lit. a — Einwilligung** |
| **AV-Vertrag** | **Appendix 3 der Brevo Terms of Service** („Data Processing Agreement") — gilt automatisch mit Annahme der AGB, **keine gesonderte Unterzeichnung nötig**. Bei Widersprüchen geht das DPA vor, „to the extent of that conflict" (Ziff. **2.1**) |
| **Gelesene Fassung** | **abgerufen am 2026-09-12**, Kopie in `qa/brevo-dpa/BREVO-Appendix-3-DPA-abgerufen-2026-09-12.pdf`. ⚠ Die Vorfassung (**Annex 2**, Stand 15.05.2024) liegt daneben und ist **überholt** — Brevo hat das Dokument neu gefasst, Aufbau und Nummerierung stimmen nicht mehr |
| **Löschung nach Vertragsende** | ⚠ **drei Monate, und nur auf Verlangen** (Ziff. **5.3**): „Upon termination of the Terms, We will, **upon your request**, destroy all Customer Data within three (3) months". Eine schriftliche Löschbestätigung gibt es ebenfalls auf Anfrage. ⚠ Innerhalb dieser Frist muss ein Export selbst erfolgen |
| **Aufbewahrungsfristen im Betrieb** | Ziff. **4.3**: Der Verantwortliche — nicht Brevo — steuert die Aufbewahrung der hochgeladenen Daten. Brevo löscht nur am Ende der Vertragsbeziehung |
| **Meldung einer Datenschutzverletzung** | ⚠ **„without undue delay"** (Ziff. **5.7**), Inhalt nach Art. 33 Abs. 3 DSGVO. **Keine Stundenzahl mehr** — die Vorfassung nannte ausdrücklich 72 Stunden |

⚠⚠ **Am 2026-09-12 gegen den Online-Stand abgeglichen: Brevo hat den
Auftragsverarbeitungsvertrag neu gefasst.** Die hier zuvor dokumentierte Fassung
(Annex 2, Stand 15.05.2024) war korrekt wiedergegeben — die PDF-Kopie belegt jede
Angabe samt Klauselnummer —, sie beschreibt aber nicht mehr den geltenden Vertrag.
Was sich geändert hat:

| Punkt | Fassung 15.05.2024 | Stand 2026-09-12 |
|---|---|---|
| Fundstelle | **Annex 2** der General Terms and Conditions | **Appendix 3** der Terms of Service. ⚠ „Annex 2" bezeichnet dort jetzt die **Liste der Unterauftragsverarbeiter** — wer der alten Angabe folgt, findet das falsche Dokument |
| Vorrang | Ziff. 1.4, unbedingt | Ziff. 2.1, „to the extent of that conflict" |
| Löschung | Ziff. 8.1: **100 Tage**, „incompressible period", **von selbst** | Ziff. 5.3: **drei Monate**, **auf Verlangen** |
| Löschnachweis | Ziff. 8.2: „certificate of deletion" | Ziff. 5.3: „written confirmation of such destruction" |
| Datenschutzverletzung | Ziff. 5.3: **72 Stunden** | Ziff. 5.7: „without undue delay" |
| Neue Unterauftragsverarbeiter | Ziff. 6.2: Hinweis **nur bei Anmeldung** über ein Formular, 10 Werktage vorher | Ziff. 7.3: Hinweis **immer**, per Mail oder im Konto, **vor** der Freigabe, danach 30 Tage Kündigungsrecht bei berechtigtem Einwand |

⚠ **Die wichtigste Folge ist eine Handlung, nicht ein Satz:** Die Löschung am
Vertragsende geschieht **nicht mehr von selbst**. Wer den Vertrag beendet, muss sie
ausdrücklich verlangen — sonst bleiben die Daten bei Brevo, und die drei Monate laufen
nie an.

⚠ **Eine Verbesserung ist ebenfalls eingetreten:** Die Anmeldung zum
Benachrichtigungsformular (bisher DS-01) ist entfallen; über neue
Unterauftragsverarbeiter wird jetzt in jedem Fall informiert.

⚠ **Die frühere Angabe „EU — keine Drittlandsübermittlung nach Kapitel V DSGVO" war
falsch** und stand hier seit Feature 04. Brevo selbst sitzt in Frankreich, aber mehrere
seiner Unterauftragsverarbeiter verarbeiten in den USA — **Datadog** (Protokollierung)
sogar ausschließlich dort. Die Übermittlung ist zulässig, aber sie findet statt, und ein
Verzeichnis, das sie verneint, trägt bei einer Auskunft nicht. Korrigiert am 2026-09-05
nach Lektüre des Vertragstexts.

**Unterauftragsverarbeiter** (Annex 2 des DPA, abgerufen am 2026-09-12). Immer
im Spiel, weil Teil der Infrastruktur:

| Dienst | Aufgabe | Sitz | Serverstandort | Grundlage |
|---|---|---|---|---|
| OVH | Hosting | Frankreich | Frankreich | — (EU) |
| Google Cloud Platform | Hosting | Frankreich | Belgien | DPF + Standardvertragsklauseln |
| Cloudflare | CDN und Firewall | USA | USA/EU | DPF + SCC + „Data Localization Suite" |
| Zendesk | Support-Ticketsystem | USA | EU/USA | BCR + Standardvertragsklauseln |
| **Omni** | Dashboards | **USA** | EU | DPF + SCC + ergänzende Maßnahmen |

Nur bei Nutzung des jeweiligen Dienstes — **Endlech.lu nutzt keinen davon**, wer einen
einschaltet, erweitert damit die Übermittlung und zieht diese Liste mit:

| Gruppe | Dienste |
|---|---|
| Integration und Seiten | Integry (USA), Convrrt (USA) |
| SMS-Routing | Vonage (USA), Telnyx (Irland), iBasis (Liechtenstein), Twilio (USA), Sinch (UK) |
| **KI-Anbieter** | **OpenAI** (EU, Server EU/USA), **Google Gemini** (EU, EU/USA), **Anthropic** (Irland, Server **USA**), **Langfuse** (EU, EU) |
| Wartung | Eldar IT (Serbien, Salesforce-Plugin) |

Dazu Gruppenunternehmen: Brevo GmbH (Deutschland) und Brevo CRM Solutions Limited.

⚠ **Drei Änderungen gegenüber dem Stand vom 15.05.2024, und eine ist bemerkenswert:**
**Datadog** (Protokollierung, USA), **Scaleway/Iliad** und **Hetzner** stehen **nicht
mehr** in der Liste; **Looker** ist durch **Omni** ersetzt — und das steht jetzt in der
Infrastrukturgruppe, ist also nicht mehr abwählbar. Neu sind vier **KI-Anbieter**, unter
ihnen Anthropic mit Servern in den USA. Sie gelten als Unterauftragsverarbeiter nur, wenn
die betreffende Funktion tatsächlich benutzt wird — **dieses Projekt benutzt sie nicht**,
und wer im Brevo-Konto eine KI-Funktion einschaltet, übermittelt Kontaktdaten an einen
weiteren Dritten.

⚠ **Der Hinweis auf neue Unterauftragsverarbeiter kommt jetzt in jedem Fall** (Ziff. 7.3):
per Mail oder im Konto, **vor** der Freigabe, danach 30 Tage Kündigungsrecht bei einem
Einwand, der sich auf einen Verstoß gegen EU-Datenschutzrecht stützt. ~~Ziff. 6.2: Hinweis
nur bei Anmeldung über ein dediziertes Formular~~ — **entfallen; DS-01 ist damit erledigt**
und braucht keinen Handgriff im Brevo-Konto mehr.

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
| `ORGANISATION` | Restaurant- bzw. Organisationsname — **bei App-Wartelisten-Einträgen leer**. Die Plattformwahl (iOS/Android) geht ausdrücklich **nicht** mit (Feature 08, AK-54). ⚠ Sie ging es kurzzeitig doch: `getDisplayName()` liefert bei jener Warteliste das Plattform-Label, und derselbe geteilte Aufruf schrieb es als `ORGANISATION` fort (BF-120, behoben vor der Auslieferung) |
| `LOCALE` | Sprache |
| `ORIGIN` | Rolle im Vertrieb: Partner, Gemeinde, Unternehmen, Verein, Nutzerkonto, **App-Warteliste** (seit Feature 08, 2026-09-05) |
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

### App-Warteliste (Feature 08, seit 2026-09-05)

| | |
|---|---|
| **Zweck** | Benachrichtigung, sobald die mobile App verfügbar ist; für iOS zusätzlich der Zugang zur TestFlight-Testfassung |
| **Rechtsgrundlage** | Art. 6 Abs. 1 lit. a — Einwilligung, eingeholt per Double-Opt-In |
| **Empfänger** | Brevo (Versand beider Mails). Der Kontaktbestand **nur** bei zusätzlich erteilter Werbe-Einwilligung — die ist getrennt, freiwillig und nicht vorangehakt (Koppelungsverbot, Art. 7 Abs. 4) |
| **Speicherort** | eigene Datenbank (`app_waitlist_entry`) |

**Verarbeitete Daten** — abschließend, es sind sechs:

| Feld | Inhalt |
|---|---|
| `email` | E-Mail-Adresse, normalisiert auf Kleinschreibung |
| `platform` | `ios` oder `android` |
| `consent_at` | Zeitpunkt der Einwilligung (Nachweis nach Art. 7 Abs. 1) |
| `marketing_consent_at` | Zeitpunkt der **Werbe**-Einwilligung; `null` = keine |
| `locale` | Sprache des Formulars |
| `source` | UTM-Quelle oder Referrer-Host |

Dazu die Verwaltungszeitstempel (`created_at`, `updated_at`, `confirmed_at`,
`self_confirmed_at`, `beta_link_sent_at`) und der Bestätigungstoken.

**Ausdrücklich nicht erhoben:** kein Name, keine IP-Adresse, kein Gerätemodell, keine
Telefonnummer. Was nicht erfasst ist, kann nicht versehentlich veröffentlicht werden —
die Feldliste ist durch einen Prüflauf abgesichert
(`AppWaitlistQaTest::testAk42KeineBesonderenKategorien`), ein neues Feld ist damit eine
Entscheidung und kein Nebenprodukt.

**Keine besondere Kategorie nach Art. 9.** Die Wahl zwischen zwei Betriebssystemen sagt
nichts über Gesundheit, Herkunft oder Überzeugung.

**Löschfrist:** Nie selbst bestätigte Vormerkungen werden nach **30 Tagen** gelöscht —
ohne eingelöste Bestätigung liegt keine Einwilligung vor. Der Lauf hängt an zwei
unabhängigen Wegen (täglicher Zeitplan **und** ein Durchgang je Kalendertag beim Öffnen
der Verwaltungsliste), weil auf Produktion schon zweimal ein geplanter Lauf ausblieb.

⚠ **Gemessen wird an `consent_at`, nicht an `created_at`** (BF-122). Letzteres wird
zurückgesetzt, wenn ein abgelaufener Bestätigungslink neu ausgestellt wird — darüber
ließ sich die Aufbewahrung sonst unbegrenzt verlängern, auch mit fremden Adressen.

⚠ **Bestätigte Vormerkungen haben keine Frist** (OF-01, Betreiberentscheid 2026-09-04).
Die Liste soll eine Veröffentlichung überleben — nach iOS kommt Android. Der
Widerrufsweg ist damit die einzige Ausstiegsmöglichkeit: **Jede Mail trägt einen
Abmeldelink, und er löscht den Eintrag**, statt ihn zu markieren (Art. 7 Abs. 3).

⚠ **Abweichung zu B14/B15:** Beim Löschen eines Nutzerkontos wird eine App-Vormerkung
unter derselben Adresse **mitgelöscht**; Partner- und Organisationseinträge bleiben
ausdrücklich stehen. Begründung und offene Frage: `features/08-app-warteliste/spec.md`,
OF-08.

**An Apple geht aus diesem Feature nichts.** Der TestFlight-Link ist ein Link in einer
Mail; die Anwendung ruft Apple nicht auf. Wer ihn anklickt, tritt selbst in Apples
Reichweite — das ist seine Handlung, nicht die der Plattform.

---

### Hostinger (Hosting, seit 2026-09-02)

⚠ **Der Auftragsverarbeiter ist Hostinger, nicht Coolify.** Coolify ist *selbst
betriebene Software* auf einem eigenen Server — sie läuft dort, wo auch die Anwendung
läuft, und überträgt nichts an ihren Hersteller. Wer sie als Verarbeiter führt, benennt
den Falschen und übersieht den Richtigen: Der Server-Anbieter hält Anwendung, Worker
**und Datenbank** und sieht damit alles.

Bis zum 2026-09-02 lief das Projekt bei Cloudways; dieser Eintrag stand seither falsch
im Verzeichnis und wurde am **2026-09-05** berichtigt.

| | |
|---|---|
| **Anbieter** | Hostinger International Ltd., 61 Lordou Vironos str., 6023 Larnaca, **Zypern (EU)** |
| **Weitere Vertragspartner laut DPA** | Hostinger UK Limited (London) und **Hostinger Global S.à r.l., 6 Avenue Pasteur, L-2310 Luxembourg** |
| **Serverstandort** | **Deutschland** — ermittelt am 2026-09-05: Reverse DNS `srv1947421.hstgr.cloud`, AS47583 Hostinger International Limited, Geolokalisierung Düsseldorf, Laufzeit **19 ms** (Brasilien wären ~200 ms; die IP liegt aus historischen Gründen in einem LACNIC-Bereich, was in die Irre führt) |
| **AV-Vertrag** | **Data Processing Addendum**, Anhang zu den Nutzungsbedingungen — gilt automatisch mit deren elektronischer Annahme, keine gesonderte Unterzeichnung |
| **Gelesene Fassung** | Stand **18.08.2026**, geprüft am 2026-09-05 · <https://www.hostinger.com/legal/dpa> |
| **Löschung nach Vertragsende** | **30 Tage** ab Beendigung |
| **Unterauftragsverarbeiter** | AWS EMEA SARL (EU) · Google Cloud EMEA (EU) · **Cloudflare, Inc. (USA)** · MailChannels · Proofpoint · Anthropic Ireland Ltd. (Irland) · spectra tech UAB |
| **Benachrichtigung über neue Unterauftragsverarbeiter** | vorgesehen; bei Ablehnung kann der Kunde binnen **10 Tagen** ohne Strafzahlung kündigen |

⚠ **Cloudflare, Inc. verarbeitet in den USA.** Auch hier gilt also: Der Hoster selbst
sitzt in der EU, die Kette reicht darüber hinaus. Hostinger erklärt für Übermittlungen
außerhalb der EU/EWR den Abschluss von Standardvertragsklauseln.

⚠ **Welche der drei Hostinger-Gesellschaften der Vertragspartner ist, steht in der
Rechnung**, nicht im DPA — offen als DS-02b.

---

### Nutzungsmessung (Feature 11, gebaut 2026-09-13, überarbeitet 2026-09-14, noch nicht ausgeliefert)

**Kein neuer Auftragsverarbeiter.** Umami ist selbst betriebene Software auf einem **zweiten VPS bei
Hostinger** — im selben Konto wie die Anwendung, der Auftragsverarbeitungsvertrag oben gilt.
Serverstandort laut Betreiber **Deutschland** (Angabe 2026-09-13). Derselbe VPS trägt Uptime Kuma (BE-01).
Die Instanz stammt aus dem Docker-Katalog des Hosters, läuft mit Umamis Vorgaben (Version 3.3.1) und ist
über **eine eigene Domain** erreichbar (Überarbeitung vom 2026-09-14, Spec Decision Log #23).

⚠ **Rechnername, Adresse und Umami-Domain stehen hier bewusst nicht** — wie bei BE-01. Skript und
Zählaufrufe laufen **über endlech.lu** (`/zaehler.js`, `POST /api/send`); die Anwendung reicht sie geprüft
und gekürzt an die Umami-Domain weiter. ⚠ **Seit dem 2026-09-14 ist der Standort nicht mehr verborgen,
sondern nur nicht aus endlech.lu ableitbar:** Die Domain steht im öffentlichen DNS und ihr Zertifikat in
öffentlichen Zertifikatslogs. `APP_UMAMI_UPSTREAM` gehört trotzdem in kein Protokoll und in keine
Unterlage — dokumentiert wäre sonst der Weg vom Projekt zum VPS der Überwachung.

| | |
|---|---|
| **Zweck** | Welche Seiten gelesen werden, woher Besucher kommen, wo sie Suche und Wartelisten abbrechen; Nachmessung des Growth-Loops |
| **Rechtsgrundlage (Text in `/legal`)** | berechtigtes Interesse, Art. 6 Abs. 1 lit. f DSGVO — ⚠ **vor dem Deploy in `/sdd-betrieb` zu prüfen** (OF-01 der Spec, seit 2026-09-14 mit Region und Stadt) |
| **Einwilligung** | keine; Banner unverändert (kein Cookie). Widerspruch: „Do Not Track", „Global Privacy Control", Schalter in `/legal` (Browserspeicher) |
| **Gespeichert** | Sitzung: Kennung (wechselt **monatlich**, Umamis Vorgabe), Browser, Betriebssystem, Gerät, Bildschirmgröße, Sprache, **Land, Region und Stadt** (Umamis Städtedatenbank). Seitenaufruf/Ereignis: Pfad ohne Abfrage, Seitentitel, Herkunftsdomain, Ereignisname mit Katalogfeldern |
| **Nicht gespeichert** | IP-Adresse (nur beim Eingang für Ort und Sitzungskennung), Abfrage, Herkunftspfad, Cookies, Kontokennung, Formularinhalte |
| **Nicht gemessen** | Verwaltung, Profil, jede Seite mit Token in der Adresse; lokal, Test, `www.endlech.lu`; bekannte Bots |
| **Aufbewahrung** | **unbegrenzt**, Begründung: langfristiger Vergleich der Reichweite (Decision Log #12 der Spec). ⚠ Mit OF-01 in `/sdd-betrieb` zu prüfen — Stadt und unbegrenzte Aufbewahrung zusammen grenzen einen Besuch stärker ein als Land allein |
| **Kontolöschung / Auskunft** | kein Kontobezug — nichts zu löschen, nichts zuzuordnen |
| **Zugriff** | Umami-Oberfläche über die Umami-Domain: Betreiber mit Passwort **und zweitem Faktor** (nur für diesen Benutzer, nicht global erzwungen), Growth-Loop mit eigenem Benutzer **nur lesend**, ohne zweiten Faktor, Zugang nur unter `~/.config/umami/`. Kein SSH-Tunnel mehr. Umami deckelt Anmeldeversuche nicht (OF-08) |
| **Übertragung zwischen den Servern** | HTTPS an die Umami-Domain mit **gewöhnlicher Zertifikatsprüfung**; die Besucheradresse steht im Zählaufruf selbst (Feld `ip`), damit Umami hinter dem Proxy des Hosters das richtige Land und eine eigene Sitzung je Besucher bildet |
| **Hersteller** | Das Umami-**Dashboard** lädt im Browser dessen, der es öffnet, ein Telemetrie-Bild des Herstellers (nur die Versionsnummer) und fragt nach Updates. Besucherdaten gehen darüber nicht an den Hersteller |
| **Bekannte Grenze** | Die Zählschnittstelle der Umami-Domain ist öffentlich: Wer Domain und Website-Kennung kennt, kann an endlech.lu vorbei Zählaufrufe schicken — ohne Deckel und ohne Kürzung. Hingenommen (Spec, EC-08); Last auf dem VPS der Überwachung: OF-08 |
| **Unterlagen** | `features/11-nutzungsmessung/` (Spec, Entwurf, Plan) |
---

### Weitere Verarbeiter

| Dienst | Zweck | Sitz | Bemerkung |
|---|---|---|---|
| **Hostinger** (Hosting) | Betrieb der Anwendung, des Messenger-Workers und der Datenbank | Zypern (EU); Server in **Deutschland** | Siehe eigener Abschnitt unten. ⚠ **Nicht Coolify** — das ist selbst betriebene Software und sieht die Daten nur auf dem eigenen Server |
| Sentry | Fehler-Tracking | EU (`ingest.de.sentry.io`, Frankfurt) | `send_default_pii: false` — keine IP-Adressen, Cookies, Request-Header oder Nutzerdaten. `zend.exception_ignore_args` bleibt auf `On`, damit keine Funktionsargumente (und damit keine Passwörter) in Stacktraces landen |
| Mobilité (HAFAS) | Haltestellen in der Nähe | Luxemburg | Es gehen **Koordinaten eines Restaurants** hin, keine Nutzerdaten |

---

## Was vor dem ersten echten Brevo-Lauf stehen muss

Das ist AK-34 aus Feature 04 und keine Nacharbeit:

- [x] ~~**AV-Vertrag mit Brevo geprüft und hier mit Datum eingetragen**~~ — **am
      2026-09-05 erledigt, am 2026-09-12 erneut geprüft und nachgezogen.** Er ist
      **Appendix 3** der Terms of Service (bis 2024: Annex 2) und gilt automatisch mit
      deren Annahme; die Kernpunkte stehen oben, samt Abgleichstabelle der Änderungen.
      ⚠ **DS-01b ist entfallen** (die Anmeldung zum Benachrichtigungsformular verlangt
      der Vertrag nicht mehr), **DS-01c ist mit diesem Abgleich erledigt**. Offen bleibt
      allein **DS-01d** — das Datum, an dem die AGB angenommen wurden; es steht im
      Brevo-Konto
- [x] ~~**`/legal` nennt Brevo als Empfänger für Werbezwecke**~~ — **am 2026-09-05
      festgestellt: war bereits erfüllt.** Der Abschnitt steht seit Feature 04; der
      offene Punkt war überholt. Beim Nachsehen fielen allerdings drei andere Lücken
      derselben Erklärung auf, alle behoben — siehe DS-03b bis DS-03d
- [x] ~~**OF-01 beantwortet** (Datenschutzstufe des Projekts)~~ — **am 2026-08-30
      auf Stufe B festgelegt**, siehe oben

Erst danach: `app:marketing:import --commit` bzw. der erste Cron-Lauf mit
gesetztem Schlüssel. **Kein Kontakt geht raus, bevor die Erklärung ihn nennt.**

---

## Offene Punkte mit Frist

- **DS-01e · Fassung des Brevo-DPA einmal im Jahr prüfen** (nächste Prüfung: **2027-09**).
  ⚠ **Der Grund steht in BF-139:** Zwischen dem 15.05.2024 und dem 2026-09-12 hat Brevo den
  Vertrag neu gefasst — Fundstelle, Nummerierung, Löschfrist und Meldefrist haben sich
  geändert, und **niemand hat davon erfahren**. Der Vertrag sieht eine Benachrichtigung nur
  für neue *Unterauftragsverarbeiter* vor (Ziff. 7.3), nicht für neue Fassungen des DPA
  selbst. Ein Vertragsstand veraltet also lautlos, und eine Dokumentation, die ihn
  wiedergibt, veraltet mit ihm. Der Ablauf ist kurz: Seite abrufen, gegen die
  Abgleichstabelle oben lesen, PDF nach `qa/brevo-dpa/` legen, Datum hier eintragen.
- **DS-01f · Die Löschung am Vertragsende ausdrücklich verlangen**, falls Brevo je
  gekündigt wird. Ziff. 5.3: „upon your request" — ohne Verlangen läuft keine Frist an.
  Das ist kein Termin, sondern ein Schritt in der Kündigung; er steht hier, weil er sonst
  niemandem einfällt.


> **Vorbereitet am 2026-09-05:** Für alle Punkte, die einen Kontozugang brauchen, liegen
> fertige Texte und Klickwege bereit — `docs/anfragen/`. Die Checkliste dort ist nach
> Dringlichkeit geordnet und nennt zu jedem Punkt, wohin die Antwort gehört.
>
> | Datei | Deckt ab |
> |---|---|
> | `2026-09-checkliste-konten.md` | DS-04, BE-01, BE-03 und die Vorarbeit zu DS-02 |
> | `2026-09-brevo-dpa.md` | DS-01b, DS-01c, DS-01d — fertige Anfrage an `dpo@brevo.com` |
> | `2026-09-hostinger-standort.md` | DS-02b, DS-02c — fertige Anfrage, mit dem Hinweis, was vorher im hPanel steht |
>
> ⚠ ~~**DS-01b braucht wirklich eine Anfrage.** Ziffer 6.2 des DPA verlangt eine
> Anmeldung „via the dedicated form", verlinkt dieses Formular aber nicht — weder im
> Vertragstext noch auf den Hilfeseiten (beides am 2026-09-05 geprüft). Ohne Nachfrage
> lässt sich das Widerspruchsrecht gegen neue Unterauftragsverarbeiter nicht ausüben.~~
> — **am 2026-09-12 entfallen.** Die neue Fassung informiert in jedem Fall (Ziff. 7.3),
> ohne Anmeldung; das Formular gibt es nicht mehr, und die Anfrage dazu erübrigt sich.
> ⚠ **Die Frage, die stattdessen bleibt:** Die Löschung am Vertragsende geschieht nur
> noch **auf Verlangen** (Ziff. 5.3). Wer den Vertrag beendet, muss sie ausdrücklich
> fordern — das gehört in die Kündigungsschritte, nicht in eine Anfrage.


Aufgenommen am 2026-09-05 im Rahmen von `/sdd-betrieb`, nach der Auslieferung von
Feature 08. Was hier ohne Datum steht, hat niemanden, der es erzwingt.

| # | Punkt | Warum es drängt | Frist | Wer |
|---|---|---|---|---|
| **DS-01a** | ~~AV-Vertrag mit Brevo prüfen~~ — **erledigt 2026-09-05.** Er ist Annex 2 der AGB und gilt automatisch; eine gesonderte Unterzeichnung gibt es nicht und war nie nötig. Vertragstext gelesen, Kernpunkte oben eingetragen, Kopie in `qa/brevo-dpa/` | — | ✅ |
| **DS-01b** | **Im Brevo-Konto für Benachrichtigungen über neue Unterauftragsverarbeiter anmelden** | Ziff. 6.2 macht die Vorabinformation von einer Anmeldung über ein eigenes Formular abhängig. Ohne sie erfährt der Verantwortliche von einem neuen Unterauftragsverarbeiter **gar nichts** und kann die zehn Werktage Widerspruchsfrist nicht nutzen | **2026-09-30** | Betreiber |
| **DS-01c** | **Prüfen, ob die gelesene DPA-Fassung noch die geltende ist** | Die vorliegende trägt den Stand **15.05.2024** — über zwei Jahre alt. Ob seither eine neue in Kraft ist, ließ sich von außen nicht feststellen; im Konto bzw. in den AGB steht das Datum | **2026-09-30** | Betreiber |
| **DS-01d** | **Datum der AGB-Annahme festhalten** | Das DPA gilt ab Annahme der AGB — dieses Datum ist der Vertragsbeginn und gehört ins Verzeichnis. Es steht in der Kontoeröffnung bzw. der ersten Rechnung | **2026-09-30** | Betreiber |
| **DS-02a** | ~~Hoster: Vertrag, Sitz und Serverstandort nachtragen~~ — **erledigt 2026-09-05.** Der Verarbeiter ist **Hostinger** (Zypern, Server in Deutschland), nicht Coolify: Letzteres ist selbst betriebene Software und überträgt nichts an seinen Hersteller. DPA gilt automatisch mit den Nutzungsbedingungen, Stand 18.08.2026, Löschfrist 30 Tage. Alles im eigenen Abschnitt oben | — | ✅ |
| **DS-02b** | **Feststellen, welche Hostinger-Gesellschaft Vertragspartner ist** | Das DPA nennt drei: Zypern, UK und **Luxemburg**. Für die Frage, wer bei einer Auskunft oder einem Vorfall haftet und welches Recht gilt, ist das nicht gleichgültig. Steht in der Rechnung oder im Konto | **2026-09-30** | Betreiber |
| **DS-02c** | **Prüfen, ob der Serverstandort vertraglich zugesichert ist** | Gemessen ist er Deutschland — das ist eine Momentaufnahme, keine Zusage. Ob der Tarif eine Region festschreibt oder Hostinger frei verschieben darf, steht in der Produktbeschreibung | **2026-09-30** | Betreiber |
| **DS-03a** | ~~`/legal` nennt Brevo als Empfänger für Werbezwecke~~ — **war bereits erfüllt**, festgestellt 2026-09-05. Der Abschnitt `legal.marketing_*` steht seit Feature 04 und nennt Zweck, übermittelte Felder, Rechtsgrundlage, Freiwilligkeit und Widerruf. Der offene Punkt war überholt | — | ✅ |
| **DS-03b** | ~~Widersprüchliche Angabe zum selben Verarbeiter~~ — **behoben 2026-09-05.** `legal.mail_text` nannte „Sendinblue GmbH, Köln", `legal.marketing_text` im selben Abschnitt „Brevo SA, Frankreich". Vertragspartner ist Brevo SA, Paris (so auch im DPA). Berichtigt in vier Sprachen | — | ✅ |
| **DS-03c** | ~~Der Hoster fehlte in der Erklärung~~ — **behoben 2026-09-05.** Neuer Abschnitt `legal.hosting_*`: Hostinger, Zypern, Server in Deutschland. ⚠ Er hält Anwendung, Worker **und** Datenbank — Art. 13 Abs. 1 lit. e verlangt seine Nennung so gut wie die von Brevo. BF-65 hatte drei Empfänger nachgetragen und den Hoster übersehen | — | ✅ |
| **DS-03d** | ~~Kein Hinweis auf Drittlandsübermittlung~~ — **behoben 2026-09-05.** Neuer Abschnitt `legal.transfer_*`: Alle unmittelbaren Empfänger sitzen in der EU, ihre Unterauftragsverarbeiter nicht durchgängig (Datadog USA bei Brevo, Cloudflare bei beiden). Art. 13 Abs. 1 lit. f | — | ✅ |
| **DS-05** | **Klären, ob der Wechsel des Verantwortlichen eine Information der Betroffenen erfordert** | Bis zum 2026-09-05 war der Verantwortliche eine Privatperson, seither die S.à r.l.-S. Die Verarbeitung läuft unverändert weiter, aber die Stelle, gegen die sich Betroffenenrechte richten, ist eine andere. ⚠ Das ist eine Rechtsfrage, keine technische — sie gehört jemandem mit fachlicher Qualifikation vorgelegt | **2026-09-30** | Betreiber |
| **DS-04** | **Anonyme Nachverfolgung in Brevo einschalten** — Konto → Settings → Default settings → Tracking → „Anonymous email tracking" auf **Yes**, dann oben rechts speichern | **Entschieden am 2026-09-05.** ⚠ Die Frist war als „vor dem ersten Kampagnenlauf" notiert und damit zu spät angesetzt: Brevo verfolgt auch **Transaktionsmails**, und die laufen seit dem ersten Tag. Es geht also nicht um eine künftige Kampagne, sondern um Bestätigungsmails, die bereits verschickt wurden | **sofort** | Betreiber |

⚠ **DS-01 und DS-02 sind keine Formalien.** Beide betreffen Verarbeiter, die bereits
echte Personendaten halten. Sie stehen hier mit Datum, weil ein offener Punkt ohne Frist
in drei Monaten genauso offen ist — nur dass dann niemand mehr weiß, seit wann.

### Öffnungs- und Klickverfolgung (Entscheidung vom 2026-09-05)

**Entschieden: anonyme Nachverfolgung.** Öffnungen und Klicks werden weiter gezählt,
aber nicht mehr einer Person zugeordnet — Brevo anonymisiert dabei E-Mail-Adresse und
IP-Adresse des Empfängers.

**Warum nicht so lassen, wie es war:** Die Einwilligung dieses Projekts lautet *„Ich
möchte Neuigkeiten von Endlech.lu per E-Mail erhalten."* Sie deckt den **Empfang** ab.
Eine Messung, wer wann was geöffnet hat, ist etwas anderes und stand dort nie.

⚠ **Bei den Bestätigungsmails wiegt das schwerer als bei Kampagnen.** Wer sich auf eine
Warteliste setzt, bestätigt eine Adresse — er willigt in **gar keine** Werbeverarbeitung
ein, und die Bestätigungsmail geht auch an Menschen, die den Vorgang nie abschließen.
Das Verfolgen ihres Öffnungsverhaltens hätte keine Grundlage, auf die man sich berufen
könnte.

**Warum nicht vollständig abschalten:** Für Transaktionsmails ist das bei Brevo nicht
als Option vorgesehen. Und die Zustellquote ist die einzige Kennzahl, an der ein
Zustellproblem auffällt, bevor sich jemand beschwert — bei einem Projekt, dessen
Wartelisten vollständig am Double-Opt-In hängen, ist das keine Kleinigkeit.

**Was der Mittelweg kostet:** Eine Segmentierung nach Öffnern oder Klickern ist danach
nicht mehr möglich, und auf der Kontaktseite steht nicht mehr, ob eine bestimmte Person
geöffnet hat. Für dieses Projekt ist das kein Verlust — es betreibt kein
zielgruppenscharfes Kampagnenmarketing und will es laut PRD auch nicht.

**Passt zum PRD:** Dort steht *„Es gibt kein Web-Analytics … Keine Besucherverfolgung.
Passt zur Datensparsamkeit."* Eine personenbezogene Öffnungsmessung im Postfach wäre
dieselbe Verfolgung, nur an anderer Stelle.

⚠ **Die Einstellung wirkt nur für künftige und geplante Mails**, nicht rückwirkend. Was
bis zur Umstellung an personenbezogenen Öffnungsdaten entstanden ist, liegt weiter in
Brevo — wer es entfernen will, muss es dort löschen.

⚠ **Nach dem Umschalten gehört diese Zeile hier ergänzt**: Datum der Umstellung und wer
sie vorgenommen hat. Ohne Datum ist später nicht feststellbar, welcher Zeitraum betroffen
war.

## Betriebsüberwachung

Stand 2026-09-12. Was hier fehlt, meldet seinen Ausfall nicht selbst.

| Bereich | Zustand | Wo |
|---|---|---|
| Fehler-Tracking | **läuft** — Sentry, EU-Region (`ingest.de.sentry.io`), nur `prod`, `send_default_pii: false`. DSN in Coolify gesetzt (Betreiber bestätigt 2026-09-05) | `config/packages/sentry.yaml` |
| Rate Limits | **läuft** — 23 Limiter (Stand 2026-09-13, zuletzt `usage_collect` für den Zählweg aus Feature 11), jeder verdrahtet und mit `when@test`-Override; `LimiterCoverageTest` färbt rot, sobald einer davon fehlt | `config/packages/framework.yaml` |
| Protokollierung | **läuft** — `prod` schreibt nach `stderr`, `!doctrine` und `!request` ausgeschlossen, damit keine Bestätigungstoken im Hoster-Log landen (BF-23) | `config/packages/monolog.yaml` |
| Lebendigkeitsprüfung | **läuft** — `/health`, sprachfrei, bewusst **ohne** Datenbankabfrage | `src/Controller/Health/` |
| **Messenger-Worker** | **überwacht seit 2026-09-05** — `app:messenger:watch` meldet einen Rückstau per Mail, täglich aus dem `marketing`-Zeitplan | siehe unten |
| **Uptime von außen** | **läuft seit 2026-09-12** — Uptime Kuma auf einem **zweiten VPS**, zwei Prüfungen: `/health` und ein Puls des Messenger-Consumers. `/open.json` bewusst nicht (Entscheidung 2026-09-12) (BE-01) | siehe unten |
| **Messenger-Consumer, Totalausfall** | **läuft seit 2026-09-12, Alarm ausgelöst** — `app:worker:pulse` meldet alle fünf Minuten nach außen; bleibt der Puls aus, schlägt Kuma an | `src/Command/WorkerPulseCommand.php` |
| Produktanalyse | **gebaut als Feature 11 (2026-09-13, überarbeitet 2026-09-14), noch nicht ausgeliefert** — Umami, selbst betrieben auf dem zweiten VPS, erreicht über eine eigene Domain, Zählweg über endlech.lu; der Roadmap-Eintrag `usage_analytics` ist entfernt (BE-02) | `features/11-nutzungsmessung/`, Abschnitt „Nutzungsmessung“ oben |
| Sicherungen der Datenbank | **Rückweg prüfbar seit 2026-09-12**, die Sicherung selbst weiter ungeklärt: ob Coolify sichert und wie oft, ist nicht dokumentiert (BE-03) | `bin/sicherung-pruefen.sh` |

### BE-01 · Uptime-Prüfung von außen — eingerichtet und ausgelöst (2026-09-12)

Überwacht wird mit **Uptime Kuma**, selbst betrieben auf einem **zweiten VPS**. Das ist
der Punkt, an dem diese Lücke wirklich geschlossen ist: Ein Wächter auf demselben
Rechner teilt dessen Schicksal — stirbt der Hostinger-VPS, stirbt der Wächter mit ihm,
und genau dieser Fall ist der einzige, den sonst niemand meldet.

**Die Prüfungen, und welche Frage jede beantwortet:**

| # | Kuma-Typ | Ziel | Takt | Erwartung | Beantwortet |
|---|---|---|---|---|---|
| 1 | HTTP(s) | `https://endlech.lu/health` | 60 s | 200 | Läuft der PHP-Prozess? |
| ~~2~~ | ~~HTTP(s) – Json Query~~ | ~~`https://endlech.lu/open.json`~~ | — | **nicht eingerichtet, Entscheidung 2026-09-12** | ~~Antwortet die Datenbank?~~ — bleibt unbeantwortet |
| 3 | **Push** | von Kuma erzeugte Adresse | 360 s, Retries 2 | ein Puls alle 5 Min | Läuft der Messenger-Consumer? |

⚠ **Monitor 2 ist bewusst nicht eingerichtet** (Betreiberentscheidung vom 2026-09-12).
Die Folge gehört benannt: `/health` macht **keine** Datenbankabfrage — hinge sie daran,
nähme ein kurzer Ausfall der Datenbank den Container mit, und der Neustart hülfe nichts,
weil die Ursache außerhalb liegt. Ein grünes `/health` sagt deshalb nichts darüber, ob
die Datenbank antwortet. **Ein Datenbankausfall bleibt in Kuma grün.** Auffallen würde er
indirekt über Sentry: Jede Seite, die die Datenbank braucht, wirft dann eine Ausnahme,
die gemeldet wird — allerdings nur, **wenn jemand die Seite aufruft** und in Sentry eine
Alarmregel eingerichtet ist.

Falls die Prüfung später doch kommt, gilt, was am 2026-09-12 nachgelesen wurde:

⚠ **So beweist `/open.json` die Datenbank — anders, als man denkt.** Am 2026-09-12
im Code nachgelesen: `OpenStatsService::platform()`, `impact()` und `finance()` liegen
**eine Stunde im Cache** (Pool `cache.open_stats`, Filesystem). Was bei *jedem* Aufruf an
die Datenbank geht, ist `MetricSnapshotRepository::findTrend(24)` — ohne Cache. Steht die
Datenbank, wirft dieser Aufruf, und der Endpunkt antwortet mit 500. **Der Datenbankbeweis
ist also die 200 selbst.** Die Json-Query-Prüfung liest dagegen einen bis zu eine Stunde
alten Cachewert; sie ist Absicherung gegen „200 mit kaputtem Rumpf", nicht gegen eine
stehende Datenbank. Wer das verwechselt, hält eine Prüfung für stärker, als sie ist.

⚠ **`/open.json` sendet `cache-control: max-age=3600, public`.** Kuma ignoriert das
(eigener Client ohne Cache), und weder Caddy noch Coolifys Proxy cachen von sich aus —
derzeit unkritisch. **Wer aber je ein CDN davorsetzt, verliert diese Prüfung
lautlos:** Der Wächter bekäme dann eine Stunde lang eine gespeicherte 200, während
die Anwendung tot ist.

**Stand der Einrichtung:**

| # | Eingerichtet | Alarm ausgelöst |
|---|---|---|
| 1 `/health` | 2026-09-12, 60 s, Retries 2 | **ja, 2026-09-12** — Ziel auf eine nicht vorhandene Adresse gestellt, „down" und danach „up" beim Betreiber angekommen (Betreiber bestätigt) |
| 2 `/open.json` | **bewusst nicht** (Betreiberentscheidung 2026-09-12) | — |
| 3 Push | 2026-09-12, 360 s, Retries 2; `APP_UPTIME_PUSH_URL` auf der Worker-Ressource, ausgeliefert mit `v2026.09.12.3` | **ja, 2026-09-12** — `app:worker:pulse` im Worker-Container meldete „Puls angekommen (HTTP 200)“; Monitor grün; Worker kurz angehalten, Alarm angekommen (Betreiber bestätigt) |

#### Der Push-Monitor ist die eigentliche Neuigkeit

Der Ausfall des Messenger-Consumers war bis hierhin der **lautlose**: Nachrichten stapeln
sich in `messenger_messages`, die Anwendung meldet weiter „erfolgreich", und niemand
bekommt mehr eine Bestätigungsmail (Registrierung, Double-Opt-In aller drei Wartelisten,
E-Mail-Wechsel), kein Monats-Snapshot entsteht, kein Brevo-Abgleich läuft. Der vorhandene
Wächter `app:messenger:watch` hilft dort nicht — er läuft **im selben Consumer**, den er
beobachtet, und schweigt mit ihm.

⚠ **Die Umkehrung ist der ganze Trick.** Ein Wächter, der etwas *abfragt*, kann einen
stehenden Prozess nicht von einem gesunden unterscheiden; der Consumer serviert kein
HTTP, es gibt nichts zu fragen. Hier ruft deshalb der Beobachtete an:
`app:worker:pulse` läuft alle fünf Minuten aus dem Zeitplan `marketing` und ruft eine
Push-Adresse von Kuma. **Bleibt der Anruf aus, ist das die Aussage.**

| | |
|---|---|
| **Befehl** | `app:worker:pulse`, Zeitplan `marketing`, `*/5 * * * *`; `--dry-run` sagt nur, ob ein Ziel eingerichtet ist |
| **Adresse** | `APP_UPTIME_PUSH_URL`, Form `https://<wächter>/api/push/<token>`. Leer heißt lautlos aus — wie `SENTRY_DSN` und `MOBILITEIT_API_KEY` |
| **Grenze** | Eine **Lebendigkeits**-, keine Fortschrittsprüfung: „der Consumer läuft", nicht „er arbeitet den Rückstau ab". Letzteres bleibt Sache von `app:messenger:watch`. Beide zusammen decken den Fall ab, keiner allein |

⚠⚠ **Die Variable gehört auf die WORKER-Ressource in Coolify, nicht auf die Anwendung.**
Das sind zwei Ressourcen mit je eigener Variablenliste (derselbe Fallstrick wie beim
gemeinsamen `APP_SECRET`). Steht sie nur bei der Anwendung, läuft der Puls **nie** — und
das Ergebnis ist ein Dauer-Alarm über einen Worker, der einwandfrei arbeitet. Ein Wächter,
der grundlos weckt, wird abgeschaltet; damit wäre die Lücke schlimmer wieder offen als
vorher.

⚠ **Ein Netzproblem zwischen den beiden Servern sieht aus wie ein toter Worker.** Kuma
meldet dann „ausgefallen", obwohl der Consumer arbeitet. Dafür schreibt der Befehl eine
Warnung ins Protokoll (`Puls an den externen Wächter nicht zustellbar`): Erscheint sie,
war der Worker am Leben und nur der Weg versperrt. Sie ist der **einzige** Unterschied
zwischen den beiden Fällen — deshalb wird sie geschrieben, obwohl der Alarm ohne sie
ohnehin zustande käme.

⚠ **Der Befehl gibt nie `FAILURE` zurück.** Der Zeitplan ruft ihn über
`RunCommandMessage`; ein Fehlschlag würfe dort eine Ausnahme, und der `failed`-Transport
füllte sich mit 288 Nachrichten am Tag. Dieselbe Überlegung wie beim belegten Schloss in
`MarketingSyncCommand`.

⚠ **Die Push-Adresse ist ein Geheimnis besonderer Art: Wer sie hat, schaltet einen Alarm
AUS.** Er kann Kuma dauerhaft „alles in Ordnung" melden, und ein abgeschalteter Alarm
fällt niemandem auf. Deshalb steht sie nur in der Umgebung (das Repository ist
öffentlich), und deshalb protokolliert der Befehl ausschließlich Rechnername und
Ausnahme**klasse** — Symfonys Transport-Ausnahmen führen die vollständige URL in ihrem
**Text**. Den zweiten Weg deckt `App\Monolog\SecretMaskingProcessor` ab, der seit dem
2026-09-12 auch pfadgetragene Geheimnisse maskiert: Das Token steht bei Kuma im **Pfad**
(`/api/push/<token>`), und die Parameterliste des Processors griff dort nicht. Genau
dasselbe Muster wie bei BF-45 (HAFAS-Schlüssel), einschließlich des Umstands, dass
`monolog.yaml` den `http_client`-Kanal in `prod` **nicht** ausschließt.

#### Was beim Einrichten in Kuma noch zu tun ist

- [x] ⚠ **Benachrichtigungskanal an jedem einzelnen Monitor anhaken.** Kuma hängt einen
      Kanal nur dann automatisch an neue Monitore, wenn er als „Default enabled" angelegt
      wurde. Sonst entsteht ein Monitor, der brav rot wird, und **niemand erfährt es** —
      ein Dashboard statt einer Überwachung. Das ist hier der wahrscheinlichste Fehler.
- [x] **Wiederholungen setzen** (Retries 2, Retry-Intervall 20–60 s). Ein einzelner
      verlorener Antwortversuch zwischen zwei Rechenzentren ist ein Alltagsereignis; drei
      Fehlschläge in Folge sind es nicht.
- [ ] **Zertifikatswarnung prüfen.** Kuma warnt von sich aus 21/14/7 Tage vorher und
      erfüllt die Zusage aus BE-01 damit bereits — nachzusehen ist nur, dass die
      Benachrichtigung überhaupt an einem Kanal hängt.
- [x] ⚠ **Jeden Alarm einmal auslösen.** Push-Monitor: den Worker kurz anhalten oder
      `APP_UPTIME_PUSH_URL` vorübergehend verbiegen. HTTP-Monitore: Ziel kurz auf eine
      falsche Adresse zeigen lassen. **Ein Alarm, der nie ausgelöst hat, hat nie
      funktioniert** — und bei Push ist das doppelt wahr, weil dort das *Ausbleiben* das
      Signal ist und sich ein falsch gesetzter Takt nicht anders äußert als Ruhe.
- [x] `APP_UPTIME_PUSH_URL` auf der **Worker**-Ressource in Coolify eintragen (siehe oben).

⚠ **Die Reihenfolge ist hier nicht beliebig.** Der Puls ist Code und läuft erst nach
einem Rollout (`main` → Release → `master` → Coolify). Ein aktiver Push-Monitor meldet
vorher vom ersten Takt an „ausgefallen" — zu Recht, denn es ruft niemand an. Das ist der
Fehlalarm, mit dem eine neue Überwachung ihr Vertrauen verliert, bevor sie einmal
gearbeitet hat. Die Adresse entsteht aber erst mit dem Monitor. Deshalb:

1. Monitor 1 sofort anlegen — **erledigt und ausgelöst am 2026-09-12**.
2. Monitor 3 anlegen und **sofort pausieren**; die Push-Adresse kopieren.
3. `APP_UPTIME_PUSH_URL` auf der **Worker**-Ressource eintragen.
4. Ausrollen — **beide** Ressourcen. Der Puls läuft im Worker; wer nur die Anwendung neu
   ausrollt, lässt den Worker auf dem alten Stand ohne Puls.
5. `php bin/console app:worker:pulse` im Worker-Container einmal von Hand aufrufen:
   „Puls angekommen (HTTP 200)" ist der Nachweis, dass Adresse und Weg stimmen.
6. Monitor 3 fortsetzen.

**Der Aufruf von Hand in Schritt 5 ist einmalig, nicht Teil jedes Deploys.** Danach ruft
der Zeitplan den Puls alle fünf Minuten selbst. Nach einem Rollout startet der Worker neu,
und weil der Zeitplan `marketing` genau einen verpassten Lauf nachholt, geht der erste Puls
in der Regel Sekunden nach dem Start hinaus; der kurze Container-Tausch liegt weit unter den
bis zu 18 Minuten, die Kuma wartet. Von Hand gehört der Befehl nur noch in zwei Fälle:
nach einer **geänderten Adresse** (Token-Reset, anderer Wächter) und zur **Fehlersuche**
bei einem Alarm — antwortet er mit „Puls nicht zustellbar", lebt der Worker und nur der
Weg ist versperrt.

⚠ **Gemessen am 2026-09-12: zwei Rechner, aber derselbe Anbieter.** endlech.lu und Kuma
laufen auf zwei verschiedenen VPS, beide bei **Hostinger, AS47583** (per DNS und ASN-Abfrage
bestimmt; Rechnername und Adresse des Wächters stehen hier bewusst nicht — das Repository
ist öffentlich, und wer weiß, wo die Überwachung steht, kann gezielt sie lahmlegen). Der Ausfall *eines* VPS ist damit
abgedeckt, ein Ausfall bei Hostinger selbst (Netz, Rechenzentrum) nimmt beide mit, und
dann meldet niemand etwas. Ob beide im selben Rechenzentrum stehen, ist nicht bekannt.
Seltener Fall, aber benannt: Wer ihn abdecken will, stellt den Wächter zu einem anderen
Anbieter.

⚠ **Die offene Restfrage: Wer bewacht den Wächter?** Stirbt der Kuma-VPS, kommen keine
Alarme mehr, und das fällt nicht auf — dieselbe Bauartgrenze wie beim
`app:messenger:watch`, nur eine Ebene höher. Kuma selbst bietet dafür keine Lösung. Die
billigen Wege: eine zweite, sehr kleine Prüfung anderswo auf die Kuma-Oberfläche, oder
ein wöchentlicher Blick. Nicht entschieden, aber benannt.

### BE-02 · Produktanalyse — am 2026-09-12 auf die Roadmap gesetzt, am 2026-09-13 als Feature 11 gebaut

> **Stand 2026-09-13:** Umgesetzt als Feature 11 mit Umami, selbst betrieben — der Weg aus der
> ersten Zeile der Vorarbeit-Tabelle unten. Beschreibung im Abschnitt „Nutzungsmessung (Feature 11)"
> unter den Auftragsverarbeitern. Der Roadmap-Eintrag ist mit dem Bau entfernt; ausgeliefert ist
> noch nichts. **Stand 2026-09-14:** überarbeitet — die vorhandene Katalog-Instanz über ihre eigene Domain
> statt eigenem Zähl-Eingang und Tunnel (Spec, Decision Log #23).

Ein Analyse-Skript im Frontend ist eine **Produktänderung**: Es berührt die App-Hülle,
den Datenschutzabschnitt in `/legal` und — je nach Wahl — das Einwilligungsbanner. Es
gehört deshalb als Feature auf die Roadmap und nicht in einen Betriebsdurchgang.

**Erledigt:** Das Vorhaben steht seit dem 2026-09-12 als `usage_analytics` in der Spalte
„Angedacht" auf `/roadmap`, in allen vier Sprachen, mit dem Begründungssatz „Infrage
kommt nur ein Weg ohne Cookies und ohne Personendaten — sonst bleibt es ungebaut". Damit
ist die Bedingung, unter der es überhaupt gebaut würde, **öffentlich zugesagt** und nicht
nur hier notiert. Ein Datum steht nicht daran; `RoadmapItem` hat kein Datumsfeld.

Die Vorarbeit unten bleibt stehen — sie ist die Entscheidungsgrundlage für den Tag, an
dem das Vorhaben aus „Angedacht" herauswandert.

Vorarbeit, damit die Entscheidung später keine Recherche mehr braucht:

| Weg | Einwilligung nötig? | Folge |
|---|---|---|
| **Plausible / Umami** | nein — cookielos, keine Personendaten | Ein Skript, ein Absatz in `/legal`, ein Verarbeiter mehr. Beantwortet „wie viele öffnen `/app`, wie viele senden ab" |
| **PostHog (EU)** | ja | Ereignisse und Trichter, deutlich mehr Erkenntnis — dafür Banner-Kopplung und ein längerer Datenschutzhinweis |
| **Serverseitig zählen** | nein | Kein Fremddienst, aber auch keine Absprungrate: Der Server sieht keinen Abbruch vor dem Absenden |

⚠ **Das PRD schließt Web-Analytics aus** und begründet das mit Datensparsamkeit. Eine
Analyse einzuführen widerspricht dem, solange das PRD nicht mitgezogen wird — das ist
eine Produktentscheidung und keine technische.

⚠ **Bei ereignisbasierter Analyse gehören keine Personendaten in Ereignisnamen oder
-eigenschaften.** Eine Nutzer-ID ist in Ordnung, eine E-Mail-Adresse nicht.

### BE-03 · Sicherungen der Datenbank — der Rückweg ist seit 2026-09-12 prüfbar

Ob Coolify die Datenbank sichert, in welchem Takt und wie lange die Sicherungen liegen,
ist nirgends dokumentiert. **Das gehört gemessen, nicht angenommen**: eine Sicherung
wiederherstellen, bevor sie gebraucht wird.

**Dafür gibt es seit dem 2026-09-12 ein Werkzeug statt eines Vorsatzes:**

```bash
make sicherung-pruefen DATEI=~/Downloads/endlech-2026-09-12.sql.gz
```

`bin/sicherung-pruefen.sh` spielt die Datei in einen **eigens gestarteten
Wegwerf-Container** ein (MariaDB, dieselbe Maschine wie Produktion — ein Einspielen in
das lokale MySQL 8 kann an Kollationen scheitern, die mit der Sicherung nichts zu tun
haben, und das wäre ein Fehlalarm über eine gesunde Sicherung). Der Container wird
danach gelöscht, auch bei Abbruch. **In eine bestehende Datenbank schreibt das Skript
nie** — ein Prüfwerkzeug, das Produktion überschreiben könnte, ist ein Risiko und keine
Prüfung. Das Urteil landet als Zeugnis unter `qa/sicherungen/`.

Sieben Prüfungen, und sie sind so gewählt, dass die *stillen* Fehlerfälle auffallen:

| Prüfung | Der Fehlerfall, der sonst durchgeht |
|---|---|
| Archiv unbeschädigt (`gzip -t`) | Eine abgebrochene Übertragung sieht wie eine Sicherung aus |
| Mindestens ein `INSERT` | **Ein Struktur-Dump spielt fehlerfrei ein und hinterlässt eine leere Datenbank.** Beim Bauen nachgestellt: Ohne diese Prüfung wäre das Urteil grün |
| Alle 19 erwarteten Tabellen | Die Liste steht im Skript, nicht im Prüfling — eine Prüfung, die ihre Erwartung aus dem Prüfling ableitet, prüft gegen sich selbst |
| Mindestens 10 Fremdschlüssel | Ein Dump ohne Beziehungen spielt Zeilen ein und ist keine wiederherstellbare Datenbank: Die Anwendung verlässt sich auf `ON DELETE CASCADE` (Konto löschen, Bilder, Öffnungszeiten) |
| Schemastand gegen die letzte Migration | Eine einspielbare Sicherung eines **älteren** Schemas ist brauchbar, braucht danach aber `doctrine:migrations:migrate`. Wer das nicht weiß, sucht den Fehler in der Anwendung |
| `consent_at` ist NOT NULL | ⚠ Geprüft wird die **Spaltendefinition**, nicht der Inhalt: `consent_at` ist im Schema `NOT NULL`, eine Zählung von NULL-Werten könnte also nie anschlagen und wäre ein Prüfschritt, der strukturell immer grün ist. Aussagekräftig ist, ob die Sicherung die Bedingung mitgebracht hat |
| Zeilenzahlen je Tabelle, mit `--quelle` gegen das Original | `COUNT(*)`, nicht `information_schema.TABLE_ROWS` — letzteres ist bei InnoDB eine Schätzung und als Abgleich wertlos. Die Ausgabe nennt, **wie viele** Tabellen verglichen wurden; „alle stimmen überein" klingt auch bei einer einzigen richtig |

**Am 2026-09-12 damit gemessen** (gegen eine aus den Migrationen erzeugte Datenbank, da
Produktion nicht von außen erreichbar ist):

- Alle Migrationen laufen auf **MariaDB 10.5** durch — das war bisher eine Annahme, die
  `CLAUDE.md` an mehreren Stellen voraussetzt, ohne sie je geprüft zu haben.
- Eine gezogene Sicherung (44 kB, gepackt 7,7 kB) spielt fehlerfrei ein: 19 von 19
  Tabellen, 14 Fremdschlüssel, Schemastand `Version20260904120000` = Stand des Codes.
- Der Abgleich gegen die Quelle findet nachgestellte Abweichungen: drei nachträglich
  eingefügte Zeilen wurden als `app_waitlist_entry: Quelle 2, Sicherung 0 (unersetzlich!)`
  und `cuisine: Quelle 21, Sicherung 20` gemeldet, Rückgabewert 1.

⚠ **Was das Skript NICHT beantwortet: ob überhaupt gesichert wird.** Es prüft eine Datei,
die ihm jemand gibt — es kann nicht wissen, ob sie von gestern ist oder aus dem Frühjahr.
Genau das bleibt der offene Teil von BE-03, und die Frist dafür steht unverändert.

⚠ Seit Feature 08 liegen dort E-Mail-Adressen Dritter mit Einwilligungszeitpunkt — die
lassen sich nicht rekonstruieren. Bei den Restaurantdaten wäre ein Verlust ärgerlich,
hier ist er endgültig.

| | |
|---|---|
| **Zu klären** | Zwei Ebenen, und sie werden leicht verwechselt: **Hostinger** kann den VPS als Ganzes sichern (Snapshot), **Coolify** kann die Datenbank sichern. Sichert eines von beidem? In welchem Takt, wie lange aufbewahrt, und liegt die Sicherung auf demselben Rechner wie die Datenbank? |
| **Zu prüfen** | Eine Sicherung einmal einspielen — ein Rückweg, den niemand gegangen ist, ist eine Annahme. Der Weg dafür steht jetzt: `make sicherung-pruefen DATEI=…` |
| **Woher die Datei** | Entweder aus Coolify (Datenbank-Ressource → „Backups" → herunterladen), oder von Hand auf dem VPS: `docker exec <db-container> mariadb-dump -u… -p… --single-transaction --quick --databases endlech \| gzip > endlech-$(date +%F).sql.gz` und herunterkopieren. ⚠ **Nicht** über eine von außen erreichbare Adresse — die Datenbank ist bewusst nicht öffentlich, und das soll sie bleiben |
| **Frist** | 2026-09-30 |
