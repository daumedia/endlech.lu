# Willkommens-Mail „Willkommen · Konto“ — Vorlagen für Brevo (T06)

Die Mail verschickt **Brevo**, nicht die Anwendung (design.md, Entscheidung 8). Diese Dateien sind die
Quelle der fünf Vorlagen, die in Brevo als „HTML custom code“ eingefügt werden — Muster wie
`features/04-brevo-marketing-kontakte/erste-kampagne.md`.

- `texte.py` — die Texte je Sprache (Begriffe aus den Katalogen der Einführung: `onboarding.welcome.*`,
  `onboarding.search`, `newsletter.text`). **Wer einen Text ändert, ändert ihn hier und baut neu.**
- `bauen.py` — erzeugt `willkommen-{de,lb,fr,en,pt}.html` (`python3 -I bauen.py` in diesem Verzeichnis).

**Inhalt nach AK-47/AK-48:** Links auf `/{locale}/restaurants`, `/{locale}/community/suggest` und
`/{locale}/app` (Warteliste, **kein** TestFlight-Link); persönliche Angabe nur `{{ contact.CONTACT_NAME }}`;
sichtbarer Abmeldelink `{{ unsubscribe }}`; keine Bewertungen, Kommentare, Favoriten (AK-21).

## Stand in Brevo (Konto „Daumedia (endlech.lu)“, kostenloser Tarif)

**Zu OF-04 (Tarif):** Der kostenlose Tarif trägt die Automation — Einstieg über die Liste, Bedingung auf Attribute und
„nur einmal“ gehen; nur mehrere Zweige je Verzweigung sind kostenpflichtig, deshalb die Ja/Nein-Kette. Die Spec-Frage
formal schließen gehört zu `/sdd-klaeren 12`.

Absender aller Vorlagen: **Endlech.lu <support@endlech.lu>** (verifiziert, Domain mit DKIM/DMARC).
`noreply@endlech.lu` ist in Brevo kein freigegebener Absender.

| Sprache | Brevo-Vorlage | Betreff | Vorschautext | Stand 2026-10-23 |
|---|---|---|---|---|
| de | #2 „Willkommen · Konto · de“ | Willkommen bei Endlech.lu | Drei Dinge, die du sofort tun kannst. | angelegt, aktiv |
| lb | #3 „Willkommen · Konto · lb“ | Wëllkomm op Endlech.lu | Dräi Saachen fir direkt unzefänken. | angelegt, aktiv |
| fr | #4 „Willkommen · Konto · fr“ | Bienvenue sur Endlech.lu | Trois choses à faire dès maintenant. | angelegt, aktiv |
| en | #5 „Willkommen · Konto · en“ | Welcome to Endlech.lu | Three things you can do right away. | angelegt, aktiv |
| pt | #6 „Willkommen · Konto · pt“ | Boas-vindas ao Endlech.lu | Três coisas que pode fazer já. | angelegt, aktiv |

„Aktiv“ heißt bei einer Vorlage nur „in Automationen auswählbar“ — verschickt wird dadurch nichts.

## Automation „Willkommen · Konto“ — **aktiv seit 2026-10-23** (eingeschaltet unmittelbar vor dem Ausrollen von `v2026.10.23`)

Brevo-Automation **#1** „Willkommen · Konto“, `https://app.brevo.com/automation/edit/1`.

