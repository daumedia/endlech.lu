# 01 · Betroffenenrechte — Spezifikation

Status: `spec` · Stand: 2026-08-25 · **Anforderung vor Code**

## Zweck

Ein Konto muss sich löschen lassen, seine Daten müssen sich mitnehmen lassen, ein
vergessenes Passwort darf keine Sackgasse sein, und eine Einwilligung muss
widerrufbar sein. Nichts davon existiert heute (BF-04, BF-37).

**Warum das kein Nebenfeature ist:** Seit der BF-19-Reparatur wird eine
E-Mail-Änderung nur noch nach Bestätigung wirksam — was richtig ist. Wer aber sein
Passwort vergisst, kommt nicht mehr in sein Konto, und es gibt keinen Weg zurück.
Die Sackgasse ist real und heute offen.

## Rechtsgrundlage

| Recht | Artikel | Bisher |
|---|---|---|
| Löschung | Art. 17 DSGVO | fehlt |
| Datenübertragbarkeit | Art. 20 DSGVO | fehlt |
| Widerruf der Einwilligung | Art. 7 Abs. 3 DSGVO | fehlt (Wartelisten) |
| Auskunft | Art. 15 DSGVO | über den Export erfüllt |

Das Zurücksetzen des Passworts ist **kein** Betroffenenrecht — es steht hier, weil
es dieselbe Sackgasse betrifft und dieselbe Mechanik braucht (Token, Frist, Mail).

## User Stories

- **US-01** · Als Nutzer möchte ich mein Konto endgültig löschen können.
- **US-02** · Als Nutzer möchte ich alles herunterladen, was über mich gespeichert ist.
- **US-03** · Als Nutzer möchte ich ein vergessenes Passwort zurücksetzen können.
- **US-04** · Als Wartelisten-Interessent möchte ich meine Anmeldung zurückziehen können.

## Nicht im Scope

- Löschen einzelner Beiträge (ein Restaurant ist eine Sachangabe, keine Personenangabe)
- Ein Konto-Bereich für Admins zum Löschen fremder Konten
- Aufbewahrungsfristen und automatische Bereinigung

## Akzeptanzkriterien

### Konto löschen (US-01)

- **AK-01** · Angenommen, ein angemeldeter Nutzer öffnet sein Profil, wenn er
  hinunterscrollt, dann findet er einen Bereich „Konto löschen" mit einer
  Beschreibung dessen, was verschwindet und was bleibt.
- **AK-02** · Angenommen, er löst die Löschung aus, wenn das Formular abgesendet
  wird, dann muss er **sein Passwort eingeben** — ein Klick allein genügt nicht.
- **AK-03** · Angenommen, das Passwort ist falsch, wenn abgesendet wird, dann bleibt
  das Konto bestehen und es erscheint eine Meldung.
- **AK-04** · Angenommen, die Löschung läuft durch, wenn danach geprüft wird, dann
  ist der Nutzer abgemeldet, sein Datensatz ist weg, und eine erneute Anmeldung mit
  denselben Zugangsdaten schlägt fehl.
- **AK-05** · Angenommen, der Nutzer hatte Restaurants eingereicht, wenn sein Konto
  gelöscht wird, dann **bleiben die Restaurants** und ihr `submittedBy` wird `NULL`.
- **AK-06** · Angenommen, der Nutzer hatte einen Avatar, wenn sein Konto gelöscht
  wird, dann verschwindet auch die **Datei** aus `public/uploads/avatars/`.
- **AK-07** · Angenommen, der Nutzer hatte Passkeys, wenn sein Konto gelöscht wird,
  dann verschwinden sie mit (Kaskade).
- **AK-08** · Angenommen, die Löschung ist erfolgt, wenn geprüft wird, dann hat der
  Nutzer eine Bestätigungsmail an seine bisherige Adresse bekommen.

### Daten exportieren (US-02)

- **AK-09** · Angenommen, ein angemeldeter Nutzer fordert seine Daten an, wenn die
  Antwort kommt, dann ist es eine **JSON-Datei zum Herunterladen** mit
  `Content-Disposition: attachment`.
- **AK-10** · Angenommen, der Export wird geöffnet, wenn er betrachtet wird, dann
  enthält er Stammdaten, Einreichungen, Vorschläge und Passkey-Namen — und **kein**
  Passwort, keinen Token und keinen fremden Datensatz.
- **AK-11** · Angenommen, der Export wird angefordert, wenn er gedeckelt ist, dann
  greift ein Limiter — er liest den halben Bestand des Nutzers zusammen.

### Passwort zurücksetzen (US-03)

