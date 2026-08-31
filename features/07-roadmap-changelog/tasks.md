# 07 · Öffentliche Roadmap und Changelog — Aufgabenplan

Status: `tasked` · Stand: 2026-08-30 · alle offenen Fragen entschieden

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation. **Rot heißt anhalten.**

```bash
make fix-check                          # Exit 8 = etwas zu tun, nicht 1
php bin/console cache:warmup            # statt lint:container — siehe unten
php bin/console lint:twig templates/
php bin/phpunit --testsuite Unit
```

⚠ **`lint:container` ist in diesem Projekt vorbestehend rot** (Webauthn-Alias-Altlast) und
taugt nicht als Ebenen-Gate — festgestellt beim Bau von Feature `04`. `cache:warmup` steht
an seiner Stelle: Es baut denselben Container und schlägt fehl, wenn ein Dienst oder
Parameter nicht auflösbar ist.

⚠ **`doctrine:schema:validate` entfällt.** Dieses Feature legt keine Entität, keine Spalte
und keine Migration an. Der neue Entity-Listener hört nur zu; er ändert kein Mapping. Ein
Schema-Abgleich prüfte hier nichts und meldete vorbestehende Abweichungen, die nichts mit
dem Feature zu tun haben.

⚠ **Ab Ebene 4 gehört `npm run build` zur Verifikation.** Tailwind scannt die Templates —
zwei neue Seiten und zwei Partials bringen Klassen mit, die im gebauten CSS noch nicht
stehen. Die Lehre stammt aus Feature `04`, wo eine einzige neue Admin-Spalte
(`lg:table-cell`) einen Neubau erzwang, den der Plan nicht vorgesehen hatte.

**Es gibt keine Migration.** Ebene 1 ist Konfiguration und Grundstruktur.

---

## Ebene 1 · Fundament — Konfiguration und Grundstruktur

- [x] **T01** · Aufzählung `App\Roadmap\RoadmapStage` mit den drei Fällen
      `in_progress`, `planned`, `considered` — **die Reihenfolge der Fälle ist die
      Reihenfolge der Spalten**; dazu `transKey()`, `emoji()`, `badgeClasses()` nach dem
      Muster von `BoardIdeaStatus` — `AK-04`
- [x] **T02** · Unveränderliche Wertobjekte in `src/Roadmap/`: `RoadmapItem`
      (`key`, `stage`), `ShelvedItem` (`key`) als **eigener Typ**, `ReleaseNote`
      (`version`, `date`, `public`); dazu das Gerüst von `RoadmapRegistry` und
      `ChangelogRegistry` mit leeren Listen — *Grundlage für T07, T08*
- [x] **T03** `[P]` · Zwei Übersetzungsdomains anlegen: je vier Dateien
      `translations/roadmap.{lb,de,fr,en}.yaml` und
      `translations/changelog.{lb,de,fr,en}.yaml` mit dem gemeinsamen Gerüst
      (Seitentitel, Kurzbeschreibung, Spaltenüberschriften, Überschrift und Einleitung
      des Blocks „Bewusst nicht gebaut", Beschriftungen des Aktualitätshinweises,
      Verweistexte); beide Domains in `CatalogueCompletenessTest::DOMAINS` eintragen
      — `AK-30, AK-31, AK-32`
- [x] **T04** `[P]` · Zwei neue Schlüssel in `messages.{lb,de,fr,en}.yaml` für die
      Fußzeilenverweise (`footer.roadmap`, `footer.changelog`) — `AK-02`
- [x] **T05** `[P]` · Cache-Pool `cache.roadmap` in `config/packages/cache.yaml`
      (Dateisystem, **Lebensdauer 3600** — OF-07 am 2026-08-30 so entschieden) **samt `when@test`-Override auf
      `cache.adapter.array`** — ohne den Override sähe ein Test den Stand eines
      früheren — `AK-46`
