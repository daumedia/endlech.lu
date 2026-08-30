<?php

namespace App\Controller;

use App\Board\AuthorName;
use App\Board\BoardVoteService;
use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Form\BoardIdeaType;
use App\RateLimit\ActionLimiter;
use App\Repository\BoardIdeaRepository;
use App\Repository\BoardVoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Das öffentliche Community-Board (Feature 06).
 *
 * ⚠ **Die Routenreihenfolge ist wesentlich:** `/neu` und `/eingereicht` stehen
 * vor `/{id}-{slug}`, sonst behandelt die Einzelansicht „neu" als Kennung.
 * Zusätzlich abgesichert über `requirements: ['id' => '\d+']`.
 */
#[Route('/community/ideen')]
final class BoardController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly BoardIdeaRepository $ideas,
        // ⚠ Beide Deckel zählen am KONTO, nicht an der IP: Beide Wege setzen
        // eine bestätigte Anmeldung voraus, und dort wechselt der Angreifer die
        // IP mühelos, das Konto nicht (Projektkonvention, wie `password_change`).
        #[Autowire(service: 'limiter.board_submit')]
        private readonly RateLimiterFactoryInterface $submitLimiter,
        #[Autowire(service: 'limiter.board_vote')]
        private readonly RateLimiterFactoryInterface $voteLimiter,
    ) {
    }

    #[Route('', name: 'app_board_index', methods: ['GET'])]
    public function index(Request $request, BoardVoteRepository $votes, AuthorName $authorName): Response
    {
        $sort = 'newest' === $request->query->get('sort') ? 'newest' : 'votes';
        $page = max(1, $request->query->getInt('page', 1));
        $status = BoardIdeaStatus::tryFrom((string) $request->query->get('status', ''));

        // Ein Filter auf „umgesetzt" ist wirkungslos: Diese Ideen stehen in
        // ihrem eigenen Abschnitt (AK-75), nicht in der Hauptliste.
        if (BoardIdeaStatus::DONE === $status) {
            $status = null;
        }

        $paginator = $this->ideas->findPublishedPaginated($sort, $page, $status);
        $liste = iterator_to_array($paginator);
        $gesamt = \count($paginator);
        $letzteSeite = max(1, (int) ceil($gesamt / BoardIdeaRepository::PER_PAGE));

        $umgesetzt = $this->ideas->findPublishedDone();

        // Die Zahlen für BEIDE Abschnitte in einer Abfrage. Ohne die
        // umgesetzten Ideen zeigte der untere Abschnitt überall eine 0.
        $ids = array_map(
            static fn (BoardIdea $i): int => (int) $i->getId(),
            array_merge($liste, $umgesetzt),
        );

        return $this->render('board/index.html.twig', [
            'ideas' => $liste,
            'done' => $umgesetzt,
            'voteCounts' => $this->ideas->countVotesFor($ids),
            'votedIds' => $this->votedIds($votes, $ids),
            'authorName' => $authorName,
            'sort' => $sort,
            'status' => $status,
            'currentPage' => $page,
            'lastPage' => $letzteSeite,
            'total' => $gesamt,
        ]);
    }

    #[Route('/neu', name: 'app_board_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            $this->addFlash('error', $this->translator->trans('flash.board_verify_first'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $idea = new BoardIdea();
        $form = $this->createForm(BoardIdeaType::class, $idea);
        $form->handleRequest($request);

        $limiter = ActionLimiter::for($this->submitLimiter, $user->getUserIdentifier());

        if ($form->isSubmitted() && !$limiter->isAllowed()) {
            // ⚠ BF-101: Die Meldung nennt die Wartezeit. „Bitte später erneut"
            // ohne Zahl lässt den Nutzer raten, ob er in einer Minute oder in
            // einer Stunde wiederkommen soll. Muster wie `AccessibilityController`.
            $this->addFlash('error', $this->translator->trans(
                'flash.board_rate_limited',
                ['%minutes%' => $this->wartezeitInMinuten($limiter)],
            ));

            return $this->render('board/new.html.twig', [
                'form' => $form,
            ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Fallenfeld: dieselbe Antwort wie im Gutfall, aber ohne Datensatz
            // und ohne Mail. Ein Validierungsfehler verriete dem Bot, welches
            // Feld die Falle ist (AK-17).
            if ('' !== trim((string) $form->get('website')->getData())) {
                return $this->redirectToRoute('app_board_thanks');
            }

            // ⚠ Erst hier verbrauchen (BF-11): Wer sich vertippt, soll nicht
            // gedeckelt werden — der Deckel zählt stattgefundene Einreichungen.
            $limiter->consume();

            $idea->setSubmittedBy($user);
            $idea->setLocale($request->getLocale());
            $idea->setSlug((new AsciiSlugger())->slug($idea->getTitle())->lower()->toString());

            $em->persist($idea);
            $em->flush();

            return $this->redirectToRoute('app_board_thanks');
        }

        return $this->render('board/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/eingereicht', name: 'app_board_thanks', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function thanks(): Response
    {
        return $this->render('board/thanks.html.twig');
    }

    #[Route('/{id}-{slug}', name: 'app_board_show', requirements: ['id' => '\d+', 'slug' => '[^/]*'], methods: ['GET'])]
    public function show(BoardIdea $idea, BoardVoteRepository $votes, AuthorName $authorName): Response
    {
        // Zusammengeführte Dubletten führen auf das Original (AK-35).
        if (null !== $original = $idea->getDuplicateOf()) {
            return $this->redirectToRoute('app_board_show', [
                'id' => $original->getId(),
                'slug' => $original->getSlug(),
            ]);
        }

        // ⚠ Fremde wartende Idee → 404, nicht 403. Ein 403 mit Titel in der
        // Fehlerseite verriete Existenz und Inhalt (AK-18, AK-56).
        if (!$idea->isPublished() && $this->getUser() !== $idea->getSubmittedBy()) {
            throw $this->createNotFoundException();
        }

        $id = (int) $idea->getId();

        return $this->render('board/show.html.twig', [
            'idea' => $idea,
            'voteCount' => $this->ideas->countVotesForOne($idea),
            'hasVoted' => \in_array($id, $this->votedIds($votes, [$id]), true),
            'authorName' => $authorName,
        ]);
    }

    #[Route('/{id}/zustimmen', name: 'app_board_vote', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function vote(BoardIdea $idea, Request $request, BoardVoteService $voting): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('board-vote-' . $idea->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->zurueckZurIdee($idea);
        }

        // Nur öffentliche Ideen lassen sich unterstützen — sonst wäre die Zahl
        // an einer wartenden Idee ein Kanal an der Moderation vorbei.
        if (!$idea->isPublished() || !$user->isVerified()) {
            throw $this->createNotFoundException();
        }

        $limiter = ActionLimiter::for($this->voteLimiter, $user->getUserIdentifier());

        if (!$limiter->isAllowed()) {
            // ⚠ BF-102: Eigener Schlüssel. Vorher stand hier derselbe Text wie
            // beim Einreichen — „Zu viele Einreichungen", während der Nutzer
            // zugestimmt hat. Ein Schlüssel für zwei Vorgänge benennt zwangsläufig
            // einen davon falsch.
            $this->addFlash('error', $this->translator->trans(
                'flash.board_vote_rate_limited',
                ['%minutes%' => $this->wartezeitInMinuten($limiter)],
            ));

            return $this->zurueckZurIdee($idea);
        }

        // ⚠ Das Konto kommt aus der Sitzung. Der Endpunkt nimmt keine
        // Konto-Kennung entgegen — ein Feld, das es nicht gibt, lässt sich
        // nicht unterschieben (AK-58).
        $voting->toggle($idea, $user);
        $limiter->consume();

        return $this->zurueckZurIdee($idea);
    }

    #[Route('/{id}/zurueckziehen', name: 'app_board_withdraw', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function withdraw(BoardIdea $idea, Request $request, EntityManagerInterface $em): Response
    {
        // ⚠ Die Besitzprüfung steht VOR der CSRF-Prüfung: Wer nicht Eigentümer
        // ist, hat hier unabhängig vom Token nichts verloren (Muster
        // PasskeyController). Am Schutz ändert das nichts — ein Angriff über
        // eine fremde Seite zielt auf eine ID des Opfers und scheitert danach
        // am Token.
        if ($this->getUser() !== $idea->getSubmittedBy()) {
            throw $this->createAccessDeniedException();
        }

        // ⚠ Nach der Veröffentlichung gibt es keinen Rückweg (AK-77): Andere
        // haben zugestimmt, das Team hat geantwortet. Serverseitig geprüft, denn
        // „es gibt keinen Knopf" ist keine Regel.
        if ($idea->isPublished()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('board-withdraw-' . $idea->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('app_board_index');
        }

        $em->remove($idea);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('flash.board_withdrawn'));

        return $this->redirectToRoute('app_board_index');
    }

    /**
     * Verbleibende Wartezeit in vollen Minuten, mindestens 1 (BF-101).
     *
     * `retryAfter()` liefert Sekunden; „in 0 Minuten erneut" wäre keine Auskunft,
     * deshalb der Mindestwert. Dasselbe Muster wie in `AccessibilityController`.
     */
    private function wartezeitInMinuten(ActionLimiter $limiter): int
    {
        return max(1, (int) ceil($limiter->retryAfter() / 60));
    }

    private function zurueckZurIdee(BoardIdea $idea): Response
    {
        return $this->redirectToRoute('app_board_show', [
            'id' => $idea->getId(),
            'slug' => $idea->getSlug(),
        ]);
    }

    /**
     * @param int[] $ids
     *
     * @return int[]
     */
    private function votedIds(BoardVoteRepository $votes, array $ids): array
    {
        $user = $this->getUser();

        return $user instanceof User ? $votes->findVotedIdeaIds($user, $ids) : [];
    }
}
