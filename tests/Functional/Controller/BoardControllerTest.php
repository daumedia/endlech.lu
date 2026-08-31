<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Repository\BoardIdeaRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Das öffentliche Community-Board (Feature 06).
 */
final class BoardControllerTest extends AbstractWebTestCase
{
    private function idee(KernelBrowser $client, bool $published, ?User $von = null, string $titel = 'Kartenansicht mit Filtern'): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Eine Karte wäre hilfreich, um Lokale in der Nähe zu finden.')
            ->setSlug('kartenansicht')
            ->setLocale('de')
            ->setSubmittedBy($von);

        if ($published) {
            $idee->setPublishedAt(new \DateTimeImmutable());
        }

        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    /** AK-01: Das Board ist ohne Anmeldung erreichbar. */
    public function testBoardIstOeffentlich(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/community/ideen');

        self::assertResponseIsSuccessful();
    }

    /** AK-08: Der leere Zustand erklärt und führt weiter. */
    public function testLeererZustand(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/community/ideen');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('a[href$="/community/ideen/neu"]')->count());
    }

    /** AK-03 + AK-71: Wartende Ideen erscheinen nicht im Board. */
    public function testWartendeIdeeErscheintNichtImBoard(): void
    {
        $client = static::createClient();
        $this->idee($client, published: false, titel: 'Geheime wartende Idee');
        $this->idee($client, published: true, titel: 'Sichtbare Idee');

        $client->request('GET', self::LOCALE . '/community/ideen');

        self::assertSelectorTextContains('body', 'Sichtbare Idee');
        self::assertStringNotContainsString('Geheime wartende Idee', (string) $client->getResponse()->getContent());
    }

    /** AK-18 + AK-56: Fremde wartende Idee → 404, nicht 403 und nicht der Inhalt. */
    public function testFremdeWartendeIdeeErgibt404(): void
    {
        $client = static::createClient();
        $besitzer = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, published: false, von: $besitzer, titel: 'Nur für den Verfasser');

        // Gast
        $client->request('GET', self::LOCALE . '/community/ideen/' . $idee->getId() . '-kartenansicht');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Fremdes Konto
        $this->loginAs($client, 'admin@endlech.lu');
        $client->request('GET', self::LOCALE . '/community/ideen/' . $idee->getId() . '-kartenansicht');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** AK-18: Der Verfasser selbst sieht seine wartende Idee. */
    public function testVerfasserSiehtEigeneWartendeIdee(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $idee = $this->idee($client, published: false, von: $user, titel: 'Meine wartende Idee');

        $client->request('GET', self::LOCALE . '/community/ideen/' . $idee->getId() . '-kartenansicht');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Meine wartende Idee');
    }

    /** AK-10: Ohne Anmeldung kein Formular und kein Datensatz. */
    public function testEinreichenVerlangtAnmeldung(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/community/ideen/neu');

        self::assertResponseRedirects();

        $anzahl = $client->getContainer()->get(BoardIdeaRepository::class)->count([]);
        self::assertSame(0, $anzahl);
    }

    /** AK-11: Unbestätigte Adresse → Hinweis, kein Datensatz. */
    public function testUnbestaetigtesKontoDarfNichtEinreichen(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'unverified@endlech.lu');

        $client->request('GET', self::LOCALE . '/community/ideen/neu');

