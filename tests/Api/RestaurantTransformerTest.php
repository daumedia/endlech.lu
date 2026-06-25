<?php

namespace App\Tests\Api;

use App\Api\AssetUrlBuilder;
use App\Api\RestaurantTransformer;
use App\Entity\Cuisine;
use App\Entity\OpeningHour;
use App\Entity\OrderingOption;
use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Entity\User;
use App\Enum\Language;
use App\Enum\OrderingPlatform;
use App\Service\OpeningHoursService;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class RestaurantTransformerTest extends TestCase
{
    private OpeningHoursService&Stub $openingHours;

    protected function setUp(): void
    {
        // Reiner Stub (nur Rückgabewerte, keine Verhaltens-Erwartungen).
        $this->openingHours = $this->createStub(OpeningHoursService::class);
    }

    private function transformer(): RestaurantTransformer
    {
        return new RestaurantTransformer(
            $this->openingHours,
            new AssetUrlBuilder(new RequestStack(), 'https://cdn.test'),
        );
    }

    private function slot(int $day, string $open, string $close): OpeningHour
    {
        return (new OpeningHour())
            ->setDayOfWeek($day)
            ->setOpenTime(new \DateTime($open))
            ->setCloseTime(new \DateTime($close));
    }

    private function fullRestaurant(): Restaurant
    {
        $restaurant = (new Restaurant())
            ->setName('Pizzeria Bella Vista')
            ->setCity('Strassen')
            ->setEmoji('🍕')
            ->setRating(8.5)
            ->setIsVerified(true)
            ->setIsWheelchairAccessible(true)
            ->setHasAccessibleToilet(true)
            ->setAllowsAssistanceDogs(false)
            ->setHasBrightLighting(true)
            ->setHasChangingTable(false)
            ->setHasDisabledParking(true)
            ->setAcceptsCash(true)
            ->setAcceptsCard(true)
            ->setAcceptsPayconiq(false)
            ->setIsVegan(false)
            ->setIsVegetarian(true)
            ->setIsHalal(false)
            ->setPhone('+352 12 34 56')
            ->setEmail('info@bella.lu')
            ->setWebsite('https://bella.lu')
            ->setInstagramUrl('https://instagram.com/bella')
            ->setLatitude('49.61160000')
            ->setLongitude('6.13190000')
            ->setNearbyStopsNote('2 Min. zur Tram')
            ->setAccessibilityNotes(['ok:Eingang stufenlos'])
            ->setSpokenLanguages([Language::DE, Language::FR]);

        $restaurant->addCuisine((new Cuisine())->setName('Pizza')->setSlug('pizza'));

        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));
        $restaurant->addOpeningHour($this->slot(3, '18:00', '22:00'));

        $restaurant->addOrderingOption(
            (new OrderingOption())->setPlatform(OrderingPlatform::UBER_EATS)->setUrl('https://ubereats.com/bella')
        );

        $restaurant->getImages()->add(
            (new RestaurantImage())->setFilename('cover.jpg')->setAltText('Cover')->setSortOrder(0)
        );

        $restaurant->setSubmittedBy((new User())->setName('Alice')->setEmail('alice@endlech.lu'));

        return $restaurant;
    }

    public function testListMapsCompactFields(): void
    {
        $this->openingHours->method('isOpenNow')->willReturn(true);

        $data = $this->transformer()->list($this->fullRestaurant());

        self::assertSame('Pizzeria Bella Vista', $data['name']);
        self::assertSame('Strassen', $data['city']);
        self::assertSame('🍕', $data['emoji']);
        self::assertSame(8.5, $data['rating']);
        self::assertTrue($data['isVerified']);
        self::assertTrue($data['isOpenNow']);
        self::assertSame('https://cdn.test/uploads/restaurants/cover.jpg', $data['coverImageUrl']);
        self::assertSame([['id' => null, 'name' => 'Pizza', 'slug' => 'pizza']], $data['cuisines']);
        self::assertSame([
            'wheelchairAccessible' => true,
            'accessibleToilet' => true,
            'assistanceDogs' => false,
            'brightLighting' => true,
            'changingTable' => false,
            'disabledParking' => true,
        ], $data['accessibility']);
        self::assertSame(['vegan' => false, 'vegetarian' => true, 'halal' => false], $data['dietary']);
        self::assertNotEmpty($data['createdAt']);
    }

    public function testCoverImageUrlIsNullWithoutImages(): void
    {
        $this->openingHours->method('isOpenNow')->willReturn(false);

        $data = $this->transformer()->list(new Restaurant());

        self::assertNull($data['coverImageUrl']);
        self::assertSame([], $data['cuisines']);
    }

    public function testDetailMapsNestedStructures(): void
    {
        $this->openingHours->method('isOpenNow')->willReturn(true);
        $this->openingHours->method('getNextOpeningTime')->willReturn([
            'dayOfWeek' => 3,
            'time' => new \DateTime('18:00'),
        ]);

        $data = $this->transformer()->detail($this->fullRestaurant());

        self::assertSame(['cash' => true, 'card' => true, 'payconiq' => false], $data['payment']);

        self::assertSame('+352 12 34 56', $data['contact']['phone']);
        self::assertSame('info@bella.lu', $data['contact']['email']);
        self::assertSame('https://instagram.com/bella', $data['contact']['instagramUrl']);

        self::assertSame('49.61160000', $data['location']['latitude']);
        self::assertSame('6.13190000', $data['location']['longitude']);
        self::assertSame('2 Min. zur Tram', $data['location']['nearbyStopsNote']);

        self::assertSame(['ok:Eingang stufenlos'], $data['accessibilityNotes']);

        self::assertCount(2, $data['spokenLanguages']);
        self::assertSame(['code' => 'de', 'label' => 'Deutsch', 'flag' => '🇩🇪'], $data['spokenLanguages'][0]);

        // Öffnungszeiten: 7 Tage, Tag 3 hat zwei Slots, restliche Tage leer.
        self::assertCount(7, $data['openingHours']);
        self::assertSame(3, $data['openingHours'][2]['dayOfWeek']);
        self::assertSame([
            ['open' => '12:00', 'close' => '14:30'],
            ['open' => '18:00', 'close' => '22:00'],
        ], $data['openingHours'][2]['slots']);
        self::assertSame([], $data['openingHours'][0]['slots']);

        self::assertSame(['dayOfWeek' => 3, 'time' => '18:00'], $data['nextOpeningTime']);

        self::assertCount(1, $data['orderingOptions']);
        self::assertSame('uber_eats', $data['orderingOptions'][0]['platform']);
        self::assertSame('Uber Eats', $data['orderingOptions'][0]['label']);
        self::assertSame('images/platforms/uber-eats.svg', $data['orderingOptions'][0]['logoPath']);

        self::assertCount(1, $data['images']);
        self::assertSame('https://cdn.test/uploads/restaurants/cover.jpg', $data['images'][0]['url']);
        self::assertSame('Cover', $data['images'][0]['altText']);

        self::assertSame('Alice', $data['submittedBy']['name']);
    }

    public function testDetailNextOpeningTimeNullWhenServiceReturnsNull(): void
    {
        $this->openingHours->method('isOpenNow')->willReturn(false);
        $this->openingHours->method('getNextOpeningTime')->willReturn(null);

        $data = $this->transformer()->detail(new Restaurant());

        self::assertNull($data['nextOpeningTime']);
        self::assertNull($data['submittedBy']);
    }

    public function testImageMapsToAbsoluteUrl(): void
    {
        $image = (new RestaurantImage())->setFilename('photo-1.jpg')->setAltText('Innen')->setSortOrder(2);

        $data = $this->transformer()->image($image);

        self::assertSame('https://cdn.test/uploads/restaurants/photo-1.jpg', $data['url']);
        self::assertSame('Innen', $data['altText']);
        self::assertSame(2, $data['sortOrder']);
    }
}
