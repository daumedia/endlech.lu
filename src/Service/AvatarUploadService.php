<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Lock\LockFactory;

class AvatarUploadService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/avatars')]
        private string $uploadDir,
        private EntityManagerInterface $em,
        // Optional, damit Prüfläufe den Dienst ohne Sperre bauen können (wie bisher).
        private ?LockFactory $locks = null,
    ) {
    }

    public function upload(UploadedFile $file, User $user): string
    {
        // ⚠ BF-173: Gleichzeitige Uploads desselben Kontos hinterließen Dateien, auf die kein Konto
        // mehr verweist. Jede Anfrage las den alten Namen aus ihrer beim Anfragebeginn geladenen
        // Entity — hatte eine zweite inzwischen gespeichert, löschte die erste einen längst ersetzten
        // Namen und überschrieb den neuen. Deshalb nacheinander (Sperre je Konto) und der alte Name
        // frisch aus der Datenbank, nicht aus der Entity. Kein `refresh()`: Das Profilformular hat
        // Name und Adresse dann schon in die Entity geschrieben, ein Neuladen verwürfe sie.
        $sperre = null !== $this->locks && null !== $user->getId()
            ? $this->locks->createLock('avatar-upload-'.$user->getId())
            : null;
        $sperre?->acquire(true);

        try {
            return $this->ersetzen($file, $user);
        } finally {
            $sperre?->release();
        }
    }

    private function ersetzen(UploadedFile $file, User $user): string
    {
        // Beide alten Namen — gleichzeitig ist nur der aus der Datenbank aktuell, und genau dessen
        // Datei bliebe sonst ohne Konto liegen.
        foreach ($this->alteNamen($user) as $oldFilename) {
            $oldPath = $this->uploadDir . '/' . $oldFilename;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $filename = uniqid('', true) . '.' . $file->guessExtension();
        $file->move($this->uploadDir, $filename);

        $user->setAvatarFilename($filename);
        $this->em->flush();

        return $filename;
    }

    public function delete(User $user): void
    {
        // ⚠ BF-180: dieselbe Sperre wie beim Hochladen, und der Name auch frisch aus der Datenbank.
        // Lief ein Upload daneben, löschte „Entfernen" den beim Anfragebeginn geladenen alten Namen
        // und setzte das Konto auf NULL — die eben hochgeladene Datei blieb ohne Konto liegen.
        $sperre = null !== $this->locks && null !== $user->getId()
            ? $this->locks->createLock('avatar-upload-'.$user->getId())
            : null;
        $sperre?->acquire(true);

        try {
            $namen = $this->alteNamen($user);
            if ([] === $namen) {
                return;
            }

            foreach ($namen as $filename) {
                $path = $this->uploadDir . '/' . $filename;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            $user->setAvatarFilename(null);
            $this->em->flush();
        } finally {
            $sperre?->release();
        }
    }

    /**
     * Der Name aus der Entity (was diese Anfrage für das aktuelle Bild hält) und der aus der Datenbank
     * (was zuletzt gespeichert wurde). Nacheinander sind sie gleich; gleichzeitig ist nur der zweite
     * aktuell (BF-173, BF-180).
     *
     * @return list<string>
     */
    private function alteNamen(User $user): array
    {
        $namen = [$user->getAvatarFilename()];
        if (null !== $user->getId()) {
            $namen[] = $this->em->getConnection()->fetchOne('SELECT avatar_filename FROM `user` WHERE id = ?', [$user->getId()]);
        }

        return array_values(array_unique(array_filter($namen, static fn ($name): bool => \is_string($name) && '' !== $name)));
    }
}
