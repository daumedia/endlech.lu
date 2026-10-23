<?php
// AK-07: Altkonto (Fixture, am „Auslieferungstag" unbestätigt, Merker 0) fordert den Link neu an, bestätigt, meldet sich an.
require __DIR__.'/treiber.php';
$e = 'unverified@endlech.lu';
aus('AK-07 vorher: '.zeige(array_intersect_key(konto($e), array_flip(['is_verified', 'onboarding_due', 'onboarding_shown_at']))));
$u = new Geraet();
$u->anmelden($e, 'unverified123');
aus("AK-07 Anmeldung unbestätigt: {$u->status()} → {$u->ziel()}");
$u->get('/de/verify/resend');
aus("AK-07 Link neu anfordern: {$u->status()} → {$u->ziel()}");
$g = new Geraet();
$g->get(bestaetigungslink($e));
aus("AK-07 Link als Gast geöffnet: {$g->status()} → {$g->ziel()} · ".zeige(array_intersect_key(konto($e), array_flip(['is_verified', 'onboarding_due', 'onboarding_shown_at']))));
$n = new Geraet();
$n->anmelden($e, 'unverified123');
aus("AK-07 erste Anmeldung nach Bestätigung: {$n->status()} → {$n->ziel()}");
