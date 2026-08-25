<?php

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Cuisine;
use App\Entity\Restaurant;
use App\Entity\RestaurantSuggestion;
use App\Entity\User;
use App\Enum\TriState;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RestaurantApiControllerTest extends WebTestCase
{
    public function testIndexReturnsPaginatedEnvelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants?limit=5');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = $this->json($client);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('meta', $data);
        self::assertLessThanOrEqual(5, \count($data['data']));
        self::assertSame(5, $data['meta']['limit']);
        self::assertGreaterThanOrEqual(11, $data['meta']['total']);
        self::assertArrayHasKey('totalPages', $data['meta']);

        $first = $data['data'][0];
        self::assertArrayHasKey('id', $first);
        self::assertArrayHasKey('isOpenNow', $first);
        self::assertArrayHasKey('accessibility', $first);
    }

    public function testIndexFilterWheelchairOnlyReturnsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants?wheelchair=1&limit=50');

        self::assertResponseIsSuccessful();
        $data = $this->json($client);

        self::assertNotEmpty($data['data']);
        foreach ($data['data'] as $restaurant) {
            self::assertTrue($restaurant['accessibility']['wheelchairAccessible']);
        }
    }

    public function testIndexSortByNameReturnsAlphabetical(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants?sort=name&limit=50');

        self::assertResponseIsSuccessful();
        $data = $this->json($client);

        self::assertSame('name', $data['meta']['sort']);

        $names = array_column($data['data'], 'name');
        self::assertNotEmpty($names);

        $sorted = $names;
        usort($sorted, 'strcasecmp');
        self::assertSame($sorted, $names);
    }

    public function testIndexSortByRatingReturnsDescending(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants?sort=rating&limit=6');

        self::assertResponseIsSuccessful();
        $data = $this->json($client);

        self::assertSame('rating', $data['meta']['sort']);

        // Auf der ersten Seite (limit 6) hat keines der Top-Restaurants ein null-Rating.
        $ratings = array_column($data['data'], 'rating');
        self::assertNotEmpty($ratings);
        for ($i = 0; $i < \count($ratings) - 1; ++$i) {
            self::assertGreaterThanOrEqual($ratings[$i + 1], $ratings[$i]);
        }
    }

    public function testIndexSortByNewestReturnsDescendingByCreatedAt(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants?sort=newest&limit=50');

        self::assertResponseIsSuccessful();
        $data = $this->json($client);

        self::assertSame('newest', $data['meta']['sort']);

        // ATOM-Strings gleicher Zeitzone sind lexikografisch = chronologisch vergleichbar;
        // gleiche createdAt-Werte der Fixtures sind als Ties erlaubt.
        $dates = array_column($data['data'], 'createdAt');
        self::assertNotEmpty($dates);
        for ($i = 0; $i < \count($dates) - 1; ++$i) {
            self::assertGreaterThanOrEqual($dates[$i + 1], $dates[$i]);
        }
    }

    public function testIndexInvalidSortFallsBackToRating(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants?sort=unsinn');

        self::assertResponseIsSuccessful();
        self::assertSame('rating', $this->json($client)['meta']['sort']);
    }

    public function testShowReturnsFullDetailWithoutPassword(): void
    {
        $client = static::createClient();
        $id = $this->firstRestaurantId();

        $client->request('GET', '/api/v1/restaurants/' . $id);

        self::assertResponseIsSuccessful();
        $raw = $client->getResponse()->getContent();
        self::assertStringNotContainsStringIgnoringCase('password', $raw);

        $data = json_decode($raw, true);
        self::assertSame($id, $data['id']);
        self::assertArrayHasKey('payment', $data);
        self::assertArrayHasKey('contact', $data);
        self::assertArrayHasKey('location', $data);
        self::assertArrayHasKey('openingHours', $data);
        self::assertArrayHasKey('orderingOptions', $data);
        self::assertArrayHasKey('spokenLanguages', $data);
        self::assertCount(7, $data['openingHours']);
    }

    public function testShowUnknownReturns404Json(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants/99999999');

        self::assertResponseStatusCodeSame(404);
        $data = $this->json($client);
        self::assertSame(404, $data['error']['code']);
    }

    public function testImagesEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/restaurants/' . $this->firstRestaurantId() . '/images');

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $this->json($client));
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Test', 'city' => 'Luxembourg']),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateRejectsInvalidCoordinatesWith422(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode([
                'name' => 'Koordinaten-Test',
                'city' => 'Luxembourg',
                'location' => ['latitude' => 'not-a-number', 'longitude' => 999],
            ]),
        );

        self::assertResponseStatusCodeSame(422);
        $data = $this->json($client);
        self::assertArrayHasKey('latitude', $data['error']['violations']);
        self::assertArrayHasKey('longitude', $data['error']['violations']);
    }

    public function testCreateAcceptsValidNumericCoordinates(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode([
                'name' => 'Geo Bistro',
                'city' => 'Luxembourg',
                'location' => ['latitude' => 49.6116, 'longitude' => 6.1319],
            ]),
        );

        self::assertResponseStatusCodeSame(202);

        // Die Koordinaten wandern in den Vorschlag, nicht in ein Restaurant. Ohne
        // die Spalten dort gingen sie zwischen Eingang und Freigabe verloren.
        $suggestion = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(RestaurantSuggestion::class)
            ->find($this->json($client)['submissionId']);

        self::assertEqualsWithDelta(49.6116, (float) $suggestion->getLatitude(), 0.0000001);
        self::assertEqualsWithDelta(6.1319, (float) $suggestion->getLongitude(), 0.0000001);
    }

    /**
     * BF-24: Der Endpunkt legt einen Vorschlag an, kein öffentliches Restaurant.
     *
     * Vorher stand der Eintrag augenblicklich in der Restaurantliste, auf einer
     * Detailseite, in den Kennzahlen von /open und im Datensatz unter CC BY 4.0 —
     * ohne dass jemand ihn gesehen hatte.
     */
    public function testAk21CreateLegtEinenVorschlagAnKeinRestaurant(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userId = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu'])->getId();
        $name = 'Eingereicht API ' . uniqid();

        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode(['name' => $name, 'city' => 'Luxembourg']),
        );

        self::assertResponseStatusCodeSame(202);
        $data = $this->json($client);
        self::assertSame('pending', $data['status']);

        $suggestion = $em->getRepository(RestaurantSuggestion::class)->find($data['submissionId']);
        self::assertSame($name, $suggestion->getName());
        self::assertSame(RestaurantSuggestion::STATUS_PENDING, $suggestion->getStatus());
        self::assertSame($userId, $suggestion->getSuggestedBy()->getId());

        // Und genau das nicht: ein Restaurant unter diesem Namen.
        self::assertNull($em->getRepository(Restaurant::class)->findOneBy(['name' => $name]));
    }

    /**
     * BF-24, zweite Hälfte: Der Vorschlag darf auf keinem öffentlichen Weg auftauchen.
     */
    public function testAk21VorschlagErscheintNichtInDerOeffentlichenListe(): void
    {
        $client = static::createClient();
        $name = 'Unsichtbar API ' . uniqid();

        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode(['name' => $name, 'city' => 'Luxembourg']),
        );
        self::assertResponseStatusCodeSame(202);

        $client->request('GET', '/api/v1/restaurants?sort=newest&limit=50');
        self::assertStringNotContainsString($name, $client->getResponse()->getContent());

        $client->request('GET', '/open/dataset.csv');
        self::assertStringNotContainsString($name, $client->getResponse()->getContent());
    }

    /**
     * BF-24, dritter Teil: `cuisines` schrieb über `findOrCreateByName()` dauerhaft
     * in die öffentliche Filterauswahl der Website. Gemessen wurden dort „Pizzza"
     * und „JETZT BEI UNS BESTELLEN 0900-123456".
     */
    public function testAk21CuisinesLegenKeineOeffentlichenKuechenTypenAn(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $vorher = $em->getRepository(Cuisine::class)->count([]);

        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode([
                'name' => 'Kuechen API ' . uniqid(),
                'city' => 'Luxembourg',
                'cuisines' => ['Pizzza', 'JETZT BESTELLEN'],
            ]),
        );

        self::assertResponseStatusCodeSame(202);
        self::assertSame($vorher, $em->getRepository(Cuisine::class)->count([]), 'Kein neuer Küchen-Typ darf entstehen.');

        $suggestion = $em->getRepository(RestaurantSuggestion::class)->find($this->json($client)['submissionId']);
        self::assertSame('Pizzza, JETZT BESTELLEN', $suggestion->getCuisine(), 'Der Wunsch bleibt als Freitext erhalten.');
    }

    /**
     * BF-27: Zu lange Küchen-Angaben endeten in einem 500er aus der Datenbankschicht
     * — jeder davon erzeugt in Produktion einen Sentry-Bericht.
     */
    public function testBf27ZuLangeKuechenAngabeLiefert422(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode([
                'name' => 'Lang API',
                'city' => 'Luxembourg',
                'cuisines' => [str_repeat('A', 200)],
            ]),
        );

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('cuisines', $this->json($client)['error']['violations']);
    }

    /**
     * Nicht übermittelte Merkmale sind „weiß nicht", nicht „nein" — für eine
     * Barrierefreiheits-Plattform ist das der wesentliche Unterschied.
     */
    public function testNichtUebermittelteMerkmaleWerdenNichtAlsNeinGespeichert(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode([
                'name' => 'Tristate API ' . uniqid(),
                'city' => 'Luxembourg',
                'accessibility' => ['wheelchairAccessible' => true, 'accessibleToilet' => false],
            ]),
        );

        self::assertResponseStatusCodeSame(202);
        $suggestion = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(RestaurantSuggestion::class)
            ->find($this->json($client)['submissionId']);

        self::assertSame(TriState::YES, $suggestion->isWheelchairAccessible());
        self::assertSame(TriState::NO, $suggestion->hasAccessibleToilet());
        self::assertSame(TriState::UNKNOWN, $suggestion->allowsAssistanceDogs(), 'Nicht gefragt ist nicht "nein".');
    }

    public function testCreateRejectsMissingNameWith422(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode(['city' => 'Luxembourg']),
        );

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('name', $this->json($client)['error']['violations']);
    }

    public function testCreateRejectsInvalidJsonWith400(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: '"kein-objekt"',
        );

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return json_decode($client->getResponse()->getContent(), true);
    }

    private function firstRestaurantId(): int
    {
        $repository = static::getContainer()->get(RestaurantRepository::class);

        return $repository->findOneBy([])->getId();
    }

    private function token(): string
    {
        $container = static::getContainer();
        /** @var User $user */
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);

        return $container->get(JWTTokenManagerInterface::class)->create($user);
    }
}
