<?php

declare(strict_types=1);

namespace App\Tests\Integration\Sponsor;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\SponsorshipTerm;
use App\Marketing\MarketingContactRegistry;
use App\Marketing\MarketingPayloadMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * QA Feature 15 (2026-10-28) · Spec, Katalogfrage 2: „Brevo bekommt aus der Warteliste dieselben Felder wie bisher — die
 * Laufzeit geht **nicht** mit.“ Geprüft am tatsächlichen Brevo-Payload, nicht am Quelltext, der ihn baut.
 */
final class Qa15BrevoLaufzeitTest extends KernelTestCase
{
    public function testDieLaufzeitGehtNichtNachBrevo(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $payloads = [];
        foreach (SponsorshipTerm::cases() as $nr => $term) {
            $entry = (new OrganisationWaitlistEntry())
                ->setType(OrganisationType::COMPANY)
                ->setOrganisationName('QA15 Laufzeit '.$nr.' SA')
                ->setContactName('Probe')
                ->setEmail('qa15-brevo-'.$nr.'@example.org')
                ->setLocale('de')
                ->setSponsorshipTerm($term)
                ->setMarketingConsentAt(new \DateTimeImmutable());
            $entry->confirm();
            $em->persist($entry);
            $em->flush();

            $contact = static::getContainer()->get(MarketingContactRegistry::class)->recordWaitlistEntry($entry);
            self::assertNotNull($contact, 'Mit Einwilligung und eigener Bestätigung entsteht ein Kontakt — sonst prüfte der Test nichts.');
            $em->flush();
            $payloads[$nr] = json_encode(
                static::getContainer()->get(MarketingPayloadMapper::class)->toBrevoPayload($contact),
                \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
            );
        }

        foreach ($payloads as $nr => $json) {
            $wert = SponsorshipTerm::cases()[$nr]->value;
            self::assertStringContainsString('qa15-brevo-'.$nr.'@example.org', $json, 'Der Payload ist der des Eintrags.');
            self::assertDoesNotMatchRegularExpression('/monthly|yearly|monatlich|jährlich|sponsorship/i', $json, "Laufzeit „$wert“ steht im Brevo-Payload: $json");
        }
    }
}
