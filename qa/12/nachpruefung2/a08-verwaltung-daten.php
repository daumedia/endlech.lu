<?php

declare(strict_types=1);

// AK-49, AK-53, AK-57, AK-58, AK-61, AK-62, EC-09, EC-10 — gegen :8099.
require __DIR__.'/treiber.php';

$s = date('His');
$pw = 'Sicher-Passwort-2026!';
const AVATARE = __DIR__.'/../../../public/uploads/avatars';

function kachel(): string
{
    $a = new Geraet();
    $a->anmelden('admin@endlech.lu', 'admin123');
    $a->get('/de/admin');
    $k = $a->c->filter('main')->filter('*')->reduce(static fn ($n) => str_contains($n->attr('class') ?? '', 'rounded') && str_contains($n->text(), 'Einführung') && $n->filter('a')->count() === 0 && mb_strlen($n->text()) < 200);
    $text = $k->count() ? trim((string) preg_replace('/\s+/u', ' ', $k->last()->text())) : 'KEINE KACHEL';
    $html = $k->count() ? $k->last()->outerHtml() : '';

    return $text.' · Links in der Kachel: '.substr_count($html, '<a ').' · @ in der Kachel: '.substr_count($html, '@');
}

$soll = zeile('SELECT SUM(onboarding_due = 1 AND is_verified = 1) vorgesehen, SUM(onboarding_due = 1 AND is_verified = 1 AND onboarding_shown_at IS NOT NULL) angezeigt FROM `user`');
aus('AK-49 SQL: '.zeige($soll));
aus('AK-49 Kachel: '.kachel());
$nutzer = new Geraet();
$nutzer->anmelden('user@endlech.lu', 'user123');
$nutzer->get('/de/admin');
aus("AK-49 /de/admin als Nutzer: {$nutzer->status()}");

// EC-09: Neustart über das Profil ändert Kachel und Datum nicht
$frieda = zeile("SELECT email FROM `user` WHERE email LIKE 'frieda3-%' ORDER BY id DESC")['email'];
$f = new Geraet();
$f->anmelden($frieda, $pw);
$vorher = konto($frieda)['onboarding_shown_at'];
$f->get('/de/profile');
$f->get('/de/einfuehrung');
aus("EC-09 Neustart: {$f->status()} „".$f->text('h1').'“ · shown vorher '.$vorher.' nachher '.konto($frieda)['onboarding_shown_at'].' · Kachel: '.kachel());

// AK-57: fremde Kennung mitschicken
$anna = zeile("SELECT email FROM `user` WHERE email LIKE 'anna3-%' ORDER BY id DESC")['email'];
$admin = konto('admin@endlech.lu');
$an = new Geraet();
$an->anmelden($anna, $pw);
$an->get('/de/einfuehrung/newsletter');
$tok = $an->c->filter('input[name="onboarding_newsletter[_token]"]')->attr('value');
$an->post('/de/einfuehrung/newsletter', ['onboarding_newsletter' => ['consent' => '1', '_token' => $tok, 'user' => $admin['id'], 'email' => 'admin@endlech.lu'], 'user' => $admin['id'], 'user_id' => $admin['id'], 'email' => 'admin@endlech.lu']);
aus("AK-57 Newsletter-Schritt mit Admin-Kennung: {$an->status()} → {$an->ziel()} · Anna ".(konto($anna)['marketing_consent_at'] ? 'abonniert' : 'NICHT').' · Admin vorher '.zeige($admin).' nachher '.zeige(konto('admin@endlech.lu')));
$an->get('/de/einfuehrung/profilbild');
$tok = $an->c->filter('input[name="onboarding_avatar[_token]"]')->attr('value');
$an->post('/de/einfuehrung/profilbild', ['onboarding_avatar' => ['_token' => $tok, 'user' => $admin['id']], 'user' => $admin['id']], ['onboarding_avatar' => ['avatar' => ['tmp_name' => '/tmp/endlech-f12/bilder/klein.png', 'name' => 'klein.png', 'type' => 'image/png', 'size' => filesize('/tmp/endlech-f12/bilder/klein.png'), 'error' => 0]]]);
aus("AK-57 Upload mit Admin-Kennung: {$an->status()} → {$an->ziel()} · Anna Bild ".(konto($anna)['avatar_filename'] ?? 'NULL').' · Admin Bild '.(konto('admin@endlech.lu')['avatar_filename'] ?? 'NULL'));
$an->get('/de/profile');
$tok = $an->c->filter('form[action$="/profile/newsletter"] input[name="_token"]')->attr('value');
$an->post('/de/profile/newsletter', ['_token' => $tok, 'action' => 'unsubscribe', 'user' => $admin['id'], 'email' => 'admin@endlech.lu']);
aus("AK-57 Profilschalter mit Admin-Kennung: {$an->status()} → {$an->ziel()} · Anna ".(konto($anna)['marketing_consent_at'] ? 'abonniert' : 'nicht abonniert').' · Admin nachher '.zeige(konto('admin@endlech.lu')));

