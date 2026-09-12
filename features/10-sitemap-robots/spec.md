# 10 · Sitemap und robots.txt — Spezifikation

Status: `planned` · Stand: 2026-09-12

## Zweck

Suchmaschinen bekommen eine vollständige Liste der öffentlichen Seiten in allen vier
Sprachen und klare Regeln, was sie lesen dürfen. Vorher lieferte `/robots.txt` einen
404, eine Sitemap gab es nicht, und nur fünf Templates nannten ihre maßgebliche Adresse
— die Google Search Console für `endlech.lu` hatte damit nichts, womit sie arbeiten
konnte.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B24 · Mehrsprachigkeit | approved (Code live seit `v2026.08.29`) | Die Sprachfassungen und ihre Verweise, auf die jeder Sitemap-Eintrag zeigt |
| B05 · Restaurantsuche | deployed | Die Restaurantliste und ihre Parameter, die nicht in Suchergebnisse sollen |
| B06 · Restaurant-Detailseite | deployed | Der größte Teil der Einträge |
| B13 · Statische Inhaltsseiten | deployed | Feste Seiten |
| 03 · Vergleichsseiten | deployed | Vier feste Seiten |
| 05 · Presse-Kit | deployed | Feste Seite |
| 06 · Community Feedback Board | deployed | Übersichtsseite in der Sitemap, Einreichformular ausgeschlossen |
| 07 · Roadmap und Changelog | deployed | Zwei feste Seiten |
| B14, B15, 08 · Wartelisten | deployed | Zielgruppenseiten in der Sitemap; Bestätigungs-, Abmelde- und Dankeseiten ausgeschlossen |

## User Stories

- **US-01** · Als Gast, der nach „Restaurant X barrierefrei" sucht, möchte ich die
  Detailseite des Restaurants in den Suchergebnissen finden, damit ich vor dem Besuch
  weiß, woran ich bin.
- **US-02** · Als Gemeinde, Unternehmen oder Wirt möchte ich die Seite für meine
  Zielgruppe über eine Suche finden, damit ich nicht erst die Startseite durchsuchen muss.
- **US-03** · Als Betreiber möchte ich in der Google Search Console sehen, welche Seiten
  Google kennt und ob die Regeln fehlerfrei gelesen werden, damit ich Auffindbarkeit
  messen statt vermuten kann.
- **US-04** · Als Betreiber möchte ich, dass Anmelde-, Token- und Formularseiten nicht in
  Suchergebnissen erscheinen, damit dort weder Sackgassen noch Einmal-Links landen.

## Nicht im Scope

- **Einzelne Board-Ideen in der Sitemap.** Entschieden am 2026-09-12. Sie bleiben über
  die Übersicht auffindbar, werden aber nicht aktiv angeboten.
- **Sprechende Adressen für Restaurants** (Name statt Nummer). Siehe OF-02.
- **Die Weiterleitung von `/` auf `/lb/`** bleibt, wie sie ist (302).
- **Strukturierte Daten** (Restaurant-Auszeichnung für Suchergebnisse) — ein eigenes
  Vorhaben, sinnvoll erst mit Daten aus dem Growth-Loop.
- **Der offene Datensatz und `/open.json` als Sitemap-Einträge.** Das sind Dateien, keine
  Seiten; sie bleiben für Crawler erlaubt (AK-19).
- **Aufteilen der Sitemap** bei mehr als 50.000 Einträgen (EC-03).
- **Auswertung der Search Console** — das ist der Growth-Loop, der danach folgt.
- **Die englische Standard-Fehlerseite** bei 404 — sie trägt bereits einen
  Ausschlussvermerk und betrifft ein anderes Feature.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar: mit einem Browser, einem
Kommandozeilen-Abruf, einem Sitemap-Validator, einem Robots-Prüfwerkzeug oder der
Search Console.

### Die festen Seiten

