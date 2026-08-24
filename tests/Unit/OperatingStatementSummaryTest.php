<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\OperatingStatements\OperatingStatementSummary;
use PHPUnit\Framework\TestCase;

/**
 * 收支營運表彙總測試:以新北市教育局範例的實際數字驗證合計與比率。
 */
final class OperatingStatementSummaryTest extends TestCase
{
    /** 範例本年度數字(費損與稅務以負數輸入)。 */
    private function referenceItems(): array
    {
        return [
            ['section' => 'income', 'current_amount' => 5000000, 'budget_amount' => 5000000, 'prior_amount' => 5000000],
            ['section' => 'income', 'current_amount' => 9500000, 'budget_amount' => 9000000, 'prior_amount' => 10000000],
            ['section' => 'income', 'current_amount' => 972138, 'budget_amount' => 900000, 'prior_amount' => 553369],
            ['section' => 'income', 'current_amount' => 8251950, 'budget_amount' => 8000000, 'prior_amount' => 8251950],
            ['section' => 'expense', 'current_amount' => -3375728, 'budget_amount' => -3500000, 'prior_amount' => -4407636],
            ['section' => 'expense', 'current_amount' => -3200000, 'budget_amount' => -3000000, 'prior_amount' => -3000000],
            ['section' => 'expense', 'current_amount' => -7800000, 'budget_amount' => -7500000, 'prior_amount' => -8000000],
            ['section' => 'expense', 'current_amount' => -2375728, 'budget_amount' => -2300000, 'prior_amount' => -2500071],
            ['section' => 'expense', 'current_amount' => -375428, 'budget_amount' => -350000, 'prior_amount' => -203889],
            ['section' => 'tax', 'current_amount' => -340000, 'budget_amount' => -300000, 'prior_amount' => -400000],
        ];
    }

    public function test_totals_match_reference_statement(): void
    {
        $totals = OperatingStatementSummary::totals($this->referenceItems());

        $this->assertSame(23724088.0, $totals['income']['current']);
        $this->assertSame(22900000.0, $totals['income']['budget']);
        $this->assertSame(-17126884.0, $totals['expense']['current']);
        $this->assertSame(-16650000.0, $totals['expense']['budget']);

        // 本期稅前賸餘 = 收益合計 + 費損合計
        $this->assertSame(6597204.0, $totals['pretax']['current']);
        $this->assertSame(6250000.0, $totals['pretax']['budget']);
        $this->assertSame(5693723.0, $totals['pretax']['prior']);

        // 本期稅後賸餘 = 稅前賸餘 + 所得稅
        $this->assertSame(6257204.0, $totals['aftertax']['current']);
        $this->assertSame(5950000.0, $totals['aftertax']['budget']);
        $this->assertSame(5293723.0, $totals['aftertax']['prior']);
    }

    public function test_variance_percent_matches_reference_rounding(): void
    {
        // 附屬作業組織收入:9,500,000 vs 9,000,000 → 範例列 6%。
        $this->assertSame(6.0, round(OperatingStatementSummary::variancePercent(9500000, 9000000)));
        // 獎助或捐贈費用(負數):-3,200,000 vs -3,000,000 → 範例列 -7%。
        $this->assertSame(-7.0, round(OperatingStatementSummary::variancePercent(-3200000, -3000000)));
        // 費損合計:-17,126,884 vs -16,650,000 → 範例列 -3%。
        $this->assertSame(-3.0, round(OperatingStatementSummary::variancePercent(-17126884, -16650000)));
        // 所得稅:-340,000 vs -300,000 → 範例列 -13%。
        $this->assertSame(-13.0, round(OperatingStatementSummary::variancePercent(-340000, -300000)));
    }

    public function test_variance_percent_is_zero_when_budget_zero(): void
    {
        $this->assertSame(0.0, OperatingStatementSummary::variancePercent(5000, 0));
    }
}
