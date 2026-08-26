<?php

namespace App\Entity;

use App\Enum\Language;
use App\Repository\RestaurantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant
{
    /**
     * Schwellwerte nach DIN 18040-1 – 90 cm lichte Breite für Türen und
     * Durchgänge. Als Konstanten, damit Entity, Repository-Filter und die
     * Open-Startup-Auswertung nicht drei eigene Zahlen pflegen.
     */
    public const int MIN_DOOR_WIDTH_CM = 90;
    public const int MIN_TABLE_SPACING_CM = 90;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $city = '';

    /** @var Collection<int, Cuisine> */
    #[ORM\ManyToMany(targetEntity: Cuisine::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'restaurant_cuisine')]
    private Collection $cuisines;

    #[ORM\Column(length: 10)]
    private string $emoji = '🍽️';

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column]
    private bool $isWheelchairAccessible = false;

    #[ORM\Column]
    private bool $hasAccessibleToilet = false;

    #[ORM\Column]
    private bool $allowsAssistanceDogs = false;

    #[ORM\Column]
    private bool $hasBrightLighting = false;

    #[ORM\Column]
    private bool $hasChangingTable = false;

    #[ORM\Column]
    private bool $hasDisabledParking = false;

    /**
     * Lichte Durchgangsbreite der schmalsten Tür auf dem Weg von der Straße
     * zum Tisch, in Zentimetern.
     *
     * Nullable und ohne Default: null heißt "nicht ausgemessen", nicht
     * "zu schmal". Auf der Open-Startup-Seite zählen nur dokumentierte Maße –
     * ein 0-Default würde jedes nie erfasste Haus als Negativbefund ausweisen.
     */
    #[ORM\Column(nullable: true)]
    private ?int $doorWidthCm = null;

    /**
     * Schmalste Durchgangsbreite zwischen den Tischen, in Zentimetern.
     * Gleiche Semantik für null wie bei $doorWidthCm.
     */
    #[ORM\Column(nullable: true)]
    private ?int $tableSpacingCm = null;

    #[ORM\Column]
    private bool $acceptsCash = false;

    #[ORM\Column]
    private bool $acceptsCard = false;

    #[ORM\Column]
    private bool $acceptsPayconiq = false;

    #[ORM\Column]
    private bool $isVegan = false;

    #[ORM\Column]
    private bool $isVegetarian = false;

    #[ORM\Column]
    private bool $isHalal = false;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $verifiedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $submittedBy = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
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

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nearbyStopsNote = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $accessibilityNotes = [];

    /**
     * Merkmale, zu denen tatsächlich eine Auskunft vorliegt.
     *
     * ⚠ BF-49 + BF-67: `Restaurant` speichert die Barrierefreiheits-Merkmale als
     * `bool` — dort ist `false` zweierlei zugleich: „gibt es nicht" und „wissen wir
     * nicht". Der Vorschlags-Assistent unterscheidet das seit Langem (`TriState`),
     * bei der Genehmigung ging die Unterscheidung verloren.
     *
     * Die Folge war auf `/open` messbar: Ein Haus, über das nichts bekannt war,
     * hob die ausgewiesene Gemeindeabdeckung (8 → 9) und senkte zugleich die
     * Durchschnittspunktzahl (5,09 → 4,67). Zwei Leitzahlen auf derselben Seite,
     * die in gegenläufige Richtungen zeigten — wer die Kurven nebeneinander sah,
     * las „wächst und wird schlechter". Tatsächlich hieß es: noch nicht gemessen.
     *
     * Diese Liste hält fest, wonach jemand gesehen hat. Ein leeres Feld heißt
     * „nicht bewertet" und ergibt **keine** Punktzahl statt einer Null.
     *
     * Bewusst eine Liste und kein einzelnes `isAssessed`-Flag: Wer fünf Merkmale
     * kennt und eines nicht, soll für das eine nicht bestraft werden.
     *
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $assessedFeatures = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: RestaurantImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $images;

    /** @var Collection<int, OrderingOption> */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: OrderingOption::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderingOptions;

    /** @var Collection<int, OpeningHour> */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: OpeningHour::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['dayOfWeek' => 'ASC', 'openTime' => 'ASC'])]
    private Collection $openingHours;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->cuisines = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->orderingOptions = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /** @return Collection<int, Cuisine> */
    public function getCuisines(): Collection
    {
        return $this->cuisines;
    }

    public function addCuisine(Cuisine $cuisine): static
    {
        if (!$this->cuisines->contains($cuisine)) {
            $this->cuisines->add($cuisine);
        }

        return $this;
    }

    public function removeCuisine(Cuisine $cuisine): static
    {
        $this->cuisines->removeElement($cuisine);

        return $this;
    }

    public function getCuisineNames(): string
    {
        return implode(', ', $this->cuisines->map(fn (Cuisine $c) => $c->getName())->toArray());
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

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function isWheelchairAccessible(): bool
    {
        return $this->isWheelchairAccessible;
    }

    public function setIsWheelchairAccessible(bool $isWheelchairAccessible): static
    {
        $this->isWheelchairAccessible = $isWheelchairAccessible;

        return $this;
    }

    public function hasAccessibleToilet(): bool
    {
        return $this->hasAccessibleToilet;
    }

    public function setHasAccessibleToilet(bool $hasAccessibleToilet): static
    {
        $this->hasAccessibleToilet = $hasAccessibleToilet;

        return $this;
    }

    public function allowsAssistanceDogs(): bool
    {
        return $this->allowsAssistanceDogs;
    }

    public function setAllowsAssistanceDogs(bool $allowsAssistanceDogs): static
    {
        $this->allowsAssistanceDogs = $allowsAssistanceDogs;

        return $this;
    }

    public function hasBrightLighting(): bool
    {
        return $this->hasBrightLighting;
    }

    public function setHasBrightLighting(bool $hasBrightLighting): static
    {
        $this->hasBrightLighting = $hasBrightLighting;

        return $this;
    }

    public function hasChangingTable(): bool
    {
        return $this->hasChangingTable;
    }

    public function setHasChangingTable(bool $hasChangingTable): static
    {
        $this->hasChangingTable = $hasChangingTable;

        return $this;
    }

    public function hasDisabledParking(): bool
    {
        return $this->hasDisabledParking;
    }

    public function setHasDisabledParking(bool $hasDisabledParking): static
    {
        $this->hasDisabledParking = $hasDisabledParking;

        return $this;
    }

    public function getDoorWidthCm(): ?int
    {
        return $this->doorWidthCm;
    }

    public function setDoorWidthCm(?int $doorWidthCm): static
    {
        $this->doorWidthCm = $doorWidthCm;

        return $this;
    }

    public function getTableSpacingCm(): ?int
    {
        return $this->tableSpacingCm;
    }

    public function setTableSpacingCm(?int $tableSpacingCm): static
    {
        $this->tableSpacingCm = $tableSpacingCm;

        return $this;
    }

    /**
     * Tür breit genug für einen Rollstuhl (DIN 18040: 90 cm lichte Breite).
     * Gibt null zurück, solange kein Maß erfasst ist – Twig und die API
     * unterscheiden damit "zu schmal" von "unbekannt".
     */
    public function hasWideDoors(): ?bool
    {
        return null === $this->doorWidthCm
            ? null
            : $this->doorWidthCm >= self::MIN_DOOR_WIDTH_CM;
    }

    public function hasWheelchairTableSpacing(): ?bool
    {
        return null === $this->tableSpacingCm
            ? null
            : $this->tableSpacingCm >= self::MIN_TABLE_SPACING_CM;
    }

    public function acceptsCash(): bool
    {
        return $this->acceptsCash;
    }

    public function setAcceptsCash(bool $acceptsCash): static
    {
        $this->acceptsCash = $acceptsCash;

        return $this;
    }

    public function acceptsCard(): bool
    {
        return $this->acceptsCard;
    }

    public function setAcceptsCard(bool $acceptsCard): static
    {
        $this->acceptsCard = $acceptsCard;

        return $this;
    }

    public function acceptsPayconiq(): bool
    {
        return $this->acceptsPayconiq;
    }

    public function setAcceptsPayconiq(bool $acceptsPayconiq): static
    {
        $this->acceptsPayconiq = $acceptsPayconiq;

        return $this;
    }

    public function isVegan(): bool
    {
        return $this->isVegan;
    }

    public function setIsVegan(bool $isVegan): static
    {
        $this->isVegan = $isVegan;

        return $this;
    }

    public function isVegetarian(): bool
    {
        return $this->isVegetarian;
    }

    public function setIsVegetarian(bool $isVegetarian): static
    {
        $this->isVegetarian = $isVegetarian;

        return $this;
    }

    public function isHalal(): bool
    {
        return $this->isHalal;
    }

    public function setIsHalal(bool $isHalal): static
    {
        $this->isHalal = $isHalal;

        return $this;
    }

    /** @return Language[] */
    public function getSpokenLanguages(): array
    {
        return array_filter(
            array_map(
                static fn (string $value) => Language::tryFrom($value),
                $this->spokenLanguages,
            ),
        );
    }

    /** @param Language[]|string[] $languages */
    public function setSpokenLanguages(array $languages): static
    {
        $this->spokenLanguages = array_map(
            static fn (Language|string $l) => $l instanceof Language ? $l->value : $l,
            $languages,
        );

        return $this;
    }

    /** @return list<string> */
    public function getAccessibilityNotes(): array
    {
        return $this->accessibilityNotes;
    }

    /**
     * Alle bewertbaren Merkmale — zugleich die zulässigen Werte von
     * `assessedFeatures` und der Nenner der Punktzahl.
     *
     * @return list<string>
     */
    public static function assessableFeatures(): array
    {
        return [
            'wheelchair', 'toilet', 'dogs', 'lighting',
            'changing_table', 'disabled_parking', 'door_width', 'table_spacing',
        ];
    }

    /** @return list<string> */
    public function getAssessedFeatures(): array
    {
        return $this->assessedFeatures;
    }

    /** @param list<string> $features */
    public function setAssessedFeatures(array $features): static
    {
        $this->assessedFeatures = array_values(array_intersect(self::assessableFeatures(), $features));

        return $this;
    }

    /**
     * Wurde zu diesem Haus überhaupt etwas erhoben?
     *
     * Ist die Antwort nein, bekommt es keine Punktzahl — und fällt aus dem
     * Durchschnitt heraus, statt ihn zu senken.
     */
    public function isAssessed(): bool
    {
        return [] !== $this->assessedFeatures;
    }

    /** @param list<string> $accessibilityNotes */
    public function setAccessibilityNotes(array $accessibilityNotes): static
    {
        $this->accessibilityNotes = $accessibilityNotes;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    public function getVerifiedBy(): ?User
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?User $verifiedBy): static
    {
        $this->verifiedBy = $verifiedBy;

        return $this;
    }

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?User $submittedBy): static
    {
        $this->submittedBy = $submittedBy;

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

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function hasContactInfo(): bool
    {
        return $this->phone || $this->email || $this->website || $this->instagramUrl || $this->facebookUrl || $this->tiktokUrl;
    }

    /** @return Collection<int, RestaurantImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getCoverImage(): ?RestaurantImage
    {
        return $this->images->isEmpty() ? null : $this->images->first();
    }

    /** @return Collection<int, RestaurantImage> */
    public function getGalleryImages(): Collection
    {
        return $this->images->filter(fn (RestaurantImage $image) => $image !== $this->images->first());
    }

    /** @return Collection<int, OrderingOption> */
    public function getOrderingOptions(): Collection
    {
        return $this->orderingOptions;
    }

    public function addOrderingOption(OrderingOption $option): static
    {
        if (!$this->orderingOptions->contains($option)) {
            $this->orderingOptions->add($option);
            $option->setRestaurant($this);
        }

        return $this;
    }

    public function removeOrderingOption(OrderingOption $option): static
    {
        if ($this->orderingOptions->removeElement($option)) {
            if ($option->getRestaurant() === $this) {
                $option->setRestaurant(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, OpeningHour> */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(OpeningHour $openingHour): static
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->setRestaurant($this);
        }

        return $this;
    }

    public function removeOpeningHour(OpeningHour $openingHour): static
    {
        if ($this->openingHours->removeElement($openingHour)) {
            if ($openingHour->getRestaurant() === $this) {
                $openingHour->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return OpeningHour[]
     */
    public function getOpeningHoursForDay(int $day): array
    {
        $slots = [];
        foreach ($this->openingHours as $oh) {
            if ($oh->getDayOfWeek() === $day) {
                $slots[] = $oh;
            }
        }

        return $slots;
    }
}
