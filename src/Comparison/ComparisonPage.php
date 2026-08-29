<?php

namespace App\Comparison;

/**
 * Alles, was eine Vergleichsseite braucht – ohne Datenbank, ohne Zustand.
 *
 * `advantageKeys` sind die Punkte, in denen der Wettbewerber überlegen ist
 * (AK-10, mindestens drei). Das ist der Abschnitt, der die Seite glaubwürdig
 * macht: Eine Vergleichsseite ohne ihn ist Werbung, und Werbung liest niemand
 * als Auskunft.
 */
final readonly class ComparisonPage
{
    /**
     * @param list<ComparisonRow>    $rows
     * @param list<ComparisonSource> $sources
     * @param list<string>           $advantageKeys Textschlüssel, mindestens drei
     * @param list<string>           $faqKeys       Präfixe je Frage; erwartet werden
     *                                              `<präfix>.q` und `<präfix>.a`
     */
    public function __construct(
        public Competitor $competitor,
        public array $rows,
        public array $sources,
        public array $advantageKeys,
        public array $faqKeys,
    ) {
    }

    /** @return list<ComparisonRow> */
    public function rowsIn(ComparisonGroup $group): array
    {
        return array_values(array_filter(
            $this->rows,
            static fn (ComparisonRow $row): bool => $row->group === $group,
        ));
    }

    public function source(int $ref): ?ComparisonSource
    {
        foreach ($this->sources as $source) {
            if ($source->ref === $ref) {
                return $source;
            }
        }

        return null;
    }
}
