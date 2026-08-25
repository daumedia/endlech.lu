# B24 · Mehrsprachigkeit — Testbericht

Stand: 2026-08-25 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: nein** — ein Befund vom Grad *hoch*. **Die Erfassung pausiert.**

16 von 16 Kriterien bestätigt, alle vier Edge Cases bestanden. Die Grundlage ist solide:
57 Routen mit identischem Sprach-Requirement, vier Kataloge mit **exakt derselben
Schlüsselmenge** (1084 + 82), Zahlen und Beträge in landestypischer Schreibweise.

**Kein Kriterium ist durchgefallen — und trotzdem steht hier ein *hoch*.** Die
Rekonstruktion hat das Verhalten richtig beschrieben; sie hat den Schaden falsch
eingeschätzt. AK-12 nennt die Merge-Reihenfolge im Sprachumschalter *„harmlos, aber
leicht zu beheben"*. Gemessen ist sie ein **Open Redirect**:

```
Aufruf:  /de/restaurants?_locale=//qa-fremde-domain.example/de
Knopf:   "LB"   href="///qa-fremde-domain.example/de/restaurants"
Browser: navigiert nach  http://qa-fremde-domain.example/de/restaurants
```

Dazu, mit anderen Werten desselben Parameters: **HTTP 500 auf zehn von zehn geprüften
öffentlichen Seiten**.

Nächster Aufruf: **`/sdd-build B24`** mit BF-68. B24–B26 und Feature `01` warten, bis
das repariert und geprüft ist.

## Akzeptanzkriterien im Einzelnen

### Routen und Auflösung

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-01** | ✅ bestanden | **57** Routen mit `/{_locale}`; Requirement bei **allen 57** identisch `lb\|de\|fr\|en`, keine Abweichung. Die 34 sprachfreien sind genau die dokumentierten: `/api/v1/*`, `/open*`, `/passkey/*`, `/api/docs`, `app_root` |
| AK-02 | ✅ bestanden | `debug:router app_restaurant_index`: `Defaults → _locale: lb` |
| **AK-03** | ✅ bestanden | im Browser gemessen: `/de/restaurants?sort=name&wheelchair=1` → Klick auf FR → `/fr/restaurants?sort=name&wheelchair=1`, `html lang="fr"`, Überschrift *„Restaurants au Luxembourg"*. Auf der Detailseite bleibt zusätzlich der Routenparameter: `/lb/restaurants/410?foo=bar&sort=name` |
| **AK-16** | ✅ bestanden | `lb/de/fr/en` → 200; `es`, `xx`, `LB`, `de-DE` im **Pfad** → **404** |

### Umschalter

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-04** | ✅ bestanden | in allen vier Fassungen ist die aktuelle Sprache ein `<span>` und die übrigen drei sind `<a>` — `lb→Span LB`, `de→Span DE`, `fr→Span FR`, `en→Span EN` |
| **AK-05** | ✅ bestanden | **im Browser, per Tastatur**: Fokus auf den Knopf, `Enter` → `aria-expanded` `false → true`, Menü sichtbar, Pfeilklasse `rotate-180` ergänzt. Zweiter Klick → `false`, Menü zu, Klasse weg. Klick daneben schließt ebenfalls |

### Dokumentkopf

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-06** | ✅ bestanden | fünf `<link rel="alternate">` je Seite: `lb`, `de`, `fr`, `en` und `x-default` → `/lb/…`. Auf der Detailseite mit Routenparameter: `…/restaurants/410` in allen fünf |
| AK-07 | ✅ bestanden | die hreflang-Schleife läuft nur bei gesetzter Route: gültige Seite **5** Treffer, 404-Seite **0**. Zum `app_root`-Teil siehe Hinweise |
| AK-08 | ✅ bestanden | `<html lang="lb">` / `"de"` / `"fr"` / `"en"` — jede Fassung trägt ihre Sprache |

