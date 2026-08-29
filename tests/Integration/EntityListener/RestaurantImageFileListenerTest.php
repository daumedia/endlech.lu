<?php

declare(strict_types=1);

namespace App\Tests\Integration\EntityListener;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * BF-53: Bilddateien überlebten das Löschen ihres Restaurants.
 *
 * Die Datenbankzeilen verschwanden über die ORM-Kaskade, und dabei lief
 * `ImageUploadService::delete()` nie — der Dienst kannte den Weg, aber niemand
 * ging ihn. Bei der Prüfung von B09 lagen fünf verwaiste Dateien aus Februar und
 * Juni im Verzeichnis, weiterhin unter ihrer alten Adresse abrufbar.
 *
 * Der Test schreibt bewusst ins ECHTE Upload-Verzeichnis: Der Listener bekommt
 * seinen Pfad per Autowire, und geprüft werden soll die Verdrahtung, nicht die
 * Logik allein. Die Datenbankänderungen rollt DAMA zurück; die Datei räumt
 * `tearDown()` weg, falls der Listener sie stehen lässt.
 */
final class RestaurantImageFileListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private string $uploadDir;

    /** @var list<string> */
    private array $angelegt = [];

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->uploadDir = $container->getParameter('kernel.project_dir').'/public/uploads/restaurants';
    }

    protected function tearDown(): void
    {
        foreach ($this->angelegt as $pfad) {
            @unlink($pfad);
        }
        parent::tearDown();
    }

    private function bilddateiAnlegen(): string
    {
        $name = 'qa_listener_'.uniqid().'.png';
        $pfad = $this->uploadDir.'/'.$name;
        file_put_contents($pfad, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ));
        $this->angelegt[] = $pfad;

        return $name;
    }

    private function restaurantMitBild(string $dateiname): Restaurant
    {
        $restaurant = (new Restaurant())->setName('Listener Probe '.uniqid())->setCity('Luxembourg');

        $bild = new RestaurantImage();
        $bild->setFilename($dateiname);
        $bild->setAltText('Probe');
        $bild->setUploadedAt(new \DateTimeImmutable());
        $bild->setSortOrder(0);
        $bild->setRestaurant($restaurant);

        $this->em->persist($restaurant);
        $this->em->persist($bild);
        $this->em->flush();
        $this->em->refresh($restaurant);

        return $restaurant;
    }

    public function testDateiVerschwindetMitDemEinzelnenBild(): void
    {
        $name = $this->bilddateiAnlegen();
        $restaurant = $this->restaurantMitBild($name);

        self::assertFileExists($this->uploadDir.'/'.$name);

        $this->em->remove($restaurant->getImages()->first());
        $this->em->flush();

        self::assertFileDoesNotExist($this->uploadDir.'/'.$name);
    }

    /**
     * Der Fall aus dem Befund: gelöscht wird das Restaurant, nicht das Bild.
     */
    public function testDateiVerschwindetMitDemRestaurant(): void
    {
        $name = $this->bilddateiAnlegen();
        $restaurant = $this->restaurantMitBild($name);

        self::assertFileExists($this->uploadDir.'/'.$name);

        $this->em->remove($restaurant);
        $this->em->flush();

        self::assertFileDoesNotExist(
            $this->uploadDir.'/'.$name,
            'Die Bilddatei hat das Löschen des Restaurants überlebt.',
        );
    }

    /**
     * Ein fehlender Dateiname darf den Löschvorgang nicht aufhalten.
     */
    public function testFehlendeDateiIstKeinFehler(): void
    {
        $restaurant = $this->restaurantMitBild('gibt-es-nicht-'.uniqid().'.png');

        $this->em->remove($restaurant);
        $this->em->flush();

        self::assertTrue(true, 'Der Löschvorgang lief ohne Fehler durch.');
    }
}
