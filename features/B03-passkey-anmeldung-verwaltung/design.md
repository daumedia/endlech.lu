# B03 · Passkey-Anmeldung & -Verwaltung — Systemdesign

Status: `approved` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

`web-auth/webauthn-symfony-bundle` ^5.3.5 liefert die Kryptografie und zwei
Controller-Sätze für die Challenge-Endpunkte. Alles Anwendungsseitige ist selbst
geschrieben: ein Authenticator, der die Assertion aus einem **Formularfeld** liest, ein
Repository, das Passkeys in eine eigene Entity legt, und ein Übersetzer zwischen
`User` und `PublicKeyCredentialUserEntity`.

Der Kunstgriff des Entwurfs: Der Passkey läuft nicht neben dem Passwort-Login, sondern
**durch dieselbe Firewall und denselben `check_path`**. Damit erbt er Weiterleitung,
Zielpfad-Merker und „Angemeldet bleiben", ohne dass etwas davon zweimal existiert.

Das Flex-Recipe des Bundles liegt nur in `recipes-contrib` und wird wegen
`extra.symfony.allow-contrib: false` übersprungen — `bundles.php`,
`config/packages/webauthn.yaml` und `config/routes/webauthn.yaml` sind von Hand
angelegt.

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `webauthn.controller.request.request.login` | `/passkey/login/options` (sprachfrei) | Challenge zum Anmelden | `PUBLIC_ACCESS` |
| `app_login` | `/{_locale}/login` | nimmt die Assertion als `_assertion` entgegen | `PUBLIC_ACCESS` |
| `webauthn.controller.creation.request.add_device` | `/passkey/register/options` | Challenge zum Anlegen | `IS_AUTHENTICATED_FULLY` |
| `webauthn.controller.creation.response.add_device` | `/passkey/register` | Passkey speichern | `IS_AUTHENTICATED_FULLY` |
| `app_passkey_rename` | `/{_locale}/profile/passkeys/{id}/umbenennen` | umbenennen | `IS_AUTHENTICATED_FULLY` + Besitzprüfung |
| `app_passkey_delete` | `/{_locale}/profile/passkeys/{id}/loeschen` | entfernen | dito |
| — | `/.well-known/webauthn` | Origin-Auskunft des Bundles | öffentlich |

Die vier Bundle-Routen sind **sprachfrei** und stehen deshalb in `access_control`
**vor** den Web-Regeln — sonst griffe keine.

## Komponentenstruktur

```
Anmelden
security/login.html.twig
└── partials/_passkey_login.html.twig
    ├── data-controller="passkey-ui"        Feature-Detection, Ladezustand, Meldungen
    └── <form action=app_login>
        ├── data-controller="passkey-auth"  @web-auth/webauthn-stimulus
        ├── input[name=_assertion]          nimmt die Antwort des Browsers auf
        └── button (type=button)            löst passkey-auth#authenticate aus

Verwalten
profile/index.html.twig
└── partials/_passkey_manage.html.twig
    ├── Liste je Passkey: Name · angelegt am · zuletzt benutzt
    ├── <form> umbenennen   (CSRF rename-passkey-{id})
    ├── <form> löschen      (CSRF delete-passkey-{id}, onsubmit confirm)
    └── <button> anlegen    data-controller="passkey-register"  ← braucht JavaScript
```

Beteiligte Klassen:

| Klasse | Rolle |
|---|---|
| `App\Security\PasskeyAuthenticator` | erbt `WebauthnAuthenticator`, liest `_assertion`, eigene Erfolgs- und Fehlerbehandlung |
| `App\Security\WebauthnUserEntityRepository` | `User` ⇄ `PublicKeyCredentialUserEntity`, erzeugt den Handle bei Bedarf |
| `App\Repository\WebauthnCredentialRepository` | `CredentialRecordRepositoryInterface` + `CanSaveCredentialRecord` |
| `App\Entity\WebauthnCredential` | erbt `Webauthn\CredentialRecord` |
| `App\Controller\PasskeyController` | umbenennen, löschen |

⚠ Die Stimulus-Controller des Fremdpakets sind in `assets/stimulus_bootstrap.ts`
registriert, **nicht** in `assets/controllers.json` — das StimulusBundle löst jeden
Eintrag dort gegen ein Composer-Paket auf und bricht mit „Could not find package".

## Datenmodell

