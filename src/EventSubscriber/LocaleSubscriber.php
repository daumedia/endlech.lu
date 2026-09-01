<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_LOCALES = ['lb', 'de', 'fr', 'en'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // ⚠ Sitzungslose Routen bleiben sitzungslos. Ohne diese Zeile legt der
        // Sprachwähler noch fuer den Healthcheck eine Sitzung an — alle 30
        // Sekunden eine neue Datei in var/cache, dauerhaft, ohne dass jemand sie
        // je liest. Betroffen ist jede Route, die `stateless: true` traegt.
        if ($request->attributes->getBoolean('_stateless')) {
            return;
        }

        // _locale aus Route-Parameter → Session speichern
        $locale = $request->attributes->get('_locale');

        if ($locale && in_array($locale, self::ALLOWED_LOCALES, true)) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);

            return;
        }

        // Erstbesucher: Accept-Language Header auswerten
        if (!$request->getSession()->has('_locale')) {
            $preferred = $request->getPreferredLanguage(self::ALLOWED_LOCALES);
            if ($preferred) {
                $request->getSession()->set('_locale', $preferred);
            }
        }

        $request->setLocale($request->getSession()->get('_locale', 'lb'));
    }
}
