<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DateScope;
use PHPUnit\Framework\TestCase;

/**
 * 列表查詢期間範圍(月/年/全部)邏輯的測試。
 */
final class DateScopeTest extends TestCase
{
    public function test_normalize_defaults_to_month(): void
    {
        $this->assertSame(DateScope::MONTH, DateScope::normalize(null));
        $this->assertSame(DateScope::MONTH, DateScope::normalize(''));
        $this->assertSame(DateScope::MONTH, DateScope::normalize('bogus'));
    }

    public function test_normalize_accepts_valid_values(): void
    {
        $this->assertSame(DateScope::YEAR, DateScope::normalize('year'));
        $this->assertSame(DateScope::ALL, DateScope::normalize('all'));
    }

    public function test_month_condition_matches_exact_month(): void
    {
        [$sql, $params] = DateScope::condition('occurred_on', 'month', '2026-03', 2026);
        $this->assertSame('DATE_FORMAT(occurred_on, "%Y-%m") = :scope_month', $sql);
        $this->assertSame(['scope_month' => '2026-03'], $params);
    }

    public function test_year_condition_matches_whole_year(): void
    {
        [$sql, $params] = DateScope::condition('occurred_on', 'year', '2026-03', 2026);
        $this->assertSame('YEAR(occurred_on) = :scope_year', $sql);
        $this->assertSame(['scope_year' => 2026], $params);
    }

    public function test_all_condition_has_no_restriction(): void
    {
        [$sql, $params] = DateScope::condition('occurred_on', 'all', '2026-03', 2026);
        $this->assertSame('', $sql);
        $this->assertSame([], $params);
    }

    public function test_invalid_scope_falls_back_to_month(): void
    {
        [$sql] = DateScope::condition('occurred_on', 'nonsense', '2026-03', 2026);
        $this->assertSame('DATE_FORMAT(occurred_on, "%Y-%m") = :scope_month', $sql);
    }

    public function test_month_string_condition_matches_exact_value(): void
    {
        [$sql, $params] = DateScope::conditionForMonthString('payroll_month', 'month', '2026-03', 2026);
        $this->assertSame('payroll_month = :scope_month', $sql);
        $this->assertSame(['scope_month' => '2026-03'], $params);
    }

    public function test_month_string_condition_year_uses_prefix_match(): void
    {
        [$sql, $params] = DateScope::conditionForMonthString('payroll_month', 'year', '2026-03', 2026);
        $this->assertSame('payroll_month LIKE :scope_year_prefix', $sql);
        $this->assertSame(['scope_year_prefix' => '2026-%'], $params);
    }

    public function test_month_string_condition_all_has_no_restriction(): void
    {
        [$sql, $params] = DateScope::conditionForMonthString('payroll_month', 'all', '2026-03', 2026);
        $this->assertSame('', $sql);
        $this->assertSame([], $params);
    }
}
