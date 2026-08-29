<?php

declare(strict_types=1);

namespace App\Tests\Integration\Comparison;

use App\Comparison\ComparisonFigures;
use App\Entity\Restaurant;
use App\Open\OpenStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Die eigenen Zahlen der Vergleichsseiten (Feature 03, AK-16 bis AK-18).
 *
 * Geschrieben in der QA: Ohne diesen Lauf wäre AK-17 („die Zahl folgt der
 * Datenlage") einmal von Hand geprüft und danach nie wieder. Genau dort entsteht
 * die Zahl, die auf einer öffentlichen Seite gegen „250 Millionen Orte" steht.
 */
final class ComparisonFiguresTest extends KernelTestCase
{
    /** AK-16: dieselbe Quelle wie die Transparenzseite — keine zweite Rechnung. */
    public function testDieZahlenStammenAusDerselbenQuelleWieDieTransparenzseite(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $stats = $container->get(OpenStatsService::class)->platform();
        $figures = $container->get(ComparisonFigures::class)->all();

        self::assertSame($stats['restaurants'], $figures['restaurants']);
        self::assertSame($stats['verified'], $figures['verified']);
        self::assertSame($stats['communesCovered'], $figures['communesCovered']);
    }

    /**
     * AK-17: Kommt ein Restaurant dazu, steigt die Zahl — ohne dass jemand eine
     * Datei ändert.
     *
     * Der Zwischenspeicher wird hier ausdrücklich verworfen; im Betrieb erledigt
     * das der Ablauf nach einer Stunde.
     */
    public function testEinNeuesRestaurantErhoehtDieZahl(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $stats = $container->get(OpenStatsService::class);
        $figures = $container->get(ComparisonFigures::class);
        $em = $container->get(EntityManagerInterface::class);

        $stats->invalidate();
        $vorher = $figures->all()['restaurants'];

        $restaurant = new Restaurant();
        $restaurant->setName('QA-Testeintrag AK-17');
        $restaurant->setCity('Strassen');
        $em->persist($restaurant);
        $em->flush();

        $stats->invalidate();
        $nachher = $figures->all()['restaurants'];

        self::assertSame($vorher + 1, $nachher, 'Die veröffentlichte Zahl folgt der Datenlage nicht.');
    }

    /**
     * AK-18: Der zweite Aufruf rechnet nicht neu.
     *
     * Nachgewiesen über die Identität des Ergebnisses bei zwischenzeitlich
     * verändertem Bestand: Solange der Zwischenspeicher steht, bleibt die Zahl —
     * genau das ist der Schutz davor, dass jeder Seitenaufruf den gesamten
     * Bestand lädt.
     */
    public function testDerZweiteAufrufLiestAusDemZwischenspeicher(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $stats = $container->get(OpenStatsService::class);
        $figures = $container->get(ComparisonFigures::class);
        $em = $container->get(EntityManagerInterface::class);

        $stats->invalidate();
        $erst = $figures->all()['restaurants'];

        $restaurant = new Restaurant();
        $restaurant->setName('QA-Testeintrag AK-18');
        $restaurant->setCity('Strassen');
        $em->persist($restaurant);
        $em->flush();

        self::assertSame($erst, $figures->all()['restaurants'], 'Der zweite Aufruf hat neu gerechnet.');
    }

    /** EC-02: Auch bei null Restaurants entsteht eine Zahl, keine Division durch null. */
    public function testKeinBestandErgibtNullUndKeinenFehler(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\Restaurant r')->execute();

        $container->get(OpenStatsService::class)->invalidate();
        $figures = $container->get(ComparisonFigures::class)->all();

        self::assertSame(0, $figures['restaurants']);
        self::assertSame(0, $figures['verified']);
        self::assertIsNumeric($figures['communesCovered']);
    }
}
