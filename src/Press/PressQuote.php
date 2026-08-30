<?php

namespace App\Press;

/**
 * Ein freigegebenes Zitat, das eine Redaktion ohne Rückfrage übernehmen darf.
 *
 * ⚠ Der Name steht als fester Text im Quelltext und **nicht** im
 * Übersetzungskatalog — dasselbe Muster wie die Wortmarken in
 * `App\Comparison\Competitor`. Eigennamen werden nicht übersetzt, und ein
 * übersetzbarer Name lädt zu einer Schreibweise ein, die in einer der vier
 * Sprachfassungen falsch ist und die niemand bemerkt.
 */
final readonly class PressQuote
{
    /**
     * @param string $textKey    Wortlaut des Zitats, Domain `press`
     * @param string $personName Name der zitierten Person, fester Text
     * @param string $roleKey    Funktion der Person, Domain `press`
     */
    public function __construct(
        public string $textKey,
        public string $personName,
        public string $roleKey,
    ) {
    }
}
