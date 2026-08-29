<?php

namespace App\Controller\Api\V1;

use App\Api\RestaurantTransformer;
use App\Api\UserTransformer;
use App\Entity\Restaurant;
use App\Entity\RestaurantSuggestion;
use App\Entity\User;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/me')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[OA\Tag(name: 'Me')]
#[Security(name: 'Bearer')]
final class MeController extends AbstractController
{
    public function __construct(
        private readonly UserTransformer $userTransformer,
        private readonly RestaurantTransformer $restaurantTransformer,
        private readonly RestaurantRepository $restaurants,
        private readonly RestaurantSuggestionRepository $suggestions,
    ) {
    }

    #[Route('', name: 'api_v1_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse($this->userTransformer->profile($user));
    }

    #[Route('/submissions', name: 'api_v1_me_submissions', methods: ['GET'])]
    /**
     * Eigene Einreichungen — freigegebene UND wartende.
     *
     * ⚠ BF-32: Vorher standen hier nur die freigegebenen Restaurants. Seit BF-24
     * legt `POST /restaurants` einen Vorschlag an und kein Restaurant mehr; eine
     * Einreichung war damit für den Einreicher unsichtbar, bis ein Admin sie
     * freigab — und wenn er sie ablehnte, für immer.
     *
     * Beide Arten in einer Liste, unterschieden durch `state`: Ein Client, der
     * „Meine Beiträge" anzeigt, braucht genau das und nicht zwei Endpunkte.
     */
    public function submissions(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $freigegeben = array_map(
            fn (Restaurant $r) => ['state' => 'published'] + $this->restaurantTransformer->list($r),
            $this->restaurants->findBySubmitter($user),
        );

        $wartend = array_map(
            static fn (RestaurantSuggestion $s) => [
                'state' => match ($s->getStatus()) {
                    RestaurantSuggestion::STATUS_APPROVED => 'approved',
                    RestaurantSuggestion::STATUS_REJECTED => 'rejected',
                    default => 'pending',
                },
                'id' => $s->getId(),
                'name' => $s->getName(),
                'city' => $s->getCity(),
                'emoji' => $s->getEmoji(),
                'submittedAt' => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            $this->suggestions->findBySuggester($user),
        );

        return new JsonResponse(['data' => array_merge($freigegeben, $wartend)]);
    }
}
