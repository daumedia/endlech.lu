# 05 · Presse-Kit — Systemdesign

Status: `architected` · Stand: 2026-08-30 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Eine neue öffentliche Seite, die **nichts speichert**: `/presse`. Es entsteht keine
Entität, keine Tabelle, keine Migration. Der Aufbau folgt Feature `03` — die
**Struktur** (welche Datei im Paket liegt, welches Zitat von wem stammt, welche Meldung
wann erschien) liegt als unveränderliche Datenstruktur im neuen Namensraum `App\Press\`,
die **Texte** liegen in einer eigenen Übersetzungsdomain `press`.

Drei Dinge kommen von außerhalb des Quelltexts und sind der eigentliche Kern des
Entwurfs:

1. **Die Zahlen** stammen aus derselben Quelle wie `/open` (`OpenStatsService::platform()`,
   Zwischenspeicher `cache.open_stats`). Es entsteht kein zweiter Rechenweg.
2. **Die Betreiberangaben** stehen als Parameter in `config/services.yaml` und werden von
   `/presse` **und** `/legal` aus derselben Stelle gelesen. Zwei Textfassungen derselben
   Anschrift würden auseinanderlaufen, ohne dass es jemandem auffällt.
3. **Das Materialpaket** ist eine fertige Datei im Repository, gebaut von einem
   Konsolenbefehl aus derselben Liste, aus der die Seite ihre Vorschauen erzeugt. Der
   Befehl läuft von Hand, nicht im Request und nicht im Deploy; ein Prüflauf vergleicht
   den Inhalt der committeten Datei mit der Liste.

Angefasst werden fünf bestehende Dateien: die App-Hülle (ein Fußzeilen-Link), die
Über-Seite (Verweisblock), die Routenkonfiguration (sprachfreier Kurzlink), das Impressum
(Betreiberangaben aus dem Parameter) und zwei Prüfläufe (Routenliste,
Katalogvollständigkeit). Dazu **eine Zeile in `.github/workflows/ci.yml`** — siehe
Prüfläufe.

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_press_index` | `/{_locale}/presse` | die Presseseite mit allen sieben Abschnitten | öffentlich |
| `app_press_redirect` | `/presse` (sprachfrei) | leitet auf `app_press_index` in der Vorgabesprache | öffentlich |
| — | `/presse/presse-kit-endlech-lu.zip` | das Materialpaket | öffentlich, **statische Datei** — keine Route, kein Controller |

Der Locale-Präfix entsteht automatisch über den `controllers`-Loader in
`config/routes.yaml`. Der sprachfreie Kurzlink wird dort als `RedirectController`
eingetragen, wie `app_open_redirect` und `app_comparison_redirect` — dieselbe Begründung:
Die kurze Adresse steht auf Visitenkarten und in Mails an Redaktionen und darf nicht an
einer Sprachwahl scheitern.

**Der Pfad ist in allen vier Sprachen `presse`**, wie `/vergleich` und
`/organisationen`. Die Route trägt keinen Parameter, damit ist sie für den
Sprachumschalter und die `hreflang`-Schleife in `base.html.twig:29–36` trivial auflösbar.

**Kein Ziel nach einer Handlung**, weil es keine Handlung gibt: Die Seite nimmt nichts
entgegen. Die weiterführenden Wege sind der Mailto-Link auf die Presseadresse, der
Download und der Verweis auf `/open`.

⚠ **Die Paketdatei liegt unter `public/presse/` und wird vom Webserver direkt
ausgeliefert.** Der Front-Controller sieht sie nie: `public/.htaccess` leitet nur
Anfragen an `index.php` weiter, für die **keine** Datei existiert. Das ist die
technische Entsprechung von AK-40 — es gibt nichts zu deckeln, weil nichts gerechnet
wird.

## Komponentenstruktur

```
Presseseite /presse
├── Dokumentkopf                  füllt die leeren Blöcke `meta_description` und `canonical` aus base.html.twig
├── Kopfband                      dunkles Verlaufsband, H1, Einordnungssatz, Sprungmarken auf die Abschnitte
├── #boilerplate                  „Über Endlech.lu"
│   └── Textkarte × 3             Längenangabe als Überschrift, Text offen im Markup (kein Aufklappen)
├── #fakten                       Faktenblatt
│   ├── Kennzahlenzeile           erfasste Lokale, davon verifiziert, Gemeindeabdeckung — live
│   └── Stammdatenliste           Betreiber, Anschrift, Verantwortlicher, Gründung, Status, Lizenzen, Sprachen
├── #material                     Bildmaterial
│   ├── Vorschaukachel × 5        Bild, Bezeichnung der Variante, Dateiname, Format
│   ├── Download-Knopf            ein Paket; Linktext nennt Format und Größe
│   └── Nutzungsbedingungen       erlaubt / nicht erlaubt, zwei Listen
├── #person                       Gründer
│   ├── Porträt                   mit Fotocredit und Nutzungshinweis darunter
│   └── Kurzvita                  Motivation, enthält die Angabe zur Behinderung — genau hier und sonst nirgends
├── #zitate                       freigegebene Zitate
│   └── Zitatkarte × n            Zitat, Name, Funktion, Freigabehinweis
├── #meldungen                    Pressemitteilungen
│   └── Meldungseintrag × n       Datum, Titel, Text — neueste zuerst
└── #kontakt                      Pressekontakt
    └── Kontaktkarte              Mailto, zugesagte Antwortzeit, Hinweis auf Interviewanfragen

Über-Seite /about  (bestehende Seite)
└── #presse-teaser                neuer Abschnitt am Ende: drei Zeilen und ein Link auf /presse
```

