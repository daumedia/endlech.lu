# 04 · Marketing-Kontakte in Brevo — Systemdesign

Status: `architected` · Stand: 2026-08-29 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Der Entwurf hat einen tragenden Gedanken: **Kein Anfrage-Ablauf spricht jemals mit
Brevo.** Wer sich einträgt, erzeugt einen Eintrag in einem *Auftragsbuch* — einer
eigenen Tabelle, die für jede E-Mail-Adresse festhält, was in Brevo stehen soll und ob
es schon dort steht. Ein wiederkehrender Konsolenbefehl arbeitet dieses Buch ab.

Das ist nicht die naheliegende Bauweise, aber die einzige, die hier trägt: **Auf
Produktion läuft `MESSENGER_TRANSPORT_DSN=sync://` und kein Worker** (`src/Schedule.php`
sagt das ausdrücklich, BF-48). Eine per Messenger „asynchron" verschickte Nachricht
liefe dort **synchron im Request** — und damit hinge jede Wartelisten-Anmeldung an der
Erreichbarkeit eines fremden Dienstes. Genau das verbietet AK-17.

Der Rückweg läuft über einen Webhook, den Brevo bei Abmeldung, Bounce und Beschwerde
ruft. Er ist der einzige Weg von außen nach innen und entsprechend eng gefasst.

Das Auftragsbuch ist zugleich die Antwort auf die schwierigste Stelle der Spec: Ein
Widerruf **löscht** den Wartelisten-Eintrag (Feature `01`, im Code belegt:
`WaitlistConfirmationService::revoke()` ruft `remove()`). Läge der Sync-Zustand an diesem
Eintrag, wäre mit ihm auch der Auftrag verschwunden, den Brevo-Kontakt zu entfernen —
und die Adresse bliebe dort für immer stehen. **Der Löschauftrag muss die Löschung
seiner Quelle überleben.** Deshalb steht er in einer eigenen Tabelle ohne Fremdschlüssel.

## Seiten und Routen

Dieses Feature bringt **eine** neue Route mit. Alles andere sind Ergänzungen an
bestehenden Seiten.

| Route | Zweck | Zugang |
|---|---|---|
| `POST /marketing/brevo/webhook` | **neu** — nimmt Brevos Meldungen zu Abmeldung, Bounce, Beschwerde und Kontaktlöschung entgegen | öffentlich erreichbar, per Bearer-Geheimnis abgesichert |
| `GET/POST /{_locale}/partner` | bestehend — Formular bekommt das Einwilligungsfeld | öffentlich |
| `GET/POST /{_locale}/organisationen` und die drei Zielgruppenseiten | bestehend — dito | öffentlich |
| `GET/POST /{_locale}/register` | bestehend — dito | öffentlich |
| `GET /{_locale}/admin/warteliste` | bestehend — Liste bekommt die Spalte „Brevo" | `ROLE_ADMIN` |
| `GET /{_locale}/admin/warteliste/partner/{id}`, `…/organisation/{id}` | bestehend — Detailansicht zeigt Sync-Zustand und letzten Fehler | `ROLE_ADMIN` |

⚠ **Der Webhook ist sprachfrei und braucht deshalb zwei Eintragungen von Hand.** Brevo
ruft ohne Sprachpräfix. `config/routes.yaml` führt heute eine `exclude`-**Liste** mit
zwei Einträgen (`Api/V1/`, `Open/`) am `controllers`-Loader; hier kommt ein dritter
hinzu, plus ein eigener Importblock ohne `/{_locale}`.

⚠ **Ohne eigene `access_control`-Zeile wäre der Endpunkt von keiner Regel gedeckt.** Die
Web-Regeln greifen auf `^/[a-z]{2}/…` — ein sprachfreier Pfad fällt durch alle hindurch.
Das ist derselbe Fallstrick, der bei den Passkey-Endpunkten zu BF-18 führte.

**Was es bewusst nicht gibt:** keine Verwaltungsseite für Kampagnen, keine Listenpflege
in Endlech.lu, kein Schalter im Profil (das ist OF-02 und nicht beauftragt).

