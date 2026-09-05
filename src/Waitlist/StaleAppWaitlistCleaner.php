<?php

declare(strict_types=1);

namespace App\Waitlist;

use App\Repository\AppWaitlistEntryRepository;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Löscht nie selbst bestätigte Vormerkungen älter als 30 Tage (AK-47, AK-48).
 *
 * ⚠ **Hängt bewusst an keinem neuen Cron-Eintrag.** Auf Produktion fehlten
 * geplante Läufe schon zweimal — `app:metrics:snapshot` hat dadurch nie einen
 * Snapshot geschrieben, und die Historie ist nicht nachholbar. Eine Löschfrist,
 * die von einer Servereinrichtung abhängt, die bereits zweimal ausblieb, wäre
 * keine: Ohne Bestätigung liegt keine Einwilligung vor, und die Adresse läge
 * weiter in der Datenbank.
 *
 * Deshalb zwei Wege zum selben Ergebnis:
 *  - `app:app-waitlist:cleanup` für den Tag, an dem der Zeitplan läuft,
 *  - `sweepOncePerDay()` beim Öffnen der Wartelisten-Verwaltung.
 *
 * Der zweite ist über einen Cache-Schlüssel auf einen Lauf je Kalendertag
 * gesperrt: Die Löschung ist eine einzige DQL-Anweisung auf einer indizierten
 * Spalte, aber sie gehört nicht in jeden Seitenaufruf.
 *
 * ⚠ **Gelöscht wird nach `selfConfirmedAt IS NULL`, nicht nach
 * `status = pending`** — siehe `AppWaitlistEntryRepository::deleteStaleUnconfirmed()`.
 * Ein vom Admin weitergesetzter Eintrag steht nicht mehr auf `pending` und
 * entginge dem Lauf, obwohl nie jemand bestätigt hat (dieselbe Zweideutigkeit
 * wie BF-89).
 */
final readonly class StaleAppWaitlistCleaner
{
    private const string SPERRE = 'app_waitlist.cleanup.last_run';

    public function __construct(
        private AppWaitlistEntryRepository $entries,
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * ⚠ Der optionale `$now` ist kein Schmuck: Ohne ihn wäre die Frist nur mit
     * einer manipulierten Systemzeit prüfbar.
     *
     * @return int Zahl der gelöschten Vormerkungen
     */
    public function sweep(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return $this->entries->deleteStaleUnconfirmed($now->modify(AppWaitlistEntryRepository::STALE_AFTER));
    }

    /**
     * Führt höchstens einen Lauf je Kalendertag aus.
     *
     * @return int Zahl der gelöschten Vormerkungen; 0 auch dann, wenn heute
     *             bereits gelaufen wurde
     */
    public function sweepOncePerDay(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $eintrag = $this->cache->getItem(self::SPERRE);

        if ($eintrag->isHit() && $eintrag->get() === $now->format('Y-m-d')) {
            return 0;
        }

        $geloescht = $this->sweep($now);

        $eintrag->set($now->format('Y-m-d'));
        $eintrag->expiresAfter(60 * 60 * 48);
        $this->cache->save($eintrag);

        return $geloescht;
    }
}
