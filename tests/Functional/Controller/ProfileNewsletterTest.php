<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\MarketingContact;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feature 12 — der Newsletter-Schalter im Profil (AK-34 bis AK-38, AK-52, AK-57 bis AK-60).
 */
final class ProfileNewsletterTest extends AbstractWebTestCase
{
    private const KONTO = 'user@endlech.lu';

    protected function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::KONTO)->reset();
        self::ensureKernelShutdown();
    }

    private function angemeldet(string $email = self::KONTO, bool $abonniert = false): KernelBrowser
    {
        $client = static::createClient();
        $user = $this->loginAs($client, $email);
        $user->setMarketingConsentAt($abonniert ? new \DateTimeImmutable('2026-10-01 12:00:00') : null);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        return $client;
    }

    private function trans(KernelBrowser $client, string $key, array $parameter = []): string
    {
        return $client->getContainer()->get(TranslatorInterface::class)->trans($key, $parameter, null, 'de');
    }

    private function umschalten(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($crawler->filter('form[action$="/profile/newsletter"]')->form());
    }

    private function frisch(KernelBrowser $client, string $email = self::KONTO): ?\DateTimeImmutable
    {
        $client->getContainer()->get(EntityManagerInterface::class)->clear();

        return $this->user($client, $email)->getMarketingConsentAt();
    }

    public function testAk34ProfilZeigtDenStand(): void
    {
        $client = $this->angemeldet(abonniert: true);

        $crawler = $client->request('GET', self::LOCALE.'/profile');

        self::assertStringContainsString($this->trans($client, 'newsletter.subscribed_since', ['%date%' => '01.10.2026']), $crawler->text());
        self::assertCount(1, $crawler->filter('form[action$="/profile/newsletter"] button:contains("'.$this->trans($client, 'newsletter.unsubscribe').'")'));
    }

    public function testAk35AbonnierenUeberDasProfil(): void
    {
        $client = $this->angemeldet();

        $this->umschalten($client);

        self::assertResponseRedirects(self::LOCALE.'/profile');
        self::assertNotNull($this->frisch($client));
        self::assertSame(MarketingSyncState::PENDING, $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::KONTO)?->getSyncState());
        $crawler = $client->followRedirect();
        self::assertStringContainsString($this->trans($client, 'newsletter.flash_subscribed'), $crawler->text());
    }

    public function testAk36AbbestellenSperrtUndStelltDenAuftrag(): void
    {
        $client = $this->angemeldet(abonniert: true);
        $user = $this->user($client, self::KONTO);
        $kontakt = $client->getContainer()->get(MarketingContactRegistry::class)->recordUser($user, 'de');
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        $kontakt?->markSynced();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $this->umschalten($client);

        self::assertNull($this->frisch($client));
        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::KONTO);
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt?->getSyncState());
        self::assertStringContainsString($this->trans($client, 'newsletter.not_subscribed'), $client->followRedirect()->text());
    }

    public function testAk37AbmeldungUeberBrevoZeigtSichImProfil(): void
    {
        $client = $this->angemeldet(abonniert: true);
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $kontakt = new MarketingContact();
        $kontakt->setEmail(self::KONTO)->setOrigin(MarketingOrigin::ACCOUNT)->setLocale('de');
        $em->persist($kontakt);
        $em->flush();

        // Was der Brevo-Webhook bei einer Abmeldung ruft (04/AK-11).
        $client->getContainer()->get(MarketingContactRegistry::class)->blockByEmail(self::KONTO);
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        self::assertStringContainsString($this->trans($client, 'newsletter.not_subscribed'), $crawler->text());
    }

    public function testAk38UnbestaetigtesKontoBekommtDenHinweis(): void
    {
        $client = $this->angemeldet('unverified@endlech.lu');

        $this->umschalten($client);
        $crawler = $client->followRedirect();

        self::assertNotNull($this->frisch($client, 'unverified@endlech.lu'));
        self::assertNull($client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail('unverified@endlech.lu'));
        self::assertStringContainsString($this->trans($client, 'newsletter.pending_verification'), $crawler->text());
    }

    public function testAk52SchalterLoestKeinMessereignisAus(): void
    {
        $client = $this->angemeldet();

        $crawler = $client->request('GET', self::LOCALE.'/profile');

        self::assertCount(0, $crawler->filter('form[action$="/profile/newsletter"] [data-controller~="usage-event"], form[action$="/profile/newsletter"][data-controller~="usage-event"]'));
    }

    public function testAk57FremdeKennungImRumpfAendertNurDasEigeneKonto(): void
    {
        $client = $this->angemeldet();
        $fremdVorher = $this->user($client, 'admin@endlech.lu')->getMarketingConsentAt();
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $token = $crawler->filter('form[action$="/profile/newsletter"] input[name=_token]')->attr('value');

        $client->request('POST', self::LOCALE.'/profile/newsletter', [
            '_token' => $token,
            'action' => 'subscribe',
            'user' => (string) $this->user($client, 'admin@endlech.lu')->getId(),
            'email' => 'admin@endlech.lu',
        ]);

        self::assertNotNull($this->frisch($client));
        self::assertEquals($fremdVorher, $this->frisch($client, 'admin@endlech.lu'));
    }

    public function testAk58FremdesTokenAendertNichts(): void
    {
        $client = $this->angemeldet();

        $client->request('POST', self::LOCALE.'/profile/newsletter', ['_token' => 'gefaelscht', 'action' => 'subscribe']);

        self::assertResponseRedirects(self::LOCALE.'/profile');
        self::assertNull($this->frisch($client));
    }

    public function testAk59ElfterWechselScheitertAuchVonAndererAdresse(): void
    {
        $client = $this->angemeldet();
        $factory = $client->getContainer()->get('limiter.newsletter_toggle');
        // Im Test steht das Kontingent auf 10000 (when@test); bis auf null aufgebraucht ist es
        // derselbe Zustand wie nach zehn Wechseln in Produktion.
        $factory->create(self::KONTO)->consume(10000);
        $client->setServerParameter('REMOTE_ADDR', '203.0.113.77');

        $this->umschalten($client);
        $crawler = $client->followRedirect();

        self::assertStringContainsString('Zu viele Änderungen', $crawler->text());
        self::assertNull($this->frisch($client));
    }

    public function testAk59ProduktionswertIstZehnJeStunde(): void
    {
        $konfiguration = Yaml::parseFile(static::getContainer()->getParameter('kernel.project_dir').'/config/packages/framework.yaml');
        $limiter = $konfiguration['framework']['rate_limiter']['newsletter_toggle'];

        self::assertSame('sliding_window', $limiter['policy']);
        self::assertSame(10, $limiter['limit']);
        self::assertSame('1 hour', $limiter['interval']);
    }

    public function testAk60AbbestellenOhneAboVerbrauchtNichts(): void
    {
        $client = $this->angemeldet();
        $factory = $client->getContainer()->get('limiter.newsletter_toggle');
        $vorher = $factory->create(self::KONTO)->consume(0)->getRemainingTokens();
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $token = $crawler->filter('form[action$="/profile/newsletter"] input[name=_token]')->attr('value');

        $client->request('POST', self::LOCALE.'/profile/newsletter', ['_token' => $token, 'action' => 'unsubscribe']);

        self::assertSame($vorher, $factory->create(self::KONTO)->consume(0)->getRemainingTokens());
    }
}
