<?php

namespace App\Domain\TravelExpenses;

/**
 * 差旅費計算的純邏輯:費用合計、扣除預支款後的可核銷金額,以及清單彙總。
 *
 * 從 TravelExpenseController 抽出,不依賴資料庫。金額四捨五入至小數 2 位;
 * 清單彙總排除已作廢(voided)的紀錄,與原本 controller 行為一致。
 */
final class TravelExpenseCalculator
{
    /**
     * 費用合計 = 交通費 + 住宿費 + 膳雜費 + 其他費用。
     */
    public static function total(
        float $transportation,
        float $accommodation,
        float $meal,
        float $miscellaneous
    ): float {
        return round($transportation + $accommodation + $meal + $miscellaneous, 2);
    }

    /**
     * 可核銷金額 = 費用合計 − 預支款。
     */
    public static function reimbursable(float $total, float $advance): float
    {
        return round($total - $advance, 2);
    }

    /**
     * 差旅費清單彙總:合計、預支款、可核銷金額(排除已作廢紀錄)。
     *
     * @param array<int, array{payment_status?: string, total_amount?: mixed, advance_amount?: mixed, reimbursable_amount?: mixed}> $expenses
     * @return array{total: float, advance: float, reimbursable: float}
     */
    public static function summary(array $expenses): array
    {
        $total = 0.0;
        $advance = 0.0;
        $reimbursable = 0.0;

        foreach ($expenses as $expense) {
            if (($expense['payment_status'] ?? '') === 'voided') {
                continue;
            }
            $total += (float) ($expense['total_amount'] ?? 0);
            $advance += (float) ($expense['advance_amount'] ?? 0);
            $reimbursable += (float) ($expense['reimbursable_amount'] ?? 0);
        }

        return [
            'total' => $total,
            'advance' => $advance,
            'reimbursable' => $reimbursable,
        ];
    }
}
