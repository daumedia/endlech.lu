<?php

namespace App\Controller\Api;

use App\Entity\Cuisine;
use App\Repository\CuisineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/cuisines')]
#[IsGranted('ROLE_ADMIN')]
final class CuisineApiController extends AbstractController
{
    /**
     * Spaltenbreite von `cuisine.name` (VARCHAR 80).
     *
     * ⚠ BF-62: Ohne diese Prüfung landete ein zu langer Name als
     * `SQLSTATE[22001] Data too long` in einem 500er — dritte Wiederholung
     * desselben Musters nach BF-27 und BF-51. Der Endpunkt hängt am
     * Tom-Select-Feld des Admin-Formulars: Ein Admin, der einen langen Namen
     * eingibt, bekam einen Serverfehler statt einer Meldung, und in `prod` einen
     * Sentry-Bericht dazu.
     */
    private const MAX_NAME_LENGTH = 80;

    #[Route('/search', name: 'api_cuisine_search', methods: ['GET'])]
    public function search(Request $request, CuisineRepository $cuisineRepository): JsonResponse
    {
        $query = trim($request->query->getString('q', ''));
        $cuisines = $query !== '' ? $cuisineRepository->search($query) : $cuisineRepository->findAllSorted();

        $data = array_map(fn ($c) => ['id' => $c->getId(), 'name' => $c->getName()], $cuisines);

        return $this->json($data);
    }

    #[Route('', name: 'api_cuisine_create', methods: ['POST'])]
    public function create(Request $request, CuisineRepository $cuisineRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $name = trim($payload['name'] ?? '');

        if ($name === '') {
            return $this->json(['error' => 'Name is required'], 400);
        }

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            return $this->json([
                'error' => sprintf('Name must not exceed %d characters.', self::MAX_NAME_LENGTH),
            ], 422);
        }

        $cuisine = $cuisineRepository->findOrCreateByName($name);
        $entityManager->flush();

        return $this->json(['id' => $cuisine->getId(), 'name' => $cuisine->getName()], 201);
    }
}
