<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 16 · T04/T06: der gemeinsame Bereichskopf „Roadmap & Ideen" mit drei Reitern.
 *
 * Der Kopf wird über `data-area-header` gefunden, die Reiterleiste über
 * `data-area-tabs` — beides Prüfanker, die das Template eigens setzt, damit der Lauf
 * nicht an Klassenketten hängt.
 */
final class RoadmapAreaTest extends AbstractWebTestCase
{
    private const array REITER = ['Roadmap', 'Ideen', 'Changelog'];

    private function idee(KernelBrowser $client, string $titel, string $slug, BoardIdeaStatus $status = BoardIdeaStatus::PLANNED): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung zu ' . $titel)
            ->setSlug($slug)
            ->setLocale('de')
            ->setStatus($status)
            ->setPublishedAt(new \DateTimeImmutable('-1 day'));
        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    /** @return list<string> */
    private function reitertexte(Crawler $crawler): array
    {
        return $crawler->filter('nav[data-area-tabs] a')->each(static fn (Crawler $a): string => trim($a->text()));
    }

    /** AK-05: Kopf mit Name und drei Reitern in fester Reihenfolge — auf jeder Seitenart gleich. */
    #[DataProvider('seitenarten')]
    public function testAk05KopfMitNameUndDreiReitern(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . $this->aufgeloest($client, $pfad));
        self::assertResponseIsSuccessful();

