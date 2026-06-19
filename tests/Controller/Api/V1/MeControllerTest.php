<?php

namespace App\Tests\Controller\Api\V1;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MeControllerTest extends WebTestCase
{
    public function testMeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsProfileWithoutPassword(): void
    {
        $client = static::createClient();
        $token = $this->tokenFor('user@endlech.lu');

        $client->request('GET', '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        self::assertResponseIsSuccessful();
        $raw = $client->getResponse()->getContent();
        self::assertStringNotContainsStringIgnoringCase('password', $raw);

        $data = json_decode($raw, true);
        self::assertSame('user@endlech.lu', $data['email']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testSubmissionsReturnsList(): void
    {
        $client = static::createClient();
        $token = $this->tokenFor('user@endlech.lu');

        $client->request('GET', '/api/v1/me/submissions', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('data', json_decode($client->getResponse()->getContent(), true));
    }

    private function tokenFor(string $email): string
    {
        $container = static::getContainer();
        /** @var User $user */
        $user = $container->get(UserRepository::class)->findOneBy(['email' => $email]);

        return $container->get(JWTTokenManagerInterface::class)->create($user);
    }
}