- [x] **T06** `[P]` · Zwei sprachfreie Weiterleitungen `app_roadmap_redirect` und
      `app_changelog_redirect` in `config/routes.yaml` nach dem Muster von
      `app_open_redirect`, **nicht permanent**. ⚠ Es darf **kein** Verzeichnis
      `public/roadmap` und `public/changelog` entstehen — sonst wiederholt sich BF-100
      — `EC-10`

## Ebene 2 · Server — Inhalte, Abfragen, Zwischenspeicher, Prüfläufe

- [x] **T07** `[P]` · `RoadmapRegistry` füllen — **die Zuordnung steht seit dem
      2026-08-30** (VB-02 erfüllt): *In Arbeit* — Öffentliche Roadmap und Changelog;
      *Geplant* — Bewertungen und Kommentare, Kartenansicht, Favoriten; *Angedacht* —
      Native iOS-App, Chat-Widget, KI-Filter, Android-App mit Google- und Apple-Login;
      dazu die acht zurückgestellten Punkte aus `CLAUDE.md` als `ShelvedItem`.
      ⚠ **„Bewertungen und Kommentare" bekommt eine sachliche Begründung ohne Bezug auf
      das Werbeversprechen der Startseite** (OF-02) — der Widerspruch läuft als OF-08
      weiter und wird hier nicht halb aufgelöst — `AK-07, AK-08, EC-05`
- [x] **T08** `[P]` · `ChangelogRegistry` füllen: **alle** Versionen aus `CHANGELOG.md`
      als `ReleaseNote`, die stillen mit `public = false`; dazu die Gruppierung nach
      Jahren und das Datum des jüngsten öffentlichen Eintrags. **Der Umfang steht seit
      dem 2026-08-30** (VB-03 erfüllt): öffentlich werden die **neun** Releases mit
      sichtbarer Wirkung für Gäste — Community-Board, Presse-Kit, Vergleichsseiten,
      Vorschlags-Wizard, PWA und App-Anbindung, Küchen · Öffnungszeiten · Nahverkehr,
      Profil · Fotos, Filter, Fotogalerie —, davor **eine Sammelzeile** „Aufbau der
      Plattform" für Januar bis März 2026. Alles Übrige trägt den Vermerk still
      — `AK-21, AK-22, EC-06`
- [x] **T09** · Zwei Abfragemethoden in `BoardIdeaRepository`:
      `findPublishedPlanned(int $limit)` und `countPublishedPlanned()`. Filter:
      `publishedAt IS NOT NULL`, `duplicateOf IS NULL`, `status = Geplant`; Sortierung
      Zustimmungen absteigend, bei Gleichstand `publishedAt` absteigend. ⚠ **Gezählt
      wird über `COUNT(...) AS HIDDEN` mit `GROUP BY`, nicht über einen
      `addSelect`-Join** — sonst begrenzt `setMaxResults()` Zeilen statt Ideen (BF-64)
      — `AK-12, AK-13, AK-14, AK-17, AK-43, AK-45, EC-03`
- [x] **T10** · Dienst `App\Roadmap\CommunityRoadmap`: liefert höchstens
      `MAX_ITEMS = 10` geplante Ideen als **reine Skalarstruktur** (Kennung, Titel,
      Slug, Sprache, Zustimmungszahl) plus die Zahl der nicht gezeigten; Ergebnis im
      Pool `cache.roadmap`; `invalidate()`. Kein Verfasserfeld wird abgefragt oder
      weitergereicht. Ist der Zwischenspeicher leer, wird gerechnet — das ist das
      Standardverhalten und deckt EC-11 ohne Zusatzarbeit
      — `AK-15, AK-17, AK-39, AK-45, AK-46, EC-11`
- [x] **T11** · Entity-Listener `src/EntityListener/RoadmapCacheListener.php` mit
      `#[AsEntityListener]` nach dem Muster von `RestaurantImageFileListener`: verwirft
      den Pool bei Anlegen, Ändern und Entfernen von `BoardIdea` und `BoardVote`
      **sowie beim Entfernen eines `User`**. ⚠ Der Konto-Fall ist kein Beiwerk: Die
      Stimmen fallen dort über die Fremdschlüssel-Kaskade **in der Datenbank**, am
      Anwendungscode vorbei — ohne diesen Fall stünde bis zu eine Stunde lang eine zu
      hohe Zustimmungszahl auf der Seite — `AK-18, AK-47, AK-48, EC-02`
