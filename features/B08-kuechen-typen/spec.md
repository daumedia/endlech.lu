# B08 · Küchen-Typen — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Küchentypen sind eine eigene Entität statt eines Freitextfelds: Sie lassen sich filtern,
als Abzeichen anzeigen und im Verwaltungsformular per Autovervollständigung zuordnen —
mit der Möglichkeit, unbekannte im selben Zug anzulegen.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B19 | rekonstruiert | die Schnittstelle ist Admin-only |

An B08 hängen: B05 (Filter), B06 (Abzeichen), B20 (Formular), B21 (Auflösung des
Freitexts), B23 (Anlage über die API).

## User Stories

- **US-01** · Als Admin möchte ich beim Bearbeiten eines Restaurants Küchen aus einer
  Liste wählen.
- **US-02** · Als Admin möchte ich eine fehlende Küche im selben Formular anlegen.
- **US-03** · Als Besucher möchte ich nach Küchentyp filtern.

## Nicht im Scope

- Umbenennen oder Löschen von Küchen — **existiert nicht**, siehe FB-01
- Übersetzung der Küchennamen — sie sind einsprachig gespeichert

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Admin bearbeitet ein Restaurant, wenn er ins Küchenfeld
  tippt, dann erscheinen passende Vorschläge über `GET /{locale}/api/cuisines/search?q=…`.
- **AK-02** · Angenommen, das Suchfeld ist leer, wenn die Vorschläge geladen werden,
  dann liefert die Schnittstelle **alle** Küchen alphabetisch.
- **AK-03** · Angenommen, ein Admin gibt einen unbekannten Namen ein und bestätigt, wenn
  der Aufruf durchläuft, dann entsteht über `POST /{locale}/api/cuisines` eine neue
  Küche und die Antwort ist **201** mit ID und Namen.
- **AK-04** · Angenommen, der Name ist leer, wenn angelegt werden soll, dann antwortet
  der Server mit **400** und `{"error": "Name is required"}`.
- **AK-05** · Angenommen, eine Küche mit demselben Namen existiert bereits, wenn sie
  erneut angelegt werden soll, dann wird die **bestehende** zurückgegeben — es entsteht
  keine Dublette (`findOrCreateByName()`).
- **AK-06** · Angenommen, ein Gast oder `ROLE_USER` ruft eine der beiden Routen auf, wenn
  die Anfrage durchläuft, dann greift `#[IsGranted('ROLE_ADMIN')]`.
- **AK-07** · Angenommen, ein Restaurant hat Küchen, wenn die Detailseite lädt, dann
  erscheinen sie als Abzeichen (`partials/_cuisine_badges.html.twig`).
- **AK-08** · Angenommen, `?cuisine[]=1&cuisine[]=2` steht in der Adresse der
  Restaurantliste, wenn sie lädt, dann erscheinen nur Restaurants mit **mindestens
  einer** dieser Küchen (ManyToMany-JOIN).
- **AK-09** · Angenommen, ein Restaurant wird gelöscht, wenn danach die Küchen
  betrachtet werden, dann bleiben sie bestehen — nur die Verknüpfungszeilen
  verschwinden.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-10** ⚠ · Angenommen, ein **beliebiger angemeldeter** Nutzer schickt
  `POST /api/v1/restaurants` mit einem `cuisines`-Eintrag, wenn die Anfrage durchläuft,
  dann wird über `findOrCreateByName()` ein neuer Küchentyp angelegt — **ohne**
  `ROLE_ADMIN`.
  *(So verhält sich der Code heute: `RestaurantApiController::applyOptionalData()` ruft
  dieselbe Methode wie die Admin-Schnittstelle. Die Rollenschranke auf
  `CuisineApiController` schützt damit nur den einen von zwei Wegen. Folge: Die
  Küchenliste ist über die öffentlich zugängliche API von jedem Konto beschreibbar; sie
  erscheint als Filterauswahl auf der öffentlichen Restaurantliste.)*

