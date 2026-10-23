<?php

declare(strict_types=1);

// Vorbestehend B04, beim Prüfen der Profilseite (BF-164/BF-173-Reparatur) gefunden:
// Adresswechsel + Bild-Upload im selben Absenden, wenn der Deckel `email_change` erreicht ist. Gegen :8099.
require __DIR__.'/treiber.php';

$s = date('His');
$eva = "eva3-$s@example.org";
$neu = "uebernahme3-$s@example.org";
registriere('Eva Dritte', $eva);
(new Geraet())->get(bestaetigungslink($eva));
$e = new Geraet();
$e->anmelden($eva, 'Sicher-Passwort-2026!');
aus("Eva angemeldet: {$e->status()} → {$e->ziel()} · ".zeige(array_intersect_key(konto($eva), array_flip(['id', 'email', 'pending_email']))));

for ($i = 1; $i <= 3; ++$i) {
    $e->get('/de/profile');
    $e->absenden('form[action$="/profile/edit"]', ['profile[email]' => $neu]);
    $e->folgen();
    $f = $e->c->filter('[role=alert]');
    aus("Adresswechsel $i auf $neu (ohne Bild): Flash „".($f->count() ? trim($f->first()->text()) : '')."“ · DB ".zeige(array_intersect_key(konto($eva), array_flip(['email', 'pending_email']))));
}

// Viertes Absenden: dieselbe neue Adresse, dazu ein Profilbild.
$e->get('/de/profile');
$e->absenden('form[action$="/profile/edit"]', ['profile[email]' => $neu], ['profile[avatar]' => '/tmp/endlech-f12/bilder/klein.jpg']);
aus("4. Absenden mit Bild: {$e->status()} → {$e->ziel()}");
$e->folgen();
$f = $e->c->filter('[role=alert]');
aus('  Flash „'.($f->count() ? trim($f->first()->text()) : '').'“');
$k = zeile('SELECT id, email, pending_email, is_verified FROM `user` WHERE id = ?', [konto($neu)['id'] ?? konto($eva)['id'] ?? 0]);
aus('  Konto in der Datenbank: '.zeige($k));
aus('  Konto unter alter Adresse: '.zeige(konto($eva)).' · unter neuer Adresse: '.zeige(konto($neu)));

// Was die Übernahme bedeutet: Anmeldung mit der NEUEN Adresse und dem Passwort; Passwort-Zurücksetzen an die neue Adresse.
$x = new Geraet();
$x->anmelden($neu, 'Sicher-Passwort-2026!');
aus("Anmeldung mit der neuen, nie bestätigten Adresse: {$x->status()} → {$x->ziel()}");
$y = new Geraet();
$y->anmelden($eva, 'Sicher-Passwort-2026!');
aus("Anmeldung mit der bisherigen Adresse: {$y->status()} → {$y->ziel()}");
$r = new Geraet();
$r->get('/de/passwort-vergessen');
$r->absenden('form', [$r->c->filter('form input[type="email"]')->attr('name') => $neu]);
aus("Passwort vergessen für die neue Adresse: {$r->status()} → {$r->ziel()} · Reset-Token gesetzt: ".(null !== (zeile('SELECT password_reset_token t FROM `user` WHERE email = ?', [$neu])['t'] ?? null) ? 'JA (Link ginge an '.$neu.')' : 'nein'));
