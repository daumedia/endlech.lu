<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Die Presseseite (Feature 05).
 *
 * Geprüft wird, was eine Redaktion tatsächlich vorfindet: Antwortet die Seite in
 * jeder Sprache, sind alle sieben Abschnitte da, steht die Kurzbeschreibung im
 * Kopf, und bleibt der Sprachwechsel auf der Seite statt auf der Startseite zu
 * landen.
 */
final class PressControllerTest extends AbstractWebTestCase
{
    /** Die sieben Abschnitte, die AK-42 in jeder Sprache verlangt. */
    private const ABSCHNITTE = ['boilerplate', 'fakten', 'material', 'person', 'zitate', 'meldungen', 'kontakt'];

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en'] as $locale) {
            yield $locale => [$locale];
        }
    }

    /** AK-02: 200 in allen vier Sprachen. */
    #[DataProvider('sprachen')]
    public function testDieSeiteAntwortetInAllenVierSprachen(string $locale): void
    {
        $client = static::createClient();
        $client->request('GET', '/'.$locale.'/presse');

        self::assertResponseIsSuccessful();
    }

    /** AK-42: alle sieben Abschnitte in jeder Sprache — keine Sprachfassung ist halb. */
    #[DataProvider('sprachen')]
    public function testAlleSiebenAbschnitteStehenInJederSprache(string $locale): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/'.$locale.'/presse');

        foreach (self::ABSCHNITTE as $id) {
            self::assertCount(1, $crawler->filter('section#'.$id), sprintf(
                'Abschnitt #%s fehlt in der Sprachfassung %s.', $id, $locale,
            ));
        }
    }

    /** AK-05: der sprachfreie Kurzlink führt auf die Seite, nicht auf eine Fehlerseite. */
    public function testDerSprachfreieKurzlinkLeitetAufDieSeite(): void
    {
        $client = static::createClient();
        $client->request('GET', '/presse');

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('/presse', $client->getRequest()->getUri());
    }

    /** AK-04: Der Sprachwechsel bleibt auf der Presseseite. */
    public function testDerSprachwechselBleibtAufDerPresseseite(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        self::assertResponseIsSuccessful();
        foreach (['lb', 'fr', 'en'] as $locale) {
            self::assertGreaterThan(
                0,
                $crawler->filter('a[href="/'.$locale.'/presse"]')->count(),
                sprintf('Kein Weg nach /%s/presse — der Sprachwechsel verlässt die Seite.', $locale),
            );
        }
    }

    /** AK-06: eigener Fenstertitel, nicht der einer anderen Seite. */
    public function testDieSeiteTraegtEinenEigenenFenstertitel(): void
    {
        $client = static::createClient();

        $presse = $client->request('GET', self::LOCALE.'/presse')->filter('title')->text();
        $about = $client->request('GET', self::LOCALE.'/about')->filter('title')->text();

        self::assertNotSame($about, $presse);
        self::assertStringContainsString('Endlech.lu', $presse);
    }

    /** AK-43/AK-44: Kurzbeschreibung und kanonische Adresse im Kopf des Dokuments. */
    #[DataProvider('sprachen')]
    public function testKurzbeschreibungUndKanonischeAdresse(string $locale): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/'.$locale.'/presse');

        $beschreibung = $crawler->filter('meta[name="description"]');
        self::assertCount(1, $beschreibung, sprintf('Keine Kurzbeschreibung in %s.', $locale));
        self::assertNotSame('', trim($beschreibung->attr('content') ?? ''));

        $canonical = $crawler->filter('link[rel="canonical"]');
        self::assertCount(1, $canonical);
        self::assertStringEndsWith('/'.$locale.'/presse', (string) $canonical->attr('href'), sprintf(
            'Die kanonische Adresse zeigt nicht auf diese Sprachfassung (%s).', $locale,
        ));

        // AK-44 zweite Hälfte: die vier Sprachverweise zeigen auf die Presseseite.
        foreach (['lb', 'de', 'fr', 'en'] as $andere) {
            self::assertGreaterThan(
                0,
                $crawler->filter('link[rel="alternate"][hreflang="'.$andere.'"]')->count(),
                sprintf('Sprachverweis auf %s fehlt.', $andere),
            );
        }
    }

    /** AK-27: Ohne Meldung bleibt der Abschnitt stehen und verweist auf den Kontakt. */
    public function testOhneMeldungStehtDerHinweisStattEinerLeerenListe(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        $abschnitt = $crawler->filter('section#meldungen');
        self::assertCount(1, $abschnitt, 'Der Meldungsabschnitt ist ausgeblendet statt leer.');
        self::assertStringContainsString(
            'Noch keine Pressemitteilung',
            $abschnitt->text(),
            'Der leere Zustand nennt seinen Grund nicht.',
        );
        self::assertGreaterThan(0, $abschnitt->filter('a[href="#kontakt"]')->count());
    }

    /** AK-39: Zusatzparameter in der Adresse ändern nichts und erzeugen keinen Fehler. */
    public function testZusatzparameterAendernNichts(): void
    {
        $client = static::createClient();

        $ohne = $client->request('GET', self::LOCALE.'/presse')->filter('main')->html();
        $mit = $client->request('GET', self::LOCALE.'/presse?sort=alles&id=4711&x[]=1')->filter('main')->html();

        self::assertResponseIsSuccessful();
        self::assertSame($ohne, $mit, 'Ein Query-Parameter verändert die ausgelieferte Seite.');
    }

    /**
     * AK-36: Über die Person steht nur, was vorgesehen ist.
     *
     * Geprüft als Abwesenheit: keine Telefonnummer, kein Geburtsdatum. Beides
     * wäre eine Angabe, die niemand beschlossen hat und die sich nach der
     * Verbreitung nicht mehr einfangen lässt.
     */
    public function testKeineWeiterenAngabenZurPerson(): void
    {
        $client = static::createClient();
        $inhalt = $client->request('GET', self::LOCALE.'/presse')->filter('main')->text();

        self::assertDoesNotMatchRegularExpression('/tel:|\+352[\s\d]{6,}/', $inhalt, 'Auf der Seite steht eine Telefonnummer.');
        self::assertDoesNotMatchRegularExpression('/\b\d{1,2}\.\d{1,2}\.(19|20)\d{2}\b/', $inhalt, 'Auf der Seite steht ein vollständiges Datum, das wie ein Geburtsdatum aussieht.');
    }
}
