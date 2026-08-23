<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\LecturerExpenses\LecturerFeeCalculator;
use PHPUnit\Framework\TestCase;

/**
 * 講師費計算純邏輯的測試:鐘點費、應付總額、代扣後淨額。
 */
final class LecturerFeeCalculatorTest extends TestCase
{
    public function test_lecture_fee_is_hours_times_rate(): void
    {
        $this->assertSame(4800.0, LecturerFeeCalculator::lectureFee(3, 1600));
    }

    public function test_lecture_fee_supports_fractional_hours(): void
    {
        $this->assertSame(2400.0, LecturerFeeCalculator::lectureFee(1.5, 1600));
    }

    public function test_lecture_fee_rounds_to_two_decimals(): void
    {
        // 1 × 100.126 = 100.126 -> 100.13
        $this->assertSame(100.13, LecturerFeeCalculator::lectureFee(1, 100.126));
    }

    public function test_gross_total_sums_fees(): void
    {
        $this->assertSame(5500.0, LecturerFeeCalculator::grossTotal(4800, 500, 200));
    }

    public function test_net_total_subtracts_withholding_tax(): void
    {
        // 應付 5500,代扣 10% = 550,實付 4950
        $this->assertSame(4950.0, LecturerFeeCalculator::netTotal(5500, 550));
    }

    public function test_net_total_without_withholding(): void
    {
        $this->assertSame(5500.0, LecturerFeeCalculator::netTotal(5500, 0));
    }

    public function test_full_flow(): void
    {
        $lectureFee = LecturerFeeCalculator::lectureFee(3, 1600);
        $gross = LecturerFeeCalculator::grossTotal($lectureFee, 500, 200);
        $net = LecturerFeeCalculator::netTotal($gross, 480);

        $this->assertSame(4800.0, $lectureFee);
        $this->assertSame(5500.0, $gross);
        $this->assertSame(5020.0, $net);
    }
}
