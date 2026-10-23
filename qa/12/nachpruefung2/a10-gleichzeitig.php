<?php

declare(strict_types=1);

// BF-172 (gleichzeitiges Erst-Abo) und BF-173 (gleichzeitige Uploads) gegen Server mit vier Arbeitsprozessen.
// Aufruf: php a10-gleichzeitig.php http://127.0.0.1:8096   bzw. http://127.0.0.1:8093 (Produktionsmodus)
require __DIR__.'/treiber.php';

$basis = $argv[1] ?? 'http://127.0.0.1:8096';
$durchgaenge = (int) ($argv[2] ?? 5);
const AVATARE = __DIR__.'/../../../public/uploads/avatars';

/** @return array{string, Geraet} Cookie-Kopf und Gerät */
function sitzung(string $basis, string $email): array
{
    $g = new Geraet($basis);
    $g->anmelden($email, 'Sicher-Passwort-2026!');
    $c = [];
    foreach ($g->b->getCookieJar()->all() as $k) {
        $c[] = $k->getName().'='.$k->getValue();
    }

    return [implode('; ', $c), $g];
}

/** @param list<array{url: string, cookie: string, felder: array<string, mixed>}> $anfragen @return list<array{int, string}> */
function gleichzeitig(array $anfragen, string $basis): array
{
    $mh = curl_multi_init();
    $hs = [];
    foreach ($anfragen as $a) {
        $h = curl_init($a['url']);
        curl_setopt_array($h, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $a['felder'], CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => ['Cookie: '.$a['cookie'], 'Origin: '.$basis, 'Referer: '.$basis.'/de/profile'], CURLOPT_TIMEOUT => 60]);
        curl_multi_add_handle($mh, $h);
        $hs[] = $h;
    }
    do {
        curl_multi_exec($mh, $laufend);
        curl_multi_select($mh, 0.05);
    } while ($laufend > 0);
    $r = [];
    foreach ($hs as $h) {
        $antwort = (string) curl_multi_getcontent($h);
        preg_match('/^Location: (.*)$/mi', $antwort, $ort);
        preg_match('/SQLSTATE\[[^\]]+\][^<"]{0,80}/', $antwort, $sql);
        $r[] = [curl_getinfo($h, CURLINFO_HTTP_CODE), trim($ort[1] ?? '').($sql ? ' · '.$sql[0] : '')];
        curl_multi_remove_handle($mh, $h);
    }

    return $r;
}

$s = date('His');
aus("Server $basis");

// --- BF-172 ---------------------------------------------------------------------------------------
$fuenfhundert = 0;
for ($d = 1; $d <= $durchgaenge; ++$d) {
    $e = "par3-$s-$d@example.org";
    registriere("Parallel Dritte $d", $e);
    (new Geraet($basis))->get(bestaetigungslink($e));
    $anfragen = [];
    for ($i = 0; $i < 2; ++$i) {
        [$cookie, $g] = sitzung($basis, $e);
        $g->get('/de/profile');
        $anfragen[] = ['url' => $basis.'/de/profile/newsletter', 'cookie' => $cookie,
            'felder' => ['_token' => $g->c->filter('form[action$="/profile/newsletter"] input[name="_token"]')->attr('value'), 'action' => 'subscribe']];
    }
    $dupVorher = (int) zeile('SELECT SUM_ERROR_RAISED n FROM performance_schema.events_errors_summary_global_by_error WHERE ERROR_NAME = "ER_DUP_ENTRY"')['n'];
    $r = gleichzeitig($anfragen, $basis);
    $dup = (int) zeile('SELECT SUM_ERROR_RAISED n FROM performance_schema.events_errors_summary_global_by_error WHERE ERROR_NAME = "ER_DUP_ENTRY"')['n'] - $dupVorher;
    $fuenfhundert += count(array_filter($r, static fn ($x) => 500 === $x[0]));
    aus("BF-172 Durchgang $d: ".implode(' | ', array_map(static fn ($x) => $x[0].' '.$x[1], $r)).' · Kontaktzeilen '.zeile('SELECT COUNT(*) n FROM marketing_contact WHERE email = ?', [$e])['n'].' · Konto abonniert: '.(konto($e)['marketing_consent_at'] ? 'ja' : 'NEIN').' · MySQL-Fehler 1062 (Duplicate entry) in diesem Durchgang: '.$dup);
}
aus("BF-172 Summe: $fuenfhundert × HTTP 500 in $durchgaenge Durchgängen");

// --- BF-173 ---------------------------------------------------------------------------------------
for ($d = 1; $d <= $durchgaenge; ++$d) {
    $e = "bild3-$s-$d@example.org";
    registriere("Bild Dritte $d", $e);
    (new Geraet($basis))->get(bestaetigungslink($e));
    $anfragen = [];
    for ($i = 0; $i < 4; ++$i) {
        [$cookie, $g] = sitzung($basis, $e);
        $g->get('/de/einfuehrung/profilbild');
        $anfragen[] = ['url' => $basis.'/de/einfuehrung/profilbild', 'cookie' => $cookie, 'felder' => [
            'onboarding_avatar[_token]' => 'csrf-token',
            'onboarding_avatar[avatar]' => new CURLFile('/tmp/endlech-f12/bilder/mittel.jpg', 'image/jpeg', "p$i.jpg"),
        ]];
    }
    $vorher = glob(AVATARE.'/*') ?: [];
    $r = gleichzeitig($anfragen, $basis);
    $nachher = glob(AVATARE.'/*') ?: [];
    $neu = array_map('basename', array_values(array_diff($nachher, $vorher)));
    $referenz = konto($e)['avatar_filename'];
    $verwaist = array_values(array_filter($neu, static fn ($n) => $n !== $referenz && null === zeile('SELECT id FROM `user` WHERE avatar_filename = ?', [$n])));
    aus("BF-173 Durchgang $d: ".implode(' | ', array_map(static fn ($x) => (string) $x[0], $r)).' · neue Dateien '.count($neu).' · Konto verweist auf '.$referenz.' · verwaist '.count($verwaist).($verwaist ? ' ('.implode(', ', $verwaist).')' : ''));
}
