<?php

namespace App\Domain\Projects;

/**
 * 專案成本彙總與預算執行率的純計算。
 *
 * 從 ProjectController 抽出,不依賴資料庫:各成本來源的查詢仍由
 * controller 負責,本類別只負責把來源加總為實支金額、剩餘預算與
 * 執行率。預算為 0(或未編列)時執行率回傳 0,避免除以零。
 */
final class CostSummary
{
    /**
     * @param float $budgetAmount 預算金額
     * @param array<int, array{label: string, count: int|string, amount: int|float|string}> $sources
     *        依顯示順序排列的成本來源
     * @return array{
     *     budget: float,
     *     actual: float,
     *     remaining: float,
     *     execution_rate: float,
     *     sources: array<int, array{label: string, count: int, amount: float}>
     * }
     */
    public static function build(float $budgetAmount, array $sources): array
    {
        $normalized = [];
        $actual = 0.0;

        foreach ($sources as $source) {
            $amount = (float) ($source['amount'] ?? 0);
            $actual += $amount;
            $normalized[] = [
                'label' => (string) ($source['label'] ?? ''),
                'count' => (int) ($source['count'] ?? 0),
                'amount' => $amount,
            ];
        }

        return [
            'budget' => $budgetAmount,
            'actual' => $actual,
            'remaining' => $budgetAmount - $actual,
            'execution_rate' => $budgetAmount > 0 ? round(($actual / $budgetAmount) * 100, 2) : 0.0,
            'sources' => $normalized,
        ];
    }
}
