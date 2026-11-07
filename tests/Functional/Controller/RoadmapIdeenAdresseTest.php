<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Board\BoardModerator;
use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Feature 16 · T02: Das Board liegt unter `/roadmap/ideen` — und alles, was eine
 * Adresse aus dem Routennamen erzeugt, zieht mit.
 *
 * ⚠ Die Prüfläufe aus Feature 06 laufen unverändert gegen den neuen Pfad (dort
 * wurden nur die Adressen ersetzt). Hier steht, was der Umzug **zusätzlich**
 * zusagt: Mail-Link, Sitemap, maßgebliche Adresse, Rückleitung, Deckel.
 */
final class RoadmapIdeenAdresseTest extends AbstractWebTestCase
{
    use MailerAssertionsTrait;

    private function idee(KernelBrowser $client, string $titel, string $slug, ?User $verfasser = null, bool $veroeffentlicht = true): BoardIdea
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

    /** AK-04: Alle drei Reiter antworten ohne Anmeldung. */
    #[DataProvider('sprachen')]
    public function testAk04DreiReiterOhneAnmeldung(string $locale): void
    {
        $client = static::createClient();

        foreach (['/roadmap', '/roadmap/ideen', '/changelog'] as $pfad) {
            $client->request('GET', '/' . $locale . $pfad);
            self::assertResponseStatusCodeSame(200, sprintf('[%s] %s', $locale, $pfad));
        }
    }

