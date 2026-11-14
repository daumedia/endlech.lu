# 17 · Favoriten und Listen — Aufgabenplan

Status: `building` · Stand: 2026-11-13

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
php bin/console doctrine:schema:validate          # ab Ebene 1: Entitäten und Tabellen gleich
php bin/phpunit tests/Unit                        # Zahl ablesen — null ist rot
```

Nach Ebene 1 die Test-Datenbank migrieren (`php bin/console doctrine:migrations:migrate
--env=test --no-interaction`). Nach Ebene 3 und vor der Übergabe zusätzlich
`php bin/console tailwind:build`, `php bin/console typescript:build`, `npm run typecheck`,
`npm run lint` und die **volle Suite** `php bin/phpunit`.

## Ebene 1 · Fundament — Daten und Konfiguration

- [x] **T01** · Vier Entitäten mit Repositories nach `design.md`, Datenmodell:
      `Favorite`, `SavedList` (mit `name_key`, Kollation `utf8mb4_bin`),
      `SavedListEntry`, `RestaurantNote`; Fremdschlüssel `ON DELETE CASCADE`, eindeutige
      Indizes und Sortierindizes; Beziehungen einseitig, **keine** Änderung an `User` oder
      `Restaurant`. Jede Repository-Methode, die Zeilen eines Kontos liefert, nimmt das
      Konto als Pflichtargument (`findOneOwned(id, user)` usw.). Eine Migration, nur
      `CREATE TABLE` — erzeugte Fassung lesen, bevor sie läuft. `bin/sicherung-pruefen.sh`:
      vier Namen in `ERWARTETE_TABELLEN`. Prüflauf `SavedCascadeTest` (Integration) zuerst
      rot: Konto löschen → keine Zeile der vier Tabellen übrig; Lokal löschen (über den
      EntityManager wie B20) → keine Zeile zeigt mehr darauf; Liste löschen → Einträge weg,
      Favorit und Notiz bleiben; zweiter Favorit für dasselbe Paar scheitert am Index.
      — `AK-61, AK-62, AK-30`, Grundlage für T05–T12
- [x] **T02** `[P]` · `App\Saved\SavedWriteLock`: Schreibsperre je Konto über `LockFactory`
      (Schlüssel `gespeichert-{id}`, blockierend, mit Freigabe im `finally`), Muster
      `AvatarUploadService`. Prüflauf: zweite Sperre desselben Kontos wartet, die eines
      anderen Kontos nicht. — Grundlage für T05, T06, T07
- [x] **T03** `[P]` · `config/packages/sentry.yaml`, `when@prod`:
      `max_request_body_size: never` mit Kommentar (Befund aus `design.md`, *Systemweite
      Änderungen*). Prüflauf `SentryConfigTest` liest die Produktionskonfiguration aus der
      Datei und verlangt den Wert — Gegenprobe: ohne die Zeile rot. — `AK-65`
- [x] **T04** `[P]` · Übersetzungen in allen fünf Sprachen (`lb`, `de`, `fr`, `en`, `pt`;
      pt europäisch, höfliche Anrede ohne „você“): `messages` — Herz (zwei Knopftexte mit
      `%name%`), Meldungen (gemerkt, entfernt, Listen gespeichert, Liste angelegt/umbenannt/
      gelöscht, aus Liste entfernt, Notiz gespeichert/gelöscht, Deckel mit `%minutes%`,
      Anmeldehinweis mit `%name%`), Seiten „Gespeichert“/„Favoriten“/Liste/Rückfrage samt
      Leertexten, Abschnitte „In Listen“/„Deine Notiz“ mit Hinweis, Filter „Nur meine
      Favoriten“ und Leertext AK-40, Navigation (Kopfzeile, Leiste, Profil), Absatz für
      `/legal`; `validators` — Name leer, Name zu lang, Dublette, 25 Listen, Notiz zu lang.
      Pluralformen mit `{0}` (Zahl der Lokale). Wortlaut der deutschen Fassung aus
      `spec.md`. Beschriftung „Gespeichert“ der Leiste in jeder Sprache ≤ 11 Zeichen
      (AK-42). — `AK-46`, Grundlage für Ebene 3 und 4

## Ebene 2 · Server — Logik und Validierung

- [x] **T05** `[P]` · `App\Saved\FavoriteService`: `merken` und `entfernen` als getrennte
      Absichten unter `SavedWriteLock`; Rückgabe „geändert ja/nein“ (für Deckel und
      Meldung). Prüflauf zuerst rot: merken → Zeile; merken auf gemerktes → keine zweite
      Zeile, „nicht geändert“; entfernen auf entferntes → „nicht geändert“, keine Ausnahme;
      entfernen lässt Listeneinträge und Notiz stehen. — `AK-01, AK-03, AK-04, AK-05`
- [x] **T06** `[P]` · `App\Saved\SavedListService`: anlegen, umbenennen, löschen,
      Mitgliedschaften eines Lokals setzen (eigene Listen; optional neue Liste mit dem Lokal
      — alles in **einer** Transaktion), unter `SavedWriteLock`. Namensregeln an dieser einen
      Stelle: trimmen, NFC, Länge ≤ 60 Zeichen, `name_key` = Kleinschreibung, Dublette je
      Konto, höchstens 25 Listen; Fehler als benannte Ergebnisse, die der Controller ans Feld
      hängt. Prüflauf zuerst rot: „ kirchberg “ neben „Kirchberg“ → Dublette; „Café“ neben
      „Cafe“ → erlaubt; eigener Name beim Umbenennen → keine Dublette; 26. Liste →
      abgewiesen, auch über „neue Liste“ beim Setzen von Mitgliedschaften; scheitert der
      neue Name, bleiben die Häkchen unverändert; doppeltes Setzen → ein Eintrag; 60 Emoji
      / 61 Buchstaben / zusammengesetzte Emoji → gespeichert oder abgewiesen, **nie** eine
      Datenbankausnahme. — `AK-16, AK-17, AK-18, AK-19, AK-20, AK-22, AK-23, AK-28,
      AK-30, AK-31`, `EC-05, EC-07`
- [x] **T07** `[P]` · `App\Saved\RestaurantNoteService`: speichern oder löschen;
      `\r\n` → `\n` vor dem Zählen, trimmen, Länge ≤ 500, leer bzw. nur Leerraum → Zeile
      löschen; unter `SavedWriteLock`. Prüflauf zuerst rot: 500 Zeichen mit sechs
      Zeilenumbrüchen als `\r\n` → gespeichert; 501 → abgewiesen, bisherige Notiz
      unverändert; Leerraum → gelöscht; zweites Speichern überschreibt (letzter gewinnt).
      — `AK-33, AK-35, AK-36`, `EC-06, EC-08`
- [x] **T08** `[P]` · `App\Saved\PendingFavorite`: Merkwunsch in der Sitzung ablegen,
      lesen, löschen; Prüfung des Felds `ziel` über den Router — nur
      `app_restaurant_index`, `app_restaurant_show`, `app_saved_favorites`,
      `app_saved_list_show` in der aktuellen Sprache, sonst Detailseite des Lokals;
      Abfrageteil nur bei der Suche, bei den übrigen nur `page`. Prüflauf zuerst rot:
      `https://example.com`, `//example.com`, `/\example.com`, `/de/admin`,
      `/fr/restaurants` aus einer `de`-Seite, `javascript:` → jeweils Detailseite;
      `/de/restaurants?wheelchair=1&page=2` → bleibt. — `AK-06, AK-08, AK-09`
