# 05 · Presse-Kit — Testbericht

Stand: 2026-08-30 · Geprüft gegen `spec.md` vom 2026-08-30 (44 Kriterien)

## Fazit

**Production-ready: nein.**

**Der schwerste Befund ist erst entstanden, als die Prüfung den Regelfall herstellte:
Sobald das Materialpaket existiert, antwortet `/presse` in allen vier Sprachen mit
HTTP 500** (BUG-05, kritisch). `_material.html.twig:44` ruft `package.publicPath` — eine
Eigenschaft, die es auf `PressPackage` nicht gibt, weil der Pfad dort eine
Klassenkonstante ist. Der Fehler konnte sich verstecken, weil die heutige Umgebung kein
Paket hat: Der einzige Lauf, der diesen Abschnitt anfasst, verzweigt an
`PressPackage::exists()` und prüft ausschließlich den Ersatzzweig. **Der Regelfall des
Features lag in keinem einzigen Test.** Nachgestellt mit einem angelegten Paket: 500 in
lb, de, fr und en; nach dem Entfernen wieder 200.

Davon abgesehen ist die Seite in einem sehr guten Zustand: 31 von 44 Kriterien bestanden,
darunter alle zu Barrierefreiheit, Mehrsprachigkeit, Datenschutz und Missbrauchsschutz.
Der Angriffsdurchlauf blieb **ohne Fund** — keine fremde Ressource, keine Reflexion von
Eingaben, keine Schreibwege, keine Personendaten in Protokollen, kein Geheimnis im
Quelltext; axe-core (WCAG 2.2 AA) meldet in allen vier Sprachfassungen null Verstöße.

Die übrigen offenen Punkte sind **kein Code, sondern Material und eine Entscheidung**:
Die vier Vektormarken fehlen (VB-01), das Postfach ist nicht eingerichtet (VB-02), die
Betreiberangaben stehen nicht fest (VB-03). Der schlichteste Befund fasst es zusammen:
**Ein Journalist kann mit dieser Seite heute keinen Beitrag schreiben, ohne
nachzufragen** — genau das, was AK-41 zusagt.

Dazu ein zweiter Befund aus dem Codequalitäts-Durchlauf, per Mutation bestätigt: Wird ein
Schlüssel, den nur eine Vorlage zusammensetzt (`'material.allowed_' ~ i`), aus **allen
vier** Katalogen entfernt, bleibt die Testsuite grün und die Seite zeigt Besuchern den
rohen Schlüssel (BUG-06). AK-30 selbst ist davon nicht berührt — es verlangt den Fall
„fehlt in *einer* der vier Sprachen", und der wird sauber rot.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 44 von 44 |
| davon bestanden | 31 |
| davon durchgefallen | 6 |
| **nicht prüfbar** | 7 |
| Edge Cases belegt | 7 von 10 |
| Tests neu geschrieben | 2 (`PressFiguresConsistencyTest`, `PressDownloadStateTest`) |
| Tests grün | 736 von 741 · **5 sind Befund-Nachweise zu BUG-05 und absichtlich rot**; 13 übersprungen, davon 4 aus diesem Feature |

## Akzeptanzkriterien im Einzelnen

### Auffindbarkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `curl /de/restaurants` → `href="/de/presse" …>Presse` in der Fußzeile |
| AK-02 | ❌ durchgefallen | Ohne Paket je **200**. **Mit vorhandenem Paket — dem Regelfall des Features — je 500** in allen vier Sprachen → **BUG-05**. Nachweis: `PressDownloadStateTest` (4 Fehler), Meldung „Neither the property \"publicPath\" … exist\" in `_material.html.twig` Zeile 44 |
| AK-03 | ✅ bestanden | `curl /de/about` → genau ein `id="presse-teaser"` mit Link auf `/de/presse` |
| AK-04 | ✅ bestanden | Echter Klick im Browser: `/de/presse` + „FR" → `/fr/presse`; + „LB" → `/lb/presse`; `/fr/presse` + „EN" → `/en/presse`. Gegenprobe `/de/about` → `/fr/about` |
| AK-05 | ✅ bestanden | `curl /presse` → **302**, `Location: /lb/presse` |
| AK-06 | ✅ bestanden | Titel „Presse – Endlech.lu" gegen „Über Endlech \| Endlech.lu", „Impressum – Endlech.lu", „Endlech.lu im Vergleich – Endlech.lu" |

