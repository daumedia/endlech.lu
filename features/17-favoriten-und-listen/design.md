# 17 · Favoriten und Listen — Systemdesign

Status: `architected` · Stand: 2026-11-13 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

⚠ Das Stack-Profil nennt Webpack Encore. Das Projekt baut seit 2026-10-22 mit AssetMapper,
TypeScript über SWC (CLAUDE.md, „Frontend“) — es gilt das Projekt.

## Überblick

Vier neue Tabellen halten fest, was ein Konto gemerkt hat: **Favoriten** (Konto ↔ Lokal),
**Listen** (benannt, je Konto), **Listeneinträge** (Liste ↔ Lokal) und **Notizen** (Konto ↔
Lokal, ein Text). Alle vier hängen mit einem Fremdschlüssel an Konto und Lokal, der in der
Datenbank kaskadiert: Verschwindet das Konto oder das Lokal, verschwindet alles daran, ohne
dass Kontolöschung oder Restaurantverwaltung etwas dafür tun müssen.

Bedient wird über gewöhnliche Formulare, die **ohne JavaScript** funktionieren (Absenden →
Weiterleitung zurück an dieselbe Karte). Mit JavaScript schickt Turbo dieselben Formulare ab
und bekommt für das Herz eine **Turbo-Stream-Antwort**, die nur das Herz austauscht und die
Meldung in einen Ansagebereich schreibt — die Seite bleibt stehen. Ein abgemeldeter Besucher
hinterlässt beim Tipp aufs Herz einen **Merkwunsch in seiner Sitzung**; ein Zuhörer auf das
Anmeldeereignis löst ihn nach der Anmeldung ein.

Jeder Zugriff auf eine Liste wird **über das Konto aus der Sitzung gesucht**, nie über die
Nummer allein — eine fremde Nummer findet schlicht nichts und endet in 404. Ein Deckel am
Konto (120 Änderungen je Stunde) und eine Schreibsperre je Konto schützen vor Skripten und
gleichzeitigen Absendungen.

## Seiten und Routen

Alle Wege unter dem Sprachpräfix `/{_locale}`, im gewöhnlichen `controllers`-Block von
`routes.yaml` (kein eigener Block). Routennamen englisch wie im Bestand, Pfade deutsch wie
`/einfuehrung`, `/sponsoren`.

### Neu — Bereich „Gespeichert“

| Route | Methode · Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_saved_index` | GET `/gespeichert` | Übersicht: Favoriten mit Zahl, eigene Listen mit Zahl, Feld „Neue Liste“ | angemeldet |
| `app_saved_favorites` | GET `/gespeichert/favoriten` | Gemerkte Lokale als Karten, 12 je Seite | angemeldet |
| `app_saved_list_create` | POST `/gespeichert/listen` | Liste anlegen; Fehler → Übersicht mit 422 | angemeldet |
| `app_saved_list_show` | GET `/gespeichert/listen/{id}` | Eine Liste als Karten, 12 je Seite; darin das Umbenennen-Formular | Inhaber |
| `app_saved_list_rename` | POST `/gespeichert/listen/{id}/umbenennen` | Umbenennen; Fehler → Listenseite mit 422 | Inhaber |
| `app_saved_list_delete_confirm` | GET `/gespeichert/listen/{id}/loeschen` | Rückfrage mit Name und Zahl | Inhaber |
| `app_saved_list_delete` | POST `/gespeichert/listen/{id}/loeschen` | Löschen → Übersicht | Inhaber |
| `app_saved_list_entry_remove` | POST `/gespeichert/listen/{id}/lokale/{restaurant}/entfernen` | Lokal aus der Liste nehmen → dieselbe Listenseite | Inhaber |

### Neu — Handlungen an einem Lokal

| Route | Methode · Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_favorite_add` | POST `/restaurants/{id}/merken` | Herz setzen. **Gast:** Merkwunsch in die Sitzung, weiter zur Anmeldung | jeder |
| `app_favorite_remove` | POST `/restaurants/{id}/nicht-mehr-merken` | Herz entfernen | angemeldet |
| `app_restaurant_lists_save` | POST `/restaurants/{id}/listen` | Häkchen „In Listen“ übernehmen, optional neue Liste mit dem Lokal anlegen | angemeldet |
| `app_restaurant_note_save` | POST `/restaurants/{id}/notiz` | Notiz speichern oder (leer) löschen | angemeldet |

### Geänderte Seiten

| Route | Änderung |
|---|---|
| `app_restaurant_index` | Herz auf jeder Karte; Filter `favorites=1` (nur angemeldet wirksam); Leertext AK-40 |
| `app_restaurant_show` | Herz beim Namen (beide Kopfvarianten); für Angemeldete die Abschnitte „In Listen“ und „Deine Notiz“ |
| `app_login` | Hinweis „Melde dich an, um dir <Name> zu merken.“, solange ein Merkwunsch besteht |
| `app_profile` | Verweis auf „Gespeichert“ |
| App-Hülle | Kopfzeile: Verweis „Gespeichert“ im Kontobereich; untere Leiste: „Gespeichert“ statt „Über uns“ für Angemeldete; Ansagebereich für Meldungen |

### Wohin nach dem Absenden

| Handlung | Ohne JavaScript (303-Weiterleitung) | Mit JavaScript (Turbo) |
|---|---|---|
| Herz setzen/entfernen | Herkunftsseite aus dem Feld `ziel` + Anker `#restaurant-{id}` | Turbo-Stream: Herz austauschen, Meldung ansagen |
| Herz entfernen auf „Favoriten“ | „Favoriten“, gleiche Seite, Anker auf die nächste Karte | Turbo-Stream: Kartenraster der Seite neu, Fokus auf nächste Karte |
| „In Listen“ / Notiz | Detailseite + Anker `#in-listen` bzw. `#notiz` | wie ohne JS (Turbo folgt der Weiterleitung, springt zum Anker) |
| Liste anlegen | Übersicht | wie ohne JS |
| Umbenennen | Listenseite | wie ohne JS |
| Löschen | Übersicht | wie ohne JS |
| Aus Liste entfernen | Listenseite, gleiche Seite | Turbo-Stream: Kartenraster der Seite neu, Fokus auf nächste Karte |
| Prüfung scheitert | Seite des Formulars mit 422, Meldung am Feld, Fokus im Feld | gleich (Turbo rendert 422 an Ort und Stelle) |

