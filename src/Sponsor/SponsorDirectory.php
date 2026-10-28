<?php

declare(strict_types=1);

namespace App\Sponsor;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Wer ist heute Sponsor, wer war es, wer noch nicht (Feature 15)?
 *
 * Die einzige Stelle, die das beantwortet — Footer, Übersicht, Sponsorenseite,
 * Faktenblatt, Unternehmensseite und Sitemap fragen alle hier. Gerechnet wird
 * bei jeder Anfrage aus den Zeiträumen und dem heutigen Tag; deshalb greifen
 * Beginn und Ende ohne Ausrollen (AK-23, AK-24).
 */
final readonly class SponsorDirectory
{
    public const string TIMEZONE = 'Europe/Luxembourg';

    /** Höchstzahl der Logos im Footer und auf der Unternehmensseite (AK-06, AK-31). */
    public const int FOOTER_LIMIT = 12;

    public function __construct(
        private SponsorSource $source,
        #[Autowire(service: 'app.sponsor_clock')]
        private ClockInterface $clock,
    ) {
    }

    /** Der heutige Kalendertag in Luxemburg, Format `JJJJ-MM-TT` (EC-02). */
    public function today(): string
    {
        return $this->clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('Y-m-d');
    }

    /**
     * Alle aktiven Sponsoren — nach Beginn des laufenden Zeitraums, der älteste
     * zuerst; Gleichstand nach dem Namen (AK-02, AK-25, EC-03).
     *
     * @return list<Sponsor>
     */
    public function active(): array
    {
        $today = $this->today();
        $active = array_values(array_filter(
            $this->source->entries(),
            static fn (Sponsor $s): bool => null !== self::periodCovering($s, $today),
        ));

        usort($active, static function (Sponsor $a, Sponsor $b) use ($today): int {
            $byStart = self::periodCovering($a, $today)?->start <=> self::periodCovering($b, $today)?->start;

            return 0 !== $byStart ? $byStart : self::sortName($a) <=> self::sortName($b);
        });

        return $active;
    }

    /**
     * Alle ehemaligen Sponsoren — das jüngste Ende zuerst (AK-15).
     *
     * Ehemalig ist, wer heute nicht aktiv ist und mindestens einen
     * abgeschlossenen Zeitraum hat. Wer nur künftige Zeiträume hat, ist weder
     * aktiv noch ehemalig, sondern unsichtbar (AK-23).
     *
     * @return list<Sponsor>
     */
    public function former(): array
    {
        $today = $this->today();
        $former = array_values(array_filter(
            $this->source->entries(),
            static fn (Sponsor $s): bool => null === self::periodCovering($s, $today) && null !== self::lastEnd($s, $today),
        ));

        usort($former, static function (Sponsor $a, Sponsor $b) use ($today): int {
            $byEnd = self::lastEnd($b, $today) <=> self::lastEnd($a, $today);

            return 0 !== $byEnd ? $byEnd : self::sortName($a) <=> self::sortName($b);
        });

        return $former;
    }

    /**
     * Die Auswahl für Footer und Unternehmensseite: die ersten zwölf aktiven
     * (AK-06, AK-31, EC-07).
     *
     * @return list<Sponsor>
     */
    public function footer(): array
    {
        return \array_slice($this->active(), 0, self::FOOTER_LIMIT);
    }

    /**
     * Aktive und ehemalige — alle, die eine Seite haben und in der Sitemap
     * stehen (AK-41).
     *
     * @return list<Sponsor>
     */
    public function visible(): array
    {
        return [...$this->active(), ...$this->former()];
    }

    /** Gibt es einen aktiven oder ehemaligen Sponsor? Dann steht „Alle Sponsoren“ im Footer (AK-05). */
    public function hasAny(): bool
    {
        return [] !== $this->visible();
    }

    public function isActive(Sponsor $sponsor): bool
    {
        return null !== self::periodCovering($sponsor, $this->today());
    }

    /** Der sichtbare Sponsor zu einer Adresse — nie ein unsichtbarer oder entfernter (AK-22, AK-23). */
    public function findVisible(string $slug): ?Sponsor
    {
        foreach ($this->visible() as $sponsor) {
            if ($sponsor->slug === $slug) {
                return $sponsor;
            }
        }

        return null;
    }

    public function isRemoved(string $slug): bool
    {
        return \in_array($slug, $this->source->removedSlugs(), true);
    }

    /** Der heute laufende Zeitraum — für „Sponsor seit …“ (AK-12, AK-25). */
    public function currentPeriod(Sponsor $sponsor): ?SponsorPeriod
    {
        return self::periodCovering($sponsor, $this->today());
    }

    /**
     * Die Zeiträume, die schon begonnen haben (AK-16). Ein künftiger Zeitraum nach einer
     * Pause wird noch nicht genannt.
     *
     * @return list<SponsorPeriod>
     */
    public function startedPeriods(Sponsor $sponsor): array
    {
        $today = $this->today();

        return array_values(array_filter(
            $sponsor->periods,
            static fn (SponsorPeriod $p): bool => $p->start <= $today,
        ));
    }

    private static function periodCovering(Sponsor $sponsor, string $today): ?SponsorPeriod
    {
        foreach ($sponsor->periods as $period) {
            if ($period->covers($today)) {
                return $period;
            }
        }

        return null;
    }

    /** Das letzte Ende vor heute, oder null, wenn kein Zeitraum abgeschlossen ist. */
    private static function lastEnd(Sponsor $sponsor, string $today): ?string
    {
        $last = null;
        foreach ($sponsor->periods as $period) {
            if ($period->endedBefore($today) && ($last === null || $period->end > $last)) {
                $last = $period->end;
            }
        }

        return $last;
    }

    /**
     * Vergleichsschlüssel für den Gleichstand: ASCII-gefaltet und klein, damit
     * „École“ neben „Ecole“ steht und die Reihenfolge in allen fünf Sprachen
     * dieselbe ist (EC-03).
     */
    private static function sortName(Sponsor $sponsor): string
    {
        $folded = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $sponsor->name);

        return false === $folded ? mb_strtolower($sponsor->name) : $folded;
    }
}