### Beschreibungstexte

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-07 | ✅ bestanden | Drei `<article>` mit „Kurz · rund 25 Wörter", „Mittel · rund 60 Wörter", „Lang · rund 110 Wörter" |
| AK-08 | ✅ bestanden | Gemessen je Sprache: lb 26/63/119 · de 25/62/118 · fr 28/62/119 · en 29/65/117 — alle zwölf in den Grenzen 20–30 / 50–70 / 95–125. Test: `PressCatalogueTest::testDieBeschreibungstexteHaltenIhreWortgrenzenInJederSprache` |
| AK-09 | ✅ bestanden | Browser mit `javaScriptEnabled: false`: 3 Texte sichtbar (197/463/855 Zeichen), **0** `<details>`, 7 Abschnitte |
| AK-10 | ✅ bestanden | `/fr/presse` → „Endlech.lu est la plateforme ouverte de la gastronomie accessible au Luxembourg …" |

### Faktenblatt

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ❌ durchgefallen | Betreiber, Anschrift und Verantwortlicher zeigen alle drei „Wird derzeit ergänzt …" statt einer Angabe → **BUG-01** |
| AK-12 | ✅ bestanden | `/open.json` `restaurants=11 verified=3 communesCovered=8 totalCommunes=100`; Faktenblatt: Kacheln 11 / 3 / 8, Suffix „von 100" — identisch. Neuer Test: `PressFiguresConsistencyTest` |
| AK-13 | ✅ bestanden | `PressFactsTest::testEinNeuesRestaurantErhoehtDieZahl` |
| AK-14 | ✅ bestanden | `PressFactsTest::testDerZweiteAufrufLiestAusDemZwischenspeicher` |
| AK-15 | ⚠️ nicht prüfbar | Es gibt nichts zu vergleichen: `/presse` zeigt den Ausstehend-Hinweis, `/legal` den bisherigen Text „Endlech.lu Luxemburg". `OperatorDetailsTest::testPresseUndImpressumNennenDieselbenBetreiberangaben` überspringt mit genau dieser Begründung und läuft, sobald VB-03 steht |

### Bildmaterial und Paket

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-16 | ❌ durchgefallen | 5 Kacheln stehen, aber **4 Bilder laden nicht**: `404 /presse/endlech-wortbildmarke.svg`, `…-invers.svg`, `…-bildmarke.svg`, `…-bildmarke-invers.svg` → **BUG-02**, Code-Anteil **BUG-03** |
| AK-17 | ⚠️ nicht prüfbar | Es gibt kein Paket. `PressPackageTest::testDerPaketinhaltEntsprichtDerMaterialliste` überspringt mit Verweis auf VB-01 |
| AK-18 | ⚠️ nicht prüfbar | dito — die Liste ist vollständig (`PressRegistryTest::testJedeMaterialartGenauEinmal` grün), die Dateien fehlen |
| AK-19 | ⚠️ nicht prüfbar | Mit angelegtem Paket wäre der Dateiname zu sehen — die Seite bricht dort aber mit 500 ab (**BUG-05**), sodass der Linktext nicht beobachtbar ist |
| AK-20 | ❌ durchgefallen | `a[download]` auf der Seite: **0**. Stattdessen der Ersatzhinweis „Das Paket steht gerade nicht zum Herunterladen bereit …" → **BUG-02** |
| AK-21 | ✅ bestanden | Abschnitt „Nutzungsbedingungen" mit 13 Listenpunkten, getrennt in „Erlaubt" (honorarfrei redaktionell, Skalieren mit Schutzraum, Markenfarben, Standard-/Inversvariante) und „Nicht erlaubt" (Verzerren, Umfärben, abweichende Schreibweisen, vorgetäuschte Partnerschaft) |
| AK-22 | ⚠️ nicht prüfbar | Ohne Paket gibt es keine Bedingungsdatei. `PressPackageTest::testDieNutzungsbedingungenLiegenImPaket` überspringt |

