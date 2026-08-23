<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\IncomeExpenses\IncomeExpenseReport;
use PHPUnit\Framework\TestCase;

/**
 * 收支報表計算的測試:總計(排除作廢)與科目比例。
 */
final class IncomeExpenseReportTest extends TestCase
{
    public function test_totals_separates_income_and_expense(): void
    {
        $totals = IncomeExpenseReport::totals([
            ['status' => 'active', 'item_type' => 'income', 'amount' => 8000],
            ['status' => 'active', 'item_type' => 'expense', 'amount' => 3000],
            ['status' => 'active', 'item_type' => 'expense', 'amount' => 2000],
        ]);

        $this->assertSame(8000.0, $totals['income']);
        $this->assertSame(5000.0, $totals['expense']);
        $this->assertSame(3000.0, $totals['balance']);
    }

    public function test_totals_exclude_voided_records(): void
    {
        $totals = IncomeExpenseReport::totals([
            ['status' => 'active', 'item_type' => 'income', 'amount' => 1000],
            ['status' => 'voided', 'item_type' => 'income', 'amount' => 9999],
            ['status' => 'voided', 'item_type' => 'expense', 'amount' => 9999],
        ]);

        $this->assertSame(1000.0, $totals['income']);
        $this->assertSame(0.0, $totals['expense']);
        $this->assertSame(1000.0, $totals['balance']);
    }

    public function test_totals_balance_can_be_negative(): void
    {
        $totals = IncomeExpenseReport::totals([
            ['status' => 'active', 'item_type' => 'income', 'amount' => 100],
            ['status' => 'active', 'item_type' => 'expense', 'amount' => 500],
        ]);

        $this->assertSame(-400.0, $totals['balance']);
    }

    public function test_summary_ratios_use_matching_type_total(): void
    {
        $rows = [
            ['item_type' => 'expense', 'category_name' => '文具', 'subtotal' => 1500],
            ['item_type' => 'expense', 'category_name' => '水電', 'subtotal' => 500],
            ['item_type' => 'income', 'category_name' => '捐款', 'subtotal' => 8000],
        ];

        $withRatios = IncomeExpenseReport::summaryWithRatios($rows, 8000.0, 2000.0);

        $this->assertSame(75.0, $withRatios[0]['ratio']);
        $this->assertSame(25.0, $withRatios[1]['ratio']);
        $this->assertSame(100.0, $withRatios[2]['ratio']);
    }

    public function test_summary_ratio_is_zero_when_base_is_zero(): void
    {
        $rows = [['item_type' => 'expense', 'category_name' => 'x', 'subtotal' => 100]];
        $withRatios = IncomeExpenseReport::summaryWithRatios($rows, 0.0, 0.0);

        $this->assertSame(0.0, $withRatios[0]['ratio']);
    }

    public function test_summary_preserves_original_row_keys(): void
    {
        $rows = [['item_type' => 'income', 'category_name' => '捐款', 'subtotal' => 8000, 'record_count' => 3]];
        $withRatios = IncomeExpenseReport::summaryWithRatios($rows, 8000.0, 0.0);

        $this->assertSame('捐款', $withRatios[0]['category_name']);
        $this->assertSame(3, $withRatios[0]['record_count']);
        $this->assertArrayHasKey('ratio', $withRatios[0]);
    }
}
