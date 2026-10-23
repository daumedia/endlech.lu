<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\AdminStatsService;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Feature 12 — Randfälle (EC-01, EC-07 bis EC-10), soweit sie sich ohne zweites Gerät und ohne
 * abgebrochene Verbindung nachstellen lassen. EC-02 und EC-03 stehen mit Begründung im Bericht.
 */
final class OnboardingEdgeCaseTest extends AbstractWebTestCase
{
    private const KONTO = 'user@endlech.lu';

    private function vorgesehen(KernelBrowser $client): void
    {
        $this->user($client, self::KONTO)->markOnboardingDue();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function anmelden(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/login');
        $client->submit($this->formWithField($crawler, '_password', ['_username' => self::KONTO, '_password' => 'user123']));
    }

    public function testEc01ZweitesGeraetBekommtSieNichtMehr(): void
    {
        $geraetA = static::createClient();
        $this->vorgesehen($geraetA);
        $this->anmelden($geraetA);
        $geraetA->followRedirect();
        self::assertResponseIsSuccessful();

        // Gerät B: frische Sitzung, dieselbe Datenbank.
        $geraetA->getCookieJar()->clear();
        $this->anmelden($geraetA);

        self::assertSame('/de', rtrim((string) parse_url((string) $geraetA->getResponse()->headers->get('Location'), PHP_URL_PATH), '/'));
    }

    public function testEc07PasswortZuruecksetzenVorDerErstenAnmeldung(): void
    {
        // Feature 01 meldet nach dem Zurücksetzen nicht an — die erste Anmeldung danach ist eine
        // gewöhnliche über das Formular. Nachgestellt wird deshalb genau diese.
        $client = static::createClient();
        $this->vorgesehen($client);
        $user = $this->user($client, self::KONTO);
        $token = $user->generatePasswordResetToken();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        $crawler = $client->request('GET', self::LOCALE.'/passwort-zuruecksetzen/'.$token);
        self::assertResponseIsSuccessful();

        $this->anmelden($client);

        self::assertResponseRedirects(self::LOCALE.'/einfuehrung');
    }

    public function testEc08SchonImAnderenTabAbonniertAendertNichts(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO)->setMarketingConsentAt(null);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::KONTO)->reset();
        $form = $client->request('GET', self::LOCALE.'/einfuehrung/newsletter')->filter('#einfuehrung-newsletter')->form();

        // Der zweite Tab abonniert über das Profil.
        $profil = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($profil->filter('form[action$="/profile/newsletter"]')->form());
        $factory = $client->getContainer()->get('limiter.newsletter_toggle');
        $nachProfil = $factory->create(self::KONTO)->consume(0)->getRemainingTokens();
        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        $erste = $this->user($client, self::KONTO)->getMarketingConsentAt();

        $form['onboarding_newsletter[consent]']->tick();
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/restaurants');
        self::assertSame($nachProfil, $factory->create(self::KONTO)->consume(0)->getRemainingTokens(), 'Kein zweiter Verbrauch.');
        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertEquals($erste, $this->user($client, self::KONTO)->getMarketingConsentAt(), 'Der Einwilligungszeitpunkt bleibt der erste.');
    }

    public function testEc09NeustartImProfilAendertDieSummeNicht(): void
    {
        $client = static::createClient();
        $this->vorgesehen($client);
        $this->loginAs($client, self::KONTO);
        $client->request('GET', self::LOCALE.'/einfuehrung');
        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        $erstesDatum = $this->user($client, self::KONTO)->getOnboardingShownAt();
        $summe = $client->getContainer()->get(AdminStatsService::class)->getOnboardingShownCount();

        $client->request('GET', self::LOCALE.'/einfuehrung');
        $client->getContainer()->get(EntityManagerInterface::class)->clear();

        self::assertEquals($erstesDatum, $this->user($client, self::KONTO)->getOnboardingShownAt());
        self::assertSame($summe, $client->getContainer()->get(AdminStatsService::class)->getOnboardingShownCount());
    }

    public function testEc10KontoImAnderenTabGeloescht(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, self::KONTO);
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->remove($user);
        $em->flush();

        $client->request('GET', self::LOCALE.'/einfuehrung/eintraege');

        self::assertResponseRedirects();
        self::assertStringEndsWith('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