- [x] **T09** `[P]` · `App\Saved\SavedState` (für eine Menge Lokale: welche das Konto
      gemerkt hat und welche Notizen dazu gehören — je **eine** Abfrage) und Filter
      „nur Favoriten des Kontos“ in `RestaurantRepository::findPaginated` (zusätzliche
      Bedingung, kombinierbar). Prüflauf zuerst rot: 5 Favoriten, davon 2 rollstuhlgerecht
      → Filter + `wheelchair` liefert 2, Zählung und Blättern stimmen; Konto B sieht keinen
      Merkzustand von A; Zahl der Abfragen für 12 Karten konstant. — `AK-04, AK-34, AK-38,
      AK-52`
- [x] **T10** `[P]` · Formulartypen `SavedListNameType`, `RestaurantListsType` (Häkchen aus
      den eigenen Listen als Auswahl, Feld `newList`), `RestaurantNoteType`; jeweils
      `'empty_data' => ''`, Pflicht/Länge mit Normalisierer (trimmen, `\r\n`), Meldungen
      aus `validators`; **kein** `maxlength`-Attribut. Prüflauf (Unit, `TypeTestCase`):
      leer → Meldung, kein `TypeError`; 61 Zeichen → Meldung; Feld `null` → kein Fehler 500.
      — `AK-17, AK-18, AK-21, AK-24, AK-35, AK-50`
- [x] **T11** `[P]` · `AccountDataExporter`: Schlüssel `favorites` (Lokal-Nummer, Name,
      gemerkt am), `savedLists` (Name, angelegt am, Lokale je mit Zeitpunkt), `notes`
      (Lokal-Nummer, Name, Text, zuletzt geändert); Format bleibt `v1`. Prüflauf zuerst rot
      mit 2 Favoriten, 1 Liste mit 3 Lokalen, 1 Notiz. — `AK-60`
- [x] **T12** `[P]` · Messung: `UsageEventCatalogue` um `herz_gesetzt` und
      `liste_angelegt` (ohne Datenfelder); Pfadkürzung `/{xx}/gespeichert/listen/{id}…` →
      `/{xx}/gespeichert/listen` in `CollectPayloadNormalizer` **und**
      `assets/usage/before_send.ts`. ⚠ `FILTER_KEYS` hier **nicht** anfassen (T19, sonst ist
      `UsageEventCatalogueTest` zwischen den Ebenen rot). Prüflauf zuerst rot: beide
      Ereignisse mit Datenfeld → verworfen, ohne → durchgelassen; `/de/gespeichert/listen/42`
      und `/de/gespeichert/listen/42/loeschen` → gekürzt; `/de/gespeichert` unverändert.
      — `AK-64, AK-66, AK-67`

## Ebene 3 · Schnittstellen

Controller entstehen hier mit **schlichten Vorlagen**, die schon die Formulare, Kennungen,
Anker und Texte tragen, die die Prüfläufe lesen. Gestaltung, Zustände und Einbau in
bestehende Seiten folgen in Ebene 4.

- [x] **T13** · Gemeinsamer Baustein für die Daten der Detailseite (`RestaurantShowView`:
      Öffnungstag, Haltestellen, Radius, Merkzustand und Formulare des Kontos);
      `RestaurantController::show` nutzt ihn, Verhalten unverändert (bestehende Prüfläufe
      der Detailseite bleiben grün). — Grundlage für T14, T15 (422-Antwort rendert die
      Detailseite)
- [x] **T14** · `RestaurantSavedController`: `app_favorite_add` (Gast: Token prüfen,
      `PendingFavorite`, Zielseite der Firewall `main` mit Anker, weiter zur Anmeldung) und
      `app_favorite_remove` (`#[IsGranted]`); Token je Handlungsart `favorit`; Limiter
      **`saved_change` jetzt anlegen** (`framework.yaml`, 120/Std gleitend, `when@test`
      10000) und verdrahten — `isAllowed()` vor, `consume()` nur nach tatsächlicher
      Änderung, Schlüssel `konto-{id}`; Antwort als Turbo-Stream (`replace` mit
      `method="morph"` am Herz-Formular, `update` am Ansagebereich, beim Merken der
      Messauslöser mit `data-turbo-temporary`) oder als 303 auf `ziel#restaurant-{id}`
      mit Meldung und `usage_event`. Dazu `partials/_favorite_button.html.twig` (Formular,
      Knopf `favorit-knopf-{id}`, Text mit Name und Handlung, zwei Herzformen) und die
      Stream-Vorlage. Prüflauf zuerst rot: merken/entfernen mit und ohne Stream; Gast →
      Anmeldung, nichts gespeichert; unbekanntes Lokal → 404; falsches Token → Meldung,
      nichts geändert; 121. Änderung → Meldung, nichts gespeichert, zweites Konto am
      selben Anschluss frei; „merken“ auf Gemerktes zählt nicht; unbestätigtes Konto
      darf; `LimiterCoverageTest` grün. — `AK-01, AK-02, AK-03, AK-05, AK-06, AK-08,
      AK-55, AK-56, AK-57, AK-58, AK-66, AK-69`
- [x] **T15** `[P]` · `RestaurantSavedController`: `app_restaurant_lists_save` und
      `app_restaurant_note_save` (`#[IsGranted]`), Formulare aus T10, Dienste aus T06/T07;
      **übermittelte Listennummern vor dem Verarbeiten gegen die eigenen prüfen** — eine
      fremde oder unbekannte → 404, nichts geändert; Token-Fehler des Formulartyps →
      `flash.invalid_csrf` + Weiterleitung; Fehler → 422 mit Detailseite (T13), Eingabe
      bleibt; Erfolg → 303 auf `#in-listen` bzw. `#notiz`; `liste_angelegt` als
      `usage_event`, wenn eine Liste entstand; Deckel wie T14. Schlichte Vorlage
      `restaurant/_saved_panel.html.twig`. Prüflauf zuerst rot: Häkchen setzen/entfernen,
      neue Liste mit Lokal, Dublette/26. Liste über die Detailseite → 422 ohne
      Häkchenänderung, doppeltes Absenden → ein Eintrag, fremde Listennummer → 404 und
      Liste von A unverändert, Notiz speichern/zu lang/leeren, Gast → Anmeldung.
      — `AK-21, AK-22, AK-23, AK-24, AK-31, AK-32, AK-33, AK-35, AK-36, AK-54, AK-56,
      AK-58, AK-69`
