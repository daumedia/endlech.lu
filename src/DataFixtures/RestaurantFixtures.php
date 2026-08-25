<?php

namespace App\DataFixtures;

use App\Entity\Cuisine;
use App\Entity\OpeningHour;
use App\Entity\OrderingOption;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\Language;
use App\Enum\OrderingPlatform;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RestaurantFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [UserFixtures::class, CuisineFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $restaurants = [
            [
                'name'                   => 'Pizzeria Bella Vista',
                'city'                   => 'Luxembourg-Ville',
                'cuisines'               => ['italienisch', 'pizza'],
                'emoji'                  => '🍕',
                'latitude'               => '49.61167200',
                'longitude'              => '6.13194400',
                'rating'                 => 9.8,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => false,
                'hasChangingTable'       => true,
                'hasDisabledParking'     => true,
                'doorWidthCm'            => 95,
                'tableSpacingCm'         => 100,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Eingang stufenlos', 'ok:WC Tür > 90cm'],
                'isVerified'             => true,
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN, Language::OTHER],
                'phone'                  => '+352 26 12 34 56',
                'email'                  => 'info@bellavista.lu',
                'website'                => 'https://www.bellavista.lu',
                'instagramUrl'           => 'https://instagram.com/bellavista.lu',
                'facebookUrl'            => 'https://facebook.com/bellavista.lu',
                'openingHours'           => [[1, '11:00', '22:00'], [2, '11:00', '22:00'], [3, '11:00', '22:00'], [4, '11:00', '22:00'], [5, '11:00', '22:00'], [6, '11:00', '22:00'], [7, '12:00', '21:00']],
                'orderingOptions'        => [
                    [OrderingPlatform::UBER_EATS, 'https://www.ubereats.com/lu/store/pizzeria-bella-vista'],
                    [OrderingPlatform::WEBSITE, 'https://www.bellavista.lu'],
                    [OrderingPlatform::PHONE, '+352 26 12 34 56'],
                ],
            ],
            [
                'name'                   => 'Umami Corner',
                'city'                   => 'Esch-Belval',
                'cuisines'               => ['asiatisch'],
                'emoji'                  => '🍜',
                'latitude'               => '49.50200000',
                'longitude'              => '5.94700000',
                'rating'                 => null,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'hasChangingTable'       => false,
                'hasDisabledParking'     => false,
                'doorWidthCm'            => 80,
                'tableSpacingCm'         => 85,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Menü in Braille', 'warn:Stufe am Eingang'],
                'spokenLanguages'        => [Language::EN, Language::FR],
                'phone'                  => '+352 27 44 55 66',
                'instagramUrl'           => 'https://instagram.com/umamicorner',
            ],
            [
                'name'                   => 'Burger & Co.',
                'city'                   => 'Dudelange',
                'cuisines'               => ['fast-food', 'burger'],
                'emoji'                  => '🍔',
                'latitude'               => '49.48050000',
                'longitude'              => '6.08780000',
                'rating'                 => 8.5,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => true,
                'hasChangingTable'       => true,
                'hasDisabledParking'     => true,
                'doorWidthCm'            => 90,
                'tableSpacingCm'         => 90,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => true,
                'accessibilityNotes'     => ['ok:Parkplatz vor der Tür'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 27 98 76 54',
                'email'                  => 'hello@burgerandco.lu',
                'website'                => 'https://www.burgerandco.lu',
                'instagramUrl'           => 'https://instagram.com/burgerandco.lu',
                'facebookUrl'            => 'https://facebook.com/burgerandco.lu',
                'tiktokUrl'              => 'https://tiktok.com/@burgerandco.lu',
                'openingHours'           => [[1, '11:00', '23:00'], [2, '11:00', '23:00'], [3, '11:00', '23:00'], [4, '11:00', '23:00'], [5, '11:00', '23:00'], [6, '11:00', '23:00'], [7, '11:00', '23:00']],
                'orderingOptions'        => [
                    [OrderingPlatform::JUST_EAT, 'https://www.just-eat.lu/menu/burger-and-co'],
                    [OrderingPlatform::PHONE, '+352 27 98 76 54'],
                ],
            ],
            [
                'name'                   => 'Le Jardin Brasserie',
                'city'                   => 'Kirchberg',
                'cuisines'               => ['franzosisch', 'mediterran'],
                'emoji'                  => '🥂',
                'latitude'               => '49.62800000',
                'longitude'              => '6.16100000',
                'rating'                 => 9.1,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => false,
                'hasChangingTable'       => true,
                'hasDisabledParking'     => true,
                'doorWidthCm'            => null,
                'tableSpacingCm'         => null,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => true,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Eingang stufenlos', 'ok:Barrierefreies WC', 'ok:Assistenzhunde willkommen'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN],
                'phone'                  => '+352 26 88 99 00',
                'email'                  => 'reservierung@lejardin.lu',
                'website'                => 'https://www.lejardin.lu',
                'facebookUrl'            => 'https://facebook.com/lejardinbrasserie',
                'openingHours'           => [[2, '12:00', '14:30'], [2, '18:00', '22:30'], [3, '12:00', '14:30'], [3, '18:00', '22:30'], [4, '12:00', '14:30'], [4, '18:00', '22:30'], [5, '12:00', '14:30'], [5, '18:00', '22:30'], [6, '12:00', '14:30'], [6, '18:00', '23:00'], [7, '12:00', '15:00']],
                'orderingOptions'        => [
                    [OrderingPlatform::WOLT, 'https://wolt.com/lu/restaurant/le-jardin-brasserie'],
                ],
            ],
            [
                'name'                   => 'Steakhaus Moselle',
                'city'                   => 'Grevenmacher',
                'cuisines'               => ['steak-grill'],
                'emoji'                  => '🥩',
                'latitude'               => '49.67900000',
                'longitude'              => '6.44100000',
                'rating'                 => 8.0,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => true,
                'hasChangingTable'       => false,
                'hasDisabledParking'     => false,
                'doorWidthCm'            => 85,
                'tableSpacingCm'         => null,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => true,
                'accessibilityNotes'     => ['warn:Zwei Stufen am Eingang', 'ok:Helle Innenbeleuchtung'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 75 11 22 33',
                'openingHours'           => [[2, '17:00', '23:00'], [3, '17:00', '23:00'], [4, '17:00', '23:00'], [5, '17:00', '23:00'], [6, '12:00', '14:30'], [6, '18:00', '23:30'], [7, '12:00', '14:30'], [7, '18:00', '21:00']],
            ],
            [
                'name'                   => 'Café Nordstad',
                'city'                   => 'Diekirch',
                'cuisines'               => ['cafe-bistro'],
                'emoji'                  => '☕',
                'latitude'               => '49.86800000',
                'longitude'              => '6.15900000',
                'rating'                 => 7.4,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'hasChangingTable'       => false,
                'hasDisabledParking'     => false,
                'doorWidthCm'            => null,
                'tableSpacingCm'         => null,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'isVegan'                => true,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Ebenerdiger Zugang', 'ok:Assistenzhunde erlaubt', 'warn:WC nicht barrierefrei'],
                'spokenLanguages'        => [Language::LU, Language::DE],
                'email'                  => 'hallo@cafenordstad.lu',
                'instagramUrl'           => 'https://instagram.com/cafenordstad',
            ],
            [
                'name'                   => 'Sushi Zen',
                'city'                   => 'Strassen',
                'cuisines'               => ['japanisch', 'sushi'],
                'emoji'                  => '🍣',
                'latitude'               => '49.62100000',
                'longitude'              => '6.07400000',
                'rating'                 => 9.4,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => false,
                'hasChangingTable'       => true,
                'hasDisabledParking'     => true,
                'doorWidthCm'            => 100,
                'tableSpacingCm'         => 110,
                'acceptsCash'            => false,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Vollständig barrierefrei', 'ok:Rollstuhlrampe vorhanden', 'ok:Barrierefreies WC'],
                'isVerified'             => true,
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN],
                'phone'                  => '+352 26 33 44 55',
                'email'                  => 'info@sushizen.lu',
                'website'                => 'https://www.sushizen.lu',
                'instagramUrl'           => 'https://instagram.com/sushizen.lu',
                'facebookUrl'            => 'https://facebook.com/sushizen.lu',
                'openingHours'           => [[1, '11:30', '22:00'], [2, '11:30', '22:00'], [3, '11:30', '22:00'], [4, '11:30', '22:00'], [5, '11:30', '23:00'], [6, '11:30', '23:00'], [7, '12:00', '21:00']],
                'orderingOptions'        => [
                    [OrderingPlatform::DELIVEROO, 'https://deliveroo.lu/menu/luxembourg/sushi-zen'],
                    [OrderingPlatform::JUST_EAT, 'https://www.just-eat.lu/menu/sushi-zen'],
                    [OrderingPlatform::GOOSTY, 'https://goosty.lu/sushi-zen'],
                ],
            ],
            [
                'name'                   => 'Wäinhaus am Markt',
                'city'                   => 'Remich',
                'cuisines'               => ['luxemburgisch'],
                'emoji'                  => '🍷',
                'latitude'               => '49.54500000',
                'longitude'              => '6.36700000',
                'rating'                 => 8.8,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => false,
                'hasChangingTable'       => false,
                'hasDisabledParking'     => false,
                'doorWidthCm'            => 75,
                'tableSpacingCm'         => 80,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => false,
                'accessibilityNotes'     => ['warn:Kopfsteinpflaster vor dem Eingang', 'warn:Treppen im Inneren'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 23 66 77 88',
                'website'                => 'https://www.wainhaus.lu',
                'facebookUrl'            => 'https://facebook.com/wainhausammarkt',
                'openingHours'           => [[2, '18:00', '01:00'], [3, '18:00', '01:00'], [4, '18:00', '01:00'], [5, '18:00', '01:00'], [6, '18:00', '01:00']],
            ],
            [
                'name'                   => 'Trattoria Roma',
                'city'                   => 'Ettelbruck',
                'cuisines'               => ['italienisch', 'pizza'],
                'emoji'                  => '🍝',
                'latitude'               => '49.84700000',
                'longitude'              => '6.10400000',
                'rating'                 => 8.2,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'hasChangingTable'       => false,
                'hasDisabledParking'     => false,
                'doorWidthCm'            => 92,
                'tableSpacingCm'         => 95,
                'acceptsCash'            => true,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => false,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Ebenerdiger Eingang', 'ok:Helle Beleuchtung im Gastraum'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::PT, Language::OTHER],
                'phone'                  => '+352 81 22 33 44',
                'email'                  => 'ciao@trattoriaroma.lu',
                'website'                => 'https://www.trattoriaroma.lu',
                'instagramUrl'           => 'https://instagram.com/trattoriaroma.lu',
                'openingHours'           => [[1, '12:00', '14:30'], [1, '18:30', '23:00'], [2, '12:00', '14:30'], [2, '18:30', '23:00'], [3, '12:00', '14:30'], [3, '18:30', '23:00'], [4, '12:00', '14:30'], [4, '18:30', '23:00'], [5, '12:00', '14:30'], [5, '18:30', '23:30'], [6, '18:30', '23:30'], [7, '12:00', '15:00']],
                'orderingOptions'        => [
                    [OrderingPlatform::WEDELY, 'https://wedely.com/trattoria-roma'],
                ],
            ],
            [
                'name'                   => 'Green Bowl',
                'city'                   => 'Cloche d\'Or',
                'cuisines'               => ['vegetarisch', 'vegan'],
                'emoji'                  => '🥗',
                'latitude'               => '49.58300000',
                'longitude'              => '6.12500000',
                'rating'                 => 9.0,
                'isWheelchairAccessible' => true,
                'hasAccessibleToilet'    => true,
                'allowsAssistanceDogs'   => true,
                'hasBrightLighting'      => true,
                'hasChangingTable'       => true,
                'hasDisabledParking'     => true,
                'doorWidthCm'            => 110,
                'tableSpacingCm'         => 120,
                'acceptsCash'            => false,
                'acceptsCard'            => true,
                'acceptsPayconiq'        => true,
                'isVegan'                => true,
                'isVegetarian'           => true,
                'isHalal'                => false,
                'accessibilityNotes'     => ['ok:Vollständig barrierefrei', 'ok:Induktive Höranlage vorhanden'],
                'isVerified'             => true,
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR, Language::EN, Language::PT],
                'email'                  => 'hello@greenbowl.lu',
                'website'                => 'https://www.greenbowl.lu',
                'instagramUrl'           => 'https://instagram.com/greenbowl.lu',
                'facebookUrl'            => 'https://facebook.com/greenbowl.lu',
                'tiktokUrl'              => 'https://tiktok.com/@greenbowl.lu',
                'openingHours'           => [[1, '10:00', '20:00'], [2, '10:00', '20:00'], [3, '10:00', '20:00'], [4, '10:00', '20:00'], [5, '10:00', '20:00'], [6, '10:00', '18:00']],
                'orderingOptions'        => [
                    [OrderingPlatform::UBER_EATS, 'https://www.ubereats.com/lu/store/green-bowl'],
                    [OrderingPlatform::WEBSITE, 'https://www.greenbowl.lu/order'],
                ],
            ],
            [
                'name'                   => 'Brasserie du Grund',
                'city'                   => 'Luxembourg-Grund',
                'cuisines'               => ['luxemburgisch'],
                'emoji'                  => '🍺',
                'latitude'               => '49.60800000',
                'longitude'              => '6.13400000',
                'nearbyStopsNote'        => 'Haltestelle "Grund" (Linie 23) ca. 200m entfernt. Achtung: steiler Weg vom Plateau hinunter.',
                'rating'                 => 7.9,
                'isWheelchairAccessible' => false,
                'hasAccessibleToilet'    => false,
                'allowsAssistanceDogs'   => false,
                'hasBrightLighting'      => false,
                'hasChangingTable'       => false,
                'hasDisabledParking'     => false,
                'doorWidthCm'            => null,
                'tableSpacingCm'         => 90,
                'acceptsCash'            => true,
                'acceptsCard'            => false,
                'acceptsPayconiq'        => false,
                'isVegan'                => false,
                'isVegetarian'           => false,
                'isHalal'                => false,
                'accessibilityNotes'     => ['warn:Historisches Gebäude, mehrere Stufen', 'warn:Keine barrierefreie Toilette'],
                'spokenLanguages'        => [Language::LU, Language::DE, Language::FR],
                'phone'                  => '+352 46 11 22 33',
            ],
        ];

        // Zuordnung: Restaurant-Name → Submitter-Referenz
        $submitterMap = [
            'Pizzeria Bella Vista' => UserFixtures::REFERENCE_ADMIN,
            'Sushi Zen'            => UserFixtures::REFERENCE_ADMIN,
            'Green Bowl'           => UserFixtures::REFERENCE_ADMIN,
            'Umami Corner'         => UserFixtures::REFERENCE_USER,
            'Burger & Co.'         => UserFixtures::REFERENCE_USER,
            'Café Nordstad'        => UserFixtures::REFERENCE_USER,
        ];

        foreach ($restaurants as $data) {
            $restaurant = new Restaurant();
            $restaurant->setName($data['name']);
            $restaurant->setCity($data['city']);
            foreach ($data['cuisines'] ?? [] as $cuisineSlug) {
                $restaurant->addCuisine($this->getReference('cuisine_' . $cuisineSlug, Cuisine::class));
            }
            $restaurant->setEmoji($data['emoji']);
            $restaurant->setRating($data['rating']);
            $restaurant->setIsWheelchairAccessible($data['isWheelchairAccessible']);
            $restaurant->setHasAccessibleToilet($data['hasAccessibleToilet']);
            $restaurant->setAllowsAssistanceDogs($data['allowsAssistanceDogs']);
            $restaurant->setHasBrightLighting($data['hasBrightLighting']);
            $restaurant->setHasChangingTable($data['hasChangingTable']);
            $restaurant->setHasDisabledParking($data['hasDisabledParking']);
            $restaurant->setDoorWidthCm($data['doorWidthCm'] ?? null);
            $restaurant->setTableSpacingCm($data['tableSpacingCm'] ?? null);
            $restaurant->setAcceptsCash($data['acceptsCash']);
            $restaurant->setAcceptsCard($data['acceptsCard']);
            $restaurant->setAcceptsPayconiq($data['acceptsPayconiq']);
            $restaurant->setIsVegan($data['isVegan']);
            $restaurant->setIsVegetarian($data['isVegetarian']);
            $restaurant->setIsHalal($data['isHalal']);
            $restaurant->setAccessibilityNotes($data['accessibilityNotes']);
            // BF-49/BF-67: Die elf Häuser stammen aus Recherche des Teams — dort
            // wurde hingesehen, auch wo das Ergebnis „nein" war. Ohne diese Zeile
            // wäre der gesamte Fixture-Bestand „nicht bewertet", und die
            // Durchschnittspunktzahl stützte sich auf null Häuser.
            $restaurant->setAssessedFeatures(Restaurant::assessableFeatures());
            $restaurant->setSpokenLanguages($data['spokenLanguages'] ?? []);
            $restaurant->setPhone($data['phone'] ?? null);
            $restaurant->setEmail($data['email'] ?? null);
            $restaurant->setWebsite($data['website'] ?? null);
            $restaurant->setInstagramUrl($data['instagramUrl'] ?? null);
            $restaurant->setFacebookUrl($data['facebookUrl'] ?? null);
            $restaurant->setTiktokUrl($data['tiktokUrl'] ?? null);
            $restaurant->setLatitude($data['latitude'] ?? null);
            $restaurant->setLongitude($data['longitude'] ?? null);
            $restaurant->setNearbyStopsNote($data['nearbyStopsNote'] ?? null);

            $isVerified = $data['isVerified'] ?? false;
            $restaurant->setIsVerified($isVerified);
            if ($isVerified) {
                $restaurant->setVerifiedAt(new \DateTimeImmutable('2026-01-15'));
                $restaurant->setVerifiedBy($this->getReference(UserFixtures::REFERENCE_ADMIN, User::class));
            }

            if (isset($submitterMap[$data['name']])) {
                $restaurant->setSubmittedBy($this->getReference($submitterMap[$data['name']], User::class));
            }

            foreach ($data['orderingOptions'] ?? [] as [$platform, $url]) {
                $option = new OrderingOption();
                $option->setPlatform($platform);
                $option->setUrl($url);
                $restaurant->addOrderingOption($option);
            }

            foreach ($data['openingHours'] ?? [] as [$day, $open, $close]) {
                $oh = new OpeningHour();
                $oh->setDayOfWeek($day);
                $oh->setOpenTime(new \DateTime($open));
                $oh->setCloseTime(new \DateTime($close));
                $restaurant->addOpeningHour($oh);
            }

            $manager->persist($restaurant);
        }

        $manager->flush();
    }
}