    /** AK-18: Die Einzelseite einer veröffentlichten Idee liegt unter der neuen Adresse. */
    public function testAk18EinzelseiteUnterNeuerAdresse(): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, 'Kartenansicht für Rollstuhlwege', 'kartenansicht-rollstuhl');

        $client->request('GET', sprintf('%s/roadmap/ideen/%d-%s', self::LOCALE, $idee->getId(), $idee->getSlug()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Kartenansicht für Rollstuhlwege');
    }

    /** AK-20: Nach dem Zustimmen landet der Nutzer unter der neuen Adresse. */
    public function testAk20ZustimmenFuehrtUnterDieNeueAdresse(): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, 'Ruhige Ecken markieren', 'ruhige-ecken');
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', sprintf('%s/roadmap/ideen/%d-%s', self::LOCALE, $idee->getId(), $idee->getSlug()));
        self::assertResponseIsSuccessful();
        $client->submit($this->formByAction($crawler, '/zustimmen'));

        self::assertResponseRedirects();
        self::assertStringStartsWith(self::LOCALE . '/roadmap/ideen/', (string) $client->getResponse()->headers->get('Location'));
    }

    /** AK-20: Nach dem Zurückziehen landet der Verfasser unter der neuen Adresse. */
    public function testAk20ZurueckziehenFuehrtUnterDieNeueAdresse(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'Noch wartende Idee', 'noch-wartend', $verfasser, false);
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', sprintf('%s/roadmap/ideen/%d-%s', self::LOCALE, $idee->getId(), $idee->getSlug()));
        self::assertResponseIsSuccessful();
        $client->submit($this->formByAction($crawler, '/zurueckziehen'));

        self::assertResponseRedirects();
        self::assertStringStartsWith(self::LOCALE . '/roadmap/ideen', (string) $client->getResponse()->headers->get('Location'));
    }

    /** AK-23: Die Mail nach der Freigabe verweist direkt auf die neue Adresse. */
    public function testAk23MailLinkZeigtAufDieNeueAdresse(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'Idee mit Mail', 'idee-mit-mail', $verfasser, false);

        $client->getContainer()->get(BoardModerator::class)->publish($idee);

        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertNotNull($mail);
        $inhalt = $mail->getHtmlBody() . ' ' . (string) $mail->getTextBody();

        self::assertStringContainsString(sprintf('/de/roadmap/ideen/%d-idee-mit-mail', $idee->getId()), $inhalt);
        self::assertStringNotContainsString('/community/ideen', $inhalt);
    }

    /** AK-25: Die Sitemap nennt die neue Adresse in allen Sprachen und die alte nicht mehr. */
    public function testAk25SitemapNenntNurDieNeueAdresse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        $xml = (string) $client->getResponse()->getContent();

        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $locale) {
            self::assertStringContainsString('<loc>https://endlech.lu/' . $locale . '/roadmap/ideen</loc>', $xml, "[$locale] neue Adresse fehlt");
        }
        self::assertStringNotContainsString('/community/ideen', $xml);
        self::assertStringContainsString('<loc>https://endlech.lu/de/roadmap</loc>', $xml);
        self::assertStringContainsString('<loc>https://endlech.lu/de/changelog</loc>', $xml);
    }

    /** AK-26: canonical und Sprachverweise zeigen auf die neue Adresse, `page` bleibt. */
    public function testAk26MassgeblicheAdresseUndSprachverweise(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/roadmap/ideen?page=2&sort=newest');
        self::assertResponseIsSuccessful();

        self::assertSame('https://endlech.lu/de/roadmap/ideen?page=2', $crawler->filter('link[rel="canonical"]')->attr('href'));
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $locale) {
            self::assertSame(
                'https://endlech.lu/' . $locale . '/roadmap/ideen?page=2',
                $crawler->filter(sprintf('link[rel="alternate"][hreflang="%s"]', $locale))->attr('href'),
                "[$locale] Sprachverweis",
            );
        }
    }

    /** AK-33: Eine wartende Idee sieht nur ihr Verfasser — auch unter der neuen Adresse. */
    public function testAk33WartendeIdeeNurFuerDenVerfasser(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'Geheim wartende Idee', 'geheim-wartend', $verfasser, false);
        $pfad = sprintf('%s/roadmap/ideen/%d-%s', self::LOCALE, $idee->getId(), $idee->getSlug());

        $client->request('GET', $pfad);
        self::assertResponseStatusCodeSame(404, 'Gast');
        self::assertStringNotContainsString('Geheim wartende Idee', (string) $client->getResponse()->getContent());

        $this->loginAs($client, 'admin@endlech.lu');
        $client->request('GET', $pfad);
        self::assertResponseStatusCodeSame(404, 'fremdes Konto');
        self::assertStringNotContainsString('Geheim wartende Idee', (string) $client->getResponse()->getContent());

        $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', $pfad);
        self::assertResponseIsSuccessful('Der Verfasser sieht seine wartende Idee.');
    }

    /**
     * AK-34: Der Deckel zählt am Konto — unter der neuen Adresse greift dasselbe
     * Kontingent. Im Test steht er auf 10000; erschöpft wird er direkt am Limiter
     * für genau dieses Konto, nicht über 10000 Anfragen.
     */
    public function testAk34DeckelAmKontoGiltUnterDerNeuenAdresse(): void
    {
        $client = static::createClient();
        /** @var RateLimiterFactoryInterface $fabrik */
        $fabrik = static::getContainer()->get('limiter.board_submit');
        $limiter = $fabrik->create('user@endlech.lu');
        // ⚠ Den REST verbrauchen, nicht pauschal 10000: Haben frühere Prüfläufe für dieses
        // Konto schon eingereicht, wiese `consume(10000)` ab und verbrauchte nichts.
        $limiter->consume($limiter->consume(0)->getRemainingTokens());

        try {
            $this->loginAs($client, 'user@endlech.lu');
            $crawler = $client->request('GET', self::LOCALE . '/roadmap/ideen/neu');
            self::assertResponseIsSuccessful();

            $client->submit($this->formWithField($crawler, 'board_idea[title]', [
                'board_idea[title]' => 'Idee trotz Deckel',
                'board_idea[description]' => 'Diese Idee darf nicht gespeichert werden, das Kontingent ist erschöpft.',
            ]));

            self::assertResponseStatusCodeSame(429);
            self::assertSelectorTextContains('body', 'Minute');
            $anzahl = static::getContainer()->get(EntityManagerInterface::class)
                ->getRepository(BoardIdea::class)->count(['title' => 'Idee trotz Deckel']);
            self::assertSame(0, $anzahl, 'Bei erschöpftem Deckel entsteht keine Idee.');
        } finally {
            $limiter->reset();
        }
    }

    /** @return iterable<string, array{0: string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $locale) {
            yield $locale => [$locale];
        }
    }
}
