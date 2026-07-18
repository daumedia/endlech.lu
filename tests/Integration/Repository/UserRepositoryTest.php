<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repo;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(UserRepository::class);
    }

    private function persistUser(string $email): User
    {
        $user = (new User())->setName('Test')->setEmail($email)->setPassword('hashed');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testFindByVerificationToken(): void
    {
        $token = 'tok_'.uniqid();
        $user = $this->persistUser('token_'.uniqid().'@endlech.lu');
        $user->setVerificationToken($token);
        $this->em->flush();

        self::assertSame($user->getId(), $this->repo->findByVerificationToken($token)?->getId());
        self::assertNull($this->repo->findByVerificationToken('nicht-vorhanden'));
    }

    public function testCountRegisteredSince(): void
    {
        self::assertSame(0, $this->repo->countRegisteredSince(new \DateTimeImmutable('+1 day')));
        self::assertGreaterThanOrEqual(3, $this->repo->countRegisteredSince(new \DateTimeImmutable('-10 years')));
    }

    public function testFindRecentReturnsNewestFirst(): void
    {
        $fresh = $this->persistUser('frisch_'.uniqid().'@endlech.lu');

        $recent = $this->repo->findRecent(1);

        self::assertCount(1, $recent);
        self::assertSame($fresh->getId(), $recent[0]->getId());
    }
}