## Komponentenstruktur

```
Wartelisten-Formular (Partner / Organisationen)          bestehend
├── Felder wie bisher
├── Einwilligung zur Anmeldung          bestehend, Pflicht, IsTrue
├── Einwilligung zu Neuigkeiten         NEU, freiwillig, nicht vorangehakt
│   └── Kurztext mit Abmeldehinweis     „jederzeit widerrufbar"
└── Honeypot                            bestehend, bleibt letztes Feld

Registrierformular                                       bestehend
└── Einwilligung zu Neuigkeiten         NEU, dasselbe Partial

Verwaltung · Wartelisten-Liste                           bestehend
├── Filterleiste                        bestehend, unverändert
└── Tabellenzeile
    └── Spalte „Brevo"                  NEU, Abzeichen mit vier Zuständen

Verwaltung · Wartelisten-Detail                          bestehend
└── Block „Marketing"                   NEU
    ├── Einwilligung ja/nein + Zeitpunkt
    ├── Sync-Zustand + Zeitpunkt der Übertragung
    └── letzter Fehler                  nur sichtbar, wenn einer vorliegt
```

**Die vier Zustände der Spalte „Brevo"** — sie ist die einzige neue Anzeige und
entspricht `MarketingSyncState`:

| Zustand | Anzeige | Wann |
|---|---|---|
| *leer* | „—", grau | keine Einwilligung; der Normalfall bei den meisten Zeilen |
| ausstehend | Abzeichen bernstein | eingewilligt, noch nicht übertragen |
| übertragen | Abzeichen grün, mit Datum | in Brevo vorhanden |
| fehlgeschlagen | Abzeichen rot, mit Grund | letzter Versuch scheiterte |

Farben über eine `badgeClasses()`-Methode am Enum, wie `WaitlistStatus` es vormacht
(`docs/design-system.md`, Abschnitt *Abzeichen*): Die Methode liefert **nur Farbe**, Form
und Größe bleiben im Template. Das Abzeichen trägt zusätzlich Text — Farbe trägt nie
allein.

**Wiederverwendet, nicht neu gebaut:** `templates/partials/_form_field.html.twig` für das
Einwilligungsfeld (es kapselt Label, Hilfetext, `aria-describedby` und Fehlermeldung),
und die bestehende Abzeichen-Kette der Verwaltungsliste.

## Datenmodell

### Neue Tabelle `marketing_contact`

Das Auftragsbuch. Eine Zeile je **E-Mail-Adresse**, nicht je Quelle.

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | int, PK | ja | wird zugleich als `ext_id` an Brevo übergeben |
| `email` | varchar(180), **unique** | ja | der fachliche Schlüssel — eine Adresse, ein Kontakt |
| `contact_name` | varchar(120) | nein | Ansprechpartner für die Anrede |
| `organisation_name` | varchar(180) | nein | Restaurant- bzw. Organisationsname |
| `locale` | varchar(5) | ja | Sprache der Kampagne |
| `origin` | varchar(20), enum | ja | `partner`, `commune`, `company`, `association`, `account` |
| `funnel_status` | varchar(20), enum, nullable | nein | `WaitlistStatus`; bleibt leer bei Konten |
| `consent_at` | datetime_immutable | ja | Zeitpunkt der Werbe-Einwilligung |
| `revoked_at` | datetime_immutable | nein | gesetzt bei Abmeldung über Brevo — die Zeile wird damit zur Sperre |
| `sync_state` | varchar(20), enum | ja | `pending`, `synced`, `failed`, `removal_pending` |
| `synced_at` | datetime_immutable | nein | wann der Stand zuletzt bei Brevo ankam |
| `last_error` | varchar(255) | nein | Klasse und Statuscode des letzten Fehlversuchs, **nie die Antwort im Wortlaut** |
| `attempts` | smallint, default 0 | ja | Zähler für den Rückzug bei dauerhaftem Fehler |
| `created_at` | datetime_immutable | ja | Konstruktor, kein Setter (Projektkonvention) |
| `updated_at` | datetime_immutable | ja | über `#[ORM\PreUpdate]`, wie bei `PartnerWaitlistEntry` |

