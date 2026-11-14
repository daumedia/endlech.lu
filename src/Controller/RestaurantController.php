<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\Language;
use App\Repository\CuisineRepository;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use App\Saved\SavedState;
use App\Service\RestaurantShowView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RestaurantController extends AbstractController
{
    private const LIMIT = 6;

    #[Route('/restaurants', name: 'app_restaurant_index')]
    public function index(
        Request $request,
        RestaurantRepository $restaurantRepository,
        CuisineRepository $cuisineRepository,
        FavoriteRepository $favoriteRepository,
        SavedState $savedState,
    ): Response {
        $sort = $request->query->getString('sort', 'rating');
        if (!in_array($sort, ['rating', 'name', 'newest'], true)) {
            $sort = 'rating';
        }

        $page = max(1, $request->query->getInt('page', 1));

        // ⚠ BF-214: Die Prüfung „Seite hinter dem Ende → 404“ (BF-60, unten) kommt erst nach der
        // Abfrage. Eine Seite, deren Versatz `(page - 1) * LIMIT` keine ganze Zahl mehr ist, gibt
        // es nie — sie lief in `setFirstResult()` zu `float` über (TypeError, HTTP 500).
        if ($page > intdiv(\PHP_INT_MAX, self::LIMIT)) {
            throw $this->createNotFoundException('Diese Seite gibt es nicht.');
        }

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

        // Feature 17, AK-38/AK-39: „Nur meine Favoriten“ wirkt nur mit angemeldetem Konto. Ein Gast
        // mit `?favorites=1` (weitergegebener Link) sieht die gewöhnliche Liste, ohne Meldung.
        // ⚠ Das Konto kommt aus der Sitzung, nie aus der Anfrage — sonst läse ein Parameter fremde
        // Favoriten (AK-52). Deshalb steht es nur in den Filtern fürs Repository, nicht im Template.
        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;
        $favoritesOf = null !== $user && $request->query->getBoolean('favorites', false) ? $user : null;
        $filters['favorites'] = null !== $favoritesOf;

        $paginator = $restaurantRepository->findPaginated($sort, $page, self::LIMIT, $filters + ['favorites_of' => $favoritesOf]);
        $total = count($paginator);
        $lastPage = max(1, (int) ceil($total / self::LIMIT));

        // ⚠ BF-60: Eine Seite jenseits des Endes ist keine leere Seite, sondern
        // keine Seite. Vorher lieferte `?page=99999` eine 200 mit leerer Liste —
        // beliebig viele indexierbare Adressen mit identischem, leerem Inhalt.
        // Seite 1 bleibt erreichbar, auch wenn der Bestand leer ist.
        if ($page > $lastPage && $page > 1) {
            throw $this->createNotFoundException('Diese Seite gibt es nicht.');
        }

        // ⚠ Ein Doctrine-`Paginator` fragt bei jedem Durchlauf erneut: einmal auslesen, dieselbe
        // Liste an `SavedState` und ans Template. Gezählt wird weiter über `count($paginator)`.
        $karten = iterator_to_array($paginator, false);

        // AK-40: Nur bei gesetztem Filter und leerer Trefferliste fragen, ob das Konto überhaupt
        // etwas gemerkt hat — bei Treffern ist die Antwort ohnehin „ja“. `null` = nicht gefragt.
        $hasAnyFavorites = null !== $favoritesOf && 0 === $total
            ? $favoriteRepository->countByUser($favoritesOf) > 0
            : null;

        return $this->render('restaurant/index.html.twig', [
            'restaurants' => $karten,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'sort' => $sort,
            'filters' => $filters,
            'languages' => Language::cases(),
            'allCuisines' => $cuisineRepository->findAllSorted(),
            // AK-01, AK-52: Merkzustand nur des eigenen Kontos, Gäste bekommen eine leere Seite.
            'savedPage' => $savedState->forRestaurants($user, $karten),
            'hasAnyFavorites' => $hasAnyFavorites,
        ]);
    }

    #[Route('/restaurants/{id}', name: 'app_restaurant_show', requirements: ['id' => '\d+'])]
    public function show(Restaurant $restaurant, RestaurantShowView $view): Response
    {
        // Feature 17: Die Variablen der Detailseite stehen in `RestaurantShowView`, weil auch
        // `RestaurantSavedController` die Seite nach einer abgewiesenen Eingabe rendert.
        $user = $this->getUser();

        return $this->render('restaurant/show.html.twig', $view->variables($restaurant, $user instanceof User ? $user : null));
    }
}
