# B15 · Deploy-Nachprüfung BF-151 auf der Produktion (2026-09-14)

Release `v2026.09.14`, `master` auf `88827e4`, in Coolify ausgerollt (Anwendung). Die Fußzeile zeigte
`v2026.09.14` rund 460 s nach dem Merge.

Stand **vor** dem Ausrollen, gemessen am selben Tag: Fußzeile `v2026.09.13`; auf
`/de/organisationen/gemeinden`, `…/unternehmen` und `…/vereine` trug
`<form name="organisation_waitlist">` **kein** `action`.

⚠ **Auf der Produktion wurde kein Eintrag angelegt.** Jede Eintragung bleibt ohne Pflichtfelder.
`OrganisationController::submit()` antwortet dann mit 422, **bevor** das Kontingent verbraucht wird
(`$limiter->consume()` steht hinter `isValid()`). Es entsteht keine Zeile, es geht keine Mail hinaus, und
es wird kein Deckel belegt. Dass eine vollständige Eintragung gespeichert wird und die Mail auslöst, ist
lokal im Produktionsmodus belegt (`zielgruppen-browser.ausgabe.txt`, 16 von 16).

## Formularziel, vier Sprachen × vier Seiten (curl)

```
lb  /             200 method="post" action="/lb/organisationen"
lb  /gemeinden    200 method="post" action="/lb/organisationen"
lb  /unternehmen  200 method="post" action="/lb/organisationen"
lb  /vereine      200 method="post" action="/lb/organisationen"
de  /             200 method="post" action="/de/organisationen"
de  /gemeinden    200 method="post" action="/de/organisationen"
de  /unternehmen  200 method="post" action="/de/organisationen"
de  /vereine      200 method="post" action="/de/organisationen"
fr  /             200 method="post" action="/fr/organisationen"
fr  /gemeinden    200 method="post" action="/fr/organisationen"
fr  /unternehmen  200 method="post" action="/fr/organisationen"
fr  /vereine      200 method="post" action="/fr/organisationen"
en  /             200 method="post" action="/en/organisationen"
en  /gemeinden    200 method="post" action="/en/organisationen"
en  /unternehmen  200 method="post" action="/en/organisationen"
en  /vereine      200 method="post" action="/en/organisationen"
```

## Unvollständige Eintragung ohne JavaScript (curl, mit Token und eigener Herkunft)

Das Formular wird von der Zielgruppenseite geholt und an das `action` geschickt, das es trägt. Der Typ ist
gesetzt, Name und E-Mail bleiben leer.

```
de/gemeinden → POST /de/organisationen: 422 · Felder mit Fehler: 4 · Stacktrace: 0
fr/unternehmen → POST /fr/organisationen: 422 · Felder mit Fehler: 4 · Stacktrace: 0
en/vereine → POST /en/organisationen: 422 · Felder mit Fehler: 4 · Stacktrace: 0
lb/gemeinden → POST /lb/organisationen: 422 · Felder mit Fehler: 4 · Stacktrace: 0
Alte Zieladresse POST /de/organisationen/vereine → 405 (reine GET-Route, erwartet)
```

## Mit JavaScript im Browser (`produktion-browser.mjs`, HeadlessChrome 152)

```
✅ de/gemeinden mit JavaScript, Pflichtfelder leer · Turbo true · action /de/organisationen · POST /de/organisationen → 422 · Fehlerfelder 4 · Fehlerseite false · Adresse /de/organisationen/gemeinden · JS-Ausnahmen 0 · Konsolenfehler 0
✅ fr/unternehmen mit JavaScript, Pflichtfelder leer · Turbo true · action /fr/organisationen · POST /fr/organisationen → 422 · Fehlerfelder 4 · Fehlerseite false · Adresse /fr/organisationen/unternehmen · JS-Ausnahmen 0 · Konsolenfehler 0
✅ en/vereine mit JavaScript, Pflichtfelder leer · Turbo true · action /en/organisationen · POST /en/organisationen → 422 · Fehlerfelder 4 · Fehlerseite false · Adresse /en/organisationen/vereine · JS-Ausnahmen 0 · Konsolenfehler 0
✅ lb/vereine mit JavaScript, Pflichtfelder leer · Turbo true · action /lb/organisationen · POST /lb/organisationen → 422 · Fehlerfelder 4 · Fehlerseite false · Adresse /lb/organisationen/vereine · JS-Ausnahmen 0 · Konsolenfehler 0

4 von 4 bestanden
```

Die Adresse bleibt mit Turbo auf der Zielgruppenseite stehen. Das bestätigt BF-153 auf der Produktion.

## Allgemein

```
/                      302
/de/                   200
/de/restaurants        200
/de/organisationen     200
/health                200
/sitemap.xml           200
/robots.txt            200
404-Seite: 404, Titel „An Error Occurred: Not Found", Stacktrace-/vendor-Spuren: 0
Testdaten/QA-Spuren (QA B15, QA Kontakt, Fixture-Konten, Qa11, lorem ipsum) in /de/, /de/restaurants, /de/organisationen, /open.json: 0
Kopfzeilen auf /de/organisationen/gemeinden: HSTS, nosniff, X-Frame-Options DENY, Referrer-Policy, CSP-Report-Only
```

Nicht wiederholt, weil diese Auslieferung sie nicht berührt: Anmeldung mit echtem Konto und der Deckel der
Anmeldung. Die Änderung betrifft ausschließlich `templates/organisation/_form.html.twig`.
