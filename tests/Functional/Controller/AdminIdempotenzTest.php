<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Restaurant;
use App\Entity\RestaurantSuggestion;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * BF-54: Zweimal genehmigen erzeugt nicht zwei Restaurants.
 *
 * `approve()` prüfte den Status nicht — ein Doppelklick auf den Knopf, oder die
 * Zurück-Taste des Browsers, legte einen zweiten Eintrag an. Beide mit
 * Erfolgsmeldung, und die Dublette landete unbemerkt in der öffentlichen Liste.
 */
final class AdminIdempotenzTest extends AbstractWebTestCase
{
    public function testZweimalGenehmigenErzeugtEinRestaurant(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $name = 'Idempotenz '.uniqid();

        $suggestion = new RestaurantSuggestion();
        $suggestion->setName($name);
        $suggestion->setCity('Wiltz');
        $suggestion->setCuisine('Test');
        $em->persist($suggestion);
        $em->flush();

        $id = $suggestion->getId();

        // Das Formular EINMAL holen und dreimal abschicken — genau das tut ein
        // Doppelklick, und genau das tut die Zurück-Taste des Browsers.
        $crawler = $client->request('GET', self::LOCALE.'/admin/vorschlaege/'.$id);
        $formular = $this->formByAction($crawler, '/genehmigen');

        for ($i = 0; $i < 3; ++$i) {
            $client->request(
                'POST',
                $formular->getUri(),
                $formular->getPhpValues(),
                [],
                ['HTTP_REFERER' => 'http://localhost'.self::LOCALE.'/admin/vorschlaege/'.$id],
            );
        }

        $treffer = $em->getRepository(Restaurant::class)->findBy(['name' => $name]);
        self::assertCount(1, $treffer, 'Mehrfaches Genehmigen hat mehrere Restaurants erzeugt.');
    }
}
