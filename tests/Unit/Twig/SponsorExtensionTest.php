<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorDirectory;
use App\Sponsor\SponsorPeriod;
use App\Tests\Support\Sponsor\InMemorySponsorSource;
use App\Twig\SponsorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Twig\TwigFunction;

/**
 * Die Twig-Funktionen für Footer, Übersicht und Faktenblatt (Feature 15, T09).
 */
final class SponsorExtensionTest extends TestCase
{
    /** @param list<Sponsor> $sponsors */
    private static function call(array $sponsors, string $function, mixed ...$args): mixed
    {
        $source = new InMemorySponsorSource();
        $source->replace($sponsors);
        $extension = new SponsorExtension(new SponsorDirectory($source, new MockClock('2027-06-15 10:00:00', SponsorDirectory::TIMEZONE)));

        foreach ($extension->getFunctions() as $twigFunction) {
            \assert($twigFunction instanceof TwigFunction);
            if ($twigFunction->getName() === $function) {
                return ($twigFunction->getCallable())(...$args);
            }
        }

        self::fail("Twig-Funktion {$function} fehlt.");
    }

    private static function aktiv(string $slug, string $start = '2026-01-01'): Sponsor
    {
        return new Sponsor($slug, $slug, $slug.'.png', [new SponsorPeriod($start)]);
    }

    public function testAk06FooterHoechstensZwoelf(): void
    {
        $sponsoren = array_map(static fn (int $i): Sponsor => self::aktiv('s'.$i, \sprintf('2026-%02d-01', $i)), range(1, 12));
        $sponsoren[] = self::aktiv('s13', '2027-01-01');

        self::assertCount(12, self::call($sponsoren, 'sponsors_footer'));
        self::assertCount(13, self::call($sponsoren, 'sponsors_active'));
    }

    public function testAk32ZahlDerAktiven(): void
    {
        $sponsoren = [self::aktiv('a'), self::aktiv('b'), new Sponsor('c', 'c', 'c.png', [new SponsorPeriod('2025-01-01', '2025-12-31')])];

        self::assertSame(2, self::call($sponsoren, 'sponsors_active_count'));
        self::assertSame(0, self::call([], 'sponsors_active_count'));
    }

    public function testAk05GibtEsSponsoren(): void
    {
        self::assertFalse(self::call([], 'sponsors_exist'));
        self::assertTrue(self::call([new Sponsor('c', 'c', 'c.png', [new SponsorPeriod('2025-01-01', '2025-12-31')])], 'sponsors_exist'));
    }

    public function testEhemaligeUndAktivZustand(): void
    {
        $ehemals = new Sponsor('c', 'c', 'c.png', [new SponsorPeriod('2025-01-01', '2025-12-31')]);

        self::assertSame(['c'], array_map(static fn (Sponsor $s) => $s->slug, self::call([$ehemals, self::aktiv('a')], 'sponsors_former')));
        self::assertFalse(self::call([$ehemals], 'sponsor_is_active', $ehemals));
        self::assertTrue(self::call([self::aktiv('a')], 'sponsor_is_active', self::aktiv('a')));
    }
}
