<?php

namespace App\Tests\Controller\Api\V1;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    public function testLoginReturnsToken(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@endlech.lu', 'password' => 'user123']),
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@endlech.lu', 'password' => 'falsch']),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testRegisterCreatesUnverifiedUserWithoutToken(): void
    {
        $client = static::createClient();
        $email = 'apitest_' . uniqid() . '@endlech.lu';

        $client->request(
            'POST',
            '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'API Tester', 'email' => $email, 'password' => 'supersecret']),
        );

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayNotHasKey('token', $data);
        self::assertArrayHasKey('message', $data);

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());
    }

    /**
     * Eine bereits registrierte E-Mail darf nicht durch eine abweichende Antwort
     * erkennbar sein (kein User-Enumeration). Antwort identisch zur Neuregistrierung.
     */
    public function testRegisterWithExistingEmailDoesNotLeakAndCreatesNoDuplicate(): void
    {
        $client = static::createClient();
        $repository = static::getContainer()->get(UserRepository::class);
        $before = \count($repository->findBy(['email' => 'user@endlech.lu']));

        $client->request(
            'POST',
            '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Eindringling', 'email' => 'user@endlech.lu', 'password' => 'supersecret']),
        );

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $data);
        self::assertStringNotContainsStringIgnoringCase('registriert', $data['message']);

        // Kein Duplikat angelegt.
        self::assertSame($before, \count($repository->findBy(['email' => 'user@endlech.lu'])));
    }

    public function testRegisterValidationFailsForShortPassword(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'X', 'email' => 'not-an-email', 'password' => '123']),
        );

        self::assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('violations', $data['error']);
        self::assertArrayHasKey('name', $data['error']['violations']);
        self::assertArrayHasKey('email', $data['error']['violations']);
        self::assertArrayHasKey('password', $data['error']['violations']);
    }
}
