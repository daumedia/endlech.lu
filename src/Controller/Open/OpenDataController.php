<?php

namespace App\Controller\Open;

use App\Entity\Restaurant;
use App\Open\AccessibilityScore;
use App\Open\CantonResolver;
use App\Open\OpenStatsService;
use App\Repository\MetricSnapshotRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Maschinenlesbare Ausgabe der Open-Startup-Daten.
 *
 * Sprachfrei geroutet (siehe `open_data`-Block in config/routes.yaml). Ein
 * `/de/open.json` wäre für einen Datenabruf sinnlos: Die Zahlen sind in jeder
 * Sprache dieselben, und ein Sprachpräfix zwänge jeden Abrufer, sich für eine
 * zu entscheiden – mit dem Ergebnis, dass zitierte URLs zufällig verteilt in
 * vier Varianten kursieren.
 *
 * Der Datensatz steht unter CC-BY 4.0. Absichtlich nicht enthalten sind
 * E-Mail-Adressen und Telefonnummern: Sie stehen zwar auf jeder Detailseite,
 * aber ein Sammelabzug davon ist eine Adressliste, kein Barrierefreiheits-
 * Datensatz.
 */
final class OpenDataController extends AbstractController
{
    private const string LICENCE = 'CC-BY-4.0';
    private const string LICENCE_URL = 'https://creativecommons.org/licenses/by/4.0/';

    /**
     * Dieselben Zahlen wie die gerenderte Seite – aus derselben Quelle, damit
     * beide nicht auseinanderlaufen können.
     */
    #[Route('/open.json', name: 'app_open_json', methods: ['GET'])]
    public function stats(OpenStatsService $stats, MetricSnapshotRepository $snapshots): JsonResponse
    {
        $trend = array_map(
            static fn ($snapshot) => [
                'month' => $snapshot->getMonthKey(),
                'restaurants' => $snapshot->getRestaurantCount(),
                'verified' => $snapshot->getVerifiedCount(),
                'communesCovered' => $snapshot->getCommunesCovered(),
                'cantonsCovered' => $snapshot->getCantonsCovered(),
                'averageAccessibilityScore' => (float) $snapshot->getAverageAccessibilityScore(),
                'stepFreeEntrances' => $snapshot->getStepFreeEntrances(),
                'accessibleRestrooms' => $snapshot->getAccessibleRestrooms(),
                'wideDoors' => $snapshot->getWideDoors(),
                'wheelchairTableSpacing' => $snapshot->getWheelchairTableSpacing(),
                'inclusionBoxesDelivered' => $snapshot->getInclusionBoxesDelivered(),
                'totalExpenses' => (float) $snapshot->getTotalExpenses(),
            ],
            $snapshots->findTrend(24),
        );

        return $this->cached(new JsonResponse([
            ...$stats->all(),
            'trend' => $trend,
            'licence' => self::LICENCE,
            'licenceUrl' => self::LICENCE_URL,
            'generatedAt' => (new \DateTimeImmutable())->format(\DATE_ATOM),
        ]));
    }

    #[Route('/open/dataset.json', name: 'app_open_dataset_json', methods: ['GET'])]
    public function datasetJson(RestaurantRepository $repository, CantonResolver $cantons): JsonResponse
    {
        $rows = array_map(
            fn (Restaurant $restaurant) => $this->row($restaurant, $cantons),
            $repository->findAllForExport(),
        );

        return $this->cached(new JsonResponse([
            'licence' => self::LICENCE,
            'licenceUrl' => self::LICENCE_URL,
            'attribution' => 'Endlech.lu',
            'generatedAt' => (new \DateTimeImmutable())->format(\DATE_ATOM),
            'count' => \count($rows),
            'data' => $rows,
        ]));
    }

