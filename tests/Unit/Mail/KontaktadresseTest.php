<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;

/**
 * Endlech.lu hat genau ein Postfach: `support@endlech.lu` (2026-09-15).
 *
 * Bis dahin stand `info@endlech.lu` im Impressum, in der Barrierefreiheitserklärung und — schwerer —
 * als Vorgabe für `CONTACT_EMAIL`. Daran hängen drei interne Mailwege: die Team-Meldung bei
 * bestätigten Wartelisten-Anmeldungen, die Rückstau-Warnung des Messenger-Wächters und die
 * Barriere-Meldungen aus dem Formular auf `/barrierefreiheit`. Ohne gesetzte Variable gingen alle drei
 * an eine Adresse, die es nicht gibt — lautlos, denn der Versand selbst gelingt.
 *
 * Geprüft wird alles, was ausgeliefert wird oder die Laufzeit bestimmt. Prüfberichte und Specs unter
 * `features/` und `qa/` bleiben ausgenommen: Sie halten fest, was zu ihrem Zeitpunkt galt.
 */
final class KontaktadresseTest extends TestCase
{
    private const string GIBT_ES_NICHT = 'info@endlech.lu';

    /** Verzeichnisse und Dateien relativ zum Projekt. */
    private const array ORTE = ['templates', 'translations', 'config', 'src', 'public', '.env', '.env.test'];

    /** Binär oder erzeugt — dort steht die Adresse nicht als Text, und `public/build` baut Encore. */
    private const array AUSGENOMMEN = ['/public/build/', '.zip', '.png', '.jpg', '.woff2', '.ico'];

    public function testDieNichtExistierendeAdresseStehtNirgends(): void
    {
        $wurzel = \dirname(__DIR__, 3);
        $treffer = [];

        foreach (self::ORTE as $ort) {
            foreach ($this->dateien($wurzel.'/'.$ort) as $datei) {
                if (str_contains((string) file_get_contents($datei), self::GIBT_ES_NICHT)) {
                    $treffer[] = substr($datei, \strlen($wurzel) + 1);
                }
            }
        }

        self::assertSame([], $treffer, sprintf(
            '%s existiert nicht — das Postfach ist support@endlech.lu. Gefunden in: %s',
            self::GIBT_ES_NICHT,
            implode(', ', $treffer),
        ));
    }

    public function testDieVorgabeFuerInterneMeldungenIstDasEchtePostfach(): void
    {
        $env = (string) file_get_contents(\dirname(__DIR__, 3).'/.env');

        self::assertMatchesRegularExpression('/^CONTACT_EMAIL=support@endlech\.lu$/m', $env);
    }

    /** @return iterable<string> */
    private function dateien(string $pfad): iterable
    {
        if (is_file($pfad)) {
            yield $pfad;

            return;
        }
        if (!is_dir($pfad)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pfad, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $datei) {
            $name = $datei->getPathname();
            foreach (self::AUSGENOMMEN as $muster) {
                if (str_contains($name, $muster)) {
                    continue 2;
                }
            }
            yield $name;
        }
    }
}
