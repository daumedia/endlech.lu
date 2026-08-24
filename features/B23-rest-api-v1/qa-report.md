# B23 · REST-API v1 (iOS-Backend) — Testbericht

Stand: 2026-08-24 · **zweiter Durchlauf**, nach der Reparatur von BF-24 bis BF-28
Vorstufe: `building` · Branch `fix/b04-profil-qa` · Commit `e61c253`

## Fazit

**Production-ready: ja** — mit drei mittleren Befunden, von denen zwei durch die
Reparatur entstanden sind.

Beide *hoch*-Befunde des ersten Durchlaufs sind belegt geschlossen. Die Kette, die
diesen Bericht im ersten Durchlauf getragen hat — ein `POST` und der Eintrag steht in
der öffentlichen Liste, im CC-BY-Datensatz und in den Kennzahlen der Transparenzseite —
ist an allen fünf Stellen unterbrochen, jede einzeln nachgemessen.

Die drei verbleibenden Befunde teilen ein Muster, das benannt gehört: **Die Reparatur
hat den öffentlichen Schaden abgestellt, aber den Vektor nicht.** Was vorher in die
Restaurantliste lief, läuft jetzt in die Moderationsschlange — 40 Aufrufe erzeugten 40
Vorschläge, alle mit 202 angenommen. Das ist ein sehr viel besserer Ort dafür, aber
kein sicherer.

Dazu ein Nebenbefund, den ich beim Nachfassen gefunden habe und der ohne Client-Test
unentdeckt geblieben wäre: Die `id` in der 202-Antwort ist eine Vorschlags-ID, steht
aber im Rumpf eines Restaurant-Endpunkts. Wer sie an `GET /api/v1/restaurants/{id}`
weiterreicht, bekommt bei Überlappung der beiden Zähler **ein fremdes Restaurant mit
200** — nachgestellt.

Nächster Aufruf: **`/sdd-erfassen B19`**. Die Erfassung läuft weiter.

## Was seit dem ersten Durchlauf anders ist

| | erster Durchlauf | dieser |
|---|---|---|
| AK-21 Moderation | ✅ bestätigt (= der Befund) | **✅ repariert** — 0 Treffer an allen fünf öffentlichen Stellen |
| AK-22 Mailversand | ✅ bestätigt — 11 Mails an eine fremde Adresse | **✅ repariert** — 5 durch, ab dem 6. → 429, `Retry-After: 720` |
| AK-24 Kontaktdaten Dritter | ✅ bestätigt — öffentlich sichtbar | **✅ repariert** — nur noch im Vorschlag, den der Admin sieht |
| EC-06 Filterauswahl | ✅ bestätigt — von außen beschreibbar | **✅ repariert** — Küchen-Zähler bleibt bei 20 |
| AK-02 Formatvertrag | ❌ `{code,message}` | **✅ repariert** — alle sechs Fehlerformen mit `error`-Umschlag |
| AK-19 Fehlerformat | ❌ dito | **✅ repariert** |
| **AK-14** | ✅ bestanden (201 + Restaurant) | **❌ durchgefallen — gegen die Spec, nicht gegen den Code** |

## Die Korrektur an der Spezifikation

**AK-14 und AK-21 widersprechen sich seit der Reparatur.**

AK-14 lautet: *„… dann entsteht ein Restaurant mit `submittedBy` = Aufrufer und
`isVerified = false`, und die Antwort ist 201 mit der Detaildarstellung."*

AK-21 (⚠, *fragwürdiges Verhalten, zur Klärung vorgelegt*) beschrieb dasselbe Verhalten
und markierte es als das Problem. Die Klärung ist erfolgt, das Verhalten repariert —
AK-14 beschreibt damit einen Zustand, den niemand mehr will.

Gemessen:
```
POST /api/v1/restaurants {"name":"QA2 Lokal","city":"Strassen"}  →  HTTP 202
   Restaurant angelegt: NEIN
   Vorschlag angelegt:  2 status=pending
```

Das ist **kein Fehler im Code**, sondern eine überholte Rekonstruktion. AK-14 ist
in `spec.md` berichtigt worden, mit Vermerk, dass die alte Fassung dort stand — genau
wie bei B04/AK-13. Der Eintrag bleibt nachvollziehbar, damit niemand später den
Widerspruch für einen Fund hält.

