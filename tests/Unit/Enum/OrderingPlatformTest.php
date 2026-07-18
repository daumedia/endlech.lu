<?php

namespace App\Tests\Unit\Enum;

use App\Enum\OrderingPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderingPlatformTest extends TestCase
{
    public function testEveryCaseHasNonEmptyLabelAndEmoji(): void
    {
        foreach (OrderingPlatform::cases() as $platform) {
            self::assertNotSame('', $platform->label(), $platform->value.' label');
            self::assertNotSame('', $platform->emoji(), $platform->value.' emoji');
        }
    }

    public function testLabels(): void
    {
        self::assertSame('Uber Eats', OrderingPlatform::UBER_EATS->label());
        self::assertSame('Telefon', OrderingPlatform::PHONE->label());
        self::assertSame('Andere', OrderingPlatform::OTHER->label());
    }

    public function testActionLabel(): void
    {
        self::assertSame('Anrufen', OrderingPlatform::PHONE->actionLabel());
        self::assertSame('Zur Webseite', OrderingPlatform::WEBSITE->actionLabel());
        // Alle übrigen Plattformen fallen auf den Default 'Bestellen' zurück.
        self::assertSame('Bestellen', OrderingPlatform::UBER_EATS->actionLabel());
        self::assertSame('Bestellen', OrderingPlatform::OTHER->actionLabel());
    }

    /**
     * @return iterable<string, array{OrderingPlatform, ?string}>
     */
    public static function logoPathProvider(): iterable
    {
        yield 'uber_eats' => [OrderingPlatform::UBER_EATS, 'images/platforms/uber-eats.svg'];
        yield 'deliveroo' => [OrderingPlatform::DELIVEROO, 'images/platforms/deliveroo.svg'];
        yield 'just_eat' => [OrderingPlatform::JUST_EAT, 'images/platforms/just-eat.svg'];
        yield 'wolt' => [OrderingPlatform::WOLT, 'images/platforms/wolt.svg'];
        yield 'wedely' => [OrderingPlatform::WEDELY, 'images/platforms/wedely.svg'];
        yield 'goosty' => [OrderingPlatform::GOOSTY, 'images/platforms/goosty.svg'];
        yield 'phone' => [OrderingPlatform::PHONE, null];
        yield 'website' => [OrderingPlatform::WEBSITE, null];
        yield 'other' => [OrderingPlatform::OTHER, null];
    }

    #[DataProvider('logoPathProvider')]
    public function testLogoPath(OrderingPlatform $platform, ?string $expected): void
    {
        self::assertSame($expected, $platform->logoPath());
    }

    public function testTransKeys(): void
    {
        self::assertSame('ordering_platform.wolt', OrderingPlatform::WOLT->transKey());
        self::assertSame('ordering_action.phone', OrderingPlatform::PHONE->actionTransKey());
    }
}
