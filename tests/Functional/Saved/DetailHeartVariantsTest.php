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

    /**
     * BF-218 (AK-49): Außen um den Knopf lag der Fokusrahmen auf dem Grund — im Kopf auf dem Verlauf
     * (1,0–1,3 : 1) bzw. über dem Titelfoto (bis 2,5 : 1), in der Karte über einem Titelfoto mittlerer
     * Helligkeit (1,3 : 1). Der Rahmen liegt jetzt **innen auf der eigenen weißen Fläche** des Knopfs
     * (gray-900, gemessen 13,4–13,8 : 1 auf jedem Grund) — in jeder Lage dieselbe Klasse, damit auch
     * der Turbo-Stream, der den Knopf neu rendert, nichts über die Lage wissen muss.
     * Gemessen wird im Browser; dieser Fall hält die Klassen fest.
     */
    public function testBf218FokusrahmenLiegtInJederLageInnenAufDerWeissenFlaeche(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $ohneBild = (new Restaurant())->setName('Fokus ohne Bild')->setCity('Luxembourg');
        $mitBild = (new Restaurant())->setName('Fokus mit Bild')->setCity('Luxembourg');
        $em->persist($ohneBild);
        $em->persist($mitBild);
        $em->flush();
        $em->persist((new RestaurantImage())->setFilename('titelbild-fokus.jpg')->setRestaurant($mitBild)->setSortOrder(1));
        $em->flush();
        $em->clear();

        $knoepfe = [];
        foreach ([$ohneBild, $mitBild] as $lokal) {
            $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
            $knoepfe['Kopf, '.$lokal->getName()] = $seite->filter('#favorit-knopf-'.$lokal->getId());
        }
        $suche = $client->request('GET', self::LOCALE.'/restaurants?city=Luxembourg&sort=newest');
        $knoepfe['Karte, Fokus mit Bild'] = $suche->filter('#favorit-knopf-'.$mitBild->getId());

        foreach ($knoepfe as $wo => $knopf) {
            self::assertCount(1, $knopf, $wo);
            $klassen = explode(' ', (string) $knopf->attr('class'));
            self::assertContains('focus:outline-2', $klassen, $wo);
            self::assertContains('focus:-outline-offset-4', $klassen, $wo);
            self::assertContains('focus:outline-gray-900', $klassen, $wo);
            self::assertContains('bg-white', $klassen, $wo.': der Rahmen braucht die eigene weiße Fläche');
            self::assertNotContains('focus:outline-offset-2', $klassen, $wo);
        }
    }
}
