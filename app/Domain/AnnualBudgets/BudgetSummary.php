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
            // 小計／合計列僅供顯示,其金額為其他明細之和,不再計入收益／費損合計(避免重複計算)。
            if (!empty($item['is_subtotal'])) {
                continue;
            }
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
     * 重新計算「聚合列」的金額,使其自動加總對應明細,避免手動維護造成誤差。
     *
     * 依項目在清單中的順序與款/項/目/次/節階層(較深者為其上一列之子項):
     *  - 聚合列(其下一列層級較深,如「業務活動費用」「深耕教育計畫」「保險費」):
     *    金額 = 其子樹內所有葉節點金額之和(僅加葉節點,不重複計入其中的子聚合列)。
     *  - 無階層之小計列(is_subtotal 且未填任何款/項/目/次/節):
     *    金額 = 其上方直到上一個小計列前、同收益／費損之明細加總。
     *  - 其餘為葉節點,維持使用者輸入之金額。
     * is_subtotal 僅代表「不計入收益／費損總計」,與是否為聚合列無關。
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function applySubtotals(array $items): array
    {
        $items = array_values($items);
        $count = count($items);
        if ($count === 0) {
            return $items;
        }

        $levelOf = static function (array $item): int {
            for ($level = 5; $level >= 1; $level--) {
                if (trim((string) ($item['gov_level' . $level] ?? '')) !== '') {
                    return $level;
                }
            }
            return 0;
        };
        $typeOf = static fn (array $item): string => ($item['item_type'] ?? '') === 'income' ? 'income' : 'expense';

        $levels = [];
        for ($i = 0; $i < $count; $i++) {
            $levels[$i] = $levelOf($items[$i]);
        }
        // 聚合列:下一列層級較深者(其明細列緊接於後)。
        $isAggregate = static fn (int $i): bool => $i + 1 < $count && $levels[$i + 1] > $levels[$i];

        for ($i = 0; $i < $count; $i++) {
            $type = $typeOf($items[$i]);

            if ($isAggregate($i)) {
                $level = $levels[$i];
                $sumA = 0.0;
                $sumB = 0.0;
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($levels[$j] <= $level) {
                        break; // 離開此列之子樹
                    }
                    if ($typeOf($items[$j]) !== $type || $isAggregate($j)) {
                        continue; // 只加同類別之葉節點,略過子聚合列避免重複
                    }
                    $sumA += (float) ($items[$j]['amount'] ?? 0);
                    $sumB += (float) ($items[$j]['previous_amount'] ?? 0);
                }
                $items[$i]['amount'] = round($sumA, 2);
                $items[$i]['previous_amount'] = round($sumB, 2);
            } elseif (!empty($items[$i]['is_subtotal']) && $levels[$i] === 0) {
                $sumA = 0.0;
                $sumB = 0.0;
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (!empty($items[$j]['is_subtotal'])) {
                        break;
                    }
                    if ($typeOf($items[$j]) !== $type) {
                        continue;
                    }
                    $sumA += (float) ($items[$j]['amount'] ?? 0);
                    $sumB += (float) ($items[$j]['previous_amount'] ?? 0);
                }
                $items[$i]['amount'] = round($sumA, 2);
                $items[$i]['previous_amount'] = round($sumB, 2);
            }
        }

        return $items;
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
