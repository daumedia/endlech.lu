<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AppWaitlistEntry;
use App\Enum\AppPlatform;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kriterien, die beim Bau ohne Nachweis blieben (QA 2026-09-05).
 *
 * Sie standen im Aufgabenplan, wurden aber von keinem Test berührt — und ein
 * Kriterium ohne Test ist genau eines: einmal von Hand gesehen.
 */
final class AppWaitlistQaTest extends AbstractWebTestCase
{
    private const PFAD = self::LOCALE.'/app';

    /** AK-10: Mit Turbo wird nur das Formular ersetzt, nicht die Seite geladen. */
    public function testAk10TurboStreamErsetztNurDasFormular(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PFAD);
        $form = $this->formWithField($crawler, 'app_waitlist[email]');
        $form['app_waitlist[email]'] = 'turbo@example.lu';
        $form['app_waitlist[platform]']->select('ios');
        $form['app_waitlist[consent]']->tick();

        $client->submit($form, [], ['HTTP_ACCEPT' => 'text/vnd.turbo-stream.html']);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'turbo-stream',
            (string) $client->getResponse()->headers->get('Content-Type'),
        );
        $inhalt = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('action="replace"', $inhalt);
        self::assertStringContainsString('target="app-waitlist-form"', $inhalt);
    }

    /** AK-12: Der Fehlerfall bleibt HTML — sonst rendert Turbo die Meldungen nicht. */
    public function testAk12FehlerfallBleibtHtmlAuchMitTurbo(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PFAD);

        $client->submit(
            $this->formWithField($crawler, 'app_waitlist[email]'),
            [],
            ['HTTP_ACCEPT' => 'text/vnd.turbo-stream.html'],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringStartsWith(
            'text/html',
            (string) $client->getResponse()->headers->get('Content-Type'),
        );
    }

    /** AK-29: Ohne eingelöste Bestätigung entsteht kein Marketing-Kontakt. */
    public function testAk29PendingErzeugtKeinenMarketingKontakt(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $entry = $this->eintrag($client, 'pending@example.lu');
        $entry->setMarketingConsentAt(new \DateTimeImmutable());
        $em->flush();

        self::assertNull(
            $client->getContainer()->get(MarketingContactRepository::class)
                ->findOneByEmail('pending@example.lu'),
        );
    }

    /** AK-32: Der Widerruf entfernt auch den Werbe-Kontakt. */
    public function testAk32WiderrufRaeumtDenMarketingKontaktAb(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $kontakte = $client->getContainer()->get(MarketingContactRepository::class);

        $entry = $this->eintrag($client, 'widerruf@example.lu');
        $entry->setMarketingConsentAt(new \DateTimeImmutable());
        $em->flush();

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());
        self::assertNotNull($kontakte->findOneByEmail('widerruf@example.lu'), 'Vorbedingung');

        $client->request('GET', self::LOCALE.'/app/abmelden/'.$entry->getConfirmationToken());

        $kontakt = $kontakte->findOneByEmail('widerruf@example.lu');
        self::assertTrue(
            null === $kontakt || null !== $kontakt->getRevokedAt() || $kontakt->getSyncState()->isOpen(),
            'Nach dem Widerruf muss der Kontakt entfernt oder zur Löschung vorgemerkt sein.',
        );
    }

    /** AK-20: Scheitert der Versand, bleibt der Eintrag trotzdem stehen. */
    public function testAk20EintragUeberlebtEinenVersandfehler(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        // Die Reihenfolge im Dienst ist Token → flush → Mail. Der Nachweis ist,
        // dass die Zeile schon steht, bevor der Versand überhaupt beginnt.
        $vorher = (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM app_waitlist_entry');

        $crawler = $client->request('GET', self::PFAD);
        $form = $this->formWithField($crawler, 'app_waitlist[email]');
        $form['app_waitlist[email]'] = 'versand@example.lu';
        $form['app_waitlist[platform]']->select('android');
        $form['app_waitlist[consent]']->tick();
        $client->submit($form);

        $nachher = (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM app_waitlist_entry');
        self::assertSame($vorher + 1, $nachher);
    }

    /** AK-46: eigenes Kontingent — Partner-Submits sperren die App-Warteliste nicht. */
    public function testAk46EigenesKontingentGetrenntVonDenAnderenWartelisten(): void
    {
        $client = static::createClient();
        $c = $client->getContainer();

        self::assertNotSame(
            $c->get('limiter.app_waitlist'),
            $c->get('limiter.partner_waitlist'),
            'BF-38: geteilte Limiter sperren unabhängige Interessenten gegenseitig aus.',
        );
        self::assertNotSame(
            $c->get('limiter.app_waitlist'),
            $c->get('limiter.organisation_waitlist'),
        );
    }

    /** AK-42: kein Feld, das eine besondere Kategorie nach Art. 9 DSGVO tragen könnte. */
    public function testAk42KeineBesonderenKategorien(): void
    {
        $client = static::createClient();
        $spalten = array_values(array_map(
            static fn ($c) => $c->getName(),
            $client->getContainer()->get(EntityManagerInterface::class)
                ->getConnection()->createSchemaManager()->listTableColumns('app_waitlist_entry'),
        ));
        sort($spalten);

        self::assertSame(
            [
                'beta_link_sent_at', 'confirmation_token', 'confirmed_at',
                'consent_at', 'created_at', 'email', 'id', 'locale',
                'marketing_consent_at', 'platform', 'self_confirmed_at',
                'source', 'status', 'updated_at',
            ],
            $spalten,
            'AK-41/AK-42: Die Feldliste ist abschließend. Ein neues Feld ist eine Entscheidung.',
        );
    }

    private function eintrag(KernelBrowser $client, string $email): AppWaitlistEntry
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $e = new AppWaitlistEntry();
        $e->setEmail($email);
        $e->setPlatform(AppPlatform::IOS);
        $e->setLocale('de');
        $e->generateConfirmationToken();
        $em->persist($e);
        $em->flush();

        return $e;
    }
}
