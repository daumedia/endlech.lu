<?php

namespace App\Controller;

use App\Service\AdminStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(AdminStatsService $stats): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'restaurantCount' => $stats->getRestaurantCount(),
            'verifiedCount' => $stats->getVerifiedCount(),
            'pendingSuggestionCount' => $stats->getPendingSuggestionCount(),
            'userCount' => $stats->getUserCount(),
            'imageCount' => $stats->getImageCount(),
            'restaurantsThisMonth' => $stats->getRestaurantsAddedThisMonth(),
            'usersThisMonth' => $stats->getUsersRegisteredThisMonth(),
            'recentRestaurants' => $stats->getRecentRestaurants(5),
            'recentUsers' => $stats->getRecentUsers(5),
        ]);
    }
}
