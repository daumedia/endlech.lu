<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature 17, T27: Randfälle, die noch kein anderer Prüflauf trägt — EC-10, EC-12, EC-13, EC-14,
 * dazu AK-46 auf einer Listenseite in allen fünf Sprachen. Aus dem Fehlerauftrag (QA vom 2026-11-14):
 * BF-212 (Nummern jenseits von `PHP_INT_MAX` auf allen fünf Wegen) und BF-213 (riesige Seitenzahl
 * beim Entfernen).
 */
final class SavedEdgeCasesTest extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string STREAM = 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deckelZuruecksetzen();
    }

    public function testEc10FilterAktivHerzEntferntKarteFehltNachDemNeuladen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'user@endlech.lu');
        [$eins, $zwei] = $this->neueLokale($client, 2, 'Ec10');
        $this->favoriten($client, $konto, [$eins, $zwei]);
        $filter = self::LOCALE.'/restaurants?favorites=1&sort=name';

        $seite = $client->request('GET', $filter);
        $client->submit($seite->filter('#favorit-'.$eins->getId())->form(), [], ['HTTP_ACCEPT' => self::STREAM]);

        // Mit Turbo wird nur das Herz getauscht — die Karte darf bis zum nächsten Laden stehen bleiben.
        $antwort = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('target="favorit-'.$eins->getId().'"', $antwort);
        self::assertStringNotContainsString('target="karte-'.$eins->getId().'"', $antwort);

        $neu = $client->request('GET', $filter);
        self::assertCount(0, $neu->filter('#karte-'.$eins->getId()), 'Nach dem Neuladen fehlt sie.');
        self::assertCount(1, $neu->filter('#karte-'.$zwei->getId()));
    }

    public function testEc12GemerktesLokalBleibtAuchWennEsMerkmaleVerliert(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'user@endlech.lu');
        [$lokal] = $this->neueLokale($client, 1, 'Ec12');
        $lokal->setIsWheelchairAccessible(true)->setIsVerified(true);
        $this->em($client)->flush();
        $this->favoriten($client, $konto, [$lokal]);

        $lokal->setIsWheelchairAccessible(false)->setIsVerified(false);
        $this->em($client)->flush();

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/favoriten');
        self::assertCount(1, $seite->filter('#karte-'.$lokal->getId()));
        self::assertStringNotContainsString('title="Rollstuhl', (string) $seite->filter('#karte-'.$lokal->getId())->html(), 'Die Karte zeigt den aktuellen Stand.');
    }

    public function testEc13UnbestaetigtesKontoBehaeltSeineFavoritenUndSiehtSie(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'unverified@endlech.lu');
        [$lokal] = $this->neueLokale($client, 1, 'Ec13');
        $this->favoriten($client, $konto, [$lokal]);
        $this->notiz($client, $konto, $lokal, 'Notiz eines unbestätigten Kontos');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/favoriten');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $seite->filter('#karte-'.$lokal->getId()));
        self::assertStringContainsString('Notiz eines unbestätigten Kontos', $seite->text());
    }

    /** Größer als `PHP_INT_MAX` — passte auf `\d+`, scheiterte an `int $id` (BF-212). */
    private const string ZU_GROSS = '99999999999999999999';

    /**
     * BF-212: Alle fünf Wege mit einer Listen- bzw. Lokalnummer im Pfad antworten auf eine Zahl
     * jenseits von `PHP_INT_MAX` wie auf jede unbekannte Nummer (AK-53) — nicht mit HTTP 500.
     * Die Reproduktion der QA prüft die beiden GET-Wege; hier stehen auch die drei POST-Wege.
     */
    public function testBf212NummerJenseitsVonPhpIntMaxIstAufAllenFuenfWegen404(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'user@endlech.lu');
        [$lokal] = $this->neueLokale($client, 1, 'Bf212');
        $liste = $this->liste($client, $konto, 'Bf212');
        $this->eintraege($client, $liste, [$lokal]);
        $eigene = self::LOCALE.'/gespeichert/listen/'.$liste->getId();

        $wege = [
            ['GET', self::LOCALE.'/gespeichert/listen/'.self::ZU_GROSS],
            ['GET', self::LOCALE.'/gespeichert/listen/'.self::ZU_GROSS.'/loeschen'],
            ['POST', self::LOCALE.'/gespeichert/listen/'.self::ZU_GROSS.'/umbenennen'],
            ['POST', self::LOCALE.'/gespeichert/listen/'.self::ZU_GROSS.'/loeschen'],
            ['POST', self::LOCALE.'/gespeichert/listen/'.self::ZU_GROSS.'/lokale/'.$lokal->getId().'/entfernen'],
            ['POST', $eigene.'/lokale/'.self::ZU_GROSS.'/entfernen'],
        ];
        foreach ($wege as [$methode, $pfad]) {
            $client->request($methode, $pfad, [], [], ['HTTP_REFERER' => 'http://localhost/']);
            self::assertResponseStatusCodeSame(404, $methode.' '.$pfad);
        }

        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()), 'Nichts geändert.');
    }

    /**
     * BF-213: Eine riesige Seitenzahl im Formular „Aus Liste entfernen“ wird wie jede Seite hinter
     * dem Ende behandelt — der Eintrag ist weg, die Weiterleitung führt auf die letzte Seite.
     * Vorher lief `(seite - 1) * 12` zu `float` über (TypeError, HTTP 500, Eintrag blieb).
     */
    public function testBf213RiesigeSeitenzahlBeimEntfernenFuehrtAufDieLetzteSeite(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'user@endlech.lu');
        [$eins, $zwei] = $this->neueLokale($client, 2, 'Bf213');
        $liste = $this->liste($client, $konto, 'Bf213');
        $this->eintraege($client, $liste, [$eins, $zwei]);
        $adresse = self::LOCALE.'/gespeichert/listen/'.$liste->getId();
        $form = $client->request('GET', $adresse)
            ->filter('form[action$="/lokale/'.$eins->getId().'/entfernen"]')->form();
        $form['page']->setValue((string) \PHP_INT_MAX);

        $client->submit($form);

        self::assertResponseStatusCodeSame(303);
        self::assertSame($adresse.'#karte-'.$zwei->getId(), $client->getResponse()->headers->get('Location'));
        self::assertSame(1, $this->eintragsZahl($client, (int) $liste->getId()));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sprachen(): iterable
    {
        yield 'lb' => ['lb', 'Ëmbenennen'];
        yield 'de' => ['de', 'Umbenennen'];
        yield 'fr' => ['fr', 'Renommer'];
        yield 'en' => ['en', 'Rename'];
        yield 'pt' => ['pt', 'Mudar o nome'];
    }

    #[DataProvider('sprachen')]
    public function testEc14Ak46ListenseiteInJederSpracheMitUnveraendertemNamen(string $sprache, string $umbenennen): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'user@endlech.lu');
        $liste = $this->liste($client, $konto, 'Mëttes bei der Bomi');

        $seite = $client->request('GET', '/'.$sprache.'/gespeichert/listen/'.$liste->getId());

        self::assertResponseIsSuccessful();
        self::assertSame($sprache, $seite->filter('html')->attr('lang'));
        self::assertSame('Mëttes bei der Bomi', trim($seite->filter('h1')->text()));
        self::assertStringContainsString($umbenennen, $seite->text());
    }
}
