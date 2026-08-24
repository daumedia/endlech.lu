<?php

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;

final class AdminDashboardControllerTest extends AbstractWebTestCase
{
    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseRedirects();
    }

    public function testNonAdminIsForbidden(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminSeesDashboard(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseIsSuccessful();
    }

    /**
     * AK-11 / BF-33: `admin_set_locale` leitet ungeprüft auf den Referer weiter.
     *
     * Der Test hält den Befund fest, bis er behoben ist — er schlägt fehl, sobald
     * die Weiterleitung auf die eigene Herkunft beschränkt wird. Das ist Absicht:
     * Ohne ihn fiele beim nächsten Durchlauf niemandem auf, dass sich hier etwas
     * geändert hat.
     */
    public function testAk11SprachwahlLeitetUngeprueftAufDenRefererWeiter(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin/locale/fr', server: [
            'HTTP_REFERER' => 'https://boeswillig.example/phishing',
        ]);

        self::assertResponseRedirects('https://boeswillig.example/phishing');
    }

    public function testEc02OhneRefererLandetManAufDemDashboard(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin/locale/fr');

        self::assertResponseRedirects();
        self::assertStringContainsString('/admin', $client->getResponse()->headers->get('Location'));
        self::assertStringNotContainsString('boeswillig', $client->getResponse()->headers->get('Location'));
    }

    /**
     * AK-07: Das Routen-Requirement fängt unbekannte Sprachcodes ab, bevor der
     * Controller sie sieht.
     */
    public function testAk07UnbekannterSprachcodeErgibt404(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin/locale/xx');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * AK-14: ROLE_ADMIN lässt sich über keine Route vergeben — es gibt keine
     * Nutzerverwaltung (FB-01). Der Test hält den Zustand fest: Entsteht eine,
     * gehört sie geprüft.
     */
    public function testAk14KeineRouteVergibtRollen(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');

        $verdaechtig = array_filter(
            array_keys($router->getRouteCollection()->all()),
            static fn (string $name) => str_contains($name, 'user') || str_contains($name, 'role'),
        );

        self::assertSame([], array_values($verdaechtig), 'Neue Routen zur Nutzerverwaltung gehören geprüft.');
    }
}
