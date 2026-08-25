<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\RestaurantRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RestaurantRepository $restaurantRepository,
        #[Autowire(service: 'limiter.password_change')]
        private readonly RateLimiterFactoryInterface $passwordChangeLimiter,
    ) {
    }

    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $profileForm = $this->createForm(ProfileType::class, $this->getUser(), [
            'action' => $this->generateUrl('app_profile_edit'),
        ]);

        $passwordForm = $this->createForm(ChangePasswordType::class, null, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'submittedRestaurants' => $this->restaurantRepository->findBySubmitter($this->getUser()),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function edit(Request $request, EntityManagerInterface $em, AvatarUploadService $avatarService, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Die bisherige Adresse festhalten, BEVOR das Formular sie überschreibt.
        // ProfileType ist an die Entity gebunden; nach handleRequest() steht dort
        // bereits der eingegebene Wert. Genau der soll aber nicht wirksam werden,
        // solange er nicht bestätigt ist. Die Validierung darf ihn trotzdem sehen:
        // Nur so greift die Prüfung auf Doppelvergabe (UniqueEntity) auf dem Wert,
        // um den es geht.
        $bisherigeAdresse = $user->getEmail();

        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $avatarFile = $profileForm->get('avatar')->getData();
            if ($avatarFile instanceof UploadedFile) {
                $avatarService->upload($avatarFile, $user);
            }

            $gewuenschteAdresse = $user->getEmail();
            $adresseGeaendert = $gewuenschteAdresse !== null
                && strcasecmp($gewuenschteAdresse, (string) $bisherigeAdresse) !== 0;

            if ($adresseGeaendert) {
                // Zurück auf die bestätigte Adresse. Eine sofort wirksame Änderung
                // machte aus einer gekaperten Sitzung eine dauerhafte Übernahme:
                // Der rechtmäßige Inhaber könnte sich nicht mehr anmelden und hat
                // keinen Weg zurück, weil es kein Passwort-Zurücksetzen gibt.
                $user->setEmail($bisherigeAdresse);
                $token = $user->requestEmailChange($gewuenschteAdresse);
                $em->flush();

                $this->sendeAdressbestaetigung($request, $mailer, $user, $bisherigeAdresse, $gewuenschteAdresse, $token);

                $this->addFlash('info', $this->translator->trans('flash.profile_email_pending', ['%email%' => $gewuenschteAdresse]));

                return $this->redirectToRoute('app_profile');
            }

            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.profile_updated'));

            return $this->redirectToRoute('app_profile');
        }

        $passwordForm = $this->createForm(ChangePasswordType::class, null, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'submittedRestaurants' => $this->restaurantRepository->findBySubmitter($this->getUser()),
        ]);
    }

    #[Route('/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            // Gedeckelt am Konto, nicht an der IP: Der Angriff, den das abwehrt,
            // ist das Raten des aktuellen Passworts aus einer gekaperten Sitzung
            // heraus – dort wechselt die IP mühelos, das Konto nicht. Die
            // Anmeldung ist über login_throttling gedrosselt, dieser zweite Weg
            // zur Passwortprüfung war es nicht.
            $limit = $this->passwordChangeLimiter->create($user->getUserIdentifier())->consume(1);

            if (!$limit->isAccepted()) {
                $this->addFlash('error', $this->translator->trans('flash.profile_password_rate_limited'));

                return $this->redirectToRoute('app_profile');
            }

            $currentPassword = $passwordForm->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', $this->translator->trans('flash.profile_wrong_password'));

                return $this->redirectToRoute('app_profile');
            }

            $newPassword = $passwordForm->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.profile_password_changed'));

            return $this->redirectToRoute('app_profile');
        }

        $profileForm = $this->createForm(ProfileType::class, $user, [
            'action' => $this->generateUrl('app_profile_edit'),
        ]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'submittedRestaurants' => $this->restaurantRepository->findBySubmitter($this->getUser()),
        ]);
    }

    /**
     * Schickt den Bestätigungslink an die neue und eine Warnung an die alte Adresse.
     *
     * Die Warnung ist der wirksamere Teil: Wer ein Konto übernehmen will, sitzt im
     * neuen Postfach und sieht die Bestätigung ohnehin. Nur die Meldung an die
     * bisherige Adresse erreicht den rechtmäßigen Inhaber.
     */
    private function sendeAdressbestaetigung(
        Request $request,
        MailerInterface $mailer,
        \App\Entity\User $user,
        string $bisherigeAdresse,
        string $neueAdresse,
        string $token,
    ): void {
        $confirmUrl = $this->generateUrl(
            'app_email_change_confirm',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $bestaetigung = (new TemplatedEmail())
            ->to($neueAdresse)
            ->locale($request->getLocale())
            ->subject($this->translator->trans('email.email_change_subject'))
            ->htmlTemplate('email/email_change.html.twig')
            ->context([
                'user' => $user,
                'oldEmail' => $bisherigeAdresse,
                'newEmail' => $neueAdresse,
                'confirmUrl' => $confirmUrl,
            ]);

        $warnung = (new TemplatedEmail())
            ->to($bisherigeAdresse)
            ->locale($request->getLocale())
            ->subject($this->translator->trans('email.email_change_notice_subject'))
            ->htmlTemplate('email/email_change_notice.html.twig')
            ->context([
                'user' => $user,
                'newEmail' => $neueAdresse,
            ]);

        try {
            $mailer->send($bestaetigung);
            $mailer->send($warnung);
        } catch (TransportExceptionInterface) {
            // Die Vormerkung steht bereits in der Datenbank – ein gescheiterter
            // Versand darf sie nicht zurücknehmen, sonst hinge der Vorgang
            // zwischen zwei Zuständen. Der Nutzer sieht den offenen Vorgang im
            // Profil und kann ihn dort abbrechen.
            $this->addFlash('error', $this->translator->trans('flash.profile_email_mail_failed'));
        }
    }

    #[Route('/avatar/delete', name: 'app_profile_avatar_delete', methods: ['POST'])]
    public function deleteAvatar(Request $request, AvatarUploadService $avatarService): Response
    {
        if ($this->isCsrfTokenValid('delete-avatar', $request->request->getString('_token'))) {
            $avatarService->delete($this->getUser());
            $this->addFlash('success', $this->translator->trans('flash.profile_avatar_deleted'));
        } else {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/email/abbrechen', name: 'app_profile_email_cancel', methods: ['POST'])]
    public function cancelEmailChange(Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('cancel-email-change', $request->request->getString('_token'))) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $user->clearPendingEmail();
            $em->flush();
            $this->addFlash('success', $this->translator->trans('flash.profile_email_cancelled'));
        } else {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));
        }

        return $this->redirectToRoute('app_profile');
    }
}
