<?php

namespace App\Controller;

use App\Marketing\MarketingContactRegistry;
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
        MarketingContactRegistry $marketingContacts,
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

        // Feature 04 / AK-05: Erst hier ist belegt, dass die Adresse dem Konto
        // gehört – vorher geht sie nicht nach Brevo (EC-03). Der Aufruf steht
        // deshalb NACH setIsVerified(true): Die Registry prüft die Bedingung
        // selbst, und davor liefe die Prüfung ins Leere.
        //
        // Sie schreibt nur ins Auftragsbuch und persist()et; der flush() unten
        // nimmt die neue Zeile mit. Ein fremder Dienst wird hier nicht gerufen
        // (AK-17).
        $marketingContacts->recordUser($user);

        $entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.verify_success'));

        return $this->redirectToRoute('app_login');
    }

    /**
     * Bestätigt eine im Profil gewünschte neue Adresse (QA B04, BUG-15).
     *
     * Bewusst ohne Anmeldepflicht: Der Token IST der Nachweis, und zwar genau
     * der, um den es geht – Zugriff auf das neue Postfach. Wer den Link im
     * Postfach anklickt, sitzt oft an einem Gerät ohne offene Sitzung; eine
     * Anmeldepflicht machte den Vorgang dort unbenutzbar, ohne etwas zu sichern.
     *
     * Zwei Segmente, deshalb kein Konflikt mit /verify/{token}.
     */
    #[Route('/verify/email-change/{token}', name: 'app_email_change_confirm', requirements: ['token' => '[a-f0-9]{64}'])]
    public function confirmEmailChange(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MarketingContactRegistry $marketingContacts,
    ): Response {
        $user = $userRepository->findByPendingEmailToken($token);

        if (!$user) {
            $this->addFlash('error', $this->translator->trans('flash.verify_invalid_link'));

            return $this->redirectToRoute('app_home');
        }

        if ($user->isPendingEmailTokenExpired()) {
            $user->clearPendingEmail();
            $entityManager->flush();

            $this->addFlash('error', $this->translator->trans('flash.profile_email_expired'));

            return $this->redirectToRoute('app_profile');
        }

        // In der Zwischenzeit kann sich jemand anderes mit genau dieser Adresse
        // registriert haben – pending_email trägt keine Eindeutigkeit, email
        // dagegen schon. Ohne diese Prüfung liefe der flush() in eine
        // Unique-Verletzung und der Nutzer sähe einen 500er.
        if ($userRepository->findOneBy(['email' => $user->getPendingEmail()]) !== null) {
            $user->clearPendingEmail();
            $entityManager->flush();

            $this->addFlash('error', $this->translator->trans('flash.profile_email_taken'));

            return $this->redirectToRoute('app_profile');
        }

        // Feature 04 / EC-02: Das Auftragsbuch findet seine Zeile über die
        // E-Mail-Adresse – und genau die ändert sich hier. Nach
        // confirmEmailChange() steht am Konto bereits die neue; wer erst dann
        // nachschlägt, legt eine zweite Zeile an, während die alte mit der
        // aufgegebenen Adresse stehenbleibt und weiter bespielt wird.
        $previousEmail = $user->getEmail();

        $user->confirmEmailChange();

        // Fortgeschrieben wird ohne Rücksicht auf die Werbe-Einwilligung: Ob
        // eine Zeile existiert, entscheidet die Registry. Gibt es keine, läuft
        // der Aufruf folgenlos durch; gibt es eine, gehört sie zu dieser
        // Adresse und muss mitziehen – auch wenn sie aus einer Warteliste
        // stammt. Eine neue Einwilligung ist das nicht: consent_at bleibt
        // unberührt.
        $marketingContacts->changeEmail($previousEmail, $user->getEmail());

        $entityManager->flush();

        // Die Adresse ist der Anmeldename (User::getUserIdentifier()). Nach dem
        // Wechsel passt die laufende Sitzung nicht mehr zum Konto und Symfony
        // meldet ab – das ist richtig so und wird deshalb angesagt, statt den
        // Nutzer wortlos auf der Anmeldeseite landen zu lassen.
        $this->addFlash('success', $this->translator->trans('flash.profile_email_changed'));

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
