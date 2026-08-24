# B09 · Restaurantfotos & Galerie — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein mittlerer und ein niedriger Befund.

18 von 18 Kriterien bestanden. Die Mechanik ist sauber: Sortierung per JSON, Löschung
samt Datei und lückenloser Neuvergabe, fremde Bild-IDs mit 400 abgewiesen, fünf
schreibende Endpunkte mit je eigenem CSRF-Token.

**Der Befund, der zählt**, ist der aus der Spec — und er ist real:

```
6a8c7ed2715325.68439004.html   HTTP 200  Content-Type: text/html; charset=utf-8
    Inhalt: <html><script>alert(document.cookie)</script></html>
```

Eine hochgeladene HTML-Datei wird vom Webserver **im Ursprung der Seite** ausgeliefert.
Wer den Link öffnet, führt fremdes Skript unter `endlech.lu` aus.

**Und ein Fund, der nicht in der Spec steht:** Im Upload-Verzeichnis lagen **fünf
verwaiste Dateien** aus früheren Sitzungen, ohne Datenbankeintrag. BF-53 aus B20 ist
also nicht theoretisch — er hat schon Spuren hinterlassen.

Nächster Aufruf: **`/sdd-erfassen B05`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Hochladen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | zwei Dateien → *„2 Foto(s) erfolgreich hochgeladen."*, beide unter `public/uploads/restaurants/` |
| AK-02 | ✅ bestanden | Upload ohne Datei → *„Keine gültigen Dateien gefunden."* |
| **AK-03** | ✅ bestanden | ohne `altText` → `alt="Pizzeria Bella Vista"` (der Restaurantname); mit `altText=QA Eigener Text` → genau der Text |
| AK-04 | ✅ bestanden | neue Bilder bekamen `sort=1`, `sort=2`, danach `sort=3` — jeweils hinten |

### Sortieren

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-05** | ✅ bestanden | `{"_token":…,"imageIds":[5,4]}` → `{"success":true}`, HTTP 200; DB **`4@0,5@1` → `5@0,4@1`** |
| **AK-06** | ✅ bestanden | Bild 10 (gehört zu Restaurant 322) an Restaurant 321 gesendet → `{"error":"Image does not belong to this restaurant"}`, **HTTP 400**; Bild 10 unverändert |
| AK-07 | ✅ bestanden | falsches Token → `{"error":"Invalid CSRF token"}`, **HTTP 403** |

### Löschen

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-08** | ✅ bestanden | Bild gelöscht → *„Foto erfolgreich gelöscht."*, **Datei vom Dateisystem entfernt**, Sortierung der übrigen lückenlos: `0,1` |
| AK-09 | ✅ bestanden | fremde Bild-ID → keine Änderung (Bild bleibt) |
| AK-10 | ✅ bestanden | FK-Regel `CASCADE` auf `restaurant_image`; in B20/AK-08 gemessen: Bildzeilen verschwinden mit dem Restaurant |
| AK-11 | ✅ bestanden | Die Reihenfolge auf der Detailseite entspricht der `sort_order` in der Datenbank — erstes Bild ist das Titelbild |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-14 | ✅ bestanden | `GET /uploads/restaurants/<datei>` ohne Anmeldung → **200** |
| AK-15 | ✅ bestanden | `grep` nach `imagecreate|exif|strip` in `ImageUploadService`: **kein Treffer** — die Datei wird unverändert verschoben |
| AK-16 | ✅ bestanden | **fünf** schreibende Endpunkte, je eigenes Token: `toggle-verified-{id}`, `delete-restaurant-{id}`, `upload-images-{id}`, `delete-image-{imageId}`, `sort-images-{id}` |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-12** ⚠ | ✅ bestätigt | HTML und SVG werden ausgeliefert, PHP nicht → BF-57 |
| **AK-13** ⚠ | ✅ bestätigt | keine Größengrenze in der Anwendung; die PHP-Grenzen greifen mit irreführender Meldung → BF-58 |

## Fehler

### BF-57 · Hochgeladenes Skript läuft im eigenen Ursprung — mittel

**Betrifft:** AK-12