    #[Route('/open/dataset.csv', name: 'app_open_dataset_csv', methods: ['GET'])]
    public function datasetCsv(RestaurantRepository $repository, CantonResolver $cantons): Response
    {
        $restaurants = $repository->findAllForExport();
        $handle = fopen('php://temp', 'r+');

        if (false === $handle) {
            throw new \RuntimeException('Temporärer Datenstrom für den CSV-Export nicht verfügbar.');
        }

        $first = $restaurants[0] ?? null;
        $columns = array_keys($this->row($first ?? new Restaurant(), $cantons));
        fputcsv($handle, $columns, ',', '"', '');

        foreach ($restaurants as $restaurant) {
            fputcsv($handle, array_map(
                static fn ($value) => self::csvSafe(match (true) {
                    null === $value => '',
                    \is_bool($value) => $value ? 'true' : 'false',
                    \is_array($value) => implode('|', $value),
                    default => $value,
                }),
                $this->row($restaurant, $cantons),
            ), ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $response = new Response(false === $csv ? '' : $csv);
        // Kein UTF-8-BOM: Es würde Excel erfreuen, aber im ersten Spaltennamen
        // jedes gewöhnlichen CSV-Parsers als Müllzeichen landen.
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="endlech-accessibility-dataset.csv"');
        $response->headers->set('X-Licence', self::LICENCE);

        return $this->cached($response);
    }

    /**
     * Entschärft Werte, die eine Tabellenkalkulation als Formel liest.
     *
     * ⚠ BF-43: Ein Restaurantname mit führendem `=` stand unverändert im CSV und
     * wurde von Excel, LibreOffice und Google Sheets beim Öffnen ausgeführt. Die
     * CSV-Struktur selbst hielt — der Angriff zielt nicht auf den Parser, sondern
     * auf das Programm dahinter.
     *
     * Die Zielgruppe des Datensatzes sind Ministerien und Forschende; die einzige
     * Hürde davor war bisher die Moderation. Das ist zu wenig für eine Datei, die
     * unter CC BY 4.0 zum Herunterladen einlädt.
     *
     * Ein vorangestelltes Apostroph ist die Empfehlung von OWASP: Der Wert bleibt
     * lesbar, die Formel wird zu Text. Betroffen sind `= + - @` sowie Tab und
     * Wagenrücklauf — letztere, weil manche Programme sie als Zeilenanfang werten.
     */
    private static function csvSafe(string $wert): string
    {
        if ('' === $wert) {
            return $wert;
        }

        return \in_array($wert[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$wert : $wert;
    }

    /**
     * Eine Zeile des offenen Datensatzes.
     *
     * @return array<string, mixed>
     */
    private function row(Restaurant $restaurant, CantonResolver $cantons): array
    {
        return [
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'city' => $restaurant->getCity(),
            'commune' => $cantons->resolveCommune($restaurant->getCity()),
            'canton' => $cantons->resolveCanton($restaurant->getCity())?->label(),
            'latitude' => $restaurant->getLatitude(),
            'longitude' => $restaurant->getLongitude(),
            'cuisines' => array_map(
                static fn ($cuisine) => $cuisine->getName(),
                $restaurant->getCuisines()->toArray(),
            ),
            'website' => $restaurant->getWebsite(),
            'isVerified' => $restaurant->isVerified(),
            'verifiedAt' => $restaurant->getVerifiedAt()?->format('Y-m-d'),
            'accessibilityScore' => AccessibilityScore::forRestaurant($restaurant),
            'stepFreeEntrance' => $restaurant->isWheelchairAccessible(),
            'accessibleRestroom' => $restaurant->hasAccessibleToilet(),
            'assistanceDogsWelcome' => $restaurant->allowsAssistanceDogs(),
            'brightLighting' => $restaurant->hasBrightLighting(),
            'changingTable' => $restaurant->hasChangingTable(),
            'disabledParking' => $restaurant->hasDisabledParking(),
            'doorWidthCm' => $restaurant->getDoorWidthCm(),
            'tableSpacingCm' => $restaurant->getTableSpacingCm(),
            'createdAt' => $restaurant->getCreatedAt()->format('Y-m-d'),
        ];
    }

    /**
     * Eine Stunde öffentlich cachebar – passend zur TTL der Kennzahlen. Ohne
     * den Header holt sich jeder Abrufer bei jedem Aufruf den vollen
     * Datensatz, obwohl er sich höchstens stündlich ändert.
     */
    private function cached(Response $response): Response
    {
        $response->setPublic();
        $response->setMaxAge(OpenStatsService::TTL);

        // Ohne diesen Marker überschreibt Symfonys Session-Listener die
        // Cache-Header mit "private, must-revalidate", sobald irgendwo im
        // Request eine Session angefasst wurde – und der öffentliche Cache
        // oben wäre wirkungslos. Hier ist nichts nutzerspezifisch: Diese
        // Antworten sehen für jeden Abrufer gleich aus.
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');

        return $response;
    }
}
