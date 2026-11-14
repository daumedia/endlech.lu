# App-Shell

Was auf jeder Seite gleich ist: Layout-Hierarchie, Kopfzeile, Navigation, Fußzeile
und die beiden aufgesetzten Leisten (Bottom-Navigation, Cookie-Banner).

Dieses Dokument beschreibt **den Bestand**, nicht den Sollzustand. Abweichungen und
Lücken stehen unter [Bekannte Lücken](#bekannte-lücken) — sie sind dort Befunde, keine
beschlossenen Entscheidungen.

Verwandte Dokumente: [PRD](prd.md) · [Design-System](design-system.md) · [Datenmodell](data-model.md)

---

## Inhalt

1. [Layout-Hierarchie](#layout-hierarchie)
2. [Sprachen und Routing](#sprachen-und-routing)
3. [Dokumentkopf](#dokumentkopf)
4. [Kopfzeile](#kopfzeile)
5. [Hauptbereich und Flash-Meldungen](#hauptbereich-und-flash-meldungen)
6. [Fußzeile](#fußzeile)
7. [Aufgesetzte Leisten](#aufgesetzte-leisten)
8. [Admin-Shell](#admin-shell)
9. [Druckansicht](#druckansicht)
10. [Bekannte Lücken](#bekannte-lücken)

---

## Layout-Hierarchie

Zwei Ebenen, mehr nicht:

```
templates/base.html.twig          alle öffentlichen Seiten
└── templates/admin/base.html.twig  erbt von base, ersetzt {% block body %}
```

Die Admin-Shell ist **kein eigenes Layout**, sondern füllt den `body`-Block der
öffentlichen Shell. Kopfzeile und Fußzeile der Website bleiben im Admin also stehen;
darunter setzt der Admin sein eigenes dunkles Band und die Seitenleiste.

Bereiche ohne eigenes Layout: E-Mails (`templates/email/base.html.twig`, eigenständig)
und `public/offline.html` (steht außerhalb von Twig, weil offline weder Server noch
die Dateien unter `/assets/` erreichbar sind).

| Block | Zweck |
|---|---|
| `title` | Seitentitel, Vorgabe `Endlech.lu` |
| `stylesheets` / `javascripts` | AssetMapper-Einbindung: drei `<link>` (Tom Select, GLightbox, app.css), `importmap('app')` mit Nonce, Polyfill-Lader. Wird nie überschrieben — wer es tut, verliert JS und CSS |
| `body` | der gesamte Seiteninhalt |
| `admin_title` / `admin_body` | nur in der Admin-Shell |

---

## Sprachen und Routing

Vier Sprachen, konfiguriert in `config/packages/translation.yaml`:

| | |
|---|---|
| **Vorgabe** | `lb` (Luxemburgisch) |
| **Aktiv** | `lb`, `de`, `fr`, `en` |
| **Kataloge** | `translations/messages.{lb,de,fr,en}.yaml`, `validators.{…}.yaml` |

**Alle Web-Routen tragen den Präfix `/{_locale}`** (`config/routes.yaml`, Loader
`controllers`, Requirement `lb|de|fr|en`). Zwei Loader sind davon ausgenommen und
bewusst sprachfrei:

| Loader | Pfad | Warum sprachfrei |
|---|---|---|
| `api_v1` | `/api/v1` | Schnittstelle für die iOS-App |
| `open_data` | `/open.json`, `/open/dataset.*` | zitierte URLs müssen eindeutig bleiben |

Dazu sieben Weiterleitungen ohne Sprache, alle 302 über `LocaleRedirectController`: `app_root` (`/`) und die
Kurzlinks `/open`, `/vergleich`, `/presse`, `/roadmap`, `/changelog`, `/app`. **Seit 2026-09-15 in die Sprache des
Besuchers:** gewählte Sprache (Sitzung) → Browsersprache (`Accept-Language`) → `lb`. Vorher fest `_locale: lb`.
Die Antwort trägt `Vary: Accept-Language, Cookie`. ⚠ Keine der Routen darf `_locale` als Vorgabe tragen, sonst
schreibt `LocaleSubscriber` Luxemburgisch in die Sitzung (`LocaleRedirectControllerTest`).
Begründung für die Kurzlinks im Code: *„/open ist die URL, die in Fördermails, auf Visitenkarten und in
Vorträgen steht – sie darf nicht an einer Sprachwahl scheitern."*

Auch die Passkey-Endpunkte des WebAuthn-Bundles sind sprachfrei
(`/passkey/login/options`, `/passkey/register`, `/.well-known/webauthn`).

---

## Dokumentkopf

`<html lang="{{ app.request.locale }}">` — die Sprache steht am Wurzelelement.

**Viewport:** `width=device-width, initial-scale=1.0, viewport-fit=cover`.
Das `viewport-fit=cover` ist die Voraussetzung dafür, dass
`env(safe-area-inset-bottom)` in der Bottom-Navigation greift.

**PWA und iOS** (Issue #83):

| Tag | Wert |
|---|---|
| `<link rel="manifest">` | `manifest.webmanifest` |
| `theme-color` | `#0891b2` (cyan-600) |
| `mobile-web-app-capable`, `apple-mobile-web-app-capable` | `yes` |
| `apple-mobile-web-app-status-bar-style` | `black-translucent` |
| `apple-mobile-web-app-title` | `Endlech.lu` |
| `apple-touch-icon` | `icons/icon-180.png`, dazu 152/144/120/114/76/72/60/57 per Twig-Schleife |

**Suchmaschinen-Auszeichnung** (Feature 10). Drei Angaben, alle aus derselben Quelle —
dem Seitenverzeichnis `App\Seo\SeoRegistry` und dem Adressbildner `App\Seo\SeoUrlBuilder`:

| Angabe | Wo | Für welche Seiten |
|---|---|---|
| `<link rel="canonical">` | Block `canonical` in `base.html.twig`, Funktion `seo_canonical_url()` | nur Seiten aus dem Verzeichnis (20 feste plus Restaurant-Detailseiten) |
| `<link rel="alternate" hreflang>` | `base.html.twig`, Funktion `seo_alternate_urls()` | jede Seite mit Route außer `app_root`; alle Sprachen aus `enabled_locales` (lb, de, fr, en, pt) plus `x-default` auf `lb` |
| `X-Robots-Tag: noindex` | Antwortkopfzeile, `SeoRobotsHeaderSubscriber` | die 16 Ausschlusswege (Anmelden, Passwort, Bestätigungen, Abmeldelinks, Dankeseiten, Einreichformulare) |

⚠️ **Alle Adressen lauten auf `https://endlech.lu`**, auch lokal und im Test — der Host kommt
aus `app.canonical_base_url`, nicht aus der Anfrage. `www.endlech.lu` liefert jede Seite mit 200
(OF-03 in `features/10-sitemap-robots/spec.md`); aus der Anfrage gebaut, nennte eine Seite dort
`www` als maßgeblich.

⚠️ **Von der Abfragezeichenfolge überlebt nur `page` als ganze Zahl ab 2, und nur auf Seiten, die
blättern** (Restaurantliste, Board-Übersicht; `SeoRegistry::PAGINATED_ROUTES`, BF-147) — in canonical
und Sprachverweisen gleichermaßen (`/de/restaurants?page=2&sort=name` → `…/de/restaurants?page=2`,
aber `/de/about?page=2` → `…/de/about`).
Keine andere Eingabe des Aufrufers erreicht einen Verweis (BF-110 bleibt gewahrt).

⚠️ **Keine Vorlage füllt den Block `canonical` selbst.** Bis Feature 10 taten es fünf, mit einer
Adresse aus der Anfrage. Eine neue öffentliche Seite gehört ins Seitenverzeichnis — sonst wird
`SeoRouteCoverageTest` rot.

Dazu gehören `/sitemap.xml` (Route) und `/robots.txt` (statische Datei unter `public/`), siehe
`CLAUDE.md`, Abschnitt „Sitemap, robots.txt und maßgebliche Adressen".

**Private Seiten Angemeldeter** (Feature 17, AK-63). Zeigt eine Seite einem angemeldeten Konto
Gespeichertes — alle `app_saved_*`, dazu `app_restaurant_index` und `app_restaurant_show`
(gefüllte Herzen, Listen, Notiz) —, steht im Kopf
`<meta name="turbo-cache-control" content="no-cache">`, und die Antwort trägt
`Cache-Control: no-store, private`. Turbo legt dann keinen Schnappschuss an, der Browser keinen
Eintrag im Back-Forward-Cache: „Zurück“ nach dem Abmelden lädt vom Server. Für Gäste fehlen
beide.

| Baustein | Aufgabe |
|---|---|
| `App\Saved\PrivatePages` | die **eine** Routenliste und die Bedingung „angemeldet **und** private Route“ |
| Twig `is_private_page()` (`App\Twig\PrivatePageExtension`) | das Meta-Element; fragt die **Hauptanfrage** |
| `App\EventSubscriber\PrivatePageCacheSubscriber` | die Kopfzeile, Priorität −900 — nach allen übrigen Antwort-Zuhörern, vor dem Session-Zuhörer (−1000), dessen Cache-Control sie per `NO_AUTO_CACHE_CONTROL_HEADER` abbestellt |

⚠ **Das Meta-Element trägt kein `data-turbo-track`.** Es wechselt je Seite; mit Verfolgung läse
Turbo jeden Wechsel als neuen Stand und lüde die ganze Seite neu (siehe `CLAUDE.md`,
„Frontend“).

⚠ **Wer eine Seite anlegt, die Gespeichertes eines Kontos zeigt, trägt sie in `PrivatePages`
ein** — sonst fehlen Kopfzeile und Meta-Element dort, und kein Prüflauf ohne Browser merkt es.
Im Browser nachgeprüft ist das Verhalten bisher nur in Chromium, Safari ist offen
(`qa/17/browser-durchgang-2026-11-14.md`).

---

## Kopfzeile

```
<header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm print:hidden">
  container mx-auto px-4 h-20 flex items-center justify-between
```

Feste Höhe **80 px** (`h-20`), klebend am oberen Rand, über allem (`z-50`).
Die Admin-Seitenleiste rechnet mit dieser Höhe: ihr `sticky top-24` setzt 96 px an.

Drei Bereiche nebeneinander:

### 1 · Logo — immer sichtbar

`images/logo.png` in `h-12 w-auto object-contain`, daneben ab `md` der Schriftzug
„Endlech**.lu**" (`text-cyan-600` / `text-purple-600`). Verlinkt auf `app_home`.

### 2 · Hauptnavigation — `hidden md:flex`

Vier Punkte, in dieser Reihenfolge:

| Punkt | Ziel | Bemerkung |
|---|---|---|
| `nav.find_restaurants` | `app_restaurant_index` | |
| `nav.suggest_restaurant` | `community_vorschlagen` | |
| `nav.participate` | — | Aufklappmenü mit vier Einträgen |
| `nav.about` | `app_about` | steht zuletzt: Information ohne Handlungsaufforderung |

Das Aufklappmenü ist ein **natives `<details>/<summary>`**, kein Button-Dropdown.
Begründung im Code: funktioniert ohne JavaScript, ist von Haus aus tastaturbedienbar
und meldet seinen Zustand selbst an Screenreader. Der Stimulus-Controller
`nav-dropdown` schließt es lediglich zusätzlich bei Escape oder Klick daneben.
Der `::-webkit-details-marker` ist ausgeblendet, der Pfeil dreht sich über
`group-open:rotate-180` mit `motion-safe:`-Schutz.

Inhalt des Aufklappmenüs — die drei Vertriebszielgruppen, Slugs aus
`App\Enum\OrganisationType::slug()`:

`🍽️ nav.for_restaurants` → `app_partner` ·
`🏛️ nav.for_communes` → `…/gemeinden` ·
`🏢 nav.for_companies` („Für Unternehmen und Vereine") → `…/unternehmen`

Bis 2026-10-14 stand dort ein vierter Eintrag `🤲 nav.for_associations` → `…/vereine`
(Beirat, entfallen; die Adresse leitet weiter).

### 3 · Konto und Sprache — rechts

| Element | Sichtbarkeit | Bedingung |
|---|---|---|
| Sprachumschalter | `hidden md:block` | immer |
| **„Gespeichert“** (`saved.nav`) → `app_saved_index` | `hidden md:inline-flex`; ab `md` nur das Herz, der Text erst ab `lg` (`sr-only lg:not-sr-only` — der zugängliche Name bleibt derselbe) | angemeldet; aktiv (`text-cyan-700`, `aria-current="page"`) auf allen `app_saved_*` (Feature 17, AK-43) |
| Avatar + Name → `app_profile` | `hidden md:inline-flex` | angemeldet |
| `nav.admin` → `admin_dashboard` | `hidden md:inline-block` | `ROLE_ADMIN` |
| `nav.logout` | `hidden md:inline-block` | angemeldet |
| `nav.login` | `hidden md:inline-block` | abgemeldet |
| `nav.join` → `app_register` | **immer sichtbar** | abgemeldet |

Der Registrieren-Knopf ist der einzige Bedienpunkt der Kopfzeile ohne
`hidden md:`-Schranke: violetter Vollknopf, `rounded-full`, mit
`hover:-translate-y-0.5`.

⚠ **Breite des angemeldeten Kontobereichs mit „Gespeichert“ — nachgemessen 2026-11-14**
(Feature 17, Vorschau-Browser, Konto angemeldet, `/xx/restaurants`): bei 768, 900, 1024 und
1280 px in de, bei 768 und 1024 px auch in fr und pt **kein Überlauf** (`scrollWidth` des
Kopfinhalts = `clientWidth`), Kopfzeile durchgehend 81 px hoch, kein Umbruch. Der Überlauf
aus BF-80 ist damit nicht zurück. Der neue Verweis braucht ab `md` ein Symbol und ab `lg`
ein Wort mehr Platz — wer dort weitere Einträge ergänzt, misst erneut.

---

## Hauptbereich und Flash-Meldungen

Zwischen Kopfzeile und Inhalt steht ein fester Container mit
`partials/_flash_messages.html.twig` — auf **jeder** Seite, auch wenn keine Meldung
vorliegt. Die Admin-Shell bindet dasselbe Partial ein zweites Mal in ihrem
Inhaltsbereich ein.

**Ansagebereich `#meldung-live`** (Feature 17, AK-48). Im selben Container, direkt unter den
Meldungen, steht auf jeder Seite ein leeres
`<div id="meldung-live" role="status" aria-live="polite" aria-atomic="true">`. Turbo-Streams
schreiben ihre Meldung dort hinein (`action="update"`), ohne die Seite neu zu laden — heute das
Herz („Gemerkt: …“) und das Entfernen einer Karte auf „Favoriten“ bzw. aus einer Liste
(`saved/favorite.stream.html.twig`, `saved/cards.stream.html.twig`).
⚠ **Er muss leer im Dokument stehen, bevor etwas hineinkommt** — einen neu eingefügten
Live-Bereich sagen Screenreader nicht zuverlässig an. Ohne Inhalt ist er stumm und unsichtbar.

**Unsichtbare Meldungsarten.** Zwei Flash-Arten erzeugen keinen Kasten, sondern ein
verborgenes Element (`hidden`) mit Stimulus-Controller, beide mit `data-turbo-temporary`
(BF-177 — sonst wirkten sie bei jeder Rückkehr aus Turbos Zwischenspeicher erneut):

| Art | Seit | Wirkung |
|---|---|---|
| `usage_event` | Feature 12 (BF-171) | löst ein Messereignis aus `UsageEventCatalogue` aus — erst **nach** gelungener Handlung, auf der Folgeseite (Controller `usage-event`) |
| `scroll_to` | Feature 17 (AK-09, AK-51) | Rücksprung nach einer Weiterleitung mit Anker: rollt zum Element und fokussiert dessen Knopf (Controller `scroll-to`). **Turbo verliert den Anker** einer Weiterleitung, weil es ihr per `fetch` folgt — im Browser gemessen stand die Seite oben, das Herz 2000 px tiefer. Übernommen werden nur `favorit-{Nummer}`, `in-listen` und `notiz`; der Wert kommt vom Server, das Muster im Partial schützt trotzdem vor einem untergeschobenen Flash |

Alle übrigen Arten erscheinen als Kasten mit `role="alert"` (Farbe nach `success`/`error`/sonst).

```
<main class="flex-grow pb-16 md:pb-0 print:pb-0">
```

Das `pb-16` schafft auf Mobil Platz für die feste Bottom-Navigation; ohne das läge
der letzte Inhalt dahinter. `<body>` ist `flex flex-col min-h-screen`, damit die
Fußzeile auf kurzen Seiten unten bleibt.

### Ziel nach der Anmeldung — die Weiche der Einführung (Feature 12)

`form_login` schickt nach der Anmeldung auf `app_home`, sofern keine Zielseite gemerkt ist.
Seit Feature 12 lenkt `App\EventSubscriber\OnboardingRedirectSubscriber` **genau diese**
Weiterleitung auf `/{locale}/einfuehrung` um, wenn das Konto fällig ist
(`User::isOnboardingPending()`). Eine gemerkte Zielseite (B11: „Restaurant vorschlagen")
gewinnt; „angemeldet bleiben" liefert keine Weiterleitung und bleibt unberührt.

Die vier Bildschirme unter `/{locale}/einfuehrung/{willkommen|eintraege|profilbild|newsletter}`
stehen im gewöhnlichen Seitengerüst — Kopfzeile, Flash-Meldungen, Fußzeile und
Bottom-Navigation bleiben, man kommt jederzeit hinaus. Erreichbar auch über
„Einführung ansehen" im Profil.

**Seit Feature 17 setzt auch das Herz eines Gastes eine Zielseite.** Tippt ein Abgemeldeter
aufs Herz, merkt sich die Firewall die Seite des Herzens samt Anker `#favorit-{Nummer}` als
Ziel und die Sitzung den Merkwunsch; die Anmeldeseite zeigt „Melde dich an, um dir … zu
merken.“ mit dem Namen des Lokals (`saved.login_hint`). Ein zweiter Zuhörer auf `LoginSuccessEvent`
(`App\EventSubscriber\PendingFavoriteSubscriber`, nur Firewall `main`) löst den Wunsch nach der
Anmeldung ein — für Passwort und Passkey gleichermaßen — und lässt die Antwort unangetastet.
Die Zielseite gewinnt damit gegen die Einführung; die bleibt fällig (17/EC-04).

---

## Fußzeile

`templates/partials/_footer.html.twig`, eingebunden in `base.html.twig`.
`bg-gray-900 text-gray-300 print:hidden`. **Seit 2026-09-15 nach Themen gegliedert**, vorher
Feature für Feature gewachsen (eine Spalte „Links" mit zwölf ungeordneten Einträgen, Impressum
neben Suche neben Cookie-Einstellungen, dazu eine Liste ohne Überschrift unter „Kontakt").

**Raster:** `grid-cols-2 md:grid-cols-4 lg:grid-cols-12`. Markenspalte `col-span-2 md:col-span-4
lg:col-span-4` (volle Breite bis `lg`), jede Gruppe `lg:col-span-2`. Gemessen: Desktop 1440 px
359 px hoch (vorher 541), Tablet 900 px 591 px (vorher 778).

| Bereich | Überschrift (`h2`) | Inhalt |
|---|---|---|
| Marke | — / `footer.contact_title` | Wortmarke → `app_home`, `footer.tagline`, Herstellerhinweis `footer.product_of` („Ein Produkt von Daumedia" → `daumedia.lu/lb/` bzw. `/en/`, Twig-Globals `operator_brand`/`operator_website`, ohne `rel="sponsored"`); Kontakt: `footer.help_improve`, `mailto:support@endlech.lu`, GitHub (`target="_blank"`, `rel="noopener noreferrer"`) |
| Entdecken | `footer.discover` | Restaurants suchen · Kriterien für Barrierefreiheit · App-Warteliste (Feature 08) |
| Mitmachen | `footer.get_involved` | Restaurant vorschlagen (`footer.suggest_restaurant`) · **Roadmap & Ideen** (`footer.roadmap_ideas` → `app_roadmap_index`, Feature 16) · Für Restaurants · Für Organisationen |
| Über Endlech | `nav.about` | Über uns (`nav.about_short`, BF-80) · Zahlen & Finanzen · Presse (Feature 05) — Roadmap und Changelog stehen seit Feature 16 nicht mehr hier, sondern sind Reiter des Bereichs „Roadmap & Ideen“ |
| Vergleiche | `footer.comparisons` | je ein Link pro Wettbewerber + `footer.all_comparisons` (Feature 03 AK-01) |
| Sponsoren (Feature 15) | `sponsor.footer.title` (`section[aria-labelledby=footer-sponsoren]`) | eigene Zeile unter dem Raster: bis zu zwölf Logos auf weißer Kachel (je ein Link auf die Sponsorenseite, `alt` = Name), dann „Alle Sponsoren" (sobald es aktive oder ehemalige gibt) und „Sponsor werden →". Ohne jeden Sponsor nur der letzte Link |
| Rechtsleiste | `nav[aria-label=footer.legal_nav]` | Impressum · Datenschutz (`app_impressum#datenschutz`) · Barrierefreiheit (Feature 02 AK-42) · Cookie-Einstellungen |

⚠ **Wer einen Link ergänzt, ordnet ihn einer Gruppe zu.** Keine Liste ohne Überschrift, keine
Rechtsseite zwischen den Inhalten — genau so war die Fußzeile unübersichtlich geworden.
`FooterStructureTest` prüft beides.

⚠ **Die Wortmarke ist EIN Textfluss** (`<span>Endlech</span>.lu` direkt im Link). Im alten
`flex gap-2`-Container stand zwischen „Endlech" und „.lu" eine sichtbare Lücke.

⚠ **Links tragen `min-h-[44px] md:min-h-0`** (Projektregel für Tap-Ziele unter `md`, wie BF-144).
Auf dem Telefon ist die Fußzeile deshalb kaum kürzer als vorher (1053 statt 1105 px), aber in
zwei Spalten gegliedert.

**Feedback & Ideen** zeigte bis 2026-08-30 auf `https://endlech.userjot.com` und wurde mit
Feature 06 **ersetzt**, nicht ergänzt (AK-80: genau ein Rückmeldeweg, intern, ohne `_blank`).
Seit Feature 16 (2026-11-07) ist dieser eine Rückmeldeweg der Eintrag **„Roadmap & Ideen“**: Er führt
auf den Reiter Roadmap, dessen Kopf den Knopf „Idee einreichen“ trägt (16/AK-01 bis AK-03, AK-10).

**Bereichskopf „Roadmap & Ideen“** (`roadmap/_area_header.html.twig`, Feature 16): einzeiliges Verlaufsband
mit Bereichsname (Etikett, **kein** `h1`) und Knopf „Idee einreichen“, darunter die Reiterleiste
(`<nav aria-label=…>`, gewöhnliche Links mit `aria-current="page"`, alle gleich fett, aktiver Reiter mit
4-px-Unterstrich, je ≥ 44 × 44 px), **danach** `h1` + Einleitung der Seite. Über der Leiste steht auf jeder
Seitenart dasselbe — sie steht beim Wechseln still (BF-200). Steht auf `/roadmap`, `/roadmap/ideen`,
`/changelog` und der Einzelseite einer Idee; nicht auf Formular und Bestätigung (16, Decision Log 14).

**Die Vergleiche kommen aus einer Twig-Erweiterung**, nicht aus dem Controller:
`comparison_competitors()` in `src/Twig/ComparisonExtension.php`. Die Fußzeile wird auf *jeder*
Seite gerendert; der erste Controller, der die Liste vergäße, lieferte eine halbe Fußzeile.

~~⚠ Die Fußzeile überschreibt ihre Spalten mit `<h4>`.~~ **Behoben (BF-109):** Gruppentitel sind
`h2`, `HeadingOrderTest` prüft die Kette. Die Schriftgröße hängt an den Klassen.

**„Cookie-Einstellungen" wird auf Admin-Routen unterdrückt** — eigene `cookie-consent`-Instanz,
stößt per `dispatch('open')` das Banner an.

Zum Feature 08 gehört außerdem ein **Hinweisband auf der Startseite**
(`templates/home/index.html.twig`), außerhalb der Shell. ⚠ **Kein Store-Abzeichen** (es gibt keine
veröffentlichte App).

Abschlusszeile: `© {{ 'now'|date('Y') }} Endlech.lu`, `footer.copyright` und die Version aus dem
Twig-Global `app_version` (`app.version` in `config/services.yaml`). **Das ist die einzige
Versionsangabe, die Besucher sehen.**

⚠ Dieser Abschnitt ist zweimal hinter dem Template zurückgeblieben. Wer die Fußzeile ändert,
zieht ihn mit.

---

## Aufgesetzte Leisten

Beide hängen am Ende des `<body>` und beide werden auf Admin-Routen unterdrückt,
über dieselbe Bedingung wie der Cookie-Link in der Fußzeile.

### Bottom-Navigation — `partials/_bottom_nav.html.twig`

```
fixed bottom-0 inset-x-0 z-50 md:hidden bg-white border-t
pb-[env(safe-area-inset-bottom)]
```

Vier gleich breite Felder (`grid-cols-4`), Tap-Target mindestens 44 px, Symbol plus
Beschriftung. Der aktive Zustand kommt aus einem Routenvergleich und setzt
`text-cyan-600` **und** `aria-current="page"` — Farbe trägt nie allein.

| Feld | Ziel | aktiv bei |
|---|---|---|
| `nav.home` | `app_home` | `app_home` |
| `nav.restaurants` | `app_restaurant_index` | `app_restaurant_index`, `app_restaurant_show` |
| **angemeldet:** `saved.nav` („Gespeichert“, Herz) · **abgemeldet:** `nav.about_short` | `app_saved_index` bzw. `app_about` | **jeder** Route mit Präfix `app_saved_` (Übersicht, Favoriten, Listen, Rückfrage) bzw. `app_about` |
| `nav.profile` / `nav.login` | `app_profile` bzw. `app_login` | je nach Anmeldestatus |

Angemeldet also **Start · Restaurants · Gespeichert · Profil**, abgemeldet unverändert
Start · Restaurants · Über uns · Anmelden (Feature 17, AK-41).

⚠ **„Gespeichert“ steht an der Stelle von „Über uns“, nicht als fünfter Punkt**
(Entscheidung 17/OF-04 vom 2026-11-13, Decision Log 25). Fünf Punkte ließen bei 320 px je
64 px, und schon heute stehen dort Beschriftungen mit zwölf Zeichen (lb „Restauranten“, pt
„Restaurantes“, „O meu perfil“), in 12-px-Inter rund 74 px breit. Vier Punkte behalten 80 px
und einzeilige Beschriftungen in allen fünf Sprachen (gemessen 2026-11-14: je 76 × 60 px bei
320 px). „Über uns“ ist Information ohne Handlung und bleibt über die Fußzeile erreichbar.
**Wer einen fünften Punkt ergänzt, fasst AK-42 von Feature 17 an.**

Der aktive Zustand prüft neben der Liste `active` seit Feature 17 auch ein optionales
`activePrefix` — sonst müsste jede neue Seite des Bereichs „Gespeichert“ einzeln eingetragen
werden.

Die Leiste trägt ein `aria-label` aus `nav.bottom_navigation`; die Symbole sind
`aria-hidden`.

⚠ **Der aktive Punkt verfehlt den Kontrast für kleinen Text:** `text-cyan-600` auf Weiß bei
12 px misst 3,61 : 1, nötig sind 4,5 : 1 (axe, 2026-11-14). Bestand aus B25, nicht durch
Feature 17 verursacht — betrifft seitdem aber auch „Gespeichert“
(`qa/17/browser-durchgang-2026-11-14.md`).

### Cookie-Banner — `partials/_cookie_banner.html.twig`

`role="dialog"`, `aria-modal="false"`, fest am unteren Rand. Der Controller
`cookie_consent_controller.ts` zeigt es, solange kein Cookie `cookie_consent` gesetzt
ist, und schreibt bei Wahl `accepted`/`declined` für 365 Tage
(`path=/; samesite=lax`, `secure` nur bei HTTPS). Verlinkt auf
`app_impressum` mit Anker `#datenschutz`.

---

## Admin-Shell

`templates/admin/base.html.twig`, erbt von `base.html.twig`.

**Kopfband:** `bg-linear-to-r from-gray-800 to-gray-900`, links der Titel
`admin.title_prefix` (in Cyan), rechts der Admin-Sprachumschalter und der Rückweg
`admin.nav.back_to_website` → `app_home`.

**Aufteilung:** `flex flex-col lg:flex-row gap-8` — Seitenleiste `lg:w-1/5`,
Inhalt `lg:w-4/5`. Unterhalb von `lg` steht die Navigation also über dem Inhalt.

**Seitenleiste:** weiße Karte, `sticky top-24`, Überschrift `admin.nav.navigation`,
fünf Punkte mit Emoji:

| Punkt | Ziel | aktiv bei |
|---|---|---|
| 📊 `admin.nav.dashboard` | `admin_dashboard` | exakter Routenvergleich |
| 🍽️ `admin.nav.restaurants` | `admin_restaurant_index` | `starts with 'admin_restaurant'` |
| 💡 `admin.nav.suggestions` | `admin_suggestion_index` | `starts with 'admin_suggestion'` |
| 🤝 `admin.nav.waitlist` | `admin_waitlist_index` | `starts with 'admin_waitlist'` |
| 💶 `admin.nav.finance` | `admin_finance_index` | `starts with 'admin_finance'` |

Der aktive Zustand ist `bg-purple-50 text-purple-700` — die Admin-Leitfarbe ist
Violett, nicht das Cyan der Website.

**Zugriff:** `config/packages/security.yaml`,
`{ path: '^/[a-z]{2}/admin', roles: ROLE_ADMIN }`. Der Präfix `[a-z]{2}` deckt die
Sprachkomponente ab.

---

## Druckansicht

`print:hidden` liegt auf Kopfzeile, Fußzeile, Bottom-Navigation und Cookie-Banner;
`<main>` verliert im Druck sein `pb-16`. Begründung im Code: *„Gedruckt oder als PDF
gespeichert ist Navigation nichts wert, und ein `sticky` Header erscheint sonst auf
jeder Papierseite erneut."*

Angelegt für `/open`, wo der PDF-Export vor Fördergesprächen der erwartete Weg ist,
aber bewusst global gesetzt. Die Feinheiten — Verlaufsbänder entfärben, `<details>`
aufklappen, Umbrüche in Diagrammen verhindern — stehen im `@media print`-Block von
`assets/styles/app.css` und im [Design-System](design-system.md#druckansicht).

---

## Bekannte Lücken

Beobachtet beim Rückerfassen, **nicht** behoben. Jeder Punkt ist ein Befund, keine
Entscheidung.

### 1 · Auf Mobil gibt es keinen Weg, sich abzumelden

Der einzige `app_logout`-Link im gesamten Projekt steht in `base.html.twig:122` und
trägt `hidden md:inline-block`. Ein Burger-Menü existiert nicht; die
Bottom-Navigation führt angemeldet zu Startseite, Restaurants, Gespeichert und Profil
(bis Feature 17: Über uns statt Gespeichert). Die Profilseite enthält keinen
Abmeldeknopf.

⚠ **Überholt, festgestellt 2026-11-14 beim Nachziehen von Feature 17:** Die Profilseite
trägt seit Feature 02 ein Abmeldeformular (`templates/profile/index.html.twig`, „die
Bottom-Nav führt hierher“). Der Befund ist damit vermutlich erledigt, aber nicht neu
geprüft.

Gewicht: Die Anwendung ist ausdrücklich als installierbare iPhone-App gebaut
(Issue #83). Auf genau diesem Gerät kann sich ein angemeldeter Nutzer nicht abmelden,
ohne die URL von Hand einzugeben.

### 2 · Auf Mobil gibt es keine Sprachwahl

`partials/_language_switcher.html.twig` wird ausschließlich in `base.html.twig:110`
eingebunden, dort in `<div class="hidden md:block">`. Die Bottom-Navigation hat
keinen Ersatz. Auf einem Telefon bleibt die Sprache damit auf dem, was die Route
mitbringt — die Vorgabe `lb`.

Gewicht: vier gepflegte Sprachkataloge, von denen auf Mobil nur einer erreichbar ist.

### 3 · Zwei Navigationspunkte fehlen auf Mobil ersatzlos

„Restaurant vorschlagen" und das Aufklappmenü „Mitmachen" (Partner, Gemeinden,
Unternehmen und Vereine) stehen nur in der `hidden md:flex`-Kopfnavigation. Beides sind
die Eingänge zu Community-Beitrag und Vertrieb — auf Mobil nur über die Fußzeile
erreichbar (dort liegen Partner und Organisationen), „vorschlagen" gar nicht.

### 4 · ~~Toter Link in der Fußzeile~~ — behoben

`base.html.twig`, Spalte 2, erster Eintrag: `footer.search_restaurants` zeigte auf
`href="#"` statt auf `app_restaurant_index`. **Inzwischen behoben**; der Eintrag
verweist auf die Restaurantliste (festgestellt am 2026-08-28).

### 5 · Der GitHub-Link zeigt auf ein anderes Konto

Die Fußzeile verlinkt `https://github.com/Mukaarts/endlech.lu`; das konfigurierte
Remote ist `https://github.com/daumedia/endlech.lu.git`. Einer der beiden Werte ist
überholt — welcher, ist von außen nicht entscheidbar.

### 6 · Die Admin-Shell erbt die öffentliche Kopf- und Fußzeile

Weil `admin/base.html.twig` nur `{% block body %}` füllt, stehen über der
Admin-Oberfläche die Website-Navigation und darunter die vollständige Fußzeile.
Das ist funktionsfähig und spart ein zweites Layout; ob es gewollt ist, geht aus dem
Code nicht hervor. Die Ausnahmen für Bottom-Navigation, Cookie-Banner und
Cookie-Link zeigen, dass die Kollision an drei Stellen einzeln entschärft wurde —
ein eigenes Admin-Layout hätte alle drei erübrigt.

### 7 · Die Kopfzeile läuft zwischen 768 px und 1000 px über

**Gemessen am 2026-08-30** beim Selbsttest von Feature 05 (Brave, headless, `/de/about`):

| Fensterbreite | abgemeldet | angemeldet (ROLE_ADMIN) |
|---|---|---|
| 320 px · 375 px | ok | ok |
| **768 px** | **+36 px** | **+81 px** |
| 800 px | +20 px | — nicht gemessen |
| 850 px | ok | **+40 px** |
| 900 px | ok | **+15 px** |
| 1000 px und mehr | ok | ok |

**Ursache:** `header > div.container` ist ein `flex` ohne Umbruch, und der
`md:`-Umbruchpunkt liegt bei genau 768 px. Ab dort blendet die Kopfzeile die
Hauptnavigation (`hidden md:flex`, 416 px) **und** den Konto- und Sprachbereich
ein — bevor Platz dafür da ist. Bei 768 px messen die drei Gruppen zusammen
789 px (abgemeldet: Logo 123 + Navigation 416 + Konto 250) beziehungsweise
833 px (angemeldet: 123 + 416 + 295), während der Inhaltsbereich nach
`px-4` beidseitig nur 736 px fasst.

**Reichweite:** jede Seite. Nachgemessen auf `/de/about`, `/de/presse`,
`/de/vergleich`, `/de/open` und `/de/partner` — überall derselbe Wert, weil die
Ursache in `base.html.twig` liegt und nicht im Seiteninhalt.

⚠ **Das ist kein neuer Befund — er steht seit der QA von Feature `02` als BF-80
in `features/befunde.md`** (dort mit 51 px gemessen, Grad *mittel*). Beim
Selbsttest von Feature `05` wurde er am 2026-08-30 erneut gefunden und zunächst
für neu gehalten; die Messung oben ist der Ertrag daraus, denn **den angemeldeten
Fall führte BF-80 nicht** — dort ist der Überlauf doppelt so groß und reicht bis
unter 1000 px. BF-80 ist entsprechend ergänzt, nicht verdoppelt.

⚠ **Warum er zweimal übersehen wurde:** Die Kriterien der Features `02`, `03` und
`05` nennen **320 px** (die QA von `03` maß zusätzlich 375 px). Dort tritt der
Fehler nicht auf — die Navigation ist unterhalb von `md:` ausgeblendet. Wer ihn
behebt, ergänzt zugleich eine Prüfbreite zwischen 768 px und 1000 px, sonst kommt
er wieder.

**Nicht behoben.** Der Befund entstand beim Bau von Feature 05 und gehört nicht
dorthin repariert: Er betrifft die App-Hülle und damit jedes Feature. Mögliche
Richtungen — der Umbruchpunkt der Navigation wandert von `md:` auf `lg:`, oder
die Kopfzeile darf umbrechen. Beides ist eine Entwurfsentscheidung, keine
Kleinigkeit.
