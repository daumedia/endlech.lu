<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Verwaltung der eigenen Passkeys im Profil.
 *
 * Bewusst gewöhnliche Formulare: Anlegen braucht zwingend JavaScript (ohne
 * navigator.credentials gibt es keinen Passkey), Umbenennen und Löschen aber
 * nicht. Wer einen Passkey loswerden will, soll das nicht davon abhängig
 * machen müssen, ob ein Skript geladen hat.
 */
#[Route('/profile/passkeys')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PasskeyController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{id}/umbenennen', name: 'app_passkey_rename', methods: ['POST'])]
    public function rename(Request $request, WebauthnCredential $passkey): Response
    {
        $this->denyUnlessOwnedByCurrentUser($passkey);

        if (!$this->isCsrfTokenValid('rename-passkey-' . $passkey->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('app_profile');
        }

        $name = trim($request->request->getString('name'));

        if ($name === '') {
            $this->addFlash('error', $this->translator->trans('flash.passkey_name_empty'));

            return $this->redirectToRoute('app_profile');
        }

        $passkey->setName(mb_substr($name, 0, 100));
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.passkey_renamed'));

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{id}/loeschen', name: 'app_passkey_delete', methods: ['POST'])]
    public function delete(Request $request, WebauthnCredential $passkey): Response
    {
        $this->denyUnlessOwnedByCurrentUser($passkey);

        if (!$this->isCsrfTokenValid('delete-passkey-' . $passkey->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.invalid_csrf'));

            return $this->redirectToRoute('app_profile');
        }

        $this->entityManager->remove($passkey);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('flash.passkey_deleted'));

        return $this->redirectToRoute('app_profile');
    }

    /**
     * Ohne diese Prüfung liesse sich über eine geratene id ein fremder Passkey
     * umbenennen oder löschen – die id steht im Pfad und ist fortlaufend.
     *
     * Läuft bewusst VOR der CSRF-Prüfung. Wer nicht Eigentümer ist, hat hier
     * unabhängig von jedem Token nichts verloren, und die Antwort sagt dann
     * auch das (403) statt einer Weiterleitung, die nach einem abgelaufenen
     * Formular aussieht. Am Schutz ändert die Reihenfolge nichts: Ein Angriff
     * über eine fremde Seite zielt auf eine id des Opfers, kommt also durch
     * diese Prüfung hindurch und scheitert danach am Token.
     */
    private function denyUnlessOwnedByCurrentUser(WebauthnCredential $passkey): void
    {
        $user = $this->getUser();

        if (!$user instanceof User || $passkey->getUser() !== $user) {
            throw $this->createAccessDeniedException('Dieser Passkey gehört einem anderen Konto.');
        }
    }
}