### Kataloge und Formate

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-09** ⚠ | ✅ bestätigt, **und es passiert** | 11 im Code verwendete Schlüssel fehlen in **allen vier** Katalogen. Live: der Abbrechen-Knopf auf `/{lb,de,fr,en}/admin/finanzen/neu` heißt **`admin.restaurant.cancel`**; ein Vorschlag mit ungültiger E-Mail zeigt **`suggestion.email_invalid`** → BF-69 |
| **AK-10** | ✅ bestanden | `lb/de: 1.322,70 €` · `fr: 1 322,70 €` (schmales geschütztes Leerzeichen) · `en: €1,322.70`; Prozent `27,3 %` gegen `27.3 %` |
| **AK-11** | ✅ bestanden | `suggestion.answer_required` in vier Fassungen: *„Bitte wähle Ja, Nein oder Weiß nicht."* / *„Merci de choisir Oui, Non ou Je ne sais pas."* / *„Please choose Yes, No or Don't know."* / *„Wiel w.e.g. Jo, Nee oder Weess net."* |
| **Vollständigkeit** | ✅ **exakt gleich** | `messages`: 1084 Schlüssel in jeder der vier Sprachen, **0** Lücken. `validators`: 82, ebenfalls **0**. Dafür gibt es jetzt einen Test |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-15** | ✅ bestanden | keine der vier Fassungen setzt ein sprachbezogenes Cookie. Gesetzt werden nur `PHPSESSID` und die CSRF-/Auth-Profil-Token — in jeder Sprache dieselben |

### Fragwürdiges Verhalten

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-12** ⚠ | ✅ bestätigt — **und die Einschätzung der Spec ist widerlegt** | siehe BF-68. Nicht „harmlos": Open Redirect plus 500er |
| **AK-13** ⚠ | ✅ bestätigt | bei 390 px: Umschalter **im DOM vorhanden**, `isVisible() = false`, **null** sichtbare Sprachlinks auf der ganzen Seite. Bottom-Nav: `["Home","Restaurants","Über uns","Login"]` → BF-72 |
| **AK-14** ⚠ | ✅ bestätigt | `/` mit `Accept-Language: fr-FR` / `de-DE` / `en-US` / ohne Header → **viermal** `Location: /lb/` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| **EC-01** | ✅ bestanden | `/api/v1/restaurants`, `/open.json`, `/open/dataset.csv`, `/open/dataset.json` → **200**; dieselben mit `/de/` davor → **404**. Der `exclude` ist eine Liste mit beiden Einträgen |
| EC-02 | ✅ bestanden | `api_cuisine_search` und `api_cuisine_create` liegen weiterhin unter `/{_locale}/api/cuisines` |
| EC-03 | ✅ bestanden | alle vier Passkey-Routen sprachfrei (`/passkey/…`, `/.well-known/webauthn`) |
| **EC-04** | ✅ bestanden | `Pizzeria Bella Vista` und `Italienisch` stehen in allen vier Fassungen unverändert — siehe aber BF-70 |

## Fehler

### BF-68 · Ein Query-Parameter kapert den Sprachumschalter — hoch

**Betrifft:** AK-12 · **Ort:** `templates/partials/_language_switcher.html.twig:5` und `:36`

```twig
{% set query_params = app.request.query.all() %}
...
href="{{ path(current_route, current_params|merge({'_locale': locale})|merge(query_params)) }}"
```

Der `merge` der Query-Parameter steht **hinter** dem der Sprache und gewinnt. Ein
Parameter, der zufällig `_locale` heißt, wird damit zum Routenparameter — und den
schreibt jeder Besucher selbst in die Adresse.

**Drei Wirkungen, alle gemessen.**

**1 · Open Redirect.** Der schwerste Teil.

```
Aufruf:   /de/restaurants?_locale=//qa-fremde-domain.example/de
Knopf:    "LB"   href="///qa-fremde-domain.example/de/restaurants"
Browser:  http://qa-fremde-domain.example/de/restaurants
```

Im echten Browser (Chromium) gemessen; die WHATWG-Auflösung von `///host/…` gegen eine
`http:`-Basis ergibt einen **fremden Host**, in Chrome, Safari und Firefox gleichermaßen.

