<?php

namespace App\Controller;

use App\Board\AuthorName;
use App\Board\BoardModerator;
use App\Board\Overdue;
use App\Board\StaleIdeaCleaner;
use App\Entity\BoardIdea;
use App\Enum\BoardIdeaStatus;
use App\Repository\BoardIdeaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Die Moderationsschlange des Community-Boards (Feature 06).
 *
 * ⚠ **Jeder schreibende Weg prüft zuerst den Zustand** — im `BoardModerator`.
 * Ein Doppelklick auf „Freigeben" oder die Zurück-Taste des Browsers darf nicht
 * zweimal wirken; genau das erzeugte bei den Restaurantvorschlägen zwei
 * Restaurants mit zwei Erfolgsmeldungen (BF-54, EC-05).
 */
#[Route('/admin/ideen')]
#[IsGranted('ROLE_ADMIN')]
final class AdminBoardController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly BoardModerator $moderator,
    ) {
    }

    #[Route('', name: 'admin_board_index', methods: ['GET'])]
    public function index(BoardIdeaRepository $ideas, Overdue $overdue, AuthorName $authorName, StaleIdeaCleaner $cleaner): Response
    {
        // ⚠ Hier hängt AK-74. Der Aufräumlauf hat bewusst keinen eigenen
        // Cron-Eintrag: Auf Produktion fehlen von drei geplanten Läufen zwei.
        // Höchstens einmal je Tag, über einen Cache-Schlüssel gesperrt.
        $cleaner->sweepOncePerDay();

        $wartend = $ideas->findAwaitingReview();

        $stufen = [];
        foreach ($wartend as $idee) {
            $stufen[(int) $idee->getId()] = $overdue->levelFor($idee);
        }

        return $this->render('admin/board/index.html.twig', [
            'awaiting' => $wartend,
            'levels' => $stufen,
            'published' => $ideas->findPublishedForAdmin(),
            'authorName' => $authorName,
        ]);
    }

    #[Route('/{id}', name: 'admin_board_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(BoardIdea $idea, BoardIdeaRepository $ideas, Overdue $overdue, AuthorName $authorName): Response
    {
        return $this->render('admin/board/show.html.twig', [
            'idea' => $idea,
            'voteCount' => $ideas->countVotesForOne($idea),
            'level' => $overdue->levelFor($idea),
            'authorName' => $authorName,
            'statuses' => BoardIdeaStatus::cases(),
            'candidates' => $ideas->findPublishedForAdmin(),
        ]);
    }

    #[Route('/{id}/veroeffentlichen', name: 'admin_board_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(BoardIdea $idea, Request $request): Response
    {
        if (!$this->pruefeToken($request, 'board-publish-' . $idea->getId())) {
            return $this->redirectToRoute('admin_board_index');
        }

        if ($this->moderator->publish($idea)) {
            $this->addFlash('success', $this->translator->trans('flash.board_published'));
        } else {
            $this->addFlash('warning', $this->translator->trans('flash.board_already_published'));
        }

        return $this->redirectToRoute('admin_board_index');
    }

    #[Route('/{id}/ablehnen', name: 'admin_board_decline', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function decline(BoardIdea $idea, Request $request): Response
    {
        if (!$this->pruefeToken($request, 'board-decline-' . $idea->getId())) {
            return $this->redirectToRoute('admin_board_index');
        }

        // ⚠ Ohne öffentliche Begründung geschieht nichts (AK-27). Produktprinzip 2
        // gilt sonst nur, solange es dem Betreiber gelegen kommt.
        if (!$this->moderator->decline($idea, $request->request->getString('reason'))) {
            $this->addFlash('error', $this->translator->trans('flash.board_reason_required'));

            return $this->redirectToRoute('admin_board_show', ['id' => $idea->getId()]);
        }

        $this->addFlash('success', $this->translator->trans('flash.board_declined'));

        return $this->redirectToRoute('admin_board_index');
    }

    #[Route('/{id}/status', name: 'admin_board_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function status(BoardIdea $idea, Request $request): Response
    {
        if (!$this->pruefeToken($request, 'board-status-' . $idea->getId())) {
            return $this->redirectToRoute('admin_board_index');
        }

        $status = BoardIdeaStatus::tryFrom($request->request->getString('status'));

        if (null === $status || !$this->moderator->changeStatus($idea, $status)) {
            $this->addFlash('error', $this->translator->trans('flash.board_status_failed'));
        } else {
            $this->addFlash('success', $this->translator->trans('flash.board_status_changed'));
        }

        return $this->redirectToRoute('admin_board_show', ['id' => $idea->getId()]);
    }

    #[Route('/{id}/antwort', name: 'admin_board_response', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function response(BoardIdea $idea, Request $request): Response
    {
        if (!$this->pruefeToken($request, 'board-response-' . $idea->getId())) {
            return $this->redirectToRoute('admin_board_index');
        }

        $this->moderator->setResponse($idea, $request->request->getString('response'));
        $this->addFlash('success', $this->translator->trans('flash.board_response_saved'));

        return $this->redirectToRoute('admin_board_show', ['id' => $idea->getId()]);
    }

    #[Route('/{id}/dublette', name: 'admin_board_merge', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function merge(BoardIdea $idea, Request $request, BoardIdeaRepository $ideas): Response
    {
        if (!$this->pruefeToken($request, 'board-merge-' . $idea->getId())) {
            return $this->redirectToRoute('admin_board_index');
        }

        $ziel = $ideas->find($request->request->getInt('target'));

        if (!$ziel instanceof BoardIdea || !$this->moderator->merge($idea, $ziel)) {
            $this->addFlash('error', $this->translator->trans('flash.board_merge_failed'));

            return $this->redirectToRoute('admin_board_show', ['id' => $idea->getId()]);
        }

        $this->addFlash('success', $this->translator->trans('flash.board_merged'));

        return $this->redirectToRoute('admin_board_show', ['id' => $ziel->getId()]);
    }

    #[Route('/{id}/loeschen', name: 'admin_board_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(BoardIdea $idea, Request $request): Response
    {
        if (!$this->pruefeToken($request, 'board-delete-' . $idea->getId())) {
            return $this->redirectToRoute('admin_board_index');
        }

        if ($this->moderator->delete($idea)) {
            $this->addFlash('success', $this->translator->trans('flash.board_deleted'));
        } else {
            // Eine veröffentlichte Idee wird abgelehnt, nicht gelöscht — andere
            // haben für sie gestimmt und das Team hat geantwortet.
            $this->addFlash('error', $this->translator->trans('flash.board_delete_published'));
        }

        return $this->redirectToRoute('admin_board_index');
    }

    private function pruefeToken(Request $request, string $id): bool
    {
        if ($this->isCsrfTokenValid($id, $request->request->getString('_token'))) {
            return true;
        }

        $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

        return false;
    }
}
