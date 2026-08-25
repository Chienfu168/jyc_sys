<?php

namespace App\Domain\Donations;

/**
 * 將金額轉為收據用的國字大寫金額(純邏輯,不依賴資料庫)。
 *
 * 採一般法定金額寫法,四位一組並正確處理中間與跨組的零,例如:
 *   3,000,000  → 參佰萬
 *   1,050,000  → 壹佰零伍萬
 *   10,001     → 壹萬零壹
 *   12,345,678 → 壹仟貳佰參拾肆萬伍仟陸佰柒拾捌
 * 呼叫端通常在前面加「新台幣」、後面加「元整」。
 */
final class AmountInWords
{
    private const DIGITS = ['零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖'];
    private const MINOR_MARKS = ['', '拾', '佰', '仟'];
    private const GROUP_MARKS = ['', '萬', '億', '兆'];

    /**
     * 金額轉國字大寫(不含「元整」),0 或負數回傳「零」。
     */
    public static function formal(int $amount): string
    {
        $amount = max(0, $amount);
        if ($amount === 0) {
            return '零';
        }

        // 由低位到高位切成四位一組:個級、萬級、億級、兆級。
        $groups = [];
        $n = $amount;
        while ($n > 0) {
            $groups[] = $n % 10000;
            $n = intdiv($n, 10000);
        }

        $result = '';
        for ($g = count($groups) - 1; $g >= 0; $g--) {
            $val = $groups[$g];
            if ($val === 0) {
                continue;
            }

            // 此組不是最高組且本身不足四位(有前導零),需補一個「零」承接。
            if ($result !== '' && $val < 1000 && !str_ends_with($result, '零')) {
                $result .= '零';
            }

            $result .= self::belowTenThousand($val) . self::GROUP_MARKS[$g];
        }

        return $result;
    }

    /** 收據金額文字:新台幣 … 元整(0 顯示「零元整」)。 */
    public static function text(int $amount): string
    {
        return self::formal($amount) . '元整';
    }

    /** 轉換 0..9999 的整數,處理內部零(不含前導/尾端多餘的零)。 */
    private static function belowTenThousand(int $value): string
    {
        $result = '';
        $started = false;
        $zeroPending = false;

        for ($pos = 3; $pos >= 0; $pos--) {
            $digit = intdiv($value, 10 ** $pos) % 10;
            if ($digit === 0) {
                if ($started) {
                    $zeroPending = true;
                }
                continue;
            }

            if ($zeroPending) {
                $result .= self::DIGITS[0];
                $zeroPending = false;
            }
            $result .= self::DIGITS[$digit] . self::MINOR_MARKS[$pos];
            $started = true;
        }

        return $result;
    }
}
