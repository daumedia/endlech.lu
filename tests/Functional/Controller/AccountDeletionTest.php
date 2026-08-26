<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Feature 01 / BF-04: Konto löschen (Art. 17 DSGVO) und Daten mitnehmen (Art. 20).
 */
final class AccountDeletionTest extends AbstractWebTestCase
{
    private function loeschen(object $client, string $passwort): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $formular = $this->formByAction($crawler, '/profile/loeschen');
        $formular['password'] = $passwort;
        $client->submit($formular);
    }

    /**
     * AK-02, AK-03: Ohne richtiges Passwort passiert nichts.
     */
    public function testFalschesPasswortLoeschtNichts(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $this->loeschen($client, 'ganz-sicher-falsch');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertNotNull(
            $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']),
            'Das Konto wurde ohne gültiges Passwort gelöscht.',
        );
    }

    /**
     * AK-04, AK-05: Das Konto verschwindet, die Restaurants bleiben.
     *
     * Eine Angabe darüber, ob ein Lokal eine Rampe hat, ist eine Sachangabe. Sie
     * mitzulöschen nähme anderen Menschen eine Auskunft weg, die sie brauchen —
     * und Art. 17 verlangt es nicht.
     */
    public function testKontoVerschwindetUndEinreichungenBleiben(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $vorher = \count($em->getRepository(Restaurant::class)->findAll());
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        $eigene = \count($em->getRepository(Restaurant::class)->findBy(['submittedBy' => $user]));
        self::assertGreaterThan(0, $eigene, 'Der Fixture-Nutzer hat keine Einreichungen — der Test prüft nichts.');

        $this->loeschen($client, 'user123');

        $em->clear();
        self::assertNull($em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']));
        self::assertCount($vorher, $em->getRepository(Restaurant::class)->findAll(), 'Restaurants sind mitgelöscht worden.');
    }

    /**
     * AK-04: Nach der Löschung ist der Nutzer abgemeldet.
     */
    public function testNutzerIstNachDerLoeschungAbgemeldet(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $this->loeschen($client, 'user123');
        $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseRedirects();
    }

    /**
     * AK-08: Der Nutzer bekommt eine Bestätigung an seine bisherige Adresse.
     */
    public function testLoeschungSchicktEineBestaetigung(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'unverified@endlech.lu');

        $this->loeschen($client, 'unverified123');

        self::assertEmailCount(1);
        self::assertSame('unverified@endlech.lu', self::getMailerMessage()?->getTo()[0]->getAddress());
    }

    /**
     * EC-02: Der letzte Admin kann sich nicht selbst löschen — sonst wäre der
     * Verwaltungsbereich nach einem Klick unerreichbar.
     */
    public function testLetzterAdminKannSichNichtLoeschen(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $this->loeschen($client, 'admin123');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertNotNull(
            $em->getRepository(User::class)->findOneBy(['email' => 'admin@endlech.lu']),
            'Der letzte Admin hat sich gelöscht.',
        );
    }

    /**
     * AK-09, AK-10: Der Export ist eine JSON-Datei — und enthält kein Geheimnis.
     */
    public function testExportLiefertJsonOhneGeheimnisse(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/profile/daten');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
        self::assertStringContainsString('attachment', (string) $client->getResponse()->headers->get('Content-Disposition'));

        $inhalt = (string) $client->getResponse()->getContent();
        $daten = json_decode($inhalt, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('user@endlech.lu', $daten['account']['email']);
        self::assertArrayHasKey('publishedRestaurants', $daten);
        self::assertArrayHasKey('suggestions', $daten);

        foreach (['password', 'passwordResetToken', 'verificationToken', '$2y$'] as $verboten) {
            self::assertStringNotContainsString($verboten, $inhalt, sprintf('"%s" steht im Export.', $verboten));
        }
    }

    /**
     * AK-10: Kein fremder Datensatz im eigenen Export.
     */
    public function testExportEnthaeltNurEigeneDaten(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/profile/daten');
        $inhalt = (string) $client->getResponse()->getContent();

        self::assertStringNotContainsString('admin@endlech.lu', $inhalt);
        self::assertStringNotContainsString('unverified@endlech.lu', $inhalt);
    }

    /**
     * AK-24: Ohne Anmeldung führt der Weg zur Anmeldung.
     */
    public function testGastKommtNichtAnDenExport(): void
    {
        $client = static::createClient();

        $client->request('GET', self::LOCALE.'/profile/daten');

        self::assertResponseRedirects();
    }
}
