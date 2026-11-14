<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Entity\Restaurant;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use App\Saved\SavedListResult;
use App\Saved\SavedListService;
use App\Saved\SavedWriteLock;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * Feature 17, T06: Die Namensregeln stehen an einer Stelle (`SavedListService`) — für die
 * Übersicht, das Umbenennen und die neue Liste auf der Detailseite gleichermaßen.
 */
final class SavedListServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $db;
    private SavedListService $service;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->db = $this->em->getConnection();
        // Direkt gebaut: Bis die Controller (Ebene 3) den Dienst benutzen, entfernt der
        // Container ihn als unbenutzt.
        $this->service = new SavedListService(
            $this->em,
            $this->em->getRepository(SavedList::class),
            $this->em->getRepository(SavedListEntry::class),
            new SavedWriteLock(new LockFactory(new InMemoryStore())),
        );
    }

    public function testAk16AnlegenSpeichertGetrimmtenNamen(): void
    {
        $konto = $this->konto('liste-anlegen@example.test');

        $ergebnis = $this->service->create($konto, '  Mittag in Kirchberg ');

        self::assertFalse($ergebnis->isRejected());
        self::assertTrue($ergebnis->changed);
        self::assertSame('Mittag in Kirchberg', $ergebnis->list?->getName());
        self::assertSame(1, $this->listen($konto));
    }

    public function testAk17LeererNameWirdAbgewiesen(): void
    {
        $konto = $this->konto('liste-leer@example.test');

        self::assertSame(SavedListResult::NAME_BLANK, $this->service->create($konto, "  \t ")->error);
        self::assertSame(0, $this->listen($konto));
    }

    public function testAk18EinundsechzigBuchstabenWerdenAbgewiesen(): void
    {
        $konto = $this->konto('liste-lang@example.test');

        self::assertSame(SavedListResult::NAME_TOO_LONG, $this->service->create($konto, str_repeat('a', 61))->error);
        self::assertFalse($this->service->create($konto, ' '.str_repeat('a', 60).' ')->isRejected());
    }

    public function testAk19GrossKleinUndRandleerraumSindEineDublette(): void
    {
        $konto = $this->konto('liste-dublette@example.test');
        $this->service->create($konto, 'Kirchberg');

        self::assertSame(SavedListResult::NAME_DUPLICATE, $this->service->create($konto, ' kirchberg ')->error);
        self::assertSame(1, $this->listen($konto));
    }

    public function testAk19AkzentIstKeineDublette(): void
    {
        $konto = $this->konto('liste-akzent@example.test');
        $this->service->create($konto, 'Cafe');

        self::assertFalse($this->service->create($konto, 'Café')->isRejected());
        self::assertSame(2, $this->listen($konto));
    }

    public function testAk19AnderesKontoDarfDenselbenNamenHaben(): void
    {
        $this->service->create($this->konto('liste-a@example.test'), 'Kirchberg');

        self::assertFalse($this->service->create($this->konto('liste-b@example.test'), 'Kirchberg')->isRejected());
    }

    public function testAk20SechsundzwanzigsteListeWirdAbgewiesen(): void
    {
        $konto = $this->konto('liste-25@example.test');
        for ($i = 1; $i <= SavedList::MAX_PER_USER; ++$i) {
            self::assertFalse($this->service->create($konto, 'Liste '.$i)->isRejected());
        }

        self::assertSame(SavedListResult::LIMIT_REACHED, $this->service->create($konto, 'Liste 26')->error);
        self::assertSame(25, $this->listen($konto));
    }

    public function testAk20GrenzeGiltAuchFuerDieNeueListeAufDerDetailseite(): void
    {
        $konto = $this->konto('liste-25-detail@example.test');
        $lokal = $this->lokal('Grenze');
        for ($i = 1; $i <= SavedList::MAX_PER_USER; ++$i) {
            $this->service->create($konto, 'Liste '.$i);
        }

        $ergebnis = $this->service->setMemberships($konto, $lokal, [], 'Liste 26');

        self::assertSame(SavedListResult::LIMIT_REACHED, $ergebnis->error);
        self::assertSame(25, $this->listen($konto));
    }

    public function testAk28EigenenNamenNochmalSpeichernIstKeineDublette(): void
    {
        $konto = $this->konto('liste-umbenennen@example.test');
        $liste = $this->service->create($konto, 'Kirchberg')->list;
        self::assertNotNull($liste);

        self::assertFalse($this->service->rename($liste, 'kirchberg')->isRejected());
        self::assertSame('kirchberg', $liste->getName());
    }

    public function testAk28UmbenennenAufFremdenEigenenNamenIstDublette(): void
    {
        $konto = $this->konto('liste-umbenennen-dublette@example.test');
        $this->service->create($konto, 'Kirchberg');
        $zweite = $this->service->create($konto, 'Mit Oma')->list;
        self::assertNotNull($zweite);

        self::assertSame(SavedListResult::NAME_DUPLICATE, $this->service->rename($zweite, 'KIRCHBERG')->error);
        self::assertSame('Mit Oma', $zweite->getName());
    }

    public function testAk22HaekchenSetzenUndEntfernen(): void
    {
        $konto = $this->konto('liste-haekchen@example.test');
        $lokal = $this->lokal('Haekchen');
        $erste = $this->service->create($konto, 'Erste')->list;
        $zweite = $this->service->create($konto, 'Zweite')->list;
        self::assertNotNull($erste);
        self::assertNotNull($zweite);
        $this->service->setMemberships($konto, $lokal, [$erste->getId()]);

        $ergebnis = $this->service->setMemberships($konto, $lokal, [$zweite->getId()]);

        self::assertTrue($ergebnis->changed);
        self::assertSame(0, $this->eintraege($erste));
        self::assertSame(1, $this->eintraege($zweite));
    }

    public function testAk23NeueListeEnthaeltDasLokalSofort(): void
    {
        $konto = $this->konto('liste-neu-detail@example.test');
        $lokal = $this->lokal('Neu');

        $ergebnis = $this->service->setMemberships($konto, $lokal, [], ' Mit Oma ');

        self::assertTrue($ergebnis->changed);
        self::assertNotNull($ergebnis->list);
        self::assertSame('Mit Oma', $ergebnis->list->getName());
        self::assertSame(1, $this->eintraege($ergebnis->list));
    }

    public function testAk23ScheitertDerNeueNameBleibenDieHaekchenUnveraendert(): void
    {
        $konto = $this->konto('liste-neu-scheitert@example.test');
        $lokal = $this->lokal('Scheitert');
        $erste = $this->service->create($konto, 'Erste')->list;
        self::assertNotNull($erste);

        $ergebnis = $this->service->setMemberships($konto, $lokal, [$erste->getId()], 'erste');

        self::assertSame(SavedListResult::NAME_DUPLICATE, $ergebnis->error);
        self::assertSame(0, $this->eintraege($erste), 'Kein Teil desselben Absendens darf übernommen werden.');
    }

    public function testAk31DoppeltesSetzenErgibtEinenEintrag(): void
    {
        $konto = $this->konto('liste-doppelt@example.test');
        $lokal = $this->lokal('Doppelt');
        $liste = $this->service->create($konto, 'Kirchberg')->list;
        self::assertNotNull($liste);

        self::assertTrue($this->service->setMemberships($konto, $lokal, [$liste->getId()])->changed);
        self::assertFalse($this->service->setMemberships($konto, $lokal, [$liste->getId()])->changed);
        self::assertSame(1, $this->eintraege($liste));
    }

    public function testAk30LoeschenNimmtNurDieListeUndIhreEintraege(): void
    {
        $konto = $this->konto('liste-loeschen@example.test');
        $lokal = $this->lokal('Loeschen');
        $liste = $this->service->create($konto, 'Weg')->list;
        self::assertNotNull($liste);
        $this->service->setMemberships($konto, $lokal, [$liste->getId()]);
        $id = $liste->getId();

        $this->service->delete($liste);

        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM saved_list WHERE id = ?', [$id]));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', [$id]));
    }

    public function testAk26AusListeEntfernen(): void
    {
        $konto = $this->konto('liste-eintrag-weg@example.test');
        $lokal = $this->lokal('Eintrag weg');
        $liste = $this->service->create($konto, 'Kirchberg')->list;
        self::assertNotNull($liste);
        $this->service->setMemberships($konto, $lokal, [$liste->getId()]);

        self::assertTrue($this->service->removeEntry($liste, $lokal));
        self::assertFalse($this->service->removeEntry($liste, $lokal));
        self::assertSame(0, $this->eintraege($liste));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function grenzfaelle(): iterable
    {
        yield '60 einfache Emoji' => [str_repeat('😀', 60), true];
        yield '61 einfache Emoji' => [str_repeat('😀', 61), false];
        yield '12 Familien-Emoji (60 Codepunkte)' => [str_repeat("👨\u{200D}👩\u{200D}👧", 12), true];
        yield '13 Familien-Emoji (65 Codepunkte)' => [str_repeat("👨\u{200D}👩\u{200D}👧", 13), false];
        yield '60 Umlaute' => [str_repeat('ß', 60), true];
        yield '60 japanische Zeichen' => [str_repeat('日', 60), true];
    }

    #[DataProvider('grenzfaelle')]
    public function testEc07SonderzeichenWerdenGespeichertOderAbgewiesenNieDatenbankfehler(string $name, bool $erlaubt): void
    {
        $konto = $this->konto('liste-ec07-'.md5($name).'@example.test');

        $ergebnis = $this->service->create($konto, $name);

        self::assertSame($erlaubt, !$ergebnis->isRejected());
        if ($erlaubt) {
            self::assertSame($name, (string) $this->db->fetchOne('SELECT name FROM saved_list WHERE user_id = ?', [$konto->getId()]));
        } else {
            self::assertSame(SavedListResult::NAME_TOO_LONG, $ergebnis->error);
        }
    }

    private function listen(User $konto): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM saved_list WHERE user_id = ?', [$konto->getId()]);
    }

    private function eintraege(SavedList $liste): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', [$liste->getId()]);
    }

    private function konto(string $email): User
    {
        $user = (new User())->setName('Listen')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function lokal(string $name): Restaurant
    {
        $restaurant = (new Restaurant())->setName('Liste '.$name)->setCity('Luxembourg');
        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }
}
