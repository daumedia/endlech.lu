<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 17, T17: die Lese-Seiten des Bereichs „Gespeichert“ — Übersicht, Favoriten, Liste,
 * Rückfrage; Zugang, Blättern, fremde Listen, Sitemap.
 */
final class SavedPagesTest extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string KONTO = 'user@endlech.lu';
    private const string ANDERES = 'admin@endlech.lu';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deckelZuruecksetzen();
    }

    /** @return iterable<string, array{string}> */
    public static function geschuetzteSeiten(): iterable
    {
        yield 'Gespeichert' => ['/gespeichert'];
        yield 'Favoriten' => ['/gespeichert/favoriten'];
    }

    #[DataProvider('geschuetzteSeiten')]
    public function testAk15GastLandetAufDerAnmeldung(string $pfad): void
    {
        $client = static::createClient();

        $client->request('GET', self::LOCALE.$pfad);

        self::assertResponseRedirects(self::LOCALE.'/login');
    }

    public function testAk15NachDerAnmeldungLandetDerGastAufDerAufgerufenenListe(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Mittag in Kirchberg');
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $client->request('GET', $adresse);
        self::assertResponseRedirects(self::LOCALE.'/login');

        // Das echte Anmeldeformular: Die Firewall hat die Zielseite gemerkt.
        $anmeldung = $client->request('GET', self::LOCALE.'/login');
        $client->submit($this->formWithField($anmeldung, '_password', ['_username' => self::KONTO, '_password' => 'user123']));

        self::assertResponseRedirects();
        self::assertStringEndsWith($adresse, (string) $client->getResponse()->headers->get('Location'));
        $seite = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSame('Mittag in Kirchberg', trim($seite->filter('h1')->text()));
    }

    public function testAk11UebersichtZeigtZahlenUndZuletztAngelegteListeZuerst(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$a, $b] = $this->neueLokale($client, 2);
        $this->favoriten($client, $konto, [$a, $b]);
        $aeltere = $this->liste($client, $konto, 'Ältere Liste');
        $this->eintraege($client, $aeltere, [$a]);
        $neuere = $this->liste($client, $konto, 'Neuere Liste');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $seite->filter('h1'));
        self::assertSame('Gespeichert', trim($seite->filter('h1')->text()));
        self::assertSame('2 Lokale', trim($seite->filter('#gespeichert-favoriten-zahl')->text()));
        self::assertSame(
            self::LOCALE.'/gespeichert/favoriten',
            $seite->filter('#gespeichert-favoriten a')->first()->attr('href'),
        );

        $listen = $seite->filter('#gespeichert-listen li')->each(static fn (Crawler $li): array => [
            trim($li->filter('a')->text()),
            trim($li->filter('[data-anzahl]')->text()),
            $li->filter('a')->attr('href'),
        ]);
        self::assertSame([
            ['Neuere Liste', '0 Lokale', self::LOCALE.'/gespeichert/listen/'.$neuere->getId()],
            ['Ältere Liste', '1 Lokal', self::LOCALE.'/gespeichert/listen/'.$aeltere->getId()],
        ], $listen);

        self::assertCount(1, $seite->filter('input[name="saved_list_name[name]"]'));
        self::assertSame('Anlegen', trim($seite->filter('form[action="'.self::LOCALE.'/gespeichert/listen"] button[type="submit"]')->text()));
    }

    public function testAk13LeereUebersichtZeigtBeideLeertexte(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::ANDERES);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Noch nichts gemerkt. Tippe in der Suche auf das Herz, um ein Lokal hier zu sammeln.', $seite->text());
        self::assertStringContainsString('Noch keine eigene Liste. Lege eine an, zum Beispiel für einen Anlass oder einen Ort.', $seite->text());
        self::assertGreaterThan(0, $seite->filter('#gespeichert-favoriten a[href="'.self::LOCALE.'/restaurants"]')->count(), 'Verweis auf die Restaurantsuche');
    }

    public function testAk12FuenfzehnFavoritenStehenZuZwoelfUndDrei(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 15, 'Favorit');
        $this->favoriten($client, $konto, $lokale);
        $this->notiz($client, $konto, $lokale[14], "Seiteneingang links,\nRampe steil");

        $erste = $client->request('GET', self::LOCALE.'/gespeichert/favoriten');

        self::assertResponseIsSuccessful();
        self::assertSame('Favoriten', trim($erste->filter('h1')->text()));
        self::assertCount(12, $erste->filter('[id^="karte-"]'));
        // Zuletzt gemerkt zuerst.
        self::assertSame('karte-'.$lokale[14]->getId(), $erste->filter('[id^="karte-"]')->first()->attr('id'));
        self::assertSame('karte-'.$lokale[3]->getId(), $erste->filter('[id^="karte-"]')->last()->attr('id'));
        // Die Notiz steht unter ihrer Karte, mit Zeilenumbruch als Text.
        $notiz = $this->notizInKarte($erste, $lokale[14]->getId());
        self::assertSame("Seiteneingang links,\nRampe steil", trim($notiz->text(null, false)));
        self::assertCount(0, $this->notizInKarte($erste, $lokale[13]->getId()), 'Ohne Notiz kein Notizblock.');
        // Das Herz ist gefüllt und führt hierher zurück.
        self::assertSame('Favorit 15 aus Favoriten entfernen', trim($erste->filter('#favorit-knopf-'.$lokale[14]->getId())->text()));

        $weiter = $erste->selectLink('Weiter →');
        self::assertCount(1, $weiter);
        $zweite = $client->click($weiter->link());

        self::assertResponseIsSuccessful();
        self::assertCount(3, $zweite->filter('[id^="karte-"]'));
        self::assertSame('karte-'.$lokale[0]->getId(), $zweite->filter('[id^="karte-"]')->last()->attr('id'));

        $client->request('GET', self::LOCALE.'/gespeichert/favoriten?page=3');
        self::assertResponseStatusCodeSame(404);
    }

    public function testAk13FavoritenOhneEintragZeigenDenLeertext(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::ANDERES);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/favoriten');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $seite->filter('[id^="karte-"]'));
        self::assertStringContainsString('Noch nichts gemerkt.', $seite->filter('#gespeichert-karten')->text());
    }

    /** @return iterable<string, array{string}> */
    public static function ungueltigeSeiten(): iterable
    {
        yield 'keine Zahl' => ['abc'];
        yield 'negativ' => ['-3'];
        yield 'null' => ['0'];
        yield 'riesig' => ['99999999999999999999'];
        // BF-214: eine gültige ganze Zahl, deren Versatz (`(seite - 1) * Karten je Seite`) keine mehr ist.
        yield 'größte ganze Zahl' => [(string) \PHP_INT_MAX];
    }

    #[DataProvider('ungueltigeSeiten')]
    public function testEc16UngueltigeSeitenzahlVerhaeltSichWieDieSuche(string $seite): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Blättern');

        $client->request('GET', self::LOCALE.'/restaurants?page='.urlencode($seite));
        $suche = $client->getResponse()->getStatusCode();

        foreach (['/gespeichert/favoriten', '/gespeichert/listen/'.$liste->getId()] as $pfad) {
            $client->request('GET', self::LOCALE.$pfad.'?page='.urlencode($seite));
            self::assertSame($suche, $client->getResponse()->getStatusCode(), $pfad.'?page='.$seite);
            self::assertLessThan(500, $client->getResponse()->getStatusCode());
        }
    }

    public function testAk25ListeMitVierzehnLokalenStehtZuZwoelfUndZwei(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 14, 'Listenlokal');
        $liste = $this->liste($client, $konto, 'Mit Oma');
        $this->eintraege($client, $liste, $lokale);
        $this->notiz($client, $konto, $lokale[13], 'Klingel rechts');

        $erste = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());

        self::assertResponseIsSuccessful();
        self::assertCount(1, $erste->filter('h1'));
        self::assertSame('Mit Oma', trim($erste->filter('h1')->text()));
        self::assertSelectorTextContains('title', 'Mit Oma');
        self::assertCount(12, $erste->filter('[id^="karte-"]'));
        self::assertSame('karte-'.$lokale[13]->getId(), $erste->filter('[id^="karte-"]')->first()->attr('id'));
        self::assertSame('Klingel rechts', trim($this->notizInKarte($erste, $lokale[13]->getId())->text()));
        // Jede Karte trägt „Aus Liste entfernen“; der zugängliche Name beginnt mit dem sichtbaren
        // Text und nennt das Lokal (WCAG 2.5.3) — kein `aria-label`, das ihn überschriebe.
        $knopf = $erste->filter('article#karte-'.$lokale[13]->getId().' form[action$="/entfernen"] button');
        self::assertCount(1, $knopf);
        self::assertNull($knopf->attr('aria-label'));
        self::assertSame('Aus Liste entfernen: Listenlokal 14', trim($knopf->text()));
        // Das Fokusziel steht nur im Stream — eine gewöhnlich geladene Seite nähme sonst den Fokus weg.
        self::assertCount(0, $erste->filter('[data-fokus-nach-aktualisierung]'));

        $zweite = $client->click($erste->selectLink('Weiter →')->link());
        self::assertCount(2, $zweite->filter('[id^="karte-"]'));

        $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId().'?page=3');
        self::assertResponseStatusCodeSame(404);
    }

    public function testAk27LeereListeZeigtDenLeertextMitVerweisAufDieSuche(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Noch leer');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());

        self::assertResponseIsSuccessful();
        $raster = $seite->filter('#gespeichert-karten');
        self::assertStringContainsString('Diese Liste ist noch leer.', $raster->text());
        self::assertCount(1, $raster->filter('a[href="'.self::LOCALE.'/restaurants"]'));
        // Umbenennen und Löschen stehen auch an einer leeren Liste.
        self::assertSame('Noch leer', $seite->filter('input[name="saved_list_name[name]"]')->attr('value'));
        self::assertCount(1, $seite->filter('a[href="'.self::LOCALE.'/gespeichert/listen/'.$liste->getId().'/loeschen"]'));
    }

    public function testEc15HerzAufDerListenseiteWirktAufDieFavoritenNichtAufDieListe(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$lokal]);
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $seite = $client->request('GET', $adresse);
        $client->submit($seite->filter('#favorit-'.$lokal->getId())->form());

        self::assertResponseRedirects($adresse.'#favorit-'.$lokal->getId(), 303);
        self::assertSame(1, $this->sql($client, 'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?', [$konto->getId(), $lokal->getId()]));
        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()), 'Die Karte bleibt in der Liste.');
    }

    public function testAk53FremdeListeIstNichtGefundenWieEineUnbekannte(): void
    {
        // Ohne Debug, also mit der Fehlerseite, die Besucher sehen: Seit Symfony 8.1 trägt
        // die Debug-Fehlerseite Log-Zeilen mit Uhrzeit und zufällige Dump-Kennungen, zwei
        // 404-Antworten sind dort nie gleich. Verglichen wird, was in Produktion ausgeliefert wird.
        $client = static::createClient(['debug' => false]);
        $a = $this->user($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $liste = $this->liste($client, $a, 'Mit Oma');
        $this->eintraege($client, $liste, [$lokal]);
        $this->loginAs($client, self::ANDERES);

        $antworten = [];
        foreach (['', '/loeschen'] as $zusatz) {
            foreach ([(int) $liste->getId(), 999999] as $nummer) {
                $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$nummer.$zusatz);
                self::assertResponseStatusCodeSame(404, '/gespeichert/listen/'.$nummer.$zusatz);
                self::assertStringNotContainsString('Mit Oma', self::antwortOhneQuelltextAuszug($client));
                $antworten[$zusatz][] = $this->ohneNummer((string) $client->getResponse()->getContent(), $nummer);
            }
        }

        self::assertSame($antworten[''][0], $antworten[''][1], 'Fremd und unbekannt antworten gleich.');
        self::assertSame($antworten['/loeschen'][0], $antworten['/loeschen'][1]);
    }

    public function testAk45SitemapNenntKeineAdresseAusGespeichert(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $this->liste($client, $konto, 'Sitemap-Probe');

        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('gespeichert', (string) $client->getResponse()->getContent());
    }

    public function testAk69UnbestaetigtesKontoSiehtSeineSeiten(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'unverified@endlech.lu');
        $liste = $this->liste($client, $konto, 'Unbestätigt');

        foreach (['/gespeichert', '/gespeichert/favoriten', '/gespeichert/listen/'.$liste->getId(), '/gespeichert/listen/'.$liste->getId().'/loeschen'] as $pfad) {
            $client->request('GET', self::LOCALE.$pfad);
            self::assertResponseIsSuccessful($pfad);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function seitenMitKette(): iterable
    {
        yield 'Übersicht' => [''];
        yield 'Favoriten' => ['/favoriten'];
        yield 'Liste' => ['/listen/{id}'];
        yield 'Rückfrage' => ['/listen/{id}/loeschen'];
    }

    #[DataProvider('seitenMitKette')]
    public function testJedeSeiteHatGenauEineH1UndEineLueckenloseKette(string $pfad): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 2);
        $this->favoriten($client, $konto, $lokale);
        $liste = $this->liste($client, $konto, 'Kette');
        $this->eintraege($client, $liste, $lokale);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert'.str_replace('{id}', (string) $liste->getId(), $pfad));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $seite->filter('h1'));
        $vorige = 0;
        foreach ($seite->filter('h1, h2, h3, h4, h5, h6') as $knoten) {
            $ebene = (int) substr($knoten->nodeName, 1);
            self::assertLessThanOrEqual($vorige + 1, $ebene, 'Lücke vor „'.trim($knoten->textContent).'“');
            $vorige = $ebene;
        }
    }

    private function ohneNummer(string $inhalt, int $nummer): string
    {
        // Die Fehlerseite im Testbetrieb nennt die aufgerufene Adresse und laufende Objektnummern
        // (`{#6430`); nur sie dürfen sich unterscheiden — Klasse, Meldung und Aufrufweg nicht.
        return (string) preg_replace('/\{#\d+/', '{#N', str_replace((string) $nummer, 'N', $inhalt));
    }
}
