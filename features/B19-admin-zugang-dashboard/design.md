# B19 · Admin-Zugang & Dashboard — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Layout, ein Controller, ein Service. `admin/base.html.twig` erbt von der
öffentlichen Shell und setzt darin ein dunkles Kopfband plus Seitenleiste;
`AdminDashboardController` reicht neun Werte aus `AdminStatsService` ins Template.
Die Rollenschranke ist Konfiguration, kein Code.

## Seiten und Routen

| Route | Pfad | Zugang |
|---|---|---|
| `admin_dashboard` | `/{_locale}/admin` | `ROLE_ADMIN` |
| `admin_set_locale` | `/{_locale}/admin/locale/{locale}` | `ROLE_ADMIN`, `locale` = `lb\|de\|fr\|en` |

## Komponentenstruktur

```
admin/base.html.twig                    extends base.html.twig, füllt {% block body %}
├── Kopfband  bg-linear-to-r from-gray-800 to-gray-900
│   ├── partials/_admin_language_switcher.html.twig
│   └── Link „zurück zur Website"
├── <aside> Seitenleiste  lg:w-1/5, sticky top-24
│   └── fünf Punkte: Dashboard · Restaurants · Vorschläge · Warteliste · Finanzen
└── <main> lg:w-4/5
    ├── partials/_flash_messages.html.twig   (zweites Mal, zusätzlich zur Shell)
    └── {% block admin_body %}

admin/dashboard.html.twig
├── sieben Kennzahl-Kacheln
├── Liste: fünf zuletzt angelegte Restaurants
└── Liste: fünf zuletzt registrierte Nutzer
```

## Datenmodell

B19 legt nichts an. `AdminStatsService` ruft acht Zählungen und zwei Listen ab:

| Methode | Repository | Abfrage |
|---|---|---|
| `getRestaurantCount()` | `RestaurantRepository::count()` | alle |
| `getVerifiedCount()` | `countVerified()` | `isVerified = true` |
| `getPendingSuggestionCount()` | `RestaurantSuggestionRepository::countPending()` | Status `pending` |
| `getUserCount()` | `UserRepository::count()` | alle |
| `getImageCount()` | `RestaurantImageRepository::count()` | alle |
| `getRestaurantsAddedThisMonth()` | `countCreatedSince()` | ab Monatsersten |
| `getUsersRegisteredThisMonth()` | `countRegisteredSince()` | ab Monatsersten |
| `getRecentRestaurants(5)` / `getRecentUsers(5)` | `findRecent()` | `createdAt DESC`, 5 |

Kein Cache — jeder Aufruf trifft die Datenbank (FB-06).

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| Gast | nichts | `access_control` `^/[a-z]{2}/admin` = `ROLE_ADMIN`, Umleitung über `entry_point` |
| `ROLE_USER` | nichts | dieselbe Regel → 403 |
| `ROLE_ADMIN` | alles unter `/admin` | Regel + `#[IsGranted('ROLE_ADMIN')]` an jeder Admin-Controller-Klasse |

**Vergabe der Rolle:** ausschließlich über `user.roles` in der Datenbank (JSON-Spalte).
`User::getRoles()` hängt `ROLE_USER` zur Laufzeit an; `ROLE_ADMIN` muss gespeichert
sein. Es gibt keine Oberfläche dafür — die Fixtures legen `admin@endlech.lu` an.

Kein Voter, keine Rollenhierarchie konfiguriert.

## Missbrauchsschutz

| Endpunkt | Schutz |
|---|---|
| alle Anzeigerouten | Rollenschranke |
| `admin_set_locale` | Rollenschranke + Routen-Requirement; **keine Prüfung des Referers** (FB-04) |
| Schreibrouten der Unterbereiche | jeweils CSRF-Token, siehe B20–B22, B18 |

Kein Rate Limit, kein Audit-Log.

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`. Ergänzend:

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 6 | Aktiv-Erkennung über `starts with` statt exaktem Vergleich | Routenliste je Bereich | eine Zeile je Punkt; funktioniert, weil alle Routen eines Bereichs denselben Namenspräfix tragen |
| 7 | `_flash_messages` zweimal eingebunden | einmal | in der Shell über dem Admin-Kopfband, im Admin-Inhalt nochmal — Meldungen erscheinen im Verwaltungsbereich **doppelt**, sofern die Shell-Ausgabe nicht überschrieben wird ⚠ |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02, AK-13 | `access_control` + `#[IsGranted('ROLE_ADMIN')]` |
| AK-03 | `AdminDashboardController::dashboard()`, `AdminStatsService` |
| AK-04 | `getRecentRestaurants(5)`, `getRecentUsers(5)` |
| AK-05 | `new \DateTimeImmutable('first day of this month midnight')` |
| AK-06 | `AdminLocaleController::setLocale()` |
| AK-07 | Routen-Requirement `lb\|de\|fr\|en` |
| AK-08 | `{% if not (…_route) starts with 'admin_' %}` in `base.html.twig` |
| AK-09 | Klassenausdrücke in `admin/base.html.twig` |
| AK-10 ⚠ | Sitzungswert **ohne Leser** | Lücke, siehe FB / OF-02 |
| AK-11 ⚠ | `$this->redirect($referer ?: …)` ohne Herkunftsprüfung | Lücke, FB-04 |
| AK-12 | `getRecentUsers()` liefert vollständige `User`-Objekte ans Template |
| AK-14 | **Abwesenheit** einer Rollenverwaltung | Lücke, FB-01 |

## Für `sdd-qa` besonders zu prüfen

1. **AK-11** — `admin_set_locale` mit fremdem `Referer` aufrufen. Ein Einzeiler mit
   `$request->getSchemeAndHttpHost()`-Abgleich behebt es.
2. **AK-10** — Sprache umstellen und beobachten, ob sie wirkt.
3. **Entscheidung 7** — prüfen, ob Flash-Meldungen im Admin doppelt erscheinen.
