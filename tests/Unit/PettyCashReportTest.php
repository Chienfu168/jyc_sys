<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\PettyCash\PettyCashReport;
use PHPUnit\Framework\TestCase;

/**
 * 零用金報表計算的測試:總計與項目比例。
 */
final class PettyCashReportTest extends TestCase
{
    public function test_totals_separates_income_and_expense(): void
    {
        $totals = PettyCashReport::totals([
            ['item_type' => 'income', 'amount' => 5000],
            ['item_type' => 'expense', 'amount' => 1200],
            ['item_type' => 'expense', 'amount' => 800],
        ]);

        $this->assertSame(5000.0, $totals['income']);
        $this->assertSame(2000.0, $totals['expense']);
        $this->assertSame(3000.0, $totals['balance']);
    }

    public function test_totals_balance_can_be_negative(): void
    {
        $totals = PettyCashReport::totals([
            ['item_type' => 'income', 'amount' => 100],
            ['item_type' => 'expense', 'amount' => 500],
        ]);

        $this->assertSame(-400.0, $totals['balance']);
    }

    public function test_totals_treats_unknown_type_as_expense(): void
    {
        // 非 income 一律計入支出,與原本 controller 行為一致。
        $totals = PettyCashReport::totals([['item_type' => '', 'amount' => 300]]);
        $this->assertSame(300.0, $totals['expense']);
    }

    public function test_totals_of_empty_is_zero(): void
    {
        $totals = PettyCashReport::totals([]);
        $this->assertSame(0.0, $totals['income']);
        $this->assertSame(0.0, $totals['expense']);
        $this->assertSame(0.0, $totals['balance']);
    }

    public function test_summary_ratios_use_matching_type_total(): void
    {
        $rows = [
            ['item_type' => 'expense', 'item_name' => '文具', 'subtotal' => 1500],
            ['item_type' => 'expense', 'item_name' => '郵資', 'subtotal' => 500],
            ['item_type' => 'income', 'item_name' => '撥補', 'subtotal' => 4000],
        ];

        $withRatios = PettyCashReport::summaryWithRatios($rows, 4000.0, 2000.0);

        $this->assertSame(75.0, $withRatios[0]['ratio']); // 1500 / 2000
        $this->assertSame(25.0, $withRatios[1]['ratio']); // 500 / 2000
        $this->assertSame(100.0, $withRatios[2]['ratio']); // 4000 / 4000
    }

    public function test_summary_ratio_is_zero_when_base_is_zero(): void
    {
        $rows = [['item_type' => 'expense', 'item_name' => 'x', 'subtotal' => 100]];
        $withRatios = PettyCashReport::summaryWithRatios($rows, 0.0, 0.0);

        $this->assertSame(0.0, $withRatios[0]['ratio']);
    }

    public function test_summary_preserves_original_row_keys(): void
    {
        $rows = [['item_type' => 'income', 'item_name' => '撥補', 'subtotal' => 4000, 'entry_count' => 2]];
        $withRatios = PettyCashReport::summaryWithRatios($rows, 4000.0, 0.0);

        $this->assertSame('撥補', $withRatios[0]['item_name']);
        $this->assertSame(2, $withRatios[0]['entry_count']);
        $this->assertArrayHasKey('ratio', $withRatios[0]);
    }
}
