<?php

namespace App\Tests\Integration\Command;

use App\Command\MessengerWatchCommand;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Die Überwachung der Messenger-Warteschlange.
 *
 * ⚠ Der wichtigste Test ist `testWarnungLandetNichtInDerWarteschlange` — genau
 * dieser Fehler steckte in der ersten Fassung: `MailerInterface` schiebt jede
 * Mail über den Bus, die Warnung hätte also in der Warteschlange gelegen, vor
 * der sie warnt.
 */
final class MessengerWatchCommandTest extends KernelTestCase
{
    private Connection $db;
    private CommandTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->tester = new CommandTester(
            (new Application(self::$kernel))->find('app:messenger:watch'),
        );
        $this->db->executeStatement('DELETE FROM messenger_messages');
    }

    public function testLeereWarteschlangeIstKeinAlarm(): void
    {
        $this->tester->execute([]);

        self::assertSame(0, $this->tester->getStatusCode());
        self::assertStringContainsString('Die Warteschlange läuft', $this->tester->getDisplay());
    }

    public function testUnterhalbDerSchwelleIstKeinAlarm(): void
    {
        $this->fuelle(MessengerWatchCommand::SCHWELLE - 1);

        $this->tester->execute([]);

        self::assertStringContainsString('Die Warteschlange läuft', $this->tester->getDisplay());
    }

    public function testAbDerSchwelleSchlaegtErAn(): void
    {
        $this->fuelle(MessengerWatchCommand::SCHWELLE);

        $this->tester->execute(['--dry-run' => true]);

        self::assertStringContainsString('Rückstau erkannt', $this->tester->getDisplay());
    }

    /** Der zweite Pfad: wenige Nachrichten, aber eine hängt seit Stunden. */
    public function testEineHaengendeNachrichtGenuegt(): void
    {
        $this->db->executeStatement(
            "INSERT INTO messenger_messages (body, headers, queue_name, created_at, available_at, delivered_at)
             VALUES ('O:8:\"stdClass\":0:{}', '{}', 'async', NOW(), NOW(), :alt)",
            ['alt' => (new \DateTimeImmutable('-2 hours'))->format('Y-m-d H:i:s')],
        );

        $this->tester->execute(['--dry-run' => true]);

        self::assertStringContainsString('Rückstau erkannt', $this->tester->getDisplay());
    }

    /**
     * ⚠ **Der Kern der Sache.** Die Warnung darf die Warteschlange nicht
     * verlängern — sonst wartet sie auf den Worker, dessen Ausfall sie meldet.
     */
    public function testWarnungLandetNichtInDerWarteschlange(): void
    {
        $this->fuelle(MessengerWatchCommand::SCHWELLE);
        $vorher = $this->offen();

        $this->tester->execute([]);   // ohne --dry-run: echter Versand

        self::assertSame(
            $vorher,
            $this->offen(),
            'Die Warnung muss am Messenger vorbeigehen (TransportInterface, nicht MailerInterface).',
        );
    }

    /** Ein belegtes Schloss ist SUCCESS — sonst füllt die Überwachung den failed-Transport. */
    public function testBelegtesSchlossIstKeinFehler(): void
    {
        $this->tester->execute([]);
        $this->tester->execute([]);

        self::assertSame(0, $this->tester->getStatusCode());
    }

    private function fuelle(int $anzahl): void
    {
        for ($i = 0; $i < $anzahl; ++$i) {
            $this->db->executeStatement(
                "INSERT INTO messenger_messages (body, headers, queue_name, created_at, available_at)
                 VALUES ('O:8:\"stdClass\":0:{}', '{}', 'async', NOW(), NOW())",
            );
        }
    }

    private function offen(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM messenger_messages WHERE delivered_at IS NULL');
    }
}