- [x] **T12** `[P]` · Prüflauf `tests/Unit/Roadmap/ChangelogCompletenessTest.php`:
      liest die `## [version]`-Überschriften aus `CHANGELOG.md`, lässt `[Unreleased]`
      außen vor und verlangt für jede Version einen Eintrag in `ChangelogRegistry` —
      öffentlich **oder** ausdrücklich still. **Gegenprobe ist Teil der Aufgabe:** Ein
      absichtlich entfernter Eintrag muss den Lauf rot färben, sonst prüft er nichts
      — `AK-26`
- [x] **T13** `[P]` · Prüflauf `tests/Unit/Translation/RoadmapCatalogueTest.php`: jeder
      `RoadmapItem` und jeder `ShelvedItem` hat `…title` **und `…reason`** in allen vier
      Sprachen, jeder öffentliche `ReleaseNote` seinen Titel und Text. ⚠ Mit
      **zusammengesetzten** Schlüsseln gegenprüfen — genau daran ging BF-98 durch beide
      bestehenden Läufe — `AK-05, AK-29, AK-31, AK-32`

## Ebene 3 · Schnittstellen

- [x] **T14** · `App\Controller\RoadmapController` mit zwei Aktionen: `index`
      (`/roadmap`, `app_roadmap_index`) und `changelog` (`/changelog`,
      `app_changelog_index`), beide `methods: ['GET']`. **Der Controller liest keinen
      Query-Parameter** — es gibt keinen Filter, keine Sortierung, keine Kennung. Keine
      `access_control`-Zeile ergänzen; die Begründung dafür gehört in den Klassenkopf,
      wie bei `PressController` — `AK-01, AK-11, AK-19, AK-44`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände. **Ladend entfällt hier ausdrücklich** — beide Seiten
werden serverseitig in einer Antwort gerendert; es gibt keinen Nachladeschritt.

- [x] **T15** `[P]` · Partial `templates/partials/_freshness.html.twig`: Datum des
      jüngsten öffentlichen Changelog-Eintrags, ab **60 Tagen** im hervorgehobenen
      `bg-amber-50`-Kasten statt im grauen Kleingedruckten. Regel und Klassen von
      `/open` übernehmen, nicht neu erfinden — `AK-27, AK-28`
- [x] **T16** `[P]` · Partial `templates/roadmap/_item.html.twig`: eine Eintragskarte in
      zwei Varianten. Kuratiert: Titel und Begründungssatz. Community: zusätzlich
      Zustimmungszahl, Verweis auf `app_board_show` und **`lang`-Attribut am Titel** aus
      `idea.locale`. ⚠ Das Board zeichnet heute nur die Beschreibung aus, nicht den
      Titel — hier wird es erstmals hergestellt, nicht übernommen
      — `AK-05, AK-15, AK-16, AK-33`
- [x] **T17** · Seite `templates/roadmap/index.html.twig`: Hero-Band,
      Aktualitätshinweis, drei Spalten **je als eigene `<section>` mit `h2`**, leerer
      Zustand je Spalte, Zeile „… und N weitere im Board", Block „Bewusst nicht gebaut"
      unter eigener Überschrift auf abgesetzter Fläche, Weiterleser-Block zu Changelog
      und Board. Über der Community-Gruppe steht **immer**, dass die zehn mit den
      meisten Zustimmungen erscheinen — auch bei weniger als zehn (OF-06). **Kein
      Datum, kein Quartal, keine Fortschrittsangabe an irgendeinem Eintrag**
      — `AK-03, AK-04, AK-06, AK-07, AK-08, AK-09, AK-10, AK-37, AK-38, AK-52, EC-01, EC-04`
