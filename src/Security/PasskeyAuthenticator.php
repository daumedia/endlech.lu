<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webauthn\Bundle\Security\Authentication\WebauthnAuthenticator;
use Webauthn\Bundle\Security\Authentication\WebauthnBadge;
use Webauthn\Bundle\Security\Authentication\WebauthnPassport;

/**
 * Anmeldung per Passkey – als gewöhnlicher Formular-Login.
 *
 * Das Bundle bietet daneben einen fertigen `webauthn:`-Schlüssel für die
 * Firewall. Der ist zum einen für Version 6.0 abgekündigt, zum anderen nimmt er
 * die Assertion ausschliesslich als JSON-Body entgegen. Dieser Authenticator
 * liest sie stattdessen aus einem Formularfeld, und damit läuft der
 * Passkey-Login durch dieselbe Mechanik wie der Passwort-Login: derselbe
 * check_path, dieselbe Weiterleitung, dasselbe „Angemeldet bleiben".
 *
 * Die Login-Seite führt zwei Formulare (siehe `partials/_passkey_login.html.twig`:
 * der AuthenticationController des Fremdpakets ruft `form.checkValidity()`, und
 * im Passwort-Formular sind beide Felder `required`). Beide schicken an
 * denselben check_path. Welcher Weg gemeint ist, entscheidet allein das Feld
 * `_assertion`: Ist es VORHANDEN, greift dieser Authenticator (Priorität 0),
 * sonst der form_login-Authenticator (-30).
 */
final class PasskeyAuthenticator extends WebauthnAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * ⚠ `has()`, nicht `getString(...) !== ''` (ENDLECH-6).
     *
     * Das Passkey-Formular führt kein `_username`. Wurde hier auf einen
     * GEFÜLLTEN Wert geprüft, fiel ein Submit mit leerer Assertion an den
     * form_login-Authenticator durch — und der wirft bei fehlendem `_username`
     * eine BadRequestHttpException. Der Nutzer sah statt der Meldung
     * „Passkey-Anmeldung fehlgeschlagen" eine nackte Fehlerseite.
     *
     * Mit `has()` beansprucht dieser Authenticator jeden Submit, der aus dem
     * Passkey-Formular stammt. Eine leere oder unbrauchbare Assertion scheitert
     * dann regulär in `authenticate()` und landet über `onAuthenticationFailure`
     * als Flash-Nachricht auf der Login-Seite (gemessen: 302 statt 400, für
     * leer, Nicht-JSON, `{}` und unvollständiges JSON gleichermaßen).
     */
    public function supports(Request $request): bool
    {
        return $request->isMethod('POST')
            && $request->attributes->get('_route') === 'app_login'
            && $request->request->has('_assertion');
    }

    public function authenticate(Request $request): Passport
    {
        return new WebauthnPassport(
            new WebauthnBadge($request->getHost(), $request->request->getString('_assertion')),
            [new RememberMeBadge()],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $target = $this->getTargetPath($request->getSession(), $firewallName)
            ?? $this->urlGenerator->generate('app_home');

        return new RedirectResponse($target);
    }

    /**
     * Ersetzt die technische Meldung des Prüfers durch einen verständlichen Satz.
     *
     * Bewusst nicht das Verhalten der Basisklasse (Exception in der Session,
     * Anzeige über error.messageKey): Deren Meldungen kämen aus der
     * `security`-Domäne, die das Projekt nicht führt, und benennen Interna wie
     * „The credential ID is invalid". Eine Flash-Nachricht landet dagegen im
     * bereits eingebundenen _flash_messages-Partial.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', $this->translator->trans('flash.passkey_login_failed'));
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}
