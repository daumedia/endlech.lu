<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Form\RestaurantListsType;
use App\Form\RestaurantNoteType;
use App\Repository\SavedListEntryRepository;
use App\Repository\SavedListRepository;
use App\Saved\FavoriteService;
use App\Saved\RestaurantNoteService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Die Daten der Restaurant-Detailseite an einer Stelle (Feature 17, T13/T15).
 *
 * Die Detailseite wird nicht mehr nur von `RestaurantController::show()` gerendert, sondern
 * auch nach einer abgewiesenen Eingabe in „In Listen“ oder „Deine Notiz“ (422 mit Meldung am
 * Feld, AK-23, AK-35). Beide Wege brauchen dieselben Variablen — sonst fehlte der zweiten
 * Darstellung etwa der Nahverkehrsblock, ohne dass es jemand merkt.
 *
 * Die Formulare gehen als `FormInterface` hinaus, nicht als Ansicht: `AbstractController::render()`
 * setzt dann für ein abgeschicktes, ungültiges Formular selbst den Status 422.
 */
final readonly class RestaurantShowView
{
    public function __construct(
        private PublicTransportService $publicTransport,
        private FavoriteService $favorites,
        private FormFactoryInterface $forms,
        private SavedListRepository $lists,
        private SavedListEntryRepository $entries,
        private RestaurantNoteService $notes,
        #[Autowire('%app.mobiliteit_radius%')]
        private int $nearbyStopsRadius,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function variables(
        Restaurant $restaurant,
        ?User $user,
        ?FormInterface $listsForm = null,
        ?FormInterface $noteForm = null,
    ): array {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Luxembourg'));

        $nearbyStops = [];
        if ($restaurant->hasCoordinates()) {
            $nearbyStops = $this->publicTransport->findNearbyStops(
                $restaurant->getLatitude(),
                $restaurant->getLongitude(),
            );
        }

        return [
            'restaurant' => $restaurant,
            'todayDayOfWeek' => (int) $now->format('N'),
            'nearbyStops' => $nearbyStops,
            // Der Radius gehört in die Meldung: „keine Haltestelle gefunden" ohne
            // Angabe, in welchem Umkreis gesucht wurde, ist keine Information.
            'nearbyStopsRadius' => $this->nearbyStopsRadius,
            // Feature 17: Merkzustand, Listen und Notiz nur für das angemeldete Konto; ein Gast
            // sieht das leere Herz und sonst nichts (AK-07, AK-52).
            'isFavorite' => null !== $user && $this->favorites->isFavorite($user, $restaurant),
            'listsForm' => null === $user ? null : ($listsForm ?? $this->listsForm($user, $restaurant)),
            'noteForm' => null === $user ? null : ($noteForm ?? $this->noteForm($user, $restaurant)),
        ];
    }

    /**
     * „In Listen“ (AK-21): Häkchen je eigener Liste, vorbelegt mit den Listen, in denen das
     * Lokal schon steht, dazu das Feld „Neue Liste“.
     */
    public function listsForm(User $user, Restaurant $restaurant): FormInterface
    {
        return $this->forms->create(
            RestaurantListsType::class,
            ['lists' => $this->entries->findListIdsContaining($user, $restaurant), 'newList' => ''],
            ['lists' => $this->lists->findByUser($user)],
        );
    }

    public function noteForm(User $user, Restaurant $restaurant): FormInterface
    {
        return $this->forms->create(
            RestaurantNoteType::class,
            ['text' => $this->notes->textFor($user, $restaurant) ?? ''],
            ['restaurant_name' => $restaurant->getName()],
        );
    }
}
