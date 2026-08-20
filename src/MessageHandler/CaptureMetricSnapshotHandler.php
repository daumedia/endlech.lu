<?php

namespace App\MessageHandler;

use App\Message\CaptureMetricSnapshot;
use App\Open\MetricSnapshotService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CaptureMetricSnapshotHandler
{
    public function __construct(
        private readonly MetricSnapshotService $snapshots,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CaptureMetricSnapshot $message): void
    {
        $result = $this->snapshots->capture();

        $this->logger->info('Monats-Snapshot der Open-Startup-Kennzahlen verarbeitet.', [
            'month' => $result['snapshot']->getMonthKey(),
            'created' => $result['created'],
        ]);
    }
}
