<?php

namespace App\Tests\Integration\Service;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Repository\RestaurantImageRepository;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageUploadServiceTest extends KernelTestCase
{
    // 1×1-PNG; finfo erkennt image/png => guessExtension() liefert 'png' (keine ext-gd nötig).
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private string $uploadDir;
    private EntityManagerInterface $em;
    private ImageUploadService $service;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->uploadDir = sys_get_temp_dir().'/endlech_img_'.uniqid();
        mkdir($this->uploadDir, 0777, true);

        $this->service = new ImageUploadService(
            $this->uploadDir,
            $this->em,
            $container->get(RestaurantImageRepository::class),
        );
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

        return new UploadedFile($source, 'photo.png', 'image/png', null, true);
    }

    private function persistRestaurant(): Restaurant
    {
        $restaurant = (new Restaurant())->setName('Foto Probe')->setCity('Luxembourg');
        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }

    public function testUploadStoresFileAndPersistsImage(): void
    {
        $restaurant = $this->persistRestaurant();

        $image = $this->service->upload($this->uploadedPng(), $restaurant);

        self::assertInstanceOf(RestaurantImage::class, $image);
        self::assertNotNull($image->getId());
        self::assertStringEndsWith('.png', $image->getFilename());
        self::assertFileExists($this->uploadDir.'/'.$image->getFilename());
        self::assertSame(1, $image->getSortOrder());
        // Leerer Alt-Text fällt auf den Restaurant-Namen zurück.
        self::assertSame('Foto Probe', $image->getAltText());
    }

    public function testUploadUsesProvidedAltText(): void
    {
        $restaurant = $this->persistRestaurant();

        $image = $this->service->upload($this->uploadedPng(), $restaurant, 'Aussenansicht');

        self::assertSame('Aussenansicht', $image->getAltText());
    }

    public function testDeleteRemovesFileAndReindexesRemaining(): void
    {
        $restaurant = $this->persistRestaurant();

        $first = $this->service->upload($this->uploadedPng(), $restaurant);
        $middle = $this->service->upload($this->uploadedPng(), $restaurant);
        $last = $this->service->upload($this->uploadedPng(), $restaurant);

        self::assertSame([1, 2, 3], [$first->getSortOrder(), $middle->getSortOrder(), $last->getSortOrder()]);

        $middlePath = $this->uploadDir.'/'.$middle->getFilename();
        self::assertFileExists($middlePath);

        $this->service->delete($middle);

        self::assertFileDoesNotExist($middlePath);

        $repo = static::getContainer()->get(RestaurantImageRepository::class);
        $remaining = $repo->findBy(['restaurant' => $restaurant], ['sortOrder' => 'ASC']);

        self::assertCount(2, $remaining);
        self::assertSame([0, 1], array_map(static fn (RestaurantImage $i) => $i->getSortOrder(), $remaining));
    }
}
