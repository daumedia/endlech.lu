# 04 · Marketing-Kontakte — Inbetriebnahme

Stand: 2026-08-30 · Der Code ist seit `v2026.08.30` live, die Funktion ist **aus**.

Drei Sperren halten den ersten echten Lauf auf. Sie fallen in dieser Reihenfolge —
**T08 und BF-88 vor dem Schlüssel**, sonst überträgt der Cron, bevor die Zielstruktur
steht und bevor die Erklärung den Empfänger nennt.

---

## Kontostand, gemessen am 2026-08-30

Über den Brevo-Zugang abgefragt, damit niemand raten muss:

| | Stand |
|---|---|
| **Attribute** | alle fünf vorhanden, `normal`/`text` — **T08 ist erfüllt** |
| **Listen** | drei: **id 5 „Endlech.lu · Neuigkeiten" (0 Abonnenten)**, id 4 „Contacts involved in conversations" (1), id 2 „Your first list" (2) |
| **Kontakte** | drei, alle vom 29.08.2026 — **keiner** trägt eines der fünf Attribute, **keiner** ist in Liste 5, keiner hat eine `ext_id`. Die Anwendung hat nie übertragen |
| **Kampagnen** | eine, „test", am 29.08. an Liste 2 mit zwei Empfängern |

⚠ **`BREVO_LIST_ID` ist leer, die Liste existiert aber.** Wer den Schlüssel setzt und die
Listen-ID vergisst, bekommt einen Sync, der **erfolgreich meldet** — die Kontakte landen in
Brevo, aber **in keiner Liste**. Eine Kampagne an „Endlech.lu · Neuigkeiten" erreicht dann
niemanden, und der Fehler sieht aus wie ein leerer Verteiler statt wie eine fehlende
Konfiguration. Beide Werte gehören zusammen in die `.env.local`:

```
BREVO_API_KEY=xkeysib-…
BREVO_LIST_ID=5
```

Dieselbe Bauart wie die Attributfalle: Brevo verwirft Unbekanntes stillschweigend, und der
Aufrufer sieht Erfolg.

## T08 · Die fünf Kontaktattribute im Brevo-Konto anlegen — **erfüllt**

Am 2026-08-30 gegen das Konto geprüft: **alle fünf vorhanden.** Der Abschnitt bleibt als
Nachschlagewerk stehen — für den Fall, dass jemand ein Attribut löscht oder ein zweites
Konto aufsetzt.

⚠ **Brevo verwirft unbekannte Attribute stillschweigend.** Ohne sie meldet
`app:marketing:sync` Erfolg und überträgt nur die nackte Adresse — Name, Organisation,
Sprache, Vertriebsrolle und Bearbeitungsstand fallen weg, ohne Fehler und ohne Warnung.

Alle fünf sind **Textfelder**, Kategorie `normal`. Die Namen stehen fest in
`App\Marketing\MarketingPayloadMapper` und dürfen nicht abweichen:

| Attribut | Inhalt |
|---|---|
| `CONTACT_NAME` | Name des Ansprechpartners |
| `ORGANISATION` | Restaurant- bzw. Organisationsname |
| `LOCALE` | Sprache (`lb`, `de`, `fr`, `en`) |
| `ORIGIN` | Rolle im Vertrieb — Partner, Gemeinde, Unternehmen, Verein, Nutzerkonto |
| `FUNNEL_STATUS` | Bearbeitungsstand |

`ext_id` ist **kein** Attribut, sondern ein Feld, das Brevo selbst führt — nichts anzulegen.

### Weg 1 · Über die Oberfläche

Brevo → **Contacts → Settings → Contact attributes → Add an attribute**.
Je Attribut: Name exakt wie oben (Großbuchstaben), Typ **Text**.

### Weg 2 · Über die API

```bash
KEY='<dein API-Schlüssel>'
for A in CONTACT_NAME ORGANISATION LOCALE ORIGIN FUNNEL_STATUS; do
  curl -s -o /dev/null -w "$A → %{http_code}\n" \
    -X POST "https://api.brevo.com/v3/contacts/attributes/normal/$A" \
    -H "api-key: $KEY" -H 'Content-Type: application/json' \
    -d '{"type":"text"}'
done
```