Diese 21 Seiten stehen immer in der Sitemap, jede in vier Sprachen (`lb`, `de`, `fr`,
`en`) — zusammen **84 Einträge**:

| # | Seite | Adresse (mit `{s}` für das Sprachsegment) |
|---|---|---|
| 1 | Startseite | `/{s}/` |
| 2 | Über uns | `/{s}/about` |
| 3 | Kriterien | `/{s}/criteria` |
| 4 | Barrierefreiheitserklärung | `/{s}/accessibility` |
| 5 | Impressum und Datenschutz | `/{s}/legal` |
| 6 | Transparenzseite | `/{s}/open` |
| 7 | Presse | `/{s}/presse` |
| 8 | Roadmap | `/{s}/roadmap` |
| 9 | Changelog | `/{s}/changelog` |
| 10 | Vergleiche · Übersicht | `/{s}/vergleich` |
| 11 | Vergleich Google Maps | `/{s}/vergleich/google-maps` |
| 12 | Vergleich TripAdvisor | `/{s}/vergleich/tripadvisor` |
| 13 | Vergleich Wheelmap | `/{s}/vergleich/wheelmap` |
| 14 | Restaurantliste (ohne Parameter) | `/{s}/restaurants` |
| 15 | Partnerprogramm | `/{s}/partner` |
| 16 | Organisationen · Übersicht | `/{s}/organisationen` |
| 17 | Gemeinden | `/{s}/organisationen/gemeinden` |
| 18 | Unternehmen | `/{s}/organisationen/unternehmen` |
| 19 | Vereine | `/{s}/organisationen/vereine` |
| 20 | App-Warteliste | `/{s}/app` |
| 21 | Community-Ideen · Übersicht | `/{s}/community/ideen` |

⚠ Kommt eine öffentliche Seite hinzu oder fällt eine weg, ändert sich diese Tabelle und
mit ihr die Zahl in AK-02 und AK-10. Wer eine Seite anlegt, ohne hier nachzuziehen,
bekommt einen roten Prüflauf — so ist es gewollt.

### Sitemap

- **AK-01** · Angenommen die Anwendung läuft, wenn `https://endlech.lu/sitemap.xml`
  abgerufen wird, dann antwortet sie mit HTTP 200, und die Datei besteht die Prüfung
  gegen das Sitemap-Schema von sitemaps.org ohne Fehler.
- **AK-02** · Angenommen es gibt *n* öffentlich aufrufbare Restaurants, wenn die Sitemap
  abgerufen wird, dann enthält sie genau **(21 + n) × 4** Einträge: die festen Seiten aus
  der Tabelle oben und jede Restaurant-Detailseite, jeweils in allen vier Sprachen.
  *(Heute auf Produktion mit n = 3: 96 Einträge.)*
- **AK-03** · Angenommen die Sitemap wird abgerufen, wenn man ihre Einträge liest, dann
  beginnt jede Adresse mit `https://endlech.lu/`, trägt direkt danach eines der
  Sprachsegmente `lb`, `de`, `fr` oder `en` und enthält kein `?`.
- **AK-04** · Angenommen die Sitemap wird abgerufen, wenn jede ihrer Adressen einzeln
  abgerufen wird, dann antwortet jede mit HTTP 200 — ohne Weiterleitung und ohne 404.
- **AK-05** · Angenommen ein beliebiger Eintrag der Sitemap wird betrachtet, dann nennt er
  alle vier Sprachfassungen derselben Seite und als Vorgabe für alle übrigen Sprachen die
  luxemburgische Fassung.
- **AK-06** · Angenommen die Sitemap wird abgerufen, dann enthält kein Eintrag eine Adresse
  aus Verwaltung, Profil oder Schnittstelle, keine Anmelde-, Registrier- oder
  Passwortseite, keinen Bestätigungs- oder Abmeldelink, keine Dankeseite, kein
  Einreichformular und keine einzelne Board-Idee.
