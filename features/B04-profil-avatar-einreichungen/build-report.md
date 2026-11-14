# B04 · Profil, Avatar & eigene Einreichungen — Abschlussbericht der Reparatur

Eingang: Fehlerauftrag (Status `review`, Rücksprung durch den Betreiber am 2026-11-14) · Auftrag: BF-182, BF-184,
BF-186 aus `features/befunde.md`, gefunden in der QA von Feature 12 (vierter Durchlauf) · Branch
`feature/17-favoriten-und-listen`, neben dem uncommitteten Feature 17 · nicht committet

## Abschlussbericht · 2026-11-14

### 1 · Umgesetzt

Das Profilformular beantwortet einen leeren Namen, einen Namen aus Leerzeichen, ein leeres Adressfeld, eine
RFC-widrige Adresse und eine Adresse über 180 Zeichen mit HTTP 422 und einer Meldung am Feld statt mit HTTP 500; eine
abgewiesene Adresse wird nicht vorgemerkt, verschickt keine Mail und verbraucht keinen Platz im Deckel `email_change`.
„Profilbild entfernen“ und die Kontolöschung räumen ein Bild, das ein Upload daneben gespeichert hat, samt Verweis ab.
3 von 3 Befunden behoben; die drei QA-Reproduktionen in `Qa12Nachpruefung4Test` laufen jetzt immer und sind grün.

| Befund | Grad | Zustand | Änderung | Nachweis |
|---|---|---|---|---|
| BF-182 · Name/Adresse leer → 500 | mittel | **behoben** | `ProfileType`: `'empty_data' => ''` an `name` und `email` | `Qa12Nachpruefung4Test::testBf182…`, `Bf182Bf184ProfilEingabenTest::testBf182…` (6 Fälle) |
| BF-184 · RFC-widrige / > 180 Zeichen → 500 | mittel | **behoben** | `ProfileType`: `Email` strikt, `Length(max: 180)`; `ProfileController::edit()`: `new Address()` vor allem, was schreibt | `testBf184…` (QA), `Bf182Bf184ProfilEingabenTest::testBf184…` (12 Fälle) |
| BF-186 · Entfernen/Kontolöschung neben Upload | niedrig | **behoben** | `AvatarUploadService::delete()` schreibt `NULL` per SQL unter der Sperre; `AccountDeleter` ruft `delete()` immer | `testBf186…` (QA), `Bf186AvatarEntfernenTest` (3 Fälle) |

Im Browser gegen den laufenden Server nachgestellt (`/tmp/b04vorlage/protokoll.txt`): auf `main` alle drei Eingaben
HTTP 500 (`Expected argument of type "string", "null" given at property path "name"`, `RfcComplianceException`,
`SQLSTATE[22001] … 'pending_email'`), die RFC-widrige Adresse blieb vorgemerkt; nach dem Bau 422 mit Meldung, in
`lb` bei 1280 und 375 px, die neue Meldung `user.email_max` in allen fünf Sprachen, kein waagrechter Überstand
(`scrollWidth` 375/375).

### 2 · Offene Akzeptanzkriterien

Keins aus dem Auftrag. Zwei Punkte außerhalb, als offene Fragen in `spec.md` eingetragen, **nicht gebaut**:

- **OF-06 · Zweites Absenden nach einer Meldung → HTTP 405** (vorbestehend, gemessen auf `main` und nach dem Bau
  gleich). Die 422-Seite rendert das Profilformular ohne `action`; Turbo lässt die Adresse `/xx/profile` stehen, die
  Korrektur geht an `POST /xx/profile` → 405 und ist verloren. Gemessen: Name „a“ → 422, Formular-`action` `null`,
  Korrektur „Test User“ → `POST /de/profile` HTTP 405, auf beiden Ständen. Betrifft jeden 422-Weg des Profils — und
  damit seit dieser Reparatur auch die Eingaben aus BF-182/BF-184, die vorher als 500 endeten: Die Meldung erscheint
  jetzt, die Korrektur scheitert danach. Muster BF-151. Vorschlag: `'action' => $this->generateUrl('app_profile_edit')`
  in `edit()` (und das Gegenstück für das Passwortformular in `changePassword()`, im Code gleich, nicht gemessen).