        $kopf = $crawler->filter('[data-area-header]');
        self::assertCount(1, $kopf, 'Genau ein Bereichskopf.');
        self::assertStringContainsString('Roadmap & Ideen', $kopf->text());
        self::assertSame(self::REITER, $this->reitertexte($crawler));
        self::assertSame('Roadmap & Ideen', $crawler->filter('nav[data-area-tabs]')->attr('aria-label'));
    }

    /** AK-06: Der aktive Reiter ist als aktuelle Seite ausgezeichnet — genau einer. */
    #[DataProvider('aktiveReiter')]
    public function testAk06AktiverReiter(string $pfad, string $aktiv): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . $this->aufgeloest($client, $pfad));

        $aktuell = $crawler->filter('nav[data-area-tabs] a[aria-current="page"]');
        self::assertCount(1, $aktuell);
        self::assertSame($aktiv, trim($aktuell->text()));
    }

    /** AK-07: Jeder Reiter ist ein gewöhnlicher Link auf eine eigene Adresse. */
    public function testAk07ReiterSindLinksAufEigeneAdressen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');

        self::assertSame(
            [self::LOCALE . '/roadmap', self::LOCALE . '/roadmap/ideen', self::LOCALE . '/changelog'],
            $crawler->filter('nav[data-area-tabs] a')->each(static fn (Crawler $a): string => (string) $a->attr('href')),
        );
    }

    /** AK-10: Der Knopf „Idee einreichen" steht im Kopf jedes Reiters. */
    #[DataProvider('reiterpfade')]
    public function testAk10KnopfImKopfJedesReiters(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . $pfad);

        $knopf = $crawler->filter('[data-area-header] a[href="' . self::LOCALE . '/roadmap/ideen/neu"]');
        self::assertCount(1, $knopf);
        self::assertSame('Idee einreichen', trim($knopf->text()));
    }

    /** AK-12: Ein Gast kommt über den Knopf und die Anmeldung auf das Einreichformular. */
    public function testAk12GastLandetNachDerAnmeldungAufDemFormular(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');
        $client->click($crawler->filter('[data-area-header]')->selectLink('Idee einreichen')->link());

        self::assertResponseRedirects();
        $crawler = $client->followRedirect();
        self::assertStringContainsString('/login', $client->getRequest()->getPathInfo());

        $client->submit($this->formWithField($crawler, '_username', [
            '_username' => 'user@endlech.lu',
            '_password' => 'user123',
        ]));

        self::assertResponseRedirects();
        self::assertStringEndsWith(self::LOCALE . '/roadmap/ideen/neu', (string) $client->getResponse()->headers->get('Location'));
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="board_idea[title]"]');
    }

    /** AK-12: Ein unbestätigtes Konto bekommt wie bisher den Bestätigungshinweis. */
    public function testAk12UnbestaetigtesKontoBekommtDenHinweis(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap/ideen/neu');
        self::assertResponseRedirects();
        $crawler = $client->followRedirect();

        $client->submit($this->formWithField($crawler, '_username', [
            '_username' => 'unverified@endlech.lu',
            '_password' => 'unverified123',
        ]));
        while ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
        }

        // ⚠ BF-205: Positiv prüfen, wo er landet — der Bestätigungshinweis mit der Meldung des Boards.
        // Nur „nicht auf dem Formular" war auch mit falschem Passwort grün (Anmeldeseite).
        self::assertSame(self::LOCALE . '/verify', $client->getRequest()->getPathInfo());
        self::assertSelectorTextContains('body', 'Bitte bestätige zuerst deine E-Mail-Adresse.');
        self::assertSelectorNotExists('input[name="board_idea[title]"]');
    }

    /** AK-13: Jeder Reiter trägt einen eigenen Titel und genau eine h1. */
    public function testAk13EigeneTitelUndGenauEineH1(): void
    {
        $client = static::createClient();
        $titel = [];
        foreach (['/roadmap', '/roadmap/ideen', '/changelog'] as $pfad) {
            $crawler = $client->request('GET', self::LOCALE . $pfad);
            self::assertCount(1, $crawler->filter('h1'), $pfad);
            $titel[] = trim($crawler->filter('title')->text());
        }

        self::assertCount(3, array_unique($titel), 'Jeder Reiter braucht einen eigenen Titel.');
    }

    /**
     * AK-13 im **gefüllten** Zustand: Die Kette ist auf allen drei Reitern lückenlos, auch
     * wenn Ideen-Karten (h3) und eine umgesetzte Idee dastehen. `HeadingOrderTest` prüft den
     * Reiter Ideen nur gegen eine Test-Datenbank ohne veröffentlichte Ideen — dort gibt es
     * keine Karte und damit keinen Sprung (bei T08 im Browser gefunden: h1 → h3).
     */
    public function testAk13KetteLueckenlosMitIdeen(): void
    {
        $client = static::createClient();
        $this->idee($client, 'Offene Idee für die Kette', 'kette-offen', BoardIdeaStatus::NEW);
        $this->idee($client, 'Geplante Idee für die Kette', 'kette-geplant');
        $this->idee($client, 'Umgesetzte Idee für die Kette', 'kette-umgesetzt', BoardIdeaStatus::DONE);

        foreach (['/roadmap', '/roadmap/ideen', '/changelog'] as $pfad) {
            $crawler = $client->request('GET', self::LOCALE . $pfad);
            $ebenen = $crawler->filter('h1, h2, h3, h4, h5, h6')->each(static fn (Crawler $h): int => (int) substr($h->nodeName(), 1));
            $vorige = 0;
            foreach ($ebenen as $ebene) {
                self::assertLessThanOrEqual($vorige + 1, $vorige > 0 ? $ebene : 1, sprintf('%s springt: %s', $pfad, implode(',', $ebenen)));
                $vorige = $ebene;
            }
        }
    }

    /** AK-08 (Teil, ohne Browser prüfbar): Jeder Reiter trägt Mindesthöhe UND Mindestbreite 44 px. */
    public function testAk08ReiterTragenMindestmasse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');

        foreach ($crawler->filter('nav[data-area-tabs] a') as $a) {
            $klassen = (string) $a->getAttribute('class');
            self::assertStringContainsString('min-h-[44px]', $klassen);
            self::assertStringContainsString('min-w-[44px]', $klassen, 'Bei 320 px war „Ideen" nur 41 px breit (T08).');
        }
    }

    /**
     * BF-200 (AK-05 „an derselben Stelle"): Über der Reiterleiste steht auf jeder Seitenart dasselbe —
     * Etikett und Knopf. Die Überschrift der Seite folgt erst darunter. Vorher stand sie im Kopfband
     * über den Reitern, und die Leiste sprang je Seite um bis zu 128 px (QA 2026-11-07).
     */
    #[DataProvider('seitenarten')]
    public function testBf200UeberDerReiterleisteStehtUeberallDasselbe(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . $this->aufgeloest($client, $pfad));

        $kopf = $crawler->filter('[data-area-header]');
        $vorDerLeiste = $kopf->filter('[data-area-band]');
        self::assertCount(1, $vorDerLeiste, 'Das Kopfband ist eigens ausgezeichnet.');
        self::assertCount(0, $vorDerLeiste->filter('h1, h2, p:not([data-area-name])'), 'Im Band steht nur Etikett und Knopf.');
        self::assertSame(['Roadmap & Ideen', 'Idee einreichen'], $vorDerLeiste->filter('[data-area-name], a')->each(static fn (Crawler $e): string => trim($e->text())));

        $html = (string) $client->getResponse()->getContent();
        $h1 = strpos($html, '<h1');
        if (false !== $h1) {
            self::assertLessThan($h1, strpos($html, 'data-area-tabs'), 'Die Überschrift steht unter der Reiterleiste.');
        }
    }

    /** BF-200: Alle Reiter gleich fett — der aktive unterscheidet sich nicht durch Breite. */
    public function testBf200AlleReiterGleichFett(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');

        foreach ($crawler->filter('nav[data-area-tabs] a') as $a) {
            $klassen = ' ' . $a->getAttribute('class') . ' ';
            self::assertStringContainsString(' font-semibold ', $klassen);
            self::assertStringNotContainsString(' font-bold ', $klassen);
        }
    }

    /** BF-203: Das Etikett im Kopf liegt auf dunklem Glas, nicht auf `bg-white/20` (3,7 : 1). */
    public function testBf203EtikettAufDunklemGlas(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');
        $klassen = (string) $crawler->filter('[data-area-name]')->attr('class');

        self::assertStringContainsString('bg-black/20', $klassen);
        self::assertStringNotContainsString('bg-white/20', $klassen);
    }

    /** AK-14: Die Roadmap zeigt ihren Inhalt wie bisher — ohne „Weiterlesen". */
    public function testAk14RoadmapOhneWeiterlesen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');

        foreach (['in_progress', 'planned', 'considered'] as $spalte) {
            self::assertCount(1, $crawler->filter(sprintf('section[aria-labelledby="stage-%s"]', $spalte)), $spalte);
        }
        self::assertCount(1, $crawler->filter('#shelved-heading'));
        self::assertCount(0, $crawler->filter('#more-heading'));
        self::assertStringNotContainsString('Weiterlesen', $crawler->filter('main')->text());
    }

    /** AK-15: Der Verweis einer Community-Idee führt direkt unter die neue Adresse. */
    public function testAk15CommunityVerweisOhneUmweg(): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, 'Direkt verlinkte Idee', 'direkt-verlinkt');
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');

        $verweis = $crawler->filter(sprintf('section[aria-labelledby="stage-planned"] a[href="%s/roadmap/ideen/%d-direkt-verlinkt"]', self::LOCALE, $idee->getId()));
        self::assertCount(1, $verweis);
        self::assertSame('Zur Idee', trim(str_replace('→', '', $verweis->text())));
    }

    /** AK-16: Der Hinweis auf weitere geplante Ideen führt auf den gefilterten Reiter. */
    public function testAk16WeitereGeplanteIdeenFuehrenAufDenGefiltertenReiter(): void
    {
        $client = static::createClient();
        for ($i = 1; $i <= 12; ++$i) {
            $this->idee($client, sprintf('Geplante Idee %02d', $i), 'geplant-' . $i);
        }
        $crawler = $client->request('GET', self::LOCALE . '/roadmap');

        $verweis = $crawler->filter('section[aria-labelledby="stage-planned"] a[href="' . self::LOCALE . '/roadmap/ideen?status=planned"]');
        self::assertCount(1, $verweis);
        self::assertStringContainsString('2 weitere geplante Ideen', $verweis->text());
    }

    /** AK-17: Der Reiter Ideen zeigt weiter die Zahl der offenen Ideen — jetzt im Inhalt, nicht im Kopf. */
    public function testAk17ReiterIdeenZeigtDieLeitzahl(): void
    {
        $client = static::createClient();
        $this->idee($client, 'Gezählte Idee', 'gezaehlt', BoardIdeaStatus::NEW);
        $crawler = $client->request('GET', self::LOCALE . '/roadmap/ideen');

        $leitzahl = $crawler->filter('[data-board-lead]');
        self::assertCount(1, $leitzahl);
        self::assertMatchesRegularExpression('/^\d+\s+offene Ideen$/u', trim(preg_replace('/\s+/u', ' ', $leitzahl->text()) ?? ''));
        self::assertCount(0, $crawler->filter('[data-area-header] [data-board-lead]'), 'Die Leitzahl steht nicht im Kopf (Entscheidung 10).');
        self::assertSame('Ideen für Endlech.lu', trim($crawler->filter('h1')->text()));
    }

    /** AK-19: Formular und Bestätigung führen zurück auf den Reiter Ideen. */
    public function testAk19FormularUndBestaetigungFuehrenZurueck(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        foreach (['/roadmap/ideen/neu', '/roadmap/ideen/eingereicht'] as $pfad) {
            $crawler = $client->request('GET', self::LOCALE . $pfad);
            self::assertResponseIsSuccessful($pfad);
            $zurueck = $crawler->filter('main a[href="' . self::LOCALE . '/roadmap/ideen"]');
            self::assertGreaterThanOrEqual(1, $zurueck->count(), $pfad);
            self::assertStringContainsString('Zurück zu den Ideen', $zurueck->text());
            self::assertCount(0, $crawler->filter('[data-area-header]'), 'Kein Bereichskopf auf ' . $pfad . ' (Decision Log 14).');
        }
    }

    /** EC-01: Ohne veröffentlichte Idee zeigt der Reiter den Leerzustand mit Weg zum Formular. */
    public function testEc01LeererReiterMitWegZumFormular(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('UPDATE App\Entity\BoardIdea i SET i.publishedAt = NULL')->execute();

        $crawler = $client->request('GET', self::LOCALE . '/roadmap/ideen');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Noch keine Idee');
        self::assertGreaterThanOrEqual(2, $crawler->filter('main a[href="' . self::LOCALE . '/roadmap/ideen/neu"]')->count(), 'Kopf-Knopf und Weg im Leerzustand.');
    }

    /** EC-07: Der Reiter Ideen trägt keinen Aktualitätshinweis. */
    public function testEc07KeinAktualitaetshinweisAufDemReiterIdeen(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/roadmap');
        self::assertStringContainsString('Stand: ', (string) $client->getResponse()->getContent(), 'Gegenprobe: Die Roadmap trägt ihn.');

        $client->request('GET', self::LOCALE . '/roadmap/ideen');
        self::assertStringNotContainsString('Stand: ', (string) $client->getResponse()->getContent());
    }

    /**
     * Die Einzelseite braucht eine echte Nummer; `{idee}` wird hier ersetzt.
     */
    private function aufgeloest(KernelBrowser $client, string $pfad): string
    {
        if (!str_contains($pfad, '{idee}')) {
            return $pfad;
        }
        $idee = $this->idee($client, 'Idee für den Kopf', 'kopf', BoardIdeaStatus::NEW);

        return str_replace('{idee}', sprintf('%d-kopf', $idee->getId()), $pfad);
    }

    /** @return iterable<string, array{0: string}> */
    public static function seitenarten(): iterable
    {
        yield 'Roadmap' => ['/roadmap'];
        yield 'Ideen' => ['/roadmap/ideen'];
        yield 'Changelog' => ['/changelog'];
        yield 'Einzelseite' => ['/roadmap/ideen/{idee}'];
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function aktiveReiter(): iterable
    {
        yield 'Roadmap' => ['/roadmap', 'Roadmap'];
        yield 'Ideen' => ['/roadmap/ideen', 'Ideen'];
        yield 'Changelog' => ['/changelog', 'Changelog'];
        yield 'Einzelseite' => ['/roadmap/ideen/{idee}', 'Ideen'];
    }

    /** @return iterable<string, array{0: string}> */
    public static function reiterpfade(): iterable
    {
        yield 'Roadmap' => ['/roadmap'];
        yield 'Ideen' => ['/roadmap/ideen'];
        yield 'Changelog' => ['/changelog'];
    }
}
