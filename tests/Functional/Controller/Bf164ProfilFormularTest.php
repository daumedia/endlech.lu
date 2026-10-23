<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * BF-164 (B04) — mit Profilbild ließ sich das Profil nicht speichern; BF-159 (Profilhälfte) — ein
 * Bild über `post_max_size` ergab im Profil keine Meldung.
 *
 * BF-164: Das Formular „Profilbild entfernen" stand IM Profilformular. `<form>` im `<form>` ist
 * ungültig; der Browser verwirft das innere Tag, „Änderungen speichern" gehörte zu keinem Formular
 * mehr und „Profilbild entfernen" schickte das falsche ab. Im Testclient nicht nachstellbar (der
 * DOM-Parser verwirft anders als ein Browser) — geprüft wird deshalb die Struktur.
 */
final class Bf164ProfilFormularTest extends AbstractWebTestCase
{
    public function testBf164KeinFormularImFormular(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $user->setAvatarFilename('vorhanden.png');
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $client->request('GET', self::LOCALE.'/profile');
        $html = (string) $client->getResponse()->getContent();

        // Am Quelltext gezählt, nicht am DOM — der Parser hätte die Verschachtelung schon aufgelöst.
        $tiefe = 0;
        $maximum = 0;
        preg_match_all('#<(/?)form\b#i', $html, $treffer);
        foreach ($treffer[1] as $schliessend) {
            $tiefe += '' === $schliessend ? 1 : -1;
            $maximum = max($maximum, $tiefe);
        }
        self::assertSame(1, $maximum, 'Ein Formular darf in keinem anderen stehen.');
    }

    public function testBf164EntfernenKnopfGehoertZumEntfernenFormular(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $user->setAvatarFilename('vorhanden.png');
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $knopf = $crawler->filter('button[form]')->reduce(static fn ($k) => str_contains($k->text(), 'Profilbild entfernen'));

        self::assertCount(1, $knopf);
        $ziel = $crawler->filter('form#'.$knopf->attr('form'));
        self::assertCount(1, $ziel);
        self::assertStringEndsWith('/profile/avatar/delete', (string) $ziel->attr('action'));
    }

    public function testBf159UebergrosserUploadImProfilZeigtEineMeldung(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('POST', self::LOCALE.'/profile/edit', server: [
            'CONTENT_TYPE' => 'multipart/form-data; boundary=bf159',
            'CONTENT_LENGTH' => (string) (64 * 1024 * 1024),
        ]);

        self::assertResponseRedirects(self::LOCALE.'/profile');
        $crawler = $client->followRedirect();
        self::assertStringContainsString('2 MB', $crawler->filter('body')->text(), 'Der Nutzer muss erfahren, warum nichts geschah.');
    }
}
