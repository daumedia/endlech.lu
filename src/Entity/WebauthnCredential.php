<?php

namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\TrustPath;

/**
 * Ein beim Nutzer hinterlegter Passkey.
 *
 * Erbt von `Webauthn\CredentialRecord`, weil das Bundle dafür selbst eine
 * XML-mapped-superclass registriert (`WebauthnBundle::registerMappings()`) und
 * die zugehörigen DBAL-Typen (`base64`, `aaguid`, `trust_path`) über
 * `WebauthnExtension::prepend()` einträgt. Die geerbten Spalten brauchen hier
 * deshalb keine Attribute – und es entfällt der Umweg, den Datensatz von Hand
 * zu serialisieren.
 *
 * Eigene Felder sind nur die, die das Profil braucht, plus die Relation zum
 * Nutzer: Der geerbte `userHandle` ist ein blosser String und gäbe weder eine
 * Löschkaskade noch eine Liste für die Profilseite.
 */
#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credential')]
// Beide Indizes bedienen je eine Abfrage des Bundles. Die Kennung liegt als
// LONGTEXT in der Tabelle (so deklariert der DBAL-Typ `base64`), weshalb sie
// nur mit Längenangabe indizierbar ist – 100 Zeichen trennen die Einträge
// zuverlässig, ohne den Index aufzublähen.
#[ORM\Index(name: 'IDX_webauthn_credential_id', columns: ['public_key_credential_id'], options: ['lengths' => [100]])]
#[ORM\Index(name: 'IDX_webauthn_credential_handle', columns: ['user_handle'])]
class WebauthnCredential extends CredentialRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'passkeys')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * @param string[]                 $transports
     * @param array<string, mixed>|null $otherUI
     */
    public function __construct(
        string $publicKeyCredentialId,
        string $type,
        array $transports,
        string $attestationType,
        TrustPath $trustPath,
        Uuid $aaguid,
        string $credentialPublicKey,
        string $userHandle,
        int $counter,
        ?array $otherUI = null,
        ?bool $backupEligible = null,
        ?bool $backupStatus = null,
        ?bool $uvInitialized = null,
    ) {
        parent::__construct(
            $publicKeyCredentialId,
            $type,
            $transports,
            $attestationType,
            $trustPath,
            $aaguid,
            $credentialPublicKey,
            $userHandle,
            $counter,
            $otherUI,
            $backupEligible,
            $backupStatus,
            $uvInitialized,
        );

        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Baut aus dem Datensatz, den die Attestation-Prüfung liefert, unsere Entity.
     *
     * Nötig, weil das Bundle beim Anlegen immer einen `PublicKeyCredentialSource`
     * erzeugt und nie unsere Klasse – anders als beim Anmelden, wo der Datensatz
     * aus dem Repository stammt und damit bereits diese Entity ist.
     */
    public static function fromRecord(CredentialRecord $record, User $user, string $name): self
    {
        $credential = new self(
            $record->publicKeyCredentialId,
            $record->type,
            $record->transports,
            $record->attestationType,
            $record->trustPath,
            $record->aaguid,
            $record->credentialPublicKey,
            $record->userHandle,
            $record->counter,
            $record->otherUI,
            $record->backupEligible,
            $record->backupStatus,
            $record->uvInitialized,
        );

        $credential->user = $user;
        $credential->name = $name;

        return $credential;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Hält fest, dass mit diesem Passkey gerade angemeldet wurde.
     */
    public function markUsed(): static
    {
        $this->lastUsedAt = new \DateTimeImmutable();

        return $this;
    }
}
