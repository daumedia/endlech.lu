<?php

namespace App\Tests\Unit\Usage;

use App\Enum\Language;
use App\Enum\OrderingPlatform;
use App\Usage\UsageEventCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Feature 11 · Der Ereigniskatalog lässt nur durch, was keine Person beschreiben kann. */
final class UsageEventCatalogueTest extends TestCase
{
    private const string FILTERFORMULAR = __DIR__.'/../../../templates/restaurant/index.html.twig';

    /**
     * Jeder Filterschlüssel ist ein Feldname des Filterformulars — gegen die Vorlage geprüft,
     * nicht abgeschrieben. Und umgekehrt: Jedes Ja/Nein-Feld der Vorlage steht im Katalog, sonst
     * bliebe ein neuer Filter lautlos ungezählt.
     */
    public function testFilterschluesselDeckenSichMitDemFilterformular(): void
    {
        $vorlage = (string) file_get_contents(self::FILTERFORMULAR);
        preg_match_all('#name="([a-z_]+)"\s+value="1"#', $vorlage, $treffer);
        $jaNeinFelder = array_values(array_unique($treffer[1]));

        sort($jaNeinFelder);
        $katalog = UsageEventCatalogue::FILTER_KEYS;
        sort($katalog);

        self::assertNotSame([], $jaNeinFelder, 'Vorbedingung: Die Vorlage hat Ja/Nein-Filter.');
        self::assertSame($jaNeinFelder, $katalog);

        // Die beiden Felder mit Wert werden nur als Merker gezählt.
        self::assertStringContainsString('name="city"', $vorlage);
        self::assertStringContainsString('name="cuisine[]"', $vorlage);
        self::assertStringContainsString('name="lang_{{ lang.value }}"', $vorlage);
    }

    public function testJedeSpracheUndJedePlattformIstErlaubt(): void
    {
        $katalog = new UsageEventCatalogue();

        foreach (Language::cases() as $sprache) {
            self::assertTrue($katalog->isAllowed(UsageEventCatalogue::FILTER_APPLIED, ['filter' => 'lang_'.$sprache->value]));
        }
        foreach (OrderingPlatform::cases() as $plattform) {
            self::assertTrue($katalog->isAllowed(UsageEventCatalogue::CONTACT_USED, ['art' => 'bestellweg', 'plattform' => $plattform->value]));
        }
    }

    /** AK-12, AK-17, AK-21 · Kein Ereignis kennt ein Feld, das eine Person beschreiben kann. */
    public function testKeinFeldNameBeschreibtEinePerson(): void
    {
        $katalog = new UsageEventCatalogue();
        $verboten = ['email', 'name', 'phone', 'telefonnummer', 'user', 'id', 'city', 'href', 'url', 'message'];

        foreach ($katalog->eventNames() as $ereignis) {
            self::assertSame([], array_intersect($verboten, $katalog->allowedFields($ereignis)), $ereignis);
        }
    }

    /** @return iterable<string, array{string, array<mixed>, bool}> */
    public static function faelle(): iterable
    {
        $f = UsageEventCatalogue::FILTER_APPLIED;
        $k = UsageEventCatalogue::CONTACT_USED;

        yield 'Filter: zwei Schlüssel' => [$f, ['filter' => 'wheelchair,toilet'], true];
        yield 'Filter: Ort nur als Merker' => [$f, ['filter' => 'wheelchair,ort'], true];
        yield 'Filter: Ortstext (AK-13)' => [$f, ['filter' => 'Esch'], false];
        yield 'Filter: Ortstext angehängt' => [$f, ['filter' => 'ort,Esch-sur-Alzette'], false];
        yield 'Filter: Ort als eigenes Feld' => [$f, ['filter' => 'wheelchair', 'ort' => 'Esch'], false];
        yield 'Filter: doppelt' => [$f, ['filter' => 'toilet,toilet'], false];
        yield 'Filter: leer' => [$f, ['filter' => ''], false];
        yield 'Filter: ohne Daten' => [$f, [], false];
        yield 'Kontaktweg: Telefon' => [$k, ['art' => 'telefon'], true];
        yield 'Kontaktweg: Nummer statt Art (AK-12)' => [$k, ['art' => '+352 123456'], false];
        yield 'Kontaktweg: Zieladresse (AK-12)' => [$k, ['art' => 'website', 'href' => 'https://example.lu'], false];
        yield 'Kontaktweg: Bestellweg mit Plattform' => [$k, ['art' => 'bestellweg', 'plattform' => 'wolt'], true];
        yield 'Kontaktweg: Plattform ohne Bestellweg' => [$k, ['art' => 'website', 'plattform' => 'wolt'], false];
        yield 'Kontaktweg: unbekannte Plattform' => [$k, ['art' => 'bestellweg', 'plattform' => 'foodora'], false];
        yield 'Warteliste: app' => [UsageEventCatalogue::WAITLIST_JOINED, ['liste' => 'app'], true];
        yield 'Warteliste: mit E-Mail (AK-17)' => [UsageEventCatalogue::WAITLIST_JOINED, ['liste' => 'app', 'email' => 'a@b.lu'], false];
        yield 'Warteliste: unbekannte Liste' => [UsageEventCatalogue::WAITLIST_JOINED, ['liste' => 'newsletter'], false];
        yield 'Zustimmung: ohne Daten' => [UsageEventCatalogue::VOTE_GIVEN, [], true];
        yield 'Zustimmung: mit Konto (AK-21)' => [UsageEventCatalogue::VOTE_GIVEN, ['user' => '42'], false];
        yield 'Presse-Kit: ohne Daten' => [UsageEventCatalogue::PRESS_KIT_DOWNLOADED, [], true];
        yield 'Datensatz: csv' => [UsageEventCatalogue::DATASET_DOWNLOADED, ['format' => 'csv'], true];
        yield 'Datensatz: xml' => [UsageEventCatalogue::DATASET_DOWNLOADED, ['format' => 'xml'], false];
        yield 'Wert kein Text' => [UsageEventCatalogue::DATASET_DOWNLOADED, ['format' => ['csv']], false];
        yield 'unbekanntes Ereignis' => ['newsletter_abonniert', [], false];
    }

    /** @param array<mixed> $daten */
    #[DataProvider('faelle')]
    public function testIsAllowed(string $ereignis, array $daten, bool $erwartet): void
    {
        self::assertSame($erwartet, (new UsageEventCatalogue())->isAllowed($ereignis, $daten));
    }
}