- **AK-07** · Angenommen die Sitemap wird abgerufen, dann nennt kein Eintrag ein
  Änderungsdatum, eine Priorität oder eine Änderungshäufigkeit.
- **AK-08** · Angenommen ein Admin legt ein neues Restaurant an oder gibt einen Vorschlag
  frei, wenn die Sitemap 60 Minuten später abgerufen wird, dann steht die Detailseite des
  Restaurants in allen vier Sprachen darin.
- **AK-09** · Angenommen ein Admin löscht ein Restaurant, wenn die Sitemap 60 Minuten später
  abgerufen wird, dann steht keine der vier Sprachfassungen seiner Detailseite mehr darin.
- **AK-10** · Angenommen es gibt kein einziges Restaurant, wenn die Sitemap abgerufen wird,
  dann antwortet sie mit HTTP 200 und enthält genau die 84 Einträge der festen Seiten.
- **AK-11** · Angenommen der Restaurantbestand kann nicht gelesen werden und es liegt keine
  gespeicherte Fassung der Sitemap vor, wenn die Sitemap abgerufen wird, dann antwortet
  sie mit einem Serverfehler (HTTP 5xx) — **nicht** mit HTTP 200 und **nicht** mit einer
  leeren oder verkürzten Liste.

### Maßgebliche Adresse und Sprachverweise auf den Seiten

- **AK-12** · Angenommen eine Seite steht in der Sitemap, wenn sie im Browser aufgerufen
  wird, dann nennt ihr Quelltext als maßgebliche Adresse (`rel="canonical"`) genau die
  Adresse, unter der sie in der Sitemap steht.
- **AK-13** · Angenommen die Restaurantliste wird mit Sortier-, Filter- oder
  Seitenparametern aufgerufen, wenn man ihren Quelltext betrachtet, dann nennt sie als
  maßgebliche Adresse die Liste derselben Sprache **ohne Sortier- und Filterparameter**; eine
  Seitenzahl ab 2 bleibt erhalten, Seite 1 und jede ungültige Seitenangabe entfallen:

  | Aufruf | Maßgebliche Adresse |
  |---|---|
  | `/de/restaurants?sort=name` | `https://endlech.lu/de/restaurants` |
  | `/de/restaurants?wheelchair=1&city=Esch` | `https://endlech.lu/de/restaurants` |
  | `/de/restaurants?page=2&sort=name` | `https://endlech.lu/de/restaurants?page=2` |
  | `/de/restaurants?page=1` · `?page=0` · `?page=abc` | `https://endlech.lu/de/restaurants` |

  *(Geändert am 2026-09-12 beim Systemdesign, Entscheidung zu OF-01.)*
- **AK-14** · Angenommen eine Seite steht in der Sitemap, wenn man ihre Sprachverweise
  (`hreflang`) im Quelltext mit ihrem Sitemap-Eintrag vergleicht, dann nennen beide
  dieselben vier Sprachfassungen und dieselbe Vorgabe (`lb`).

### Aus Suchergebnissen heraushalten

- **AK-15** · Angenommen eine der folgenden Seiten wird aufgerufen — Anmelden,
  Registrieren, Passwort vergessen, Passwort zurücksetzen, Bestätigung einer
  E-Mail-Adresse oder eines Wartelisteneintrags, Abmeldelink einer der drei Wartelisten,
  Dankeseite nach dem Einreichen eines Restaurants oder einer Idee, Restaurant vorschlagen,
  Idee einreichen —, wenn man die Antwort mit einem einfachen Abruf ohne Browser betrachtet
  (`curl -I`), dann trägt sie die Kopfzeile `X-Robots-Tag: noindex` — **auch dann, wenn sie
  nur weiterleitet**.
  *(Geändert am 2026-09-12 beim Systemdesign, Entscheidung zu OF-04: Zwei der Wege liefern
  nie eine Seite aus und hätten keinen Quelltext, in dem die Anweisung stehen könnte.)*
