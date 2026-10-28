<?php

declare(strict_types=1);

namespace App\Tests\Integration\Seo;

use App\Repository\RestaurantRepository;
use App\Seo\SeoRegistry;
use App\Seo\SeoUrlBuilder;
use App\Seo\SitemapGenerator;
use App\Sponsor\SponsorDirectory;
use App\Tests\Support\Sponsor\InMemorySponsorSource;
use App\Tests\Support\Sponsor\TestSponsors;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;

/**
 * Feature 15 · AK-41 · Übersicht und Sponsorenseiten in der Sitemap.
 */
final class SitemapSponsorTest extends KernelTestCase
{
    /** @return list<string> */
    private function adressen(string $now = '2027-06-15 10:00:00'): array
    {
        self::bootKernel();
        $c = static::getContainer();

        $source = new InMemorySponsorSource();
        $source->replace(TestSponsors::all(), ['entfernt-sarl']);

        $xml = (new SitemapGenerator(
            new SeoRegistry(),
            new SeoUrlBuilder($c->get('router'), 'https://endlech.lu', ['lb', 'de', 'fr', 'en', 'pt']),
            $c->get(RestaurantRepository::class),
            new SponsorDirectory($source, new MockClock($now, SponsorDirectory::TIMEZONE)),
            $c->get('twig'),
            new ArrayAdapter(),
        ))->render();

        $d = new \DOMDocument();
        $d->loadXML($xml);

        return array_map(static fn (\DOMNode $n): string => (string) $n->textContent, iterator_to_array($d->getElementsByTagName('loc')));
    }

    public function testAk41UebersichtInAllenSprachen(): void
    {
        $adressen = $this->adressen();

        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            self::assertContains("https://endlech.lu/$sprache/sponsoren", $adressen);
        }
    }

    public function testAk41AktiveUndEhemaligeInAllenSprachen(): void
    {
        $adressen = $this->adressen();

        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            self::assertContains("https://endlech.lu/$sprache/sponsoren/acme", $adressen);
            self::assertContains("https://endlech.lu/$sprache/sponsoren/ecole", $adressen);
            self::assertContains("https://endlech.lu/$sprache/sponsoren/ehemals", $adressen);
        }
    }

    public function testAk41KuenftigeUndEntfernteFehlen(): void
    {
        $adressen = implode("\n", $this->adressen());

        self::assertStringNotContainsString('/sponsoren/morgen', $adressen);
        self::assertStringNotContainsString('/sponsoren/entfernt-sarl', $adressen);
    }

    public function testAk23AbDemBeginnStehtDerSponsorDrin(): void
    {
        self::assertStringContainsString('/de/sponsoren/morgen', implode("\n", $this->adressen('2027-06-16 00:00:00')));
    }
}
