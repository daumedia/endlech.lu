<?php

namespace App\Controller;

use App\Entity\AppWaitlistEntry;
use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Marketing\MarketingContactRegistry;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\MarketingContactRepository;
use App\Repository\OrganisationWaitlistEntryRepository;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Repository\RestaurantRepository;
use App\Waitlist\StaleAppWaitlistCleaner;
use App\Waitlist\WaitlistEntryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gemeinsame Verwaltung aller drei Wartelisten: Restaurants (Partnerprogramm),
 * Organisationen (Gemeinden, Unternehmen, Vereine) und die Vormerkung für die
 * mobile App (Feature 08).
 *
 * Die Liste normalisiert alle Entities zu einheitlichen Zeilen, damit sie in
 * einer Tabelle nebeneinander stehen können, ohne dass das Template die
 * konkreten Klassen kennen muss.
 *
 * ⚠ `#[IsGranted('ROLE_ADMIN')]` steht auf der KLASSE und deckt damit jede
 * Methode, die hier hinzukommt (AK-36). Eine Rechteprüfung je Methode wäre eine
 * Zeile, die beim nächsten Anbau fehlen kann — und dann läge eine vollständige
 * Adressliste offen.
 */
#[Route('/admin/warteliste')]
#[IsGranted('ROLE_ADMIN')]
final class AdminWaitlistController extends AbstractController
{
    private const SOURCE_PARTNER = 'partner';
    private const SOURCE_ORGANISATION = 'organisation';
    private const SOURCE_APP = 'app';

