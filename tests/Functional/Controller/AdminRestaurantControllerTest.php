<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Component\PropertyAccess\Exception\InvalidTypeException;
use Doctrine\ORM\EntityManagerInterface;

final class AdminRestaurantControllerTest extends AbstractWebTestCase
{
    private function em(object $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function restaurants(object $client): RestaurantRepository
    {
        return $client->getContainer()->get(RestaurantRepository::class);
    }

    private function createRestaurant(object $client, string $name): Restaurant
    {
        $restaurant = (new Restaurant())->setName($name)->setCity('Luxembourg');
        $em = $this->em($client);
        $em->persist($restaurant);
        $em->flush();

        return $restaurant;
    }

    private function createImage(object $client, Restaurant $restaurant, int $sortOrder): RestaurantImage
    {
        $image = (new RestaurantImage())->setFilename('fake-'.$sortOrder.'.jpg')->setSortOrder($sortOrder);
        $image->setRestaurant($restaurant);
        $em = $this->em($client);
        $em->persist($image);
        $em->flush();

        return $image;
    }

    public function testIndexForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', self::LOCALE.'/admin/restaurants');

        self::assertResponseStatusCodeSame(403);
    }

    public function testIndexLoadsForAdmin(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $client->request('GET', self::LOCALE.'/admin/restaurants');

        self::assertResponseIsSuccessful();
    }

    public function testNewFormLoads(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $client->request('GET', self::LOCALE.'/admin/restaurants/neu');

        self::assertResponseIsSuccessful();
    }