**Das Feld `ziel`** trägt die relative Adresse der Seite, auf der das Herz steht. Es wird nur
übernommen, wenn der Router es als eine von vier Routen erkennt (`app_restaurant_index`,
`app_restaurant_show`, `app_saved_favorites`, `app_saved_list_show`) in derselben Sprache;
sonst gilt die Detailseite des Lokals. Damit ist das Feld **kein offener Redirect** (Muster
`BoardLegacyRedirectController`: keine fremde Adresse aus einer Eingabe).

## Abläufe

### Herz mit JavaScript (AK-01, AK-02, AK-48)

1. Turbo schickt das Formular ab und bittet um einen Turbo-Stream.
2. Der Server merkt (oder entfernt) und antwortet mit zwei Stream-Anweisungen: das Herz-Formular
   **per `replace` mit `method="morph"`** ersetzen und den Ansagebereich mit der Meldung füllen;
   beim Merken zusätzlich der unsichtbare Messauslöser „Herz gesetzt“.
3. Weil Morphing das vorhandene Knopf-Element behält (gleiche Kennung `favorit-knopf-{id}`),
   **bleibt der Fokus auf dem Herz**; der neue zugängliche Name wird dort gelesen.
   *(Turbo 8.0.23 im Projekt: `replace` wertet `method="morph"` aus, nachgesehen in
   `assets/vendor/@hotwired/turbo/turbo.index.js`, 2026-11-13.)*
4. Die Seite wird nicht neu aufgebaut, scrollt also nicht.

### Herz ohne JavaScript (AK-06)

Gewöhnlicher POST, Merken, 303 auf `ziel#restaurant-{id}` mit Meldung. Jede Karte trägt
`id="restaurant-{id}"` und einen Abstand für die feststehende Kopfzeile (`scroll-mt-24`).

### Merken ohne Anmeldung (AK-08 bis AK-10, EC-01 bis EC-04)

1. Ein Gast schickt das Herz ab (Formular wie für Angemeldete, Token geprüft).
2. Der Server legt den **Merkwunsch** in die Sitzung (Lokal-Nummer und geprüftes `ziel`),
   speichert `ziel#restaurant-{id}` als Zielseite der Firewall `main` (dasselbe Mittel wie
   B11, `saveTargetPath`) und leitet zur Anmeldung. Gespeichert ist noch nichts.
3. Die Anmeldeseite zeigt den Hinweis mit dem Namen des Lokals, solange der Wunsch besteht.
4. **Zuhörer `PendingFavoriteSubscriber` auf `LoginSuccessEvent`** (Firewall `main`): Besteht
   ein Wunsch und gibt es das Lokal noch, wird gemerkt — mit Deckel wie jede Änderung —,
   „Gemerkt: <Name>“ und der Messauslöser gehen als Meldung mit, der Wunsch wird gelöscht.
   Gibt es das Lokal nicht mehr, wird der Wunsch still verworfen (EC-03).
5. Symfony leitet auf die gespeicherte Zielseite; der Zuhörer der Einführung (Feature 12)
   greift nur bei der Startseite und lässt sie stehen (EC-04; dort bleibt die Einführung fällig).

Das Ereignis feuert für Passwort, Passkey und „angemeldet bleiben“ gleichermaßen
(`AuthenticatorManager`, symfony/security-http 8.0). Ein fehlgeschlagener Versuch lässt den
Wunsch in der Sitzung (AK-10); endet die Sitzung, ist er weg (EC-01).

## Komponentenstruktur

Wiederverwendet und nur benannt: `partials/_form_field.html.twig` (Feld mit Fehlermeldung und
Fokus), `partials/_flash_messages.html.twig`, `partials/_verified_badge.html.twig`,
`partials/_cuisine_badges.html.twig`, der Stimulus-Controller `usage-event`.

```
App-Hülle (base.html.twig)                                            ÄNDERUNG
├── Kopfzeile, Kontobereich      Verweis „Gespeichert“ neben Profilbild und Name (angemeldet)
├── Meldungsbereich              zusätzlich ein dauerhafter, leerer Ansagebereich
│                                (role="status", aria-live="polite") für Stream-Meldungen
└── Untere Leiste                angemeldet: Start · Restaurants · Gespeichert · Profil

Restaurantkarte (partials/_restaurant_card.html.twig)                 NEU, aus restaurant/index herausgelöst
├── bisheriger Karteninhalt      unverändert (Bild, Status, Name, Merkmale, Hinweise, Details-Link)
├── Herz                         partials/_favorite_button: Formular mit Knopf, zwei Zustände
├── Notiz                        nur auf „Gespeichert“-Seiten: Text der eigenen Notiz
└── „Aus Liste entfernen“        nur auf Listenseiten

Restaurantsuche (restaurant/index)                                    ÄNDERUNG
├── Filterbereich                Häkchen „Nur meine Favoriten“ (nur angemeldet), Filterchip
└── Kartenraster                 Restaurantkarte; Leertext AK-40 statt „keine Treffer“, wenn
                                 der Filter gesetzt ist und das Konto nichts gemerkt hat

Detailseite (restaurant/show)                                         ÄNDERUNG
├── Kopf                         Herz neben dem Namen (Variante mit und ohne Titelbild)
└── Abschnitt „Für dich“         nur angemeldet, nach den Barrierefreiheitshinweisen
    ├── In Listen (h2, #in-listen)   Häkchen je eigener Liste, Feld „Neue Liste“, „Speichern“
    └── Deine Notiz (h2, #notiz)     Textfeld, Hinweis „Nur für dich sichtbar.“, „Notiz speichern“

Gespeichert (saved/index)
├── Überschrift (h1)             „Gespeichert“
├── Favoriten                    Verweis auf „Favoriten“ mit Zahl, oder Leertext AK-13
├── Meine Listen (h2)            je Liste Name + Zahl, zuletzt angelegt zuerst, oder Leertext
└── Neue Liste                   Formular mit einem Feld, Fehler am Feld

Favoriten (saved/favorites)  ·  Liste (saved/list)
├── Überschrift (h1)             „Favoriten“ bzw. Listenname
├── Kopfaktionen                 nur Liste: Umbenennen-Formular, Verweis „Liste löschen“
├── Kartenraster                 Restaurantkarten, 12 je Seite; Kennung für Stream-Austausch
│                                und Fokusziel nach dem Entfernen (Stimulus `focus-after-stream`)
└── Blättern                     wie in der Suche

Rückfrage (saved/delete_confirm)
├── Frage (h1)                   „Liste „<Liste>“ mit N Lokalen löschen?“ + Erläuterung
└── Zwei Knöpfe                  „Löschen“ (POST-Formular), „Abbrechen“ (Verweis auf die Liste)

Anmeldeseite (security/login)                                         ÄNDERUNG
└── Hinweis über dem Formular    nur bei Merkwunsch, mit Namen des Lokals
```

