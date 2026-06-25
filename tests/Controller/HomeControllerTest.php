<?php

namespace App\Tests\Controller;

use App\Tests\AbstractWebTestCase;

final class HomeControllerTest extends AbstractWebTestCase
{
    public function testHomePageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/');

        self::assertResponseIsSuccessful();
    }
}
