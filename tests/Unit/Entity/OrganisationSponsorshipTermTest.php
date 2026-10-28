<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\SponsorshipTerm;
use PHPUnit\Framework\TestCase;

/**
 * Laufzeit des Sponsorings und Altdaten am Wartelisten-Eintrag (Feature 15, T03).
 */
final class OrganisationSponsorshipTermTest extends TestCase
{
    public function testAk37LaufzeitWirdGespeichertUndGelesen(): void
    {
        $entry = (new OrganisationWaitlistEntry())->setSponsorshipTerm(SponsorshipTerm::YEARLY);

        self::assertSame(SponsorshipTerm::YEARLY, $entry->getSponsorshipTerm());
        self::assertSame(['yearly'], $entry->getSponsorshipInterests());
        self::assertSame([], $entry->getLegacySponsorshipInterests());
    }

    public function testAk37LeerHeisstKeinSponsoring(): void
    {
        $entry = (new OrganisationWaitlistEntry())->setSponsorshipTerm(SponsorshipTerm::MONTHLY)->setSponsorshipTerm(null);

        self::assertNull($entry->getSponsorshipTerm());
        self::assertSame([], $entry->getSponsorshipInterests());
    }

    public function testAk39AlteFormateBleibenLesbar(): void
    {
        $entry = (new OrganisationWaitlistEntry())->setSponsorshipInterests(['inclusion_boxes', 'workshops']);

        self::assertNull($entry->getSponsorshipTerm());
        self::assertSame(['inclusion_boxes', 'workshops'], $entry->getLegacySponsorshipInterests());
    }

    public function testAk39GemischteWerteWerdenGetrennt(): void
    {
        $entry = (new OrganisationWaitlistEntry())->setSponsorshipInterests(['employee_engagement', 'monthly']);

        self::assertSame(SponsorshipTerm::MONTHLY, $entry->getSponsorshipTerm());
        self::assertSame(['employee_engagement'], $entry->getLegacySponsorshipInterests());
    }
}
