# 05 · Presse-Kit — Testbericht, dritter Durchlauf

Stand: 2026-08-30 · Geprüft gegen `spec.md` vom 2026-08-30 (44 Kriterien)
Vorgänger: `qa-report.md` (31/44) · `qa-report-2.md` (37/44)
Geprüfter Stand: Commit `7b32e4f`

## Fazit

**Production-ready: ja** — mit einer benannten Einschränkung, die keine Softwarefrage ist.

**42 von 44 Kriterien bestanden, kein neuer Befund am Code.** Seit dem zweiten Durchlauf
sind die drei Vorbedingungen erfüllt: Die vier Markendateien liegen als echte Vektoren vor
(nachgezeichnet, 0,244 % Abweichung zum Original), die Betreiberangaben stehen, der
Fotocredit nennt den Urheber, das Postfach ist eingerichtet. Damit sind **fünf Kriterien
von offen auf bestanden gewechselt**, und **kein einziger Prüflauf dieses Features
überspringt noch** — die zehn Übersprungenen der Suite stammen sämtlich aus anderen
Features oder prüfen absichtlich einen Zustand, den es nicht mehr gibt.

**Die Einschränkung ist AK-11**, und sie ist eine Entscheidung, kein Mangel: Es wird
**keine Anschrift veröffentlicht** (OF-04, am 2026-08-30 entschieden). Das Kriterium
verlangt „Betreiber mit vollständiger Anschrift" und kann damit nicht bestehen. ⚠ Das
Impressum zitiert weiterhin „§ 5 TMG / Art. 11 Loi sur le commerce électronique" und
erfüllt beides nicht — das ist eine Betreiberentscheidung, die dieses Feature sichtbar
gemacht, aber nicht verursacht hat: Vorher stand dort gar nichts außer „Endlech.lu,
Luxemburg". **Empfehlung an die Spec:** AK-11 durchstreichen und als gestrichen markieren
oder auf „Betreiber und Verantwortlicher" zurücknehmen — beides braucht Michaels
Zustimmung und passiert nicht hier.

Der Angriffsdurchlauf blieb zum dritten Mal ohne Fund, diesmal einschließlich der neuen
Angriffsfläche: **vier SVG-Dateien, die von derselben Herkunft ausgeliefert werden.** Sie
enthalten kein `<script>`, kein `onload`, kein `foreignObject`, kein nachgeladenes Bild.

| | erster | zweiter | **dritter** |
|---|---|---|---|
| bestanden | 31 | 37 | **42** |
| durchgefallen | 6 | 3 | **1** |
| nicht prüfbar | 7 | 4 | **1** |
| Edge Cases belegt | 7/10 | 8/10 | **8/10** |
| Tests grün | 736/741 | 741/741 | **741/741** |
| übersprungene Feature-Tests | 4 | 4 | **0** |

## Geänderte Kriterien gegenüber dem zweiten Durchlauf

| AK | vorher | jetzt | Nachweis |
|---|---|---|---|
| AK-15 | ⚠️ | ✅ bestanden | `OperatorDetailsTest::testPresseUndImpressumNennenDieselbenBetreiberangaben` **läuft** statt zu überspringen. Beide Seiten zeigen „Michael Ferreira" als Betreiber und als Verantwortlichen, zeichengenau verglichen |
| AK-18 | ⚠️ | ✅ bestanden | Paket enthält vier **echte** SVG (je 2 Pfade, kein Platzhaltertext), das Porträt und `NUTZUNGSBEDINGUNGEN.txt` mit den Abschnitten `LB —`, `DE —`, `FR —`, `EN —`. Die Marken sind aus `public/images/logo.png` nachgezeichnet, Abweichung gemessen: **0,244 %** von 1,1 Mio. Bildpunkten |
| AK-24 | ❌ | ✅ bestanden | „Foto: Michael Ferreira (Selbstporträt), Endlech.lu" — in allen vier Sprachen, dazu die Nutzungsbedingung darunter |
| AK-29 | ⚠️ | ✅ bestanden | Betreiberauskunft vom 2026-08-30, dazu belegt: MX für `endlech.lu` (Hostinger + ImprovMX) und ein SPF, der beide nennt. ⚠ Eine Zustellprobe wurde **nicht** gefahren — sie hätte einen fremden Mailserver kontaktiert; „wird gelesen" kann ohnehin nur der Betreiber bezeugen |
| AK-41 | ❌ | ✅ bestanden | Handprüfung: Beschreibungstext, Livezahlen, fünf Bilddateien mit Nutzungsbedingungen, Betreiber und Verantwortlicher, Fotocredit und Pressekontakt sind vollständig da. Für einen Beitrag ist keine Rückfrage nötig |
| AK-11 | ❌ | ❌ durchgefallen | Keine Anschrift — **durch Entscheidung** (OF-04). Faktenblatt geprüft: Zeile „Anschrift" ist nicht vorhanden, Betreiber und Verantwortlicher stehen |

