<?php

declare(strict_types=1);

namespace App\Tests\Integration\Roadmap;

use App\Account\AccountDataExporter;
use App\Account\AccountDeleter;
use App\Entity\BoardIdea;
use App\Entity\BoardVote;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Roadmap\CommunityRoadmap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Was eine Kontolöschung mit der Roadmap macht (AK-48, AK-49).
 *
 * ⚠ **Über den echten Löschweg**, nicht über ein `remove()` von Hand: `AccountDeleter`
 * ist der Dienst, den die Profilseite ruft. Er löscht wartende Ideen des Nutzers
 * ausdrücklich, bevor das Konto verschwindet — die Stimmen fallen dagegen über die
 * Fremdschlüssel-Kaskade **in der Datenbank** weg, und genau dafür hängt
 * `RoadmapCacheListener` zusätzlich an `User::postRemove`.
 *
 * Angelegt von der Qualitätssicherung am 2026-08-30.
 */
final class RoadmapAccountDeletionTest extends KernelTestCase
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

    /**
     * AK-48: Nach der Kontolöschung steht die Idee unverändert auf der Roadmap —
     * ohne Verfasserbezug — und die Zustimmungszahl stimmt.
     */
    public function testNachKontoloeschungStimmtDieZustimmungszahl(): void
    {
        $verfasser = (new User())->setEmail('qa-verfasser@example.test')->setName('QA Verfasser')
            ->setPassword('x')->setIsVerified(true);
        $stimmgeber = (new User())->setEmail('qa-stimmgeber@example.test')->setName('QA Stimmgeber')
            ->setPassword('x')->setIsVerified(true);
        $this->em->persist($verfasser);
        $this->em->persist($stimmgeber);

        $idee = (new BoardIdea())
            ->setTitle('Idee überlebt die Löschung')
            ->setDescription('…')
            ->setSlug('qa-ueberlebt')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setSubmittedBy($verfasser)
            ->setPublishedAt(new \DateTimeImmutable());
        $this->em->persist($idee);
        $this->em->persist((new BoardVote())->setIdea($idee)->setUser($stimmgeber));
        $this->em->flush();

        $vorher = $this->roadmap->planned()['ideas'];
        self::assertCount(1, $vorher);
        self::assertSame(1, $vorher[0]['votes']);

        // Der echte Löschweg der Profilseite.
        self::getContainer()->get(AccountDeleter::class)->delete($stimmgeber);

        $nachher = $this->roadmap->planned()['ideas'];
        self::assertCount(1, $nachher, 'Die veröffentlichte Idee muss stehen bleiben (AK-48).');
        self::assertSame(
            0,
            $nachher[0]['votes'],
            'Nach der Kontolöschung steht eine zu hohe Zustimmungszahl — der Listener an User greift nicht.',
        );

        // Und der Verfasser: Idee bleibt, Bezug wird null.
        // ⚠ Neu laden — der erste Löschvorgang hat den Entity-Manager verändert,
        // eine gehaltene Referenz gilt Doctrine danach als losgelöst.
        $verfasserId = $verfasser->getId();
        $this->em->clear();
        $verfasser = $this->em->getRepository(User::class)->find($verfasserId);
        self::assertNotNull($verfasser);

        self::getContainer()->get(AccountDeleter::class)->delete($verfasser);
        $this->em->clear();

        $erneut = $this->em->getRepository(BoardIdea::class)->findOneBy(['slug' => 'qa-ueberlebt']);
        self::assertNotNull($erneut, 'Die veröffentlichte Idee darf mit dem Konto nicht verschwinden.');
        self::assertNull($erneut->getSubmittedBy(), 'Der Verfasserbezug muss null werden.');
        self::assertCount(1, $this->roadmap->planned()['ideas']);
    }

    /** AK-49: Der Datenexport bekommt durch dieses Feature keinen Abschnitt. */
    public function testDerDatenexportBleibtUnveraendert(): void
    {
        $nutzer = $this->em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertNotNull($nutzer);

        $export = self::getContainer()->get(AccountDataExporter::class)->export($nutzer);
        $schluessel = array_keys($export);

        foreach ($schluessel as $k) {
            self::assertStringNotContainsStringIgnoringCase('roadmap', (string) $k);
            self::assertStringNotContainsStringIgnoringCase('changelog', (string) $k);
        }
    }
}
