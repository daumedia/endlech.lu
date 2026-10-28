<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\SponsorshipTerm;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mime\Email;

/**
 * Feature 15 · Laufzeit und Altdaten in Verwaltung und interner Meldung (T17).
 */
final class WaitlistLegacyTest extends AbstractWebTestCase
{
    /** @param string[] $interessen */
    private function eintrag(KernelBrowser $client, array $interessen): OrganisationWaitlistEntry
    {
        $entry = (new OrganisationWaitlistEntry())
            ->setType(OrganisationType::COMPANY)
            ->setOrganisationName('Firma '.uniqid())
            ->setContactName('Test Person')
            ->setEmail(uniqid().'@example.lu')
            ->setLocale('de')
            ->setSponsorshipInterests($interessen);
        $entry->generateConfirmationToken();

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist($entry);
        $em->flush();

        return $entry;
    }

    public function testAk39AlteFormateBleibenLesbarMitVermerk(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $id = $this->eintrag($client, ['inclusion_boxes', 'employee_engagement'])->getId();

        $client->request('GET', self::LOCALE.'/admin/warteliste/organisation/'.$id);

        self::assertResponseIsSuccessful();
        $text = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Inclusion-Boxen finanzieren', $text);
        self::assertStringContainsString('Mitarbeitende als Testpersonen', $text);
        self::assertStringContainsString('Frühere Angabe (vor dem Paket)', $text);
    }

    public function testAk38VerwaltungZeigtDieLaufzeit(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $id = $this->eintrag($client, [SponsorshipTerm::YEARLY->value])->getId();

        $client->request('GET', self::LOCALE.'/admin/warteliste/organisation/'.$id);

        self::assertResponseIsSuccessful();
        $text = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Ja, jährlich', $text);
        self::assertStringNotContainsString('Frühere Angabe', $text);
    }

    public function testAk38InterneMeldungNenntDieLaufzeitAufDeutsch(): void
    {
        $client = static::createClient();
        $token = (string) $this->eintrag($client, [SponsorshipTerm::MONTHLY->value])->getConfirmationToken();

        $client->request('GET', '/fr/organisationen/confirmation/'.$token);
        self::assertResponseIsSuccessful();

        $mail = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $mail);
        self::assertStringContainsString('Ja, monatlich', (string) $mail->getHtmlBody());
        self::assertStringNotContainsString('monthly', (string) $mail->getHtmlBody(), 'Übersetzt, nicht der Rohwert.');
    }

    public function testAk39InterneMeldungKennzeichnetAlteFormate(): void
    {
        $client = static::createClient();
        $token = (string) $this->eintrag($client, ['workshops'])->getConfirmationToken();

        $client->request('GET', self::LOCALE.'/organisationen/confirmation/'.$token);

        $html = (string) self::getMailerMessage()?->getHtmlBody();
        self::assertStringContainsString('Workshops finanzieren', $html);
        self::assertStringContainsString('Frühere Angabe', $html);
    }
}
