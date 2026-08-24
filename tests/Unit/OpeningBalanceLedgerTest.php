<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\OpeningBalances\OpeningBalanceLedger;
use PHPUnit\Framework\TestCase;

/**
 * 期初餘額結轉計算的測試。
 */
final class OpeningBalanceLedgerTest extends TestCase
{
    public function test_closing_is_opening_plus_income_minus_expense(): void
    {
        $this->assertSame(1500.0, OpeningBalanceLedger::closing(1000.0, 800.0, 300.0));
        $this->assertSame(-200.0, OpeningBalanceLedger::closing(0.0, 100.0, 300.0));
    }

    public function test_summary_reports_all_parts(): void
    {
        $summary = OpeningBalanceLedger::summary(5000.0, 2000.0, 1200.0);
        $this->assertSame(5000.0, $summary['opening']);
        $this->assertSame(2000.0, $summary['income']);
        $this->assertSame(1200.0, $summary['expense']);
        $this->assertSame(5800.0, $summary['closing']);
    }

    public function test_roll_forward_accumulates_across_years(): void
    {
        $rows = OpeningBalanceLedger::rollForward(1000.0, [
            ['year' => 2025, 'income' => 500, 'expense' => 200],
            ['year' => 2026, 'income' => 300, 'expense' => 400],
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame(2025, $rows[0]['year']);
        $this->assertSame(1000.0, $rows[0]['opening']);
        $this->assertSame(1300.0, $rows[0]['closing']);
        // 第二年度的期初 = 前一年度的期末。
        $this->assertSame(1300.0, $rows[1]['opening']);
        $this->assertSame(1200.0, $rows[1]['closing']);
    }

    public function test_roll_forward_sorts_years_before_accumulating(): void
    {
        $rows = OpeningBalanceLedger::rollForward(0.0, [
            ['year' => 2026, 'income' => 100, 'expense' => 0],
            ['year' => 2024, 'income' => 500, 'expense' => 0],
            ['year' => 2025, 'income' => 200, 'expense' => 0],
        ]);

        $this->assertSame([2024, 2025, 2026], array_column($rows, 'year'));
        $this->assertSame(500.0, $rows[1]['opening']);
        $this->assertSame(700.0, $rows[2]['opening']);
        $this->assertSame(800.0, $rows[2]['closing']);
    }

    public function test_ledger_module_detection(): void
    {
        $this->assertTrue(OpeningBalanceLedger::isLedgerModule('petty_cash'));
        $this->assertTrue(OpeningBalanceLedger::isLedgerModule('income_expense'));
        $this->assertFalse(OpeningBalanceLedger::isLedgerModule('bank_account'));
    }
}
