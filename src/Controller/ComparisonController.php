<?php

namespace App\Controller;

use App\Comparison\ComparisonFigures;
use App\Comparison\ComparisonRegistry;
use App\Comparison\Competitor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Öffentliche Vergleichsseiten: Endlech.lu gegenüber anderen Anlaufstellen.
 *
 * Rein lesend. Es entsteht kein Datensatz, es wird nichts entgegengenommen, und
 * es wird kein fremder Server kontaktiert — die Quellen stehen als Text in den
 * Fußnoten. Damit gibt es hier weder ein Rate Limit noch eine Zugriffsprüfung.
 *
 * ⚠ **Die Seiten sind öffentlich, weil keine `access_control`-Regel auf sie
 * passt** — nicht, weil eine Regel sie freigibt. `security.yaml` hat keinen
 * Catch-all; gedeckt sind nur `admin`, `profile`, `register`, `login`, `verify`
 * und die Passwort-Pfade. Das gilt genauso für `/about`, `/partner` und `/open`.
 * Bewusst keine eigene Zeile ergänzt: Eine einzelne explizite Regel für ein
 * einzelnes Feature ließe die übrigen öffentlichen Seiten so aussehen, als seien
 * sie anders behandelt.
 *
 * Der sprachfreie Kurzlink `/vergleich` liegt in `config/routes.yaml`, wie bei
 * `/open`.
 */
#[Route('/vergleich')]
final class ComparisonController extends AbstractController
{
    public function __construct(
        private readonly ComparisonRegistry $registry,
        private readonly ComparisonFigures $figures,
    ) {
    }

    #[Route('', name: 'app_comparison_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('comparison/index.html.twig', [
            'pages' => $this->registry->all(),
        ]);
    }

    /**
     * Eine Vergleichsseite.
     *
     * ⚠ **Der Slug wird zweimal geprüft**, und das ist Absicht: Das Requirement
     * hält alles ab, was nicht zu den bekannten Werten gehört; `fromSlug()` fängt
     * den Fall, dass jemand später das Requirement erweitert und das Enum
     * vergisst. Beides ergibt 404 — nie die Übersicht und nie eine leere Seite.
     *
     * ⚠ **Das Requirement muss von Hand mit dem Enum synchron gehalten werden.**
     * PHP-Attribute lassen nur konstante Ausdrücke zu; `Competitor::slugPattern()`
     * kann hier also nicht stehen. Wer einen Vergleich hinzufügt oder streicht,
     * ändert deshalb zwei Stellen. Damit das nicht schiefgeht, vergleicht
     * `ComparisonControllerTest` das Muster dieser Route mit dem Enum — genau
     * dieser Abgleich hat beim Streichen von Jaccede gegriffen.
     */
    #[Route('/{slug}', name: 'app_comparison_show', methods: ['GET'], requirements: ['slug' => 'google-maps|wheelmap|tripadvisor'])]
    public function show(string $slug): Response
    {
        $competitor = Competitor::fromSlug($slug);

        if (!$competitor) {
            throw $this->createNotFoundException();
        }

        return $this->render('comparison/show.html.twig', [
            'page' => $this->registry->page($competitor),
            'others' => array_values(array_filter(
                Competitor::cases(),
                static fn (Competitor $c): bool => $c !== $competitor,
            )),
            'figures' => $this->figures->all(),
        ]);
    }
}