    /** Erlaubte Werte des Quellen-Filters; alles andere gilt als „alle". */
    private const SOURCES = [self::SOURCE_PARTNER, self::SOURCE_ORGANISATION, self::SOURCE_APP];

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
        AppWaitlistEntryRepository $appRepository,
        StaleAppWaitlistCleaner $staleAppEntries,
    ): Response {
        $source = $request->query->getString('source');
        $status = WaitlistStatus::tryFrom($request->query->getString('status'));
        $organisationType = OrganisationType::tryFrom($request->query->getString('type'));
        $direction = 'asc' === strtolower($request->query->getString('sort')) ? 'ASC' : 'DESC';

        // Ein gesetzter Organisationstyp impliziert die Quelle "Organisation".
        if ($organisationType) {
            $source = self::SOURCE_ORGANISATION;
        }

        // ⚠ Erst normalisieren, dann positiv vergleichen. Bis zur dritten
        // Quelle stand hier je Zweig eine Negation („alles außer Organisation"
        // → Partnerzeilen). Mit drei Werten ist das nicht mehr dasselbe:
        // `?source=app` hätte darüber weiterhin die Partner- UND die
        // Organisationszeilen geliefert. Ein unbekannter Wert fällt wie bisher
        // auf „alle" zurück, statt eine leere Liste zu zeigen.
        $source = \in_array($source, self::SOURCES, true) ? $source : null;

        // AK-49: der zweite der beiden Wege, auf denen nie selbst bestätigte
        // App-Vormerkungen verschwinden — der erste ist der Zeitplan.
        //
        // Zwei Wege, weil auf Produktion geplante Läufe bereits zweimal
        // ausblieben: `app:metrics:snapshot` hat dadurch nie einen Snapshot
        // geschrieben, und diese Historie ist nicht nachholbar. Ohne
        // Bestätigung liegt keine Einwilligung vor — eine Löschfrist, die
        // allein an einer Servereinrichtung hängt, ist keine.
        //
        // ⚠ Der Aufruf steht VOR dem Einsammeln der Zeilen. Andersherum zeigte
        // die Liste Einträge, die derselbe Aufruf soeben gelöscht hat.
        //
        // Der Dienst sperrt sich selbst auf einen Lauf je Kalendertag; hier
        // steht deshalb keine zusätzliche Bedingung, und der Rückgabewert
        // interessiert nicht — gelöscht wird still.
        $staleAppEntries->sweepOncePerDay();

        $rows = [];

        if (null === $source || self::SOURCE_PARTNER === $source) {
            foreach ($partnerRepository->findFiltered($status, $direction) as $entry) {
                $rows[] = $this->partnerRow($entry);
            }
        }

        if (null === $source || self::SOURCE_ORGANISATION === $source) {
            foreach ($organisationRepository->findFiltered($organisationType, $status, $direction) as $entry) {
                $rows[] = $this->organisationRow($entry);
            }
        }

        if (null === $source || self::SOURCE_APP === $source) {
            foreach ($appRepository->findFiltered($status, $direction) as $entry) {
                $rows[] = $this->appRow($entry);
            }
        }

        // Nach dem Zusammenführen erneut sortieren – sonst stünden erst alle
        // Partner-, dann alle Organisations- und zuletzt alle App-Einträge.
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
            // Bereits oben auf einen bekannten Wert bzw. null normalisiert.
            'activeSource' => $source,
            'activeStatus' => $status,
            'activeType' => $organisationType,
            'direction' => $direction,
            'statuses' => WaitlistStatus::cases(),
            'types' => OrganisationType::cases(),
            'partnerTotal' => $partnerRepository->count([]),
            'organisationTotal' => $organisationRepository->count([]),
            'appTotal' => $appRepository->count([]),
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

    /**
     * Detailansicht einer App-Vormerkung (Feature 08).
     *
     * Sie trägt das, was in der Liste keinen Platz hat und dort auch nicht
     * hingehört: die Adresse, den Bestätigungsstand und den Zeitpunkt, an dem
     * die Mail mit dem Beta-Zugang hinausging. Letzterer ist die einzige
     * Auskunft, die eine Nachfrage „ich habe nichts bekommen" beantworten kann.
     */
    #[Route('/app/{id}', name: 'admin_waitlist_app_show', requirements: ['id' => '\d+'])]
    public function showApp(AppWaitlistEntry $entry): Response
    {
        return $this->render('admin/waitlist/show_app.html.twig', [
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

    /**
     * ⚠ Kein eigener Rumpf: `applyStatus()` ist gegen `WaitlistEntryInterface`
     * typisiert, und `AppWaitlistEntry` erfüllt es. Damit gelten hier dieselbe
     * CSRF-Token-ID, derselbe Backfill von `confirmedAt` und dieselbe
     * Rückstellung des Brevo-Auftragsbuchs wie bei den beiden anderen
     * Wartelisten — eine nachgebaute Fassung liefe beim nächsten Eingriff an
     * jener Methode auseinander.
     */
    #[Route('/app/{id}/status', name: 'admin_waitlist_app_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changeAppStatus(AppWaitlistEntry $entry, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->applyStatus($entry, $request, $entityManager, 'admin_waitlist_app_show');
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
            // ⚠ Jede Zeilenart führt ALLE Schlüssel, auch die für sie leeren.
            // `strict_variables: true` gilt im Test — ein `row.platform` auf
            // einer Zeile ohne diesen Schlüssel wäre dort kein „null", sondern
            // ein Laufzeitfehler im Template.
            'platform' => null,
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
            'platform' => null,
            'createdAt' => $entry->getCreatedAt(),
            'route' => 'admin_waitlist_organisation_show',
        ];
    }

    /**
     * Dritte Zeilenart: die Vormerkung für die mobile App (Feature 08, AK-35).
     *
     * ⚠ **Die Plattform steht in `name`, nicht bloß im Abzeichen.** Das Feature
     * erhebt keinen Namen; bliebe die Spalte leer, wäre die Zeile ohne
     * Aufklappen nicht von einer anderen zu unterscheiden. `platform` trägt
     * denselben Wert zusätzlich als Enum-Case, damit das Template Farbe und
     * Emoji aus `AppPlatform` nehmen kann, statt sie selbst zu entscheiden.
     *
     * @return array<string, mixed>
     */
    private function appRow(AppWaitlistEntry $entry): array
    {
        return [
            'kind' => self::SOURCE_APP,
            'id' => $entry->getId(),
            'name' => $entry->getDisplayName(),
            // ⚠ Bewusst nichts: Es gibt keine Ortsangabe und keine Rolle, und
            // die einzigen weiteren Angaben — Adresse und Herkunftsquelle —
            // gehören nicht in eine Liste, die man im Vorbeigehen offen hat.
            // Der Bestätigungsstand und der Beta-Versand stehen auf der
            // Detailseite.
            'detail' => null,
            // ⚠ Immer leer, und das ist die ehrliche Antwort: Diese Warteliste
            // kennt keinen Ansprechpartner (siehe AppWaitlistEntry). Ein
            // erfundener Name landete über das Auftragsbuch in Brevo.
            'contact' => $entry->getContactName(),
            // Nur zum Nachschlagen des Sync-Zustands (Feature 04) – die Liste
            // zeigt die Adresse nicht an. Bei dieser Zeilenart wiegt das
            // schwerer als bei den beiden anderen: Hier IST die Adresse der
            // ganze Datensatz.
            'email' => $entry->getEmail(),
            'marketingConsentAt' => $entry->getMarketingConsentAt(),
            'status' => $entry->getStatus(),
            'type' => null,
            'platform' => $entry->getPlatform(),
            'createdAt' => $entry->getCreatedAt(),
            'route' => 'admin_waitlist_app_show',
        ];
    }
}
