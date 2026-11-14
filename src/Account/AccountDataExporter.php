<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\BoardIdeaRepository;
use App\Repository\BoardVoteRepository;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantNoteRepository;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use App\Repository\SavedListEntryRepository;
use App\Repository\SavedListRepository;

/**
 * Stellt alles zusammen, was zu einem Konto gespeichert ist (Art. 20 DSGVO).
 *
 * ⚠ **Was NICHT hineingehört, ist die wichtigere Hälfte:** kein Passwort-Hash,
 * keine Token, keine fremden Datensätze. Ein Export, der den Hash mitliefert,
 * macht aus einem Auskunftsrecht ein Angriffswerkzeug — und zwar eines, das der
 * Betroffene selbst in ein unverschlüsseltes Postfach legt.
 *
 * Die Zusammenstellung liegt in einer eigenen Klasse und nicht im Controller,
 * damit sie prüfbar ist: Ein Test kann gegen die Struktur laufen, ohne eine
 * HTTP-Anfrage zu bauen — und genau der fängt es ab, wenn jemand später ein Feld
 * ergänzt, das nicht nach draußen gehört.
 */
final readonly class AccountDataExporter
{
    public function __construct(
        private RestaurantRepository $restaurants,
        private RestaurantSuggestionRepository $suggestions,
        private BoardIdeaRepository $boardIdeas,
        private BoardVoteRepository $boardVotes,
        private AppWaitlistEntryRepository $appWaitlist,
        private FavoriteRepository $favorites,
        private SavedListRepository $savedLists,
        private SavedListEntryRepository $savedListEntries,
        private RestaurantNoteRepository $notes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        // Feature 08 / AK-51: Die App-Vormerkung hängt an der Adresse, nicht am
        // Konto — es gibt keine Beziehung, über die sie sonst gefunden würde.
        $appEntry = $this->appWaitlist->findOneByEmail($user->getEmail());

        return [
            'exportedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'format' => 'endlech.lu account export v1',
            'account' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'verified' => $user->isVerified(),
                'registeredAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'avatar' => $user->getAvatarUrl(),
                'pendingEmail' => $user->getPendingEmail(),
                // Feature 04 / AK-44: Der Export sagt, ob der Werbung zugestimmt
                // wurde und wann. Art. 7 Abs. 1 DSGVO verlangt, die Einwilligung
                // nachweisen zu können – dann muss auch der Betroffene sie
                // einsehen können, sonst ist der Nachweis einseitig.
                'marketingConsent' => $user->hasMarketingConsent(),
                'marketingConsentAt' => $user->getMarketingConsentAt()?->format(\DateTimeInterface::ATOM),
                // Feature 12, AK-62: die beiden Angaben zur Einführung — ob sie vorgesehen
                // ist und wann sie zum ersten Mal angezeigt wurde. Mehr speichert das
                // Feature nicht (AK-53).
                'onboardingDue' => $user->isOnboardingDue(),
                'onboardingShownAt' => $user->getOnboardingShownAt()?->format(\DateTimeInterface::ATOM),
            ],
            'passkeys' => array_map(
                static fn ($passkey) => [
                    'name' => $passkey->getName(),
                    'createdAt' => $passkey->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'lastUsedAt' => $passkey->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
                ],
                $user->getPasskeys()->toArray(),
            ),
            'publishedRestaurants' => array_map(
                static fn ($restaurant) => [
                    'id' => $restaurant->getId(),
                    'name' => $restaurant->getName(),
                    'city' => $restaurant->getCity(),
                    'verified' => $restaurant->isVerified(),
                    'submittedAt' => $restaurant->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->restaurants->findBySubmitter($user),
            ),
            'suggestions' => array_map(
                static fn ($suggestion) => [
                    'id' => $suggestion->getId(),
                    'name' => $suggestion->getName(),
                    'city' => $suggestion->getCity(),
                    'status' => $suggestion->getStatus(),
                    'adminNote' => $suggestion->getAdminNote(),
                    'submittedAt' => $suggestion->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->suggestions->findBySuggester($user),
            ),
            // Feature 06 / AK-67: Eingereichte Ideen und abgegebene Zustimmungen.
            // Beides sind Handlungen dieses Kontos und gehören damit in die
            // Auskunft nach Art. 15/20 DSGVO.
            'boardIdeas' => array_map(
                static fn ($idea) => [
                    'id' => $idea->getId(),
                    'title' => $idea->getTitle(),
                    'description' => $idea->getDescription(),
                    'status' => $idea->getStatus()->value,
                    'published' => $idea->isPublished(),
                    'teamResponse' => $idea->getTeamResponse(),
                    'submittedAt' => $idea->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->boardIdeas->findBySubmitter($user),
            ),
            'boardVotes' => array_map(
                static fn ($vote) => [
                    'ideaId' => $vote->getIdea()?->getId(),
                    'ideaTitle' => $vote->getIdea()?->getTitle(),
                    'votedAt' => $vote->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->boardVotes->findBy(['user' => $user], ['createdAt' => 'DESC']),
            ),
            // Feature 08 / AK-51: Die Vormerkung für die App ist eine Angabe zu
            // dieser Person und gehört damit in die Auskunft nach Art. 15/20
            // DSGVO. Höchstens eine Zeile je Adresse (Unique-Index auf `email`),
            // deshalb ein Objekt statt einer Liste — und `null`, wenn keine
            // besteht, wie bei `pendingEmail` weiter oben.
            //
            // ⚠ **Ohne `confirmationToken`.** Er ist kein Datum über die Person,
            // sondern ein Zugangsgeheimnis: Wer ihn hat, bestätigt und löscht die
            // Vormerkung. Ihn mitzugeben machte aus der Auskunft genau das
            // Angriffswerkzeug, das der Klassenkommentar oben ausschließt — der
            // Export landet am Ende in einem unverschlüsselten Postfach.
            'appWaitlist' => null === $appEntry ? null : [
                // ⚠ `->value`, nicht der Enum-Fall selbst: Was hier steht, geht
                // durch `json_encode` und muss ein Skalar sein.
                'platform' => $appEntry->getPlatform()?->value,
                'status' => $appEntry->getStatus()->value,
                'createdAt' => $appEntry->getCreatedAt()->format(\DateTimeInterface::ATOM),
                // Beide Zeitpunkte, weil sie Verschiedenes belegen (BF-89):
                // `selfConfirmedAt` setzt allein der Klick des Betroffenen,
                // `confirmedAt` kann auch aus einem Statuswechsel im Admin
                // stammen. Nur der erste weist eine Einwilligung nach.
                'confirmedAt' => $appEntry->getConfirmedAt()?->format(\DateTimeInterface::ATOM),
                'selfConfirmedAt' => $appEntry->getSelfConfirmedAt()?->format(\DateTimeInterface::ATOM),
                'marketingConsentAt' => $appEntry->getMarketingConsentAt()?->format(\DateTimeInterface::ATOM),
                'betaLinkSentAt' => $appEntry->getBetaLinkSentAt()?->format(\DateTimeInterface::ATOM),
            ],
            // Feature 17 / AK-60: Gemerktes, eigene Listen und Notizen. Rein ergänzend —
            // deshalb bleibt die Formatkennung `v1`. Das Lokal steht mit Nummer und Namen
            // darin: Die Nummer allein sagte in der heruntergeladenen Datei niemandem etwas.
            'favorites' => array_map(
                static fn ($favorite) => [
                    'restaurantId' => $favorite->getRestaurant()->getId(),
                    'restaurantName' => $favorite->getRestaurant()->getName(),
                    'savedAt' => $favorite->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->favorites->findByUserNewestFirst($user),
            ),
            // Ohne die interne Nummer der Liste: Sie beschreibt nichts an der Person und
            // taugt außerhalb des Systems zu nichts. Je Liste eine Abfrage für die Einträge —
            // höchstens 25 Listen (`SavedList::MAX_PER_USER`), und der Export ist gedeckelt.
            'savedLists' => array_map(
                fn ($list) => [
                    'name' => $list->getName(),
                    'createdAt' => $list->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'restaurants' => array_map(
                        static fn ($entry) => [
                            'restaurantId' => $entry->getRestaurant()->getId(),
                            'restaurantName' => $entry->getRestaurant()->getName(),
                            'addedAt' => $entry->getAddedAt()->format(\DateTimeInterface::ATOM),
                        ],
                        $this->savedListEntries->findByListNewestFirst($list),
                    ),
                ],
                $this->savedLists->findByUser($user),
            ),
            // ⚠ Der Notiztext kann eine Gesundheitsangabe tragen. Hier gehört er hin — die
            // Auskunft geht an die Person selbst, die ihn geschrieben hat. Nirgendwo sonst
            // verlässt er das System (siehe `RestaurantNote`).
            'notes' => array_map(
                static fn ($note) => [
                    'restaurantId' => $note->getRestaurant()->getId(),
                    'restaurantName' => $note->getRestaurant()->getName(),
                    'text' => $note->getText(),
                    'updatedAt' => $note->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->notes->findByUser($user),
            ),
        ];
    }
}
