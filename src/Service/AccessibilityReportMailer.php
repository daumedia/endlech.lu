<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Verschickt eine Barriere-Meldung an die Kontaktadresse — und speichert nichts.
 *
 * ⚠ Der Meldetext ist eine besondere Kategorie nach Art. 9 (wer eine Barriere
 * meldet, beschreibt fast zwangsläufig seine Behinderung): Er darf NUR ins
 * Postfach, nicht in die Datenbank (es gibt keine Entity), nicht ins Protokoll,
 * nicht ins Fehler-Tracking (AK-56, AK-57). Scheitert der Versand, wird
 * ausschließlich die Ausnahmeklasse und der Statuscode protokolliert — niemals
 * die Beschreibung oder die Adresse des Melders.
 *
 * Die interne Mail geht fest auf Deutsch an das Team (Muster wie
 * WaitlistConfirmationService::notifyTeam), unabhängig von der Sprache des
 * Melders.
 */
final class AccessibilityReportMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.contact_email%')]
        private readonly string $contactEmail,
    ) {
    }

    /**
     * @return bool false, wenn der Versand scheiterte — der Melder erfährt es,
     *              behält aber seinen Text (nichts wurde gespeichert)
     */
    public function send(string $description, ?string $senderEmail): bool
    {
        $email = (new TemplatedEmail())
            ->to($this->contactEmail)
            ->subject($this->translator->trans('accessibility_statement.email_subject', [], null, 'de'))
            ->locale('de')
            ->htmlTemplate('email/accessibility_report.html.twig')
            ->context([
                'description' => $description,
                'senderEmail' => $senderEmail,
            ]);

        // replyTo nur, wenn der Melder freiwillig eine Adresse angegeben hat (AK-49).
        if (null !== $senderEmail && '' !== $senderEmail) {
            $email->replyTo($senderEmail);
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // ⚠ AK-57: NUR Klasse + Code. Weder die Beschreibung noch die Adresse
            // dürfen in den Log-Record — beides ginge sonst über Monolog (und in
            // prod über den Sentry-Handler) nach außen. Der context trägt deshalb
            // ausschließlich technische Angaben.
            $this->logger->warning('Barriere-Meldung konnte nicht versendet werden.', [
                'exception_class' => $e::class,
                'code' => $e->getCode(),
            ]);

            return false;
        }

        return true;
    }
}