- **AK-16** · Angenommen eine Seite aus der Sitemap wird aufgerufen, wenn man ihre Antwort
  und ihren Quelltext betrachtet, dann trägt sie **weder** eine Kopfzeile `X-Robots-Tag` mit
  `noindex` **noch** eine `noindex`-Anweisung im Quelltext.

### robots.txt

- **AK-17** · Angenommen die Anwendung läuft, wenn `https://endlech.lu/robots.txt`
  abgerufen wird, dann antwortet sie mit HTTP 200 als Klartext und nennt
  `https://endlech.lu/sitemap.xml` als Sitemap.
- **AK-18** · Angenommen die ausgelieferte robots.txt wird mit einem Robots-Prüfwerkzeug
  gelesen, wenn man Adressen der Verwaltung (`/de/admin`), des Profils (`/de/profile`)
  und der Schnittstelle (`/api/v1/restaurants`, `/de/api/cuisines/search`) in jeder der
  vier Sprachen prüft, dann ist jede davon für alle Crawler gesperrt.
- **AK-19** · Angenommen die robots.txt wird so geprüft, wenn man jede Adresse aus der
  Sitemap sowie `/open.json`, `/open/dataset.csv` und `/open/dataset.json` prüft, dann ist
  jede davon erlaubt.
- **AK-20** · Angenommen die robots.txt wird so geprüft, wenn man die Seiten aus AK-15
  prüft, dann ist jede davon **erlaubt**.
  *(Eine Sperre verhinderte, dass Suchmaschinen deren Ausschlussanweisung überhaupt lesen
  — die Adresse könnte dann ohne Inhalt in den Ergebnissen erscheinen.)*
- **AK-21** · Angenommen die ausgelieferte robots.txt wird gelesen, dann enthält sie keine
  Regel, die die gesamte Seite für einen Crawler sperrt.

### Datenschutz und Missbrauchsschutz

- **AK-22** · Angenommen die Sitemap wird abgerufen, wenn man alle Einträge durchsucht, dann
  enthält keine Adresse ein `@`, einen Token oder einen Pfadabschnitt aus einem Bestätigungs-,
  Abmelde- oder Zurücksetzen-Link.
- **AK-23** · Angenommen dieselbe Adresse hat die Sitemap innerhalb einer Stunde 60-mal
  abgerufen, wenn sie es ein 61. Mal tut, dann antwortet die Anwendung mit HTTP 429 und nennt,
  nach wie vielen Sekunden der nächste Abruf möglich ist; die ersten 60 Abrufe liefern HTTP 200.

**Katalog `~/.claude/sdd/sicherheit.md`, die Fragen ohne eigenes Kriterium:**

| Frage | Antwort |
|---|---|
| Personenbezogene Daten | **Trifft nicht zu**, weil Sitemap und robots.txt nur Adressen öffentlicher Seiten enthalten. Restaurants erscheinen über ihre Nummer, nicht ihren Namen. Besondere Kategorien: keine — Barrierefreiheitsmerkmale beschreiben Lokale, keine Personen. Abgesichert durch AK-06 und AK-22 |
| Logs | **Trifft nicht zu**, weil kein neuer Weg Eingaben oder Personendaten verarbeitet |
| Externe Dienste | **Trifft nicht zu**, weil die Anwendung nichts überträgt: Google holt sich öffentliche Seiten selbst. Kein Auftragsverarbeitungsvertrag, kein Eintrag in `docs/datenschutz.md` als Verarbeiter |
| Zugriff, fremde ID | **Trifft nicht zu**, weil beide Dateien bewusst öffentlich sind und keine Datensätze einzeln ausliefern. Was nicht hineingehört, regeln AK-06 und AK-22. ⚠ Eine robots.txt-Sperre ist **keine** Zugriffsregel: Verwaltung und Profil schützt weiterhin die Anmeldung |
| Rate Limit | Sitemap: AK-23 (lädt bei abgelaufener Fassung den Bestand, Projektkonvention). robots.txt: **trifft nicht zu**, weil sie keinen Bestand lädt |
| Kosten | **Trifft nicht zu**, weil kein kostenpflichtiger Dienst aufgerufen wird |
| Uploads | **Trifft nicht zu** |
| Löschen und Auskunft | **Trifft nicht zu**, weil keine Kontodaten enthalten sind. Ein gelöschtes Restaurant fällt nach AK-09 heraus |
| Geheimnisse | **Trifft nicht zu**, weil kein Schlüssel nötig ist. Die Search Console ist über DNS bestätigt (Domain-Property) — keine Prüfdatei, kein Meta-Tag im Repository |

