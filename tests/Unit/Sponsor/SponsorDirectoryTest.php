<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorDirectory;
use App\Sponsor\SponsorPeriod;
use App\Tests\Support\Sponsor\InMemorySponsorSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Zustände, Reihenfolge und Auswahl der Sponsoren (Feature 15, T05).
 */
final class SponsorDirectoryTest extends TestCase
{
    /**
     * @param list<Sponsor> $sponsors
     * @param list<string>  $removed
     */
    private static function directory(string $now, array $sponsors, array $removed = []): SponsorDirectory
    {
        $source = new InMemorySponsorSource();
        $source->replace($sponsors, $removed);

        return new SponsorDirectory($source, new MockClock($now, SponsorDirectory::TIMEZONE));
    }

    private static function sponsor(string $slug, SponsorPeriod ...$periods): Sponsor
    {
        return new Sponsor($slug, ucfirst($slug), $slug.'.png', array_values($periods));
    }

    /** @param list<Sponsor> $sponsors */
    private static function slugs(array $sponsors): array
    {
        return array_map(static fn (Sponsor $s): string => $s->slug, $sponsors);
    }

    public function testHeuteIstDerKalendertagInLuxemburg(): void
    {
        // 23:30 UTC am 31. März ist in Luxemburg (Sommerzeit, UTC+2) schon der 1. April.
        $source = new InMemorySponsorSource();
        $directory = new SponsorDirectory($source, new MockClock('2027-03-31 23:30:00', 'UTC'));

        self::assertSame('2027-04-01', $directory->today());
    }

    /** @return iterable<string, array{string, bool, bool}> */
    public static function tageswechsel(): iterable
    {
        yield 'AK-24 am Endtag 23:59 noch aktiv' => ['2027-03-31 23:59:00', true, false];
        yield 'AK-24 am Folgetag 00:00 ehemalig' => ['2027-04-01 00:00:00', false, true];
        yield 'AK-24 am Folgetag 00:30 ehemalig' => ['2027-04-01 00:30:00', false, true];
    }

    #[DataProvider('tageswechsel')]
    public function testAk24EndeZaehltMitUndWechseltUmMitternacht(string $now, bool $aktiv, bool $ehemalig): void
    {
        $directory = self::directory($now, [self::sponsor('acme', new SponsorPeriod('2026-04-01', '2027-03-31'))]);

        self::assertSame($aktiv ? ['acme'] : [], self::slugs($directory->active()));
        self::assertSame($aktiv ? ['acme'] : [], self::slugs($directory->footer()));
        self::assertSame($ehemalig ? ['acme'] : [], self::slugs($directory->former()));
    }

    public function testEc02TageswechselInDerNachtDerZeitumstellung(): void
    {
        // In der Nacht auf den 28. März 2027 springt Luxemburg von 02:00 auf 03:00.
        $sponsor = self::sponsor('acme', new SponsorPeriod('2027-03-28'));

        self::assertSame([], self::slugs(self::directory('2027-03-27 23:59:59', [$sponsor])->active()));
        self::assertSame(['acme'], self::slugs(self::directory('2027-03-28 00:00:00', [$sponsor])->active()));
        self::assertSame(['acme'], self::slugs(self::directory('2027-03-28 03:00:00', [$sponsor])->active()));
    }

    public function testAk23KuenftigerSponsorIstUnsichtbarBisZumBeginn(): void
    {
        $sponsor = self::sponsor('morgen', new SponsorPeriod('2027-06-16'));
        $heute = self::directory('2027-06-15 10:00:00', [$sponsor]);

        self::assertSame([], $heute->active());
        self::assertSame([], $heute->former());
        self::assertSame([], $heute->visible());
        self::assertFalse($heute->hasAny());
        self::assertNull($heute->findVisible('morgen'));

        $morgen = self::directory('2027-06-16 00:00:00', [$sponsor]);
        self::assertSame(['morgen'], self::slugs($morgen->active()));
        self::assertSame('morgen', $morgen->findVisible('morgen')?->slug);
    }

    public function testEc01EinTagSponsoring(): void
    {
        $sponsor = self::sponsor('eintag', new SponsorPeriod('2027-06-15', '2027-06-15'));

        self::assertSame(['eintag'], self::slugs(self::directory('2027-06-15 12:00:00', [$sponsor])->active()));
        self::assertSame(['eintag'], self::slugs(self::directory('2027-06-16 12:00:00', [$sponsor])->former()));
    }

    public function testAk02ReihenfolgeNachBeginnUndGleichstandNachName(): void
    {
        $directory = self::directory('2027-06-15 10:00:00', [
            new Sponsor('zeta', 'Zeta', 'z.png', [new SponsorPeriod('2027-01-01')]),
            new Sponsor('ecole', 'École Ëmil', 'e.png', [new SponsorPeriod('2026-05-01')]),
            new Sponsor('alpha', 'alpha', 'a.png', [new SponsorPeriod('2026-05-01')]),
            new Sponsor('beta', 'Beta', 'b.png', [new SponsorPeriod('2025-01-01')]),
        ]);

        // EC-03: Gleichstand am 2026-05-01 nach gefaltetem Namen — „alpha“ vor „École“.
        self::assertSame(['beta', 'alpha', 'ecole', 'zeta'], self::slugs($directory->active()));
    }

