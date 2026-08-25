<?php

namespace App\Repository;

use App\Entity\Cuisine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;

/**
 * @extends ServiceEntityRepository<Cuisine>
 */
class CuisineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cuisine::class);
    }

    /**
     * @return Cuisine[]
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Cuisine[]
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Spaltenbreiten aus dem Mapping — `name` VARCHAR(80), `slug` VARCHAR(100).
     */
    private const MAX_NAME_LENGTH = 80;
    private const MAX_SLUG_LENGTH = 100;

    public function findOrCreateByName(string $name): Cuisine
    {
        $name = mb_substr(trim($name), 0, self::MAX_NAME_LENGTH);
        $slug = $this->buildSlug($name);

        $existing = $this->findOneBy(['slug' => $slug]);
        if ($existing !== null) {
            return $existing;
        }

        $cuisine = new Cuisine();
        $cuisine->setName($name);
        $cuisine->setSlug($slug);

        $this->getEntityManager()->persist($cuisine);

        return $cuisine;
    }

    /**
     * Erzeugt einen Slug, der garantiert in die Spalte passt.
     *
     * ⚠ BF-62: Ein Slug ist NICHT so lang wie sein Name. `AsciiSlugger` macht aus
     * „ß" ein „ss", aus „Æ" ein „ae" und aus einem japanischen Zeichen bis zu drei
     * Buchstaben — gemessen: 80 × „ß" ergeben 160 Zeichen, 80 × „日" ergeben 239.
     * Die Längenprüfung am Endpunkt fing deshalb nur den halben Fehler ab: Der
     * `SQLSTATE[22001]` wanderte von der Spalte `name` auf `slug`.
     *
     * Bei Kürzung kann ein Slug mit einem bestehenden kollidieren; dann hängt ein
     * Zähler an. Der Aufrufer bekommt in dem Fall einen neuen Eintrag und nicht
     * versehentlich einen fremden — bei einem Küchen-Typ ist das der Unterschied
     * zwischen „zwei ähnliche Einträge" und „falsch zugeordnete Restaurants".
     */
    private function buildSlug(string $name): string
    {
        $basis = strtolower((string) new UnicodeString((new AsciiSlugger())->slug($name)));
        $basis = trim(mb_substr($basis, 0, self::MAX_SLUG_LENGTH), '-');

        if ('' === $basis) {
            // Ein Name aus reinen Sonderzeichen hinterlässt keinen Slug.
            $basis = 'kueche';
        }

        $slug = $basis;
        $zaehler = 2;
        while (null !== $this->findOneBy(['slug' => $slug]) && $this->nameOf($slug) !== $name) {
            $anhang = '-'.$zaehler;
            $slug = mb_substr($basis, 0, self::MAX_SLUG_LENGTH - mb_strlen($anhang)).$anhang;
            ++$zaehler;
        }

        return $slug;
    }

    private function nameOf(string $slug): ?string
    {
        return $this->findOneBy(['slug' => $slug])?->getName();
    }
}
