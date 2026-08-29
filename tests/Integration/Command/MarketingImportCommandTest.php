<?php

namespace App\Tests\Integration\Command;

use App\Entity\PartnerWaitlistEntry;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Die Bestandsübertragung `app:marketing:import` (Feature 04).
 *
 * ⚠ Der Trockenlauf ist der **Vorgabefall** — umgekehrt zum `--force` von
 * `app:metrics:snapshot`, und das mit Absicht: Die gefährliche Richtung
 * braucht die Flagge. Ein falsch gefilterter Lauf ist nicht zurückzuholen,
 * die Mails sind dann raus.
 */
final class MarketingImportCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommandTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->tester = new CommandTester(
            (new Application(static::$kernel))->find('app:marketing:import'),
        );
    }

    private function anzahlImAuftragsbuch(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM marketing_contact');
    }

    private function partner(string $email, bool $consent, bool $confirmed): void
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Haus ' . $email)
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht');

        if ($consent) {
            $entry->setMarketingConsentAt(new \DateTimeImmutable());
        }

        if ($confirmed) {
            $entry->confirm();
        }

        $this->em->persist($entry);
        $this->em->flush();
    }

    /**
     * ⚠ AK-21: Ohne `--commit` wird **nichts** geschrieben.
     */
    public function testAk21TrockenlaufSchreibtNichts(): void
    {
        $this->partner('trocken@qa.lu', true, true);
        $vorher = $this->anzahlImAuftragsbuch();

        $this->tester->execute([]);
        $this->tester->assertCommandIsSuccessful();

        self::assertSame($vorher, $this->anzahlImAuftragsbuch(), 'Der Trockenlauf hat geschrieben');
        self::assertStringContainsString('1', $this->tester->getDisplay(), 'Der Trockenlauf nennt die Zahl nicht');
    }

    /**
     * AK-22: Mit `--commit` werden genau die angezeigten Einträge übertragen.
     */
    public function testAk22CommitUebertraegtDieAngezeigtenEintraege(): void
    {
        $this->partner('commit@qa.lu', true, true);
        $vorher = $this->anzahlImAuftragsbuch();

        $this->tester->execute(['--commit' => true]);
        $this->tester->assertCommandIsSuccessful();

        self::assertSame($vorher + 1, $this->anzahlImAuftragsbuch());
    }

    /**
     * ⚠ AK-23: **Nur bestätigte Wartelisten-Einträge mit Einwilligung.**
     *
     * Keine Konten (die haben nur der Nutzung zugestimmt), keine
     * unbestätigten (dort ist nie belegt worden, dass die Adresse dem
     * Anmelder gehört).
     */
    public function testAk23AuswahlregelIstEng(): void
    {
        $this->partner('nimmt-teil@qa.lu', true, true);
        $this->partner('unbestaetigt@qa.lu', true, false);
        $this->partner('ohne-einwilligung@qa.lu', false, true);

        $konto = new User();
        $konto->setName('Konto')
            ->setEmail('konto@qa.lu')
            ->setPassword('x')
            ->setIsVerified(true)
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $this->em->persist($konto);
        $this->em->flush();

        $vorher = $this->anzahlImAuftragsbuch();

        $this->tester->execute(['--commit' => true]);

        self::assertSame($vorher + 1, $this->anzahlImAuftragsbuch(), 'Es wurden mehr oder weniger als der eine gültige Eintrag übertragen');

        $adressen = $this->em->getConnection()->fetchFirstColumn('SELECT email FROM marketing_contact');

        self::assertContains('nimmt-teil@qa.lu', $adressen);
        self::assertNotContains('unbestaetigt@qa.lu', $adressen);
        self::assertNotContains('ohne-einwilligung@qa.lu', $adressen);
        self::assertNotContains('konto@qa.lu', $adressen, 'Ein Nutzerkonto ist in die Bestandsübertragung geraten');
    }

    /**
     * ⚠ BF-89: Die Bestandsübertragung nimmt einen bloß verwaltungsseitig
     * „bestätigten" Eintrag **nicht** mit.
     *
     * Vorher tat sie es — und zeigte ihn dabei selbst als „Unbestätigt" an.
     */
    public function testBf89ImportNimmtBackfillEintraegeNichtMit(): void
    {
        $backfill = new PartnerWaitlistEntry();
        $backfill->setRestaurantName('NUR-BACKFILL')
            ->setContactName('Kontakt')
            ->setEmail('backfill@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $backfill->generateConfirmationToken();
        // Das tut `applyStatus()` – ohne `confirm()`.
        $backfill->setConfirmedAt(new \DateTimeImmutable());
        $this->em->persist($backfill);

        $echt = new PartnerWaitlistEntry();
        $echt->setRestaurantName('ECHTER-DOI')
            ->setContactName('Kontakt')
            ->setEmail('echt@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $echt->confirm();
        $this->em->persist($echt);
        $this->em->flush();

        $this->tester->execute([]);
        $ausgabe = $this->tester->getDisplay();

        self::assertStringNotContainsString('NUR-BACKFILL', $ausgabe, 'BF-89: Der Backfill-Eintrag steht in der Übertragung');
        self::assertStringContainsString('ECHTER-DOI', $ausgabe, 'Der echt bestätigte Eintrag fehlt');
    }

    /**
     * AK-25: Ein zweiter Lauf erzeugt keine Dubletten.
     */
    public function testAk25ZweiterLaufErzeugtKeineDubletten(): void
    {
        $this->partner('zweimal@qa.lu', true, true);

        $this->tester->execute(['--commit' => true]);
        $nachErstem = $this->anzahlImAuftragsbuch();

        $this->tester->execute(['--commit' => true]);

        self::assertSame($nachErstem, $this->anzahlImAuftragsbuch(), 'Der zweite Lauf hat Dubletten erzeugt');
    }

    /**
     * ⚠ AK-31: Die Ausgabe landet im Terminal, in Logdateien und womöglich in
     * einem Ticket — vollständige Adressen haben dort nichts zu suchen.
     */
    public function testAk31AusgabeMaskiertDieAdressen(): void
    {
        // Der Anzeigename darf die Adresse hier nicht enthalten – sonst prüft
        // der Test die Maskierung nicht, sondern den Namen.
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Restaurant ohne Adresse im Namen')
            ->setContactName('Kontakt')
            ->setEmail('geheim@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->confirm();

        $this->em->persist($entry);
        $this->em->flush();

        $this->tester->execute([]);
        $ausgabe = $this->tester->getDisplay();

        self::assertStringNotContainsString('geheim@qa.lu', $ausgabe, 'Die vollständige Adresse steht in der Ausgabe');
        self::assertStringContainsString('@qa.lu', $ausgabe, 'Die Domain sollte zur Einordnung sichtbar bleiben');
    }
}
