<?php

namespace App\Domain\CashFlowStatements;

/**
 * 現金流量表的純彙總邏輯(不依賴資料庫)。
 *
 * 參考新北市教育局現金流量表範例:業務/投資/籌資活動之淨現金流入(流出)、
 * 匯率變動影響、現金及約當現金增減數、期初與期末餘額,以及各列比較增(減)比率。
 */
final class CashFlowSummary
{
    /**
     * 依活動別彙總,並計算現金增減數與期末餘額(current 與 prior 兩欄)。
     *
     * @param array<int, array{section?: string, current_amount?: mixed, prior_amount?: mixed}> $items
     * @param array{current?: mixed, prior?: mixed} $exchange 匯率變動影響
     * @param array{current?: mixed, prior?: mixed} $opening 期初現金及約當現金餘額
     * @return array{
     *     operating: array{current: float, prior: float},
     *     investing: array{current: float, prior: float},
     *     financing: array{current: float, prior: float},
     *     exchange: array{current: float, prior: float},
     *     net_change: array{current: float, prior: float},
     *     opening: array{current: float, prior: float},
     *     ending: array{current: float, prior: float}
     * }
     */
    public static function totals(array $items, array $exchange = [], array $opening = []): array
    {
        $sections = [
            'operating' => ['current' => 0.0, 'prior' => 0.0],
            'investing' => ['current' => 0.0, 'prior' => 0.0],
            'financing' => ['current' => 0.0, 'prior' => 0.0],
        ];

        foreach ($items as $item) {
            $section = ($item['section'] ?? 'operating');
            if (!isset($sections[$section])) {
                $section = 'operating';
            }
            $sections[$section]['current'] += (float) ($item['current_amount'] ?? 0);
            $sections[$section]['prior'] += (float) ($item['prior_amount'] ?? 0);
        }

        $exchangeAmounts = [
            'current' => (float) ($exchange['current'] ?? 0),
            'prior' => (float) ($exchange['prior'] ?? 0),
        ];
        $openingAmounts = [
            'current' => (float) ($opening['current'] ?? 0),
            'prior' => (float) ($opening['prior'] ?? 0),
        ];

        $netChange = [
            'current' => $sections['operating']['current'] + $sections['investing']['current'] + $sections['financing']['current'] + $exchangeAmounts['current'],
            'prior' => $sections['operating']['prior'] + $sections['investing']['prior'] + $sections['financing']['prior'] + $exchangeAmounts['prior'],
        ];

        $ending = [
            'current' => $openingAmounts['current'] + $netChange['current'],
            'prior' => $openingAmounts['prior'] + $netChange['prior'],
        ];

        return $sections + [
            'exchange' => $exchangeAmounts,
            'net_change' => $netChange,
            'opening' => $openingAmounts,
            'ending' => $ending,
        ];
    }

    /**
     * 比較增(減)比率 (4)=(3)/(2)*100,分母取上年度決算數絕對值,為 0 時回傳 0。
     */
    public static function variancePercent(float $current, float $prior): float
    {
        if ($prior == 0.0) {
            return 0.0;
        }

        return round((($current - $prior) / abs($prior)) * 100, 2);
    }
}
