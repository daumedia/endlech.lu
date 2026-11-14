<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SavedListRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Eine eigene, benannte Liste von Lokalen (Feature 17). Privat — nur der Inhaber sieht sie.
 *
 * ⚠ **`nameKey` ist der Vergleichsschlüssel, nicht `name`.** Die Standardkollation von
 * MySQL 8 (`utf8mb4_0900_ai_ci`) ist auch akzentblind: Ein eindeutiger Index auf `name` hielte
 * „Café“ und „Cafe“ für dieselbe Liste. Die Spec verlangt nur „Groß/klein egal“ (AK-19). Darum
 * steht hier der kleingeschriebene Name mit **binärer** Kollation, und Index und Prüfung im
 * `SavedListService` sind deckungsgleich.
 *
 * Den Namen setzt allein der `SavedListService` (getrimmt, NFC) — der Setter nimmt Name und
 * Schlüssel zusammen, damit beide nicht auseinanderlaufen.
 */
#[ORM\Entity(repositoryClass: SavedListRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_saved_list_user_name', columns: ['user_id', 'name_key'])]
#[ORM\Index(name: 'IDX_saved_list_user_created', columns: ['user_id', 'created_at'])]
class SavedList
{
    public const int MAX_NAME_LENGTH = 60;

    /** Höchstzahl eigener Listen je Konto (AK-20). */
    public const int MAX_PER_USER = 25;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    private string $name;

    #[ORM\Column(length: self::MAX_NAME_LENGTH, options: ['collation' => 'utf8mb4_bin'])]
    private string $nameKey;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $name, string $nameKey)
    {
        $this->user = $user;
        $this->rename($name, $nameKey);
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameKey(): string
    {
        return $this->nameKey;
    }

    public function rename(string $name, string $nameKey): void
    {
        $this->name = $name;
        $this->nameKey = $nameKey;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
