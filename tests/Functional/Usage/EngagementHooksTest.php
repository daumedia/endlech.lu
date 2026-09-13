<?php

namespace App\Tests\Functional\Usage;

use App\Entity\BoardIdea;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/** Feature 11 · Engagement: Zustimmung, Presse-Kit, Datensatz (AK-18, AK-19). */
final class EngagementHooksTest extends AbstractWebTestCase
{
    private function zustimmFormular(Crawler $seite): Crawler
    {
        return $seite->filter('form[action$="/zustimmen"]');
    }

    /**
     * AK-18 · Nur an einer noch nicht unterstützten Idee meldet das Absenden `zustimmung_gegeben` —
     * das Zurückziehen zählt nicht. Das Formular trägt keine Konto- oder Ideenkennung als Daten.
     */
    public function testZustimmungNurWennNochNichtUnterstuetzt(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->loginAs($client, 'user@endlech.lu');

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle('Messung der Zustimmung')
            ->setDescription('Eine Idee, an der die Zustimmung gemessen wird.')
            ->setSlug('messung-zustimmung')
            ->setLocale('de')
            ->setPublishedAt(new \DateTimeImmutable());
        $em->persist($idee);
        $em->flush();

        $pfad = self::LOCALE.'/community/ideen/'.$idee->getId().'-messung-zustimmung';

        $formular = $this->zustimmFormular($client->request('GET', $pfad));
        self::assertCount(1, $formular);
        self::assertSame('usage-event', $formular->attr('data-controller'));
        self::assertSame('submit->usage-event#track', $formular->attr('data-action'));
        self::assertSame('zustimmung_gegeben', $formular->attr('data-usage-event-name-value'));
        self::assertNull($formular->attr('data-usage-event-data-value'));

        $client->submit($formular->form());

        $danach = $this->zustimmFormular($client->request('GET', $pfad));
        self::assertCount(1, $danach);
        self::assertNull($danach->attr('data-controller'), 'Das Zurückziehen darf nicht als Zustimmung zählen.');
    }

    public function testPresseKitMeldetDenDownload(): void
    {
        $client = static::createClient();
        $link = $client->request('GET', self::LOCALE.'/presse')->filter('a[download]');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $link);
        self::assertSame('click->usage-event#track', $link->attr('data-action'));
        self::assertSame('presse_kit_geladen', $link->attr('data-usage-event-name-value'));
    }

    public function testDatensatzMeldetSeinFormatDieKennzahlenNicht(): void
    {
        $client = static::createClient();
        $seite = $client->request('GET', self::LOCALE.'/open');
        self::assertResponseIsSuccessful();

        $formate = $seite->filter('a[data-usage-event-name-value="datensatz_geladen"]')->each(
            static fn (Crawler $k): array => json_decode((string) $k->attr('data-usage-event-data-value'), true),
        );
        self::assertSame([['format' => 'csv'], ['format' => 'json']], $formate);

        // Die maschinenlesbaren Kennzahlen (/open.json) sind kein Datensatz-Download.
        self::assertNull($seite->filter('a[href="/open.json"]')->attr('data-controller'));
    }
}
