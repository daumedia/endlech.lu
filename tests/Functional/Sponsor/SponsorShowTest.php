<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 15 · Die Seite eines Sponsors (T15).
 */
final class SponsorShowTest extends AbstractWebTestCase
{
    /** @param list<Sponsor> $sponsoren */
    private function seite(string $slug, array $sponsoren, string $sprache = 'de'): Crawler
    {
        $client = static::createClient();
        TestSponsors::use($client, $sponsoren);
        $crawler = $client->request('GET', "/$sprache/sponsoren/$slug");
        self::assertResponseIsSuccessful();

        return $crawler->filter('main');
    }

    public function testAk16InhaltEinesAktivenSponsors(): void
    {
        $main = $this->seite('acme', TestSponsors::all());
        $text = $main->text();

        self::assertSame('Acme S.à r.l.', trim($main->filter('h1')->text()));
        self::assertStringContainsString('Sponsor seit März 2026', $text);
        self::assertCount(1, $main->filter('img[src^="/assets/images/sponsors/"]'), 'Logo');
        self::assertStringContainsString('Was Acme S.à r.l. dafür nicht bekommt', $text);
        foreach (['Keine bessere Bewertung.', 'Keinen Einfluss auf Einträge oder Inhalte.', 'Keinen Zugriff auf einzelne Bewertungen.'] as $punkt) {
            self::assertStringContainsString($punkt, $text);
        }
        self::assertCount(1, $main->filter('a[href="/de/open#finance-heading"]'));
        self::assertCount(1, $main->filter('a[href="/de/organisationen/unternehmen#anfrage"]'));
        self::assertCount(1, $main->filter('a[href="/de/sponsoren"]'));
    }

    public function testAk17WebsiteLinkIstAlsBezahltGekennzeichnet(): void
    {
        $link = $this->seite('acme', TestSponsors::all())->filter('a[href="https://acme.example"]');

        self::assertCount(1, $link);
        $rel = explode(' ', (string) $link->attr('rel'));
        self::assertContains('sponsored', $rel);
        self::assertContains('noopener', $rel);
        self::assertNull($link->attr('target'));
        self::assertStringContainsString('Website von Acme S.à r.l.', $link->text());
    }

    public function testAk18Ak19ZitatInDerOriginalspracheGezeichnetMitFirmenname(): void
    {
        $main = $this->seite('acme', TestSponsors::all());
        $zitat = $main->filter('blockquote');

        self::assertCount(1, $zitat);
        self::assertSame('fr', $zitat->attr('lang'));
        self::assertStringContainsString('Parce que l’accessibilité nous concerne tous.', $zitat->text());
        self::assertStringContainsString('— Acme S.à r.l.', $main->filter('figure')->text());
        self::assertSame('de', $main->closest('html')?->attr('lang') ?? 'de', 'Die Seite selbst bleibt Deutsch.');
    }

    public function testAk20OhneZitatUndOhneWebsiteFehlenDieTeile(): void
    {
        $main = $this->seite('ecole', TestSponsors::all());

        self::assertCount(0, $main->filter('blockquote'));
        self::assertCount(0, $main->filter('figure'));
        self::assertCount(0, $main->filter('a[rel~="sponsored"]'));
        self::assertSame('Ëmil & Fils S.à r.l.', trim($main->filter('h1')->text()), 'EC-05');
    }

    public function testAk21EhemaligerOhneWebsiteLink(): void
    {
        $main = $this->seite('ehemals', TestSponsors::all());

        self::assertStringContainsString('Ehemaliger Sponsor · Januar 2025 – Dezember 2026', $main->text());
        self::assertCount(0, $main->filter('a[href="https://ehemals.example"]'));
        self::assertCount(0, $main->filter('a[rel~="sponsored"]'));
    }

