<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Routen der Sponsoren und wer dort zu sehen ist (Feature 15, T08).
 */
final class SponsorControllerTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    #[DataProvider('sprachen')]
    public function testUebersichtAntwortetInAllenSprachen(string $sprache): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->request('GET', '/'.$sprache.'/sponsoren');

        self::assertResponseIsSuccessful();
    }

    public function testAktiverSponsorHatEineSeite(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->request('GET', self::LOCALE.'/sponsoren/acme');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Acme S.à r.l.');
    }

    public function testEhemaligerSponsorHatEineSeite(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->request('GET', self::LOCALE.'/sponsoren/ehemals');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ehemals AG');
    }

    public function testAk22UnbekannteAdresseIst404(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->request('GET', self::LOCALE.'/sponsoren/gibt-es-nicht');

        self::assertResponseStatusCodeSame(404);
        self::assertStringNotContainsString('Acme', (string) $client->getResponse()->getContent());
    }

    public function testAk22AdresseAusserhalbDesMustersIst404(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->request('GET', self::LOCALE.'/sponsoren/Acme');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAk23NochNichtBegonnenerSponsorIst404(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->request('GET', self::LOCALE.'/sponsoren/morgen');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAk23AbDemBeginnIstDieSeiteDa(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all(), now: '2027-06-16 00:00:00');
        $client->request('GET', self::LOCALE.'/sponsoren/morgen');

        self::assertResponseIsSuccessful();
    }

    public function testAk43EntfernteAdresseLeitetDauerhaftAufDieUebersicht(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all(), ['entfernt-sarl']);
        $client->request('GET', self::LOCALE.'/sponsoren/entfernt-sarl');

        self::assertResponseRedirects(self::LOCALE.'/sponsoren', 301);
    }
}
