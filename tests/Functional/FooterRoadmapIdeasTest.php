<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 16 · T05: Die Fußzeile führt mit genau einem Eintrag in den Bereich
 * „Roadmap & Ideen" — in der Gruppe „Mitmachen".
 *
 * Löst den Fußzeilenteil von 06/AK-02, 06/AK-80 und 07/AK-02 ab: Vorher standen
 * „Feedback & Ideen" unter „Mitmachen" und „Roadmap" + „Changelog" unter „Über Endlech".
 */
final class FooterRoadmapIdeasTest extends AbstractWebTestCase
{
    /** @return list<Crawler> */
    private function verweiseInDenBereich(Crawler $fusszeile): array
    {
        $treffer = [];
        $fusszeile->filter('a')->each(function (Crawler $a) use (&$treffer): void {
            $href = (string) $a->attr('href');
            if (preg_match('#^/[a-z]{2}/(roadmap|changelog|community/ideen)(/|$|\?)#', $href)) {
                $treffer[] = $a;
            }
        });

        return $treffer;
    }

    /** AK-01, AK-03: Genau ein Eintrag, in „Mitmachen", im selben Fenster, auf den Reiter Roadmap. */
    #[DataProvider('seiten')]
    public function testAk01EinEintragInMitmachen(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . $pfad);
        self::assertResponseIsSuccessful();

        $fusszeile = $crawler->filter('footer');
        $verweise = $this->verweiseInDenBereich($fusszeile);
        self::assertCount(1, $verweise, 'Genau ein Verweis in den Bereich.');

        $verweis = $verweise[0];
        self::assertSame(self::LOCALE . '/roadmap', $verweis->attr('href'));
        self::assertSame('Roadmap & Ideen', trim($verweis->text()));
        self::assertNull($verweis->attr('target'), 'Kein neues Fenster (AK-03).');

        $gruppe = $verweis->closest('div');
        self::assertNotNull($gruppe);
        self::assertSame('Mitmachen', trim($gruppe->filter('h2')->first()->text()));
    }

    /** AK-02: Die alten Einträge sind weg — in keiner Gruppe. */
    public function testAk02AlteEintraegeSindWeg(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/restaurants');
        $texte = $crawler->filter('footer a')->each(static fn (Crawler $a): string => trim($a->text()));

        foreach (['Roadmap', 'Changelog', 'Feedback & Ideen'] as $alt) {
            self::assertNotContains($alt, $texte, sprintf('„%s" darf nicht mehr in der Fußzeile stehen.', $alt));
        }
    }

    /** @return iterable<string, array{0: string}> */
    public static function seiten(): iterable
    {
        yield 'Startseite der Suche' => ['/restaurants'];
        yield 'Über uns' => ['/about'];
        yield 'Roadmap' => ['/roadmap'];
        yield 'Ideen' => ['/roadmap/ideen'];
    }
}
