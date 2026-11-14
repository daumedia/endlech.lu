<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SavedList;
use App\Entity\User;
use App\Form\SavedListNameType;
use App\RateLimit\ActionLimiter;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use App\Repository\SavedListEntryRepository;
use App\Repository\SavedListRepository;
use App\Saved\SavedCards;
use App\Saved\SavedListResult;
use App\Saved\SavedListService;
use App\Usage\UsageEventCatalogue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Der Bereich „Gespeichert“ (Feature 17): Übersicht, Favoriten, eigene Listen, Rückfrage.
 *
 * ⚠ **Jede Liste nur über `findOneOwned($id, $konto)`** — es gibt keinen Weg über die Nummer
 * allein. Eine fremde Nummer findet nichts und endet in 404, **identisch** zu einer Nummer, die
 * es nicht gibt (AK-53, AK-54). Kein Voter, kein nachgelagerter Besitzvergleich, der sich
 * vergessen ließe.
 * ⚠ **Besitz vor Token** (BF-199, Muster `PasskeyController`): Ein falsches Token führt per
 * Weiterleitung zurück — für eine fremde Liste gäbe es dann eine Antwort, die ihre Existenz
 * verrät. Wer hier nichts verloren hat, bekommt 404, egal mit welchem Token.
 * ⚠ **Deckel `saved_change` nach `ActionLimiter`:** `isAllowed()` vor, `consume()` erst, wenn
 * tatsächlich etwas geändert wurde (BF-11, AK-58). Kein `return` dazwischen, der speichert (BF-118).
 *
 * Lesen ist ungedeckelt: Kein Weg lädt den Bestand — 12 Karten je Seite, höchstens 25 Listen.
 * Seite, Klemmen und Fokusziel des Kartenrasters rechnet `SavedCards` — derselbe Dienst, den das
 * Herz auf „Favoriten“ benutzt (AK-14).
 */