Wiederverwendet, nicht neu gebaut: das dunkle Kopfband der Geschäfts- und Datenseiten
(`from-cyan-700 to-purple-800`, als `<section>` wegen der Druckregel), die Kartenkette
und die Schaltflächenkette aus `docs/design-system.md`, der Stat-Kachel-Aufbau von
`/open` für die drei Livezahlen.

**Das Faktenblatt ist eine Beschreibungsliste, keine Tabelle.** Eine Tabelle bräuchte bei
320 px einen eigenen Scrollbereich mit Fokusfalle (das Muster aus
`templates/partner/index.html.twig:86–135`); eine zweispaltige Liste, die bei schmaler
Breite untereinander bricht, braucht das nicht. Das erledigt AK-33 strukturell statt
durch Nachbesserung.

**Vier Zustände je Bildschirm:**

| Zustand | Presseseite |
|---|---|
| leer | **tritt an einer Stelle wirklich auf:** keine Pressemitteilung. Der Abschnitt bleibt stehen und zeigt den Hinweis plus Verweis auf den Pressekontakt (AK-27). Boilerplate, Fakten, Material, Person und Zitate können nicht leer sein — sie stehen fest im Quelltext, und die Prüfläufe erzwingen Mindestzahlen |
| ladend | tritt nicht auf — serverseitig gerendert, kein Nachladen, kein Skript |
| Fehler | (a) Zwischenspeicher leer → die Zahlen werden einmal berechnet, kein sichtbarer Unterschied. (b) **Paketdatei fehlt** → die Seite rendert vollständig, an der Stelle des Download-Knopfes steht der Hinweis auf den Pressekontakt. Kein toter Link, keine nackte Fehlerseite (EC-04) |
| gefüllt | alle sieben Abschnitte |

Der Fehlerfall (b) ist der Grund, warum die Paketgröße **zur Laufzeit von der Datei
gelesen** wird und nicht im Katalog steht: Wer die Größe kennt, weiß auch, ob die Datei
da ist. Eine Zahl im Übersetzungskatalog wäre beides nicht — sie veraltete still und
sagte nichts über die Existenz.

## Datenmodell

**Es entsteht keine Datenbanktabelle und keine Migration.** Alle vorhandenen Entitäten
bleiben unverändert; `docs/data-model.md` braucht keinen Eintrag — das ist beim Nachziehen
ausdrücklich zu vermerken, damit niemand eine fehlende Tabelle sucht.

Was folgt, ist die Feldebene der unveränderlichen Datenstrukturen im Quelltext. Sie
ersetzt an dieser Stelle das Schema.

### `App\Press\BoilerplateLength` (Aufzählung)

| Fall | Textschlüssel | Wortzahl min | Wortzahl max |
|---|---|---|---|
| `SHORT` | `boilerplate.short` | 20 | 30 |
| `MEDIUM` | `boilerplate.medium` | 50 | 70 |
| `LONG` | `boilerplate.long` | 95 | 125 |

Die Grenzen stehen **im Enum, nicht im Prüflauf**. Sie sind die Zusage aus AK-08; ein
Prüflauf, der seine eigenen Zahlen mitbringt, prüft gegen sich selbst.

### `App\Press\PressAssetKind` (Aufzählung)

`WORDMARK_LIGHT` · `WORDMARK_DARK` · `SYMBOL_LIGHT` · `SYMBOL_DARK` · `PORTRAIT`

Fünf Fälle, und der Prüflauf verlangt, dass jeder genau einmal belegt ist. Damit kann
das Paket nicht stillschweigend ohne Dunkelvariante ausgeliefert werden — der Fall, den
AK-18 abdeckt.

### `App\Press\PressAsset` (unveränderliches Wertobjekt)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `kind` | `PressAssetKind` | ja | welche Variante |
| `publicPath` | `string` | ja | Pfad unterhalb von `public/`, zugleich Quelle der Vorschau und Eintrag im Paket |
| `labelKey` | `string` | ja | Bezeichnung der Variante — trägt zugleich den Alternativtext (AK-35) |
| `format` | `string` | ja | „SVG", „JPG" — im Klartext neben der Vorschau |
| `onDark` | `bool` | ja | ob die Vorschaukachel dunkel hinterlegt wird; eine helle Marke auf hellem Grund wäre unsichtbar |
| `creditKey` | `?string` | nein | Fotocredit; **Pflicht, sobald `kind` = `PORTRAIT`** (AK-24) |

`publicPath` zeigt für die vier Marken auf `presse/…`, für das Porträt auf
`uploads/team/michael.jpg`. **Es wird keine zweite Kopie des Porträts angelegt:** Die
vorhandene Datei ist 2048 × 1365 px groß und damit in Druckauflösung brauchbar (rund
17 cm bei 300 dpi). Eine Kopie unter `public/presse/` wäre eine zweite Wahrheit, die
beim nächsten Bildwechsel auseinanderfällt.

⚠ **`public/uploads/team/` ist per `!`-Regel aus `.gitignore` ausgenommen, und die Datei
ist committet.** Wäre sie es nicht, löschte `git clean -fd` im Deploy sie weg — und das
Paket enthielte auf Produktion ein Porträt, das die Seite nicht anzeigen kann.

### `App\Press\PressQuote` (unveränderliches Wertobjekt)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `textKey` | `string` | ja | Wortlaut des Zitats |
| `personName` | `string` | ja | Name — fester Text, **nicht** übersetzt |
| `roleKey` | `string` | ja | Funktion der Person |

