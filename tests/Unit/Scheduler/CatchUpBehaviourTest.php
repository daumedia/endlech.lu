<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduler;

use App\Message\CaptureMetricSnapshot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Generator\Checkpoint;
use Symfony\Component\Scheduler\Generator\MessageGenerator;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Belegt, was nach einem Ausfall des Consumers tatsächlich nachgeholt wird.
 *
 * ⚠ **Der Prüflauf misst das Verhalten, statt es zu behaupten.** Ob verpasste
 * Läufe nachgeholt werden, entscheidet ein einzelnes Boolean
 * (`processOnlyLastMissedRun`), das sich in `debug:scheduler` nicht zeigt und
 * dessen Wirkung erst nach einem echten Ausfall sichtbar würde — also genau dann,
 * wenn niemand mehr experimentieren will. Ein vertauschter Wert wäre in beide
 * Richtungen teuer: ein verlorener Monatswert auf der einen Seite, hunderte
 * Brevo-Läufe hintereinander auf der anderen.
 *
 * Der Aufbau spiegelt bewusst die beiden Provider aus `src/Scheduler/`, statt sie
 * aus dem Container zu holen: Hier geht es um die Wirkung der Einstellung, und
 * die braucht eine gestellte Uhr.
 */
final class CatchUpBehaviourTest extends TestCase
{
    private const TZ = 'Europe/Luxembourg';

    /**
     * Der Monatslauf holt JEDEN verpassten Termin nach.
     *
     * Gestellt: Der Consumer stand vom 1. September bis zum 3. Dezember. In das
     * Fenster fallen drei Monatsläufe (Oktober, November, Dezember) — und alle
     * drei müssen zugestellt werden.
     *
     * ⚠ Gemessen wird die ZUSTELLUNG, nicht das Ergebnis: `capture()` nimmt den
     * Vormonat relativ zum Laufzeitpunkt, hier schrieben also alle drei denselben
     * Monat und zwei verpufften an der Idempotenz. Der Fall, den das Nachholen
     * wirklich rettet, ist der kurze Ausfall — ein Deploy um 03:15 am Ersten. Mit
     * `processOnlyLastMissedRun(true)` ginge auch der verloren.
     */
    public function testMonatslaufHoltJedenVerpasstenTerminNach(): void
    {
        $nachrichten = $this->laufenLassen(
            onlyLastMissed: false,
            message: RecurringMessage::cron('15 3 1 * *', new CaptureMetricSnapshot(), new \DateTimeZone(self::TZ)),
            letzterLauf: '2026-09-01 03:15:00',
            jetzt: '2026-12-03 09:00:00',
        );

        self::assertCount(3, $nachrichten, 'Oktober, November und Dezember müssen einzeln nachgeholt werden.');
        self::assertContainsOnlyInstancesOf(CaptureMetricSnapshot::class, $nachrichten);
    }

    /**
     * Der Fünf-Minuten-Takt holt GENAU EINEN Durchgang nach – egal wie lange der
     * Ausfall dauerte.
     *
     * Gestellt: drei Tage Stillstand. Ohne das Flag wären das 864 Läufe
     * nacheinander, jeder mit eigenen Aufrufen gegen Brevos API — und alle bis
     * auf den ersten fänden ein leeres Auftragsbuch vor, weil der Befehl einen
     * Bestand abarbeitet und keinen Zeitpunkt.
     */
    #[DataProvider('ausfallDauern')]
    public function testBrevoAbgleichHoltGenauEinenDurchgangNach(string $jetzt, string $lage): void
    {
        $nachrichten = $this->laufenLassen(
            onlyLastMissed: true,
            message: RecurringMessage::cron('*/5 * * * *', new RunCommandMessage('app:marketing:sync --no-interaction'), new \DateTimeZone(self::TZ)),
            letzterLauf: '2026-09-02 12:00:00',
            jetzt: $jetzt,
        );

        self::assertCount(1, $nachrichten, $lage);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function ausfallDauern(): iterable
    {
        yield 'eine Stunde' => ['2026-09-02 13:00:00', 'Nach einer Stunde (12 Termine) darf genau ein Lauf kommen.'];
        yield 'drei Tage' => ['2026-09-05 12:00:00', 'Nach drei Tagen (864 Termine) darf immer noch genau ein Lauf kommen.'];
    }

    /**
     * Gegenprobe: Dieselbe Lage OHNE das Flag holt jeden einzelnen Termin nach.
     * Ohne diesen Fall bewiese der Test oben nur, dass irgendetwas eine Nachricht
     * liefert – nicht, dass die Einstellung greift.
     */
    public function testOhneDasFlagStapelnSichDieVerpasstenLaeufe(): void
    {
        $nachrichten = $this->laufenLassen(
            onlyLastMissed: false,
            message: RecurringMessage::cron('*/5 * * * *', new RunCommandMessage('app:marketing:sync --no-interaction'), new \DateTimeZone(self::TZ)),
            letzterLauf: '2026-09-02 12:00:00',
            jetzt: '2026-09-02 13:00:00',
        );

        self::assertCount(12, $nachrichten, 'Eine Stunde Ausfall entspricht zwölf Terminen im Fünf-Minuten-Takt.');
    }

    /**
     * @return list<object>
     */
    private function laufenLassen(
        bool $onlyLastMissed,
        RecurringMessage $message,
        string $letzterLauf,
        string $jetzt,
    ): array {
        $schedule = (new Schedule())
            ->processOnlyLastMissedRun($onlyLastMissed)
            ->add($message);

        $provider = new class($schedule) implements ScheduleProviderInterface {
            public function __construct(private readonly Schedule $schedule)
            {
            }

            public function getSchedule(): Schedule
            {
                return $this->schedule;
            }
        };

        // Der Merkposten steht auf dem letzten geglückten Lauf – genau das, was
        // der Pool `cache.scheduler` in der Datenbank festhält.
        $checkpoint = new Checkpoint('test');
        $checkpoint->acquire(new \DateTimeImmutable($letzterLauf, new \DateTimeZone(self::TZ)));
        $checkpoint->save(new \DateTimeImmutable($letzterLauf, new \DateTimeZone(self::TZ)), 0);

        $generator = new MessageGenerator(
            $provider,
            'test',
            new MockClock(new \DateTimeImmutable($jetzt, new \DateTimeZone(self::TZ))),
            $checkpoint,
        );

        return iterator_to_array($generator->getMessages(), false);
    }
}
