# B09 · Restaurantfotos & Galerie — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Drei Endpunkte am `AdminRestaurantController`, ein Service, eine Entity, ein
Stimulus-Controller. Der Upload umgeht bewusst das Formularsystem, um Mehrfachauswahl
in einem Feld zu erlauben — und verliert damit die Validierungsschicht, die der
Avatar-Upload (B04) hat.

## Seiten und Routen

Alle `ROLE_ADMIN`, alle `POST`, alle mit eigenem CSRF-Token.

| Route | Pfad | Antwort |
|---|---|---|
| `admin_restaurant_image_upload` | `/restaurants/{id}/fotos` | Redirect auf `edit` |
| `admin_restaurant_image_delete` | `/restaurants/{id}/fotos/{imageId}/loeschen` | Redirect auf `edit` |
| `admin_restaurant_image_sort` | `/restaurants/{id}/fotos/sortieren` | **JSON** |

Anzeige: `app_restaurant_show` (B06).

## Komponentenstruktur

```
admin/restaurant/edit.html.twig
├── <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
│   └── accept ist eine Bequemlichkeit des Dateidialogs, KEINE Prüfung
├── Bildliste mit data-controller="image-sort"
│   └── image_sort_controller.ts → fetch(JSON) an admin_restaurant_image_sort
└── je Bild ein Löschformular mit CSRF delete-image-{id}

restaurant/show.html.twig
├── getCoverImage()      erstes Bild
└── getGalleryImages()   alle übrigen, GLightbox
```

## Datenmodell

### Tabelle `restaurant_image`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT | ja | |
| `filename` | VARCHAR(255) | ja | reiner Dateiname, kein Pfad |
| `alt_text` | VARCHAR(255) | nein | Rückfall: Restaurantname |
| `restaurant_id` | FK → `restaurant`, **CASCADE DELETE** | ja | |
| `uploaded_at` | DATETIME | ja | |
| `sort_order` | INT, Vorgabe 0 | ja | lückenlos gehalten |

Collection auf `Restaurant`: `$images` (OneToMany, `cascade: persist, remove`,
`orphanRemoval: true`, `OrderBy sortOrder ASC`).

Helper: `Restaurant::getCoverImage()`, `getGalleryImages()`.
Migration: `Version20260308110000` (Basis), Sortierung später ergänzt.

### Dateisystem

`public/uploads/restaurants/` — gitignoriert bis auf `.gitkeep`, im Web-Root, ohne
`.htaccess`. ⚠ `git clean -fd` im Deploy läuft ohne `-x`, die Dateien überleben also.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| jeder | jede Bilddatei, deren URL er kennt | — | Webserver, keine Prüfung |
| `ROLE_ADMIN` | Verwaltungsansicht | hochladen, sortieren, löschen | `access_control` + `#[IsGranted]` |
| `ROLE_ADMIN` | — | **nur Bilder des Restaurants im Pfad** | `$image->getRestaurant() === $restaurant` in `deleteImage()` und `sortImages()` |

## Missbrauchsschutz

| Aspekt | Vorhanden | Fehlt |
|---|---|---|
| CSRF | drei eigene Token | — |
| Zugehörigkeit | geprüft bei Löschen und Sortieren | — |
| **Dateityp** | — | serverseitige Prüfung (FB-01) |
| **Dateigröße** | nur PHP-Konfiguration | Anwendungsgrenze (FB-02) |
| Verzeichnisschutz | — | `.htaccess` oder Auslieferung über Controller (FB-03) |
| Rate Limit | — | (B19/FB-05) |

## Externe Dienste

Keine. GLightbox liegt als npm-Abhängigkeit lokal.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `uploadImage()`, `ImageUploadService::upload()` |
| AK-02 | `if ($uploaded > 0)` / `else` |
| AK-03 | `setAltText($altText ?: $restaurant->getName())` |
| AK-04 | `RestaurantImageRepository::getNextSortOrder()` |
| AK-05 | `sortImages()`, `image_sort_controller.ts` |
| AK-06 | `if (!$image \|\| $image->getRestaurant() !== $restaurant)` → 400 |
| AK-07 | `isCsrfTokenValid('sort-images-' . $id, $data['_token'] ?? '')` → 403 |
| AK-08 | `ImageUploadService::delete()` + `reorderAfterDelete()` |
| AK-09 | Zugehörigkeitsprüfung in `deleteImage()` |
| AK-10 | `cascade: remove`, `orphanRemoval`, FK `ON DELETE CASCADE` |
| AK-11 | `getCoverImage()`, `getGalleryImages()` |
| AK-12 ⚠ | **Abwesenheit** jeder Typprüfung; `guessExtension()` | Lücke, FB-01 |
| AK-13 ⚠ | **Abwesenheit** einer Größengrenze | Lücke, FB-02 |
| AK-14 | Speicherort im Web-Root |
| AK-15 | `$file->move()` ohne Neukodierung |
| AK-16 | drei `isCsrfTokenValid()`-Aufrufe |
| AK-17 | zwei Zugehörigkeitsprüfungen |

## Für `sdd-qa` besonders zu prüfen

1. **AK-12** — als Admin eine `.html`- und eine `.svg`-Datei hochladen und die abgelegte
   Datei im Browser öffnen. Der Nachweis ist die ausgeführte Skriptzeile im Ursprung der
   Seite. Bitte auch gegenprüfen, dass `.php` **nicht** funktioniert — die Rekonstruktion
   behauptet das ausdrücklich und sollte widerlegbar sein.
2. **AK-13** — eine sehr große Datei hochladen und beobachten, wo die Grenze liegt.
3. **EC-03** — beim Sortieren eine fremde ID in die Mitte der Liste setzen und prüfen,
   ob wirklich nichts gespeichert wurde.
