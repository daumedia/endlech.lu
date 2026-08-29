<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\RestaurantImage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Löscht die Bilddatei, wenn ihre Datenbankzeile verschwindet.
 *
 * ⚠ BF-53: Die Zeilen verschwanden über die ORM-Kaskade
 * (`cascade: ['remove']`, `orphanRemoval`), und dabei lief
 * `ImageUploadService::delete()` nie — der Dienst kennt den Weg, aber niemand
 * ging ihn. Nicht theoretisch: Bei der Prüfung von B09 lagen fünf verwaiste
 * Dateien aus Februar und Juni im Verzeichnis, weiterhin öffentlich abrufbar
 * unter ihrer alten Adresse.
 *
 * Als Entity-Listener statt im Dienst, weil nur so JEDER Löschweg erfasst ist:
 * das Löschen eines einzelnen Bildes, das Löschen eines Restaurants und ein
 * `orphanRemoval` beim Bearbeiten.
 *
 * `postRemove` statt `preRemove`: Erst wenn die Transaktion die Zeile wirklich
 * los ist, darf die Datei weg. Ein Rollback nach einem `unlink` ließe einen
 * Datenbankeintrag ohne Datei zurück — schlimmer als eine Datei ohne Eintrag.
 */
#[AsEntityListener(event: Events::postRemove, entity: RestaurantImage::class)]
final readonly class RestaurantImageFileListener
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/restaurants')]
        private string $uploadDir,
        private LoggerInterface $logger,
    ) {
    }

    public function postRemove(RestaurantImage $image): void
    {
        $dateiname = $image->getFilename();

        // Kein Pfadanteil: Der Name kommt zwar aus `uniqid()`, aber ein Listener,
        // der blind zusammensetzt, ist eine Einladung für den nächsten Aufrufer.
        if ('' === $dateiname || basename($dateiname) !== $dateiname) {
            return;
        }

        $pfad = $this->uploadDir.'/'.$dateiname;
        if (!is_file($pfad)) {
            return;
        }

        if (!@unlink($pfad)) {
            // Nicht werfen: Die Zeile ist bereits weg, und ein Fehler hier machte
            // aus einem Aufräumproblem einen abgebrochenen Löschvorgang.
            $this->logger->warning('Bilddatei konnte nicht gelöscht werden.', ['file' => $dateiname]);
        }
    }
}