**201** = angelegt, **400** = existiert bereits (unkritisch), **401** = Schlüssel oder
IP-Freigabeliste.

### Gegenprobe

```bash
curl -s "https://api.brevo.com/v3/contacts/attributes" -H "api-key: $KEY" \
  | python3 -c "import json,sys; a=json.load(sys.stdin)['attributes']; \
    n={x['name'] for x in a}; \
    soll={'CONTACT_NAME','ORGANISATION','LOCALE','ORIGIN','FUNNEL_STATUS'}; \
    print('vorhanden:', sorted(soll & n)); print('fehlt:', sorted(soll - n) or 'nichts')"
```

Erst wenn „fehlt: nichts" dasteht, ist T08 erledigt.

---

## BF-88 · Auftragsverarbeitungsvertrag prüfen und datieren

⚠ **Das ist keine technische Aufgabe und lässt sich nicht delegieren.** Wer den Vertrag
nicht gesehen hat, kann ihn nicht bestätigen — ein Eintrag „geprüft am …" ohne Prüfung
wäre ein erfundener Nachweis in einem Datenschutzdokument.

Brevo veröffentlicht den AV-Vertrag **nicht** frei zugänglich; er liegt im Konto.

**Wo nachsehen:** Brevo → Account/Settings, Bereich **Privacy / Data Protection** oder
**Plan & Billing**. Gesucht wird ein *Data Processing Agreement* (DPA). Zu klären:

1. Ist es Bestandteil der Nutzungsbedingungen oder **separat zu unterzeichnen**?
2. Welche **Fassung/Datum** trägt es?
3. Nennt es die **Unterauftragsverarbeiter** und den **Serverstandort**?
4. Braucht es **Standardvertragsklauseln**? (Sollte entfallen — Brevo SA sitzt in
   Frankreich, `docs/datenschutz.md` führt „EU — keine Drittlandsübermittlung".)

**Danach einzutragen** in `docs/datenschutz.md`, Abschnitt *Brevo (Sendinblue SAS)*:

```
| **AV-Vertrag** | <Fassung/Datum, Fundstelle im Konto> |
| **Datum der Prüfung** | <Datum> |
```

Und die Prüfliste am Dateiende abhaken — dort stehen drei Punkte, von denen zwei hier
fallen:

- [ ] AV-Vertrag mit Brevo geprüft und mit Datum eingetragen
- [ ] `/legal` nennt Brevo als Empfänger für Werbezwecke ✔ *(seit v2026.08.30 erfüllt)*
- [ ] **OF-01** beantwortet (Datenschutzstufe des Projekts)

---

## Danach · Schlüssel setzen und übertragen

```bash
ssh <user>@<host>
cd ~/public_html

# 1 · Schlüssel eintragen
nano .env.local                       # BREVO_API_KEY=…
php bin/console cache:clear --env=prod   # PHP-FPM hält den alten Wert sonst fest

# 2 · Was würde übertragen? (schreibt nichts)
php bin/console app:marketing:import

# 3 · Erst wenn die Liste stimmt
php bin/console app:marketing:import --commit
php bin/console app:marketing:sync
```

⚠ **Der Trockenlauf wird heute „nichts zu übertragen" melden.** Die Auswahlregel ist
`hasSelfConfirmed() && hasMarketingConsent()`, und `marketing_consent_at` wird
ausschließlich beim Absenden der drei Formulare gesetzt. Der Bestand aus August hat die
Spalte auf `NULL` und **keinen Weg, sie nachträglich zu setzen** — es gibt keine Route
dafür. Wer den Bestand erreichen will, schreibt ihn einmal über den regulären Mailweg an
(Programm-Neuigkeit, der Zweck, für den er sich eingetragen hat) und bietet darin die
Einwilligung an.

**Nicht per `UPDATE` in der Datenbank nachtragen.** Dann fehlt der Nachweis nach
Art. 7 Abs. 1 — genau dagegen wurde `selfConfirmedAt` eingeführt (BF-89).
