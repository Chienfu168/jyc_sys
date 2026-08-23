<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Payroll\PayrollCalculator;
use PHPUnit\Framework\TestCase;

/**
 * 薪資計算純邏輯的測試 —— 應發、應扣、實發、雇主提繳。
 */
final class PayrollCalculatorTest extends TestCase
{
    public function test_gross_pay_sums_earnings(): void
    {
        $this->assertSame(53000.0, PayrollCalculator::grossPay(40000, 5000, 3000, 5000));
    }

    public function test_gross_pay_rounds_to_two_decimals(): void
    {
        $this->assertSame(100.35, PayrollCalculator::grossPay(100.111, 0.24, 0, 0));
    }

    public function test_deduction_total_sums_all_deductions(): void
    {
        // 勞保 + 健保 + 自提 + 稅 + 請假 + 其他
        $this->assertSame(9500.0, PayrollCalculator::deductionTotal(800, 600, 1500, 5000, 1000, 600));
    }

    public function test_net_pay_is_gross_minus_deduction(): void
    {
        $this->assertSame(43500.0, PayrollCalculator::netPay(53000, 9500));
    }

    public function test_net_pay_can_be_negative(): void
    {
        $this->assertSame(-500.0, PayrollCalculator::netPay(1000, 1500));
    }

    public function test_employer_pension_is_base_times_rate_rounded_to_integer(): void
    {
        // 40000 × 6% = 2400
        $this->assertSame(2400.0, PayrollCalculator::employerPension(40000, 6));
    }

    public function test_employer_pension_rounds_to_integer(): void
    {
        // 33333 × 6% = 1999.98 -> 2000
        $this->assertSame(2000.0, PayrollCalculator::employerPension(33333, 6));
    }

    public function test_full_payroll_flow(): void
    {
        $gross = PayrollCalculator::grossPay(40000, 5000, 3000, 5000);
        $deduction = PayrollCalculator::deductionTotal(800, 600, 1500, 5000, 1000, 600);
        $net = PayrollCalculator::netPay($gross, $deduction);

        $this->assertSame(53000.0, $gross);
        $this->assertSame(9500.0, $deduction);
        $this->assertSame(43500.0, $net);
    }
}