    public function testAk25PauseZaehltMitDemLaufendenZeitraum(): void
    {
        $directory = self::directory('2027-06-15 10:00:00', [
            self::sponsor('rueckkehr', new SponsorPeriod('2024-01-01', '2024-12-31'), new SponsorPeriod('2027-03-01')),
            self::sponsor('treu', new SponsorPeriod('2026-01-01')),
        ]);

        // Rückkehrer zählt mit März 2027, nicht mit 2024 — der Treue steht vorn.
        self::assertSame(['treu', 'rueckkehr'], self::slugs($directory->active()));
        self::assertSame([], $directory->former());
    }

    public function testPauseOhneLaufendenZeitraumIstEhemalig(): void
    {
        $sponsor = self::sponsor('pause', new SponsorPeriod('2025-01-01', '2025-12-31'), new SponsorPeriod('2027-09-01'));
        $directory = self::directory('2027-06-15 10:00:00', [$sponsor]);

        self::assertSame([], $directory->active());
        self::assertSame(['pause'], self::slugs($directory->former()));
        self::assertFalse($directory->isActive($sponsor));
    }

    public function testAk15EhemaligeNachLetztemEndeJuengstesZuerst(): void
    {
        $directory = self::directory('2027-06-15 10:00:00', [
            self::sponsor('alt', new SponsorPeriod('2025-01-01', '2025-06-30')),
            self::sponsor('neu', new SponsorPeriod('2026-01-01', '2027-05-31')),
            self::sponsor('mitte', new SponsorPeriod('2025-01-01', '2026-02-28')),
        ]);

        self::assertSame(['neu', 'mitte', 'alt'], self::slugs($directory->former()));
    }

    public function testAk06Ec07FooterZeigtDieZwoelfAeltesten(): void
    {
        $sponsoren = [];
        for ($i = 13; $i >= 1; --$i) {
            $sponsoren[] = self::sponsor(\sprintf('s%02d', $i), new SponsorPeriod(\sprintf('2026-%02d-01', $i <= 12 ? $i : 12), null));
        }
        // s13 beginnt am selben Tag wie s12, steht alphabetisch aber dahinter.
        $directory = self::directory('2027-06-15 10:00:00', $sponsoren);

        self::assertCount(13, $directory->active());
        self::assertCount(12, $directory->footer());
        self::assertNotContains('s13', self::slugs($directory->footer()));
        self::assertSame('s01', $directory->footer()[0]->slug);
    }

    public function testAk05HasAnyBeiNurEhemaligen(): void
    {
        self::assertFalse(self::directory('2027-06-15 10:00:00', [])->hasAny());
        self::assertTrue(self::directory('2027-06-15 10:00:00', [self::sponsor('war', new SponsorPeriod('2025-01-01', '2025-12-31'))])->hasAny());
        self::assertTrue(self::directory('2027-06-15 10:00:00', [self::sponsor('ist', new SponsorPeriod('2025-01-01'))])->hasAny());
    }

    public function testSichtbarSindAktiveUndEhemalige(): void
    {
        $directory = self::directory('2027-06-15 10:00:00', [
            self::sponsor('ist', new SponsorPeriod('2026-01-01')),
            self::sponsor('war', new SponsorPeriod('2025-01-01', '2025-12-31')),
            self::sponsor('kommt', new SponsorPeriod('2028-01-01')),
        ]);

        self::assertSame(['ist', 'war'], self::slugs($directory->visible()));
        self::assertSame('war', $directory->findVisible('war')?->slug);
        self::assertNull($directory->findVisible('kommt'));
        self::assertNull($directory->findVisible('gibt-es-nicht'));
    }

    public function testAk12LaufenderZeitraumFuerSponsorSeit(): void
    {
        $sponsor = self::sponsor('rueckkehr', new SponsorPeriod('2024-01-01', '2024-12-31'), new SponsorPeriod('2027-03-01'));
        $directory = self::directory('2027-06-15 10:00:00', [$sponsor]);

        self::assertSame('2027-03-01', $directory->currentPeriod($sponsor)?->start);
        self::assertNull(self::directory('2025-06-15 10:00:00', [$sponsor])->currentPeriod($sponsor));
    }

    public function testAk16NurBegonneneZeitraeumeWerdenGenannt(): void
    {
        // Ein pausierender Sponsor, der im September wiederkommt: Die Seite nennt den
        // künftigen Zeitraum noch nicht — „seit September 2027“ stimmte im Juni nicht.
        $sponsor = self::sponsor('pause', new SponsorPeriod('2025-01-01', '2025-12-31'), new SponsorPeriod('2027-09-01'));

        $starts = array_map(static fn (SponsorPeriod $p): string => $p->start, self::directory('2027-06-15 10:00:00', [$sponsor])->startedPeriods($sponsor));
        self::assertSame(['2025-01-01'], $starts);
    }

    public function testAk43Ec08EntfernterSlug(): void
    {
        $directory = self::directory('2027-06-15 10:00:00', [], ['entfernt-sarl']);

        self::assertTrue($directory->isRemoved('entfernt-sarl'));
        self::assertFalse($directory->isRemoved('acme'));
        self::assertNull($directory->findVisible('entfernt-sarl'));
    }
}