### Person, Zitate und Meldungen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-23 | ✅ bestanden | Porträt vorhanden (lädt, 2048 × 1365), Name „Michael", Funktion „Gründer von Endlech.lu", Kurzvita 267 Zeichen |
| AK-24 | ❌ durchgefallen | Der Bildnachweis lautet „Bildnachweis wird ergänzt – bitte vor der Veröffentlichung beim Pressekontakt erfragen." Er nennt **nicht**, wer das Foto gemacht hat → **BUG-04** |
| AK-25 | ✅ bestanden | Zwei `<figure>` mit Zitat, „Michael, Gründer von Endlech.lu" und dem Hinweis „Diese Zitate sind freigegeben und dürfen ohne Rückfrage verwendet werden." Test: `PressRegistryTest::testMindestensZweiZitateMitNameUndFunktion` |
| AK-26 | ⚠️ nicht prüfbar | Es existiert keine Meldung (OF-06), also weder Datum, Reihenfolge noch Sprachformat beobachtbar. `PressRegistryTest::testMeldungenStehenAbsteigend` läuft gegen eine leere Liste und beweist damit nichts über die Darstellung |
| AK-27 | ✅ bestanden | Abschnitt `#meldungen` steht, 0 Listeneinträge, Überschrift „Noch keine Pressemitteilung", 1 Link auf `#kontakt`. Test: `PressControllerTest::testOhneMeldungStehtDerHinweisStattEinerLeerenListe` |

### Kontakt

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-28 | ✅ bestanden | „E-Mail: support@endlech.lu · Wir antworten in der Regel innerhalb von zwei Werktagen. · Interviewanfragen sind willkommen – auf Luxemburgisch, Deutsch, Französisch oder Englisch." |
| AK-29 | ⚠️ nicht prüfbar | Kein Zugang zum Postfach und keine Möglichkeit, Zustellung von hier aus zu beobachten. Nachweis ist eine Testmail durch den Betreiber (VB-02) |

