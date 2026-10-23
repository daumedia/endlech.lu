<?php
// AK-23: Begriffe der Einführung wortgleich mit der Detailseite — Katalogwerte gegen die tatsächlich ausgelieferten Seiten.
require __DIR__.'/treiber.php';
use Symfony\Component\Yaml\Yaml;
$e = $argv[1] ?? 'olga4';
$konto = zeile("SELECT email FROM `user` WHERE email LIKE ? ORDER BY id DESC", ["$e%"])['email'];
$o = new Geraet();
$o->anmelden($konto, 'Sicher-Passwort-2026!');
$gast = new Geraet();
foreach (['lb', 'de', 'fr', 'en', 'pt'] as $L) {
    $c = Yaml::parseFile(__DIR__."/../../../translations/messages.$L.yaml");
    $g = static fn (string $k) => array_reduce(explode('.', $k), static fn ($a, $p) => $a[$p] ?? null, $c);
    $begriffe = ['verified.badge_text' => $g('verified.badge_text'), 'accessibility.not_available' => $g('accessibility.not_available'),
        'accessibility.not_measured' => $g('accessibility.not_measured'), 'accessibility.below_minimum' => str_replace('%min%', '90', (string) $g('accessibility.below_minimum'))];
    $o->get("/$L/einfuehrung/eintraege");
    $ein = $o->text('main');
    $detail = '';
    foreach ([56, 57, 59, 60, 66] as $id) {   // verifiziert, unter 90, nicht ausgemessen, gemischt
        $gast->get("/$L/restaurants/$id");
        $detail .= ' '.$gast->text('main');
    }
    foreach ($begriffe as $k => $v) {
        printf("%s %-30s %-28s Einführung:%s Detailseite:%s\n", $L, $k, '„'.$v.'“', str_contains($ein, $v) ? 'ja' : 'NEIN', str_contains($detail, $v) ? 'ja' : 'NEIN');
    }
}
