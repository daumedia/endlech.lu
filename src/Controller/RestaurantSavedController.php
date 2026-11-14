<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\User;
use App\RateLimit\ActionLimiter;
use App\Repository\SavedListRepository;
use App\Saved\FavoriteService;
use App\Saved\PendingFavorite;
use App\Saved\RestaurantNoteService;
use App\Saved\SavedCards;
use App\Saved\SavedListService;
use App\Service\RestaurantShowView;
use App\Usage\UsageEventCatalogue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Handlungen an einem Lokal (Feature 17): das Herz, „In Listen“, die Notiz.
 *
 * Alles sind gewöhnliche Formulare, die ohne JavaScript funktionieren: Absenden, 303 zurück auf
 * die Seite, auf der das Herz stand (`ziel`, geprüft in `PendingFavorite`). Mit Turbo bekommt das
 * Herz stattdessen einen Turbo-Stream, der nur das Herz austauscht und die Meldung ansagt — die
 * Seite springt nicht (AK-01).
 *
 * ⚠ **Das Konto kommt aus der Sitzung.** Kein Formular nimmt eine Kontokennung entgegen.
 * ⚠ **Deckel `saved_change` nach `ActionLimiter`:** `isAllowed()` vor, `consume()` erst, wenn
 * tatsächlich gespeichert wurde (BF-11, AK-58). Kein `return` dazwischen, der speichert (BF-118).
 */
final class RestaurantSavedController extends AbstractController
{
    use TargetPathTrait;

