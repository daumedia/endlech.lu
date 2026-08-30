<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Marketing\MarketingContactRegistry;
use App\RateLimit\ActionLimiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Nimmt Brevos Meldungen zu Abmeldung, Bounce, Beschwerde und Kontaktlöschung
 * entgegen (Feature 04).
 *
 * ⚠ **Das ist der einzige Weg von außen nach innen** und entsprechend eng
 * gefasst. Es gibt keine zweite Schicht: Das Projekt hat kein RLS, und was die
 * Anwendung nicht prüft, ist ungeprüft. Die gesamte Absicherung dieses
 * Endpunkts ist der Vergleich eines Geheimnisses – deshalb steht er ganz vorne
 * und vor jedem Datenbankzugriff.
 *
 * ⚠ **Sprachfrei.** Brevo ruft ohne Sprachpräfix. Der Pfad fiele damit durch
 * alle `^/[a-z]{2}/…`-Regeln hindurch und wäre von keiner `access_control`-Zeile
 * gedeckt – derselbe Fallstrick, der bei den Passkey-Endpunkten zu BF-18
 * führte. `config/routes.yaml` importiert dieses Verzeichnis deshalb in einem
 * eigenen Block, und `security.yaml` trägt eine eigene Zeile für `^/marketing/`.
 */
#[Route('/marketing/brevo')]
class BrevoWebhookController extends AbstractController
{
    /**
     * Ereignisse, auf die dieser Endpunkt reagiert.
     *
     * Alle vier führen zu derselben Handlung: Die Adresse wird gesperrt und
     * die Einwilligung an der Quelle gelöscht. Ein harter Bounce und eine
     * Spam-Beschwerde sind zwar keine Abmeldung, aber in beiden Fällen darf
     * dieselbe Adresse nicht weiter bespielt werden – bei einer Beschwerde ist
     * das Weitersenden zusätzlich ein Zustellrisiko für die ganze Domain.
     */
    private const BLOCKING_EVENTS = ['unsubscribed', 'hardBounce', 'spam', 'contactDeleted'];

    /**
     * Ereignisse, die zusätzlich die Einwilligung **an der Quelle** entwerten.
     *
     * ⚠ **`contactDeleted` steht bewusst nicht drin (BF-84b).** Brevo meldet es
     * auch bei einer Löschung über die API – also als Echo unseres eigenen
     * Aufrufs. Vorher löschte dieses Echo `marketing_consent_at` an allen
     * Quellen und vernichtete damit einen Nachweis nach Art. 7 Abs. 1 DSGVO,
     * den niemand widerrufen hatte.
     *
     * Der Unterschied ist inhaltlich und nicht technisch: Eine Abmeldung, ein
     * harter Bounce und eine Beschwerde sagen etwas über den Empfänger aus –
     * eine gelöschte Karteikarte bei Brevo sagt nur etwas über die Karteikarte.
     */
    private const CONSENT_WITHDRAWING_EVENTS = ['unsubscribed', 'hardBounce', 'spam'];

    public function __construct(
        private readonly MarketingContactRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%app.brevo_webhook_token%')]
        private readonly string $webhookToken,
        #[Autowire(service: 'limiter.marketing_webhook')]
        private readonly RateLimiterFactoryInterface $webhookLimiter,
    ) {
    }

    /**
     * ⚠ **Antwortet immer mit 200, auch bei unbekannter Adresse.** Eine
     * unterschiedliche Antwort verriete, ob eine Adresse im Bestand ist –
     * dieselbe Überlegung wie bei der Anti-Enumeration in Registrierung und
     * Passwort-Zurücksetzen. Die einzigen Ausnahmen sind das falsche Geheimnis
     * (401) und das überschrittene Limit (429); beides sagt nichts über
     * einzelne Adressen aus.
     */
    #[Route('/webhook', name: 'app_marketing_brevo_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        // Erst der Deckel: Ein Endpunkt, der ein Geheimnis prüft, ist ohne ihn
        // ein kostenloser Rateversuch-Automat (CLAUDE.md-Konvention). Der
        // Verbrauch steht bewusst VOR der Geheimnisprüfung – hier ist der
        // Fehlversuch der Angriff und nicht ein Tippfehler, genau wie beim
        // Passwortwechsel.
        $limiter = ActionLimiter::for($this->webhookLimiter, $request->getClientIp());

        if (!$limiter->isAllowed()) {
            return new JsonResponse(
                ['status' => 'rate_limited'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $limiter->retryAfter()],
            );
        }

        $limiter->consume();

        if (!$this->isAuthorised($request)) {
            return new JsonResponse(['status' => 'unauthorised'], Response::HTTP_UNAUTHORIZED);
        }

        // ⚠ Aus dem Rumpf werden AUSSCHLIESSLICH die Adresse und der
        // Ereignistyp gelesen. Alles andere, was Brevo mitschickt, wird
        // verworfen. Andernfalls wäre ein kompromittiertes Brevo-Konto ein
        // Schreibzugang in die eigene Datenbank.
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return $this->ok();
        }

        $email = \is_string($payload['email'] ?? null) ? trim($payload['email']) : '';
        $event = \is_string($payload['event'] ?? null) ? $payload['event'] : '';

        if ('' === $email || !\in_array($event, self::BLOCKING_EVENTS, true)) {
            return $this->ok();
        }

        $this->registry->blockByEmail(
            $email,
            \in_array($event, self::CONSENT_WITHDRAWING_EVENTS, true),
        );
        $this->entityManager->flush();

        return $this->ok();
    }

    /**
     * Vergleich in konstanter Zeit – ein Vergleich mit `===` verriete über die
     * Laufzeit, wie viele Zeichen stimmen.
     *
     * Ein leeres Geheimnis lehnt **jede** Anfrage ab. Das ist die richtige
     * Richtung für AK-47: „still aus" heißt beim Senden, dass nichts hinausgeht
     * – beim Empfangen heißt es, dass nichts hereinkommt. Ein Endpunkt, der bei
     * fehlender Konfiguration alles durchließe, wäre offen.
     */
    private function isAuthorised(Request $request): bool
    {
        if ('' === $this->webhookToken) {
            return false;
        }

        $header = $request->headers->get('Authorization', '');
        $given = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';

        return hash_equals($this->webhookToken, $given);
    }

    private function ok(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
