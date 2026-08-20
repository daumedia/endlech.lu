<?php

namespace App\Controller;

use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Repository\OrganisationWaitlistEntryRepository;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Repository\RestaurantRepository;
use App\Waitlist\WaitlistEntryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gemeinsame Verwaltung beider Wartelisten: Restaurants (Partnerprogramm) und
 * Organisationen (Gemeinden, Unternehmen, Vereine).
 *
 * Die Liste normalisiert beide Entities zu einheitlichen Zeilen, damit sie in
 * einer Tabelle nebeneinander stehen können, ohne dass das Template die
 * konkreten Klassen kennen muss.
 */
#[Route('/admin/warteliste')]
#[IsGranted('ROLE_ADMIN')]
final class AdminWaitlistController extends AbstractController
{
    private const SOURCE_PARTNER = 'partner';
    private const SOURCE_ORGANISATION = 'organisation';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Route('', name: 'admin_waitlist_index')]
    public function index(
        Request $request,
        PartnerWaitlistEntryRepository $partnerRepository,
        OrganisationWaitlistEntryRepository $organisationRepository,
    ): Response {
        $source = $request->query->getString('source');
        $status = WaitlistStatus::tryFrom($request->query->getString('status'));
        $organisationType = OrganisationType::tryFrom($request->query->getString('type'));
        $direction = 'asc' === strtolower($request->query->getString('sort')) ? 'ASC' : 'DESC';

        // Ein gesetzter Organisationstyp impliziert die Quelle "Organisation".
        if ($organisationType) {
            $source = self::SOURCE_ORGANISATION;
        }

        $rows = [];

        if (self::SOURCE_ORGANISATION !== $source) {
            foreach ($partnerRepository->findFiltered($status, $direction) as $entry) {
                $rows[] = $this->partnerRow($entry);
            }
        }

        if (self::SOURCE_PARTNER !== $source) {
            foreach ($organisationRepository->findFiltered($organisationType, $status, $direction) as $entry) {
                $rows[] = $this->organisationRow($entry);
            }
        }

        // Nach dem Zusammenführen erneut sortieren – sonst stünden erst alle
        // Partner- und danach alle Organisationseinträge.
        usort($rows, static fn (array $a, array $b) => 'ASC' === $direction
            ? $a['createdAt'] <=> $b['createdAt']
            : $b['createdAt'] <=> $a['createdAt']);

        return $this->render('admin/waitlist/index.html.twig', [
            'rows' => $rows,
            'activeSource' => \in_array($source, [self::SOURCE_PARTNER, self::SOURCE_ORGANISATION], true) ? $source : null,
            'activeStatus' => $status,
            'activeType' => $organisationType,
            'direction' => $direction,
            'statuses' => WaitlistStatus::cases(),
            'types' => OrganisationType::cases(),
            'partnerTotal' => $partnerRepository->count([]),
            'organisationTotal' => $organisationRepository->count([]),
            'typeCounts' => $organisationRepository->countByType(),
        ]);
    }

    #[Route('/partner/{id}', name: 'admin_waitlist_partner_show', requirements: ['id' => '\d+'])]
    public function showPartner(PartnerWaitlistEntry $entry, RestaurantRepository $restaurantRepository): Response
    {
        return $this->render('admin/waitlist/partner_show.html.twig', [
            'entry' => $entry,
            'statuses' => WaitlistStatus::cases(),
            'restaurants' => $restaurantRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/organisation/{id}', name: 'admin_waitlist_organisation_show', requirements: ['id' => '\d+'])]
    public function showOrganisation(OrganisationWaitlistEntry $entry): Response
    {
        return $this->render('admin/waitlist/organisation_show.html.twig', [
            'entry' => $entry,
            'statuses' => WaitlistStatus::cases(),
        ]);
    }

    #[Route('/partner/{id}/status', name: 'admin_waitlist_partner_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changePartnerStatus(PartnerWaitlistEntry $entry, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->applyStatus($entry, $request, $entityManager, 'admin_waitlist_partner_show');
    }

    #[Route('/organisation/{id}/status', name: 'admin_waitlist_organisation_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changeOrganisationStatus(OrganisationWaitlistEntry $entry, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->applyStatus($entry, $request, $entityManager, 'admin_waitlist_organisation_show');
    }

    #[Route('/partner/{id}/restaurant', name: 'admin_waitlist_partner_link', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function linkRestaurant(
        PartnerWaitlistEntry $entry,
        Request $request,
        EntityManagerInterface $entityManager,
        RestaurantRepository $restaurantRepository,
    ): Response {
        $redirect = $this->redirectToRoute('admin_waitlist_partner_show', ['id' => $entry->getId()]);

        if (!$this->isCsrfTokenValid('waitlist-link-' . $entry->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $redirect;
        }

        $restaurantId = $request->request->getInt('restaurant');

        if (0 === $restaurantId) {
            $entry->setRestaurant(null);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.partner_restaurant_unlinked'));

            return $redirect;
        }

        $restaurant = $restaurantRepository->find($restaurantId);

        if (!$restaurant) {
            $this->addFlash('error', $this->translator->trans('flash.partner_restaurant_missing'));

            return $redirect;
        }

        $entry->setRestaurant($restaurant);
        $entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.partner_restaurant_linked', [
            '%name%' => $restaurant->getName(),
        ]));

        return $redirect;
    }

    /**
     * Statuswechsel für beide Eintragstypen – identische Regeln, identische
     * CSRF-Token-ID.
     */
    private function applyStatus(
        WaitlistEntryInterface $entry,
        Request $request,
        EntityManagerInterface $entityManager,
        string $redirectRoute,
    ): Response {
        $redirect = $this->redirectToRoute($redirectRoute, ['id' => $entry->getId()]);

        if (!$this->isCsrfTokenValid('waitlist-status-' . $entry->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $redirect;
        }

        $status = WaitlistStatus::tryFrom($request->request->getString('status'));

        if (!$status) {
            $this->addFlash('error', $this->translator->trans('flash.waitlist_status_invalid'));

            return $redirect;
        }

        $entry->setStatus($status);

        // Wird ein Eintrag im Admin von Hand weitergesetzt, fehlt sonst der
        // Bestätigungszeitpunkt.
        if (WaitlistStatus::PENDING !== $status && !$entry->isConfirmed()) {
            $entry->setConfirmedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.waitlist_status_changed', [
            '%status%' => $this->translator->trans($status->transKey()),
        ]));

        return $redirect;
    }

    /** @return array<string, mixed> */
    private function partnerRow(PartnerWaitlistEntry $entry): array
    {
        return [
            'kind' => self::SOURCE_PARTNER,
            'id' => $entry->getId(),
            'name' => $entry->getRestaurantName(),
            'detail' => $entry->getLocality(),
            'contact' => $entry->getContactName(),
            'status' => $entry->getStatus(),
            'type' => null,
            'createdAt' => $entry->getCreatedAt(),
            'route' => 'admin_waitlist_partner_show',
        ];
    }

    /** @return array<string, mixed> */
    private function organisationRow(OrganisationWaitlistEntry $entry): array
    {
        return [
            'kind' => self::SOURCE_ORGANISATION,
            'id' => $entry->getId(),
            'name' => $entry->getOrganisationName(),
            'detail' => $entry->getCommuneName() ?? $entry->getContactRole(),
            'contact' => $entry->getContactName(),
            'status' => $entry->getStatus(),
            'type' => $entry->getType(),
            'createdAt' => $entry->getCreatedAt(),
            'route' => 'admin_waitlist_organisation_show',
        ];
    }
}