Der Name steht wie die Wortmarken in Feature 03 fest im Quelltext: Eigennamen werden
nicht übersetzt, und ein übersetzbarer Name lädt zu einer Schreibweise ein, die in einer
Sprachfassung falsch ist und die niemand bemerkt.

### `App\Press\PressRelease` (unveränderliches Wertobjekt)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `date` | `DateTimeImmutable` | ja | Erscheinungstag — als Datum ausgegeben, nicht als Zeitpunkt |
| `titleKey` | `string` | ja | Überschrift der Meldung |
| `bodyKey` | `string` | ja | Text der Meldung |

Die Ausgabe läuft über `format_date` (`twig/intl-extra`, im Projekt vorhanden), nicht
über ein festes Format — sonst stünde in der luxemburgischen Fassung ein deutsches Datum
(EC-10).

### `App\Press\PressRegistry` (Dienst)

Die **einzige** Stelle, an der Inhalte stehen: `boilerplates()`, `assets()`, `quotes()`,
`releases()` (absteigend nach Datum sortiert). Kein Zustand, keine Datenbank. Sie ist
zugleich die Liste, aus der der Paketbefehl arbeitet — Seite und Paket können deshalb
nicht auseinanderlaufen, ohne dass es einen zweiten Ort gäbe, an dem jemand hätte
vergessen können.

### `App\Press\PressFacts` (Dienst)

Liest `OpenStatsService::platform()` und gibt die drei veröffentlichten Zahlen zurück:
erfasste Lokale, davon verifiziert, abgedeckte Gemeinden von 100.

⚠ **Ruft ausschließlich `platform()`, nicht `all()`.** `all()` berechnet zusätzlich
Wirkung und Finanzen; keine dieser Zahlen erscheint auf der Presseseite, und bei leerem
Zwischenspeicher zahlte der Besucher trotzdem dafür. Dieselbe Begründung wie bei
`ComparisonFigures`.

### `App\Press\PressPackage` (Dienst)

| Feld | Typ | Bedeutung |
|---|---|---|
| `publicPath` | `string` | `presse/presse-kit-endlech-lu.zip` |
| `exists` | `bool` | ob die Datei auf der Platte liegt — trägt den Fehlerzustand |
| `sizeBytes` | `int` | Größe für den Linktext (AK-20) |

Ein einziger Dateisystemzugriff je Aufruf der Seite. Er ersetzt eine Zahl im Katalog,
die niemand pflegt, und deckt zugleich EC-04 ab.

### Betreiberangaben als Parameter

Vier neue Parameter in `config/services.yaml`, im Muster von `app.accessibility.*`
(Feature 02) und `app.contact_email`:

| Parameter | Bedeutung |
|---|---|
| `app.operator_name` | Name des Betreibers |
| `app.operator_address` | Anschrift, mehrzeilig |
| `app.operator_responsible` | presserechtlich Verantwortlicher |
| `app.press_email` | Presseadresse, aus `PRESS_EMAIL` mit Rückfall auf `support@endlech.lu` |

Gerendert werden sie an **zwei** Stellen: im Faktenblatt auf `/presse` und im Abschnitt
`legal.info_title` des Impressums. Genau das ist AK-15 — und der Grund, warum die Angaben
kein Übersetzungsschlüssel sind: Vier Kataloge mit derselben Anschrift bedeuten vier
Stellen, an denen eine Änderung vergessen werden kann, und der Katalogtest würde es nicht
merken (er prüft Vollständigkeit, nicht Gleichheit).

⚠ **Damit steht die Anschrift im öffentlichen Repository.** Sie steht ohnehin auf der
Seite; der Unterschied ist, dass die Git-Historie sie behält, auch wenn OF-04 später
anders entschieden wird. Der Rückweg ist ein Wechsel auf eine Umgebungsvariable — er
löscht die Historie nicht. Das ist die Konsequenz von Entscheidung 9 der Spec und gehört
hier benannt, nicht entdeckt.

### Dateien im Verzeichnis `public/presse/`

| Datei | Herkunft |
|---|---|
| `endlech-wortbildmarke.svg` | VB-01, Gestaltungsarbeit |
| `endlech-wortbildmarke-invers.svg` | VB-01 |
| `endlech-bildmarke.svg` | VB-01 |
| `endlech-bildmarke-invers.svg` | VB-01 |
| `presse-kit-endlech-lu.zip` | erzeugt von `app:press:package`, committet |

Alles committet — `git clean -fd` im Deploy läuft ohne `-x` und fasst getrackte Dateien
nicht an, aber untracked Dateien unterhalb von `public/` verschwinden.

### Änderungen an bestehenden Dateien

