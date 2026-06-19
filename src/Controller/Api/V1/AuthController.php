<?php

namespace App\Controller\Api\V1;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/auth')]
#[OA\Tag(name: 'Auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Wird vom json_login-Authenticator (LexikJWT) abgefangen und gibt das Token zurück.
     * Der Methodenrumpf wird nie erreicht.
     */
    #[Route('/login', name: 'api_v1_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new \LogicException('Wird vom json_login-Authenticator behandelt.');
    }

    /**
     * Registriert einen neuen Nutzer und stößt die E-Mail-Verifikation an.
     * Gibt KEIN Token zurück – Login erst nach Bestätigung (wie im Web).
     */
    #[Route('/register', name: 'api_v1_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MailerInterface $mailer,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->error(400, 'Ungültiger JSON-Body.');
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        $violations = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $violations['name'] = 'Der Name muss zwischen 2 und 100 Zeichen lang sein.';
        }
        if ($email === '' || filter_var($email, \FILTER_VALIDATE_EMAIL) === false) {
            $violations['email'] = 'Bitte eine gültige E-Mail-Adresse angeben.';
        }
        if (mb_strlen($password) < 8) {
            $violations['password'] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        }

        if ($violations !== []) {
            return new JsonResponse([
                'error' => [
                    'code' => 422,
                    'message' => 'Validierung fehlgeschlagen.',
                    'violations' => $violations,
                ],
            ], 422);
        }

        // User-Enumeration vermeiden: identische Antwort, egal ob die E-Mail bereits
        // existiert. Eine bestehende Adresse bekommt einen Hinweis statt einer
        // Bestätigungsmail; ein neuer Account wird normal angelegt. Das Passwort wird
        // in beiden Fällen gehasht, um auch die Antwortzeit nicht zu verraten.
        $hashedPassword = $passwordHasher->hashPassword(new User(), $password);

        if ($userRepository->findOneBy(['email' => $email]) !== null) {
            $this->sendAccountExistsHint($mailer, $email);
        } else {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($hashedPassword);
            $token = $user->generateVerificationToken();

            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendVerificationEmail($mailer, $user, $token);
        }

        return new JsonResponse([
            'message' => 'Fast geschafft! Wenn die Angaben gültig sind, haben wir dir eine E-Mail geschickt. Bitte bestätige deine Adresse.',
        ], 201);
    }

    private function sendVerificationEmail(MailerInterface $mailer, User $user, string $token): void
    {
        $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = (new TemplatedEmail())
            ->to((string) $user->getEmail())
            ->subject($this->translator->trans('email.verify_subject'))
            ->htmlTemplate('email/verification.html.twig')
            ->context([
                'user' => $user,
                'verifyUrl' => $verifyUrl,
            ]);

        try {
            $mailer->send($message);
        } catch (TransportExceptionInterface) {
            // Nutzer ist angelegt; nur der Mailversand schlug fehl.
        }
    }

    /**
     * Hinweis an eine bereits registrierte Adresse, ohne dies dem Aufrufer zu verraten.
     */
    private function sendAccountExistsHint(MailerInterface $mailer, string $email): void
    {
        $message = (new Email())
            ->to($email)
            ->subject('Du hast bereits ein Konto bei Endlech.lu')
            ->text(
                "Hallo,\n\n"
                ."soeben gab es einen Registrierungsversuch mit dieser E-Mail-Adresse. "
                ."Du hast bereits ein Konto bei Endlech.lu – bitte melde dich einfach an. "
                ."Falls du dein Passwort vergessen hast, kannst du es über die Anmeldeseite zurücksetzen.\n\n"
                ."Warst du das nicht, kannst du diese E-Mail ignorieren.\n\n"
                .'Dein Endlech.lu-Team',
            );

        try {
            $mailer->send($message);
        } catch (TransportExceptionInterface) {
            // Hinweis nicht zustellbar – nach außen kein Unterschied.
        }
    }

    private function error(int $code, string $message): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => $code, 'message' => $message]], $code);
    }
}