Die übrigen 38 Kriterien behalten ihr Ergebnis aus den ersten beiden Berichten. **AK-26**
bleibt als einziges nicht prüfbar: Es gibt keine Pressemitteilung (OF-06), also sind weder
Datum noch Reihenfolge noch Sprachformat beobachtbar.

## Regressionsprüfung nach der Namensänderung

Der vollständige Name wurde an sechs Stellen durchgezogen. Alle vier Sprachfassungen
gemessen — Person, Zitate, Fotocredit, Kurzvita, Kontakt, Download:

| Sprache | 320 px | Abschnitte | `<h1>` | Vorschauen | axe | 4xx |
|---|---|---|---|---|---|---|
| lb | 320/320 ok | 7 | 1 | 5 (0 gebrochen) | **0** | 0 |
| de | 320/320 ok | 7 | 1 | 5 (0 gebrochen) | **0** | 0 |
| fr | 320/320 ok | 7 | 1 | 5 (0 gebrochen) | **0** | 0 |
| en | 320/320 ok | 7 | 1 | 5 (0 gebrochen) | **0** | 0 |

Stichproben: „Michael Ferreira, Grënner vun Endlech.lu" · „…, Fondateur d'Endlech.lu" ·
„…, Founder of Endlech.lu"; Kurzvita beginnt in jeder Sprache mit dem vollen Namen;
Download „Presspak eroflueden (ZIP · 1,0 MB)" bzw. „Download press kit (ZIP · 1.0 MB)" —
Zahlenschreibweise folgt der Sprache.

**Kein zweiter Codequalitäts-Durchlauf.** Der Unterschied zum bereits geprüften Stand ist
ein Getter, eine Testkonstante, eine Bedingung in der Vorlage und Katalogtexte; die
Bedingung wurde stattdessen in vier Sprachen am Verhalten geprüft, was hier aussagekräftiger
ist als eine erneute Lektüre.

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| **SVG als Angriffsfläche** (neu) | bestanden | Alle vier Dateien: **0** Treffer auf `<script`, `onload=`, `onerror=`, `foreignObject`, `<image`, `javascript:`, externes `xlink:href`. Direktaufruf `/presse/endlech-bildmarke.svg` → 200, `image/svg+xml` |
| Zugriff auf fremde ID (IDOR) | bestanden | `/de/presse/4711`, `/xx/presse` → je 404 |
| Pfad-Traversal | bestanden | `/presse/../.env`, `/presse/../config/services.yaml` → je 404 |
| Schreibwege | bestanden | `POST`/`PUT`/`DELETE` auf `/de/presse` → je **405** |
| Eingaben | bestanden | XSS, Traversal und SQL über den Query-String → je 200, **0** Reflexionen im `<main>` |
| Rate Limit / Last | bestanden (kein Zähler, wie entworfen) | 30 Aufrufe → 30 × 200; der Zwischenspeicher trägt |
| PII in Logs | bestanden | Protokoll geleert, zwei Aufrufe: 16 Zeilen, **0** mit E-Mail-Muster |
| PII an externe Dienste | bestanden | 0 Dateien mit `HttpClient`, `curl_`, `fsockopen`; kein fremder Host beim Laden |
| Geheimnisse im Repository | bestanden | Keine Treffer auf `sk_live_`, `sk-…`, `xkeysib-`, private Schlüssel. ⚠ `app.operator_name: 'Michael Ferreira'` steht jetzt im öffentlichen Repository — das ist die bewusste Folge von VB-03, kein Fund |
| Löschen und Auskunft | bestanden (trifft nicht zu) | 0 Entities, 0 Migrationen, 0 Verweise auf `App\Press` in `src/Account/AccountDeleter.php` |

