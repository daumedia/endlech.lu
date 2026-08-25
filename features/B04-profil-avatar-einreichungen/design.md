# B04 · Profil, Avatar & eigene Einreichungen — Systemdesign

Status: `review` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Eine Anzeigeroute (GET) und drei Änderungsrouten (POST), alle an derselben
Controller-Klasse mit `#[IsGranted('IS_AUTHENTICATED_FULLY')]` auf Klassenebene. Die
Formulare bekommen ihr `action` beim Bauen mitgegeben, damit die Anzeigeseite ein
reines GET bleibt und jede Änderung ihren eigenen Endpunkt hat.

Bei ungültiger Eingabe rendert die jeweilige POST-Route dieselbe Seite neu — Symfony
liefert dafür automatisch HTTP 422. Das jeweils **andere** Formular wird dabei frisch
und leer aufgebaut.

Der Nutzer wird nie über eine ID aus der Anfrage bestimmt, sondern immer über
`$this->getUser()`. Damit gibt es in diesem Feature strukturell kein IDOR.

## Seiten und Routen

| Route | Pfad | Methode | Zweck |
|---|---|---|---|
| `app_profile` | `/{_locale}/profile` | GET | Anzeige, drei Formulare, Einreichungen |
| `app_profile_edit` | `/{_locale}/profile/edit` | POST | Name, E-Mail, Avatar |
| `app_profile_password` | `/{_locale}/profile/password` | POST | Passwortwechsel |
| `app_profile_avatar_delete` | `/{_locale}/profile/avatar/delete` | POST | Avatar entfernen |

Zugang doppelt abgesichert: `access_control` `^/[a-z]{2}/profile` =
`IS_AUTHENTICATED_FULLY` **und** das Attribut an der Klasse.

## Komponentenstruktur

```
profile/index.html.twig
├── partials/_avatar.html.twig            Anzeige, Größe 'sm'|'lg'
├── ProfileType          → app_profile_edit
│   ├── name    TextType   NotBlank, Length(2..100)
│   ├── email   EmailType  NotBlank, Email
│   └── avatar  FileType   mapped:false, File(2M, jpeg|png|webp)
├── <form> Avatar löschen  nacktes POST, CSRF-Token 'delete-avatar'
├── ChangePasswordType   → app_profile_password
│   ├── currentPassword  mapped:false, NotBlank
│   └── newPassword      RepeatedType, mapped:false, Length(8..4096)
├── partials/_passkey_manage.html.twig     → B03
└── Sektion „Meine Einreichungen"          aus findBySubmitter()
```

## Datenmodell

B04 legt keine Tabelle an. Beschrieben werden `user.name`, `user.email`,
`user.password`, `user.avatar_filename`.

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `avatar_filename` | VARCHAR(255) | nein | reiner Dateiname, kein Pfad |

`User::getAvatarUrl()` setzt daraus `/uploads/avatars/{filename}` zusammen — der Pfad
steht also **im Code**, nicht in der Datenbank.

**Gelesen aus `restaurant`:** `RestaurantRepository::findBySubmitter($user)` filtert auf
`r.submittedBy = :user`, sortiert `createdAt DESC`. Die Beziehung ist
`ManyToOne` mit `ON DELETE SET NULL` — ein gelöschtes Konto ließe seine Einreichungen
stehen, anonymisiert. (Löschen gibt es allerdings nicht, siehe FB-01.)

Migration: `Version20260317000000` (Avatar), `Version20260319000000` (`submittedBy`).

### Dateisystem

| Ort | Inhalt | Sichtbarkeit |
|---|---|---|
| `public/uploads/avatars/` | hochgeladene Profilbilder | **öffentlich**, direkt vom Webserver ausgeliefert |

Gitignoriert bis auf `.gitkeep`. ⚠ `git clean -fd` im Deploy läuft **ohne** `-x`,
Uploads überleben also — anders als `public/uploads/team/`, das per `!`-Regel aus
`.gitignore` ausgenommen ist und dessen nicht committete Dateien der Deploy löscht.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | nichts | nichts | `access_control` + `#[IsGranted]` |
| Angemeldeter | **nur die eigenen** Stammdaten | dieselben | `$this->getUser()`, keine ID aus der Anfrage |
| Angemeldeter | **nur die eigenen** Einreichungen | — | `findBySubmitter($this->getUser())` |
| beliebig | jede Avatardatei, deren URL er kennt | — | Webserver, keine Prüfung |

