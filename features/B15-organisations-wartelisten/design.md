# B15 · Organisations-Wartelisten — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Formulartyp mit drei Feldblöcken, deren Aufbau von zwei verschiedenen
Form-Events abhängt: `PRE_SET_DATA` baut **alle** Blöcke (damit die Seite ohne
JavaScript bedienbar bleibt), `PRE_SUBMIT` nur den **übermittelten** (damit ein
Fremdfeld ein unerlaubtes Zusatzfeld ist und 422 auslöst).

Diese Asymmetrie ist der ganze Entwurf. Alles andere — Double-Opt-In, Honeypot, Rate
Limit, Turbo-Stream — ist mit B14 geteilt.

## Seiten und Routen

| Route | Pfad | Methode | Zugang |
|---|---|---|---|
| `app_organisations` | `/{_locale}/organisationen` | GET | öffentlich, `?type=` wählt vor |
| `app_organisations_type` | `/{_locale}/organisationen/{slug}` | GET | `slug` = `gemeinden\|unternehmen\|vereine` |
| `app_organisations_submit` | `/{_locale}/organisationen` | POST | öffentlich, 5/h je IP |
| `app_organisations_confirm` | `/{_locale}/organisationen/confirmation/{token}` | GET | öffentlich |

## Komponentenstruktur

```
organisation/index.html.twig       Übersicht: Hero · 3 Karten · Integrität · Formular
organisation/type.html.twig        Zielgruppenseite
├── organisation/_section_commune.html.twig      nur auf /gemeinden
├── organisation/_section_company.html.twig      nur auf /unternehmen
├── organisation/_section_association.html.twig  nur auf /vereine
├── organisation/_integrity.html.twig            auf allen vier Seiten
└── organisation/_form.html.twig
    └── data-controller="organisation-type"      blendet um, setzt disabled, aria-live
organisation/success.stream.html.twig  → partials/_waitlist_success.html.twig
organisation/confirmation.html.twig    → partials/_waitlist_confirmation.html.twig
email/organisation/{commune,company,association}.html.twig
email/organisation/internal_notification.html.twig
```

## Datenmodell

### Tabelle `organisation_waitlist_entry`

Gemeinsame Felder (identisch zu B14, soweit sinnvoll): `type`, `organisation_name`,
`contact_name`, `contact_role`, `email`, `phone`, `website`, `message`, `status`,
`confirmation_token`, `confirmed_at`, `consent_at`, `locale`, `source`, `created_at`,
`updated_at`.

Typspezifisch, **alle nullable**:

| Feld | Typ | nur für |
|---|---|---|
| `commune_name` | VARCHAR | `commune` |
| `estimated_venues` | INT | `commune` |
| `timeframe` | Enum `OrganisationTimeframe` | `commune` |
| `sponsorship_interests` | JSON `string[]` | `company` |
| `collaboration_interests` | JSON `string[]` | `association` |

Enums: `OrganisationType`, `OrganisationTimeframe`, `SponsorshipInterest`,
`CollaborationInterest`. Migration: `Version20260820100000`.

Beide Wartelisten-Entities erfüllen `App\Waitlist\WaitlistEntryInterface` — das ist die
Grundlage für den geteilten Service **und** die kombinierte Verwaltungsliste (B22).

## Zugriffsregeln

Identisch zu B14: Der Token ist das einzige Geheimnis, es gibt keine öffentliche Route
über eine fortlaufende ID, alle Verwaltungssichten hängen an `ROLE_ADMIN` (B22).

## Missbrauchsschutz

| Endpunkt | Limit | Wo |
|---|---|---|
| `app_organisations_submit` | 5/Stunde je IP | `limiter.partner_waitlist` — ⚠ **geteilt mit B14** |
| dito | Honeypot `companyWebsite` | Controller |
| Feldebene | `PRE_SUBMIT` + `validation_groups` | verhindert Feld-Unterschieben (AK-07/AK-08) |

## Externe Dienste

Brevo, wie B14 — drei typspezifische Bestätigungsvorlagen statt einer, eine gemeinsame
interne Meldung.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`. Ergänzend:

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 9 | Vorauswahl über Query-Parameter statt JavaScript | `?type=commune` | die Bereichskarten springen mit passendem Typ ans Ziel, auch ohne Skript |
| 10 | Selektor bleibt auf den Unterseiten sichtbar | ausblenden | wer falsch gelandet ist, wechselt ohne Umweg |
| 11 | `disabled` statt nur `hidden` beim Umschalten | nur CSS | ausgeblendete, aber aktive Felder blieben in der Tab-Reihenfolge und würden mitgesendet |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `OrganisationController::index()`, `organisation/index.html.twig` |
| AK-02 | `OrganisationType::tryFrom($request->query->getString('type'))` |
| AK-03 | `type()`, `OrganisationType::fromSlug()`, `organisation/type.html.twig` |
| AK-04 | Routen-Requirement |
| AK-05, AK-19 | `PRE_SET_DATA` in `OrganisationWaitlistType` |
| AK-06 | `organisation_type_controller.ts` |
| AK-07, AK-18 | `PRE_SUBMIT` in `OrganisationWaitlistType` |
| AK-08 | `validation_groups`-Callback, `IsNull` / `Count(max: 0)` in den fremden Gruppen |
| AK-09 | `'email/organisation/' . $type->value . '.html.twig'` |
| AK-10 | `$entry->getType() ?? OrganisationType::COMMUNE` |
| AK-11 | Honeypot-Prüfung auf `companyWebsite` |
| AK-12 | geteilter `WaitlistConfirmationService` |
| AK-13 | `_section_*`-Partials nur in `type.html.twig` eingebunden |
| AK-14 ⚠ | `flash.partner_rate_limited` auch im Organisations-Controller | Lücke, FB-05 |
| AK-15 ⚠ | geteilte Entity- und Service-Logik | Lücken, FB-01/FB-03 |
| AK-16 | Feldbestand der Tabelle |
| AK-17 | JSON-Spalten, `enumChoices()` liefert Strings |

## Für `sdd-qa` besonders zu prüfen

1. **AK-05** — JavaScript abschalten und das Formular vollständig ausfüllen. Das ist
   das erklärte Entwurfsziel und leicht zu brechen.
2. **AK-07** — `estimatedVenues` in einen Vereins-Submit einschmuggeln; erwartet 422.
3. **FB-01** — Widerruf, wie bei B14 eine Rechtspflicht.
