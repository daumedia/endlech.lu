# Feature 17 · Browser-Durchgang (T24, T25) — 2026-11-14

Selbsttest des Baus, **keine QA**. Lokaler Server (`symfony server:start`, Port 8000), Entwicklungsdatenbank mit
Fixtures, Konto `user@endlech.lu`. Browser: eingebaute Vorschau (Chromium-Engine). Safari nicht geprüft.

## Ergebnisse

| AK | Prüfung | Ergebnis |
|---|---|---|
| AK-08 | Gast tippt auf Seite 2 der Suche auf ein Herz | Anmeldeseite mit „Melde dich an, um dir Sushi Zen zu merken.“ — `01-anmeldung-mit-merkhinweis-375.png` |
| AK-09 | Anmeldung danach | Gemerkt, Meldung, Seite 2 mit Sortierung. **Erster Lauf: Seite stand oben** (Turbo verliert den Anker der Weiterleitung). Behoben mit `scroll_to_controller` — zweiter Lauf: `scrollY` 2603, Herz sichtbar, Fokus auf dem Herz |
| AK-01, AK-48 | Herz in der Suche per Tastatur (Enter) | Gleiches Knopf-Element (Morph), Name „… aus Favoriten entfernen“, Meldung im Ansagebereich. **Erster Lauf: Fokus verloren** (Turbo setzt den Absendeknopf auf `disabled`). Behoben mit `favorite_button_controller` — zweiter Lauf: Fokus bleibt, `:focus-visible` |
| AK-01 | Seite springt nicht | Browser gleicht die eingefügte Meldung per Scroll-Anchoring aus; die Karte bleibt am Ort |
| AK-16 | Liste anlegen auf 375 px | Meldung, Liste mit 0 Lokalen — `02-gespeichert-uebersicht-375.png` |
| AK-22 | Häkchen auf der Detailseite | „Listen gespeichert.“ **Erster Lauf: Seite stand danach oben** (AK-51). Behoben: Rücksprung zu `#in-listen` bzw. `#notiz` |
| AK-33, AK-51 | Notiz speichern | Seite steht beim Abschnitt, Fokus auf „Notiz speichern“, keine waagerechte Scrollleiste — `03-detailseite-notiz-ruecksprung-375.png` |
| AK-38 | Filter „Nur meine Favoriten“ | Genau die drei gemerkten Lokale, Chip vorhanden |
| AK-14, AK-34 | Herz auf „Favoriten“ entfernen | Karte weg, Fokus auf der nächsten Karte, Meldung angesagt; Notiz unter der Karte |
| AK-63 | Abmelden, dann „Zurück“ | Server lädt neu → Anmeldeseite; weder Liste noch Notiz sichtbar (Chromium). **Safari offen** |
| AK-42 | Untere Leiste bei 320 px | lb, de, fr, en, pt: vier Punkte je 76 × 60 px, alle Beschriftungen einzeilig, kein Überlauf |
| AK-29 | Rückfrage | Name und Zahl, „Abbrechen“ und „Löschen“ je 276 × 48 px — `04-rueckfrage-liste-loeschen-375.png` |
| AK-47 | „Aus Liste entfernen“ | Zugänglicher Name „Aus Liste entfernen: Sushi Zen“, kein `aria-label` (WCAG 2.5.3) |

## axe-core 3.50 (WCAG 2.0/2.1/2.2 A + AA)

Geprüft: `/de/gespeichert`, `/de/gespeichert/favoriten`, `/de/gespeichert/listen/1`, `/de/restaurants`,
`/de/restaurants/18`. **In den neuen Teilen (Herz, „In Listen“, „Deine Notiz“, Gespeichert-Seiten) kein Verstoß.**

Vorbestehend, nicht durch Feature 17 verursacht (auf `/de/restaurants` ohne Feature-Bezug gleich):

- **Aktiver Punkt der unteren Leiste:** `text-cyan-600` (#0092b8) auf Weiß, 12 px — **3,61 : 1** (nötig 4,5 : 1).
  `partials/_bottom_nav.html.twig`, B25. Betrifft seit diesem Feature auch „Gespeichert“.
- **Detailseite:** kleiner Text `text-green-600 text-xs` in den Merkmalskacheln und das Verifizierungs-Abzeichen
  (#ad46ff auf #faf5ff, 3,83 : 1) — zusammen 10 Knoten. B06.

## Nicht im Browser geprüft

- Safari (AK-63 Back-Forward-Cache, Fokus nach Morph).
- VoiceOver-Ansage selbst — geprüft wurde die Voraussetzung (Ansagebereich `role="status"` existiert leer vor dem
  Füllen, Inhalt nach dem Stream), nicht die Ausgabe eines Screenreaders.
- Ohne JavaScript im Browser — die Wege sind durch Funktionstests ohne Turbo abgedeckt (303 mit Anker).
- EC-11 (offline in der installierten App): `public/sw.js` speichert Navigationen nicht (network-first, Rückfall
  `offline.html`) — aus dem Quelltext belegt, nicht im Offline-Modus ausprobiert.

## Nachtrag: Kopfzeile angemeldet (BF-80-Rückfall?)

Kontobereich mit dem neuen Verweis „Gespeichert“, `/xx/restaurants`, angemeldet: de bei 768, 900, 1024, 1280 px;
fr und pt bei 768 und 1024 px — **kein Überlauf** (Kopfinhalt `scrollWidth` = `clientWidth`, Dokument ohne
waagerechte Scrollleiste), Kopfzeile durchgehend 81 px.
