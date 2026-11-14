<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SavedListEntryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ein Lokal in einer eigenen Liste (Feature 17).
 *
 * Kein eigener Kontobezug: Der Inhaber ergibt sich über die Liste, die Kaskade läuft
 * Konto → Liste → Eintrag. Verschwindet das Lokal, verschwindet der Eintrag in der Datenbank
 * mit (AK-62); verschwindet die Liste, ebenso (AK-30) — Herz und Notiz bleiben davon unberührt.
 */
#[ORM\Entity(repositoryClass: SavedListEntryRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_saved_list_entry_list_restaurant', columns: ['list_id', 'restaurant_id'])]
#[ORM\Index(name: 'IDX_saved_list_entry_list_added', columns: ['list_id', 'added_at'])]
class SavedListEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'list_id', nullable: false, onDelete: 'CASCADE')]
    private SavedList $list;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Restaurant $restaurant;

    #[ORM\Column]
    private \DateTimeImmutable $addedAt;

    public function __construct(SavedList $list, Restaurant $restaurant)
    {
        $this->list = $list;
        $this->restaurant = $restaurant;
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getList(): SavedList
    {
        return $this->list;
    }

    public function getRestaurant(): Restaurant
    {
        return $this->restaurant;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
