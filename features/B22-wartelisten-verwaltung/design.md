# B22 · Wartelisten-Verwaltung (Admin) — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Ein Controller, sechs Routen, zwei Repositories. Der Kunstgriff ist die
**Normalisierung**: Beide Entity-Typen werden im Controller auf ein gemeinsames
Array-Format gebracht (`kind`, `id`, `name`, `detail`, `contact`, `status`, `type`,
`createdAt`, `route`), damit die Tabelle keine Klassenprüfung braucht.

Schreibende Vorgänge laufen über zwei private Methoden, die beide gegen
`WaitlistEntryInterface` arbeiten — dieselbe Abstraktion, die auch
`WaitlistConfirmationService` benutzt.

## Seiten und Routen

Alle `ROLE_ADMIN`, Präfix `/{_locale}/admin/warteliste`.

| Route | Pfad | Methode |
|---|---|---|
| `admin_waitlist_index` | `` | GET |
| `admin_waitlist_partner_show` | `/partner/{id}` | GET |
| `admin_waitlist_organisation_show` | `/organisation/{id}` | GET |
| `admin_waitlist_partner_status` | `/partner/{id}/status` | POST |
| `admin_waitlist_organisation_status` | `/organisation/{id}/status` | POST |
| `admin_waitlist_partner_link` | `/partner/{id}/restaurant` | POST |

Alle `{id}` mit Requirement `\d+`.

## Komponentenstruktur

```
admin/waitlist/index.html.twig
├── Filterleiste            Quelle · Status · Typ · Richtung
├── Zählerkacheln           partnerTotal · organisationTotal · typeCounts
└── Tabelle                 normalisierte Zeilen, Link je nach row.route

admin/waitlist/partner_show.html.twig
admin/waitlist/organisation_show.html.twig
├── admin/waitlist/_field.html.twig        Feldzeile
├── admin/waitlist/_status_form.html.twig  Statuswechsel, CSRF waitlist-status-{id}
└── admin/waitlist/_timestamps.html.twig   created · consent · confirmed · updated
```

## Datenmodell

B22 legt nichts an. Gelesen und geschrieben werden `partner_waitlist_entry` und
`organisation_waitlist_entry` (siehe B14, B15), gelesen zusätzlich `restaurant`.

Geschrieben werden genau drei Dinge: `status`, `confirmedAt` (nur nachtragend) und
`partner_waitlist_entry.restaurant_id`.

**Repository-Methoden:**

| Methode | Zweck |
|---|---|
| `PartnerWaitlistEntryRepository::findFiltered($status, $direction)` | gefilterte Liste |
| `OrganisationWaitlistEntryRepository::findFiltered($type, $status, $direction)` | dito |
| `countByStatus()`, `countByType()` | Zählerkacheln |
| `count([])` | Gesamtzahlen |

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast, `ROLE_USER` | nichts | nichts | `access_control` `^/[a-z]{2}/admin` + `#[IsGranted('ROLE_ADMIN')]` |
| `ROLE_ADMIN` | alle Einträge beider Wartelisten | Status, `confirmedAt`, Restaurantzuordnung | dieselbe Schranke |

Kein Voter, keine Feingliederung innerhalb der Admin-Rolle.

## Missbrauchsschutz

| Endpunkt | Schutz |
|---|---|
| `…_status` (beide) | CSRF `waitlist-status-{id}` + `WaitlistStatus::tryFrom()` |
| `…_link` | CSRF `waitlist-link-{id}` + Existenzprüfung des Restaurants |
| Anzeigerouten | Rollenschranke, `ParamConverter` mit `\d+` |

Kein Rate Limit (B19/FB-05), kein Audit-Log (B19/FB-02).

## Externe Dienste

Keine. B22 verschickt nichts — die interne Meldung entsteht bei der Bestätigung durch
den Interessenten (B14/B15), nicht hier.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md`.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01, AK-02 | `index()`, `partnerRow()`/`organisationRow()`, `usort()` nach dem Zusammenführen |
| AK-03 | Vergleich gegen `SOURCE_PARTNER` / `SOURCE_ORGANISATION` |
| AK-04 | `if ($organisationType) { $source = SOURCE_ORGANISATION; }` |
| AK-05 | `WaitlistStatus::tryFrom()` → an beide `findFiltered()` |
| AK-06 | `'asc' === strtolower(...) ? 'ASC' : 'DESC'` |
| AK-07 | `tryFrom()` liefert `null`, der Filter entfällt |
| AK-08 | `partner_show` / `organisation_show` mit `_field`-Partial |
| AK-09, AK-10 | `applyStatus()` |
| AK-11, AK-21 | `isCsrfTokenValid('waitlist-status-' . $id, …)` |
| AK-12 | `WaitlistStatus::tryFrom()` → `flash.waitlist_status_invalid` |
| AK-13, AK-14, AK-15 | `linkRestaurant()`, drei Zweige |
| AK-16 | Rollenschranke |
| AK-17 ⚠ | `findFiltered()` ohne Limit | Lücke, FB-05 |
| AK-18 ⚠ | `findBy([], ['name' => 'ASC'])` in `showPartner()` | Lücke |
| AK-19 ⚠ | **Abwesenheit** eines Audit-Logs | Lücke, FB-04 |
| AK-20 | Feldbestand beider Entities |
| AK-22 | `ParamConverter`, Requirement `\d+` |

## Für `sdd-qa` besonders zu prüfen

1. **FB-01** — der fehlende Löschweg ist die Verwaltungsseite der DSGVO-Lücke aus B14.
2. **AK-11** — Statuswechsel ohne Token für **beide** Entity-Typen prüfen; sie laufen
   durch dieselbe Methode, aber über getrennte Routen.
3. **AK-04** — die implizite Quellensetzung ist leicht zu übersehen und beeinflusst,
   was der Betreiber zu sehen glaubt.
