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
| **Anbieter** | Brevo SA (vormals Sendinblue), 7 rue de Madrid, 75008 Paris, Frankreich · Datenschutzbeauftragter: `dpo@brevo.com` |
| **Sitz** | Frankreich (EU) |
| **Drittlandsübermittlung** | ⚠ **ja — über Unterauftragsverarbeiter.** Siehe die Liste unten. Abgesichert über EU-US Data Privacy Framework und Standardvertragsklauseln |
| **Zwecke** | (1) Versand von Transaktionsmails, (2) **seit Feature 04:** Führung eines Kontaktbestands für Werbe-Kampagnen |
| **Rechtsgrundlage** | Zweck 1: Art. 6 Abs. 1 lit. b (Vertrag/vorvertraglich) · Zweck 2: **Art. 6 Abs. 1 lit. a — Einwilligung** |
| **AV-Vertrag** | **Annex 2 der Brevo General Terms and Conditions** — gilt automatisch mit Annahme der AGB, **keine gesonderte Unterzeichnung nötig**. Bei Widersprüchen geht das DPA den AGB vor (Ziff. 1.4) |
| **Gelesene Fassung** | Annex 2 – Data Protection Agreement, Stand **15.05.2024**, Kopie in `qa/brevo-dpa/` · geprüft am **2026-09-05** |
| **Löschung nach Vertragsende** | **100 Tage** (Ziff. 8.1). Eine Löschbescheinigung gibt es auf erste Anfrage (Ziff. 8.2). ⚠ Innerhalb dieser Frist muss ein Export selbst erfolgen |

⚠ **Die frühere Angabe „EU — keine Drittlandsübermittlung nach Kapitel V DSGVO" war
falsch** und stand hier seit Feature 04. Brevo selbst sitzt in Frankreich, aber mehrere
seiner Unterauftragsverarbeiter verarbeiten in den USA — **Datadog** (Protokollierung)
sogar ausschließlich dort. Die Übermittlung ist zulässig, aber sie findet statt, und ein
Verzeichnis, das sie verneint, trägt bei einer Auskunft nicht. Korrigiert am 2026-09-05
nach Lektüre des Vertragstexts.

**Unterauftragsverarbeiter** (Schedule 1 des DPA, Stand 15.05.2024):

| Dienst | Aufgabe | Serverstandort | Grundlage |
|---|---|---|---|
| Google Cloud Platform | Hosting | Belgien | DPF + Standardvertragsklauseln |
| Scaleway/Iliad | Hosting | Frankreich | — (EU) |
| OVH | Hosting | Frankreich | — (EU) |
| Hetzner Online | Hosting | Deutschland | — (EU) |
| Cloudflare | CDN und Firewall | USA/EU | DPF + Standardvertragsklauseln |
| Zendesk | Support-Ticketsystem | EU/USA | BCR + SCC + „Data Centre Location Add-On" |
| **Datadog** | Protokollierung und Fehlersuche | **USA** | EU-US Data Privacy Framework |

Dazu optional, nur bei Nutzung des jeweiligen Dienstes: Looker (Dashboards), Integry
(Integrationen), Convrrt (Landingpages) — alle mit US-Bezug. **Endlech.lu nutzt keinen
davon**; wer einen einschaltet, erweitert damit die Übermittlung und zieht diese Liste mit.

⚠ **Über neue Unterauftragsverarbeiter wird nur informiert, wer sich dafür angemeldet
hat.** Ziff. 6.2: *„Provided that Customer has subscribed to receive notifications via the
dedicated form"* — dann zehn Werktage vorher, mit Widerspruchsrecht. **Ohne diese
Anmeldung erfährt der Verantwortliche nichts** und kann sein Widerspruchsrecht nicht
ausüben. Das ist ein Handgriff im Brevo-Konto, kein Vertragsdetail: siehe DS-01.

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
      2026-09-05 erledigt.** Er ist Annex 2 der AGB und gilt automatisch mit deren
      Annahme; die Kernpunkte (Löschfrist 100 Tage, Unterauftragsverarbeiter samt
      Serverstandorten, Widerspruchsrecht) stehen jetzt oben. Offen bleiben DS-01b bis
      DS-01d — Anmeldung zu den Benachrichtigungen, Fassungsprüfung, Datum der Annahme
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
> ⚠ **DS-01b braucht wirklich eine Anfrage.** Ziffer 6.2 des DPA verlangt eine Anmeldung
> „via the dedicated form", **verlinkt dieses Formular aber nicht** — weder im
> Vertragstext noch auf den Hilfeseiten (beides am 2026-09-05 geprüft). Ohne Nachfrage
> lässt sich das Widerspruchsrecht gegen neue Unterauftragsverarbeiter nicht ausüben.


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

Stand 2026-09-05. Was hier fehlt, meldet seinen Ausfall nicht selbst.

