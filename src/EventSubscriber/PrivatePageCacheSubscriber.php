<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Saved\PrivatePages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * `Cache-Control: no-store, private` auf Seiten mit privaten Angaben eines angemeldeten Kontos
 * (Feature 17, AK-63). `no-store` nimmt die Seite aus dem Verlaufsspeicher und dem
 * Back-Forward-Cache: „Zurück“ nach dem Abmelden lädt vom Server, und der sieht keine Sitzung
 * mehr. Welche Seiten das sind, steht in {@see PrivatePages}; das Gegenstück für Turbos
 * Schnappschüsse ist `is_private_page()` im Seitenkopf.
 *
 * ⚠ **Hier wird überschrieben — anders als in `SecurityHeadersSubscriber`.** Jeder Wert, den
 * ein Controller, ein `#[Cache]`-Attribut oder der Session-Zuhörer hinterlässt, ist schwächer;
 * auf diesen Seiten soll es keinen Ausweg aus `no-store` geben.
 */
final class PrivatePageCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly PrivatePages $pages)
    {
    }

    /**
     * ⚠ **Priorität -900: nach allen übrigen Antwort-Zuhörern, vor dem Session-Zuhörer (-1000).**
     * Danach kommt nur noch dieser — er setzte bei benutzter Sitzung `private, must-revalidate,
     * max-age=0` samt `Expires` darüber, ließe er sich nicht abbestellen. Das Abbestellen
     * (`NO_AUTO_CACHE_CONTROL_HEADER`, Muster aus `OpenDataController`) liest er nur, wenn es vor
     * ihm gesetzt ist. Nach -10 steht diese Zahl, damit ein `#[Cache]`-Attribut
     * (`CacheAttributeListener`) den Wert nicht wieder lockert.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [['onKernelResponse', -900]],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->pages->isPrivateView($event->getRequest())) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('Cache-Control', 'no-store, private');
        $headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
    }
}
