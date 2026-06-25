<?php

namespace App\Tests\Repository;

use App\Entity\Cuisine;
use App\Repository\CuisineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CuisineRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CuisineRepository $repo;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(CuisineRepository::class);
    }

    public function testFindOrCreateByNameReturnsExistingBySlug(): void
    {
        $existing = (new Cuisine())->setName('Vorhandene Kueche')->setSlug('vorhandene-kueche');
        $this->em->persist($existing);
        $this->em->flush();

        $before = $this->repo->count();
        $found = $this->repo->findOrCreateByName('Vorhandene Kueche');

        self::assertSame($existing->getId(), $found->getId());

        $this->em->flush();
        self::assertSame($before, $this->repo->count(), 'Es darf kein Duplikat angelegt werden.');
    }

    public function testFindOrCreateByNameCreatesNewWithAsciiSlug(): void
    {
        $cuisine = $this->repo->findOrCreateByName('Crêpes Spezial');

        // findOrCreateByName persistiert, flusht aber nicht selbst.
        self::assertNull($cuisine->getId());
        self::assertSame('Crêpes Spezial', $cuisine->getName());
        self::assertSame('crepes-spezial', $cuisine->getSlug());

        $this->em->flush();
        self::assertNotNull($cuisine->getId());
    }

    public function testFindOrCreateByNameIsIdempotent(): void
    {
        $name = 'Wiederholbar '.uniqid();

        $first = $this->repo->findOrCreateByName($name);
        $this->em->flush();
        $second = $this->repo->findOrCreateByName($name);

        self::assertSame($first->getId(), $second->getId());
    }

    public function testSearchMatchesByName(): void
    {
        $cuisine = (new Cuisine())->setName('Suchmarke ABCXYZ')->setSlug('suchmarke-'.uniqid());
        $this->em->persist($cuisine);
        $this->em->flush();

        $results = $this->repo->search('Suchmarke');

        self::assertNotEmpty($results);
        foreach ($results as $found) {
            self::assertStringContainsStringIgnoringCase('Suchmarke', $found->getName());
        }
    }

    public function testSearchRespectsLimit(): void
    {
        self::assertLessThanOrEqual(2, \count($this->repo->search('a', 2)));
    }

    public function testFindAllSortedIsAscending(): void
    {
        $this->em->persist((new Cuisine())->setName('ZZZ Sortprobe')->setSlug('zzz-sortprobe-'.uniqid()));
        $this->em->persist((new Cuisine())->setName('AAA Sortprobe')->setSlug('aaa-sortprobe-'.uniqid()));
        $this->em->flush();

        $names = array_map(static fn (Cuisine $c) => $c->getName(), $this->repo->findAllSorted());

        self::assertLessThan(
            array_search('ZZZ Sortprobe', $names, true),
            array_search('AAA Sortprobe', $names, true),
        );
    }
}
