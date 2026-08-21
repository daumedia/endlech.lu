# Design-System

Die visuelle Sprache von Endlech.lu: Farben, Typografie, Komponenten und die
Regeln für Barrierefreiheit, an die sie gebunden sind.

**Das Wichtigste vorweg:** Es gibt keine `tailwind.config.js` und keinen
`@theme`-Block. `assets/styles/app.css` besteht aus `@import "tailwindcss"` plus
vier Sonderblöcken. **Es existiert kein einziger benannter Design-Token** — das
System lebt vollständig in den Utility-Ketten der 77 Twig-Templates. Dieses
Dokument ist deshalb die einzige Stelle, an der es überhaupt aufgeschrieben ist.

Wo zwei Varianten nebeneinander existieren, ist eine als **Kanon** markiert und
die andere als **Bestand**. Bestand heißt: vorhanden, funktioniert, aber nicht
nachbauen. Die Angleichung ist ein eigenes Vorhaben — siehe
[Bekannte Abweichungen](#bekannte-abweichungen).

Verwandte Dokumente: [Datenmodell](data-model.md) · [PRD](prd.md)

---

## Inhalt

1. [Grundlagen](#grundlagen)
2. [Farbe](#farbe)
3. [Verläufe](#verläufe)
4. [Typografie](#typografie)
5. [Abstand und Layout](#abstand-und-layout)
6. [Komponenten](#komponenten)
7. [Barrierefreiheit](#barrierefreiheit)
8. [Responsive](#responsive)
9. [Diagramme](#diagramme)
10. [Druckansicht](#druckansicht)
11. [E-Mail](#e-mail)
12. [Stimulus-Controller](#stimulus-controller)
13. [Bekannte Abweichungen](#bekannte-abweichungen)

---

## Grundlagen

**Tailwind CSS v4.1** über PostCSS. Die vollständige Konfiguration:

```js
// postcss.config.mjs
export default { plugins: { "@tailwindcss/postcss": {} } };
```

```css
/* assets/styles/app.css */
@import "tailwindcss";
@source not "../../public";
```

Gebaut wird mit **Webpack Encore** (nicht AssetMapper, trotz vorhandener
`importmap.php`): Entry `assets/app.ts` → `public/build/`, TypeScript über
ts-loader mit `transpileOnly`, Typprüfung separat per `npm run typecheck`.

> **Wer Templates ändert, muss `npm run build` ausführen und `public/build`
> mitcommitten.** Der CD-Workflow baut die Assets neu und vergleicht sie mit dem
> committeten Stand; eine Abweichung blockt den Deploy. Ein laufender
> `npm run watch` überschreibt `public/build` mit einem Dev-Build — vor dem
> Commit also immer noch einmal produktiv bauen.

Die vier Sonderblöcke in `app.css`:

| Block | Zweck |
|---|---|
| `@media (max-width: 767px)` | `font-size: 16px` auf `input`/`select`/`textarea` — verhindert iOS-Auto-Zoom beim Fokussieren |
| `html:has(#warteliste)` | Smooth-Scroll nur auf der Partnerseite, und nur bei `prefers-reduced-motion: no-preference` |
| Tom-Select-Overrides | die einzige Stelle mit hartkodierten Farben — alle spiegeln Tailwind-Werte |
| `@media print` | siehe [Druckansicht](#druckansicht) |

---

## Farbe

Gezählt über alle Templates, nach Häufigkeit der Familie:

| Familie | Größenordnung | Rolle |
|---|---|---|
| **gray** | ~1000 | Text, Rahmen, Flächen — das Grundgerüst |
| **purple** | ~350 | Konto, Admin, Registrierung; Formular-Fokus im Bestand |
| **cyan** | ~250 | Marke, Navigation, Verifikation, öffentliche Aktionen |
| **green** | ~140 | Erfolg, „Ja", positive Veränderung |
| **red** | ~100 | Fehler, „Nein", destruktive Aktionen |
| **blue** | ~35 | neutrale Information, Status `confirmed` |
| **emerald** | ~27 | Ernährungsmerkmale (vegan, vegetarisch) |
| **amber** | ~23 | Warnung, Status `pending`, Ausgaben, Bewertung 4–6 |
| **teal** | ~17 | Vereine (`OrganisationType::ASSOCIATION`) |
| orange, pink, indigo, yellow | einstellig | Einzelakzente (Küchen-Tags orange) |

### Welche Farbe ist die primäre?

Die Frage stellt sich, weil cyan und purple beide wie Primärfarben auftreten. Die
Auflösung:

- **Cyan ist die Markenfarbe.** Sie trägt die Wortmarke im Header
  (`text-cyan-600`), die `theme_color` der PWA (`#0891b2` = cyan-600), das
  Verifikations-Abzeichen, den Navigations-Hover und alle öffentlichen Aktionen.
- **Purple ist die Aktionsfarbe des angemeldeten Bereichs.** Registrierung,
  Profil, Admin-Navigation, „Heute geöffnet"-Hervorhebung.
- Die Wortmarke zeigt beide zusammen: `Endlech` in cyan, `.lu` in purple.

Alles andere ist Zustandsfarbe und wird nie dekorativ eingesetzt.

**Es gibt keinen Dark-Mode.** Null Treffer für `dark:`. Das ist eine
Feststellung, keine Lücke — der Modus wurde nie begonnen und ist deshalb auch
nicht halb vorhanden.

---

## Verläufe

Alle Verlaufsbänder nutzen die Tailwind-v4-Syntax `bg-linear-to-*` (nicht
`bg-gradient-to-*`). Es gibt **zwei Stufen**, deren Zuordnung bisher nirgends
stand:

| Stufe | Kette | Wo |
|---|---|---|
| **Hell** — Publikumsseiten | `bg-linear-to-r from-cyan-600 to-purple-700` | Startseite, Restaurantliste, Restaurantdetail, Über Endlech |
| **Dunkel** — Geschäfts- und Datenseiten | `bg-linear-to-r from-cyan-700 to-purple-800` | Partner, Organisationen, Open Startup |

Die dunkle Stufe ist kontraststärker und trägt längere Fließtexte im Hero. Wer
eine neue Seite anlegt, wählt nach Publikum, nicht nach Geschmack.

**Sonderfälle**

| Fundstelle | Kette | Zweck |
|---|---|---|
| `templates/home/index.html.twig` (CTA unten) | `from-purple-700 to-cyan-600` | umgekehrte Richtung — setzt den Schlussakzent gegen den Hero |
| `templates/admin/base.html.twig` | `from-gray-800 to-gray-900` | Admin ist bewusst farblos |
| `templates/about/index.html.twig` | `bg-linear-to-br from-cyan-500 to-purple-600` | Icon- und Avatarkacheln |
| `templates/about/index.html.twig` | `bg-linear-to-r from-cyan-600 to-purple-600 bg-clip-text text-transparent` | einziger Text-Verlauf |

> **Ein Rest alter Syntax:** `templates/restaurant/show.html.twig` nutzt noch
> `bg-gradient-to-t from-black/70 to-transparent` für das Bild-Overlay. Die
> Druckregel greift auf `section.bg-linear-to-r` und erfasst ihn deshalb nicht —
> unkritisch, weil es ein Overlay über einem Foto ist, aber gut zu wissen.

---

## Typografie

Systemschrift über `font-sans`, keine Webfont-Einbindung.

### Überschriften

| Ebene | Kette | Einsatz |
|---|---|---|
| **H1 Hero** | `text-3xl md:text-5xl font-bold mb-5 leading-tight` | auf Verlaufsgrund, weiß |
| **H1 Seite** | `text-3xl font-bold text-gray-900 mb-4` | auf hellem Grund |
| **H2 Sektion** | `text-2xl md:text-3xl font-bold text-center text-gray-900 mb-3` | zentrierte Sektionstitel |
| **H2 Block** | `text-2xl font-bold text-gray-800 mb-6` | linksbündige Abschnitte |
| **H2 Panel** | `text-lg font-bold text-gray-800 mb-4` | Überschrift innerhalb einer Karte — die häufigste Überschrift im Projekt |
| **H3** | `text-lg font-bold text-gray-900 mb-2` | |
| **Overline** | `text-sm font-bold text-gray-500 uppercase tracking-wider mb-3` | Kategorienzeile über einem Block |

### Fließtext

| Rolle | Kette |
|---|---|
| Hero-Lead | `text-cyan-100 text-lg md:text-xl max-w-2xl mx-auto mb-8` |
| Body | `text-gray-800 leading-relaxed` |
| Sekundär | `text-gray-500 text-sm` |
| Kleingedrucktes | `text-xs text-gray-500` |

Die tatsächliche Verteilung ist bewusst schmal: `text-sm` und `text-xs` machen
zusammen über die Hälfte aller Größenangaben aus, `text-base` kommt kaum vor
(der Standard braucht keine Klasse). Gewichte beschränken sich praktisch auf
`font-bold`, `font-semibold` und `font-medium`.

> **`tabular-nums` in Tabellen, nicht auf der Leitzahl.** In Tabellen und an
> Diagrammachsen sollen Ziffern untereinander stehen. Die große Kennzahl im
> Hero ist dagegen ein Schriftzug — dort wirken Tabellenziffern lückenhaft.

---

## Abstand und Layout

**Sektionsrhythmus**

| Klasse | Einsatz |
|---|---|
| `py-20` | größter Hero (Startseite, Partner, Organisationen) |
| `py-16` | Standard-Sektion — der mit Abstand häufigste Wert |
| `py-12` | kompaktere Sektion, Footer |
| `py-8` | Admin-Inhalt |

**Container** — immer `container mx-auto px-4`, danach optional eine Lesebreite:

| Klasse | Einsatz |
|---|---|
| `max-w-3xl` | Standard für Text- und Formularseiten |
| `max-w-2xl` | Hero-Lead, schmale Textblöcke |
| `max-w-4xl` / `max-w-5xl` | Feature-Raster |
| `max-w-xl` / `max-w-md` | Bestätigungsseiten, Login |

**Abstände zwischen Elementen:** `gap-2` und `gap-3` innerhalb von Zeilen,
`gap-4` zwischen Karten, `gap-8` zwischen Layout-Spalten.

**Sticky-Offsets:** Sidebars stehen auf `sticky top-24`, Sprungziele tragen
`scroll-mt-24`. Beide Werte hängen an der Headerhöhe `h-20` — wer den Header
ändert, muss sie mitziehen.

**Radien — die Konvention nach Bauteil:**

| Radius | Bauteil |
|---|---|
| `rounded-full` | Aktion, CTA, Abzeichen, Chip |
| `rounded-lg` | Eingabefeld, Block-Button im Formular |
| `rounded-xl` | Karte |
| `rounded-2xl` | großes Panel, Hero-Kachel |

**Schatten:** `shadow-sm` für Karten, `shadow-md` für Buttons und Hero-Bänder,
`shadow-lg` für Panels und Dropdowns. Hover hebt jeweils eine Stufe an.

---

## Komponenten

Die Ketten sind zum Kopieren gedacht. Jede ist im Bestand belegt; die Fundstelle
steht dabei.

### Karte

Der Grundstein — rund 30 Vorkommen in dieser oder einer nahen Form:

```html
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
```

| Variante | Kette |
|---|---|
| Listenkarte mit Hover | `bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition group flex flex-col` |
| Großes Panel | `bg-white rounded-2xl shadow-lg border border-gray-200 p-8` |
| Responsive Panel | `bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8` |
| Sidebar | `bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24` |
| Leerzustand | `bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500` — **ohne** Schatten |

**Verschachtelung:** `bg-white` ist die Karte, `bg-gray-50 border border-gray-100`
das Element darin (Passkey-Eintrag, Haltestelle, Tageszeile der Öffnungszeiten).
`bg-gray-50` ist zugleich der Seitenhintergrund — eine Karte hebt sich also durch
Weiß ab, nicht durch Schatten allein.

### Button

**Kanon** — `templates/partner/_form.html.twig:115`, wortgleich in
`templates/organisation/_form.html.twig`:

```html
<button class="w-full md:w-auto min-h-[48px] bg-cyan-700 hover:bg-cyan-800
               text-white font-bold px-8 py-3 rounded-full shadow-md hover:shadow-lg
               motion-safe:transition
               focus:outline-2 focus:outline-offset-2 focus:outline-cyan-700
               cursor-pointer">
```

Die vier Bestandteile, die nicht verhandelbar sind: `min-h-[48px]` (Tap-Target),
`motion-safe:` (respektiert `prefers-reduced-motion`), `focus:outline-2` mit
`outline-offset-2` (sichtbarer Fokus), und eine Outline-Farbe, die die
Buttonfarbe spiegelt.

Als Link statt Button: `inline-block` statt `w-full md:w-auto`.

**Einfärbung nach Zielgruppe** — dieselbe Kette, andere Farbe:

| Zielgruppe | Farbe |
|---|---|
| Öffentlich, Gemeinden | `bg-cyan-700 hover:bg-cyan-800 … focus:outline-cyan-700` |
| Unternehmen | `bg-purple-700 hover:bg-purple-800 … focus:outline-purple-700` |
| Vereine | `bg-teal-800 hover:bg-teal-900 … focus:outline-teal-800` |
| Destruktiv | `text-red-600 hover:text-red-800 … focus:outline-red-600` |

**Auf Verlaufsgrund** — primär und sekundär als Paar (`templates/open/index.html.twig`):

```html
<a class="inline-flex items-center gap-2 min-h-[48px] bg-white text-cyan-900 font-bold
          px-6 py-3 rounded-full shadow-lg hover:shadow-xl hover:bg-cyan-50
          motion-safe:transition focus:outline-2 focus:outline-offset-2 focus:outline-white">

<a class="inline-flex items-center gap-2 min-h-[48px] border border-white/40 text-white
          font-semibold px-6 py-3 rounded-full hover:bg-white/10
          motion-safe:transition focus:outline-2 focus:outline-offset-2 focus:outline-white">
```

**Mit Ladezustand** (Passkey-Anmeldung):
zusätzlich `disabled:opacity-60 disabled:cursor-wait` und
`motion-safe:transform motion-safe:hover:-translate-y-0.5`.

**Bestand — nicht nachbauen.** In `home/`, `security/`, `registration/`,
`profile/`, `admin/`, `community/` und `base.html.twig` steht noch die ältere
Form:

```html
<a class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-8 py-3
          rounded-full shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
```

Ihr fehlen die Mindesthöhe, der Fokusstil und der `motion-safe`-Schutz. Sie
funktioniert, aber jede neue Aktion folgt dem Kanon.

**Admin-Kompaktbuttons** nutzen `min-h-[44px]` statt 48 — dichtere Tabellen,
Bedienung mit Maus statt Daumen.

### Formularfeld

`templates/partials/_form_field.html.twig` kapselt Label, Pflichthinweis, Widget,
Hilfetext und Fehlermeldung samt `aria-describedby` und `aria-invalid`. Es ist
ein Include, **kein registriertes Form-Theme** — ein Theme schlüge global auf
Wizard, Admin und Profil durch.

```twig
{% set input_class = 'w-full rounded-lg bg-white px-4 py-2.5 text-base text-gray-900 border motion-safe:transition'
    ~ ' focus:outline-2 focus:outline-offset-2'
    ~ (has_error
        ? ' border-red-600 focus:outline-red-700'
        : ' border-gray-500 focus:outline-cyan-700') %}
```

| Teil | Kette |
|---|---|
| Label | `block mb-1 text-sm font-semibold text-gray-900` |
| Pflicht-/Optional-Zeile | `mb-1.5 text-xs text-gray-700` |
| Fehlermeldung | `mt-1 text-sm font-semibold text-red-700` |

> **Zwei Fallen, die dort schon einmal zugeschnappt sind.**
>
> In `attr` unterdrückt nur `false` ein Attribut, **nicht `null`**.
> `'aria-invalid': null` rendert `aria-invalid=""` — und Screenreader lesen das
> als „ungültig".
>
> Der Fehlercontainer wird **immer** gerendert, auch im Gutfall leer. Sonst zeigte
> `aria-describedby` ins Leere.
>
> Das erste fehlerhafte Feld bekommt serverseitig `autofocus`. Damit funktioniert
> die Fehlerführung ohne JavaScript; Turbo tut nach einem Render dasselbe.

### Segmented Control (dreiwertige Antwort)

`templates/partials/_tristate_field.html.twig`:

```html
<div class="inline-flex rounded-lg border border-gray-300 overflow-hidden divide-x divide-gray-300 bg-white">
  <input type="radio" class="peer sr-only">
  <span class="block px-4 py-2.5 min-w-[5.5rem] text-center text-sm text-gray-600 transition
               hover:bg-gray-50
               peer-checked:bg-cyan-600 peer-checked:text-white peer-checked:font-semibold
               peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-cyan-500">
```

> **`peer sr-only`, nicht `hidden`.** Ein `hidden`-Input ist für Tastatur und
> Screenreader nicht vorhanden. `sr-only` nimmt ihn nur optisch heraus — er
> bleibt fokussierbar, und der Fokus wird über `peer-focus-visible` am sichtbaren
> Segment dargestellt.

### Abzeichen

Zwei Formen:

| Form | Kette | Einsatz |
|---|---|---|
| **Tag** (eckig) | `text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded border border-green-100` | Merkmale, Küchen (orange), Sprachen |
| **Pill** (rund) | `bg-purple-100 text-purple-700 text-xs font-semibold px-2.5 py-1 rounded-full` | Status, Linien, Kategorien |
| **Glas** (auf Verlauf) | `inline-block bg-white/20 text-white text-sm font-semibold px-4 py-1.5 rounded-full` | Hero-Auszeichnung |

Die Statusfarben kommen aus PHP, nicht aus dem Template:

```php
// src/Enum/WaitlistStatus.php
self::PENDING   => 'bg-amber-100 text-amber-800',
self::CONFIRMED => 'bg-blue-100 text-blue-700',
self::CONTACTED => 'bg-purple-100 text-purple-700',
self::QUALIFIED => 'bg-cyan-100 text-cyan-800',
self::CONVERTED => 'bg-green-100 text-green-700',
self::DECLINED  => 'bg-red-100 text-red-600',
```

Dieselbe Methode gibt es auf `OrganisationType` und `FinanceType`.
**`badgeClasses()` liefert ausschließlich Farbe** — Form und Größe bleiben im
Template. So lässt sich derselbe Status als Pille in der Liste und als breites
Abzeichen in der Detailansicht zeigen.

### Weitere Partials

| Partial | Parameter | Anmerkung |
|---|---|---|
| `_avatar.html.twig` | `user`, `size` (`sm`/`md`/`lg`) | Fallback sind die Initialen auf `bg-purple-100` |
| `_verified_badge.html.twig` | `restaurant`, `size` (`sm`/`lg`) | `bg-cyan-50 text-cyan-700 border border-cyan-200` |
| `_flash_messages.html.twig` | – | `border-l-4 … rounded-r-lg` + `role="alert"`; grün/rot/blau |
| `_hero_badges.html.twig` | `restaurant`, `centered` | Bewertungsampel: ≥7 grün, ≥4 amber, sonst rot |
| `_bottom_nav.html.twig` | – | `fixed bottom-0 … md:hidden`, `pb-[env(safe-area-inset-bottom)]`, Tap-Targets ≥ 44 px |
| `_cookie_banner.html.twig` | – | `role="dialog" aria-modal="false"` |
| `_form_field.html.twig` | `field`, `help`, `autofocus`, `rows` | siehe oben |
| `_tristate_field` / `_tristate_value` | `field`/`value`, `emoji`, `label` | Eingabe bzw. Anzeige |
| `_opening_hours.html.twig` | `restaurant`, `todayDayOfWeek` | heutiger Tag `bg-purple-50 border-purple-200` |
| `_nearby_stops.html.twig` | `nearbyStops`, `restaurant` | Linien als Pillen |
| `_waitlist_success` / `_waitlist_confirmation` | siehe Datei | von beiden Wartelisten geteilt |
| `_language_switcher` / `_admin_language_switcher` | – | helle bzw. dunkle Fassung desselben Menüs |
| `_passkey_login` / `_passkey_manage` | – | Anmeldung bzw. Verwaltung |
| `_cuisine_badges.html.twig` | `restaurant` | orange Tags |

### Stat-Kachel

`templates/open/_metric.html.twig` — die Kennzahlkachel der Transparenzseite:

```html
<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm break-inside-avoid">
  <p class="w-11 h-11 bg-cyan-50 rounded-xl flex items-center justify-center text-2xl mb-3" aria-hidden="true">…</p>
  <p class="text-sm font-semibold text-gray-700">…</p>
  <span class="text-3xl font-bold text-gray-900">…</span>
  <span class="text-base text-gray-500">…</span>
</div>
```

`break-inside-avoid` sorgt dafür, dass die Kachel beim Drucken nicht zerrissen
wird.

---

## Barrierefreiheit

Bei einer Plattform für Barrierefreiheit ist dieser Abschnitt kein Anhang. Die
folgenden Regeln sind im Bestand durchgesetzt und gelten für neue Arbeit
verbindlich.

### Fokus ist ein `outline`, kein `ring`

```
focus:outline-2 focus:outline-offset-2 focus:outline-<farbe>-700
```

Die Outline-Farbe spiegelt die Farbe des Elements. **Nirgends steht
`outline-none`.**

> Aus dem Kopfkommentar von `_form_field.html.twig`: Ringe per `box-shadow`
> verschwinden im Windows-Kontrastmodus. Eine echte `outline` bleibt.

Die ältere Konvention `focus:ring-2 focus:ring-purple-500 focus:outline-none`
lebt im Bestand weiter, vor allem im Admin — siehe
[Bekannte Abweichungen](#bekannte-abweichungen).

### Tap-Targets

`min-h-[48px]` bei öffentlichen Primäraktionen, `min-h-[44px]` im Admin und bei
sekundären Aktionen. 44 px ist das Minimum aus WCAG 2.2; 48 px ist die Größe, die
sich mit dem Daumen zuverlässig trifft.

### Farbe trägt nie allein

Jede farbcodierte Aussage hat eine zweite Codierung:

- Veränderungen zeigen **Vorzeichen und Farbe** (`{{ delta.value > 0 ? '+' : '−' }}`)
- Dreiwertige Antworten zeigen **Emoji, Text und Farbe**
- Balken sind `aria-hidden` — die **Zahl daneben** trägt die Aussage

### Weitere Konventionen

| Regel | Umsetzung |
|---|---|
| Dekoratives ist unsichtbar für Screenreader | `aria-hidden="true"` an allen Emojis und schmückenden SVGs |
| Bewegung ist optional | `motion-safe:transition` statt `transition` (kein `motion-reduce:` im Projekt) |
| Menüs melden ihren Zustand | `aria-expanded` am Sprachumschalter; die `<details>`-Navigation meldet sich selbst und braucht deshalb **kein** handgeschriebenes `aria-expanded` |
| Dynamische Änderungen werden angesagt | `aria-live="polite"` um Turbo-Stream-Ziele und beim Typwechsel im Organisationsformular |
| Fokus wandert nach dem Absenden mit | `tabindex="-1" autofocus role="status"` auf der Erfolgsmeldung |
| Tabellen haben eine Beschriftung | `<caption class="sr-only">` |
| Radios bleiben echte Inputs | `peer sr-only`, nie `hidden` |
| Aktive Navigation ist ausgezeichnet | `aria-current="page"` in der Bottom-Nav |

### Zwei offene Punkte

- **Es gibt keinen Skip-Link.** Tastaturnutzende durchlaufen auf jeder Seite die
  vollständige Navigation, bevor sie den Inhalt erreichen.
- **`home/`, `about/` und `community/` haben keinerlei Fokusgestaltung.** Dort
  gilt der Browser-Standard, der auf farbigem Grund schlecht sichtbar ist.

---

## Responsive

Konsequent mobile-first. Drei Breakpoints im Einsatz — `xl:` und `2xl:` kommen
nicht vor.

| Präfix | Bedeutung |
|---|---|
| `sm:` | Zweispaltigkeit **innerhalb** einer Karte: `grid grid-cols-1 sm:grid-cols-2 gap-3`, Buttonreihen `flex flex-col sm:flex-row` |
| `md:` | **der Desktop-Umschaltpunkt** |
| `lg:` | Layout-Spalten: `flex flex-col lg:flex-row gap-8` mit `lg:w-1/4` (Filter) bzw. `lg:w-1/5` (Admin-Sidebar) |

Was an `md:` umschaltet:

- Header-Navigation `hidden md:flex` ↔ Bottom-Navigation `md:hidden`
- `<main>` trägt `pb-16 md:pb-0`, damit Inhalt nicht hinter der Bottom-Nav liegt
- Wortmarke `hidden md:block`
- Typografie skaliert fast ausschließlich hier: `text-2xl md:text-3xl`
- Formular-Submit `w-full md:w-auto`

---

## Diagramme

Die Regeln stammen von der Transparenzseite `/open` und gelten für jede weitere
Datenvisualisierung. Die Partials liegen in `templates/open/`: `_metric`, `_bar`,
`_histogram`, `_sparkline`.

**Eine Farbe je Serie.** Eine frühere Ampelfärbung der Punkteverteilung
(grün/cyan/bernstein je nach Punktzahl) kodierte die Balkenlänge ein zweites Mal
als Farbe. Die Position trägt die Ordnung bereits; die Farbe fügte nichts hinzu
und Bernstein lag bei 1,49:1 Kontrast.

**Cyan für Ausgaben, Purple für Einnahmen.** Die beiden Markenfarben, geprüft:
ΔE 26,4 bei normalem Sehen, 13,6 bei Deuteranopie, beide über 3:1 gegen Weiß.

> **Kein Bernstein für Ausgaben.** Bernstein ist eine Warnfarbe. Betriebskosten
> sind kein Problem, und sie sollen auch nicht wie eines aussehen.

**Balken** (`_bar`) haben eine 4 px runde Datenkante und bleiben an der
Grundlinie eckig. Die Spur ist eine hellere Stufe derselben Farbe, nicht
neutrales Grau. Balken sind `aria-hidden`.

**Histogramm** (`_histogram`) nutzt Säulen, keine gestapelten Querbalken — die
Punktzahl ist eine geordnete Skala, und nur nebeneinander liest man die Form der
Verteilung. Die Säulen reichen auf **85 %** der Höhe; die oberen 15 % sind der
Streifen für das Wertlabel.

**Verlaufslinie** (`_sparkline`) ist reines SVG ohne Bibliothek, mit
`vector-effect="non-scaling-stroke"` für gleichbleibende 2 px Strichstärke.

> **Keine `<circle>`-Punkte.** `preserveAspectRatio="none"` streckt das
> Koordinatensystem — richtig für einen Zeitverlauf, aber es macht aus Kreisen
> Ellipsen. Der aktuelle Wert steht deshalb als Zahl über der Grafik.

**Jede Grafik hat eine Tabellen-Entsprechung**, aufklappbar in einem `<details>`
oder als eigene Tabelle daneben.

---

## Druckansicht

Angelegt für den PDF-Export von `/open` vor Fördergesprächen, wirkt aber auf
allen Seiten.

In `templates/base.html.twig` tragen Header, Footer, Bottom-Navigation und
Cookie-Banner `print:hidden`; `<main>` bekommt `print:pb-0`.

Der `@media print`-Block in `app.css` erledigt vier Dinge:

| Regel | Grund |
|---|---|
| `print-color-adjust: exact` | die Balkenfarben sind Daten, keine Dekoration |
| Verlaufsbänder verlieren Fläche **samt Textfarbe der Nachfahren** | sie tragen `text-white` — sonst stünde Weiß auf Weiß |
| `<details>` wird aufgeklappt, `<summary>` ausgeblendet | die Tabellenansichten der Diagramme sollen im PDF stehen |
| `figure, table { break-inside: avoid }` | keine zerrissenen Diagramme |

---

## E-Mail

`templates/email/base.html.twig` ist eine eigene Ebene: Inline-CSS und
Tabellenlayout, weil E-Mail-Clients weder Tailwind noch moderne Selektoren
zuverlässig unterstützen. Die Farbwerte sind dieselben:

| Element | Wert | Entspricht |
|---|---|---|
| Seitenhintergrund | `#f9fafb` | gray-50, wie `<body>` der Website |
| Karte | `#ffffff`, `border-radius: 12px`, `box-shadow: 0 1px 3px rgba(0,0,0,0.1)` | `rounded-xl shadow-sm` |
| Kopfbereich | `linear-gradient(135deg, #0891b2, #7c3aed)` | cyan-600 → violet-600 |
| `.lu` in der Wortmarke | `#e9d5ff` | purple-200 |
| Kleingedrucktes | `#9ca3af`, 11 px | gray-400 |

> **Wer die Markenfarbe ändert, muss diese Datei mit anfassen** — sie zieht keine
> Tailwind-Klasse nach.

---

## Stimulus-Controller

Zwölf eigene Controller in `assets/controllers/`, automatisch registriert über
`assets/controllers.json`.

| Controller | Aufgabe |
|---|---|
| `collection_form_controller` | Symfony-`CollectionType` dynamisch erweitern und kürzen |
| `cookie_consent_controller` | Banner zeigen, Wahl 365 Tage speichern; der Footer-Link öffnet es über ein Fenster-Event erneut |
| `csrf_protection_controller` | CSRF-Token als Double-Submit-Cookie |
| `hello_controller` | ungenutztes Symfony-Beispiel |
| `image_sort_controller` | Restaurantbilder per Drag & Drop sortieren (SortableJS) |
| `language_switcher_controller` | Sprachmenü, pflegt `aria-expanded` |
| `nav_dropdown_controller` | schließt das `<details>`-Menü bei Escape und Außenklick |
| `opening_hours_form_controller` | Zeitfenster pro Wochentag hinzufügen und entfernen |
| `organisation_type_controller` | typspezifische Formularblöcke umschalten, Wechsel in einer Live-Region ansagen |
| `passkey_ui_controller` | Sichtbarkeit, Ladezustand und übersetzte Fehlertexte rund um WebAuthn |
| `suggestion_wizard_controller` | fünfstufiger Vorschlags-Wizard samt Prüfung der Pflichtfragen |
| `tom_select_controller` | Küchen-Autocomplete mit Anlegen neuer Einträge |

Dazu registriert `assets/stimulus_bootstrap.ts` zwei Fremd-Controller aus
`@web-auth/webauthn-stimulus` unter eigenen Namen: `passkey-auth` und
`passkey-register`.

> **Diese beiden dürfen nicht in `controllers.json` stehen.** Das StimulusBundle
> löst jeden Eintrag dort gegen ein Composer-Paket auf und bricht den Build mit
> „Could not find package".

---

## Bekannte Abweichungen

Vier Stellen, an denen zwei Generationen nebeneinander laufen. Sie sind kein
Fehler, sondern gewachsener Bestand — die neueren Seiten sind strenger, weil die
Anforderungen dazugelernt haben. Die Tabelle existiert, damit eine spätere
Angleichung planbar ist, ohne dass jemand erneut zählen muss.

### 1. Fokusgestaltung

| | Kanon | Bestand |
|---|---|---|
| Kette | `focus:outline-2 focus:outline-offset-2 focus:outline-<farbe>-700` | `focus:ring-2 focus:ring-purple-500 focus:outline-none` |
| Vorkommen | 38 | 57 |

| Verzeichnis | Bestand | Kanon |
|---|---:|---:|
| `admin/` | 37 | 11 |
| `restaurant/` | 7 | 0 |
| `profile/` | 5 | 0 |
| `registration/` | 4 | 0 |
| `security/` | 3 | 0 |
| `partials/` | 1 | 7 |
| `organisation/` | 0 | 8 |
| `partner/` | 0 | 6 |
| `open/` | 0 | 6 |
| `home/`, `about/`, `community/` | 0 | 0 |

Die letzte Zeile ist die dringlichste: Dort gibt es **gar keine** Fokusgestaltung.

### 2. Buttons

| | Kanon | Bestand |
|---|---|---|
| Farbe | `bg-cyan-700 hover:bg-cyan-800` | `bg-purple-600 hover:bg-purple-700` |
| Mindesthöhe | `min-h-[48px]` | keine |
| Bewegung | `motion-safe:transition` | `transition transform hover:-translate-y-0.5` |
| Fokus | `focus:outline-2 …` | keiner |
| Verzeichnisse | `partner/`, `organisation/`, `open/`, `partials/` | `home/`, `security/`, `registration/`, `profile/`, `admin/`, `community/`, `base.html.twig` |

### 3. Hero-Verläufe

Zwei Stufen — `from-cyan-600 to-purple-700` und `from-cyan-700 to-purple-800`.
Die Zuordnung nach Publikum (siehe [Verläufe](#verläufe)) ist stimmig, war aber
bis zu diesem Dokument nirgends festgehalten.

### 4. Verlaufssyntax

`templates/restaurant/show.html.twig` nutzt noch das v3-Präfix
`bg-gradient-to-t`. Alle übrigen Stellen sind auf `bg-linear-to-*` umgestellt.

### Und eine Grundsatzfrage

**Es gibt keinen `@theme`-Block.** Jede Farbentscheidung wird in jedem Template
neu ausgeschrieben. Solange das System klein ist, funktioniert das; es bedeutet
aber, dass eine Änderung der Markenfarbe heute ein Suchen-und-Ersetzen über 77
Templates plus die E-Mail-Vorlage plus `manifest.webmanifest` wäre. Benannte
Tokens (`--color-brand`, `--color-accent`) würden das auf eine Stelle
zusammenziehen — der Umbau ist überschaubar, aber nicht kostenlos.
