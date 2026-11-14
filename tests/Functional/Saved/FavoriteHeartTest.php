<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Yaml\Yaml;

/**
 * Feature 17, T14: das Herz — merken, entfernen, als Gast, mit Turbo-Stream, mit Deckel.
 */
final class FavoriteHeartTest extends AbstractWebTestCase
{
    private const string KONTO = 'user@endlech.lu';
    private const string STREAM = 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml';

    protected function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        foreach ([self::KONTO, 'admin@endlech.lu', 'unverified@endlech.lu'] as $email) {
            $client->getContainer()->get('limiter.saved_change')->create('konto-'.$this->user($client, $email)->getId())->reset();
        }
        self::ensureKernelShutdown();
    }

    public function testAk02MerkenAufDerDetailseiteOhneJavaScriptFuehrtZurueckZumHerz(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);

        $this->herzAbschicken($client, $lokal);

        self::assertResponseRedirects(self::LOCALE.'/restaurants/'.$lokal->getId().'#favorit-'.$lokal->getId(), 303);
        self::assertSame(1, $this->zahl($client, $konto, $lokal));
        $seite = $client->followRedirect();
        self::assertStringContainsString('Gemerkt: '.$lokal->getName(), $seite->text());
        self::assertSame(
            $lokal->getName().' aus Favoriten entfernen',
            trim($seite->filter('#favorit-knopf-'.$lokal->getId())->text()),
        );
    }

    public function testAk01MitTurboWirdNurDasHerzGetauschtUndDieMeldungAngesagt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);

        $this->herzAbschicken($client, $lokal, stream: true);

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/vnd.turbo-stream.html', (string) $client->getResponse()->headers->get('Content-Type'));
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<turbo-stream action="replace" method="morph" target="favorit-'.$lokal->getId().'">', $inhalt);
        self::assertStringContainsString('<turbo-stream action="update" target="meldung-live">', $inhalt);
        self::assertStringContainsString($lokal->getName().' aus Favoriten entfernen', $inhalt);
        self::assertStringContainsString('Gemerkt: '.$lokal->getName(), $inhalt);
        self::assertStringContainsString('data-usage-event-name-value="herz_gesetzt"', $inhalt, 'AK-66: Herz gesetzt wird gezählt.');
    }

    public function testAk03EntfernenNimmtDasHerzZurueck(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->herzAbschicken($client, $lokal);

        $this->herzAbschicken($client, $lokal);

        self::assertSame(0, $this->zahl($client, $konto, $lokal));
        self::assertStringContainsString('Aus Favoriten entfernt: '.$lokal->getName(), $client->followRedirect()->text());
    }

    public function testAk05VeralteteSeiteEntferntNichtsUndZaehltNicht(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $alteSeite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $formular = $alteSeite->filter('#favorit-'.$lokal->getId())->form();
        $client->submit($formular);                 // erster Tipp: gemerkt
        $deckel = $this->deckel($client, $konto);

        $client->submit($formular, [], ['HTTP_ACCEPT' => self::STREAM]);  // zweiter Tipp auf der alten Seite

        self::assertSame(1, $this->zahl($client, $konto, $lokal), 'Das Lokal bleibt gemerkt, genau einmal.');
        self::assertSame($deckel, $this->deckel($client, $konto), 'AK-58: keine Änderung, kein Verbrauch.');
        self::assertStringNotContainsString('herz_gesetzt', (string) $client->getResponse()->getContent());
    }

    public function testAk08GastLandetAufDerAnmeldungUndNichtsIstGespeichert(): void
    {
        $client = static::createClient();
        $lokal = $this->lokal($client);
        $vorher = $this->alleFavoriten($client);

        $this->herzAbschicken($client, $lokal);

        self::assertResponseRedirects(self::LOCALE.'/login', 303);
        self::assertSame($vorher, $this->alleFavoriten($client));
    }

    public function testAk55UnbekanntesLokalIst404(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        $client->request('POST', self::LOCALE.'/restaurants/999999/merken', ['_token' => 'egal', 'ziel' => '/de/restaurants']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testAk56FalschesTokenAendertNichts(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);

        $client->request('POST', self::LOCALE.'/restaurants/'.$lokal->getId().'/merken', ['_token' => 'falsch', 'ziel' => '/de/restaurants']);

        self::assertSame(0, $this->zahl($client, $konto, $lokal));
        self::assertStringContainsString('Ungültiges CSRF-Token', $client->followRedirect()->text());
    }

    public function testAk57EinhundertEinundzwanzigsteAenderungWirdAbgewiesen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        // when@test steht auf 10000 — aufgebraucht ist derselbe Zustand wie nach 120 in Produktion.
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$konto->getId())->consume(10000);
        $client->setServerParameter('REMOTE_ADDR', '203.0.113.17');

        $client->submit($seite->filter('#favorit-'.$lokal->getId())->form());

        self::assertSame(0, $this->zahl($client, $konto, $lokal));
        self::assertStringContainsString('Zu viele Änderungen', $client->followRedirect()->text());
    }

    public function testAk57AnderesKontoAmSelbenAnschlussIstNichtGesperrt(): void
    {
        $client = static::createClient();
        $erstes = $this->user($client, self::KONTO);
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$erstes->getId())->consume(10000);
        $zweites = $this->loginAs($client, 'admin@endlech.lu');
        $lokal = $this->lokal($client);
        $client->setServerParameter('REMOTE_ADDR', '203.0.113.17');

        $this->herzAbschicken($client, $lokal);

        self::assertSame(1, $this->zahl($client, $zweites, $lokal));
    }

    public function testAk69UnbestaetigtesKontoDarfMerken(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'unverified@endlech.lu');
        $lokal = $this->lokal($client);

        $this->herzAbschicken($client, $lokal);

        self::assertSame(1, $this->zahl($client, $konto, $lokal));
    }

    public function testAk57ProduktionswertIst120JeStundeGleitend(): void
    {
        $konfiguration = Yaml::parseFile(static::getContainer()->getParameter('kernel.project_dir').'/config/packages/framework.yaml');
        $limiter = $konfiguration['framework']['rate_limiter']['saved_change'];

        self::assertSame('sliding_window', $limiter['policy']);
        self::assertSame(120, $limiter['limit']);
        self::assertSame('1 hour', $limiter['interval']);
    }

    private function herzAbschicken(KernelBrowser $client, Restaurant $lokal, bool $stream = false): void
    {
        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit(
            $seite->filter('#favorit-'.$lokal->getId())->form(),
            [],
            $stream ? ['HTTP_ACCEPT' => self::STREAM] : [],
        );
    }

    private function lokal(KernelBrowser $client): Restaurant
    {
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($lokal);

        return $lokal;
    }

    private function zahl(KernelBrowser $client, User $konto, Restaurant $lokal): int
    {
        return (int) $client->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?',
            [$konto->getId(), $lokal->getId()],
        );
    }

    private function alleFavoriten(KernelBrowser $client): int
    {
        return (int) $client->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne('SELECT COUNT(*) FROM favorite');
    }

    private function deckel(KernelBrowser $client, User $konto): int
    {
        return $client->getContainer()->get('limiter.saved_change')->create('konto-'.$konto->getId())->consume(0)->getRemainingTokens();
    }
}
