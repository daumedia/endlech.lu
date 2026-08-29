<?php

namespace App\Controller;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Form\OrganisationWaitlistType;
use App\RateLimit\ActionLimiter;
use App\Repository\OrganisationWaitlistEntryRepository;
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
 * Landing Page und Warteliste für Organisationen: Gemeinden, Unternehmen und
 * Vereine.
 *
 * Die drei Typen sind kommerziell grundverschieden – Gemeinden beauftragen eine
 * Erhebung, Unternehmen sponsern, Vereine sitzen im Beirat, ohne dass Geld
 * fließt. Deshalb je eigene Bestätigungsmail und eigene Erfolgsmeldung.
 */
#[Route('/organisationen')]
final class OrganisationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly WaitlistConfirmationService $confirmationService,
        // ⚠ BF-38: Eigener Zähler statt des geteilten `limiter.partner_waitlist`.
        // Vorher sperrten fünf Anmeldungen auf /partner das Formular hier mit —
        // zwei getrennte Formulare, ein Kontingent.
        #[Autowire(service: 'limiter.organisation_waitlist')]
        private readonly RateLimiterFactoryInterface $waitlistLimiter,
    ) {
    }

    #[Route('', name: 'app_organisations', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $entry = new OrganisationWaitlistEntry();

        // Vorauswahl über ?type=commune – so springen die Bereichs-Karten
        // direkt mit dem passenden Formulartyp ans Ziel, ganz ohne JavaScript.
        $entry->setType(OrganisationType::tryFrom($request->query->getString('type')));

        return $this->renderLandingPage($this->createForm(OrganisationWaitlistType::class, $entry));
    }

    /**
     * Eigene Seite je Zielgruppe. Der Slug wird über das Enum aufgelöst; ein
     * unbekannter Slug ergibt 404 statt einer leeren Seite.
     */
    #[Route('/{slug}', name: 'app_organisations_type', methods: ['GET'], requirements: ['slug' => 'gemeinden|unternehmen|vereine'])]
    public function type(string $slug): Response
    {
        $type = OrganisationType::fromSlug($slug);

        if (!$type) {
            throw $this->createNotFoundException();
        }

        $entry = new OrganisationWaitlistEntry();
        $entry->setType($type);

        return $this->render('organisation/type.html.twig', [
            'form' => $this->createForm(OrganisationWaitlistType::class, $entry),
            'type' => $type,
            'types' => OrganisationType::cases(),
        ]);
    }

    #[Route('', name: 'app_organisations_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $entry = new OrganisationWaitlistEntry();
        $form = $this->createForm(OrganisationWaitlistType::class, $entry);
        $form->handleRequest($request);

        // ⚠ BF-11: `consume(0)` fragt ab, ohne zu verbrauchen. Der Verbrauch steht
        // unten, wo die Anmeldung wirklich entsteht — ein Tippfehler darf keine
        // Stunde kosten.
        $limiter = ActionLimiter::for($this->waitlistLimiter, $request->getClientIp());

        if (!$limiter->isAllowed()) {
            $this->addFlash('error', $this->translator->trans('flash.partner_rate_limited'));

            return $this->renderLandingPage($form, Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Honeypot: gleiche Antwort wie bei einem echten Erfolg, aber ohne
        // Speichern und ohne Mail.
        if ($form->isSubmitted() && '' !== trim((string) $form->get('companyWebsite')->getData())) {
            return $this->successResponse($request, null);
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
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
        // Formular. Diese eine Stelle deckt alle vier Einstiege ab: Übersicht
        // und die drei Zielgruppenseiten senden an dieselbe Route
        // (app_organisations_submit, POST /organisationen).
        if (true === $form->get('marketingConsent')->getData()) {
            $entry->setMarketingConsentAt(new \DateTimeImmutable());
        }

        $type = $entry->getType() ?? OrganisationType::COMMUNE;

        $sent = $this->confirmationService->register(
            $entry,
            'app_organisations_confirm',
            'email/organisation/' . $type->value . '.html.twig',
            'email.organisation_confirm_subject_' . $type->value,
            revokeRoute: 'app_organisations_revoke',
        );

        if (!$sent) {
            $this->addFlash('warning', $this->translator->trans('flash.organisation_email_failed'));

            return $this->redirectToRoute('app_organisations');
        }

        return $this->successResponse($request, $entry);
    }

    #[Route('/confirmation/{token}', name: 'app_organisations_confirm', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function confirm(string $token, OrganisationWaitlistEntryRepository $repository): Response
    {
        $entry = $repository->findOneByConfirmationToken($token);

        // ⚠ BF-91: siehe `PartnerController::confirm()` — eine späte
        // Bestätigung darf keine „Neue Anmeldung"-Meldung für einen Vorgang
        // auslösen, den das Team längst bearbeitet hat.
        $warNeu = null !== $entry && WaitlistStatus::PENDING === $entry->getStatus();

        $state = $this->confirmationService->confirm($entry);

        if (WaitlistConfirmationService::RESULT_CONFIRMED === $state && $entry && $warNeu) {
            // Typ steht im Betreff, damit sich die Meldungen filtern lassen.
            $this->confirmationService->notifyTeam(
                $entry,
                'email/organisation/internal_notification.html.twig',
                'email.organisation_internal_subject',
                [
                    '%type%' => $this->translator->trans($entry->getType()?->transKey() ?? '', [], null, 'de'),
                    '%organisation%' => $entry->getOrganisationName(),
                ],
            );
        }

        return $this->render('organisation/confirmation.html.twig', [
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
     * Erfolgsantwort. Die Meldung ist typspezifisch – eine Gemeinde erfährt
     * etwas über nächste Schritte und Zeitrahmen, ein Verein über den Beirat.
     */
    private function successResponse(Request $request, ?OrganisationWaitlistEntry $entry): Response
    {
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('organisation/success.stream.html.twig', ['entry' => $entry]);
        }

        $this->addFlash('success', $this->translator->trans('flash.organisation_submitted'));

        return $this->redirectToRoute('app_organisations');
    }

    private function renderLandingPage(FormInterface $form, ?int $status = null): Response
    {
        $response = $this->render('organisation/index.html.twig', [
            'form' => $form,
            'types' => OrganisationType::cases(),
        ]);

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
    #[Route('/abmelden/{token}', name: 'app_organisations_revoke', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET'])]
    public function revoke(string $token, OrganisationWaitlistEntryRepository $repository): Response
    {
        $state = $this->confirmationService->revoke($repository->findOneByConfirmationToken($token));

        return $this->render('organisation/confirmation.html.twig', [
            'state' => $state,
            'entry' => null,
        ], WaitlistConfirmationService::RESULT_INVALID === $state
            ? new Response(null, Response::HTTP_NOT_FOUND)
            : null);
    }
}
