<?php

declare(strict_types=1);

namespace App\Board;

use App\Entity\BoardIdea;
use App\Repository\BoardIdeaRepository;

/**
 * Wie lange wartet eine Einreichung schon — in Werktagen (AK-73, AK-79).
 *
 * Zwei Stufen, und das ist der Kern: Die erste warnt, **bevor** die in AK-72
 * zugesagten fünf Werktage um sind, die zweite genau dann. Eine einzelne
 * Schwelle könnte entweder warnen oder den Bruch melden, nicht beides.
 *
 * ⚠ **Werktag heißt Montag bis Freitag. Feiertage werden nicht gerechnet.**
 * Eine Feiertagstabelle für Luxemburg wäre eigene Mechanik, die zweimal im Jahr
 * eine Kachel anders einfärbt — Aufwand ohne Gegenwert. Das steht so in der
 * Spec und ist eine Entscheidung, keine Nachlässigkeit.
 */
final readonly class Overdue
{
    public const string OK = 'ok';
    public const string DUE_SOON = 'due_soon';
    public const string OVERDUE = 'overdue';

    /** @return self::OK|self::DUE_SOON|self::OVERDUE */
    public function levelFor(BoardIdea $idea, ?\DateTimeImmutable $now = null): string
    {
        $tage = $this->workdaysBetween($idea->getCreatedAt(), $now ?? new \DateTimeImmutable());

        if ($tage >= BoardIdeaRepository::OVERDUE_WORKDAYS) {
            return self::OVERDUE;
        }

        if ($tage >= BoardIdeaRepository::DUE_SOON_WORKDAYS) {
            return self::DUE_SOON;
        }

        return self::OK;
    }

    /**
     * Volle Werktage zwischen zwei Zeitpunkten.
     *
     * Gezählt wird kalendertagweise ab dem Tag **nach** der Einreichung: Wer
     * morgens einreicht, hat am selben Abend keinen Werktag Wartezeit.
     */
    public function workdaysBetween(\DateTimeInterface $von, \DateTimeInterface $bis): int
    {
        $start = (new \DateTimeImmutable($von->format('Y-m-d')))->modify('+1 day');
        $ende = new \DateTimeImmutable($bis->format('Y-m-d'));

        if ($start > $ende) {
            return 0;
        }

        $tage = 0;
        for ($tag = $start; $tag <= $ende; $tag = $tag->modify('+1 day')) {
            if ((int) $tag->format('N') < 6) {
                ++$tage;
            }
        }

        return $tage;
    }
}
