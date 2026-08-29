<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
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
     * AK-14 · Eine bereits vergebene Adresse legt kein zweites Konto an — und
     * verrät seit BF-09 nicht mehr, dass es das erste gibt.
     *
     * Vorher meldete das Formular „Diese E-Mail-Adresse wird bereits verwendet"
     * und war damit ein Werkzeug, um herauszufinden, wer hier ein Konto hat. Auf
     * einer Barrierefreiheitsplattform ist das eine Angabe, die niemanden etwas
     * angeht: Wer sie abfragt, erfährt nicht, dass jemand hier isst, sondern dass
     * jemand nach barrierefreien Lokalen sucht. Die API macht es seit jeher so.
     */
    public function testAk14BereitsVergebeneAdresseLegtKeinZweitesKontoAn(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $vorher = \count($em->getRepository(User::class)->findBy(['email' => 'user@endlech.lu']));

        $crawler = $client->request('GET', self::LOCALE.'/register');
        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Doppelt Test',
            'registration[email]' => 'user@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertResponseRedirects();

        $em->clear();
        self::assertCount($vorher, $em->getRepository(User::class)->findBy(['email' => 'user@endlech.lu']));
    }

    /**
     * BF-09 · Die Antwort ist dieselbe, egal ob die Adresse vergeben ist.
     *
     * Weder Statuscode noch Weiterleitungsziel dürfen die beiden Fälle
     * unterscheiden — sonst hätte die Reparatur nur den Meldungstext versteckt.
     */
    public function testBf09AntwortVerraetNichtObDieAdresseVergebenIst(): void
    {
        $client = static::createClient();

        $absenden = function (string $adresse) use ($client): array {
            $crawler = $client->request('GET', self::LOCALE.'/register');
            $client->submit($this->formWithField($crawler, 'registration[email]', [
                'registration[name]' => 'Vergleich',
                'registration[email]' => $adresse,
                'registration[plainPassword][first]' => 'supersecret',
                'registration[plainPassword][second]' => 'supersecret',
            ]));

            return [
                $client->getResponse()->getStatusCode(),
                (string) $client->getResponse()->headers->get('Location'),
            ];
        };

        $vergeben = $absenden('user@endlech.lu');
        $frei = $absenden('frei-'.uniqid().'@example.test');

        self::assertSame($vergeben, $frei, 'Die Antworten unterscheiden die beiden Fälle.');
    }

    /**
     * BF-09 · Die bestehende Adresse bekommt einen Hinweis — sonst erführe der
     * rechtmäßige Inhaber nie, dass jemand seine Adresse benutzt hat.
     */
    public function testBf09BestehendeAdresseBekommtEinenHinweis(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Doppelt Test',
            'registration[email]' => 'user@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertSame('user@endlech.lu', $mail?->getTo()[0]->getAddress());
        self::assertStringContainsString('bereits ein Konto', (string) $mail?->getTextBody());
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
     * BUG-06 / BF-09 · Der Hinweis an die bestehende Adresse folgt der Sprache.
     *
     * Die frühere Fassung dieses Tests prüfte die Meldung IM FORMULAR — die gibt
     * es seit BF-09 nicht mehr, und zwar mit Absicht. Was bleibt und geprüft
     * gehört: Die Mail, die stattdessen rausgeht, ist übersetzt.
     */
    public function testAk14HinweisBeiVergebenerAdresseFolgtDerSprache(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Doublon Test',
            'registration[email]' => 'user@endlech.lu',
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));

        self::assertStringNotContainsString(
            'déjà utilisée',
            (string) $client->getResponse()->getContent(),
            'Das Formular verrät weiterhin, dass die Adresse vergeben ist.',
        );

        self::assertEmailCount(1);
        self::assertStringContainsString('déjà un compte', (string) self::getMailerMessage()?->getTextBody());
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
