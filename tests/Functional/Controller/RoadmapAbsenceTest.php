<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Account\AccountDataExporter;
use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Nachweise über Dinge, die dieses Feature **nicht** tut (AK-41, AK-49).
 *
 * ⚠ Ein Negativkriterium lässt sich nicht bauen, nur belegen. Deshalb stehen diese
 * Läufe hier und nicht als Aufgabe im Code.
 */
final class RoadmapAbsenceTest extends AbstractWebTestCase
{
    /**
     * AK-41: Kein Aufruf schreibt eine personenbezogene Angabe aus dem Board ins
     * Protokoll.
     *
     * Geprüft am tatsächlich geschriebenen Protokoll, nicht am Quelltext: Ein
     * `console.log`-Äquivalent in einer Abhängigkeit fiele so ebenfalls auf.
     */
    public function testKeinAufrufProtokolliertBoardDaten(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $titel = 'Streng vertrauliche Ideenbezeichnung QQX';
        $em->persist((new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung, die nirgends im Protokoll auftauchen darf.')
            ->setSlug('vertrauliche-idee')
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::PLANNED)
            ->setPublishedAt(new \DateTimeImmutable()));
        $em->flush();

        $logDatei = $client->getContainer()->getParameter('kernel.logs_dir').'/test.log';
        $vorher = is_file($logDatei) ? (int) filesize($logDatei) : 0;

        for ($i = 0; $i < 20; ++$i) {
            $client->request('GET', self::LOCALE.'/roadmap');
            self::assertResponseIsSuccessful();
        }

        if (!is_file($logDatei)) {
            self::assertTrue(true, 'Es wird überhaupt kein Protokoll geschrieben.');

            return;
        }

        $neu = (string) file_get_contents($logDatei, false, null, $vorher);
        self::assertStringNotContainsString($titel, $neu, 'Der Titel einer Idee steht im Protokoll.');
        self::assertStringNotContainsString('vertrauliche-idee', $neu);
    }

    /**
     * AK-49: Der Datenexport aus Feature `01` bleibt unverändert — dieses Feature
     * speichert nichts über einen Nutzer und hat dort deshalb nichts zu suchen.
     */
    public function testDerDatenexportKenntKeinenRoadmapAbschnitt(): void
    {
        $client = static::createClient();
        $nutzer = $this->user($client, 'user@endlech.lu');

        $export = $client->getContainer()->get(AccountDataExporter::class)->export($nutzer);
        $flach = strtolower(json_encode($export, \JSON_THROW_ON_ERROR));

        foreach (['roadmap', 'changelog', 'releasenote'] as $begriff) {
            self::assertStringNotContainsString(
                '"'.$begriff,
                $flach,
                sprintf('Der Datenexport führt einen Abschnitt „%s" — dieses Feature speichert nichts.', $begriff),
            );
        }
    }

    /**
     * ⚠ **Der Nachweis für AK-46 und AK-47 steht NICHT hier**, sondern in
     * `tests/Integration/Roadmap/CommunityRoadmapTest.php`.
     *
     * Über HTTP ist er nicht führbar: Der Testclient bootet den Kernel bei jedem
     * Request neu, und selbst mit `disableReboot()` leert Symfonys
     * `services_resetter` den Array-Adapter zwischen zwei Requests. Ein Lauf, der
     * den Zwischenspeicher hier zu belegen vorgäbe, wäre grün, ob er arbeitet oder
     * nicht — gemessen am 2026-08-30: derselbe Aufruf liefert am Dienst den
     * gespeicherten, über die Seite den frisch geladenen Stand.
     */
}
