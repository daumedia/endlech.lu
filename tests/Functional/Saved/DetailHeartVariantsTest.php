<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Feature 17, T20 — AK-02: Das Herz steht beim Namen in **beiden** Kopfvarianten der
 * Detailseite (mit Titelbild über dem Foto, ohne Titelbild im Farbband). Die übrigen
 * Prüfläufe treffen nur die Variante ohne Bild, weil die Fixtures keine Fotos tragen.
 */
final class DetailHeartVariantsTest extends AbstractWebTestCase
{
    public function testAk02HerzNebenDemNamenMitUndOhneTitelbild(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $ohneBild = (new Restaurant())->setName('Ohne Bild')->setCity('Luxembourg');
        $mitBild = (new Restaurant())->setName('Mit Bild')->setCity('Luxembourg');
        $em->persist($ohneBild);
        $em->persist($mitBild);
        $em->flush();
        $bild = (new RestaurantImage())->setFilename('titelbild-test.jpg')->setRestaurant($mitBild)->setSortOrder(1);
        $em->persist($bild);
        $em->flush();
        $em->clear();

        foreach ([$ohneBild, $mitBild] as $lokal) {
            $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
            self::assertResponseIsSuccessful();

            $kopf = $seite->filter('h1')->closest('div');
            self::assertNotNull($kopf);
            self::assertCount(1, $kopf->filter('form#favorit-'.$lokal->getId()), $lokal->getName());
            self::assertSame($lokal->getName().' merken', trim($kopf->filter('#favorit-knopf-'.$lokal->getId())->text()));
        }

        self::assertCount(1, $client->request('GET', self::LOCALE.'/restaurants/'.$mitBild->getId())->filter('img[src="/uploads/restaurants/titelbild-test.jpg"]'), 'Die Variante mit Titelbild wurde gerendert.');
    }
}
