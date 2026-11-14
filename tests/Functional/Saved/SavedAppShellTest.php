<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Tests\AbstractWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 17, T22: „Gespeichert“ in der App-Hülle — untere Leiste, Kopfzeile, Profil,
 * Ansagebereich für Stream-Meldungen.
 */
final class SavedAppShellTest extends AbstractWebTestCase
{
    public function testAk41AngemeldetErsetztGespeichertUeberUns(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $leiste = $this->leiste($client->request('GET', self::LOCALE.'/restaurants'));

        self::assertSame(
            [self::LOCALE.'/', self::LOCALE.'/restaurants', self::LOCALE.'/gespeichert', self::LOCALE.'/profile'],
            $leiste->filter('a')->each(static fn (Crawler $a): string => (string) $a->attr('href')),
        );
        self::assertStringContainsString('Gespeichert', $leiste->text());
        self::assertStringNotContainsString('Über uns', $leiste->text());
    }

    public function testAk41GastBehaeltDieLeisteWieBisher(): void
    {
        $client = static::createClient();

        $leiste = $this->leiste($client->request('GET', self::LOCALE.'/restaurants'));

        self::assertSame(
            [self::LOCALE.'/', self::LOCALE.'/restaurants', self::LOCALE.'/about', self::LOCALE.'/login'],
            $leiste->filter('a')->each(static fn (Crawler $a): string => (string) $a->attr('href')),
        );
        self::assertStringNotContainsString('Gespeichert', $leiste->text());
    }

    public function testAk41GespeichertIstAufAllenSeitenDesBereichsAktuell(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        foreach (['/gespeichert', '/gespeichert/favoriten'] as $pfad) {
            $leiste = $this->leiste($client->request('GET', self::LOCALE.$pfad));
            self::assertSame(self::LOCALE.'/gespeichert', $leiste->filter('a[aria-current="page"]')->attr('href'), $pfad);
        }
    }

    public function testAk43KopfzeileFuehrtAngemeldetZuGespeichert(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $kopf = $client->request('GET', self::LOCALE.'/about')->filter('header');

        $verweis = $kopf->filter('a[href="'.self::LOCALE.'/gespeichert"]');
        self::assertCount(1, $verweis);
        self::assertSame('Gespeichert', trim($verweis->text()), 'Der Verweis hat einen Namen, auch wenn der Text erst ab lg sichtbar ist.');
    }

    public function testAk43GastHatKeinenVerweisInDerKopfzeile(): void
    {
        $client = static::createClient();

        $kopf = $client->request('GET', self::LOCALE.'/about')->filter('header');

        self::assertCount(0, $kopf->filter('a[href="'.self::LOCALE.'/gespeichert"]'));
    }

    public function testAk44ProfilVerweistAufGespeichert(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $profil = $client->request('GET', self::LOCALE.'/profile');

        self::assertCount(1, $profil->filter('main a[href="'.self::LOCALE.'/gespeichert"]'));
    }

    public function testAk48AnsagebereichStehtLeerAufJederSeite(): void
    {
        $client = static::createClient();

        $bereich = $client->request('GET', self::LOCALE.'/restaurants')->filter('#meldung-live');

        self::assertCount(1, $bereich);
        self::assertSame('status', $bereich->attr('role'));
        self::assertSame('polite', $bereich->attr('aria-live'));
        self::assertSame('', trim($bereich->text()));
    }

    private function leiste(Crawler $seite): Crawler
    {
        $leiste = $seite->filter('nav.fixed.bottom-0');
        self::assertCount(1, $leiste, 'Untere Leiste nicht gefunden.');

        return $leiste;
    }
}
