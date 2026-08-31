<?php

declare(strict_types=1);

namespace App\Board;

use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Repository\BoardVoteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Alles, was den Zustand einer Idee verändert.
 *
 * ⚠ **Die einzige Stelle im Code, die `publishedAt` setzt.** Deshalb ist AK-71
 * („kein Beitrag war je ohne Freigabe öffentlich") hier und im Repository
 * prüfbar — an zwei Stellen, nicht an fünf.
 *
 * ⚠ **Jeder Weg prüft zuerst den Zustand.** Ein Doppelklick auf „Freigeben"
 * oder die Zurück-Taste des Browsers darf nicht zweimal wirken. Genau das
 * passierte bei den Restaurantvorschlägen (BF-54): Zweimal abgeschickt erzeugte
 * zwei Restaurants, beide mit Erfolgsmeldung (EC-05).
 */
final readonly class BoardModerator
{
    public function __construct(
        private EntityManagerInterface $em,
        private BoardNotifier $notifier,
        private BoardVoteRepository $votes,
    ) {
    }

    /**
     * Gibt eine wartende Idee frei (AK-26).
     *
     * ⚠ **Erst speichern, dann benachrichtigen.** Scheitert der Versand, bleibt
     * die Idee trotzdem öffentlich — die Zustellung darf die Veröffentlichung
     * nicht rückgängig machen (AK-39).
     *
     * @return bool false, wenn sie bereits öffentlich war
     */
    public function publish(BoardIdea $idea): bool
    {
        if ($idea->isPublished()) {
            return false;
        }

        $idea->setPublishedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->notifier->notifyPublished($idea);

        return true;
    }

    /**
     * Lehnt eine Idee ab — **mit öffentlicher Begründung** (AK-27, AK-28).
     *
     * ⚠ **Ohne Begründung geschieht nichts.** Produktprinzip 2 („Lücken werden
     * gezeigt, nicht versteckt") gilt sonst nur, solange es dem Betreiber
     * gelegen kommt. Die Pflicht ist hier erzwungen, nicht erhofft.
     *
     * ⚠ **Eine Ablehnung ist eine Veröffentlichung.** Die Idee bleibt mit ihrer
     * Begründung im Board stehen; deshalb wird auch hier `publishedAt` gesetzt
     * und die eine Mail verschickt.
     *
     * @return bool false, wenn die Begründung leer ist
     */
    public function decline(BoardIdea $idea, string $reason): bool
    {
        if ('' === trim($reason)) {
            return false;
        }

        $idea->setTeamResponse(trim($reason));
        $idea->setStatus(BoardIdeaStatus::DECLINED);

        $erstmals = !$idea->isPublished();
        if ($erstmals) {
            $idea->setPublishedAt(new \DateTimeImmutable());
        }

        $this->em->flush();

        if ($erstmals) {
            $this->notifier->notifyPublished($idea);
        }

        return true;
    }

    /**
     * Wechselt den Status einer bereits öffentlichen Idee (AK-33).
     *
     * ⚠ **Kein Mailversand.** Benachrichtigt wird einmal, bei der
     * Veröffentlichung; jeder weitere Wechsel bleibt still (Decision Log 8).
     *
     * ⚠ **Nur auf Veröffentlichtes anwendbar.** Ein Statuswechsel an einer
     * wartenden Idee wäre eine Freigabe durch die Hintertür.
     *
     * @return bool false, wenn die Idee noch wartet
     */
    public function changeStatus(BoardIdea $idea, BoardIdeaStatus $status): bool
    {
        if (!$idea->isPublished()) {
            return false;
        }

        $idea->setStatus($status);
        $this->em->flush();

        return true;
    }

    /** Setzt oder ändert die öffentliche Antwort des Teams (AK-32). */
    public function setResponse(BoardIdea $idea, ?string $response): void
    {
        $response = null === $response ? null : trim($response);
        $idea->setTeamResponse('' === $response ? null : $response);
        $this->em->flush();
    }

    /**
     * Löscht eine Einreichung endgültig (AK-30).
     *
     * ⚠ **Nur solange sie nie öffentlich war.** Eine veröffentlichte Idee wird
     * abgelehnt, nicht gelöscht — andere haben für sie gestimmt und das Team
     * hat geantwortet.
     *
     * @return bool false, wenn die Idee bereits öffentlich ist
     */
    public function delete(BoardIdea $idea): bool
    {
        if ($idea->isPublished()) {
            return false;
        }

        $this->em->remove($idea);
        $this->em->flush();

        return true;
    }

    /**
     * Führt eine Idee als Dublette in eine andere über (AK-34).
     *
     * Die Stimmen wandern auf das Original. ⚠ **Ein Konto, das für beide
     * gestimmt hat, zählt einmal** — seine Stimme an der Dublette wird
     * verworfen, sonst schlüge der Unique-Index zu.
     *
     * @return bool false, wenn Ziel und Quelle dasselbe sind oder das Ziel
     *              selbst schon eine Dublette ist
     */
    public function merge(BoardIdea $duplicate, BoardIdea $original): bool
    {
        if ($duplicate === $original || null !== $original->getDuplicateOf()) {
            return false;
        }

        $schonDort = [];
        foreach ($this->votes->findByIdea($original) as $stimme) {
            $schonDort[(int) $stimme->getUser()?->getId()] = true;
        }

        foreach ($this->votes->findByIdea($duplicate) as $stimme) {
            if (isset($schonDort[(int) $stimme->getUser()?->getId()])) {
                $this->em->remove($stimme);

                continue;
            }

            $stimme->setIdea($original);
        }

        $duplicate->setDuplicateOf($original);
        $this->em->flush();

        return true;
    }
}
