<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Accounting\LedgerMath;
use PHPUnit\Framework\TestCase;

/**
 * 會計報表帶號餘額規則的測試 —— 帳務正確性核心。
 */
final class LedgerMathTest extends TestCase
{
    public function test_signed_amount_for_debit_account(): void
    {
        // 借方科目(資產/費用):借 - 貸
        $this->assertSame(300.0, LedgerMath::signedAmount(1000, 700, 'debit'));
        $this->assertSame(-200.0, LedgerMath::signedAmount(500, 700, 'debit'));
    }

    public function test_signed_amount_for_credit_account(): void
    {
        // 貸方科目(負債/淨值/收入):貸 - 借
        $this->assertSame(300.0, LedgerMath::signedAmount(700, 1000, 'credit'));
        $this->assertSame(-200.0, LedgerMath::signedAmount(700, 500, 'credit'));
    }

    public function test_signed_amount_defaults_unknown_normal_balance_to_debit_side(): void
    {
        // 非 credit 一律以借方方向計算,與原本 controller 行為一致。
        $this->assertSame(300.0, LedgerMath::signedAmount(1000, 700, ''));
    }

    public function test_ending_debit_takes_positive_balance_for_debit_account(): void
    {
        $this->assertSame(500.0, LedgerMath::endingDebit(500, 'debit'));
        $this->assertSame(0.0, LedgerMath::endingDebit(-500, 'debit'));
    }

    public function test_ending_debit_takes_abs_of_negative_for_credit_account(): void
    {
        // 貸方科目若期末為負(帶號),表示落在借方欄。
        $this->assertSame(500.0, LedgerMath::endingDebit(-500, 'credit'));
        $this->assertSame(0.0, LedgerMath::endingDebit(500, 'credit'));
    }

    public function test_ending_credit_takes_positive_balance_for_credit_account(): void
    {
        $this->assertSame(500.0, LedgerMath::endingCredit(500, 'credit'));
        $this->assertSame(0.0, LedgerMath::endingCredit(-500, 'credit'));
    }

    public function test_ending_credit_takes_abs_of_negative_for_debit_account(): void
    {
        $this->assertSame(500.0, LedgerMath::endingCredit(-500, 'debit'));
        $this->assertSame(0.0, LedgerMath::endingCredit(500, 'debit'));
    }

    public function test_ending_columns_are_mutually_exclusive(): void
    {
        // 同一筆餘額只會落在借方或貸方其中一欄,另一欄為 0。
        foreach ([['debit', 800.0], ['credit', 800.0], ['debit', -800.0], ['credit', -800.0]] as [$normal, $balance]) {
            $debit = LedgerMath::endingDebit($balance, $normal);
            $credit = LedgerMath::endingCredit($balance, $normal);
            $this->assertTrue($debit === 0.0 || $credit === 0.0, "one column must be zero for {$normal} {$balance}");
            $this->assertSame(abs($balance), $debit + $credit);
        }
    }
}
