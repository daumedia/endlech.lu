# B20 · Restaurantverwaltung (Admin) — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Vollständiges CRUD über den Kernbestand der Anwendung: anlegen, bearbeiten, löschen,
verifizieren. Das Bearbeitungsformular deckt alle Felder ab — Barrierefreiheit, Maße,
Zahlung, Ernährung, Sprachen, Kontakt, Sozialkonten, Koordinaten, Öffnungszeiten,
Bestellwege und Küchen.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B19 | rekonstruiert | Rollenschranke und Shell |

An B20 hängen: B09 (Fotos im selben Formular), B07, B08 (Teilformulare).

## User Stories

- **US-01** · Als Betreiber möchte ich Restaurants anlegen und pflegen.
- **US-02** · Als Betreiber möchte ich ein Haus als geprüft kennzeichnen.
- **US-03** · Als Betreiber möchte ich nachvollziehen, wer wann verifiziert hat.

## Nicht im Scope

- Fotos → B09 · Öffnungszeiten → B07 · Küchen → B08 (alle im selben Formular, aber
  eigene Features)
- Anlegen durch Nutzer → B11 (Vorschlag) bzw. B23 (API)

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Admin ruft `/{locale}/admin/restaurants` auf, wenn die
  Seite lädt, dann erscheinen **alle** Restaurants absteigend nach Anlagedatum.
- **AK-02** · Angenommen, ein Admin legt ein Restaurant an, wenn das Formular gültig
  ist, dann wird es gespeichert und er landet auf der Liste mit
  `flash.restaurant_created`.
- **AK-03** · Angenommen, ein Formular ist ungültig, wenn abgeschickt wird, dann
  antwortet der Server mit **422** und zeigt das Formular mit Fehlern erneut.
- **AK-04** · Angenommen, ein Admin setzt beim Bearbeiten den Haken „verifiziert", wenn
  gespeichert wird, dann werden `verifiedAt` = jetzt und `verifiedBy` = der handelnde
  Admin gesetzt.
- **AK-05** · Angenommen, ein Admin nimmt den Haken zurück, wenn gespeichert wird, dann
  werden `verifiedAt` und `verifiedBy` auf `null` gesetzt.
- **AK-06** · Angenommen, der Verifizierungszustand bleibt beim Bearbeiten unverändert,
  wenn gespeichert wird, dann bleiben `verifiedAt` und `verifiedBy` **unangetastet** —
  ein Datum wird nicht bei jeder Änderung neu gesetzt.
- **AK-07** · Angenommen, ein Admin drückt den Verifizierungsknopf in der Liste, wenn
  das CSRF-Token `toggle-verified-{id}` stimmt, dann kippt der Zustand samt Datum und
  Prüfer, und es erscheint `flash.verification_granted` bzw. `…_revoked` mit dem Namen.
- **AK-08** · Angenommen, ein Admin löscht ein Restaurant, wenn das CSRF-Token
  `delete-restaurant-{id}` stimmt, dann verschwindet es samt Bildern, Öffnungszeiten und
  Bestellwegen (Kaskaden).
- **AK-09** · Angenommen, ein CSRF-Token fehlt oder ist falsch, wenn Verifizieren oder
  Löschen abgeschickt wird, dann geschieht **nichts** und es erscheint
  `flash.invalid_csrf`.
- **AK-10** · Angenommen, eine nicht existierende ID steht im Pfad, wenn die Anfrage
  durchläuft, dann antwortet der Server mit 404 (`ParamConverter`, Requirement `\d+`).
- **AK-11** · Angenommen, ein Gast oder `ROLE_USER` ruft eine dieser Routen auf, wenn die
  Anfrage durchläuft, dann greift die Rollenschranke.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-12** ⚠ · Angenommen, die Liste lädt, wenn die Zahl der Restaurants wächst, dann
  werden **alle** geladen — `findBy([], ['createdAt' => 'DESC'])` ohne Grenze.
  *(Die öffentliche Liste (B05) blättert zu sechs, die API (B23) deckelt bei 50. Der
  Verwaltungsbereich, der als einziger jeden Datensatz samt Bildern rendert, tut es
  nicht.)*

- **AK-13** ⚠ · Angenommen, ein Restaurant wird gelöscht, wenn danach das
  Dateisystem betrachtet wird, dann liegen seine **Bilddateien weiterhin** unter
  `public/uploads/restaurants/`.
  *(So verhält sich der Code heute: `AdminRestaurantController::delete()` ruft
  `$entityManager->remove($restaurant)`. Die Kaskade entfernt die `RestaurantImage`-Zeilen,
  aber `ImageUploadService::delete()` — die einzige Stelle, die `unlink()` aufruft —
  wird dabei **nicht** durchlaufen. Folge: verwaiste Dateien, die niemand mehr
  zuordnen kann und die bei jedem Deploy überleben.)*

