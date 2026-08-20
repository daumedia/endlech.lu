<?php

namespace App\Tests\Functional\Controller\Open;

use App\Tests\AbstractWebTestCase;

final class OpenDataControllerTest extends AbstractWebTestCase
{
    public function testMetricsJsonMirrorsThePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open.json');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('platform', $data);
        self::assertArrayHasKey('impact', $data);
        self::assertArrayHasKey('finance', $data);
        self::assertArrayHasKey('trend', $data);
        self::assertSame('CC-BY-4.0', $data['licence']);
    }

    /**
     * Die Endpunkte sind sprachfrei geroutet – ein /de/open.json würde
     * zitierte URLs auf vier Varianten verteilen.
     */
    public function testMetricsJsonIsNotAvailableUnderALocalePrefix(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/open.json');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Die Quartalssperre muss auch im maschinenlesbaren Weg greifen: Wären die
     * Beträge im Ergebnis und nur im Template verborgen, stünden sie hier.
     */
    public function testWithheldIncomeIsAbsentFromTheJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open.json');

        $finance = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR)['finance'];

        self::assertFalse($finance['incomeVisible']);
        self::assertSame([], $finance['income']);
        self::assertNull($finance['totalIncome']);
    }

    public function testDatasetCsvIsDownloadableAndLabelled(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open/dataset.csv');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
        self::assertResponseHeaderSame('X-Licence', 'CC-BY-4.0');
        self::assertStringContainsString('attachment', (string) $client->getResponse()->headers->get('Content-Disposition'));

        $lines = array_filter(explode("\n", (string) $client->getResponse()->getContent()));
        self::assertGreaterThan(1, \count($lines), 'Kopfzeile plus mindestens ein Datensatz.');
        self::assertStringContainsString('accessibilityScore', $lines[0]);
        self::assertStringContainsString('doorWidthCm', $lines[0]);
    }

    /**
     * Datensparsamkeit: Ein Sammelabzug von Kontaktdaten wäre eine
     * Adressliste, kein Barrierefreiheits-Datensatz.
     */
    public function testDatasetContainsNoContactDetails(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open/dataset.csv');
        $header = explode("\n", (string) $client->getResponse()->getContent())[0];

        self::assertStringNotContainsString('email', strtolower($header));
        self::assertStringNotContainsString('phone', strtolower($header));
        self::assertStringNotContainsString('@', (string) $client->getResponse()->getContent());
    }

    public function testDatasetJsonCarriesLicenceAndAttribution(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open/dataset.json');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('CC-BY-4.0', $data['licence']);
        self::assertSame('Endlech.lu', $data['attribution']);
        self::assertSame(\count($data['data']), $data['count']);
        self::assertArrayHasKey('commune', $data['data'][0]);
        self::assertArrayHasKey('canton', $data['data'][0]);
    }

    /**
     * Ohne den NO_AUTO_CACHE_CONTROL-Marker überschreibt Symfonys
     * Session-Listener die Header mit "private, must-revalidate" – der
     * öffentliche Cache wäre wirkungslos.
     */
    public function testResponsesArePubliclyCacheable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/open.json');

        $cacheControl = (string) $client->getResponse()->headers->get('Cache-Control');

        self::assertStringContainsString('public', $cacheControl);
        self::assertStringContainsString('max-age=3600', $cacheControl);
    }
}