- [x] **T18** · Seite `templates/roadmap/changelog.html.twig`: Hero-Band,
      Aktualitätshinweis, laufendes Jahr offen gerendert, frühere Jahre je als
      `<details>`/`<summary>` — **ohne JavaScript und ohne handgeschriebenes
      `aria-expanded`**, das sich ohne Skript nie aktualisieren ließe. Dazu der leere
      Zustand und der Verweis auf `CHANGELOG.md` samt Hinweis, dass sie nur auf Deutsch
      vorliegt — **dazu ein Satz, was den Leser dort erwartet** (vollständige technische
      Fassung aus Entwicklersicht, unkommentiert; OF-05)
      — `AK-20, AK-22, AK-23, AK-24, AK-25, AK-38, AK-51, EC-07`
- [x] **T19** · Zwei Verweise in **Spalte 4** der Fußzeile von
      `templates/base.html.twig` (bei Kontakt), nicht in Spalte 2. ⚠ Spalte 2 trägt
      bereits elf Einträge, und eine fünfte Spalte bräche das `lg:grid-cols-4`-Raster —
      die App-Hülle hat mit BF-80 schon eine offene Umbruchlücke — `AK-02`

## Ebene 5 · Feinschliff

- [x] **T20** · Funktionaler Lauf `tests/Functional/Controller/RoadmapControllerTest.php`:
      beide Seiten in vier Sprachen mit 200, POST ergibt 405, beliebige Query-Parameter
      ändern nichts und werden nicht zurückgegeben, eine nie freigegebene Idee steht an
      **keiner** Stelle des Quelltextes, sprachfreier Aufruf endet in **einem** Sprung
      ohne 301 — `AK-01, AK-11, AK-14, AK-19, AK-30, AK-43, AK-44, EC-10`
- [x] **T21** · Funktionaler Lauf für die Community-Spalte: elf geplante Ideen ergeben
      zehn Karten und den Hinweis auf **genau eine** weitere; Gleichstand stellt die
      neuere nach oben und ist zwischen zwei Aufrufen stabil; eine depublizierte Idee
      ist beim nächsten Aufruf verschwunden — `AK-17, AK-18, EC-02, EC-03, EC-04`
- [x] **T22** · Barrierefreiheit und Breite: bei **320 px** stapeln die drei Spalten und
      es entsteht kein Querscrollen; ein 120 Zeichen langer Ideentitel bricht um, statt
      die Spalte zu verbreitern; Emoji und arabische Schrift werden dargestellt, ohne
      die Leserichtung des Umfelds zu ändern; Tastaturweg über alle Verweise und
      `<summary>`-Elemente; eine `h1` je Seite, lückenlose Ebenen; axe-Lauf in vier
      Sprachfassungen — `AK-34, AK-35, AK-36, AK-37, AK-38, EC-08, EC-09`
- [x] **T23** · Läufe zum Aktualitätshinweis und zur Jahresgrenze: bei einem 10 Tage
      alten Eintrag ist der Hinweis **nicht** hervorgehoben, bei 61 Tagen ist er es;
      über den Jahreswechsel hinweg ist das neue Jahr offen und das vorherige
      zugeklappt — `AK-27, AK-28, EC-07`
- [x] **T24** · Läufe zur Abwesenheit: kein Aufruf schreibt eine personenbezogene Angabe
      ins Protokoll, kein Verfassername steht auf der Roadmap, die Seiten laden keine
      Ressource von einem fremden Host, im ausgelieferten Quelltext steht kein Schlüssel
      und kein interner Pfad, und der Datenexport aus Feature `01` bleibt unverändert
      — `AK-39, AK-41, AK-42, AK-49, AK-50`
- [x] **T25** · Dokumentation nachziehen: die **Release-Checkliste in `CLAUDE.md` um den
      fünften Punkt** ergänzen: öffentlicher Changelog-Eintrag in vier Sprachen, sonst
      Vermerk „still" — **und im selben Handgriff die Roadmap durchsehen** (OF-03), samt
      der Regel, dass ein Vorhaben nach zwölf Monaten ohne Fortschritt von „Geplant"
      nach „Angedacht" zurückwandert (OF-04). Dazu den Abschnitt zu diesem Feature;
      `docs/prd.md` verweist bei der Roadmap-Tabelle auf die öffentliche Seite;
      `docs/app-shell.md` führt die zwei neuen Fußzeilenverweise — `AK-26`
