<?php

namespace App\Domain\AnnualBudgets;

/**
 * 年度預算的彙總計算(純邏輯,不依賴資料庫)。
 *
 * 從 AnnualBudgetController 抽出:預算明細的收入/支出/結餘與去年度對比,
 * 以及預算執行表的預算 vs 實際、執行率與超支/未對應科目統計。
 */
final class BudgetSummary
{
    /**
     * 預算明細彙總:本年度與去年度的收入、支出、結餘。
     *
     * @param array<int, array{item_type?: string, amount?: mixed, previous_amount?: mixed}> $items
     * @return array{
     *     income: float, expense: float, balance: float,
     *     previous_income: float, previous_expense: float, previous_balance: float
     * }
     */
    public static function totals(array $items): array
    {
        $income = 0.0;
        $expense = 0.0;
        $previousIncome = 0.0;
        $previousExpense = 0.0;

        foreach ($items as $item) {
            if (($item['item_type'] ?? '') === 'income') {
                $income += (float) ($item['amount'] ?? 0);
                $previousIncome += (float) ($item['previous_amount'] ?? 0);
            } else {
                $expense += (float) ($item['amount'] ?? 0);
                $previousExpense += (float) ($item['previous_amount'] ?? 0);
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'previous_income' => $previousIncome,
            'previous_expense' => $previousExpense,
            'previous_balance' => $previousIncome - $previousExpense,
        ];
    }

    /**
     * 預算執行彙總:收入/支出的預算與實際、執行率、結餘,以及未對應科目與超支項目計數。
     *
     * @param array<int, array{item_type?: string, amount?: mixed, actual_amount?: mixed, remaining_amount?: mixed, account_id?: mixed}> $items
     * @return array<string, float|int>
     */
    public static function executionTotals(array $items): array
    {
        $totals = [
            'income_budget' => 0.0,
            'income_actual' => 0.0,
            'expense_budget' => 0.0,
            'expense_actual' => 0.0,
            'unmapped' => 0,
            'over_budget' => 0,
        ];

        foreach ($items as $item) {
            $type = ($item['item_type'] ?? '') === 'income' ? 'income' : 'expense';
            $totals[$type . '_budget'] += (float) ($item['amount'] ?? 0);
            $totals[$type . '_actual'] += (float) ($item['actual_amount'] ?? 0);
            if (empty($item['account_id'])) {
                $totals['unmapped']++;
            }
            if (($item['item_type'] ?? '') === 'expense' && (float) ($item['remaining_amount'] ?? 0) < 0) {
                $totals['over_budget']++;
            }
        }

        $totals['income_rate'] = $totals['income_budget'] > 0 ? round(($totals['income_actual'] / $totals['income_budget']) * 100, 2) : 0;
        $totals['expense_rate'] = $totals['expense_budget'] > 0 ? round(($totals['expense_actual'] / $totals['expense_budget']) * 100, 2) : 0;
        $totals['budget_balance'] = $totals['income_budget'] - $totals['expense_budget'];
        $totals['actual_balance'] = $totals['income_actual'] - $totals['expense_actual'];

        return $totals;
    }

    /**
     * 經費預算表的「增(減)比率」:依主管機關格式,以上年度數為分母
     * (D)=(C)/(B)*100,其中 C=本年度-上年度、B=上年度數。上年度為 0 時無法計算比率。
     */
    public static function variancePercent(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
