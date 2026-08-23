<?php

namespace App\Domain\PettyCash;

/**
 * 零用金報表的純計算:收入 / 支出 / 結餘總計,以及各項目占同類型的比例。
 *
 * 從 PettyCashController 與報表 view 抽出,不依賴資料庫。原本比例計算
 * 散在 view 內,集中於此可統一維護並以單元測試涵蓋(含除以零防護)。
 */
final class PettyCashReport
{
    /**
     * 由零用金明細加總收入、支出與結餘。
     *
     * @param array<int, array{item_type?: string, amount?: mixed}> $entries
     * @return array{income: float, expense: float, balance: float}
     */
    public static function totals(array $entries): array
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($entries as $entry) {
            $amount = (float) ($entry['amount'] ?? 0);
            if (($entry['item_type'] ?? '') === 'income') {
                $income += $amount;
            } else {
                $expense += $amount;
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    /**
     * 為每個項目統計列補上占同類型總額的比例(百分比)。
     * 收入項目以收入總額為分母,支出項目以支出總額為分母;分母為 0 時比例為 0。
     *
     * @param array<int, array{item_type?: string, subtotal?: mixed}> $rows
     * @return array<int, array<string, mixed>> 原列加上 `ratio`(float,百分比)
     */
    public static function summaryWithRatios(array $rows, float $incomeTotal, float $expenseTotal): array
    {
        $result = [];
        foreach ($rows as $row) {
            $base = ($row['item_type'] ?? '') === 'income' ? $incomeTotal : $expenseTotal;
            $subtotal = (float) ($row['subtotal'] ?? 0);
            $row['ratio'] = $base > 0 ? ($subtotal / $base * 100) : 0.0;
            $result[] = $row;
        }

        return $result;
    }
}
