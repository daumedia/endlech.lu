<?php

namespace App\Controller;

use App\Entity\FinanceEntry;
use App\Enum\FinanceType;
use App\Form\FinanceEntryType;
use App\Open\MetricSnapshotService;
use App\Open\OpenStatsService;
use App\Repository\FinanceEntryRepository;
use App\Repository\MetricSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Pflege der Finanzdaten hinter /open.
 *
 * Jede schreibende Aktion wirft den Kennzahlen-Cache weg. Ohne das stünde nach
 * dem Speichern bis zu eine Stunde lang der alte Betrag auf der öffentlichen
 * Seite – die verlässlichste Art, jemanden zu einem zweiten, korrigierenden
 * Eintrag zu verleiten.
 */
#[Route('/admin/finanzen')]
#[IsGranted('ROLE_ADMIN')]
final class AdminFinanceController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly OpenStatsService $stats,
    ) {
    }

    #[Route('', name: 'admin_finance_index')]
    public function index(
        Request $request,
        FinanceEntryRepository $repository,
        MetricSnapshotRepository $snapshots,
    ): Response {
        $type = FinanceType::tryFrom($request->query->getString('type'));

        return $this->render('admin/finance/index.html.twig', [
            'entries' => $repository->findForAdmin($type),
            'activeType' => $type,
            'types' => FinanceType::cases(),
            'totalExpenses' => $repository->sumByType(FinanceType::EXPENSE),
            'totalIncome' => $repository->sumByType(FinanceType::INCOME),
            'lastUpdatedAt' => $repository->findLastUpdatedAt(),
            'latestSnapshot' => $snapshots->findLatest(),
        ]);
    }

    #[Route('/neu', name: 'admin_finance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entry = new FinanceEntry();
        $form = $this->createForm(FinanceEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entry);
            $entityManager->flush();
            $this->stats->invalidate();

            $this->addFlash('success', $this->translator->trans('flash.finance_created'));

            return $this->redirectToRoute('admin_finance_index');
        }

        return $this->render('admin/finance/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/bearbeiten', name: 'admin_finance_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(FinanceEntry $entry, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FinanceEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->stats->invalidate();

            $this->addFlash('success', $this->translator->trans('flash.finance_updated'));

            return $this->redirectToRoute('admin_finance_index');
        }

        return $this->render('admin/finance/edit.html.twig', [
            'form' => $form,
            'entry' => $entry,
        ]);
    }

    #[Route('/{id}/loeschen', name: 'admin_finance_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(FinanceEntry $entry, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-finance-' . $entry->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($entry);
            $entityManager->flush();
            $this->stats->invalidate();

            $this->addFlash('success', $this->translator->trans('flash.finance_deleted'));
        } else {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));
        }

        return $this->redirectToRoute('admin_finance_index');
    }

    /**
     * Monats-Snapshot von Hand auslösen.
     *
     * Der Zeitplan in App\Schedule braucht einen Messenger-Worker, den es auf
     * Production nicht gibt; der Cron-Eintrag kann ausfallen. Ohne diesen
     * Knopf bliebe die Historie in beiden Fällen unbemerkt leer – und ein
     * Verlauf lässt sich später nicht rückwirkend erzeugen.
     */
    #[Route('/snapshot', name: 'admin_finance_snapshot', methods: ['POST'])]
    public function snapshot(Request $request, MetricSnapshotService $snapshots): Response
    {
        if (!$this->isCsrfTokenValid('metric-snapshot', $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('admin_finance_index');
        }

        $result = $snapshots->capture(null, true);

        $this->addFlash('success', $this->translator->trans('flash.snapshot_captured', [
            '%month%' => $result['snapshot']->getMonthKey(),
        ]));

        return $this->redirectToRoute('admin_finance_index');
    }
}
