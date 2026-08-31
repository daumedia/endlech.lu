# 06 · Community Feedback Board — Systemdesign

Status: `architected` · Stand: 2026-08-30 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Zwei neue Tabellen, ein neuer Enum, zwei Controller, ein Formular, vier Templates und ein
Aufräumbefehl. Eine Idee ist ein Datensatz mit einem Titel, einem Beschreibungstext, einem
Verfasser und einem Status; sie wird erst öffentlich, wenn ein Admin ein Datum in das Feld
`published_at` schreibt. Eine Zustimmung ist eine eigene Zeile, die ein Konto mit einer
Idee verbindet — ihre Zahl wird bei jedem Aufruf gezählt, nicht mitgeführt.

Alles, was das Board tut, benutzt Muster, die im Projekt bereits stehen: die
Moderationsschlange von B21, das Ratenlimit am Konto von B11, das Fallenfeld von B14, das
barrierefreie Formularfeld von `02` und die Blätterung von B05. Es kommt **keine neue
Bibliothek** hinzu und kein neuer externer Dienst.

---

## ⚠ Ein Fund, der die Spezifikation berührt

**Die Fußzeile führt bereits einen Punkt „Feedback & Ideen".** Er zeigt auf
`https://endlech.userjot.com/?cursor=1&order=top&limit=10` — ein extern gehostetes
Ideen-Board mit Zustimmungen, sortiert nach „top". Das ist dieses Feature, bei einem
Dritten betrieben.

