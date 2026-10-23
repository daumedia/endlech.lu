<?php

declare(strict_types=1);

namespace App\Tests\Unit\Marketing;

use App\Entity\MarketingContact;
use App\Enum\MarketingOrigin;
use App\Marketing\MarketingPayloadMapper;
use PHPUnit\Framework\TestCase;

/**
 * Feature 12 — die eine Ausnahme von „`emailBlacklisted` steht nie im Rumpf" (04/EC-05).
 *
 * Wer bei uns abbestellt und danach ausdrücklich wieder einwilligt, ist bei Brevo gesperrt.
 * Ohne Aufhebung stünde im Profil „abonniert", während Brevo nichts zustellt (AK-35). Die
 * Ausnahme gilt nur, wenn **wir** die Sperre kennen und die Einwilligung jünger ist — eine
 * Abmeldung, von der nur Brevo weiß, bleibt unangetastet (EC-05).
 */
final class Feature12PayloadMapperTest extends TestCase
{
    private function kontakt(?string $revoked, string $consent = '2026-10-22 10:00:00'): MarketingContact
    {
        $kontakt = new MarketingContact();
        $kontakt->setEmail('f12-mapper@qa.lu')
            ->setOrigin(MarketingOrigin::ACCOUNT)
            ->setLocale('de')
            ->setConsentAt(new \DateTimeImmutable($consent));

        if (null !== $revoked) {
            $kontakt->setRevokedAt(new \DateTimeImmutable($revoked));
        }

        (new \ReflectionProperty(MarketingContact::class, 'id'))->setValue($kontakt, 42);

        return $kontakt;
    }

    public function testEc05OhneBekannteSperreFehltDasFeld(): void
    {
        $rumpf = (new MarketingPayloadMapper('7'))->toBrevoPayload($this->kontakt(null));

        self::assertArrayNotHasKey('emailBlacklisted', $rumpf);
    }

    public function testAk35WiederEingewilligtHebtDieSperreAuf(): void
    {
        $rumpf = (new MarketingPayloadMapper('7'))->toBrevoPayload(
            $this->kontakt(revoked: '2026-10-22 09:00:00', consent: '2026-10-22 10:00:00'),
        );

        self::assertArrayHasKey('emailBlacklisted', $rumpf);
        self::assertFalse($rumpf['emailBlacklisted']);
    }

    public function testNochGesperrtSchicktKeineAufhebung(): void
    {
        // Gesperrt und nicht wieder eingewilligt: Der Lauf überträgt so eine Zeile ohnehin nicht,
        // aber der Mapper darf auch für sich genommen keine Aufhebung bauen.
        $rumpf = (new MarketingPayloadMapper('7'))->toBrevoPayload(
            $this->kontakt(revoked: '2026-10-22 11:00:00', consent: '2026-10-22 10:00:00'),
        );

        self::assertArrayNotHasKey('emailBlacklisted', $rumpf);
    }

    public function testAk56AttributlisteBleibtGenauDieAus04(): void
    {
        $rumpf = (new MarketingPayloadMapper('7'))->toBrevoPayload(
            $this->kontakt(revoked: '2026-10-22 09:00:00'),
        );

        self::assertSame(
            ['CONTACT_NAME', 'ORGANISATION', 'LOCALE', 'ORIGIN', 'FUNNEL_STATUS'],
            array_keys($rumpf['attributes']),
        );
        self::assertSame(
            ['email', 'ext_id', 'updateEnabled', 'attributes', 'listIds', 'emailBlacklisted'],
            array_keys($rumpf),
        );
    }
}
