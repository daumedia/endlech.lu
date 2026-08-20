<?php

namespace App\Entity;

use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use App\Repository\FinanceEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ein Posten der öffentlichen Finanzübersicht auf /open.
 *
 * Das ist keine Buchhaltung, sondern die Datenquelle einer Transparenzseite:
 * Ein Eintrag pro Beleg, aggregiert wird ausschließlich nach Kategorie. Es gibt
 * bewusst kein Feld für Vertragspartner, Restaurant oder Rechnungsnummer –
 * was nicht erfasst ist, kann auch nicht versehentlich veröffentlicht werden.
 */
#[ORM\Entity(repositoryClass: FinanceEntryRepository::class)]
#[ORM\Index(name: 'IDX_finance_entry_type_date', columns: ['type', 'entry_date'])]
#[ORM\HasLifecycleCallbacks]
class FinanceEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Belegdatum. `date` wäre ein reserviertes Wort in MySQL – die Spalte
     * heißt deshalb `entry_date`, die Property bleibt sprechend.
     */
    #[ORM\Column(name: 'entry_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    /**
     * Redundant zu `category->type()`, aber als eigene Spalte indiziert: Die
     * Aggregation nach Einnahmen/Ausgaben läuft damit in SQL statt über einen
     * PHP-Mapping-Schritt pro Zeile. Konsistenz garantiert setCategory().
     */
    #[ORM\Column(length: 20, enumType: FinanceType::class)]
    private FinanceType $type = FinanceType::EXPENSE;

    #[ORM\Column(length: 40, enumType: FinanceCategory::class)]
    private FinanceCategory $category = FinanceCategory::HOSTING;

    /**
     * Immer positiv – die Richtung steckt in $type. Ein negativer Betrag wäre
     * eine zweite, stille Kodierung derselben Information und würde Summen
     * doppelt invertieren.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount = '0.00';

    /**
     * Stückzahl, nur für Material-Belege der Inclusion Box. Die Zahl gelieferter
     * Boxen hängt damit am Beleg, der sie bezahlt hat, und kann nicht getrennt
     * davon veralten.
     */
    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->date = $now->setTime(0, 0);
        $this->createdAt = $now;
        // PreUpdate feuert beim ersten persist() nicht – daher hier initialisieren.
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getType(): FinanceType
    {
        return $this->type;
    }

    public function getCategory(): FinanceCategory
    {
        return $this->category;
    }

    /**
     * Setzt die Richtung mit. Es gibt absichtlich keinen setType(): Eine
     * Ausgabe unter einer Einnahmekategorie wäre in der veröffentlichten
     * Summe nicht mehr als Fehler erkennbar.
     */
    public function setCategory(FinanceCategory $category): static
    {
        $this->category = $category;
        $this->type = $category->type();

        // Stückzahlen gibt es nur bei der Inclusion Box. Nach einem
        // Kategoriewechsel bliebe der Wert sonst als Leiche stehen und würde
        // weiter in die Impact-Zahl einfließen.
        if (!$category->tracksQuantity()) {
            $this->quantity = null;
        }

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Normalisiert auf zwei Nachkommastellen.
     *
     * Ohne das hängt die Schreibweise davon ab, woher der Wert kommt: Das
     * Formular liefert "42.5", die Datenbank "42.50". Ein Vergleich zweier
     * Beträge – etwa in einem Test oder beim Prüfen auf Änderungen – wäre
     * dann davon abhängig, ob die Entity zwischendurch neu geladen wurde.
     * Gerundet wird ohnehin, die Spalte ist DECIMAL(10,2).
     */
    public function setAmount(string $amount): static
    {
        $this->amount = number_format((float) $amount, 2, '.', '');

        return $this;
    }

    public function getAmountAsFloat(): float
    {
        return (float) $this->amount;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

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
