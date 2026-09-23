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

    public function test_totals_exclude_subtotal_rows(): void
    {
        $items = [
            ['item_type' => 'expense', 'amount' => 3000, 'previous_amount' => 2500],
            ['item_type' => 'expense', 'amount' => 1000, 'previous_amount' => 900],
            // 小計列:金額為上述明細之和,不應再計入合計。
            ['item_type' => 'expense', 'amount' => 4000, 'previous_amount' => 3400, 'is_subtotal' => 1],
            ['item_type' => 'income', 'amount' => 5000, 'previous_amount' => 4000],
            ['item_type' => 'income', 'amount' => 5000, 'previous_amount' => 4000, 'is_subtotal' => true],
        ];

        $totals = BudgetSummary::totals($items);

        $this->assertSame(5000.0, $totals['income']);
        $this->assertSame(4000.0, $totals['expense']);
        $this->assertSame(1000.0, $totals['balance']);
        $this->assertSame(4000.0, $totals['previous_income']);
        $this->assertSame(3400.0, $totals['previous_expense']);
    }

    public function test_totals_count_top_level_subject_not_deepest_leaves(): void
    {
        // 依主管機關格式,加總以各分類最上層科目(目)為準;其下之次/節為明細拆解不再另計。
        // 上層科目採自行輸入之上年度數(本年度未編列而上年度有的項目)亦須正確計入合計。
        $items = [
            // 業務活動費用(目,小計):本年度自動加總 3,900,000;上年度自行輸入 4,571,000。
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level3' => '1', 'is_subtotal' => 1, 'previous_is_manual' => 1, 'amount' => 3900000, 'previous_amount' => 4571000],
            // 其下之次(明細,上年度為 0)——不應另計。
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level4' => '1', 'amount' => 960000, 'previous_amount' => 0],
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level4' => '2', 'amount' => 1240000, 'previous_amount' => 0],
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level4' => '3', 'amount' => 1700000, 'previous_amount' => 0],
            // 同層其他目(葉)。
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level3' => '2', 'amount' => 1000000, 'previous_amount' => 0],
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level3' => '3', 'amount' => 150000, 'previous_amount' => 150000],
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level3' => '4', 'amount' => 100000, 'previous_amount' => 100000],
        ];

        $totals = BudgetSummary::totals($items);

        // 計入:業務活動費用 + 三個目(葉),次不另計。
        $this->assertSame(5150000.0, $totals['expense']);          // 3,900,000+1,000,000+150,000+100,000
        $this->assertSame(4821000.0, $totals['previous_expense']); // 4,571,000(自行輸入)+0+150,000+100,000
    }

    public function test_counted_flags_mark_top_level_and_flat_details(): void
    {
        $items = [
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level3' => '1', 'is_subtotal' => 1], // 0 目:計入
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level4' => '1'],                     // 1 次:不計
            ['item_type' => 'expense', 'category' => '業務費', 'gov_level3' => '2'],                     // 2 目:計入
            ['item_type' => 'income', 'amount' => 100],                                                  // 3 無階層明細:計入
            ['item_type' => 'income', 'amount' => 100, 'is_subtotal' => 1],                              // 4 無階層平列小計:不計
        ];

        $flags = BudgetSummary::countedFlags($items);

        $this->assertSame([true, false, true, true, false], $flags);
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

    public function test_apply_subtotals_sums_hierarchical_descendants(): void
    {
        // 僅「勾選(is_subtotal)」之上層科目自動加總,加總其子樹內「未勾選」之葉節點;
        // 本年度(amount)與上年度(previous_amount)皆一併加總,未勾選之葉節點維持原值。
        $items = [
            ['item_type' => 'expense', 'gov_level3' => '1', 'is_subtotal' => 1, 'amount' => 0, 'previous_amount' => 0],   // 0 業務活動費用(目,勾選)
            ['item_type' => 'expense', 'gov_level4' => '1', 'is_subtotal' => 1, 'amount' => 999, 'previous_amount' => 9],  // 1 深耕(次,勾選)
            ['item_type' => 'expense', 'gov_level5' => '1', 'amount' => 100, 'previous_amount' => 10],                     // 2 豆腐(葉)
            ['item_type' => 'expense', 'gov_level5' => '2', 'amount' => 200, 'previous_amount' => 20],                     // 3 兒童(葉)
            ['item_type' => 'expense', 'gov_level4' => '2', 'is_subtotal' => 1, 'amount' => 0, 'previous_amount' => 9],    // 4 玩聚(次,勾選)
            ['item_type' => 'expense', 'gov_level5' => '1', 'amount' => 300, 'previous_amount' => 30],                     // 5 倡議(葉)
        ];

        $out = BudgetSummary::applySubtotals($items);

        $this->assertSame(300.0, $out[1]['amount']);          // 深耕 本年度 = 100 + 200
        $this->assertSame(30.0, $out[1]['previous_amount']);  // 深耕 上年度 = 10 + 20
        $this->assertSame(300.0, $out[4]['amount']);          // 玩聚 本年度 = 300
        $this->assertSame(30.0, $out[4]['previous_amount']);  // 玩聚 上年度 = 30
        $this->assertSame(600.0, $out[0]['amount']);          // 業務活動費用 本年度 = 100+200+300(只算未勾選葉節點)
        $this->assertSame(60.0, $out[0]['previous_amount']);  // 業務活動費用 上年度 = 10+20+30
        $this->assertSame(100, $out[2]['amount']);            // 未勾選葉節點維持原值
        $this->assertSame(10, $out[2]['previous_amount']);
    }

    public function test_apply_subtotals_only_applies_to_checked_rows(): void
    {
        // 未勾選之列即使下方有較深層級,也不會被自動加總或覆寫。
        $items = [
            ['item_type' => 'expense', 'gov_level3' => '1', 'amount' => 4571000, 'previous_amount' => 4200000], // 0 未勾選:維持原值
            ['item_type' => 'expense', 'gov_level4' => '1', 'amount' => 100, 'previous_amount' => 10],          // 1 葉
            ['item_type' => 'expense', 'gov_level4' => '2', 'amount' => 200, 'previous_amount' => 20],          // 2 葉
        ];

        $out = BudgetSummary::applySubtotals($items);

        $this->assertSame(4571000, $out[0]['amount']);          // 未勾選,不被覆寫
        $this->assertSame(4200000, $out[0]['previous_amount']); // 上年度亦不被覆寫
    }

    public function test_apply_subtotals_keeps_manual_previous_but_sums_amount(): void
    {
        // 小計列標記 previous_is_manual:上年度保留自行輸入,本年度仍自動加總。
        $items = [
            ['item_type' => 'expense', 'gov_level3' => '1', 'is_subtotal' => 1, 'previous_is_manual' => 1, 'amount' => 0, 'previous_amount' => 999999], // 0 小計(上年度自行輸入)
            ['item_type' => 'expense', 'gov_level4' => '1', 'amount' => 100, 'previous_amount' => 10], // 1 葉
            ['item_type' => 'expense', 'gov_level4' => '2', 'amount' => 200, 'previous_amount' => 20], // 2 葉
        ];

        $out = BudgetSummary::applySubtotals($items);

        $this->assertSame(300.0, $out[0]['amount']);          // 本年度仍自動加總 = 100 + 200
        $this->assertSame(999999, $out[0]['previous_amount']); // 上年度保留自行輸入,不被覆寫
    }

    public function test_apply_subtotals_non_hierarchical_sums_block_above(): void
    {
        // 勾選之獨立小計列(無較深子項):本年度與上年度 = 其上方同類明細之和。
        $items = [
            ['item_type' => 'expense', 'amount' => 300, 'previous_amount' => 30],
            ['item_type' => 'expense', 'amount' => 150, 'previous_amount' => 15],
            ['item_type' => 'expense', 'is_subtotal' => 1, 'amount' => 0, 'previous_amount' => 0],
        ];

        $out = BudgetSummary::applySubtotals($items);

        $this->assertSame(450.0, $out[2]['amount']);         // 本年度 = 300 + 150
        $this->assertSame(45.0, $out[2]['previous_amount']); // 上年度 = 30 + 15
    }
}
