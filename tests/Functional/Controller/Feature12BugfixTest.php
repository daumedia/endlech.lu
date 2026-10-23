<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feature 12 — Reproduktionen der Befunde BF-161 bis BF-163 (QA vom 2026-10-22, BUG-07 bis BUG-09).
 *
 * BF-154 bis BF-160 reproduziert die QA selbst in `Qa12OnboardingTest`.
 */
final class Feature12BugfixTest extends AbstractWebTestCase
{
    /** @var list<string> */
    private array $aufzuraeumen = [];

    protected function tearDown(): void
    {
        foreach ($this->aufzuraeumen as $pfad) {
            @unlink($pfad);
        }
        parent::tearDown();
    }

    public function testBf161NachDerBestaetigungImAngemeldetenZweigKeinAufrufZumAnmelden(): void
    {
        $client = static::createClient();
        $user = $this->user($client, 'unverified@endlech.lu');
        $token = $user->generateVerificationToken();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        $this->loginAs($client, 'unverified@endlech.lu');

        $client->request('GET', self::LOCALE.'/verify/'.$token);
        self::assertResponseRedirects(self::LOCALE.'/einfuehrung');
        $crawler = $client->followRedirect();

        $aufruf = $client->getContainer()->get(TranslatorInterface::class)->trans('flash.verify_success', [], null, 'de');
        self::assertStringNotContainsString($aufruf, $crawler->text(), 'Wer schon angemeldet ist, soll nicht zum Anmelden aufgefordert werden.');
    }

    public function testBf162CsrfFehlerInDerEinfuehrungZeigtEineMeldungMitFokus(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('POST', self::LOCALE.'/einfuehrung/newsletter', [
            'onboarding_newsletter' => ['consent' => '1', '_token' => 'gefaelscht'],
        ]);

        self::assertResponseStatusCodeSame(422);
        $meldung = $crawler->filter('main [role="alert"]');
        self::assertGreaterThan(0, $meldung->count(), 'Es muss eine Meldung geben.');
        self::assertNotSame('', trim($meldung->text()));
        self::assertNotNull($meldung->attr('autofocus'), 'Der Fokus gehört auf die Meldung, sonst landet er nirgends.');
    }

    public function testBf163GroessenmeldungNenntDieEinheit(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $pfad = sys_get_temp_dir().'/f12-gross-'.bin2hex(random_bytes(4)).'.png';
        $bild = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($bild);
        file_put_contents($pfad, ob_get_clean().str_repeat("\0", (int) (2.5 * 1024 * 1024)));
        $this->aufzuraeumen[] = $pfad;
        $form = $client->request('GET', self::LOCALE.'/einfuehrung/profilbild')->filter('form[action$="/einfuehrung/profilbild"]')->form();
        $form['onboarding_avatar[avatar]']->upload($pfad);

        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertMatchesRegularExpression('/2\s?(MB|M)\b/', $crawler->filter('#onboarding_avatar_avatar_error')->text());
    }
}