**Zustände je Bildschirm**

| Bildschirm | leer | ladend | Fehler | gefüllt |
|---|---|---|---|---|
| Herz | — | Turbo setzt `aria-busy` am Formular, Knopf bleibt bedienbar; ohne JS gewöhnlicher Seitenwechsel | Deckel / Token: Meldung im Ansagebereich bzw. als Meldung nach der Weiterleitung, Herz unverändert | gefüllt / leer |
| Gespeichert | AK-13 (beide Leertexte) | Seitenwechsel | Formularfehler am Feld (AK-17 bis AK-20) | AK-11 |
| Favoriten | Leertext AK-13 mit Verweis auf die Suche | Seitenwechsel | Deckel/Token als Meldung | AK-12 |
| Listenseite | Leertext AK-27 | Seitenwechsel | Umbenennen-Fehler am Feld; fremde/fehlende Liste → 404 | AK-25 |
| Rückfrage | — | — | fremde/fehlende Liste → 404 | AK-29 |
| „In Listen“ | nur Feld „Neue Liste“ (AK-24) | Seitenwechsel | Fehler am Feld, keine Häkchenänderung (AK-23) | AK-21 |
| „Deine Notiz“ | leeres Feld | Seitenwechsel | Fehler am Feld, Eingabe bleibt (AK-35) | Text im Feld (AK-33) |
| Filter | AK-40 | Seitenwechsel | — | AK-38 |

## Datenmodell

Vier **neue** Tabellen. **Keine Änderung an bestehenden Tabellen**, auch nicht an
`restaurant` oder `user`: Die Beziehungen sind einseitig (von den neuen Tabellen aus), die
Kaskade liegt in der Datenbank. Ganzzahlige Primärschlüssel wie im Bestand.

### Tabelle `favorite` — Entity `Favorite`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | int, auto | ja | Primärschlüssel |
| `user_id` | int → `user.id` | ja | Konto; **ON DELETE CASCADE** |
| `restaurant_id` | int → `restaurant.id` | ja | Lokal; **ON DELETE CASCADE** |
| `created_at` | datetime_immutable | ja | gemerkt am — Sortierung und Export |

Indizes: **eindeutig** `(user_id, restaurant_id)` — ein Lokal je Konto höchstens einmal
(AK-05); `(user_id, created_at)` für „Favoriten“, zuletzt gemerkt zuerst.

### Tabelle `saved_list` — Entity `SavedList`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | int, auto | ja | Primärschlüssel, erscheint in der Adresse |
| `user_id` | int → `user.id` | ja | Inhaber; **ON DELETE CASCADE** |
| `name` | varchar(60) | ja | angezeigter Name, getrimmt, Unicode-normalisiert (NFC) |
| `name_key` | varchar(60), Kollation `utf8mb4_bin` | ja | Vergleichsschlüssel: `name` in Kleinbuchstaben |
| `created_at` | datetime_immutable | ja | angelegt am — Sortierung und Export |

Indizes: **eindeutig** `(user_id, name_key)` — kein Konto hat zwei Listen gleichen Namens
(AK-19); `(user_id, created_at)` für die Übersicht.

`name_key` statt der Kollation der Spalte `name`: Die Standardkollation von MySQL 8
(`utf8mb4_0900_ai_ci`) ist auch **akzentblind** — „Café“ und „Cafe“ wären eine Dublette, was
die Spec nicht verlangt. Der eigene Schlüssel macht genau „Groß/klein egal“ und hält Prüfung im
Dienst und Index in der Datenbank deckungsgleich.

### Tabelle `saved_list_entry` — Entity `SavedListEntry`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | int, auto | ja | Primärschlüssel |
| `list_id` | int → `saved_list.id` | ja | Liste; **ON DELETE CASCADE** |
| `restaurant_id` | int → `restaurant.id` | ja | Lokal; **ON DELETE CASCADE** |
| `added_at` | datetime_immutable | ja | hinzugefügt am — Sortierung und Export |

Indizes: **eindeutig** `(list_id, restaurant_id)` (AK-31); `(list_id, added_at)` für die
Listenseite. Kein eigener Kontobezug: Der Inhaber ergibt sich über die Liste, die Kaskade
läuft Konto → Liste → Eintrag.

### Tabelle `restaurant_note` — Entity `RestaurantNote`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | int, auto | ja | Primärschlüssel |
| `user_id` | int → `user.id` | ja | Verfasser; **ON DELETE CASCADE** |
| `restaurant_id` | int → `restaurant.id` | ja | Lokal; **ON DELETE CASCADE** |
| `text` | varchar(500) | ja | Notiz, getrimmt, Zeilenumbrüche als `\n` |
| `updated_at` | datetime_immutable | ja | zuletzt geändert — Export |