Kein Voter nötig, weil kein Objekt über eine Anfrage-ID adressiert wird.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `app_profile_edit` | Upload 2 MB, drei MIME-Typen | 422 | `ProfileType`, `File`-Constraint |
| `app_profile_password` | **kein Rate Limit** | — | — (FB-07) |
| `app_profile_avatar_delete` | CSRF `delete-avatar` | Flash `flash.invalid_csrf`, keine Änderung | Controller |

Der `File`-Constraint prüft den **tatsächlichen** MIME-Typ über die
`fileinfo`-Erweiterung, nicht die Endung. Ein umbenanntes PHP-Skript wird also
abgewiesen — was wichtig ist, weil das Zielverzeichnis im Web-Root liegt.

Nicht vorhanden: Neukodierung des Bildes (FB-06), Größenbegrenzung der Bildabmessungen,
Prüfung auf eingebettete Metadaten.

## Externe Dienste

Keine. Avatare liegen lokal, es gibt kein CDN und keinen Objektspeicher.

## Erkennbare Entscheidungen

| # | Entscheidung | Alternative | Warum so, soweit erkennbar |
|---|---|---|---|
| 1 | Anzeige GET, Änderungen POST auf eigenen Routen | ein Endpunkt mit Weichen | jede Änderung ist einzeln adressierbar und im Router sichtbar |
| 2 | `action` beim Formularbau setzen | `action` im Template | hält die Route an einer Stelle |
| 3 | Avatar-Upload in einem Service | im Controller | derselbe Zuschnitt wie `ImageUploadService` (B09); beide löschen die alte Datei mit |
| 4 | Alte Datei löschen, bevor die neue kommt | behalten | verhindert unbegrenztes Wachstum des Verzeichnisses |
| 5 | Passwortprüfung über `isPasswordValid()` statt Constraint | `UserPassword`-Constraint | erlaubt die eigene Flash-Meldung statt eines Feldfehlers — der Preis ist ein Redirect statt 422 |
| 6 | `uniqid('', true)` als Dateiname | `bin2hex(random_bytes())` | Grund nicht erkennbar; für öffentliche Dateien folgenlos |
| 7 | E-Mail-Änderung ohne erneute Bestätigung | Token-Umweg wie in B01 | **Grund nicht erkennbar** — siehe AK-12, das ist die gewichtigste Lücke des Features |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | `access_control`, `#[IsGranted]` an der Klasse | |
| AK-02 | `ProfileController::index()`, `profile/index.html.twig` | |
| AK-03 | `edit()` + `ProfileType`, Flash `flash.profile_updated` | |
| AK-04 | `AvatarUploadService::upload()` | |
| AK-05 | `File(maxSize: '2M', mimeTypes: […])` | Symfony liefert 422 für submitted-invalide Formulare |
| AK-06 | `upload()` löscht `$oldFilename` vor dem `move()` | |
| AK-07 | `deleteAvatar()` → `AvatarUploadService::delete()` | |
| AK-08 | `isCsrfTokenValid('delete-avatar', …)` | |
| AK-09 | `isPasswordValid()` in `changePassword()` | |
| AK-10 | `hashPassword()` + `flush()` | |
| AK-11 | `RestaurantRepository::findBySubmitter()` | |
| AK-12 ⚠ | `ProfileType` führt `email`; `edit()` flusht ohne Weiteres | Lücke, siehe FB-03 |
| AK-13 | Symfonys Sicherheitskontext (`hasUserChanged`) und die `remember_me`-Signatur | **berichtigt 2026-08-24** — keine Lücke, das Framework erledigt es |
| AK-14 ⚠ | **Abwesenheit** eines Limiters | Lücke, siehe FB-07 |
| AK-15 | `File`-Constraint, `fileinfo` | |
| AK-16 | Speicherort `public/uploads/avatars` | beschreibt eine bewusste Eigenschaft, keine Lücke |
| AK-17 | `findBySubmitter($this->getUser())` | |
| AK-18 | keine Logging-Aufrufe im Controller; `zend.exception_ignore_args = On` | |

## Für `sdd-qa` besonders zu prüfen

1. **AK-12** — E-Mail auf eine fremde Adresse ändern und prüfen, ob `is_verified`
   `true` bleibt. Das ist der Weg zur stillen Kontoübernahme.
2. **AK-13** — in zwei Browsern anmelden, in einem das Passwort ändern, im anderen
   weiterarbeiten.
3. **FB-01/FB-02** — beides Pflichten aus dem Katalog. Die Kaskaden für ein Löschen
   sind bereits gesetzt; es fehlt nur der Auslöser.
4. **FB-06** — ein Foto mit GPS-Metadaten hochladen und die abgelegte Datei prüfen.