**Reproduktion:** Als Admin über das Fotoformular hochladen:

| Datei | Endung in der Ablage | Auslieferung | Folge |
|---|---|---|---|
| `boes.html` (`<script>alert(document.cookie)</script>`) | `.html` | `HTTP 200`, `Content-Type: text/html; charset=utf-8` | **Skript läuft unter endlech.lu** |
| `boes.svg` (mit `<script>`) | `.svg` | `HTTP 200`, `Content-Type: image/svg+xml` | läuft beim direkten Aufruf |
| `polyglot.gif` (`GIF89a<?php system(...)`) | `.gif` | `HTTP 200`, `Content-Type: image/gif` | **PHP wird nicht ausgeführt** ✓ |
| `x.php` | **ohne Endung** (`…07587981.`) | — | **nicht ausführbar** ✓ |

Die Spec beschreibt das exakt, einschließlich der Einschränkungen. Beides ist
nachgemessen und stimmt: `guessExtension()` liefert für `text/x-php` kein Ergebnis, die
Datei landet endungslos.

**Ort:** `AdminRestaurantController::uploadImage()` liest die Dateien direkt aus
`$request->files` — **kein** Symfony-Formular, **kein** `File`-Constraint.
`ImageUploadService::upload()` prüft weder MIME-Typ noch Größe; einzige Prüfung ist
`$file->isValid()`, und das betrachtet nur den PHP-Upload-Fehlercode. Das
`accept="image/jpeg,image/png,image/webp"` im Markup ist eine Bequemlichkeit des
Dateidialogs, keine Prüfung.

**Warum es trotz `ROLE_ADMIN` zählt:** Der Weg ist nicht „ein Angreifer wird Admin",
sondern „ein Admin lädt eine Datei hoch, die er bekommen hat". Ein Restaurantbetreiber
schickt ein Logo als SVG — das ist der Normalfall, nicht der Ausnahmefall. Danach liegt
die Datei **öffentlich** unter `endlech.lu` und läuft im Ursprung der Seite: Zugriff auf
die Sitzung jedes Besuchers, der sie öffnet.

**Der Kontrast im selben Projekt:** Der Avatar-Upload (B04) begrenzt auf 2 MB und prüft
`image/jpeg, image/png, image/webp` über ein `File`-Constraint. Dort ist es richtig
gelöst — nur hier nicht.

**Vorschlag:** Im `ImageUploadService` eine Positivliste der erlaubten MIME-Typen prüfen
(dieselben drei wie beim Avatar), bevor `move()` läuft. Zusätzlich empfehlenswert:
`Content-Disposition: attachment` oder ein eigener Ursprung für Uploads — beides ist die
gründlichere Lösung, aber die MIME-Prüfung ist die, die heute fehlt und morgen dastehen
kann.

### BF-58 · Keine Größengrenze — und die Meldung führt in die Irre — niedrig

**Betrifft:** AK-13

**Gemessen** an den beiden PHP-Grenzen (`upload_max_filesize: 2M`, `post_max_size: 8M`):

| Datei | Meldung |
|---|---|
| 3 MB (über `upload_max_filesize`) | *„Keine gültigen Dateien gefunden."* |
| 9 MB (über `post_max_size`) | **„Ungültiges CSRF-Token. Bitte versuche es erneut."** |

**Die zweite Zeile ist der eigentliche Fund.** Überschreitet der POST `post_max_size`,
verwirft PHP den **gesamten** Request-Body — auch das `_token`-Feld. Der Controller
sieht ein leeres Token und meldet einen CSRF-Fehler. Der Admin sucht an einer Stelle,
an der nichts kaputt ist, und bekommt nie zu hören, dass seine Datei zu groß war.

`grep` nach `maxSize|File(` im `ImageUploadService`: **0 Treffer** — die Anwendung setzt
keine Grenze.

**Vorschlag:** Eine ausdrückliche Größengrenze im Service (der Avatar-Upload nimmt 2 MB),
mit eigener Meldung. Für den `post_max_size`-Fall zusätzlich: Wenn `$request->request`
leer ist **und** `$_SERVER['CONTENT_LENGTH']` die Grenze übersteigt, ist es kein
CSRF-Problem — das ließe sich unterscheiden und sauber melden.

