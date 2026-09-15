<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Leitet `/` und die sprachfreien Kurzlinks in die Sprache des Besuchers (2026-09-15).
 *
 * Reihenfolge: eine schon gewählte Sprache (Sitzung) → die Browsersprache (`Accept-Language`) →
 * Luxemburgisch. Die Auswahl selbst trifft `LocaleSubscriber`; dieser Controller übernimmt nur
 * `$request->getLocale()` in die Zieladresse. Bis dahin leitete Symfonys `RedirectController` fest auf
 * `/lb/` — ein Besucher mit portugiesischem Browser landete auf Luxemburgisch.
 *
 * ⚠ **Das `_locale` wird ausdrücklich übergeben, nicht dem Router überlassen.** Symfonys `LocaleListener`
 * (Priorität 16) schreibt die Sprache in den Router-Kontext, bevor `LocaleSubscriber` (15) sie aus
 * Sitzung oder Browser bestimmt. Ohne das ausdrückliche `_locale` erzeugte der Router weiter `/lb/…`.
 *
 * ⚠ **Die Routen dürfen kein `_locale` als Vorgabe tragen** (`config/routes.yaml`). Mit `_locale: lb`
 * hält `LocaleSubscriber` das für eine Wahl im Pfad, schreibt es in die Sitzung und überstimmt damit
 * den Browser — auch bei jedem späteren Aufruf.
 *
 * ⚠ **`Vary: Accept-Language`**, weil dieselbe Adresse je nach Browser woandershin führt. Ohne die
 * Kopfzeile dürfte ein Zwischenspeicher die erste Antwort an alle weitergeben. 302, nie 301: Ein
 * dauerhafter Umzug bliebe in fremden Browsern stehen (siehe BF-100).
 */
final class LocaleRedirectController extends AbstractController
{
    /**
     * @param list<string> $ignoreAttributes Routenvariablen, die nicht in die Zieladresse gehören
     */
    public function __invoke(Request $request, string $route, array $ignoreAttributes = []): RedirectResponse
    {
        $parameter = $request->attributes->get('_route_params', []);
        foreach (['route', 'ignoreAttributes', ...$ignoreAttributes] as $schluessel) {
            unset($parameter[$schluessel]);
        }
        $parameter['_locale'] = $request->getLocale();

        $antwort = $this->redirectToRoute($route, $parameter);
        $antwort->setVary(['Accept-Language', 'Cookie']);

        return $antwort;
    }
}
