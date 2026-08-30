<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;

/**
 * Die Zahlen auf /presse stimmen mit dem veröffentlichten Endpunkt überein (AK-12).
 *
 * ⚠ **Geschrieben in der QA.** `PressFactsTest` belegt, dass der Dienst dieselbe
 * Quelle liest wie die Transparenzseite — das ist die halbe Zusage. Die andere
 * Hälfte ist, dass die **gerenderte Seite** dieselbe Zahl zeigt wie
 * `/open.json`. Dazwischen liegen Template, Zahlenformatierung und
 * Zwischenspeicher; eine Abweichung dort wäre am Dienst nicht zu sehen.
 *
 * Ohne diesen Lauf wäre AK-12 einmal von Hand verglichen worden (am 2026-08-30:
 * 11 / 3 / 8 auf beiden Wegen) und danach nie wieder.
 */
final class PressFiguresConsistencyTest extends AbstractWebTestCase
{
    public function testDieSeiteZeigtDieselbenZahlenWieDerOffeneEndpunkt(): void
    {
        $client = static::createClient();

        $client->request('GET', '/open.json');
        self::assertResponseIsSuccessful();
        $json = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $platform = $json['platform'];

        $crawler = $client->request('GET', self::LOCALE.'/presse');
        self::assertResponseIsSuccessful();

        $kacheln = $crawler->filter('#fakten .tabular-nums')->each(
            static fn ($k) => (int) preg_replace('/\D/', '', $k->text()),
        );

        self::assertCount(3, $kacheln, 'Es stehen nicht genau drei Kennzahlen im Faktenblatt.');
        self::assertSame(
            [(int) $platform['restaurants'], (int) $platform['verified'], (int) $platform['communesCovered']],
            $kacheln,
            'Die Presseseite zeigt andere Zahlen als /open.json — eine davon landet in einem Artikel.',
        );

        // Die Bezugsgröße der Gemeindeabdeckung steht als Suffix daneben.
        self::assertStringContainsString(
            (string) $platform['totalCommunes'],
            $crawler->filter('#fakten')->text(),
            'Die Gesamtzahl der Gemeinden fehlt oder weicht ab.',
        );
    }
}
