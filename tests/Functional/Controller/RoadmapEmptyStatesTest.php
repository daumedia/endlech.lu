<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Roadmap\RoadmapStage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * Die leeren Zustände beider Seiten (AK-09, AK-25, EC-01).
 *
 * ⚠ **Diese Zustände gibt der Regelbetrieb nicht her**: Die Registries stehen im
 * Code und sind nie leer, der Changelog hat neun Einträge. Über HTTP wären die
 * Kriterien deshalb dauerhaft unprüfbar — geprüft wird an den Vorlagen, mit genau
 * den Daten, die der Controller im leeren Fall übergäbe.
 *
 * Angelegt von der Qualitätssicherung am 2026-08-30.
 */
final class RoadmapEmptyStatesTest extends KernelTestCase
{
    private function twig(): Environment
    {
        self::bootKernel();

        $request = Request::create('/de/roadmap');
        $request->setLocale('de');
        $request->attributes->set('_route', 'app_roadmap_index');
        $request->attributes->set('_route_params', ['_locale' => 'de']);
        self::getContainer()->get('request_stack')->push($request);

        return self::getContainer()->get(Environment::class);
    }

    private function leereRoadmap(): Crawler
    {
        $html = $this->twig()->render('roadmap/index.html.twig', [
            'columns' => array_map(
                static fn (RoadmapStage $s): array => ['stage' => $s, 'items' => []],
                RoadmapStage::cases(),
            ),
            'community' => ['ideas' => [], 'more' => 0],
            'shelved' => [],
            'lastChange' => null,
            'plannedStage' => RoadmapStage::PLANNED,
        ]);

        return new Crawler($html);
    }

    /** AK-09: Jede leere Spalte trägt einen erklärenden Satz, keine leere Fläche. */
    public function testJedeLeereSpalteErklaertSich(): void
    {
        $crawler = $this->leereRoadmap();
        $spalten = $crawler->filter('section[aria-labelledby^="stage-"]');

        self::assertCount(3, $spalten, 'Auch ohne Einträge müssen die drei Spalten stehen.');

        foreach ($spalten as $spalte) {
            $text = trim(preg_replace('/\s+/', ' ', $spalte->textContent) ?? '');
            $ueberschrift = trim((new Crawler($spalte))->filter('h2')->text());
            $ohneUeberschrift = trim(str_replace($ueberschrift, '', $text));

            self::assertGreaterThan(
                25,
                mb_strlen($ohneUeberschrift),
                sprintf('Die Spalte „%s" ist leer statt erklärt.', $ueberschrift),
            );
        }
    }

    /**
     * EC-01: Ohne Community-Ideen erscheint weder der Block noch ein Hinweis auf
     * ein Versäumnis.
     */
    public function testOhneCommunityIdeenKeinLeererBlock(): void
    {
        $html = $this->leereRoadmap()->html();

        self::assertStringNotContainsString('Aus dem Ideen-Board', $html);
        self::assertStringNotContainsString('weitere geplante', $html);
        self::assertStringNotContainsString('zehn geplanten Ideen', $html, 'Die Auswahlregel gehört nur über eine vorhandene Gruppe.');
    }

    /** AK-25: Ein leerer Changelog erklärt sich und verweist auf die technische Fassung. */
    public function testDerLeereChangelogErklaertSich(): void
    {
        $html = $this->twig()->render('roadmap/changelog.html.twig', [
            'years' => [],
            'currentYear' => '2026',
            'lastChange' => null,
        ]);
        $crawler = new Crawler($html);

        self::assertCount(0, $crawler->filter('main article'), 'Ohne Einträge darf kein Artikel stehen.');
        self::assertGreaterThan(0, $crawler->filter('main h2')->count());
        self::assertStringContainsString('CHANGELOG.md', $html, 'Der Verweis auf die technische Fassung fehlt.');
        self::assertStringNotContainsString('%date%', $html, 'Ein unersetzter Platzhalter im leeren Zustand.');

        $leerBlock = $crawler->filter('main .bg-gray-50')->first();
        self::assertGreaterThan(20, mb_strlen(trim($leerBlock->text())), 'Der leere Zustand trägt keinen erklärenden Text.');
    }
}
