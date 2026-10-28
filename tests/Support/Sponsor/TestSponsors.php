<?php

declare(strict_types=1);

namespace App\Tests\Support\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Clock\MockClock;

/**
 * Testsponsoren für die Funktionstests von Feature 15.
 *
 * Die Testuhr steht auf dem 15. Juni 2027 (`when@test` in `services.yaml`).
 * Bezogen darauf ist `acme` aktiv (mit Zitat auf Französisch und Website),
 * `ecole` aktiv (ohne Zitat, ohne Website), `ehemals` beendet und `morgen`
 * noch nicht begonnen.
 */
final class TestSponsors
{
    public static function acme(): Sponsor
    {
        return new Sponsor(
            slug: 'acme',
            name: 'Acme S.à r.l.',
            logo: 'acme.png',
            periods: [new SponsorPeriod('2026-03-01')],
            website: 'https://acme.example',
            quote: 'Parce que l’accessibilité nous concerne tous.',
            quoteLocale: 'fr',
            description: self::beschreibung(),
            profiles: [
                'linkedin' => 'https://lu.linkedin.com/company/acme',
                'instagram' => 'https://www.instagram.com/acme/',
            ],
        );
    }

    /**
     * Die Kurzbeschreibung der Überarbeitung (AK-49) — je Sprache erkennbar verschieden, damit ein
     * Prüflauf sieht, welche Fassung auf welcher Seite steht.
     *
     * @return array<string, string>
     */
    public static function beschreibung(): array
    {
        return [
            'lb' => 'Acme baut Rampen fir Lëtzebuerg.',
            'de' => 'Acme baut Rampen für Luxemburg.',
            'fr' => 'Acme construit des rampes pour le Luxembourg.',
            'en' => 'Acme builds ramps for Luxembourg.',
            'pt' => 'A Acme constrói rampas para o Luxemburgo.',
        ];
    }

    public static function ecole(): Sponsor
    {
        return new Sponsor('ecole', 'Ëmil & Fils S.à r.l.', 'breit.webp', [new SponsorPeriod('2026-09-01')]);
    }

    public static function ehemals(): Sponsor
    {
        return new Sponsor(
            slug: 'ehemals',
            name: 'Ehemals AG',
            logo: 'quadrat.png',
            periods: [new SponsorPeriod('2025-01-01', '2026-12-31')],
            website: 'https://ehemals.example',
        );
    }

    public static function morgen(): Sponsor
    {
        return new Sponsor('morgen', 'Morgen GmbH', 'quadrat.png', [new SponsorPeriod('2027-06-16')]);
    }

    /** @return list<Sponsor> */
    public static function all(): array
    {
        return [self::acme(), self::ecole(), self::ehemals(), self::morgen()];
    }

    /**
     * Setzt Sponsoren (und optional die Uhr) für die NÄCHSTE Anfrage des Clients.
     *
     * @param list<Sponsor> $sponsors
     * @param list<string>  $removed
     */
    public static function use(KernelBrowser $client, array $sponsors, array $removed = [], ?string $now = null): void
    {
        $container = $client->getContainer();
        $container->get(InMemorySponsorSource::class)->replace($sponsors, $removed);

        if (null !== $now) {
            $clock = $container->get('app.sponsor_clock');
            \assert($clock instanceof MockClock);
            $clock->modify($now);
        }
    }
}
