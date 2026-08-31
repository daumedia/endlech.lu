<?php

declare(strict_types=1);

namespace App\Tests\Integration\Roadmap;

use App\Entity\BoardIdea;
use App\Entity\BoardVote;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Roadmap\CommunityRoadmap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Zwischenspeicher und Invalidierung der Community-Spalte (AK-46, AK-47).
 *
 * ⚠ **Warum hier und nicht über die Seite.** Der Testclient bootet den Kernel bei
 * jedem Request neu, und selbst mit `disableReboot()` leert Symfonys
 * `services_resetter` den Array-Adapter zwischen zwei Requests. Über HTTP wäre der
 * Zwischenspeicher also **immer** leer — ein Lauf, der ihn dort zu belegen
 * vorgäbe, prüfte nichts und wäre grün, ob der Cache arbeitet oder nicht.
 * Gemessen am 2026-08-30: derselbe Aufruf liefert am Dienst den gespeicherten,
 * über die Seite den frisch geladenen Stand.
 *
 * Innerhalb **eines** Kernels ist der Nachweis dagegen echt — und genau so läuft
 * es in Produktion, wo der Dateisystem-Adapter über Requests hinweg hält.
 */
final class CommunityRoadmapTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommunityRoadmap $roadmap;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->roadmap = self::getContainer()->get(CommunityRoadmap::class);
        $this->roadmap->invalidate();
    }

    private function idee(string $titel, string $slug, BoardIdeaStatus $status = BoardIdeaStatus::PLANNED, bool $published = true): BoardIdea
    {
        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung zu '.$titel)
            ->setSlug($slug)
            ->setLocale('de')
            ->setStatus($status);

        if ($published) {
            $idee->setPublishedAt(new \DateTimeImmutable());
        }

        $this->em->persist($idee);
        $this->em->flush();

        return $idee;
    }

    /**
     * AK-46: Der zweite Aufruf rechnet nicht neu.
     *
     * Nachgewiesen über eine Änderung **am ORM vorbei**: Doctrine feuert dabei kein
     * Ereignis, der Listener greift nicht — sieht der Dienst den alten Wert, kam er
     * aus dem Zwischenspeicher.
     */
    public function testDerZweiteAufrufKommtAusDemZwischenspeicher(): void
    {
        $idee = $this->idee('Gespeicherter Titel', 'gespeicherter-titel');

        self::assertSame(['Gespeicherter Titel'], array_column($this->roadmap->planned()['ideas'], 'title'));

        $this->em->getConnection()->executeStatement(
            'UPDATE board_idea SET title = :neu WHERE id = :id',
            ['neu' => 'Am ORM vorbei geaendert', 'id' => $idee->getId()],
        );

        self::assertSame(
            ['Gespeicherter Titel'],
            array_column($this->roadmap->planned()['ideas'], 'title'),
            'Der zweite Aufruf hat neu abgefragt — der Zwischenspeicher greift nicht.',
        );
    }

    /** AK-47: Ein Statuswechsel über Doctrine verwirft den Zwischenspeicher. */
    public function testEinStatuswechselVerwirftDenZwischenspeicher(): void
    {
        $idee = $this->idee('Wird abgelehnt', 'wird-abgelehnt-int');
        self::assertCount(1, $this->roadmap->planned()['ideas']);

        $idee->setStatus(BoardIdeaStatus::DECLINED);
        $this->em->flush();

        self::assertCount(0, $this->roadmap->planned()['ideas'], 'Der Listener hat den Zwischenspeicher nicht verworfen.');
    }

    /** AK-47: Auch eine zurückgenommene Veröffentlichung wirkt sofort. */
    public function testEineZurueckgenommeneVeroeffentlichungWirktSofort(): void
    {
        $idee = $this->idee('Wird zurückgezogen', 'wird-zurueckgezogen-int');
        self::assertCount(1, $this->roadmap->planned()['ideas']);

        $idee->setPublishedAt(null);
        $this->em->flush();

        self::assertCount(0, $this->roadmap->planned()['ideas']);
    }

    /** AK-47: Eine neue Stimme verwirft ebenfalls — der Listener hängt an BoardVote. */
    public function testEineNeueStimmeVerwirftDenZwischenspeicher(): void
    {
        $idee = $this->idee('Bekommt eine Stimme', 'bekommt-stimme');
        self::assertSame(0, $this->roadmap->planned()['ideas'][0]['votes']);

        $nutzer = (new User())->setEmail('stimmt-ab@example.test')->setName('Abstimmer')->setPassword('x')->setIsVerified(true);
        $this->em->persist($nutzer);
        $this->em->persist((new BoardVote())->setIdea($idee)->setUser($nutzer));
        $this->em->flush();

        self::assertSame(1, $this->roadmap->planned()['ideas'][0]['votes'], 'Die neue Stimme ist nicht sichtbar geworden.');
    }

    /**
     * AK-47, AK-48: Die Kontolöschung verwirft den Zwischenspeicher.
     *
     * ⚠ **Der Grund für den Listener an `User`.** Die Stimmen fallen über die
     * Fremdschlüssel-Kaskade **in der Datenbank** weg; Doctrine feuert dafür kein
     * `BoardVote`-Ereignis. Ohne diesen Fall stünde bis zu eine Stunde lang eine zu
     * hohe Zustimmungszahl auf der Roadmap.
     */
    public function testEineKontoloeschungVerwirftDenZwischenspeicher(): void
    {
        $idee = $this->idee('Idee mit Fremdstimme', 'idee-fremdstimme');

        $nutzer = (new User())->setEmail('verschwindet@example.test')->setName('Verschwindet')->setPassword('x')->setIsVerified(true);
        $this->em->persist($nutzer);
        $this->em->persist((new BoardVote())->setIdea($idee)->setUser($nutzer));
        $this->em->flush();

        self::assertSame(1, $this->roadmap->planned()['ideas'][0]['votes']);

        $this->em->remove($nutzer);
        $this->em->flush();

        self::assertSame(
            0,
            $this->roadmap->planned()['ideas'][0]['votes'],
            'Nach der Kontolöschung steht eine zu hohe Zustimmungszahl (AK-48).',
        );
    }

    /** AK-45: Auch bei vielen geplanten Ideen wird nie mehr als die Obergrenze geladen. */
    public function testDieObergrenzeWirktInDerAbfrage(): void
    {
        for ($i = 1; $i <= 14; ++$i) {
            $this->idee(sprintf('Massenidee %02d', $i), 'massenidee-int-'.$i);
        }

        $paket = $this->roadmap->planned();

        self::assertCount(CommunityRoadmap::MAX_ITEMS, $paket['ideas']);
        self::assertSame(4, $paket['more']);
    }

    /** AK-13, AK-14: Nur freigegebene Ideen mit Status „Geplant". */
    public function testNurFreigegebeneGeplanteIdeenErscheinen(): void
    {
        $this->idee('Wartet noch', 'wartet-noch', BoardIdeaStatus::PLANNED, false);
        $this->idee('Ist umgesetzt', 'ist-umgesetzt', BoardIdeaStatus::DONE);
        $this->idee('Ist geplant', 'ist-geplant');

        self::assertSame(['Ist geplant'], array_column($this->roadmap->planned()['ideas'], 'title'));
    }

    /** EC-11: Ohne Zwischenspeicher wird gerechnet, statt zu scheitern. */
    public function testOhneZwischenspeicherWirdGerechnet(): void
    {
        $this->idee('Frisch gerechnet', 'frisch-gerechnet');

        $this->roadmap->invalidate();

        self::assertSame(['Frisch gerechnet'], array_column($this->roadmap->planned()['ideas'], 'title'));
    }
}
