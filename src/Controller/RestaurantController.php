<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Enum\Language;
use App\Repository\CuisineRepository;
use App\Repository\RestaurantRepository;
use App\Service\PublicTransportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RestaurantController extends AbstractController
{
    private const LIMIT = 6;

    #[Route('/restaurants', name: 'app_restaurant_index')]
    public function index(Request $request, RestaurantRepository $restaurantRepository, CuisineRepository $cuisineRepository): Response
    {
        $sort = $request->query->getString('sort', 'rating');
        if (!in_array($sort, ['rating', 'name', 'newest'], true)) {
            $sort = 'rating';
        }

        $page = max(1, $request->query->getInt('page', 1));

        $langValues = [];
        foreach (Language::cases() as $lang) {
            if ($request->query->getBoolean('lang_'.$lang->value, false)) {
                $langValues[] = $lang->value;
            }
        }

        $filters = [
            'verified'   => $request->query->getBoolean('verified', false),
            'wheelchair' => $request->query->getBoolean('wheelchair', false),
            'toilet'     => $request->query->getBoolean('toilet', false),
            'dogs'       => $request->query->getBoolean('dogs', false),
            'lighting'       => $request->query->getBoolean('lighting', false),
            'changing_table'   => $request->query->getBoolean('changing_table', false),
            'disabled_parking' => $request->query->getBoolean('disabled_parking', false),
            'open'             => $request->query->getBoolean('open', false),
            'vegan'      => $request->query->getBoolean('vegan', false),
            'vegetarian' => $request->query->getBoolean('vegetarian', false),
            'halal'      => $request->query->getBoolean('halal', false),
            'city'       => trim($request->query->getString('city', '')),
            'cuisine'    => array_filter(array_map('intval', $request->query->all('cuisine'))),
            'lang'       => $langValues,
        ];

        $paginator = $restaurantRepository->findPaginated($sort, $page, self::LIMIT, $filters);
        $total = count($paginator);
        $lastPage = max(1, (int) ceil($total / self::LIMIT));

        // ⚠ BF-60: Eine Seite jenseits des Endes ist keine leere Seite, sondern
        // keine Seite. Vorher lieferte `?page=99999` eine 200 mit leerer Liste —
        // beliebig viele indexierbare Adressen mit identischem, leerem Inhalt.
        // Seite 1 bleibt erreichbar, auch wenn der Bestand leer ist.
        if ($page > $lastPage && $page > 1) {
            throw $this->createNotFoundException('Diese Seite gibt es nicht.');
        }

        return $this->render('restaurant/index.html.twig', [
            'restaurants' => $paginator,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'sort' => $sort,
            'filters' => $filters,
            'languages' => Language::cases(),
            'allCuisines' => $cuisineRepository->findAllSorted(),
        ]);
    }

    #[Route('/restaurants/{id}', name: 'app_restaurant_show', requirements: ['id' => '\d+'])]
    public function show(
        Restaurant $restaurant,
        PublicTransportService $publicTransportService,
        #[Autowire('%app.mobiliteit_radius%')]
        int $nearbyStopsRadius,
    ): Response {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Luxembourg'));

        $nearbyStops = [];
        if ($restaurant->hasCoordinates()) {
            $nearbyStops = $publicTransportService->findNearbyStops(
                $restaurant->getLatitude(),
                $restaurant->getLongitude(),
            );
        }

        return $this->render('restaurant/show.html.twig', [
            'restaurant' => $restaurant,
            'todayDayOfWeek' => (int) $now->format('N'),
            'nearbyStops' => $nearbyStops,
            // Der Radius gehört in die Meldung: „keine Haltestelle gefunden" ohne
            // Angabe, in welchem Umkreis gesucht wurde, ist keine Information.
            'nearbyStopsRadius' => $nearbyStopsRadius,
        ]);
    }
}
