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

    /**
     * Zeitpunkt, zu dem der Interessent **selbst** bestätigt hat (BF-89).
     *
     * ⚠ **`confirmedAt` allein reicht nicht, um das zu beantworten.** Es wird
     * an zwei Stellen gesetzt: beim eingelösten Double-Opt-In **und** beim
     * Statuswechsel in der Verwaltung, wenn ein Eintrag von Hand weitergesetzt
     * wird (Bestandsmuster für telefonisch geführte Kontakte). Wer die beiden
     * Fälle unterscheiden muss, kann es an `confirmedAt` nicht.
     *
     * Genau daran ist die erste Reparatur von BF-83 gescheitert: Sie zog die
     * Prüfung vor den Backfill, womit der *erste* Statuswechsel sauber war und
     * der *zweite* das nachgesetzte Feld vorfand. Eine Reparatur an der
     * Reihenfolge kann eine Zweideutigkeit nicht auflösen — sie verschiebt sie.
     *
     * Dieses Feld setzt **ausschließlich** `confirm()`, also der Weg über den
     * Bestätigungslink. Alles, was einer belegten Adresse bedarf — allen voran
     * die Übertragung nach Brevo (AK-05) — fragt hier und nicht bei
     * `confirmedAt`.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $selfConfirmedAt = null;

    /** Zeitpunkt der Einwilligung (DSGVO-Nachweis) – wird beim Absenden gesetzt. */
    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    /**
     * Zeitpunkt der **Werbe**-Einwilligung; `null` heißt: keine (Feature 04).
     *
     * Getrennt von `$consentAt`: Jene deckt die Kontaktaufnahme **zum Angebot**,
     * um das es bei der Anmeldung geht. Ein Newsletter geht darüber hinaus und
     * braucht deshalb eine eigene, nicht vorangehakte Entscheidung (AK-04).
     *
     * Hier steht der Zeitpunkt und nicht das Häkchen – Art. 7 Abs. 1 DSGVO
     * verlangt, die Einwilligung nachweisen zu können. Dasselbe Muster wie bei
     * `$consentAt`, dessen Formularfeld ebenfalls `mapped: false` ist.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $marketingConsentAt = null;

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

    /**
     * Der eingelöste Double-Opt-In — der einzige Weg, auf dem
     * `selfConfirmedAt` entsteht (BF-89).
     *
     * ⚠ **BF-91: Der Status wird nur aus `PENDING` heraus gesetzt.** Diese
     * Methode wurde bis BF-89 nie erreicht, wenn ein Admin den Eintrag
     * zwischenzeitlich weitergesetzt hatte — der Service stieg vorher mit
     * „bereits bestätigt" aus. Seit diese Abbruchbedingung (zu Recht) weg ist,
     * kommt eine späte Bestätigung hier an, und ein unbedingtes
     * `status = CONFIRMED` warf einen gewonnenen Kunden auf „bestätigt"
     * zurück. Gemessen: `converted` → `confirmed`, und der Rückfall wanderte
     * über das Auftragsbuch bis nach Brevo (AK-08).
     *
     * Ein fortgeschrittener Vertriebsstand ist die **jüngere** Information;
     * die Bestätigung sagt nichts über ihn aus. Die Zeitstempel werden
     * trotzdem unbedingt gesetzt: Die Selbstbestätigung ist eingetreten und
     * gehört festgehalten.
     */
    public function confirm(): static
    {
        $now = new \DateTimeImmutable();

        if (WaitlistStatus::PENDING === $this->status) {
            $this->status = WaitlistStatus::CONFIRMED;
        }

        $this->confirmedAt = $now;
        $this->selfConfirmedAt = $now;

        return $this;
    }

    public function getSelfConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->selfConfirmedAt;
    }

    /**
     * Hat der Interessent **selbst** bestätigt?
     *
     * Der Unterschied zu `isConfirmed()` ist der ganze Punkt: Jenes ist auch
     * nach einem Verwaltungs-Statuswechsel wahr.
     */
    public function hasSelfConfirmed(): bool
    {
        return null !== $this->selfConfirmedAt;
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

    public function getMarketingConsentAt(): ?\DateTimeImmutable
    {
        return $this->marketingConsentAt;
    }

    public function setMarketingConsentAt(?\DateTimeImmutable $marketingConsentAt): static
    {
        $this->marketingConsentAt = $marketingConsentAt;

        return $this;
    }

    public function hasMarketingConsent(): bool
    {
        return null !== $this->marketingConsentAt;
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