| Datei | Änderung |
|---|---|
| `templates/base.html.twig` | ein elfter Eintrag in Fußzeilenspalte 2: „Presse" → `app_press_index` |
| `templates/about/index.html.twig` | neuer Abschnitt `#presse-teaser` am Ende, nach der Mission |
| `templates/impressum/index.html.twig` | `legal.info_text` wird durch die vier Parameter ersetzt; der Katalogschlüssel entfällt in allen vier Sprachen |
| `config/routes.yaml` | Block `app_press_redirect` nach dem Muster von `app_open_redirect` |
| `config/services.yaml` | vier neue Parameter (oben) |
| `.env` | `PRESS_EMAIL=support@endlech.lu` als dokumentierter Vorgabewert |
| `translations/press.{lb,de,fr,en}.yaml` | **neu** — eigene Domain, keine Konfiguration nötig |
| `translations/messages.{lb,de,fr,en}.yaml` | zwei neue Schlüssel (Fußzeilen-Link, Teaser-Überschrift), ein entfallender (`legal.info_text`) |
| `tests/Functional/AccessibilityStructureTest.php` | `/presse` in `publicRoutes()` |
| `tests/Unit/Translation/CatalogueCompletenessTest.php` | Domain `press` in `DOMAINS` |
| `.github/workflows/ci.yml` | Erweiterung `zip` in der Extension-Liste — siehe Prüfläufe |
| `composer.json` | `ext-zip` in `require-dev` |
| `Makefile` | Ziel `press-kit` → ruft `app:press:package` |
| `public/sw.js` | **keine Änderung, aber eine Regel** — siehe unten |
| `docs/app-shell.md` | Fußzeilenabschnitt: elf Einträge in Spalte 2 statt zehn |
| `docs/data-model.md` | ein Satz: Feature 05 bringt keine Entität |

⚠ **Der Service Worker cacht Bilder „cache-first" (`public/sw.js:87–93`).** Wer eine
Logo-Datei unter demselben Namen ersetzt, zeigt wiederkehrenden Besuchern die alte
Fassung — während das Paket, das nicht gecacht wird (es ist kein `image` und liegt nicht
unter `/build/` oder `/icons/`), bereits die neue enthält. Das wäre ein Bruch von AK-17,
den kein Prüflauf sieht, weil er im Browser des Besuchers passiert. **Regel: Wer eine
Datei in `public/presse/` ersetzt, erhöht `CACHE_VERSION` in `public/sw.js`** — dieselbe
Konvention, die dort ohnehin für die App-Hülle gilt.

**Beziehungen und Löschregeln:** entfallen. Es gibt keinen Fremdschlüssel, keinen
Datensatz, der zu einem Nutzerkonto gehört, und beim Löschen eines Kontos ist an diesem
Feature nichts zu tun (Sicherheitskatalog 5, in der Spec als *trifft nicht zu* geführt).

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| jeder, auch nicht angemeldet | die Seite und das Paket vollständig | nichts | — es gibt keinen Schreibweg |
| Redaktion (Betreiber) | dasselbe | Inhalte über einen Commit im Quelltext, Paket über `app:press:package` | Schreibrecht am Repository, Auslieferung über den Deploy-Branch |

**Die Seite ist öffentlich, weil keine `access_control`-Regel auf sie passt — nicht, weil
eine Regel sie freigibt.** In `config/packages/security.yaml` gibt es keinen Catch-all;
gedeckt sind `admin`, `profile`, `register`, `login`, `verify`, die Passwort-Pfade und der
Marketing-Webhook. Ein Pfad ohne passende Regel ist in Symfony offen. Das entspricht dem
Bestand (`/about`, `/partner`, `/open`, `/vergleich`) und wird hier **nicht** durch eine
eigene `PUBLIC_ACCESS`-Zeile ergänzt: Eine einzelne explizite Regel für ein einzelnes
Feature ließe die übrigen öffentlichen Seiten so aussehen, als seien sie anders behandelt.

Es gibt keine fremde Kennung im Aufruf und damit keinen IDOR-Fall: Die Route trägt keinen
Parameter. Zusatzparameter im Query-String werden nirgends gelesen (AK-39).

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `/{_locale}/presse` | **kein Zähler** | — | — |
| `/presse/presse-kit-endlech-lu.zip` | **kein Zähler** | — | — (statische Datei, kein PHP im Spiel) |

Begründung entlang der drei Fälle der Projektkonvention:

- **Löst eine Mail aus?** Nein. Kein Formular, kein Versand. Der Kontakt ist ein
  `mailto:`-Link — er läuft im Mailprogramm des Besuchers, nicht auf dem Server.
- **Prüft ein Geheimnis?** Nein. Kein Token, kein Passwort, keine Anmeldung.
- **Lädt bei jedem Aufruf den gesamten Bestand?** Das wäre der Fall, und hier greift der
  Schutz strukturell statt als Zähler: Die Kennzahlen kommen aus dem bestehenden
  Zwischenspeicher `cache.open_stats` (Dateisystem, eine Stunde). Ein Rate Limit wäre das
  falsche Werkzeug — es sperrte echte Besucher aus, ohne die Last zu senken. Und ein
  Presse-Kit, das nach fünf Aufrufen dichtmacht, verfehlt seinen Zweck: Redaktionen sitzen
  regelmäßig hinter einer gemeinsamen Adresse.

Der Zwischenspeicher wird von diesem Feature **nicht** geleert. `OpenStatsService::invalidate()`
ruft weiterhin nur die Finanzverwaltung.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| — | — | — | — |

**Die Tabelle ist absichtlich leer.** Beim Aufruf wird kein fremder Server kontaktiert:
keine fremden Schriften, keine Karte, kein Analysewerkzeug, kein extern geladenes Bild.
Alle fünf Vorschauen liegen im eigenen Verzeichnis. Das ist die technische Entsprechung
von AK-38 — die IP-Adresse des Besuchers erreicht keinen Dritten.

