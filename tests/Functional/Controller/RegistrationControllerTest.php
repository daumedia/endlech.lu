<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final class RegistrationControllerTest extends AbstractWebTestCase
{
    public function testRegisterPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/register');

        self::assertResponseIsSuccessful();
    }

    public function testSuccessfulRegistrationCreatesUserAndSendsEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $email = 'neu_'.uniqid().'@endlech.lu';
        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Neu Benutzer',
            'registration[email]' => $email,
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertResponseRedirects();
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', $email);

        $user = $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());
        self::assertNotNull($user->getVerificationToken());
    }

    public function testValidationErrorsRerenderWithoutSendingEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'X',
            'registration[email]' => 'keine-email',
            'registration[plainPassword][first]' => '123',
            'registration[plainPassword][second]' => '123',
        ]));

        // Ungültiges Formular wird mit Fehlern neu gerendert (Symfony: HTTP 422).
        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
    }

    public function testMailerFailureShowsWarningAndStillRedirects(): void
    {
        $client = static::createClient();
        $client->getContainer()->set(MailerInterface::class, new class implements MailerInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                throw new TransportException('SMTP down');
            }
        });

        $crawler = $client->request('GET', self::LOCALE.'/register');
        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Mailer Pech',
            'registration[email]' => 'mailerfail_'.uniqid().'@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        // Trotz Mailer-Fehler kein 500er, sondern Redirect (mit Warnung).
        self::assertResponseRedirects();
    }
}
