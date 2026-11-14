<?php

declare(strict_types=1);

namespace App\Tests\Functional\Accessibility;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature 02 · Wächter für Kontrastbefunde aus dem Fehlerauftrag 17 (QA vom 2026-11-14).
 *
 * Gemessen wird ein Kontrast im Browser an den Pixeln (`features/17-favoriten-und-listen/tasks.md`,
 * Abschlussbericht des Fehlerauftrags) — PHPUnit sieht keine Farben. Diese Läufe halten nur fest,
 * dass die gemessene Klasse nicht zurückkommt: Wer sie wieder einsetzt, sieht hier Rot und muss
 * neu messen.
 */
final class KontrastKlassenTest extends AbstractWebTestCase
{
    /** Das alte Glas-Etikett: Weiß auf `bg-white/20` über dem Verlauf, 3,7–3,98 : 1 (BF-203). */
    private const string HELLES_GLAS = 'bg-white/20 text-white text-sm font-semibold';

    private const string DUNKLES_GLAS = 'bg-black/20 text-white text-sm font-semibold';

    /** @return iterable<string, array{string, int}> Pfad, Zahl der Etiketten auf der Seite */
    public static function seitenMitGlasEtikett(): iterable
    {
        yield 'Startseite' => ['/', 1];
        yield '/open' => ['/open', 2];
        yield '/presse' => ['/presse', 1];
        yield '/vergleich' => ['/vergleich', 1];
        yield '/partner' => ['/partner', 2];
        yield '/organisationen (mit Integritätsblock)' => ['/organisationen', 2];
        yield '/organisationen/gemeinden (Integritätsblock)' => ['/organisationen/gemeinden', 1];
        yield '/sponsoren' => ['/sponsoren', 1];
        yield '/app' => ['/app', 1];
    }

    /**
     * BF-203 (Rest): Das Glas-Etikett liegt in allen neun Vorlagen auf dunklem Glas wie im
     * Bereichskopf von Feature 16 (`RoadmapAreaTest::testBf203EtikettAufDunklemGlas`).
     */
    #[DataProvider('seitenMitGlasEtikett')]
    public function testBf203GlasEtikettLiegtAufDunklemGlas(string $pfad, int $anzahl): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.$pfad);
        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();

        self::assertStringNotContainsString(self::HELLES_GLAS, $html);
        self::assertSame($anzahl, substr_count($html, self::DUNKLES_GLAS));
    }

    /**
     * BF-216: Die kleine Schrift der Detailseite trägt keine der gemessenen Farben unter 4,5 : 1 mehr —
     * Merkmalskacheln `text-green-600` auf `green-50` (3,07 : 1) und `text-amber-600` auf `amber-50`
     * (3,08 : 1, Maß unter dem Mindestwert), „(heute)“ `text-purple-500` auf `purple-50` (3,83 : 1),
     * „Keine Öffnungszeiten angegeben“ `text-gray-400` auf Weiß (2,6 : 1). Über alle Lokale der
     * Fixtures, weil jede Fassung (Merkmal da/nicht da, Maß zu eng, keine Zeiten) woanders steht.
     */
    public function testBf216KleineSchriftDerDetailseiteOhneZuHelleFarben(): void
    {
        $client = static::createClient();
        $ids = $client->getContainer()->get('doctrine')->getConnection()->fetchFirstColumn('SELECT id FROM restaurant ORDER BY id');
        self::assertNotEmpty($ids);
        $kachelnDa = $ohneZeiten = $zuEng = 0;

        foreach ($ids as $id) {
            $crawler = $client->request('GET', self::LOCALE.'/restaurants/'.$id);
            self::assertResponseIsSuccessful();
            $html = (string) $client->getResponse()->getContent();

            foreach (['text-green-600', 'text-amber-600', 'text-purple-500'] as $klasse) {
                self::assertStringNotContainsString($klasse, $html, 'Lokal '.$id);
            }
            $keineZeiten = $crawler->filterXPath('//p[normalize-space(.)="Keine Öffnungszeiten angegeben"]');
            if ($keineZeiten->count() > 0) {
                ++$ohneZeiten;
                self::assertStringNotContainsString('text-gray-400', (string) $keineZeiten->attr('class'), 'Lokal '.$id);
            }
            $kachelnDa += $crawler->filter('p.text-xs.text-green-800')->count();
            $zuEng += $crawler->filter('p.text-xs.text-amber-800')->count();
        }

        // Jede Fassung kommt in den Fixtures vor — sonst prüfte der Lauf ins Leere.
        self::assertGreaterThan(0, $kachelnDa, 'Merkmalskacheln „vorhanden“');
        self::assertGreaterThan(0, $zuEng, 'Maß unter dem Mindestwert');
        self::assertGreaterThan(0, $ohneZeiten, 'Lokal ohne Öffnungszeiten');
    }

    /**
     * Fehlerauftrag 17 (Folge von BF-206): Der Fokusrahmen des Titelfotos liegt außerhalb des Fotos,
     * oben am Seitenkopf und unten auf `bg-gray-50`. Seit die Fokusfarbe wirkt, wäre Weiß dort
     * unsichtbar (im Browser 1,22 : 1); cyan-700 maß 5,05 : 1 — wie vorher, als der Fallback galt.
     */
    public function testTitelfotoTraegtEinenFokusrahmenDerAufDemSeitengrundSichtbarIst(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $lokal = $em->getRepository(Restaurant::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($lokal);
        $foto = (new RestaurantImage())->setFilename('titelfoto-test.jpg')->setAltText('Eingang')->setRestaurant($lokal)
            ->setUploadedAt(new \DateTimeImmutable())->setSortOrder(-1);
        $lokal->getImages()->add($foto);
        $em->persist($foto);
        $em->flush();
        $em->clear();

        $crawler = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        self::assertResponseIsSuccessful();
        $link = $crawler->filter('a.glightbox[href$="/titelfoto-test.jpg"]')->first();
        self::assertCount(1, $link, 'Das Lokal hat ein Titelfoto.');

        self::assertStringContainsString('focus:outline-cyan-700', (string) $link->attr('class'));
        self::assertStringNotContainsString('focus:outline-white', (string) $link->attr('class'));
    }
}