- **AK-14** ⚠ · Angenommen, ein Restaurant wird gelöscht, wenn eine Wartelisten-Anmeldung
  darauf verweist, dann wird die Verknüpfung auf `NULL` gesetzt und der Admin erfährt
  nichts davon.
  *(`PartnerWaitlistEntry.restaurant_id` ist `ON DELETE SET NULL`. Keine Rückfrage,
  kein Hinweis.)*

### Datenschutz und Missbrauchsschutz

- **AK-15** · Angenommen, ein Restaurant wird gepflegt, wenn nach personenbezogenen
  Daten gesucht wird, dann sind es Geschäftskontaktdaten (Telefon, E-Mail, Website,
  Sozialkonten) sowie zwei Verweise auf Nutzer: `verifiedBy` und `submittedBy`.
- **AK-16** · Angenommen, ein Admin-Konto wird gelöscht, wenn ein von ihm verifiziertes
  Restaurant betrachtet wird, dann steht `verifiedBy` auf `NULL` — die Verifizierung
  bleibt, der Prüfer ist weg.
- **AK-17** · Angenommen, alle schreibenden Endpunkte werden geprüft, wenn nach CSRF
  gesucht wird, dann tragen `toggleVerified` und `delete` eigene Token; `new` und
  `edit` sind Symfony-Formulare.
- **AK-18** · Angenommen, ein Restaurant wird angelegt, wenn geprüft wird, ob es sofort
  öffentlich ist, dann ja — es gibt keine Entwurfsstufe.

## Edge Cases

- **EC-01** · Das CSRF-Token für `toggle-verified-{id}` ist **session-basiert** (eigene
  Token-ID), nicht der stateless `submit`-Token. In Tests muss es als gerendertes
  Hidden-Feld mitgesendet werden.
- **EC-02** · `edit()` merkt sich `$wasVerified` **vor** `handleRequest()` — sonst wäre
  der alte Zustand bereits überschrieben.
- **EC-03** · `RestaurantType` ist der umfangreichste FormType des Projekts und bindet
  drei CollectionTypes ein (Öffnungszeiten, Bestellwege) sowie den EntityType für
  Küchen.

## Fehlbestand

- **FB-01 · Keine Bereinigung der Bilddateien beim Löschen.** Siehe AK-13.
- **FB-02 · Kein Seitenblättern und keine Suche.** Siehe AK-12.
- **FB-03 · Keine Sicherheitsabfrage im Server.** Das Löschen verlässt sich auf ein
  `confirm()` im Browser; ohne JavaScript entfällt die Rückfrage ersatzlos.
- **FB-04 · Kein Audit-Log.** `verifiedBy` ist die einzige Spur im ganzen Projekt —
  wer Felder geändert oder gelöscht hat, ist nicht rekonstruierbar (B19/FB-02).
- **FB-05 · Kein Papierkorb.** Löschen ist endgültig, auch für einen Datensatz mit
  Fotos, Öffnungszeiten und Verifikationshistorie.
- **FB-06 · Keine Rate-Limitierung.** (B19/FB-05)

## Offene Fragen


- **OF-neu** · Soll beim Löschen eines Restaurants ein Hinweis erscheinen, wenn eine
  Wartelisten-Anmeldung darauf verweist (AK-14)? Die Kaskade `SET NULL` ist richtig —
  der Eintrag soll erhalten bleiben —, aber der Admin erfährt nichts davon
  (2026-08-24 gemessen). — Betreiber
- **OF-neu2** · Soll der Restaurantname im Admin eine Mindestlänge haben? Ein Name mit
  einem Zeichen geht durch; Vorschlags-Wizard und API verlangen 2–150. — Betreiber
- **OF-01** · Sollen Bilddateien beim Löschen des Restaurants mit entfernt werden
  (FB-01)? Ein `PreRemove`-Callback oder ein Aufruf von `reorderAfterDelete`-Verwandtem
  wäre der Weg. Vorher zu klären: Wie viele verwaiste Dateien liegen bereits auf
  Produktion? — Betreiber
- **OF-02** · Ab welcher Größe wird das Fehlen des Seitenblätterns spürbar (AK-12)? —
  Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Verifikationslogik im Controller | statt in der Entity | sie braucht den handelnden Nutzer, den die Entity nicht kennt |
| 2 | Zustandsvergleich vor `handleRequest()` | ja | sonst wäre der alte Wert schon überschrieben |
| 3 | Zwei Wege zum Verifizieren | Formularhaken **und** Listenknopf | der Knopf spart das Öffnen des Formulars |
| 4 | Eigene CSRF-Token-IDs je Aktion und ID | ja | ein Token aus einer Zeile taugt nicht für eine andere |
| 5 | Keine Entwurfsstufe | so | `isVerified` ist ein Gütesiegel, kein Sichtbarkeitsschalter |
