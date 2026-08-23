<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'user.email_unique')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    /**
     * Kennung, unter der dieses Konto gegenüber Passkeys auftritt (WebAuthn user handle).
     *
     * Bewusst nicht die Datenbank-ID: Der Wert liegt dauerhaft auf dem Gerät des
     * Nutzers und wandert bei jeder Anmeldung mit. Eine fortlaufende Zahl gäbe
     * dort die Nutzerzahl preis und verknüpfte ein fremdverwahrtes Datum fest
     * mit der internen Identität.
     *
     * Nullable, damit Bestandskonten ohne Datenmigration auskommen – der Wert
     * entsteht erst beim ersten Passkey.
     */
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $webauthnHandle = null;

    /** @var Collection<int, WebauthnCredential> */
    #[ORM\OneToMany(targetEntity: WebauthnCredential::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $passkeys;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->passkeys = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
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

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationTokenExpiresAt;
    }

    public function setVerificationTokenExpiresAt(?\DateTimeImmutable $verificationTokenExpiresAt): static
    {
        $this->verificationTokenExpiresAt = $verificationTokenExpiresAt;

        return $this;
    }

    public function isVerificationTokenExpired(): bool
    {
        if ($this->verificationTokenExpiresAt === null) {
            return true;
        }

        return $this->verificationTokenExpiresAt < new \DateTimeImmutable();
    }

    public function generateVerificationToken(): string
    {
        $this->verificationToken = bin2hex(random_bytes(32));
        $this->verificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');

        return $this->verificationToken;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): static
    {
        $this->avatarFilename = $avatarFilename;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarFilename ? '/uploads/avatars/' . $this->avatarFilename : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getWebauthnHandle(): ?string
    {
        return $this->webauthnHandle;
    }

    /**
     * Erzeugt den WebAuthn-Handle beim ersten Passkey und gibt ihn danach unverändert zurück.
     *
     * 16 Zufallsbytes, nicht 32 wie bei generateVerificationToken(): Als Hex sind
     * das 32 Zeichen, und PublicKeyCredentialUserEntity lässt für die Kennung
     * höchstens 64 Byte zu. Ein 64-Zeichen-Handle läge exakt auf der Grenze.
     */
    public function obtainWebauthnHandle(): string
    {
        if ($this->webauthnHandle === null) {
            $this->webauthnHandle = bin2hex(random_bytes(16));
        }

        return $this->webauthnHandle;
    }

    /**
     * @return Collection<int, WebauthnCredential>
     */
    public function getPasskeys(): Collection
    {
        return $this->passkeys;
    }

    public function addPasskey(WebauthnCredential $passkey): static
    {
        if (!$this->passkeys->contains($passkey)) {
            $this->passkeys->add($passkey);
            $passkey->setUser($this);
        }

        return $this;
    }

    public function removePasskey(WebauthnCredential $passkey): static
    {
        if ($this->passkeys->removeElement($passkey) && $passkey->getUser() === $this) {
            $passkey->setUser(null);
        }

        return $this;
    }
}
