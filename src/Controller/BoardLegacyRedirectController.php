<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Die alten Adressen des Boards (Feature 06) unter `/community/ideen` — seit Feature 16
 * liegt es als Reiter „Ideen" unter `/roadmap/ideen`.
 *
 * ⚠ **301, nicht 302 wie die sprachfreien Kurzlinks** (design.md Entscheidung 3). Ein
 * Kurzlink führt je Besucher in eine andere Sprache; ein gespeicherter 301 wäre dort
 * falsch. Hier hängt das Ziel nur an der Adresse selbst (`/de/…` → `/de/…`), und nur
 * eine dauerhafte Weiterleitung lässt die neue Adresse die alte im Suchindex ablösen.
 *
 * ⚠ **Das Ziel kommt immer aus einem festen Routennamen** (AK-24). Kein
 * Abfrageparameter wird gelesen oder weitergereicht; von der Einzelseite gehen nur
 * Nummer und Titelteil mit, beide durch das Routen-Requirement eingeschränkt.
 *
 * ⚠ **Keine Datenbankabfrage.** Ob eine Nummer existiert oder auf Freigabe wartet,
 * entscheidet erst das Ziel (404) — die Weiterleitung verrät es nicht und lädt keinen
 * Bestand (design.md Entscheidung 5). Eine Dublette kostet dadurch einen zweiten Sprung.
 *
 * Die wichtigsten Kunden sind Links in bereits verschickten Mails (AK-22). Wer diesen
 * Controller entfernt, macht jeden davon zu einer 404.
 *
 * Keine POST-Routen für Zustimmen/Zurückziehen: Die Spec sagt sie nicht zu (Decision
 * Log 12), ein solcher Aufruf endet in 404.
 */
#[Route('/community/ideen', methods: ['GET'])]
final class BoardLegacyRedirectController extends AbstractController
{
    #[Route('', name: 'app_board_legacy_index')]
    public function index(Request $request): RedirectResponse
    {
        return $this->dauerhaft('app_board_index', $request);
    }

    #[Route('/neu', name: 'app_board_legacy_new')]
    public function new(Request $request): RedirectResponse
    {
        return $this->dauerhaft('app_board_new', $request);
    }

    #[Route('/eingereicht', name: 'app_board_legacy_thanks')]
    public function thanks(Request $request): RedirectResponse
    {
        return $this->dauerhaft('app_board_thanks', $request);
    }

    #[Route('/{id}-{slug}', name: 'app_board_legacy_show', requirements: ['id' => '\d+', 'slug' => '[^/]*'])]
    public function show(Request $request, string $id, string $slug): RedirectResponse
    {
        return $this->dauerhaft('app_board_show', $request, ['id' => $id, 'slug' => $slug]);
    }

    /**
     * @param array<string, string> $parameter
     */
    private function dauerhaft(string $route, Request $request, array $parameter = []): RedirectResponse
    {
        // `_locale` ausdrücklich übergeben (wie `LocaleRedirectController`): Die Sprache der
        // alten Adresse ist die des Ziels, nicht die der Sitzung.
        return $this->redirectToRoute($route, ['_locale' => $request->getLocale()] + $parameter, Response::HTTP_MOVED_PERMANENTLY);
    }
}
