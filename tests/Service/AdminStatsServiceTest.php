<?php

namespace App\Tests\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantImageRepository;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use App\Repository\UserRepository;
use App\Service\AdminStatsService;
use PHPUnit\Framework\TestCase;

final class AdminStatsServiceTest extends TestCase
{
    public function testAggregatesDelegateToRepositories(): void
    {
        $restaurants = $this->createStub(RestaurantRepository::class);
        $restaurants->method('count')->willReturn(11);
        $restaurants->method('countVerified')->willReturn(3);

        $users = $this->createStub(UserRepository::class);
        $users->method('count')->willReturn(5);

        $images = $this->createStub(RestaurantImageRepository::class);
        $images->method('count')->willReturn(7);

        $suggestions = $this->createStub(RestaurantSuggestionRepository::class);
        $suggestions->method('countPending')->willReturn(4);

        $service = new AdminStatsService($restaurants, $users, $images, $suggestions);

        self::assertSame(11, $service->getRestaurantCount());
        self::assertSame(3, $service->getVerifiedCount());
        self::assertSame(5, $service->getUserCount());
        self::assertSame(7, $service->getImageCount());
        self::assertSame(4, $service->getPendingSuggestionCount());
    }

    public function testThisMonthCountersUseFirstDayOfMonth(): void
    {
        $restaurants = $this->createMock(RestaurantRepository::class);
        $restaurants->expects(self::once())
            ->method('countCreatedSince')
            ->with(self::callback(static function (\DateTimeImmutable $since): bool {
                return $since->format('d') === '01' && $since->format('H:i:s') === '00:00:00';
            }))
            ->willReturn(2);

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())
            ->method('countRegisteredSince')
            ->with(self::isInstanceOf(\DateTimeImmutable::class))
            ->willReturn(1);

        $service = new AdminStatsService(
            $restaurants,
            $users,
            $this->createStub(RestaurantImageRepository::class),
            $this->createStub(RestaurantSuggestionRepository::class),
        );

        self::assertSame(2, $service->getRestaurantsAddedThisMonth());
        self::assertSame(1, $service->getUsersRegisteredThisMonth());
    }

    public function testRecentListsAreForwardedWithLimit(): void
    {
        $recentRestaurants = [new Restaurant(), new Restaurant()];
        $recentUsers = [new User()];

        $restaurants = $this->createMock(RestaurantRepository::class);
        $restaurants->expects(self::once())->method('findRecent')->with(5)->willReturn($recentRestaurants);

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findRecent')->with(3)->willReturn($recentUsers);

        $service = new AdminStatsService(
            $restaurants,
            $users,
            $this->createStub(RestaurantImageRepository::class),
            $this->createStub(RestaurantSuggestionRepository::class),
        );

        self::assertSame($recentRestaurants, $service->getRecentRestaurants(5));
        self::assertSame($recentUsers, $service->getRecentUsers(3));
    }
}
