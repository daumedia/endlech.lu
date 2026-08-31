<?php

namespace App\Controller;

use App\Roadmap\ChangelogRegistry;
use App\Roadmap\CommunityRoadmap;
use App\Roadmap\RoadmapRegistry;
use App\Roadmap\RoadmapStage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Die beiden öffentlichen Seiten von Feature 07: Roadmap und Changelog.
 *
 * Rein lesend. Es entsteht kein Datensatz, es wird nichts entgegengenommen, und es
 * wird kein fremder Server kontaktiert — damit gibt es hier weder ein Rate Limit
 * noch eine Zugriffsprüfung.
 *
 * ⚠ **Der Controller liest keinen Query-Parameter** (AK-44). Was in der Adresse
 * hinter dem Pfad steht, ändert an der ausgelieferten Seite nichts; es gibt keinen
 * Filter, keine Sortierung und keine Kennung. Damit existiert auch kein IDOR-Weg.
 *
 * ⚠ **Beide Routen sind auf GET beschränkt** (AK-11). Jede andere Methode
 * beantwortet Symfony mit 405, bevor eine Zeile hier läuft — das ist billiger und
 * verlässlicher als eine eigene Prüfung.
 *
 * ⚠ **Die Seiten sind öffentlich, weil keine `access_control`-Regel auf sie passt**
 * — nicht, weil eine Regel sie freigibt. `security.yaml` hat keinen Catch-all.
 * Bewusst keine eigene Zeile ergänzt: Eine einzelne explizite Regel für ein
 * einzelnes Feature ließe `/about`, `/partner`, `/open`, `/vergleich` und `/presse`
 * so aussehen, als seien sie anders behandelt. Dieselbe Begründung steht im Kopf
 * von `PressController`.
 *
 * ⚠ **Die Spalte „Geplant" mischt zwei Quellen.** Die kuratierten Vorhaben stehen
 * im Code, die Community-Ideen kommen live aus dem Board — deshalb kann eine dort
 * zurückgezogene Idee hier nicht stehen bleiben (AK-18).
 *
 * Die sprachfreien Kurzlinks `/roadmap` und `/changelog` liegen in
 * `config/routes.yaml`, wie bei `/open`, `/vergleich` und `/presse`.
 */
final class RoadmapController extends AbstractController
{
    public function __construct(
        private readonly RoadmapRegistry $registry,
        private readonly CommunityRoadmap $community,
        private readonly ChangelogRegistry $changelog,
    ) {
    }

    #[Route('/roadmap', name: 'app_roadmap_index', methods: ['GET'])]
    public function index(): Response
    {
        $spalten = [];
        foreach (RoadmapStage::cases() as $stage) {
            $spalten[] = [
                'stage' => $stage,
                'items' => $this->registry->itemsFor($stage),
            ];
        }

        return $this->render('roadmap/index.html.twig', [
            'columns' => $spalten,
            'community' => $this->community->planned(),
            'shelved' => $this->registry->shelved(),
            'lastChange' => $this->changelog->latestShownDate(),
            'plannedStage' => RoadmapStage::PLANNED,
        ]);
    }

    #[Route('/changelog', name: 'app_changelog_index', methods: ['GET'])]
    public function changelog(): Response
    {
        return $this->render('roadmap/changelog.html.twig', [
            'years' => $this->changelog->byYear(),
            'currentYear' => (new \DateTimeImmutable())->format('Y'),
            'lastChange' => $this->changelog->latestShownDate(),
        ]);
    }
}
