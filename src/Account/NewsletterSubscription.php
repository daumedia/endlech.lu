<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Marketing\MarketingContactRegistry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Newsletter abonnieren und abbestellen aus einem Konto (Feature 12).
 *
 * Profilschalter und Newsletter-Schritt der Einführung rufen beide diesen Dienst — zwei Wege,
 * ein Verhalten (AK-35 = AK-33). Beide Methoden sagen, **ob sich etwas geändert hat**: Nur
 * dann verbraucht der Aufrufer ein Stück des gemeinsamen Kontingents `newsletter_toggle`
 * (AK-60, BF-11). Wer „abonnieren" schickt, obwohl schon abonniert, hat nichts getan.
 *
 * ⚠ **Abonnieren allein bringt niemanden nach Brevo.** Das Auftragsbuch nimmt nur bestätigte
 * Adressen (04/AK-05); bei einem unbestätigten Konto wartet die Einwilligung, bis der
 * Bestätigungslink sie mitnimmt (AK-38). Genau davor warnte `04/OF-02`: Ein Schalter, der nur
 * das Feld setzt, wäre eine folgenlose Zusage.
 */
final readonly class NewsletterSubscription
{
    public function __construct(
        private MarketingContactRegistry $marketingContacts,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param string $locale Sprache der Anfrage — sie wird die Sprache der Willkommens-Mail (AK-40)
     */
    public function subscribe(User $user, string $locale): bool
    {
        if ($user->hasMarketingConsent()) {
            return false;
        }

        $user->setMarketingConsentAt(
            $this->marketingContacts->consentMomentFor($user->getEmail(), new \DateTimeImmutable()),
        );
        $this->marketingContacts->recordUser($user, $locale);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // ⚠ BF-172 (Muster BF-121): Zwei Sitzungen desselben Kontos haben gleichzeitig abonniert;
            // beide fanden noch keine Zeile im Auftragsbuch, der Unique-Index auf `email` entschied.
            // Die andere Anfrage hat das Abo angelegt — für diese ist nichts geschehen, kein 500er.
            // Der EntityManager ist danach geschlossen; der Aufrufer leitet nur noch weiter.
            return false;
        }

        return true;
    }

    public function unsubscribe(User $user): bool
    {
        if (!$user->hasMarketingConsent()) {
            return false;
        }

        $this->marketingContacts->revokeForAccount($user);
        $this->entityManager->flush();

        return true;
    }
}
