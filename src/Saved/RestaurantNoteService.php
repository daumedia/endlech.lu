<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\User;
use App\Repository\RestaurantNoteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Die private Notiz zu einem Lokal (Feature 17): speichern, überschreiben, leeren.
 *
 * - `\r\n` → `\n` **vor** dem Zählen (`SavedText::note`): Ein Formular schickt Umbrüche als
 *   zwei Zeichen, der Nutzer zählt einen (AK-35).
 * - **Leer heißt keine Zeile** (AK-36, EC-08) — auch nur Leerraum.
 * - Zu lang → abgewiesen, die gespeicherte Notiz bleibt, wie sie war (AK-35).
 * - Zwei Geräte: der zuletzt gespeicherte Text gilt, ohne Warnung (EC-06).
 *
 * ⚠ Der Text verlässt diesen Dienst nie Richtung Protokoll — keine Log-Zeile mit Inhalt.
 */
final readonly class RestaurantNoteService
{
    public const string SAVED = 'saved';
    public const string DELETED = 'deleted';
    public const string UNCHANGED = 'unchanged';
    public const string TOO_LONG = 'saved.note_too_long';

    public function __construct(
        private EntityManagerInterface $em,
        private RestaurantNoteRepository $notes,
        private SavedWriteLock $lock,
    ) {
    }

    /**
     * @return self::SAVED|self::DELETED|self::UNCHANGED|self::TOO_LONG
     */
    public function save(User $user, Restaurant $restaurant, string $text): string
    {
        $bereinigt = SavedText::note($text);
        if (SavedText::length($bereinigt) > RestaurantNote::MAX_LENGTH) {
            return self::TOO_LONG;
        }

        return $this->lock->forAccount($user, function () use ($user, $restaurant, $bereinigt): string {
            $vorhanden = $this->notes->findOneByUserAndRestaurant($user, $restaurant);

            if ('' === $bereinigt) {
                if (null === $vorhanden) {
                    return self::UNCHANGED;
                }

                $this->em->remove($vorhanden);
                $this->em->flush();

                return self::DELETED;
            }

            if (null === $vorhanden) {
                $this->em->persist(new RestaurantNote($user, $restaurant, $bereinigt));
            } elseif ($vorhanden->getText() === $bereinigt) {
                return self::UNCHANGED;
            } else {
                $vorhanden->setText($bereinigt);
            }

            $this->em->flush();

            return self::SAVED;
        });
    }

    public function textFor(User $user, Restaurant $restaurant): ?string
    {
        return $this->notes->findOneByUserAndRestaurant($user, $restaurant)?->getText();
    }
}
