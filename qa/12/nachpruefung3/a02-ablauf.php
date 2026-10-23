<?php

declare(strict_types=1);

// AK-12 bis AK-17, AK-20 bis AK-24, AK-28, AK-31, AK-54, AK-64 — gegen :8099.
require __DIR__.'/treiber.php';

$s = date('His');
$pw = 'Sicher-Passwort-2026!';

$ohne = "olga4-$s@example.org";     // ohne Einwilligung → 4 Bildschirme
$mit = "dora4-$s@example.org";      // Einwilligung bei der Registrierung → 3 Bildschirme
registriere('Olga Vierte', $ohne);
registriere('Dora Vierte', $mit, true);
(new Geraet())->get(bestaetigungslink($ohne));
(new Geraet())->get(bestaetigungslink($mit));
aus('Dora nach Bestätigung: '.zeige(array_intersect_key(konto($mit), array_flip(['marketing_consent_at', 'onboarding_due']))).' · Kontakt '.zeige(kontakt($mit)));

$o = new Geraet();
$o->anmelden($ohne, $pw);
aus("Olga Anmeldung: {$o->status()} → {$o->ziel()}");

// AK-12 / AK-14 / AK-16 / AK-17 / AK-15: Fußleiste je Bildschirm lesen
$vorherKonto = konto($ohne);
$vorherKontakte = zeile('SELECT COUNT(*) n FROM marketing_contact')['n'];
$pfad = '/de/einfuehrung';
for ($i = 1; $i <= 5 && null !== $pfad; ++$i) {
    $o->get($pfad);
    $nav = $o->c->filter('nav[aria-label]');
    $links = [];
    foreach ($nav->filter('a, button') as $el) {
        $links[] = trim(preg_replace('/\s+/', ' ', $el->textContent)).' ['.($el->getAttribute('href') ?: 'form='.$el->getAttribute('form')).']';
    }
    aus("Bildschirm $i ($pfad): {$o->status()} · h1 „".$o->text('h1').'" · title „'.$o->text('title').'"');
    aus('   Fußleiste: '.implode(' | ', $links));
    $weiter = $nav->filter('a')->reduce(static fn ($a) => 'Weiter' === trim($a->text()));
    $pfad = $weiter->count() ? preg_replace('#^https?://[^/]+#', '', $weiter->attr('href')) : null;
}
$nachher = konto($ohne);
aus('AK-15 Konto vorher/nachher gleich (ohne shown): '.(array_diff_key($vorherKonto, ['onboarding_shown_at' => 1]) === array_diff_key($nachher, ['onboarding_shown_at' => 1]) ? 'ja' : 'NEIN').' · Kontaktzeilen '.$vorherKontakte.' → '.zeile('SELECT COUNT(*) n FROM marketing_contact')['n']);

// AK-15: Upload-Formular ohne Datei, Newsletter-Formular ohne Häkchen
$o->get('/de/einfuehrung/profilbild');
$o->absenden('form[action$="/einfuehrung/profilbild"]');
aus("AK-15 Upload ohne Datei: {$o->status()} → {$o->ziel()} · avatar=".(konto($ohne)['avatar_filename'] ?? 'NULL'));
$o->get('/de/einfuehrung/newsletter');
$haken = $o->c->filter('input[type=checkbox]');
aus('AK-30 Häkchen: '.$haken->count().' Stück, checked='.($haken->attr('checked') ?? 'nicht gesetzt').' · '.$o->c->filter('form#einfuehrung-newsletter')->outerHtml() ? 'Formular gefunden' : '');
$o->absenden('form#einfuehrung-newsletter');
aus("AK-15/AK-32 Newsletter ohne Häkchen: {$o->status()} → {$o->ziel()} · Einwilligung=".(konto($ohne)['marketing_consent_at'] ?? 'NULL').' · Kontakt='.zeige(kontakt($ohne)));

// AK-13 / AK-14: Dora (Einwilligung)
$d = new Geraet();
$d->anmelden($mit, $pw);
foreach (['/de/einfuehrung', '/de/einfuehrung/eintraege', '/de/einfuehrung/profilbild'] as $p) {
    $d->get($p);
    $letzter = $d->c->filter('nav[aria-label] a')->each(static fn ($a) => trim($a->text()));
    aus("AK-13 Dora $p: „".$d->text('h1').'" · Fußleiste: '.implode(' | ', $letzter));
}
$d->get('/de/einfuehrung/newsletter');
aus("AK-13 Dora /de/einfuehrung/newsletter: {$d->status()} → {$d->ziel()}");

// AK-20, AK-22, AK-24, AK-28, AK-31, AK-54, AK-64: Texte in fünf Sprachen
$verboten = ['bewert', 'kommentar', 'favorit', 'rating', 'review', 'avis', 'évalu', 'evalu', 'avalia', 'coment', 'favori', 'notez', 'stern', 'étoile', 'estrela', 'bewäert', 'note ', 'rate ', 'comment'];
$texte = [];
foreach (['lb', 'de', 'fr', 'en', 'pt'] as $loc) {
    foreach (['willkommen', 'eintraege', 'profilbild', 'newsletter'] as $schritt) {
        $o->get("/$loc/einfuehrung/$schritt");
        $text = $o->text('main');
        $texte[$loc][$schritt] = $text;
        $felder = $o->c->filter('main input:not([type=hidden]), main select, main textarea')->each(static fn ($i) => $i->attr('type').':'.$i->attr('name'));
        aus("\n[$loc/$schritt] {$o->status()} · Eingabefelder: ".(implode(', ', $felder) ?: 'keine'));
        aus('  '.$text);
        $treffer = [];
        foreach ($verboten as $w) {
            if (false !== mb_stripos($text, $w)) {
                $treffer[] = $w;
            }
        }
        aus('  AK-21 Wortstämme: '.($treffer ? implode(',', $treffer) : 'keine'));
    }
}

// AK-64: steht ein deutscher Satz (≥ 4 Wörter) aus der de-Fassung wörtlich in einer anderen Sprache?
foreach (['lb', 'fr', 'en', 'pt'] as $loc) {
    $leck = [];
    foreach ($texte['de'] as $schritt => $de) {
        foreach (preg_split('/(?<=[.!?:])\s+/u', $de) as $satz) {
            if (str_word_count(preg_replace('/[^\p{L} ]/u', '', $satz)) >= 4 && str_contains($texte[$loc][$schritt], $satz)) {
                $leck[] = "$schritt: $satz";
            }
        }
    }
    aus("AK-64 $loc: deutsche Sätze wörtlich übernommen: ".($leck ? implode(' / ', $leck) : 'keine'));
}
aus('AK-64 pt „você": '.(str_contains(mb_strtolower(implode(' ', $texte['pt'])), 'você') ? 'JA' : 'nein'));
