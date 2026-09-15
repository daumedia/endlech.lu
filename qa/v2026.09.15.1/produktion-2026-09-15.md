# v2026.09.15.1 · Deploy-Nachprüfung auf der Produktion (2026-09-15)

Release `v2026.09.15.1`, `master` auf `36e9a71`, in Coolify ausgerollt. Enthält #137: Violett #9333ea in
Presse-Kit und E-Mails, `support@endlech.lu` statt der nicht existierenden `info@endlech.lu`.

⚠ **Nur lesende Abrufe.** Auf der Produktion wurde nichts abgeschickt und keine Mail ausgelöst.

## Kontaktadresse (curl)

Je Seite: HTTP-Status, Zahl der `mailto:support@endlech.lu` (Seite + Fußzeile), Zahl der `info@endlech.lu`.

```
lb  legal          200 · support@ 2 · info@ 0
lb  accessibility  200 · support@ 2 · info@ 0
de  legal          200 · support@ 2 · info@ 0
de  accessibility  200 · support@ 2 · info@ 0
fr  legal          200 · support@ 2 · info@ 0
fr  accessibility  200 · support@ 2 · info@ 0
en  legal          200 · support@ 2 · info@ 0
en  accessibility  200 · support@ 2 · info@ 0
pt  legal          200 · support@ 2 · info@ 0
pt  accessibility  200 · support@ 2 · info@ 0
```

Weitere neun Seiten ohne Treffer für `info@endlech.lu`: `/de/`, `/de/about`, `/de/presse`, `/de/partner`,
`/de/organisationen`, `/pt/`, `/de/community/suggest`, `/de/changelog`, `/de/open`.

## Übrige Prüfungen

| Prüfung | Ergebnis |
|---|---|
| Fußzeile | `v2026.09.15.1` |
| `/health` | 200 |
| Presse-Paket, Nutzungsbedingungen | `#9333ea` fünfmal, `#7c3aed` keinmal, `#01b6ed` fünfmal |
| `/de/presse`, Bedingungen | „Violett #9333ea" |
| `sw.js` | `CACHE_VERSION = 'endlech-v4'` (unverändert, keine Bilddatei geändert) |
| `/changelog` | kein neuer Eintrag (SILENT) |

## Nicht von außen prüfbar

- **Wohin interne Meldungen gehen** (`CONTACT_EMAIL` auf Anwendung und Worker). Der Wert steht in Coolify; die
  Vorgabe im Image ist `support@endlech.lu`. Belegbar nur durch eine echte Meldung — etwa eine Probe-Meldung über
  das Formular auf `/de/accessibility`, die im Postfach `support@endlech.lu` ankommen muss. Nicht ausgelöst.
- **Farben in den E-Mails:** Die Vorlagen sind im Image; eine Mail müsste ausgelöst werden. Belegt durch den
  Diff (nur Farbwerte) und `lint:twig` vor dem Merge.
