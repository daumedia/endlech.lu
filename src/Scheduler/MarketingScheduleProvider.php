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
 * Wiederkehrende Hausarbeit ohne Nachholen: der Brevo-Kontaktabgleich alle fünf
 * Minuten und die tägliche Aufräumung der App-Warteliste.
 *
 * ⚠ **Der Name des Zeitplans ist enger als sein Inhalt.** Die Aufräumung
 * (Feature 08) hat mit Marketing nichts zu tun und sitzt trotzdem hier: Beide
 * Aufgaben teilen die Eigenschaft, dass ein verpasster Lauf durch genau einen
 * nachgeholten aufgeholt wird — und `processOnlyLastMissedRun()` hängt am
 * Zeitplan, nicht am einzelnen Eintrag. Ein passender dritter Zeitplan kostete
 * einen dritten Transport im Consumer-Befehl an drei Orten, von denen einer in
 * Coolify von Hand gepflegt wird (OF-07 in `features/08-app-warteliste/spec.md`).
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
            )
            ->add(
                // Feature 08, AK-47/AK-49: Nie bestätigte App-Vormerkungen
                // fallen nach 30 Tagen weg. Einmal täglich um 03:40 — spät
                // genug, dass der Monatslauf um 03:15 durch ist, und außerhalb
                // der Zeit, in der jemand die Verwaltung offen hat.
                //
                // ⚠ **Hier und nicht in einem eigenen Zeitplan.** Ein dritter
                // Zeitplan bräuchte einen dritten Transport im
                // `messenger:consume`-Befehl, und der steht an drei Stellen:
                // im `worker`-Stage des Dockerfiles, in `CLAUDE.md` und in
                // Coolifys Startbefehl. Die dritte zieht niemand automatisch
                // nach, und ihr Ausfall ist lautlos.
                //
                // Das `processOnlyLastMissedRun(true)` dieses Zeitplans passt
                // dazu: Der Lauf arbeitet einen Bestand ab, keinen Zeitpunkt —
                // ein einzelner nachgeholter Durchgang holt alles auf, genau
                // wie beim Brevo-Abgleich.
                //
                // ⚠ Die Zeitzone ist hier nicht optional wie beim
                // Fünf-Minuten-Takt: Ohne sie liefe der Eintrag nach UTC und
                // damit im Sommer um 05:40 Ortszeit.
                RecurringMessage::cron(
                    '40 3 * * *',
                    new RunCommandMessage('app:app-waitlist:cleanup --no-interaction'),
                    new \DateTimeZone('Europe/Luxembourg'),
                ),
            );
    }
}
