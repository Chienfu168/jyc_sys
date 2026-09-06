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
     * 應扣總額 = 勞保 + 健保 + 自提退休金 + 所得稅 + 請假扣款 + 其他扣款 + 二代健保。
     *
     * 二代健保(補充保費)為選填,預設 0,維持既有呼叫相容。
     */
    public static function deductionTotal(
        float $laborInsurance,
        float $healthInsurance,
        float $pensionSelf,
        float $incomeTax,
        float $leaveDeduction,
        float $otherDeduction,
        float $supplementaryPremium = 0.0
    ): float {
        return round(
            $laborInsurance
            + $healthInsurance
            + $pensionSelf
            + $incomeTax
            + $leaveDeduction
            + $otherDeduction
            + $supplementaryPremium,
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

    /**
     * 雇主保險小計(B)= 勞保雇主負擔 + 健保雇主負擔 + 職業災害保險。
     */
    public static function employerInsuranceSubtotal(
        float $employerLaborInsurance,
        float $employerHealthInsurance,
        float $occupationalInsurance
    ): float {
        return round($employerLaborInsurance + $employerHealthInsurance + $occupationalInsurance, 2);
    }

    /**
     * 雇主總負擔(D)= 雇主保險小計(B) + 雇主提繳退休金(C,勞退6%)。
     */
    public static function employerBurdenTotal(
        float $employerInsuranceSubtotal,
        float $employerPension
    ): float {
        return round($employerInsuranceSubtotal + $employerPension, 2);
    }
}
