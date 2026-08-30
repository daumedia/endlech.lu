<?php

declare(strict_types=1);

namespace App\Tests\Integration\Press;

use App\Entity\Restaurant;
use App\Open\OpenStatsService;
use App\Press\PressFacts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Die Zahlen des Faktenblatts (Feature 05, AK-12 bis AK-14).
 *
 * Eine falsche Zahl auf der Transparenzseite ist ein Fehler; eine falsche Zahl
 * in einem Zeitungsartikel ist nicht mehr einzufangen. Deshalb wird hier
 * nachgewiesen, dass beide aus derselben Quelle kommen — und nicht nur einmal
 * von Hand verglichen wurden.
 */
final class PressFactsTest extends KernelTestCase
{
    /**
     * ⚠ `PressFacts` wird hier von Hand erzeugt und nicht aus dem Container
     * geholt.
     *
     * Der Grund ist keine Vorliebe: Solange nur der Pressecontroller den Dienst
     * benutzt, ist er im Testcontainer nicht auflösbar — Symfony inlined einen
     * Dienst mit genau einem Konsumenten. Ein Prüflauf, der davon abhängt, wird
     * in dem Moment rot, in dem jemand den letzten Aufrufer umbaut, und die
     * Meldung („service has been removed or inlined") zeigt dann auf den Test
     * statt auf die Ursache. `OpenStatsService` hat mehrere Aufrufer und bleibt.
     */
    private static function facts(): PressFacts
    {
        return new PressFacts(static::getContainer()->get(OpenStatsService::class));
    }

    /** AK-12: dieselbe Quelle wie /open und /open.json. */
    public function testDieZahlenStammenAusDerselbenQuelleWieDieTransparenzseite(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $stats = $container->get(OpenStatsService::class)->platform();
        $facts = self::facts()->all();

        self::assertSame((int) $stats['restaurants'], $facts['restaurants']);
        self::assertSame((int) $stats['verified'], $facts['verified']);
        self::assertSame((int) $stats['communesCovered'], $facts['communesCovered']);
        self::assertSame((int) $stats['totalCommunes'], $facts['totalCommunes']);
    }

    /** AK-13: Kommt ein Lokal dazu, steigt die Zahl — ohne dass jemand eine Datei ändert. */
    public function testEinNeuesRestaurantErhoehtDieZahl(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $stats = $container->get(OpenStatsService::class);
        $facts = self::facts();
        $em = $container->get(EntityManagerInterface::class);

        $stats->invalidate();
        $vorher = $facts->all()['restaurants'];

        $restaurant = new Restaurant();
        $restaurant->setName('QA-Testeintrag Presse AK-13');
        $restaurant->setCity('Strassen');
        $em->persist($restaurant);
        $em->flush();

        $stats->invalidate();

        self::assertSame($vorher + 1, $facts->all()['restaurants'], 'Die veröffentlichte Zahl folgt der Datenlage nicht.');
    }

    /**
     * AK-14: Der zweite Aufruf rechnet nicht neu.
     *
     * Nachgewiesen über die Identität des Ergebnisses bei zwischenzeitlich
     * verändertem Bestand: Solange der Zwischenspeicher steht, bleibt die Zahl.
     * Genau das ist der Schutz davor, dass jeder Seitenaufruf den gesamten
     * Bestand lädt — und der Grund, warum hier kein Rate Limit nötig ist.
     */
    public function testDerZweiteAufrufLiestAusDemZwischenspeicher(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $stats = $container->get(OpenStatsService::class);
        $facts = self::facts();
        $em = $container->get(EntityManagerInterface::class);

        $stats->invalidate();
        $erst = $facts->all()['restaurants'];

        $restaurant = new Restaurant();
        $restaurant->setName('QA-Testeintrag Presse AK-14');
        $restaurant->setCity('Strassen');
        $em->persist($restaurant);
        $em->flush();

        self::assertSame($erst, $facts->all()['restaurants'], 'Der zweite Aufruf hat neu gerechnet.');
    }

    /** EC-02: Ohne Bestand steht dort 0 — keine Division durch null, kein leeres Feld. */
    public function testKeinBestandErgibtNullUndKeinenFehler(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\Restaurant r')->execute();

        $container->get(OpenStatsService::class)->invalidate();
        $facts = self::facts()->all();

        self::assertSame(0, $facts['restaurants']);
        self::assertSame(0, $facts['verified']);
        self::assertSame(0, $facts['communesCovered']);
        self::assertGreaterThan(0, $facts['totalCommunes'], 'Die Gesamtzahl der Gemeinden ist eine Konstante, kein Messwert.');
    }
}
