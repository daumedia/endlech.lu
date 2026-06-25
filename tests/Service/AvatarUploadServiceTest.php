<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AvatarUploadServiceTest extends KernelTestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private string $uploadDir;
    private EntityManagerInterface $em;
    private AvatarUploadService $service;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->uploadDir = sys_get_temp_dir().'/endlech_avatar_'.uniqid();
        mkdir($this->uploadDir, 0777, true);

        $this->service = new AvatarUploadService($this->uploadDir, $this->em);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->uploadDir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->uploadDir);
        parent::tearDown();
    }

    private function uploadedPng(): UploadedFile
    {
        $source = $this->uploadDir.'/source_'.uniqid().'.png';
        file_put_contents($source, base64_decode(self::PNG_BASE64));

        return new UploadedFile($source, 'avatar.png', 'image/png', null, true);
    }

    private function fixtureUser(): User
    {
        return static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
    }

    public function testUploadStoresFileAndSetsFilename(): void
    {
        $user = $this->fixtureUser();
        $user->setAvatarFilename(null);

        $filename = $this->service->upload($this->uploadedPng(), $user);

        self::assertStringEndsWith('.png', $filename);
        self::assertSame($filename, $user->getAvatarFilename());
        self::assertFileExists($this->uploadDir.'/'.$filename);
    }

    public function testUploadDeletesPreviousAvatar(): void
    {
        $user = $this->fixtureUser();

        $oldFilename = 'old_'.uniqid().'.png';
        $oldPath = $this->uploadDir.'/'.$oldFilename;
        file_put_contents($oldPath, base64_decode(self::PNG_BASE64));
        $user->setAvatarFilename($oldFilename);

        $newFilename = $this->service->upload($this->uploadedPng(), $user);

        self::assertFileDoesNotExist($oldPath);
        self::assertNotSame($oldFilename, $newFilename);
        self::assertFileExists($this->uploadDir.'/'.$newFilename);
    }

    public function testDeleteRemovesFileAndClearsFilename(): void
    {
        $user = $this->fixtureUser();

        $filename = 'avatar_'.uniqid().'.png';
        $path = $this->uploadDir.'/'.$filename;
        file_put_contents($path, base64_decode(self::PNG_BASE64));
        $user->setAvatarFilename($filename);

        $this->service->delete($user);

        self::assertFileDoesNotExist($path);
        self::assertNull($user->getAvatarFilename());
    }
}
