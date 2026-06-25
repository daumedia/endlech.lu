<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileControllerTest extends AbstractWebTestCase
{
    public function testProfileRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseRedirects();
    }

    public function testProfileLoadsForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseIsSuccessful();
    }

    public function testChangePasswordWithWrongCurrentIsRejected(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formWithField($crawler, 'change_password[currentPassword]', [
            'change_password[currentPassword]' => 'falsches-passwort',
            'change_password[newPassword][first]' => 'neuespasswort',
            'change_password[newPassword][second]' => 'neuespasswort',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/profile');

        // Passwort bleibt unverändert.
        $container = $client->getContainer();
        $container->get(EntityManagerInterface::class)->clear();
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertTrue($container->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'user123'));
    }

    public function testChangePasswordSuccess(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formWithField($crawler, 'change_password[currentPassword]', [
            'change_password[currentPassword]' => 'user123',
            'change_password[newPassword][first]' => 'ganzneuespasswort',
            'change_password[newPassword][second]' => 'ganzneuespasswort',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/profile');

        $container = $client->getContainer();
        $container->get(EntityManagerInterface::class)->clear();
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertTrue($container->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'ganzneuespasswort'));
    }

    public function testDeleteAvatarWithInvalidCsrfShowsError(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('POST', self::LOCALE.'/profile/avatar/delete', ['_token' => 'ungueltig']);

        self::assertResponseRedirects(self::LOCALE.'/profile');
    }
}
