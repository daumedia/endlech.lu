<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;

final class PasskeyControllerTest extends AbstractWebTestCase
{
    public function testLoginOptionsArePublicAndCarryAChallenge(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/passkey/login/options');

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('challenge', $payload);
        self::assertNotSame('', $payload['challenge']);
    }

    /**
     * Die Anmelde-Optionen dürfen keine Kennungen aufzählen: Sonst liesse sich
     * von aussen ablesen, welche Passkeys es gibt. Ohne allowCredentials sucht
     * der Browser selbst – genau das ermöglicht die Anmeldung ohne E-Mail.
     */
    public function testLoginOptionsDoNotListCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/passkey/login/options');

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertTrue(
            !isset($payload['allowCredentials']) || $payload['allowCredentials'] === [],
            'Die Anmeldung ohne E-Mail setzt eine leere Liste erlaubter Kennungen voraus.',
        );
    }

    public function testRegistrationOptionsRequireLogin(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/passkey/register/options');

        self::assertResponseStatusCodeSame(302);
    }

    public function testRegistrationOptionsWorkWhenLoggedIn(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->jsonRequest('POST', '/passkey/register/options');

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('challenge', $payload);
        self::assertArrayHasKey('user', $payload);
        self::assertSame('user@endlech.lu', $payload['user']['name'] ?? null);
    }

    public function testProfileListsOwnPasskeys(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $this->createPasskey($client, $user, 'Testgerät');

        $crawler = $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Testgerät', $crawler->filter('body')->text());
    }

    public function testRenameChangesTheDisplayName(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $passkey = $this->createPasskey($client, $user, 'Alter Name');

        // Über das gerenderte Formular, damit der session-basierte Token
        // mitkommt – wie bei den anderen Custom-Token-IDs im Projekt.
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formByAction($crawler, '/umbenennen', ['name' => 'Neuer Name']));

        self::assertResponseRedirects();
        self::assertSame('Neuer Name', $this->reload($client, $passkey)?->getName());
    }

    public function testDeleteRemovesThePasskey(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $passkey = $this->createPasskey($client, $user, 'Wegwerfgerät');
        $id = $passkey->getId();

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formByAction($crawler, '/loeschen'));

        self::assertResponseRedirects();
        self::assertNull($client->getContainer()->get(EntityManagerInterface::class)->find(WebauthnCredential::class, $id));
    }

    /**
     * Die id steht im Pfad und ist fortlaufend – ohne Besitzprüfung liesse sich
     * ein fremder Passkey mit einer geratenen Zahl entfernen.
     */
    public function testForeignPasskeyCannotBeDeleted(): void
    {
        $client = static::createClient();
        $owner = $this->user($client, 'admin@endlech.lu');
        $passkey = $this->createPasskey($client, $owner, 'Fremdes Gerät');
        $id = $passkey->getId();

        $this->loginAs($client, 'user@endlech.lu');

        // Der Token ist hier gleichgültig: Die Besitzprüfung steht davor und
        // muss allein schon greifen.
        $client->request('POST', self::LOCALE.'/profile/passkeys/'.$id.'/loeschen', ['_token' => 'egal']);

        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($client->getContainer()->get(EntityManagerInterface::class)->find(WebauthnCredential::class, $id));
    }

    public function testDeleteWithoutValidTokenDoesNothing(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $passkey = $this->createPasskey($client, $user, 'Geschütztes Gerät');
        $id = $passkey->getId();

        $client->request('POST', self::LOCALE.'/profile/passkeys/'.$id.'/loeschen', ['_token' => 'unsinn']);

        self::assertResponseRedirects();
        self::assertNotNull($client->getContainer()->get(EntityManagerInterface::class)->find(WebauthnCredential::class, $id));
    }

    private function createPasskey(KernelBrowser $client, User $user, string $name): WebauthnCredential
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $user->obtainWebauthnHandle();

        $passkey = new WebauthnCredential(
            random_bytes(32),
            'public-key',
            ['internal'],
            'none',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            random_bytes(32),
            (string) $user->getWebauthnHandle(),
            0,
        );
        $passkey->setUser($user)->setName($name);

        $em->persist($passkey);
        $em->flush();

        return $passkey;
    }

    private function reload(KernelBrowser $client, WebauthnCredential $passkey): ?WebauthnCredential
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(WebauthnCredential::class, $passkey->getId());
    }
}
