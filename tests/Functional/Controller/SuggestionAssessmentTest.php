<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Restaurant;
use App\Entity\RestaurantSuggestion;
use App\Enum\TriState;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * BF-49: „Weiß nicht" wird bei der Genehmigung nicht mehr stillschweigend zu
 * „Nein".
 *
 * `Restaurant` speichert die Merkmale als `bool` — dort ist `false` zweierlei
 * zugleich. Die Liste `assessedFeatures` hält jetzt fest, wonach der Einreicher
 * tatsächlich gesehen hat: `YES` und `NO` sind Auskünfte, `UNKNOWN` ist keine.
 */
final class SuggestionAssessmentTest extends AbstractWebTestCase
{
    private function genehmigen(object $client, RestaurantSuggestion $suggestion): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/admin/vorschlaege/'.$suggestion->getId());
        $client->submit($this->formByAction($crawler, '/genehmigen'));
    }

    private function vorschlag(EntityManagerInterface $em, TriState $antwort): RestaurantSuggestion
    {
        $suggestion = new RestaurantSuggestion();
        $suggestion->setName('Erhebung '.uniqid());
        $suggestion->setCity('Wiltz');
        $suggestion->setCuisine('Test');
        $suggestion->setIsWheelchairAccessible($antwort);
        $suggestion->setHasAccessibleToilet($antwort);
        $suggestion->setAllowsAssistanceDogs($antwort);
        $suggestion->setHasBrightLighting($antwort);
        $suggestion->setHasChangingTable($antwort);
        $suggestion->setHasDisabledParking($antwort);

        $em->persist($suggestion);
        $em->flush();

        return $suggestion;
    }

    /**
     * Zwölfmal „weiß nicht" ergibt ein Haus, das nicht bewertet ist — und keines,
     * bei dem angeblich nichts vorhanden wäre.
     */
    public function testWeissNichtErgibtKeineErhebung(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $suggestion = $this->vorschlag($em, TriState::UNKNOWN);
        $name = $suggestion->getName();

        $this->genehmigen($client, $suggestion);

        $em->clear();
        $restaurant = $em->getRepository(Restaurant::class)->findOneBy(['name' => $name]);
        self::assertNotNull($restaurant, 'Der Vorschlag wurde nicht übernommen.');
        self::assertSame([], $restaurant->getAssessedFeatures());
        self::assertFalse($restaurant->isAssessed(), 'Ein Haus voller „weiß nicht" gilt als bewertet.');
    }

    /**
     * Ein „nein" ist dagegen sehr wohl eine Auskunft — wer nein sagt, hat
     * hingesehen.
     */
    public function testNeinIstEineErhebung(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $suggestion = $this->vorschlag($em, TriState::NO);
        $name = $suggestion->getName();

        $this->genehmigen($client, $suggestion);

        $em->clear();
        $restaurant = $em->getRepository(Restaurant::class)->findOneBy(['name' => $name]);
        self::assertTrue($restaurant?->isAssessed());
        self::assertCount(6, $restaurant->getAssessedFeatures());
    }

    /**
     * Die Maße zählen als erhoben, sobald eine Zahl dasteht (BF-56).
     */
    public function testMasseZaehlenAlsErhebung(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $suggestion = $this->vorschlag($em, TriState::UNKNOWN);
        $suggestion->setDoorWidthCm(95);
        $em->flush();
        $name = $suggestion->getName();

        $this->genehmigen($client, $suggestion);

        $em->clear();
        $restaurant = $em->getRepository(Restaurant::class)->findOneBy(['name' => $name]);
        self::assertSame(['door_width'], $restaurant?->getAssessedFeatures());
        self::assertSame(95, $restaurant->getDoorWidthCm());
    }
}
