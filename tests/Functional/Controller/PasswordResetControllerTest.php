<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Feature 01 / BF-04: Ein vergessenes Passwort ist keine Sackgasse mehr.
 *
 * Bis heute gab es keinen Weg zurück in ein Konto — und seit der BF-19-Reparatur
 * wird eine E-Mail-Änderung nur noch nach Bestätigung wirksam, womit auch der
 * Umweg über die Adresse zu war. Wer sein Passwort vergaß, verlor sein Konto.
 */
final class PasswordResetControllerTest extends AbstractWebTestCase
{
    private function anfordern(object $client, string $adresse): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/passwort-vergessen');
        $formular = $this->formWithField($crawler, 'password_reset_request[email]');
        $formular['password_reset_request[email]'] = $adresse;
        $client->submit($formular);
    }

    /**
     * AK-13: Die Antwort ist identisch, egal ob die Adresse existiert.
     *
     * Andernfalls wäre dieses Formular ein Werkzeug, um herauszufinden, wer hier
     * ein Konto hat — bei einer Barrierefreiheitsplattform eine Angabe, die
     * niemanden etwas angeht.
     */
    public function testAntwortVerraetNichtObDieAdresseExistiert(): void
    {
        // Ein Client für beide Läufe: `createClient()` bootet den Kernel, und der
        // darf je Test nur einmal starten. Zwischen den Läufen räumt die Sitzung
        // sich ohnehin nicht auf — hier wird nichts angemeldet.
        $client = static::createClient();

        $this->anfordern($client, 'user@endlech.lu');
        $bekannt = [$client->getResponse()->getStatusCode(), $client->followRedirect()->filter('body')->text()];

        $this->anfordern($client, 'gibt-es-nicht-'.uniqid().'@example.test');
        $unbekannt = [$client->getResponse()->getStatusCode(), $client->followRedirect()->filter('body')->text()];

        self::assertSame($bekannt[0], $unbekannt[0], 'Der Statuscode unterscheidet die beiden Fälle.');
        self::assertSame(
            $this->meldung($bekannt[1]),
            $this->meldung($unbekannt[1]),
            'Der Meldungstext unterscheidet die beiden Fälle.',
        );
    }

    private function meldung(string $text): string
    {
        preg_match('/Wenn wir ein Konto[^.]*\./u', $text, $treffer);

        return $treffer[0] ?? '';
    }

    /**
     * AK-14: Nur für eine bekannte Adresse entsteht ein Token und geht eine Mail.
     */
    public function testNurEinBekanntesKontoBekommtEinenToken(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $this->anfordern($client, 'user@endlech.lu');

        self::assertEmailCount(1);

        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertNotNull($user?->getPasswordResetToken(), 'Kein Token erzeugt.');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $user->getPasswordResetToken());
        self::assertStringContainsString($user->getPasswordResetToken(), (string) self::getMailerMessage()?->getHtmlBody());
    }

    public function testUnbekannteAdresseVerschicktKeineMail(): void
    {
        $client = static::createClient();

        $this->anfordern($client, 'gibt-es-nicht-'.uniqid().'@example.test');

        self::assertEmailCount(0);
    }

    /**
     * AK-15: Das neue Passwort greift, und der Token ist danach verbraucht.
     */
    public function testTokenSetztDasPasswortUndIstDanachVerbraucht(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $this->anfordern($client, 'unverified@endlech.lu');
        $em->clear();
        $token = (string) $em->getRepository(User::class)
            ->findOneBy(['email' => 'unverified@endlech.lu'])?->getPasswordResetToken();

        $crawler = $client->request('GET', self::LOCALE.'/passwort-zuruecksetzen/'.$token);
        self::assertResponseIsSuccessful();

        $formular = $this->formWithField($crawler, 'password_reset[plainPassword][first]');
        $formular['password_reset[plainPassword][first]'] = 'GanzNeuesPasswort1';
        $formular['password_reset[plainPassword][second]'] = 'GanzNeuesPasswort1';
        $client->submit($formular);

        self::assertResponseRedirects();

        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'unverified@endlech.lu']);
        self::assertNull($user?->getPasswordResetToken(), 'Der Token wurde nicht verbraucht.');

        $hasher = static::getContainer()->get('security.user_password_hasher');
        self::assertTrue($hasher->isPasswordValid($user, 'GanzNeuesPasswort1'));

        // Zweiter Aufruf desselben Links.
        $client->request('GET', self::LOCALE.'/passwort-zuruecksetzen/'.$token);
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * AK-16: Ein abgelaufener Token ist von einem falschen unterscheidbar.
     */
    public function testAbgelaufenerTokenSagtDassErAbgelaufenIst(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        $token = $user->generatePasswordResetToken();

        $spiegel = new \ReflectionProperty(User::class, 'passwordResetTokenExpiresAt');
        $spiegel->setValue($user, new \DateTimeImmutable('-2 hours'));
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/passwort-zuruecksetzen/'.$token);

        self::assertResponseStatusCodeSame(410, 'Abgelaufen ist nicht dasselbe wie unbekannt.');
        self::assertStringContainsString('abgelaufen', $crawler->filter('body')->text());
    }

    /**
     * AK-18: Ein offener Adresswechsel wird beim Zurücksetzen abgeräumt.
     *
     * Wer ein Konto übernehmen will, stößt zuerst die Adressänderung an und
     * wartet. Der rechtmäßige Inhaber setzt danach sein Passwort zurück und hat
     * damit bewiesen, dass ihm das Postfach gehört — alles Angefangene davor ist
     * hinfällig. Ohne diese Zeile liefe die Übernahme trotzdem durch.
     */
    public function testOffenerAdresswechselWirdAbgeraeumt(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        $user->requestEmailChange('angreifer@example.test');
        $token = $user->generatePasswordResetToken();
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE.'/passwort-zuruecksetzen/'.$token);
        $formular = $this->formWithField($crawler, 'password_reset[plainPassword][first]');
        $formular['password_reset[plainPassword][first]'] = 'GanzNeuesPasswort1';
        $formular['password_reset[plainPassword][second]'] = 'GanzNeuesPasswort1';
        $client->submit($formular);

        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        self::assertNull($user?->getPendingEmail(), 'Der Übernahmeversuch läuft weiter.');
    }

    /**
     * AK-24: Ein angemeldeter Nutzer braucht das Formular nicht.
     */
    public function testAngemeldeterNutzerWirdInsProfilGeschickt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/passwort-vergessen');

        self::assertResponseRedirects();
    }
}
