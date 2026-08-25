<?php

namespace App\Domain\NetAssetStatements;

/**
 * 淨值變動表的純邏輯(不依賴資料庫)。
 *
 * 參考新北市教育局淨值變動表範例:合計欄為各淨值組成之列總和。
 */
final class NetAssetSummary
{
    /** 淨值組成欄位(對應資料表欄名)。 */
    public const COMPONENTS = [
        'founding_fund',
        'other_fund',
        'capital_reserve',
        'accumulated_surplus',
        'other_equity',
    ];

    /**
     * 單列合計:設立基金 + 其他基金 + 公積 + 累積賸餘 + 淨值其他項目。
     *
     * @param array<string, mixed> $row
     */
    public static function rowTotal(array $row): float
    {
        $total = 0.0;
        foreach (self::COMPONENTS as $component) {
            $total += (float) ($row[$component] ?? 0);
        }

        return $total;
    }

    /**
     * 各欄縱向合計(含 total),供表尾驗算參考。
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, float>
     */
    public static function columnTotals(array $rows): array
    {
        $totals = array_fill_keys(self::COMPONENTS, 0.0);
        $totals['total'] = 0.0;

        foreach ($rows as $row) {
            foreach (self::COMPONENTS as $component) {
                $totals[$component] += (float) ($row[$component] ?? 0);
            }
            $totals['total'] += self::rowTotal($row);
        }

        return $totals;
    }
}
