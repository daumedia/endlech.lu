<?php

namespace App\Entity;

use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Enum\WaitlistStatus;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Das Auftragsbuch (Feature 04): eine Zeile je E-Mail-Adresse, die festhält,
 * was in Brevo stehen soll und ob es schon dort steht.
 *
 * ⚠ **Diese Entity hat bewusst keine Beziehungen.** Das ist die einzige Stelle,
 * an der Feature 04 gegen die `ON DELETE`-Konvention des Projekts geht
 * (docs/data-model.md), und der Grund ist zwingend: Ein Wartelisten-Widerruf
 * **löscht** den Eintrag (`WaitlistConfirmationService::revoke()` ruft
 * `remove()`). Hinge der Löschauftrag an einem Fremdschlüssel, verschwände er
 * mit seiner Quelle – und die Adresse bliebe für immer in Brevo stehen. Der
 * Auftrag muss die Löschung seiner Quelle überleben. Die Verbindung läuft
 * deshalb über die E-Mail-Adresse, und `origin` hält fest, woher sie kam.
 *
 * ⚠ **Kein Feld für die Freitextnachricht**, weder hier noch in der Abbildung
 * nach Brevo. Auf einer Barrierefreiheitsplattform kann dort alles stehen –
 * auch eine Gesundheitsangabe und damit eine besondere Kategorie nach Art. 9
 * DSGVO. Was nicht erfasst ist, kann nicht abfließen (AK-29).
 */
#[ORM\Entity(repositoryClass: MarketingContactRepository::class)]
#[ORM\Table(name: 'marketing_contact')]
#[ORM\Index(name: 'IDX_marketing_contact_state_updated', columns: ['sync_state', 'updated_at'])]
#[ORM\HasLifecycleCallbacks]
class MarketingContact
{
    /**
     * Wird zugleich als `ext_id` an Brevo übergeben.
     *
     * Der Kontakt wird dort über diese Kennung adressiert und nicht über die
     * Adresse: Die Adresse ist das einzige Feld, das sich ändern kann. Über sie
     * zu adressieren machte die Adressänderung zum Sonderfall statt zum
     * Normalfall – und hinterließe bei jedem Wechsel einen zweiten Kontakt.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    /** Der fachliche Schlüssel: eine Adresse, ein Kontakt (EC-01). */
    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $organisationName = null;

    /** Sprache der Kampagne. */
    #[ORM\Column(length: 5)]
    private string $locale = 'de';

    #[ORM\Column(length: 20, enumType: MarketingOrigin::class)]
    private MarketingOrigin $origin = MarketingOrigin::ACCOUNT;

    /** Vertriebsstatus; bleibt bei Nutzerkonten leer. */
    #[ORM\Column(length: 20, nullable: true, enumType: WaitlistStatus::class)]
    private ?WaitlistStatus $funnelStatus = null;

    /** Zeitpunkt der Werbe-Einwilligung. */
    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    /**
     * Gesetzt bei einer Abmeldung über Brevo – die Zeile wird damit zur Sperre.
     *
     * Sie bleibt stehen, statt gelöscht zu werden: Sonst trüge der nächste Lauf
     * dieselbe Adresse erneut ein (AK-12). Aufgehoben wird die Sperre allein
     * durch eine **jüngere** Einwilligung (AK-45).
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(length: 20, enumType: MarketingSyncState::class)]
    private MarketingSyncState $syncState = MarketingSyncState::PENDING;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $syncedAt = null;

    /**
     * Klasse und Statuscode des letzten Fehlversuchs – **nie die Antwort im
     * Wortlaut**. Dort stünde sonst die aufgerufene URL samt Schlüssel (AK-31).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastError = null;

    /** Zähler für den Rückzug bei dauerhaftem Fehler. */
    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** Nach so vielen Fehlversuchen greift der Lauf die Zeile nicht mehr auf. */
    public const MAX_ATTEMPTS = 5;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * ⚠ Normalisiert auf Kleinschreibung.
     *
     * Die Adresse ist hier der fachliche Schlüssel, und der Unique-Index ist
     * die einzige Stelle, die EC-01 („eine Adresse, ein Kontakt") durchsetzt.
     * Ohne Normalisierung entstünden aus „Max@example.lu" und „max@example.lu"
     * zwei Zeilen, der Index bliebe zufrieden, und der Empfänger bekäme jede
     * Kampagne doppelt. `MarketingContactRepository` schlägt aus demselben
     * Grund kleingeschrieben nach.
     */
    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): static
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getOrganisationName(): ?string
    {
        return $this->organisationName;
    }

