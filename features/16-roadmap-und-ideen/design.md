# 16 · Roadmap und Ideen in einem Bereich — Systemdesign

Status: `architected` · Stand: 2026-11-07 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Das Board zieht um, ohne sich zu ändern: Der Pfadpräfix des Board-Controllers wechselt
von `/community/ideen` auf `/roadmap/ideen`, **die Routennamen (`app_board_*`) bleiben**.
Damit folgen alle Stellen, die eine Adresse aus dem Namen erzeugen — Mails, Sitemap,
canonical, Verwaltung, Roadmap-Verweise —, ohne dass sie angefasst werden. Ein kleiner
neuer Controller beantwortet die vier alten Seitenadressen mit einer festen 301 auf die
neue. Ein gemeinsamer Bereichskopf (Name, Knopf „Idee einreichen", drei Reiter) ersetzt
die drei bisherigen Kopfbänder von Roadmap, Board und Changelog und steht zusätzlich über
der Einzelseite einer Idee. Die Fußzeile verliert drei Einträge und gewinnt einen.
Es entsteht keine Tabelle, kein Feld, keine Migration und kein Limiter.

## Seiten und Routen

Alle unter dem Sprachpräfix `/{_locale}` (`lb|de|fr|en|pt`) des `controllers`-Loaders.

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_roadmap_index` | `/roadmap` | Reiter Roadmap — **unverändert**, nur neuer Kopf, ohne „Weiterlesen" | öffentlich |
| `app_changelog_index` | `/changelog` | Reiter Changelog — **unverändert**, nur neuer Kopf | öffentlich |
| `app_board_index` | `/roadmap/ideen` **(neu, war `/community/ideen`)** | Reiter Ideen: Liste, Sortierung, Filter, Blättern | öffentlich |
| `app_board_new` | `/roadmap/ideen/neu` | Einreichformular | `ROLE_USER`, bestätigt (wie bisher, `#[IsGranted]` + Prüfung im Controller) |
| `app_board_thanks` | `/roadmap/ideen/eingereicht` | Bestätigungsseite nach dem Einreichen | `ROLE_USER` |
| `app_board_show` | `/roadmap/ideen/{id}-{slug}` | Einzelseite einer Idee | öffentlich; wartende nur für den Verfasser |
| `app_board_vote` | `/roadmap/ideen/{id}/zustimmen` (POST) | Zustimmen / zurücknehmen | `ROLE_USER`, bestätigt |
| `app_board_withdraw` | `/roadmap/ideen/{id}/zurueckziehen` (POST) | eigene wartende Idee zurückziehen | Verfasser |
| `app_board_legacy_index` **(neu)** | `/community/ideen` | 301 → `app_board_index` | öffentlich |
| `app_board_legacy_new` **(neu)** | `/community/ideen/neu` | 301 → `app_board_new` | öffentlich (Ziel prüft) |
| `app_board_legacy_thanks` **(neu)** | `/community/ideen/eingereicht` | 301 → `app_board_thanks` | öffentlich (Ziel prüft) |
| `app_board_legacy_show` **(neu)** | `/community/ideen/{id}-{slug}` | 301 → `app_board_show` mit derselben Nummer und demselben Titelteil | öffentlich (Ziel prüft) |

**Seite nach dem Erfolg:** Einreichen → `app_board_thanks` (bestehend). Zustimmen und
Zurückziehen → Einzelseite bzw. Liste unter `/roadmap/ideen` (bestehend, `zurueckZurIdee()`
erzeugt aus dem Namen).

**Nicht angelegt:** keine POST-Weiterleitung für `zustimmen`/`zurueckziehen` unter der
alten Adresse (Spec, Decision Log 12) — ein solcher Aufruf endet in 404. Kein sprachfreier
Kurzlink `/roadmap/ideen` (Spec, Nicht im Scope).

Die Routen der Verwaltung (`/admin/ideen`) und der sprachfreie Kurzlink `/roadmap`
(`LocaleRedirectController`, 302) bleiben, wie sie sind.

## Komponentenstruktur

Wiederverwendet und nur benannt: `partials/_board_idea_card.html.twig`,
`roadmap/_item.html.twig`, `partials/_freshness.html.twig`, `partials/_footer.html.twig`.

```
Bereichskopf  (neues Include `roadmap/_area_header.html.twig`, Parameter: aktiver Reiter,
               optional Überschrift + Einleitung der Seite)
├── Kopfband          Verlauf cyan-700 → purple-800 wie bisher, aber niedriger (py-8 md:py-10)
│   ├── Bereichsname       „Roadmap & Ideen" als Etikett (Pill-Stil der bisherigen
│   │                      Eyebrows), KEIN h1 — ersetzt „Was wir bauen" und das Changelog-Etikett
│   ├── Knopf               „Idee einreichen" → app_board_new, weiß auf Verlauf, min-h 48
│   └── h1 + Einleitung     nur wenn übergeben (Roadmap, Ideen, Changelog); auf der
│                           Einzelseite einer Idee entfällt beides
└── Reiterleiste      <nav> mit Bereichsname als aria-label, weißer Grund, Unterkante
    └── drei Links        Roadmap · Ideen · Changelog, je min-h 44, umbrechend;
                          aktiver: aria-current="page", fett, 4-px-Unterstrich cyan-700
```

**Reiter Roadmap** (`roadmap/index.html.twig`)
```
├── Bereichskopf (aktiv: roadmap, h1 „Roadmap", Einleitung wie bisher)
├── Spalten              unverändert: Hinweis „kein Datum", Aktualität, drei Spalten,
│   └── Community-Gruppe    unverändert bis auf: Hinweis „N weitere" wird ein Link auf
│                           app_board_index mit status=planned (AK-16)
├── Bewusst nicht gebaut unverändert
└── (Weiterlesen)        ENTFÄLLT samt den Schlüsseln more.*
```

**Reiter Ideen** (`board/index.html.twig`)
```
├── Bereichskopf (aktiv: ideas, h1 = board.title, Einleitung = board.intro)
├── Leitzahl              Zahl der offenen Ideen + board.lead_label — aus dem alten Kopfband
│                         in den Inhalt gezogen, über der Steuerleiste
├── Steuerleiste / Liste / Blätterung / Leerzustand / „Umgesetzt"   unverändert
```

**Reiter Changelog** (`roadmap/changelog.html.twig`)
```
├── Bereichskopf (aktiv: changelog, h1 + Einleitung wie bisher)
└── Einträge             unverändert
```

**Einzelseite einer Idee** (`board/show.html.twig`)
```
├── Bereichskopf (aktiv: ideas, ohne h1 und Einleitung)
├── Rückweg              „Zurück zu den Ideen" (neuer Text, OF-01)
└── Karte, Team-Antwort, Zurückziehen   unverändert
```

**Formular und Bestätigung** (`board/new.html.twig`, `board/thanks.html.twig`): kein
Bereichskopf (Spec, Decision Log 14), Rückweg mit neuem Text „Zurück zu den Ideen".

**Fußzeile** (`partials/_footer.html.twig`): In „Mitmachen" ersetzt
`Roadmap & Ideen → app_roadmap_index` den Eintrag `Feedback & Ideen → app_board_index`; in
„Über Endlech" entfallen „Roadmap" und „Changelog". Der Kopfkommentar mit den Zusagen
(06/AK-80, „Feature 07: Roadmap + Changelog") wird auf Feature 16 umgeschrieben.

**Vier Zustände je Bildschirm:**

| Bildschirm | leer | ladend | Fehler | gefüllt |
|---|---|---|---|---|
| Reiter Roadmap | Leertext je Spalte (07/AK-09), keine Community-Gruppe ohne Ideen (07/EC-01) | entfällt — serverseitig gerendert, kein Nachladen | unverändert: Cache-Ausfall → direkte Abfrage (07/EC-11); DB-Fehler → Fehlerseite | Spalten + Gruppe |
| Reiter Ideen | Leerzustand aus 06 mit Weg zum Formular (EC-01); Kopf-Knopf zusätzlich | entfällt | unverändert (06) | Liste |
| Reiter Changelog | Leerzustand aus 07/AK-25 | entfällt | unverändert | Einträge |
| Alte Adresse | — | — | unbekannte Nummer → 301, dann 404 am Ziel | 301 |

### Nachtrag 2026-11-07 · Kopf nach der ersten QA (BF-200, BF-202, BF-203)

Entschieden in `/sdd-klaeren 16` (Decision Log 19 der Spec). **Ersetzt den Baum „Bereichskopf“ oben**:

```
Bereichskopf
├── Kopfband          Verlauf wie bisher, aber nur noch EINE Zeile: Etikett + Knopf — auf jeder
│   │                 Seitenart gleich hoch, deshalb steht die Reiterleiste überall an derselben Stelle
│   ├── Etikett            „Roadmap & Ideen“, dunkles Glas (bg-black/20) statt bg-white/20 → ≥ 4,5 : 1 (BF-203)
│   └── Knopf               „Idee einreichen“; auf schmalen Schirmen kompakter, damit beide in eine Zeile passen
├── Reiterleiste      wie bisher, aber alle Reiter gleich fett (semibold) — der aktive unterscheidet sich
│                     durch Unterstrich und Farbe, nicht durch Breite (kein waagerechtes Springen)
└── Seitenkopf        h1 + Einleitung der Seite, UNTER der Reiterleiste auf weißem Grund
                      (entfällt auf der Einzelseite einer Idee)
```

Für BF-202 zusätzlich: Reiterbeschriftung Französisch „Journal“ statt „Journal des modifications“ (die
Leiste bleibt bei 375 px einzeilig); die Seitenüberschrift des Changelogs bleibt ausgeschrieben.
Gemessen wird gegen den ersten Bildschirm **mit** Cookie-Banner.

## Datenmodell

**Keine Änderung.** Keine Tabelle, kein Feld, keine Migration. `board_idea` und
`board_vote` bleiben, wie `docs/data-model.md` sie beschreibt; der Titelteil der Adresse
kommt weiter aus `board_idea.slug`.

## Zugriffsregeln

Die bestehenden Regeln von Feature 06 ziehen unverändert mit — sie hängen an Controller-
Attributen und Prüfungen im Controller, **nicht** an Pfaden in `access_control`
(`security.yaml` hat keine Zeile für `/community` oder `/roadmap`; nachgesehen
2026-11-07). Der Umzug kann sie deshalb nicht aushebeln.

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | drei Reiter, Einzelseiten **veröffentlichter** Ideen, alte Adressen (nur 301) | nichts | `BoardController::show()` wirft 404 für wartende Ideen, deren Verfasser er nicht ist (BF-111-Bedingung bleibt); Liste fragt nur veröffentlichte ab |
| Angemeldet, unbestätigt | wie Gast + eigene wartende Idee | nichts — Formular leitet auf den Bestätigungshinweis, Zustimmen → 404 | `#[IsGranted('ROLE_USER')]` + `isVerified()` im Controller (unverändert) |
| Angemeldet, bestätigt | wie oben | eigene Ideen einreichen, zustimmen, eigene wartende zurückziehen | `#[IsGranted]`, CSRF-Token je Idee, Besitzprüfung vor CSRF (unverändert) |
| Admin | wie oben + `/admin/ideen` | Freigabe, Status, Antwort | `access_control` `^/[a-z]{2}/admin` → `ROLE_ADMIN` (unverändert) |

**Weiterleitungen:** Ziel ist immer eine aus einem **festen Routennamen** erzeugte Adresse
derselben Anwendung; der Controller liest keinen Abfrageparameter und reicht keinen
weiter (AK-24). Für die Einzelseite übernimmt er nur `id` (Ziffern, durch das
Routen-Requirement erzwungen) und `slug` (ohne `/`, ebenso). Er fragt die Datenbank nicht
ab — er verrät also nicht, ob es eine Nummer gibt; das entscheidet erst das Ziel mit 404.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `app_board_new` (POST) | `board_submit`, am **Konto** — unverändert | 429 mit Wartezeit in Minuten (BF-101) | `framework.yaml`, `when@test` 10000 — unverändert |
| `app_board_vote` (POST) | `board_vote`, am **Konto** — unverändert | Flash mit Wartezeit (BF-102) | `framework.yaml` — unverändert |
| Drei Reiter, Einzelseite | **keins** | — | Lesend; Board blättert mit 20 je Seite, Roadmap fragt höchstens zehn ab (07/AK-45) |
| Vier alte Adressen | **keins** | — | Keine Abfrage, kein Mailversand, kein Bestand — die Konvention aus `CLAUDE.md` greift nicht (Spec, Decision Log 9) |

Da die Deckel am Konto zählen, ist ihr Zustand vom Pfad unabhängig — der Umzug eröffnet
kein frisches Kontingent (AK-34), ohne dass etwas zu tun ist. Ein Prüflauf weist es nach.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| Umami (selbst betrieben, Feature 11) | Seitenaufrufe | wie bisher: Pfad, Herkunftsdomain, Sprache, Bildschirmgröße — jetzt mit `/…/roadmap/ideen` statt `/…/community/ideen` | unverändert durch `CollectPayloadNormalizer`: **Abfrageteil**, `id`/`tag`, Verwaltungs-/Profil-/Token-Pfade. Keine Regel nennt `/community/ideen` (nachgesehen 2026-11-07: weder `src/Usage/` noch `assets/usage/before_send.ts` noch `growth/config.json`), also muss keine nachziehen |

Kein weiterer Dienst. Mails gehen wie bisher über Brevo; ihr Inhalt ändert sich nur in der
Adresse, die aus dem Routennamen erzeugt wird.

## Folgen an anderer Stelle (Bestand)

| Stelle | Was passiert | Handlung |
|---|---|---|
| `src/Seo/SeoRegistry.php` | `app_board_index` bleibt angeboten, `app_board_new`/`_thanks` bleiben ausgeschlossen, `app_board_show` bleibt „weder noch", `app_board_index` bleibt blätternd — alles über den Namen, zieht mit | Die vier neuen Weiterleitungsrouten in `UNLISTED_ROUTES` eintragen (Begründung: „leitet dauerhaft weiter, keine eigene Seite") — sonst wird `SeoRouteCoverageTest` rot |
| `BoardNotifier` | erzeugt `ideaUrl` aus `app_board_show` | nichts — AK-23 folgt aus dem Namen |
| `CommunityRoadmap` (Cache) | speichert `id`/`slug`, keine Adressen | nichts — kein Cache-Leeren beim Ausrollen nötig |
| `public/robots.txt` | nennt `/community/ideen` nicht (nachgesehen) | nichts |
| `RouteDirectoryCollisionTest` | `public/roadmap` darf nicht entstehen | nichts anlegen |
| Tests mit wörtlichen Pfaden | 44 Stellen in 13 Dateien nennen `/community/ideen` | auf `/roadmap/ideen` umstellen; Stellen, die das Weiterleiten prüfen, bleiben alt |
| Katalog `roadmap` (5 Sprachen) | `more.*` entfällt; `eyebrow` entfällt; neu `area.*`; OF-01-Texte geändert | `RoadmapCatalogueTest` + `CatalogueCompletenessTest` halten die fünf Kataloge gleich |
| Katalog `changelog` | `eyebrow` entfällt | ebenso |
| Katalog `messages` | `footer.feedback`, `footer.roadmap`, `footer.changelog` entfallen, `footer.roadmap_ideas` neu; `board.back_to_board` neuer Text | ebenso |
| `CLAUDE.md`, `docs/app-shell.md`, `docs/prd.md` (Abschnitt Roadmap) | Routentabelle, Fußzeilengruppen, Hinweise auf `/community/ideen` | nachziehen (CLAUDE.md-Konvention „Bei Änderungen an Komponenten-Mustern die passende Datei mitziehen") |
| `features/06-…/spec.md`, `features/07-…/spec.md` | sechs Kriterien abgelöst | durchstreichen, Verweis auf 16 |
| `CHANGELOG.md` | Eintrag unter `[Unreleased]` | ergänzen; die öffentliche Changelog-Zeile entsteht beim Release (Release-Punkt 5) |

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Präfix des `BoardController` umstellen, **Routennamen behalten** | Neue Namen (`app_ideas_*`) | Jede Stelle, die eine Adresse erzeugt — Mails, Sitemap, canonical, Admin, Roadmap — folgt ohne Änderung. Neue Namen hätten ~20 Stellen berührt, jede eine Gelegenheit, eine zu vergessen |
| 2 | Weiterleitungen als eigener kleiner Controller unter dem `controllers`-Loader | `RedirectController` von Symfony in `routes.yaml` | Ein YAML-Block bräuchte ein eigenes `_locale`-Requirement — eine weitere Stelle, die bei „Eine Sprache hinzufügen" von Hand mitmuss (`CLAUDE.md` zählt sie auf). Unter dem Loader erbt der Controller Präfix und Requirement |
| 3 | **301**, obwohl die sprachfreien Kurzlinks bewusst 302 sind | 302 wie die Kurzlinks | Die Kurzlinks führen je Besucher in eine andere Sprache — ein gespeicherter 301 wäre falsch. Hier hängt das Ziel nur an der Adresse selbst (`/de/…` → `/de/…`); ein gespeicherter 301 ist richtig und lässt die neue Adresse die alte im Index ablösen (Spec, Decision Log 10). Die BF-100-Warnung betrifft Schleifen mit `mod_dir`; `/roadmap/ideen` ist kein Verzeichnis |
| 4 | Abfrageparameter werden **nicht** weitergereicht | Sortierung/Status/Seite übernehmen | Die Spec sagt es nicht zu (EC-06). Ohne Weiterreichen ist AK-24 strukturell erfüllt: Es gibt nichts aus der Anfrage, das im Ziel landen könnte |
| 5 | Weiterleitung der Einzelseite **ohne Datenbankabfrage** | Idee laden, nur bei Existenz weiterleiten | Billiger, kein Bestand, und es verrät nichts: Ob eine Nummer existiert oder wartet, entscheidet erst das Ziel (404, AK-33). Eine Dublette kostet dadurch einen zweiten Sprung (EC-03) |
| 6 | Bereichskopf als **Include mit Parametern** | Eigenes Zwischen-Layout, das die vier Seiten erweitern | Dasselbe Muster wie `partials/_form_field.html.twig`; ein Zwischen-Layout zwänge Board- und Roadmap-Templates in eine gemeinsame Blockstruktur, die sie heute nicht haben |
| 7 | Reiter als **gewöhnliche Links mit `aria-current="page"`** | ARIA-Tab-Muster (`role="tablist"`, Pfeiltasten) | Jeder Reiter ist eine eigene Seite. Das Tab-Muster beschreibt umschaltbare Flächen innerhalb einer Seite und verlangt JavaScript für die Tastaturführung — AK-07 verlangt Funktion ohne JavaScript |
| 8 | Aktiver Reiter: fett **und** 4-px-Unterstrich, nicht nur Farbe | Nur Farbwechsel | AK-06; Design-System „Farbe trägt nie allein" |
| 9 | **Kein `h1` im Kopf**; jede Seite bringt ihre eigene | Bereichsname als `h1` | Der Bereichsname als `h1` machte jede Seitenüberschrift zur `h2` und ließe die Einzelseite ohne eigene Hauptüberschrift — AK-13 verlangt genau eine `h1` je Reiter und eigene Titel |
| 10 | Leitzahl des Boards wandert aus dem Kopf in den Inhalt | Im Kopf nur auf dem Reiter Ideen | AK-05 verlangt den Kopf auf allen Reitern gleich; AK-10/AK-11 verlangen Knopf und „In Arbeit" im ersten Bildschirm bei 800 px Höhe — die große Zahl im Kopf kostete ~90 px |
| 11 | Bereichsname ersetzt die Etiketten „Was wir bauen" (Roadmap) und das Changelog-Etikett | Etiketten behalten, Name zusätzlich | AK-05 („wortgleich und an derselben Stelle"); zwei Etiketten übereinander wären Lärm und kosteten Höhe |
| 12 | `footer.feedback`, `footer.roadmap`, `footer.changelog`, `more.*` und die beiden `eyebrow` werden **gelöscht**, nicht stehengelassen | Ungenutzt stehen lassen | Ein ungenutzter Schlüssel sieht wie ein vergessener Weg aus; fünf Kataloge × acht Schlüssel Pflege für nichts |

## Abdeckung der Akzeptanzkriterien

Abgegangen aus `spec.md`, AK-01 bis AK-37 der Reihe nach.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Fußzeile: „Mitmachen" → `Roadmap & Ideen` → `app_roadmap_index` | Funktionstest auf einer beliebigen Außenseite |
| AK-02 | Fußzeile: drei Einträge entfernt | Funktionstest zählt Verweise auf `/roadmap`, `/changelog`, `/roadmap/ideen` in der Fußzeile: genau einer |
| AK-03 | Fußzeile: einziger Rückmeldeweg, kein `target` | löst den Test zu 06/AK-80 ab |
| AK-04 | Routen `app_roadmap_index`, `app_board_index`, `app_changelog_index`, alle ohne Anmeldung | Funktionstest, fünf Sprachen |
| AK-05 | Bereichskopf auf Roadmap, Ideen, Changelog, Einzelseite | Funktionstest vergleicht Reiterliste je Seitenart |
| AK-06 | Reiterleiste: `aria-current="page"`, fett + Unterstrich | Funktionstest (Attribut); Sichtprüfung im Browser |
| AK-07 | Reiter als Links auf eigene Routen | Funktionstest (href); ohne JS: Links, keine Skriptabhängigkeit |
| AK-08 | Reiterleiste umbrechend, `min-h-[44px]` | Browsermessung bei 320 px, fünf Sprachen (QA) |
| AK-09 | Links und Knopf als native Elemente, `focus:outline-2` | Tastaturprüfung im Browser (QA) |
| AK-10 | Knopf im Kopfband oben, Kopfband niedriger (Entscheidung 10) | Browsermessung 1280 × 800 und 375 × 800 (QA) |
| AK-11 | Kopfband niedriger, Leitzahl nicht im Kopf | Browsermessung wie AK-10 |
| AK-12 | Knopf → `app_board_new` (`#[IsGranted]`) → Anmeldung merkt Ziel → Formular; unbestätigt → Bestätigungshinweis | Funktionstest mit **echtem** Anmeldeformular (`loginUser()` löst kein Anmeldeereignis aus, siehe Feature 12) |
| AK-13 | `title`-Blöcke je Seite unterschiedlich; Entscheidung 9 | Funktionstest; `HeadingOrderTest` um neue Pfade |
| AK-14 | `roadmap/index.html.twig` ohne „Weiterlesen" | Funktionstest |
| AK-15 | `roadmap/_item.html.twig` erzeugt `app_board_show` → neuer Pfad direkt | Funktionstest: href beginnt mit `/de/roadmap/ideen/` |
| AK-16 | Community-Gruppe: Hinweis „N weitere" als Link `app_board_index` + `status=planned` | Funktionstest mit 12 geplanten Ideen |
| AK-17 | `board/index.html.twig` unverändert bis auf Kopf und Leitzahl-Ort | bestehende Board-Tests laufen gegen den neuen Pfad |
| AK-18 | Präfix `/roadmap/ideen`; Bereichskopf auf `board/show` | Funktionstest |
| AK-19 | Präfix; Rückweg in `new`/`thanks` | bestehende Einreichtests gegen neuen Pfad + Prüfung des Rückwegs |
| AK-20 | `zurueckZurIdee()` und Rückleitung nach dem Zurückziehen über Routennamen | Funktionstest prüft `Location` |
| AK-21 | Weiterleitungs-Controller, drei Seitenrouten, 301 | Funktionstest, fünf Sprachen |
| AK-22 | Weiterleitungs-Controller, Einzelseite, 301, `id` + `slug` übernommen | Funktionstest, fünf Sprachen |
| AK-23 | `BoardNotifier` über Routennamen | Mailtest prüft Link im Rumpf |
| AK-24 | Entscheidung 4: kein Abfrageparameter gelesen, Ziel aus festem Namen | Funktionstest mit `?next=https://…`, `?url=//…` und `//` im Pfad |
| AK-25 | `SeoRegistry` über Routennamen | `SitemapGeneratorTest`/`Qa10SitemapBestandTest` auf neue Pfade; Gegenprobe: kein `/community/ideen` |
| AK-26 | `SeoExtension` über Routennamen, `PAGINATED_ROUTES` unverändert | `Qa10SitemapBestandTest` auf neuen Pfad |
| AK-27 | neue Schlüssel `area.*`, `footer.roadmap_ideas` in fünf Katalogen | `CatalogueCompletenessTest`, `RoadmapCatalogueTest`; Funktionstest „kein roher Schlüssel" |
| AK-28 | bestehende Bauweise (Kontrast, Fokus, Überschriften) | axe-core im Browser, fünf Sprachen (QA) |
| AK-29 | Sichtbarkeitsregeln aus 06 unverändert; Weiterleitung lädt nichts | Funktionstest: wartender Titel nicht im Quelltext von Reitern und alten Adressen |
| AK-30 | Kein neues Logging; Weiterleitung schreibt nichts | Funktionstest liest das Testprotokoll nach 100 Aufrufen |
| AK-31 | `CollectPayloadNormalizer` unverändert | Prüflauf mit `/de/roadmap/ideen?sort=newest&status=planned` als Pfad: Abfrageteil entfernt |
| AK-32 | keine neue Ressource; Bereichskopf ohne externe Einbindung | bestehender „keine fremde Ressource"-Prüflauf um `/roadmap/ideen` |
| AK-33 | `BoardController::show()` unverändert (BF-111-Bedingung) | Funktionstest unter neuem Pfad, Gast und fremdes Konto |
| AK-34 | Limiter am Konto, pfadunabhängig | Funktionstest: Kontingent über alten Weg nicht erreichbar, über neuen erschöpft → Meldung |
| AK-35 | Blätterung 20/Seite, `CommunityRoadmap::MAX_ITEMS` 10 | bestehende Tests gegen neuen Pfad |
| AK-36 | Datenexport unverändert | bestehender Exporttest; keine Änderung an `AccountDataExporter` |
| AK-37 | OF-01-Texte in `roadmap.*` und `board.back_to_board`, fünf Sprachen | Funktionstest: gerenderte Stellen ohne „Board"/„Ideen-Board" je Sprache |

Keine Zeile leer.
