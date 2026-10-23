<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feature 12, AK-49 — die Kachel „Einführung" in der Verwaltung: zwei Zahlen, keine Namen.
 */
final class AdminOnboardingTileTest extends AbstractWebTestCase
{
    public function testAk49ZweiZahlenOhneNamen(): void
    {
        $client = static::createClient();
        $user = $this->user($client, 'user@endlech.lu');
        $user->markOnboardingDue();
        $user->markOnboardingShown(new \DateTimeImmutable());
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseIsSuccessful();
        $titel = $client->getContainer()->get(TranslatorInterface::class)->trans('onboarding.admin.title', [], null, 'de');
        $kachel = $crawler->filter('p:contains("'.$titel.'")')->closest('div.rounded-xl');
        self::assertNotNull($kachel);
        self::assertMatchesRegularExpression('#\b1\s*/\s*1\b#', $kachel->text(), 'angezeigt / vorgesehen');
        self::assertStringNotContainsString('user@endlech.lu', $kachel->text());
        self::assertStringNotContainsString((string) $user->getName(), $kachel->text());
        self::assertCount(0, $kachel->filter('a'), 'Kein Verweis auf einzelne Konten.');
    }
}
