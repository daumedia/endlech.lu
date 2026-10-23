<?php

declare(strict_types=1);

// AK-31 bis AK-39 (Anwendungsseite), BF-155, BF-171 (Server), BF-174, EC-08 — gegen :8099; Webhook gegen :8097.
require __DIR__.'/treiber.php';

$s = date('His');
$pw = 'Sicher-Passwort-2026!';

function profilStand(Geraet $g): string
{
    $g->get('/de/profile');
    $sek = $g->c->filter('section[aria-labelledby="newsletter-titel"]');

    return $sek->count() ? trim((string) preg_replace('/\s+/u', ' ', $sek->text())) : 'KEIN ABSCHNITT';
}

function schalter(Geraet $g, string $loc = 'de'): string
{
    $g->get("/$loc/profile");
    $g->absenden('form[action$="/profile/newsletter"]');
    $ziel = $g->ziel();
    $g->folgen();
    $f = $g->c->filter('[role=alert]');

    return "{$ziel} · Flash „".($f->count() ? trim($f->first()->text()) : '').'“';
}

// --- AK-34 / AK-35 / AK-36 (Anwendungsseite) -----------------------------------------------------
$gustav = "gustav4-$s@example.org";
registriere('Gustav Vierte', $gustav);
(new Geraet())->get(bestaetigungslink($gustav));
$g = new Geraet();
$g->anmelden($gustav, $pw);
aus('AK-34 Profil vorher: '.profilStand($g));
aus('AK-35 abonnieren (fr): '.schalter($g, 'fr'));
aus('  Konto '.konto($gustav)['marketing_consent_at'].' · Kontakt '.zeige(kontakt($gustav)));
aus('AK-34 Profil nachher: '.profilStand($g));
aus('AK-36 abbestellen, Kontakt nie übertragen: '.schalter($g));
aus('  Konto '.(konto($gustav)['marketing_consent_at'] ?? 'NULL').' · Kontakt '.zeige(kontakt($gustav)).' · Profil: '.profilStand($g));
// Übertragen nachstellen (Brevo nicht erreichbar): Zeile wie nach einem gelungenen Lauf
aus('AK-35 wieder abonnieren: '.schalter($g));
sql("UPDATE marketing_contact SET sync_state = 'synced', synced_at = DATE_ADD(consent_at, INTERVAL 1 SECOND) WHERE email = ?", [$gustav]);
sleep(1);
aus('AK-36 abbestellen, Kontakt übertragen: '.schalter($g));
aus('  Konto '.(konto($gustav)['marketing_consent_at'] ?? 'NULL').' · Kontakt '.zeige(kontakt($gustav)));

