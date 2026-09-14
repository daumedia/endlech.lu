<?php

declare(strict_types=1);

namespace App\Usage;

/**
 * Ein Zählaufruf verletzt eine Regel der Weiterleitung und wird nicht weitergereicht (Feature 11).
 *
 * Die Meldung nennt nur die verletzte **Regel**, nie den eingegangenen Wert — sonst stünde genau
 * das, was die Prüfung fernhalten soll (Ortstext, Token, Adresse), in einer Fehlermeldung.
 */
final class InvalidCollectPayload extends \InvalidArgumentException
{
}