**Beziehungen: keine.** Das ist die zentrale Abweichung von der `ON DELETE`-Konvention
des Projekts (`docs/data-model.md`) und der einzige Punkt, an dem dieser Entwurf bewusst
gegen ein bestehendes Muster geht. Der Grund steht im Überblick: Ein Fremdschlüssel auf
`partner_waitlist_entry` mit `CASCADE` löschte den Auftrag mit; mit `SET NULL` bliebe
eine Zeile, deren Herkunft niemand mehr kennt. Die Verbindung läuft deshalb über die
E-Mail-Adresse, und `origin` hält fest, woher sie kam.

**Indizes:**
- `email` unique — trägt EC-01 (eine Adresse, ein Kontakt) auf Datenbankebene, nicht als
  Anwendungslogik.
- `(sync_state, updated_at)` — die einzige Abfrage des Sync-Laufs: „was ist offen,
  ältestes zuerst".

**Aufbewahrung:** Eine Zeile mit `revoked_at` bleibt als Sperre stehen (sonst trüge der
nächste Lauf die Adresse erneut ein, AK-12). Eine Zeile in `removal_pending` verschwindet,
sobald Brevo die Löschung bestätigt hat. Eine Löschfrist für alte Sperren ist offen
(spec OF-06).

### Änderungen an bestehenden Tabellen

Drei Mal dasselbe Feld, jeweils **neu an einer bestehenden Tabelle** — keine Neuanlage:

| Tabelle | Neues Feld | Typ | Bedeutung |
|---|---|---|---|
| `partner_waitlist_entry` | `marketing_consent_at` | datetime_immutable, nullable | Zeitpunkt der Werbe-Einwilligung; `NULL` = keine |
| `organisation_waitlist_entry` | `marketing_consent_at` | datetime_immutable, nullable | dito |
| `user` | `marketing_consent_at` | datetime_immutable, nullable | dito |

**Warum der Zeitpunkt zweimal steht** — an der Quelle und im Auftragsbuch — und das keine
Redundanz ist: Die Quelle trägt den **Nachweis** der Einwilligung (Art. 7 Abs. 1
verlangt, ihn führen zu können) und speist den Datenexport (AK-44). Das Auftragsbuch
trägt den **Auftrag** und muss die Quelle überleben. Zwei Zwecke, zwei Orte.

Das Muster ist im Projekt bereits da: `PartnerWaitlistEntry::$consentAt` speichert
ebenfalls den Zeitpunkt und nicht das Häkchen — das Formularfeld ist `mapped: false`
(`PartnerWaitlistType.php:80`).

⚠ **Migration MariaDB-10.5-tauglich halten.** Reine `ADD COLUMN` plus eine `CREATE TABLE`
ohne native `ENUM`-Spalten — dieselbe Vorgabe, unter der `Version20260824120000`
entstanden ist. Produktion ist MariaDB, lokal und CI sind MySQL 8.

### Neue Enums

| Enum | Werte | Zusatz |
|---|---|---|
| `MarketingOrigin` | `partner`, `commune`, `company`, `association`, `account` | `label()`, `brevoValue()` |
| `MarketingSyncState` | `pending`, `synced`, `failed`, `removal_pending` | `label()`, `badgeClasses()` |