Indizes: **eindeutig** `(user_id, restaurant_id)` — eine Notiz je Konto und Lokal.
**Eine leere Notiz ist keine Zeile:** Leeren löscht die Zeile (AK-36, EC-08).

`\r\n` → `\n` **vor** dem Zählen: Ein Formular schickt Zeilenumbrüche als zwei Zeichen, der
Nutzer zählt einen. Ohne die Umwandlung schlüge eine Notiz mit 490 Zeichen und sechs Absätzen
an der Grenze fehl.

### Löschregeln

| Wenn gelöscht wird … | … dann verschwindet | durch |
|---|---|---|
| ein Konto (Feature 01) | seine Favoriten, Listen samt Einträgen, Notizen | DB-Kaskade über `user_id` / `list_id` — `AccountDeleter` braucht keine Zeile (AK-61) |
| ein Lokal (B20) | alle Favoriten, Listeneinträge und Notizen dazu, bei allen Konten | DB-Kaskade über `restaurant_id` (AK-62) |
| eine Liste | ihre Einträge | DB-Kaskade über `list_id`; Herz und Notizen bleiben (AK-30) |

Doctrine löscht ein Lokal per `remove()` als SQL-`DELETE`; die Kaskade greift in der
Datenbank, auch ohne Rückbeziehung im Entity. Dasselbe Muster wie `board_vote`.

### Migration

Eine Migration, nur `CREATE TABLE` mit Fremdschlüsseln — **rein ergänzend**. Der alte Code
liest die Tabellen nicht. Der neue liest `favorite` aber auf jeder Such- und Detailseite eines
angemeldeten Kontos: Läuft die Migration erst als Post-Deployment-Befehl nach dem
Containertausch, werfen diese Seiten bis dahin einen Fehler. Für `sdd-deploy` vermerkt:
Migration vor dem Code (Weg „einmaliger Pre-Deployment-Befehl“ aus CLAUDE.md, Deployment)
oder das kurze Fenster bewusst in Kauf nehmen.

⚠ **`bin/sicherung-pruefen.sh` führt die erwarteten Tabellen von Hand** — die vier neuen
Namen kommen in `ERWARTETE_TABELLEN`, sonst gilt eine Sicherung ohne sie als vollständig.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | nichts aus diesen Tabellen; leeres Herz | nur den Merkwunsch in der **eigenen** Sitzung | `access_control` (`^/[a-z]{2}/gespeichert` → `IS_AUTHENTICATED_REMEMBERED`); `#[IsGranted]` an den übrigen Lokal-Handlungen; `app_favorite_add` prüft selbst |
| Angemeldetes Konto (auch unbestätigt, AK-69) | nur eigene Favoriten, Listen, Einträge, Notizen | nur eigene | **Jede Abfrage ist auf das Konto aus der Sitzung beschränkt**: Liste nur über „Nummer **und** Konto“; Häkchen nur aus der Menge eigener Listen |
| Ein anderes Konto | nichts davon | nichts davon | dieselbe Beschränkung → fremde Nummer findet nichts → 404 (AK-53, AK-54) |
| `ROLE_ADMIN` | wie jedes Konto: nur das eigene | nur das eigene | keine Verwaltungsseite liest diese Tabellen (AK-59) |

Drei Festlegungen, weil es keine zweite Schicht gibt (keine Zeilensicherheit in der Datenbank):

- **Besitz über die Abfrage, kein Voter.** Ein Repository-Aufruf „Liste mit Nummer X des
  Kontos Y“ kann nicht vergessen werden, ein nachgelagerter Besitzvergleich schon. Ergebnis
  `null` → 404, **identisch** für „gibt es nicht“ und „gehört jemand anderem“.
- **Besitzprüfung vor Token-Prüfung** (Muster `PasskeyController`, BF-199): Eine fremde Nummer
  bekommt 404, egal welches Token mitkommt; eine Weiterleitung mit Listennamen in der Adresse
  gibt es nie.
- **Das Konto kommt aus der Sitzung.** Kein Formular nimmt eine Kontokennung entgegen (Muster
  Board AK-58).
- **Fremde Listennummern im Häkchen-Formular** (manipuliert): Der Controller prüft die
  übermittelten Nummern **vor** dem Verarbeiten gegen die eigenen; eine einzige fremde oder
  unbekannte → 404, nichts geändert (AK-54). Ein bloßer „ungültige Auswahl“-Formularfehler
  wäre 422 und verriete über die Antwort nichts — die Spec verlangt aber 404.

**Token:** Formulare mit Formulartyp (Liste anlegen, umbenennen, „In Listen“, Notiz)
benutzen das zustandslose Projekttoken `submit`; handgeschriebene Formulare (Herz,
Aus-Liste-entfernen, Löschen) Sitzungstoken. Scheitert das Token eines Formulartyps, antwortet
der Controller wie bei den handgeschriebenen mit `flash.invalid_csrf` und Weiterleitung statt
mit dem Formularfehler (AK-56: ein Wortlaut).

## Privater Seiteninhalt und Zwischenspeicher (AK-52, AK-63)

Such- und Detailseite zeigen einem angemeldeten Konto private Angaben (gefüllte Herzen, Listen,
Notiz), die Seiten unter `/gespeichert` ohnehin. Nach dem Abmelden darf „Zurück“ sie nicht
wieder hervorholen — weder aus Turbos Seitenspeicher noch aus dem Verlauf des Browsers.

- **Eine Klasse `PrivatePages`** führt die Routen: `app_saved_*`, `app_restaurant_index`,
  `app_restaurant_show`.
- **Kopfzeile `Cache-Control: no-store, private`** für diese Routen, wenn ein Konto angemeldet
  ist (Zuhörer auf `kernel.response`). `no-store` nimmt die Seite aus dem Verlaufsspeicher und
  dem Back-Forward-Cache.
- **`<meta name="turbo-cache-control" content="no-cache">`** unter derselben Bedingung (eine
  Twig-Funktion fragt dieselbe Klasse). Turbo legt dann keinen Schnappschuss an; „Zurück“ lädt
  vom Server, und der sieht eine abgemeldete Sitzung.
