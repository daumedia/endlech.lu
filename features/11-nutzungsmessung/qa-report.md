# 11 · Nutzung messen, ohne zu verfolgen — Testbericht

Stand: 2026-09-13 · Geprüft gegen `spec.md` vom 2026-09-13 · Branch `feature/11-nutzungsmessung`
(nicht committet) · Prüfumgebung: `qa/11/umgebung.md`

# Nachprüfung 2 — 2026-09-14, nach BF-152

Vorstufe: `building` (Fehlerauftrag BF-152, Feature 11 dafür von `approved` zurückgenommen) · Branch
`feature/11-nutzungsmessung`, Reparatur nicht committet · Anwendung im Produktionsmodus mit echter Dateisperre,
Hilfs-Eingänge „gesund" (antwortet nach 30 ms) und „hängt" (antwortet nie)

## Fazit

**Production-ready: ja** — mit denselben zwei Bedingungen wie in der ersten Nachprüfung (unten).

BF-152 ist behoben und an der laufenden Anwendung bestätigt: Eine Sperrdatei, die sich nicht öffnen lässt, ergibt
**202 `{}`** statt 500, der Unterbrecher greift, und nach seinem Ablauf wird wieder gezählt. Die eigentliche Gefahr
der Reparatur lag woanders — die Sperre entsteht jetzt ohne automatische Freigabe, und ein vergessener Weg hätte den
Platz festgehalten und still jede weitere Zählung verhindert. Das tritt nicht ein: 20 Aufrufe nacheinander, danach
10, nach dem Sperrausfall 5 und nach dem Hänger 5 — alle angekommen. Der eine Platz aus BF-149 hält weiter: Gegen den
hängenden Eingang wartet genau ein Aufruf 2,09 s. Kein neuer Befund.

| | |
|---|---|
| BF-152 am laufenden Server | bestanden (`qa/11/bf152-qa.sh`, `qa/11/bf152-qa.ausgabe.txt`) |
| Prüfläufe | 1330 Tests grün, 13 übersprungen; `lint:container` unverändert der eine WebAuthn-Fehler |
| Code-Review | kein Fund ≥ 80 |
| Akzeptanzkriterien | unverändert gegenüber der ersten Nachprüfung: 28 bestanden, AK-15 teilweise, 10 nicht prüfbar |

## Nachprüfung der Behebung

| Fall | Ergebnis | Nachweis |
|---|---|---|
| Gesunder Eingang, 20 Aufrufe nacheinander | ✅ 20 × 200, 20 angekommen | A1 — der Platz wird nach jedem Aufruf freigegeben |
| 6 Aufrufe exakt gleichzeitig | ✅ 5 × 200, 1 × 202, 5 angekommen | A2 — der bekannte Preis des einen Platzes |
| Danach 10 nacheinander | ✅ 10 × 200, 10 angekommen | A3 |
| Sperrdatei `chmod 000` | ✅ 202 `{}` zweimal, nichts weitergeleitet, Unterbrecher gesetzt | B1 — vorher 500 mit HTML-Fehlerseite |
| Rechte zurück, Unterbrecher aktiv | ✅ 202, nichts weitergeleitet | B2 — 60 s ohne Zählung, wie im Abschlussbericht angenommen |
| Unterbrecher geleert, danach 1 + 5 | ✅ alle 200 und angekommen | B3, B4 — kein hängender Platz nach dem Ausfall |
| Hängender Eingang, 12 gleichzeitig | ✅ genau einer 2,09 s, übrige 0,19–0,77 s, alle 202; Sperrdatei nach 0,3 s bei 1 Prozess offen | C1 |
| Eingang wieder gesund, 5 nacheinander | ✅ alle 200 und angekommen | C2 — kein hängender Platz nach dem Hänger |
| Fehler beim Freigeben | ✅ nur als Prüflauf | `UmamiForwarderTest::testFehlerBeimFreigebenBrichtNichtsAb`; an einer echten Dateisperre ließ sich ein scheiterndes `flock`-Entsperren nicht herbeiführen |

**Nicht erneut gefahren:** Browserprüfung, Angriff und Trichter. Die Reparatur ändert ausschließlich Belegen und
Freigeben der Sperre; den Weiterleitungsweg selbst belegen A, B und C an der laufenden Anwendung, die übrigen
Kriterien tragen ihren Nachweis aus der ersten Nachprüfung.

## Hinweise ohne Befund

- **Das Code-Review stützt eine Aussage auf eine falsche Annahme.** Es hält einen dauerhaft gehaltenen Platz für
  ausgeschlossen, weil FrankenPHP „Request-pro-Prozess" fahre. Gemessen ist das Gegenteil
  (`qa/11/frankenphp-sperre.ausgabe.txt`: alle Anfragen `pid=1`, Threads eines Prozesses). Die Schlussfolgerung hält
  vermutlich trotzdem, weil PHP die Dateihandles einer Anfrage an deren Ende schließt — **nicht gemessen**, und nur
  relevant, wenn `release()` tatsächlich scheitert.
- **`gc_collect_cycles()` in `testFehlerBeimFreigebenBrichtNichtsAb` bewirkt nichts** (Review, unter der Schwelle,
  nachgelesen): Mit `autoRelease: false` bricht `Lock::__destruct()` sofort ab. Der Test prüft das `catch` in
  `platzFreigeben()` und wird ohne Reparatur rot; nur sein Kommentar „auch über den Destruktor hinweg" beschreibt die
  Gegenprobe, nicht den produktiven Weg.
- **Prüfwerkzeug:** Der erste Lauf blieb an einem nackten `wait` hängen, das auf den Eingang als Kindprozess wartete —
  dieselbe Ursache, die in der ersten Nachprüfung zwei Läufe von `qa/11/haenger.sh` stehen ließ. Im Skript vermerkt.
- **AK-15 bleibt teilweise, bis B15 ausgeliefert ist.** BF-151 ist auf `fix/bf-151-zielgruppen-formular` behoben
  und von der QA nachgeprüft, auf diesem Branch aber nicht enthalten.

## Nächster Schritt

**`/sdd-deploy B15`** zuerst (BF-151 ist auf der Produktion aktiv), danach **`/sdd-deploy 11`** — Feature 11 mit
den Bedingungen T01–T05 vor dem Scharfschalten; beim Zusammenführen den Konflikt in `features/befunde.md` und
`features/index.md` lösen.

---

# Nachprüfung — 2026-09-13, nach BF-149 und BF-150

Vorstufe: `building` (Fehlerauftrag BF-149/BF-150 aus dem ersten Durchlauf unten) · Umgebung neu aufgebaut
nach `qa/11/umgebung.md`: frische Umami-3.3.1-Instanz, Anwendung im Produktionsmodus, Zähl-Eingang, Chromium.

## Fazit

**Production-ready: ja** — für den Code dieses Features, mit zwei Bedingungen für die Auslieferung.

