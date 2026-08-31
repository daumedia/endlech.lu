<?php

declare(strict_types=1);

namespace App\Board;

use App\Entity\BoardIdea;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Die einzige Mail des Boards: „deine Idee ist jetzt öffentlich" (AK-37).
 *
 * ⚠ **Sie geht genau einmal.** `notifiedAt` ist die Sperre — zwei Admin-Fenster,
 * die dieselbe Idee gleichzeitig freigeben, erzeugen trotzdem nur eine
 * Nachricht (AK-38, EC-05).
 *
 * ⚠ **Auch eine Ablehnung ist eine Veröffentlichung.** Eine abgelehnte Idee
 * bleibt mit ihrer Begründung öffentlich stehen; deshalb ist diese Mail
 * zugleich ihre Benachrichtigung, und es braucht keine zweite Mailart.
 *
 * ⚠ **Der Beschreibungstext geht nicht mit** — nur Titel und Link (AK-54).
 *
 * ⚠ **Der Link zeigt auf das Original, wenn die Idee zusammengeführt wurde**
 * (AK-36). Sonst führte er auf eine Adresse, die nur weiterleitet.
 */
final readonly class BoardNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urls,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Verschickt die Nachricht, sofern sie noch nicht verschickt wurde.
     *
     * Ein Versandfehler wird geschluckt: Die Veröffentlichung ist bereits
     * geschehen und darf daran nicht scheitern (AK-39). Seit der Umstellung auf
     * die Messenger-Queue ist der eigentliche Versand ohnehin asynchron — ein
     * dort gescheiterter Versand landet im `failed`-Transport und in Sentry.
     */
    public function notifyPublished(BoardIdea $idea): void
    {
        if (null !== $idea->getNotifiedAt()) {
            return;
        }

        $empfaenger = $idea->getSubmittedBy()?->getEmail();

        // Kein Konto mehr (gelöscht) oder keine Adresse: Es gibt niemanden zu
        // benachrichtigen. Die Sperre wird trotzdem gesetzt, damit kein
        // späterer Lauf es erneut versucht.
        if (null !== $empfaenger && '' !== $empfaenger) {
            $ziel = $idea->getDuplicateOf() ?? $idea;
            $locale = $idea->getLocale();

            $mail = (new TemplatedEmail())
                ->to($empfaenger)
                ->subject($this->translator->trans('email.board_published_subject', [], null, $locale))
                ->locale($locale)
                ->htmlTemplate('email/board/published.html.twig')
                ->context([
                    'ideaTitle' => $ziel->getTitle(),
                    'ideaUrl' => $this->urls->generate('app_board_show', [
                        '_locale' => $locale,
                        'id' => $ziel->getId(),
                        'slug' => $ziel->getSlug(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);

            try {
                $this->mailer->send($mail);
            } catch (TransportExceptionInterface) {
                // Die Veröffentlichung steht bereits und bleibt bestehen.
            }
        }

        $idea->setNotifiedAt(new \DateTimeImmutable());
        $this->em->flush();
    }
}
