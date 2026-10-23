<?php

declare(strict_types=1);

// AK-01 bis AK-11, EC-01, EC-04 bis EC-07, BF-161, BF-170 — gegen den laufenden Server :8099.
require __DIR__.'/treiber.php';

$s = date('His');
$pw = 'Sicher-Passwort-2026!';

// --- AK-01 / AK-02 / EC-01 -------------------------------------------------------------
$frieda = "frieda4-$s@example.org";
$reg = registriere("Frieda Vierte", $frieda);

aus("Registrierung Frieda: {$reg->status()} → {$reg->ziel()}");
aus('  Konto nach Registrierung: '.zeige(konto($frieda)));
$link = bestaetigungslink($frieda);
$gast = new Geraet();
$gast->get($link);
aus("AK-01 Link als Gast: {$gast->status()} → {$gast->ziel()}");
aus('  Konto nach Bestätigung: '.zeige(konto($frieda)));
$a = new Geraet();
$a->anmelden($frieda, $pw);
aus("AK-01 erste Anmeldung (Gerät A): {$a->status()} → {$a->ziel()}");
$a->folgen();
aus('  Seite: '.mb_substr($a->text('h1'), 0, 80).' · shown='.(konto($frieda)['onboarding_shown_at'] ?? 'NULL'));
$b = new Geraet();
$b->anmelden($frieda, $pw);
aus("AK-02/EC-01 Anmeldung Gerät B nach Anzeige: {$b->status()} → {$b->ziel()}");
$a->get('/de/logout');
$c = new Geraet();
$c->anmelden($frieda, $pw);
aus("AK-02 dritte Anmeldung: {$c->status()} → {$c->ziel()}");

// --- AK-02 Variante: nur angezeigt, mitten verlassen (Gerät verlässt nach Schritt 2) -----
$mia = "mia4-$s@example.org";
registriere('Mia Vierte', $mia);
(new Geraet())->get(bestaetigungslink($mia));
$m = new Geraet();
$m->anmelden($mia, $pw)->folgen();
$m->get('/de/einfuehrung/eintraege');
aus("AK-02 Mia verlässt auf Schritt 2: {$m->status()}, Text '".mb_substr($m->text('h1'), 0, 40)."'");
$m2 = new Geraet();
$m2->anmelden($mia, $pw);
aus("AK-02 Mia nächste Anmeldung: {$m2->status()} → {$m2->ziel()}");

// --- AK-03 -------------------------------------------------------------------------------
$bert = "bert4-$s@example.org";
registriere('Bert Vierte', $bert);
(new Geraet())->get(bestaetigungslink($bert));
$g = new Geraet();
$g->get('/de/community/suggest');
aus("AK-03 Gast ruft Vorschlagen auf: {$g->status()} → {$g->ziel()}");
$g->anmelden($bert, $pw);
aus("AK-03 Anmeldung mit gemerktem Ziel: {$g->status()} → {$g->ziel()} · shown=".(konto($bert)['onboarding_shown_at'] ?? 'NULL'));
$g2 = new Geraet();
$g2->anmelden($bert, $pw);
aus("AK-03 nächste Anmeldung: {$g2->status()} → {$g2->ziel()}");

// --- AK-04 / AK-11 / AK-05 / BF-161 ------------------------------------------------------
$anna = "anna4-$s@example.org";
registriere('Anna Vierte', $anna);
$an = new Geraet();
$an->anmelden($anna, $pw);
aus("AK-04 unbestätigt anmelden: {$an->status()} → {$an->ziel()} · ".zeige(array_intersect_key(konto($anna), array_flip(['is_verified', 'onboarding_due', 'onboarding_shown_at']))));
foreach (['/de/einfuehrung', '/de/einfuehrung/eintraege', '/de/einfuehrung/profilbild', '/de/einfuehrung/newsletter'] as $p) {
    $an->get($p);
    aus("AK-11 GET $p: {$an->status()} → {$an->ziel()}");
}
$an->post('/de/einfuehrung/newsletter', ['onboarding_newsletter' => ['consent' => '1']]);
aus("AK-11 POST newsletter: {$an->status()} → {$an->ziel()}");
$an->post('/de/einfuehrung/profilbild', [], ['onboarding_avatar' => ['avatar' => ['tmp_name' => '/dev/null', 'name' => 'x.jpg']]]);
aus("AK-11 POST profilbild: {$an->status()} → {$an->ziel()} · Konto unverändert: ".zeige(array_intersect_key(konto($anna), array_flip(['marketing_consent_at', 'avatar_filename', 'onboarding_shown_at']))));
$an->get(bestaetigungslink($anna));
aus("AK-05 eigener Link, angemeldet: {$an->status()} → {$an->ziel()}");
$an->folgen();
$t = $an->text('main');
aus('  Willkommensseite: '.mb_substr($an->text('h1'), 0, 60).' · „jetzt anmelden"-Treffer: '.substr_count(mb_strtolower($an->html()), 'jetzt anmelden').' · Meldung bestätigt: '.(str_contains($t, 'bestätigt') ? 'ja' : 'nein'));
aus('  Konto: '.zeige(array_intersect_key(konto($anna), array_flip(['is_verified', 'onboarding_due', 'onboarding_shown_at']))));

