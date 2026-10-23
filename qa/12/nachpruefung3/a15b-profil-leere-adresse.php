<?php
// Ergänzung zu a15 (BF-182): dasselbe am Feld „E-Mail-Adresse" des Profils.
require __DIR__.'/treiber.php';
$e = zeile("SELECT email FROM `user` WHERE email LIKE 'paula4-%' ORDER BY id DESC")['email'];
$p = new Geraet('http://127.0.0.1:8099');
$p->anmelden($e, 'Sicher-Passwort-2026!');
foreach (['leer' => '', 'drei Leerzeichen' => '   '] as $was => $wert) {
    $p->get('/de/profile');
    $p->absenden('form[action$="/profile/edit"]', ['profile[email]' => $wert]);
    $titel = $p->c->filter('title')->count() ? trim($p->c->filter('title')->text()) : '';
    aus("Adresse $was: {$p->status()} {$p->ziel()} · Titel „".mb_substr($titel, 0, 90).'“ · Adresse in DB: '.(konto($e)['email'] ?? 'NULL'));
}
