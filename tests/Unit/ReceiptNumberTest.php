<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Donations\ReceiptNumber;
use PHPUnit\Framework\TestCase;

/**
 * 捐款收據編號純邏輯的測試 —— 收據編號是財務憑證,連號正確性關鍵。
 */
final class ReceiptNumberTest extends TestCase
{
    public function test_year_reads_from_iso_date(): void
    {
        $this->assertSame(2024, ReceiptNumber::year('2024-08-23'));
    }

    public function test_year_falls_back_to_current_year_for_invalid_date(): void
    {
        $this->assertSame((int) date('Y'), ReceiptNumber::year(''));
        $this->assertSame((int) date('Y'), ReceiptNumber::year('2024/08/23'));
    }

    public function test_prefix(): void
    {
        $this->assertSame('R2024-', ReceiptNumber::prefix(2024));
    }

    public function test_format_pads_sequence_to_four_digits(): void
    {
        $this->assertSame('R2024-0001', ReceiptNumber::format(2024, 1));
        $this->assertSame('R2024-0042', ReceiptNumber::format(2024, 42));
        $this->assertSame('R2024-9999', ReceiptNumber::format(2024, 9999));
    }

    public function test_format_extends_beyond_four_digits(): void
    {
        $this->assertSame('R2024-10000', ReceiptNumber::format(2024, 10000));
    }

    public function test_parse_sequence_reads_number_for_matching_year(): void
    {
        $this->assertSame(42, ReceiptNumber::parseSequence('R2024-0042', 2024));
        $this->assertSame(10000, ReceiptNumber::parseSequence('R2024-10000', 2024));
    }

    public function test_parse_sequence_returns_null_for_other_year_or_bad_format(): void
    {
        $this->assertNull(ReceiptNumber::parseSequence('R2023-0042', 2024));
        $this->assertNull(ReceiptNumber::parseSequence('X2024-0042', 2024));
        $this->assertNull(ReceiptNumber::parseSequence('R2024-', 2024));
        $this->assertNull(ReceiptNumber::parseSequence('', 2024));
    }

    public function test_max_sequence_finds_highest_for_year(): void
    {
        $receiptNos = ['R2024-0001', 'R2024-0007', 'R2024-0003', 'R2023-0099', null, 'garbage'];
        $this->assertSame(7, ReceiptNumber::maxSequence($receiptNos, 2024));
    }

    public function test_max_sequence_returns_zero_when_no_match(): void
    {
        $this->assertSame(0, ReceiptNumber::maxSequence(['R2023-0001', null], 2024));
        $this->assertSame(0, ReceiptNumber::maxSequence([], 2024));
    }

    public function test_format_and_parse_are_inverse(): void
    {
        foreach ([1, 55, 9999, 12345] as $sequence) {
            $formatted = ReceiptNumber::format(2024, $sequence);
            $this->assertSame($sequence, ReceiptNumber::parseSequence($formatted, 2024));
        }
    }
}
