<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

/**
 * Feature 17 · dritte QA vom 2026-11-14: die Wege, auf denen ein Listenname hereinkommt, nach BF-220.
 *
 * Die Prüfläufe des Baus prüfen BF-220 am Dienst und an `SavedText`. Hier über HTTP auf allen drei Wegen
 * (Anlegen auf „Gespeichert“, „Neue Liste“ auf der Detailseite, Umbenennen) — und EC-07 mit Zeichen, die NFC
 * in mehrere Codepunkte zerlegt: Gezählt wird der normalisierte Name, nie ein Serverfehler.
 */
final class Qa17Nachpruefung3Test extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string KONTO = 'user@endlech.lu';
    private const string FELD = 'saved_list_name[name]';
    private const string NEU = 'restaurant_lists[newList]';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deckelZuruecksetzen();
    }

    public function testBf220SchlussSigmaIstAufDerDetailseiteEineDubletteOhneHaekchenAenderung(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->erstesLokal($client);
        $ziel = $this->liste($client, $konto, 'ΟΔΟΣ');

        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $formular = $this->formWithField($seite, self::NEU, [self::NEU => 'οδος']);
        $this->haekchenSetzen($formular, (string) $ziel->getId());
        $antwort = $client->submit($formular);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Eine Liste mit diesem Namen hast du schon.', $antwort->filter('#in-listen')->text());
        self::assertSame(1, $this->listenZahl($client, $konto));
        self::assertSame(0, $this->eintragsZahl($client, (int) $ziel->getId()), 'AK-23: keine Häkchenänderung aus demselben Absenden.');
    }

    public function testBf220UmbenennenAufSchlussSigmaFormEinerAnderenListeIst422(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $this->liste($client, $konto, 'ΟΔΟΣ');
        $andere = $this->liste($client, $konto, 'Paſta');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$andere->getId());
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'Οδος']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Eine Liste mit diesem Namen hast du schon.', trim($antwort->filter('#saved_list_name_name_error')->text()));
        self::assertSame('Paſta', $this->listenName($client, (int) $andere->getId()));
    }

    public function testBf220LangesSIstEineDubletteBeimAnlegen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $this->liste($client, $konto, 'Paſta');

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $client->submit($this->formWithField($seite, self::FELD, [self::FELD => 'PASTA']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(1, $this->listenZahl($client, $konto));
    }

    /**
     * EC-07: 21 × U+FB2C (hebräisch, NFC zerlegt in je drei Codepunkte) sind 63 Zeichen → Meldung aus AK-18;
     * 20 × sind genau 60 → gespeichert, Name und Schlüssel je 60 Zeichen. Auf beiden Anlegewegen.
     */
    public function testEc07ZerlegteZeichenZaehlenNormalisiertAufBeidenWegen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->erstesLokal($client);

        $seite = $client->request('GET', self::LOCALE.'/gespeichert');
        $antwort = $client->submit($this->formWithField($seite, self::FELD, [self::FELD => str_repeat("\u{FB2C}", 21)]));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('Der Name darf höchstens 60 Zeichen lang sein.', trim($antwort->filter('#saved_list_name_name_error')->text()));

        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit($this->formWithField($seite, self::NEU, [self::NEU => str_repeat("\u{FB2C}", 21)]));
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->listenZahl($client, $konto));

        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit($this->formWithField($seite, self::NEU, [self::NEU => str_repeat("\u{FB2C}", 20)]));
        self::assertResponseRedirects(null, 303);
        self::assertSame(1, $this->sql($client, 'SELECT COUNT(*) FROM saved_list WHERE user_id = ? AND CHAR_LENGTH(name) = 60 AND CHAR_LENGTH(name_key) = 60', [$konto->getId()]));
    }

    private function erstesLokal(KernelBrowser $client): Restaurant
    {
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($lokal);

        return $lokal;
    }

    private function haekchenSetzen(Form $formular, string $listId): void
    {
        $felder = $formular['restaurant_lists[lists]'];
        foreach (\is_array($felder) ? $felder : [$felder] as $feld) {
            \assert($feld instanceof ChoiceFormField);
            \in_array($listId, $feld->availableOptionValues(), true) ? $feld->tick() : $feld->untick();
        }
    }
}
