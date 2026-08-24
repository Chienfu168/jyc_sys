<?php

namespace App\Domain\BoardMeetings;

/**
 * 董事會議的顯示用文字格式(純邏輯,不依賴資料庫)。
 *
 * 參考新北市教育局範例格式:「財團法人○○教育基金會第2屆第4次董事會」。
 */
final class MeetingLabel
{
    /** 「第X屆第Y次董事會」。 */
    public static function sessionTitle(int $termNo, int $sessionNo): string
    {
        return '第' . $termNo . '屆第' . $sessionNo . '次董事會';
    }

    /** 基金會全名 + 屆次,供標題列使用。 */
    public static function fullTitle(string $foundationName, int $termNo, int $sessionNo): string
    {
        return $foundationName . self::sessionTitle($termNo, $sessionNo);
    }

    /** 狀態代碼轉中文標籤。 */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'confirmed' => '已確認紀錄',
            default => '草稿(議程)',
        };
    }

    /** 出列席角色代碼轉中文標籤。 */
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'observer' => '列席',
            default => '出席',
        };
    }

    /** 出列席狀態代碼轉中文標籤。 */
    public static function attendanceStatusLabel(string $status): string
    {
        return match ($status) {
            'leave' => '請假',
            'proxy' => '委託出席',
            default => '出席',
        };
    }
}
