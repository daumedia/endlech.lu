<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Press\PressPackage;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Die Seite mit **vorhandenem** Materialpaket (Feature 05).
 *
 * ⚠ **Befund-Nachweis zu BUG-05 — dieser Lauf ist absichtlich rot**, bis
 * `PressPackage` einen öffentlichen Zugriff auf den Pfad hat.
 *
 * Der bestehende `PressEdgeCaseTest::testOhnePaketStehtDerKontaktwegAnSeinerStelle`
 * verzweigt an `PressPackage::exists()` und prüft in der heutigen Umgebung
 * ausschließlich den **Ersatzzweig**. Damit lag der eigentliche Regelfall des
 * Features — es gibt ein Paket, der Knopf steht — in keinem einzigen Lauf, und
 * ein Fehler darin konnte sich hinter einer offenen Vorbedingung verstecken.
 * Genau das ist passiert: `_material.html.twig:44` ruft `package.publicPath`, das
 * es nicht gibt, und die Seite antwortet in allen vier Sprachen mit 500.
 *
 * Der Lauf legt das Paket selbst an und räumt es wieder weg — er hängt nicht an
 * VB-01.
 */
final class PressDownloadStateTest extends AbstractWebTestCase
{
    private string $pfad = '';

    protected function setUp(): void
    {
        $this->pfad = \dirname(__DIR__, 3).'/public/'.PressPackage::PUBLIC_PATH;

        if (is_file($this->pfad)) {
            $this->pfad = '';  // ein echtes Paket liegt vor: nichts anlegen, nichts löschen

            return;
        }

        $verzeichnis = \dirname($this->pfad);
        if (!is_dir($verzeichnis)) {
            mkdir($verzeichnis, 0o775, true);
        }
        file_put_contents($this->pfad, "PK\x05\x06".str_repeat("\0", 18));
    }

    /**
     * ⚠ `parent::tearDown()` ist Pflicht: `KernelTestCase::tearDown()` fährt den
     * Kernel herunter. Ohne den Aufruf bleibt er zwischen zwei Testmethoden
     * gebootet, und der zweite `createClient()` wirft „Booting the kernel before
     * calling createClient() is not supported" — ein Fehler, der wie ein
     * Anwendungsfehler aussieht und keiner ist.
     */
    protected function tearDown(): void
    {
        if ('' !== $this->pfad && is_file($this->pfad)) {
            unlink($this->pfad);
            @rmdir(\dirname($this->pfad));
        }

        parent::tearDown();
    }

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en'] as $locale) {
            yield $locale => [$locale];
        }
    }

    /** AK-02 im Regelfall: Die Seite antwortet auch dann mit 200, wenn ein Paket vorliegt. */
    #[DataProvider('sprachen')]
    public function testDieSeiteAntwortetAuchMitVorhandenemPaket(string $locale): void
    {
        $client = static::createClient();
        $client->request('GET', '/'.$locale.'/presse');

        self::assertResponseIsSuccessful(sprintf(
            'Mit vorhandenem Paket antwortet /%s/presse nicht mit 200 — siehe BUG-05.', $locale,
        ));
    }

    /** AK-19/AK-20: Der Downloadlink zeigt auf das Paket und nennt Format und Größe. */
    public function testDerDownloadlinkZeigtAufDasPaketUndNenntFormatUndGroesse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        self::assertResponseIsSuccessful();
        $link = $crawler->filter('section#material a[download]');
        self::assertCount(1, $link, 'Mit vorhandenem Paket fehlt der Downloadlink.');
        self::assertStringContainsString(PressPackage::PUBLIC_PATH, (string) $link->attr('href'));
        self::assertStringContainsString('ZIP', $link->text());
        self::assertMatchesRegularExpression('/\d/', $link->text(), 'Der Linktext nennt keine Größe.');
    }
}
