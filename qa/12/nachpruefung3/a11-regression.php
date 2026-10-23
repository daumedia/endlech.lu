<?php
// Hauptwege der berührten Features (curl-Ebene, :8099). Browserwege für B01/B02/B04 stehen in bf156-/bf164-browser.
require __DIR__.'/treiber.php';
$g = new Geraet();
foreach ([['GET', '/sitemap.xml'], ['GET', '/de/'], ['GET', '/de/restaurants'], ['GET', '/de/restaurants/45'], ['GET', '/de/community/suggest'], ['GET', '/de/app'], ['GET', '/open.json'], ['GET', '/api/v1/restaurants'], ['GET', '/health']] as [$m, $p]) {
    $g->b->request($m, $g->basis.$p);
    $r = $g->b->getInternalResponse();
    $zusatz = '';
    if ('/sitemap.xml' === $p) {
        $zusatz = ' · <loc> '.substr_count($r->getContent(), '<loc>').' · „einfuehrung" '.substr_count($r->getContent(), 'einfuehrung');
    }
    aus(sprintf('Gast %s %-24s %d%s', $m, $p, $r->getStatusCode(), $zusatz));
}
$u = new Geraet();
$u->anmelden('user@endlech.lu', 'user123');
aus("B02 user@ Anmeldung: {$u->status()} → {$u->ziel()}");
foreach (['/de/community/suggest', '/de/profile', '/de/einfuehrung', '/de/admin'] as $p) {
    $u->get($p);
    aus(sprintf('user@ GET %-24s %d %s', $p, $u->status(), $u->ziel()));
}
$u->get('/de/einfuehrung');
aus('X-Robots-Tag auf /de/einfuehrung: '.($u->b->getInternalResponse()->getHeader('X-Robots-Tag') ?? '–'));
$a = new Geraet();
$a->anmelden('admin@endlech.lu', 'admin123');
foreach (['/de/admin', '/de/admin/warteliste', '/de/admin/restaurants'] as $p) {
    $a->get($p);
    aus(sprintf('admin@ GET %-24s %d', $p, $a->status()));
}
$p = new Geraet();
$p->b->request('GET', $p->basis.'/passkey/login/options');
aus('B03 GET /passkey/login/options: '.$p->status());
$p->b->request('POST', $p->basis.'/passkey/login/options', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
aus('B03 POST /passkey/login/options: '.$p->status());
