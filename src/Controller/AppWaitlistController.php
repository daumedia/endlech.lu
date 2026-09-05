<?php

namespace App\Controller;

use App\Entity\AppWaitlistEntry;
use App\Form\AppWaitlistType;
use App\RateLimit\ActionLimiter;
use App\Repository\AppWaitlistEntryRepository;
use App\Waitlist\WaitlistConfirmationService;
use App\Waitlist\WaitlistRequestHelper;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
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
 * Warteliste für die mobile App (Feature 08).
 *
 * Die dritte Warteliste des Projekts und die schmalste: Adresse und Plattform.
 * Sie teilt die Mechanik mit B14 und B15 über den
 * {@see WaitlistConfirmationService} — Token, 7-Tage-Frist, Widerruf.
 *
 * Der Unterschied zu den beiden anderen liegt in zwei Punkten:
 *  - **Eine Adresse, ein Eintrag.** Ein zweiter Versuch legt keine zweite Zeile
 *    an und verrät zugleich nicht, dass die Adresse schon bekannt ist.
 *  - **Eine zweite Mail nach dem Bestätigungsklick**, die bei iOS den
 *    TestFlight-Link trägt. Erst sie, nicht die Bestätigungsmail: Sonst hätte
 *    der Klick keinen Grund mehr, und wer eine fremde Adresse einträgt,
 *    schickte dem Fremden den Beta-Zugang.
 */
