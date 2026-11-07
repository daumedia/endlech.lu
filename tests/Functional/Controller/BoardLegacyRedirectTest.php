<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Feature 16 · T03: Die alten Adressen unter `/community/ideen` leiten dauerhaft und
 * in einem Sprung auf `/roadmap/ideen` — vor allem die Links in bereits verschickten
 * Mails (AK-22).
 */
final class BoardLegacyRedirectTest extends AbstractWebTestCase
{
    private const array SPRACHEN = ['lb', 'de', 'fr', 'en', 'pt'];

    private function idee(KernelBrowser $client, string $titel, string $slug, bool $veroeffentlicht = true, ?User $verfasser = null): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung zu ' . $titel)
            ->setSlug($slug)
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::NEW)
            ->setSubmittedBy($verfasser);
        if ($veroeffentlicht) {
            $idee->setPublishedAt(new \DateTimeImmutable('-1 day'));
        }
        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    private function ziel(KernelBrowser $client): string
    {
        return (string) $client->getResponse()->headers->get('Location');
    }

    /** AK-21: Übersicht, Formular und Bestätigung — 301 in einem Sprung, dieselbe Sprache. */
    #[DataProvider('seitenJeSprache')]
    public function testAk21SeitenadressenLeitenDauerhaftWeiter(string $locale, string $alt, string $neu): void
    {
        $client = static::createClient();
        $client->request('GET', '/' . $locale . $alt);

        self::assertResponseStatusCodeSame(301);
        self::assertSame('/' . $locale . $neu, $this->ziel($client));
    }

    /** AK-21: Die Übersicht ist nach dem einen Sprung erreicht, nicht nach einer Kette. */
    public function testAk21NachEinemSprungIstDasZielErreicht(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/community/ideen');
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    /** AK-22: Der Link aus einer alten Mail führt in einem Sprung auf dieselbe Idee. */
    #[DataProvider('sprachen')]
    public function testAk22MailLinkFuehrtAufDieselbeIdee(string $locale): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, 'Idee aus der Mail', 'idee-aus-der-mail');
        $client->request('GET', sprintf('/%s/community/ideen/%d-idee-aus-der-mail', $locale, $idee->getId()));

        self::assertResponseStatusCodeSame(301);
        self::assertSame(sprintf('/%s/roadmap/ideen/%d-idee-aus-der-mail', $locale, $idee->getId()), $this->ziel($client));

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Idee aus der Mail');
    }

    /** AK-24: Eine angehängte fremde Adresse wird nie zum Ziel. */
    #[DataProvider('fremdeZiele')]
    public function testAk24WeiterleitungBleibtIntern(string $aufruf, int $erwartet): void
    {
        $client = static::createClient();
        $client->request('GET', $aufruf);
        $ziel = $this->ziel($client);

        // ⚠ Der Status ist Teil der Aussage: Ein 404 statt der Weiterleitung hieße, die alte
        // Adresse ist kaputt — und erfüllte „nie nach außen" nur zufällig (Gegenprobe beim Bau).
        self::assertResponseStatusCodeSame($erwartet);
        self::assertStringNotContainsString('example.com', $ziel, 'Die Weiterleitung darf nie auf eine fremde Adresse zeigen.');
        if (301 === $erwartet) {
            self::assertStringStartsWith('/de/roadmap/ideen', $ziel);
            self::assertStringNotContainsString('?', $ziel, 'Kein Abfrageparameter wird weitergereicht.');
        }
    }

    /** AK-29: Eine wartende Idee erscheint weder in der Weiterleitung noch im Ziel. */
    public function testAk29WartendeIdeeBleibtUnsichtbar(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'Streng geheimer Titel', 'x', false, $verfasser);

        $client->request('GET', sprintf('%s/community/ideen/%d-x', self::LOCALE, $idee->getId()));
        self::assertResponseStatusCodeSame(301);
        self::assertStringNotContainsString('Streng geheimer Titel', (string) $client->getResponse()->getContent());

        $client->followRedirect();
        self::assertResponseStatusCodeSame(404);
        self::assertStringNotContainsString('Streng geheimer Titel', (string) $client->getResponse()->getContent());

        foreach (['/community/ideen', '/community/ideen?status=new', '/roadmap', '/roadmap/ideen', '/roadmap/ideen?status=new', '/changelog'] as $pfad) {
            $client->request('GET', self::LOCALE . $pfad);
            self::assertStringNotContainsString('Streng geheimer Titel', (string) $client->getResponse()->getContent(), $pfad);
        }
    }

    /**
     * AK-30: Hundert Aufrufe der alten Adressen hinterlassen keine Personenangabe im Protokoll.
     *
     * ⚠ BF-205: Gelesen wird der Speicher-Handler `pruefung` (alle Stufen ab `debug`), nicht
     * `test.log` — dorthin schreibt der Test-Kernel erst ab `error`, der alte Lauf blieb deshalb grün,
     * auch wenn der Controller eine E-Mail-Adresse protokollierte (Gegenprobe der QA).
     */
    public function testAk30KeinePersonenangabeImProtokoll(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'Idee für das Protokoll', 'protokoll', true, $verfasser);
        $angaben = array_filter(['user@endlech.lu', (string) $verfasser->getName(), 'Idee für das Protokoll']);

        // Der Aufbau oben (Konto laden, Idee anlegen) protokolliert selbst — er zählt nicht.
        $client->getContainer()->get('monolog.handler.pruefung')->clear();

        $zeilen = 0;
        for ($i = 0; $i < 25; ++$i) {
            foreach (['/community/ideen', '/community/ideen/neu', '/community/ideen/eingereicht', sprintf('/community/ideen/%d-protokoll', $idee->getId())] as $pfad) {
                $client->request('GET', self::LOCALE . $pfad);
                // Sonst prüfte der Lauf hundert 404er, die das Protokoll ohnehin auslässt.
                self::assertResponseStatusCodeSame(301, $pfad);

                /** @var TestHandler $speicher */
                $speicher = $client->getContainer()->get('monolog.handler.pruefung');
                foreach ($speicher->getRecords() as $eintrag) {
                    ++$zeilen;
                    $text = $eintrag->message . ' ' . json_encode($eintrag->context) . ' ' . json_encode($eintrag->extra);
                    foreach ($angaben as $angabe) {
                        self::assertStringNotContainsString($angabe, $text, sprintf('%s protokolliert „%s“: %s', $pfad, $angabe, $eintrag->message));
                    }
                }
            }
        }

        self::assertGreaterThan(0, $zeilen, 'Der Speicher-Handler hat nichts gesehen — der Lauf prüfte ins Leere.');
    }

    /** EC-02: Passt der Titelteil der alten Adresse nicht mehr, zählt die Nummer. */
    public function testEc02VeralteterTitelteilFuehrtTrotzdemZurIdee(): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, 'Heute so betitelt', 'heute-so-betitelt');

        $client->request('GET', sprintf('%s/community/ideen/%d-frueher-anders', self::LOCALE, $idee->getId()));
        self::assertResponseStatusCodeSame(301);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Heute so betitelt');
    }

    /** EC-03: Eine Dublette erreicht über die alte Adresse in höchstens zwei Sprüngen das Original. */
    public function testEc03DubletteHoechstensZweiSpruenge(): void
    {
        $client = static::createClient();
        $original = $this->idee($client, 'Das Original', 'das-original');
        $dublette = $this->idee($client, 'Die Dublette', 'die-dublette');
        $dublette->setDuplicateOf($original);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $client->request('GET', sprintf('%s/community/ideen/%d-die-dublette', self::LOCALE, $dublette->getId()));
        $spruenge = 0;
        while ($client->getResponse()->isRedirection()) {
            ++$spruenge;
            $client->followRedirect();
        }

        self::assertLessThanOrEqual(2, $spruenge);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Das Original');
    }

    /** EC-04: Ohne Sprache ist `/roadmap/ideen` keine Adresse. */
    public function testEc04OhneSpracheKeineAdresse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/roadmap/ideen');

        self::assertResponseStatusCodeSame(404);
    }

    /** EC-06: Ein Lesezeichen mit Filter landet auf dem Reiter Ideen. */
    public function testEc06LesezeichenMitFilterLandetAufDemReiter(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/community/ideen?status=planned&page=2');

        self::assertResponseStatusCodeSame(301);
        self::assertSame(self::LOCALE . '/roadmap/ideen', $this->ziel($client));
    }

    /** @return iterable<string, array{0: string}> */
    public static function sprachen(): iterable
    {
        foreach (self::SPRACHEN as $locale) {
            yield $locale => [$locale];
        }
    }

    /** @return iterable<string, array{0: string, 1: string, 2: string}> */
    public static function seitenJeSprache(): iterable
    {
        foreach (self::SPRACHEN as $locale) {
            yield $locale . ' Übersicht' => [$locale, '/community/ideen', '/roadmap/ideen'];
            yield $locale . ' Formular' => [$locale, '/community/ideen/neu', '/roadmap/ideen/neu'];
            yield $locale . ' Bestätigung' => [$locale, '/community/ideen/eingereicht', '/roadmap/ideen/eingereicht'];
        }
    }

    /** @return iterable<string, array{0: string, 1: int}> */
    public static function fremdeZiele(): iterable
    {
        yield 'next' => ['/de/community/ideen?next=https://example.com', 301];
        yield 'url' => ['/de/community/ideen?url=//example.com', 301];
        yield 'target' => ['/de/community/ideen/neu?_target_path=https://example.com', 301];
        // Keine alte Adresse — die Route passt nicht, es gibt nichts weiterzuleiten.
        yield 'Pfad' => ['/de/community/ideen//example.com', 404];
        yield 'Pfad nach Nummer' => ['/de/community/ideen/5-x/https://example.com', 404];
    }
}
