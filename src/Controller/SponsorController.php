<?php

namespace App\Controller;

use App\Sponsor\SponsorDirectory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Übersicht und Einzelseiten der Sponsoren (Feature 15).
 *
 * Rein lesend, wie die Presseseite: kein Formular, keine Mail, kein
 * Datenbankzugriff — die Einträge stehen im Quelltext. Deshalb gibt es hier
 * weder ein Rate Limit noch eine Zugriffsprüfung (Spec, Katalogfragen 3 und 4).
 *
 * ⚠ **Ob ein Sponsor zu sehen ist, entscheidet `SponsorDirectory`, nicht das
 * Template.** Ein Sponsor, dessen Zeitraum erst morgen beginnt, ist hier eine
 * 404 (AK-23) — ein `{% if %}` in der Vorlage verbärge ihn nur und gäbe seine
 * Adresse trotzdem preis.
 */
final class SponsorController extends AbstractController
{
    #[Route('/sponsoren', name: 'app_sponsor_index', methods: ['GET'])]
    public function index(SponsorDirectory $sponsors): Response
    {
        return $this->render('sponsor/index.html.twig', [
            'active' => $sponsors->active(),
            'former' => $sponsors->former(),
        ]);
    }

    /**
     * ⚠ Das Requirement ist das Slug-Muster aus `SponsorRules` (EC-05). Eine
     * Adresse außerhalb des Musters ist keine Route und damit ebenfalls eine 404.
     */
    #[Route('/sponsoren/{slug}', name: 'app_sponsor_show', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'], methods: ['GET'])]
    public function show(string $slug, SponsorDirectory $sponsors): Response
    {
        // AK-43: Eine entfernte Adresse leitet dauerhaft auf die Übersicht — sie war
        // verlinkt und womöglich indexiert, eine 404 ließe den Besucher stehen.
        if ($sponsors->isRemoved($slug)) {
            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_MOVED_PERMANENTLY);
        }

        $sponsor = $sponsors->findVisible($slug);
        if (null === $sponsor) {
            throw $this->createNotFoundException();
        }

        return $this->render('sponsor/show.html.twig', [
            'sponsor' => $sponsor,
            'is_active' => $sponsors->isActive($sponsor),
        ]);
    }
}
