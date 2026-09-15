<?php

namespace App\Controller;

use App\Entity\RestaurantSuggestion;
use App\Form\RestaurantSuggestionType;
use App\RateLimit\ActionLimiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/community')]
final class CommunityController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        // ⚠ BF-50: Am KONTO gezählt, nicht an der IP — der Weg setzt eine
        // bestätigte Anmeldung voraus. Er füllt dieselbe Moderationsschlange wie
        // der API-Weg (BF-30), und die abzuarbeiten ist Handarbeit; darum teilen
        // sich beide denselben Zähler.
        #[Autowire(service: 'limiter.suggestion_submit')]
        private readonly RateLimiterFactoryInterface $suggestionLimiter,
    ) {
    }

    #[Route('/suggest', name: 'community_vorschlagen', methods: ['GET', 'POST'])]
    public function vorschlagen(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Gäste sehen zuerst, worum es geht (2026-09-15, ersetzt B11 AK-01). Vorher schickte
        // `#[IsGranted('ROLE_USER')]` jeden Gast ohne ein Wort zur Anmeldung — wer auf
        // „Restaurant vorschlagen" klickte, wusste danach weder, was gefragt wird, noch warum
        // er ein Konto braucht.
        //
        // ⚠ Nur das ANSEHEN ist offen. Ein POST ohne Konto endet weiterhin an der Anmeldung:
        // Der Weg füllt eine Moderationsschlange, die von Hand abgearbeitet wird, und sein
        // Limiter zählt am Konto (BF-50).
        if (!$this->isGranted('ROLE_USER')) {
            if (!$request->isMethod('GET')) {
                throw $this->createAccessDeniedException();
            }

            // Nach Anmeldung (Passwort oder Passkey) geht es hier weiter, nicht auf der Startseite.
            $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('community_vorschlagen'));

            return $this->render('community/vorschlagen_gast.html.twig');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            $this->addFlash('error', $this->translator->trans('flash.suggest_verify_first'));

            return $this->redirectToRoute('app_verify_notice');
        }

        $suggestion = new RestaurantSuggestion();
        $form = $this->createForm(RestaurantSuggestionType::class, $suggestion);
        $form->handleRequest($request);

        $limiter = ActionLimiter::for($this->suggestionLimiter, $user->getUserIdentifier());

        if ($form->isSubmitted() && !$limiter->isAllowed()) {
            $this->addFlash('error', $this->translator->trans('flash.suggest_rate_limited'));

            return $this->render('community/vorschlagen.html.twig', [
                'form' => $form,
            ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Erst hier verbrauchen (BF-11): ein unvollständiger Assistent mit zwölf
            // Pflichtfragen wird oft mehrfach abgeschickt, bis alles stimmt.
            $limiter->consume();

            $suggestion->setSuggestedBy($user);
            $suggestion->setLocale($request->getLocale());
            $entityManager->persist($suggestion);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.suggest_success'));

            return $this->redirectToRoute('community_danke');
        }

        return $this->render('community/vorschlagen.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/thanks', name: 'community_danke')]
    public function danke(): Response
    {
        return $this->render('community/danke.html.twig');
    }
}
