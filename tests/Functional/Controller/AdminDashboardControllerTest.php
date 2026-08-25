<?php

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
     * AK-11 / BF-33: Ein fremder Referer wird nicht übernommen.
     *
     * Der Referer ist seit der Reparatur nur noch ein Wegweiser — aus ihm stammt der
     * Routenname, die Adresse baut der Router. Ein fremder Host kann so gar nicht
     * entstehen. Vorher landete ein Admin hier bei `https://boeswillig.example/phishing`.
     */
    #[DataProvider('fremdeReferer')]
    public function testAk11FremderRefererFuehrtAufsEigeneDashboard(string $referer): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin/locale/fr', server: ['HTTP_REFERER' => $referer]);

        self::assertResponseRedirects('/fr/admin');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fremdeReferer(): iterable
    {
        yield 'fremder Host' => ['https://boeswillig.example/phishing'];
        yield 'protokollrelativ' => ['//evil.example/x'];
        yield 'javascript-Schema' => ['javascript:alert(1)'];
        yield 'eigener Host, fremder Bereich' => ['http://localhost/de/restaurants'];
    }

    /**
     * BF-34: Der Sprachwechsel wirkt — er schreibt den Pfad um statt in die Sitzung.
     */
    public function testAk10SprachwechselFuehrtAufDieselbeSeiteInDerNeuenSprache(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin/locale/fr', server: [
            'HTTP_REFERER' => 'http://localhost'.self::LOCALE.'/admin/restaurants',
        ]);

        self::assertResponseRedirects('/fr/admin/restaurants');

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Gérer les restaurants', $crawler->filter('body')->text());
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
