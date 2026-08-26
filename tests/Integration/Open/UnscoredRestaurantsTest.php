<?php

declare(strict_types=1);

namespace App\Tests\Integration\Open;

use App\Entity\Restaurant;
use App\Open\OpenStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * BF-67: Ein Haus ohne jede Erhebung darf die veröffentlichte
 * Durchschnittspunktzahl nicht senken.
 *
 * Gemessen vor der Reparatur: Ein einziger leerer Eintrag hob `communesCovered`
 * von 8 auf 9 und senkte `averageScore` von 5,09 auf 4,67 — zwei Leitzahlen auf
 * derselben Seite, die in gegenläufige Richtungen zeigten. Wer die Kurven
 * nebeneinander sah, las „wächst und wird schlechter". Tatsächlich hieß es: noch
 * nicht gemessen.
 */
final class UnscoredRestaurantsTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private OpenStatsService $stats;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->stats = $container->get(OpenStatsService::class);
    }

    public function testUnbewertetesHausSenktDenDurchschnittNicht(): void
    {
        $vorher = $this->stats->computeAll()['platform'];

        $leer = (new Restaurant())->setName('Ohne Angaben '.uniqid())->setCity('Wiltz');
        self::assertSame([], $leer->getAssessedFeatures(), 'Ein neues Haus gilt als nicht bewertet.');
        $this->em->persist($leer);
        $this->em->flush();

        $nachher = $this->stats->computeAll()['platform'];

        self::assertSame(
            $vorher['averageScore'],
            $nachher['averageScore'],
            'Ein Haus ohne Angaben hat den Durchschnitt verändert.',
        );
        self::assertSame($vorher['restaurants'] + 1, $nachher['restaurants']);
        self::assertSame($vorher['unscoredRestaurants'] + 1, $nachher['unscoredRestaurants']);
        self::assertSame($vorher['scoredRestaurants'], $nachher['scoredRestaurants']);
    }

    /**
     * Ein bewertetes Haus wirkt sehr wohl — sonst hätte die Reparatur die Zahl
     * bloß eingefroren.
     */
    public function testBewertetesHausWirktAufDenDurchschnitt(): void
    {
        $vorher = $this->stats->computeAll()['platform'];

        $gut = (new Restaurant())
            ->setName('Alles erfasst '.uniqid())
            ->setCity('Wiltz')
            ->setAssessedFeatures(Restaurant::assessableFeatures())
            ->setIsWheelchairAccessible(true)
            ->setHasAccessibleToilet(true)
            ->setAllowsAssistanceDogs(true)
            ->setHasBrightLighting(true)
            ->setHasChangingTable(true)
            ->setHasDisabledParking(true)
            ->setDoorWidthCm(100)
            ->setTableSpacingCm(100);
        $this->em->persist($gut);
        $this->em->flush();

        $nachher = $this->stats->computeAll()['platform'];

        self::assertGreaterThan($vorher['averageScore'], $nachher['averageScore']);
        self::assertSame($vorher['scoredRestaurants'] + 1, $nachher['scoredRestaurants']);
        self::assertSame($vorher['unscoredRestaurants'], $nachher['unscoredRestaurants']);
    }

    /**
     * Die Punkteverteilung zählt nur bewertete Häuser — sonst wüchse die
     * Null-Säule mit jedem Eintrag, über den nichts bekannt ist.
     */
    public function testVerteilungEnthaeltNurBewerteteHaeuser(): void
    {
        $vorher = $this->stats->computeAll()['platform']['scoreDistribution'];

        $this->em->persist((new Restaurant())->setName('Leer '.uniqid())->setCity('Wiltz'));
        $this->em->flush();

        self::assertSame($vorher, $this->stats->computeAll()['platform']['scoreDistribution']);
    }
}
