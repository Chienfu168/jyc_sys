<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\BankAccounts\ReconciliationSummary;
use PHPUnit\Framework\TestCase;

/**
 * 銀行交易與對帳彙總的測試。
 */
final class ReconciliationSummaryTest extends TestCase
{
    public function test_totals_classify_deposit_and_interest_as_deposit(): void
    {
        $totals = ReconciliationSummary::totals([
            ['transaction_type' => 'deposit', 'amount' => 5000],
            ['transaction_type' => 'interest', 'amount' => 25],
            ['transaction_type' => 'withdrawal', 'amount' => 1200],
        ]);

        $this->assertSame(5025.0, $totals['deposit']);
        $this->assertSame(1200.0, $totals['withdrawal']);
        $this->assertSame(0.0, $totals['pettyCash']);
    }

    public function test_totals_track_petty_cash_transfer_within_withdrawal(): void
    {
        $totals = ReconciliationSummary::totals([
            ['transaction_type' => 'transfer_to_petty_cash', 'amount' => 2000],
            ['transaction_type' => 'withdrawal', 'amount' => 500],
        ]);

        // 撥入零用金也計入支出總額,同時另計小計
        $this->assertSame(2500.0, $totals['withdrawal']);
        $this->assertSame(2000.0, $totals['pettyCash']);
    }

    public function test_totals_of_empty(): void
    {
        $totals = ReconciliationSummary::totals([]);
        $this->assertSame(0.0, $totals['deposit']);
        $this->assertSame(0.0, $totals['withdrawal']);
        $this->assertSame(0.0, $totals['pettyCash']);
    }

    public function test_reconciliation_totals_sum_amounts_and_count_status(): void
    {
        $transactions = [
            ['transaction_type' => 'deposit', 'amount' => 5000, 'reconciliation_status' => 'reconciled'],
            ['transaction_type' => 'withdrawal', 'amount' => 1200, 'reconciliation_status' => 'unreconciled'],
            ['transaction_type' => 'interest', 'amount' => 25, 'reconciliation_status' => 'reconciled'],
            ['transaction_type' => 'withdrawal', 'amount' => 300, 'reconciliation_status' => 'ignored'],
        ];

        $totals = ReconciliationSummary::reconciliationTotals($transactions);

        $this->assertSame(5025.0, $totals['deposit']);
        $this->assertSame(1500.0, $totals['withdrawal']);
        $this->assertSame(2, $totals['reconciled']);
        $this->assertSame(1, $totals['unreconciled']);
        $this->assertSame(1, $totals['ignored']);
    }

    public function test_reconciliation_totals_default_unknown_status_to_unreconciled(): void
    {
        $totals = ReconciliationSummary::reconciliationTotals([
            ['transaction_type' => 'deposit', 'amount' => 100, 'reconciliation_status' => ''],
        ]);

        $this->assertSame(1, $totals['unreconciled']);
        $this->assertSame(0, $totals['reconciled']);
        $this->assertSame(0, $totals['ignored']);
    }
}