- [x] **T16** `[P]` · `PendingFavoriteSubscriber` auf `LoginSuccessEvent` (nur Firewall
      `main`): Wunsch einlösen mit Deckel, Meldung „Gemerkt: <Name>“ und `usage_event`,
      Wunsch löschen; Lokal fehlt → still verwerfen. `SecurityController::login` und
      `security/login.html.twig`: Hinweis mit Namen, solange ein Wunsch besteht. Prüflauf
      zuerst rot mit dem **echten** Anmeldeformular (`loginUser()` löst kein Ereignis aus):
      Gast auf Seite 2 mit Filter → Herz → Anmeldung zeigt Hinweis → Anmeldung → zurück
      auf Seite 2 mit Filter und Anker, gemerkt; erster Versuch falsches Passwort, zweiter
      richtig → gemerkt; Lokal zwischendurch gelöscht → nichts, keine Meldung; neues Konto
      mit fälliger Einführung → Zielseite gewinnt, Einführung bleibt fällig; Passkey-Weg
      über `onAuthenticationSuccess` nimmt dieselbe Zielseite. — `AK-08, AK-09, AK-10`,
      `EC-01, EC-02, EC-03, EC-04`
- [x] **T17** `[P]` · `SavedController` mit allen acht Routen aus `design.md`;
      `security.yaml` `^/[a-z]{2}/gespeichert` → `IS_AUTHENTICATED_REMEMBERED`; jede Liste
      über `findOneOwned(id, user)`, **Besitz vor Token**; Paginator mit
      `fetchJoinCollection`, 12 je Seite, Seite hinter dem Ende → 404 wie die Suche;
      „Aus Liste entfernen“ als Stream (Kartenraster neu, Fokusziel markiert) oder 303 mit
      Anker auf die nächste Karte (das Herz auf „Favoriten“ folgt in T21); Rückfrage als
      GET-Seite. `SeoRegistry`: vier GET-Routen in `UNLISTED_ROUTES`, `app_saved_favorites`
      und `app_saved_list_show` in `PAGINATED_ROUTES`. Schlichte Vorlagen unter
      `templates/saved/`. Prüflauf zuerst rot: Gast → Anmeldung → Ziel; Übersicht mit
      Zahlen und Reihenfolge; 15 Favoriten → 12 + 3; anlegen/umbenennen/löschen mit allen
      Meldungen; Abbrechen ändert nichts; Konto B auf Liste von A (GET und alle POST) →
      404, gleich wie eine unbekannte Nummer, Liste von A unverändert; Sitemap ohne
      `/gespeichert`; `SeoRouteCoverageTest` grün. — `AK-11, AK-12, AK-14, AK-15, AK-16,
      AK-17, AK-18, AK-19, AK-20, AK-25, AK-26, AK-28, AK-29, AK-30, AK-45, AK-53, AK-54,
      AK-56, AK-57, AK-66, AK-69`, `EC-09, EC-15, EC-16`
- [x] **T18** `[P]` · `App\Saved\PrivatePages` (Routen `app_saved_*`,
      `app_restaurant_index`, `app_restaurant_show`), `PrivatePageCacheSubscriber`
      (`Cache-Control: no-store, private` für Angemeldete auf diesen Routen) und eine
      Twig-Funktion, über die `base.html.twig` dann `turbo-cache-control: no-cache`
      ausgibt. Prüflauf zuerst rot: angemeldet → beide gesetzt; Gast → wie bisher; andere
      Routen unberührt. — `AK-63`

## Ebene 4 · Oberfläche

Jede Seite braucht vier Zustände: leer, ladend, Fehler, gefüllt (Tabelle „Zustände je
Bildschirm“ in `design.md`). Wer nur den gefüllten baut, baut die Hälfte.

- [x] **T19** `[P]` · Restaurantkarte als `partials/_restaurant_card.html.twig` aus
      `restaurant/index.html.twig` herauslösen (Inhalt unverändert), mit Herz, Anker
      `id="restaurant-{id}"` + `scroll-mt-24`, optional Notiz und „Aus Liste entfernen“;
      Suche: Häkchen „Nur meine Favoriten“ nur für Angemeldete, Filterchip, Leertext AK-40
      mit Verweis „Filter entfernen“ (übrige Filter bleiben); `RestaurantController::index`
      liest `favorites` nur für Angemeldete; `UsageEventCatalogue::FILTER_KEYS` + `favorites`.
      Prüflauf zuerst rot: Gast sieht leeres Herz, kein Häkchen, `?favorites=1` ohne Fehler
      und ohne Wirkung; Konto B sieht keinen Merkzustand von A (auch nicht im Quelltext);
      Leertext bei null Favoriten; bestehende Prüfläufe der Suche grün. — `AK-01, AK-07,
      AK-38, AK-39, AK-40, AK-52, AK-66`
- [x] **T20** `[P]` · Detailseite: Herz neben dem Namen in **beiden** Kopfvarianten;
      Abschnitt „Für dich“ (nur angemeldet, nach den Barrierefreiheitshinweisen) mit `h2`
      „In Listen“ (`#in-listen`) und „Deine Notiz“ (`#notiz`, Hinweis „Nur für dich
      sichtbar.“) über `_form_field`; Notiz als Text mit `white-space: pre-line`. Vier
      Zustände (keine Liste, Fehler am Feld mit `autofocus`, gefüllt). Prüflauf zuerst rot:
      Gast ohne Abschnitt; Notiz `<b>fett</b> <script>…` erscheint als Text; Konto B sieht
      Notiz und Listen von A nicht; `HeadingOrderTest` grün. — `AK-02, AK-07, AK-21, AK-24,
      AK-32, AK-34, AK-37, AK-50, AK-52`
- [x] **T21** · Seiten „Gespeichert“, „Favoriten“, Liste und Rückfrage gestalten (Muster
      der Außenseiten, Karten aus T19, Leertexte AK-13/AK-27, Umbenennen am Kopf der Liste,
      Verweis „Liste löschen“); Stimulus `focus_after_stream_controller.ts` setzt nach
      einem Stream den Fokus auf das vom Server markierte Ziel (nächste Karte, vorige,
      Leertext). Herz-Entfernen auf „Favoriten“: `app_favorite_remove` antwortet bei `ziel`
      = Favoriten mit dem neu gerenderten Kartenraster statt nur dem Herz, ohne JS mit Anker
      auf die nächste Karte. Hängt an T19 (Kartenpartial). Prüflauf zuerst rot: Leertexte,
      Zahlen, Reihenfolge, Notiz unter der Karte in drei Ansichten, Karte nach dem
      Entfernen weg, genau eine `h1` je Seite.
      — `AK-11, AK-12, AK-13, AK-14, AK-25, AK-26, AK-27, AK-29, AK-34`
- [x] **T22** `[P]` · App-Hülle: Kopfzeile, Kontobereich — Verweis „Gespeichert“
      (angemeldet, ab 768 px); `partials/_bottom_nav.html.twig` — angemeldet Start ·
      Restaurants · Gespeichert · Profil, aktiv auf allen `app_saved_*`, Gäste unverändert;
      `base.html.twig` — dauerhafter, leerer Ansagebereich (`role="status"`,
      `aria-live="polite"`, `aria-atomic="true"`) im Meldungscontainer;
      `profile/index.html.twig` — Verweis auf „Gespeichert“. Prüflauf zuerst rot: Leiste
      angemeldet/abgemeldet, `aria-current`, Verweise vorhanden. — `AK-41, AK-43, AK-44,
      AK-48`
