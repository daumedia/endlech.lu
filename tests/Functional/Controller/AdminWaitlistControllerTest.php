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

    /**
     * AK-02: Die beiden Listen werden nach dem Zusammenführen **erneut** sortiert.
     *
     * Ohne das stünden erst alle Partner-, dann alle Organisationseinträge — die
     * Liste sähe sortiert aus, wäre es aber nur innerhalb jeder Hälfte. Das ist
     * ein Fehler, der bei zwei Testeinträgen nie auffällt und bei zwanzig sofort.
     */
    public function testAk02BeideQuellenSindNachDatumDurchmischt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        // Abwechselnd anlegen: P(alt) O(mitte) P(neu) O(neuest)
        $namen = [];
        foreach ([['p', '-4 days'], ['o', '-3 days'], ['p', '-2 days'], ['o', '-1 day']] as $i => [$art, $wann]) {
            if ($art === 'p') {
                $e = $this->createEntry($client);
                $namen[] = $e->getRestaurantName();
            } else {
                $e = $this->createOrganisation($client);
                $namen[] = $e->getOrganisationName();
            }
            $em->createQuery(sprintf(
                'UPDATE %s x SET x.createdAt = :d WHERE x.id = :id',
                $art === 'p' ? PartnerWaitlistEntry::class : OrganisationWaitlistEntry::class,
            ))->setParameter('d', new \DateTimeImmutable($wann))->setParameter('id', $e->getId())->execute();
        }

        $text = $client->request('GET', self::LOCALE.'/admin/warteliste')->filter('tbody')->text();
        $positionen = array_map(static fn (string $n) => mb_strpos($text, $n), $namen);

        // Absteigend nach Datum: der zuletzt angelegte steht oben.
        self::assertGreaterThan($positionen[3], $positionen[2], 'Reihenfolge nicht absteigend.');
        self::assertGreaterThan($positionen[2], $positionen[1]);
        self::assertGreaterThan($positionen[1], $positionen[0]);
    }

    /**
     * AK-21: Das CSRF-Token trägt die ID des Eintrags. Ein Token von Eintrag A darf
     * gegen Eintrag B nicht wirken — sonst genügte ein einziges abgegriffenes Token
     * für die ganze Liste.
     */
    public function testAk21TokenEinesFremdenEintragsWirktNicht(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $a = $this->createEntry($client);
        $b = $this->createEntry($client);

        $crawler = $client->request('GET', self::LOCALE.'/admin/warteliste/partner/'.$a->getId());
        $fremdesToken = $crawler->filter('form[action$="/status"] input[name="_token"]')->attr('value');

        $client->request('POST', self::LOCALE.'/admin/warteliste/partner/'.$b->getId().'/status', [
            '_token' => $fremdesToken,
            'status' => 'declined',
        ]);

        // Frisch laden statt refresh(): Der Test-Client bootet den Kernel je Request
        // neu, die Entity aus dem Vorher-Container ist danach nicht mehr verwaltet.
        $frisch = $client->getContainer()->get(EntityManagerInterface::class)
            ->getRepository(PartnerWaitlistEntry::class)
            ->find($b->getId());

        self::assertNotSame(WaitlistStatus::DECLINED, $frisch->getStatus());
    }

    /**
     * AK-07: Unbekannte Filterwerte werden verworfen, nicht geworfen.
     */
    public function testAk07UnbekannteFilterwerteZeigenDieVolleListe(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $partner = $this->createEntry($client);

        $text = $client->request('GET', self::LOCALE.'/admin/warteliste?status=erfunden&type=erfunden')
            ->filter('tbody')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString($partner->getRestaurantName(), $text);
    }
}
