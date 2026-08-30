<?php

namespace App\Controller;

use App\Press\PressFacts;
use App\Press\PressPackage;
use App\Press\PressRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Die öffentliche Presseseite: Material für eine Redaktion an einer Stelle.
 *
 * Rein lesend. Es entsteht kein Datensatz, es wird nichts entgegengenommen, und
 * es wird kein fremder Server kontaktiert — damit gibt es hier weder ein Rate
 * Limit noch eine Zugriffsprüfung.
 *
 * ⚠ **Der Controller liest keinen Query-Parameter** (AK-39). Was in der Adresse
 * hinter dem Pfad steht, ändert an der ausgelieferten Seite nichts; es gibt
 * keinen Filter, keine Sortierung und keine Kennung.
 *
 * ⚠ **Die Seite ist öffentlich, weil keine `access_control`-Regel auf sie
 * passt** — nicht, weil eine Regel sie freigibt. `security.yaml` hat keinen
 * Catch-all; gedeckt sind nur `admin`, `profile`, `register`, `login`, `verify`,
 * die Passwort-Pfade und der Marketing-Webhook. Das gilt genauso für `/about`,
 * `/partner`, `/open` und `/vergleich`. Bewusst keine eigene Zeile ergänzt: Eine
 * einzelne explizite Regel für ein einzelnes Feature ließe die übrigen
 * öffentlichen Seiten so aussehen, als seien sie anders behandelt.
 *
 * ⚠ **Das Materialpaket hat keine Route.** Es liegt als Datei unter
 * `public/presse/` und wird vom Webserver direkt ausgeliefert; der
 * Front-Controller sieht es nie, weil `public/.htaccess` nur Anfragen
 * weiterleitet, für die keine Datei existiert. Deshalb ist hier nichts zu
 * deckeln — es wird nichts gerechnet (AK-40).
 *
 * Der sprachfreie Kurzlink `/presse` liegt in `config/routes.yaml`, wie bei
 * `/open` und `/vergleich`.
 */
final class PressController extends AbstractController
{
    public function __construct(
        private readonly PressRegistry $registry,
        private readonly PressFacts $facts,
        private readonly PressPackage $package,
    ) {
    }

    #[Route('/presse', name: 'app_press_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('press/index.html.twig', [
            'boilerplates' => $this->registry->boilerplates(),
            'assets' => $this->registry->assets(),
            'quotes' => $this->registry->quotes(),
            'releases' => $this->registry->releases(),
            'facts' => $this->facts->all(),
            'package' => $this->package,
            'founder' => PressRegistry::FOUNDER_NAME,
        ]);
    }
}
