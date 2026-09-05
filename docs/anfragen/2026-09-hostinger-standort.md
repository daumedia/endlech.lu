# Anfrage an Hostinger — Vertragspartner und Serverstandort

Stand: 2026-09-05 · deckt **DS-02b** und **DS-02c** ab
Weg: Support-Ticket im Kundenkonto (hPanel → Hilfe) oder `dpo@hostinger.com`

> **Was vorher geprüft wurde:** Der Serverstandort ist am 2026-09-05 **gemessen**
> worden — Reverse DNS `srv1947421.hstgr.cloud`, AS47583, Geolokalisierung Düsseldorf,
> 19 ms Laufzeit. Das ist eine Momentaufnahme. Die Produktseite nennt nur Regionen
> („Europe"), keine Länder, und sagt nichts darüber, ob der Standort zugesichert ist
> oder wechseln kann. Genau das ist die offene Frage.

---

## Was du im eigenen Konto selbst nachsehen kannst — vor der Anfrage

Beides steht vermutlich schon da, dann erübrigt sich die halbe Anfrage:

| Frage | Wo im hPanel |
|---|---|
| **Welche Gesellschaft ist Vertragspartner?** (DS-02b) | Rechnung oder Zahlungsbeleg — dort steht die ausstellende Gesellschaft. Das DPA nennt drei: Hostinger International Ltd. (Zypern), Hostinger UK Ltd. und **Hostinger Global S.à r.l. (Luxemburg)** |
| **Welcher Standort ist für den VPS gewählt?** (DS-02c, Teil 1) | VPS → Übersicht bzw. Einstellungen; dort steht die Region |

Bleibt danach offen, ob der Standort **vertraglich zugesichert** ist — das steht
erfahrungsgemäß nirgends und ist der eigentliche Grund für die Anfrage.

---

## Textvorschlag (deutsch)

Betreff: **Serverstandort und Vertragspartner — Angaben für unser Verarbeitungsverzeichnis**

Sehr geehrte Damen und Herren,

wir betreiben auf einem VPS bei Ihnen eine Anwendung, die personenbezogene Daten von
Nutzerinnen und Nutzern aus der EU verarbeitet. Für unser Verarbeitungsverzeichnis nach
Art. 30 DSGVO benötigen wir zwei Auskünfte:

**1. Vertragspartner.** Welche Ihrer Gesellschaften ist unser Vertragspartner? Das Data
Processing Addendum nennt Hostinger International Ltd. (Zypern), Hostinger UK Limited und
Hostinger Global S.à r.l. (Luxemburg). Für die Frage, welches Recht gilt und an wen sich
Betroffene wenden, ist das maßgeblich.

**2. Serverstandort.** Unser Server steht derzeit in Deutschland. Ist dieser Standort
vertraglich zugesichert, oder kann er im Rahmen des Betriebs innerhalb einer Region
wechseln? Falls er wechseln kann: Werden wir vorab informiert, und ist eine Beschränkung
auf die EU/den EWR möglich?

Mit freundlichen Grüßen

---

## Textvorschlag (englisch)

Subject: **Server location and contracting entity — details for our record of processing**

Dear Sir or Madam,

We run an application on a VPS with you that processes personal data of users in the EU.
For our record of processing activities under Art. 30 GDPR we need two pieces of
information:

**1. Contracting entity.** Which of your entities is our contracting party? The Data
Processing Addendum names Hostinger International Ltd. (Cyprus), Hostinger UK Limited and
Hostinger Global S.à r.l. (Luxembourg). This determines applicable law and where data
subjects should turn.

**2. Server location.** Our server is currently located in Germany. Is this location
contractually guaranteed, or may it change within a region during normal operation? If it
may change: will we be informed in advance, and can the location be restricted to the
EU/EEA?

Kind regards

---

## Wenn die Antwort da ist

Einzutragen in `docs/datenschutz.md`, Abschnitt **Hostinger**:

| Antwort | Trägt ein |
|---|---|
| Vertragsgesellschaft | neue Zeile **„Vertragspartner"**; DS-02b abhaken |
| Standort zugesichert? | Zeile **„Serverstandort"** ergänzen — „gemessen" wird zu „zugesichert" oder bleibt mit dem Vorbehalt stehen; DS-02c abhaken |

⚠ Sollte der Standort **nicht** zugesichert sein und ein Wechsel außerhalb der EU möglich:
Das wäre kein Formfehler, sondern ein Punkt, der eine Entscheidung verlangt — Tarifwechsel,
Standortbindung oder ein anderer Anbieter. Dann gehört er als Befund in
`features/befunde.md`, nicht als Fußnote hierher.
