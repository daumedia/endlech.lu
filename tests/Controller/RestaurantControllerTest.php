<?php

namespace App\Tests\Controller;

use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;

final class RestaurantControllerTest extends AbstractWebTestCase
{
    public function testIndexLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/restaurants');

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithSortAndFilters(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/restaurants?sort=name&verified=1&vegan=1&city=Lux');

        self::assertResponseIsSuccessful();
    }

    /**
     * Regression: Der ?lang_…-Filter nutzt JSON_CONTAINS. Ohne die registrierte
     * DQL-Funktion (App\Doctrine\JsonContainsFunction) warf diese Route einen 500er.
     */
    public function testLanguageFilterDoesNotError(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/restaurants?lang_de=1&lang_fr=1');

        self::assertResponseIsSuccessful();
    }

    public function testInvalidSortFallsBackGracefully(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/restaurants?sort=unsinn&page=0');

        self::assertResponseIsSuccessful();
    }

    public function testShowLoads(): void
    {
        $client = static::createClient();
        $id = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([])->getId();

        $client->request('GET', self::LOCALE.'/restaurants/'.$id);

        self::assertResponseIsSuccessful();
    }

    public function testShowUnknownReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/restaurants/99999999');

        self::assertResponseStatusCodeSame(404);
    }
}
