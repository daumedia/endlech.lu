<?php

namespace App\Tests\Integration\Marketing;

use App\Account\AccountDeleter;
use App\Entity\PartnerWaitlistEntry;
use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Die Löschkaskade (Feature 04, AK-13/AK-14/AK-16).
 *
 * ⚠ Der Kern: **Der Löschauftrag muss die Löschung seiner Quelle überleben.**
 * Ein Widerruf entfernt den Wartelisten-Eintrag; hinge der Auftrag an einem
 * Fremdschlüssel, verschwände er mit ihm — und die Adresse bliebe für immer
 * bei Brevo stehen. Eine Löschung nach Art. 17, die einen Kontakt bei einem
 * Dritten stehen lässt, ist keine.
 */
final class MarketingLoeschkaskadeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MarketingContactRepository $contacts;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
    }

    /**
     * ⚠ AK-13: Der Wartelisten-Widerruf löscht den Eintrag **und** hinterlässt
     * einen Löschauftrag, der ihn überlebt.
     */
    public function testAk13WiderrufHinterlaesstEinenAuftragDerDieQuelleUeberlebt(): void
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Widerruf-Haus')
            ->setContactName('Kontakt')
            ->setEmail('widerruf@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->confirm();

        $this->em->persist($entry);
        $this->em->flush();

        static::getContainer()->get(MarketingContactRegistry::class)->recordWaitlistEntry($entry);
        $this->em->flush();

        self::assertNotNull($this->contacts->findOneByEmail('widerruf@qa.lu'));

        $ergebnis = static::getContainer()->get(WaitlistConfirmationService::class)->revoke($entry);

        self::assertSame(WaitlistConfirmationService::RESULT_REVOKED, $ergebnis);

        // Die Quelle ist weg …
        $verbliebene = $this->em->getConnection()
            ->fetchOne("SELECT COUNT(*) FROM partner_waitlist_entry WHERE email = 'widerruf@qa.lu'");
        self::assertSame(0, (int) $verbliebene, 'Der Wartelisten-Eintrag steht noch da');

        // … der Auftrag lebt.
        $kontakt = $this->contacts->findOneByEmail('widerruf@qa.lu');
        self::assertNotNull($kontakt, 'Der Löschauftrag ist mit seiner Quelle verschwunden');
        self::assertSame(MarketingSyncState::REMOVAL_PENDING, $kontakt->getSyncState());
    }

    /**
     * ⚠ AK-14: Die Kontolöschung nimmt den Brevo-Kontakt mit.
     */
    public function testAk14KontoloeschungStelltDenLoeschauftrag(): void
    {
        $user = new User();
        $user->setName('Löschkandidat')
            ->setEmail('konto-weg@qa.lu')
            ->setPassword('irrelevant')
            ->setIsVerified(true)
            ->setMarketingConsentAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        static::getContainer()->get(MarketingContactRegistry::class)->recordUser($user);
        $this->em->flush();

        self::assertNotNull($this->contacts->findOneByEmail('konto-weg@qa.lu'));

        static::getContainer()->get(AccountDeleter::class)->delete($user);

        $verbliebene = $this->em->getConnection()
            ->fetchOne("SELECT COUNT(*) FROM `user` WHERE email = 'konto-weg@qa.lu'");
        self::assertSame(0, (int) $verbliebene, 'Das Konto steht noch da');

        $kontakt = $this->contacts->findOneByEmail('konto-weg@qa.lu');
        self::assertNotNull($kontakt, 'Der Brevo-Kontakt wurde nicht zur Löschung vorgemerkt');
        self::assertSame(MarketingSyncState::REMOVAL_PENDING, $kontakt->getSyncState());
    }

    /**
     * AK-45: Nach einem Widerruf ist die Adresse wieder frei — ein späterer
     * erneuter Eintrag funktioniert.
     */
    public function testAk45AdresseIstNachWiderrufWiederFrei(): void
    {
        $erste = new PartnerWaitlistEntry();
        $erste->setRestaurantName('Erstes Haus')
            ->setContactName('Kontakt')
            ->setEmail('wieder-frei@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable('-1 hour'));
        $erste->confirm();

        $this->em->persist($erste);
        $this->em->flush();

        static::getContainer()->get(WaitlistConfirmationService::class)->revoke($erste);

        // Erneuter Eintrag mit derselben Adresse.
        $zweite = new PartnerWaitlistEntry();
        $zweite->setRestaurantName('Zweites Haus')
            ->setContactName('Kontakt')
            ->setEmail('wieder-frei@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $zweite->confirm();

        $this->em->persist($zweite);
        $this->em->flush();

        self::assertNotNull(
            static::getContainer()->get(MarketingContactRegistry::class)->recordWaitlistEntry($zweite),
            'Die Adresse ist nach dem Widerruf blockiert geblieben',
        );
    }
}
