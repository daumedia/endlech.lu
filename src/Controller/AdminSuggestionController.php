<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\RestaurantSuggestion;
use App\Repository\CuisineRepository;
use App\Repository\RestaurantSuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/vorschlaege')]
#[IsGranted('ROLE_ADMIN')]
final class AdminSuggestionController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }
    #[Route('', name: 'admin_suggestion_index')]
    public function index(RestaurantSuggestionRepository $repository): Response
    {
        return $this->render('admin/suggestion/index.html.twig', [
            'pending' => $repository->findByStatus(RestaurantSuggestion::STATUS_PENDING),
            'approved' => $repository->findByStatus(RestaurantSuggestion::STATUS_APPROVED),
            'rejected' => $repository->findByStatus(RestaurantSuggestion::STATUS_REJECTED),
        ]);
    }

    #[Route('/{id}', name: 'admin_suggestion_show', requirements: ['id' => '\d+'])]
    public function show(RestaurantSuggestion $suggestion): Response
    {
        return $this->render('admin/suggestion/show.html.twig', [
            'suggestion' => $suggestion,
        ]);
    }

    #[Route('/{id}/genehmigen', name: 'admin_suggestion_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approve(RestaurantSuggestion $suggestion, Request $request, EntityManagerInterface $entityManager, CuisineRepository $cuisineRepository): Response
    {
        if (!$this->isCsrfTokenValid('approve-suggestion-' . $suggestion->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('admin_suggestion_index');
        }

        // ⚠ BF-54: Zweimal abgeschickt erzeugte zwei Restaurants — beide mit
        // Erfolgsmeldung, und die Dublette landete unbemerkt in der öffentlichen
        // Liste. Ein Doppelklick auf einen Knopf ist keine Absicht, und die
        // Zurück-Taste des Browsers macht daraus im Zweifel einen dritten.
        if (RestaurantSuggestion::STATUS_PENDING !== $suggestion->getStatus()) {
            $this->addFlash('warning', $this->translator->trans('flash.suggestion_already_handled'));

            return $this->redirectToRoute('admin_suggestion_index');
        }

        $restaurant = new Restaurant();
        $restaurant->setName($suggestion->getName());
        $restaurant->setCity($suggestion->getCity());
        $restaurant->setEmoji($suggestion->getEmoji());

        $cuisineNames = array_map('trim', explode(',', $suggestion->getCuisine()));
        foreach ($cuisineNames as $cuisineName) {
            if ($cuisineName !== '') {
                $cuisine = $cuisineRepository->findOrCreateByName($cuisineName);
                $restaurant->addCuisine($cuisine);
            }
        }
        // Restaurant kennt nur ja/nein: "Weiß nicht" wird als "nein" übernommen,
        // der Admin kann es im Restaurant-Formular nachtragen.
        $restaurant->setIsWheelchairAccessible($suggestion->isWheelchairAccessible()?->isYes() ?? false);
        $restaurant->setHasAccessibleToilet($suggestion->hasAccessibleToilet()?->isYes() ?? false);
        $restaurant->setAllowsAssistanceDogs($suggestion->allowsAssistanceDogs()?->isYes() ?? false);
        $restaurant->setHasBrightLighting($suggestion->hasBrightLighting()?->isYes() ?? false);
        $restaurant->setHasChangingTable($suggestion->hasChangingTable()?->isYes() ?? false);
        $restaurant->setHasDisabledParking($suggestion->hasDisabledParking()?->isYes() ?? false);
        $restaurant->setAcceptsCash($suggestion->acceptsCash()?->isYes() ?? false);
        $restaurant->setAcceptsCard($suggestion->acceptsCard()?->isYes() ?? false);
        $restaurant->setAcceptsPayconiq($suggestion->acceptsPayconiq()?->isYes() ?? false);
        $restaurant->setIsVegan($suggestion->isVegan()?->isYes() ?? false);
        $restaurant->setIsVegetarian($suggestion->isVegetarian()?->isYes() ?? false);
        $restaurant->setIsHalal($suggestion->isHalal()?->isYes() ?? false);
        $restaurant->setSpokenLanguages($suggestion->getSpokenLanguages());
        $restaurant->setPhone($suggestion->getPhone());
        $restaurant->setEmail($suggestion->getEmail());
        $restaurant->setWebsite($suggestion->getWebsite());
        $restaurant->setInstagramUrl($suggestion->getInstagramUrl());
        $restaurant->setFacebookUrl($suggestion->getFacebookUrl());
        $restaurant->setTiktokUrl($suggestion->getTiktokUrl());
        // Standort, sofern der Vorschlag ihn trägt (kommt über die REST-API; der
        // Web-Wizard fragt ihn nicht ab). Ohne diese drei Zeilen ginge die Angabe
        // bei der Freigabe verloren.
        $restaurant->setLatitude($suggestion->getLatitude());
        $restaurant->setLongitude($suggestion->getLongitude());
        $restaurant->setNearbyStopsNote($suggestion->getNearbyStopsNote());
        $restaurant->setSubmittedBy($suggestion->getSuggestedBy());

        $suggestion->setStatus(RestaurantSuggestion::STATUS_APPROVED);

        $entityManager->persist($restaurant);
        $entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.suggestion_approved', ['%name%' => $suggestion->getName()]));

        return $this->redirectToRoute('admin_suggestion_index');
    }

    #[Route('/{id}/ablehnen', name: 'admin_suggestion_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(RestaurantSuggestion $suggestion, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('reject-suggestion-' . $suggestion->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('admin_suggestion_index');
        }

        $suggestion->setStatus(RestaurantSuggestion::STATUS_REJECTED);
        $suggestion->setAdminNote($request->request->getString('admin_note') ?: null);

        $entityManager->flush();

        $this->addFlash('info', $this->translator->trans('flash.suggestion_rejected', ['%name%' => $suggestion->getName()]));

        return $this->redirectToRoute('admin_suggestion_index');
    }
}
