<?php

namespace App\Tests\Enum;

use App\Enum\Language;
use PHPUnit\Framework\TestCase;

final class LanguageTest extends TestCase
{
    public function testEveryCaseHasLabelFlagAndTransKey(): void
    {
        foreach (Language::cases() as $language) {
            self::assertNotSame('', $language->label(), $language->value.' label');
            self::assertNotSame('', $language->flag(), $language->value.' flag');
            self::assertSame('language.'.$language->value, $language->transKey());
        }
    }

    public function testLabels(): void
    {
        self::assertSame('Lëtzebuergesch', Language::LU->label());
        self::assertSame('Deutsch', Language::DE->label());
        self::assertSame('Français', Language::FR->label());
        self::assertSame('English', Language::EN->label());
        self::assertSame('Português', Language::PT->label());
        self::assertSame('Andere', Language::OTHER->label());
    }

    public function testFlags(): void
    {
        self::assertSame('🇱🇺', Language::LU->flag());
        self::assertSame('🇬🇧', Language::EN->flag());
        self::assertSame('🌐', Language::OTHER->flag());
    }

    public function testBadgeLabelCombinesFlagAndLabel(): void
    {
        self::assertSame('🇱🇺 Lëtzebuergesch', Language::LU->badgeLabel());
        self::assertSame('🇫🇷 Français', Language::FR->badgeLabel());
    }
}
