<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Roadmap\ChangelogRegistry;
use App\Roadmap\ReleaseNote;
use App\Roadmap\RoadmapRegistry;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Die beiden öffentlichen Seiten von Feature 07.
 */
final class RoadmapControllerTest extends AbstractWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en'] as $locale) {
            yield $locale => [$locale];
        }
    }

    /** AK-01, AK-30: Die Roadmap ist ohne Anmeldung in allen vier Sprachen erreichbar. */
    #[DataProvider('sprachen')]
    public function testRoadmapIstInAllenSprachenOeffentlich(string $locale): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/'.$locale.'/roadmap');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('h1'), 'Es muss genau eine h1 geben (AK-38).');
        self::assertStringNotContainsString('roadmap.', $crawler->filter('body')->text(), 'Roher Übersetzungsschlüssel auf der Seite (AK-32).');
    }

    /** AK-19, AK-30: Der Changelog ebenso. */
    #[DataProvider('sprachen')]
    public function testChangelogIstInAllenSprachenOeffentlich(string $locale): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/'.$locale.'/changelog');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('h1'));
        self::assertStringNotContainsString('changelog.', $crawler->filter('body')->text(), 'Roher Übersetzungsschlüssel auf der Seite (AK-32).');
    }

    /** AK-11: Jede Methode außer GET und HEAD wird abgewiesen. */
    public function testSchreibendeMethodenWerdenAbgewiesen(): void
    {
        $client = static::createClient();

        foreach (['POST', 'PUT', 'DELETE', 'PATCH'] as $methode) {
            foreach ([self::LOCALE.'/roadmap', self::LOCALE.'/changelog'] as $pfad) {
                $client->request($methode, $pfad);
                self::assertSame(405, $client->getResponse()->getStatusCode(), sprintf('%s %s muss 405 liefern.', $methode, $pfad));
            }
        }
    }

    /**
     * AK-44: Beliebige Parameter ändern nichts am Inhalt und wirken nicht.
     *
     * ⚠ **AK-44 ist hier nur teilweise erfüllt, und die Ursache liegt außerhalb
     * dieses Features.** Der `hreflang`-Block in `base.html.twig` (Feature B24)
     * übernimmt die Abfragezeichenfolge in die Alternativ-Verweise — **auf jeder
     * Seite des Projekts**, nachgemessen an `/presse`, `/open`, `/about` und
     * `/restaurants`. Die Eingabe erscheint also escaped in der Antwort, obwohl
     * das Kriterium „keine Eingabe von ihm in der Antwort" verlangt.
     *
     * Geprüft wird deshalb, was dieses Feature verantwortet und was das Kriterium
     * schützen soll: Der Controller liest nichts, der Inhalt ändert sich nicht,
     * es gibt keinen Serverfehler, und nichts wird ungefiltert ausgegeben.
     * Der Rest steht als OF-09 in `spec.md`.
     */
    public function testQueryParameterWirkenNichtUndWerdenNichtUngefiltertAusgegeben(): void
    {
        $client = static::createClient();

        $ohne = $client->request('GET', self::LOCALE.'/roadmap');
        self::assertResponseIsSuccessful();
        $spaltenOhne = $ohne->filter('section[aria-labelledby^="stage-"] li')->count();

        $mit = $client->request('GET', self::LOCALE.'/roadmap?sort=<script>alert(1)</script>&page=99&stage=considered');
        self::assertResponseIsSuccessful('Ein unbekannter Parameter darf keinen Serverfehler auslösen.');

        self::assertSame(
            $spaltenOhne,
            $mit->filter('section[aria-labelledby^="stage-"] li')->count(),
            'Ein Query-Parameter hat den Inhalt verändert — der Controller liest doch etwas.',
        );

        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('<script>alert(1)', $inhalt, 'Eingabe wird ungefiltert ausgegeben.');
        self::assertStringNotContainsString('alert(1)</script>', $inhalt);
    }

    /**
     * AK-14, AK-43: Eine nie freigegebene Idee steht an keiner Stelle des
     * ausgelieferten Quelltextes — auch nicht, wenn sie bereits „Geplant" ist.
     *
     * Der Filter steht in den Abfragekriterien, nicht im Template: Ausblenden im
     * Template hieße, den Titel trotzdem auszuliefern.
     */
    public function testWartendeIdeeErscheintNicht(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $geheim = 'Geheime wartende Idee ZZTOP';
        $idee = (new BoardIdea())
            ->setTitle($geheim)
            ->setDescription('Noch nicht freigegeben.')
            ->setSlug('geheime-wartende-idee')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED);
        $em->persist($idee);
        $em->flush();

        $client->request('GET', self::LOCALE.'/roadmap');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString($geheim, (string) $client->getResponse()->getContent());
    }

    /**
     * EC-10: Der sprachfreie Kurzlink geht in EINEM Sprung auf die Sprachfassung —
     * und mit 302, nicht 301.
     *
     * ⚠ Ein 301 bliebe in fremden Browsern stehen. Das war der teure Teil von
     * BF-100, nicht die Schleife selbst.
     */
    public function testKurzlinksLeitenEinmaligUndNichtDauerhaftWeiter(): void
    {
        $client = static::createClient();

        foreach (['/roadmap' => 'app_roadmap_index', '/changelog' => 'app_changelog_index'] as $kurz => $ziel) {
            $client->request('GET', $kurz);
            self::assertSame(302, $client->getResponse()->getStatusCode(), $kurz.' muss mit 302 weiterleiten, nicht mit 301.');

            $client->followRedirect();
            self::assertResponseIsSuccessful($kurz.' muss nach einem Sprung ankommen.');
        }
    }

    /** AK-02: Beide Seiten sind aus der Fußzeile jeder öffentlichen Seite erreichbar. */
    public function testFusszeileVerweistAufBeideSeiten(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        self::assertGreaterThan(0, $crawler->filter('footer a[href$="/roadmap"]')->count(), 'Fußzeile führt nicht zur Roadmap.');
        self::assertGreaterThan(0, $crawler->filter('footer a[href$="/changelog"]')->count(), 'Fußzeile führt nicht zum Changelog.');
    }

    /** AK-04, AK-06, AK-37: Drei Spalten, keine vierte — und nirgends ein Datum. */
    public function testDreiSpaltenOhneDatum(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $spalten = $crawler->filter('section[aria-labelledby^="stage-"]');
        self::assertCount(3, $spalten, 'Es muss genau drei Spalten geben (AK-04).');

        foreach ($spalten as $spalte) {
            $text = $spalte->textContent;
            self::assertDoesNotMatchRegularExpression(
                '/\b(Q[1-4]|\d{1,2}\.\d{1,2}\.\d{4}|\d{4}-\d{2}-\d{2}|\d{1,3}\s?%)\b/u',
                $text,
                'In einer Roadmap-Spalte steht ein Datum, ein Quartal oder eine Fortschrittsangabe (AK-06).',
            );
        }
    }

    /**
     * AK-07, AK-08: Der Block „Bewusst nicht gebaut" steht außerhalb der Spalten.
     *
     * ⚠ **Die erwartete Zahl wird abgeleitet, nicht genannt (BF-113).** Vorher stand
     * hier `assertCount(8, …)`; ein neunter zurückgestellter Punkt hätte den Lauf rot
     * gemacht, obwohl alles richtig ist — und genau das ist der Regelbetrieb: OF-03
     * sieht eine Roadmap-Durchsicht bei jedem Release vor, OF-04 die Rückstufung nach
     * zwölf Monaten. Einträge wandern planmäßig.
     *
     * Der abgeleitete Wert prüft mehr als die feste Zahl: die **Kopplung** zwischen
     * Registry und Seite. Verschluckt das Template einen Punkt, weicht die Zahl ab.
     */
    public function testZurueckgestelltesStehtAusserhalbDerSpalten(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $erwartet = \count((new RoadmapRegistry())->shelved());
        self::assertGreaterThan(0, $erwartet, 'Die Registry führt keinen zurückgestellten Punkt — der Lauf prüft ins Leere.');

        $block = $crawler->filter('section[aria-labelledby="shelved-heading"]');
        self::assertCount(1, $block, 'Der Block „Bewusst nicht gebaut" fehlt (AK-07).');
        self::assertCount($erwartet, $block->filter('li'), sprintf(
            'Die Seite zeigt nicht die %d zurückgestellten Punkte, die die Registry führt.',
            $erwartet,
        ));
        self::assertCount(0, $block->filter('section[aria-labelledby^="stage-"]'), 'Der Block darf in keiner Spalte liegen (AK-08).');
    }

    /** AK-10: Von der Roadmap geht es in einem Klick zu Changelog und Board. */
    public function testWeiterlesenFuehrtZuChangelogUndBoard(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $block = $crawler->filter('section[aria-labelledby="more-heading"]');
        self::assertCount(1, $block);
        self::assertGreaterThan(0, $block->filter('a[href$="/changelog"]')->count());
        self::assertGreaterThan(0, $block->filter('a[href*="/community/ideen"]')->count());
    }

    /**
     * AK-20, AK-21: Die Seite zeigt genau die Einträge, die die Registry als
     * sichtbar führt — die stillen erscheinen nicht.
     *
     * ⚠ **Die erwartete Zahl wird abgeleitet, nicht genannt (BF-112).** Vorher stand
     * hier `assertCount(10, …)`; damit wurde der Lauf bei **jedem** Release rot,
     * obwohl alles richtig war — beim Deploy von `v2026.08.30.3` zum ersten Mal.
     * Ein Prüflauf, der bei jedem korrekten Vorgang anschlägt, wird nach dem dritten
     * Mal ignoriert, und dann fehlt genau die Absicherung, für die er gebaut wurde.
     *
     * Der abgeleitete Wert prüft mehr als die feste Zahl: nicht eine Momentaufnahme,
     * sondern die **Kopplung** zwischen Registry und Seite. Verschluckt das Template
     * einen gezeigten Eintrag oder rendert es einen stillen, weicht die Zahl ab.
     */
    public function testChangelogZeigtGenauDieSichtbarenEintraege(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/changelog');

        $registry = new ChangelogRegistry();
        $gezeigt = \count(array_filter(
            $registry->notes(),
            static fn (ReleaseNote $n): bool => $n->isShown(),
        ));
        $erwartet = $gezeigt + (null !== $registry->summary() ? 1 : 0);

        self::assertGreaterThan(0, $gezeigt, 'Die Registry führt keinen einzigen sichtbaren Eintrag — der Lauf prüft ins Leere.');
        self::assertCount($erwartet, $crawler->filter('article'), sprintf(
            'Die Seite zeigt nicht die %d Einträge, die die Registry als sichtbar führt (%d Releases plus Sammelzeile).',
            $erwartet,
            $gezeigt,
        ));

        $text = $crawler->filter('body')->text();
        foreach (['2026.08.30.1', '2026.08.29.1', '2026.08.06'] as $still) {
            self::assertStringNotContainsString($still, $text, 'Ein stilles Release erscheint auf der Seite (AK-21).');
        }
    }

    /** AK-24, AK-51: Verweis auf die technische Fassung, mit Erklärsatz und Sprachhinweis. */
    public function testVerweisAufDieTechnischeFassung(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/changelog');

        $block = $crawler->filter('section[aria-labelledby="repo-heading"]');
        self::assertCount(1, $block);
        self::assertGreaterThan(0, $block->filter('a[href*="CHANGELOG.md"]')->count(), 'Der Verweis auf CHANGELOG.md fehlt (AK-24).');
        self::assertStringContainsString('Deutsch', $block->text(), 'Der Sprachhinweis fehlt (AK-24).');
        self::assertGreaterThan(120, mb_strlen(trim($block->filter('p')->first()->text())), 'Der Erklärsatz fehlt oder ist zu knapp (AK-51).');
    }

    private function geplanteIdee(KernelBrowser $client, string $titel, string $slug, int $tageAlt = 0): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung zu '.$titel)
            ->setSlug($slug)
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable(sprintf('-%d days', $tageAlt)));

        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    /** AK-12, AK-16, AK-52: Geplante Ideen erscheinen, gekennzeichnet, mit Auswahlregel. */
    public function testGeplanteIdeenErscheinenMitHerkunftUndAuswahlregel(): void
    {
        $client = static::createClient();
        $this->geplanteIdee($client, 'Kartenansicht für unterwegs', 'kartenansicht-unterwegs');

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $spalte = $crawler->filter('section[aria-labelledby="stage-planned"]');
        self::assertStringContainsString('Kartenansicht für unterwegs', $spalte->text());
        self::assertGreaterThan(0, $spalte->filter('a[href*="/community/ideen/"]')->count(), 'Der Verweis ins Board fehlt (AK-15).');
        self::assertStringContainsString('Zustimmungen', $spalte->text(), 'Die Zustimmungszahl fehlt (AK-15).');
        // AK-52: Die Auswahlregel steht auch bei einer einzigen Idee.
        self::assertStringContainsString('zehn geplanten Ideen', $spalte->text(), 'Die Auswahlregel wird nicht ausgewiesen (AK-52).');
    }

    /** AK-13: Ideen anderer Status erscheinen nicht. */
    public function testNurGeplanteIdeenErscheinen(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        foreach ([BoardIdeaStatus::NEW, BoardIdeaStatus::REVIEWING, BoardIdeaStatus::DONE, BoardIdeaStatus::DECLINED] as $i => $status) {
            $idee = (new BoardIdea())
                ->setTitle('Fremdstatus '.$status->value.' XYQ')
                ->setDescription('…')
                ->setSlug('fremdstatus-'.$i)
                ->setLocale('de')
                ->setStatus($status)
                ->setPublishedAt(new \DateTimeImmutable());
            $em->persist($idee);
        }
        $em->flush();

        $client->request('GET', self::LOCALE.'/roadmap');
        $inhalt = (string) $client->getResponse()->getContent();

        foreach (['new', 'reviewing', 'done', 'declined'] as $status) {
            self::assertStringNotContainsString('Fremdstatus '.$status.' XYQ', $inhalt);
        }
    }

    /** AK-39: Der Verfasser einer Idee erscheint nirgends. */
    public function testVerfasserErscheintNicht(): void
    {
        $client = static::createClient();
        $nutzer = $this->user($client, 'user@endlech.lu');

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle('Idee mit Verfasser')
            ->setDescription('…')
            ->setSlug('idee-mit-verfasser')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setSubmittedBy($nutzer)
            ->setPublishedAt(new \DateTimeImmutable());
        $em->persist($idee);
        $em->flush();

        $client->request('GET', self::LOCALE.'/roadmap');
        $inhalt = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('Idee mit Verfasser', $inhalt);
        self::assertStringNotContainsString($nutzer->getEmail(), $inhalt);
        self::assertStringNotContainsString((string) $nutzer->getName(), $inhalt);
    }

    /** AK-33: Der Titel einer fremdsprachigen Idee ist ausgezeichnet. */
    public function testFremdsprachigerTitelIstAusgezeichnet(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = (new BoardIdea())
            ->setTitle('Une carte interactive')
            ->setDescription('…')
            ->setSlug('carte-interactive')
            ->setLocale('fr')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable());
        $em->persist($idee);
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $titel = $crawler->filter('[lang="fr"]');
        self::assertGreaterThan(0, $titel->count(), 'Der Titel trägt keine Sprachauszeichnung (AK-33).');
        self::assertStringContainsString('Une carte interactive', $titel->text());
    }

    /** AK-42: Keine Ressource von einem fremden Host. */
    public function testKeineFremdeRessource(): void
    {
        $client = static::createClient();

        foreach ([self::LOCALE.'/roadmap', self::LOCALE.'/changelog'] as $pfad) {
            $crawler = $client->request('GET', $pfad);

            foreach (['img[src^="http"]', 'script[src^="http"]', 'link[rel="stylesheet"][href^="http"]', 'iframe'] as $selektor) {
                self::assertCount(0, $crawler->filter($selektor), sprintf('%s lädt eine fremde Ressource (%s).', $pfad, $selektor));
            }
        }
    }

    /** AK-50: Kein Schlüssel, kein interner Pfad im ausgelieferten Quelltext. */
    public function testKeinGeheimnisImQuelltext(): void
    {
        $client = static::createClient();

        foreach ([self::LOCALE.'/roadmap', self::LOCALE.'/changelog'] as $pfad) {
            $client->request('GET', $pfad);
            $inhalt = (string) $client->getResponse()->getContent();

            foreach (['APP_SECRET', 'DATABASE_URL', 'BREVO_API_KEY', '/var/www', 'src/Roadmap'] as $verboten) {
                self::assertStringNotContainsString($verboten, $inhalt, sprintf('%s steht im Quelltext von %s.', $verboten, $pfad));
            }
        }
    }
}
