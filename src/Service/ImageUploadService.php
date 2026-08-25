<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Repository\RestaurantImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadService
{
    /**
     * Zulässige Bildtypen — dieselben wie beim Avatar (`ProfileType`).
     *
     * ⚠ BF-57: Vorher gab es keine Prüfung. Eine `.html` wurde als `text/html`
     * ausgeliefert und lief damit im Ursprung der Anwendung; dasselbe galt für
     * eine `.svg` mit `<script>`. Der Upload-Weg läuft nicht über ein Formular
     * (`$request->files` direkt), deshalb griff auch kein `File`-Constraint —
     * die Prüfung gehört in den Dienst, wo sie jeder Aufrufweg passiert.
     *
     * SVG ist bewusst NICHT dabei: Es ist ein XML-Format, das Skripte tragen
     * kann, und für Restaurantfotos gibt es keinen Grund dafür.
     *
     * @var array<string, string> MIME-Typ → Endung
     */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** ⚠ BF-58: Die Anwendung hatte keine eigene Grenze. */
    private const MAX_SIZE_BYTES = 4 * 1024 * 1024;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/restaurants')]
        private string $uploadDir,
        private EntityManagerInterface $em,
        private RestaurantImageRepository $imageRepo,
    ) {
    }

    /**
     * @throws UploadRejectedException wenn Typ oder Größe nicht zulässig sind
     */
    public function upload(UploadedFile $file, Restaurant $restaurant, string $altText = ''): RestaurantImage
    {
        $filename = uniqid('', true).'.'.$this->pruefeUndErmittleEndung($file);
        $file->move($this->uploadDir, $filename);

        $image = new RestaurantImage();
        $image->setFilename($filename);
        $image->setAltText($altText ?: $restaurant->getName());
        $image->setRestaurant($restaurant);
        $image->setUploadedAt(new \DateTimeImmutable());
        $image->setSortOrder($this->imageRepo->getNextSortOrder($restaurant));

        $this->em->persist($image);
        $this->em->flush();

        return $image;
    }

    /**
     * Prüft die Datei und liefert die Endung, die zum ECHTEN Typ gehört.
     *
     * Die Endung stammt aus der MIME-Erkennung, nicht aus dem übermittelten
     * Dateinamen: Wer eine `bild.jpg` hochlädt, die HTML enthält, bekommt keine
     * `.html` — und wer eine `skript.html` hochlädt, kommt gar nicht erst durch.
     */
    private function pruefeUndErmittleEndung(UploadedFile $file): string
    {
        $groesse = $file->getSize();
        if (false !== $groesse && $groesse > self::MAX_SIZE_BYTES) {
            throw new UploadRejectedException('flash.upload_too_large', [
                '%max%' => (int) (self::MAX_SIZE_BYTES / 1024 / 1024),
            ]);
        }

        $mime = (string) $file->getMimeType();
        if (!isset(self::ALLOWED_TYPES[$mime])) {
            throw new UploadRejectedException('flash.upload_wrong_type', [
                '%types%' => 'JPEG, PNG, WebP',
            ]);
        }

        return self::ALLOWED_TYPES[$mime];
    }

    public function delete(RestaurantImage $image): void
    {
        $restaurant = $image->getRestaurant();
        $path = $this->uploadDir.'/'.$image->getFilename();
        if (file_exists($path)) {
            unlink($path);
        }
        $this->em->remove($image);
        $this->em->flush();

        $this->reorderAfterDelete($restaurant);
    }

    public function reorderAfterDelete(Restaurant $restaurant): void
    {
        $images = $this->imageRepo->findBy(
            ['restaurant' => $restaurant],
            ['sortOrder' => 'ASC'],
        );
        foreach ($images as $index => $image) {
            $image->setSortOrder($index);
        }
        $this->em->flush();
    }
}
