<?php

namespace App\Tests\Integration\Seo;

use App\Seo\SeoUrlBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Feature 10 · Die Adressen, die Suchmaschinen sehen.
 *
 * ⚠ Der Dienst wird hier von Hand gebaut, mit dem echten Router. Aus dem Testcontainer
 * holen ginge erst, wenn ihn etwas benutzt — ungenutzte private Dienste entfernt Symfony.
 */
final class SeoUrlBuilderTest extends KernelTestCase
{
    private RouterInterface $router;
    private SeoUrlBuilder $builder;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->router = static::getContainer()->get('router');
        $this->builder = new SeoUrlBuilder($this->router, 'https://endlech.lu', ['lb', 'de', 'fr', 'en', 'pt']);
    }

    /** AK-03 · absolute Adresse, Sprachsegment direkt dahinter, Startseite mit Schrägstrich. */
    public function testAbsoluteAdressenMitSprachsegment(): void
    {
        self::assertSame('https://endlech.lu/de/', $this->builder->url('app_home', [], 'de'));
        self::assertSame('https://endlech.lu/fr/restaurants/5', $this->builder->url('app_restaurant_show', ['id' => 5], 'fr'));
        self::assertSame('https://endlech.lu/lb/vergleich/google-maps', $this->builder->url('app_comparison_show', ['slug' => 'google-maps'], 'lb'));
    }

    /**
     * AK-13 · die vier Beispielzeilen aus der Spec, dazu die Ränder.
     *
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function listenparameter(): iterable
    {
        yield 'Sortierung' => [['sort' => 'name'], 'https://endlech.lu/de/restaurants'];
        yield 'Filter' => [['wheelchair' => '1', 'city' => 'Esch'], 'https://endlech.lu/de/restaurants'];
        yield 'Seite 2 mit Sortierung' => [['page' => '2', 'sort' => 'name'], 'https://endlech.lu/de/restaurants?page=2'];
        yield 'Seite 1' => [['page' => '1'], 'https://endlech.lu/de/restaurants'];
        yield 'Seite 0' => [['page' => '0'], 'https://endlech.lu/de/restaurants'];
        yield 'Seite abc' => [['page' => 'abc'], 'https://endlech.lu/de/restaurants'];
        yield 'negative Seite' => [['page' => '-3'], 'https://endlech.lu/de/restaurants'];
        yield 'Seite als Liste' => [['page' => ['2']], 'https://endlech.lu/de/restaurants'];
        yield 'Seite mit Anhang' => [['page' => '2abc'], 'https://endlech.lu/de/restaurants'];
        yield 'Seite 12' => [['page' => '12'], 'https://endlech.lu/de/restaurants?page=12'];
    }

    /** @param array<string, mixed> $query */
    #[DataProvider('listenparameter')]
    public function testNurDieSeitenzahlAbZweiUeberlebt(array $query, string $erwartet): void
    {
        self::assertSame($erwartet, $this->builder->url('app_restaurant_index', [], 'de', $query));
    }

    /** AK-05 · alle Sprachen, die Seite selbst eingeschlossen, Vorgabe Luxemburgisch. */
    public function testSprachverweiseUmfassenAlleSprachenUndDieVorgabe(): void
    {
        $verweise = $this->builder->alternates('app_about', []);

        self::assertSame(['lb', 'de', 'fr', 'en', 'pt', 'x-default'], array_keys($verweise));
        self::assertSame('https://endlech.lu/de/about', $verweise['de']);
        self::assertSame($verweise['lb'], $verweise['x-default']);
    }

    /** AK-14 · Die Seitenzahl gilt für die Sprachverweise genauso wie für canonical. */
    public function testSprachverweiseBehaltenDieSeitenzahl(): void
    {
        $verweise = $this->builder->alternates('app_restaurant_index', [], ['page' => '2', 'wheelchair' => '1']);

        self::assertSame('https://endlech.lu/fr/restaurants?page=2', $verweise['fr']);
        self::assertSame('https://endlech.lu/lb/restaurants?page=2', $verweise['x-default']);
    }

    /**
     * AK-03, EC-06 · Kommt die Anfrage über `www` und `http`, lauten die Adressen trotzdem auf
     * die Hauptadresse. Das ist der Grund, warum der Host nicht aus der Anfrage stammt.
     */
    public function testAnfrageUeberWwwUndHttpAendertDieAdresseNicht(): void
    {
        $this->router->getContext()->setHost('www.endlech.lu')->setScheme('http')->setHttpPort(8080);

        self::assertSame('https://endlech.lu/en/presse', $this->builder->url('app_press_index', [], 'en'));
        self::assertSame(
            'http://www.endlech.lu:8080/en/presse',
            $this->router->generate('app_press_index', ['_locale' => 'en'], RouterInterface::ABSOLUTE_URL),
            'Gegenprobe: Der Router selbst folgt der Anfrage — genau das darf hier nicht durchschlagen.',
        );
    }
}
