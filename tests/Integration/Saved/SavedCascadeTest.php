<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 17, T01: Die Kaskade liegt in der Datenbank, nicht im Code (`design.md`,
 * Löschregeln). Gezählt wird mit rohem SQL — Doctrines Identity Map kennte gelöschte Zeilen
 * sonst noch und täuschte ein Ergebnis vor.
 */
final class SavedCascadeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $db;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->db = $this->em->getConnection();
    }

    public function testAk61KontoLoeschenNimmtFavoritenListenEintraegeUndNotizenMit(): void
    {
        $konto = $this->konto('kaskade-konto@example.test');
        $lokal = $this->lokal('Kaskade Konto');
        $listeId = $this->befuelle($konto, $lokal);
        $kontoId = $konto->getId();

        $this->em->remove($konto);
        $this->em->flush();
        $this->em->clear();

        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM favorite WHERE user_id = ?', $kontoId));
        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM saved_list WHERE user_id = ?', $kontoId));
        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', $listeId));
        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM restaurant_note WHERE user_id = ?', $kontoId));
    }

    public function testAk62LokalLoeschenNimmtAllesMitDasDaraufZeigt(): void
    {
        $konto = $this->konto('kaskade-lokal@example.test');
        $lokal = $this->lokal('Kaskade Lokal');
        $listeId = $this->befuelle($konto, $lokal);
        $lokalId = $lokal->getId();

        // Wie die Restaurantverwaltung (B20): `remove()` über den EntityManager.
        $this->em->remove($lokal);
        $this->em->flush();
        $this->em->clear();

        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM favorite WHERE restaurant_id = ?', $lokalId));
        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM saved_list_entry WHERE restaurant_id = ?', $lokalId));
        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM restaurant_note WHERE restaurant_id = ?', $lokalId));
        // Die Liste selbst bleibt — nur ihr Eintrag ist weg.
        self::assertSame(1, $this->zahl('SELECT COUNT(*) FROM saved_list WHERE id = ?', $listeId));
    }

    public function testAk30ListeLoeschenNimmtNurIhreEintraegeMit(): void
    {
        $konto = $this->konto('kaskade-liste@example.test');
        $lokal = $this->lokal('Kaskade Liste');
        $listeId = $this->befuelle($konto, $lokal);

        $this->em->remove($this->em->find(SavedList::class, $listeId));
        $this->em->flush();
        $this->em->clear();

        self::assertSame(0, $this->zahl('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', $listeId));
        self::assertSame(1, $this->zahl('SELECT COUNT(*) FROM favorite WHERE user_id = ?', $konto->getId()));
        self::assertSame(1, $this->zahl('SELECT COUNT(*) FROM restaurant_note WHERE user_id = ?', $konto->getId()));
    }

    public function testAk05ZweiterFavoritFuerDasselbePaarScheitertAmIndex(): void
    {
        $konto = $this->konto('kaskade-index@example.test');
        $lokal = $this->lokal('Kaskade Index');
        $this->em->persist(new Favorite($konto, $lokal));
        $this->em->flush();

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->persist(new Favorite($konto, $lokal));
        $this->em->flush();
    }

    private function befuelle(User $konto, Restaurant $lokal): int
    {
        $liste = new SavedList($konto, 'Mit Oma', 'mit oma');
        $this->em->persist(new Favorite($konto, $lokal));
        $this->em->persist($liste);
        $this->em->persist(new SavedListEntry($liste, $lokal));
        $this->em->persist(new RestaurantNote($konto, $lokal, 'Seiteneingang links'));
        $this->em->flush();

        return (int) $liste->getId();
    }

    private function zahl(string $sql, ?int $wert): int
    {
        return (int) $this->db->fetchOne($sql, [$wert]);
    }

    private function konto(string $email): User
    {
        $user = (new User())->setName('Kaskade')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function lokal(string $name): Restaurant
    {
        $restaurant = (new Restaurant())->setName($name)->setCity('Luxembourg');
        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }
}
