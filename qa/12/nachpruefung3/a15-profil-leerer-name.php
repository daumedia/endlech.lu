<?php
// Angriff/Randfall B04 (vom Branch geänderter Controller): Profil mit leerem, nur aus Leerzeichen bestehendem oder fehlendem Namen.
require __DIR__.'/treiber.php';
$e = zeile("SELECT email FROM `user` WHERE email LIKE 'paula4-%' ORDER BY id DESC")['email'];
$p = new Geraet('http://127.0.0.1:8099');
$p->anmelden($e, 'Sicher-Passwort-2026!');
foreach (['leer' => '', 'drei Leerzeichen' => '   ', 'ein Buchstabe' => 'P'] as $was => $wert) {
    $p->get('/de/profile');
    $p->absenden('form[action$="/profile/edit"]', ['profile[name]' => $wert]);
    $titel = $p->c->filter('title')->count() ? trim($p->c->filter('title')->text()) : '';
    aus("Name $was: {$p->status()} {$p->ziel()} · Titel „".mb_substr($titel, 0, 90).'“ · Name in DB: '.konto($e)['name']);
}
