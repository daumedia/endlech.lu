<?php

namespace App\Entity;

use App\Enum\CollaborationInterest;
use App\Enum\OrganisationTimeframe;
use App\Enum\OrganisationType;
use App\Enum\SponsorshipInterest;
use App\Repository\OrganisationWaitlistEntryRepository;
use App\Waitlist\WaitlistEntryInterface;
use App\Enum\WaitlistStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Wartelisten-Anmeldung einer Organisation (Gemeinde, Unternehmen, Verein).
 *
 * Die typspezifischen Felder sind alle nullable und werden über
 * Validierungsgruppen abgesichert: Der Formulartyp leitet die Gruppe aus $type
 * ab, und die jeweils fremden Felder tragen in den anderen Gruppen ein
 * IsNull- bzw. Count(max: 0)-Constraint. Ein manipulierter Request, der einer
 * Gemeinde Sponsoring-Interessen unterschiebt, wird dadurch abgelehnt statt
 * still gespeichert.
 */
#[ORM\Entity(repositoryClass: OrganisationWaitlistEntryRepository::class)]
#[ORM\Index(name: 'IDX_org_waitlist_type_status', columns: ['type', 'status'])]
#[ORM\Index(name: 'IDX_org_waitlist_status_created', columns: ['status', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class OrganisationWaitlistEntry implements WaitlistEntryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: OrganisationType::class)]
    #[Assert\NotNull(message: 'organisation_waitlist.type_required')]
    private ?OrganisationType $type = null;

    #[ORM\Column(length: 180)]
    private string $organisationName = '';

    #[ORM\Column(length: 120)]
    private string $contactName = '';

    /** Funktion im Haus, z. B. „Échevin" oder „CSR Manager". */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $contactRole = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 20, enumType: WaitlistStatus::class)]
    private WaitlistStatus $status = WaitlistStatus::PENDING;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    /** Zeitpunkt der Einwilligung (DSGVO-Nachweis). */
    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    #[ORM\Column(length: 5)]
    private string $locale = 'de';

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $source = null;

    // --- Nur für Gemeinden -------------------------------------------------

    /** Kann vom Organisationsnamen abweichen (z. B. Gemeindesyndikat). */
    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\IsNull(message: 'organisation_waitlist.commune_only', groups: ['company', 'association'])]
    private ?string $communeName = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 5000, notInRangeMessage: 'organisation_waitlist.venues_range', groups: ['commune'])]
    #[Assert\IsNull(message: 'organisation_waitlist.commune_only', groups: ['company', 'association'])]
    private ?int $estimatedVenues = null;

    #[ORM\Column(length: 20, nullable: true, enumType: OrganisationTimeframe::class)]
    #[Assert\IsNull(message: 'organisation_waitlist.commune_only', groups: ['company', 'association'])]
    private ?OrganisationTimeframe $timeframe = null;

    // --- Nur für Unternehmen ------------------------------------------------

    /** @var string[] Werte aus App\Enum\SponsorshipInterest */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(max: 0, maxMessage: 'organisation_waitlist.company_only', groups: ['commune', 'association'])]
    #[Assert\All([new Assert\Choice(callback: [SponsorshipInterest::class, 'values'], message: 'organisation_waitlist.invalid_choice')], groups: ['company'])]
    private array $sponsorshipInterests = [];

    // --- Nur für Organisationen ---------------------------------------------

    /** @var string[] Werte aus App\Enum\CollaborationInterest */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(max: 0, maxMessage: 'organisation_waitlist.association_only', groups: ['commune', 'company'])]
    #[Assert\All([new Assert\Choice(callback: [CollaborationInterest::class, 'values'], message: 'organisation_waitlist.invalid_choice')], groups: ['association'])]
    private array $collaborationInterests = [];

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

    // --- Interface-Vertrag ---------------------------------------------------

    public function getDisplayName(): string
    {
        return $this->organisationName;
    }

    public function generateConfirmationToken(): string
    {
        $this->confirmationToken = bin2hex(random_bytes(32));

        return $this->confirmationToken;
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

    // --- Getter / Setter -----------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?OrganisationType
    {
        return $this->type;
    }

    public function setType(?OrganisationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getOrganisationName(): string
    {
        return $this->organisationName;
    }

    public function setOrganisationName(string $organisationName): static
    {
        $this->organisationName = $organisationName;

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

    public function getContactRole(): ?string
    {
        return $this->contactRole;
    }

    public function setContactRole(?string $contactRole): static
    {
        $this->contactRole = $contactRole;

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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

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

    public function getCommuneName(): ?string
    {
        return $this->communeName;
    }

    public function setCommuneName(?string $communeName): static
    {
        $this->communeName = $communeName;

        return $this;
    }

    public function getEstimatedVenues(): ?int
    {
        return $this->estimatedVenues;
    }

    public function setEstimatedVenues(?int $estimatedVenues): static
    {
        $this->estimatedVenues = $estimatedVenues;

        return $this;
    }

    public function getTimeframe(): ?OrganisationTimeframe
    {
        return $this->timeframe;
    }

    public function setTimeframe(?OrganisationTimeframe $timeframe): static
    {
        $this->timeframe = $timeframe;

        return $this;
    }

    /** @return string[] */
    public function getSponsorshipInterests(): array
    {
        return $this->sponsorshipInterests;
    }

    /** @param string[] $sponsorshipInterests */
    public function setSponsorshipInterests(array $sponsorshipInterests): static
    {
        $this->sponsorshipInterests = array_values($sponsorshipInterests);

        return $this;
    }

    /** @return string[] */
    public function getCollaborationInterests(): array
    {
        return $this->collaborationInterests;
    }

    /** @param string[] $collaborationInterests */
    public function setCollaborationInterests(array $collaborationInterests): static
    {
        $this->collaborationInterests = array_values($collaborationInterests);

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
