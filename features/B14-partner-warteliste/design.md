# B14 · Partner-Warteliste — Systemdesign

Status: `rekonstruiert` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Landing-Page mit Formular, Double-Opt-In über einen geteilten Service, Turbo-Stream für
die Erfolgsantwort. Die gesamte Bestätigungsmechanik liegt in `src/Waitlist/` und wird
mit B15 geteilt — der Controller liefert nur Route, Vorlage und Betreffschlüssel.

## Seiten und Routen

| Route | Pfad | Methode | Zugang |
|---|---|---|---|
| `app_partner` | `/{_locale}/partner` | GET | öffentlich |
| `app_partner_submit` | `/{_locale}/partner` | POST | öffentlich, 5/h je IP |
| `app_partner_confirm` | `/{_locale}/partner/confirmation/{token}` | GET | öffentlich, `token` = `[a-f0-9]{64}` |

## Komponentenstruktur

```
partner/index.html.twig            Landing-Page
└── partner/_form.html.twig        id="partner-waitlist-form"  ← Ziel des Turbo-Streams
    └── PartnerWaitlistType        + Honeypot-Feld `website`
partner/success.stream.html.twig   <turbo-stream action="replace" target="partner-waitlist-form">
└── partials/_waitlist_success.html.twig     mit B15 geteilt
partner/confirmation.html.twig     drei Zustände in einer Vorlage
└── partials/_waitlist_confirmation.html.twig  mit B15 geteilt
email/partner/confirmation.html.twig          an den Interessenten
email/partner/internal_notification.html.twig ans Team
```

## Datenmodell

### Tabelle `partner_waitlist_entry`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT | ja | |
| `restaurant_name` | VARCHAR(180) | ja | |
| `contact_name` | VARCHAR(120) | ja | |
| `email` | VARCHAR(180) | ja | Empfänger der Bestätigung |
| `phone` | VARCHAR(40) | nein | |
| `locality` | VARCHAR(120) | ja | |
| `restaurant_id` | FK → `restaurant`, **SET NULL** | nein | Zuordnung durch den Admin (B22) |
| `message` | TEXT | nein | Freitext |
| `status` | VARCHAR(20), Enum `WaitlistStatus` | ja | `pending` → … |
| `confirmation_token` | VARCHAR(64) **UNIQUE** | nein | bleibt nach Bestätigung stehen |
| `confirmed_at` | DATETIME | nein | |
| `consent_at` | DATETIME | **ja** | Rechtsgrundlage |
| `locale` | VARCHAR(5) | ja | Sprache der Anmeldung |
| `source` | VARCHAR(60) | nein | UTM oder Referrer-Host |
| `created_at`, `updated_at` | DATETIME | ja | `updated_at` über `#[ORM\PreUpdate]` |

**Index:** Kombi `(status, created_at)` — im Mapping **und** in der Migration
deklariert, sonst meldet `doctrine:schema:validate` eine Abweichung.

Migration: `Version20260820000000`.

**Kein Feld für Vertragspartner, Rechnungsnummer oder Preis** — was nicht erfasst ist,
kann nicht versehentlich veröffentlicht werden.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | den eigenen Eintrag über den Token | anlegen, bestätigen | Token ist das Geheimnis |
| Gast | **keine** fremden Einträge | — | Token mit 64 Hex-Zeichen; Routen-Requirement weist alles andere ab |
| `ROLE_ADMIN` | alle | Status, Restaurantzuordnung | B22 |

Es gibt **keinen** Weg, einen Eintrag über eine fortlaufende ID abzurufen — die
öffentliche Route kennt nur den Token.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten | Wo |
|---|---|---|---|
| `app_partner_submit` | 5/Stunde je IP, `sliding_window` | 429 + Flash | `limiter.partner_waitlist`, `#[Autowire]` im Controller |
| dito | Honeypot `website` | Erfolgsantwort ohne Wirkung | Controller |
| `app_partner` (GET) | keins | — | bewusst: Lesen verbraucht nichts |
| `app_partner_confirm` | keins | — | Token mit 256 Bit Entropie |

⚠ Der Limiter-Service wird mit B15 geteilt — siehe AK-23.

## Externe Dienste

| Dienst | Wofür | Was geht hin |
|---|---|---|
| Brevo | Bestätigungsmail an den Interessenten | alle Formularangaben (über die Vorlage) + Bestätigungs-URL mit Token |
| Brevo | interne Meldung ans Team | dieselben Daten, `Reply-To` = Interessent |

Empfänger der internen Meldung: `%app.contact_email%` aus der Env `CONTACT_EMAIL`.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md` — acht Entscheidungen, alle im Quelltext begründet.

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch |
|---|---|
| AK-01 | `PartnerController::index()` |
| AK-02 | `submit()`, `setConsentAt/Locale/Source`, `WaitlistRequestHelper::resolveSource()` |
| AK-03, AK-04 | `WaitlistConfirmationService::register()`, Rückgabewert `bool` |
| AK-05, AK-06 | `successResponse()`, Weiche über `getPreferredFormat()` |
| AK-07 | `AbstractController::render()` setzt 422 selbst; **kein** `setRequestFormat` auf diesem Pfad |
| AK-08 | Honeypot-Prüfung auf `website` vor der Gültigkeitsprüfung |
| AK-09, AK-10 | Limiter-Aufruf **nach** `handleRequest()` |
| AK-11 | `confirm()` → `RESULT_CONFIRMED` → `notifyTeam()` |
| AK-12, AK-13 | `WaitlistConfirmationService::confirm()`, drei Rückgabewerte; 404 bei `RESULT_INVALID` |
| AK-14 | Routen-Requirement `[a-f0-9]{64}` |
| AK-15 | `notifyTeam()`, `trans(…, null, 'de')`, `replyTo()` |
| AK-16 | `try`/`catch` in `notifyTeam()` |
| AK-17 | Feldbestand der Tabelle |
| AK-18, AK-19 | `PartnerWaitlistType`, `partner/_form.html.twig` |
| AK-20 | Spalte `consent_at`, NOT NULL |
| AK-21 ⚠ | **Abwesenheit** eines Ablaufzeitpunkts | Lücke, FB-03 |
| AK-22 ⚠ | **Abwesenheit** eines Widerrufswegs | Lücke, FB-01 |
| AK-23 ⚠ | geteilter Limiter-Service | Lücke |

## Für `sdd-qa` besonders zu prüfen

1. **AK-22 / FB-01** — der Widerruf ist eine Rechtspflicht, keine Ausbaustufe.
2. **FB-02** — `findPendingOlderThan()` ist toter Code; prüfen, ob irgendwo aufgerufen.
3. **AK-08** — Honeypot füllen und die Antwort mit dem Erfolgsfall byteweise vergleichen.
4. **AK-07** — ungültiges Formular mit aktivem Turbo abschicken; die Antwort muss
   `text/html` sein, nicht `turbo-stream`.
