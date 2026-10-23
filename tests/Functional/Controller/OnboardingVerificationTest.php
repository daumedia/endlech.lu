<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Feature 12 — der Bestätigungslink setzt den Merker und lenkt Angemeldete um
 * (AK-05, AK-07, AK-08, AK-40, EC-04, EC-05).
 */
final class OnboardingVerificationTest extends AbstractWebTestCase
{
    private function token(KernelBrowser $client, string $email, string $ablauf = '+24 hours'): string
    {
        $user = $this->user($client, $email);
        $token = $user->generateVerificationToken();
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable($ablauf));
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        return $token;
    }

    private function frisch(KernelBrowser $client, string $email): User
    {
        $client->getContainer()->get(EntityManagerInterface::class)->clear();

        return $this->user($client, $email);
    }

    public function testAk07BestaetigenSetztDenMerker(): void
    {
        $client = static::createClient();
        // Das Fixture-Konto steht für „am Tag der Auslieferung angelegt, aber unbestätigt".
        $token = $this->token($client, 'unverified@endlech.lu');
        self::assertFalse($this->user($client, 'unverified@endlech.lu')->isOnboardingDue());

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/login');
        self::assertTrue($this->frisch($client, 'unverified@endlech.lu')->isOnboardingPending());
    }

    public function testAk05AngemeldetMitDiesemKontoFuehrtDirektInDieEinfuehrung(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'unverified@endlech.lu');
        $this->loginAs($client, 'unverified@endlech.lu');

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/einfuehrung');
    }

    public function testEc04AnderesKontoAngemeldetBleibtBeimAltenWeg(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'unverified@endlech.lu');
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/login');
        self::assertFalse($this->frisch($client, 'user@endlech.lu')->isOnboardingDue(), 'Das angemeldete Konto ist nicht das bestätigte.');
        self::assertTrue($this->frisch($client, 'unverified@endlech.lu')->isOnboardingPending());
    }

    public function testEc05AbgelaufenerLinkSetztNichts(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'unverified@endlech.lu', '-1 hour');

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/verify');
        self::assertFalse($this->frisch($client, 'unverified@endlech.lu')->isOnboardingDue());
    }

    public function testAk08KontoAusDerAppBekommtDieEinfuehrungImWeb(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth/register', server: ['CONTENT_TYPE' => 'application/json'], content: (string) json_encode([
            'name' => 'App Nutzerin',
            'email' => 'f12-app@qa.lu',
            'password' => 'geheim1234',
        ]));
        self::assertResponseStatusCodeSame(201);
        $token = (string) $this->user($client, 'f12-app@qa.lu')->getVerificationToken();

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        $crawler = $client->request('GET', self::LOCALE.'/login');
        $client->submit($this->formWithField($crawler, '_password', ['_username' => 'f12-app@qa.lu', '_password' => 'geheim1234']));
        self::assertResponseRedirects(self::LOCALE.'/einfuehrung');
    }

    public function testAk40SpracheDesLinksWirdSpracheDesKontakts(): void
    {
        $client = static::createClient();
        $user = $this->user($client, 'unverified@endlech.lu');
        $user->setMarketingConsentAt(new \DateTimeImmutable('-1 hour'));
        $token = $this->token($client, 'unverified@endlech.lu');

        $client->request('GET', '/pt/verify/'.$token);

        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail('unverified@endlech.lu');
        self::assertSame('pt', $kontakt?->getLocale());
    }
}
