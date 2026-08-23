<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 民國/西元年與日期轉換 helper 的測試。
 * 這些函式廣泛用於會計報表、預算年度與收據,正確性影響財務資料呈現。
 */
final class DateHelpersTest extends TestCase
{
    public function test_roc_year_converts_gregorian_to_minguo(): void
    {
        $this->assertSame(113, roc_year(2024));
        $this->assertSame(1, roc_year(1912));
    }

    public function test_roc_year_passes_through_values_already_in_minguo(): void
    {
        // 小於等於 1911 視為已是民國年,直接回傳。
        $this->assertSame(113, roc_year(113));
    }

    public function test_roc_year_defaults_to_current_year(): void
    {
        $this->assertSame((int) date('Y') - 1911, roc_year(null));
        $this->assertSame((int) date('Y') - 1911, roc_year(0));
    }

    public function test_gregorian_year_from_roc_is_inverse_of_roc_year(): void
    {
        $this->assertSame(2024, gregorian_year_from_roc(113));
        $this->assertSame(1912, gregorian_year_from_roc(1));
    }

    public function test_gregorian_year_from_roc_passes_through_gregorian_values(): void
    {
        $this->assertSame(2024, gregorian_year_from_roc(2024));
    }

    public function test_normalize_fiscal_year_matches_gregorian_conversion(): void
    {
        $this->assertSame(2024, normalize_fiscal_year(113));
        $this->assertSame(2024, normalize_fiscal_year(2024));
    }

    public function test_roc_date_formats_valid_iso_date(): void
    {
        $this->assertSame('民國 113 年 8 月 23 日', roc_date('2024-08-23'));
        $this->assertSame('民國 1 年 1 月 1 日', roc_date('1912-01-01'));
    }

    public function test_roc_date_returns_dash_for_invalid_input(): void
    {
        $this->assertSame('-', roc_date(null));
        $this->assertSame('-', roc_date(''));
        $this->assertSame('-', roc_date('2024/08/23'));
        $this->assertSame('-', roc_date('not-a-date'));
    }

    public function test_roc_date_range_joins_two_dates(): void
    {
        $this->assertSame(
            '民國 113 年 1 月 1 日 ~ 民國 113 年 12 月 31 日',
            roc_date_range('2024-01-01', '2024-12-31')
        );
    }

    public function test_roc_year_label(): void
    {
        $this->assertSame('民國 113 年', roc_year_label(2024));
    }
}