Die Presseadresse ist ein `mailto:`-Link und damit kein Dienstaufruf. Was der Besucher
dorthin schreibt, geht über den vorhandenen Postweg und nicht durch diese Anwendung.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Struktur in `App\Press\`, Texte in eigener Domain `press` | alles als Block `presse:` in `messages.*.yaml` | dieselbe Begründung wie bei Feature 03: Die Kataloge tragen bereits über 1200 Schlüssel, und eine datengetriebene Liste ruft ihre Schlüssel dynamisch auf — der Scanner in `CatalogueCompletenessTest` erfasst nur Literale und liefe ins Leere |
| 2 | eigener Dienst `PressFacts` statt Wiederverwendung von `ComparisonFigures` | den vorhandenen Dienst mitbenutzen | beide lesen dieselbe Quelle (`OpenStatsService::platform()`), es entsteht also kein zweiter Rechenweg. Ein Dienst namens `Comparison…` im Pressecontroller wäre eine Abhängigkeit zwischen zwei Features, die fachlich nichts miteinander zu tun haben; die Umbenennung des vorhandenen Dienstes fasste Feature 03 ohne funktionalen Gewinn an |
| 3 | Betreiberangaben als **Parameter**, nicht als Übersetzungsschlüssel | vier Katalogeinträge | AK-15 verlangt Gleichheit auf zwei Seiten. Vier Kataloge sind vier Stellen, an denen eine Änderung vergessen werden kann — und der Katalogtest prüft Vollständigkeit, nicht Gleichheit. Die Anschrift ist in jeder Sprache dieselbe |
| 4 | Parameter **committet**, nicht aus einer Umgebungsvariablen | `OPERATOR_ADDRESS` in der `.env.local` auf dem Server | ein leerer Wert ergäbe ein unvollständiges Impressum, und zwar still. Die Angabe soll ohnehin öffentlich sein. Preis: Sie steht dauerhaft in der Git-Historie (siehe Datenmodell, OF-04) |
| 5 | ein Paket als **fertige Datei im Repository**, erzeugt von einem Konsolenbefehl | zur Laufzeit packen · im Deploy packen | Entscheidung 15 der Spec. Zur Laufzeit fiele die Auslieferung unter die Limiter-Konvention und bräuchte einen Deckel; im Deploy wäre es ein neuer Schritt in `deploy.sh`, der bei jedem Auslieferungsfehler mit untersucht werden muss. Der Befehl macht das Erzeugen wiederholbar, der Prüflauf das Ergebnis nachprüfbar |
| 6 | der Paketbefehl liest die Liste aus `PressRegistry` und rendert die Nutzungsbedingungen über den Übersetzer | Shell-Skript wie `bin/generate-pwa-icons.sh` | ein Shell-Skript käme nicht an die Katalogtexte heran. AK-22 verlangt, dass die Bedingungen im Paket dieselben sind wie auf der Seite — mit einer zweiten, von Hand gepflegten Textdatei wäre das eine Hoffnung |
| 7 | die Nutzungsbedingungen liegen als **eine** Datei mit vier Sprachabschnitten im Paket | vier Dateien, eine je Sprache | wer das Paket entpackt, soll die Bedingungen finden und nicht auswählen müssen; vier Dateien laden dazu ein, drei davon zu löschen |
| 8 | Paketgröße zur Laufzeit aus der Datei | Größe im Übersetzungskatalog | eine Zahl im Katalog veraltet still und sagt nichts darüber, ob die Datei überhaupt da ist. Ein `stat` je Aufruf deckt AK-20 und EC-04 zugleich |
| 9 | kein zweites Porträt für die Presse | eine eigene Datei unter `public/presse/` | die vorhandene ist 2048 × 1365 px und damit drucktauglich; eine Kopie wäre eine zweite Wahrheit, die beim nächsten Bildwechsel auseinanderfällt |
| 10 | Faktenblatt als Beschreibungsliste, nicht als Tabelle | Tabelle mit Scrollbereich wie auf `/partner` | eine Tabelle bräuchte bei 320 px einen fokussierbaren Scrollbereich. Eine Liste, die untereinander bricht, erfüllt AK-33 ohne Zusatzmechanik |
| 11 | ein Fußzeilen-Eintrag in Spalte 2, keine eigene Spalte | fünfte Fußzeilenspalte | Feature 03 brauchte eine eigene Spalte für vier Links plus Überschrift; ein einzelner Link steht in der bestehenden Liste. Eine fünfte Spalte zöge die Fußzeile bei `lg:` schief |
| 12 | Meldungen ohne eigene Detailseiten | je Meldung eine Adresse `/presse/meldung/{slug}` | die Spec verlangt eine Liste (AK-26). Eigene Adressen wären neue Seiten mit eigenen Titeln, Sprachverweisen und Prüflaufeinträgen — ein Nachtrag, den niemand bestellt hat |
| 13 | Abschnitts-`id`s (`#boilerplate`, `#fakten`, …) | keine Anker | sie sind der Prüfpunkt für AK-42 in allen vier Sprachen und erlauben nebenbei den Verweis auf einen einzelnen Abschnitt aus einer Mail an eine Redaktion |
| 14 | kein Kopier-Knopf an den Beschreibungstexten | Knopf mit Zwischenablage-Zugriff | AK-09 verlangt, dass der Text ohne JavaScript markierbar ist; ein Knopf wäre eine Zugabe, die kein Kriterium trägt, und brächte einen Stimulus-Controller samt Rückfallweg mit |
| 15 | `ext-zip` nur in `require-dev`, dazu `zip` in der CI-Erweiterungsliste | in `require` aufnehmen | die Anwendung selbst braucht die Erweiterung nie — nur der Befehl (lokal) und der Prüflauf. Eine Pflichterweiterung in `require` müsste auf Produktion vorhanden sein, ohne dort je benutzt zu werden |

