<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
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

    /**
     * AK-02 · Wer angemeldet ist, hat auf der Registrierseite nichts verloren.
     */
    public function testAk02AngemeldeterWirdVonDerRegistrierseiteWeggeleitet(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/register');

        self::assertResponseRedirects(self::LOCALE.'/');
    }

    /**
     * AK-14 · Eine bereits vergebene Adresse wird abgewiesen — und es geht keine Mail raus.
     */
    public function testAk14BereitsVergebeneAdresseWirdAbgewiesen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Doppelt Test',
            'registration[email]' => 'user@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
    }

    /**
     * AK-05 · Ungleiche Passwörter werden abgewiesen.
     */
    public function testAk05UngleichePasswoerterWerdenAbgewiesen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Mismatch Test',
            'registration[email]' => 'mismatch_'.uniqid().'@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'anderesgeheimnis',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
    }

    /**
     * AK-05 · Die Meldung dazu muss lesbar sein, nicht der rohe Übersetzungsschlüssel.
     *
     * RepeatedType::invalid_message wird in der Domäne "validators" übersetzt;
     * der Schlüssel form.password_mismatch steht aber nur in messages.*.yaml.
     * Folge: In allen vier Sprachen erscheint "form.password_mismatch" im Klartext.
     *
     * Behoben am 2026-08-23: Der Schlüssel steht jetzt in den vier
     * validators.*.yaml, wo invalid_message ihn sucht.
     */
    public function testAk05MeldungIstUebersetztNichtDerRoheSchluessel(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Mismatch Test',
            'registration[email]' => 'mismatch_'.uniqid().'@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'anderesgeheimnis',
        ]));

        self::assertStringNotContainsString(
            'form.password_mismatch',
            (string) $client->getResponse()->getContent(),
            'Dem Nutzer darf kein roher Übersetzungsschlüssel angezeigt werden.',
        );
    }

    /**
     * BUG-06 · Die Meldung bei vergebener Adresse muss der Sprache folgen.
     * Vorher stand in allen vier Fassungen deutscher Klartext aus der Entity.
     */
    public function testAk14MeldungBeiVergebenerAdresseFolgtDerSprache(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Doublon Test',
            'registration[email]' => 'user@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        $inhalt = (string) $client->getResponse()->getContent();

        self::assertResponseStatusCodeSame(422);
        self::assertStringNotContainsString('bereits registriert', $inhalt, 'Kein deutscher Klartext in der französischen Fassung.');
        self::assertStringContainsString('déjà utilisée', $inhalt);
    }

    /**
     * BUG-07 · Die Bestätigungsmail muss ihre Sprache mitführen. Ohne locale()
     * rendert ein Messenger-Worker das Template ohne Request-Sprache und der
     * Inhalt fällt auf default_locale zurück — der Betreff aber nicht.
     */
    public function testAk20BestaetigungsmailTraegtDieLocaleDerRegistrierung(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Locale Test',
            'registration[email]' => 'locale_'.uniqid().'@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertEmailCount(1);

        $mail = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $mail);
        self::assertSame('fr', $mail->getLocale(), 'Ohne Locale rendert der Worker in der Vorgabesprache.');
    }
}
