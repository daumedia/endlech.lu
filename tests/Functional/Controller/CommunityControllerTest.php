<?php

namespace App\Tests\Functional\Controller;

use App\Enum\TriState;
use App\Repository\RestaurantSuggestionRepository;
use App\Tests\AbstractWebTestCase;

final class CommunityControllerTest extends AbstractWebTestCase
{
    /**
     * Die 12 dreiwertigen Pflichtfragen mit gemischten Antworten.
     *
     * @return array<string, string>
     */
    private static function triStateValues(): array
    {
        return [
            'restaurant_suggestion[isWheelchairAccessible]' => 'yes',
            'restaurant_suggestion[hasAccessibleToilet]' => 'no',
            'restaurant_suggestion[allowsAssistanceDogs]' => 'unknown',
            'restaurant_suggestion[hasBrightLighting]' => 'yes',
            'restaurant_suggestion[hasChangingTable]' => 'no',
            'restaurant_suggestion[hasDisabledParking]' => 'unknown',
            'restaurant_suggestion[isVegan]' => 'yes',
            'restaurant_suggestion[isVegetarian]' => 'no',
            'restaurant_suggestion[isHalal]' => 'unknown',
            'restaurant_suggestion[acceptsCash]' => 'yes',
            'restaurant_suggestion[acceptsCard]' => 'no',
            'restaurant_suggestion[acceptsPayconiq]' => 'unknown',
        ];
    }

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
        ] + self::triStateValues()));

        self::assertResponseRedirects(self::LOCALE.'/community/thanks');

        $suggestion = $client->getContainer()->get(RestaurantSuggestionRepository::class)->findOneBy(['name' => $name]);
        self::assertNotNull($suggestion);
        self::assertSame($user->getId(), $suggestion->getSuggestedBy()?->getId());
    }

    public function testTriStateAnswersArePersisted(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $name = 'Vorschlag '.uniqid();
        $crawler = $client->request('GET', self::LOCALE.'/community/suggest');
        $client->submit($this->formWithField($crawler, 'restaurant_suggestion[name]', [
            'restaurant_suggestion[name]' => $name,
            'restaurant_suggestion[city]' => 'Differdange',
            'restaurant_suggestion[cuisine]' => 'Portugiesisch',
        ] + self::triStateValues()));

        self::assertResponseRedirects(self::LOCALE.'/community/thanks');

        $suggestion = $client->getContainer()->get(RestaurantSuggestionRepository::class)->findOneBy(['name' => $name]);
        self::assertNotNull($suggestion);
        self::assertSame(TriState::YES, $suggestion->isWheelchairAccessible());
        self::assertSame(TriState::NO, $suggestion->hasAccessibleToilet());
        self::assertSame(TriState::UNKNOWN, $suggestion->allowsAssistanceDogs());
        self::assertSame(TriState::NO, $suggestion->acceptsCard());
        self::assertSame(TriState::UNKNOWN, $suggestion->isHalal());
    }

    public function testUnansweredQuestionsAreRejected(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $name = 'Vorschlag '.uniqid();
        $crawler = $client->request('GET', self::LOCALE.'/community/suggest');
        $client->submit($this->formWithField($crawler, 'restaurant_suggestion[name]', [
            'restaurant_suggestion[name]' => $name,
            'restaurant_suggestion[city]' => 'Ettelbruck',
            'restaurant_suggestion[cuisine]' => 'Französisch',
        ]));

        self::assertResponseStatusCodeSame(422);

        $suggestion = $client->getContainer()->get(RestaurantSuggestionRepository::class)->findOneBy(['name' => $name]);
        self::assertNull($suggestion);
    }
}
