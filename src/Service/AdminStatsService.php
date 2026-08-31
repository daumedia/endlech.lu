<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\BoardIdeaRepository;
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
        private readonly BoardIdeaRepository $boardIdeaRepository,
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

    /**
     * Wartende Ideen auf dem Community-Board (Feature 06, AK-25).
     *
     * ⚠ Der Zähler ist hier der einzige Hinweis auf die Warteschlange: Für neue
     * Einreichungen wurde bewusst keine interne Meldung gewählt (Decision Log 8).
     * Wer ihn aus dem Dashboard entfernt, kappt die einzige Sichtbarkeit.
     */
    public function getPendingBoardIdeaCount(): int
    {
        return $this->boardIdeaRepository->countAwaitingReview();
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
