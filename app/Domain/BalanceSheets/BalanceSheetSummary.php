<?php

namespace App\Domain\BalanceSheets;

/**
 * 資產負債表的純彙總邏輯(不依賴資料庫)。
 *
 * 參考新北市教育局資產負債表範例:資產總計、負債總計、淨值總計、負債及淨值總計,
 * 以及各列「比較增(減)比率」(4)=(3)/(2)*100(以上年底決算數絕對值為分母)。
 */
final class BalanceSheetSummary
{
    /**
     * 依段落(資產/負債/淨值)彙總本年底、上年底金額,並計算負債及淨值總計與平衡差額。
     *
     * @param array<int, array{section?: string, current_amount?: mixed, prior_amount?: mixed}> $items
     * @return array{
     *     asset: array{current: float, prior: float},
     *     liability: array{current: float, prior: float},
     *     equity: array{current: float, prior: float},
     *     liability_equity: array{current: float, prior: float},
     *     balance_check: array{current: float, prior: float}
     * }
     */
    public static function totals(array $items): array
    {
        $sections = [
            'asset' => ['current' => 0.0, 'prior' => 0.0],
            'liability' => ['current' => 0.0, 'prior' => 0.0],
            'equity' => ['current' => 0.0, 'prior' => 0.0],
        ];

        foreach ($items as $item) {
            $section = ($item['section'] ?? 'asset');
            if (!isset($sections[$section])) {
                $section = 'asset';
            }
            $sections[$section]['current'] += (float) ($item['current_amount'] ?? 0);
            $sections[$section]['prior'] += (float) ($item['prior_amount'] ?? 0);
        }

        $liabilityEquity = [
            'current' => $sections['liability']['current'] + $sections['equity']['current'],
            'prior' => $sections['liability']['prior'] + $sections['equity']['prior'],
        ];

        // 平衡差額:資產總計 − (負債及淨值總計),正常應為 0。
        $balanceCheck = [
            'current' => $sections['asset']['current'] - $liabilityEquity['current'],
            'prior' => $sections['asset']['prior'] - $liabilityEquity['prior'],
        ];

        return $sections + [
            'liability_equity' => $liabilityEquity,
            'balance_check' => $balanceCheck,
        ];
    }

    /**
     * 比較增(減)比率 (4)=(3)/(2)*100,分母取上年底決算數絕對值,為 0 時回傳 0。
     */
    public static function variancePercent(float $current, float $prior): float
    {
        if ($prior == 0.0) {
            return 0.0;
        }

        return round((($current - $prior) / abs($prior)) * 100, 2);
    }
}
