<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Repository\BoardIdeaRepository;
use App\Repository\BoardVoteRepository;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;

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
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
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
        ];
    }
}
