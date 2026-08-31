# 07 · Öffentliche Roadmap und Changelog — Systemdesign

Status: `architected` · Stand: 2026-08-30 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Zwei öffentliche Leseseiten, **keine Entität und keine Migration**. Die Bauart ist die
von Feature `03` und `05`: Die Struktur steht als unveränderliche Wertobjekte im Code
(hier unter `App\Roadmap\`), die Texte liegen in eigenen Übersetzungsdomains, damit sie
in vier Sprachen existieren und ein Prüflauf sie zählen kann.

Die Roadmap setzt zwei Quellen zusammen. Die **kuratierten Vorhaben** des Betreibers
stehen im Code — eine Änderung ist ein Commit, so wie die Vergleichstabellen und das
Presse-Kit. Die **Community-Ideen** kommen live aus dem Board von Feature `06`,
gefiltert auf veröffentlichte Ideen mit Status `Geplant`, höchstens zehn, nach
Zustimmungen sortiert. Das Ergebnis liegt in einem eigenen Zwischenspeicher, den ein
Doctrine-Listener bei jeder Änderung an einer Idee, einer Stimme oder einem Konto
verwirft.

Der Changelog ist eine Liste von Release-Notizen: Versionsnummer und Datum stehen im
Code, der Text im Katalog. Jede Version aus `CHANGELOG.md` ist dort entweder mit einem
öffentlichen Text vertreten oder ausdrücklich als **still** verzeichnet; ein Prüflauf
hält beide Listen gegeneinander und färbt rot, wenn eine Version in keiner steht.

Beide Seiten teilen sich einen Aktualitätshinweis, der ab 60 Tagen ohne neuen Eintrag
aus dem Kleingedruckten in einen hervorgehobenen Kasten tritt — dieselbe Regel wie bei
den Finanzzahlen auf `/open`.

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_roadmap_index` | `/{_locale}/roadmap` | Drei Status-Spalten, darunter der Block „Bewusst nicht gebaut" | öffentlich, nur `GET` |
| `app_changelog_index` | `/{_locale}/changelog` | Öffentliche Release-Einträge, nach Jahren gruppiert | öffentlich, nur `GET` |
| `app_roadmap_redirect` | `/roadmap` (sprachfrei) | Kurzlink → Sprachfassung, **302**, ein Sprung | öffentlich |
| `app_changelog_redirect` | `/changelog` (sprachfrei) | Kurzlink → Sprachfassung, **302**, ein Sprung | öffentlich |

Die beiden Weiterleitungen stehen in `config/routes.yaml` neben `app_open_redirect`,
`app_comparison_redirect` und `app_press_redirect`. Sie sind **nicht** permanent — ein
301 bliebe in fremden Browsern stehen, und genau das war der teure Teil von BF-100.

⚠ **Es darf kein Verzeichnis `public/roadmap` und kein `public/changelog` entstehen.**
Auf Apache schickt `mod_dir` sonst jeden Aufruf per 301 auf die Form mit Schrägstrich,
während Symfony zurückschickt — die Schleife aus BF-100, lokal unsichtbar.
`RouteDirectoryCollisionTest` prüft diese Ursache bereits projektweit; die beiden neuen
Routen fallen ohne Zutun in seinen Geltungsbereich.

⚠ **Die Seiten sind öffentlich, weil keine `access_control`-Regel auf sie passt** —
nicht, weil eine Regel sie freigibt. `security.yaml` hat keinen Catch-all. Es wird
bewusst **keine** eigene Zeile ergänzt: Eine einzelne explizite Regel für dieses Feature
ließe `/about`, `/partner`, `/open`, `/vergleich` und `/presse` so aussehen, als seien
sie anders behandelt. (Muster und Begründung stehen bereits im Kopf von
`PressController`.)

⚠ **Beide Routen sind auf `GET` beschränkt.** Jede andere Methode beantwortet Symfony
mit **405**, ohne dass der Controller läuft — das ist die Umsetzung von AK-11, und sie
kostet keine Zeile Prüfcode.

## Komponentenstruktur

```
Roadmap-Seite (/roadmap)
├── Hero-Band                     Verlauf cyan-700 → purple-800, Titel, ein Satz Zweck,
│                                 Hinweis „keine Termine" als Leitaussage
├── Aktualitätshinweis            geteiltes Partial, Datum des jüngsten Changelog-Eintrags
├── Spaltenblock                  drei Abschnitte nebeneinander, ab 320 px untereinander
│   ├── Spalte „In Arbeit"        eigene <section> mit h2 — Zugehörigkeit steht im Text,
│   │                             nicht in der Position (AK-37)
│   ├── Spalte „Geplant"          kuratierte Vorhaben + bis zu zehn Community-Ideen
│   └── Spalte „Angedacht"        kuratierte Vorhaben
│       └── Eintragskarte         Titel · ein Satz Begründung · Herkunftszeichen
│                                 Community-Variante: Zustimmungszahl + Verweis ins Board
├── Community-Fußzeile der Spalte „… und N weitere im Board" (nur wenn mehr als zehn)
├── Block „Bewusst nicht gebaut"  eigene Überschrift, sichtbar abgesetzte Fläche,
│                                 je Punkt Titel und Begründung
└── Weiterleser-Block             ein Klick zu Changelog und Board (AK-10)

Changelog-Seite (/changelog)
├── Hero-Band                     wie oben, Titel und Zweck
├── Aktualitätshinweis            dasselbe geteilte Partial
├── Jahresliste
│   ├── Laufendes Jahr            offen, ohne <details>-Hülle
│   │   └── Release-Eintrag       Version · Datum · Titel · Fließtext
│   └── Frühere Jahre             je Jahr ein <details>, zugeklappt, ohne JavaScript
└── Hinweis auf die technische Fassung   Verweis auf CHANGELOG.md, mit dem Zusatz,
                                  dass sie nur auf Deutsch vorliegt
```

Wiederverwendet, nicht neu gebaut: das Hero-Band-Muster der Außenseiten, die
`<details>`-Mechanik von `/open`, der Fokus- und Zielgrößenstandard aus Feature `02`
(`focus:outline-2`, `min-h-[48px]`).

**Die vier Zustände, ausdrücklich:**

| Zustand | Roadmap | Changelog |
|---|---|---|
| **leer** | Eine Spalte ohne Eintrag zeigt einen erklärenden Satz statt einer leeren Fläche (AK-09). Fehlen die Community-Ideen ganz, erscheint kein leerer Block und kein Hinweis auf ein Versäumnis (EC-01) | Ohne einen einzigen öffentlichen Eintrag: erklärender Text plus Verweis auf die technische Fassung (AK-25) |
| **ladend** | **Entfällt.** Die Seite wird serverseitig in einer Antwort gerendert; es gibt keinen Nachladeschritt und damit keinen Ladezustand zu entwerfen | ebenso |
| **Fehler** | Ist das Board nicht abfragbar, wird die Seite mit den kuratierten Vorhaben allein gerendert — kein 500er, kein leerer Bereich (EC-11) | Kein eigener Fehlerfall: Die Daten kommen aus Code und Katalog, beide sind beim Ausliefern vorhanden |
| **gefüllt** | Der Regelfall | Der Regelfall |

## Datenmodell

**Keine neue Tabelle, keine Migration, keine Änderung an einer bestehenden Spalte.**
Der Abschnitt beschreibt deshalb die Wertobjekte und die gelesenen Fremdfelder.

### Wertobjekte unter `App\Roadmap\` (kein Zustand, keine Datenbank)

**`RoadmapStage`** — Aufzählung der drei Spalten.

| Wert | Bedeutung |
|---|---|
| `in_progress` | wird gerade gebaut |
| `planned` | fest vorgesehen, noch nicht begonnen |
| `considered` | erwogen, nicht zugesagt |

Trägt `transKey()`, `emoji()` und `badgeClasses()` — dasselbe Muster wie
`BoardIdeaStatus` und `TriState`. Die Reihenfolge der Fälle ist die Reihenfolge der
Spalten (AK-04).

**`RoadmapItem`** — ein kuratiertes Vorhaben.

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `key` | string | ja | Schlüsselstamm in der Domain `roadmap`; daraus entstehen `…title` und `…reason` |
| `stage` | `RoadmapStage` | ja | in welcher Spalte es steht |

⚠ **Ein Eintrag ohne Begründungstext ist strukturell nicht möglich**: Der Schlüssel
`…reason` gehört zum Wertobjekt, und der Katalogtest verlangt ihn in vier Sprachen. Das
ist die Umsetzung von AK-05 und AK-29 — nicht eine Bitte an den, der Einträge pflegt.

**`ShelvedItem`** — ein Punkt aus „Bewusst nicht gebaut". Gleicher Aufbau, ohne
`stage`; getrennter Typ, damit ein zurückgestellter Punkt nicht versehentlich in einer
Spalte landen kann (AK-08).

**`RoadmapRegistry`** — die einzige Stelle, an der beide Listen stehen. Liefert die
Vorhaben je Spalte und die zurückgestellten Punkte. Kein Zustand, keine Abhängigkeit.

**Der Inhalt steht seit dem 2026-08-30 fest** (VB-02, Decision Log 15):

| Spalte | Vorhaben |
|---|---|
| **In Arbeit** | Öffentliche Roadmap und Changelog |
| **Geplant** | Bewertungen und Kommentare · Kartenansicht · Favoriten — dazu bis zu zehn Community-Ideen |
| **Angedacht** | Native iOS-App · Chat-Widget · KI-Filter · Android-App, Google- und Apple-Login |
| **Bewusst nicht gebaut** | die acht zurückgestellten Punkte aus `CLAUDE.md` |

⚠ **„Bewertungen und Kommentare" trägt eine sachliche Begründung ohne Bezug auf das
Werbeversprechen der Startseite** (OF-02). Der Widerspruch — die Startseite wirbt mit
„echten Bewertungen", die es nicht gibt — bleibt damit ungenannt und ist als **OF-08**
in `spec.md` festgehalten. Er gehört in ein eigenes Vorhaben, nicht hierher.

**`ReleaseNote`** — ein Release.

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `version` | string | ja | CalVer wie in `CHANGELOG.md`, z. B. `2026.08.30.2` |
| `date` | `DateTimeImmutable` | ja | Datum des Release |
| `public` | bool | ja | `true` = öffentlicher Eintrag, `false` = **still** |

⚠ **Die stillen Releases stehen in derselben Liste wie die öffentlichen.** Ohne sie
könnte der Prüflauf aus AK-26 nicht zwischen „bewusst nicht gezeigt" und „vergessen"
unterscheiden — und wäre damit wertlos.

**`ChangelogRegistry`** — die Liste aller `ReleaseNote`. Liefert zusätzlich die
öffentlichen Einträge nach Jahren gruppiert (AK-22) und das Datum des jüngsten
öffentlichen Eintrags für den Aktualitätshinweis (AK-27, AK-28).

**Der Umfang steht seit dem 2026-08-30 fest** (VB-03, OF-01): neun Releases mit
sichtbarer Wirkung für Gäste werden öffentlich, davor eine Sammelzeile „Aufbau der
Plattform" für Januar bis März 2026; alle übrigen tragen `public = false`.

**`CommunityRoadmap`** — der einzige Dienst mit Datenbankzugriff. Liefert die
geplanten Board-Ideen als reine Skalarstruktur (Kennung, Titel, Slug, Sprache,
Zustimmungszahl) plus die Zahl der nicht gezeigten. **Skalar, nicht Entitäten:**
Dieselbe Struktur geht durch Zwischenspeicher und Twig; eine Entität im Cache wäre
losgelöst vom Entity-Manager und verhielte sich in beiden Wegen unterschiedlich —
dieselbe Begründung wie bei `OpenStatsService`.

### Gelesene Felder aus Feature `06` (unverändert)

| Entität | Feld | Wozu hier |
|---|---|---|
| `BoardIdea` | `id`, `slug` | Verweis auf `app_board_show` (AK-15) |
| `BoardIdea` | `title` | Anzeige in der Spalte |
| `BoardIdea` | `locale` | `lang`-Auszeichnung des Titels (AK-33) |
| `BoardIdea` | `status` | Filter auf `Geplant` (AK-12, AK-13) |
| `BoardIdea` | `publishedAt` | Sichtbarkeitsgrenze (AK-14) |
| `BoardIdea` | `duplicateOf` | zusammengeführte Dubletten bleiben draußen |
| `BoardVote` | Anzahl je Idee | Sortierung und Anzeige (AK-15, AK-17) |
| `BoardIdea` | `submittedBy` | **wird nicht gelesen** — der Verfasser erscheint nirgends (AK-39) |

### Änderung an bestehendem Code

| Was | Wo | Warum |
|---|---|---|
| **Zwei neue Abfragemethoden** | `BoardIdeaRepository` | Die geplanten Ideen mit Begrenzung, und ihre Gesamtzahl. Keine Änderung an bestehenden Methoden |
| **Ein Entity-Listener** | neu unter `src/EntityListener/` | Verwirft den Zwischenspeicher; fasst Feature `06` nicht an |
| **Zwei Fußzeilenverweise** | `templates/base.html.twig`, Spalte 4 | Einstieg für AK-02 |
| **Ein neuer Cache-Pool** | `config/packages/cache.yaml` | eigener Pool, siehe Entscheidung 3 |

⚠ **`findPublishedPaginated()` taugt hier nicht**, obwohl sie einen Status-Filter kennt:
Sie liefert 20 Einträge je Seite, baut einen `Paginator` und behandelt `Umgesetzt`
gesondert. Für zehn Einträge ohne Blätterung ist eine eigene Methode ehrlicher als ein
Aufruf, dessen Rückgabe zur Hälfte weggeworfen wird.

⚠ **Die Zählung läuft über `COUNT(...) AS HIDDEN` mit `GROUP BY`, nicht über einen
`addSelect`-Join** — die Lehre aus BF-64, die `findPublishedPaginated()` bereits zieht:
Ein fetch-join vervielfacht die SQL-Zeilen je Entität, und eine Begrenzung wirkt dann
auf Zeilen statt auf Ideen. Eine Idee mit zwölf Stimmen füllte sonst die ganze Spalte.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Jeder, auch abgemeldet | beide Seiten vollständig | nichts | `methods: ['GET']` an beiden Routen → 405 bei jeder anderen Methode; der Controller kennt keinen Schreibweg |
| Jeder | von den Board-Ideen **nur** die mit `publishedAt IS NOT NULL`, `duplicateOf IS NULL` und `status = Geplant` | — | die Kriterien der Abfrage in `BoardIdeaRepository`, serverseitig — nicht durch eine Bedingung im Template |
| Angemeldete, Admin | dasselbe wie jeder | nichts | keine Sonderbehandlung; die Seiten kennen den Anmeldezustand nicht |

⚠ **Der Filter steht in der Abfrage, nicht in der Ausgabe.** Eine nie freigegebene Idee
darf nicht erst im Template ausgeblendet werden — sie stünde sonst im ausgelieferten
Quelltext (AK-43). Das ist die einzige Zugriffsregel dieses Features und zugleich die
einzige Stelle, an der es etwas falsch machen kann.

⚠ **Es gibt keine Detailseite und keine Kennung in der Adresse.** Damit existiert kein
IDOR-Weg; der einzige Parameter der beiden Routen ist die Sprache, und die ist auf
`lb|de|fr|en` beschränkt. Beliebige Query-Parameter werden nicht gelesen und nicht
zurückgegeben (AK-44).

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `/roadmap` | **kein Rate Limit** — bewusst | entfällt | Entscheidung 6, begründet in `spec.md` Decision Log 7 |
| `/changelog` | **kein Rate Limit** — bewusst | entfällt | ebenda |
| `/roadmap` → Board | höchstens **10** Ideen je Aufruf, dazu eine Zählabfrage | die elfte und alles darüber erscheint nicht; die Zahl der übrigen wird genannt | `CommunityRoadmap::MAX_ITEMS`, umgesetzt als Begrenzung **in der Abfrage** |
| `/roadmap` | Zwischenspeicher, Lebensdauer 3600 s | wiederholte Aufrufe erzeugen keine Datenbankabfrage | neuer Pool `cache.roadmap` in `config/packages/cache.yaml`; `when@test` auf `cache.adapter.array` |
| Invalidierung | bei jeder Änderung an `BoardIdea`, `BoardVote` oder gelöschtem `User` | der nächste Aufruf rechnet neu | Entity-Listener, `#[AsEntityListener]` — Muster vorhanden in `RestaurantImageFileListener` |
| `/changelog` | keine Abfrage | entfällt | Die Seite liest nur Code und Katalog |

**Warum kein Limiter, obwohl `CLAUDE.md` einen verlangt.** Die Konvention lautet: Jeder
Weg, der bei jedem Aufruf den gesamten Bestand lädt, braucht einen Deckel. Dieser Weg
lädt den Bestand nie — die Begrenzung steht in der Abfrage, nicht in der Darstellung.
Damit ist die Ursache beseitigt, gegen die der Deckel gerichtet wäre. Ein Limiter wäre
hier die erste öffentliche **Leseseite** der Plattform, die Besucher aussperrt; ein
Angreifer bekäme dieselbe Seite wie ein Leser, nur langsamer.

⚠ **Der Lebensdauerwert ist keine Bequemlichkeit, sondern ein zweites Netz.** Beim
Löschen eines Kontos entfernt die Fremdschlüssel-Kaskade die Stimmen **in der
Datenbank**, am Anwendungscode vorbei — das steht so im Changelog von Feature `06`.
Doctrine sieht dieses Entfernen nicht. Der Listener hängt deshalb **zusätzlich am
gelöschten Konto**, und die Lebensdauer fängt jeden Weg auf, an den beim Entwurf
niemand gedacht hat.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| — | **keiner** | nichts | entfällt |

Beide Seiten laden ausschließlich Ressourcen der eigenen Herkunft: keine Schrift von
einem fremden Host, kein eingebettetes Bild, kein Skript, kein Zählpixel (AK-42). Der
Verweis auf `CHANGELOG.md` ist ein gewöhnlicher Link, den der Besucher selbst anklickt;
die Seite ruft GitHub nicht ab. Ein Feed, der Inhalte an Dritte ausliefert, ist
ausdrücklich nicht im Scope.

Damit ist auch kein Auftragsverarbeitungsvertrag berührt und `docs/datenschutz.md`
bleibt unverändert.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **Zwei Übersetzungsdomains** `roadmap` und `changelog`, geprüft von **einem** gemeinsamen Katalogtest | Eine gemeinsame Domain | Ein Domainname müsste für beide Seiten stehen und wäre für die andere irreführend. Der Prüflauf ist trotzdem einer — dass zwei Domains zwei Läufe brauchen, stimmt nicht |
| 2 | **Zwischenspeicher wird per Doctrine-Entity-Listener verworfen** | Ausdrückliche Aufrufe in `BoardModerator` und `BoardVoteService` | Der Listener fasst Feature `06` nicht an und deckt jeden künftigen Schreibweg mit ab. Ausdrückliche Aufrufe müssten an fünf Stellen stehen, und die sechste wäre die, die jemand vergisst |
| 3 | **Eigener Pool `cache.roadmap`** | `cache.open_stats` mitbenutzen | Beide werfen ihren Inhalt vollständig weg. Eine Finanzänderung im Admin nähme die Roadmap mit, eine Zustimmung im Board die Kennzahlen — dieselbe Begründung, aus der `cache.open_stats` seinerzeit ein eigener Pool wurde |
| 4 | **Kuratierte Vorhaben stehen im Code** | Entität mit Verwaltungsmaske | Muster aus `03` und `05`. Die Liste ändert sich im Takt von Releases, und ein Release ist ohnehin ein Deploy — eine Tabelle, eine Migration und eine Maske wären Struktur ohne Träger |
| 5 | **Stille Releases stehen mit in der Liste** | Nur die öffentlichen führen | Ohne den ausdrücklichen Vermerk kann kein Prüflauf „bewusst still" von „vergessen" unterscheiden. AK-26 wäre nicht umsetzbar, und der neue fünfte Punkt der Release-Checkliste wäre eine Bitte statt einer Absicherung |
| 6 | **Kein Rate Limit** | Limiter je IP wie bei `partner_waitlist` | Rein lesende Seiten ohne Kosten je Aufruf; die Konvention wird an der Ursache erfüllt (Begrenzung in der Abfrage). Ausdrücklich vermerkt, damit die Abwesenheit als Entscheidung erkennbar ist und nicht als Lücke |
| 7 | **Eigene Repository-Methode** statt `findPublishedPaginated()` mit Status-Filter | Bestehende Methode wiederverwenden | Sie liefert 20 je Seite, baut einen `Paginator` und behandelt `Umgesetzt` gesondert. Zehn Einträge ohne Blätterung sind ein anderer Auftrag |
| 8 | **Ein Controller mit zwei Aktionen** | Zwei Controller | Beide Seiten teilen den Aktualitätshinweis und dieselbe Bauart. Zwei Klassen für vier Zeilen Unterschied wären Trennung ohne Grenze |
| 9 | **Die zwei Fußzeilenverweise stehen in Spalte 4** (bei Kontakt), nicht in Spalte 2 | Spalte 2 „Links" oder eine fünfte Spalte | Spalte 2 trägt bereits elf Einträge — Feature `03` hat aus genau diesem Grund eine eigene Spalte aufgemacht. Eine fünfte Spalte bräche das `lg:grid-cols-4`-Raster der Fußzeile, und die App-Hülle hat mit BF-80 bereits eine offene Umbruchlücke, die kein Feature nebenbei vergrößern sollte. Spalte 4 ist heute die dünnste und trägt zwei Einträge, ohne dass sich etwas verschiebt |
| 10 | **`<details>` je Jahr** statt Blätterung | Seitenweise Blätterung wie im Board | Eine Adresse bleibt teilbar und vollständig durchsuchbar; das Muster ist auf `/open` erprobt und funktioniert ohne JavaScript (AK-23). `<details>`/`<summary>` meldet seinen Zustand selbst — **kein handgeschriebenes `aria-expanded`**, das sich ohne Skript nie aktualisieren ließe |
| 11 | **Aktualitätshinweis als geteiltes Partial** | Je Seite eine eigene Fassung | Zwei Fassungen derselben 60-Tage-Regel wären zwei Stellen, an denen sie auseinanderläuft |
| 12 | **Keine Doku-Runde vor dem Entwurf** | Symfony- und Doctrine-Doku nachschlagen | Jedes benutzte Muster steht bereits im Projekt und ist dort belegt: Cache-Pool (`config/packages/cache.yaml`), Entity-Listener (`RestaurantImageFileListener`, Doctrine-Bundle 3.2), sprachfreie Weiterleitung (`app_open_redirect`), `<details>` (`templates/open/index.html.twig`). Nachzuschlagen wäre gewesen, was hier ohnehin nicht verwendet wird |

## Funde beim Entwerfen

Vier Dinge, die sonst erst beim Bauen oder gar nicht aufgefallen wären.

**(1) Der Ideentitel im Board trägt keine Sprachauszeichnung.**
`templates/partials/_board_idea_card.html.twig:110` zeichnet die **Beschreibung** mit
`lang="{{ idea.locale }}"` aus, Zeile 86 den **Titel** nicht. Die Roadmap zeigt genau
den Titel. AK-33 stellt die Auszeichnung dort also erstmals her, statt ein Muster zu
übernehmen — die Spec ist entsprechend korrigiert. **Im Board selbst bleibt die Lücke
offen**; sie gehört zu Feature `06` und wird hier nicht nebenbei behoben.

**(2) `findPublishedPaginated()` schließt `Umgesetzt` aus und blättert zu zwanzigst.**
Wer für die Roadmap dieselbe Methode nähme, bekäme einen `Paginator` über 20 Einträge
und müsste die Hälfte wegwerfen. Siehe Entscheidung 7.

**(3) Die Stimmen-Kaskade beim Kontolöschen läuft an Doctrine vorbei.**
Sie ist im Changelog von Feature `06` ausdrücklich so beschrieben („das geschieht in der
Datenbank, am Anwendungscode vorbei"). Ein Listener auf `BoardVote` allein sähe sie
nicht — deshalb hängt der Listener **zusätzlich am gelöschten Konto**, und die
Lebensdauer von einer Stunde bleibt als zweites Netz stehen. Ohne diesen Fund stünde
nach einer Kontolöschung bis zu eine Stunde lang eine zu hohe Zustimmungszahl auf der
Roadmap.

**(4) Die Fußzeilenspalte 2 ist voll.** Elf Einträge, und der Kommentar über Spalte 3
hält seit Feature `03` fest, dass eine zwölfte Gruppe die Fußzeile schief zöge. Siehe
Entscheidung 9.

## Abdeckung der Akzeptanzkriterien

Aus `spec.md` der Reihe nach abgegangen, nicht aus dem Gedächtnis.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Route `app_roadmap_index`, keine `access_control`-Regel trifft zu | |
| AK-02 | Zwei Verweise in Fußzeilenspalte 4 von `base.html.twig` | Entscheidung 9 |
| AK-03 | Spaltenblock steht direkt unter Hero und Aktualitätshinweis; „In Arbeit" ist die erste Spalte | Zu belegen bei 800 px Höhe — Messung gehört in die QA |
| AK-04 | Reihenfolge der Fälle in `RoadmapStage`; das Template folgt der Aufzählung | Eine vierte Spalte gäbe es nur mit einem vierten Fall |
| AK-05 | Schlüssel `…reason` ist Bestandteil von `RoadmapItem`, Katalogtest verlangt ihn | Strukturell, nicht redaktionell |
| AK-06 | Keine Datumsangabe in `RoadmapItem`, kein Datumsfeld im Template | Negativkriterium — Nachweis als Abwesenheit |
| AK-07 | Block „Bewusst nicht gebaut" aus `RoadmapRegistry::shelved()` | Inhalt: die acht Punkte aus `CLAUDE.md` |
| AK-08 | Eigener Typ `ShelvedItem` und eigener Abschnitt mit eigener Überschrift | Ein zurückgestellter Punkt kann strukturell in keiner Spalte landen |
| AK-09 | Leerer Zustand je Spalte im Template | |
| AK-10 | Weiterleser-Block am Seitenende | |
| AK-11 | `methods: ['GET']` an beiden Routen | Symfony antwortet 405, der Controller läuft nicht |
| AK-12 | `BoardIdeaRepository::findPublishedPlanned()`, Filter auf `Geplant` | |
| AK-13 | Derselbe Filter — alle anderen Status fallen aus der Abfrage | |
| AK-14 | Bedingung `publishedAt IS NOT NULL` in derselben Abfrage | |
| AK-15 | Eintragskarte, Community-Variante: Titel, Zustimmungszahl, Verweis auf `app_board_show` | `submittedBy` wird nicht gelesen |
| AK-16 | Herkunftszeichen an der Community-Variante der Eintragskarte | |
| AK-17 | `CommunityRoadmap::MAX_ITEMS = 10`, Begrenzung in der Abfrage, dazu Zählabfrage für den Hinweis | |
| AK-18 | Live-Abfrage plus Invalidierung durch den Entity-Listener | Kein Deploy nötig |
| AK-19 | Route `app_changelog_index` | |
| AK-20 | Texte in der Domain `changelog`, Version und Datum aus `ReleaseNote` | Dass kein Klassenname im Text steht, ist redaktionell und wird in der QA gelesen |
| AK-21 | `ReleaseNote::$public = false` — stille Releases werden nicht gerendert | |
| AK-22 | Jahresgruppierung in `ChangelogRegistry`, laufendes Jahr offen | |
| AK-23 | `<details>`/`<summary>`, kein JavaScript | Entscheidung 10 |
| AK-24 | Verweis auf `CHANGELOG.md` im Fuß der Seite, mit Sprachhinweis | |
| AK-25 | Leerer Zustand der Changelog-Seite | |
| AK-26 | Neuer Prüflauf: liest die `## [version]`-Überschriften aus `CHANGELOG.md` und hält sie gegen `ChangelogRegistry`; `[Unreleased]` bleibt außen vor | Nachweis ist ein absichtlich entfernter Eintrag, der den Lauf rot färbt |
| AK-27 | Geteiltes Partial `_freshness`, Schwelle 60 Tage, `bg-amber-50`-Kasten | Regel und Klassen von `/open` übernommen |
| AK-28 | Dasselbe Partial, Zweig unter der Schwelle | |
| AK-29 | Kuratierte Einträge tragen `…reason`, Community-Einträge einen Verweis | Zusammen mit AK-05 strukturell erzwungen |
| AK-30 | Locale-Präfix aus dem `controllers`-Loader, Anforderung `lb\|de\|fr\|en` | |
| AK-31 | Gemeinsamer Katalogtest über beide neuen Domains | Verhindert die Auslieferung, nicht nur die Anzeige |
| AK-32 | Derselbe Test — jeder im Template benutzte Schlüssel muss definiert sein | Vorsicht bei zusammengesetzten Schlüsseln, siehe BF-98 |
| AK-33 | `lang`-Attribut am Titel der Community-Eintragskarte, gespeist aus `BoardIdea::$locale` | Fund 1: im Board selbst fehlt es |
| AK-34 | Kein neues Muster; Fokus-, Kontrast- und Zielgrößenstandard aus Feature `02` | Nachweis ist ein axe-Lauf je Sprache in der QA |
| AK-35 | Spaltenblock stapelt unterhalb des Umbruchpunkts; keine Tabelle mit fester Breite | Lehre aus BF-77 — dort war eine Merkmalstabelle 525 px breit |
| AK-36 | Nur Standardelemente: Verweise und `<summary>` | |
| AK-37 | Je Spalte eine eigene `<section>` mit `h2` | Zugehörigkeit steht im Text, nicht in der Position |
| AK-38 | Eine `h1` je Seite, `h2` je Spalte bzw. Jahr, `h3` je Eintrag | |
| AK-39 | `submittedBy` wird nicht abgefragt und nicht übergeben | Strukturell: Das Datenpaket im Zwischenspeicher führt das Feld nicht |
| AK-40 | Redaktionelle Regel für die Domain `changelog`; der Katalogtest kennt sie nicht | **Wird in der QA gelesen, nicht vom Code erzwungen** — vermerkt, damit es niemand für abgesichert hält |
| AK-41 | Kein Protokollaufruf in Controller und Dienst; die Seiten schreiben nichts ins Log | Nachweis als Abwesenheit |
| AK-42 | Keine externe Ressource im Template | Nachweis über die Netzwerkliste im Browser |
| AK-43 | Filter in den Abfragekriterien, nicht im Template | Die einzige Zugriffsregel des Features |
| AK-44 | Der Controller liest keinen Query-Parameter | Muster und Begründung aus `PressController` |
| AK-45 | Begrenzung auf 10 in der Abfrage plus eine Zählabfrage | Nachweis über die Abfrageliste des Profilers |
| AK-46 | Pool `cache.roadmap`, Lebensdauer 3600 s | |
| AK-47 | Entity-Listener auf `BoardIdea`, `BoardVote` und gelöschtes `User` | Fund 3 |
| AK-48 | Keine eigene Speicherung; die Anzeige hängt nicht an `submittedBy` | Zustimmungszahl kann auf dem Kaskadenweg bis zu 3600 s zu hoch stehen — siehe **OF-07** |
| AK-49 | Das Feature legt nichts an, der Export bleibt unverändert | Ausdrücklich **keine** Änderung an Feature `01` |
| AK-50 | Kein Schlüssel, keine Umgebungsvariable, kein interner Pfad im Template | |
| AK-51 | Erklärsatz neben dem Repo-Verweis, Text in der Domain `changelog` | aus OF-05 |
| AK-52 | Zeile über der Community-Gruppe auf der Roadmap-Seite | aus OF-06, steht **immer**, auch bei weniger als zehn Ideen |

Keine Zeile ist leer. Zwei Kriterien sind ausdrücklich **nicht durch Code** gedeckt und
werden in der QA von Hand gelesen: **AK-20** (kein Codebegriff im Text) und **AK-40**
(keine Personennamen). Beide sind redaktionelle Zusagen an einen Text, den ein Prüflauf
nicht beurteilen kann — sie stehen hier, damit die QA sie nicht für abgesichert hält.

## Entschiedene Fragen

Die sechs aus `spec.md` sind seit dem 2026-08-30 entschieden und dort mit ihrer
Entscheidung festgehalten; zwei von ihnen haben je ein Kriterium nachgetragen (AK-51,
AK-52). Die Frage aus diesem Entwurf ebenso:

- **OF-07 · entschieden: Lebensdauer 3600 Sekunden.** Der Entity-Listener deckt
  Statuswechsel, Stimmen und Kontolöschungen ab; die Dauer ist nur das Netz für Wege,
  die niemand vorhergesehen hat. Fünf Minuten hätten zwölfmal so viele Abfragen
  gekostet, um ein Fenster zu verkürzen, das der Listener in aller Regel gar nicht
  erst entstehen lässt.

**Neu offen ist OF-08** (steht in `spec.md`): Die Startseite wirbt weiterhin mit
Bewertungen, die es nicht gibt. OF-02 hat das bewusst nicht aufgelöst — es gehört als
eigene Zeile ins Feature-Inventar, nicht in dieses Feature.