## Akzeptanzkriterien im Einzelnen

### Repariert und nachgemessen

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-21** | ✅ bestanden | Website 0 · API-Liste 0 · `dataset.csv` 0 · `dataset.json` 0 · Startseite 0 · `/open.json` unverändert bei `restaurants=11, verifiedShare=27.3%, averageScore=5.09` (vorher: 13 / 23,1 % / 4,31) |
| **AK-22** | ✅ bestanden | `1:201 2:201 3:201 4:201 5:201 6:429 7:429 8:429`, **5 Mails** statt 11, `Retry-After: 720` |
| **AK-24** | ✅ bestanden | `vorstand@fremde-firma.lu` angelegt → 0 Treffer auf `/de/restaurants`, in `/api/v1/restaurants`, in `dataset.csv`; steht nur im Vorschlag |
| **EC-06** | ✅ bestanden | 50 Typen → 422 (Längengrenze); 3 kurze Typen → 202, Küchen-Zähler **20 → 20**, Vorschlag trägt `"Pizzza, Sushiii, XSSTest"` als Freitext |
| **AK-02** | ✅ bestanden | `{"error":{"code":401,"message":"Fehlerhafte Zugangsdaten."}}` |
| **AK-19** | ✅ bestanden | alle sechs Formen tragen den Umschlag — siehe Tabelle unten |
| **AK-28** | ✅ bestanden | `{"error":{"code":404,"message":"Nicht gefunden."}}` — keine Klassennamen mehr; Unit-Test `testAk28VerraetKeineInternenKlassennamenBei404` |
| BF-27 | ✅ bestanden | 200-Zeichen-Küche → **422** mit `violations.cuisines` (vorher 500) |

**Der Formatvertrag über alle Fehlerformen:**

| Fall | Antwort |
|---|---|
| falsches Passwort | `{"error":{"code":401,"message":"Fehlerhafte Zugangsdaten."}}` |
| kaputtes Token | `{"error":{"code":401,"message":"Das Token ist ungültig. …"}}` |
| fehlendes Token | `{"error":{"code":401,"message":"Authentifizierung erforderlich."}}` |
| 404 | `{"error":{"code":404,"message":"Nicht gefunden."}}` |
| kaputtes JSON | `{"error":{"code":400,"message":"Ungültiger JSON-Body."}}` |
| 429 | `{"error":{"code":429,"message":"Zu viele Anfragen. …"}}` |

### Regression — unverändert bestanden

| AK | Nachweis |
|---|---|
| AK-01 | Login liefert JWT |
| AK-03/04/05/06 | Registrierung: 201 generisch, wortgleich bei vorhandener Adresse, 422 mit `violations`, 400 bei kaputtem JSON |
| AK-07 | `GET /restaurants` ohne Token → **200** |
| AK-08/09 | `meta: {page:1, limit:50, total:11, totalPages:1, sort:'rating'}` bei `?limit=500` |
| AK-10 | `?sort=erfunden` → `meta.sort: rating` |
| AK-11/27 | `/me` ohne Token → 401 |
| AK-12/25 | Felder: `id, name, email, avatarUrl, roles, isVerified, createdAt` — `password` kommt nicht vor |
| AK-13 | `/me/submissions` → 3 Einträge, genau die mit `submittedBy` |
| **AK-15** | `latitude:"999"` → **422**; `49.6116/6.1319` → 202, im Vorschlag als `49.61160000 / 6.13190000` gespeichert |
| AK-16 | absolute URLs (siehe erster Durchlauf, unverändert) |
| AK-17/18 | beide Limits an der Grenze (siehe erster Durchlauf) |
| AK-20 | `/api/docs` → 200, 7 Pfade, `Bearer` |
| AK-23 | unverändert — die Hinweis-Mail verspricht weiter ein Passwort-Zurücksetzen, das es nicht gibt (BF-04 / Feature `01`) |
| AK-26/29 | Schlüssel nicht im Repository; CORS nur für erlaubte Herkünfte |
| **AK-14** | ❌ **durchgefallen gegen die Spec** — siehe Korrektur oben |
| EC-01…EC-05 | unverändert bestanden |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Moderation umgehbar?** | nein — an allen fünf öffentlichen Stellen 0 Treffer |
| **Filterauswahl beschreibbar?** | nein — Küchen-Zähler unverändert, Namen bleiben Freitext am Vorschlag |
| **Mailversand an Dritte** | gedeckelt bei 5/Stunde je IP |
| **Moderationsschlange flutbar?** | **ja** — 40 Aufrufe, 40 Vorschläge, alle 202 → BF-30 |
| **ID-Verwechslung** | **ja** — die `id` der 202 ist keine Restaurant-ID → BF-31 |
| **Reparatur zu weit gegriffen?** | nein — `findOrCreateByName()` bleibt in `AdminSuggestionController` und `CuisineApiController` (beide admin-only), verschwunden ist es nur aus dem öffentlich erreichbaren API-Pfad |
| **Personendaten in Antworten** | `password` und Token strukturell nicht vorhanden |
| **Testsuite** | 342 Tests, 1173 Assertions, 1 übersprungen, **0 Fehler** |