// --- AK-06 ---------------------------------------------------------------------------------
foreach (['user@endlech.lu' => 'user123', 'admin@endlech.lu' => 'admin123'] as $e => $p) {
    for ($i = 1; $i <= 2; ++$i) {
        $x = new Geraet();
        $x->anmelden($e, $p);
        aus("AK-06 $e Anmeldung $i: {$x->status()} → {$x->ziel()} · due=".konto($e)['onboarding_due']);
    }
}

// --- AK-07 (Altkonto unbestätigt: Fixture unverified@) ------------------------------------
aus('AK-07 vorher: '.zeige(array_intersect_key(konto('unverified@endlech.lu'), array_flip(['is_verified', 'onboarding_due']))));
$u = new Geraet();
$u->get(bestaetigungslink('unverified@endlech.lu'));
aus("AK-07 Link: {$u->status()} → {$u->ziel()} · ".zeige(array_intersect_key(konto('unverified@endlech.lu'), array_flip(['is_verified', 'onboarding_due']))));
$u->anmelden('unverified@endlech.lu', 'unverified123');
aus("AK-07 erste Anmeldung: {$u->status()} → {$u->ziel()}");

// --- AK-08 (iOS-App) ------------------------------------------------------------------------
$ios = "ios4-$s@example.org";
$api = new Geraet();
$api->b->request('POST', $api->basis.'/api/v1/auth/register', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], json_encode(['name' => 'Ios Vierte', 'email' => $ios, 'password' => $pw]));
aus("AK-08 API-Registrierung: {$api->status()}");
$link = bestaetigungslink($ios, 'lb');
$api2 = new Geraet();
$api2->get($link);
aus("AK-08 Link $link: {$api2->status()} → {$api2->ziel()} · due=".konto($ios)['onboarding_due']);
$w = new Geraet();
$w->anmelden($ios, $pw);
aus("AK-08 erste Website-Anmeldung: {$w->status()} → {$w->ziel()}");

// --- AK-09 -------------------------------------------------------------------------------------
$vorher = konto($frieda)['onboarding_shown_at'];
$b->get('/de/profile');
$link = $b->c->filter('a[href$="/einfuehrung"]');
aus('AK-09 Profillink: '.($link->count() ? trim($link->text()).' · prefetch='.$link->attr('data-turbo-prefetch') : 'FEHLT'));
$b->get('/de/einfuehrung');
aus("AK-09 Aufruf: {$b->status()} · '".mb_substr($b->text('h1'), 0, 40)."' · shown vorher=$vorher nachher=".konto($frieda)['onboarding_shown_at']);

// --- AK-10 -------------------------------------------------------------------------------------
$gast = new Geraet();
foreach (['/de/einfuehrung', '/de/einfuehrung/willkommen', '/de/einfuehrung/eintraege', '/de/einfuehrung/profilbild', '/de/einfuehrung/newsletter'] as $p) {
    $gast->get($p);
    aus("AK-10 Gast GET $p: {$gast->status()} → {$gast->ziel()} · Inhalt ".strlen($gast->html()).' B, Willkommen-Text: '.(str_contains($gast->html(), 'Schritt 1') ? 'JA' : 'nein'));
}
$gast->post('/de/einfuehrung/newsletter', ['onboarding_newsletter' => ['consent' => '1']]);
aus("AK-10 Gast POST newsletter: {$gast->status()} → {$gast->ziel()}");
$gast->get('/de/einfuehrung/unbekannt');
aus("AK-10 unbekannter Schritt (Gast): {$gast->status()}");
$b->get('/de/einfuehrung/unbekannt');
aus("AK-10 unbekannter Schritt (angemeldet): {$b->status()}");

