<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RestaurantNoteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Die private Notiz eines Kontos zu einem Lokal (Feature 17) — höchstens eine je Paar.
 *
 * ⚠ **Freitext, in dem eine Gesundheitsangabe stehen kann** („Rampe für Papas Rollstuhl zu
 * steil“). Er verlässt das System nie: kein Protokoll, keine Messung, kein Fehlerbericht
 * (Sentry sendet seit Feature 17 keinen Anfragerumpf mehr, AK-64/AK-65), keine
 * Verwaltungsansicht (AK-59). Siehe `docs/datenschutz.md`.
 *
 * **Eine leere Notiz ist keine Zeile** — `RestaurantNoteService` löscht sie (AK-36).
 */
#[ORM\Entity(repositoryClass: RestaurantNoteRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_restaurant_note_user_restaurant', columns: ['user_id', 'restaurant_id'])]
class RestaurantNote
{
    public const int MAX_LENGTH = 500;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Restaurant $restaurant;

    #[ORM\Column(length: self::MAX_LENGTH)]
    private string $text;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Restaurant $restaurant, string $text)
    {
        $this->user = $user;
        $this->restaurant = $restaurant;
        $this->setText($text);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRestaurant(): Restaurant
    {
        return $this->restaurant;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