### Plattformnachweise (Google Search Console)

- **AK-24** · Angenommen die Sitemap ist auf Produktion ausgeliefert, wenn der Betreiber sie
  in der Search Console für `sc-domain:endlech.lu` einreicht, dann zeigt der Bericht den
  Status „Erfolgreich" und als erkannte Seiten die Zahl aus AK-02.
  *(Plattformnachweis — Screenshot in `qa-report.md`.)*
- **AK-25** · Angenommen die robots.txt ist auf Produktion ausgeliefert, wenn der Betreiber in
  der Search Console unter Einstellungen den robots.txt-Bericht öffnet, dann ist
  `https://endlech.lu/robots.txt` als abgerufen aufgeführt, ohne Fehler und ohne Warnung.
  *(Plattformnachweis — Screenshot in `qa-report.md`.)*

## Edge Cases

- **EC-01** · Die Datenbank ist kurz weg, aber eine gespeicherte Fassung der Sitemap liegt
  vor → sie wird ausgeliefert. Ein bis zu einer Stunde alter, **vollständiger** Stand ist
  richtiger als ein Fehler; nur ein halber Stand wäre falsch (AK-11).
- **EC-02** · Ein Restaurant wird gelöscht, während Google die Sitemap noch in der alten
  Fassung hat → die Detailseite antwortet mit 404, bis die Sitemap nachzieht (höchstens
  60 Minuten, AK-09). Google verkraftet das; es ist kein Fehler.
- **EC-03** · Die Sitemap fasst höchstens 50.000 Einträge. Mit vier Sprachen reicht das bis
  zu 12.479 Restaurants — Luxemburg hat deutlich weniger. Wird die Grenze erreichbar,
  wird die Sitemap aufgeteilt; das ist nicht Teil dieses Features.
- **EC-04** · Ein Crawler hinter einer gemeinsamen Adresse erreicht den Deckel → HTTP 429
  mit Wartezeit; Suchmaschinen versuchen es später erneut. Bei 60 Abrufen je Stunde tritt
  das bei keinem bekannten Crawler auf.
- **EC-05** · Die Restaurantliste hat mehrere Seiten (ab dem siebten Restaurant) → jede
  Folgeseite nennt sich selbst mit ihrer Seitenzahl als maßgeblich (AK-13), wie Google es
  empfiehlt. In der Sitemap stehen die Folgeseiten trotzdem nicht; jede Detailseite steht
  dort selbst. Eine Seitenzahl jenseits der letzten Seite nennt sich ebenfalls selbst —
  unschädlich, weil nichts auf sie verlinkt.
- **EC-06** · Jemand ruft `http://endlech.lu/sitemap.xml` oder eine Variante mit `www` auf →
  alle Adressen in der Sitemap lauten trotzdem auf `https://endlech.lu/` (AK-03).

## Offene Fragen

