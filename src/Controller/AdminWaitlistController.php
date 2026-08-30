<?php

namespace App\Controller;

use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
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

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MarketingContactRegistry $marketingContacts,
        private readonly MarketingContactRepository $marketingContactRepository,
    ) {
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

        // Feature 04 / AK-26: Sync-Zustand je Zeile. **Eine** Abfrage für die
        // ganze Seite – eine je Zeile lüde bei 50 Einträgen 50 Mal nach
        // (dasselbe Thema wie BF-40 an der Restaurantauswahl).
        $marketing = $this->marketingContactRepository->findIndexedByEmails(
            array_column($rows, 'email'),
        );

        foreach ($rows as $index => $row) {
            $rows[$index]['marketing'] = $marketing[mb_strtolower(trim($row['email']))] ?? null;
        }

        return $this->render('admin/waitlist/index.html.twig', [
            'rows' => $rows,
            // AK-27: Gegenprobe zur Kontaktzahl in Brevo. Stimmen die Zahlen
            // nicht überein, fehlt etwas – und das fällt hier auf, nicht erst
            // beim Versand einer Kampagne.
            'marketingConsented' => $this->marketingContactRepository->countConsented(),
            'marketingCounts' => $this->marketingContactRepository->countBySyncState(),
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

    /**
     * Auswahlliste für die Verknüpfung — begrenzt und durchsuchbar.
     *
     * ⚠ BF-40: Vorher `findBy([], ['name' => 'ASC'])`, also der komplette
     * Kernbestand in ein `<select>`. Bei elf Häusern fällt das nicht auf; bei
     * dreitausend ist es eine Seite, die nicht mehr lädt. Blättern ist im Projekt
     * vorhanden (B05, B20) und war hier nur nicht angewandt.
     *
     * Die Suche läuft serverseitig über denselben Query-Parameter wie die
     * Verwaltungsliste und braucht kein JavaScript — der Admin tippt einen Namen
     * und bekommt die Treffer.
     */
    private const RESTAURANT_CHOICES = 50;

    #[Route('/partner/{id}', name: 'admin_waitlist_partner_show', requirements: ['id' => '\d+'])]
    public function showPartner(PartnerWaitlistEntry $entry, Request $request, RestaurantRepository $restaurantRepository): Response
    {
        $suche = trim($request->query->getString('rq', ''));
        $auswahl = $restaurantRepository->findForAdmin(1, self::RESTAURANT_CHOICES, $suche);

        return $this->render('admin/waitlist/partner_show.html.twig', [
            'entry' => $entry,
            'statuses' => WaitlistStatus::cases(),
            // Feature 04 / AK-15, AK-18: Sync-Zustand und letzter Fehler. Eine
            // gescheiterte Übertragung erzeugt keinen Alarm – sie fällt nur
            // hier auf, und sonst erst, wenn eine Kampagne jemanden nicht
            // erreicht.
            'marketingContact' => $this->marketingContactRepository->findOneByEmail($entry->getEmail()),
            'restaurants' => $auswahl,
            'restaurantSuche' => $suche,
            'restaurantTotal' => \count($auswahl),
            'restaurantLimit' => self::RESTAURANT_CHOICES,
        ]);
    }

    #[Route('/organisation/{id}', name: 'admin_waitlist_organisation_show', requirements: ['id' => '\d+'])]
    public function showOrganisation(OrganisationWaitlistEntry $entry): Response
    {
        return $this->render('admin/waitlist/organisation_show.html.twig', [
            'entry' => $entry,
            'statuses' => WaitlistStatus::cases(),
            'marketingContact' => $this->marketingContactRepository->findOneByEmail($entry->getEmail()),
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
        //
        // ⚠ Dieser Backfill setzt **nur** `confirmedAt`, nicht
        // `selfConfirmedAt` — ein Telefonat ist kein Nachweis, dass die
        // Adresse dem Angerufenen gehört. Es rechtfertigt den
        // Vertriebsstatus, nicht die Werbung (BF-83/BF-89).
        if (WaitlistStatus::PENDING !== $status && !$entry->isConfirmed()) {
            $entry->setConfirmedAt(new \DateTimeImmutable());
        }

        // Feature 04 / AK-09: Der Vertriebsstatus steht als Attribut
        // `FUNNEL_STATUS` auch in Brevo und ist dort ein Segmentkriterium –
        // eine Kampagne fürs Partnerprogramm schließt darüber die bereits
        // gewonnenen Häuser aus. Bliebe die Änderung hier hängen, liefe genau
        // diese Kampagne an Menschen, mit denen der Vorgang abgeschlossen ist.
        //
        // Die Zeile wird nur auf `pending` zurückgestellt; übertragen wird im
        // nächsten Lauf. Eine Direktübertragung im Request hinge an der
        // Erreichbarkeit eines fremden Dienstes (AK-17).
        // Die Registry entscheidet selbst, ob der Eintrag nach Brevo darf: Sie
        // fragt `hasSelfConfirmed()`, und das setzt allein der eingelöste
        // Bestätigungslink. Ein Vorabfilter an dieser Stelle wäre die zweite
        // Hälfte derselben Zweideutigkeit — genau daran ist die erste
        // Reparatur von BF-83 gescheitert.
        $this->marketingContacts->recordWaitlistEntry($entry);

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
            // Nur zum Nachschlagen des Sync-Zustands (Feature 04) – die Liste
            // zeigt die Adresse nicht an.
            'email' => $entry->getEmail(),
            'marketingConsentAt' => $entry->getMarketingConsentAt(),
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
            'email' => $entry->getEmail(),
            'marketingConsentAt' => $entry->getMarketingConsentAt(),
            'status' => $entry->getStatus(),
            'type' => $entry->getType(),
            'createdAt' => $entry->getCreatedAt(),
            'route' => 'admin_waitlist_organisation_show',
        ];
    }
}
