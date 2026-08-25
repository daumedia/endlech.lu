<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminLocaleController extends AbstractController
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    /**
     * Wechselt die Sprache im Verwaltungsbereich — über den PFAD, nicht über die Sitzung.
     *
     * ⚠ Der Sitzungswert `_locale` hatte keinen Leser (BF-34): Die Sprache kommt aus
     * `/{_locale}/…`, und ohne einen Listener, der die Sitzung darüberschreibt, blieb
     * jede Wahl wirkungslos. Statt einen solchen Listener einzuführen — der dem
     * Pfad-Prinzip des Projekts widerspräche (B24, Decision Log 1) — wird die Route
     * aufgelöst, aus der der Aufruf kam, und in der neuen Sprache neu erzeugt.
     *
     * ⚠ Der Referer wird dabei NICHT als Ziel übernommen (BF-33), sondern nur als
     * Wegweiser gelesen: Aus ihm stammt allein der Routenname, und die Zieladresse
     * baut anschließend der Router. Ein fremder Host kann so gar nicht erst entstehen.
     * Vorher landete ein Admin über einen Link auf der echten Domain bei
     * `https://boeswillig.example/phishing` — mit ausgerechnet dem Zugang, der ohne
     * zweite Stufe auskommt.
     */
    #[Route('/locale/{locale}', name: 'admin_set_locale', requirements: ['locale' => 'lb|de|fr|en'])]
    public function setLocale(string $locale, Request $request): RedirectResponse
    {
        return $this->redirectToRoute(...$this->ziel($request, $locale));
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function ziel(Request $request, string $locale): array
    {
        $fallback = ['admin_dashboard', ['_locale' => $locale]];
        $referer = (string) $request->headers->get('referer');

        if ('' === $referer) {
            return $fallback;
        }

        $teile = parse_url($referer);
        if (false === $teile || !isset($teile['path'])) {
            return $fallback;
        }

        // Ein Referer von fremder Herkunft wird gar nicht erst aufgelöst.
        if (isset($teile['host']) && $teile['host'] !== $request->getHost()) {
            return $fallback;
        }

        $pfad = $teile['path'];
        $basis = $request->getBaseUrl();
        if ('' !== $basis && str_starts_with($pfad, $basis)) {
            $pfad = substr($pfad, \strlen($basis));
        }

        try {
            $parameter = $this->router->match($pfad);
        } catch (RoutingException) {
            return $fallback;
        }

        $route = $parameter['_route'] ?? '';
        // Nur zurück in den Verwaltungsbereich — und nicht auf die Umschaltroute selbst,
        // sonst entstünde bei einem Doppelklick eine Kette aus Weiterleitungen.
        if (!\is_string($route) || !str_starts_with($route, 'admin_') || 'admin_set_locale' === $route) {
            return $fallback;
        }

        unset($parameter['_route'], $parameter['_controller']);
        $parameter['_locale'] = $locale;

        if (isset($teile['query'])) {
            parse_str($teile['query'], $query);
            unset($query['_locale']);
            $parameter += $query;
        }

        return [$route, $parameter];
    }
}
