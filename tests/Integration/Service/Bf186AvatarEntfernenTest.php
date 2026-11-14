<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Account\AccountDeleter;
use App\Entity\User;
use App\Marketing\MarketingContactRegistry;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\BoardIdeaRepository;
use App\Repository\UserRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\LockFactory;

/**
 * BF-186 (B04 / 12) — „Profilbild entfernen" und die Kontolöschung neben einem Upload.
 *
 * Restfall von BF-180: Trug die beim Anfragebeginn geladene Entity noch kein Bild, während ein Upload daneben schon
 * eines gespeichert hatte, löschte `delete()` die Datei des frisch gelesenen Namens — `setAvatarFilename(null)` war für
 * Doctrine aber keine Änderung (`null` → `null`), der `flush()` schrieb nichts. Das Konto verwies auf eine gelöschte
 * Datei: kaputtes Bild in Kopfzeile und Profil. `AccountDeleter` fragte nur den Entity-Namen ab und rief `delete()` gar
 * nicht erst — die Bilddatei blieb nach der Kontolöschung liegen.
 *
 * Nachgestellt wird der Stand, den der Upload der anderen Anfrage hinterlässt: Datei im Verzeichnis, Name in der
 * Datenbank, an der Unit of Work dieser Anfrage vorbei.
 */
final class Bf186AvatarEntfernenTest extends KernelTestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private string $verzeichnis;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->verzeichnis = sys_get_temp_dir().'/endlech_bf186_'.uniqid();
        mkdir($this->verzeichnis, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->verzeichnis.'/*') ?: [] as $datei) {
            @unlink($datei);
        }
        @rmdir($this->verzeichnis);
        parent::tearDown();
    }

    public function testBf186EntfernenMitLeeremEntityNamenRaeumtDateiUndVerweisAb(): void
    {
        $user = $this->kontoOhneBild('bf186-entfernen@example.org');
        $this->bildDerAnderenAnfrage($user, 'von-der-anderen-anfrage.png');

        $this->dienst()->delete($user);

        clearstatcache();
        self::assertFileDoesNotExist($this->verzeichnis.'/von-der-anderen-anfrage.png', 'Entfernen heißt: Datei weg (AK-07).');
        self::assertNull(
            $this->namenInDerDatenbank($user),
            'Und Verweis weg — sonst zeigt das Konto auf eine gelöschte Datei.',
        );
        self::assertNull($user->getAvatarFilename());
    }

    /**
     * Gegenprobe zum Hauptweg (AK-07): Entity und Datenbank kennen dasselbe Bild — Datei und Verweis gehen wie bisher.
     */
    public function testEntfernenOhneGleichzeitigenUploadWieBisher(): void
    {
        $user = $this->kontoOhneBild('bf186-hauptweg@example.org');
        file_put_contents($this->verzeichnis.'/eigenes.png', base64_decode(self::PNG_BASE64));
        $user->setAvatarFilename('eigenes.png');
        $this->em->flush();

        $this->dienst()->delete($user);

        clearstatcache();
        self::assertFileDoesNotExist($this->verzeichnis.'/eigenes.png');
        self::assertNull($this->namenInDerDatenbank($user));
    }

    public function testBf186KontoloeschungRaeumtDasZwischenzeitlichGespeicherteBildAb(): void
    {
        $user = $this->kontoOhneBild('bf186-konto@example.org');
        $this->bildDerAnderenAnfrage($user, 'von-der-anderen-anfrage.png');
        $id = $user->getId();

        $this->loescher()->delete($user);

        clearstatcache();
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM `user` WHERE id = ?', [$id]));
        self::assertFileDoesNotExist(
            $this->verzeichnis.'/von-der-anderen-anfrage.png',
            'Nach der Kontolöschung darf keine Bilddatei des Kontos liegen bleiben (Art. 17 DSGVO, BF-53).',
        );
    }

    // ─── Hilfen ─────────────────────────────────────────────────────────────

    private function kontoOhneBild(string $email): User
    {
        $user = new User();
        $user->setName('Bf186 Konto')->setEmail($email)->setPassword('unbenutzt')->setIsVerified(true);
        $user->setRoles([]);
        $this->em->persist($user);
        $this->em->flush();
        self::assertNull($user->getAvatarFilename());

        return $user;
    }

    /** Was ein Upload der anderen Anfrage hinterlässt: Datei im Verzeichnis, Name in der Datenbank. */
    private function bildDerAnderenAnfrage(User $user, string $name): void
    {
        file_put_contents($this->verzeichnis.'/'.$name, base64_decode(self::PNG_BASE64));
        $this->em->getConnection()->executeStatement('UPDATE `user` SET avatar_filename = ? WHERE id = ?', [$name, $user->getId()]);
    }

    private function namenInDerDatenbank(User $user): mixed
    {
        return $this->em->getConnection()->fetchOne('SELECT avatar_filename FROM `user` WHERE id = ?', [$user->getId()]);
    }

    private function dienst(): AvatarUploadService
    {
        return new AvatarUploadService($this->verzeichnis, $this->em, static::getContainer()->get(LockFactory::class));
    }

    /** Der echte Löschdienst, nur mit dem Bildverzeichnis dieses Tests statt `public/uploads/avatars`. */
    private function loescher(): AccountDeleter
    {
        $c = static::getContainer();

        return new AccountDeleter(
            $this->em,
            $c->get(UserRepository::class),
            $this->dienst(),
            $c->get(MarketingContactRegistry::class),
            $c->get(BoardIdeaRepository::class),
            $c->get(AppWaitlistEntryRepository::class),
        );
    }
}
