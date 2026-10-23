<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feature 12 — Reproduktionen zu BF-170, BF-171 und BF-174 aus der Nachprüfung vom 2026-10-23.
 * BF-165 bis BF-168 reproduziert die QA selbst in `Qa12NachpruefungTest`, BF-172 steht in
 * `Bf172GleichzeitigesAboTest`.
 */
final class Feature12Nachpruefung2BugfixTest extends AbstractWebTestCase
{
    private const KONTO = 'user@endlech.lu';

    public function testBf170HeadAnfrageVermerktKeineAnzeige(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, self::KONTO);
        $user->markOnboardingDue();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $client->request('HEAD', self::LOCALE.'/einfuehrung');

        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull($this->user($client, self::KONTO)->getOnboardingShownAt(), 'Eine HEAD-Anfrage zeigt nichts an.');
    }

    public function testBf171AbgeschlossenErstNachDemErfolgreichenAbsenden(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, self::KONTO);
        $user->setMarketingConsentAt(null);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::KONTO)->reset();

        // Der Knopf selbst zählt nicht mehr — er weiß nicht, ob das Absenden gelingt.
        $crawler = $client->request('GET', self::LOCALE.'/einfuehrung/newsletter');
        self::assertCount(0, $crawler->filter('button[form="einfuehrung-newsletter"][data-usage-event-name-value]'));

        // Abgewiesen (Deckel erreicht): kein Ereignis.
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::KONTO)->consume(10000);
        $form = $crawler->filter('#einfuehrung-newsletter')->form();
        $form['onboarding_newsletter[consent]']->tick();
        $client->submit($form);
        $abgewiesen = $client->followRedirect();
        self::assertCount(0, $abgewiesen->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]'));

        // Gelungen: Die Seite danach zählt.
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::KONTO)->reset();
        $form = $client->request('GET', self::LOCALE.'/einfuehrung/newsletter')->filter('#einfuehrung-newsletter')->form();
        $form['onboarding_newsletter[consent]']->tick();
        $client->submit($form);
        self::assertResponseRedirects(self::LOCALE.'/restaurants');
        $danach = $client->followRedirect();
        self::assertCount(1, $danach->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"][data-usage-event-on-connect-value="true"]'));
    }

    public function testBf174UnbestaetigtesKontoBekommtKeineAdressweiteZusage(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'unverified@endlech.lu');
        $translator = $client->getContainer()->get(TranslatorInterface::class);

        $text = $client->request('GET', self::LOCALE.'/profile')->filter('main')->text();

        self::assertStringNotContainsString($translator->trans('newsletter.text', [], null, 'de'), $text);
        self::assertStringContainsString($translator->trans('newsletter.text_unverified', [], null, 'de'), $text);
    }
}
