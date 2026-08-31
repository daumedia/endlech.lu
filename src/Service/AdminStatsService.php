<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantImageRepository;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use App\Repository\UserRepository;

class AdminStatsService
{
    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly UserRepository $userRepository,
        private readonly RestaurantImageRepository $imageRepository,
        private readonly RestaurantSuggestionRepository $suggestionRepository,
    ) {
    }

    public function getRestaurantCount(): int
    {
        return $this->restaurantRepository->count();
    }

    public function getVerifiedCount(): int
    {
        return $this->restaurantRepository->countVerified();
    }

    public function getPendingSuggestionCount(): int
    {
        return $this->suggestionRepository->countPending();
    }

    public function getUserCount(): int
    {
        return $this->userRepository->count();
    }

    public function getImageCount(): int
    {
        return $this->imageRepository->count();
    }

    public function getRestaurantsAddedThisMonth(): int
    {
        $firstOfMonth = new \DateTimeImmutable('first day of this month midnight');

        return $this->restaurantRepository->countCreatedSince($firstOfMonth);
    }

    public function getUsersRegisteredThisMonth(): int
    {
        $firstOfMonth = new \DateTimeImmutable('first day of this month midnight');

        return $this->userRepository->countRegisteredSince($firstOfMonth);
    }

    /**
     * @return Restaurant[]
     */
    public function getRecentRestaurants(int $limit = 5): array
    {
        return $this->restaurantRepository->findRecent($limit);
    }

    /**
     * @return User[]
     */
    public function getRecentUsers(int $limit = 5): array
    {
        return $this->userRepository->findRecent($limit);
    }
}
