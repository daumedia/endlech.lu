# 10 · Sitemap und robots.txt — Testbericht

Stand: 2026-09-13, **zweiter Durchgang nach dem Fehlerauftrag BF-147** · Geprüft gegen `spec.md` (mit
OF-01 bis OF-08) · Erster Durchgang auf `feature/10-sitemap-robots` (gemergt, #125), Nachprüfung auf
`fix/bf-147-seitenzahl`, nicht committet

## Fazit

**Production-ready: ja**

**BF-147 ist behoben und nachgeprüft.** Keine Seite, die nicht blättert, nennt mehr eine
Parametervariante als maßgeblich: 384 Abrufe über alle 128 Sitemap-Seiten mit angehängter Seitenzahl
und Fremdparametern ohne eine Abweichung. Die Restaurantliste und die Board-Übersicht behalten ihre
Seitenzahl in canonical und Sprachverweisen. Gegenprobe am laufenden Server: ohne Reparatur 740
Abweichungen. Der vollständige Regressionslauf von außen besteht wieder 26 von 26, die Testsuite
1195 Tests.

Die Reparatur hat nichts verschoben: Sitemap, robots.txt, Ausschluss und Deckel verhalten sich wie im
ersten Durchgang, das Code-Review findet keinen Fehler.

**Offen, keiner davon blockiert:**
- **BF-148 (niedrig):** Ein Satz in EC-05 der Spec ist falsch. Das kann nur der Betreiber ändern (OF-08).
- **OF-07:** Die Board-Übersicht blättert, AK-13 und `design.md` Entscheidung 11 nennen nur die
  Restaurantliste. Eine Seitenzahl jenseits der letzten Board-Seite liefert eine leere Seite mit 200,
  die sich selbst nennt.
- **OF-05, OF-06:** unverändert beim Betreiber.
- **AK-24, AK-25:** nicht prüfbar vor dem Deploy (Search Console).

Ursprünglicher Stand des ersten Durchgangs: 23 von 25 Kriterien bestanden, BF-147 (mittel) und
BF-148 (niedrig) gefunden.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 23 von 25 |
| davon bestanden | 23 |
| davon durchgefallen | 0 |
| **nicht prüfbar** | 2 (AK-24, AK-25 — Plattformnachweise nach dem Deploy) |
| Edge Cases belegt | 5 von 6 (EC-03 ist ein Rechenrand, nicht ausführbar) |
| Tests neu geschrieben | 5 in `Qa10SitemapBestandTest` (1 in der Nachprüfung) + Prüfwerkzeuge `qa/10/http-pruefung.py` (26 Prüfungen) und `qa/10/bf147-nachpruefung.py` (444 Abrufe) |
| Tests grün | **1195 von 1195**, 10 übersprungen (wie vor dem Bau; erster Durchgang 1191/11) |

## Auf der Produktion (2026-09-13, `v2026.09.13`)

Nach dem Ausrollen gegen `https://endlech.lu` mit denselben Werkzeugen gefahren; vollständige Ausgaben
in `qa/10/produktion-2026-09-13.md`. Bestand dort: 3 Restaurants.

| Prüfung | Ergebnis |
|---|---|
| Crawler-Prüfung (AK-01–07, 12–21, EC-06) | ✅ 24 von 25; Sitemap 96 = (21 + 3) × 4, Schema gültig, alle 96 Seiten 200 mit passendem canonical und Sprachverweisen, 64 Ausschlusswege mit `noindex`, robots.txt byte-gleich. ⚠ AK-13 `?page=2` → 404, weil es bei drei Restaurants keine Seite 2 gibt — mit diesem Bestand nicht belegbar, lokal bestanden |
| BF-147 | ✅ 288 Abrufe über alle Sitemap-Seiten mit Seitenzahl und Fremdparametern ohne Abweichung; Board behält die Seitenzahl |
| AK-23 Deckel | ✅ erster 429 beim insgesamt 61. Abruf, `Retry-After: 3308`, kein `Set-Cookie`; Datensatz danach 200 |
| Anmeldeseite, Testdaten, `/health` | ✅ `/de/login` 200 mit beiden Anmeldewegen und `noindex`; 0 Fixture-Namen in Liste und Sitemap; `/health` 200 |
| AK-24, AK-25 | ⚠️ weiterhin nicht prüfbar — T17/T18 in der Search Console, Betreiber |

## Nachprüfung nach dem Fehlerauftrag (2026-09-13)

Geprüft wurde die Reparatur von BF-147 auf `fix/bf-147-seitenzahl` — und alles, was sie berühren
kann. Umgebung wie im ersten Durchgang: Produktionsmodus gegen `endlech_test`, 11 Restaurants, kein
Debug-Token in den Antworten. Der Abschlussbericht des Fehlerauftrags steht am Ende von `tasks.md`.

**1 · Die Reparatur selbst, von außen.** Neues Werkzeug `qa/10/bf147-nachpruefung.py`, unabhängig vom
Anwendungscode: Die blätternden Seiten sind aus AK-13 und OF-07 abgeschrieben, die Erwartung je Seite
ist ihr eigener Sitemap-Eintrag. Ausgabe `qa/10/bf147-nachpruefung.ausgabe.txt`:

| Prüfung | Ergebnis |
|---|---|
| Alle 128 Sitemap-Seiten × 3 Abfragen (`page=2`, `page=2&sort=name&foo=//fremd.example`, `page=37`) = 384 Abrufe | ✅ 360 nicht blätternde Abrufe: canonical = Sitemap-Adresse, Sprachverweise = Sitemap-Eintrag, 0 Abweichungen |
| Blätternde Seiten (Restaurantliste, Board-Übersicht) mit Seitenzahl | ✅ 20 Abrufe: canonical und alle fünf Sprachverweise tragen `?page=N`; die 4 Abrufe `restaurants?page=37` → 404 ohne Verweis |
| Seite 1 und ungültige Seitenzahlen (`1`, `0`, `-2`, `2%0A`, `+2`) auf beiden blätternden Seiten, 4 Sprachen | ✅ keine Seitenzahl im Verweis |
| Fremdparameter in irgendeinem Verweis | ✅ 0 |
| Ausschlusswege mit `?page=2` (die 5 anonym rendernden × 4 Sprachen) | ✅ kein canonical, 5 Sprachverweise ohne Abfrage |

⚠ **Gegenprobe am laufenden Server:** die Reparatur in `SeoExtension` kurz herausgenommen → das
Werkzeug meldet **740 Abweichungen** (`/de/about?page=2: canonical …?page=2`), wiederhergestellt
(byte-gleich) → 0. Es prüft also etwas.

⚠ **Ein Fehler im eigenen Werkzeug unterwegs korrigiert**, bevor Ergebnisse zählten: Die erste Fassung
führte `/community/suggest` als rendernden Ausschlussweg. Der Weg verlangt eine Anmeldung und antwortet
mit 302 — 8 Scheinabweichungen. Alle 16 Ausschlusswege einzeln abgerufen (5 × 200, 5 × 302, 6 × 404
für `/de`), die Liste auf die fünf mit 200 berichtigt.

**2 · Regression, alle Kriterien von außen.** `qa/10/http-pruefung.py` unverändert erneut gefahren,
Deckel-Zähler und Sitemap-Speicher vorher geleert: **26 von 26** (`qa/10/http-pruefung.ausgabe.txt`),
darunter AK-12, AK-13 (alle Beispielzeilen), AK-14, AK-15, AK-16 und AK-23 (erster 429 bei Nr. 61)
sowie EC-06.

**3 · Testsuite.** `php bin/phpunit`: **1195 Tests grün**, 10 übersprungen (erster Durchgang 1191/11;
+3 aus dem Fehlerauftrag, +1 aus dieser Prüfung, Reproduktion nicht mehr übersprungen).

**4 · Beobachtung zu OF-07, kein Befund:** `/de/community/ideen?page=99` → **200**, canonical
`…/de/community/ideen?page=99` (leere Liste); `/de/restaurants?page=99` → 404. Das Board-Verhalten ist
älter als Feature 10, und die Spec regelt die Board-Übersicht nicht — die Frage steht als OF-07 beim
Betreiber.

### Sicherheitsprüfung der Nachprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | `/de/restaurants/999999` → 404; `/de/community/ideen/1?page=2` → 404. Die Änderung berührt keinen Datensatzzugriff |
| Rate Limit greift | bestanden | AK-23 erneut: 60 × 200, erster 429 bei Nr. 61, `Retry-After: 3594`, kein `Set-Cookie` |
| PII in Logs | bestanden | Anwendungsprotokoll (Produktionsmodus): 0 × Probe-Token, 0 E-Mail-Adressen, 0 `http_client`/`critical`. ⚠ 57 × Probe-Token stehen im **Zugriffsprotokoll des lokalen Symfony-CLI-Servers** (`"source":"server"`, Anfragepfad) — das ist der Entwicklungswebserver, nicht die Anwendung und nicht die Produktion |
| PII an externe Dienste | bestanden | Im Änderungsumfang (`git diff main`) 0 × `HttpClient`, `Mailer`, `Messenger` |
| Zugriffsregeln serverseitig | bestanden | `/de/admin` → 302, `/de/profile` → 302, `/api/v1/me` → 401 |
| Geheimnisse im Repository | bestanden | Änderungsumfang samt neuer Dateien: 0 Schlüssel-/Token-Muster |
| Eingaben | bestanden | `/de/about` mit `page[]=2`, 20-stelliger Zahl, `page=<script>`, `_locale=//evil.example` → 200, canonical `…/de/about`; Restaurantliste und Board mit `page[]=2` → 400, Board mit 20-stelliger Zahl → 400; `_locale=//evil.example` erreicht keinen Verweis; `<script>alert(1)</script>` in `x`, `status`, `city` erscheint auf keiner der drei Seiten ungeschützt |
| Löschen | trifft nicht zu | Die Änderung speichert und löscht nichts |

## Wie geprüft wurde

Nicht im Entwicklungsmodus: Dort setzt Symfony `X-Robots-Tag: noindex` auf **jede** Antwort, und
AK-15/AK-16 wären nicht unterscheidbar. Stattdessen `symfony server:start` im
**Produktionsmodus** (`APP_ENV=prod`, `APP_DEBUG=0`) gegen `endlech_test` mit 11 Restaurants.

Das Prüfwerkzeug `qa/10/http-pruefung.py` ist **unabhängig vom Anwendungscode**: Die festen
Seiten und die Ausschlusswege sind aus der Spec abgeschrieben, nicht aus dem Seitenverzeichnis
gelesen, und es wertet die robots.txt mit eigener Logik aus. Es folgt keinen Weiterleitungen.
Ausgabe: `qa/10/http-pruefung.ausgabe.txt`. Die übrigen Belege vom Server: `qa/10/server-belege.md`.

⚠ **Zwei Fehler im eigenen Prüfwerkzeug unterwegs gefunden und korrigiert**, bevor Ergebnisse
zählten: Der Deckel-Test ignorierte zwei frühere Sitemap-Abrufe desselben Laufs (meldete „erster
429 bei 59", richtig ist 61 über alle Abrufe); und der Vergiftungstest für EC-06 wäre auch bei
anfrageabhängigem Host grün gewesen, weil ein gewöhnlicher Abruf den Speicher vorher gefüllt
hatte — der `www`-Abruf ist jetzt der **erste** nach leerem Speicher.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /sitemap.xml → 200, application/xml; charset=utf-8`; ausgelieferte Datei gegen `tests/Fixtures/Sitemap/sitemap-mit-sprachverweisen.xsd` **gültig**, Gegenprobe gegen `sitemap.xsd` allein ungültig (`qa/10/server-belege.md`); `SitemapGeneratorTest::testDieSitemapBestehtDieSchemaPruefung` |
| AK-02 | ✅ bestanden | 128 `<url>` bei n = 11 aus SQL (`SELECT COUNT(*) FROM restaurant`), (21 + 11) × 4 = 128; die 84 festen Einträge deckungsgleich mit der Spec-Tabelle, abgeschrieben im Prüfwerkzeug |
| AK-03 | ✅ bestanden | 128 Adressen, 0 mit anderem Anfang als `https://endlech.lu/(lb\|de\|fr\|en)/` oder mit `?` |
| AK-04 | ✅ bestanden | 128 Adressen einzeln abgerufen, **ohne Weiterleitungen zu folgen**: 128 × 200 |
| AK-05 | ✅ bestanden | 0 Einträge ohne `lb, de, fr, en, x-default` in dieser Reihenfolge, `x-default = lb`, und jeder Eintrag führt sich unter seiner Sprache selbst |
| AK-06 | ✅ bestanden | 0 Adressen aus Verwaltung, Profil, Schnittstelle, Anmelde-/Passwort-/Bestätigungs-/Abmelde-/Dankeseiten, Formularen, einzelnen Ideen |
| AK-07 | ✅ bestanden | kein `lastmod`, `priority`, `changefreq` im ausgelieferten XML; Gegenprobe beim Bau (`<lastmod>` in der Vorlage färbt `SitemapGeneratorTest` rot) |
| AK-08 | ✅ bestanden | Am Server: Speicherdauer **3000 s**, an der Datei gemessen, plus `max-age=600` = 3600 s. Restaurant Nr. 750 per SQL angelegt → in der gespeicherten Fassung 0 Einträge, nach Ablauf **4** (`qa/10/server-belege.md`). Über HTTP: `Qa10SitemapBestandTest::testAk08NeuesRestaurantInAllenVierSprachen` |
| AK-09 | ✅ bestanden | Am Server: nach dem Löschen in der gespeicherten Fassung noch 4, nach Ablauf **0**. Über HTTP: `Qa10SitemapBestandTest::testAk09GeloeschtesRestaurantFaelltHeraus` |
| AK-10 | ✅ bestanden | `Qa10SitemapBestandTest::testAk10OhneRestaurantsGenauVierundachtzigEintraege` — über HTTP nach `DELETE FROM restaurant`: 200 und 84 Einträge. Nicht am Server geprüft, um den Testbestand nicht zu leeren |
| AK-11 | ✅ bestanden | Echter Ausfall (Datenbank nicht vorhanden), kein gespeicherter Stand: zwei Abrufe **HTTP 500**, 0 Einträge, kein `<?xml`, danach keine gespeicherte Fassung; `/health` zugleich 200. Gegenprobe beim Bau: ein `try`/`catch` färbt `SitemapGeneratorTest` rot |
| AK-12 | ✅ bestanden | Alle 128 Seiten abgerufen: canonical ≠ Sitemap-Adresse in 0 Fällen, genau **ein** canonical je Seite. ⚠ Siehe **BF-147**: mit einem angehängten `?page=N` erklärt sich jede Seite selbst für maßgeblich — der Wortlaut von AK-12 („wenn sie aufgerufen wird", unter ihrer Adresse) ist erfüllt |
| AK-13 | ✅ bestanden | `?sort=name`, `?wheelchair=1&city=Esch`, `?page=1`, `?page=0` → `…/de/restaurants`; `?page=2&sort=name` → `…/de/restaurants?page=2`. ⚠ Die Zeile `?page=abc` beschreibt eine Seite, die nie entsteht: **400, kein Verweis** — OF-06 in der Spec, kein Fehler der Umsetzung |
| AK-14 | ✅ bestanden | Alle 128 Seiten: Sprachverweise im Kopf = Sitemap-Eintrag in 128 Fällen |
| AK-15 | ✅ bestanden | 16 Wege × 4 Sprachen = 64 Abrufe im Produktionsmodus, Statuscodes 200: 20, **302: 20**, 404: 24 — alle 64 mit `X-Robots-Tag: noindex`, die Weiterleitungen eingeschlossen |
| AK-16 | ✅ bestanden | Alle 128 Sitemap-Seiten: 0 mit `X-Robots-Tag` oder `noindex`-Meta |
| AK-17 | ✅ bestanden | `GET /robots.txt → 200, text/plain; charset=utf-8`, Sitemap-Zeile vorhanden, ausgelieferte Datei **byte-gleich** mit `public/robots.txt` |
| AK-18 | ✅ bestanden | 22 Pfade (Verwaltung, Profil, Schnittstelle in vier Sprachen, `/api/v1`, `/api/docs`): alle gesperrt |
| AK-19 | ✅ bestanden | 131 Pfade (alle Sitemap-Adressen + `/open.json`, `/open/dataset.csv`, `/open/dataset.json`): 0 gesperrt |
| AK-20 | ✅ bestanden | 64 Ausschlusswege: 0 gesperrt |
| AK-21 | ✅ bestanden | 13 Regeln, keine sperrt `/`; `/` und `/lb/` erlaubt; keine Platzhalter |
| AK-22 | ✅ bestanden | 128 Adressen samt aller Sprachverweise: 0 mit `@` oder Token-Muster (≥ 24 Hex-Zeichen) |
| AK-23 | ✅ bestanden | Produktionsmodus, Zähler geleert: Abrufe 1–60 alle **200**, erster **429 bei Nr. 61**, `Retry-After: 3593`; `/open/dataset.json` danach 200 (eigenes Kontingent); **kein** `Set-Cookie` auf den gedeckelten Antworten |
| AK-24 | ⚠️ nicht prüfbar | Plattformnachweis in der Search Console — setzt die ausgelieferte Sitemap auf `https://endlech.lu` voraus. T17 nach dem Deploy, Screenshot hier nachtragen |
| AK-25 | ⚠️ nicht prüfbar | robots.txt-Bericht der Search Console — ebenso, T18 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Echter Ausfall mit gespeicherter Fassung: **HTTP 200, 128 Einträge, byte-identisch** mit der zuvor gespeicherten — während `/de/restaurants` zugleich 500 liefert |
| EC-02 | ✅ bestanden | Nach dem Löschen nennt die gespeicherte Fassung das Restaurant noch (4 Einträge, AK-09), die Detailseite eines nicht vorhandenen Restaurants antwortet mit 404 (`/de/restaurants/999999`) |
| EC-03 | ⚠️ nicht prüfbar | Rechenrand (12.479 Restaurants), nicht ausführbar; die Spec nimmt das Aufteilen aus dem Umfang |
| EC-04 | ✅ bestanden | Der Deckel greift bei gefüllter gespeicherter Fassung (AK-23-Lauf, Fassung vorher gespeichert) |
| EC-05 | ❌ weicht ab | Die Spec sagt „eine Seitenzahl jenseits der letzten Seite nennt sich selbst"; gemessen antwortet die Restaurantliste mit **404** und nennt nichts (`?page=3`, `?page=99`). Das ist harmloser als beschrieben — der Fehler liegt an der Spec, siehe **BF-148** |
| EC-06 | ✅ bestanden | **Erster** Abruf nach geleertem Speicher mit `Host: www.endlech.lu` → 200, kein `www` im XML; der folgende gewöhnliche Abruf byte-identisch |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Die Sitemap enthält nur Nummern bestehender Restaurants (Abgleich mit SQL); `/de/restaurants/999999` → 404. Keine kontobezogenen Datensätze im Feature |
| Rate Limit greift | bestanden | 60× 200, 61. → 429 mit `Retry-After`, eigenes Kontingent (AK-23); robots.txt ohne Deckel — statische Datei, erreicht PHP nie |
| PII in Logs | bestanden | Serverprotokoll im Produktionsmodus nach 64 Token-Abrufen: 0 × Probe-Token, 0 × E-Mail-Adresse. ⚠ Einschränkung: Im Produktionsmodus schreibt nur der Fehlerpfad (`fingers_crossed`), der Mitschnitt ist kurz |
| PII an externe Dienste | bestanden | Kein HTTP-Client, Mailer oder Messenger in `src/Seo`, `SitemapController`, `SeoRobotsHeaderSubscriber`, `SeoExtension`; 0 `http_client`-Einträge im Protokoll. Die Search Console empfängt nichts — Google holt sich die Dateien selbst |
| Zugriffsregeln serverseitig | bestanden | Die robots-Sperre ist keine Zugriffsregel und täuscht keine vor: `/de/admin` → 302 auf `/de/login`, `/de/profile` → 302, `/api/v1/me` ohne Token → 401 |
| Geheimnisse im Repository | bestanden | 0 Schlüssel-/Token-Muster im Änderungsumfang; keine Search-Console-Prüfdatei, kein Meta-Tag (Domain-Property über DNS) |
| Eingaben | bestanden, mit **BF-147** | `/sitemap.xml?x=<script>…` → 200, Rumpf identisch; POST → 405; `/sitemap.XML`, `/lb/sitemap.xml`, `%00` → 404; `Host: evil.example` → 400 (Vergiftung ausgeschlossen); `?page=2%0A`, `+2` → Seitenzahl verworfen; `?page[]=2`, `02`, 20-stellig → 400 von der Liste; `?_locale=//evil.example` erreicht weder canonical noch Sprachverweis. ⚠ `?page=N` auf jeder Seite → BF-147 |
| Löschen | trifft nicht zu | Das Feature speichert nichts Kontobezogenes; die Löschung eines Restaurants ist AK-09 |

## Code-Review

Der `code-reviewer`-Agent hat alle geänderten Dateien und Prüfläufe gelesen und **keinen Fehler**
gemeldet. Seine Aussagen decken sich mit dem, was hier gemessen wurde: fester Host ohne Anfragebezug,
kein Auffangen des Datenbankfehlers, eigenes Deckel-Kontingent, keine robots-Regel mit
Nebentreffern, keine tautologischen Prüfläufe.

⚠ **BF-147 hat er nicht gefunden.** Er bemerkte richtig, dass `design.md` (Entscheidung 11) mit
„keine andere Verzeichnisseite kennt einen Seitenparameter" falsch liegt, weil auch das Board blättert,
und hielt die generische Regel deshalb für harmlos. Dass sie auch auf Seiten greift, die **nicht**
blättern, zeigte erst der Abruf von außen. Sein Hinweis ist damit keine Gegenstimme, sondern die
Ursache des Befunds: Die Regel wurde am Fall gebaut und geprüft, für den sie entworfen war.

Keiner seiner Aussagen musste widersprochen werden; ungeprüft übernommen wurde nichts.

### Code-Review der Reparatur (2026-09-13)

Der `code-reviewer`-Agent hat den Änderungsumfang von `fix/bf-147-seitenzahl` gelesen: **kein Fehler.**
Seine tragenden Aussagen, jede im Code nachgeprüft:

| Aussage | Nachgeprüft |
|---|---|
| Nur zwei Aufrufer des Adressbildners; die Sitemap übergibt nie eine Abfrage | ✅ `SeoExtension.php:50, :62`, `SitemapGenerator.php:62` (ohne Abfrage) — einzige Treffer in `src/` |
| Keine eigene Fehlerseite, die den Seitenkopf rendert | ✅ weder `templates/bundles/` noch `templates/error/` vorhanden; 404 der Restaurantliste ohne Verweis auch am Server gemessen |
| Der Abgleich in `SeoRouteCoverageTest` könnte nur mit einer heute nicht verwendeten Schreibweise falsch grün sein (`$request->get('page')`, benannte Argumente, invokable Controller) | ✅ keine dieser Formen in `src/Controller/` |
| `design.md` Entscheidung 11 („keine andere Verzeichnisseite kennt einen Seitenparameter") ist durch die Reparatur sachlich überholt und wurde nicht mitgezogen | ✅ Zeile 147 unverändert. **Kein Befund:** `sdd-build` darf den Entwurf nicht ändern, und der Widerspruch steht als OF-07 beim Betreiber. Als Hinweis in *Nächster Schritt* |

⚠ **Hinweis zum Abgleich, kein Befund:** Er erkennt eine blätternde Seite nur an
`->query->getInt('page'`, `->query->get('page'` oder `->query->all('page'` in der Controller-Methode
selbst. Liest ein künftiger Controller die Seitenzahl anders, zum Beispiel über `$request->get('page')`
oder eine Hilfsmethode, bleibt der Abgleich grün. Heute trifft das auf keine Stelle zu.

## Fehler

### BF-147 · Die Seitenzahl-Regel gilt für jede Seite, nicht nur für blätternde Listen — mittel · ✅ behoben, nachgeprüft 2026-09-13

> **Nachprüfung:** Reproduktion ohne Überspringen grün; von außen 384 Abrufe über alle Sitemap-Seiten
> ohne Abweichung, Gegenprobe ohne Reparatur 740 Abweichungen (siehe *Nachprüfung nach dem
> Fehlerauftrag*). Die Board-Übersicht behält ihre Seitenzahl, wie im Vorschlag — ob AK-13 sie nennen
> soll, ist OF-07.

**Betrifft:** AK-12 (Absicht), `design.md` Entscheidung 11 („Nur für die Restaurantliste relevant —
keine andere Verzeichnisseite kennt einen Seitenparameter")
**Reproduktion:**
1. `curl -s https://<host>/de/about?page=2 | grep 'rel="canonical"'`
2. dasselbe für `/de/presse?page=12`, `/lb/vergleich/wheelmap?page=3`, `/de/restaurants/1?page=7`
**Erwartet:** `https://endlech.lu/de/about` (die Seite blättert nicht), usw.
**Tatsächlich:** `https://endlech.lu/de/about?page=2` — die Seite erklärt eine Parametervariante zur
maßgeblichen Adresse, ebenso in allen Sprachverweisen. Inhaltlich ist sie `/de/about` (Abweichung
nur im Sprachumschalter, der die Abfrage absichtlich weiterträgt, BF-68).
**Folge:** Jeder, der `?page=N` an eine beliebige Seite hängt und die Adresse verlinkt, erzeugt eine
Dublette, die sich selbst kanonisiert — unbegrenzt viele je Seite. Genau das sollte der
canonical-Verweis verhindern. Zusätzlich liefert das Board auf `?page=99` eine **leere** Seite mit 200,
die sich selbst für maßgeblich erklärt. Kein Sicherheitsproblem: Die Seitenzahl kommt als geprüfte
ganze Zahl heraus.
**Ort:** `src/Seo/SeoUrlBuilder.php` — `retainedQuery()` wird für jede Route angewandt;
`src/Twig/SeoExtension.php` reicht die Abfrage jeder Route durch.
**Warum der Bau es nicht sah:** `SeoUrlBuilderTest` und `SeoHeadTest` prüfen die Regel nur an der
Restaurantliste — also genau an dem Fall, für den sie entworfen wurde.
**Vorschlag:** Die Seitenzahl nur für Routen behalten, die tatsächlich blättern (Restaurantliste,
Board-Übersicht), als Liste im Seitenverzeichnis. Die Reproduktion liegt fertig als
`Qa10SitemapBestandTest::testBf147SeitenzahlNurAufSeitenDieBlaettern` (übersprungen) — Überspringen
entfernen.

### BF-148 · EC-05 beschreibt ein Verhalten, das die Restaurantliste nicht hat — niedrig · offen (OF-08)

**Betrifft:** EC-05
**Reproduktion:** `curl -s -o /dev/null -w '%{http_code}' https://<host>/de/restaurants?page=99`
**Erwartet (laut Spec):** Die Seite nennt sich selbst als maßgeblich.
**Tatsächlich:** **404**, kein Verweis — die Liste weist Seitenzahlen jenseits der letzten Seite schon
heute ab (B05). Harmloser als beschrieben.
**Ort:** `features/10-sitemap-robots/spec.md`, EC-05
**Vorschlag:** EC-05 auf das gemessene Verhalten berichtigen; zusammen mit OF-06 (dieselbe Ursache:
die Spec nahm an, die Liste liefere für jede Seitenangabe eine Seite).

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Seo/Qa10SitemapBestandTest.php` | 5 | AK-10, AK-08, AK-09 über HTTP; BF-147-Reproduktion (seit dem Fehlerauftrag scharf); **neu in der Nachprüfung:** `testBf147DetailseiteBoardUndAusschlussUeberHttp` — Detailseite verliert die Seitenzahl auch in den Sprachverweisen, Board behält sie, Anmeldeseite ohne Abfrage in den Sprachverweisen. Gegenprobe ohne Reparatur: rot (`…/de/restaurants/1?page=7`) |
| `qa/10/http-pruefung.py` | 26 Prüfungen | AK-01–07, 12–23, EC-06 von außen, unabhängig vom Anwendungscode |
| `qa/10/bf147-nachpruefung.py` | 444 Abrufe (384 + 40 + 20) | BF-147 über alle Sitemap-Seiten, blätternde Seiten, ungültige Seitenzahlen, Ausschlusswege |

Die Reproduktion BF-147 wurde **ohne** Überspringen gefahren und ist heute rot
(`Failed asserting … 'https://endlech.lu/de/about'`) — sie prüft also etwas.

## Nächster Schritt

Production-ready ist **ja**, BF-147 ist behoben und nachgeprüft. Der Status geht auf `approved`.

**Nächster Aufruf:** `/sdd-deploy 10`. ⚠ Vorher gehört `fix/bf-147-seitenzahl` committet und nach
`main` gemergt: Der Preflight verlangt ein sauberes Arbeitsverzeichnis, und `main` trägt Feature 10
bislang **ohne** diese Reparatur.

Nach dem Deploy: T17 und T18 — Sitemap in der Search Console einreichen, robots.txt-Bericht öffnen,
Screenshots hier unter AK-24 und AK-25 nachtragen.

Beim Betreiber, keine davon blockiert:
- **OF-07:** AK-13 um die Board-Übersicht ergänzen? Soll das Board auf eine Seitenzahl jenseits der
  letzten Seite mit 404 antworten wie die Restaurantliste? Dabei `design.md` Entscheidung 11
  nachziehen, die nur die Restaurantliste nennt.
- **OF-08 / BF-148:** EC-05 auf das gemessene Verhalten berichtigen (404, kein Verweis).
- **OF-05:** Hinweisseite zur E-Mail-Bestätigung ausschließen?
- **OF-06:** Beispielzeile `?page=abc` in AK-13 streichen?