⚠ **`MarketingOrigin` bezeichnet die Rolle im Vertrieb, nicht die Person.** Das ist
AK-30 und der Grund, warum es kein Attribut „Nutzer der Plattform" gibt, das über eine
Person mehr aussagt, als sie preisgeben wollte.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Öffentlich (nicht angemeldet) | nichts aus `marketing_contact` | nur mittelbar: die eigene Einwilligung beim Absenden eines Formulars | `access_control`; die Tabelle hat keine öffentliche Route |
| Angemeldeter Nutzer | den eigenen Einwilligungsstand im Datenexport | die eigene Einwilligung bei der Registrierung | `IS_AUTHENTICATED_FULLY` am Profilbereich; der Export liest ausschließlich das eigene Konto |
| `ROLE_ADMIN` | alle Zeilen über die Verwaltungsliste | Vertriebsstatus (mittelbar, löst Neuübertragung aus) | `access_control` auf `^/[a-z]{2}/admin` |
| Brevo (Webhook) | nichts | ausschließlich `revoked_at` und die Einwilligung an der Quelle, adressiert über die E-Mail-Adresse im Rumpf | Bearer-Geheimnis im Header, geprüft vor jeder Auswertung |
| Konsolenbefehle | alles | alles | kein HTTP-Zugang; läuft unter dem Systembenutzer |

**Es gibt keine zweite Schicht.** Das Stack-Profil sagt es deutlich: ohne RLS ist das,
was die Anwendung nicht prüft, ungeprüft. Für dieses Feature heißt das konkret: Der
Webhook ist der einzige Punkt, an dem ein Fremder schreibend an Daten kommt, und seine
gesamte Absicherung ist der Vergleich eines Geheimnisses.

⚠ **Der Webhook darf aus dem Rumpf nur die E-Mail-Adresse und den Ereignistyp
verwenden.** Alles andere, was Brevo mitschickt, wird verworfen. Andernfalls wäre ein
kompromittiertes Brevo-Konto ein Schreibzugang in die eigene Datenbank.

⚠ **Der Webhook antwortet immer mit 200**, auch bei unbekannter Adresse. Eine
unterschiedliche Antwort verriete, ob eine Adresse im Bestand ist — dieselbe Überlegung
wie bei der Anti-Enumeration in Registrierung und Passwort-Zurücksetzen.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `POST /marketing/brevo/webhook` | 120 je 5 Minuten und IP | 429, kein Datenbankzugriff | `config/packages/framework.yaml`, Limiter `marketing_webhook`, im Controller über `ActionLimiter` |
| Partner-, Organisations-, Registrierformular | unverändert 5 je Stunde und IP | wie bisher | bestehende Limiter `partner_waitlist`, `organisation_waitlist`, `registration` |
| Aufrufe **an** Brevo (Sync-Lauf) | höchstens 200 Kontakte je Lauf, mindestens 200 ms Abstand | der Rest bleibt im Auftragsbuch und kommt beim nächsten Lauf | Parameter in `config/services.yaml`, ausgewertet im Sync-Dienst |
| `app:marketing:import` | schreibt ohne `--commit` nichts | Trockenlauf ist der Vorgabefall | Konsolenbefehl |
| Wiederholung nach Fehler | Rückzug nach 5 Versuchen: Zustand bleibt `failed`, der Lauf greift ihn nicht mehr automatisch auf | in der Verwaltung sichtbar, von Hand erneut anstoßbar | Feld `attempts` |

⚠ **Der `when@test`-Override auf 10000 ist Pflicht**, sonst färbt der neue Limiter die
Suite ab dem 121. Aufruf rot. `LimiterCoverageTest` prüft beides — Verdrahtung und
Override — und wird ohne diesen Eintrag rot.

Die Konvention aus `CLAUDE.md` trifft hier zweifach zu: Der Webhook **prüft ein
Geheimnis**, und der Sync-Lauf **löst mittelbar Versand aus**. Beides verlangt einen
Limiter im selben Commit.

**Keine Kostenstelle im Anfrage-Ablauf.** Ein Kontakt in Brevo kostet nichts; Geld kostet
der Versand einer Kampagne, und der wird ausschließlich in Brevo ausgelöst. Dieses
Feature kann keine Versandkosten erzeugen — das ist AK-42 und zugleich die Begründung
dafür, dass es keinen Kampagnen-Auslöser gibt.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| Brevo · Kontakte | Anlegen, Aktualisieren, Löschen eines Kontakts | E-Mail, `ext_id`, Attribute `CONTACT_NAME`, `ORGANISATION`, `LOCALE`, `ORIGIN`, `FUNNEL_STATUS`, Listen-ID | **die Freitextnachricht** aus beiden Wartelisten, **die Telefonnummer**, **der Ort**, **die Herkunftsquelle** (`source`/UTM), **jede IP-Adresse**, **jeder Token** |
| Brevo · Webhook | Meldung von Abmeldung, Bounce, Beschwerde, Kontaktlöschung | nichts von uns — Brevo ruft uns | entfällt |
| Brevo · Versand (bestehend) | Transaktionsmails | unverändert | unverändert |

