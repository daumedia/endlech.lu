<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Entity\SavedList;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Feature 17, T17: eigene Listen anlegen, umbenennen, löschen und Lokale aus einer Liste
 * nehmen — mit allen Meldungen, Token, Deckel und der Besitzprüfung.
 */
final class SavedListManageTest extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string KONTO = 'user@endlech.lu';
    private const string ANDERES = 'admin@endlech.lu';
    private const string FELD = 'saved_list_name[name]';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deckelZuruecksetzen();
    }

    public function testAk16AnlegenStelltDieListeZuoberstMitNullLokalen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $this->liste($client, $konto, 'Schon da');
        $rest = $this->deckelRest($client, $konto);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $client->submit($this->formWithField($seite, self::FELD, [self::FELD => '  Mittag in Kirchberg ']));

        self::assertResponseRedirects(self::LOCALE.'/gespeichert', 303);
        self::assertSame($rest - 1, $this->deckelRest($client, $konto), 'Eine Änderung zählt einmal.');
        $danach = $client->followRedirect();
        self::assertStringContainsString('Liste „Mittag in Kirchberg“ angelegt.', $danach->text());
        self::assertSame('Mittag in Kirchberg', trim($danach->filter('#gespeichert-listen li a')->first()->text()));
        self::assertSame('0 Lokale', trim($danach->filter('#gespeichert-listen li [data-anzahl]')->first()->text()));
        // AK-66: genau ein Ereignis „Liste angelegt“, ohne Daten.
        self::assertCount(1, $danach->filter('[data-usage-event-name-value="liste_angelegt"]'));
    }

    /** @return iterable<string, array{string}> */
    public static function leereNamen(): iterable
    {
        yield 'leer' => [''];
        yield 'nur Leerzeichen' => ['     '];
    }

    #[DataProvider('leereNamen')]
    public function testAk17LeererNameSteht422AmFeld(string $name): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $rest = $this->deckelRest($client, $konto);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => $name]));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Bitte gib der Liste einen Namen.', trim($antwort->filter('#saved_list_name_name_error')->text()));
        self::assertSame('autofocus', $antwort->filter('input[name="'.self::FELD.'"]')->attr('autofocus'));
        self::assertSame(0, $this->listenZahl($client, $konto));
        self::assertSame($rest, $this->deckelRest($client, $konto), 'AK-58: abgewiesen zählt nicht.');
        self::assertCount(0, $antwort->filter('[data-usage-event-name-value="liste_angelegt"]'), 'AK-66: abgewiesen zählt nicht.');
    }

    public function testAk18ZuLangerNameBleibtImFeldUndNichtsEntsteht(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $name = str_repeat('a', 61);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => $name]));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Der Name darf höchstens 60 Zeichen lang sein.', trim($antwort->filter('#saved_list_name_name_error')->text()));
        self::assertSame($name, $antwort->filter('input[name="'.self::FELD.'"]')->attr('value'));
        self::assertNull($antwort->filter('input[name="'.self::FELD.'"]')->attr('maxlength'));
        self::assertSame(0, $this->listenZahl($client, $konto));
    }

    public function testAk19DubletteOhneUnterschiedVonGrossUndKleinSteht422AmFeld(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $this->liste($client, $konto, 'Kirchberg');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => ' kirchberg ']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Eine Liste mit diesem Namen hast du schon.', trim($antwort->filter('#saved_list_name_name_error')->text()));
        self::assertSame(1, $this->listenZahl($client, $konto));
    }

    public function testAk19EinAnderesKontoDarfDenselbenNamenVergeben(): void
    {
        $client = static::createClient();
        $a = $this->user($client, self::KONTO);
        $this->liste($client, $a, 'Kirchberg');
        $b = $this->loginAs($client, self::ANDERES);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'Kirchberg']));

        self::assertResponseRedirects(self::LOCALE.'/gespeichert', 303);
        self::assertSame(1, $this->listenZahl($client, $b));
    }

    public function testAk20SechsundzwanzigsteListeWirdAbgewiesen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        for ($i = 1; $i <= SavedList::MAX_PER_USER; ++$i) {
            $this->liste($client, $konto, 'Liste '.$i);
        }

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'Nummer 26']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Du hast bereits 25 Listen. Lösche eine, um eine neue anzulegen.', trim($antwort->filter('#saved_list_name_name_error')->text()));
        self::assertSame(25, $this->listenZahl($client, $konto));
    }

    public function testAk28UmbenennenGiltUeberallUndDerEigeneNameIstKeineDublette(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $seite = $client->request('GET', $adresse);
        $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'Kirchberg mittags']));

        self::assertResponseRedirects($adresse, 303);
        $danach = $client->followRedirect();
        self::assertSame('Kirchberg mittags', trim($danach->filter('h1')->text()));
        self::assertStringContainsString('Liste umbenannt in „Kirchberg mittags“.', $danach->text());
        self::assertSame('Kirchberg mittags', $this->listenName($client, (int) $liste->getId()));
        $uebersicht = $client->request('GET', self::LOCALE.'/gespeichert');
        self::assertSame('Kirchberg mittags', trim($uebersicht->filter('#gespeichert-listen li a')->text()));

        // Der eigene Name in anderer Schreibweise ist keine Dublette.
        $seite = $client->request('GET', $adresse);
        $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'KIRCHBERG MITTAGS']));
        self::assertResponseRedirects($adresse, 303);
        self::assertSame('KIRCHBERG MITTAGS', $this->listenName($client, (int) $liste->getId()));
    }

    public function testAk28UmbenennenAufDenNamenEinerAnderenListeSteht422AmFeld(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $this->liste($client, $konto, 'Mit Oma');
        $liste = $this->liste($client, $konto, 'Kirchberg');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'mit oma']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Eine Liste mit diesem Namen hast du schon.', trim($antwort->filter('#saved_list_name_name_error')->text()));
        self::assertSame('Kirchberg', trim($antwort->filter('h1')->text()), 'Die Überschrift trägt den gespeicherten Namen.');
        self::assertSame('mit oma', $antwort->filter('input[name="'.self::FELD.'"]')->attr('value'));
        self::assertSame('Kirchberg', $this->listenName($client, (int) $liste->getId()));
    }

    public function testAk29RueckfrageNenntNameUndZahlUndLoeschtNichts(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, $this->neueLokale($client, 4));

        $listenseite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());
        $rueckfrage = $client->click($listenseite->selectLink('Liste löschen')->link());

        self::assertResponseIsSuccessful();
        self::assertSame('Liste „Kirchberg“ mit 4 Lokalen löschen?', trim($rueckfrage->filter('h1')->text()));
        self::assertStringContainsString('Die Lokale selbst, deine Favoriten und deine Notizen bleiben.', $rueckfrage->text());
        self::assertSame('Löschen', trim($rueckfrage->filter('form[action$="/loeschen"] button[type="submit"]')->text()));
        self::assertSame(
            self::LOCALE.'/gespeichert/listen/'.$liste->getId(),
            $rueckfrage->selectLink('Abbrechen')->attr('href'),
        );
        self::assertCount(0, $rueckfrage->filter('[onsubmit], [onclick]'), 'Die Rückfrage braucht kein JavaScript.');
        self::assertSame(4, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    public function testAk30AbbrechenFuehrtZurueckUndAendertNichts(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, $this->neueLokale($client, 4));

        $rueckfrage = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId().'/loeschen');
        $listenseite = $client->click($rueckfrage->selectLink('Abbrechen')->link());

        self::assertResponseIsSuccessful();
        self::assertSame('Kirchberg', trim($listenseite->filter('h1')->text()));
        self::assertSame('Kirchberg', $this->listenName($client, (int) $liste->getId()));
        self::assertSame(4, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    public function testAk30LoeschenFuehrtZurUebersichtUndLaesstHerzUndNotizStehen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 4);
        $this->favoriten($client, $konto, $lokale);
        $this->notiz($client, $konto, $lokale[0], 'Rampe steil');
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, $lokale);
        $rest = $this->deckelRest($client, $konto);

        $rueckfrage = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId().'/loeschen');
        $client->submit($this->formByAction($rueckfrage, '/gespeichert/listen/'.$liste->getId().'/loeschen'));

        self::assertResponseRedirects(self::LOCALE.'/gespeichert', 303);
        self::assertStringContainsString('Liste „Kirchberg“ gelöscht.', $client->followRedirect()->text());
        self::assertNull($this->listenName($client, (int) $liste->getId()));
        self::assertSame(0, $this->eintragsZahl($client, (int) $liste->getId()));
        self::assertSame(4, $this->sql($client, 'SELECT COUNT(*) FROM favorite WHERE user_id = ?', [$konto->getId()]));
        self::assertSame(1, $this->sql($client, 'SELECT COUNT(*) FROM restaurant_note WHERE user_id = ?', [$konto->getId()]));
        self::assertSame($rest - 1, $this->deckelRest($client, $konto));
    }

    public function testAk26AusListeEntfernenOhneJavaScriptFuehrtZurNaechstenKarte(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites, $drittes] = $this->neueLokale($client, 3);
        $this->favoriten($client, $konto, [$zweites]);
        $this->notiz($client, $konto, $zweites, 'Seiteneingang');
        $andere = $this->liste($client, $konto, 'Andere');
        $this->eintraege($client, $andere, [$zweites]);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        // Angezeigt: drittes, zweites, erstes.
        $this->eintraege($client, $liste, [$erstes, $zweites, $drittes]);
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $seite = $client->request('GET', $adresse);
        $client->submit($this->entfernenFormular($seite, $zweites->getId()));

        self::assertResponseRedirects($adresse.'#karte-'.$erstes->getId(), 303);
        $danach = $client->followRedirect();
        self::assertStringContainsString($zweites->getName().' aus „Kirchberg“ entfernt.', $danach->text());
        self::assertCount(0, $danach->filter('#karte-'.$zweites->getId()));
        self::assertCount(2, $danach->filter('[id^="karte-"]'));
        // Herz, andere Liste und Notiz bleiben.
        self::assertSame(1, $this->sql($client, 'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?', [$konto->getId(), $zweites->getId()]));
        self::assertSame(1, $this->eintragsZahl($client, (int) $andere->getId()));
        self::assertSame(1, $this->sql($client, 'SELECT COUNT(*) FROM restaurant_note WHERE user_id = ? AND restaurant_id = ?', [$konto->getId(), $zweites->getId()]));
        // AK-66: Entfernen erzeugt kein Ereignis.
        self::assertCount(0, $danach->filter('[data-controller="usage-event"]'));
    }

    public function testAk26AusListeEntfernenMitTurboErsetztDasRasterUndMarkiertDieNaechsteKarte(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites, $drittes] = $this->neueLokale($client, 3);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$erstes, $zweites, $drittes]);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());
        $client->submit($this->entfernenFormular($seite, $zweites->getId()), [], ['HTTP_ACCEPT' => self::STREAM]);

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/vnd.turbo-stream.html', (string) $client->getResponse()->headers->get('Content-Type'));
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<turbo-stream action="replace" method="morph" target="gespeichert-karten">', $inhalt);
        self::assertStringContainsString('<turbo-stream action="update" target="meldung-live">', $inhalt);
        self::assertStringContainsString($zweites->getName().' aus „Kirchberg“ entfernt.', html_entity_decode($inhalt));

        $raster = $this->raster($inhalt);
        self::assertCount(0, $raster->filter('#karte-'.$zweites->getId()));
        $markiert = $raster->filter('[data-fokus-nach-aktualisierung]');
        self::assertCount(1, $markiert, 'Genau ein Fokusziel.');
        self::assertCount(1, $raster->filter('[data-fokus-nach-aktualisierung] > #karte-'.$erstes->getId()), 'Fokus auf der nächsten Karte.');
        self::assertSame(2, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    public function testAk26LetzteKarteFokussiertDieVorigeUndDieLetzteUebrigeDenLeertext(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$erstes, $zweites] = $this->neueLokale($client, 2);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        // Angezeigt: zweites, erstes.
        $this->eintraege($client, $liste, [$erstes, $zweites]);
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $seite = $client->request('GET', $adresse);
        $client->submit($this->entfernenFormular($seite, $erstes->getId()), [], ['HTTP_ACCEPT' => self::STREAM]);
        $raster = $this->raster((string) $client->getResponse()->getContent());
        self::assertCount(1, $raster->filter('[data-fokus-nach-aktualisierung] > #karte-'.$zweites->getId()), 'Bei der letzten Karte: die vorige.');

        $seite = $client->request('GET', $adresse);
        $client->submit($this->entfernenFormular($seite, $zweites->getId()), [], ['HTTP_ACCEPT' => self::STREAM]);
        $raster = $this->raster((string) $client->getResponse()->getContent());
        $leer = $raster->filter('[data-fokus-nach-aktualisierung]');
        self::assertCount(1, $leer);
        self::assertSame('gespeichert-leer', $leer->attr('id'));
        self::assertSame('-1', $leer->attr('tabindex'));
        self::assertStringContainsString('Diese Liste ist noch leer.', $leer->text());

        // Ohne JavaScript: Anker auf den Leertext.
        $this->eintraege($client, $this->em($client)->find(SavedList::class, $liste->getId()), [$this->em($client)->find(Restaurant::class, $erstes->getId())]);
        $seite = $client->request('GET', $adresse);
        $client->submit($this->entfernenFormular($seite, $erstes->getId()));
        self::assertResponseRedirects($adresse.'#gespeichert-leer', 303);
    }

    public function testAk26LeerGewordeneFolgeseiteFuehrtAufDieLetzteSeite(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokale = $this->neueLokale($client, 13);
        $liste = $this->liste($client, $konto, 'Dreizehn');
        $this->eintraege($client, $liste, $lokale);
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        // Auf Seite 2 steht allein das zuerst hinzugefügte Lokal.
        $seite = $client->request('GET', $adresse.'?page=2');
        self::assertCount(1, $seite->filter('[id^="karte-"]'));
        $client->submit($this->entfernenFormular($seite, $lokale[0]->getId()), [], ['HTTP_ACCEPT' => self::STREAM]);

        $raster = $this->raster((string) $client->getResponse()->getContent());
        self::assertCount(12, $raster->filter('[id^="karte-"]'), 'Seite 1 statt einer leeren Seite 2.');
        self::assertCount(1, $raster->filter('[data-fokus-nach-aktualisierung] > #karte-'.$lokale[1]->getId()), 'Fokus auf der vorigen Karte, der letzten von Seite 1.');

        // Ohne JavaScript dasselbe als Weiterleitung. Wieder hinzugefügt steht Lokal 0 zuoberst;
        // auf Seite 2 bleibt Lokal 1 allein, die letzte Karte von Seite 1 ist dann Lokal 2.
        $this->eintraege($client, $this->em($client)->find(SavedList::class, $liste->getId()), [$this->em($client)->find(Restaurant::class, $lokale[0]->getId())]);
        $seite = $client->request('GET', $adresse.'?page=2');
        self::assertCount(1, $seite->filter('#karte-'.$lokale[1]->getId()));
        $client->submit($this->entfernenFormular($seite, $lokale[1]->getId()));
        self::assertResponseRedirects($adresse.'#karte-'.$lokale[2]->getId(), 303);
    }

    public function testAk54KontoBScheitertAnDerListeVonAMitGueltigemEigenemToken(): void
    {
        $client = static::createClient();
        $a = $this->user($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $liste = $this->liste($client, $a, 'Mit Oma');
        $this->eintraege($client, $liste, [$lokal]);
        $b = $this->loginAs($client, self::ANDERES);
        $eigene = $this->liste($client, $b, 'Eigene');
        $nummer = (int) $liste->getId();
        $basis = self::LOCALE.'/gespeichert/listen/'.$nummer;

        // Eine Seite von B öffnen: Danach trägt jede Anfrage dieselbe Herkunft wie ein echtes Absenden.
        $client->request('GET', self::LOCALE.'/gespeichert');
        $token = $this->sitzungsToken($client, 'gespeichert-liste-'.$nummer);

        $versuche = [
            ['GET', $basis, []],
            ['GET', $basis.'/loeschen', []],
            ['POST', $basis.'/umbenennen', ['saved_list_name' => ['name' => 'Gekapert', '_token' => 'csrf-token']]],
            ['POST', $basis.'/loeschen', ['_token' => $token]],
            ['POST', $basis.'/lokale/'.$lokal->getId().'/entfernen', ['_token' => $token, 'page' => '1']],
        ];
        foreach ($versuche as [$methode, $pfad, $daten]) {
            $client->request($methode, $pfad, $daten);
            self::assertResponseStatusCodeSame(404, $methode.' '.$pfad);
            self::assertStringNotContainsString('Mit Oma', self::antwortOhneQuelltextAuszug($client), $methode.' '.$pfad);
            self::assertFalse($client->getResponse()->headers->has('Location'));
        }

        self::assertSame('Mit Oma', $this->listenName($client, $nummer), 'Die Liste von A ist unverändert.');
        self::assertSame(1, $this->eintragsZahl($client, $nummer));

        // Gegenprobe: Dasselbe Token-Verfahren wirkt an der eigenen Liste — die 404 oben lag am Besitz.
        $client->request('POST', self::LOCALE.'/gespeichert/listen/'.$eigene->getId().'/loeschen', [
            '_token' => $this->sitzungsToken($client, 'gespeichert-liste-'.$eigene->getId()),
        ]);
        self::assertResponseRedirects(self::LOCALE.'/gespeichert', 303);
        self::assertNull($this->listenName($client, (int) $eigene->getId()));
    }

    public function testAk54UnbekannteNummerIstNichtGefundenWieEineFremde(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $client->request('GET', self::LOCALE.'/gespeichert');

        foreach (['/umbenennen', '/loeschen', '/lokale/'.$lokal->getId().'/entfernen'] as $zusatz) {
            $client->request('POST', self::LOCALE.'/gespeichert/listen/999999'.$zusatz, ['_token' => 'egal']);
            self::assertResponseStatusCodeSame(404, $zusatz);
        }
        self::assertSame(0, $this->listenZahl($client, $konto));
    }

    public function testAk55UnbekanntesLokalInEigenerListeIstNichtGefunden(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId());

        $client->request('POST', self::LOCALE.'/gespeichert/listen/'.$liste->getId().'/lokale/999999/entfernen', [
            '_token' => $this->sitzungsToken($client, 'gespeichert-liste-'.$liste->getId()),
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testEc09ListeAufEinemAnderenGeraetGeloeschtIstNichtGefunden(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$lokal]);
        $nummer = (int) $liste->getId();

        $offeneSeite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$nummer);
        // Das andere Gerät löscht die Liste.
        $this->em($client)->getConnection()->executeStatement('DELETE FROM saved_list WHERE id = ?', [$nummer]);

        $client->submit($this->entfernenFormular($offeneSeite, $lokal->getId()));

        self::assertResponseStatusCodeSame(404);
        self::assertSame(0, $this->listenZahl($client, $konto), 'Nichts entsteht neu.');
        self::assertSame(0, $this->eintragsZahl($client, $nummer));
    }

    public function testAk56FalschesTokenAendertNichtsUndMeldetEs(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$lokal]);
        $basis = self::LOCALE.'/gespeichert/listen/'.$liste->getId();
        $client->request('GET', $basis);

        $versuche = [
            [self::LOCALE.'/gespeichert/listen', ['saved_list_name' => ['name' => 'Neu', '_token' => 'falsch']]],
            [$basis.'/umbenennen', ['saved_list_name' => ['name' => 'Gekapert', '_token' => 'falsch']]],
            [$basis.'/loeschen', ['_token' => 'falsch']],
            [$basis.'/lokale/'.$lokal->getId().'/entfernen', ['_token' => 'falsch', 'page' => '1']],
        ];
        foreach ($versuche as [$pfad, $daten]) {
            $client->request('POST', $pfad, $daten);
            self::assertResponseRedirects(null, 303, $pfad);
            self::assertStringContainsString('Ungültiges CSRF-Token. Bitte versuche es erneut.', $client->followRedirect()->text(), $pfad);
        }

        self::assertSame(1, $this->listenZahl($client, $konto));
        self::assertSame('Kirchberg', $this->listenName($client, (int) $liste->getId()));
        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    public function testAk57AusgeschoepfterDeckelMeldetUndSpeichertNichts(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$lokal] = $this->neueLokale($client, 1);
        $liste = $this->liste($client, $konto, 'Kirchberg');
        $this->eintraege($client, $liste, [$lokal]);
        $basis = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $uebersicht = $client->request('GET', self::LOCALE.'/gespeichert');
        $listenseite = $client->request('GET', $basis);
        $rueckfrage = $client->request('GET', $basis.'/loeschen');
        // when@test steht auf 10000 — aufgebraucht ist derselbe Zustand wie nach 120 in Produktion.
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$konto->getId())->consume(10000);

        $client->submit($this->formWithField($uebersicht, self::FELD, [self::FELD => 'Neu']));
        self::assertStringContainsString('Zu viele Änderungen.', $client->followRedirect()->text());
        $client->submit($this->formWithField($listenseite, self::FELD, [self::FELD => 'Umbenannt']));
        self::assertStringContainsString('Zu viele Änderungen.', $client->followRedirect()->text());
        $client->submit($this->formByAction($rueckfrage, $basis.'/loeschen'));
        self::assertStringContainsString('Zu viele Änderungen.', $client->followRedirect()->text());
        $client->submit($this->entfernenFormular($listenseite, $lokal->getId()));
        self::assertStringContainsString('Zu viele Änderungen.', $client->followRedirect()->text());

        self::assertSame(1, $this->listenZahl($client, $konto));
        self::assertSame('Kirchberg', $this->listenName($client, (int) $liste->getId()));
        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    public function testAk69UnbestaetigtesKontoDarfEineListeAnlegen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'unverified@endlech.lu');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'Unbestätigt']));

        self::assertResponseRedirects(self::LOCALE.'/gespeichert', 303);
        self::assertSame(1, $this->listenZahl($client, $konto));
    }

    private function entfernenFormular(Crawler $seite, ?int $lokalId): Form
    {
        $knoten = $seite->filter('form[action$="/lokale/'.$lokalId.'/entfernen"]');
        self::assertCount(1, $knoten, 'Formular „Aus Liste entfernen“ für Lokal '.$lokalId);

        return $knoten->form();
    }
}
