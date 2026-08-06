<?php

namespace App\Tests\Unit\Api;

use App\Api\AssetUrlBuilder;
use App\Api\UserTransformer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class UserTransformerTest extends TestCase
{
    private function transformer(): UserTransformer
    {
        // Echter AssetUrlBuilder (final) mit fixem Base-URL-Override statt Request-Kontext.
        return new UserTransformer(new AssetUrlBuilder(new RequestStack(), 'https://cdn.test'));
    }

    public function testProfileExposesExpectedFields(): void
    {
        $user = (new User())
            ->setName('Alice')
            ->setEmail('alice@endlech.lu')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true)
            ->setAvatarFilename('alice.png');

        $data = $this->transformer()->profile($user);

        self::assertSame('Alice', $data['name']);
        self::assertSame('alice@endlech.lu', $data['email']);
        self::assertSame('https://cdn.test/uploads/avatars/alice.png', $data['avatarUrl']);
        self::assertTrue($data['isVerified']);
        self::assertContains('ROLE_ADMIN', $data['roles']);
        self::assertContains('ROLE_USER', $data['roles']);
        self::assertNotEmpty($data['createdAt']);
    }

    public function testProfileNeverLeaksSensitiveFields(): void
    {
        $user = (new User())
            ->setName('Bob')
            ->setEmail('bob@endlech.lu')
            ->setPassword('hashed-secret');
        $user->generateVerificationToken();

        $data = $this->transformer()->profile($user);

        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('verificationToken', $data);
        self::assertSame('hashed-secret', $user->getPassword(), 'Sanity-Check: Passwort am User gesetzt.');
        self::assertNotContains('hashed-secret', $data, 'Passwort darf nicht im Profil auftauchen.');
    }

    public function testProfileWithoutAvatarReturnsNullUrl(): void
    {
        $user = (new User())->setName('Carol')->setEmail('carol@endlech.lu');

        $data = $this->transformer()->profile($user);

        self::assertNull($data['avatarUrl']);
    }
}
