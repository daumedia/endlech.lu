# 16 · Roadmap und Ideen in einem Bereich — Aufgabenplan

Status: `tasked` · Stand: 2026-11-07

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft die Verifikation unten. **Rot heißt anhalten.**
Aufgaben mit Logik beginnen mit einem Test, der rot ist, bevor der Code ihn grün macht.

**Verifikation nach jeder Ebene** (aus dem Stack-Profil, an dieses Projekt angepasst —
hier baut AssetMapper, nicht Encore):

```bash
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console lint:yaml translations config
vendor/bin/php-cs-fixer fix --dry-run --diff     # Exit ≠ 0 = etwas zu tun
php bin/phpunit tests/Unit                        # Zahl ablesen — null ist rot
```

Nach Ebene 3 und vor der Übergabe zusätzlich `php bin/console tailwind:build` und die
**volle Suite** `php bin/phpunit`. `doctrine:schema:validate` entfällt (keine Entität
berührt), läuft aber trotzdem einmal am Ende mit.

## Ebene 1 · Fundament — Daten und Konfiguration

Keine Migration, keine Tabelle, keine Umgebungsvariable (`design.md`, Datenmodell).
Einzig die Texte müssen stehen, bevor Templates sie benutzen.

- [x] **T01** · Kataloge ergänzen und Texte aus OF-01 ändern, in allen fünf Sprachen
      (`lb`, `de`, `fr`, `en`, `pt`): Domain `roadmap` neu `area.name`,
      `area.tab.roadmap`, `area.tab.ideas`, `area.tab.changelog`; Domain `messages` neu
      `footer.roadmap_ideas`; geändert `roadmap.stage.planned.empty`,
      `roadmap.stage.considered.empty`, `roadmap.community.heading`,
      `roadmap.community.selection_hint`, `roadmap.community.more`,
      `roadmap.community.open_board`, `messages.board.back_to_board` — jeweils „Ideen"
      statt „Ideen-Board"/„Board". **Noch nichts löschen** (die alten Schlüssel werden
      noch benutzt, sonst färbt `CatalogueCompletenessTest` rot). — `AK-27, AK-37`

## Ebene 2 · Server — Logik und Validierung

**Keine Aufgabe.** Das Feature hat keine neue Geschäftslogik, keinen Dienst und keine
Eingabeprüfung (`design.md`, Überblick). Bewusst leer, nicht vergessen.

## Ebene 3 · Schnittstellen

- [x] **T02** · Präfix des `BoardController` von `/community/ideen` auf `/roadmap/ideen`
      umstellen, Routennamen bleiben. Alle Prüfläufe mit wörtlichem Pfad
      (13 Dateien, 44 Stellen: `BoardControllerTest`, `BoardInputHardeningTest`,
      `BoardLocaleTest`, `BoardTargetSizeTest`, `AdminBoardControllerTest`,
      `RoadmapControllerTest`, `RoadmapCardLayoutTest`, `HeadingOrderTest`,
      `EngagementHooksTest`, `SitemapGeneratorTest`, `SeoExtensionTest`,
      `UsageExtensionTest`, `Qa10SitemapBestandTest`) auf den neuen Pfad. Neu dazu:
      Mail-Link nach Freigabe zeigt auf `/roadmap/ideen/…` (AK-23); Sitemap ohne
      `/community/ideen` (AK-25); Zählaufruf mit `/de/roadmap/ideen?…` verliert den
      Abfrageteil (AK-31); Deckel am Konto greift unter der neuen Adresse (AK-34);
      Folgeseite nach Zustimmen und Zurückziehen unter `/roadmap/ideen` (AK-20).
      ⚠ Mechanisch, aber über drei Dateien: Die Testdateien ändern nur Pfade, die Logik
      bleibt. — `AK-04, AK-17, AK-18, AK-19, AK-20, AK-23, AK-25, AK-26, AK-31, AK-33,
      AK-34, AK-35, AK-36`
