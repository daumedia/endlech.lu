# B24 · Mehrsprachigkeit — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Reine Konfiguration plus ein Partial und ein Stimulus-Controller. Die Sprache steckt im
Pfad, wird von Symfonys Router aufgelöst und von der Übersetzungskomponente benutzt —
es gibt keinen eigenen `LocaleSubscriber`.

## Seiten und Routen

Keine eigenen. Der Präfix `/{_locale}` liegt am `controllers`-Loader in
`config/routes.yaml`:

```
prefix: /{_locale}
requirements: { _locale: lb|de|fr|en }
defaults:     { _locale: lb }
exclude: [ '../src/Controller/Api/V1/', '../src/Controller/Open/' ]
```

⚠ Der `exclude` ist eine **Liste** mit zwei Einträgen. Fehlt einer, landet die
betroffene Schnittstelle unter `/{_locale}/…`.

## Komponentenstruktur

```
config/packages/translation.yaml   default_locale: lb · enabled_locales: [lb, de, fr, en]
translations/
├── messages.{lb,de,fr,en}.yaml    Oberflächentexte
└── validators.{lb,de,fr,en}.yaml  Validierungsmeldungen

templates/base.html.twig
├── <html lang="{{ app.request.locale }}">
├── hreflang-Schleife über ['lb','de','fr','en'] + x-default
└── partials/_language_switcher.html.twig    (hidden md:block)
    └── language_switcher_controller.ts       toggle · close · aria-expanded · Pfeil

templates/partials/_admin_language_switcher.html.twig  → B19, Sitzung statt Pfad
```

## Datenmodell

Keines. Die Sprache ist Zustand der Anfrage, nicht der Anwendung.

Ausnahme: `partner_waitlist_entry.locale` und `organisation_waitlist_entry.locale`
speichern die Sprache der Anmeldung — damit die Bestätigungsmail in derselben Sprache
kommt (B14, B15).

## Zugriffsregeln

Keine. ⚠ Die `access_control`-Muster berücksichtigen den Präfix als `^/[a-z]{2}/…` —
eine Erweiterung um eine fünfte Sprache mit anderem Codeformat bräche sie.

## Missbrauchsschutz

| Aspekt | Vorhanden |
|---|---|
| Wertprüfung | Routen-Requirement `lb\|de\|fr\|en` → 404 bei allem anderen |
| Kein Zustand | keine Speicherung im öffentlichen Bereich (AK-15) |

## Externe Dienste

Keine. `twig/intl-extra` ist eine lokale Abhängigkeit.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02 | `controllers`-Loader in `config/routes.yaml` |
| AK-03 | `path(current_route, current_params\|merge(…)\|merge(query_params))` |
| AK-04 | `{% if locale != current_locale %}` im Partial |
| AK-05 | `language_switcher_controller.ts`, `setAttribute('aria-expanded', …)` |
| AK-06, AK-07 | hreflang-Schleife in `base.html.twig`, `_current_route != 'app_root'` |
| AK-08 | `<html lang="{{ app.request.locale }}">` |
| AK-09 | Symfony-Vorgabe |
| AK-10 | `twig/intl-extra` |
| AK-11 | `validators.*.yaml` |
| AK-12 ⚠ | Merge-Reihenfolge im Partial | Lücke, FB-03 |
| AK-13 ⚠ | `hidden md:block` um den Umschalter | Lücke, FB-01 |
| AK-14 ⚠ | `app_root` mit festem `_locale: lb` | Lücke, FB-02 |
| AK-15 | kein Cookie, keine Sitzung im öffentlichen Bereich |
| AK-16 | Routen-Requirement |

## Für `sdd-qa` besonders zu prüfen

1. **AK-12** — eine Seite mit `?_locale=en` aufrufen und den Umschalter bedienen.
2. **AK-13** — die Seite auf einem Telefon öffnen und versuchen, die Sprache zu
   wechseln.
3. **FB-04** — `php bin/console debug:translation lb` und `… fr` laufen lassen und
   fehlende Schlüssel zählen. Das ist die einzige systematische Prüfung, die es für
   dieses Feature gibt.
