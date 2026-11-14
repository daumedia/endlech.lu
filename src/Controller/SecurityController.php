<?php

namespace App\Controller;

use App\Repository\RestaurantRepository;
use App\Saved\PendingFavorite;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        PendingFavorite $pending,
        RestaurantRepository $restaurants,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'merkwunsch' => $this->merkwunsch($request, $pending, $restaurants),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Handled by Symfony security firewall
    }

    /**
     * Feature 17, AK-08: Name des Lokals, das ein Gast per Herz merken wollte — für den Hinweis
     * über dem Formular, solange der Wunsch besteht (auch nach einem Fehlversuch, AK-10).
     *
     * Nur gelesen, nicht gelöscht: eingelöst wird im `PendingFavoriteSubscriber`. Gibt es das
     * Lokal nicht mehr, entfällt der Hinweis (EC-03). Ohne bestehende Sitzung kann es keinen
     * Wunsch geben — dann wird auch keine angelegt.
     */
    private function merkwunsch(Request $request, PendingFavorite $pending, RestaurantRepository $restaurants): ?string
    {
        if (!$request->hasPreviousSession()) {
            return null;
        }

        $id = $pending->peek($request->getSession());

        return null === $id ? null : $restaurants->find($id)?->getName();
    }
}