#[Route('/app')]
final class AppWaitlistController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly WaitlistConfirmationService $confirmationService,
        private readonly AppWaitlistEntryRepository $entries,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'limiter.app_waitlist')]
        private readonly RateLimiterFactoryInterface $waitlistLimiter,
        #[Autowire('%app.testflight_url%')]
        private readonly string $testflightUrl,
    ) {
    }

    #[Route('', name: 'app_app_waitlist', methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderPage($this->createForm(AppWaitlistType::class, new AppWaitlistEntry()));
    }

    #[Route('', name: 'app_app_waitlist_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $entry = new AppWaitlistEntry();
        $form = $this->createForm(AppWaitlistType::class, $entry);
        $form->handleRequest($request);

        // Erst nach handleRequest prüfen, damit ein GET-Aufruf der Seite nie
        // Kontingent verbraucht – gedeckelt wird das Absenden, nicht das Lesen
        // (AK-45).
        //
        // ⚠ BF-11: `consume(0)` fragt ab, ohne zu verbrauchen — und ist deshalb
        // keine Prüfung: `SlidingWindowLimiter` vergleicht verfügbar >= angefordert,
        // und 0 >= 0 gilt auch bei erschöpftem Kontingent. Maßgeblich ist
        // `isAllowed()`; der Verbrauch steht unten, wo die Vormerkung entsteht.
        $limiter = ActionLimiter::for($this->waitlistLimiter, $request->getClientIp());

        if (!$limiter->isAllowed()) {
            $this->addFlash('error', $this->translator->trans('flash.app_waitlist_rate_limited'));

            return $this->renderPage($form, Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Honeypot: Bots füllen jedes Feld aus. Sie bekommen exakt dieselbe
        // Antwort wie ein echter Absender – nur wird nichts gespeichert und
        // nichts versendet. Ein Validierungsfehler würde die Falle verraten.
        if ($form->isSubmitted() && '' !== trim((string) $form->get('website')->getData())) {
            return $this->successResponse($request);
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            // AbstractController::render() setzt für ein submitted-invalides
            // Formular selbst 422; Turbo rendert die Seite daraufhin neu.
            //
            // ⚠ Auf diesem Pfad darf `setRequestFormat()` NICHT laufen — die
            // Antwort muss `text/html` bleiben, sonst rendert Turbo die
            // Meldungen nicht (AK-12).
            return $this->renderPage($form);
        }

        // ⚠ **BF-118: Der Limiter geht mit.** Der Zweig steht weiterhin vor dem
        // Verbrauch — wer sich ein zweites Mal einträgt, hat nichts falsch
        // gemacht, und ein reiner Hinweis „steht schon da" kostet nichts. Aber
        // einer seiner Fälle **verschickt eine Mail**, und der verbraucht.
        // Vorher tat er es nicht: Fünf Absendevorgänge auf eine fremde,
        // abgelaufene Vormerkung ergaben fünf Mails bei unverändertem
        // Kontingent — ein Versandweg gegen Dritte ohne jeden Deckel.
        $bestehend = $this->entries->findOneByEmail($entry->getEmail());

        if (null !== $bestehend) {
            return $this->handleDuplicate($request, $bestehend, $limiter);
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
        // Adresse, und das tut der Bestätigungsablauf.
        if (true === $form->get('marketingConsent')->getData()) {
            $entry->setMarketingConsentAt(new \DateTimeImmutable());
        }

        try {
            $versandGelungen = $this->sendConfirmation($entry);
        } catch (UniqueConstraintViolationException) {
            // ⚠ **BF-121: das Wettrennen.** Zwischen `findOneByEmail()` weiter
            // oben und diesem `flush()` liegt ein Datenbank-Roundtrip; zwei
            // gleichzeitige Absendevorgänge derselben Adresse haben beide
            // `null` gesehen. Entschieden wird es am Unique-Index — und die
            // Ausnahme darf nicht als 500er beim Nutzer landen: Für ihn ist es
            // eine Dublette wie jede andere, und AK-15 verlangt dieselbe
            // Antwort wie beim ersten Mal (EC-06).
            //
            // Kein erneuter Versand: Die Anfrage, die das Rennen gewonnen hat,
            // verschickt die Mail bereits.
            return $this->successResponse($request);
        }

        if (!$versandGelungen) {
            // Der Eintrag steht bereits – der Interessent soll nicht den
            // Eindruck bekommen, die Vormerkung sei verloren (AK-20).
            $this->addFlash('warning', $this->translator->trans('flash.app_waitlist_email_failed'));

            return $this->redirectToRoute('app_app_waitlist');
        }

        return $this->successResponse($request);
    }

    /**
     * Die Adresse steht bereits auf der Liste (AK-15, AK-16, AK-17).
     *
     * ⚠ **Die Antwort ist in allen drei Fällen dieselbe wie beim ersten Mal.**
     * Eine Meldung „steht schon auf der Liste" machte die Warteliste von außen
     * abfragbar: Ein Fremder prüfte damit, ob eine Adresse eingetragen ist.
     * Dasselbe Muster wie die Anti-Enumeration in `Api\V1\AuthController`.
     */
    private function handleDuplicate(
        Request $request,
        AppWaitlistEntry $bestehend,
        ActionLimiter $limiter,
    ): Response {
        // Selbst bestätigt: Es gibt nichts zu tun. Keine weitere Mail, und der
        // Eintrag — Plattform eingeschlossen — bleibt unverändert (AK-16).
        // Kein Verbrauch: Hier findet keine Handlung statt.
        if ($bestehend->hasSelfConfirmed()) {
            return $this->successResponse($request);
        }

        // ⚠ Noch offen und der Link ist abgelaufen: Ohne diesen Zweig wäre der
        // Vorgang eine Sackgasse. Der alte Link trägt nicht mehr (7 Tage), ein
        // neuer entstünde nie, weil der Unique-Index einen zweiten Eintrag
        // verhindert — der Interessent käme nie hinein (AK-17).
        if ($this->confirmationService->isExpired($bestehend)) {
            // ⚠ BF-118: Erst verbrauchen, dann versenden. Ab hier geht eine
            // Mail an eine Adresse, die der Absender frei gewählt hat.
            $limiter->consume();

            // ⚠ BF-117: **Frist mit erneuern, nicht nur den Token.**
            // `isExpired()` misst an `createdAt`; ein bloß neuer Token wäre im
            // selben Augenblick wieder abgelaufen, und der Link in der neuen
            // Mail liefe auf 410. `register()` stellt den Token selbst aus —
            // hier zählt, dass die Frist vorher zurückgesetzt ist.
            $bestehend->renewConfirmationWindow();

            $this->sendConfirmation($bestehend);
        }

        return $this->successResponse($request);
    }

    /**
     * Bestätigungsmail — **ohne** TestFlight-Link (AK-19).
     *
     * @return bool false, wenn der Versand scheiterte; der Eintrag ist dann
     *              dennoch gespeichert
     */
    private function sendConfirmation(AppWaitlistEntry $entry): bool
    {
        return $this->confirmationService->register(
            $entry,
            'app_app_waitlist_confirm',
            'email/app/confirmation.html.twig',
            'email.app_confirm_subject',
            revokeRoute: 'app_app_waitlist_revoke',
        );
    }

    #[Route('/confirmation/{token}', name: 'app_app_waitlist_confirm', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function confirm(string $token): Response
    {
        $entry = $this->entries->findOneByConfirmationToken($token);
        $state = $this->confirmationService->confirm($entry);

        if (WaitlistConfirmationService::RESULT_CONFIRMED === $state && $entry) {
            $this->sendBetaAccess($entry);
        }

        return $this->render('app_waitlist/confirmation.html.twig', [
            'state' => $state,
            'entry' => $entry,
        ], match ($state) {
            // ⚠ 410 statt 404 bei einem abgelaufenen Link (BF-36). Der
            // Unterschied ist nicht Kosmetik: 404 heißt „gab es nie", 410 heißt
            // „gab es, ist weg" — und genau das ist hier der Fall.
            WaitlistConfirmationService::RESULT_INVALID => new Response(null, Response::HTTP_NOT_FOUND),
            WaitlistConfirmationService::RESULT_EXPIRED => new Response(null, Response::HTTP_GONE),
            default => null,
        });
    }

    /**
     * Die zweite Mail: bei iOS mit TestFlight-Link, bei Android mit dem
     * Hinweis, dass wir uns melden (AK-22, AK-23, AK-24).
     *
     * ⚠ **Keine interne Meldung ans Team** — anders als B14 und B15. Dort muss
     * ein Mensch zurückrufen; hier gibt es nichts zu tun, der Zugang läuft über
     * den Link. Eine Mail je Vormerkung wäre Lärm, der die beiden Meldungen
     * entwertet, die tatsächlich eine Handlung verlangen.
     */
    private function sendBetaAccess(AppWaitlistEntry $entry): void
    {
        // ⚠ **Betreff und Rumpf hängen an DERSELBEN Bedingung.** Sie allein aus
        // `hasBeta()` abzuleiten war falsch: Bei iOS mit leerem
        // `app.testflight_url` ginge „Deine Beta ist da" hinaus, während der
        // Rumpf nach AK-24 korrekt keinen Beta-Abschnitt trägt — eine Mail, die
        // sich in der Betreffzeile selbst widerspricht. Lokal und in der
        // Testsuite ist genau das der Regelfall, weil der Parameter dort leer ist.
        $linkVorhanden = true === $entry->getPlatform()?->hasBeta() && '' !== $this->testflightUrl;

        $this->confirmationService->notifyRequester(
            $entry,
            'email/app/beta_access.html.twig',
            $linkVorhanden ? 'email.app_beta_subject' : 'email.app_soon_subject',
            'app_app_waitlist_revoke',
            // ⚠ Der Link wird nur durchgereicht, wenn er auch trägt. Andernfalls
            // stünde er im Kontext bereit, und ein Fehler in der Vorlage zeigte
            // ihn jemandem, für den es keine Beta gibt.
            ['testflightUrl' => $linkVorhanden ? $this->testflightUrl : null],
        );

        // ⚠ Wird auch gesetzt, wenn der Versand scheiterte: Festgehalten wird,
        // dass die Mail **erzeugt** wurde. Ein leeres Feld nach einem
        // Transportfehler sähe aus, als wäre die Bestätigung nie durchgelaufen
        // — und genau das ist sie.
        $entry->setBetaLinkSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Vormerkung zurückziehen (Art. 7 Abs. 3 DSGVO, BF-37).
     *
     * ⚠ Der Eintrag wird gelöscht, nicht markiert. Ein Widerruf, nach dem
     * Adresse und Einwilligungszeitpunkt weiter in der Datenbank stehen, ist
     * keiner. Der Link steht in **beiden** Mails.
     *
     * ⚠ Ein zweiter Aufruf desselben Links findet nichts mehr — der Eintrag ist
     * weg. Die Vorlage stellt `invalid` auf dieser Route deshalb als „bereits
     * ausgetragen" dar und nicht als Fehler (AK-33).
     */
    #[Route('/abmelden/{token}', name: 'app_app_waitlist_revoke', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function revoke(string $token): Response
    {
        $state = $this->confirmationService->revoke($this->entries->findOneByConfirmationToken($token));

        return $this->render('app_waitlist/confirmation.html.twig', [
            'state' => $state,
            'entry' => null,
            'revoked' => true,
        ]);
    }

    /**
     * Erfolgsantwort – identisch für echte Vormerkungen, für Dubletten und für
     * Honeypot-Treffer. Mit Turbo wird nur das Formular ersetzt, ohne Turbo
     * greift der klassische Redirect samt Flash (AK-10, AK-11).
     */
    private function successResponse(Request $request): Response
    {
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('app_waitlist/success.stream.html.twig');
        }

        $this->addFlash('success', $this->translator->trans('flash.app_waitlist_submitted'));

        return $this->redirectToRoute('app_app_waitlist');
    }

    private function renderPage(FormInterface $form, ?int $status = null): Response
    {
        $response = $this->render('app_waitlist/index.html.twig', [
            'form' => $form,
            // Damit die Seite sagen kann, ob die Beta gerade offen ist, ohne
            // dass die Vorlage den Parameter selbst kennt.
            'betaOffen' => '' !== $this->testflightUrl,
        ]);

        if (null !== $status) {
            $response->setStatusCode($status);
        }

        return $response;
    }
}