- **AK-11** ⚠ · Angenommen, ein Name mit mehr als 80 Zeichen wird angelegt, wenn der
  Aufruf durchläuft, dann wirft die Datenbankschicht — die Spalte ist `VARCHAR(80)`,
  eine Längenprüfung findet **nicht** statt.
  *(Über `CuisineApiController::create()` ergibt das einen 500er; über
  `RestaurantApiController` einen 500er, der von `ApiExceptionSubscriber` als
  „Interner Serverfehler." ausgeliefert wird. Vergleichbare Prüfungen gibt es dort für
  Koordinaten, für Namen nicht.)*

- **AK-12** ⚠ · Angenommen, eine Küche wird versehentlich angelegt, wenn sie wieder weg
  soll, dann gibt es **keinen Weg** dafür — weder Oberfläche noch Endpunkt.
  *(Die Liste kann nur wachsen. Ein Tippfehler aus B21 oder B23 bleibt dauerhaft in der
  öffentlichen Filterauswahl.)*

### Datenschutz und Missbrauchsschutz

- **AK-13** · Angenommen, ein Küchendatensatz wird betrachtet, wenn nach
  personenbezogenen Daten gesucht wird, dann enthält er **keine** — nur Name und Slug.
- **AK-14** · Angenommen, `/{locale}/api/cuisines/search` wird geprüft, wenn seine Lage
  betrachtet wird, dann liegt sie **unter** dem Sprachpräfix (real
  `/{_locale}/api/cuisines/search`) — anders als `/api/v1` ist dieser ältere
  Schnittstellenteil nicht sprachfrei.
- **AK-15** · Angenommen, die Anlage-Route wird geprüft, wenn nach CSRF gesucht wird,
  dann gibt es **keins** — es ist ein JSON-Endpunkt hinter `ROLE_ADMIN`.

## Edge Cases

- **EC-01** · `Cuisine::__toString()` liefert den Namen — nötig für Symfonys
  `EntityType`.
- **EC-02** · `Restaurant::getCuisineNames()` liefert die Namen kommagetrennt; das ist
  die Brücke zum Freitextfeld des Vorschlags (B11/B21).
- **EC-03** · `slug` ist `VARCHAR(100) UNIQUE` — länger als `name` (80), damit die
  Umwandlung Platz hat.
- **EC-04** · Der JoinTable heißt `restaurant_cuisine`, die Beziehung trägt
  `cascade: persist` — eine im Formular neu angelegte Küche wird mitgespeichert.

## Fehlbestand

- **FB-01 · Keine Verwaltung der Küchenliste.** Siehe AK-12: kein Umbenennen, kein
  Zusammenführen, kein Löschen.
- **FB-02 · Die Anlage ist nicht auf Admins beschränkt.** Siehe AK-10.
- **FB-03 · Keine Längen- oder Formatprüfung.** Siehe AK-11.
- **FB-04 · Keine Übersetzung.** Küchennamen werden einsprachig gespeichert und in
  allen vier Sprachfassungen unverändert angezeigt.
- **FB-05 · Kein Rate Limit auf `POST /api/cuisines`.** Hinter `ROLE_ADMIN`, deshalb
  nachrangig — der ungeschützte Weg ist ohnehin AK-10.

## Offene Fragen

- **OF-01** · Soll die API-Anlage neuer Küchen unterbunden werden (AK-10)? Eine
  Alternative wäre, in `RestaurantApiController` nur **bestehende** Küchen zuzuordnen
  und unbekannte zu verwerfen. — Betreiber
- **OF-02** · Braucht die Küchenliste eine Verwaltungsansicht (FB-01)? Sie wird mit
  jedem Tippfehler länger. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Entität statt Freitext | Entität | erlaubt Filter, Abzeichen und Wiederverwendung |
| 2 | Anlegen im selben Formular | Tom Select mit Create-Callback | ein Admin soll das Formular nicht verlassen müssen |
| 3 | `findOrCreateByName()` statt zwei Aufrufe | eine Methode | verhindert Dubletten an allen drei Aufrufstellen |
| 4 | Vorschlag erhebt Küchen als Freitext | ja | der Einreicher soll nicht aus einer Liste wählen müssen (B11) |
| 5 | Migration mit Datenübernahme | `Version20260323000000` | die alte `cuisine`-VARCHAR-Spalte wurde migriert und entfernt |