| 16 | Kurzbeschreibung und kanonische Adresse über die **vorhandenen** Blöcke aus Feature 03 | eigene Blöcke anlegen · ganz weglassen | die Blöcke stehen seit Feature 03 leer in `base.html.twig:11–12` und ändern an keiner bestehenden Seite etwas. Am 2026-08-30 als AK-43/AK-44 in die Spec nachgetragen — vorher wären sie eine stille Zugabe gewesen, die `sdd-qa` nicht prüft und der nächste Umbau unbemerkt entfernt |

**Kein neues Framework-Muster, keine neue Abhängigkeit.** Alles, was dieser Entwurf
benutzt, steht bereits im Projekt: Übersetzungsdomains und ihr Prüflauf (Feature 03),
Parameter mit Rückfallwert (`app.contact_email`), `format_date`/`format_number` aus
`twig/intl-extra` (Feature B16), sprachfreie Weiterleitung über `RedirectController`
(`/open`), statische Dateien unter `public/` (`/icons`, `/images`). Deshalb wurde für
diesen Entwurf **keine** externe Dokumentation nachgeschlagen — die Regel aus
`references/doku-beschaffen.md` nennt „Muster, die im Projekt schon stehen" ausdrücklich
als Fall, der sich nicht lohnt. Nachgesehen wurde stattdessen im Bestand: PHP 8.5.2 mit
`zip`, `gd`, `intl` lokal; `symfony/*` auf `8.0.*`; die CI-Erweiterungsliste ohne `zip`
(`.github/workflows/ci.yml:38`).

## Prüfläufe

Sie sind Teil des Entwurfs, weil sechs Kriterien anders nicht nachweisbar sind.

| Prüflauf | Deckt ab | Bemerkung |
|---|---|---|
| `tests/Unit/Press/PressRegistryTest.php` | AK-07, AK-18, AK-25, AK-24 | Mindestzahlen und Vollständigkeit: drei Längen, fünf Materialarten je einmal, mindestens zwei Zitate, Fotocredit am Porträt |
| `tests/Unit/Translation/PressCatalogueTest.php` | AK-08, AK-30, AK-37 | hält jeden in `App\Press\` genannten Schlüssel gegen alle vier Kataloge (der Bestandsscanner sieht dynamische Schlüssel nicht) und zählt die Wörter der drei Beschreibungstexte gegen die Grenzen aus `BoilerplateLength` |
| `tests/Unit/Press/PressPackageTest.php` | AK-17, AK-18, AK-19, AK-22, EC-04 | öffnet die committete Paketdatei und vergleicht ihre Einträge mit `PressRegistry::assets()` plus der Bedingungsdatei. **Braucht `ext-zip`** |
| `tests/Functional/Controller/PressControllerTest.php` | AK-02, AK-05, AK-06, AK-16, AK-20, AK-26, AK-27, AK-38, AK-39, AK-42, AK-43, AK-44 | Antwortcodes in vier Sprachen, sieben Abschnitts-`id`s je Sprache, leerer Meldungszustand, keine fremden Hosts im Markup, Zusatzparameter ohne Wirkung, Kurzbeschreibung und kanonische Adresse je Sprache |
| `tests/Functional/Controller/OperatorDetailsTest.php` | AK-15 | rendert `/presse` und `/legal` und vergleicht Name, Anschrift und Verantwortlichen zeichengenau |
| `tests/Integration/Press/PressFactsTest.php` | AK-12, AK-13, AK-14 | dieselben Zahlen wie `OpenStatsService::platform()`; ein zweiter Aufruf rechnet nicht erneut |
| `tests/Functional/AccessibilityStructureTest.php` (erweitert) | AK-32, AK-34 | `/presse` in der Routenliste — eine H1, Sprunglink, `main#hauptinhalt`, `html lang` |
| `tests/Unit/Translation/CatalogueCompletenessTest.php` (erweitert) | AK-30, AK-31 | Domain `press` in `DOMAINS` |

⚠ **`ext-zip` fehlt heute in der CI.** `.github/workflows/ci.yml:38` installiert
`ctype, iconv, intl, mbstring, pdo_mysql, gd`. Ohne die Ergänzung wird
`PressPackageTest` auf dem Runner rot — und zwar mit einer Fehlermeldung über eine
unbekannte Klasse, die wie ein Codefehler aussieht und keiner ist. Die Zeile gehört in
denselben Commit wie der Prüflauf.

**Nicht durch Code nachweisbar** und deshalb ausdrücklich als Prüfung *am Betrieb*
geführt: AK-29 (die Presseadresse nimmt Post an) und AK-41 (ein Beitrag entsteht ohne
Rückfrage). Beide belegt `sdd-qa` von Hand — die erste mit einer Testmail, die zweite,
indem jemand die Seite als einzige Quelle benutzt.

## Abdeckung der Akzeptanzkriterien

