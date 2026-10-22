<?php

declare(strict_types=1);

namespace App\Twig;

use App\Security\CspNonce;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `csp_nonce()` für Inline-Skripte im Seitenkopf — heute nur `importmap()` in base.html.twig.
 *
 * ⚠ Ein neues Inline-Skript bekommt die Nonce nicht von selbst. Wer eines anlegt, setzt
 * `nonce="{{ csp_nonce() }}"` — oder besser: legt es als Modul unter assets/ ab.
 */
final class CspExtension extends AbstractExtension
{
    public function __construct(private readonly CspNonce $nonce)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->nonce->fuerDieseAnfrage(...)),
        ];
    }
}
