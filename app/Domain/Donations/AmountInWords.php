<?php

namespace App\Domain\Donations;

/**
 * 將金額轉為收據用的國字大寫「分位」格式(純邏輯,不依賴資料庫)。
 *
 * 依基金會實體收據格式,每個數位獨立填寫國字大寫並標註單位,
 * 例如 6,000,000 → 零仟 陸佰 零拾 零萬 零仟 零佰 零拾 零元。
 * 單位以四位一組循環:個位群為 元/拾/佰/仟,萬位群標記 萬,億位群標記 億。
 */
final class AmountInWords
{
    private const DIGITS = ['零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖'];

    /** 依位置(由低到高)的單位:個位群 元拾佰仟,群組邊界標 萬/億/兆。 */
    private const GROUP_MARKS = ['元', '萬', '億', '兆'];
    private const MINOR_MARKS = ['', '拾', '佰', '仟'];

    /**
     * 產生分位格,由高位到低位。至少補足 8 位(千萬),金額更大時自動延伸。
     *
     * @return array<int, array{digit: string, unit: string}>
     */
    public static function grid(int $amount): array
    {
        $amount = max(0, $amount);
        $digitsStr = (string) $amount;
        $length = max(8, strlen($digitsStr));
        $padded = str_pad($digitsStr, $length, '0', STR_PAD_LEFT);

        $cells = [];
        for ($i = 0; $i < $length; $i++) {
            $position = $length - 1 - $i; // 該數位對應的 10 的次方
            $digit = (int) $padded[$i];
            $cells[] = [
                'digit' => self::DIGITS[$digit],
                'unit' => self::unit($position),
            ];
        }

        return $cells;
    }

    /** 位置(10 的次方)對應的國字單位。 */
    private static function unit(int $position): string
    {
        $minor = $position % 4;
        if ($minor === 0) {
            $group = intdiv($position, 4);
            return self::GROUP_MARKS[$group] ?? self::GROUP_MARKS[count(self::GROUP_MARKS) - 1];
        }

        return self::MINOR_MARKS[$minor];
    }

    /** 分位格串接為一行文字(不含「整」)。 */
    public static function text(int $amount): string
    {
        $parts = array_map(
            static fn (array $cell): string => $cell['digit'] . $cell['unit'],
            self::grid($amount)
        );

        return implode('', $parts);
    }
}