Der Ablauf, den das erlaubt:

1. Der Angreifer verschickt `https://endlech.lu/de/restaurants?_locale=//phishing.example/de`
2. Das Opfer sieht die **echte** Seite unter der **echten** Domain — nichts wirkt falsch
3. Es klickt den Sprachumschalter und landet auf `phishing.example`
4. Dort steht eine nachgebaute Anmeldeseite

Der Link kommt von der richtigen Domain, das Opfer hat die richtige Seite gesehen, und
die Umleitung geschieht durch eine eigene Handlung. Genau das macht diese Klasse von
Fehlern brauchbar.

**2 · HTTP 500 auf jeder öffentlichen Seite.** Ein Wert, der das Requirement verfehlt,
wirft beim Rendern:

| Seite | ohne | mit `?_locale=xx` |
|---|---|---|
| `/de/` | 200 | **500** |
| `/de/restaurants` | 200 | **500** |
| `/de/restaurants/410` | 200 | **500** |
| `/de/login` | 200 | **500** |
| `/de/register` | 200 | **500** |
| `/de/open` | 200 | **500** |
| `/de/partner` | 200 | **500** |
| `/de/organisationen` | 200 | **500** |
| `/de/about` | 200 | **500** |
| `/de/legal` | 200 | **500** |

> `Parameter "_locale" for route "app_restaurant_index" must match "lb|de|fr|en"
> ("xx" given) to generate a corresponding URL.`

`strict_requirements: true` gilt auch in `prod` (nachgesehen: `debug:config framework
router`). Und `sentry.yaml` filtert `ignore_exceptions` nur 404/405/403/429 — **ein 500er
geht durch**. Ein Skript mit ein paar tausend Aufrufen räumt damit die Sentry-Quota leer,
und danach sieht niemand mehr die echten Fehler.

**3 · Der Umschalter wechselt nicht mehr.** Mit einem *gültigen* Wert:

| Aufruf | Umschalter-Links |
|---|---|
| `/de/restaurants` | `/lb/…`, `/fr/…`, `/en/…` |
| `/de/restaurants?_locale=en` | **dreimal** `/en/restaurants` |
| `/de/restaurants?_locale=de` | **dreimal** `/de/restaurants` (die aktuelle Seite) |

**Warum manche Werte durchgehen und andere werfen.** Symfony prüft mit
`'#^'.$requirement.'$#i'` — **ohne Gruppierung und case-insensitiv**. Aus
`lb|de|fr|en` wird damit `(^lb) | (de) | (fr) | (en$)`:

```
"de"     → 1     "DE"    → 1 (i-Flag)     "lbb"  → 1 (^lb)
"de-DE"  → 1     " de"   → 1              "delb" → 1 (de irgendwo)
"xx"     → 0     "l"     → 0              "en'"  → 0   → InvalidParameterException → 500
```

Deshalb erzeugt `?_locale=DE` eine **200**-Seite, deren Umschalter auf `/DE/restaurants`
zeigt — eine Adresse, die 404 liefert.

**Kein XSS und keine Header-Injection.** Beides geprüft: `de"><script>` wird zu
`de%22%3E%3Cscript%3E`, `de%0d%0aX` zu `de%250d%250aX`. Twigs `path()` und das
HTML-Escaping halten.

**Nur diese eine Stelle.** `grep` nach `query.all()` über `templates/` und `src/` findet
genau einen Treffer. Der Admin-Umschalter nutzt `path('admin_set_locale', {'locale':
locale})` — feste Route, kein Merge, nicht betroffen.

**Vorschlag:** Die Reihenfolge tauschen **und** die Sprache aus dem Query fernhalten:

```twig
{% set query_params = app.request.query.all()|filter((v, k) => k != '_locale') %}
...
href="{{ path(current_route, current_params|merge(query_params)|merge({'_locale': locale})) }}"
```

Der Tausch allein reicht nicht: Er repariert den Umschalter, lässt aber `_locale=xx` als
Query-Parameter stehen, und die nächste Stelle, die Query-Parameter durchreicht, hätte
dasselbe Problem. Der `filter` nimmt die Ursache weg.

