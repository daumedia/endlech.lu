<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Repository\UserRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Lock\LockFactory;

/**
 * BF-173 — gleichzeitige Uploads hinterließen Bilddateien, auf die kein Konto mehr verweist.
 *
 * Jede Anfrage las den alten Dateinamen aus ihrer eigenen, beim Anfragebeginn geladenen Entity.
 * Hatte eine zweite Anfrage inzwischen ein Bild gespeichert, löschte die erste den längst ersetzten
 * Namen und überschrieb den neuen — dessen Datei blieb liegen. Nachgestellt wird genau dieser
 * veraltete Stand: Die Datenbank kennt schon das Bild der „anderen" Anfrage, die Entity noch das alte.
 */
final class Bf173AvatarVerwaistTest extends KernelTestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private string $verzeichnis;

    protected function setUp(): void
    {
        $this->verzeichnis = sys_get_temp_dir().'/endlech_bf173_'.uniqid();
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

    public function testBf173DasZwischenzeitlichGespeicherteBildWirdErsetztNichtVerwaist(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertNotNull($user);

        // Die „andere" Anfrage hat schon gespeichert: Datei und Datenbank kennen ihr Bild.
        file_put_contents($this->verzeichnis.'/von-der-anderen-anfrage.png', base64_decode(self::PNG_BASE64));
        $em->getConnection()->executeStatement('UPDATE `user` SET avatar_filename = ? WHERE id = ?', ['von-der-anderen-anfrage.png', $user->getId()]);
        // Diese Anfrage hat ihr Konto vorher geladen und sieht noch das alte Bild.
        $user->setAvatarFilename('alt.png');
        file_put_contents($this->verzeichnis.'/alt.png', base64_decode(self::PNG_BASE64));

        $quelle = $this->verzeichnis.'/quelle_'.uniqid().'.png';
        file_put_contents($quelle, base64_decode(self::PNG_BASE64));
        $dienst = new AvatarUploadService($this->verzeichnis, $em, static::getContainer()->get(LockFactory::class));
        $neu = $dienst->upload(new UploadedFile($quelle, 'bild.png', 'image/png', null, true), $user);

        $uebrig = array_map('basename', glob($this->verzeichnis.'/*.png') ?: []);
        self::assertSame([$neu], $uebrig, 'Übrig bleibt nur das neue Bild — das zwischenzeitlich gespeicherte darf nicht ohne Konto liegen bleiben.');
    }
}
