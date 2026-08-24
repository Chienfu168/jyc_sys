<?php

namespace App\Domain\Donations;

/**
 * 捐贈收入清冊的純彙總邏輯(不依賴資料庫)。
 *
 * 參考新北市教育局「捐贈收入清冊」範例:以捐贈單位/人分組列出捐贈金額,並計算合計。
 */
final class DonationListSummary
{
    /**
     * 加總清冊各列的捐贈金額。
     *
     * @param array<int, array{total_amount?: mixed}> $rows
     */
    public static function total(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['total_amount'] ?? 0);
        }

        return $total;
    }
}