// --- AK-37: Abmeldelink in Brevo → Webhook ------------------------------------------------------
$clara = "clara4-$s@example.org";
registriere('Clara Vierte', $clara);
(new Geraet())->get(bestaetigungslink($clara, 'fr'));
$c = new Geraet();
$c->anmelden($clara, $pw);
schalter($c);
aus('AK-37 Clara vorher: '.profilStand($c));
$hook = new Geraet('http://127.0.0.1:8097');
$hook->b->request('POST', $hook->basis.'/marketing/brevo/webhook', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer falsch'], json_encode(['event' => 'unsubscribed', 'email' => $clara]));
aus("AK-37 Webhook mit falschem Geheimnis: {$hook->status()} · Konto ".(konto($clara)['marketing_consent_at'] ?? 'NULL'));
$hook->b->request('POST', $hook->basis.'/marketing/brevo/webhook', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer qa12-dritte-pruefung-webhook'], json_encode(['event' => 'unsubscribed', 'email' => $clara]));
aus("AK-37 Webhook unsubscribed: {$hook->status()} · Konto ".(konto($clara)['marketing_consent_at'] ?? 'NULL').' · Kontakt '.zeige(kontakt($clara)));
aus('AK-37 Clara nachher: '.profilStand($c));

// --- AK-38 / BF-174 -----------------------------------------------------------------------------
$ulla = "ulla4-$s@example.org";
registriere('Ulla Vierte', $ulla);
$u = new Geraet();
$u->anmelden($ulla, $pw);
aus('AK-38/BF-174 Ulla unbestätigt, Profil: '.profilStand($u));
aus('AK-38 abonnieren: '.schalter($u));
aus('  Konto '.(konto($ulla)['marketing_consent_at'] ?? 'NULL').' · Kontakt '.zeige(kontakt($ulla)).' · Profil: '.profilStand($u));
$link = bestaetigungslink($ulla, 'pt');
(new Geraet())->get($link);
aus("AK-38 Bestätigung über $link → Kontakt ".zeige(kontakt($ulla)));
aus('BF-174 Ulla bestätigt, Profil: '.profilStand($u));

// --- BF-155: unbestätigtes Konto mit der Adresse eines Wartelisten-Kontakts ----------------------
$opfer = "opfer4-$s@example.org";
$w = new Geraet('http://127.0.0.1:8097', ['HTTP_X_FORWARDED_FOR' => '10.44.0.'.random_int(1, 250)]);
$w->get('/de/app');
$w->absenden('form[name="app_waitlist"]', ['app_waitlist[email]' => $opfer, 'app_waitlist[platform]' => 'ios', 'app_waitlist[consent]' => true, 'app_waitlist[marketingConsent]' => true]);
aus("BF-155 App-Warteliste eingetragen: {$w->status()} → {$w->ziel()}");
$tok = zeile('SELECT confirmation_token t FROM app_waitlist_entry WHERE email = ?', [$opfer])['t'] ?? '';
(new Geraet())->get('/de/app/confirmation/'.$tok);
sql("UPDATE marketing_contact SET sync_state = 'synced', synced_at = NOW() WHERE email = ?", [$opfer]);
$vorher = zeile('SELECT marketing_consent_at FROM app_waitlist_entry WHERE email = ?', [$opfer]);
aus('  Warteliste '.zeige($vorher).' · Kontakt '.zeige(kontakt($opfer)));
registriere('Fremd Vierte', $opfer);
$f = new Geraet();
$f->anmelden($opfer, $pw);
aus('BF-155/BF-174 Fremdkonto (unbestätigt) Profil: '.profilStand($f));
aus('  abonnieren: '.schalter($f));
aus('  abbestellen: '.schalter($f));
aus('  Warteliste nachher '.zeige(zeile('SELECT marketing_consent_at FROM app_waitlist_entry WHERE email = ?', [$opfer])).' · Kontakt '.zeige(kontakt($opfer)));

// --- AK-39 ---------------------------------------------------------------------------------------
$f->get('/de/profile');
$f->absenden('form[action$="/profile/edit"]', ['profile[name]' => 'Fremd Umbenannt']);
aus("AK-39 Name ändern ohne Einwilligung: {$f->status()} → {$f->ziel()} · Name ".konto($opfer)['name']);

// --- AK-33 Einführung, BF-171 Server-Seite --------------------------------------------------------
$theo = "theo4-$s@example.org";
registriere('Theo Vierte', $theo);
(new Geraet())->get(bestaetigungslink($theo));
$t1 = new Geraet();
$t1->anmelden($theo, $pw);
$t1->get('/fr/einfuehrung/newsletter');           // Tab 1 hat den Schritt offen
$t2 = new Geraet();
$t2->anmelden($theo, $pw);
aus('EC-08 Tab 2 abonniert im Profil: '.schalter($t2));
$vorher = konto($theo)['marketing_consent_at'];
$t1->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true]);
aus("EC-08 Tab 1 setzt das Häkchen: {$t1->status()} → {$t1->ziel()} · Einwilligung vorher $vorher nachher ".konto($theo)['marketing_consent_at'].' · Kontaktzeilen '.zeile('SELECT COUNT(*) n FROM marketing_contact WHERE email = ?', [$theo])['n']);
$t1->folgen();
aus('BF-171 Restaurantliste danach: Messauslöser '.$t1->c->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]')->count().' · sichtbarer Text „einfuehrung_" '.substr_count($t1->text('body'), 'einfuehrung_'));

$hans = "hans4-$s@example.org";
registriere('Hans Vierte', $hans);
(new Geraet())->get(bestaetigungslink($hans, 'fr'));
$h = new Geraet();
$h->anmelden($hans, $pw);
$h->get('/fr/einfuehrung/newsletter');
$h->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true]);
aus("AK-33 Hans (fr) setzt das Häkchen: {$h->status()} → {$h->ziel()} · Einwilligung ".konto($hans)['marketing_consent_at'].' · Kontakt '.zeige(kontakt($hans)));
$h->folgen();
$aus = $h->c->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]');
aus('BF-171 Restaurantliste nach gelungenem Absenden: Messauslöser '.$aus->count().($aus->count() ? ' ('.$aus->outerHtml().')' : '').' · Erfolgsmeldung „'.trim($h->c->filter('[role=alert]')->count() ? $h->c->filter('[role=alert]')->first()->text() : '').'“');
$h->get('/fr/restaurants');
aus('BF-171 Restaurantliste neu geladen: Messauslöser '.$h->c->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]')->count());

// fremde Herkunft → kein Messauslöser
$h2 = new Geraet();
$h2->anmelden($gustav, $pw);
$h2->get('/de/einfuehrung/newsletter');
$h2->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true], [], ['HTTP_ORIGIN' => 'https://boese.example', 'HTTP_REFERER' => 'https://boese.example/x']);
aus("BF-171/AK-58 fremde Herkunft: {$h2->status()} · Meldung „".($h2->c->filter('[role=alert]')->count() ? trim($h2->c->filter('[role=alert]')->first()->text()) : '').'“ · Messauslöser '.$h2->c->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]')->count().' · Einwilligung '.(konto($gustav)['marketing_consent_at'] ?? 'NULL'));
