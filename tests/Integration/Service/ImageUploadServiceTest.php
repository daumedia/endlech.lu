<?php

namespace App\Tests\Integration\Service;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Repository\RestaurantImageRepository;
use App\Service\ImageUploadService;
use App\Service\UploadRejectedException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * BF-57: Was kein Bild ist, kommt nicht ins öffentliche Verzeichnis.
     *
     * Eine `.html` wurde vorher als `text/html` ausgeliefert und lief damit im
     * Ursprung der Anwendung; eine `.svg` mit `<script>` ebenso. Der Upload-Weg
     * geht nicht über ein Formular, deshalb griff kein `File`-Constraint — die
     * Prüfung sitzt jetzt im Dienst und damit auf jedem Aufrufweg.
     */
    #[DataProvider('unerlaubteDateien')]
    public function testNichtBilderWerdenAbgelehnt(string $inhalt, string $name, string $mime): void
    {
        $restaurant = $this->persistRestaurant();
        $quelle = $this->uploadDir.'/angriff_'.uniqid();
        file_put_contents($quelle, $inhalt);

        $this->expectException(UploadRejectedException::class);

        try {
            $this->service->upload(new UploadedFile($quelle, $name, $mime, null, true), $restaurant);
        } finally {
            // Nichts darf im Zielverzeichnis gelandet sein — nur die Quelldatei.
            $abgelegt = array_filter(
                glob($this->uploadDir.'/*') ?: [],
                static fn (string $pfad) => !str_contains(basename($pfad), 'angriff_'),
            );
            self::assertSame([], $abgelegt, 'Die abgelehnte Datei wurde trotzdem abgelegt.');
        }
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function unerlaubteDateien(): iterable
    {
        yield 'HTML mit Skript' => [
            '<html><body><script>alert(document.domain)</script></body></html>',
            'angriff.html',
            'text/html',
        ];
        yield 'SVG mit Skript' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            'angriff.svg',
            'image/svg+xml',
        ];
        yield 'PHP-Quelltext' => ['<?php echo 1; ?>', 'angriff.php', 'application/x-php'];
        yield 'Textdatei' => ['nur Text', 'notiz.txt', 'text/plain'];
    }

    /**
     * Die Endung folgt dem ECHTEN Typ, nicht dem übermittelten Dateinamen.
     */
    public function testEndungKommtAusDemErkanntenTypNichtAusDemNamen(): void
    {
        $restaurant = $this->persistRestaurant();
        $quelle = $this->uploadDir.'/source_'.uniqid();
        file_put_contents($quelle, base64_decode(self::PNG_BASE64));

        $image = $this->service->upload(
            new UploadedFile($quelle, 'getarnt.jpg', 'image/jpeg', null, true),
            $restaurant,
        );

        self::assertStringEndsWith('.png', $image->getFilename());
    }

    /**
     * BF-58: Die Anwendung hat eine eigene Größengrenze.
     */
    public function testZuGrosseDateiWirdAbgelehnt(): void
    {
        $restaurant = $this->persistRestaurant();
        $quelle = $this->uploadDir.'/gross_'.uniqid().'.png';
        // Gültiger PNG-Kopf, danach Füllmaterial über die Grenze hinaus.
        file_put_contents($quelle, base64_decode(self::PNG_BASE64).str_repeat("\0", 5 * 1024 * 1024));

        $this->expectException(UploadRejectedException::class);
        $this->service->upload(new UploadedFile($quelle, 'gross.png', 'image/png', null, true), $restaurant);
    }
}
