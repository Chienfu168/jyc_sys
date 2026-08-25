<?php

namespace App\Domain\Donations;

/**
 * 捐款編號的純邏輯:依捐款日期產生「YYYYMMDD-NNN」當日流水號。
 *
 * 不依賴資料庫。格式為 `{西元年月日}-{3 位當日序號}`,例如 2026-08-25 第 1 筆為
 * `20260825-001`;序號超過 999 時自然延長(`20260825-1000`)。
 */
final class DonationNumber
{
    private const SEQUENCE_PAD = 3;

    /**
     * 由捐款日期(YYYY-MM-DD)取得編號前綴,例如 `20260825-`;格式不符時退回當日。
     */
    public static function prefix(string $date): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return $matches[1] . $matches[2] . $matches[3] . '-';
        }

        return date('Ymd') . '-';
    }

    /**
     * 格式化捐款編號,例如 format('2026-08-25', 1) => `20260825-001`。
     */
    public static function format(string $date, int $sequence): string
    {
        return self::prefix($date) . str_pad((string) $sequence, self::SEQUENCE_PAD, '0', STR_PAD_LEFT);
    }

    /**
     * 由捐款編號解析出當日序號;前綴不符或格式錯誤時回傳 null。
     */
    public static function parseSequence(string $donationNo, string $date): ?int
    {
        $pattern = '/^' . preg_quote(self::prefix($date), '/') . '(\d+)$/';
        if (preg_match($pattern, $donationNo, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * 從一組捐款編號中找出指定日期的最大序號;沒有相符者回傳 0。
     *
     * @param array<int, string|null> $donationNos
     */
    public static function maxSequence(array $donationNos, string $date): int
    {
        $max = 0;
        foreach ($donationNos as $donationNo) {
            $sequence = self::parseSequence((string) $donationNo, $date);
            if ($sequence !== null && $sequence > $max) {
                $max = $sequence;
            }
        }

        return $max;
    }
}
