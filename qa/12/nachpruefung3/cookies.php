<?php
// Anmeldung per Treiber, Cookies als Kopfzeile für curl ausgeben. Aufruf: php cookies.php <basis> <email-präfix>
require __DIR__.'/treiber.php';
$e = zeile('SELECT email FROM `user` WHERE email LIKE ? ORDER BY id DESC', [$argv[2].'%'])['email'];
$p = new Geraet($argv[1]);
$p->anmelden($e, 'Sicher-Passwort-2026!');
foreach ($p->b->getCookieJar()->all() as $c) { echo $c->getName(), '=', $c->getValue(), '; '; }
