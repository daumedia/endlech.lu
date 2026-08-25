<?php

namespace App\Controller;

use App\Account\AccountDataExporter;
use App\Account\AccountDeleter;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\RateLimit\ActionLimiter;
use App\Repository\RestaurantRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
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
/**
 * ⚠ BF-15: `IS_AUTHENTICATED_REMEMBERED`, nicht `_FULLY`. Vorher zeigte die
 * Kopfzeile den Nutzer als angemeldet, und ein Klick auf sein eigenes Profil warf
 * ihn auf die Anmeldeseite — „Angemeldet bleiben" hielt für alles außer der einen
 * Seite, für die man es anhakt.
 *
 * Die empfindlichen Wege sind einzeln abgesichert und bleiben es: Passwortwechsel
 * und Kontolöschung verlangen das aktuelle Passwort, die Adressänderung eine
 * Bestätigung per Mail (BF-19).
 */
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RestaurantRepository $restaurantRepository,
        #[Autowire(service: 'limiter.password_change')]
        private readonly RateLimiterFactoryInterface $passwordChangeLimiter,
        // ⚠ BF-21: Am KONTO gezählt, nicht an der IP — derselbe Grund wie beim
        // Passwortwechsel. Der Weg verschickt seit der BF-19-Reparatur zwei Mails
        // je Durchlauf, eine davon an eine FREI GEWÄHLTE fremde Adresse; zehn
        // Durchläufe erzeugten zwanzig Mails. Wer eine Sitzung gekapert hat,
        // wechselt die IP mühelos, das Konto nicht.
        #[Autowire(service: 'limiter.email_change')]
        private readonly RateLimiterFactoryInterface $emailChangeLimiter,
        // Feature 01: Der Export liest den halben Bestand eines Kontos zusammen.
        #[Autowire(service: 'limiter.account_export')]
        private readonly RateLimiterFactoryInterface $exportLimiter,
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
                $limiter = ActionLimiter::for($this->emailChangeLimiter, $user->getUserIdentifier());

                if (!$limiter->isAllowed()) {
                    $this->addFlash('error', $this->translator->trans('flash.email_change_rate_limited'));

                    return $this->redirectToRoute('app_profile');
                }

                // Zurück auf die bestätigte Adresse. Eine sofort wirksame Änderung
                // machte aus einer gekaperten Sitzung eine dauerhafte Übernahme:
                // Der rechtmäßige Inhaber könnte sich nicht mehr anmelden und hat
                // keinen Weg zurück, weil es kein Passwort-Zurücksetzen gibt.
                $user->setEmail($bisherigeAdresse);
                $token = $user->requestEmailChange($gewuenschteAdresse);
                $em->flush();

                $limiter->consume();

                $this->sendeAdressbestaetigung($request, $mailer, $user, $bisherigeAdresse, $gewuenschteAdresse, $token);

                $this->addFlash('info', $this->translator->trans('flash.profile_email_pending', ['%email%' => $gewuenschteAdresse]));

                return $this->redirectToRoute('app_profile');
            }

            $em->flush();

            $this->addFlash('success', $this->translator->trans('flash.profile_updated'));

            return $this->redirectToRoute('app_profile');
        }

        // ⚠ BF-22: Ein ungültiges Formular darf den Nutzer nicht abmelden.
        // `handleRequest()` schreibt die eingegebene Adresse in die Entity, BEVOR
        // validiert wird. Bleibt sie dort stehen, wandert der veränderte Nutzer
        // beim Rendern in die Sitzung — und weil `EquatableInterface` die Adresse
        // vergleicht, hält Symfony ihn beim nächsten Aufruf für einen anderen und
        // meldet ihn ab. Man tippt sich also aus dem eigenen Konto.
        //
        // Dieselbe Zeile wie im Erfolgsfall, nur aus dem anderen Grund: Der Wert
        // hat die Validierung gesehen, jetzt muss er wieder weg.
        if ($profileForm->isSubmitted()) {
            $user->setEmail($bisherigeAdresse);
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

    /**
     * Alles herunterladen, was zu diesem Konto gespeichert ist (Art. 20 DSGVO).
     *
     * JSON, weil der Artikel ein „strukturiertes, gängiges, maschinenlesbares
     * Format" verlangt — und weil sich damit prüfen lässt, was drinsteht.
     */
    #[Route('/daten', name: 'app_profile_export', methods: ['GET'])]
    public function exportData(Request $request, AccountDataExporter $exporter): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $limiter = ActionLimiter::for($this->exportLimiter, $user->getUserIdentifier());

        if (!$limiter->isAllowed()) {
            $this->addFlash('error', $this->translator->trans('flash.export_rate_limited'));

            return $this->redirectToRoute('app_profile');
        }

        $limiter->consume();

        $inhalt = json_encode(
            $exporter->export($user),
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        );

        $antwort = new Response($inhalt);
        $antwort->headers->set('Content-Type', 'application/json; charset=utf-8');
        $antwort->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'endlech-meine-daten.json',
        ));
        // Ein Export gehört in keinen Zwischenspeicher — weder im Browser noch
        // in einem Proxy davor.
        $antwort->headers->set('Cache-Control', 'no-store, private');

        return $antwort;
    }

    /**
     * Konto endgültig löschen (Art. 17 DSGVO).
     *
     * ⚠ Das Passwort ist Pflicht. Ein Klick allein genügt nicht: Eine gekaperte
     * Sitzung könnte sonst ein Konto samt Zugang vernichten, und einen Rückweg
     * gibt es naturgemäß nicht.
     */
    #[Route('/loeschen', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        AccountDeleter $deleter,
        MailerInterface $mailer,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete-account', $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('app_profile');
        }

        if (!$passwordHasher->isPasswordValid($user, $request->request->getString('password'))) {
            $this->addFlash('error', $this->translator->trans('flash.profile_wrong_password'));

            return $this->redirectToRoute('app_profile');
        }

        if ($deleter->istLetzterAdmin($user)) {
            $this->addFlash('error', $this->translator->trans('flash.delete_last_admin'));

            return $this->redirectToRoute('app_profile');
        }

        // Die Mail VOR dem Löschen: Danach gibt es die Adresse nicht mehr, und ein
        // Versandfehler soll die Löschung nicht aufhalten.
        $this->sendeLoeschbestaetigung($mailer, (string) $user->getEmail(), (string) $user->getName(), $request->getLocale());

        $deleter->delete($user);

        // Sitzung abräumen: Ohne das trüge der Container weiterhin einen Nutzer,
        // den es nicht mehr gibt.
        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', $this->translator->trans('flash.account_deleted'));

        return $this->redirectToRoute('app_home');
    }

    private function sendeLoeschbestaetigung(MailerInterface $mailer, string $email, string $name, string $locale): void
    {
        $mail = (new TemplatedEmail())
            ->to($email)
            ->subject($this->translator->trans('email.account_deleted_subject', [], null, $locale))
            ->locale($locale)
            ->htmlTemplate('email/account_deleted.html.twig')
            ->context(['name' => $name]);

        try {
            $mailer->send($mail);
        } catch (TransportExceptionInterface) {
            // Die Löschung läuft trotzdem durch — sie ist das Recht, die Mail ist
            // die Höflichkeit.
        }
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
            //
            // ⚠ Hier wird BEWUSST vor der Prüfung verbraucht — anders als bei
            // Registrierung und Wartelisten (BF-11). Dort ist ein Fehlversuch ein
            // Tippfehler; hier IST der Fehlversuch der Angriff, und genau ihn soll
            // der Deckel zählen. Nicht „vereinheitlichen".
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
