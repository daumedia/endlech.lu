<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Feature 17, T18: Seiten mit privaten Angaben landen weder im Verlaufsspeicher des Browsers noch
 * in Turbos Schnappschüssen (AK-63).
 *
 * ⚠ Ob „Zurück“ nach dem Abmelden wirklich vom Server lädt, sieht nur ein echter Browser (Turbo-
 * Speicher, Back-Forward-Cache). Dieser Lauf prüft die beiden Signale, auf die sich das stützt.
 */
final class PrivatePageCacheTest extends AbstractWebTestCase
{
    private const string KONTO = 'user@endlech.lu';
    private const string META = '<meta name="turbo-cache-control" content="no-cache">';

    /** Was der Session-Zuhörer setzt — gemessen vor T18 auf beiden Seiten, auch angemeldet. */
    private const string BISHER = 'max-age=0, must-revalidate, private';

    public function testAk63AngemeldetTragenSucheUndDetailseiteNoStoreUndKeinenSchnappschuss(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        foreach ([self::LOCALE.'/restaurants', $this->detailseite($client)] as $pfad) {
            $client->request('GET', $pfad);

            self::assertResponseIsSuccessful($pfad);
            // ⚠ Genau dieser Wert: Er belegt, dass der Session-Zuhörer danach nichts mehr anhängt.
            self::assertSame('no-store, private', $client->getResponse()->headers->get('Cache-Control'), $pfad);
            self::assertStringContainsString(self::META, (string) $client->getResponse()->getContent(), $pfad);
        }
    }

    public function testAk63GastBekommtSucheUndDetailseiteWieBisher(): void
    {
        $client = static::createClient();

        foreach ([self::LOCALE.'/restaurants', $this->detailseite($client)] as $pfad) {
            $client->request('GET', $pfad);

            self::assertResponseIsSuccessful($pfad);
            self::assertSame(self::BISHER, $client->getResponse()->headers->get('Cache-Control'), $pfad);
            self::assertStringNotContainsString('turbo-cache-control', (string) $client->getResponse()->getContent(), $pfad);
        }
    }

    public function testAk63AngemeldetBleibenAndereSeitenUnberuehrt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        $client->request('GET', self::LOCALE.'/about');

        self::assertResponseIsSuccessful();
        self::assertSame(self::BISHER, $client->getResponse()->headers->get('Cache-Control'));
        self::assertStringNotContainsString('turbo-cache-control', (string) $client->getResponse()->getContent());
    }

    private function detailseite(KernelBrowser $client): string
    {
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($lokal);

        return self::LOCALE.'/restaurants/'.$lokal->getId();
    }
}