Aus `spec.md` der Reihe nach durchgegangen, nicht aus dem Gedächtnis.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | elfter Eintrag in Fußzeilenspalte 2 (`base.html.twig`) → `app_press_index` | Fußzeile wird auf jeder Route gerendert |
| AK-02 | Route `app_press_index`, Requirement `lb\|de\|fr\|en` aus dem `controllers`-Loader | `PressControllerTest` über alle vier |
| AK-03 | Abschnitt `#presse-teaser` am Ende von `about/index.html.twig` | eigener Prüfpunkt im Controllertest |
| AK-04 | Route ohne Parameter; Sprachumschalter und `hreflang`-Schleife unverändert | |
| AK-05 | `app_press_redirect` in `config/routes.yaml`, Muster `app_open_redirect` | |
| AK-06 | eigener Titelschlüssel `press.title` in der Domain `press` | |
| AK-07 | `BoilerplateLength` mit genau drei Fällen, `PressRegistry::boilerplates()` | `PressRegistryTest` |
| AK-08 | Wortzahlgrenzen **im Enum**; `PressCatalogueTest` zählt je Sprache | die Grenzen stehen nicht im Prüflauf, sonst prüfte er gegen sich selbst |
| AK-09 | Textkarten ohne `<details>`, ohne Skript; Entscheidung 14 | |
| AK-10 | Domain `press` in vier Sprachen, Fallback-Kette nur bei fehlendem Schlüssel | folgt aus AK-30 |
| AK-11 | Faktenblatt aus vier Parametern + festen Angaben (Lizenzen, Sprachen, Status) + `PressFacts` | |
| AK-12 | `PressFacts` liest `OpenStatsService::platform()` — dieselbe Quelle wie `/open` und `/open.json` | Entscheidung 2 |
| AK-13 | derselbe Zwischenspeicher `cache.open_stats`, TTL 3600; keine feste Zahl im Katalog | |
| AK-14 | nur `platform()`, Ergebnis aus dem Zwischenspeicher | `PressFactsTest` zählt die Abfragen |
| AK-15 | vier Parameter, zwei Rendering-Stellen; `legal.info_text` entfällt | `OperatorDetailsTest` vergleicht zeichengenau |
| AK-16 | Vorschaukachel je Eintrag aus `PressRegistry::assets()` — dieselbe Liste, aus der das Paket entsteht | |
| AK-17 | `PressPackageTest` vergleicht die committete Datei mit `assets()` | ⚠ dazu die Service-Worker-Regel im Datenmodell — sonst bricht es im Browser, nicht im Prüflauf |
| AK-18 | `PressAssetKind` mit fünf Fällen, jeder genau einmal belegt; Bedingungsdatei als Pflichteintrag im Paket | `PressRegistryTest` + `PressPackageTest` |
| AK-19 | Dateiname `presse-kit-endlech-lu.zip`; der Prüflauf öffnet die Datei und belegt damit ihre Gültigkeit | |
| AK-20 | Linktext mit Platzhaltern für Format und Größe, Größe aus `PressPackage::sizeBytes()` | Entscheidung 8 |
| AK-21 | Abschnitt Nutzungsbedingungen, zwei Listen (erlaubt / nicht erlaubt) in der Domain `press` | |
| AK-22 | `app:press:package` rendert dieselben Schlüssel in die Paketdatei | Entscheidung 6 |
| AK-23 | Abschnitt `#person`: Porträt, Name, Funktion, Kurzvita | |
| AK-24 | `PressAsset.creditKey`, Pflicht beim Porträt; unter dem Bild gerendert | Wortlaut hängt an OF-05 |
| AK-25 | `PressQuote`, mindestens zwei; Freigabehinweis als eigener Schlüssel | `PressRegistryTest` |
| AK-26 | `PressRegistry::releases()` absteigend sortiert; Datum über `format_date` in der Sprache der Seite | EC-10 |
| AK-27 | leerer Zustand im Template, Verweis auf den Pressekontakt | `PressControllerTest` mit leerer Liste |
| AK-28 | Parameter `app.press_email` als Mailto, Antwortzeit und Interviewhinweis als Schlüssel | Wortlaut hängt an OF-03 |
| AK-29 | **Konfigurationsaufgabe** (VB-02): Postfach beim Hoster einrichten | kein Code; Nachweis in `qa-report.md` als Testmail |
| AK-30 | Domain `press` in `CatalogueCompletenessTest::DOMAINS` **plus** `PressCatalogueTest` für die dynamisch aufgerufenen Schlüssel | der Bestandsscanner erfasst nur Literale — genau die Falle, die dieses AK sonst wirkungslos machte |
| AK-31 | folgt aus AK-30: nur ein vollständiger Katalog verhindert den stillen Rückfall auf die Fallback-Kette | |
| AK-32 | `/presse` in `AccessibilityStructureTest::publicRoutes()` | |
| AK-33 | Faktenblatt als Beschreibungsliste, Vorschaukacheln als umbrechendes Raster, Text über `container mx-auto px-4` | Entscheidung 10; keine Tabelle, kein Scrollbereich |
| AK-34 | eine H1 im Kopfband, sieben H2 (je Abschnitt), Karten als H3 | von AK-32 mitgeprüft |
| AK-35 | Alternativtext aus `PressAsset.labelKey` — die Variantenbezeichnung, nicht der Dateiname | |
| AK-36 | das Faktenblatt speist sich aus einer geschlossenen Parameterliste; `PressFacts` gibt ein festes Array zurück | `PressControllerTest` prüft die Menge der ausgegebenen Angaben |
| AK-37 | die Angabe steht in genau einem Katalogschlüssel (`press.bio`); `PressCatalogueTest` prüft, dass kein weiterer Schlüssel der Domain sie enthält | damit ist der Widerruf tatsächlich eine Textstelle und nicht drei |
| AK-38 | keine externe Ressource im Template; alle fünf Vorschauen aus dem eigenen Verzeichnis | Prüflauf: keine fremden Hosts in `src`/`href` von Ressourcen |
| AK-39 | der Controller liest keinen Query-Parameter | Prüflauf mit Zusatzparameter |
| AK-40 | statische Auslieferung durch den Webserver; `public/.htaccess` leitet nur nicht existierende Pfade an `index.php` | Prüflauf: der Router kennt keine Route auf die Paketdatei |
| AK-41 | die Seite als Ganzes — kein einzelner Baustein | **Abnahme von Hand** in `sdd-qa`, siehe Prüfläufe |
| AK-42 | sieben Abschnitts-`id`s, in allen vier Sprachen geprüft | Entscheidung 13 |
| AK-43 | Block `meta_description` im Presse-Template, Text aus der Domain `press` | Entscheidung 16; am 2026-08-30 in die Spec nachgetragen |
| AK-44 | Block `canonical` auf die eigene Adresse; die `hreflang`-Schleife in `base.html.twig` greift automatisch, weil die Route keinen Parameter trägt | dito |

