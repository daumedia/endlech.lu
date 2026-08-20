<?php

namespace App\Api;

use App\Entity\OpeningHour;
use App\Entity\OrderingOption;
use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Entity\User;
use App\Service\OpeningHoursService;

/**
 * Wandelt Restaurant-Entities in JSON-serialisierbare Arrays für die /api/v1-Endpunkte.
 *
 * Bewusst explizit (statt Serializer-Groups), weil die Entity untypische Getter hat
 * (acceptsCash(), isWheelchairAccessible(), hasAccessibleToilet()), die der
 * ObjectNormalizer nicht zuverlässig erkennt. Feldnamen sind so voll kontrolliert.
 */
final class RestaurantTransformer
{
    public function __construct(
        private readonly OpeningHoursService $openingHours,
        private readonly AssetUrlBuilder $assetUrlBuilder,
    ) {
    }

    /**
     * Kompakte Darstellung für Listen-Endpunkte.
     *
     * @return array<string, mixed>
     */
    public function list(Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'city' => $restaurant->getCity(),
            'emoji' => $restaurant->getEmoji(),
            'rating' => $restaurant->getRating(),
            'isVerified' => $restaurant->isVerified(),
            'coverImageUrl' => $this->coverImageUrl($restaurant),
            'cuisines' => $this->cuisines($restaurant),
            'accessibility' => $this->accessibility($restaurant),
            'dietary' => $this->dietary($restaurant),
            'isOpenNow' => $this->openingHours->isOpenNow($restaurant),
            'createdAt' => $restaurant->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Vollständige Darstellung für den Detail-Endpunkt inkl. aller Relationen.
     *
     * @return array<string, mixed>
     */
    public function detail(Restaurant $restaurant): array
    {
        $next = $this->openingHours->getNextOpeningTime($restaurant);

        return array_merge($this->list($restaurant), [
            'payment' => [
                'cash' => $restaurant->acceptsCash(),
                'card' => $restaurant->acceptsCard(),
                'payconiq' => $restaurant->acceptsPayconiq(),
            ],
            'contact' => [
                'phone' => $restaurant->getPhone(),
                'email' => $restaurant->getEmail(),
                'website' => $restaurant->getWebsite(),
                'instagramUrl' => $restaurant->getInstagramUrl(),
                'facebookUrl' => $restaurant->getFacebookUrl(),
                'tiktokUrl' => $restaurant->getTiktokUrl(),
            ],
            'location' => [
                'latitude' => $restaurant->getLatitude(),
                'longitude' => $restaurant->getLongitude(),
                'nearbyStopsNote' => $restaurant->getNearbyStopsNote(),
            ],
            'measurements' => $this->measurements($restaurant),
            'accessibilityNotes' => $restaurant->getAccessibilityNotes(),
            'spokenLanguages' => array_map(
                static fn ($lang) => [
                    'code' => $lang->value,
                    'label' => $lang->label(),
                    'flag' => $lang->flag(),
                ],
                $restaurant->getSpokenLanguages(),
            ),
            'openingHours' => $this->openingHoursByDay($restaurant),
            'nextOpeningTime' => $next === null ? null : [
                'dayOfWeek' => $next['dayOfWeek'],
                'time' => $next['time']->format('H:i'),
            ],
            'orderingOptions' => array_map(
                fn (OrderingOption $option) => $this->orderingOption($option),
                $restaurant->getOrderingOptions()->toArray(),
            ),
            'images' => array_map(
                fn (RestaurantImage $image) => $this->image($image),
                $restaurant->getImages()->toArray(),
            ),
            'submittedBy' => $this->user($restaurant->getSubmittedBy()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function image(RestaurantImage $image): array
    {
        return [
            'id' => $image->getId(),
            'url' => $this->assetUrlBuilder->absolute('/uploads/restaurants/' . $image->getFilename()),
            'altText' => $image->getAltText(),
            'sortOrder' => $image->getSortOrder(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cuisines(Restaurant $restaurant): array
    {
        return array_values(array_map(
            static fn ($cuisine) => [
                'id' => $cuisine->getId(),
                'name' => $cuisine->getName(),
                'slug' => $cuisine->getSlug(),
            ],
            $restaurant->getCuisines()->toArray(),
        ));
    }

    /**
     * @return array<string, bool>
     */
    private function accessibility(Restaurant $restaurant): array
    {
        return [
            'wheelchairAccessible' => $restaurant->isWheelchairAccessible(),
            'accessibleToilet' => $restaurant->hasAccessibleToilet(),
            'assistanceDogs' => $restaurant->allowsAssistanceDogs(),
            'brightLighting' => $restaurant->hasBrightLighting(),
            'changingTable' => $restaurant->hasChangingTable(),
            'disabledParking' => $restaurant->hasDisabledParking(),
        ];
    }

    /**
     * Maße in Zentimetern, jeweils mit der abgeleiteten Ja/Nein-Aussage.
     *
     * Bewusst ein eigener Block statt zusätzlicher Schlüssel in
     * `accessibility`: Dort ist jeder Wert ein Boolean, und der Client
     * verlässt sich darauf. Ein `null` in diesem Vertrag wäre ein
     * Kompatibilitätsbruch, ein neues Feld daneben ist keiner.
     *
     * @return array<string, int|bool|null>
     */
    private function measurements(Restaurant $restaurant): array
    {
        return [
            'doorWidthCm' => $restaurant->getDoorWidthCm(),
            'tableSpacingCm' => $restaurant->getTableSpacingCm(),
            // null heißt "nicht ausgemessen" – der Client soll das von
            // "zu schmal" unterscheiden können.
            'wideDoors' => $restaurant->hasWideDoors(),
            'wheelchairTableSpacing' => $restaurant->hasWheelchairTableSpacing(),
            'minDoorWidthCm' => Restaurant::MIN_DOOR_WIDTH_CM,
            'minTableSpacingCm' => Restaurant::MIN_TABLE_SPACING_CM,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function dietary(Restaurant $restaurant): array
    {
        return [
            'vegan' => $restaurant->isVegan(),
            'vegetarian' => $restaurant->isVegetarian(),
            'halal' => $restaurant->isHalal(),
        ];
    }

    /**
     * Öffnungszeiten gruppiert nach Wochentag (1=Montag … 7=Sonntag).
     * Tage ohne Slots gelten als geschlossen (leeres slots-Array).
     *
     * @return list<array{dayOfWeek: int, slots: list<array{open: ?string, close: ?string}>}>
     */
    private function openingHoursByDay(Restaurant $restaurant): array
    {
        $days = [];
        for ($day = 1; $day <= 7; ++$day) {
            $slots = array_map(
                static fn (OpeningHour $slot) => [
                    'open' => $slot->getOpenTime()?->format('H:i'),
                    'close' => $slot->getCloseTime()?->format('H:i'),
                ],
                $restaurant->getOpeningHoursForDay($day),
            );

            $days[] = [
                'dayOfWeek' => $day,
                'slots' => array_values($slots),
            ];
        }

        return $days;
    }

    /**
     * @return array<string, mixed>
     */
    private function orderingOption(OrderingOption $option): array
    {
        $enum = $option->getPlatformEnum();

        return [
            'platform' => $option->getPlatform(),
            'label' => $enum?->label() ?? $option->getPlatform(),
            'emoji' => $enum?->emoji(),
            'actionLabel' => $enum?->actionLabel(),
            'logoPath' => $enum?->logoPath(),
            'url' => $option->getUrl(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function user(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
        ];
    }

    private function coverImageUrl(Restaurant $restaurant): ?string
    {
        $cover = $restaurant->getCoverImage();

        return $cover === null
            ? null
            : $this->assetUrlBuilder->absolute('/uploads/restaurants/' . $cover->getFilename());
    }
}
