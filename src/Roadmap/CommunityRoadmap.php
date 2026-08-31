<?php

namespace App\Roadmap;

use App\Entity\BoardIdea;
use App\Repository\BoardIdeaRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Die geplanten Ideen aus dem Community-Board für die öffentliche Roadmap
 * (Feature 07).
 *
 * Der einzige Teil dieses Features, der die Datenbank anfasst.
 *
 * ⚠ **Die Rückgabe besteht aus reinen Skalaren, nicht aus Entitäten.** Dieselbe
 * Struktur geht durch den Zwischenspeicher und durch Twig; eine Entität im Cache
 * wäre vom Entity-Manager losgelöst und verhielte sich auf beiden Wegen
 * unterschiedlich. Dieselbe Begründung wie bei `OpenStatsService`.
 *
 * ⚠ **`submittedBy` wird nicht gelesen und nicht weitergereicht.** Der Verfasser
 * einer Idee erscheint auf der Roadmap nirgends (AK-39) — das Datenpaket führt das
 * Feld strukturell nicht, es kann also auch kein Template versehentlich ausgeben.
 */
final readonly class CommunityRoadmap
{
    /**
     * Höchstens so viele Ideen erscheinen in der Spalte „Geplant" (AK-17).
     *
     * Die Grenze wirkt **in der Abfrage**, nicht in der Darstellung: Damit lädt
     * kein Aufruf je den gesamten Bestand, und der Deckel, den `CLAUDE.md` für
     * solche Wege verlangt, ist an der Ursache gesetzt statt am Besucher.
     */
    public const int MAX_ITEMS = 10;

    private const string CACHE_KEY = 'community_planned';

    public function __construct(
        private BoardIdeaRepository $ideas,
        #[Autowire(service: 'cache.roadmap')]
        private CacheInterface&CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @return array{ideas: list<array{id: int, title: string, slug: string, locale: string, votes: int}>, more: int}
     */
    public function planned(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return $this->compute();
        });
    }

    /**
     * Wirft das zwischengespeicherte Ergebnis weg.
     *
     * Gerufen von `RoadmapCacheListener`, sobald sich eine Idee, eine Stimme oder
     * ein Konto ändert (AK-18, AK-47).
     */
    public function invalidate(): void
    {
        $this->cache->clear();
    }

    /**
     * @return array{ideas: list<array{id: int, title: string, slug: string, locale: string, votes: int}>, more: int}
     */
    private function compute(): array
    {
        $gefunden = $this->ideas->findPublishedPlanned(self::MAX_ITEMS);
        $gesamt = $this->ideas->countPublishedPlanned();

        $stimmen = $this->ideas->countVotesFor(array_map(
            static fn (BoardIdea $idea): int => (int) $idea->getId(),
            $gefunden,
        ));

        $zeilen = [];
        foreach ($gefunden as $idea) {
            $id = (int) $idea->getId();
            $zeilen[] = [
                'id' => $id,
                'title' => $idea->getTitle(),
                'slug' => $idea->getSlug(),
                'locale' => $idea->getLocale(),
                'votes' => $stimmen[$id] ?? 0,
            ];
        }

        return [
            'ideas' => $zeilen,
            'more' => max(0, $gesamt - \count($zeilen)),
        ];
    }
}