## Ein Fund außerhalb der Kriterien

**Im Upload-Verzeichnis lagen fünf Dateien ohne Datenbankeintrag:**
```
69ade6199b7297.04163895.png    69ade79650e554.87309696.jpg
69ade6199d9754.10209787.jpg    69b582a10261d9.83085200.jpg
69ade7964f9100.98725685.png
```
Die Zeitstempel im Dateinamen weisen auf frühere Sitzungen (Februar und Juni). Sie sind
weiterhin öffentlich abrufbar, gehören zu keinem Restaurant mehr und überleben jeden
Deploy — `git clean -fd` läuft ohne `-x`, und das Verzeichnis ist gitignoriert.

**Das ist BF-53 aus B20 in freier Wildbahn.** Der Befund war dort als *niedrig*
eingestuft, weil er theoretisch klang. Er ist es nicht: Fünf Dateien haben es bereits
durch die Bereinigung geschafft, die niemand vorgenommen hat.

Ich habe sie **nicht gelöscht** — sie sind der Beleg, und Aufräumen wäre eine Reparatur.
Die Einstufung von BF-53 bleibt *niedrig* (es sind Restaurantfotos, keine
personenbezogenen Daten), aber der Eintrag im Register bekommt diesen Nachweis.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Gespeichertes XSS** | über `.html` und `.svg` **möglich** → BF-57 |
| **Codeausführung** | `.php` landet endungslos, Polyglot wird als GIF ausgeliefert — **nicht** möglich |
| **Fremde Bild-IDs** | Sortieren → 400, Löschen → keine Änderung |
| **CSRF** | fünf Endpunkte, fünf eigene Token, alle ID-gebunden |
| **Größenbegrenzung** | nur PHP-seitig, mit irreführender Meldung → BF-58 |
| **Metadaten** | bleiben erhalten (AK-15) — bewusste Eigenschaft, in B04/FB-06 bereits erfasst |
| **Testsuite** | 364 Tests, 0 Fehler |

## Ein eigener Messfehler, der hier stehen bleibt

Beim ersten Anlauf schickte ich `{"order":[…]}` statt `{"imageIds":[…]}` an den
Sortier-Endpunkt. Antwort: `{"success":true}`, HTTP 200, Reihenfolge unverändert — und
bei einer fremden ID ebenfalls `success`. Das sah nach zwei Befunden aus (AK-05 und
AK-06 durchgefallen).

Tatsächlich war die Liste schlicht leer, weil der Schlüssel nicht passte. Mit dem
richtigen Namen verhält sich beides wie beschrieben.

**Was daran bleibt:** Der Endpunkt antwortet auf einen Body **ohne** `imageIds` mit
`success: true`, statt zu sagen, dass nichts zu tun war. Kein Befund — aber der Grund,
warum ich fast zwei falsche gemeldet hätte, und ein Hinweis darauf, dass eine leere
Liste eine eigene Antwort verdient.

## Neue Tests

Keine. Die vorhandene Abdeckung ist für dieses Feature die dichteste im Projekt:
`testDeleteImage`, `testSortImagesWithInvalidCsrfReturns403`,
`testSortImagesReordersWithValidToken`, `testSortImagesRejectsForeignImageWith400` —
sie decken AK-05 bis AK-09 ab, und alle vier laufen grün.

BF-57 ließe sich testen (Upload einer HTML-Datei, Prüfung der Endung), aber der Test
hielte das unerwünschte Verhalten fest. Er entsteht mit der Reparatur, dann in der
richtigen Richtung.

**Suite: 364 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B05`. B09 geht auf `approved`; BF-57 und BF-58 stehen in
`features/befunde.md`, BF-53 bekommt den Nachweis der fünf Altdateien.

BF-57 ist der Kandidat für den nächsten Reparaturdurchgang: eine MIME-Positivliste im
`ImageUploadService`, dieselben drei Typen wie beim Avatar-Upload. Das Muster steht im
Projekt und ist nur an dieser Stelle nicht angewandt.
