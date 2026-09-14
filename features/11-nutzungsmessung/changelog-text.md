# 11 · Changelog-Text — Entwurf

Entwurf für den öffentlichen Eintrag auf `/changelog` (AK-32, T27). Er wird **erst im Release-Commit**
eingetragen, weil die Version erst dort feststeht:

1. `src/Roadmap/ChangelogRegistry.php`: `new ReleaseNote('<version>', …, ReleaseVisibility::SHOWN)`
2. `translations/changelog.{de,en,fr,lb}.yaml`: Schlüssel `v<version mit Unterstrichen>` mit `title` und
   `body` aus der Tabelle unten

⚠ **Dieselbe vorsichtige Formulierung wie in `/legal` (AK-31, OF-01):** nur Nachstellbares — keine
Cookies, keine gespeicherte IP-Adresse, kein Konto, keine Formularinhalte. Weder „anonym" noch
„keine Kennung". Ändert `/sdd-betrieb` vor dem Deploy etwas an der Einschätzung, zieht dieser Text mit.

⚠ Der Changelog verlinkt nicht selbst; der Verweis auf den Datenschutzabschnitt steht als Wort im Text
(„Datenschutzerklärung"), wie bei den übrigen Einträgen.

| Sprache | `title` | `body` |
|---|---|---|
| de | Wir messen jetzt, was gelesen wird — ohne Cookies | Um zu sehen, welche Seiten helfen und wo die Suche abbricht, zählen wir Seitenaufrufe und einige Schritte wie „Filter angewandt“. Das läuft auf unserem eigenen Server: keine Cookies, keine gespeicherte IP-Adresse, nichts, das mit einem Konto verbunden wäre. Wer nicht gezählt werden will, schaltet es in der Datenschutzerklärung aus — oder schickt „Do Not Track“ oder „Global Privacy Control“ aus dem Browser. |
| en | We now measure what gets read — without cookies | To see which pages help and where searches are abandoned, we count page views and a few steps such as “filter applied”. It runs on our own server: no cookies, no stored IP address, nothing linked to an account. If you would rather not be counted, switch it off in the privacy policy — or send “Do Not Track” or “Global Privacy Control” from your browser. |
| fr | Nous mesurons désormais ce qui est lu — sans cookies | Pour savoir quelles pages sont utiles et où la recherche s’interrompt, nous comptons les pages vues et quelques étapes comme « filtre appliqué ». Cela tourne sur notre propre serveur : pas de cookies, pas d’adresse IP conservée, rien qui soit lié à un compte. Si vous ne souhaitez pas être compté·e, désactivez-le dans la politique de confidentialité — ou envoyez « Do Not Track » ou « Global Privacy Control » depuis votre navigateur. |
| lb | Mir moossen elo, wat gelies gëtt — ouni Cookien | Fir ze gesinn, wéi eng Säiten hëllefen a wou d’Sich ofbrécht, zielen mir Säitenopruffer an e puer Schrëtt wéi „Filter applizéiert“. Dat leeft op eisem eegene Server: keng Cookien, keng gespäichert IP-Adress, näischt, wat mat engem Kont verbonnen ass. Wien net gezielt wëll ginn, schalt et an der Dateschutzerklärung aus — oder schéckt „Do Not Track“ oder „Global Privacy Control“ aus dem Browser. |
