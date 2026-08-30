<?php

namespace App\Controller;

use App\Entity\PartnerWaitlistEntry;
use App\Enum\WaitlistStatus;
use App\Form\PartnerWaitlistType;
use App\RateLimit\ActionLimiter;
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
        //
        // ⚠ BF-11: `consume(0)` fragt ab, ohne zu verbrauchen. Der Verbrauch steht
        // unten, wo die Anmeldung wirklich entsteht.
        $limiter = ActionLimiter::for($this->waitlistLimiter, $request->getClientIp());

        if (!$limiter->isAllowed()) {
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

        $limiter->consume();

        $entry->setConsentAt(new \DateTimeImmutable());
        $entry->setLocale($request->getLocale());
        $entry->setSource(WaitlistRequestHelper::resolveSource($request));

        // Werbe-Einwilligung (Feature 04): nur der Zeitpunkt wird festgehalten.
        // Ohne Häkchen bleibt das Feld null – bewusst kein else-Zweig, denn
        // „nicht eingewilligt" ist der Ausgangszustand und kein Vorgang.
        //
        // ⚠ Hier geht nichts an Brevo. Übertragen wird erst die BESTÄTIGTE
        // Adresse (AK-05), und das tut der Bestätigungs-Ablauf, nicht dieses
        // Formular.
        if (true === $form->get('marketingConsent')->getData()) {
            $entry->setMarketingConsentAt(new \DateTimeImmutable());
        }

        $sent = $this->confirmationService->register(
            $entry,
            'app_partner_confirm',
            'email/partner/confirmation.html.twig',
            'email.partner_confirm_subject',
            revokeRoute: 'app_partner_revoke',
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

        // ⚠ BF-91: Vor der Bestätigung merken, ob der Vorgang beim Team
        // überhaupt neu ist. Seit eine späte Bestätigung durchläuft (BF-89),
        // erreicht dieser Zweig auch Einträge, die das Team längst bearbeitet
        // hat — und verschickte für einen gewonnenen Kunden erneut eine
        // Meldung „Neue Partner-Anmeldung". Der Betreff wäre schlicht falsch.
        $warNeu = null !== $entry && WaitlistStatus::PENDING === $entry->getStatus();

        $state = $this->confirmationService->confirm($entry);

        if (WaitlistConfirmationService::RESULT_CONFIRMED === $state && $entry && $warNeu) {
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
        ], match ($state) {
            // ⚠ BF-36: 410 statt 404 bei einem abgelaufenen Link. Der Unterschied
            // ist nicht Kosmetik: 404 heißt „gab es nie", 410 heißt „gab es, ist
            // weg" — und genau das ist hier der Fall.
            WaitlistConfirmationService::RESULT_INVALID => new Response(null, Response::HTTP_NOT_FOUND),
            WaitlistConfirmationService::RESULT_EXPIRED => new Response(null, Response::HTTP_GONE),
            default => null,
        });
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

    /**
     * Anmeldung zurückziehen (Art. 7 Abs. 3 DSGVO, BF-37).
     *
     * ⚠ Der Eintrag wird gelöscht, nicht markiert. Ein Widerruf, nach dem Name,
     * Adresse und Einwilligungszeitpunkt weiter in der Datenbank stehen, ist
     * keiner. Der Link steht in jeder Mail — ein Widerruf, der einen Anruf
     * verlangt, ist nicht „ebenso einfach" wie die Einwilligung.
     */
    #[Route('/abmelden/{token}', name: 'app_partner_revoke', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET'])]
    public function revoke(string $token, PartnerWaitlistEntryRepository $repository): Response
    {
        $state = $this->confirmationService->revoke($repository->findOneByConfirmationToken($token));

        return $this->render('partner/confirmation.html.twig', [
            'state' => $state,
            'entry' => null,
        ], WaitlistConfirmationService::RESULT_INVALID === $state
            ? new Response(null, Response::HTTP_NOT_FOUND)
            : null);
    }
}
