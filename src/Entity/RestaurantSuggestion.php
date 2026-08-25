<?php

namespace App\Entity;

use App\Enum\TriState;
use App\Repository\RestaurantSuggestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantSuggestionRepository::class)]
class RestaurantSuggestion
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $suggestedBy = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $city = '';

    #[ORM\Column(length: 80)]
    private string $cuisine = '';

    #[ORM\Column(length: 10)]
    private string $emoji = '🍽️';

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $isWheelchairAccessible = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $hasAccessibleToilet = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $allowsAssistanceDogs = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $hasBrightLighting = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $hasChangingTable = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $hasDisabledParking = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $acceptsCash = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $acceptsCard = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $acceptsPayconiq = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $isVegan = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $isVegetarian = null;

    #[ORM\Column(length: 10, nullable: true, enumType: TriState::class)]
    private ?TriState $isHalal = null;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    private array $spokenLanguages = [];

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $instagramUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $facebookUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $tiktokUrl = null;

    /**
     * Standort, sofern der Einreicher ihn kennt.
     *
     * Der Web-Wizard fragt ihn nicht ab; die REST-API nimmt ihn entgegen und prüft
     * ihn (±90/±180). Ohne diese Felder ginge die Angabe zwischen Eingang und
     * Freigabe verloren.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nearbyStopsNote = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

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

    public function getSuggestedBy(): ?User
    {
        return $this->suggestedBy;
    }

    public function setSuggestedBy(?User $suggestedBy): static
    {
        $this->suggestedBy = $suggestedBy;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCuisine(): string
    {
        return $this->cuisine;
    }

    public function setCuisine(string $cuisine): static
    {
        $this->cuisine = $cuisine;

        return $this;
    }

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): static
    {
        $this->emoji = $emoji;

        return $this;
    }

    public function isWheelchairAccessible(): ?TriState
    {
        return $this->isWheelchairAccessible;
    }

    public function setIsWheelchairAccessible(?TriState $isWheelchairAccessible): static
    {
        $this->isWheelchairAccessible = $isWheelchairAccessible;

        return $this;
    }

    public function hasAccessibleToilet(): ?TriState
    {
        return $this->hasAccessibleToilet;
    }

    public function setHasAccessibleToilet(?TriState $hasAccessibleToilet): static
    {
        $this->hasAccessibleToilet = $hasAccessibleToilet;

        return $this;
    }

    public function allowsAssistanceDogs(): ?TriState
    {
        return $this->allowsAssistanceDogs;
    }

    public function setAllowsAssistanceDogs(?TriState $allowsAssistanceDogs): static
    {
        $this->allowsAssistanceDogs = $allowsAssistanceDogs;

        return $this;
    }

    public function hasBrightLighting(): ?TriState
    {
        return $this->hasBrightLighting;
    }

    public function setHasBrightLighting(?TriState $hasBrightLighting): static
    {
        $this->hasBrightLighting = $hasBrightLighting;

        return $this;
    }

    public function hasChangingTable(): ?TriState
    {
        return $this->hasChangingTable;
    }

    public function setHasChangingTable(?TriState $hasChangingTable): static
    {
        $this->hasChangingTable = $hasChangingTable;

        return $this;
    }

    public function hasDisabledParking(): ?TriState
    {
        return $this->hasDisabledParking;
    }

    public function setHasDisabledParking(?TriState $hasDisabledParking): static
    {
        $this->hasDisabledParking = $hasDisabledParking;

        return $this;
    }

    public function acceptsCash(): ?TriState
    {
        return $this->acceptsCash;
    }

    public function setAcceptsCash(?TriState $acceptsCash): static
    {
        $this->acceptsCash = $acceptsCash;

        return $this;
    }

    public function acceptsCard(): ?TriState
    {
        return $this->acceptsCard;
    }

    public function setAcceptsCard(?TriState $acceptsCard): static
    {
        $this->acceptsCard = $acceptsCard;

        return $this;
    }

    public function acceptsPayconiq(): ?TriState
    {
        return $this->acceptsPayconiq;
    }

    public function setAcceptsPayconiq(?TriState $acceptsPayconiq): static
    {
        $this->acceptsPayconiq = $acceptsPayconiq;

        return $this;
    }

    public function isVegan(): ?TriState
    {
        return $this->isVegan;
    }

    public function setIsVegan(?TriState $isVegan): static
    {
        $this->isVegan = $isVegan;

        return $this;
    }

    public function isVegetarian(): ?TriState
    {
        return $this->isVegetarian;
    }

    public function setIsVegetarian(?TriState $isVegetarian): static
    {
        $this->isVegetarian = $isVegetarian;

        return $this;
    }

    public function isHalal(): ?TriState
    {
        return $this->isHalal;
    }

    public function setIsHalal(?TriState $isHalal): static
    {
        $this->isHalal = $isHalal;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSpokenLanguages(): array
    {
        return $this->spokenLanguages;
    }

    /**
     * @param string[] $spokenLanguages
     */
    public function setSpokenLanguages(array $spokenLanguages): static
    {
        $this->spokenLanguages = $spokenLanguages;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getInstagramUrl(): ?string
    {
        return $this->instagramUrl;
    }

    public function setInstagramUrl(?string $instagramUrl): static
    {
        $this->instagramUrl = $instagramUrl;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): static
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getTiktokUrl(): ?string
    {
        return $this->tiktokUrl;
    }

    public function setTiktokUrl(?string $tiktokUrl): static
    {
        $this->tiktokUrl = $tiktokUrl;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getNearbyStopsNote(): ?string
    {
        return $this->nearbyStopsNote;
    }

    public function setNearbyStopsNote(?string $nearbyStopsNote): static
    {
        $this->nearbyStopsNote = $nearbyStopsNote;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
