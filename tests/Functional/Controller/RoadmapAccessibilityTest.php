<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Struktur und Bedienbarkeit beider Seiten (Feature 07, AK-34 bis AK-38).
 *
 * ⚠ **Was hier NICHT geprüft wird:** die tatsächliche Darstellung bei 320 px und
 * der axe-Lauf. Beides braucht einen Browser und gehört in die QA. Geprüft wird,
 * was im ausgelieferten Markup entschieden wird — und das ist der Teil, den ein
 * späterer Umbau still kaputt machen kann.
 */
final class RoadmapAccessibilityTest extends AbstractWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function seiten(): iterable
    {
        yield 'roadmap' => ['/roadmap'];
        yield 'changelog' => ['/changelog'];
    }

    /**
     * AK-38: Genau eine h1, und die Überschriftenebenen sind lückenlos.
     *
     * ⚠ **Geprüft wird der Inhaltsbereich, nicht die ganze Seite — und das ist
     * eine gemeldete Einschränkung, keine Bequemlichkeit.** Die Fußzeile der
     * App-Hülle überschreibt ihre vier Spalten mit `<h4>`; da die letzte
     * Inhaltsüberschrift eine `h2` ist, springt die Kette seitenweit von h2 auf
     * h4. Das gilt **auf jeder Seite des Projekts**, nachgemessen an `/presse`,
     * `/open`, `/about` und `/vergleich` — dieses Feature hat es sichtbar
     * gemacht, nicht verursacht. Steht als OF-10 in `spec.md`.
     */
    #[DataProvider('seiten')]
    public function testUeberschriftenSindLueckenlos(string $pfad): void
    {
        $client = static::createClient();

        // Eine Community-Idee sorgt dafür, dass die tiefste Ebene (h4) vorkommt.
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist((new BoardIdea())
            ->setTitle('Idee für die Ebenenprüfung')
            ->setDescription('…')
            ->setSlug('ebenenpruefung')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable()));
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.$pfad);
        self::assertResponseIsSuccessful();

        $ebenen = $crawler->filter('main h1, main h2, main h3, main h4, main h5, main h6')->each(
            static fn ($n): int => (int) substr($n->nodeName(), 1),
        );

        // Die h1 steht im Inhaltsbereich, nicht in der Hülle.
        self::assertNotEmpty($ebenen, 'Im Inhaltsbereich steht keine einzige Überschrift.');

        self::assertCount(1, array_filter($ebenen, static fn (int $e): bool => 1 === $e), 'Es muss genau eine h1 geben.');
        self::assertSame(1, $ebenen[0], 'Die erste Überschrift der Seite muss die h1 sein.');

        $vorher = 0;
        foreach ($ebenen as $index => $ebene) {
            if ($ebene > $vorher) {
                self::assertSame($vorher + 1, $ebene, sprintf(
                    'Überschrift %d springt von h%d auf h%d — eine Ebene wird übersprungen.',
                    $index + 1,
                    $vorher,
                    $ebene,
                ));
            }
            $vorher = $ebene;
        }
    }

    /**
     * AK-37: Jede Spalte ist ein eigener Abschnitt mit eigener Überschrift.
     *
     * Im Screenreader gibt es keine Spalten, nur eine Reihenfolge — die
     * Zugehörigkeit eines Eintrags darf sich nicht aus seiner Position ergeben.
     */
    public function testJedeSpalteIstEinBeschrifteterAbschnitt(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $spalten = $crawler->filter('section[aria-labelledby^="stage-"]');
        self::assertCount(3, $spalten);

        foreach ($spalten as $spalte) {
            $id = $spalte->getAttribute('aria-labelledby');
            self::assertCount(
                1,
                $crawler->filter('#'.$id),
                sprintf('Die Beschriftung "%s" zeigt ins Leere.', $id),
            );
        }
    }

    /**
     * AK-23: Die Jahresabschnitte klappen ohne JavaScript auf.
     *
     * ⚠ **Kein handgeschriebenes `aria-expanded`.** <details>/<summary> meldet
     * seinen Zustand selbst; ein von Hand gesetztes Attribut ließe sich ohne
     * Skript nie aktualisieren und wäre nach dem ersten Klick falsch.
     */
    public function testJahresabschnitteBrauchenKeinJavaScript(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/changelog');

        $inhaltsbereich = $crawler->filter('section[aria-labelledby], details');
        self::assertGreaterThan(0, $inhaltsbereich->count());

        foreach ($crawler->filter('details')->each(static fn ($n) => $n) as $details) {
            self::assertCount(1, $details->filter('summary'), 'Ein <details> ohne <summary> ist mit der Tastatur nicht erreichbar.');
            self::assertSame('', (string) $details->attr('aria-expanded'), 'aria-expanded gehört nicht an ein <details>.');
        }

        // Kein Skript steuert das Auf- und Zuklappen.
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('data-controller="changelog', $inhalt);
    }

    /** AK-36: Jeder Verweis der beiden Seiten ist ein echter Anker mit Ziel. */
    #[DataProvider('seiten')]
    public function testAlleVerweiseHabenEinZiel(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        $ohneZiel = $crawler->filter('main a:not([href]), a[href=""], a[href="#"]')->count();
        self::assertSame(0, $ohneZiel, 'Es gibt Verweise ohne Ziel.');
    }

    /**
     * EC-08: Ein Titel in Maximallänge bricht um, statt die Spalte zu verbreitern.
     *
     * Geprüft wird die Zusage im Markup (`wrap-anywhere`), nicht die Darstellung —
     * die misst die QA im Browser. Ohne diese Klasse entsteht bei 320 px genau der
     * waagerechte Überlauf, der bei Feature 03 als BF-77 blockierte.
     */
    public function testLangeTitelDuerfenUmbrechen(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $em->persist((new BoardIdea())
            ->setTitle(str_repeat('Donaudampfschifffahrtsgesellschaft', 3))
            ->setDescription('…')
            ->setSlug('sehr-langer-titel')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable()));
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        $karte = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->first();

        self::assertStringContainsString('wrap-anywhere', (string) $karte->attr('class'), 'Ein überlanger Titel kann die Spalte sprengen.');
    }

    /**
     * EC-09: Emoji und fremde Schriftsysteme werden dargestellt, ohne die
     * Leserichtung des Umfelds zu ändern.
     */
    public function testEmojiUndFremdeSchriftBrechenNichts(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $em->persist((new BoardIdea())
            ->setTitle('🚻 مقهى بلا حواجز')
            ->setDescription('…')
            ->setSlug('emoji-und-arabisch')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable()));
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        self::assertResponseIsSuccessful();

        $inhalt = $crawler->filter('section[aria-labelledby="stage-planned"]')->text();
        self::assertStringContainsString('مقهى بلا حواجز', $inhalt);
        self::assertStringContainsString('🚻', $inhalt);

        // Keine Richtungsumschaltung im Umfeld — die Seite bleibt links-nach-rechts.
        self::assertSame('', (string) $crawler->filter('section[aria-labelledby="stage-planned"]')->attr('dir'));
    }

    /** AK-35: Nichts erzwingt eine Mindestbreite, die bei 320 px überliefe. */
    #[DataProvider('seiten')]
    public function testKeineFesteBreiteImMarkup(string $pfad): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.$pfad);
        $inhalt = (string) $client->getResponse()->getContent();

        self::assertDoesNotMatchRegularExpression(
            '/(min-w-\[\d{3,}px\]|width:\s*\d{3,}px|<table)/',
            $inhalt,
            'Es gibt eine feste Mindestbreite oder eine Tabelle — bei 320 px droht waagerechtes Scrollen (BF-77).',
        );
    }
}
