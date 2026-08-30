# 05 · Presse-Kit — Testbericht, zweiter Durchlauf

Stand: 2026-08-30 · Geprüft gegen `spec.md` vom 2026-08-30 (44 Kriterien)
Vorgänger: `qa-report.md` (31/44, ein kritischer Befund)

## Fazit

**Production-ready: nein** — aber aus deutlich weniger Gründen als beim ersten Durchlauf.

**Beide Reparaturen halten der Gegenprobe stand.** BF-97 ist an der Ursache behoben: Mit
vorhandenem Paket antworten alle vier Sprachfassungen mit 200 statt mit 500. BF-98
ebenfalls — beide Mutationsproben, die vorher grün blieben, färben den Lauf jetzt rot.

**Der eigentliche Ertrag dieses Durchlaufs ist ein anderer.** Aus dem Muster des ersten
Berichts — *ein Ast, der nie ausgeführt wird, ist keine Abdeckung* — folgt eine Methode:
Statt auf VB-01 zu warten, habe ich den **vollständigen Zustand hergestellt** (vier
SVG-Platzhalter abgelegt, `app:press:package` laufen lassen) und die gesamte
Materialmechanik zum ersten Mal durchgespielt. Ergebnis: **Sie trägt vollständig.** Der
Befehl schreibt sechs Dateien, die Bedingungsdatei enthält alle vier Sprachabschnitte samt
eingesetzter Presseadresse, die Seite zeigt fünf Vorschauen ohne eine einzige
fehlgeschlagene Anfrage, der Download liefert ein gültiges ZIP, und `PressPackageTest`
läuft erstmals durch statt zu überspringen — die Suite meldete in diesem Zustand **10
statt 13 übersprungene Tests**.

Damit sind **sechs vormals offene Kriterien belegt**, und das Feature wartet nur noch auf
drei Dinge, von denen keines Code ist: die echten Vektormarken, die Betreiberangaben und
den Fotocredit. Der Angriffsdurchlauf blieb erneut ohne Fund, axe-core meldet in allen vier
Sprachfassungen null Verstöße — auch im vollständigen Zustand, den vorher niemand gesehen
hatte.

⚠ **Alle Prüf-Artefakte sind entfernt.** `public/presse/` existiert nicht mehr, `git status
public/` zeigt keine Rückstände. Was ich mit Platzhaltern belegt habe, ist die **Mechanik** —
nicht das Material.

| | erster Durchlauf | jetzt |
|---|---|---|
| Akzeptanzkriterien geprüft | 44 von 44 | 44 von 44 |
| davon bestanden | 31 | **37** |
| davon durchgefallen | 6 | **3** |
| nicht prüfbar | 7 | **4** |
| Edge Cases belegt | 7 von 10 | **8 von 10** |
| Tests grün | 736 von 741 (5 absichtlich rot) | **741 von 741** |
| Tests neu geschrieben | 2 | 0 |

## Der Maßstab dieses Durchlaufs

Sechs Kriterien wurden mit **QA-Platzhaltern** statt mit den echten Marken belegt. Damit
das nicht zu einem Freundlichkeitsbonus wird, gilt hier eine ausgesprochene Regel:

> **Ein Kriterium über die Mechanik ist bestanden, wenn die Mechanik ausgeführt wurde und
> hielt. Ein Kriterium über den Inhalt des Materials bleibt offen, bis das Material
> existiert.**

Danach ist **AK-18** weiterhin nicht prüfbar — ob das Paket die *Wort-Bildmarke* enthält,
lässt sich mit einem grauen Kasten voller „QA-PLATZHALTER" nicht beantworten. AK-17, AK-19,
AK-20 und AK-22 sind dagegen inhaltsunabhängig: Sie fragen nach Übereinstimmung,
Dateiname, Linktext und Sprachabschnitten, und all das wurde ausgeführt.

## Geänderte Kriterien gegenüber dem ersten Durchlauf