**Sitz und Rechtsgrundlage:** Brevo SA, Frankreich (EU). Der
Auftragsverarbeitungsvertrag wird in der neu anzulegenden `docs/datenschutz.md`
festgehalten, mit Datum der Prüfung — das ist AK-33 und eine **Vorbedingung des ersten
echten Laufs**, keine Nacharbeit.

**Was aus der Brevo-Dokumentation folgt** *(API-Schemata gelesen am 2026-08-29)*:

- **Anlegen und Aktualisieren sind ein Aufruf.** `POST /contacts` mit
  `updateEnabled: true` legt an oder schreibt fort. Damit ist Idempotenz (AK-20) und
  Dublettenfreiheit (AK-25) **strukturell** gegeben, ohne vorher zu prüfen, ob der
  Kontakt existiert.
- ⚠ **Unbekannte Attribute werden stillschweigend verworfen.** Die Dokumentation sagt
  wörtlich, dass jedes Attribut ignoriert wird, das im Konto nicht definiert ist. Ein
  Sync, dessen Attribute nicht vorher angelegt wurden, meldet **Erfolg** und überträgt
  nur die nackte Adresse. Das Anlegen der fünf Attribute ist deshalb ein eigener
  Einrichtungsschritt und gehört als solcher in den Aufgabenplan — nicht in eine Fußnote.
- ⚠ **`emailBlacklisted` wird beim Übertragen niemals mitgeschickt.** Das Feld wird nur
  gesetzt, wenn es im Rumpf steht; lässt man es weg, bleibt eine bestehende Abmeldung in
  Brevo unangetastet. Genau das ist EC-05. Wer es „vorsichtshalber" auf `false` setzt,
  hebt jede Abmeldung auf, die dort schon steht.
- **`ext_id` löst die Adressänderung.** Der Kontakt wird über die eigene Kennung
  adressiert (`identifierType: ext_id`), die neue Adresse geht als `EMAIL` in den
  Attributen mit. Ohne das entstünde bei jeder Adressänderung ein zweiter Kontakt und
  der alte bliebe stehen — EC-02.
- **Der Webhook-Typ ist `marketing`**, die Ereignisse sind `unsubscribed`, `hardBounce`,
  `spam` und `contactDeleted`. `unsubscribed` ist der Fall aus AK-11.

