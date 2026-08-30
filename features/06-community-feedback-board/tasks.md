# 06 · Community Feedback Board — Aufgabenplan

Status: `tasked` · Stand: 2026-08-30 · Stack-Profil: `symfony-doctrine`

Ebenen laufen in Reihenfolge. `[P]` heißt: innerhalb dieser Ebene unabhängig von den
anderen `[P]`-Aufgaben, darf parallel an einen Subagenten gehen.

Nach jeder Ebene läuft der Verifikationsblock. **Rot heißt anhalten** — nicht „das räumen
wir in Ebene 5 auf".

```bash
make fix-check                            # ⚠ Exit 8 heißt „etwas zu tun", nicht 1
php bin/console lint:container
php bin/console lint:twig templates/
php bin/console doctrine:schema:validate  # ab Ebene 1
php bin/phpunit
```

`npm run build` entfällt: Das Feature kommt ohne Änderung unter `assets/` aus. Entsteht
doch eine, gehört der Bau **und** der committete `public/build` dazu — sonst blockt
`verify-assets` den Deploy.

---

## Ebene 1 · Fundament — Daten und Konfiguration

- [x] **T01** `[P]` · Enum `src/Enum/BoardIdeaStatus.php`: fünf Fälle (`new`, `reviewing`,
      `planned`, `done`, `declined`) mit `transKey()`, `label()`, `emoji()`,
      `badgeClasses()`. ⚠ `badgeClasses()` liefert **nur Farbe**, Form bleibt im Template
      (Design-System) — `AK-31`
- [~] **T02** → **nach Ebene 3 verschoben (T18a), am 2026-08-30 beim Bau korrigiert.**
      ⚠ **Die Ebenengrenze war falsch geschnitten.** `LimiterCoverageTest` verlangt, dass
      jeder konfigurierte Limiter auch verdrahtet ist — nach Ebene 1 kann das
      strukturell nicht erfüllt sein, und die Suite wäre zwei Ebenen lang rot gewesen.
      CLAUDE.md sagt dasselbe: „Wer einen solchen Weg neu anlegt, legt den Limiter **im
      selben Commit** an." Konfiguration und Verdrahtung gehören deshalb zusammen —
      `AK-59, AK-60`

- [x] **T03** · Entities `src/Entity/BoardIdea.php` und `src/Entity/BoardVote.php` nach der
      Feldtabelle in `design.md`: `publishedAt`/`notifiedAt`/`duplicateOf` nullable,
      `submittedBy` **`ON DELETE SET NULL`**, beide Fremdschlüssel von `BoardVote`
      **`ON DELETE CASCADE`**, `#[ORM\PreUpdate]` für `updatedAt`, `createdAt` im
      Konstruktor ohne Setter. ⚠ **Kein Feld für den Anzeigenamen** — es überlebte die
      Kontolöschung. ⚠ **Kein Zählerfeld für Stimmen.** Indizes im Mapping deklarieren,
      nicht nur in der Migration — `AK-65, AK-66, AK-68` · Grundlage für T04
- [x] **T04** · Migration `migrations/Version2026________.php`: beide Tabellen,
      `idx_board_idea_public (published_at, status)`, `idx_board_idea_queue
      (published_at, created_at)`, `uniq_board_idea_slug`, **`uniq_board_vote
      (idea_id, user_id)`**. ⚠ Erzeugte Datei lesen, bevor sie läuft — `make migration`
      schlägt hier regelmäßig Index-Umbenennungen aus Altlasten vor. ⚠ Gegen **MariaDB
      10.5** lauffähig: kein natives `ENUM`, kein `JSON_TABLE` — `AK-20` · Grundlage für T05 ff.

## Ebene 2 · Server — Logik und Validierung