- **AK-12** · Angenommen, jemand hat sein Passwort vergessen, wenn er auf der
  Anmeldeseite ist, dann findet er dort einen Link „Passwort vergessen".
- **AK-13** · Angenommen, eine Adresse wird eingegeben, wenn abgesendet wird, dann
  ist die Antwort **immer dieselbe** — egal ob die Adresse existiert
  (Anti-Enumeration, wie bei der Registrierung).
- **AK-14** · Angenommen, die Adresse existiert, wenn die Mail ankommt, dann enthält
  sie einen Link mit einem Token, der **eine Stunde** gilt.
- **AK-15** · Angenommen, der Link wird geöffnet, wenn das Formular abgesendet wird,
  dann ist das neue Passwort gesetzt und der Token **verbraucht** — ein zweiter
  Aufruf schlägt fehl.
- **AK-16** · Angenommen, der Token ist älter als eine Stunde, wenn er eingelöst
  wird, dann erscheint eine Meldung, die das sagt, und ein Weg zu einem neuen Link.
- **AK-17** · Angenommen, das Zurücksetzen wird missbraucht, wenn es gedeckelt ist,
  dann greift ein Limiter — jeder Aufruf verschickt eine Mail an eine frei wählbare
  Adresse.
- **AK-18** · Angenommen, ein Passwort wurde zurückgesetzt, wenn danach geprüft
  wird, dann ist ein offener E-Mail-Änderungsvorgang **abgeräumt** — sonst liefe
  eine Übernahme durch, die vor dem Zurücksetzen angestoßen wurde.

### Widerruf der Einwilligung (US-04)

- **AK-19** · Angenommen, eine Wartelisten-Mail kommt an, wenn sie betrachtet wird,
  dann enthält sie einen **Abmeldelink**.
- **AK-20** · Angenommen, der Link wird geöffnet, wenn er bestätigt wird, dann ist
  der Eintrag **gelöscht**, nicht nur auf einen Status gesetzt.
- **AK-21** · Angenommen, derselbe Link wird erneut geöffnet, wenn er verarbeitet
  wird, dann erscheint eine verständliche Antwort statt eines Fehlers.

### Datenschutz und Missbrauchsschutz

- **AK-22** · Angenommen, ein Token wird erzeugt, wenn er geprüft wird, dann ist er
  kryptografisch zufällig und mindestens 32 Byte lang.
- **AK-23** · Angenommen, ein fremder Token wird untergeschoben, wenn er eingelöst
  wird, dann trifft er nur das Konto, zu dem er gehört.
- **AK-24** · Angenommen, ein Export oder eine Löschung wird ohne Anmeldung
  versucht, wenn geprüft wird, dann führt der Weg zur Anmeldung.

## Edge Cases

- **EC-01** · Ein Konto ohne Einreichungen, ohne Avatar und ohne Passkeys lässt sich
  ebenso löschen wie eines mit allem.
- **EC-02** · Ein Nutzer, der sich selbst löscht, während er der einzige Admin ist:
  Das Projekt hat genau ein Admin-Konto (B19/FB-01) — die Löschung des letzten
  Admins wird **abgelehnt**, sonst ist der Verwaltungsbereich unerreichbar.
- **EC-03** · Ein Zurücksetzen-Token für ein zwischenzeitlich gelöschtes Konto
  läuft ins Leere, ohne einen Fehler zu erzeugen.
- **EC-04** · Ein Widerruf für einen bereits gelöschten Wartelisten-Eintrag
  ebenfalls.

## Offene Fragen

Keine. Die Anforderungen sind rechtlich vorgegeben; die Fristen (eine Stunde für
das Zurücksetzen, sieben Tage für Wartelisten-Bestätigungen) folgen dem, was im
Projekt bereits gilt.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Löschung sofort oder mit Frist | sofort | Eine Karenzzeit wäre ein zweiter Zustand mit eigener Mechanik; Art. 17 verlangt „unverzüglich" |
| 2 | Restaurants mitlöschen | nein | Eine Barrierefreiheitsangabe ist eine Sachangabe. `submittedBy` wird `NULL` — die Person verschwindet, die Auskunft bleibt |
| 3 | Export als JSON | ja | Art. 20 verlangt ein „strukturiertes, gängiges, maschinenlesbares Format" |
| 4 | Widerruf löscht statt zu markieren | löscht | Ein Widerruf, nach dem der Datensatz bleibt, ist keiner |
| 5 | Passwort-Token eine Stunde | eine Stunde | Kürzer als die 24 Stunden der Registrierung: Wer zurücksetzt, sitzt vor dem Postfach |