## Fehler

### BF-30 · Die Moderationsschlange lässt sich fluten — mittel

**Betrifft:** AK-21 (Folgezustand der Reparatur)

**Reproduktion:**
```python
for i in range(40):
    POST /api/v1/restaurants  {"name": f"Flut {i}", "city": "Luxembourg"}
```
**Erwartet:** eine Sperre nach wenigen Einreichungen
**Tatsächlich:** `{202: 40}` — Vorschläge in der Datenbank: **4 → 44**

**Ort:** `src/EventSubscriber/ApiRateLimitSubscriber.php:54–58` — der `match`-Ausdruck
kennt nur `auth/login` und `auth/register` als Sonderfälle; `POST /restaurants` fällt
unter `api_anonymous` mit **100 je Minute**.

**Warum das trotzdem ein Fortschritt ist und trotzdem ein Befund:** Vor der Reparatur
landeten dieselben 6.000 Einträge je Stunde in der öffentlichen Liste und im
CC-BY-Datensatz. Jetzt landen sie in `/de/admin/vorschlaege`. Der Schaden ist von
„falsche Daten sind veröffentlicht" auf „die Moderation ist unbenutzbar" gesunken — das
ist die richtige Richtung, aber die Warteschlange hat keine Sortierung, keine Filterung
nach Einreicher und kein Massenlöschen. Wer sie flutet, legt die Freigabe still, und
echte Vorschläge gehen darin unter.

**Vorschlag:** Ein eigener Limiter für `POST /api/v1/restaurants`, gezählt **am Konto**
statt an der IP — analog `password_change` aus B04. Fünf Einreichungen je Stunde sind
großzügig für einen echten Nutzer und wertlos für einen Angreifer. Das schließt zugleich
die Lücke, die FB-08 beschreibt (Limits nur je IP): Ein Angreifer mit wechselnden IPs
umgeht den IP-Deckel, das Konto nicht.

**Dies ist das vierte Auftreten von M-01** (nach BF-02 Registrierung, BF-13 Anmeldung,
BF-21 Adressänderung). Die Konvention, die dem Projekt fehlt, steht seit gestern in
`fehlbestand-uebersicht.md` — dieser Befund ist der erste Fall, in dem sie schon
formuliert war und trotzdem nicht angewandt wurde. Sie gehört an eine Stelle, die beim
Bauen gelesen wird, nicht nur beim Prüfen.

### BF-31 · Die `id` der 202-Antwort ist keine Restaurant-ID — mittel

**Betrifft:** der neue Antwortvertrag (in der Spec als OF-06 vermerkt, dort noch nicht beschrieben)

**Reproduktion:**
1. `POST /api/v1/restaurants` → `{"status":"pending","id":46,"message":"…"}`
2. `GET /api/v1/restaurants/46`

