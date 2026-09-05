# Checkliste: was in den Konten zu tun ist

Stand: 2026-09-05 · Alles hier braucht eine Anmeldung, die außerhalb dieses Projekts
liegt. Nach jedem Häkchen gehört das Datum in `docs/datenschutz.md` — ohne Datum ist
später nicht feststellbar, ab wann etwas galt.

---

## 1 · Brevo: anonyme Nachverfolgung einschalten (DS-04)

**Das dringendste**, weil es bereits laufende Bestätigungsmails betrifft — nicht erst
künftige Kampagnen.

- [ ] In Brevo anmelden
- [ ] Oben rechts auf den **Kontonamen** klicken → **Settings**
- [ ] Reiter **Default settings** → Abschnitt **Tracking**
- [ ] **Anonymous email tracking** auf **Yes**
- [ ] Den Hinweistext lesen → **Activate**
- [ ] Oben rechts **Save** (ohne diesen Schritt greift nichts)
- [ ] Datum in `docs/datenschutz.md` eintragen, Abschnitt „Öffnungs- und Klickverfolgung"

⚠ **Wirkt nur für künftige und geplante Mails.** Was bis dahin an personenbezogenen
Öffnungsdaten entstanden ist, bleibt in Brevo liegen. Wer es entfernen will, muss es
dort löschen — das ist ein eigener Vorgang.

⚠ **Danach ist keine Segmentierung nach Öffnern mehr möglich.** Für dieses Projekt kein
Verlust; falls doch einmal eine Kampagne darauf aufbauen soll, ist die Einstellung
umkehrbar — dann aber mit der Einwilligungsfrage von vorn.

---

## 2 · Brevo: Anfrage abschicken (DS-01b, DS-01c, DS-01d)

- [ ] `docs/anfragen/2026-09-brevo-dpa.md` öffnen, Text an `dpo@brevo.com` senden
- [ ] Datum des Versands hier vermerken: ______________
- [ ] Antwort in `docs/datenschutz.md` eintragen (die Tabelle steht am Ende der Anfrage)

---

## 3 · Hostinger: erst nachsehen, dann fragen (DS-02b, DS-02c)

- [ ] hPanel → **Rechnungen**: Welche Gesellschaft stellt aus? → DS-02b
      Notiert: ______________________________
- [ ] hPanel → **VPS** → Übersicht: Welche Region ist eingestellt?
      Notiert: ______________________________
- [ ] Bleibt offen, ob der Standort zugesichert ist → `docs/anfragen/2026-09-hostinger-standort.md` senden
- [ ] Antworten in `docs/datenschutz.md`, Abschnitt Hostinger, eintragen

---

## 4 · Sicherungen prüfen (BE-03)

Kein Datenschutzpunkt, aber der mit dem größten Schaden, wenn er unbeantwortet bleibt:
Seit Feature 08 liegen E-Mail-Adressen Dritter mit Einwilligungszeitpunkt in der
Datenbank. Die lassen sich nicht rekonstruieren.

- [ ] **Hostinger:** Gibt es automatische VPS-Snapshots? Takt? Aufbewahrung?
- [ ] **Coolify:** Ist eine Datenbanksicherung eingerichtet? Wohin schreibt sie?
- [ ] ⚠ **Liegt die Sicherung auf demselben Rechner wie die Datenbank?** Dann ist sie
      keine — ein Ausfall des Rechners nimmt beide mit
- [ ] **Eine Sicherung einmal einspielen.** Ein Rückweg, den niemand gegangen ist, ist
      eine Annahme, keine Sicherung
- [ ] Ergebnis in `docs/datenschutz.md` unter BE-03 eintragen

---

## 5 · Uptime-Prüfung einrichten (BE-01)

- [ ] Konto bei UptimeRobot, Better Stack o. ä. anlegen
- [ ] Prüfung 1: `https://endlech.lu/health` — alle 5 Minuten, erwartet 200
- [ ] Prüfung 2: `https://endlech.lu/open.json` — alle 15 Minuten, erwartet 200
      ⚠ Beide, nicht nur die erste: `/health` macht bewusst keine Datenbankabfrage und
      meldet auch dann 200, wenn die Datenbank weg ist
- [ ] Zertifikatswarnung 14 Tage vor Ablauf
- [ ] Alarmadresse eintragen — dieselbe wie `app.contact_email`
- [ ] **Alarm einmal auslösen** (Prüfung kurz auf eine falsche URL zeigen lassen) und
      nachsehen, ob die Meldung ankommt. Ein Alarm, der nie ausgelöst hat, hat nie
      funktioniert
- [ ] Ergebnis in `docs/datenschutz.md` unter BE-01 eintragen

---

## Reihenfolge, wenn die Zeit knapp ist

1. **Punkt 1** — läuft gegen bereits verschickte Mails, jeder Tag zählt
2. **Punkt 4** — ein Datenverlust ist der einzige Schaden hier, der endgültig ist
3. **Punkt 5** — bis dahin bemerkt einen Ausfall nur, wer sich beschwert
4. **Punkte 2 und 3** — wichtig für die Rechenschaftslage, aber nichts läuft schief,
   solange sie offen sind
