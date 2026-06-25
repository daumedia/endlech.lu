<?php

namespace App\Tests\Controller;

use App\Tests\AbstractWebTestCase;

final class SecurityControllerTest extends AbstractWebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('input[name="_password"]')->count());
    }

    public function testLoggedInUserIsRedirected(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/login');

        self::assertResponseRedirects();
    }

    public function testValidCredentialsAuthenticate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        $client->submit($crawler->filter('form')->form([
            '_username' => 'user@endlech.lu',
            '_password' => 'user123',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testInvalidCredentialsAreRejected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        $client->submit($crawler->filter('form')->form([
            '_username' => 'user@endlech.lu',
            '_password' => 'falsches-passwort',
        ]));

        // Zurück zur Login-Seite (Authentifizierung fehlgeschlagen).
        self::assertResponseRedirects(self::LOCALE.'/login');
    }
}
