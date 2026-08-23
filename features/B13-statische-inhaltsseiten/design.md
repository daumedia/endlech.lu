# B13 · Statische Inhaltsseiten — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Drei Controller mit je einer Methode, die nichts tut außer rendern. Kein
Datenbankzugriff, keine Dienste, keine Zustände.

## Seiten und Routen

| Route | Pfad | Vorlage | Zugang |
|---|---|---|---|
| `app_about` | `/{_locale}/about` | `about/index.html.twig` | öffentlich |
| `app_kriterien` | `/{_locale}/criteria` | `kriterien/index.html.twig` | öffentlich |
| `app_impressum` | `/{_locale}/legal` | `impressum/index.html.twig` | öffentlich |

## Komponentenstruktur

```
about/index.html.twig       Mission · Person · Zeitleiste
kriterien/index.html.twig   Kriterienkatalog
impressum/index.html.twig
└── <section id="datenschutz" class="… scroll-mt-24">   ← Ziel des Cookie-Banners (B26)
```

Bilder des Teams liegen unter `public/uploads/team/` — ⚠ dieses Verzeichnis ist per
`!`-Regel aus `.gitignore` ausgenommen; nicht committete Dateien dort löscht der Deploy
(`git clean -fd`).

## Datenmodell

Keines.

## Zugriffsregeln

Keine.

## Missbrauchsschutz

Keiner nötig.

## Externe Dienste

Keine.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02, AK-03 | die drei Controller und ihre Vorlagen |
| AK-04 | `<section id="datenschutz" class="… scroll-mt-24">` |
| AK-05 | keine `access_control`-Regel |
| AK-06 | Übersetzungskataloge, `/{_locale}`-Routing (B24) |
| AK-07 ⚠ | Vorlageninhalt, nicht gegenprüfbar | Lücke, FB-01 |
| AK-08 ⚠ | fehlende Herleitung im Katalog | Lücke, FB-03 |
| AK-09, AK-10 | Vorlageninhalt; keine Abfragen |

## Für `sdd-qa` besonders zu prüfen

1. **AK-07** — die Datenschutzerklärung Satz für Satz gegen die tatsächlichen
   Datenflüsse halten: Brevo, Sentry, HAFAS, Wartelisten mit Einwilligungszeitpunkt,
   Avatare im Web-Root, offener Datensatz unter CC BY. Das ist der einzige Punkt dieses
   Features, an dem etwas schiefgehen kann — und der einzige mit rechtlicher Folge.
2. **AK-04** — dem Cookie-Link folgen und prüfen, ob die Überschrift sichtbar ist.