### Mehrsprachigkeit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-30 | ✅ bestanden | **Mutationsprobe ausgeführt:** `wordmark_dark` aus `press.lb.yaml` entfernt → `tests/Unit/Translation/` **2 Fehlschläge**; Schlüssel wiederhergestellt → 16 Tests grün. Der Prüflauf greift also tatsächlich, auch bei einem datengetrieben aufgerufenen Schlüssel |
| AK-31 | ✅ bestanden | `/lb/presse` durchgelesen: eigenständige luxemburgische Fassung („Endlech.lu ass déi offen Plattform fir barrierefrä Gastronomie zu Lëtzebuerg …"), kein eingesprungener Text; abgesichert durch die Katalogvollständigkeit aus AK-30 |

### Barrierefreiheit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-32 | ✅ bestanden | `/presse` steht in `AccessibilityStructureTest::publicRoutes()`; der Lauf ist grün (32 Tests, 96 Zusicherungen) |
| AK-33 | ✅ bestanden | Bei 320 px in allen vier Sprachen `scrollWidth = clientWidth = 320` — keine waagerechte Scrollleiste |
| AK-34 | ✅ bestanden | Überschriftenfolge im Hauptbereich `1 2 3 3 3 2 2 3 3 3 3 3 3 4 4 2 3 2 2 3 2` — genau eine erste Ebene, kein Sprung. In allen vier Sprachen identisch |
| AK-35 | ✅ bestanden | Alternativtexte: „Wort-Bildmarke, heller Grund", „Wort-Bildmarke, dunkler Grund", „Bildmarke, heller Grund", „Bildmarke, dunkler Grund", „Porträt des Gründers" — Bezeichnungen, keine Dateinamen |

### Datenschutz und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-36 | ✅ bestanden | Gerenderte Seite ohne Auszeichnung nach `tel:`, `+352…` und Datumsmuster durchsucht → **0 Treffer**. Test: `PressControllerTest::testKeineWeiterenAngabenZurPerson` |
| AK-37 | ✅ bestanden | Suche nach `SMA2\|Muskelatrophie\|amyotrophie\|muscular atrophy` über alle vier Kataloge: **genau ein Treffer je Sprache**, immer `person.bio` (Zeile 77/80/77/77). Test: `PressCatalogueTest::testDieGesundheitsangabeStehtNurInDerKurzvita` |
| AK-38 | ✅ bestanden | Netzwerkmitschnitt beim Laden: **keine** Anfrage an einen fremden Host; im Markup keine Ressource mit fremder Herkunft. Test: `PressEdgeCaseTest::testKeineFremdeRessourceWirdGeladen` |
| AK-39 | ✅ bestanden | `?sort=alles&id=4711&x[]=1` liefert byteweise denselben `<main>`; `<script>alert(1)</script>`, `'; drop table user --`, `../../etc/passwd`, 8000 Zeichen und Emoji je **200**, **0** Reflexionen im Hauptbereich |
| AK-40 | ✅ bestanden | `/presse/presse-kit-endlech-lu.zip` → 404 (Datei fehlt), `/de/presse/presse-kit-endlech-lu.zip` → 404 (keine Route). Tests: `PressPackageRoutingTest` (2 Fälle) |

### Abnahme

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-41 | ❌ durchgefallen | **Handprüfung:** Mit dem, was heute auf `/presse` steht, lässt sich kein Beitrag ohne Rückfrage schreiben. Es fehlen der Betreiber (BUG-01), jedes verwendbare Bild (BUG-02) und die Urheberangabe zum Porträt (BUG-04). Beschreibungstext, Zahlen, Zitate und Kontakt wären dagegen ausreichend |
| AK-42 | ✅ bestanden | Alle sieben Abschnitts-`id`s in allen vier Sprachfassungen vorhanden (`boilerplate`, `fakten`, `material`, `person`, `zitate`, `meldungen`, `kontakt`). Test: `PressControllerTest::testAlleSiebenAbschnitteStehenInJederSprache` |
| AK-43 | ✅ bestanden | `<meta name="description">` je Sprache gefüllt, z. B. lb „Pressematerial zu Endlech.lu: fräigi Beschreiwungstexter an dräi Längten …" |
| AK-44 | ✅ bestanden | `<link rel="canonical">` zeigt je Sprachfassung auf sich selbst; vier `hreflang`-Verweise plus `x-default` vorhanden. Test: `PressControllerTest::testKurzbeschreibungUndKanonischeAdresse` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 · ohne JavaScript | ✅ belegt | `javaScriptEnabled: false`: alle 7 Abschnitte, 3 Texte, 0 `<details>` |
| EC-02 · kein Restaurant | ✅ belegt | `PressFactsTest::testKeinBestandErgibtNullUndKeinenFehler` — 0/0/0, `totalCommunes` bleibt 100 |
| EC-03 · leerer Zwischenspeicher | ✅ belegt | `PressFactsTest` ruft `invalidate()` und liest danach; kein Fehler, Zahl wird einmal berechnet |
| EC-04 · Paketdatei fehlt | ✅ belegt | **Der aktuelle Zustand ist der Testfall:** 0 Downloadlinks, stattdessen der Kontakthinweis; kein toter Link. `PressEdgeCaseTest::testOhnePaketStehtDerKontaktwegAnSeinerStelle` |
| EC-05 · Druck | ✅ belegt | `emulateMedia({media:'print'})`: Kopf- und Fußzeile `display:none`, Verlauf entfernt, H1-Farbe `rgb(17,24,39)` — lesbar, nicht weiß auf weiß |
| EC-06 · langer Meldungstitel | ⚠️ offen | Es existiert keine Meldung; die Darstellung ist nicht beobachtbar |
| EC-07 · 320 px | ✅ belegt | vier Sprachen, `scrollWidth = 320` |
| EC-08 · Vorschau ohne Paketeintrag | ⚠️ offen | Ohne Paket lässt sich die Abweichung nicht herstellen |
| EC-09 · Zitat zurückgezogen | ✅ belegt | `PressRegistryTest::testMindestensZweiZitateMitNameUndFunktion` schlägt unter zwei Zitaten fehl |
| EC-10 · Datumsformat auf Luxemburgisch | ⚠️ offen | Ohne Meldung wird `format_date` nie aufgerufen |

## Sicherheitsprüfung

Aktiv angegriffen, nicht gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Es gibt keinen Datensatz mit Eigentümer. `/de/presse/4711` → 404, `/xx/presse` → 404, `/presse/../.env` → 404, `/de/presse%2F..%2F..%2Fetc%2Fpasswd` → 404 |
| Schreibwege | bestanden | `POST`/`PUT`/`DELETE`/`PATCH` auf `/de/presse` → je **405** |
| Rate Limit greift | bestanden (kein Zähler, wie entworfen) | 30 Aufrufe in Folge → 30 × 200; Antwortzeit erste 0,040 s, Mittel der folgenden 0,048 s — der Zwischenspeicher trägt, es entsteht keine Last, die einen Deckel bräuchte |
| Zugriffsregeln serverseitig | bestanden | Keine `access_control`-Zeile nötig und keine ergänzt; die Seite hat keinen Schreibweg und keine Rolle. Kein Voter zu prüfen |
| PII in Logs | bestanden (für dieses Feature) | `var/log/dev.log` geleert, zwei Seitenaufrufe erzeugt: 16 Zeilen, **0** mit E-Mail-Muster. ⚠ Hinweis unten |
| PII an externe Dienste | bestanden | Kein `HttpClient`, kein `curl_`, kein `fsockopen` im Feature-Code (0 Dateien); Netzwerkmitschnitt ohne fremden Host. Es gibt keinen Payload, weil es keinen Empfänger gibt |
| Geheimnisse im Repository | bestanden | Suche nach `sk_live_`, `sk-…`, `xkeysib-`, `BEGIN … PRIVATE KEY` über alle neuen Dateien: keine Treffer. Die neuen Parameter sind leer bzw. tragen die öffentliche Presseadresse |
| Eingaben | bestanden | Fünf Angriffsformen über den Query-String (XSS, SQL, Traversal, 8000 Zeichen, Emoji) → je 200, **0** Reflexionen im `<main>`. Die drei Treffer auf „etc/passwd" lagen ausschließlich in der Symfony-Entwicklungsleiste, die in `prod` nicht existiert |
| Löschen und Auskunft | bestanden (trifft nicht zu) | 0 Entities, 0 Migrationen, 0 Verweise auf `App\Press` in `src/Account/AccountDeleter.php` — es entstehen keine Daten, die zu einem Konto gehören |

⚠ **Hinweis ohne Befundcharakter:** `var/log/test.log` und `dev.log` enthalten aus
früheren Läufen (ältester Eintrag 25.06.2026) Doctrine-DEBUG-Zeilen mit
Fixture-Adressen wie `user@endlech.lu`. Das ist **vorbestehend**, entsteht in `dev`/`test`
und nicht durch dieses Feature; `var/` ist gitignoriert, und `when@prod` schließt den
`doctrine`-Kanal aus dem Puffer ausdrücklich aus. Kein Befund gegen Feature 05 — hier
vermerkt, weil eine Prüfung, die nur bei einem Fund erwähnt wird, von einer nicht
durchgeführten nicht zu unterscheiden ist.

## Fehler

### BUG-01 · Betreiberangaben fehlen auf beiden Seiten — hoch

**Betrifft:** AK-11, AK-15, mittelbar AK-41
**Reproduktion:**
1. `/de/presse` aufrufen, Abschnitt „Fakten" lesen
2. `/de/legal` aufrufen, ersten Abschnitt lesen

**Erwartet:** Beide nennen Name, ladungsfähige Anschrift und presserechtlich
Verantwortlichen, und zwar wortgleich.
**Tatsächlich:** `/presse` zeigt dreimal „Wird derzeit ergänzt — bis dahin erreichen Sie
uns über den Pressekontakt."; `/legal` zeigt unverändert „Endlech.lu / Luxemburg".
**Ort:** `config/services.yaml:40–42` — `app.operator_name`, `app.operator_address`,
`app.operator_responsible` sind leere Zeichenketten.
**Vorschlag:** Die drei Parameter füllen (VB-03). **Kein Codeanteil** — die Mechanik
steht, `OperatorDetailsTest` läuft ab dem Moment automatisch mit.

### BUG-02 · Kein Bildmaterial und kein Paket — hoch

**Betrifft:** AK-16, AK-17, AK-18, AK-19, AK-20, AK-22, mittelbar AK-41
**Reproduktion:**
1. `php bin/console app:press:package` → Exit-Code 1, nennt vier fehlende Dateien
2. `/de/presse` aufrufen, Abschnitt „Bildmaterial" ansehen

**Erwartet:** Fünf Vorschauen, ein Downloadknopf mit Format und Größe.
**Tatsächlich:** Vier der fünf Vorschauen laufen in `404`
(`/presse/endlech-wortbildmarke.svg` und die drei weiteren Marken), kein Downloadlink,
stattdessen der Ersatzhinweis auf den Pressekontakt.
**Ort:** `public/presse/` existiert nicht; `src/Press/PressRegistry.php:assets()` verweist
auf die vier Pfade.
**Vorschlag:** Die vier Vektormarken erzeugen und ablegen (VB-01), dann `make press-kit`.
**Kein Codeanteil** — der Befehl verweigert bewusst ein halbes Paket, und drei Prüfläufe
warten mit benannter Begründung.

### BUG-03 · Eine fehlende Vorschaudatei erzeugt ein Bruchbild statt eines Ersatzes — mittel

**Betrifft:** AK-16
**Reproduktion:** `/de/presse` mit fehlender Datei unter `public/presse/` aufrufen
(aktueller Zustand) — der Browser zeigt vier Bruchbild-Symbole, zwei davon auf dunklem
Grund.
**Erwartet:** Entweder die Vorschau oder ein sichtbarer Ersatz — wie beim Paket, wo der
Kontaktweg an die Stelle des Knopfes tritt.
**Tatsächlich:** Die Kachel wird gerendert, das Bild schlägt fehl, nichts bemerkt es.
**Ort:** `templates/press/_material.html.twig:18–22` — `asset(asset.publicPath)` ohne
Existenzprüfung; `design.md` sieht einen Fehlerzustand nur für `PressPackage` vor.
**Vorschlag:** **Erst ein Kriterium, dann Code** — der Fall steht als **OF-09** in der
Spec. Fünf zusätzliche Dateisystemzugriffe je Aufruf wären der Preis; er ist tragbar, aber
die Entscheidung gehört in die Spec und nicht in einen Nebensatz.

### BUG-04 · Der Fotocredit nennt keinen Urheber — mittel

**Betrifft:** AK-24
**Reproduktion:** `/de/presse`, Abschnitt „Gründer", Text unter dem Porträt lesen
**Erwartet:** Wer das Foto gemacht hat, und unter welchen Bedingungen es verwendet werden
darf.
**Tatsächlich:** „Bildnachweis wird ergänzt – bitte vor der Veröffentlichung beim
Pressekontakt erfragen." Die Nutzungsbedingung steht, die Urheberangabe fehlt.
**Ort:** `translations/press.{lb,de,fr,en}.yaml`, Schlüssel `person.photo_credit`
**Vorschlag:** OF-05 beantworten und den Schlüssel füllen. Das Bild ist bis dahin nicht
freigabefähig — ein Presse-Kit, das ein Foto ohne Urheberangabe ausgibt, verursacht das
Problem beim Abdruckenden.

### BUG-05 · Mit vorhandenem Paket antwortet die Presseseite mit HTTP 500 — kritisch

**Betrifft:** AK-02, AK-19, AK-20, mittelbar die gesamte Seite
**Reproduktion:**
1. Eine Datei unter `public/presse/presse-kit-endlech-lu.zip` anlegen (beliebiger Inhalt —
   im Regelbetrieb erzeugt sie `make press-kit`)
2. `/de/presse` aufrufen — ebenso `/lb/presse`, `/fr/presse`, `/en/presse`
3. Datei wieder entfernen → die Seite antwortet erneut mit 200

**Erwartet:** 200 mit sichtbarem Downloadknopf samt Format und Größe.
**Tatsächlich:** **500** in allen vier Sprachen. Meldung: *„Neither the property
`publicPath` nor one of the methods `publicPath()`, `getpublicPath()`, `ispublicPath()`,
`haspublicPath()` or `__call()` exist and have public access in class
`App\Press\PressPackage`"* — `_material.html.twig` at line 44.
**Ort:** `templates/press/_material.html.twig:44` (`asset(package.publicPath)`) gegen
`src/Press/PressPackage.php:22` — der Pfad ist dort die **Klassenkonstante**
`PUBLIC_PATH`; Twig löst `object.attr` über Eigenschaft und Getter auf, nie über eine
Konstante.
**Vorschlag:** Einen öffentlichen Zugriff ergänzen (`publicPath(): string { return
self::PUBLIC_PATH; }`) und den Regelfall dauerhaft abdecken — `PressDownloadStateTest`
liegt bereits vor und legt sein Paket selbst an.

**✅ Behoben am 2026-08-30.** `PressPackage::publicPath()` ergänzt
(`src/Press/PressPackage.php`). Gegen die Reproduktion oben geprüft: Mit angelegtem Paket
antworten `/lb`, `/de`, `/fr` und `/en` **je 200**, der Downloadknopf steht und liest sich
„Presse-Paket herunterladen (ZIP · 244 kB)"; nach dem Entfernen wieder 200 mit
Ersatzhinweis. `PressDownloadStateTest` ist grün (5 Fälle).

⚠ **Beim Beheben fiel ein Fehler in diesem Prüflauf selbst auf:** Sein `tearDown()` rief
`parent::tearDown()` nicht, wodurch der Kernel zwischen zwei Testmethoden gebootet blieb
(„Booting the kernel before calling createClient() is not supported"). Das sah aus wie ein
zweiter Anwendungsfehler und war keiner — der Aufruf ist ergänzt.

⚠ **Der eigentliche Befund ist die Testlücke dahinter.** `PressEdgeCaseTest` verzweigt an
`PressPackage::exists()` und prüfte in dieser Umgebung nur den Ersatzzweig. Eine
Verzweigung, deren einer Ast nie ausgeführt wird, ist keine Abdeckung — sie ist eine
Abdeckung, die aussieht wie eine. Dasselbe Muster steckt in jeder Verzweigung, die an
einer offenen Vorbedingung hängt.

### BUG-06 · Zusammengesetzte Übersetzungsschlüssel fallen durch beide Prüfläufe — mittel

**Betrifft:** die Konvention „Übersetzungsschlüssel werden getestet, nicht gehofft"
(CLAUDE.md); **nicht** AK-30
**Reproduktion:**
1. `material.allowed_3` aus **allen vier** `translations/press.*.yaml` entfernen
2. `php bin/phpunit --testsuite Unit` → **162 Tests, grün**
3. `/de/presse` aufrufen → im Abschnitt „Erlaubt" steht der rohe Schlüssel
   `material.allowed_3`

**Erwartet:** Ein Prüflauf wird rot, bevor ein roher Schlüssel öffentlich sichtbar wird.
**Tatsächlich:** Beide Netze haben an derselben Stelle ein Loch:
`CatalogueCompletenessTest` erfasst nur **literale** `'…'|trans`-Aufrufe, und
`PressCatalogueTest` sammelt nur, was `PressRegistry` nennt — die acht
`material.allowed_*`/`forbidden_*` und die sechs `facts.*_value` stehen aber ausschließlich
als zusammengesetzter Ausdruck in der Vorlage bzw. im Paketbefehl.
**Ort:** `templates/press/_material.html.twig` (`('material.allowed_' ~ i)|trans`),
`templates/press/_facts.html.twig:60–72`, `src/Command/PressPackageCommand.php:138–144`
gegen `tests/Unit/Translation/PressCatalogueTest.php:129–154`
**Vorschlag:** Die vierzehn Schlüssel in `schluesselAusDemQuelltext()` aufnehmen.
**Heute ist nichts kaputt** — alle vierzehn stehen vollständig in allen vier Katalogen;
der Befund ist die fehlende Absicherung, und CLAUDE.md nennt genau diesen blinden Fleck
schon einmal (BF-56).

**✅ Behoben am 2026-08-30.** Die vierzehn Schlüssel stehen jetzt als
`ZUSAMMENGESETZTE_SCHLUESSEL` in `PressCatalogueTest`. Beide Mutationsproben wiederholt:
`material.allowed_3` aus allen vier Katalogen entfernt → **1 Fehlschlag** (vorher grün);
`facts.status_value` aus allen vier entfernt → **Fehlschlag** mit „1 Schlüssel fehlen in
press.lb.yaml"; nach dem Wiederherstellen jeweils grün. ⚠ Die Liste ist von Hand geführt:
Wer eine Schleifengrenze ändert (`1..4` → `1..5`), trägt den neuen Schlüssel nach — das
steht als Warnung im Prüflauf.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/PressFiguresConsistencyTest.php` | 1 | AK-12 — die **gerenderte** Seite gegen `/open.json`, nicht nur den Dienst gegen den Dienst |
| `tests/Functional/Controller/PressDownloadStateTest.php` | 5 | AK-02/AK-19/AK-20 im **Regelfall**: Der Lauf legt das Paket selbst an und hängt nicht an VB-01. **Absichtlich rot** — Befund-Nachweis zu BUG-05 |

## Nächster Schritt

`/sdd-build 05` mit dem Auftrag, **BUG-05 und BUG-06 zu beheben** — das ist der einzige
Codeanteil, und BUG-05 blockiert alles Weitere: Solange er steht, macht das Ablegen der
Vektormarken die Seite kaputt statt fertig. **Reihenfolge: erst BUG-05, dann VB-01.**

Die übrigen Befunde brauchen kein Programmieren:

- **BUG-01** → die drei Parameter füllen (VB-03)
- **BUG-02** → vier SVG ablegen, `make press-kit` (VB-01) — **erst nach BUG-05**
- **BUG-04** → OF-05 beantworten, einen Katalogschlüssel füllen
- **BUG-03** → braucht zuerst eine Entscheidung zu OF-09, dann Code

Danach erneut `/sdd-qa 05`. Sieben Kriterien werden dann prüfbar: fünf Prüfläufe
überspringen heute mit benannter Begründung und laufen automatisch mit, sobald Material
und Angaben da sind. **AK-29** bleibt eine Betreiberprüfung (Testmail an
`support@endlech.lu`), **AK-26**, **EC-06** und **EC-10** bleiben offen, solange keine
Pressemitteilung existiert (OF-06).

⚠ **Ein Muster für `features/befunde.md`, das über dieses Feature hinausgeht:** BUG-05
lag hinter einer Verzweigung, deren einer Ast wegen einer offenen Vorbedingung nie
ausgeführt wurde — und wurde erst gefunden, als die Prüfung den Zustand **herstellte**,
statt ihn abzuwarten. Wo eine Vorbedingung offen ist, gehört der Zustand dahinter
simuliert, nicht übersprungen.
