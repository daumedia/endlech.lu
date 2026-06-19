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
        } elseif ($userRepository->findOneBy(['email' => $email]) !== null) {
            $violations['email'] = 'Diese E-Mail-Adresse ist bereits registriert.';
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

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $token = $user->generateVerificationToken();

        $entityManager->persist($user);
        $entityManager->flush();

        $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = (new TemplatedEmail())
            ->to($user->getEmail())
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

        return new JsonResponse([
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
            ],
            'message' => 'Registrierung erfolgreich. Bitte bestätige deine E-Mail-Adresse.',
        ], 201);
    }

    private function error(int $code, string $message): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => $code, 'message' => $message]], $code);
    }
}