// AK-58: fremde Website
$vorher = konto($anna);
$boese = ['HTTP_ORIGIN' => 'https://boese.example', 'HTTP_REFERER' => 'https://boese.example/falle'];
$an->get('/de/einfuehrung/profilbild');
$an->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => '/tmp/endlech-f12/bilder/klein.jpg'], $boese);
aus("AK-58 Upload von fremder Herkunft: {$an->status()} · Meldung „".($an->c->filter('[role=alert]')->count() ? trim($an->c->filter('[role=alert]')->first()->text()) : '').'“');
$an->post('/de/profile/newsletter', ['_token' => 'gefaelscht', 'action' => 'subscribe'], [], $boese);
$z = $an->ziel();
$an->folgen();
aus("AK-58 Profilschalter mit falschem Token: → $z · Flash „".($an->c->filter('[role=alert]')->count() ? trim($an->c->filter('[role=alert]')->first()->text()) : '').'“');
$an->post('/de/einfuehrung/newsletter', ['onboarding_newsletter' => ['consent' => '1', '_token' => 'csrf-token']], [], $boese);
aus("AK-58 Newsletter-Schritt ohne Token-Cookie, fremde Herkunft: {$an->status()}");
aus('AK-58 Anna vorher = nachher: '.(zeige($vorher) === zeige(konto($anna)) ? 'ja' : 'NEIN '.zeige(konto($anna))));

// AK-62: Datenexport
$dora = zeile("SELECT email FROM `user` WHERE email LIKE 'dora3-%' ORDER BY id DESC")['email'];
$d = new Geraet();
$d->anmelden($dora, $pw);
$d->get('/de/profile/daten');
$json = json_decode($d->html(), true);
$flach = static function ($a, $p = '') use (&$flach) { $r = []; foreach ((array) $a as $k => $v) { if (is_array($v)) { $r += $flach($v, $p.$k.'.'); } else { $r[$p.$k] = $v; } } return $r; };
$treffer = array_filter($flach($json), static fn ($k) => (bool) preg_match('/onboarding|marketing|newsletter/i', (string) $k), ARRAY_FILTER_USE_KEY);
aus("AK-62 Export {$d->status()} ".$d->b->getInternalResponse()->getHeader('Content-Type').': '.zeige($treffer).' · DB '.zeige(array_intersect_key(konto($dora), array_flip(['onboarding_due', 'onboarding_shown_at', 'marketing_consent_at']))));

// AK-61: Konto löschen
$ines = "ines3-$s@example.org";
registriere('Ines Dritte', $ines);
(new Geraet())->get(bestaetigungslink($ines));
$i = new Geraet();
$i->anmelden($ines, $pw)->folgen();
$i->get('/de/einfuehrung/profilbild');
$i->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => '/tmp/endlech-f12/bilder/klein.png']);
$i->get('/de/einfuehrung/newsletter');
$i->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true]);
sql("UPDATE marketing_contact SET sync_state = 'synced', synced_at = NOW() WHERE email = ?", [$ines]);
$k = konto($ines);
aus('AK-61 vorher: '.zeige($k).' · Datei vorhanden '.(is_file(AVATARE.'/'.$k['avatar_filename']) ? 'ja' : 'nein').' · Kontakt '.zeige(kontakt($ines)));
$i->get('/de/profile');
$i->absenden('form[action$="/profile/loeschen"]', ['password' => $pw]);
aus("AK-61 löschen: {$i->status()} → {$i->ziel()}");
aus('AK-61 nachher: Konto '.zeige(konto($ines)).' · Datei vorhanden '.(is_file(AVATARE.'/'.$k['avatar_filename']) ? 'JA' : 'nein').' · Kontakt '.zeige(kontakt($ines)));
$x = new Geraet();
$x->anmelden($ines, $pw);
aus("AK-61 Anmeldung danach: {$x->status()} → {$x->ziel()}");

// EC-10: Konto in anderem Tab gelöscht
$otto = "otto3-$s@example.org";
registriere('Otto Dritte', $otto);
(new Geraet())->get(bestaetigungslink($otto));
$a = new Geraet();
$a->anmelden($otto, $pw)->folgen();
$b = new Geraet();
$b->anmelden($otto, $pw);
$b->get('/de/profile');
$b->absenden('form[action$="/profile/loeschen"]', ['password' => $pw]);
aus("EC-10 Sitzung B löscht: {$b->status()} → {$b->ziel()} · Konto ".zeige(konto($otto)));
$a->get('/de/einfuehrung/eintraege');
aus("EC-10 Sitzung A nächster Schritt: {$a->status()} → {$a->ziel()}");
$a->post('/de/einfuehrung/newsletter', ['onboarding_newsletter' => ['consent' => '1']]);
aus("EC-10 Sitzung A Newsletter absenden: {$a->status()} → {$a->ziel()} · Kontakt ".zeige(kontakt($otto)).' · Konto '.zeige(konto($otto)));
