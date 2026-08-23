<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Deckt den Bestätigungsweg ab, der bis zur QA von B01 (2026-08-23) ohne Test war.
 *
 * Die Methodennamen tragen die Kriteriennummer aus
 * features/B01-registrierung-email-bestaetigung/spec.md.
 */
final class EmailVerificationControllerTest extends AbstractWebTestCase
{
    public function testAk09GueltigerTokenVerifiziertUndLeertDenToken(): void
    {
        $client = static::createClient();
        [$user, $token] = $this->createUnverifiedUser($client);

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/login');

        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        $fresh = $client->getContainer()->get(UserRepository::class)->find($user->getId());

        self::assertTrue($fresh->isVerified());
        self::assertNull($fresh->getVerificationToken(), 'Der Token muss nach dem Einlösen geleert sein.');
        self::assertNull($fresh->getVerificationTokenExpiresAt());
    }

    public function testAk10AbgelaufenerTokenVerifiziertNicht(): void
    {
        $client = static::createClient();
        [$user, $token] = $this->createUnverifiedUser($client);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        $em->flush();

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/verify');

        $em->clear();
        self::assertFalse(
            $client->getContainer()->get(UserRepository::class)->find($user->getId())->isVerified(),
            'Ein abgelaufener Token darf das Konto nicht bestätigen.',
        );
    }

    public function testAk11UnbekannterTokenLeitetAufDieStartseite(): void
    {
        $client = static::createClient();

        $client->request('GET', self::LOCALE.'/verify/'.str_repeat('0', 64));

        self::assertResponseRedirects(self::LOCALE.'/');
    }

    public function testAk19EingeloesterTokenGreiftKeinZweitesMal(): void
    {
        $client = static::createClient();
        [, $token] = $this->createUnverifiedUser($client);

        $client->request('GET', self::LOCALE.'/verify/'.$token);
        self::assertResponseRedirects(self::LOCALE.'/login');

        // Zweiter Aufruf desselben Links: Der Token wurde geleert, ist also unbekannt.
        $client->request('GET', self::LOCALE.'/verify/'.$token);
        self::assertResponseRedirects(self::LOCALE.'/');
    }

    /**
     * EC-03 · Ohne Ablaufzeitpunkt gilt ein Token als abgelaufen — nicht als unbegrenzt gültig.
     */
    public function testEc03TokenOhneAblaufzeitpunktGiltAlsAbgelaufen(): void
    {
        $client = static::createClient();
        [$user, $token] = $this->createUnverifiedUser($client);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $user->setVerificationTokenExpiresAt(null);
        $em->flush();

        $client->request('GET', self::LOCALE.'/verify/'.$token);

        self::assertResponseRedirects(self::LOCALE.'/verify');

        $em->clear();
        self::assertFalse(
            $client->getContainer()->get(UserRepository::class)->find($user->getId())->isVerified(),
        );
    }

    /**
     * AK-15 · Der Weg „Bestätigungsmail erneut senden" ist unerreichbar: In
     * EmailVerificationController steht /verify/{token} vor /verify/resend und fängt
     * die Anfrage mit token="resend" ab.
     *
     * Nachweis: php bin/console router:match /de/verify/resend  ->  app_verify_email
     *
     * Behoben am 2026-08-23 durch requirements: ['token' => '[a-f0-9]{64}'] auf
     * app_verify_email. Der Test hält die Reparatur fest.
     */
    public function testAk15ErneutSendenIstErreichbar(): void
    {
        $client = static::createClient();
        [$user] = $this->createUnverifiedUser($client);
        $this->loginAs($client, $user->getEmail());

        $client->request('GET', self::LOCALE.'/verify/resend');

        self::assertResponseRedirects(self::LOCALE.'/verify');
        self::assertEmailCount(1);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function createUnverifiedUser(KernelBrowser $client): array
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setName('Unbestaetigt Test');
        $user->setEmail('verify_'.uniqid().'@endlech.lu');
        $user->setPassword('$2y$04$abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQR');
        $token = $user->generateVerificationToken();

        $em->persist($user);
        $em->flush();

        return [$user, $token];
    }
}
