<?php

namespace App\Controller;

use App\Entity\PartnerWaitlistEntry;
use App\Form\PartnerWaitlistType;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Waitlist\WaitlistConfirmationService;
use App\Waitlist\WaitlistRequestHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Partnerprogramm: Landing Page und Warteliste für Restaurants.
 *
 * Bewusst ohne Zahlung und ohne Account-Anlage – Preise und Paketumfang stehen
 * noch nicht fest. Gesammelt werden nur Anmeldungen, bestätigt per Double-Opt-In
 * (siehe WaitlistConfirmationService, geteilt mit den Organisationen).
 */
#[Route('/partner')]
final class PartnerController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly WaitlistConfirmationService $confirmationService,
        #[Autowire(service: 'limiter.partner_waitlist')]
        private readonly RateLimiterFactoryInterface $waitlistLimiter,
    ) {
    }

    #[Route('', name: 'app_partner', methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderLandingPage($this->createForm(PartnerWaitlistType::class, new PartnerWaitlistEntry()));
    }

    #[Route('', name: 'app_partner_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $entry = new PartnerWaitlistEntry();
        $form = $this->createForm(PartnerWaitlistType::class, $entry);
        $form->handleRequest($request);

        // Erst nach handleRequest prüfen, damit ein GET-Aufruf der Seite nie
        // Kontingent verbraucht – gedeckelt wird das Absenden, nicht das Lesen.
        $limit = $this->waitlistLimiter->create($request->getClientIp() ?? 'anonymous')->consume(1);

        if (!$limit->isAccepted()) {
            $this->addFlash('error', $this->translator->trans('flash.partner_rate_limited'));

            return $this->renderLandingPage($form, Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Honeypot: Bots füllen jedes Feld aus. Sie bekommen exakt dieselbe
        // Antwort wie ein echter Absender – nur wird nichts gespeichert und
        // nichts versendet. Ein Validierungsfehler würde die Falle verraten.
        if ($form->isSubmitted() && '' !== trim((string) $form->get('website')->getData())) {
            return $this->successResponse($request, null);
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            // AbstractController::render() setzt für ein submitted-invalides
            // Formular selbst 422; Turbo rendert die Seite daraufhin neu.
            return $this->renderLandingPage($form);
        }

        $entry->setConsentAt(new \DateTimeImmutable());
        $entry->setLocale($request->getLocale());
        $entry->setSource(WaitlistRequestHelper::resolveSource($request));

        $sent = $this->confirmationService->register(
            $entry,
            'app_partner_confirm',
            'email/partner/confirmation.html.twig',
            'email.partner_confirm_subject',
        );

        if (!$sent) {
            // Der Eintrag steht bereits – der Interessent soll nicht den
            // Eindruck bekommen, die Anmeldung sei verloren.
            $this->addFlash('warning', $this->translator->trans('flash.partner_email_failed'));

            return $this->redirectToRoute('app_partner');
        }

        return $this->successResponse($request, $entry);
    }

    #[Route('/confirmation/{token}', name: 'app_partner_confirm', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function confirm(string $token, PartnerWaitlistEntryRepository $repository): Response
    {
        $entry = $repository->findOneByConfirmationToken($token);
        $state = $this->confirmationService->confirm($entry);

        if (WaitlistConfirmationService::RESULT_CONFIRMED === $state && $entry) {
            $this->confirmationService->notifyTeam(
                $entry,
                'email/partner/internal_notification.html.twig',
                'email.partner_internal_subject',
                ['%restaurant%' => $entry->getRestaurantName()],
            );
        }

        return $this->render('partner/confirmation.html.twig', [
            'state' => $state,
            'entry' => $entry,
        ], WaitlistConfirmationService::RESULT_INVALID === $state
            ? new Response(null, Response::HTTP_NOT_FOUND)
            : null);
    }

    /**
     * Erfolgsantwort – identisch für echte Anmeldungen und für Honeypot-Treffer.
     * Mit Turbo wird nur das Formular ersetzt, ohne Turbo greift der klassische
     * Redirect samt Flash (die Seite funktioniert also ohne JavaScript).
     */
    private function successResponse(Request $request, ?PartnerWaitlistEntry $entry): Response
    {
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('partner/success.stream.html.twig', ['entry' => $entry]);
        }

        $this->addFlash('success', $this->translator->trans('flash.partner_waitlist_submitted'));

        return $this->redirectToRoute('app_partner');
    }

    private function renderLandingPage(FormInterface $form, ?int $status = null): Response
    {
        $response = $this->render('partner/index.html.twig', ['form' => $form]);

        if (null !== $status) {
            $response->setStatusCode($status);
        }

        return $response;
    }
}
