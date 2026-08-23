<?php

namespace App\Domain\IncomeExpenses;

/**
 * 收支報表的純計算:收入/支出/結餘總計,以及各科目占同類型的比例。
 *
 * 從 IncomeExpenseController 與報表 view 抽出,不依賴資料庫。作廢
 * (voided)紀錄不計入總計;比例計算原散在 view 內,集中於此並以單元
 * 測試涵蓋(含除以零防護)。
 */
final class IncomeExpenseReport
{
    /**
     * 由收支紀錄加總收入、支出與結餘,排除已作廢紀錄。
     *
     * @param array<int, array{status?: string, item_type?: string, amount?: mixed}> $records
     * @return array{income: float, expense: float, balance: float}
     */
    public static function totals(array $records): array
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($records as $record) {
            if (($record['status'] ?? '') === 'voided') {
                continue;
            }
            $amount = (float) ($record['amount'] ?? 0);
            if (($record['item_type'] ?? '') === 'income') {
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
     * 為每個科目統計列補上占同類型總額的比例(百分比)。
     * 收入科目以收入總額為分母,支出科目以支出總額為分母;分母為 0 時比例為 0。
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