- [x] **T26** · `npm run build` ausführen und `public/build` mitcommitten. ⚠ Ohne diesen
      Schritt blockt `verify-assets` den Deploy, und die neuen Tailwind-Klassen der
      beiden Seiten fehlen im ausgelieferten CSS — *Grundlage für den Deploy*

---

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T14, T20 |
| AK-02 | T04, T19 |
| AK-03 | T17 |
| AK-04 | T01, T17 |
| AK-05 | T13, T16 |
| AK-06 | T17 |
| AK-07 | T07, T17 |
| AK-08 | T07, T17 |
| AK-09 | T17 |
| AK-10 | T17 |
| AK-11 | T14, T20 |
| AK-12 | T09 |
| AK-13 | T09 |
| AK-14 | T09, T20 |
| AK-15 | T10, T16 |
| AK-16 | T16 |
| AK-17 | T09, T10, T21 |
| AK-18 | T11, T21 |
| AK-19 | T14, T20 |
| AK-20 | T18 |
| AK-21 | T08 |
| AK-22 | T08, T18 |
| AK-23 | T18 |
| AK-24 | T18 |
| AK-25 | T18 |
| AK-26 | T12, T25 |
| AK-27 | T15, T23 |
| AK-28 | T15, T23 |
| AK-29 | T13 |
| AK-30 | T03, T20 |
| AK-31 | T03, T13 |
| AK-32 | T03, T13 |
| AK-33 | T16 |
| AK-34 | T22 |
| AK-35 | T22 |
| AK-36 | T22 |
| AK-37 | T17, T22 |
| AK-38 | T17, T18, T22 |
| AK-39 | T10, T24 |
| AK-40 | — |
| AK-41 | T24 |
| AK-42 | T24 |
| AK-43 | T09, T20 |
| AK-44 | T14, T20 |
| AK-45 | T09, T10 |
| AK-46 | T05, T10 |
| AK-47 | T11 |
| AK-48 | T11 |
| AK-49 | T24 |
| AK-50 | T24 |
| AK-51 | T18 |
| AK-52 | T17 |

| EC | Aufgaben |
|---|---|
| EC-01 | T17 |
| EC-02 | T11, T21 |
| EC-03 | T09, T21 |
| EC-04 | T17, T21 |
| EC-05 | T07 |
| EC-06 | T08 |
| EC-07 | T18, T23 |
| EC-08 | T22 |
| EC-09 | T22 |
| EC-10 | T06, T20 |
| EC-11 | T10 |

**AK ohne Aufgabe:** **AK-40** — „Der öffentliche Changelog nennt keine natürliche
Person." Das ist eine redaktionelle Zusage an einen Text, den kein Prüflauf beurteilen
kann: Ein Name unterscheidet sich für eine Maschine nicht von einem Produktnamen. Der
Entwurf führt es bereits als *nicht durch Code gedeckt*; der **Nachweis ist eine
Handprüfung in der QA**. Bewusst **keine** Alibi-Aufgabe dafür — eine Aufgabe, die
niemand erfüllen kann, sieht später aus wie eine Absicherung, die es nicht gibt.

