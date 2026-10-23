<?php
// Vorbestehend B04: Woran zählt der Deckel `email_change` (3/Std, „am Konto")? Gegen :8099.
require __DIR__.'/treiber.php';
$s = date('His');
$ida = "ida4-$s@example.org";
registriere('Ida Vierte', $ida);
(new Geraet())->get(bestaetigungslink($ida));
$e = new Geraet();
$e->anmelden($ida, 'Sicher-Passwort-2026!');
for ($i = 1; $i <= 8; ++$i) {
    $ziel = "ida4-$s-ziel$i@example.org";
    $e->get('/de/profile');
    $e->absenden('form[action$="/profile/edit"]', ['profile[email]' => $ziel]);
    $e->folgen();
    $f = $e->c->filter('[role=alert]');
    aus("Adresswechsel $i auf eine jeweils NEUE Adresse: Flash „".mb_substr($f->count() ? trim($f->first()->text()) : '', 0, 60)."…“ · pending=".konto($ida)['pending_email']);
}
