<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Accounting\VoucherBalance;
use PHPUnit\Framework\TestCase;

/**
 * 會計傳票借貸平衡邏輯的測試 —— 財務關鍵規則。
 */
final class VoucherBalanceTest extends TestCase
{
    public function test_totals_sums_debit_and_credit(): void
    {
        $lines = [
            ['debit' => 1000, 'credit' => 0],
            ['debit' => 0, 'credit' => 400],
            ['debit' => 0, 'credit' => 600],
        ];

        $totals = VoucherBalance::totals($lines);

        $this->assertSame(1000.0, $totals['debit']);
        $this->assertSame(1000.0, $totals['credit']);
        $this->assertSame(0.0, $totals['balance']);
    }

    public function test_totals_handles_missing_keys(): void
    {
        $totals = VoucherBalance::totals([['debit' => 50], ['credit' => 50]]);

        $this->assertSame(50.0, $totals['debit']);
        $this->assertSame(50.0, $totals['credit']);
    }

    public function test_balanced_voucher_is_balanced(): void
    {
        $this->assertTrue(VoucherBalance::isBalanced([
            ['debit' => 1500, 'credit' => 0],
            ['debit' => 0, 'credit' => 1500],
        ]));
    }

    public function test_unbalanced_voucher_is_not_balanced(): void
    {
        $this->assertFalse(VoucherBalance::isBalanced([
            ['debit' => 1500, 'credit' => 0],
            ['debit' => 0, 'credit' => 1400],
        ]));
    }

    public function test_zero_total_is_not_balanced(): void
    {
        // 借方總額為 0 即使「相等」也不算平衡(空傳票不可過帳)。
        $this->assertFalse(VoucherBalance::isBalanced([
            ['debit' => 0, 'credit' => 0],
        ]));
    }

    public function test_balance_tolerates_floating_point_noise(): void
    {
        // 0.1 + 0.2 的浮點誤差不應被誤判為不平衡。
        $this->assertTrue(VoucherBalance::isBalanced([
            ['debit' => 0.1, 'credit' => 0],
            ['debit' => 0.2, 'credit' => 0],
            ['debit' => 0, 'credit' => 0.3],
        ]));
    }

    public function test_line_is_valid_only_when_exactly_one_side_positive(): void
    {
        $this->assertTrue(VoucherBalance::lineIsValid(100.0, 0.0));
        $this->assertTrue(VoucherBalance::lineIsValid(0.0, 100.0));
    }

    public function test_line_is_invalid_when_both_sides_filled(): void
    {
        $this->assertFalse(VoucherBalance::lineIsValid(100.0, 100.0));
    }

    public function test_line_is_invalid_when_both_sides_zero(): void
    {
        $this->assertFalse(VoucherBalance::lineIsValid(0.0, 0.0));
    }

    public function test_line_is_invalid_when_negative(): void
    {
        $this->assertFalse(VoucherBalance::lineIsValid(-100.0, 0.0));
        $this->assertFalse(VoucherBalance::lineIsValid(0.0, -100.0));
    }
}
