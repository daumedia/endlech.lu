<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_LOCALES = ['lb', 'de', 'fr', 'en'];

    public function __construct(private readonly RequestStack $requests)
    {
    }

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
        //
        // ⚠ **Auch die Unteranfrage einer zustandslosen Hauptanfrage** (Feature 10). Eine
        // Fehlerantwort — die 429 des Sitemap-Deckels, eine 5xx — rendert Symfony in einer
        // Unteranfrage, und die trägt `_stateless` nicht. Ohne die zweite Bedingung legte
        // der Sprachwähler dort eine Sitzung an: im Debug-Modus eine
        // `UnexpectedSessionUsageException` (aus der 429 wurde eine 500), in Produktion ein
        // `PHPSESSID`-Cookie an einen Crawler und eine Warnung je gedeckeltem Abruf in
        // Sentry. Beim Selbsttest am 2026-09-12 genau so gemessen.
        //
        // Nur die Hauptanfrage zählt, nicht jede Unteranfrage: Die Fehlerseite einer
        // gewöhnlichen HTML-Seite braucht die Sprache aus der Sitzung weiterhin.
        if ($request->attributes->getBoolean('_stateless')
            || true === $this->requests->getMainRequest()?->attributes->getBoolean('_stateless')) {
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
