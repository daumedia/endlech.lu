<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use App\Usage\UsageEventCatalogue;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 17, T19: die Restaurantsuche mit Herz auf jeder Karte und dem Filter
 * „Nur meine Favoriten“ — für Gäste wirkungslos, für Konten streng getrennt.
 */
final class SearchFavoritesTest extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string KONTO_A = 'user@endlech.lu';
    private const string KONTO_B = 'admin@endlech.lu';

    public function testAk07GastSiehtAufJederKarteEinLeeresHerzUndKeinHaekchen(): void
    {
        $client = static::createClient();
        $seite = $client->request('GET', self::LOCALE.'/restaurants');

        self::assertResponseIsSuccessful();
        $karten = $seite->filter('article');
        self::assertGreaterThan(0, $karten->count(), 'Vorbedingung: Die Suche zeigt Karten.');

        $karten->each(function (Crawler $karte): void {
            self::assertCount(1, $karte->filter('form[action$="/merken"]'), 'Jede Karte trägt genau ein leeres Herz.');
            self::assertCount(0, $karte->filter('form[action$="/nicht-mehr-merken"]'));
            self::assertCount(0, $karte->filter('a form, form a'), 'Das Herz-Formular liegt nicht in einem Verweis.');
        });
        self::assertCount(0, $seite->filter('input[name="favorites"]'), 'AK-39: Kein Häkchen für Gäste.');
        self::assertStringNotContainsString('Deine Notiz', $seite->text());
    }

    public function testAk07HerzKarteUndAnkerTragenDieKennungenDerKarte(): void
    {
        $client = static::createClient();
        $lokal = $this->erstesNachName($client);

        $seite = $client->request('GET', self::LOCALE.'/restaurants?sort=name');

        $karte = $seite->filter('article#karte-'.$lokal->getId());
        self::assertCount(1, $karte, 'Die Karte trägt die Kennung karte-{id}.');
        self::assertStringContainsString('scroll-mt-24', (string) $karte->attr('class'));
        self::assertCount(1, $karte->filter('form#favorit-'.$lokal->getId()), 'Das Herz sitzt in seiner Karte.');
        self::assertSame($lokal->getName(), trim($karte->filter('h3')->text()), 'Der Name bleibt die h3 der Karte.');
    }

    public function testAk39GastMitFavoritenFilterSiehtDieGewoehnlicheListe(): void
    {
        $client = static::createClient();
        $ohne = $this->kartenNamen($client->request('GET', self::LOCALE.'/restaurants?sort=name'));

        $mit = $client->request('GET', self::LOCALE.'/restaurants?sort=name&favorites=1');

        self::assertResponseIsSuccessful();
        self::assertNotSame([], $ohne);
        self::assertSame($ohne, $this->kartenNamen($mit), 'Ohne Konto wirkt der Filter nicht.');
        self::assertCount(0, $this->favoritenChip($mit), 'Kein Filterchip für Gäste.');
        self::assertStringNotContainsString('Du hast noch keine Favoriten', $mit->text(), 'Keine Meldung für Gäste.');
    }

    public function testAk01GemerktesLokalTraegtDasGefuellteHerz(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO_A);
        $lokal = $this->erstesNachName($client);
        $this->favoriten($client, $konto, [$lokal]);

        $seite = $client->request('GET', self::LOCALE.'/restaurants?sort=name');

        self::assertResponseIsSuccessful();
        $herz = $seite->filter('#favorit-'.$lokal->getId());
        self::assertStringEndsWith('/restaurants/'.$lokal->getId().'/nicht-mehr-merken', (string) $herz->attr('action'));
        self::assertSame(
            1,
            $seite->filter('form[action$="/nicht-mehr-merken"]')->count(),
            'Nur das gemerkte Lokal trägt ein gefülltes Herz.',
        );
        self::assertGreaterThan(1, $seite->filter('form[action$="/merken"]')->count(), 'Die übrigen Karten bleiben leer.');
    }

    public function testAk38FavoritenUndRollstuhlZeigenGenauDiePassendenKarten(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO_A);
        $lokale = $this->lokaleMitRollstuhl($client, [true, false, true, false, false]);
        $this->favoriten($client, $konto, $lokale);

        $seite = $client->request('GET', self::LOCALE.'/restaurants?favorites=1&wheelchair=1&sort=name');

        self::assertResponseIsSuccessful();
        self::assertSame(['Favoritentest 01', 'Favoritentest 03'], $this->kartenNamen($seite));
        self::assertCount(2, $seite->filter('form[action$="/nicht-mehr-merken"]'), 'Beide Karten tragen das gefüllte Herz.');
        self::assertCount(1, $seite->filter('input[name="favorites"][value="1"][checked]'), 'Das Häkchen bleibt gesetzt.');
        self::assertCount(1, $this->favoritenChip($seite), 'Der aktive Filter steht als Chip da.');
    }

    public function testAk38SortierungUndBlaetternBehaltenDenFilter(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO_A);
        $lokale = $this->lokaleMitRollstuhl($client, array_fill(0, 7, false));
        $this->favoriten($client, $konto, $lokale);

        $erste = $client->request('GET', self::LOCALE.'/restaurants?favorites=1&sort=name');

        self::assertSame(
            ['Favoritentest 01', 'Favoritentest 02', 'Favoritentest 03', 'Favoritentest 04', 'Favoritentest 05', 'Favoritentest 06'],
            $this->kartenNamen($erste),
            'Sechs je Seite, nach Name — wie bei jedem anderen Filter.',
        );
        $weiter = $erste->filter('nav a')->reduce(static fn (Crawler $a): bool => str_contains((string) $a->attr('href'), 'page=2'));
        self::assertGreaterThan(0, $weiter->count(), 'Vorbedingung: Es gibt eine zweite Seite.');
        self::assertStringContainsString('favorites=1', (string) $weiter->first()->attr('href'));
        $sortierung = $erste->filter('a[href*="sort=newest"]');
        self::assertStringContainsString('favorites=1', (string) $sortierung->attr('href'));

        $zweite = $client->click($weiter->first()->link());

        self::assertSame(['Favoritentest 07'], $this->kartenNamen($zweite));
    }

    public function testAk38HaekchenStehtImGezaehltenFilterformularUndOhneFilterKeinChip(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO_A);

        $seite = $client->request('GET', self::LOCALE.'/restaurants');

        $haekchen = $seite->filter('form[data-controller="usage-event"] input[type="checkbox"][name="favorites"][value="1"]');
        self::assertCount(1, $haekchen, 'AK-66: Das Häkchen steht im Formular, das filter_angewandt zählt.');
        self::assertNull($haekchen->attr('checked'));
        self::assertCount(0, $this->favoritenChip($seite));
    }

    public function testAk40OhneFavoritenStehtDerLeertextMitVerweisDerDieUebrigenFilterBehaelt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO_A);

        $seite = $client->request('GET', self::LOCALE.'/restaurants?favorites=1&wheelchair=1&sort=name');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $seite->filter('article'));
        self::assertStringContainsString(
            'Du hast noch keine Favoriten. Tippe auf das Herz eines Lokals, um es hier zu finden.',
            $seite->text(),
        );
        self::assertStringNotContainsString('Noch keine Restaurants eingetragen.', $seite->text());

        $verweis = $seite->filter('a')->reduce(static fn (Crawler $a): bool => 'Filter entfernen' === trim($a->text()));
        self::assertCount(1, $verweis, 'Der Leertext trägt den Verweis „Filter entfernen“.');
        $href = (string) $verweis->attr('href');
        self::assertStringContainsString('wheelchair=1', $href);
        self::assertStringContainsString('sort=name', $href);
        self::assertStringNotContainsString('favorites', $href);
        self::assertStringNotContainsString('page=', $href);
    }

    public function testAk40MitFavoritenAberOhneTrefferBleibtDerGewoehnlicheLeertext(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO_A);
        $this->favoriten($client, $konto, $this->lokaleMitRollstuhl($client, [false]));

        $seite = $client->request('GET', self::LOCALE.'/restaurants?favorites=1&wheelchair=1');

        self::assertCount(0, $seite->filter('article'));
        self::assertStringNotContainsString('Du hast noch keine Favoriten', $seite->text());
        self::assertStringContainsString('Noch keine Restaurants eingetragen.', $seite->text());
    }

    public function testAk52KontoBUndGastSehenWederMerkzustandNochNotizNochListeVonA(): void
    {
        $client = static::createClient();
        $kontoA = $this->user($client, self::KONTO_A);
        $lokal = $this->erstesNachName($client);
        $this->favoriten($client, $kontoA, [$lokal]);
        $this->notiz($client, $kontoA, $lokal, 'Geheime Notiz von A 7f3c');
        $this->eintraege($client, $this->liste($client, $kontoA, 'Mit Oma 7f3c'), [$lokal]);

        // Gegenprobe: A selbst sieht das gefüllte Herz — sonst prüfte der Rest nichts.
        $client->loginUser($kontoA);
        $alsA = $client->request('GET', self::LOCALE.'/restaurants?sort=name');
        self::assertStringEndsWith('/nicht-mehr-merken', (string) $alsA->filter('#favorit-'.$lokal->getId())->attr('action'));

        $client->loginUser($this->user($client, self::KONTO_B));
        $this->pruefeNichtsVonA($client, $lokal, 'Konto B');

        $client->getCookieJar()->clear();
        $this->pruefeNichtsVonA($client, $lokal, 'Gast');

        // Und mit dem Filter: B hat nichts gemerkt, sieht also A's Favoriten nicht.
        $client->loginUser($this->user($client, self::KONTO_B));
        $gefiltert = $client->request('GET', self::LOCALE.'/restaurants?favorites=1');
        self::assertCount(0, $gefiltert->filter('article'), 'Der Filter liest nur die Favoriten des eigenen Kontos.');
    }

    public function testAk66FavoritenIstEinGezaehlterFilterschluessel(): void
    {
        $katalog = new UsageEventCatalogue();

        self::assertContains('favorites', UsageEventCatalogue::FILTER_KEYS);
        self::assertTrue($katalog->isAllowed(UsageEventCatalogue::FILTER_APPLIED, ['filter' => 'favorites,wheelchair']));
    }

    private function pruefeNichtsVonA(KernelBrowser $client, Restaurant $lokal, string $wer): void
    {
        $seite = $client->request('GET', self::LOCALE.'/restaurants?sort=name');
        $quelltext = (string) $client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(
            '/restaurants/'.$lokal->getId().'/merken',
            (string) $seite->filter('#favorit-'.$lokal->getId())->attr('action'),
            $wer.': Das Herz ist leer.',
        );
        self::assertStringNotContainsString('nicht-mehr-merken', $quelltext, $wer.': Kein gefülltes Herz auf der Seite.');
        self::assertStringNotContainsString('7f3c', $quelltext, $wer.': Weder Notiz noch Listenname im Quelltext.');
    }

    /** @return list<string> */
    private function kartenNamen(Crawler $seite): array
    {
        return $seite->filter('article h3')->each(static fn (Crawler $h): string => trim($h->text()));
    }

    private function favoritenChip(Crawler $seite): Crawler
    {
        return $seite->filter('span.rounded-full')->reduce(
            static fn (Crawler $chip): bool => str_contains($chip->text(), 'Nur meine Favoriten'),
        );
    }

    private function erstesNachName(KernelBrowser $client): Restaurant
    {
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['name' => 'ASC']);
        self::assertNotNull($lokal);

        return $lokal;
    }

    /**
     * Neue Lokale „Favoritentest 01“ … mit vorgegebenem Merkmal „rollstuhlgerecht“.
     *
     * @param list<bool> $rollstuhl
     *
     * @return list<Restaurant>
     */
    private function lokaleMitRollstuhl(KernelBrowser $client, array $rollstuhl): array
    {
        $lokale = $this->neueLokale($client, count($rollstuhl), 'Favoritentest');
        foreach ($lokale as $i => $lokal) {
            $lokal->setIsWheelchairAccessible($rollstuhl[$i]);
        }
        $this->em($client)->flush();

        return $lokale;
    }
}
