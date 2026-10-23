<?php
// Angriff 7 · Eingaben an die drei Formulare der Einführung/des Profils — nie 500, nie gespeichert ohne Grund. :8099
require __DIR__.'/treiber.php';
$e = zeile("SELECT email FROM `user` WHERE email LIKE 'olga3-%' ORDER BY id DESC")['email'];
$g = new Geraet();
$g->anmelden($e, 'Sicher-Passwort-2026!');
$vorher = konto($e);
$faelle = [
    'consent leer' => ['onboarding_newsletter' => ['consent' => '', '_token' => 'csrf-token']],
    'consent "nein"' => ['onboarding_newsletter' => ['consent' => 'nein', '_token' => 'csrf-token']],
    'consent als Feld-Liste' => ['onboarding_newsletter' => ['consent' => ['1', '2'], '_token' => 'csrf-token']],
    'consent 10 000 Zeichen' => ['onboarding_newsletter' => ['consent' => str_repeat('A', 10000), '_token' => 'csrf-token']],
    'consent SQL' => ['onboarding_newsletter' => ['consent' => "1'; DROP TABLE user; --", '_token' => 'csrf-token']],
    'Formular als Zeichenkette' => ['onboarding_newsletter' => '<script>alert(1)</script>'],
    'Token als Liste' => ['onboarding_newsletter' => ['consent' => '1', '_token' => ['x']]],
];
foreach ($faelle as $name => $felder) {
    $g->post('/de/einfuehrung/newsletter', $felder);
    aus(sprintf('Newsletter-Schritt %-26s → %d %s', $name, $g->status(), $g->ziel()));
}
foreach (['subscribe ' => 'subscribe ', 'Liste' => ['subscribe'], 'leer' => '', 'Skript' => '<script>'] as $name => $aktion) {
    $g->get('/de/profile');
    $t = $g->c->filter('form[action$="/profile/newsletter"] input[name="_token"]')->attr('value');
    $g->post('/de/profile/newsletter', ['_token' => $t, 'action' => $aktion]);
    aus(sprintf('Profilschalter action=%-12s → %d %s', $name, $g->status(), $g->ziel()));
}
$g->post('/de/profile/newsletter', ['_token' => ['a'], 'action' => 'subscribe']);
aus("Profilschalter _token als Liste → {$g->status()} {$g->ziel()}");
// Upload mit Pfadwechsel im Dateinamen und 255-Zeichen-Namen
foreach (['../../../../etc/passwd.png', str_repeat('ä', 200).'.png', '<img src=x onerror=alert(1)>.png'] as $name) {
    $g->get('/de/einfuehrung/profilbild');
    $h = curl_init($g->basis.'/de/einfuehrung/profilbild');
    $cookie = implode('; ', array_map(static fn ($c) => $c->getName().'='.$c->getValue(), $g->b->getCookieJar()->all()));
    curl_setopt_array($h, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Cookie: '.$cookie, 'Origin: '.$g->basis],
        CURLOPT_POSTFIELDS => ['onboarding_avatar[_token]' => 'csrf-token', 'onboarding_avatar[avatar]' => new CURLFile('/tmp/endlech-f12/bilder/klein.png', 'image/png', $name)]]);
    curl_exec($h);
    aus(sprintf('Upload Dateiname %-40s → %d · gespeichert als %s', mb_substr($name, 0, 38), curl_getinfo($h, CURLINFO_HTTP_CODE), konto($e)['avatar_filename']));
}
foreach (['/de/einfuehrung/newsletter', '/de/einfuehrung/profilbild', '/de/profile/newsletter'] as $p) {
    $g->b->request('PUT', $g->basis.$p);
    aus("PUT $p → {$g->status()}");
}
$g->get('/de/profile/newsletter');
aus("GET /de/profile/newsletter → {$g->status()}");
$n = konto($e);
aus('Konto: Einwilligung vorher '.($vorher['marketing_consent_at'] ?? 'NULL').' · nachher '.($n['marketing_consent_at'] ?? 'NULL'));
