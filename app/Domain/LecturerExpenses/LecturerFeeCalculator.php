<?php

namespace App\Domain\LecturerExpenses;

/**
 * 講師費計算的純邏輯:鐘點費、費用合計與代扣所得稅後淨額。
 *
 * 從 LecturerExpenseController 抽出,不依賴資料庫。代扣稅額由使用者輸入,
 * 本類別負責由時數、鐘點費、交通費、其他費用推算應付總額與實付淨額。
 * 金額一律四捨五入至小數 2 位。
 */
final class LecturerFeeCalculator
{
    /**
     * 鐘點費 = 時數 × 每小時鐘點費。
     */
    public static function lectureFee(float $hours, float $hourlyRate): float
    {
        return round($hours * $hourlyRate, 2);
    }

    /**
     * 應付總額 = 鐘點費 + 交通費 + 其他費用。
     */
    public static function grossTotal(float $lectureFee, float $transportationFee, float $otherFee): float
    {
        return round($lectureFee + $transportationFee + $otherFee, 2);
    }

    /**
     * 實付淨額 = 應付總額 − 代扣所得稅。
     */
    public static function netTotal(float $grossTotal, float $withholdingTax): float
    {
        return round($grossTotal - $withholdingTax, 2);
    }
}