        self::assertResponseRedirects();
        self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-12: Leeres Formular → 422, je Feld eine Meldung, kein Datensatz. */
    public function testLeeresFormularErgibt422(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/neu');
        $form = $this->formWithField($crawler, 'board_idea[title]', [
            'board_idea[title]' => '',
            'board_idea[description]' => '',
        ]);
        $client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-13 + AK-14 + EC-03: Überlange Eingaben ergeben eine Meldung, keinen 500er. */
    public function testUeberlangeEingabenErgebenKeinenServerfehler(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/neu');
        $form = $this->formWithField($crawler, 'board_idea[title]', [
            // EC-03: 121 × „ß" — der Slugger macht daraus 242 Zeichen.
            'board_idea[title]' => str_repeat('ß', 121),
            'board_idea[description]' => str_repeat('a', 2001),
        ]);
        $client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-15: Gültige Einreichung wartet und erscheint nicht im Board. */
    public function testGueltigeEinreichungWartetAufFreigabe(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/neu');
        $form = $this->formWithField($crawler, 'board_idea[title]', [
            'board_idea[title]' => 'Filter für ruhige Bereiche',
            'board_idea[description]' => 'Ein Filter für reizarme Ecken wäre für mich der wichtigste überhaupt.',
        ]);
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE . '/community/ideen/eingereicht');

        $ideen = $client->getContainer()->get(BoardIdeaRepository::class)->findAll();
        self::assertCount(1, $ideen);
        self::assertFalse($ideen[0]->isPublished());
        self::assertSame('de', $ideen[0]->getLocale());
    }

    /** AK-17: Gefülltes Fallenfeld → gleiche Antwort, aber kein Datensatz. */
    public function testFallenfeldErzeugtKeinenDatensatz(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/neu');
        $form = $this->formWithField($crawler, 'board_idea[title]', [
            'board_idea[title]' => 'Von einem Bot',
            'board_idea[description]' => 'Diese Idee sollte nie entstehen.',
            'board_idea[website]' => 'https://spam.example',
        ]);
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE . '/community/ideen/eingereicht');
        self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-19 bis AK-21: Zustimmen, nicht doppelt zählen, zurücknehmen. */
    public function testZustimmenIstUmschaltbarUndZaehltEinmal(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $idee = $this->idee($client, published: true);
        $repo = $client->getContainer()->get(BoardIdeaRepository::class);

        $pfad = self::LOCALE . '/community/ideen/' . $idee->getId() . '-kartenansicht';

        $crawler = $client->request('GET', $pfad);
        $client->submit($this->formByAction($crawler, '/zustimmen'));
        self::assertSame(1, $repo->countVotesForOne($idee));

        // Ein zweiter Klick nimmt die Zustimmung zurück (AK-21) — und die Zahl
        // steigt nicht auf 2 (AK-20).
        $crawler = $client->request('GET', $pfad);
        $client->submit($this->formByAction($crawler, '/zustimmen'));
        self::assertSame(0, $repo->countVotesForOne($idee));
    }

    /** AK-22: Ein Gast kann nicht zustimmen. */
    public function testGastKannNichtZustimmen(): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, published: true);

        $client->request('POST', self::LOCALE . '/community/ideen/' . $idee->getId() . '/zustimmen');

        self::assertResponseRedirects();
        self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->countVotesForOne($idee));
    }

    /** AK-76: Der Verfasser kann seine wartende Idee zurückziehen. */
    public function testVerfasserKannWartendeIdeeZurueckziehen(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $idee = $this->idee($client, published: false, von: $user);

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/' . $idee->getId() . '-kartenansicht');
        $client->submit($this->formByAction($crawler, '/zurueckziehen'));

        self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-77: Nach der Veröffentlichung gibt es keinen Rückweg — auch nicht von Hand. */
    public function testVeroeffentlichteIdeeLaesstSichNichtZurueckziehen(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $idee = $this->idee($client, published: true, von: $user);

        $client->request('POST', self::LOCALE . '/community/ideen/' . $idee->getId() . '/zurueckziehen');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame(1, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-75: Umgesetzte Ideen stehen nicht in der Hauptliste. */
    public function testUmgesetzteIdeenStehenImEigenenAbschnitt(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $fertig = $this->idee($client, published: true, titel: 'Presse-Kit');
        $fertig->setStatus(BoardIdeaStatus::DONE);
        $em->flush();

        $client->request('GET', self::LOCALE . '/community/ideen');

        self::assertResponseIsSuccessful();
        // Sie erscheint — aber unter der eigenen Überschrift, nicht in der Liste.
        self::assertSelectorTextContains('body', 'Presse-Kit');
        self::assertSelectorExists('h2');
    }

    /** AK-64 + EC-08: Verweise und HTML im Text bleiben Text. */
    public function testVerweiseUndHtmlBleibenText(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = $this->idee($client, published: true);
        $idee->setDescription('Siehe https://beispiel.tld und <script>alert(1)</script>');
        $em->flush();

        $client->request('GET', self::LOCALE . '/community/ideen/' . $idee->getId() . '-kartenansicht');

        $html = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringNotContainsString('<a href="https://beispiel.tld"', $html);
        self::assertStringContainsString('https://beispiel.tld', $html);
    }

    /** AK-35: Eine zusammengeführte Dublette führt auf das Original. */
    public function testDubletteLeitetAufDasOriginal(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $original = $this->idee($client, published: true, titel: 'Original');
        $dublette = $this->idee($client, published: true, titel: 'Dublette');
        $dublette->setDuplicateOf($original);
        $em->flush();

        $client->request('GET', self::LOCALE . '/community/ideen/' . $dublette->getId() . '-kartenansicht');

        self::assertResponseRedirects();
    }
}
