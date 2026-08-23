<?php

namespace App\Domain\Accounting;

/**
 * 會計報表共用的帶號餘額規則(純邏輯,不依賴資料庫)。
 *
 * 每張報表(總帳、明細帳、試算表、收支餘絀表、資產負債表)都要依科目的
 * 「正常餘額方向」把借方/貸方金額換算為帶正負號的餘額,並在試算表把期末
 * 餘額拆回借方欄或貸方欄。這些規則最易出錯,集中於此並以單元測試涵蓋。
 */
final class LedgerMath
{
    /**
     * 依科目正常餘額方向,將借貸總額換算為帶號餘額。
     * 借方科目:借 - 貸;貸方科目:貸 - 借。
     */
    public static function signedAmount(float $debit, float $credit, string $normalBalance): float
    {
        return $normalBalance === 'credit' ? $credit - $debit : $debit - $credit;
    }

    /**
     * 試算表:期末餘額落在借方欄的金額(不為負)。
     * 借方科目取正餘額,貸方科目取負餘額的絕對值。
     */
    public static function endingDebit(float $endingBalance, string $normalBalance): float
    {
        return $normalBalance === 'debit' ? max(0.0, $endingBalance) : max(0.0, -$endingBalance);
    }

    /**
     * 試算表:期末餘額落在貸方欄的金額(不為負)。
     * 貸方科目取正餘額,借方科目取負餘額的絕對值。
     */
    public static function endingCredit(float $endingBalance, string $normalBalance): float
    {
        return $normalBalance === 'credit' ? max(0.0, $endingBalance) : max(0.0, -$endingBalance);
    }
}
