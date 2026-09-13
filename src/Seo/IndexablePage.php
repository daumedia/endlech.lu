<?php

declare(strict_types=1);

namespace App\Seo;

/**
 * Eine Seite, die Suchmaschinen angeboten wird — ohne Sprache (Feature 10).
 *
 * Die Sprache kommt erst beim Bilden der Adresse hinzu: Jede Seite existiert in
 * allen vier Sprachfassungen, und ein Eintrag je Sprache wäre eine Liste, die man
 * vierfach pflegt.
 */
final readonly class IndexablePage
{
    /**
     * @param array<string, string|int> $parameters Pfadparameter der Route, ohne `_locale`
     */
    public function __construct(
        public string $route,
        public array $parameters = [],
    ) {
    }
}
