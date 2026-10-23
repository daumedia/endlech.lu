<?php
// BF-185 und BF-178 (a) mit ZWEI echten Prozessen: Der Abgleich läuft hier (CLI, echter MarketingSyncService, echte
// Datenbank), Brevo ist ein Nachbau im Prozess. Während der Nachbau „antwortet", schickt ein zweiter Prozess — der
// laufende Webserver :8099 — das Abbestellen bzw. Wieder-Abonnieren über das echte Profilformular.
// Aufruf: DATABASE_URL=…3309/endlech php a17-wettlauf-abgleich.php
require __DIR__.'/treiber.php';

use App\Entity\MarketingContact;
use App\Marketing\BrevoContactClient;
use App\Marketing\MarketingPayloadMapper;
use App\Marketing\MarketingSyncService;
use Psr\Log\NullLogger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

(new Dotenv())->bootEnv(__DIR__.'/../../../.env');
$kernel = new App\Kernel('dev', false);
$kernel->boot();
$doctrine = $kernel->getContainer()->get('doctrine');

$brevo = [];   // ext_id => ['email', 'blacklisted']
$aufrufe = [];

function lauf(array &$brevo, array &$aufrufe, $doctrine, ?callable $bei = null): void
{
    $em = $doctrine->getManager();
    $em->clear();
    $handler = function (string $method, string $url, array $options) use (&$brevo, &$aufrufe, $bei): MockResponse {
        $rumpf = json_decode((string) ($options['body'] ?? '{}'), true) ?: [];
        preg_match('#/contacts/(\d+)#', $url, $t);
        $id = $t[1] ?? (string) ($rumpf['ext_id'] ?? '');
        $aufrufe[] = [$method, $id, $rumpf];
        if (null !== $bei && null !== ($antwort = $bei($method, $id, $rumpf))) {
            return $antwort;
        }
        if ('POST' === $method) {
            $brevo[$id] = ['email' => $rumpf['email'] ?? null, 'blacklisted' => $rumpf['emailBlacklisted'] ?? false];
            return new MockResponse('{"id":1}', ['http_code' => 201]);
        }
        if (!isset($brevo[$id])) {
            return new MockResponse('', ['http_code' => 404]);
        }
        if (array_key_exists('emailBlacklisted', $rumpf)) {
            $brevo[$id]['blacklisted'] = (bool) $rumpf['emailBlacklisted'];
        }
        if (isset($rumpf['email'])) {
            $brevo[$id]['email'] = $rumpf['email'];
        }
        return new MockResponse('', ['http_code' => 204]);
    };
    (new MarketingSyncService($em->getRepository(MarketingContact::class), new MarketingPayloadMapper('7'),
        new BrevoContactClient(new MockHttpClient($handler), new NullLogger(), 'qa12-attrappe', '7'), $em, new NullLogger(), 500, 0))->run();
}

function schalter(Geraet $g): string
{
    $g->get('/de/profile');
    $g->absenden('form[action$="/profile/newsletter"]');
    $g->folgen();
    $f = $g->c->filter('[role=alert]');
    return $f->count() ? trim($f->first()->text()) : '–';
}

$s = date('His');
$pw = 'Sicher-Passwort-2026!';

// ---------------- BF-185 ----------------------------------------------------------------------------------------
$alt = "wett4-$s@example.org";
$neu = "wett4-$s-neu@example.org";
registriere('Wett Vierte', $alt);
(new Geraet())->get(bestaetigungslink($alt));
$g = new Geraet();
$g->anmelden($alt, $pw);
aus('BF-185 abonnieren: '.schalter($g));
lauf($brevo, $aufrufe, $doctrine);
$id = (string) kontakt($alt)['id'];
aus("BF-185 nach Lauf 1: Brevo[$id] ".json_encode($brevo[$id] ?? null).' · Zeile '.zeige(kontakt($alt)));
// Bestätigter Adresswechsel (B04): die Zeile braucht eine Neuübertragung.
$g->get('/de/profile');
$g->absenden('form[action$="/profile/edit"]', ['profile[email]' => $neu]);
$tok = zeile('SELECT pending_email_token t FROM `user` WHERE email = ?', [$alt])['t'];
(new Geraet())->get('/de/verify/email-change/'.$tok);
$g2 = new Geraet();
$g2->anmelden($neu, $pw);
$g2->get('/de/profile');
aus('BF-185 nach bestätigtem Adresswechsel: Zeile '.zeige(kontakt($neu)));
sleep(1);
// Lauf 2: Brevo antwortet auf die Neuübertragung mit 504 — und WÄHREND der Antwort bestellt das Konto im Profil ab.
lauf($brevo, $aufrufe, $doctrine, function (string $method, string $kid) use ($id, $g2): ?MockResponse {
    if ($kid !== $id) {
        return null;
    }
    aus('   (während des Aufrufs '.$method.' an Brevo) Profil → abbestellen: '.schalter($g2).' · Zeile jetzt '.zeige(kontakt($GLOBALS['neu'])));
    return new MockResponse('', ['http_code' => 504]);
});
aus('BF-185 nach Lauf 2 (504): Zeile '.zeige(kontakt($neu)).' · Konto-Einwilligung '.(konto($neu)['marketing_consent_at'] ?? 'NULL'));
$vorher = count($aufrufe);
lauf($brevo, $aufrufe, $doctrine);
lauf($brevo, $aufrufe, $doctrine);
$fuerId = array_values(array_filter(array_slice($aufrufe, $vorher), static fn ($a) => $a[1] === $id));
aus('BF-185 Läufe 3 und 4: Aufrufe für diese Zeile '.count($fuerId).' · Brevo['.$id.'] '.json_encode($brevo[$id]).' · Zeile '.zeige(kontakt($neu)));
aus('BF-185 Ergebnis: '.($brevo[$id]['blacklisted'] ? 'gesperrt (behoben)' : 'NICHT gesperrt — Profil „nicht abonniert", Brevo stellt weiter zu'));

// ---------------- BF-178 (a) ------------------------------------------------------------------------------------
$w = "wieder4-$s@example.org";
registriere('Wieder Vierte', $w);
(new Geraet())->get(bestaetigungslink($w));
$h = new Geraet();
$h->anmelden($w, $pw);
aus('BF-178 abonnieren: '.schalter($h));
lauf($brevo, $aufrufe, $doctrine);
$wid = (string) kontakt($w)['id'];
sleep(1);
aus('BF-178 abbestellen: '.schalter($h).' · Zeile '.zeige(kontakt($w)));
sleep(1);
lauf($brevo, $aufrufe, $doctrine, function (string $method, string $kid, array $rumpf) use ($wid, $h): ?MockResponse {
    if ($kid !== $wid || true !== ($rumpf['emailBlacklisted'] ?? null)) {
        return null;
    }
    $GLOBALS['brevo'][$wid]['blacklisted'] = true;
    aus('   (während des Sperr-PUT) Profil → wieder abonnieren: '.schalter($h).' · Zeile jetzt '.zeige(kontakt($GLOBALS['w'])));
    return new MockResponse('', ['http_code' => 204]);
});
aus('BF-178 nach dem Lauf: Zeile '.zeige(kontakt($w)).' · Brevo['.$wid.'] '.json_encode($brevo[$wid]));
lauf($brevo, $aufrufe, $doctrine);
aus('BF-178 nächster Lauf: Brevo['.$wid.'] '.json_encode($brevo[$wid]).' · Zeile '.zeige(kontakt($w)).' → '.($brevo[$wid]['blacklisted'] ? 'GESPERRT trotz Einwilligung' : 'entsperrt (behoben)'));
