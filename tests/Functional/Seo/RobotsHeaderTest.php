<?php

namespace App\Tests\Functional\Seo;

use App\Seo\IndexablePage;
use App\Seo\SeoRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/** Feature 10 · Ausschluss aus Suchergebnissen per Kopfzeile — und nur dort, wo er hingehört. */
final class RobotsHeaderTest extends WebTestCase
{
    /** Erfüllt die Anforderung `[a-f0-9]{64}` aller Token-Wege, ist aber kein gültiger Token. */
    private const string PROBE_TOKEN = 'abababababababababababababababababababababababababababababababab';

    private function pfad(KernelBrowser $client, string $route, array $parameter, string $locale): string
    {
        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        return $router->generate($route, [...$parameter, '_locale' => $locale]);
    }

    /**
     * AK-15 · Alle sechzehn Wege tragen `X-Robots-Tag: noindex` — auch die zwei, die nur
     * weiterleiten, und die Token-Wege, die bei einem unbekannten Token eine Fehlerseite liefern.
     */
    public function testJederAusschlusswegTraegtDieKopfzeile(): void
    {
        $client = static::createClient();
        $ohne = [];
        $status = [];

        foreach ((new SeoRegistry())->excludedRoutes() as $route) {
            $router = $client->getContainer()->get('router');
            $braucht = $router->getRouteCollection()->get($route)?->compile()->getPathVariables() ?? [];
            $parameter = \in_array('token', $braucht, true) ? ['token' => self::PROBE_TOKEN] : [];
            $pfad = $this->pfad($client, $route, $parameter, 'de');

            $client->request('GET', $pfad);
            $antwort = $client->getResponse();
            $status[$route] = $antwort->getStatusCode();

            if ('noindex' !== $antwort->headers->get('X-Robots-Tag')) {
                $ohne[] = "$route ($pfad → {$antwort->getStatusCode()})";
            }
        }

        self::assertCount(16, $status);
        self::assertSame([], $ohne, "Ohne Ausschluss-Kopfzeile:\n  ".implode("\n  ", $ohne));

        // Die Zusage aus OF-04: Auch reine Weiterleitungen tragen die Kopfzeile.
        self::assertTrue($status['app_verify_email'] >= 300 && $status['app_verify_email'] < 400, 'app_verify_email sollte weiterleiten');
        self::assertTrue($status['app_email_change_confirm'] >= 300 && $status['app_email_change_confirm'] < 400, 'app_email_change_confirm sollte weiterleiten');
    }

    /** AK-16 · Keine Seite aus der Sitemap trägt die Kopfzeile oder ein `noindex` im Quelltext. */
    public function testKeineVerzeichnisseiteIstAusgeschlossen(): void
    {
        $client = static::createClient();
        $seiten = [...(new SeoRegistry())->fixedPages(), new IndexablePage(SeoRegistry::RESTAURANT_ROUTE, ['id' => $this->ersteRestaurantnummer($client)])];
        $betroffen = [];

        foreach ($seiten as $seite) {
            foreach (['lb', 'de'] as $locale) {
                $pfad = $this->pfad($client, $seite->route, $seite->parameters, $locale);
                $client->request('GET', $pfad);
                $antwort = $client->getResponse();

                self::assertSame(200, $antwort->getStatusCode(), $pfad);
                if ($antwort->headers->has('X-Robots-Tag') || str_contains((string) $antwort->getContent(), 'noindex')) {
                    $betroffen[] = $pfad;
                }
            }
        }

        self::assertSame([], $betroffen);
    }

    private function ersteRestaurantnummer(KernelBrowser $client): int
    {
        $ids = $client->getContainer()->get(\App\Repository\RestaurantRepository::class)->findAllIdsAscending();
        self::assertNotEmpty($ids);

        return $ids[0];
    }
}
