<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature 17, T23 — AK-68: Die Datenschutzerklärung nennt Favoriten, Listen und Notizen,
 * in allen fünf Sprachen.
 */
final class SavedPrivacyStatementTest extends AbstractWebTestCase
{
    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function sprachen(): iterable
    {
        yield 'lb' => ['lb', 'Favoritten, Lëschten an Notizen', ['Datenexport', 'Kont geläscht', 'Notzungsmiessung']];
        yield 'de' => ['de', 'Favoriten, Listen und Notizen', ['Datenexport', 'mit deinem Konto gelöscht', 'Nutzungsmessung', 'Sehen kannst nur du']];
        yield 'fr' => ['fr', 'Favoris, listes et notes', ['export de données', 'supprimées avec votre compte', 'mesure d\'audience']];
        yield 'en' => ['en', 'Favourites, lists and notes', ['data export', 'deleted together with your account', 'usage measurement']];
        yield 'pt' => ['pt', 'Favoritos, listas e notas', ['exportação de dados', 'apagadas juntamente com a sua conta', 'medição de utilização']];
    }

    /**
     * @param list<string> $kernaussagen
     */
    #[DataProvider('sprachen')]
    public function testAk68AbsatzStehtInJederSprache(string $sprache, string $titel, array $kernaussagen): void
    {
        $client = static::createClient();

        $abschnitt = $client->request('GET', '/'.$sprache.'/legal')->filter('#gespeichert');

        self::assertCount(1, $abschnitt);
        self::assertSame($titel, trim($abschnitt->text()));
        $absatz = $abschnitt->nextAll()->first()->text();
        foreach ($kernaussagen as $aussage) {
            self::assertStringContainsString($aussage, $absatz);
        }
    }
}
