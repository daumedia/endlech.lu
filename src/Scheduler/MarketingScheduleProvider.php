<?php

namespace App\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Der Brevo-Kontaktabgleich alle fünf Minuten — **ohne** Nachholen.
 *
 * ⚠ **`processOnlyLastMissedRun(true)` ist hier die eigentliche Aussage.** Stand
 * der Consumer drei Tage, wären 864 Läufe fällig; ohne das Flag arbeitete der
 * Worker sie nacheinander ab und liefe dabei 864 Mal gegen Brevos API — mit
 * einem Auftragsbuch, das schon der erste Lauf vollständig geleert hätte. Mit
 * dem Flag wird genau **ein** Lauf nachgeholt, und der holt alles auf: Der
 * Befehl arbeitet ein Auftragsbuch ab, keinen Zeitpunkt.
 *
 * ⚠ **`RunCommandMessage` statt eigener Nachricht mit Handler.** Der Zeitplan
 * geht damit exakt denselben Weg wie ein Aufruf von Hand. Der Befehl prüft
 * `--limit`, unterscheidet Fehlversuche (eingeplanter Normalfall, färbt nicht
 * rot) von einem fehlenden API-Schlüssel (Konfigurationsfehler, färbt rot) und
 * hält E-Mail-Adressen aus der Ausgabe (AK-31). Ein Handler müsste all das
 * nachbauen, und die zweite Fassung liefe irgendwann auseinander.
 */
#[AsSchedule('marketing')]
final class MarketingScheduleProvider implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        // Derselbe persistente Pool wie beim Monatslauf — der Merkposten muss
        // auch hier einen Neustart überleben, sonst gilt nach jedem Deploy
        // „jetzt" als letzter Lauf und der eine nachzuholende Durchgang fiele aus.
        #[Autowire(service: 'cache.scheduler')]
        private CacheInterface $state,
        private LockFactory $lockFactory,
    ) {
    }

    /** ⚠ Zwischengespeichert wegen der Sperre — Begründung in {@see MetricsScheduleProvider::getSchedule()}. */
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->stateful($this->state)
            // Nachholen abgeschaltet: siehe Klassenkommentar.
            ->processOnlyLastMissedRun(true)
            // ⚠ Eigener Ressourcenname. Teilten sich beide Zeitpläne eine Sperre,
            // blockierte der eine den anderen — und zwar lautlos.
            ->lock($this->lockFactory->createLock('scheduler-marketing', 3600))
            ->add(
                // Alle fünf Minuten. AK-10 verlangt für den Abgleich eine Frist
                // von 15 Minuten — der Takt unterschreitet sie dreifach, damit
                // zwei ausgefallene Läufe die Zusage noch nicht brechen.
                //
                // ⚠ Die Zeitzone steht auch hier, obwohl ein Fünf-Minuten-Takt
                // nicht von ihr abhängt: Ohne Angabe zöge der Ausdruck die
                // Zeitzone des PHP-Prozesses, und die ist im Container UTC. Beim
                // nächsten Eintrag mit fester Uhrzeit wäre das eine stille
                // Stunde Versatz.
                RecurringMessage::cron(
                    '*/5 * * * *',
                    new RunCommandMessage('app:marketing:sync --no-interaction'),
                    new \DateTimeZone('Europe/Luxembourg'),
                ),
            );
    }
}