- [x] **T03** · Neuer `BoardLegacyRedirectController` unter dem `controllers`-Loader mit
      den vier GET-Routen aus `design.md` (301, Ziel aus festem Routennamen, `_locale`
      ausdrücklich übergeben, kein Abfrageparameter, keine Datenbankabfrage); die vier
      Routen in `SeoRegistry::UNLISTED_ROUTES` mit Begründung. Prüflauf
      `BoardLegacyRedirectTest` zuerst rot: fünf Sprachen × vier Adressen → 301 in einem
      Sprung aufs richtige Ziel; `?next=https://example.com`, `?url=//example.com` und
      `//example.com` im Pfad → Ziel bleibt endlech-intern; wartende Idee: Titel weder in
      Weiterleitung noch im Ziel; Testprotokoll nach 100 Aufrufen ohne Anzeigename und
      E-Mail. — `AK-21, AK-22, AK-24, AK-29, AK-30`, `EC-02, EC-03, EC-04, EC-06`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt. Hier wird an
bestehenden Seiten nur der Kopf getauscht; die Zustände aus 06 und 07 bleiben und werden
in jeder Aufgabe mitgeprüft (Tabelle „Vier Zustände" in `design.md`).

- [x] **T04** · Bereichskopf `templates/roadmap/_area_header.html.twig` (Bereichsname
      als Etikett, Knopf „Idee einreichen", optional `h1` + Einleitung, Reiterleiste mit
      `aria-current`, fett + 4-px-Unterstrich, `min-h-[44px]`, umbrechend). Eingebaut in
      `roadmap/index.html.twig` (Kopfband ersetzt, Block „Weiterlesen" entfernt, Hinweis
      „N weitere" als Link auf `app_board_index` mit `status=planned`) und
      `roadmap/changelog.html.twig` (Kopfband ersetzt). Schlüssel `eyebrow` (roadmap,
      changelog) und `more.*` (roadmap) in allen fünf Sprachen löschen. Prüflauf
      `RoadmapAreaTest` zuerst rot: Kopf auf beiden Seiten mit gleicher Reiterliste,
      aktiver Reiter, Knopf führt auf `/de/roadmap/ideen/neu`, eigene Titel, genau eine
      `h1`, kein „Weiterlesen", Link „weitere" mit `status=planned`, Verweis einer
      Community-Idee direkt auf `/de/roadmap/ideen/…`; Gast über den Knopf → Anmeldung
      mit **echtem** Formular → Einreichformular, unbestätigt → Bestätigungshinweis.
      Leerzustand der Spalten und der Community-Gruppe bleibt. — `AK-05, AK-06, AK-07,
      AK-10, AK-12, AK-13, AK-14, AK-15, AK-16`
- [x] **T05** `[P]` · Fußzeile: in „Mitmachen" `footer.roadmap_ideas` →
      `app_roadmap_index` statt `footer.feedback` → `app_board_index`; in „Über
      Endlech" Roadmap und Changelog entfernen; Kopfkommentar auf Feature 16
      umschreiben. Schlüssel `footer.feedback`, `footer.roadmap`, `footer.changelog` in
      allen fünf Sprachen löschen. Prüflauf `FooterRoadmapIdeasTest` zuerst rot: genau
      ein Verweis in den Bereich, in „Mitmachen", ohne `target`, landet auf
      `/de/roadmap`; kein Verweis auf `/changelog`, `/roadmap/ideen` oder
      `/community/ideen` in der Fußzeile. Den bestehenden Prüflauf zu 06/AK-80 ablösen
      (umschreiben, nicht löschen). — `AK-01, AK-02, AK-03`
- [x] **T06** · Bereichskopf in `board/index.html.twig` (Kopfband ersetzt, `h1` =
      `board.title`, Leitzahl in den Inhalt über die Steuerleiste) und
      `board/show.html.twig` (Kopf ohne `h1`, Rückweg „Zurück zu den Ideen");
      `board/new.html.twig` und `board/thanks.html.twig` behalten ihren Aufbau ohne
      Kopf, mit neuem Rückwegtext. `RoadmapAreaTest` erweitern: Reiter Ideen mit Kopf,
      aktiver Reiter „Ideen" auf Liste und Einzelseite, Leitzahl vorhanden, Leerzustand
      mit Weg zum Formular, kein Aktualitätshinweis; Formular und Bestätigung mit
      Rückweg auf `/de/roadmap/ideen`. Hängt an T04 (Include). — `AK-05, AK-06,
      AK-13, AK-17, AK-18, AK-19`, `EC-01, EC-07`

## Ebene 5 · Feinschliff

- [x] **T07** · Fünf Sprachen, drei Reiter + Einzelseite + Formular: kein roher
      Schlüssel (`area.`, `footer.`, `roadmap.`) im ausgelieferten HTML; an den
      OF-01-Stellen kein „Board"/„Ideen-Board" in der jeweiligen Sprache; keine
      Einbindung von fremdem Host auf `/de/roadmap/ideen` (bestehenden Prüflauf um die
      Adresse erweitern). Prüflauf `RoadmapAreaLocaleTest`. — `AK-27, AK-32, AK-37`
- [x] **T08** · Im Browser messen und den Kopf nachjustieren, bis es passt: 320 px in
      fünf Sprachen (kein Querscrollen, Reiter ≥ 44 × 44, Umbruch statt Abschneiden),
      1280 × 800 und 375 × 800 (Knopf und Überschrift „In Arbeit" ohne Scrollen),
      Tastatur von oben durch Reiter und Knopf mit sichtbarem Fokus, axe-core null
      Verstöße auf den fünf Seitenarten. Messwerte in den Abschlussbericht. — `AK-08,
      AK-09, AK-10, AK-11, AK-28`, `EC-05`
- [x] **T09** · Dokumentation nachziehen: `CLAUDE.md` (Routentabelle `/roadmap/ideen`,
      Hinweis auf die Weiterleitungen und den Bereichskopf), `docs/app-shell.md`
      (Fußzeilengruppen), `docs/prd.md` (Abschnitt Roadmap: Ort des Boards),
      `features/06-…/spec.md` und `features/07-…/spec.md` (sechs Kriterien
      durchgestrichen mit Verweis auf 16), `CHANGELOG.md` unter `[Unreleased]`. — keine
      AK (Projektkonvention „Bei Änderungen an Komponenten-Mustern die passende Datei
      mitziehen", Spec-Abschnitt „Verhältnis zu den Zusagen aus 06 und 07")

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T05 |
| AK-02 | T05 |
| AK-03 | T05 |
| AK-04 | T02 |
| AK-05 | T04, T06 |
| AK-06 | T04, T06 |
| AK-07 | T04 |
| AK-08 | T08 |
| AK-09 | T08 |
| AK-10 | T04, T08 |
| AK-11 | T08 |
| AK-12 | T04 |
| AK-13 | T04, T06 |
| AK-14 | T04 |
| AK-15 | T04 |
| AK-16 | T04 |
| AK-17 | T02, T06 |
| AK-18 | T02, T06 |
| AK-19 | T02, T06 |
| AK-20 | T02 |
| AK-21 | T03 |
| AK-22 | T03 |
| AK-23 | T02 |
| AK-24 | T03 |
| AK-25 | T02 |
| AK-26 | T02 |
| AK-27 | T01, T07 |
| AK-28 | T08 |
| AK-29 | T03 |
| AK-30 | T03 |
| AK-31 | T02 |
| AK-32 | T07 |
| AK-33 | T02 |
| AK-34 | T02 |
| AK-35 | T02 |
| AK-36 | T02 |
| AK-37 | T01, T07 |

| EC | Aufgaben |
|---|---|
| EC-01 | T06 |
| EC-02 | T03 |
| EC-03 | T03 |
| EC-04 | T03 |
| EC-05 | T08 |
| EC-06 | T03 |
| EC-07 | T06 |

**AK ohne Aufgabe:** keine (AK-01 bis AK-37 abgegangen).
**Aufgabe ohne AK:** T09 (Dokumentation) — zulässig, Projektkonvention; die abgelösten
Kriterien in 06/07 müssen sichtbar abgelöst sein, sonst prüft eine spätere QA gegen sie.

## Parallelisierung

Ebene 4: **T05** läuft gleichzeitig mit **T04** — T05 berührt
`templates/partials/_footer.html.twig`, `translations/messages.{lb,de,fr,en,pt}.yaml`
und `tests/Functional/…/FooterRoadmapIdeasTest.php` (+ den abgelösten 06/AK-80-Test);
T04 berührt `templates/roadmap/{_area_header,index,changelog}.html.twig`,
`translations/{roadmap,changelog}.{lb,de,fr,en,pt}.yaml` und
`tests/Functional/Controller/RoadmapAreaTest.php`. Keine gemeinsame Datei.
**T06** hängt am Include aus T04 und schreibt in dieselbe Testdatei — seriell danach.

Ebene 3: T02 und T03 **nicht** parallel — solange T02 den alten Präfix nicht abgelöst
hat, kollidieren die Weiterleitungsrouten aus T03 mit den alten Board-Routen.

## Vor dem Bauen

- [x] Feature-Branch: `feature/16-roadmap-und-ideen` (angelegt 2026-11-07, auf dem
      Footer-Branch `feature/feat/footer-herstellerhinweis-mwst`, weil beide die Fußzeile
      ändern — der Footer-PR wird zuerst gemergt)
- [ ] Test-Datenbank steht (`make test-db-setup`), Assets gebaut (`make assets`)
- [ ] Keine Schlüssel nötig (Spec, Abschnitt 6)

## Abschlussbericht · 2026-11-07

### 1 · Umgesetzt

Board, Roadmap und Changelog bilden den Bereich „Roadmap & Ideen“. Ein gemeinsamer Kopf
(`roadmap/_area_header.html.twig`) mit Bereichsname, Knopf „Idee einreichen“ und drei Reitern steht
auf `/roadmap`, `/roadmap/ideen` und `/changelog` sowie auf der Einzelseite einer Idee. Das Board
liegt unter `/roadmap/ideen`, die Routennamen blieben gleich. Die vier alten Seitenadressen unter
`/community/ideen` leiten mit 301 in einem Sprung weiter (`BoardLegacyRedirectController`), auch die
Links aus bereits verschickten Mails. Die Fußzeile führt mit einem Eintrag unter „Mitmachen“ hinein.
„Weiterlesen“ ist entfallen, der Hinweis auf weitere geplante Ideen führt auf den gefilterten Reiter.
Die OF-01-Texte sagen „Ideen“ in fünf Sprachen. **9 von 9 Aufgaben erledigt**, alle 37 Kriterien und
7 Randfälle haben Code oder Test (siehe Abdeckung oben).

Messwerte aus dem Browser (T08, Brave über playwright-core 1.62.1, axe-core 4.13.0, Dev-Server):

| Messung | Ergebnis |
|---|---|
| 320 px, 5 Sprachen × 4 Seitenarten (20 Seiten) | kein Querscrollen; kleinster Reiter 44 × 44 px (`Ideas`, en); keine Beschriftung abgeschnitten. **Erster Lauf: `Ideen`/`Idées`/`Ideas` nur 41–43 px breit** → `min-w-[44px]` ergänzt |
| Erster Bildschirm, 1280 × 800 und 375 × 800, 5 Sprachen × 3 Reiter (30) | Knopf spätestens bei 221 px, Überschrift „In Arbeit“ spätestens bei 689 px (Grenze 800); bei 375 px liegt sie auch über dem Cookie-Banner (`qa/16/AK-10-AK-11-roadmap-375x800.png`) |
| Tastatur, `/de/roadmap` | Knopf → Roadmap → Ideen → Changelog, je `outline: solid 2px`; Enter auf „Ideen“ öffnet `/de/roadmap/ideen` |
| Ohne JavaScript | alle drei Reiter wechseln, aktiver Reiter stimmt |
| axe-core, 5 Sprachen × 5 Seiten (25) | **erster Lauf: `heading-order` auf dem Reiter Ideen** (h1 → h3) → Leitzahl ist jetzt die `h2` der Liste. Danach je genau **ein** Treffer: `color-contrast` an der Wortmarke im Seitenkopf (siehe 2) |

### 2 · Offene Akzeptanzkriterien

**AK-28** (axe null Verstöße) — **nicht wörtlich erfüllt, Ursache außerhalb des Features.** Auf allen
25 Seiten meldet axe genau einen Verstoß `color-contrast` an der Wortmarke „Endlech.lu“ im Seitenkopf
(`base.html.twig:157`, `text-brand` `#01b6ed` auf Weiß, 2,35 : 1, nur ab 1280 px). Derselbe Treffer
steht auf `/de/about` und `/de/restaurants` (nachgemessen), er gehört zur App-Hülle und zur am
2026-09-15 entschiedenen Markenfarbe. WCAG 1.4.3 nimmt Logotypen ausdrücklich aus. Nicht angefasst
(Auftrag, Markenentscheidung). Als **OF-02** in `spec.md` mit Vorschlag zur Wertung.

Sonst keine.

### 3 · Getroffene Annahmen

- **OF-01 selbst entschieden** (unter dem Sitzungsziel „Feature 16 approved“, ohne Rückfrage): „Ideen“
  an den Verweisstellen, Changelog-Historie und Board-Überschrift unverändert. Luxemburgisch dabei auf
  „Iddi/Iddien“ vereinheitlicht wie im Katalog `messages` (der Katalog `roadmap` schrieb vorher
  „Ideeën“).
- **Der Verweis an jeder Community-Idee heißt jetzt „Zur Idee“** (vorher „Zum Ideen-Board“) — er führt auf
  die Idee selbst, nicht auf das Board.
- **Französische Reiterbeschriftung „Journal des modifications“** wie die Seitenüberschrift; bei 320 px
  bricht die Reiterleiste in zwei Zeilen um (EC-05 erlaubt das, gemessen: kein Querscrollen).
- **Die Leitzahl des Boards ist jetzt ein `h2`** (Text „N offene Ideen“), um die Lücke h1 → h3 zu
  schließen. Den Sprung gab es schon vor Feature 16; AK-13 verlangt die lückenlose Kette.
- **Testideen in der Entwicklungsdatenbank:** 15 Ideen (Slugs `qa16-0` … `qa16-14`) für die Messung
  angelegt, dazu eine ausstehende Migration lokal eingespielt (`Version20261022203034`). Nur Dev-DB.

### 4 · Systemweite Änderungen

- **Fußzeile auf jeder Seite:** „Feedback & Ideen“, „Roadmap“, „Changelog“ entfallen, „Roadmap & Ideen“
  neu (`footer.roadmap_ideas`). Schlüssel `footer.feedback`, `footer.roadmap`, `footer.changelog`,
  `roadmap.eyebrow`, `roadmap.more.*`, `changelog.eyebrow` in fünf Sprachen **gelöscht**.
- **Neue Routen** `app_board_legacy_*` (vier, GET) und Einträge in `SeoRegistry::UNLISTED_ROUTES`.
- **Neues Partial** `roadmap/_area_header.html.twig`, eingetragen in `docs/design-system.md`.
- **Dokumentation:** `CLAUDE.md` (Routentabelle, Abschnitt Feature 16), `docs/app-shell.md`, `docs/prd.md`,
  `CHANGELOG.md` (`[Unreleased]`); in `features/06-…/spec.md` AK-01, AK-02, AK-80 und in
  `features/07-…/spec.md` AK-02, AK-10 durchgestrichen, 07/AK-17 als **teilweise** abgelöst vermerkt
  (die Obergrenze von zehn gilt weiter — die Spec-Tabelle nannte es pauschal „abgelöst“).
- **Zwei abgelöste Bestandstests umgeschrieben, nicht gelöscht:** `RoadmapControllerTest::
  testFusszeileFuehrtUeberDieRoadmapZuBeidenSeiten` (07/AK-02) und `::testReiterFuehrenZuChangelogUndIdeen`
  (07/AK-10). 44 wörtliche Pfade in 13 Testdateien auf `/roadmap/ideen`. Zwei Negativprüfungen in
  `RoadmapEmptyStatesTest`/`RoadmapCommunityTest` auf die neue Gruppenüberschrift umgestellt (sonst wären
  sie nach der Textänderung blind grün gewesen).
- **Vorbestehend, nicht angefasst:** `lint:container` scheitert vor und nach dem Bau an
  `Webauthn\…\PublicKeyCredentialSourceRepositoryInterface` (Alias auf `WebauthnCredentialRepository`);
  `php-cs-fixer` ist im Projekt nicht installiert (`make fix` ruft ein fehlendes `vendor/bin/php-cs-fixer`).
- **Abhängigkeit:** Der Branch baut auf `feature/feat/footer-herstellerhinweis-mwst` auf (dieselbe Fußzeile).

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 85 (`RoadmapIdeenAdresseTest` 13, `BoardLegacyRedirectTest` 32, `RoadmapAreaTest` 24, `FooterRoadmapIdeasTest` 5, `RoadmapAreaLocaleTest` 10, `CollectPayloadNormalizerTest` +1) |
| davon rot gesehen, dann grün | 65 (13 + 29 + 18 + 5) |
| gelaufen in der letzten Verifikation | 1907, alle grün (28 übersprungen wie im Ausgangslauf: 1822) |

**Nie rot gesehen, mit Grund:**
- `BoardLegacyRedirectTest` — `Ak24…@Pfad`, `Ak24…@Pfad nach Nummer`, `Ec04…` (3): prüfen eine
  **Abwesenheit** (keine Route → 404); ohne Controller ebenso grün, das ist ihr Sinn. Gegenprobe mit
  abgeschaltetem Controller: alle übrigen 29 rot.
- `RoadmapAreaTest` — `Ak12UnbestaetigtesKonto…`, `Ak13EigeneTitelUndGenauEineH1`, `Ak15…`, `Ak19…`,
  `Ec01…`, `Ec07…` (6): Bestandsverhalten bzw. schon durch T01/T02 hergestellt, bevor der Test entstand.
  Sie sichern es gegen Rückfall.
- `RoadmapAreaLocaleTest` (10): T01 hatte die Texte schon geändert. **Gegenprobe:** `community.heading`
  fr auf „Issu du tableau d'idées“ gesetzt → `testAk37…@fr` rot; zurückgesetzt.
- `CollectPayloadNormalizerTest::testReiterIdeenZaehltOhneAbfrageteil` (1): Der Normalisierer ist
  pfadunabhängig, das Verhalten bestand.
- Zwei Tests aus dem ersten Lauf waren **zu schwach** und wurden geschärft: `Ak24…` (next/url/target)
  und `Ak30…` ließen einen 404 durchgehen und waren ohne Controller grün; jetzt verlangen sie 301.

**Aufgaben mit Logik ohne Test:** keine. Ohne automatischen Test, im Browser belegt: AK-08 (Breiten),
AK-09 (Tastatur), AK-10/AK-11 (erster Bildschirm), AK-28 (axe) — Werte oben.

⚠ **Falle beim Testen:** Der Test-Kernel prüft den Übersetzungscache nicht auf Frische. Nach der
Gegenprobe stand der verfälschte französische Text im Cache, obwohl die Datei zurückgesetzt war
(`var/cache/test/translations/catalogue.fr.*.php`) — ein Lauf war deshalb rot. `cache:clear --env=test`
behob es. Wer Katalogdateien für eine Gegenprobe ändert, leert danach den Testcache.

Übergabe an `/sdd-qa 16` in einer frischen Sitzung.

## Abschlussbericht · 2026-11-07 · Fehlerauftrag nach der ersten QA

Auftrag: BF-199, BF-200, BF-201, BF-202, BF-203 (nur Bereichskopf), BF-204, BF-205 aus `qa-report.md`,
freigegeben in `/sdd-klaeren 16` (Entscheidungen von Claude unter dem Sitzungsziel, vermerkt in Spec,
Entwurf und `befunde.md`). Behebungen sind unter den Fehlern in `qa-report.md` vermerkt.

### 1 · Umgesetzt

Sieben Befunde, nach Schwere: **BF-199** (*hoch*) — `vote()` prüft die Sichtbarkeit vor dem CSRF-Token,
eine wartende fremde Idee antwortet mit 404 statt mit einer Weiterleitung samt Titelteil. **BF-200** — der
Kopf ist umgebaut: Band mit Etikett und Knopf, darunter die Reiterleiste, darunter `h1` und Einleitung;
alle Reiter gleich fett. Die Leiste steht auf allen vier Seitenarten auf gleicher Höhe (gemessen, alle
fünf Sprachen, drei Breiten). **BF-202** — das Band ist einzeilig, der Knopf auf schmalen Schirmen
kompakt, der französische Reiter heißt „Journal“; „En cours“ steht bei 375 × 800 über dem Banner.
**BF-203** — Etikett auf dunklem Glas, ≥ 7,4 : 1. **BF-201** — sechs Texte in fünf Sprachen sagen „Ideen“
bzw. „Roadmap & Ideen“. **BF-205** — beide Tests werden bei ihrer Gegenprobe rot. **BF-204** — README.

### 2 · Offene Akzeptanzkriterien

Keine im Wortlaut der Spec. Zwei Dinge, die dicht daneben liegen:
- **320 × 800, erster Besuch, Französisch und Portugiesisch:** Das Cookie-Banner (ab 601 px) verdeckt die
  Überschrift „En cours“/„Em curso“ (Unterkante 652/659 px). AK-11 nennt 1280 × 800 und 375 × 800 — dort
  frei in allen Sprachen. Bei 320 px bricht das französische Band zweizeilig um (Etikett + Knopf passen
  nicht nebeneinander). Nicht weiter verkleinert, weil kein Kriterium es verlangt und der Knopf sonst unter
  die Tap-Mindestgröße ginge.
- **AK-28:** unverändert ein axe-Treffer an der Wortmarke der App-Hülle (OF-02, beantwortet).

### 3 · Getroffene Annahmen

- **Texte nach BF-201:** Wo eine Mail den Ort nennt, heißt er „Roadmap & Ideen“ (Bereich, den der Link
  öffnet); Betreffzeile ohne Ortsangabe („Deine Idee ist jetzt öffentlich“). Leerzustand „Noch keine
  Idee veröffentlicht“ statt „… unter ‚Ideen‘“ — der Leser steht bereits auf dem Reiter.
- **Französischer Reiter „Journal“**, Überschrift bleibt „Journal des modifications“ (Entwurf, Nachtrag).
- **Kopfbreite je Seite:** neuer optionaler Parameter `kopfbreite`, damit `h1` bündig mit dem Inhalt steht
  (Board `max-w-4xl`, Changelog `max-w-3xl`, Roadmap `container`).
- **Unbestätigtes Konto stimmt einer veröffentlichten Idee mit falschem Token zu:** jetzt 404 statt
  Weiterleitung mit Fehlermeldung (Folge von BF-199 — die Prüfung `isVerified()` steht in derselben Bedingung).

### 4 · Systemweite Änderungen

- **`config/packages/monolog.yaml`, `when@test`:** neuer Handler `pruefung` (`type: test`, `debug`). Nur im
  Test-Kernel; hält Protokollzeilen im Speicher, wird zwischen Anfragen geleert.
- **`BoardController::vote()`** (Feature 06, auf der Produktion): Reihenfolge der Prüfungen geändert.
- **Freigabe-Mail** (Feature 06): Betreff, Gruß und Text in fünf Sprachen neu.
- **QA-Test `Qa16RoadmapIdeenTest`:** beide Reproduktionen scharf (nicht mehr übersprungen); im
  BF-201-Test das Konto nach dem Kernel-Neustart neu geladen (der Test erreichte die Mailprüfung vorher nie).
- Doku: `docs/design-system.md`, `docs/app-shell.md`, `CLAUDE.md` auf den neuen Kopf; `README.md`.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 7 (`RoadmapAreaTest`: `testBf200…` ×4 Seitenarten, `testBf200AlleReiterGleichFett`, `testBf203…`; `RoadmapAreaLocaleTest::testBf202…`) |
| davon rot gesehen, dann grün | 7 |
| scharf geschaltet (QA) | 2 (`testBf199…`, `testBf201…`), beide vorher rot |
| geschärft | 2 (`testAk30…`, `testAk12UnbestaetigtesKonto…`), Gegenprobe jetzt rot |
| erweitert | `RoadmapAreaLocaleTest::testAk37…` (+6 Schlüssel), vorher rot |
| gelaufen in der letzten Verifikation | 1924, alle grün, 28 übersprungen (wie im Ausgangslauf) |

**Nie rot gesehen:** keiner der neuen. **Ohne automatischen Test, im Browser belegt:** Höhe der Leiste je
Seitenart, erster Bildschirm mit Banner, Pixelkontrast des Etiketts, axe (`qa/16/FA-*.json`).
`lint:twig`, `lint:yaml` grün; `lint:container` weiter vorbestehend rot (Webauthn-Alias).

Übergabe an `/sdd-qa 16` in einer frischen Sitzung.
