<?php

declare(strict_types=1);

namespace App\Board;

use App\Repository\BoardIdeaRepository;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Löscht nie freigegebene Einreichungen älter als zwölf Monate (AK-74).
 *
 * ⚠ **Hängt bewusst an keinem neuen Cron-Eintrag.** Auf Produktion fehlen von
 * drei geplanten Läufen zwei — `app:metrics:snapshot` hat dadurch nie einen
 * Snapshot geschrieben, und die Historie ist nicht nachholbar. Eine Frist, die
 * von einer Servereinrichtung abhängt, die schon zweimal ausblieb, wäre keine.
 *
 * Deshalb zwei Wege zum selben Ergebnis:
 *  - `app:board:cleanup` für den Tag, an dem der Cron steht,
 *  - `sweepOncePerDay()` beim Öffnen der Moderationsschlange.
 *
 * Der zweite ist über einen Cache-Schlüssel auf einen Lauf je Tag gesperrt: Die
 * Löschung ist eine einzige DQL-Anweisung auf einer indizierten Spalte, aber
 * sie gehört nicht in jeden Seitenaufruf.
 */
final readonly class StaleIdeaCleaner
{
    private const string SPERRE = 'board.cleanup.last_run';

    public function __construct(
        private BoardIdeaRepository $ideas,
        private CacheItemPoolInterface $cache,
    ) {
    }

    /** @return int Zahl der gelöschten Einreichungen */
    public function sweep(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return $this->ideas->deleteStaleUnpublished($now->modify(BoardIdeaRepository::STALE_AFTER));
    }

    /**
     * Führt höchstens einen Lauf je Kalendertag aus.
     *
     * @return int Zahl der gelöschten Einreichungen; 0 auch dann, wenn heute
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