⚠ **AK-20** („kein Klassenname, kein Dateipfad, kein Migrationsname im Text") trägt mit
T18 zwar eine Aufgabe, aber die baut nur die Seite — ob der **Text** die Zusage hält,
liest ebenfalls die QA. Hier steht es, damit es dort niemand für erledigt hält.

**Aufgabe ohne AK:** **T02** (Wertobjekte, Grundlage für T07 und T08) und **T26**
(Asset-Bau, Grundlage für den Deploy) — beide zulässig, weil sie andere Aufgaben erst
ermöglichen. Alle übrigen 24 Aufgaben tragen mindestens ein Kriterium.

## Parallelisierung

**Ebene 1 — T03, T04, T05, T06 laufen gleichzeitig.** Berührte Dateien:

| Aufgabe | Dateien |
|---|---|
| T03 | acht neue `translations/{roadmap,changelog}.*.yaml`, `tests/Unit/Translation/CatalogueCompletenessTest.php` |
| T04 | vier `translations/messages.*.yaml` |
| T05 | `config/packages/cache.yaml` |
| T06 | `config/routes.yaml` |

Keine Überschneidung. **T01 und T02 tragen kein `[P]`**: T02 verweist auf den in T01
angelegten Aufzählungstyp und muss danach laufen.

**Ebene 2 — T07, T08, T12, T13 laufen gleichzeitig; T09, T10, T11 danach seriell.**

| Aufgabe | Dateien |
|---|---|
| T07 | `src/Roadmap/RoadmapRegistry.php` |
| T08 | `src/Roadmap/ChangelogRegistry.php` |
| T12 | `tests/Unit/Roadmap/ChangelogCompletenessTest.php` |
| T13 | `tests/Unit/Translation/RoadmapCatalogueTest.php` |

⚠ **T12 liest `ChangelogRegistry`, T13 liest beide Registries** — beide nur lesend, und
das in T02 angelegte Gerüst genügt ihnen. Ein leerer Registry-Inhalt lässt sie zunächst
fehlschlagen; das ist beabsichtigt und der Beleg, dass sie prüfen. **T09 → T10 → T11**
bauen aufeinander auf (Abfrage → Dienst → Listener, der den Dienst verwirft) und laufen
deshalb nacheinander.

**Ebene 4 — T15 und T16 laufen gleichzeitig, T17, T18 und T19 danach.**

| Aufgabe | Dateien |
|---|---|
| T15 | `templates/partials/_freshness.html.twig` |
| T16 | `templates/roadmap/_item.html.twig` |

T17, T18 und T19 berühren zwar untereinander getrennte Dateien
(`roadmap/index.html.twig`, `roadmap/changelog.html.twig`, `base.html.twig`), binden aber
die Partials aus T15 und T16 ein und tragen deshalb **kein** `[P]` — sie liefen sonst
gegen Vorlagen, die es noch nicht gibt. Serielle Ausführung kostet hier Minuten, ein
halber Zusammenführungsstand kostet mehr.

**Ebene 5 — keine Parallelisierung.** T20 bis T24 sind zwar getrennte Testdateien,
messen aber dieselben zwei Seiten; sie laufen nacheinander, damit ein Fehlschlag
eindeutig einer Prüfung zuzuordnen ist. T25 und T26 stehen ohnehin am Ende.

## Vor dem Bauen

- [ ] Feature-Branch: `git checkout -b feature/07-roadmap-changelog`
- [x] **VB-02 geklärt** (2026-08-30): Spaltenzuordnung steht, siehe T07 — Bewertungen
      und Kommentare stehen unter „Geplant", ohne Bezug auf das Werbeversprechen
- [x] **VB-03 geklärt** (2026-08-30): neun merkbare Releases plus Sammelzeile, siehe T08
- [x] **OF-07 entschieden** (2026-08-30): Lebensdauer 3600 s, siehe T05
- [x] **OF-01 bis OF-06 entschieden** (2026-08-30) — zwei davon haben je ein Kriterium
      nachgetragen: **AK-51** (Erklärsatz am Repo-Verweis, T18) und **AK-52**
      (Auswahlregel ausgewiesen, T17)
- [ ] Test-Datenbank steht (`make test-db-setup`) — die Läufe aus T20, T21 und T23
      brauchen sie
- [ ] Kein Schlüssel und keine Umgebungsvariable nötig — dieses Feature hat keine

⚠ **VB-01 blockiert den Bau nicht, die Auslieferung schon.** Feature `06` liegt auf `dev`
und ist dort abgenommen; darauf lässt sich bauen. **Ausgeliefert werden darf `07` erst
nach `06`** — sonst zeigt die Roadmap eine Spalte ohne Quelle und die Fußzeile verweist
auf eine Route, die es auf Produktion nicht gibt.
