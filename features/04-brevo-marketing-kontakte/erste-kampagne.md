# Erste Kampagne an den übertragenen Bestand — Textvorlage

Erfüllt **AK-24**. Stand: 2026-08-29

Diese Datei ist der **Entwurf**, nicht die Kampagne. Die Kampagne selbst wird in
Brevo angelegt — Betreff, Inhalt, Versandzeitpunkt und Segmente gehören dorthin
und nicht in dieses Repository (Entscheidung 10 des Entwurfs). Was hier steht,
ist der Text, den jemand dort einfügt, und die Begründung für seine Form.

**Nachweis für die QA:** Screenshot der angelegten Kampagne oder ihre
Kampagnen-ID, hier nachgetragen, sobald sie existiert.

> Kampagnen-ID: ⚠ noch nicht angelegt

---

## Warum der erste Absatz so aussehen muss

Der Bestand, der mit `app:marketing:import --commit` übertragen wird, sind
Menschen, die sich **ab August 2026** auf einer Warteliste eingetragen haben —
teils Monate, bevor diese Kampagne sie erreicht. Sie haben damals einer
Kontaktaufnahme zugestimmt; dass daraus jetzt eine Mail wird, ist für sie
womöglich überraschend.

**Deshalb nennt der erste Absatz die Herkunft der Adresse, bevor irgendetwas
anderes gesagt wird.** Nicht als Fußnote, nicht im Kleingedruckten am Ende. Wer
nicht innerhalb der ersten drei Zeilen versteht, warum er diese Mail bekommt,
liest sie als Spam — und meldet sie im Zweifel auch so. Eine
Spam-Beschwerdequote schadet der Zustellrate aller künftigen Mails, auch der
Bestätigungsmails.

Der Abmeldelink ist in Brevo Pflicht und wird automatisch eingefügt; er gehört
zusätzlich **sichtbar** in den Text, nicht nur in den Fußbereich.

---

## Deutsch

**Betreff:** Endlech.lu — was seit Ihrer Anmeldung passiert ist

> **Sie erhalten diese E-Mail, weil Sie sich auf endlech.lu in die Warteliste
> für das Partnerprogramm eingetragen und dabei zugestimmt haben, Neuigkeiten
> zu bekommen.** Falls Sie das nicht mehr möchten, melden Sie sich mit einem
> Klick wieder ab — der Link steht am Ende dieser Mail und wirkt sofort.
>
> [Fließtext: Stand des Projekts, Zahl der erfassten Restaurants, was als
> Nächstes kommt, was das Partnerprogramm kosten wird, sobald es steht.]
>
> [Abmeldelink, sichtbar]

⚠ **Der erste Absatz ist zu variieren, wenn die Kampagne an mehrere Herkünfte
zugleich geht.** Das Attribut `ORIGIN` unterscheidet Partner, Gemeinde,
Unternehmen, Verein und Nutzerkonto. „Warteliste für das Partnerprogramm" stimmt
nur für `PARTNER`. Entweder je Segment eine eigene Kampagne, oder der Satz wird
allgemeiner formuliert („in eine unserer Wartelisten eingetragen"). **Ein
falscher Herkunftssatz ist schlimmer als ein allgemeiner** — er behauptet etwas
über die Person, das nicht stimmt.

## Englisch

**Subject:** Endlech.lu — what has happened since you signed up

> **You are receiving this email because you joined the partner programme
> waiting list on endlech.lu and agreed to receive news.** If you would rather
> not, unsubscribe with one click — the link is at the end of this email and
> takes effect immediately.

## Französisch

**Objet :** Endlech.lu — ce qui s'est passé depuis votre inscription

> **Vous recevez cet e-mail parce que vous vous êtes inscrit·e sur la liste
> d'attente du programme partenaire sur endlech.lu et avez accepté de recevoir
> des actualités.** Si vous ne le souhaitez plus, désinscrivez-vous en un clic —
> le lien se trouve à la fin de cet e-mail et prend effet immédiatement.

## Luxemburgisch

**Sujet:** Endlech.lu — wat zanter Ärer Umeldung geschitt ass

> **Dir kritt dës E-Mail, well Dir Iech op endlech.lu an d'Waardelëscht fir de
> Partnerprogramm agedroen an dobäi zougestëmmt hutt, Neiegkeeten ze kréien.**
> Wann Dir dat net méi wëllt, mellt Iech mat engem Klick of — de Link steet um
> Enn vun dëser Mail a wierkt direkt.

---

## Vor dem Versand

- [ ] Die Sprache der Kampagne über das Attribut `LOCALE` segmentieren — sonst
      bekommt ein französischsprachiger Empfänger einen deutschen Text.
- [ ] Gegen `FUNNEL_STATUS` filtern: Wer bereits `converted` ist, braucht keine
      Werbung fürs Partnerprogramm mehr (AK-08).
- [ ] Herkunftssatz gegen das Segment prüfen (siehe Warnung oben).
- [ ] `docs/datenschutz.md` und `/legal` stehen (AK-34) — **das ist die
      Vorbedingung, nicht eine Nacharbeit.**
