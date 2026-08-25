<?php

namespace App\Tests\Functional\Controller\Api\V1;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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

    /**
     * BF-26: Die Fehlerantworten des JWT-Bundles trugen ein flaches
     * {"code","message"} statt {"error":{"code","message"}} — und trafen damit
     * ausgerechnet die häufigsten Fehlerfälle eines Mobil-Clients.
     */
    public function testBf26FalschesPasswortAntwortetImVereinbartenFormat(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@endlech.lu', 'password' => 'falsch']),
        );

        self::assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
        self::assertSame(401, $data['error']['code']);
        self::assertNotEmpty($data['error']['message']);
    }

    public function testBf26UngueltigesTokenAntwortetImVereinbartenFormat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer kein.gueltiges.token']);

        self::assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(401, $data['error']['code']);
    }

    /**
     * Der vierte Pfad des Subscribers: ein korrekt signiertes, aber abgelaufenes
     * Token. Ohne den privaten Schlüssel ließe sich das nicht erzeugen — hier
     * stellt der TokenManager es mit vergangenem `exp` aus.
     */
    public function testBf26AbgelaufenesTokenAntwortetImVereinbartenFormat(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->createFromPayload($user, ['exp' => time() - 60]);

        $client->request('GET', '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        self::assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(401, $data['error']['code']);
        self::assertArrayNotHasKey('code', array_diff_key($data, ['error' => null]), 'Kein flaches code-Feld auf oberster Ebene.');
    }

    /**
     * BF-25: Die Registrierung fiel unter das anonyme Limit (100/Minute) und
     * verschickte damit bis zu 100 Mails je Minute an eine frei wählbare fremde
     * Adresse. In `when@test` steht das Limit auf 10000 — geprüft wird deshalb,
     * dass der Endpunkt überhaupt am strengen Limiter hängt.
     */
    public function testBf25RegistrierungHaengtAmEigenenLimiter(): void
    {
        $subscriber = static::getContainer()->get(\App\EventSubscriber\ApiRateLimitSubscriber::class);
        $spiegel = new \ReflectionClass($subscriber);
        $feld = $spiegel->getProperty('apiRegisterLimiter');

        self::assertNotNull($feld->getValue($subscriber), 'Der Registrierungs-Limiter muss verdrahtet sein.');
    }
}
