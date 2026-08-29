<?php

namespace App\Entity;

use App\Enum\WaitlistStatus;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Waitlist\WaitlistEntryInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnerWaitlistEntryRepository::class)]
#[ORM\Index(name: 'IDX_partner_waitlist_status_created', columns: ['status', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class PartnerWaitlistEntry implements WaitlistEntryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $restaurantName = '';

    #[ORM\Column(length: 120)]
    private string $contactName = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    /** Gemeinde bzw. Ortschaft des Restaurants. */
    #[ORM\Column(length: 120)]
    private string $locality = '';

    /**
     * Wird erst gesetzt, wenn das Haus bereits in der Datenbank steht – die
     * Anmeldung selbst verlangt keinen bestehenden Eintrag.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Restaurant $restaurant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 20, enumType: WaitlistStatus::class)]
    private WaitlistStatus $status = WaitlistStatus::PENDING;

    /**
     * Bleibt nach der Bestätigung absichtlich stehen: nur so lässt sich ein
     * zweiter Klick auf denselben Link von einem unbekannten Token unterscheiden.
     */
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    /** Zeitpunkt der Einwilligung (DSGVO-Nachweis) – wird beim Absenden gesetzt. */
    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    /** Sprache, in der das Formular abgeschickt wurde. */
    #[ORM\Column(length: 5)]
    private string $locale = 'de';

    /** UTM-Quelle oder Referrer, für spätere Attribution. */
    #[ORM\Column(length: 60, nullable: true)]
    private ?string $source = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->consentAt = $now;
        $this->createdAt = $now;
        // PreUpdate feuert beim ersten persist() nicht – daher hier initialisieren.
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Erzeugt den Double-Opt-In-Token. bin2hex(random_bytes(32)) ergibt genau
     * 64 Zeichen – identisch zu User::generateVerificationToken().
     */
    public function generateConfirmationToken(): string
    {
        $this->confirmationToken = bin2hex(random_bytes(32));

        return $this->confirmationToken;
    }

    /**
     * Anzeigename für Listen und Betreffzeilen (Interface-Vertrag).
     */
    public function getDisplayName(): string
    {
        return $this->restaurantName;
    }

    public function isConfirmed(): bool
    {
        return null !== $this->confirmedAt;
    }

    public function confirm(): static
    {
        $this->status = WaitlistStatus::CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRestaurantName(): string
    {
        return $this->restaurantName;
    }

    public function setRestaurantName(string $restaurantName): static
    {
        $this->restaurantName = $restaurantName;

        return $this;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): static
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getLocality(): string
    {
        return $this->locality;
    }

    public function setLocality(string $locality): static
    {
        $this->locality = $locality;

        return $this;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): WaitlistStatus
    {
        return $this->status;
    }

    public function setStatus(WaitlistStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getConsentAt(): \DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function setConsentAt(\DateTimeImmutable $consentAt): static
    {
        $this->consentAt = $consentAt;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
