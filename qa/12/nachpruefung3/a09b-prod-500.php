<?php
// Gegenprobe zu AK-55: Was schreibt der Produktionsmodus, wenn im angemeldeten Zustand ein Fehler entsteht (fingers_crossed leert dann den Puffer)?
// Auslöser: der vorbestehende 500er im Profil (Name aus Leerzeichen, siehe a15), danach ein Newsletter-Wechsel im selben Zustand.
require __DIR__.'/treiber.php';
$rosa = trim(file_get_contents('/tmp/endlech-f12/rosa.txt'));
$g = new Geraet('http://127.0.0.1:8095');
$g->anmelden($rosa, 'Sicher-Passwort-2026!');
$g->get('/de/profile');
$g->absenden('form[action$="/profile/edit"]', ['profile[name]' => '   ']);
aus("Profil mit Name aus Leerzeichen (prod): {$g->status()}");
