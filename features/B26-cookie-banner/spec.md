# B26 · Cookie-Banner — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Beim ersten Besuch erscheint unten ein Banner mit der Wahl „annehmen" oder „ablehnen";
die Entscheidung liegt 365 Tage im Cookie `cookie_consent`. Über einen Link in der
Fußzeile lässt sie sich erneut aufrufen.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B13 | rekonstruiert | verlinkt auf den Datenschutzabschnitt |

## User Stories

- **US-01** · Als Besucher möchte ich der Cookie-Nutzung zustimmen oder widersprechen.
- **US-02** · Als Besucher möchte ich meine Entscheidung später ändern können.
- **US-03** · Als Betreiber möchte ich die Wahl nicht bei jedem Besuch erneut erfragen.

## Nicht im Scope

- Kategorienauswahl (notwendig / Statistik / Marketing) — es gibt genau eine Wahl
- Serverseitige Auswertung der Entscheidung — siehe AK-07

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Besucher kommt zum ersten Mal, wenn eine Seite lädt, dann
  erscheint das Banner am unteren Rand.
- **AK-02** · Angenommen, das Cookie `cookie_consent` ist gesetzt, wenn eine Seite lädt,
  dann erscheint das Banner **nicht**.
- **AK-03** · Angenommen, ein Besucher drückt „annehmen" oder „ablehnen", wenn die
  Aktion durchläuft, dann wird `cookie_consent` auf `accepted` bzw. `declined` gesetzt
  (`path=/; max-age=365 Tage; samesite=lax`, `secure` nur bei HTTPS) und das Banner
  verschwindet.
- **AK-04** · Angenommen, ein Besucher drückt in der Fußzeile „Cookie-Einstellungen",
  wenn die Aktion durchläuft, dann erscheint das Banner erneut.
- **AK-05** · Angenommen, das Banner wird betrachtet, wenn seine Barrierefreiheit
  geprüft wird, dann trägt es `role="dialog"` und `aria-modal="false"`, ist
  tastaturbedienbar und kontrastreich.
- **AK-06** · Angenommen, die Route beginnt mit `admin_`, wenn die Seite lädt, dann
  erscheinen weder Banner noch Fußzeilenlink.
- **AK-07** · Angenommen, der Besucher lehnt ab, wenn geprüft wird, was sich dadurch
  ändert, dann **nichts** — die Anwendung setzt in beiden Fällen dieselben Cookies
  (Sitzung, CSRF, gegebenenfalls `REMEMBERME`).

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-08** ⚠ · Angenommen, ein Besucher lehnt ab, wenn er danach die Seite benutzt,
  dann verhält sich die Anwendung **genauso wie bei Zustimmung**.
  *(So verhält sich der Code heute: Der Banner ist rein clientseitig; kein Code liest
  `cookie_consent` aus, weder im Browser noch auf dem Server. Es gibt allerdings auch
  nichts zu unterbinden — die Anwendung bindet **keine** Analyse-, Werbe- oder
  Fremdskripte ein. Die gesetzten Cookies (Sitzung, CSRF, `REMEMBERME`) sind technisch
  notwendig und einwilligungsfrei. Folge: Der Banner fragt nach einer Einwilligung, die
  rechtlich nicht erforderlich ist, und die Ablehnung hat keine Wirkung — ein Banner,
  dessen einzige Funktion darin besteht, zu verschwinden.)*

- **AK-09** ⚠ · Angenommen, die Wahl wird gespeichert, wenn geprüft wird wo, dann in
  einem Cookie ohne Zeitstempel und ohne Fassungsnummer.
  *(Ein Einwilligungsnachweis nach Art. 7 Abs. 1 DSGVO verlangt, belegen zu können,
  **wann** und **wofür** eingewilligt wurde. Da hier — siehe AK-08 — nichts
  Einwilligungspflichtiges stattfindet, ist der fehlende Nachweis folgenlos; er wäre es
  nicht, sobald ein Analysewerkzeug hinzukäme.)*

### Datenschutz und Missbrauchsschutz

- **AK-10** · Angenommen, das Cookie wird betrachtet, wenn sein Inhalt geprüft wird,
  dann steht dort `accepted` oder `declined` — keine Kennung, kein Zeitstempel, nichts
  Personenbeziehbares.
- **AK-11** · Angenommen, die Seite wird geladen, wenn nach Fremdressourcen gesucht
  wird, dann gibt es **keine** — kein Analysewerkzeug, keine Schriftarten von außen,
  keine CDN-Skripte. (Der Feedback-Link in der Fußzeile führt zu `endlech.userjot.com`,
  lädt aber nichts nach.)

## Edge Cases

- **EC-01** · Der Fußzeilenlink ist eine **eigene** Controller-Instanz
  (`<li data-controller="cookie-consent">`); die Verständigung läuft über ein
  Fenster-Ereignis: `dispatch('open')` → `cookie-consent:open@window->cookie-consent#reopen`.
- **EC-02** · Beide Einstiegspunkte (`connect()`, `reopen()`) sind über
  `hasBannerTarget` abgesichert — die Fußzeilen-Instanz hat kein Banner-Ziel.
- **EC-03** · Ohne JavaScript erscheint das Banner **nie** — und die Anwendung
  funktioniert unverändert.
- **EC-04** · Die Cookie-Schreibweise folgt dem Muster aus
  `csrf_protection_controller.ts`, einschließlich der `secure`-Erkennung über das
  Protokoll.

## Fehlbestand

- **FB-01 · Die Ablehnung hat keine Wirkung.** Siehe AK-08.
- **FB-02 · Kein Einwilligungsnachweis.** Siehe AK-09.
- **FB-03 · Keine Kategorien.** Sobald ein Analysewerkzeug hinzukäme, wäre die
  Ja/Nein-Wahl unzureichend.
- **FB-04 · Kein Ablauf der Entscheidung bei Änderung der Datenschutzerklärung.** Eine
  Fassungsnummer im Cookie wäre der übliche Weg.
- **FB-05 · Keine Fokusführung.** Beim Erscheinen wandert der Fokus nicht ins Banner;
  ein Tastaturnutzer erreicht es erst nach der gesamten Seite.

## Offene Fragen

- **OF-01** · Braucht die Seite den Banner überhaupt (AK-08)? Ohne einwilligungspflichtige
  Verarbeitung ist er rechtlich entbehrlich und kostet jeden Besucher einen Klick. Die
  Alternative wäre, ihn stehen zu lassen und **wirksam** zu machen, sobald ein
  Analysewerkzeug kommt. — Betreiber
- **OF-02** · Soll der Fokus beim Erscheinen ins Banner wandern (FB-05)? Auf einer
  Barrierefreiheitsplattform wiegt das schwerer als anderswo. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Rein clientseitig | ja | keine Entity, keine Migration, kein Backend |
| 2 | 365 Tage Laufzeit | ja | branchenüblich |
| 3 | Verständigung über ein Fenster-Ereignis | statt gemeinsamem Zustand | die beiden Instanzen stehen an verschiedenen Stellen im DOM; das ist der idiomatische Stimulus-Weg |
| 4 | Nicht im Verwaltungsbereich | Routennamen-Präfix | derselbe Weg wie bei der Bottom-Navigation |
| 5 | `samesite=lax`, `secure` nur bei HTTPS | Muster aus `csrf_protection_controller.ts` | eine Schreibweise für alle Cookies der Anwendung |
