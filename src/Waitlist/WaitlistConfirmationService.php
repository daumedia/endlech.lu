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

        $email = (new TemplatedEmail())
            ->to($entry->getEmail())
            ->subject($this->translator->trans($subjectKey, $subjectParams))
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
    public function confirm(?WaitlistEntryInterface $entry): string
    {
        if (!$entry) {
            return self::RESULT_INVALID;
        }

        if ($entry->isConfirmed()) {
            return self::RESULT_ALREADY;
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
