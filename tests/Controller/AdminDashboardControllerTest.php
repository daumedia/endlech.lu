<?php

namespace App\Tests\Controller;

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
}
