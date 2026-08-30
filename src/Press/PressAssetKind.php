<?php

namespace App\Press;

/**
 * Die fünf Bestandteile des Materialpakets.
 *
 * ⚠ **Fünf Fälle, und `PressRegistryTest` verlangt jeden genau einmal.** Damit
 * kann das Paket nicht stillschweigend ohne Dunkelvariante ausgeliefert werden —
 * genau der Fall, den AK-18 abdeckt. Eine Redaktion, die auf dunklem Grund
 * layoutet, hat sonst die Wahl zwischen einem unsichtbaren Logo und einem, das
 * sie selbst umfärbt; das Zweite verbieten die Nutzungsbedingungen.
 */
enum PressAssetKind: string
{
    case WORDMARK_LIGHT = 'wordmark_light';
    case WORDMARK_DARK = 'wordmark_dark';
    case SYMBOL_LIGHT = 'symbol_light';
    case SYMBOL_DARK = 'symbol_dark';
    case PORTRAIT = 'portrait';

    /** Textschlüssel der Variantenbezeichnung, Domain `press`. */
    public function labelKey(): string
    {
        return 'material.'.$this->value;
    }

    /**
     * Trägt dieser Bestandteil das Bild einer Person?
     *
     * Steuert die Pflicht zum Fotocredit (AK-24): Ein Bild ohne Urheberangabe
     * ist nicht freigabefähig, und wer es trotzdem druckt, hat ein Problem, das
     * dieses Presse-Kit verursacht hat.
     */
    public function isPortrait(): bool
    {
        return self::PORTRAIT === $this;
    }
}
