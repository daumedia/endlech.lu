<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileControllerTest extends AbstractWebTestCase
{
    use MailerAssertionsTrait;

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

    /**
     * AK-13 (korrigiert) / BUG-15: Die neue Adresse wird vorgemerkt, nicht übernommen.
     *
     * Vor der Reparatur wechselte $user->email im selben Request. Damit machte
     * eine gekaperte Sitzung aus einem Konto dauerhaft ein fremdes – der
     * rechtmäßige Inhaber hätte keinen Weg zurück, weil es kein
     * Passwort-Zurücksetzen gibt.
     */
    public function testEmailAenderungWirdNurVorgemerkt(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formWithField($crawler, 'profile[email]', [
            'profile[name]' => $user->getName(),
            'profile[email]' => 'neue-adresse@qa.example',
        ]));

        self::assertResponseRedirects();

        $frisch = static::getContainer()->get(UserRepository::class)->find($user->getId());
        self::assertSame('user@endlech.lu', $frisch->getEmail(), 'Die bestätigte Adresse darf sich nicht ändern.');
        self::assertSame('neue-adresse@qa.example', $frisch->getPendingEmail());
        self::assertNotNull($frisch->getPendingEmailToken());
    }

    /**
     * Die Warnung an die BISHERIGE Adresse ist der eigentliche Schutz: Wer das
     * Konto übernehmen will, liest die Bestätigung im neuen Postfach ohnehin mit.
     */
    public function testEmailAenderungBenachrichtigtBeideAdressen(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formWithField($crawler, 'profile[email]', [
            'profile[name]' => $user->getName(),
            'profile[email]' => 'zweite@qa.example',
        ]));

        // Doppelt gezählt, weil der sync-Transport jede Nachricht sowohl beim
        // Versenden als auch beim Verarbeiten meldet. Die Aussage ist, WELCHE
        // Postfächer erreicht werden – nicht wie oft der Sammler zuschlägt.
        $empfaenger = [];
        foreach (self::getMailerMessages() as $mail) {
            $empfaenger[] = $mail->getTo()[0]->getAddress();
        }
        $empfaenger = array_values(array_unique($empfaenger));
        sort($empfaenger);

        self::assertSame(['user@endlech.lu', 'zweite@qa.example'], $empfaenger);
    }

    public function testBestaetigungUebernimmtDieNeueAdresse(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = $user->requestEmailChange('bestaetigt@qa.example');
        $em->flush();

        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);
        self::assertResponseRedirects();

        $em->refresh($user);
        self::assertSame('bestaetigt@qa.example', $user->getEmail());
        self::assertNull($user->getPendingEmail());
        self::assertNull($user->getPendingEmailToken());
    }

    public function testAbgelaufenerBestaetigungslinkWechseltNicht(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = $user->requestEmailChange('zu-spaet@qa.example');
        $em->flush();

        // Erst NACH dem flush zurückdatieren – sonst schriebe der flush die
        // eben gesetzte Frist wieder über das UPDATE.
        $em->createQuery('UPDATE App\\Entity\\User u SET u.pendingEmailTokenExpiresAt = :gestern WHERE u.id = :id')
            ->setParameter('gestern', new \DateTimeImmutable('-1 hour'))
            ->setParameter('id', $user->getId())
            ->execute();
        $em->refresh($user);

        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);

        $em->refresh($user);
        self::assertSame('user@endlech.lu', $user->getEmail());
        self::assertNull($user->getPendingEmail(), 'Der abgelaufene Vorgang wird abgeräumt.');
    }

    /**
     * Zwischen Vormerkung und Bestätigung kann sich jemand mit genau dieser
     * Adresse registrieren. pending_email trägt keine Eindeutigkeit, email schon –
     * ohne die Prüfung im Controller liefe der flush() in einen 500er.
     */
    public function testBereitsVergebeneAdresseFuehrtNichtZumFehler(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = $user->requestEmailChange('admin@endlech.lu');
        $em->flush();

        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);

        self::assertResponseRedirects();
        $em->refresh($user);
        self::assertSame('user@endlech.lu', $user->getEmail());
        self::assertNull($user->getPendingEmail());
    }

    public function testOffenerVorgangLaesstSichAbbrechen(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user->requestEmailChange('doch-nicht@qa.example');
        $em->flush();
        $em->clear();

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        self::assertStringContainsString('doch-nicht@qa.example', $crawler->html(), 'Der offene Vorgang muss im Profil sichtbar sein.');

        $client->submit($this->formByAction($crawler, '/profile/email/abbrechen'));

        $frisch = static::getContainer()->get(UserRepository::class)->find($user->getId());
        self::assertNull($frisch->getPendingEmail());
    }

    /**
     * Eine unveränderte Adresse darf keinen Bestätigungsvorgang auslösen – sonst
     * sperrte jedes Speichern des Namens die eigene Anmeldung aus.
     */
    public function testNamensaenderungLoestKeinenAdresswechselAus(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formWithField($crawler, 'profile[email]', [
            'profile[name]' => 'Neuer Name',
            'profile[email]' => 'user@endlech.lu',
        ]));

        self::assertEmailCount(0);

        $frisch = static::getContainer()->get(UserRepository::class)->find($user->getId());
        self::assertSame('Neuer Name', $frisch->getName());
        self::assertNull($frisch->getPendingEmail());
    }
}