Keine Zeile leer. Zwei Kriterien (AK-29, AK-41) sind ausdrücklich **nicht durch Code**
erfüllt und als solche gekennzeichnet — sie fallen damit nicht still unter den Tisch,
sondern stehen im Testbericht als Konfigurations- beziehungsweise Handprüfung.

Die vier Punkte des Sicherheitskatalogs, die die Spec als *trifft nicht zu* führt
(Weitergabe an externe Dienste, Zugriff auf fremde Datensätze, Löschen und Auskunft,
Geheimnisse), sind in den Abschnitten Externe Dienste, Zugriffsregeln und Datenmodell
jeweils mit ihrer Begründung wiederholt — nicht weggelassen.

## Vorbedingungen und offene Fragen aus der Spec

| # | Stand nach dem Entwurf |
|---|---|
| VB-01 | **unverändert blockierend.** Der Entwurf legt Dateinamen und Varianten fest (vier SVG in `public/presse/`), erzeugt sie aber nicht. `PressRegistryTest` und `PressPackageTest` schlagen fehl, solange sie fehlen — die Blockade ist damit sichtbar und nicht bloß vermerkt |
| VB-02 | **unverändert blockierend**, jetzt an einer Stelle: Parameter `app.press_email`, Vorgabewert `support@endlech.lu`. Der Nachweis (AK-29) ist eine Testmail, kein Prüflauf |
| VB-03 | **unverändert blockierend.** Der Entwurf legt die Form fest (vier Parameter, zwei Rendering-Stellen), nicht den Inhalt |
| OF-01 | **offen** — Datenschutzstufe des Projekts. Der Entwurf ändert daran nichts, macht die Folge aber lokal: Die Gesundheitsangabe steht in genau einem Katalogschlüssel (AK-37), damit eine spätere Entscheidung an einer Stelle wirkt |
| OF-02 | **offen** — wer die Vektormarken erstellt. Entwurfsseitig vorbereitet: Dateinamen, Varianten, Ablageort stehen fest |
| OF-03 | **offen** — die zugesagte Antwortzeit. Sie ist ein Katalogschlüssel; die Zahl ändert kein Bauteil |
| OF-04 | **offen** — Privatanschrift oder c/o. Entscheidung 4 benennt die Konsequenz: Der Wechsel ist ein Parametertausch, die Git-Historie bleibt |
| OF-05 | **offen** — Fotocredit. Entwurfsseitig erzwungen: `creditKey` ist beim Porträt Pflicht, `PressRegistryTest` schlägt ohne ihn fehl |
| OF-06 | **offen** — erste Pressemitteilung. Der Entwurf trägt den leeren Zustand (AK-27) und braucht sie deshalb nicht zum Ausliefern |
| OF-07 | **entschieden:** `app:press:package` erzeugt das Paket, `PressPackageTest` vergleicht es mit der Liste. Wer eine Datei ändert und das Paket nicht neu baut, bekommt einen roten Prüflauf statt eines veralteten Downloads. **Zusatz aus dem Entwurf:** Dazu gehört die Service-Worker-Regel (`CACHE_VERSION` erhöhen) — sonst ist das Paket frisch und die Vorschau im Browser alt |

## Was dieser Entwurf ausdrücklich nicht vorsieht

- **Keine Entität, keine Migration, kein Admin-Formular.** Wer später Pflege im Admin
  will, baut ein neues Feature — nicht einen Nachtrag hier.
- **Keine Detailseiten für Meldungen** und keine Ankündigungsübersicht (Entscheidung 12).
- **Kein Vorschaubild für geteilte Links (Open Graph, `og:image`).** Kurzbeschreibung und
  kanonische Adresse sind seit dem 2026-08-30 im Umfang (AK-43, AK-44); ein Vorschaubild
  ist es nicht — es bräuchte eine eigene Bilddatei je Sprache und gehört in dasselbe
  Feature wie `sitemap.xml` und `robots.txt`.
- **Keine Kurzbeschreibung für die übrigen rund zwanzig Seiten.** Dieses Feature füllt die
  vorhandenen Blöcke nur für sich selbst.
- **Keine strukturierten Daten (JSON-LD) zur Organisation.** Naheliegend für eine
  Presseseite, aber die Plattform hat heute nirgends welche; ein erster Fall gehört in
  dasselbe Feature wie `sitemap.xml` und `robots.txt`.
- **Keine Einzeldownloads.** Entscheidung 5 der Spec. Die Dateien sind als Vorschau
  natürlich über ihre Adresse erreichbar — das ist unvermeidlich und kein Widerspruch:
  Die Entscheidung betrifft, was die Seite *anbietet*, nicht was der Webserver ausliefert.
- **Kein Presseverteiler, kein Formular, kein Brevo-Bezug.** Steht so in der Spec unter
  *Nicht im Scope*; hier wiederholt, weil ein Verteiler die einzige naheliegende
  Erweiterung ist, die das Feature vom Sicherheitskatalog her grundlegend ändern würde.
- **Keine Änderung an `OpenStatsService`.** Der neue Dienst liest, er erweitert nicht.