#[Route('/gespeichert')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class SavedController extends AbstractController
{
    /**
     * Listen- und Lokalnummer im Pfad: höchstens zehn Ziffern (BF-212). Deckt jede Nummer einer
     * `INT`-Spalte ab (bis 2 147 483 647) und bleibt weit unter `PHP_INT_MAX`. Mit `\d+` passte
     * `99999999999999999999` auf die Route und scheiterte am `int $id` — TypeError, HTTP 500
     * statt 404 wie jede andere unbekannte Nummer.
     */
    private const string NUMMER = '\d{1,10}';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SavedListService $listService,
        private readonly SavedListRepository $lists,
        private readonly SavedListEntryRepository $entries,
        private readonly FavoriteRepository $favorites,
        private readonly RestaurantRepository $restaurants,
        private readonly SavedCards $cards,
        #[Autowire(service: 'limiter.saved_change')]
        private readonly RateLimiterFactoryInterface $changeLimiter,
    ) {
    }

    #[Route('', name: 'app_saved_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->uebersicht($this->konto(), $this->neueListeFormular());
    }

    #[Route('/favoriten', name: 'app_saved_favorites', methods: ['GET'])]
    public function favorites(Request $request): Response
    {
        // Wie die Suche (BF-60, EC-16): Eine Seite hinter dem Ende ist keine leere Seite.
        $karten = $this->cards->page($this->konto(), null, max(1, $request->query->getInt('page', 1)))
            ?? throw $this->createNotFoundException();

        return $this->render('saved/favorites.html.twig', ['karten' => $karten]);
    }

    #[Route('/listen', name: 'app_saved_list_create', methods: ['POST'])]
    public function createList(Request $request): Response
    {
        $konto = $this->konto();
        $formular = $this->neueListeFormular();
        $formular->handleRequest($request);
        $zurueck = $this->generateUrl('app_saved_index');

        if (!$formular->isSubmitted()) {
            return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
        }

        if (self::tokenUngueltig($formular)) {
            return $this->ungueltigesToken($zurueck);
        }

        $deckel = ActionLimiter::for($this->changeLimiter, RestaurantSavedController::limiterKey($konto));
        if (!$deckel->isAllowed()) {
            return $this->zuVieleAenderungen($request, $deckel, $zurueck, false);
        }

        if (!$formular->isValid()) {
            return $this->uebersicht($konto, $formular);
        }

        $ergebnis = $this->listService->create($konto, (string) $formular->get('name')->getData());
        if ($ergebnis->isRejected() || null === $ergebnis->list) {
            // Dublette oder 25er-Grenze (AK-19, AK-20): am Feld, 422, nichts verbraucht.
            return $this->uebersicht($konto, $this->mitFehler($formular, $ergebnis));
        }

        if ($ergebnis->changed) {
            $deckel->consume();
        }

        $this->addFlash('success', $this->translator->trans('saved.flash.list_created', ['%list%' => $ergebnis->list->getName()]));
        // BF-171: erst nach gelungener Handlung zählen; ohne Name und Nummer (AK-64, AK-66).
        $this->addFlash('usage_event', UsageEventCatalogue::LIST_CREATED);

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }

    #[Route('/listen/{id}', name: 'app_saved_list_show', requirements: ['id' => self::NUMMER], methods: ['GET'])]
    public function showList(int $id, Request $request): Response
    {
        $konto = $this->konto();
        $liste = $this->eigeneListe($id, $konto);

        return $this->listenseite($konto, $liste, $this->umbenennenFormular($liste), max(1, $request->query->getInt('page', 1)));
    }

    #[Route('/listen/{id}/umbenennen', name: 'app_saved_list_rename', requirements: ['id' => self::NUMMER], methods: ['POST'])]
    public function renameList(int $id, Request $request): Response
    {
        $konto = $this->konto();
        $liste = $this->eigeneListe($id, $konto);
        $formular = $this->umbenennenFormular($liste);
        $formular->handleRequest($request);
        $zurueck = $this->generateUrl('app_saved_list_show', ['id' => $liste->getId()]);

        if (!$formular->isSubmitted()) {
            return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
        }

        if (self::tokenUngueltig($formular)) {
            return $this->ungueltigesToken($zurueck);
        }

        $deckel = ActionLimiter::for($this->changeLimiter, RestaurantSavedController::limiterKey($konto));
        if (!$deckel->isAllowed()) {
            return $this->zuVieleAenderungen($request, $deckel, $zurueck, false);
        }

        if (!$formular->isValid()) {
            return $this->listenseite($konto, $liste, $formular, 1);
        }

        $ergebnis = $this->listService->rename($liste, (string) $formular->get('name')->getData());
        if ($ergebnis->isRejected()) {
            return $this->listenseite($konto, $liste, $this->mitFehler($formular, $ergebnis), 1);
        }

        // Derselbe Name noch einmal ist keine Änderung und zählt nicht (AK-58).
        if ($ergebnis->changed) {
            $deckel->consume();
        }

        $this->addFlash('success', $this->translator->trans('saved.flash.list_renamed', ['%list%' => $liste->getName()]));

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }

    /**
     * Die Rückfrage ist eine eigene Seite, kein `onsubmit`-Dialog (Decision Log 8): Sie geht ohne
     * JavaScript, und ein Inline-Handler liefe nach dem Scharfschalten der CSP ohne Rückfrage durch.
     */
    #[Route('/listen/{id}/loeschen', name: 'app_saved_list_delete_confirm', requirements: ['id' => self::NUMMER], methods: ['GET'])]
    public function confirmDelete(int $id): Response
    {
        $liste = $this->eigeneListe($id, $this->konto());

        return $this->render('saved/delete_confirm.html.twig', [
            'liste' => $liste,
            'anzahl' => $this->entries->countByList($liste),
            'tokenId' => SavedCards::listToken($liste),
        ]);
    }

    #[Route('/listen/{id}/loeschen', name: 'app_saved_list_delete', requirements: ['id' => self::NUMMER], methods: ['POST'])]
    public function deleteList(int $id, Request $request): Response
    {
        $konto = $this->konto();
        $liste = $this->eigeneListe($id, $konto);
        $rueckfrage = $this->generateUrl('app_saved_list_delete_confirm', ['id' => $liste->getId()]);

        if (!$this->isCsrfTokenValid(SavedCards::listToken($liste), $request->request->getString('_token'))) {
            return $this->ungueltigesToken($rueckfrage);
        }

        $deckel = ActionLimiter::for($this->changeLimiter, RestaurantSavedController::limiterKey($konto));
        if (!$deckel->isAllowed()) {
            return $this->zuVieleAenderungen($request, $deckel, $rueckfrage, false);
        }

        // Der Name wird vorher gelesen: Nach dem Löschen gibt es die Liste nicht mehr.
        $name = $liste->getName();
        $this->listService->delete($liste);
        $deckel->consume();

        $this->addFlash('success', $this->translator->trans('saved.flash.list_deleted', ['%list%' => $name]));

        return $this->redirectToRoute('app_saved_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Ohne JavaScript: 303 zurück auf dieselbe Seite der Liste, Anker auf die nächste Karte.
     * Mit Turbo: das Kartenraster derselben Seite neu, das Fokusziel markiert (AK-26) — den
     * Fokus setzt der Stimulus-Controller `focus-after-stream`, nicht der Server. Seite klemmen
     * und Fokusziel rechnet `SavedCards::afterRemoval()`.
     */
    #[Route('/listen/{id}/lokale/{restaurant}/entfernen', name: 'app_saved_list_entry_remove', requirements: ['id' => self::NUMMER, 'restaurant' => self::NUMMER], methods: ['POST'])]
    public function removeEntry(int $id, int $restaurant, Request $request): Response
    {
        $konto = $this->konto();
        // EC-09: auf einem anderen Gerät gelöscht → 404 wie eine fremde Liste; nichts entsteht neu.
        $liste = $this->eigeneListe($id, $konto);
        $lokal = $this->restaurants->find($restaurant) ?? throw $this->createNotFoundException();
        // BF-213: vor jedem Rechnen und vor jeder Adresse auf das Raster begrenzt.
        $seite = $this->cards->clampPage($konto, $liste, $request->request->getInt('page', 1));

        if (!$this->isCsrfTokenValid(SavedCards::listToken($liste), $request->request->getString('_token'))) {
            return $this->ungueltigesToken($this->cards->address($liste, $seite));
        }

        $deckel = ActionLimiter::for($this->changeLimiter, RestaurantSavedController::limiterKey($konto));
        if (!$deckel->isAllowed()) {
            return $this->zuVieleAenderungen($request, $deckel, $this->cards->address($liste, $seite), true);
        }

        // Wo stand die Karte? Danach rückt die nächste an genau diese Stelle.
        $stelle = $this->cards->position($konto, $liste, $seite, $lokal);

        // Stand das Lokal schon nicht mehr darin (zweiter Tab, Doppelklick), ist nichts geändert:
        // kein Verbrauch, aber auch keine Fehlermeldung — der gewünschte Zustand besteht.
        if ($this->listService->removeEntry($liste, $lokal)) {
            $deckel->consume();
        }

        $karten = $this->cards->afterRemoval($konto, $liste, $seite, $stelle);
        $meldung = $this->translator->trans('saved.flash.entry_removed', [
            '%name%' => $lokal->getName(),
            '%list%' => $liste->getName(),
        ]);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('saved/cards.stream.html.twig', [
                'karten' => $karten,
                'meldung' => $meldung,
                'meldungArt' => 'success',
            ]);
        }

        $this->addFlash('success', $meldung);

        return $this->redirect(
            $this->cards->address($liste, (int) $karten['seite']).SavedCards::anchor($karten),
            Response::HTTP_SEE_OTHER,
        );
    }

    /**
     * Das zustandslose Projekttoken eines Formulartyps scheitert als Formularfehler an der
     * Wurzel. Er wird nicht angezeigt, sondern beantwortet wie bei den handgeschriebenen
     * Formularen — ein Wortlaut für AK-56.
     */
    private static function tokenUngueltig(FormInterface $formular): bool
    {
        foreach ($formular->getErrors() as $fehler) {
            if ($fehler instanceof FormError && $fehler->getCause() instanceof CsrfToken) {
                return true;
            }
        }

        return false;
    }

    private function konto(): User
    {
        $konto = $this->getUser();
        if (!$konto instanceof User) {
            // Unerreichbar hinter `access_control` und `#[IsGranted]` — zweite Schicht.
            throw $this->createAccessDeniedException();
        }

        return $konto;
    }

    /** Fremd und nicht vorhanden sind dieselbe Antwort, ohne Name und Zahl (AK-53). */
    private function eigeneListe(int $id, User $konto): SavedList
    {
        return $this->lists->findOneOwned($id, $konto) ?? throw $this->createNotFoundException();
    }

    private function neueListeFormular(): FormInterface
    {
        return $this->createForm(SavedListNameType::class, null, [
            'action' => $this->generateUrl('app_saved_list_create'),
        ]);
    }

    private function umbenennenFormular(SavedList $liste): FormInterface
    {
        return $this->createForm(SavedListNameType::class, ['name' => $liste->getName()], [
            'action' => $this->generateUrl('app_saved_list_rename', ['id' => $liste->getId()]),
            'name_label' => 'saved.list.rename_label',
        ]);
    }

    private function mitFehler(FormInterface $formular, SavedListResult $ergebnis): FormInterface
    {
        $formular->get('name')->addError(new FormError($this->translator->trans((string) $ergebnis->error, [], 'validators')));

        return $formular;
    }

    /** Ein abgeschicktes, ungültiges Formular rendert `AbstractController::render()` mit 422. */
    private function uebersicht(User $konto, FormInterface $formular): Response
    {
        return $this->render('saved/index.html.twig', [
            'favoritenZahl' => $this->favorites->countByUser($konto),
            'listen' => $this->lists->findByUser($konto),
            'zahlen' => $this->lists->countEntriesPerList($konto),
            'formular' => $formular,
        ]);
    }

    private function listenseite(User $konto, SavedList $liste, FormInterface $formular, int $seite): Response
    {
        $karten = $this->cards->page($konto, $liste, $seite) ?? throw $this->createNotFoundException();

        return $this->render('saved/list.html.twig', [
            'liste' => $liste,
            'formular' => $formular,
            'karten' => $karten,
        ]);
    }

    private function ungueltigesToken(string $zurueck): Response
    {
        $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }

    /**
     * Nur „Aus Liste entfernen“ antwortet mit einem Stream; Anlegen, Umbenennen und Löschen
     * folgen auch mit Turbo der Weiterleitung (design.md, „Wohin nach dem Absenden“).
     */
    private function zuVieleAenderungen(Request $request, ActionLimiter $deckel, string $zurueck, bool $mitStream): Response
    {
        $meldung = $this->translator->trans('saved.flash.rate_limited', [
            '%minutes%' => max(1, (int) ceil($deckel->retryAfter() / 60)),
        ]);

        if ($mitStream && TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('saved/cards.stream.html.twig', [
                'karten' => null,
                'meldung' => $meldung,
                'meldungArt' => 'error',
            ]);
        }

        $this->addFlash('error', $meldung);

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }
}