- ~~**OF-01**~~ · **Entschieden am 2026-09-12:** Seitenzahl bleibt im canonical-Verweis, Sortier- und Filterparameter entfallen — AK-13 ist entsprechend gefasst. Ursprüngliche Frage: Google rät davon ab, Folgeseiten einer Liste auf Seite 1 zu verweisen. Entschieden
  ist es trotzdem so (AK-13), weil jede Detailseite in der Sitemap steht. Meldet die Search
  Console später „Duplikat — Google hat eine andere kanonische Seite gewählt" für
  Seitenvarianten, wird neu entschieden. — Betreiber, im Growth-Loop.
  ⚠ **Beim Systemdesign am 2026-09-12 mit Primärquelle nachgeschärft:** *„Don't use the first
  page of a paginated sequence as the canonical page. Instead, give each page its own
  canonical URL."* (developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading,
  Stand 2025-12-10). Vorschlag an den Betreiber: AK-13 so fassen, dass **Sortier- und
  Filterparameter** entfallen, der **Seitenparameter** aber bleibt (`?page=2&sort=name` →
  `…/restaurants?page=2`). Kostet im Entwurf nichts. — Betreiber, vor `/sdd-tasks`.
- **OF-02** · Restaurant-Detailseiten haben eine Nummer statt eines Namens in der Adresse
  (`/de/restaurants/17`). Sprechende Adressen helfen der Suche, sind aber eine Änderung an
  B06 mit Weiterleitungen für alle bestehenden Links. — Betreiber, sobald der Growth-Loop
  Daten liefert.
- **OF-03** · **`https://www.endlech.lu` liefert alle Seiten mit HTTP 200**, ohne Weiterleitung auf
  `https://endlech.lu` (am 2026-09-12 beim Systemdesign gemessen). Jede Seite existiert damit
  unter zwei Adressen. Dieses Feature entschärft das — Sitemap, canonical-Verweis und
  Sprachverweise nennen immer `https://endlech.lu` —, beseitigt es aber nicht. Die saubere
  Lösung ist eine dauerhafte Weiterleitung von `www` auf die Hauptadresse, und die gehört
  entweder in Coolify oder in die Anwendung. — Betreiber; eigenes Vorhaben, nicht Teil von 10.
- ~~**OF-04**~~ · **Entschieden am 2026-09-12:** Prüfung über die Antwortkopfzeile `X-Robots-Tag: noindex` — AK-15 und AK-16 sind entsprechend gefasst. Ursprüngliche Frage: **AK-15 nennt den „Quelltext" — zwei der Wege haben keinen.** Die Bestätigung
  einer E-Mail-Adresse und die eines Adresswechsels leiten nur weiter (HTTP 302), eine Seite
  wird dort nie ausgeliefert. Vorschlag an den Betreiber: AK-15 so fassen, dass die Anweisung
  **als Antwortkopfzeile `X-Robots-Tag: noindex`** geprüft wird — die wirkt laut Google für jede
  Antwort, auch für Weiterleitungen und Nicht-HTML, und ist mit einem einfachen Abruf ohne
  Browser nachprüfbar. — Betreiber, vor `/sdd-tasks`.
- **OF-05** · **Die Hinweisseite „Bitte bestätige deine E-Mail-Adresse" (`/{s}/verify`) und das
  erneute Senden (`/{s}/verify/resend`) stehen nicht in der Liste von AK-15.** Beim Bau am
  2026-09-12 aufgefallen, als jede öffentliche Route einer Klasse zugeordnet werden musste. Beide
  sind Sackgassen wie die Dankeseiten und gehörten wohl ausgeschlossen. Bis zur Entscheidung stehen
  sie als „bewusst weder angeboten noch ausgeschlossen" im Seitenverzeichnis — nicht in der
  Sitemap, ohne Ausschlussvermerk. — Betreiber.
- **OF-06** · **Die Beispielzeile `?page=abc` in AK-13 beschreibt eine Seite, die nie entsteht.**
  Beim Bau am 2026-09-12 gemessen: Die Restaurantliste antwortet auf eine nicht-numerische
  Seitenzahl schon heute mit **HTTP 400** (B05) — es gibt keine Seite, die einen canonical-Verweis
  tragen könnte. Die Abbildungsregel greift trotzdem (geprüft im Adressbildner), nur ist sie über
  die Seite nicht beobachtbar. `?page=0` und `?page=1` liefern dagegen 200 und verhalten sich wie
  beschrieben. Vorschlag: `?page=abc` aus der Beispielzeile streichen oder als „antwortet mit 400,
  ohne Verweis" führen. — Betreiber, bei der QA.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Welche Seitenarten in die Sitemap? | Inhaltsseiten, Restaurant-Detailseiten, Zielgruppenseiten; **keine** einzelnen Board-Ideen | Board-Ideen sind Nutzertexte mit wenig Bestand; über die Übersicht bleiben sie auffindbar |
