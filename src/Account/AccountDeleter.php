<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Löscht ein Konto endgültig (Art. 17 DSGVO).
 *
 * Was verschwindet: der Nutzerdatensatz, seine Passkeys (Kaskade) und die
 * Avatar-Datei im Dateisystem.
 *
 * ⚠ **Was bleibt, ist eine Entscheidung und keine Nachlässigkeit:** Restaurants
 * und Vorschläge, die dieser Nutzer eingereicht hat, überleben — ihr Bezug auf
 * die Person wird über `ON DELETE SET NULL` gekappt. Eine Angabe darüber, ob ein
 * Lokal eine Rampe hat, ist eine Sachangabe; sie mitzulöschen nähme anderen
 * Menschen eine Auskunft weg, die sie brauchen, und wäre von Art. 17 nicht
 * gefordert.
 *
 * ⚠ **Der letzte Admin kann sich nicht selbst löschen.** Das Projekt hat genau
 * ein Admin-Konto (B19/FB-01); ohne diese Sperre wäre der Verwaltungsbereich nach
 * einem unbedachten Klick unerreichbar, und es gäbe keinen Weg zurück.
 */
final readonly class AccountDeleter
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $users,
        private AvatarUploadService $avatars,
    ) {
    }

    public function istLetzterAdmin(User $user): bool
    {
        if (!\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        return 1 >= \count($this->users->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :rolle')
            ->setParameter('rolle', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult());
    }

    public function delete(User $user): void
    {
        // Erst die Datei, dann die Zeile: Ein Rollback nach dem `unlink` ließe ein
        // Konto ohne Bild zurück — ärgerlich, aber reparierbar. Umgekehrt bliebe
        // ein Bild ohne Konto liegen, und genau das ist der Fehler, den BF-53
        // beschreibt.
        if (null !== $user->getAvatarFilename()) {
            $this->avatars->delete($user);
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