- [x] **T23** `[P]` · `/legal`, Datenschutzabschnitt: Absatz zu Favoriten, Listen und
      Notizen (was, nur der Inhaber, kein Dienstleister, Export, Löschung mit dem Konto),
      fünf Sprachen. Prüflauf: Absatz in jeder Sprache vorhanden. — `AK-68`

## Ebene 5 · Feinschliff

- [x] **T24** · Im Browser (Chrome und Safari, mit dem lokalen Server):
      Telefon-Durchgang bei 375 px — merken in der Suche, Liste anlegen, Lokal auf der
      Detailseite hineinlegen, Notiz, Filter — ohne Sprung nach oben und ohne waagerechte
      Scrollleiste; Leiste bei 320 px in fünf Sprachen einzeilig; VoiceOver: Herznamen,
      Ansage „Gemerkt: …“, **Fokus bleibt nach dem Morph am Herz** (bleibt er nicht:
      kleiner Stimulus-Controller stellt ihn über die Kennung wieder her); Kontrast ≥ 3 : 1
      beider Herzformen, 44 × 44 px, sichtbarer `outline`-Fokus; ohne JavaScript dieselben
      Schritte mit Rücksprung zur Karte; axe auf allen neuen Seiten. Ergebnisse mit
      Bildschirmfotos unter `qa/17/`. — `AK-06, AK-14, AK-42, AK-47, AK-48, AK-49, AK-51`
- [x] **T25** · Im Browser: angemeldet „Gespeichert“ und eine Detailseite mit Notiz
      öffnen, abmelden, „Zurück“ → keine Listen, keine Notiz (Turbo-Speicher **und**
      Back-Forward-Cache, Chrome und Safari); offline in der installierten App →
      Offline-Seite, keine Listen. — `AK-63`, `EC-11`
- [x] **T26** · Abfluss und Verwaltung: Prüflauf über alle GET-Routen unter `/admin` als
      Admin mit einem Konto, das Favoriten, eine Liste „Mit Oma“ und eine Notiz hat →
      weder Name noch Notiz noch eine Anzahl erscheint; beim Anlegen einer Liste und
      Speichern einer Notiz enthält kein Protokolleintrag (Test-Handler), keine Mail und
      kein Brevo-Auftrag Listennamen oder Notiztext. — `AK-59, AK-64`
- [x] **T27** · Randfälle, die noch kein Prüflauf trägt: EC-10 (Filter aktiv, Herz
      entfernt), EC-12 (Merkmal fällt weg, bleibt gemerkt), EC-13 (unbestätigtes Konto
      behält seine Daten), EC-14 (Sprachwechsel auf einer Listenseite, Name unverändert).
      — `EC-10, EC-12, EC-13, EC-14`, `AK-46`
- [x] **T28** · Dokumentation mitziehen: `docs/data-model.md` (vier Entities, ERD,
      Migration), `docs/datenschutz.md` (Abschnitt „Favoriten, Listen und Notizen“,
      Einstufung B begründet), `docs/app-shell.md` (Leiste, Kopfzeile, Ansagebereich),
      `docs/prd.md` (Zielgruppe 1, Roadmap-Tabelle), `CLAUDE.md` (Abschnitt mit den
      Fallstricken: Kaskade in der DB, Besitz über die Abfrage, `name_key`, Schreibsperre,
      Herz mit Absicht, `no-store` privater Seiten, Sentry-Rumpf, Pfadkürzung an zwei
      Stellen, Leiste vier Punkte). — Aufgabe ohne AK, Projektregel (CLAUDE.md: „Bei
      Änderungen am Datenmodell … die passende Datei mitziehen“)
- [ ] **T29** · Beim Release (nicht im Feature-Commit): `RoadmapRegistry` ohne
      `favourites`, Texte des Eintrags in `roadmap.*.yaml` entfernen;
      `ChangelogRegistry::notes()` mit `ReleaseVisibility::SHOWN` und Text in allen
      `changelog.*.yaml`; dazu die fünf Release-Stellen aus CLAUDE.md, *Versioning*.
      — `AK-70`

## Abdeckung

| AK | Aufgaben |
|---|---|
| AK-01 | T05, T14, T19 |
| AK-02 | T14, T20 |
| AK-03 | T05, T14 |
| AK-04 | T05, T09 |
| AK-05 | T05, T14 |
| AK-06 | T08, T14, T24 |
| AK-07 | T19, T20 |
| AK-08 | T08, T14, T16 |
| AK-09 | T08, T16 |
| AK-10 | T16 |
| AK-11 | T17, T21 |
| AK-12 | T17, T21 |
| AK-13 | T21 |
| AK-14 | T17, T21, T24 |
| AK-15 | T17 |
| AK-16 | T06, T17 |
| AK-17 | T06, T10, T17 |
| AK-18 | T06, T10, T17 |
| AK-19 | T06, T17 |
| AK-20 | T06, T17 |
| AK-21 | T10, T15, T20 |
| AK-22 | T06, T15 |
| AK-23 | T06, T15 |
| AK-24 | T10, T15, T20 |
| AK-25 | T17, T21 |
| AK-26 | T17, T21 |
| AK-27 | T21 |
| AK-28 | T06, T17 |
| AK-29 | T17, T21 |
| AK-30 | T01, T06, T17 |
| AK-31 | T06, T15 |
| AK-32 | T15, T20 |
| AK-33 | T07, T15 |
| AK-34 | T09, T20, T21 |
| AK-35 | T07, T10, T15 |
| AK-36 | T07, T15 |
| AK-37 | T20 |
| AK-38 | T09, T19 |
| AK-39 | T19 |
| AK-40 | T19 |
| AK-41 | T22 |
| AK-42 | T04, T24 |
| AK-43 | T22 |
| AK-44 | T22 |
| AK-45 | T17 |
| AK-46 | T04, T27 |
| AK-47 | T24 |
| AK-48 | T22, T24 |
| AK-49 | T24 |
| AK-50 | T10, T20 |
| AK-51 | T24 |
| AK-52 | T09, T19, T20 |
| AK-53 | T17 |
| AK-54 | T15, T17 |
| AK-55 | T14 |
| AK-56 | T14, T15, T17 |
| AK-57 | T14, T17 |
| AK-58 | T14, T15 |
| AK-59 | T26 |
| AK-60 | T11 |
| AK-61 | T01 |
| AK-62 | T01 |
| AK-63 | T18, T25 |
| AK-64 | T12, T26 |
| AK-65 | T03 |
| AK-66 | T12, T14, T15, T17, T19 |
| AK-67 | T12 |
| AK-68 | T23 |
| AK-69 | T14, T15, T17 |
| AK-70 | T29 |

| EC | Aufgaben |
|---|---|
| EC-01 – EC-04 | T16 |
| EC-05 | T06 |
| EC-06 | T07 |
| EC-07 | T06 |
| EC-08 | T07 |
| EC-09 | T17 |
| EC-10 | T27 |
| EC-11 | T25 |
| EC-12 – EC-14 | T27 |
| EC-15, EC-16 | T17 |

