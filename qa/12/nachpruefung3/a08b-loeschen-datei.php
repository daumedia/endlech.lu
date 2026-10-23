<?php
// AK-61, Gegenprobe zur Zeile „Datei vorhanden JA" in a08: PHP hält is_file() im Stat-Cache — hier mit clearstatcache() und `test -e`.
require __DIR__.'/treiber.php';
const AVATARE = __DIR__.'/../../../public/uploads/avatars';
$s = date('His'); $pw = 'Sicher-Passwort-2026!';
$x = "ines4b-$s@example.org";
registriere('Ines Gegenprobe', $x);
(new Geraet())->get(bestaetigungslink($x));
$g = new Geraet();
$g->anmelden($x, $pw)->folgen();
$g->get('/de/einfuehrung/profilbild');
$g->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => '/tmp/endlech-f12/bilder/klein.png']);
$name = konto($x)['avatar_filename'];
clearstatcache();
aus("vorher: Bild $name · is_file ".(is_file(AVATARE."/$name") ? 'ja' : 'nein').' · test -e '.trim((string) shell_exec('test -e '.escapeshellarg(AVATARE."/$name").' && echo ja || echo nein')));
$g->get('/de/profile');
$g->absenden('form[action$="/profile/loeschen"]', ['password' => $pw]);
aus("löschen: {$g->status()} → {$g->ziel()} · Konto ".zeige(konto($x)));
clearstatcache();
aus('nachher: is_file '.(is_file(AVATARE."/$name") ? 'JA' : 'nein').' · test -e '.trim((string) shell_exec('test -e '.escapeshellarg(AVATARE."/$name").' && echo JA || echo nein')).' · Kontakt '.zeige(kontakt($x)));
