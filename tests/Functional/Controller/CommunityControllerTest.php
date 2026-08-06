<?php

namespace App\Tests\Functional\Controller;

use App\Repository\RestaurantSuggestionRepository;
use App\Tests\AbstractWebTestCase;

final class CommunityControllerTest extends AbstractWebTestCase
{
    public function testGuestIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/community/suggest');

        self::assertResponseRedirects();
    }

    public function testUnverifiedUserIsRedirected(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'unverified@endlech.lu');

        $client->request('GET', self::LOCALE.'/community/suggest');

        self::assertResponseRedirects();
    }

    public function testVerifiedUserSeesForm(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/community/suggest');

        self::assertResponseIsSuccessful();
    }

    public function testVerifiedUserCanSubmitSuggestion(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');

        $name = 'Vorschlag '.uniqid();
        $crawler = $client->request('GET', self::LOCALE.'/community/suggest');
        $client->submit($this->formWithField($crawler, 'restaurant_suggestion[name]', [
            'restaurant_suggestion[name]' => $name,
            'restaurant_suggestion[city]' => 'Esch-sur-Alzette',
            'restaurant_suggestion[cuisine]' => 'Italienisch',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/community/thanks');

        $suggestion = $client->getContainer()->get(RestaurantSuggestionRepository::class)->findOneBy(['name' => $name]);
        self::assertNotNull($suggestion);
        self::assertSame($user->getId(), $suggestion->getSuggestedBy()?->getId());
    }
}
