<?php

declare(strict_types=1);

namespace App\Sponsor;

/**
 * Woher die Sponsoren kommen (Feature 15, Entscheidung #1).
 *
 * In Produktion ist das `SponsorRegistry` — die Einträge im Quelltext. Im
 * Testlauf ersetzt `when@test` sie durch eine Quelle im Speicher, damit die
 * Prüfläufe nicht von der echten Liste abhängen: Die ist heute leer und ändert
 * sich mit jedem Vertrag. Die echte Liste prüft `SponsorRegistryTest`.
 */
interface SponsorSource
{
    /** @return list<Sponsor> */
    public function entries(): array;

    /**
     * Adressen entfernter Sponsoren (AK-43). Sie leiten dauerhaft auf die
     * Übersicht, statt eine 404 zu liefern.
     *
     * @return list<string>
     */
    public function removedSlugs(): array;
}