Beide Befunde des ersten Durchlaufs sind behoben und **unabhängig vom Bau nachgemessen**. Die Trichter aus
`growth/config.json` zählen gegen Umami auf Daten, die durch die Anwendung gelaufen sind (Suche 1 · 1 · 1 · 1,
App 1 · 1, Partner 1 · 1, Organisationen 3 · 1). Ein hängender Zähl-Eingang hält nur noch **einen** Aufruf fest,
belegt nach echtem Ablauf des Unterbrechers und — was der Bau offenließ — im echten FrankenPHP-Image: Dort laufen
alle Anfragen in **einem** Prozess, und die Sperre trennt sie trotzdem. Browserprüfung 34/34 und Angriffsprüfung
32/32 laufen nach der Reparatur unverändert durch, Umami speichert dieselben 32 Aufrufe.

Zwei neue Befunde. **BF-152 (mittel, Feature 11)**, gefunden vom Code-Review und am laufenden Server bestätigt:
Kann die Sperrdatei nicht geöffnet werden, antwortet der Zählweg mit **500** statt 202 — die neue Sperre kam
ohne die Fehlerbehandlung, die der Rest der Weiterleitung hat. **BF-151 (hoch, B15, live seit dem Bau der
Zielgruppenseiten)**: Auf `/organisationen/gemeinden`, `/unternehmen` und `/vereine` endet jede Eintragung in
einer **405-Fehlerseite**, nichts wird gespeichert. Das Formular hat kein `action`. Kein Fehler dieses Features —
aber er verhindert, dass AK-15 dort überhaupt etwas zu zählen hat, und die drei Seiten stehen seit Feature 10 in
der Sitemap.

**Bedingungen:** (1) `APP_UMAMI_*` in Coolify erst nach T01–T05 setzen; ohne die Variablen rendert die Anwendung
kein Skript und leitet nichts weiter (`UsageExtensionTest::testOhneKennungNieEinSkript`,
`CollectControllerTest::testOhneZaehlEingangNichtsWeitergeleitet`) — der Code kann also vorher ausgeliefert
werden, ohne dass etwas gemessen wird. (2) Die zehn Instanz-Kriterien werden beim Scharfschalten gegen den zweiten
VPS belegt, nicht angenommen. **Empfohlen vor allem anderen: BF-151 beheben** — er betrifft laufende Besucher.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 29 von 39 |
| davon bestanden | 28 (AK-14 jetzt bestanden) |
| davon teilweise | 1 (AK-15 — Zielgruppenseiten blockiert durch BF-151, B15) |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 10 (unverändert, zweiter VPS) |
| Edge Cases belegt | 7 von 7 |
| Tests neu geschrieben | 2 PHPUnit-Reproduktionen (4 Fälle, übersprungen, scharf gefahren rot) + 4 Prüfskripte |
| Tests grün | 1314 von 1314 ausgeführten, 14 übersprungen (`php bin/phpunit`: 1328 Tests, 7284 Zusicherungen) |

## Was sich gegenüber dem ersten Durchlauf geändert hat

| Kriterium | vorher | jetzt | Nachweis |
|---|---|---|---|
| AK-11 | ✅ (nur mit festem Präfix) | ✅ | `qa/11/trichter-nachpruefung.ausgabe.txt`: `*/restaurants` → `filter_angewandt` → `*/restaurants/*` → `kontaktweg_genutzt` = 1 · 1 · 1 · 1 auf Browserdaten |
| AK-14 | ❌ BF-150 | ✅ bestanden | dieselbe Kette zählt den Besuch über `/de/…` und `/fr/…` zusammen; Gegenprobe alte Schreibweise weiterhin 0 · 0 · 0 · 0; `/fr/restaurants` allein 1 · 0 · 0 · 0 |
| AK-15 | ❌ BF-150 | ⚠️ teilweise | `qa/11/wartelisten-browser.ausgabe.txt`: App (Browserprüfung), **Partner auf `/fr/partner`** und **Organisation über `/de/organisationen`** je 1 Ereignis mit `liste`, keine Adresse, kein Name, 0 JS-Fehler; Umami-Trichter Partner 1 · 1, Organisationen 3 · 1. **Zielgruppenseiten:** Eintragung unmöglich — 405, 0 gespeichert, 0 Ereignisse (BF-151, B15) |
| AK-35 | ⚠️ | ⚠️ nicht prüfbar | Schrittfolge jetzt wirksam; die Website-Kennung kommt erst mit T29 |
| AK-37 / EC-01 | ✅ mit BF-149 | ✅ | `qa/11/haenger.ausgabe.txt`: nach echtem Ablauf des Unterbrechers genau ein Aufruf 2,10 s, elf 0,20–0,55 s, danach wieder 200; Übergang gesund → hängend einzeln: ein Wartender 2,04 s / 2,09 s |
| EC-03 | ⚠️ BF-150 | ✅ | Suchtrichter läuft über den Sprachwechsel (AK-14) |
| alle übrigen | — | unverändert | `qa/11/browser-pruefung-nachpruefung.ausgabe.txt` (34/34), `qa/11/angriff-nachpruefung.ausgabe.txt` (32/32) |

## Nachprüfung der Behebungen

**BF-150 · behoben, bestätigt.** Nicht mit dem Skript des Baus, sondern auf den Daten der Browserprüfung
(durch Anwendung und Weiterleitung gelaufen) über Umamis Auswertungsschnittstelle. Zusätzlich die beiden bisher
datenlosen Trichter (Partner, Organisationen) mit echten Eintragungen im Browser gefüllt — im ersten Durchlauf
hatten sie gar keine Daten, und der Nachweis des Baus füllte sie mit Aufrufen direkt an Umami, an der Anwendung
vorbei.
`GrowthTrichterTest` ist gegen den Umami-Quelltext 3.3.1 (`getFunnel.ts`, Z. 116–118) nachgelesen; der Nachbau
entspricht ihm. `design.md` steht weiterhin falsch (OF-07, Betreiber).

**BF-149 · behoben, bestätigt — mit drei Einschränkungen im Nachweis.**
- **Die Annahme aus dem Bau hält:** `qa/11/frankenphp-sperre.ausgabe.txt` — `dunglas/frankenphp:1-php8.4`
  (FrankenPHP 1.12.7, PHP 8.4.25, 16 Kerne), Nachbau von `platzBelegen()` mit `FlockStore`: sechs gleichzeitige
  Anfragen, alle `pid=1`, **eine** bekommt den Platz (2,00 s), fünf geben nach 0,12 s auf; zweite Runde gleich.
- **Die Seiten warten nicht mehr auf Umami, aber sie stellen sich an:** Bei zwölf gleichzeitigen Zählaufrufen
  gegen den Hänger brauchte die Restaurantliste 0,25–0,29 s statt 0,06 s bis zum ersten Byte. Ursache ist die
  lokale Umgebung mit **drei** PHP-FPM-Workern: Jeder aufgebende Aufruf belegt einen für bis zu 100 ms. In
  FrankenPHP (Threads je Kern) ist das nicht gemessen; vorher waren es 1,8 s. Kein Befund, eine Beobachtung.