**Erwartet:** 404 — oder eine Kennung, die im selben Namensraum liegt wie der Endpunkt
**Tatsächlich:** Sobald ein Restaurant mit dieser ID existiert, liefert der Aufruf es
mit **200** aus:
```
GET /api/v1/restaurants/46
{"id":46,"name":"Fremdes Lokal","city":"Luxembourg","emoji":"X","rating":4.5,…}
```
(nachgestellt durch Anlegen eines Restaurants mit `id=46`; die beiden Tabellen haben
eigene `AUTO_INCREMENT`-Zähler und überlappen zwangsläufig — im Testbestand standen
Restaurant-IDs bei 215–225 und Vorschlags-IDs bei 2–46)

**Ort:** `RestaurantApiController::create()` gibt `$suggestion->getId()` unter dem
Schlüssel `id` zurück — im Rumpf eines Endpunkts, dessen andere Antworten alle
Restaurant-IDs führen.

**Folge:** Ein Client, der „Dein Vorschlag" anzeigen will und die ID naiv weiterreicht,
zeigt ein fremdes Lokal an. Kein Datenabfluss — die Daten sind ohnehin öffentlich —,
aber eine falsche Zuordnung, die wie ein Fehler des Nutzers aussieht.

**Vorschlag:** Den Schlüssel `suggestionId` nennen statt `id`. Ein Wort, und die
Verwechslung ist strukturell ausgeschlossen. Alternativ ganz weglassen, solange es
keinen Endpunkt gibt, der eine Vorschlags-ID auflöst — was zu BF-32 führt.

### BF-32 · Wer einreicht, sieht seinen Vorschlag nirgends — niedrig

**Betrifft:** AK-13 (Kriterium erfüllt, Erlebnis gebrochen)

**Reproduktion:** Nach vier Einreichungen über die API:
```
GET /api/v1/me/submissions
   Einträge: 3 → ['Umami Corner', 'Burger & Co.', 'Café Nordstad']
```
Das sind die drei aus den Fixtures. Die vier neuen Vorschläge fehlen.

**Ort:** `MeController::submissions()` liest `RestaurantRepository::findBySubmitter()` —
also genehmigte Restaurants. Ein Endpunkt für eigene Vorschläge existiert nicht.

AK-13 ist damit **nicht verletzt** (es verlangt „nur Restaurants, deren `submittedBy` der
Token-Inhaber ist" — genau das liefert es). Aber der Weg als Ganzes hat jetzt ein Loch:
Die API bestätigt eine Einreichung mit einer ID, und kein Endpunkt löst sie auf. Vor der
Reparatur erschien der Eintrag sofort unter `submissions` — das war der falsche Weg zum
richtigen Erlebnis.

**Vorschlag:** `submissions` um die offenen Vorschläge erweitern, mit `status`-Feld je
Eintrag. Das ist neue Funktion, keine Reparatur, und steht als OF-05 in der Spec.

## Hinweise ohne Fehlerstatus

- **BF-29 ist unverändert offen** und im Abschlussbericht der Reparatur auch so
  ausgewiesen: `trusted_hosts` hätte bei leerem Wert jeden Host abgewiesen. Der Weg über
  `APP_API_BASE_URL` steht in `.env` dokumentiert und ist eine **Serveraufgabe**. Er
  gehört auf die Deployment-Liste, sonst geht er unter.
- **Die API antwortet ohne `Accept-Language` luxemburgisch** — auch die neue
  202-Meldung („Merci! Däi Virschlag ass ukomm…"). Bewusst nicht geändert; steht als
  Hinweis in `CLAUDE.md`.
- **Die Reparatur hat die Antwortsprache in den Vertrag geholt.** Vorher trug die
  201-Antwort nur Daten, jetzt trägt die 202 einen übersetzten Satz. Damit ist die
  Locale-Frage nicht mehr nur kosmetisch — ein Client, der die Meldung anzeigt, zeigt
  Luxemburgisch, solange er den Header nicht setzt.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe. Alle Befunde stammen aus
  dem Angriffsdurchlauf.

## Nächster Schritt

`/sdd-erfassen B19`. B23 geht auf `approved`; die drei Befunde stehen in
`features/befunde.md`. Ausgeliefert ist weiterhin nichts — die Reparaturen von B01, B02,
B04 und B23 liegen zusammen auf dem Branch und warten auf `/sdd-deploy`.
