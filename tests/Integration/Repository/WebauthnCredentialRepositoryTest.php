<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

final class WebauthnCredentialRepositoryTest extends KernelTestCase
{
    private WebauthnCredentialRepository $repository;

    private EntityManagerInterface $entityManager;

    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->repository = $container->get(WebauthnCredentialRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertInstanceOf(User::class, $user, 'Fixture-User fehlt – Test-DB nicht geladen?');

        $user->obtainWebauthnHandle();
        $this->entityManager->flush();

        $this->user = $user;
    }

    public function testSaveCreatesCredentialForTheMatchingUser(): void
    {
        $this->repository->saveCredentialRecord($this->record('erste-kennung'));

        $stored = $this->repository->findOneByCredentialId('erste-kennung');

        self::assertInstanceOf(WebauthnCredential::class, $stored);
        self::assertSame($this->user->getId(), $stored->getUser()?->getId());
        self::assertSame('erste-kennung', $stored->publicKeyCredentialId);
        self::assertNotSame('', $stored->getName(), 'Ohne Namen wäre der Passkey im Profil nicht unterscheidbar.');
    }

    /**
     * Der Signaturzähler wandert bei jeder Anmeldung mit. Würde dabei ein
     * zweiter Datensatz entstehen, liefe der Klon-Schutz ins Leere und die
     * Profilliste zeigte denselben Passkey mehrfach.
     */
    public function testSecondSaveUpdatesInsteadOfInserting(): void
    {
        $this->repository->saveCredentialRecord($this->record('wiederholte-kennung'));

        $stored = $this->repository->findOneByCredentialId('wiederholte-kennung');
        self::assertInstanceOf(WebauthnCredential::class, $stored);

        ++$stored->counter;
        $this->repository->saveCredentialRecord($stored);

        self::assertCount(1, $this->repository->findBy(['userHandle' => $this->user->getWebauthnHandle()]));
        self::assertSame(1, $this->repository->findOneByCredentialId('wiederholte-kennung')?->counter);
    }

    public function testSaveMarksTheCredentialAsUsed(): void
    {
        $this->repository->saveCredentialRecord($this->record('benutzte-kennung'));

        $stored = $this->repository->findOneByCredentialId('benutzte-kennung');
        self::assertInstanceOf(WebauthnCredential::class, $stored);
        self::assertNull($stored->getLastUsedAt(), 'Frisch angelegt ist noch nichts benutzt worden.');

        $this->repository->saveCredentialRecord($stored);

        self::assertNotNull($this->repository->findOneByCredentialId('benutzte-kennung')?->getLastUsedAt());
    }

    /**
     * Die Kennung liegt base64-kodiert in der Datenbank – ohne die Umrechnung
     * in findOneByCredentialId() fände die Anmeldung nie ein Credential.
     */
    public function testCredentialIsStoredBase64EncodedButFoundByRawId(): void
    {
        $raw = random_bytes(32);
        $this->repository->saveCredentialRecord($this->record($raw));

        self::assertNotNull($this->repository->findOneByCredentialId($raw));
        self::assertNull($this->repository->findOneByCredentialId(base64_encode($raw)));
    }

    public function testFindAllForUserEntityReturnsOnlyOwnCredentials(): void
    {
        $this->repository->saveCredentialRecord($this->record('eigene-kennung'));

        $other = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@endlech.lu']);
        self::assertInstanceOf(User::class, $other);
        $other->obtainWebauthnHandle();
        $this->entityManager->flush();
        $this->repository->saveCredentialRecord($this->record('fremde-kennung', $other));

        $found = $this->repository->findAllForUserEntity(new PublicKeyCredentialUserEntity(
            (string) $this->user->getEmail(),
            (string) $this->user->getWebauthnHandle(),
            (string) $this->user->getName(),
        ));

        self::assertCount(1, $found);
        self::assertSame('eigene-kennung', $found[0]->publicKeyCredentialId);
    }

    /**
     * Ein Passkey, dessen Handle zu keinem Konto gehört, liesse sich weder
     * benutzen noch im Profil entfernen.
     */
    public function testCredentialWithUnknownUserHandleIsNotStored(): void
    {
        $orphan = new PublicKeyCredentialSource(
            'verwaiste-kennung',
            'public-key',
            ['internal'],
            'none',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            random_bytes(32),
            'handle-das-es-nicht-gibt',
            0,
        );

        $this->repository->saveCredentialRecord($orphan);

        self::assertNull($this->repository->findOneByCredentialId('verwaiste-kennung'));
    }

    private function record(string $credentialId, ?User $owner = null): CredentialRecord
    {
        $owner ??= $this->user;

        return new PublicKeyCredentialSource(
            $credentialId,
            'public-key',
            ['internal'],
            'none',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            random_bytes(32),
            $owner->obtainWebauthnHandle(),
            0,
        );
    }
}
