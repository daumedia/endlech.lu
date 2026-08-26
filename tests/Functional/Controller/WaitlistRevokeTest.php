<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\PartnerWaitlistEntry;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * BF-37 / Feature 01 (US-04): Eine Einwilligung lässt sich zurückziehen.
 *
 * Es gab keine Route, keinen Abmeldelink und keine Löschfunktion — nach
 * Art. 7 Abs. 3 DSGVO muss ein Widerruf „ebenso einfach" sein wie die
 * Einwilligung, und die war ein Klick.
 */
final class WaitlistRevokeTest extends AbstractWebTestCase
{
    private function eintrag(EntityManagerInterface $em): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Widerruf Probe');
        $entry->setContactName('QA');
        $entry->setEmail('widerruf-'.uniqid().'@example.test');
        $entry->setLocality('Wiltz');
        $entry->setConsentAt(new \DateTimeImmutable());
        $entry->setLocale('de');
        $entry->generateConfirmationToken();

        $em->persist($entry);
        $em->flush();

        return $entry;
    }

    /**
     * AK-20: Der Eintrag wird gelöscht, nicht markiert.
     *
     * Ein Widerruf, nach dem Name, Adresse und Einwilligungszeitpunkt weiterhin
     * in der Datenbank stehen, ist keiner.
     */
    public function testWiderrufLoeschtDenEintrag(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $entry = $this->eintrag($em);
        $token = (string) $entry->getConfirmationToken();
        $id = $entry->getId();

        $crawler = $client->request('GET', self::LOCALE.'/partner/abmelden/'.$token);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('nicht mehr auf der Liste', $crawler->filter('body')->text());

        $em->clear();
        self::assertNull(
            $em->getRepository(PartnerWaitlistEntry::class)->find($id),
            'Der Eintrag steht noch in der Datenbank.',
        );
    }

    /**
     * AK-21: Derselbe Link ein zweites Mal endet in einer Antwort, nicht in
     * einem Fehler — der Eintrag ist ja bereits weg.
     */
    public function testZweiterAufrufIstKeinFehler(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = (string) $this->eintrag($em)->getConfirmationToken();

        $client->request('GET', self::LOCALE.'/partner/abmelden/'.$token);
        $crawler = $client->request('GET', self::LOCALE.'/partner/abmelden/'.$token);

        self::assertResponseStatusCodeSame(404);
        self::assertStringContainsString('ins Leere', $crawler->filter('body')->text());
    }

    /**
     * AK-19: Der Link steht in der Bestätigungsmail.
     */
    public function testBestaetigungsmailEnthaeltDenAbmeldelink(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', self::LOCALE.'/partner');
        $formular = $this->formWithField($crawler, 'partner_waitlist[restaurantName]');
        $formular['partner_waitlist[restaurantName]'] = 'Abmeldelink Probe';
        $formular['partner_waitlist[contactName]'] = 'QA';
        $formular['partner_waitlist[email]'] = 'abmeldelink-'.uniqid().'@example.test';
        $formular['partner_waitlist[locality]'] = 'Wiltz';
        $formular['partner_waitlist[consent]'] = '1';
        $client->submit($formular);

        self::assertEmailCount(1);
        self::assertStringContainsString('/partner/abmelden/', (string) self::getMailerMessage()?->getHtmlBody());
    }

    /**
     * EC-04: Ein unbekannter Token läuft ins Leere, ohne einen Fehler zu erzeugen.
     */
    public function testUnbekannterTokenLaeuftInsLeere(): void
    {
        $client = static::createClient();

        $client->request('GET', self::LOCALE.'/partner/abmelden/'.str_repeat('c', 64));

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Der Widerruf trifft nur den eigenen Eintrag (AK-23).
     */
    public function testWiderrufTrifftNurDenEigenenEintrag(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $meiner = $this->eintrag($em);
        $fremder = $this->eintrag($em);
        $fremdeId = $fremder->getId();

        $client->request('GET', self::LOCALE.'/partner/abmelden/'.$meiner->getConfirmationToken());

        $em->clear();
        self::assertNotNull(
            $em->getRepository(PartnerWaitlistEntry::class)->find($fremdeId),
            'Der Widerruf hat einen fremden Eintrag getroffen.',
        );
    }
}
