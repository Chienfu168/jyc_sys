<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Donations\DonationListSummary;
use PHPUnit\Framework\TestCase;

final class DonationListSummaryTest extends TestCase
{
    public function test_total_sums_all_rows(): void
    {
        $rows = [
            ['name' => '○○機構', 'total_amount' => 420000],
            ['name' => '王大明', 'total_amount' => 50000],
            ['name' => '李小華', 'total_amount' => 12500.5],
        ];

        $this->assertSame(482500.5, DonationListSummary::total($rows));
    }

    public function test_total_of_empty_is_zero(): void
    {
        $this->assertSame(0.0, DonationListSummary::total([]));
    }

    public function test_total_ignores_missing_amount_key(): void
    {
        $rows = [
            ['name' => 'A'],
            ['name' => 'B', 'total_amount' => 1000],
        ];

        $this->assertSame(1000.0, DonationListSummary::total($rows));
    }
}