⚠ **Im kostenlosen Tarif hat eine Verzweigung nur zwei Zweige** („Add Branch“ ist kostenpflichtig). Die
Sprachweiche ist deshalb eine Kette aus Ja/Nein-Verzweigungen; ein Kontakt nimmt den ersten passenden Zweig.
⚠ **Der Auslöser „Contact added to list“ berücksichtigt nur Kontakte, die nach dem Einschalten zur Liste kommen**
(Hinweis im Brevo-Panel). Damit ist AK-45 (kein Bestand) ohne eigene Einstellung erfüllt.
⚠ **Einfügen geht nur per Ziehen und Ablegen.** Beim Bauen per Skript nachgebildet (`DragEvent` mit
`DataTransfer`, Typ `automation/draggedcarddata`): `dragstart` an der Karte, dann `drop` auf die Ablagezone, deren
Mitte dem Exit des gewünschten Zweigs am nächsten liegt.
⚠ **Ein Mail-Schritt kopiert die Vorlage** in eine eigene Automationsnachricht (lb → Nachricht #7). Wer den Text
später ändert, ändert ihn **in der Automationsnachricht**, nicht nur in der Vorlage.

| Schritt | Inhalt | Stand 2026-10-23, 15:35 |
|---|---|---|
| Auslöser | Contact added to list → Liste 5 „Endlech.lu · Neuigkeiten“ | gespeichert |
| Verzweigung #2 | A: `ORIGIN` ist `ACCOUNT` → weiter · B: Ende (AK-43) | gespeichert |
| Verzweigung #3 | A: `LOCALE` ist `lb` → Mail (Nachricht #7 aus Vorlage #3) · B: weiter | gespeichert |
| Verzweigung #5 | A: `LOCALE` ist `fr` → Mail (Nachricht #8 aus Vorlage #4) · B: weiter | gespeichert |
| Verzweigung #7 | A: `LOCALE` ist `en` → Mail (Nachricht #9 aus Vorlage #5) · B: weiter | gespeichert |
| Verzweigung #9 | A: `LOCALE` ist `pt` → Mail (Nachricht #10 aus Vorlage #6) · B: Mail de (Nachricht #11 aus Vorlage #2) | gespeichert |
| Einstellungen | Name „Willkommen · Konto“; „Allow contact re-entry after exit“ **aus** → jeder Kontakt nur einmal (AK-44) | gespeichert |
| Zustand | **Active** (seit 2026-10-23, kurz vor dem Ausrollen) | — |

Nachweis: `qa/12/t06/automation-willkommen-konto.png` (Aufbau), `qa/12/t06/automation-einstellungen-wiedereintritt.png`
(Wiedereintritt aus). Alle fünf Mail-Schritte mit Absender `support@endlech.lu` / „Endlech.lu“ und dem Betreff
der jeweiligen Vorlage.

**Testversand 2026-10-23, 15:58** über Brevos „Test“ an ein Testpostfach des Betreibers (als Kontakt eigens dafür angelegt,
**keiner Liste** zugeordnet, `CONTACT_NAME` = „Michael“): alle fünf Mail-Schritte „Processed“, im Versandprotokoll
je Mail **Sent → Delivered → First opening** (`qa/12/t06/testversand-processed.png`; das Versandprotokoll zeigt die private Adresse und liegt deshalb nicht im
öffentlichen Repository).
Der Test schickt jede Nachricht der Automation an den Kontakt — Verzweigungen spielen dabei keine Rolle.
**Vom Betreiber im Postfach geprüft (2026-10-23): passt** — Anrede, Links, Abmeldelink, Zustellung.
⚠ Das prüft Zustellung und Inhalt, **nicht** die Weiche (Liste, `ORIGIN`, `LOCALE`, „nur einmal“). Die zeigt sich erst
im Betrieb: ein neues Konto mit Newsletter-Häkchen auf `/fr/…` bekommt genau die französische Mail.

**Eingeschaltet 2026-10-23** unmittelbar vor dem Ausrollen von `v2026.10.23`. Nach dem Ausrollen vom Betreiber auf der
Produktion nachgeprüft: Registrierung auf `/fr/`, Einführung, Newsletter im letzten Schritt → französische
Willkommens-Mail angekommen (Weiche `ORIGIN`/`LOCALE` im Betrieb bestätigt).

**Einschalten erst unmittelbar vor dem Ausrollen von Feature 12** (sonst bekäme ein neues Konto bis dahin die
deutsche Fassung, weil der laufende Code `LOCALE` fest auf `de` setzt; Konten, die vor dem Einschalten zur Liste
kamen, holt Brevo nicht nach).
