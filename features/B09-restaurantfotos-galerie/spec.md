# B09 · Restaurantfotos & Galerie — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein Admin lädt Fotos zu einem Restaurant hoch, versieht sie mit einem Alternativtext,
ordnet sie per Ziehen und löscht sie wieder. Das erste Bild ist das Titelbild; die
übrigen bilden die Galerie auf der Detailseite, dort mit Lightbox.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B20 | rekonstruiert | Upload und Sortierung leben im Bearbeitungsformular |
| B19 | rekonstruiert | Rollenschranke |

## User Stories

- **US-01** · Als Admin möchte ich mehrere Fotos auf einmal hochladen.
- **US-02** · Als Admin möchte ich die Reihenfolge festlegen, weil das erste Bild das
  Titelbild ist.
- **US-03** · Als Besucher möchte ich Fotos vergrößert ansehen.
- **US-04** · Als blinder Besucher möchte ich einen Alternativtext vorfinden.

## Nicht im Scope

- Bilder über die REST-API hochladen → B23 kennt nur `GET /images`
- Bilder durch Nutzer oder Betreiber — nur `ROLE_ADMIN`

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Admin wählt im Bearbeitungsformular eine oder mehrere
  Dateien, wenn abgeschickt wird, dann liegen sie unter
  `public/uploads/restaurants/` und erscheinen in der Liste; die Erfolgsmeldung nennt
  die Anzahl.
- **AK-02** · Angenommen, keine gültige Datei war dabei, wenn abgeschickt wird, dann
  erscheint `flash.no_valid_files`.
- **AK-03** · Angenommen, ein Alternativtext wurde eingegeben, wenn das Bild gespeichert
  wird, dann steht er am Datensatz; **ohne** Eingabe wird der **Restaurantname**
  eingesetzt.
- **AK-04** · Angenommen, ein Bild wird hochgeladen, wenn seine Position geprüft wird,
  dann steht es hinten (`getNextSortOrder()`).
- **AK-05** · Angenommen, ein Admin ordnet die Bilder per Ziehen, wenn die Reihenfolge
  gespeichert wird, dann läuft das über einen JSON-POST mit den IDs in neuer Folge und
  antwortet `{"success": true}`.
- **AK-06** · Angenommen, eine der übermittelten Bild-IDs gehört zu einem **anderen**
  Restaurant, wenn sortiert wird, dann antwortet der Server mit **400** und ändert
  nichts.
- **AK-07** · Angenommen, das CSRF-Token beim Sortieren fehlt oder ist falsch, wenn der
  JSON-POST ankommt, dann antwortet der Server mit **403**.
- **AK-08** · Angenommen, ein Bild wird gelöscht, wenn danach nachgesehen wird, dann ist
  **die Datei vom Dateisystem entfernt**, der Datensatz weg und die Sortierung der
  übrigen lückenlos neu vergeben.
- **AK-09** · Angenommen, eine Bild-ID gehört nicht zum Restaurant im Pfad, wenn
  gelöscht werden soll, dann geschieht nichts und es erscheint `flash.photo_not_found`.
- **AK-10** · Angenommen, ein Restaurant wird gelöscht, wenn danach nachgesehen wird,
  dann sind seine Bilddatensätze mit gelöscht (`cascade`, `orphanRemoval`, FK
  `ON DELETE CASCADE`).
- **AK-11** · Angenommen, ein Restaurant hat Bilder, wenn die Detailseite lädt, dann ist
  das erste das Titelbild und die übrigen bilden die Galerie
  (`getCoverImage()`, `getGalleryImages()`).

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-12** ⚠ · Angenommen, ein Admin lädt eine **HTML-Datei** oder eine **SVG-Datei mit
  eingebettetem Skript** hoch, wenn sie gespeichert ist, dann liegt sie mit der Endung
  `.html` bzw. `.svg` unter `public/uploads/restaurants/` und wird vom Webserver
  **im Ursprung der Seite** ausgeliefert.
  *(So verhält sich der Code heute — nachgeprüft: `AdminRestaurantController::uploadImage()`
  liest die Dateien direkt aus `$request->files`, es ist **kein** Symfony-Formular und
  es greift **kein** `File`-Constraint. `ImageUploadService::upload()` prüft weder
  MIME-Typ noch Größe; einzige Prüfung ist `$file->isValid()`, das nur den
  PHP-Upload-Fehlercode betrachtet. Die Endung stammt aus `guessExtension()`, das den
  tatsächlichen Inhalt auswertet — für `text/html` liefert es `html`, für
  `image/svg+xml` liefert es `svg`. Das `accept="image/jpeg,image/png,image/webp"` im
  Markup ist eine Bequemlichkeit des Dateidialogs, keine Prüfung.
  Folge: gespeichertes Skript im eigenen Ursprung, also Zugriff auf die Sitzung jedes
  Besuchers, der die Datei öffnet.
  Nicht betroffen: PHP-Dateien. `text/x-php` kennt keine zugeordnete Endung,
  `guessExtension()` liefert dafür `null` und die Datei landete ohne Endung — eine
  Ausführung als PHP ist auf diesem Weg **nicht** möglich. Ein Polyglot
  `GIF89a<?php …>` wird als `image/gif` erkannt und als `.gif` abgelegt.
  Einschränkung der Reichweite: Der Weg setzt `ROLE_ADMIN` voraus.)*

