<?php
// AK-55 (Protokolle) und AK-63 (Schlüssel im Quelltext) im Produktionsmodus :8095.
require __DIR__.'/treiber.php';
const P = 'http://127.0.0.1:8095';
$s = date('His');
$rosa = "rosa.geheimname4-$s@example.org";
$r = new Geraet(P);
$r->registrieren('Rosa Geheimname', $rosa);
aus("Registrierung (prod): {$r->status()} → {$r->ziel()}");
(new Geraet(P))->get(bestaetigungslink($rosa));
$g = new Geraet(P);
$g->anmelden($rosa, 'Sicher-Passwort-2026!');
aus("Anmeldung: {$g->status()} → {$g->ziel()}");
$quellen = [];
foreach (['willkommen', 'eintraege', 'profilbild', 'newsletter'] as $schritt) {
    $g->get("/de/einfuehrung/$schritt");
    $quellen["einfuehrung/$schritt"] = $g->html();
}
$g->get('/de/einfuehrung/profilbild');
$g->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => '/tmp/endlech-f12/bilder/Rosa Privat Geheimname.jpg']);
aus("Upload „Rosa Privat Geheimname.jpg“: {$g->status()} · gespeichert als ".konto($rosa)['avatar_filename']);
$g->get('/de/einfuehrung/profilbild');
$g->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => '/tmp/endlech-f12/bilder/Rosa Zu Gross Geheimname.jpg']);
aus("Fehlupload „Rosa Zu Gross Geheimname.jpg“: {$g->status()}");
$g->get('/de/einfuehrung/newsletter');
$g->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true]);
aus("Abo in der Einführung: {$g->status()} → {$g->ziel()}");
foreach (['unsubscribe', 'subscribe', 'unsubscribe'] as $a) {
    $g->get('/de/profile');
    $quellen['profile'] = $g->html();
    $g->absenden('form[action$="/profile/newsletter"]');
    aus("Profilschalter $a: {$g->status()} → {$g->ziel()}");
}
$g->get('/de/einfuehrung/newsletter');
$g->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true], [], ['HTTP_ORIGIN' => 'https://boese.example', 'HTTP_REFERER' => 'https://boese.example/']);
aus("Fremde Herkunft: {$g->status()}");
$g->get('/de/einfuehrung/gibtsnicht');
aus("404: {$g->status()}");
$g->get('/de/profile');
$quellen['profile'] = $g->html();
foreach ($quellen as $seite => $html) {
    aus(sprintf('AK-63 %-24s %6d B · „xkeysib-qa12vierteattrappe“: %d · „xkeysib-“: %d · „api-key“: %d', $seite, strlen($html), substr_count($html, 'xkeysib-qa12vierteattrappe'), substr_count($html, 'xkeysib-'), substr_count(strtolower($html), 'api-key')));
}
file_put_contents('/tmp/endlech-f12/rosa.txt', $rosa);
