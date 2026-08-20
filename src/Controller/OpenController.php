<?php

namespace App\Controller;

use App\Entity\MetricSnapshot;
use App\Open\OpenStatsService;
use App\Repository\MetricSnapshotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Öffentliche Transparenzseite: Plattform, Wirkung, Finanzen.
 *
 * Endlech.lu verlangt von Restaurants, ihre Barrierefreiheit offenzulegen.
 * Diese Seite wendet denselben Maßstab auf das Projekt selbst an – und ist
 * zugleich die Antwort auf die Frage, die vor jedem Fördergespräch,
 * Sponsoring und Gemeindekontakt gestellt wird.
 *
 * Die maschinenlesbaren Gegenstücke (/open.json, Datensatz-Downloads) liegen
 * bewusst in einem eigenen, sprachfreien Controller.
 */
final class OpenController extends AbstractController
{
    #[Route('/open', name: 'app_open', methods: ['GET'])]
    public function index(OpenStatsService $stats, MetricSnapshotRepository $snapshots): Response
    {
        $trend = $snapshots->findTrend(12);
        $data = $stats->all();

        return $this->render('open/index.html.twig', [
            'stats' => $data,
            'trend' => $trend,
            // Ein einzelner Punkt ist kein Verlauf. Die Grafiken erscheinen
            // erst ab zwei Monaten, statt eine Linie zu zeichnen, die
            // zwangsläufig waagerecht ist und nichts aussagt.
            'hasTrend' => \count($trend) >= 2,
            'deltas' => $this->deltas($data['platform'], end($trend) ?: null),
        ]);
    }

    /**
     * Veränderung gegenüber dem zuletzt festgehaltenen Monat.
     *
     * Der Bezugspunkt ist der Snapshot, nicht „vor 30 Tagen": Nur er ist ein
     * Stand, den jemand nachprüfen kann. Steht noch keiner in der Datenbank,
     * gibt es keine Deltas – eine Veränderung gegen einen unbekannten
     * Ausgangswert wäre erfunden.
     *
     * @param array<string, mixed> $platform
     *
     * @return array<string, array{value: int|float, since: string}>
     */
    private function deltas(array $platform, ?MetricSnapshot $reference): array
    {
        if (null === $reference) {
            return [];
        }

        $since = $reference->getMonthKey();

        return [
            'restaurants' => ['value' => $platform['restaurants'] - $reference->getRestaurantCount(), 'since' => $since],
            'verified' => ['value' => $platform['verified'] - $reference->getVerifiedCount(), 'since' => $since],
            'communesCovered' => ['value' => $platform['communesCovered'] - $reference->getCommunesCovered(), 'since' => $since],
            'averageScore' => ['value' => round($platform['averageScore'] - (float) $reference->getAverageAccessibilityScore(), 2), 'since' => $since],
        ];
    }
}
