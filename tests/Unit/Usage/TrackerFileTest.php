<?php

namespace App\Tests\Unit\Usage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Feature 11 · Das Zählskript `public/zaehler.js` ist der Umami-Tracker in fester Version.
 *
 * ⚠ **Die Datei liegt im Repository, nicht auf dem zweiten VPS** (Entwurf, Entscheidung 2): Sie
 * lädt ohne PHP und ohne Umami. Der Preis ist, dass sie mit der Umami-Version auf dem VPS
 * übereinstimmen muss — dieser Prüflauf hält die Version an einer Stelle fest und prüft, dass
 * das Skript die Eigenschaften hat, auf die sich die Messung verlässt.
 */
final class TrackerFileTest extends TestCase
{
    private const string DATEI = __DIR__.'/../../../public/zaehler.js';

    /** @return array{string, string} Kopfzeile, Skript */
    private function datei(): array
    {
        $inhalt = (string) file_get_contents(self::DATEI);
        $teile = explode("\n", $inhalt, 2);

        return [$teile[0], $teile[1] ?? ''];
    }

    public function testKopfzeileNenntDieVersionAusDerKonfiguration(): void
    {
        [$kopf] = $this->datei();
        self::assertMatchesRegularExpression('#^/\*! Umami Tracker (\d+\.\d+\.\d+) .*MIT#', $kopf);
        preg_match('#Umami Tracker (\d+\.\d+\.\d+)#', $kopf, $treffer);

        $parameter = Yaml::parseFile(__DIR__.'/../../../config/services.yaml')['parameters'];

        self::assertSame(
            $parameter['app.umami_tracker_version'],
            $treffer[1],
            'Version der Datei und app.umami_tracker_version laufen auseinander — wer Umami aktualisiert, ersetzt beides.',
        );
    }

    /** AK-24 · Das Skript kennt keinen fremden Host; es schickt neben sich selbst an /api/send. */
    public function testSkriptNenntKeinenFremdenHostUndZaehltNebenSich(): void
    {
        [, $skript] = $this->datei();

        self::assertDoesNotMatchRegularExpression('#https?://#', $skript);
        // Host aus dem Skriptpfad, dann der Zählweg — ohne data-host-url landet der Aufruf
        // bei https://endlech.lu/api/send.
        self::assertStringContainsString('src.split("/").slice(0,-1).join("/")', $skript);
        self::assertStringContainsString('/api/send', $skript);
    }

    /**
     * Die Messung verlässt sich auf diese Tracker-Eigenschaften (Entwurf, Entscheidungen 10–12).
     * Fehlt eine nach einem Update, wäre z. B. der Widerspruchsschalter wirkungslos, ohne dass
     * eine Seite sich anders verhielte.
     *
     * @return iterable<string, array{string}>
     */
    public static function eigenschaften(): iterable
    {
        yield 'Widerspruch über den Browserspeicher (AK-23)' => ['umami.disabled'];
        yield 'Do Not Track (AK-22)' => ['do-not-track'];
        yield 'Vor-Versand-Prüfung für GPC (AK-22)' => ['before-send'];
        yield 'Beschränkung auf eine Domain (AK-09)' => ['domains'];
        yield 'ohne Abfrage (AK-03)' => ['exclude-search'];
        yield 'ohne Anker' => ['exclude-hash'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('eigenschaften')]
    public function testSkriptKenntDieEigenschaft(string $merkmal): void
    {
        [, $skript] = $this->datei();

        self::assertStringContainsString($merkmal, $skript);
    }
}
