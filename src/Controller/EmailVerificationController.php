<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailVerificationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'limiter.verify_resend')]
        private readonly RateLimiterFactoryInterface $resendLimiter,
    ) {
    }
    #[Route('/verify', name: 'app_verify_notice')]
    public function notice(): Response
    {
        return $this->render('email_verification/notice.html.twig');
    }

    /**
     * Das Requirement ist nicht kosmetisch: Ohne es fängt diese Route jeden
     * Ein-Segment-Pfad unter /verify/ ab – auch /verify/resend, das weiter unten
     * deklariert ist und deshalb nie erreicht wurde. Ein Token ist immer
     * bin2hex(random_bytes(32)), also genau 64 Zeichen aus [a-f0-9].
     */
    #[Route('/verify/{token}', name: 'app_verify_email', requirements: ['token' => '[a-f0-9]{64}'])]
    public function verify(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $userRepository->findByVerificationToken($token);

        if (!$user) {
            $this->addFlash('error', $this->translator->trans('flash.verify_invalid_link'));

            return $this->redirectToRoute('app_home');
        }

        if ($user->isVerificationTokenExpired()) {
            $this->addFlash('error', $this->translator->trans('flash.verify_expired'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        $entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.verify_success'));

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify/resend', name: 'app_verify_resend')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function resend(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            $this->addFlash('info', $this->translator->trans('flash.verify_already'));

            return $this->redirectToRoute('app_home');
        }

        // Gedeckelt, bevor ein Token erzeugt oder eine Mail verschickt wird: Das
        // Ziel ist ein fremdes Postfach, sobald jemand die Adresse eines anderen
        // an seinem Konto hinterlegt. Jeder Aufruf entwertet zudem den zuvor
        // versandten Link.
        $limit = $this->resendLimiter->create($request->getClientIp() ?? 'anonymous')->consume(1);

        if (!$limit->isAccepted()) {
            $this->addFlash('error', $this->translator->trans('flash.verify_resend_rate_limited'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $token = $user->generateVerificationToken();
        $entityManager->flush();

        $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            // Siehe RegistrationController: ohne locale() rendert der Worker das
            // Template ohne Request-Sprache.
            ->locale($request->getLocale())
            ->subject($this->translator->trans('email.verify_subject'))
            ->htmlTemplate('email/verification.html.twig')
            ->context([
                'user' => $user,
                'verifyUrl' => $verifyUrl,
            ]);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface) {
            $this->addFlash('error', $this->translator->trans('flash.verify_resend_failed'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $this->addFlash('success', $this->translator->trans('flash.verify_resent'));

        return $this->redirectToRoute('app_verify_notice');
    }
}
