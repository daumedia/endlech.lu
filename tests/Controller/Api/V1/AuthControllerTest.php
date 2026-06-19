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
        self::assertFalse($data['data']['isVerified']);

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());
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
