<?php

namespace App\Roadmap;

/**
 * Die Sammelzeile für die Aufbauphase (OF-01, entschieden am 2026-08-30).
 *
 * Sie steht für die Releases, die einzeln nichts erzählen, zusammen aber die
 * Entstehung der Plattform — Januar bis März 2026. Ohne sie begänne der Changelog
 * mitten in der Geschichte und die Plattform sähe drei Wochen alt aus.
 *
 * ⚠ **Sie ist kein Release und trägt deshalb keine Version.** Ihr Datum ist nur
 * dafür da, sie im richtigen Jahr und an der richtigen Stelle einzusortieren; sie
 * steht als älteste Zeile unter den gezeigten Einträgen.
 */
final readonly class ChangelogSummary
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }

    /** Für die Einsortierung: dieselbe Achse wie `ReleaseNote::$date`. */
    public function date(): \DateTimeImmutable
    {
        return $this->to;
    }

    public function year(): string
    {
        return $this->to->format('Y');
    }

    public function titleKey(): string
    {
        return 'summary.title';
    }

    /** Der Zeitraum als Text — „Januar bis März 2026" in der jeweiligen Sprache. */
    public function periodKey(): string
    {
        return 'summary.period';
    }

    public function bodyKey(): string
    {
        return 'summary.body';
    }
}
