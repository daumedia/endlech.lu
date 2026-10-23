<?php

declare(strict_types=1);

namespace App\Controller;

use App\Account\NewsletterSubscription;
use App\Entity\User;
use App\Form\AvatarConstraints;
use App\Form\OnboardingAvatarType;
use App\Form\OnboardingNewsletterType;
use App\Onboarding\OnboardingFlow;
use App\Onboarding\OnboardingPosition;
use App\RateLimit\ActionLimiter;
use App\Service\AvatarUploadService;
use App\Usage\UsageEventCatalogue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Die Einführung für neue Konten (Feature 12).
 *
 * Vier gewöhnliche Seiten, ohne JavaScript bedienbar. Jede arbeitet ausschließlich mit dem
 * angemeldeten Konto; keine Adresse und kein Formular trägt eine Kontokennung (AK-57). Den
 * Zugang regelt `access_control` (`^/[a-z]{2}/einfuehrung`, AK-10), die Bestätigung prüft
 * dieser Controller (AK-11).
 */
#[Route('/einfuehrung')]
final class OnboardingController extends AbstractController
{
    public function __construct(
        private readonly OnboardingFlow $flow,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'limiter.newsletter_toggle')]
        private readonly RateLimiterFactoryInterface $newsletterLimiter,
    ) {
    }

    #[Route(
        '/{schritt}',
        name: 'app_onboarding',
        requirements: ['schritt' => 'willkommen|eintraege|profilbild|newsletter'],
        defaults: ['schritt' => OnboardingFlow::WELCOME],
        methods: ['GET'],
    )]
    public function show(string $schritt, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->konto();

        if (!$user->isVerified()) {
            return $this->erstBestaetigen();
        }

        $position = $this->flow->locate($user, $schritt);

        // AK-13: Wer schon eingewilligt hat, hat keinen Newsletter-Bildschirm.
        if (null === $position) {
            return $this->redirectToRoute('app_restaurant_index');
        }

        // AK-02: „gesehen" ab der ersten Auslieferung — wer den Tab schließt, klickt nichts.
        //
        // ⚠ BF-158: Ein Vorabladen ist keine Anzeige. Turbo 8 holt Links beim Überfahren mit der
        // Maus vorab und schickt dabei `X-Sec-Purpose: prefetch`; ohne diese Prüfung verlor ein
        // Konto, das nach AK-03 noch fällig war, die Einführung, ohne sie je gesehen zu haben.
        // Die Links, die in die Einführung führen, sind zusätzlich vom Vorabladen ausgenommen —
        // Turbo benutzte die vorab geholte Antwort sonst beim echten Klick weiter, und die echte
        // Anzeige käme nie hier an.
        //
        // ⚠ BF-170: nur bei GET. Die Route nimmt HEAD mit (Symfony), und eine HEAD-Anfrage zeigt nichts an.
        if (null === $user->getOnboardingShownAt() && $request->isMethod('GET') && !$this->istVorabladen($request)) {
            $user->markOnboardingShown(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->bildschirm($position, $user);
    }

    #[Route('/profilbild', name: 'app_onboarding_avatar', methods: ['POST'])]
    public function avatar(Request $request, AvatarUploadService $avatars): Response
    {
        $user = $this->konto();

        if (!$user->isVerified()) {
            return $this->erstBestaetigen();
        }

        // ⚠ BF-159 (Muster BF-58): Überschreitet der Upload `post_max_size`, verwirft PHP den
        // GESAMTEN Rumpf — Datei, Felder, CSRF-Token. Symfony hängte den Fehler an das Formular
        // selbst, und die Seite zeigte 422 ohne jede Meldung. Hier steht stattdessen dieselbe
        // Meldung wie bei jedem zu großen Bild am Feld (AK-26). Ohne Token-Prüfung, weil es kein
        // Token gibt — gespeichert wird in diesem Zweig nichts. `CONTENT_LENGTH` überlebt als Kopf.
        if (AvatarConstraints::bodyWasDiscarded($request)) {
            $form = $this->createForm(OnboardingAvatarType::class, null, ['csrf_protection' => false]);
            $form->submit([]);
            $form->get('avatar')->addError(new FormError(AvatarConstraints::tooLargeMessage($this->translator)));

            return $this->bildschirm($this->avatarPosition($user), $user, avatarForm: $form);
        }

        $form = $this->createForm(OnboardingAvatarType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $datei = $form->get('avatar')->getData();

            // Ohne Datei abgeschickt ist kein Fehler, es geschieht nur nichts (AK-15).
            if ($datei instanceof UploadedFile) {
                $avatars->upload($datei, $user);
                $this->addFlash('success', $this->translator->trans('onboarding.avatar.saved'));
            }

            return $this->redirectToRoute('app_onboarding', ['schritt' => OnboardingFlow::AVATAR]);
        }

        // Ungültig abgeschickt → 422 mit der Meldung am Feld; `render()` setzt den Status selbst,
        // sobald ein abgeschicktes, ungültiges Formular übergeben wird (AK-26).
        return $this->bildschirm($this->avatarPosition($user), $user, avatarForm: $form);
    }

    #[Route('/newsletter', name: 'app_onboarding_newsletter', methods: ['POST'])]
    public function newsletter(Request $request, NewsletterSubscription $newsletter): Response
    {
        $user = $this->konto();

        if (!$user->isVerified()) {
            return $this->erstBestaetigen();
        }

        $form = $this->createForm(OnboardingNewsletterType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $position = $this->flow->locate($user, OnboardingFlow::NEWSLETTER);

            if (null === $position) {
                return $this->redirectToRoute('app_restaurant_index');
            }

            return $this->bildschirm($position, $user, newsletterForm: $form);
        }

        // AK-32: Ohne Häkchen geschieht nichts — kein Aufruf, kein Kontingent.
        if (true === $form->get('consent')->getData()) {
            // AK-59: dasselbe Kontingent wie der Schalter im Profil, am Konto gezählt.
            $limiter = ActionLimiter::for($this->newsletterLimiter, $user->getUserIdentifier());

            if (!$limiter->isAllowed()) {
                $this->addFlash('error', $this->zuVieleAenderungen($limiter));

                return $this->redirectToRoute('app_onboarding', ['schritt' => OnboardingFlow::NEWSLETTER]);
            }

            // AK-60: verbraucht wird nur, wenn sich der Stand tatsächlich ändert.
            if ($newsletter->subscribe($user, $request->getLocale())) {
                $limiter->consume();
                $this->addFlash('success', $this->translator->trans('newsletter.flash_subscribed'));
            }
        }

        // ⚠ BF-171: „abgeschlossen" erst hier zählen, auf der Seite NACH dem gelungenen Absenden. Der
        // Knopf selbst wusste nicht, ob der Server das Absenden annimmt (Deckel, Token) — er zählte auch
        // abgewiesene Versuche. Die Flash-Art `usage_event` rendert das Partial als Messauslöser.
        $this->addFlash('usage_event', UsageEventCatalogue::ONBOARDING_COMPLETED);

        return $this->redirectToRoute('app_restaurant_index');
    }

    private function avatarPosition(User $user): OnboardingPosition
    {
        $position = $this->flow->locate($user, OnboardingFlow::AVATAR);
        \assert(null !== $position);

        return $position;
    }

    private function istVorabladen(Request $request): bool
    {
        foreach (['X-Sec-Purpose', 'Sec-Purpose', 'Purpose'] as $kopf) {
            if (str_contains(strtolower((string) $request->headers->get($kopf)), 'prefetch')) {
                return true;
            }
        }

        return false;
    }

    private function konto(): User
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        return $user;
    }

    private function erstBestaetigen(): Response
    {
        $this->addFlash('warning', $this->translator->trans('onboarding.verify_first'));

        return $this->redirectToRoute('app_verify_notice');
    }

    private function zuVieleAenderungen(ActionLimiter $limiter): string
    {
        return $this->translator->trans('newsletter.flash_rate_limited', [
            '%minutes%' => max(1, (int) ceil($limiter->retryAfter() / 60)),
        ]);
    }

    private function bildschirm(
        OnboardingPosition $position,
        User $user,
        ?FormInterface $avatarForm = null,
        ?FormInterface $newsletterForm = null,
    ): Response {
        $parameter = ['position' => $position, 'user' => $user];

        if (OnboardingFlow::AVATAR === $position->step) {
            $parameter['avatarForm'] = $avatarForm ?? $this->createForm(OnboardingAvatarType::class, null, [
                'action' => $this->generateUrl('app_onboarding_avatar'),
            ]);
        }

        if (OnboardingFlow::NEWSLETTER === $position->step) {
            $parameter['newsletterForm'] = $newsletterForm ?? $this->createForm(OnboardingNewsletterType::class, null, [
                'action' => $this->generateUrl('app_onboarding_newsletter'),
            ]);
        }

        return $this->render('onboarding/'.$position->step.'.html.twig', $parameter);
    }
}
