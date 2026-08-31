<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * AK-40, AK-41: Alle vier Sprachfassungen, kein roher Übersetzungsschlüssel.
 *
 * ⚠ **Dieser Prüflauf existiert wegen eines konkreten Fehlers.** Beim Bau von
 * Feature 06 lieferte `BoardIdeaStatus::transKey()` einen verschachtelten
 * Schlüssel (`board.status.declined`), die Kataloge trugen ihn flach — auf der
 * Seite stand daraufhin der rohe Schlüsselname. `CatalogueCompletenessTest`
 * fand das nicht: Der Schlüssel entsteht in PHP, nicht als Literal im Template.
 * Nur ein Abruf der gerenderten Seite deckt so etwas auf.
 */
final class BoardLocaleTest extends AbstractWebTestCase
{
    /**
     * @return iterable<string, array{0: string}>
     */
    public static function sprachen(): iterable
    {
        yield 'de' => ['/de'];
        yield 'en' => ['/en'];
        yield 'fr' => ['/fr'];
        yield 'lb' => ['/lb'];
    }

    #[DataProvider('sprachen')]
    public function testKeinRoherSchluesselAufDemBoard(string $locale): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        // Je eine Idee pro Status, damit jedes Abzeichen gerendert wird.
        foreach (BoardIdeaStatus::cases() as $i => $status) {
            $idee = (new BoardIdea())
                ->setTitle('Idee ' . $status->value)
                ->setDescription('Beschreibung.')
                ->setSlug('idee-' . $i)
                ->setLocale('lb')
                ->setStatus($status)
                ->setPublishedAt(new \DateTimeImmutable());
            $idee->setTeamResponse('Antwort des Teams.');
            $em->persist($idee);
        }
        $em->flush();

        $client->request('GET', $locale . '/community/ideen');
        self::assertResponseIsSuccessful();

        $html = (string) $client->getResponse()->getContent();

        // Ein roher Schlüssel sieht aus wie `board.irgendwas` im sichtbaren Text.
        self::assertDoesNotMatchRegularExpression(
            '/>[^<]*\bboard\.[a-z_]+/',
            $html,
            'Auf der Seite steht ein roher Übersetzungsschlüssel statt eines Textes.',
        );
        self::assertDoesNotMatchRegularExpression('/>[^<]*\bflash\.[a-z_]+/', $html);
    }

    #[DataProvider('sprachen')]
    public function testEinreichformularOhneRohenSchluessel(string $locale): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', $locale . '/community/ideen/neu');
        self::assertResponseIsSuccessful();

        $html = (string) $client->getResponse()->getContent();
        self::assertDoesNotMatchRegularExpression('/>[^<]*\bboard\.[a-z_]+/', $html);
    }

    /** AK-41: Der Beitragstext bleibt unverändert und trägt seine Sprache. */
    public function testFremdsprachigerBeitragBleibtUnveraendert(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $text = 'Eng Kaart wier flott fir Lokaler an der Noperschaft ze fannen.';
        $idee = (new BoardIdea())
            ->setTitle('Kaart')
            ->setDescription($text)
            ->setSlug('kaart')
            ->setLocale('lb')
            ->setPublishedAt(new \DateTimeImmutable());
        $em->persist($idee);
        $em->flush();

        $client->request('GET', '/fr/community/ideen');

        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString($text, $html, 'Der Text erscheint unverändert.');
        self::assertStringContainsString('lang="lb"', $html, 'Die Sprache des Beitrags ist ausgezeichnet.');
    }
}
