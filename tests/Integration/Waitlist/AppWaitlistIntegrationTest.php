<?php

namespace App\Tests\Integration\Waitlist;

use App\Account\AccountDataExporter;
use App\Account\AccountDeleter;
use App\Entity\AppWaitlistEntry;
use App\Entity\User;
use App\Enum\AppPlatform;
use App\Enum\MarketingOrigin;
use App\Open\OpenStatsService;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\MarketingContactRepository;
use App\Waitlist\StaleAppWaitlistCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Die Teile der App-Warteliste, die nicht über HTTP zu belegen sind:
 * Aufräumfrist, Kennzahl-Schwelle, Marketing-Herkunft und die Kopplung an
 * Kontolöschung und Datenexport.
 */
final class AppWaitlistIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    /** AK-47: nie selbst bestätigt und älter als 30 Tage → weg. */
    public function testAufraeumlaufLoeschtNieBestaetigteNach30Tagen(): void
    {
        $alt = $this->eintrag('alt@example.lu');
        $this->altern($alt, '-31 days');

        $geloescht = self::getContainer()->get(StaleAppWaitlistCleaner::class)->sweep();

        self::assertSame(1, $geloescht);
        self::assertNull($this->repository()->findOneByEmail('alt@example.lu'));
    }

    /** AK-48: bestätigte bleiben, unabhängig vom Alter. */
    public function testAufraeumlaufLaesstBestaetigteStehen(): void
    {
        $alt = $this->eintrag('bestaetigt-alt@example.lu');
        $alt->confirm();
        $this->em->flush();
        $this->altern($alt, '-400 days');

        self::getContainer()->get(StaleAppWaitlistCleaner::class)->sweep();

        self::assertNotNull($this->repository()->findOneByEmail('bestaetigt-alt@example.lu'));
    }

    /**
     * ⚠ Der Lauf hängt an `selfConfirmedAt`, NICHT am Status. Ein vom Admin
     * weitergesetzter Eintrag steht nicht mehr auf `pending` und entginge einer
     * Statusprüfung — obwohl nie jemand bestätigt hat (BF-89).
     */
    public function testAufraeumlaufGreiftAuchBeiVerwaltungsseitigWeitergesetztenEintraegen(): void
    {
        $alt = $this->eintrag('weitergesetzt@example.lu');
        $alt->setStatus(\App\Enum\WaitlistStatus::CONTACTED);
        $alt->setConfirmedAt(new \DateTimeImmutable());   // Backfill der Verwaltung
        $this->em->flush();
        $this->altern($alt, '-31 days');

        self::assertSame(1, self::getContainer()->get(StaleAppWaitlistCleaner::class)->sweep());
    }

    /**
     * ⚠ Der Cache-Schlüssel wird zuerst geleert. DAMA isoliert die **Datenbank**
     * je Test, nicht `cache.app` — dort steht die Tagessperre, und sie überlebt
     * sowohl den einzelnen Test als auch den ganzen Lauf. Ohne diese Zeile ist
     * der Test grün oder rot, je nachdem was vorher lief.
     */
    public function testZweiterLaufAmSelbenTagLaeuftNichtNochEinmal(): void
    {
        self::getContainer()->get(\Psr\Cache\CacheItemPoolInterface::class)
            ->deleteItem('app_waitlist.cleanup.last_run');

        $cleaner = self::getContainer()->get(StaleAppWaitlistCleaner::class);
        $this->altern($this->eintrag('erst@example.lu'), '-31 days');

        self::assertSame(1, $cleaner->sweepOncePerDay());

        $this->altern($this->eintrag('dann@example.lu'), '-31 days');
        self::assertSame(0, $cleaner->sweepOncePerDay(), 'AK-49: höchstens ein Lauf je Kalendertag');
    }

    /**
     * AK-37: Unterhalb der Schwelle fehlen die Schlüssel im Array – sie werden
     * nicht im Template verborgen. Andernfalls stünden sie über /open.json offen.
     */
    public function testKennzahlFehltUnterhalbDerSchwelle(): void
    {
        $stats = self::getContainer()->get(OpenStatsService::class)->platform();

        self::assertArrayNotHasKey('appWaitlistTotal', $stats);
        self::assertArrayNotHasKey('appWaitlistIos', $stats);
        self::assertArrayNotHasKey('appWaitlistAndroid', $stats);
    }

    /** AK-38: ab der Schwelle stehen alle drei Zahlen da. */
    public function testKennzahlErscheintAbDerSchwelle(): void
    {
        $ios = OpenStatsService::APP_WAITLIST_MIN - 3;

        for ($i = 0; $i < $ios; ++$i) {
            $this->eintrag("ios{$i}@example.lu", AppPlatform::IOS)->confirm();
        }
        for ($i = 0; $i < 3; ++$i) {
            $this->eintrag("android{$i}@example.lu", AppPlatform::ANDROID)->confirm();
        }
        $this->em->flush();

        $stats = self::getContainer()->get(OpenStatsService::class)->platform();

        self::assertSame(OpenStatsService::APP_WAITLIST_MIN, $stats['appWaitlistTotal']);
        self::assertSame($ios, $stats['appWaitlistIos']);
        self::assertSame(3, $stats['appWaitlistAndroid']);
    }

    /** Unbestätigte zählen nicht mit – sonst wäre die Zahl über das Formular aufblasbar. */
    public function testUnbestaetigteZaehlenNichtMit(): void
    {
        for ($i = 0; $i < OpenStatsService::APP_WAITLIST_MIN + 10; ++$i) {
            $this->eintrag("offen{$i}@example.lu");
        }
        $this->em->flush();

        self::assertArrayNotHasKey(
            'appWaitlistTotal',
            self::getContainer()->get(OpenStatsService::class)->platform(),
        );
    }

    /**
     * AK-54: Die Herkunft ist `app` – nicht `account`. Ohne den eigenen Case
     * fiele die neue Quelle in den ACCOUNT-Zweig von `originOf()`.
     */
    public function testBestaetigterEintragMitEinwilligungWirdAlsAppQuelleVermerkt(): void
    {
        $entry = $this->eintrag('werbung@example.lu');
        $entry->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->confirm();
        $this->em->flush();

        self::getContainer()->get(\App\Marketing\MarketingContactRegistry::class)
            ->recordWaitlistEntry($entry);
        $this->em->flush();

        $kontakt = self::getContainer()->get(MarketingContactRepository::class)
            ->findOneByEmail('werbung@example.lu');

        self::assertNotNull($kontakt);
        self::assertSame(MarketingOrigin::APP, $kontakt->getOrigin());
    }

    /** AK-50: Kontolöschung nimmt die Vormerkung unter derselben Adresse mit. */
    public function testKontoloeschungEntferntDieVormerkung(): void
    {
        $user = $this->konto('loeschen@example.lu');
        $this->eintrag('loeschen@example.lu');
        $this->em->flush();

        self::getContainer()->get(AccountDeleter::class)->delete($user);

        self::assertNull($this->repository()->findOneByEmail('loeschen@example.lu'));
    }

    /** AK-51: der Export weist sie aus – und zwar ohne den Token. */
    public function testDatenexportEnthaeltDieVormerkungOhneToken(): void
    {
        $user = $this->konto('export@example.lu');
        $entry = $this->eintrag('export@example.lu', AppPlatform::ANDROID);
        $this->em->flush();

        $export = self::getContainer()->get(AccountDataExporter::class)->export($user);

        self::assertArrayHasKey('appWaitlist', $export);
        self::assertSame('android', $export['appWaitlist']['platform']);
        self::assertStringNotContainsString(
            (string) $entry->getConfirmationToken(),
            json_encode($export, \JSON_THROW_ON_ERROR),
            'Der Token ist ein Zugangsgeheimnis und gehört nicht in eine Auskunft.',
        );
    }

    /** AK-41: keine IP-Adresse, kein Name – die Feldliste ist abschließend. */
    public function testEintragTraegtKeineIpUndKeinenNamen(): void
    {
        $spalten = array_map(
            static fn (array $c): string => $c['name'],
            $this->em->getConnection()->createSchemaManager()->listTableColumns('app_waitlist_entry')
                ? array_map(
                    static fn ($c) => ['name' => $c->getName()],
                    $this->em->getConnection()->createSchemaManager()->listTableColumns('app_waitlist_entry'),
                )
                : [],
        );

        self::assertNotContains('ip', $spalten);
        self::assertNotContains('ip_address', $spalten);
        self::assertNotContains('name', $spalten);
        self::assertNotContains('contact_name', $spalten);
    }

    /**
     * BF-122 — Die Aufbewahrungsfrist darf sich nicht durch wiederholtes
     * Eintragen verlängern lassen.
     *
     * Der Bestätigungslink ist erneuerbar (AK-17), die **Aufbewahrung** nicht:
     * Ohne eingelöste Bestätigung liegt keine Einwilligung vor, und daran ändert
     * ein weiterer Absendevorgang nichts.
     */
    public function testBf122ErneuernVerlaengertDieAufbewahrungNicht(): void
    {
        $entry = $this->eintrag('dauergast@example.lu');

        // Erstkontakt vor 29 Tagen; der Bestätigungslink ist längst abgelaufen.
        $this->altern($entry, '-29 days');

        // Jemand trägt dieselbe Adresse erneut ein — der Ablauf-Zweig erneuert
        // Frist und Token (BF-117), `consentAt` bleibt der Erstkontakt.
        $entry->renewConfirmationWindow();
        $this->em->flush();

        // Der Aufräumlauf zwei Tage später: Tag 31 seit dem Erstkontakt.
        $geloescht = self::getContainer()->get(StaleAppWaitlistCleaner::class)
            ->sweep(new \DateTimeImmutable('+2 days'));

        self::assertSame(1, $geloescht, 'BF-122: die Frist misst am Erstkontakt, nicht am letzten Erneuern');
        self::assertNull($this->repository()->findOneByEmail('dauergast@example.lu'));
    }

    /** BF-122 — mehrfaches Erneuern hilft ebenso wenig. */
    public function testBf122AuchMehrfachesErneuernHaeltDenEintragNichtAmLeben(): void
    {
        $entry = $this->eintrag('hartnaeckig@example.lu');
        $this->altern($entry, '-29 days');

        for ($runde = 0; $runde < 5; ++$runde) {
            $entry->renewConfirmationWindow();
            $this->em->flush();
        }

        self::assertSame(
            1,
            self::getContainer()->get(StaleAppWaitlistCleaner::class)
                ->sweep(new \DateTimeImmutable('+2 days')),
        );
    }

    /**
     * Die Gegenprobe: Der erneuerte Bestätigungslink bleibt einlösbar, solange
     * die Aufbewahrungsfrist läuft. BF-117 darf durch die Reparatur nicht
     * zurückkommen.
     */
    public function testBf122ErneuerterLinkBleibtInnerhalbDerFristGueltig(): void
    {
        $entry = $this->eintrag('rechtzeitig@example.lu');
        $this->altern($entry, '-8 days');

        $entry->renewConfirmationWindow();
        $this->em->flush();

        self::assertFalse(
            self::getContainer()->get(\App\Waitlist\WaitlistConfirmationService::class)
                ->isExpired($entry),
            'Der Bestätigungslink bleibt erneuerbar — nur die Aufbewahrung nicht.',
        );
        self::assertSame(0, self::getContainer()->get(StaleAppWaitlistCleaner::class)->sweep());
    }

    // ---------------------------------------------------------------- Hilfen

    private function repository(): AppWaitlistEntryRepository
    {
        return self::getContainer()->get(AppWaitlistEntryRepository::class);
    }

    private function eintrag(string $email, AppPlatform $platform = AppPlatform::IOS): AppWaitlistEntry
    {
        $entry = new AppWaitlistEntry();
        $entry->setEmail($email);
        $entry->setPlatform($platform);
        $entry->setLocale('de');
        $entry->generateConfirmationToken();

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    private function konto(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Testkonto');
        $user->setPassword('x');
        $user->setIsVerified(true);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Verschiebt den Eintrag in die Vergangenheit — **beide** Zeitpunkte.
     *
     * ⚠ `consentAt` gehört dazu: Es markiert den Erstkontakt, und daran misst
     * die Aufbewahrungsfrist seit BF-122. Ein Test, der nur `createdAt` altert,
     * bildet einen Zustand ab, den es nicht gibt.
     */
    private function altern(AppWaitlistEntry $entry, string $versatz): void
    {
        $wert = (new \DateTimeImmutable($versatz))->format('Y-m-d H:i:s');

        $this->em->getConnection()->executeStatement(
            'UPDATE app_waitlist_entry SET created_at = :d, consent_at = :d WHERE id = :id',
            ['d' => $wert, 'id' => $entry->getId()],
        );
        $this->em->refresh($entry);
    }
}
