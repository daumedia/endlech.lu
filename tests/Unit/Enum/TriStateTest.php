<?php

namespace App\Tests\Unit\Enum;

use App\Enum\TriState;
use PHPUnit\Framework\TestCase;

final class TriStateTest extends TestCase
{
    public function testEveryCaseHasLabelEmojiAndTransKey(): void
    {
        foreach (TriState::cases() as $state) {
            self::assertNotSame('', $state->label(), $state->value.' label');
            self::assertNotSame('', $state->emoji(), $state->value.' emoji');
            self::assertSame('tristate.'.$state->value, $state->transKey());
        }
    }

    public function testValues(): void
    {
        self::assertSame('yes', TriState::YES->value);
        self::assertSame('no', TriState::NO->value);
        self::assertSame('unknown', TriState::UNKNOWN->value);
    }

    public function testLabels(): void
    {
        self::assertSame('Ja', TriState::YES->label());
        self::assertSame('Nein', TriState::NO->label());
        self::assertSame('Weiß nicht', TriState::UNKNOWN->label());
    }

    /**
     * Nur YES ist eine Zusage – "Weiß nicht" darf niemals als Ja durchgehen.
     */
    public function testOnlyYesCountsAsYes(): void
    {
        self::assertTrue(TriState::YES->isYes());
        self::assertFalse(TriState::NO->isYes());
        self::assertFalse(TriState::UNKNOWN->isYes());
    }
}
