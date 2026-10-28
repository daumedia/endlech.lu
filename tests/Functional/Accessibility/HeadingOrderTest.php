<?php

declare(strict_types=1);

namespace App\Tests\Functional\Accessibility;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * BF-109 — die Überschriftenkette jeder Seite muss lückenlos sein (WCAG 1.3.1,
 * axe-Regel `heading-order`).
 *
 * Gefunden bei der QA von Feature `07`: Die Fussleiste überschrieb ihre vier
 * Spalten mit `<h4>`, und weil die letzte Inhaltsüberschrift eine `h2` ist,
 * sprang die Kette **jeder Seite** von h2 auf h4. Nachgemessen auf `/presse`
 * (`…,2,4,4,4`), `/open`, `/about`, `/vergleich` und `/community/ideen` — ein
 * Fehler in der App-Hülle, der wie ein Fehler des jeweiligen Features aussah und
 * AK-34 sowie AK-38 von Feature `07` blockierte.
 *
 * ⚠ **Geprüft wird die Kette, nicht die Fussleiste.** Ein Lauf, der nur nach
 * `<h4>` im Layout sucht, wäre nach der Reparatur blind für die nächste Seite,
 * die mit einer eigenen Lücke dazukommt. Für einen Screenreader ist die
 * Überschriftenliste das Inhaltsverzeichnis; eine übersprungene Ebene liest sich
 * dort als fehlender Abschnitt.
 */
final class HeadingOrderTest extends AbstractWebTestCase
{
    /**
     * Ein Querschnitt durch die Hülle: Startseite, Liste, Statikseite, jede der
     * drei jüngsten Aussenseiten, das Board und die Rechtsseite.
     *
     * @return iterable<string, array{string}>
     */
    public static function seiten(): iterable
    {
        yield 'Startseite' => ['/'];
        yield 'Restaurantliste' => ['/restaurants'];
        yield 'Über uns' => ['/about'];
        yield 'Presse' => ['/presse'];
        yield 'Transparenz' => ['/open'];
        yield 'Roadmap' => ['/roadmap'];
        yield 'Changelog' => ['/changelog'];
        yield 'Ideen-Board' => ['/community/ideen'];
        yield 'Impressum' => ['/legal'];
        yield 'Sponsoren' => ['/sponsoren'];
        yield 'Unternehmen und Vereine' => ['/organisationen/unternehmen'];
    }

    #[DataProvider('seiten')]
    public function testDieUeberschriftenketteHatKeineLuecke(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        self::assertResponseIsSuccessful();

        $ebenen = $crawler->filter('h1, h2, h3, h4, h5, h6')->each(
            static fn ($knoten) => [(int) substr($knoten->nodeName(), 1), trim($knoten->text())],
        );

        self::assertNotEmpty($ebenen, 'Eine Seite ohne jede Überschrift hat kein Inhaltsverzeichnis.');

        $vorige = 0;
        foreach ($ebenen as [$ebene, $text]) {
            if ($vorige > 0 && $ebene > $vorige + 1) {
                self::fail(sprintf(
                    'BF-109: %s springt von h%d auf h%d bei „%s". Kette: %s',
                    $pfad,
                    $vorige,
                    $ebene,
                    mb_substr($text, 0, 40),
                    implode(',', array_column($ebenen, 0)),
                ));
            }

            $vorige = $ebene;
        }
    }

    /**
     * Die zweite Hälfte der Regel: **genau eine** `h1` je Seite. Ohne diesen Fall
     * liesse sich die Lücke auch schliessen, indem jemand aus der Fussleiste eine
     * zweite Hauptüberschrift macht — die Kette wäre formal in Ordnung und die
     * Seite hätte zwei Titel.
     */
    #[DataProvider('seiten')]
    public function testJedeSeiteHatGenauEineHauptueberschrift(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        self::assertCount(
            1,
            $crawler->filter('h1'),
            sprintf('%s: Eine Seite trägt genau eine h1.', $pfad),
        );
    }
}
