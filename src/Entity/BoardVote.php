<?php

namespace App\Entity;

use App\Repository\BoardVoteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Die Zustimmung eines Kontos zu einer Idee (Feature 06).
 *
 * ⚠ **Beide Fremdschlüssel kaskadieren — entgegen der Projektkonvention**, die
 * `SET NULL` vorsieht, „wo der Datensatz eigenständig weiterlebt". Genau das
 * tut eine Stimme nicht: Sie ist die Handlung einer Person und ohne sie
 * bedeutungslos. Das ist der Unterschied zwischen AK-65 (die Idee bleibt, ihr
 * Verfasserbezug wird `NULL`) und AK-66 (die Stimmen verschwinden, die Zahl
 * sinkt).
 *
 * Der Unique-Index über `(idea_id, user_id)` ist die letzte Instanz gegen eine
 * doppelte Zustimmung (AK-20) — nicht die einzige: `BoardVoteService` prüft
 * vorher, damit ein zweiter Klick eine ruhige Antwort bekommt und keinen
 * Datenbankfehler.
 */
#[ORM\Entity(repositoryClass: BoardVoteRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_board_vote_idea_user', columns: ['idea_id', 'user_id'])]
class BoardVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BoardIdea $idea = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdea(): ?BoardIdea
    {
        return $this->idea;
    }

    public function setIdea(?BoardIdea $idea): static
    {
        $this->idea = $idea;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
