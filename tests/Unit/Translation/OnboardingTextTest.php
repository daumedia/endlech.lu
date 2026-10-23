<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Feature 12, AK-21 — kein Text der Einführung verspricht, was es nicht gibt.
 *
 * Bewertungen, Kommentare und Favoriten gibt es auf Endlech.lu nicht (PRD, Risiko 1: die
 * Startseite wirbt bis heute mit „Bewerten"). Geprüft wird je Sprache über Wortstämme, nach dem
 * Muster von `OrganisationControllerTest::testKeineSeiteBietetNochEinenBeiratAn`.
 *
 * ⚠ Französisch: „comment" heißt dort auch „wie" („comment lire une fiche") — geprüft wird
 * deshalb „commentaire", nicht der kürzere Stamm.
 */
final class OnboardingTextTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function sprachen(): iterable
    {
        yield 'lb' => ['lb', '/(bewäert|bewert|kommentar|favorit)/iu'];
        yield 'de' => ['de', '/(bewert|kommentar|favorit)/iu'];
        yield 'fr' => ['fr', '/(\bavis\b|évalu|\bnot(er|ez|ation)\b|commentaire|favori)/iu'];
        yield 'en' => ['en', '/(review|\brat(e|ing|ings)\b|comment|favou?rite)/iu'];
        yield 'pt' => ['pt', '/(avalia|coment|favorit)/iu'];
    }

    /**
     * @param array<string, mixed> $baum
     *
     * @return array<string, string>
     */
    private function flach(array $baum, string $praefix = ''): array
    {
        $flach = [];
        foreach ($baum as $schluessel => $wert) {
            $pfad = '' === $praefix ? (string) $schluessel : $praefix.'.'.$schluessel;
            if (\is_array($wert)) {
                $flach += $this->flach($wert, $pfad);
            } else {
                $flach[$pfad] = (string) $wert;
            }
        }

        return $flach;
    }

    #[DataProvider('sprachen')]
    public function testAk21KeinVersprechenVonBewertungenKommentarenOderFavoriten(string $sprache, string $muster): void
    {
        $katalog = Yaml::parseFile(\dirname(__DIR__, 3).'/translations/messages.'.$sprache.'.yaml');
        $texte = $this->flach(['onboarding' => $katalog['onboarding'], 'newsletter' => $katalog['newsletter']]);

        self::assertNotEmpty($texte);
        foreach ($texte as $schluessel => $text) {
            self::assertDoesNotMatchRegularExpression($muster, $text, $sprache.': '.$schluessel);
        }
    }

    #[DataProvider('sprachen')]
    public function testDieGegenprobeFindetDasVerboteneWort(string $sprache, string $muster): void
    {
        // Ohne diese Probe könnte ein Muster nie anschlagen und der Test bliebe grün.
        $beispiele = [
            'lb' => 'Bewäert d’Restaurant',
            'de' => 'Bewerte das Restaurant',
            'fr' => 'Laissez un commentaire',
            'en' => 'Rate this restaurant',
            'pt' => 'Deixe uma avaliação',
        ];

        self::assertMatchesRegularExpression($muster, $beispiele[$sprache]);
    }
}
