<?php

namespace App\Tests\Functional\Controller;

use App\Entity\FinanceEntry;
use App\Entity\Restaurant;
use App\Enum\FinanceCategory;
use App\Open\MetricSnapshotService;
use App\Open\OpenStatsService;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class OpenControllerTest extends AbstractWebTestCase
{
    public function testPageIsPubliclyReachable(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/open');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#platform-heading');
        self::assertSelectorExists('#impact-heading');
        self::assertSelectorExists('#finance-heading');
    }

    /**
     * /open ohne Sprachpräfix ist die URL, die in Fördermails und Vorträgen
     * steht – sie muss ankommen, nicht in einem 404 enden.
     */
    public function testLocaleFreeShortLinkRedirectsToThePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open');

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#platform-heading');
    }

    public function testFinanceSectionShowsExpensesButWithholdsRecentIncome(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/open');
        $text = $crawler->filter('#finance-heading')->closest('section')->text();

        self::assertStringContainsString('Ausgaben', $text);
        self::assertStringContainsString('Noch nicht veröffentlicht', $text, 'Die Fixtures enthalten nur Einnahmen aus dem laufenden Quartal.');
    }

    /**
     * Die Kernaussage der Seite ist Vollständigkeit: Auch Kantone ohne einen
     * einzigen Eintrag müssen sichtbar sein.
     */
    public function testAllTwelveCantonsAreListed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/open');

        self::assertCount(12, $crawler->filter('#canton-coverage tbody tr'));
    }

    public function testDatasetDownloadsAreLinked(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/open');

        self::assertGreaterThan(0, $crawler->filter('a[href="/open/dataset.csv"]')->count());
        self::assertGreaterThan(0, $crawler->filter('a[href="/open/dataset.json"]')->count());
        self::assertGreaterThan(0, $crawler->filter('a[href="/open.json"]')->count());
    }

    public function testNoUntranslatedKeysLeak(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/open');

        self::assertStringNotContainsString('open.platform.', (string) $client->getResponse()->getContent());
        self::assertStringNotContainsString('finance_category.', (string) $client->getResponse()->getContent());
    }

    /**
     * Die Veränderung wird gegen den zuletzt festgehaltenen Monat gerechnet,
     * nicht gegen "vor 30 Tagen" – nur ein Snapshot ist ein Stand, den jemand
     * nachprüfen kann.
     */
    public function testDeltaIsShownAgainstTheLatestSnapshot(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $container->get(MetricSnapshotService::class)->capture(new \DateTimeImmutable('-1 month'), force: true);

        $em->persist((new Restaurant())->setName('Nachzügler')->setCity('Mersch'));
        $em->flush();
        $container->get(OpenStatsService::class)->invalidate();

        $crawler = $client->request('GET', self::LOCALE.'/open');
        $text = $crawler->filter('#platform-heading')->closest('section')->text();

        self::assertMatchesRegularExpression('/\+1\s+seit \d{4}-\d{2}/u', $text);
    }

    public function testWithoutASnapshotNoDeltaIsInvented(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\MetricSnapshot s')->execute();

        $crawler = $client->request('GET', self::LOCALE.'/open');
        $text = $crawler->filter('#platform-heading')->closest('section')->text();

        self::assertStringNotContainsString('seit', $text, 'Ohne Bezugspunkt darf keine Veränderung behauptet werden.');
    }

    /**
     * Ein Dashboard, dem man das Alter nicht ansieht, richtet mehr Schaden an
     * als gar keines: Ab zwei Monaten tritt der Hinweis aus dem
     * Kleingedruckten heraus.
     */
    public function testStaleFinanceDataIsFlaggedVisibly(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/open');
        self::assertSame(0, $crawler->filter('#finance-heading')->closest('section')->filter('.bg-amber-50')->count());

        $this->ageFinanceEntries($client, '-120 days');

        $crawler = $client->request('GET', self::LOCALE.'/open');
        self::assertGreaterThan(
            0,
            $crawler->filter('#finance-heading')->closest('section')->filter('.bg-amber-50')->count(),
            'Veraltete Finanzdaten müssen sichtbar markiert sein.',
        );
    }

    /**
     * Auf Papier ist Navigation nichts wert – und ein sticky Header erschiene
     * sonst auf jeder gedruckten Seite erneut.
     */
    public function testChromeIsHiddenInPrint(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/open');

        self::assertStringContainsString('print:hidden', (string) $crawler->filter('header')->attr('class'));
        self::assertStringContainsString('print:hidden', (string) $crawler->filter('footer')->attr('class'));
    }

    private function ageFinanceEntries(KernelBrowser $client, string $modifier): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('UPDATE '.FinanceEntry::class.' f SET f.updatedAt = :date')
            ->setParameter('date', new \DateTimeImmutable($modifier))
            ->execute();
        $em->clear();
        $client->getContainer()->get(OpenStatsService::class)->invalidate();
    }
}
