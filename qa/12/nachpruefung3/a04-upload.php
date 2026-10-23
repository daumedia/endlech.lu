<?php

declare(strict_types=1);

// AK-25 bis AK-29, BF-159 (beide Hälften), BF-163, EC-02 — gegen :8098 (Upload-Grenzen des Produktionsbildes).
require __DIR__.'/treiber.php';

const BASIS = 'http://127.0.0.1:8098';
const BILDER = '/tmp/endlech-f12/bilder';
const AVATARE = __DIR__.'/../../../public/uploads/avatars';

$s = date('His');
$paula = "paula4-$s@example.org";
registriere('Paula Vierte', $paula);
(new Geraet(BASIS))->get(bestaetigungslink($paula));
$p = new Geraet(BASIS);
$p->anmelden($paula, 'Sicher-Passwort-2026!');
aus("Paula Anmeldung: {$p->status()} → {$p->ziel()}");

function dateien(): int
{
    return count(glob(AVATARE.'/*') ?: []);
}

function feld(Geraet $g, string $id): string
{
    $input = $g->c->filter('#'.$id);
    $fehler = $g->c->filter('#'.$id.'_error');

    return 'aria-invalid='.($input->count() ? ($input->attr('aria-invalid') ?? '–') : 'kein Feld')
        .' · autofocus='.($input->count() && null !== $input->attr('autofocus') ? 'ja' : 'nein')
        .' · Meldung „'.($fehler->count() ? trim($fehler->text()) : '').'“';
}

function einfuehrungsUpload(Geraet $g, string $datei): void
{
    $g->get('/de/einfuehrung/profilbild');
    $vorher = konto($GLOBALS['paula'])['avatar_filename'];
    $g->absenden('form[action$="/einfuehrung/profilbild"]', [], ['onboarding_avatar[avatar]' => BILDER.'/'.$datei]);
    $status = $g->status();
    $ziel = $g->ziel();
    $nachher = konto($GLOBALS['paula'])['avatar_filename'];
    $zeile = sprintf('%-14s %8d B → %d %s · Bild %s', $datei, filesize(BILDER.'/'.$datei), $status, $ziel, $vorher === $nachher ? 'unverändert' : 'NEU '.$nachher);
    if (422 === $status) {
        $zeile .= ' · '.feld($g, 'onboarding_avatar_avatar').' · Weiter vorhanden: '.($g->c->filter('nav[aria-label] a')->reduce(static fn ($a) => 'Weiter' === trim($a->text()))->count() ? 'ja' : 'NEIN');
    }
    if (302 === $status) {
        $g->folgen();
        $kopf = $g->c->filter('header img[src*="/uploads/avatars/"]');
        $bild = $g->c->filter('main img[src*="/uploads/avatars/"]');
        $zeile .= ' · Kopfzeile '.($kopf->count() ? basename((string) $kopf->attr('src')) : 'KEIN BILD').' · Bildschirm '.($bild->count() ? basename((string) $bild->attr('src')) : 'KEIN BILD');
        $meldung = $g->c->filter('main [role=alert], main .bg-green-50');
        $zeile .= ' · Meldung „'.($meldung->count() ? trim($meldung->first()->text()) : '').'“';
    }
    aus($zeile);
}

$vorherDateien = dateien();
aus("Dateien im Avatar-Verzeichnis vorher: $vorherDateien");
aus('--- AK-25 / AK-29 (nacheinander ersetzen) ---');
foreach (['klein.jpg', 'klein.png', 'klein.webp', 'mittel.jpg', 'genau2m.png'] as $d) {
    einfuehrungsUpload($p, $d);
}
$name = konto($paula)['avatar_filename'];
aus('Dateien nachher: '.dateien().' (vorher '.$vorherDateien.', erwartet +1) · Datei des Kontos vorhanden: '.(is_file(AVATARE.'/'.$name) ? 'ja' : 'NEIN'));
$p->get('/de/profile');
$profil = $p->c->filter('main img[src*="/uploads/avatars/"]');
aus('AK-25 Profil zeigt: '.($profil->count() ? basename((string) $profil->attr('src')) : 'KEIN BILD').' (Konto: '.$name.')');

aus('--- AK-26 / AK-27 / BF-159 / BF-163 (Einführung) ---');
foreach (['ueber2m.png', 'zwei4.jpg', 'gross21.png', 'text-bild.jpg', 'shell.jpg', 'bild.gif'] as $d) {
    einfuehrungsUpload($p, $d);
}

aus('--- Profil zum Vergleich (B04: „dieselbe Meldung wie im Profil") ---');
foreach (['ueber2m.png', 'zwei4.jpg', 'text-bild.jpg', 'gross21.png'] as $d) {
    $p->get('/de/profile');
    $vorher = konto($paula)['avatar_filename'];
    $p->absenden('form[action$="/profile/edit"]', [], ['profile[avatar]' => BILDER.'/'.$d]);
    $z = sprintf('Profil %-14s → %d %s', $d, $p->status(), $p->ziel());
    if (422 === $p->status()) {
        $z .= ' · '.feld($p, 'profile_avatar');
    } elseif (302 === $p->status()) {
        $p->folgen();
        $flash = $p->c->filter('[role=alert]');
        $z .= ' · Flash „'.($flash->count() ? trim($flash->first()->text()) : '').'“';
    }
    aus($z.' · Bild '.($vorher === konto($paula)['avatar_filename'] ? 'unverändert' : 'GEÄNDERT'));
}
aus('Dateien am Ende: '.dateien());
