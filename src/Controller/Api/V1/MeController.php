<?php

namespace App\Controller\Api\V1;

use App\Api\RestaurantTransformer;
use App\Api\UserTransformer;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantRepository;
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
    public function submissions(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = array_map(
            fn (Restaurant $r) => $this->restaurantTransformer->list($r),
            $this->restaurants->findBySubmitter($user),
        );

        return new JsonResponse(['data' => $data]);
    }
}
