<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\AccessibilityReportType;
use App\RateLimit\ActionLimiter;
use App\Service\AccessibilityReportMailer;
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
 * Barrierefreiheitserklärung (Feature 02) und Meldeformular für Barrieren.
 *
 * Öffentlich (AK-59), unter dem Locale-Präfix (vier Sprachen, AK-44). Kein
 * #[IsGranted]: Die Seite ist der Nachweis nach außen und muss ohne Anmeldung
 * lesbar sein; das Meldeformular soll niemand vor eine Registrierung stellen
 * (AK-48). Die Meldung wird versendet, nicht gespeichert (AK-50).
 */
#[Route('/accessibility')]
final class AccessibilityController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AccessibilityReportMailer $mailer,
        #[Autowire(service: 'limiter.accessibility_report')]
        private readonly RateLimiterFactoryInterface $reportLimiter,
        #[Autowire('%app.accessibility.conformance_level%')]
        private readonly string $conformanceLevel,
        #[Autowire('%app.accessibility.tested_on%')]
        private readonly string $testedOn,
        #[Autowire('%app.accessibility.review_interval_months%')]
        private readonly int $reviewIntervalMonths,
        #[Autowire('%app.accessibility.known_issues%')]
        private readonly array $knownIssues,
    ) {
    }

    #[Route('', name: 'app_accessibility', methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderPage($this->createForm(AccessibilityReportType::class));
    }

    #[Route('', name: 'app_accessibility_report', methods: ['POST'])]
    public function report(Request $request): Response
    {
        $form = $this->createForm(AccessibilityReportType::class);
        $form->handleRequest($request);

        // Erst nach handleRequest prüfen, damit das Lesen der Seite nie Kontingent
        // verbraucht. isAllowed() fragt ab, ohne zu verbrauchen (BF-11).
        $limiter = ActionLimiter::for($this->reportLimiter, $request->getClientIp());

        if (!$limiter->isAllowed()) {
            $minutes = max(1, (int) ceil($limiter->retryAfter() / 60));
            $this->addFlash('error', $this->translator->trans(
                'accessibility_statement.report_rate_limited',
                ['%minutes%' => $minutes],
            ));

            return $this->renderPage($form, Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Honeypot: Bots bekommen dieselbe Erfolgsantwort, aber es wird nichts
        // versendet (und ohnehin nichts gespeichert). Ein Validierungsfehler
        // würde die Falle verraten.
        if ($form->isSubmitted() && '' !== trim((string) $form->get('website')->getData())) {
            return $this->successResponse($request);
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            // AbstractController::render() setzt für ein submitted-invalides
            // Formular selbst 422; Turbo rendert die Seite daraufhin neu.
            return $this->renderPage($form);
        }

        $limiter->consume();

        /** @var array{description: string, email: string} $data */
        $data = $form->getData();
        $sent = $this->mailer->send(
            $data['description'],
            '' !== $data['email'] ? $data['email'] : null,
        );

        if (!$sent) {
            $this->addFlash('error', $this->translator->trans('accessibility_statement.report_error'));

            return $this->renderPage($form);
        }

        return $this->successResponse($request);
    }

    /**
     * Erfolgsantwort — identisch für echte Meldungen und Honeypot-Treffer. Mit
     * Turbo wird nur das Formular durch die Bestätigung ersetzt (der Fokus wandert
     * dorthin, AK-51); ohne Turbo greift der Redirect samt Flash (funktioniert
     * ohne JavaScript).
     */
    private function successResponse(Request $request): Response
    {
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('accessibility/success.stream.html.twig');
        }

        $this->addFlash('success', $this->translator->trans('accessibility_statement.report_success_heading'));

        return $this->redirectToRoute('app_accessibility');
    }

    private function renderPage(FormInterface $form, ?int $status = null): Response
    {
        // Veralterungshinweis: Prüfdatum gegen heute (AK-46). Ist noch kein Datum
        // gesetzt, weist die Seite den Stand als ausstehend aus (EC-07).
        $testedOnDate = '' !== $this->testedOn ? new \DateTimeImmutable($this->testedOn) : null;
        $outdated = null !== $testedOnDate
            && $testedOnDate < new \DateTimeImmutable('-'.$this->reviewIntervalMonths.' months');

        $response = $this->render('accessibility/index.html.twig', [
            'form' => $form,
            'conformance_level' => $this->conformanceLevel,
            'tested_on' => $testedOnDate,
            'review_interval_months' => $this->reviewIntervalMonths,
            'known_issues' => $this->knownIssues,
            'outdated' => $outdated,
        ]);

        if (null !== $status) {
            $response->setStatusCode($status);
        }

        return $response;
    }
}
