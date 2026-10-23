<?php
// AK-59, AK-60, BF-171 (abgewiesenes Absenden zählt nicht) — gegen :8097, jede Anfrage mit anderer Absenderadresse.
require __DIR__.'/treiber.php';
$s = date('His');
$kurt = "kurt4-$s@example.org";
registriere('Kurt Vierte', $kurt);
(new Geraet())->get(bestaetigungslink($kurt));
$n = 0;
$ip = static function () use (&$n): array { ++$n; return ['HTTP_X_FORWARDED_FOR' => '10.55.'.intdiv($n, 250).'.'.($n % 250 + 1)]; };
$k = new Geraet('http://127.0.0.1:8097');
$k->anmelden($kurt, 'Sicher-Passwort-2026!');
$schritt = static function (string $aktion) use ($k, $ip): string {
    $k->get('/de/profile', $ip());
    $token = $k->c->filter('form[action$="/profile/newsletter"] input[name="_token"]')->attr('value');
    $k->post('/de/profile/newsletter', ['_token' => $token, 'action' => $aktion], [], $ip());
    $k->folgen();
    $f = $k->c->filter('[role=alert]');
    return ($f->count() ? trim($f->first()->text()) : '–').' · Stand '.(konto($GLOBALS['kurt'])['marketing_consent_at'] ? 'abonniert' : 'nicht abonniert').' · Absender '.$k->b->getServerParameter('HTTP_X_FORWARDED_FOR', '');
};
// AK-60: dreimal „abbestellen" ohne Abo — darf nichts verbrauchen
for ($i = 1; $i <= 3; ++$i) {
    aus("AK-60 abbestellen ohne Abo $i: ".$schritt('unsubscribe'));
}
for ($i = 1; $i <= 12; ++$i) {
    $aktion = $i % 2 ? 'subscribe' : 'unsubscribe';
    aus("AK-59 Wechsel $i ($aktion): ".$schritt($aktion));
}
// Kontaktzeile: wie viele Aufträge entstanden?
aus('Kontakt nach dem Deckel: '.zeige(kontakt($kurt)));
// Einführungsschritt danach (Kurt ist nach Wechsel 10 = abbestellt)
$k->get('/de/einfuehrung/newsletter', $ip());
$k->absenden('form#einfuehrung-newsletter', ['onboarding_newsletter[consent]' => true], [], $ip());
aus("AK-59 Einführungsschritt danach: {$k->status()} → {$k->ziel()}");
$k->folgen();
$f = $k->c->filter('[role=alert]');
aus('  Seite „'.$k->text('h1').'“ · Meldung „'.($f->count() ? trim($f->first()->text()) : '').'“ · Stand '.(konto($kurt)['marketing_consent_at'] ? 'abonniert' : 'nicht abonniert').' · Messauslöser „abgeschlossen" '.$k->c->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]')->count());
