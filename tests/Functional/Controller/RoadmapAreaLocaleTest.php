<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 16 · T07: Der Bereich in allen fünf Sprachen — Beschriftungen, keine rohen
 * Schlüssel (AK-27) und „Ideen" statt „Board" an den Stellen aus OF-01 (AK-37).
 */
final class RoadmapAreaLocaleTest extends AbstractWebTestCase
{
    /** Das Wort, das an den OF-01-Stellen nicht mehr stehen darf — je Sprache. */
    private const array BOARD_WORT = [
        'lb' => 'Board',
        'de' => 'Board',
        'fr' => 'tableau',
        'en' => 'board',
        'pt' => 'quadro',
    ];

    /** Die Stellen aus OF-01 (und BF-201), als Katalogschlüssel. */
    private const array OF01_SCHLUESSEL = [
        ['stage.planned.empty', 'roadmap'],
        ['stage.considered.empty', 'roadmap'],
        ['community.heading', 'roadmap'],
        ['community.selection_hint', 'roadmap'],
        ['community.more', 'roadmap'],
        ['community.open_board', 'roadmap'],
        ['board.back_to_board', 'messages'],
        // BF-201 (entschieden 2026-11-07): Die Regel aus OF-01 gilt auch hier.
        ['board.empty_title', 'messages'],
        ['board.thanks_text', 'messages'],
        ['flash.board_published', 'messages'],
        ['email.board_published_subject', 'messages'],
        ['email.board_published_greeting', 'messages'],
        ['email.board_published_text', 'messages'],
    ];

    private function geplanteIdee(KernelBrowser $client): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle('Idee in allen Sprachen')
            ->setDescription('Beschreibung.')
            ->setSlug('alle-sprachen')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable('-1 day'));
        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    private static function sichtbarerText(Crawler $crawler): string
    {
        return $crawler->filter('body')->text();
    }

    /** AK-27: Name, Reiter und Knopf sind beschriftet, nirgends ein roher Schlüssel. */
    #[DataProvider('sprachen')]
    public function testAk27BeschriftetUndOhneRohenSchluessel(string $locale): void
    {
        $client = static::createClient();
        $idee = $this->geplanteIdee($client);
        $uebersetzer = $client->getContainer()->get('translator');

        $erwartet = [
            $uebersetzer->trans('area.tab.roadmap', [], 'roadmap', $locale),
            $uebersetzer->trans('area.tab.ideas', [], 'roadmap', $locale),
            $uebersetzer->trans('area.tab.changelog', [], 'roadmap', $locale),
        ];
        foreach ($erwartet as $text) {
            self::assertStringNotContainsString('area.', $text, "[$locale] Reiter unübersetzt");
        }

        $seiten = ['/roadmap', '/roadmap/ideen', '/changelog', sprintf('/roadmap/ideen/%d-alle-sprachen', $idee->getId())];
        foreach ($seiten as $pfad) {
            $crawler = $client->request('GET', '/' . $locale . $pfad);
            self::assertResponseIsSuccessful("[$locale] $pfad");

            self::assertSame($erwartet, $crawler->filter('nav[data-area-tabs] a')->each(static fn (Crawler $a): string => trim($a->text())), "[$locale] $pfad");
            self::assertSame($uebersetzer->trans('area.name', [], 'roadmap', $locale), $crawler->filter('nav[data-area-tabs]')->attr('aria-label'));
            self::assertStringContainsString($uebersetzer->trans('board.submit_cta', [], null, $locale), $crawler->filter('[data-area-header]')->text());

            self::assertDoesNotMatchRegularExpression(
                '/\b(area|footer|community|stage|board|more|shelved)\.[a-z_]+\b/',
                self::sichtbarerText($crawler),
                "[$locale] $pfad zeigt einen rohen Übersetzungsschlüssel",
            );
        }

        $this->loginAs($client, 'user@endlech.lu');
        $crawler = $client->request('GET', '/' . $locale . '/roadmap/ideen/neu');
        self::assertResponseIsSuccessful();
        self::assertDoesNotMatchRegularExpression('/\b(area|footer|community|board)\.[a-z_]+\b/', self::sichtbarerText($crawler), "[$locale] Formular");
    }

    /** AK-37: An den Stellen aus OF-01 heißt der Ort „Ideen" — im Katalog und auf der Seite. */
    #[DataProvider('sprachen')]
    public function testAk37OrtHeisstIdeen(string $locale): void
    {
        $client = static::createClient();
        $uebersetzer = $client->getContainer()->get('translator');
        $wort = self::BOARD_WORT[$locale];

        foreach (self::OF01_SCHLUESSEL as [$schluessel, $domain]) {
            $text = $uebersetzer->trans($schluessel, ['%count%' => 3], $domain, $locale);
            self::assertStringNotContainsStringIgnoringCase($wort, $text, "[$locale] $schluessel");
            self::assertStringNotContainsString($schluessel, $text, "[$locale] $schluessel unübersetzt");
        }

        $idee = $this->geplanteIdee($client);
        $crawler = $client->request('GET', '/' . $locale . '/roadmap');
        $gruppe = $crawler->filter('section[aria-labelledby="stage-planned"]');
        self::assertStringNotContainsStringIgnoringCase($wort, $gruppe->filter('h3')->text(), "[$locale] Überschrift der Community-Gruppe");
        self::assertStringNotContainsStringIgnoringCase($wort, $gruppe->filter('a[href*="/roadmap/ideen/"]')->text(), "[$locale] Verweis an der Idee");

        $crawler = $client->request('GET', sprintf('/%s/roadmap/ideen/%d-alle-sprachen', $locale, $idee->getId()));
        $rueckweg = $crawler->filter('main a[href="/' . $locale . '/roadmap/ideen"]')->last();
        self::assertStringNotContainsStringIgnoringCase($wort, $rueckweg->text(), "[$locale] Rückweg");
    }

    /**
     * BF-202: Französisch muss die Reiterleiste bei 375 px in eine Zeile passen — sonst schiebt sie
     * „En cours" unter das Cookie-Banner. Der Reiter heißt deshalb „Journal"; die Überschrift der
     * Seite bleibt „Journal des modifications".
     */
    public function testBf202FranzoesischerReiterIstKurz(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/changelog');

        self::assertSame(['Feuille de route', 'Idées', 'Journal'], $crawler->filter('nav[data-area-tabs] a')->each(static fn (Crawler $a): string => trim($a->text())));
        self::assertSame('Journal des modifications', trim($crawler->filter('h1')->text()));
    }

    /** @return iterable<string, array{0: string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $locale) {
            yield $locale => [$locale];
        }
    }
}
