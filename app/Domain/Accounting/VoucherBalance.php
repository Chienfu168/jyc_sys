<?php

namespace App\Domain\Accounting;

/**
 * 會計傳票分錄的純計算與驗證邏輯。
 *
 * 從 VoucherController 抽出,不依賴資料庫或請求狀態,方便單元測試涵蓋
 * 借貸平衡這類財務關鍵規則。金額一律以四捨五入至小數 2 位後比較,
 * 避免浮點誤差造成「看似相等卻不相等」。
 */
final class VoucherBalance
{
    /**
     * 加總分錄的借方、貸方與差額。
     *
     * @param array<int, array{debit?: mixed, credit?: mixed}> $lines
     * @return array{debit: float, credit: float, balance: float}
     */
    public static function totals(array $lines): array
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($lines as $line) {
            $debit += (float) ($line['debit'] ?? 0);
            $credit += (float) ($line['credit'] ?? 0);
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $debit - $credit,
        ];
    }

    /**
     * 傳票是否借貸平衡:借方總額需大於 0,且借方等於貸方(四捨五入至 2 位)。
     *
     * @param array<int, array{debit?: mixed, credit?: mixed}> $lines
     */
    public static function isBalanced(array $lines): bool
    {
        $totals = self::totals($lines);

        return $totals['debit'] > 0
            && round($totals['debit'], 2) === round($totals['credit'], 2);
    }

    /**
     * 單筆分錄是否有效:借、貸只能填一邊,且該邊金額大於 0(皆四捨五入至 2 位)。
     */
    public static function lineIsValid(float $debit, float $credit): bool
    {
        $debit = round($debit, 2);
        $credit = round($credit, 2);

        if ($debit < 0 || $credit < 0) {
            return false;
        }

        // 恰好一邊大於 0(等同「不可同時填、也不可同時空」)。
        return ($debit > 0) !== ($credit > 0);
    }
}
