<?php

namespace App\Tests\Integration\Command;

use App\Repository\MetricSnapshotRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Der Konsolenbefehl ist auf Production der eigentliche Auslöser: Der
 * Zeitplan aus App\Schedule braucht einen Messenger-Worker, den es dort nicht
 * gibt. Entsprechend muss er auch die unschönen Eingaben aushalten.
 */
final class CaptureMetricSnapshotCommandTest extends KernelTestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $this->tester = new CommandTester($application->find('app:metrics:snapshot'));
    }

    public function testWritesTheSnapshotForTheGivenMonth(): void
    {
        $this->tester->execute(['--month' => '2026-04']);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString('2026-04', $this->tester->getDisplay());
        self::assertNotNull(
            self::getContainer()->get(MetricSnapshotRepository::class)->findForMonth(new \DateTimeImmutable('2026-04-01')),
        );
    }

    public function testSecondRunReportsTheExistingSnapshotInsteadOfDuplicatingIt(): void
    {
        $this->tester->execute(['--month' => '2026-05']);
        $this->tester->execute(['--month' => '2026-05']);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString('existiert bereits', $this->tester->getDisplay());
        self::assertCount(
            1,
            self::getContainer()->get(MetricSnapshotRepository::class)->findBy(['capturedFor' => new \DateTimeImmutable('2026-05-01')]),
        );
    }

    public function testInvalidMonthIsRejectedWithoutAStackTrace(): void
    {
        $exitCode = $this->tester->execute(['--month' => 'April 2026']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('YYYY-MM', $this->tester->getDisplay());
    }

    public function testMonthWithoutDayIsNormalisedToTheFirst(): void
    {
        $this->tester->execute(['--month' => '2026-02']);

        $snapshot = self::getContainer()->get(MetricSnapshotRepository::class)->findForMonth(new \DateTimeImmutable('2026-02-20'));

        self::assertNotNull($snapshot);
        self::assertSame('2026-02-01', $snapshot->getCapturedFor()->format('Y-m-d'));
    }
}
