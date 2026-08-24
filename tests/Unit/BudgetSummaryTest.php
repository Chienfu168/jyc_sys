<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AnnualBudgets\BudgetSummary;
use PHPUnit\Framework\TestCase;

/**
 * 年度預算彙總的測試:明細合計與執行率統計。
 */
final class BudgetSummaryTest extends TestCase
{
    public function test_totals_separates_income_and_expense_with_previous_year(): void
    {
        $items = [
            ['item_type' => 'income', 'amount' => 5000, 'previous_amount' => 4000],
            ['item_type' => 'expense', 'amount' => 3000, 'previous_amount' => 2500],
            ['item_type' => 'expense', 'amount' => 1000, 'previous_amount' => 900],
        ];

        $totals = BudgetSummary::totals($items);

        $this->assertSame(5000.0, $totals['income']);
        $this->assertSame(4000.0, $totals['expense']);
        $this->assertSame(1000.0, $totals['balance']);
        $this->assertSame(4000.0, $totals['previous_income']);
        $this->assertSame(3400.0, $totals['previous_expense']);
        $this->assertSame(600.0, $totals['previous_balance']);
    }

    public function test_totals_of_empty_is_zero(): void
    {
        $totals = BudgetSummary::totals([]);
        $this->assertSame(0.0, $totals['income']);
        $this->assertSame(0.0, $totals['balance']);
        $this->assertSame(0.0, $totals['previous_balance']);
    }

    public function test_execution_totals_budget_actual_and_rates(): void
    {
        $items = [
            ['item_type' => 'income', 'amount' => 10000, 'actual_amount' => 8000, 'remaining_amount' => 2000, 'account_id' => 1],
            ['item_type' => 'expense', 'amount' => 6000, 'actual_amount' => 3000, 'remaining_amount' => 3000, 'account_id' => 2],
        ];

        $totals = BudgetSummary::executionTotals($items);

        $this->assertSame(10000.0, $totals['income_budget']);
        $this->assertSame(8000.0, $totals['income_actual']);
        $this->assertSame(6000.0, $totals['expense_budget']);
        $this->assertSame(3000.0, $totals['expense_actual']);
        $this->assertSame(80.0, $totals['income_rate']);
        $this->assertSame(50.0, $totals['expense_rate']);
        $this->assertSame(4000.0, $totals['budget_balance']);
        $this->assertSame(5000.0, $totals['actual_balance']);
    }

    public function test_execution_totals_counts_unmapped_and_over_budget(): void
    {
        $items = [
            // 未對應科目(account_id 空)
            ['item_type' => 'expense', 'amount' => 1000, 'actual_amount' => 1200, 'remaining_amount' => -200, 'account_id' => null],
            // 已對應但超支(remaining < 0)
            ['item_type' => 'expense', 'amount' => 500, 'actual_amount' => 800, 'remaining_amount' => -300, 'account_id' => 5],
            // 正常支出
            ['item_type' => 'expense', 'amount' => 2000, 'actual_amount' => 1000, 'remaining_amount' => 1000, 'account_id' => 6],
            // 收入超收不計為超支
            ['item_type' => 'income', 'amount' => 100, 'actual_amount' => 500, 'remaining_amount' => -400, 'account_id' => 7],
        ];

        $totals = BudgetSummary::executionTotals($items);

        $this->assertSame(1, $totals['unmapped']);
        $this->assertSame(2, $totals['over_budget']); // 兩筆支出 remaining < 0
    }

    public function test_execution_rates_are_zero_when_budget_zero(): void
    {
        $items = [
            ['item_type' => 'income', 'amount' => 0, 'actual_amount' => 500, 'remaining_amount' => 0, 'account_id' => 1],
            ['item_type' => 'expense', 'amount' => 0, 'actual_amount' => 300, 'remaining_amount' => 0, 'account_id' => 2],
        ];

        $totals = BudgetSummary::executionTotals($items);

        $this->assertSame(0, $totals['income_rate']);
        $this->assertSame(0, $totals['expense_rate']);
    }

    public function test_variance_percent_divides_by_previous_year_per_reference_format(): void
    {
        // 新北市教育局經費預算表範例:「增(減)比率(%) (D)=(C)/(B)*100」,B 為上年度數。
        // 附屬作業組織收入:A=10,000,000 B=11,000,000 C=-1,000,000 → 範例列出 D=-9%。
        $this->assertSame(-9.09, BudgetSummary::variancePercent(10_000_000, 11_000_000));
        // 利息收入:A=972,138 B=446,707 C=525,431 → 範例列出 D=118%。
        $this->assertSame(117.62, BudgetSummary::variancePercent(972138, 446707));
        // 股利收入:A=10,251,950 B=8,251,950 C=2,000,000 → 範例列出 D=24%。
        $this->assertSame(24.24, BudgetSummary::variancePercent(10251950, 8251950));
    }

    public function test_variance_percent_is_zero_when_previous_is_zero(): void
    {
        $this->assertSame(0.0, BudgetSummary::variancePercent(5000, 0));
    }
}
