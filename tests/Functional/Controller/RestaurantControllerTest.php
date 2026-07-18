<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

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

    public function testSortByNameRendersCardsAlphabetically(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/restaurants?sort=name');

        self::assertResponseIsSuccessful();

        $rendered = $this->cardNames($crawler);
        self::assertNotEmpty($rendered);

        $sorted = $rendered;
        usort($sorted, 'strcasecmp');

        self::assertSame($sorted, $rendered);
    }

    public function testSortByRatingRendersRepositoryOrder(): void
    {
        $client = static::createClient();

        // Erwartete Reihenfolge der 6 sichtbaren Karten direkt aus dem Repository,
        // damit der Test die Controller→Template-Kette prüft, ohne Fixture-Werte zu fixieren.
        $expected = array_map(
            static fn (Restaurant $r) => $r->getName(),
            iterator_to_array(
                $client->getContainer()->get(RestaurantRepository::class)->findPaginated('rating', 1, 6),
            ),
        );

        $crawler = $client->request('GET', self::LOCALE.'/restaurants?sort=rating');

        self::assertResponseIsSuccessful();
        self::assertSame($expected, $this->cardNames($crawler));
    }

    public function testSortByNewestShowsFreshlyCreatedFirst(): void
    {
        $client = static::createClient();

        $fresh = (new Restaurant())->setName('Brandneu Funktional '.uniqid())->setCity('Luxembourg');
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist($fresh);
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/restaurants?sort=newest');

        self::assertResponseIsSuccessful();
        self::assertSame($fresh->getName(), $this->cardNames($crawler)[0] ?? null);
    }

    /**
     * Namen der gerenderten Restaurant-Karten in Anzeige-Reihenfolge.
     *
     * @return list<string>
     */
    private function cardNames(Crawler $crawler): array
    {
        return $crawler->filter('article h3')->each(static fn (Crawler $node) => trim($node->text()));
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
