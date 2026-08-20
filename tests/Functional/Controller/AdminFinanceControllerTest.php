<?php

namespace App\Tests\Functional\Controller;

use App\Entity\FinanceEntry;
use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use App\Repository\FinanceEntryRepository;
use App\Repository\MetricSnapshotRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class AdminFinanceControllerTest extends AbstractWebTestCase
{
    public function testIndexIsForbiddenForGuests(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/admin/finanzen');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testIndexIsForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', self::LOCALE.'/admin/finanzen');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminSeesEntriesAndCanFilterByType(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Hosting', $crawler->filter('table')->text());

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen?type=income');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Hosting', $crawler->filter('table')->text());
    }

    public function testAdminCanCreateAnEntry(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen/neu');
        self::assertResponseIsSuccessful();

        $form = $this->formWithField($crawler, 'finance_entry[amount]', [
            'finance_entry[date]' => '2026-06-15',
            'finance_entry[category]' => FinanceCategory::DOMAIN->value,
            'finance_entry[amount]' => '42.50',
            'finance_entry[note]' => 'Testbeleg',
        ]);
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/admin/finanzen');

        $entry = $this->repository($client)->findOneBy(['note' => 'Testbeleg']);
        self::assertNotNull($entry);
        self::assertSame('42.50', $entry->getAmount());
        self::assertSame(FinanceType::EXPENSE, $entry->getType(), 'Die Richtung folgt aus der Kategorie.');
    }

    public function testAdminCanEditAnEntry(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $entry = $this->createEntry($client);

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen/'.$entry->getId().'/bearbeiten');
        self::assertResponseIsSuccessful();

        $form = $this->formWithField($crawler, 'finance_entry[amount]', [
            'finance_entry[amount]' => '77.00',
        ]);
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/admin/finanzen');
        // Neu laden statt refresh(): Der Client bootet den Kernel für den
        // Request neu, die alte Instanz gehört keinem EntityManager mehr an.
        self::assertSame('77.00', $this->repository($client)->find($entry->getId())?->getAmount());
    }

    public function testAdminCanDeleteAnEntry(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $entry = $this->createEntry($client);
        $id = $entry->getId();

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen');
        $form = $this->formByAction($crawler, '/admin/finanzen/'.$id.'/loeschen');
        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/admin/finanzen');
        self::assertNull($this->repository($client)->find($id));
    }

    /**
     * Der Betrag ist immer positiv; die Richtung steckt in der Kategorie. Ein
     * negativer Wert würde die veröffentlichte Summe doppelt invertieren.
     */
    public function testNegativeAmountIsRejected(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen/neu');
        $form = $this->formWithField($crawler, 'finance_entry[amount]', [
            'finance_entry[date]' => '2026-06-15',
            'finance_entry[category]' => FinanceCategory::HOSTING->value,
            'finance_entry[amount]' => '-10.00',
        ]);
        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * Sichtbarer Fehler statt stillem Verwerfen: Wer eine Stückzahl einträgt,
     * meint sie auch – sie kommentarlos zu löschen wäre nicht nachvollziehbar.
     */
    public function testQuantityIsRejectedForCategoriesWithoutQuantity(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen/neu');
        $form = $this->formWithField($crawler, 'finance_entry[amount]', [
            'finance_entry[date]' => '2026-06-15',
            'finance_entry[category]' => FinanceCategory::HOSTING->value,
            'finance_entry[amount]' => '10.00',
            'finance_entry[quantity]' => '5',
        ]);
        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
    }

    public function testQuantityIsAcceptedForInclusionBoxMaterials(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen/neu');
        $form = $this->formWithField($crawler, 'finance_entry[amount]', [
            'finance_entry[date]' => '2026-06-15',
            'finance_entry[category]' => FinanceCategory::INCLUSION_BOX_MATERIALS->value,
            'finance_entry[amount]' => '120.00',
            'finance_entry[quantity]' => '4',
            'finance_entry[note]' => 'Boxen-Test',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(4, $this->repository($client)->findOneBy(['note' => 'Boxen-Test'])?->getQuantity());
    }

    /**
     * Der Zeitplan braucht einen Messenger-Worker, den Production nicht hat.
     * Ohne diesen Knopf bliebe die Historie unbemerkt leer.
     */
    public function testAdminCanTriggerASnapshotByHand(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin/finanzen');
        $client->submit($this->formByAction($crawler, '/admin/finanzen/snapshot'));

        self::assertResponseRedirects(self::LOCALE.'/admin/finanzen');
        self::assertNotNull(
            $client->getContainer()->get(MetricSnapshotRepository::class)->findLatest(),
            'Nach dem Klick muss ein Snapshot existieren.',
        );
    }

    private function createEntry(KernelBrowser $client): FinanceEntry
    {
        $entry = (new FinanceEntry())
            ->setCategory(FinanceCategory::HOSTING)
            ->setAmount('29.00')
            ->setDate(new \DateTimeImmutable('-2 months'))
            ->setNote('Bestehender Beleg');

        $em = $this->em($client);
        $em->persist($entry);
        $em->flush();

        return $entry;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function repository(KernelBrowser $client): FinanceEntryRepository
    {
        return $client->getContainer()->get(FinanceEntryRepository::class);
    }
}
