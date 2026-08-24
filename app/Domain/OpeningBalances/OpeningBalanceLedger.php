<?php

namespace App\Domain\OpeningBalances;

/**
 * 期初餘額(年度結轉)的純計算,不依賴資料庫。
 *
 * 帳本以「期初餘額 + 本期收入 − 本期支出 = 期末餘額」結轉;期末餘額即為
 * 下一年度的期初餘額。Controller 蒐集各年度的收入/支出總額後,呼叫本類別
 * 逐年累積,即可算出任一年度的期初與期末結餘。
 */
final class OpeningBalanceLedger
{
    /** 支援期初餘額結轉的帳本型模組(reference_id 固定為 0)。 */
    public const LEDGER_MODULES = ['petty_cash', 'income_expense'];

    /** 期末餘額 = 期初 + 收入 − 支出。 */
    public static function closing(float $opening, float $income, float $expense): float
    {
        return $opening + $income - $expense;
    }

    /**
     * 依「基準期初餘額」與逐年收支,累積算出每個年度的期初與期末結餘。
     *
     * @param float $baseOpening 起始年度的期初餘額
     * @param array<int, array{year: int, income?: mixed, expense?: mixed}> $yearlyTotals
     *        依年度排序(可不排序,本方法會自行排序)的各年度收入/支出總額
     * @return array<int, array{year: int, opening: float, income: float, expense: float, closing: float}>
     */
    public static function rollForward(float $baseOpening, array $yearlyTotals): array
    {
        usort($yearlyTotals, static fn (array $a, array $b): int => ($a['year'] ?? 0) <=> ($b['year'] ?? 0));

        $result = [];
        $opening = $baseOpening;
        foreach ($yearlyTotals as $row) {
            $income = (float) ($row['income'] ?? 0);
            $expense = (float) ($row['expense'] ?? 0);
            $closing = self::closing($opening, $income, $expense);
            $result[] = [
                'year' => (int) ($row['year'] ?? 0),
                'opening' => $opening,
                'income' => $income,
                'expense' => $expense,
                'closing' => $closing,
            ];
            $opening = $closing;
        }

        return $result;
    }

    /**
     * 單一年度的結餘摘要。
     *
     * @return array{opening: float, income: float, expense: float, closing: float}
     */
    public static function summary(float $opening, float $income, float $expense): array
    {
        return [
            'opening' => $opening,
            'income' => $income,
            'expense' => $expense,
            'closing' => self::closing($opening, $income, $expense),
        ];
    }

    /** 判斷模組代碼是否為支援結轉的帳本型模組。 */
    public static function isLedgerModule(string $module): bool
    {
        return in_array($module, self::LEDGER_MODULES, true);
    }
}
