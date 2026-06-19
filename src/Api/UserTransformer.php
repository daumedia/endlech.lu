<?php

namespace App\Api;

use App\Entity\User;

/**
 * Wandelt User-Entities in JSON-serialisierbare Arrays für die /api/v1-Endpunkte.
 *
 * Exponiert NIEMALS password oder Verifikations-Token.
 */
final class UserTransformer
{
    /**
     * Eigenes Profil (Endpunkt /me).
     *
     * @return array<string, mixed>
     */
    public function profile(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'avatarUrl' => $user->getAvatarUrl(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
