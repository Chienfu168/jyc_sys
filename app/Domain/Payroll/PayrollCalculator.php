<?php

namespace App\Domain\Payroll;

/**
 * 薪資計算的純邏輯:應發、應扣、實發與雇主提繳退休金。
 *
 * 從 PayrollController 抽出,不依賴資料庫或請求狀態,方便單元測試涵蓋
 * 這些財務關鍵計算。金額除雇主提繳外一律四捨五入至小數 2 位。
 */
final class PayrollCalculator
{
    /**
     * 應發總額 = 本薪 + 加給 + 加班費 + 獎金。
     */
    public static function grossPay(
        float $baseSalary,
        float $allowance,
        float $overtime,
        float $bonus
    ): float {
        return round($baseSalary + $allowance + $overtime + $bonus, 2);
    }

    /**
     * 應扣總額 = 勞保 + 健保 + 自提退休金 + 所得稅 + 請假扣款 + 其他扣款。
     */
    public static function deductionTotal(
        float $laborInsurance,
        float $healthInsurance,
        float $pensionSelf,
        float $incomeTax,
        float $leaveDeduction,
        float $otherDeduction
    ): float {
        return round(
            $laborInsurance
            + $healthInsurance
            + $pensionSelf
            + $incomeTax
            + $leaveDeduction
            + $otherDeduction,
            2
        );
    }

    /**
     * 實發薪資 = 應發 - 應扣。
     */
    public static function netPay(float $grossPay, float $deductionTotal): float
    {
        return round($grossPay - $deductionTotal, 2);
    }

    /**
     * 雇主提繳退休金 = 本薪 × 提繳率(%),四捨五入至整數。
     */
    public static function employerPension(float $baseSalary, float $pensionRate): float
    {
        return round($baseSalary * $pensionRate / 100, 0);
    }
}
