<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Donations\DonationNumber;
use PHPUnit\Framework\TestCase;

final class DonationNumberTest extends TestCase
{
    public function test_prefix_uses_date_digits(): void
    {
        $this->assertSame('20260825-', DonationNumber::prefix('2026-08-25'));
    }

    public function test_format_pads_sequence_to_three_digits(): void
    {
        $this->assertSame('20260825-001', DonationNumber::format('2026-08-25', 1));
        $this->assertSame('20260825-042', DonationNumber::format('2026-08-25', 42));
    }

    public function test_format_extends_beyond_three_digits(): void
    {
        $this->assertSame('20260825-1000', DonationNumber::format('2026-08-25', 1000));
    }

    public function test_parse_sequence_returns_null_for_other_date(): void
    {
        $this->assertSame(5, DonationNumber::parseSequence('20260825-005', '2026-08-25'));
        $this->assertNull(DonationNumber::parseSequence('20260825-005', '2026-08-26'));
        $this->assertNull(DonationNumber::parseSequence('R2026-0005', '2026-08-25'));
    }

    public function test_max_sequence_finds_largest_for_date(): void
    {
        $nos = ['20260825-001', '20260825-003', '20260824-009', null, '20260825-002'];
        $this->assertSame(3, DonationNumber::maxSequence($nos, '2026-08-25'));
        $this->assertSame(0, DonationNumber::maxSequence($nos, '2026-08-26'));
    }
}
