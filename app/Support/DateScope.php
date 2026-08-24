<?php

namespace App\Support;

/**
 * 列表查詢的期間範圍(純邏輯,不依賴資料庫)。
 *
 * 多數列表頁原本僅能查詢單一月份,查詢較早期資料時得逐月切換。
 * 本類別讓查詢改為三種範圍:依月份(原行為,預設)、依年度(整年)、
 * 全部(不限期間),並統一產出 SQL 條件與參數供各 Controller 套用。
 */
final class DateScope
{
    public const MONTH = 'month';
    public const YEAR = 'year';
    public const ALL = 'all';

    /** 將任意輸入正規化為合法範圍值,非法或缺省時回傳「依月份」。 */
    public static function normalize(?string $scope): string
    {
        return in_array($scope, [self::MONTH, self::YEAR, self::ALL], true) ? $scope : self::MONTH;
    }

    /**
     * 依範圍產出 SQL WHERE 條件片段與對應參數;範圍為「全部」時回傳空字串與空參數(不加條件)。
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function condition(string $column, string $scope, string $month, int $year): array
    {
        return match (self::normalize($scope)) {
            self::YEAR => ["YEAR({$column}) = :scope_year", ['scope_year' => $year]],
            self::ALL => ['', []],
            default => ["DATE_FORMAT({$column}, \"%Y-%m\") = :scope_month", ['scope_month' => $month]],
        };
    }

    /**
     * 同 condition(),但適用於欄位本身即以「YYYY-MM」字串儲存(而非日期型別)的情況,
     * 例如 payroll_records.payroll_month。年度範圍以字串開頭比對,避免對字串欄位做隱式日期轉換。
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function conditionForMonthString(string $column, string $scope, string $month, int $year): array
    {
        return match (self::normalize($scope)) {
            self::YEAR => ["{$column} LIKE :scope_year_prefix", ['scope_year_prefix' => $year . '-%']],
            self::ALL => ['', []],
            default => ["{$column} = :scope_month", ['scope_month' => $month]],
        };
    }
}