### Tabelle `webauthn_credential`

Die geerbten Felder stammen aus `Webauthn\CredentialRecord`; das Bundle registriert
dafür selbst eine mapped-superclass und trägt fünf DBAL-Typen (`base64`, `aaguid`,
`trust_path`, …) über `WebauthnExtension::prepend()` ein. Für die geerbten Felder
braucht es deshalb **keine** ORM-Attribute.

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | ja | Primärschlüssel |
| `user_id` | INT, FK → `user.id`, **ON DELETE CASCADE** | ja | Besitzer |
| `name` | VARCHAR | ja | Anzeigename, beim Anlegen aus dem User-Agent geraten |
| `created_at` | DATETIME | ja | |
| `last_used_at` | DATETIME | nein | `null` = nie benutzt |
| `public_key_credential_id` | **LONGTEXT** | ja | Suchschlüssel jeder Anmeldung |
| `credential_public_key`, `aaguid`, `counter`, `trust_path`, `user_handle`, … | geerbt | | aus `CredentialRecord` |

⚠ **Die geerbten Spalten sind LONGTEXT**, weil der DBAL-Typ `base64` sich als CLOB
deklariert. `public_key_credential_id` ist deshalb nur mit Längenangabe indizierbar:
`#[ORM\Index(..., options: ['lengths' => [100]])]`.

### Ergänzung an `user`

| Feld | Typ | Bedeutung |
|---|---|---|
| `webauthn_handle` | VARCHAR(64), **UNIQUE**, nullable | 16 Zufallsbytes in Hex; entsteht beim ersten Passkey |

**Beziehung:** `User` 1—n `WebauthnCredential`, `orphanRemoval: true`,
`OrderBy createdAt DESC`. Löschsicherheit doppelt: Kaskade in der Datenbank **und**
`orphanRemoval` im Mapping.

Migration: `Version20260821000000`.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | Challenge zum Anmelden anfordern | — | `access_control` `^/passkey/login/options` = `PUBLIC_ACCESS` |
| Angemeldeter | eigene Passkeys (`app.user.passkeys` im Template) | eigene anlegen | `access_control` `^/passkey/register` = `IS_AUTHENTICATED_FULLY`, `user_entity_guesser: CurrentUserEntityGuesser` |
| Angemeldeter | — | eigene umbenennen/löschen | `PasskeyController::denyUnlessOwnedByCurrentUser()` → 403 |
| Angemeldeter | **keine** fremden | **keine** fremden | dieselbe Methode; läuft **vor** der CSRF-Prüfung |

Die Besitzprüfung ist der IDOR-Schutz des Features: Die ID steht im Pfad und ist
fortlaufend. Dass sie vor der CSRF-Prüfung steht, ist begründet — wer nicht Eigentümer
ist, hat dort unabhängig vom Token nichts verloren, und die Antwort soll 403 sein statt
einer Weiterleitung, die nach abgelaufenem Formular aussieht. Am Schutz ändert die
Reihenfolge nichts: Ein Angriff über eine fremde Seite zielt auf eine ID des Opfers,
kommt also durch die Besitzprüfung und scheitert danach am Token.

Ein **Voter** existiert nicht; die Prüfung ist eine private Methode im Controller.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `/passkey/login/options` | **keins** | — | — (FB-01) |
| `/passkey/register/options` | **keins** | — | immerhin angemeldet |
| `app_passkey_rename` / `_delete` | **keins** | — | Besitzprüfung + CSRF |

Kryptografisch vorhanden:

- `user_verification: required` in beiden Profilen — Besitz allein genügt nicht.
- `resident_key: required` **und** `require_resident_key: true` — ohne auffindbaren
  Schlüssel könnte der Browser beim Anmelden keine Konten vorschlagen, dann bräuchte
  der Login doch wieder die E-Mail vorab.
- Signaturzähler als Klon-Schutz, fortgeschrieben bei **jeder** Anmeldung.
- `allowed_origins` leer auf Produktion → Weg der Spezifikation: HTTPS-Zwang plus
  Abgleich gegen die `rp_id`.
- `hide_existing_credentials: false` beim Anlegen — verhindert Doppelanlage.

## Externe Dienste

Keine. Der private Schlüssel verlässt das Gerät des Nutzers nie; der Server sieht nur
den öffentlichen Teil. Das ist der Datenschutzvorteil des Verfahrens gegenüber jedem
serverseitigen Geheimnis.