- **Phase 1 des Skripts ist zweimal nicht aussagekräftig:** Der Unterbrecher stand dort schon vor der Welle,
  obwohl das Serverprotokoll keinen Zählaufruf dazwischen zeigt; einmal antwortete auf dem frisch belegten Port
  noch der alte Prozess. Ursache nicht restlos geklärt, ein Fehler im Prüfaufbau. Der Übergang ist deshalb
  viermal einzeln belegt (siehe Ausgabedatei, Abschnitt „Bewertung").

## Sicherheitsprüfung (Nachprüfung)

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Alle Angriffe des ersten Durchlaufs | bestanden | `qa/11/angriff-nachpruefung.ausgabe.txt` — 32/32; die Suche nach Geheimnissen fand zuerst den **lokalen Prüfport im QA-Bericht selbst** (kein Geheimnis, aber es entwertet die Prüfung), Stelle umformuliert, erneut grün |
| Verlust der Sperre als Angriffsweg | kein Befund | Der Deckel `usage_collect` greift vor dem Controller; eine Adresse kann den Platz höchstens 300-mal je Stunde belegen, jeder Belegversuch kostet höchstens 100 ms. Messdaten lassen sich damit nur mit vielen Adressen unterdrücken — Seiten blockiert das nicht mehr |
| Defekter Sperrspeicher | **BF-152** | `qa/11/sperre-ausfall.ausgabe.txt`: Sperrdatei ohne Rechte → **500** mit HTML-Fehlerseite, zweimal; mit Rechten wieder 200 |

## Fehler

### BF-152 · Defekte Sperre macht aus dem Zählaufruf einen Serverfehler — mittel

**Betrifft:** AK-37 / EC-01 („Besucher sehen keine Fehlermeldung" — sie sehen sie nicht, der Aufruf ist ein
Hintergrund-`fetch`), die Zusicherung in `ForwardResult` („in allen Fällen `202 {}`") und Sentry.
**Gefunden:** `code-reviewer`, von der QA verifiziert.
**Reproduktion:**
1. `tests/Unit/Usage/Qa11SperrAusfallTest.php` ohne `markTestSkipped`: Speicher wirft `LockStorageException` →
   `LockAcquiringException: Failed to acquire the "umami-weiterleitung" lock.` statt 202.
2. Am laufenden Server (Produktionsmodus): Sperrdatei `sf.umami-weiterleitung.*.lock` im Temp-Verzeichnis des
   PHP-Prozesses auf `chmod 000`, Zählaufruf schicken → **500**, HTML-Fehlerseite; Rechte zurück → 200.
**Erwartet:** 202 `{}`, nichts weitergeleitet, nur die Ausnahmeklasse im Protokoll — wie bei jedem anderen
Ausfall in `UmamiForwarder`.
**Tatsächlich:** `Lock::acquire()` fängt nur den Konflikt („Platz belegt") ab und wirft jede andere Ausnahme
weiter (`vendor/symfony/lock/Lock.php`, letzter `catch`); weder `platzBelegen()` noch `forward()` noch
`CollectController` fangen sie. Jeder Zählaufruf wird zum 500er, und weil 500 nicht in `ignore_exceptions`
steht, meldet Sentry **jeden einzelnen** — bei vielen Besuchern ein Kontingent, das an einem Tag verbraucht ist.
**Wann das eintritt:** Temp-Verzeichnis voll oder schreibgeschützt, Sperrdatei mit fremdem Eigentümer. Selten;
nicht selten genug, um die Zusicherung zu brechen.
**Ort:** `src/Usage/UmamiForwarder.php` — `platzBelegen()` (`$platz->acquire()`) und das `release()` im `finally`
von `forward()`.
**Vorschlag:** Belegen und Freigeben in denselben `catch (\Throwable)` wie die Weiterleitung nehmen — Klasse
loggen, nicht weiterleiten. Der Prüflauf braucht einen Speicher, der etwas anderes als den Konflikt wirft.

**Behoben am 2026-09-13 (`sdd-build`, Branch `feature/11-nutzungsmessung`, nicht committet — Feature 11 dafür
von `approved` über `review` auf `building` zurückgenommen):** In `UmamiForwarder::forward()` steht das Belegen im
`try`; eine Ausnahme wird mit Klasse protokolliert, setzt den Unterbrecher und ergibt 202. Freigegeben wird in
`platzFreigeben()` mit eigenem `catch`, und die Sperre entsteht ohne automatische Freigabe — sonst wiederholte der
Destruktor ein gescheitertes `release()` außerhalb jedes `catch`. Nachweise:
- Reproduktion 1: `Qa11SperrAusfallTest` läuft ohne `markTestSkipped` grün.
- Reproduktion 2 am laufenden Server (`qa/11/sperre-ausfall-nachpruefung.ausgabe.txt`): Sperrdatei `chmod 000` →
  **202 `{}`** statt 500; zweiter Aufruf 202 über den Unterbrecher; Rechte zurück und Unterbrecher geleert → 200.
- Neu in `UmamiForwarderTest`: `testDefekteSperreWirdWieEinAusfallBehandelt` (202, kein Versand, nur die
  Ausnahmeklasse im Protokoll, kein zweiter Belegversuch während des Unterbrechers) und
  `testFehlerBeimFreigebenBrichtNichtsAb` (Antwort geht durch, Warnung protokolliert).
- Vier Gegenproben, jede rot an der erwarteten Stelle: ohne Abfangen beim Belegen (2 Fehler), mit automatischer
  Freigabe (1), Freigabe nicht abgefangen (1), ohne Unterbrecher bei defekter Sperre (1).
- Volle Suite 1330 Tests grün, 13 übersprungen.

### BF-151 · Eintragen von den Zielgruppenseiten endet in einer 405-Fehlerseite — hoch (B15)

**Betrifft:** B15 AK-03 („Zielgruppenseite mit vorgewähltem Typ") und AK-09 (gültige Anmeldung → Mail); in
Feature 11 den Teil „samt ihrer Zielgruppenseiten" von AK-15. Nachtrag im Bericht von B15.
**Reproduktion:**
1. `/de/organisationen/gemeinden` (ebenso `unternehmen`, `vereine`) im Browser öffnen, Pflichtfelder ausfüllen,
   absenden (`qa/11/wartelisten-browser.mjs`).
2. `tests/Functional/Controller/Qa11ZielgruppenFormularTest.php` ohne `markTestSkipped`: drei Fälle rot,
   „Failed asserting that 405 is not identical to 405".
**Erwartet:** Weiterleitung wie auf `/de/organisationen`, Eintrag gespeichert, Bestätigungsmail.
**Tatsächlich:** `POST /de/organisationen/gemeinden` → **405**, sichtbar „Oops! An Error Occurred — The server
returned a "405 Method Not Allowed"", **0** Einträge, eingegebene Daten verloren. Das Formular trägt kein
`action`; der Browser schickt es an die Zielgruppenseite, die nur GET kennt. Die POST-Route
`app_organisations_submit` liegt unter `/organisationen`.
**Auf der Produktion:** nur lesend geprüft — `GET https://endlech.lu/de/organisationen/gemeinden` rendert dasselbe
Formular ohne `action`, und `master` trägt dieselben Routen (`#[Route('/{slug}', …, methods: ['GET'])]`). Kein
POST an die Produktion geschickt. Sentry sieht es nicht: 405 steht in `ignore_exceptions`.
**Warum es niemand sah:** Jeder Absende-Test in `OrganisationControllerTest` holt das Formular von der Übersicht,
wo die aktuelle Adresse zufällig die POST-Route ist. Die Zielgruppenseiten werden nur per GET geprüft.
**Ort:** `templates/organisation/_form.html.twig:33` (`form_start` ohne `action`), `OrganisationController::type()`.
**Vorschlag:** Das Formular ausdrücklich an `app_organisations_submit` schicken; der Prüflauf holt es von jeder
Zielgruppenseite.

## Hinweise ohne Befund

- **Code-Review dieser Reparatur:** ein Fund ≥ 80 (→ BF-152). Unterhalb der Schwelle und nachgesehen: Die TTL der
  Sperre (`MAX_DURATION + 1`) wirkt bei `FlockStore` nicht (`putOffExpiration()` ist dort leer) — sie ist
  Dokumentation und würde erst bei einem anderen Speicher greifen. Über mehrere Container hinweg trennt `flock`
  nicht; derzeit gibt es einen. Der Zeitprüflauf `testBelegterPlatzSendetNichtUndWartetNurKurz` misst Wanduhrzeit,
  die Marge (100 ms gegen 1 s) ist großzügig.
- **`design.md` widerspricht `growth/config.json`**, bis OF-07 entschieden ist. T29 trägt einen Vermerk.
- **Prüfwerkzeug:** Zwölf Hintergrundprozesse, die an **eine** Datei anhängen, verschmelzen ihre Zeilen, und ein
  laufendes Bash-Skript darf nicht bearbeitet werden. Beides hat hier einen Messlauf entwertet; `qa/11/haenger.sh`
  schreibt seither je Aufruf eine Datei.

## Neue Tests und Prüfskripte (Nachprüfung)

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Unit/Usage/Qa11SperrAusfallTest.php` | 1 (übersprungen; scharf rot: `LockAcquiringException`) | BF-152 |
| `tests/Functional/Controller/Qa11ZielgruppenFormularTest.php` | 3 (übersprungen; scharf rot: 405) | BF-151 |
| `qa/11/wartelisten-browser.mjs` | 11 (8 bestanden, 3 × BF-151) | AK-15, AK-17 für Partner und Organisationen, Trichter |
| `qa/11/haenger.sh` | 3 Phasen × 12 gleichzeitige Aufrufe + Seiten | BF-149 nach echtem Ablauf des Unterbrechers |
| `qa/11/frankenphp-sperre.php` | 2 Runden × 6 Anfragen | Sperre zwischen FrankenPHP-Threads |
| — (Schnittstelle, Browserdaten) | 4 Trichter + 2 Gegenproben | AK-11, AK-14, AK-15 — `qa/11/trichter-nachpruefung.ausgabe.txt` |

## Nächster Schritt

1. **`/sdd-build B15 BF-151 beheben`** — zuerst, weil er laufende Besucher trifft (B15 steht dafür auf `review`).
2. **`/sdd-build 11 BF-152 beheben`** — blockiert nicht, liegt aber direkt neben einer Fehlerbehandlung, die es
   schon gibt. Feature 11 steht auf `approved`; für den Fehlerauftrag muss der Status vorher auf `review` zurück
   (wie bei BF-147).
3. Betreiber: T01–T05, OF-05, OF-06, OF-07; danach `/sdd-deploy 11` mit Nachprüfung der zehn Instanz-Kriterien.

---

# Erster Durchlauf — 2026-09-13

## Fazit

**Production-ready: nein**

Der Zählweg selbst ist sauber gebaut und hält jedem Angriff stand, der gefahren wurde: 34 von 34
Browserprüfungen und 32 von 32 Angriffen bestanden, keine Personendaten am Zähl-Eingang, keine im
Protokoll, kein Cookie, keine fremde Ressource, Schlüsselbindung wirksam, Deckel greift beim 301. Aufruf.
Durchgefallen ist die **Auswertung**: Die Trichter in `growth/config.json` und in `design.md` setzen den
Platzhalter `*` mitten in den Pfad (`/*/restaurants`), und Umami 3.3.1 versteht ihn dort nicht. Gegen eine
echte Umami-Instanz gemessen zählt jeder der vier Trichter **null** Besucher, obwohl die Daten vollständig
ankommen (**BF-150, hoch** — AK-14 und AK-15 nicht erfüllt). Daneben hält ein hängender Zähl-Eingang
gleichzeitige Anfragen bis zu 3,7 Sekunden fest, und eine Seite wartete im selben Fenster 2 Sekunden
(**BF-149, mittel**). Zehn Kriterien hängen an der echten Instanz auf dem zweiten VPS (T01–T05) und sind
lokal nicht prüfbar. Nächster Schritt: `/sdd-build 11 BF-149 und BF-150 beheben`, danach `/sdd-qa 11`.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 29 von 39 |
| davon bestanden | 27 |
| davon durchgefallen | 2 (AK-14, AK-15 — BF-150) |
| **nicht prüfbar** | 10 |
| Edge Cases belegt | 7 von 7 (EC-03 mit Befund) |
| Tests neu geschrieben | 1 PHPUnit-Reproduktion (übersprungen) + 5 Prüfskripte (darunter 34 Browser- und 32 Angriffsprüfungen) |
| Tests grün | 1307 von 1307 ausgeführten, 11 übersprungen (`php bin/phpunit`: 1318 Tests, 7171 Zusicherungen); `npm run typecheck` und `npm run lint` grün |

## Akzeptanzkriterien im Einzelnen

Nachweise: **B** = `qa/11/browser-pruefung.ausgabe.txt`, **A** = `qa/11/angriff.ausgabe.txt`,
**L** = `qa/11/ladezeit.ausgabe.txt`, **T** = `qa/11/trichter.ausgabe.txt`, **O** = `qa/11/offline.ausgabe.txt`,
**R** = `qa/11/legal.ausgabe.txt`. „Umami" heißt: in der Datenbank der lokalen Umami-3.3.1-Instanz
nachgesehen, nicht im Netzwerk-Tab vermutet.

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | B: Seitenaufruf `/de/restaurants` aus dem Browser in Umami gespeichert (Datenbankabfrage unmittelbar nach dem Durchlauf) |
| AK-02 | ✅ bestanden | B: Herkunft Google → `google.com` ohne Pfad; A: Herkunft `https://mail.example.lu/inbox/12345?mid=abc` → Umami speichert `mail.example.lu` \| `/` \| leer |
| AK-03 | ✅ bestanden | B: Zählaufruf ohne Abfrage, „Esch" nicht im Browser-Payload; Umami: 32 Aufrufe, 0 mit Abfrage, „Esch" nirgends |
| AK-04 | ⚠️ nicht prüfbar | Land entsteht nur aus einer öffentlichen Besucheradresse und der Geo-Datenbank der echten Instanz (T02). Lokal belegt ist die Gegenrichtung: unterschobenes `cf-ipcountry: US` und vier gefälschte IP-Kopfzeilen erreichen Umami nicht (A: Kopfzeilen am Eingang, Besucher-IP `127.0.0.1`, Land gespeichert: ∅) |
| AK-05 | ✅ bestanden | B: Umami je Sprache `/de` 4, `/fr` 2, zusammen 6; T: Trichter mit festem Präfix `/de/restaurants` zählt je Schritt 1 |
| AK-06 | ✅ bestanden | B: Turbo-Besuche in Verwaltung/Profil → 0 Zählaufrufe, Tracker zählt danach `/de/about` weiter; Umami: 0 Pfade mit `/admin`, `/profile`, Token. A: selbst gebauter Aufruf mit Verwaltungs-/Profilpfad → 400, am Eingang 0 |
| AK-07 | ✅ bestanden | B: Token-Seite direkt geöffnet → kein Skript, kein Zählaufruf; per Turbo → kein Zählaufruf. A: Token-Link als Pfad → 400, am Eingang 0 |
| AK-08 | ✅ bestanden | B: Anmeldeseite gezählt; `TrackerTagTest::testAnmeldungUndRegistrierungTragenDasSkript` |
| AK-09 | ✅ bestanden | B: Aufruf über `www.endlech.lu` → 0 Zählaufrufe; A: Hostname `www.endlech.lu` und fremde Website-Kennung → 400, am Eingang 0. Lokal/Test: `UsageExtensionTest::testOhneKennungNieEinSkript` (`.env` ohne Kennung), `CollectControllerTest::testOhneZaehlEingangNichtsWeitergeleitet` |
| AK-10 | ✅ bestanden | B: Googlebot → 1 Zählaufruf gesendet, in Umami 0 gespeichert |
| AK-11 | ✅ bestanden | B: Filter → Ereignis, Detailseite per Turbo, 8 Kontaktwege; T: Trichter `/de/restaurants` → `filter_angewandt` → `/de/restaurants/*` → `kontaktweg_genutzt` zählt 1 · 1 · 1 · 1. ⚠ Mit der ausgelieferten Schrittfolge 0 · 0 · 0 · 0, siehe AK-14 |
| AK-12 | ✅ bestanden | B: 8 Kontaktwege → 8 Ereignisse, nur `art`/`plattform`; Umami-Ereignisdaten ohne Nummer oder Adresse. A: Telefonnummer als Art → 400 |
| AK-13 | ✅ bestanden | B: Ereignis `filter=wheelchair,toilet,ort` (Ort nur als Markierung); A: Ortstext im Filter → 400 |
| AK-14 | ❌ durchgefallen | siehe **BF-150** — T: ausgelieferte Kette `/*/restaurants` … zählt 0 · 0 · 0 · 0; dieselben Daten mit `*/restaurants` … 1 · 1 · 1 · 1 |
| AK-15 | ❌ durchgefallen | siehe **BF-150** — T: `/*/app` → 0 · 0, `*/app` → 1 · 1. Das Ereignis selbst stimmt (B: `liste=app` nach erfolgreicher Eintragung; Partner und Organisationen: `WartelistenHooksTest`, im Browser nicht durchgespielt) |
| AK-16 | ✅ bestanden | B: fehlerhafte Absendung → 0 × `warteliste_eingetragen`; `WartelistenHooksTest::testFehlerhafteAbsendungMeldetNichts` |
| AK-17 | ✅ bestanden | B: keine Adresse in den Zählaufrufen; Umami-Ereignisdaten nur `liste=app`; A: E-Mail in Ereignisdaten → 400 |
| AK-18 | ✅ bestanden | B: Zustimmen → 1 Ereignis ohne Daten, Zurückziehen → weiterhin 1 |
| AK-19 | ✅ bestanden | B: Presse-Kit 1 Ereignis, Datensatz `format=csv` und `format=json` |
| AK-20 | ✅ bestanden | B: Cookies vor/nach allen Wegen und dem Umschalten nur `PHPSESSID` (bestand vorher); Startseite im Produktionsmodus zeigt weiterhin „Wir nutzen nur technisch notwendige Cookies, damit die Seite funktioniert", der `cookie`-Block der Kataloge ist unverändert (`git diff`) |
| AK-21 | ✅ bestanden | B: Zählaufruf als angemeldeter Admin nur `website,screen,language,title,hostname,url,referrer`; Umami: 1 Sitzung, ohne `distinct_id`. A: unterschobene `id`/`tag` fallen weg, Typ `identify` → 400 |
| AK-22 | ✅ bestanden | B: GPC und DNT je → Seitenaufrufe, Filter, Turbo-Besuch, Kontaktweg: 0 Zählaufrufe; A: selbst gebaut mit `Sec-GPC: 1`/`DNT: 1` → 204, am Eingang 0 |
| AK-23 | ✅ bestanden | B: an → aus, nach Neuladen aus, neuer Tab 0 Zählaufrufe, wieder an → neuer Tab 1; kein neues Cookie; bei gesperrtem Speicher Hinweis statt Knopf |
| AK-24 | ✅ bestanden | B: angefragte Hosts nur `endlech.lu`, 0 CSP-Meldungen; A: keine Zieladresse, kein Rechnername, keine Schlüsselbindung im Repository; fremdes Zertifikat → beim Eingang 0 Anfragen |
| AK-25 | ✅ bestanden | A: `GET /api/send` 405, `/api/send/login`, `POST /api/auth/login`, `/api/websites`, `/api/heartbeat`, `/script.js`, `/login` je 404 |
| AK-26 | ⚠️ nicht prüfbar | Firewall des zweiten VPS (T01) — nur gegen die echte Maschine |
| AK-27 | ✅ bestanden | A: erster 429 bei Aufruf 301, `/de/restaurants` danach 200, keine Set-Cookie auf der 429; Funktionen unter erreichtem Deckel: L `deckel-erreicht` |
| AK-28 | ⚠️ nicht prüfbar | Kennwort der echten Instanz (T03) |
| AK-29 | ⚠️ nicht prüfbar | Benutzerrechte in der echten Instanz (T03/T04) |
| AK-30 | ✅ bestanden | A: Anwendungsprotokoll nach allen Angriffen — Zieladresse, unterschobene IPs, Herkunft, Kennung: 0 Treffer |
| AK-31 | ⚠️ nicht prüfbar | Text vollständig: R — alle 13 verlangten Angaben in allen vier Sprachen, keine „anonym"/„keine Kennung"; die Angaben zu Abfrage, Domain, Cookie, IP und Sitzung decken sich mit B und A. **Nicht prüfbar** sind „Serverstandort Deutschland" (T01) und „Land" (T02) — beides sagt nur die echte Instanz. Rechtsgrundlage ist OF-06, keine QA-Frage |
| AK-32 | ⚠️ nicht prüfbar | `/roadmap` in allen vier Sprachen ohne den Eintrag (0 Treffer; Gegenprobe: `HEAD` enthält „Nutzung messen, ohne zu verfolgen"). Der `/changelog`-Eintrag entsteht erst beim Release (`changelog-text.md` liegt als Entwurf vor) |
| AK-33 | ✅ bestanden | `docs/prd.md:379–386` beschreibt die cookielose Messung mit ihrer Bedingung, „nicht als öffentliche Kennzahl"; Zeile 590/596 „Besucherverfolgung" mit der Ausnahme statt „kein Web-Analytics" |
| AK-34 | ⚠️ nicht prüfbar | Abnahme nach Auslieferung. Lokal vorweggenommen: B AK-01 und AK-23 |
| AK-35 | ⚠️ nicht prüfbar | Website-Kennung existiert erst mit T03/T05 (`website_id: null`). ⚠ Die eingetragene Schrittfolge ist wirkungslos — BF-150 |
| AK-36 | ✅ bestanden | B: Detailseite per Turbo als `/de/restaurants/1` gezählt; A/B: Sitzung ohne Konto- oder Namensbezug |
| AK-37 | ✅ bestanden | L, erster Inhalt im Mittel aus fünf Aufrufen (Start · Liste · Detail, ms): normal 194 · 198 · 197 — Umami angehalten 193 · 193 · 188 — Eingang aus 195 · 196 · 194 — Skript blockiert 211 · 202 · 198 — Deckel erreicht, im Wechsel mit Kontrolle gemessen: 281 · 280 · 278 gegen 313 · 342 · 352 und 225 · 212 · 214 gegen 210 · 249 · 256 (höchstens +15). Filter, Warteliste, Kontaktweg in jeder Bedingung funktionsfähig, 0 JS-Fehler, keine Fehlermeldung. Zusätzlich hängender Eingang (nicht verlangt): +67/+73/+68 auf der Startseite, einzelne Aufrufe bis 576 ms — siehe BF-149 |
| AK-38 | ⚠️ nicht prüfbar | SSH-Schlüssel des Growth-Loops auf dem zweiten VPS (T04) |
| AK-39 | ⚠️ nicht prüfbar | Tunnel gegen den zweiten VPS (T04/T05). Die Abmeldung ohne Zugang ist in `mcp-umami/server.js --check` (Exit 3) gebaut, gegen eine Maschine aber nicht gefahren |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ belegt | L: Umami angehalten, Eingang aus, Eingang hängt, Skript blockiert, Deckel erreicht — Filter, Warteliste, Kontaktweg funktionieren, 0 JS-Fehler, keine Meldung. ⚠ „Kein Seitenaufbau wartet": bei gleichzeitigen Zählaufrufen gegen einen **hängenden** Eingang nicht erfüllt, siehe BF-149 |
| EC-02 | ✅ belegt | B: ohne JavaScript 0 Zählaufrufe; `OptOutSwitchTest::testSchalterIstOhneJavaScriptVerborgenUndBarrierefrei` (Hinweis „ohne JavaScript keine Messung") |
| EC-03 | ⚠️ teilweise | Zwei Seitenaufrufe je Sprache ✅ (B: `/de/restaurants/1` und `/fr/restaurants/1`); der sprachübergreifende Trichter zählt mit der ausgelieferten Schrittfolge 0 — BF-150 |
| EC-04 | ✅ belegt | `curl` gegen `/de/restaurants/999999` und `/fr/restaurants/999999`: 404, kein Zählskript im HTML, also auch kein Zählaufruf; die Seite antwortet regulär statt mit 500 |
| EC-05 | ✅ belegt | A: Deckel zählt je Adresse (301. Aufruf 429), Seite danach 200 |
| EC-06 | ✅ belegt | O: Service Worker aktiv; offline zur nächsten Seite → `offline.html` („Offline – Endlech.lu") ohne Zählskript, 0 Zählaufrufe, 0 Fehler; wieder online → 1 Zählaufruf, beantwortet. Auf einer **offen gebliebenen** Seite versucht ein Kontaktklick einen Zählaufruf, der im Browser scheitert — ohne Fehler, ohne Wiederholung, am Server kommt nichts an. Der Vollbildmodus der installierten App ist headless nicht nachstellbar; gezählt wird dort über denselben Service Worker |
| EC-07 | ✅ belegt | B AK-23: Schalter wieder an → wird gezählt (das Leeren des Speichers entfernt denselben Eintrag `umami.disabled`); R: der Satz „Wer den Browserspeicher leert, hebt den Widerspruch auf" steht in allen vier Sprachen |

## Sicherheitsprüfung

Aktiv angegriffen, nicht nur gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`. Alle Angriffe in
`qa/11/angriff.sh`, beobachtet an drei Stellen: Antwort der Anwendung, Mitschrift des Zähl-Eingangs,
Umamis Datenbank.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | trifft nicht zu | Der Zählweg liest und liefert keine Datensätze. Umamis Anmeldung und Schnittstelle über endlech.lu: 404 (AK-25) |
| Rate Limit greift | bestanden | 301. Aufruf aus einer Adresse → 429, Seite funktioniert weiter (AK-27) |
| PII in Logs | bestanden | Anwendungsprotokoll: 0 Treffer für Zieladresse, unterschobene IPs, Herkunft, Website-Kennung (AK-30). Warnungen der Weiterleitung: 0 in stderr (`fingers_crossed` ab `error`) — erst Sentry sähe sie, und dort steht nur die Ausnahmeklasse (`UmamiForwarderTest::testProtokollVerraetWederZielNochBesucher`) |
| PII an externe Dienste | bestanden | **Tatsächlich angekommen** am Eingang: Kopfzeilen `accept, accept-encoding, content-length, content-type, host, user-agent, x-endlech-client-ip`; Rumpf `website, hostname, url, referrer` — unterschobene `X-Forwarded-For`, `X-Real-IP`, `cf-connecting-ip`, `cf-ipcountry`, `Cookie`, `id`, `tag` fielen weg; Besucher-IP ist die vom Server ermittelte |
| Zugriffsregeln serverseitig | bestanden | Wer den Tracker umgeht: Verwaltungs-, Profil-, Token-Pfade, fremder Host, fremde Kennung, E-Mail in Daten, Ortstext, Telefonnummer, `identify` → 400, am Eingang jeweils 0 |
| Eingaben | bestanden | kaputtes JSON, Schachtelung > 16 → 400; Rumpf > 8 KB → 413; Markup in Titel/Pfad wird als Text weitergereicht und nirgends in der Anwendung ausgegeben |
| Transportsicherung | bestanden | Eingang mit fremdem Zertifikat: TLS-Handschlag abgebrochen, 0 Anfragen angekommen, Besucher bekommt 202; mit richtigem Zertifikat 200 |
| Geheimnisse im Repository | bestanden | Keine Zieladresse, keine Schlüsselbindung, kein Rechnername des zweiten VPS (A, letzter Block); `.env` trägt alle drei Werte leer |

## Fehler

### BF-150 · Kein Trichter zählt: `*` mitten im Pfad versteht Umami nicht — hoch

**Betrifft:** AK-14, AK-15 (dazu AK-35 und EC-03)

**Reproduktion:**
1. Umgebung nach `qa/11/umgebung.md`, `node qa/11/browser-pruefung.mjs` einmal durchlaufen lassen
   (Liste, Filter, Detailseite, Kontaktweg, App-Warteliste).
2. Umamis Trichter-Bericht (`POST /api/reports/funnel`) mit der Schrittfolge aus `growth/config.json`
   abfragen: `/*/restaurants` → `filter_angewandt` → `/*/restaurants/*` → `kontaktweg_genutzt`.
3. Dieselbe Abfrage mit `*/restaurants` → `filter_angewandt` → `*/restaurants/*` → `kontaktweg_genutzt`.
4. Ohne laufende Instanz: `Qa11TrichterMusterTest::testBf150KeinPlatzhalterMittenImPfad` ohne das
   `markTestSkipped` — rot mit `conversion_events: „/*/restaurants"`.

**Erwartet:** Der Suchtrichter zählt die Durchläufe aller Sprachen zusammen (AK-14), die
Wartelisten-Trichter zählen „Seite geöffnet" und „erfolgreich abgesendet" (AK-15).

**Tatsächlich:** Schritt 2 liefert 0 · 0 · 0 · 0, Schritt 3 auf denselben Daten 1 · 1 · 1 · 1. Ebenso
`/*/app` → 0 · 0 gegen `*/app` → 1 · 1 (`qa/11/trichter.ausgabe.txt`). Umami 3.3.1 ersetzt den Stern nur,
wenn der Schritt damit **beginnt oder endet** (`src/queries/sql/reports/getFunnel.ts`:
`startsWith('*') || endsWith('*')`, dann `replace(/^\*|\*$/g, '%')`); ein mittlerer Stern bleibt ein
wörtliches Zeichen im `LIKE`. `/*/restaurants` wird so zu `/*/restaurants` (kein Treffer),
`/*/restaurants/*` zu `/*/restaurants/%`. Der Growth-Loop hätte jede Woche einen Trichter mit null
Besuchern gemeldet — eine Aussage, die wie „niemand sucht" aussieht und in Wahrheit „falsch abgefragt"
heißt.

**Ort:**
- `growth/config.json:22`, `:24`, `:39`, `:43`, `:47` — alle fünf Pfadschritte
- `features/11-nutzungsmessung/design.md:107–116` — Trichtertabelle und die Begründung „Umami übersetzt
  `*` in jedem Schritt … auch mitten im Pfad (`getFunnel.test.ts`: `'*/thanks'` → `'%/thanks'`)". Das
  zitierte Testbeispiel beginnt selbst mit dem Stern und belegt das Gegenteil nicht.
- `features/11-nutzungsmessung/tasks.md:86` (T28, abgehakt mit derselben Schrittfolge)
- außerhalb des Repositorys: `~/.claude/skills/growth-loop/mcp-umami/README.md:64–66` („Platzhalter an
  beliebiger Stelle, etwa `/*/restaurants/*`"), in T28 geschrieben. Der Server reicht den Wert
  unverändert an Umami durch (`server.js:331`), korrigiert also nichts.

**Vorschlag:** Schritte mit führendem Stern (`*/restaurants`, `*/restaurants/*`, `*/app`, `*/partner`,
`*/organisationen*`) und die falsche Aussage im Skill-README berichtigen; `design.md` darf `sdd-build`
nicht ändern — die Tabelle geht als offene Frage an den Betreiber (Muster BF-147/OF-07).

**Behoben am 2026-09-13 (`sdd-build`, nicht committet):** `growth/config.json` trägt die fünf Pfadschritte
mit führendem Stern. Außerhalb des Repositorys berichtigt: `mcp-umami/README.md` (Regel samt Tabelle
„Schritt → wird zu → trifft") und `references/einrichtung.md`. ⚠ Die ursprüngliche README-Fassung sagte
richtig „am Anfang oder Ende" — T28 hatte das auf „an beliebiger Stelle" geändert (Sicherung im Scratchpad).
`design.md` bleibt unverändert, dafür **OF-07** in `spec.md`; `tasks.md` T09 trägt einen Berichtigungsvermerk.
Nachweise:
- Reproduktion 4 aus diesem Bericht: `Qa11TrichterMusterTest` läuft ohne `markTestSkipped` grün.
- Neu `tests/Integration/Usage/GrowthTrichterTest.php`: bildet Umamis Abgleich aus `getFunnel.ts` nach
  und hält jeden Schritt gegen die Routen aller vier Sprachen (Liste trifft keine Detailseite, kein
  Wartelisten-Trichter die Seiten eines anderen). Gegenproben rot: alte Schreibweise (3 Fehlschläge),
  fester Präfix `/de/restaurants` (1), vertauschter Partner-Schritt (1).
- Reproduktion 1–3 gegen eine frische Umami-3.3.1-Instanz, zwei Besucher auf Deutsch und Französisch:
  Suche 2 · 2 · 2 · 2, App 1 · 1, Partner 1 · 1, Organisationen 2 · 2 — alte Schreibweise auf denselben
  Daten weiterhin überall 0 (`qa/11/bf150-nachpruefung.py`, `.ausgabe.txt`).

### BF-149 · Hängender Zähl-Eingang hält gleichzeitige Anfragen bis 3,7 s fest — mittel

**Betrifft:** EC-01 („kein Seitenaufbau wartet auf die Messung"), AK-37 im Randfall

**Reproduktion:**
1. Umgebung nach `qa/11/umgebung.md`, statt `eingang.mjs` den Hänger `haengt.mjs` auf dem Port des Eingangs starten
   (nimmt Verbindungen an, antwortet nie — ein VPS, der Pakete schluckt), Unterbrecher leeren
   (`cache:pool:clear cache.usage --env=prod`).
2. Sechs Zählaufrufe gleichzeitig an `/api/send` schicken und im selben Augenblick `/de/restaurants`
   abrufen; Zeit bis zum ersten Byte messen.
3. 60 Sekunden warten (Unterbrecher läuft ab) und wiederholen.

**Erwartet:** Höchstens ein Aufruf wartet auf das Zeitlimit; die Seite antwortet so schnell wie ohne Messung.

**Tatsächlich:** Ein einzelner Aufruf wartet 2,12 s, danach 0,08 s (Unterbrecher greift). Sechs
gleichzeitige warten **jeder** 2,19–3,67 s; `/de/restaurants` im selben Fenster **2,04 s** bis zum ersten
Byte, ohne Hänger 0,10 s. Nach Ablauf des Unterbrechers wiederholt sich das alle 60 Sekunden. Im
Browsermittel bleibt es unter der Grenze von AK-37 (+67 bis +73 ms), einzelne Startseitenaufrufe lagen
bei 540–576 ms statt ~200 ms (`qa/11/ladezeit.ausgabe.txt`, Nachmessung und paarweise Kontrolle).

**Ort:** `src/Usage/UmamiForwarder.php:81–84` und `:111–116` — der Unterbrecher wird erst **nach** einem
gescheiterten Aufruf gesetzt; bis dahin prüft jede parallele Anfrage einen leeren Unterbrecher und startet
ihren eigenen Versuch mit vollem Zeitlimit (`TIMEOUT = 2`, `MAX_DURATION = 3`) und belegtem PHP-Prozess.

**Einordnung:** Lokal läuft der Symfony-Entwicklungsserver mit wenigen PHP-Prozessen; wie stark sich das
auf FrankenPHP in Produktion auswirkt, hängt von dessen Threadzahl und vom gleichzeitigen Besuch ab. Die
Ursache — N gleichzeitige Anfragen warten N-mal — ist davon unabhängig.

**Vorschlag:** Vor dem Aufruf einen kurzlebigen „Versuch läuft"-Vermerk setzen, damit nur eine Anfrage je
Ausfall wartet, und/oder ein kürzeres Verbindungs-Zeitlimit getrennt vom Gesamtlimit.

**Behoben am 2026-09-13 (`sdd-build`, nicht committet):** `UmamiForwarder` lässt **höchstens eine
Weiterleitung zur Zeit** zu (Dateisperre `umami-weiterleitung` über die vorhandene `LockFactory`, `flock`).
Wer den Platz nicht binnen 100 ms bekommt, antwortet 202 ohne Weiterleitung; wer ihn nach einem
gescheiterten Vorgänger bekommt, sieht den Unterbrecher ein zweites Mal nach und sendet nicht. Zeitlimit
(2 s), Gesamtdauer (3 s) und Unterbrecher (60 s) bleiben wie im Entwurf — die Reparatur bringt den Code auf
Entscheidung 7 („ein hängender Aufruf je Minute"). Statt des vorgeschlagenen Vermerks im Cache eine Sperre,
weil PSR-6 kein atomares „anlegen, falls nicht vorhanden" kennt — gleichzeitig startende Anfragen hätten den
Vermerk alle leer vorfinden können. Das ist abgeleitet, nicht gemessen.
Nachweise:
- Reproduktion aus diesem Bericht, vorher/nachher auf derselben Umgebung (`qa/11/bf149-nachpruefung.sh`,
  `.ausgabe.txt`): **vorher** alle sechs Zählaufrufe 2,05–3,88 s, Restaurantliste 1,78–1,80 s bis zum ersten
  Byte; **nachher** genau einer 2,06 s, die übrigen fünf 0,17–0,40 s, Restaurantliste 0,058–0,060 s — in
  beiden Runden, also auch nach abgelaufenem Unterbrecher.
- ⚠ **Der Preis, gemessen gegen einen gesunden Eingang (30 ms):** Von sechs exakt gleichzeitigen
  Zählaufrufen kamen in drei Runden 6, 5 und 5 an; bei 50 ms Abstand 20 von 20, bei 200 ms 20 von 20.
- `UmamiForwarderTest`: `testBelegterPlatzSendetNichtUndWartetNurKurz`, `testPlatzWirdNachFehlschlagFrei`,
  `testNachGescheitertemVorgaengerWirdNichtGesendet`. Gegenproben: ohne Platzprüfung 2 rot, ohne zweiten
  Blick auf den Unterbrecher 1 rot; ohne `release()` bleibt es grün, weil sich die Sperre beim Verlassen der
  Methode selbst freigibt — so im Test vermerkt.

## Hinweise ohne Befund

- **Code-Review** (`code-reviewer`-Agent, im Anschluss an den Bau über alle geänderten Dateien): kein
  Befund mit Konfidenz ≥ 80. Positivlisten, Kopfzeilen, Schlüsselbindung, Limiter-Verdrahtung,
  Template-Escaping einzeln verifiziert. Ein Hinweis unter der Schwelle, von der QA nachgesehen und
  bestätigt: `CollectControllerTest::andereWege` beschriftet `GET /login → 404` als „Umami-Oberfläche",
  geprüft wird dort aber nur das Sprachpräfix-Routing der eigenen Anmeldeseite. Inhaltlich richtig,
  die Beschriftung verspricht mehr. **Beide Befunde dieses Berichts hat das Review nicht gesehen** — sie
  zeigen sich erst gegen eine laufende Umami-Instanz bzw. unter gleichzeitiger Last.
- **Umami verwirft `HeadlessChrome` als Bot.** Für echte Besucher belanglos; wer die Messung später mit
  einem Headless-Browser abnimmt (AK-34), sieht sonst nichts und sucht den Fehler in der Anwendung.
- **Offene Betriebsschritte** vor jeder Auslieferung: T01–T05 (VPS, Umami, Benutzer, Tunnel, Kennung),
  T29–T31; OF-05 und OF-06 in `/sdd-betrieb`. Die zehn nicht prüfbaren Kriterien werden erst danach
  prüfbar — eine erneute QA nach T05 ist deshalb ohnehin fällig.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Unit/Usage/Qa11TrichterMusterTest.php` | 1 (übersprungen bis zur Behebung; ohne `markTestSkipped` rot, gefahren) | BF-150: kein `*` mitten in einem Pfadschritt von `growth/config.json` |
| `qa/11/browser-pruefung.mjs` | 34 | AK-01–03, 05–13, 15–24, 31 (Speicherung), 36; EC-02 |
| `qa/11/angriff.sh` | 32 | AK-02–04, 06, 07, 09, 12, 13, 17, 21, 22, 24, 25, 27, 30 |
| `qa/11/ladezeit.sh` + `ladezeit.mjs` | 6 Bedingungen × 3 Seiten × 5 Aufrufe + Funktionen | AK-37, EC-01, BF-149 |
| `qa/11/offline.mjs` | 4 Phasen | EC-06 |
| `qa/11/legal.py` | 13 Angaben × 4 Sprachen | AK-31 (Text) |

## Nächster Schritt

`/sdd-build 11 BF-149 und BF-150 beheben`, danach erneut `/sdd-qa 11`. Status `review`.
Ausgeliefert wird erst nach T01–T05 und einer QA, die die zehn Instanz-Kriterien gegen den zweiten VPS
prüft.
