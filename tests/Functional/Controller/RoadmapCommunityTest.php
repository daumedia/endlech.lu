<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Entity\BoardVote;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Roadmap\CommunityRoadmap;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Die Community-Spalte der Roadmap: Obergrenze, Reihenfolge, Rücknahme.
 */
final class RoadmapCommunityTest extends AbstractWebTestCase
{
    private function idee(KernelBrowser $client, string $titel, string $slug, int $stimmen = 0, int $tageAlt = 0): BoardIdea
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

        for ($i = 0; $i < $stimmen; ++$i) {
            $nutzer = (new User())
                ->setEmail(sprintf('stimme-%s-%d@example.test', $slug, $i))
                ->setName('Stimmgeber '.$i)
                ->setPassword('x')
                ->setIsVerified(true);
            $em->persist($nutzer);
            $em->persist((new BoardVote())->setIdea($idee)->setUser($nutzer));
        }

        $em->flush();

        return $idee;
    }

    /**
     * AK-17, EC-04: Elf geplante Ideen ergeben zehn Karten und den Hinweis auf
     * **genau eine** weitere — nicht auf „weitere".
     */
    public function testElfIdeenErgebenZehnKartenUndEinenHinweisAufEine(): void
    {
        $client = static::createClient();

        for ($i = 1; $i <= 11; ++$i) {
            $this->idee($client, sprintf('Geplante Idee %02d', $i), 'geplante-idee-'.$i, $i);
        }

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        self::assertResponseIsSuccessful();

        $spalte = $crawler->filter('section[aria-labelledby="stage-planned"]');
        $karten = $spalte->filter('h4');

        self::assertCount(CommunityRoadmap::MAX_ITEMS, $karten, 'Es dürfen höchstens zehn Community-Ideen erscheinen.');
        self::assertStringContainsString('Eine weitere geplante Idee', $spalte->text(), 'Bei genau einer übrigen Idee muss die Einzahl stehen (EC-04).');
        self::assertStringNotContainsString('Geplante Idee 01', $spalte->text(), 'Die Idee mit den wenigsten Zustimmungen darf nicht erscheinen.');
    }

    /** AK-17: Sortiert wird nach Zustimmungen, absteigend. */
    public function testDieIdeeMitDenMeistenZustimmungenStehtOben(): void
    {
        $client = static::createClient();
        $this->idee($client, 'Wenig Zuspruch', 'wenig-zuspruch', 1);
        $this->idee($client, 'Viel Zuspruch', 'viel-zuspruch', 5);

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        $titel = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->each(
            static fn ($n) => trim($n->text()),
        );

        self::assertSame(['Viel Zuspruch', 'Wenig Zuspruch'], $titel);
    }

    /**
     * EC-03: Bei Gleichstand steht die neuere oben — und die Reihenfolge ist
     * zwischen zwei Aufrufen stabil.
     */
    public function testBeiGleichstandStehtDieNeuereObenUndBleibtStabil(): void
    {
        $client = static::createClient();
        $this->idee($client, 'Aeltere Idee', 'aeltere-idee', 2, 30);
        $this->idee($client, 'Neuere Idee', 'neuere-idee', 2, 1);

        $ersteRunde = null;
        for ($lauf = 0; $lauf < 2; ++$lauf) {
            $crawler = $client->request('GET', self::LOCALE.'/roadmap');
            $titel = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->each(
                static fn ($n) => trim($n->text()),
            );

            self::assertSame(['Neuere Idee', 'Aeltere Idee'], $titel);

            if (null === $ersteRunde) {
                $ersteRunde = $titel;
            } else {
                self::assertSame($ersteRunde, $titel, 'Die Reihenfolge wechselt zwischen zwei Aufrufen.');
            }
        }
    }

    /**
     * AK-18, EC-02: Wird eine Idee abgelehnt, ist sie beim nächsten Aufruf weg —
     * ohne Deploy und ohne dass jemand den Zwischenspeicher von Hand leert.
     *
     * ⚠ **Dies ist NICHT der Nachweis für den Entity-Listener** — der steht in
     * `tests/Integration/Roadmap/CommunityRoadmapTest.php`. Hier wird belegt, dass
     * die ausgelieferte Seite den aktuellen Stand zeigt.
     */
    public function testEineAbgelehnteIdeeVerschwindetOhneWeiterenHandgriff(): void
    {
        $client = static::createClient();
        // ⚠ Der Zwischenspeicher wird im Test zwischen zwei Requests ohnehin
        // geleert (services_resetter). Dieser Lauf belegt deshalb, dass die SEITE
        // den aktuellen Stand zeigt — **nicht**, dass der Listener greift. Dafür:
        // tests/Integration/Roadmap/CommunityRoadmapTest.php.
        $client->disableReboot();
        $idee = $this->idee($client, 'Wird gleich abgelehnt', 'wird-abgelehnt', 3);

        $client->request('GET', self::LOCALE.'/roadmap');
        self::assertStringContainsString('Wird gleich abgelehnt', (string) $client->getResponse()->getContent());

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee->setStatus(BoardIdeaStatus::DECLINED);
        $em->flush();

        $client->request('GET', self::LOCALE.'/roadmap');
        self::assertStringNotContainsString('Wird gleich abgelehnt', (string) $client->getResponse()->getContent());
    }

    /** AK-18: Dasselbe, wenn die Veröffentlichung zurückgenommen wird. */
    public function testEineDepublizierteIdeeVerschwindetEbenso(): void
    {
        $client = static::createClient();
        // ⚠ Der Zwischenspeicher wird im Test zwischen zwei Requests ohnehin
        // geleert (services_resetter). Dieser Lauf belegt deshalb, dass die SEITE
        // den aktuellen Stand zeigt — **nicht**, dass der Listener greift. Dafür:
        // tests/Integration/Roadmap/CommunityRoadmapTest.php.
        $client->disableReboot();
        $idee = $this->idee($client, 'Wird zurückgezogen', 'wird-zurueckgezogen', 4);

        $client->request('GET', self::LOCALE.'/roadmap');
        self::assertStringContainsString('Wird zurückgezogen', (string) $client->getResponse()->getContent());

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee->setPublishedAt(null);
        $em->flush();

        $client->request('GET', self::LOCALE.'/roadmap');
        self::assertStringNotContainsString('Wird zurückgezogen', (string) $client->getResponse()->getContent());
    }

    /** AK-17: Eine neue Zustimmung ändert die Reihenfolge der Spalte. */
    public function testEineNeueZustimmungWirktSofort(): void
    {
        $client = static::createClient();
        // ⚠ Der Zwischenspeicher wird im Test zwischen zwei Requests ohnehin
        // geleert (services_resetter). Dieser Lauf belegt deshalb, dass die SEITE
        // den aktuellen Stand zeigt — **nicht**, dass der Listener greift. Dafür:
        // tests/Integration/Roadmap/CommunityRoadmapTest.php.
        $client->disableReboot();
        $a = $this->idee($client, 'Idee A', 'idee-a', 1);
        $this->idee($client, 'Idee B', 'idee-b', 3);

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        $vorher = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->each(static fn ($n) => trim($n->text()));
        self::assertSame(['Idee B', 'Idee A'], $vorher);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        for ($i = 0; $i < 5; ++$i) {
            $nutzer = (new User())->setEmail(sprintf('nachzuegler-%d@example.test', $i))->setName('N'.$i)->setPassword('x')->setIsVerified(true);
            $em->persist($nutzer);
            $em->persist((new BoardVote())->setIdea($a)->setUser($nutzer));
        }
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/roadmap');
        $nachher = $crawler->filter('section[aria-labelledby="stage-planned"] h4')->each(static fn ($n) => trim($n->text()));
        self::assertSame(['Idee A', 'Idee B'], $nachher, 'Die neue Zustimmung hat die Reihenfolge nicht verändert.');
    }

    /**
     * EC-01: Ohne eine einzige geplante Idee zeigt die Spalte nur die kuratierten
     * Vorhaben — kein leerer Community-Block, kein Hinweis auf ein Versäumnis.
     */
    public function testOhneCommunityIdeenBleibtDieSpalteRuhig(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/roadmap');

        $spalte = $crawler->filter('section[aria-labelledby="stage-planned"]');
        self::assertGreaterThan(0, $spalte->filter('h3')->count(), 'Die kuratierten Vorhaben müssen stehen bleiben.');
        self::assertStringNotContainsString('Aus dem Ideen-Board', $spalte->text(), 'Ohne Ideen darf der Community-Block nicht erscheinen.');
        self::assertCount(0, $spalte->filter('h4'));
    }

    /**
     * AK-45: Auch bei vielen geplanten Ideen lädt kein Aufruf mehr als die zehn
     * angezeigten.
     */
    public function testDieAbfrageHoltNieMehrAlsZehnIdeen(): void
    {
        $client = static::createClient();
        for ($i = 1; $i <= 25; ++$i) {
            $this->idee($client, sprintf('Massenidee %02d', $i), 'massenidee-'.$i, $i % 4);
        }

        $roadmap = $client->getContainer()->get(CommunityRoadmap::class);
        $paket = $roadmap->planned();

        self::assertCount(CommunityRoadmap::MAX_ITEMS, $paket['ideas'], 'Es dürfen nie mehr als zehn Ideen geladen werden.');
        self::assertSame(15, $paket['more'], 'Die Zahl der nicht gezeigten Ideen stimmt nicht.');
    }
}