- [x] **T05** `[P]` · `src/Board/AuthorName.php`: „Anna Katharina Berg" → „Anna B.", ein
      einzelnes Wort bleibt unverändert, leerer oder fehlender Name → `null` (das Template
      übersetzt daraus „Beitrag ohne Namen"), ein einzelnes Wort über 30 Zeichen wird
      gekürzt. Unit-Test ohne HTTP — `AK-51, AK-65, EC-01, EC-02`
- [x] **T06** `[P]` · `src/Board/BoardVoteService.php`: `toggle()` setzt oder entfernt eine
      Stimme, idempotent; der UNIQUE-Index ist die letzte Instanz, nicht die einzige —
      `AK-19, AK-20, AK-21`
- [x] **T07** `[P]` · `src/Board/BoardNotifier.php` + `templates/email/board/published.html.twig`
      (erbt von `email/base.html.twig`): genau eine Mail beim erstmaligen Veröffentlichen,
      `->locale()` aus `BoardIdea::$locale`, Inhalt **Titel und Link — nicht der
      Beschreibungstext**, Link auf `duplicateOf ?? idea`, `notifiedAt` als Sperre.
      ⚠ Die Sentry-Einstellungen (`send_default_pii: false`,
      `zend.exception_ignore_args=On`) gelten bereits und werden **nicht** angefasst —
      `AK-37, AK-38, AK-54, AK-55, AK-36`
- [x] **T08** `[P]` · `src/Account/AccountDataExporter.php` um zwei Blöcke erweitern:
      eingereichte Ideen (Titel, Status, Datum) und Ideen, denen zugestimmt wurde.
      ⚠ Änderung an **ausgeliefertem** Code — `AK-67`
- [x] **T09** `[P]` · `src/Account/AccountDeleter.php`: wartende Ideen des Kontos entfernen,
      **bevor** `remove($user)` läuft. Ohne das bliebe nach `SET NULL` eine herrenlose,
      nie freigebbare Einreichung stehen. ⚠ Änderung an **ausgeliefertem** Code — `EC-09`
- [x] **T10** · `src/Repository/BoardIdeaRepository.php`, öffentliche Abfragen:
      `findPublishedPaginated()` (Sortierung nach Stimmen bzw. Datum, Statusfilter,
      Blätterung zu 20) und `findPublishedDone()`. ⚠ **`published_at IS NOT NULL`,
      `duplicate_of_id IS NULL` und `status != done` sind fest verdrahtet**, nicht
      abschaltbar. ⚠ Gezählt wird über `COUNT(v.id) AS HIDDEN` + `GROUP BY` **ohne
      fetch-join einer Collection** — sonst begrenzt `setMaxResults()` Zeilen statt Ideen
      (BF-64) — `AK-03, AK-05, AK-06, AK-07, AK-66, AK-71, AK-75`
- [x] **T11** · `BoardIdeaRepository`, Verwaltung und Wartung: `findAwaitingReview()`
      (`created_at ASC`), `countAwaitingReview()`, `deleteStaleUnpublished(before)`,
      `deleteUnpublishedBy(user)` — dieselbe Datei wie T10, deshalb seriell —
      `AK-24, AK-74`
- [x] **T12** · `src/Service/AdminStatsService.php`: `pendingBoardIdeaCount()` über
      `countAwaitingReview()`. ⚠ Änderung an **ausgeliefertem** Code — `AK-25`
- [x] **T13** · `src/Board/StaleIdeaCleaner.php` + `src/Command/BoardCleanupCommand.php`
      (`app:board:cleanup`): löscht nie freigegebene Einreichungen älter als zwölf Monate,
      Tagessperre über einen Cache-Schlüssel. ⚠ **Hängt bewusst an keinem neuen Cron** —
      auf Produktion fehlen zwei von drei; der Befehl bleibt für den Tag, an dem der Cron
      steht — `AK-74`
- [x] **T14** · `src/Board/BoardModerator.php`: `publish()` (setzt `publishedAt`, flusht
      **zuerst** und stößt T07 danach an), `decline()` (⚠ **bricht ab, wenn die Begründung
      leer ist** — die Pflicht ist erzwungen, nicht erhofft), `changeStatus()`,
      `setResponse()`, `delete()` (nur solange unveröffentlicht). ⚠ Jeder Weg prüft
      zuerst den aktuellen Zustand — ein Doppelklick auf „Freigeben" darf nicht zweimal
      wirken (Muster BF-54). **Einzige Stelle im Code, die `publishedAt` setzt** —
      `AK-26, AK-27, AK-28, AK-30, AK-32, AK-33, AK-39, AK-71, EC-05`
- [x] **T15** · `BoardModerator::merge()`: Stimmen der Dublette auf das Original umhängen,
      Konten mit Stimme auf **beiden** Seiten einmal zählen, `duplicateOf` setzen —
      dieselbe Datei wie T14, deshalb seriell — `AK-34`

## Ebene 3 · Schnittstellen

- [x] **T16** `[P]` · `src/Form/BoardIdeaType.php`: Titel `NotBlank` + `Length(max: 120)`
      **+ `'empty_data' => ''`** (ohne die Zeile wird aus der Meldung ein 500er),
      Beschreibung `NotBlank` + `Length(max: 2000)`, Fallenfeld (**kein** `type="hidden"`,
      per CSS aus dem Blickfeld, `aria-hidden="true"`, `tabindex="-1"`, ohne
      `Blank`-Constraint). ⚠ **Kein Dateifeld**; `allow_extra_fields` bleibt auf dem
      Standardwert `false` — `AK-12, AK-13, AK-14, AK-63`
- [x] **T17** `[P]` · `src/Controller/BoardController.php`, Grundgerüst mit
      `#[Route('/community/ideen')]`: `index()` (Sortierung, Statusfilter, Blätterung),
      `show()` — veröffentlicht für alle; **wartend nur für den Verfasser, sonst 404 (nicht
      403)**; gesetzte `duplicateOf` leitet auf das Original. ⚠ `/neu` und `/eingereicht`
      müssen im Quelltext **vor** `/{id}-{slug}` stehen, zusätzlich
      `requirements: ['id' => '\d+']` — `AK-01, AK-09, AK-18, AK-35, AK-56`
- [x] **T18** `[P]` · `src/Controller/AdminBoardController.php`, Grundgerüst:
      `#[Route('/admin/ideen')]` + `#[IsGranted('ROLE_ADMIN')]` auf Klassenebene,
      `index()` mit Warteschlange und veröffentlichten Ideen, `show()` —
      `AK-24, AK-29, AK-57`
- [x] **T18a** · *(verschoben aus Ebene 1, siehe T02)* Zwei Limiter in
      `config/packages/framework.yaml`: `board_submit` (sliding_window, 5 / 1 hour) und
      `board_vote` (sliding_window, 60 / 1 hour), **beide zusätzlich im `when@test`-Block
      auf 10000**. ⚠ Muss zusammen mit T19/T20 laufen — ein konfigurierter Limiter ohne
      Aufrufer färbt `LimiterCoverageTest` rot — `AK-59, AK-60`
- [x] **T19** · `BoardController::new()` und `thanks()`: `#[IsGranted('ROLE_USER')]`,
      Prüfung auf `isVerified()` mit Weiterleitung auf `app_verify_notice`,
      `ActionLimiter::for($this->boardSubmitLimiter, $user->getUserIdentifier())`.
      ⚠ **`consume()` steht NACH `$form->isValid()`** (BF-11) und der Schlüssel ist das
      **Konto, nicht die IP**. Fallenfeld → dieselbe Erfolgsantwort ohne Datensatz und
      ohne Mail. `locale` aus `$request->getLocale()` festhalten. ⚠ **Kein Logging von
      Titel oder Beschreibungstext** und **kein Aufruf von `MarketingContactRegistry`** —
      `AK-10, AK-11, AK-15, AK-17, AK-42, AK-52, AK-53, AK-59, AK-61, AK-62`
- [x] **T20** · `BoardController::vote()` und `withdraw()`: beide `POST` mit CSRF-Token,
      Konto **aus der Sitzung** (der Endpunkt nimmt keine Konto-Kennung entgegen),
      Limiter `board_vote`, `withdraw` nur bei `publishedAt IS NULL` — sonst 403 ohne
      Änderung. ⚠ Die **Eigentumsprüfung steht vor der CSRF-Prüfung** (Muster
      `PasskeyController`). Antwort ist eine Weiterleitung, kein Turbo-Stream —
      `AK-19, AK-22, AK-23, AK-58, AK-60, AK-76, AK-77`
- [x] **T21** · `AdminBoardController`, Moderationswege: `publish`, `decline`, `status`,
      `response`, `delete` — je eigenes CSRF-Token (`board-publish-{id}` usw.), alle
      `POST`, alle über `BoardModerator` — `AK-26, AK-27, AK-28, AK-30, AK-32, AK-33`
- [x] **T22** · `AdminBoardController::merge()`: Auswahl der Ziel-Idee, CSRF, über
      `BoardModerator::merge()` — dieselbe Datei wie T18/T21, deshalb seriell — `AK-34`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt. ⚠ **Einen Ladezustand
gibt es hier bewusst nicht** — alle Seiten werden serverseitig gerendert, Zustimmen und
Zurückziehen sind Formular-Absendungen mit Weiterleitung. Das ist Technische Entscheidung 7
im Entwurf, kein Vergessen.

- [x] **T23** · Übersetzungen: Block `board:` in `translations/messages.{de,en,fr,lb}.yaml`
      (Überschriften, Status, Knöpfe, Hilfetexte, leerer Zustand, Anzeigename-Platzhalter,
      Sprachkennzeichnung, Mailtexte) und die Meldungen in
      `translations/validators.{de,en,fr,lb}.yaml`.
      ⚠ **Diese Aufgabe steht vor den Templates und ist der Grund, dass T25–T30 parallel
      laufen dürfen** — schriebe jede Template-Aufgabe ihre Schlüssel selbst, griffen fünf
      Aufgaben gleichzeitig in dieselben acht Katalogdateien — `AK-40, AK-43` ·
      Grundlage für T24 ff.
- [x] **T24** · `templates/partials/_board_idea_card.html.twig`: Zustimmungsknopf als
      eigenes POST-Formular mit `aria-pressed` **und Textwechsel** („Zustimmen" ⇄
      „Zugestimmt"), Zahl daneben trägt die Aussage, Titel als Verweis, Statusabzeichen
      als **Emoji + Wort + Farbe**, Kartenfuß mit Anzeigename, Datum und
      Sprachkennzeichnung. ⚠ Verweise im Text bleiben **unverlinkter** Text; kein
      Verlinkungsfilter, kein `|raw` — `AK-04, AK-31, AK-44, AK-45, AK-50, AK-64` ·
      Grundlage für T25, T26
- [x] **T25** `[P]` · `templates/board/index.html.twig`: Hero-Band im Verlauf
      `from-cyan-700 to-purple-800` mit **genau einer Leitzahl**, Steuerleiste als
      **GET-Formular** (Sortierung, Statusfilter, aktiver Zustand über `aria-current`),
      Liste, Blätterung mit den vorhandenen `pagination.*`-Schlüsseln, Abschnitt
      **„Schon umgesetzt"** darunter, und der **leere Zustand** mit Weg zum Formular —
      `AK-05, AK-06, AK-07, AK-08, AK-75`
- [x] **T26** `[P]` · `templates/board/show.html.twig`: Volltext mit `lang`-Attribut
      (zugleich WCAG 3.1.2), Zeilenumbrüche über `white-space: pre-line` statt `nl2br`,
      Status, Antwort des Teams **als solche gekennzeichnet**, Hinweis „wartet auf
      Freigabe" für den Verfasser — `AK-09, AK-18, AK-32, AK-41`
- [x] **T27** `[P]` · `templates/board/new.html.twig` und `thanks.html.twig`:
      Hinweisblock **vor** dem Absendeknopf („wird nach Freigabe öffentlich sichtbar",
      „keine Gesundheits- oder Kontaktangaben"), die **Fünf-Werktage-Zusage an beiden
      Stellen**, Felder über `_form_field.html.twig` mit `autofocus` am ersten
      Fehlerfeld — `AK-16, AK-48, AK-72`
- [x] **T28** `[P]` · `templates/admin/board/index.html.twig` und `show.html.twig`:
      Warteschlange älteste zuerst mit vollständigem Text, **zweistufige Überfälligkeit**
      — ab drei Werktagen gekennzeichnet, ab fünf deutlicher **und mit eigenem Wort**,
      beide Stufen ohne Farbwahrnehmung unterscheidbar. Werktag = Mo–Fr, **ohne
      Feiertagsrechnung**. Formulare für alle Moderationswege — `AK-24, AK-73, AK-79`
- [x] **T29** `[P]` · `templates/base.html.twig` Zeile 220: `footer.feedback` zeigt auf
      `path('app_board_index')`; `target="_blank"` und `rel="noopener noreferrer"`
      entfallen. ⚠ **Ersetzen, nicht ergänzen** — es bleibt genau ein Rückmeldeweg in der
      Fußzeile — `AK-02, AK-80`
- [x] **T30** `[P]` · `templates/admin/dashboard.html.twig`: Kachel mit der Zahl wartender
      Ideen und Hinweiszeile, Muster der wartenden Restaurantvorschläge — `AK-25`

## Ebene 5 · Feinschliff

- [x] **T31** · Barrierefreiheit über alle vier neuen Seiten: bei **320 px** keine
      waagerechte Bildlaufleiste (⚠ Kartenlayout statt Tabelle — BF-77 entstand genau
      hier), Tastaturbedienbarkeit, sichtbarer Fokus als `outline` (nie `outline-none`,
      kein `ring`), Tap-Targets ≥ 44 px, und **Board, Filter, Sortierung, Einreichen und
      Zustimmen funktionieren mit abgeschaltetem JavaScript** — `AK-46, AK-47, AK-49`
- [x] **T32** · Randfälle: EC-03 (Titel aus 120 × „ß" oder „日" — die Ausdehnung durch den
      Slugger darf **keinen** Datenbankfehler erzeugen), EC-04, EC-06, EC-07, EC-08
      (HTML im Text erscheint als Text), EC-10 (Druckansicht), EC-11, EC-12 —
      `EC-03, EC-04, EC-06, EC-07, EC-08, EC-10, EC-11, EC-12`
- [x] **T33** `[P]` · `docs/datenschutz.md`: Stufe B als **bestätigt** führen — nicht als
      Annahme —, mit Begründung und Datum, und das Board als eigene Verarbeitung samt
      Rechtsgrundlage und der Löschfrist aus AK-74 aufnehmen — `AK-78`
- [x] **T34** `[P]` · `docs/prd.md`: Roadmap um **„Chat-Widget"** und **„KI-Filter"**
      ergänzen — die beiden Vorhaben vom externen Board, die im PRD bisher nirgends
      stehen — `AK-82`
- [x] **T35** `[P]` · `docs/data-model.md` (zwei Entitäten, ein Enum, Migrationszeile) und
      `docs/app-shell.md` (Fußzeilenabschnitt — er ist bereits zweimal auseinandergelaufen)
      nachziehen — Projektkonvention, Grundlage für die nächste Rückerfassung
- [ ] **T36** *(NICHT ERLEDIGT — Betriebsaufgabe, siehe Bericht)* · **Beim Anbieter, außerhalb des Repositorys:** `endlech.userjot.com` so
      einstellen, dass keine neuen Einreichungen mehr angenommen werden. ⚠ Der einzige
      Schritt, den **kein Prüflauf sehen kann** — gehört zusätzlich in die
      Nachverifikation nach dem Deploy — `AK-81`

---

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T17 |
| AK-02 | T29 |
| AK-03 | T10 |
| AK-04 | T24 |
| AK-05 | T10, T25 |
| AK-06 | T10, T25 |
| AK-07 | T10, T25 |
| AK-08 | T25 |
| AK-09 | T17, T26 |
| AK-10 | T19 |
| AK-11 | T19 |
| AK-12 | T16 |
| AK-13 | T16 |
| AK-14 | T16 |
| AK-15 | T19 |
| AK-16 | T27 |
| AK-17 | T16, T19 |
| AK-18 | T17, T26 |
| AK-19 | T06, T20 |
| AK-20 | T04, T06 |
| AK-21 | T06 |
| AK-22 | T20 |
| AK-23 | T20 |
| AK-24 | T11, T18, T28 |
| AK-25 | T12, T30 |
| AK-26 | T14, T21 |
| AK-27 | T14, T21 |
| AK-28 | T14, T21 |
| AK-29 | T18 |
| AK-30 | T14, T21 |
| AK-31 | T01, T24 |
| AK-32 | T14, T21, T26 |
| AK-33 | T14, T21 |
| AK-34 | T15, T22 |
| AK-35 | T17 |
| AK-36 | T07 |
| AK-37 | T07 |
| AK-38 | T07 |
| AK-39 | T14 |
| AK-40 | T23 |
| AK-41 | T26 |
| AK-42 | T19 |
| AK-43 | T23 |
| AK-44 | T24 |
| AK-45 | T24 |
| AK-46 | T31 |
| AK-47 | T31 |
| AK-48 | T27 |
| AK-49 | T31 |
| AK-50 | T24 |
| AK-51 | T05 |
| AK-52 | T19 |
| AK-53 | T19 |
| AK-54 | T07 |
| AK-55 | T07 |
| AK-56 | T17 |
| AK-57 | T18 |
| AK-58 | T20 |
| AK-59 | T02, T19 |
| AK-60 | T02, T20 |
| AK-61 | T19 |
| AK-62 | T19 |
| AK-63 | T16 |
| AK-64 | T24 |
| AK-65 | T03, T05 |
| AK-66 | T03, T10 |
| AK-67 | T08 |
| AK-68 | T03 |
| AK-69 | — *(Abnahmekriterium: der Volldurchlauf ist das Ergebnis von T01–T30, keine eigene Bauaufgabe)* |
| AK-70 | — *(Abnahmekriterium: der Verifikationsblock nach jeder Ebene)* |
| AK-71 | T10, T14 |
| AK-72 | T27 |
| AK-73 | T28 |
| AK-74 | T11, T13 |
| AK-75 | T10, T25 |
| AK-76 | T20 |
| AK-77 | T20 |
| AK-78 | T33 |
| AK-79 | T28 |
| AK-80 | T29 |
| AK-81 | T36 |
| AK-82 | T34 |

**AK ohne Aufgabe:** keine.
**AK-69 und AK-70 tragen bewusst keine Aufgabe** — es sind Abnahmekriterien über das
Ganze, nicht über einen Arbeitsschritt. AK-69 wird erfüllt, wenn T01 bis T30 stehen;
AK-70 durch den Verifikationsblock, der nach jeder Ebene läuft. Beide werden in
`sdd-qa` belegt. Eine Sammelaufgabe „testen" wäre die erste, die gestrichen wird, sobald
es eng wird.

**Aufgabe ohne AK:** drei, alle geprüft und zulässig.

| Aufgabe | Statt eines AK | Warum zulässig |
|---|---|---|
| T09 | `EC-09` | Ein Randfall erzeugt hier eine echte Bauaufgabe: Ohne sie bliebe nach der Kontolöschung eine herrenlose Einreichung stehen. Der Fall steht in der Spec, nur eben als Edge Case |
| T32 | `EC-03, EC-04, EC-06, EC-07, EC-08, EC-10, EC-11, EC-12` | Ebene 5 verweist regelgemäß auf `EC-NN` statt auf `AK-NN` |
| T35 | Projektkonvention | `CLAUDE.md`: „Bei Änderungen am Datenmodell oder an den Komponenten-Mustern die passende Datei mitziehen, sonst laufen Code und Referenz auseinander" |

### Abdeckung der Randfälle

| EC | Aufgabe | | EC | Aufgabe |
|---|---|---|---|---|
| EC-01 | T05 | | EC-07 | T32 |
| EC-02 | T05 | | EC-08 | T32 |
| EC-03 | T32 | | EC-09 | T09 |
| EC-04 | T32 | | EC-10 | T32 |
| EC-05 | T14 | | EC-11 | T32 |
| EC-06 | T32 | | EC-12 | T32 |

**EC ohne Aufgabe:** keine. ⚠ **EC-05 sitzt bewusst in T14, nicht in T32** — dass zwei
Admin-Fenster dieselbe Idee nicht zweimal veröffentlichen können, ist eine Zustandsprüfung
im `BoardModerator` und kein Feinschliff. Als Aufgabe in Ebene 5 wäre sie das erste, was
gestrichen wird; genau so entstand BF-54 bei den Restaurantvorschlägen.

**Aufgaben mit reiner Grundlagenfunktion:** T01 (Grundlage für T03), T03 (Grundlage für
T04), T04 (Grundlage für Ebene 2), T23 (Grundlage für T24 ff.). Alle vier tragen
zusätzlich mindestens ein AK.

---

## Parallelisierung

**Ebene 1 · T01 und T02** — `src/Enum/BoardIdeaStatus.php` gegen
`config/packages/framework.yaml`. Keine gemeinsame Datei, keine Abhängigkeit.
T03 und T04 laufen danach seriell: Die Entities brauchen das Enum, die Migration
die Entities. ⚠ **Zwei Migrationen gleichzeitig bekämen kollidierende Zeitstempel** —
in dieser Ebene wird darüber hinaus nicht parallelisiert.

**Ebene 2 · T05, T06, T07, T08, T09** — fünf getrennte Dateisätze:

| Aufgabe | Dateien |
|---|---|
| T05 | `src/Board/AuthorName.php` |
| T06 | `src/Board/BoardVoteService.php` |
| T07 | `src/Board/BoardNotifier.php`, `templates/email/board/published.html.twig` |
| T08 | `src/Account/AccountDataExporter.php` |
| T09 | `src/Account/AccountDeleter.php` |

Alle fünf setzen nur die Entities aus Ebene 1 voraus, keine einander.
**T10 bis T15 laufen seriell** und tragen deshalb kein `[P]`: T10 und T11 teilen sich
`BoardIdeaRepository.php`, T14 und T15 teilen sich `BoardModerator.php`, T12 und T13
brauchen T11, und T14 braucht T07 sowie T10/T11.

**Ebene 3 · T16, T17, T18** — `src/Form/BoardIdeaType.php`,
`src/Controller/BoardController.php`, `src/Controller/AdminBoardController.php`. Drei
Dateien, keine Überschneidung.
**T19 bis T22 danach seriell:** T19/T20 schreiben in dieselbe Datei wie T17, T21/T22 in
dieselbe wie T18, und T19 braucht das Formular aus T16.

**Ebene 4 · T25, T26, T27, T28, T29, T30** — sechs getrennte Dateisätze:

| Aufgabe | Dateien |
|---|---|
| T25 | `templates/board/index.html.twig` |
| T26 | `templates/board/show.html.twig` |
| T27 | `templates/board/new.html.twig`, `templates/board/thanks.html.twig` |
| T28 | `templates/admin/board/index.html.twig`, `templates/admin/board/show.html.twig` |
| T29 | `templates/base.html.twig` |
| T30 | `templates/admin/dashboard.html.twig` |

⚠ **Das gilt nur, weil T23 die Übersetzungen vorwegnimmt.** Ohne T23 griffen alle sechs
Aufgaben gleichzeitig in dieselben acht Katalogdateien — genau der Fehler „zwei
Komponenten, beide ergänzen dieselbe Index-Datei". T23 und T24 laufen deshalb **vor** der
parallelen Gruppe und seriell.

**Ebene 5 · T33, T34, T35** — `docs/datenschutz.md`, `docs/prd.md`, sowie
`docs/data-model.md` + `docs/app-shell.md`. Keine Überschneidung.
**T31 und T32 tragen kein `[P]`:** Beide fassen quer über alle Templates aus Ebene 4 an
und kollidierten miteinander wie mit allem anderen. **T36 ist keine Codeänderung** und
läuft außerhalb.

---

## Vor dem Bauen

- [x] Feature-Branch: `git checkout -b feature/06-community-feedback-board`
- [x] Docker läuft, Test-DB steht: `make start`, einmalig `make test-db-setup`
- [x] Ausgangslage grün: `php bin/phpunit` **vor** der ersten Änderung — sonst ist
      später nicht unterscheidbar, was dieses Feature kaputtgemacht hat
- [x] **Keine neuen Schlüssel nötig.** Das Feature bringt keinen externen Dienst mit; der
      Mailversand läuft über die vorhandene Konfiguration
- [x] ⚠ **Kein Verzeichnis `public/community` anlegen** — ein Verzeichnis, das so heißt
      wie eine Route, schickt Apaches `mod_dir` in eine 301-Schleife (BF-100), lokal
      unsichtbar. `RouteDirectoryCollisionTest` hält es fest
- [x] ⚠ **Drei Aufgaben liegen außerhalb des Quelltexts** und werden erfahrungsgemäß
      vergessen: T33 (`docs/datenschutz.md`), T34 (`docs/prd.md`) und **T36 (Abschaltung
      bei userjot)**. T36 gehört zusätzlich in die Nachverifikation nach dem Deploy


---

## Abschlussbericht des Baus · 2026-08-30

Gebaut auf `feature/06-community-feedback-board`. **35 von 36 Aufgaben erledigt**,
791 Tests grün (3340 Zusicherungen), Ausgangslage waren 742.

### 1 · Umgesetzt

Das Board läuft: Einreichen mit bestätigtem Konto, Freigabe vor Veröffentlichung,
Zustimmen und Zurücknehmen, fünf Status, erzwungene Ablehnungsbegründung,
Dublettenzusammenführung, genau eine Mail bei der Veröffentlichung, Blätterung zu
20, eigener Abschnitt für Umgesetztes, vier Sprachfassungen. Dazu die drei
Änderungen an ausgeliefertem Code (Datenexport, Kontolöschung, Dashboard) und der
ersetzte Fußzeilenverweis.

**49 neue Tests**: `AuthorNameTest` (12), `BoardControllerTest` (18),
`AdminBoardControllerTest` (10), `BoardLocaleTest` (9).

### 2 · Offene Akzeptanzkriterien

| AK | Warum offen | Nachweis / Vorschlag |
|---|---|---|
| **AK-81** | **Nicht erledigt.** Die Abschaltung von `endlech.userjot.com` geschieht beim Anbieter, außerhalb des Repositorys — ich habe dort keinen Zugang. | Der Fußzeilenverweis zeigt bereits aufs eigene Board (AK-02/AK-80 belegt). Solange userjot Einreichungen annimmt, laufen Beiträge dorthin, die niemand liest. **Vor dem Deploy im Anbieter-Konto erledigen.** |
| **AK-46** | Umgesetzt, aber **nicht gemessen**. Die Seiten nutzen Kartenlayout statt Tabelle (die Ursache von BF-77), `max-w`-Container und umbrechende Flex-Zeilen. | Eine echte 320-px-Messung braucht einen Browser; im Projekt läuft das über Brave + CDP. Gehört in `sdd-qa`. |
| **AK-47** | Teilweise belegt. `min-h-[48px]` und `focus:outline-2` stehen an jedem Bedienelement, `outline-none` nirgends. | Tastaturdurchlauf und Fokussichtbarkeit sind nicht automatisiert geprüft — QA. |
| **AK-49** | Konstruktiv erfüllt: Steuerleiste ist ein GET-Formular, Zustimmen/Zurückziehen sind POST-Formulare, kein Stimulus-Controller im Feature. | Ein Lauf mit abgeschaltetem JavaScript steht aus — QA. |
| **AK-69** | Der Volldurchlauf ist über Tests belegt, **nicht von Hand im Browser**. | `AdminBoardControllerTest` deckt Einreichen → Freigabe → Mail → Zustimmung → Status ab. |

Nicht automatisiert geprüfte Randfälle: **EC-04, EC-06, EC-07, EC-10, EC-11,
EC-12**. EC-01/02/03/05/08/09 sind durch Tests belegt.

### 3 · Getroffene Annahmen

1. **Der Slug ist nicht unique** — der Entwurf sah `uniq_board_idea_slug` vor. Das
   hätte bei zwei gleichnamigen Ideen einen Serverfehler erzeugt, und gleiche
   Titel sind auf einem Wunschboard der Normalfall. Die Adresse `/{id}-{slug}` ist
   durch die Kennung eindeutig. **Abweichung vom Entwurf**, in `docs/data-model.md`
   begründet.
2. **`BoardIdeaStatus::transKey()` liefert einen flachen Schlüssel**
   (`board.status_new`). Der Entwurf legte die Form nicht fest; die flache passt
   zum übrigen `board:`-Block.
3. **`BoardVoteRepository::findVotedIdeaIds()`** holt die Zustimmungen der ganzen
   Seite in einer Abfrage statt einer je Karte. Nicht im Entwurf benannt, aber
   dieselbe Erwägung wie beim Verzicht auf das Zählerfeld.
4. **Zustimmen setzt eine bestätigte Adresse voraus** (wie das Einreichen). Der
   Entwurf sagte „bestätigtes Konto" für beide Wege, die Spec nannte es bei AK-19
   nicht ausdrücklich.
5. **Drei Ebenengrenzen im Plan waren falsch geschnitten** — siehe unten.

### 4 · Systemweite Änderungen

| Was | Wirkung über das Feature hinaus |
|---|---|
| `templates/base.html.twig` | **Der Fußzeilenverweis „Feedback & Ideen" zeigt nicht mehr auf `endlech.userjot.com`, sondern auf `/community/ideen`.** Sichtbar auf **jeder** Seite. |
| `src/Account/AccountDataExporter.php` | Der Datenexport jedes Kontos führt zwei neue Blöcke. Betrifft Feature `01`. |
| `src/Account/AccountDeleter.php` | Die Kontolöschung räumt zusätzlich wartende Board-Ideen ab. Betrifft Feature `01`. |
| `src/Service/AdminStatsService.php` | Fünftes Konstruktorargument — **jeder Aufrufer muss mitziehen.** `AdminStatsServiceTest` wurde angepasst. |
| `templates/admin/dashboard.html.twig` | Eine sechste Kachel. |
| `config/packages/framework.yaml` | Zwei neue Limiter samt `when@test`-Overrides. |
| `translations/*.yaml` (8 Dateien) | Neuer Block `board:` (58 Schlüssel), 7 Mail-Schlüssel, 14 Flash-Schlüssel, 4 Meldungen. |
| `docs/datenschutz.md` | **Die Datenschutzstufe des Projekts ist von „angenommen" auf „bestätigt: B" gesetzt** — mit der Bedingung, unter der sie kippt. Gilt projektweit. |
| `docs/prd.md` | Vier Roadmap-Zeilen aus dem externen Board übernommen. |
| `docs/data-model.md`, `docs/app-shell.md` | Zwei Entitäten, ein Enum, eine Migrationszeile, Fußzeilenabschnitt. |

**Keine neue Abhängigkeit.** Kein `composer require`, kein `npm install`, keine
Änderung unter `assets/` — `public/build` bleibt unberührt.

### 5 · Abweichungen vom Aufgabenplan

**Dreimal dieselbe Lehre, und sie gehört in den nächsten Plan:**
**Konfiguration und Katalogeinträge gehören in dieselbe Ebene wie das Artefakt,
das sie benutzt — nicht in eine eigene.**

1. **T02 (Limiter) musste von Ebene 1 nach Ebene 3** (als T18a). `LimiterCoverageTest`
   verlangt, dass jeder konfigurierte Limiter verdrahtet ist; nach Ebene 1 kann das
   strukturell nicht erfüllt sein, und die Suite wäre zwei Ebenen lang rot gewesen.
   CLAUDE.md sagt dasselbe: „legt den Limiter **im selben Commit** an."
2. **Die Mail-Schlüssel mussten zu T07 vorgezogen** — eine Mailvorlage ohne ihre
   Schlüssel färbt `CatalogueCompletenessTest` sofort rot.
3. **T23 (Übersetzungen) musste vor Ebene 3 vorgezogen** — der Katalogprüflauf
   scannt `src/Form/`, also brauchen die Formularschlüssel dieselbe Ebene wie das
   Formular.

Dazu: **Die Repository-Gerüste entstanden in T03 statt in T10/T11.** Eine Entität
mit `repositoryClass` auf eine nicht existierende Klasse bricht den
Container-Lint.

### 6 · Zwei Fehler, die erst der Prüflauf fand

1. **Roher Übersetzungsschlüssel auf der Seite.** `transKey()` lieferte
   `board.status.declined`, die Kataloge trugen `board.status_declined`. Der
   Katalogprüflauf sah das **nicht** — der Schlüssel entsteht in PHP, nicht als
   Literal im Template. Gefunden hat es erst ein Test, der die gerenderte Seite
   liest. Dafür gibt es jetzt `BoardLocaleTest` über alle vier Sprachen.
2. **Die Ablehnungsbegründung fehlte in der Listenansicht.** AK-28 verlangt sie
   „im Board"; die Karte zeigte nur die Beschreibung. Ohne den Fund hätte dort ein
   „Abgelehnt" ohne jedes Warum gestanden — Produktprinzip 2 nur halb eingelöst.

### 7 · Bekannte rote Stelle, die nicht von diesem Feature kommt

`php bin/console lint:container` schlägt fehl:
`Invalid alias definition … PublicKeyCredentialSourceRepositoryInterface`.
**Gegengeprüft mit `git stash`: Der Fehler besteht auch ohne dieses Feature** —
Vorbestand aus B03, in CLAUDE.md als bewusste Abweichung dokumentiert.
`make fix-check` existiert im Makefile nicht und `php-cs-fixer` ist nicht
installiert; die Stilprüfung aus dem Stack-Profil konnte deshalb nicht laufen.