**AK ohne Aufgabe:** keine — alle 70 abgegangen.
**Aufgabe ohne AK:** T01 (auch Grundlage), T02 (Schreibsperre, Grundlage für T05–T07),
T13 (Baustein der Detailseite, Grundlage für T14/T15), T28 (Dokumentation, Projektregel) —
zulässig.

## Parallelisierung

Ebene 1: **T02, T03, T04** laufen gleichzeitig neben T01 — T01 berührt `src/Entity/`,
`src/Repository/`, `migrations/`, `bin/sicherung-pruefen.sh`; T02 nur
`src/Saved/SavedWriteLock.php` + Test; T03 nur `config/packages/sentry.yaml` + Test; T04
nur `translations/messages.*.yaml` und `validators.*.yaml`. T01 selbst nicht `[P]`
(eine Migration).

Ebene 2: **T05 bis T12** gleichzeitig — je eine eigene Datei unter `src/Saved/` bzw.
`src/Form/` und ihr Test; T09 zusätzlich `src/Repository/RestaurantRepository.php`, T11
`src/Account/AccountDataExporter.php`, T12 `src/Usage/UsageEventCatalogue.php`,
`src/Usage/CollectPayloadNormalizer.php`, `assets/usage/before_send.ts`. Keine Datei
doppelt; Dienste werden automatisch verdrahtet (`services.yaml` bleibt unberührt).

Ebene 3: **T13 → T14** nacheinander (T14 legt den Limiter in `framework.yaml` an, den alle
weiteren brauchen, und rendert über T13). Danach **T15, T16, T17, T18** gleichzeitig:
T15 `RestaurantSavedController.php` (nur T14 und T15 berühren ihn, nacheinander) +
`restaurant/_saved_panel.html.twig`; T16 `src/EventSubscriber/PendingFavoriteSubscriber.php`,
`SecurityController.php`, `security/login.html.twig`; T17 `SavedController.php`,
`config/packages/security.yaml`, `src/Seo/SeoRegistry.php`, `templates/saved/*`; T18
`src/Saved/PrivatePages.php`, `src/EventSubscriber/PrivatePageCacheSubscriber.php`, eine
Twig-Erweiterung, `base.html.twig` (Kopf). Jede Aufgabe mit eigenen Testdateien.

Ebene 4: **T19, T20, T22, T23** gleichzeitig — T19 `restaurant/index.html.twig`,
`partials/_restaurant_card.html.twig`, `RestaurantController.php`, `UsageEventCatalogue.php`;
T20 `restaurant/show.html.twig`, `restaurant/_saved_panel.html.twig`; T22 `base.html.twig`
(Kopfzeile, Meldungscontainer), `partials/_bottom_nav.html.twig`, `profile/index.html.twig`;
T23 `impressum/index.html.twig`. **T21** danach (braucht das Kartenpartial aus T19;
berührt `templates/saved/*`, `assets/controllers/focus_after_stream_controller.ts` und
`RestaurantSavedController.php`, den in Ebene 4 sonst niemand anfasst).

Ebene 5: nacheinander; T24 und T25 brauchen den laufenden lokalen Server und einen Browser.

## Vor dem Bauen

- [x] **Arbeitsstand klären:** Auf `main` liegen nicht festgeschriebene Änderungen aus einer
      früheren Sitzung (`CLAUDE.md`, sieben `spec.md`, `features/index.md`). Vor dem
      Abzweigen getrennt festschreiben oder bewusst mitnehmen — sonst landen sie im
      Feature-Commit.
- [x] Feature-Branch: `git checkout -b feature/17-favoriten-und-listen`
- [x] Docker-Dienste laufen (`make start`), Test-Datenbank steht (`make test-db-setup`)
- [x] Watcher laufen (`symfony server:start` startet Tailwind und TypeScript mit) — sonst
      liefert der lokale Server veraltete Assets ohne Fehlermeldung
- [x] Keine neuen Schlüssel oder Umgebungsvariablen nötig
- [x] Kein Verzeichnis `public/gespeichert` anlegen (BF-100)

## Abschlussbericht · 2026-11-14

### 1 · Umgesetzt

Angemeldete Konten (auch unbestätigte) merken ein Lokal mit dem Herz auf jeder Suchkarte und auf der Detailseite,
legen eigene Listen an, hängen ein Lokal per Häkchen in Listen, schreiben eine private Notiz je Lokal und finden alles
auf „Gespeichert“ (Übersicht, Favoriten, je Liste eine Seite, Rückfrage vor dem Löschen). Die Suche filtert auf die
eigenen Favoriten. Gäste landen über das Herz in der Anmeldung, danach ist das Lokal gemerkt und die Seite steht bei der
Karte. Alles funktioniert ohne JavaScript; mit Turbo tauscht ein Stream nur das Herz bzw. das Kartenraster, der Fokus
bleibt bzw. wandert zur nächsten Karte, Meldungen gehen in einen Ansagebereich. Datenexport, Kontolöschung (DB-Kaskade),
`/legal`-Absatz in fünf Sprachen, Deckel 120/h am Konto, Messung ohne Inhalt.

**28 von 29 Aufgaben** erledigt; T29 gehört in den Release-Commit (Roadmap/Changelog, AK-70). **69 von 70 Kriterien**
im Code abgedeckt und durch Prüfläufe bzw. den Browser-Durchgang belegt; AK-70 folgt mit dem Release.

### 2 · Offene Akzeptanzkriterien

- **AK-70** (Roadmap/Changelog) — bewusst nicht im Feature-Commit; T29 beim Release (`RoadmapRegistry` ohne
  `favourites`, `ChangelogRegistry` + fünf `changelog.*.yaml`).
- **AK-63** (nach dem Abmelden zeigt „Zurück“ nichts) — im Chromium-Browser der Vorschau bestanden
  (`qa/17/browser-durchgang-2026-11-14.md`), **in Safari nicht geprüft**. Safari behandelt den Back-Forward-Cache
  eigen; `no-store` sollte greifen, belegt ist es nicht.
- **AK-47/AK-48** (VoiceOver) — geprüft wurde die Voraussetzung im DOM (zugänglicher Name, Fokus bleibt am Knopf,
  `role="status"`-Bereich gefüllt), **nicht die Ansage eines echten Screenreaders**.
- **AK-06** (ohne JavaScript bei derselben Karte) — durch Funktionstests ohne Turbo belegt (303 mit Anker), nicht in
  einem Browser mit abgeschaltetem JavaScript.

### 3 · Getroffene Annahmen und Abweichungen vom Entwurf

- **Anker heißt `#favorit-{id}`** (das Herz-Formular selbst), nicht `#restaurant-{id}` wie in `design.md`. Die Karte
  trägt zusätzlich `id="karte-{id}"` für die Gespeichert-Seiten.
- **Kartenseiten ohne `Paginator`:** Favoriten und Listen holen erst eine Seite Lokalnummern (sortiert, `LIMIT`) und dann
  die Karten über `RestaurantRepository::findForCards()` in dieser Reihenfolge. Gleiches Ziel wie im Entwurf (keine
  BF-64-Falle), einfacher.
