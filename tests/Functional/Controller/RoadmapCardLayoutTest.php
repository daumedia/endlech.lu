<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * BF-108: Der Titel einer Community-Karte darf neben dem Herkunftszeichen nicht
 * zusammenfallen.
 *
 * ⚠ **Dieser Lauf misst keine Pixel — er kann es nicht.** Genau daran ging BF-107 in
 * Feature `06` vorbei: `BoardTargetSizeTest` liest Klassen aus dem Markup und sieht
 * keine gerenderte Breite. Was hier geprüft wird, ist die **Ursache**: eine
 * Überschrift, die in einem Flex-Container neben einem `shrink-0`-Element steht und
 * selbst weder `min-w-0` noch `basis-full` trägt, fällt zusammen, sobald der Container
 * schmal wird — bei 768 px gemessene 12 px Breite und 648 px Höhe.
 *
 * Die Messung selbst gehört in die QA (Browser, vier Breiten). Dieser Lauf verhindert,
 * dass die Klassenkette bei einem Umbau still verschwindet.
 *
 * Angelegt bei der Reparatur von BF-108 am 2026-08-30.
 */
final class RoadmapCardLayoutTest extends AbstractWebTestCase
{
    private function geplanteIdee(string $titel, string $slug): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new BoardIdea())
            ->setTitle($titel)
            ->setDescription('…')
            ->setSlug($slug)
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable()));
        $em->flush();
    }

    /**
     * Der Titel trägt die Kette, die ihn im schmalen Container am Leben hält.
     *
     * `basis-full` gibt ihm unterhalb von `lg:` die ganze Zeile, `min-w-0` erlaubt ihm
     * das Schrumpfen unter die Wortbreite (sonst greift `min-width: auto`), und
     * `lg:flex-1` lässt ihn oberhalb wieder neben dem Zeichen stehen.
     */
    public function testDerTitelTraegtDieUmbruchkette(): void
    {
        $client = static::createClient();
        $this->geplanteIdee('Ein Titel neben dem Herkunftszeichen', 'bf108-kette');

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        self::assertResponseIsSuccessful();

        $titel = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->first();
        $klassen = (string) $titel->attr('class');

        foreach (['basis-full', 'min-w-0', 'lg:flex-1'] as $pflicht) {
            self::assertStringContainsString($pflicht, $klassen, sprintf(
                'Der Titel einer Community-Karte braucht „%s" — ohne die Kette fällt er neben dem '
                .'Herkunftszeichen auf wenige Pixel zusammen (BF-108).',
                $pflicht,
            ));
        }
    }

    /**
     * Der Container lässt umbrechen — ohne `flex-wrap` wirkt `basis-full` nicht.
     *
     * ⚠ Die beiden gehören zusammen: `basis-full` weist die volle Breite zu, aber erst
     * `flex-wrap` erlaubt dem Zeichen, in die nächste Zeile zu rutschen. Fehlt eines
     * von beiden, ist die Reparatur wirkungslos.
     */
    public function testDerContainerLaesstUmbrechen(): void
    {
        $client = static::createClient();
        $this->geplanteIdee('Noch ein Titel', 'bf108-wrap');

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        $container = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->first()
            ->ancestors()->filter('div')->first();

        self::assertStringContainsString('flex-wrap', (string) $container->attr('class'),
            'Ohne flex-wrap kann das Herkunftszeichen nicht umbrechen — basis-full bleibt wirkungslos (BF-108).');
    }

    /**
     * Die Regel gilt für **jede** Überschrift, die neben einem `shrink-0`-Element steht.
     *
     * ⚠ Das ist der eigentliche Ertrag: BF-107 und BF-108 sind derselbe Fehler an zwei
     * Karten. Geprüft werden beide Kartenvorlagen — eine dritte fällt hier auf, bevor
     * sie in die QA kommt.
     */
    public function testKeineUeberschriftStehtUngeschuetztNebenEinemShrinkNullElement(): void
    {
        $client = static::createClient();
        $this->geplanteIdee('Titel für die Musterprüfung', 'bf108-muster');

        $verstoesse = [];
        foreach ([self::LOCALE.'/roadmap', self::LOCALE.'/community/ideen'] as $pfad) {
            $crawler = $client->request('GET', $pfad);
            self::assertResponseIsSuccessful();

            $crawler->filter('main h2, main h3, main h4')->each(
                static function (Crawler $ueberschrift) use (&$verstoesse, $pfad): void {
                    $eltern = $ueberschrift->ancestors()->filter('div')->first();
                    if (0 === $eltern->count()) {
                        return;
                    }
                    $elternKlassen = (string) $eltern->attr('class');
                    if (!str_contains($elternKlassen, 'flex') || str_contains($elternKlassen, 'flex-col')) {
                        return;
                    }
                    if (0 === $eltern->filter('.shrink-0')->count()) {
                        return;
                    }

                    $eigene = (string) $ueberschrift->attr('class');
                    if (!str_contains($eigene, 'min-w-0') && !str_contains($eigene, 'basis-full')) {
                        $verstoesse[] = sprintf('%s: „%s"', $pfad, mb_substr(trim($ueberschrift->text()), 0, 40));
                    }
                },
            );
        }

        self::assertSame([], $verstoesse, sprintf(
            "%d Überschrift(en) stehen neben einem shrink-0-Element, ohne selbst schrumpfen zu dürfen "
            ."— sie fallen im schmalen Container zusammen (BF-107, BF-108):\n  %s",
            \count($verstoesse),
            implode("\n  ", $verstoesse),
        ));
    }
}
