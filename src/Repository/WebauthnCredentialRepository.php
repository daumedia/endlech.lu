<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Webauthn\Bundle\Repository\CanSaveCredentialRecord;
use Webauthn\Bundle\Repository\CredentialRecordRepositoryInterface;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Speicher für Passkeys – die Brücke zwischen dem WebAuthn-Bundle und unserem Datenmodell.
 *
 * Implementiert bewusst `CredentialRecordRepositoryInterface` und
 * `CanSaveCredentialRecord`; die gleichnamigen `…CredentialSource…`-Varianten
 * sind seit Bundle-Version 5.3 überholt, ebenso die Basisklasse
 * `DoctrineCredentialSourceRepository` (seit 5.2).
 *
 * @extends ServiceEntityRepository<WebauthnCredential>
 */
class WebauthnCredentialRepository extends ServiceEntityRepository implements CredentialRecordRepositoryInterface, CanSaveCredentialRecord
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($registry, WebauthnCredential::class);
    }

    /**
     * @return array<CredentialRecord>
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return $this->findBy(['userHandle' => $publicKeyCredentialUserEntity->id]);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord
    {
        // Die rohe Kennung übergeben, NICHT base64-kodiert: Die Spalte trägt
        // den DBAL-Typ `base64`, und Doctrine kodiert gebundene Parameter
        // anhand des Feld-Mappings selbst. Eine Kodierung von Hand käme
        // doppelt an und fände nie etwas. (Das mitgelieferte
        // DoctrineCredentialSourceRepository kodiert vor – es baut die Abfrage
        // aber über einen QueryBuilder ohne Feldbezug.)
        return $this->findOneBy(['publicKeyCredentialId' => $publicKeyCredentialId]);
    }

    /**
     * Legt einen Passkey an oder schreibt den Signaturzähler fort.
     *
     * Wird bei JEDER Anmeldung gerufen, nicht nur beim Anlegen: Der Zähler im
     * Datensatz wandert mit und ist der Klon-Schutz. Ein reines Anlegen erzeugte
     * hier Duplikate und liefe der Prüfung zuwider.
     *
     * Beim Anmelden ist der übergebene Datensatz bereits diese Entity – er kam
     * aus findOneByCredentialId() und wurde vom Prüfer nur fortgeschrieben.
     * Beim Anlegen ist es ein frischer PublicKeyCredentialSource, den wir
     * übersetzen müssen.
     */
    public function saveCredentialRecord(CredentialRecord $credentialRecord): void
    {
        $em = $this->getEntityManager();

        if ($credentialRecord instanceof WebauthnCredential) {
            $credentialRecord->markUsed();
            $em->persist($credentialRecord);
            $em->flush();

            return;
        }

        $user = $em->getRepository(User::class)->findOneBy(['webauthnHandle' => $credentialRecord->userHandle]);

        if (!$user instanceof User) {
            // Ohne zugehöriges Konto gäbe es einen Passkey ohne Besitzer – der
            // wäre weder im Profil sichtbar noch löschbar. Lieber gar nicht anlegen.
            return;
        }

        $credential = WebauthnCredential::fromRecord($credentialRecord, $user, $this->guessDeviceName());

        $em->persist($credential);
        $em->flush();
    }

    /**
     * @return WebauthnCredential[]
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Rät einen Anzeigenamen aus dem User-Agent.
     *
     * Bewusst Produktnamen statt Übersetzungsschlüssel: „iPhone" heißt in allen
     * vier Sprachen gleich, und der Name wird einmal beim Anlegen festgeschrieben –
     * ein übersetzter Wert trüge sonst für immer die Sprache jenes einen Moments.
     * Wem der Vorschlag nicht passt, benennt den Passkey im Profil um.
     */
    private function guessDeviceName(): string
    {
        $agent = $this->requestStack->getCurrentRequest()?->headers->get('User-Agent') ?? '';

        return match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Passkey',
        };
    }
}
