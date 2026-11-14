<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use App\Saved\FavoriteService;
use App\Saved\SavedWriteLock;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * Feature 17, T05: Das Herz hat eine Absicht — „merken“ oder „entfernen“ —, keinen Umschalter
 * (`design.md`, Technische Entscheidung; AK-05). Eine veraltete Seite darf ein gemerktes Lokal
 * nicht aus Versehen wieder herausnehmen.
 */
final class FavoriteServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $db;
    private FavoriteService $service;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->db = $this->em->getConnection();
        // Direkt gebaut: Bis die Controller (Ebene 3) den Dienst benutzen, entfernt der
        // Container ihn als unbenutzt.
        $this->service = new FavoriteService(
            $this->em,
            $this->em->getRepository(Favorite::class),
            new SavedWriteLock(new LockFactory(new InMemoryStore())),
        );
    }

    public function testAk01MerkenLegtGenauEineZeileAn(): void
    {
        [$konto, $lokal] = $this->paar('fav-merken@example.test');

        self::assertTrue($this->service->add($konto, $lokal));
        self::assertSame(1, $this->zahl($konto, $lokal));
    }

    public function testAk05MerkenAufGemerktesAendertNichtsUndMachtKeineZweiteZeile(): void
    {
        [$konto, $lokal] = $this->paar('fav-doppelt@example.test');
        $this->service->add($konto, $lokal);

        self::assertFalse($this->service->add($konto, $lokal), 'Zweites „merken“ ist keine Änderung (zählt nicht gegen den Deckel).');
        self::assertSame(1, $this->zahl($konto, $lokal));
    }

    public function testAk03EntfernenNimmtDieZeileWeg(): void
    {
        [$konto, $lokal] = $this->paar('fav-entfernen@example.test');
        $this->service->add($konto, $lokal);

        self::assertTrue($this->service->remove($konto, $lokal));
        self::assertSame(0, $this->zahl($konto, $lokal));
    }

    public function testAk05EntfernenAufEntferntesIstRuhigUndKeineAenderung(): void
    {
        [$konto, $lokal] = $this->paar('fav-weg@example.test');

        self::assertFalse($this->service->remove($konto, $lokal));
        self::assertSame(0, $this->zahl($konto, $lokal));
    }

    public function testAk03EntfernenLaesstListeneintragUndNotizStehen(): void
    {
        [$konto, $lokal] = $this->paar('fav-rest@example.test');
        $liste = new SavedList($konto, 'Kirchberg', 'kirchberg');
        $this->em->persist($liste);
        $this->em->persist(new SavedListEntry($liste, $lokal));
        $this->em->persist(new RestaurantNote($konto, $lokal, 'Seiteneingang'));
        $this->em->flush();
        $this->service->add($konto, $lokal);

        $this->service->remove($konto, $lokal);

        self::assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', [$liste->getId()]));
        self::assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM restaurant_note WHERE user_id = ?', [$konto->getId()]));
    }

    public function testAk04FremdesKontoBleibtUnberuehrt(): void
    {
        [$a, $lokal] = $this->paar('fav-a@example.test');
        [$b] = $this->paar('fav-b@example.test');
        $this->service->add($a, $lokal);

        self::assertFalse($this->service->remove($b, $lokal));
        self::assertSame(1, $this->zahl($a, $lokal));
    }

    private function zahl(User $konto, Restaurant $lokal): int
    {
        return (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?',
            [$konto->getId(), $lokal->getId()],
        );
    }

    /**
     * @return array{User, Restaurant}
     */
    private function paar(string $email): array
    {
        $konto = (new User())->setName('Fav')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $lokal = (new Restaurant())->setName('Fav '.$email)->setCity('Luxembourg');
        $this->em->persist($konto);
        $this->em->persist($lokal);
        $this->em->flush();

        return [$konto, $lokal];
    }
}
