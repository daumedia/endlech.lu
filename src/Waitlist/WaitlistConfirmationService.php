<?php

namespace App\Waitlist;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Double-Opt-In für beide Wartelisten: Token erzeugen, Bestätigungsmail
 * verschicken, Token einlösen und das Team benachrichtigen.
 *
 * Die Reihenfolge ist überall dieselbe und bewusst so: Token setzen → flush →
 * absolute URL erzeugen → Mail. Erst dadurch ist der Eintrag beim Versand
 * garantiert gespeichert; scheitert der Transport, ist die Anmeldung trotzdem
 * nicht verloren.
 */
final class WaitlistConfirmationService
{
    public const RESULT_CONFIRMED = 'confirmed';
    public const RESULT_ALREADY = 'already';
    public const RESULT_INVALID = 'invalid';

    /** Der Link war gültig, ist aber zu alt (BF-36). */
    public const RESULT_EXPIRED = 'expired';

    /** Gültigkeitsdauer eines Bestätigungslinks in Tagen. */
    public const TOKEN_LIFETIME_DAYS = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.contact_email%')]
        private readonly string $contactEmail,
    ) {
    }

    /**
     * Legt den Eintrag mit frischem Token an und verschickt die
     * Bestätigungsmail.
     *
     * @param string $confirmRoute   Route der Bestätigungsseite (Token als {token})
     * @param string $emailTemplate  Twig-Template der Bestätigungsmail
     * @param string $subjectKey     Übersetzungsschlüssel der Betreffzeile
     * @param array<string, mixed> $subjectParams
     *
     * @return bool false, wenn der Versand scheiterte – der Eintrag ist dann
     *              dennoch gespeichert
     */
    public function register(
        WaitlistEntryInterface $entry,
        string $confirmRoute,
        string $emailTemplate,
        string $subjectKey,
        array $subjectParams = [],
    ): bool {
        $token = $entry->generateConfirmationToken();

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $confirmUrl = $this->urlGenerator->generate(
            $confirmRoute,
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        // ⚠ BF-10: `->locale()` ist Pflicht, sobald der Versand asynchron laufen
        // kann. Der Worker hat keine Anfrage und damit keine Sprache — er nimmt
        // `default_locale` (lb), und ein französischsprachiger Interessent bekäme
        // seine Bestätigung auf Luxemburgisch. Auf Production fällt es heute nicht
        // auf, weil dort synchron versendet wird (`sync://`); es kippt in dem
        // Moment, in dem ein Messenger-Worker dazukommt — und genau der ist für die
        // Monats-Snapshots vorgesehen (B18/AK-17).
        //
        // Der Betreff wird ebenfalls in der Sprache des Eintrags übersetzt, nicht
        // in der der aktuellen Anfrage: Beides muss zusammenpassen.
        $locale = $entry->getLocale();

        $email = (new TemplatedEmail())
            ->to($entry->getEmail())
            ->subject($this->translator->trans($subjectKey, $subjectParams, null, $locale))
            ->locale($locale)
            ->htmlTemplate($emailTemplate)
            ->context([
                'entry' => $entry,
                'confirmUrl' => $confirmUrl,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    /**
     * Löst den Token ein.
     *
     * Der Token bleibt danach absichtlich stehen – nur so lässt sich ein
     * zweiter Klick auf denselben Link ("bereits bestätigt") von einem
     * unbekannten Token ("Link ungültig") unterscheiden.
     *
     * @return self::RESULT_* Zustand für die Bestätigungsseite
     */
    /**
     * ⚠ BF-36: Der Token hatte kein Ablaufdatum — anders als
     * `User::generateVerificationToken()`, der nach 24 Stunden verfällt. Ein Link
     * aus einer Mail, die vor einem Jahr verschickt wurde, bestätigte weiterhin
     * eine Einwilligung, die inzwischen niemand mehr im Kopf hat.
     *
     * Gemessen wird an `createdAt` statt an einer neuen Spalte: Der Zeitpunkt steht
     * bereits in beiden Entities, und eine Migration für eine Frist, die aus ihm
     * folgt, wäre eine Spalte mehr ohne eigene Aussage.
     *
     * Sieben Tage, nicht 24 Stunden: Eine Wartelisten-Anmeldung ist kein
     * Anmeldevorgang: Wer sie an einem Freitagabend abschickt, liest die Mail
     * vielleicht erst am Montag.
     */
    public function confirm(?WaitlistEntryInterface $entry): string
    {
        if (!$entry) {
            return self::RESULT_INVALID;
        }

        if ($entry->isConfirmed()) {
            return self::RESULT_ALREADY;
        }

        if ($this->isExpired($entry)) {
            return self::RESULT_EXPIRED;
        }

        $entry->confirm();
        $this->entityManager->flush();

        return self::RESULT_CONFIRMED;
    }

    /**
     * Interne Meldung ans Team – bewusst fest auf Deutsch, unabhängig davon, in
     * welcher Sprache der Interessent bestätigt hat. Ein Zustellproblem hier
     * darf die Bestätigung des Nutzers nicht kaputt machen, deshalb wird die
     * Transport-Exception geschluckt.
     *
     * @param array<string, mixed> $subjectParams
     * @param array<string, mixed> $context
     */
    /**
     * Ein Bestätigungslink verfällt nach dieser Frist.
     */
    public function isExpired(WaitlistEntryInterface $entry): bool
    {
        return $entry->getCreatedAt() < new \DateTimeImmutable('-'.self::TOKEN_LIFETIME_DAYS.' days');
    }

    public function notifyTeam(
        WaitlistEntryInterface $entry,
        string $emailTemplate,
        string $subjectKey,
        array $subjectParams = [],
        array $context = [],
    ): void {
        $email = (new TemplatedEmail())
            ->to($this->contactEmail)
            ->replyTo($entry->getEmail())
            ->subject($this->translator->trans($subjectKey, $subjectParams, null, 'de'))
            ->htmlTemplate($emailTemplate)
            ->context(array_merge(['entry' => $entry], $context));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            // Der Eintrag ist bereits bestätigt und gespeichert.
        }
    }
}
