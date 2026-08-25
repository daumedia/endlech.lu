<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * BF-51: Ein leeres Pflichtfeld muss 422 ergeben, nicht 500.
 *
 * `setName(string)` nimmt kein `null`, und Symfony übergibt genau das, wenn ein
 * Textfeld leer bleibt und `empty_data` fehlt. Der Nutzer bekam einen Serverfehler
 * statt der Meldung, die direkt daneben konfiguriert ist — im Admin-Formular und
 * im Vorschlags-Assistenten.
 *
 * Das ist dasselbe Muster wie BF-27 und BF-62: Die Prüfung fehlt dort, wo der Wert
 * hereinkommt, nicht dort, wo er verbraucht wird.
 */
final class PflichtfeldValidierungTest extends AbstractWebTestCase
{
    #[DataProvider('formulare')]
    public function testLeeresPflichtfeldLiefert422(string $pfad, string $konto, string $feld): void
    {
        $client = static::createClient();
        $this->loginAs($client, $konto);

        $crawler = $client->request('GET', self::LOCALE.$pfad);
        self::assertResponseIsSuccessful();

        // Über ein Feld greifen, nicht über die Reihenfolge: In der Kopfzeile steht
        // das Abmelde-Formular, und `filter('form')->first()` erwischt genau das.
        $client->submit($this->formWithField($crawler, $feld));

        self::assertSame(
            422,
            $client->getResponse()->getStatusCode(),
            sprintf('%s liefert %d statt 422.', $pfad, $client->getResponse()->getStatusCode()),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function formulare(): iterable
    {
        yield 'Vorschlags-Assistent' => ['/community/suggest', 'user@endlech.lu', 'restaurant_suggestion[name]'];
        yield 'Admin: neues Restaurant' => ['/admin/restaurants/neu', 'admin@endlech.lu', 'restaurant[name]'];
    }
}
