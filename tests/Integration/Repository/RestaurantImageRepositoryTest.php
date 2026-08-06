<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Repository\RestaurantImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RestaurantImageRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RestaurantImageRepository $repo;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(RestaurantImageRepository::class);
    }

    private function persistRestaurant(): Restaurant
    {
        $restaurant = (new Restaurant())->setName('Bild Probe')->setCity('Luxembourg');
        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }

    public function testNextSortOrderIsOneForEmptyRestaurant(): void
    {
        // MAX(sortOrder) ist NULL => 0, +1 => 1 (erstes Bild erhält sortOrder 1).
        self::assertSame(1, $this->repo->getNextSortOrder($this->persistRestaurant()));
    }

    public function testNextSortOrderIsMaxPlusOne(): void
    {
        $restaurant = $this->persistRestaurant();

        foreach ([1, 4] as $order) {
            $image = (new RestaurantImage())->setFilename('img-'.$order.'.jpg')->setSortOrder($order);
            $image->setRestaurant($restaurant);
            $this->em->persist($image);
        }
        $this->em->flush();

        self::assertSame(5, $this->repo->getNextSortOrder($restaurant));
    }
}