- **`SeoRegistry::PAGINATED_ROUTES` ohne die beiden Gespeichert-Seiten** (Entwurf und T17 sahen sie vor):
  `SeoRouteCoverageTest` gleicht dort nur angebotene Seiten ab und würde rot; private Seiten haben keinen canonical.
- **Zwei Stimulus-Controller, die der Entwurf nicht kannte**, beide nach Messung im Browser:
  `scroll_to_controller.ts` (Turbo verliert den Anker einer Weiterleitung — nach der Anmeldung über das Herz und nach
  dem Speichern auf der Detailseite stand die Seite oben; AK-09, AK-51) mit neuer Flash-Art `scroll_to`, und
  `favorite_button_controller.ts` (Turbo 8 setzt den Absendeknopf während des Sendens auf `disabled`, der Fokus ging
  trotz Morph verloren; AK-48). Dazu `focus_after_stream_controller.ts` (im Entwurf vorgesehen).
- **Fokusziel nach dem Entfernen einer Karte ist der Karten-Umschlag (`li`, `tabindex="-1"` nur im Stream)**, nicht
  ein Namens-Link — die Karte hat keinen, und so löst ein zweites Enter nicht das nächste Entfernen aus.
- **Neuer Dienst `App\Saved\SavedCards`** (Kartenlogik für `SavedController` und das Herz auf „Favoriten“).
- **Die Messung entfernt auf Listenseiten auch den Seitentitel** (sonst ginge der Listenname über `<title>` an Umami;
  AK-64). Muster kürzt alles nach `/gespeichert/listen/`.
- **Nach einer abgewiesenen Umbenennung (422) zeigt die Listenseite Seite 1**; bei erschöpftem Deckel erscheint die
  Deckel-Meldung vor einem Feldfehler; ein falsches Token beim Löschen führt zurück auf die Rückfrage.
- **„Gemerkt“ erscheint auch, wenn das Lokal schon gemerkt war** (veraltete Seite) — Messereignis nur bei echter Änderung.
- **Auf Detailseite und Suche bekommen Angemeldete `no-store`** — „Zurück“ lädt diese Seiten für sie neu.

### 4 · Systemweite Änderungen

| Änderung | Datei | Wirkung über das Feature hinaus |
|---|---|---|
| Sentry ohne Anfragerumpf | `config/packages/sentry.yaml` (`max_request_body_size: never`) | ⚠ Bis jetzt ging bei jedem Fehler der Rumpf bis 10 KB mit — **auch Passwortfelder und Board-Texte**, unabhängig von `send_default_pii`. Die Zusage auf `/legal` („Nutzerdaten werden nicht übertragen“) hält erst damit. **Betreiber: in Sentry prüfen, ob Ereignisse mit Rumpf liegen.** |
| SQL-Protokoll in `prod` ausdrücklich aus | `config/packages/doctrine.yaml` (`when@prod`, `dbal.logging: false`) | entspricht der Vorgabe, jetzt festgeschrieben und geprüft |
| Restaurantkarte als Partial | `partials/_restaurant_card.html.twig` | Suche rendert ihre Karten darüber |
| Ansagebereich | `base.html.twig` (`#meldung-live`) | auf jeder Seite, leer und stumm |
| Turbo-Cache-Meta | `base.html.twig` + `PrivatePageCacheSubscriber` | Suche/Detailseite für Angemeldete `no-store` |
| Kopfzeile, untere Leiste, Profil | `base.html.twig`, `_bottom_nav.html.twig`, `profile/index.html.twig` | angemeldet „Gespeichert“ statt „Über uns“ in der Leiste |
| Meldungs-Partial | `_flash_messages.html.twig` | neue unsichtbare Art `scroll_to` |
| Detailseite über Baustein | `RestaurantShowView`, `RestaurantController::show` | Verhalten unverändert |
| Messung | `UsageEventCatalogue`, `CollectPayloadNormalizer`, `before_send.ts` | zwei Ereignisse, Filterschlüssel `favorites`, Pfadkürzung |
| Sicherungsprüfung | `bin/sicherung-pruefen.sh` | vier Tabellen mehr erwartet |
| Zugriff | `security.yaml` | `^/[a-z]{2}/gespeichert` → `IS_AUTHENTICATED_REMEMBERED` |
| Bestehender Prüflauf angepasst | `tests/Functional/Sponsor/SponsorIntegrityTest.php` | setzt das je Anfrage neue CSRF-Token gleich (sonst rot, sobald eine Seite ein Herz trägt) |
| Entwicklungsdatenbank | lokal | auf den aktuellen Migrationsstand gebracht und Fixtures neu geladen (enthielt nur Fixtures) |

**Beobachtungen außerhalb des Auftrags (nicht behoben, für `/sdd-qa` bzw. den Betreiber):**
- **Kontrast des aktiven Punkts der unteren Leiste** `text-cyan-600` (#0092b8) auf Weiß, 12 px: **3,61 : 1** (axe,
  nötig 4,5 : 1) — auf jeder Seite, schon vor Feature 17 (`/de/restaurants`); B25.
