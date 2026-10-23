<?php
// Hinweis des code-reviewer, nachgestellt: Adresswechsel im Profil (B04) auf eine RFC-widrige bzw. zu lange Adresse. Gegen :8099.
require __DIR__.'/treiber.php';
$s = date('His');
foreach (['RFC-widrig' => '../../etc/passwd@example.lu', 'Punkt am Ende des lokalen Teils' => "vera4.$s.@example.org", '181 Zeichen' => str_repeat('a', 181 - strlen("@example.org")).'@example.org'] as $was => $neu) {
    $e = "vera4-$s-".substr(md5($was), 0, 4).'@example.org';
    registriere('Vera Vierte', $e);
    (new Geraet())->get(bestaetigungslink($e));
    $g = new Geraet();
    $g->anmelden($e, 'Sicher-Passwort-2026!');
    $g->get('/de/profile');
    $g->absenden('form[action$="/profile/edit"]', ['profile[email]' => $neu]);
    $titel = $g->c->filter('title')->count() ? trim($g->c->filter('title')->text()) : '';
    $k = konto($e);
    aus(sprintf('%s (%d Zeichen): %d %s · Titel „%s“ · email=%s · pending_email=%s', $was, strlen($neu), $g->status(), $g->ziel(), mb_substr($titel, 0, 110), $k['email'] ?? 'NULL', $k['pending_email'] ?? 'NULL'));
    if (302 === $g->status()) { $g->folgen(); $f = $g->c->filter('[role=alert]'); aus('   Flash „'.($f->count() ? trim($f->first()->text()) : '').'“'); }
    // Danach: kann man den Vorgang im Profil sehen/abbrechen?
    $g->get('/de/profile');
    aus('   Profil danach: '.$g->status().' · Hinweis auf offenen Wechsel: '.(str_contains($g->html(), htmlspecialchars($neu)) || str_contains($g->html(), $neu) ? 'ja' : 'nein'));
}