Dazu ein Funktionstest je Wirkung: `?_locale=//fremd.example/de` darf keinen Link auf
einen fremden Host erzeugen, `?_locale=xx` muss **200** liefern.

### BF-69 · Elf Übersetzungsschlüssel stehen als Text auf der Seite — mittel

**Betrifft:** AK-09, FB-04

`debug:translation` meldet in **allen vier** Sprachen dieselben elf Schlüssel als
`missing` — sie werden im Code verwendet und sind in keinem Katalog definiert:

| Domäne | Schlüssel |
|---|---|
| `validators` | `suggestion.phone_max`, `suggestion.email_invalid`, `suggestion.email_max`, `suggestion.url_invalid`, `suggestion.url_max`, `restaurant.latitude_range`, `restaurant.longitude_range`, `restaurant.nearby_stops_note_max` |
| `messages` | `admin.restaurant.edit`, `admin.restaurant.delete`, `admin.restaurant.cancel` |

**Zwei davon live nachgestellt.**

Ein Vorschlag mit ungültiger E-Mail und ungültiger Adresse (`POST /de/community/suggest`
und `/fr/…`, beide **422**):

```
rohe Schlüssel in der Antwort: ['suggestion.email_invalid', 'suggestion.url_invalid']
```

Der Abbrechen-Knopf auf `/admin/finanzen/neu`, in **allen vier** Sprachen:

```html
<a href="/de/admin/finanzen" class="text-gray-500 hover:text-gray-700 font-medium">
    admin.restaurant.cancel
</a>
```

**Warum das mehr als Kosmetik ist:** Der Vorschlags-Assistent ist der Weg, auf dem diese
Plattform wächst. Wer dort einen Tippfehler in der E-Mail hat, liest
`suggestion.email_invalid` und weiß nicht, welches Feld gemeint ist oder was daran falsch
war. Ein Formular mit zwölf Pflichtfragen verzeiht das nicht.

Der Rest der Kataloge ist dagegen **vorbildlich**: 1084 Schlüssel in `messages` und 82 in
`validators`, in jeder der vier Sprachen dieselben, keine einzige Lücke.

**Vorschlag:** Die elf Schlüssel ergänzen. Dazu `debug:translation --only-missing` in
`ci.yml` — das ist der Grund, warum sie so lange stehen konnten.

### BF-70 · Barrierefreiheitshinweise stehen in allen Sprachen auf Deutsch — mittel

**Betrifft:** EC-04, FB-05

Die strukturierten Merkmale werden übersetzt. Die **Freitexte daneben nicht** — und
genau sie tragen die konkrete Auskunft:

| | de | fr | en |
|---|---|---|---|
| Merkmal | Rollstuhlgerecht | Accessible en fauteuil roulant | Wheelchair Accessible |
| Merkmal | Barrierefreies WC | Toilettes accessibles | Accessible Toilet |
| **Notiz** | Eingang stufenlos | **Eingang stufenlos** | **Eingang stufenlos** |
| **Notiz** | WC Tür > 90cm | **WC Tür > 90cm** | **WC Tür > 90cm** |

Beim Wäinhaus am Markt (`/…/restaurants/417`) sind es **Warnungen**:

```
de → ['Kopfsteinpflaster vor dem Eingang', 'Treppen im Inneren']
fr → ['Kopfsteinpflaster vor dem Eingang', 'Treppen im Inneren']
en → ['Kopfsteinpflaster vor dem Eingang', 'Treppen im Inneren']
```

Dazu die Filterauswahl der Restaurantliste: **zwanzig** Küchentypen, in allen vier
Fassungen deutsch — `Amerikanisch`, `Chinesisch`, `Französisch`, `Italienisch`,
`Japanisch`, `Portugiesisch`, `Vegetarisch` … Wer auf `/fr/restaurants` nach *italienne*
sucht, findet *Italienisch*.

