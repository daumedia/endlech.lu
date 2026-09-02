<?php

namespace App\Scheduler;

use App\Message\CaptureMetricSnapshot;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Der Monatslauf der Open-Startup-Kennzahlen — **mit** Nachholen.
 *
 * ⚠ **Warum ein eigener Zeitplan und nicht ein zweiter Eintrag neben dem
 * Brevo-Abgleich:** Ob verpasste Läufe nachgeholt werden, entscheidet
 * `processOnlyLastMissedRun()` — und das Flag hängt am **Zeitplan**, nicht am
 * einzelnen Eintrag (`MessageGenerator` fragt `$this->schedule->
 * shouldProcessOnlyLastMissedRun()`). Zwei Einträge mit gegensätzlichem Bedarf
 * passen deshalb nicht in denselben Zeitplan. Der Preis sind zwei Transporte im
 * Consumer-Befehl; der Nutzen ist, dass ein ausgefallener Monatswert
 * zurückkommt, ohne dass ein Ausfall den Fünf-Minuten-Takt hundertfach nachholt.
 *
 * ⚠ **`catchUp()` gibt es in symfony/scheduler 8.0 nicht** (nachgesehen in
 * `vendor/symfony/scheduler/Schedule.php`: die öffentliche Fläche kennt
 * `stateful()`, `processOnlyLastMissedRun()`, `lock()`, nichts weiter). Nachholen
 * ist hier das **Standardverhalten** eines Zeitplans mit Zustand — man schaltet
 * es mit `processOnlyLastMissedRun(true)` ab, nicht mit einem Aufruf ein. Der
 * Aufruf steht unten trotzdem ausdrücklich mit `false` da: Ohne ihn wäre nicht
 * erkennbar, ob jemand das Nachholen gewollt oder bloß vergessen hat.
 */
#[AsSchedule('metrics')]
final class MetricsScheduleProvider implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        // ⚠ Nicht der Vorgabe-Pool: Der liegt im Dateisystem unter var/cache und
        // ist nach jedem `cache:clear` weg — also nach jedem Deploy. Der
        // Merkposten läge dann bei „jetzt", und ein Monatslauf, der während des
        // Deploys fällig war, wäre für immer verloren. `cache.scheduler` liegt
        // in der Datenbank.
        #[Autowire(service: 'cache.scheduler')]
        private CacheInterface $state,
        private LockFactory $lockFactory,
    ) {
    }

    /**
     * ⚠ **Das `??=` ist der Unterschied zwischen „läuft" und „läuft nie."**
     * `getSchedule()` wird mehrfach gerufen — vom Transport, vom Generator, von
     * `debug:scheduler`. Ohne Zwischenspeicherung entstünde je Aufruf ein
     * **neues** Sperrobjekt auf derselben Ressource; das zweite `acquire()`
     * scheitert am `flock` des ersten, und der Generator bricht ab, ohne etwas
     * zu melden. Nachgestellt am 2026-09-02: Ein Consumer lief 220 Sekunden über
     * einen fälligen Takt hinweg und verarbeitete nichts, während
     * `debug:scheduler` weiterhin vollkommen richtig aussah.
     */
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->stateful($this->state)
            // Nachholen eingeschaltet: siehe Klassenkommentar.
            ->processOnlyLastMissedRun(false)
            ->lock($this->lockFactory->createLock('scheduler-metrics', 3600))
            ->add(
                // Am Ersten jedes Monats um 03:15 Uhr Luxemburger Zeit: früh
                // genug, dass die Zahlen am ersten Arbeitstag stehen, spät genug,
                // um nicht mit nächtlichen Wartungsfenstern zu kollidieren.
                //
                // Die Nachricht trägt bewusst keine Nutzlast: Welcher Monat
                // gemeint ist, ergibt sich aus dem Ausführungszeitpunkt
                // (MetricSnapshotService::defaultMonth() = abgeschlossener
                // Vormonat). Ein mitgeschicktes Datum wäre bei einem nachgeholten
                // Lauf falsch.
                RecurringMessage::cron(
                    '15 3 1 * *',
                    new CaptureMetricSnapshot(),
                    new \DateTimeZone('Europe/Luxembourg'),
                ),
            );
    }
}
