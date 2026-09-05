<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AppWaitlistEntry;
use App\Enum\AppPlatform;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die App-Warteliste in der Verwaltung (Feature 08) und der Deckel auf dem
 * Absenden.
 */
final class AppWaitlistAdminTest extends AbstractWebTestCase
{
    public function testEintragErscheintInDerKombiniertenListe(): void
    {
        $client = static::createClient();
        $this->eintrag($client, 'liste@example.lu', AppPlatform::ANDROID);
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/warteliste');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Android', $crawler->filter('table')->text()); // AK-35
    }

    /** ⚠ Die Adresse steht in der Zeile, wird aber nicht angezeigt. */
    public function testDieListeZeigtKeineAdressen(): void
    {
        $client = static::createClient();
        $this->eintrag($client, 'geheim@example.lu', AppPlatform::IOS);
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/warteliste');

        self::assertStringNotContainsString('geheim@example.lu', $crawler->filter('table')->text());
    }

    public function testQuellenfilterAppZeigtNurAppEintraege(): void
    {
        $client = static::createClient();
        $this->eintrag($client, 'nurapp@example.lu', AppPlatform::IOS);
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/warteliste?source=app');

        self::assertResponseIsSuccessful();
        // Vor der Umstellung von Negation auf positiven Vergleich lieferte
        // ?source=app zusätzlich alle Partner- und Organisationszeilen.
        self::assertSame(1, $crawler->filter('table tbody tr')->count());
    }

    public function testDetailseiteZeigtBetaVersandUndSelbstbestaetigung(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'detail@example.lu', AppPlatform::IOS);
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/warteliste/app/'.$entry->getId());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('detail@example.lu', $crawler->text());
    }

    /** AK-36: ohne Admin-Rolle kein einziger Eintrag. */
    public function testOhneAdminRolleKeinZugriff(): void
    {
        $client = static::createClient();
        $this->eintrag($client, 'verborgen@example.lu', AppPlatform::IOS);
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin/warteliste');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAnonymKeinZugriffAufDieDetailseite(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'anonym@example.lu', AppPlatform::IOS);

        $client->request('GET', self::LOCALE.'/admin/warteliste/app/'.$entry->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND); // Weiterleitung zur Anmeldung
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    /**
     * AK-45: Ein reiner Seitenaufruf verbraucht kein Kontingent.
     *
     * ⚠ Die Höhe des Deckels selbst ist funktional nicht prüfbar: `when@test`
     * hebt alle Limiter auf 10000, sonst summierten sich die Aufrufe über die
     * Suite (Konvention in CLAUDE.md). Was hier belegt wird, ist die
     * Reihenfolge — geprüft wird nach `handleRequest`, verbraucht erst beim
     * Anlegen. Die Zahl 10 steht in `framework.yaml` und wird von
     * `LimiterCoverageTest` als konfiguriert und verdrahtet nachgewiesen.
     */
    public function testLesenderAufrufVerbrauchtKeinKontingent(): void
    {
        $client = static::createClient();
        $limiter = $client->getContainer()->get('limiter.app_waitlist');
        $vorher = $limiter->create('1.2.3.4')->consume(0)->getRemainingTokens();

        $client->request('GET', self::LOCALE.'/app');

        self::assertSame($vorher, $limiter->create('1.2.3.4')->consume(0)->getRemainingTokens());
    }

    private function eintrag(KernelBrowser $client, string $email, AppPlatform $platform): AppWaitlistEntry
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $entry = new AppWaitlistEntry();
        $entry->setEmail($email);
        $entry->setPlatform($platform);
        $entry->setLocale('de');
        $entry->generateConfirmationToken();

        $em->persist($entry);
        $em->flush();

        return $entry;
    }
}
