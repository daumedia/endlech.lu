<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\SavedList;

/**
 * Ergebnis einer Listen-Handlung: entweder gelungen (mit „wurde etwas geändert?“ und ggf. der
 * angelegten Liste) oder abgewiesen mit einem Meldungsschlüssel aus der Domain `validators`,
 * den der Controller als Fehler ans Namensfeld hängt (AK-17 bis AK-20).
 */
final readonly class SavedListResult
{
    public const string NAME_BLANK = 'saved.name_blank';
    public const string NAME_TOO_LONG = 'saved.name_too_long';
    public const string NAME_DUPLICATE = 'saved.name_duplicate';
    public const string LIMIT_REACHED = 'saved.limit_reached';

    private function __construct(
        public bool $changed,
        public ?SavedList $list,
        public ?string $error,
    ) {
    }

    public static function done(bool $changed, ?SavedList $list = null): self
    {
        return new self($changed, $list, null);
    }

    public static function rejected(string $error): self
    {
        return new self(false, null, $error);
    }

    public function isRejected(): bool
    {
        return null !== $this->error;
    }
}