## Fehler

### BF-99 · Der Schriftzug der Wort-Bildmarken ist nicht in Pfade umgewandelt — mittel

**Betrifft:** kein Kriterium unmittelbar (AK-18 verlangt Vektordateien, und das sind sie)
**Reproduktion:** `grep -c '<text' public/presse/endlech-wortbildmarke.svg` → **2**;
`<path>` → 2. Die Bildmarke liegt als Pfad vor, der Schriftzug „Endlech.lu" dagegen als
Text mit einem Schriftstapel.
**Erwartet:** Ein Logo, das überall gleich aussieht.
**Tatsächlich:** Ohne die Schrift auf dem Zielsystem ersetzt der Betrachter sie — eine
Redaktion, die die Datei in ein Layoutprogramm zieht, bekommt möglicherweise eine andere
Schrift als die der Website. Bei einer Wortmarke ist die Schrift die Marke.
**Ort:** `public/presse/endlech-wortbildmarke.svg`, `…-invers.svg` (Hinweis steht als
Kommentar in beiden Dateien)
**Vorschlag:** Beide Dateien einmal durch Illustrator oder Affinity ziehen und den Text in
Pfade umwandeln. Kein Codeanteil.

## Weiterhin offen aus früheren Durchläufen

| # | Grad | Stand |
|---|---|---|
| **BF-95** | mittel | Eine fehlende Vorschaudatei erzeugt ein Bruchbild statt eines Ersatzes. Tritt seit VB-01 praktisch nicht mehr auf; wartet auf die Entscheidung zu **OF-09**, nicht auf eine Umsetzung |

**BF-93, BF-94 und BF-96 sind behoben** — Betreiberangaben, Markendateien und Fotocredit
liegen vor und sind oben belegt.

## Hinweise ohne Befundcharakter

- **Zwei Cyan-Töne** (OF-11): Die Kachel ist `#01b6ed`, die Nutzungsbedingungen nennen als
  Markenfarbe `#0891b2`, und der Schriftzug nimmt ebenfalls `#0891b2`. Beide Werte stammen
  belegt aus dem Projekt, passen aber nicht zueinander. Steht als offene Frage in der Spec;
  kein Kriterium verlangt Übereinstimmung.
- **Zwei Kontaktadressen:** `/legal` nennt `info@endlech.lu`, `/presse` nennt
  `support@endlech.lu`. Beides ist gewollt, aber wer über das Impressum schreibt, landet
  nicht beim Pressekontakt.
- **`facts.pending` ist toter Katalogtext**, seit Betreiber und Verantwortlicher gesetzt
  sind. Er wird noch gebraucht, falls ein Wert wieder geleert wird — kein Aufräumbedarf.

## Neue Tests

Keine. Die beiden in den vorherigen Durchläufen geschriebenen Läufe
(`PressFiguresConsistencyTest`, `PressDownloadStateTest`) decken die geänderten Stellen ab
und sind grün.

## Nächster Schritt

`/sdd-deploy 05`.

Vor dem Ausliefern zwei Handgriffe, die kein Code sind: **BF-99** (Schriftzug outlinen) und
die Antwort auf **OF-11** (welches Cyan gilt). Beide ändern nur Dateien in
`public/presse/` — danach `make press-kit` erneut laufen lassen, sonst zeigt das Paket die
alte Fassung. ⚠ Und in diesem Fall **`CACHE_VERSION` in `public/sw.js` erhöhen**: Der
Service Worker liefert Bilder cache-first aus, ein wiederkehrender Besucher sähe sonst die
alte Vorschau neben dem neuen Paket.

**AK-26 bleibt offen**, bis es eine Pressemitteilung gibt (OF-06) — das blockiert die
Auslieferung nicht.
