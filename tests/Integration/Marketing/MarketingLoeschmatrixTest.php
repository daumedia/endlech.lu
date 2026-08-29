<?php

namespace App\Tests\Integration\Marketing;

use App\Entity\PartnerWaitlistEntry;
use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Die Löschsemantik bei geteilter Adresse — nach der BF-84-Reparatur (QA²).
 *
 * ⚠ Die Reparatur hat eine Verzweigung eingeführt, die es vorher nicht gab:
 * `scheduleRemoval()` löscht **oder** schreibt um, je nachdem, ob eine andere
 * Quelle übrig bleibt. Damit gibt es Reihenfolgen, die vorher nicht existierten
 * — und die Frage, die diese Prüfung beantwortet, ist die einzige, die zählt:
 *
 * **Bleibt ein Kontakt bei Brevo stehen, wenn alle Quellen verschwunden sind?**
 *
 * Ein „nein" darauf ist der Unterschied zwischen einer Löschung nach Art. 17
 * und einer, die nur so aussieht.
 */
final class MarketingLoeschmatrixTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MarketingContactRegistry $registry;
    private MarketingContactRepository $contacts;
    private WaitlistConfirmationService $waitlist;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->registry = static::getContainer()->get(MarketingContactRegistry::class);
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
        $this->waitlist = static::getContainer()->get(WaitlistConfirmationService::class);
    }

    private function warteliste(string $email): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Haus')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->confirm();

        $this->em->persist($entry);
        $this->em->flush();

        $this->registry->recordWaitlistEntry($entry);
        $this->em->flush();

        return $entry;
    }

    private function konto(string $email): User
    {
        $user = new User();
        $user->setName('Konto')
            ->setEmail($email)
            ->setPassword('irrelevant')
            ->setIsVerified(true)
            ->setMarketingConsentAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        $this->registry->recordUser($user);
        $this->em->flush();

        return $user;
    }

    /**
     * ⚠ Beide Quellen verschwinden, Warteliste zuerst.
     *
     * Nach dem ersten Widerruf darf **nicht** gelöscht werden (BF-84), nach
     * dem zweiten **muss** es.
     */
    public function testBeideQuellenWegWartelisteZuerst(): void
    {
        $email = 'matrix-a@qa.lu';
        $entry = $this->warteliste($email);
        $user = $this->konto($email);

        $this->waitlist->revoke($entry);

        self::assertSame(
            MarketingSyncState::PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Nach dem ersten Widerruf darf nicht gelöscht werden – das Konto bleibt',
        );

        static::getContainer()->get(\App\Account\AccountDeleter::class)->delete($user);

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Alle Quellen sind weg – der Kontakt muss bei Brevo gelöscht werden',
        );
    }

    /**
     * ⚠ Dieselbe Lage, umgekehrte Reihenfolge: Konto zuerst.
     */
    public function testBeideQuellenWegKontoZuerst(): void
    {
        $email = 'matrix-b@qa.lu';
        $entry = $this->warteliste($email);
        $user = $this->konto($email);

        static::getContainer()->get(\App\Account\AccountDeleter::class)->delete($user);

        self::assertSame(
            MarketingSyncState::PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Nach der Kontolöschung darf nicht gelöscht werden – die Warteliste bleibt',
        );

        $this->waitlist->revoke($entry);

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Alle Quellen sind weg – der Kontakt muss bei Brevo gelöscht werden',
        );
    }

    /**
     * ⚠ Ein stehender Löschauftrag darf nicht durch den Widerruf einer
     * **weiteren** Quelle zurückgenommen werden.
     */
    public function testStehenderLoeschauftragUeberlebtEinenZweitenWiderruf(): void
    {
        $email = 'matrix-c@qa.lu';
        $ersteWarteliste = $this->warteliste($email);

        $zweite = new PartnerWaitlistEntry();
        $zweite->setRestaurantName('Zweites Haus')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht');
        // Ohne Werbe-Einwilligung – zählt nicht als aktive Quelle.
        $zweite->confirm();
        $this->em->persist($zweite);
        $this->em->flush();

        $this->waitlist->revoke($ersteWarteliste);

        self::assertSame(MarketingSyncState::REMOVAL_PENDING, $this->contacts->findOneByEmail($email)->getSyncState());

        $this->waitlist->revoke($zweite);

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Der Löschauftrag wurde durch den zweiten Widerruf zurückgenommen',
        );
    }

    /**
     * ⚠ Eine Quelle **ohne** Einwilligung hält den Kontakt nicht am Leben.
     *
     * Sonst genügte ein beliebiger Wartelisten-Eintrag derselben Adresse, um
     * eine Löschung dauerhaft zu verhindern.
     */
    public function testQuelleOhneEinwilligungVerhindertDieLoeschungNicht(): void
    {
        $email = 'matrix-d@qa.lu';
        $mitEinwilligung = $this->warteliste($email);

        $ohne = new PartnerWaitlistEntry();
        $ohne->setRestaurantName('Ohne Einwilligung')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht');
        $ohne->confirm();
        $this->em->persist($ohne);
        $this->em->flush();

        $this->waitlist->revoke($mitEinwilligung);

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Ein Eintrag ohne Einwilligung hat die Löschung verhindert',
        );
    }

    /**
     * ⚠ Eine **unbestätigte** Quelle hält den Kontakt ebenfalls nicht am Leben
     * — sie dürfte selbst gar nicht in Brevo stehen (AK-05).
     */
    public function testUnbestaetigteQuelleVerhindertDieLoeschungNicht(): void
    {
        $email = 'matrix-e@qa.lu';
        $bestaetigt = $this->warteliste($email);

        $unbestaetigt = new PartnerWaitlistEntry();
        $unbestaetigt->setRestaurantName('Unbestätigt')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $unbestaetigt->generateConfirmationToken();
        $this->em->persist($unbestaetigt);
        $this->em->flush();

        $this->waitlist->revoke($bestaetigt);

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Ein unbestätigter Eintrag hat die Löschung verhindert',
        );
    }

    /**
     * ⚠ Die auslösende Quelle wird über Objektidentität erkannt. Trägt das
     * auch, wenn sie zwischenzeitlich neu geladen wurde?
     *
     * Doctrine gibt aus seiner Identity Map dieselbe Instanz zurück — dieser
     * Test hält fest, dass die Reparatur darauf beruht. Bräche es, hielte die
     * auslösende Quelle sich selbst am Leben und der Kontakt bliebe für immer
     * bei Brevo.
     */
    public function testAusloesendeQuelleWirdAuchNachNeuladenErkannt(): void
    {
        $email = 'matrix-f@qa.lu';
        $entry = $this->warteliste($email);
        $id = $entry->getId();

        $neuGeladen = $this->em->getRepository(PartnerWaitlistEntry::class)->find($id);
        self::assertSame($entry, $neuGeladen, 'Vorbedingung: Doctrine liefert dieselbe Instanz');

        $this->registry->scheduleRemoval($email, $neuGeladen);
        $this->em->flush();

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail($email)->getSyncState(),
            'Die auslösende Quelle hat sich selbst am Leben gehalten',
        );
    }
}