    public function testCreateRestaurant(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $name = 'Admin Neu '.uniqid();
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/neu');
        $client->submit($this->formWithField($crawler, 'restaurant[name]', [
            'restaurant[name]' => $name,
            'restaurant[city]' => 'Diekirch',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/admin/restaurants');
        self::assertNotNull($this->restaurants($client)->findOneBy(['name' => $name]));
    }

    public function testEditTogglesVerification(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $restaurant = $this->createRestaurant($client, 'Edit Verify '.uniqid());
        $id = $restaurant->getId();

        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/'.$id.'/bearbeiten');
        $form = $this->formWithField($crawler, 'restaurant[name]');
        $form['restaurant[isVerified]']->tick();
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/admin/restaurants');

        $this->em($client)->clear();
        $reloaded = $this->restaurants($client)->find($id);
        self::assertTrue($reloaded->isVerified());
        self::assertNotNull($reloaded->getVerifiedAt());
        self::assertNotNull($reloaded->getVerifiedBy());
    }

    public function testToggleVerifiedWithValidCsrf(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $restaurant = $this->createRestaurant($client, 'Toggle '.uniqid());
        $id = $restaurant->getId();
        $wasVerified = $restaurant->isVerified();

        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants');
        $client->submit($this->formByAction($crawler, '/'.$id.'/verifizieren'));

        self::assertResponseRedirects(self::LOCALE.'/admin/restaurants');

        $this->em($client)->clear();
        self::assertNotSame($wasVerified, $this->restaurants($client)->find($id)->isVerified());
    }

    public function testToggleVerifiedWithInvalidCsrfDoesNotChange(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $restaurant = $this->createRestaurant($client, 'Toggle Invalid '.uniqid());
        $id = $restaurant->getId();

        $client->request('POST', self::LOCALE.'/admin/restaurants/'.$id.'/verifizieren', ['_token' => 'ungueltig']);

        self::assertResponseRedirects(self::LOCALE.'/admin/restaurants');
        $this->em($client)->clear();
        self::assertFalse($this->restaurants($client)->find($id)->isVerified());
    }

    public function testDeleteImage(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $restaurant = $this->createRestaurant($client, 'Bild Lösch '.uniqid());
        $image = $this->createImage($client, $restaurant, 1);
        $imageId = $image->getId();
        $restaurantId = $restaurant->getId();

        // EM leeren, damit der Request das Restaurant samt Bildern frisch aus der DB lädt.
        $this->em($client)->clear();
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/'.$restaurantId.'/bearbeiten');
        $client->submit($this->formByAction($crawler, '/fotos/'.$imageId.'/loeschen'));

        self::assertResponseRedirects();
        self::assertNull($client->getContainer()->get('doctrine')->getManager()->getRepository(RestaurantImage::class)->find($imageId));
    }

    public function testSortImagesWithInvalidCsrfReturns403(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $restaurant = $this->createRestaurant($client, 'Sort CSRF '.uniqid());

        $client->request(
            'POST',
            self::LOCALE.'/admin/restaurants/'.$restaurant->getId().'/fotos/sortieren',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['_token' => 'ungueltig', 'imageIds' => []]),
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testSortImagesReordersWithValidToken(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $restaurant = $this->createRestaurant($client, 'Sort OK '.uniqid());
        $first = $this->createImage($client, $restaurant, 0);
        $second = $this->createImage($client, $restaurant, 1);
        $restaurantId = $restaurant->getId();

        $this->em($client)->clear();
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/'.$restaurantId.'/bearbeiten');
        $token = $this->csrfTokenFrom($crawler, '[data-image-sort-token-value]', 'data-image-sort-token-value');

        // Reihenfolge umkehren: second -> sortOrder 0, first -> sortOrder 1.
        $client->request(
            'POST',
            self::LOCALE.'/admin/restaurants/'.$restaurant->getId().'/fotos/sortieren',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['_token' => $token, 'imageIds' => [$second->getId(), $first->getId()]]),
        );

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());

        $repo = $client->getContainer()->get('doctrine')->getManager()->getRepository(RestaurantImage::class);
        self::assertSame(1, $repo->find($first->getId())->getSortOrder());
        self::assertSame(0, $repo->find($second->getId())->getSortOrder());
    }

    public function testSortImagesRejectsForeignImageWith400(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $owner = $this->createRestaurant($client, 'Eigner '.uniqid());
        // Eigenes Bild, damit der Sortier-Container (inkl. Token) überhaupt gerendert wird.
        $this->createImage($client, $owner, 0);
        $foreignImage = $this->createImage($client, $this->createRestaurant($client, 'Fremd '.uniqid()), 0);
        $ownerId = $owner->getId();
        $foreignImageId = $foreignImage->getId();

        $this->em($client)->clear();
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/'.$ownerId.'/bearbeiten');
        $token = $this->csrfTokenFrom($crawler, '[data-image-sort-token-value]', 'data-image-sort-token-value');

        $client->request(
            'POST',
            self::LOCALE.'/admin/restaurants/'.$ownerId.'/fotos/sortieren',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['_token' => $token, 'imageIds' => [$foreignImageId]]),
        );

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * AK-03 / BF-51: Ein leeres Pflichtfeld endet in einem **500er**, nicht in einem 422.
     *
     * Ursache: `Restaurant::setName(string $name)` nimmt kein `null`. Das Formular ist
     * an die Entity gebunden, `handleRequest()` schreibt **vor** der Validierung — der
     * TypeError fliegt, bevor die `NotBlank`-Constraints je zum Zug kommen.
     *
     * Der Test hält den Befund fest und schlägt fehl, sobald er behoben ist.
     */
    public function testAk03LeeresPflichtfeldEndetImServerfehler(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/neu');
        $form = $this->formWithField($crawler, 'restaurant[name]');
        $form['restaurant[name]'] = '';
        $form['restaurant[city]'] = '';

        // Der PropertyAccessor verpackt den TypeError aus dem Setter.
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "null" given at property path "name"');
        $client->submit($form);
    }

    /**
     * AK-06: Ein Speichern ohne Änderung am Verifizierungszustand darf `verifiedAt`
     * und `verifiedBy` nicht anfassen — sonst wanderte das Prüfdatum bei jeder
     * Textkorrektur auf heute.
     */
    public function testAk06UnveraenderteVerifizierungBleibtUnangetastet(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $restaurant = $this->createRestaurant($client, 'AK06 '.uniqid());
        $id = $restaurant->getId();

        // erst verifizieren
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/'.$id.'/bearbeiten');
        $form = $this->formWithField($crawler, 'restaurant[name]');
        $form['restaurant[isVerified]']->tick();
        $client->submit($form);

        $this->em($client)->clear();
        $vorher = $this->restaurants($client)->find($id)->getVerifiedAt();
        self::assertNotNull($vorher);

        // dann nur den Namen ändern, Haken unberührt lassen
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants/'.$id.'/bearbeiten');
        $form = $this->formWithField($crawler, 'restaurant[name]');
        $form['restaurant[name]'] = 'AK06 umbenannt';
        $client->submit($form);

        $this->em($client)->clear();
        $nachher = $this->restaurants($client)->find($id);

        self::assertSame('AK06 umbenannt', $nachher->getName());
        self::assertEquals($vorher, $nachher->getVerifiedAt(), 'Das Prüfdatum darf sich nicht verschieben.');
        self::assertNotNull($nachher->getVerifiedBy());
    }
}
