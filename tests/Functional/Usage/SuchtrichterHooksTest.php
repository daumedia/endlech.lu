<?php

namespace App\Tests\Functional\Usage;

use App\Entity\Restaurant;
use App\Tests\AbstractWebTestCase;
use App\Usage\UsageEventCatalogue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/** Feature 11 · Suchtrichter: Filterformular und Kontaktwege tragen den Auslöser (AK-11 bis AK-14). */
final class SuchtrichterHooksTest extends AbstractWebTestCase
{
    public function testFilterformularTraegtDenAuslöser(): void
    {
        $client = static::createClient();
        $formular = $client->request('GET', self::LOCALE.'/restaurants')->filter('form[data-controller="usage-event"]');

        self::assertCount(1, $formular);
        self::assertSame('submit->usage-event#filter', $formular->attr('data-action'));
        self::assertSame(UsageEventCatalogue::FILTER_APPLIED, $formular->attr('data-usage-event-name-value'));
        // Der Auslöser sammelt selbst; die Vorlage gibt keine Daten mit.
        self::assertNull($formular->attr('data-usage-event-data-value'));
    }

    /**
     * AK-12 · Über alle Detailseiten: Jeder Telefon- und E-Mail-Link trägt den Auslöser, jede
     * Kontaktweg-Angabe ist eine erlaubte Art — und keine enthält Nummer, Adresse oder Ziel.
     */
    public function testJederKontaktwegMeldetNurSeineArt(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $ids = array_map(
            static fn (Restaurant $r): int => (int) $r->getId(),
            $client->getContainer()->get(EntityManagerInterface::class)->getRepository(Restaurant::class)->findAll(),
        );
        self::assertNotSame([], $ids);

        $katalog = new UsageEventCatalogue();
        $gesehen = [];

        foreach ($ids as $id) {
            $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$id);
            self::assertResponseIsSuccessful();

            $seite->filter('a[href^="tel:"], a[href^="mailto:"]')->each(static function (Crawler $link): void {
                // Die Adressen der Plattform selbst (Fußzeile, Meldehinweis) sind kein Kontaktweg eines Restaurants.
                if (str_ends_with((string) $link->attr('href'), '@endlech.lu')) {
                    return;
                }
                self::assertSame('usage-event', $link->attr('data-controller'), 'Kontaktweg ohne Auslöser: '.$link->attr('href'));
            });

            $seite->filter('a[data-usage-event-name-value="kontaktweg_genutzt"]')->each(static function (Crawler $link) use ($katalog, &$gesehen): void {
                $roh = (string) $link->attr('data-usage-event-data-value');
                $daten = json_decode($roh, true);

                self::assertIsArray($daten);
                self::assertTrue($katalog->isAllowed(UsageEventCatalogue::CONTACT_USED, $daten), $roh);
                self::assertSame('click->usage-event#track', $link->attr('data-action'));

                // Nichts aus dem Ziel des Links: keine Adresse, keine URL, keine Telefonnummer. Die Werte
                // selbst sind durch den Katalog auf feste Arten und Plattformen beschränkt.
                self::assertDoesNotMatchRegularExpression('#@|://|\d{3}#', $roh);

                $gesehen[$daten['art']] = true;
            });
        }

        // Die Fixtures decken diese Arten ab; fehlt eine, ist der Auslöser dort vergessen worden.
        foreach (['telefon', 'email', 'website', 'instagram', 'facebook', 'bestellweg'] as $art) {
            self::assertArrayHasKey($art, $gesehen, 'Kein Kontaktweg der Art '.$art.' mit Auslöser gefunden.');
        }
    }
}
