<?php

namespace App\Waitlist;

use Symfony\Component\HttpFoundation\Request;

/**
 * Kleine Helfer, die sich beide Wartelisten-Formulare teilen.
 */
final class WaitlistRequestHelper
{
    /** Spaltenbreite von `source` in beiden Tabellen. */
    private const SOURCE_MAX_LENGTH = 60;

    /**
     * UTM-Quelle, sonst Referrer-Host. Auf harmlose Zeichen reduziert und
     * gekürzt – ein unbrauchbarer Wert wird verworfen, führt aber nie zur
     * Ablehnung der Anmeldung.
     */
    public static function resolveSource(Request $request): ?string
    {
        $source = $request->query->getString('utm_source');

        if ('' === $source) {
            $referer = $request->headers->get('referer');
            $source = $referer ? (parse_url($referer, \PHP_URL_HOST) ?: '') : '';
        }

        $source = preg_replace('/[^A-Za-z0-9_.\-]/', '', trim($source)) ?? '';

        return '' === $source ? null : mb_substr($source, 0, self::SOURCE_MAX_LENGTH);
    }
}
