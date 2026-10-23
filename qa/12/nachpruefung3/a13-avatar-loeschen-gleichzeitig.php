<?php
// Hinweis des code-reviewer: `AvatarUploadService::delete()` liegt nicht unter der Sperre aus BF-173. Gleichzeitig: Upload + Entfernen.
require __DIR__.'/treiber.php';
const AVATARE = __DIR__.'/../../../public/uploads/avatars';
$basis = 'http://127.0.0.1:8096';
$s = date('His');
$verwaistGesamt = 0;
foreach ([0, 20, 40, 60, 80, 100, 130, 160, 200, 250] as $d => $verzoegerung) {
    $e = "loesch4-$s-$d@example.org";
    registriere("Loesch Vierte $d", $e);
    (new Geraet($basis))->get(bestaetigungslink($e));
    $a = new Geraet($basis);
    $a->anmelden($e, 'Sicher-Passwort-2026!');
    $a->get('/de/einfuehrung/profilbild');
    $a->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => '/tmp/endlech-f12/bilder/klein.png']);   // erstes Bild
    $b = new Geraet($basis);
    $b->anmelden($e, 'Sicher-Passwort-2026!');
    $b->get('/de/profile');
    $loeschToken = $b->c->filter('form#profil-bild-entfernen input[name="_token"]')->attr('value');
    $ck = static fn (Geraet $g) => implode('; ', array_map(static fn ($c) => $c->getName().'='.$c->getValue(), $g->b->getCookieJar()->all()));
    $vorher = glob(AVATARE.'/*') ?: [];
    $mh = curl_multi_init();
    $h1 = curl_init($basis.'/de/einfuehrung/profilbild');
    curl_setopt_array($h1, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Cookie: '.$ck($a), 'Origin: '.$basis],
        CURLOPT_POSTFIELDS => ['onboarding_avatar[_token]' => 'csrf-token', 'onboarding_avatar[avatar]' => new CURLFile('/tmp/endlech-f12/bilder/klein.jpg', 'image/jpeg', 'neu.jpg')]]);
    $h2 = curl_init($basis.'/de/profile/avatar/delete');
    curl_setopt_array($h2, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Cookie: '.$ck($b), 'Origin: '.$basis], CURLOPT_POSTFIELDS => ['_token' => $loeschToken]]);
    curl_multi_add_handle($mh, $h1);
    $t0 = microtime(true);
    $zweiter = false;
    do {
        curl_multi_exec($mh, $l);
        if (!$zweiter && (microtime(true) - $t0) * 1000 >= $verzoegerung) { curl_multi_add_handle($mh, $h2); $zweiter = true; curl_multi_exec($mh, $l); }
        curl_multi_select($mh, 0.005);
    } while ($l > 0 || !$zweiter);
    clearstatcache();
    $nachher = glob(AVATARE.'/*') ?: [];
    $neu = array_map('basename', array_values(array_diff($nachher, $vorher)));
    $ref = konto($e)['avatar_filename'];
    $verwaist = array_values(array_filter($neu, static fn ($n) => null === zeile('SELECT id FROM `user` WHERE avatar_filename = ?', [$n])));
    $verwaistGesamt += count($verwaist);
    aus(sprintf('Verzögerung '.$verzoegerung.' ms · Durchgang %d: Upload %d, Entfernen %d · Konto verweist auf %s · neue Dateien %d · verwaist %d', $d, curl_getinfo($h1, CURLINFO_HTTP_CODE), curl_getinfo($h2, CURLINFO_HTTP_CODE), $ref ?? 'NULL', count($neu), count($verwaist)));
}
aus("Summe verwaister Dateien: $verwaistGesamt");
