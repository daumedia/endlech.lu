<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Bringt die Fehlerantworten des JWT-Bundles auf dieselbe Form wie alle anderen
 * API-Antworten: {"error": {"code", "message"}}.
 *
 * Ohne diesen Subscriber antwortet das Bundle mit einem flachen
 * {"code": 401, "message": "Invalid JWT Token"} — es wirft keine Exception, an der
 * {@see ApiExceptionSubscriber} ansetzen könnte, sondern schreibt die Antwort selbst.
 *
 * Das traf ausgerechnet die beiden häufigsten Fehlerfälle eines Mobil-Clients:
 * falsches Passwort und abgelaufenes Token. Ein Client, der einheitlich `error.code`
 * liest, bekam dort `undefined` und zeigte im Zweifel gar keine Meldung.
 */
final class ApiAuthenticationFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            Events::JWT_INVALID => 'onTokenInvalid',
            Events::JWT_NOT_FOUND => 'onTokenMissing',
            Events::JWT_EXPIRED => 'onTokenExpired',
        ];
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        // getMessageKey() liefert den Schlüssel, den Symfony selbst für
        // Anmeldefehler führt ("Invalid credentials."); die Domäne `security`
        // bringt die vorhandenen Übersetzungen mit.
        $message = $this->translator->trans(
            $event->getException()->getMessageKey(),
            [],
            'security',
        );

        $event->setResponse($this->antwort($message));
    }

    public function onTokenInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse($this->antwort($this->translator->trans('api.token_invalid')));
    }

    public function onTokenMissing(JWTNotFoundEvent $event): void
    {
        $event->setResponse($this->antwort($this->translator->trans('api.token_missing')));
    }

    public function onTokenExpired(JWTExpiredEvent $event): void
    {
        $event->setResponse($this->antwort($this->translator->trans('api.token_expired')));
    }

    /**
     * Durchgehend 401: Alle vier Fälle bedeuten „nicht angemeldet". 403 bleibt dem
     * Fall vorbehalten, dass jemand angemeldet ist und die Rolle fehlt — dieselbe
     * Trennung, die {@see ApiExceptionSubscriber} zieht.
     */
    private function antwort(string $message): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => 401, 'message' => $message]], 401);
    }
}
