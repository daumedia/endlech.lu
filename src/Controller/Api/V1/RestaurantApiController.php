<?php

namespace App\Controller\Api\V1;

use App\Api\RestaurantTransformer;
use App\Entity\Restaurant;
use App\Entity\RestaurantSuggestion;
use App\Entity\User;
use App\Enum\Language;
use App\Enum\TriState;
use App\RateLimit\ActionLimiter;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private readonly TranslatorInterface $translator,
        // ⚠ BF-30: Am KONTO gezählt, nicht an der IP. Der Endpunkt fiel unter
        // `api_anonymous` (100/Minute) und füllte damit die Moderationsschlange
        // schneller, als ein Mensch sie leeren kann — nachgestellt: 40 Aufrufe,
        // 40 Vorschläge, alle 202. Derselbe Zähler wie der Browser-Weg (BF-50):
        // Es ist dieselbe Schlange, und der Deckel gehört an die Schlange.
        #[Autowire(service: 'limiter.suggestion_submit')]
        private readonly RateLimiterFactoryInterface $suggestionLimiter,
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

    /**
     * Nimmt einen Restaurantvorschlag entgegen.
     *
     * Legt bewusst KEIN Restaurant an, sondern einen `RestaurantSuggestion` — denselben
     * Datensatz, den auch der Web-Wizard erzeugt, und der dieselbe Freigabe durch einen
     * Admin durchläuft. Vorher entstand hier sofort ein öffentlicher Eintrag: Er stand
     * augenblicklich in der Restaurantliste, auf einer Detailseite, in den
     * veröffentlichten Kennzahlen von /open und im Datensatz unter CC BY 4.0 — ohne dass
     * jemand ihn gesehen hatte. Zwei Aufrufe genügten, um die veröffentlichte
     * Verifizierungsquote von 27,3 auf 23,1 Prozent zu drücken.
     */
    #[Route('', name: 'api_v1_restaurants_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Security(name: 'Bearer')]
    #[OA\Response(response: 202, description: 'Vorschlag angenommen, wartet auf Freigabe')]
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

        $location = (array) ($payload['location'] ?? []);
        if (($latError = $this->validateCoordinate($location['latitude'] ?? null, 90.0)) !== null) {
            $violations['latitude'] = $latError;
        }
        if (($lngError = $this->validateCoordinate($location['longitude'] ?? null, 180.0)) !== null) {
            $violations['longitude'] = $lngError;
        }

        // Die Küchen-Typen landen als Freitext im Vorschlag; die Spalte fasst 80
        // Zeichen. Ohne diese Prüfung schlug die Einfügung mit einem 500er aus der
        // Datenbankschicht fehl — und jeder davon erzeugt in Produktion einen
        // Sentry-Bericht.
        $cuisines = $this->cuisineNames($payload);
        if (mb_strlen($cuisines) > 80) {
            $violations['cuisines'] = 'Die Küchen-Typen dürfen zusammen höchstens 80 Zeichen lang sein.';
        }

        if ($violations !== []) {
            return new JsonResponse([
                'error' => ['code' => 422, 'message' => 'Validierung fehlgeschlagen.', 'violations' => $violations],
            ], 422);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Erst nach der Prüfung des Rumpfs (BF-11): Ein fehlerhafter Aufruf einer
        // App soll das Kontingent des Nutzers nicht verbrauchen.
        $limiter = ActionLimiter::for($this->suggestionLimiter, $user->getUserIdentifier());

        if (!$limiter->isAllowed()) {
            throw new TooManyRequestsHttpException(
                max(1, $limiter->retryAfter()),
                'Zu viele Vorschläge. Bitte später erneut versuchen.',
            );
        }

        $limiter->consume();

        $suggestion = new RestaurantSuggestion();
        $suggestion->setName($name);
        $suggestion->setCity($city);
        $suggestion->setCuisine($cuisines);
        $suggestion->setSuggestedBy($user);
        $suggestion->setLocale($request->getLocale());
        $suggestion->setStatus(RestaurantSuggestion::STATUS_PENDING);
        $this->applyOptionalData($suggestion, $payload);

        $this->entityManager->persist($suggestion);
        $this->entityManager->flush();

        // 202, nicht 201: Die Anfrage ist angenommen, aber die Ressource entsteht
        // erst mit der Freigabe durch einen Admin. Ein 201 mit Location-Header
        // behauptete etwas, das es noch nicht gibt.
        // ⚠ BF-31: Das Feld heißt `submissionId`, nicht `id`. Ein `id` im Rumpf
        // eines Restaurant-Endpunkts liest sich wie eine Restaurant-ID — ein Client,
        // der damit `GET /restaurants/{id}` aufruft, bekommt bei überlappenden
        // Zählern ein FREMDES Restaurant mit 200 zurück und zeigt es als das eigene
        // an. Der Name sagt jetzt, worauf sich die Zahl bezieht.
        return new JsonResponse([
            'status' => 'pending',
            'submissionId' => $suggestion->getId(),
            'message' => $this->translator->trans('api.moderation_pending'),
        ], 202);
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
    /**
     * Überträgt die optionalen Angaben auf den Vorschlag.
     *
     * Die dreiwertige Zuordnung ist kein Beiwerk: Der Vorschlag unterscheidet
     * „ja", „nein" und „weiß nicht" (siehe TriState), und für eine
     * Barrierefreiheits-Plattform ist der Unterschied zwischen „gibt es nicht" und
     * „wurde nicht angegeben" wesentlich. Vorher setzte diese Methode jedes nicht
     * übermittelte Merkmal auf `false` — daraus wurde ein „nein", das niemand
     * behauptet hatte.
     */
    private function applyOptionalData(RestaurantSuggestion $suggestion, array $payload): void
    {
        if (isset($payload['emoji']) && \is_string($payload['emoji']) && $payload['emoji'] !== '') {
            $suggestion->setEmoji(mb_substr($payload['emoji'], 0, 10));
        }

        $access = (array) ($payload['accessibility'] ?? []);
        $suggestion->setIsWheelchairAccessible($this->triState($access, 'wheelchairAccessible'));
        $suggestion->setHasAccessibleToilet($this->triState($access, 'accessibleToilet'));
        $suggestion->setAllowsAssistanceDogs($this->triState($access, 'assistanceDogs'));
        $suggestion->setHasBrightLighting($this->triState($access, 'brightLighting'));
        $suggestion->setHasChangingTable($this->triState($access, 'changingTable'));
        $suggestion->setHasDisabledParking($this->triState($access, 'disabledParking'));

        $dietary = (array) ($payload['dietary'] ?? []);
        $suggestion->setIsVegan($this->triState($dietary, 'vegan'));
        $suggestion->setIsVegetarian($this->triState($dietary, 'vegetarian'));
        $suggestion->setIsHalal($this->triState($dietary, 'halal'));

        $payment = (array) ($payload['payment'] ?? []);
        $suggestion->setAcceptsCash($this->triState($payment, 'cash'));
        $suggestion->setAcceptsCard($this->triState($payment, 'card'));
        $suggestion->setAcceptsPayconiq($this->triState($payment, 'payconiq'));

        $contact = (array) ($payload['contact'] ?? []);
        $suggestion->setPhone($this->nullableString($contact['phone'] ?? null));
        $suggestion->setEmail($this->nullableString($contact['email'] ?? null));
        $suggestion->setWebsite($this->nullableString($contact['website'] ?? null));
        $suggestion->setInstagramUrl($this->nullableString($contact['instagramUrl'] ?? null));
        $suggestion->setFacebookUrl($this->nullableString($contact['facebookUrl'] ?? null));
        $suggestion->setTiktokUrl($this->nullableString($contact['tiktokUrl'] ?? null));

        $location = (array) ($payload['location'] ?? []);
        $suggestion->setLatitude($this->coordinateString($location['latitude'] ?? null));
        $suggestion->setLongitude($this->coordinateString($location['longitude'] ?? null));
        $suggestion->setNearbyStopsNote($this->nullableString($location['nearbyStopsNote'] ?? null));

        // Nur gültige Sprachcodes übernehmen.
        $languages = array_values(array_filter(
            array_map(
                static fn ($code) => \is_string($code) ? Language::tryFrom($code) : null,
                (array) ($payload['spokenLanguages'] ?? []),
            ),
        ));
        if ($languages !== []) {
            $suggestion->setSpokenLanguages($languages);
        }

        $suggestion->setNotes($this->nullableString($payload['notes'] ?? null));
    }

    /**
     * Nicht übermittelt heißt „weiß nicht", nicht „nein".
     */
    private function triState(array $daten, string $feld): TriState
    {
        if (!\array_key_exists($feld, $daten) || $daten[$feld] === null) {
            return TriState::UNKNOWN;
        }

        return $daten[$feld] ? TriState::YES : TriState::NO;
    }

    /**
     * Die Küchen-Typen kommen als Liste und werden als Freitext abgelegt.
     *
     * Bewusst KEIN findOrCreateByName() mehr: Damit legte jeder Aufruf dauerhaft
     * neue Einträge in der öffentlichen Filterauswahl der Website an — gemessen
     * wurden „Pizzza" und „JETZT BEI UNS BESTELLEN 0900-123456". Welcher echte
     * Küchen-Typ gemeint ist, entscheidet jetzt der Admin bei der Freigabe.
     */
    private function cuisineNames(array $payload): string
    {
        $namen = array_values(array_filter(array_map(
            static fn ($name) => \is_string($name) ? trim($name) : '',
            (array) ($payload['cuisines'] ?? []),
        ), static fn (string $name) => $name !== ''));

        return implode(', ', $namen);
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
     * Prüft einen optionalen Koordinatenwert (String oder Zahl) auf Dezimalformat
     * und Wertebereich ±$max. Gibt eine Fehlermeldung zurück oder null, wenn ok/leer.
     */
    private function validateCoordinate(mixed $value, float $max): ?string
    {
        if (\is_string($value)) {
            $value = trim($value);
        }
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return 'Muss eine Dezimalzahl sein.';
        }
        $float = (float) $value;
        if ($float < -$max || $float > $max) {
            return \sprintf('Muss zwischen %s und %s liegen.', -$max, $max);
        }

        return null;
    }

    /**
     * Normalisiert eine (bereits validierte) Koordinate zur String-Form für die
     * DECIMAL-Spalte; akzeptiert auch numerische JSON-Werte. Ungültig/leer → null.
     */
    private function coordinateString(mixed $value): ?string
    {
        if (\is_string($value)) {
            $value = trim($value);
        }
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (string) $value;
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