    public function setOrganisationName(?string $organisationName): static
    {
        $this->organisationName = $organisationName;

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

    public function getOrigin(): MarketingOrigin
    {
        return $this->origin;
    }

    public function setOrigin(MarketingOrigin $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getFunnelStatus(): ?WaitlistStatus
    {
        return $this->funnelStatus;
    }

    public function setFunnelStatus(?WaitlistStatus $funnelStatus): static
    {
        $this->funnelStatus = $funnelStatus;

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

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeImmutable $revokedAt): static
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    public function getSyncState(): MarketingSyncState
    {
        return $this->syncState;
    }

    public function setSyncState(MarketingSyncState $syncState): static
    {
        $this->syncState = $syncState;

        return $this;
    }

    public function getSyncedAt(): ?\DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(?\DateTimeImmutable $syncedAt): static
    {
        $this->syncedAt = $syncedAt;

        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): static
    {
        // Hart auf die Spaltenbreite kürzen: Eine überlange Fehlerzeile darf
        // den Lauf nicht mit SQLSTATE[22001] abbrechen lassen.
        $this->lastError = null === $lastError ? null : mb_substr($lastError, 0, 255);

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): static
    {
        $this->attempts = $attempts;

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

    /**
     * Ist diese Adresse gesperrt?
     *
     * Maßgeblich ist der jüngere Zeitpunkt: Liegt die Einwilligung nach dem
     * Widerruf, ist die Sperre überholt. Ohne diese Regel stünden AK-12 („nicht
     * erneut anlegen") und AK-45 („Adresse wieder frei") gegeneinander.
     */
    public function isBlocked(): bool
    {
        if (null === $this->revokedAt) {
            return false;
        }

        return $this->consentAt <= $this->revokedAt;
    }

    /**
     * Hat diese Zeile ihren Rückzug erreicht?
     *
     * Danach bleibt sie auf `failed` stehen und wartet sichtbar in der
     * Verwaltung darauf, von Hand erneut angestoßen zu werden.
     */
    public function hasExhaustedAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Erfolgreiche Übertragung vermerken.
     */
    public function markSynced(): static
    {
        $this->syncState = MarketingSyncState::SYNCED;
        $this->syncedAt = new \DateTimeImmutable();
        $this->lastError = null;
        $this->attempts = 0;

        return $this;
    }

    /**
     * Fehlversuch vermerken.
     *
     * ⚠ `$reason` trägt Klasse und Statuscode, nie die Antwort im Wortlaut.
     *
     * ⚠ **Ein Löschauftrag bleibt ein Löschauftrag.** Fiele `REMOVAL_PENDING`
     * hier auf `FAILED`, wäre der Auftrag verloren – der Kontakt bliebe in
     * Brevo stehen, und zwar dauerhaft, weil die Quelle in diesem Moment schon
     * gelöscht ist und ihn niemand neu stellen kann. Der Zustand bleibt
     * deshalb erhalten; sichtbar wird der Fehlschlag über `lastError` und den
     * erschöpften Versuchszähler (AK-13, AK-15, AK-16).
     */
    public function markFailed(string $reason): static
    {
        if (MarketingSyncState::REMOVAL_PENDING !== $this->syncState) {
            $this->syncState = MarketingSyncState::FAILED;
        }

        $this->setLastError($reason);
        ++$this->attempts;

        return $this;
    }

    /**
     * Steckt diese Zeile fest? Grundlage der roten Anzeige in der Verwaltung.
     *
     * Deckt beide Fälle ab: die auf `failed` gefallene Übertragung und den
     * Löschauftrag, der seinen Zustand behält und trotzdem nicht durchkommt.
     */
    public function isStuck(): bool
    {
        return null !== $this->lastError && $this->hasExhaustedAttempts();
    }
}