- Für Gäste bleibt alles wie bisher (die Seiten zeigen dann nichts Privates).

Die Nachprüfung gehört in einen echten Browser (Safari und Chrome), ein Prüflauf ohne Browser
sieht weder Turbo-Speicher noch Back-Forward-Cache.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| alle schreibenden Wege dieses Features (Herz setzen/entfernen, Häkchen, Notiz, Liste anlegen/umbenennen/löschen, aus Liste entfernen, eingelöster Merkwunsch) | **120 je Stunde je Konto**, gleitendes Fenster, ein gemeinsames Kontingent | „Zu viele Änderungen. Bitte versuche es in <N> Minuten erneut.“; nichts gespeichert; Stream-Antwort bzw. Weiterleitung mit Meldung | Limiter `saved_change` in `config/packages/framework.yaml`, `when@test` 10000; Schlüssel `konto-{id}` (nicht E-Mail: bleibt beim Adresswechsel gleich) |
| Gast-Herz (Merkwunsch) | kein Limiter | — | Es wird nichts gespeichert außer einer Angabe in der **eigenen** Sitzung; kein Mailversand, kein Geheimnis, kein Bestand |
| Lesewege (`/gespeichert/…`, Filter) | kein Limiter | — | Kein Weg lädt den Bestand: 12 Karten je Seite, höchstens 25 Listen, Filter ist eine zusätzliche Bedingung in der bestehenden Abfrage |
| Listen je Konto | 25 | AK-20 | Prüfung im Dienst unter der Schreibsperre |

**Verbrauch nach `ActionLimiter`** (CLAUDE.md): `isAllowed()` vor der Handlung,
`consume()` erst, **wenn tatsächlich etwas gespeichert wurde** (BF-11, AK-58). Kein `return`
zwischen beiden, der etwas speichert (BF-118). Ein Absenden zählt einmal, auch wenn es Häkchen
und neue Liste zugleich ändert.

**Schreibsperre je Konto** (`LockFactory`, Schlüssel `gespeichert-{id}`, Muster
`AvatarUploadService`): Jeder schreibende Weg prüft und schreibt unter dieser Sperre. Damit
entstehen bei Doppelklick, zwei Tabs oder zwei Geräten weder doppelte Einträge (AK-05, AK-31)
noch eine 26. Liste oder zwei gleichnamige (EC-05). Die eindeutigen Indizes bleiben die letzte
Instanz. Speicher `flock` — wirkt, solange die Anwendung in **einem** Container läuft (heute
der Fall); bei mehreren bräuchte es einen gemeinsamen Sperrspeicher.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| Umami (über `/api/send`, Feature 11) | Nutzungsmessung | Ereignisse `herz_gesetzt`, `liste_angelegt` **ohne Daten**; im bestehenden `filter_angewandt` zusätzlich der Schlüssel `favorites`; Seitenaufrufe | Lokal, Listenname, Listennummer, Notiz werden nie gesendet. **Pfade `/{xx}/gespeichert/listen/{id}…` werden auf `/{xx}/gespeichert/listen` gekürzt** — in `CollectPayloadNormalizer` **und** in `assets/usage/before_send.ts` (Projektregel: beide Stellen) |
| Sentry (nur prod) | Fehlerberichte | Ausnahme, Stacktrace ohne Argumente, Anfrageadresse | **Anfragerumpf: neu `max_request_body_size: never`** — siehe *Systemweite Änderungen* |
| Brevo | — | nichts | — |
| Protokolle (Monolog) | — | nichts aus diesem Feature | Kein Protokollaufruf enthält Listenname oder Notiz |

## Systemweite Änderungen

Was dieses Feature außerhalb seines eigenen Bereichs ändert — jede Zeile hat Wirkung auf
Bestehendes.

| Änderung | Wo | Wirkung über das Feature hinaus |
|---|---|---|
| **Sentry sendet keinen Anfragerumpf mehr** | `config/packages/sentry.yaml`, `when@prod`: `max_request_body_size: never` | ⚠ **Bisher ging der Rumpf jeder Anfrage bis 10 KB mit**, wenn sie in einen Fehler lief: sentry/sentry 4.30 erfasst ihn unabhängig von `send_default_pii` (Vorgabe `medium`, nachgesehen in `vendor/sentry/sentry/src/Integration/RequestIntegration.php` und `Options.php`, 2026-11-13). Das betraf schon heute Passwortfelder (Registrierung, Passwortwechsel, Kontolöschung) und Board-Texte. `zend.exception_ignore_args` deckt nur Funktionsargumente, nicht den Rumpf. Die Einstellung schließt das für alle Wege. **Befund für den Betreiber:** ob bereits Ereignisse mit Rumpf in Sentry liegen, ist dort zu prüfen |
| Restaurantkarte als Partial | `partials/_restaurant_card.html.twig`, aus `restaurant/index.html.twig` herausgelöst | Suche rendert dieselbe Karte wie vorher, nur mit Herz und Anker; Prüfläufe der Suche müssen grün bleiben |
| Ansagebereich im Layout | `base.html.twig`, im Meldungscontainer | leeres Element auf jeder Seite, ohne Inhalt stumm |
| Untere Leiste | `partials/_bottom_nav.html.twig` | angemeldet „Gespeichert“ statt „Über uns“ (Decision Log 25) |
| Kopfzeile | `base.html.twig`, Kontobereich | ein Verweis mehr (angemeldet, ab 768 px) |
| Messung: Pfadkürzung, Ereignisse, Filterschlüssel | `CollectPayloadNormalizer`, `before_send.ts`, `UsageEventCatalogue` | neue Regel für einen neuen Pfad; Bestand unberührt |
| Zwischenspeicher privater Seiten | neuer Zuhörer + Twig-Funktion | Such- und Detailseite für **Angemeldete** mit `no-store`; Gäste unverändert |
| Login-Seite | `security/login.html.twig`, `SecurityController::login` | ein Hinweis, nur bei Merkwunsch |
| Detailseite rendern aus mehreren Controllern | gemeinsamer Baustein für die Seitendaten von `show` (Öffnungstag, Haltestellen, Radius, eigener Merkzustand) | `RestaurantController::show` nutzt ihn mit; Verhalten unverändert |

