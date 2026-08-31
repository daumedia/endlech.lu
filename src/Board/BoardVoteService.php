<?php

declare(strict_types=1);

namespace App\Board;

use App\Entity\BoardIdea;
use App\Entity\BoardVote;
use App\Entity\User;
use App\Repository\BoardVoteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Setzt und entfernt die Zustimmung eines Kontos zu einer Idee (AK-19 bis AK-21).
 *
 * ⚠ **Die Prüfung hier ersetzt den Unique-Index nicht, sie ergänzt ihn.** Der
 * Index in der Datenbank ist die letzte Instanz; ohne die Prüfung davor bekäme
 * ein zweiter Klick einen Datenbankfehler statt einer ruhigen Antwort.
 *
 * ⚠ **Es wird kein Zähler fortgeschrieben.** Die Zahl entsteht in der Abfrage
 * (`BoardIdeaRepository`). Ein Zählerfeld liefe auseinander, sobald die
 * Fremdschlüssel-Kaskade beim Kontolöschen Stimmen entfernt — das geschieht in
 * der Datenbank, an dieser Klasse vorbei (AK-66).
 */
final readonly class BoardVoteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BoardVoteRepository $votes,
    ) {
    }

    /**
     * Schaltet um. Rückgabe: `true` = zugestimmt, `false` = Zustimmung entfernt.
     */
    public function toggle(BoardIdea $idea, User $user): bool
    {
        $vorhanden = $this->votes->findOneByIdeaAndUser($idea, $user);

        if (null !== $vorhanden) {
            $this->em->remove($vorhanden);
            $this->em->flush();

            return false;
        }

        $stimme = (new BoardVote())
            ->setIdea($idea)
            ->setUser($user);

        $this->em->persist($stimme);
        $this->em->flush();

        return true;
    }

    public function hasVoted(BoardIdea $idea, User $user): bool
    {
        return null !== $this->votes->findOneByIdeaAndUser($idea, $user);
    }
}