- **Detailseite:** `text-green-600 text-xs` in den Merkmalskacheln und das Verifizierungs-Abzeichen (#ad46ff auf
  #faf5ff, 3,83 : 1), zusammen 10 Knoten laut axe; B06.
- **Suche:** `filterParams` behält bei mehreren gewählten Küchen nur die letzte (Sortier-/Blätterverweise und neu
  „Filter entfernen“); B05.
- **`lint:container` ist rot ohne Bezug zum Feature** (Alias `PublicKeyCredentialSourceRepositoryInterface` →
  `WebauthnCredentialRepository`), gegengeprüft am Stand ohne Änderungen. `doctrine:schema:validate` meldet ältere
  Abweichungen (Indexnamen, `cache_items`), die neuen Tabellen sind nicht darunter.
- **`vendor/bin/php-cs-fixer` ist nicht installiert** — `make fix` läuft ins Leere; Stil nicht maschinell geprüft.
- **CLAUDE.md, Tri-State-Wizard:** „expanded `ChoiceType` … `error_bubbling` default `true`“ stimmt für Symfony 8.0
  nicht mehr (`false`).
- **Die lokale Entwicklungsdatenbank lag im Container eines anderen Projekts** (`mikaplus-database-1`, Port 3306) und
  fiel mit einem Docker-Neustart weg; neu gestartet.

### 5 · Tests

| | Anzahl |
|---|---|
| Testmethoden neu geschrieben | 228 in 30 Dateien (mit Datenprovidern mehr Fälle) |
| davon rot gesehen, dann grün | alle Aufgaben mit Logik; je Datei mindestens eine Gegenprobe (Bericht der Subagenten bzw. oben im Lauf) |
| gelaufen in der letzten Verifikation | **2254, alle grün, 40 übersprungen** (Ausgangsstand 1947, 40 übersprungen) |

**Sofort grün, mit Gegenprobe belegt:** `SavedEdgeCasesTest` (Verhalten stammt aus Ebene 2–4; Gegenprobe EC-10 mit
außer Kraft gesetztem `FavoriteService::remove()` → rot), `DetailHeartVariantsTest` (Gegenprobe: Herz in der
Titelbild-Variante entfernt → rot), `SavedAppShellTest` und `SavedPrivacyStatementTest` (Gegenprobe gegen die Vorlagen
im alten Stand → rot), `SavedPrivacyLeakTest::testAk64ProduktionProtokolliertKeinSql` (Gegenprobe `logging: true` → rot).
Negativfälle, die vor dem Bau schon grün waren (Gast ohne Wirkung, fremde Nummer → 404 ohne Route), bleiben als Wächter.

**Logik ohne automatischen Test:** die drei Stimulus-Controller (Fokus und Rücksprung) — im Browser belegt, nicht in
PHPUnit prüfbar; die Prüfläufe sichern die Markierungen im HTML (`data-controller="scroll-to"`, `focus-after-stream`).

**Übergabe:** `/sdd-qa 17` **in einer neuen Session**. Status bleibt `building`.

**Nachtrag T28 (2026-11-14):** Referenzdokumente nachgezogen (`docs/data-model.md`, `docs/datenschutz.md`,
`docs/app-shell.md`, `docs/prd.md`, `CLAUDE.md`). Dabei aufgefallen und vermerkt, nicht behoben: Die Zahlen im
Überblick von `data-model.md` stimmten schon vor Feature 17 nicht (jetzt nachgezählt); die Tabellen aus Feature 04,
06, 08 fehlen im Beziehungsdiagramm; `app-shell.md` führt Lücke 1 (Abmelden im Profil) und Lücke 7 (BF-80) noch als
offen, der Sprachumschalter steht dort noch als `hidden md:block`. ⚠ **Die Sentry-Einstellung wirkt erst mit dem
Ausrollen** — bis dahin sendet die Produktion Anfragerümpfe weiter. Kopfzeile angemeldet mit „Gespeichert“
nachgemessen (de 768–1280 px, fr/pt 768 und 1024 px): kein Überlauf, BF-80 nicht zurück.

## Abschlussbericht · 2026-11-14 · Fehlerauftrag (BF-211 bis BF-217, BF-206, Rest von BF-203)

Eingang **Fehlerauftrag** (`qa-report.md`, Status `review` → `building`). Umfang laut `features/befunde.md`,
„zur Behebung freigegeben … → `/sdd-build 17`“. T29 gehört weiterhin in den Release-Commit (AK-70).

### 1 · Umgesetzt

Alle neun Befunde sind behoben, jeder mit Reproduktion bzw. Messung vorher und nachher; Einzelheiten je Befund stehen
unter dem Fehler in `qa-report.md`.

- **BF-211** — `SavedText::nameKey()` mit `MB_CASE_LOWER_SIMPLE`: Der Schlüssel hat für jeden Codepunkt dieselbe Länge
  wie der Name (geprüft über alle 1 112 064 Codepunkte; vorher allein U+0130 länger). 31 × „İ“ wird gespeichert,
  „Istanbul“ + 53 × „İ“ (61 Zeichen) → 422 mit Meldung. Migration unverändert.
- **BF-212** — `SavedController::NUMMER` = `\d{1,10}` an allen fünf Routen → 404 statt TypeError.
- **BF-213** — `SavedCards::clampPage()` vor jedem Rechnen in `removeEntry()` → Eintrag entfernt, 303 auf die letzte Seite.
- **BF-214** — Suche: Seiten jenseits `intdiv(PHP_INT_MAX, 6)` → 404 vor der Abfrage, darunter wie bisher BF-60.
- **BF-215** — aktiver Punkt der unteren Leiste `text-cyan-700`; axe bei 375 px auf 18 Seiten: 0 Verstöße an der Leiste.
- **BF-216** — Detailseite in den beiden genannten Dateien: `green-800`, `amber-800`, `purple-700`, `gray-600`; axe über
  alle elf Detailseiten 375/1280 px **220 → 21 Knoten** (Rest: Wortmarke 16/OF-02, Gehweite in `_nearby_stops`).
- **BF-206 / BF-217** — globale `:focus-visible`-Regel in `@layer base`. Rückverweis im Kopfband **1,13 → 6,03 : 1**
  (1280 px), **1,37 → 7,36 : 1** (375 px).
- **Rest von BF-203** — zwölf Etiketten in neun Vorlagen auf `bg-black/20`: **2,95–4,21 → 5,89–10,71 : 1** (Pixel,
  de/fr/pt × 1280/375/320 px); `_hero_badges.html.twig` gemessen (3,19–4,47 : 1) und mitgezogen (6,48–8,87 : 1);
  `docs/design-system.md` „Glas“ nachgezogen.

**Fokus-Umfrage (Auftrag: „sieh die Vorlagen durch …“).** Für jedes sichtbare fokussierbare Element auf 41 Seiten (23 Gast,
10 Konto, 8 Verwaltung) bei 1280 und 375 px wurde der Bildausschnitt ohne und mit Tastaturfokus verglichen (Pixel mit
≥ 3 : 1 Änderung, Chromium), einmal vor und einmal nach der Reparatur: **3 708 Elemente, 0 Rückschritte, 56 neu sichtbar**
(alle `focus:outline-white` auf Bändern), 19 unverändert schwach (Bestand, siehe 2). Was die Dev-Daten nicht zeigen, wurde
statisch durchgesehen (alle 19 `outline-white`, alle Fokusfarben außer cyan-700) und per eingesetztem Markup gemessen. Dabei
**zwei Folgestellen gefunden und angepasst**, die erst durch die Reparatur unsichtbar geworden wären:
- **Titelfoto der Detailseite** (`restaurant/show.html.twig`, `focus:outline-white`): Der Rahmen liegt außerhalb des Fotos
  auf Seitenkopf bzw. `bg-gray-50` — Weiß 1,22 : 1. Jetzt `focus:outline-cyan-700` (5,05 : 1, wie vorher de facto).
- **Sponsorenreihe auf `/organisationen/unternehmen`** (`sponsor/_logo.html.twig`, `focus:outline-cyan-400` für den dunklen
  Footer): auf `bg-gray-50` 1,73 : 1. Neuer Parameter `hellerGrund` (über `_logo_row.html.twig`, gesetzt in
  `organisation/type.html.twig`) → cyan-700 (5,05 : 1); im Footer bleibt cyan-400 (9,81 : 1).

### 2 · Offene Akzeptanzkriterien

Keine aus dem Auftrag. Offen bzw. außerhalb des Auftrags, **nicht** behoben:
- **OF-05 (neu in `spec.md`)** — Herz auf der Detailseite ohne Titelfoto: Fokusrahmen cyan-700 auf dem Verlauf,
  **1,06–1,33 : 1**, vor und nach der Reparatur gleich (Bestand von 17, kein Rückschritt). Fassung mit Titelfoto nicht gemessen.
- **Gehweite der Haltestellen** (`partials/_nearby_stops.html.twig:41`, `text-xs text-gray-400` auf `gray-50`) **2,48 : 1**,
  10 axe-Knoten auf fünf Detailseiten — andere Datei als in BF-216 benannt (B10).
- **Passkey-Knopf im Profil** weiß auf `cyan-600`, **3,61 : 1** (axe, `/de/profile`, B03/B04).
- **Küchen-Auswahl der Suche (Tom Select)**: Das Eingabefeld zeigt beim Tastaturfokus keinen Rahmen mit ≥ 3 : 1 (1,52 : 1),
  vor und nach gleich (B05). Ebenso die Tom-Select-Felder der Verwaltung.
- **Hover** der übrigen Punkte der unteren Leiste bleibt `cyan-600` (nicht im Befund).

### 3 · Getroffene Annahmen

- **BF-211 über die Schlüsselbildung, nicht die Spaltenbreite** (der Betreiber überließ die Wahl dem Bau). Die einfache
  Abbildung hält die Länge strukturell gleich; eine breitere Spalte hätte auf den heutigen Höchstfaktor (2) gesetzt.
  Folge: „İstanbul“ und „Istanbul“ sind eine Dublette (vorher verschieden). Die Migration bleibt unverändert.
- **BF-212: `\d{1,10}` statt `\d{1,9}`** — deckt jede `INT`-Nummer (bis 2 147 483 647) und entspricht dem Muster, das das
  Häkchen-Formular schon prüft (`RestaurantSavedController::saveLists`).
- **BF-213: geklemmt statt 404** — so verhielt sich eine Seite knapp hinter dem Ende schon vorher; der Eintrag wird entfernt.
- **BF-214: 404 ab `intdiv(PHP_INT_MAX, LIMIT)`** — eine Grenze aus der Arithmetik, keine gewählte Seitenzahl.
- **BF-216 vollständig in den beiden benannten Dateien**, nicht nur die beiden Farben aus dem Befund: `amber-600` steht in
  derselben Zeile wie ein `green-600`, „Keine Öffnungszeiten“ in `_opening_hours.html.twig`. Die QA hatte nur Lokal 12 gemessen.
- **Farben nach dem Vorschlag der QA** (`green-800`, `purple-700`), analog `amber-800`; „Keine Öffnungszeiten“ `gray-600`
  wie „Nicht verfügbar“ daneben.
- **BF-203: Sprach-Abzeichen mitgezogen**, weil Weiß dort unter 4,5 : 1 maß. Ihr Inhalt ist ein Flaggen-Emoji — wo das
  System keine Flaggen kennt (Windows), steht dort weißer Text.
- **Reproduktionen der QA aufgelöst wie in 16** (`// Scharf seit der Behebung … — Rückfallschutz.` statt `self::offen()`);
  die Hilfsfunktion `offen()` bleibt für künftige Reproduktionen stehen.
- **Messungen gegen die Entwicklungsdatenbank** über den laufenden Server (nur lesend; Anmeldungen als `user@` und `admin@`).
  Die Liste „Mittag in Kirchberg“ stammt aus dem Bau. Der BF-211-Fehlerzustand für die Vorlage wurde mit einem
  abgewiesenen Namen (422) aufgenommen, nichts gespeichert.

### 4 · Systemweite Änderungen

| Änderung | Datei | Wirkung über das Feature hinaus |
|---|---|---|
| Globaler Fokus-Fallback in `@layer base` | `assets/styles/app.css` | ⚠ **Projektweit:** Jede `focus:outline-<farbe>` wirkt jetzt (74 Vorlagen). Weiß auf allen Bändern sichtbar; Fußzeile cyan-400 (9,8 statt 3,3 : 1); `focus:outline-offset-4` und `outline-offset-[-2px]` greifen jetzt |
| Glas-Etikett dunkel | `home`, `open` (2), `press`, `comparison/index`, `partner` (2), `organisation/index`, `organisation/_integrity`, `sponsor/index`, `app_waitlist/index` | sichtbar auf neun Seiten |
| Sprach-Abzeichen dunkel | `partials/_hero_badges.html.twig` | Kopf jeder Detailseite |
| Titelfoto-Fokus cyan-700 | `restaurant/show.html.twig` | Folge von BF-206 |
| Fokusfarbe der Sponsorkachel nach Grund | `sponsor/_logo.html.twig`, `sponsor/_logo_row.html.twig`, `organisation/type.html.twig` | Feature 15; neuer Parameter `hellerGrund` |
| Kontrast Detailseite | `restaurant/show.html.twig`, `partials/_opening_hours.html.twig` | B06 |
| Aktiver Leistenpunkt | `partials/_bottom_nav.html.twig` | B25, jede Seite bei < 768 px |
| Suche: Obergrenze der Seitenzahl | `RestaurantController::index()` | B05 |
| Prüfläufe anderer Features angepasst | `PwaTest` (B25: verlangt `text-cyan-700`), `Qa16NachpruefungTest` (BF-206 scharf), `CompanyPageTest` (15: neuer Fall) | — |
| Referenz nachgezogen | `CLAUDE.md` (Konvention „Prüfung, wo der Wert hereinkommt“ +2 Punkte, Fokus-Fallback, Feature-17-Abschnitt), `docs/design-system.md` (Glas, Fokus), `docs/data-model.md` (`name_key`) | — |

Keine neue Abhängigkeit, keine Migration, keine Konfiguration, keine Übersetzung.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 17 Fälle in 6 Dateien (`SavedTextTest` 2, `SavedEdgeCasesTest` 2, `SavedPagesTest` 1 Datenfall, `KontrastKlassenTest` 11 neu, `CompanyPageTest` 1) |
| davon rot gesehen, dann grün | alle 17 (BF-211 120 statt 60 bzw. U+0130; BF-212/213/214 TypeError → 500; BF-203 9 × helles Glas; BF-216 keine `green-800`; Titelfoto und Sponsorenreihe per kurz zurückgenommener Vorlage) |
| Reproduktionen der QA scharf geschaltet | 5 (`Qa17PruefungTest` BF-211 bis BF-214, `Qa16NachpruefungTest` BF-206) — vorher übersprungen, scharf rot, jetzt immer grün |
| bestehender Test verschärft | `PwaTest::testAk11Ak12…` (rot vor der Änderung) |
| gelaufen in der letzten Verifikation | **2 288, alle grün, 39 übersprungen** (Unit 605/11, Integration 369/1, Functional 1 314/27); vorher 2 271 / 44 |

Verifikation: `lint:twig` 151 Dateien OK; `npm run typecheck` und `npm run lint` ohne Befund; `doctrine:schema:validate` rot
**wie vorher** (ältere Indexnamen, `cache_items` — keine der vier Tabellen von 17 im Diff); `lint:container` rot **wie
vorher** (Webauthn-Alias, ohne Bezug); `make fix-check` existiert nicht, `php-cs-fixer` ist nicht installiert — Stil nicht
maschinell geprüft.

**Ohne PHPUnit-Nachweis, im Browser gemessen:** alle Kontraste und Fokusrahmen — die Prüfläufe halten nur die Klassen bzw.
die Kaskadenebene fest. Skripte und Rohdaten außerhalb des Projekts unter `/tmp/build17/` (`bin/fokus-umfrage.js`,
`bin/glas.js`, `bin/axe-kontrast.js`, `bin/injektion.js`, `out/`).

**Übergabe:** `/sdd-qa 17` **in einer neuen Session** — mit Regressionsprüfung der Bänder anderer Seiten (BF-206 wirkt
projektweit). Status bleibt `building`.
