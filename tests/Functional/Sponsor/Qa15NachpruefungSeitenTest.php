<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * QA Feature 15, Nachprüfung der Überarbeitung (2026-10-28) — Sponsorenseite mit Kurzbeschreibung und Profillinks.
 *
 * Ergänzt `SponsorShowTest` dort, wo der Bau nur `/de/` oder nur einen Teil des Kriteriums prüft:
 * - AK-49 an der Grenze (500 Zeichen vollständig) in allen fünf Sprachen;
 * - AK-52 mit einem Namen aus `&` und `"` und einer Profiladresse mit `"` (kein Attribut entsteht aus Daten);
 * - AK-54 in allen fünf Sprachen, mit Website **und** drei Profilen;
 * - EC-14 zweite Hälfte („hat er weder noch, fehlt der ganze Block mit Links“);
 * - AK-55 mit allen drei Plattformen.
 *
 * Testuhr: 2027-06-15 (`when@test`).
 */
final class Qa15NachpruefungSeitenTest extends AbstractWebTestCase
{
    private const array SPRACHEN = ['lb', 'de', 'fr', 'en', 'pt'];

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (self::SPRACHEN as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    /** @param list<Sponsor> $sponsoren */
    private function seite(string $pfad, array $sponsoren): Crawler
    {
        $client = static::createClient();
        TestSponsors::use($client, $sponsoren);
        $crawler = $client->request('GET', $pfad);
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    /** @return array<string, string> */
    private static function jeSprache(string $muster): array
    {
        $texte = [];
        foreach (self::SPRACHEN as $sprache) {
            $texte[$sprache] = str_replace('%s', strtoupper($sprache), $muster);
        }

        return $texte;
    }

    /** AK-49 · Eine Beschreibung von genau 500 Zeichen steht vollständig da, unter „Über <Name>“ der Seitensprache. */
    #[DataProvider('sprachen')]
    public function testAk49BeschreibungVon500ZeichenStehtVollstaendig(string $sprache): void
    {
        $texte = [];
        foreach (self::SPRACHEN as $s) {
            $kopf = strtoupper($s).'-';
            $texte[$s] = $kopf.str_repeat('ä', 500 - mb_strlen($kopf) - 1).'Z';
            self::assertSame(500, mb_strlen($texte[$s]));
        }
        $sponsor = new Sponsor('grenze', 'Grenze GmbH', 'acme.png', [new SponsorPeriod('2026-01-01')], description: $texte);

        $main = $this->seite("/$sprache/sponsoren/grenze", [$sponsor])->filter('main');
        $titel = static::getContainer()->get('translator')->trans('sponsor.show.about_title', ['%name%' => 'Grenze GmbH'], null, $sprache);
        $ueberschrift = $main->filter('h2')->reduce(static fn (Crawler $h): bool => trim($h->text()) === $titel);

        self::assertCount(1, $ueberschrift, "Überschrift „{$titel}“ fehlt.");
        self::assertSame($texte[$sprache], trim($ueberschrift->nextAll()->first()->text()), 'Beschreibung gekürzt oder in falscher Sprache.');
    }

    /** AK-52 · Name mit `&` und `"`: angesagt wird genau „<Plattform> von <Name>“, aus der Adresse entsteht kein Attribut. */
    public function testAk52NameUndAdresseMitSonderzeichen(): void
    {
        $sponsor = new Sponsor('emil', 'Ëmil & Fils "Q" S.à r.l.', 'acme.png', [new SponsorPeriod('2026-01-01')], profiles: [
            'instagram' => 'https://www.instagram.com/emil"onmouseover="alert(1)',
            'linkedin' => 'https://lu.linkedin.com/company/emil',
        ]);
        $links = $this->seite('/de/sponsoren/emil', [$sponsor])->filter('main a[rel~="sponsored"]');

        self::assertSame(['Instagram von Ëmil & Fils "Q" S.à r.l.', 'LinkedIn von Ëmil & Fils "Q" S.à r.l.'], $links->each(static fn (Crawler $a): string => (string) $a->attr('aria-label')));
        self::assertSame('https://www.instagram.com/emil"onmouseover="alert(1)', $links->first()->attr('href'));
        $links->each(static function (Crawler $a): void {
            $namen = [];
            foreach ($a->getNode(0)->attributes as $attribut) {
                $namen[] = $attribut->name;
            }
            sort($namen);
            self::assertSame(['aria-label', 'class', 'href', 'rel'], $namen);
        });
    }

    /** AK-54 · Ehemalig: Beschreibung bleibt (Seitensprache), Website- und alle Profillinks fallen weg. */
    #[DataProvider('sprachen')]
    public function testAk54EhemaligBehaeltBeschreibungVerliertAlleLinks(string $sprache): void
    {
        $sponsor = new Sponsor('alt', 'Alt SA', 'acme.png', [new SponsorPeriod('2025-01-01', '2026-12-31')],
            website: 'https://alt.example',
            description: self::jeSprache('%s-ALT Beschreibung'),
            profiles: [
                'instagram' => 'https://www.instagram.com/alt/',
                'facebook' => 'https://www.facebook.com/alt',
                'linkedin' => 'https://www.linkedin.com/company/alt',
            ]);
        $main = $this->seite("/$sprache/sponsoren/alt", [$sponsor])->filter('main');

        self::assertStringContainsString(strtoupper($sprache).'-ALT Beschreibung', $main->text());
        self::assertCount(0, $main->filter('a[href^="https://"]'), 'Ein ehemaliger Sponsor trägt noch einen Link nach außen.');
    }

    /** EC-14 · Weder Website noch Profil: Es gibt keine Linkzeile — nur die Liste „Was … nicht bekommt“ bleibt als Liste. */
    public function testEc14OhneWebsiteUndOhneProfilKeineLinkzeile(): void
    {
        $ohne = new Sponsor('ohne', 'Ohne ASBL', 'acme.png', [new SponsorPeriod('2026-01-01')]);
        $nurProfil = new Sponsor('nur', 'Nur SA', 'acme.png', [new SponsorPeriod('2026-01-01')], profiles: ['linkedin' => 'https://www.linkedin.com/company/nur']);

        $client = static::createClient();
        TestSponsors::use($client, [$ohne, $nurProfil]);
        $client->disableReboot();

        self::assertCount(1, $client->request('GET', '/de/sponsoren/ohne')->filter('main ul'), 'Leere Linkzeile ohne Website und Profil.');
        self::assertCount(2, $client->request('GET', '/de/sponsoren/nur')->filter('main ul'), 'Gegenprobe: mit Profil gibt es die Linkzeile.');
    }

    /** AK-55 · Mit allen drei Plattformen: nichts wird von dort geladen, die Links tragen keinen Messauslöser. */
    public function testAk55DreiPlattformenNurAlsLinkziel(): void
    {
        $sponsor = new Sponsor('drei', 'Drei SA', 'acme.png', [new SponsorPeriod('2026-01-01')], profiles: [
            'instagram' => 'https://www.instagram.com/drei/',
            'facebook' => 'https://www.facebook.com/drei',
            'linkedin' => 'https://www.linkedin.com/company/drei',
        ]);
        $crawler = $this->seite('/de/sponsoren/drei', [$sponsor]);

        $fremd = $crawler->filter('[src], [srcset], [data-src], [poster], link[href], object[data]')->reduce(static fn (Crawler $n): bool => 1 === preg_match(
            '#instagram|facebook|fbcdn|linkedin|licdn#i',
            implode(' ', [$n->attr('src'), $n->attr('srcset'), $n->attr('data-src'), $n->attr('poster'), 'link' === $n->nodeName() ? $n->attr('href') : '', $n->attr('data')]),
        ));
        self::assertCount(0, $fremd, 'Etwas wird von einer Plattform geladen.');

        $links = $crawler->filter('a[href*="instagram.com"], a[href*="facebook.com"], a[href*="linkedin.com"]');
        self::assertCount(3, $links);
        $links->each(static function (Crawler $a): void {
            foreach ($a->getNode(0)->attributes as $attribut) {
                self::assertStringStartsNotWith('data-', $attribut->name, 'Messauslöser an einem Profillink.');
            }
        });
    }
}