| 2 | Welche Restaurants? | Alle öffentlich sichtbaren, nicht nur verifizierte | Weglassen versteckt nichts vor Google — die Liste verlinkt ohnehin —, es verzögert nur das Finden |
| 3 | Was aus den Suchergebnissen? | Anmelde-/Passwortseiten, Bestätigungs-, Abmelde- und Dankeseiten, Einreichformulare, Parametervarianten der Restaurantliste | Sackgassen für Suchende; Token-Adressen dürfen nie in einen Index |
| 4 | canonical-Verweise mit hinein? | Ja, auf allen Seiten der Sitemap | Sitemap und Seite müssen dieselbe Adresse nennen, sonst wählt Google selbst |
| 5 | Wie aktuell? | Binnen einer Stunde | Google liest Sitemaps nur alle paar Stunden; eine Stunde erlaubt Zwischenspeichern |
| 6 | Board-Übersicht? | Rein; Ideen draußen | Feste, verlinkte Seite ohne Nutzertext im Adressteil |
| 7 | x-default? | Luxemburgisch, wie bisher | Deckt sich mit den Seiten und der Weiterleitung von `/` |
| 8 | Deckel für die Sitemap? | 60 je Stunde je Adresse | Projektkonvention für Wege, die den Bestand laden; dieselbe Zahl wie `/open/dataset`; kein Crawler erreicht sie |
| 9 | robots.txt: Bereiche sperren? | Verwaltung, Profil, Schnittstelle gesperrt; offener Datensatz ausdrücklich erlaubt | Verrät nichts — das Repository ist öffentlich; der Datensatz steht unter CC BY und soll gefunden werden |
| 10 | Seiten mit Ausschlussvermerk auch in robots.txt sperren? | **Nein** (AK-20) | Eine Sperre machte den Vermerk unlesbar; die Adresse könnte dann ohne Inhalt indexiert werden |
| 11 | Änderungsdatum, Priorität, Häufigkeit in der Sitemap? | Keines davon (AK-07) | Restaurants führen kein Änderungsdatum; ein erfundenes wäre falsch, und Priorität und Häufigkeit wertet Google nicht aus |
| 12 | Randfälle als Kriterien | Fehler statt halber Liste, nie „alles sperren", leerer Bestand; Obergrenze als Randfall | Eine leere Sitemap sagt Google „alles weg" — der teuerste, weil lautlose Fehler |
| 13 | Abnahme | Search Console: Sitemap „Erfolgreich" mit der erwarteten Zahl; robots.txt-Bericht ohne Fehler | Die Aussage, auf die es ankommt, trifft Google, nicht der Code |
| 14 | `/` in die Sitemap? | Nein; die vier Startseiten mit Sprachsegment stehen darin | `/` ist eine Weiterleitung, keine Seite |
| 15 | Folgeseiten der Restaurantliste: canonical auf Seite 1 oder auf sich selbst? (OF-01, beim Systemdesign) | Auf sich selbst mit Seitenzahl; Sortier- und Filterparameter entfallen | Wörtliche Empfehlung von Google; kostet im Entwurf eine Zeile |
| 16 | Ausschluss im Quelltext oder als Kopfzeile? (OF-04, beim Systemdesign) | Kopfzeile `X-Robots-Tag: noindex` auf allen 16 Wegen | Zwei Wege sind reine Weiterleitungen ohne Quelltext; eine Stelle statt sechzehn; laut Google gleichwertig |
