<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Übersetzt unsere Nutzer in die Form, die WebAuthn erwartet.
 *
 * Bewusst NICHT mit `CanRegisterUserEntity`/`CanGenerateUserEntity`: Konten
 * entstehen weiterhin nur über die Registrierung mit E-Mail und Passwort. Ohne
 * diese beiden Schnittstellen lehnt das Bundle jeden Versuch ab, über einen
 * Passkey ein neues Konto anzulegen – die Absicherung liegt damit in der
 * Struktur und nicht in einer Konfigurationszeile, die man übersehen kann.
 */
final readonly class WebauthnUserEntityRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $user = $this->userRepository->findOneBy(['email' => $username]);

        if (!$user instanceof User) {
            return null;
        }

        // Der Handle entsteht hier, weil dies der erste Punkt im Ablauf ist, an
        // dem feststeht, für wen ein Passkey angelegt werden soll. Vorher wäre
        // er für jedes Konto zu erzeugen, das nie einen Passkey benutzt.
        if ($user->getWebauthnHandle() === null) {
            $user->obtainWebauthnHandle();
            $this->entityManager->flush();
        }

        return $this->toUserEntity($user);
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $user = $this->userRepository->findOneBy(['webauthnHandle' => $userHandle]);

        return $user instanceof User ? $this->toUserEntity($user) : null;
    }

    private function toUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            (string) $user->getEmail(),
            $user->obtainWebauthnHandle(),
            (string) $user->getName(),
        );
    }
}