- **OF-07 · Restfenster Kontolöschung neben Upload.** Ein Upload, der die Sperre erst nach `delete()` bekommt, legt
  eine Datei für ein Konto ab, das gleich verschwindet. Nur im Code nachvollzogen, nicht nachgestellt.

### 3 · Getroffene Annahmen

- **Neuer Katalogschlüssel `user.email_max`** in allen fünf `validators.*.yaml` (Texte aus `app_waitlist.email_max`
  übernommen, mit `{{ limit }}` statt fester 180). Der freigegebene Weg nannte „Länge 180“, aber keine Meldung; ein
  fremder Schlüssel (`app_waitlist.*`) wäre der falsche Ort.
- **Die Wunschadresse wird so gespeichert, wie `Mime\Address` sie geprüft hat** (`$empfaenger->getAddress()`, nach
  `trim()` identisch mit der Formulareingabe).
- **Die Adressprüfung steht vor dem Upload**, nicht erst vor `requestEmailChange()`: „vor allem, was schreibt“ wie
  BF-175 — sonst wäre bei einem Fehlschlag das Bild schon gespeichert.
- **Zwei Meldungen bei leerem Namen** („Bitte gib deinen Namen ein.“ und „Der Name muss mindestens 2 Zeichen lang
  sein.“): `Length(min: 2)` prüft auch die leere Zeichenkette. So gelassen — die Bedingungen stehen seit jeher so da,
  der Auftrag war `empty_data`. Ebenso zeigt die Kopfzeile auf der 422-Seite den eingegebenen (leeren) Namen, bis die
  Seite neu geladen wird; gespeichert wird nichts (Test prüft die Zeile).
- **Bilder der Vorlage gegen die Entwicklungsdatenbank** (wie vom Auftraggeber vorgegeben, Port 8000), nicht gegen
  eine Wegwerf-Datenbank. Der vorher-Stand hat beim Nachstellen von BF-184 `../../etc/passwd@example.lu` am Konto
  `user@endlech.lu` vorgemerkt — genau der Befund; danach per SQL zurückgesetzt. Endstand des Kontos wie vorher
  (Name „Test User“, keine Vormerkung, kein Bild).

### 4 · Systemweite Änderungen

- `translations/validators.{lb,de,fr,en,pt}.yaml` — je eine Zeile `user.email_max` im Block `user:`. ⚠ Dieselben
  Dateien tragen uncommittete Änderungen von Feature 17 (Block `saved:` am Ende); die Hunks liegen getrennt.
- `CLAUDE.md` — drei Ergänzungen (Abschnitt E-Mail-Änderung: BF-184/BF-182; BF-119-Absatz: Profil als fünfter Weg,
  offen bleiben vier Formulare aus OF-BF119a; `AvatarUploadService`-Zeile: BF-186). ⚠ Datei trägt ebenfalls
  uncommittete Änderungen von Feature 17, Hunks getrennt.
- `AccountDeleter` ruft `AvatarUploadService::delete()` jetzt bei **jeder** Kontolöschung (eine `SELECT` mehr, unter
  der Sperre je Konto) — wirkt auf Feature 01 (Konto löschen).
- `Qa12Nachpruefung4Test` (Feature 12): drei Aufrufe `$this->offen(…)` entfernt, wie die Datei es für die Behebung
  vorschreibt; BF-185 bleibt übersprungen (Feature 12, nicht dieser Auftrag).
