<?php

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;

final class SecurityControllerTest extends AbstractWebTestCase
{
    /**
     * Die Login-Seite trägt seit den Passkeys zwei Formulare. Deshalb hier
     * durchgehend formWithField() statt filter('form') – sonst greift der Test
     * das Passkey-Formular, das weder _username noch _password kennt.
     */
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('input[name="_password"]')->count());
    }

    public function testLoginPageOffersPasskey(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        self::assertResponseIsSuccessful();
        // Das Formular, das die Assertion aufnimmt, zeigt auf denselben
        // check_path wie der Passwort-Login – dort entscheidet der
        // PasskeyAuthenticator anhand des Feldes.
        self::assertGreaterThan(0, $crawler->filter('input[name="_assertion"]')->count());
        self::assertGreaterThan(0, $crawler->filter('[data-controller="passkey-auth"]')->count());
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

        $client->submit($this->formWithField($crawler, '_password', [
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

        $client->submit($this->formWithField($crawler, '_password', [
            '_username' => 'user@endlech.lu',
            '_password' => 'falsches-passwort',
        ]));

        // Zurück zur Login-Seite (Authentifizierung fehlgeschlagen).
        self::assertResponseRedirects(self::LOCALE.'/login');
    }
}