## Erkennbare Entscheidungen

Siehe Decision Log in `spec.md` — der Code ist an dieser Stelle ungewöhnlich gut
kommentiert, jede der acht Entscheidungen steht mit Begründung im Quelltext.

Zusätzlich erkennbar:

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 9 | Konfiguration bewusst schmal | vollständige Ausschöpfung | `phpunit.dist.xml` hat `failOnDeprecation="true"`; jede abgekündigte Option färbt die Suite rot. Nicht gesetzt: `rp.name`, `rp.icon`, `secured_rp_ids`, `options_storage` je Firewall |
| 10 | `CredentialRecordRepositoryInterface` statt `…CredentialSource…` | die alten Schnittstellen | letztere sind seit 5.3 abgekündigt, ebenso `DoctrineCredentialSourceRepository` seit 5.2 |
| 11 | Fehlerbehandlung über Flash statt `error.messageKey` | Basisklassenverhalten | die Meldungen kämen aus der `security`-Domäne, die das Projekt nicht führt, und benennen Interna wie „The credential ID is invalid" |
| 12 | `when@dev` mit `allowed_origins: http://localhost:8000` | `symfony server:ca:install` | Browser behandeln `localhost` als sicher, `CheckAllowedOrigins` nicht — ohne den Block bräuchte jede lokale Anmeldung eine eigene Zertifizierungsstelle im Schlüsselbund |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | `passkey_ui_controller.ts`, Ziel `panel` | Knopf erscheint nur bei vorhandenem `window.PublicKeyCredential` |
| AK-02 | dieselbe Feature-Detection | |
| AK-03 | `PasskeyAuthenticator::supports()` + `authenticate()` + `onAuthenticationSuccess()` | `TargetPathTrait` liefert das ursprüngliche Ziel |
| AK-04 | `passkey_ui_controller.ts`, `ERROR_CEREMONY_ABORTED` erzeugt bewusst keine Meldung | |
| AK-05 | `PasskeyAuthenticator::onAuthenticationFailure()` | |
| AK-06 | `WebauthnCredentialRepository::saveCredentialRecord()`, `markUsed()` | |
| AK-07 | `saveCredentialRecord()` → `WebauthnCredential::fromRecord()`, `guessDeviceName()` | |
| AK-08 | `PasskeyController::rename()`, `mb_substr($name, 0, 100)` | |
| AK-09 | `if ($name === '')` nach `trim()` | |
| AK-10 | `PasskeyController::delete()` | |
| AK-11 | `denyUnlessOwnedByCurrentUser()` | steht vor der CSRF-Prüfung |
| AK-12 | `WebauthnUserEntityRepository` implementiert `CanRegisterUserEntity` **nicht** | strukturelle Absicherung |
| AK-13 | gewöhnliche `<form method="post">` in `_passkey_manage.html.twig` | |
| AK-14 ⚠ | stiller `return` in `saveCredentialRecord()` | Lücke, siehe FB-02 |
| AK-15 ⚠ | **Abwesenheit** eines Limiters für `^/passkey` | Lücke, siehe FB-01 |
| AK-16 | Feldbestand `webauthn_credential` | |
| AK-17 | FK `ON DELETE CASCADE` + `orphanRemoval: true` | greift heute nur theoretisch — es gibt keinen Löschweg für Konten (B04/FB-01) |
| AK-18 | `User::obtainWebauthnHandle()` | |
| AK-19 | `user_verification: required` in beiden Profilen | |
| AK-20 | `saveCredentialRecord()` läuft bei jeder Anmeldung | |

## Für `sdd-qa` besonders zu prüfen

1. **AK-14** — Anlegen mit unpassendem Handle erzwingen und beobachten, was der Nutzer
   sieht.
2. **FB-06** — `WEBAUTHN_RP_ID` auf Produktion nachsehen. Ein falscher Wert macht das
   Feature unbenutzbar und fällt sonst erst einem Nutzer auf.
3. Die Assertion selbst ist mit PHPUnit **nicht** testbar — dafür braucht es einen
   virtuellen Authenticator im Browser (Chrome DevTools Protocol,
   `WebAuthn.addVirtualAuthenticator`). Der Weg ist im Projektgedächtnis hinterlegt
   (Brave + CDP).