- Keine neue Abhängigkeit, keine Migration, keine Konfiguration.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 21 (18 in `Bf182Bf184ProfilEingabenTest`, 3 in `Bf186AvatarEntfernenTest`) |
| davon rot gesehen, dann grün | 14 |
| QA-Reproduktionen scharf geschaltet (vorher rot, jetzt grün) | 3 |
| gelaufen in der letzten Verifikation | 2 320, 0 Fehlschläge, 43 übersprungen (vorher 2 299 / 46) |

Rot gesehen, aus dem erwarteten Grund: 6 × `Expected argument of type "string", "null" given` (BF-182), 4 ×
`RfcComplianceException`, 1 × `SQLSTATE[22001]`, 1 × Umlaut-Domain vom HTML5-Default abgewiesen (BF-184), 2 × toter
Verweis bzw. liegengebliebene Datei (BF-186).

**Nicht rot gesehen, mit Grund (7):** `testBf184AdresseMitGenau180ZeichenWirdVorgemerkt`, fünf Fälle von
`testBf184GueltigeAdresseWirdWeiterhinVorgemerkt` und `testEntfernenOhneGleichzeitigenUploadWieBisher` sind
Gegenproben auf bestehendes Verhalten (die Reparatur darf es nicht nehmen) — sie waren vorher grün und sind es weiter.

**Rückbau-Gegenproben** (je eine Zeile entfernt, Test rot, wiederhergestellt): `Length(max: 180)` → 1 rot; strikter
Modus → 5 rot; `empty_data` → 6 rot; SQL-`NULL` in `delete()` → 1 rot; alte Bedingung im `AccountDeleter` → 1 rot;
Schlüssel `user.email_max` in `pt` entfernt → `CatalogueCompletenessTest` rot.

**Ohne dauerhaften Test:** die Adressprüfung vor dem `flush()` in `ProfileController::edit()` — über das Formular
nicht mehr erreichbar (die strikte Prüfung hält jede Adresse ab, die `Mime\Address` ablehnt; wie bei BF-119). Belegt
per Rückbau der strikten Prüfung: mit der Zeile HTTP 500 **ohne** Vormerkung, mit der Prüfung erst beim Versand
HTTP 500 **mit** Vormerkung `../../etc/passwd@example.lu`.

**Verifikation:** `php -l` auf alle geänderten PHP-Dateien ohne Befund; `lint:twig` 151 Dateien OK; `lint:yaml
translations/` OK; `--testsuite Unit` 610 grün (12 übersprungen). **Rot wie vorher, ohne Bezug zum Auftrag:**
`lint:container` (Alias `PublicKeyCredentialSourceRepositoryInterface` → `WebauthnCredentialRepository`) und
`doctrine:schema:validate` (ältere Indexnamen, `DATETIME`-Typen; keine Entity geändert) — beide schon im Bericht von
Feature 17 so vermerkt. `make fix-check` gibt es im `Makefile` nicht, `php-cs-fixer` liegt nicht in `vendor/bin` —
Codestil nicht maschinell geprüft.

Nächster Schritt: `/sdd-qa B04` in frischer Sitzung. Status bleibt `building`.

## Abschlussbericht · 2026-11-14 · Fehlerauftrag BF-226

Eingang **Fehlerauftrag** (`qa-report.md`, Status `review` → `building`, Rücksprung durch Claude unter dem Sitzungsziel
„deploy ready“). Auftrag: BF-226 aus `features/befunde.md`. Nicht im Auftrag: BF-227, BF-228, BF-229. Branch
`feature/17-favoriten-und-listen`, nicht committet.

### 1 · Umgesetzt

Nach einer Meldung am Profil- oder Passwortformular geht die Korrektur mit Turbo wieder an die POST-Route und wird
gespeichert, statt mit HTTP 405 verloren zu gehen. `ProfileController::edit()` baut `ProfileType` und
`changePassword()` baut `ChangePasswordType` mit ausdrücklichem `action` (Muster BF-151) — wie `index()` und die jeweils
andere Hälfte es schon taten. 1 von 1 Befund behoben (EC-04).

