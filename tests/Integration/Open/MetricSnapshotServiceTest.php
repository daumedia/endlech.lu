<?php

namespace App\Tests\Integration\Open;

use App\Entity\Restaurant;
use App\Open\MetricSnapshotService;
use App\Repository\MetricSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MetricSnapshotServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MetricSnapshotService $snapshots;
    private MetricSnapshotRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->snapshots = self::getContainer()->get(MetricSnapshotService::class);
        $this->repository = self::getContainer()->get(MetricSnapshotRepository::class);
    }

    public function testDefaultMonthIsTheCompletedPreviousMonth(): void
    {
        $month = $this->snapshots->defaultMonth(new \DateTimeImmutable('2026-08-01 03:15:00'));

        self::assertSame('2026-07-01', $month->format('Y-m-d'));
    }

    public function testCaptureWritesASnapshotForTheGivenMonth(): void
    {
        $result = $this->snapshots->capture(new \DateTimeImmutable('2026-05-17'));

        self::assertTrue($result['created']);
        self::assertSame('2026-05', $result['snapshot']->getMonthKey());
        self::assertSame('2026-05-01', $result['snapshot']->getCapturedFor()->format('Y-m-d'), 'Der Tag muss auf den Monatsanfang normalisiert werden.');
    }

    /**
     * Der Lauf kommt aus zwei Richtungen (Zeitplan und Konsolenbefehl) und
     * kann nachgeholt werden. Ein zweiter Schreibvorgang darf keinen zweiten
     * Punkt im Verlauf erzeugen.
     */
    public function testCaptureIsIdempotent(): void
    {
        $month = new \DateTimeImmutable('2026-04-01');

        $first = $this->snapshots->capture($month);
        $second = $this->snapshots->capture($month);

        self::assertTrue($first['created']);
        self::assertFalse($second['created']);
        self::assertSame($first['snapshot']->getId(), $second['snapshot']->getId());
        self::assertCount(1, $this->repository->findBy(['capturedFor' => $month]));
    }

    public function testForceOverwritesTheExistingSnapshot(): void
    {
        $month = new \DateTimeImmutable('2026-03-01');
        $first = $this->snapshots->capture($month);
        $countBefore = $first['snapshot']->getRestaurantCount();

        $this->em->persist((new Restaurant())->setName('Nachzügler')->setCity('Mersch'));
        $this->em->flush();

        $second = $this->snapshots->capture($month, force: true);

        self::assertSame($first['snapshot']->getId(), $second['snapshot']->getId(), 'Force überschreibt, es legt keinen zweiten Eintrag an.');
        self::assertSame($countBefore + 1, $second['snapshot']->getRestaurantCount());
    }

    /**
     * Die Quartalssperre blendet Einnahmen auf der Seite aus. Der Snapshot
     * muss sie trotzdem festhalten – sonst stünde für die Anfangsmonate
     * dauerhaft eine 0 in der Historie, die sich nicht mehr korrigieren lässt.
     */
    public function testSnapshotStoresIncomeEvenWhileItIsWithheldFromThePage(): void
    {
        $snapshot = $this->snapshots->capture(new \DateTimeImmutable('2026-02-01'))['snapshot'];

        self::assertGreaterThan(0.0, (float) $snapshot->getTotalIncome(), 'Die Fixtures enthalten Einnahmen im laufenden Quartal.');
        self::assertFalse($snapshot->getPayload()['finance']['incomeVisible'], 'Die Anzeigeentscheidung wird mitgeschrieben, nicht angewandt.');
    }

    public function testPayloadKeepsTheFullMeasurement(): void
    {
        $payload = $this->snapshots->capture(new \DateTimeImmutable('2026-01-01'))['snapshot']->getPayload();

        self::assertArrayHasKey('platform', $payload);
        self::assertArrayHasKey('impact', $payload);
        self::assertArrayHasKey('finance', $payload);
        self::assertArrayHasKey('scoreDistribution', $payload['platform']);
        self::assertArrayHasKey('byCanton', $payload['platform']);
    }

    public function testFindTrendReturnsChronologicalOrder(): void
    {
        $this->snapshots->capture(new \DateTimeImmutable('2025-11-01'));
        $this->snapshots->capture(new \DateTimeImmutable('2025-12-01'));
        $this->snapshots->capture(new \DateTimeImmutable('2026-01-01'));

        $keys = array_map(
            static fn ($snapshot) => $snapshot->getMonthKey(),
            $this->repository->findTrend(3),
        );

        self::assertSame(['2025-11', '2025-12', '2026-01'], $keys);
    }
}