| Bereich | Zustand | Wo |
|---|---|---|
| Fehler-Tracking | **läuft** — Sentry, EU-Region (`ingest.de.sentry.io`), nur `prod`, `send_default_pii: false`. DSN in Coolify gesetzt (Betreiber bestätigt 2026-09-05) | `config/packages/sentry.yaml` |
| Rate Limits | **läuft** — 22 Limiter, jeder verdrahtet und mit `when@test`-Override; `LimiterCoverageTest` färbt rot, sobald einer davon fehlt | `config/packages/framework.yaml` |
| Protokollierung | **läuft** — `prod` schreibt nach `stderr`, `!doctrine` und `!request` ausgeschlossen, damit keine Bestätigungstoken im Hoster-Log landen (BF-23) | `config/packages/monolog.yaml` |
| Lebendigkeitsprüfung | **läuft** — `/health`, sprachfrei, bewusst **ohne** Datenbankabfrage | `src/Controller/Health/` |
| **Messenger-Worker** | **überwacht seit 2026-09-05** — `app:messenger:watch` meldet einen Rückstau per Mail, täglich aus dem `marketing`-Zeitplan | siehe unten |
| **Uptime von außen** | ⚠ **fehlt** — vorbereitet, aber kein Wächter eingerichtet (BE-01) | — |
| Produktanalyse | ⚠ **fehlt** — als Feature auf der Roadmap, nicht als Betriebsmaßnahme (BE-02) | — |
| Sicherungen der Datenbank | ⚠ **ungeprüft** — ob Coolify sichert und wie oft, ist nicht dokumentiert (BE-03) | — |

### BE-01 · Uptime-Prüfung von außen — vorbereitet, noch einzurichten

Ein Konto kann dieser Durchgang nicht anlegen. Die Angaben stehen fertig; einzutragen
sind sie bei UptimeRobot, Better Stack oder einem gleichwertigen Dienst.

| | |
|---|---|
| **Endpunkt 1** | `https://endlech.lu/health` — alle 5 Minuten, erwartet **200** |
| **Endpunkt 2** | `https://endlech.lu/open.json` — alle 15 Minuten, erwartet **200** und gültiges JSON |
| **Zertifikat** | Ablaufwarnung 14 Tage vorher |
| **Alarmweg** | dieselbe Adresse wie `app.contact_email`; der Weg gehört **einmal ausgelöst**, bevor man sich auf ihn verlässt |

⚠ **Zwei Endpunkte, und das ist der Punkt.** `/health` beantwortet bewusst nur „läuft
der PHP-Prozess" — es macht **keine** Datenbankabfrage, damit ein kurzer Ausfall der
Datenbank nicht den Container mitreißt (der Neustart hülfe dort nichts, die Ursache
liegt außerhalb). Genau deshalb sagt ein grünes `/health` **nichts** darüber, ob die
Anwendung funktioniert. `/open.json` schließt die Lücke: Es liest Restaurants, Finanzen
und seit Feature 08 die App-Warteliste — wer damit 200 und gültiges JSON bekommt, weiß,
dass die Datenbank antwortet.

⚠ **Diese Prüfung ist die einzige, die einen vollständig stehenden Container bemerkt.**
Die Warteschlangen-Überwachung läuft im selben Prozess wie das, was sie überwacht: Steht
der Consumer, läuft auch sie nicht. Beide zusammen decken den Fall ab, keine allein.

### BE-02 · Produktanalyse — als Feature, nicht als Betriebsmaßnahme

Ein Analyse-Skript im Frontend ist eine **Produktänderung**: Es berührt die App-Hülle,
den Datenschutzabschnitt in `/legal` und — je nach Wahl — das Einwilligungsbanner. Es
gehört deshalb als Feature auf die Roadmap und nicht in einen Betriebsdurchgang.

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

### BE-03 · Sicherungen der Datenbank — ungeprüft

Ob Coolify die Datenbank sichert, in welchem Takt und wie lange die Sicherungen liegen,
ist nirgends dokumentiert. **Das gehört gemessen, nicht angenommen**: eine Sicherung
wiederherstellen, bevor sie gebraucht wird.

⚠ Seit Feature 08 liegen dort E-Mail-Adressen Dritter mit Einwilligungszeitpunkt — die
lassen sich nicht rekonstruieren. Bei den Restaurantdaten wäre ein Verlust ärgerlich,
hier ist er endgültig.

| | |
|---|---|
| **Zu klären** | Zwei Ebenen, und sie werden leicht verwechselt: **Hostinger** kann den VPS als Ganzes sichern (Snapshot), **Coolify** kann die Datenbank sichern. Sichert eines von beidem? In welchem Takt, wie lange aufbewahrt, und liegt die Sicherung auf demselben Rechner wie die Datenbank? |
| **Zu prüfen** | Eine Sicherung einmal einspielen — ein Rückweg, den niemand gegangen ist, ist eine Annahme |
| **Frist** | 2026-09-30 |