// --- BF-170 (HEAD) ---------------------------------------------------------------------------
$lena = "lena4-$s@example.org";
registriere('Lena Vierte', $lena);
(new Geraet())->get(bestaetigungslink($lena));
$l = new Geraet();
$l->anmelden($lena, $pw);
aus("BF-170 Lena erste Anmeldung: {$l->status()} → {$l->ziel()} (nicht gefolgt)");
$l->head('/de/einfuehrung');
aus("BF-170 HEAD /de/einfuehrung: {$l->status()} · shown=".(konto($lena)['onboarding_shown_at'] ?? 'NULL'));
$l->get('/de/einfuehrung', ['HTTP_X_SEC_PURPOSE' => 'prefetch']);
aus("BF-158 GET mit X-Sec-Purpose: prefetch: {$l->status()} · shown=".(konto($lena)['onboarding_shown_at'] ?? 'NULL'));
$l->get('/de/einfuehrung', ['HTTP_SEC_PURPOSE' => 'prefetch;prerender']);
aus("BF-158 GET mit Sec-Purpose: prefetch;prerender: {$l->status()} · shown=".(konto($lena)['onboarding_shown_at'] ?? 'NULL'));
$l2 = new Geraet();
$l2->anmelden($lena, $pw);
aus("BF-170 Lena nächste Anmeldung: {$l2->status()} → {$l2->ziel()}");

// --- EC-04 -----------------------------------------------------------------------------------
$jana = "jana4-$s@example.org";
registriere('Jana Vierte', $jana);
$fremd = new Geraet();
$fremd->anmelden('user@endlech.lu', 'user123');
$fremd->get(bestaetigungslink($jana));
aus("EC-04 user@ öffnet Janas Link: {$fremd->status()} → {$fremd->ziel()} · Jana ".zeige(array_intersect_key(konto($jana), array_flip(['is_verified', 'onboarding_due', 'onboarding_shown_at']))).' · user@ due='.konto('user@endlech.lu')['onboarding_due']);
$fremd->get('/de/profile');
aus("EC-04 user@ noch angemeldet: Profil {$fremd->status()} · ".(str_contains($fremd->html(), 'user@endlech.lu') ? 'eigene Adresse sichtbar' : 'Adresse nicht gefunden'));
$j = new Geraet();
$j->anmelden($jana, $pw);
aus("EC-04 Janas erste Anmeldung: {$j->status()} → {$j->ziel()}");

// --- EC-05 -----------------------------------------------------------------------------------
$karl = "karl4-$s@example.org";
registriere('Karl Vierte', $karl);
sql('UPDATE `user` SET verification_token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE email = ?', [$karl]);
$k = new Geraet();
$k->get(bestaetigungslink($karl));
aus("EC-05 abgelaufener Link: {$k->status()} → {$k->ziel()} · ".zeige(array_intersect_key(konto($karl), array_flip(['is_verified', 'onboarding_due']))));

// --- EC-06 -----------------------------------------------------------------------------------
$rosi = "rosi4-$s@example.org";
registriere('Rosi Vierte', $rosi);
(new Geraet())->get(bestaetigungslink($rosi));
$r = new Geraet();
$r->anmelden($rosi, $pw, true);
aus("EC-06 Anmeldung mit „angemeldet bleiben\" (nicht gefolgt): {$r->status()} → {$r->ziel()}");
// Die erste Anmeldung hat die Einführung vorgesehen, aber noch nicht gezeigt — Anzeige nie aufgerufen.
$jar = $r->b->getCookieJar();
$merk = $jar->get('REMEMBERME');
aus('  REMEMBERME-Cookie: '.($merk ? 'vorhanden' : 'FEHLT').' · shown='.(konto($rosi)['onboarding_shown_at'] ?? 'NULL'));
$r2 = new Geraet();
if ($merk) {
    $r2->b->getCookieJar()->set(new Symfony\Component\BrowserKit\Cookie('REMEMBERME', $merk->getValue(), null, '/', '127.0.0.1'));
}
foreach (['/de/restaurants', '/de/', '/de/profile'] as $p) {
    $r2->get($p);
    aus("EC-06 nur mit REMEMBERME GET $p: {$r2->status()} {$r2->ziel()} · shown=".(konto($rosi)['onboarding_shown_at'] ?? 'NULL'));
}

// --- EC-07 -----------------------------------------------------------------------------------
$max = "max4-$s@example.org";
registriere('Max Vierte', $max);
(new Geraet())->get(bestaetigungslink($max));
$x = new Geraet();
$x->get('/de/passwort-vergessen');
$x->absenden('form', [$x->c->filter('form input[type="email"]')->attr('name') => $max]);
aus("EC-07 Passwort vergessen: {$x->status()} → {$x->ziel()}");
$tok = zeile('SELECT password_reset_token t FROM `user` WHERE email = ?', [$max])['t'] ?? '';
$x->get('/de/passwort-zuruecksetzen/'.$tok);
$felder = [];
foreach ($x->c->filter('form input[type="password"]') as $i) {
    $felder[$i->getAttribute('name')] = 'Neues-Passwort-2026!';
}
$x->absenden('form', $felder);
aus("EC-07 neues Passwort gesetzt: {$x->status()} → {$x->ziel()}");
$y = new Geraet();
$y->anmelden($max, 'Neues-Passwort-2026!');
aus("EC-07 erste Anmeldung mit neuem Passwort: {$y->status()} → {$y->ziel()}");
