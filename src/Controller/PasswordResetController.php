<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordResetRequestType;
use App\Form\PasswordResetType;
use App\RateLimit\ActionLimiter;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Passwort zurücksetzen — der fehlende Rückweg (Feature 01, BF-04).
 *
 * ⚠ Bis heute war ein vergessenes Passwort eine Sackgasse. Seit der
 * BF-19-Reparatur wird eine E-Mail-Änderung nur noch nach Bestätigung wirksam —
 * was richtig ist, aber jeden Ausweg über die Adresse verschließt. Wer sein
 * Passwort vergaß, verlor sein Konto.
 *
 * ⚠ **Anti-Enumeration wie bei der Registrierung:** Die Antwort ist immer
 * dieselbe, egal ob die Adresse existiert. Andernfalls wäre dieses Formular ein
 * Werkzeug, um herauszufinden, wer hier ein Konto hat — und das ist bei einer
 * Barrierefreiheitsplattform eine Angabe, die niemanden etwas angeht.
 */
final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        // ⚠ Jeder Aufruf verschickt eine Mail an eine FREI WÄHLBARE Adresse —
        // dieselbe Lage wie bei der Registrierung, deshalb dieselben Werte.
        #[Autowire(service: 'limiter.password_reset')]
        private readonly RateLimiterFactoryInterface $resetLimiter,
    ) {
    }

    #[Route('/passwort-vergessen', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        // Ein angemeldeter Nutzer braucht das Formular nicht — er ändert sein
        // Passwort im Profil und kennt das alte.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(PasswordResetRequestType::class);
        $form->handleRequest($request);

        $limiter = ActionLimiter::for($this->resetLimiter, $request->getClientIp());

        if ($form->isSubmitted() && !$limiter->isAllowed()) {
            $this->addFlash('error', $this->translator->trans('flash.password_reset_rate_limited'));

            return $this->render('security/password_reset_request.html.twig', [
                'form' => $form,
            ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->consume();

            $email = strtolower(trim((string) $form->get('email')->getData()));
            $user = $this->users->findOneBy(['email' => $email]);

            if ($user instanceof User) {
                $token = $user->generatePasswordResetToken();
                $this->em->flush();
                $this->sendeLink($mailer, $user, $token, $request->getLocale());
            }

            // ⚠ Dieselbe Antwort in beiden Zweigen. Der Unterschied darf sich weder
            // im Text noch im Statuscode zeigen.
            $this->addFlash('success', $this->translator->trans('flash.password_reset_sent'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset_request.html.twig', ['form' => $form]);
    }

    #[Route('/passwort-zuruecksetzen/{token}', name: 'app_password_reset', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $user = $this->users->findOneBy(['passwordResetToken' => $token]);

        if (!$user instanceof User) {
            return $this->render('security/password_reset.html.twig', [
                'state' => 'invalid',
                'form' => null,
            ], new Response(null, Response::HTTP_NOT_FOUND));
        }

        if ($user->isPasswordResetTokenExpired()) {
            return $this->render('security/password_reset.html.twig', [
                'state' => 'expired',
                'form' => null,
            ], new Response(null, Response::HTTP_GONE));
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $user->clearPasswordResetToken();

            // ⚠ Einen offenen Adresswechsel abräumen. Wer ein Konto übernehmen will,
            // stößt zuerst die Adressänderung an und wartet; wird danach das Passwort
            // zurückgesetzt, liefe der Vorgang trotzdem weiter. Der rechtmäßige
            // Inhaber hat gerade bewiesen, dass ihm das Postfach gehört — alles
            // Angefangene davor ist damit hinfällig.
            $user->clearPendingEmail();

            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('flash.password_reset_done'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset.html.twig', [
            'state' => 'form',
            'form' => $form,
        ]);
    }

    private function sendeLink(MailerInterface $mailer, User $user, string $token, string $locale): void
    {
        $url = $this->generateUrl(
            'app_password_reset',
            ['token' => $token, '_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $mail = (new TemplatedEmail())
            ->to((string) $user->getEmail())
            ->subject($this->translator->trans('email.password_reset_subject', [], null, $locale))
            ->locale($locale)
            ->htmlTemplate('email/password_reset.html.twig')
            ->context(['user' => $user, 'resetUrl' => $url]);

        try {
            $mailer->send($mail);
        } catch (TransportExceptionInterface) {
            // Der Token steht; ein Zustellproblem darf die Antwort nicht verraten.
        }
    }
}