| AK | vorher | jetzt | Nachweis |
|---|---|---|---|
| AK-02 | ❌ | ✅ bestanden | Mit angelegtem Paket `/lb` `/de` `/fr` `/en` je **200** (vorher je 500). `PressDownloadStateTest` grün (5 Fälle) |
| AK-16 | ❌ | ✅ bestanden | Im vollständigen Zustand **5 Kacheln, 0 gebrochen, 0 fehlgeschlagene Anfragen** — in allen vier Sprachen gemessen |
| AK-17 | ⚠️ | ✅ bestanden | `app:press:package` → 6 Dateien; `PressPackageTest::testDerPaketinhaltEntsprichtDerMaterialliste` lief erstmals **durch** statt zu überspringen. Gegenprobe: Ein Paket mit abweichendem Inhalt lässt ihn fehlschlagen („Fehlt: endlech-bildmarke-invers.svg, … michael.jpg") |
| AK-19 | ⚠️ | ✅ bestanden | `curl /presse/presse-kit-endlech-lu.zip` → **HTTP 200, 1 089 631 Bytes, `application/zip`**; `zipfile.is_zipfile` → True, 6 Einträge, `testzip()` ohne Defekt |
| AK-20 | ❌ | ✅ bestanden | Linktext „Presse-Paket herunterladen (ZIP · 1,0 MB)" — Format und Größe, in der Zahlenschreibweise der Sprache |
| AK-22 | ⚠️ | ✅ bestanden | `NUTZUNGSBEDINGUNGEN.txt` (3 820 Bytes) im Paket, Sprachabschnitte `LB —`, `DE —`, `FR —`, `EN —` alle vorhanden, Presseadresse eingesetzt |
| AK-18 | ⚠️ | ⚠️ nicht prüfbar | Die vier Dateien lagen nur als QA-Platzhalter vor. Dass sie **Vektordateien** sind und an den richtigen Namen stehen, ist belegt; dass sie die Marken **enthalten**, ist es nicht (VB-01) |

Alle übrigen 37 Kriterien behalten ihr Ergebnis aus `qa-report.md` — die Reparatur berührt
sie nicht, und die Belege von dort gelten unverändert.

## Weiterhin offen

| AK | Ergebnis | Grund |
|---|---|---|
| AK-11 | ❌ durchgefallen | Betreiberangaben fehlen (BF-93, VB-03) |
| AK-24 | ❌ durchgefallen | Fotocredit nennt keinen Urheber (BF-96, OF-05) |
| AK-41 | ❌ durchgefallen | Ohne Marken, Betreiber und Urheberangabe ist kein Beitrag ohne Rückfrage möglich |
| AK-15 | ⚠️ nicht prüfbar | Es gibt nichts zu vergleichen, solange die Angaben fehlen |
| AK-18 | ⚠️ nicht prüfbar | siehe oben |
| AK-26 | ⚠️ nicht prüfbar | keine Pressemitteilung (OF-06) |
| AK-29 | ⚠️ nicht prüfbar | kein Zugang zum Postfach (VB-02) |

## Edge Cases

Unverändert bis auf einen: **EC-08** (Vorschau ohne Paketeintrag) ist jetzt **belegt** —
im hergestellten Zustand ließ sich die Abweichung erzeugen, und `PressPackageTest` meldete
sie namentlich. Damit 8 von 10. Offen bleiben **EC-06** und **EC-10**, beide mangels
Pressemitteilung.

## Sicherheitsprüfung

Der vollständige Durchlauf steht in `qa-report.md` und gilt unverändert. Wiederholt wurde,
was der neue Zustand berührt:

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Pfad-Traversal am neuen Verzeichnis | bestanden | `/presse/../.env`, `/presse/../../.env`, `/presse/../config/services.yaml`, `/presse/presse-kit-endlech-lu.zip/../../.env` → je **404** |
| Paket wird statisch ausgeliefert | bestanden | `HTTP 200 · application/zip` direkt vom Webserver; kein Router-Eintrag (`PressPackageRoutingTest`, 2 Fälle) |
| Verzeichnisauflistung | bestanden | `/presse/` → 301 auf `/presse` → 302 auf `/lb/presse`; kein Index |
| Barrierefreiheit im vollständigen Zustand | bestanden | axe-core (WCAG 2.2 AA) in lb, de, fr, en → **0 Verstöße**; 320 px und 375 px ohne waagerechtes Scrollen |
| Druckansicht mit Downloadknopf | bestanden | Weißer Text auf Cyan — geprüft, ob er im Druck verschwindet: `assets/styles/app.css` setzt `print-color-adjust: exact` auf `*`, die Fläche druckt also mit. **Kein Befund** |

## Fehler

**Kein neuer Befund.** Die beiden Reparaturen haben nichts aufgerissen — geprüft wurde
gezielt der Zweig, den BF-97 erstmals erreichbar gemacht hat, in vier Sprachen, zwei
Fensterbreiten, Druckansicht und mit axe-core.

Die vier offenen Befunde aus dem ersten Durchlauf bleiben unverändert bestehen:
**BF-93** (hoch, Betreiberangaben), **BF-94** (hoch, Vektormarken), **BF-95** (mittel,
Vorschau degradiert nicht — wartet auf OF-09), **BF-96** (mittel, Fotocredit).

⚠ **Ein Rückstand des ersten Durchlaufs, hier korrigiert:** Der Befund-Nachweis
`PressDownloadStateTest` rief in seinem `tearDown()` kein `parent::tearDown()`. Die
Folgefehler sahen aus wie ein zweiter Anwendungsfehler; `sdd-build` hat es beim Beheben
bemerkt und ergänzt. **Der Fehler lag im Prüfwerkzeug dieses Skills, nicht in der
Anwendung** — er gehört deshalb hierher und nicht in `befunde.md`.

## Nächster Schritt

**Kein `/sdd-build`.** Am Code ist nichts mehr zu tun: Von den vier offenen Befunden
braucht keiner eine Codeänderung, und BF-95 wartet ausdrücklich auf eine Entscheidung
(OF-09), nicht auf eine Umsetzung.

Was fehlt, sind drei Handgriffe außerhalb des Quelltexts:

1. **Die vier Vektormarken** nach `public/presse/` legen, dann `make press-kit` (VB-01) —
   die Mechanik dahinter ist jetzt vollständig belegt
2. **Die drei Betreiberparameter** in `config/services.yaml` füllen (VB-03)
3. **Den Fotocredit** eintragen (OF-05) und **`support@endlech.lu`** einrichten (VB-02)

Danach ein dritter, kurzer QA-Durchlauf über die sieben betroffenen Kriterien — vier
Prüfläufe laufen dann automatisch mit statt zu überspringen. **AK-26, EC-06 und EC-10**
bleiben offen, bis es eine Pressemitteilung gibt (OF-06).
