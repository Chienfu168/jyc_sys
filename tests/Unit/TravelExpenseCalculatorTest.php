<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\TravelExpenses\TravelExpenseCalculator;
use PHPUnit\Framework\TestCase;

/**
 * 差旅費計算純邏輯的測試:費用合計、可核銷金額、清單彙總。
 */
final class TravelExpenseCalculatorTest extends TestCase
{
    public function test_total_sums_all_fees(): void
    {
        $this->assertSame(5300.0, TravelExpenseCalculator::total(2000, 2500, 600, 200));
    }

    public function test_total_rounds_to_two_decimals(): void
    {
        $this->assertSame(100.35, TravelExpenseCalculator::total(100.111, 0.24, 0, 0));
    }

    public function test_reimbursable_subtracts_advance(): void
    {
        // 合計 5300,預支 3000,可核銷 2300
        $this->assertSame(2300.0, TravelExpenseCalculator::reimbursable(5300, 3000));
    }

    public function test_reimbursable_can_be_negative_when_advance_exceeds_total(): void
    {
        // 預支超過實支,應退回(負數)
        $this->assertSame(-500.0, TravelExpenseCalculator::reimbursable(1000, 1500));
    }

    public function test_summary_aggregates_and_excludes_voided(): void
    {
        $expenses = [
            ['payment_status' => 'paid', 'total_amount' => 5000, 'advance_amount' => 2000, 'reimbursable_amount' => 3000],
            ['payment_status' => 'draft', 'total_amount' => 1000, 'advance_amount' => 0, 'reimbursable_amount' => 1000],
            ['payment_status' => 'voided', 'total_amount' => 9999, 'advance_amount' => 9999, 'reimbursable_amount' => 9999],
        ];

        $summary = TravelExpenseCalculator::summary($expenses);

        $this->assertSame(6000.0, $summary['total']);
        $this->assertSame(2000.0, $summary['advance']);
        $this->assertSame(4000.0, $summary['reimbursable']);
    }

    public function test_summary_of_empty_is_zero(): void
    {
        $summary = TravelExpenseCalculator::summary([]);
        $this->assertSame(0.0, $summary['total']);
        $this->assertSame(0.0, $summary['advance']);
        $this->assertSame(0.0, $summary['reimbursable']);
    }

    public function test_full_flow(): void
    {
        $total = TravelExpenseCalculator::total(2000, 2500, 600, 200);
        $reimbursable = TravelExpenseCalculator::reimbursable($total, 3000);

        $this->assertSame(5300.0, $total);
        $this->assertSame(2300.0, $reimbursable);
    }
}