## Wo die Logik liegt

Namensraum **`App\Saved\`** für die Geschäftslogik (wie `App\Board\`, `App\Roadmap\`),
Controller dünn.

| Baustein | Aufgabe |
|---|---|
| `FavoriteService` | merken / entfernen, **mit Absicht statt Umschalter** (AK-05): „merken“ auf ein gemerktes Lokal ändert nichts und meldet „keine Änderung“, ebenso „entfernen“ auf ein entferntes |
| `SavedListService` | anlegen, umbenennen, löschen, Mitgliedschaften eines Lokals setzen; Namensregeln (trimmen, NFC, Länge, Dublette, 25er-Grenze) an **einer** Stelle für Übersicht und Detailseite |
| `RestaurantNoteService` | speichern / leeren; `\r\n` → `\n`, trimmen, Länge |
| `PendingFavorite` | Merkwunsch in der Sitzung ablegen, lesen, löschen; Prüfung des Felds `ziel` |
| `SavedState` | für eine Seite mit Karten in **einer** Abfrage je Art: welche dieser Lokale sind gemerkt, welche Notizen gehören dazu (kein N+1, Muster `findVotedIdeaIds`) |
| `PrivatePages` | Routenliste für Zwischenspeicher-Regeln |
| `PendingFavoriteSubscriber`, `PrivatePageCacheSubscriber` | in `src/EventSubscriber/` |
| Controller | `SavedController` (Bereich „Gespeichert“), `RestaurantSavedController` (Herz, Häkchen, Notiz an einem Lokal) |
| Formulartypen | `SavedListNameType` (ein Feld), `RestaurantListsType` (Häkchen + neue Liste), `RestaurantNoteType` (ein Feld); jeweils `'empty_data' => ''` (CLAUDE.md, BF-27) |
| Repositories | je Entity; jede Methode, die Listen liefert, nimmt das Konto als Pflichtargument |

**Abfragen mit Karten** nutzen `Paginator` mit `fetchJoinCollection` (Karten laden
Öffnungszeiten und Küchen per Join; `setMaxResults` allein zählte Zeilen, nicht Lokale — die
Falle aus BF-64). Seite hinter dem Ende → 404 wie in der Suche (BF-60, EC-16).

**Filter „Nur meine Favoriten“:** zusätzliche Bedingung in `RestaurantRepository::findPaginated`
(„es gibt einen Favoriten dieses Kontos für das Lokal“), nur wenn ein Konto angemeldet ist;
für Gäste wird der Parameter nicht ausgewertet (AK-39). Für den Leertext AK-40 fragt der
Controller zusätzlich, ob das Konto überhaupt etwas gemerkt hat.

**Anzeige der Notiz** als Text mit `white-space: pre-line` (Zeilenumbrüche sichtbar, kein HTML
erzeugt), Twig-Autoescape bleibt an (AK-37). **Kein `maxlength`** an Notiz und Listenname: Der
Browser schnitte eingefügten Text still ab; so gibt es die Meldung und die Eingabe bleibt
(AK-18, AK-35).

**Barrierefreiheit des Herzens:** Der Knopf trägt den Text „<Name> merken“ bzw. „<Name> aus
Favoriten entfernen“ (visuell verborgen), das Symbol ist `aria-hidden`; **kein**
`aria-pressed` zusätzlich (sonst läse VoiceOver „… entfernen, gedrückt“). Gefüllt und leer
unterscheiden sich in der Form (ausgefülltes vs. umrandetes Herz), Zielfläche 44 × 44 px,
Fokus als `outline` (Projektregel, kein Ring).

## Mitzuziehen (Prüfläufe und Dokumentation)

| Stelle | Was |
|---|---|
| `LimiterCoverageTest` | `saved_change` muss verdrahtet sein und den `when@test`-Override tragen |
| `CatalogueCompletenessTest` | alle neuen Schlüssel in fünf Sprachen, `messages` und `validators` |
| `SeoRegistry` | GET-Routen `app_saved_index`, `app_saved_favorites`, `app_saved_list_show`, `app_saved_list_delete_confirm` in `UNLISTED_ROUTES` (nur nach Anmeldung, wie `app_onboarding`); `app_saved_favorites` und `app_saved_list_show` in `PAGINATED_ROUTES` (lesen `page`, `SeoRouteCoverageTest` prüft beide Richtungen) |
| `RouteDirectoryCollisionTest` | kein Verzeichnis `public/gespeichert` (BF-100) |
| `UsageEventCatalogueTest` | gleicht `FILTER_KEYS` gegen das Filterformular ab — `favorites` gehört in beide |
| `bin/sicherung-pruefen.sh` | vier Tabellen in `ERWARTETE_TABELLEN` |
| `docs/data-model.md` | vier Entities, ERD, Migrationshistorie |
| `docs/datenschutz.md` | eigener Abschnitt „Favoriten, Listen und Notizen“ (Zweck, Rechtsgrundlage Art. 6 Abs. 1 lit. b, keine Empfänger, Löschung, Auskunft; Einstufung B begründet) |
| `/legal`, Datenschutzabschnitt | ein Absatz in fünf Sprachen (AK-68) |
| `docs/app-shell.md` | untere Leiste und Kopfzeile |
| `docs/prd.md` | Zielgruppe 1 „Offener Punkt“, Roadmap-Tabelle |
| `CLAUDE.md` | eigener Abschnitt mit den Fallstricken dieses Entwurfs |
| Beim Release | `RoadmapRegistry`: `favourites` entfernen; `ChangelogRegistry` + fünf `changelog.*.yaml` (AK-70) |

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | Vier Tabellen; Favoriten getrennt von Listen | Favoriten als feste erste Liste in `saved_list` | Die Spec trennt beides fachlich (Herz ≠ Liste, eigener Filter, nicht umbenennbar). Als Sonderliste bräuchte jede Listenregel eine Ausnahme |
| 2 | Kaskade in der Datenbank, Beziehungen einseitig | Rückbeziehungen an `User`/`Restaurant` mit `orphanRemoval` | Muster `board_vote`; Kontolöschung und Restaurantverwaltung bleiben unberührt; kein Laden der Sammlungen beim Löschen |
| 3 | Besitz über die Abfrage | Voter `SavedListVoter` | Kein Weg, die Prüfung zu vergessen; 404 für fremd und fehlend fällt von selbst gleich aus |
| 4 | Turbo-Stream mit `morph` nur fürs Herz und fürs Kartenraster | Turbo-Frame je Karte; reines Neuladen mit Anker | Frame verschachtelt Formulare und Verweise der Karte; reines Neuladen verliert den Fokus und springt bei Turbo nach oben |
| 5 | Merkwunsch in der Sitzung + `LoginSuccessEvent` | Merken per GET-Link nach der Anmeldung (`/login?merken=12`) | Ein GET, der etwas speichert, ließe sich von fremden Seiten auslösen; die Sitzung gehört dem Besucher |
| 6 | Token je **Handlungsart** für das Herz (`favorit`), nicht je Lokal | `favorit-{id}` wie `board-vote-{id}` | Auch Gäste rendern Herzen; je Lokal sammelte jede Gast-Sitzung beim Stöbern hunderte Token. Das Token bindet an die Sitzung — das ist der Schutz; die Lokalnummer steht ohnehin in der Adresse |
| 7 | `name_key` mit binärer Kollation | eindeutiger Index auf `name` mit Standardkollation | Standardkollation ist akzentblind („Café“ = „Cafe“) |
| 8 | Schreibsperre je Konto | eindeutige Indizes + Ausnahme abfangen | Eine Unique-Verletzung schließt Doctrines EntityManager; danach scheitert jede weitere Arbeit der Anfrage. Die Sperre deckt außerdem die 25er-Grenze, die kein Index kann |
| 9 | Limiterschlüssel Kontonummer | E-Mail (`getUserIdentifier()`, Board) | Bleibt bei einem Adresswechsel stabil |
| 10 | Favoriten-Filter im bestehenden Ereignis `filter_angewandt` (Schlüssel `favorites`) | eigenes Ereignis „Favoriten-Filter benutzt“ | `UsageEventCatalogueTest` verlangt jeden Filterschlüssel im Katalog; ein eigenes Ereignis zählte dieselbe Handlung doppelt. AK-66 ist damit als „`filter_angewandt` mit `favorites`“ nachzuweisen |
| 11 | `Cache-Control: no-store` + Turbo-Meta für private Seiten Angemeldeter | Abmelden als volle Seitenladung (`data-turbo="false"`) | Die Abmeldung leert Turbos Speicher, aber nicht den Back-Forward-Cache des Browsers; `no-store` deckt beides |
| 12 | Sentry `max_request_body_size: never` | Notizfeld in einem `before_send` herausfiltern | Ein Filter je Feld vergisst das nächste Feld; der Rumpf hat in einem Fehlerbericht keinen Nutzen, den die Adresse und der Stacktrace nicht schon liefern |
| 13 | 12 Karten je Seite (Spec Decision Log 23) | 6 wie die Suche | — (Spec) |
| 14 | Export-Format bleibt `endlech.lu account export v1`, drei neue Schlüssel | Version erhöhen | Rein ergänzend, keine bestehende Angabe ändert Bedeutung oder Form (wie bei `boardIdeas`) |

## Abdeckung der Akzeptanzkriterien

Abgegangen aus `spec.md`, der Reihe nach.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Restaurantkarte mit Herz; `FavoriteService` merken; Turbo-Stream (morph + Ansagebereich) | Seite bleibt stehen, weil nichts neu geladen wird |
| AK-02 | Herz im Kopf der Detailseite (beide Varianten); derselbe Weg | |
| AK-03 | `FavoriteService` entfernen; Listen und Notizen liegen in eigenen Tabellen | |
| AK-04 | `favorite`-Zeile; `SavedState` liest sie für jede Kartenseite | |
| AK-05 | Herz mit Absicht (`merken`/`entfernen` getrennte Routen); Schreibsperre; eindeutiger Index | |
| AK-06 | 303 auf geprüftes `ziel` + `#restaurant-{id}`; Karten-Anker mit `scroll-mt-24` | |
| AK-07 | Karte/Detailseite rendern für Gäste nur das Herz; Abschnitt „Für dich“ nur angemeldet | |
| AK-08 | `app_favorite_add` für Gäste: `PendingFavorite`, Zielseite, Weiterleitung; Hinweis auf der Anmeldeseite | |
| AK-09 | `PendingFavoriteSubscriber` auf `LoginSuccessEvent`; Zielseite mit Anker | Passwort und Passkey |
| AK-10 | Merkwunsch bleibt bis zur gelungenen Anmeldung in der Sitzung | |
| AK-11 | `saved/index`: Zahlen aus gruppierter Abfrage, Listen `created_at` absteigend | |
| AK-12 | `saved/favorites`: Paginator 12, `created_at` absteigend; Notizen über `SavedState` | |
| AK-13 | Leertexte in `saved/index` | |
| AK-14 | Turbo-Stream ersetzt Kartenraster; Stimulus `focus-after-stream` setzt den Fokus auf das vom Server markierte Ziel | ohne JS: Neuladen mit Anker auf die nächste Karte |
| AK-15 | `access_control` `^/[a-z]{2}/gespeichert`; Firewall merkt die Zielseite | |
| AK-16 | `SavedListService` anlegen; Übersicht | |
| AK-17 | `SavedListNameType`: Pflicht nach Trimmen, `empty_data` | |
| AK-18 | Länge ≤ 60 nach Trimmen und NFC; kein `maxlength`, Eingabe bleibt | |
| AK-19 | `name_key` + eindeutiger Index; Prüfung im Dienst → Fehler am Feld | |
| AK-20 | Grenze 25 im Dienst unter Schreibsperre; gilt für Übersicht und Detailseite | |
| AK-21 | `RestaurantListsType`: Häkchen aus eigenen Listen, vorbelegt mit Mitgliedschaften | |
| AK-22 | `SavedListService` setzt Mitgliedschaften; Herz unberührt | |
| AK-23 | Neue Liste im selben Absenden; scheitert der Name, wird nichts übernommen (eine Transaktion) | |
| AK-24 | Formular ohne Häkchen, nur Feld | |
| AK-25 | `saved/list`: Name als h1, Paginator 12, `added_at` absteigend | |
| AK-26 | `app_saved_list_entry_remove`; nur der Eintrag fällt weg | Stream mit Fokus wie AK-14 |
| AK-27 | Leertext in `saved/list` | |
| AK-28 | `app_saved_list_rename`; Namensregeln aus `SavedListService`; eigener Name ist keine Dublette | |
| AK-29 | `app_saved_list_delete_confirm` (GET-Seite, kein `onsubmit`) | |
| AK-30 | „Abbrechen“ ist ein Verweis; `app_saved_list_delete` löscht, Kaskade nur auf Einträge | |
| AK-31 | eindeutiger Index `(list_id, restaurant_id)`; Schreibsperre | |
| AK-32 | Abschnitt „Deine Notiz“ für jedes angemeldete Konto, unabhängig von Herz/Liste | |
| AK-33 | `RestaurantNoteService` speichern; Weiterleitung `#notiz` | |
| AK-34 | `SavedState` liefert Notizen zu den Karten jeder Gespeichert-Seite | |
| AK-35 | `RestaurantNoteType` Länge ≤ 500 nach `\r\n`→`\n`; 422, Zeile unverändert, Eingabe im Feld | |
| AK-36 | Leere Notiz löscht die Zeile | |
| AK-37 | Autoescape + `white-space: pre-line` | |
| AK-38 | Bedingung in `findPaginated`, kombiniert mit allen Filtern | |
| AK-39 | Häkchen nur angemeldet gerendert; Parameter für Gäste nicht ausgewertet | |
| AK-40 | Controller prüft „hat das Konto Favoriten?“, Leertext mit Verweis ohne `favorites` | |
| AK-41 | `_bottom_nav`: angemeldet Start · Restaurants · Gespeichert · Profil; aktiv auf `app_saved_*` | nach OF-04 |
| AK-42 | vier Spalten wie bisher (80 px bei 320 px); Prüfung in fünf Sprachen im Browser | |
| AK-43 | Kopfzeile, Kontobereich | |
| AK-44 | Profilseite | |
| AK-45 | `SeoRegistry::UNLISTED_ROUTES`; Sitemap bietet nur feste Seiten und Lokale an | |
| AK-46 | Übersetzungsschlüssel in fünf Sprachen; Listennamen und Notizen ohne `trans` | `CatalogueCompletenessTest` |
| AK-47 | Knopftext mit Name und Handlung | |
| AK-48 | Ansagebereich `role="status"`; `morph` hält das Knopf-Element und damit den Fokus | Nachweis im Browser; falls Morph den Fokus verliert: Stimulus stellt ihn über die Kennung wieder her |
| AK-49 | zwei Herzformen, Kontrast ≥ 3 : 1, 44 × 44 px, `outline`-Fokus | |
| AK-50 | `_form_field` (Meldung verknüpft, `autofocus` am ersten fehlerhaften Feld) | |
| AK-51 | Gesamtablauf; Durchgang im Browser bei 375 px | Abnahme des Betreibers |
| AK-52 | `SavedState` nur für das angemeldete Konto; Gäste rendern nichts davon | |
| AK-53 | Repository „Liste mit Nummer **und** Konto“ → 404 | |
| AK-54 | dieselbe Abfrage vor jeder Handlung; Häkchen-Nummern gegen eigene geprüft → 404 | Besitz vor Token |
| AK-55 | Lokal über die Routennummer geladen → 404 bei unbekannter | |
| AK-56 | Sitzungstoken bzw. `submit`-Token; einheitlich `flash.invalid_csrf` | |
| AK-57 | Limiter `saved_change` 120/h, Schlüssel Kontonummer | |
| AK-58 | `consume()` nur nach tatsächlicher Änderung; ein Absenden = eins | |
| AK-59 | Keine Verwaltungsseite und keine Dashboard-Kennzahl liest die vier Tabellen | Prüflauf über alle `/admin`-Routen |
| AK-60 | `AccountDataExporter`: `favorites`, `savedLists`, `notes` | |
| AK-61 | DB-Kaskade über `user_id` | Prüflauf zählt Zeilen nach der Löschung |
| AK-62 | DB-Kaskade über `restaurant_id` | |
| AK-63 | `PrivatePages` → `no-store` + Turbo-Meta | Nachweis im Browser |
| AK-64 | Normalizer/`before_send.ts` kürzen Listenpfade; Ereignisse ohne Daten; keine Protokollzeile mit Inhalt; Brevo unberührt | |
| AK-65 | Sentry `max_request_body_size: never` | systemweit |
| AK-66 | `herz_gesetzt`, `liste_angelegt` als `usage_event` nach gelungener Handlung (Flash bzw. im Stream); Favoriten-Filter als `filter_angewandt` mit `favorites` | Technische Entscheidung 10 |
| AK-67 | Pfadkürzung `/gespeichert/listen/{id}` → `/gespeichert/listen` | |
| AK-68 | Absatz im Datenschutzabschnitt von `/legal`, fünf Sprachen | |
| AK-69 | keine Prüfung auf Bestätigung in diesen Wegen | anders als Board und Vorschlag |
| AK-70 | Release: `RoadmapRegistry` ohne `favourites`, `ChangelogRegistry` + Kataloge | beim Release |

70 von 70 Kriterien zugeordnet.
