<?php

namespace App\Tests\Unit\Enum;

use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use PHPUnit\Framework\TestCase;

final class FinanceCategoryTest extends TestCase
{
    public function testEveryCaseHasLabelEmojiAndTransKey(): void
    {
        foreach (FinanceCategory::cases() as $category) {
            self::assertNotSame('', $category->label(), $category->value.' label');
            self::assertNotSame('', $category->emoji(), $category->value.' emoji');
            self::assertSame('finance_category.'.$category->value, $category->transKey());
        }
    }

    public function testCasesForSplitsAllCategoriesWithoutOverlap(): void
    {
        $value = static fn (FinanceCategory $category) => $category->value;
        $income = array_map($value, FinanceCategory::casesFor(FinanceType::INCOME));
        $expense = array_map($value, FinanceCategory::casesFor(FinanceType::EXPENSE));

        self::assertSame([], array_intersect($income, $expense), 'Keine Kategorie darf auf beiden Seiten stehen.');
        self::assertCount(\count(FinanceCategory::cases()), array_merge($income, $expense), 'Jede Kategorie muss genau einer Seite zugeordnet sein.');
    }

    public function testOnlyInclusionBoxMaterialsTracksQuantity(): void
    {
        foreach (FinanceCategory::cases() as $category) {
            self::assertSame(
                FinanceCategory::INCLUSION_BOX_MATERIALS === $category,
                $category->tracksQuantity(),
                $category->value.' tracksQuantity',
            );
        }
    }

    public function testSignIsPositiveForIncomeAndNegativeForExpense(): void
    {
        self::assertSame(1, FinanceType::INCOME->sign());
        self::assertSame(-1, FinanceType::EXPENSE->sign());
    }
}
