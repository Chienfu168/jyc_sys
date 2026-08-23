<?php

namespace App\Domain\Donations;

/**
 * 捐款收據編號的純邏輯:年度判定、號碼格式化與序號解析。
 *
 * 從 DonationController 抽出,不依賴資料庫。收據編號格式為
 * `R{西元年}-{4 位序號}`,例如 2024 年第 5 號為 `R2024-0005`;
 * 序號超過 9999 時自然延長(`R2024-10000`)。
 */
final class ReceiptNumber
{
    private const SEQUENCE_PAD = 4;

    /**
     * 由捐款日期(YYYY-MM-DD)判定收據年度;格式不符時退回當年。
     */
    public static function year(string $date): int
    {
        if (preg_match('/^(\d{4})-\d{2}-\d{2}$/', $date, $matches)) {
            return (int) $matches[1];
        }

        return (int) date('Y');
    }

    /**
     * 該年度收據編號前綴,例如 `R2024-`。
     */
    public static function prefix(int $year): string
    {
        return 'R' . $year . '-';
    }

    /**
     * 格式化收據編號,例如 format(2024, 5) => `R2024-0005`。
     */
    public static function format(int $year, int $sequence): string
    {
        return self::prefix($year) . str_pad((string) $sequence, self::SEQUENCE_PAD, '0', STR_PAD_LEFT);
    }

    /**
     * 由收據編號解析出該年度的序號;不屬於該年度或格式不符時回傳 null。
     */
    public static function parseSequence(string $receiptNo, int $year): ?int
    {
        $pattern = '/^' . preg_quote(self::prefix($year), '/') . '(\d+)$/';
        if (preg_match($pattern, $receiptNo, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * 從一組收據編號中找出指定年度的最大序號;沒有相符者回傳 0。
     *
     * @param array<int, string|null> $receiptNos
     */
    public static function maxSequence(array $receiptNos, int $year): int
    {
        $max = 0;
        foreach ($receiptNos as $receiptNo) {
            $sequence = self::parseSequence((string) $receiptNo, $year);
            if ($sequence !== null && $sequence > $max) {
                $max = $sequence;
            }
        }

        return $max;
    }
}
