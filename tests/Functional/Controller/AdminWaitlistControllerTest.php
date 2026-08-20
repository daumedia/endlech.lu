<?php

namespace App\Tests\Functional\Controller;

use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class AdminWaitlistControllerTest extends AbstractWebTestCase
{
    public function testIndexIsForbiddenForGuests(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/admin/warteliste');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testIndexIsForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', self::LOCALE . '/admin/warteliste');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminSeesEntryAndCanFilter(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $entry = $this->createEntry($client);

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString($entry->getRestaurantName(), $crawler->text());

        // Filter auf einen anderen Status blendet den Eintrag aus.
        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste?status=converted');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString($entry->getRestaurantName(), $crawler->filter('table')->count() ? $crawler->filter('table')->text() : '');
    }

    public function testAdminCanChangeStatus(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $entry = $this->createEntry($client);
        $id = $entry->getId();

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $id);
        self::assertResponseIsSuccessful();

        $client->submit($this->formWithField($crawler, 'status', ['status' => WaitlistStatus::CONTACTED->value]));

        self::assertResponseRedirects(self::LOCALE . '/admin/warteliste/partner/' . $id);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $reloaded = $em->find(PartnerWaitlistEntry::class, $id);

        self::assertSame(WaitlistStatus::CONTACTED, $reloaded->getStatus());
        // Ein Statuswechsel jenseits von "unbestätigt" setzt den Zeitstempel nach.
        self::assertNotNull($reloaded->getConfirmedAt());
    }

    public function testInvalidCsrfLeavesStatusUnchanged(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $entry = $this->createEntry($client);
        $id = $entry->getId();

        $client->request('POST', self::LOCALE . '/admin/warteliste/partner/' . $id . '/status', [
            '_token' => 'ungueltig',
            'status' => WaitlistStatus::DECLINED->value,
        ]);

        self::assertResponseRedirects();

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        self::assertSame(WaitlistStatus::PENDING, $em->find(PartnerWaitlistEntry::class, $id)->getStatus());
    }

    public function testAdminCanLinkAndUnlinkRestaurant(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $entry = $this->createEntry($client);
        $id = $entry->getId();

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $id);
        $restaurantId = (int) $crawler->filter('#partner-restaurant option')->eq(1)->attr('value');
        self::assertGreaterThan(0, $restaurantId, 'Fixtures müssen mindestens ein Restaurant liefern.');

        $client->submit($this->formWithField($crawler, 'restaurant', ['restaurant' => (string) $restaurantId]));
        self::assertResponseRedirects();

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertSame($restaurantId, $em->find(PartnerWaitlistEntry::class, $id)->getRestaurant()?->getId());

        // Auswahl "nicht verknüpft" löst die Verbindung wieder.
        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $id);
        $client->submit($this->formWithField($crawler, 'restaurant', ['restaurant' => '0']));

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertNull($em->find(PartnerWaitlistEntry::class, $id)->getRestaurant());
    }

    public function testCombinedListShowsBothTypesAndFiltersBySource(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $partner = $this->createEntry($client);
        $organisation = $this->createOrganisation($client);

        // Ungefiltert stehen beide Eintragstypen nebeneinander.
        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste');
        self::assertResponseIsSuccessful();
        $all = $crawler->filter('tbody')->text();
        self::assertStringContainsString($partner->getRestaurantName(), $all);
        self::assertStringContainsString($organisation->getOrganisationName(), $all);

        // Quellen-Filter blendet die jeweils andere Liste aus.
        $onlyPartner = $client->request('GET', self::LOCALE . '/admin/warteliste?source=partner')->filter('tbody')->text();
        self::assertStringContainsString($partner->getRestaurantName(), $onlyPartner);
        self::assertStringNotContainsString($organisation->getOrganisationName(), $onlyPartner);

        // Typ-Filter impliziert die Quelle "Organisation".
        $onlyCommune = $client->request('GET', self::LOCALE . '/admin/warteliste?type=commune')->filter('tbody')->text();
        self::assertStringContainsString($organisation->getOrganisationName(), $onlyCommune);
        self::assertStringNotContainsString($partner->getRestaurantName(), $onlyCommune);
    }

    public function testOrganisationStatusCanBeChanged(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $organisation = $this->createOrganisation($client);
        $id = $organisation->getId();

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/organisation/' . $id);
        self::assertResponseIsSuccessful();

        $client->submit($this->formWithField($crawler, 'status', ['status' => WaitlistStatus::QUALIFIED->value]));

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        self::assertSame(WaitlistStatus::QUALIFIED, $em->find(OrganisationWaitlistEntry::class, $id)->getStatus());
    }

    private function createOrganisation(KernelBrowser $client): OrganisationWaitlistEntry
    {
        $entry = new OrganisationWaitlistEntry();
        $entry->setType(OrganisationType::COMMUNE)
            ->setOrganisationName('Gemeng ' . uniqid())
            ->setContactName('Test Person')
            ->setEmail(uniqid() . '@example.lu')
            ->setLocale('de');
        $entry->generateConfirmationToken();

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist($entry);
        $em->flush();

        return $entry;
    }

    private function createEntry(KernelBrowser $client): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Warteliste ' . uniqid())
            ->setContactName('Test Person')
            ->setEmail(uniqid() . '@example.lu')
            ->setLocality('Luxemburg')
            ->setLocale('de');
        $entry->generateConfirmationToken();

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist($entry);
        $em->flush();

        return $entry;
    }
}