- **AK-13** ⚠ · Angenommen, ein Admin lädt eine sehr große Datei hoch, wenn geprüft
  wird, was sie begrenzt, dann nur `upload_max_filesize` und `post_max_size` der
  PHP-Konfiguration — die Anwendung setzt **keine** Grenze.
  *(Zum Vergleich: Der Avatar-Upload (B04) begrenzt auf 2 MB, weil er über ein
  Formular mit `File`-Constraint läuft.)*

### Datenschutz und Missbrauchsschutz

- **AK-14** · Angenommen, ein Foto wird abgelegt, wenn sein Ort geprüft wird, dann liegt
  es unter `public/uploads/restaurants/` und ist damit **öffentlich abrufbar**, ohne
  Anmeldung.
- **AK-15** · Angenommen, ein Foto enthält Metadaten (Aufnahmeort, Kamera), wenn es
  gespeichert wird, dann bleiben sie **erhalten** — die Datei wird unverändert
  verschoben, nicht neu kodiert.
- **AK-16** · Angenommen, alle schreibenden Endpunkte werden geprüft, wenn nach CSRF
  gesucht wird, dann tragen alle drei ein eigenes Token (`upload-images-{id}`,
  `delete-image-{id}`, `sort-images-{id}`).
- **AK-17** · Angenommen, eine Bild-ID aus der Anfrage wird verarbeitet, wenn geprüft
  wird, ob sie zum Restaurant im Pfad gehört, dann geschieht das bei **Löschen und
  Sortieren** ausdrücklich (`$image->getRestaurant() === $restaurant`).

## Edge Cases

- **EC-01** · `uniqid('', true)` als Dateiname — zeitbasiert mit Zufallsanteil; eine
  Kollision ist praktisch ausgeschlossen, der Name aber nicht kryptografisch zufällig.
- **EC-02** · `getNextSortOrder()` und `reorderAfterDelete()` halten die Reihenfolge
  lückenlos; ohne Letzteres entstünden nach jedem Löschen Sprünge.
- **EC-03** · `sortImages()` bricht bei der ersten fremden ID ab — bereits gesetzte
  `sortOrder`-Werte der Schleife werden dann **nicht** geflusht, der Zustand bleibt
  unverändert.
- **EC-04** · Der Alternativtext gilt für **alle** Dateien eines Uploads gemeinsam; ein
  Text je Bild ist nicht vorgesehen.

## Fehlbestand

- **FB-01 · Keine serverseitige Typprüfung beim Upload.** Siehe AK-12. Der Avatar-Upload
  (B04) zeigt im selben Projekt, wie es ginge: ein `File`-Constraint mit `mimeTypes`.
- **FB-02 · Keine Größenbegrenzung.** Siehe AK-13.
- **FB-03 · Kein Schutz des Upload-Verzeichnisses.** Weder `.htaccess` unter
  `public/uploads/` noch eine Auslieferung über einen Controller. Das Verzeichnis liegt
  im Web-Root und wird direkt vom Webserver bedient.
- **FB-04 · Keine Neukodierung, keine Metadatenentfernung.** Siehe AK-15.
- **FB-05 · Keine Größenvarianten.** Ein 4-MB-Foto wird auf jeder Listenseite in voller
  Auflösung ausgeliefert.
- **FB-06 · Alternativtext ohne Zwang.** Fehlt er, wird der Restaurantname eingesetzt —
  für einen Screenreader ist „Pizzeria Bella Vista" bei fünf Bildern fünfmal dieselbe,
  nichtssagende Angabe. Auf einer Barrierefreiheitsplattform wiegt das besonders.

## Offene Fragen

- **OF-01** · Reicht es, den Upload durch ein Formular mit `File`-Constraint zu führen
  (FB-01), oder soll zusätzlich das Verzeichnis abgesichert werden (FB-03)? Beides
  zusammen wäre die vollständige Antwort. — Betreiber
- **OF-02** · Soll ein Alternativtext je Bild erzwungen werden (FB-06)? — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Upload ohne Symfony-Formular | direkt aus `$request->files` | erlaubt Mehrfachauswahl in einem Feld — der Preis ist der Verlust aller Constraints |
| 2 | Sortierung über JSON statt Formular | JSON-POST | Ziehen und Ablegen braucht ohnehin JavaScript |
| 3 | Zugehörigkeitsprüfung beim Sortieren und Löschen | ausdrücklich | die Bild-ID steht im Pfad bzw. im Body und ist fortlaufend |
| 4 | Alternativtext gemeinsam für alle Dateien | so | Grund nicht erkennbar |
| 5 | Rückfall auf den Restaurantnamen | statt leer | ein leeres `alt` wäre schlechter als ein ungenaues — aber nur knapp |
| 6 | Speicherort im Web-Root | `public/uploads/restaurants` | direkte Auslieferung ohne Controller |
