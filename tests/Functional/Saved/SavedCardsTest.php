<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Feature 17, T21: die gestalteten Seiten des Bereichs „Gespeichert“ — dieselben Karten wie in
 * der Suche, die Notiz darunter, das Fokusziel nach einem Stream und das Herz auf „Favoriten“,
 * das die Karte mitnimmt (AK-14).
 *
 * ⚠ **Das Fokusziel steht nur im Stream.** `focus-after-stream` auf einer gewöhnlich geladenen
 * Seite nähme dem Besucher beim Laden den Fokus weg — `testNormalGeladeneSeiten…` hält das fest.
 */
final class SavedCardsTest extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string KONTO = 'user@endlech.lu';
    private const string ANDERES = 'admin@endlech.lu';
    private const string FAVORITEN = self::LOCALE.'/gespeichert/favoriten';
    private const string FOKUS = '[data-controller="focus-after-stream"]';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deckelZuruecksetzen();
    }

    public function testAk12FavoritenZeigenDieKarteDerSuche(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1, 'Suchkarte');
        $this->favoriten($client, $konto, [$lokal]);

        $suche = $client->request('GET', self::LOCALE.'/restaurants?favorites=1');
        $favoriten = $client->request('GET', self::FAVORITEN);

        foreach (['Suche' => $suche, 'Favoriten' => $favoriten] as $wo => $seite) {
            $karte = $seite->filter('article#karte-'.$lokal->getId());
            self::assertCount(1, $karte, $wo);
            self::assertSame('Suchkarte 01', trim($karte->filter('h3')->text()), $wo);
            self::assertSame(self::LOCALE.'/restaurants/'.$lokal->getId(), $karte->selectLink('Details ansehen')->attr('href'), $wo);
            self::assertSame('Suchkarte 01 aus Favoriten entfernen', trim($karte->filter('#favorit-knopf-'.$lokal->getId())->text()), $wo);
        }
    }

    /**
     * AK-34: Dasselbe Lokal unter „Favoriten“ und in zwei Listen — die Notiz steht in allen drei
     * Ansichten unter seiner Karte, als Text, mit Zeilenumbruch. Ein Lokal ohne Notiz trägt keine.
     */
    public function testAk34NotizStehtInFavoritenUndZweiListenUnterDerKarte(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$mitNotiz, $ohneNotiz] = $this->neueLokale($client, 2, 'Notizlokal');
        $this->favoriten($client, $konto, [$mitNotiz, $ohneNotiz]);
        $kirchberg = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $kirchberg, [$mitNotiz, $ohneNotiz]);
        $oma = $this->liste($client, $konto, 'Mit Oma');
        $this->eintraege($client, $oma, [$ohneNotiz, $mitNotiz]);
        $this->notiz($client, $konto, $mitNotiz, "Seiteneingang links,\n<b>Rampe</b> steil");

        $ansichten = [
            'Favoriten' => self::FAVORITEN,
            'Kirchberg' => self::LOCALE.'/gespeichert/listen/'.$kirchberg->getId(),
            'Mit Oma' => self::LOCALE.'/gespeichert/listen/'.$oma->getId(),
        ];
        foreach ($ansichten as $wo => $adresse) {
            $seite = $client->request('GET', $adresse);

            self::assertResponseIsSuccessful($wo);
            $notiz = $this->notizInKarte($seite, $mitNotiz->getId());
            self::assertCount(1, $notiz, $wo.': Notiz unter der Karte');
            self::assertSame("Seiteneingang links,\n<b>Rampe</b> steil", trim($notiz->text(null, false)), $wo);
            self::assertCount(0, $notiz->filter('b'), $wo.': Die Notiz ist Text, kein HTML (AK-37).');
            self::assertCount(0, $this->notizInKarte($seite, $ohneNotiz->getId()), $wo.': ohne Notiz kein Block');
        }
    }

    public function testAk26KnopfAusListeEntfernenBeginntMitDemSichtbarenText(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1, 'Knopflokal');
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$lokal]);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());

        $knopf = $seite->filter('article#karte-'.$lokal->getId().' form[action$="/lokale/'.$lokal->getId().'/entfernen"] button[type="submit"]');
        self::assertCount(1, $knopf, 'Der Knopf steht in der Karte (Block `karten_aktion`).');
        // WCAG 2.5.3: Wer „Aus Liste entfernen“ sagt, trifft den Knopf — der zugängliche Name
        // beginnt mit dem sichtbaren Text. Ein `aria-label` überschriebe ihn.
        self::assertNull($knopf->attr('aria-label'));
        $versteckt = $knopf->filter('.sr-only');
        self::assertCount(1, $versteckt);
        self::assertStringContainsString('Knopflokal 01', $versteckt->text());
        $sichtbar = trim(str_replace($versteckt->text(null, false), '', $knopf->text(null, false)));
        self::assertSame('Aus Liste entfernen', $sichtbar);
        self::assertStringStartsWith($sichtbar, trim($knopf->text()));
    }

    public function testAk26StreamMarkiertDieNaechsteKarteFuerDenFokusController(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites, $drittes] = $this->neueLokale($client, 3);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        // Angezeigt: drittes, zweites, erstes.
        $this->eintraege($client, $liste, [$erstes, $zweites, $drittes]);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());
        $client->submit($this->formular($seite, 'form[action$="/lokale/'.$zweites->getId().'/entfernen"]'), [], ['HTTP_ACCEPT' => self::STREAM]);

        $raster = $this->raster((string) $client->getResponse()->getContent());
        self::assertCount(0, $raster->filter('#karte-'.$zweites->getId()), 'Die Karte ist weg.');
        self::assertCount(2, $raster->filter('article[id^="karte-"]'));
        $ziel = $raster->filter(self::FOKUS);
        self::assertCount(1, $ziel, 'Genau ein Fokusziel.');
        self::assertCount(1, $ziel->filter('#karte-'.$erstes->getId()), 'Fokus auf der nächsten Karte.');
        self::assertSame('-1', $ziel->attr('tabindex'), 'Eine Karte ist von sich aus nicht fokussierbar.');
        self::assertNotNull($ziel->attr('data-fokus-nach-aktualisierung'));
        // Die Zahl über den Karten zieht mit — sie steht im ersetzten Raster.
        self::assertSame('2 Lokale', trim($raster->filter('h2')->text()));
    }

    /** @return iterable<string, array{string}> */
    public static function gewoehnlicheSeiten(): iterable
    {
        yield 'Übersicht' => [''];
        yield 'Favoriten' => ['/favoriten'];
        yield 'Liste' => ['/listen/{id}'];
        yield 'leere Liste' => ['/listen/{leer}'];
        yield 'Rückfrage' => ['/listen/{id}/loeschen'];
    }

    #[DataProvider('gewoehnlicheSeiten')]
    public function testNormalGeladeneSeitenTragenNieDenFokusController(string $pfad): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 2);
        $this->favoriten($client, $konto, $lokale);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, $lokale);
        $leer = $this->liste($client, $konto, 'Leer');

        $client->request('GET', self::LOCALE.'/gespeichert'.strtr($pfad, ['{id}' => (string) $liste->getId(), '{leer}' => (string) $leer->getId()]));

        self::assertResponseIsSuccessful();
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('focus-after-stream', $inhalt);
        self::assertStringNotContainsString('data-fokus-nach-aktualisierung', $inhalt);
    }

    /** @return iterable<string, array{string}> */
    public static function leereSeiten(): iterable
    {
        yield 'leere Übersicht' => [''];
        yield 'leere Favoriten' => ['/favoriten'];
        yield 'leere Liste' => ['/listen/{leer}'];
    }

    #[DataProvider('leereSeiten')]
    public function testLeereSeitenHabenGenauEineH1UndEineLueckenloseKette(string $pfad): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::ANDERES);
        $leer = $this->liste($client, $konto, 'Leer');
        if ('' === $pfad) {
            $this->em($client)->remove($leer);
            $this->em($client)->flush();
        }

        $seite = $client->request('GET', self::LOCALE.'/gespeichert'.str_replace('{leer}', (string) $leer->getId(), $pfad));

        self::assertResponseIsSuccessful();
        $this->assertKette($seite);
    }

    public function testAk28AbgewiesenesUmbenennenHatGenauEineH1(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, $this->neueLokale($client, 2));

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());
        $antwort = $client->submit($this->formWithField($seite, 'saved_list_name[name]', ['saved_list_name[name]' => '']));

        self::assertResponseStatusCodeSame(422);
        $this->assertKette($antwort);
    }

    public function testAk14HerzAufFavoritenMitTurboNimmtDieKarteMitUndFokussiertDieNaechste(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites, $drittes] = $this->neueLokale($client, 3, 'Herzlokal');
        // Angezeigt: drittes, zweites, erstes.
        $this->favoriten($client, $konto, [$erstes, $zweites, $drittes]);
        $rest = $this->deckelRest($client, $konto);

        $seite = $client->request('GET', self::FAVORITEN);
        $client->submit($seite->filter('#favorit-'.$zweites->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/vnd.turbo-stream.html', (string) $client->getResponse()->headers->get('Content-Type'));
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('target="favorit-'.$zweites->getId().'"', $inhalt, 'Nicht nur das Herz: das ganze Raster.');
        self::assertStringContainsString('<turbo-stream action="update" target="meldung-live">', $inhalt);
        self::assertStringContainsString('Aus Favoriten entfernt: Herzlokal 02', html_entity_decode($inhalt));
        self::assertStringNotContainsString('usage-event', $inhalt, 'AK-66: Entfernen zählt nicht.');

        $raster = $this->raster($inhalt);
        self::assertCount(0, $raster->filter('#karte-'.$zweites->getId()));
        self::assertCount(2, $raster->filter('article[id^="karte-"]'));
        $ziel = $raster->filter(self::FOKUS);
        self::assertCount(1, $ziel);
        self::assertCount(1, $ziel->filter('#karte-'.$erstes->getId()), 'Fokus auf der nächsten Karte.');
        self::assertSame('2 Lokale', trim($raster->filter('h2')->text()));

        self::assertSame(0, $this->sql($client, 'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?', [$konto->getId(), $zweites->getId()]));
        self::assertSame($rest - 1, $this->deckelRest($client, $konto), 'Eine Änderung zählt einmal.');
    }

    public function testAk14LetzteKarteFokussiertDieVorigeUndDieLetzteUebrigeDenLeertext(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites] = $this->neueLokale($client, 2, 'Herzlokal');
        // Angezeigt: zweites, erstes.
        $this->favoriten($client, $konto, [$erstes, $zweites]);

        $seite = $client->request('GET', self::FAVORITEN);
        $client->submit($seite->filter('#favorit-'.$erstes->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);
        $raster = $this->raster((string) $client->getResponse()->getContent());
        self::assertCount(1, $raster->filter(self::FOKUS.' > #karte-'.$zweites->getId()), 'Bei der letzten Karte: die vorige.');

        $seite = $client->request('GET', self::FAVORITEN);
        $client->submit($seite->filter('#favorit-'.$zweites->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);
        $raster = $this->raster((string) $client->getResponse()->getContent());
        $leer = $raster->filter(self::FOKUS);
        self::assertCount(1, $leer);
        self::assertSame('gespeichert-leer', $leer->attr('id'));
        self::assertSame('-1', $leer->attr('tabindex'));
        self::assertStringContainsString('Noch nichts gemerkt. Tippe in der Suche auf das Herz', $leer->text());
        self::assertCount(1, $raster->filter('a[href="'.self::LOCALE.'/restaurants"]'), 'AK-13: Verweis auf die Suche.');
    }

    public function testAk14OhneJavaScriptFuehrtDasHerzAufFavoritenZurNaechstenKarte(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites] = $this->neueLokale($client, 2, 'Herzlokal');
        $this->favoriten($client, $konto, [$erstes, $zweites]);

        $seite = $client->request('GET', self::FAVORITEN);
        $client->submit($seite->filter('#favorit-'.$zweites->getId())->form());

        self::assertResponseRedirects(self::FAVORITEN.'#karte-'.$erstes->getId(), 303);
        $danach = $client->followRedirect();
        self::assertStringContainsString('Aus Favoriten entfernt: Herzlokal 02', $danach->text());
        self::assertCount(0, $danach->filter('#karte-'.$zweites->getId()), 'Die Karte ist weg.');
        self::assertCount(1, $danach->filter('article#karte-'.$erstes->getId()));

        // Die letzte übrige Karte: Anker auf den Leertext.
        $client->submit($danach->filter('#favorit-'.$erstes->getId())->form());
        self::assertResponseRedirects(self::FAVORITEN.'#gespeichert-leer', 303);
        self::assertCount(1, $client->followRedirect()->filter('#gespeichert-leer'));
    }

    public function testAk14LeerGewordeneFolgeseiteDerFavoritenFuehrtAufDieLetzteSeite(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 13, 'Herzlokal');
        $this->favoriten($client, $konto, $lokale);

        // Auf Seite 2 steht allein das zuerst gemerkte Lokal; sein Herz führt auf Seite 2 zurück.
        $seite = $client->request('GET', self::FAVORITEN.'?page=2');
        self::assertCount(1, $seite->filter('article[id^="karte-"]'));
        $formular = $seite->filter('#favorit-'.$lokale[0]->getId())->form();
        self::assertSame(self::FAVORITEN.'?page=2', $formular->get('ziel')->getValue());
        $client->submit($formular, [], ['HTTP_ACCEPT' => self::STREAM]);

        $raster = $this->raster((string) $client->getResponse()->getContent());
        self::assertCount(12, $raster->filter('article[id^="karte-"]'), 'Seite 1 statt einer leeren Seite 2.');
        self::assertCount(1, $raster->filter(self::FOKUS.' > #karte-'.$lokale[1]->getId()), 'Fokus auf der vorigen Karte, der letzten von Seite 1.');

        // Ohne JavaScript dasselbe als Weiterleitung — auf Seite 1, ohne `page`.
        $this->favoriten($client, $this->frisch($client, $konto), [$this->em($client)->find(Restaurant::class, $lokale[0]->getId())]);
        $seite = $client->request('GET', self::FAVORITEN.'?page=2');
        self::assertCount(1, $seite->filter('#karte-'.$lokale[1]->getId()));
        $client->submit($seite->filter('#favorit-'.$lokale[1]->getId())->form());
        self::assertResponseRedirects(self::FAVORITEN.'#karte-'.$lokale[2]->getId(), 303);
    }

    /**
     * Nach einem Entfernen per Stream steht ein neues Raster da — dessen Herzen müssen weiter auf
     * „Favoriten“ zurückführen, sonst nimmt das zweite Entfernen die Karte nicht mehr mit (AK-14).
     */
    public function testAk14HerzenImNeuenRasterFuehrenWeiterAufDieFavoritenseite(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites, $drittes] = $this->neueLokale($client, 3, 'Herzlokal');
        $this->favoriten($client, $konto, [$erstes, $zweites, $drittes]);

        $seite = $client->request('GET', self::FAVORITEN);
        $client->submit($seite->filter('#favorit-'.$drittes->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);

        $raster = $this->raster((string) $client->getResponse()->getContent());
        $ziele = $raster->filter('form[id^="favorit-"] input[name="ziel"]')->each(static fn (Crawler $feld): ?string => $feld->attr('value'));
        self::assertSame([self::FAVORITEN, self::FAVORITEN], $ziele);
    }

    public function testEc15HerzEntfernenAufDerListenseiteLaesstDieKarteInDerListe(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $this->favoriten($client, $konto, [$lokal]);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$lokal]);
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        // Mit Turbo: nur das Herz, kein neues Raster.
        $seite = $client->request('GET', $adresse);
        $client->submit($seite->filter('#favorit-'.$lokal->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<turbo-stream action="replace" method="morph" target="favorit-'.$lokal->getId().'">', $inhalt);
        self::assertStringNotContainsString('target="gespeichert-karten"', $inhalt);
        self::assertSame(0, $this->sql($client, 'SELECT COUNT(*) FROM favorite WHERE user_id = ?', [$konto->getId()]));
        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()), 'Die Karte bleibt in der Liste.');

        // Ohne JavaScript: zurück zum Herz auf der Listenseite; die Karte steht noch da.
        $this->favoriten($client, $this->frisch($client, $konto), [$this->em($client)->find(Restaurant::class, $lokal->getId())]);
        $seite = $client->request('GET', $adresse);
        $client->submit($seite->filter('#favorit-'.$lokal->getId())->form());
        self::assertResponseRedirects($adresse.'#favorit-'.$lokal->getId(), 303);
        self::assertCount(1, $client->followRedirect()->filter('article#karte-'.$lokal->getId()));
        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    public function testAk14HerzEntfernenAufDerSucheBleibtBeimHerz(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $this->favoriten($client, $konto, [$lokal]);

        $seite = $client->request('GET', self::LOCALE.'/restaurants?favorites=1');
        $client->submit($seite->filter('#favorit-'.$lokal->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);

        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('target="favorit-'.$lokal->getId().'"', $inhalt);
        self::assertStringNotContainsString('gespeichert-karten', $inhalt);
    }

    /** Nach einer Anfrage hat der Client neu gebootet — das Konto aus dem neuen EntityManager. */
    private function frisch(KernelBrowser $client, User $konto): User
    {
        $frisch = $this->em($client)->find(User::class, $konto->getId());
        self::assertNotNull($frisch);

        return $frisch;
    }

    private function formular(Crawler $seite, string $selektor): Form
    {
        $knoten = $seite->filter($selektor);
        self::assertCount(1, $knoten, $selektor);

        return $knoten->form();
    }

    private function assertKette(Crawler $seite): void
    {
        self::assertCount(1, $seite->filter('h1'), 'Genau eine h1.');
        $vorige = 0;
        foreach ($seite->filter('h1, h2, h3, h4, h5, h6') as $knoten) {
            $ebene = (int) substr($knoten->nodeName, 1);
            self::assertLessThanOrEqual($vorige + 1, $ebene, 'Lücke vor „'.trim($knoten->textContent).'“');
            $vorige = $ebene;
        }
    }
}