**Ausfall ist der Normalfall, nicht die Ausnahme.** Der Aufruf folgt dem Muster von
`PublicTransportService`: eigener `timeout`, `catch (\Throwable)`, und **die
Ausnahmemeldung wird nicht ins Protokoll durchgereicht** — dort stünde sonst der
Schlüssel. Protokolliert werden Klasse und Statuscode.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **Auftragsbuch plus Konsolenbefehl** statt Messenger | `#[AsMessageHandler]` auf einer async-Nachricht | Produktion läuft mit `sync://` und ohne Worker (BF-48). „Async" wäre dort synchron im Request — die Anmeldung hinge an Brevo, entgegen AK-17. Kommt der Worker, kann der Befehl unverändert von einem Handler gerufen werden |
| 2 | **Eigene Tabelle ohne Fremdschlüssel** | Sync-Felder an den drei Quell-Entities | Der Widerruf löscht den Wartelisten-Eintrag. Ein Auftrag, der an ihm hängt, verschwindet mit ihm — und der Kontakt bliebe für immer in Brevo. Zusätzlich löst `email` als Unique-Schlüssel EC-01 auf Datenbankebene |
| 3 | **Eine Brevo-Liste, Segmentierung über Attribute** | je Zielgruppe eine eigene Liste | Ein Kontakt aus zwei Quellen läge sonst in zwei Listen und bekäme jede Kampagne doppelt. Attribute lassen sich in Brevo frei kombinieren, Listenmitgliedschaften nicht |
| 4 | **`ext_id` = eigene Datensatz-Kennung** | Adressierung über die E-Mail | Die Adresse ist das einzige Feld, das sich ändern kann. Über sie zu adressieren, macht die Adressänderung zum Sonderfall statt zum Normalfall |
| 5 | **Trockenlauf ist der Vorgabefall des Imports** | `--dry-run` als Zusatzflagge | Umgekehrt zum `--force` beim Snapshot-Befehl, und das mit Absicht: Die gefährliche Richtung braucht die Flagge, nicht die harmlose. Ein falsch gefilterter Lauf ist nicht zurückzuholen |
| 6 | **Einzelaufrufe statt Sammelimport** | `POST /contacts/import` mit JSON-Rumpf | Der Sammelweg wäre ein zweiter Ablauf mit eigener Fehlerbehandlung und eigenem Nachlauf, für einen Bestand im zweistelligen Bereich. Ab etwa 1000 Kontakten kehrt sich das um — dann ist der Sammelweg nachzurüsten, und diese Zeile ist der Anlass |
| 7 | **Abmeldung sperrt, Widerruf löscht** | beides gleich behandeln | Zwei verschiedene Handlungen: Der Werbewiderspruch lässt die Anmeldung bestehen und muss als Sperre erinnert werden, sonst trägt der nächste Lauf die Adresse erneut ein. Der Wartelisten-Widerruf zieht die Anmeldung selbst zurück — dort bleibt nichts |
| 8 | **Eine neue Einwilligung hebt die Sperre auf** | Sperre gilt dauerhaft | AK-12 und AK-45 stehen sonst gegeneinander. Maßgeblich ist der jüngere Zeitpunkt: liegt `consent_at` nach `revoked_at`, ist die Sperre überholt |
| 9 | **Eigener Namensraum `App\Marketing\`** | Einordnung unter `App\Service\` | Folgt `App\Open\` und `App\Waitlist\`: Ein Bereich mit eigenen Begriffen (Auftragsbuch, Herkunft, Sync-Zustand) bleibt beisammen |
| 10 | **Kein neues Paket** | offizielles Brevo-SDK | Ein einziger HTTP-Aufruf in drei Ausprägungen. Symfonys `HttpClient` ist da, das Muster steht in `PublicTransportService`, und ein SDK brächte eine Abhängigkeit samt eigenem Auffrischungsbedarf für vier Endpunkte |
| 11 | **Eigener Umgebungswert für den Schlüssel** | den Schlüssel aus `MAILER_DSN` herausziehen | Der DSN ist Konfiguration des Versandwegs. Ihn zu zerlegen, um an ein Geheimnis zu kommen, koppelt zwei Belange, die getrennt rotiert werden können — auch wenn heute derselbe Schlüssel dahintersteht |

## Abdeckung der Akzeptanzkriterien

Alle 48 Kriterien aus `spec.md`, der Reihe nach durchgegangen.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Einwilligungsfeld in `PartnerWaitlistType`, `OrganisationWaitlistType`, `RegistrationType`, gerendert über `_form_field.html.twig` | |
| AK-02 | Feld ohne Vorbelegung, `required: false` | wie das bestehende Honeypot-Feld: kein `data`-Vorgabewert |
| AK-03 | Feld ohne `IsTrue`-Constraint; der Ablauf im Controller bleibt unverändert | Gegenstück zum Pflichtfeld `consent`, das ein `IsTrue` trägt |
| AK-04 | `marketing_consent_at` an der jeweiligen Quelle, gesetzt beim Absenden | Muster von `consentAt` (`mapped: false`, Zeitpunkt statt Häkchen) |
| AK-05 | `MarketingContactRegistry` legt die Zeile erst an, wenn die Quelle bestätigt ist — bei Wartelisten im Bestätigungs-Ablauf, bei Konten nach der E-Mail-Verifikation | die Einwilligung wird vorher gespeichert, aber nicht ausgeführt |
| AK-06 | Sync-Lauf `app:marketing:sync`, Zustand `pending` → `synced` | |
| AK-07 | fünf Attribute plus `ext_id`; die Abbildung liegt an genau einer Stelle im Sync-Dienst | die Negativliste steht unter *Externe Dienste* |
| AK-08 | Attribut `FUNNEL_STATUS` aus `funnel_status` | in Brevo als Segmentkriterium nutzbar |
| AK-09 | Statuswechsel in der Verwaltung setzt `sync_state` auf `pending` zurück | der Lauf holt es ab; keine Direktübertragung im Request |
| AK-10 | Cron-Eintrag alle 5 Minuten | Frist 15 Minuten ist damit dreifach unterschritten |
| AK-11 | Webhook-Ereignis `unsubscribed` → `revoked_at` gesetzt **und** `marketing_consent_at` an der Quelle geleert | |
| AK-12 | Der Lauf überspringt Zeilen mit `revoked_at`, sofern nicht `consent_at` jünger ist | Entscheidung 8 |
| AK-13 | Wartelisten-Widerruf setzt `removal_pending`, **bevor** der Eintrag gelöscht wird; der Lauf entfernt danach den Kontakt und löscht die Zeile | greift in `WaitlistConfirmationService::revoke()` ein |
| AK-14 | `AccountDeleter` setzt `removal_pending`, bevor er das Konto entfernt | dieselbe Reihenfolge wie beim Avatar: erst das Auswärtige, dann die Zeile |
| AK-15 | Zustand `failed` bleibt sichtbar in Liste und Detailansicht | |
| AK-16 | Die lokale Löschung läuft unabhängig vom Brevo-Aufruf; das Auftragsbuch trägt den Rest | genau dafür hat die Tabelle keinen Fremdschlüssel |
| AK-17 | Kein Anfrage-Ablauf ruft Brevo | Entscheidung 1 |
| AK-18 | Felder `sync_state`, `last_error`, `attempts` in der Verwaltung | |
| AK-19 | Der wiederkehrende Lauf greift jede Zeile in `pending` erneut auf | bis zum Rückzug nach 5 Versuchen |
| AK-20 | `updateEnabled: true` beim Anlegen | Brevo-Dokumentation, siehe *Externe Dienste* |
| AK-21 | `app:marketing:import` ohne `--commit` gibt nur aus | Entscheidung 5 |
| AK-22 | derselbe Befehl mit `--commit` legt die Zeilen an; der reguläre Lauf überträgt sie | |
| AK-23 | Auswahl des Befehls: bestätigte Wartelisten-Einträge, keine Konten, keine unbestätigten | die Auswahlregel steht im Befehl, nicht im Aufruf — sie ist nicht per Parameter aufweichbar |
| AK-24 | **Inhaltsaufgabe, kein Code** — Text der ersten Kampagne in Brevo | wird in `tasks.md` als Aufgabe mit Nachweis geführt |
| AK-25 | `email` unique lokal, `updateEnabled` bei Brevo | zwei Schichten, beide strukturell |
| AK-26 | Spalte „Brevo" in der Wartelisten-Liste | |
| AK-27 | Zählung über den Index `(sync_state, updated_at)`; Gegenprobe in Brevo von Hand | die Übereinstimmung ist ein QA-Nachweis, keine Automatik |
| AK-28 | Feldliste der Tabelle plus Abbildung im Sync-Dienst | |
| AK-29 | Die Nachricht steht in keiner Abbildung und in keinem Feld von `marketing_contact` | strukturell: das Feld existiert dort nicht |
| AK-30 | `MarketingOrigin` bezeichnet die Vertriebsrolle | |
| AK-31 | Protokolliert werden Klasse und Statuscode; zusätzlich greift `SecretMaskingProcessor` | Muster aus `PublicTransportService` |
| AK-32 | **Inhaltsaufgabe** — Datenschutzabschnitt auf `/legal` (B13) | |
| AK-33 | **Dokumentationsaufgabe** — neue `docs/datenschutz.md` | die Datei existiert heute nicht |
| AK-34 | Reihenfolge im Aufgabenplan: AK-32 und AK-33 vor dem ersten `--commit` | organisatorisch, nicht technisch erzwungen — gehört als Vorbedingung in `tasks.md` |
| AK-35 | `access_control` auf `^/[a-z]{2}/admin` | bestehende Regel, keine neue |
| AK-36 | derselbe Weg; der Konsolenbefehl hat keinen HTTP-Zugang | |
| AK-37 | Detailrouten sind bestehende Admin-Routen mit `{id}`-Bindung | keine neue Objektzugriffsregel nötig, weil kein Nutzer je eine eigene Zeile besitzt |
| AK-38 | Rollenübersicht in *Zugriffsregeln* | |
| AK-39 | Deckel je Lauf plus Mindestabstand | |
| AK-40 | bestehende Limiter der drei Formulare, unverändert | die Checkbox eröffnet keinen neuen Weg |
| AK-41 | trifft nicht zu — keine Dateientgegennahme | ausdrücklich geprüft |
| AK-42 | kein Kampagnen-Auslöser im Produkt | siehe *Missbrauchsschutz*, letzter Absatz |
| AK-43 | siehe AK-13 und AK-14 | |
| AK-44 | Datenexport liest `marketing_consent_at` des Kontos mit | Erweiterung des bestehenden Exports aus Feature `01` |
| AK-45 | Entscheidung 8: jüngeres `consent_at` hebt die Sperre auf | |
| AK-46 | `BREVO_API_KEY`, `BREVO_LIST_ID`, `BREVO_WEBHOOK_TOKEN` — leer in `.env`, echt nur in der `.env.local` auf dem Server | Muster von `SENTRY_DSN` und `MOBILITEIT_API_KEY` |
| AK-47 | Leerer Schlüssel: Registry schreibt weiter ins Auftragsbuch, der Lauf bricht mit Hinweis ab | die Einwilligung geht nicht verloren, sie wartet |
| AK-48 | Kein Wert erreicht ein Template oder ein Stimulus-Element | die gesamte Verarbeitung liegt serverseitig |

**Keine Zeile ist leer.** Vier Kriterien (AK-24, AK-32, AK-33, AK-34) werden **nicht
durch Code** erfüllt, sondern durch Inhalt, Dokumentation und die Reihenfolge im
Aufgabenplan. Sie stehen hier ausdrücklich als solche, damit `sdd-tasks` sie als
Aufgaben führt und `sdd-qa` sie einzeln nachweist — nicht, damit sie beim Bauen
untergehen.

## Was dieser Entwurf offen lässt

Die sechs offenen Fragen aus `spec.md` bleiben offen; keine wurde hier still
entschieden. Drei berühren den Entwurf unmittelbar:

- **OF-04 (Herkunft bei mehreren Quellen)** — die Tabelle trägt heute **ein**
  `origin`-Feld. Die einfache Lesart „die erste gewinnt" ist damit umsetzbar; eine
  Mehrfachzuordnung bräuchte ein zusätzliches Attribut und eine Folgemigration. Der
  Entwurf legt sich nicht fest, weil die Antwort das Datenmodell ändert.
- **OF-03 (Öffnungsverfolgung)** — reine Kontoeinstellung in Brevo, kein Code. Sie
  berührt diesen Entwurf nicht, wohl aber die Zusage im PRD.
- **OF-06 (Löschfrist für Sperren)** — betrifft die Aufbewahrung von Zeilen mit
  `revoked_at`. Ohne Antwort wachsen sie unbegrenzt; das ist derselbe Fehler wie
  B14/FB-02, wo eine Aufräumroutine existiert und nie gerufen wird.
