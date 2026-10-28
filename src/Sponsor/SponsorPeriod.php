<?php

declare(strict_types=1);

namespace App\Sponsor;

/**
 * Ein Zeitraum, in dem ein Sponsor genannt wird (Feature 15).
 *
 * Beide Angaben sind Kalendertage im Format `JJJJ-MM-TT` und gelten nach
 * Luxemburger Zeit. Das Ende zählt **mit** (EC-01, EC-12): Wer zum 31. Mai
 * kündigt, steht am 31. Mai noch im Footer und ist ab dem 1. Juni ehemalig.
 *
 * ⚠ **Texte statt Datumsobjekte**, weil ein Tag hier kein Zeitpunkt ist. Ein
 * `DateTimeImmutable` trüge eine Uhrzeit und eine Zeitzone mit, und der
 * Vergleich „liegt heute im Zeitraum?“ hinge davon ab, in welcher Zone jemand
 * den Eintrag angelegt hat. Zwei Texte im selben Format vergleichen sich
 * eindeutig. Ob sie gültige Tage sind, prüft `SponsorRules`.
 */
final readonly class SponsorPeriod
{
    public function __construct(
        public string $start,
        public ?string $end = null,
    ) {
    }

    public function isOpen(): bool
    {
        return null === $this->end;
    }

    /** Läuft der Zeitraum am Tag `$today` (Format `JJJJ-MM-TT`)? */
    public function covers(string $today): bool
    {
        return $this->start <= $today && (null === $this->end || $today <= $this->end);
    }

    /** Liegt der Zeitraum vollständig vor dem Tag `$today`? */
    public function endedBefore(string $today): bool
    {
        return null !== $this->end && $this->end < $today;
    }

    public function startDate(): \DateTimeImmutable
    {
        return self::day($this->start);
    }

    public function endDate(): ?\DateTimeImmutable
    {
        return null === $this->end ? null : self::day($this->end);
    }

    private static function day(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value.' 12:00:00', new \DateTimeZone(SponsorDirectory::TIMEZONE));
    }
}
