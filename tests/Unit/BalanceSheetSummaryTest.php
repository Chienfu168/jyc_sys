<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\BalanceSheets\BalanceSheetSummary;
use PHPUnit\Framework\TestCase;

/**
 * 資產負債表彙總測試:以新北市教育局範例的實際數字驗證合計、平衡與比率。
 */
final class BalanceSheetSummaryTest extends TestCase
{
    /** 範例本年底/上年底數字(節錄主要科目,合計與範例一致)。 */
    private function referenceItems(): array
    {
        return [
            // 資產(流動資產 + 非流動資產,合計 270,807,320 / 236,468,331)
            ['section' => 'asset', 'current_amount' => 21086262, 'prior_amount' => 14971012],
            ['section' => 'asset', 'current_amount' => 193095630, 'prior_amount' => 165039000],
            ['section' => 'asset', 'current_amount' => 349534, 'prior_amount' => 176929],
            ['section' => 'asset', 'current_amount' => 56271772, 'prior_amount' => 56271772],
            ['section' => 'asset', 'current_amount' => 4122, 'prior_amount' => 9618],
            // 負債(合計 650,378 / 625,223)
            ['section' => 'liability', 'current_amount' => 644257, 'prior_amount' => 616949],
            ['section' => 'liability', 'current_amount' => 6121, 'prior_amount' => 8274],
            // 淨值(合計 270,156,942 / 235,843,108)
            ['section' => 'equity', 'current_amount' => 10000000, 'prior_amount' => 10000000],
            ['section' => 'equity', 'current_amount' => 132000000, 'prior_amount' => 132000000],
            ['section' => 'equity', 'current_amount' => 20789540, 'prior_amount' => 14532336],
            ['section' => 'equity', 'current_amount' => 107367402, 'prior_amount' => 79310772],
        ];
    }

    public function test_totals_and_balance_match_reference(): void
    {
        $totals = BalanceSheetSummary::totals($this->referenceItems());

        $this->assertSame(270807320.0, $totals['asset']['current']);
        $this->assertSame(236468331.0, $totals['asset']['prior']);
        $this->assertSame(650378.0, $totals['liability']['current']);
        $this->assertSame(270156942.0, $totals['equity']['current']);

        // 負債及淨值總計應等於資產總計 → 平衡差額為 0。
        $this->assertSame(270807320.0, $totals['liability_equity']['current']);
        $this->assertSame(236468331.0, $totals['liability_equity']['prior']);
        $this->assertSame(0.0, $totals['balance_check']['current']);
        $this->assertSame(0.0, $totals['balance_check']['prior']);
    }

    public function test_variance_percent_matches_reference_rounding(): void
    {
        // 現金及約當現金:21,086,262 vs 14,971,012 → 範例列 41%。
        $this->assertSame(41.0, round(BalanceSheetSummary::variancePercent(21086262, 14971012)));
        // 其他應收款:349,534 vs 176,929 → 範例列 98%。
        $this->assertSame(98.0, round(BalanceSheetSummary::variancePercent(349534, 176929)));
        // 資產總計:270,807,320 vs 236,468,331 → 範例列 15%。
        $this->assertSame(15.0, round(BalanceSheetSummary::variancePercent(270807320, 236468331)));
    }

    public function test_variance_percent_is_zero_when_prior_zero(): void
    {
        $this->assertSame(0.0, BalanceSheetSummary::variancePercent(5000, 0));
    }
}
