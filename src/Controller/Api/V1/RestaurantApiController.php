<?php

namespace App\Controller\Api\V1;

use App\Api\RestaurantTransformer;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\Language;
use App\Repository\CuisineRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/restaurants')]
#[OA\Tag(name: 'Restaurants')]
final class RestaurantApiController extends AbstractController
{
    /** Maximale Seitengröße, um teure Antworten zu begrenzen. */
    private const MAX_LIMIT = 50;

    private const SORTS = ['rating', 'name', 'newest'];

    /** Bool-Filter, die 1:1 an RestaurantRepository::findPaginated() durchgereicht werden. */
    private const BOOL_FILTERS = [
        'verified', 'wheelchair', 'toilet', 'dogs', 'lighting', 'changing_table',
        'disabled_parking', 'open', 'vegan', 'vegetarian', 'halal',
    ];

    public function __construct(
        private readonly RestaurantRepository $restaurants,
        private readonly RestaurantTransformer $transformer,
        private readonly EntityManagerInterface $entityManager,
        private readonly CuisineRepository $cuisines,
    ) {
    }

    #[Route('', name: 'api_v1_restaurants_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_LIMIT, max(1, $request->query->getInt('limit', 20)));
        $sort = $request->query->getString('sort', 'rating');
        if (!\in_array($sort, self::SORTS, true)) {
            $sort = 'rating';
        }

        $filters = $this->extractFilters($request);

        $paginator = $this->restaurants->findPaginated($sort, $page, $limit, $filters);
        $total = \count($paginator);

        $data = array_map(
            fn (Restaurant $r) => $this->transformer->list($r),
            iterator_to_array($paginator),
        );

        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
                'sort' => $sort,
            ],
        ]);
    }

    #[Route('', name: 'api_v1_restaurants_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Security(name: 'Bearer')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => ['code' => 400, 'message' => 'Ungültiger JSON-Body.']], 400);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));

        $violations = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $violations['name'] = 'Der Name muss zwischen 2 und 150 Zeichen lang sein.';
        }
        if (mb_strlen($city) < 2 || mb_strlen($city) > 100) {
            $violations['city'] = 'Die Stadt muss zwischen 2 und 100 Zeichen lang sein.';
        }
        if ($violations !== []) {
            return new JsonResponse([
                'error' => ['code' => 422, 'message' => 'Validierung fehlgeschlagen.', 'violations' => $violations],
            ], 422);
        }

        /** @var User $user */
        $user = $this->getUser();

        $restaurant = new Restaurant();
        $restaurant->setName($name);
        $restaurant->setCity($city);
        $restaurant->setSubmittedBy($user);
        $restaurant->setIsVerified(false);
        $this->applyOptionalData($restaurant, $payload);

        $this->entityManager->persist($restaurant);
        $this->entityManager->flush();

        return new JsonResponse($this->transformer->detail($restaurant), 201);
    }

    #[Route('/{id}', name: 'api_v1_restaurants_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Restaurant $restaurant): JsonResponse
    {
        return new JsonResponse($this->transformer->detail($restaurant));
    }

    #[Route('/{id}/images', name: 'api_v1_restaurants_images', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function images(Restaurant $restaurant): JsonResponse
    {
        $data = array_map(
            fn ($image) => $this->transformer->image($image),
            $restaurant->getImages()->toArray(),
        );

        return new JsonResponse(['data' => $data]);
    }

    /**
     * Überträgt optionale Felder aus dem JSON-Body auf das neue Restaurant.
     * Spiegelt die Struktur der Detail-Antwort (verschachtelte Objekte).
     *
     * @param array<string, mixed> $payload
     */
    private function applyOptionalData(Restaurant $restaurant, array $payload): void
    {
        if (isset($payload['emoji']) && \is_string($payload['emoji']) && $payload['emoji'] !== '') {
            $restaurant->setEmoji(mb_substr($payload['emoji'], 0, 10));
        }

        $access = (array) ($payload['accessibility'] ?? []);
        $restaurant->setIsWheelchairAccessible((bool) ($access['wheelchairAccessible'] ?? false));
        $restaurant->setHasAccessibleToilet((bool) ($access['accessibleToilet'] ?? false));
        $restaurant->setAllowsAssistanceDogs((bool) ($access['assistanceDogs'] ?? false));
        $restaurant->setHasBrightLighting((bool) ($access['brightLighting'] ?? false));
        $restaurant->setHasChangingTable((bool) ($access['changingTable'] ?? false));
        $restaurant->setHasDisabledParking((bool) ($access['disabledParking'] ?? false));

        $dietary = (array) ($payload['dietary'] ?? []);
        $restaurant->setIsVegan((bool) ($dietary['vegan'] ?? false));
        $restaurant->setIsVegetarian((bool) ($dietary['vegetarian'] ?? false));
        $restaurant->setIsHalal((bool) ($dietary['halal'] ?? false));

        $payment = (array) ($payload['payment'] ?? []);
        $restaurant->setAcceptsCash((bool) ($payment['cash'] ?? false));
        $restaurant->setAcceptsCard((bool) ($payment['card'] ?? false));
        $restaurant->setAcceptsPayconiq((bool) ($payment['payconiq'] ?? false));

        $contact = (array) ($payload['contact'] ?? []);
        $restaurant->setPhone($this->nullableString($contact['phone'] ?? null));
        $restaurant->setEmail($this->nullableString($contact['email'] ?? null));
        $restaurant->setWebsite($this->nullableString($contact['website'] ?? null));
        $restaurant->setInstagramUrl($this->nullableString($contact['instagramUrl'] ?? null));
        $restaurant->setFacebookUrl($this->nullableString($contact['facebookUrl'] ?? null));
        $restaurant->setTiktokUrl($this->nullableString($contact['tiktokUrl'] ?? null));

        $location = (array) ($payload['location'] ?? []);
        $restaurant->setLatitude($this->nullableString($location['latitude'] ?? null));
        $restaurant->setLongitude($this->nullableString($location['longitude'] ?? null));
        $restaurant->setNearbyStopsNote($this->nullableString($location['nearbyStopsNote'] ?? null));

        // Nur gültige Sprachcodes übernehmen.
        $languages = array_values(array_filter(
            array_map(
                static fn ($code) => \is_string($code) ? Language::tryFrom($code) : null,
                (array) ($payload['spokenLanguages'] ?? []),
            ),
        ));
        if ($languages !== []) {
            $restaurant->setSpokenLanguages($languages);
        }

        // Küchen-Typen per Name (findet bestehende oder legt neue an).
        foreach ((array) ($payload['cuisines'] ?? []) as $cuisineName) {
            if (\is_string($cuisineName) && trim($cuisineName) !== '') {
                $restaurant->addCuisine($this->cuisines->findOrCreateByName(trim($cuisineName)));
            }
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Mappt Query-Parameter auf die Filter-Keys von findPaginated().
     *
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        $query = $request->query;
        $filters = [];

        foreach (self::BOOL_FILTERS as $key) {
            if ($query->getBoolean($key)) {
                $filters[$key] = true;
            }
        }

        $city = trim($query->getString('city', ''));
        if ($city !== '') {
            $filters['city'] = $city;
        }

        // cuisine[]=1&cuisine[]=2
        $cuisine = array_filter(array_map('intval', (array) $query->all('cuisine')));
        if ($cuisine !== []) {
            $filters['cuisine'] = array_values($cuisine);
        }

        // lang[]=de&lang[]=fr
        $lang = array_filter((array) $query->all('lang'), 'is_string');
        if ($lang !== []) {
            $filters['lang'] = array_values($lang);
        }

        return $filters;
    }
}
