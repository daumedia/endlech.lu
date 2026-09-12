<?php

declare(strict_types=1);

namespace App\Controller\Seo;

use App\Seo\SitemapGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Attribute\Route;

/**
 * `/sitemap.xml` (Feature 10) — sprachfrei, zustandslos, gedeckelt.
 *
 * Sprachfrei geroutet (Block `seo` in `config/routes.yaml`): Eine Sitemap steht im
 * Wurzelverzeichnis, weil sie nur dort die ganze Herkunft abdeckt
 * (developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap).
 *
 * ⚠ **Es darf kein Verzeichnis `public/sitemap.xml` oder `public/sitemap` entstehen** — der
 * Webserver lieferte sonst die Datei statt dieser Route aus, und die Sitemap wäre eingefroren
 * (dazu BF-100, `RouteDirectoryCollisionTest`).
 *
 * Der Deckel (60 je Stunde je Adresse, AK-23) greift im `RouteRateLimitSubscriber`, bevor
 * dieser Controller läuft — also auch dann, wenn eine gespeicherte Fassung vorliegt.
 */
final class SitemapController extends AbstractController
{
    /**
     * ⚠ Zusammen mit der Lebensdauer von `cache.sitemap` (3000 s) höchstens 3600 s — sonst hält
     * die Zusage „binnen 60 Minuten" aus AK-08/AK-09 nicht. `SitemapControllerTest` prüft die
     * Summe.
     */
    public const int MAX_AGE = 600;

    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], stateless: true)]
    public function sitemap(SitemapGenerator $generator): Response
    {
        $response = new Response($generator->xml(), Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
        $response->setPublic();
        $response->setMaxAge(self::MAX_AGE);

        // Ohne diesen Marker überschriebe Symfonys Session-Listener die Cache-Kopfzeile mit
        // „private, must-revalidate", sobald irgendwo eine Sitzung angefasst wurde — dasselbe
        // Muster wie bei den offenen Datenendpunkten. Die Sitemap ist für jeden Abrufer gleich.
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');

        return $response;
    }
}
