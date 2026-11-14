<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\User;
use App\Saved\RestaurantNoteService;
use App\Saved\SavedWriteLock;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * Feature 17, T07: eine private Notiz je Konto und Lokal; leer heißt „keine Zeile“.
 */
final class RestaurantNoteServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $db;
    private RestaurantNoteService $service;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->db = $this->em->getConnection();
        $this->service = new RestaurantNoteService(
            $this->em,
            $this->em->getRepository(RestaurantNote::class),
            new SavedWriteLock(new LockFactory(new InMemoryStore())),
        );
    }

    public function testAk33SpeichernLegtDieNotizAn(): void
    {
        [$konto, $lokal] = $this->paar('notiz-an@example.test');

        self::assertSame(RestaurantNoteService::SAVED, $this->service->save($konto, $lokal, '  Seiteneingang links, Rampe steil '));
        self::assertSame('Seiteneingang links, Rampe steil', $this->text($konto, $lokal));
    }

    public function testAk35FuenfhundertZeichenMitSechsWindowsUmbruechenPassen(): void
    {
        [$konto, $lokal] = $this->paar('notiz-500@example.test');
        // 494 Buchstaben + 6 Umbrüche = 500 Zeichen; roh mit \r\n wären es 506.
        $roh = implode("\r\n", str_split(str_repeat('a', 494), 82)).'b';
        $roh = substr($roh, 0, -1);
        self::assertSame(506, \strlen($roh));

        self::assertSame(RestaurantNoteService::SAVED, $this->service->save($konto, $lokal, $roh));
        self::assertSame(500, mb_strlen((string) $this->text($konto, $lokal)));
    }

    public function testAk35FuenfhundertEinsWirdAbgewiesenUndDieAlteNotizBleibt(): void
    {
        [$konto, $lokal] = $this->paar('notiz-501@example.test');
        $this->service->save($konto, $lokal, 'alt');

        self::assertSame(RestaurantNoteService::TOO_LONG, $this->service->save($konto, $lokal, str_repeat('x', 501)));
        self::assertSame('alt', $this->text($konto, $lokal));
    }

    public function testAk36LeerenLoeschtDieZeile(): void
    {
        [$konto, $lokal] = $this->paar('notiz-leer@example.test');
        $this->service->save($konto, $lokal, 'weg damit');

        self::assertSame(RestaurantNoteService::DELETED, $this->service->save($konto, $lokal, ''));
        self::assertNull($this->text($konto, $lokal));
    }

    public function testEc08NurLeerraumGiltAlsLeer(): void
    {
        [$konto, $lokal] = $this->paar('notiz-leerraum@example.test');
        $this->service->save($konto, $lokal, 'weg damit');

        self::assertSame(RestaurantNoteService::DELETED, $this->service->save($konto, $lokal, " \r\n\t "));
        self::assertNull($this->text($konto, $lokal));
    }

    public function testEc08LeerOhneNotizIstKeineAenderung(): void
    {
        [$konto, $lokal] = $this->paar('notiz-nichts@example.test');

        self::assertSame(RestaurantNoteService::UNCHANGED, $this->service->save($konto, $lokal, '   '));
    }

    public function testEc06ZweitesSpeichernUeberschreibt(): void
    {
        [$konto, $lokal] = $this->paar('notiz-zweimal@example.test');
        $this->service->save($konto, $lokal, 'erste Fassung');

        self::assertSame(RestaurantNoteService::SAVED, $this->service->save($konto, $lokal, 'zweite Fassung'));
        self::assertSame('zweite Fassung', $this->text($konto, $lokal));
        self::assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM restaurant_note WHERE user_id = ?', [$konto->getId()]));
    }

    public function testGleicherTextIstKeineAenderung(): void
    {
        [$konto, $lokal] = $this->paar('notiz-gleich@example.test');
        $this->service->save($konto, $lokal, 'gleich');

        self::assertSame(RestaurantNoteService::UNCHANGED, $this->service->save($konto, $lokal, ' gleich '));
    }

    private function text(User $konto, Restaurant $lokal): ?string
    {
        $wert = $this->db->fetchOne(
            'SELECT text FROM restaurant_note WHERE user_id = ? AND restaurant_id = ?',
            [$konto->getId(), $lokal->getId()],
        );

        return false === $wert ? null : (string) $wert;
    }

    /**
     * @return array{User, Restaurant}
     */
    private function paar(string $email): array
    {
        $konto = (new User())->setName('Notiz')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $lokal = (new Restaurant())->setName('Notiz '.$email)->setCity('Luxembourg');
        $this->em->persist($konto);
        $this->em->persist($lokal);
        $this->em->flush();

        return [$konto, $lokal];
    }
}
