<?php

namespace App\Open;

use App\Entity\Restaurant;
use App\Enum\Canton;
use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use App\Repository\FinanceEntryRepository;
use App\Repository\RestaurantRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Liefert die Zahlen der Open-Startup-Seite.
 *
 * Alle Rückgaben sind reine Arrays aus Skalaren. Das ist kein Zufall: Dieselbe
 * Struktur geht durch den Cache, in die Twig-Vorlage, nach /open.json und in
 * den Monats-Snapshot. Enum-Instanzen oder Entities darin würden je nach Weg
 * unterschiedlich behandelt und die vier Ausgaben früher oder später
 * auseinanderlaufen lassen – genau das, was eine Transparenzseite nicht
 * überlebt.
 */
final class OpenStatsService
{
    /**
     * Eine Stunde. Kurz genug, dass ein neu freigeschaltetes Restaurant am
     * selben Vormittag auftaucht; lang genug, dass ein verlinkter Beitrag die
     * Aggregatabfragen nicht bei jedem Aufruf auslöst.
     */
    public const int TTL = 3600;

    private const string KEY_PLATFORM = 'open_stats.platform';
    private const string KEY_IMPACT = 'open_stats.impact';
    private const string KEY_FINANCE = 'open_stats.finance';

    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly FinanceEntryRepository $financeRepository,
        private readonly CantonResolver $cantonResolver,
        #[Autowire(service: 'cache.open_stats')]
        private readonly CacheInterface&CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function platform(): array
    {
        return $this->cache->get(self::KEY_PLATFORM, function (ItemInterface $item): array {
            $item->expiresAfter(self::TTL);

            return $this->computePlatform();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function impact(): array
    {
        return $this->cache->get(self::KEY_IMPACT, function (ItemInterface $item): array {
            $item->expiresAfter(self::TTL);

            return $this->computeImpact();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function finance(): array
    {
        return $this->cache->get(self::KEY_FINANCE, function (ItemInterface $item): array {
            $item->expiresAfter(self::TTL);

            return $this->computeFinance();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'platform' => $this->platform(),
            'impact' => $this->impact(),
            'finance' => $this->finance(),
        ];
    }

    /**
     * Wirft den Cache weg. Der Admin ruft das nach jeder Änderung an den
     * Finanzdaten auf – eine Zahl, die nach dem Speichern noch eine Stunde
     * alt aussieht, führt sonst zu einem zweiten, korrigierenden Eintrag.
     */
    public function invalidate(): void
    {
        $this->cache->clear();
    }

    /**
     * Berechnet ohne Cache – für den Monats-Snapshot, der einen definierten
     * Stand einfriert und dabei keinen bis zu eine Stunde alten Zwischenstand
     * gebrauchen kann.
     *
     * @return array<string, mixed>
     */
    public function computeAll(): array
    {
        return [
            'platform' => $this->computePlatform(),
            'impact' => $this->computeImpact(),
            'finance' => $this->computeFinance(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function computePlatform(): array
    {
        $rows = $this->restaurantRepository->findMetricRows();

        $total = \count($rows);
        $verified = 0;
        $scoreSum = 0;
        // ⚠ BF-67: Häuser ohne jede Erhebung stehen NICHT im Durchschnitt und nicht
        // in der Verteilung. Sie bekamen vorher eine glatte Null und zogen damit
        // die veröffentlichte Zahl nach unten, während sie zugleich die
        // Gemeindeabdeckung hoben. Sie erscheinen jetzt als eigene Zahl.
        $unscored = 0;
        $scored = 0;
        $distribution = array_fill(0, AccessibilityScore::MAX + 1, 0);
        $communes = [];
        $cantons = [];
        $unassigned = 0;

        foreach ($rows as $row) {
            $isVerified = (bool) $row['isVerified'];
            $verified += $isVerified ? 1 : 0;

            if ([] === ($row['assessedFeatures'] ?? [])) {
                ++$unscored;
            } else {
                $score = AccessibilityScore::fromFlags($this->flagsFromRow($row));
                $scoreSum += $score;
                ++$scored;
                ++$distribution[$score];
            }

            $commune = $this->cantonResolver->resolveCommune((string) $row['city']);

            if (null === $commune) {
                ++$unassigned;
                continue;
            }

            $canton = $this->cantonResolver->resolveCanton((string) $row['city']);

            $communes[$commune] ??= ['restaurants' => 0, 'verified' => 0, 'canton' => $canton];
            ++$communes[$commune]['restaurants'];
            $communes[$commune]['verified'] += $isVerified ? 1 : 0;

            if ($canton) {
                $cantons[$canton->value] ??= ['restaurants' => 0, 'verified' => 0, 'communes' => []];
                ++$cantons[$canton->value]['restaurants'];
                $cantons[$canton->value]['verified'] += $isVerified ? 1 : 0;
                $cantons[$canton->value]['communes'][$commune] = true;
            }
        }

        $totalCommunes = $this->cantonResolver->totalCommunes();

        return [
            'restaurants' => $total,
            'verified' => $verified,
            'verifiedShare' => $this->share($verified, $total),
            'unassigned' => $unassigned,
            'communesCovered' => \count($communes),
            'totalCommunes' => $totalCommunes,
            'communeCoverage' => $this->share(\count($communes), $totalCommunes),
            'cantonsCovered' => \count($cantons),
            'totalCantons' => \count(Canton::cases()),
            'averageScore' => $scored > 0 ? round($scoreSum / $scored, 2) : 0.0,
            'scoredRestaurants' => $scored,
            'unscoredRestaurants' => $unscored,
            'maxScore' => AccessibilityScore::MAX,
            'scoreDistribution' => $distribution,
            'byCanton' => $this->cantonRows($cantons),
            'byCommune' => $this->communeRows($communes),
        ];
    }

    /**
     * Alle zwölf Kantone erscheinen, auch die ohne einen einzigen Eintrag.
     * Die weißen Flecken sind auf einer Abdeckungsseite die interessantere
     * Hälfte der Aussage.
     *
     * @param array<string, array{restaurants: int, verified: int, communes: array<string, true>}> $cantons
     *
     * @return list<array<string, mixed>>
     */
    private function cantonRows(array $cantons): array
    {
        $rows = [];

        foreach (Canton::cases() as $canton) {
            $data = $cantons[$canton->value] ?? ['restaurants' => 0, 'verified' => 0, 'communes' => []];
            $covered = \count($data['communes']);

            $rows[] = [
                'canton' => $canton->value,
                'label' => $canton->label(),
                'restaurants' => $data['restaurants'],
                'verified' => $data['verified'],
                'communesCovered' => $covered,
                'communeTotal' => $canton->communeCount(),
                'communeCoverage' => $this->share($covered, $canton->communeCount()),
            ];
        }

        usort($rows, static fn (array $a, array $b) => [$b['restaurants'], $a['label']] <=> [$a['restaurants'], $b['label']]);

        return $rows;
    }

    /**
     * @param array<string, array{restaurants: int, verified: int, canton: Canton|null}> $communes
     *
     * @return list<array<string, mixed>>
     */
    private function communeRows(array $communes): array
    {
        $rows = [];

        foreach ($communes as $name => $data) {
            $rows[] = [
                'commune' => $name,
                'canton' => $data['canton']?->value,
                'cantonLabel' => $data['canton']?->label(),
                'restaurants' => $data['restaurants'],
                'verified' => $data['verified'],
            ];
        }

        usort($rows, static fn (array $a, array $b) => [$b['restaurants'], $a['commune']] <=> [$a['restaurants'], $b['commune']]);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeImpact(): array
    {
        $rows = $this->restaurantRepository->findMetricRows();

        $counts = [
            'stepFreeEntrances' => 0,
            'accessibleRestrooms' => 0,
            'assistanceDogsWelcome' => 0,
            'brightLighting' => 0,
            'changingTables' => 0,
            'disabledParking' => 0,
        ];

        $wideDoors = 0;
        $documentedDoorWidths = 0;
        $wheelchairTableSpacing = 0;
        $documentedTableSpacing = 0;

        foreach ($rows as $row) {
            $counts['stepFreeEntrances'] += $row['isWheelchairAccessible'] ? 1 : 0;
            $counts['accessibleRestrooms'] += $row['hasAccessibleToilet'] ? 1 : 0;
            $counts['assistanceDogsWelcome'] += $row['allowsAssistanceDogs'] ? 1 : 0;
            $counts['brightLighting'] += $row['hasBrightLighting'] ? 1 : 0;
            $counts['changingTables'] += $row['hasChangingTable'] ? 1 : 0;
            $counts['disabledParking'] += $row['hasDisabledParking'] ? 1 : 0;

            if (null !== $row['doorWidthCm']) {
                ++$documentedDoorWidths;
                $wideDoors += $row['doorWidthCm'] >= Restaurant::MIN_DOOR_WIDTH_CM ? 1 : 0;
            }

            if (null !== $row['tableSpacingCm']) {
                ++$documentedTableSpacing;
                $wheelchairTableSpacing += $row['tableSpacingCm'] >= Restaurant::MIN_TABLE_SPACING_CM ? 1 : 0;
            }
        }

        return [
            ...$counts,
            'wideDoors' => $wideDoors,
            'documentedDoorWidths' => $documentedDoorWidths,
            'wheelchairTableSpacing' => $wheelchairTableSpacing,
            'documentedTableSpacing' => $documentedTableSpacing,
            'minDoorWidthCm' => Restaurant::MIN_DOOR_WIDTH_CM,
            'minTableSpacingCm' => Restaurant::MIN_TABLE_SPACING_CM,
            'inclusionBoxesDelivered' => $this->financeRepository->sumQuantity(FinanceCategory::INCLUSION_BOX_MATERIALS),
            'total' => \count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function computeFinance(): array
    {
        $now = new \DateTimeImmutable();
        $expenses = $this->financeRepository->sumByCategory(FinanceType::EXPENSE);
        $earliestIncome = $this->financeRepository->findEarliestDate(FinanceType::INCOME);
        $incomeVisibleFrom = $this->incomeVisibleFrom($earliestIncome);
        $incomeVisible = null !== $incomeVisibleFrom && $incomeVisibleFrom <= $now;

        $totalExpenses = $this->financeRepository->sumByType(FinanceType::EXPENSE);
        $totalIncome = $this->financeRepository->sumByType(FinanceType::INCOME);

        return [
            'expenses' => $this->categoryRows($expenses, $totalExpenses),
            'totalExpenses' => round($totalExpenses, 2),
            // Die Einnahmenseite wird strukturell verschwiegen, nicht nur
            // ausgeblendet: Wären die Zahlen im Array und nur im Template
            // hinter einer Bedingung, stünden sie trotzdem in /open.json.
            'incomeVisible' => $incomeVisible,
            'income' => $incomeVisible
                ? $this->categoryRows($this->financeRepository->sumByCategory(FinanceType::INCOME), $totalIncome)
                : [],
            'totalIncome' => $incomeVisible ? round($totalIncome, 2) : null,
            'balance' => $incomeVisible ? round($totalIncome - $totalExpenses, 2) : null,
            'incomeVisibleFrom' => $incomeVisibleFrom?->format('Y-m-d'),
            'lastUpdatedAt' => $this->financeRepository->findLastUpdatedAt()?->format(\DATE_ATOM),
            'currency' => 'EUR',
        ];
    }

    /**
     * @param array<string, array{category: FinanceCategory, total: float, count: int, quantity: int}> $sums
     *
     * @return list<array<string, mixed>>
     */
    private function categoryRows(array $sums, float $total): array
    {
        $rows = [];

        foreach ($sums as $row) {
            $rows[] = [
                'category' => $row['category']->value,
                'label' => $row['category']->label(),
                'transKey' => $row['category']->transKey(),
                'emoji' => $row['category']->emoji(),
                'total' => round($row['total'], 2),
                'entries' => $row['count'],
                'share' => $this->share($row['total'], $total),
            ];
        }

        usort($rows, static fn (array $a, array $b) => $b['total'] <=> $a['total']);

        return $rows;
    }

    /**
     * Datum, ab dem Einnahmen veröffentlicht werden: der Tag nach Ablauf des
     * Kalenderquartals, in dem der erste Einnahmeposten liegt.
     *
     * Hintergrund: Eine Einnahmenzeile nahe null schreckt Partner ab, statt
     * Vertrauen zu schaffen. Erst ein vollständiges Quartal zeigt, ob eine
     * Zahl Signal oder Zufall ist. Null bedeutet: noch keine Einnahmen erfasst.
     */
    private function incomeVisibleFrom(?\DateTimeImmutable $earliest): ?\DateTimeImmutable
    {
        if (null === $earliest) {
            return null;
        }

        $quarterEndMonth = (int) ceil((int) $earliest->format('n') / 3) * 3;

        return $earliest
            ->setDate((int) $earliest->format('Y'), $quarterEndMonth, 1)
            ->modify('last day of this month')
            ->modify('+1 day')
            ->setTime(0, 0);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<bool>
     */
    private function flagsFromRow(array $row): array
    {
        return [
            (bool) $row['isWheelchairAccessible'],
            (bool) $row['hasAccessibleToilet'],
            (bool) $row['allowsAssistanceDogs'],
            (bool) $row['hasBrightLighting'],
            (bool) $row['hasChangingTable'],
            (bool) $row['hasDisabledParking'],
            null !== $row['doorWidthCm'] && $row['doorWidthCm'] >= Restaurant::MIN_DOOR_WIDTH_CM,
            null !== $row['tableSpacingCm'] && $row['tableSpacingCm'] >= Restaurant::MIN_TABLE_SPACING_CM,
        ];
    }

    private function share(float $part, float $total): float
    {
        return $total > 0.0 ? round($part / $total * 100, 1) : 0.0;
    }
}
