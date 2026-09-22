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
     * 重新計算「小計／合計列」的本年度金額,使其自動加總對應明細,避免手動維護造成誤差。
     *
     * 僅由 is_subtotal(使用者勾選「小計 / 合計列」)決定是否自動加總;未勾選之列一律
     * 維持使用者輸入,不會被覆寫或鎖定。對每一「已勾選」之列:
     *  - 其下一列款/項/目/次/節層級較深者(該列為上層科目,如「業務活動費用」「保險費」):
     *    本年度金額 = 其子樹內所有「未勾選」明細(葉節點)之和,同收益／費損才計入,
     *    略過子樹中其他已勾選之小計列以免重複。
     *  - 否則(無較深子項之獨立小計列,如「業務費合計」):
     *    本年度金額 = 其上方直到上一個已勾選小計列前、同收益／費損之明細加總。
     * 上年度金額(previous_amount)一律保留使用者輸入,不自動加總、不覆寫。
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
        $isSubtotal = static fn (int $i): bool => !empty($items[$i]['is_subtotal']);

        $levels = [];
        for ($i = 0; $i < $count; $i++) {
            $levels[$i] = $levelOf($items[$i]);
        }

        for ($i = 0; $i < $count; $i++) {
            if (!$isSubtotal($i)) {
                continue; // 只有勾選「小計 / 合計列」的列才自動加總。
            }
            $type = $typeOf($items[$i]);
            $sum = 0.0;

            if ($i + 1 < $count && $levels[$i + 1] > $levels[$i]) {
                // 上層科目:加總其子樹內未勾選之葉節點(略過子小計列以免重複)。
                $level = $levels[$i];
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($levels[$j] <= $level) {
                        break; // 離開此列之子樹
                    }
                    if ($typeOf($items[$j]) !== $type || $isSubtotal($j)) {
                        continue;
                    }
                    $sum += (float) ($items[$j]['amount'] ?? 0);
                }
            } else {
                // 獨立小計列:加總其上方、上一個小計列之後、同收益／費損之明細。
                for ($j = $i - 1; $j >= 0; $j--) {
                    if ($isSubtotal($j)) {
                        break;
                    }
                    if ($typeOf($items[$j]) !== $type) {
                        continue;
                    }
                    $sum += (float) ($items[$j]['amount'] ?? 0);
                }
            }

            $items[$i]['amount'] = round($sum, 2);
            // 上年度金額保留使用者輸入,不覆寫。
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
