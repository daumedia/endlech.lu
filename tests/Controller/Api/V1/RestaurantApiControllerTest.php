<?php

namespace App\Tests\Controller\Api\V1;

use App\Entity\User;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
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

        self::assertResponseStatusCodeSame(201);
        $data = $this->json($client);
        self::assertEqualsWithDelta(49.6116, (float) $data['location']['latitude'], 0.0000001);
        self::assertEqualsWithDelta(6.1319, (float) $data['location']['longitude'], 0.0000001);
    }

    public function testCreateMarksSubmitterAndIsUnverified(): void
    {
        $client = static::createClient();
        $userId = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu'])->getId();

        $client->request(
            'POST',
            '/api/v1/restaurants',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token()],
            content: json_encode(['name' => 'Eingereicht API ' . uniqid(), 'city' => 'Luxembourg']),
        );

        self::assertResponseStatusCodeSame(201);
        $data = $this->json($client);
        self::assertFalse($data['isVerified']);
        self::assertNotNull($data['submittedBy']);
        self::assertSame($userId, $data['submittedBy']['id']);
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
