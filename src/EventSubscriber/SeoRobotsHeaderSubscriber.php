<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Seo\SeoRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Setzt `X-Robots-Tag: noindex` auf jede Antwort der Wege, die Suchmaschinen nicht in
 * Ergebnisse aufnehmen sollen (Feature 10, AK-15).
 *
 * ⚠ **Kopfzeile statt Meta-Element, und das ist entschieden (OF-04).** Zwei der Wege — die
 * Bestätigung einer E-Mail-Adresse und die eines Adresswechsels — rendern nie eine Seite, sie
 * leiten nur weiter. Eine Kopfzeile trägt jede Antwort, auch eine Weiterleitung oder eine
 * Fehlerseite nach einem ungültigen Token; laut Google ist sie dem Meta-Element gleichwertig
 * (developers.google.com/search/docs/crawling-indexing/block-indexing).
 *
 * ⚠ **Diese Wege dürfen in `public/robots.txt` NICHT gesperrt werden.** Eine Sperre dort
 * verhinderte, dass Suchmaschinen diese Kopfzeile je lesen (AK-20).
 *
 * Welche Wege betroffen sind, steht ausschließlich im Seitenverzeichnis. Vorhandene Kopfzeilen
 * werden nicht überschrieben — dasselbe Muster wie `SecurityHeadersSubscriber`.
 */
final readonly class SeoRobotsHeaderSubscriber implements EventSubscriberInterface
{
    public function __construct(private SeoRegistry $registry)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => [['onKernelResponse', -100]]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        if (!\is_string($route) || !$this->registry->isExcludedRoute($route)) {
            return;
        }

        $headers = $event->getResponse()->headers;
        if (!$headers->has('X-Robots-Tag')) {
            $headers->set('X-Robots-Tag', 'noindex');
        }
    }
}
