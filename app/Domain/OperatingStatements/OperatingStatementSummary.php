<?php

namespace App\Domain\OperatingStatements;

/**
 * 收支營運表的純彙總邏輯(不依賴資料庫)。
 *
 * 參考新北市教育局收支營運表範例:收益合計、費損合計、本期稅前賸餘、稅後賸餘,
 * 以及各列「比較增(減)比率」(4)=(3)/(2)*100。費損以負數輸入,故比率以預算數絕對值為分母,
 * 使增減方向與範例一致。
 */
final class OperatingStatementSummary
{
    /**
     * 逐段(收益/費損/稅務調整)彙總三個年度欄位,並計算稅前、稅後賸餘。
     *
     * @param array<int, array{section?: string, prior_amount?: mixed, current_amount?: mixed, budget_amount?: mixed}> $items
     * @return array{
     *     income: array{prior: float, current: float, budget: float},
     *     expense: array{prior: float, current: float, budget: float},
     *     tax: array{prior: float, current: float, budget: float},
     *     pretax: array{prior: float, current: float, budget: float},
     *     aftertax: array{prior: float, current: float, budget: float}
     * }
     */
    public static function totals(array $items): array
    {
        $sections = [
            'income' => ['prior' => 0.0, 'current' => 0.0, 'budget' => 0.0],
            'expense' => ['prior' => 0.0, 'current' => 0.0, 'budget' => 0.0],
            'tax' => ['prior' => 0.0, 'current' => 0.0, 'budget' => 0.0],
        ];

        foreach ($items as $item) {
            $section = ($item['section'] ?? 'income');
            if (!isset($sections[$section])) {
                $section = 'income';
            }
            $sections[$section]['prior'] += (float) ($item['prior_amount'] ?? 0);
            $sections[$section]['current'] += (float) ($item['current_amount'] ?? 0);
            $sections[$section]['budget'] += (float) ($item['budget_amount'] ?? 0);
        }

        $pretax = [
            'prior' => $sections['income']['prior'] + $sections['expense']['prior'],
            'current' => $sections['income']['current'] + $sections['expense']['current'],
            'budget' => $sections['income']['budget'] + $sections['expense']['budget'],
        ];

        $aftertax = [
            'prior' => $pretax['prior'] + $sections['tax']['prior'],
            'current' => $pretax['current'] + $sections['tax']['current'],
            'budget' => $pretax['budget'] + $sections['tax']['budget'],
        ];

        return $sections + ['pretax' => $pretax, 'aftertax' => $aftertax];
    }

    /**
     * 比較增(減)比率 (4)=(3)/(2)*100,分母取預算數絕對值,預算為 0 時回傳 0。
     */
    public static function variancePercent(float $current, float $budget): float
    {
        if ($budget == 0.0) {
            return 0.0;
        }

        return round((($current - $budget) / abs($budget)) * 100, 2);
    }
}
