<?php

namespace App\Entity;

use App\Repository\MetricSnapshotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Eingefrorene Monatswerte der Open-Startup-Seite.
 *
 * Der Grund für die Entity ist die Trendanzeige. Ein Verlauf, der aus den
 * heutigen Daten rückgerechnet wird, zeigt nicht, wie die Plattform damals
 * aussah: Restaurants werden umbenannt, Merkmale nachgetragen, Einträge
 * gelöscht. Der Verlauf würde sich rückwirkend ändern – und wäre damit als
 * Beleg gegenüber einem Ministerium wertlos.
 *
 * Die typisierten Spalten tragen die Werte, die in den Verlaufsgrafiken
 * vorkommen; `payload` bewahrt zusätzlich die vollständige Momentaufnahme
 * (Score-Verteilung, Abdeckung je Kanton) als JSON, damit eine spätere
 * Kennzahl nicht rückwirkend fehlt, nur weil es damals keine Spalte gab.
 */
#[ORM\Entity(repositoryClass: MetricSnapshotRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_metric_snapshot_month', columns: ['captured_for'])]
class MetricSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Erster Tag des Monats, für den die Werte gelten. Ein DATE statt eines
     * "2026-08"-Strings, damit Sortierung und Bereichsfilter in SQL
     * funktionieren; die Eindeutigkeit auf der Spalte macht den Schreibvorgang
     * idempotent.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $capturedFor;

    #[ORM\Column]
    private int $restaurantCount = 0;

    #[ORM\Column]
    private int $verifiedCount = 0;

    #[ORM\Column]
    private int $communesCovered = 0;

    #[ORM\Column]
    private int $cantonsCovered = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2)]
    private string $averageAccessibilityScore = '0.00';

    #[ORM\Column]
    private int $stepFreeEntrances = 0;

    #[ORM\Column]
    private int $accessibleRestrooms = 0;

    #[ORM\Column]
    private int $wideDoors = 0;

    #[ORM\Column]
    private int $wheelchairTableSpacing = 0;

    #[ORM\Column]
    private int $inclusionBoxesDelivered = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalExpenses = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalIncome = '0.00';

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->capturedFor = new \DateTimeImmutable('first day of this month midnight');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCapturedFor(): \DateTimeImmutable
    {
        return $this->capturedFor;
    }

    public function setCapturedFor(\DateTimeImmutable $capturedFor): static
    {
        // Auf den Monatsanfang normalisieren: Der Unique-Index schützt sonst
        // nicht vor zwei Snapshots desselben Monats mit verschiedenen Tagen.
        $this->capturedFor = $capturedFor->modify('first day of this month')->setTime(0, 0);

        return $this;
    }

    /**
     * Schlüssel für Achsenbeschriftungen und die JSON-Ausgabe.
     */
    public function getMonthKey(): string
    {
        return $this->capturedFor->format('Y-m');
    }

    public function getRestaurantCount(): int
    {
        return $this->restaurantCount;
    }

    public function setRestaurantCount(int $restaurantCount): static
    {
        $this->restaurantCount = $restaurantCount;

        return $this;
    }

    public function getVerifiedCount(): int
    {
        return $this->verifiedCount;
    }

    public function setVerifiedCount(int $verifiedCount): static
    {
        $this->verifiedCount = $verifiedCount;

        return $this;
    }

    public function getCommunesCovered(): int
    {
        return $this->communesCovered;
    }

    public function setCommunesCovered(int $communesCovered): static
    {
        $this->communesCovered = $communesCovered;

        return $this;
    }

    public function getCantonsCovered(): int
    {
        return $this->cantonsCovered;
    }

    public function setCantonsCovered(int $cantonsCovered): static
    {
        $this->cantonsCovered = $cantonsCovered;

        return $this;
    }

    public function getAverageAccessibilityScore(): string
    {
        return $this->averageAccessibilityScore;
    }

    public function setAverageAccessibilityScore(string $averageAccessibilityScore): static
    {
        $this->averageAccessibilityScore = $averageAccessibilityScore;

        return $this;
    }

    public function getStepFreeEntrances(): int
    {
        return $this->stepFreeEntrances;
    }

    public function setStepFreeEntrances(int $stepFreeEntrances): static
    {
        $this->stepFreeEntrances = $stepFreeEntrances;

        return $this;
    }

    public function getAccessibleRestrooms(): int
    {
        return $this->accessibleRestrooms;
    }

    public function setAccessibleRestrooms(int $accessibleRestrooms): static
    {
        $this->accessibleRestrooms = $accessibleRestrooms;

        return $this;
    }

    public function getWideDoors(): int
    {
        return $this->wideDoors;
    }

    public function setWideDoors(int $wideDoors): static
    {
        $this->wideDoors = $wideDoors;

        return $this;
    }

    public function getWheelchairTableSpacing(): int
    {
        return $this->wheelchairTableSpacing;
    }

    public function setWheelchairTableSpacing(int $wheelchairTableSpacing): static
    {
        $this->wheelchairTableSpacing = $wheelchairTableSpacing;

        return $this;
    }

    public function getInclusionBoxesDelivered(): int
    {
        return $this->inclusionBoxesDelivered;
    }

    public function setInclusionBoxesDelivered(int $inclusionBoxesDelivered): static
    {
        $this->inclusionBoxesDelivered = $inclusionBoxesDelivered;

        return $this;
    }

    public function getTotalExpenses(): string
    {
        return $this->totalExpenses;
    }

    public function setTotalExpenses(string $totalExpenses): static
    {
        $this->totalExpenses = $totalExpenses;

        return $this;
    }

    public function getTotalIncome(): string
    {
        return $this->totalIncome;
    }

    public function setTotalIncome(string $totalIncome): static
    {
        $this->totalIncome = $totalIncome;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
