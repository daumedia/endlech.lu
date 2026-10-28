<?php

declare(strict_types=1);

namespace App\Tests\Support\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorSource;

/**
 * Sponsoren für den Testlauf (Feature 15, Entscheidung #1).
 *
 * Ersetzt in `when@test` die Einträge im Quelltext. Vorgabe ist **leer** — so
 * bleiben die Zählprüfläufe anderer Features (Sitemap, Footer) unberührt, und
 * der leere Zustand ist der, den jede Seite ohne Zutun zeigt.
 *
 * ⚠ **`replace()` vor der ersten Anfrage rufen.** Der Testclient bootet den
 * Kernel vor jeder weiteren Anfrage neu; was danach gesetzt wird, sieht nur die
 * nächste Anfrage nicht mehr. Ein Prüflauf je Anfrage oder `disableReboot()`.
 */
final class InMemorySponsorSource implements SponsorSource
{
    /** @var list<Sponsor> */
    private array $entries = [];

    /** @var list<string> */
    private array $removed = [];

    /**
     * @param list<Sponsor> $entries
     * @param list<string>  $removed
     */
    public function replace(array $entries, array $removed = []): void
    {
        $this->entries = $entries;
        $this->removed = $removed;
    }

    public function entries(): array
    {
        return $this->entries;
    }

    public function removedSlugs(): array
    {
        return $this->removed;
    }
}
