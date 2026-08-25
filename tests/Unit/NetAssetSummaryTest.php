<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\NetAssetStatements\NetAssetSummary;
use PHPUnit\Framework\TestCase;

/**
 * 淨值變動表測試:以新北市教育局範例的實際列驗證合計欄。
 */
final class NetAssetSummaryTest extends TestCase
{
    public function test_row_total_matches_reference_opening_balance(): void
    {
        // 113年1月1日餘額:10,000,000 + 132,000,000 + 0 + 9,238,613 + 53,214,542 = 204,453,155。
        $row = [
            'founding_fund' => 10000000,
            'other_fund' => 132000000,
            'capital_reserve' => 0,
            'accumulated_surplus' => 9238613,
            'other_equity' => 53214542,
        ];
        $this->assertSame(204453155.0, NetAssetSummary::rowTotal($row));
    }

    public function test_row_total_matches_reference_closing_balance(): void
    {
        // 114年12月31日餘額:10,000,000 + 132,000,000 + 0 + 20,789,540 + 107,367,402 = 270,156,942。
        $row = [
            'founding_fund' => 10000000,
            'other_fund' => 132000000,
            'capital_reserve' => 0,
            'accumulated_surplus' => 20789540,
            'other_equity' => 107367402,
        ];
        $this->assertSame(270156942.0, NetAssetSummary::rowTotal($row));
    }

    public function test_row_total_of_movement_row(): void
    {
        // 114年度綜合餘絀總額:累積賸餘 6,257,204 + 淨值其他 28,056,630 = 34,313,834。
        $row = [
            'accumulated_surplus' => 6257204,
            'other_equity' => 28056630,
        ];
        $this->assertSame(34313834.0, NetAssetSummary::rowTotal($row));
    }

    public function test_column_totals_sum_components_and_total(): void
    {
        $rows = [
            ['founding_fund' => 10000000, 'other_fund' => 0, 'capital_reserve' => 0, 'accumulated_surplus' => 100, 'other_equity' => 0],
            ['founding_fund' => 0, 'other_fund' => 5000, 'capital_reserve' => 0, 'accumulated_surplus' => 0, 'other_equity' => 200],
        ];
        $totals = NetAssetSummary::columnTotals($rows);

        $this->assertSame(10000000.0, $totals['founding_fund']);
        $this->assertSame(5000.0, $totals['other_fund']);
        $this->assertSame(100.0, $totals['accumulated_surplus']);
        $this->assertSame(200.0, $totals['other_equity']);
        $this->assertSame(10005300.0, $totals['total']);
    }
}
