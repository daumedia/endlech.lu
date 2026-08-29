<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Eine hochgeladene Datei wurde abgelehnt.
 *
 * Trägt einen Übersetzungsschlüssel statt eines fertigen Satzes: Der Aufrufer
 * entscheidet, in welcher Sprache und in welchem Rahmen die Meldung erscheint.
 */
final class UploadRejectedException extends \RuntimeException
{
    /**
     * @param array<string, string|int> $parameters
     */
    public function __construct(
        public readonly string $transKey,
        public readonly array $parameters = [],
    ) {
        parent::__construct($transKey);
    }
}