Im Browser nachgestellt (Chromium, Turbo aktiv, Wegwerfkonto), alle fünf Wege aus dem Bericht — Name zu kurz, Name aus
Leerzeichen, RFC-widrige Adresse, Bild über 2 MB, neues Passwort zu kurz:

| | Erstes Absenden | Formular danach | Korrektur |
|---|---|---|---|
| vorher | 422, Adresse bleibt `/de/profile` | ohne `action`, keine Meldung | **405 `POST /de/profile`**, Eingabe verloren |
| nachher | 422, Adresse bleibt `/de/profile` | `action` = `/de/profile/edit` bzw. `/de/profile/password`, Meldung am Feld | **302**, „Profil erfolgreich aktualisiert.“ bzw. „Passwort erfolgreich geändert.“, gespeichert |

Ohne JavaScript nachher ebenso 422 → 302 (wie vorher). In `lb` (1280 und 375 px) und `pt` (Passwort) wiederholt.

### 2 · Offene Akzeptanzkriterien

Keine aus dem Auftrag. Offen außerhalb des Auftrags: BF-227 und BF-228 (*niedrig*, B04), BF-229 (Feature 15 / B05).

### 3 · Getroffene Annahmen

- **Nur die beiden Formulare aus dem Befund.** Die übrigen Formulare der Profilseite (Bild entfernen, Adresswechsel
  abbrechen, Newsletter, Passkeys umbenennen/entfernen, Konto löschen, Abmelden) tragen ihr Ziel schon im Template —
  durchgesehen, nicht geändert.
- **Wegwerfkonto `build-b04@example.test`** in der Entwicklungsdatenbank (Passwort-Hash von `user@endlech.lu`
  übernommen), danach gelöscht. Die Fixture-Konten sind unverändert.
- **Kein Eintrag in `CLAUDE.md`**, um die Dateien der beiden Aufträge getrennt zu halten; die Regel steht als
  Kommentar an beiden Stellen im Controller, und das Muster BF-151 ist in `CLAUDE.md` bereits beschrieben.

### 4 · Systemweite Änderungen

Keine. Geändert sind nur `src/Controller/ProfileController.php` (zwei `createForm`-Aufrufe) und der Prüflauf der QA.
Keine Abhängigkeit, keine Migration, keine Konfiguration, keine Übersetzung.

### 5 · Tests

| | Anzahl |
|---|---|
| Tests neu geschrieben | 0 — die Reproduktionen der QA prüfen genau das Verhalten |
| Reproduktionen der QA scharf geschaltet | 2 (`QaB04NachpruefungTest::testBf226Profilformular…`, `…Passwortformular…`) — mit `QAB04_REPRO=1` rot gesehen (`null` statt `/de/profile/edit` bzw. `/de/profile/password`), jetzt immer grün |
| gelaufen in der letzten Verifikation | **2 330, 0 Fehlschläge, 44 übersprungen** (volle Suite, enthält auch den zweiten Fehlerauftrag 17) |

Die Gegenprobe `testGegenprobeProfilseiteNimmtKeinPostAn` bleibt 405. Die eigentliche Wirkung (Turbo lässt die Adresse
stehen) ist mit dem Symfony-Testclient nicht nachstellbar — er schickt ein Formular ohne `action` an die Adresse der
422-Antwort, nicht an die im Browser angezeigte; Nachweis deshalb im Browser (`/tmp/build17b/bin/bf226.js`).
Verifikation: `php -l` ohne Befund; Profil-, Passkey-, Avatar- und Adresswechsel-Prüfläufe 80 grün (4 übersprungen);
`lint:container` und `doctrine:schema:validate` rot wie vorher, ohne Bezug.

Nächster Schritt: `/sdd-qa B04` in frischer Sitzung. Status bleibt `building`.