**Warum die Notizen schwerer wiegen als die Küchentypen:** „Treppen im Inneren" ist der
Satz, wegen dem jemand **nicht** hinfährt. Ein frankophoner Rollstuhlfahrer — in
Luxemburg keine Randgruppe, Französisch ist Verwaltungssprache — liest auf der
französischen Seite genau die eine Angabe nicht, für die er gekommen ist. Und nichts auf
der Seite sagt ihm, dass dort eine fremde Sprache steht.

Die Spec führt das unter *Nicht im Scope* und nennt in FB-05 die Küchentypen als
naheliegenden Fall. Sie sieht nicht, dass dieselbe Struktur die
Barrierefreiheitshinweise betrifft.

**Vorschlag, gestuft:**
1. **Sofort und billig:** Die Notizen mit `lang="de"` auszeichnen. Dann sagt zumindest
   der Screenreader die richtige Sprache an, statt Deutsch französisch vorzulesen. Eine
   Zeile im Template, kein Datenmodell.
2. **Danach:** Küchentypen übersetzbar machen — das ist OF-02 und berührt B08, B05, B06,
   B17, B23.
3. **Der eigentliche Punkt:** Die Freitextnotizen sind pro Restaurant erfasst und werden
   nie so vollständig übersetzt sein wie ein Katalog. Naheliegender wäre, die häufigsten
   („stufenloser Eingang", „Treppen im Inneren", „Kopfsteinpflaster") zu strukturierten
   Merkmalen zu machen — dann übersetzen sie sich von selbst, und sie werden zugleich
   filterbar.

### BF-71 · Escape schließt den Sprachumschalter nicht — niedrig

**Betrifft:** AK-05

Im Browser gemessen:

```
nach 3. Klick (öffnet)   aria-expanded=true · Menü sichtbar=true
nach Escape              aria-expanded=true · Menü sichtbar=true
nach Klick daneben       aria-expanded=false · Menü sichtbar=false
```

`language_switcher_controller.ts` kennt `toggle` und `close`, aber keinen
`keydown`-Handler. `close` hängt an `click@window` — eine Maushandlung.

**Die Wirkung ist begrenzt**, und das gehört dazu: Nach dem Öffnen führt `Tab` in die
Liste (gemessen: Fokus auf `A LB`), die Sprachwahl ist also per Tastatur erreichbar. Was
fehlt, ist der Rückweg — ein Menü mit `aria-haspopup="true"` zu öffnen und es ohne Maus
nicht mehr schließen zu können, widerspricht den ARIA Authoring Practices.

**Vorschlag:** `data-action="keydown.esc->language-switcher#closeMenu"` auf dem
Wurzelelement; Stimulus bringt den `.esc`-Filter mit. Der Fokus gehört danach zurück auf
den Knopf. Beide Umschalter teilen sich den Controller — die Reparatur wirkt auch im
Admin.

### BF-72 · Auf dem Telefon gibt es nur eine Sprache — mittel

**Betrifft:** AK-13, FB-01

Bei 390 px Breite gemessen:

```
Umschalter im DOM      1
Umschalter sichtbar    false
sichtbare Sprachlinks  []
Bottom-Nav             ["Home", "Restaurants", "Über uns", "Login"]
```

Der Umschalter steht in `<div class="hidden md:block">` der Kopfzeile — die einzige
Einbindung im Projekt (`grep`: ein Treffer in `base.html.twig:110`).

**Warum das hier schwerer wiegt als anderswo:** Diese Plattform ist als
**installierbare Telefon-App** ausgelegt (B25, PWA). Auf genau dem Gerät, für das sie
gebaut ist, sind drei der vier gepflegten Kataloge nur über die Adresszeile erreichbar —
und wer eine Sprache über die Adresse eintippen kann, braucht keine Übersetzung.

Zusammen mit AK-14 (kein `Accept-Language`) heißt das: Ein französischsprachiger Besucher
mit dem Telefon bekommt Luxemburgisch und hat innerhalb der App keinen Weg heraus.

**Vorschlag:** Die Frage aus OF-01 („die Bottom-Nav hat vier Felder und keinen Platz")
hat eine Antwort, die kein fünftes Feld braucht: Der Umschalter gehört nicht in die
Bottom-Nav, sondern in die Kopfzeile neben das Logo — dort steht auf Mobil ohnehin Platz.
Alternativ als erster Eintrag im mobilen Menü, falls es eines gibt.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Wertprüfung des Sprachcodes im Pfad** | greift — `es`, `xx`, `LB`, `de-DE` → 404 |
| **Wertprüfung des Sprachcodes im Query** | **greift nicht** → BF-68 |
| **Open Redirect** | **möglich**, im Browser belegt → BF-68 |
| **XSS über `_locale`** | nein — `de"><script>` wird urlkodiert |
| **CRLF/Header-Injection** | nein — `%0d%0a` wird doppelt kodiert |
| **Auslösbarer 500er** | **ja**, 10 von 10 öffentlichen Seiten → BF-68 |
| **Sprachspeicherung** | keine — kein Cookie, nichts serverseitig (AK-15) |
| **Sprachfreie Endpunkte** | dicht: `/de/api/v1/…` und `/de/open.json` → 404 |
| **Admin-Umschalter** | eigene Route, kein Query-Merge — nicht betroffen |
| **Testsuite** | 369 Tests, 0 Fehler |

## Hinweise ohne Fehlerstatus

- **FB-06 hat sich erledigt.** Die Spec nennt die `UniqueEntity`-Meldung als hartkodiert
  deutsch. Sie ist es nicht mehr: `#[UniqueEntity(message: 'user.email_unique')]`, und der
  Schlüssel steht in allen vier Katalogen — *„Diese E-Mail-Adresse wird bereits
  verwendet."* / *„Cette adresse e-mail est déjà utilisée."* / *„This email address is
  already in use."* / *„Dës E-Mail-Adress gëtt scho benotzt."* Das kam mit der
  B01-Reparatur.
- **AK-07 beschreibt eine Vorsorge, keine Wirkung.** `app_root` ist ein
  `RedirectController` und rendert `base.html.twig` nie — der Teilausdruck
  `!= 'app_root'` kann also nicht greifen. Wirksam ist die andere Hälfte derselben Zeile:
  `_current_route and …`, und die habe ich an der 404-Seite belegt (0 hreflang gegen 5 auf
  einer gültigen Seite). Kein Fehler; nützlich, falls `app_root` je eine echte Seite wird.
- **hreflang trägt die Query-Parameter bewusst nicht mit**, der Umschalter schon. Das ist
  richtig so: hreflang beschreibt die Sprachvarianten *einer* Seite, nicht einer gefilterten
  Ansicht.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

`tests/Unit/Translation/CatalogueCompletenessTest.php` — **4 Tests, 24 Assertions, grün.**

Er prüft, dass `messages` und `validators` in allen vier Sprachen **dieselbe
Schlüsselmenge** tragen und dass kein Wert leer ist. Das ist heute erfüllt (1084 und 82)
und deckt die Regression ab, die hier am wahrscheinlichsten ist: jemand ergänzt einen
Schlüssel nur auf Deutsch.

**Nicht angelegt** habe ich den Test auf BF-69 (im Code verwendete Schlüssel, die in
keinem Katalog stehen) — er wäre heute rot. Er gehört in den Reparaturdurchgang, dann in
der richtigen Richtung. Dasselbe gilt für die drei Tests zu BF-68.

**Suite: 369 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-build B24` mit **BF-68**. B24 geht auf `review`; BF-68 bis BF-72 stehen in
`features/befunde.md`.

**Die Erfassung pausiert** — bei einem Bestandsfeature betrifft ein Befund vom Grad *hoch*
Code, der in diesem Moment läuft. B25, B26 und Feature `01` warten, bis BF-68 repariert,
erneut geprüft und ausgeliefert ist.

Die Reparatur selbst ist klein: eine Zeile für die Reihenfolge, eine für den `filter`.
Was zählt, ist der Test dazu — dieser Fehler stand in der Spec und galt als harmlos.
