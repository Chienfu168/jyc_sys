<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\CashFlowStatements\CashFlowSummary;
use PHPUnit\Framework\TestCase;

/**
 * 現金流量表彙總測試:以新北市教育局範例的活動淨額驗證現金增減數與期末餘額。
 */
final class CashFlowSummaryTest extends TestCase
{
    public function test_net_change_and_ending_match_reference(): void
    {
        // 以範例各活動淨額作為輸入(current / prior):
        // 業務活動 -2,136,700 / -3,272,627;投資活動 8,251,950 / 8,251,950;籌資 0 / 0。
        $items = [
            ['section' => 'operating', 'current_amount' => -2136700, 'prior_amount' => -3272627],
            ['section' => 'investing', 'current_amount' => 8251950, 'prior_amount' => 8251950],
            ['section' => 'financing', 'current_amount' => 0, 'prior_amount' => 0],
        ];
        $exchange = ['current' => 0, 'prior' => 0];
        $opening = ['current' => 14971012, 'prior' => 9991689];

        $totals = CashFlowSummary::totals($items, $exchange, $opening);

        // 現金及約當現金增(減)數:範例 6,115,250 / 4,979,323。
        $this->assertSame(6115250.0, $totals['net_change']['current']);
        $this->assertSame(4979323.0, $totals['net_change']['prior']);

        // 期末現金及約當現金餘額:範例 21,086,262 / 14,971,012。
        $this->assertSame(21086262.0, $totals['ending']['current']);
        $this->assertSame(14971012.0, $totals['ending']['prior']);
    }

    public function test_section_sums_leaf_items(): void
    {
        $items = [
            ['section' => 'operating', 'current_amount' => -2768838, 'prior_amount' => -3425996],
            ['section' => 'operating', 'current_amount' => 972138, 'prior_amount' => 553369],
            ['section' => 'operating', 'current_amount' => -340000, 'prior_amount' => -400000],
        ];

        $totals = CashFlowSummary::totals($items);

        // 業務活動之淨現金流入(流出):-2,768,838 + 972,138 - 340,000 = -2,136,700。
        $this->assertSame(-2136700.0, $totals['operating']['current']);
        $this->assertSame(-3272627.0, $totals['operating']['prior']);
    }

    public function test_variance_percent_matches_reference_rounding(): void
    {
        // 現金增減數:6,115,250 vs 4,979,323 → 範例列 23%。
        $this->assertSame(23.0, round(CashFlowSummary::variancePercent(6115250, 4979323)));
        // 期初餘額:14,971,012 vs 9,991,689 → 範例列 50%。
        $this->assertSame(50.0, round(CashFlowSummary::variancePercent(14971012, 9991689)));
    }

    public function test_variance_percent_is_zero_when_prior_zero(): void
    {
        $this->assertSame(0.0, CashFlowSummary::variancePercent(5000, 0));
    }
}
