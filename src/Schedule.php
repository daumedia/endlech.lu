<?php

namespace App;

use App\Message\CaptureMetricSnapshot;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Wiederkehrende Aufgaben der Anwendung.
 *
 * ⚠️ Voraussetzung: ein laufender `php bin/console messenger:consume
 * scheduler_default`. Production läuft derzeit mit `MESSENGER_TRANSPORT_DSN=
 * sync://` und ohne Worker – dort feuert dieser Zeitplan nicht. Der Monatslauf
 * hängt deshalb am Cron-Eintrag auf `app:metrics:snapshot` (siehe README →
 * Deployment). Der Zeitplan hier ist die saubere Anlaufstelle, sobald ein
 * Worker existiert, und der Weg für lokale Läufe.
 */
#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true) // ensure only last missed task is run
            ->add(
                // Am Ersten jedes Monats um 03:15 Uhr Luxemburger Zeit: früh
                // genug, dass die Zahlen am ersten Arbeitstag stehen, spät
                // genug, um nicht mit nächtlichen Wartungsfenstern zu kollidieren.
                RecurringMessage::cron(
                    '15 3 1 * *',
                    new CaptureMetricSnapshot(),
                    new \DateTimeZone('Europe/Luxembourg'),
                ),
            );
    }
}