    /** Token je Handlungsart, nicht je Lokal — auch Gäste rendern Herzen (design.md, Entscheidung 6). */
    public const string HEART_TOKEN = 'favorit';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly FavoriteService $favorites,
        private readonly PendingFavorite $pending,
        private readonly SavedListService $savedLists,
        private readonly SavedListRepository $lists,
        private readonly RestaurantNoteService $notes,
        private readonly RestaurantShowView $showView,
        private readonly SavedCards $cards,
        #[Autowire(service: 'limiter.saved_change')]
        private readonly RateLimiterFactoryInterface $changeLimiter,
    ) {
    }

    #[Route('/restaurants/{id}/merken', name: 'app_favorite_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(Restaurant $restaurant, Request $request): Response
    {
        $ziel = $this->pending->safeTarget($request->request->getString('ziel'), $restaurant, $request->getLocale());

        if (!$this->isCsrfTokenValid(self::HEART_TOKEN, $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirect($ziel.PendingFavorite::anchor($restaurant), Response::HTTP_SEE_OTHER);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            // AK-08: Gast — nichts speichern, Wunsch in die eigene Sitzung, nach der Anmeldung
            // zurück auf dieselbe Seite und Karte. Eingelöst wird im `PendingFavoriteSubscriber`.
            $this->pending->remember($request->getSession(), $restaurant);
            $this->saveTargetPath($request->getSession(), 'main', $ziel.PendingFavorite::anchor($restaurant));

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $limiter = ActionLimiter::for($this->changeLimiter, self::limiterKey($user));
        if (!$limiter->isAllowed()) {
            return $this->rateLimited($request, $restaurant, $limiter, $ziel, $this->favorites->isFavorite($user, $restaurant));
        }

        $geaendert = $this->favorites->add($user, $restaurant);
        if ($geaendert) {
            $limiter->consume();
        }

        return $this->heartResponse(
            $request,
            $restaurant,
            true,
            $ziel,
            $this->translator->trans('saved.flash.added', ['%name%' => $restaurant->getName()]),
            $geaendert ? UsageEventCatalogue::HEART_SET : null,
        );
    }

    /**
     * Herz entfernen. Stand es auf „Favoriten“ (geprüftes `ziel`), nimmt es die Karte mit
     * (AK-14): mit Turbo das Kartenraster derselben Seite neu samt Fokusziel, ohne JavaScript
     * 303 auf dieselbe Seite mit Anker auf die nächste Karte. Überall sonst — Suche, Detailseite,
     * eine eigene Liste (EC-15) — bleibt die Karte stehen, und nur das Herz wechselt.
     */
    #[Route('/restaurants/{id}/nicht-mehr-merken', name: 'app_favorite_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function remove(Restaurant $restaurant, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $ziel = $this->pending->safeTarget($request->request->getString('ziel'), $restaurant, $request->getLocale());

        if (!$this->isCsrfTokenValid(self::HEART_TOKEN, $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirect($ziel.PendingFavorite::anchor($restaurant), Response::HTTP_SEE_OTHER);
        }

        $limiter = ActionLimiter::for($this->changeLimiter, self::limiterKey($user));
        if (!$limiter->isAllowed()) {
            return $this->rateLimited($request, $restaurant, $limiter, $ziel, $this->favorites->isFavorite($user, $restaurant));
        }

        // Auf „Favoriten“: die Stelle der Karte VOR dem Entfernen — danach rückt die nächste dorthin.
        $favoritenSeite = $this->cards->favoritesPage($ziel);
        $stelle = null === $favoritenSeite ? null : $this->cards->position($user, null, $favoritenSeite, $restaurant);

        if ($this->favorites->remove($user, $restaurant)) {
            $limiter->consume();
        }

        $meldung = $this->translator->trans('saved.flash.removed', ['%name%' => $restaurant->getName()]);

        if (null !== $favoritenSeite) {
            return $this->favoritesResponse($request, $user, $favoritenSeite, $stelle, $meldung);
        }

        return $this->heartResponse($request, $restaurant, false, $ziel, $meldung, null);
    }

    /**
     * „In Listen“ (AK-21 bis AK-24, AK-31, AK-54): Häkchen übernehmen, optional neue Liste mit
     * dem Lokal anlegen — alles oder nichts (AK-23, `SavedListService::setMemberships`).
     */
    #[Route('/restaurants/{id}/listen', name: 'app_restaurant_lists_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function saveLists(Restaurant $restaurant, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $eigene = $this->lists->findByUser($user);

        // ⚠ AK-54: Die übermittelten Listennummern werden VOR allem anderen gegen die eigenen
        // geprüft — auch vor dem Token (Besitz vor Token, BF-199). Eine fremde oder unbekannte
        // Nummer endet in 404, identisch, und nichts wird geändert. Ein bloßer Formularfehler
        // („ungültige Auswahl“) wäre 422 und spräche über die fremde Liste.
        $eigeneIds = array_map(static fn ($liste): int => (int) $liste->getId(), $eigene);
        $roh = $request->request->all('restaurant_lists')['lists'] ?? [];
        foreach (\is_array($roh) ? $roh : [$roh] as $nummer) {
            if (!\is_string($nummer) || 1 !== preg_match('/^\d{1,10}$/', $nummer) || !\in_array((int) $nummer, $eigeneIds, true)) {
                throw $this->createNotFoundException();
            }
        }

        $form = $this->showView->listsForm($user, $restaurant);
        $form->handleRequest($request);
        $zurueck = $this->generateUrl('app_restaurant_show', ['id' => $restaurant->getId()]).'#in-listen';

        if (!$form->isSubmitted()) {
            return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
        }
        if (self::csrfFailed($form)) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
        }
        if (!$form->isValid()) {
            return $this->renderDetail($restaurant, $user, listsForm: $form);
        }

        $limiter = ActionLimiter::for($this->changeLimiter, self::limiterKey($user));
        if (!$limiter->isAllowed()) {
            return $this->limitReached($limiter, $zurueck);
        }

        /** @var array{lists: list<int|string>, newList: string} $daten */
        $daten = $form->getData();
        $ergebnis = $this->savedLists->setMemberships($user, $restaurant, array_map('intval', $daten['lists']), $daten['newList']);

        if ($ergebnis->isRejected()) {
            $form->get('newList')->addError(new FormError($this->translator->trans((string) $ergebnis->error, [], 'validators')));

            return $this->renderDetail($restaurant, $user, listsForm: $form);
        }

        if ($ergebnis->changed) {
            $limiter->consume();
        }
        $this->addFlash('success', $this->translator->trans('saved.flash.lists_saved'));
        // AK-51: Mit Turbo geht der Anker `#in-listen` der Weiterleitung verloren — die Seite stünde
        // oben. Die Markierung rollt zurück zum Abschnitt (`scroll_to_controller`).
        $this->addFlash('scroll_to', 'in-listen');
        if (null !== $ergebnis->list) {
            $this->addFlash('usage_event', UsageEventCatalogue::LIST_CREATED);
        }

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }

    /**
     * „Deine Notiz“ (AK-32 bis AK-37): speichern, überschreiben, leeren.
     *
     * ⚠ Der Notiztext geht in keine Meldung, kein Protokoll, keine Messung (AK-64).
     */
    #[Route('/restaurants/{id}/notiz', name: 'app_restaurant_note_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function saveNote(Restaurant $restaurant, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->showView->noteForm($user, $restaurant);
        $form->handleRequest($request);
        $zurueck = $this->generateUrl('app_restaurant_show', ['id' => $restaurant->getId()]).'#notiz';

        if (!$form->isSubmitted()) {
            return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
        }
        if (self::csrfFailed($form)) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
        }
        if (!$form->isValid()) {
            return $this->renderDetail($restaurant, $user, noteForm: $form);
        }

        $limiter = ActionLimiter::for($this->changeLimiter, self::limiterKey($user));
        if (!$limiter->isAllowed()) {
            return $this->limitReached($limiter, $zurueck);
        }

        /** @var array{text: string} $daten */
        $daten = $form->getData();
        $ergebnis = $this->notes->save($user, $restaurant, $daten['text']);

        if (RestaurantNoteService::TOO_LONG === $ergebnis) {
            $form->get('text')->addError(new FormError($this->translator->trans(RestaurantNoteService::TOO_LONG, [], 'validators')));

            return $this->renderDetail($restaurant, $user, noteForm: $form);
        }

        if (RestaurantNoteService::UNCHANGED !== $ergebnis) {
            $limiter->consume();
        }
        $leer = RestaurantNoteService::DELETED === $ergebnis
            || (RestaurantNoteService::UNCHANGED === $ergebnis && null === $this->notes->textFor($user, $restaurant));
        $this->addFlash('success', $this->translator->trans($leer ? 'saved.flash.note_deleted' : 'saved.flash.note_saved'));
        $this->addFlash('scroll_to', 'notiz');

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }

    /**
     * Der Schlüssel des Deckels: die Kontonummer, nicht die E-Mail — sie bleibt beim
     * Adresswechsel gleich (design.md, Technische Entscheidung 9).
     */
    public static function limiterKey(User $user): string
    {
        return 'konto-'.$user->getId();
    }

    private function heartResponse(
        Request $request,
        Restaurant $restaurant,
        bool $isFavorite,
        string $ziel,
        string $meldung,
        ?string $usageEvent,
    ): Response {
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('saved/favorite.stream.html.twig', [
                'restaurant' => $restaurant,
                'isFavorite' => $isFavorite,
                'ziel' => $ziel,
                'meldung' => $meldung,
                'meldungArt' => 'success',
                'usageEvent' => $usageEvent,
            ]);
        }

        $this->addFlash('success', $meldung);
        if (null !== $usageEvent) {
            // BF-171: erst nach gelungener Handlung zählen — als unsichtbarer Auslöser auf der Folgeseite.
            $this->addFlash('usage_event', $usageEvent);
        }

        return $this->redirect($ziel.PendingFavorite::anchor($restaurant), Response::HTTP_SEE_OTHER);
    }

    /**
     * Antwort auf das Herz auf „Favoriten“ — dieselbe Vorlage und dasselbe Fokusziel wie
     * „Aus Liste entfernen“ (`SavedCards::afterRemoval`). Kein Messauslöser: Entfernen zählt
     * nicht (AK-66).
     */
    private function favoritesResponse(Request $request, User $user, int $seite, ?int $stelle, string $meldung): Response
    {
        $karten = $this->cards->afterRemoval($user, null, $seite, $stelle);

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
            $this->cards->address(null, (int) $karten['seite']).SavedCards::anchor($karten),
            Response::HTTP_SEE_OTHER,
        );
    }

    private function rateLimited(Request $request, Restaurant $restaurant, ActionLimiter $limiter, string $ziel, bool $isFavorite): Response
    {
        $meldung = $this->translator->trans('saved.flash.rate_limited', [
            '%minutes%' => max(1, (int) ceil($limiter->retryAfter() / 60)),
        ]);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('saved/favorite.stream.html.twig', [
                'restaurant' => $restaurant,
                'isFavorite' => $isFavorite,
                'ziel' => $ziel,
                'meldung' => $meldung,
                'meldungArt' => 'error',
                'usageEvent' => null,
            ]);
        }

        $this->addFlash('error', $meldung);

        return $this->redirect($ziel.PendingFavorite::anchor($restaurant), Response::HTTP_SEE_OTHER);
    }

    /**
     * Die Detailseite mit dem abgewiesenen Formular — `render()` setzt für ein abgeschicktes,
     * ungültiges Formular selbst 422 (Turbo rendert es an Ort und Stelle).
     */
    private function renderDetail(Restaurant $restaurant, User $user, ?FormInterface $listsForm = null, ?FormInterface $noteForm = null): Response
    {
        return $this->render(
            'restaurant/show.html.twig',
            $this->showView->variables($restaurant, $user, $listsForm, $noteForm),
        );
    }

    private function limitReached(ActionLimiter $limiter, string $zurueck): Response
    {
        $this->addFlash('error', $this->translator->trans('saved.flash.rate_limited', [
            '%minutes%' => max(1, (int) ceil($limiter->retryAfter() / 60)),
        ]));

        return $this->redirect($zurueck, Response::HTTP_SEE_OTHER);
    }

    /**
     * Ein Formulartyp meldet ein ungültiges Token als Fehler an der Wurzel. Dieses Feature
     * antwortet darauf wie bei den handgeschriebenen Formularen mit einer Meldung (AK-56).
     */
    private static function csrfFailed(FormInterface $form): bool
    {
        foreach ($form->getErrors() as $fehler) {
            if ($fehler->getCause() instanceof CsrfToken) {
                return true;
            }
        }

        return false;
    }
}
