<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ein gemerktes Lokal — das Herz (Feature 17).
 *
 * ⚠ **Beide Fremdschlüssel kaskadieren in der Datenbank** (Muster `BoardVote`). Ein Favorit
 * ist die Handlung einer Person an einem Lokal und ohne eines von beiden bedeutungslos. Die
 * Beziehungen sind einseitig: `User` und `Restaurant` kennen ihre Favoriten nicht, damit
 * Kontolöschung (Feature 01) und Restaurantverwaltung (B20) nichts dafür tun müssen —
 * `DELETE` auf die Elternzeile räumt hier mit ab (AK-61, AK-62).
 *
 * Der eindeutige Index ist die letzte Instanz gegen einen doppelten Eintrag (AK-05), nicht die
 * einzige: `FavoriteService` prüft vorher unter der Schreibsperre des Kontos.
 */
#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_favorite_user_restaurant', columns: ['user_id', 'restaurant_id'])]
#[ORM\Index(name: 'IDX_favorite_user_created', columns: ['user_id', 'created_at'])]
class Favorite
{
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

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Restaurant $restaurant)
    {
        $this->user = $user;
        $this->restaurant = $restaurant;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
