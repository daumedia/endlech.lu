<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Cuisine;
use App\Repository\CuisineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * BF-62: Ein Slug ist nicht so lang wie sein Name.
     *
     * `AsciiSlugger` macht aus „ß" ein „ss", aus einem japanischen Zeichen bis zu
     * drei Buchstaben. Gemessen: 80 × „ß" ergaben 160 Zeichen Slug, 80 × „日"
     * ergaben 239 — beides zu lang für VARCHAR(100), und beides landete als
     * `SQLSTATE[22001]` in einem 500er.
     */
    #[DataProvider('ausdehnendeZeichen')]
    public function testSlugPasstImmerInDieSpalte(string $zeichen): void
    {
        $cuisine = $this->repo->findOrCreateByName(str_repeat($zeichen, 80));
        $this->em->flush();

        self::assertLessThanOrEqual(80, mb_strlen($cuisine->getName()));
        self::assertLessThanOrEqual(100, mb_strlen($cuisine->getSlug()));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function ausdehnendeZeichen(): iterable
    {
        yield 'scharfes s wird zu ss' => ['ß'];
        yield 'ae-Ligatur wird zu ae' => ['Æ'];
        yield 'oe-Ligatur wird zu oe' => ['œ'];
        yield 'japanisch wird transkribiert' => ['日'];
        yield 'umlaut bleibt einstellig' => ['Ä'];
    }

    public function testNameWirdAufDieSpaltenbreiteGekuerzt(): void
    {
        $cuisine = $this->repo->findOrCreateByName(str_repeat('K', 200));
        $this->em->flush();

        self::assertSame(80, mb_strlen($cuisine->getName()));
    }

    /**
     * Ein Name aus reinen Sonderzeichen hinterlässt keinen Slug — und ein leerer
     * Slug wäre über die Unique-Regel nur einmal möglich.
     */
    public function testNameOhneSlugfaehigeZeichenBekommtEinenErsatzslug(): void
    {
        $cuisine = $this->repo->findOrCreateByName('!!!');
        $this->em->flush();

        self::assertNotSame('', $cuisine->getSlug());
        self::assertSame('!!!', $cuisine->getName());
    }
}