Belegstellen: `templates/base.html.twig:220`, Übersetzungsschlüssel `footer.feedback`
(„Feedback & Ideen"), `docs/app-shell.md:208`, `features/B26-cookie-banner/spec.md:75`.

Die Spezifikation wusste davon nichts — sie nennt als bestehende Rückmeldewege nur das
Meldeformular auf `/barrierefreiheit` und den Wizard auf `/community/suggest`. Zwei Folgen:

1. **AK-02 kollidiert mit dem vorhandenen Eintrag.** Der Entwurf sieht deshalb vor, dass
   `footer.feedback` künftig auf `app_board_index` zeigt — **Ersetzung, kein zwölfter
   Eintrag**. Zwei Adressen für dieselbe Frage teilen die Nutzer und die Stimmen; das Board
   wäre von Anfang an halb leer.
2. **Der Bestand wurde am 2026-08-30 nachgesehen** statt vermutet: **sieben Einträge, alle
   vom Betreiber selbst, alle mit null Stimmen** (Presskit, iOS app, Android App, Google
   Login, Apple Login, Chat widget, AI filter). **Es gibt keinen fremden Nutzerbestand.**
   Entschieden (OF-07): Das Board startet leer, die Titel wandern in die PRD-Roadmap
   (AK-82), der externe Dienst wird nach dem Deploy abgeschaltet (AK-81).

Der Fund spricht im Übrigen *für* das Feature: Der Bedarf war real genug, dass bereits ein
fremdes Werkzeug dafür im Einsatz ist. Und er zeigt, warum ein zweites Board nicht bleiben
darf — „Presskit" steht dort bis heute auf *In Progress*, während Feature `05` seit
`v2026.08.30.1` live ist.

---

## Seiten und Routen

Alle Routen liegen unter `/{_locale}` und werden vom bestehenden `controllers`-Loader
erfasst. **Kein sprachfreier Kurzlink** — anders als `/open`, `/vergleich` und `/presse`
steht diese Adresse nicht auf Visitenkarten oder in Fördermails; sie wird verlinkt, nicht
abgetippt.

### Öffentlich und angemeldet — `BoardController`

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_board_index` | `/community/ideen` | Board: Liste, Sortierung, Statusfilter, Blätterung, Abschnitt „Schon umgesetzt" | öffentlich |
| `app_board_new` | `/community/ideen/neu` (GET, POST) | Einreichformular | `ROLE_USER`, zusätzlich bestätigte Adresse |
| `app_board_thanks` | `/community/ideen/eingereicht` | Wartehinweis nach dem Absenden | `ROLE_USER` |
| `app_board_show` | `/community/ideen/{id}-{slug}` | Einzelansicht mit Volltext, Status, Team-Antwort | öffentlich für Veröffentlichtes; Wartendes nur für den Verfasser |
| `app_board_vote` | `/community/ideen/{id}/zustimmen` (POST) | Zustimmung setzen oder zurücknehmen | `ROLE_USER`, bestätigt |
| `app_board_withdraw` | `/community/ideen/{id}/zurueckziehen` (POST) | eigene wartende Idee zurückziehen | `ROLE_USER`, nur Verfasser, nur unveröffentlicht |

⚠ **`/neu` und `/eingereicht` müssen im Quelltext VOR `/{id}-{slug}` stehen** — sonst
greift die Einzelansicht zuerst und behandelt „neu" als Kennung. Zusätzlich abgesichert
über `requirements: ['id' => '\d+']`. Dasselbe Muster wie bei
`admin_restaurant_new` gegenüber `admin_restaurant_edit`.

⚠ **Es darf kein Verzeichnis `public/community` entstehen.** Ein Verzeichnis, das so heißt
wie eine Route, schickt Apaches `mod_dir` in eine 301-Schleife (BF-100) — lokal
unsichtbar. Heute existiert keins; `RouteDirectoryCollisionTest` hält das fest.

### Verwaltung — `AdminBoardController`

Klassenebene `#[Route('/admin/ideen')]` + `#[IsGranted('ROLE_ADMIN')]`, wie
`AdminSuggestionController`.

| Route | Pfad | Zweck |
|---|---|---|
| `admin_board_index` | `/admin/ideen` | Warteschlange (älteste zuerst) plus die veröffentlichten Ideen |
| `admin_board_show` | `/admin/ideen/{id}` | Einzelansicht mit allen Aktionen |
| `admin_board_publish` | `/admin/ideen/{id}/veroeffentlichen` (POST) | freigeben — Status `neu` |
| `admin_board_decline` | `/admin/ideen/{id}/ablehnen` (POST) | ablehnen; **verlangt eine Begründung** |
| `admin_board_status` | `/admin/ideen/{id}/status` (POST) | Status wechseln |
| `admin_board_response` | `/admin/ideen/{id}/antwort` (POST) | Antwort des Teams setzen oder ändern |
| `admin_board_merge` | `/admin/ideen/{id}/dublette` (POST) | als Dublette einer anderen Idee zusammenführen |
| `admin_board_delete` | `/admin/ideen/{id}/loeschen` (POST) | endgültig löschen; nur solange unveröffentlicht |

Alle schreibenden Wege sind `POST` mit eigenem CSRF-Token je Vorgang
(`board-publish-{id}`, `board-decline-{id}`, …), wie bei `admin_suggestion_approve`.

---

## Komponentenstruktur

### Board — `templates/board/index.html.twig`

```
Board (/community/ideen)
├── Hero-Band                       Verlauf from-cyan-700 to-purple-800, wie /open, /partner
│   ├── Titel und Einleitung
│   ├── Leitzahl                    Zahl der offenen Ideen — genau eine Leitzahl je Seite
│   └── Handlungsaufruf             „Idee einreichen" → app_board_new
├── Steuerleiste                    GET-Formular, wirkt ohne JavaScript
│   ├── Sortierung                  Zustimmungen (Vorgabe) · neueste
│   └── Statusfilter                alle · neu · in Prüfung · geplant · abgelehnt
├── Ideenliste                      höchstens 20 Karten, bg-white auf bg-gray-50
│   └── Ideenkarte                  (geteiltes Partial, siehe unten)
├── Blätterung                      pagination.*-Schlüssel aus B05, wiederverwendet
└── Abschnitt „Schon umgesetzt"     eigene Überschrift, dieselbe Karte, eigene Abfrage
```

### Ideenkarte — `templates/partials/_board_idea_card.html.twig`

```
Ideenkarte
├── Zustimmungsspalte               eigenes POST-Formular, links
│   ├── Knopf                       aria-pressed, min-h-[48px], Textwechsel „Zustimmen" ⇄ „Zugestimmt"
│   └── Zahl                        trägt die Aussage; das Dreieck ist aria-hidden
├── Hauptteil
│   ├── Titel                       Verweis auf app_board_show
│   ├── Textauszug                  bei der Einzelansicht der Volltext, mit lang-Attribut
│   └── Statusabzeichen             Emoji + Wort + Farbe (Pille, badgeClasses() aus dem Enum)
└── Kartenfuß                       Anzeigename · Datum · Sprachkennzeichnung
```

### Einreichformular — `templates/board/new.html.twig`

```
Einreichformular (/community/ideen/neu)
├── Hinweisblock                    VOR dem Absendeknopf (AK-16, AK-72)
│   ├── „wird nach Freigabe öffentlich sichtbar"
│   ├── „keine Gesundheits- oder Kontaktangaben"
│   └── „in der Regel innerhalb von fünf Werktagen"
├── Formular
│   ├── Titel                       _form_field.html.twig, max. 120
│   ├── Beschreibung                _form_field.html.twig, rows=8, max. 2000
│   ├── Fallenfeld                  CSS aus dem Blickfeld, aria-hidden, tabindex=-1
│   └── Absendeknopf
└── Verweis zurück aufs Board
```

### Die vier Zustände je Bildschirm

| Zustand | Board | Formular | Verwaltung |
|---|---|---|---|
| **leer** | erklärender Kasten + Weg zum Formular (AK-08) | entfällt | „keine wartenden Ideen" |
| **überfällig** | – | – | zwei Stufen: ab drei Werktagen gekennzeichnet (AK-73), ab fünf deutlicher und mit eigenem Wort (AK-79) |
| **ladend** | **existiert nicht** — die Seite wird serverseitig gerendert, Zustimmen ist ein Formular-Absenden mit anschließender Weiterleitung. Das ist eine Entscheidung, kein Vergessen: siehe Technische Entscheidung 7 | dito | dito |
| **Fehler** | Limiter → Flash + HTTP 429, Zahl unverändert | ungültig → HTTP 422, Meldung je Feld, Fokus im ersten Fehlerfeld | Ablehnung ohne Begründung → Flash, keine Änderung |
| **gefüllt** | Liste + Blätterung + Abschnitt „Schon umgesetzt" | ausgefülltes Formular | Warteschlange + veröffentlichte Ideen |

---

## Datenmodell

### Neue Tabelle `board_idea`

`src/Entity/BoardIdea.php` · `BoardIdeaRepository`

| Property | Spalte | Typ | Null | Bedeutung |
|---|---|---|---|---|
| `id` | `id` | `integer` PK AUTO | – | |
| `title` | `title` | `varchar(120)` | nein | Titel der Idee |
| `description` | `description` | `longtext` | nein | Beschreibungstext, per Constraint auf 2000 Zeichen begrenzt |
| `slug` | `slug` | `varchar(160)` | nein | aus dem Titel, Teil der Adresse |
| `status` | `status` | `varchar(20)` | nein | `enumType: BoardIdeaStatus` |
| `submittedBy` | `submitted_by_id` | FK → `` `user` `` | **ja** | Verfasser; `ON DELETE SET NULL` |
| `locale` | `locale` | `varchar(5)` | nein | Sprache, in der eingereicht wurde |
| `teamResponse` | `team_response` | `longtext` | ja | öffentliche Antwort des Teams; bei Ablehnung die Begründung |
| `duplicateOf` | `duplicate_of_id` | FK → `board_idea` | ja | gesetzt = zusammengeführt; `ON DELETE SET NULL` |
| `publishedAt` | `published_at` | `datetime_immutable` | ja | **`NULL` = wartet, gesetzt = öffentlich** |
| `notifiedAt` | `notified_at` | `datetime_immutable` | ja | Sperre gegen einen zweiten Mailversand |
| `createdAt` | `created_at` | `datetime_immutable` | nein | im Konstruktor, ohne Setter |
| `updatedAt` | `updated_at` | `datetime_immutable` | nein | `#[ORM\PreUpdate]`, Muster `PartnerWaitlistEntry` |

**Indizes**

| Index | Spalten | Wofür |
|---|---|---|
| `idx_board_idea_public` | `(published_at, status)` | jede öffentliche Abfrage filtert auf beides |
| `idx_board_idea_queue` | `(published_at, created_at)` | Warteschlange, älteste zuerst, und der Aufräumlauf |
| `uniq_board_idea_slug` | `(slug)` **unique** | eindeutige Adressen |

⚠ **Der Index gehört auch ins Entity-Mapping**, nicht nur in die Migration — sonst meldet
`doctrine:schema:validate` eine Abweichung. Derselbe Fallstrick wie beim Kombi-Index von
`PartnerWaitlistEntry`.

⚠ **Es gibt kein Feld für den Anzeigenamen.** Der Name wird bei jeder Anzeige aus
`submittedBy` abgeleitet. Ein eingefrorener Schnappschuss überlebte die Kontolöschung und
wäre genau der Weg zurück zur Person, den AK-68 ausschließt.

⚠ **Kein Zählerfeld für Zustimmungen.** Begründung: Technische Entscheidung 2.

### Neue Tabelle `board_vote`

`src/Entity/BoardVote.php` · `BoardVoteRepository`

| Property | Spalte | Typ | Null | Bedeutung |
|---|---|---|---|---|
| `id` | `id` | `integer` PK AUTO | – | |
| `idea` | `idea_id` | FK → `board_idea` | nein | **`ON DELETE CASCADE`** |
| `user` | `user_id` | FK → `` `user` `` | nein | **`ON DELETE CASCADE`** |
| `createdAt` | `created_at` | `datetime_immutable` | nein | |

| Index | Spalten | Wofür |
|---|---|---|
| `uniq_board_vote` | `(idea_id, user_id)` **unique** | eine Stimme je Konto und Idee — erzwungen in der Datenbank, nicht nur im Dienst (AK-20) |

⚠ **Hier ist `CASCADE` richtig und `SET NULL` falsch** — entgegen der Projektkonvention
(„`SET NULL`, wo der Datensatz eigenständig weiterlebt"). Eine Zustimmung lebt *nicht*
eigenständig weiter: Sie ist die Handlung einer Person und ohne sie bedeutungslos. Genau
das ist der Unterschied zwischen AK-65 (die Idee bleibt) und AK-66 (die Stimmen
verschwinden, die Zahl sinkt).

### Neuer Enum `BoardIdeaStatus`

`src/Enum/BoardIdeaStatus.php` — Backed String, Muster `WaitlistStatus`.

| Fall | Wert | Anzeige |
|---|---|---|
| `NEW` | `new` | neu |
| `REVIEWING` | `reviewing` | in Prüfung |
| `PLANNED` | `planned` | geplant |
| `DONE` | `done` | umgesetzt |
| `DECLINED` | `declined` | abgelehnt |

Methoden `transKey()`, `label()`, `emoji()`, `badgeClasses()`.
⚠ `badgeClasses()` liefert **ausschließlich Farbe** (Design-System); Form und Größe bleiben
im Template, und das Wort steht immer daneben (AK-45).

⚠ **„Wartet auf Freigabe" ist KEIN Status dieses Enums.** Es ist der Zustand
`published_at IS NULL`. Warum getrennt: Technische Entscheidung 1.

### Änderungen an bestehenden Tabellen

**Keine.** Beide Beziehungen sind unidirektional von den neuen Tabellen aus — wie
`RestaurantSuggestion::$suggestedBy`. `User` bekommt **keine** Collection und damit auch
keine Änderung an der Tabelle `` `user` ``.

### Löschverhalten im Überblick

| Ereignis | `board_idea` | `board_vote` |
|---|---|---|
| Konto gelöscht, Idee **veröffentlicht** | bleibt, `submitted_by_id` → `NULL` (AK-65) | Zeilen des Kontos verschwinden per CASCADE (AK-66) |
| Konto gelöscht, Idee **wartet noch** | wird **mitgelöscht** — siehe unten (EC-09) | dito |
| Idee gelöscht | – | zugehörige Stimmen verschwinden per CASCADE |
| Idee zusammengeführt | bleibt mit `duplicate_of_id` | Stimmen wandern auf das Original (AK-34) |

⚠ **`AccountDeleter` braucht genau eine Ergänzung:** wartende Ideen des Kontos entfernen,
**bevor** der Nutzer gelöscht wird. Ohne sie bliebe nach `SET NULL` eine herrenlose,
niemals freigebbare Einreichung in der Warteschlange stehen — und AK-37 schickte bei einer
späteren Freigabe eine Mail an eine Adresse, die es nicht mehr gibt (EC-09).

⚠ **Für AK-66 braucht `AccountDeleter` dagegen nichts.** Die Stimmen verschwinden über den
Fremdschlüssel, und weil die Zahl gezählt und nicht mitgeführt wird, stimmt sie danach von
allein. Bei einem Zählerfeld wäre genau hier die Stelle, an der es auseinanderliefe — die
Datenbank-Kaskade läuft am Anwendungscode vorbei.

---

## Dienste

| Klasse | Aufgabe |
|---|---|
| `App\Board\AuthorName` | leitet den Anzeigenamen aus `User::$name` ab (AK-51). Reine Funktion, gibt `?string`; `null` heißt „Beitrag ohne Namen" und wird im Template übersetzt. Als Unit-Test prüfbar, ohne HTTP |
| `App\Board\BoardVoteService` | setzt und entfernt eine Stimme, idempotent |
| `App\Board\BoardModerator` | veröffentlichen, ablehnen, Status wechseln, Antwort setzen, zusammenführen, löschen — alles, was den Zustand einer Idee verändert |
| `App\Board\BoardNotifier` | die eine Mail aus AK-37; setzt `notified_at` |
| `App\Board\StaleIdeaCleaner` | löscht nie freigegebene Einreichungen älter als zwölf Monate (AK-74) |
| `App\Command\BoardCleanupCommand` | `app:board:cleanup`, ruft `StaleIdeaCleaner` |

Die Geschäftslogik liegt bewusst **nicht** im Controller (Stack-Profil, Abschnitt 1). Der
Grund ist hier nicht Formalismus: `BoardModerator` ist die einzige Stelle, an der
`published_at` gesetzt wird, und damit die einzige Stelle, an der AK-71 geprüft werden muss.

### Abfragen — `BoardIdeaRepository`

| Methode | Wofür |
|---|---|
| `findPublishedPaginated(sort, page, limit, status)` | Board-Hauptliste. **`published_at IS NOT NULL`, `duplicate_of_id IS NULL` und `status != done` sind fest verdrahtet**, nicht als Parameter |
| `findPublishedDone(limit)` | Abschnitt „Schon umgesetzt" (AK-75) |
| `findAwaitingReview()` | Warteschlange, `created_at ASC` (AK-24) |
| `countAwaitingReview()` | Dashboard-Kachel (AK-25) |
| `deleteStaleUnpublished(before)` | Aufräumlauf (AK-74) |
| `deleteUnpublishedBy(user)` | Kontolöschung (EC-09) |

⚠ **Die Sichtbarkeitsregel steht im Repository, nicht im Template.** Jede öffentliche
Abfrage geht über eine Methode, die `published_at IS NOT NULL` selbst setzt und keinen
Weg anbietet, es abzuschalten. Damit ist AK-71 an **einer** Stelle prüfbar statt an fünf.

⚠ **Sortierung nach Zustimmungen ohne die Falle aus BF-64.** Gezählt wird über
`LEFT JOIN` auf `board_vote` mit `COUNT(...) AS HIDDEN` und `GROUP BY` auf der Idee — es
wird **keine Collection per `addSelect` mitgeholt**. Damit liefert die Abfrage genau eine
Zeile je Idee, und `setMaxResults()` begrenzt Ideen statt Zeilen. Das ist das Gegenteil des
Fehlers, an dem `findTopRated(6)` ein einziges Restaurant zurückgab. Zweitschlüssel bei
Gleichstand: `created_at DESC` (AK-05).

---

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| **Gast** | veröffentlichte Ideen | nichts | Repository-Kriterium (`published_at IS NOT NULL`) · `#[IsGranted('ROLE_USER')]` auf allen Schreibrouten |
| **Angemeldet, Adresse unbestätigt** | wie Gast, dazu die **eigenen** wartenden Ideen | nichts | Controller prüft `User::isVerified()` und leitet auf `app_verify_notice` — Muster `CommunityController::vorschlagen` |
| **Angemeldet, bestätigt** | wie oben | eigene Idee anlegen und zurückziehen · Stimme setzen und entfernen | `#[IsGranted('ROLE_USER')]` · Eigentumsprüfung im Controller · UNIQUE-Index gegen Doppelstimmen |
| **`ROLE_ADMIN`** | alles, auch Wartendes | alle Moderationswege | `#[IsGranted('ROLE_ADMIN')]` auf Klassenebene **und** `access_control: ^/[a-z]{2}/admin` |

**Es gibt keine zweite Schicht.** Das Stack-Profil sagt es ausdrücklich: Ohne RLS ist die
Anwendung die einzige Grenze. Was hier nicht geprüft wird, ist ungeprüft.

⚠ **Kein Voter.** Das Projekt enthält keinen einzigen (`src/Security/` führt nur
`PasskeyAuthenticator` und `WebauthnUserEntityRepository`). Die Eigentumsprüfung läuft
explizit im Controller, wie in `PasskeyController` — und dort steht sie **vor** der
CSRF-Prüfung: Wer nicht Eigentümer ist, hat dort unabhängig vom Token nichts verloren.
Ein einzelner Voter wäre ein zweites Muster für dieselbe Frage.

⚠ **Keine neue `access_control`-Zeile nötig.** `/community/ideen` liegt unter
`/{_locale}` und ist von keiner Regel erfasst — das ist richtig so, weil das Board
öffentlich lesbar sein soll und die Schreibwege ihr `#[IsGranted]` selbst tragen. Der
Verwaltungsbereich fällt unter die bestehende Zeile `^/[a-z]{2}/admin`.

⚠ **Der Zustimmungsendpunkt nimmt keine Konto-Kennung entgegen** (AK-58). Das Konto kommt
aus der Sitzung; im Formular steht nur die Kennung der Idee und das CSRF-Token. Ein Feld,
das es nicht gibt, lässt sich nicht unterschieben.

⚠ **Fremde wartende Idee → 404, nicht 403** (AK-56). Ein 403 mit Titel in der Fehlerseite
verriete die Existenz und den Inhalt.

---

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `app_board_new` (POST) | **5 je Stunde und Konto** | HTTP 429, Flash mit Wartezeit, Formular bleibt gefüllt, **kein Datensatz** | `board_submit` in `config/packages/framework.yaml`, `policy: sliding_window` |
| `app_board_vote` (POST) | **60 je Stunde und Konto** | HTTP 429, Flash, keine Zahl verändert sich | `board_vote` in derselben Datei |
| `app_board_new` (POST) | Fallenfeld | dieselbe Erfolgsantwort wie im Gutfall, kein Datensatz, keine Mail | Controller, Muster `PartnerController` |
| `app_board_withdraw`, alle `admin_board_*` | kein Limiter | – | setzen einen bestehenden Datensatz voraus und erzeugen weder Mail noch Zeile |

⚠ **Der Schlüssel ist `User::getUserIdentifier()`, nicht die IP** (AK-61). Beide Wege
setzen ein bestätigtes Konto voraus; dort wechselt der Angreifer die IP mühelos, das Konto
nicht. Dieselbe Begründung wie bei `password_change` und `suggestion_submit`.

⚠ **`ActionLimiter` benutzen, nicht `consume(1)` von Hand.** `isAllowed()` fragt ab,
`consume()` bucht — und `consume()` steht **nach** `$form->isValid()` (AK-62). Ein
`consume(0)` als Prüfung wäre wirkungslos, das ist im Projekt nachgestellt worden.

⚠ **Der `when@test`-Override auf 10000 ist Pflicht** für beide Limiter, sonst summieren
sich die Aufrufe über die Suite. `LimiterCoverageTest` prüft beides: dass jeder
konfigurierte Limiter irgendwo verdrahtet ist **und** einen Test-Override hat.

**Keine Uploads.** Das Formular führt kein Dateifeld; Symfonys Formularkomponente weist
unerwartete Felder ohnehin mit 422 ab (`allow_extra_fields` steht auf dem Standardwert
`false`). Damit ist AK-63 durch Abwesenheit erfüllt und nicht durch eine Prüfung, die
jemand vergessen könnte.

---

## Externe Dienste

Es kommt **kein neuer Dienst** hinzu.

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| **Brevo** (Versand, Zweck 1 nach `docs/datenschutz.md`) | die eine Mail bei Veröffentlichung (AK-37) | Empfängeradresse = **der Verfasser selbst**, Anzeigename, **Titel** der Idee, absolute URL | **Der Beschreibungstext.** Er steht auf der verlinkten Seite; ihn zusätzlich durch ein fremdes System zu schicken, brächte nichts und wäre genau die Art.-9-Fläche, die die Spec eingrenzen will. Ebenso: kein Kontakt-Upsert, keine Liste, keine Einwilligung (AK-53) |
| **Sentry** (Fehler) | uncaught Exceptions | Klasse, Meldung, Stacktrace **ohne Argumente** | Titel und Beschreibungstext erscheinen nie. Greift bereits: `send_default_pii: false` und `zend.exception_ignore_args` auf `On` (AK-55) |

⚠ **Der Beitragstext darf in keinen Log-Aufruf** (AK-52). Das Board schreibt selbst nichts
ins Log; wo geloggt wird, wird die Kennung der Idee geloggt, nie ihr Inhalt.

---

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Sichtbarkeit über das Datumsfeld `published_at` | ein sechster Status `pending` im selben Enum | Die fünf Status aus AK-31 sind fachliche Aussagen über eine **öffentliche** Idee; „wartet auf Freigabe" ist eine andere Achse. Vermischt man beides, kann ein Statuswechsel eine veröffentlichte Idee versehentlich vom Netz nehmen. Getrennt ist AK-71 eine einzige Bedingung, die an einer Stelle steht |
| 2 | Zustimmungen werden **gezählt** (`COUNT AS HIDDEN` + `GROUP BY`) | denormalisiertes Feld `vote_count` | Ein Zählerfeld liefe genau bei AK-66 auseinander: Die Löschung eines Kontos räumt die Stimmen per Fremdschlüssel-Kaskade ab, und das passiert in der Datenbank, am Anwendungscode vorbei. Der Ausweg wäre ein Abgleichsbefehl — ein dritter geplanter Lauf in einem Projekt, dem zwei von drei fehlen. Bei zweistelligen Ideenzahlen kostet die Unterabfrage nichts. **Ab etwa 2000 Ideen neu bewerten** |
| 3 | `board_vote` kaskadiert, `board_idea.submitted_by` wird `NULL` | überall `SET NULL`, wie sonst im Projekt | Der scheinbare Bruch mit der Konvention ist der Kern von AK-65 gegen AK-66: Eine Idee lebt eigenständig weiter, weil andere für sie gestimmt und das Team öffentlich geantwortet hat. Eine Stimme ist die Handlung einer Person und ohne sie nichts |
| 4 | Kein Schnappschuss des Anzeigenamens | `author_name` beim Einreichen einfrieren | Ein Schnappschuss überlebte die Kontolöschung. Genau das schließt AK-68 aus: „von keiner Idee führt ein Weg zurück auf Name oder E-Mail-Adresse" |
| 5 | Eigentumsprüfung im Controller, kein Voter | `BoardIdeaVoter` in `src/Security/Voter/` | Das Projekt hat keinen einzigen Voter. Ein einzelner wäre ein zweites Muster für dieselbe Frage — und zwei Antworten auf „gehört mir das?" sind teurer als die unbeliebtere Variante konsequent |
| 6 | Aufräumen läuft als Befehl **und** faul beim Öffnen der Warteschlange (höchstens einmal je Tag, über einen Cache-Schlüssel gesperrt) | eigener Cron-Eintrag | Auf Produktion fehlen **zwei von drei** Cron-Einträgen; `app:metrics:snapshot` hat dadurch nie einen Snapshot geschrieben. Ein dritter, der von einer Einrichtung auf dem Server abhängt, fehlte mit hoher Wahrscheinlichkeit auch. Der Befehl bleibt für den Tag, an dem der Cron steht — der faule Aufruf sorgt dafür, dass AK-74 bis dahin trotzdem greift |
| 7 | Zustimmen ist ein gewöhnliches Formular mit Weiterleitung, **kein** Turbo-Stream | Turbo-Stream wie auf `/partner` | AK-23 und AK-49 verlangen, dass es ohne JavaScript funktioniert. Ein Turbo-Stream bräuchte den Formularweg als Rückfall zusätzlich — zwei Codepfade für dieselbe Handlung. Turbo lässt sich später ohne Bruch nachrüsten |
| 8 | Die Mail enthält Titel und Link, nicht den Volltext | Volltext mitschicken, damit man ihn im Postfach liest | Der Text kann eine Gesundheitsangabe enthalten. AK-37 verlangt Titel und Link; mehr zu schicken erweitert die Fläche bei einem Auftragsverarbeiter ohne Gegenwert |
| 9 | Verweise im Beitragstext bleiben unverlinkter Text | `nl2br` plus automatische Verlinkung | AK-64. Twig maskiert ohnehin; es wird schlicht **kein** Verlinkungsfilter eingebaut. Diese Nicht-Handlung gehört dokumentiert, sonst baut sie jemand später „als Verbesserung" ein und macht aus dem Board ein Ziel für Verweis-Spam |
| 10 | Zeilenumbrüche im Beschreibungstext bleiben erhalten (`white-space: pre-line`) | `nl2br`-Filter | `nl2br` erzeugt Markup aus Nutzertext; eine CSS-Regel erreicht dasselbe, ohne dass an irgendeiner Stelle `|raw` steht. Wo `|raw` steht, wird es irgendwann falsch benutzt |

---

## Änderungen an bestehendem, ausgeliefertem Code

Das ist der Teil, der leicht zwischen die Stühle fällt — er gehört in `tasks.md` als
eigene Aufgaben, nicht als Nebenbei-Handgriff.

| Datei | Änderung | AK |
|---|---|---|
| `src/Account/AccountDataExporter.php` | zwei Blöcke ergänzen: eingereichte Ideen (mit Status) und Ideen, denen zugestimmt wurde | AK-67 |
| `src/Account/AccountDeleter.php` | wartende Ideen des Kontos entfernen, **bevor** der Nutzer gelöscht wird | EC-09 |
| `src/Service/AdminStatsService.php` | `pendingBoardIdeaCount()` | AK-25 |
| `templates/admin/dashboard.html.twig` | Kachel und Hinweiszeile, Muster der wartenden Vorschläge | AK-25 |
| `templates/base.html.twig` Zeile 220 | `footer.feedback` zeigt auf `app_board_index` statt auf `endlech.userjot.com`; `target="_blank"` und `rel` entfallen | AK-02 |
| `docs/app-shell.md` | Fußzeilenabschnitt nachziehen — er ist bereits zweimal auseinandergelaufen | – |
| `config/packages/framework.yaml` | zwei Limiter plus zwei `when@test`-Overrides | AK-59, AK-60 |
| `translations/messages.{de,en,fr,lb}.yaml` | neuer Block `board:`; `footer.feedback` bleibt im Wortlaut brauchbar („Feedback & Ideen") | AK-40, AK-43 |
| `translations/validators.{de,en,fr,lb}.yaml` | Meldungen für Pflicht und Länge | AK-12 bis AK-14 |
| `docs/datenschutz.md` | Stufe B **bestätigen** und das Board als Verarbeitung aufnehmen | AK-78 |
| `docs/data-model.md` | zwei Entitäten, ein Enum, Migrationszeile | – |
| `docs/prd.md` | Roadmap um „Chat-Widget“ und „KI-Filter“ ergänzen (aus dem externen Board übernommen) | AK-82 |
| — *(kein Repo)* | `endlech.userjot.com` beim Anbieter abschalten | AK-81 |

---

## Abdeckung der Akzeptanzkriterien

Alle 78 Kriterien aus `spec.md`, der Reihe nach aus der Datei abgegangen.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | `app_board_index`, keine `access_control`-Zeile, kein `#[IsGranted]` | |
| AK-02 | `templates/base.html.twig:220` — `footer.feedback` zeigt künftig auf `app_board_index` | ⚠ ersetzt den externen Verweis; siehe OF-07 |
| AK-03 | `findPublishedPaginated()`, `published_at IS NOT NULL` fest verdrahtet | |
| AK-04 | `_board_idea_card.html.twig` | Status als Wort, nicht nur Farbe |
| AK-05 | `findPublishedPaginated(sort)` — `COUNT AS HIDDEN`, Zweitschlüssel `created_at DESC`; Steuerleiste als GET-Formular | |
| AK-06 | Statusparameter derselben Methode; aktiver Filter über `aria-current` in der Steuerleiste | |
| AK-07 | Doctrine `Paginator`, 20 je Seite, kein fetch-join einer Collection | Gegenteil von BF-64 |
| AK-08 | leerer Zustand in `board/index.html.twig` | |
| AK-09 | `app_board_show` | |
| AK-10 | `#[IsGranted('ROLE_USER')]` auf `app_board_new` | |
| AK-11 | Controller prüft `isVerified()`, leitet auf `app_verify_notice` | Muster `CommunityController` |
| AK-12 | `BoardIdeaType` mit `NotBlank` je Feld, `_form_field.html.twig`; `render()` liefert bei submitted-invalid selbst 422 | |
| AK-13 | `Length(max: 120)` **und** `empty_data: ''` | ohne `empty_data` wird aus der Meldung ein 500er |
| AK-14 | `Length(max: 2000)` | |
| AK-15 | `app_board_thanks`; `published_at` bleibt `NULL` | |
| AK-16 | Hinweisblock im Formular, vor dem Absendeknopf | |
| AK-17 | Fallenfeld im Controller geprüft, gleiche Erfolgsantwort | Muster `PartnerController` |
| AK-18 | `app_board_show`: veröffentlicht → alle; wartend → nur Verfasser, sonst `createNotFoundException()` | |
| AK-19 | `BoardVoteService::toggle()` | |
| AK-20 | UNIQUE `(idea_id, user_id)` **plus** Prüfung im Dienst | Datenbank ist die letzte Instanz |
| AK-21 | derselbe Endpunkt schaltet um | |
| AK-22 | `#[IsGranted('ROLE_USER')]`; der `form_login`-Entry-Point leitet zur Anmeldung | |
| AK-23 | echtes `<form method="post">`, POST-Redirect-GET | Technische Entscheidung 7 |
| AK-24 | `findAwaitingReview()`, `created_at ASC` | |
| AK-25 | `AdminStatsService::pendingBoardIdeaCount()` + Dashboard-Kachel | Änderung an bestehendem Code |
| AK-26 | `BoardModerator::publish()` setzt `published_at` | |
| AK-27 | `BoardModerator::decline()` bricht ab, wenn die Begründung leer ist; Flash, keine Änderung | |
| AK-28 | Status `declined` **und** `published_at` gesetzt → erscheint im Board | Ablehnung ist eine Veröffentlichung |
| AK-29 | `#[IsGranted('ROLE_ADMIN')]` auf Klassenebene + `access_control ^/[a-z]{2}/admin` | |
| AK-30 | `admin_board_delete`, harte Löschung, nur bei `published_at IS NULL`; kein Versand | |
| AK-31 | `BoardIdeaStatus` + Template zeigt Emoji, Wort und Farbe | |
| AK-32 | Feld `team_response`, im Template als Antwort des Teams ausgezeichnet | |
| AK-33 | `notified_at` ist bereits gesetzt → `BoardNotifier` schickt nicht erneut | |
| AK-34 | `BoardModerator::merge()` hängt Stimmen um; Konten mit Stimme auf beiden Seiten werden verworfen, UNIQUE fängt den Rest | |
| AK-35 | `duplicate_of_id` gesetzt → `app_board_show` leitet auf das Original, Repository filtert `duplicate_of_id IS NULL` | |
| AK-36 | `BoardNotifier` baut den Link auf `duplicateOf ?? idea` | |
| AK-37 | `BoardNotifier` beim erstmaligen Setzen von `published_at`, `->locale($idea->getLocale())` | |
| AK-38 | `notified_at` als Sperre | |
| AK-39 | `publish()` flusht **zuerst**, dispatcht danach; der Versand läuft über die Messenger-Queue | |
| AK-40 | Block `board:` in vier Katalogen | |
| AK-41 | `lang`-Attribut am Textelement **und** sichtbare Sprachkennzeichnung | zugleich WCAG 3.1.2 |
| AK-42 | `locale` aus `$request->getLocale()` beim Anlegen | Muster `RestaurantSuggestion::setLocale()` |
| AK-43 | `CatalogueCompletenessTest` | prüft auch Formularbeschriftungen |
| AK-44 | `aria-pressed` am Zustimmungsknopf plus Textwechsel | |
| AK-45 | Emoji + Wort + Farbe am Statusabzeichen | |
| AK-46 | **Kartenlayout statt Tabelle** | ⚠ Lehre aus BF-77: Die Merkmalstabelle von `03` scrollte bei 320 px waagerecht |
| AK-47 | `min-h-[48px]`, `focus:outline-2 focus:outline-offset-2` | kein `outline-none` |
| AK-48 | `_form_field.html.twig` mit `autofocus` am ersten Fehlerfeld | wirkt ohne JavaScript |
| AK-49 | Steuerleiste als GET-Formular, Zustimmen und Zurückziehen als POST-Formulare | |
| AK-50 | Template zeigt ausschließlich den abgeleiteten Anzeigenamen | |
| AK-51 | `App\Board\AuthorName` | Unit-Test, deckt EC-01 und EC-02 mit ab |
| AK-52 | Das Board loggt keine Beitragstexte; geloggt wird die Kennung | |
| AK-53 | Das Board ruft `MarketingContactRegistry` nirgends | prüfbar durch Abwesenheit |
| AK-54 | Empfänger ist ausschließlich der Verfasser; die Mail führt Titel und Link, nicht den Volltext | Technische Entscheidung 8 |
| AK-55 | `send_default_pii: false`, `zend.exception_ignore_args=On` | bereits gesetzt, gilt weiter |
| AK-56 | `createNotFoundException()` bei fremder wartender Idee | 404, nicht 403 |
| AK-57 | `#[IsGranted('ROLE_ADMIN')]` — greift vor jeder Zustandsänderung | |
| AK-58 | Der Endpunkt nimmt keine Konto-Kennung entgegen; das Konto kommt aus der Sitzung | |
| AK-59 | Limiter `board_submit`, 5/Stunde, `ActionLimiter` | |
| AK-60 | Limiter `board_vote`, 60/Stunde | |
| AK-61 | Schlüssel ist `getUserIdentifier()`, nicht `getClientIp()` | |
| AK-62 | `consume()` steht nach `$form->isValid()` | |
| AK-63 | kein `FileType` im Formular; `allow_extra_fields: false` weist Untergeschobenes mit 422 ab | |
| AK-64 | kein Verlinkungsfilter; Twig maskiert | Technische Entscheidung 9 |
| AK-65 | `ON DELETE SET NULL` auf `submitted_by_id`; `AuthorName` liefert dann `null` | |
| AK-66 | `ON DELETE CASCADE` auf `board_vote`; die Zahl wird gezählt und stimmt danach von allein | Technische Entscheidung 2 |
| AK-67 | `AccountDataExporter` um zwei Blöcke erweitert | Änderung an ausgeliefertem Code |
| AK-68 | kein Namens-Schnappschuss an der Idee | Technische Entscheidung 4 |
| AK-69 | Functional-Test über den vollen Durchlauf | `AbstractWebTestCase::loginAs()` |
| AK-70 | Testsuite; `LimiterCoverageTest` und `CatalogueCompletenessTest` laufen mit | |
| AK-71 | `findPublished*()` ist die einzige öffentliche Quelle, `BoardModerator` die einzige Stelle, die `published_at` setzt | die beiden Stellen, die geprüft werden müssen |
| AK-72 | Hinweistext im Formular **und** auf `app_board_thanks` | |
| AK-73 | `admin_board_index`, Stufe 1: Einreichungen älter als **drei Werktage** sind gekennzeichnet | Schwelle durch OF-08 entschieden |
| AK-74 | `StaleIdeaCleaner` über `app:board:cleanup` **und** faul beim Öffnen der Warteschlange | Technische Entscheidung 6 |
| AK-75 | `findPublishedDone()` als zweite Abfrage, eigener Abschnitt unter der Liste; die Hauptliste schließt `done` aus | |
| AK-76 | `app_board_withdraw`, harte Löschung, kein Versand | |
| AK-77 | `app_board_withdraw` prüft `published_at IS NULL`; sonst 403 ohne Änderung | serverseitig, nicht durch einen fehlenden Knopf |
| AK-78 | Fortschreibung von `docs/datenschutz.md` | **Dokumentationsaufgabe**, kein Code — gehört als eigene Aufgabe in `tasks.md`, sonst fällt sie zwischen die Stühle wie VB-03 in `05` |
| AK-79 | `admin_board_index`, Stufe 2: älter als **fünf Werktage** — eigene, deutlichere Kennzeichnung mit eigenem Wort, nicht nur eigener Farbe | Werktag = Mo–Fr, ohne Feiertagsrechnung |
| AK-80 | `templates/base.html.twig:220` — der bisherige externe Verweis wird **ersetzt**; `target="_blank"` und `rel` entfallen | schärft AK-02: genau ein Verweis |
| AK-81 | Abschaltung von `endlech.userjot.com` beim Anbieter | **Betriebsaufgabe**, kein Code — gehört in `tasks.md` und in die Deploy-Nachverifikation |
| AK-82 | Fortschreibung von `docs/prd.md` um „Chat-Widget" und „KI-Filter" | **Dokumentationsaufgabe**, kein Code |

**Keine Zeile ist leer.** Mehrere Kriterien werden nicht durch Code erfüllt, sondern durch
Konfiguration (AK-59 bis AK-61), durch bestehende Einstellungen (AK-55), durch Abwesenheit
(AK-53, AK-63, AK-64), durch Dokumentation (AK-78, AK-82) oder durch einen Handgriff beim
Anbieter (AK-81) — sie stehen trotzdem hier und brauchen im Testbericht einen eigenen
Nachweis.

---

## Offene Fragen zurück an die Spezifikation — beide am 2026-08-30 entschieden

- **OF-07** · ~~Was geschieht mit dem Bestand auf `endlech.userjot.com`?~~ → **Entschieden,
  nachdem nachgesehen wurde:** sieben Einträge, alle vom Betreiber, alle null Stimmen. Kein
  Community-Bestand. Das Board startet leer, die Titel wandern in die PRD-Roadmap (AK-82),
  der Fußzeilenverweis wird ersetzt statt ergänzt (AK-80), der externe Dienst wird nach dem
  Deploy abgeschaltet (AK-81).
- **OF-08** · ~~Passt die Schwelle in AK-73 zur Zusage in AK-72?~~ → **Entschieden:
  zweistufig.** Hinweis ab drei Werktagen (AK-73), deutliche Warnung ab fünf (AK-79). Die
  erste Stufe warnt, bevor die Zusage bricht, die zweite genau dann. **Werktag = Montag bis
  Freitag, ohne Feiertagsrechnung** — eine Feiertagstabelle für Luxemburg wäre eigene
  Mechanik ohne Gegenwert an dieser Stelle.

**Damit ist keine Frage dieses Features mehr offen.**

## Hinweise für `/sdd-tasks 06`

1. **Reihenfolge der Ebenen:** Enum und Entitäten → Migration → Repository und Dienste →
   Controller und Formular → Templates und Übersetzungen → Änderungen am Bestand
   (Export, Löschung, Dashboard, Fußzeile) → Tests.
2. **Die Migration wird gelesen, bevor sie läuft.** `make migration` schlägt in diesem
   Projekt regelmäßig Index-Umbenennungen aus Altlasten vor, die nichts mit der Änderung
   zu tun haben.
3. **Gegen MariaDB 10.5 lauffähig bleiben.** Zwei Tabellen mit gewöhnlichen Spalten und
   Fremdschlüsseln sind unkritisch; die Abfrage mit `GROUP BY` und `COUNT` ebenfalls.
   Kein natives `ENUM`, kein `JSON_TABLE`, keine Fensterfunktion.
4. **Änderungen unter `assets/` sind nicht vorgesehen** — das Feature kommt ohne Stimulus
   aus. Entsteht doch eine, gehört `npm run build` und der committete `public/build` dazu,
   sonst blockt `verify-assets` den Deploy.
5. **Drei Aufgaben liegen außerhalb des Quelltexts** und fallen sonst zwischen die Stühle
   wie VB-03 in `05`: AK-78 (`docs/datenschutz.md`), AK-82 (`docs/prd.md`) und **AK-81 —
   die Abschaltung von `endlech.userjot.com` beim Anbieter.** Letztere gehört zusätzlich in
   die Nachverifikation nach dem Deploy, denn sie ist der einzige Schritt, den kein
   Prüflauf sehen kann.
6. ⚠ **Nebenbefund, nicht Teil dieses Features:** Der Klassenkommentar in `src/Schedule.php`
   behauptet, Production laufe mit `MESSENGER_TRANSPORT_DSN=sync://` und ohne Worker. Seit
   dem 2026-08-30 stimmt das nicht mehr — die Queue ist in Betrieb. Der Zeitplan feuert
   dort trotzdem nicht, weil der Cron nur `async` konsumiert und nicht
   `scheduler_default`; die Schlussfolgerung des Kommentars bleibt also richtig, seine
   Begründung nicht. Gehört korrigiert, aber in einem eigenen Auftrag.
