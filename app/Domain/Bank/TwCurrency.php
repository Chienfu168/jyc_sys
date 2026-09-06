<?php

namespace App\Domain\Bank;

/**
 * 台幣金額轉國字大寫與逐位數字框,供銀行匯款單(取款憑條)列印。
 *
 * - uppercase():整數金額轉標準國字大寫(壹貳參…拾佰仟萬億),結尾「元整」。
 * - digitColumns():將金額拆為匯款單金額欄各格數字(拾億…元,共 10 格),
 *   高位空白處回傳空字串,方便逐格填入格線。
 */
final class TwCurrency
{
    private const DIGITS = ['零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖'];

    /** 金額欄位由高到低的 10 個位值(對齊彰銀單據:拾億 億 仟萬 佰萬 拾萬 萬 仟 佰 拾 元)。 */
    public const COLUMN_LABELS = ['拾億', '億', '仟萬', '佰萬', '拾萬', '萬', '仟', '佰', '拾', '元'];

    /**
     * 整數金額轉國字大寫(不含「新臺幣」前綴),結尾為「元整」。
     * 金額為 0 時回「零元整」。負數以絕對值處理。
     */
    public static function uppercase(int $amount): string
    {
        $amount = abs($amount);
        if ($amount === 0) {
            return '零元整';
        }

        // 以「億、萬、元」三段分節,各節內套用 仟佰拾 並處理節間的零。
        $sections = [
            ['value' => intdiv($amount, 100000000) % 10000, 'unit' => '億'],
            ['value' => intdiv($amount, 10000) % 10000, 'unit' => '萬'],
            ['value' => $amount % 10000, 'unit' => ''],
        ];

        $result = '';
        $prevSectionZero = false;
        foreach ($sections as $index => $section) {
            $value = $section['value'];
            if ($value === 0) {
                // 節為 0:記錄需要補零(若後面還有非零節)。
                $prevSectionZero = $result !== '';
                continue;
            }

            // 若前面已有內容,且本節不足四位(高位為零),需補一個「零」。
            if ($result !== '' && ($prevSectionZero || $value < 1000)) {
                $result .= '零';
            }
            $result .= self::fourDigits($value) . $section['unit'];
            $prevSectionZero = false;
        }

        return $result . '元整';
    }

    /**
     * 四位數(0-9999)轉國字,含仟佰拾與節內補零(例:1005 → 壹仟零伍)。
     */
    private static function fourDigits(int $value): string
    {
        $units = ['仟', '佰', '拾', ''];
        $digits = [
            intdiv($value, 1000) % 10,
            intdiv($value, 100) % 10,
            intdiv($value, 10) % 10,
            $value % 10,
        ];

        $out = '';
        $pendingZero = false;
        foreach ($digits as $i => $d) {
            if ($d === 0) {
                // 僅在已輸出非零數字、且後面仍有非零數字時,補一個零(避免重複)。
                if ($out !== '') {
                    $pendingZero = true;
                }
                continue;
            }
            if ($pendingZero) {
                $out .= '零';
                $pendingZero = false;
            }
            $out .= self::DIGITS[$d] . $units[$i];
        }

        return $out;
    }

    /**
     * 金額拆為 10 格數字字串(拾億…元),對齊 COLUMN_LABELS。
     * 高位無數字處為空字串。金額超出 10 位(≥ 千億)時,溢位併入最高格顯示。
     *
     * @return array<int, string>
     */
    public static function digitColumns(int $amount): array
    {
        $amount = abs($amount);
        $columns = array_fill(0, 10, '');
        if ($amount === 0) {
            $columns[9] = '0';
            return $columns;
        }

        $digits = str_split((string) $amount);
        $count = count($digits);
        // 由個位往高位填,超過 10 位者全部併入第 0 格。
        for ($i = 0; $i < $count; $i++) {
            $col = 9 - $i;
            $digit = $digits[$count - 1 - $i];
            if ($col <= 0) {
                $columns[0] = $digit . $columns[0];
            } else {
                $columns[$col] = $digit;
            }
        }

        return $columns;
    }
}