    public function testAk16AlleZeitraeumeNachPause(): void
    {
        $rueckkehr = new Sponsor('rueckkehr', 'Rückkehr SA', 'acme.png', [
            new SponsorPeriod('2025-01-01', '2025-12-31'),
            new SponsorPeriod('2027-03-01'),
        ]);

        self::assertStringContainsString(
            'Sponsor Januar 2025 – Dezember 2025 und seit März 2027',
            $this->seite('rueckkehr', [$rueckkehr])->text(),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    /** Überarbeitung · AK-49: „Über <Name>“ mit der Beschreibung in der Seitensprache. */
    #[\PHPUnit\Framework\Attributes\DataProvider('sprachen')]
    public function testAk49BeschreibungInDerSeitensprache(string $sprache): void
    {
        $main = $this->seite('acme', TestSponsors::all(), $sprache);
        $t = static::getContainer()->get('translator');
        $titel = $t->trans('sponsor.show.about_title', ['%name%' => 'Acme S.à r.l.'], null, $sprache);

        $abschnitt = $main->filter('h2')->reduce(static fn (Crawler $h): bool => trim($h->text()) === $titel);
        self::assertCount(1, $abschnitt, "Überschrift „{$titel}“ fehlt.");
        self::assertStringContainsString(TestSponsors::beschreibung()[$sprache], $main->text());
        foreach (TestSponsors::beschreibung() as $andere => $text) {
            if ($andere !== $sprache) {
                self::assertStringNotContainsString($text, $main->text(), "Fassung $andere auf /$sprache/.");
            }
        }
    }

    /** Überarbeitung · AK-50: Ohne Beschreibung fehlt der Abschnitt ganz. */
    public function testAk50OhneBeschreibungKeinAbschnitt(): void
    {
        $main = $this->seite('ecole', TestSponsors::all());

        self::assertStringNotContainsString('Über Ëmil', $main->text());
    }

    /** Überarbeitung · AK-52: je Profil ein bezahlter Link mit Plattformname, Reihenfolge der Plattformen. */
    public function testAk52ProfillinksMitNamenUndKennzeichnung(): void
    {
        $links = $this->seite('acme', TestSponsors::all())->filter('a[href*="instagram.com"], a[href*="linkedin.com"]');

        self::assertSame(['https://www.instagram.com/acme/', 'https://lu.linkedin.com/company/acme'], $links->each(static fn (Crawler $a): string => (string) $a->attr('href')));
        self::assertSame(['Instagram', 'LinkedIn'], $links->each(static fn (Crawler $a): string => trim($a->text())));
        self::assertSame(['Instagram von Acme S.à r.l.', 'LinkedIn von Acme S.à r.l.'], $links->each(static fn (Crawler $a): string => (string) $a->attr('aria-label')));
        $links->each(static function (Crawler $a): void {
            self::assertContains('sponsored', explode(' ', (string) $a->attr('rel')));
            self::assertContains('noopener', explode(' ', (string) $a->attr('rel')));
            self::assertNull($a->attr('target'));
        });
    }

    /** Überarbeitung · AK-54: Ehemalige behalten die Beschreibung, verlieren die Profillinks. */
    public function testAk54EhemaligBehaeltBeschreibungOhneProfile(): void
    {
        $ehemals = new Sponsor('alt', 'Alt SA', 'acme.png', [new SponsorPeriod('2025-01-01', '2025-12-31')],
            description: TestSponsors::beschreibung(), profiles: ['facebook' => 'https://www.facebook.com/alt']);
        $main = $this->seite('alt', [$ehemals]);

        self::assertStringContainsString('Acme baut Rampen für Luxemburg.', $main->text());
        self::assertCount(0, $main->filter('a[href*="facebook.com"]'));
    }

    /** Überarbeitung · EC-13: Auszeichnung wird Text, ein Zeilenumbruch bleibt ein Umbruch. */
    public function testEc13BeschreibungWirdAlsTextGezeigt(): void
    {
        $text = "Erste Zeile <b>fett</b> & „zitiert“\nZweite Zeile";
        $sponsor = new Sponsor('sonder', 'Sonder SA', 'acme.png', [new SponsorPeriod('2026-01-01')], description: array_fill_keys(['lb', 'de', 'fr', 'en', 'pt'], $text));
        $main = $this->seite('sonder', [$sponsor]);

        self::assertCount(0, $main->filter('b'), 'Auszeichnung aus der Beschreibung wurde ausgeführt.');
        self::assertStringContainsString('<b>fett</b>', $main->text());
        self::assertStringContainsString('&lt;b&gt;fett&lt;/b&gt;', $main->html());
        self::assertMatchesRegularExpression('#Erste Zeile.*<br\s*/?>\s*Zweite Zeile#s', $main->html());
    }

    /** Überarbeitung · EC-14: Profile ohne Website stehen da; ohne beides fehlt die Linkzeile. */
    public function testEc14ProfileOhneWebsite(): void
    {
        $sponsor = new Sponsor('nurprofil', 'Nur Profil SA', 'acme.png', [new SponsorPeriod('2026-01-01')], profiles: ['facebook' => 'https://www.facebook.com/nurprofil']);
        $main = $this->seite('nurprofil', [$sponsor]);

        self::assertCount(1, $main->filter('a[href="https://www.facebook.com/nurprofil"]'));
        self::assertCount(1, $main->filter('a[rel~="sponsored"]'));
    }

    public function testAk16MonatInDerSeitensprache(): void
    {
        self::assertStringContainsString('Sponsor depuis mars 2026', $this->seite('acme', TestSponsors::all(), 'fr')->text());
    }
}
