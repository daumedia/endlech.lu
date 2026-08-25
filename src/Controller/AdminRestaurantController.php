<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Form\RestaurantType;
use App\Repository\RestaurantImageRepository;
use App\Repository\RestaurantRepository;
use App\Service\ImageUploadService;
use App\Service\UploadRejectedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminRestaurantController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /** Zeilen je Seite in der Verwaltungsliste (BF-52). */
    private const ADMIN_PAGE_SIZE = 25;

    #[Route('/restaurants', name: 'admin_restaurant_index')]
    public function index(Request $request, RestaurantRepository $restaurantRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $suche = trim($request->query->getString('q', ''));

        $paginator = $restaurantRepository->findForAdmin($page, self::ADMIN_PAGE_SIZE, $suche);
        $total = \count($paginator);
        $lastPage = max(1, (int) ceil($total / self::ADMIN_PAGE_SIZE));

        if ($page > $lastPage && $page > 1) {
            throw $this->createNotFoundException('Diese Seite gibt es nicht.');
        }

        return $this->render('admin/restaurant/index.html.twig', [
            'restaurants' => $paginator,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'suche' => $suche,
        ]);
    }

    #[Route('/restaurants/neu', name: 'admin_restaurant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $restaurant = new Restaurant();
        $form = $this->createForm(RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($restaurant);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.restaurant_created'));

            return $this->redirectToRoute('admin_restaurant_index');
        }

        return $this->render('admin/restaurant/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/restaurants/{id}/bearbeiten', name: 'admin_restaurant_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Restaurant $restaurant, Request $request, EntityManagerInterface $entityManager): Response
    {
        $wasVerified = $restaurant->isVerified();
        $form = $this->createForm(RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNowVerified = $restaurant->isVerified();
            if ($isNowVerified && !$wasVerified) {
                $restaurant->setVerifiedAt(new \DateTimeImmutable());
                $restaurant->setVerifiedBy($this->getUser());
            } elseif (!$isNowVerified && $wasVerified) {
                $restaurant->setVerifiedAt(null);
                $restaurant->setVerifiedBy(null);
            }

            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.restaurant_updated'));

            return $this->redirectToRoute('admin_restaurant_index');
        }

        return $this->render('admin/restaurant/edit.html.twig', [
            'restaurant' => $restaurant,
            'form' => $form,
        ]);
    }

    #[Route('/restaurants/{id}/verifizieren', name: 'admin_restaurant_toggle_verified', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleVerified(Restaurant $restaurant, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle-verified-' . $restaurant->getId(), $request->request->getString('_token'))) {
            if ($restaurant->isVerified()) {
                $restaurant->setIsVerified(false);
                $restaurant->setVerifiedAt(null);
                $restaurant->setVerifiedBy(null);
                $this->addFlash('success', $this->translator->trans('flash.verification_revoked', ['%name%' => $restaurant->getName()]));
            } else {
                $restaurant->setIsVerified(true);
                $restaurant->setVerifiedAt(new \DateTimeImmutable());
                $restaurant->setVerifiedBy($this->getUser());
                $this->addFlash('success', $this->translator->trans('flash.verification_granted', ['%name%' => $restaurant->getName()]));
            }
            $em->flush();
        } else {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));
        }

        return $this->redirectToRoute('admin_restaurant_index');
    }

    #[Route('/restaurants/{id}/loeschen', name: 'admin_restaurant_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Restaurant $restaurant, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-restaurant-' . $restaurant->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($restaurant);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.restaurant_deleted'));
        } else {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));
        }

        return $this->redirectToRoute('admin_restaurant_index');
    }

    #[Route('/restaurants/{id}/fotos', name: 'admin_restaurant_image_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadImage(Restaurant $restaurant, Request $request, ImageUploadService $imageUploadService): Response
    {
        // ⚠ BF-58: Überschreitet der Upload `post_max_size`, verwirft PHP den GESAMTEN
        // Body — Dateien, Formularfelder und CSRF-Token. Ohne diese Abfrage sähe der
        // Admin „Ungültiges CSRF-Token" und suchte an einer Stelle, an der nichts
        // kaputt ist. `CONTENT_LENGTH` überlebt als Header.
        if ([] === $request->request->all() && [] === $request->files->all() && $request->server->get('CONTENT_LENGTH') > 0) {
            $this->addFlash('error', $this->translator->trans('flash.upload_exceeds_server_limit', [
                '%limit%' => (string) \ini_get('post_max_size'),
            ]));

            return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
        }

        if (!$this->isCsrfTokenValid('upload-images-' . $restaurant->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
        }

        $files = $request->files->get('images', []);
        $altText = $request->request->getString('altText', '');
        $uploaded = 0;
        $abgelehnt = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            try {
                $imageUploadService->upload($file, $restaurant, $altText);
                ++$uploaded;
            } catch (UploadRejectedException $e) {
                // ⚠ BF-57: Der Dienst lehnt alles ab, was kein Bild ist. Die Meldung
                // nennt die betroffene Datei — bei einem Mehrfach-Upload wäre sonst
                // unklar, welche gemeint ist.
                $abgelehnt[] = $this->translator->trans($e->transKey, $e->parameters + [
                    '%file%' => $file->getClientOriginalName(),
                ]);
            }
        }

        if ($uploaded > 0) {
            $this->addFlash('success', $this->translator->trans('flash.photo_uploaded', ['%count%' => $uploaded]));
        }

        foreach ($abgelehnt as $meldung) {
            $this->addFlash('error', $meldung);
        }

        if (0 === $uploaded && [] === $abgelehnt) {
            $this->addFlash('error', $this->translator->trans('flash.no_valid_files'));
        }

        return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
    }

    #[Route('/restaurants/{id}/fotos/{imageId}/loeschen', name: 'admin_restaurant_image_delete', requirements: ['id' => '\d+', 'imageId' => '\d+'], methods: ['POST'])]
    public function deleteImage(Restaurant $restaurant, int $imageId, Request $request, RestaurantImageRepository $imageRepository, ImageUploadService $imageUploadService): Response
    {
        if (!$this->isCsrfTokenValid('delete-image-' . $imageId, $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
        }

        $image = $imageRepository->find($imageId);
        if ($image && $image->getRestaurant() === $restaurant) {
            $imageUploadService->delete($image);
            $this->addFlash('success', $this->translator->trans('flash.photo_deleted'));
        } else {
            $this->addFlash('error', $this->translator->trans('flash.photo_not_found'));
        }

        return $this->redirectToRoute('admin_restaurant_edit', ['id' => $restaurant->getId()]);
    }

    #[Route('/restaurants/{id}/fotos/sortieren', name: 'admin_restaurant_image_sort', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sortImages(Restaurant $restaurant, Request $request, RestaurantImageRepository $imageRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->isCsrfTokenValid('sort-images-' . $restaurant->getId(), $data['_token'] ?? '')) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $imageIds = $data['imageIds'] ?? [];
        foreach ($imageIds as $sortOrder => $imageId) {
            $image = $imageRepository->find($imageId);
            if (!$image || $image->getRestaurant() !== $restaurant) {
                return new JsonResponse(['error' => 'Image does not belong to this restaurant'], Response::HTTP_BAD_REQUEST);
            }
            $image->setSortOrder($sortOrder);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
